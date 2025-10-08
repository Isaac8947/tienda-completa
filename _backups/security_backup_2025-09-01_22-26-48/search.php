<?php
session_start();
require_once 'config/database.php';
require_once 'config/global-settings.php';
require_once 'models/Product.php';
require_once 'models/Category.php';
require_once 'models/Brand.php';

$query = $_GET['q'] ?? '';
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 12;
$offset = ($page - 1) * $perPage;

// Obtener filtros
$filters = [
    'brand' => $_GET['marca'] ?? '',
    'category' => $_GET['categoria'] ?? '',
    'min_price' => $_GET['precio_min'] ?? '',
    'max_price' => $_GET['precio_max'] ?? '',
    'sort' => $_GET['orden'] ?? 'newest'
];

$products = [];
$totalProducts = 0;
$totalPages = 0;

try {
    $productModel = new Product();
    $brandModel = new Brand();
    $categoryModel = new Category();
    
    if (!empty($query)) {
        // Buscar productos
        $products = $productModel->searchProducts($query, $filters, $perPage, $offset);
        
        // Contar total de resultados (simplificado)
        $allResults = $productModel->searchProducts($query, $filters, 1000, 0);
        $totalProducts = count($allResults);
        $totalPages = ceil($totalProducts / $perPage);
    }
    
    // Obtener marcas y categorías para filtros
    $brands = $brandModel->getAll(['status' => 'active']);
    $categories = $categoryModel->getAll(['is_active' => 1]);
    
} catch (Exception $e) {
    error_log("Error loading search page: " . $e->getMessage());
    $products = [];
    $brands = [];
    $categories = [];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buscar: <?php echo htmlspecialchars($query); ?> - Odisea Makeup Store</title>
    <meta name="description" content="Resultados de búsqueda para <?php echo htmlspecialchars($query); ?>">
    
    <!-- Tailwind CSS -->
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
                            50: '#f0f9ff',
                            100: '#e0f2fe',
                            200: '#bae6fd',
                            300: '#7dd3fc',
                            400: '#38bdf8',
                            500: '#0ea5e9',
                            600: '#0284c7',
                            700: '#0369a1',
                            800: '#075985',
                            900: '#0c4a6e'
                        }
                    }
                }
            }
        }
    </script>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- AOS Animation -->
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
</head>
<body class="bg-white">
    <!-- Header Global -->
    <?php include 'includes/global-header.php'; ?>
    
    <!-- Navigation -->
    <?php include 'includes/header.php'; ?>
    
    <!-- Search Header -->
    <section class="py-12 bg-gradient-to-r from-primary-50 to-secondary-50">
        <div class="container mx-auto px-4">
            <div class="text-center">
                <h1 class="text-3xl lg:text-5xl font-bold text-gray-900 mb-4">
                    Resultados de búsqueda
                </h1>
                <?php if (!empty($query)): ?>
                <p class="text-xl text-gray-600 mb-4">
                    Mostrando resultados para: <span class="font-semibold text-primary-600">"<?php echo htmlspecialchars($query); ?>"</span>
                </p>
                <?php endif; ?>
                <div class="mt-6">
                    <span class="text-lg text-primary-600 font-semibold">
                        <?php echo $totalProducts; ?> productos encontrados
                    </span>
                </div>
                
                <!-- Search Form -->
                <div class="mt-8 max-w-2xl mx-auto">
                    <form method="GET" action="search.php" class="relative">
                        <input type="text" 
                               name="q"
                               value="<?php echo htmlspecialchars($query); ?>"
                               placeholder="Buscar productos, marcas..." 
                               class="w-full px-6 py-4 pr-16 border border-gray-300 rounded-full focus:outline-none focus:ring-2 focus:ring-primary-500 text-lg">
                        <button type="submit" class="absolute right-2 top-1/2 transform -translate-y-1/2 bg-primary-500 text-white px-6 py-2 rounded-full hover:bg-primary-600 transition-colors">
                            <i class="fas fa-search"></i>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Filters and Results -->
    <section class="py-12">
        <div class="container mx-auto px-4">
            <?php if (!empty($query)): ?>
            <div class="flex flex-col lg:flex-row gap-8">
                <!-- Sidebar Filters -->
                <div class="lg:w-1/4">
                    <div class="bg-white rounded-lg shadow-lg p-6 sticky top-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-6">Filtros</h3>
                        
                        <form method="GET" action="search.php" id="filters-form">
                            <input type="hidden" name="q" value="<?php echo htmlspecialchars($query); ?>">
                            
                            <!-- Category Filter -->
                            <div class="mb-6">
                                <h4 class="font-medium text-gray-900 mb-3">Categoría</h4>
                                <select name="categoria" class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500">
                                    <option value="">Todas las categorías</option>
                                    <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo $category['id']; ?>" <?php echo $filters['category'] == $category['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($category['name']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <!-- Brand Filter -->
                            <div class="mb-6">
                                <h4 class="font-medium text-gray-900 mb-3">Marca</h4>
                                <select name="marca" class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500">
                                    <option value="">Todas las marcas</option>
                                    <?php foreach ($brands as $brand): ?>
                                    <option value="<?php echo $brand['id']; ?>" <?php echo $filters['brand'] == $brand['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($brand['name']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <!-- Price Filter -->
                            <div class="mb-6">
                                <h4 class="font-medium text-gray-900 mb-3">Precio</h4>
                                <div class="grid grid-cols-2 gap-3">
                                    <input type="number" name="precio_min" placeholder="Mín" 
                                           value="<?php echo htmlspecialchars($filters['min_price']); ?>"
                                           class="p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500">
                                    <input type="number" name="precio_max" placeholder="Máx" 
                                           value="<?php echo htmlspecialchars($filters['max_price']); ?>"
                                           class="p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500">
                                </div>
                            </div>
                            
                            <!-- Sort Filter -->
                            <div class="mb-6">
                                <h4 class="font-medium text-gray-900 mb-3">Ordenar por</h4>
                                <select name="orden" class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500">
                                    <option value="newest" <?php echo $filters['sort'] == 'newest' ? 'selected' : ''; ?>>Más nuevos</option>
                                    <option value="price_low" <?php echo $filters['sort'] == 'price_low' ? 'selected' : ''; ?>>Precio: Menor a mayor</option>
                                    <option value="price_high" <?php echo $filters['sort'] == 'price_high' ? 'selected' : ''; ?>>Precio: Mayor a menor</option>
                                    <option value="name" <?php echo $filters['sort'] == 'name' ? 'selected' : ''; ?>>Nombre A-Z</option>
                                </select>
                            </div>
                            
                            <button type="submit" class="w-full bg-primary-500 text-white py-3 rounded-lg font-semibold hover:bg-primary-600 transition-colors">
                                Aplicar Filtros
                            </button>
                            
                            <a href="search.php?q=<?php echo urlencode($query); ?>" 
                               class="w-full border border-gray-300 text-gray-700 py-3 rounded-lg font-semibold hover:bg-gray-50 transition-colors block text-center mt-3">
                                Limpiar Filtros
                            </a>
                        </form>
                    </div>
                </div>
                
                <!-- Products Grid -->
                <div class="lg:w-3/4">
                    <?php if (!empty($products)): ?>
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-8">
                            <?php foreach ($products as $product): 
                                // Obtener información de la marca
                                $productBrand = !empty($product['brand_id']) ? $brandModel->getById($product['brand_id']) : null;
                                $brandName = $productBrand ? $productBrand['name'] : 'Sin marca';
                                
                                // Calcular descuento si hay precio de oferta
                                $discount = 0;
                                if (!empty($product['compare_price']) && $product['compare_price'] > $product['price']) {
                                    $discount = round((($product['compare_price'] - $product['price']) / $product['compare_price']) * 100);
                                }
                                
                                // Imagen del producto
                                if (!empty($product['main_image'])) {
                                    if (strpos($product['main_image'], 'uploads/products/') === 0) {
                                        // La ruta ya incluye uploads/products/
                                        $productImage = $product['main_image'];
                                    } else {
                                        // Solo el nombre del archivo
                                        $productImage = 'uploads/products/' . $product['main_image'];
                                    }
                                } else {
                                    $productImage = 'assets/images/placeholder-product.svg';
                                }
                            ?>
                            <div class="bg-white rounded-2xl shadow-lg overflow-hidden group hover:shadow-xl transition-all duration-300" data-aos="fade-up">
                                <div class="relative overflow-hidden">
                                    <img src="<?php echo $productImage; ?>" 
                                         alt="<?php echo htmlspecialchars($product['name']); ?>" 
                                         class="w-full h-64 object-cover group-hover:scale-110 transition-transform duration-500"
                                         onerror="this.src='assets/images/placeholder-product.svg'">
                                    
                                    <!-- Product Badges -->
                                    <?php if ($discount > 0): ?>
                                    <div class="absolute top-4 left-4">
                                        <span class="bg-red-500 text-white px-3 py-1 rounded-full text-xs font-semibold">-<?php echo $discount; ?>%</span>
                                    </div>
                                    <?php elseif (!empty($product['is_new']) && $product['is_new']): ?>
                                    <div class="absolute top-4 left-4">
                                        <span class="bg-green-500 text-white px-3 py-1 rounded-full text-xs font-semibold">NUEVO</span>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <!-- Quick Actions -->
                                    <div class="absolute top-4 right-4 space-y-2 opacity-0 group-hover:opacity-100 transition-opacity duration-300">
                                        <button class="w-10 h-10 bg-white rounded-full flex items-center justify-center shadow-lg hover:bg-primary-500 hover:text-white transition-colors duration-300"
                                                onclick="toggleWishlist(<?php echo $product['id']; ?>)">
                                            <i class="fas fa-heart text-sm"></i>
                                        </button>
                                        <button class="w-10 h-10 bg-white rounded-full flex items-center justify-center shadow-lg hover:bg-primary-500 hover:text-white transition-colors duration-300"
                                                onclick="quickView(<?php echo $product['id']; ?>)">
                                            <i class="fas fa-eye text-sm"></i>
                                        </button>
                                    </div>

                                    <!-- Add to Cart Overlay -->
                                    <div class="absolute bottom-0 left-0 right-0 bg-gradient-to-t from-black/50 to-transparent p-4 transform translate-y-full group-hover:translate-y-0 transition-transform duration-300">
                                        <button class="w-full bg-white text-gray-900 py-2 rounded-full font-semibold hover:bg-primary-500 hover:text-white transition-colors duration-300"
                                                onclick="addToCart(<?php echo $product['id']; ?>)">
                                            Agregar al Carrito
                                        </button>
                                    </div>
                                </div>
                                
                                <div class="p-6">
                                    <div class="mb-2">
                                        <span class="text-sm text-gray-500"><?php echo htmlspecialchars($brandName); ?></span>
                                    </div>
                                    <h3 class="font-semibold text-gray-900 mb-2 line-clamp-2">
                                        <?php echo htmlspecialchars($product['name']); ?>
                                    </h3>
                                    
                                    <!-- Rating -->
                                    <div class="flex items-center mb-3">
                                        <div class="flex text-yellow-400">
                                            <?php for ($i = 0; $i < 5; $i++): ?>
                                            <i class="fas fa-star text-sm"></i>
                                            <?php endfor; ?>
                                        </div>
                                        <span class="text-sm text-gray-500 ml-2">(0)</span>
                                    </div>
                                    
                                    <!-- Price -->
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center space-x-2">
                                            <span class="text-xl font-bold text-primary-500">$<?php echo number_format($product['price'], 0, ',', '.'); ?></span>
                                            <?php if (!empty($product['compare_price']) && $product['compare_price'] > $product['price']): ?>
                                            <span class="text-sm text-gray-400 line-through">$<?php echo number_format($product['compare_price'], 0, ',', '.'); ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    
                                    <!-- Stock Status -->
                                    <div class="mt-3">
                                        <?php if ($product['inventory_quantity'] > 0): ?>
                                            <span class="text-sm text-green-600">✓ En stock (<?php echo $product['inventory_quantity']; ?> disponibles)</span>
                                        <?php else: ?>
                                            <span class="text-sm text-red-600">✗ Agotado</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <!-- Pagination -->
                        <?php if ($totalPages > 1): ?>
                        <div class="mt-12 flex justify-center">
                            <nav class="flex items-center space-x-2">
                                <?php if ($page > 1): ?>
                                <a href="?q=<?php echo urlencode($query); ?>&page=<?php echo $page-1; ?>&<?php echo http_build_query($filters); ?>" 
                                   class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">
                                    Anterior
                                </a>
                                <?php endif; ?>
                                
                                <?php for ($i = max(1, $page-2); $i <= min($totalPages, $page+2); $i++): ?>
                                <a href="?q=<?php echo urlencode($query); ?>&page=<?php echo $i; ?>&<?php echo http_build_query($filters); ?>" 
                                   class="px-4 py-2 border rounded-lg <?php echo $i == $page ? 'bg-primary-500 text-white border-primary-500' : 'border-gray-300 text-gray-700 hover:bg-gray-50'; ?>">
                                    <?php echo $i; ?>
                                </a>
                                <?php endfor; ?>
                                
                                <?php if ($page < $totalPages): ?>
                                <a href="?q=<?php echo urlencode($query); ?>&page=<?php echo $page+1; ?>&<?php echo http_build_query($filters); ?>" 
                                   class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">
                                    Siguiente
                                </a>
                                <?php endif; ?>
                            </nav>
                        </div>
                        <?php endif; ?>
                        
                    <?php elseif (!empty($query)): ?>
                        <!-- No Results Found -->
                        <div class="text-center py-16">
                            <div class="text-gray-400 text-6xl mb-4">
                                <i class="fas fa-search"></i>
                            </div>
                            <h3 class="text-xl font-semibold text-gray-600 mb-2">No se encontraron resultados</h3>
                            <p class="text-gray-500 mb-6">Intenta buscar con otros términos o explora nuestras categorías.</p>
                            <a href="index.php" class="bg-primary-500 text-white px-6 py-3 rounded-lg font-semibold hover:bg-primary-600 transition-colors">
                                Volver al inicio
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php else: ?>
            <!-- Empty Search -->
            <div class="text-center py-16">
                <div class="text-gray-400 text-6xl mb-4">
                    <i class="fas fa-search"></i>
                </div>
                <h3 class="text-xl font-semibold text-gray-600 mb-2">Realiza una búsqueda</h3>
                <p class="text-gray-500">Ingresa un término en el campo de búsqueda para encontrar productos.</p>
            </div>
            <?php endif; ?>
        </div>
    </section>
    
    <!-- Footer -->
    <?php include 'includes/global-footer.php'; ?>
    <?php include 'includes/footer.php'; ?>
    
    <!-- JavaScript -->
    <script>
        AOS.init({
            duration: 800,
            once: true
        });
        
        // Add to cart function
        function addToCart(productId, quantity = 1) {
            fetch('cart-add.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `product_id=${productId}&quantity=${quantity}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification('Producto agregado al carrito', 'success');
                } else {
                    showNotification(data.message || 'Error al agregar producto', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('Error al agregar producto', 'error');
            });
        }
        
        // Toggle wishlist
        function toggleWishlist(productId) {
            showNotification('Funcionalidad de lista de deseos próximamente', 'info');
        }
        
        // Quick view
        function quickView(productId) {
            window.location.href = `product.php?id=${productId}`;
        }
        
        // Show notification
        function showNotification(message, type = 'info') {
            const notification = document.createElement('div');
            notification.className = `fixed top-4 right-4 z-50 p-4 rounded-lg shadow-lg text-white max-w-sm transition-all duration-300 ${
                type === 'success' ? 'bg-green-500' : 
                type === 'error' ? 'bg-red-500' : 
                type === 'warning' ? 'bg-yellow-500' : 'bg-blue-500'
            }`;
            notification.innerHTML = `
                <div class="flex items-center">
                    <span class="mr-2">
                        ${type === 'success' ? '✓' : type === 'error' ? '✗' : type === 'warning' ? '⚠' : 'ℹ'}
                    </span>
                    <span>${message}</span>
                    <button onclick="this.parentElement.parentElement.remove()" class="ml-auto text-white hover:text-gray-200">×</button>
                </div>
            `;
            document.body.appendChild(notification);
            
            setTimeout(() => {
                if (notification.parentElement) {
                    notification.remove();
                }
            }, 3000);
        }
        
        // Auto-submit filters form when changed
        document.querySelectorAll('#filters-form select, #filters-form input').forEach(element => {
            element.addEventListener('change', function() {
                document.getElementById('filters-form').submit();
            });
        });
    </script>
</body>
</html>
