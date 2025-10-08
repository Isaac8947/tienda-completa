<?php
session_start();

header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');

require_once '../config/database.php';

// Verificar que el usuario sea administrador
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Acceso denegado']);
    exit;
}

// Verificar método POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

try {
    // Validar datos
    if (!isset($_POST['order_id']) || !is_numeric($_POST['order_id'])) {
        throw new Exception('ID de pedido inválido');
    }
    
    if (!isset($_POST['status']) || empty($_POST['status'])) {
        throw new Exception('Estado requerido');
    }
    
    $orderId = (int)$_POST['order_id'];
    $newStatus = trim($_POST['status']);
    
    // Validar estados permitidos
    $allowedStatuses = ['pending', 'confirmed', 'shipped', 'delivered', 'cancelled'];
    if (!in_array($newStatus, $allowedStatuses)) {
        throw new Exception('Estado no válido');
    }
    
    $database = new Database();
    $db = $database->getConnection();
    
    // Verificar que el pedido existe
    $checkQuery = "SELECT id, status FROM orders WHERE id = ?";
    $checkStmt = $db->prepare($checkQuery);
    $checkStmt->execute([$orderId]);
    $order = $checkStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$order) {
        throw new Exception('Pedido no encontrado');
    }
    
    // Manejar cambios de stock según el estado
    $oldStatus = $order['status'];
    
    // Si se cancela una orden, restaurar el stock
    if ($newStatus === 'cancelled' && $oldStatus !== 'cancelled') {
        require_once '../includes/InventoryManager.php';
        $inventoryManager = new InventoryManager($db);
        
        // Obtener items de la orden
        $itemsQuery = "SELECT product_id, quantity, product_name FROM order_items WHERE order_id = ?";
        $itemsStmt = $db->prepare($itemsQuery);
        $itemsStmt->execute([$orderId]);
        $orderItems = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Restaurar stock para cada item
        foreach ($orderItems as $item) {
            // Restaurar stock
            $restoreStockQuery = "UPDATE products SET stock = stock + ? WHERE id = ?";
            $restoreStockStmt = $db->prepare($restoreStockQuery);
            $restoreStockStmt->execute([$item['quantity'], $item['product_id']]);
            
            // Registrar movimiento de inventario
            try {
                $inventoryManager->recordMovement([
                    'product_id' => $item['product_id'],
                    'movement_type' => 'return',
                    'quantity_change' => $item['quantity'],
                    'reason' => "Cancelación de orden #{$orderId}",
                    'reference_id' => $orderId,
                    'reference_type' => 'order_cancellation',
                    'created_by' => $_SESSION['user_id']
                ]);
            } catch (Exception $e) {
                error_log('Error registrando movimiento de inventario para cancelación: ' . $e->getMessage());
            }
        }
    }
    
    // Actualizar estado
    $updateQuery = "UPDATE orders SET status = ?, updated_at = NOW() WHERE id = ?";
    $updateStmt = $db->prepare($updateQuery);
    $result = $updateStmt->execute([$newStatus, $orderId]);
    
    if (!$result) {
        throw new Exception('Error al actualizar el estado');
    }
    
    // Log de la actividad (opcional)
    $logQuery = "INSERT INTO order_status_logs (order_id, previous_status, new_status, changed_by, created_at) 
                 VALUES (?, ?, ?, ?, NOW())";
    try {
        $logStmt = $db->prepare($logQuery);
        $logStmt->execute([$orderId, $order['status'], $newStatus, $_SESSION['user_id']]);
    } catch (Exception $e) {
        // Si la tabla de logs no existe, continuamos sin error
        error_log('Warning: Could not log status change: ' . $e->getMessage());
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Estado actualizado correctamente'
    ]);
    
} catch (Exception $e) {
    error_log('Error actualizando estado del pedido: ' . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
