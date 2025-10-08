<?php
require_once __DIR__ . '/BaseModel.php';

class InventoryHistory extends BaseModel {
    protected $table = 'inventory_history';
    protected $fillable = [
        'product_id', 'movement_type', 'quantity_change', 'quantity_before', 
        'quantity_after', 'reason', 'reference_type', 'reference_id', 
        'admin_id', 'notes'
    ];
    
    /**
     * Registrar un movimiento de inventario
     */
    public function recordMovement($productId, $movementType, $quantityChange, $quantityBefore, $quantityAfter, $options = []) {
        $data = [
            'product_id' => $productId,
            'movement_type' => $movementType,
            'quantity_change' => $quantityChange,
            'quantity_before' => $quantityBefore,
            'quantity_after' => $quantityAfter,
            'reason' => $options['reason'] ?? '',
            'reference_type' => $options['reference_type'] ?? 'other',
            'reference_id' => $options['reference_id'] ?? null,
            'admin_id' => $options['admin_id'] ?? $_SESSION['admin_id'] ?? null,
            'notes' => $options['notes'] ?? ''
        ];
        
        return $this->create($data);
    }
    
    /**
     * Obtener historial de un producto específico
     */
    public function getProductHistory($productId, $limit = 50) {
        $sql = "SELECT ih.*, p.name as product_name, p.sku, a.full_name as admin_name
                FROM inventory_history ih
                LEFT JOIN products p ON ih.product_id = p.id
                LEFT JOIN admins a ON ih.admin_id = a.id
                WHERE ih.product_id = ?
                ORDER BY ih.created_at DESC
                LIMIT ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$productId, $limit]);
        return $stmt->fetchAll();
    }
    
    /**
     * Obtener historial general con filtros
     */
    public function getHistory($filters = [], $limit = 100) {
        $conditions = [];
        $params = [];
        
        if (!empty($filters['product_id'])) {
            $conditions[] = "ih.product_id = ?";
            $params[] = $filters['product_id'];
        }
        
        if (!empty($filters['movement_type'])) {
            $conditions[] = "ih.movement_type = ?";
            $params[] = $filters['movement_type'];
        }
        
        if (!empty($filters['reference_type'])) {
            $conditions[] = "ih.reference_type = ?";
            $params[] = $filters['reference_type'];
        }
        
        if (!empty($filters['date_from'])) {
            $conditions[] = "DATE(ih.created_at) >= ?";
            $params[] = $filters['date_from'];
        }
        
        if (!empty($filters['date_to'])) {
            $conditions[] = "DATE(ih.created_at) <= ?";
            $params[] = $filters['date_to'];
        }
        
        $whereClause = !empty($conditions) ? "WHERE " . implode(" AND ", $conditions) : "";
        
        $sql = "SELECT ih.*, p.name as product_name, p.sku, p.main_image, a.full_name as admin_name
                FROM inventory_history ih
                LEFT JOIN products p ON ih.product_id = p.id
                LEFT JOIN admins a ON ih.admin_id = a.id
                $whereClause
                ORDER BY ih.created_at DESC
                LIMIT ?";
        
        $params[] = $limit;
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    
    /**
     * Obtener estadísticas de movimientos
     */
    public function getMovementStats($period = '30 days') {
        try {
            // Extraer número de días del período
            $days = (int) filter_var($period, FILTER_SANITIZE_NUMBER_INT);
            if ($days <= 0) $days = 30;
            
            $sql = "SELECT 
                        movement_type,
                        COUNT(*) as total_movements,
                        SUM(ABS(quantity_change)) as total_quantity,
                        AVG(ABS(quantity_change)) as avg_quantity
                    FROM inventory_history 
                    WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
                    GROUP BY movement_type
                    ORDER BY total_movements DESC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$days]);
            return $stmt->fetchAll();
            
        } catch (Exception $e) {
            error_log("Error getting movement stats: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obtener productos con más movimientos
     */
    public function getTopMovedProducts($limit = 10, $period = '30 days') {
        $days = (int) filter_var($period, FILTER_SANITIZE_NUMBER_INT);
        if ($days <= 0) $days = 30;
        
        $sql = "SELECT 
                    p.id, p.name, p.sku, p.main_image,
                    COUNT(ih.id) as total_movements,
                    SUM(CASE WHEN ih.movement_type = 'in' THEN ih.quantity_change ELSE 0 END) as total_in,
                    SUM(CASE WHEN ih.movement_type = 'out' THEN ABS(ih.quantity_change) ELSE 0 END) as total_out,
                    p.inventory_quantity as current_stock
                FROM products p
                LEFT JOIN inventory_history ih ON p.id = ih.product_id 
                    AND ih.created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
                GROUP BY p.id, p.name, p.sku, p.main_image, p.inventory_quantity
                HAVING total_movements > 0
                ORDER BY total_movements DESC
                LIMIT ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$days, $limit]);
        return $stmt->fetchAll();
    }
    
    /**
     * Registrar entrada de stock
     */
    public function recordStockIn($productId, $quantity, $reason = 'Restock', $options = []) {
        // Obtener cantidad actual
        $product = new Product();
        $productData = $product->findById($productId);
        
        if (!$productData) {
            return false;
        }
        
        $quantityBefore = $productData['inventory_quantity'];
        $quantityAfter = $quantityBefore + $quantity;
        
        // Actualizar el stock del producto
        $product->update($productId, ['inventory_quantity' => $quantityAfter]);
        
        // Registrar el movimiento
        return $this->recordMovement(
            $productId, 
            'in', 
            $quantity, 
            $quantityBefore, 
            $quantityAfter,
            array_merge($options, [
                'reason' => $reason,
                'reference_type' => $options['reference_type'] ?? 'restock'
            ])
        );
    }
    
    /**
     * Registrar salida de stock
     */
    public function recordStockOut($productId, $quantity, $reason = 'Sale', $options = []) {
        // Obtener cantidad actual
        $product = new Product();
        $productData = $product->findById($productId);
        
        if (!$productData) {
            return false;
        }
        
        $quantityBefore = $productData['inventory_quantity'];
        $quantityAfter = $quantityBefore - $quantity;
        
        // Actualizar el stock del producto
        $product->update($productId, ['inventory_quantity' => $quantityAfter]);
        
        // Registrar el movimiento (cantidad negativa para salida)
        return $this->recordMovement(
            $productId, 
            'out', 
            -$quantity, 
            $quantityBefore, 
            $quantityAfter,
            array_merge($options, [
                'reason' => $reason,
                'reference_type' => $options['reference_type'] ?? 'sale'
            ])
        );
    }
    
    /**
     * Ajustar stock manualmente
     */
    public function adjustStock($productId, $newQuantity, $reason = 'Manual adjustment', $options = []) {
        // Obtener cantidad actual
        $product = new Product();
        $productData = $product->findById($productId);
        
        if (!$productData) {
            return false;
        }
        
        $quantityBefore = $productData['inventory_quantity'];
        $quantityChange = $newQuantity - $quantityBefore;
        
        // Actualizar el stock del producto
        $product->update($productId, ['inventory_quantity' => $newQuantity]);
        
        // Registrar el movimiento
        return $this->recordMovement(
            $productId, 
            'adjustment', 
            $quantityChange, 
            $quantityBefore, 
            $newQuantity,
            array_merge($options, [
                'reason' => $reason,
                'reference_type' => 'adjustment'
            ])
        );
    }
}
?>
