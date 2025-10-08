<?php
// Configuración de sesión segura
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 0); // Cambiar a 1 en HTTPS
ini_set('session.cookie_samesite', 'Strict');

session_start();

require_once '../config/database.php';

// Verificar que el usuario sea administrador
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php?redirect=admin/pedidos.php');
    exit;
}

// Conectar a la base de datos
$database = new Database();
$db = $database->getConnection();

// Obtener filtros
$status_filter = $_GET['status'] ?? '';
$date_filter = $_GET['date'] ?? '';
$search = $_GET['search'] ?? '';

// Construir consulta simplificada
$query = "SELECT o.*, 
                 (SELECT COUNT(*) FROM order_items oi WHERE oi.order_id = o.id) as item_count
          FROM orders o 
          WHERE 1=1";

$params = [];

if ($status_filter) {
    $query .= " AND o.status = ?";
    $params[] = $status_filter;
}

if ($date_filter) {
    $query .= " AND DATE(o.created_at) = ?";
    $params[] = $date_filter;
}

// Para búsqueda, usaremos LIKE en los campos JSON como texto
if ($search) {
    $query .= " AND (o.billing_address LIKE ? OR o.id = ?)";
    $searchParam = '%' . $search . '%';
    $params = array_merge($params, [$searchParam, $search]);
}

$query .= " ORDER BY o.created_at DESC";

$stmt = $db->prepare($query);
$stmt->execute($params);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Procesar datos JSON para cada pedido
foreach ($orders as &$order) {
    $billingData = json_decode($order['billing_address'], true);
    $shippingData = json_decode($order['shipping_address'], true);
    
    // Extraer datos del cliente
    if ($billingData) {
        $order['customer_name'] = ($billingData['first_name'] ?? '') . ' ' . ($billingData['last_name'] ?? '');
        $order['customer_phone'] = $billingData['phone'] ?? 'N/A';
        $order['customer_email'] = $billingData['email'] ?? 'N/A';
    } else {
        $order['customer_name'] = 'N/A';
        $order['customer_phone'] = 'N/A';
        $order['customer_email'] = 'N/A';
    }
    
    // Extraer ciudad de envío
    if ($shippingData) {
        $order['shipping_city'] = $shippingData['city'] ?? ($billingData['city'] ?? 'N/A');
    } else {
        $order['shipping_city'] = $billingData['city'] ?? 'N/A';
    }
}
unset($order); // Romper la referencia

// Obtener estadísticas rápidas
$stats_query = "SELECT 
                    COUNT(*) as total_orders,
                    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_orders,
                    SUM(CASE WHEN status = 'confirmed' THEN 1 ELSE 0 END) as confirmed_orders,
                    SUM(CASE WHEN status = 'shipped' THEN 1 ELSE 0 END) as shipped_orders,
                    SUM(CASE WHEN status = 'delivered' THEN 1 ELSE 0 END) as delivered_orders,
                    SUM(CASE WHEN DATE(created_at) = CURDATE() THEN total ELSE 0 END) as today_sales
                FROM orders";
$stats_stmt = $db->prepare($stats_query);
$stats_stmt->execute();
$stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Pedidos - Admin Odisea Makeup</title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: {
                            50: '#fdf8f6',
                            100: '#f2e8e5',
                            200: '#eaddd7',
                            300: '#e0cec7',
                            400: '#d2bab0',
                            500: '#b08d80',
                            600: '#a07870',
                            700: '#8b6456',
                            800: '#6d4c41',
                            900: '#4e342e'
                        }
                    }
                }
            }
        }
    </script>
</head>

<body class="bg-gray-50 font-['Inter']">
    <!-- Header -->
    <header class="bg-white shadow-sm border-b">
        <div class="container mx-auto px-4 py-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <h1 class="text-2xl font-bold text-gray-800">Gestión de Pedidos</h1>
                    <span class="bg-primary-100 text-primary-800 px-3 py-1 rounded-full text-sm">Admin</span>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="../admin.php" class="text-gray-600 hover:text-primary-600">
                        <i class="fas fa-arrow-left mr-2"></i>Panel Admin
                    </a>
                    <a href="../index.php" class="text-gray-600 hover:text-primary-600">
                        <i class="fas fa-home mr-2"></i>Sitio Web
                    </a>
                </div>
            </div>
        </div>
    </header>

    <!-- Estadísticas -->
    <div class="container mx-auto px-4 py-6">
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4 mb-8">
            <div class="bg-white rounded-xl p-4 shadow-sm border">
                <div class="flex items-center">
                    <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                        <i class="fas fa-shopping-cart text-blue-600"></i>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-gray-600">Total Pedidos</p>
                        <p class="text-xl font-bold text-gray-800"><?php echo $stats['total_orders']; ?></p>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-xl p-4 shadow-sm border">
                <div class="flex items-center">
                    <div class="w-12 h-12 bg-yellow-100 rounded-lg flex items-center justify-center">
                        <i class="fas fa-clock text-yellow-600"></i>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-gray-600">Pendientes</p>
                        <p class="text-xl font-bold text-gray-800"><?php echo $stats['pending_orders']; ?></p>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-xl p-4 shadow-sm border">
                <div class="flex items-center">
                    <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                        <i class="fas fa-check-circle text-green-600"></i>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-gray-600">Confirmados</p>
                        <p class="text-xl font-bold text-gray-800"><?php echo $stats['confirmed_orders']; ?></p>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-xl p-4 shadow-sm border">
                <div class="flex items-center">
                    <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center">
                        <i class="fas fa-truck text-purple-600"></i>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-gray-600">Enviados</p>
                        <p class="text-xl font-bold text-gray-800"><?php echo $stats['shipped_orders']; ?></p>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-xl p-4 shadow-sm border">
                <div class="flex items-center">
                    <div class="w-12 h-12 bg-indigo-100 rounded-lg flex items-center justify-center">
                        <i class="fas fa-box text-indigo-600"></i>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-gray-600">Entregados</p>
                        <p class="text-xl font-bold text-gray-800"><?php echo $stats['delivered_orders']; ?></p>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-xl p-4 shadow-sm border">
                <div class="flex items-center">
                    <div class="w-12 h-12 bg-primary-100 rounded-lg flex items-center justify-center">
                        <i class="fas fa-dollar-sign text-primary-600"></i>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-gray-600">Ventas Hoy</p>
                        <p class="text-lg font-bold text-gray-800">$<?php echo number_format($stats['today_sales'] ?? 0, 0, ',', '.'); ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filtros -->
        <div class="bg-white rounded-xl shadow-sm border p-6 mb-8">
            <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Buscar</label>
                    <input type="text" 
                           name="search" 
                           value="<?php echo htmlspecialchars($search); ?>"
                           placeholder="Nombre, teléfono o ID"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Estado</label>
                    <select name="status" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                        <option value="">Todos los estados</option>
                        <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pendiente</option>
                        <option value="confirmed" <?php echo $status_filter === 'confirmed' ? 'selected' : ''; ?>>Confirmado</option>
                        <option value="shipped" <?php echo $status_filter === 'shipped' ? 'selected' : ''; ?>>Enviado</option>
                        <option value="delivered" <?php echo $status_filter === 'delivered' ? 'selected' : ''; ?>>Entregado</option>
                        <option value="cancelled" <?php echo $status_filter === 'cancelled' ? 'selected' : ''; ?>>Cancelado</option>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Fecha</label>
                    <input type="date" 
                           name="date" 
                           value="<?php echo htmlspecialchars($date_filter); ?>"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                </div>
                
                <div class="flex items-end space-x-2">
                    <button type="submit" class="flex-1 bg-primary-500 text-white px-4 py-2 rounded-lg hover:bg-primary-600 transition-colors">
                        <i class="fas fa-search mr-2"></i>Filtrar
                    </button>
                    <a href="pedidos.php" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors">
                        <i class="fas fa-times"></i>
                    </a>
                </div>
            </form>
        </div>

        <!-- Lista de pedidos -->
        <div class="bg-white rounded-xl shadow-sm border overflow-hidden">
            <div class="p-6 border-b">
                <h2 class="text-xl font-semibold text-gray-800">Pedidos Recientes</h2>
            </div>
            
            <?php if (empty($orders)): ?>
            <div class="p-8 text-center">
                <i class="fas fa-shopping-cart text-gray-400 text-4xl mb-4"></i>
                <p class="text-gray-600">No se encontraron pedidos con los filtros aplicados.</p>
            </div>
            <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Pedido</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Cliente</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ubicación</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Items</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Estado</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fecha</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($orders as $order): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="font-medium text-gray-900">#<?php echo str_pad($order['id'], 6, '0', STR_PAD_LEFT); ?></div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($order['customer_name']); ?></div>
                                <div class="text-sm text-gray-500"><?php echo htmlspecialchars($order['customer_phone']); ?></div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm text-gray-900"><?php echo htmlspecialchars($order['shipping_city']); ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 py-1 text-xs font-medium bg-gray-100 text-gray-800 rounded-full">
                                    <?php echo $order['item_count']; ?> items
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900">$<?php echo number_format($order['total'], 0, ',', '.'); ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <?php
                                $statusClasses = [
                                    'pending' => 'bg-yellow-100 text-yellow-800',
                                    'confirmed' => 'bg-blue-100 text-blue-800',
                                    'shipped' => 'bg-purple-100 text-purple-800',
                                    'delivered' => 'bg-green-100 text-green-800',
                                    'cancelled' => 'bg-red-100 text-red-800'
                                ];
                                $statusLabels = [
                                    'pending' => 'Pendiente',
                                    'confirmed' => 'Confirmado',
                                    'shipped' => 'Enviado',
                                    'delivered' => 'Entregado',
                                    'cancelled' => 'Cancelado'
                                ];
                                ?>
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $statusClasses[$order['status']] ?? 'bg-gray-100 text-gray-800'; ?>">
                                    <?php echo $statusLabels[$order['status']] ?? ucfirst($order['status']); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <div class="flex space-x-2">
                                    <button onclick="viewOrder(<?php echo $order['id']; ?>)" 
                                            class="text-primary-600 hover:text-primary-900">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button onclick="editOrderStatus(<?php echo $order['id']; ?>, '<?php echo $order['status']; ?>')" 
                                            class="text-blue-600 hover:text-blue-900">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button onclick="generateWhatsApp(<?php echo $order['id']; ?>)" 
                                            class="text-green-600 hover:text-green-900">
                                        <i class="fab fa-whatsapp"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Modal para ver detalles del pedido -->
    <div id="orderModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
        <div class="bg-white rounded-xl max-w-4xl w-full mx-4 max-h-[90vh] overflow-y-auto">
            <div class="p-6 border-b">
                <div class="flex justify-between items-center">
                    <h3 class="text-xl font-semibold text-gray-800">Detalles del Pedido</h3>
                    <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
            </div>
            <div id="orderDetails" class="p-6">
                <!-- Los detalles se cargarán aquí -->
            </div>
        </div>
    </div>

    <!-- Modal para cambiar estado -->
    <div id="statusModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
        <div class="bg-white rounded-xl max-w-md w-full mx-4">
            <div class="p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Cambiar Estado del Pedido</h3>
                <form id="statusForm">
                    <input type="hidden" id="orderIdStatus" name="order_id">
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Nuevo Estado</label>
                        <select id="newStatus" name="status" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                            <option value="pending">Pendiente</option>
                            <option value="confirmed">Confirmado</option>
                            <option value="shipped">Enviado</option>
                            <option value="delivered">Entregado</option>
                            <option value="cancelled">Cancelado</option>
                        </select>
                    </div>
                    <div class="flex space-x-4">
                        <button type="submit" class="flex-1 bg-primary-500 text-white px-4 py-2 rounded-lg hover:bg-primary-600 transition-colors">
                            Actualizar
                        </button>
                        <button type="button" onclick="closeStatusModal()" class="flex-1 bg-gray-200 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-300 transition-colors">
                            Cancelar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Ver detalles del pedido
        function viewOrder(orderId) {
            document.getElementById('orderModal').classList.remove('hidden');
            document.getElementById('orderDetails').innerHTML = '<div class="text-center py-8"><i class="fas fa-spinner fa-spin text-2xl text-gray-400"></i></div>';
            
            fetch(`get-order-details.php?id=${orderId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('orderDetails').innerHTML = data.html;
                    } else {
                        document.getElementById('orderDetails').innerHTML = '<p class="text-red-600">Error al cargar los detalles del pedido.</p>';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    document.getElementById('orderDetails').innerHTML = '<p class="text-red-600">Error al cargar los detalles del pedido.</p>';
                });
        }

        // Cerrar modal
        function closeModal() {
            document.getElementById('orderModal').classList.add('hidden');
        }

        // Editar estado del pedido
        function editOrderStatus(orderId, currentStatus) {
            document.getElementById('orderIdStatus').value = orderId;
            document.getElementById('newStatus').value = currentStatus;
            document.getElementById('statusModal').classList.remove('hidden');
        }

        // Cerrar modal de estado
        function closeStatusModal() {
            document.getElementById('statusModal').classList.add('hidden');
        }

        // Manejar cambio de estado
        document.getElementById('statusForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            
            fetch('update-order-status.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Estado actualizado correctamente');
                    location.reload();
                } else {
                    alert('Error al actualizar el estado: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error al actualizar el estado');
            });
        });

        // Generar mensaje de WhatsApp
        function generateWhatsApp(orderId) {
            fetch(`generate-whatsapp.php?id=${orderId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        window.open(data.whatsapp_url, '_blank');
                    } else {
                        alert('Error al generar mensaje de WhatsApp: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error al generar mensaje de WhatsApp');
                });
        }
    </script>
</body>
</html>
