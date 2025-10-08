<?php
session_start();
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../includes/InventoryManager.php';

// Verificar autenticación del admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    // Auto-login para desarrollo
    $_SESSION['user_id'] = 1;
    $_SESSION['role'] = 'admin';
    $_SESSION['admin_id'] = 1;
    $_SESSION['admin_name'] = 'Administrador';
}

$database = new Database();
$db = $database->getConnection();

// Inicializar gestor de inventario
$inventory = new InventoryManager($db);

// Procesar acciones
if ($_POST['action'] ?? '' === 'update_status') {
    $order_id = intval($_POST['order_id'] ?? 0);
    $new_status = trim($_POST['status'] ?? '');
    $change_reason = trim($_POST['reason'] ?? '');
    $custom_reason = trim($_POST['custom_reason'] ?? '');
    $notes = trim($_POST['notes'] ?? '');
    
    // Usar razón personalizada si está seleccionada
    if ($change_reason === 'custom' && !empty($custom_reason)) {
        $change_reason = $custom_reason;
    }
    
    if ($order_id && $new_status) {
        try {
            // Iniciar transacción
            $db->beginTransaction();
            
            // Obtener el estado actual del pedido
            $current_query = "SELECT status FROM orders WHERE id = ?";
            $current_stmt = $db->prepare($current_query);
            $current_stmt->execute([$order_id]);
            $current_order = $current_stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($current_order) {
                $old_status = $current_order['status'];
                
                // Solo actualizar si el estado es diferente
                if ($old_status !== $new_status) {
                    // Actualizar el estado del pedido
                    $update_query = "UPDATE orders SET status = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?";
                    $update_stmt = $db->prepare($update_query);
                    $update_stmt->execute([$new_status, $order_id]);
                    
                    // Registrar en el historial de auditoría
                    $history_query = "INSERT INTO order_status_history 
                                     (order_id, old_status, new_status, changed_by_user_id, changed_by_name, 
                                      change_reason, notes, ip_address, user_agent, created_at) 
                                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP)";
                    
                    $admin_name = $_SESSION['admin_name'] ?? $_SESSION['username'] ?? 'Administrador';
                    $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
                    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
                    
                    $history_stmt = $db->prepare($history_query);
                    $history_stmt->execute([
                        $order_id, 
                        $old_status, 
                        $new_status,
                        $_SESSION['user_id'] ?? 1,
                        $admin_name,
                        $change_reason ?: 'Actualización manual desde panel admin',
                        $notes,
                        $ip_address,
                        $user_agent
                    ]);
                    
                    // ============= GESTIÓN AUTOMÁTICA DE INVENTARIO =============
                    try {
                        // Si el pedido se confirma, descontar del stock
                        if ($new_status === 'confirmed' && $old_status !== 'confirmed') {
                            $inventory->processSale($order_id);
                            error_log("INVENTORY: Stock descontado para pedido #$order_id");
                        }
                        
                        // Si se cancela un pedido que estaba confirmado, devolver stock
                        if ($new_status === 'cancelled' && $old_status === 'confirmed') {
                            $inventory->revertSale($order_id);
                            error_log("INVENTORY: Stock devuelto para pedido cancelado #$order_id");
                        }
                        
                    } catch (Exception $inv_error) {
                        // Si hay error en inventario, hacer rollback completo
                        $db->rollback();
                        throw new Exception("Error en gestión de inventario: " . $inv_error->getMessage());
                    }
                    // ============================================================
                    
                    // Confirmar transacción
                    $db->commit();
                    
                    $status_labels = [
                        'pending' => 'Pendiente',
                        'confirmed' => 'Confirmado',
                        'shipped' => 'Enviado',
                        'delivered' => 'Entregado',
                        'cancelled' => 'Cancelado'
                    ];
                    
                    $old_label = $status_labels[$old_status] ?? $old_status;
                    $new_label = $status_labels[$new_status] ?? $new_status;
                    
                    $success_message = "Estado del pedido #$order_id actualizado exitosamente de '$old_label' a '$new_label'";
                    
                    // Log adicional para auditoría
                    error_log("ORDER STATUS CHANGE - Order ID: $order_id, From: $old_status, To: $new_status, By: $admin_name, Reason: $change_reason, IP: $ip_address, Time: " . date('Y-m-d H:i:s'));
                    
                } else {
                    $db->rollback();
                    $status_labels = [
                        'pending' => 'Pendiente',
                        'confirmed' => 'Confirmado',
                        'shipped' => 'Enviado',
                        'delivered' => 'Entregado',
                        'cancelled' => 'Cancelado'
                    ];
                    $current_label = $status_labels[$new_status] ?? $new_status;
                    $error_message = "El pedido ya tiene el estado '$current_label'";
                }
            } else {
                $db->rollback();
                $error_message = "Pedido no encontrado";
            }
            
        } catch (Exception $e) {
            $db->rollback();
            $error_message = "Error al actualizar estado: " . $e->getMessage();
            error_log("ERROR updating order status: " . $e->getMessage());
        }
    } else {
        $error_message = "Datos incompletos para actualizar el estado";
    }
}

// Obtener filtros
$status_filter = $_GET['status'] ?? '';
$date_filter = $_GET['date'] ?? '';
$search = $_GET['search'] ?? '';

// Consulta detallada de pedidos
$query = "SELECT 
    o.*,
    (SELECT COUNT(*) FROM order_items oi WHERE oi.order_id = o.id) as total_items,
    (SELECT SUM(oi.quantity) FROM order_items oi WHERE oi.order_id = o.id) as total_quantity
FROM orders o WHERE 1=1";

$params = [];

if ($status_filter) {
    $query .= " AND o.status = ?";
    $params[] = $status_filter;
}

if ($date_filter) {
    $query .= " AND DATE(o.created_at) = ?";
    $params[] = $date_filter;
}

if ($search) {
    $query .= " AND (o.billing_address LIKE ? OR o.id = ?)";
    $params[] = '%' . $search . '%';
    $params[] = $search;
}

$query .= " ORDER BY o.created_at DESC";

$stmt = $db->prepare($query);
$stmt->execute($params);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Procesar datos de cada pedido
foreach ($orders as &$order) {
    $billing = json_decode($order['billing_address'], true);
    $shipping = json_decode($order['shipping_address'], true);
    
    // Información del cliente
    $order['customer_name'] = ($billing['first_name'] ?? 'N/A') . ' ' . ($billing['last_name'] ?? '');
    $order['customer_email'] = $billing['email'] ?? 'N/A';
    $order['customer_phone'] = $billing['phone'] ?? 'N/A';
    $order['customer_document'] = $billing['document'] ?? 'N/A';
    
    // Dirección de facturación
    $order['billing_full'] = ($billing['address'] ?? 'N/A') . ', ' . 
                            ($billing['city'] ?? 'N/A') . ', ' . 
                            ($billing['state'] ?? 'N/A');
    
    // Dirección de envío
    if ($shipping) {
        $order['shipping_full'] = ($shipping['address'] ?? 'N/A') . ', ' . 
                                 ($shipping['city'] ?? 'N/A') . ', ' . 
                                 ($shipping['state'] ?? 'N/A');
    } else {
        $order['shipping_full'] = 'Igual a facturación';
    }
    
    // Obtener detalles de productos
    $items_query = "SELECT oi.*, p.name as product_name, p.main_image 
                   FROM order_items oi 
                   LEFT JOIN products p ON oi.product_id = p.id 
                   WHERE oi.order_id = ?";
    $items_stmt = $db->prepare($items_query);
    $items_stmt->execute([$order['id']]);
    $order['items'] = $items_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Obtener historial de cambios de estado
    $history_query = "SELECT osh.*, 
                            CASE 
                                WHEN osh.old_status IS NULL THEN 'Pedido creado'
                                ELSE CONCAT('Cambio de \"', osh.old_status, '\" a \"', osh.new_status, '\"')
                            END as change_description
                     FROM order_status_history osh 
                     WHERE osh.order_id = ? 
                     ORDER BY osh.created_at DESC";
    $history_stmt = $db->prepare($history_query);
    $history_stmt->execute([$order['id']]);
    $order['status_history'] = $history_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Si no hay historial, crear una entrada inicial
    if (empty($order['status_history'])) {
        $initial_history = [
            [
                'id' => null,
                'old_status' => null,
                'new_status' => $order['status'],
                'changed_by_name' => 'Sistema',
                'change_reason' => 'Pedido creado',
                'notes' => '',
                'created_at' => $order['created_at'],
                'change_description' => 'Pedido creado con estado: ' . $order['status']
            ]
        ];
        $order['status_history'] = $initial_history;
    }
}
unset($order);

// Obtener estadísticas del dashboard
$stats_query = "SELECT 
    COUNT(*) as total_orders,
    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_orders,
    SUM(CASE WHEN status = 'confirmed' THEN 1 ELSE 0 END) as confirmed_orders,
    SUM(CASE WHEN status = 'shipped' THEN 1 ELSE 0 END) as shipped_orders,
    SUM(CASE WHEN status = 'delivered' THEN 1 ELSE 0 END) as delivered_orders,
    SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_orders,
    SUM(total) as total_revenue,
    SUM(CASE WHEN DATE(created_at) = CURDATE() THEN total ELSE 0 END) as today_revenue,
    SUM(CASE WHEN DATE(created_at) = CURDATE() THEN 1 ELSE 0 END) as today_orders,
    SUM(CASE WHEN DATE(created_at) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) THEN 1 ELSE 0 END) as week_orders,
    SUM(CASE WHEN DATE(created_at) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) THEN 1 ELSE 0 END) as month_orders
FROM orders";

$stats_stmt = $db->prepare($stats_query);
$stats_stmt->execute();
$stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);

// Calcular promedios
$stats['avg_order_value'] = $stats['total_orders'] > 0 ? $stats['total_revenue'] / $stats['total_orders'] : 0;
?>
<!DOCTYPE html>
<html lang="es" class="h-full bg-gray-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Pedidos - Admin Panel</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: {
                            400: '#b08d80',
                            500: '#9d7b6f',
                            600: '#8a695d'
                        },
                        secondary: {
                            400: '#c4a575',
                            500: '#b89660',
                            600: '#a6864b'
                        }
                    }
                }
            }
        }
    </script>
    <style>
        .status-pending { background: #fef3c7; color: #92400e; }
        .status-confirmed { background: #d1fae5; color: #065f46; }
        .status-shipped { background: #dbeafe; color: #1e40af; }
        .status-delivered { background: #dcfce7; color: #166534; }
        .status-cancelled { background: #fee2e2; color: #991b1b; }
    </style>
</head>
<body class="h-full">
    <div class="flex h-full">
        <!-- Sidebar -->
        <?php include 'includes/sidebar.php'; ?>
        
        <!-- Main Content -->
        <div class="flex-1 flex flex-col overflow-hidden md:ml-0">
            <!-- Header -->
            <?php include 'includes/header.php'; ?>
            
            <!-- Main Content Area -->
            <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-50 p-6">
                <!-- Page Header -->
                <div class="mb-8">
                    <div class="flex items-center justify-between">
                        <div>
                            <h1 class="text-3xl font-bold text-gray-900 flex items-center">
                                <i class="fas fa-shopping-cart mr-3 text-primary-500"></i>
                                Gestión de Pedidos
                            </h1>
                            <p class="mt-2 text-gray-600">Administra y supervisa todos los pedidos de la tienda</p>
                        </div>
                        
                        <?php if (isset($success_message)): ?>
                        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg mb-4 flex items-center">
                            <i class="fas fa-check-circle mr-2"></i><?php echo $success_message; ?>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (isset($error_message)): ?>
                        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg mb-4 flex items-center">
                            <i class="fas fa-exclamation-circle mr-2"></i><?php echo $error_message; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Dashboard Stats -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-4 mb-8">
                    <div class="bg-white rounded-xl shadow-md p-4 border-l-4 border-blue-500">
                        <div class="flex items-center">
                            <div class="p-2 rounded-lg bg-blue-100 text-blue-600">
                                <i class="fas fa-shopping-bag text-lg"></i>
                            </div>
                            <div class="ml-3">
                                <p class="text-xs text-gray-600 mb-1">Total Pedidos</p>
                                <p class="text-xl font-bold text-gray-900"><?php echo number_format($stats['total_orders']); ?></p>
                                <p class="text-xs text-gray-500">Histórico completo</p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-xl shadow-md p-4 border-l-4 border-yellow-500">
                        <div class="flex items-center">
                            <div class="p-2 rounded-lg bg-yellow-100 text-yellow-600">
                                <i class="fas fa-clock text-lg"></i>
                            </div>
                            <div class="ml-3">
                                <p class="text-xs text-gray-600 mb-1">Pendientes</p>
                                <p class="text-xl font-bold text-gray-900"><?php echo $stats['pending_orders']; ?></p>
                                <p class="text-xs text-red-500">Requieren atención</p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-xl shadow-md p-4 border-l-4 border-green-500">
                        <div class="flex items-center">
                            <div class="p-2 rounded-lg bg-green-100 text-green-600">
                                <i class="fas fa-check-circle text-lg"></i>
                            </div>
                            <div class="ml-3">
                                <p class="text-xs text-gray-600 mb-1">Confirmados</p>
                                <p class="text-xl font-bold text-gray-900"><?php echo $stats['confirmed_orders']; ?></p>
                                <p class="text-xs text-green-600">En proceso</p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-xl shadow-md p-4 border-l-4 border-purple-500">
                        <div class="flex items-center">
                            <div class="p-2 rounded-lg bg-purple-100 text-purple-600">
                                <i class="fas fa-dollar-sign text-lg"></i>
                            </div>
                            <div class="ml-3">
                                <p class="text-xs text-gray-600 mb-1">Ingresos Totales</p>
                                <p class="text-xl font-bold text-gray-900">$<?php echo number_format($stats['total_revenue'], 0); ?></p>
                                <p class="text-xs text-gray-500">Promedio: $<?php echo number_format($stats['avg_order_value'], 0); ?></p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-xl shadow-md p-4 border-l-4 border-orange-500">
                        <div class="flex items-center">
                            <div class="p-2 rounded-lg bg-orange-100 text-orange-600">
                                <i class="fas fa-calendar-day text-lg"></i>
                            </div>
                            <div class="ml-3">
                                <p class="text-xs text-gray-600 mb-1">Hoy</p>
                                <p class="text-xl font-bold text-gray-900"><?php echo $stats['today_orders']; ?></p>
                                <p class="text-xs text-gray-500">$<?php echo number_format($stats['today_revenue'], 0); ?></p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-xl shadow-md p-4 border-l-4 border-indigo-500">
                        <div class="flex items-center">
                            <div class="p-2 rounded-lg bg-indigo-100 text-indigo-600">
                                <i class="fas fa-calendar-week text-lg"></i>
                            </div>
                            <div class="ml-3">
                                <p class="text-xs text-gray-600 mb-1">Esta Semana</p>
                                <p class="text-xl font-bold text-gray-900"><?php echo $stats['week_orders']; ?></p>
                                <p class="text-xs text-gray-500">Últimos 7 días</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filters -->
                <div class="bg-white rounded-xl shadow-md p-4 mb-6">
                    <div class="flex flex-col lg:flex-row lg:items-center gap-4">
                        <h3 class="text-lg font-semibold text-gray-900 lg:mr-4">Filtros:</h3>
                        
                        <form method="GET" class="flex flex-col sm:flex-row flex-wrap items-start sm:items-center gap-3 flex-1">
                            <select name="status" class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:border-transparent min-w-[140px]">
                                <option value="">Todos los estados</option>
                                <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>🕐 Pendientes</option>
                                <option value="confirmed" <?php echo $status_filter === 'confirmed' ? 'selected' : ''; ?>>✅ Confirmados</option>
                                <option value="shipped" <?php echo $status_filter === 'shipped' ? 'selected' : ''; ?>>🚚 Enviados</option>
                                <option value="delivered" <?php echo $status_filter === 'delivered' ? 'selected' : ''; ?>>📦 Entregados</option>
                                <option value="cancelled" <?php echo $status_filter === 'cancelled' ? 'selected' : ''; ?>>❌ Cancelados</option>
                            </select>
                            
                            <input type="date" name="date" value="<?php echo $date_filter; ?>" 
                                   class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                            
                            <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Buscar por ID o cliente..." 
                                   class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:border-transparent min-w-[200px]">
                            
                            <div class="flex gap-2">
                                <button type="submit" class="bg-primary-500 hover:bg-primary-600 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors flex items-center">
                                    <i class="fas fa-search mr-1 text-xs"></i>Filtrar
                                </button>
                                
                                <a href="gestion-pedidos.php" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors flex items-center">
                                    <i class="fas fa-refresh mr-1 text-xs"></i>Limpiar
                                </a>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Detailed Orders Table -->
                <div class="bg-white rounded-xl shadow-lg">
                    <div class="p-6 border-b border-gray-200">
                        <h2 class="text-xl font-bold text-gray-800 flex items-center justify-between">
                            <span><i class="fas fa-list-ul mr-3 text-primary-500"></i>Lista Detallada de Pedidos</span>
                            <span class="text-sm text-gray-500"><?php echo count($orders); ?> pedido(s) encontrado(s)</span>
                        </h2>
                    </div>

                    <div class="overflow-x-auto">
                        <?php if (empty($orders)): ?>
                        <div class="p-12 text-center">
                            <i class="fas fa-inbox text-6xl text-gray-300 mb-4"></i>
                            <h3 class="text-xl font-semibold text-gray-500 mb-2">No hay pedidos</h3>
                            <p class="text-gray-400">Los pedidos aparecerán aquí cuando los clientes realicen compras</p>
                        </div>
                        <?php else: ?>
                        
                        <!-- Orders List -->
                        <div class="space-y-4 p-4">
                            <?php foreach ($orders as $order): ?>
                            <div class="border border-gray-200 rounded-lg hover:shadow-md transition-shadow">
                                <!-- Order Header -->
                                <div class="bg-gray-50 p-4 rounded-t-lg border-b border-gray-200">
                                    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-3">
                                        <div class="flex items-center space-x-3">
                                            <h3 class="text-lg font-bold text-gray-900">
                                                Pedido #<?php echo str_pad($order['id'], 4, '0', STR_PAD_LEFT); ?>
                                            </h3>
                                            <span class="px-3 py-1 text-xs font-semibold rounded-full status-<?php echo $order['status']; ?>">
                                                <?php 
                                                $status_labels = [
                                                    'pending' => '🕐 Pendiente',
                                                    'confirmed' => '✅ Confirmado', 
                                                    'shipped' => '🚚 Enviado',
                                                    'delivered' => '📦 Entregado',
                                                    'cancelled' => '❌ Cancelado'
                                                ];
                                                echo $status_labels[$order['status']] ?? $order['status'];
                                                ?>
                                            </span>
                                        </div>
                                        
                                        <div class="flex flex-wrap gap-2">
                                            <button onclick="toggleOrderDetails(<?php echo $order['id']; ?>)" 
                                                    class="bg-blue-100 hover:bg-blue-200 text-blue-700 px-3 py-2 rounded-lg text-sm transition-colors flex items-center">
                                                <i class="fas fa-eye mr-1"></i>Ver Detalles
                                            </button>
                                            <button onclick="changeStatus(<?php echo $order['id']; ?>, '<?php echo $order['status']; ?>')" 
                                                    class="bg-green-100 hover:bg-green-200 text-green-700 px-3 py-2 rounded-lg text-sm transition-colors flex items-center">
                                                <i class="fas fa-edit mr-1"></i>Cambiar Estado
                                            </button>
                                        </div>
                                    </div>
                                    
                                    <!-- Order Summary -->
                                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 mt-4 text-sm">
                                        <div>
                                            <span class="text-gray-500 block">Cliente:</span>
                                            <p class="font-medium"><?php echo htmlspecialchars($order['customer_name']); ?></p>
                                        </div>
                                        <div>
                                            <span class="text-gray-500 block">Total:</span>
                                            <p class="font-bold text-lg text-green-600">$<?php echo number_format($order['total'], 0); ?></p>
                                        </div>
                                        <div>
                                            <span class="text-gray-500 block">Productos:</span>
                                            <p class="font-medium"><?php echo $order['total_items']; ?> tipo(s), <?php echo $order['total_quantity']; ?> unidades</p>
                                        </div>
                                        <div>
                                            <span class="text-gray-500 block">Fecha:</span>
                                            <p class="font-medium"><?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?></p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Order Details (Hidden by default) -->
                                <div id="order-details-<?php echo $order['id']; ?>" class="hidden p-4">
                                    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
                                        <!-- Customer Information -->
                                        <div class="space-y-4">
                                            <div>
                                                <h4 class="text-base font-semibold text-gray-900 mb-3 flex items-center">
                                                    <i class="fas fa-user mr-2 text-blue-500"></i>Información del Cliente
                                                </h4>
                                                <div class="bg-gray-50 p-3 rounded-lg space-y-2 text-sm">
                                                    <div class="flex justify-between">
                                                        <span class="text-gray-600">Nombre:</span>
                                                        <span class="font-medium"><?php echo htmlspecialchars($order['customer_name']); ?></span>
                                                    </div>
                                                    <div class="flex justify-between">
                                                        <span class="text-gray-600">Email:</span>
                                                        <span class="font-medium"><?php echo htmlspecialchars($order['customer_email']); ?></span>
                                                    </div>
                                                    <div class="flex justify-between">
                                                        <span class="text-gray-600">Teléfono:</span>
                                                        <span class="font-medium"><?php echo htmlspecialchars($order['customer_phone']); ?></span>
                                                    </div>
                                                    <div class="flex justify-between">
                                                        <span class="text-gray-600">Documento:</span>
                                                        <span class="font-medium"><?php echo htmlspecialchars($order['customer_document']); ?></span>
                                                    </div>
                                                </div>
                                            </div>

                                            <div>
                                                <h4 class="text-base font-semibold text-gray-900 mb-3 flex items-center">
                                                    <i class="fas fa-map-marker-alt mr-2 text-red-500"></i>Direcciones
                                                </h4>
                                                <div class="space-y-3">
                                                    <div class="bg-gray-50 p-3 rounded-lg">
                                                        <h5 class="font-medium text-gray-800 mb-1 text-sm">Dirección de Facturación:</h5>
                                                        <p class="text-gray-600 text-sm"><?php echo htmlspecialchars($order['billing_full']); ?></p>
                                                    </div>
                                                    <div class="bg-gray-50 p-3 rounded-lg">
                                                        <h5 class="font-medium text-gray-800 mb-1 text-sm">Dirección de Envío:</h5>
                                                        <p class="text-gray-600 text-sm"><?php echo htmlspecialchars($order['shipping_full']); ?></p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Order Items -->
                                        <div>
                                            <h4 class="text-base font-semibold text-gray-900 mb-3 flex items-center">
                                                <i class="fas fa-shopping-bag mr-2 text-green-500"></i>Productos Ordenados
                                            </h4>
                                            <div class="space-y-3 max-h-96 overflow-y-auto">
                                                <?php foreach ($order['items'] as $item): 
                                                    // Construir la ruta correcta de la imagen
                                                    $image_url = '';
                                                    if (!empty($item['main_image'])) {
                                                        if (strpos($item['main_image'], 'uploads/') === 0) {
                                                            // Imagen en uploads/ - ruta desde admin
                                                            $image_url = '../' . $item['main_image'];
                                                        } elseif (strpos($item['main_image'], 'assets/') === 0) {
                                                            // Imagen en assets/ - ruta desde admin
                                                            $image_url = '../' . $item['main_image'];
                                                        } else {
                                                            // Ruta completa o relativa
                                                            $image_url = $item['main_image'];
                                                        }
                                                    }
                                                ?>
                                                <div class="bg-gray-50 p-3 rounded-lg flex items-start space-x-3">
                                                    <div class="flex-shrink-0">
                                                        <?php if (!empty($image_url) && file_exists(__DIR__ . '/../' . $item['main_image'])): ?>
                                                        <img src="<?php echo htmlspecialchars($image_url); ?>" 
                                                             alt="<?php echo htmlspecialchars($item['product_name'] ?? 'Producto'); ?>" 
                                                             class="w-12 h-12 object-cover rounded-lg border border-gray-200"
                                                             onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                                        <div class="w-12 h-12 bg-gray-200 rounded-lg flex items-center justify-center border border-gray-200" style="display: none;">
                                                            <i class="fas fa-image text-gray-400 text-sm"></i>
                                                        </div>
                                                        <?php else: ?>
                                                        <div class="w-12 h-12 bg-gray-200 rounded-lg flex items-center justify-center border border-gray-200">
                                                            <i class="fas fa-image text-gray-400 text-sm"></i>
                                                        </div>
                                                        <?php endif; ?>
                                                    </div>
                                                    
                                                    <div class="flex-1 min-w-0">
                                                        <h5 class="font-medium text-gray-900 text-sm truncate"><?php echo htmlspecialchars($item['product_name'] ?? 'Producto #' . $item['product_id']); ?></h5>
                                                        <div class="text-xs text-gray-600 space-y-1 mt-1">
                                                            <p>Cantidad: <span class="font-medium"><?php echo $item['quantity']; ?></span></p>
                                                            <p>Precio: <span class="font-medium">$<?php echo number_format($item['price'], 0); ?></span></p>
                                                            <p>Subtotal: <span class="font-bold text-green-600">$<?php echo number_format($item['price'] * $item['quantity'], 0); ?></span></p>
                                                        </div>
                                                    </div>
                                                </div>
                                                <?php endforeach; ?>
                                            </div>
                                            
                                            <!-- Order Totals -->
                                            <div class="mt-4 bg-gray-100 p-3 rounded-lg">
                                                <div class="flex justify-between items-center">
                                                    <span class="font-semibold text-base">Total del Pedido:</span>
                                                    <span class="font-bold text-xl text-green-600">$<?php echo number_format($order['total'], 0); ?></span>
                                                </div>
                                                <?php if (!empty($order['payment_method'])): ?>
                                                <div class="flex justify-between items-center text-xs text-gray-600 mt-2">
                                                    <span>Método de pago:</span>
                                                    <span><?php echo htmlspecialchars($order['payment_method']); ?></span>
                                                </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>

                                        <!-- Order Status History (Audit Trail) -->
                                        <div>
                                            <h4 class="text-base font-semibold text-gray-900 mb-3 flex items-center">
                                                <i class="fas fa-history mr-2 text-purple-500"></i>Historial de Estados
                                                <span class="ml-2 px-2 py-1 bg-purple-100 text-purple-700 text-xs rounded-full">
                                                    <?php echo count($order['status_history']); ?> cambios
                                                </span>
                                            </h4>
                                            <div class="space-y-2 max-h-96 overflow-y-auto">
                                                <?php foreach ($order['status_history'] as $history): ?>
                                                <div class="bg-white border border-gray-200 rounded-lg p-3 text-sm">
                                                    <div class="flex items-start justify-between mb-2">
                                                        <div class="flex-1">
                                                            <p class="font-medium text-gray-900 mb-1">
                                                                <?php echo htmlspecialchars($history['change_description']); ?>
                                                            </p>
                                                            <div class="text-xs text-gray-600 space-y-1">
                                                                <p><i class="fas fa-user mr-1"></i>Por: <?php echo htmlspecialchars($history['changed_by_name']); ?></p>
                                                                <p><i class="fas fa-clock mr-1"></i><?php echo date('d/m/Y H:i:s', strtotime($history['created_at'])); ?></p>
                                                                <?php if (!empty($history['change_reason'])): ?>
                                                                <p><i class="fas fa-info-circle mr-1"></i>Razón: <?php echo htmlspecialchars($history['change_reason']); ?></p>
                                                                <?php endif; ?>
                                                                <?php if (!empty($history['notes'])): ?>
                                                                <p><i class="fas fa-sticky-note mr-1"></i>Notas: <?php echo htmlspecialchars($history['notes']); ?></p>
                                                                <?php endif; ?>
                                                            </div>
                                                        </div>
                                                        <div class="ml-2">
                                                            <?php if ($history['new_status']): ?>
                                                            <span class="px-2 py-1 text-xs rounded-full status-<?php echo $history['new_status']; ?>">
                                                                <?php 
                                                                $status_labels = [
                                                                    'pending' => '🕐 Pendiente',
                                                                    'confirmed' => '✅ Confirmado', 
                                                                    'shipped' => '🚚 Enviado',
                                                                    'delivered' => '📦 Entregado',
                                                                    'cancelled' => '❌ Cancelado'
                                                                ];
                                                                echo $status_labels[$history['new_status']] ?? $history['new_status'];
                                                                ?>
                                                            </span>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                </div>
                                                <?php endforeach; ?>
                                            </div>
                                            
                                            <!-- Quick Actions -->
                                            <div class="mt-4 bg-blue-50 p-3 rounded-lg">
                                                <h5 class="font-medium text-blue-800 mb-2 text-sm">Acciones Rápidas:</h5>
                                                <div class="flex flex-wrap gap-2">
                                                    <button onclick="changeStatusWithReason(<?php echo $order['id']; ?>, 'confirmed', 'Pedido revisado y confirmado')" 
                                                            class="text-xs bg-green-100 hover:bg-green-200 text-green-700 px-2 py-1 rounded">
                                                        ✅ Confirmar
                                                    </button>
                                                    <button onclick="changeStatusWithReason(<?php echo $order['id']; ?>, 'shipped', 'Pedido enviado al cliente')" 
                                                            class="text-xs bg-blue-100 hover:bg-blue-200 text-blue-700 px-2 py-1 rounded">
                                                        🚚 Marcar Enviado
                                                    </button>
                                                    <button onclick="changeStatusWithReason(<?php echo $order['id']; ?>, 'delivered', 'Pedido entregado exitosamente')" 
                                                            class="text-xs bg-purple-100 hover:bg-purple-200 text-purple-700 px-2 py-1 rounded">
                                                        📦 Marcar Entregado
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Status Change Modal -->
    <div id="statusModal" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center z-50">
        <div class="bg-white rounded-xl p-6 w-full max-w-lg mx-4 shadow-2xl max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-xl font-bold text-gray-800 flex items-center">
                    <i class="fas fa-edit mr-2 text-primary-500"></i>
                    Cambiar Estado del Pedido
                </h3>
                <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600 transition-colors">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            
            <form id="statusForm" method="POST" class="space-y-4">
                <input type="hidden" name="action" value="update_status">
                <input type="hidden" name="order_id" id="modal_order_id">
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Pedido:</label>
                    <div id="modal_order_info" class="text-lg font-semibold text-gray-900 mb-4"></div>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Estado Actual:</label>
                    <div id="modal_current_status" class="text-sm text-gray-600 mb-4"></div>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Nuevo Estado: <span class="text-red-500">*</span></label>
                    <select name="status" id="modal_status" required class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                        <option value="pending">🕐 Pendiente</option>
                        <option value="confirmed">✅ Confirmado</option>
                        <option value="shipped">🚚 Enviado</option>
                        <option value="delivered">📦 Entregado</option>
                        <option value="cancelled">❌ Cancelado</option>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Razón del Cambio:</label>
                    <select name="reason" id="modal_reason" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent mb-2">
                        <option value="">Seleccionar razón predefinida</option>
                        <option value="Pedido revisado y aprobado">Pedido revisado y aprobado</option>
                        <option value="Productos preparados para envío">Productos preparados para envío</option>
                        <option value="Enviado por courier/transportadora">Enviado por courier/transportadora</option>
                        <option value="Cliente confirmó recepción">Cliente confirmó recepción</option>
                        <option value="Cancelado por falta de stock">Cancelado por falta de stock</option>
                        <option value="Cancelado por solicitud del cliente">Cancelado por solicitud del cliente</option>
                        <option value="Cambio manual del administrador">Cambio manual del administrador</option>
                        <option value="custom">Otra razón (escribir abajo)</option>
                    </select>
                    <input type="text" name="custom_reason" id="modal_custom_reason" placeholder="Escriba la razón personalizada..." 
                           class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent hidden">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Notas Adicionales:</label>
                    <textarea name="notes" id="modal_notes" rows="3" placeholder="Notas opcionales sobre el cambio de estado..."
                              class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent"></textarea>
                </div>
                
                <div class="bg-gray-50 p-4 rounded-lg">
                    <h4 class="font-medium text-gray-800 mb-2 text-sm">Estados disponibles:</h4>
                    <ul class="text-xs text-gray-600 space-y-1">
                        <li>• <strong>🕐 Pendiente:</strong> Esperando confirmación y revisión</li>
                        <li>• <strong>✅ Confirmado:</strong> Pedido aprobado y en preparación</li>
                        <li>• <strong>🚚 Enviado:</strong> Pedido en camino al cliente</li>
                        <li>• <strong>📦 Entregado:</strong> Pedido completado exitosamente</li>
                        <li>• <strong>❌ Cancelado:</strong> Pedido cancelado por cualquier motivo</li>
                    </ul>
                </div>
                
                <div class="bg-blue-50 p-4 rounded-lg">
                    <div class="flex items-center text-blue-800">
                        <i class="fas fa-info-circle mr-2"></i>
                        <p class="text-sm"><strong>Importante:</strong> Este cambio quedará registrado en el historial de auditoría con fecha, hora, usuario y razón del cambio.</p>
                    </div>
                </div>
                
                <div class="flex space-x-3 pt-4">
                    <button type="submit" class="flex-1 bg-primary-500 hover:bg-primary-600 text-white py-3 px-4 rounded-lg font-medium transition-colors flex items-center justify-center">
                        <i class="fas fa-check mr-2"></i>Actualizar Estado
                    </button>
                    <button type="button" onclick="closeModal()" class="flex-1 bg-gray-300 hover:bg-gray-400 text-gray-700 py-3 px-4 rounded-lg font-medium transition-colors">
                        Cancelar
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Toggle order details
        function toggleOrderDetails(orderId) {
            const details = document.getElementById('order-details-' + orderId);
            if (details.classList.contains('hidden')) {
                details.classList.remove('hidden');
                details.classList.add('animate-fade-in');
            } else {
                details.classList.add('hidden');
                details.classList.remove('animate-fade-in');
            }
        }

        // Change order status
        function changeStatus(orderId, currentStatus) {
            document.getElementById('modal_order_id').value = orderId;
            document.getElementById('modal_status').value = currentStatus;
            document.getElementById('modal_order_info').textContent = 'Pedido #' + String(orderId).padStart(4, '0');
            
            // Show current status
            const statusLabels = {
                'pending': '🕐 Pendiente',
                'confirmed': '✅ Confirmado',
                'shipped': '🚚 Enviado',
                'delivered': '📦 Entregado',
                'cancelled': '❌ Cancelado'
            };
            document.getElementById('modal_current_status').textContent = statusLabels[currentStatus] || currentStatus;
            
            // Reset form
            document.getElementById('modal_reason').value = '';
            document.getElementById('modal_notes').value = '';
            document.getElementById('modal_custom_reason').classList.add('hidden');
            
            document.getElementById('statusModal').classList.remove('hidden');
        }

        // Quick status change with predefined reason
        function changeStatusWithReason(orderId, newStatus, reason) {
            document.getElementById('modal_order_id').value = orderId;
            document.getElementById('modal_status').value = newStatus;
            document.getElementById('modal_reason').value = reason;
            document.getElementById('modal_notes').value = 'Cambio rápido desde acciones predefinidas';
            
            // Auto-submit the form
            document.getElementById('statusForm').dispatchEvent(new Event('submit'));
        }

        // Close modal
        function closeModal() {
            document.getElementById('statusModal').classList.add('hidden');
        }

        // Handle reason dropdown change
        document.getElementById('modal_reason').addEventListener('change', function() {
            const customReasonInput = document.getElementById('modal_custom_reason');
            if (this.value === 'custom') {
                customReasonInput.classList.remove('hidden');
                customReasonInput.focus();
            } else {
                customReasonInput.classList.add('hidden');
                customReasonInput.value = '';
            }
        });

        // Close modal when clicking outside
        document.getElementById('statusModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal();
            }
        });

        // Status form submission
        document.getElementById('statusForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            
            // Handle custom reason
            const reasonSelect = document.getElementById('modal_reason');
            const customReason = document.getElementById('modal_custom_reason');
            
            if (reasonSelect.value === 'custom' && customReason.value.trim()) {
                formData.set('reason', customReason.value.trim());
            } else if (reasonSelect.value === 'custom' && !customReason.value.trim()) {
                alert('Por favor, escriba una razón personalizada o seleccione una predefinida.');
                return;
            }
            
            const orderId = formData.get('order_id');
            const status = formData.get('status');
            
            // Show loading state
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Actualizando...';
            submitBtn.disabled = true;
            
            // Submit form
            fetch(window.location.href, {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(data => {
                // Show success message briefly
                const tempDiv = document.createElement('div');
                tempDiv.innerHTML = data;
                const successMessage = tempDiv.querySelector('.bg-green-100');
                const errorMessage = tempDiv.querySelector('.bg-red-100');
                
                if (successMessage) {
                    // Show success notification
                    showNotification('Estado actualizado correctamente', 'success');
                    // Reload page after short delay
                    setTimeout(() => window.location.reload(), 1500);
                } else if (errorMessage) {
                    // Show error message
                    const errorText = errorMessage.textContent.replace(/Error:?\s*/, '');
                    showNotification(errorText, 'error');
                    submitBtn.innerHTML = originalText;
                    submitBtn.disabled = false;
                } else {
                    // Fallback: just reload
                    window.location.reload();
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('Error al actualizar el estado del pedido', 'error');
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            });
        });

        // Show notification function
        function showNotification(message, type = 'info') {
            const notification = document.createElement('div');
            notification.className = `fixed top-4 right-4 z-50 px-6 py-4 rounded-lg shadow-lg transition-all duration-300 transform translate-x-full`;
            
            if (type === 'success') {
                notification.className += ' bg-green-100 border border-green-400 text-green-700';
                notification.innerHTML = '<i class="fas fa-check-circle mr-2"></i>' + message;
            } else if (type === 'error') {
                notification.className += ' bg-red-100 border border-red-400 text-red-700';
                notification.innerHTML = '<i class="fas fa-exclamation-circle mr-2"></i>' + message;
            } else {
                notification.className += ' bg-blue-100 border border-blue-400 text-blue-700';
                notification.innerHTML = '<i class="fas fa-info-circle mr-2"></i>' + message;
            }
            
            document.body.appendChild(notification);
            
            // Animate in
            setTimeout(() => {
                notification.classList.remove('translate-x-full');
            }, 100);
            
            // Animate out after delay
            setTimeout(() => {
                notification.classList.add('translate-x-full');
                setTimeout(() => {
                    if (notification.parentNode) {
                        notification.parentNode.removeChild(notification);
                    }
                }, 300);
            }, 4000);
        }

        // Auto-refresh every 2 minutes to show new orders (only if no modal is open)
        setInterval(function() {
            if (document.getElementById('statusModal').classList.contains('hidden')) {
                // Check for new orders without full page reload
                fetch(window.location.href + '?ajax=1')
                .then(response => response.json())
                .then(data => {
                    if (data.newOrdersCount > 0) {
                        showNotification(`${data.newOrdersCount} nuevo(s) pedido(s) recibido(s)`, 'info');
                    }
                })
                .catch(() => {}); // Ignore errors for background checks
            }
        }, 120000); // 2 minutes

        // Add keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            // ESC key to close modal
            if (e.key === 'Escape' && !document.getElementById('statusModal').classList.contains('hidden')) {
                closeModal();
            }
        });

        // Add fade-in animation styles
        const style = document.createElement('style');
        style.textContent = `
            .animate-fade-in {
                animation: fadeIn 0.3s ease-in-out;
            }
            @keyframes fadeIn {
                from { opacity: 0; transform: translateY(-10px); }
                to { opacity: 1; transform: translateY(0); }
            }
        `;
        document.head.appendChild(style);

        // Initialize tooltips and other UI enhancements on page load
        document.addEventListener('DOMContentLoaded', function() {
            // Add loading states to buttons
            const buttons = document.querySelectorAll('button');
            buttons.forEach(button => {
                button.addEventListener('click', function(e) {
                    if (this.type !== 'submit') {
                        // Add subtle loading effect
                        this.style.transform = 'scale(0.95)';
                        setTimeout(() => {
                            this.style.transform = '';
                        }, 150);
                    }
                });
            });
        });
    </script>
</body>
</html>
