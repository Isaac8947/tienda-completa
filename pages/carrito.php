<?php
// Configuración de sesión segura
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 0); // Cambiar a 1 en HTTPS
ini_set('session.cookie_samesite', 'Strict');

session_start();

require_once 'config/database.php';
require_once 'config/global-settings.php';
require_once 'models/Product.php';

// Obtener datos del carrito
$cart = $_SESSION['cart'] ?? [];
$subtotal = 0;
$cartCount = 0;

foreach ($cart as $item) {
    if (isset($item['quantity']) && isset($item['price'])) {
        $subtotal += $item['price'] * $item['quantity'];
        $cartCount += $item['quantity'];
    }
}

$tax = $subtotal * 0.19; // 19% IVA
$shipping = $subtotal > 0 ? 15000 : 0;
$total = $subtotal + $tax + $shipping;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <title>Carrito de Compras - Odisea Makeup</title>
    <meta name="description" content="Revisa y gestiona los productos en tu carrito de compras. Finaliza tu compra de productos de belleza premium.">
    
    <!-- DNS Prefetch para mejorar rendimiento -->
    <link rel="dns-prefetch" href="//fonts.googleapis.com">
    <link rel="dns-prefetch" href="//cdnjs.cloudflare.com">
    <link rel="dns-prefetch" href="//cdn.tailwindcss.com">
    <link rel="dns-prefetch" href="//unpkg.com">
    
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=Playfair+Display:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    
    <!-- Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- AOS Animation -->
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    
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
        
        .hide-scrollbar::-webkit-scrollbar {
            display: none;
        }

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

        /* Cart specific styles */
        .product-card {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .product-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 20px 25px -5px rgba(176, 141, 128, 0.1), 0 10px 10px -5px rgba(176, 141, 128, 0.04);
        }
        
        .quantity-input {
            -webkit-appearance: none;
            -moz-appearance: textfield;
        }
        
        .quantity-input::-webkit-outer-spin-button,
        .quantity-input::-webkit-inner-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }

        .cart-animation {
            animation: slideInUp 0.6s ease-out;
        }

        @keyframes slideInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
</head>

<body class="font-sans bg-white overflow-x-hidden">
    <!-- Header -->
    <!-- Desktop Header -->
    <header class="hidden md:block fixed top-0 left-0 right-0 z-50 header-scroll-animation header-visible" id="desktop-header">
        <!-- Top Bar -->
        <div class="bg-gradient-to-r from-primary-500 to-secondary-500 text-white py-2 text-sm">
            <div class="container mx-auto px-4">
                <div class="flex justify-between items-center">
                    <div class="flex items-center space-x-6">
                        <a href="tel:+573001234567" class="flex items-center space-x-2 hover:text-primary-200 transition-colors">
                            <i class="fas fa-phone text-xs"></i>
                            <span>+57 300 123 4567</span>
                        </a>
                        <a href="mailto:contacto@odisea.com" class="flex items-center space-x-2 hover:text-primary-200 transition-colors">
                            <i class="fas fa-envelope text-xs"></i>
                            <span>contacto@odisea.com</span>
                        </a>
                    </div>
                    <div class="flex items-center space-x-4">
                        <span class="text-xs">Síguenos:</span>
                        <a href="#" target="_blank" class="hover:text-primary-200 transition-colors transform hover:scale-110" title="Instagram">
                            <i class="fab fa-instagram"></i>
                        </a>
                        <a href="#" target="_blank" class="hover:text-primary-200 transition-colors transform hover:scale-110" title="Facebook">
                            <i class="fab fa-facebook"></i>
                        </a>
                        <a href="#" target="_blank" class="hover:text-primary-200 transition-colors transform hover:scale-110" title="TikTok">
                            <i class="fab fa-tiktok"></i>
                        </a>
                        <a href="#" target="_blank" class="hover:text-primary-200 transition-colors transform hover:scale-110" title="YouTube">
                            <i class="fab fa-youtube"></i>
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
                    <div class="flex flex-1 max-w-xl mx-8">
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
                                <span class="font-medium">Mi Cuenta</span>
                            </button>
                        </div>
                        
                        <!-- Wishlist -->
                        <button class="relative p-2 text-gray-700 hover:text-primary-500 transition-colors rounded-xl hover:bg-primary-50">
                            <i class="fas fa-heart text-xl"></i>
                        </button>
                        
                        <!-- Shopping Cart -->
                        <a href="carrito.php" class="relative p-2 text-primary-500 transition-colors rounded-xl bg-primary-50">
                            <i class="fas fa-shopping-bag text-xl"></i>
                            <span class="absolute -top-1 -right-1 bg-gradient-to-r from-primary-500 to-secondary-500 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center font-medium shadow-lg" id="cart-count">
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
                    </div>
                    
                    <!-- Main Navigation -->
                    <div class="flex items-center space-x-8">
                        <a href="index.php" class="text-gray-700 hover:text-primary-500 transition-colors font-medium py-4 border-b-2 border-transparent hover:border-primary-500">Inicio</a>
                        <a href="nuevos.php" class="text-gray-700 hover:text-primary-500 transition-colors font-medium py-4 border-b-2 border-transparent hover:border-primary-500">Novedades</a>
                        <a href="ofertas.php" class="text-gray-700 hover:text-primary-500 transition-colors font-medium py-4 border-b-2 border-transparent hover:border-primary-500">Ofertas</a>
                        <a href="marcas.php" class="text-gray-700 hover:text-primary-500 transition-colors font-medium py-4 border-b-2 border-transparent hover:border-primary-500">Marcas</a>
                        <a href="carrito.php" class="text-primary-500 font-medium py-4 border-b-2 border-primary-500">Carrito</a>
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
                    <a href="/" class="text-2xl font-serif font-bold gradient-text">
                        Odisea
                    </a>
                    <span class="ml-1 text-xs text-gray-500 font-light">MAKEUP</span>
                </div>
                
                <!-- Mobile Actions -->
                <div class="flex items-center space-x-2">
                    <!-- Search Button -->
                    <button class="touch-target p-2 text-gray-700 hover:text-primary-500 transition-colors rounded-xl hover:bg-primary-50" id="mobile-search-btn">
                        <i class="fas fa-search text-lg"></i>
                    </button>
                    
                    <!-- Cart Button -->
                    <a href="carrito.php" class="touch-target relative p-2 text-primary-500 transition-colors rounded-xl bg-primary-50">
                        <i class="fas fa-shopping-bag text-lg"></i>
                        <span class="absolute -top-1 -right-1 bg-gradient-to-r from-primary-500 to-secondary-500 text-white text-xs rounded-full h-4 w-4 flex items-center justify-center font-medium shadow-lg" id="mobile-cart-count">
                            <?php echo $cartCount; ?>
                        </span>
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Mobile Search Bar (Hidden by default) -->
        <div class="px-4 pb-3 hidden" id="mobile-search-bar">
            <div class="relative">
                <input type="text"
                       placeholder="Buscar productos, marcas..."
                       class="w-full px-4 py-3 pr-12 bg-gray-50 border border-gray-200 rounded-2xl focus:outline-none focus:ring-2 focus:ring-primary-500/50 focus:border-primary-500 transition-all duration-300 text-gray-700 placeholder-gray-400">
                <button class="absolute right-3 top-1/2 transform -translate-y-1/2 w-8 h-8 bg-gradient-to-r from-primary-500 to-secondary-500 rounded-xl flex items-center justify-center text-white">
                    <i class="fas fa-search text-sm"></i>
                </button>
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
                            <div class="font-semibold text-gray-900">Bienvenida</div>
                            <div class="text-sm text-gray-600">Invitado</div>
                        </div>
                    </div>
                    <button class="touch-target p-2 text-gray-500 hover:text-gray-700 rounded-xl" id="mobile-menu-close">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
                
                <!-- Menu Items -->
                <div class="flex-1 overflow-y-auto py-6">
                    <nav class="space-y-2 px-6">
                        <a href="index.php" class="flex items-center space-x-3 px-4 py-3 text-gray-700 hover:bg-primary-50 hover:text-primary-600 rounded-xl transition-colors">
                            <i class="fas fa-home text-primary-500"></i>
                            <span class="font-medium">Inicio</span>
                        </a>
                        <a href="nuevos.php" class="flex items-center space-x-3 px-4 py-3 text-gray-700 hover:bg-primary-50 hover:text-primary-600 rounded-xl transition-colors">
                            <i class="fas fa-star text-primary-500"></i>
                            <span class="font-medium">Novedades</span>
                        </a>
                        <a href="ofertas.php" class="flex items-center space-x-3 px-4 py-3 text-gray-700 hover:bg-primary-50 hover:text-primary-600 rounded-xl transition-colors">
                            <i class="fas fa-tags text-primary-500"></i>
                            <span class="font-medium">Ofertas</span>
                        </a>
                        <a href="marcas.php" class="flex items-center space-x-3 px-4 py-3 text-gray-700 hover:bg-primary-50 hover:text-primary-600 rounded-xl transition-colors">
                            <i class="fas fa-gem text-primary-500"></i>
                            <span class="font-medium">Marcas</span>
                        </a>
                        <a href="carrito.php" class="flex items-center space-x-3 px-4 py-3 bg-primary-50 text-primary-600 rounded-xl">
                            <i class="fas fa-shopping-cart text-primary-500"></i>
                            <span class="font-medium">Carrito</span>
                        </a>
                    </nav>
                    
                    <!-- Categories -->
                    <div class="mt-8 px-6">
                        <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-4">Categorías</h3>
                        <nav class="space-y-2">
                            <a href="categoria.php?categoria=rostro" class="flex items-center space-x-3 px-4 py-3 text-gray-700 hover:bg-primary-50 hover:text-primary-600 rounded-xl transition-colors">
                                <i class="fas fa-palette text-primary-500"></i>
                                <span>Rostro</span>
                            </a>
                            <a href="categoria.php?categoria=ojos" class="flex items-center space-x-3 px-4 py-3 text-gray-700 hover:bg-primary-50 hover:text-primary-600 rounded-xl transition-colors">
                                <i class="fas fa-eye text-primary-500"></i>
                                <span>Ojos</span>
                            </a>
                            <a href="categoria.php?categoria=labios" class="flex items-center space-x-3 px-4 py-3 text-gray-700 hover:bg-primary-50 hover:text-primary-600 rounded-xl transition-colors">
                                <i class="fas fa-kiss-wink-heart text-primary-500"></i>
                                <span>Labios</span>
                            </a>
                            <a href="categoria.php?categoria=cuidado" class="flex items-center space-x-3 px-4 py-3 text-gray-700 hover:bg-primary-50 hover:text-primary-600 rounded-xl transition-colors">
                                <i class="fas fa-spa text-primary-500"></i>
                                <span>Cuidado</span>
                            </a>
                        </nav>
                    </div>
                </div>
                
                <!-- Footer Links -->
                <div class="border-t border-gray-100 p-6">
                    <div class="space-y-3">
                        <a href="login.php" class="flex items-center space-x-3 text-gray-700 hover:text-primary-500 transition-colors">
                            <i class="fas fa-sign-in-alt"></i>
                            <span>Iniciar Sesión</span>
                        </a>
                        <a href="register.php" class="flex items-center space-x-3 text-gray-700 hover:text-primary-500 transition-colors">
                            <i class="fas fa-user-plus"></i>
                            <span>Registrarse</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <main class="pt-20 md:pt-32">
        <!-- Breadcrumb -->
        <section class="bg-mesh py-8">
            <div class="container mx-auto px-4">
                <nav class="text-sm" data-aos="fade-right">
                    <ol class="flex items-center space-x-3">
                        <li>
                            <a href="index.php" class="text-gray-600 hover:text-primary-600 transition-colors duration-300 flex items-center">
                                <i class="fas fa-home mr-1"></i>
                                Inicio
                            </a>
                        </li>
                        <li class="text-gray-400">
                            <i class="fas fa-chevron-right text-xs"></i>
                        </li>
                        <li class="text-primary-600 font-semibold flex items-center">
                            <i class="fas fa-shopping-cart mr-1"></i>
                            Carrito de Compras
                        </li>
                    </ol>
                </nav>
            </div>
        </section>

        <!-- Hero Section -->
        <section class="bg-mesh py-12 md:py-32 overflow-hidden relative">
            <!-- Background Elements -->
            <div class="absolute inset-0">
                <div class="absolute top-10 left-5 w-40 h-40 md:w-72 md:h-72 bg-gradient-to-r from-primary-200/30 to-secondary-200/30 rounded-full blur-3xl animate-float"></div>
                <div class="absolute bottom-10 right-5 w-48 h-48 md:w-96 md:h-96 bg-gradient-to-r from-secondary-200/30 to-accent-200/30 rounded-full blur-3xl animate-float" style="animation-delay: 2s;"></div>
            </div>

            <div class="container mx-auto px-4 relative z-10">
                <div class="text-center max-w-4xl mx-auto">
                    <div class="inline-block mb-4 md:mb-6" data-aos="fade-up">
                        <span class="bg-gradient-to-r from-primary-500 to-secondary-500 text-white px-4 py-2 md:px-6 md:py-2 rounded-full text-xs md:text-sm font-medium tracking-wide uppercase">
                            <i class="fas fa-shopping-cart mr-1 md:mr-2"></i>
                            Tu Carrito
                        </span>
                    </div>

                    <h1 class="text-3xl md:text-6xl lg:text-7xl font-serif font-bold mb-4 md:mb-6 gradient-text animate-fade-in-up" data-aos="fade-up" data-aos-delay="200">
                        Carrito de Compras
                    </h1>
                    <p class="text-base md:text-xl text-gray-600 mb-6 md:mb-8 leading-relaxed animate-fade-in-up font-light max-w-3xl mx-auto px-4" style="animation-delay: 0.2s;" data-aos="fade-up" data-aos-delay="400">
                        Revisa tus productos seleccionados y procede con tu compra de belleza premium
                    </p>
                    
                    <!-- Cart Stats - Mobile Optimized -->
                    <div class="grid grid-cols-3 gap-3 md:gap-8 mb-8 md:mb-12 animate-fade-in-up px-4" style="animation-delay: 0.4s;" data-aos="fade-up" data-aos-delay="600">
                        <div class="bg-white/70 backdrop-blur-sm rounded-xl md:rounded-2xl p-3 md:p-6 luxury-shadow hover-lift">
                            <div class="text-xl md:text-3xl font-bold gradient-text mb-1 md:mb-2"><?php echo $cartCount; ?></div>
                            <div class="text-gray-600 font-medium text-xs md:text-base">Productos</div>
                        </div>
                        <div class="bg-white/70 backdrop-blur-sm rounded-xl md:rounded-2xl p-3 md:p-6 luxury-shadow hover-lift">
                            <div class="text-lg md:text-3xl font-bold gradient-text mb-1 md:mb-2">$<?php echo number_format($subtotal, 0, ',', '.'); ?></div>
                            <div class="text-gray-600 font-medium text-xs md:text-base">Subtotal</div>
                        </div>
                        <div class="bg-white/70 backdrop-blur-sm rounded-xl md:rounded-2xl p-3 md:p-6 luxury-shadow hover-lift">
                            <div class="text-lg md:text-3xl font-bold gradient-text mb-1 md:mb-2">$<?php echo number_format($total, 0, ',', '.'); ?></div>
                            <div class="text-gray-600 font-medium text-xs md:text-base">Total</div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Cart Content -->
        <section class="py-16 bg-gradient-to-br from-luxury-rose/30 via-white to-luxury-gold/30 relative overflow-hidden">
            <div class="container mx-auto px-4">
                <?php if (empty($cart)): ?>
                <!-- Empty Cart -->
                <div class="text-center py-20" data-aos="fade-up">
                    <div class="w-32 h-32 bg-gradient-to-br from-primary-100 to-secondary-100 rounded-full flex items-center justify-center mx-auto mb-8 animate-bounce-gentle">
                        <i class="fas fa-shopping-bag text-6xl text-primary-300"></i>
                    </div>
                    <h2 class="text-3xl font-serif font-bold text-gray-900 mb-4">Tu carrito está vacío</h2>
                    <p class="text-lg text-gray-600 mb-8 max-w-md mx-auto">
                        ¡Descubre nuestra increíble colección de productos de belleza y encuentra tus favoritos!
                    </p>
                    <div class="flex flex-col sm:flex-row gap-4 justify-center">
                        <a href="catalogo.php" class="bg-gradient-to-r from-primary-500 to-secondary-500 text-white px-8 py-4 rounded-2xl font-semibold hover:shadow-xl transform hover:scale-105 transition-all duration-300 flex items-center justify-center shimmer-effect">
                            <i class="fas fa-search mr-2"></i>
                            Explorar Catálogo
                        </a>
                        <a href="ofertas.php" class="bg-white text-primary-500 border-2 border-primary-500 px-8 py-4 rounded-2xl font-semibold hover:bg-primary-50 transition-all duration-300 flex items-center justify-center">
                            <i class="fas fa-tags mr-2"></i>
                            Ver Ofertas
                        </a>
                    </div>
                </div>
                <?php else: ?>
                
                <!-- Cart Content -->
                <div class="lg:grid lg:grid-cols-3 lg:gap-12 space-y-8 lg:space-y-0">
                    <!-- Cart Items -->
                    <div class="lg:col-span-2">
                        <div class="glass-effect rounded-2xl md:rounded-3xl luxury-shadow p-4 md:p-8 cart-animation" data-aos="fade-right">
                            <h2 class="text-xl md:text-2xl font-serif font-bold text-gray-900 mb-4 md:mb-6 flex items-center">
                                <i class="fas fa-shopping-basket text-primary-500 mr-2 md:mr-3"></i>
                                Productos en tu carrito
                            </h2>
                            
                            <div id="cart-items-container" class="space-y-4 md:space-y-6">
                                <?php foreach ($cart as $index => $item):
                                     // Imagen del producto con lógica inteligente
                                     $productImage = 'assets/images/placeholder.svg'; // Default fallback
                                     
                                     if (!empty($item['image'])) {
                                         if (strpos($item['image'], 'uploads/products/') === 0) {
                                             // La ruta ya incluye uploads/products/
                                             $testImage = $item['image'];
                                         } else {
                                             // Solo el nombre del archivo
                                             $testImage = 'uploads/products/' . $item['image'];
                                         }
                                         
                                         // Verificar si el archivo existe
                                         if (file_exists($testImage)) {
                                             $productImage = $testImage;
                                         }
                                     }
                                ?>
                                <div class="product-card bg-white/90 backdrop-blur-sm rounded-xl md:rounded-2xl p-4 md:p-6 border border-white/20 luxury-shadow" data-product-id="<?php echo $item['product_id'] ?? $item['id'] ?? ''; ?>">
                                    <div class="flex flex-col space-y-4 md:flex-row md:items-center md:space-y-0 md:space-x-6">
                                        <!-- Product Image -->
                                        <div class="flex-shrink-0 mx-auto md:mx-0">
                                            <img src="<?php echo $productImage; ?>"
                                                  alt="<?php echo htmlspecialchars($item['name'] ?? 'Producto'); ?>"
                                                  class="w-20 h-20 md:w-32 md:h-32 object-cover rounded-lg md:rounded-xl luxury-shadow"
                                                  onerror="this.onerror=null; this.src='assets/images/placeholder.svg'">
                                        </div>
                                        
                                        <!-- Product Info -->
                                        <div class="flex-1 min-w-0 text-center md:text-left">
                                            <h3 class="text-lg md:text-xl font-semibold text-gray-900 mb-2">
                                                <?php echo htmlspecialchars($item['name'] ?? 'Producto'); ?>
                                            </h3>
                                            <?php if (!empty($item['variant'])): ?>
                                            <p class="text-sm text-gray-600 mb-2">
                                                <i class="fas fa-palette mr-1"></i>
                                                <?php echo htmlspecialchars($item['variant']); ?>
                                            </p>
                                            <?php endif; ?>
                                            <div class="flex items-center justify-center md:justify-start space-x-4">
                                                <?php if (!empty($item['compare_price']) && ($item['compare_price'] ?? 0) > ($item['price'] ?? 0)): ?>
                                                <div class="flex flex-col items-start">
                                                    <div class="flex items-center space-x-2">
                                                        <span class="text-sm text-gray-500 line-through">$<?php echo number_format($item['compare_price'] ?? 0, 0, ',', '.'); ?></span>
                                                        <span class="bg-red-500 text-white text-xs px-2 py-1 rounded-full">-<?php echo $item['discount_percentage'] ?? 0; ?>%</span>
                                                    </div>
                                                    <span class="text-xl md:text-2xl font-bold gradient-text">
                                                        $<?php echo number_format($item['price'] ?? 0, 0, ',', '.'); ?>
                                                    </span>
                                                </div>
                                                <?php else: ?>
                                                <span class="text-xl md:text-2xl font-bold gradient-text">
                                                    $<?php echo number_format($item['price'] ?? 0, 0, ',', '.'); ?>
                                                </span>
                                                <?php endif; ?>
                                                <span class="text-sm text-gray-500">por unidad</span>
                                            </div>
                                        </div>
                                        
                                        <!-- Mobile Layout: Quantity and Remove -->
                                        <div class="flex items-center justify-between md:flex-col md:items-center md:space-y-4">
                                            <!-- Quantity Controls -->
                                            <div class="flex items-center bg-white rounded-lg md:rounded-xl border-2 border-gray-200 p-1 luxury-shadow">
                                                <button onclick="updateQuantity(<?php echo $item['product_id'] ?? $item['id'] ?? ''; ?>, -1)"
                                                         class="w-8 h-8 md:w-10 md:h-10 flex items-center justify-center text-gray-600 hover:text-primary-500 hover:bg-primary-50 rounded-md md:rounded-lg transition-all duration-300">
                                                    <i class="fas fa-minus text-sm"></i>
                                                </button>
                                                <input type="number"
                                                        value="<?php echo $item['quantity'] ?? 1; ?>"
                                                        min="1"
                                                        max="99"
                                                       class="quantity-input w-12 md:w-16 text-center font-semibold text-base md:text-lg bg-transparent border-0 focus:outline-none"
                                                       onchange="updateQuantityDirect(<?php echo $item['product_id'] ?? $item['id'] ?? ''; ?>, this.value)"
                                                       id="qty-<?php echo $item['product_id'] ?? $item['id'] ?? ''; ?>">
                                                <button onclick="updateQuantity(<?php echo $item['product_id'] ?? $item['id'] ?? ''; ?>, 1)"
                                                         class="w-8 h-8 md:w-10 md:h-10 flex items-center justify-center text-gray-600 hover:text-primary-500 hover:bg-primary-50 rounded-md md:rounded-lg transition-all duration-300">
                                                    <i class="fas fa-plus text-sm"></i>
                                                </button>
                                            </div>
                                            
                                            <!-- Remove Button -->
                                            <button onclick="removeFromCart(<?php echo $item['product_id'] ?? $item['id'] ?? ''; ?>)"
                                                     class="w-10 h-10 md:w-12 md:h-12 bg-red-50 hover:bg-red-100 text-red-500 hover:text-red-700 rounded-lg md:rounded-xl flex items-center justify-center transition-all duration-300 hover:scale-110 luxury-shadow">
                                                <i class="fas fa-trash text-sm"></i>
                                            </button>
                                        </div>
                                        
                                        <!-- Subtotal - Mobile -->
                                        <div class="text-center md:hidden">
                                            <p class="text-sm text-gray-600">Subtotal</p>
                                            <p class="text-lg font-bold text-gray-900">
                                                $<?php echo number_format(($item['price'] ?? 0) * ($item['quantity'] ?? 1), 0, ',', '.'); ?>
                                            </p>
                                        </div>
                                        
                                        <!-- Subtotal - Desktop -->
                                        <div class="hidden md:block text-center">
                                            <p class="text-sm text-gray-600">Subtotal</p>
                                            <p class="text-lg font-bold text-gray-900">
                                                $<?php echo number_format(($item['price'] ?? 0) * ($item['quantity'] ?? 1), 0, ',', '.'); ?>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            
                            <!-- Continue Shopping -->
                            <div class="mt-6 md:mt-8 pt-4 md:pt-6 border-t border-gray-200 text-center md:text-left">
                                <a href="catalogo.php" class="inline-flex items-center text-primary-500 hover:text-primary-700 font-semibold transition-colors duration-300">
                                    <i class="fas fa-arrow-left mr-2"></i>
                                    Continuar comprando
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Order Summary -->
                    <div class="lg:col-span-1">
                        <div class="glass-effect rounded-2xl md:rounded-3xl luxury-shadow p-4 md:p-8 lg:sticky lg:top-24 cart-animation" data-aos="fade-left">
                            <h2 class="text-xl md:text-2xl font-serif font-bold text-gray-900 mb-4 md:mb-6 flex items-center">
                                <i class="fas fa-receipt text-primary-500 mr-2 md:mr-3"></i>
                                Resumen del pedido
                            </h2>
                            
                            <div id="order-summary" class="space-y-3 md:space-y-4">
                                <div class="flex justify-between items-center py-2 md:py-3 border-b border-gray-100">
                                    <span class="text-gray-600 text-sm md:text-base">Subtotal (<?php echo $cartCount; ?> productos)</span>
                                    <span class="font-semibold text-gray-900 text-sm md:text-base">$<?php echo number_format($subtotal, 0, ',', '.'); ?></span>
                                </div>
                                
                                <div class="flex justify-between items-center py-2 md:py-3 border-b border-gray-100">
                                    <span class="text-gray-600 text-sm md:text-base">Envío</span>
                                    <span class="font-semibold text-gray-900 text-sm md:text-base">
                                        <?php if ($shipping > 0): ?>
                                            $<?php echo number_format($shipping, 0, ',', '.'); ?>
                                        <?php else: ?>
                                            <span class="text-green-500">Gratis</span>
                                        <?php endif; ?>
                                    </span>
                                </div>
                                
                                <div class="flex justify-between items-center py-2 md:py-3 border-b border-gray-100">
                                    <span class="text-gray-600 text-sm md:text-base">IVA (19%)</span>
                                    <span class="font-semibold text-gray-900 text-sm md:text-base">$<?php echo number_format($tax, 0, ',', '.'); ?></span>
                                </div>
                                
                                <div class="flex justify-between items-center py-3 md:py-4 bg-gradient-to-r from-primary-50 to-secondary-50 rounded-xl px-3 md:px-4 mt-4 md:mt-6">
                                    <span class="text-lg md:text-xl font-bold text-gray-900">Total</span>
                                    <span class="text-xl md:text-2xl font-bold gradient-text">$<?php echo number_format($total, 0, ',', '.'); ?></span>
                                </div>
                            </div>
                            
                            <!-- Checkout Button -->
                            <div class="mt-6 md:mt-8 space-y-3 md:space-y-4">
                                <button onclick="proceedToCheckout()" class="w-full bg-gradient-to-r from-primary-500 to-secondary-500 text-white py-3 md:py-4 rounded-xl md:rounded-2xl font-bold text-base md:text-lg hover:shadow-xl transform hover:scale-105 transition-all duration-300 flex items-center justify-center shimmer-effect">
                                    <i class="fas fa-credit-card mr-2 md:mr-3"></i>
                                    Proceder al Pago
                                </button>
                                
                                <div class="text-center">
                                    <p class="text-xs md:text-sm text-gray-600">
                                        <i class="fas fa-shield-alt text-green-500 mr-1"></i>
                                        Pago 100% seguro
                                    </p>
                                </div>
                            </div>
                            
                            <!-- Payment Methods -->
                            <div class="mt-4 md:mt-6 pt-4 md:pt-6 border-t border-gray-200">
                                <p class="text-xs md:text-sm text-gray-600 mb-2 md:mb-3">Métodos de pago aceptados:</p>
                                <div class="flex items-center justify-center space-x-2 md:space-x-3">
                                    <i class="fab fa-cc-visa text-xl md:text-2xl text-blue-600"></i>
                                    <i class="fab fa-cc-mastercard text-xl md:text-2xl text-red-500"></i>
                                    <i class="fas fa-credit-card text-xl md:text-2xl text-gray-600"></i>
                                    <i class="fab fa-paypal text-xl md:text-2xl text-blue-700"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </section>
    </main>
<!-- Move the stray JS code inside a <script> tag -->
<script>
    // Mobile menu functionality
    document.addEventListener('DOMContentLoaded', function() {
        const mobileMenuBtn = document.getElementById('mobile-menu-btn');
        const mobileMenuOverlay = document.getElementById('mobile-menu-overlay');
        const mobileMenuDrawer = document.getElementById('mobile-menu-drawer');
        const mobileMenuClose = document.getElementById('mobile-menu-close');
        const mobileMenuBackdrop = document.getElementById('mobile-menu-backdrop');
        const mobileSearchBtn = document.getElementById('mobile-search-btn');
        const mobileSearchBar = document.getElementById('mobile-search-bar');

        // Mobile menu toggle
        function openMobileMenu() {
            mobileMenuOverlay.classList.remove('hidden');
            setTimeout(() => {
                mobileMenuDrawer.classList.add('open');
            }, 10);
            document.body.style.overflow = 'hidden';
        }

        function closeMobileMenu() {
            mobileMenuDrawer.classList.remove('open');
            setTimeout(() => {
                mobileMenuOverlay.classList.add('hidden');
                document.body.style.overflow = '';
            }, 300);
        }

        mobileMenuBtn?.addEventListener('click', openMobileMenu);
        mobileMenuClose?.addEventListener('click', closeMobileMenu);
        mobileMenuBackdrop?.addEventListener('click', closeMobileMenu);

        // Mobile search toggle
        mobileSearchBtn?.addEventListener('click', function() {
            mobileSearchBar.classList.toggle('hidden');
            if (!mobileSearchBar.classList.contains('hidden')) {
                const input = mobileSearchBar.querySelector('input');
                setTimeout(() => input.focus(), 100);
            }
        });
    });
</script>

    <!-- Footer -->
    <footer class="bg-gray-900 text-white relative overflow-hidden">
        <div class="absolute inset-0 bg-gradient-to-br from-gray-900 via-gray-800 to-gray-900"></div>
        
        <div class="container mx-auto px-4 py-20 md:py-12 relative z-10">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-12 md:gap-8">
                <!-- Company Info -->
                <div class="space-y-8 md:space-y-6">
                    <div>
                        <h3 class="text-3xl md:text-2xl font-serif font-bold gradient-text mb-6 md:mb-4">
                            Odisea
                        </h3>
                        <p class="text-gray-300 leading-relaxed font-light text-lg md:text-base">
                            Tu destino para el maquillaje perfecto. Descubre las mejores marcas y productos de belleza con la calidad que mereces.
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
                            <span class="text-gray-300">+57 300 123 4567</span>
                        </div>
                        <div class="flex items-center space-x-4 group">
                            <div class="w-12 h-12 md:w-10 md:h-10 bg-gradient-to-r from-primary-500 to-secondary-500 rounded-xl flex items-center justify-center group-hover:scale-110 transition-transform duration-300">
                                <i class="fas fa-envelope text-lg md:text-base"></i>
                            </div>
                            <span class="text-gray-300">contacto@odisea.com</span>
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
                        <li><a href="blog.php" class="text-gray-300 hover:text-primary-400 transition-colors duration-300 font-light hover:translate-x-1 transform block">Blog</a></li>
                        <li><a href="contacto.php" class="text-gray-300 hover:text-primary-400 transition-colors duration-300 font-light hover:translate-x-1 transform block">Contacto</a></li>
                    </ul>
                </div>
                
                <!-- Customer Service -->
                <div>
                    <h4 class="text-xl md:text-lg font-semibold mb-8 md:mb-6 text-white">Atención al Cliente</h4>
                    <ul class="space-y-4 md:space-y-3">
                        <li><a href="mi-cuenta.php" class="text-gray-300 hover:text-primary-400 transition-colors duration-300 font-light hover:translate-x-1 transform block">Mi Cuenta</a></li>
                        <li><a href="mis-pedidos.php" class="text-gray-300 hover:text-primary-400 transition-colors duration-300 font-light hover:translate-x-1 transform block">Mis Pedidos</a></li>
                        <li><a href="envios-devoluciones.php" class="text-gray-300 hover:text-primary-400 transition-colors duration-300 font-light hover:translate-x-1 transform block">Envíos y Devoluciones</a></li>
                        <li><a href="faq.php" class="text-gray-300 hover:text-primary-400 transition-colors duration-300 font-light hover:translate-x-1 transform block">Preguntas Frecuentes</a></li>
                        <li><a href="terminos.php" class="text-gray-300 hover:text-primary-400 transition-colors duration-300 font-light hover:translate-x-1 transform block">Términos y Condiciones</a></li>
                    </ul>
                </div>
                
                <!-- Social & Payment -->
                <div>
                    <h4 class="text-xl md:text-lg font-semibold mb-8 md:mb-6 text-white">Síguenos</h4>
                    <div class="flex space-x-4 mb-10 md:mb-8">
                        <a href="#" target="_blank"
                           class="w-12 h-12 md:w-10 md:h-10 bg-gradient-to-r from-primary-500 to-secondary-500 rounded-xl flex items-center justify-center hover:scale-110 transition-all duration-300 luxury-shadow"
                           title="Instagram">
                            <i class="fab fa-instagram text-lg md:text-base"></i>
                        </a>
                        <a href="#" target="_blank"
                           class="w-12 h-12 md:w-10 md:h-10 bg-gradient-to-r from-primary-500 to-secondary-500 rounded-xl flex items-center justify-center hover:scale-110 transition-all duration-300 luxury-shadow"
                           title="Facebook">
                            <i class="fab fa-facebook text-lg md:text-base"></i>
                        </a>
                        <a href="#" target="_blank"
                           class="w-12 h-12 md:w-10 md:h-10 bg-gradient-to-r from-primary-500 to-secondary-500 rounded-xl flex items-center justify-center hover:scale-110 transition-all duration-300 luxury-shadow"
                           title="TikTok">
                            <i class="fab fa-tiktok text-lg md:text-base"></i>
                        </a>
                        <a href="#" target="_blank"
                           class="w-12 h-12 md:w-10 md:h-10 bg-gradient-to-r from-primary-500 to-secondary-500 rounded-xl flex items-center justify-center hover:scale-110 transition-all duration-300 luxury-shadow"
                           title="YouTube">
                            <i class="fab fa-youtube text-lg md:text-base"></i>
                        </a>
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
                        © <?php echo date('Y'); ?> Odisea Makeup. Todos los derechos reservados.
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

    <!-- WhatsApp Floating Button -->
    <div class="fixed bottom-8 right-8 md:bottom-6 md:right-6 z-40">
        <a href="https://wa.me/573001234567?text=Hola,%20me%20interesa%20conocer%20más%20sobre%20sus%20productos"
           target="_blank"
           class="w-16 h-16 md:w-14 md:h-14 bg-gradient-to-r from-green-500 to-green-600 rounded-full flex items-center justify-center text-white luxury-shadow hover:shadow-3xl transform hover:scale-110 transition-all duration-300 animate-bounce-gentle">
            <i class="fab fa-whatsapp text-2xl md:text-xl"></i>
        </a>
    </div>

    <!-- Back to Top Button -->
    <button class="fixed bottom-8 left-8 md:bottom-6 md:left-6 w-14 h-14 md:w-12 md:h-12 bg-gradient-to-r from-primary-500 to-secondary-500 text-white rounded-full flex items-center justify-center luxury-shadow hover:shadow-3xl transform hover:scale-110 transition-all duration-300 opacity-0 invisible" id="back-to-top">
        <i class="fas fa-chevron-up text-lg md:text-base"></i>
    </button>

    <!-- Notification Container -->
    <div id="notification-container" class="fixed top-20 right-4 z-50 space-y-2"></div>

    <!-- Scripts -->
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    
    <script>
        // Initialize AOS
        AOS.init({
            duration: 1000,
            once: true,
            offset: 100
        });

        // Header scroll effect
        window.addEventListener('scroll', function() {
            const backToTop = document.getElementById('back-to-top');
            
            if (window.scrollY > 100) {
                backToTop.classList.remove('opacity-0', 'invisible');
            } else {
                backToTop.classList.add('opacity-0', 'invisible');
            }
        });

        // Back to top functionality
        document.getElementById('back-to-top')?.addEventListener('click', function() {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        });

        console.log('🛒 Carrito page loaded');
        
        // Función para actualizar cantidad
        async function updateQuantity(productId, change) {
            console.log(`Updating quantity for product ${productId} with change ${change}`);
            
            try {
                const response = await fetch('cart-update.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `id=${productId}&change=${change}`
                });
                const data = await response.json();
                
                if (data.success) {
                    // Reload page to update all values
                    location.reload();
                } else {
                    showNotification(data.message || 'Error al actualizar cantidad', 'error');
                }
            } catch (error) {
                console.error('Error updating quantity:', error);
                showNotification('Error al actualizar cantidad', 'error');
            }
        }

        // Función para actualizar cantidad directamente
        async function updateQuantityDirect(productId, newQuantity) {
            if (newQuantity < 1) {
                newQuantity = 1;
                document.getElementById(`qty-${productId}`).value = 1;
            }
            
            try {
                const response = await fetch('cart-update.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `id=${productId}&quantity=${newQuantity}`
                });
                const data = await response.json();
                
                if (data.success) {
                    location.reload();
                } else {
                    showNotification(data.message || 'Error al actualizar cantidad', 'error');
                }
            } catch (error) {
                console.error('Error updating quantity:', error);
                showNotification('Error al actualizar cantidad', 'error');
            }
        }

        // Función para eliminar del carrito
        async function removeFromCart(productId) {
            if (!confirm('¿Estás seguro de que quieres eliminar este producto del carrito?')) {
                return;
            }
            
            console.log(`Removing product ${productId} from cart`);
            
            try {
                const response = await fetch('cart-remove.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `id=${productId}`
                });
                
                // Debug: Ver el contenido de la respuesta
                const responseText = await response.text();
                console.log('Response text:', responseText);
                
                // Intentar parsear como JSON
                let data;
                try {
                    data = JSON.parse(responseText);
                } catch (parseError) {
                    console.error('Error parsing JSON:', parseError);
                    console.error('Response was:', responseText);
                    throw new Error('Response is not valid JSON: ' + responseText.substring(0, 100));
                }
                
                if (data.success) {
                    showNotification('Producto eliminado del carrito', 'success');
                    
                    // Animate removal
                    const productCard = document.querySelector(`[data-product-id="${productId}"]`);
                    if (productCard) {
                        productCard.style.transform = 'translateX(100%)';
                        productCard.style.opacity = '0';
                        setTimeout(() => {
                            location.reload();
                        }, 300);
                    } else {
                        location.reload();
                    }
                } else {
                    showNotification(data.message || 'Error al eliminar producto', 'error');
                }
            } catch (error) {
                console.error('Error removing product:', error);
                showNotification('Error al eliminar producto: ' + error.message, 'error');
            }
        }

        // Función para obtener el número de productos en el carrito
        function getCartCount() {
            const productCards = document.querySelectorAll('.product-card');
            let totalCount = 0;
            
            productCards.forEach(card => {
                const quantityInput = card.querySelector('.quantity-input');
                if (quantityInput) {
                    const quantity = parseInt(quantityInput.value) || 0;
                    totalCount += quantity;
                }
            });
            
            // Si no hay cards de productos, intentar contar desde el elemento del contador
            if (totalCount === 0) {
                const cartCountElement = document.getElementById('cart-count');
                if (cartCountElement) {
                    totalCount = parseInt(cartCountElement.textContent) || 0;
                }
            }
            
            return totalCount;
        }

        // Función para proceder al checkout
        function proceedToCheckout() {
            const cartCount = getCartCount();
            if (cartCount === 0) {
                showNotification('Tu carrito está vacío', 'error');
                return;
            }
            
            showNotification('Redirigiendo al proceso de finalización...', 'info');
            
            setTimeout(() => {
                window.location.href = 'finalizar-pedido.php';
            }, 1000);
        }

        // Función para mostrar notificaciones
        function showNotification(message, type = 'info') {
            const colors = {
                success: 'from-green-500 to-emerald-500',
                error: 'from-red-500 to-rose-500',
                warning: 'from-yellow-500 to-orange-500',
                info: 'from-primary-500 to-secondary-500'
            };
            const icons = {
                success: 'fas fa-check-circle',
                error: 'fas fa-exclamation-circle',
                warning: 'fas fa-exclamation-triangle',
                info: 'fas fa-info-circle'
            };
            
            const notification = document.createElement('div');
            notification.className = `bg-gradient-to-r ${colors[type]} text-white p-4 rounded-2xl luxury-shadow max-w-sm transform translate-x-full transition-all duration-500 ease-out`;
            notification.innerHTML = `
                <div class="flex items-center">
                    <i class="${icons[type]} text-xl mr-3"></i>
                    <span class="font-medium flex-1">${message}</span>
                    <button onclick="this.parentElement.parentElement.remove()" class="ml-3 text-white hover:text-gray-200 text-xl">×</button>
                </div>
            `;
            
            document.getElementById('notification-container').appendChild(notification);
            
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

        // Inicialización
        document.addEventListener('DOMContentLoaded', function() {
            console.log('Cart page initialized');
            
            // Agregar efectos de hover a las tarjetas de productos
            const productCards = document.querySelectorAll('.product-card');
            productCards.forEach(card => {
                card.addEventListener('mouseenter', function() {
                    this.classList.add('shadow-2xl');
                });
                
                card.addEventListener('mouseleave', function() {
                    this.classList.remove('shadow-2xl');
                });
            });
        });

        // Mobile menu functionality
        document.addEventListener('DOMContentLoaded', function() {
            const mobileMenuBtn = document.getElementById('mobile-menu-btn');
            const mobileMenuOverlay = document.getElementById('mobile-menu-overlay');
            const mobileMenuDrawer = document.getElementById('mobile-menu-drawer');
            const mobileMenuClose = document.getElementById('mobile-menu-close');
            const mobileMenuBackdrop = document.getElementById('mobile-menu-backdrop');
            const mobileSearchBtn = document.getElementById('mobile-search-btn');
            const mobileSearchBar = document.getElementById('mobile-search-bar');

            // Mobile menu toggle
            function openMobileMenu() {
                mobileMenuOverlay.classList.remove('hidden');
                setTimeout(() => {
                    mobileMenuDrawer.classList.add('open');
                }, 10);
                document.body.style.overflow = 'hidden';
            }

            function closeMobileMenu() {
                mobileMenuDrawer.classList.remove('open');
                setTimeout(() => {
                    mobileMenuOverlay.classList.add('hidden');
                    document.body.style.overflow = '';
                }, 300);
            }

            mobileMenuBtn?.addEventListener('click', openMobileMenu);
            mobileMenuClose?.addEventListener('click', closeMobileMenu);
            mobileMenuBackdrop?.addEventListener('click', closeMobileMenu);

            // Mobile search toggle
            mobileSearchBtn?.addEventListener('click', function() {
                mobileSearchBar.classList.toggle('hidden');
                if (!mobileSearchBar.classList.contains('hidden')) {
                    const input = mobileSearchBar.querySelector('input');
                    setTimeout(() => input.focus(), 100);
                }
            });
        });
    </script>

    <?php include 'includes/header-scroll-animation.php'; ?>
</body>
</html>
