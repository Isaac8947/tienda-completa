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

// Obtener rango de fechas (por defecto últimos 30 días)
$endDate = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');
$startDate = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('-30 days'));

// Función para obtener estadísticas de ventas
function getSalesStats($startDate, $endDate) {
    $database = new Database();
    $db = $database->getConnection();
    
    $sql = "SELECT 
                COUNT(*) as total_orders,
                COALESCE(SUM(total), 0) as total_revenue,
                COALESCE(AVG(total), 0) as avg_order_value,
                COUNT(DISTINCT customer_id) as unique_customers
            FROM orders 
            WHERE created_at BETWEEN ? AND ?
            AND status != 'cancelled'";
    
    $stmt = $db->prepare($sql);
    $stmt->execute([$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
    
    return $stmt->fetch();
}

// Función para obtener ventas por día
function getDailySales($startDate, $endDate) {
    $database = new Database();
    $db = $database->getConnection();
    
    $sql = "SELECT 
                DATE(created_at) as date,
                COUNT(*) as orders,
                COALESCE(SUM(total), 0) as revenue
            FROM orders 
            WHERE created_at BETWEEN ? AND ?
            AND status != 'cancelled'
            GROUP BY DATE(created_at)
            ORDER BY date ASC";
    
    $stmt = $db->prepare($sql);
    $stmt->execute([$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
    
    return $stmt->fetchAll();
}

// Función para obtener productos más vendidos
function getTopProducts($startDate, $endDate) {
    $database = new Database();
    $db = $database->getConnection();
    
    $sql = "SELECT 
                p.name,
                p.main_image,
                p.price,
                COALESCE(SUM(oi.quantity), 0) as total_sold,
                COALESCE(SUM(oi.quantity * oi.price), 0) as total_revenue
            FROM products p
            LEFT JOIN order_items oi ON p.id = oi.product_id
            LEFT JOIN orders o ON oi.order_id = o.id
            WHERE o.created_at BETWEEN ? AND ?
            AND o.status != 'cancelled'
            GROUP BY p.id, p.name, p.main_image, p.price
            HAVING total_sold > 0
            ORDER BY total_sold DESC
            LIMIT 10";
    
    $stmt = $db->prepare($sql);
    $stmt->execute([$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
    
    return $stmt->fetchAll();
}

// Función para obtener estadísticas de clientes
function getCustomerStats($startDate, $endDate) {
    $database = new Database();
    $db = $database->getConnection();
    
    $sql = "SELECT 
                COUNT(*) as new_customers,
                COUNT(CASE WHEN email_verified = 1 THEN 1 END) as verified_customers
            FROM customers 
            WHERE created_at BETWEEN ? AND ?";
    
    $stmt = $db->prepare($sql);
    $stmt->execute([$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
    
    return $stmt->fetch();
}

// Función para obtener estados de órdenes
function getOrderStatuses($startDate, $endDate) {
    $database = new Database();
    $db = $database->getConnection();
    
    $sql = "SELECT 
                status,
                COUNT(*) as count,
                COALESCE(SUM(total), 0) as total_amount
            FROM orders 
            WHERE created_at BETWEEN ? AND ?
            GROUP BY status
            ORDER BY count DESC";
    
    $stmt = $db->prepare($sql);
    $stmt->execute([$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
    
    return $stmt->fetchAll();
}

// Función para obtener métodos de pago
function getPaymentMethods($startDate, $endDate) {
    $database = new Database();
    $db = $database->getConnection();
    
    $sql = "SELECT 
                payment_method,
                COUNT(*) as count,
                COALESCE(SUM(total), 0) as total_amount
            FROM orders 
            WHERE created_at BETWEEN ? AND ?
            AND status != 'cancelled'
            GROUP BY payment_method
            ORDER BY count DESC";
    
    $stmt = $db->prepare($sql);
    $stmt->execute([$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
    
    return $stmt->fetchAll();
}

// Obtener datos
$salesStats = getSalesStats($startDate, $endDate);
$dailySales = getDailySales($startDate, $endDate);
$topProducts = getTopProducts($startDate, $endDate);
$customerStats = getCustomerStats($startDate, $endDate);
$orderStatuses = getOrderStatuses($startDate, $endDate);
$paymentMethods = getPaymentMethods($startDate, $endDate);
$newsStats = $newsModel->getStats();

// Estadísticas generales del sistema
$totalCustomers = $customerModel->findAll();
$totalProducts = $productModel->findAll();
$totalOrders = $orderModel->findAll();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analytics y Reportes - Odisea Admin</title>
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
            z-index: 1;
        }
        
        /* Forzar sidebar visible y por encima de todo */
        #sidebar {
            z-index: 9999 !important;
            background-color: #1e293b !important;
            opacity: 1 !important;
            visibility: visible !important;
            position: fixed !important;
        }
        
        /* Asegurar que el contenido principal no interfiera */
        .main-content {
            position: relative;
            z-index: 1;
        }
        
        /* Fix para canvas de Chart.js */
        canvas {
            max-height: 300px !important;
            z-index: 1;
        }
        
        /* Asegurar que ningún elemento de Chart.js interfiera */
        .chartjs-render-monitor {
            z-index: 1 !important;
        }
        
        /* Forzar el contenedor principal a no interferir */
        body, html {
            overflow-x: hidden;
        }
        
        /* Container flex no debe interferir con sidebar */
        .flex.h-screen {
            position: relative;
            z-index: 1;
        }
        
        /* Sidebar debe estar siempre visible en desktop */
        @media (min-width: 768px) {
            #sidebar {
                position: relative !important;
                transform: translateX(0) !important;
                z-index: 9999 !important;
            }
        }
    </style>
</head>
<body class="bg-gray-50">
    <div class="flex h-screen overflow-hidden">
        <?php include '../admin/includes/sidebar.php'; ?>
        
        <div class="flex-1 flex flex-col overflow-hidden">
            <?php include '../admin/includes/header.php'; ?>
            
            <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-50 p-4 lg:p-6 main-content">
                <!-- Page Header -->
                <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between mb-8">
                    <div class="mb-4 lg:mb-0">
                        <h1 class="text-2xl lg:text-3xl font-bold text-gray-900">Analytics y Reportes</h1>
                        <p class="text-gray-600 mt-1">Estadísticas y métricas del rendimiento de la tienda</p>
                    </div>
                    
                    <!-- Filtro de fechas -->
                    <div class="flex flex-col sm:flex-row space-y-2 sm:space-y-0 sm:space-x-3">
                        <form method="GET" class="flex space-x-2">
                            <div>
                                <label for="start_date" class="block text-xs font-medium text-gray-600 mb-1">Desde</label>
                                <input type="date" id="start_date" name="start_date" value="<?php echo $startDate; ?>" 
                                       class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-primary-500">
                            </div>
                            <div>
                                <label for="end_date" class="block text-xs font-medium text-gray-600 mb-1">Hasta</label>
                                <input type="date" id="end_date" name="end_date" value="<?php echo $endDate; ?>" 
                                       class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-primary-500">
                            </div>
                            <div class="flex items-end">
                                <button type="submit" class="px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white text-sm font-medium rounded-lg transition-colors">
                                    <i class="fas fa-filter mr-1"></i>Filtrar
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- KPI Cards -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                        <div class="flex items-center">
                            <div class="p-3 bg-green-100 rounded-lg">
                                <i class="fas fa-dollar-sign text-green-600 text-xl"></i>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-600">Ingresos del Período</p>
                                <p class="text-2xl font-bold text-gray-900">$<?php echo number_format($salesStats['total_revenue'] ?? 0, 2); ?></p>
                                <p class="text-xs text-gray-500"><?php echo $salesStats['total_orders'] ?? 0; ?> órdenes</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                        <div class="flex items-center">
                            <div class="p-3 bg-blue-100 rounded-lg">
                                <i class="fas fa-shopping-cart text-blue-600 text-xl"></i>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-600">Valor Promedio de Orden</p>
                                <p class="text-2xl font-bold text-gray-900">$<?php echo number_format($salesStats['avg_order_value'] ?? 0, 2); ?></p>
                                <p class="text-xs text-gray-500"><?php echo $salesStats['unique_customers'] ?? 0; ?> clientes únicos</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                        <div class="flex items-center">
                            <div class="p-3 bg-purple-100 rounded-lg">
                                <i class="fas fa-users text-purple-600 text-xl"></i>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-600">Nuevos Clientes</p>
                                <p class="text-2xl font-bold text-gray-900"><?php echo number_format($customerStats['new_customers'] ?? 0); ?></p>
                                <p class="text-xs text-gray-500"><?php echo $customerStats['verified_customers'] ?? 0; ?> verificados</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                        <div class="flex items-center">
                            <div class="p-3 bg-orange-100 rounded-lg">
                                <i class="fas fa-newspaper text-orange-600 text-xl"></i>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-600">Contenido Publicado</p>
                                <p class="text-2xl font-bold text-gray-900"><?php echo number_format($newsStats['published'] ?? 0); ?></p>
                                <p class="text-xs text-gray-500"><?php echo $newsStats['total_views'] ?? 0; ?> visualizaciones</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Charts Row -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                    <!-- Gráfico de Ventas Diarias -->
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Ventas Diarias</h3>
                        <div class="chart-container">
                            <canvas id="dailySalesChart"></canvas>
                        </div>
                    </div>
                    
                    <!-- Gráfico de Estados de Órdenes -->
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Estados de Órdenes</h3>
                        <div class="chart-container">
                            <canvas id="orderStatusChart"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Tables Row -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                    <!-- Productos Más Vendidos -->
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h3 class="text-lg font-semibold text-gray-900">Productos Más Vendidos</h3>
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
                                    <?php foreach (array_slice($topProducts, 0, 5) as $product): ?>
                                    <tr>
                                        <td class="px-6 py-4">
                                            <div class="flex items-center">
                                                <div class="h-10 w-10 flex-shrink-0">
                                                    <?php if (!empty($product['main_image'])): ?>
                                                    <img src="<?php echo BASE_URL . '/' . htmlspecialchars($product['main_image']); ?>" 
                                                         alt="<?php echo htmlspecialchars($product['name']); ?>" 
                                                         class="h-10 w-10 object-cover rounded">
                                                    <?php else: ?>
                                                    <div class="h-10 w-10 bg-gray-200 rounded flex items-center justify-center">
                                                        <i class="fas fa-image text-gray-400"></i>
                                                    </div>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="ml-4">
                                                    <div class="text-sm font-medium text-gray-900">
                                                        <?php echo htmlspecialchars(substr($product['name'], 0, 30)) . (strlen($product['name']) > 30 ? '...' : ''); ?>
                                                    </div>
                                                    <div class="text-sm text-gray-500">$<?php echo number_format($product['price'], 2); ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-900"><?php echo number_format($product['total_sold']); ?></td>
                                        <td class="px-6 py-4 text-sm text-gray-900">$<?php echo number_format($product['total_revenue'], 2); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                    
                                    <?php if (empty($topProducts)): ?>
                                    <tr>
                                        <td colspan="3" class="px-6 py-4 text-center text-gray-500">No hay datos disponibles para el período seleccionado</td>
                                    </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Métodos de Pago -->
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h3 class="text-lg font-semibold text-gray-900">Métodos de Pago</h3>
                        </div>
                        <div class="p-6">
                            <?php foreach ($paymentMethods as $method): ?>
                            <div class="flex items-center justify-between py-3 border-b border-gray-100 last:border-b-0">
                                <div class="flex items-center">
                                    <div class="p-2 bg-gray-100 rounded">
                                        <?php
                                        $icons = [
                                            'credit_card' => 'fas fa-credit-card',
                                            'debit_card' => 'fas fa-credit-card',
                                            'paypal' => 'fab fa-paypal',
                                            'bank_transfer' => 'fas fa-university',
                                            'cash' => 'fas fa-money-bill-wave'
                                        ];
                                        $icon = $icons[$method['payment_method']] ?? 'fas fa-credit-card';
                                        ?>
                                        <i class="<?php echo $icon; ?> text-gray-600"></i>
                                    </div>
                                    <div class="ml-3">
                                        <p class="text-sm font-medium text-gray-900">
                                            <?php 
                                            $labels = [
                                                'credit_card' => 'Tarjeta de Crédito',
                                                'debit_card' => 'Tarjeta de Débito',
                                                'paypal' => 'PayPal',
                                                'bank_transfer' => 'Transferencia Bancaria',
                                                'cash' => 'Efectivo'
                                            ];
                                            echo $labels[$method['payment_method']] ?? ucfirst(str_replace('_', ' ', $method['payment_method']));
                                            ?>
                                        </p>
                                        <p class="text-xs text-gray-500"><?php echo $method['count']; ?> transacciones</p>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <p class="text-sm font-semibold text-gray-900">$<?php echo number_format($method['total_amount'], 2); ?></p>
                                </div>
                            </div>
                            <?php endforeach; ?>
                            
                            <?php if (empty($paymentMethods)): ?>
                            <div class="text-center text-gray-500 py-4">
                                No hay datos disponibles para el período seleccionado
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Estadísticas Generales del Sistema -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-6">Resumen General del Sistema</h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                        <div class="text-center">
                            <div class="p-4 bg-blue-100 rounded-lg inline-block mb-3">
                                <i class="fas fa-box text-blue-600 text-2xl"></i>
                            </div>
                            <h4 class="text-2xl font-bold text-gray-900"><?php echo number_format(count($totalProducts)); ?></h4>
                            <p class="text-sm text-gray-600">Total Productos</p>
                        </div>
                        
                        <div class="text-center">
                            <div class="p-4 bg-green-100 rounded-lg inline-block mb-3">
                                <i class="fas fa-shopping-bag text-green-600 text-2xl"></i>
                            </div>
                            <h4 class="text-2xl font-bold text-gray-900"><?php echo number_format(count($totalOrders)); ?></h4>
                            <p class="text-sm text-gray-600">Total Órdenes</p>
                        </div>
                        
                        <div class="text-center">
                            <div class="p-4 bg-purple-100 rounded-lg inline-block mb-3">
                                <i class="fas fa-users text-purple-600 text-2xl"></i>
                            </div>
                            <h4 class="text-2xl font-bold text-gray-900"><?php echo number_format(count($totalCustomers)); ?></h4>
                            <p class="text-sm text-gray-600">Total Clientes</p>
                        </div>
                        
                        <div class="text-center">
                            <div class="p-4 bg-orange-100 rounded-lg inline-block mb-3">
                                <i class="fas fa-newspaper text-orange-600 text-2xl"></i>
                            </div>
                            <h4 class="text-2xl font-bold text-gray-900"><?php echo number_format($newsStats['total']); ?></h4>
                            <p class="text-sm text-gray-600">Total Noticias</p>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script>
        // Gráfico de Ventas Diarias
        const dailySalesData = <?php echo json_encode($dailySales); ?>;
        const dailySalesCtx = document.getElementById('dailySalesChart').getContext('2d');
        
        new Chart(dailySalesCtx, {
            type: 'line',
            data: {
                labels: dailySalesData.map(item => {
                    const date = new Date(item.date);
                    return date.toLocaleDateString('es-ES', { month: 'short', day: 'numeric' });
                }),
                datasets: [{
                    label: 'Ingresos',
                    data: dailySalesData.map(item => parseFloat(item.revenue)),
                    borderColor: '#ec4899',
                    backgroundColor: 'rgba(236, 72, 153, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    intersect: false,
                    mode: 'index'
                },
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    x: {
                        grid: {
                            display: false
                        }
                    },
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(0, 0, 0, 0.1)'
                        },
                        ticks: {
                            callback: function(value) {
                                return '$' + value.toLocaleString();
                            }
                        }
                    }
                }
            }
        });

        // Gráfico de Estados de Órdenes
        const orderStatusData = <?php echo json_encode($orderStatuses); ?>;
        const orderStatusCtx = document.getElementById('orderStatusChart').getContext('2d');
        
        const statusColors = {
            'pending': '#f59e0b',
            'processing': '#3b82f6',
            'shipped': '#8b5cf6',
            'delivered': '#10b981',
            'cancelled': '#ef4444',
            'completed': '#059669'
        };
        
        new Chart(orderStatusCtx, {
            type: 'doughnut',
            data: {
                labels: orderStatusData.map(item => {
                    const labels = {
                        'pending': 'Pendiente',
                        'processing': 'Procesando',
                        'shipped': 'Enviado',
                        'delivered': 'Entregado',
                        'cancelled': 'Cancelado',
                        'completed': 'Completado'
                    };
                    return labels[item.status] || item.status;
                }),
                datasets: [{
                    data: orderStatusData.map(item => parseInt(item.count)),
                    backgroundColor: orderStatusData.map(item => statusColors[item.status] || '#6b7280')
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
        
        // Forzar visibilidad del sidebar después de cargar Chart.js
        document.addEventListener('DOMContentLoaded', function() {
            const sidebar = document.getElementById('sidebar');
            if (sidebar) {
                sidebar.style.zIndex = '9999';
                sidebar.style.visibility = 'visible';
                sidebar.style.opacity = '1';
                sidebar.style.backgroundColor = '#1e293b';
                sidebar.style.position = window.innerWidth >= 768 ? 'relative' : 'fixed';
            }
        });
        
        // Forzar visibilidad después de que Chart.js termine de renderizar
        setTimeout(function() {
            const sidebar = document.getElementById('sidebar');
            if (sidebar) {
                sidebar.style.zIndex = '9999';
                sidebar.style.visibility = 'visible';
                sidebar.style.opacity = '1';
            }
        }, 100);
    </script>
</body>
</html>
