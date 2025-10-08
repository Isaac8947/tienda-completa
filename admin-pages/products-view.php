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

// Verificar que se proporcionó un ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: products.php');
    exit;
}

$productId = (int)$_GET['id'];
$productModel = new Product();
$categoryModel = new Category();
$brandModel = new Brand();

// Obtener el producto
$product = $productModel->findById($productId);
if (!$product) {
    header('Location: products.php?error=product_not_found');
    exit;
}

// Obtener categoría y marca
$category = null;
$brand = null;

if (!empty($product['category_id'])) {
    $category = $categoryModel->findById($product['category_id']);
}

if (!empty($product['brand_id'])) {
    $brand = $brandModel->findById($product['brand_id']);
}

// Obtener datos del admin para el header
$adminData = [];
if (isset($_SESSION['admin_id'])) {
    $adminModel = new Admin();
    $adminData = $adminModel->findById($_SESSION['admin_id']);
    
    if (!$adminData) {
        $adminData = [
            'id' => $_SESSION['admin_id'],
            'name' => $_SESSION['admin_name'] ?? 'Administrador',
            'full_name' => $_SESSION['admin_name'] ?? 'Administrador',
            'email' => $_SESSION['admin_email'] ?? '',
            'role' => $_SESSION['admin_role'] ?? 'admin',
            'avatar' => null
        ];
    }
    
    if (!isset($adminData['full_name']) || empty($adminData['full_name'])) {
        $adminData['full_name'] = $adminData['name'] ?? 
                                  $adminData['first_name'] ?? 
                                  $_SESSION['admin_name'] ?? 
                                  'Administrador';
    }
}

// Procesar imagen del producto
$productImage = BASE_URL . '/assets/images/placeholder-product.svg';
if (!empty($product['main_image'])) {
    if (strpos($product['main_image'], 'uploads/products/') === 0) {
        $productImage = BASE_URL . '/' . $product['main_image'];
    } else {
        $productImage = BASE_URL . '/uploads/products/' . $product['main_image'];
    }
}
?>

<!DOCTYPE html>
<html lang="es" class="h-full bg-gray-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ver Producto - <?php echo htmlspecialchars($product['name']); ?> | Odisea Makeup Admin</title>
    <link rel="shortcut icon" href="<?php echo BASE_URL; ?>/assets/images/favicon.ico" type="image/x-icon">
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        body {
            font-family: 'Inter', sans-serif;
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
                        <div class="flex items-center mb-2">
                            <a href="products.php" class="text-gray-500 hover:text-gray-700 mr-4 p-2 hover:bg-gray-100 rounded-lg transition-colors">
                                <i class="fas fa-arrow-left"></i>
                            </a>
                            <h1 class="text-2xl lg:text-3xl font-bold text-gray-900">Ver Producto</h1>
                        </div>
                        <p class="text-gray-600"><?php echo htmlspecialchars($product['name']); ?></p>
                    </div>
                    <div class="flex flex-col sm:flex-row space-y-2 sm:space-y-0 sm:space-x-3">
                        <a href="products-edit.php?id=<?php echo $product['id']; ?>" 
                           class="bg-primary-500 text-white px-6 py-2 rounded-lg hover:bg-primary-600 transition-colors flex items-center justify-center">
                            <i class="fas fa-edit mr-2"></i>Editar
                        </a>
                        <a href="products.php" 
                           class="bg-gray-100 text-gray-700 px-6 py-2 rounded-lg hover:bg-gray-200 transition-colors flex items-center justify-center">
                            <i class="fas fa-list mr-2"></i>Volver
                        </a>
                    </div>
                </div>

                <!-- Content -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Product Image Card -->
                    <div class="lg:col-span-1">
                        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                            <div class="p-6">
                                <h3 class="text-lg font-medium text-gray-900 mb-4">Imagen del Producto</h3>
                                <div class="aspect-square bg-gray-50 rounded-lg overflow-hidden border border-gray-200">
                                    <img src="<?php echo $productImage; ?>" 
                                         alt="<?php echo htmlspecialchars($product['name']); ?>"
                                         class="w-full h-full object-cover"
                                         onerror="this.src='<?php echo BASE_URL; ?>/assets/images/placeholder-product.svg'">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Product Details Card -->
                    <div class="lg:col-span-2">
                        <div class="bg-white rounded-xl shadow-sm border border-gray-200">
                            <div class="p-6">
                                <h3 class="text-lg font-medium text-gray-900 mb-6">Información del Producto</h3>
                                
                                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-500">Nombre</label>
                                        <p class="mt-1 text-sm text-gray-900 font-medium"><?php echo htmlspecialchars($product['name']); ?></p>
                                    </div>
                                    
                                    <div>
                                        <label class="block text-sm font-medium text-gray-500">SKU</label>
                                        <p class="mt-1 text-sm text-gray-900"><?php echo htmlspecialchars($product['sku'] ?? 'No definido'); ?></p>
                                    </div>
                                    
                                    <div>
                                        <label class="block text-sm font-medium text-gray-500">Precio</label>
                                        <p class="mt-1 text-lg font-semibold text-green-600">$<?php echo number_format($product['price'], 0, ',', '.'); ?></p>
                                    </div>
                                    
                                    <div>
                                        <label class="block text-sm font-medium text-gray-500">Precio de Comparación</label>
                                        <p class="mt-1 text-sm text-gray-900">
                                            <?php if (!empty($product['compare_price'])): ?>
                                                $<?php echo number_format($product['compare_price'], 0, ',', '.'); ?>
                                            <?php else: ?>
                                                <span class="text-gray-400">No definido</span>
                                            <?php endif; ?>
                                        </p>
                                    </div>
                                    
                                    <div>
                                        <label class="block text-sm font-medium text-gray-500">Categoría</label>
                                        <p class="mt-1 text-sm text-gray-900">
                                            <?php if ($category): ?>
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                    <?php echo htmlspecialchars($category['name']); ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="text-gray-400">Sin categoría</span>
                                            <?php endif; ?>
                                        </p>
                                    </div>
                                    
                                    <div>
                                        <label class="block text-sm font-medium text-gray-500">Marca</label>
                                        <p class="mt-1 text-sm text-gray-900">
                                            <?php if ($brand): ?>
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                                                    <?php echo htmlspecialchars($brand['name']); ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="text-gray-400">Sin marca</span>
                                            <?php endif; ?>
                                        </p>
                                    </div>
                                    
                                    <div>
                                        <label class="block text-sm font-medium text-gray-500">Inventario</label>
                                        <p class="mt-1 text-sm">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo ($product['inventory_quantity'] ?? 0) > 10 ? 'bg-green-100 text-green-800' : (($product['inventory_quantity'] ?? 0) > 0 ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800'); ?>">
                                                <?php echo $product['inventory_quantity'] ?? 0; ?> unidades
                                            </span>
                                        </p>
                                    </div>
                                    
                                    <div>
                                        <label class="block text-sm font-medium text-gray-500">Estado</label>
                                        <p class="mt-1">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo $product['status'] === 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                                <span class="w-1.5 h-1.5 mr-1.5 rounded-full <?php echo $product['status'] === 'active' ? 'bg-green-400' : 'bg-red-400'; ?>"></span>
                                                <?php echo $product['status'] === 'active' ? 'Activo' : 'Inactivo'; ?>
                                            </span>
                                        </p>
                                    </div>
                                    
                                    <div class="sm:col-span-2">
                                        <label class="block text-sm font-medium text-gray-500">Descripción Corta</label>
                                        <p class="mt-1 text-sm text-gray-900">
                                            <?php echo !empty($product['short_description']) ? htmlspecialchars($product['short_description']) : '<span class="text-gray-400">No definida</span>'; ?>
                                        </p>
                                    </div>
                                    
                                    <div class="sm:col-span-2">
                                        <label class="block text-sm font-medium text-gray-500">Descripción</label>
                                        <div class="mt-1 text-sm text-gray-900 bg-gray-50 p-3 rounded-lg">
                                            <?php echo !empty($product['description']) ? nl2br(htmlspecialchars($product['description'])) : '<span class="text-gray-400">No definida</span>'; ?>
                                        </div>
                                    </div>
                                    
                                    <div>
                                        <label class="block text-sm font-medium text-gray-500">Fecha de Creación</label>
                                        <p class="mt-1 text-sm text-gray-900">
                                            <i class="fas fa-calendar-plus text-gray-400 mr-1"></i>
                                            <?php echo date('d/m/Y H:i', strtotime($product['created_at'])); ?>
                                        </p>
                                    </div>
                                    
                                    <div>
                                        <label class="block text-sm font-medium text-gray-500">Última Actualización</label>
                                        <p class="mt-1 text-sm text-gray-900">
                                            <i class="fas fa-calendar-edit text-gray-400 mr-1"></i>
                                            <?php echo date('d/m/Y H:i', strtotime($product['updated_at'])); ?>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Additional Information -->
                <?php if (!empty($product['ingredients']) || !empty($product['how_to_use']) || !empty($product['benefits'])): ?>
                <div class="mt-6">
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200">
                        <div class="p-6">
                            <h3 class="text-lg font-medium text-gray-900 mb-6">Información Adicional</h3>
                            
                            <?php if (!empty($product['ingredients'])): ?>
                            <div class="mb-6">
                                <label class="block text-sm font-medium text-gray-500 mb-2">Ingredientes</label>
                                <div class="text-sm text-gray-900 bg-gray-50 p-3 rounded-lg">
                                    <?php echo nl2br(htmlspecialchars($product['ingredients'])); ?>
                                </div>
                            </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($product['how_to_use'])): ?>
                            <div class="mb-6">
                                <label class="block text-sm font-medium text-gray-500 mb-2">Modo de Uso</label>
                                <div class="text-sm text-gray-900 bg-gray-50 p-3 rounded-lg">
                                    <?php echo nl2br(htmlspecialchars($product['how_to_use'])); ?>
                                </div>
                            </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($product['benefits'])): ?>
                            <div class="mb-6">
                                <label class="block text-sm font-medium text-gray-500 mb-2">Beneficios</label>
                                <div class="text-sm text-gray-900 bg-gray-50 p-3 rounded-lg">
                                    <?php echo nl2br(htmlspecialchars($product['benefits'])); ?>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </main>
        </div>
    </div>
</body>
</html>
