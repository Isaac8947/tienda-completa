<?php
session_start();
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../models/Product.php';
require_once '../models/Order.php';
require_once '../models/Customer.php';
require_once '../models/News.php';
require_once '../admin/auth-check.php';

// Inicializar modelos
$productModel = new Product();
$orderModel = new Order();
$customerModel = new Customer();
$newsModel = new News();

// Función para obtener métricas de rendimiento del sitio
function getPerformanceMetrics() {
    $database = new Database();
    $db = $database->getConnection();
    
    // Tasa de conversión (órdenes vs visitantes únicos)
    $sql = "SELECT 
                COUNT(DISTINCT customer_id) as unique_customers,
                COUNT(*) as total_orders,
                AVG(total) as avg_order_value,
                (COUNT(*) / COUNT(DISTINCT customer_id)) as orders_per_customer
            FROM orders 
            WHERE status != 'cancelled'
            AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
    
    $stmt = $db->prepare($sql);
    $stmt->execute();
    $orderMetrics = $stmt->fetch();
    
    // Métricas de productos
    $sql = "SELECT 
                COUNT(*) as total_products,
                SUM(CASE WHEN inventory_quantity = 0 THEN 1 ELSE 0 END) as out_of_stock,
                SUM(CASE WHEN inventory_quantity < 10 THEN 1 ELSE 0 END) as low_stock,
                AVG(price) as avg_price
            FROM products 
            WHERE status = 'active'";
    
    $stmt = $db->prepare($sql);
    $stmt->execute();
    $productMetrics = $stmt->fetch();
    
    // Métricas de clientes
    $sql = "SELECT 
                COUNT(*) as total_customers,
                COUNT(CASE WHEN email_verified = 1 THEN 1 END) as verified_customers,
                COUNT(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 END) as new_customers_30d
            FROM customers";
    
    $stmt = $db->prepare($sql);
    $stmt->execute();
    $customerMetrics = $stmt->fetch();
    
    // Métricas de contenido
    $newsModel = new News();
    $newsStats = $newsModel->getStats();
    
    return [
        'orders' => $orderMetrics,
        'products' => $productMetrics,
        'customers' => $customerMetrics,
        'content' => $newsStats
    ];
}

// Función para obtener productos con mejor y peor rendimiento
function getProductPerformance() {
    $database = new Database();
    $db = $database->getConnection();
    
    // Productos más vendidos (últimos 30 días)
    $sql = "SELECT 
                p.id, p.name, p.main_image, p.price,
                COALESCE(SUM(oi.quantity), 0) as total_sold,
                COALESCE(SUM(oi.quantity * oi.price), 0) as revenue
            FROM products p
            LEFT JOIN order_items oi ON p.id = oi.product_id
            LEFT JOIN orders o ON oi.order_id = o.id
            WHERE o.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            AND o.status != 'cancelled'
            GROUP BY p.id
            HAVING total_sold > 0
            ORDER BY total_sold DESC
            LIMIT 5";
    
    $stmt = $db->prepare($sql);
    $stmt->execute();
    $topProducts = $stmt->fetchAll();
    
    // Productos con stock bajo
    $sql = "SELECT id, name, inventory_quantity, price
            FROM products 
            WHERE inventory_quantity < 10 AND status = 'active'
            ORDER BY inventory_quantity ASC
            LIMIT 10";
    
    $stmt = $db->prepare($sql);
    $stmt->execute();
    $lowStockProducts = $stmt->fetchAll();
    
    return [
        'top_products' => $topProducts,
        'low_stock' => $lowStockProducts
    ];
}

// Función para obtener tendencias de crecimiento
function getGrowthTrends() {
    $database = new Database();
    $db = $database->getConnection();
    
    // Crecimiento de ventas mes a mes
    $sql = "SELECT 
                YEAR(created_at) as year,
                MONTH(created_at) as month,
                COUNT(*) as orders,
                SUM(total) as revenue
            FROM orders 
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
            AND status != 'cancelled'
            GROUP BY YEAR(created_at), MONTH(created_at)
            ORDER BY year DESC, month DESC";
    
    $stmt = $db->prepare($sql);
    $stmt->execute();
    $monthlyGrowth = $stmt->fetchAll();
    
    // Crecimiento de clientes
    $sql = "SELECT 
                YEAR(created_at) as year,
                MONTH(created_at) as month,
                COUNT(*) as new_customers
            FROM customers 
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
            GROUP BY YEAR(created_at), MONTH(created_at)
            ORDER BY year DESC, month DESC";
    
    $stmt = $db->prepare($sql);
    $stmt->execute();
    $customerGrowth = $stmt->fetchAll();
    
    return [
        'monthly_sales' => $monthlyGrowth,
        'customer_growth' => $customerGrowth
    ];
}

// Obtener datos
$metrics = getPerformanceMetrics();
$productPerformance = getProductPerformance();
$growthTrends = getGrowthTrends();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Métricas de Rendimiento - Odisea Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
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
                        secondary: {
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        /* Asegurar que los gráficos no crezcan sin control */
        .chart-container {
            position: relative;
            height: 300px !important;
            width: 100% !important;
            overflow: hidden;
        }
        
        /* Fix para canvas de Chart.js */
        canvas {
            max-height: 300px !important;
        }
    </style>
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
                        <h1 class="text-2xl lg:text-3xl font-bold text-gray-900">Métricas de Rendimiento</h1>
                        <p class="text-gray-600 mt-1">Indicadores clave de rendimiento y análisis profundo</p>
                    </div>
                    <div class="flex space-x-3">
                        <a href="analytics.php" class="inline-flex items-center px-4 py-2 bg-secondary-600 hover:bg-secondary-700 text-white text-sm font-medium rounded-lg transition-colors">
                            <i class="fas fa-chart-bar mr-2"></i>
                            Analytics
                        </a>
                        <a href="reports.php" class="inline-flex items-center px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white text-sm font-medium rounded-lg transition-colors">
                            <i class="fas fa-file-export mr-2"></i>
                            Exportar
                        </a>
                    </div>
                </div>

                <!-- KPIs de Rendimiento -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-600">Tasa de Conversión</p>
                                <p class="text-2xl font-bold text-gray-900">
                                    <?php 
                                    $conversionRate = $metrics['orders']['unique_customers'] > 0 ? 
                                        ($metrics['orders']['total_orders'] / $metrics['orders']['unique_customers']) * 100 : 0;
                                    echo number_format($conversionRate, 1) . '%'; 
                                    ?>
                                </p>
                                <p class="text-xs text-gray-500">Órdenes por cliente único</p>
                            </div>
                            <div class="p-3 bg-green-100 rounded-lg">
                                <i class="fas fa-percentage text-green-600 text-xl"></i>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-600">Valor Promedio Orden</p>
                                <p class="text-2xl font-bold text-gray-900">$<?php echo number_format($metrics['orders']['avg_order_value'] ?? 0, 2); ?></p>
                                <p class="text-xs text-gray-500">Últimos 30 días</p>
                            </div>
                            <div class="p-3 bg-blue-100 rounded-lg">
                                <i class="fas fa-dollar-sign text-blue-600 text-xl"></i>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-600">Stock Crítico</p>
                                <p class="text-2xl font-bold text-gray-900"><?php echo number_format($metrics['products']['low_stock'] ?? 0); ?></p>
                                <p class="text-xs text-gray-500"><?php echo number_format($metrics['products']['out_of_stock'] ?? 0); ?> agotados</p>
                            </div>
                            <div class="p-3 bg-yellow-100 rounded-lg">
                                <i class="fas fa-exclamation-triangle text-yellow-600 text-xl"></i>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-600">Tasa Verificación</p>
                                <p class="text-2xl font-bold text-gray-900">
                                    <?php 
                                    $verificationRate = $metrics['customers']['total_customers'] > 0 ? 
                                        ($metrics['customers']['verified_customers'] / $metrics['customers']['total_customers']) * 100 : 0;
                                    echo number_format($verificationRate, 1) . '%'; 
                                    ?>
                                </p>
                                <p class="text-xs text-gray-500">Clientes verificados</p>
                            </div>
                            <div class="p-3 bg-purple-100 rounded-lg">
                                <i class="fas fa-user-check text-purple-600 text-xl"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Gráficos de Tendencias -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Tendencia de Ingresos Mensuales</h3>
                        <div class="chart-container">
                            <canvas id="revenueChart"></canvas>
                        </div>
                    </div>
                    
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Crecimiento de Clientes</h3>
                        <div class="chart-container">
                            <canvas id="customerGrowthChart"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Tablas de Rendimiento -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                    <!-- Top Products -->
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h3 class="text-lg font-semibold text-gray-900">Productos Top (30 días)</h3>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Producto</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Vendidos</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Ingresos</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200">
                                    <?php foreach ($productPerformance['top_products'] as $product): ?>
                                    <tr>
                                        <td class="px-6 py-4">
                                            <div class="flex items-center">
                                                <div class="h-8 w-8 flex-shrink-0">
                                                    <?php if (!empty($product['main_image'])): ?>
                                                    <img src="<?php echo BASE_URL . '/' . htmlspecialchars($product['main_image']); ?>" 
                                                         alt="<?php echo htmlspecialchars($product['name']); ?>" 
                                                         class="h-8 w-8 object-cover rounded">
                                                    <?php else: ?>
                                                    <div class="h-8 w-8 bg-gray-200 rounded flex items-center justify-center">
                                                        <i class="fas fa-image text-gray-400 text-xs"></i>
                                                    </div>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="ml-3">
                                                    <div class="text-sm font-medium text-gray-900">
                                                        <?php echo htmlspecialchars(substr($product['name'], 0, 25)) . (strlen($product['name']) > 25 ? '...' : ''); ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-900"><?php echo number_format($product['total_sold']); ?></td>
                                        <td class="px-6 py-4 text-sm text-gray-900">$<?php echo number_format($product['revenue'], 2); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                    
                                    <?php if (empty($productPerformance['top_products'])): ?>
                                    <tr>
                                        <td colspan="3" class="px-6 py-4 text-center text-gray-500">No hay datos de ventas</td>
                                    </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Low Stock Alert -->
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h3 class="text-lg font-semibold text-gray-900">Alertas de Stock Bajo</h3>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Producto</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Stock</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Estado</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200">
                                    <?php foreach (array_slice($productPerformance['low_stock'], 0, 5) as $product): ?>
                                    <tr>
                                        <td class="px-6 py-4">
                                            <div class="text-sm font-medium text-gray-900">
                                                <?php echo htmlspecialchars(substr($product['name'], 0, 30)) . (strlen($product['name']) > 30 ? '...' : ''); ?>
                                            </div>
                                            <div class="text-sm text-gray-500">$<?php echo number_format($product['price'], 2); ?></div>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-900"><?php echo $product['inventory_quantity']; ?></td>
                                        <td class="px-6 py-4">
                                            <?php if ($product['inventory_quantity'] == 0): ?>
                                            <span class="inline-flex px-2 py-1 text-xs font-medium bg-red-100 text-red-800 rounded-full">Agotado</span>
                                            <?php elseif ($product['inventory_quantity'] < 5): ?>
                                            <span class="inline-flex px-2 py-1 text-xs font-medium bg-red-100 text-red-800 rounded-full">Crítico</span>
                                            <?php else: ?>
                                            <span class="inline-flex px-2 py-1 text-xs font-medium bg-yellow-100 text-yellow-800 rounded-full">Bajo</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                    
                                    <?php if (empty($productPerformance['low_stock'])): ?>
                                    <tr>
                                        <td colspan="3" class="px-6 py-4 text-center text-gray-500">
                                            <i class="fas fa-check-circle text-green-500 mr-2"></i>
                                            Todos los productos tienen stock adecuado
                                        </td>
                                    </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Métricas Adicionales -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-6">Resumen de Métricas Clave</h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div class="border border-gray-200 rounded-lg p-4">
                            <h4 class="font-medium text-gray-900 mb-3">Análisis de Ventas</h4>
                            <div class="space-y-2 text-sm">
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Órdenes por cliente:</span>
                                    <span class="font-medium"><?php echo number_format($metrics['orders']['orders_per_customer'] ?? 0, 2); ?></span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Precio promedio producto:</span>
                                    <span class="font-medium">$<?php echo number_format($metrics['products']['avg_price'] ?? 0, 2); ?></span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Nuevos clientes (30d):</span>
                                    <span class="font-medium"><?php echo number_format($metrics['customers']['new_customers_30d'] ?? 0); ?></span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="border border-gray-200 rounded-lg p-4">
                            <h4 class="font-medium text-gray-900 mb-3">Estado del Inventario</h4>
                            <div class="space-y-2 text-sm">
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Productos activos:</span>
                                    <span class="font-medium"><?php echo number_format($metrics['products']['total_products'] ?? 0); ?></span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Stock bajo (<10):</span>
                                    <span class="font-medium text-yellow-600"><?php echo number_format($metrics['products']['low_stock'] ?? 0); ?></span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Agotados:</span>
                                    <span class="font-medium text-red-600"><?php echo number_format($metrics['products']['out_of_stock'] ?? 0); ?></span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="border border-gray-200 rounded-lg p-4">
                            <h4 class="font-medium text-gray-900 mb-3">Contenido y Engagement</h4>
                            <div class="space-y-2 text-sm">
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Noticias publicadas:</span>
                                    <span class="font-medium"><?php echo number_format($metrics['content']['published'] ?? 0); ?></span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Visualizaciones totales:</span>
                                    <span class="font-medium"><?php echo number_format($metrics['content']['total_views'] ?? 0); ?></span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Contenido destacado:</span>
                                    <span class="font-medium"><?php echo number_format($metrics['content']['featured'] ?? 0); ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script>
        // Gráfico de Ingresos Mensuales
        const revenueData = <?php echo json_encode($growthTrends['monthly_sales']); ?>;
        const revenueCtx = document.getElementById('revenueChart').getContext('2d');
        
        new Chart(revenueCtx, {
            type: 'line',
            data: {
                labels: revenueData.map(item => {
                    const months = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
                    return months[item.month - 1] + ' ' + item.year;
                }).reverse(),
                datasets: [{
                    label: 'Ingresos',
                    data: revenueData.map(item => parseFloat(item.revenue)).reverse(),
                    borderColor: '#ec4899',
                    backgroundColor: 'rgba(236, 72, 153, 0.1)',
                    tension: 0.4,
                    fill: true
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
                    }
                }
            }
        });

        // Gráfico de Crecimiento de Clientes
        const customerData = <?php echo json_encode($growthTrends['customer_growth']); ?>;
        const customerCtx = document.getElementById('customerGrowthChart').getContext('2d');
        
        new Chart(customerCtx, {
            type: 'bar',
            data: {
                labels: customerData.map(item => {
                    const months = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
                    return months[item.month - 1] + ' ' + item.year;
                }).reverse(),
                datasets: [{
                    label: 'Nuevos Clientes',
                    data: customerData.map(item => parseInt(item.new_customers)).reverse(),
                    backgroundColor: '#3b82f6',
                    borderColor: '#2563eb',
                    borderWidth: 1
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
                            stepSize: 1
                        }
                    }
                }
            }
        });
    </script>
</body>
</html>
