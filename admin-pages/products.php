<?php
session_start();
require_once '../config/config.php';
require_once '../models/Product.php';
require_once '../models/Category.php';
require_once '../models/Brand.php';
require_once '../models/Admin.php';

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

// Make basic stats available for sidebar
$stats = [
    'pending_orders' => 0
];

// Try to get real stats if database is available
if (class_exists('Database')) {
    try {
        $db = new Database();
        $pdo = $db->getConnection();
        
        // Get pending orders count
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM orders WHERE status = 'pending'");
        $stmt->execute();
        $stats['pending_orders'] = $stmt->fetch()['count'] ?? 0;
        
    } catch (Exception $e) {
        // Keep default values if database error occurs
        error_log("Stats error in products.php: " . $e->getMessage());
    }
}

$product = new Product();
$category = new Category();
$brand = new Brand();

// Manejar acciones en lote y AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    $action = $_POST['action'] ?? '';
    
    if ($action === 'bulk_action') {
        $bulkAction = $_POST['bulk_action'] ?? '';
        $productIds = json_decode($_POST['product_ids'] ?? '[]', true);
        
        if (empty($productIds)) {
            echo json_encode(['success' => false, 'message' => 'No se seleccionaron productos']);
            exit;
        }
        
        try {
            $success = false;
            $message = '';
            
            switch ($bulkAction) {
                case 'activate':
                    $success = $product->bulkUpdateStatus($productIds, 'active');
                    $message = $success ? 'Productos activados exitosamente' : 'Error al activar productos';
                    break;
                    
                case 'archive':
                    $success = $product->bulkUpdateStatus($productIds, 'archived');
                    $message = $success ? 'Productos archivados exitosamente' : 'Error al archivar productos';
                    break;
                    
                case 'delete':
                    $success = $product->bulkDelete($productIds);
                    $message = $success ? 'Productos eliminados exitosamente' : 'Error al eliminar productos';
                    break;
                    
                default:
                    echo json_encode(['success' => false, 'message' => 'Acción no válida']);
                    exit;
            }
            
            echo json_encode(['success' => $success, 'message' => $message]);
            exit;
            
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
            exit;
        }
    }
    
    if ($action === 'bulk_edit') {
        $productIds = json_decode($_POST['product_ids'] ?? '[]', true);
        $updates = json_decode($_POST['updates'] ?? '{}', true);
        
        if (empty($productIds) || empty($updates)) {
            echo json_encode(['success' => false, 'message' => 'Datos insuficientes para la actualización']);
            exit;
        }
        
        try {
            $success = $product->bulkUpdate($productIds, $updates);
            $message = $success ? 'Productos actualizados exitosamente' : 'Error al actualizar productos';
            
            echo json_encode(['success' => $success, 'message' => $message]);
            exit;
            
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
            exit;
        }
    }
    
    echo json_encode(['success' => false, 'message' => 'Acción no reconocida']);
    exit;
}

// Filtros y paginación
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

$filters = [
    'search' => $_GET['search'] ?? '',
    'category' => $_GET['category'] ?? '',
    'brand' => $_GET['brand'] ?? '',
    'status' => $_GET['status'] ?? '',
    'stock' => $_GET['stock'] ?? ''
];

// Obtener productos con filtros
$products = $product->getProductsWithFilters($filters, $limit, $offset);
$totalProducts = $product->countProductsWithFilters($filters);
$totalPages = ceil($totalProducts / $limit);

// Obtener categorías y marcas para filtros
$categories = $category->findAll(['is_active' => 1]);
$brands = $brand->findAll(['is_active' => 1]);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Productos - Odisea Admin</title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../admin/assets/css/admin.css">
    
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
                        <h1 class="text-2xl lg:text-3xl font-bold text-gray-900">Productos</h1>
                        <p class="text-gray-600 mt-1">Gestiona tu catálogo de productos</p>
                    </div>
                    <div class="flex flex-col sm:flex-row space-y-2 sm:space-y-0 sm:space-x-3">
                        <button class="bg-gray-100 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-200 transition-colors duration-200 flex items-center justify-center">
                            <i class="fas fa-download mr-2"></i>
                            <span class="hidden sm:inline">Exportar</span>
                        </button>
                        <button class="bg-gray-100 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-200 transition-colors duration-200 flex items-center justify-center">
                            <i class="fas fa-upload mr-2"></i>
                            <span class="hidden sm:inline">Importar</span>
                        </button>
                        <a href="../admin-pages/products-add.php" class="bg-gradient-to-r from-primary-500 to-primary-600 text-white px-6 py-2 rounded-lg hover:from-primary-600 hover:to-primary-700 transition-all duration-200 flex items-center justify-center">
                            <i class="fas fa-plus mr-2"></i>
                            Agregar Producto
                        </a>
                    </div>
                </div>

                <!-- Filters -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 lg:p-6 mb-6">
                    <form method="GET" class="space-y-4">
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
                            <!-- Search -->
                            <div class="lg:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Buscar</label>
                                <div class="relative">
                                    <input type="text" name="search" value="<?php echo htmlspecialchars($filters['search']); ?>" 
                                           placeholder="Nombre, SKU, descripción..." 
                                           class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <i class="fas fa-search text-gray-400"></i>
                                    </div>
                                </div>
                            </div>

                            <!-- Category -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Categoría</label>
                                <select name="category" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                                    <option value="">Todas las categorías</option>
                                    <?php foreach ($categories as $cat): ?>
                                        <option value="<?php echo $cat['id']; ?>" <?php echo $filters['category'] == $cat['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($cat['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- Brand -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Marca</label>
                                <select name="brand" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                                    <option value="">Todas las marcas</option>
                                    <?php foreach ($brands as $b): ?>
                                        <option value="<?php echo $b['id']; ?>" <?php echo $filters['brand'] == $b['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($b['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- Status -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Estado</label>
                                <select name="status" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                                    <option value="">Todos los estados</option>
                                    <option value="active" <?php echo $filters['status'] == 'active' ? 'selected' : ''; ?>>Activo</option>
                                    <option value="draft" <?php echo $filters['status'] == 'draft' ? 'selected' : ''; ?>>Borrador</option>
                                    <option value="archived" <?php echo $filters['status'] == 'archived' ? 'selected' : ''; ?>>Archivado</option>
                                </select>
                            </div>
                        </div>

                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between space-y-3 sm:space-y-0">
                            <div class="flex items-center space-x-4">
                                <label class="flex items-center">
                                    <input type="checkbox" name="stock" value="low" <?php echo $filters['stock'] == 'low' ? 'checked' : ''; ?> 
                                           class="rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                                    <span class="ml-2 text-sm text-gray-700">Solo stock bajo</span>
                                </label>
                            </div>
                            
                            <div class="flex space-x-3">
                                <button type="submit" class="bg-primary-500 text-white px-6 py-2 rounded-lg hover:bg-primary-600 transition-colors duration-200">
                                    <i class="fas fa-filter mr-2"></i>
                                    Filtrar
                                </button>
                                <a href="products.php" class="bg-gray-100 text-gray-700 px-6 py-2 rounded-lg hover:bg-gray-200 transition-colors duration-200">
                                    <i class="fas fa-times mr-2"></i>
                                    Limpiar
                                </a>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Products Table -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                    <!-- Table Header -->
                    <div class="px-4 lg:px-6 py-4 border-b border-gray-200 bg-gray-50">
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
                            <h3 class="text-lg font-semibold text-gray-900 mb-2 sm:mb-0">
                                <?php echo number_format($totalProducts); ?> productos encontrados
                            </h3>
                            <div class="flex items-center space-x-3">
                                <select class="px-3 py-1 border border-gray-300 rounded text-sm focus:outline-none focus:ring-2 focus:ring-primary-500">
                                    <option>Ordenar por: Más reciente</option>
                                    <option>Nombre A-Z</option>
                                    <option>Nombre Z-A</option>
                                    <option>Precio menor</option>
                                    <option>Precio mayor</option>
                                    <option>Stock menor</option>
                                    <option>Stock mayor</option>
                                </select>
                                <div class="flex border border-gray-300 rounded">
                                    <button class="px-3 py-1 bg-primary-500 text-white text-sm">
                                        <i class="fas fa-th-list"></i>
                                    </button>
                                    <button class="px-3 py-1 text-gray-600 hover:bg-gray-100 text-sm">
                                        <i class="fas fa-th-large"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Mobile Cards View -->
                    <div class="block lg:hidden">
                        <?php foreach ($products as $prod): ?>
                        <div class="p-4 border-b border-gray-100 hover:bg-gray-50">
                            <div class="flex items-start space-x-4">
                                <div class="w-16 h-16 bg-gray-100 rounded-lg overflow-hidden flex-shrink-0">
                                    <?php if ($prod['main_image']): ?>
                                        <img src="<?php echo BASE_URL . '/' . htmlspecialchars($prod['main_image']); ?>" alt="<?php echo htmlspecialchars($prod['name']); ?>" class="w-full h-full object-cover"
                                             onerror="this.src='<?php echo BASE_URL; ?>/assets/images/placeholder-product.svg'">
                                    <?php else: ?>
                                        <div class="w-full h-full flex items-center justify-center">
                                            <i class="fas fa-image text-gray-400 text-xl"></i>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-start justify-between mb-2">
                                        <div>
                                            <h4 class="text-sm font-semibold text-gray-900 truncate"><?php echo htmlspecialchars($prod['name']); ?></h4>
                                            <p class="text-xs text-gray-500">SKU: <?php echo htmlspecialchars($prod['sku']); ?></p>
                                        </div>
                                        <div class="flex items-center space-x-2 ml-2">
                                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium
                                                <?php echo $prod['status'] === 'active' ? 'bg-green-100 text-green-800' : 
                                                         ($prod['status'] === 'draft' ? 'bg-yellow-100 text-yellow-800' : 'bg-gray-100 text-gray-800'); ?>">
                                                <?php echo ucfirst($prod['status']); ?>
                                            </span>
                                        </div>
                                    </div>
                                    
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <p class="text-sm font-semibold text-gray-900"><?php echo formatPrice($prod['price']); ?></p>
                                            <p class="text-xs text-gray-500">Stock: <?php echo $prod['inventory_quantity']; ?></p>
                                        </div>
                                        <div class="flex items-center space-x-2">
                                            <a href="products-edit.php?id=<?php echo $prod['id']; ?>" class="text-primary-600 hover:text-primary-700" title="Editar producto">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="product-images.php?id=<?php echo $prod['id']; ?>" class="text-blue-600 hover:text-blue-700" title="Gestionar imágenes">
                                                <i class="fas fa-images"></i>
                                            </a>
                                            <button onclick="deleteProduct(<?php echo $prod['id']; ?>)" class="text-red-600 hover:text-red-700" title="Eliminar producto">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Desktop Table View -->
                    <div class="hidden lg:block overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        <input type="checkbox" class="rounded border-gray-300 text-primary-600 focus:ring-primary-500" id="select-all">
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Producto</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">SKU</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Categoría</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Precio</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Stock</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Estado</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($products as $prod): ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <input type="checkbox" class="rounded border-gray-300 text-primary-600 focus:ring-primary-500" value="<?php echo $prod['id']; ?>">
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div class="w-12 h-12 bg-gray-100 rounded-lg overflow-hidden flex-shrink-0">
                                                <?php if ($prod['main_image']): ?>
                                                    <img src="<?php echo BASE_URL . '/' . htmlspecialchars($prod['main_image']); ?>" alt="<?php echo htmlspecialchars($prod['name']); ?>" class="w-full h-full object-cover"
                                                         onerror="this.src='<?php echo BASE_URL; ?>/assets/images/placeholder-product.svg'">
                                                <?php else: ?>
                                                    <div class="w-full h-full flex items-center justify-center">
                                                        <i class="fas fa-image text-gray-400"></i>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                            <div class="ml-4">
                                                <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($prod['name']); ?></div>
                                                <div class="text-sm text-gray-500"><?php echo htmlspecialchars($prod['brand_name'] ?? 'Sin marca'); ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo htmlspecialchars($prod['sku']); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($prod['category_name'] ?? 'Sin categoría'); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?php echo formatPrice($prod['price']); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="text-sm text-gray-900 <?php echo $prod['inventory_quantity'] <= 5 ? 'text-red-600 font-semibold' : ''; ?>">
                                            <?php echo $prod['inventory_quantity']; ?>
                                        </span>
                                        <?php if ($prod['inventory_quantity'] <= 5): ?>
                                            <i class="fas fa-exclamation-triangle text-red-500 ml-1" title="Stock bajo"></i>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                            <?php echo $prod['status'] === 'active' ? 'bg-green-100 text-green-800' : 
                                                     ($prod['status'] === 'draft' ? 'bg-yellow-100 text-yellow-800' : 'bg-gray-100 text-gray-800'); ?>">
                                            <?php echo ucfirst($prod['status']); ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <div class="flex items-center space-x-3">
                                            <a href="products-view.php?id=<?php echo $prod['id']; ?>" class="text-gray-600 hover:text-gray-900" title="Ver">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="products-edit.php?id=<?php echo $prod['id']; ?>" class="text-primary-600 hover:text-primary-900" title="Editar">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="product-images.php?id=<?php echo $prod['id']; ?>" class="text-blue-600 hover:text-blue-900" title="Gestionar Imágenes">
                                                <i class="fas fa-images"></i>
                                            </a>
                                            <button onclick="duplicateProduct(<?php echo $prod['id']; ?>)" class="text-green-600 hover:text-green-900" title="Duplicar">
                                                <i class="fas fa-copy"></i>
                                            </button>
                                            <button onclick="deleteProduct(<?php echo $prod['id']; ?>)" class="text-red-600 hover:text-red-900" title="Eliminar">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Empty State -->
                    <?php if (empty($products)): ?>
                    <div class="text-center py-12">
                        <div class="w-24 h-24 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                            <i class="fas fa-box text-gray-400 text-3xl"></i>
                        </div>
                        <h3 class="text-lg font-semibold text-gray-900 mb-2">No se encontraron productos</h3>
                        <p class="text-gray-600 mb-6">No hay productos que coincidan con los filtros seleccionados.</p>
                        <div class="flex flex-col sm:flex-row items-center justify-center space-y-2 sm:space-y-0 sm:space-x-3">
                            <a href="../admin-pages/products-add.php" class="bg-primary-500 text-white px-6 py-2 rounded-lg hover:bg-primary-600 transition-colors duration-200">
                                <i class="fas fa-plus mr-2"></i>
                                Agregar Primer Producto
                            </a>
                            <a href="products.php" class="bg-gray-100 text-gray-700 px-6 py-2 rounded-lg hover:bg-gray-200 transition-colors duration-200">
                                <i class="fas fa-times mr-2"></i>
                                Limpiar Filtros
                            </a>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                <div class="flex flex-col sm:flex-row items-center justify-between mt-6 bg-white rounded-xl shadow-sm border border-gray-100 px-4 lg:px-6 py-4">
                    <div class="text-sm text-gray-700 mb-4 sm:mb-0">
                        Mostrando <?php echo (($page - 1) * $limit) + 1; ?> a <?php echo min($page * $limit, $totalProducts); ?> de <?php echo number_format($totalProducts); ?> productos
                    </div>
                    
                    <div class="flex items-center space-x-2">
                        <!-- Previous Page -->
                        <?php if ($page > 1): ?>
                            <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>" 
                               class="px-3 py-2 text-sm text-gray-600 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
                                <i class="fas fa-chevron-left"></i>
                            </a>
                        <?php endif; ?>

                        <!-- Page Numbers -->
                        <?php
                        $start = max(1, $page - 2);
                        $end = min($totalPages, $page + 2);
                        
                        for ($i = $start; $i <= $end; $i++):
                        ?>
                            <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>" 
                               class="px-3 py-2 text-sm <?php echo $i == $page ? 'bg-primary-500 text-white' : 'text-gray-600 bg-white border border-gray-300 hover:bg-gray-50'; ?> rounded-lg">
                                <?php echo $i; ?>
                            </a>
                        <?php endfor; ?>

                        <!-- Next Page -->
                        <?php if ($page < $totalPages): ?>
                            <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>" 
                               class="px-3 py-2 text-sm text-gray-600 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
                                <i class="fas fa-chevron-right"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Bulk Actions -->
                <div class="fixed bottom-6 left-1/2 transform -translate-x-1/2 bg-white rounded-lg shadow-lg border border-gray-200 px-6 py-3 hidden" id="bulk-actions">
                    <div class="flex items-center space-x-4">
                        <span class="text-sm text-gray-600" id="selected-count">0 productos seleccionados</span>
                        <div class="flex items-center space-x-2">
                            <button id="bulk-edit" class="bg-primary-500 text-white px-4 py-2 rounded-lg hover:bg-primary-600 transition-colors duration-200 text-sm">
                                <i class="fas fa-edit mr-2"></i>
                                Editar en lote
                            </button>
                            <button id="bulk-activate" class="bg-green-500 text-white px-4 py-2 rounded-lg hover:bg-green-600 transition-colors duration-200 text-sm">
                                <i class="fas fa-check mr-2"></i>
                                Activar
                            </button>
                            <button id="bulk-archive" class="bg-yellow-500 text-white px-4 py-2 rounded-lg hover:bg-yellow-600 transition-colors duration-200 text-sm">
                                <i class="fas fa-archive mr-2"></i>
                                Archivar
                            </button>
                            <button id="bulk-delete" class="bg-red-500 text-white px-4 py-2 rounded-lg hover:bg-red-600 transition-colors duration-200 text-sm">
                                <i class="fas fa-trash mr-2"></i>
                                Eliminar
                            </button>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden" id="delete-modal">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="bg-white rounded-lg shadow-xl max-w-md w-full p-6">
                <div class="flex items-center justify-center w-12 h-12 mx-auto bg-red-100 rounded-full mb-4">
                    <i class="fas fa-exclamation-triangle text-red-600 text-xl"></i>
                </div>
                <h3 class="text-lg font-semibold text-gray-900 text-center mb-2">¿Eliminar producto?</h3>
                <p class="text-gray-600 text-center mb-6">Esta acción no se puede deshacer. El producto será eliminado permanentemente.</p>
                <div class="flex space-x-3">
                    <button onclick="closeDeleteModal()" class="flex-1 bg-gray-100 text-gray-700 py-2 px-4 rounded-lg hover:bg-gray-200 transition-colors duration-200">
                        Cancelar
                    </button>
                    <button onclick="confirmDelete()" class="flex-1 bg-red-500 text-white py-2 px-4 rounded-lg hover:bg-red-600 transition-colors duration-200">
                        Eliminar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bulk Edit Modal -->
    <div class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden" id="bulk-edit-modal">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="bg-white rounded-lg shadow-xl max-w-2xl w-full p-6 max-h-[90vh] overflow-y-auto">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-xl font-semibold text-gray-900">Editar productos en lote</h3>
                    <button onclick="closeBulkEditModal()" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
                
                <form id="bulk-edit-form">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Categoría -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                <input type="checkbox" id="update-category" class="mr-2">
                                Cambiar Categoría
                            </label>
                            <select name="category_id" id="bulk-category" class="w-full px-3 py-2 border border-gray-300 rounded-lg" disabled>
                                <option value="">Seleccionar categoría</option>
                                <?php
                                $categoryModel = new Category();
                                $categories = $categoryModel->findAll();
                                foreach ($categories as $category):
                                ?>
                                    <option value="<?php echo $category['id']; ?>"><?php echo htmlspecialchars($category['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <!-- Marca -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                <input type="checkbox" id="update-brand" class="mr-2">
                                Cambiar Marca
                            </label>
                            <select name="brand_id" id="bulk-brand" class="w-full px-3 py-2 border border-gray-300 rounded-lg" disabled>
                                <option value="">Seleccionar marca</option>
                                <?php
                                $brandModel = new Brand();
                                $brands = $brandModel->findAll();
                                foreach ($brands as $brand):
                                ?>
                                    <option value="<?php echo $brand['id']; ?>"><?php echo htmlspecialchars($brand['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <!-- Estado -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                <input type="checkbox" id="update-status" class="mr-2">
                                Cambiar Estado
                            </label>
                            <select name="status" id="bulk-status" class="w-full px-3 py-2 border border-gray-300 rounded-lg" disabled>
                                <option value="active">Activo</option>
                                <option value="draft">Borrador</option>
                                <option value="archived">Archivado</option>
                            </select>
                        </div>
                        
                        <!-- Precio -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                <input type="checkbox" id="update-price" class="mr-2">
                                Ajustar Precio
                            </label>
                            <div class="flex space-x-2">
                                <select id="price-action" class="px-3 py-2 border border-gray-300 rounded-lg" disabled>
                                    <option value="increase">Aumentar</option>
                                    <option value="decrease">Disminuir</option>
                                    <option value="set">Establecer</option>
                                </select>
                                <input type="number" step="0.01" name="price_value" id="bulk-price" class="flex-1 px-3 py-2 border border-gray-300 rounded-lg" placeholder="Valor" disabled>
                                <select id="price-type" class="px-3 py-2 border border-gray-300 rounded-lg" disabled>
                                    <option value="percent">%</option>
                                    <option value="fixed">$</option>
                                </select>
                            </div>
                        </div>
                        
                        <!-- Featured -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                <input type="checkbox" id="update-featured" class="mr-2">
                                Cambiar Destacado
                            </label>
                            <select name="featured" id="bulk-featured" class="w-full px-3 py-2 border border-gray-300 rounded-lg" disabled>
                                <option value="1">Destacado</option>
                                <option value="0">No destacado</option>
                            </select>
                        </div>
                        
                        <!-- Stock -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                <input type="checkbox" id="update-stock" class="mr-2">
                                Ajustar Stock
                            </label>
                            <div class="flex space-x-2">
                                <select id="stock-action" class="px-3 py-2 border border-gray-300 rounded-lg" disabled>
                                    <option value="increase">Aumentar</option>
                                    <option value="decrease">Disminuir</option>
                                    <option value="set">Establecer</option>
                                </select>
                                <input type="number" name="stock_value" id="bulk-stock" class="flex-1 px-3 py-2 border border-gray-300 rounded-lg" placeholder="Cantidad" disabled>
                            </div>
                        </div>
                    </div>
                    
                    <div class="flex justify-end space-x-3 mt-8">
                        <button type="button" onclick="closeBulkEditModal()" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors duration-200">
                            Cancelar
                        </button>
                        <button type="submit" class="px-4 py-2 bg-primary-500 text-white rounded-lg hover:bg-primary-600 transition-colors duration-200">
                            <i class="fas fa-save mr-2"></i>
                            Aplicar cambios
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Bulk Confirmation Modal -->
    <div class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden" id="bulk-confirmation-modal">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="bg-white rounded-lg shadow-xl max-w-md w-full p-6">
                <div class="flex items-center justify-center w-12 h-12 mx-auto bg-blue-100 rounded-full mb-4">
                    <i class="fas fa-question-circle text-blue-600 text-xl"></i>
                </div>
                <h3 class="text-lg font-semibold text-gray-900 text-center mb-2" id="bulk-modal-title">Confirmar acción</h3>
                <p class="text-gray-600 text-center mb-6" id="bulk-modal-message">¿Estás seguro de realizar esta acción?</p>
                <div class="flex space-x-3">
                    <button onclick="closeBulkConfirmationModal()" class="flex-1 bg-gray-100 text-gray-700 py-2 px-4 rounded-lg hover:bg-gray-200 transition-colors duration-200">
                        Cancelar
                    </button>
                    <button onclick="confirmBulkAction()" id="bulk-confirm-btn" class="flex-1 bg-primary-500 text-white py-2 px-4 rounded-lg hover:bg-primary-600 transition-colors duration-200">
                        Confirmar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="../admin/assets/js/admin.js"></script>
    <script>
        let productToDelete = null;

        // Delete product
        function deleteProduct(id) {
            productToDelete = id;
            document.getElementById('delete-modal').classList.remove('hidden');
        }

        function closeDeleteModal() {
            document.getElementById('delete-modal').classList.add('hidden');
            productToDelete = null;
        }

        function confirmDelete() {
            if (productToDelete) {
                // Make AJAX call to delete the product
                fetch(`../api/products/delete.php`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ product_ids: [productToDelete] })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert(data.message);
                        location.reload();
                    } else {
                        alert('Error: ' + (data.message || 'Error al eliminar el producto'));
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error de conexión al eliminar el producto');
                });
            }
            closeDeleteModal();
        }

        // Duplicate product
        function duplicateProduct(id) {
            fetch(`api/products/duplicate.php`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ id: id })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Error al duplicar el producto');
                }
            });
        }

        // Bulk actions
        document.addEventListener('DOMContentLoaded', function() {
            const checkboxes = document.querySelectorAll('input[type="checkbox"][value]');
            const selectAll = document.getElementById('select-all');
            const bulkActions = document.getElementById('bulk-actions');
            const selectedCount = document.getElementById('selected-count');

            function updateBulkActions() {
                const selected = document.querySelectorAll('input[type="checkbox"][value]:checked');
                if (selected.length > 0) {
                    bulkActions.classList.remove('hidden');
                    selectedCount.textContent = `${selected.length} producto${selected.length > 1 ? 's' : ''} seleccionado${selected.length > 1 ? 's' : ''}`;
                } else {
                    bulkActions.classList.add('hidden');
                }
            }

            checkboxes.forEach(checkbox => {
                checkbox.addEventListener('change', updateBulkActions);
            });

            if (selectAll) {
                selectAll.addEventListener('change', function() {
                    checkboxes.forEach(checkbox => {
                        checkbox.checked = this.checked;
                    });
                    updateBulkActions();
                });
            }

            // Bulk action handlers
            document.getElementById('bulk-edit').addEventListener('click', function() {
                const selected = getSelectedProducts();
                if (selected.length > 0) {
                    openBulkEditModal();
                }
            });

            document.getElementById('bulk-activate').addEventListener('click', function() {
                const selected = getSelectedProducts();
                if (selected.length > 0) {
                    showBulkConfirmation('Activar productos', `¿Activar ${selected.length} producto${selected.length > 1 ? 's' : ''}?`, 'activate');
                }
            });

            document.getElementById('bulk-archive').addEventListener('click', function() {
                const selected = getSelectedProducts();
                if (selected.length > 0) {
                    showBulkConfirmation('Archivar productos', `¿Archivar ${selected.length} producto${selected.length > 1 ? 's' : ''}?`, 'archive');
                }
            });

            document.getElementById('bulk-delete').addEventListener('click', function() {
                const selected = getSelectedProducts();
                if (selected.length > 0) {
                    showBulkConfirmation('Eliminar productos', `¿Eliminar permanentemente ${selected.length} producto${selected.length > 1 ? 's' : ''}? Esta acción no se puede deshacer.`, 'delete');
                }
            });

            // Bulk edit form checkboxes
            const updateCheckboxes = ['update-category', 'update-brand', 'update-status', 'update-price', 'update-featured', 'update-stock'];
            updateCheckboxes.forEach(id => {
                const checkbox = document.getElementById(id);
                const fieldIds = {
                    'update-category': ['bulk-category'],
                    'update-brand': ['bulk-brand'],
                    'update-status': ['bulk-status'],
                    'update-price': ['price-action', 'bulk-price', 'price-type'],
                    'update-featured': ['bulk-featured'],
                    'update-stock': ['stock-action', 'bulk-stock']
                };

                if (checkbox) {
                    checkbox.addEventListener('change', function() {
                        const fields = fieldIds[id];
                        fields.forEach(fieldId => {
                            const field = document.getElementById(fieldId);
                            if (field) {
                                field.disabled = !this.checked;
                            }
                        });
                    });
                }
            });

            // Bulk edit form submission
            document.getElementById('bulk-edit-form').addEventListener('submit', function(e) {
                e.preventDefault();
                submitBulkEdit();
            });
        });

        function getSelectedProducts() {
            const selected = document.querySelectorAll('input[type="checkbox"][value]:checked');
            return Array.from(selected).map(cb => cb.value);
        }

        function openBulkEditModal() {
            document.getElementById('bulk-edit-modal').classList.remove('hidden');
        }

        function closeBulkEditModal() {
            document.getElementById('bulk-edit-modal').classList.add('hidden');
            // Reset form
            document.getElementById('bulk-edit-form').reset();
            // Reset checkboxes and disable fields
            const updateCheckboxes = ['update-category', 'update-brand', 'update-status', 'update-price', 'update-featured', 'update-stock'];
            updateCheckboxes.forEach(id => {
                const checkbox = document.getElementById(id);
                if (checkbox) {
                    checkbox.checked = false;
                    checkbox.dispatchEvent(new Event('change'));
                }
            });
        }

        let currentBulkAction = null;

        function showBulkConfirmation(title, message, action) {
            document.getElementById('bulk-modal-title').textContent = title;
            document.getElementById('bulk-modal-message').textContent = message;
            currentBulkAction = action;
            
            const confirmBtn = document.getElementById('bulk-confirm-btn');
            confirmBtn.className = 'flex-1 py-2 px-4 rounded-lg transition-colors duration-200 text-white';
            
            if (action === 'delete') {
                confirmBtn.classList.add('bg-red-500', 'hover:bg-red-600');
            } else if (action === 'activate') {
                confirmBtn.classList.add('bg-green-500', 'hover:bg-green-600');
            } else if (action === 'archive') {
                confirmBtn.classList.add('bg-yellow-500', 'hover:bg-yellow-600');
            } else {
                confirmBtn.classList.add('bg-primary-500', 'hover:bg-primary-600');
            }
            
            document.getElementById('bulk-confirmation-modal').classList.remove('hidden');
        }

        function closeBulkConfirmationModal() {
            document.getElementById('bulk-confirmation-modal').classList.add('hidden');
            currentBulkAction = null;
        }

        function confirmBulkAction() {
            const selectedProducts = getSelectedProducts();
            
            if (selectedProducts.length === 0) {
                alert('No hay productos seleccionados');
                return;
            }

            const formData = new FormData();
            formData.append('action', 'bulk_action');
            formData.append('bulk_action', currentBulkAction);
            formData.append('product_ids', JSON.stringify(selectedProducts));

            fetch('products.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert(data.message || 'Error al realizar la acción');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error al realizar la acción');
            });

            closeBulkConfirmationModal();
        }

        function submitBulkEdit() {
            const selectedProducts = getSelectedProducts();
            const formData = new FormData();
            
            formData.append('action', 'bulk_edit');
            formData.append('product_ids', JSON.stringify(selectedProducts));

            // Check which fields to update
            const updates = {};
            
            if (document.getElementById('update-category').checked) {
                updates.category_id = document.getElementById('bulk-category').value;
            }
            
            if (document.getElementById('update-brand').checked) {
                updates.brand_id = document.getElementById('bulk-brand').value;
            }
            
            if (document.getElementById('update-status').checked) {
                updates.status = document.getElementById('bulk-status').value;
            }
            
            if (document.getElementById('update-featured').checked) {
                updates.featured = document.getElementById('bulk-featured').value;
            }
            
            if (document.getElementById('update-price').checked) {
                updates.price_action = document.getElementById('price-action').value;
                updates.price_value = document.getElementById('bulk-price').value;
                updates.price_type = document.getElementById('price-type').value;
            }
            
            if (document.getElementById('update-stock').checked) {
                updates.stock_action = document.getElementById('stock-action').value;
                updates.stock_value = document.getElementById('bulk-stock').value;
            }

            formData.append('updates', JSON.stringify(updates));

            fetch('products.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert(data.message || 'Error al actualizar los productos');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error al actualizar los productos');
            });

            closeBulkEditModal();
        }
    </script>
</body>
</html>
