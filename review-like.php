<?php
require_once 'includes/security-headers.php';
require_once 'includes/CSRFProtection.php';
require_once 'includes/InputSanitizer.php';
require_once 'includes/RateLimiter.php';

header('Content-Type: application/json');

// Start session for user validation
session_start();

// Block suspicious IPs
if (RateLimiter::isBlocked()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Access denied']);
    exit;
}

// Check rate limiting for reviews
if (!RateLimiter::checkLimit('review', 10, 600)) {
    http_response_code(429);
    echo json_encode(['success' => false, 'error' => 'Too many requests. Please wait.']);
    exit;
}

// Allow anonymous likes but require authentication for persistent likes
$isAuthenticated = isset($_SESSION['customer_id']) && !empty($_SESSION['customer_id']);
$user_id = $isAuthenticated ? $_SESSION['customer_id'] : null;

// Validate request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

require_once 'config/database.php';

$data = json_decode(file_get_contents('php://input'), true);

// Validate CSRF token from JSON data
if (!isset($data['csrf_token']) || !CSRFProtection::validateToken($data['csrf_token'], 'review')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Invalid request token']);
    exit;
}

// Simplificar la obtención de datos para debug
$review_id = (int)($data['review_id'] ?? 0);
$action = strtolower(trim($data['action'] ?? 'like'));

// Debug logging completo
error_log("=== REVIEW LIKE DEBUG ===");
error_log("Raw POST data: " . file_get_contents('php://input'));
error_log("Parsed data: " . json_encode($data));
error_log("Review ID: " . $review_id);
error_log("Action: '" . $action . "'");
error_log("Action length: " . strlen($action));
error_log("Is authenticated: " . ($isAuthenticated ? 'true' : 'false'));
error_log("User ID: " . ($user_id ?? 'null'));

if (!$review_id || $review_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID de reseña requerido']);
    exit;
}

if (!in_array($action, ['like', 'dislike'])) {
    echo json_encode([
        'success' => false, 
        'message' => 'Acción inválida: "' . $action . '"',
        'debug' => [
            'received_action' => $action,
            'action_length' => strlen($action),
            'action_bytes' => bin2hex($action),
            'valid_actions' => ['like', 'dislike'],
            'raw_data' => $data
        ]
    ]);
    exit;
}

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    if ($isAuthenticated && $user_id) {
        // Handle authenticated user likes/dislikes
        $table = $action === 'like' ? 'review_likes' : 'review_dislikes';
        $oppositeTable = $action === 'like' ? 'review_dislikes' : 'review_likes';
        
        // Check if user already has this reaction
        $checkSql = "SELECT id FROM {$table} WHERE review_id = ? AND user_id = ?";
        $checkStmt = $conn->prepare($checkSql);
        $checkStmt->execute([$review_id, $user_id]);
        $existingReaction = $checkStmt->fetch();
        
        // Remove opposite reaction if exists
        $deleteOppositeSql = "DELETE FROM {$oppositeTable} WHERE review_id = ? AND user_id = ?";
        $deleteOppositeStmt = $conn->prepare($deleteOppositeSql);
        $deleteOppositeStmt->execute([$review_id, $user_id]);
        
        if ($existingReaction) {
            // Remove current reaction
            $deleteSql = "DELETE FROM {$table} WHERE review_id = ? AND user_id = ?";
            $deleteStmt = $conn->prepare($deleteSql);
            $deleteStmt->execute([$review_id, $user_id]);
            $reactionAction = 'removed';
        } else {
            // Add new reaction
            $insertSql = "INSERT INTO {$table} (review_id, user_id, created_at) VALUES (?, ?, NOW())";
            $insertStmt = $conn->prepare($insertSql);
            $insertStmt->execute([$review_id, $user_id]);
            $reactionAction = 'added';
        }
        
        // Get updated counts
        $likeCountSql = "SELECT COUNT(*) as like_count FROM review_likes WHERE review_id = ?";
        $likeCountStmt = $conn->prepare($likeCountSql);
        $likeCountStmt->execute([$review_id]);
        $likeCount = $likeCountStmt->fetch()['like_count'];
        
        $dislikeCountSql = "SELECT COUNT(*) as dislike_count FROM review_dislikes WHERE review_id = ?";
        $dislikeCountStmt = $conn->prepare($dislikeCountSql);
        $dislikeCountStmt->execute([$review_id]);
        $dislikeCount = $dislikeCountStmt->fetch()['dislike_count'];
        
        echo json_encode([
            'success' => true,
            'action' => $reactionAction,
            'like_count' => $likeCount,
            'dislike_count' => $dislikeCount,
            'authenticated' => true,
            'message' => $reactionAction === 'added' ? 
                ucfirst($action) . ' agregado' : 
                ucfirst($action) . ' removido'
        ]);
    } else {
        // Handle anonymous user reactions
        // Just return current counts for display
        $likeCountSql = "SELECT COUNT(*) as like_count FROM review_likes WHERE review_id = ?";
        $likeCountStmt = $conn->prepare($likeCountSql);
        $likeCountStmt->execute([$review_id]);
        $likeCount = $likeCountStmt->fetch()['like_count'];
        
        $dislikeCountSql = "SELECT COUNT(*) as dislike_count FROM review_dislikes WHERE review_id = ?";
        $dislikeCountStmt = $conn->prepare($dislikeCountSql);
        $dislikeCountStmt->execute([$review_id]);
        $dislikeCount = $dislikeCountStmt->fetch()['dislike_count'];
        
        echo json_encode([
            'success' => true,
            'action' => 'anonymous_toggle',
            'like_count' => $likeCount,
            'dislike_count' => $dislikeCount,
            'authenticated' => false,
            'message' => ucfirst($action) . ' registrado (inicia sesión para guardarlo)'
        ]);
    }
    
} catch (Exception $e) {
    error_log("Error in review-like.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error al procesar el like'
    ]);
}
?>
