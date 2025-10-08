<?php
// Configuración de sesión segura (debe ir antes de session_start)
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 0); // Cambiar a 1 en HTTPS
ini_set('session.cookie_samesite', 'Strict');

session_start();

header('Content-Type: application/json');

// Verificar si se recibieron los datos necesarios
if (!isset($_POST['id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Faltan parámetros requeridos'
    ]);
    exit;
}

$productId = (int)$_POST['id'];
$change = isset($_POST['change']) ? (int)$_POST['change'] : 0;
$newQuantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : null;

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
        if ($newQuantity !== null) {
            // Actualizar con cantidad específica
            if ($newQuantity <= 0) {
                unset($_SESSION['cart'][$key]);
                $_SESSION['cart'] = array_values($_SESSION['cart']);
            } else {
                $item['quantity'] = $newQuantity;
            }
        } else {
            // Actualizar con cambio relativo
            $item['quantity'] += $change;
            if ($item['quantity'] <= 0) {
                unset($_SESSION['cart'][$key]);
                $_SESSION['cart'] = array_values($_SESSION['cart']);
            }
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
if (isset($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $item) {
        $cartCount += $item['quantity'];
    }
}

echo json_encode([
    'success' => true,
    'message' => 'Carrito actualizado',
    'cartCount' => $cartCount
]);
?>
