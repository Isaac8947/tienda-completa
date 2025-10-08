<?php
// Security headers
require_once 'includes/security-headers.php';

// Load security classes
require_once 'includes/CSRFProtection.php';
require_once 'includes/InputSanitizer.php';
require_once 'includes/RateLimiter.php';

// Initialize security components
$csrf = new CSRFProtection();
$sanitizer = new InputSanitizer();

// Log errors instead of displaying them
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

// Start session first
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Rate limiting check
if (!RateLimiter::checkLimit('wishlist_toggle', 30, 3600, $_SERVER['REMOTE_ADDR'])) {
    http_response_code(429);
    echo json_encode([
        'success' => false,
        'message' => 'Demasiados intentos. Intenta más tarde.'
    ]);
    exit;
}

require_once 'config/database.php';

header('Content-Type: application/json');

// Validate CSRF token
if (!$csrf->validateToken($_POST['csrf_token'] ?? '', 'wishlist')) {
    echo json_encode([
        'success' => false,
        'message' => 'Token de seguridad inválido',
        'debug' => [
            'token_received' => isset($_POST['csrf_token']) ? 'yes' : 'no',
            'token_value' => $_POST['csrf_token'] ?? 'none'
        ]
    ]);
    exit;
}

if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false,
        'requires_login' => true,
        'message' => 'Debes iniciar sesión para agregar productos a favoritos'
    ]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['product_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Datos inválidos'
    ]);
    exit;
}

try {
    $database = new Database();
    $db = $database->getConnection();

    $userId = $_SESSION['user_id'];
    $productId = $sanitizer->sanitizeInt($_POST['product_id']);
    
    // Validate product ID
    if (!$productId || $productId <= 0) {
        throw new Exception('ID de producto inválido');
    }

    // Check if product exists
    $productCheckQuery = "SELECT id FROM products WHERE id = ? AND status = 'active'";
    $productCheckStmt = $db->prepare($productCheckQuery);
    $productCheckStmt->execute([$productId]);
    
    if (!$productCheckStmt->fetch()) {
        throw new Exception('Producto no encontrado');
    }

    // Verificar si el producto ya está en favoritos
    $checkQuery = "SELECT id FROM wishlists WHERE customer_id = ? AND product_id = ?";
    $checkStmt = $db->prepare($checkQuery);
    $checkStmt->execute([$userId, $productId]);
    $existingWishlist = $checkStmt->fetch();

    if ($existingWishlist) {
        // Remover de favoritos
        $deleteQuery = "DELETE FROM wishlists WHERE customer_id = ? AND product_id = ?";
        $deleteStmt = $db->prepare($deleteQuery);
        
        if ($deleteStmt->execute([$userId, $productId])) {
            echo json_encode([
                'success' => true,
                'action' => 'removed',
                'added' => false,
                'message' => 'Removido de favoritos'
            ]);
        } else {
            throw new Exception('Error al remover de favoritos');
        }
    } else {
        // Agregar a favoritos
        $insertQuery = "INSERT INTO wishlists (customer_id, product_id, created_at) VALUES (?, ?, NOW())";
        $insertStmt = $db->prepare($insertQuery);
        
        if ($insertStmt->execute([$userId, $productId])) {
            echo json_encode([
                'success' => true,
                'action' => 'added',
                'added' => true,
                'message' => 'Agregado a favoritos'
            ]);
        } else {
            throw new Exception('Error al agregar a favoritos');
        }
    }

} catch (Exception $e) {
    error_log("Error in wishlist-toggle.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage(),
        'debug' => [
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ]
    ]);
}
?>
