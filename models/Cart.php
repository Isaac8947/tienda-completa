<?php
require_once __DIR__ . '/BaseModel.php';

class Cart extends BaseModel {
    protected $table = 'carts';
    protected $fillable = [
        'customer_id', 'session_id', 'coupon_id', 'subtotal', 'discount_amount',
        'tax_amount', 'shipping_amount', 'total'
    ];
    
    public function getOrCreateCart($customerId = null, $sessionId = null) {
        if ($customerId) {
            $cart = $this->findOne(['customer_id' => $customerId]);
        } else {
            $cart = $this->findOne(['session_id' => $sessionId]);
        }
        
        if (!$cart) {
            $cartData = [
                'customer_id' => $customerId,
                'session_id' => $sessionId,
                'subtotal' => 0,
                'discount_amount' => 0,
                'tax_amount' => 0,
                'shipping_amount' => 0,
                'total' => 0
            ];
            $cartId = $this->create($cartData);
            return $this->findById($cartId);
        }
        
        return $cart;
    }
    
    public function addItem($cartId, $productId, $quantity = 1) {
        // Verificar stock
        require_once __DIR__ . '/Product.php';
        $productModel = new Product();
        
        if (!$productModel->checkStock($productId, $quantity)) {
            throw new Exception('Stock insuficiente');
        }
        
        // Verificar si el item ya existe
        $sql = "SELECT * FROM cart_items WHERE cart_id = ? AND product_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$cartId, $productId]);
        $existingItem = $stmt->fetch();
        
        // Obtener precio del producto
        $product = $productModel->findById($productId);
        $price = $product['price'];
        
        if ($existingItem) {
            // Actualizar cantidad
            $newQuantity = $existingItem['quantity'] + $quantity;
            
            // Verificar stock para la nueva cantidad
            if (!$productModel->checkStock($productId, $newQuantity)) {
                throw new Exception('Stock insuficiente para la cantidad solicitada');
            }
            
            $updateSql = "UPDATE cart_items SET quantity = ?, updated_at = NOW() WHERE id = ?";
            $updateStmt = $this->db->prepare($updateSql);
            $result = $updateStmt->execute([$newQuantity, $existingItem['id']]);
        } else {
            // Crear nuevo item
            $insertSql = "INSERT INTO cart_items (cart_id, product_id, quantity, price, created_at, updated_at) 
                         VALUES (?, ?, ?, ?, NOW(), NOW())";
            $insertStmt = $this->db->prepare($insertSql);
            $result = $insertStmt->execute([$cartId, $productId, $quantity, $price]);
        }
        
        if ($result) {
            $this->updateCartTotals($cartId);
        }
        
        return $result;
    }
    
    public function updateItemQuantity($cartId, $itemId, $quantity) {
        if ($quantity <= 0) {
            return $this->removeItem($cartId, $itemId);
        }
        
        $updateSql = "UPDATE cart_items SET quantity = ?, updated_at = NOW() 
                      WHERE id = ? AND cart_id = ?";
        $updateStmt = $this->db->prepare($updateSql);
        
        if ($updateStmt->execute([$quantity, $itemId, $cartId])) {
            $this->updateCartTotals($cartId);
            return true;
        }
        
        return false;
    }
    
    public function removeItem($cartId, $itemId) {
        $sql = "DELETE FROM cart_items WHERE id = ? AND cart_id = ?";
        $stmt = $this->db->prepare($sql);
        
        if ($stmt->execute([$itemId, $cartId])) {
            $this->updateCartTotals($cartId);
            return true;
        }
        
        return false;
    }
    
    public function getCartItems($cartId) {
        $sql = "SELECT ci.*, p.name as product_name, p.main_image, p.slug as product_slug,
                       b.name as brand_name
                FROM cart_items ci
                JOIN products p ON ci.product_id = p.id
                LEFT JOIN brands b ON p.brand_id = b.id
                WHERE ci.cart_id = ?
                ORDER BY ci.created_at DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$cartId]);
        return $stmt->fetchAll();
    }
    
    private function updateCartTotals($cartId) {
        // Calcular subtotal
        $sql = "SELECT SUM(quantity * price) as subtotal FROM cart_items WHERE cart_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$cartId]);
        $result = $stmt->fetch();
        $subtotal = $result['subtotal'] ?: 0;
        
        // Calcular impuestos (19% en Colombia)
        $taxRate = 0.19;
        $taxAmount = $subtotal * $taxRate;
        
        // Calcular envío
        $shippingAmount = $this->calculateShipping($subtotal);
        
        $total = $subtotal + $taxAmount + $shippingAmount;
        
        // Actualizar carrito
        return $this->update($cartId, [
            'subtotal' => $subtotal,
            'tax_amount' => $taxAmount,
            'shipping_amount' => $shippingAmount,
            'total' => $total
        ]);
    }
    
    private function calculateShipping($subtotal) {
        // Envío gratis por encima del umbral
        $freeShippingThreshold = 150000;
        if ($subtotal >= $freeShippingThreshold) {
            return 0;
        }
        
        // Costo de envío estándar
        return 15000;
    }
    
    public function getCartCount($cartId) {
        $sql = "SELECT SUM(quantity) as total_items FROM cart_items WHERE cart_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$cartId]);
        $result = $stmt->fetch();
        return $result['total_items'] ?: 0;
    }
    
    public function clearCart($cartId) {
        $sql = "DELETE FROM cart_items WHERE cart_id = ?";
        $stmt = $this->db->prepare($sql);
        
        if ($stmt->execute([$cartId])) {
            $this->updateCartTotals($cartId);
            return true;
        }
        
        return false;
    }
}
?>
