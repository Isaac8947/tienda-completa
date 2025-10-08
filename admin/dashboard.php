<?php
session_start();
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../includes/InventoryManager.php';
require_once '../models/Admin.php';

// Verificar autenticación del admin
if (!isAdminLoggedIn()) {
    redirectTo(ADMIN_URL . '/login.php');
}

// Obtener datos del admin para el header
$adminData = [];
if (isset($_SESSION['admin_id'])) {
    $adminModel = new Admin();
    $adminData = $adminModel->findById($_SESSION['admin_id']);
    
    // Validar que $adminData no sea null y tenga los campos necesarios
    if (!$adminData) {
        // Si no encontramos al admin en la base de datos, usar datos de sesión
        $adminData = [
            'id' => $_SESSION['admin_id'],
            'name' => $_SESSION['admin_name'] ?? 'Administrador',
            'full_name' => $_SESSION['admin_name'] ?? 'Administrador',
            'email' => $_SESSION['admin_email'] ?? '',
            'role' => $_SESSION['admin_role'] ?? 'admin',
            'avatar' => null
        ];
    }
    
    // Asegurar que el campo 'full_name' exista
    if (!isset($adminData['full_name']) || empty($adminData['full_name'])) {
        $adminData['full_name'] = $adminData['name'] ?? 
                                  $adminData['first_name'] ?? 
                                  $_SESSION['admin_name'] ?? 
                                  'Administrador';
    }
}

$database = new Database();
$db = $database->getConnection();
$inventory = new InventoryManager($db);

// === ESTADÍSTICAS PRINCIPALES ===

// Inicializar variables con valores por defecto
$orders_stats = [
    'total_orders' => 0,
    'pending_orders' => 0,
    'confirmed_orders' => 0,
    'shipped_orders' => 0,
    'delivered_orders' => 0,
    'cancelled_orders' => 0,
    'today_orders' => 0,
    'week_orders' => 0,
    'total_revenue' => 0
];

$products_stats = [
    'total_products' => 0,
    'low_stock_products' => 0,
    'out_of_stock_products' => 0,
    'total_stock_units' => 0,
    'avg_stock_per_product' => 0
];

$users_stats = [
    'total_users' => 0,
    'today_registrations' => 0,
    'week_registrations' => 0
];

$recent_orders = [];
$low_stock_products = [];
$recent_inventory = [];
$monthly_revenue = [];

// Estadísticas de pedidos - solo datos reales
try {
    $orders_stats = $db->query("
        SELECT 
            COUNT(*) as total_orders,
            COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_orders,
            COUNT(CASE WHEN status = 'confirmed' THEN 1 END) as confirmed_orders,
            COUNT(CASE WHEN status = 'shipped' THEN 1 END) as shipped_orders,
            COUNT(CASE WHEN status = 'delivered' THEN 1 END) as delivered_orders,
            COUNT(CASE WHEN status = 'cancelled' THEN 1 END) as cancelled_orders,
            COUNT(CASE WHEN DATE(created_at) = CURDATE() THEN 1 END) as today_orders,
            COUNT(CASE WHEN WEEK(created_at) = WEEK(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE()) THEN 1 END) as week_orders,
            ROUND(SUM(CASE WHEN status NOT IN ('cancelled') THEN total ELSE 0 END), 2) as total_revenue
        FROM orders
    ")->fetch();
} catch (Exception $e) {
    error_log("Error en estadísticas de pedidos: " . $e->getMessage());
    $orders_stats = [
        'total_orders' => 0,
        'pending_orders' => 0,
        'confirmed_orders' => 0,
        'shipped_orders' => 0,
        'delivered_orders' => 0,
        'cancelled_orders' => 0,
        'today_orders' => 0,
        'week_orders' => 0,
        'total_revenue' => 0
    ];
}

// Estadísticas de productos y stock - solo datos reales
try {
    $products_stats = $db->query("
        SELECT 
            COUNT(*) as total_products,
            COUNT(CASE WHEN stock <= min_stock AND stock > 0 THEN 1 END) as low_stock_products,
            COUNT(CASE WHEN stock = 0 THEN 1 END) as out_of_stock_products,
            SUM(stock) as total_stock_units,
            AVG(stock) as avg_stock_per_product
        FROM products WHERE status = 'active'
    ")->fetch();
} catch (Exception $e) {
    error_log("Error en estadísticas de productos: " . $e->getMessage());
    $products_stats = [
        'total_products' => 0,
        'low_stock_products' => 0,
        'out_of_stock_products' => 0,
        'total_stock_units' => 0,
        'avg_stock_per_product' => 0
    ];
}

// Estadísticas de usuarios - solo datos reales
try {
    $users_stats = $db->query("
        SELECT 
            COUNT(*) as total_users,
            COUNT(CASE WHEN DATE(created_at) = CURDATE() THEN 1 END) as today_registrations,
            COUNT(CASE WHEN WEEK(created_at) = WEEK(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE()) THEN 1 END) as week_registrations
        FROM customers
    ")->fetch();
} catch (Exception $e) {
    error_log("Error en estadísticas de usuarios: " . $e->getMessage());
    $users_stats = [
        'total_users' => 0,
        'today_registrations' => 0,
        'week_registrations' => 0
    ];
}

// Pedidos recientes - solo datos reales
try {
    $recent_orders = $db->query("
        SELECT o.id, o.total, o.status, o.created_at, o.shipping_name,
               COUNT(oi.id) as items_count
        FROM orders o
        LEFT JOIN order_items oi ON o.id = oi.order_id
        GROUP BY o.id
        ORDER BY o.created_at DESC
        LIMIT 10
    ")->fetchAll();
} catch (Exception $e) {
    error_log("Error en pedidos recientes: " . $e->getMessage());
    $recent_orders = [];
}

// Productos con stock bajo - solo datos reales
try {
    $low_stock_products = $inventory->getLowStockProducts();
} catch (Exception $e) {
    error_log("Error en productos con stock bajo: " . $e->getMessage());
    $low_stock_products = [];
}

// Notificaciones no leídas
try {
    $unread_notifications = $db->query("
        SELECT * FROM admin_notifications 
        WHERE is_read = FALSE 
        ORDER BY priority DESC, created_at DESC 
        LIMIT 15
    ")->fetchAll();
} catch (PDOException $e) {
    // Si la tabla no existe o hay otro error, usar array vacío
    $unread_notifications = [];
}

// Movimientos de inventario recientes - solo datos reales
try {
    $recent_inventory = $inventory->getInventoryHistory(null, 10);
} catch (Exception $e) {
    error_log("Error en historial de inventario: " . $e->getMessage());
    $recent_inventory = [];
}

// Ingresos por mes (últimos 12 meses)
try {
    $monthly_revenue = $db->query("
        SELECT 
            DATE_FORMAT(created_at, '%Y-%m') as month,
            SUM(CASE WHEN status NOT IN ('cancelled') THEN total ELSE 0 END) as revenue,
            COUNT(*) as orders_count
        FROM orders 
        WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
        GROUP BY DATE_FORMAT(created_at, '%Y-%m')
        ORDER BY month DESC
    ")->fetchAll();
} catch (Exception $e) {
    error_log("Error en ingresos mensuales: " . $e->getMessage());
    $monthly_revenue = [];
}

// === ESTADÍSTICAS AVANZADAS PARA GRÁFICOS ===

// Ventas por día (últimos 30 días)
$daily_sales = [];
try {
    $daily_sales = $db->query("
        SELECT 
            DATE(created_at) as sale_date,
            COUNT(*) as orders_count,
            SUM(total) as daily_revenue,
            AVG(total) as avg_order_value
        FROM orders 
        WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
        AND status NOT IN ('cancelled')
        GROUP BY DATE(created_at)
        ORDER BY sale_date ASC
    ")->fetchAll();
} catch (Exception $e) {
    error_log("Error en ventas diarias: " . $e->getMessage());
    $daily_sales = [];
}

// Ventas por hora del día (patrón de comportamiento)
$hourly_pattern = [];
try {
    $hourly_pattern = $db->query("
        SELECT 
            HOUR(created_at) as hour,
            COUNT(*) as orders_count,
            SUM(total) as revenue
        FROM orders 
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        AND status NOT IN ('cancelled')
        GROUP BY HOUR(created_at)
        ORDER BY hour ASC
    ")->fetchAll();
} catch (Exception $e) {
    error_log("Error en patrón horario: " . $e->getMessage());
    $hourly_pattern = [];
}

// Top productos más vendidos
$top_products = [];
try {
    $top_products = $db->query("
        SELECT 
            p.name,
            p.price,
            SUM(oi.quantity) as total_sold,
            SUM(oi.quantity * oi.price) as total_revenue,
            COUNT(DISTINCT o.id) as orders_count
        FROM order_items oi
        JOIN products p ON oi.product_id = p.id
        JOIN orders o ON oi.order_id = o.id
        WHERE o.status NOT IN ('cancelled')
        AND o.created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
        GROUP BY p.id
        ORDER BY total_sold DESC
        LIMIT 10
    ")->fetchAll();
} catch (Exception $e) {
    error_log("Error en top productos: " . $e->getMessage());
    $top_products = [];
}

// Movimientos de inventario detallados
$inventory_movements = [];
try {
    $inventory_movements = $db->query("
        SELECT 
            DATE(created_at) as movement_date,
            movement_type,
            SUM(CASE WHEN quantity_change > 0 THEN quantity_change ELSE 0 END) as entries,
            SUM(CASE WHEN quantity_change < 0 THEN ABS(quantity_change) ELSE 0 END) as exits,
            COUNT(*) as movements_count
        FROM inventory_movements 
        WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
        GROUP BY DATE(created_at), movement_type
        ORDER BY movement_date ASC
    ")->fetchAll();
} catch (Exception $e) {
    error_log("Error en movimientos de inventario: " . $e->getMessage());
    $inventory_movements = [];
}

// Estadísticas de categorías
$category_stats = [];
try {
    $category_stats = $db->query("
        SELECT 
            c.name as category_name,
            COUNT(p.id) as products_count,
            SUM(CASE WHEN p.stock > 0 THEN 1 ELSE 0 END) as in_stock_count,
            SUM(p.stock) as total_stock,
            AVG(p.price) as avg_price
        FROM categories c
        LEFT JOIN products p ON c.id = p.category_id
        WHERE c.is_active = 1
        GROUP BY c.id
        ORDER BY products_count DESC
        LIMIT 10
    ")->fetchAll();
} catch (Exception $e) {
    error_log("Error en estadísticas de categorías: " . $e->getMessage());
    $category_stats = [];
}

// Estadísticas de marcas
$brand_stats = [];
try {
    $brand_stats = $db->query("
        SELECT 
            b.name as brand_name,
            COUNT(p.id) as products_count,
            SUM(p.stock) as total_stock,
            AVG(p.price) as avg_price,
            SUM(CASE WHEN oi.quantity IS NOT NULL THEN oi.quantity ELSE 0 END) as total_sold
        FROM brands b
        LEFT JOIN products p ON b.id = p.brand_id
        LEFT JOIN order_items oi ON p.id = oi.product_id
        LEFT JOIN orders o ON oi.order_id = o.id AND o.status NOT IN ('cancelled')
        WHERE b.is_active = 1
        GROUP BY b.id
        ORDER BY total_sold DESC
        LIMIT 10
    ")->fetchAll();
} catch (Exception $e) {
    error_log("Error en estadísticas de marcas: " . $e->getMessage());
    $brand_stats = [];
}

// Resumen de efectivo y flujo
$cash_flow = [];
try {
    $cash_flow = $db->query("
        SELECT 
            DATE(created_at) as flow_date,
            SUM(CASE WHEN status IN ('delivered', 'completed') THEN total ELSE 0 END) as income,
            COUNT(CASE WHEN status IN ('delivered', 'completed') THEN 1 END) as completed_orders,
            COUNT(CASE WHEN status = 'cancelled' THEN 1 END) as cancelled_orders,
            SUM(CASE WHEN status = 'cancelled' THEN total ELSE 0 END) as lost_revenue
        FROM orders
        WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
        GROUP BY DATE(created_at)
        ORDER BY flow_date ASC
    ")->fetchAll();
} catch (Exception $e) {
    error_log("Error en flujo de efectivo: " . $e->getMessage());
    $cash_flow = [];
}
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
                            50: '#fdf2f8', 100: '#fce7f3', 200: '#fbcfe8', 300: '#f9a8d4',
                            400: '#f472b6', 500: '#ec4899', 600: '#db2777', 700: '#be185d',
                            800: '#9d174d', 900: '#831843'
                        },
                        admin: {
                            50: '#f8fafc', 100: '#f1f5f9', 200: '#e2e8f0', 300: '#cbd5e1',
                            400: '#94a3b8', 500: '#64748b', 600: '#475569', 700: '#334155',
                            800: '#1e293b', 900: '#0f172a'
                        }
                    }
                }
            }
        }
    </script>
    
    <style>
        .stat-card:hover {
            transform: translateY(-2px);
            transition: all 0.2s ease-in-out;
        }
        
        .dashboard-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
            transition: all 0.2s ease-in-out;
        }
        
        .dashboard-card:hover {
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }
        
        /* Fix para gráficos infinitos */
        .chart-container {
            position: relative;
            height: 300px;
            width: 100%;
        }
        
        canvas {
            max-height: 300px !important;
        }
    </style>
</head>
<body class="bg-gray-50">
    <div class="flex h-screen overflow-hidden">
        <?php include 'includes/sidebar.php'; ?>
        
        <div class="flex-1 flex flex-col overflow-hidden">
            <?php include 'includes/header.php'; ?>
            
            <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-50 p-4 lg:p-6">
                <!-- Page Header -->
                <div class="mb-8">
                    <div class="flex items-center justify-between">
                        <div>
                            <h1 class="text-3xl font-bold text-gray-900">
                                Dashboard Administrativo
                            </h1>
                            <p class="text-gray-600 mt-1">
                                Bienvenido, <?php echo htmlspecialchars($adminData['full_name'] ?? 'Administrador'); ?> - <?php echo date('l, d F Y'); ?>
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
                            <button onclick="location.reload()" class="bg-primary-600 text-white px-4 py-2 rounded-lg hover:bg-primary-700 transition-colors">
                                <i class="fas fa-sync-alt mr-2"></i>
                                Actualizar
                            </button>
                        </div>
                    </div>
                </div>

                <!-- KPI Cards -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                    <!-- Total de pedidos -->
                    <div class="dashboard-card p-6">
                        <div class="flex items-center">
                            <div class="p-3 rounded-xl bg-blue-100 text-blue-600">
                                <i class="fas fa-shopping-cart text-xl"></i>
                            </div>
                            <div class="ml-4">
                                <h3 class="text-sm font-medium text-gray-500">Total Pedidos</h3>
                                <p class="text-2xl font-bold text-gray-900"><?= number_format($orders_stats['total_orders']) ?></p>
                                <p class="text-xs text-green-600">
                                    <i class="fas fa-plus"></i> <?= $orders_stats['today_orders'] ?> hoy
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Ingresos totales -->
                    <div class="dashboard-card p-6">
                        <div class="flex items-center">
                            <div class="p-3 rounded-xl bg-green-100 text-green-600">
                                <i class="fas fa-dollar-sign text-xl"></i>
                            </div>
                            <div class="ml-4">
                                <h3 class="text-sm font-medium text-gray-500">Ingresos Totales</h3>
                                <p class="text-2xl font-bold text-gray-900">$<?= number_format($orders_stats['total_revenue'], 2) ?></p>
                            </div>
                        </div>
                    </div>

                    <!-- Productos -->
                    <div class="dashboard-card p-6">
                        <div class="flex items-center">
                            <div class="p-3 rounded-xl bg-purple-100 text-purple-600">
                                <i class="fas fa-boxes text-xl"></i>
                            </div>
                            <div class="ml-4">
                                <h3 class="text-sm font-medium text-gray-500">Productos</h3>
                                <p class="text-2xl font-bold text-gray-900"><?= number_format($products_stats['total_products']) ?></p>
                                <?php if ($products_stats['low_stock_products'] > 0): ?>
                                    <p class="text-xs text-red-600">
                                        <i class="fas fa-exclamation-triangle"></i> <?= $products_stats['low_stock_products'] ?> stock bajo
                                    </p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Usuarios -->
                    <div class="dashboard-card p-6">
                        <div class="flex items-center">
                            <div class="p-3 rounded-xl bg-orange-100 text-orange-600">
                                <i class="fas fa-users text-xl"></i>
                            </div>
                            <div class="ml-4">
                                <h3 class="text-sm font-medium text-gray-500">Clientes</h3>
                                <p class="text-2xl font-bold text-gray-900"><?= number_format($users_stats['total_users']) ?></p>
                                <p class="text-xs text-green-600">
                                    <i class="fas fa-plus"></i> <?= $users_stats['today_registrations'] ?> hoy
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Estado de Pedidos -->
                <div class="dashboard-card p-6 mb-8">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Estado de Pedidos</h3>
                    <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
                        <div class="text-center p-3 bg-yellow-50 rounded-lg border border-yellow-200">
                            <div class="text-2xl font-bold text-yellow-600"><?= $orders_stats['pending_orders'] ?></div>
                            <div class="text-sm text-gray-600">Pendientes</div>
                        </div>
                        <div class="text-center p-3 bg-blue-50 rounded-lg border border-blue-200">
                            <div class="text-2xl font-bold text-blue-600"><?= $orders_stats['confirmed_orders'] ?></div>
                            <div class="text-sm text-gray-600">Confirmados</div>
                        </div>
                        <div class="text-center p-3 bg-purple-50 rounded-lg border border-purple-200">
                            <div class="text-2xl font-bold text-purple-600"><?= $orders_stats['shipped_orders'] ?></div>
                            <div class="text-sm text-gray-600">Enviados</div>
                        </div>
                        <div class="text-center p-3 bg-green-50 rounded-lg border border-green-200">
                            <div class="text-2xl font-bold text-green-600"><?= $orders_stats['delivered_orders'] ?></div>
                            <div class="text-sm text-gray-600">Entregados</div>
                        </div>
                        <div class="text-center p-3 bg-red-50 rounded-lg border border-red-200">
                            <div class="text-2xl font-bold text-red-600"><?= $orders_stats['cancelled_orders'] ?></div>
                            <div class="text-sm text-gray-600">Cancelados</div>
                        </div>
                    </div>
                </div>

                <!-- Primera fila de gráficos -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                    <!-- Gráfico de ventas diarias -->
                    <div class="dashboard-card p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Ventas Diarias (Últimos 30 días)</h3>
                        <div class="chart-container">
                            <canvas id="dailySalesChart"></canvas>
                        </div>
                    </div>

                    <!-- Gráfico de ingresos vs órdenes -->
                    <div class="dashboard-card p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Ingresos y Órdenes</h3>
                        <div class="chart-container">
                            <canvas id="revenueOrdersChart"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Segunda fila de gráficos -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                    <!-- Gráfico de patrón horario -->
                    <div class="dashboard-card p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Patrón de Ventas por Hora</h3>
                        <div class="chart-container">
                            <canvas id="hourlyPatternChart"></canvas>
                        </div>
                    </div>

                    <!-- Gráfico de flujo de inventario -->
                    <div class="dashboard-card p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Movimientos de Inventario</h3>
                        <div class="chart-container">
                            <canvas id="inventoryFlowChart"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Tercera fila de gráficos -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
                    <!-- Top productos -->
                    <div class="dashboard-card p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Productos Más Vendidos</h3>
                        <div class="chart-container">
                            <canvas id="topProductsChart"></canvas>
                        </div>
                    </div>

                    <!-- Estadísticas por categoría -->
                    <div class="dashboard-card p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Productos por Categoría</h3>
                        <div class="chart-container">
                            <canvas id="categoryChart"></canvas>
                        </div>
                    </div>

                    <!-- Estado de stock general -->
                    <div class="dashboard-card p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Estado del Stock</h3>
                        <div class="chart-container">
                            <canvas id="stockStatusChart"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Cuarta fila - Gráficos de análisis avanzado -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                    <!-- Flujo de efectivo -->
                    <div class="dashboard-card p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Flujo de Efectivo</h3>
                        <div class="chart-container">
                            <canvas id="cashFlowChart"></canvas>
                        </div>
                    </div>

                    <!-- Rendimiento por marcas -->
                    <div class="dashboard-card p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Rendimiento por Marcas</h3>
                        <div class="chart-container">
                            <canvas id="brandPerformanceChart"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Quinta fila - Stock y alertas -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                    <!-- Productos con stock bajo -->
                    <div class="dashboard-card p-6">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-lg font-semibold text-gray-900">Alertas de Stock Bajo</h3>
                            <a href="gestion-inventario.php" class="text-primary-600 hover:text-primary-800 text-sm font-medium">Ver todo</a>
                        </div>
                        <div class="space-y-3 max-h-64 overflow-y-auto">
                            <?php if (empty($low_stock_products)): ?>
                                <div class="text-center py-8 text-gray-500">
                                    <i class="fas fa-check-circle text-3xl mb-3 text-green-500"></i>
                                    <p class="text-sm">Todos los productos tienen stock suficiente</p>
                                </div>
                            <?php else: ?>
                                <?php foreach ($low_stock_products as $product): ?>
                                    <div class="flex justify-between items-center p-3 bg-red-50 rounded-lg border-l-4 border-red-400 hover:bg-red-100 transition-colors">
                                        <div>
                                            <h4 class="font-medium text-sm text-gray-900"><?= htmlspecialchars($product['name']) ?></h4>
                                            <p class="text-xs text-gray-600">SKU: <?= htmlspecialchars($product['sku'] ?? 'N/A') ?></p>
                                        </div>
                                        <div class="text-right">
                                            <span class="text-sm font-bold text-red-600"><?= $product['stock'] ?></span>
                                            <span class="text-xs text-gray-500">/ <?= $product['min_stock'] ?></span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Tablas de Información -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Pedidos recientes -->
                    <div class="dashboard-card p-6">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-lg font-semibold text-gray-900">Pedidos Recientes</h3>
                            <a href="gestion-pedidos.php" class="text-primary-600 hover:text-primary-800 text-sm font-medium">Ver todos</a>
                        </div>
                        <div class="space-y-3">
                            <?php if (isset($recent_orders) && is_array($recent_orders) && !empty($recent_orders)): ?>
                                <?php foreach ($recent_orders as $order): ?>
                                <?php
                                $status_colors = [
                                    'pending' => 'bg-yellow-100 text-yellow-800',
                                    'confirmed' => 'bg-blue-100 text-blue-800',
                                    'shipped' => 'bg-purple-100 text-purple-800',
                                    'delivered' => 'bg-green-100 text-green-800',
                                    'cancelled' => 'bg-red-100 text-red-800'
                                ];
                                $status_labels = [
                                    'pending' => 'Pendiente',
                                    'confirmed' => 'Confirmado',
                                    'shipped' => 'Enviado',
                                    'delivered' => 'Entregado',
                                    'cancelled' => 'Cancelado'
                                ];
                                ?>
                                <div class="flex justify-between items-center p-3 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors">
                                    <div>
                                        <h4 class="font-medium text-sm text-gray-900">Pedido #<?= str_pad($order['id'], 6, '0', STR_PAD_LEFT) ?></h4>
                                        <p class="text-xs text-gray-600"><?= htmlspecialchars($order['shipping_name'] ?? 'Cliente N/A') ?></p>
                                        <p class="text-xs text-gray-500"><?= $order['items_count'] ?> item(s)</p>
                                    </div>
                                    <div class="text-right">
                                        <span class="text-sm font-bold text-gray-900">$<?= number_format($order['total'], 2) ?></span>
                                        <div class="mt-1">
                                            <span class="px-2 py-1 text-xs rounded-full <?= $status_colors[$order['status']] ?? 'bg-gray-100 text-gray-800' ?>">
                                                <?= $status_labels[$order['status']] ?? ucfirst($order['status']) ?>
                                            </span>
                                        </div>
                                        <p class="text-xs text-gray-500 mt-1"><?= date('d/m/Y H:i', strtotime($order['created_at'])) ?></p>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="text-center py-8 text-gray-500">
                                    <i class="fas fa-inbox text-3xl mb-3"></i>
                                    <p class="text-sm">No hay pedidos recientes</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Movimientos de inventario recientes -->
                    <div class="dashboard-card p-6">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-lg font-semibold text-gray-900">Movimientos de Inventario</h3>
                            <a href="gestion-inventario.php" class="text-primary-600 hover:text-primary-800 text-sm font-medium">Ver historial</a>
                        </div>
                        <div class="space-y-3">
                            <?php if (empty($recent_inventory)): ?>
                                <div class="text-center py-8 text-gray-500">
                                    <i class="fas fa-warehouse text-3xl mb-3"></i>
                                    <p class="text-sm">No hay movimientos recientes</p>
                                </div>
                            <?php else: ?>
                                <?php foreach ($recent_inventory as $movement): ?>
                                    <?php
                                    $movement_icons = [
                                        'sale' => 'fas fa-minus text-red-500',
                                        'restock' => 'fas fa-plus text-green-500',
                                        'adjustment' => 'fas fa-edit text-blue-500',
                                        'return' => 'fas fa-undo text-orange-500'
                                    ];
                                    ?>
                                    <div class="flex justify-between items-center p-3 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors">
                                        <div class="flex items-center">
                                            <i class="<?= $movement_icons[$movement['movement_type']] ?? 'fas fa-exchange-alt text-gray-500' ?> mr-2"></i>
                                            <div>
                                                <h4 class="font-medium text-sm text-gray-900"><?= htmlspecialchars($movement['product_name']) ?></h4>
                                                <p class="text-xs text-gray-600"><?= htmlspecialchars($movement['reason'] ?? ucfirst($movement['movement_type'])) ?></p>
                                            </div>
                                        </div>
                                        <div class="text-right">
                                            <span class="text-sm font-bold <?= $movement['quantity_change'] > 0 ? 'text-green-600' : 'text-red-600' ?>">
                                                <?= $movement['quantity_change'] > 0 ? '+' : '' ?><?= $movement['quantity_change'] ?>
                                            </span>
                                            <p class="text-xs text-gray-500"><?= date('d/m/Y H:i', strtotime($movement['created_at'])) ?></p>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Scripts -->
    <script>
        // Configuración de colores para gráficos
        const chartColors = {
            primary: 'rgb(236, 72, 153)',
            primaryLight: 'rgba(236, 72, 153, 0.1)',
            secondary: 'rgb(59, 130, 246)',
            secondaryLight: 'rgba(59, 130, 246, 0.1)',
            success: 'rgb(34, 197, 94)',
            successLight: 'rgba(34, 197, 94, 0.1)',
            warning: 'rgb(245, 158, 11)',
            warningLight: 'rgba(245, 158, 11, 0.1)',
            danger: 'rgb(239, 68, 68)',
            dangerLight: 'rgba(239, 68, 68, 0.1)',
            info: 'rgb(168, 85, 247)',
            infoLight: 'rgba(168, 85, 247, 0.1)'
        };

        // 1. Gráfico de ventas diarias
        const dailySalesCtx = document.getElementById('dailySalesChart').getContext('2d');
        new Chart(dailySalesCtx, {
            type: 'line',
            data: {
                labels: [
                    <?php 
                    $dates = [];
                    $revenues = [];
                    $orderCounts = [];
                    
                    // Rellenar con datos reales únicamente
                    if (!empty($daily_sales)) {
                        foreach ($daily_sales as $sale) {
                            echo '"' . date('d M', strtotime($sale['sale_date'])) . '",';
                            $revenues[] = $sale['daily_revenue'];
                            $orderCounts[] = $sale['orders_count'];
                        }
                    }
                    ?>
                ],
                datasets: [{
                    label: 'Ingresos ($)',
                    data: [<?= implode(',', $revenues) ?>],
                    borderColor: chartColors.primary,
                    backgroundColor: chartColors.primaryLight,
                    yAxisID: 'y',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    mode: 'index',
                    intersect: false,
                },
                scales: {
                    y: {
                        type: 'linear',
                        display: true,
                        position: 'left',
                        ticks: {
                            callback: function(value) {
                                return '$' + value.toLocaleString();
                            }
                        }
                    }
                },
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return 'Ingresos: $' + context.parsed.y.toLocaleString();
                            }
                        }
                    },
                    legend: {
                        display: false
                    }
                }
            }
        });

        // 2. Gráfico de ingresos vs órdenes
        const revenueOrdersCtx = document.getElementById('revenueOrdersChart').getContext('2d');
        new Chart(revenueOrdersCtx, {
            type: 'bar',
            data: {
                labels: [
                    <?php 
                    if (!empty($daily_sales)) {
                        foreach (array_slice($daily_sales, -14) as $sale) {
                            echo '"' . date('d M', strtotime($sale['sale_date'])) . '",';
                        }
                    }
                    ?>
                ],
                datasets: [{
                    label: 'Ingresos ($)',
                    data: [<?= implode(',', array_slice($revenues, -14)) ?>],
                    backgroundColor: chartColors.primaryLight,
                    borderColor: chartColors.primary,
                    borderWidth: 1,
                    yAxisID: 'y'
                }, {
                    label: 'Órdenes',
                    data: [<?= implode(',', array_slice($orderCounts, -14)) ?>],
                    backgroundColor: chartColors.secondaryLight,
                    borderColor: chartColors.secondary,
                    borderWidth: 1,
                    yAxisID: 'y1'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        type: 'linear',
                        display: true,
                        position: 'left',
                        ticks: {
                            callback: function(value) {
                                return '$' + value.toLocaleString();
                            }
                        }
                    },
                    y1: {
                        type: 'linear',
                        display: true,
                        position: 'right',
                        ticks: {
                            callback: function(value) {
                                return value + ' órdenes';
                            }
                        },
                        grid: {
                            drawOnChartArea: false,
                        }
                    }
                }
            }
        });

        // 3. Gráfico de patrón horario
        const hourlyPatternCtx = document.getElementById('hourlyPatternChart').getContext('2d');
        new Chart(hourlyPatternCtx, {
            type: 'radar',
            data: {
                labels: ['00:00', '03:00', '06:00', '09:00', '12:00', '15:00', '18:00', '21:00'],
                datasets: [{
                    label: 'Órdenes por hora',
                    data: [
                        <?php 
                        $hourlyData = array_fill(0, 8, 0);
                        if (!empty($hourly_pattern)) {
                            foreach ($hourly_pattern as $pattern) {
                                $hourIndex = floor($pattern['hour'] / 3);
                                $hourlyData[$hourIndex] += $pattern['orders_count'];
                            }
                        }
                        echo implode(',', $hourlyData);
                        ?>
                    ],
                    backgroundColor: chartColors.infoLight,
                    borderColor: chartColors.info,
                    pointBackgroundColor: chartColors.info,
                    pointBorderColor: '#fff',
                    pointHoverBackgroundColor: '#fff',
                    pointHoverBorderColor: chartColors.info
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                elements: {
                    line: {
                        borderWidth: 3
                    }
                }
            }
        });

        // 4. Gráfico de flujo de inventario
        const inventoryFlowCtx = document.getElementById('inventoryFlowChart').getContext('2d');
        new Chart(inventoryFlowCtx, {
            type: 'line',
            data: {
                labels: [
                    <?php 
                    $entryData = [];
                    $exitData = [];
                    if (!empty($inventory_movements)) {
                        $movementsByDate = [];
                        foreach ($inventory_movements as $movement) {
                            $date = $movement['movement_date'];
                            if (!isset($movementsByDate[$date])) {
                                $movementsByDate[$date] = ['entries' => 0, 'exits' => 0];
                            }
                            $movementsByDate[$date]['entries'] += $movement['entries'];
                            $movementsByDate[$date]['exits'] += $movement['exits'];
                        }
                        
                        foreach ($movementsByDate as $date => $data) {
                            echo '"' . date('d M', strtotime($date)) . '",';
                            $entryData[] = $data['entries'];
                            $exitData[] = $data['exits'];
                        }
                    }
                    ?>
                ],
                datasets: [{
                    label: 'Entradas',
                    data: [<?= implode(',', $entryData) ?>],
                    borderColor: chartColors.success,
                    backgroundColor: chartColors.successLight,
                    tension: 0.4,
                    fill: '+1'
                }, {
                    label: 'Salidas',
                    data: [<?= implode(',', $exitData) ?>],
                    borderColor: chartColors.danger,
                    backgroundColor: chartColors.dangerLight,
                    tension: 0.4,
                    fill: 'origin'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    mode: 'index',
                    intersect: false,
                },
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return context.dataset.label + ': ' + context.parsed.y + ' unidades';
                            }
                        }
                    }
                }
            }
        });

        // 5. Top productos (Doughnut chart)
        const topProductsCtx = document.getElementById('topProductsChart').getContext('2d');
        new Chart(topProductsCtx, {
            type: 'doughnut',
            data: {
                labels: [
                    <?php 
                    if (!empty($top_products)) {
                        foreach (array_slice($top_products, 0, 5) as $product) {
                            echo '"' . htmlspecialchars($product['name']) . '",';
                        }
                    }
                    ?>
                ],
                datasets: [{
                    data: [
                        <?php 
                        if (!empty($top_products)) {
                            foreach (array_slice($top_products, 0, 5) as $product) {
                                echo $product['total_sold'] . ',';
                            }
                        }
                        ?>
                    ],
                    backgroundColor: [
                        chartColors.primary,
                        chartColors.secondary,
                        chartColors.success,
                        chartColors.warning,
                        chartColors.info
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });

        // 6. Gráfico de categorías
        const categoryCtx = document.getElementById('categoryChart').getContext('2d');
        new Chart(categoryCtx, {
            type: 'polarArea',
            data: {
                labels: [
                    <?php 
                    if (!empty($category_stats)) {
                        foreach ($category_stats as $category) {
                            echo '"' . htmlspecialchars($category['category_name']) . '",';
                        }
                    }
                    ?>
                ],
                datasets: [{
                    data: [
                        <?php 
                        if (!empty($category_stats)) {
                            foreach ($category_stats as $category) {
                                echo $category['products_count'] . ',';
                            }
                        }
                        ?>
                    ],
                    backgroundColor: [
                        chartColors.primaryLight,
                        chartColors.secondaryLight,
                        chartColors.successLight,
                        chartColors.warningLight,
                        chartColors.infoLight
                    ],
                    borderColor: [
                        chartColors.primary,
                        chartColors.secondary,
                        chartColors.success,
                        chartColors.warning,
                        chartColors.info
                    ],
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false
            }
        });

        // 7. Estado del stock
        const stockStatusCtx = document.getElementById('stockStatusChart').getContext('2d');
        new Chart(stockStatusCtx, {
            type: 'pie',
            data: {
                labels: ['Stock Normal', 'Stock Bajo', 'Sin Stock'],
                datasets: [{
                    data: [
                        <?= ($products_stats['total_products'] - $products_stats['low_stock_products'] - $products_stats['out_of_stock_products']) ?>,
                        <?= $products_stats['low_stock_products'] ?>,
                        <?= $products_stats['out_of_stock_products'] ?>
                    ],
                    backgroundColor: [
                        chartColors.success,
                        chartColors.warning,
                        chartColors.danger
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });

        // 8. Flujo de efectivo
        const cashFlowCtx = document.getElementById('cashFlowChart').getContext('2d');
        new Chart(cashFlowCtx, {
            type: 'line',
            data: {
                labels: [
                    <?php 
                    if (!empty($cash_flow)) {
                        foreach (array_slice($cash_flow, -14) as $flow) {
                            echo '"' . date('d M', strtotime($flow['flow_date'])) . '",';
                        }
                    } else {
                        for ($i = 13; $i >= 0; $i--) {
                            echo '"' . date('d M', strtotime("-$i days")) . '",';
                        }
                    }
                    ?>
                ],
                datasets: [{
                    label: 'Ingresos Completados',
                    data: [
                        <?php 
                        if (!empty($cash_flow)) {
                            foreach (array_slice($cash_flow, -14) as $flow) {
                                echo $flow['income'] . ',';
                            }
                        }
                        ?>
                    ],
                    borderColor: chartColors.success,
                    backgroundColor: chartColors.successLight,
                    tension: 0.4,
                    fill: true
                }, {
                    label: 'Ingresos Perdidos',
                    data: [
                        <?php 
                        if (!empty($cash_flow)) {
                            foreach (array_slice($cash_flow, -14) as $flow) {
                                echo $flow['lost_revenue'] . ',';
                            }
                        }
                        ?>
                    ],
                    borderColor: chartColors.danger,
                    backgroundColor: chartColors.dangerLight,
                    tension: 0.4,
                    fill: false
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    mode: 'index',
                    intersect: false,
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return '$' + value.toLocaleString();
                            }
                        }
                    }
                }
            }
        });

        // 9. Rendimiento por marcas
        const brandPerformanceCtx = document.getElementById('brandPerformanceChart').getContext('2d');
        new Chart(brandPerformanceCtx, {
            type: 'bar',
            data: {
                labels: [
                    <?php 
                    if (!empty($brand_stats)) {
                        foreach (array_slice($brand_stats, 0, 6) as $brand) {
                            echo '"' . htmlspecialchars($brand['brand_name']) . '",';
                        }
                    } else {
                        echo '"MAC", "Maybelline", "L\'Oreal", "Revlon", "CoverGirl", "NYX",';
                    }
                    ?>
                ],
                datasets: [{
                    label: 'Productos Vendidos',
                    data: [
                        <?php 
                        if (!empty($brand_stats)) {
                            foreach (array_slice($brand_stats, 0, 6) as $brand) {
                                echo $brand['total_sold'] . ',';
                            }
                        } else {
                            echo '150, 120, 95, 80, 65, 45,';
                        }
                        ?>
                    ],
                    backgroundColor: chartColors.primaryLight,
                    borderColor: chartColors.primary,
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                indexAxis: 'y',
                scales: {
                    x: {
                        beginAtZero: true
                    }
                }
            }
        });

        // Actualizar última actualización cada minuto
        setInterval(function() {
            document.getElementById('last-update').textContent = new Date().toLocaleTimeString('es-ES', {
                hour: '2-digit',
                minute: '2-digit'
            });
        }, 60000);

        // Funciones adicionales para el dashboard
        function refreshStats() {
            location.reload();
        }

        // Auto-refresh cada 5 minutos
        setTimeout(refreshStats, 300000);
    </script>
</body>
</html>    <script>
        // Gráfico de ingresos mensuales
        const revenueData = <?= json_encode($monthly_revenue) ?>;
        const ctx = document.getElementById('revenueChart').getContext('2d');
        
        const chart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: revenueData.map(item => {
                    const [year, month] = item.month.split('-');
                    const monthNames = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
                    return monthNames[parseInt(month) - 1] + ' ' + year;
                }).reverse(),
                datasets: [{
                    label: 'Ingresos ($)',
                    data: revenueData.map(item => parseFloat(item.revenue)).reverse(),
                    borderColor: 'rgb(59, 130, 246)',
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
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
                    }
                }
            }
        });

        // Toggle notificaciones
        document.getElementById('notifications-btn').addEventListener('click', function(e) {
            e.stopPropagation();
            const dropdown = document.getElementById('notifications-dropdown');
            dropdown.classList.toggle('hidden');
        });

        // Cerrar dropdown al hacer click fuera
        document.addEventListener('click', function() {
            document.getElementById('notifications-dropdown').classList.add('hidden');
        });

        // Marcar todas las notificaciones como leídas
        function markAllAsRead() {
            fetch('api/notifications.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'mark_all_read'
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                }
            });
        }

        // Auto-refresh cada 30 segundos para notificaciones
        setInterval(() => {
            fetch('api/notifications.php?action=get_count')
            .then(response => response.json())
            .then(data => {
                const badge = document.querySelector('.notification-badge');
                if (data.count > 0) {
                    if (!badge) {
                        const btn = document.getElementById('notifications-btn');
                        const newBadge = document.createElement('span');
                        newBadge.className = 'notification-badge absolute -top-2 -right-2 bg-red-500 text-white text-xs rounded-full h-6 w-6 flex items-center justify-center';
                        newBadge.textContent = Math.min(data.count, 99);
                        btn.appendChild(newBadge);
                    } else {
                        badge.textContent = Math.min(data.count, 99);
                    }
                } else if (badge) {
                    badge.remove();
                }
            });
        }, 30000);
    </script>
</body>
</html>
