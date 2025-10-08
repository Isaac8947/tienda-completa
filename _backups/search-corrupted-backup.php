<?php
require_once 'includes/security-headers.php';
require_once 'includes/InputSanitizer.php';
require_once 'includes/RateLimiter.php';

// Check rate limiting for search
if (!RateLimiter::checkLimit('search', 20, 300)) {
    header('Location: /?error=too_many_searches');
    exit;
}

session_start();
require_once 'config/database.php';
require_once 'config/global-settings.php';
require_once 'models/Product.php';
require_once 'models/Category.php';
require_once 'models/Brand.php';

// Initialize variables
$query = '';
$totalProducts = 0;
$products = [];
$brands = [];
$categories = [];
$page = 1;
$totalPages = 0;
$filters = [];

// Sanitize and validate search query
if (isset($_GET['q'])) {
    $searchQuery = InputSanitizer::sanitizeString($_GET['q'], 100);
    
    // Check for malicious content
    if (InputSanitizer::detectSQLInjection($searchQuery) || InputSanitizer::detectXSS($searchQuery)) {
        InputSanitizer::logSuspiciousActivity($searchQuery, 'SEARCH_ATTACK');
        header('Location: /?error=invalid_search');
        exit;
    }
    
    // Minimum search length
    if (strlen(trim($searchQuery)) >= 2) {
        $query = $searchQuery;
    }
}

$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 12;
$offset = ($page - 1) * $perPage;

// Obtener filtros
$selectedBrand = $_GET['brand'] ?? '';
$selectedCategory = $_GET['category'] ?? '';
$minPrice = $_GET['min_price'] ?? 0;
$maxPrice = $_GET['max_price'] ?? 1000;
$sort = $_GET['sort'] ?? 'relevance';

$filters = [
    'brand' => $selectedBrand,
    'category' => $selectedCategory,
    'min_price' => $minPrice,
    'max_price' => $maxPrice,
    'sort' => $sort
];

$filters_applied = !empty($selectedBrand) || !empty($selectedCategory) || !empty($minPrice) || !empty($maxPrice);

try {
    $productModel = new Product();
    $brandModel = new Brand();
    $categoryModel = new Category();
    
    if (!empty($query)) {
        // Buscar productos
        $products = $productModel->searchProducts($query, $filters, $perPage, $offset);
        
        // Contar total de resultados
        $totalProducts = $productModel->countSearchResults($query, $filters);
        $totalPages = ceil($totalProducts / $perPage);
    } else {
        // Mostrar productos destacados si no hay búsqueda
        $products = $productModel->getFeaturedProducts($perPage);
        $totalProducts = count($products);
        $totalPages = 1;
    }
    
    // Obtener marcas y categorías para filtros
    $brands = $brandModel->getAll();
    $categories = $categoryModel->getAll();
    
} catch (Exception $e) {
    error_log("Error loading search page: " . $e->getMessage());
    $products = [];
    $brands = [];
    $categories = [];
    $totalProducts = 0;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo !empty($query) ? 'Resultados para: ' . htmlspecialchars($query) : 'Buscar Productos'; ?> - Odisea Store</title>
    <meta name="description" content="<?php echo !empty($query) ? 'Resultados de búsqueda para ' . htmlspecialchars($query) : 'Buscar productos'; ?> en Odisea Store. La mejor selección de productos de belleza y maquillaje.">

    <!-- DNS Prefetch para mejorar rendimiento -->
    <link rel="dns-prefetch" href="//fonts.googleapis.com">
    <link rel="dns-prefetch" href="//cdnjs.cloudflare.com">
    <link rel="dns-prefetch" href="//cdn.tailwindcss.com">

    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=Playfair+Display:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">

    <!-- Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

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
                        'slide-in-left': 'slideInLeft 0.6s ease-out',
                        'bounce-gentle': 'bounceGentle 2s ease-in-out infinite',
                        'pulse-soft': 'pulseSoft 3s ease-in-out infinite'
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
                        },
                        bounceGentle: {
                            '0%, 20%, 53%, 100%': { transform: 'translateY(0px)' },
                            '40%, 43%': { transform: 'translateY(-30px)' },
                            '70%': { transform: 'translateY(-15px)' },
                            '90%': { transform: 'translateY(-4px)' }
                        },
                        pulseSoft: {
                            '0%, 100%': { opacity: '1' },
                            '50%': { opacity: '0.8' }
                        }
                    }
                }
            }
        }
    </script>
    
    <style>
        .gradient-bg {
            background: linear-gradient(135deg, #b08d80 0%, #c4a575 25%, #d4af37 50%, #c4a575 75%, #b08d80 100%);
            background-size: 400% 400%;
            animation: gradientShift 10s ease infinite;
        }
        
        @keyframes gradientShift {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }
        
        .glass-effect {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .gradient-text {
            background: linear-gradient(135deg, #b08d80 0%, #c4a575 50%, #d4af37 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .product-card {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            height: 100%;
            display: flex;
            flex-direction: column;
        }
        
        .product-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
        }
        
        .product-card .card-content {
            flex: 1;
            display: flex;
            flex-direction: column;
            padding: 1rem;
        }
        
        .product-card .card-title {
            flex: 1;
            display: flex;
            align-items: flex-start;
            min-height: 3rem;
        }
        
        .product-card .card-actions {
            margin-top: auto;
            padding-top: 1rem;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #b08d80 0%, #c4a575 100%);
            transition: all 0.3s ease;
        }
        
        .btn-primary:hover {
            background: linear-gradient(135deg, #a67c76 0%, #b39256 100%);
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(176, 141, 128, 0.4);
        }
        
        .filter-panel {
            backdrop-filter: blur(10px);
            background: rgba(255, 255, 255, 0.95);
        }
        
        .search-highlight {
            background: linear-gradient(135deg, #f7f1e8, #fefdfb);
            border-left: 4px solid #d4af37;
        }
        
        .pagination-active {
            background: linear-gradient(135deg, #b08d80 0%, #c4a575 100%);
            color: white;
        }
        
        .action-buttons {
            position: absolute;
            top: 8px;
            right: 8px;
            display: flex;
            gap: 4px;
            opacity: 0;
            transform: translateY(-10px);
            transition: all 0.3s ease;
        }
        
        .product-card:hover .action-buttons {
            opacity: 1;
            transform: translateY(0);
        }
        
        .shadow-luxury {
            box-shadow: 0 20px 40px rgba(176, 141, 128, 0.15), 0 8px 16px rgba(196, 165, 117, 0.1);
        }
        
        .mobile-drawer {
            transform: translateX(-100%);
            transition: transform 0.3s ease-in-out;
        }
        
        .mobile-drawer.open {
            transform: translateX(0);
        }
        
        .touch-target {
            min-height: 44px;
            min-width: 44px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        @keyframes pulse-gentle {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }
        
        @media (max-width: 640px) {
            .grid-cols-2 {
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 1rem;
            }
            
            .product-card {
                margin-bottom: 1rem;
            }
            
            .product-card .card-content {
                padding: 0.75rem;
            }
            
            .product-card .card-title {
                min-height: 2.5rem;
                font-size: 0.875rem;
                line-height: 1.25;
            }
        }
        
        /* Fixed overlay issues */
        .hidden {
            display: none !important;
        }
        
        .filter-overlay {
            position: fixed !important;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5) !important;
            backdrop-filter: blur(4px);
            z-index: 9999 !important;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .filter-mobile-panel {
            background: white;
            border-radius: 20px;
            padding: 24px;
            max-width: 400px;
            width: 100%;
            max-height: 90vh;
            overflow-y: auto;
            transform: translateY(100px);
            opacity: 0;
            transition: all 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
        }
        
        .filter-mobile-panel.active {
            transform: translateY(0);
            opacity: 1;
        }
        
        .mobile-filter-btn {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: linear-gradient(135deg, #b08d80 0%, #c4a575 100%);
            color: white;
            padding: 12px 20px;
            border-radius: 50px;
            box-shadow: 0 10px 25px rgba(176, 141, 128, 0.4);
            z-index: 1000;
            font-weight: 600;
            border: none;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .mobile-filter-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 15px 35px rgba(176, 141, 128, 0.5);
        }
        
        .filter-group label {
            display: block;
            font-weight: 600;
            margin-bottom: 8px;
            color: #374151;
        }
        
        .filter-group select,
        .filter-group input {
            width: 100%;
            padding: 12px;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            transition: all 0.3s ease;
            background: white;
        }
        
        .filter-group select:focus,
        .filter-group input:focus {
            border-color: #b08d80;
            outline: none;
            box-shadow: 0 0 0 3px rgba(176, 141, 128, 0.1);
        }
    </style>
</head>

<body class="font-sans bg-white overflow-x-hidden"><?php 
    // Initialize contact settings for header
    require_once 'models/Settings.php';
    $settingsModel = new Settings();
    $contactSettings = $settingsModel->getContactSettings();
?>
    <!-- Desktop Header -->
    <header class="hidden md:block fixed top-0 left-0 right-0 z-50 transition-all duration-500" id="desktop-header">
        <!-- Top Bar -->
        <div class="bg-gradient-to-r from-primary-500 to-secondary-500 text-white py-2 text-sm">
            <div class="container mx-auto px-4">
                <div class="flex justify-between items-center">
                    <div class="flex items-center space-x-6">
                        <?php if (!empty($contactSettings['site_phone'])): ?>
                        <a href="tel:<?php echo str_replace(' ', '', $contactSettings['site_phone']); ?>" class="flex items-center space-x-2 hover:text-primary-200 transition-colors">
                            <i class="fas fa-phone text-xs"></i>
                            <span><?php echo htmlspecialchars($contactSettings['site_phone']); ?></span>
                        </a>
                        <?php endif; ?>

                        <?php if (!empty($contactSettings['site_email'])): ?>
                        <a href="mailto:<?php echo htmlspecialchars($contactSettings['site_email']); ?>" class="flex items-center space-x-2 hover:text-primary-200 transition-colors">
                            <i class="fas fa-envelope text-xs"></i>
                            <span><?php echo htmlspecialchars($contactSettings['site_email']); ?></span>
                        </a>
                        <?php endif; ?>
                    </div>
                    <div class="flex items-center space-x-4">
                        <span class="text-xs">Síguenos:</span>

                        <?php if (!empty($contactSettings['social_instagram'])): ?>
                        <a href="<?php echo htmlspecialchars($contactSettings['social_instagram']); ?>" target="_blank" class="hover:text-primary-200 transition-colors transform hover:scale-110" title="Instagram">
                            <i class="fab fa-instagram"></i>
                        </a>
                        <?php endif; ?>

                        <?php if (!empty($contactSettings['social_facebook'])): ?>
                        <a href="<?php echo htmlspecialchars($contactSettings['social_facebook']); ?>" target="_blank" class="hover:text-primary-200 transition-colors transform hover:scale-110" title="Facebook">
                            <i class="fab fa-facebook"></i>
                        </a>
                        <?php endif; ?>

                        <?php if (!empty($contactSettings['social_tiktok'])): ?>
                        <a href="<?php echo htmlspecialchars($contactSettings['social_tiktok']); ?>" target="_blank" class="hover:text-primary-200 transition-colors transform hover:scale-110" title="TikTok">
                            <i class="fab fa-tiktok"></i>
                        </a>
                        <?php endif; ?>

                        <?php if (!empty($contactSettings['social_youtube'])): ?>
                        <a href="<?php echo htmlspecialchars($contactSettings['social_youtube']); ?>" target="_blank" class="hover:text-primary-200 transition-colors transform hover:scale-110" title="YouTube">
                            <i class="fab fa-youtube"></i>
                        </a>
                        <?php endif; ?>

                        <?php if (!empty($contactSettings['social_twitter'])): ?>
                        <a href="<?php echo htmlspecialchars($contactSettings['social_twitter']); ?>" target="_blank" class="hover:text-primary-200 transition-colors transform hover:scale-110" title="Twitter">
                            <i class="fab fa-twitter"></i>
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Header -->
        <div class="glass-effect backdrop-blur-md">
            <div class="container mx-auto px-4 py-4">
                <div class="flex items-center justify-between">
                    <!-- Logo -->
                    <div class="flex items-center">
                        <a href="/" class="text-3xl font-serif font-bold gradient-text">
                            Odisea
                        </a>
                        <span class="ml-2 text-xs text-gray-500 font-light">STORE</span>
                    </div>

                    <!-- Search Bar -->
                    <div class="flex flex-1 max-w-xl mx-8 relative">
                        <form action="search.php" method="GET" class="relative w-full group">
                            <input type="text" 
                                   name="q"
                                   id="desktop-search-input"
                                   value="<?php echo htmlspecialchars($query); ?>"
                                   placeholder="Buscar productos, marcas..."
                                   class="w-full px-6 py-4 pr-14 bg-white/80 backdrop-blur-sm border border-gray-200 rounded-2xl focus:outline-none focus:ring-2 focus:ring-primary-500/50 focus:border-primary-500 transition-all duration-300 text-gray-700 placeholder-gray-400"
                                   required
                                   minlength="2"
                                   autocomplete="off">
                            <button type="submit" class="absolute right-4 top-1/2 transform -translate-y-1/2 w-8 h-8 bg-gradient-to-r from-primary-500 to-secondary-500 rounded-xl flex items-center justify-center text-white hover:shadow-lg transition-all duration-300">
                                <i class="fas fa-search text-sm"></i>
                            </button>
                        </form>
                    </div>

                    <!-- Header Actions -->
                    <div class="flex items-center space-x-6">
                        <!-- User Account -->
                        <div class="relative group">
                            <button class="flex items-center space-x-2 text-gray-700 hover:text-primary-500 transition-colors p-2 rounded-xl hover:bg-primary-50">
                                <div class="w-8 h-8 bg-gradient-to-r from-primary-500 to-secondary-500 rounded-full flex items-center justify-center text-white text-sm">
                                    <i class="fas fa-user"></i>
                                </div>
                                <span class="font-medium">Mi Cuenta</span>
                            </button>

                            <!-- Dropdown Menu -->
                            <div class="absolute right-0 mt-2 w-56 bg-white/95 backdrop-blur-md rounded-2xl shadow-2xl opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-300 z-50 border border-gray-100">
                                <div class="py-3">
                                    <?php if (isset($_SESSION['user_id'])): ?>
                                        <a href="mi-cuenta.php" class="flex items-center px-4 py-3 text-sm text-gray-700 hover:bg-primary-50 hover:text-primary-600 transition-colors">
                                            <i class="fas fa-user-circle mr-3 text-primary-500"></i>
                                            Mi Perfil
                                        </a>
                                        <a href="logout.php" class="flex items-center px-4 py-3 text-sm text-gray-700 hover:bg-red-50 hover:text-red-600 transition-colors">
                                            <i class="fas fa-sign-out-alt mr-3 text-red-500"></i>
                                            Cerrar Sesión
                                        </a>
                                    <?php else: ?>
                                        <a href="login.php" class="flex items-center px-4 py-3 text-sm text-gray-700 hover:bg-primary-50 hover:text-primary-600 transition-colors">
                                            <i class="fas fa-sign-in-alt mr-3 text-primary-500"></i>
                                            Iniciar Sesión
                                        </a>
                                        <a href="register.php" class="flex items-center px-4 py-3 text-sm text-gray-700 hover:bg-primary-50 hover:text-primary-600 transition-colors">
                                            <i class="fas fa-user-plus mr-3 text-primary-500"></i>
                                            Registrarse
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Wishlist -->
                        <button class="relative p-2 text-gray-700 hover:text-primary-500 transition-colors rounded-xl hover:bg-primary-50">
                            <i class="fas fa-heart text-xl"></i>
                            <?php if (isset($_SESSION['wishlist']) && count($_SESSION['wishlist']) > 0): ?>
                            <span class="absolute -top-1 -right-1 bg-gradient-to-r from-red-500 to-pink-500 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center font-medium shadow-lg">
                                <?php echo count($_SESSION['wishlist']); ?>
                            </span>
                            <?php endif; ?>
                        </button>

                        <!-- Shopping Cart -->
                        <a href="carrito.php" class="relative p-2 text-gray-700 hover:text-primary-500 transition-colors rounded-xl hover:bg-primary-50">
                            <i class="fas fa-shopping-bag text-xl"></i>
                            <span class="absolute -top-1 -right-1 bg-gradient-to-r from-primary-500 to-secondary-500 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center font-medium shadow-lg" id="cart-count">
                                <?php echo isset($_SESSION['cart']) ? array_sum(array_column($_SESSION['cart'], 'quantity')) : 0; ?>
                            </span>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Navigation -->
        <nav class="bg-white/90 backdrop-blur-md border-t border-gray-100">
            <div class="container mx-auto px-4">
                <div class="flex items-center justify-between">
                    <!-- Categories Menu -->
                    <div class="relative group">
                        <button class="flex items-center space-x-3 px-6 py-4 text-gray-700 hover:text-primary-500 transition-colors font-medium">
                            <i class="fas fa-th-large"></i>
                            <span>Categorías</span>
                            <i class="fas fa-chevron-down text-sm transition-transform group-hover:rotate-180"></i>
                        </button>
                    </div>

                    <!-- Main Navigation -->
                    <div class="flex items-center space-x-8">
                        <a href="index.php" class="text-gray-700 hover:text-primary-500 transition-colors font-medium py-4 border-b-2 border-transparent hover:border-primary-500">Inicio</a>
                        <a href="ofertas.php" class="text-gray-700 hover:text-primary-500 transition-colors font-medium py-4 border-b-2 border-transparent hover:border-primary-500">Ofertas</a>
                        <a href="marcas.php" class="text-gray-700 hover:text-primary-500 transition-colors font-medium py-4 border-b-2 border-transparent hover:border-primary-500">Marcas</a>
                    </div>

                    <!-- Promo Banner -->
                    <div class="block">
                        <div class="bg-gradient-to-r from-primary-500 to-secondary-500 text-white px-6 py-2 rounded-full text-sm font-medium animate-glow">
                            <i class="fas fa-shipping-fast mr-2"></i>
                            Envío GRATIS en compras +$150.000
                        </div>
                    </div>
                </div>
            </div>
        </nav>
    </header>

    <!-- Mobile Header -->
    <header class="md:hidden fixed top-0 left-0 right-0 z-50 bg-white/95 backdrop-blur-md shadow-lg" id="mobile-header">
        <div class="px-4 py-3">
            <div class="flex items-center justify-between">
                <!-- Mobile Menu Button -->
                <button class="touch-target p-2 text-gray-700 hover:text-primary-500 transition-colors rounded-xl hover:bg-primary-50" id="mobile-menu-btn">
                    <i class="fas fa-bars text-xl"></i>
                </button>

                <!-- Mobile Logo -->
                <div class="flex items-center">
                    <a href="/" class="text-2xl font-serif font-bold gradient-text">
                        Odisea
                    </a>
                    <span class="ml-1 text-xs text-gray-500 font-light">STORE</span>
                </div>

                <!-- Mobile Actions -->
                <div class="flex items-center space-x-2">
                    <!-- Search Button -->
                    <button class="touch-target p-2 text-gray-700 hover:text-primary-500 transition-colors rounded-xl hover:bg-primary-50" id="mobile-search-btn">
                        <i class="fas fa-search text-lg"></i>
                    </button>

                    <!-- Cart Button -->
                    <a href="carrito.php" class="touch-target relative p-2 text-gray-700 hover:text-primary-500 transition-colors rounded-xl hover:bg-primary-50">
                        <i class="fas fa-shopping-bag text-lg"></i>
                        <span class="absolute -top-1 -right-1 bg-gradient-to-r from-primary-500 to-secondary-500 text-white text-xs rounded-full h-4 w-4 flex items-center justify-center font-medium shadow-lg" id="mobile-cart-count">
                            <?php echo isset($_SESSION['cart']) ? array_sum(array_column($_SESSION['cart'], 'quantity')) : 0; ?>
                        </span>
                    </a>
                </div>
            </div>
        </div>

        <!-- Mobile Search Bar (Hidden by default) -->
        <div class="px-4 pb-3 hidden relative" id="mobile-search-bar">
            <form action="search.php" method="GET" class="relative">
                <input type="text"
                       name="q"
                       id="mobile-search-input"
                       value="<?php echo htmlspecialchars($query); ?>"
                       placeholder="Buscar productos, marcas..."
                       class="w-full px-4 py-3 pr-12 bg-gray-50 border border-gray-200 rounded-2xl focus:outline-none focus:ring-2 focus:ring-primary-500/50 focus:border-primary-500 transition-all duration-300 text-gray-700 placeholder-gray-400"
                       required
                       minlength="2"
                       autocomplete="off">
                <button type="submit" class="absolute right-3 top-1/2 transform -translate-y-1/2 w-8 h-8 bg-gradient-to-r from-primary-500 to-secondary-500 rounded-xl flex items-center justify-center text-white">
                    <i class="fas fa-search text-sm"></i>
                </button>
            </form>
        </div>
    </header>
                        pulseGentle: {
                            '0%, 100%': { opacity: '1' },
                            '50%': { opacity: '0.8' }
                        },
                        scaleIn: {
                            '0%': { opacity: '0', transform: 'scale(0.9)' },
                            '100%': { opacity: '1', transform: 'scale(1)' }
                        },
                        rotateSlow: {
                            '0%': { transform: 'rotate(0deg)' },
                            '100%': { transform: 'rotate(360deg)' }
                        }
                    },
                    boxShadow: {
                        'elegant': '0 4px 25px -3px rgba(176, 141, 128, 0.15), 0 2px 10px -2px rgba(176, 141, 128, 0.08)',
                        'premium': '0 10px 40px -8px rgba(176, 141, 128, 0.25), 0 4px 20px -4px rgba(176, 141, 128, 0.12)',
                        'luxury': '0 20px 60px -15px rgba(176, 141, 128, 0.35), 0 8px 30px -8px rgba(176, 141, 128, 0.18)',
                        'glow': '0 0 30px rgba(255, 215, 0, 0.3)',
                        'warm': '0 8px 32px -4px rgba(196, 165, 117, 0.2)'
                    }
                }
            }
        }
    </script>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- AOS Animation -->
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    
    <style>
        /* Simplified luxury design - removed complex animations and effects */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #faf9f7 0%, #f5f3f0 100%);
            color: #2d2d2d;
            line-height: 1.6;
        }

        /* Clean luxury color palette */
        :root {
            --primary: #b08d80;
            --secondary: #c4a575;
            --accent: #d4af37;
            --text: #2d2d2d;
            --text-light: #6b7280;
            --bg-white: #ffffff;
            --bg-light: #faf9f7;
        }

        /* Simple container */
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        /* Clean header */
        .header {
            background: var(--bg-white);
            padding: 20px 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 30px;
        }

        .header h1 {
            font-size: 28px;
            color: var(--primary);
            font-weight: 700;
            text-align: center;
        }

        /* Simple search section */
        .search-section {
            background: var(--bg-white);
            padding: 30px;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            margin-bottom: 30px;
        }

        .search-input {
            width: 100%;
            padding: 16px 20px;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            font-size: 16px;
            transition: border-color 0.3s ease;
        }

        .search-input:focus {
            outline: none;
            border-color: var(--primary);
        }

        /* Clean filter toggle */
        .filter-toggle {
            background: var(--primary);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .filter-toggle:hover {
            background: #9a7a6d;
        }

        /* Simple filters sidebar */
        .filters-sidebar {
            background: var(--bg-white);
            padding: 25px;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            height: fit-content;
        }

        .filter-group {
            margin-bottom: 20px;
            padding-bottom: 16px;
            border-bottom: 1px solid #f3f4f6;
        }

        .filter-group:last-child {
            border-bottom: none;
        }

        .filter-group h3 {
            font-size: 15px;
            font-weight: 600;
            color: var(--text);
            margin-bottom: 12px;
            font-family: 'Playfair Display', serif;
        }

        .filter-option {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
            padding: 6px 0;
        }

        .filter-option input[type="checkbox"] {
            margin-right: 10px;
            width: 16px;
            height: 16px;
            accent-color: var(--primary);
        }

        .filter-option label {
            font-size: 14px;
            color: var(--text-secondary);
            cursor: pointer;
            flex: 1;
        }

        /* Improved price range inputs styling */
        .price-range {
            display: flex;
            gap: 8px;
            align-items: center;
        }

        .price-range input {
            width: 80px;
            padding: 8px 12px;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            font-size: 14px;
        }

        .price-range span {
            color: var(--text-secondary);
            font-weight: 500;
        }

        /* Simplified product cards - removed complex effects and better spacing */
        .product-card {
            background: var(--bg-white);
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            overflow: hidden;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            display: flex;
            flex-direction: column;
            height: 100%;
            min-height: 450px;
        }

        .product-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 30px rgba(0,0,0,0.12);
        }

        .product-image {
            aspect-ratio: 1;
            background: #f8f9fa;
            position: relative;
            overflow: hidden;
            height: 200px;
            flex-shrink: 0;
        }

        .product-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s ease;
        }

        .product-card:hover .product-image img {
            transform: scale(1.05);
        }

        /* Clean product info */
        .product-info {
            padding: 20px;
            display: flex;
            flex-direction: column;
            flex: 1;
            height: 100%;
        }

        .product-category {
            color: var(--text-light);
            font-size: 14px;
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .product-title {
            font-size: 16px;
            font-weight: 600;
            color: var(--text);
            margin-bottom: 8px;
            line-height: 1.4;
            height: 44px;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .product-description {
            color: var(--text-light);
            font-size: 14px;
            margin-bottom: 16px;
            line-height: 1.5;
            height: 42px;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .product-price {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 16px;
            margin-top: auto;
        }

        /* Simple badges */
        .badge {
            position: absolute;
            top: 12px;
            left: 12px;
            background: var(--primary);
            color: white;
            padding: 6px 12px;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 600;
        }

        .badge.new {
            background: var(--accent);
        }

        .badge.discount {
            background: #ef4444;
        }

        /* Clean product info */
        .product-info {
            padding: 20px;
        }

        .product-category {
            color: var(--text-light);
            font-size: 14px;
            margin-bottom: 8px;
        }

        .product-title {
            font-size: 18px;
            font-weight: 600;
            color: var(--text);
            margin-bottom: 8px;
            line-height: 1.4;
        }

        .product-description {
            color: var(--text-light);
            font-size: 14px;
            margin-bottom: 16px;
            line-height: 1.5;
        }

        .product-price {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 16px;
        }

        .price-current {
            font-size: 20px;
            font-weight: 700;
            color: var(--primary);
        }

        .price-old {
            font-size: 16px;
            color: var(--text-light);
            text-decoration: line-through;
            margin-left: 8px;
        }

        .product-rating {
            display: flex;
            align-items: center;
            color: #fbbf24;
        }

        .rating-text {
            color: var(--text-light);
            font-size: 14px;
            margin-left: 8px;
        }

        /* Simple buttons */
        .btn-primary {
            background: var(--primary);
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.3s ease;
            width: 100%;
        }

        .btn-primary:hover {
            background: #9a7a6d;
        }

        .btn-secondary {
            background: #f3f4f6;
            color: var(--text);
            border: none;
            padding: 12px 16px;
            border-radius: 12px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .btn-secondary:hover {
            background: #e5e7eb;
        }

        /* Action buttons styling */
        .wishlist-btn:hover {
            background: rgba(239, 68, 68, 0.9) !important;
            color: white !important;
            transform: scale(1.1);
        }

        .quick-view-btn:hover {
            background: var(--primary) !important;
            color: white !important;
            transform: scale(1.1);
        }

        .wishlist-btn.active {
            background: rgba(239, 68, 68, 0.9) !important;
            color: white !important;
        }

        /* Pagination styling */
        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 8px;
            margin-top: 32px;
            padding: 16px;
            background: white;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
        }

        .pagination a {
            padding: 10px 16px;
            border-radius: 8px;
            text-decoration: none;
            color: var(--text);
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .pagination a:hover {
            background: var(--primary);
            color: white;
        }

        .pagination a.active {
            background: var(--primary);
            color: white;
        }

        /* Grid layout */
        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 24px;
            margin-bottom: 40px;
        }

        /* Updated mobile grid to show 2x2 cards instead of single column */
        @media (max-width: 768px) {
            .container {
                padding: 0 12px;
            }
            
            .search-section {
                padding: 16px;
            }
            
            .products-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 12px;
            }
            
            .product-card {
                border-radius: 12px;
                min-height: 380px;
            }
            
            .product-image {
                height: 160px;
                aspect-ratio: 1;
            }
            
            .product-info {
                padding: 16px;
            }
            
            .product-title {
                font-size: 14px;
                height: 38px;
                line-height: 1.3;
            }
            
            .product-description {
                font-size: 12px;
                height: 32px;
                -webkit-line-clamp: 2;
            }
            
            .price-current {
                font-size: 16px;
            }
            
            .rating-text {
                font-size: 12px;
            }
            
            .btn-primary {
                padding: 10px 12px;
                font-size: 14px;
            }
        }
            }
            
            .product-image {
                aspect-ratio: 4/3; /* Better proportion for mobile */
                min-height: 120px;
            }
            
            .product-info {
                padding: 8px;
            }
            
            .product-info h3 {
                font-size: 13px;
                line-height: 1.3;
                margin-bottom: 4px;
                font-weight: 600;
            }
            
            .product-info .price {
                font-size: 14px;
                font-weight: 700;
                margin-bottom: 6px;
            }
            
            .product-info .brand {
                font-size: 11px;
                margin-bottom: 6px;
            }
            
            .btn-add-cart {
                padding: 6px 12px;
                font-size: 12px;
                border-radius: 8px;
                width: 100%;
            }
            
            /* Fixed filter button positioning */
            .mobile-filter-btn {
                position: fixed;
                bottom: 20px;
                right: 20px;
                z-index: 1000;
                background: var(--primary-color);
                color: white;
                border: none;
                border-radius: 50px;
                padding: 12px 20px;
                box-shadow: 0 4px 20px rgba(176, 141, 128, 0.3);
                font-size: 14px;
                font-weight: 600;
            }
        }

        /* Fixed filter sidebar functionality */
        .filter-sidebar {
            background: var(--bg-white);
            border-radius: 16px;
            padding: 24px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            margin-bottom: 24px;
        }
        
        .filter-sidebar h3 {
            font-family: 'Playfair Display', serif;
            color: var(--primary-color);
            margin-bottom: 16px;
            font-size: 20px;
        }
        
        .filter-group {
            margin-bottom: 20px;
        }
        
        .filter-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--text-dark);
        }
        
        .filter-group select,
        .filter-group input {
            width: 100%;
            padding: 10px 12px;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-size: 14px;
            transition: border-color 0.3s ease;
        }
        
        .filter-group select:focus,
        .filter-group input:focus {
            outline: none;
            border-color: var(--primary-color);
        }

        /* Mobile filter overlay */
        .filter-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
            display: flex;
            align-items: flex-end;
        }
        
        .filter-overlay.hidden {
            display: none;
        }
        
        .filter-mobile-panel {
            background: white;
            width: 100%;
            max-height: 80vh;
            border-radius: 20px 20px 0 0;
            padding: 20px;
            transform: translateY(100%);
            transition: transform 0.4s ease;
            overflow-y: auto;
        }
        
        .filter-mobile-panel.active {
            transform: translateY(0);
        }

        /* Mobile responsive */
        @media (max-width: 768px) {
            .container {
                padding: 0 16px;
            }
            
            .search-section {
                padding: 20px;
            }
            
            .products-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 12px;
            }
            
            .product-info {
                padding: 12px;
            }
            
            .product-info h3 {
                font-size: 14px;
                margin-bottom: 4px;
            }
            
            .product-info .price {
                font-size: 16px;
            }
        }
    </style>
</head>

<body class="bg-gradient-to-br from-luxury-pearl via-primary-50 to-luxury-champagne font-sans">
    <!-- Header -->
    <?php include 'includes/global-header.php'; ?>
    
    <!-- Enhanced luxury hero section with advanced particles and floating elements -->
    <div class="container">
            <!-- Simplified header -->
            <header class="header">
                <h1>OdiseaStore - Búsqueda de Productos</h1>
            </header>

            <!-- Clean search section -->
            <div class="search-section">
                <form method="GET" class="search-form">
                    <div style="display: flex; gap: 16px; align-items: center; flex-wrap: wrap;">
                        <input type="text" 
                               name="q" 
                               value="<?php echo htmlspecialchars($query); ?>" 
                               placeholder="Buscar productos..." 
                               class="search-input"
                               style="flex: 1; min-width: 250px;">
                        
                        <button type="button" class="filter-toggle" onclick="toggleFilters()">
                            <i class="fas fa-filter"></i> Filtros
                        </button>
                        
                        <button type="submit" class="btn-primary" style="width: auto; padding: 16px 24px;">
                            <i class="fas fa-search"></i> Buscar
                        </button>
                    </div>
                </form>
            </div>

            <div style="display: flex; gap: 30px;">
                <!-- Updated filters sidebar with cleaner styling -->
                <aside class="filters-sidebar" style="width: 280px; display: none;" id="filtersSidebar">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; padding-bottom: 16px; border-bottom: 2px solid var(--primary);">
                        <h2 style="font-size: 18px; font-weight: 600; font-family: 'Playfair Display', serif; color: var(--primary);">Filtros</h2>
                        <button onclick="toggleFilters()" style="background: none; border: none; font-size: 18px; color: var(--text-secondary); cursor: pointer;">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>

                    <form method="GET" id="filtersForm">
                        <input type="hidden" name="q" value="<?php echo htmlspecialchars($query); ?>">
                        
                        <div class="filter-group">
                            <h3>Categoría</h3>
                            <select name="category" class="search-input" style="width: 100%; padding: 10px; border-radius: 8px; border: 1px solid #e5e7eb;">
                                <option value="">Todas las categorías</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo htmlspecialchars($category['id']); ?>" 
                                            <?php echo $selectedCategory == $category['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($category['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="filter-group">
                            <h3>Marca</h3>
                            <select name="brand" class="search-input" style="width: 100%; padding: 10px; border-radius: 8px; border: 1px solid #e5e7eb;">
                                <option value="">Todas las marcas</option>
                                <?php foreach ($brands as $brand): ?>
                                    <option value="<?php echo htmlspecialchars($brand['id']); ?>" 
                                            <?php echo $selectedBrand == $brand['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($brand['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="filter-group">
                            <h3>Rango de Precio</h3>
                            <div class="price-range">
                                <input type="number" name="min_price" value="<?php echo $minPrice; ?>" 
                                       placeholder="Min">
                                <span>-</span>
                                <input type="number" name="max_price" value="<?php echo $maxPrice; ?>" 
                                       placeholder="Max">
                            </div>
                        </div>

                        <div class="filter-group">
                            <h3>Ordenar por</h3>
                            <select name="sort" class="search-input" style="width: 100%; padding: 10px; border-radius: 8px; border: 1px solid #e5e7eb;">
                                <option value="relevance" <?php echo $sort === 'relevance' ? 'selected' : ''; ?>>Relevancia</option>
                                <option value="price_asc" <?php echo $sort === 'price_asc' ? 'selected' : ''; ?>>Precio: menor a mayor</option>
                                <option value="price_desc" <?php echo $sort === 'price_desc' ? 'selected' : ''; ?>>Precio: mayor a menor</option>
                                <option value="name_asc" <?php echo $sort === 'name_asc' ? 'selected' : ''; ?>>Nombre: A-Z</option>
                                <option value="name_desc" <?php echo $sort === 'name_desc' ? 'selected' : ''; ?>>Nombre: Z-A</option>
                            </select>
                        </div>

                        <button type="submit" class="btn-primary" style="width: 100%; margin-top: 16px;">Aplicar Filtros</button>
                    </form>
                </aside>

                <!-- Simplified products section -->
                <main style="flex: 1;">
                    <?php if (!empty($products)): ?>
                        <div class="products-grid">
                            <?php foreach ($products as $product): ?>
                            <div class="product-card">
                                <div class="product-image">
                                    <?php if (!empty($product['main_image'])): ?>
                                        <img src="<?php echo htmlspecialchars($product['main_image']); ?>" 
                                             alt="<?php echo htmlspecialchars($product['name']); ?>">
                                    <?php else: ?>
                                        <div style="display: flex; align-items: center; justify-content: center; height: 100%; color: #9ca3af;">
                                            <i class="fas fa-image" style="font-size: 48px;"></i>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <!-- Badges -->
                                    <div style="position: absolute; top: 8px; left: 8px;">
                                        <?php if ($product['is_featured']): ?>
                                            <span class="badge">Destacado</span>
                                        <?php elseif ($product['is_new']): ?>
                                            <span class="badge new">Nuevo</span>
                                        <?php elseif (isset($product['compare_price']) && $product['compare_price'] > $product['price']): ?>
                                            <span class="badge discount">
                                                <?php echo round((($product['compare_price'] - $product['price']) / $product['compare_price']) * 100); ?>% OFF
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <!-- Action buttons (heart and eye) -->
                                    <div style="position: absolute; top: 8px; right: 8px; display: flex; flex-direction: column; gap: 8px;">
                                        <!-- Wishlist button -->
                                        <button onclick="toggleWishlist(<?php echo $product['id']; ?>)" 
                                                class="wishlist-btn"
                                                style="width: 32px; height: 32px; background: rgba(255,255,255,0.9); backdrop-filter: blur(10px); border: none; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #6b7280; cursor: pointer; transition: all 0.3s ease; box-shadow: 0 2px 8px rgba(0,0,0,0.1);"
                                                title="Agregar a favoritos">
                                            <i class="fas fa-heart" style="font-size: 14px;"></i>
                                        </button>
                                        
                                        <!-- Quick view button -->
                                        <button onclick="quickView(<?php echo $product['id']; ?>)" 
                                                class="quick-view-btn"
                                                style="width: 32px; height: 32px; background: rgba(255,255,255,0.9); backdrop-filter: blur(10px); border: none; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #6b7280; cursor: pointer; transition: all 0.3s ease; box-shadow: 0 2px 8px rgba(0,0,0,0.1);"
                                                title="Vista rápida">
                                            <i class="fas fa-eye" style="font-size: 14px;"></i>
                                        </button>
                                    </div>
                                </div>
                                
                                <div class="product-info">
                                    <div class="product-category">
                                        <?php echo htmlspecialchars($product['category_name'] ?? 'Sin categoría'); ?>
                                    </div>
                                    
                                    <h3 class="product-title">
                                        <?php echo htmlspecialchars($product['name']); ?>
                                    </h3>
                                    
                                    <?php if (!empty($product['short_description'])): ?>
                                    <p class="product-description">
                                        <?php echo htmlspecialchars(substr($product['short_description'], 0, 100)) . '...'; ?>
                                    </p>
                                    <?php endif; ?>
                                    
                                    <div class="product-price">
                                        <div>
                                            <span class="price-current">$<?php echo number_format($product['price'], 2); ?></span>
                                            <?php if (isset($product['compare_price']) && $product['compare_price'] > $product['price']): ?>
                                                <span class="price-old">$<?php echo number_format($product['compare_price'], 2); ?></span>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <div class="product-rating">
                                            <i class="fas fa-star"></i>
                                            <i class="fas fa-star"></i>
                                            <i class="fas fa-star"></i>
                                            <i class="fas fa-star"></i>
                                            <i class="fas fa-star"></i>
                                            <span class="rating-text">(4.8)</span>
                                        </div>
                                    </div>
                                    
                                    <div style="display: flex; gap: 8px;">
                                        <button class="btn-primary" style="flex: 1;">
                                            <i class="fas fa-shopping-cart"></i> Agregar
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>

                        <!-- Simple pagination -->
                        <?php if ($totalPages > 1): ?>
                        <nav class="pagination">
                            <?php if ($page > 1): ?>
                                <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>">
                                    <i class="fas fa-chevron-left"></i> Anterior
                                </a>
                            <?php endif; ?>
                            
                            <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                                <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>" 
                                   class="<?php echo $i == $page ? 'active' : ''; ?>">
                                    <?php echo $i; ?>
                                </a>
                            <?php endfor; ?>
                            
                            <?php if ($page < $totalPages): ?>
                                <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>">
                                    Siguiente <i class="fas fa-chevron-right"></i>
                                </a>
                            <?php endif; ?>
                        </nav>
                        <?php endif; ?>

                    <?php else: ?>
                        <div style="text-align: center; padding: 60px 20px; background: var(--bg-white); border-radius: 16px;">
                            <i class="fas fa-search" style="font-size: 48px; color: #9ca3af; margin-bottom: 16px;"></i>
                            <h3 style="font-size: 24px; margin-bottom: 8px;">No se encontraron productos</h3>
                            <p style="color: var(--text-light);">Intenta con otros términos de búsqueda o ajusta los filtros.</p>
                        </div>
                    <?php endif; ?>
                </main>
            </div>
        </div>

        <!-- Mobile filter overlay -->
        <div class="filter-overlay" id="filterOverlay" onclick="toggleFilters()"></div>

        <script>
        function toggleFilters() {
            const sidebar = document.getElementById('filtersSidebar');
            const overlay = document.getElementById('filterOverlay');
            
            if (window.innerWidth <= 768) {
                sidebar.classList.toggle('filter-sidebar-mobile');
                sidebar.classList.toggle('active');
                overlay.classList.toggle('active');
            } else {
                sidebar.style.display = sidebar.style.display === 'none' ? 'block' : 'none';
            }
        }

        // Wishlist functionality
        function toggleWishlist(productId) {
            const btn = event.currentTarget;
            const icon = btn.querySelector('i');
            
            // Toggle active state
            btn.classList.toggle('active');
            
            // Add animation
            icon.style.transform = 'scale(1.3)';
            setTimeout(() => {
                icon.style.transform = 'scale(1)';
            }, 200);
            
            // Here you would add AJAX call to save to wishlist
            console.log('Toggled wishlist for product:', productId);
        }

        // Quick view functionality
        function quickView(productId) {
            // Add loading state
            const btn = event.currentTarget;
            const originalContent = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin" style="font-size: 14px;"></i>';
            
            // Simulate loading
            setTimeout(() => {
                btn.innerHTML = originalContent;
                // Redirect to product page
                window.location.href = `product.php?id=${productId}`;
            }, 500);
        }

        // Auto-submit filters on change
        document.getElementById('filtersForm').addEventListener('change', function() {
            this.submit();
        });

        // Close filters on window resize and adjust grid
        window.addEventListener('resize', function() {
            if (window.innerWidth > 768) {
                document.getElementById('filtersSidebar').classList.remove('active', 'filter-sidebar-mobile');
                document.getElementById('filterOverlay').classList.remove('active');
            }
        });
        </script>
    
    <!-- Footer -->
    <?php include 'includes/global-footer.php'; ?>
    <?php include 'includes/footer.php'; ?>
    
    <!-- Enhanced JavaScript with better functionality -->
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        // Initialize AOS with enhanced settings
        if (typeof AOS !== 'undefined') {
            AOS.init({
                duration: 700,
                easing: 'ease-out-cubic',
                once: true,
                mirror: false,
                offset: 100,
                anchorPlacement: 'top-bottom'
            });
        }
        
        // Enhanced mobile filter functionality
        const mobileFilterToggle = document.getElementById('mobile-filter-toggle');
        const filterOverlay = document.getElementById('filter-overlay');
        const closeFilter = document.getElementById('close-filter');
        const filtersForm = document.getElementById('filters-form');
        const mobileFiltersContainer = document.getElementById('mobile-filters-container');
        
        if (mobileFilterToggle && filterOverlay) {
            mobileFilterToggle.addEventListener('click', () => {
                filterOverlay.classList.remove('hidden');
                setTimeout(() => {
                    filterOverlay.querySelector('.filter-mobile-panel').classList.add('active');
                }, 10);
                
                // Clone desktop filters to mobile
                if (filtersForm && mobileFiltersContainer) {
                    const mobileFilters = filtersForm.cloneNode(true);
                    mobileFilters.id = 'mobile-filters-form';
                    mobileFiltersContainer.innerHTML = '';
                    mobileFiltersContainer.appendChild(mobileFilters);
                }
                document.body.style.overflow = 'hidden';
            });
            
            const closeMobileFilter = () => {
                filterOverlay.querySelector('.filter-mobile-panel').classList.remove('active');
                setTimeout(() => {
                    filterOverlay.classList.add('hidden');
                    document.body.style.overflow = '';
                }, 400);
            };
            
            closeFilter?.addEventListener('click', closeMobileFilter);
            
            filterOverlay.addEventListener('click', (e) => {
                if (e.target === filterOverlay) {
                    closeMobileFilter();
                }
            });
        }
        
        // Mobile search functionality
        const mobileSearchBtn = document.getElementById('mobile-search-btn');
        const mobileSearchBar = document.getElementById('mobile-search-bar');
        
        if (mobileSearchBtn && mobileSearchBar) {
            mobileSearchBtn.addEventListener('click', function() {
                if (mobileSearchBar.classList.contains('hidden')) {
                    mobileSearchBar.classList.remove('hidden');
                    // Focus on input after animation
                    const searchInput = mobileSearchBar.querySelector('input[name="q"]');
                    setTimeout(() => searchInput.focus(), 100);
                } else {
                    mobileSearchBar.classList.add('hidden');
                }
            });
        }
    </script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const filterToggle = document.getElementById('filter-toggle');
    const filterOverlay = document.getElementById('filter-overlay');
    const closeFilters = document.getElementById('close-filters');
    const applyFilters = document.getElementById('apply-filters');
    const clearFilters = document.getElementById('clear-filters');
    
    // Toggle filter overlay
    if (filterToggle && filterOverlay) {
        filterToggle.addEventListener('click', function() {
            filterOverlay.classList.remove('hidden');
            filterOverlay.classList.add('flex');
            document.body.style.overflow = 'hidden';
        });
    }
    
    // Close filter overlay
    if (closeFilters && filterOverlay) {
        closeFilters.addEventListener('click', function() {
            filterOverlay.classList.add('hidden');
            filterOverlay.classList.remove('flex');
            document.body.style.overflow = 'auto';
        });
    }
    
    // Apply filters
    if (applyFilters) {
        applyFilters.addEventListener('click', function() {
            const form = document.getElementById('filter-form');
            if (form) {
                form.submit();
            }
        });
    }
    
    // Clear filters
    if (clearFilters) {
        clearFilters.addEventListener('click', function() {
            // Reset all form inputs
            const form = document.getElementById('filter-form');
            if (form) {
                const inputs = form.querySelectorAll('input, select');
                inputs.forEach(input => {
                    if (input.type === 'checkbox' || input.type === 'radio') {
                        input.checked = false;
                    } else {
                        input.value = '';
                    }
                });
                
                // Reset price range
                const minPrice = form.querySelector('input[name="min_price"]');
                const maxPrice = form.querySelector('input[name="max_price"]');
                if (minPrice) minPrice.value = '0';
                if (maxPrice) maxPrice.value = '1000';
                
                // Submit form to clear filters
                form.submit();
            }
        });
    }
    
    // Close overlay when clicking outside
    if (filterOverlay) {
        filterOverlay.addEventListener('click', function(e) {
            if (e.target === filterOverlay) {
                filterOverlay.classList.add('hidden');
                filterOverlay.classList.remove('flex');
                document.body.style.overflow = 'auto';
            }
        });
    }
    
    // Handle price range inputs
    const minPriceInput = document.querySelector('input[name="min_price"]');
    const maxPriceInput = document.querySelector('input[name="max_price"]');
    
    if (minPriceInput && maxPriceInput) {
        minPriceInput.addEventListener('input', function() {
            const minVal = parseInt(this.value);
            const maxVal = parseInt(maxPriceInput.value);
            if (minVal > maxVal) {
                maxPriceInput.value = minVal;
            }
        });
        
        maxPriceInput.addEventListener('input', function() {
            const maxVal = parseInt(this.value);
            const minVal = parseInt(minPriceInput.value);
            if (maxVal < minVal) {
                minPriceInput.value = maxVal;
            }
        });
    }
});
</script>
</body>
</html>
