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
    <link rel="stylesheet" href="assets/css/admin.css">
    
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
        <?php include 'includes/sidebar.php'; ?>
        
        <div class="flex-1 flex flex-col overflow-hidden">
            <?php include 'includes/header.php'; ?>
            
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
                                        <img src="<?php echo BASE_URL . '/' . htmlspecialchars($prod['main_image']); ?>" alt="<?php echo htmlspecialchars($prod['name']); ?>" class="w-full h-full object-cover">
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
                                            <a href="products-edit.php?id=<?php echo $prod['id']; ?>" class="text-primary-600 hover:text-primary-700">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <button onclick="deleteProduct(<?php echo $prod['id']; ?>)" class="text-red-600 hover:text-red-700">
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
                                                    <img src="<?php echo BASE_URL . '/' . htmlspecialchars($prod['main_image']); ?>" alt="<?php echo htmlspecialchars($prod['name']); ?>" class="w-full h-full object-cover">
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
                                            <button onclick="duplicateProduct(<?php echo $prod['id']; ?>)" class="text-blue-600 hover:text-blue-900" title="Duplicar">
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
                            <button class="bg-primary-500 text-white px-4 py-2 rounded-lg hover:bg-primary-600 transition-colors duration-200 text-sm">
                                <i class="fas fa-edit mr-2"></i>
                                Editar en lote
                            </button>
                            <button class="bg-green-500 text-white px-4 py-2 rounded-lg hover:bg-green-600 transition-colors duration-200 text-sm">
                                <i class="fas fa-check mr-2"></i>
                                Activar
                            </button>
                            <button class="bg-yellow-500 text-white px-4 py-2 rounded-lg hover:bg-yellow-600 transition-colors duration-200 text-sm">
                                <i class="fas fa-archive mr-2"></i>
                                Archivar
                            </button>
                            <button class="bg-red-500 text-white px-4 py-2 rounded-lg hover:bg-red-600 transition-colors duration-200 text-sm">
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

    <script src="assets/js/admin.js"></script>
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
                // Here you would make an AJAX call to delete the product
                fetch(`api/products/delete.php`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ id: productToDelete })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('Error al eliminar el producto');
                    }
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
        });
    </script>
</body>
</html>
