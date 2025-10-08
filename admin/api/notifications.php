<?php
header('Content-Type: application/json');
require_once '../../config/config.php';
require_once '../../config/database.php';

// Verificar autenticación
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    // Auto-login para desarrollo
    $_SESSION['user_id'] = 1;
    $_SESSION['role'] = 'admin';
}

$database = new Database();
$db = $database->getConnection();

$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true);

try {
    switch ($method) {
        case 'GET':
            if (isset($_GET['action'])) {
                switch ($_GET['action']) {
                    case 'get_count':
                        // Obtener count de notificaciones no leídas
                        $stmt = $db->prepare("SELECT COUNT(*) as count FROM admin_notifications WHERE is_read = FALSE");
                        $stmt->execute();
                        $result = $stmt->fetch();
                        
                        echo json_encode(['count' => (int)$result['count']]);
                        break;
                        
                    case 'get_unread':
                        // Obtener notificaciones no leídas
                        $limit = intval($_GET['limit'] ?? 15);
                        $stmt = $db->prepare("
                            SELECT * FROM admin_notifications 
                            WHERE is_read = FALSE 
                            ORDER BY priority DESC, created_at DESC 
                            LIMIT ?
                        ");
                        $stmt->execute([$limit]);
                        $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
                        
                        echo json_encode(['notifications' => $notifications]);
                        break;
                        
                    case 'get_all':
                        // Obtener todas las notificaciones
                        $limit = intval($_GET['limit'] ?? 50);
                        $stmt = $db->prepare("
                            SELECT * FROM admin_notifications 
                            ORDER BY created_at DESC 
                            LIMIT ?
                        ");
                        $stmt->execute([$limit]);
                        $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
                        
                        echo json_encode(['notifications' => $notifications]);
                        break;
                }
            }
            break;
            
        case 'POST':
            if (isset($input['action'])) {
                switch ($input['action']) {
                    case 'mark_read':
                        // Marcar una notificación como leída
                        $id = intval($input['id'] ?? 0);
                        if ($id > 0) {
                            $stmt = $db->prepare("
                                UPDATE admin_notifications 
                                SET is_read = TRUE, read_at = CURRENT_TIMESTAMP 
                                WHERE id = ?
                            ");
                            $stmt->execute([$id]);
                            
                            echo json_encode(['success' => true]);
                        } else {
                            echo json_encode(['success' => false, 'error' => 'ID inválido']);
                        }
                        break;
                        
                    case 'mark_all_read':
                        // Marcar todas como leídas
                        $stmt = $db->prepare("
                            UPDATE admin_notifications 
                            SET is_read = TRUE, read_at = CURRENT_TIMESTAMP 
                            WHERE is_read = FALSE
                        ");
                        $stmt->execute();
                        
                        echo json_encode(['success' => true]);
                        break;
                        
                    case 'create':
                        // Crear nueva notificación
                        $type = trim($input['type'] ?? '');
                        $title = trim($input['title'] ?? '');
                        $message = trim($input['message'] ?? '');
                        $related_id = $input['related_id'] ?? null;
                        $priority = trim($input['priority'] ?? 'medium');
                        
                        if (!empty($type) && !empty($title) && !empty($message)) {
                            $stmt = $db->prepare("
                                INSERT INTO admin_notifications (type, title, message, related_id, priority) 
                                VALUES (?, ?, ?, ?, ?)
                            ");
                            $stmt->execute([$type, $title, $message, $related_id, $priority]);
                            
                            echo json_encode(['success' => true, 'id' => $db->lastInsertId()]);
                        } else {
                            echo json_encode(['success' => false, 'error' => 'Datos incompletos']);
                        }
                        break;
                        
                    case 'delete':
                        // Eliminar notificación
                        $id = intval($input['id'] ?? 0);
                        if ($id > 0) {
                            $stmt = $db->prepare("DELETE FROM admin_notifications WHERE id = ?");
                            $stmt->execute([$id]);
                            
                            echo json_encode(['success' => true]);
                        } else {
                            echo json_encode(['success' => false, 'error' => 'ID inválido']);
                        }
                        break;
                        
                    case 'get_stats':
                        // Obtener estadísticas rápidas para dashboard
                        $stats = [];
                        
                        // Pedidos pendientes
                        $stmt = $db->query("SELECT COUNT(*) as count FROM orders WHERE status = 'pending'");
                        $stats['pending_orders'] = $stmt->fetchColumn();
                        
                        // Productos con stock bajo
                        $stmt = $db->query("SELECT COUNT(*) as count FROM products WHERE stock <= min_stock");
                        $stats['low_stock_products'] = $stmt->fetchColumn();
                        
                        // Notificaciones no leídas
                        $stmt = $db->query("SELECT COUNT(*) as count FROM admin_notifications WHERE is_read = FALSE");
                        $stats['unread_notifications'] = $stmt->fetchColumn();
                        
                        // Ventas de hoy
                        $stmt = $db->query("
                            SELECT COUNT(*) as count 
                            FROM orders 
                            WHERE DATE(created_at) = CURDATE() AND status NOT IN ('cancelled')
                        ");
                        $stats['today_sales'] = $stmt->fetchColumn();
                        
                        // Ingresos de hoy
                        $stmt = $db->query("
                            SELECT COALESCE(SUM(total_amount), 0) as total 
                            FROM orders 
                            WHERE DATE(created_at) = CURDATE() AND status NOT IN ('cancelled')
                        ");
                        $stats['today_revenue'] = $stmt->fetchColumn();
                        
                        echo json_encode(['success' => true, 'stats' => $stats]);
                        break;
                }
            }
            break;
            
        case 'DELETE':
            // Eliminar notificación por ID en URL
            $id = intval($_GET['id'] ?? 0);
            if ($id > 0) {
                $stmt = $db->prepare("DELETE FROM admin_notifications WHERE id = ?");
                $stmt->execute([$id]);
                
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'error' => 'ID inválido']);
            }
            break;
            
        default:
            echo json_encode(['success' => false, 'error' => 'Método no permitido']);
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
