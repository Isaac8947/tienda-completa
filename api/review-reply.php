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
if (!RateLimiter::checkLimit('review', 5, 600)) {
    http_response_code(429);
    echo json_encode(['success' => false, 'error' => 'Too many requests. Please wait.']);
    exit;
}

// Validate user authentication - Support hybrid session system
$userId = null;
$authorName = 'Usuario Anónimo';
$authorType = 'guest';

// Check multiple session types for compatibility
if (isset($_SESSION['user_id']) && $_SESSION['user_id']) {
    $userId = $_SESSION['user_id'];
    $authorType = 'user';
    $authorName = $_SESSION['user_name'] ?? $_SESSION['username'] ?? 'Usuario';
} elseif (isset($_SESSION['customer_id']) && $_SESSION['customer_id']) {
    $userId = $_SESSION['customer_id'];
    $authorType = 'customer';
    $authorName = $_SESSION['customer_name'] ?? $_SESSION['username'] ?? 'Cliente';
} elseif (isset($_SESSION['admin_id']) && $_SESSION['admin_id']) {
    $userId = $_SESSION['admin_id'];
    $authorType = 'admin';
    $authorName = $_SESSION['admin_name'] ?? $_SESSION['username'] ?? 'Administrador';
}

if (!$userId) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Authentication required']);
    exit;
}

// Validate request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

require_once 'config/database.php';

$data = json_decode(file_get_contents('php://input'), true);

// Validate CSRF token
if (!isset($data['csrf_token']) || !CSRFProtection::validateToken($data['csrf_token'], 'review')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Invalid request token']);
    exit;
}

$review_id = InputSanitizer::sanitizeInt($data['review_id'] ?? 0, 1, 999999);
$reply_text = InputSanitizer::sanitizeString($data['reply_text'] ?? '', 500);
$user_id = $_SESSION['customer_id'];

// Check for malicious content
if (InputSanitizer::detectXSS($reply_text) || InputSanitizer::detectSQLInjection($reply_text)) {
    InputSanitizer::logSuspiciousActivity($reply_text, 'REVIEW_ATTACK');
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid content detected']);
    exit;
}

if (!$review_id || empty($reply_text)) {
    echo json_encode(['success' => false, 'message' => 'ID de reseña y texto de respuesta requeridos']);
    exit;
}

if (strlen($reply_text) < 10) {
    echo json_encode(['success' => false, 'message' => 'La respuesta debe tener al menos 10 caracteres']);
    exit;
}

if (strlen($reply_text) > 500) {
    echo json_encode(['success' => false, 'message' => 'La respuesta no puede exceder 500 caracteres']);
    exit;
}

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    // Get user info
    $userSql = "SELECT first_name, last_name FROM customers WHERE id = ?";
    $userStmt = $conn->prepare($userSql);
    $userStmt->execute([$user_id]);
    $user = $userStmt->fetch();
    
    if (!$user) {
        echo json_encode(['success' => false, 'message' => 'Usuario no encontrado']);
        exit;
    }
    
    // Insert reply
    $insertSql = "INSERT INTO review_replies (review_id, user_id, reply_text, created_at) VALUES (?, ?, ?, NOW())";
    $insertStmt = $conn->prepare($insertSql);
    $insertStmt->execute([$review_id, $user_id, $reply_text]);
    
    $reply_id = $conn->lastInsertId();
    
    // Get the inserted reply with user info
    $getSql = "SELECT rr.*, c.first_name, c.last_name, rr.created_at
               FROM review_replies rr
               JOIN customers c ON rr.user_id = c.id
               WHERE rr.id = ?";
    $getStmt = $conn->prepare($getSql);
    $getStmt->execute([$reply_id]);
    $reply = $getStmt->fetch();
    
    // Format the reply for display
    $formattedReply = [
        'id' => $reply['id'],
        'user_name' => $reply['first_name'] . ' ' . $reply['last_name'],
        'user_initials' => strtoupper(substr($reply['first_name'], 0, 1) . substr($reply['last_name'], 0, 1)),
        'reply_text' => $reply['reply_text'],
        'created_at' => $reply['created_at'],
        'time_ago' => timeAgo($reply['created_at'])
    ];
    
    echo json_encode([
        'success' => true,
        'message' => 'Respuesta agregada exitosamente',
        'reply' => $formattedReply
    ]);
    
} catch (Exception $e) {
    error_log("Error in review-reply.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error al agregar la respuesta'
    ]);
}

function timeAgo($datetime) {
    $time = time() - strtotime($datetime);
    
    if ($time < 60) return 'hace unos segundos';
    if ($time < 3600) return 'hace ' . floor($time/60) . ' minutos';
    if ($time < 86400) return 'hace ' . floor($time/3600) . ' horas';
    if ($time < 2592000) return 'hace ' . floor($time/86400) . ' días';
    if ($time < 31536000) return 'hace ' . floor($time/2592000) . ' meses';
    return 'hace ' . floor($time/31536000) . ' años';
}
?>
