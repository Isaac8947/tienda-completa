<?php
// Funciones para gestión de inventario
class InventoryManager {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Registra un movimiento de inventario y actualiza el stock
     * NOTA: No maneja transacciones internamente, debe llamarse dentro de una transacción existente
     */
    public function recordMovement($product_id, $order_id, $movement_type, $quantity_change, $reason = null, $user_name = 'Admin') {
        try {
            // Obtener stock actual
            $stmt = $this->pdo->prepare("SELECT stock, name FROM products WHERE id = ?");
            $stmt->execute([$product_id]);
            $product = $stmt->fetch();
            
            if (!$product) {
                throw new Exception("Producto no encontrado");
            }
            
            $quantity_before = $product['stock'];
            $quantity_after = $quantity_before + $quantity_change;
            
            // Verificar que no quede stock negativo
            if ($quantity_after < 0) {
                throw new Exception("Stock insuficiente para el producto: " . $product['name']);
            }
            
            // Actualizar stock del producto
            $update_stmt = $this->pdo->prepare("UPDATE products SET stock = ? WHERE id = ?");
            $update_stmt->execute([$quantity_after, $product_id]);
            
            // Registrar en historial
            $history_stmt = $this->pdo->prepare("
                INSERT INTO inventory_history 
                (product_id, order_id, movement_type, quantity_change, quantity_before, quantity_after, reason, created_by_name) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $history_stmt->execute([
                $product_id,
                $order_id,
                $movement_type,
                $quantity_change,
                $quantity_before,
                $quantity_after,
                $reason,
                $user_name
            ]);
            
            // Verificar stock mínimo y crear notificación si es necesario
            $this->checkLowStock($product_id, $quantity_after);
            
            return true;
            
        } catch (Exception $e) {
            // No manejar transacciones aquí, se manejan en el nivel superior
            error_log('Error en recordMovement: ' . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Procesa una venta cuando se confirma un pedido
     */
    public function processSale($order_id) {
        try {
            // Obtener items del pedido
            $stmt = $this->pdo->prepare("
                SELECT oi.product_id, oi.quantity, p.name 
                FROM order_items oi 
                JOIN products p ON oi.product_id = p.id 
                WHERE oi.order_id = ?
            ");
            $stmt->execute([$order_id]);
            $items = $stmt->fetchAll();
            
            foreach ($items as $item) {
                $this->recordMovement(
                    $item['product_id'],
                    $order_id,
                    'sale',
                    -$item['quantity'], // Negativo porque es una venta
                    "Venta - Pedido #{$order_id}",
                    'Sistema'
                );
            }
            
            // Crear notificación de venta procesada
            $this->createNotification(
                'order',
                "Pedido #{$order_id} confirmado",
                "El pedido ha sido confirmado y el stock ha sido actualizado automáticamente.",
                $order_id,
                'medium'
            );
            
            return true;
            
        } catch (Exception $e) {
            throw $e;
        }
    }
    
    /**
     * Revierte una venta cuando se cancela un pedido
     */
    public function revertSale($order_id) {
        try {
            // Obtener items del pedido
            $stmt = $this->pdo->prepare("
                SELECT oi.product_id, oi.quantity 
                FROM order_items oi 
                WHERE oi.order_id = ?
            ");
            $stmt->execute([$order_id]);
            $items = $stmt->fetchAll();
            
            foreach ($items as $item) {
                $this->recordMovement(
                    $item['product_id'],
                    $order_id,
                    'return',
                    $item['quantity'], // Positivo porque devolvemos stock
                    "Devolución - Cancelación Pedido #{$order_id}",
                    'Sistema'
                );
            }
            
            return true;
            
        } catch (Exception $e) {
            throw $e;
        }
    }
    
    /**
     * Verifica stock bajo y crea notificaciones
     */
    private function checkLowStock($product_id, $current_stock) {
        $stmt = $this->pdo->prepare("SELECT name, min_stock FROM products WHERE id = ?");
        $stmt->execute([$product_id]);
        $product = $stmt->fetch();
        
        if ($product && $current_stock <= $product['min_stock']) {
            $this->createNotification(
                'stock',
                "Stock bajo: {$product['name']}",
                "El producto '{$product['name']}' tiene stock bajo ({$current_stock} unidades). Stock mínimo: {$product['min_stock']}",
                $product_id,
                'high'
            );
        }
    }
    
    /**
     * Crear notificación para admin
     */
    public function createNotification($type, $title, $message, $related_id = null, $priority = 'medium') {
        $stmt = $this->pdo->prepare("
            INSERT INTO admin_notifications (type, title, message, related_id, priority) 
            VALUES (?, ?, ?, ?, ?)
        ");
        
        return $stmt->execute([$type, $title, $message, $related_id, $priority]);
    }
    
    /**
     * Obtener historial de inventario
     */
    public function getInventoryHistory($product_id = null, $limit = 50) {
        $sql = "
            SELECT ih.*, p.name as product_name, p.sku 
            FROM inventory_history ih 
            JOIN products p ON ih.product_id = p.id 
        ";
        
        $params = [];
        if ($product_id) {
            $sql .= " WHERE ih.product_id = ?";
            $params[] = $product_id;
        }
        
        $sql .= " ORDER BY ih.created_at DESC LIMIT ?";
        $params[] = $limit;
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll();
    }
    
    /**
     * Obtener productos con stock bajo
     */
    public function getLowStockProducts() {
        $stmt = $this->pdo->prepare("
            SELECT id, name, sku, stock, min_stock, 
                   (min_stock - stock) as deficit
            FROM products 
            WHERE stock <= min_stock 
            ORDER BY (min_stock - stock) DESC
        ");
        $stmt->execute();
        
        return $stmt->fetchAll();
    }
    
    /**
     * Actualizar stock manualmente
     */
    public function updateStock($product_id, $new_stock, $reason = 'Ajuste manual') {
        $stmt = $this->pdo->prepare("SELECT stock FROM products WHERE id = ?");
        $stmt->execute([$product_id]);
        $current_stock = $stmt->fetchColumn();
        
        $change = $new_stock - $current_stock;
        
        return $this->recordMovement(
            $product_id,
            null,
            'adjustment',
            $change,
            $reason
        );
    }
}
?>
