<?php
// Configuración de sesión segura (debe ir antes de session_start)
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 0); // Cambiar a 1 en HTTPS
ini_set('session.cookie_samesite', 'Strict');

session_start();

require_once 'config/database.php';
require_once 'config/global-settings.php';
require_once 'models/Product.php';
require_once 'models/Brand.php';
require_once 'models/Category.php';
require_once 'models/Review.php';

// Get product ID from URL
$product_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$product_id) {
    header('Location: index.php');
    exit;
}

try {
    $database = new Database();
    $db = $database->getConnection();
    $productModel = new Product();
    $brandModel = new Brand();
    $categoryModel = new Category();
    $reviewModel = new Review($db);
    
    // Get product details
    $product = $productModel->findById($product_id);
    if (!$product) {
        header('Location: 404.php');
        exit;
    }
    
    // Get brand and category information
    $brand = null;
    if (!empty($product['brand_id'])) {
        $brand = $brandModel->getById($product['brand_id']);
    }
    
    $category = null;
    if (!empty($product['category_id'])) {
        $category = $categoryModel->getById($product['category_id']);
    }
    
    // Get additional product images
    $imagesQuery = "SELECT image_url FROM product_images WHERE product_id = ? ORDER BY sort_order ASC";
    $imagesStmt = $db->prepare($imagesQuery);
    $imagesStmt->execute([$product_id]);
    $additionalImages = $imagesStmt->fetchAll();
    
    // Check if product is in wishlist
    $inWishlist = false;
    if (isset($_SESSION['user_id'])) {
        $wishlistQuery = "SELECT id FROM wishlists WHERE customer_id = ? AND product_id = ?";
        $wishlistStmt = $db->prepare($wishlistQuery);
        $wishlistStmt->execute([$_SESSION['user_id'], $product_id]);
        $inWishlist = $wishlistStmt->fetch() !== false;
    }
    
    // Calculate discount
    $discount = 0;
    $originalPrice = $product['price'];
    $salePrice = $product['compare_price'];
    if ($salePrice && $salePrice < $originalPrice) {
        $discount = round((($originalPrice - $salePrice) / $originalPrice) * 100);
        $displayPrice = $salePrice;
    } else {
        $displayPrice = $originalPrice;
    }
    
    // Get related products
    $relatedQuery = "SELECT * FROM products WHERE category_id = ? AND id != ? AND status = 'active' LIMIT 4";
    $relatedStmt = $db->prepare($relatedQuery);
    $relatedStmt->execute([$product['category_id'], $product_id]);
    $relatedProducts = $relatedStmt->fetchAll();
    
    // Get product reviews and ratings
    $reviews = $reviewModel->getByProductId($product_id, 10, 0);
    $ratingData = $reviewModel->getAverageRating($product_id);
    $ratingDistribution = $reviewModel->getRatingDistribution($product_id);
    
    // Check if current user can review this product
    $canReview = false;
    if (isset($_SESSION['user_id'])) {
        $canReview = $reviewModel->canCustomerReview($_SESSION['user_id'], $product_id);
    }
    
    // Get cart count for header
    $cartCount = 0;
    if (isset($_SESSION['cart'])) {
        $cartCount = array_sum(array_column($_SESSION['cart'], 'quantity'));
    }
    
    // Process product image
    if (!empty($product['main_image'])) {
        if (strpos($product['main_image'], 'uploads/products/') === 0) {
            // La ruta ya incluye uploads/products/
            $productImage = BASE_URL . '/' . $product['main_image'];
        } else {
            // Solo el nombre del archivo
            $productImage = BASE_URL . '/uploads/products/' . $product['main_image'];
        }
    } else {
        $productImage = '/placeholder.svg?height=600&width=600&text=Producto';
    }
    
    // Process related products images
    foreach ($relatedProducts as &$relatedProduct) {
        if (!empty($relatedProduct['main_image'])) {
            if (strpos($relatedProduct['main_image'], 'uploads/products/') === 0) {
                // La ruta ya incluye uploads/products/
                $relatedProduct['processed_image'] = BASE_URL . '/' . $relatedProduct['main_image'];
            } else {
                // Solo el nombre del archivo
                $relatedProduct['processed_image'] = BASE_URL . '/uploads/products/' . $relatedProduct['main_image'];
            }
        } else {
            $relatedProduct['processed_image'] = '/placeholder.svg?height=300&width=300&text=Producto';
        }
    }

} catch (Exception $e) {
    error_log("Error in product.php: " . $e->getMessage());
    header('Location: 404.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($product['name']); ?> - Odisea Makeup Store</title>
    <meta name="description" content="<?php echo htmlspecialchars($product['short_description'] ?? ''); ?>">
    
    <!-- DNS Prefetch -->
    <link rel="dns-prefetch" href="//fonts.googleapis.com">
    <link rel="dns-prefetch" href="//fonts.gstatic.com">
    <link rel="dns-prefetch" href="//cdnjs.cloudflare.com">
    <link rel="dns-prefetch" href="//unpkg.com">
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=Playfair+Display:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- AOS Animation -->
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    
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
                        },
                        offer: {
                            50: '#fef2f2',
                            100: '#fee2e2',
                            200: '#fecaca',
                            300: '#fca5a5',
                            400: '#f87171',
                            500: '#ef4444',
                            600: '#dc2626',
                            700: '#b91c1c',
                            800: '#991b1b',
                            900: '#7f1d1d'
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
                        'pulse-glow': 'pulseGlow 2s ease-in-out infinite',
                        'bounce-slow': 'bounceSlow 3s ease-in-out infinite',
                        'sparkle': 'sparkle 1.5s ease-in-out infinite',
                        'gradient-shift': 'gradientShift 3s ease-in-out infinite'
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
                        pulseGlow: {
                            '0%, 100%': { boxShadow: '0 0 20px rgba(239, 68, 68, 0.4)' },
                            '50%': { boxShadow: '0 0 40px rgba(239, 68, 68, 0.8)' }
                        },
                        bounceSlow: {
                            '0%, 100%': { transform: 'translateY(0px)' },
                            '50%': { transform: 'translateY(-10px)' }
                        },
                        sparkle: {
                            '0%, 100%': { opacity: '1', transform: 'scale(1)' },
                            '50%': { opacity: '0.5', transform: 'scale(1.1)' }
                        },
                        gradientShift: {
                            '0%': { backgroundPosition: '0% 50%' },
                            '50%': { backgroundPosition: '100% 50%' },
                            '100%': { backgroundPosition: '0% 50%' }
                        }
                    },
                    backdropBlur: {
                        xs: '2px'
                    }
                }
            }
        }
    </script>
    
    <style>
        /* Mobile-first optimizations */
        * {
            -webkit-tap-highlight-color: transparent;
            -webkit-touch-callout: none;
        }
        
        html {
            -webkit-overflow-scrolling: touch;
            scroll-behavior: smooth;
        }
        
        body {
            overscroll-behavior: none;
        }
        
        .glass-effect {
            background: rgba(255, 255, 255, 0.25);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.18);
        }
        
        .gradient-text {
            background: linear-gradient(135deg, #b08d80, #c4a575, #eab676);
            background-size: 200% 200%;
            animation: gradientShift 3s ease-in-out infinite;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .luxury-shadow {
            box-shadow: 0 25px 50px -12px rgba(176, 141, 128, 0.25);
        }
        
        .hover-lift {
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .hover-lift:hover {
            transform: translateY(-8px) scale(1.02);
            box-shadow: 0 35px 60px -12px rgba(176, 141, 128, 0.35);
        }
        
        /* Mobile hover effects */
        @media (max-width: 768px) {
            .hover-lift:hover {
                transform: translateY(-4px) scale(1.01);
            }
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
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.4), transparent);
            transition: left 0.5s;
        }
        
        .shimmer-effect:hover::before {
            left: 100%;
        }
        
        .text-shadow {
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        
        .bg-mesh {
            background-image: 
                radial-gradient(at 40% 20%, rgba(176, 141, 128, 0.1) 0px, transparent 50%),
                radial-gradient(at 80% 0%, rgba(196, 165, 117, 0.1) 0px, transparent 50%),
                radial-gradient(at 0% 50%, rgba(234, 182, 118, 0.1) 0px, transparent 50%);
        }
        
        .floating-element {
            position: absolute;
            border-radius: 50%;
            background: linear-gradient(135deg, rgba(176, 141, 128, 0.1), rgba(196, 165, 117, 0.1));
            animation: float 6s ease-in-out infinite;
            filter: blur(1px);
        }
        
        /* Hide floating elements on mobile */
        @media (max-width: 768px) {
            .floating-element {
                display: none;
            }
        }
        
        .floating-element:nth-child(1) { animation-delay: 0s; }
        .floating-element:nth-child(2) { animation-delay: -2s; }
        .floating-element:nth-child(3) { animation-delay: -4s; }
        
        .product-image-zoom {
            transition: transform 0.5s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .product-image-zoom:hover {
            transform: scale(1.1);
        }
        
        .product-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            transition: all 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }
        
        .product-card:hover {
            transform: translateY(-12px) scale(1.03);
            box-shadow: 0 30px 60px rgba(176, 141, 128, 0.25);
        }
        
        /* Mobile product card adjustments */
        @media (max-width: 768px) {
            .product-card:hover {
                transform: translateY(-6px) scale(1.02);
            }
        }
        
        .tab-button {
            position: relative;
            transition: all 0.3s ease;
        }
        
        .tab-button.active::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            right: 0;
            height: 2px;
            background: linear-gradient(90deg, #b08d80, #c4a575);
            border-radius: 1px;
        }
        
        .quantity-input {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            border: 2px solid rgba(176, 141, 128, 0.2);
            transition: all 0.3s ease;
        }
        
        .quantity-input:focus {
            border-color: #b08d80;
            box-shadow: 0 0 0 3px rgba(176, 141, 128, 0.1);
        }
        
        /* Touch-friendly buttons */
        .touch-button {
            min-height: 44px;
            min-width: 44px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        /* Custom scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
        }
        
        ::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 10px;
        }
        
        ::-webkit-scrollbar-thumb {
            background: linear-gradient(135deg, #b08d80, #c4a575);
            border-radius: 10px;
        }
        
        ::-webkit-scrollbar-thumb:hover {
            background: linear-gradient(135deg, #a67c76, #b39256);
        }
        
        /* Mobile header styles */
        .mobile-header {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-bottom: 1px solid rgba(0, 0, 0, 0.1);
        }
        
        /* Mobile drawer styles */
        .mobile-drawer {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(4px);
            z-index: 9999;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
        }
        
        .mobile-drawer.active {
            opacity: 1;
            visibility: visible;
        }
        
        .mobile-drawer-content {
            position: absolute;
            top: 0;
            left: 0;
            width: 85%;
            max-width: 400px;
            height: 100%;
            background: white;
            transform: translateX(-100%);
            transition: transform 0.3s ease;
            overflow-y: auto;
            -webkit-overflow-scrolling: touch;
        }
        
        .mobile-drawer.active .mobile-drawer-content {
            transform: translateX(0);
        }
        
        /* Prevent body scroll when modal is open */
        .modal-open {
            overflow: hidden;
            position: fixed;
            width: 100%;
        }
        
        /* Line clamp utility */
        .line-clamp-2 {
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
    </style>
</head>

<body class="font-sans bg-white overflow-x-hidden">
    <!-- Mobile Header -->
    <header class="md:hidden fixed top-0 left-0 right-0 z-50 mobile-header">
        <div class="px-4 py-3">
            <div class="flex items-center justify-between">
                <!-- Menu Button -->
                <button id="mobile-menu-btn" class="touch-button text-gray-700 hover:text-primary-500 transition-colors">
                    <i class="fas fa-bars text-xl"></i>
                </button>
                
                <!-- Logo -->
                <div class="flex items-center">
                    <a href="/" class="text-2xl font-serif font-bold gradient-text">
                        Odisea
                    </a>
                    <span class="ml-1 text-xs text-gray-500 font-light">MAKEUP</span>
                </div>
                
                <!-- Actions -->
                <div class="flex items-center space-x-2">
                    <!-- Search -->
                    <button id="mobile-search-btn" class="touch-button text-gray-700 hover:text-primary-500 transition-colors">
                        <i class="fas fa-search text-lg"></i>
                    </button>
                    
                    <!-- Cart -->
                    <a href="carrito.php" class="touch-button relative text-primary-500">
                        <i class="fas fa-shopping-bag text-lg"></i>
                        <span class="absolute -top-1 -right-1 bg-gradient-to-r from-primary-500 to-secondary-500 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center font-medium" id="mobile-cart-count">
                            <?php echo $cartCount; ?>
                        </span>
                    </a>
                </div>
            </div>
            
            <!-- Mobile Search Bar (Hidden by default) -->
            <div id="mobile-search-bar" class="mt-3 hidden">
                <div class="relative">
                    <input type="text" placeholder="Buscar productos..."
                           class="w-full px-4 py-3 pr-12 bg-gray-50 border border-gray-200 rounded-2xl focus:outline-none focus:ring-2 focus:ring-primary-500/50 focus:border-primary-500 transition-all duration-300">
                    <button class="absolute right-3 top-1/2 transform -translate-y-1/2 w-8 h-8 bg-gradient-to-r from-primary-500 to-secondary-500 rounded-xl flex items-center justify-center text-white">
                        <i class="fas fa-search text-sm"></i>
                    </button>
                </div>
            </div>
        </div>
    </header>
    
    <!-- Desktop Header -->
    <header class="hidden md:block fixed top-0 left-0 right-0 z-50 transition-all duration-500" id="header">
        <!-- Top Bar -->
        <div class="bg-gradient-to-r from-primary-500 to-secondary-500 text-white py-2 text-sm">
            <div class="container mx-auto px-4">
                <div class="flex justify-between items-center">
                    <div class="flex items-center space-x-6">
                        <a href="tel:+573001234567" class="flex items-center space-x-2 hover:text-primary-200 transition-colors">
                            <i class="fas fa-phone text-xs"></i>
                            <span>+57 300 123 4567</span>
                        </a>
                        <a href="mailto:info@odiseamakeup.com" class="flex items-center space-x-2 hover:text-primary-200 transition-colors">
                            <i class="fas fa-envelope text-xs"></i>
                            <span>info@odiseamakeup.com</span>
                        </a>
                    </div>
                    <div class="flex items-center space-x-4">
                        <span class="text-xs">Síguenos:</span>
                        <a href="#" class="hover:text-primary-200 transition-colors transform hover:scale-110" title="Instagram">
                            <i class="fab fa-instagram"></i>
                        </a>
                        <a href="#" class="hover:text-primary-200 transition-colors transform hover:scale-110" title="Facebook">
                            <i class="fab fa-facebook"></i>
                        </a>
                        <a href="#" class="hover:text-primary-200 transition-colors transform hover:scale-110" title="TikTok">
                            <i class="fab fa-tiktok"></i>
                        </a>
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
                        <span class="ml-2 text-xs text-gray-500 font-light">MAKEUP</span>
                    </div>
                    
                    <!-- Search Bar -->
                    <div class="hidden md:flex flex-1 max-w-xl mx-8">
                        <div class="relative w-full group">
                            <input type="text"
                                   placeholder="Buscar productos, marcas..."
                                   class="w-full px-6 py-4 pr-14 bg-white/80 backdrop-blur-sm border border-gray-200 rounded-2xl focus:outline-none focus:ring-2 focus:ring-primary-500/50 focus:border-primary-500 transition-all duration-300 text-gray-700 placeholder-gray-400">
                            <button class="absolute right-4 top-1/2 transform -translate-y-1/2 w-8 h-8 bg-gradient-to-r from-primary-500 to-secondary-500 rounded-xl flex items-center justify-center text-white hover:shadow-lg transition-all duration-300">
                                <i class="fas fa-search text-sm"></i>
                            </button>
                        </div>
                    </div>
                    
                    <!-- Header Actions -->
                    <div class="flex items-center space-x-6">
                        <!-- User Account -->
                        <div class="relative group">
                            <button class="flex items-center space-x-2 text-gray-700 hover:text-primary-500 transition-colors p-2 rounded-xl hover:bg-primary-50">
                                <div class="w-8 h-8 bg-gradient-to-r from-primary-500 to-secondary-500 rounded-full flex items-center justify-center text-white text-sm">
                                    <i class="fas fa-user"></i>
                                </div>
                                <span class="hidden lg:block font-medium">Mi Cuenta</span>
                            </button>
                            
                            <!-- Dropdown Menu -->
                            <div class="absolute right-0 mt-2 w-56 bg-white/95 backdrop-blur-md rounded-2xl shadow-2xl opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-300 z-50 border border-gray-100">
                                <div class="py-3">
                                    <?php if (isset($_SESSION['user_id'])): ?>
                                        <a href="mi-cuenta.php" class="flex items-center px-4 py-3 text-sm text-gray-700 hover:bg-primary-50 hover:text-primary-600 transition-colors">
                                            <i class="fas fa-user-circle mr-3 text-primary-500"></i>
                                            Mi Perfil
                                        </a>
                                        <a href="mis-pedidos.php" class="flex items-center px-4 py-3 text-sm text-gray-700 hover:bg-primary-50 hover:text-primary-600 transition-colors">
                                            <i class="fas fa-shopping-bag mr-3 text-primary-500"></i>
                                            Mis Pedidos
                                        </a>
                                        <a href="lista-deseos.php" class="flex items-center px-4 py-3 text-sm text-gray-700 hover:bg-primary-50 hover:text-primary-600 transition-colors">
                                            <i class="fas fa-heart mr-3 text-primary-500"></i>
                                            Lista de Deseos
                                        </a>
                                        <hr class="my-2 border-gray-100">
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
                        <a href="carrito.php" class="relative p-2 text-primary-500 rounded-xl bg-primary-50 group">
                            <i class="fas fa-shopping-bag text-xl group-hover:scale-110 transition-transform duration-300"></i>
                            <span class="absolute -top-1 -right-1 bg-gradient-to-r from-primary-500 to-secondary-500 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center font-medium shadow-lg transform group-hover:scale-110 transition-transform duration-300" id="cart-count">
                                <?php echo $cartCount; ?>
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
                        
                        <!-- Mega Menu -->
                        <div class="absolute left-0 top-full w-screen max-w-6xl bg-white/95 backdrop-blur-md shadow-2xl opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-300 z-40 rounded-2xl border border-gray-100 mt-2">
                            <div class="grid grid-cols-4 gap-8 p-8">
                                <?php
                                $categories_menu = [
                                    [
                                        'name' => 'Rostro',
                                        'icon' => 'fa-palette',
                                        'items' => ['Base de Maquillaje', 'Correctores', 'Rubor', 'Contorno', 'Iluminadores']
                                    ],
                                    [
                                        'name' => 'Ojos',
                                        'icon' => 'fa-eye',
                                        'items' => ['Sombras', 'Delineadores', 'Máscaras', 'Cejas', 'Pestañas Postizas']
                                    ],
                                    [
                                        'name' => 'Labios',
                                        'icon' => 'fa-kiss-wink-heart',
                                        'items' => ['Labiales', 'Gloss', 'Delineadores', 'Bálsamos', 'Tintes']
                                    ],
                                    [
                                        'name' => 'Cuidado',
                                        'icon' => 'fa-spa',
                                        'items' => ['Limpiadores', 'Hidratantes', 'Serums', 'Mascarillas', 'Protector Solar']
                                    ]
                                ];
                                
                                foreach ($categories_menu as $category):
                                ?>
                                <div class="group/item">
                                    <div class="flex items-center space-x-3 mb-4">
                                        <div class="w-10 h-10 bg-gradient-to-r from-primary-500 to-secondary-500 rounded-xl flex items-center justify-center text-white">
                                            <i class="fas <?php echo $category['icon']; ?>"></i>
                                        </div>
                                        <h3 class="font-semibold text-gray-900 text-lg"><?php echo $category['name']; ?></h3>
                                    </div>
                                    <ul class="space-y-3">
                                        <?php foreach ($category['items'] as $item): ?>
                                        <li>
                                            <a href="categoria.php?cat=<?php echo urlencode(strtolower($item)); ?>"
                                                class="text-gray-600 hover:text-primary-500 transition-colors text-sm hover:translate-x-1 transform duration-200 block">
                                                <?php echo $item; ?>
                                            </a>
                                        </li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Main Navigation -->
                    <div class="hidden md:flex items-center space-x-8">
                        <a href="index.php" class="text-gray-700 hover:text-primary-500 transition-colors font-medium py-4 border-b-2 border-transparent hover:border-primary-500">Inicio</a>
                        <a href="nuevos.php" class="text-gray-700 hover:text-primary-500 transition-colors font-medium py-4 border-b-2 border-transparent hover:border-primary-500">Novedades</a>
                        <a href="ofertas.php" class="text-gray-700 hover:text-primary-500 transition-colors font-medium py-4 border-b-2 border-transparent hover:border-primary-500">Ofertas</a>
                        <a href="marcas.php" class="text-gray-700 hover:text-primary-500 transition-colors font-medium py-4 border-b-2 border-transparent hover:border-primary-500">Marcas</a>
                        <a href="blog.php" class="text-gray-700 hover:text-primary-500 transition-colors font-medium py-4 border-b-2 border-transparent hover:border-primary-500">Blog</a>
                    </div>
                    
                    <!-- Secure Shopping Badge -->
                    <div class="hidden lg:block">
                        <div class="bg-gradient-to-r from-green-500 to-emerald-500 text-white px-6 py-2 rounded-full text-sm font-medium">
                            <i class="fas fa-shield-alt mr-2"></i>
                            Compra Segura
                        </div>
                    </div>
                </div>
            </div>
        </nav>
    </header>
    
    <!-- Mobile Menu Drawer -->
    <div id="mobile-menu-drawer" class="mobile-drawer md:hidden">
        <div class="mobile-drawer-content">
            <!-- Header -->
            <div class="bg-gradient-to-r from-primary-500 to-secondary-500 text-white p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-2xl font-serif font-bold">Odisea</h2>
                        <p class="text-sm opacity-90">Makeup Store</p>
                    </div>
                    <button id="close-mobile-menu" class="touch-button text-white hover:text-gray-200">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
            </div>
            
            <!-- Navigation -->
            <div class="p-6">
                <nav class="space-y-2">
                    <a href="index.php" class="flex items-center space-x-3 p-4 rounded-xl hover:bg-gray-50 transition-colors">
                        <i class="fas fa-home text-gray-400"></i>
                        <span class="font-medium text-gray-700">Inicio</span>
                    </a>
                    <a href="catalogo.php" class="flex items-center space-x-3 p-4 rounded-xl hover:bg-gray-50 transition-colors">
                        <i class="fas fa-th-large text-gray-400"></i>
                        <span class="font-medium text-gray-700">Catálogo</span>
                    </a>
                    <a href="ofertas.php" class="flex items-center space-x-3 p-4 rounded-xl hover:bg-gray-50 transition-colors">
                        <i class="fas fa-fire text-offer-500"></i>
                        <span class="font-medium text-gray-700">Ofertas</span>
                        <span class="ml-auto bg-offer-500 text-white text-xs px-2 py-1 rounded-full">HOT</span>
                    </a>
                    <a href="marcas.php" class="flex items-center space-x-3 p-4 rounded-xl hover:bg-gray-50 transition-colors">
                        <i class="fas fa-tags text-gray-400"></i>
                        <span class="font-medium text-gray-700">Marcas</span>
                    </a>
                    <a href="carrito.php" class="flex items-center space-x-3 p-4 rounded-xl hover:bg-gray-50 transition-colors">
                        <i class="fas fa-shopping-bag text-gray-400"></i>
                        <span class="font-medium text-gray-700">Mi Carrito</span>
                        <span class="ml-auto bg-primary-500 text-white text-xs px-2 py-1 rounded-full"><?php echo $cartCount; ?></span>
                    </a>
                </nav>
                
                <!-- Categories -->
                <div class="mt-8">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">Categorías</h3>
                    <div class="space-y-2">
                        <a href="#" class="flex items-center space-x-3 p-3 rounded-lg hover:bg-gray-50 transition-colors">
                            <i class="fas fa-palette text-primary-500"></i>
                            <span class="text-gray-700">Rostro</span>
                        </a>
                        <a href="#" class="flex items-center space-x-3 p-3 rounded-lg hover:bg-gray-50 transition-colors">
                            <i class="fas fa-eye text-primary-500"></i>
                            <span class="text-gray-700">Ojos</span>
                        </a>
                        <a href="#" class="flex items-center space-x-3 p-3 rounded-lg hover:bg-gray-50 transition-colors">
                            <i class="fas fa-kiss-wink-heart text-primary-500"></i>
                            <span class="text-gray-700">Labios</span>
                        </a>
                        <a href="#" class="flex items-center space-x-3 p-3 rounded-lg hover:bg-gray-50 transition-colors">
                            <i class="fas fa-spa text-primary-500"></i>
                            <span class="text-gray-700">Cuidado</span>
                        </a>
                    </div>
                </div>
                
                <!-- User Section -->
                <div class="mt-8 pt-6 border-t border-gray-200">
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <div class="space-y-2">
                            <a href="mi-cuenta.php" class="flex items-center space-x-3 p-3 rounded-lg hover:bg-gray-50 transition-colors">
                                <i class="fas fa-user-circle text-primary-500"></i>
                                <span class="text-gray-700">Mi Cuenta</span>
                            </a>
                            <a href="lista-deseos.php" class="flex items-center space-x-3 p-3 rounded-lg hover:bg-gray-50 transition-colors">
                                <i class="fas fa-heart text-red-500"></i>
                                <span class="text-gray-700">Lista de Deseos</span>
                            </a>
                            <a href="logout.php" class="flex items-center space-x-3 p-3 rounded-lg hover:bg-red-50 text-red-600 transition-colors">
                                <i class="fas fa-sign-out-alt"></i>
                                <span>Cerrar Sesión</span>
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="space-y-2">
                            <a href="login.php" class="flex items-center space-x-3 p-3 rounded-lg hover:bg-gray-50 transition-colors">
                                <i class="fas fa-sign-in-alt text-primary-500"></i>
                                <span class="text-gray-700">Iniciar Sesión</span>
                            </a>
                            <a href="register.php" class="flex items-center space-x-3 p-3 rounded-lg hover:bg-gray-50 transition-colors">
                                <i class="fas fa-user-plus text-primary-500"></i>
                                <span class="text-gray-700">Registrarse</span>
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Breadcrumb -->
    <section class="pt-20 md:pt-32 pb-6 bg-gradient-to-r from-primary-50/50 to-secondary-50/50 backdrop-blur-sm">
        <div class="container mx-auto px-4">
            <nav class="text-sm" data-aos="fade-right">
                <ol class="flex items-center space-x-3">
                    <li>
                        <a href="index.php" class="text-gray-600 hover:text-primary-500 transition-colors duration-300 flex items-center">
                            <i class="fas fa-home mr-2"></i>
                            Inicio
                        </a>
                    </li>
                    <li class="text-gray-400">
                        <i class="fas fa-chevron-right text-xs"></i>
                    </li>
                    <li>
                        <a href="catalogo.php" class="text-gray-600 hover:text-primary-500 transition-colors duration-300">Productos</a>
                    </li>
                    <?php if ($category): ?>
                    <li class="text-gray-400">
                        <i class="fas fa-chevron-right text-xs"></i>
                    </li>
                    <li>
                        <a href="categoria.php?id=<?php echo $product['category_id']; ?>" class="text-gray-600 hover:text-primary-500 transition-colors duration-300">
                            <?php echo htmlspecialchars($category['name']); ?>
                        </a>
                    </li>
                    <?php endif; ?>
                    <li class="text-gray-400">
                        <i class="fas fa-chevron-right text-xs"></i>
                    </li>
                    <li class="text-primary-600 font-medium flex items-center">
                        <i class="fas fa-star mr-2"></i>
                        <?php echo htmlspecialchars($product['name']); ?>
                    </li>
                </ol>
            </nav>
        </div>
    </section>
    
    <!-- Product Details -->
    <section class="py-8 md:py-20 relative overflow-hidden bg-mesh">
        <!-- Floating Elements (Desktop only) -->
        <div class="floating-element absolute top-20 left-10 w-64 h-64"></div>
        <div class="floating-element absolute bottom-20 right-10 w-80 h-80" style="animation-delay: -2s;"></div>
        <div class="floating-element absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 w-96 h-96" style="animation-delay: -4s;"></div>
        
        <div class="container mx-auto px-4 relative z-10">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 md:gap-16">
                <!-- Product Images -->
                <div class="space-y-6" data-aos="fade-right">
                    <!-- Main Image -->
                    <div class="aspect-square rounded-2xl md:rounded-3xl overflow-hidden luxury-shadow bg-white/95 backdrop-blur-sm border border-white/50">
                        <img src="<?php echo htmlspecialchars($productImage); ?>"
                             alt="<?php echo htmlspecialchars($product['name']); ?>"
                             class="w-full h-full object-cover product-image-zoom cursor-pointer"
                             id="main-product-image"
                             onclick="openImageLightbox('<?php echo htmlspecialchars($productImage); ?>')">
                    </div>
                    
                    <!-- Thumbnail Images -->
                    <div class="grid grid-cols-4 gap-3 md:gap-4">
                        <div class="aspect-square rounded-xl overflow-hidden cursor-pointer border-2 border-primary-500 bg-white/95 backdrop-blur-sm"
                             onclick="changeMainImage('<?php echo htmlspecialchars($productImage); ?>')">
                            <img src="<?php echo htmlspecialchars($productImage); ?>"
                                 alt="<?php echo htmlspecialchars($product['name']); ?>"
                                 class="w-full h-full object-cover hover:scale-110 transition-transform duration-300">
                        </div>
                        <?php foreach ($additionalImages as $image): ?>
                        <div class="aspect-square rounded-xl overflow-hidden cursor-pointer border-2 border-transparent hover:border-primary-300 transition-colors bg-white/95 backdrop-blur-sm"
                             onclick="changeMainImage('<?php echo htmlspecialchars($image['image_url']); ?>')">
                            <img src="<?php echo htmlspecialchars($image['image_url']); ?>"
                                 alt="<?php echo htmlspecialchars($product['name']); ?>"
                                 class="w-full h-full object-cover hover:scale-110 transition-transform duration-300">
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <!-- Product Information -->
                <div class="space-y-6 md:space-y-8" data-aos="fade-left">
                    <!-- Brand and Category -->
                    <div class="flex flex-wrap items-center gap-3">
                        <?php if ($brand): ?>
                        <span class="px-4 py-2 bg-gradient-to-r from-primary-100 to-secondary-100 text-primary-700 rounded-full text-sm font-semibold">
                            <?php echo htmlspecialchars($brand['name']); ?>
                        </span>
                        <?php endif; ?>
                        
                        <?php if ($category): ?>
                        <span class="px-4 py-2 bg-gray-100 text-gray-700 rounded-full text-sm font-medium">
                            <?php echo htmlspecialchars($category['name']); ?>
                        </span>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Title -->
                    <h1 class="text-3xl md:text-4xl lg:text-5xl font-serif font-bold text-gray-900 leading-tight text-shadow">
                        <?php echo htmlspecialchars($product['name']); ?>
                    </h1>
                    
                    <!-- Rating -->
                    <div class="flex flex-wrap items-center gap-4">
                        <div class="flex text-yellow-400 text-lg">
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                        </div>
                        <span class="text-gray-600">(4.9)</span>
                        <span class="text-gray-400">•</span>
                        <span class="text-gray-600">128 reseñas</span>
                    </div>
                    
                    <!-- Price -->
                    <div class="space-y-3">
                        <div class="flex flex-wrap items-center gap-4">
                            <span class="text-3xl md:text-4xl font-bold text-primary-600">
                                $<?php echo number_format($displayPrice, 0, ',', '.'); ?>
                            </span>
                            <?php if ($salePrice && $salePrice < $originalPrice): ?>
                            <span class="text-xl md:text-2xl line-through text-gray-400">
                                $<?php echo number_format($originalPrice, 0, ',', '.'); ?>
                            </span>
                            <?php endif; ?>
                        </div>
                        
                        <?php if ($discount > 0): ?>
                        <div class="flex flex-wrap items-center gap-3">
                            <span class="bg-gradient-to-r from-offer-500 to-orange-500 text-white px-3 py-1 rounded-full text-sm font-semibold animate-pulse-glow">
                                -<?php echo $discount; ?>% OFF
                            </span>
                            <span class="text-green-600 font-semibold">
                                ¡Ahorras $<?php echo number_format($originalPrice - $displayPrice, 0, ',', '.'); ?>!
                            </span>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Short Description -->
                    <?php if ($product['short_description']): ?>
                    <p class="text-lg md:text-xl text-gray-600 leading-relaxed font-light">
                        <?php echo htmlspecialchars($product['short_description']); ?>
                    </p>
                    <?php endif; ?>
                    
                    <!-- Stock Status -->
                    <div class="flex items-center space-x-3">
                        <?php if ($product['inventory_quantity'] > 0): ?>
                        <div class="flex items-center space-x-2 text-green-600">
                            <i class="fas fa-check-circle"></i>
                            <span class="font-semibold">En Stock</span>
                        </div>
                        <span class="text-gray-400">•</span>
                        <span class="text-gray-600"><?php echo $product['inventory_quantity']; ?> disponibles</span>
                        <?php else: ?>
                        <div class="flex items-center space-x-2 text-red-600">
                            <i class="fas fa-times-circle"></i>
                            <span class="font-semibold">Agotado</span>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Action Buttons -->
                    <div class="space-y-6">
                        <!-- Quantity Selector -->
                        <div class="flex items-center space-x-4">
                            <span class="text-gray-700 font-medium">Cantidad:</span>
                            <div class="flex items-center border-2 border-gray-200 rounded-xl overflow-hidden bg-white/90 backdrop-blur-sm">
                                <button class="touch-button w-12 h-12 bg-gray-50 hover:bg-primary-100 flex items-center justify-center transition-colors" onclick="decreaseQuantity()">
                                    <i class="fas fa-minus text-gray-600"></i>
                                </button>
                                <input type="number" id="product-quantity" value="1" min="1" max="<?php echo $product['inventory_quantity']; ?>"
                                       class="quantity-input w-16 h-12 text-center border-none focus:outline-none font-semibold bg-transparent">
                                <button class="touch-button w-12 h-12 bg-gray-50 hover:bg-primary-100 flex items-center justify-center transition-colors" onclick="increaseQuantity()">
                                    <i class="fas fa-plus text-gray-600"></i>
                                </button>
                            </div>
                        </div>
                        
                        <!-- Add to Cart & Wishlist -->
                        <div class="flex flex-col sm:flex-row gap-4">
                            <button class="flex-1 touch-button py-4 md:py-5 bg-gradient-to-r from-primary-500 to-secondary-500 text-white rounded-xl md:rounded-2xl font-semibold text-lg hover:shadow-xl transform hover:scale-105 transition-all duration-300 shimmer-effect"
                                    onclick="addToCart(<?php echo $product['id']; ?>, document.getElementById('product-quantity').value)"
                                    <?php echo $product['inventory_quantity'] <= 0 ? 'disabled' : ''; ?>>
                                <i class="fas fa-shopping-bag mr-3"></i>
                                <?php echo $product['inventory_quantity'] > 0 ? 'Agregar al Carrito' : 'Agotado'; ?>
                            </button>
                            
                            <button class="touch-button w-full sm:w-16 h-16 bg-white/90 backdrop-blur-sm border-2 border-gray-200 hover:bg-primary-500 hover:border-primary-500 hover:text-white rounded-xl md:rounded-2xl flex items-center justify-center transition-all duration-300 transform hover:scale-105 luxury-shadow"
                                    onclick="toggleWishlist(<?php echo $product['id']; ?>)"
                                    id="wishlist-btn">
                                <i class="<?php echo $inWishlist ? 'fas text-red-500' : 'far'; ?> fa-heart text-xl"></i>
                                <span class="ml-2 sm:hidden font-medium">
                                    <?php echo $inWishlist ? 'En Favoritos' : 'Agregar a Favoritos'; ?>
                                </span>
                            </button>
                        </div>
                    </div>
                    
                    <!-- Product Information Tabs -->
                    <div class="border-t pt-6 md:pt-8">
                        <div class="flex flex-wrap gap-2 md:gap-8 mb-6 border-b overflow-x-auto">
                            <button class="tab-button py-3 px-1 whitespace-nowrap border-b-2 border-primary-500 text-primary-600 font-semibold active" data-tab="description">
                                Descripción
                            </button>
                            <?php if ($product['ingredients']): ?>
                            <button class="tab-button py-3 px-1 whitespace-nowrap border-b-2 border-transparent text-gray-500 hover:text-gray-700 font-semibold" data-tab="ingredients">
                                Ingredientes
                            </button>
                            <?php endif; ?>
                            <?php if ($product['how_to_use']): ?>
                            <button class="tab-button py-3 px-1 whitespace-nowrap border-b-2 border-transparent text-gray-500 hover:text-gray-700 font-semibold" data-tab="usage">
                                Modo de Uso
                            </button>
                            <?php endif; ?>
                            <button class="tab-button py-3 px-1 whitespace-nowrap border-b-2 border-transparent text-gray-500 hover:text-gray-700 font-semibold" data-tab="shipping">
                                Envío
                            </button>
                        </div>
                        
                        <!-- Tab Content -->
                        <div class="tab-content active" id="description">
                            <div class="prose prose-lg max-w-none text-gray-600 leading-relaxed">
                                <?php echo $product['description'] ? nl2br(htmlspecialchars($product['description'])) : 'Descripción no disponible.'; ?>
                            </div>
                        </div>
                        
                        <?php if ($product['ingredients']): ?>
                        <div class="tab-content hidden" id="ingredients">
                            <div class="prose prose-lg max-w-none text-gray-600 leading-relaxed">
                                <?php echo nl2br(htmlspecialchars($product['ingredients'])); ?>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($product['how_to_use']): ?>
                        <div class="tab-content hidden" id="usage">
                            <div class="prose prose-lg max-w-none text-gray-600 leading-relaxed">
                                <?php echo nl2br(htmlspecialchars($product['how_to_use'])); ?>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <div class="tab-content hidden" id="shipping">
                            <div class="prose prose-lg max-w-none text-gray-600 leading-relaxed">
                                <h4 class="font-semibold text-gray-900 mb-4 text-xl">Información de Envío</h4>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div class="space-y-3">
                                        <div class="flex items-center space-x-3">
                                            <i class="fas fa-shipping-fast text-green-500"></i>
                                            <span>Envío gratis en compras superiores a $150.000</span>
                                        </div>
                                        <div class="flex items-center space-x-3">
                                            <i class="fas fa-clock text-blue-500"></i>
                                            <span>Entrega de 2-5 días hábiles</span>
                                        </div>
                                    </div>
                                    <div class="space-y-3">
                                        <div class="flex items-center space-x-3">
                                            <i class="fas fa-box text-purple-500"></i>
                                            <span>Empaque discreto y seguro</span>
                                        </div>
                                        <div class="flex items-center space-x-3">
                                            <i class="fas fa-map-marker-alt text-orange-500"></i>
                                            <span>Seguimiento en tiempo real</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Product Reviews Section -->
    <section class="py-16 md:py-24 bg-white">
        <div class="container mx-auto px-4">
            <div class="max-w-4xl mx-auto">
                <!-- Reviews Header -->
                <div class="text-center mb-12" data-aos="fade-up">
                    <h2 class="text-3xl md:text-4xl font-serif font-bold text-gray-900 mb-4">
                        Reseñas y <span class="gradient-text">Calificaciones</span>
                    </h2>
                    
                    <?php if ($ratingData['count'] > 0): ?>
                    <div class="flex items-center justify-center gap-4 mb-6">
                        <div class="flex items-center gap-2">
                            <div class="flex">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <svg class="w-6 h-6 <?= $i <= round($ratingData['average']) ? 'text-yellow-400' : 'text-gray-300' ?>" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                    </svg>
                                <?php endfor; ?>
                            </div>
                            <span class="text-xl font-semibold text-gray-900"><?= $ratingData['average'] ?></span>
                            <span class="text-gray-600">(<?= $ratingData['count'] ?> reseñas)</span>
                        </div>
                    </div>
                    
                    <!-- Rating Distribution -->
                    <div class="max-w-md mx-auto space-y-2">
                        <?php for ($i = 5; $i >= 1; $i--): ?>
                        <div class="flex items-center gap-3">
                            <span class="text-sm font-medium text-gray-700 w-8"><?= $i ?> ★</span>
                            <div class="flex-1 bg-gray-200 rounded-full h-2">
                                <?php 
                                $percentage = $ratingData['count'] > 0 ? ($ratingDistribution[$i] / $ratingData['count']) * 100 : 0;
                                ?>
                                <div class="bg-yellow-400 h-2 rounded-full" style="width: <?= $percentage ?>%"></div>
                            </div>
                            <span class="text-sm text-gray-600 w-8"><?= $ratingDistribution[$i] ?></span>
                        </div>
                        <?php endfor; ?>
                    </div>
                    <?php else: ?>
                    <p class="text-gray-600">Aún no hay reseñas para este producto.</p>
                    <?php endif; ?>
                </div>
                
                <!-- Write Review Form -->
                <?php if ($canReview): ?>
                <div class="bg-gray-50 rounded-2xl p-6 md:p-8 mb-12" data-aos="fade-up">
                    <h3 class="text-xl font-semibold text-gray-900 mb-6">Escribir una reseña</h3>
                    <form id="reviewForm" class="space-y-6">
                        <input type="hidden" name="product_id" value="<?= $product_id ?>">
                        
                        <!-- Rating Stars -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Calificación</label>
                            <div class="flex gap-1">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                <button type="button" class="star-rating w-8 h-8 text-gray-300 hover:text-yellow-400 transition-colors" data-rating="<?= $i ?>">
                                    <svg fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                    </svg>
                                </button>
                                <?php endfor; ?>
                            </div>
                            <input type="hidden" name="rating" id="ratingInput" required>
                        </div>
                        
                        <!-- Title -->
                        <div>
                            <label for="title" class="block text-sm font-medium text-gray-700 mb-2">Título de la reseña</label>
                            <input type="text" id="title" name="title" required 
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                                   placeholder="Ej: Excelente producto, muy recomendado">
                        </div>
                        
                        <!-- Comment -->
                        <div>
                            <label for="comment" class="block text-sm font-medium text-gray-700 mb-2">Tu reseña</label>
                            <textarea id="comment" name="comment" rows="4" required
                                      class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                                      placeholder="Comparte tu experiencia con este producto..."></textarea>
                        </div>
                        
                        <!-- Submit Button -->
                        <button type="submit" 
                                class="w-full bg-gradient-to-r from-primary-600 to-secondary-600 text-white font-semibold py-3 px-6 rounded-lg hover:from-primary-700 hover:to-secondary-700 transition-all duration-300 transform hover:scale-105">
                            Enviar Reseña
                        </button>
                    </form>
                </div>
                <?php elseif (isset($_SESSION['user_id'])): ?>
                <div class="bg-gray-50 rounded-2xl p-6 md:p-8 mb-12 text-center" data-aos="fade-up">
                    <p class="text-gray-600">Debes haber comprado este producto para poder escribir una reseña.</p>
                </div>
                <?php else: ?>
                <div class="bg-gray-50 rounded-2xl p-6 md:p-8 mb-12 text-center" data-aos="fade-up">
                    <p class="text-gray-600">
                        <a href="login.php" class="text-primary-600 hover:text-primary-700 font-medium">Inicia sesión</a> 
                        para escribir una reseña.
                    </p>
                </div>
                <?php endif; ?>
                
                <!-- Reviews List -->
                <?php if (!empty($reviews)): ?>
                <div class="space-y-6" data-aos="fade-up">
                    <h3 class="text-xl font-semibold text-gray-900">Reseñas de clientes</h3>
                    
                    <?php foreach ($reviews as $review): ?>
                    <div class="bg-white border border-gray-200 rounded-xl p-6 shadow-sm">
                        <div class="flex items-start justify-between mb-4">
                            <div>
                                <div class="flex items-center gap-2 mb-2">
                                    <div class="flex">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <svg class="w-4 h-4 <?= $i <= $review['rating'] ? 'text-yellow-400' : 'text-gray-300' ?>" fill="currentColor" viewBox="0 0 20 20">
                                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                            </svg>
                                        <?php endfor; ?>
                                    </div>
                                    <?php if ($review['is_verified']): ?>
                                    <span class="bg-green-100 text-green-800 text-xs font-medium px-2 py-1 rounded-full">Compra verificada</span>
                                    <?php endif; ?>
                                </div>
                                <h4 class="font-semibold text-gray-900"><?= htmlspecialchars($review['title']) ?></h4>
                                <p class="text-sm text-gray-600">
                                    Por <?= htmlspecialchars($review['first_name'] . ' ' . ($review['last_name'] ? substr($review['last_name'], 0, 1) . '.' : '')) ?>
                                    • <?= date('d/m/Y', strtotime($review['created_at'])) ?>
                                </p>
                            </div>
                        </div>
                        
                        <p class="text-gray-700 mb-4"><?= nl2br(htmlspecialchars($review['comment'])) ?></p>
                        
                        <?php if ($review['helpful_count'] > 0): ?>
                        <div class="flex items-center gap-2 text-sm text-gray-600">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 10h4.764a2 2 0 011.789 2.894l-3.5 7A2 2 0 0115.263 21h-4.017c-.163 0-.326-.02-.485-.06L7 20m7-10V5a2 2 0 00-2-2h-.095c-.5 0-.905.405-.905.905 0 .714-.211 1.412-.608 2.006L9 9.5v10.5m7-10h-2m0 0h-2m2 0v6a2 2 0 01-2 2h-2m2-2v-2a2 2 0 00-2-2H9.5m2 0H9.5"/>
                            </svg>
                            <span><?= $review['helpful_count'] ?> personas encontraron esto útil</span>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </section>
    
    <!-- Related Products -->
    <?php if (!empty($relatedProducts)): ?>
    <section class="py-16 md:py-24 bg-gradient-to-r from-primary-50/30 to-secondary-50/30">
        <div class="container mx-auto px-4">
            <div class="text-center mb-12 md:mb-16" data-aos="fade-up">
                <h2 class="text-3xl md:text-5xl font-serif font-bold text-gray-900 mb-6">
                    Productos <span class="gradient-text">Relacionados</span>
                </h2>
                <p class="text-lg md:text-xl text-gray-600 max-w-3xl mx-auto font-light">
                    Descubre otros productos que complementan perfectamente tu rutina de belleza
                </p>
            </div>
            
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 md:gap-8">
                <?php foreach ($relatedProducts as $index => $relatedProduct): ?>
                <div class="product-card rounded-2xl md:rounded-3xl overflow-hidden luxury-shadow hover-lift" data-aos="fade-up" data-aos-delay="<?php echo $index * 100; ?>">
                    <div class="relative aspect-square overflow-hidden">
                        <img src="<?php echo htmlspecialchars($relatedProduct['processed_image']); ?>"
                             alt="<?php echo htmlspecialchars($relatedProduct['name']); ?>"
                             class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500"
                             onerror="this.src='/placeholder.svg?height=300&width=300&text=Producto'">
                        
                        <!-- Quick Actions -->
                        <div class="absolute top-4 right-4 space-y-2 opacity-0 group-hover:opacity-100 transition-all duration-300">
                            <button class="w-10 h-10 bg-white/90 backdrop-blur-sm rounded-full flex items-center justify-center shadow-lg hover:bg-primary-500 hover:text-white transition-all duration-300 touch-button"
                                    onclick="toggleWishlist(<?php echo $relatedProduct['id']; ?>)">
                                <i class="far fa-heart text-sm"></i>
                            </button>
                            <button class="w-10 h-10 bg-white/90 backdrop-blur-sm rounded-full flex items-center justify-center shadow-lg hover:bg-primary-500 hover:text-white transition-all duration-300 touch-button"
                                    onclick="window.location.href='product.php?id=<?php echo $relatedProduct['id']; ?>'">
                                <i class="fas fa-eye text-sm"></i>
                            </button>
                        </div>
                        
                        <!-- Discount Badge -->
                        <?php 
                        $relatedDiscount = 0;
                        if (!empty($relatedProduct['compare_price']) && $relatedProduct['compare_price'] > $relatedProduct['price']) {
                            $relatedDiscount = round((($relatedProduct['compare_price'] - $relatedProduct['price']) / $relatedProduct['compare_price']) * 100);
                        }
                        if ($relatedDiscount > 0): ?>
                        <div class="absolute top-4 left-4 bg-gradient-to-r from-offer-500 to-orange-500 text-white px-3 py-1 rounded-full text-xs font-semibold">
                            -<?php echo $relatedDiscount; ?>%
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="p-4 md:p-6">
                        <h3 class="font-semibold text-lg mb-3 line-clamp-2 text-gray-800">
                            <?php echo htmlspecialchars($relatedProduct['name']); ?>
                        </h3>
                        
                        <div class="flex items-center justify-between mb-4">
                            <div class="flex items-center space-x-2">
                                <span class="text-xl font-bold text-primary-600">
                                    $<?php echo number_format($relatedProduct['price'], 0, ',', '.'); ?>
                                </span>
                                <?php if (!empty($relatedProduct['compare_price']) && $relatedProduct['compare_price'] > $relatedProduct['price']): ?>
                                <span class="text-sm line-through text-gray-400">
                                    $<?php echo number_format($relatedProduct['compare_price'], 0, ',', '.'); ?>
                                </span>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <button class="w-full touch-button py-3 bg-gradient-to-r from-primary-500 to-secondary-500 text-white rounded-xl font-semibold hover:shadow-lg transition-all duration-300 transform hover:scale-105"
                                onclick="addToCart(<?php echo $relatedProduct['id']; ?>)">
                            <i class="fas fa-shopping-bag mr-2"></i>
                            Agregar al Carrito
                        </button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>
    
    <!-- Trust Badges -->
    <section class="py-12 md:py-20 bg-white">
        <div class="container mx-auto px-4">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8 md:gap-12">
                <div class="text-center" data-aos="fade-up">
                    <div class="w-16 h-16 bg-gradient-to-r from-green-500 to-emerald-500 rounded-2xl flex items-center justify-center mx-auto mb-6">
                        <i class="fas fa-shipping-fast text-white text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 mb-4">Envío Gratis</h3>
                    <p class="text-gray-600">En compras superiores a $150.000. Recibe tus productos en 2-3 días hábiles.</p>
                </div>
                <div class="text-center" data-aos="fade-up" data-aos-delay="100">
                    <div class="w-16 h-16 bg-gradient-to-r from-blue-500 to-indigo-500 rounded-2xl flex items-center justify-center mx-auto mb-6">
                        <i class="fas fa-shield-alt text-white text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 mb-4">Compra Segura</h3>
                    <p class="text-gray-600">Tus datos están protegidos con encriptación SSL de última generación.</p>
                </div>
                <div class="text-center" data-aos="fade-up" data-aos-delay="200">
                    <div class="w-16 h-16 bg-gradient-to-r from-purple-500 to-pink-500 rounded-2xl flex items-center justify-center mx-auto mb-6">
                        <i class="fas fa-undo text-white text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 mb-4">Devoluciones</h3>
                    <p class="text-gray-600">30 días para devolver productos sin usar. Proceso simple y rápido.</p>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Image Lightbox -->
    <div id="imageLightbox" class="fixed inset-0 z-50 bg-black/90 backdrop-blur-sm hidden opacity-0 transition-opacity duration-300">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="relative max-w-4xl max-h-[90vh]">
                <img id="lightboxImage" src="/placeholder.svg" alt="" class="max-w-full max-h-full object-contain rounded-2xl">
                <button onclick="closeLightbox()" class="absolute top-4 right-4 w-12 h-12 bg-white/10 backdrop-blur-sm rounded-full flex items-center justify-center text-white hover:bg-white/20 transition-colors touch-button">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
        </div>
    </div>
    
    <!-- Footer -->
    <?php include 'includes/footer.php'; ?>
    
    <!-- JavaScript -->
    <script>
        AOS.init({
            duration: 1000,
            easing: 'ease-out-cubic',
            once: true,
            offset: 100
        });

        // Mobile menu functionality
        const mobileMenuBtn = document.getElementById('mobile-menu-btn');
        const mobileMenuDrawer = document.getElementById('mobile-menu-drawer');
        const closeMobileMenu = document.getElementById('close-mobile-menu');
        
        mobileMenuBtn?.addEventListener('click', () => {
            mobileMenuDrawer.classList.add('active');
            document.body.classList.add('modal-open');
        });
        
        closeMobileMenu?.addEventListener('click', () => {
            mobileMenuDrawer.classList.remove('active');
            document.body.classList.remove('modal-open');
        });
        
        mobileMenuDrawer?.addEventListener('click', (e) => {
            if (e.target === mobileMenuDrawer) {
                mobileMenuDrawer.classList.remove('active');
                document.body.classList.remove('modal-open');
            }
        });

        // Mobile search functionality
        const mobileSearchBtn = document.getElementById('mobile-search-btn');
        const mobileSearchBar = document.getElementById('mobile-search-bar');
        
        mobileSearchBtn?.addEventListener('click', () => {
            mobileSearchBar.classList.toggle('hidden');
            if (!mobileSearchBar.classList.contains('hidden')) {
                mobileSearchBar.querySelector('input').focus();
            }
        });

        // Tab functionality
        document.querySelectorAll('.tab-button').forEach(btn => {
            btn.addEventListener('click', function() {
                const tabId = this.getAttribute('data-tab');
                
                // Remove active class from all tabs and contents
                document.querySelectorAll('.tab-button').forEach(b => {
                    b.classList.remove('active', 'border-primary-500', 'text-primary-600');
                    b.classList.add('border-transparent', 'text-gray-500');
                });
                
                document.querySelectorAll('.tab-content').forEach(content => {
                    content.classList.remove('active');
                    content.classList.add('hidden');
                });
                
                // Add active class to clicked tab and corresponding content
                this.classList.add('active', 'border-primary-500', 'text-primary-600');
                this.classList.remove('border-transparent', 'text-gray-500');
                
                const content = document.getElementById(tabId);
                content.classList.add('active');
                content.classList.remove('hidden');
            });
        });
        
        // Quantity controls
        function increaseQuantity() {
            const input = document.getElementById('product-quantity');
            const max = parseInt(input.getAttribute('max'));
            const current = parseInt(input.value);
            if (current < max) {
                input.value = current + 1;
            }
        }
        
        function decreaseQuantity() {
            const input = document.getElementById('product-quantity');
            const current = parseInt(input.value);
            if (current > 1) {
                input.value = current - 1;
            }
        }
        
        // Change main image
        function changeMainImage(imageSrc) {
            document.getElementById('main-product-image').src = imageSrc;
            
            // Update thumbnail borders
            document.querySelectorAll('.aspect-square.cursor-pointer').forEach(thumb => {
                thumb.classList.remove('border-primary-500');
                thumb.classList.add('border-transparent');
            });
            
            // Add border to clicked thumbnail
            event.target.closest('.aspect-square').classList.add('border-primary-500');
            event.target.closest('.aspect-square').classList.remove('border-transparent');
        }
        
        // Image lightbox
        function openImageLightbox(imageSrc) {
            const lightbox = document.getElementById('imageLightbox');
            const lightboxImage = document.getElementById('lightboxImage');
            
            lightboxImage.src = imageSrc;
            lightbox.classList.remove('hidden');
            
            setTimeout(() => {
                lightbox.classList.add('opacity-100');
                lightbox.classList.remove('opacity-0');
            }, 10);
            
            document.body.style.overflow = 'hidden';
        }
        
        function closeLightbox() {
            const lightbox = document.getElementById('imageLightbox');
            
            lightbox.classList.add('opacity-0');
            lightbox.classList.remove('opacity-100');
            
            setTimeout(() => {
                lightbox.classList.add('hidden');
                document.body.style.overflow = '';
            }, 300);
        }
        
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
                    showNotification('Producto agregado al carrito ✨', 'success');
                    updateCartCount();
                } else {
                    showNotification(data.message || 'Error al agregar producto', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('Error al agregar producto', 'error');
            });
        }
        
        // Toggle wishlist function
        function toggleWishlist(productId) {
            fetch('wishlist-toggle.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `product_id=${productId}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const wishlistBtn = document.getElementById('wishlist-btn');
                    const heartIcon = wishlistBtn.querySelector('i');
                    const wishlistText = wishlistBtn.querySelector('span');
                    
                    if (data.action === 'added') {
                        heartIcon.classList.remove('far');
                        heartIcon.classList.add('fas', 'text-red-500');
                        if (wishlistText) wishlistText.textContent = 'En Favoritos';
                        showNotification('Agregado a favoritos ❤️', 'success');
                    } else {
                        heartIcon.classList.remove('fas', 'text-red-500');
                        heartIcon.classList.add('far');
                        if (wishlistText) wishlistText.textContent = 'Agregar a Favoritos';
                        showNotification('Removido de favoritos', 'info');
                    }
                } else {
                    if (data.message && data.message.includes('login')) {
                        showNotification('Debes iniciar sesión para agregar a favoritos', 'warning');
                        setTimeout(() => {
                            window.location.href = 'login.php';
                        }, 2000);
                    } else {
                        showNotification(data.message || 'Error al procesar favoritos', 'error');
                    }
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('Error al procesar favoritos', 'error');
            });
        }
        
        // Update cart count
        function updateCartCount() {
            fetch('cart-count.php')
            .then(response => response.json())
            .then(data => {
                const cartBadges = document.querySelectorAll('#cart-count, #mobile-cart-count');
                cartBadges.forEach(badge => {
                    if (badge) {
                        badge.textContent = data.count || 0;
                    }
                });
            })
            .catch(error => {
                console.error('Error updating cart count:', error);
            });
        }
        
        // Show notification
        function showNotification(message, type = 'info') {
            const notification = document.createElement('div');
            const colors = {
                success: 'from-green-500 to-emerald-500',
                error: 'from-red-500 to-rose-500',
                warning: 'from-yellow-500 to-orange-500',
                info: 'from-blue-500 to-indigo-500'
            };
            
            notification.className = `fixed top-20 md:top-8 right-4 md:right-8 z-50 p-4 md:p-6 rounded-xl md:rounded-2xl shadow-2xl text-white max-w-sm transition-all duration-500 transform translate-x-full bg-gradient-to-r ${colors[type] || colors.info}`;
            notification.innerHTML = `
                <div class="flex items-center">
                    <span class="mr-2 md:mr-3 text-lg md:text-2xl">
                        ${type === 'success' ? '✓' : type === 'error' ? '✗' : type === 'warning' ? '⚠' : 'ℹ'}
                    </span>
                    <span class="font-medium text-sm md:text-base">${message}</span>
                    <button onclick="this.parentElement.parentElement.remove()" class="ml-3 md:ml-4 text-white hover:text-gray-200 text-lg md:text-xl touch-button">×</button>
                </div>
            `;
            
            document.body.appendChild(notification);
            
            setTimeout(() => {
                notification.classList.remove('translate-x-full');
            }, 100);
            
            setTimeout(() => {
                if (notification.parentElement) {
                    notification.classList.add('translate-x-full');
                    setTimeout(() => {
                        notification.remove();
                    }, 500);
                }
            }, 4000);
        }
        
        // Close lightbox on escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeLightbox();
            }
        });
        
        // Close lightbox when clicking outside
        document.getElementById('imageLightbox').addEventListener('click', function(e) {
            if (e.target === this) {
                closeLightbox();
            }
        });
        
        // Update cart count on page load
        document.addEventListener('DOMContentLoaded', function() {
            updateCartCount();
        });

        // Smooth scroll for internal links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });

        // Prevent zoom on double tap for iOS
        let lastTouchEnd = 0;
        document.addEventListener('touchend', function (event) {
            const now = (new Date()).getTime();
            if (now - lastTouchEnd <= 300) {
                event.preventDefault();
            }
            lastTouchEnd = now;
        }, false);

        // Handle orientation change
        window.addEventListener('orientationchange', function() {
            setTimeout(() => {
                window.scrollTo(0, window.scrollY);
            }, 100);
        });
        
        // Review Rating System
        document.addEventListener('DOMContentLoaded', function() {
            const starButtons = document.querySelectorAll('.star-rating');
            const ratingInput = document.getElementById('ratingInput');
            let selectedRating = 0;
            
            starButtons.forEach((star, index) => {
                star.addEventListener('click', function() {
                    selectedRating = parseInt(this.dataset.rating);
                    ratingInput.value = selectedRating;
                    updateStars();
                });
                
                star.addEventListener('mouseover', function() {
                    const hoverRating = parseInt(this.dataset.rating);
                    highlightStars(hoverRating);
                });
            });
            
            document.querySelector('.flex.gap-1').addEventListener('mouseleave', function() {
                updateStars();
            });
            
            function updateStars() {
                starButtons.forEach((star, index) => {
                    const rating = index + 1;
                    if (rating <= selectedRating) {
                        star.classList.remove('text-gray-300');
                        star.classList.add('text-yellow-400');
                    } else {
                        star.classList.remove('text-yellow-400');
                        star.classList.add('text-gray-300');
                    }
                });
            }
            
            function highlightStars(rating) {
                starButtons.forEach((star, index) => {
                    const starRating = index + 1;
                    if (starRating <= rating) {
                        star.classList.remove('text-gray-300');
                        star.classList.add('text-yellow-400');
                    } else {
                        star.classList.remove('text-yellow-400');
                        star.classList.add('text-gray-300');
                    }
                });
            }
            
            // Handle review form submission
            const reviewForm = document.getElementById('reviewForm');
            if (reviewForm) {
                reviewForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    
                    if (selectedRating === 0) {
                        alert('Por favor selecciona una calificación');
                        return;
                    }
                    
                    const formData = new FormData(this);
                    
                    fetch('api/reviews/create.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            alert('Reseña enviada exitosamente. Será revisada antes de publicarse.');
                            location.reload();
                        } else {
                            alert('Error: ' + (data.message || 'No se pudo enviar la reseña'));
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('Error al enviar la reseña');
                    });
                });
            }
        });
    </script>
</body>
</html>
