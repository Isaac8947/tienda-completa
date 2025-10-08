<?php
/**
 * Validador de Inventario
 * Funciones para validar disponibilidad de stock antes de procesar pedidos
 */

class InventoryValidator {
    private $db;
    
    public function __construct($database) {
        $this->db = $database;
    }
    
    /**
     * Validar stock disponible para un carrito
     * @param array $cartItems Array de items del carrito con product_id y quantity
     * @return array Array con resultado y detalles de productos sin stock
     */
    public function validateCartStock($cartItems) {
        $result = [
            'valid' => true,
            'errors' => [],
            'warnings' => []
        ];
        
        foreach ($cartItems as $item) {
            $stockInfo = $this->getProductStock($item['product_id']);
            
            if (!$stockInfo) {
                $result['valid'] = false;
                $result['errors'][] = [
                    'product_id' => $item['product_id'],
                    'message' => "Producto no encontrado o inactivo",
                    'requested' => $item['quantity'],
                    'available' => 0
                ];
                continue;
            }
            
            // Verificar si hay stock suficiente
            if ($stockInfo['stock'] < $item['quantity']) {
                $result['valid'] = false;
                $result['errors'][] = [
                    'product_id' => $item['product_id'],
                    'product_name' => $stockInfo['name'],
                    'message' => "Stock insuficiente",
                    'requested' => $item['quantity'],
                    'available' => $stockInfo['stock']
                ];
            }
            
            // Advertencia si el stock queda muy bajo después de la venta
            $remainingStock = $stockInfo['stock'] - $item['quantity'];
            if ($remainingStock <= $stockInfo['min_stock'] && $remainingStock > 0) {
                $result['warnings'][] = [
                    'product_id' => $item['product_id'],
                    'product_name' => $stockInfo['name'],
                    'message' => "Stock quedará bajo después de la venta",
                    'remaining_stock' => $remainingStock,
                    'min_stock' => $stockInfo['min_stock']
                ];
            }
        }
        
        return $result;
    }
    
    /**
     * Obtener información de stock de un producto
     */
    private function getProductStock($productId) {
        $sql = "SELECT id, name, stock, min_stock, status FROM products WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$productId]);
        
        $product = $stmt->fetch();
        
        // Solo retornar si el producto está activo
        if ($product && $product['status'] === 'active') {
            return $product;
        }
        
        return null;
    }
    
    /**
     * Reservar stock temporalmente (para prevenir overselling)
     * Esto es útil durante el proceso de checkout
     */
    public function reserveStock($cartItems, $sessionId, $expirationMinutes = 15) {
        try {
            $this->db->beginTransaction();
            
            // Primero validar que hay stock disponible
            $validation = $this->validateCartStock($cartItems);
            if (!$validation['valid']) {
                throw new Exception('Stock insuficiente para completar la reserva');
            }
            
            // Limpiar reservas expiradas
            $this->cleanExpiredReservations();
            
            // Crear reservas
            foreach ($cartItems as $item) {
                $sql = "INSERT INTO stock_reservations (product_id, session_id, quantity, expires_at, created_at) 
                        VALUES (?, ?, ?, DATE_ADD(NOW(), INTERVAL ? MINUTE), NOW())
                        ON DUPLICATE KEY UPDATE 
                        quantity = VALUES(quantity),
                        expires_at = VALUES(expires_at)";
                        
                $stmt = $this->db->prepare($sql);
                $stmt->execute([
                    $item['product_id'],
                    $sessionId,
                    $item['quantity'],
                    $expirationMinutes
                ]);
            }
            
            $this->db->commit();
            return true;
            
        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }
    
    /**
     * Liberar reservas de stock
     */
    public function releaseStockReservation($sessionId, $productId = null) {
        if ($productId) {
            $sql = "DELETE FROM stock_reservations WHERE session_id = ? AND product_id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$sessionId, $productId]);
        } else {
            $sql = "DELETE FROM stock_reservations WHERE session_id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$sessionId]);
        }
    }
    
    /**
     * Limpiar reservas expiradas
     */
    private function cleanExpiredReservations() {
        $sql = "DELETE FROM stock_reservations WHERE expires_at < NOW()";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
    }
    
    /**
     * Obtener stock disponible considerando reservas
     */
    public function getAvailableStock($productId) {
        $sql = "SELECT p.stock,
                       COALESCE(SUM(sr.quantity), 0) as reserved_stock,
                       (p.stock - COALESCE(SUM(sr.quantity), 0)) as available_stock
                FROM products p
                LEFT JOIN stock_reservations sr ON p.id = sr.product_id AND sr.expires_at > NOW()
                WHERE p.id = ? AND p.status = 'active'
                GROUP BY p.id";
                
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$productId]);
        
        return $stmt->fetch();
    }
    
    /**
     * Obtener alertas de stock bajo
     */
    public function getLowStockAlerts() {
        $sql = "SELECT id, name, sku, stock, min_stock, 
                       (min_stock - stock) as deficit
                FROM products 
                WHERE stock <= min_stock AND status = 'active'
                ORDER BY deficit DESC";
                
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }
    
    /**
     * Obtener productos sin stock
     */
    public function getOutOfStockProducts() {
        $sql = "SELECT id, name, sku, stock, min_stock
                FROM products 
                WHERE stock = 0 AND status = 'active'
                ORDER BY name";
                
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }
}
