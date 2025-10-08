<?php
session_start();

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
    $itemsQuery = "SELECT oi.*, p.name as product_name, p.main_image as product_image 
                   FROM order_items oi 
                   LEFT JOIN products p ON oi.product_id = p.id 
                   WHERE oi.order_id = ?";
    $itemsStmt = $db->prepare($itemsQuery);
    $itemsStmt->execute([$orderId]);
    $items = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Decodificar JSON
    $customerData = json_decode($order['billing_address'], true);
    $shippingAddress = json_decode($order['shipping_address'], true);
    
    // Generar HTML
    ob_start();
    ?>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
        <!-- Información del pedido -->
        <div>
            <div class="mb-6">
                <h4 class="text-lg font-semibold text-gray-800 mb-4">Información del Pedido</h4>
                <div class="bg-gray-50 rounded-lg p-4 space-y-2">
                    <div class="flex justify-between">
                        <span class="text-gray-600">Número:</span>
                        <span class="font-medium">#<?php echo str_pad($order['id'], 6, '0', STR_PAD_LEFT); ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Fecha:</span>
                        <span class="font-medium"><?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Estado:</span>
                        <span class="font-medium capitalize"><?php echo $order['status']; ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Método de pago:</span>
                        <span class="font-medium"><?php echo $order['payment_method']; ?></span>
                    </div>
                    <div class="flex justify-between text-lg font-bold border-t pt-2">
                        <span>Total:</span>
                        <span class="text-primary-600">$<?php echo number_format($order['total'], 0, ',', '.'); ?></span>
                    </div>
                </div>
            </div>
            
            <!-- Información del cliente -->
            <div class="mb-6">
                <h4 class="text-lg font-semibold text-gray-800 mb-4">Datos del Cliente</h4>
                <div class="bg-gray-50 rounded-lg p-4 space-y-2">
                    <div class="flex justify-between">
                        <span class="text-gray-600">Nombre:</span>
                        <span class="font-medium"><?php echo htmlspecialchars($customerData['first_name'] . ' ' . $customerData['last_name']); ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Teléfono:</span>
                        <span class="font-medium"><?php echo htmlspecialchars($customerData['phone']); ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Email:</span>
                        <span class="font-medium"><?php echo htmlspecialchars($customerData['email']); ?></span>
                    </div>
                    <?php if (!empty($customerData['cedula'])): ?>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Cédula:</span>
                        <span class="font-medium"><?php echo htmlspecialchars($customerData['cedula']); ?></span>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Dirección de envío -->
            <div>
                <h4 class="text-lg font-semibold text-gray-800 mb-4">Dirección de Envío</h4>
                <div class="bg-gray-50 rounded-lg p-4 space-y-2">
                    <div class="flex justify-between">
                        <span class="text-gray-600">Departamento:</span>
                        <span class="font-medium"><?php echo htmlspecialchars($shippingAddress['department']); ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Ciudad:</span>
                        <span class="font-medium"><?php echo htmlspecialchars($shippingAddress['city']); ?></span>
                    </div>
                    <div>
                        <span class="text-gray-600">Dirección:</span>
                        <p class="mt-1 font-medium"><?php echo htmlspecialchars($shippingAddress['address']); ?></p>
                    </div>
                </div>
            </div>
            
            <?php if (!empty($order['notes'])): ?>
            <!-- Notas -->
            <div class="mt-6">
                <h4 class="text-lg font-semibold text-gray-800 mb-4">Notas del Cliente</h4>
                <div class="bg-yellow-50 rounded-lg p-4 border border-yellow-200">
                    <p class="text-gray-700"><?php echo nl2br(htmlspecialchars($order['notes'])); ?></p>
                </div>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Productos -->
        <div>
            <h4 class="text-lg font-semibold text-gray-800 mb-4">Productos (<?php echo count($items); ?> items)</h4>
            <div class="space-y-4">
                <?php foreach ($items as $item): ?>
                <div class="bg-gray-50 rounded-lg p-4">
                    <div class="flex items-center space-x-4">
                        <div class="w-16 h-16 bg-white rounded-lg overflow-hidden flex-shrink-0">
                            <?php if (!empty($item['product_image'])): ?>
                            <img src="../<?php echo htmlspecialchars($item['product_image']); ?>" 
                                 alt="<?php echo htmlspecialchars($item['product_name'] ?? 'Producto'); ?>"
                                 class="w-full h-full object-cover">
                            <?php else: ?>
                            <div class="w-full h-full bg-gray-200 flex items-center justify-center">
                                <i class="fas fa-image text-gray-400"></i>
                            </div>
                            <?php endif; ?>
                        </div>
                        <div class="flex-1">
                            <h5 class="font-medium text-gray-800">
                                <?php echo htmlspecialchars($item['product_name'] ?? 'Producto no encontrado'); ?>
                            </h5>
                            <div class="text-sm text-gray-600 mt-1">
                                <span>Cantidad: <?php echo $item['quantity']; ?></span>
                                <span class="mx-2">•</span>
                                <span>Precio: $<?php echo number_format($item['price'], 0, ',', '.'); ?></span>
                            </div>
                            <div class="text-lg font-semibold text-primary-600 mt-2">
                                $<?php echo number_format($item['subtotal'], 0, ',', '.'); ?>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <!-- Resumen de costos -->
            <div class="mt-6 bg-primary-50 rounded-lg p-4 border border-primary-200">
                <h5 class="font-semibold text-gray-800 mb-3">Resumen de Costos</h5>
                <div class="space-y-2 text-sm">
                    <div class="flex justify-between">
                        <span class="text-gray-600">Subtotal:</span>
                        <span class="font-medium">$<?php echo number_format($order['subtotal'], 0, ',', '.'); ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">IVA:</span>
                        <span class="font-medium">$<?php echo number_format($order['tax_amount'], 0, ',', '.'); ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Envío:</span>
                        <span class="font-medium">
                            <?php if ($order['shipping_amount'] == 0): ?>
                                <span class="text-green-600">¡Gratis!</span>
                            <?php else: ?>
                                $<?php echo number_format($order['shipping_amount'], 0, ',', '.'); ?>
                            <?php endif; ?>
                        </span>
                    </div>
                    <hr class="border-primary-200">
                    <div class="flex justify-between text-lg font-bold">
                        <span>Total:</span>
                        <span class="text-primary-600">$<?php echo number_format($order['total'], 0, ',', '.'); ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Acciones -->
    <div class="mt-8 pt-6 border-t">
        <div class="flex flex-wrap gap-4">
            <button onclick="editOrderStatus(<?php echo $order['id']; ?>, '<?php echo $order['status']; ?>'); closeModal();" 
                    class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition-colors">
                <i class="fas fa-edit mr-2"></i>Cambiar Estado
            </button>
            <button onclick="generateWhatsApp(<?php echo $order['id']; ?>); closeModal();" 
                    class="px-4 py-2 bg-green-500 text-white rounded-lg hover:bg-green-600 transition-colors">
                <i class="fab fa-whatsapp mr-2"></i>Enviar WhatsApp
            </button>
            <a href="mailto:<?php echo htmlspecialchars($customerData['email']); ?>" 
               class="px-4 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600 transition-colors">
                <i class="fas fa-envelope mr-2"></i>Enviar Email
            </a>
            <a href="tel:<?php echo htmlspecialchars($customerData['phone']); ?>" 
               class="px-4 py-2 bg-primary-500 text-white rounded-lg hover:bg-primary-600 transition-colors">
                <i class="fas fa-phone mr-2"></i>Llamar Cliente
            </a>
        </div>
    </div>
    <?php
    $html = ob_get_clean();
    
    echo json_encode([
        'success' => true,
        'html' => $html
    ]);
    
} catch (Exception $e) {
    error_log('Error obteniendo detalles del pedido: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
