<?php
session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../../config/database.php';

// Check if customer is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Debes iniciar sesión']);
    exit;
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

try {
    // Get JSON input or POST data
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        $input = $_POST;
    }
    
    if (!isset($input['review_id']) || !isset($input['action'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Datos requeridos faltantes']);
        exit;
    }
    
    $reviewId = intval($input['review_id']);
    $action = $input['action']; // 'like' or 'dislike'
    $customerId = $_SESSION['user_id'];
    
    if (!in_array($action, ['like', 'dislike'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Acción no válida']);
        exit;
    }
    
    $database = new Database();
    $db = $database->getConnection();
    
    // Check if review exists
    $reviewQuery = "SELECT id FROM reviews WHERE id = ?";
    $reviewStmt = $db->prepare($reviewQuery);
    $reviewStmt->execute([$reviewId]);
    
    if (!$reviewStmt->fetch()) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Reseña no encontrada']);
        exit;
    }
    
    // Check if user already voted on this review (using user_id from existing DB structure)
    $existingVoteQuery = "SELECT id FROM review_likes WHERE review_id = ? AND user_id = ?";
    $existingStmt = $db->prepare($existingVoteQuery);
    $existingStmt->execute([$reviewId, $customerId]);
    $existingVote = $existingStmt->fetch();
    
    if ($existingVote) {
        // Remove the vote if user already voted (toggle functionality)
        $deleteQuery = "DELETE FROM review_likes WHERE review_id = ? AND user_id = ?";
        $deleteStmt = $db->prepare($deleteQuery);
        $deleteStmt->execute([$reviewId, $customerId]);
        $voteAction = 'removed';
    } else {
        // Create new vote (only likes in existing structure, treating all as likes)
        $insertQuery = "INSERT INTO review_likes (review_id, user_id, created_at) VALUES (?, ?, NOW())";
        $insertStmt = $db->prepare($insertQuery);
        $insertStmt->execute([$reviewId, $customerId]);
        $voteAction = 'added';
    }
    
    // Get updated like count
    $likesQuery = "SELECT COUNT(*) as count FROM review_likes WHERE review_id = ?";
    $likesStmt = $db->prepare($likesQuery);
    $likesStmt->execute([$reviewId]);
    $likesCount = $likesStmt->fetch()['count'];
    
    // Update helpful_count in reviews table (using existing structure)
    $updateReviewQuery = "UPDATE reviews SET helpful_count = ? WHERE id = ?";
    $updateReviewStmt = $db->prepare($updateReviewQuery);
    $updateReviewStmt->execute([$likesCount, $reviewId]);
    
    echo json_encode([
        'success' => true,
        'message' => $voteAction === 'added' ? 'Like agregado' : 'Like removido',
        'likes' => $likesCount,
        'action' => $voteAction
    ]);
    
} catch (Exception $e) {
    error_log("Review Like Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error interno del servidor'
    ]);
}
?>
