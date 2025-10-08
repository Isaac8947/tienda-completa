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

// Obtener categorías y marcas para los selects
$categories = $categoryModel->getAll();
$brands = $brandModel->getAll();

// Procesar formulario de edición
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $data = [
            'name' => $_POST['name'] ?? '',
            'description' => $_POST['description'] ?? '',
            'short_description' => $_POST['short_description'] ?? '',
            'price' => floatval($_POST['price'] ?? 0),
            'compare_price' => !empty($_POST['compare_price']) ? floatval($_POST['compare_price']) : null,
            'sku' => $_POST['sku'] ?? '',
            'category_id' => !empty($_POST['category_id']) ? intval($_POST['category_id']) : null,
            'brand_id' => !empty($_POST['brand_id']) ? intval($_POST['brand_id']) : null,
            'inventory_quantity' => intval($_POST['inventory_quantity'] ?? 0),
            'status' => $_POST['status'] ?? 'active'
        ];
        
        if ($productModel->update($productId, $data)) {
            $success = "Producto actualizado exitosamente";
            // Recargar datos del producto
            $product = $productModel->findById($productId);
        } else {
            $error = "Error al actualizar el producto";
        }
    } catch (Exception $e) {
        $error = "Error: " . $e->getMessage();
    }
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
?>

<!DOCTYPE html>
<html lang="es" class="h-full bg-gray-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Producto - <?php echo htmlspecialchars($product['name']); ?> | Odisea Makeup Admin</title>
    <link rel="shortcut icon" href="<?php echo BASE_URL; ?>/assets/images/favicon.ico" type="image/x-icon">
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Custom Admin CSS -->
    <link rel="stylesheet" href="../admin/assets/css/admin.css">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
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
    
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
    </style>
</head>
<body class="bg-gray-50">
    <div class="flex h-screen bg-gray-50">
        <?php include '../admin/includes/sidebar.php'; ?>
        
        <div class="flex-1 flex flex-col min-w-0">
            <?php include '../admin/includes/header.php'; ?>
            
            <main class="flex-1 bg-gray-50 p-4 lg:p-6 overflow-y-auto relative pt-8">
                <!-- Page Header -->
                <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between mb-8">
                    <div class="mb-4 lg:mb-0">
                        <div class="flex items-center mb-2">
                            <a href="products-view.php?id=<?php echo $product['id']; ?>" class="text-gray-500 hover:text-gray-700 mr-4 p-2 hover:bg-gray-100 rounded-lg transition-colors">
                                <i class="fas fa-arrow-left"></i>
                            </a>
                            <h1 class="text-2xl lg:text-3xl font-bold text-gray-900">Editar Producto</h1>
                        </div>
                        <p class="text-gray-600"><?php echo htmlspecialchars($product['name']); ?></p>
                    </div>
                    <div class="flex flex-col sm:flex-row space-y-2 sm:space-y-0 sm:space-x-3">
                        <a href="products-view.php?id=<?php echo $product['id']; ?>" 
                           class="bg-gray-100 text-gray-700 px-6 py-2 rounded-lg hover:bg-gray-200 transition-colors flex items-center justify-center">
                            <i class="fas fa-eye mr-2"></i>Ver
                        </a>
                        <a href="products.php" 
                           class="bg-gray-100 text-gray-700 px-6 py-2 rounded-lg hover:bg-gray-200 transition-colors flex items-center justify-center">
                            <i class="fas fa-list mr-2"></i>Lista
                        </a>
                    </div>
                </div>

                </div>

                <!-- Mensajes -->
                <?php if (isset($success)): ?>
                <div class="mb-6 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg">
                    <div class="flex items-center">
                        <i class="fas fa-check-circle mr-2"></i>
                        <?php echo $success; ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <?php if (isset($error)): ?>
                <div class="mb-6 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg">
                    <div class="flex items-center">
                        <i class="fas fa-exclamation-circle mr-2"></i>
                        <?php echo $error; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Formulario de edición -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 relative z-0">
                    <form method="POST">
                        <!-- Header del formulario -->
                        <div class="px-6 py-5 border-b border-gray-200">
                            <h3 class="text-lg font-semibold text-gray-900 flex items-center">
                                <i class="fas fa-edit text-blue-500 mr-3"></i>
                                Información del Producto
                            </h3>
                            <p class="text-sm text-gray-600 mt-1">Actualiza la información del producto</p>
                        </div>

                        <!-- Contenido del formulario -->
                        <div class="px-6 py-6">
                            <div class="max-w-4xl mx-auto space-y-8">
                            <div class="max-w-4xl mx-auto space-y-8">
                                <!-- Información básica -->
                                <div>
                                    <h4 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                                        <i class="fas fa-info-circle text-blue-500 mr-3"></i>
                                        Información Básica
                                    </h4>
                                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                                        <!-- Nombre -->
                                        <div>
                                            <label for="name" class="block text-sm font-medium text-gray-700 mb-2">
                                                Nombre del Producto <span class="text-red-500">*</span>
                                            </label>
                                            <input type="text" name="name" id="name" required 
                                                   value="<?php echo htmlspecialchars($product['name']); ?>"
                                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all">
                                        </div>
                                        
                                        <!-- SKU -->
                                        <div>
                                            <label for="sku" class="block text-sm font-medium text-gray-700 mb-2">SKU</label>
                                            <input type="text" name="sku" id="sku" 
                                                   value="<?php echo htmlspecialchars($product['sku'] ?? ''); ?>"
                                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all">
                                        </div>
                                    </div>
                                </div>

                                <!-- Precios -->
                                <div>
                                    <h4 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                                        <i class="fas fa-dollar-sign text-green-500 mr-3"></i>
                                        Precios
                                    </h4>
                                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                                        <!-- Precio -->
                                        <div>
                                            <label for="price" class="block text-sm font-medium text-gray-700 mb-2">
                                                Precio <span class="text-red-500">*</span>
                                            </label>
                                            <div class="relative">
                                                <input type="number" name="price" id="price" step="0.01" required 
                                                       value="<?php echo $product['price']; ?>"
                                                       class="w-full px-4 py-3 pl-8 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all">
                                                <span class="absolute left-3 top-3 text-gray-500">$</span>
                                            </div>
                                        </div>
                                        
                                        <!-- Precio de comparación -->
                                        <div>
                                            <label for="compare_price" class="block text-sm font-medium text-gray-700 mb-2">Precio de Comparación</label>
                                            <div class="relative">
                                                <input type="number" name="compare_price" id="compare_price" step="0.01" 
                                                       value="<?php echo $product['compare_price'] ?? ''; ?>"
                                                       class="w-full px-4 py-3 pl-8 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all">
                                                <span class="absolute left-3 top-3 text-gray-500">$</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Categorización -->
                                <div>
                                    <h4 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                                        <i class="fas fa-tags text-purple-500 mr-3"></i>
                                        Categorización
                                    </h4>
                                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                                        <!-- Categoría -->
                                        <div>
                                            <label for="category_id" class="block text-sm font-medium text-gray-700 mb-2">Categoría</label>
                                            <select name="category_id" id="category_id" 
                                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all bg-white">
                                                <option value="">Seleccionar categoría</option>
                                                <?php foreach ($categories as $category): ?>
                                                <option value="<?php echo $category['id']; ?>" 
                                                        <?php echo $product['category_id'] == $category['id'] ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($category['name']); ?>
                                                </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        
                                        <!-- Marca -->
                                        <div>
                                            <label for="brand_id" class="block text-sm font-medium text-gray-700 mb-2">Marca</label>
                                            <select name="brand_id" id="brand_id" 
                                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all bg-white">
                                                <option value="">Seleccionar marca</option>
                                                <?php foreach ($brands as $brand): ?>
                                                <option value="<?php echo $brand['id']; ?>" 
                                                        <?php echo $product['brand_id'] == $brand['id'] ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($brand['name']); ?>
                                                </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <!-- Inventario y Estado -->
                                <div>
                                    <h4 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                                        <i class="fas fa-warehouse text-orange-500 mr-3"></i>
                                        Inventario y Estado
                                    </h4>
                                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                                        <!-- Inventario -->
                                        <div>
                                            <label for="inventory_quantity" class="block text-sm font-medium text-gray-700 mb-2">Cantidad en Inventario</label>
                                            <input type="number" name="inventory_quantity" id="inventory_quantity" min="0" 
                                                   value="<?php echo $product['inventory_quantity'] ?? 0; ?>"
                                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all">
                                        </div>
                                        
                                        <!-- Estado -->
                                        <div>
                                            <label for="status" class="block text-sm font-medium text-gray-700 mb-2">Estado</label>
                                            <select name="status" id="status" 
                                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all bg-white">
                                                <option value="active" <?php echo $product['status'] === 'active' ? 'selected' : ''; ?>>
                                                    ● Activo
                                                </option>
                                                <option value="inactive" <?php echo $product['status'] === 'inactive' ? 'selected' : ''; ?>>
                                                    ● Inactivo
                                                </option>
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <!-- Descripciones -->
                                <div>
                                    <h4 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                                        <i class="fas fa-align-left text-indigo-500 mr-3"></i>
                                        Descripciones
                                    </h4>
                                    
                                    <div class="space-y-6">
                                        <!-- Descripción corta -->
                                        <div>
                                            <label for="short_description" class="block text-sm font-medium text-gray-700 mb-2">Descripción Corta</label>
                                            <textarea name="short_description" id="short_description" rows="3" 
                                                      placeholder="Breve descripción del producto..."
                                                      class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all resize-none"><?php echo htmlspecialchars($product['short_description'] ?? ''); ?></textarea>
                                        </div>
                                        
                                        <!-- Descripción -->
                                        <div>
                                            <label for="description" class="block text-sm font-medium text-gray-700 mb-2">Descripción Completa</label>
                                            <textarea name="description" id="description" rows="4" 
                                                      placeholder="Descripción detallada del producto..."
                                                      class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all resize-none"><?php echo htmlspecialchars($product['description'] ?? ''); ?></textarea>
                                        </div>
                                    </div>
                            </div>
                        </div>
                        
                        <!-- Footer con botones -->
                        <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 rounded-b-xl">
                            <div class="flex flex-col sm:flex-row justify-between items-center space-y-3 sm:space-y-0">
                                <div class="flex items-center text-sm text-gray-500">
                                    <i class="fas fa-info-circle mr-2"></i>
                                    Todos los campos marcados con * son obligatorios
                                </div>
                                <div class="flex space-x-3">
                                    <a href="products-view.php?id=<?php echo $product['id']; ?>" 
                                       class="px-5 py-2.5 bg-white border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors duration-200 flex items-center font-medium">
                                        <i class="fas fa-times mr-2"></i>
                                        Cancelar
                                    </a>
                                    <button type="submit" 
                                            class="px-5 py-2.5 bg-gradient-to-r from-blue-600 to-blue-700 text-white rounded-lg hover:from-blue-700 hover:to-blue-800 transition-all duration-200 flex items-center font-medium shadow-lg hover:shadow-xl">
                                        <i class="fas fa-save mr-2"></i>
                                        Guardar Cambios
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </main>
        </div>
    </div>

    <script>
        // Validación en tiempo real
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.querySelector('form');
            const requiredFields = form.querySelectorAll('input[required], select[required], textarea[required]');
            
            // Validar campos requeridos
            requiredFields.forEach(field => {
                field.addEventListener('blur', function() {
                    if (!this.value.trim()) {
                        this.classList.add('border-red-300', 'focus:ring-red-500', 'focus:border-red-500');
                        this.classList.remove('border-gray-300', 'focus:ring-blue-500', 'focus:border-transparent');
                    } else {
                        this.classList.remove('border-red-300', 'focus:ring-red-500', 'focus:border-red-500');
                        this.classList.add('border-gray-300', 'focus:ring-blue-500', 'focus:border-transparent');
                    }
                });
            });
            
            // Validar precios
            const priceField = document.getElementById('price');
            const comparePriceField = document.getElementById('compare_price');
            
            function validatePrices() {
                const price = parseFloat(priceField.value) || 0;
                const comparePrice = parseFloat(comparePriceField.value) || 0;
                
                if (comparePrice > 0 && comparePrice <= price) {
                    comparePriceField.classList.add('border-amber-300', 'focus:ring-amber-500');
                    comparePriceField.title = 'El precio de comparación debería ser mayor al precio regular';
                } else {
                    comparePriceField.classList.remove('border-amber-300', 'focus:ring-amber-500');
                    comparePriceField.title = '';
                }
            }
            
            priceField.addEventListener('input', validatePrices);
            comparePriceField.addEventListener('input', validatePrices);
            
            // Auto-generar SKU si está vacío
            const nameField = document.getElementById('name');
            const skuField = document.getElementById('sku');
            
            nameField.addEventListener('input', function() {
                if (!skuField.value) {
                    const sku = this.value
                        .toUpperCase()
                        .replace(/[^A-Z0-9]/g, '')
                        .substring(0, 10);
                    skuField.value = sku;
                }
            });
        });
    </script>
</body>
</html>
