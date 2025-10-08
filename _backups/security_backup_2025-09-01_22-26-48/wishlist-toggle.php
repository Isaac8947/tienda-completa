<?php
// Log errors instead of displaying them
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

// Start session first
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Usuario no logueado'
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
    $productId = intval($_POST['product_id']);

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
