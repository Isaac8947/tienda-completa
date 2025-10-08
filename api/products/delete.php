<?php
session_start();
header('Content-Type: application/json');

require_once '../../config/config.php';

// Check if admin is logged in using the correct function
if (!isAdminLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Acceso no autorizado']);
    exit;
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

require_once '../../config/database.php';
require_once '../../models/Product.php';

try {
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || !isset($input['product_ids']) || !is_array($input['product_ids'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'IDs de productos no válidos']);
        exit;
    }
    
    $productIds = array_filter($input['product_ids'], function($id) {
        return is_numeric($id) && $id > 0;
    });
    
    if (empty($productIds)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'No se proporcionaron IDs válidos']);
        exit;
    }
    
    // Initialize database and model
    $database = new Database();
    $db = $database->getConnection();
    $product = new Product($db);
    
    // Perform bulk delete
    $result = $product->bulkDelete($productIds);
    
    if ($result) {
        $count = count($productIds);
        $message = $count === 1 ? 'Producto eliminado exitosamente' : "$count productos eliminados exitosamente";
        echo json_encode([
            'success' => true, 
            'message' => $message,
            'deleted_count' => $count
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error al eliminar los productos. Verifica que los productos existan.']);
    }
    
} catch (Exception $e) {
    error_log("Delete API Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error interno del servidor']);
}
?>
