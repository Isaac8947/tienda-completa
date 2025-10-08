<?php
/**
 * Validador de Inventario - Versión Simplificada
 * Funciones para validar disponibilidad de stock antes de procesar pedidos
 */

class InventoryValidator {
    private $db;
    
    public function __construct($database) {
        $this->db = $database;
    }
    
    /**
     * Validar stock disponible para un carrito
     * @param array $cartItems Array de items del carrito
     * @return array Array con resultado y detalles
     */
    public function validateCartStock($cartItems) {
        $result = [
            'valid' => true,
            'errors' => [],
            'warnings' => []
        ];
        
        try {
            foreach ($cartItems as $item) {
                // Obtener información del producto directamente
                $productId = isset($item['id']) ? $item['id'] : $item['product_id'];
                $requestedQty = $item['quantity'];
                
                $stmt = $this->db->prepare("SELECT id, name, stock, min_stock, status FROM products WHERE id = ? AND status = 'active'");
                $stmt->execute([$productId]);
                $product = $stmt->fetch();
                
                if (!$product) {
                    $result['valid'] = false;
                    $result['errors'][] = [
                        'product_id' => $productId,
                        'message' => "Producto no encontrado o inactivo",
                        'requested' => $requestedQty,
                        'available' => 0
                    ];
                    continue;
                }
                
                // Verificar stock suficiente
                if ($product['stock'] < $requestedQty) {
                    $result['valid'] = false;
                    $result['errors'][] = [
                        'product_id' => $productId,
                        'product_name' => $product['name'],
                        'message' => "Stock insuficiente",
                        'requested' => $requestedQty,
                        'available' => $product['stock']
                    ];
                }
                
                // Advertencia si queda stock bajo
                $remainingStock = $product['stock'] - $requestedQty;
                if ($remainingStock <= $product['min_stock'] && $remainingStock > 0) {
                    $result['warnings'][] = [
                        'product_id' => $productId,
                        'product_name' => $product['name'],
                        'message' => "Stock quedará bajo después de la venta",
                        'remaining_stock' => $remainingStock,
                        'min_stock' => $product['min_stock']
                    ];
                }
            }
        } catch (Exception $e) {
            $result['valid'] = false;
            $result['errors'][] = [
                'message' => 'Error validando stock: ' . $e->getMessage()
            ];
        }
        
        return $result;
    }
    
    /**
     * Verificar disponibilidad de un producto específico
     */
    public function getAvailableStock($productId) {
        try {
            $stmt = $this->db->prepare("SELECT stock FROM products WHERE id = ? AND status = 'active'");
            $stmt->execute([$productId]);
            $result = $stmt->fetch();
            
            return $result ? (int)$result['stock'] : 0;
        } catch (Exception $e) {
            return 0;
        }
    }
    
    /**
     * Obtener productos con stock bajo
     */
    public function getLowStockAlerts() {
        try {
            $stmt = $this->db->query("SELECT id, name, stock, min_stock FROM products WHERE stock <= min_stock AND status = 'active'");
            return $stmt->fetchAll();
        } catch (Exception $e) {
            return [];
        }
    }
    
    /**
     * Obtener productos sin stock
     */
    public function getOutOfStockProducts() {
        try {
            $stmt = $this->db->query("SELECT id, name FROM products WHERE stock = 0 AND status = 'active'");
            return $stmt->fetchAll();
        } catch (Exception $e) {
            return [];
        }
    }
}
?>
