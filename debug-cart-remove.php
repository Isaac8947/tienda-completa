<?php
// Debug para cart-remove.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Evitar cualquier salida antes del JSON
ob_start();

// Configuración de sesión segura
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 0);
ini_set('session.cookie_samesite', 'Strict');

session_start();

// Limpiar cualquier salida previa
ob_clean();

header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');
header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');

// Debug: Verificar datos recibidos
$debug_info = [
    'POST_data' => $_POST,
    'session_cart' => $_SESSION['cart'] ?? 'No cart in session'
];

// Verificar si se recibieron los datos necesarios
if (!isset($_POST['id']) && !isset($_POST['product_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Falta el ID del producto',
        'debug' => $debug_info
    ]);
    exit;
}

$productId = (int)($_POST['id'] ?? $_POST['product_id']);

// Verificar si el carrito existe
if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
    echo json_encode([
        'success' => false,
        'message' => 'El carrito está vacío',
        'debug' => $debug_info
    ]);
    exit;
}

// Buscar el producto en el carrito
$found = false;
$debug_search = [];

foreach ($_SESSION['cart'] as $key => $item) {
    // Manejar tanto 'id' como 'product_id'
    $itemId = $item['id'] ?? $item['product_id'] ?? 0;
    $debug_search[] = [
        'key' => $key,
        'item_id' => $itemId,
        'searching_for' => $productId,
        'match' => ($itemId == $productId)
    ];
    
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
        'message' => 'Producto no encontrado en el carrito',
        'debug' => $debug_info,
        'search_debug' => $debug_search
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
    'cartCount' => $cartCount,
    'debug' => $debug_info,
    'search_debug' => $debug_search
];

echo json_encode($response);
exit;
?>