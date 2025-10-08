<?php
session_start();
header('Content-Type: application/json');

try {
    $cart = $_SESSION['cart'] ?? [];
    $subtotal = 0;
    $cartCount = 0;
    
    // Calcular totales
    foreach ($cart as $item) {
        if (isset($item['quantity']) && isset($item['price'])) {
            $subtotal += $item['price'] * $item['quantity'];
            $cartCount += $item['quantity'];
        }
    }
    
    $tax = $subtotal * 0.19; // 19% IVA
    $shipping = $subtotal > 0 ? 15000 : 0; // Envío gratis si no hay productos
    $total = $subtotal + $tax + $shipping;
    
    // Preparar datos de productos
    $cartItems = [];
    foreach ($cart as $item) {
        $productImage = !empty($item['image']) ? 'uploads/products/' . $item['image'] : 'assets/images/placeholder-product.svg';
        
        $cartItems[] = [
            'id' => $item['id'],
            'name' => htmlspecialchars($item['name'] ?? 'Producto'),
            'price' => $item['price'] ?? 0,
            'quantity' => $item['quantity'] ?? 1,
            'image' => $productImage,
            'variant' => $item['variant'] ?? null,
            'subtotal' => ($item['price'] ?? 0) * ($item['quantity'] ?? 1)
        ];
    }
    
    echo json_encode([
        'success' => true,
        'isEmpty' => count($cart) === 0,
        'items' => $cartItems,
        'totals' => [
            'subtotal' => $subtotal,
            'tax' => $tax,
            'shipping' => $shipping,
            'total' => $total
        ],
        'counts' => [
            'items' => count($cart),
            'quantity' => $cartCount
        ]
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'isEmpty' => true,
        'items' => [],
        'totals' => [
            'subtotal' => 0,
            'tax' => 0,
            'shipping' => 0,
            'total' => 0
        ],
        'counts' => [
            'items' => 0,
            'quantity' => 0
        ]
    ]);
}
?>
