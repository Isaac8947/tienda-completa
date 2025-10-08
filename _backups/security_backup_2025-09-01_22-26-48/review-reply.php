<?php
session_start();

require_once 'config/database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$review_id = isset($data['review_id']) ? intval($data['review_id']) : 0;
$reply_text = isset($data['reply_text']) ? trim($data['reply_text']) : '';
$user_id = isset($_SESSION['customer_id']) ? $_SESSION['customer_id'] : null;

if (!$review_id || empty($reply_text)) {
    echo json_encode(['success' => false, 'message' => 'ID de reseña y texto de respuesta requeridos']);
    exit;
}

if (!$user_id) {
    echo json_encode(['success' => false, 'message' => 'Debes iniciar sesión para responder']);
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
