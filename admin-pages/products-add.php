<?php
session_start();
require_once '../config/config.php';
require_once '../models/Product.php';
require_once '../models/Category.php';
require_once '../models/Brand.php';
require_once '../models/Admin.php';

// Verificar si el admin está logueado
if (!isAdminLoggedIn()) {
    redirectTo(ADMIN_URL . '/login.php');
}

$admin = new Admin();
$adminData = $admin->findById($_SESSION['admin_id']);

$product = new Product();
$category = new Category();
$brand = new Brand();

// Obtener categorías y marcas para los dropdowns
$categories = $category->findAll(['where' => 'is_active = 1', 'order_by' => 'name ASC']);
$brands = $brand->findAll(['where' => 'is_active = 1', 'order_by' => 'name ASC']);

// Procesar formulario de creación
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $productData = [
        'name' => trim($_POST['name']),
        'slug' => createSlug($_POST['name']),
        'sku' => trim($_POST['sku']),
        'description' => trim($_POST['description']),
        'short_description' => trim($_POST['short_description']),
        'price' => $_POST['price'],
        'compare_price' => !empty($_POST['sale_price']) ? $_POST['sale_price'] : null,
        'inventory_quantity' => $_POST['stock'],
        'category_id' => $_POST['category_id'],
        'brand_id' => !empty($_POST['brand_id']) ? $_POST['brand_id'] : null,
        'featured' => isset($_POST['is_featured']) ? 1 : 0,
        'is_new' => isset($_POST['is_new']) ? 1 : 0,
        'status' => isset($_POST['is_active']) ? 'active' : 'inactive',
        'meta_title' => trim($_POST['meta_title']),
        'meta_description' => trim($_POST['meta_description']),
        'created_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s')
    ];
    
    // Si hay una imagen principal subida o imágenes adicionales
    $uploadedImages = [];
    
    // Procesar imagen principal
    if (!empty($_FILES['main_image']['name'])) {
        $uploadResult = uploadImage($_FILES['main_image'], 'products');
        if ($uploadResult['success']) {
            $productData['main_image'] = $uploadResult['path'];
            $uploadedImages[] = [
                'path' => $uploadResult['path'],
                'is_primary' => true,
                'alt_text' => 'Imagen principal - ' . $productData['name']
            ];
        } else {
            $message = $uploadResult['message'];
            $messageType = 'error';
        }
    }
    
    // Procesar imágenes adicionales (máximo 4 adicionales + 1 principal = 5 total)
    if (!empty($_FILES['additional_images']['name'][0])) {
        $additionalImages = reArrayFiles($_FILES['additional_images']);
        $maxAdditional = 5 - count($uploadedImages); // Máximo 5 imágenes total
        
        foreach (array_slice($additionalImages, 0, $maxAdditional) as $image) {
            if (!empty($image['name'])) {
                $uploadResult = uploadSingleImage($image, 'products');
                if ($uploadResult['success']) {
                    $uploadedImages[] = [
                        'path' => $uploadResult['path'],
                        'is_primary' => false,
                        'alt_text' => 'Imagen adicional - ' . $productData['name']
                    ];
                }
            }
        }
    }
    
    if (empty($message)) {
        // Crear nuevo producto
        $productId = $product->create($productData);
        
        if ($productId) {
            // Guardar todas las imágenes en la nueva tabla product_images
            foreach ($uploadedImages as $index => $imageData) {
                $product->addProductImageNew(
                    $productId,
                    $imageData['path'],
                    $imageData['alt_text'],
                    $imageData['is_primary']
                );
            }
            
            // Procesar atributos
            if (isset($_POST['attribute_name']) && is_array($_POST['attribute_name'])) {
                foreach ($_POST['attribute_name'] as $key => $name) {
                    if (!empty($name) && isset($_POST['attribute_value'][$key]) && !empty($_POST['attribute_value'][$key])) {
                        $product->addProductAttribute($productId, $name, $_POST['attribute_value'][$key]);
                    }
                }
            }
            
            $message = 'Producto creado correctamente con ' . count($uploadedImages) . ' imagen(es)';
            $messageType = 'success';
            
            // Redirigir a la página de edición del producto
            redirectTo(ADMIN_URL . '/products-edit.php?id=' . $productId);
        } else {
            $message = 'Error al crear el producto';
            $messageType = 'error';
        }
    }
}

// Estadísticas para el sidebar
$stats = [
    'pending_orders' => $admin->getPendingOrders(),
    'low_stock_products' => $admin->getLowStockProducts()
];
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agregar Producto - Odisea Admin</title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Quill Editor -->
    <link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
    <script src="https://cdn.quilljs.com/1.3.6/quill.js"></script>
    
    <!-- Custom Admin CSS -->
    <link rel="stylesheet" href="assets/css/admin.css">
    
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
</head>
<body class="bg-gray-50">
    <div class="flex h-screen overflow-hidden">
        <!-- Sidebar -->
        <?php include '../admin/includes/sidebar.php'; ?>
        
        <!-- Main Content -->
        <div class="flex-1 flex flex-col overflow-hidden">
            <!-- Top Header -->
            <?php include '../admin/includes/header.php'; ?>
            
            <!-- Main Content -->
            <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-50 p-4 lg:p-6">
                <!-- Page Header -->
                <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between mb-8">
                    <div class="mb-4 lg:mb-0">
                        <h1 class="text-2xl lg:text-3xl font-bold text-gray-900">Agregar Producto</h1>
                        <p class="text-gray-600 mt-1">Crea un nuevo producto para tu tienda</p>
                    </div>
                    <div class="flex flex-col sm:flex-row space-y-2 sm:space-y-0 sm:space-x-3">
                        <a href="products.php" class="bg-gray-100 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-200 transition-colors duration-200 flex items-center justify-center">
                            <i class="fas fa-arrow-left mr-2"></i>
                            Volver a Productos
                        </a>
                    </div>
                </div>

                <?php if (!empty($message)): ?>
                <div class="mb-6 p-4 rounded-lg <?php echo $messageType === 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                    <?php echo $messageType === 'success' ? '<i class="fas fa-check-circle mr-2"></i>' : '<i class="fas fa-exclamation-circle mr-2"></i>'; ?>
                    <?php echo $message; ?>
                </div>
                <?php endif; ?>

                <!-- Product Form -->
                <form method="POST" enctype="multipart/form-data" class="space-y-6">
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                        <!-- Main Content -->
                        <div class="lg:col-span-2 space-y-6">
                            <!-- Basic Information -->
                            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                                <h2 class="text-lg font-semibold text-gray-900 mb-4">Información Básica</h2>
                                
                                <div class="space-y-4">
                                    <div>
                                        <label for="name" class="block text-sm font-medium text-gray-700 mb-2">Nombre del Producto *</label>
                                        <input type="text" id="name" name="name" required
                                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                                    </div>
                                    
                                    <div>
                                        <label for="short_description" class="block text-sm font-medium text-gray-700 mb-2">Descripción Corta</label>
                                        <textarea id="short_description" name="short_description" rows="2"
                                                  class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent"></textarea>
                                    </div>
                                    
                                    <div>
                                        <label for="description" class="block text-sm font-medium text-gray-700 mb-2">Descripción Completa</label>
                                        <div id="editor" class="h-64 border border-gray-300 rounded-lg"></div>
                                        <input type="hidden" name="description" id="description">
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Media -->
                            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                                <h2 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                                    <i class="fas fa-images text-primary-500 mr-2"></i>
                                    Imágenes del Producto
                                    <span class="text-sm text-gray-500 ml-2">(Máximo 5 imágenes)</span>
                                </h2>
                                
                                <div class="space-y-6">
                                    <!-- Imagen Principal -->
                                    <div>
                                        <label for="main_image" class="block text-sm font-medium text-gray-700 mb-2">
                                            <i class="fas fa-star text-yellow-500 mr-1"></i>
                                            Imagen Principal *
                                        </label>
                                        <div class="flex items-center space-x-4">
                                            <div class="flex-1">
                                                <input type="file" id="main_image" name="main_image" accept="image/*" required
                                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                                                       onchange="previewMainImage(this)">
                                                <p class="text-xs text-gray-500 mt-1">
                                                    <i class="fas fa-info-circle mr-1"></i>
                                                    Tamaño recomendado: 800x800px. Formato: JPG, PNG, WebP.
                                                </p>
                                            </div>
                                            <div id="main_image_preview" class="hidden">
                                                <img src="" alt="Vista previa" class="w-20 h-20 object-cover rounded-lg border border-gray-200">
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Imágenes Adicionales -->
                                    <div>
                                        <label for="additional_images" class="block text-sm font-medium text-gray-700 mb-2">
                                            <i class="fas fa-images text-blue-500 mr-1"></i>
                                            Imágenes Adicionales
                                            <span class="text-xs text-gray-500">(Máximo 4 adicionales)</span>
                                        </label>
                                        <div class="space-y-4">
                                            <input type="file" id="additional_images" name="additional_images[]" accept="image/*" multiple
                                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                                                   onchange="previewAdditionalImages(this)"
                                                   data-max-files="4">
                                            <p class="text-xs text-gray-500">
                                                <i class="fas fa-lightbulb mr-1 text-yellow-500"></i>
                                                Mantén presionado Ctrl (o Cmd en Mac) para seleccionar múltiples imágenes. Total máximo: 5 imágenes.
                                            </p>
                                            
                                            <!-- Preview Container -->
                                            <div id="additional_images_preview" class="hidden">
                                                <div class="border-2 border-dashed border-gray-200 rounded-lg p-4">
                                                    <div class="text-center text-gray-500 mb-3">
                                                        <i class="fas fa-eye text-2xl mb-2"></i>
                                                        <p class="text-sm font-medium">Vista previa de imágenes</p>
                                                    </div>
                                                    <div id="images_grid" class="grid grid-cols-2 md:grid-cols-4 gap-4"></div>
                                                    <div class="mt-3 text-center">
                                                        <span id="image_count" class="text-sm text-gray-600"></span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Información adicional -->
                                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                                        <div class="flex items-start space-x-3">
                                            <i class="fas fa-info-circle text-blue-500 mt-0.5"></i>
                                            <div class="text-sm text-blue-700">
                                                <p class="font-semibold mb-1">Consejos para mejores imágenes:</p>
                                                <ul class="space-y-1 text-xs">
                                                    <li>• Use fondo blanco o neutro para mejor contraste</li>
                                                    <li>• Capture diferentes ángulos del producto</li>
                                                    <li>• Asegúrese que las imágenes estén bien iluminadas</li>
                                                    <li>• La primera imagen se mostrará como principal en el catálogo</li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Attributes -->
                            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                                <div class="flex items-center justify-between mb-4">
                                    <h2 class="text-lg font-semibold text-gray-900">Atributos</h2>
                                    <button type="button" id="add-attribute" class="text-primary-600 hover:text-primary-900">
                                        <i class="fas fa-plus"></i> Agregar Atributo
                                    </button>
                                </div>
                                
                                <div id="attributes-container" class="space-y-4">
                                    <div class="attribute-row grid grid-cols-1 sm:grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-2">Nombre del Atributo</label>
                                            <input type="text" name="attribute_name[]" placeholder="Ej: Color"
                                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-2">Valor del Atributo</label>
                                            <input type="text" name="attribute_value[]" placeholder="Ej: Rojo"
                                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- SEO -->
                            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                                <h2 class="text-lg font-semibold text-gray-900 mb-4">SEO</h2>
                                
                                <div class="space-y-4">
                                    <div>
                                        <label for="meta_title" class="block text-sm font-medium text-gray-700 mb-2">Meta Título</label>
                                        <input type="text" id="meta_title" name="meta_title"
                                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                                    </div>
                                    
                                    <div>
                                        <label for="meta_description" class="block text-sm font-medium text-gray-700 mb-2">Meta Descripción</label>
                                        <textarea id="meta_description" name="meta_description" rows="3"
                                                  class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent"></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Sidebar -->
                        <div class="space-y-6">
                            <!-- Product Status -->
                            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                                <h2 class="text-lg font-semibold text-gray-900 mb-4">Estado</h2>
                                
                                <div class="space-y-4">
                                    <div class="flex items-center">
                                        <input type="checkbox" id="is_active" name="is_active" value="1" checked
                                               class="rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                                        <label for="is_active" class="ml-2 text-sm text-gray-700">Activo</label>
                                    </div>
                                    
                                    <div class="flex items-center">
                                        <input type="checkbox" id="is_featured" name="is_featured" value="1"
                                               class="rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                                        <label for="is_featured" class="ml-2 text-sm text-gray-700">Destacado</label>
                                    </div>
                                    
                                    <div class="flex items-center">
                                        <input type="checkbox" id="is_new" name="is_new" value="1" checked
                                               class="rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                                        <label for="is_new" class="ml-2 text-sm text-gray-700">Nuevo</label>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Organization -->
                            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                                <h2 class="text-lg font-semibold text-gray-900 mb-4">Organización</h2>
                                
                                <div class="space-y-4">
                                    <div>
                                        <label for="category_id" class="block text-sm font-medium text-gray-700 mb-2">Categoría *</label>
                                        <select id="category_id" name="category_id" required
                                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                                            <option value="">Seleccionar Categoría</option>
                                            <?php foreach ($categories as $cat): ?>
                                                <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    
                                    <div>
                                        <label for="brand_id" class="block text-sm font-medium text-gray-700 mb-2">Marca</label>
                                        <select id="brand_id" name="brand_id"
                                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                                            <option value="">Seleccionar Marca</option>
                                            <?php foreach ($brands as $b): ?>
                                                <option value="<?php echo $b['id']; ?>"><?php echo htmlspecialchars($b['name']); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Pricing -->
                            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                                <h2 class="text-lg font-semibold text-gray-900 mb-4">Precios</h2>
                                
                                <div class="space-y-4">
                                    <div>
                                        <label for="price" class="block text-sm font-medium text-gray-700 mb-2">Precio Regular *</label>
                                        <div class="relative">
                                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                                <span class="text-gray-500">$</span>
                                            </div>
                                            <input type="number" id="price" name="price" min="0" step="0.01" required
                                                   class="w-full pl-8 pr-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                                        </div>
                                    </div>
                                    
                                    <div>
                                        <label for="sale_price" class="block text-sm font-medium text-gray-700 mb-2">Precio de Oferta</label>
                                        <div class="relative">
                                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                                <span class="text-gray-500">$</span>
                                            </div>
                                            <input type="number" id="sale_price" name="sale_price" min="0" step="0.01"
                                                   class="w-full pl-8 pr-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Inventory -->
                            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                                <h2 class="text-lg font-semibold text-gray-900 mb-4">Inventario</h2>
                                
                                <div class="space-y-4">
                                    <div>
                                        <label for="sku" class="block text-sm font-medium text-gray-700 mb-2">SKU</label>
                                        <input type="text" id="sku" name="sku"
                                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                                    </div>
                                    
                                    <div>
                                        <label for="stock" class="block text-sm font-medium text-gray-700 mb-2">Stock *</label>
                                        <input type="number" id="stock" name="stock" min="0" required
                                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Actions -->
                            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                                <div class="flex flex-col space-y-3">
                                    <button type="submit" class="w-full bg-gradient-to-r from-primary-500 to-primary-600 text-white px-6 py-2 rounded-lg hover:from-primary-600 hover:to-primary-700 transition-all duration-200">
                                        <i class="fas fa-save mr-2"></i>
                                        Guardar Producto
                                    </button>
                                    
                                    <a href="products.php" class="w-full bg-gray-100 text-gray-700 px-6 py-2 rounded-lg hover:bg-gray-200 transition-colors duration-200 text-center">
                                        <i class="fas fa-times mr-2"></i>
                                        Cancelar
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </main>
        </div>
    </div>

    <script src="../admin/assets/js/admin.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize Quill editor
            var quill = new Quill('#editor', {
                theme: 'snow',
                modules: {
                    toolbar: [
                        [{ 'header': [1, 2, 3, 4, 5, 6, false] }],
                        ['bold', 'italic', 'underline', 'strike'],
                        [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                        [{ 'color': [] }, { 'background': [] }],
                        ['link', 'image'],
                        ['clean']
                    ]
                }
            });
            
            // Update hidden input with Quill content before form submission
            document.querySelector('form').addEventListener('submit', function() {
                document.getElementById('description').value = quill.root.innerHTML;
            });
            
            // Add attribute button
            document.getElementById('add-attribute').addEventListener('click', function() {
                const container = document.getElementById('attributes-container');
                const newRow = document.createElement('div');
                newRow.className = 'attribute-row grid grid-cols-1 sm:grid-cols-2 gap-4';
                newRow.innerHTML = `
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Nombre del Atributo</label>
                        <input type="text" name="attribute_name[]" placeholder="Ej: Color"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                    </div>
                    <div class="relative">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Valor del Atributo</label>
                        <div class="flex items-center">
                            <input type="text" name="attribute_value[]" placeholder="Ej: Rojo"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                            <button type="button" class="remove-attribute ml-2 text-red-500 hover:text-red-700">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>
                `;
                container.appendChild(newRow);
                
                // Add event listener to the new remove button
                newRow.querySelector('.remove-attribute').addEventListener('click', function() {
                    container.removeChild(newRow);
                });
            });
            
            // Auto-generate SKU from product name
            document.getElementById('name').addEventListener('blur', function() {
                const skuInput = document.getElementById('sku');
                if (skuInput.value === '') {
                    // Generate SKU from product name (first 3 letters + random number)
                    const name = this.value.trim();
                    if (name) {
                        const prefix = name.substring(0, 3).toUpperCase();
                        const randomNum = Math.floor(Math.random() * 10000).toString().padStart(4, '0');
                        skuInput.value = `${prefix}-${randomNum}`;
                    }
                }
            });
            
            // Auto-fill meta title and description if empty
            document.getElementById('name').addEventListener('blur', function() {
                const metaTitleInput = document.getElementById('meta_title');
                if (metaTitleInput.value === '') {
                    metaTitleInput.value = this.value;
                }
            });
            
            document.getElementById('short_description').addEventListener('blur', function() {
                const metaDescInput = document.getElementById('meta_description');
                if (metaDescInput.value === '') {
                    metaDescInput.value = this.value;
                }
            });
        });
        
        // ============================================
        // FUNCIONES PARA VISTA PREVIA DE IMÁGENES
        // ============================================
        
        /**
         * Vista previa de la imagen principal
         */
        function previewMainImage(input) {
            const previewContainer = document.getElementById('main_image_preview');
            const previewImg = previewContainer.querySelector('img');
            
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    previewImg.src = e.target.result;
                    previewContainer.classList.remove('hidden');
                };
                
                reader.readAsDataURL(input.files[0]);
            } else {
                previewContainer.classList.add('hidden');
            }
        }
        
        /**
         * Vista previa de imágenes adicionales
         */
        function previewAdditionalImages(input) {
            const previewContainer = document.getElementById('additional_images_preview');
            const imagesGrid = document.getElementById('images_grid');
            const imageCount = document.getElementById('image_count');
            const maxFiles = parseInt(input.dataset.maxFiles) || 4;
            
            // Limpiar grid anterior
            imagesGrid.innerHTML = '';
            
            if (input.files && input.files.length > 0) {
                // Verificar límite de archivos
                let filesToProcess = Array.from(input.files);
                if (filesToProcess.length > maxFiles) {
                    alert(`Máximo ${maxFiles} imágenes adicionales permitidas. Se procesarán las primeras ${maxFiles}.`);
                    filesToProcess = filesToProcess.slice(0, maxFiles);
                }
                
                // Mostrar container de preview
                previewContainer.classList.remove('hidden');
                
                // Procesar cada archivo
                filesToProcess.forEach((file, index) => {
                    const reader = new FileReader();
                    
                    reader.onload = function(e) {
                        const imageDiv = document.createElement('div');
                        imageDiv.className = 'relative group';
                        imageDiv.innerHTML = `
                            <img src="${e.target.result}" 
                                 alt="Preview ${index + 1}" 
                                 class="w-full h-24 object-cover rounded-lg border border-gray-200 shadow-sm">
                            <div class="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-30 transition-all duration-200 rounded-lg flex items-center justify-center">
                                <div class="opacity-0 group-hover:opacity-100 text-white text-xs text-center">
                                    <i class="fas fa-eye block mb-1"></i>
                                    Imagen ${index + 1}
                                </div>
                            </div>
                        `;
                        imagesGrid.appendChild(imageDiv);
                    };
                    
                    reader.readAsDataURL(file);
                });
                
                // Actualizar contador
                const totalImages = Math.min(filesToProcess.length, maxFiles);
                imageCount.textContent = `${totalImages} imagen${totalImages !== 1 ? 'es' : ''} seleccionada${totalImages !== 1 ? 's' : ''}`;
                
                // Mostrar advertencia si se excedió el límite
                if (input.files.length > maxFiles) {
                    setTimeout(() => {
                        imageCount.innerHTML += ' <span class="text-orange-600 font-semibold">(Máximo alcanzado)</span>';
                    }, 100);
                }
                
            } else {
                // No hay archivos, ocultar preview
                previewContainer.classList.add('hidden');
            }
        }
        
        /**
         * Validación antes del envío del formulario
         */
        document.querySelector('form').addEventListener('submit', function(e) {
            const mainImage = document.getElementById('main_image').files;
            const additionalImages = document.getElementById('additional_images').files;
            const totalImages = mainImage.length + additionalImages.length;
            
            if (totalImages > 5) {
                e.preventDefault();
                alert('Máximo 5 imágenes permitidas en total (1 principal + 4 adicionales).');
                return false;
            }
            
            if (mainImage.length === 0) {
                e.preventDefault();
                alert('La imagen principal es obligatoria.');
                return false;
            }
            
            // Validar tamaño de archivos (máximo 5MB por imagen)
            const maxSize = 5 * 1024 * 1024; // 5MB
            let invalidFiles = [];
            
            Array.from(mainImage).concat(Array.from(additionalImages)).forEach((file, index) => {
                if (file.size > maxSize) {
                    invalidFiles.push(`Imagen ${index + 1}: ${file.name}`);
                }
            });
            
            if (invalidFiles.length > 0) {
                e.preventDefault();
                alert('Las siguientes imágenes exceden el límite de 5MB:\n\n' + invalidFiles.join('\n'));
                return false;
            }
            
            return true;
        });
    </script>
</body>
</html>
