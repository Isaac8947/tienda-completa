<?php
session_start();
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../models/Order.php';

// Verificar autenticación del admin
if (!isAdminLoggedIn()) {
    redirectTo(ADMIN_URL . '/login.php');
    exit;
}

$orderModel = new Order();

// Obtener todas las órdenes con datos de facturación
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

$orders = $orderModel->getAllWithInvoiceData($limit, $offset);
$totalOrders = $orderModel->getTotalCount();
$totalPages = ceil($totalOrders / $limit);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Facturas - Odisea Makeup Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .active { 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); 
        }
    </style>
</head>
<body class="bg-gray-50 font-sans">
    <div class="flex h-screen bg-gray-100">
        
        <!-- Sidebar -->
        <?php include 'includes/sidebar.php'; ?>
        
        <!-- Main Content -->
        <div class="flex-1 flex flex-col overflow-hidden">
            
            <!-- Top Navigation -->
            <?php include 'includes/header.php'; ?>
            
            <!-- Page Content -->
            <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-50 p-6">
                <div class="container mx-auto">
                    
                    <!-- Header -->
                    <div class="mb-8">
                        <div class="flex justify-between items-center">
                            <div>
                                <h1 class="text-3xl font-bold text-gray-900 mb-2">
                                    <i class="fas fa-file-invoice text-indigo-600 mr-3"></i>
                                    Gestión de Facturas
                                </h1>
                                <p class="text-gray-600">Administra todas las facturas generadas</p>
                            </div>
                            
                            <!-- Stats Cards -->
                            <div class="grid grid-cols-2 gap-4">
                                <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100">
                                    <div class="flex items-center">
                                        <div class="p-2 bg-green-100 rounded-lg">
                                            <i class="fas fa-check-circle text-green-600"></i>
                                        </div>
                                        <div class="ml-3">
                                            <p class="text-sm text-gray-600">Total Facturas</p>
                                            <p class="text-xl font-semibold text-gray-900"><?php echo $totalOrders; ?></p>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100">
                                    <div class="flex items-center">
                                        <div class="p-2 bg-blue-100 rounded-lg">
                                            <i class="fas fa-calendar-alt text-blue-600"></i>
                                        </div>
                                        <div class="ml-3">
                                            <p class="text-sm text-gray-600">Este Mes</p>
                                            <p class="text-xl font-semibold text-gray-900">
                                                <?php 
                                                $monthOrders = $orderModel->getCountByMonth(date('Y'), date('m'));
                                                echo $monthOrders; 
                                                ?>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Filters -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mb-6">
                        <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Fecha Desde</label>
                                <input type="date" name="date_from" value="<?php echo $_GET['date_from'] ?? ''; ?>" 
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Fecha Hasta</label>
                                <input type="date" name="date_to" value="<?php echo $_GET['date_to'] ?? ''; ?>" 
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Estado</label>
                                <select name="status" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                                    <option value="">Todos los estados</option>
                                    <option value="pending" <?php echo ($_GET['status'] ?? '') == 'pending' ? 'selected' : ''; ?>>Pendiente</option>
                                    <option value="processing" <?php echo ($_GET['status'] ?? '') == 'processing' ? 'selected' : ''; ?>>En Proceso</option>
                                    <option value="completed" <?php echo ($_GET['status'] ?? '') == 'completed' ? 'selected' : ''; ?>>Completado</option>
                                    <option value="cancelled" <?php echo ($_GET['status'] ?? '') == 'cancelled' ? 'selected' : ''; ?>>Cancelado</option>
                                </select>
                            </div>
                            
                            <div class="flex items-end">
                                <button type="submit" class="w-full bg-indigo-600 text-white py-2 px-4 rounded-lg hover:bg-indigo-700 transition duration-200">
                                    <i class="fas fa-search mr-2"></i>Filtrar
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Invoices Table -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100">
                        <div class="p-6">
                            <div class="overflow-x-auto">
                                <table class="w-full table-auto">
                                    <thead>
                                        <tr class="border-b border-gray-200">
                                            <th class="text-left py-4 px-4 font-semibold text-gray-700">N° Factura</th>
                                            <th class="text-left py-4 px-4 font-semibold text-gray-700">N° Pedido</th>
                                            <th class="text-left py-4 px-4 font-semibold text-gray-700">Cliente</th>
                                            <th class="text-left py-4 px-4 font-semibold text-gray-700">Fecha</th>
                                            <th class="text-left py-4 px-4 font-semibold text-gray-700">Total</th>
                                            <th class="text-left py-4 px-4 font-semibold text-gray-700">Estado</th>
                                            <th class="text-left py-4 px-4 font-semibold text-gray-700">Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($orders)): ?>
                                            <tr>
                                                <td colspan="7" class="text-center py-8 text-gray-500">
                                                    <i class="fas fa-file-invoice text-4xl text-gray-300 mb-4"></i>
                                                    <p>No hay facturas disponibles</p>
                                                </td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($orders as $order): ?>
                                                <?php 
                                                    // Validar datos de la orden
                                                    $orderId = $order['id'] ?? 0;
                                                    $createdAt = $order['created_at'] ?? date('Y-m-d H:i:s');
                                                    
                                                    $invoiceNumber = 'INV-' . date('Y', strtotime($createdAt)) . '-' . str_pad($orderId, 4, '0', STR_PAD_LEFT);
                                                    $statusColors = [
                                                        'pending' => 'bg-yellow-100 text-yellow-800',
                                                        'processing' => 'bg-blue-100 text-blue-800',
                                                        'completed' => 'bg-green-100 text-green-800',
                                                        'cancelled' => 'bg-red-100 text-red-800'
                                                    ];
                                                    $statusTexts = [
                                                        'pending' => 'Pendiente',
                                                        'processing' => 'En Proceso',
                                                        'completed' => 'Completado',
                                                        'cancelled' => 'Cancelado'
                                                    ];
                                                ?>
                                                <tr class="border-b border-gray-100 hover:bg-gray-50">
                                                    <td class="py-4 px-4">
                                                        <span class="font-mono text-sm font-semibold text-indigo-600">
                                                            <?php echo $invoiceNumber; ?>
                                                        </span>
                                                    </td>
                                                    <td class="py-4 px-4">
                                                        <span class="font-mono text-sm">
                                                            #<?php echo str_pad($orderId, 6, '0', STR_PAD_LEFT); ?>
                                                        </span>
                                                    </td>
                                                    <td class="py-4 px-4">
                                                        <div>
                                                            <p class="font-medium text-gray-900">
                                                                <?php echo htmlspecialchars(($order['first_name'] ?? '') . ' ' . ($order['last_name'] ?? '')); ?>
                                                            </p>
                                                            <p class="text-sm text-gray-500">
                                                                <?php echo htmlspecialchars($order['email'] ?? 'N/A'); ?>
                                                            </p>
                                                        </div>
                                                    </td>
                                                    <td class="py-4 px-4">
                                                        <span class="text-sm text-gray-600">
                                                            <?php echo $order['created_at'] ? date('d/m/Y H:i', strtotime($order['created_at'])) : 'N/A'; ?>
                                                        </span>
                                                    </td>
                                                    <td class="py-4 px-4">
                                                        <span class="font-semibold text-gray-900">
                                                            $<?php echo number_format($order['total'] ?? 0, 0, ',', '.'); ?>
                                                        </span>
                                                    </td>
                                                    <td class="py-4 px-4">
                                                        <span class="px-2 py-1 text-xs font-medium rounded-full <?php echo $statusColors[$order['status'] ?? 'pending'] ?? 'bg-gray-100 text-gray-800'; ?>">
                                                            <?php echo $statusTexts[$order['status'] ?? 'pending'] ?? ucfirst($order['status'] ?? 'Pendiente'); ?>
                                                        </span>
                                                    </td>
                                                    <td class="py-4 px-4">
                                                        <div class="flex space-x-2">
                                                            <!-- Ver Factura -->
                                                            <a href="../admin-pages/invoices.php?order_id=<?php echo $orderId; ?>" 
                                                               target="_blank"
                                                               class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-indigo-600 bg-indigo-50 rounded-lg hover:bg-indigo-100 transition-colors">
                                                                <i class="fas fa-eye mr-1"></i>Ver
                                                            </a>
                                                            
                                                            <!-- Descargar PDF -->
                                                            <a href="../admin-pages/invoices.php?order_id=<?php echo $orderId; ?>&download=pdf" 
                                                               class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-green-600 bg-green-50 rounded-lg hover:bg-green-100 transition-colors">
                                                                <i class="fas fa-download mr-1"></i>PDF
                                                            </a>
                                                            
                                                            <!-- Ver Pedido -->
                                                            <a href="gestion-pedidos.php?id=<?php echo $orderId; ?>" 
                                                               class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-gray-600 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                                                                <i class="fas fa-box mr-1"></i>Pedido
                                                            </a>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                            
                            <!-- Pagination -->
                            <?php if ($totalPages > 1): ?>
                                <div class="mt-6 flex justify-between items-center">
                                    <div class="text-sm text-gray-600">
                                        Mostrando <?php echo count($orders); ?> de <?php echo $totalOrders; ?> facturas
                                    </div>
                                    
                                    <div class="flex space-x-2">
                                        <?php if ($page > 1): ?>
                                            <a href="?page=<?php echo $page - 1; ?>" 
                                               class="px-3 py-2 text-sm text-gray-600 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
                                                Anterior
                                            </a>
                                        <?php endif; ?>
                                        
                                        <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                                            <a href="?page=<?php echo $i; ?>" 
                                               class="px-3 py-2 text-sm <?php echo $i == $page ? 'bg-indigo-600 text-white' : 'text-gray-600 bg-white border border-gray-300 hover:bg-gray-50'; ?> rounded-lg">
                                                <?php echo $i; ?>
                                            </a>
                                        <?php endfor; ?>
                                        
                                        <?php if ($page < $totalPages): ?>
                                            <a href="?page=<?php echo $page + 1; ?>" 
                                               class="px-3 py-2 text-sm text-gray-600 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
                                                Siguiente
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                </div>
            </main>
        </div>
    </div>

    <script>
        // Auto-submit form when filters change
        document.querySelectorAll('select[name="status"]').forEach(select => {
            select.addEventListener('change', function() {
                this.form.submit();
            });
        });
    </script>
</body>
</html>
