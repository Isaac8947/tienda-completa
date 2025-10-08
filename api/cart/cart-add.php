<?php
// Iniciar output buffering para capturar cualquier output no deseado
ob_start();

// Suprimir errores para respuesta JSON limpia
error_reporting(0);
ini_set('display_errors', 0);

// Configuración de sesión segura (debe ir antes de session_start)
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 0); // Cambiar a 1 en HTTPS
ini_set('session.cookie_samesite', 'Strict');

session_start();

require_once 'includes/security-headers.php';
require_once 'includes/CSRFProtection.php';
require_once 'includes/InputSanitizer.php';
require_once 'includes/RateLimiter.php';

// Limpiar cualquier output previo
ob_clean();

// Set JSON response header ANTES de cualquier output
header('Content-Type: application/json; charset=utf-8');

// Function to send clean JSON response
function sendJsonResponse($data, $httpCode = 200) {
    ob_clean();
    http_response_code($httpCode);
    echo json_encode($data);
    ob_end_flush();
    exit;
}

// Initialize security classes
$csrf = new CSRFProtection();
$sanitizer = new InputSanitizer();

// Check rate limiting (usar método estático correcto)
if (!RateLimiter::checkLimit('cart_add', 30, 300)) {
    sendJsonResponse(['success' => false, 'message' => 'Demasiadas solicitudes'], 429);
}

// Validate request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJsonResponse(['success' => false, 'error' => 'Method not allowed'], 405);
}

// Validate CSRF token - usar token global reutilizable
$csrfValid = false;
$debugInfo = [
    'csrf_token_received' => isset($_POST['csrf_token']),
    'csrf_token_value' => isset($_POST['csrf_token']) ? substr($_POST['csrf_token'], 0, 10) . '...' : 'none',
    'csrf_token_length' => isset($_POST['csrf_token']) ? strlen($_POST['csrf_token']) : 0,
    'post_data_keys' => array_keys($_POST),
    'session_id' => session_id()
];

if (isset($_POST['csrf_token'])) {
    try {
        // Usar el método de token global reutilizable
        $csrfValid = CSRFProtection::validateGlobalToken($_POST['csrf_token']);
        
        // Si no es válido con token global, intentar con otros contextos
        if (!$csrfValid) {
            $csrfValid = $csrf->validateToken($_POST['csrf_token'], 'cart', false) ||
                        $csrf->validateToken($_POST['csrf_token'], 'default', false);
        }
        
        $debugInfo['validation_result'] = $csrfValid;
    } catch (Exception $e) {
        error_log("CSRF validation error: " . $e->getMessage());
        $csrfValid = false;
        $debugInfo['validation_error'] = $e->getMessage();
    }
}

// Si no es válido, generar un nuevo token global y darlo al cliente
if (!$csrfValid) {
    $newToken = CSRFProtection::generateGlobalToken();
    sendJsonResponse([
        'success' => false, 
        'message' => 'Token de seguridad inválido o expirado', 
        'new_csrf_token' => $newToken,
        'debug' => $debugInfo
    ], 403);
}

require_once 'config/database.php';
require_once 'models/Product.php';

// Verificar si se recibieron los datos necesarios
if (!isset($_POST['product_id']) && !isset($_POST['id'])) {
    sendJsonResponse([
        'success' => false,
        'message' => 'Faltan parámetros requeridos'
    ]);
}

// Sanitize inputs
$productId = $sanitizer->sanitizeInt($_POST['product_id'] ?? $_POST['id'] ?? 0);
$quantity = $sanitizer->sanitizeInt($_POST['quantity'] ?? 1);

// Validar cantidad
if ($quantity <= 0) {
    sendJsonResponse([
        'success' => false,
        'message' => 'La cantidad debe ser mayor que cero'
    ]);
}

// Obtener información del producto
$productModel = new Product();
$product = $productModel->getProductById($productId);

if (!$product) {
    sendJsonResponse([
        'success' => false,
        'message' => 'Producto no encontrado'
    ]);
}

// Verificar stock
$stock = $product['stock'] ?? $product['inventory_quantity'] ?? 0;
if ($stock < $quantity) {
    sendJsonResponse([
        'success' => false,
        'message' => 'No hay suficiente stock disponible'
    ]);
}

// Inicializar el carrito si no existe
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Verificar si el producto ya está en el carrito
$found = false;
foreach ($_SESSION['cart'] as &$item) {
    if (($item['id'] ?? $item['product_id'] ?? 0) == $productId) {
        // Actualizar cantidad y mantener información de descuento actualizada
        $item['quantity'] += $quantity;
        
        // Actualizar información de descuento por si cambió
        $comparePrice = !empty($product['compare_price']) ? $product['compare_price'] : null;
        $discountPercentage = 0;
        
        if ($comparePrice && $comparePrice > $product['price']) {
            $discountPercentage = round((($comparePrice - $product['price']) / $comparePrice) * 100);
        }
        
        $item['compare_price'] = $comparePrice;
        $item['discount_percentage'] = $discountPercentage;
        $item['is_on_sale'] = !empty($product['is_on_sale']);
        
        $found = true;
        break;
    }
}

// Si no se encontró, agregar al carrito
if (!$found) {
    // Calcular descuento si existe precio de comparación
    $comparePrice = !empty($product['compare_price']) ? $product['compare_price'] : null;
    $discountPercentage = 0;
    
    if ($comparePrice && $comparePrice > $product['price']) {
        $discountPercentage = round((($comparePrice - $product['price']) / $comparePrice) * 100);
    }
    
    $_SESSION['cart'][] = [
        'id' => $productId,
        'product_id' => $productId,
        'name' => $product['name'],
        'price' => $product['price'],
        'compare_price' => $comparePrice,
        'discount_percentage' => $discountPercentage,
        'is_on_sale' => !empty($product['is_on_sale']),
        'quantity' => $quantity,
        'image' => $product['main_image'] ?? $product['image'] ?? null,
        'variant' => isset($_POST['variant']) ? $_POST['variant'] : null
    ];
}

// Calcular el total de productos en el carrito
$cartCount = 0;
foreach ($_SESSION['cart'] as $item) {
    $cartCount += $item['quantity'];
}

// Limpiar output buffer y enviar respuesta JSON
sendJsonResponse([
    'success' => true,
    'message' => 'Producto agregado al carrito',
    'cartCount' => $cartCount
]);
