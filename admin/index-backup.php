<?php
session_start();
require_once '../config/config.php';
require_once '../models/Admin.php';

// Verificar si el admin está logueado
if (!isAdminLoggedIn()) {
    redirectTo(ADMIN_URL . '/login.php');
}

// Obtener datos del admin para el dashboard
$adminData = [];
if (isset($_SESSION['admin_id'])) {
    $adminData = [
        'name' => $_SESSION['admin_name'] ?? 'Administrador',
        'full_name' => $_SESSION['admin_name'] ?? 'Administrador',
        'role' => $_SESSION['admin_role'] ?? 'admin',
        'avatar' => $_SESSION['admin_avatar'] ?? null
    ];
}

$admin = new Admin();
$adminData = $admin->findById($_SESSION['admin_id']);

// Validar que $adminData no sea null y tenga los campos necesarios
if (!$adminData) {
    // Si no encontramos al admin en la base de datos, usar datos de sesión
    $adminData = [
        'id' => $_SESSION['admin_id'],
        'name' => $_SESSION['admin_name'] ?? 'Administrador',
        'email' => $_SESSION['admin_email'] ?? '',
        'role' => $_SESSION['admin_role'] ?? 'admin'
    ];
}

// Asegurar que el campo 'name' exista - mapear desde otros campos posibles
if (!isset($adminData['name']) || empty($adminData['name'])) {
    $adminData['name'] = $adminData['full_name'] ?? 
                        $adminData['first_name'] ?? 
                        $_SESSION['admin_name'] ?? 
                        'Administrador';
}

// Obtener estadísticas del dashboard con manejo de errores
$dashboardStats = [
    'total_orders' => 0,
    'total_customers' => 0,
    'total_products' => 0,
    'monthly_revenue' => 0,
    'low_stock_products' => 0,
    'pending_orders' => 0,
    'recent_orders' => [],
    'top_products' => [],
    'sales_data' => []
];

// Intentar obtener estadísticas reales de la base de datos
if (class_exists('Database')) {
    try {
        $db = new Database();
        $pdo = $db->getConnection();
        
        // Obtener total de pedidos
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM orders");
        $stmt->execute();
        $dashboardStats['total_orders'] = $stmt->fetch()['count'] ?? 0;
        
        // Obtener total de clientes
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM customers");
        $stmt->execute();
        $dashboardStats['total_customers'] = $stmt->fetch()['count'] ?? 0;
        
        // Obtener total de productos
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM products");
        $stmt->execute();
        $dashboardStats['total_products'] = $stmt->fetch()['count'] ?? 0;
        
        // Obtener ingresos del mes
        $stmt = $pdo->prepare("SELECT SUM(total_amount) as total FROM orders WHERE MONTH(created_at) = MONTH(CURRENT_DATE()) AND YEAR(created_at) = YEAR(CURRENT_DATE())");
        $stmt->execute();
        $dashboardStats['monthly_revenue'] = $stmt->fetch()['total'] ?? 0;
        
        // Obtener productos con bajo stock
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM products WHERE inventory_quantity <= 5");
        $stmt->execute();
        $dashboardStats['low_stock_products'] = $stmt->fetch()['count'] ?? 0;
        
        // Obtener pedidos pendientes
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM orders WHERE status = 'pending'");
        $stmt->execute();
        $dashboardStats['pending_orders'] = $stmt->fetch()['count'] ?? 0;
        
        // Obtener pedidos recientes (últimos 5)
        $stmt = $pdo->prepare("SELECT * FROM orders ORDER BY created_at DESC LIMIT 5");
        $stmt->execute();
        $dashboardStats['recent_orders'] = $stmt->fetchAll() ?? [];
        
    } catch (Exception $e) {
        // Mantener valores por defecto si ocurre un error con la base de datos
        error_log("Dashboard stats error: " . $e->getMessage());
    }
}

// Make stats available for sidebar
$stats = $dashboardStats;
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Odisea Admin</title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <!-- Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Custom Admin CSS -->
    <link rel="stylesheet" href="assets/css/admin.css">
    
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
        <!-- Sidebar -->
        <?php include 'includes/sidebar.php'; ?>
        
        <!-- Main Content -->
        <div class="flex-1 flex flex-col overflow-hidden">
            <!-- Top Header -->
            <?php include 'includes/header.php'; ?>
            
            <!-- Dashboard Content -->
            <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-50 p-4 lg:p-6">
                <!-- Welcome Section -->
                <div class="mb-8">
                    <h1 class="text-2xl lg:text-3xl font-bold text-gray-900 mb-2">
                        ¡Bienvenido, <?php echo htmlspecialchars($adminData['name'] ?? 'Administrador'); ?>!
                    </h1>
                    <p class="text-gray-600">Aquí tienes un resumen de tu tienda Odisea</p>
                </div>

                <!-- Stats Cards -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 lg:gap-6 mb-8">
                    <!-- Total Orders -->
                    <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100 hover:shadow-md transition-shadow duration-300">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-600">Total Pedidos</p>
                                <p class="text-2xl lg:text-3xl font-bold text-gray-900"><?php echo number_format($dashboardStats['total_orders']); ?></p>
                                <p class="text-sm text-green-600 mt-1">
                                    <i class="fas fa-arrow-up mr-1"></i>
                                    +12% vs mes anterior
                                </p>
                            </div>
                            <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                                <i class="fas fa-shopping-cart text-blue-600 text-xl"></i>
                            </div>
                        </div>
                    </div>

                    <!-- Total Customers -->
                    <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100 hover:shadow-md transition-shadow duration-300">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-600">Total Clientes</p>
                                <p class="text-2xl lg:text-3xl font-bold text-gray-900"><?php echo number_format($dashboardStats['total_customers']); ?></p>
                                <p class="text-sm text-green-600 mt-1">
                                    <i class="fas fa-arrow-up mr-1"></i>
                                    +8% vs mes anterior
                                </p>
                            </div>
                            <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                                <i class="fas fa-users text-green-600 text-xl"></i>
                            </div>
                        </div>
                    </div>

                    <!-- Total Products -->
                    <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100 hover:shadow-md transition-shadow duration-300">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-600">Total Productos</p>
                                <p class="text-2xl lg:text-3xl font-bold text-gray-900"><?php echo number_format($dashboardStats['total_products']); ?></p>
                                <p class="text-sm text-blue-600 mt-1">
                                    <i class="fas fa-plus mr-1"></i>
                                    5 nuevos esta semana
                                </p>
                            </div>
                            <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center">
                                <i class="fas fa-box text-purple-600 text-xl"></i>
                            </div>
                        </div>
                    </div>

                    <!-- Monthly Revenue -->
                    <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100 hover:shadow-md transition-shadow duration-300">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-600">Ingresos del Mes</p>
                                <p class="text-2xl lg:text-3xl font-bold text-gray-900"><?php echo formatPrice($dashboardStats['monthly_revenue']); ?></p>
                                <p class="text-sm text-green-600 mt-1">
                                    <i class="fas fa-arrow-up mr-1"></i>
                                    +15% vs mes anterior
                                </p>
                            </div>
                            <div class="w-12 h-12 bg-primary-100 rounded-lg flex items-center justify-center">
                                <i class="fas fa-dollar-sign text-primary-600 text-xl"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Charts and Recent Activity -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
                    <!-- Sales Chart -->
                    <div class="lg:col-span-2 bg-white rounded-xl shadow-sm p-6 border border-gray-100">
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-2 sm:mb-0">Ventas de los Últimos 7 Días</h3>
                            <div class="flex space-x-2">
                                <button class="px-3 py-1 text-sm bg-primary-100 text-primary-700 rounded-lg">7 días</button>
                                <button class="px-3 py-1 text-sm text-gray-600 hover:bg-gray-100 rounded-lg">30 días</button>
                                <button class="px-3 py-1 text-sm text-gray-600 hover:bg-gray-100 rounded-lg">90 días</button>
                            </div>
                        </div>
                        <div class="h-64 lg:h-80">
                            <canvas id="salesChart"></canvas>
                        </div>
                    </div>

                    <!-- Top Products -->
                    <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
                        <h3 class="text-lg font-semibold text-gray-900 mb-6">Productos Más Vendidos</h3>
                        <div class="space-y-4">
                            <?php if (!empty($dashboardStats['top_products'])): ?>
                                <?php foreach ($dashboardStats['top_products'] as $index => $product): ?>
                                <div class="flex items-center space-x-3">
                                    <div class="w-8 h-8 bg-primary-100 rounded-full flex items-center justify-center">
                                        <span class="text-sm font-semibold text-primary-600"><?php echo $index + 1; ?></span>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-medium text-gray-900 truncate"><?php echo htmlspecialchars($product['name']); ?></p>
                                        <p class="text-xs text-gray-500"><?php echo $product['sales_count']; ?> vendidos</p>
                                    </div>
                                    <div class="text-sm font-semibold text-gray-900">
                                        <?php echo formatPrice($product['revenue']); ?>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="text-center text-gray-500 py-8">
                                    <i class="fas fa-chart-bar text-2xl mb-2"></i>
                                    <p>No hay datos de ventas</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Recent Orders and Alerts -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Recent Orders -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100">
                        <div class="p-6 border-b border-gray-100">
                            <div class="flex items-center justify-between">
                                <h3 class="text-lg font-semibold text-gray-900">Pedidos Recientes</h3>
                                <a href="orders.php" class="text-sm text-primary-600 hover:text-primary-700 font-medium">Ver todos</a>
                            </div>
                        </div>
                        <div class="divide-y divide-gray-100">
                            <?php if (!empty($dashboardStats['recent_orders'])): ?>
                                <?php foreach ($dashboardStats['recent_orders'] as $order): ?>
                                <div class="p-4 hover:bg-gray-50 transition-colors duration-200">
                                    <div class="flex items-center justify-between mb-2">
                                        <div class="flex items-center space-x-3">
                                            <div class="w-8 h-8 bg-gray-100 rounded-full flex items-center justify-center">
                                                <i class="fas fa-receipt text-gray-600 text-sm"></i>
                                            </div>
                                            <div>
                                                <p class="text-sm font-medium text-gray-900">#<?php echo isset($order['order_number']) ? $order['order_number'] : $order['id']; ?></p>
                                                <p class="text-xs text-gray-500"><?php echo isset($order['customer_email']) ? htmlspecialchars($order['customer_email']) : (isset($order['first_name']) ? htmlspecialchars($order['first_name'] . ' ' . $order['last_name']) : 'Cliente'); ?></p>
                                            </div>
                                        </div>
                                        <div class="text-right">
                                            <p class="text-sm font-semibold text-gray-900"><?php echo formatPrice($order['total']); ?></p>
                                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium
                                                <?php echo $order['status'] === 'pending' ? 'bg-yellow-100 text-yellow-800' : 
                                                         ($order['status'] === 'processing' ? 'bg-blue-100 text-blue-800' : 
                                                         ($order['status'] === 'shipped' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800')); ?>">
                                                <?php echo ucfirst($order['status']); ?>
                                            </span>
                                        </div>
                                    </div>
                                    <p class="text-xs text-gray-500"><?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?></p>
                                </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="p-4 text-center text-gray-500">
                                    <i class="fas fa-inbox text-2xl mb-2"></i>
                                    <p>No hay pedidos recientes</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Alerts and Notifications -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100">
                        <div class="p-6 border-b border-gray-100">
                            <h3 class="text-lg font-semibold text-gray-900">Alertas y Notificaciones</h3>
                        </div>
                        <div class="p-6 space-y-4">
                            <!-- Low Stock Alert -->
                            <?php if ($dashboardStats['low_stock_products'] > 0): ?>
                            <div class="flex items-start space-x-3 p-3 bg-red-50 rounded-lg">
                                <div class="w-8 h-8 bg-red-100 rounded-full flex items-center justify-center flex-shrink-0">
                                    <i class="fas fa-exclamation-triangle text-red-600 text-sm"></i>
                                </div>
                                <div class="flex-1">
                                    <p class="text-sm font-medium text-red-900">Stock Bajo</p>
                                    <p class="text-xs text-red-700"><?php echo $dashboardStats['low_stock_products']; ?> productos con stock bajo</p>
                                </div>
                                <a href="products.php?filter=low_stock" class="text-xs text-red-600 hover:text-red-700 font-medium">Ver</a>
                            </div>
                            <?php endif; ?>

                            <!-- Pending Orders Alert -->
                            <?php if ($dashboardStats['pending_orders'] > 0): ?>
                            <div class="flex items-start space-x-3 p-3 bg-yellow-50 rounded-lg">
                                <div class="w-8 h-8 bg-yellow-100 rounded-full flex items-center justify-center flex-shrink-0">
                                    <i class="fas fa-clock text-yellow-600 text-sm"></i>
                                </div>
                                <div class="flex-1">
                                    <p class="text-sm font-medium text-yellow-900">Pedidos Pendientes</p>
                                    <p class="text-xs text-yellow-700"><?php echo $dashboardStats['pending_orders']; ?> pedidos esperando procesamiento</p>
                                </div>
                                <a href="orders.php?status=pending" class="text-xs text-yellow-600 hover:text-yellow-700 font-medium">Ver</a>
                            </div>
                            <?php endif; ?>

                            <!-- Success Message -->
                            <div class="flex items-start space-x-3 p-3 bg-green-50 rounded-lg">
                                <div class="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center flex-shrink-0">
                                    <i class="fas fa-check-circle text-green-600 text-sm"></i>
                                </div>
                                <div class="flex-1">
                                    <p class="text-sm font-medium text-green-900">Sistema Actualizado</p>
                                    <p class="text-xs text-green-700">Todas las funciones están operando correctamente</p>
                                </div>
                            </div>

                            <!-- New Customer Alert -->
                            <div class="flex items-start space-x-3 p-3 bg-blue-50 rounded-lg">
                                <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center flex-shrink-0">
                                    <i class="fas fa-user-plus text-blue-600 text-sm"></i>
                                </div>
                                <div class="flex-1">
                                    <p class="text-sm font-medium text-blue-900">Nuevos Clientes</p>
                                    <p class="text-xs text-blue-700">15 nuevos registros esta semana</p>
                                </div>
                                <a href="customers.php?filter=new" class="text-xs text-blue-600 hover:text-blue-700 font-medium">Ver</a>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Scripts -->
    <script src="assets/js/admin.js"></script>
    <script>
        // Initialize Sales Chart
        const ctx = document.getElementById('salesChart').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: ['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'],
                datasets: [{
                    label: 'Ventas',
                    data: [1200000, 1900000, 800000, 2100000, 1600000, 2400000, 1800000],
                    borderColor: '#ec4899',
                    backgroundColor: 'rgba(236, 72, 153, 0.1)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4,
                    pointBackgroundColor: '#ec4899',
                    pointBorderColor: '#ffffff',
                    pointBorderWidth: 2,
                    pointRadius: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return '$' + (value / 1000000).toFixed(1) + 'M';
                            }
                        },
                        grid: {
                            color: 'rgba(0, 0, 0, 0.05)'
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                },
                elements: {
                    point: {
                        hoverRadius: 8
                    }
                }
            }
        });
    </script>
</body>
</html>
