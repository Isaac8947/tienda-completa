<?php
// Version simple de cart-add.php para debug
error_reporting(0);
ini_set('display_errors', 0);

// Set JSON response header ANTES de cualquier output
header('Content-Type: application/json; charset=utf-8');

// Configuración de sesión segura
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 0);
ini_set('session.cookie_samesite', 'Strict');

session_start();
require_once 'config/database.php';
require_once 'models/Product.php';

// Validate request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

// Verificar si se recibieron los datos necesarios
if (!isset($_POST['product_id']) && !isset($_POST['id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Faltan parámetros requeridos'
    ]);
    exit;
}

// Sanitizar inputs básico
$productId = intval($_POST['product_id'] ?? $_POST['id'] ?? 0);
$quantity = intval($_POST['quantity'] ?? 1);

// Validar datos
if ($productId <= 0) {
    echo json_encode([
        'success' => false,
        'message' => 'ID de producto inválido'
    ]);
    exit;
}

if ($quantity <= 0 || $quantity > 100) {
    echo json_encode([
        'success' => false,
        'message' => 'Cantidad inválida'
    ]);
    exit;
}

try {
    // Obtener información del producto
    $productModel = new Product();
    $product = $productModel->getProductById($productId);

    if (!$product) {
        echo json_encode([
            'success' => false,
            'message' => 'Producto no encontrado'
        ]);
        exit;
    }

    // Verificar stock básico
    $stock = $product['stock'] ?? $product['inventory_quantity'] ?? 999;
    if ($stock < $quantity) {
        echo json_encode([
            'success' => false,
            'message' => 'No hay suficiente stock disponible'
        ]);
        exit;
    }

    // Inicializar el carrito si no existe
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }

    // Verificar si el producto ya está en el carrito
    $found = false;
    foreach ($_SESSION['cart'] as &$item) {
        if ($item['id'] == $productId) {
            // Actualizar cantidad
            $item['quantity'] += $quantity;
            $found = true;
            break;
        }
    }

    // Si no se encontró, agregar al carrito
    if (!$found) {
        $_SESSION['cart'][] = [
            'id' => $productId,
            'name' => $product['name'],
            'price' => $product['price'],
            'quantity' => $quantity,
            'image' => $product['main_image'] ?? $product['image'] ?? null
        ];
    }

    // Calcular el total de productos en el carrito
    $cartCount = 0;
    foreach ($_SESSION['cart'] as $item) {
        $cartCount += $item['quantity'];
    }

    echo json_encode([
        'success' => true,
        'message' => 'Producto agregado al carrito',
        'cartCount' => $cartCount,
        'debug' => [
            'product_id' => $productId,
            'quantity' => $quantity,
            'cart_items' => count($_SESSION['cart'])
        ]
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error interno del servidor',
        'debug' => $e->getMessage()
    ]);
}

exit;
?>
