<?php
session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../../config/database.php';

// Debug mode - can be disabled in production
$debug = true;

if ($debug) {
    error_log("=== REVIEWS API DEBUG ===");
    error_log("Session: " . json_encode($_SESSION));
    error_log("POST: " . json_encode($_POST));
    error_log("Method: " . $_SERVER['REQUEST_METHOD']);
}

// Check if customer is logged in
if (!isset($_SESSION['user_id']) && !isset($_SESSION['customer_id'])) {
    http_response_code(401);
    echo json_encode([
        'success' => false, 
        'message' => 'Debes iniciar sesión para escribir una reseña',
        'debug' => $debug ? [
            'session_keys' => array_keys($_SESSION ?? []),
            'has_user_id' => isset($_SESSION['user_id']),
            'has_customer_id' => isset($_SESSION['customer_id'])
        ] : null
    ]);
    exit;
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

try {
    // Validate required fields
    $requiredFields = ['product_id', 'rating', 'title', 'comment'];
    foreach ($requiredFields as $field) {
        if (!isset($_POST[$field]) || empty(trim($_POST[$field]))) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => "El campo {$field} es requerido"]);
            exit;
        }
    }
    
    $productId = intval($_POST['product_id']);
    $rating = intval($_POST['rating']);
    $title = trim($_POST['title']);
    $comment = trim($_POST['comment']);
    
    // Get customer ID from session (try both keys for compatibility)
    $customerId = $_SESSION['user_id'] ?? $_SESSION['customer_id'] ?? null;
    
    if (!$customerId) {
        http_response_code(401);
        echo json_encode([
            'success' => false, 
            'message' => 'Error de sesión. Por favor inicia sesión nuevamente.',
            'debug' => $debug ? ['session' => $_SESSION] : null
        ]);
        exit;
    }
    
    // Validate rating
    if ($rating < 1 || $rating > 5) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'La calificación debe ser entre 1 y 5 estrellas']);
        exit;
    }
    
    // Validate lengths (according to DB structure)
    if (strlen($title) > 255) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'El título no puede superar los 255 caracteres']);
        exit;
    }
    
    if (strlen($comment) > 2000) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'El comentario no puede superar los 2000 caracteres']);
        exit;
    }
    
    $database = new Database();
    $db = $database->getConnection();
    
    // Check if product exists
    $productQuery = "SELECT id, name FROM products WHERE id = ? AND status = 'active'";
    $productStmt = $db->prepare($productQuery);
    $productStmt->execute([$productId]);
    $product = $productStmt->fetch();
    
    if (!$product) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Producto no encontrado']);
        exit;
    }
    
    // Check if customer already reviewed this product
    $existingReviewQuery = "SELECT id FROM reviews WHERE product_id = ? AND customer_id = ?";
    $existingStmt = $db->prepare($existingReviewQuery);
    $existingStmt->execute([$productId, $customerId]);
    
    if ($existingStmt->fetch()) {
        http_response_code(409);
        echo json_encode(['success' => false, 'message' => 'Ya has reseñado este producto']);
        exit;
    }
    
    // Use session user name as reviewer name
    $reviewerName = $_SESSION['user_name'] ?? 'Usuario';
    
    // Insert review directly into database (matching existing structure)
    $insertQuery = "
        INSERT INTO reviews (
            product_id, customer_id, rating, title, comment, reviewer_name,
            is_verified, is_approved, helpful_count, created_at, updated_at
        ) VALUES (?, ?, ?, ?, ?, ?, 0, 1, 0, NOW(), NOW())
    ";
    
    $insertStmt = $db->prepare($insertQuery);
    $result = $insertStmt->execute([
        $productId, $customerId, $rating, $title, $comment, $reviewerName
    ]);
    
    if ($result) {
        $reviewId = $db->lastInsertId();
        
        // Update product average rating and review count
        $updateQuery = "
            UPDATE products 
            SET average_rating = (
                SELECT COALESCE(AVG(rating), 0) 
                FROM reviews 
                WHERE product_id = ? AND is_approved = 1
            ),
            total_reviews = (
                SELECT COUNT(*) 
                FROM reviews 
                WHERE product_id = ? AND is_approved = 1
            )
            WHERE id = ?
        ";
        $updateStmt = $db->prepare($updateQuery);
        $updateStmt->execute([$productId, $productId, $productId]);
        
        echo json_encode([
            'success' => true,
            'message' => '¡Reseña publicada exitosamente!',
            'review_id' => $reviewId
        ]);
    } else {
        throw new Exception('Error al insertar la reseña en la base de datos');
    }
    
} catch (Exception $e) {
    error_log("Review Creation Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error interno del servidor. Intenta nuevamente.'
    ]);
}
?>
