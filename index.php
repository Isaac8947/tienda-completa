<?php
// Configuración de sesión segura (debe ir antes de session_start)
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 0); // Cambiar a 1 en HTTPS
ini_set('session.cookie_samesite', 'Strict');

// Iniciar sesión para manejar el carrito y la autenticación de usuarios
session_start();

// Incluir archivos de configuración y modelos necesarios
require_once 'config/config.php';
require_once 'config/database.php';
require_once 'models/Product.php';
require_once 'models/Category.php';
require_once 'models/Banner.php';
require_once 'models/Brand.php';
require_once 'models/Settings.php';
require_once 'models/News.php';
require_once 'models/Review.php';
require_once 'includes/CSRFProtection.php';

// Inicializar modelos y obtener datos dinámicos
$productModel = new Product();
$categoryModel = new Category();
$bannerModel = new Banner();
$settingsModel = new Settings();
$newsModel = new News();

// Inicializar database connection para Review
$database = new Database();
$db = $database->getConnection();
$reviewModel = new Review($db);

// Obtener configuraciones de contacto y redes sociales
$contactSettings = $settingsModel->getContactSettings();

// Obtener banners activos para la página principal
$heroBanners = $bannerModel->getActiveBanners('hero', 5);

// Obtener noticias destacadas para el banner flotante
$featuredNews = $newsModel->getPublished(1, true); // Solo 1 noticia destacada

// Obtener categorías activas para el menú móvil
$dbCategories = $categoryModel->getAll(['is_active' => 1]);

// Aquí podrías cargar datos dinámicos desde la base de datos
// Por ejemplo, productos destacados, categorías, etc.
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, user-scalable=yes, viewport-fit=cover">
    <title>OdiseaStore - Tu destino para todo lo que necesitas</title>
    <meta name="description" content="Descubre la mejor selección de productos en OdiseaStore. Tecnología, hogar, moda, belleza y mucho más. Envíos a toda Colombia y la mejor experiencia de compra.">

    <?php
    // Generar CSRF token para el carrito
    $csrf = new CSRFProtection();
    $cartCsrfToken = $csrf->generateToken('cart');
    ?>
    <meta name="cart-csrf-token" content="<?php echo htmlspecialchars($cartCsrfToken); ?>">

    <!-- DNS Prefetch para mejorar rendimiento -->
    <link rel="dns-prefetch" href="//fonts.googleapis.com">
    <link rel="dns-prefetch" href="//cdnjs.cloudflare.com">
    <link rel="dns-prefetch" href="//cdn.tailwindcss.com">
    <link rel="dns-prefetch" href="//unpkg.com">

    <?php
    // Preload critical hero banner image (only the first one that shows immediately)
    if (!empty($heroBanners) && isset($heroBanners[0]['image'])):
    ?>
    <link rel="preload" href="<?php echo BASE_URL . '/' . $heroBanners[0]['image']; ?>" as="image">
    <?php endif; ?>

    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=Playfair+Display:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">

    <!-- Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- AOS Animation -->
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">

    <!-- Anime.js for advanced animations -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/animejs/3.2.1/anime.min.js"></script>

    <!-- Tailwind CSS config (moved to tailwind.config.ts for better practice) -->
    <script>
        // This inline config is now redundant if tailwind.config.ts is properly set up
        // and Tailwind is built. Keeping it here for direct browser compatibility
        // if not using a build step, but ideally it should be removed.
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
                        'pulse-soft': 'pulseSoft 3s ease-in-out infinite',
                        'fade-in-down': 'fadeInDown 0.8s ease-out', // New animation
                        'zoom-in': 'zoomIn 0.6s ease-out', // New animation
                        'slide-in-top': 'slideInTop 0.7s ease-out' // New animation
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
                        fadeInDown: { // New keyframe
                            '0%': { opacity: '0', transform: 'translateY(-30px)' },
                            '100%': { opacity: '1', transform: 'translateY(0)' }
                        },
                        scaleIn: {
                            '0%': { opacity: '0', transform: 'scale(0.9)' },
                            '100%': { opacity: '1', transform: 'scale(1)' }
                        },
                        zoomIn: { // New keyframe
                            '0%': { opacity: '0', transform: 'scale(0.8)' },
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
                        slideInTop: { // New keyframe
                            '0%': { opacity: '0', transform: 'translateY(-50px)' },
                            '100%': { opacity: '1', transform: 'translateY(0)' }
                        },
                        bounceGentle: {
                            '0%, 100%': { transform: 'translateY(0px)' },
                            '50%': { transform: 'translateY(-5px)' }
                        },
                        pulseSoft: {
                            '0%, 100%': { opacity: '1' },
                            '50%': { opacity: '0.8' }
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
        /* Custom CSS for premium effects */
        .glass-effect {
            background: rgba(255, 255, 255, 0.25);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.18);
        }

        .gradient-text {
            background: linear-gradient(135deg, #b08d80, #c4a575);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .luxury-shadow {
            box-shadow: 0 25px 50px -12px rgba(176, 141, 128, 0.25);
        }

        .hover-lift {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .hover-lift:hover {
            transform: translateY(-8px);
            box-shadow: 0 35px 60px -12px rgba(176, 141, 128, 0.35);
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
                radial-gradient(at 0% 50%, rgba(166, 124, 118, 0.1) 0px, transparent 50%);
        }

        /* Mobile Categories Carousel Navigation */
        .mobile-categories-nav-btn {
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            border: 1px solid rgba(255, 255, 255, 0.3);
            transition: all 0.3s ease;
        }

        .mobile-categories-nav-btn:hover {
            background: rgba(255, 255, 255, 1);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.2);
            transform: scale(1.05);
        }

        .mobile-categories-nav-btn:active {
            transform: scale(0.95);
        }

        .mobile-categories-nav-btn i {
            font-weight: 900;
            filter: drop-shadow(0 1px 1px rgba(0, 0, 0, 0.1));
        }
        /* Mobile-specific styles */
        @media (max-width: 768px) {
            .mobile-hero-bg {
                background: linear-gradient(135deg, #fdf8f6 0%, #f2e8e5 50%, #eaddd7 100%);
            }

            .mobile-card-shadow {
                box-shadow: 0 8px 25px rgba(176, 141, 128, 0.15);
            }

            .mobile-gradient {
                background: linear-gradient(135deg, #b08d80 0%, #c4a575 100%);
            }

            .touch-target {
                min-height: 44px;
                min-width: 44px;
            }
        }
        /* Swipe indicators */
        .swipe-indicator {
            position: relative;
        }

        .swipe-indicator::after {
            content: '';
            position: absolute;
            bottom: -8px;
            left: 50%;
            transform: translateX(-50%);
            width: 30px;
            height: 3px;
            background: linear-gradient(90deg, #b08d80, #c4a575);
            border-radius: 2px;
            opacity: 0.6;
        }
        /* Mobile drawer */
        .mobile-drawer {
            transform: translateX(-100%);
            transition: transform 0.3s ease-in-out;
        }

        .mobile-drawer.open {
            transform: translateX(0);
        }
        /* Hide scrollbar but keep functionality */
        .hide-scrollbar {
            -ms-overflow-style: none;
            scrollbar-width: none;
        }
        
        /* Quick Action Buttons - Ensure they are clickable */
        .quick-action-btn {
            cursor: pointer !important;
            pointer-events: auto !important;
            z-index: 50 !important;
            position: relative !important;
        }
        
        .quick-action-btn:hover {
            transform: scale(1.1) !important;
        }

        .hide-scrollbar::-webkit-scrollbar {
            display: none;
        }

        /* Header Scroll Animation */
        .header-visible {
            transform: translateY(0);
            opacity: 1;
        }
        
        .header-hidden {
            transform: translateY(-100%);
            opacity: 0;
        }
        
        .header-scroll-animation {
            transition: transform 0.4s cubic-bezier(0.4, 0, 0.2, 1), 
                       opacity 0.4s cubic-bezier(0.4, 0, 0.2, 1),
                       backdrop-filter 0.3s ease,
                       box-shadow 0.3s ease;
        }
        
        /* Header states for different scroll positions */
        .header-scrolled {
            backdrop-filter: blur(20px);
            background: rgba(255, 255, 255, 0.95);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .header-compact {
            backdrop-filter: blur(25px);
            background: rgba(255, 255, 255, 0.98);
            box-shadow: 0 12px 40px rgba(0, 0, 0, 0.15);
        }
        
        /* Floating Buttons - Maximum priority z-index */
        #back-to-top {
            z-index: 9998 !important;
            position: fixed !important;
            pointer-events: auto !important;
        }
        
        .floating-whatsapp {
            z-index: 9999 !important;
            position: fixed !important;
            pointer-events: auto !important;
        }
        
        /* Ensure floating buttons are always on top */
        #back-to-top, .floating-whatsapp {
            isolation: isolate;
        }
        
        /* Mobile header scroll animations */
        .mobile-header-scroll-animation {
            transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1), 
                       opacity 0.3s cubic-bezier(0.4, 0, 0.2, 1),
                       backdrop-filter 0.2s ease,
                       box-shadow 0.2s ease;
        }
        
        .mobile-header-scrolled {
            backdrop-filter: blur(20px);
            background: rgba(255, 255, 255, 0.98);
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }

        /* Line clamp utilities */
        .line-clamp-2 {
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .line-clamp-3 {
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        /* Mobile CTA Buttons Improvements */
        @media (max-width: 640px) {
            .mobile-cta-buttons {
                padding: 0 1rem;
            }
            
            .mobile-cta-buttons a {
                min-height: 48px;
                font-weight: 600;
                letter-spacing: 0.025em;
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
                transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            }
            
            .mobile-cta-buttons a:active {
                transform: scale(0.98);
            }
            
            .mobile-cta-buttons .primary-btn {
                background: linear-gradient(135deg, #ec4899 0%, #f472b6 50%, #db2777 100%);
                background-size: 200% 200%;
                animation: gradient-shift 3s ease infinite;
            }
            
            .mobile-cta-buttons .secondary-btn {
                border-width: 2px;
                background: rgba(255, 255, 255, 0.95);
                backdrop-filter: blur(10px);
            }
            
            @keyframes gradient-shift {
                0%, 100% {
                    background-position: 0% 50%;
                }
                50% {
                    background-position: 100% 50%;
                }
            }
        }
    </style>
</head>
<body class="font-sans bg-white overflow-x-hidden">
    <!-- Desktop Header -->
    <header class="hidden md:block fixed top-0 left-0 right-0 z-50 header-scroll-animation header-visible" id="desktop-header">
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
                        <a href="/" class="text-3xl font-serif font-bold gradient-text opacity-0" id="desktop-logo-odisea">
                            Odisea
                        </a>
                        <span class="ml-2 text-xs text-gray-500 font-light opacity-0" id="desktop-logo-makeup">STORE</span>
                    </div>

                    <!-- Search Bar -->
                    <div class="flex flex-1 max-w-xl mx-8 relative">
                        <form action="catalogo.php" method="GET" class="relative w-full group">
                            <input type="text" 
                                   name="q"
                                   id="desktop-search-input"
                                   placeholder="Buscar productos, marcas..."
                                   class="w-full px-6 py-4 pr-14 bg-white/80 backdrop-blur-sm border border-gray-200 rounded-2xl focus:outline-none focus:ring-2 focus:ring-primary-500/50 focus:border-primary-500 transition-all duration-300 text-gray-700 placeholder-gray-400"
                                   required
                                   minlength="2"
                                   autocomplete="off">
                            <button type="submit" class="absolute right-4 top-1/2 transform -translate-y-1/2 w-8 h-8 bg-gradient-to-r from-primary-500 to-secondary-500 rounded-xl flex items-center justify-center text-white hover:shadow-lg transition-all duration-300">
                                <i class="fas fa-search text-sm"></i>
                            </button>
                        </form>
                        
                        <!-- Search Results Dropdown -->
                        <div id="desktop-search-results" class="absolute top-full left-0 right-0 mt-2 bg-white rounded-2xl shadow-2xl border border-gray-100 z-50 max-h-96 overflow-y-auto hidden">
                            <div class="p-4">
                                <div class="animate-pulse">
                                    <div class="h-4 bg-gray-200 rounded w-3/4 mb-2"></div>
                                    <div class="h-4 bg-gray-200 rounded w-1/2"></div>
                                </div>
                            </div>
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

                        <!-- Mega Menu -->
                        <div class="absolute left-0 top-full w-screen max-w-6xl bg-white/95 backdrop-blur-md shadow-2xl opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-300 z-40 rounded-2xl border border-gray-100 mt-2">
                            <div class="grid grid-cols-4 gap-8 p-8">
                                <?php
                                // Cargar categorías dinámicamente desde la base de datos
                                try {
                                    $categories = $categoryModel->getCategoryTreeWithIcons();
                                } catch (Exception $e) {
                                    // Fallback en caso de error
                                    $categories = [];
                                }

                                foreach ($categories as $category):
                                ?>
                                <div class="group/item">
                                    <div class="flex items-center space-x-3 mb-4">
                                        <div class="w-10 h-10 bg-gradient-to-r from-primary-500 to-secondary-500 rounded-xl flex items-center justify-center text-white">
                                            <i class="fas <?php echo $category['icon']; ?>"></i>
                                        </div>
                                        <h3 class="font-semibold text-gray-900 text-lg"><?php echo htmlspecialchars($category['name']); ?></h3>
                                    </div>
                                    <ul class="space-y-3">
                                        <?php foreach ($category['subcategories'] as $subcategory): ?>
                                        <li>
                                            <a href="categoria.php?categoria=<?php echo urlencode($subcategory['slug']); ?>"
                                                class="text-gray-600 hover:text-primary-500 transition-colors text-sm hover:translate-x-1 transform duration-200 block">
                                                <?php echo htmlspecialchars($subcategory['name']); ?>
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
                    <div class="flex items-center space-x-8">
                        <a href="index.php" class="text-gray-700 hover:text-primary-500 transition-colors font-medium py-4 border-b-2 border-transparent hover:border-primary-500">Inicio</a>
                        <a href="nuevos.php" class="text-gray-700 hover:text-primary-500 transition-colors font-medium py-4 border-b-2 border-transparent hover:border-primary-500">Novedades</a>
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
    <header class="md:hidden fixed top-0 left-0 right-0 z-50 bg-white/95 backdrop-blur-md shadow-lg mobile-header-scroll-animation header-visible" id="mobile-header">
        <div class="px-4 py-3">
            <div class="flex items-center justify-between">
                <!-- Mobile Menu Button -->
                <button class="touch-target p-2 text-gray-700 hover:text-primary-500 transition-colors rounded-xl hover:bg-primary-50" id="mobile-menu-btn">
                    <i class="fas fa-bars text-xl"></i>
                </button>

                <!-- Mobile Logo -->
                <div class="flex items-center">
                    <a href="/" class="text-2xl font-serif font-bold gradient-text opacity-0" id="mobile-logo-odisea">
                        Odisea
                    </a>
                    <span class="ml-1 text-xs text-gray-500 font-light opacity-0" id="mobile-logo-makeup">MAKEUP</span>
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
            <form action="catalogo.php" method="GET" class="relative">
                <input type="text"
                       name="q"
                       id="mobile-search-input"
                       placeholder="Buscar productos, marcas..."
                       class="w-full px-4 py-3 pr-12 bg-gray-50 border border-gray-200 rounded-2xl focus:outline-none focus:ring-2 focus:ring-primary-500/50 focus:border-primary-500 transition-all duration-300 text-gray-700 placeholder-gray-400"
                       required
                       minlength="2"
                       autocomplete="off">
                <button type="submit" class="absolute right-3 top-1/2 transform -translate-y-1/2 w-8 h-8 bg-gradient-to-r from-primary-500 to-secondary-500 rounded-xl flex items-center justify-center text-white">
                    <i class="fas fa-search text-sm"></i>
                </button>
            </form>
            
            <!-- Mobile Search Results -->
            <div id="mobile-search-results" class="absolute top-full left-4 right-4 mt-2 bg-white rounded-2xl shadow-2xl border border-gray-100 z-50 max-h-96 overflow-y-auto hidden">
                <div class="p-4">
                    <div class="animate-pulse">
                        <div class="h-4 bg-gray-200 rounded w-3/4 mb-2"></div>
                        <div class="h-4 bg-gray-200 rounded w-1/2"></div>
                    </div>
                </div>
            </div>
        </div>
    </header>
    <!-- Mobile Navigation Drawer -->
    <div class="md:hidden fixed inset-0 z-40 hidden" id="mobile-menu-overlay">
        <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" id="mobile-menu-backdrop"></div>

        <div class="mobile-drawer absolute left-0 top-0 h-full w-80 bg-white shadow-2xl" id="mobile-menu-drawer">
            <div class="flex flex-col h-full">
                <!-- Drawer Header -->
                <div class="flex items-center justify-between p-6 border-b border-gray-100 bg-gradient-to-r from-primary-50 to-secondary-50">
                    <div class="flex items-center">
                        <div class="w-10 h-10 bg-gradient-to-r from-primary-500 to-secondary-500 rounded-full flex items-center justify-center text-white mr-3">
                            <i class="fas fa-user text-lg"></i>
                        </div>
                        <div>
                            <div class="font-semibold text-gray-900">
                                <?php echo isset($_SESSION['user_name']) ? htmlspecialchars($_SESSION['user_name']) : 'Invitado'; ?>
                            </div>
                            <div class="text-sm text-gray-600">Bienvenida</div>
                        </div>
                    </div>
                    <button class="touch-target p-2 text-gray-500 hover:text-gray-700 rounded-xl" id="mobile-menu-close">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>

                <!-- Drawer Content -->
                <div class="flex-1 overflow-y-auto py-6">
                    <!-- Quick Actions -->
                    <div class="px-6 mb-6">
                        <div class="grid grid-cols-2 gap-3">
                            <a href="ofertas.php" class="flex items-center justify-center p-4 bg-gradient-to-r from-red-50 to-pink-50 rounded-2xl border border-red-100 hover:shadow-md transition-all duration-300">
                                <div class="text-center">
                                    <i class="fas fa-fire text-red-500 text-xl mb-2"></i>
                                    <div class="text-sm font-semibold text-red-700">Ofertas</div>
                                </div>
                            </a>
                            <a href="lista-deseos.php" class="flex items-center justify-center p-4 bg-gradient-to-r from-pink-50 to-rose-50 rounded-2xl border border-pink-100 hover:shadow-md transition-all duration-300">
                                <div class="text-center">
                                    <i class="fas fa-heart text-pink-500 text-xl mb-2"></i>
                                    <div class="text-sm font-semibold text-pink-700">Favoritos</div>
                                </div>
                            </a>
                        </div>
                    </div>

                    <!-- Categories -->
                    <div class="px-6 mb-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                            <i class="fas fa-th-large text-primary-500 mr-3"></i>
                            Categorías
                        </h3>
                        <div class="space-y-2">
                            <?php
                            // Mapeo de iconos para categorías
                            $categoryIcons = [
                                'maquillaje' => '💄',
                                'rostro' => '💄',
                                'ojos' => '👁️',
                                'labios' => '💋',
                                'cejas' => '�️',
                                'cuidado' => '🌸',
                                'herramientas' => '✨',
                                'tecnologia' => '📱',
                                'hogar' => '🏠',
                                'deportes' => '⚽',
                                'moda' => '👗',
                                'accesorios' => '💎'
                            ];

                            // Mostrar categorías desde la base de datos
                            foreach ($dbCategories as $category):
                                $icon = $categoryIcons[$category['slug']] ?? '🛍️'; // Icono por defecto
                                $categoryUrl = 'categoria.php?categoria=' . urlencode($category['slug']);
                            ?>
                            <a href="<?php echo $categoryUrl; ?>" class="flex items-center p-3 rounded-xl hover:bg-primary-50 transition-colors duration-300 group">
                                <span class="text-2xl mr-4"><?php echo $icon; ?></span>
                                <span class="font-medium text-gray-700 group-hover:text-primary-600"><?php echo htmlspecialchars($category['name']); ?></span>
                                <i class="fas fa-chevron-right text-gray-400 ml-auto group-hover:text-primary-500 transition-colors"></i>
                            </a>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Main Navigation -->
                    <div class="px-6 mb-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                            <i class="fas fa-compass text-secondary-500 mr-3"></i>
                            Navegación
                        </h3>
                        <div class="space-y-2">
                            <a href="index.php" class="flex items-center p-3 rounded-xl hover:bg-secondary-50 transition-colors duration-300 group">
                                <i class="fas fa-home text-secondary-500 mr-4 w-5"></i>
                                <span class="font-medium text-gray-700 group-hover:text-secondary-600">Inicio</span>
                            </a>
                            <a href="nuevos.php" class="flex items-center p-3 rounded-xl hover:bg-secondary-50 transition-colors duration-300 group">
                                <i class="fas fa-star text-secondary-500 mr-4 w-5"></i>
                                <span class="font-medium text-gray-700 group-hover:text-secondary-600">Novedades</span>
                            </a>
                            <a href="marcas.php" class="flex items-center p-3 rounded-xl hover:bg-secondary-50 transition-colors duration-300 group">
                                <i class="fas fa-tags text-secondary-500 mr-4 w-5"></i>
                                <span class="font-medium text-gray-700 group-hover:text-secondary-600">Marcas</span>
                            </a>
                        </div>
                    </div>

                    <!-- Account Section -->
                    <div class="px-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                            <i class="fas fa-user-circle text-accent-500 mr-3"></i>
                            Mi Cuenta
                        </h3>
                        <div class="space-y-2">
                            <?php if (isset($_SESSION['user_id'])): ?>
                                <a href="mi-cuenta.php" class="flex items-center p-3 rounded-xl hover:bg-accent-50 transition-colors duration-300 group">
                                    <i class="fas fa-user text-accent-500 mr-4 w-5"></i>
                                    <span class="font-medium text-gray-700 group-hover:text-accent-600">Mi Perfil</span>
                                </a>
                                <a href="mis-pedidos.php" class="flex items-center p-3 rounded-xl hover:bg-accent-50 transition-colors duration-300 group">
                                    <i class="fas fa-shopping-bag text-accent-500 mr-4 w-5"></i>
                                    <span class="font-medium text-gray-700 group-hover:text-accent-600">Mis Pedidos</span>
                                </a>
                                <a href="logout.php" class="flex items-center p-3 rounded-xl hover:bg-red-50 transition-colors duration-300 group">
                                    <i class="fas fa-sign-out-alt text-red-500 mr-4 w-5"></i>
                                    <span class="font-medium text-gray-700 group-hover:text-red-600">Cerrar Sesión</span>
                                </a>
                            <?php else: ?>
                                <a href="login.php" class="flex items-center p-3 rounded-xl hover:bg-accent-50 transition-colors duration-300 group">
                                    <i class="fas fa-sign-in-alt text-accent-500 mr-4 w-5"></i>
                                    <span class="font-medium text-gray-700 group-hover:text-accent-600">Iniciar Sesión</span>
                                </a>
                                <a href="register.php" class="flex items-center p-3 rounded-xl hover:bg-accent-50 transition-colors duration-300 group">
                                    <i class="fas fa-user-plus text-accent-500 mr-4 w-5"></i>
                                    <span class="font-medium text-gray-700 group-hover:text-accent-600">Registrarse</span>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Drawer Footer -->
                <div class="p-6 border-t border-gray-100 bg-gray-50">
                    <div class="text-center mb-4">
                        <div class="text-sm text-gray-600 mb-2">Síguenos en:</div>
                        <div class="flex justify-center space-x-4">
                            <?php if (!empty($contactSettings['social_instagram'])): ?>
                            <a href="<?php echo htmlspecialchars($contactSettings['social_instagram']); ?>" target="_blank" class="w-10 h-10 bg-gradient-to-r from-pink-500 to-rose-500 rounded-full flex items-center justify-center text-white hover:scale-110 transition-transform duration-300">
                                <i class="fab fa-instagram"></i>
                            </a>
                            <?php endif; ?>

                            <?php if (!empty($contactSettings['social_facebook'])): ?>
                            <a href="<?php echo htmlspecialchars($contactSettings['social_facebook']); ?>" target="_blank" class="w-10 h-10 bg-gradient-to-r from-blue-500 to-indigo-500 rounded-full flex items-center justify-center text-white hover:scale-110 transition-transform duration-300">
                                <i class="fab fa-facebook"></i>
                            </a>
                            <?php endif; ?>

                            <?php if (!empty($contactSettings['social_tiktok'])): ?>
                            <a href="<?php echo htmlspecialchars($contactSettings['social_tiktok']); ?>" target="_blank" class="w-10 h-10 bg-gradient-to-r from-gray-800 to-gray-900 rounded-full flex items-center justify-center text-white hover:scale-110 transition-transform duration-300">
                                <i class="fab fa-tiktok"></i>
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="text-xs text-gray-500 text-center">
                        © <?php echo date('Y'); ?> OdiseaStore
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Desktop Hero Section -->
    <?php if (!empty($heroBanners)): ?>
        <!-- Banner Carousel -->
        <section class="hidden md:block relative min-h-screen overflow-hidden">
            <div id="bannerCarousel" class="relative w-full h-screen">
                <?php foreach ($heroBanners as $index => $banner): ?>
                <div class="banner-slide <?php echo $index === 0 ? 'active' : ''; ?> absolute inset-0 transition-all duration-1000 ease-in-out <?php echo $index === 0 ? 'opacity-100' : 'opacity-0'; ?>">
                    <!-- Banner Background -->
                    <div class="absolute inset-0">
                        <img src="<?php echo BASE_URL . '/' . $banner['image']; ?>"
                             alt="<?php echo htmlspecialchars($banner['title'] ?: 'Banner promocional'); ?>"
                             class="w-full h-full object-cover">
                        <div class="absolute inset-0 bg-gradient-to-r from-black/50 via-black/30 to-transparent"></div>
                    </div>

                    <!-- Banner Content -->
                    <div class="relative z-10 container mx-auto px-4 h-full flex items-center">
                        <div class="max-w-2xl text-white space-y-6" data-aos="fade-right" data-aos-duration="1000">
                            <?php if (!empty($banner['title'])): ?>
                            <h1 class="text-5xl lg:text-7xl font-serif font-bold leading-tight text-shadow">
                                <?php echo htmlspecialchars($banner['title']); ?>
                            </h1>
                            <?php endif; ?>

                            <?php if (!empty($banner['subtitle'])): ?>
                            <h2 class="text-2xl lg:text-3xl font-light text-gray-200">
                                <?php echo htmlspecialchars($banner['subtitle']); ?>
                            </h2>
                            <?php endif; ?>

                            <?php if (!empty($banner['description'])): ?>
                            <p class="text-xl text-gray-300 leading-relaxed max-w-lg">
                                <?php echo htmlspecialchars($banner['description']); ?>
                            </p>
                            <?php endif; ?>

                            <?php if (!empty($banner['link_url']) && !empty($banner['link_text'])): ?>
                            <div class="pt-4">
                                <a href="<?php echo htmlspecialchars($banner['link_url']); ?>"
                                   class="group relative overflow-hidden bg-gradient-to-r from-primary-500 to-secondary-500 text-white px-10 py-5 rounded-2xl font-semibold text-lg hover:shadow-2xl transform hover:scale-105 transition-all duration-300 inline-block">
                                    <span class="relative z-10"><?php echo htmlspecialchars($banner['link_text']); ?></span>
                                    <div class="absolute inset-0 bg-gradient-to-r from-secondary-500 to-primary-500 opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                                </a>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>

                <?php if (count($heroBanners) > 1): ?>
                <!-- Carousel Controls -->
                <button onclick="previousSlide()" class="absolute left-4 top-1/2 transform -translate-y-1/2 bg-white/20 backdrop-blur-sm text-white p-3 rounded-full hover:bg-white/30 transition-all duration-300 z-20">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                    </svg>
                </button>
                <button onclick="nextSlide()" class="absolute right-4 top-1/2 transform -translate-y-1/2 bg-white/20 backdrop-blur-sm text-white p-3 rounded-full hover:bg-white/30 transition-all duration-300 z-20">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                    </svg>
                </button>

                <!-- Carousel Indicators -->
                <div class="absolute bottom-8 left-1/2 transform -translate-x-1/2 flex space-x-3 z-20">
                    <?php foreach ($heroBanners as $index => $banner): ?>
                    <button onclick="goToSlide(<?php echo $index; ?>)"
                            class="carousel-indicator w-3 h-3 rounded-full transition-all duration-300 <?php echo $index === 0 ? 'bg-white' : 'bg-white/50'; ?>"></button>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </section>
    <?php else: ?>
        <!-- Fallback Desktop Hero Section -->
        <section class="hidden md:block relative min-h-screen flex items-center justify-center overflow-hidden bg-mesh">
            <!-- Background Elements -->
            <div class="absolute inset-0">
                <div class="absolute top-20 left-10 w-72 h-72 bg-gradient-to-r from-primary-200/30 to-secondary-200/30 rounded-full blur-3xl animate-float"></div>
                <div class="absolute bottom-20 right-10 w-96 h-96 bg-gradient-to-r from-secondary-200/30 to-accent-200/30 rounded-full blur-3xl animate-float" style="animation-delay: 2s;"></div>
                <div class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 w-[800px] h-[800px] bg-gradient-to-r from-primary-100/20 to-secondary-100/20 rounded-full blur-3xl"></div>
            </div>

            <div class="container mx-auto px-4 py-32 relative z-10">
                <div class="grid lg:grid-cols-2 gap-16 items-center">
                    <!-- Hero Content -->
                    <div class="space-y-8" data-aos="fade-right" data-aos-duration="1000">
                        <div class="space-y-6">
                            <div class="inline-block">
                                <span class="bg-gradient-to-r from-primary-500 to-secondary-500 text-white px-6 py-2 rounded-full text-sm font-medium tracking-wide uppercase animate-zoom-in">
                                    Colección Exclusiva 2024
                                </span>
                            </div>

                            <h1 class="text-5xl lg:text-7xl font-serif font-bold leading-tight text-shadow animate-fade-in-up">
                                <span class="gradient-text">Descubre</span>
                                <br>
                                <span class="text-gray-900">todo lo que</span>
                                <br>
                                <span class="gradient-text">necesitas</span>
                            </h1>

                            <p class="text-xl lg:text-2xl text-gray-600 leading-relaxed font-light max-w-lg animate-fade-in-up" style="animation-delay: 200ms;">
                                Explora nuestra colección exclusiva de productos de calidad en tecnología, hogar, moda y mucho más.
                                Desde las últimas tendencias hasta los productos más confiables.
                            </p>
                        </div>

                        <div class="flex flex-col sm:flex-row gap-4 sm:gap-6 animate-fade-in-up mobile-cta-buttons" style="animation-delay: 400ms;">
                            <a href="catalogo.php" class="group relative overflow-hidden bg-gradient-to-r from-primary-500 to-secondary-500 text-white px-6 sm:px-10 py-4 sm:py-5 rounded-2xl font-semibold text-base sm:text-lg hover:shadow-2xl transform hover:scale-105 transition-all duration-300 text-center primary-btn">
                                <span class="relative z-10 flex items-center justify-center">
                                    <i class="fas fa-th-large mr-2 sm:mr-0 sm:hidden"></i>
                                    Explorar Colección
                                </span>
                                <div class="absolute inset-0 bg-gradient-to-r from-secondary-500 to-primary-500 opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                            </a>
                            <a href="ofertas.php" class="group border-2 border-primary-500 text-primary-500 px-6 sm:px-10 py-4 sm:py-5 rounded-2xl font-semibold text-base sm:text-lg hover:bg-primary-500 hover:text-white transition-all duration-300 text-center relative overflow-hidden secondary-btn">
                                <span class="relative z-10 flex items-center justify-center">
                                    <i class="fas fa-fire mr-2 sm:mr-0 sm:hidden"></i>
                                    Ver Ofertas
                                </span>
                                <div class="absolute inset-0 bg-primary-500 transform scale-x-0 group-hover:scale-x-100 transition-transform duration-300 origin-left"></div>
                            </a>
                        </div>

                        <!-- Features -->
                        <div class="grid grid-cols-3 gap-8 pt-12">
                            <div class="text-center group animate-fade-in-up" style="animation-delay: 600ms;">
                                <div class="w-16 h-16 bg-gradient-to-r from-primary-100 to-secondary-100 rounded-2xl flex items-center justify-center mx-auto mb-4 group-hover:scale-110 transition-transform duration-300">
                                    <i class="fas fa-shipping-fast text-primary-500 text-2xl"></i>
                                </div>
                                <p class="font-semibold text-gray-800 mb-1">Envío Gratis</p>
                                <p class="text-sm text-gray-500">Compras +$150.000</p>
                            </div>
                            <div class="text-center group animate-fade-in-up" style="animation-delay: 700ms;">
                                <div class="w-16 h-16 bg-gradient-to-r from-secondary-100 to-accent-100 rounded-2xl flex items-center justify-center mx-auto mb-4 group-hover:scale-110 transition-transform duration-300">
                                    <i class="fas fa-award text-secondary-500 text-2xl"></i>
                                </div>
                                <p class="font-semibold text-gray-800 mb-1">Calidad Premium</p>
                                <p class="text-sm text-gray-500">Marcas originales</p>
                            </div>
                            <div class="text-center group animate-fade-in-up" style="animation-delay: 800ms;">
                                <div class="w-16 h-16 bg-gradient-to-r from-accent-100 to-primary-100 rounded-2xl flex items-center justify-center mx-auto mb-4 group-hover:scale-110 transition-transform duration-300">
                                    <i class="fas fa-headset text-accent-500 text-2xl"></i>
                                </div>
                                <p class="font-semibold text-gray-800 mb-1">Soporte 24/7</p>
                                <p class="text-sm text-gray-500">Siempre aquí</p>
                            </div>
                        </div>
                    </div>

                    <!-- Hero Image -->
                    <div class="relative" data-aos="fade-left" data-aos-duration="1000" data-aos-delay="200">
                        <div class="relative z-10">
                            <div class="relative overflow-hidden rounded-3xl luxury-shadow">
                                <img src="<?php echo BASE_URL; ?>/public/images/hero-model.svg"
                                     alt="Productos variados de calidad"
                                     class="w-full h-auto animate-float"
                                     loading="lazy">
                                <div class="absolute inset-0 bg-gradient-to-t from-black/20 via-transparent to-transparent"></div>
                            </div>
                        </div>

                        <!-- Floating Product Cards -->
                        <div class="absolute -top-8 -left-8 bg-white/90 backdrop-blur-md rounded-3xl shadow-2xl p-6 animate-float border border-white/20" style="animation-delay: 1s;" data-aos="fade-up" data-aos-delay="800">
                            <div class="flex items-center space-x-4">
                                <img src="<?php echo BASE_URL; ?>/public/images/lipstick.svg" alt="Labial Mate" class="w-15 h-15 rounded-2xl" loading="lazy">
                                <div>
                                    <p class="font-semibold text-gray-800">Labial Mate Luxury</p>
                                    <p class="text-primary-500 font-bold text-lg">$45.000</p>
                                    <div class="flex text-yellow-400 text-sm mt-1">
                                        <i class="fas fa-star"></i>
                                        <i class="fas fa-star"></i>
                                        <i class="fas fa-star"></i>
                                        <i class="fas fa-star"></i>
                                        <i class="fas fa-star"></i>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="absolute -bottom-8 -right-8 bg-white/90 backdrop-blur-md rounded-3xl shadow-2xl p-6 animate-float border border-white/20" style="animation-delay: 2s;" data-aos="fade-up" data-aos-delay="1000">
                            <div class="flex items-center space-x-4">
                                <img src="<?php echo BASE_URL; ?>/public/images/eyeshadow-palette.svg" alt="Paleta Sombras" class="w-15 h-15 rounded-2xl" loading="lazy">
                                <div>
                                    <p class="font-semibold text-gray-800">Paleta Premium</p>
                                    <p class="text-primary-500 font-bold text-lg">$89.000</p>
                                    <div class="flex text-yellow-400 text-sm mt-1">
                                        <i class="fas fa-star"></i>
                                        <i class="fas fa-star"></i>
                                        <i class="fas fa-star"></i>
                                        <i class="fas fa-star"></i>
                                        <i class="fas fa-star"></i>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Decorative Elements -->
                        <div class="absolute top-16 -right-16 w-32 h-32 bg-gradient-to-r from-primary-300/30 to-secondary-300/30 rounded-full blur-2xl animate-pulse"></div>
                        <div class="absolute -bottom-16 -left-16 w-40 h-40 bg-gradient-to-r from-accent-300/30 to-primary-300/30 rounded-full blur-2xl animate-pulse" style="animation-delay: 1s;"></div>
                    </div>
                </div>
            </div>
        </section>
    <?php endif; ?>
    <!-- Mobile Hero Section -->
    <?php if (!empty($heroBanners)): ?>
        <!-- Mobile Banner Carousel -->
        <section class="md:hidden pt-24 pb-16 relative overflow-hidden">
            <div id="mobileBannerCarousel" class="relative w-full min-h-screen">
                <?php foreach ($heroBanners as $index => $banner): ?>
                <div class="mobile-banner-slide <?php echo $index === 0 ? 'active' : ''; ?> absolute inset-0 transition-all duration-1000 ease-in-out <?php echo $index === 0 ? 'opacity-100' : 'opacity-0'; ?>">
                    <!-- Mobile Banner Background -->
                    <div class="absolute inset-0">
                        <img src="<?php echo BASE_URL . '/' . $banner['image']; ?>"
                             alt="<?php echo htmlspecialchars($banner['title'] ?: 'Banner promocional'); ?>"
                             class="w-full h-full object-cover">
                        <div class="absolute inset-0 bg-gradient-to-b from-black/60 via-black/40 to-black/70"></div>
                    </div>

                    <!-- Mobile Background Elements -->
                    <div class="absolute inset-0">
                        <div class="absolute top-10 right-10 w-32 h-32 bg-gradient-to-r from-white/10 to-white/5 rounded-full blur-2xl animate-pulse"></div>
                        <div class="absolute bottom-10 left-10 w-24 h-24 bg-gradient-to-r from-white/10 to-white/5 rounded-full blur-xl animate-bounce-gentle"></div>
                    </div>

                    <div class="container mx-auto px-4 relative z-10 h-full flex flex-col justify-center">
                        <!-- Mobile Hero Content -->
                        <div class="text-center mb-12" data-aos="fade-up">
                            <?php if (!empty($banner['title'])): ?>
                            <h1 class="text-3xl sm:text-4xl font-serif font-bold leading-tight mb-6 text-white text-shadow animate-fade-in-up">
                                <?php echo htmlspecialchars($banner['title']); ?>
                            </h1>
                            <?php endif; ?>

                            <?php if (!empty($banner['subtitle'])): ?>
                            <h2 class="text-lg sm:text-xl font-light text-gray-200 mb-6">
                                <?php echo htmlspecialchars($banner['subtitle']); ?>
                            </h2>
                            <?php endif; ?>

                            <?php if (!empty($banner['description'])): ?>
                            <p class="text-sm sm:text-base text-gray-300 leading-relaxed font-light mb-8 max-w-sm mx-auto animate-fade-in-up" style="animation-delay: 200ms;">
                                <?php echo htmlspecialchars($banner['description']); ?>
                            </p>
                            <?php endif; ?>

                            <div class="flex flex-col space-y-4 max-w-xs mx-auto animate-fade-in-up mobile-cta-buttons" style="animation-delay: 400ms;">
                                <?php if (!empty($banner['link_url']) && !empty($banner['link_text'])): ?>
                                <a href="<?php echo htmlspecialchars($banner['link_url']); ?>" class="bg-gradient-to-r from-primary-500 to-secondary-500 text-white px-6 sm:px-8 py-3 sm:py-4 rounded-2xl font-semibold text-center text-sm sm:text-base hover:shadow-lg transform hover:scale-105 transition-all duration-300 primary-btn">
                                    <span class="flex items-center justify-center">
                                        <i class="fas fa-external-link-alt mr-2 sm:mr-0 sm:hidden text-xs"></i>
                                        <?php echo htmlspecialchars($banner['link_text']); ?>
                                    </span>
                                </a>
                                <?php else: ?>
                                <a href="catalogo.php" class="bg-gradient-to-r from-primary-500 to-secondary-500 text-white px-6 sm:px-8 py-3 sm:py-4 rounded-2xl font-semibold text-center text-sm sm:text-base hover:shadow-lg transform hover:scale-105 transition-all duration-300 primary-btn">
                                    <span class="flex items-center justify-center">
                                        <i class="fas fa-th-large mr-2 sm:mr-0 sm:hidden text-xs"></i>
                                        Explorar Colección
                                    </span>
                                </a>
                                <?php endif; ?>
                                <a href="ofertas.php" class="border-2 border-white text-white px-6 sm:px-8 py-3 sm:py-4 rounded-2xl font-semibold text-center text-sm sm:text-base hover:bg-white hover:text-gray-900 transition-all duration-300">
                                    <span class="flex items-center justify-center">
                                        <i class="fas fa-fire mr-2 sm:mr-0 sm:hidden text-xs"></i>
                                        Ver Ofertas
                                    </span>
                                </a>
                            </div>
                        </div>

                        <!-- Mobile Features -->
                        <div class="grid grid-cols-3 gap-4 mt-auto" data-aos="fade-up" data-aos-delay="300">
                            <div class="text-center animate-fade-in-up" style="animation-delay: 700ms;">
                                <div class="w-12 h-12 bg-white/20 backdrop-blur-sm rounded-2xl flex items-center justify-center mx-auto mb-3">
                                    <i class="fas fa-shipping-fast text-white text-lg"></i>
                                </div>
                                <p class="font-semibold text-white text-sm mb-1">Envío Gratis</p>
                                <p class="text-xs text-gray-300">+$150.000</p>
                            </div>
                            <div class="text-center animate-fade-in-up" style="animation-delay: 800ms;">
                                <div class="w-12 h-12 bg-white/20 backdrop-blur-sm rounded-2xl flex items-center justify-center mx-auto mb-3">
                                    <i class="fas fa-shield-alt text-white text-lg"></i>
                                </div>
                                <p class="font-semibold text-white text-sm mb-1">Garantía</p>
                                <p class="text-xs text-gray-300">100% Seguro</p>
                            </div>
                            <div class="text-center animate-fade-in-up" style="animation-delay: 900ms;">
                                <div class="w-12 h-12 bg-white/20 backdrop-blur-sm rounded-2xl flex items-center justify-center mx-auto mb-3">
                                    <i class="fas fa-award text-white text-lg"></i>
                                </div>
                                <p class="font-semibold text-white text-sm mb-1">Calidad</p>
                                <p class="text-xs text-gray-300">Premium</p>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>

                <!-- Mobile Carousel Navigation -->
                <?php if (count($heroBanners) > 1): ?>
                <!-- Mobile Carousel Indicators -->
                <div class="absolute bottom-20 left-1/2 transform -translate-x-1/2 flex space-x-2 z-20">
                    <?php foreach ($heroBanners as $index => $banner): ?>
                    <button class="mobile-carousel-indicator w-3 h-3 rounded-full transition-all duration-300 <?php echo $index === 0 ? 'bg-white' : 'bg-white/50'; ?>" data-slide="<?php echo $index; ?>"></button>
                    <?php endforeach; ?>
                </div>

                <!-- Mobile Carousel Arrows -->
                <button class="mobile-carousel-prev absolute left-4 top-1/2 transform -translate-y-1/2 w-12 h-12 bg-white/20 backdrop-blur-sm rounded-full flex items-center justify-center text-white hover:bg-white/30 transition-all duration-300 z-20">
                    <i class="fas fa-chevron-left"></i>
                </button>
                <button class="mobile-carousel-next absolute right-4 top-1/2 transform -translate-y-1/2 w-12 h-12 bg-white/20 backdrop-blur-sm rounded-full flex items-center justify-center text-white hover:bg-white/30 transition-all duration-300 z-20">
                    <i class="fas fa-chevron-right"></i>
                </button>
                <?php endif; ?>
            </div>
        </section>
    <?php else: ?>
        <!-- Fallback Mobile Hero Section -->
        <section class="md:hidden pt-28 pb-16 mobile-hero-bg relative overflow-hidden">
        <!-- Mobile Background Elements -->
        <div class="absolute inset-0">
            <div class="absolute top-10 right-10 w-32 h-32 bg-gradient-to-r from-primary-200/20 to-secondary-200/20 rounded-full blur-2xl animate-pulse"></div>
            <div class="absolute bottom-10 left-10 w-24 h-24 bg-gradient-to-r from-secondary-200/20 to-accent-200/20 rounded-full blur-xl animate-bounce-gentle"></div>
        </div>

        <div class="container mx-auto px-4 relative z-10">
            <!-- Mobile Hero Content -->
            <div class="text-center mb-12" data-aos="fade-up">
                <div class="inline-block mb-4">
                    <span class="bg-gradient-to-r from-primary-500 to-secondary-500 text-white px-4 py-2 rounded-full text-xs font-medium tracking-wide uppercase animate-pulse-soft">
                        ✨ Colección 2024
                    </span>
                </div>

                <h1 class="text-4xl font-serif font-bold leading-tight mb-6 text-shadow animate-fade-in-up">
                    <span class="gradient-text">Descubre</span>
                    <br>
                    <span class="text-gray-900">todo lo que</span>
                    <br>
                    <span class="gradient-text">necesitas</span>
                </h1>

                <p class="text-lg text-gray-600 leading-relaxed font-light mb-8 max-w-sm mx-auto animate-fade-in-up" style="animation-delay: 200ms;">
                    Explora nuestra colección exclusiva de productos de calidad en todas las categorías.
                </p>

                <div class="flex flex-col space-y-3 max-w-xs mx-auto animate-fade-in-up mobile-cta-buttons" style="animation-delay: 400ms;">
                    <a href="catalogo.php" class="bg-gradient-to-r from-primary-500 to-secondary-500 text-white px-6 py-3.5 rounded-2xl font-semibold text-center text-sm hover:shadow-lg transform hover:scale-105 transition-all duration-300 flex items-center justify-center primary-btn">
                        <i class="fas fa-th-large mr-2"></i>
                        Explorar Colección
                    </a>
                    <a href="ofertas.php" class="border-2 border-primary-500 text-primary-500 px-6 py-3.5 rounded-2xl font-semibold text-center text-sm hover:bg-primary-500 hover:text-white transition-all duration-300 flex items-center justify-center secondary-btn">
                        <i class="fas fa-fire mr-2"></i>
                        Ver Ofertas
                    </a>
                </div>
            </div>

            <!-- Mobile Hero Image -->
            <div class="relative mb-8" data-aos="fade-up" data-aos-delay="200">
                <div class="relative overflow-hidden rounded-3xl mobile-card-shadow mx-auto max-w-sm">
                    <img src="<?php echo BASE_URL; ?>/public/images/hero-model.svg"
                         alt="Catálogo de productos variados"
                         class="w-full h-64 object-cover"
                         loading="lazy">
                    <div class="absolute inset-0 bg-gradient-to-t from-black/30 via-transparent to-transparent"></div>

                    <!-- Mobile Floating Badge -->
                    <div class="absolute top-4 left-4 bg-white/90 backdrop-blur-sm rounded-2xl px-4 py-2 shadow-lg animate-zoom-in" style="animation-delay: 600ms;">
                        <div class="text-sm font-bold text-primary-500">Premium</div>
                        <div class="text-xs text-gray-600">Quality</div>
                    </div>
                </div>
            </div>

            <!-- Mobile Features -->
            <div class="grid grid-cols-3 gap-4" data-aos="fade-up" data-aos-delay="300">
                <div class="text-center animate-fade-in-up" style="animation-delay: 700ms;">
                    <div class="w-12 h-12 bg-gradient-to-r from-primary-100 to-secondary-100 rounded-2xl flex items-center justify-center mx-auto mb-3">
                        <i class="fas fa-shipping-fast text-primary-500 text-lg"></i>
                    </div>
                    <p class="font-semibold text-gray-800 text-sm mb-1">Envío Gratis</p>
                    <p class="text-xs text-gray-500">+$150.000</p>
                </div>
                <div class="text-center animate-fade-in-up" style="animation-delay: 800ms;">
                    <div class="w-12 h-12 bg-gradient-to-r from-secondary-100 to-accent-100 rounded-2xl flex items-center justify-center mx-auto mb-3">
                        <i class="fas fa-award text-secondary-500 text-lg"></i>
                    </div>
                    <p class="font-semibold text-gray-800 text-sm mb-1">Premium</p>
                    <p class="text-xs text-gray-500">Originales</p>
                </div>
                <div class="text-center animate-fade-in-up" style="animation-delay: 900ms;">
                    <div class="w-12 h-12 bg-gradient-to-r from-accent-100 to-primary-100 rounded-2xl flex items-center justify-center mx-auto mb-3">
                        <i class="fas fa-headset text-accent-500 text-lg"></i>
                    </div>
                    <p class="font-semibold text-gray-800 text-sm mb-1">Soporte</p>
                    <p class="text-xs text-gray-500">24/7</p>
                </div>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- Desktop Categories Section -->
    <section class="hidden md:block py-24 relative overflow-hidden">
        <div class="absolute inset-0 bg-gradient-to-br from-luxury-rose/30 via-white to-luxury-gold/30"></div>

        <div class="container mx-auto px-4 relative z-10">
            <div class="text-center mb-20" data-aos="fade-up">
                <div class="inline-block mb-6">
                    <span class="text-sm font-medium tracking-widest uppercase text-primary-600 bg-primary-50 px-6 py-2 rounded-full animate-zoom-in">
                        Colección Exclusiva
                    </span>
                </div>
                <h2 class="text-4xl lg:text-6xl font-serif font-bold mb-6 gradient-text animate-fade-in-up">
                    Explora por Categorías
                </h2>
                <p class="text-xl text-gray-600 max-w-3xl mx-auto leading-relaxed font-light animate-fade-in-up" style="animation-delay: 200ms;">
                    Encuentra exactamente lo que buscas en nuestras categorías especializadas,
                    cuidadosamente curadas para realzar tu belleza natural
                </p>
            </div>

            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-6 mb-20">
                <?php
                // Cargar categorías dinámicas desde la base de datos
                try {
                    $categoryModel = new Category();
                    $productModel = new Product();
                    $dbCategories = $categoryModel->getAll(['is_active' => 1]);

                    $elegantCategories = [
                        'rostro' => ['icon' => '💄', 'color' => 'from-rose-400 to-pink-400'],
                        'base' => ['icon' => '🎨', 'color' => 'from-amber-400 to-orange-400'],
                        'ojos' => ['icon' => '👁️', 'color' => 'from-purple-400 to-indigo-400'],
                        'labios' => ['icon' => '💋', 'color' => 'from-red-400 to-rose-400'],
                        'cejas' => ['icon' => '🖌️', 'color' => 'from-amber-400 to-yellow-400'],
                        'cuidado' => ['icon' => '🌸', 'color' => 'from-green-400 to-emerald-400'],
                        'herramientas' => ['icon' => '✨', 'color' => 'from-indigo-400 to-purple-400'],
                        'accesorios' => ['icon' => '💎', 'color' => 'from-violet-400 to-purple-400']
                    ];

                    if (!empty($dbCategories)) {
                        $featuredCategories = array_slice($dbCategories, 0, 6);
                    } else {
                        $featuredCategories = [
                            ['name' => 'Rostro', 'slug' => 'rostro'],
                            ['name' => 'Ojos', 'slug' => 'ojos'],
                            ['name' => 'Labios', 'slug' => 'labios'],
                            ['name' => 'Cejas', 'slug' => 'cejas'],
                            ['name' => 'Cuidado', 'slug' => 'cuidado'],
                            ['name' => 'Herramientas', 'slug' => 'herramientas']
                        ];
                    }
                } catch (Exception $e) {
                    $featuredCategories = [
                        ['name' => 'Rostro', 'slug' => 'rostro'],
                        ['name' => 'Ojos', 'slug' => 'ojos'],
                        ['name' => 'Labios', 'slug' => 'labios'],
                        ['name' => 'Cejas', 'slug' => 'cejas'],
                        ['name' => 'Cuidado', 'slug' => 'cuidado'],
                        ['name' => 'Herramientas', 'slug' => 'herramientas']
                    ];
                }

                foreach ($featuredCategories as $index => $category):
                    $delay = ($index + 1) * 100;
                    $slug = $category['slug'] ?? strtolower(str_replace(' ', '-', $category['name']));
                    $iconData = $elegantCategories[$slug] ?? ['icon' => '✨', 'color' => 'from-gray-400 to-gray-500'];
                ?>
                <div class="group cursor-pointer" data-aos="fade-up" data-aos-delay="<?php echo $delay; ?>">
                    <a href="categoria.php?categoria=<?php echo urlencode($slug); ?>" class="block">
                        <div class="relative overflow-hidden rounded-3xl p-8 text-center transition-all duration-500 transform group-hover:-translate-y-4 hover-lift bg-white/80 backdrop-blur-sm border border-white/50 shimmer-effect">
                            <!-- Image Container -->
                            <div class="relative z-10 mb-6">
                                <div class="w-20 h-20 mx-auto rounded-2xl flex items-center justify-center mb-4 group-hover:scale-110 transition-transform duration-300 bg-gradient-to-r <?php echo $iconData['color']; ?> shadow-lg overflow-hidden">
                                    <?php if (!empty($category['image'])): ?>
                                        <img src="<?php echo BASE_URL . '/' . htmlspecialchars($category['image']); ?>" 
                                             alt="<?php echo htmlspecialchars($category['name']); ?>"
                                             class="w-full h-full object-cover"
                                             onerror="this.parentNode.innerHTML='<span class=\'filter drop-shadow-sm text-4xl text-white\'><?php echo $iconData['icon']; ?></span>'">
                                    <?php else: ?>
                                        <span class="filter drop-shadow-sm text-4xl text-white"><?php echo $iconData['icon']; ?></span>
                                    <?php endif; ?>
                                </div>

                                <h3 class="font-semibold text-lg mb-3 text-gray-800 group-hover:text-primary-600 transition-colors">
                                    <?php echo htmlspecialchars($category['name']); ?>
                                </h3>

                                <div class="w-12 h-1 mx-auto rounded-full group-hover:w-16 transition-all duration-300 bg-gradient-to-r from-primary-500 to-secondary-500"></div>
                            </div>

                            <!-- Hover glow effect -->
                            <div class="absolute inset-0 rounded-3xl opacity-0 group-hover:opacity-100 transition-opacity duration-500 bg-gradient-to-r from-primary-500/10 to-secondary-500/10"></div>
                        </div>
                    </a>
                </div>
                <?php endforeach; ?>
            </div>

            <div class="text-center" data-aos="fade-up" data-aos-delay="600">
                <a href="catalogo.php" class="group inline-flex items-center px-12 py-5 rounded-2xl font-semibold text-white transition-all duration-500 transform hover:-translate-y-2 luxury-shadow bg-gradient-to-r from-primary-500 to-secondary-500 hover:from-secondary-500 hover:to-primary-500">
                    <span class="mr-3">Ver Todas las Categorías</span>
                    <svg class="w-5 h-5 transition-transform duration-300 group-hover:translate-x-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
                    </svg>
                </a>
            </div>
        </div>
    </section>
    <!-- Mobile Categories Section -->
    <section class="md:hidden py-12 bg-gradient-to-br from-luxury-rose/20 via-white to-luxury-gold/20">
    <div class="container mx-auto px-4 mt-8">
            <div class="text-center mb-8" data-aos="fade-up">
                <div class="inline-block mb-4">
                    <span class="text-xs font-medium tracking-widest uppercase text-primary-600 bg-primary-50 px-4 py-2 rounded-full animate-zoom-in">
                        Categorías
                    </span>
                </div>
                <h2 class="text-3xl font-serif font-bold mb-4 gradient-text animate-fade-in-up">
                    Explora por Categorías
                </h2>
                <p class="text-gray-600 leading-relaxed font-light animate-fade-in-up" style="animation-delay: 200ms;">
                    Encuentra exactamente lo que buscas
                </p>
            </div>

            <!-- Mobile Categories Carousel -->
            <div class="relative" data-aos="fade-up" data-aos-delay="200">
                <div class="overflow-hidden">
                    <div class="flex transition-transform duration-300 ease-in-out" id="mobile-categories-carousel">
                        <?php
                        foreach ($featuredCategories as $index => $category):
                            $slug = $category['slug'] ?? strtolower(str_replace(' ', '-', $category['name']));
                            $iconData = $elegantCategories[$slug] ?? ['icon' => '✨', 'color' => 'from-gray-400 to-gray-500'];
                        ?>
                        <div class="w-full flex-shrink-0 px-4">
                            <div class="flex justify-center">
                                <div class="w-32 animate-fade-in-up" style="animation-delay: <?php echo 300 + ($index * 100); ?>ms;">
                                    <a href="categoria.php?categoria=<?php echo urlencode($slug); ?>" class="block">
                                        <div class="bg-white/90 backdrop-blur-sm rounded-3xl p-6 text-center mobile-card-shadow hover:shadow-lg transition-all duration-300 border border-white/50">
                                            <div class="w-16 h-16 mx-auto rounded-2xl flex items-center justify-center mb-4 bg-gradient-to-r <?php echo $iconData['color']; ?> shadow-md overflow-hidden">
                                                <?php if (!empty($category['image'])): ?>
                                                    <img src="<?php echo BASE_URL . '/' . htmlspecialchars($category['image']); ?>" 
                                                         alt="<?php echo htmlspecialchars($category['name']); ?>"
                                                         class="w-full h-full object-cover"
                                                         onerror="this.parentNode.innerHTML='<span class=\'filter drop-shadow-sm text-3xl text-white\'><?php echo $iconData['icon']; ?></span>'">
                                                <?php else: ?>
                                                    <span class="filter drop-shadow-sm text-3xl text-white"><?php echo $iconData['icon']; ?></span>
                                                <?php endif; ?>
                                            </div>
                                            <h3 class="font-semibold text-base text-gray-800 leading-tight">
                                                <?php echo htmlspecialchars($category['name']); ?>
                                            </h3>
                                        </div>
                                    </a>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <!-- Navigation Arrows -->
                <button id="mobile-categories-prev" class="mobile-categories-nav-btn absolute left-2 top-1/2 transform -translate-y-1/2 w-10 h-10 bg-white/95 backdrop-blur-sm rounded-full flex items-center justify-center z-20">
                    <i class="fas fa-chevron-left text-gray-700 text-sm"></i>
                </button>
                <button id="mobile-categories-next" class="mobile-categories-nav-btn absolute right-2 top-1/2 transform -translate-y-1/2 w-10 h-10 bg-white/95 backdrop-blur-sm rounded-full flex items-center justify-center z-20">
                    <i class="fas fa-chevron-right text-gray-700 text-sm"></i>
                </button>
                
                <!-- Indicators -->
                <div class="flex justify-center mt-6 space-x-2">
                    <?php foreach ($featuredCategories as $index => $category): ?>
                        <button class="mobile-category-indicator w-2 h-2 rounded-full transition-all duration-300 <?php echo $index === 0 ? 'bg-primary-500 w-6' : 'bg-gray-300'; ?>" data-slide="<?php echo $index; ?>"></button>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="text-center" data-aos="fade-up" data-aos-delay="400">
                <a href="catalogo.php" class="inline-flex items-center bg-gradient-to-r from-primary-500 to-secondary-500 text-white px-8 py-4 rounded-2xl font-semibold hover:shadow-lg transform hover:scale-105 transition-all duration-300 mt-8 mb-8">
                    <span class="mr-2">Ver Todas</span>
                    <i class="fas fa-arrow-right text-sm"></i>
                </a>
            </div>
        </div>
    </section>
    <!-- Desktop Featured Products -->
    <section class="hidden md:block py-24 relative overflow-hidden bg-gradient-to-br from-luxury-pearl via-white to-luxury-champagne">
        <div class="container mx-auto px-4 relative z-10">
            <div class="text-center mb-20" data-aos="fade-up">
                <div class="inline-block mb-6">
                    <span class="text-sm font-medium tracking-widest uppercase text-secondary-600 bg-secondary-50 px-6 py-2 rounded-full animate-zoom-in">
                        Selección Especial
                    </span>
                </div>
                <h2 class="text-4xl lg:text-6xl font-serif font-bold mb-6 gradient-text animate-fade-in-up">
                    Productos Destacados
                </h2>
                <p class="text-xl text-gray-600 max-w-3xl mx-auto leading-relaxed font-light animate-fade-in-up" style="animation-delay: 200ms;">
                    Los favoritos de nuestras clientas, seleccionados especialmente para ti
                    con la más alta calidad y resultados excepcionales
                </p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-8 mb-20">
                <?php
                // Cargar productos destacados desde la base de datos
                try {
                    $product = new Product();
                    $featuredProducts = $product->getFeaturedProducts(8);

                    if (empty($featuredProducts)) {
                        $featuredProducts = $product->getAll(['status' => 'active', 'limit' => 8]);
                    }
                } catch (Exception $e) {
                    $featuredProducts = [];
                    error_log("Error loading featured products: " . $e->getMessage());
                }
                ?>

                <?php if (!empty($featuredProducts)): ?>
                    <?php foreach ($featuredProducts as $index => $product):
                        $delay = ($index + 1) * 150;

                        $brandName = !empty($product['brand_name']) ? $product['brand_name'] : '';

                        // Get product rating data
                        $ratingData = $reviewModel->getAverageRating($product['id']);
                        $averageRating = $ratingData['average'] ?? 0;
                        $reviewCount = $ratingData['count'] ?? 0;

                        $discount = 0;
                        if (!empty($product['compare_price']) && $product['compare_price'] > $product['price']) {
                            $discount = round((($product['compare_price'] - $product['price']) / $product['compare_price']) * 100);
                        }

                        // Use the new placeholder images for static fallback
                        if (!empty($product['main_image'])) {
                            // Clean the image path to remove duplicated prefixes
                            $imagePath = $product['main_image'];
                            // Remove common incorrect prefixes
                            $imagePath = str_replace('uploads/products/', '', $imagePath);
                            $imagePath = str_replace('assets/images/products/', '', $imagePath);
                            $productImage = BASE_URL . '/uploads/products/' . $imagePath;
                        } else {
                            $productImage = BASE_URL . '/public/images/product-placeholder-1.svg';
                        }

                        $displayPrice = $product['price'];
                        $originalPrice = (!empty($product['compare_price']) && $product['compare_price'] > $product['price']) ? $product['compare_price'] : null;
                    ?>

                    <div class="group animate-fade-in-up" data-aos="fade-up" data-aos-delay="<?php echo $delay; ?>">
                        <div class="relative overflow-hidden rounded-3xl bg-white/80 backdrop-blur-sm border border-white/50 hover-lift transition-all duration-500 shimmer-effect">
                            <!-- Product Image -->
                            <div class="relative overflow-hidden rounded-t-3xl">
                                <img src="<?php echo $productImage; ?>"
                                     alt="<?php echo htmlspecialchars($product['name']); ?>"
                                     class="w-full h-80 object-cover group-hover:scale-110 transition-transform duration-700 product-image-clickable cursor-pointer"
                                     onerror="this.src='<?php echo BASE_URL; ?>/public/images/product-placeholder-1.svg'"
                                     loading="lazy"
                                     data-product-name="<?php echo htmlspecialchars($product['name']); ?>"
                                     data-product-description="<?php echo htmlspecialchars($product['description']); ?>"
                                     title="Haz clic para ver en detalle">

                                <!-- Zoom icon -->
                                <div class="absolute top-4 left-16 opacity-0 group-hover:opacity-100 transition-opacity duration-300 z-10">
                                    <div class="bg-white/90 backdrop-blur-sm p-2 rounded-full shadow-lg">
                                        <svg class="w-4 h-4 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v3m0 0v3m0-3h3m-3 0H7"></path>
                                        </svg>
                                    </div>
                                </div>

                                <!-- Discount Badge -->
                                <?php if ($discount > 0): ?>
                                <div class="absolute top-4 left-4 z-10">
                                    <span class="bg-gradient-to-r from-red-500 to-pink-500 text-white px-3 py-1 rounded-full text-sm font-semibold shadow-lg">
                                        -<?php echo $discount; ?>%
                                    </span>
                                </div>
                                <?php endif; ?>

                                <!-- Quick Actions -->
                                <div class="absolute top-4 right-4 space-y-2 opacity-0 group-hover:opacity-100 transition-all duration-300 z-20">
                                    <button class="quick-action-btn w-12 h-12 bg-white/90 backdrop-blur-sm rounded-full flex items-center justify-center shadow-lg hover:bg-primary-500 hover:text-white transition-all duration-300 transform hover:scale-110 relative z-30"
                                            onclick="toggleWishlist(<?php echo $product['id']; ?>)">
                                        <i class="far fa-heart"></i>
                                    </button>
                                    <button class="quick-action-btn w-12 h-12 bg-white/90 backdrop-blur-sm rounded-full flex items-center justify-center shadow-lg hover:bg-primary-500 hover:text-white transition-all duration-300 transform hover:scale-110 relative z-30"
                                            onclick="quickView(<?php echo $product['id']; ?>)">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>

                                <!-- Overlay Gradient (moved to z-0 so it doesn't interfere with buttons) -->
                                <div class="absolute inset-0 bg-gradient-to-t from-black/20 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300 z-0 pointer-events-none"></div>
                            </div>

                            <!-- Product Info -->
                            <div class="p-6">
                                <?php if ($brandName): ?>
                                <p class="text-sm font-medium mb-2 tracking-wide uppercase text-secondary-600">
                                    <?php echo htmlspecialchars($brandName); ?>
                                </p>
                                <?php endif; ?>

                                <h3 class="font-semibold text-lg mb-3 line-clamp-2 text-gray-800 group-hover:text-primary-600 transition-colors">
                                    <?php echo htmlspecialchars($product['name']); ?>
                                </h3>

                                <!-- Rating -->
                                <div class="flex items-center mb-4">
                                    <div class="flex text-yellow-400 text-sm">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <i class="fas fa-star <?= $i <= round($averageRating) ? 'text-yellow-400' : 'text-gray-300' ?>"></i>
                                        <?php endfor; ?>
                                    </div>
                                    <span class="text-sm text-gray-500 ml-2">
                                        <?php if ($reviewCount > 0): ?>
                                            (<?= number_format($averageRating, 1) ?>) • <?= $reviewCount ?> reseña<?= $reviewCount != 1 ? 's' : '' ?>
                                        <?php else: ?>
                                            Sin reseñas
                                        <?php endif; ?>
                                    </span>
                                </div>

                                <!-- Price -->
                                <div class="flex items-center justify-between mb-6">
                                    <div class="flex items-center space-x-2">
                                        <span class="text-2xl font-bold text-primary-600">
                                            $<?php echo number_format($displayPrice, 0, ',', '.'); ?>
                                        </span>
                                        <?php if ($originalPrice): ?>
                                        <span class="text-lg line-through text-gray-400">
                                            $<?php echo number_format($originalPrice, 0, ',', '.'); ?>
                                        </span>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <!-- Add to Cart Button -->
                                <button class="w-full py-4 rounded-2xl font-semibold text-white transition-all duration-300 transform group-hover:scale-105 bg-gradient-to-r from-primary-500 to-secondary-500 hover:from-secondary-500 hover:to-primary-500 shadow-lg hover:shadow-xl"
                                        onclick="addToCart(<?php echo $product['id']; ?>)">
                                    <i class="fas fa-shopping-bag mr-2"></i>
                                    Agregar al Carrito
                                </button>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>

                <?php else: ?>
                    <!-- Empty State -->
                    <div class="col-span-full">
                        <div class="text-center py-20">
                            <div class="w-32 h-32 mx-auto mb-8 rounded-full flex items-center justify-center bg-gradient-to-r from-primary-100 to-secondary-100">
                                <i class="fas fa-box-open text-4xl text-primary-500"></i>
                            </div>
                            <h3 class="text-3xl font-serif font-bold mb-4 text-gray-800">
                                No hay productos disponibles
                            </h3>
                            <p class="text-xl mb-8 max-w-md mx-auto text-gray-600">
                                Los productos aparecerán aquí una vez que se agreguen desde el panel de administración.
                            </p>
                            <a href="admin/" class="inline-flex items-center px-8 py-4 rounded-2xl font-semibold text-white transition-all duration-300 bg-gradient-to-r from-primary-500 to-secondary-500 hover:shadow-xl transform hover:scale-105">
                                <span class="mr-2">Panel de Administración</span>
                                <i class="fas fa-external-link-alt"></i>
                            </a>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <?php if (!empty($featuredProducts)): ?>
            <div class="text-center" data-aos="fade-up" data-aos-delay="800">
                <a href="catalogo.php" class="group inline-flex items-center px-12 py-5 rounded-2xl font-semibold text-white transition-all duration-500 transform hover:-translate-y-2 luxury-shadow bg-gradient-to-r from-primary-500 to-secondary-500 hover:from-secondary-500 hover:to-primary-500">
                    <span class="mr-3">Ver Todos los Productos</span>
                    <svg class="w-5 h-5 transition-transform duration-300 group-hover:translate-x-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
                    </svg>
                </a>
            </div>
            <?php endif; ?>
        </div>
    </section>
    <!-- Mobile Featured Products -->
    <section class="md:hidden py-12 bg-gradient-to-br from-luxury-pearl/30 via-white to-luxury-champagne/30">
        <div class="container mx-auto px-4">
            <div class="text-center mb-8" data-aos="fade-up">
                <div class="inline-block mb-4">
                    <span class="text-xs font-medium tracking-widest uppercase text-secondary-600 bg-secondary-50 px-4 py-2 rounded-full animate-zoom-in">
                        Destacados
                    </span>
                </div>
                <h2 class="text-3xl font-serif font-bold mb-4 gradient-text animate-fade-in-up">
                    Productos Destacados
                </h2>
                <p class="text-gray-600 leading-relaxed font-light animate-fade-in-up" style="animation-delay: 200ms;">
                    Los favoritos de nuestras clientas
                </p>
            </div>

            <!-- Mobile Products Swiper -->
            <div class="swipe-indicator mb-6" data-aos="fade-up" data-aos-delay="200">
                <div class="flex overflow-x-auto hide-scrollbar space-x-4 pb-4" id="mobile-products-scroll">
                    <?php if (!empty($featuredProducts)): ?>
                        <?php foreach (array_slice($featuredProducts, 0, 6) as $index => $product):
                            $brandName = !empty($product['brand_name']) ? $product['brand_name'] : '';

                            // Get product rating data for mobile
                            $ratingData = $reviewModel->getAverageRating($product['id']);
                            $averageRating = $ratingData['average'] ?? 0;
                            $reviewCount = $ratingData['count'] ?? 0;

                            $discount = 0;
                            if (!empty($product['compare_price']) && $product['compare_price'] > $product['price']) {
                                $discount = round((($product['compare_price'] - $product['price']) / $product['compare_price']) * 100);
                            }

                            // Use the new placeholder images for static fallback
                            if (!empty($product['main_image'])) {
                                // Clean the image path to remove duplicated prefixes
                                $imagePath = $product['main_image'];
                                // Remove common incorrect prefixes
                                $imagePath = str_replace('uploads/products/', '', $imagePath);
                                $imagePath = str_replace('assets/images/products/', '', $imagePath);
                                $productImage = BASE_URL . '/uploads/products/' . $imagePath;
                            } else {
                                $productImage = BASE_URL . '/public/images/product-placeholder-2.svg';
                            }

                            $displayPrice = $product['price'];
                            $originalPrice = (!empty($product['compare_price']) && $product['compare_price'] > $product['price']) ? $product['compare_price'] : null;
                        ?>
                        <div class="flex-shrink-0 w-64 animate-fade-in-up" style="animation-delay: <?php echo 300 + ($index * 100); ?>ms;">
                            <div class="bg-white/90 backdrop-blur-sm rounded-3xl overflow-hidden mobile-card-shadow hover:shadow-lg transition-all duration-300 border border-white/50">
                                <!-- Mobile Product Image -->
                                <div class="relative overflow-hidden">
                                    <img src="<?php echo $productImage; ?>"
                                         alt="<?php echo htmlspecialchars($product['name']); ?>"
                                         class="w-full h-48 object-cover"
                                         onerror="this.src='<?php echo BASE_URL; ?>/public/images/product-placeholder-2.svg'"
                                         loading="lazy">

                                    <!-- Mobile Discount Badge -->
                                    <?php if ($discount > 0): ?>
                                    <div class="absolute top-3 left-3">
                                        <span class="bg-gradient-to-r from-red-500 to-pink-500 text-white px-2 py-1 rounded-full text-xs font-semibold shadow-lg">
                                            -<?php echo $discount; ?>%
                                        </span>
                                    </div>
                                    <?php endif; ?>

                                    <!-- Mobile Quick Actions -->
                                    <div class="absolute top-3 right-3 space-y-2 z-20">
                                        <button class="quick-action-btn touch-target w-10 h-10 bg-white/90 backdrop-blur-sm rounded-full flex items-center justify-center shadow-lg hover:bg-primary-500 hover:text-white transition-all duration-300 relative z-30"
                                                onclick="toggleWishlist(<?php echo $product['id']; ?>)">
                                            <i class="far fa-heart text-sm"></i>
                                        </button>
                                        <button class="quick-action-btn touch-target w-10 h-10 bg-white/90 backdrop-blur-sm rounded-full flex items-center justify-center shadow-lg hover:bg-primary-500 hover:text-white transition-all duration-300 relative z-30"
                                                onclick="quickView(<?php echo $product['id']; ?>)">
                                            <i class="fas fa-eye text-sm"></i>
                                        </button>
                                    </div>
                                </div>

                                <!-- Mobile Product Info -->
                                <div class="p-4">
                                    <?php if ($brandName): ?>
                                    <p class="text-xs font-medium mb-2 tracking-wide uppercase text-secondary-600">
                                        <?php echo htmlspecialchars($brandName); ?>
                                    </p>
                                    <?php endif; ?>

                                    <h3 class="font-semibold text-base mb-2 line-clamp-2 text-gray-800">
                                        <?php echo htmlspecialchars($product['name']); ?>
                                    </h3>

                                    <!-- Mobile Rating -->
                                    <div class="flex items-center mb-3">
                                        <div class="flex text-yellow-400 text-xs">
                                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                                <i class="fas fa-star <?= $i <= round($averageRating) ? 'text-yellow-400' : 'text-gray-300' ?>"></i>
                                            <?php endfor; ?>
                                        </div>
                                        <span class="text-xs text-gray-500 ml-2">
                                            <?php if ($reviewCount > 0): ?>
                                                (<?= number_format($averageRating, 1) ?>)
                                            <?php else: ?>
                                                Sin reseñas
                                            <?php endif; ?>
                                        </span>
                                    </div>

                                    <!-- Mobile Price -->
                                    <div class="flex items-center justify-between mb-4">
                                        <div class="flex items-center space-x-2">
                                            <span class="text-lg font-bold text-primary-600">
                                                $<?php echo number_format($displayPrice, 0, ',', '.'); ?>
                                            </span>
                                            <?php if ($originalPrice): ?>
                                            <span class="text-sm line-through text-gray-400">
                                                $<?php echo number_format($originalPrice, 0, ',', '.'); ?>
                                            </span>
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                    <!-- Mobile Add to Cart Button -->
                                    <button class="w-full py-3 rounded-2xl font-semibold text-white transition-all duration-300 bg-gradient-to-r from-primary-500 to-secondary-500 hover:shadow-lg text-sm"
                                            onclick="addToCart(<?php echo $product['id']; ?>)">
                                        <i class="fas fa-shopping-bag mr-2"></i>
                                        Agregar
                                    </button>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <!-- Mobile Empty State -->
                        <div class="flex-shrink-0 w-full">
                            <div class="text-center py-12">
                                <div class="w-20 h-20 mx-auto mb-6 rounded-full flex items-center justify-center bg-gradient-to-r from-primary-100 to-secondary-100">
                                    <i class="fas fa-box-open text-2xl text-primary-500"></i>
                                </div>
                                <h3 class="text-xl font-serif font-bold mb-3 text-gray-800">
                                    No hay productos disponibles
                                </h3>
                                <p class="text-gray-600 mb-6 text-sm">
                                    Los productos aparecerán aquí una vez que se agreguen.
                                </p>
                                <a href="admin/" class="inline-flex items-center px-6 py-3 rounded-2xl font-semibold text-white transition-all duration-300 bg-gradient-to-r from-primary-500 to-secondary-500 hover:shadow-lg text-sm">
                                    <span class="mr-2">Panel Admin</span>
                                    <i class="fas fa-external-link-alt text-xs"></i>
                                </a>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <?php if (!empty($featuredProducts)): ?>
            <div class="text-center" data-aos="fade-up" data-aos-delay="400">
                <a href="catalogo.php" class="inline-flex items-center bg-gradient-to-r from-primary-500 to-secondary-500 text-white px-8 py-4 rounded-2xl font-semibold hover:shadow-lg transform hover:scale-105 transition-all duration-300">
                    <span class="mr-2">Ver Todas</span>
                    <i class="fas fa-arrow-right text-sm"></i>
                </a>
            </div>
            <?php endif; ?>
        </div>
    </section>
    <!-- Newsletter -->
    <section class="py-24 relative overflow-hidden">
        <div class="absolute inset-0 bg-gradient-to-r from-primary-500 via-secondary-500 to-primary-500"></div>
        <div class="absolute inset-0 bg-black/20"></div>

        <div class="container mx-auto px-4 relative z-10">
            <div class="max-w-4xl mx-auto text-center text-white" data-aos="fade-up">
                <div class="mb-8">
                    <h2 class="text-4xl lg:text-6xl md:text-3xl font-serif font-bold mb-6 animate-fade-in-down">
                        Únete a la Comunidad OdiseaStore
                    </h2>
                    <p class="text-xl lg:text-2xl md:text-lg opacity-90 font-light leading-relaxed animate-fade-in-down" style="animation-delay: 200ms;">
                        Recibe ofertas exclusivas, novedades de productos y sé el primero en conocer nuestros nuevos lanzamientos
                    </p>
                </div>

                <div class="max-w-lg mx-auto mb-12 animate-fade-in-up" style="animation-delay: 400ms;">
                    <form id="newsletter-form" action="newsletter-subscribe.php" method="post" class="flex flex-col sm:flex-row gap-4">
                        <input type="hidden" name="csrf_token" id="newsletter-csrf-token" value="">
                        <div class="flex-1 relative">
                            <input type="email"
                                   name="email"
                                   id="newsletter-email"
                                   placeholder="Tu email aquí..."
                                   class="w-full px-8 py-5 md:px-6 md:py-4 rounded-2xl text-gray-900 focus:outline-none focus:ring-4 focus:ring-white/30 text-lg md:text-base bg-white/95 backdrop-blur-sm"
                                   required>
                        </div>
                        <button type="submit" id="newsletter-submit"
                                class="bg-white text-primary-500 px-10 py-5 md:px-8 md:py-4 rounded-2xl font-semibold text-lg md:text-base hover:bg-gray-100 transition-all duration-300 disabled:opacity-50 disabled:cursor-not-allowed transform hover:scale-105 shadow-lg">
                            <span class="submit-text">Suscribirme</span>
                            <span class="loading-text hidden">Enviando...</span>
                        </button>
                    </form>
                    <p class="text-sm mt-4 opacity-75">
                        Al suscribirte aceptas recibir emails promocionales. Puedes cancelar en cualquier momento.
                    </p>
                </div>

                <!-- Benefits -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                    <div class="text-center group animate-fade-in-up" style="animation-delay: 600ms;">
                        <div class="w-20 h-20 md:w-16 md:h-16 bg-white/20 backdrop-blur-sm rounded-2xl flex items-center justify-center mx-auto mb-6 md:mb-4 group-hover:scale-110 transition-transform duration-300">
                            <i class="fas fa-gift text-3xl md:text-2xl"></i>
                        </div>
                        <h3 class="font-semibold text-xl md:text-lg mb-3">Ofertas Exclusivas</h3>
                        <p class="opacity-90 font-light text-sm">Descuentos especiales solo para suscriptoras</p>
                    </div>
                    <div class="text-center group animate-fade-in-up" style="animation-delay: 700ms;">
                        <div class="w-20 h-20 md:w-16 md:h-16 bg-white/20 backdrop-blur-sm rounded-2xl flex items-center justify-center mx-auto mb-6 md:mb-4 group-hover:scale-110 transition-transform duration-300">
                            <i class="fas fa-star text-3xl md:text-2xl"></i>
                        </div>
                        <h3 class="font-semibold text-xl md:text-lg mb-3">Acceso Anticipado</h3>
                        <p class="opacity-90 font-light text-sm">Compra nuevos productos antes que nadie</p>
                    </div>
                    <div class="text-center group animate-fade-in-up" style="animation-delay: 800ms;">
                        <div class="w-20 h-20 md:w-16 md:h-16 bg-white/20 backdrop-blur-sm rounded-2xl flex items-center justify-center mx-auto mb-6 md:mb-4 group-hover:scale-110 transition-transform duration-300">
                            <i class="fas fa-heart text-3xl md:text-2xl"></i>
                        </div>
                        <h3 class="font-semibold text-xl md:text-lg mb-3">Tips de Belleza</h3>
                        <p class="opacity-90 font-light text-sm">Consejos y tutoriales de expertos</p>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!-- Footer -->
    <footer class="bg-gray-900 text-white relative overflow-hidden">
        <div class="absolute inset-0 bg-gradient-to-br from-gray-900 via-gray-800 to-gray-900"></div>

        <div class="container mx-auto px-4 py-20 md:py-12 relative z-10">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-12 md:gap-8">
                <!-- Company Info -->
                <div class="space-y-8 md:space-y-6">
                    <div>
                        <h3 class="text-3xl md:text-2xl font-serif font-bold gradient-text mb-6 md:mb-4">
                            OdiseaStore
                        </h3>
                        <p class="text-gray-300 leading-relaxed font-light text-lg md:text-base">
                            Tu destino para encontrar todo lo que necesitas. Descubre productos de calidad en tecnología, hogar, moda y mucho más con la experiencia que mereces.
                        </p>
                    </div>

                    <div class="space-y-4">
                        <div class="flex items-center space-x-4 group">
                            <div class="w-12 h-12 md:w-10 md:h-10 bg-gradient-to-r from-primary-500 to-secondary-500 rounded-xl flex items-center justify-center group-hover:scale-110 transition-transform duration-300">
                                <i class="fas fa-map-marker-alt text-lg md:text-base"></i>
                            </div>
                            <span class="text-gray-300">Barranquilla, Colombia</span>
                        </div>
                        <div class="flex items-center space-x-4 group">
                            <div class="w-12 h-12 md:w-10 md:h-10 bg-gradient-to-r from-primary-500 to-secondary-500 rounded-xl flex items-center justify-center group-hover:scale-110 transition-transform duration-300">
                                <i class="fas fa-phone text-lg md:text-base"></i>
                            </div>
                            <span class="text-gray-300"><?php echo htmlspecialchars($contactSettings['site_phone'] ?? '+57 300 123 4567'); ?></span>
                        </div>
                        <div class="flex items-center space-x-4 group">
                            <div class="w-12 h-12 md:w-10 md:h-10 bg-gradient-to-r from-primary-500 to-secondary-500 rounded-xl flex items-center justify-center group-hover:scale-110 transition-transform duration-300">
                                <i class="fas fa-envelope text-lg md:text-base"></i>
                            </div>
                            <span class="text-gray-300"><?php echo htmlspecialchars($contactSettings['site_email'] ?? 'contacto@odiseastore.com'); ?></span>
                        </div>
                    </div>
                </div>

                <!-- Quick Links -->
                <div>
                    <h4 class="text-xl md:text-lg font-semibold mb-8 md:mb-6 text-white">Enlaces Rápidos</h4>
                    <ul class="space-y-4 md:space-y-3">
                        <li><a href="sobre-nosotros.php" class="text-gray-300 hover:text-primary-400 transition-colors duration-300 font-light hover:translate-x-1 transform block">Sobre Nosotros</a></li>
                        <li><a href="productos.php" class="text-gray-300 hover:text-primary-400 transition-colors duration-300 font-light hover:translate-x-1 transform block">Catálogo</a></li>
                        <li><a href="ofertas.php" class="text-gray-300 hover:text-primary-400 transition-colors duration-300 font-light hover:translate-x-1 transform block">Ofertas</a></li>
                        <li><a href="contacto.php" class="text-gray-300 hover:text-primary-400 transition-colors duration-300 font-light hover:translate-x-1 transform block">Contacto</a></li>
                    </ul>
                </div>

                <!-- Customer Service -->
                <div>
                    <h4 class="text-xl md:text-lg font-semibold mb-8 md:mb-6 text-white">Atención al Cliente</h4>
                    <ul class="space-y-4 md:space-y-3">
                        <li><a href="mi-cuenta.php" class="text-gray-300 hover:text-primary-400 transition-colors duration-300 font-light hover:translate-x-1 transform block">Mi Cuenta</a></li>
                        <li><a href="mis-pedidos.php" class="text-gray-300 hover:text-primary-400 transition-colors duration-300 font-light hover:translate-x-1 transform block">Mis Pedidos</a></li>
                        <li><a href="envios-devoluciones.php" class="text-gray-300 hover: text-primary-400 transition-colors duration-300 font-light hover:translate-x-1 transform block">Envíos y Devoluciones</a></li>
                        <li><a href="faq.php" class="text-gray-300 hover:text-primary-400 transition-colors duration-300 font-light hover:translate-x-1 transform block">Preguntas Frecuentes</a></li>
                        <li><a href="terminos.php" class="text-gray-300 hover:text-primary-400 transition-colors duration-300 font-light hover:translate-x-1 transform block">Términos y Condiciones</a></li>
                    </ul>
                </div>

                <!-- Social & Payment -->
                <div>
                    <h4 class="text-xl md:text-lg font-semibold mb-8 md:mb-6 text-white">Síguenos</h4>
                    <div class="flex space-x-4 mb-10 md:mb-8">
                        <?php if (!empty($contactSettings['social_instagram'])): ?>
                        <a href="<?php echo htmlspecialchars($contactSettings['social_instagram']); ?>" target="_blank"
                           class="w-12 h-12 md:w-10 md:h-10 bg-gradient-to-r from-primary-500 to-secondary-500 rounded-xl flex items-center justify-center hover:scale-110 transition-all duration-300 shadow-lg"
                           title="Instagram">
                            <i class="fab fa-instagram text-lg md:text-base"></i>
                        </a>
                        <?php endif; ?>

                        <?php if (!empty($contactSettings['social_facebook'])): ?>
                        <a href="<?php echo htmlspecialchars($contactSettings['social_facebook']); ?>" target="_blank"
                           class="w-12 h-12 md:w-10 md:h-10 bg-gradient-to-r from-primary-500 to-secondary-500 rounded-xl flex items-center justify-center hover:scale-110 transition-all duration-300 shadow-lg"
                           title="Facebook">
                            <i class="fab fa-facebook text-lg md:text-base"></i>
                        </a>
                        <?php endif; ?>

                        <?php if (!empty($contactSettings['social_tiktok'])): ?>
                        <a href="<?php echo htmlspecialchars($contactSettings['social_tiktok']); ?>" target="_blank"
                           class="w-12 h-12 md:w-10 md:h-10 bg-gradient-to-r from-primary-500 to-secondary-500 rounded-xl flex items-center justify-center hover:scale-110 transition-all duration-300 shadow-lg"
                           title="TikTok">
                            <i class="fab fa-tiktok text-lg md:text-base"></i>
                        </a>
                        <?php endif; ?>

                        <?php if (!empty($contactSettings['social_youtube'])): ?>
                        <a href="<?php echo htmlspecialchars($contactSettings['social_youtube']); ?>" target="_blank"
                           class="w-12 h-12 md:w-10 md:h-10 bg-gradient-to-r from-primary-500 to-secondary-500 rounded-xl flex items-center justify-center hover:scale-110 transition-all duration-300 shadow-lg"
                           title="YouTube">
                            <i class="fab fa-youtube text-lg md:text-base"></i>
                        </a>
                        <?php endif; ?>

                        <?php if (!empty($contactSettings['social_twitter'])): ?>
                        <a href="<?php echo htmlspecialchars($contactSettings['social_twitter']); ?>" target="_blank"
                           class="w-12 h-12 md:w-10 md:h-10 bg-gradient-to-r from-primary-500 to-secondary-500 rounded-xl flex items-center justify-center hover:scale-110 transition-all duration-300 shadow-lg"
                           title="Twitter">
                            <i class="fab fa-twitter text-lg md:text-base"></i>
                        </a>
                        <?php endif; ?>
                    </div>

                    <h5 class="font-semibold mb-6 md:mb-4 text-white">Métodos de Pago</h5>
                    <div class="grid grid-cols-3 gap-3 md:gap-2">
                        <div class="bg-white rounded-xl p-3 md:p-2 flex items-center justify-center hover:scale-105 transition-transform duration-300">
                            <i class="fab fa-cc-visa text-blue-600 text-2xl md:text-xl"></i>
                        </div>
                        <div class="bg-white rounded-xl p-3 md:p-2 flex items-center justify-center hover:scale-105 transition-transform duration-300">
                            <i class="fab fa-cc-mastercard text-red-600 text-2xl md:text-xl"></i>
                        </div>
                        <div class="bg-white rounded-xl p-3 md:p-2 flex items-center justify-center hover:scale-105 transition-transform duration-300">
                            <i class="fab fa-paypal text-blue-500 text-2xl md:text-xl"></i>
                        </div>
                        <div class="bg-white rounded-xl p-3 md:p-2 flex items-center justify-center text-xs font-bold text-gray-700 hover:scale-105 transition-transform duration-300">
                            NEQUI
                        </div>
                        <div class="bg-white rounded-xl p-3 md:p-2 flex items-center justify-center text-xs font-bold text-gray-700 hover:scale-105 transition-transform duration-300">
                            DAVIPLATA
                        </div>
                        <div class="bg-white rounded-xl p-3 md:p-2 flex items-center justify-center text-xs font-bold text-gray-700 hover:scale-105 transition-transform duration-300">
                            BANCOLOMBIA
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bottom Footer -->
            <div class="border-t border-gray-800 mt-16 md:mt-12 pt-8 md:pt-6">
                <div class="flex flex-col md:flex-row justify-between items-center">
                    <p class="text-gray-400 font-light text-center md:text-left">
                        © <?php echo date('Y'); ?> OdiseaStore. Todos los derechos reservados.
                    </p>
                    <div class="flex flex-wrap justify-center space-x-8 md:space-x-6 mt-4 md:mt-0">
                        <a href="privacidad.php" class="text-gray-400 hover:text-primary-400 font-light transition-colors duration-300 text-sm">Política de Privacidad</a>
                        <a href="terminos.php" class="text-gray-400 hover:text-primary-400 font-light transition-colors duration-300 text-sm">Términos de Uso</a>
                        <a href="cookies.php" class="text-gray-400 hover:text-primary-400 font-light transition-colors duration-300 text-sm">Cookies</a>
                    </div>
                </div>
            </div>
        </div>
    </footer>
    
    <!-- Newsletter Form Handler -->
    <script>
        // Initialize CSRF token for newsletter
        window.globalCSRFToken = '<?php echo CSRFProtection::generateGlobalToken(); ?>';
        
        // Set initial CSRF token
        document.addEventListener('DOMContentLoaded', function() {
            const csrfInput = document.getElementById('newsletter-csrf-token');
            if (csrfInput) {
                csrfInput.value = window.globalCSRFToken;
            }
        });
        
        // Handle newsletter form submission
        document.getElementById('newsletter-form')?.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const form = this;
            const submitBtn = document.getElementById('newsletter-submit');
            const submitText = submitBtn.querySelector('.submit-text');
            const loadingText = submitBtn.querySelector('.loading-text');
            const emailInput = document.getElementById('newsletter-email');
            
            // Update CSRF token before sending
            const csrfInput = document.getElementById('newsletter-csrf-token');
            if (csrfInput) {
                csrfInput.value = window.globalCSRFToken;
            }
            
            // Disable submit button and show loading
            submitBtn.disabled = true;
            submitText.classList.add('hidden');
            loadingText.classList.remove('hidden');
            
            // Prepare form data
            const formData = new FormData(form);
            
            fetch('newsletter-subscribe.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Show success message
                    emailInput.value = '';
                    showNotification('¡Suscripción exitosa! Revisa tu email.', 'success');
                } else {
                    // Show error message
                    showNotification(data.message || 'Error en la suscripción', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('Error al procesar la suscripción', 'error');
            })
            .finally(() => {
                // Re-enable submit button
                submitBtn.disabled = false;
                submitText.classList.remove('hidden');
                loadingText.classList.add('hidden');
            });
        });
        
        // Notification function for newsletter
        function showNotification(message, type = 'info') {
            const notification = document.createElement('div');
            const colors = {
                success: 'bg-green-500',
                error: 'bg-red-500',
                warning: 'bg-yellow-500',
                info: 'bg-blue-500'
            };
            
            notification.className = `fixed top-4 right-4 z-50 p-4 rounded-lg text-white max-w-sm transition-all duration-300 ${colors[type] || colors.info}`;
            notification.innerHTML = message;
            
            document.body.appendChild(notification);
            
            setTimeout(() => {
                notification.style.opacity = '0';
                setTimeout(() => {
                    if (notification.parentNode) {
                        document.body.removeChild(notification);
                    }
                }, 300);
            }, 3000);
        }
    </script>
    
    <!-- WhatsApp Floating Button -->
    <div class="floating-whatsapp fixed bottom-8 right-8 md:bottom-6 md:right-6 z-[9999]">
        <a href="https://wa.me/<?php echo str_replace(['+', ' '], '', $contactSettings['site_phone'] ?? '573001234567'); ?>?text=Hola,%20me%20interesa%20conocer%20más%20sobre%20sus%20productos"
           target="_blank"
           class="w-16 h-16 md:w-14 md:h-14 bg-gradient-to-r from-green-500 to-green-600 rounded-full flex items-center justify-center text-white shadow-2xl hover:shadow-3xl transform hover:scale-110 transition-all duration-300 animate-bounce">
            <i class="fab fa-whatsapp text-2xl md:text-xl"></i>
        </a>
    </div>
    <!-- Back to Top Button -->
    <button class="fixed bottom-8 left-8 md:bottom-6 md:left-6 w-14 h-14 md:w-12 md:h-12 bg-gradient-to-r from-primary-500 to-secondary-500 text-white rounded-full flex items-center justify-center shadow-2xl hover:shadow-3xl transform hover:scale-110 transition-all duration-300 opacity-0 invisible z-[9998]" id="back-to-top">
        <i class="fas fa-chevron-up text-lg md:text-base"></i>
    </button>
    <!-- Scripts -->
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script src="<?php echo BASE_URL; ?>/assets/js/main.js"></script>
    
    <!-- Debug Script for QuickView -->
    <script>
        // Define quickView function immediately
        window.quickView = function(productId) {
            console.log('quickView called with productId:', productId);
            
            if (!productId) {
                console.error('No product ID provided');
                return;
            }
            
            // Updated to use product.php
            const url = `product.php?id=${productId}`;
            console.log('Redirecting to:', url);
            window.location.href = url;
        };
        
        // Generate CSRF token for AJAX requests
        function getCSRFToken() {
            return '<?php 
            $csrf = new CSRFProtection();
            echo $csrf->generateToken(); 
            ?>';
        }
        
        // Also define other essential functions
        window.toggleWishlist = function(productId) {
            const csrfToken = getCSRFToken();
            
            if (!csrfToken) {
                showNotification('Error de seguridad. Recarga la página.', 'error');
                return;
            }
            
            fetch('wishlist-toggle.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `product_id=${productId}&csrf_token=${csrfToken}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    if (data.action === 'added') {
                        showNotification('Producto agregado a favoritos', 'success');
                    } else {
                        showNotification('Producto removido de favoritos', 'info');
                    }
                } else {
                    showNotification(data.message || 'Error al procesar favoritos', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('Error al procesar favoritos', 'error');
            });
        };
        
        window.addToCart = function(productId, quantity = 1) {
            console.log('addToCart llamado con:', productId, quantity);
            
            fetch('cart-add-simple.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `product_id=${productId}&quantity=${quantity}`
            })
            .then(response => {
                console.log('Response status:', response.status);
                console.log('Response headers:', response.headers.get('content-type'));
                
                // Check if response is ok
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                // Check if content type is JSON
                const contentType = response.headers.get('content-type');
                if (!contentType || !contentType.includes('application/json')) {
                    console.error('Response is not JSON:', response);
                    return response.text().then(text => {
                        console.error('Response text:', text);
                        throw new Error('Invalid response format');
                    });
                }
                
                return response.json();
            })
            .then(data => {
                console.log('Response data:', data);
                if (data.success) {
                    showNotification('Producto agregado al carrito', 'success');
                    updateCartCount();
                } else {
                    showNotification(data.message || 'Error al agregar producto', 'error');
                }
            })
            .catch(error => {
                console.error('Error en addToCart:', error);
                showNotification('Error al conectar con el servidor', 'error');
            });
        };
        
        // Debug function availability
        document.addEventListener('DOMContentLoaded', function() {
            console.log('DOM loaded, checking functions...');
            console.log('quickView available:', typeof window.quickView === 'function');
            console.log('toggleWishlist available:', typeof window.toggleWishlist === 'function');
            console.log('addToCart available:', typeof window.addToCart === 'function');
        });
        
        // Global error handler for onclick events
        window.onerror = function(msg, url, lineNo, columnNo, error) {
            console.error('JavaScript Error:', {
                message: msg,
                source: url,
                line: lineNo,
                column: columnNo,
                error: error
            });
            return false;
        };
    </script>

    <!-- Image Lightbox Modal -->
    <div id="imageLightbox" class="fixed inset-0 bg-black bg-opacity-75 z-50 hidden opacity-0 transition-opacity duration-300 flex items-center justify-center">
        <div class="relative max-w-4xl max-h-screen p-4 w-full h-full flex items-center justify-center">
            <!-- Close button -->
            <button onclick="closeLightbox()" class="absolute top-4 right-4 text-white hover:text-gray-300 z-60 bg-black bg-opacity-50 rounded-full p-2 transition-colors duration-200 touch-target">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>

            <!-- Image container -->
            <div class="bg-white rounded-2xl overflow-hidden shadow-2xl max-w-full max-h-full flex flex-col">
                <div class="flex-1 flex items-center justify-center p-4">
                    <img id="lightboxImage"
                         src="/placeholder.svg"
                         alt=""
                         class="max-w-full max-h-96 md:max-h-80 object-contain rounded-lg">
                </div>

                <!-- Product info -->
                <div class="p-6 md:p-4 bg-gradient-to-r from-pink-50 to-purple-50 border-t">
                    <h3 id="lightboxTitle" class="text-xl md:text-lg font-bold text-gray-900 mb-2"></h3>
                    <p id="lightboxDescription" class="text-gray-600 text-sm"></p>
                </div>
            </div>
        </div>
    </div>
    <!-- Banner Flotante de Noticias -->
    <?php if (!empty($featuredNews)): ?>
    <?php $news = $featuredNews[0]; ?>
    <div id="newsFloatingBanner" 
         data-news-id="<?php echo $news['id']; ?>"
         class="fixed top-4 right-4 z-50 max-w-sm bg-white rounded-xl shadow-2xl border border-gray-200 transform translate-x-full transition-all duration-500 ease-in-out opacity-0"
         style="right: -400px;">
        <div class="relative overflow-hidden">&nbsp;
            <!-- Botón cerrar -->
            <button onclick="closeNewsBanner()" class="absolute top-2 right-2 w-8 h-8 bg-gray-100 hover:bg-gray-200 rounded-full flex items-center justify-center transition-colors z-10">
                <i class="fas fa-times text-gray-600 text-sm"></i>
            </button>

            <!-- Contenido de la noticia -->
            <div class="p-4">
                <?php if (!empty($news['featured_image'])): ?>
                <div class="mb-3">
                    <img src="<?php echo BASE_URL; ?>/<?php echo $news['featured_image']; ?>"
                          alt="<?php echo htmlspecialchars($news['title']); ?>"
                         class="w-full h-32 object-cover rounded-lg">
                </div>
                <?php endif; ?>

                <div class="mb-2">
                    <span class="inline-block bg-primary-100 text-primary-700 px-2 py-1 rounded-full text-xs font-semibold">
                        <i class="fas fa-newspaper mr-1"></i>
                        Noticia Destacada
                    </span>
                </div>

                <h3 class="font-bold text-gray-900 text-sm mb-2 line-clamp-2">
                    <?php echo htmlspecialchars($news['title']); ?>
                </h3>

                <p class="text-gray-600 text-xs mb-3 line-clamp-3">
                    <?php echo htmlspecialchars($news['excerpt']); ?>
                </p>

                <div class="flex justify-between items-center">
                    <small class="text-gray-500 text-xs">
                        <?php echo date('d M Y', strtotime($news['published_at'])); ?>
                    </small>
                    <a href="news.php?slug=<?php echo $news['slug']; ?>"
                        class="text-primary-600 hover:text-primary-700 text-xs font-semibold hover:underline">
                        Leer más →
                    </a>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Banner Carousel Functionality -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Desktop carousel
            const desktopCarousel = document.getElementById('bannerCarousel');
            const desktopSlides = desktopCarousel ? desktopCarousel.querySelectorAll('.banner-slide') : [];
            const desktopIndicators = document.querySelectorAll('.carousel-indicator');
            
            // Mobile carousel
            const mobileCarousel = document.getElementById('mobileBannerCarousel');
            const mobileSlides = mobileCarousel ? mobileCarousel.querySelectorAll('.mobile-banner-slide') : [];
            const mobileIndicators = document.querySelectorAll('.mobile-carousel-indicator');
            const mobilePrevBtn = document.querySelector('.mobile-carousel-prev');
            const mobileNextBtn = document.querySelector('.mobile-carousel-next');

            let currentDesktopSlide = 0;
            let currentMobileSlide = 0;

            // Desktop carousel functions
            function showDesktopSlide(index) {
                if (desktopSlides.length <= 1) return;
                
                // Hide all slides
                desktopSlides.forEach(slide => {
                    slide.classList.remove('active');
                    slide.style.opacity = '0';
                });
                
                // Show current slide
                if (desktopSlides[index]) {
                    desktopSlides[index].classList.add('active');
                    desktopSlides[index].style.opacity = '1';
                }
                
                // Update indicators
                desktopIndicators.forEach((indicator, i) => {
                    if (i === index) {
                        indicator.classList.remove('bg-white/50');
                        indicator.classList.add('bg-white');
                    } else {
                        indicator.classList.remove('bg-white');
                        indicator.classList.add('bg-white/50');
                    }
                });
                
                currentDesktopSlide = index;
            }

            // Mobile carousel functions
            function showMobileSlide(index) {
                if (mobileSlides.length <= 1) return;
                
                // Hide all slides
                mobileSlides.forEach(slide => {
                    slide.classList.remove('active');
                    slide.style.opacity = '0';
                });
                
                // Show current slide
                if (mobileSlides[index]) {
                    mobileSlides[index].classList.add('active');
                    mobileSlides[index].style.opacity = '1';
                }
                
                // Update indicators
                mobileIndicators.forEach((indicator, i) => {
                    if (i === index) {
                        indicator.classList.remove('bg-white/50');
                        indicator.classList.add('bg-white');
                    } else {
                        indicator.classList.remove('bg-white');
                        indicator.classList.add('bg-white/50');
                    }
                });
                
                currentMobileSlide = index;
            }

            // Auto-advance carousels
            function nextDesktopSlide() {
                if (desktopSlides.length > 1) {
                    const nextIndex = (currentDesktopSlide + 1) % desktopSlides.length;
                    showDesktopSlide(nextIndex);
                }
            }

            function nextMobileSlide() {
                if (mobileSlides.length > 1) {
                    const nextIndex = (currentMobileSlide + 1) % mobileSlides.length;
                    showMobileSlide(nextIndex);
                }
            }

            // Desktop indicator clicks
            desktopIndicators.forEach((indicator, index) => {
                indicator.addEventListener('click', () => showDesktopSlide(index));
            });

            // Mobile controls
            if (mobilePrevBtn) {
                mobilePrevBtn.addEventListener('click', () => {
                    const prevIndex = currentMobileSlide === 0 ? mobileSlides.length - 1 : currentMobileSlide - 1;
                    showMobileSlide(prevIndex);
                });
            }

            if (mobileNextBtn) {
                mobileNextBtn.addEventListener('click', () => {
                    const nextIndex = (currentMobileSlide + 1) % mobileSlides.length;
                    showMobileSlide(nextIndex);
                });
            }

            // Mobile indicator clicks
            mobileIndicators.forEach((indicator, index) => {
                indicator.addEventListener('click', () => showMobileSlide(index));
            });

            // Auto-advance both carousels every 5 seconds
            if (desktopSlides.length > 1 || mobileSlides.length > 1) {
                setInterval(() => {
                    nextDesktopSlide();
                    nextMobileSlide();
                }, 5000);
            }

            // Pause auto-advance on hover (desktop only)
            if (desktopCarousel) {
                let autoAdvanceDesktop = true;
                desktopCarousel.addEventListener('mouseenter', () => autoAdvanceDesktop = false);
                desktopCarousel.addEventListener('mouseleave', () => autoAdvanceDesktop = true);
            }

            // Touch support for mobile carousel
            if (mobileCarousel) {
                let startX = 0;
                let endX = 0;

                mobileCarousel.addEventListener('touchstart', (e) => {
                    startX = e.touches[0].clientX;
                });

                mobileCarousel.addEventListener('touchend', (e) => {
                    endX = e.changedTouches[0].clientX;
                    const diff = startX - endX;

                    if (Math.abs(diff) > 50) { // Minimum swipe distance
                        if (diff > 0) {
                            // Swipe left - next slide
                            nextMobileSlide();
                        } else {
                            // Swipe right - previous slide
                            const prevIndex = currentMobileSlide === 0 ? mobileSlides.length - 1 : currentMobileSlide - 1;
                            showMobileSlide(prevIndex);
                        }
                    }
                });
            }
        });
    </script>

    <!-- Mobile Categories Carousel Script -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const categoriesCarousel = document.getElementById('mobile-categories-carousel');
            const categoriesIndicators = document.querySelectorAll('.mobile-category-indicator');
            const categoriesPrevBtn = document.getElementById('mobile-categories-prev');
            const categoriesNextBtn = document.getElementById('mobile-categories-next');
            
            if (!categoriesCarousel) return;
            
            let currentCategorySlide = 0;
            const totalSlides = categoriesIndicators.length;
            
            function showCategorySlide(index) {
                if (totalSlides <= 1) return;
                
                // Calculate transform percentage to center the slide
                const translateX = -index * 100;
                categoriesCarousel.style.transform = `translateX(${translateX}%)`;
                
                // Update indicators
                categoriesIndicators.forEach((indicator, i) => {
                    if (i === index) {
                        indicator.classList.remove('bg-gray-300', 'w-2');
                        indicator.classList.add('bg-primary-500', 'w-6');
                    } else {
                        indicator.classList.remove('bg-primary-500', 'w-6');
                        indicator.classList.add('bg-gray-300', 'w-2');
                    }
                });
                
                currentCategorySlide = index;
            }
            
            // Navigation button events
            if (categoriesPrevBtn) {
                categoriesPrevBtn.addEventListener('click', () => {
                    const prevIndex = currentCategorySlide === 0 ? totalSlides - 1 : currentCategorySlide - 1;
                    showCategorySlide(prevIndex);
                });
            }
            
            if (categoriesNextBtn) {
                categoriesNextBtn.addEventListener('click', () => {
                    const nextIndex = (currentCategorySlide + 1) % totalSlides;
                    showCategorySlide(nextIndex);
                });
            }
            
            // Indicator clicks
            categoriesIndicators.forEach((indicator, index) => {
                indicator.addEventListener('click', () => showCategorySlide(index));
            });
            
            // Touch/swipe support
            let startX = 0;
            let endX = 0;
            
            categoriesCarousel.addEventListener('touchstart', (e) => {
                startX = e.touches[0].clientX;
            });
            
            categoriesCarousel.addEventListener('touchend', (e) => {
                endX = e.changedTouches[0].clientX;
                const diff = startX - endX;
                
                if (Math.abs(diff) > 50) { // Minimum swipe distance
                    if (diff > 0) {
                        // Swipe left - next slide
                        const nextIndex = (currentCategorySlide + 1) % totalSlides;
                        showCategorySlide(nextIndex);
                    } else {
                        // Swipe right - previous slide
                        const prevIndex = currentCategorySlide === 0 ? totalSlides - 1 : currentCategorySlide - 1;
                        showCategorySlide(prevIndex);
                    }
                }
            });
        });
    </script>

    <!-- Search functionality script -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Mobile search toggle
            const mobileSearchBtn = document.getElementById('mobile-search-btn');
            const mobileSearchBar = document.getElementById('mobile-search-bar');
            
            if (mobileSearchBtn && mobileSearchBar) {
                mobileSearchBtn.addEventListener('click', function() {
                    if (mobileSearchBar.classList.contains('hidden')) {
                        mobileSearchBar.classList.remove('hidden');
                        // Focus on the search input
                        const searchInput = mobileSearchBar.querySelector('input[name="q"]');
                        if (searchInput) {
                            setTimeout(() => searchInput.focus(), 100);
                        }
                    } else {
                        mobileSearchBar.classList.add('hidden');
                    }
                });
            }
            
            // Real-time search functionality
            let searchTimeout;
            const desktopSearchInput = document.getElementById('desktop-search-input');
            const mobileSearchInput = document.getElementById('mobile-search-input');
            const desktopResults = document.getElementById('desktop-search-results');
            const mobileResults = document.getElementById('mobile-search-results');
            
            function performSearch(query, resultsContainer) {
                if (query.length < 2) {
                    hideResults(resultsContainer);
                    return;
                }
                
                // Show loading state
                showLoading(resultsContainer);
                
                fetch(`api/search.php?q=${encodeURIComponent(query)}`)
                    .then(response => response.json())
                    .then(data => {
                        displayResults(data, resultsContainer);
                    })
                    .catch(error => {
                        console.error('Search error:', error);
                        hideResults(resultsContainer);
                    });
            }
            
            function showLoading(container) {
                container.innerHTML = `
                    <div class="p-4">
                        <div class="animate-pulse">
                            <div class="h-4 bg-gray-200 rounded w-3/4 mb-2"></div>
                            <div class="h-4 bg-gray-200 rounded w-1/2 mb-2"></div>
                            <div class="h-4 bg-gray-200 rounded w-2/3"></div>
                        </div>
                    </div>
                `;
                container.classList.remove('hidden');
            }
            
            function hideResults(container) {
                container.classList.add('hidden');
            }
            
            function displayResults(data, container) {
                if (!data.products.length && !data.brands.length && !data.categories.length) {
                    container.innerHTML = `
                        <div class="p-4 text-center text-gray-500">
                            <i class="fas fa-search text-2xl mb-2"></i>
                            <p>No se encontraron resultados</p>
                        </div>
                    `;
                    container.classList.remove('hidden');
                    return;
                }
                
                let html = '';
                
                // Categories section
                if (data.categories.length > 0) {
                    html += `
                        <div class="p-3 border-b border-gray-100">
                            <h3 class="text-sm font-semibold text-gray-600 mb-2">Categorías</h3>
                            <div class="space-y-1">
                    `;
                    data.categories.forEach(category => {
                        html += `
                            <a href="${category.url}" class="flex items-center p-2 hover:bg-gray-50 rounded-lg transition-colors">
                                <i class="${category.icon || 'fas fa-tag'} text-primary-500 w-4 text-center mr-3"></i>
                                <span class="text-sm text-gray-700">${category.name}</span>
                            </a>
                        `;
                    });
                    html += '</div></div>';
                }
                
                // Brands section
                if (data.brands.length > 0) {
                    html += `
                        <div class="p-3 border-b border-gray-100">
                            <h3 class="text-sm font-semibold text-gray-600 mb-2">Marcas</h3>
                            <div class="space-y-1">
                    `;
                    data.brands.forEach(brand => {
                        html += `
                            <a href="${brand.url}" class="flex items-center p-2 hover:bg-gray-50 rounded-lg transition-colors">
                                <div class="w-6 h-6 bg-gray-100 rounded-full flex items-center justify-center mr-3">
                                    ${brand.logo ? `<img src="${brand.logo}" alt="${brand.name}" class="w-4 h-4 rounded-full object-cover">` : '<i class="fas fa-store text-xs text-gray-400"></i>'}
                                </div>
                                <span class="text-sm text-gray-700">${brand.name}</span>
                            </a>
                        `;
                    });
                    html += '</div></div>';
                }
                
                // Products section
                if (data.products.length > 0) {
                    html += `
                        <div class="p-3">
                            <h3 class="text-sm font-semibold text-gray-600 mb-2">Productos</h3>
                            <div class="space-y-2">
                    `;
                    data.products.forEach(product => {
                        const price = product.sale_price && product.sale_price < product.price ? product.sale_price : product.price;
                        const originalPrice = product.sale_price && product.sale_price < product.price ? product.price : null;
                        
                        html += `
                            <a href="${product.url}" class="flex items-center p-2 hover:bg-gray-50 rounded-lg transition-colors">
                                <div class="w-12 h-12 bg-gray-100 rounded-lg mr-3 overflow-hidden">
                                    ${product.image ? `<img src="${product.image}" alt="${product.name}" class="w-full h-full object-cover">` : '<i class="fas fa-image text-gray-400 flex items-center justify-center w-full h-full"></i>'}
                                </div>
                                <div class="flex-1 min-w-0">
                                    <h4 class="text-sm font-medium text-gray-900 truncate">${product.name}</h4>
                                    <p class="text-xs text-gray-500">${product.brand_name || ''} • ${product.category_name || ''}</p>
                                    <div class="flex items-center mt-1">
                                        <span class="text-sm font-semibold text-primary-600">$${Number(price).toLocaleString()}</span>
                                        ${originalPrice ? `<span class="text-xs text-gray-400 line-through ml-2">$${Number(originalPrice).toLocaleString()}</span>` : ''}
                                    </div>
                                </div>
                            </a>
                        `;
                    });
                    html += '</div>';
                    
                    if (data.products.length >= 8) {
                        html += `
                            <div class="mt-3 pt-3 border-t border-gray-100">
                                <a href="catalogo.php?q=${encodeURIComponent(desktopSearchInput.value || mobileSearchInput.value)}" class="block text-center text-sm text-primary-600 hover:text-primary-700 font-medium">
                                    Ver todos los resultados
                                </a>
                            </div>
                        `;
                    }
                    html += '</div>';
                }
                
                container.innerHTML = html;
                container.classList.remove('hidden');
            }
            
            // Desktop search input
            if (desktopSearchInput) {
                desktopSearchInput.addEventListener('input', function() {
                    clearTimeout(searchTimeout);
                    const query = this.value.trim();
                    
                    searchTimeout = setTimeout(() => {
                        performSearch(query, desktopResults);
                    }, 300);
                });
                
                desktopSearchInput.addEventListener('focus', function() {
                    const query = this.value.trim();
                    if (query.length >= 2) {
                        performSearch(query, desktopResults);
                    }
                });
            }
            
            // Mobile search input
            if (mobileSearchInput) {
                mobileSearchInput.addEventListener('input', function() {
                    clearTimeout(searchTimeout);
                    const query = this.value.trim();
                    
                    searchTimeout = setTimeout(() => {
                        performSearch(query, mobileResults);
                    }, 300);
                });
                
                mobileSearchInput.addEventListener('focus', function() {
                    const query = this.value.trim();
                    if (query.length >= 2) {
                        performSearch(query, mobileResults);
                    }
                });
            }
            
            // Add keyboard shortcut for search (Ctrl+K or Cmd+K)
            document.addEventListener('keydown', function(e) {
                if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
                    e.preventDefault();
                    // Focus on desktop search input
                    if (desktopSearchInput && window.innerWidth >= 768) {
                        desktopSearchInput.focus();
                    } else if (mobileSearchBar) {
                        // Show mobile search and focus
                        mobileSearchBar.classList.remove('hidden');
                        if (mobileSearchInput) {
                            setTimeout(() => mobileSearchInput.focus(), 100);
                        }
                    }
                }
                
                // Hide results on escape
                if (e.key === 'Escape') {
                    hideResults(desktopResults);
                    hideResults(mobileResults);
                    desktopSearchInput?.blur();
                    mobileSearchInput?.blur();
                }
            });
            
            // Hide results when clicking outside
            document.addEventListener('click', function(e) {
                if (!e.target.closest('#desktop-search-input') && !e.target.closest('#desktop-search-results')) {
                    hideResults(desktopResults);
                }
                if (!e.target.closest('#mobile-search-input') && !e.target.closest('#mobile-search-results')) {
                    hideResults(mobileResults);
                }
                
                // Hide mobile search when clicking outside
                if (mobileSearchBar && !mobileSearchBar.contains(e.target) && !mobileSearchBtn?.contains(e.target)) {
                    mobileSearchBar.classList.add('hidden');
                }
            });
        });
    </script>

    <?php include 'includes/header-scroll-animation.php'; ?>
</body>
</html>
