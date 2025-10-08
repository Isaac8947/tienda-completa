<?php
session_start();
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../models/Order.php';
require_once '../models/Customer.php';
require_once '../admin/auth-check.php';

$orderModel = new Order();
$customerModel = new Customer();

// Filtrar por estado si se proporciona
$statusFilter = isset($_GET['status']) ? $_GET['status'] : '';
$orders = $statusFilter ? $orderModel->getByStatus($statusFilter) : $orderModel->getAll();

// Procesar cambio de estado
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'update_status') {
        $orderId = $_POST['order_id'];
        $newStatus = $_POST['status'];
        
        $orderModel->updateStatus($orderId, $newStatus);
        
        header('Location: orders.php?updated=1');
        exit;
    }
}

// Obtener detalles de un pedido específico
$orderDetails = null;
if (isset($_GET['view'])) {
    $orderId = $_GET['view'];
    $orderDetails = $orderModel->getOrderWithItems($orderId);
    $customer = $customerModel->getById($orderDetails['customer_id']);
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Pedidos - Odisea Admin</title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Custom Admin CSS -->
    <link rel="stylesheet" href="../admin/assets/css/admin.css">
    
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: {
                            50: '#fdf2f8',
                            100: '#fce7f3',
                            200: '#fbcfe8',
                            300: '#f9a8d4',
                            400: '#f472b6',
                            500: '#ec4899',
                            600: '#db2777',
                            700: '#be185d',
                            800: '#9d174d',
                            900: '#831843'
                        },
                        admin: {
                            50: '#f8fafc',
                            100: '#f1f5f9',
                            200: '#e2e8f0',
                            300: '#cbd5e1',
                            400: '#94a3b8',
                            500: '#64748b',
                            600: '#475569',
                            700: '#334155',
                            800: '#1e293b',
                            900: '#0f172a'
                        }
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-gray-50">
    <div class="flex h-screen overflow-hidden">
        <?php include '../admin/includes/sidebar.php'; ?>
        
        <div class="flex-1 flex flex-col overflow-hidden">
            <?php include '../admin/includes/header.php'; ?>
            
            <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-50 p-4 lg:p-6">
                <!-- Page Header -->
                <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between mb-8">
                    <div class="mb-4 lg:mb-0">
                        <h1 class="text-2xl lg:text-3xl font-bold text-gray-900">Gestión de Pedidos</h1>
                        <p class="text-gray-600 mt-1">Administra los pedidos de la tienda</p>
                    </div>
                    <div class="flex flex-col sm:flex-row space-y-2 sm:space-y-0 sm:space-x-3">
                        <div class="flex items-center space-x-2">
                            <label for="statusFilter" class="text-sm font-medium text-gray-700 whitespace-nowrap">Filtrar por estado:</label>
                            <select id="statusFilter" onchange="filterOrders(this.value)" class="block w-40 px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 text-sm">
                                <option value="">Todos</option>
                                <option value="pending" <?php echo $statusFilter === 'pending' ? 'selected' : ''; ?>>Pendiente</option>
                                <option value="processing" <?php echo $statusFilter === 'processing' ? 'selected' : ''; ?>>En proceso</option>
                                <option value="shipped" <?php echo $statusFilter === 'shipped' ? 'selected' : ''; ?>>Enviado</option>
                                <option value="delivered" <?php echo $statusFilter === 'delivered' ? 'selected' : ''; ?>>Entregado</option>
                                <option value="cancelled" <?php echo $statusFilter === 'cancelled' ? 'selected' : ''; ?>>Cancelado</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Success/Error Messages -->
                <?php if (isset($_GET['updated'])): ?>
                <div class="mb-6 p-4 rounded-lg bg-green-100 text-green-800">
                    <i class="fas fa-check-circle mr-2"></i>
                    Estado del pedido actualizado exitosamente.
                </div>
                <?php endif; ?>
                
                <?php if (isset($_GET['error'])): ?>
                <div class="mb-6 p-4 rounded-lg bg-red-100 text-red-800">
                    <i class="fas fa-exclamation-circle mr-2"></i>
                    <?php 
                    switch($_GET['error']) {
                        case 'no_order_id':
                            echo 'No se proporcionó ID de pedido para la factura.';
                            break;
                        case 'order_not_found':
                            echo 'Pedido no encontrado (ID: ' . ($_GET['order_id'] ?? 'N/A') . ').';
                            break;
                        case 'invoice_data_failed':
                            echo 'Error al obtener datos de la factura (ID: ' . ($_GET['order_id'] ?? 'N/A') . ').';
                            break;
                        case 'exception':
                            echo 'Error del sistema: ' . ($_GET['message'] ?? 'Error desconocido') . ' (ID: ' . ($_GET['order_id'] ?? 'N/A') . ').';
                            break;
                        default:
                            echo 'Error desconocido: ' . htmlspecialchars($_GET['error']);
                    }
                    ?>
                </div>
                <?php endif; ?>
                    
                
                <?php if ($orderDetails): ?>
                <!-- Vista detallada del pedido -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="p-6">
                        <div class="flex items-center justify-between mb-6">
                            <h3 class="text-xl font-bold text-gray-900">Pedido #<?php echo $orderDetails['id'] ?? ''; ?></h3>
                            <span class="px-3 py-1 rounded-full text-sm font-medium <?php echo getStatusBadgeClass($orderDetails['status'] ?? 'pending'); ?>">
                                <?php echo ucfirst($orderDetails['status'] ?? 'pending'); ?>
                            </span>
                        </div>
                        
                        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
                            <!-- Información del Cliente -->
                            <div class="bg-gray-50 rounded-lg p-4">
                                <h4 class="text-lg font-semibold text-gray-900 mb-3">Información del Cliente</h4>
                                <div class="space-y-2 text-sm">
                                    <p><span class="font-medium text-gray-700">Nombre:</span> <?php echo ($customer['first_name'] ?? '') . ' ' . ($customer['last_name'] ?? ''); ?></p>
                                    <p><span class="font-medium text-gray-700">Email:</span> <?php echo $customer['email'] ?? 'N/A'; ?></p>
                                    <p><span class="font-medium text-gray-700">Teléfono:</span> <?php echo $customer['phone'] ?? 'N/A'; ?></p>
                                </div>
                            </div>
                            
                            <!-- Información de Envío -->
                            <div class="bg-gray-50 rounded-lg p-4">
                                <h4 class="text-lg font-semibold text-gray-900 mb-3">Información de Envío</h4>
                                <div class="space-y-2 text-sm">
                                    <p><span class="font-medium text-gray-700">Dirección:</span> 
                                        <?php 
                                        $shippingAddress = $orderDetails['shipping_address'] ?? '';
                                        if (is_array($shippingAddress)) {
                                            echo ($shippingAddress['address'] ?? '') . '<br>' . 
                                                 ($shippingAddress['city'] ?? '') . '<br>' . 
                                                 ($shippingAddress['department'] ?? '');
                                        } else if (is_string($shippingAddress)) {
                                            $decoded = json_decode($shippingAddress, true);
                                            if ($decoded && is_array($decoded)) {
                                                echo ($decoded['address'] ?? '') . '<br>' . 
                                                     ($decoded['city'] ?? '') . '<br>' . 
                                                     ($decoded['department'] ?? '');
                                            } else {
                                                echo htmlspecialchars($shippingAddress);
                                            }
                                        } else {
                                            echo 'N/A';
                                        }
                                        ?>
                                    </p>
                                    <p><span class="font-medium text-gray-700">Ciudad:</span> <?php echo $orderDetails['shipping_city'] ?? 'N/A'; ?></p>
                                    <p><span class="font-medium text-gray-700">Código Postal:</span> <?php echo $orderDetails['shipping_postal_code'] ?? 'N/A'; ?></p>
                                    <p><span class="font-medium text-gray-700">País:</span> <?php echo $orderDetails['shipping_country'] ?? 'Colombia'; ?></p>
                                </div>
                            </div>
                            
                            <!-- Información del Pedido -->
                            <div class="bg-gray-50 rounded-lg p-4">
                                <h4 class="text-lg font-semibold text-gray-900 mb-3">Información del Pedido</h4>
                                <div class="space-y-2 text-sm">
                                    <p><span class="font-medium text-gray-700">Fecha:</span> <?php echo ($orderDetails['created_at'] ?? '') ? date('d/m/Y H:i', strtotime($orderDetails['created_at'])) : 'No disponible'; ?></p>
                                    <p><span class="font-medium text-gray-700">Método de Pago:</span> <?php echo htmlspecialchars($orderDetails['payment_method'] ?? 'No especificado'); ?></p>
                                    <p><span class="font-medium text-gray-700">Estado del Pago:</span> <?php echo htmlspecialchars($orderDetails['payment_status'] ?? 'Desconocido'); ?></p>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Productos -->
                        <div class="mb-8">
                            <h4 class="text-lg font-semibold text-gray-900 mb-4">Productos</h4>
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Producto</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Precio</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Cantidad</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Subtotal</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        <?php foreach ($orderDetails['items'] as $item): ?>
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?php echo htmlspecialchars($item['product_name'] ?? ''); ?></td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">$<?php echo number_format($item['price'] ?? 0, 2); ?></td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo $item['quantity'] ?? 0; ?></td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">$<?php echo number_format(($item['price'] ?? 0) * ($item['quantity'] ?? 0), 2); ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            
                            <!-- Totales -->
                            <div class="mt-6 bg-gray-50 rounded-lg p-4">
                                <div class="space-y-2">
                                    <div class="flex justify-between text-sm">
                                        <span class="font-medium text-gray-700">Subtotal:</span>
                                        <span class="text-gray-900">$<?php echo number_format($orderDetails['subtotal'] ?? 0, 2); ?></span>
                                    </div>
                                    <div class="flex justify-between text-sm">
                                        <span class="font-medium text-gray-700">Envío:</span>
                                        <span class="text-gray-900">$<?php echo number_format($orderDetails['shipping_cost'] ?? $orderDetails['shipping_amount'] ?? 0, 2); ?></span>
                                    </div>
                                    <?php if (($orderDetails['discount'] ?? 0) > 0): ?>
                                    <div class="flex justify-between text-sm">
                                        <span class="font-medium text-gray-700">Descuento:</span>
                                        <span class="text-red-600">-$<?php echo number_format($orderDetails['discount'] ?? 0, 2); ?></span>
                                    </div>
                                    <?php endif; ?>
                                    <hr class="my-2 border-gray-200">
                                    <div class="flex justify-between text-base font-semibold">
                                        <span class="text-gray-900">Total:</span>
                                        <span class="text-gray-900">$<?php echo number_format($orderDetails['total'] ?? 0, 2); ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Actualizar Estado -->
                        <div class="bg-gray-50 rounded-lg p-4 mb-6">
                            <h4 class="text-lg font-semibold text-gray-900 mb-4">Actualizar Estado</h4>
                            <form method="POST" class="flex flex-col sm:flex-row gap-4 items-end">
                                <input type="hidden" name="action" value="update_status">
                                <input type="hidden" name="order_id" value="<?php echo $orderDetails['id']; ?>">
                                
                                <div class="flex-1">
                                    <label for="status" class="block text-sm font-medium text-gray-700 mb-2">Nuevo Estado</label>
                                    <select name="status" id="status" required class="block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                                        <option value="pending" <?php echo $orderDetails['status'] === 'pending' ? 'selected' : ''; ?>>Pendiente</option>
                                        <option value="processing" <?php echo $orderDetails['status'] === 'processing' ? 'selected' : ''; ?>>En proceso</option>
                                        <option value="shipped" <?php echo $orderDetails['status'] === 'shipped' ? 'selected' : ''; ?>>Enviado</option>
                                        <option value="delivered" <?php echo $orderDetails['status'] === 'delivered' ? 'selected' : ''; ?>>Entregado</option>
                                        <option value="cancelled" <?php echo $orderDetails['status'] === 'cancelled' ? 'selected' : ''; ?>>Cancelado</option>
                                    </select>
                                </div>
                                <button type="submit" class="bg-gradient-to-r from-primary-500 to-primary-600 text-white px-6 py-2 rounded-lg hover:from-primary-600 hover:to-primary-700 transition-all duration-200 flex items-center">
                                    <i class="fas fa-save mr-2"></i>
                                    Actualizar
                                </button>
                            </form>
                        </div>
                        
                        <!-- Back Link -->
                        <div class="flex justify-between items-center">
                            <a href="orders.php" class="bg-gray-500 text-white px-6 py-2 rounded-lg hover:bg-gray-600 transition-all duration-200 flex items-center">
                                <i class="fas fa-arrow-left mr-2"></i>
                                Volver a la lista de pedidos
                            </a>
                            
                            <div class="flex space-x-3">
                                <a href="invoice.php?order_id=<?php echo $orderDetails['id']; ?>" class="bg-blue-500 text-white px-6 py-2 rounded-lg hover:bg-blue-600 transition-all duration-200 flex items-center">
                                    <i class="fas fa-file-invoice mr-2"></i>
                                    Ver Factura
                                </a>
                                <a href="invoice.php?order_id=<?php echo $orderDetails['id']; ?>&download=pdf" class="bg-red-500 text-white px-6 py-2 rounded-lg hover:bg-red-600 transition-all duration-200 flex items-center">
                                    <i class="fas fa-file-pdf mr-2"></i>
                                    Descargar PDF
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                <?php else: ?>
                <!-- Lista de pedidos -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                    <!-- Table Header -->
                    <div class="px-4 lg:px-6 py-4 border-b border-gray-200 bg-gray-50">
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
                            <h3 class="text-lg font-semibold text-gray-900 mb-2 sm:mb-0">
                                <?php echo count($orders); ?> pedidos <?php echo $statusFilter ? "con estado: " . ucfirst($statusFilter) : "registrados"; ?>
                            </h3>
                        </div>
                    </div>

                    <!-- Desktop Table View -->
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Cliente</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Estado</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fecha</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($orders as $order): 
                                    // Obtener datos del cliente desde billing_address JSON
                                    $billingData = json_decode($order['billing_address'], true);
                                    $customerName = ($billingData['first_name'] ?? '') . ' ' . ($billingData['last_name'] ?? '');
                                    if (trim($customerName) === '') {
                                        $customerName = 'Cliente no especificado';
                                    }
                                ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">#<?php echo $order['id'] ?? ''; ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo htmlspecialchars($customerName); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">$<?php echo number_format($order['total'] ?? 0, 2); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 py-1 text-xs font-medium rounded-full <?php echo getStatusBadgeClass($order['status'] ?? 'pending'); ?>">
                                            <?php echo ucfirst($order['status'] ?? 'pending'); ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo ($order['created_at'] ?? '') ? date('d/m/Y H:i', strtotime($order['created_at'])) : 'No disponible'; ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <div class="flex space-x-2">
                                            <a href="?view=<?php echo $order['id']; ?>" class="bg-blue-100 text-blue-800 hover:bg-blue-200 px-3 py-1 rounded-lg transition-colors duration-200 text-xs font-medium inline-flex items-center">
                                                <i class="fas fa-eye mr-1"></i>
                                                Ver
                                            </a>
                                            <a href="invoice.php?order_id=<?php echo $order['id']; ?>" class="bg-green-100 text-green-800 hover:bg-green-200 px-3 py-1 rounded-lg transition-colors duration-200 text-xs font-medium inline-flex items-center">
                                                <i class="fas fa-file-invoice mr-1"></i>
                                                Factura
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                
                                <?php if (empty($orders)): ?>
                                <tr>
                                    <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                                        <div class="flex flex-col items-center">
                                            <i class="fas fa-inbox text-4xl mb-4 text-gray-300"></i>
                                            <p class="text-lg font-medium">No hay pedidos disponibles</p>
                                            <p class="text-sm">Los pedidos aparecerán aquí una vez que los clientes realicen compras.</p>
                                        </div>
                                    </td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php endif; ?>
            </main>
        </div>
    </div>
    
    <script src="../admin/assets/js/admin.js"></script>
    <script>
        function filterOrders(status) {
            window.location.href = 'orders.php' + (status ? '?status=' + status : '');
        }
    </script>
</body>
</html>

<?php
// Función para obtener la clase CSS según el estado del pedido
function getStatusBadgeClass($status) {
    switch ($status) {
        case 'pending':
            return 'bg-yellow-100 text-yellow-800';
        case 'processing':
            return 'bg-blue-100 text-blue-800';
        case 'shipped':
            return 'bg-purple-100 text-purple-800';
        case 'delivered':
            return 'bg-green-100 text-green-800';
        case 'cancelled':
            return 'bg-red-100 text-red-800';
        default:
            return 'bg-gray-100 text-gray-800';
    }
}
?>
