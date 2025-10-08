<?php
session_start();

header('Content-Type: application/json');

require_once '../config/database.php';

// Verificar que el usuario sea administrador
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Acceso denegado']);
    exit;
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID de pedido inválido']);
    exit;
}

$orderId = (int)$_GET['id'];

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Obtener datos del pedido
    $orderQuery = "SELECT * FROM orders WHERE id = ?";
    $orderStmt = $db->prepare($orderQuery);
    $orderStmt->execute([$orderId]);
    $order = $orderStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$order) {
        throw new Exception('Pedido no encontrado');
    }
    
    // Obtener items del pedido
    $itemsQuery = "SELECT oi.*, p.name as product_name 
                   FROM order_items oi 
                   LEFT JOIN products p ON oi.product_id = p.id 
                   WHERE oi.order_id = ?";
    $itemsStmt = $db->prepare($itemsQuery);
    $itemsStmt->execute([$orderId]);
    $items = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Decodificar JSON
    $customerData = json_decode($order['billing_address'], true);
    $shippingAddress = json_decode($order['shipping_address'], true);
    
    // Generar mensaje según el estado del pedido
    $message = generateStatusMessage($order, $customerData, $shippingAddress, $items);
    
    // Número del cliente
    $clientPhone = $customerData['phone'];
    // Asegurarse de que el número tenga el prefijo de país
    if (!str_starts_with($clientPhone, '57')) {
        $clientPhone = '57' . $clientPhone;
    }
    
    // URL de WhatsApp
    $whatsappUrl = 'https://wa.me/' . $clientPhone . '?text=' . urlencode($message);
    
    echo json_encode([
        'success' => true,
        'whatsapp_url' => $whatsappUrl,
        'message' => $message
    ]);
    
} catch (Exception $e) {
    error_log('Error generando mensaje de WhatsApp: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

/**
 * Genera el mensaje de WhatsApp según el estado del pedido
 */
function generateStatusMessage($order, $customer, $address, $items) {
    $orderNumber = str_pad($order['id'], 6, '0', STR_PAD_LEFT);
    $customerName = $customer['first_name'];
    
    switch ($order['status']) {
        case 'pending':
            $message = "¡Hola {$customerName}! 👋\n\n";
            $message .= "Hemos recibido tu pedido #{$orderNumber} por un valor de *\${$order['total']}*.\n\n";
            $message .= "📋 *Productos solicitados:*\n";
            foreach ($items as $item) {
                $message .= "• {$item['product_name']} (x{$item['quantity']})\n";
            }
            $message .= "\n📍 *Dirección de envío:*\n";
            $message .= "{$address['address']}, {$address['city']}, {$address['department']}\n\n";
            $message .= "✅ Estamos confirmando la disponibilidad de todos los productos.\n";
            $message .= "Te contactaremos pronto para confirmar tu pedido y coordinar la entrega.\n\n";
            $message .= "¡Gracias por confiar en Odisea Makeup! 💄✨";
            break;
            
        case 'confirmed':
            $message = "¡Excelente noticia {$customerName}! 🎉\n\n";
            $message .= "Tu pedido #{$orderNumber} ha sido *CONFIRMADO* ✅\n\n";
            $message .= "💰 *Total a pagar:* \${$order['total']}\n";
            $message .= "📍 *Dirección de entrega:* {$address['address']}, {$address['city']}\n\n";
            $message .= "🚚 *Información de entrega:*\n";
            $message .= "• Tiempo estimado: 1-3 días hábiles\n";
            $message .= "• Pago contra entrega (efectivo o transferencia)\n";
            $message .= "• Te contactaremos 1 día antes de la entrega\n\n";
            $message .= "¿Tienes alguna pregunta sobre tu pedido? ¡Escríbenos! 😊";
            break;
            
        case 'shipped':
            $message = "📦 ¡Tu pedido va en camino {$customerName}! 🚛\n\n";
            $message .= "Tu pedido #{$orderNumber} ha sido *ENVIADO* y está en ruta a tu dirección.\n\n";
            $message .= "📍 *Dirección de entrega:*\n{$address['address']}, {$address['city']}, {$address['department']}\n\n";
            $message .= "⏰ *Tiempo estimado de entrega:* 24-48 horas\n";
            $message .= "💰 *Total a pagar:* \${$order['total']}\n\n";
            $message .= "🔔 Te llamaremos cuando nuestro repartidor esté cerca de tu ubicación.\n\n";
            $message .= "¡Ya casi puedes disfrutar de tus productos Odisea Makeup! 💄✨";
            break;
            
        case 'delivered':
            $message = "🎉 ¡Pedido entregado exitosamente! 📦✅\n\n";
            $message .= "Hola {$customerName}, confirmamos que tu pedido #{$orderNumber} ha sido entregado.\n\n";
            $message .= "Esperamos que disfrutes mucho tus nuevos productos de maquillaje 💄✨\n\n";
            $message .= "📝 *Tu opinión es importante:*\n";
            $message .= "• ¿Cómo fue tu experiencia de compra?\n";
            $message .= "• ¿Los productos cumplieron tus expectativas?\n";
            $message .= "• ¿Recomendarías Odisea Makeup?\n\n";
            $message .= "🛍️ No olvides seguirnos en redes sociales para conocer nuestras ofertas y nuevos productos.\n\n";
            $message .= "¡Gracias por ser parte de la familia Odisea Makeup! 💕";
            break;
            
        case 'cancelled':
            $message = "Hola {$customerName}, 😔\n\n";
            $message .= "Lamentamos informarte que tu pedido #{$orderNumber} ha sido *CANCELADO*.\n\n";
            $message .= "📞 Motivo: [Por favor especifica el motivo de cancelación]\n\n";
            $message .= "💝 *No te preocupes:*\n";
            $message .= "• Si realizaste algún pago, será reembolsado completamente\n";
            $message .= "• Puedes realizar un nuevo pedido cuando gustes\n";
            $message .= "• Nuestros productos siguen disponibles para ti\n\n";
            $message .= "¿Podemos ayudarte con algo más? Estamos aquí para servirte 😊\n\n";
            $message .= "Odisea Makeup - Siempre contigo 💄✨";
            break;
            
        default:
            $message = "Hola {$customerName}! 👋\n\n";
            $message .= "Te contactamos sobre tu pedido #{$orderNumber}.\n\n";
            $message .= "Estado actual: " . ucfirst($order['status']) . "\n";
            $message .= "Total: \${$order['total']}\n\n";
            $message .= "¿En qué podemos ayudarte hoy? 😊";
    }
    
    return $message;
}
?>
