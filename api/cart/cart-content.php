<?php
session_start();
header('Content-Type: application/json');

try {
    $cartItems = [];
    $subtotal = 0;
    $itemCount = 0;
    
    if (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {
        foreach ($_SESSION['cart'] as $item) {
            if (is_array($item)) {
                $cartItems[] = $item;
                $subtotal += ($item['price'] * $item['quantity']);
                $itemCount += $item['quantity'];
            }
        }
    }
    
    $tax = $subtotal * 0.19; // 19% IVA
    $total = $subtotal + $tax;
    
    echo json_encode([
        'success' => true,
        'items' => $cartItems,
        'subtotal' => $subtotal,
        'tax' => $tax,
        'total' => $total,
        'itemCount' => $itemCount,
        'isEmpty' => count($cartItems) === 0
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'items' => [],
        'subtotal' => 0,
        'tax' => 0,
        'total' => 0,
        'itemCount' => 0,
        'isEmpty' => true
    ]);
}
?>
