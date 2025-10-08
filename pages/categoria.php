<?php
session_start();
require_once 'config/database.php';
require_once 'config/global-settings.php';
require_once 'models/Product.php';
require_once 'models/Category.php';
require_once 'models/Brand.php';
require_once 'includes/CSRFProtection.php';

$categorySlug = $_GET['categoria'] ?? '';
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 12;
$offset = ($page - 1) * $perPage;

// Obtener filtros
$filters = [
    'brand_id' => $_GET['marca'] ?? '',
    'brand' => $_GET['marca'] ?? '', // Para compatibilidad con countByCategory
    'min_price' => $_GET['precio_min'] ?? '',
    'max_price' => $_GET['precio_max'] ?? '',
    'sort' => $_GET['orden'] ?? 'newest'
];

try {
    $categoryModel = new Category();
    $productModel = new Product();
    $brandModel = new Brand();
    
    // Obtener información de la categoría
    $category = $categoryModel->findBySlug($categorySlug);
    if (!$category) {
        header('HTTP/1.0 404 Not Found');
        include '404.php';
        exit;
    }
    
    // Obtener productos de la categoría
    $products = $productModel->getProductsByCategory($category['id'], $perPage, $offset, $filters);
    
    // Obtener total de productos para paginación
    $totalProducts = $productModel->countByCategory($category['id'], $filters);
    $totalPages = ceil($totalProducts / $perPage);
    
    // Obtener marcas para filtros
    $brands = $brandModel->getActive();
    
} catch (Exception $e) {
    error_log("Error loading category page: " . $e->getMessage());
    $category = ['name' => 'Categoría no encontrada'];
    $products = [];
    $brands = [];
    $totalProducts = 0;
    $totalPages = 0;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($category['name']); ?> - OdiseaStore</title>
    <meta name="description" content="<?php echo htmlspecialchars($category['description'] ?? 'Productos de ' . $category['name']); ?>">
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=Playfair+Display:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: {
                            50: '#fdf8f6',
                            100: '#f2e8e5',
                            200: '#eaddd7',
                            300: '#e0cec7',
                            400: '#d2bab0',
                            500: '#b08d80',
                            600: '#a67c76',
                            700: '#8d635d',
                            800: '#745044',
                            900: '#5b3d2b'
                        },
                        secondary: {
                            50: '#fefdfb',
                            100: '#fdf6f0',
                            200: '#f9e6d3',
                            300: '#f4d3b0',
                            400: '#eab676',
                            500: '#c4a575',
                            600: '#b39256',
                            700: '#9e7d3a',
                            800: '#896820',
                            900: '#745407'
                        },
                        accent: {
                            50: '#faf9f7',
                            100: '#f1efed',
                            200: '#e8e3df',
                            300: '#d4ccc4',
                            400: '#b8a99c',
                            500: '#a67c76',
                            600: '#8d635d',
                            700: '#745044',
                            800: '#5b3d2b',
                            900: '#422a12'
                        },
                        luxury: {
                            rose: '#f4e6e1',
                            gold: '#f7f1e8',
                            pearl: '#fefdfb',
                            bronze: '#d2bab0',
                            champagne: '#f9e6d3'
                        }
                    },
                    fontFamily: {
                        'sans': ['Inter', 'sans-serif'],
                        'serif': ['Playfair Display', 'serif']
                    },
                    animation: {
                        'float': 'float 6s ease-in-out infinite',
                        'glow': 'glow 2s ease-in-out infinite alternate',
                        'shimmer': 'shimmer 2.5s linear infinite',
                        'fade-in-up': 'fadeInUp 0.8s ease-out',
                        'scale-in': 'scaleIn 0.5s ease-out',
                        'slide-in-right': 'slideInRight 0.6s ease-out',
                        'slide-in-left': 'slideInLeft 0.6s ease-out'
                    },
                    keyframes: {
                        float: {
                            '0%, 100%': { transform: 'translateY(0px)' },
                            '50%': { transform: 'translateY(-20px)' }
                        },
                        glow: {
                            '0%': { boxShadow: '0 0 20px rgba(176, 141, 128, 0.3)' },
                            '100%': { boxShadow: '0 0 30px rgba(196, 165, 117, 0.5)' }
                        },
                        shimmer: {
                            '0%': { transform: 'translateX(-100%)' },
                            '100%': { transform: 'translateX(100%)' }
                        },
                        fadeInUp: {
                            '0%': { opacity: '0', transform: 'translateY(30px)' },
                            '100%': { opacity: '1', transform: 'translateY(0)' }
                        },
                        scaleIn: {
                            '0%': { opacity: '0', transform: 'scale(0.9)' },
                            '100%': { opacity: '1', transform: 'scale(1)' }
                        },
                        slideInRight: {
                            '0%': { opacity: '0', transform: 'translateX(30px)' },
                            '100%': { opacity: '1', transform: 'translateX(0)' }
                        },
                        slideInLeft: {
                            '0%': { opacity: '0', transform: 'translateX(-30px)' },
                            '100%': { opacity: '1', transform: 'translateX(0)' }
                        }
                    }
                }
            }
        }
    </script>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- AOS Animation -->
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    
    <!-- Custom Styles -->
    <style>
        .filter-card {
            background: linear-gradient(135deg, #fefdfb 0%, #f9e6d3 100%);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(212, 204, 196, 0.2);
        }
        
        .product-card {
            background: linear-gradient(135deg, #fefdfb 0%, #f4e6e1 100%);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .product-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 40px rgba(116, 80, 68, 0.15);
        }
        
        .gradient-overlay {
            background: linear-gradient(135deg, #f4e6e1 0%, #f9e6d3 50%, #f7f1e8 100%);
        }
        
        .shimmer-effect {
            position: relative;
            overflow: hidden;
        }
        
        .shimmer-effect::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.4), transparent);
            animation: shimmer 2.5s linear infinite;
        }
        
        .mobile-filter-overlay {
            background: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(4px);
        }
        
        @media (max-width: 768px) {
            .mobile-optimized {
                padding: 1rem;
            }
            
            .product-grid-mobile {
                grid-template-columns: repeat(2, 1fr);
                gap: 1rem;
            }
            
            .product-card {
                min-height: auto;
            }
        }
        
        @media (max-width: 480px) {
            .product-grid-mobile {
                gap: 0.75rem;
            }
        }
    </style>
</head>
<body class="bg-gradient-to-br from-luxury-pearl via-luxury-rose to-luxury-champagne font-sans">
    <!-- Mobile Filter Toggle -->
    <div class="lg:hidden fixed bottom-6 left-6 z-40">
        <button id="mobile-filter-btn" 
                class="w-14 h-14 bg-gradient-to-r from-primary-500 to-secondary-500 text-white rounded-full shadow-lg hover:shadow-xl transition-all duration-300 flex items-center justify-center">
            <i class="fas fa-filter text-lg"></i>
        </button>
    </div>
    
    <!-- Mobile Filter Overlay -->
    <div id="mobile-filter-overlay" class="lg:hidden fixed inset-0 z-50 mobile-filter-overlay hidden">
        <div class="absolute inset-x-4 top-20 bottom-20 bg-white rounded-2xl shadow-2xl overflow-auto">
            <div class="p-6">
                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-xl font-serif font-bold text-gray-900">Filtros</h3>
                    <button id="close-mobile-filter" class="w-8 h-8 flex items-center justify-center rounded-full bg-gray-100 hover:bg-gray-200 transition-colors">
                        <i class="fas fa-times text-gray-600"></i>
                    </button>
                </div>
                <div id="mobile-filter-content">
                    <!-- Filter content will be cloned here -->
                </div>
            </div>
        </div>
    </div>
    
    <!-- Header Navigation -->
    <?php include 'includes/header.php'; ?>
    
    <!-- Breadcrumb -->
    <section class="py-4 lg:py-6 bg-gradient-to-r from-luxury-pearl to-luxury-gold">
        <div class="container mx-auto px-4">
            <nav class="text-sm" data-aos="fade-down">
                <ol class="flex items-center space-x-2">
                    <li><a href="index.php" class="text-primary-600 hover:text-primary-800 transition-colors">
                        <i class="fas fa-home mr-1"></i>Inicio</a></li>
                    <li class="text-primary-400">/</li>
                    <li class="text-primary-900 font-medium"><?php echo htmlspecialchars($category['name']); ?></li>
                </ol>
            </nav>
        </div>
    </section>
    
    <!-- Category Header -->
    <section class="py-12 lg:py-20 gradient-overlay relative overflow-hidden">
        <!-- Decorative Elements -->
        <div class="absolute inset-0 overflow-hidden">
            <div class="absolute -top-10 -right-10 w-40 h-40 bg-primary-200/30 rounded-full animate-float"></div>
            <div class="absolute -bottom-10 -left-10 w-32 h-32 bg-secondary-200/30 rounded-full animate-float" style="animation-delay: -3s;"></div>
        </div>
        
        <div class="container mx-auto px-4 relative z-10">
            <div class="text-center" data-aos="fade-up">
                <h1 class="text-3xl lg:text-6xl font-serif font-bold bg-gradient-to-r from-primary-800 to-secondary-700 bg-clip-text text-transparent mb-6">
                    <?php echo htmlspecialchars($category['name']); ?>
                </h1>
                <?php if (!empty($category['description'])): ?>
                <p class="text-lg lg:text-xl text-primary-700 max-w-3xl mx-auto mb-8 leading-relaxed">
                    <?php echo htmlspecialchars($category['description']); ?>
                </p>
                <?php endif; ?>
                <div class="inline-flex items-center px-6 py-3 bg-white/80 backdrop-blur-sm rounded-full shadow-lg">
                    <i class="fas fa-boxes text-primary-600 mr-2"></i>
                    <span class="text-lg text-primary-800 font-semibold">
                        <?php echo $totalProducts; ?> productos encontrados
                    </span>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Filters and Products -->
    <section class="py-12 lg:py-16">
        <div class="container mx-auto px-6 lg:px-4">
            <div class="flex flex-col lg:flex-row gap-8">
                <!-- Desktop Sidebar Filters -->
                <div class="hidden lg:block lg:w-1/4">
                    <div class="filter-card rounded-2xl shadow-xl p-6 sticky top-6" data-aos="fade-right">
                        <h3 class="text-xl font-serif font-bold text-primary-900 mb-6 flex items-center">
                            <i class="fas fa-sliders-h text-primary-600 mr-3"></i>
                            Filtros
                        </h3>
                        
                        <form method="GET" action="" id="filters-form" class="space-y-6">
                            <input type="hidden" name="categoria" value="<?php echo htmlspecialchars($categorySlug); ?>">
                            
                            <!-- Brand Filter -->
                            <div class="filter-group">
                                <h4 class="font-semibold text-primary-800 mb-3 flex items-center">
                                    <i class="fas fa-tag text-secondary-500 mr-2"></i>
                                    Marca
                                </h4>
                                <select name="marca" class="w-full p-4 border-2 border-primary-200 rounded-xl bg-white/80 backdrop-blur-sm focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-all duration-300">
                                    <option value="">Todas las marcas</option>
                                    <?php foreach ($brands as $brand): ?>
                                    <option value="<?php echo $brand['id']; ?>" <?php echo ($_GET['marca'] ?? '') == $brand['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($brand['name']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <!-- Price Filter -->
                            <div class="filter-group">
                                <h4 class="font-semibold text-primary-800 mb-3 flex items-center">
                                    <i class="fas fa-dollar-sign text-secondary-500 mr-2"></i>
                                    Precio
                                </h4>
                                <div class="grid grid-cols-2 gap-3">
                                    <input type="number" name="precio_min" placeholder="Mínimo" 
                                           value="<?php echo htmlspecialchars($filters['min_price']); ?>"
                                           class="p-3 border-2 border-primary-200 rounded-xl bg-white/80 backdrop-blur-sm focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-all duration-300">
                                    <input type="number" name="precio_max" placeholder="Máximo" 
                                           value="<?php echo htmlspecialchars($filters['max_price']); ?>"
                                           class="p-3 border-2 border-primary-200 rounded-xl bg-white/80 backdrop-blur-sm focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-all duration-300">
                                </div>
                            </div>
                            
                            <!-- Sort Filter -->
                            <div class="filter-group">
                                <h4 class="font-semibold text-primary-800 mb-3 flex items-center">
                                    <i class="fas fa-sort text-secondary-500 mr-2"></i>
                                    Ordenar por
                                </h4>
                                <select name="orden" class="w-full p-4 border-2 border-primary-200 rounded-xl bg-white/80 backdrop-blur-sm focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-all duration-300">
                                    <option value="newest" <?php echo $filters['sort'] == 'newest' ? 'selected' : ''; ?>>Más nuevos</option>
                                    <option value="price_low" <?php echo $filters['sort'] == 'price_low' ? 'selected' : ''; ?>>Precio: Menor a mayor</option>
                                    <option value="price_high" <?php echo $filters['sort'] == 'price_high' ? 'selected' : ''; ?>>Precio: Mayor a menor</option>
                                    <option value="name" <?php echo $filters['sort'] == 'name' ? 'selected' : ''; ?>>Nombre A-Z</option>
                                </select>
                            </div>
                            
                            <div class="space-y-3">
                                <button type="submit" class="w-full bg-gradient-to-r from-primary-500 to-secondary-500 text-white py-4 rounded-xl font-semibold hover:from-primary-600 hover:to-secondary-600 transition-all duration-300 shadow-lg hover:shadow-xl shimmer-effect">
                                    <i class="fas fa-search mr-2"></i>
                                    Aplicar Filtros
                                </button>
                                
                                <a href="categoria.php?categoria=<?php echo htmlspecialchars($categorySlug); ?>" 
                                   class="w-full border-2 border-primary-300 text-primary-700 py-4 rounded-xl font-semibold hover:bg-primary-50 transition-all duration-300 block text-center">
                                    <i class="fas fa-redo mr-2"></i>
                                    Limpiar Filtros
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Products Grid -->
                <div class="lg:w-3/4">
                    <?php if (!empty($products)): ?>
                        <!-- Mobile Sort Bar -->
                        <div class="lg:hidden mb-6 flex items-center justify-between bg-white/80 backdrop-blur-sm rounded-2xl p-4 shadow-lg">
                            <span class="text-primary-800 font-semibold"><?php echo $totalProducts; ?> productos</span>
                            <select name="mobile-sort" class="bg-transparent text-primary-700 font-medium focus:outline-none" 
                                    onchange="updateMobileSort(this.value)">
                                <option value="newest" <?php echo $filters['sort'] == 'newest' ? 'selected' : ''; ?>>Más nuevos</option>
                                <option value="price_low" <?php echo $filters['sort'] == 'price_low' ? 'selected' : ''; ?>>Precio ↑</option>
                                <option value="price_high" <?php echo $filters['sort'] == 'price_high' ? 'selected' : ''; ?>>Precio ↓</option>
                                <option value="name" <?php echo $filters['sort'] == 'name' ? 'selected' : ''; ?>>A-Z</option>
                            </select>
                        </div>
                        
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6 sm:gap-8 lg:gap-6 product-grid-mobile">
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
                                        $productImage = BASE_URL . '/' . $product['main_image'];
                                    } else {
                                        $productImage = BASE_URL . '/uploads/products/' . $product['main_image'];
                                    }
                                } else {
                                    $productImage = BASE_URL . '/assets/images/placeholder-product.svg';
                                }
                            ?>
                            <div class="product-card bg-white rounded-2xl shadow-md hover:shadow-xl overflow-hidden group transition-all duration-500 hover:-translate-y-2 flex flex-col h-full" 
                                 data-aos="fade-up" data-aos-delay="<?php echo array_search($product, $products) * 50; ?>">
                                <div class="relative overflow-hidden">
                                    <!-- Aspect ratio container para móvil -->
                                    <div class="aspect-square sm:aspect-[4/3] lg:aspect-square">
                                        <img src="<?php echo $productImage; ?>" 
                                             alt="<?php echo htmlspecialchars($product['name']); ?>" 
                                             class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-700"
                                             onerror="this.src='<?php echo BASE_URL; ?>/assets/images/placeholder-product.svg'"
                                             loading="lazy">
                                    </div>
                                    
                                    <!-- Product Badges -->
                                    <?php if ($discount > 0): ?>
                                    <div class="absolute top-2 sm:top-3 left-2 sm:left-3">
                                        <span class="bg-gradient-to-r from-red-500 to-pink-500 text-white px-2 sm:px-3 py-1 rounded-full text-xs sm:text-sm font-bold shadow-lg animate-pulse">
                                            -<?php echo $discount; ?>%
                                        </span>
                                    </div>
                                    <?php elseif (!empty($product['is_new']) && $product['is_new']): ?>
                                    <div class="absolute top-2 sm:top-3 left-2 sm:left-3">
                                        <span class="bg-gradient-to-r from-green-500 to-emerald-500 text-white px-2 sm:px-3 py-1 rounded-full text-xs sm:text-sm font-bold shadow-lg">
                                            NUEVO
                                        </span>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <!-- Quick Actions - Mejorados para móvil -->
                                    <div class="absolute top-2 sm:top-3 right-2 sm:right-3 flex flex-col space-y-1 sm:space-y-2 opacity-100 sm:opacity-0 sm:group-hover:opacity-100 transition-all duration-300 transform sm:translate-x-4 sm:group-hover:translate-x-0">
                                        <button class="w-8 h-8 sm:w-10 sm:h-10 bg-white/95 backdrop-blur-sm rounded-full flex items-center justify-center shadow-lg hover:bg-red-500 hover:text-white transition-all duration-300 hover:scale-110 wishlist-btn"
                                                onclick="toggleWishlist(<?php echo $product['id']; ?>)" 
                                                title="Agregar a favoritos"
                                                data-product-id="<?php echo $product['id']; ?>">
                                            <i class="fas fa-heart text-xs sm:text-sm"></i>
                                        </button>
                                        <button class="w-8 h-8 sm:w-10 sm:h-10 bg-white/95 backdrop-blur-sm rounded-full flex items-center justify-center shadow-lg hover:bg-blue-500 hover:text-white transition-all duration-300 hover:scale-110"
                                                onclick="quickView(<?php echo $product['id']; ?>)" 
                                                title="Vista rápida"
                                                data-product-id="<?php echo $product['id']; ?>"
                                                data-product-name="<?php echo htmlspecialchars($product['name']); ?>"
                                                data-product-price="<?php echo $product['price']; ?>"
                                                data-product-image="<?php echo $productImage; ?>">
                                            <i class="fas fa-eye text-xs sm:text-sm"></i>
                                        </button>
                                    </div>
                                </div>
                                
                                <div class="p-4 sm:p-5 lg:p-6 flex flex-col flex-grow">
                                    <!-- Marca -->
                                    <div class="mb-2 sm:mb-3">
                                        <span class="text-xs sm:text-sm text-secondary-600 font-medium"><?php echo htmlspecialchars($brandName); ?></span>
                                    </div>
                                    
                                    <!-- Nombre del producto -->
                                    <h3 class="font-semibold text-gray-900 mb-3 sm:mb-4 line-clamp-2 text-sm sm:text-base lg:text-lg leading-tight">
                                        <a href="product.php?id=<?php echo $product['id']; ?>" class="hover:text-primary-600 transition-colors">
                                            <?php echo htmlspecialchars($product['name']); ?>
                                        </a>
                                    </h3>
                                    
                                    <!-- Rating -->
                                    <div class="flex items-center mb-3 sm:mb-4">
                                        <div class="flex text-yellow-400">
                                            <?php for ($i = 0; $i < 5; $i++): ?>
                                            <i class="fas fa-star text-xs sm:text-sm"></i>
                                            <?php endfor; ?>
                                        </div>
                                        <span class="text-xs sm:text-sm text-gray-500 ml-2">(0)</span>
                                    </div>
                                    
                                    <!-- Price -->
                                    <div class="flex items-center justify-between mb-3 sm:mb-4">
                                        <div class="flex flex-col space-y-1">
                                            <div class="flex items-center flex-wrap gap-2">
                                                <span class="text-lg sm:text-xl lg:text-2xl font-bold bg-gradient-to-r from-primary-600 to-secondary-600 bg-clip-text text-transparent">
                                                    $<?php echo number_format($product['price'], 0, ',', '.'); ?>
                                                </span>
                                                <?php if (!empty($product['compare_price']) && $product['compare_price'] > $product['price']): ?>
                                                <span class="text-xs sm:text-sm text-gray-400 line-through">
                                                    $<?php echo number_format($product['compare_price'], 0, ',', '.'); ?>
                                                </span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Stock Status -->
                                    <div class="text-xs sm:text-sm mb-4 sm:mb-5">
                                        <?php if ($product['inventory_quantity'] > 0): ?>
                                            <span class="text-green-600 flex items-center">
                                                <i class="fas fa-check-circle mr-1"></i>
                                                En stock
                                                <span class="hidden sm:inline">(<?php echo $product['inventory_quantity']; ?> disponibles)</span>
                                            </span>
                                        <?php else: ?>
                                            <span class="text-red-600 flex items-center">
                                                <i class="fas fa-times-circle mr-1"></i>
                                                Agotado
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <!-- Botón de agregar al carrito -->
                                    <div class="mt-auto">
                                        <button class="w-full bg-gradient-to-r from-primary-500 to-secondary-500 text-white py-2.5 sm:py-3 rounded-lg font-semibold hover:shadow-lg transition-all duration-300 text-sm sm:text-base"
                                                onclick="addToCart(<?php echo $product['id']; ?>)" 
                                                <?php echo $product['inventory_quantity'] <= 0 ? 'disabled' : ''; ?>>
                                            <?php if ($product['inventory_quantity'] > 0): ?>
                                                <i class="fas fa-shopping-cart mr-2"></i>Agregar al Carrito
                                            <?php else: ?>
                                                <i class="fas fa-times mr-2"></i>Agotado
                                            <?php endif; ?>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <!-- Pagination -->
                        <?php if ($totalPages > 1): ?>
                        <div class="mt-12 flex justify-center" data-aos="fade-up">
                            <nav class="flex items-center space-x-2">
                                <?php if ($page > 1): ?>
                                <a href="?categoria=<?php echo $categorySlug; ?>&page=<?php echo $page-1; ?>&<?php echo http_build_query($filters); ?>" 
                                   class="px-4 py-3 border-2 border-primary-300 rounded-xl text-primary-700 hover:bg-primary-50 transition-all duration-300 shadow-lg hover:shadow-xl font-medium">
                                    <i class="fas fa-chevron-left mr-2"></i>Anterior
                                </a>
                                <?php endif; ?>
                                
                                <?php for ($i = max(1, $page-2); $i <= min($totalPages, $page+2); $i++): ?>
                                <a href="?categoria=<?php echo $categorySlug; ?>&page=<?php echo $i; ?>&<?php echo http_build_query($filters); ?>" 
                                   class="px-4 py-3 border-2 rounded-xl font-medium transition-all duration-300 shadow-lg hover:shadow-xl <?php echo $i == $page ? 'bg-gradient-to-r from-primary-500 to-secondary-500 text-white border-transparent' : 'border-primary-300 text-primary-700 hover:bg-primary-50'; ?>">
                                    <?php echo $i; ?>
                                </a>
                                <?php endfor; ?>
                                
                                <?php if ($page < $totalPages): ?>
                                <a href="?categoria=<?php echo $categorySlug; ?>&page=<?php echo $page+1; ?>&<?php echo http_build_query($filters); ?>" 
                                   class="px-4 py-3 border-2 border-primary-300 rounded-xl text-primary-700 hover:bg-primary-50 transition-all duration-300 shadow-lg hover:shadow-xl font-medium">
                                    Siguiente<i class="fas fa-chevron-right ml-2"></i>
                                </a>
                                <?php endif; ?>
                            </nav>
                        </div>
                        <?php endif; ?>
                        
                    <?php else: ?>
                        <!-- No Products Found -->
                        <div class="text-center py-16 lg:py-24" data-aos="fade-up">
                            <div class="bg-white/80 backdrop-blur-sm rounded-3xl p-8 lg:p-12 shadow-2xl max-w-lg mx-auto">
                                <div class="text-primary-300 text-6xl lg:text-8xl mb-6">
                                    <i class="fas fa-search"></i>
                                </div>
                                <h3 class="text-xl lg:text-2xl font-serif font-bold text-primary-800 mb-4">No se encontraron productos</h3>
                                <p class="text-primary-600 mb-8 leading-relaxed">
                                    Intenta ajustar los filtros o explora otras categorías para encontrar lo que buscas.
                                </p>
                                <div class="space-y-4">
                                    <a href="index.php" class="inline-flex items-center bg-gradient-to-r from-primary-500 to-secondary-500 text-white px-8 py-4 rounded-xl font-semibold hover:from-primary-600 hover:to-secondary-600 transition-all duration-300 shadow-lg hover:shadow-xl">
                                        <i class="fas fa-home mr-2"></i>
                                        Volver al inicio
                                    </a>
                                    <br>
                                    <a href="categoria.php?categoria=<?php echo htmlspecialchars($categorySlug); ?>" 
                                       class="inline-flex items-center border-2 border-primary-300 text-primary-700 px-8 py-4 rounded-xl font-semibold hover:bg-primary-50 transition-all duration-300">
                                        <i class="fas fa-redo mr-2"></i>
                                        Limpiar filtros
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Footer -->
    <?php include 'includes/global-footer.php'; ?>
    
    <!-- JavaScript -->
    <script>
        // Initialize AOS
        AOS.init({
            duration: 800,
            once: true,
            offset: 50
        });
        
        // Mobile Filter Functionality
        document.addEventListener('DOMContentLoaded', function() {
            const mobileFilterBtn = document.getElementById('mobile-filter-btn');
            const mobileFilterOverlay = document.getElementById('mobile-filter-overlay');
            const closeMobileFilter = document.getElementById('close-mobile-filter');
            const mobileFilterContent = document.getElementById('mobile-filter-content');
            const desktopFilters = document.querySelector('#filters-form');
            
            // Clone desktop filters to mobile
            if (desktopFilters && mobileFilterContent) {
                const clonedFilters = desktopFilters.cloneNode(true);
                clonedFilters.id = 'mobile-filters-form';
                
                // Style mobile filters
                const inputs = clonedFilters.querySelectorAll('input, select');
                inputs.forEach(input => {
                    input.className = input.className.replace('p-3', 'p-4').replace('p-4', 'p-4');
                });
                
                const buttons = clonedFilters.querySelectorAll('button, a');
                buttons.forEach(button => {
                    if (button.tagName === 'BUTTON') {
                        button.className = 'w-full bg-gradient-to-r from-primary-500 to-secondary-500 text-white py-4 rounded-xl font-semibold transition-all duration-300 shadow-lg';
                    }
                });
                
                mobileFilterContent.appendChild(clonedFilters);
            }
            
            // Mobile filter toggle
            if (mobileFilterBtn) {
                mobileFilterBtn.addEventListener('click', function() {
                    mobileFilterOverlay.classList.remove('hidden');
                    document.body.style.overflow = 'hidden';
                });
            }
            
            // Close mobile filter
            function closeMobileFilterFn() {
                mobileFilterOverlay.classList.add('hidden');
                document.body.style.overflow = '';
            }
            
            if (closeMobileFilter) {
                closeMobileFilter.addEventListener('click', closeMobileFilterFn);
            }
            
            if (mobileFilterOverlay) {
                mobileFilterOverlay.addEventListener('click', function(e) {
                    if (e.target === mobileFilterOverlay) {
                        closeMobileFilterFn();
                    }
                });
            }
            
            // Escape key to close mobile filter
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape' && !mobileFilterOverlay.classList.contains('hidden')) {
                    closeMobileFilterFn();
                }
            });
        });
        
        // Mobile sort functionality
        function updateMobileSort(value) {
            const url = new URL(window.location);
            url.searchParams.set('orden', value);
            window.location.href = url.toString();
        }
        
        // Generate CSRF token for AJAX requests
        function getCSRFToken() {
            return '<?php echo CSRFProtection::generateToken("cart"); ?>';
        }
        
        // Enhanced Add to cart function with better UX
        function addToCart(productId, quantity = 1) {
            const button = event.target;
            const originalText = button.innerHTML;
            
            // Show loading state
            button.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Agregando...';
            button.disabled = true;
            
            const csrfToken = getCSRFToken();
            
            if (!csrfToken) {
                showNotification('Error de seguridad. Recarga la página.', 'error');
                button.innerHTML = originalText;
                button.disabled = false;
                return;
            }
            
            fetch('cart-add.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `product_id=${productId}&quantity=${quantity}&csrf_token=${csrfToken}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification('¡Producto agregado al carrito!', 'success');
                    button.innerHTML = '<i class="fas fa-check mr-2"></i>¡Agregado!';
                    
                    // Reset button after 2 seconds
                    setTimeout(() => {
                        button.innerHTML = originalText;
                        button.disabled = false;
                    }, 2000);
                    
                    // Update cart count if element exists
                    updateCartCount();
                } else {
                    showNotification(data.message || 'Error al agregar producto', 'error');
                    button.innerHTML = originalText;
                    button.disabled = false;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('Error al agregar producto', 'error');
                button.innerHTML = originalText;
                button.disabled = false;
            });
        }
        
        // Update cart count
        function updateCartCount() {
            fetch('cart-count.php')
                .then(response => response.json())
                .then(data => {
                    const cartCount = document.querySelector('.cart-count');
                    if (cartCount && data.count !== undefined) {
                        cartCount.textContent = data.count;
                        cartCount.classList.add('animate-pulse');
                        setTimeout(() => cartCount.classList.remove('animate-pulse'), 1000);
                    }
                })
                .catch(error => console.error('Error updating cart count:', error));
        }
        
        // Toggle wishlist
        function toggleWishlist(productId) {
            showNotification('Funcionalidad de lista de deseos próximamente', 'info');
        }
        
        // Quick view
        function quickView(productId) {
            window.location.href = `product.php?id=${productId}`;
        }
        
        // Enhanced notification system
        function showNotification(message, type = 'info', duration = 4000) {
            // Remove existing notifications
            const existingNotifications = document.querySelectorAll('.notification');
            existingNotifications.forEach(notif => notif.remove());
            
            const notification = document.createElement('div');
            notification.className = `notification fixed top-4 right-4 z-50 p-4 rounded-xl shadow-2xl text-white max-w-sm transition-all duration-500 transform translate-x-full`;
            
            const colors = {
                success: 'bg-gradient-to-r from-green-500 to-emerald-500',
                error: 'bg-gradient-to-r from-red-500 to-pink-500',
                warning: 'bg-gradient-to-r from-yellow-500 to-orange-500',
                info: 'bg-gradient-to-r from-blue-500 to-cyan-500'
            };
            
            const icons = {
                success: 'fas fa-check-circle',
                error: 'fas fa-exclamation-circle',
                warning: 'fas fa-exclamation-triangle',
                info: 'fas fa-info-circle'
            };
            
            notification.className += ` ${colors[type] || colors.info}`;
            
            notification.innerHTML = `
                <div class="flex items-center">
                    <i class="${icons[type] || icons.info} mr-3 text-lg"></i>
                    <span class="flex-1">${message}</span>
                    <button onclick="this.closest('.notification').remove()" 
                            class="ml-4 text-white hover:text-gray-200 transition-colors">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            `;
            
            document.body.appendChild(notification);
            
            // Animate in
            setTimeout(() => {
                notification.classList.remove('translate-x-full');
            }, 100);
            
            // Auto remove
            setTimeout(() => {
                notification.classList.add('translate-x-full');
                setTimeout(() => {
                    if (notification.parentElement) {
                        notification.remove();
                    }
                }, 500);
            }, duration);
        }
        
        // Auto-submit filters form when changed (desktop only)
        document.querySelectorAll('#filters-form select, #filters-form input').forEach(element => {
            element.addEventListener('change', function() {
                if (window.innerWidth >= 1024) { // Only auto-submit on desktop
                    document.getElementById('filters-form').submit();
                }
            });
        });
        
        // Smooth scroll for pagination
        document.querySelectorAll('a[href*="page="]').forEach(link => {
            link.addEventListener('click', function() {
                setTimeout(() => {
                    document.querySelector('.container').scrollIntoView({ 
                        behavior: 'smooth',
                        block: 'start'
                    });
                }, 100);
            });
        });
        
        // Lazy loading for images (if not supported natively)
        if ('IntersectionObserver' in window) {
            const imageObserver = new IntersectionObserver((entries, observer) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const img = entry.target;
                        if (img.dataset.src) {
                            img.src = img.dataset.src;
                            img.removeAttribute('data-src');
                            observer.unobserve(img);
                        }
                    }
                });
            });
            
            document.querySelectorAll('img[data-src]').forEach(img => {
                imageObserver.observe(img);
            });
        }
        
        // Wishlist functionality
        function toggleWishlist(productId) {
            const btn = document.querySelector(`[data-product-id="${productId}"].wishlist-btn`);
            const icon = btn.querySelector('i');
            
            // Toggle visual state
            if (icon.classList.contains('fas')) {
                icon.classList.remove('fas');
                icon.classList.add('far');
                btn.classList.remove('bg-red-500', 'text-white');
                btn.classList.add('bg-white/95');
                showNotification('Producto eliminado de favoritos', 'success');
            } else {
                icon.classList.remove('far');
                icon.classList.add('fas');
                btn.classList.add('bg-red-500', 'text-white');
                btn.classList.remove('bg-white/95');
                showNotification('Producto agregado a favoritos', 'success');
            }
            
            // Here you would typically make an AJAX call to save/remove from wishlist
            // fetch('/api/wishlist.php', { method: 'POST', ... })
        }
        
        // Quick view functionality
        function quickView(productId) {
            const btn = document.querySelector(`[data-product-id="${productId}"][title="Vista rápida"]`);
            const productName = btn.dataset.productName;
            const productPrice = btn.dataset.productPrice;
            const productImage = btn.dataset.productImage;
            
            // Create modal (simplified version)
            const modal = document.createElement('div');
            modal.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4';
            modal.innerHTML = `
                <div class="bg-white rounded-2xl max-w-md w-full p-6 relative">
                    <button onclick="this.parentElement.parentElement.remove()" class="absolute top-4 right-4 w-8 h-8 bg-gray-100 rounded-full flex items-center justify-center hover:bg-gray-200">
                        <i class="fas fa-times"></i>
                    </button>
                    <div class="text-center">
                        <img src="${productImage}" alt="${productName}" class="w-32 h-32 object-cover mx-auto mb-4 rounded-xl">
                        <h3 class="text-xl font-bold mb-2">${productName}</h3>
                        <p class="text-2xl font-bold text-primary-600 mb-4">$${new Intl.NumberFormat().format(productPrice)}</p>
                        <div class="space-y-3">
                            <button onclick="addToCart(${productId})" class="w-full bg-gradient-to-r from-primary-500 to-secondary-500 text-white py-3 rounded-xl font-semibold">
                                <i class="fas fa-shopping-cart mr-2"></i>Agregar al Carrito
                            </button>
                            <a href="product.php?id=${productId}" class="block w-full bg-gray-100 text-gray-800 py-3 rounded-xl font-semibold text-center">
                                Ver Detalles Completos
                            </a>
                        </div>
                    </div>
                </div>
            `;
            document.body.appendChild(modal);
            
            // Close on click outside
            modal.addEventListener('click', function(e) {
                if (e.target === modal) {
                    modal.remove();
                }
            });
        }
        
        // Add to cart functionality
        function addToCart(productId) {
            // Show loading state
            const btn = event.target.closest('button');
            const originalText = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Agregando...';
            btn.disabled = true;
            
            // Simulate API call (replace with actual implementation)
            setTimeout(() => {
                btn.innerHTML = originalText;
                btn.disabled = false;
                showNotification('Producto agregado al carrito', 'success');
                
                // Update cart count (if you have a cart counter)
                // updateCartCount();
            }, 1000);
            
            // Here you would typically make an AJAX call
            // fetch('/api/cart.php', { method: 'POST', ... })
        }
        
        // Notification system
        function showNotification(message, type = 'info') {
            const notification = document.createElement('div');
            notification.className = `fixed top-4 right-4 z-50 p-4 rounded-xl shadow-lg text-white max-w-sm transition-all duration-300 transform translate-x-full`;
            
            switch(type) {
                case 'success':
                    notification.classList.add('bg-green-500');
                    break;
                case 'error':
                    notification.classList.add('bg-red-500');
                    break;
                default:
                    notification.classList.add('bg-blue-500');
            }
            
            notification.innerHTML = `
                <div class="flex items-center">
                    <i class="fas fa-${type === 'success' ? 'check' : type === 'error' ? 'times' : 'info'}-circle mr-3"></i>
                    <span>${message}</span>
                </div>
            `;
            
            document.body.appendChild(notification);
            
            // Animate in
            setTimeout(() => {
                notification.classList.remove('translate-x-full');
            }, 100);
            
            // Auto remove
            setTimeout(() => {
                notification.classList.add('translate-x-full');
                setTimeout(() => notification.remove(), 300);
            }, 3000);
        }
    </script>
</body>
</html>
