<?php
// Evitar cualquier salida antes del JSON
ob_start();

// Desactivar la visualización de errores para evitar HTML en la respuesta JSON
error_reporting(0);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Configuración de sesión segura (debe ir antes de session_start)
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 0); // Cambiar a 1 en HTTPS
ini_set('session.cookie_samesite', 'Strict');

session_start();

// Limpiar cualquier salida previa
ob_clean();

header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');
header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');

// Verificar si se recibieron los datos necesarios
if (!isset($_POST['id']) && !isset($_POST['product_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Falta el ID del producto'
    ]);
    exit;
}

$productId = (int)($_POST['id'] ?? $_POST['product_id']);

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
foreach ($_SESSION['cart'] as $key => $item) {
    // Manejar tanto 'id' como 'product_id'
    $itemId = $item['id'] ?? $item['product_id'] ?? 0;
    if ($itemId == $productId) {
        // Eliminar el producto
        unset($_SESSION['cart'][$key]);
        // Reindexar el array
        $_SESSION['cart'] = array_values($_SESSION['cart']);
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
    $cartCount += $item['quantity'] ?? 1;
}

// Respuesta exitosa
$response = [
    'success' => true,
    'message' => 'Producto eliminado del carrito',
    'cartCount' => $cartCount
];

echo json_encode($response);
exit;
