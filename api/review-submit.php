<?php
header('Content-Type: application/json');
session_start();

// Verificar autenticación
$user_id = null;
$is_logged_in = false;

if (isset($_SESSION['customer_id']) && !empty($_SESSION['customer_id'])) {
    $user_id = $_SESSION['customer_id'];
    $is_logged_in = true;
} elseif (isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $is_logged_in = true;
    // Configurar customer_id para compatibilidad
    $_SESSION['customer_id'] = $_SESSION['user_id'];
}

if (!$is_logged_in) {
    echo json_encode(['success' => false, 'error' => 'Usuario no autenticado']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
    exit;
}

$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    echo json_encode(['success' => false, 'error' => 'JSON inválido']);
    exit;
}

// Validar datos requeridos
$product_id = (int)($data['product_id'] ?? 0);
$rating = (int)($data['rating'] ?? 0);
$title = trim($data['title'] ?? '');
$comment = trim($data['comment'] ?? '');
$reviewer_name = trim($data['reviewer_name'] ?? '');

if ($product_id <= 0) {
    echo json_encode(['success' => false, 'error' => 'ID de producto inválido']);
    exit;
}

if ($rating < 1 || $rating > 5) {
    echo json_encode(['success' => false, 'error' => 'Calificación debe ser entre 1 y 5']);
    exit;
}

if (empty($title) || empty($comment)) {
    echo json_encode(['success' => false, 'error' => 'Título y comentario son requeridos']);
    exit;
}

try {
    require_once __DIR__ . '/config/database.php';
    
    $db = new Database();
    $conn = $db->getConnection();
    
    // Verificar si el usuario ya ha hecho una review para este producto
    $checkSql = "SELECT id FROM reviews WHERE product_id = ? AND customer_id = ?";
    $checkStmt = $conn->prepare($checkSql);
    $checkStmt->execute([$product_id, $user_id]);
    
    if ($checkStmt->fetch()) {
        echo json_encode(['success' => false, 'error' => 'Ya has dejado una reseña para este producto']);
        exit;
    }
    
    // Insertar nueva review
    $insertSql = "INSERT INTO reviews (product_id, customer_id, rating, title, comment, reviewer_name, is_approved, created_at) 
                  VALUES (?, ?, ?, ?, ?, ?, 1, NOW())";
    $insertStmt = $conn->prepare($insertSql);
    $success = $insertStmt->execute([$product_id, $user_id, $rating, $title, $comment, $reviewer_name]);
    
    if ($success) {
        $review_id = $conn->lastInsertId();
        
        // Actualizar las calificaciones del producto
        try {
            require_once 'models/Review.php';
            $reviewModel = new Review();
            $reviewModel->ensureProductRatingColumns();
            $reviewModel->updateProductRating($product_id);
        } catch (Exception $e) {
            error_log("Error updating product rating: " . $e->getMessage());
        }
        
        // Obtener información del usuario para la respuesta
        $userSql = "SELECT first_name, last_name FROM customers WHERE id = ?";
        $userStmt = $conn->prepare($userSql);
        $userStmt->execute([$user_id]);
        $user = $userStmt->fetch();
        
        $display_name = $user ? ($user['first_name'] . ' ' . $user['last_name']) : $reviewer_name;
        
        echo json_encode([
            'success' => true,
            'message' => 'Reseña guardada exitosamente',
            'review' => [
                'id' => $review_id,
                'product_id' => $product_id,
                'rating' => $rating,
                'title' => $title,
                'comment' => $comment,
                'reviewer_name' => $display_name,
                'like_count' => 0,
                'dislike_count' => 0,
                'user_has_liked' => false,
                'user_has_disliked' => false,
                'created_at' => date('Y-m-d H:i:s'),
                'replies' => []
            ]
        ]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Error al guardar la reseña']);
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Error del servidor: ' . $e->getMessage()]);
}
?>
