<?php
// Prevenir cualquier output antes de headers
ob_start();

// Configuración de errores silenciosa para producción
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/logs/procesar_pedido.log');

// Configuración de sesión segura
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 0); // Cambiar a 1 en HTTPS
ini_set('session.cookie_samesite', 'Strict');

session_start();

// Limpiar cualquier output previo
while (ob_get_level()) {
    ob_end_clean();
}

// Headers para respuesta JSON
header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');

// Verificar método POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    error_log('Error: Método no POST recibido: ' . $_SERVER['REQUEST_METHOD']);
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

// Log de inicio
error_log('Iniciando procesamiento de pedido - IP: ' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));

require_once 'config/database.php';
require_once 'models/Order.php';
require_once 'models/Product.php';
require_once 'models/SiteSettings.php';
require_once 'includes/InventoryValidatorSimple.php';

try {
    // Verificar que hay productos en el carrito
    $cart = $_SESSION['cart'] ?? [];
    error_log('Carrito encontrado: ' . json_encode($cart));
    
    if (empty($cart)) {
        throw new Exception('El carrito está vacío');
    }
    
    // Conectar a la base de datos
    $database = new Database();
    $db = $database->getConnection();
    
    if (!$db) {
        throw new Exception('Error de conexión a la base de datos');
    }
    
    // Obtener configuraciones
    require_once 'models/SiteSettings.php';
    $whatsappSettings = SiteSettings::getWhatsAppSettings();
    
    error_log('Configuraciones WhatsApp obtenidas: ' . json_encode($whatsappSettings));
    
    error_log('Conexión a BD exitosa');
    
    // Validar stock disponible antes de procesar
    $inventoryValidator = new InventoryValidator($db);
    
    // Verificar que la clase se cargó correctamente
    if (!$inventoryValidator) {
        throw new Exception('Error al cargar el validador de inventario');
    }
    
    // Convertir formato del carrito si es necesario
    $cartForValidation = [];
    foreach ($cart as $item) {
        $cartForValidation[] = [
            'product_id' => isset($item['id']) ? $item['id'] : $item['product_id'],
            'quantity' => $item['quantity']
        ];
    }
    
    error_log('Validando stock para: ' . json_encode($cartForValidation));
    
    $stockValidation = $inventoryValidator->validateCartStock($cartForValidation);
    
    if (!$stockValidation['valid']) {
        $errorMessages = [];
        foreach ($stockValidation['errors'] as $error) {
            $errorMessages[] = $error['product_name'] . ': ' . $error['message'] . 
                             ' (Disponible: ' . $error['available'] . ', Solicitado: ' . $error['requested'] . ')';
        }
        throw new Exception('Stock insuficiente para algunos productos: ' . implode('; ', $errorMessages));
    }
    
    // Validar y sanitizar datos del formulario
    $requiredFields = ['firstName', 'lastName', 'phone', 'email', 'department', 'city', 'address'];
    $orderData = [];
    
    foreach ($requiredFields as $field) {
        if (!isset($_POST[$field]) || empty(trim($_POST[$field]))) {
            throw new Exception("El campo {$field} es obligatorio");
        }
        $orderData[$field] = trim(filter_var($_POST[$field], FILTER_SANITIZE_FULL_SPECIAL_CHARS));
    }
    
    // Campos opcionales
    $orderData['cedula'] = trim(filter_var($_POST['cedula'] ?? '', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
    $orderData['notes'] = trim(filter_var($_POST['notes'] ?? '', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
    
    // Validar email
    if (!filter_var($orderData['email'], FILTER_VALIDATE_EMAIL)) {
        throw new Exception('El email no es válido');
    }
    
    // Validar teléfono
    if (!preg_match('/^[0-9]{10}$/', $orderData['phone'])) {
        throw new Exception('El teléfono debe tener 10 dígitos');
    }
    
    // Verificar términos y condiciones
    if (!isset($_POST['terms']) || $_POST['terms'] !== 'on') {
        throw new Exception('Debes aceptar los términos y condiciones');
    }
    
    // Conectar a la base de datos
    $database = new Database();
    $db = $database->getConnection();
    $orderModel = new Order($db);
    $productModel = new Product($db);
    
    // Calcular totales del carrito
    $subtotal = 0;
    $cartItems = [];
    
    foreach ($cart as $item) {
        $productId = $item['id'];
        $product = $productModel->findById($productId);
        if (!$product) {
            throw new Exception("Producto no encontrado: {$productId}");
        }
        
        $itemTotal = $product['price'] * $item['quantity'];
        $subtotal += $itemTotal;
        
        $cartItems[] = [
            'product_id' => $productId,
            'name' => $product['name'],
            'price' => $product['price'],
            'quantity' => $item['quantity'],
            'total' => $itemTotal
        ];
    }
    
    // Cálculos finales
    $tax = $subtotal * ($whatsappSettings['tax_rate'] / 100); // IVA desde configuración
    $shipping = $subtotal > $whatsappSettings['shipping_free_threshold'] ? 0 : $whatsappSettings['shipping_cost']; // Envío desde configuración
    $total = $subtotal + $tax + $shipping;
    
    // Preparar datos del pedido
    $customerData = [
        'first_name' => $orderData['firstName'],
        'last_name' => $orderData['lastName'],
        'phone' => $orderData['phone'],
        'email' => $orderData['email'],
        'cedula' => $orderData['cedula']
    ];
    
    $addressData = [
        'department' => $orderData['department'],
        'city' => $orderData['city'],
        'address' => $orderData['address']
    ];
    
    // Crear el pedido
    $orderId = $orderModel->createFromSessionCart($cartItems, $customerData, $addressData, $orderData['notes']);
    
    if (!$orderId) {
        throw new Exception('Error al crear el pedido');
    }
    
    // Limpiar el carrito
    unset($_SESSION['cart']);
    
    // Generar mensaje de WhatsApp usando plantilla personalizable
    $whatsappMessage = generateWhatsAppMessage(
        $orderId, 
        $customerData, 
        $addressData, 
        $cartItems, 
        $subtotal, 
        $tax, 
        $shipping, 
        $total, 
        $orderData['notes'],
        $whatsappSettings
    );
    
    // Validar que el mensaje no esté vacío
    if (empty($whatsappMessage)) {
        error_log("Error: Mensaje WhatsApp vacío");
        $whatsappMessage = "Nuevo pedido #" . str_pad($orderId, 6, '0', STR_PAD_LEFT) . " - Por favor contacta con la tienda para más detalles.";
    }
    
    error_log("Mensaje WhatsApp generado (primeros 200 chars): " . substr($whatsappMessage, 0, 200));
    
    // Número de WhatsApp desde configuración
    $whatsappNumber = $whatsappSettings['whatsapp_number'] ?? '3022387799';
    
    // Validar número de WhatsApp
    if (empty($whatsappNumber)) {
        error_log("Error: Número WhatsApp vacío, usando número por defecto");
        $whatsappNumber = '3022387799';
    }
    
    error_log("Número WhatsApp a usar: $whatsappNumber");
    
    // URL de WhatsApp
    $whatsappUrl = 'https://wa.me/' . $whatsappNumber . '?text=' . urlencode($whatsappMessage);
    
    error_log("URL WhatsApp generada: " . substr($whatsappUrl, 0, 150) . "...");
    
    // Respuesta exitosa
    error_log("Pedido creado exitosamente con ID: $orderId");
    
    echo json_encode([
        'success' => true,
        'message' => 'Pedido creado exitosamente',
        'order_id' => $orderId,
        'whatsapp_url' => $whatsappUrl
    ]);
    
} catch (Exception $e) {
    // Log del error para debugging
    error_log('Error procesando pedido: ' . $e->getMessage() . ' - Trace: ' . $e->getTraceAsString());
    
    // Limpiar cualquier output que pueda haber quedado
    if (ob_get_level()) {
        ob_clean();
    }
    
    // Asegurar headers correctos
    if (!headers_sent()) {
        header('Content-Type: application/json');
        http_response_code(400);
    }
    
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'error_code' => $e->getCode()
    ]);
    exit();
} catch (Error $e) {
    // Manejo de errores fatales
    error_log('Error fatal procesando pedido: ' . $e->getMessage());
    
    if (ob_get_level()) {
        ob_clean();
    }
    
    if (!headers_sent()) {
        header('Content-Type: application/json');
        http_response_code(500);
    }
    
    echo json_encode([
        'success' => false,
        'message' => 'Error interno del servidor',
        'error_type' => 'fatal_error'
    ]);
    exit();
}

/**
 * Genera el mensaje de WhatsApp usando plantilla personalizable
 */
function generateWhatsAppMessage($orderId, $customer, $address, $items, $subtotal, $tax, $shipping, $total, $notes = '', $settings = []) {
    // Usar plantilla personalizada o por defecto
    $template = $settings['whatsapp_message_template'] ?? getDefaultMessageTemplate();
    $storeName = $settings['store_name'] ?? 'Odisea Makeup';
    $taxRate = $settings['tax_rate'] ?? 19;
    
    // Formatear número de pedido
    $orderNumber = str_pad($orderId, 6, '0', STR_PAD_LEFT);
    
    // Generar lista de productos
    $productsList = '';
    foreach ($items as $item) {
        $productsList .= "• " . $item['name'] . "\n";
        $productsList .= "  Cantidad: " . $item['quantity'] . " x $" . number_format($item['price'], 0, ',', '.') . "\n";
        $productsList .= "  Subtotal: $" . number_format($item['total'], 0, ',', '.') . "\n\n";
    }
    
    // Formatear envío
    $shippingText = $shipping == 0 ? "¡GRATIS! 🎉" : "$" . number_format($shipping, 0, ',', '.');
    
    // Formatear notas
    $notesText = !empty($notes) ? "📝 *NOTAS ADICIONALES*\n" . $notes . "\n\n" : '';
    
    // Reemplazar variables en la plantilla
    $replacements = [
        '{STORE_NAME}' => $storeName,
        '{ORDER_NUMBER}' => $orderNumber,
        '{DATE}' => date('d/m/Y H:i'),
        '{CUSTOMER_NAME}' => $customer['first_name'] . ' ' . $customer['last_name'],
        '{CUSTOMER_PHONE}' => $customer['phone'],
        '{CUSTOMER_EMAIL}' => $customer['email'],
        '{CUSTOMER_CEDULA}' => !empty($customer['cedula']) ? $customer['cedula'] : 'No proporcionada',
        '{SHIPPING_DEPARTMENT}' => $address['department'],
        '{SHIPPING_CITY}' => $address['city'],
        '{SHIPPING_ADDRESS}' => $address['address'],
        '{PRODUCTS_LIST}' => trim($productsList),
        '{SUBTOTAL}' => number_format($subtotal, 0, ',', '.'),
        '{TAX}' => number_format($tax, 0, ',', '.'),
        '{TAX_RATE}' => $taxRate,
        '{SHIPPING}' => $shippingText,
        '{TOTAL}' => number_format($total, 0, ',', '.'),
        '{NOTES}' => $notesText
    ];
    
    // Aplicar reemplazos
    $message = str_replace(array_keys($replacements), array_values($replacements), $template);
    
    return $message;
}

/**
 * Plantilla por defecto si no hay configuración personalizada
 */
function getDefaultMessageTemplate() {
    return '🛍️ *NUEVO PEDIDO - {STORE_NAME}*

📋 *Número de Pedido:* #{ORDER_NUMBER}
📅 *Fecha:* {DATE}

👤 *DATOS DEL CLIENTE*
• *Nombre:* {CUSTOMER_NAME}
• *Teléfono:* {CUSTOMER_PHONE}
• *Email:* {CUSTOMER_EMAIL}
• *Cédula:* {CUSTOMER_CEDULA}

📍 *DIRECCIÓN DE ENVÍO*
• *Departamento:* {SHIPPING_DEPARTMENT}
• *Ciudad:* {SHIPPING_CITY}
• *Dirección:* {SHIPPING_ADDRESS}

� *PRODUCTOS PEDIDOS*
{PRODUCTS_LIST}

💰 *RESUMEN DE COSTOS*
• Subtotal: ${SUBTOTAL}
• IVA ({TAX_RATE}%): ${TAX}
• Envío: {SHIPPING}
• *TOTAL: ${TOTAL}*

💳 *MÉTODO DE PAGO*
Pago contra entrega 🚚
(Efectivo o transferencia al recibir)

{NOTES}✅ *¡Hola! Este es mi pedido desde la página web.*
¿Podrías confirmarme la disponibilidad y tiempo de entrega?

¡Gracias! 😊';
}
?>
