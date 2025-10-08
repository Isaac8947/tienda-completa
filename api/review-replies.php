<?php
session_start();

// Configuración de headers para JSON
header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

require_once 'config/database.php';

try {
    // Verificar método GET
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        throw new Exception('Método no permitido');
    }

    // Obtener review_id del parámetro GET
    $reviewId = filter_var($_GET['review_id'] ?? '', FILTER_VALIDATE_INT);

    if (!$reviewId) {
        throw new Exception('ID de review inválido');
    }

    // Conectar a la base de datos
    $database = new Database();
    $db = $database->getConnection();

    // Verificar que el review existe
    $checkReview = $db->prepare("SELECT id FROM reviews WHERE id = ? LIMIT 1");
    $checkReview->execute([$reviewId]);
    
    if (!$checkReview->fetch()) {
        throw new Exception('Review no encontrado');
    }

    // Obtener las respuestas del review
    $getReplies = $db->prepare("
        SELECT 
            id,
            author_name,
            reply_text,
            author_type,
            created_at
        FROM review_replies 
        WHERE review_id = ? 
        ORDER BY created_at ASC
    ");
    
    $getReplies->execute([$reviewId]);
    $replies = $getReplies->fetchAll(PDO::FETCH_ASSOC);

    // Formatear las fechas
    foreach ($replies as &$reply) {
        $reply['created_at'] = date('d/m/Y H:i', strtotime($reply['created_at']));
    }

    echo json_encode([
        'success' => true,
        'replies' => $replies,
        'count' => count($replies)
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'replies' => []
    ]);
} catch (PDOException $e) {
    error_log("Database error in review-replies.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error de base de datos',
        'replies' => []
    ]);
}
?>
