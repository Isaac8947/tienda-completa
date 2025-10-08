<?php
session_start();

header('Content-Type: application/json');

// Verificar si se recibieron los datos necesarios
if (!isset($_POST['product_id']) || !isset($_POST['quantity'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Faltan parámetros requeridos'
    ]);
    exit;
}

$productId = (int)$_POST['product_id'];
$newQuantity = (int)$_POST['quantity'];

// Verificar si el carrito existe
if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
    echo json_encode([
        'success' => false,
        'message' => 'El carrito está vacío'
    ]);
    exit;
}

// Buscar el producto en el carrito
$found = false;
foreach ($_SESSION['cart'] as $key => &$item) {
    if ($item['id'] == $productId) {
        // Si la nueva cantidad es 0 o menos, eliminar el producto
        if ($newQuantity <= 0) {
            unset($_SESSION['cart'][$key]);
            // Reindexar el array
            $_SESSION['cart'] = array_values($_SESSION['cart']);
        } else {
            $item['quantity'] = $newQuantity;
        }
        $found = true;
        break;
    }
}

if (!$found) {
    echo json_encode([
        'success' => false,
        'message' => 'Producto no encontrado en el carrito'
    ]);
    exit;
}

// Calcular el total de productos en el carrito
$cartCount = 0;
foreach ($_SESSION['cart'] as $item) {
    $cartCount += $item['quantity'];
}

echo json_encode([
    'success' => true,
    'message' => 'Carrito actualizado',
    'cartCount' => $cartCount
]);
?>
