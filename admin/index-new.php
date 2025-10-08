<?php
session_start();
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../models/Admin.php';
require_once '../models/Product.php';
require_once '../models/Order.php';
require_once '../models/Customer.php';

// Verificar si el admin está logueado
if (!isAdminLoggedIn()) {
    redirectTo(ADMIN_URL . '/login.php');
}

// Inicializar modelos
$admin = new Admin();
$productModel = new Product();
$orderModel = new Order();
$customerModel = new Customer();

// Obtener datos del admin
$adminData = $admin->findById($_SESSION['admin_id']);
if (!$adminData) {
    $adminData = [
        'id' => $_SESSION['admin_id'],
        'name' => $_SESSION['admin_name'] ?? 'Administrador',
        'email' => $_SESSION['admin_email'] ?? '',
        'role' => $_SESSION['admin_role'] ?? 'admin'
    ];
}

// Función para obtener estadísticas completas del dashboard
function getDashboardStats() {
    $database = new Database();
    $db = $database->getConnection();
    
    $stats = [];
    
    try {
        // Estadísticas básicas
        $stmt = $db->query("SELECT COUNT(*) as total FROM orders WHERE status != 'cancelled'");
        $stats['total_orders'] = $stmt->fetch()['total'];
        
        $stmt = $db->query("SELECT COUNT(*) as total FROM customers WHERE status = 'active'");
        $stats['total_customers'] = $stmt->fetch()['total'];
        
        $stmt = $db->query("SELECT COUNT(*) as total FROM products WHERE status = 'active'");
        $stats['total_products'] = $stmt->fetch()['total'];
        
        // Ingresos del mes actual
        $stmt = $db->query("SELECT COALESCE(SUM(total), 0) as revenue FROM orders 
                           WHERE status = 'completed' 
                           AND MONTH(created_at) = MONTH(CURRENT_DATE()) 
                           AND YEAR(created_at) = YEAR(CURRENT_DATE())");
        $stats['monthly_revenue'] = $stmt->fetch()['revenue'];
        
        // Ingresos del mes anterior
        $stmt = $db->query("SELECT COALESCE(SUM(total), 0) as revenue FROM orders 
                           WHERE status = 'completed' 
                           AND MONTH(created_at) = MONTH(CURRENT_DATE() - INTERVAL 1 MONTH) 
                           AND YEAR(created_at) = YEAR(CURRENT_DATE() - INTERVAL 1 MONTH)");
        $stats['last_month_revenue'] = $stmt->fetch()['revenue'];
        
        // Calcular crecimiento
        $stats['revenue_growth'] = 0;
        if ($stats['last_month_revenue'] > 0) {
            $stats['revenue_growth'] = (($stats['monthly_revenue'] - $stats['last_month_revenue']) / $stats['last_month_revenue']) * 100;
        }
        
        // Productos con stock bajo
        $stmt = $db->query("SELECT COUNT(*) as total FROM products 
                           WHERE inventory_quantity < 10 AND status = 'active'");
        $stats['low_stock_products'] = $stmt->fetch()['total'];
        
        // Pedidos pendientes
        $stmt = $db->query("SELECT COUNT(*) as total FROM orders WHERE status = 'pending'");
        $stats['pending_orders'] = $stmt->fetch()['total'];
        
        // Nuevos clientes este mes
        $stmt = $db->query("SELECT COUNT(*) as total FROM customers 
                           WHERE MONTH(created_at) = MONTH(CURRENT_DATE()) 
                           AND YEAR(created_at) = YEAR(CURRENT_DATE())");
        $stats['new_customers_month'] = $stmt->fetch()['total'];
        
        // Valor promedio de orden
        $stmt = $db->query("SELECT COALESCE(AVG(total), 0) as avg_order FROM orders 
                           WHERE status = 'completed' 
                           AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
        $stats['avg_order_value'] = $stmt->fetch()['avg_order'];
        
        // Ventas de hoy
        $stmt = $db->query("SELECT COALESCE(SUM(total), 0) as today_sales FROM orders 
                           WHERE status = 'completed' 
                           AND DATE(created_at) = CURRENT_DATE()");
        $stats['today_sales'] = $stmt->fetch()['today_sales'];
        
        // Productos más vendidos
        $stmt = $db->query("SELECT p.name, p.main_image, SUM(oi.quantity) as total_sold,
                           SUM(oi.quantity * oi.price) as revenue
                           FROM products p
                           JOIN order_items oi ON p.id = oi.product_id
                           JOIN orders o ON oi.order_id = o.id
                           WHERE o.status = 'completed' 
                           AND o.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                           GROUP BY p.id
                           ORDER BY total_sold DESC
                           LIMIT 5");
        $stats['top_products'] = $stmt->fetchAll();
        
        // Gráfico de ventas de los últimos 7 días
        $stmt = $db->query("SELECT DATE(created_at) as date, 
                           COALESCE(SUM(total), 0) as daily_sales,
                           COUNT(*) as order_count
                           FROM orders 
                           WHERE status = 'completed' 
                           AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                           GROUP BY DATE(created_at)
                           ORDER BY date ASC");
        $stats['sales_chart'] = $stmt->fetchAll();
        
        // Pedidos recientes
        $stmt = $db->query("SELECT o.id, o.total, o.status, o.created_at,
                           CONCAT(c.first_name, ' ', c.last_name) as customer_name
                           FROM orders o
                           LEFT JOIN customers c ON o.customer_id = c.id
                           ORDER BY o.created_at DESC
                           LIMIT 10");
        $stats['recent_orders'] = $stmt->fetchAll();
        
        // Productos con stock crítico
        $stmt = $db->query("SELECT name, inventory_quantity, price, main_image 
                           FROM products 
                           WHERE inventory_quantity < 5 AND status = 'active'
                           ORDER BY inventory_quantity ASC
                           LIMIT 5");
        $stats['critical_stock'] = $stmt->fetchAll();
        
        // Actividad reciente (últimas 20 acciones)
        $stmt = $db->query("SELECT 'order' as type, id as reference_id, created_at, 
                           CONCAT('Nueva orden #', id) as activity
                           FROM orders 
                           WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
                           UNION ALL
                           SELECT 'customer' as type, id as reference_id, created_at,
                           CONCAT('Nuevo cliente: ', first_name, ' ', last_name) as activity
                           FROM customers 
                           WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
                           ORDER BY created_at DESC
                           LIMIT 20");
        $stats['recent_activity'] = $stmt->fetchAll();
        
    } catch (Exception $e) {
        error_log("Dashboard stats error: " . $e->getMessage());
        // Valores por defecto en caso de error
        $stats = [
            'total_orders' => 0,
            'total_customers' => 0,
            'total_products' => 0,
            'monthly_revenue' => 0,
            'last_month_revenue' => 0,
            'revenue_growth' => 0,
            'low_stock_products' => 0,
            'pending_orders' => 0,
            'new_customers_month' => 0,
            'avg_order_value' => 0,
            'today_sales' => 0,
            'top_products' => [],
            'sales_chart' => [],
            'recent_orders' => [],
            'critical_stock' => [],
            'recent_activity' => []
        ];
    }
    
    return $stats;
}

// Obtener todas las estadísticas
$stats = getDashboardStats();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Odisea Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
        <?php include 'includes/sidebar.php'; ?>
        
        <div class="flex-1 flex flex-col overflow-hidden">
            <?php include 'includes/header.php'; ?>
            
            <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-50 p-4 lg:p-6">
                <!-- Welcome Header -->
                <div class="mb-8">
                    <div class="flex items-center justify-between">
                        <div>
                            <h1 class="text-3xl font-bold text-gray-900">
                                ¡Bienvenido, <?php echo htmlspecialchars($adminData['name']); ?>!
                            </h1>
                            <p class="text-gray-600 mt-1">
                                Aquí tienes un resumen de tu tienda - <?php echo date('l, d F Y'); ?>
                            </p>
                        </div>
                        <div class="hidden lg:flex items-center space-x-4">
                            <div class="bg-white px-4 py-2 rounded-lg shadow-sm border">
                                <div class="flex items-center text-sm">
                                    <i class="fas fa-clock text-gray-400 mr-2"></i>
                                    <span class="text-gray-600">Última actualización:</span>
                                    <span class="font-medium ml-1" id="last-update"><?php echo date('H:i'); ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- KPI Cards -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                    <!-- Total Revenue -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-600">Ingresos del Mes</p>
                                <p class="text-2xl font-bold text-gray-900">
                                    $<?php echo number_format($stats['monthly_revenue'], 2); ?>
                                </p>
                                <div class="flex items-center mt-2">
                                    <?php if ($stats['revenue_growth'] >= 0): ?>
                                        <i class="fas fa-arrow-up text-green-500 text-sm mr-1"></i>
                                        <span class="text-green-600 text-sm font-medium">
                                            +<?php echo number_format($stats['revenue_growth'], 1); ?>%
                                        </span>
                                    <?php else: ?>
                                        <i class="fas fa-arrow-down text-red-500 text-sm mr-1"></i>
                                        <span class="text-red-600 text-sm font-medium">
                                            <?php echo number_format($stats['revenue_growth'], 1); ?>%
                                        </span>
                                    <?php endif; ?>
                                    <span class="text-gray-500 text-sm ml-1">vs mes anterior</span>
                                </div>
                            </div>
                            <div class="p-3 bg-green-100 rounded-lg">
                                <i class="fas fa-dollar-sign text-green-600 text-xl"></i>
                            </div>
                        </div>
                    </div>

                    <!-- Total Orders -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-600">Total Órdenes</p>
                                <p class="text-2xl font-bold text-gray-900"><?php echo number_format($stats['total_orders']); ?></p>
                                <div class="flex items-center mt-2">
                                    <span class="text-orange-600 text-sm font-medium">
                                        <?php echo $stats['pending_orders']; ?> pendientes
                                    </span>
                                </div>
                            </div>
                            <div class="p-3 bg-blue-100 rounded-lg">
                                <i class="fas fa-shopping-cart text-blue-600 text-xl"></i>
                            </div>
                        </div>
                    </div>

                    <!-- Total Customers -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-600">Clientes Activos</p>
                                <p class="text-2xl font-bold text-gray-900"><?php echo number_format($stats['total_customers']); ?></p>
                                <div class="flex items-center mt-2">
                                    <i class="fas fa-plus text-purple-500 text-sm mr-1"></i>
                                    <span class="text-purple-600 text-sm font-medium">
                                        <?php echo $stats['new_customers_month']; ?> este mes
                                    </span>
                                </div>
                            </div>
                            <div class="p-3 bg-purple-100 rounded-lg">
                                <i class="fas fa-users text-purple-600 text-xl"></i>
                            </div>
                        </div>
                    </div>

                    <!-- Average Order Value -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-600">Valor Promedio</p>
                                <p class="text-2xl font-bold text-gray-900">
                                    $<?php echo number_format($stats['avg_order_value'], 2); ?>
                                </p>
                                <div class="flex items-center mt-2">
                                    <span class="text-gray-600 text-sm">
                                        Hoy: $<?php echo number_format($stats['today_sales'], 2); ?>
                                    </span>
                                </div>
                            </div>
                            <div class="p-3 bg-yellow-100 rounded-lg">
                                <i class="fas fa-chart-line text-yellow-600 text-xl"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Charts and Tables Section -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
                    <!-- Sales Chart -->
                    <div class="lg:col-span-2 bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                        <div class="flex items-center justify-between mb-6">
                            <h3 class="text-lg font-semibold text-gray-900">Ventas de los Últimos 7 Días</h3>
                            <div class="flex items-center space-x-2">
                                <span class="w-3 h-3 bg-primary-500 rounded-full"></span>
                                <span class="text-sm text-gray-600">Ingresos</span>
                            </div>
                        </div>
                        <div class="h-64">
                            <canvas id="salesChart"></canvas>
                        </div>
                    </div>

                    <!-- Quick Stats -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-6">Resumen Rápido</h3>
                        <div class="space-y-4">
                            <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                <div class="flex items-center">
                                    <i class="fas fa-box text-blue-500 mr-3"></i>
                                    <span class="text-sm font-medium text-gray-700">Productos Activos</span>
                                </div>
                                <span class="text-lg font-bold text-gray-900"><?php echo $stats['total_products']; ?></span>
                            </div>
                            
                            <div class="flex items-center justify-between p-3 bg-red-50 rounded-lg">
                                <div class="flex items-center">
                                    <i class="fas fa-exclamation-triangle text-red-500 mr-3"></i>
                                    <span class="text-sm font-medium text-gray-700">Stock Bajo</span>
                                </div>
                                <span class="text-lg font-bold text-red-600"><?php echo $stats['low_stock_products']; ?></span>
                            </div>
                            
                            <div class="flex items-center justify-between p-3 bg-orange-50 rounded-lg">
                                <div class="flex items-center">
                                    <i class="fas fa-clock text-orange-500 mr-3"></i>
                                    <span class="text-sm font-medium text-gray-700">Pedidos Pendientes</span>
                                </div>
                                <span class="text-lg font-bold text-orange-600"><?php echo $stats['pending_orders']; ?></span>
                            </div>
                            
                            <div class="mt-6">
                                <a href="pedidos.php?status=pending" class="w-full bg-primary-600 hover:bg-primary-700 text-white text-center py-2 px-4 rounded-lg transition-colors inline-block">
                                    Ver Pedidos Pendientes
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tables Section -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Recent Orders -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                        <div class="flex items-center justify-between mb-6">
                            <h3 class="text-lg font-semibold text-gray-900">Órdenes Recientes</h3>
                            <a href="pedidos.php" class="text-primary-600 hover:text-primary-700 text-sm font-medium">
                                Ver todas
                            </a>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full">
                                <thead>
                                    <tr class="border-b border-gray-200">
                                        <th class="text-left py-3 px-4 font-medium text-gray-500 text-sm">Orden</th>
                                        <th class="text-left py-3 px-4 font-medium text-gray-500 text-sm">Cliente</th>
                                        <th class="text-right py-3 px-4 font-medium text-gray-500 text-sm">Total</th>
                                        <th class="text-center py-3 px-4 font-medium text-gray-500 text-sm">Estado</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach (array_slice($stats['recent_orders'], 0, 8) as $order): ?>
                                    <tr class="border-b border-gray-100 hover:bg-gray-50">
                                        <td class="py-3 px-4">
                                            <span class="font-medium text-gray-900">#<?php echo $order['id']; ?></span>
                                            <div class="text-xs text-gray-500">
                                                <?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?>
                                            </div>
                                        </td>
                                        <td class="py-3 px-4">
                                            <span class="text-sm text-gray-900">
                                                <?php echo htmlspecialchars($order['customer_name'] ?: 'Cliente Invitado'); ?>
                                            </span>
                                        </td>
                                        <td class="py-3 px-4 text-right">
                                            <span class="font-medium text-gray-900">
                                                $<?php echo number_format($order['total'], 2); ?>
                                            </span>
                                        </td>
                                        <td class="py-3 px-4 text-center">
                                            <?php
                                            $statusClasses = [
                                                'pending' => 'bg-yellow-100 text-yellow-800',
                                                'processing' => 'bg-blue-100 text-blue-800',
                                                'completed' => 'bg-green-100 text-green-800',
                                                'cancelled' => 'bg-red-100 text-red-800'
                                            ];
                                            $statusClass = $statusClasses[$order['status']] ?? 'bg-gray-100 text-gray-800';
                                            ?>
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo $statusClass; ?>">
                                                <?php echo ucfirst($order['status']); ?>
                                            </span>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Top Products -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                        <div class="flex items-center justify-between mb-6">
                            <h3 class="text-lg font-semibold text-gray-900">Productos Más Vendidos</h3>
                            <a href="../admin-pages/products.php" class="text-primary-600 hover:text-primary-700 text-sm font-medium">
                                Ver todos
                            </a>
                        </div>
                        <div class="space-y-4">
                            <?php foreach (array_slice($stats['top_products'], 0, 5) as $index => $product): ?>
                            <div class="flex items-center space-x-4">
                                <div class="flex-shrink-0">
                                    <div class="w-10 h-10 bg-primary-100 rounded-lg flex items-center justify-center">
                                        <span class="text-primary-600 font-bold text-sm"><?php echo $index + 1; ?></span>
                                    </div>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium text-gray-900 truncate">
                                        <?php echo htmlspecialchars($product['name']); ?>
                                    </p>
                                    <p class="text-xs text-gray-500">
                                        <?php echo $product['total_sold']; ?> vendidos
                                    </p>
                                </div>
                                <div class="text-right">
                                    <p class="text-sm font-medium text-gray-900">
                                        $<?php echo number_format($product['revenue'], 2); ?>
                                    </p>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- Critical Stock Alert -->
                <?php if (!empty($stats['critical_stock'])): ?>
                <div class="mt-6 bg-red-50 border border-red-200 rounded-xl p-6">
                    <div class="flex items-center mb-4">
                        <i class="fas fa-exclamation-triangle text-red-500 text-xl mr-3"></i>
                        <h3 class="text-lg font-semibold text-red-900">Productos con Stock Crítico</h3>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <?php foreach ($stats['critical_stock'] as $product): ?>
                        <div class="bg-white rounded-lg p-4 border border-red-200">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="font-medium text-gray-900 text-sm">
                                        <?php echo htmlspecialchars(substr($product['name'], 0, 30)); ?><?php echo strlen($product['name']) > 30 ? '...' : ''; ?>
                                    </p>
                                    <p class="text-xs text-gray-500">
                                        $<?php echo number_format($product['price'], 2); ?>
                                    </p>
                                </div>
                                <div class="text-right">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                        <?php echo $product['inventory_quantity']; ?> en stock
                                    </span>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="mt-4">
                        <a href="../admin-pages/products.php?filter=low_stock" class="inline-flex items-center px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-medium rounded-lg transition-colors">
                            <i class="fas fa-boxes mr-2"></i>
                            Gestionar Inventario
                        </a>
                    </div>
                </div>
                <?php endif; ?>
            </main>
        </div>
    </div>

    <script>
        // Sales Chart
        const ctx = document.getElementById('salesChart').getContext('2d');
        const salesData = <?php echo json_encode($stats['sales_chart']); ?>;
        
        const labels = salesData.map(item => {
            const date = new Date(item.date);
            return date.toLocaleDateString('es-ES', { month: 'short', day: 'numeric' });
        });
        
        const data = salesData.map(item => parseFloat(item.daily_sales));
        
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Ventas Diarias',
                    data: data,
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
                                return '$' + value.toLocaleString();
                            }
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
        
        // Auto-refresh last update time
        setInterval(function() {
            document.getElementById('last-update').textContent = new Date().toLocaleTimeString('es-ES', {
                hour: '2-digit',
                minute: '2-digit'
            });
        }, 60000);
    </script>
</body>
</html>
