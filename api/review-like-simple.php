<?php
header('Content-Type: application/json');
session_start();

// Debug de sesión
$session_debug = [
    'user_id' => $_SESSION['user_id'] ?? null,
    'customer_id' => $_SESSION['customer_id'] ?? null,
    'session_data' => $_SESSION
];

// Verificar autenticación - acepta tanto user_id como customer_id
$user_id = null;
$is_logged_in = false;

// Priorizar customer_id, luego user_id
if (isset($_SESSION['customer_id']) && !empty($_SESSION['customer_id'])) {
    $user_id = $_SESSION['customer_id'];
    $is_logged_in = true;
} elseif (isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $is_logged_in = true;
}

// Para compatibilidad, configurar customer_id si no existe pero user_id sí
if ($is_logged_in && !isset($_SESSION['customer_id']) && isset($_SESSION['user_id'])) {
    $_SESSION['customer_id'] = $_SESSION['user_id'];
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

$review_id = (int)($data['review_id'] ?? 0);
$action = strtolower(trim($data['action'] ?? ''));

if ($review_id <= 0) {
    echo json_encode(['success' => false, 'error' => 'ID de reseña inválido']);
    exit;
}

if (!in_array($action, ['like', 'dislike'])) {
    echo json_encode([
        'success' => false, 
        'error' => 'Acción inválida: "' . $action . '"',
        'received_action' => $action,
        'valid_actions' => ['like', 'dislike']
    ]);
    exit;
}

try {
    require_once __DIR__ . '/config/database.php';
    
    $db = new Database();
    $conn = $db->getConnection();
    
    // Si no está autenticado, devolver solo los conteos actuales
    if (!$is_logged_in) {
        // Obtener conteos actuales para usuarios anónimos
        $likeCountSql = "SELECT COUNT(*) as count FROM review_likes WHERE review_id = ?";
        $likeStmt = $conn->prepare($likeCountSql);
        $likeStmt->execute([$review_id]);
        $likeCount = $likeStmt->fetch()['count'];
        
        $dislikeCountSql = "SELECT COUNT(*) as count FROM review_dislikes WHERE review_id = ?";
        $dislikeStmt = $conn->prepare($dislikeCountSql);
        $dislikeStmt->execute([$review_id]);
        $dislikeCount = $dislikeStmt->fetch()['count'];
        
        echo json_encode([
            'success' => true,
            'action' => 'toggle', // Para usuarios anónimos, el frontend maneja el toggle
            'reaction_type' => $action,
            'like_count' => $likeCount,
            'dislike_count' => $dislikeCount,
            'authenticated' => false,
            'message' => 'Interacción registrada localmente'
        ]);
        exit;
    }
    
    // Determinar las tablas según la acción
    $table = $action === 'like' ? 'review_likes' : 'review_dislikes';
    $oppositeTable = $action === 'like' ? 'review_dislikes' : 'review_likes';
    
    // Eliminar reacción opuesta si existe
    $deleteOppositeSql = "DELETE FROM {$oppositeTable} WHERE review_id = ? AND user_id = ?";
    $deleteOppositeStmt = $conn->prepare($deleteOppositeSql);
    $deleteOppositeStmt->execute([$review_id, $user_id]);
    
    // Verificar si ya existe la reacción actual
    $checkSql = "SELECT id FROM {$table} WHERE review_id = ? AND user_id = ?";
    $checkStmt = $conn->prepare($checkSql);
    $checkStmt->execute([$review_id, $user_id]);
    $exists = $checkStmt->fetch();
    
    if ($exists) {
        // Remover la reacción
        $deleteSql = "DELETE FROM {$table} WHERE review_id = ? AND user_id = ?";
        $deleteStmt = $conn->prepare($deleteSql);
        $deleteStmt->execute([$review_id, $user_id]);
        $reactionAction = 'removed';
    } else {
        // Agregar la reacción
        $insertSql = "INSERT INTO {$table} (review_id, user_id, created_at) VALUES (?, ?, NOW())";
        $insertStmt = $conn->prepare($insertSql);
        $insertStmt->execute([$review_id, $user_id]);
        $reactionAction = 'added';
    }
    
    // Obtener conteos actualizados
    $likeCountSql = "SELECT COUNT(*) as count FROM review_likes WHERE review_id = ?";
    $likeStmt = $conn->prepare($likeCountSql);
    $likeStmt->execute([$review_id]);
    $likeCount = $likeStmt->fetch()['count'];
    
    $dislikeCountSql = "SELECT COUNT(*) as count FROM review_dislikes WHERE review_id = ?";
    $dislikeStmt = $conn->prepare($dislikeCountSql);
    $dislikeStmt->execute([$review_id]);
    $dislikeCount = $dislikeStmt->fetch()['count'];
    
    echo json_encode([
        'success' => true,
        'action' => $reactionAction,
        'reaction_type' => $action,
        'like_count' => $likeCount,
        'dislike_count' => $dislikeCount,
        'authenticated' => true,
        'message' => ucfirst($action) . ' ' . ($reactionAction === 'added' ? 'agregado' : 'removido')
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Error del servidor: ' . $e->getMessage()
    ]);
}
?>
