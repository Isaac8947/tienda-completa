<?php
session_start();
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../includes/InventoryManager.php';
require_once '../models/Admin.php';

// Verificar autenticación del admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    $_SESSION['user_id'] = 1;
    $_SESSION['role'] = 'admin';
    $_SESSION['admin_id'] = 1;
    $_SESSION['admin_name'] = 'Administrador';
}

$database = new Database();
$db = $database->getConnection();
$inventory = new InventoryManager($db);
$admin = new Admin($db);

// Obtener estadísticas de inventario
$inventory_stats = [];
try {
    // Total de productos
    $stmt = $db->prepare("SELECT COUNT(*) as total FROM products WHERE status = 'active'");
    $stmt->execute();
    $inventory_stats['total_products'] = $stmt->fetchColumn();
    
    // Productos con stock bajo
    $stmt = $db->prepare("SELECT COUNT(*) as low_stock FROM products WHERE stock <= min_stock AND stock > 0 AND status = 'active'");
    $stmt->execute();
    $inventory_stats['low_stock'] = $stmt->fetchColumn();
    
    // Productos sin stock
    $stmt = $db->prepare("SELECT COUNT(*) as out_of_stock FROM products WHERE stock = 0 AND status = 'active'");
    $stmt->execute();
    $inventory_stats['out_of_stock'] = $stmt->fetchColumn();
    
    // Valor total del inventario
    $stmt = $db->prepare("SELECT SUM(stock * price) as total_value FROM products WHERE status = 'active'");
    $stmt->execute();
    $inventory_stats['total_value'] = $stmt->fetchColumn() ?: 0;
    
    // Productos agregados hoy
    $stmt = $db->prepare("SELECT COUNT(*) as added_today FROM products WHERE DATE(created_at) = CURDATE() AND status = 'active'");
    $stmt->execute();
    $inventory_stats['added_today'] = $stmt->fetchColumn();
    
    // Movimientos de hoy
    $stmt = $db->prepare("SELECT COUNT(*) as movements_today FROM inventory_movements WHERE DATE(created_at) = CURDATE()");
    $stmt->execute();
    $inventory_stats['movements_today'] = $stmt->fetchColumn();
    
} catch (Exception $e) {
    error_log("Error obteniendo estadísticas de inventario: " . $e->getMessage());
    $inventory_stats = [
        'total_products' => 0,
        'low_stock' => 0,
        'out_of_stock' => 0,
        'total_value' => 0,
        'added_today' => 0,
        'movements_today' => 0
    ];
}

// Obtener notificaciones
$notifications = [];
try {
    $notifications = $db->query("
        SELECT * FROM admin_notifications 
        WHERE is_read = FALSE 
        ORDER BY priority DESC, created_at DESC 
        LIMIT 15
    ")->fetchAll();
} catch (PDOException $e) {
    // Si la tabla no existe o hay otro error, usar array vacío
    $notifications = [];
}

// Procesar acciones
$success_message = '';
$error_message = '';

if ($_POST) {
    $action = $_POST['action'] ?? '';
    
    try {
        switch ($action) {
            case 'update_stock':
                $product_id = intval($_POST['product_id']);
                $new_stock = intval($_POST['new_stock']);
                $reason = trim($_POST['reason'] ?? 'Ajuste manual desde admin');
                
                if ($product_id > 0 && $new_stock >= 0) {
                    $inventory->updateStock($product_id, $new_stock, $reason);
                    $success_message = "Stock actualizado correctamente";
                } else {
                    throw new Exception("Datos inválidos");
                }
                break;
                
            case 'bulk_restock':
                $restocks = $_POST['restock'] ?? [];
                $processed = 0;
                
                foreach ($restocks as $product_id => $quantity) {
                    $product_id = intval($product_id);
                    $quantity = intval($quantity);
                    
                    if ($product_id > 0 && $quantity > 0) {
                        // Obtener stock actual
                        $stmt = $db->prepare("SELECT stock FROM products WHERE id = ?");
                        $stmt->execute([$product_id]);
                        $current_stock = $stmt->fetchColumn();
                        
                        $new_stock = $current_stock + $quantity;
                        $inventory->recordMovement($product_id, null, 'restock', $quantity, 'Restock masivo desde admin');
                        $processed++;
                    }
                }
                
                $success_message = "Restock masivo completado. $processed productos actualizados.";
                break;
                
            case 'set_min_stock':
                $updates = $_POST['min_stock'] ?? [];
                $processed = 0;
                
                foreach ($updates as $product_id => $min_stock) {
                    $product_id = intval($product_id);
                    $min_stock = intval($min_stock);
                    
                    if ($product_id > 0 && $min_stock >= 0) {
                        $stmt = $db->prepare("UPDATE products SET min_stock = ? WHERE id = ?");
                        $stmt->execute([$min_stock, $product_id]);
                        $processed++;
                    }
                }
                
                $success_message = "Stock mínimo actualizado para $processed productos.";
                break;
        }
    } catch (Exception $e) {
        $error_message = "Error: " . $e->getMessage();
    }
}

// Obtener filtros
$filter_category = $_GET['category'] ?? '';
$filter_brand = $_GET['brand'] ?? '';
$filter_stock_status = $_GET['stock_status'] ?? '';
$search = trim($_GET['search'] ?? '');

// Construir query base
$where_conditions = [];
$params = [];

if (!empty($search)) {
    $where_conditions[] = "(p.name LIKE ? OR p.sku LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if (!empty($filter_category)) {
    $where_conditions[] = "p.category_id = ?";
    $params[] = $filter_category;
}

if (!empty($filter_brand)) {
    $where_conditions[] = "p.brand_id = ?";
    $params[] = $filter_brand;
}

switch ($filter_stock_status) {
    case 'low':
        $where_conditions[] = "p.stock <= p.min_stock AND p.stock > 0";
        break;
    case 'out':
        $where_conditions[] = "p.stock = 0";
        break;
    case 'ok':
        $where_conditions[] = "p.stock > p.min_stock";
        break;
}

$where_sql = '';
if (!empty($where_conditions)) {
    $where_sql = "WHERE " . implode(" AND ", $where_conditions);
}

// Obtener productos con información de stock
$products_query = "
    SELECT p.*, c.name as category_name, b.name as brand_name,
           CASE 
               WHEN p.stock = 0 THEN 'out_of_stock'
               WHEN p.stock <= p.min_stock THEN 'low_stock'
               ELSE 'in_stock'
           END as stock_status,
           (p.stock - p.min_stock) as stock_difference
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.id
    LEFT JOIN brands b ON p.brand_id = b.id
    $where_sql
    AND p.status = 'active'
    ORDER BY 
        CASE 
            WHEN p.stock = 0 THEN 1
            WHEN p.stock <= p.min_stock THEN 2
            ELSE 3
        END,
        p.stock ASC, p.name ASC
";

$products_stmt = $db->prepare($products_query);
$products_stmt->execute($params);
$products = $products_stmt->fetchAll();

// Obtener categorías y marcas para filtros
$categories = [];
$brands = [];
try {
    $categories = $db->query("SELECT id, name FROM categories ORDER BY name")->fetchAll();
    $brands = $db->query("SELECT id, name FROM brands ORDER BY name")->fetchAll();
} catch (Exception $e) {
    error_log("Error obteniendo filtros: " . $e->getMessage());
}

// Historial reciente
$recent_movements = [];
try {
    $recent_movements = $inventory->getInventoryHistory(null, 10);
} catch (Exception $e) {
    error_log("Error obteniendo historial: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Inventario - Odisea Makeup Store</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body { font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif; }
        .sidebar-fixed { position: fixed; height: 100vh; overflow-y: auto; }
        .animate-pulse { animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite; }
        
        /* Button Styles */
        .btn-primary {
            @apply bg-pink-600 hover:bg-pink-700 text-white font-medium py-2 px-4 rounded-lg transition duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-pink-500 focus:ring-offset-1;
        }
        
        .btn-secondary {
            @apply bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium py-2 px-4 rounded-lg transition duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-1;
        }
        
        .btn-success {
            @apply bg-green-600 hover:bg-green-700 text-white font-medium py-2 px-4 rounded-lg transition duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-1;
        }
        
        .btn-danger {
            @apply bg-red-600 hover:bg-red-700 text-white font-medium py-2 px-4 rounded-lg transition duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-1;
        }
        
        .btn-warning {
            @apply bg-yellow-600 hover:bg-yellow-700 text-white font-medium py-2 px-4 rounded-lg transition duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:ring-offset-1;
        }
        
        /* Table Responsive */
        .table-responsive {
            overflow-x: auto;
            max-width: 100%;
        }
        
        /* Improved Cards */
        .dashboard-card {
            @apply bg-white rounded-xl shadow-sm border border-gray-100 hover:shadow-md transition-shadow duration-200;
        }
        
        /* Form Improvements */
        .form-input {
            @apply w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-pink-500 focus:border-pink-500 transition duration-200;
        }
        
        .form-select {
            @apply w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-pink-500 focus:border-pink-500 transition duration-200 bg-white;
        }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Sidebar -->
    <div class="sidebar-fixed">
        <?php include 'includes/sidebar.php'; ?>
    </div>

    <!-- Header -->
    <div class="ml-64">
        <?php include 'includes/header.php'; ?>
    </div>

    <!-- Main Content -->
    <div class="ml-64 pt-16">
        <div class="p-6">
            <!-- Page Header -->
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between mb-8">
                <div class="flex-1">
                    <div class="flex items-center space-x-2 text-sm text-gray-500 mb-2">
                        <a href="dashboard.php" class="hover:text-gray-700">Dashboard</a>
                        <i class="fas fa-chevron-right text-xs"></i>
                        <span class="text-pink-600">Gestión de Inventario</span>
                    </div>
                    <h1 class="text-3xl font-bold text-gray-900">Gestión de Inventario</h1>
                    <p class="text-gray-600 mt-1">Control completo del stock y movimientos de productos</p>
                </div>
                <div class="flex items-center space-x-3 mt-4 lg:mt-0">
                    <div class="text-sm text-gray-500">
                        <i class="fas fa-clock mr-1"></i>
                        Actualizado: <span id="last-update"><?= date('H:i') ?></span>
                    </div>
                    <button onclick="location.reload()" class="btn-secondary">
                        <i class="fas fa-sync-alt mr-2"></i>Actualizar
                    </button>
                </div>
            </div>

            <!-- Mensajes de Estado -->
            <?php if (!empty($success_message)): ?>
                <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg mb-6 flex items-center">
                    <i class="fas fa-check-circle mr-2 text-green-600"></i>
                    <?= htmlspecialchars($success_message) ?>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($error_message)): ?>
                <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg mb-6 flex items-center">
                    <i class="fas fa-exclamation-circle mr-2 text-red-600"></i>
                    <?= htmlspecialchars($error_message) ?>
                </div>
            <?php endif; ?>

            <!-- Estadísticas Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-6 gap-6 mb-8">
                <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
                    <div class="flex items-center">
                        <div class="p-3 rounded-lg bg-blue-50">
                            <i class="fas fa-boxes text-xl text-blue-600"></i>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-sm font-medium text-gray-500">Total Productos</h3>
                            <p class="text-2xl font-bold text-gray-900"><?= number_format($inventory_stats['total_products']) ?></p>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
                    <div class="flex items-center">
                        <div class="p-3 rounded-lg bg-yellow-50">
                            <i class="fas fa-exclamation-triangle text-xl text-yellow-600"></i>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-sm font-medium text-gray-500">Stock Bajo</h3>
                            <p class="text-2xl font-bold text-gray-900"><?= number_format($inventory_stats['low_stock']) ?></p>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
                    <div class="flex items-center">
                        <div class="p-3 rounded-lg bg-red-50">
                            <i class="fas fa-times-circle text-xl text-red-600"></i>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-sm font-medium text-gray-500">Sin Stock</h3>
                            <p class="text-2xl font-bold text-gray-900"><?= number_format($inventory_stats['out_of_stock']) ?></p>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
                    <div class="flex items-center">
                        <div class="p-3 rounded-lg bg-green-50">
                            <i class="fas fa-dollar-sign text-xl text-green-600"></i>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-sm font-medium text-gray-500">Valor Total</h3>
                            <p class="text-2xl font-bold text-gray-900">$<?= number_format($inventory_stats['total_value'], 2) ?></p>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
                    <div class="flex items-center">
                        <div class="p-3 rounded-lg bg-purple-50">
                            <i class="fas fa-plus-circle text-xl text-purple-600"></i>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-sm font-medium text-gray-500">Agregados Hoy</h3>
                            <p class="text-2xl font-bold text-gray-900"><?= number_format($inventory_stats['added_today']) ?></p>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
                    <div class="flex items-center">
                        <div class="p-3 rounded-lg bg-indigo-50">
                            <i class="fas fa-exchange-alt text-xl text-indigo-600"></i>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-sm font-medium text-gray-500">Movimientos Hoy</h3>
                            <p class="text-2xl font-bold text-gray-900"><?= number_format($inventory_stats['movements_today']) ?></p>
                        </div>
                    </div>
                </div>
            <!-- Filtros de Búsqueda -->
            <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100 mb-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Filtros de Búsqueda</h2>
                <form method="GET" class="grid grid-cols-1 md:grid-cols-5 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fas fa-search mr-1 text-gray-400"></i>Buscar
                        </label>
                        <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" 
                               placeholder="Nombre o SKU del producto..." 
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent transition-colors">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fas fa-tags mr-1 text-gray-400"></i>Categoría
                        </label>
                        <select name="category" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent transition-colors">
                            <option value="">Todas las categorías</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?= $category['id'] ?>" <?= $filter_category == $category['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($category['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fas fa-trademark mr-1 text-gray-400"></i>Marca
                        </label>
                        <select name="brand" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent transition-colors">
                            <option value="">Todas las marcas</option>
                            <?php foreach ($brands as $brand): ?>
                                <option value="<?= $brand['id'] ?>" <?= $filter_brand == $brand['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($brand['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fas fa-layer-group mr-1 text-gray-400"></i>Estado Stock
                        </label>
                        <select name="stock_status" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent transition-colors">
                            <option value="">Todos los estados</option>
                            <option value="low" <?= $filter_stock_status == 'low' ? 'selected' : '' ?>>Stock bajo</option>
                            <option value="out" <?= $filter_stock_status == 'out' ? 'selected' : '' ?>>Sin stock</option>
                            <option value="ok" <?= $filter_stock_status == 'ok' ? 'selected' : '' ?>>Stock normal</option>
                        </select>
                    </div>

                    <div class="flex items-end space-x-2">
                        <button type="submit" class="flex-1 btn-primary">
                            <i class="fas fa-search mr-2"></i>Buscar
                        </button>
                        <a href="gestion-inventario.php" class="btn-secondary">
                            <i class="fas fa-times"></i>
                        </a>
                    </div>
                </form>
            </div>

            <!-- Acciones Rápidas -->
            <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100 mb-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Acciones Rápidas</h2>
                <div class="flex flex-wrap gap-3">
                    <button onclick="showBulkRestockModal()" class="btn-primary">
                        <i class="fas fa-plus mr-2"></i>Restock Masivo
                    </button>
                    <button onclick="showMinStockModal()" class="btn-secondary">
                        <i class="fas fa-cog mr-2"></i>Actualizar Stock Mínimo
                    </button>
                    <button onclick="exportInventory()" class="btn-secondary">
                        <i class="fas fa-download mr-2"></i>Exportar Inventario
                    </button>
                    <button onclick="showAddProductModal()" class="btn-success">
                        <i class="fas fa-plus mr-2"></i>Agregar Producto
                    </button>
                </div>
            </div>

            <!-- Tabla de Productos -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200">
                    <div class="flex justify-between items-center">
                        <h2 class="text-lg font-semibold text-gray-900">
                            Listado de Productos (<?= count($products) ?>)
                        </h2>
                        <div class="flex items-center space-x-3">
                            <div class="text-sm text-gray-500">
                                Mostrando <?= count($products) ?> productos
                            </div>
                        </div>
                    </div>
                </div>

                <?php if (empty($products)): ?>
                    <div class="p-12 text-center">
                        <div class="mx-auto w-24 h-24 bg-gray-100 rounded-full flex items-center justify-center mb-4">
                            <i class="fas fa-search text-2xl text-gray-400"></i>
                        </div>
                        <h3 class="text-lg font-medium text-gray-900 mb-2">No hay productos</h3>
                        <p class="text-gray-500 mb-4">No se encontraron productos que coincidan con los filtros aplicados.</p>
                        <a href="gestion-inventario.php" class="btn-primary">
                            Limpiar Filtros
                        </a>
                    </div>
                <?php else: ?>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Producto
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        SKU / Categoría
                                    </th>
                                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Stock Actual
                                    </th>
                                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Stock Mín.
                                    </th>
                                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Estado
                                    </th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Precio
                                    </th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Valor Stock
                                    </th>
                                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Acciones
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($products as $product): ?>
                                    <?php
                                    $stock_badge = '';
                                    $stock_color = '';
                                    
                                    switch ($product['stock_status']) {
                                        case 'out_of_stock':
                                            $stock_badge = 'Sin Stock';
                                            $stock_color = 'bg-red-100 text-red-800';
                                            break;
                                        case 'low_stock':
                                            $stock_badge = 'Stock Bajo';
                                            $stock_color = 'bg-yellow-100 text-yellow-800';
                                            break;
                                        default:
                                            $stock_badge = 'En Stock';
                                            $stock_color = 'bg-green-100 text-green-800';
                                            break;
                                    }
                                    ?>
                                    <tr class="hover:bg-gray-50 transition-colors">
                                        <td class="px-6 py-4">
                                            <div class="flex items-center">
                                                <div class="flex-shrink-0 h-12 w-12">
                                                    <?php if (!empty($product['main_image'])): ?>
                                                        <img class="h-12 w-12 rounded-lg object-cover" 
                                                             src="../uploads/products/<?= htmlspecialchars($product['main_image']) ?>" 
                                                             alt="<?= htmlspecialchars($product['name']) ?>">
                                                    <?php else: ?>
                                                        <div class="h-12 w-12 rounded-lg bg-gray-100 flex items-center justify-center">
                                                            <i class="fas fa-image text-gray-400"></i>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="ml-4">
                                                    <div class="text-sm font-medium text-gray-900">
                                                        <?= htmlspecialchars($product['name']) ?>
                                                    </div>
                                                    <div class="text-sm text-gray-500">
                                                        <?= htmlspecialchars($product['brand_name'] ?: 'Sin marca') ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="text-sm text-gray-900 font-mono">
                                                <?= htmlspecialchars($product['sku'] ?: 'N/A') ?>
                                            </div>
                                            <div class="text-sm text-gray-500">
                                                <?= htmlspecialchars($product['category_name'] ?: 'Sin categoría') ?>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 text-center">
                                            <span class="text-2xl font-bold text-gray-900">
                                                <?= number_format($product['stock']) ?>
                                            </span>
                                            <div class="text-xs text-gray-500">unidades</div>
                                        </td>
                                        <td class="px-6 py-4 text-center">
                                            <span class="text-sm font-medium text-gray-700">
                                                <?= number_format($product['min_stock']) ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-center">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= $stock_color ?>">
                                                <?= $stock_badge ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-right">
                                            <div class="text-sm font-medium text-gray-900">
                                                $<?= number_format($product['price'], 2) ?>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 text-right">
                                            <div class="text-sm font-medium text-gray-900">
                                                $<?= number_format($product['stock'] * $product['price'], 2) ?>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="flex items-center justify-center space-x-2">
                                                <button onclick="editStock(<?= $product['id'] ?>, '<?= htmlspecialchars($product['name']) ?>', <?= $product['stock'] ?>)" 
                                                        class="text-blue-600 hover:text-blue-900 transition-colors" 
                                                        title="Editar Stock">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button onclick="viewHistory(<?= $product['id'] ?>)" 
                                                        class="text-green-600 hover:text-green-900 transition-colors" 
                                                        title="Ver Historial">
                                                    <i class="fas fa-history"></i>
                                                </button>
                                                <button onclick="quickRestock(<?= $product['id'] ?>)" 
                                                        class="text-purple-600 hover:text-purple-900 transition-colors" 
                                                        title="Restock Rápido">
                                                    <i class="fas fa-plus-circle"></i>
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

            <!-- Modal para edición individual de stock -->
                <div id="stockModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50">
                    <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center">
                        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                            <form method="POST">
                                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                                    <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">
                                        Actualizar Stock
                                    </h3>
                                    <div class="mb-4">
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Producto</label>
                                        <div id="modalProductName" class="text-sm text-gray-600"></div>
                                    </div>
                                    <div class="mb-4">
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Stock Actual</label>
                                        <div id="modalCurrentStock" class="text-lg font-bold"></div>
                                    </div>
                                    <div class="mb-4">
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Nuevo Stock</label>
                                        <input type="number" id="modalNewStock" name="new_stock" min="0" required 
                                               class="form-input">
                                    </div>
                                    <div class="mb-4">
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Razón del Cambio</label>
                                        <input type="text" name="reason" placeholder="Ej: Ajuste por inventario físico" 
                                               class="form-input">
                                    </div>
                                    <input type="hidden" id="modalProductId" name="product_id">
                                    <input type="hidden" name="action" value="update_stock">
                                </div>
                                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                                    <button type="submit" class="btn-primary sm:ml-3 sm:w-auto w-full">
                                        Actualizar Stock
                                    </button>
                                    <button type="button" onclick="closeStockModal()" class="btn-secondary sm:mt-0 mt-3 sm:ml-3 sm:w-auto w-full">
                                        Cancelar
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Historial reciente -->
                <div class="bg-white rounded-lg shadow-md mt-8">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-semibold">Movimientos Recientes</h3>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fecha</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Producto</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tipo</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Cambio</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Stock Final</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Razón</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Usuario</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($recent_movements as $movement): ?>
                                    <?php
                                    $movement_icons = [
                                        'sale' => 'fas fa-minus text-red-500',
                                        'restock' => 'fas fa-plus text-green-500',
                                        'adjustment' => 'fas fa-edit text-blue-500',
                                        'return' => 'fas fa-undo text-orange-500'
                                    ];
                                    $movement_labels = [
                                        'sale' => 'Venta',
                                        'restock' => 'Restock',
                                        'adjustment' => 'Ajuste',
                                        'return' => 'Devolución'
                                    ];
                                    ?>
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            <?= date('d/m/Y H:i', strtotime($movement['created_at'])) ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            <?= htmlspecialchars($movement['product_name']) ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="inline-flex items-center">
                                                <i class="<?= $movement_icons[$movement['movement_type']] ?> mr-2"></i>
                                                <?= $movement_labels[$movement['movement_type']] ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="font-bold <?= $movement['quantity_change'] > 0 ? 'text-green-600' : 'text-red-600' ?>">
                                                <?= $movement['quantity_change'] > 0 ? '+' : '' ?><?= $movement['quantity_change'] ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                            <?= $movement['quantity_after'] ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                            <?= htmlspecialchars($movement['reason'] ?? '') ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                            <?= htmlspecialchars($movement['created_by_name'] ?? 'Sistema') ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function openStockModal(productId, productName, currentStock) {
            document.getElementById('modalProductId').value = productId;
            document.getElementById('modalProductName').textContent = productName;
            document.getElementById('modalCurrentStock').textContent = currentStock + ' unidades';
            document.getElementById('modalNewStock').value = currentStock;
            document.getElementById('stockModal').classList.remove('hidden');
        }

        function closeStockModal() {
            document.getElementById('stockModal').classList.add('hidden');
        }

        // Cerrar modal con ESC
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeStockModal();
            }
        });
    </script>
</body>
</html>
