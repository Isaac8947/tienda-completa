<?php
session_start();
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../models/Product.php';
require_once '../models/Order.php';
require_once '../models/Customer.php';
require_once '../admin/auth-check.php';

// Verificar si se solicita exportación
if (isset($_GET['export']) && isset($_GET['type'])) {
    $exportType = $_GET['type'];
    $startDate = $_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
    $endDate = $_GET['end_date'] ?? date('Y-m-d');
    
    // Configurar headers para descarga de CSV
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="reporte_' . $exportType . '_' . date('Y-m-d') . '.csv"');
    
    $output = fopen('php://output', 'w');
    
    switch ($exportType) {
        case 'sales':
            // Exportar reporte de ventas
            fputcsv($output, ['Fecha', 'ID Orden', 'Cliente', 'Email', 'Total', 'Estado', 'Método de Pago']);
            
            $database = new Database();
            $db = $database->getConnection();
            
            $sql = "SELECT o.*, c.first_name, c.last_name, c.email 
                    FROM orders o 
                    LEFT JOIN customers c ON o.customer_id = c.id 
                    WHERE o.created_at BETWEEN ? AND ?
                    ORDER BY o.created_at DESC";
            
            $stmt = $db->prepare($sql);
            $stmt->execute([$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
            $orders = $stmt->fetchAll();
            
            foreach ($orders as $order) {
                fputcsv($output, [
                    date('d/m/Y H:i', strtotime($order['created_at'])),
                    $order['id'],
                    $order['first_name'] . ' ' . $order['last_name'],
                    $order['email'],
                    number_format($order['total'], 2),
                    $order['status'],
                    $order['payment_method']
                ]);
            }
            break;
            
        case 'products':
            // Exportar reporte de productos
            fputcsv($output, ['ID', 'Nombre', 'SKU', 'Precio', 'Stock', 'Categoría', 'Estado']);
            
            $productModel = new Product();
            $products = $productModel->findAll();
            
            foreach ($products as $product) {
                fputcsv($output, [
                    $product['id'],
                    $product['name'],
                    $product['sku'],
                    number_format($product['price'], 2),
                    $product['stock_quantity'],
                    $product['category_id'], // Aquí podrías agregar el nombre de la categoría
                    $product['is_active'] ? 'Activo' : 'Inactivo'
                ]);
            }
            break;
            
        case 'customers':
            // Exportar reporte de clientes
            fputcsv($output, ['ID', 'Nombre', 'Apellido', 'Email', 'Teléfono', 'Fecha Registro', 'Verificado']);
            
            $database = new Database();
            $db = $database->getConnection();
            
            $sql = "SELECT * FROM customers 
                    WHERE created_at BETWEEN ? AND ?
                    ORDER BY created_at DESC";
            
            $stmt = $db->prepare($sql);
            $stmt->execute([$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
            $customers = $stmt->fetchAll();
            
            foreach ($customers as $customer) {
                fputcsv($output, [
                    $customer['id'],
                    $customer['first_name'],
                    $customer['last_name'],
                    $customer['email'],
                    $customer['phone'],
                    date('d/m/Y', strtotime($customer['created_at'])),
                    $customer['email_verified'] ? 'Sí' : 'No'
                ]);
            }
            break;
    }
    
    fclose($output);
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Exportar Reportes - Odisea Admin</title>
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
                        <h1 class="text-2xl lg:text-3xl font-bold text-gray-900">Exportar Reportes</h1>
                        <p class="text-gray-600 mt-1">Generar y descargar reportes detallados en formato CSV</p>
                    </div>
                    <div class="flex space-x-3">
                        <a href="analytics.php" class="inline-flex items-center px-4 py-2 bg-secondary-600 hover:bg-secondary-700 text-white text-sm font-medium rounded-lg transition-colors">
                            <i class="fas fa-chart-bar mr-2"></i>
                            Ver Analytics
                        </a>
                    </div>
                </div>

                <!-- Export Cards -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <!-- Reporte de Ventas -->
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                        <div class="flex items-center mb-4">
                            <div class="p-3 bg-green-100 rounded-lg">
                                <i class="fas fa-chart-line text-green-600 text-xl"></i>
                            </div>
                            <div class="ml-4">
                                <h3 class="text-lg font-semibold text-gray-900">Reporte de Ventas</h3>
                                <p class="text-sm text-gray-600">Exportar todas las órdenes y transacciones</p>
                            </div>
                        </div>
                        
                        <form method="GET" class="space-y-4">
                            <input type="hidden" name="export" value="1">
                            <input type="hidden" name="type" value="sales">
                            
                            <div>
                                <label for="sales_start_date" class="block text-sm font-medium text-gray-700 mb-1">Fecha de inicio</label>
                                <input type="date" id="sales_start_date" name="start_date" value="<?php echo date('Y-m-d', strtotime('-30 days')); ?>" 
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary-500 text-sm">
                            </div>
                            
                            <div>
                                <label for="sales_end_date" class="block text-sm font-medium text-gray-700 mb-1">Fecha de fin</label>
                                <input type="date" id="sales_end_date" name="end_date" value="<?php echo date('Y-m-d'); ?>" 
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary-500 text-sm">
                            </div>
                            
                            <button type="submit" class="w-full bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors">
                                <i class="fas fa-download mr-2"></i>
                                Descargar Reporte de Ventas
                            </button>
                        </form>
                        
                        <div class="mt-4 text-xs text-gray-500">
                            <p><strong>Incluye:</strong> ID de orden, datos del cliente, total, estado, método de pago, fecha</p>
                        </div>
                    </div>

                    <!-- Reporte de Productos -->
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                        <div class="flex items-center mb-4">
                            <div class="p-3 bg-blue-100 rounded-lg">
                                <i class="fas fa-box text-blue-600 text-xl"></i>
                            </div>
                            <div class="ml-4">
                                <h3 class="text-lg font-semibold text-gray-900">Reporte de Productos</h3>
                                <p class="text-sm text-gray-600">Exportar inventario completo de productos</p>
                            </div>
                        </div>
                        
                        <form method="GET" class="space-y-4">
                            <input type="hidden" name="export" value="1">
                            <input type="hidden" name="type" value="products">
                            
                            <div class="bg-gray-50 p-4 rounded-lg">
                                <p class="text-sm text-gray-600">Este reporte incluye todos los productos registrados en el sistema, independientemente de la fecha.</p>
                            </div>
                            
                            <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors">
                                <i class="fas fa-download mr-2"></i>
                                Descargar Reporte de Productos
                            </button>
                        </form>
                        
                        <div class="mt-4 text-xs text-gray-500">
                            <p><strong>Incluye:</strong> ID, nombre, SKU, precio, stock, categoría, estado</p>
                        </div>
                    </div>

                    <!-- Reporte de Clientes -->
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                        <div class="flex items-center mb-4">
                            <div class="p-3 bg-purple-100 rounded-lg">
                                <i class="fas fa-users text-purple-600 text-xl"></i>
                            </div>
                            <div class="ml-4">
                                <h3 class="text-lg font-semibold text-gray-900">Reporte de Clientes</h3>
                                <p class="text-sm text-gray-600">Exportar datos de clientes registrados</p>
                            </div>
                        </div>
                        
                        <form method="GET" class="space-y-4">
                            <input type="hidden" name="export" value="1">
                            <input type="hidden" name="type" value="customers">
                            
                            <div>
                                <label for="customers_start_date" class="block text-sm font-medium text-gray-700 mb-1">Fecha de inicio</label>
                                <input type="date" id="customers_start_date" name="start_date" value="<?php echo date('Y-m-d', strtotime('-30 days')); ?>" 
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary-500 text-sm">
                            </div>
                            
                            <div>
                                <label for="customers_end_date" class="block text-sm font-medium text-gray-700 mb-1">Fecha de fin</label>
                                <input type="date" id="customers_end_date" name="end_date" value="<?php echo date('Y-m-d'); ?>" 
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary-500 text-sm">
                            </div>
                            
                            <button type="submit" class="w-full bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors">
                                <i class="fas fa-download mr-2"></i>
                                Descargar Reporte de Clientes
                            </button>
                        </form>
                        
                        <div class="mt-4 text-xs text-gray-500">
                            <p><strong>Incluye:</strong> ID, nombre, email, teléfono, fecha de registro, estado de verificación</p>
                        </div>
                    </div>
                </div>

                <!-- Información Adicional -->
                <div class="mt-8 bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">
                        <i class="fas fa-info-circle text-blue-500 mr-2"></i>
                        Información sobre los Reportes
                    </h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <h4 class="font-medium text-gray-900 mb-2">Formato de Archivos</h4>
                            <ul class="text-sm text-gray-600 space-y-1">
                                <li>• Los archivos se descargan en formato CSV</li>
                                <li>• Compatible con Excel y Google Sheets</li>
                                <li>• Codificación UTF-8 para caracteres especiales</li>
                                <li>• Separados por comas con headers incluidos</li>
                            </ul>
                        </div>
                        
                        <div>
                            <h4 class="font-medium text-gray-900 mb-2">Recomendaciones</h4>
                            <ul class="text-sm text-gray-600 space-y-1">
                                <li>• Exporta datos regularmente para respaldos</li>
                                <li>• Los reportes grandes pueden tardar unos segundos</li>
                                <li>• Revisa los filtros de fecha antes de exportar</li>
                                <li>• Mantén la confidencialidad de los datos exportados</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Reportes Rápidos -->
                <div class="mt-8 bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">
                        <i class="fas fa-bolt text-yellow-500 mr-2"></i>
                        Reportes Rápidos
                    </h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <a href="?export=1&type=sales&start_date=<?php echo date('Y-m-d'); ?>&end_date=<?php echo date('Y-m-d'); ?>" 
                           class="flex items-center justify-center p-4 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                            <i class="fas fa-calendar-day text-gray-500 mr-2"></i>
                            <span class="text-sm font-medium text-gray-700">Ventas de Hoy</span>
                        </a>
                        
                        <a href="?export=1&type=sales&start_date=<?php echo date('Y-m-d', strtotime('-7 days')); ?>&end_date=<?php echo date('Y-m-d'); ?>" 
                           class="flex items-center justify-center p-4 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                            <i class="fas fa-calendar-week text-gray-500 mr-2"></i>
                            <span class="text-sm font-medium text-gray-700">Última Semana</span>
                        </a>
                        
                        <a href="?export=1&type=sales&start_date=<?php echo date('Y-m-01'); ?>&end_date=<?php echo date('Y-m-d'); ?>" 
                           class="flex items-center justify-center p-4 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                            <i class="fas fa-calendar-alt text-gray-500 mr-2"></i>
                            <span class="text-sm font-medium text-gray-700">Este Mes</span>
                        </a>
                    </div>
                </div>
            </main>
        </div>
    </div>
</body>
</html>
