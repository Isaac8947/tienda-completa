<?php
session_start();
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../models/Customer.php';
require_once '../models/Order.php';
require_once '../admin/auth-check.php';

$customerModel = new Customer();
$orderModel = new Order();

// Obtener todos los clientes
$customers = $customerModel->getAll();

// Ver detalles de un cliente específico
$customerDetails = null;
$customerOrders = [];
if (isset($_GET['view'])) {
    $customerId = $_GET['view'];
    $customerDetails = $customerModel->getById($customerId);
    $customerOrders = $orderModel->getByCustomer($customerId);
}

// Procesar cambio de estado
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'update_status') {
        $customerId = $_POST['customer_id'];
        $status = isset($_POST['status']) ? 1 : 0;
        
        $customerModel->update($customerId, ['is_active' => $status]);
        
        header('Location: customers.php?view=' . $customerId . '&updated=1');
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Clientes - Odisea Admin</title>
    
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
                        <h1 class="text-2xl lg:text-3xl font-bold text-gray-900">Gestión de Clientes</h1>
                        <p class="text-gray-600 mt-1">Administra los clientes de la tienda</p>
                    </div>
                    <div class="flex flex-col sm:flex-row space-y-2 sm:space-y-0 sm:space-x-3">
                        <div class="relative">
                            <input type="text" id="customerSearch" placeholder="Buscar cliente..." class="block w-64 px-4 py-2 pl-10 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 text-sm">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-search text-gray-400"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Success/Error Messages -->
                <?php if (isset($_GET['updated'])): ?>
                <div class="mb-6 p-4 rounded-lg bg-green-100 text-green-800">
                    <i class="fas fa-check-circle mr-2"></i>
                    Información del cliente actualizada exitosamente.
                </div>
                <?php endif; ?>
                
                <?php if ($customerDetails): ?>
                <!-- Vista detallada del cliente -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="p-6">
                        <div class="flex items-center justify-between mb-6">
                            <h3 class="text-xl font-bold text-gray-900"><?php echo $customerDetails['first_name'] . ' ' . $customerDetails['last_name']; ?></h3>
                            <span class="px-3 py-1 rounded-full text-sm font-medium <?php echo $customerDetails['is_active'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                <?php echo $customerDetails['is_active'] ? 'Activo' : 'Inactivo'; ?>
                            </span>
                        </div>
                        
                        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
                            <!-- Información Personal -->
                            <div class="bg-gray-50 rounded-lg p-4">
                                <h4 class="text-lg font-semibold text-gray-900 mb-3">Información Personal</h4>
                                <div class="space-y-2 text-sm">
                                    <p><span class="font-medium text-gray-700">Email:</span> <?php echo $customerDetails['email']; ?></p>
                                    <p><span class="font-medium text-gray-700">Teléfono:</span> <?php echo $customerDetails['phone'] ?? 'No proporcionado'; ?></p>
                                    <p><span class="font-medium text-gray-700">Fecha de registro:</span> <?php echo date('d/m/Y', strtotime($customerDetails['created_at'])); ?></p>
                                </div>
                            </div>
                            
                            <!-- Dirección -->
                            <div class="bg-gray-50 rounded-lg p-4">
                                <h4 class="text-lg font-semibold text-gray-900 mb-3">Dirección</h4>
                                <div class="space-y-2 text-sm">
                                    <p><span class="font-medium text-gray-700">Dirección:</span> <?php echo $customerDetails['address'] ?? 'No proporcionada'; ?></p>
                                    <p><span class="font-medium text-gray-700">Ciudad:</span> <?php echo $customerDetails['city'] ?? 'No proporcionada'; ?></p>
                                    <p><span class="font-medium text-gray-700">Código Postal:</span> <?php echo $customerDetails['postal_code'] ?? 'No proporcionado'; ?></p>
                                    <p><span class="font-medium text-gray-700">País:</span> <?php echo $customerDetails['country'] ?? 'No proporcionado'; ?></p>
                                </div>
                            </div>
                            
                            <!-- Estadísticas -->
                            <div class="bg-gray-50 rounded-lg p-4">
                                <h4 class="text-lg font-semibold text-gray-900 mb-3">Estadísticas</h4>
                                <div class="space-y-2 text-sm">
                                    <p><span class="font-medium text-gray-700">Total de pedidos:</span> <?php echo count($customerOrders); ?></p>
                                    <p><span class="font-medium text-gray-700">Total gastado:</span> $<?php 
                                        $totalSpent = 0;
                                        foreach ($customerOrders as $order) {
                                            $totalSpent += $order['total'];
                                        }
                                        echo number_format($totalSpent, 2);
                                    ?></p>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Actualizar Estado -->
                        <div class="bg-gray-50 rounded-lg p-4 mb-6">
                            <h4 class="text-lg font-semibold text-gray-900 mb-4">Actualizar Estado</h4>
                            <form method="POST" class="flex flex-col sm:flex-row gap-4 items-end">
                                <input type="hidden" name="action" value="update_status">
                                <input type="hidden" name="customer_id" value="<?php echo $customerDetails['id']; ?>">
                                
                                <div class="flex items-center space-x-3">
                                    <label class="relative inline-flex items-center cursor-pointer">
                                        <input type="checkbox" name="status" class="sr-only peer" <?php echo $customerDetails['is_active'] ? 'checked' : ''; ?>>
                                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-primary-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary-600"></div>
                                        <span class="ml-3 text-sm font-medium text-gray-700">Cliente activo</span>
                                    </label>
                                    <button type="submit" class="bg-gradient-to-r from-primary-500 to-primary-600 text-white px-6 py-2 rounded-lg hover:from-primary-600 hover:to-primary-700 transition-all duration-200 flex items-center">
                                        <i class="fas fa-save mr-2"></i>
                                        Actualizar
                                    </button>
                                </div>
                            </form>
                        </div>
                        
                        <!-- Historial de Pedidos -->
                        <div class="mb-6">
                            <h4 class="text-lg font-semibold text-gray-900 mb-4">Historial de Pedidos</h4>
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fecha</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Estado</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        <?php foreach ($customerOrders as $order): ?>
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">#<?php echo $order['id']; ?></td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?></td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">$<?php echo number_format($order['total'], 2); ?></td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="px-2 py-1 text-xs font-medium rounded-full <?php echo getStatusBadgeClass($order['status']); ?>">
                                                    <?php echo ucfirst($order['status']); ?>
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                <a href="orders.php?view=<?php echo $order['id']; ?>" class="bg-blue-100 text-blue-800 hover:bg-blue-200 px-3 py-1 rounded-lg transition-colors duration-200 text-xs font-medium inline-flex items-center">
                                                    <i class="fas fa-eye mr-1"></i>
                                                    Ver
                                                </a>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                        
                                        <?php if (empty($customerOrders)): ?>
                                        <tr>
                                            <td colspan="5" class="px-6 py-12 text-center text-gray-500">
                                                <div class="flex flex-col items-center">
                                                    <i class="fas fa-shopping-cart text-4xl mb-4 text-gray-300"></i>
                                                    <p class="text-lg font-medium">Este cliente no tiene pedidos</p>
                                                    <p class="text-sm">Los pedidos aparecerán aquí una vez que el cliente realice compras.</p>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        
                        <!-- Back Link -->
                        <div class="flex justify-start">
                            <a href="customers.php" class="bg-gray-500 text-white px-6 py-2 rounded-lg hover:bg-gray-600 transition-all duration-200 flex items-center">
                                <i class="fas fa-arrow-left mr-2"></i>
                                Volver a la lista de clientes
                            </a>
                        </div>
                    </div>
                </div>
                <?php else: ?>
                <!-- Lista de clientes -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                    <!-- Table Header -->
                    <div class="px-4 lg:px-6 py-4 border-b border-gray-200 bg-gray-50">
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
                            <h3 class="text-lg font-semibold text-gray-900 mb-2 sm:mb-0">
                                <?php echo count($customers); ?> clientes registrados
                            </h3>
                        </div>
                    </div>

                    <!-- Desktop Table View -->
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200" id="customersTable">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nombre</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Teléfono</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fecha de registro</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Estado</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($customers as $customer): ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?php echo $customer['id']; ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo $customer['first_name'] . ' ' . $customer['last_name']; ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo $customer['email']; ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo $customer['phone'] ?? 'No proporcionado'; ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo date('d/m/Y', strtotime($customer['created_at'])); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <?php if ($customer['is_active'] == 1): ?>
                                        <span class="px-2 py-1 text-xs font-medium rounded-full bg-green-100 text-green-800">Activo</span>
                                        <?php else: ?>
                                        <span class="px-2 py-1 text-xs font-medium rounded-full bg-red-100 text-red-800">Inactivo</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <a href="?view=<?php echo $customer['id']; ?>" class="bg-blue-100 text-blue-800 hover:bg-blue-200 px-3 py-1 rounded-lg transition-colors duration-200 text-xs font-medium inline-flex items-center">
                                            <i class="fas fa-eye mr-1"></i>
                                            Ver
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                
                                <?php if (empty($customers)): ?>
                                <tr>
                                    <td colspan="7" class="px-6 py-12 text-center text-gray-500">
                                        <div class="flex flex-col items-center">
                                            <i class="fas fa-users text-4xl mb-4 text-gray-300"></i>
                                            <p class="text-lg font-medium">No hay clientes registrados</p>
                                            <p class="text-sm">Los clientes aparecerán aquí una vez que se registren en la tienda.</p>
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
        document.addEventListener('DOMContentLoaded', function() {
            // Búsqueda de clientes
            const searchInput = document.getElementById('customerSearch');
            if (searchInput) {
                searchInput.addEventListener('keyup', function() {
                    const searchTerm = this.value.toLowerCase();
                    const table = document.getElementById('customersTable');
                    const rows = table.getElementsByTagName('tbody')[0].getElementsByTagName('tr');
                    
                    for (let i = 0; i < rows.length; i++) {
                        const name = rows[i].getElementsByTagName('td')[1]?.textContent?.toLowerCase() || '';
                        const email = rows[i].getElementsByTagName('td')[2]?.textContent?.toLowerCase() || '';
                        
                        if (name.includes(searchTerm) || email.includes(searchTerm)) {
                            rows[i].style.display = '';
                        } else {
                            rows[i].style.display = 'none';
                        }
                    }
                });
            }
        });
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
