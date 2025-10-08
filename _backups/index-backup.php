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
require_once 'models/Brand.php';

// Aquí podrías cargar datos dinámicos desde la base de datos
// Por ejemplo, productos destacados, categorías, etc.
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Odisea Makeup - Tu destino para el maquillaje perfecto</title>
    <meta name="description" content="Descubre la mejor selección de maquillaje y productos de belleza en Odisea. Marcas exclusivas, envíos a toda Colombia y la mejor experiencia de compra.">
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?php echo ASSETS_URL; ?>/css/style.css">
    <link rel="stylesheet" href="<?php echo ASSETS_URL; ?>/css/custom-colors.css">
    <link rel="stylesheet" href="<?php echo ASSETS_URL; ?>/css/fix-backgrounds.css">
    
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
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
                            50: '#faf7f5', // Powder base
                            100: '#f5ede8', // Light nude
                            200: '#ead5cc', // Warm nude  
                            300: '#ddbdab', // Medium nude
                            400: '#c9a196', // Rose nude
                            500: '#b08d80', // Mauve elegante
                            600: '#9d7a6b', // Deep mauve
                            700: '#8a6657', // Rich nude
                            800: '#776052', // Dark nude
                            900: '#64534d'  // Deepest nude
                        },
                        secondary: {
                            50: '#fdfcfa', // Champagne base
                            100: '#faf6f0', // Pearl
                            200: '#f4e9d9', // Light gold
                            300: '#ead5b8', // Warm beige
                            400: '#d4b896', // Golden beige
                            500: '#c4a575', // Soft gold
                            600: '#b39256', // Rich gold
                            700: '#9e7d3a', // Deep gold
                            800: '#896820', // Dark gold
                            900: '#745407'  // Deepest gold
                        },
                        accent: {
                            50: '#fbf9f7', // Soft cream
                            100: '#f6f0ed', // Blush cream
                            200: '#e8d5d0', // Light mauve
                            300: '#d4b5ae', // Soft mauve
                            400: '#c19590', // Medium mauve
                            500: '#a67c76', // Deep mauve
                            600: '#8d635d', // Rich mauve
                            700: '#745044', // Dark mauve
                            800: '#5b3d2b', // Deeper mauve
                            900: '#422a12'  // Darkest mauve
                        },
                        luxury: {
                            nude: '#b08d80',
                            gold: '#c4a575',
                            mauve: '#a67c76',
                            cream: '#faf7f5',
                            pearl: '#fdfcfa'
                        }
                    },
                            700: '#0369a1',
                            800: '#075985',
                            900: '#0c4a6e'
                        },
                        orange: {
                            50: '#fff7ed',
                            100: '#ffedd5',
                            200: '#fed7aa',
                            300: '#fdba74',
                            400: '#fb923c',
                            500: '#f97316',
                            600: '#ea580c',
                            700: '#c2410c',
                            800: '#9a3412',
                            900: '#7c2d12'
                        },
                        yellow: {
                            50: '#fefce8',
                            100: '#fef9c3',
                            200: '#fef08a',
                            300: '#fde047',
                            400: '#facc15',
                            500: '#eab308',
                            600: '#ca8a04',
                            700: '#a16207',
                            800: '#854d0e',
                            900: '#713f12'
                        },
                        amber: {
                            50: '#fffbeb',
                            100: '#fef3c7',
                            200: '#fde68a',
                            300: '#fcd34d',
                            400: '#fbbf24',
                            500: '#f59e0b',
                            600: '#d97706',
                            700: '#b45309',
                            800: '#92400e',
                            900: '#78350f'
                        },
                        red: {
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
                        },
                        pink: {
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
                        rose: {
                            50: '#fff1f2',
                            100: '#ffe4e6',
                            200: '#fecdd3',
                            300: '#fda4af',
                            400: '#fb7185',
                            500: '#f43f5e',
                            600: '#e11d48',
                            700: '#be123c',
                            800: '#9f1239',
                            900: '#881337'
                        },
                        green: {
                            50: '#f0fdf4',
                            100: '#dcfce7',
                            200: '#bbf7d0',
                            300: '#86efac',
                            400: '#4ade80',
                            500: '#22c55e',
                            600: '#16a34a',
                            700: '#15803d',
                            800: '#166534',
                            900: '#14532d'
                        },
                        emerald: {
                            50: '#ecfdf5',
                            100: '#d1fae5',
                            200: '#a7f3d0',
                            300: '#6ee7b7',
                            400: '#34d399',
                            500: '#10b981',
                            600: '#059669',
                            700: '#047857',
                            800: '#065f46',
                            900: '#064e3b'
                        },
                        teal: {
                            50: '#f0fdfa',
                            100: '#ccfbf1',
                            200: '#99f6e4',
                            300: '#5eead4',
                            400: '#2dd4bf',
                            500: '#14b8a6',
                            600: '#0d9488',
                            700: '#0f766e',
                            800: '#115e59',
                            900: '#134e4a'
                        },
                        cyan: {
                            50: '#ecfeff',
                            100: '#cffafe',
                            200: '#a5f3fc',
                            300: '#67e8f9',
                            400: '#22d3ee',
                            500: '#06b6d4',
                            600: '#0891b2',
                            700: '#0e7490',
                            800: '#155e75',
                            900: '#164e63'
                        },
                        blue: {
                            50: '#eff6ff',
                            100: '#dbeafe',
                            200: '#bfdbfe',
                            300: '#93c5fd',
                            400: '#60a5fa',
                            500: '#3b82f6',
                            600: '#2563eb',
                            700: '#1d4ed8',
                            800: '#1e40af',
                            900: '#1e3a8a'
                        },
                        indigo: {
                            50: '#eef2ff',
                            100: '#e0e7ff',
                            200: '#c7d2fe',
                            300: '#a5b4fc',
                            400: '#818cf8',
                            500: '#6366f1',
                            600: '#4f46e5',
                            700: '#4338ca',
                            800: '#3730a3',
                            900: '#312e81'
                        },
                        purple: {
                            50: '#faf5ff',
                            100: '#f3e8ff',
                            200: '#e9d5ff',
                            300: '#d8b4fe',
                            400: '#c084fc',
                            500: '#a855f7',
                            600: '#9333ea',
                            700: '#7c3aed',
                            800: '#6b21a8',
                            900: '#581c87'
                        },
                        violet: {
                            50: '#f5f3ff',
                            100: '#ede9fe',
                            200: '#ddd6fe',
                            300: '#c4b5fd',
                            400: '#a78bfa',
                            500: '#8b5cf6',
                            600: '#7c3aed',
                            700: '#6d28d9',
                            800: '#5b21b6',
                            900: '#4c1d95'
                        },
                        slate: {
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
                        },
                        gray: {
                            50: '#f9fafb',
                            100: '#f3f4f6',
                            200: '#e5e7eb',
                            300: '#d1d5db',
                            400: '#9ca3af',
                            500: '#6b7280',
                            600: '#4b5563',
                            700: '#374151',
                            800: '#1f2937',
                            900: '#111827'
                        }
                    },
                    fontFamily: {
                        'sans': ['Poppins', 'sans-serif']
                    },
                    animation: {
                        'float': 'float 6s ease-in-out infinite',
                        'pulse-slow': 'pulse 3s cubic-bezier(0.4, 0, 0.6, 1) infinite',
                        'bounce-slow': 'bounce 2s infinite',
                        'fade-in': 'fadeIn 0.5s ease-in-out',
                        'slide-up': 'slideUp 0.5s ease-out',
                        'scale-in': 'scaleIn 0.3s ease-out'
                    },
                    keyframes: {
                        float: {
                            '0%, 100%': { transform: 'translateY(0px)' },
                            '50%': { transform: 'translateY(-20px)' }
                        },
                        fadeIn: {
                            '0%': { opacity: '0' },
                            '100%': { opacity: '1' }
                        },
                        slideUp: {
                            '0%': { transform: 'translateY(20px)', opacity: '0' },
                            '100%': { transform: 'translateY(0)', opacity: '1' }
                        },
                        scaleIn: {
                            '0%': { transform: 'scale(0.9)', opacity: '0' },
                            '100%': { transform: 'scale(1)', opacity: '1' }
                        },
                        'rotate-clockwise': {
                            '0%': { transform: 'rotate(0deg)' },
                            '100%': { transform: 'rotate(360deg)' }
                        },
                        'fade-in-left': {
                            '0%': { opacity: '0', transform: 'translateX(-50px) scale(0.8)' },
                            '100%': { opacity: '1', transform: 'translateX(0) scale(1)' }
                        },
                        'fade-out-right': {
                            '0%': { opacity: '1', transform: 'translateX(0) scale(1)' },
                            '100%': { opacity: '0', transform: 'translateX(50px) scale(0.8)' }
                        }
                    },
                    animation: {
                        bounce: 'bounce 2s infinite',
                        pulse: 'pulse 3s cubic-bezier(0.4, 0, 0.6, 1) infinite',
                        'rotate-slow': 'rotate-clockwise 30s linear infinite',
                        'fade-in-left': 'fade-in-left 0.8s ease-out forwards',
                        'fade-out-right': 'fade-out-right 0.8s ease-out forwards'
                    }
                    }
                }
            }
        }
    </script>
    
    <!-- Custom CSS for Circular Carousel -->
    <style>
        /* Safelist - Garantizar que estas clases se incluyan */
        .hidden { display: none !important; }
        
        /* Colores de fondo */
        .from-primary-50, .to-primary-100, .from-secondary-50, .to-secondary-100,
        .from-orange-50, .to-orange-100, .from-yellow-50, .to-yellow-100,
        .from-amber-50, .to-amber-100, .from-red-50, .to-red-100,
        .from-pink-50, .to-pink-100, .from-rose-50, .to-rose-100,
        .from-green-50, .to-green-100, .from-emerald-50, .to-emerald-100,
        .from-teal-50, .to-teal-100, .from-cyan-50, .to-cyan-100,
        .from-blue-50, .to-blue-100, .from-indigo-50, .to-indigo-100,
        .from-purple-50, .to-purple-100, .from-violet-50, .to-violet-100,
        .from-slate-50, .to-slate-100, .from-gray-50, .to-gray-100 { display: block; }
        
        /* Colores de texto */
        .text-primary-500, .text-secondary-500, .text-orange-500, .text-yellow-500,
        .text-amber-500, .text-red-500, .text-pink-500, .text-rose-500,
        .text-green-500, .text-emerald-500, .text-teal-500, .text-cyan-500,
        .text-blue-500, .text-indigo-500, .text-purple-500, .text-violet-500,
        .text-slate-500, .text-gray-500 { display: block; }
        
        /* Colores de texto hover */
        .text-primary-700, .text-secondary-700, .text-orange-700, .text-yellow-700,
        .text-amber-700, .text-red-700, .text-pink-700, .text-rose-700,
        .text-green-700, .text-emerald-700, .text-teal-700, .text-cyan-700,
        .text-blue-700, .text-indigo-700, .text-purple-700, .text-violet-700,
        .text-slate-700, .text-gray-700 { display: block; }
        
        /* Colores de fondo hover */
        .bg-primary-500, .bg-secondary-500, .bg-orange-500, .bg-yellow-500,
        .bg-amber-500, .bg-red-500, .bg-pink-500, .bg-rose-500,
        .bg-green-500, .bg-emerald-500, .bg-teal-500, .bg-cyan-500,
        .bg-blue-500, .bg-indigo-500, .bg-purple-500, .bg-violet-500,
        .bg-slate-500, .bg-gray-500 { display: block; }
        
        /* Colores de border */
        .border-primary-100, .border-secondary-100, .border-orange-100, .border-yellow-100,
        .border-amber-100, .border-red-100, .border-pink-100, .border-rose-100,
        .border-green-100, .border-emerald-100, .border-teal-100, .border-cyan-100,
        .border-blue-100, .border-indigo-100, .border-purple-100, .border-violet-100,
        .border-slate-100, .border-gray-100 { display: block; }
        
        /* Colores de border hover */
        .border-primary-200, .border-secondary-200, .border-orange-200, .border-yellow-200,
        .border-amber-200, .border-red-200, .border-pink-200, .border-rose-200,
        .border-green-200, .border-emerald-200, .border-teal-200, .border-cyan-200,
        .border-blue-200, .border-indigo-200, .border-purple-200, .border-violet-200,
        .border-slate-200, .border-gray-200 { display: block; }
        
        /* Colores de badges */
        .bg-primary-100, .bg-secondary-100, .bg-orange-100, .bg-yellow-100,
        .bg-amber-100, .bg-red-100, .bg-pink-100, .bg-rose-100,
        .bg-green-100, .bg-emerald-100, .bg-teal-100, .bg-cyan-100,
        .bg-blue-100, .bg-indigo-100, .bg-purple-100, .bg-violet-100,
        .bg-slate-100, .bg-gray-100 { display: block; }
        
        /* Colores de texto para badges */
        .text-primary-600, .text-secondary-600, .text-orange-600, .text-yellow-600,
        .text-amber-600, .text-red-600, .text-pink-600, .text-rose-600,
        .text-green-600, .text-emerald-600, .text-teal-600, .text-cyan-600,
        .text-blue-600, .text-indigo-600, .text-purple-600, .text-violet-600,
        .text-slate-600, .text-gray-600 { display: block; }

        /* Circular Carousel Container */
        #category-carousel {
            animation: rotate-clockwise 30s linear infinite;
            transform-origin: center center;
        }
        
        /* Category Items */
        .category-item {
            opacity: 0;
            animation: fade-in-left 0.8s ease-out forwards;
            transition: all 0.3s ease;
            transform-origin: center center;
        }
        
        /* Staggered Animation Delays */
        .category-item:nth-child(1) { animation-delay: 0s; }
        .category-item:nth-child(2) { animation-delay: 0.3s; }
        .category-item:nth-child(3) { animation-delay: 0.6s; }
        .category-item:nth-child(4) { animation-delay: 0.9s; }
        .category-item:nth-child(5) { animation-delay: 1.2s; }
        .category-item:nth-child(6) { animation-delay: 1.5s; }
        .category-item:nth-child(7) { animation-delay: 1.8s; }
        .category-item:nth-child(8) { animation-delay: 2.1s; }
        
        /* Hover Effects */
        .category-item:hover {
            z-index: 10;
            transform: scale(1.1);
        }
        
        /* Counter-rotation for text readability */
        .category-item > a > div {
            animation: rotate-clockwise 30s linear infinite reverse;
        }
        
        /* Responsive adjustments */
        @media (max-width: 768px) {
            #category-carousel {
                animation: rotate-clockwise 45s linear infinite;
            }
            
            .category-item > a > div {
                animation: rotate-clockwise 45s linear infinite reverse;
            }
        }
        
        /* Fade out effect for items leaving view */
        .category-item.fade-out {
            animation: fade-out-right 0.8s ease-out forwards;
        }
        
        /* Loading animation */
        @keyframes rotate-clockwise {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        @keyframes fade-in-left {
            0% { 
                opacity: 0; 
                transform: translateX(-50px) scale(0.8); 
            }
            100% { 
                opacity: 1; 
                transform: translateX(0) scale(1); 
            }
        }
        
        @keyframes fade-out-right {
            0% { 
                opacity: 1; 
                transform: translateX(0) scale(1); 
            }
            100% { 
                opacity: 0; 
                transform: translateX(50px) scale(0.8); 
            }
        }
        
        /* Estilos para el hover del icono usando variables CSS */
        .category-icon-container:hover {
            background-color: var(--hover-bg) !important;
        }
        
        .category-icon-container:hover i {
            color: white !important;
        }
    </style>
</head>
<body class="font-sans bg-white">
    <!-- Header -->
    <header class="bg-white shadow-lg sticky top-0 z-50 transition-all duration-300" id="header">
        <!-- Top Bar -->
        <div class="bg-gradient-to-r from-primary-500 to-secondary-500 text-white py-2">
            <div class="container mx-auto px-4">
                <div class="flex justify-between items-center text-sm">
                    <div class="flex items-center space-x-4">
                        <span><i class="fas fa-phone mr-1"></i> +57 300 123 4567</span>
                        <span><i class="fas fa-envelope mr-1"></i> contacto@odisea.com</span>
                    </div>
                    <div class="flex items-center space-x-4">
                        <a href="#" class="hover:text-primary-200 transition-colors">
                            <i class="fab fa-instagram"></i>
                        </a>
                        <a href="#" class="hover:text-primary-200 transition-colors">
                            <i class="fab fa-facebook"></i>
                        </a>
                        <a href="#" class="hover:text-primary-200 transition-colors">
                            <i class="fab fa-tiktok"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Header -->
        <div class="container mx-auto px-4 py-4">
            <div class="flex items-center justify-between">
                <!-- Logo -->
                <div class="flex items-center">
                    <a href="/" class="text-3xl font-bold bg-gradient-to-r from-primary-500 to-secondary-500 bg-clip-text text-transparent">
                        Odisea
                    </a>
                </div>

                <!-- Search Bar -->
                <div class="hidden md:flex flex-1 max-w-lg mx-8">
                    <div class="relative w-full">
                        <input type="text" 
                               placeholder="Buscar productos, marcas..." 
                               class="w-full px-4 py-3 pr-12 border border-gray-300 rounded-full focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-all duration-300">
                        <button class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-primary-500 transition-colors">
                            <i class="fas fa-search text-lg"></i>
                        </button>
                    </div>
                </div>

                <!-- Header Actions -->
                <div class="flex items-center space-x-4">
                    <!-- User Account -->
                    <div class="relative group">
                        <button class="flex items-center space-x-1 text-gray-700 hover:text-primary-500 transition-colors">
                            <i class="fas fa-user text-xl"></i>
                            <span class="hidden lg:block">Mi Cuenta</span>
                        </button>
                        <!-- Dropdown Menu -->
                        <div class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-300 z-50">
                            <div class="py-2">
                                <?php if (isset($_SESSION['user_id'])): ?>
                                    <a href="mi-cuenta.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-primary-50 hover:text-primary-500 transition-colors">Mi Perfil</a>
                                    <a href="mis-pedidos.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-primary-50 hover:text-primary-500 transition-colors">Mis Pedidos</a>
                                    <a href="lista-deseos.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-primary-50 hover:text-primary-500 transition-colors">Lista de Deseos</a>
                                    <hr class="my-1">
                                    <a href="logout.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-primary-50 hover:text-primary-500 transition-colors">Cerrar Sesión</a>
                                <?php else: ?>
                                    <a href="login.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-primary-50 hover:text-primary-500 transition-colors">Iniciar Sesión</a>
                                    <a href="register.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-primary-50 hover:text-primary-500 transition-colors">Registrarse</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Wishlist -->
                    <button class="relative text-gray-700 hover:text-primary-500 transition-colors">
                        <i class="fas fa-heart text-xl"></i>
                        <?php if (isset($_SESSION['wishlist']) && count($_SESSION['wishlist']) > 0): ?>
                        <span class="absolute -top-2 -right-2 bg-primary-500 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center"><?php echo count($_SESSION['wishlist']); ?></span>
                        <?php endif; ?>
                    </button>

                    <!-- Shopping Cart -->
                    <button class="relative text-gray-700 hover:text-primary-500 transition-colors" id="cart-toggle">
                        <i class="fas fa-shopping-bag text-xl"></i>
                        <span class="absolute -top-2 -right-2 bg-primary-500 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center" id="cart-count">
                            <?php echo isset($_SESSION['cart']) ? array_sum(array_column($_SESSION['cart'], 'quantity')) : 0; ?>
                        </span>
                    </button>

                    <!-- Mobile Menu Toggle -->
                    <button class="md:hidden text-gray-700 hover:text-primary-500 transition-colors" id="mobile-menu-toggle">
                        <i class="fas fa-bars text-xl"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- Navigation -->
        <nav class="bg-gray-50 border-t border-gray-200">
            <div class="container mx-auto px-4">
                <div class="flex items-center justify-between">
                    <!-- Categories Menu -->
                    <div class="relative group">
                        <button class="flex items-center space-x-2 px-4 py-3 text-gray-700 hover:text-primary-500 transition-colors">
                            <i class="fas fa-bars"></i>
                            <span>Todas las Categorías</span>
                            <i class="fas fa-chevron-down text-sm"></i>
                        </button>
                        <!-- Mega Menu -->
                        <div class="absolute left-0 top-full w-screen max-w-4xl bg-white shadow-xl opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-300 z-40">
                            <div class="grid grid-cols-4 gap-6 p-6">
                                <?php
                                // Aquí podrías cargar categorías dinámicamente desde la base de datos
                                $categories = [
                                    [
                                        'name' => 'Rostro',
                                        'items' => ['Base de Maquillaje', 'Correctores', 'Rubor', 'Contorno', 'Iluminadores']
                                    ],
                                    [
                                        'name' => 'Ojos',
                                        'items' => ['Sombras', 'Delineadores', 'Máscaras', 'Cejas', 'Pestañas Postizas']
                                    ],
                                    [
                                        'name' => 'Labios',
                                        'items' => ['Labiales', 'Gloss', 'Delineadores', 'Bálsamos', 'Tintes']
                                    ],
                                    [
                                        'name' => 'Cuidado',
                                        'items' => ['Limpiadores', 'Hidratantes', 'Serums', 'Mascarillas', 'Protector Solar']
                                    ]
                                ];
                                
                                // Obtener categorías dinámicas desde la base de datos
                                try {
                                    $categoryModel = new Category();
                                    $dbCategories = $categoryModel->getActive();
                                    
                                    if (!empty($dbCategories)) {
                                        // Limpiar el array de categorías estáticas y usar las de la BD
                                        $categories = [];
                                        foreach ($dbCategories as $cat) {
                                            $categories[] = [
                                                'name' => $cat['name'],
                                                'items' => [$cat['name']] // Por ahora solo el nombre, se puede expandir con subcategorías
                                            ];
                                        }
                                    }
                                } catch (Exception $e) {
                                    error_log("Error loading categories: " . $e->getMessage());
                                }
                                
                                foreach ($categories as $category):
                                ?>
                                <div>
                                    <h3 class="font-semibold text-gray-900 mb-3"><?php echo $category['name']; ?></h3>
                                    <ul class="space-y-2">
                                        <?php foreach ($category['items'] as $item): ?>
                                        <li><a href="categoria.php?cat=<?php echo urlencode(strtolower($item)); ?>" class="text-gray-600 hover:text-primary-500 transition-colors"><?php echo $item; ?></a></li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Main Navigation -->
                    <div class="hidden md:flex items-center space-x-8">
                        <a href="index.php" class="text-gray-700 hover:text-primary-500 transition-colors font-medium">Inicio</a>
                        <a href="nuevos.php" class="text-gray-700 hover:text-primary-500 transition-colors font-medium">Lo Más Nuevo</a>
                        <a href="proximamente.php" class="text-gray-700 hover:text-primary-500 transition-colors font-medium">Próximamente</a>
                        <a href="ofertas.php" class="text-gray-700 hover:text-primary-500 transition-colors font-medium">Ofertas</a>
                        <a href="marcas.php" class="text-gray-700 hover:text-primary-500 transition-colors font-medium">Marcas</a>
                        <a href="blog.php" class="text-gray-700 hover:text-primary-500 transition-colors font-medium">Blog</a>
                    </div>

                    <!-- Promo Banner -->
                    <div class="hidden lg:block">
                        <span class="bg-gradient-to-r from-primary-500 to-secondary-500 text-white px-4 py-2 rounded-full text-sm font-medium animate-pulse-slow">
                            🎉 Envío GRATIS en compras +$150.000
                        </span>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Mobile Menu -->
        <div class="md:hidden bg-white border-t border-gray-200 hidden" id="mobile-menu">
            <div class="px-4 py-4 space-y-4">
                <!-- Mobile Search -->
                <div class="relative">
                    <input type="text" 
                           placeholder="Buscar productos..." 
                           class="w-full px-4 py-3 pr-12 border border-gray-300 rounded-full focus:outline-none focus:ring-2 focus:ring-primary-500">
                    <button class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
                
                <!-- Mobile Navigation Links -->
                <div class="space-y-2">
                    <a href="index.php" class="block py-2 text-gray-700 hover:text-primary-500 transition-colors">Inicio</a>
                    <a href="nuevos.php" class="block py-2 text-gray-700 hover:text-primary-500 transition-colors">Lo Más Nuevo</a>
                    <a href="proximamente.php" class="block py-2 text-gray-700 hover:text-primary-500 transition-colors">Próximamente</a>
                    <a href="ofertas.php" class="block py-2 text-gray-700 hover:text-primary-500 transition-colors">Ofertas</a>
                    <a href="marcas.php" class="block py-2 text-gray-700 hover:text-primary-500 transition-colors">Marcas</a>
                    <a href="blog.php" class="block py-2 text-gray-700 hover:text-primary-500 transition-colors">Blog</a>
                </div>
            </div>
        </div>
    </header>

    <!-- Hero Section -->
    <section class="relative overflow-hidden bg-gradient-to-br from-primary-50 via-white to-secondary-50">
        <div class="container mx-auto px-4 py-16 lg:py-24">
            <div class="grid lg:grid-cols-2 gap-12 items-center">
                <!-- Hero Content -->
                <div class="space-y-8" data-aos="fade-right">
                    <div class="space-y-4">
                        <h1 class="text-4xl lg:text-6xl font-bold leading-tight">
                            <span class="bg-gradient-to-r from-primary-600 to-secondary-600 bg-clip-text text-transparent">
                                Descubre tu
                            </span>
                            <br>
                            <span class="text-gray-900">belleza única</span>
                        </h1>
                        <p class="text-xl text-gray-600 leading-relaxed">
                            Explora nuestra colección exclusiva de maquillaje y productos de belleza. 
                            Desde las últimas tendencias hasta los clásicos atemporales.
                        </p>
                    </div>
                    
                    <div class="flex flex-col sm:flex-row gap-4">
                        <a href="catalogo.php" class="bg-gradient-to-r from-primary-500 to-secondary-500 text-white px-8 py-4 rounded-full font-semibold hover:shadow-lg transform hover:scale-105 transition-all duration-300 text-center">
                            Explorar Colección
                        </a>
                        <a href="ofertas.php" class="border-2 border-primary-500 text-primary-500 px-8 py-4 rounded-full font-semibold hover:bg-primary-500 hover:text-white transition-all duration-300 text-center">
                            Ver Ofertas
                        </a>
                    </div>

                    <!-- Features -->
                    <div class="grid grid-cols-3 gap-6 pt-8">
                        <div class="text-center">
                            <div class="w-12 h-12 bg-primary-100 rounded-full flex items-center justify-center mx-auto mb-2">
                                <i class="fas fa-shipping-fast text-primary-500 text-xl"></i>
                            </div>
                            <p class="text-sm font-medium text-gray-700">Envío Gratis</p>
                            <p class="text-xs text-gray-500">+$150.000</p>
                        </div>
                        <div class="text-center">
                            <div class="w-12 h-12 bg-secondary-100 rounded-full flex items-center justify-center mx-auto mb-2">
                                <i class="fas fa-award text-secondary-500 text-xl"></i>
                            </div>
                            <p class="text-sm font-medium text-gray-700">Calidad Premium</p>
                            <p class="text-xs text-gray-500">Marcas originales</p>
                        </div>
                        <div class="text-center">
                            <div class="w-12 h-12 bg-accent-100 rounded-full flex items-center justify-center mx-auto mb-2">
                                <i class="fas fa-headset text-accent-500 text-xl"></i>
                            </div>
                            <p class="text-sm font-medium text-gray-700">Soporte 24/7</p>
                            <p class="text-xs text-gray-500">Siempre aquí</p>
                        </div>
                    </div>
                </div>

                <!-- Hero Image -->
                <div class="relative" data-aos="fade-left">
                    <div class="relative z-10">
                        <img src="<?php echo ASSETS_URL; ?>/images/hero-model.jpg" 
                             alt="Modelo con maquillaje perfecto" 
                             class="w-full h-auto rounded-3xl shadow-2xl animate-float">
                    </div>
                    
                    <!-- Floating Elements -->
                    <div class="absolute -top-4 -right-4 w-20 h-20 bg-gradient-to-br from-primary-400 to-secondary-400 rounded-full opacity-20 animate-bounce-slow"></div>
                    <div class="absolute -bottom-8 -left-8 w-32 h-32 bg-gradient-to-br from-accent-400 to-primary-400 rounded-full opacity-10 animate-pulse-slow"></div>
                    
                    <!-- Product Cards -->
                    <div class="absolute top-8 -left-8 bg-white rounded-2xl shadow-lg p-4 animate-float" style="animation-delay: 1s;">
                        <div class="flex items-center space-x-3">
                            <img src="<?php echo ASSETS_URL; ?>/images/products/lipstick-thumb.jpg" alt="Labial Mate" class="w-12 h-12 rounded-lg">
                            <div>
                                <p class="font-semibold text-sm">Labial Mate</p>
                                <p class="text-primary-500 font-bold">$45.000</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="absolute bottom-16 -right-8 bg-white rounded-2xl shadow-lg p-4 animate-float" style="animation-delay: 2s;">
                        <div class="flex items-center space-x-3">
                            <img src="<?php echo ASSETS_URL; ?>/images/products/palette-thumb.jpg" alt="Paleta Sombras" class="w-12 h-12 rounded-lg">
                            <div>
                                <p class="font-semibold text-sm">Paleta Sombras</p>
                                <p class="text-primary-500 font-bold">$89.000</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Featured Categories - Luxury Design -->
    <section class="py-20 relative overflow-hidden" style="background: linear-gradient(135deg, #faf7f5 0%, #fdfcfa 50%, #f5ede8 100%);">
        <!-- Floating decorative elements -->
        <div class="absolute top-10 left-10 w-32 h-32 rounded-full opacity-10 animate-float" style="background: linear-gradient(135deg, #b08d80, #c4a575); animation-delay: 2s;"></div>
        <div class="absolute bottom-20 right-20 w-20 h-20 rounded-full opacity-15 animate-pulse-slow" style="background: linear-gradient(135deg, #c19590, #d4b896);"></div>
        <div class="absolute top-1/2 left-1/4 w-16 h-16 rounded-full opacity-8 animate-bounce-slow" style="background: linear-gradient(135deg, #a67c76, #b08d80); animation-delay: 4s;"></div>
        
        <div class="container mx-auto px-4 relative z-10">
            <div class="text-center mb-16" data-aos="fade-up">
                <div class="inline-block mb-6">
                    <span class="text-sm font-medium tracking-wide uppercase" style="color: #9d7a6b; letter-spacing: 2px;">Colección Exclusiva</span>
                </div>
                <h2 class="text-4xl lg:text-5xl font-light mb-6" style="color: #8a6657; font-family: 'Poppins', sans-serif;">
                    Explora por 
                    <span class="font-medium bg-gradient-to-r from-primary-500 to-secondary-500 bg-clip-text text-transparent">Categorías</span>
                </h2>
                <p class="text-lg max-w-3xl mx-auto leading-relaxed" style="color: #9d7a6b;">
                    Encuentra exactamente lo que buscas en nuestras categorías especializadas, 
                    cuidadosamente curadas para realzar tu belleza natural
                </p>
            </div>

            <!-- Elegant Grid Layout instead of circular -->
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-6 mb-16">
                <?php
                // Cargar categorías dinámicas desde la base de datos
                try {
                    $categoryModel = new Category();
                    $productModel = new Product();
                    $dbCategories = $categoryModel->getAll(['is_active' => 1]);
                    
                    // Iconos elegantes para categorías
                    $elegantIcons = [
                        'rostro' => ['icon' => '💄', 'gradient' => 'from-rose-200 to-pink-100'],
                        'base' => ['icon' => '🎨', 'gradient' => 'from-amber-200 to-orange-100'],
                        'ojos' => ['icon' => '👁️', 'gradient' => 'from-purple-200 to-indigo-100'],
                        'labios' => ['icon' => '💋', 'gradient' => 'from-red-200 to-rose-100'],
                        'cejas' => ['icon' => '🖌️', 'gradient' => 'from-amber-200 to-yellow-100'],
                        'cuidado' => ['icon' => '🌸', 'gradient' => 'from-green-200 to-emerald-100'],
                        'herramientas' => ['icon' => '✨', 'gradient' => 'from-indigo-200 to-purple-100'],
                        'accesorios' => ['icon' => '💎', 'gradient' => 'from-violet-200 to-purple-100']
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
                    $iconData = $elegantIcons[$slug] ?? ['icon' => '✨', 'gradient' => 'from-gray-200 to-gray-100'];
                ?>
                <!-- Elegant Category Card -->
                <div class="group cursor-pointer" data-aos="fade-up" data-aos-delay="<?php echo $delay; ?>">
                    <a href="categoria.php?categoria=<?php echo urlencode($slug); ?>" class="block">
                        <div class="relative overflow-hidden rounded-3xl p-8 text-center transition-all duration-500 transform group-hover:-translate-y-2 group-hover:shadow-2xl"
                             style="background: linear-gradient(135deg, rgba(250, 247, 245, 0.9), rgba(253, 252, 250, 0.9)); backdrop-filter: blur(20px); border: 1px solid rgba(176, 141, 128, 0.1);">
                            
                            <!-- Shimmer effect on hover -->
                            <div class="absolute inset-0 opacity-0 group-hover:opacity-100 transition-opacity duration-700"
                                 style="background: linear-gradient(45deg, transparent 30%, rgba(196, 165, 117, 0.1) 50%, transparent 70%); animation: shimmer 2s infinite;"></div>
                            
                            <!-- Icon Container -->
                            <div class="relative z-10 mb-6">
                                <div class="w-20 h-20 mx-auto rounded-2xl flex items-center justify-center text-3xl mb-4 group-hover:scale-110 transition-transform duration-300"
                                     style="background: linear-gradient(135deg, rgba(176, 141, 128, 0.1), rgba(196, 165, 117, 0.1)); backdrop-filter: blur(10px);">
                                    <span class="filter drop-shadow-sm"><?php echo $iconData['icon']; ?></span>
                                </div>
                                
                                <h3 class="font-medium text-lg mb-2" style="color: #8a6657;">
                                    <?php echo htmlspecialchars($category['name']); ?>
                                </h3>
                                
                                <div class="w-12 h-0.5 mx-auto rounded-full group-hover:w-16 transition-all duration-300"
                                     style="background: linear-gradient(to right, #b08d80, #c4a575);"></div>
                            </div>
                            
                            <!-- Hover glow effect -->
                            <div class="absolute inset-0 rounded-3xl opacity-0 group-hover:opacity-100 transition-opacity duration-500"
                                 style="box-shadow: 0 0 30px rgba(176, 141, 128, 0.2);"></div>
                        </div>
                    </a>
                </div>
                <?php endforeach; ?>
            </div>
            
            <!-- Elegant CTA Button -->
            <div class="text-center" data-aos="fade-up" data-aos-delay="600">
                <a href="catalogo.php" 
                   class="inline-flex items-center px-10 py-4 rounded-full font-medium text-white transition-all duration-500 transform hover:-translate-y-1 hover:shadow-2xl"
                   style="background: linear-gradient(135deg, #b08d80, #c4a575); box-shadow: 0 8px 25px rgba(176, 141, 128, 0.3);">
                    <span class="mr-3">Ver Todas las Categorías</span>
                    <svg class="w-5 h-5 transition-transform duration-300 group-hover:translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
                    </svg>
                </a>
            </div>
        </div>
    </section>

    <!-- Categories Container -->
    <?php
    // Cargar categorías desde la base de datos
    try {
        $categoryModel = new Category();
        $productModel = new Product();
        $dbCategories = $categoryModel->getAll(['is_active' => 1]);
        
        // Iconos y colores para categorías con sistema mejorado
        $categoryIcons = [
            'rostro' => ['icon' => 'fa-palette', 'color' => 'primary'],
            'base' => ['icon' => 'fa-paint-brush', 'color' => 'secondary'], 
            'ojos' => ['icon' => 'fa-eye', 'color' => 'blue'],
            'labios' => ['icon' => 'fa-kiss-wink-heart', 'color' => 'red'],
            'cejas' => ['icon' => 'fa-minus', 'color' => 'amber'],
            'cuidado' => ['icon' => 'fa-spa', 'color' => 'green'],
            'herramientas' => ['icon' => 'fa-paint-brush', 'color' => 'indigo'],
            'accesorios' => ['icon' => 'fa-gem', 'color' => 'purple'],
            'mascara' => ['icon' => 'fa-eye', 'color' => 'purple'],
            'sombras' => ['icon' => 'fa-palette', 'color' => 'indigo'],
            'delineador' => ['icon' => 'fa-pencil-alt', 'color' => 'gray'],
            'corrector' => ['icon' => 'fa-magic', 'color' => 'yellow'],
            'polvo' => ['icon' => 'fa-circle', 'color' => 'pink'],
            'rubor' => ['icon' => 'fa-heart', 'color' => 'rose'],
            'bronceador' => ['icon' => 'fa-sun', 'color' => 'orange'],
            'primer' => ['icon' => 'fa-layer-group', 'color' => 'teal']
        ];
        
        // Función mejorada para obtener iconos y colores
        function getCategoryIconData($category, $categoryIcons) {
            $name = strtolower($category['name'] ?? '');
            $slug = strtolower($category['slug'] ?? '');
            
            // Buscar por palabras clave en el nombre o slug
            foreach ($categoryIcons as $key => $iconData) {
                if (strpos($slug, $key) !== false || strpos($name, $key) !== false) {
                    return $iconData;
                }
            }
            
            // Búsqueda por palabras clave específicas
            $keywords = [
                'rostro' => ['face', 'cara', 'facial'],
                'ojos' => ['eye', 'ojo', 'ocular'],
                'labios' => ['lip', 'labio', 'boca'],
                'cuidado' => ['care', 'skin', 'piel', 'tratamiento'],
                'base' => ['foundation', 'fundacion'],
                'sombras' => ['shadow', 'eyeshadow'],
                'mascara' => ['mascara', 'rimel']
            ];
            
            foreach ($keywords as $category_key => $keyword_list) {
                foreach ($keyword_list as $keyword) {
                    if (strpos($slug, $keyword) !== false || strpos($name, $keyword) !== false) {
                        return $categoryIcons[$category_key] ?? ['icon' => 'fa-star', 'color' => 'gray'];
                    }
                }
            }
            
            // Icono por defecto
            return ['icon' => 'fa-star', 'color' => 'gray'];
        }
        
        if (!empty($dbCategories)) {
            $featuredCategories = [];
            foreach ($dbCategories as $cat) {
                $iconData = getCategoryIconData($cat, $categoryIcons);
                
                // Contar productos en esta categoría
                $productCount = 0;
                try {
                    $productCount = $productModel->countByCategory($cat['id']);
                } catch (Exception $e) {
                    error_log("Error counting products for category {$cat['id']}: " . $e->getMessage());
                }
                
                $featuredCategories[] = [
                    'id' => $cat['id'],
                    'name' => $cat['name'],
                    'description' => $cat['description'] ?: 'Productos de ' . $cat['name'],
                    'icon' => $iconData['icon'],
                    'color' => $iconData['color'],
                    'slug' => $cat['slug'] ?? strtolower(str_replace(' ', '-', $cat['name'])),
                    'product_count' => $productCount
                ];
            }
        } else {
            // Fallback a categorías estáticas si no hay en la BD
            $featuredCategories = [
                [
                    'name' => 'Rostro',
                    'description' => 'Base, correctores, rubor',
                    'icon' => 'fa-palette',
                    'color' => 'primary',
                    'slug' => 'rostro'
                ],
                [
                    'name' => 'Ojos',
                    'description' => 'Sombras, delineadores, máscaras',
                    'icon' => 'fa-eye',
                    'color' => 'secondary',
                    'slug' => 'ojos'
                ],
                [
                    'name' => 'Labios',
                    'description' => 'Labiales, gloss, tintes',
                    'icon' => 'fa-kiss-wink-heart',
                    'color' => 'red',
                    'slug' => 'labios'
                ],
                [
                    'name' => 'Cejas',
                    'description' => 'Definición y cuidado',
                    'icon' => 'fa-minus',
                    'color' => 'amber',
                    'slug' => 'cejas'
                ],
                [
                    'name' => 'Cuidado',
                    'description' => 'Limpiadores, hidratantes',
                    'icon' => 'fa-spa',
                    'color' => 'green',
                    'slug' => 'cuidado'
                ],
                [
                    'name' => 'Herramientas',
                    'description' => 'Brochas y accesorios',
                    'icon' => 'fa-paint-brush',
                    'color' => 'indigo',
                    'slug' => 'herramientas'
                ]
            ];
        }
    } catch (Exception $e) {
        error_log("Error loading categories: " . $e->getMessage());
        echo "<!-- Error: " . htmlspecialchars($e->getMessage()) . " -->";
        // Categorías de emergencia mínimas
        $featuredCategories = [
            [
                'name' => 'Maquillaje',
                'description' => 'Todos los productos',
                'icon' => 'fa-palette',
                'color' => 'primary',
                'slug' => 'maquillaje'
            ],
            [
                'name' => 'Rostro',
                'description' => 'Base, correctores, rubor',
                'icon' => 'fa-palette',
                'color' => 'pink',
                'slug' => 'rostro'
            ],
            [
                'name' => 'Ojos',
                'description' => 'Sombras, delineadores, máscaras',
                'icon' => 'fa-eye',
                'color' => 'blue',
                'slug' => 'ojos'
            ],
            [
                'name' => 'Labios',
                'description' => 'Labiales, gloss, tintes',
                'icon' => 'fa-kiss-wink-heart',
                'color' => 'red',
                'slug' => 'labios'
            ]
        ];
    }
    
    // Mezclar el array para mostrar categorías en orden aleatorio
    if (!empty($featuredCategories)) {
        shuffle($featuredCategories);
    }
    
    // Función para obtener color hexadecimal
    function getColorHex($colorName, $shade = 500) {
        $colors = [
            'primary' => '#b08d80',   // Mauve neutro elegante
            'secondary' => '#c4a575', // Oro suave
            'pink' => '#d4a5a5',      // Rosa empolvado
            'purple' => '#b8a5c4',    // Lavanda suave
            'blue' => '#a5b8c4',      // Azul empolvado
            'green' => '#a5c4b0',     // Verde salvia
            'orange' => '#d4b5a5',    // Melocotón suave
            'red' => '#c4a5a5',       // Rosa antiguo
            'yellow' => '#d4c4a5',    // Champagne
            'indigo' => '#a5a5c4',    // Índigo empolvado
            'gray' => '#b8b8b8'       // Gris perla
        ];
        
        return $colors[$colorName] ?? $colors['primary'];
    }
    ?>

    <!-- Categories Container -->
            <div class="relative mb-16">
                <!-- Categories Grid -->
                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6 gap-6">
                    <?php foreach ($featuredCategories as $index => $category): 
                        // Delay progresivo para efecto de desvanecimiento
                        $fadeDelay = $index * 100; // Milisegundos
                        
                        // Determinar la URL de la categoría
                        if (isset($category['slug']) && !empty($category['slug'])) {
                            $categoryUrl = "categoria.php?categoria=" . urlencode($category['slug']);
                        } elseif (isset($category['id']) && !empty($category['id'])) {
                            $categoryUrl = "categoria.php?id=" . $category['id'];
                        } else {
                            // Generar slug desde el nombre si no existe
                            $generatedSlug = strtolower(str_replace([' ', 'á', 'é', 'í', 'ó', 'ú', 'ñ'], ['-', 'a', 'e', 'i', 'o', 'u', 'n'], $category['name']));
                            $categoryUrl = "categoria.php?categoria=" . urlencode($generatedSlug);
                        }
                        
                        // Generar colores para el degradado y badges
                        $primaryColor = getColorHex($category['color'] ?? 'primary');
                        $rgbPrimary = sscanf($primaryColor, "#%02x%02x%02x");
                        $rgbPrimaryDark = [
                            max(0, $rgbPrimary[0] - 30),
                            max(0, $rgbPrimary[1] - 30),
                            max(0, $rgbPrimary[2] - 30)
                        ];
                        $primaryColorDark = sprintf("#%02x%02x%02x", $rgbPrimaryDark[0], $rgbPrimaryDark[1], $rgbPrimaryDark[2]);
                        
                        // Colores para badges con mejor contraste
                        $badgeBg = $primaryColor;
                        $badgeText = '#ffffff';
                        
                        // Usar ícono de la categoría o uno por defecto
                        $categoryIcon = $category['icon'] ?? 'fa-tag';
                        if (!str_starts_with($categoryIcon, 'fa-')) {
                            $categoryIcon = 'fa-' . $categoryIcon;
                        }
                    ?>
               
                                
                                <!-- Tooltip with description -->
                                <div class="absolute bottom-full left-1/2 transform -translate-x-1/2 mb-2 opacity-0 group-hover:opacity-100 transition-opacity duration-300 pointer-events-none">
                                    <div class="bg-gray-900 text-white text-xs rounded-lg px-3 py-2 whitespace-nowrap">
                                        <?php echo htmlspecialchars($category['description']); ?>
                                        <div class="absolute top-full left-1/2 transform -translate-x-1/2 w-0 h-0 border-l-4 border-r-4 border-t-4 border-transparent border-t-gray-900"></div>
                                    </div>
                                </div>
                            </div>
                        </a>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <?php if (empty($featuredCategories)): ?>
                <!-- Mensaje cuando no hay categorías -->
                <div class="absolute inset-0 flex items-center justify-center">
                    <div class="text-center py-12">
                        <div class="text-gray-400 text-6xl mb-4">
                            <i class="fas fa-layer-group"></i>
                        </div>
                        <h3 class="text-xl font-semibold text-gray-600 mb-2">No hay categorías disponibles</h3>
                        <p class="text-gray-500 mb-6">Las categorías aparecerán aquí una vez que se agreguen desde el panel de administración.</p>
                        <a href="admin/" class="bg-primary-500 text-white px-6 py-3 rounded-lg font-semibold hover:bg-primary-600 transition-colors">
                            Ir al Panel de Admin
                        </a>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- Featured Products - Luxury Design -->
    <section class="py-20 relative overflow-hidden" style="background: linear-gradient(135deg, #fbf9f7 0%, #ffffff 50%, #f6f0ed 100%);">
        <!-- Floating decorative elements -->
        <div class="absolute top-16 right-16 w-24 h-24 rounded-full opacity-12 animate-float" style="background: linear-gradient(135deg, #a67c76, #c19590); animation-delay: 1s;"></div>
        <div class="absolute bottom-16 left-16 w-40 h-40 rounded-full opacity-8 animate-pulse-slow" style="background: linear-gradient(135deg, #d4b5ae, #e8d5d0);"></div>
        <div class="absolute top-1/3 right-1/3 w-16 h-16 rounded-full opacity-10 animate-bounce-slow" style="background: linear-gradient(135deg, #b08d80, #c4a575); animation-delay: 3s;"></div>
        
        <div class="container mx-auto px-4 relative z-10">
            <div class="text-center mb-16" data-aos="fade-up">
                <div class="inline-block mb-6">
                    <span class="text-sm font-medium tracking-wide uppercase" style="color: #9d7a6b; letter-spacing: 2px;">Selección Especial</span>
                </div>
                <h2 class="text-4xl lg:text-5xl font-light mb-6" style="color: #8a6657; font-family: 'Poppins', sans-serif;">
                    Productos 
                    <span class="font-medium bg-gradient-to-r from-primary-500 to-secondary-500 bg-clip-text text-transparent">Destacados</span>
                </h2>
                <p class="text-lg max-w-3xl mx-auto leading-relaxed" style="color: #9d7a6b;">
                    Los favoritos de nuestras clientas, seleccionados especialmente para ti
                    con la más alta calidad y resultados excepcionales
                </p>
            </div>

            <!-- Elegant Products Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-8 mb-16">
                <?php
                // Cargar productos destacados desde la base de datos
                try {
                    $product = new Product();
                    $brand = new Brand();
                    
                    // Obtener productos destacados o más vendidos
                    $featuredProducts = $product->getFeaturedProducts(8);
                    
                    if (empty($featuredProducts)) {
                        // Si no hay productos destacados, mostrar algunos productos activos
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
                        
                        // Obtener información de la marca
                        $productBrand = !empty($product['brand_id']) ? $brand->getById($product['brand_id']) : null;
                        $brandName = $productBrand ? $productBrand['name'] : '';
                        
                        // Calcular descuento si hay precio de oferta
                        $discount = 0;
                        if (!empty($product['sale_price']) && $product['sale_price'] < $product['price']) {
                            $discount = round((($product['price'] - $product['sale_price']) / $product['price']) * 100);
                        }
                        
                        // Imagen del producto
                        $productImage = !empty($product['main_image']) ? UPLOADS_URL . '/products/' . $product['main_image'] : ASSETS_URL . '/images/placeholder-product.svg';
                        
                        // Precio a mostrar
                        $displayPrice = !empty($product['sale_price']) ? $product['sale_price'] : $product['price'];
                        $originalPrice = (!empty($product['sale_price']) && $product['sale_price'] < $product['price']) ? $product['price'] : null;
                    ?>
                   
                           
                                
                                <!-- Heart icon -->
                                <div class="absolute top-4 right-4 opacity-0 group-hover:opacity-100 transition-opacity duration-300">
                                    <button class="w-10 h-10 rounded-full flex items-center justify-center transition-all duration-300"
                                            style="background: rgba(255, 255, 255, 0.9); backdrop-filter: blur(10px);">
                                        <svg class="w-5 h-5" style="color: #b08d80;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                            
                            <!-- Product Info -->
                            <div class="p-6">
                                <?php if ($brandName): ?>
                                <p class="text-xs font-medium mb-2 tracking-wide uppercase" style="color: #c4a575; letter-spacing: 1px;">
                                    <?php echo htmlspecialchars($brandName); ?>
                                </p>
                                <?php endif; ?>
                                
                                <h3 class="font-medium text-lg mb-3 line-clamp-2" style="color: #8a6657;">
                                    <?php echo htmlspecialchars($product['name']); ?>
                                </h3>
                                
                                <!-- Price -->
                                <div class="flex items-center justify-between mb-4">
                                    <div class="flex items-center space-x-2">
                                        <span class="text-xl font-medium" style="color: #9d7a6b;">
                                            $<?php echo number_format($displayPrice, 0, ',', '.'); ?>
                                        </span>
                                        <?php if ($originalPrice): ?>
                                        <span class="text-sm line-through opacity-60" style="color: #c19590;">
                                            $<?php echo number_format($originalPrice, 0, ',', '.'); ?>
                                        </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <!-- Add to Cart Button -->
                                <button class="w-full py-3 rounded-2xl font-medium text-white transition-all duration-300 transform group-hover:scale-105"
                                        style="background: linear-gradient(135deg, #b08d80, #c4a575); box-shadow: 0 4px 15px rgba(176, 141, 128, 0.2);">
                                    Agregar al Carrito
                                </button>
                            </div>
                            
                            <!-- Shimmer effect -->
                            <div class="absolute inset-0 opacity-0 group-hover:opacity-100 transition-opacity duration-700 pointer-events-none"
                                 style="background: linear-gradient(45deg, transparent 30%, rgba(196, 165, 117, 0.1) 50%, transparent 70%); animation: shimmer 2s infinite;"></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    
                <?php else: ?>
                    <!-- Empty State -->
                    <div class="col-span-full">
                        <div class="text-center py-16">
                            <div class="w-24 h-24 mx-auto mb-6 rounded-full flex items-center justify-center"
                                 style="background: linear-gradient(135deg, rgba(176, 141, 128, 0.1), rgba(196, 165, 117, 0.1));">
                                <svg class="w-12 h-12" style="color: #b08d80;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                                </svg>
                            </div>
                            <h3 class="text-2xl font-light mb-4" style="color: #8a6657;">
                                No hay productos disponibles
                            </h3>
                            <p class="text-lg mb-8 max-w-md mx-auto" style="color: #9d7a6b;">
                                Los productos aparecerán aquí una vez que se agreguen desde el panel de administración.
                            </p>
                            <a href="admin/" 
                               class="inline-flex items-center px-8 py-3 rounded-full font-medium text-white transition-all duration-300"
                               style="background: linear-gradient(135deg, #b08d80, #c4a575);">
                                <span class="mr-2">Panel de Administración</span>
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                                </svg>
                            </a>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Elegant CTA Button -->
            <?php if (!empty($featuredProducts)): ?>
            <div class="text-center" data-aos="fade-up" data-aos-delay="800">
                <a href="catalogo.php" 
                   class="inline-flex items-center px-12 py-4 rounded-full font-medium text-white transition-all duration-500 transform hover:-translate-y-1 hover:shadow-2xl"
                   style="background: linear-gradient(135deg, #b08d80, #c4a575); box-shadow: 0 8px 25px rgba(176, 141, 128, 0.3);">
                    <span class="mr-3">Ver Todos los Productos</span>
                    <svg class="w-5 h-5 transition-transform duration-300 group-hover:translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
                    </svg>
                </a>
            </div>
            <?php endif; ?>
        </div>
    </section>
                <!-- Productos Destacados Dinámicos -->
                <?php
                // Cargar productos destacados desde la base de datos
                try {
                    $product = new Product();
                    $brand = new Brand();
                    
                    // Obtener productos destacados o más vendidos
                    $featuredProducts = $product->getFeaturedProducts(8);
                    
                    if (empty($featuredProducts)) {
                        // Si no hay productos destacados, mostrar algunos productos activos
                        $featuredProducts = $product->getAll(['status' => 'active', 'limit' => 8]);
                    }
                    
                } catch (Exception $e) {
                    $featuredProducts = [];
                    error_log("Error loading featured products: " . $e->getMessage());
                }
                ?>
                
                <?php if (!empty($featuredProducts)): ?>
                    <?php foreach ($featuredProducts as $index => $product): 
                        $delay = ($index + 1) * 100;
                        
                        // Obtener información de la marca
                        $productBrand = !empty($product['brand_id']) ? $brand->getById($product['brand_id']) : null;
                        $brandName = $productBrand ? $productBrand['name'] : 'Sin marca';
                        
                        // Calcular descuento si hay precio de oferta
                        $discount = 0;
                        if (!empty($product['sale_price']) && $product['sale_price'] < $product['price']) {
                            $discount = round((($product['price'] - $product['sale_price']) / $product['price']) * 100);
                        }
                        
                        // Determinar badge según las características del producto
                        $badge = '';
                        $badgeColor = '';
                        
                        if (!empty($product['is_featured']) && $product['is_featured']) {
                            $badge = 'DESTACADO';
                            $badgeColor = 'primary-500';
                        } elseif ($discount > 0) {
                            $badge = 'OFERTA';
                            $badgeColor = 'red-500';
                        } elseif (!empty($product['created_at']) && strtotime($product['created_at']) > strtotime('-30 days')) {
                            $badge = 'NUEVO';
                            $badgeColor = 'green-500';
                        }
                        
                        // Imagen del producto
                        $productImage = !empty($product['main_image']) ? UPLOADS_URL . '/products/' . $product['main_image'] : ASSETS_URL . '/images/placeholder-product.svg';
                        
                        // Precio a mostrar
                        $displayPrice = !empty($product['sale_price']) ? $product['sale_price'] : $product['price'];
                        $originalPrice = (!empty($product['sale_price']) && $product['sale_price'] < $product['price']) ? $product['price'] : null;
                    ?>
                    <div class="bg-white rounded-2xl shadow-lg overflow-hidden group hover:shadow-xl transition-all duration-300" data-aos="fade-up" data-aos-delay="<?php echo $delay; ?>">
                        <div class="relative overflow-hidden">
                            <img src="<?php echo $productImage; ?>" 
                                 alt="<?php echo htmlspecialchars($product['name']); ?>" 
                                 class="w-full h-64 object-cover group-hover:scale-110 transition-transform duration-500"
                                 onerror="this.src='<?php echo ASSETS_URL; ?>/images/placeholder-product.svg'">
                            
                            <!-- Product Badges -->
                            <?php if ($badge): ?>
                            <div class="absolute top-4 left-4">
                                <span class="bg-<?php echo $badgeColor; ?> text-white px-3 py-1 rounded-full text-xs font-semibold"><?php echo $badge; ?></span>
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
                                    <span class="text-xl font-bold text-primary-500">$<?php echo number_format($displayPrice, 0, ',', '.'); ?></span>
                                    <?php if ($originalPrice): ?>
                                    <span class="text-sm text-gray-400 line-through">$<?php echo number_format($originalPrice, 0, ',', '.'); ?></span>
                                    <?php endif; ?>
                                </div>
                                <?php if ($discount > 0): ?>
                                <span class="text-sm text-green-600 font-semibold">-<?php echo $discount; ?>%</span>
                                <?php endif; ?>
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
                <?php else: ?>
                    <!-- Mensaje cuando no hay productos -->
                    <div class="col-span-full text-center py-12">
                        <div class="text-gray-400 text-6xl mb-4">
                            <i class="fas fa-box-open"></i>
                        </div>
                        <h3 class="text-xl font-semibold text-gray-600 mb-2">No hay productos disponibles</h3>
                        <p class="text-gray-500">Los productos aparecerán aquí una vez que se agreguen desde el panel de administración.</p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- View All Button -->
            <div class="text-center mt-12" data-aos="fade-up">
                <button class="bg-gradient-to-r from-primary-500 to-secondary-500 text-white px-8 py-4 rounded-full font-semibold hover:shadow-lg transform hover:scale-105 transition-all duration-300">
                    Ver Todos los Productos
                </button>
            </div>
        </div>
    </section>

    <!-- New Arrivals -->
    <section class="py-16 bg-white">
        <div class="container mx-auto px-4">
            <div class="flex items-center justify-between mb-12" data-aos="fade-up">
                <div>
                    <h2 class="text-3xl lg:text-4xl font-bold text-gray-900 mb-4">
                        Lo Más Nuevo
                    </h2>
                    <p class="text-gray-600 text-lg">
                        Las últimas tendencias en maquillaje y belleza
                    </p>
                </div>
                <button class="hidden md:block border-2 border-primary-500 text-primary-500 px-6 py-3 rounded-full font-semibold hover:bg-primary-500 hover:text-white transition-all duration-300">
                    Ver Todo
                </button>
            </div>

            <!-- Product Slider -->
            <div class="relative">
                <div class="flex space-x-6 overflow-x-auto pb-4 scrollbar-hide" id="new-arrivals-slider">
                    <!-- New Product Cards -->
                    <?php
                    // Cargar productos nuevos dinámicamente desde la base de datos
                    try {
                        $product = new Product();
                        $brand = new Brand();
                        
                        // Obtener los 10 productos más recientes ordenados por fecha de creación
                        $newProducts = $product->getNewestProducts(10);
                        
                        if (empty($newProducts)) {
                            // Productos de ejemplo si no hay en la base de datos
                            $newProducts = [
                                [
                                    'id' => 1,
                                    'name' => 'Soft Pinch Liquid Blush',
                                    'brand' => 'Rare Beauty',
                                    'price' => 67000,
                                    'image' => ASSETS_URL . '/images/products/rare-beauty.jpg',
                                    'created_at' => date('Y-m-d H:i:s')
                                ],
                                [
                                    'id' => 2,
                                    'name' => 'Cloud Paint Blush',
                                    'brand' => 'Glossier',
                                    'price' => 54000,
                                    'image' => ASSETS_URL . '/images/products/glossier.jpg',
                                    'created_at' => date('Y-m-d H:i:s')
                                ],
                                [
                                    'id' => 3,
                                    'name' => 'Niacinamide 10% + Zinc 1%',
                                    'brand' => 'The Ordinary',
                                    'price' => 32000,
                                    'image' => ASSETS_URL . '/images/products/ordinary.jpg',
                                    'created_at' => date('Y-m-d H:i:s')
                                ],
                                [
                                    'id' => 4,
                                    'name' => 'Ruby Woo Lipstick',
                                    'brand' => 'MAC',
                                    'price' => 72000,
                                    'image' => ASSETS_URL . '/images/products/mac.jpg',
                                    'created_at' => date('Y-m-d H:i:s')
                                ]
                            ];
                        }
                    } catch (Exception $e) {
                        error_log("Error loading new products: " . $e->getMessage());
                        // Productos de respaldo en caso de error
                        $newProducts = [
                            [
                                'id' => 1,
                                'name' => 'Soft Pinch Liquid Blush',
                                'brand' => 'Rare Beauty',
                                'price' => 67000,
                                'image' => ASSETS_URL . '/images/products/rare-beauty.jpg',
                                'created_at' => date('Y-m-d H:i:s')
                            ],
                            [
                                'id' => 2,
                                'name' => 'Cloud Paint Blush',
                                'brand' => 'Glossier',
                                'price' => 54000,
                                'image' => ASSETS_URL . '/images/products/glossier.jpg',
                                'created_at' => date('Y-m-d H:i:s')
                            ]
                        ];
                    }
                    
                    foreach ($newProducts as $index => $productItem):
                        $delay = ($index + 1) * 100;
                        
                        // Determinar si el producto es realmente nuevo (últimos 30 días)
                        $isNew = false;
                        if (isset($productItem['created_at'])) {
                            $createdDate = new DateTime($productItem['created_at']);
                            $now = new DateTime();
                            $diff = $now->diff($createdDate);
                            $isNew = $diff->days <= 30;
                        }
                        
                        // Generar URL de imagen si no existe
                        $productImage = $productItem['image'] ?? ASSETS_URL . '/images/placeholder-product.svg';
                        if (!empty($productItem['id']) && (empty($productItem['image']) || !file_exists($productItem['image']))) {
                            $productImage = ASSETS_URL . '/images/placeholder-product.svg';
                        }
                        
                        // Obtener nombre de marca si es un ID
                        $brandName = $productItem['brand'] ?? 'Marca';
                        if (is_numeric($brandName) && class_exists('Brand')) {
                            try {
                                $brandModel = new Brand();
                                $brandData = $brandModel->getById($brandName);
                                $brandName = $brandData['name'] ?? 'Marca';
                            } catch (Exception $e) {
                                $brandName = 'Marca';
                            }
                        }
                    ?>
                    <div class="flex-none w-72" data-aos="fade-up" data-aos-delay="<?php echo $delay; ?>">
                        <div class="bg-white rounded-2xl shadow-lg overflow-hidden group hover:shadow-xl transition-all duration-300">
                            <div class="relative overflow-hidden">
                                <img src="<?php echo htmlspecialchars($productImage); ?>" 
                                     alt="<?php echo htmlspecialchars($productItem['name'] ?? 'Producto'); ?>" 
                                     class="w-full h-48 object-cover group-hover:scale-110 transition-transform duration-500"
                                     onerror="this.src='<?php echo ASSETS_URL; ?>/images/placeholder-product.svg'">
                                
                                <?php if ($isNew): ?>
                                <div class="absolute top-4 left-4">
                                    <span class="bg-gradient-to-r from-green-500 to-emerald-500 text-white px-3 py-1 rounded-full text-xs font-semibold shadow-lg">NUEVO</span>
                                </div>
                                <?php endif; ?>
                                
                                <div class="absolute top-4 right-4 opacity-0 group-hover:opacity-100 transition-opacity duration-300">
                                    <button onclick="toggleWishlist(<?php echo $productItem['id'] ?? 0; ?>)" 
                                            class="w-10 h-10 bg-white rounded-full flex items-center justify-center shadow-lg hover:bg-primary-500 hover:text-white transition-colors duration-300"
                                            title="Agregar a favoritos">
                                        <i class="far fa-heart text-sm"></i>
                                    </button>
                                </div>
                            </div>
                            
                            <div class="p-4">
                                <span class="text-sm text-gray-500"><?php echo htmlspecialchars($brandName); ?></span>
                                <h3 class="font-semibold text-gray-900 mb-2 line-clamp-2">
                                    <a href="product-details.php?id=<?php echo $productItem['id'] ?? 0; ?>" 
                                       class="hover:text-primary-500 transition-colors duration-300">
                                        <?php echo htmlspecialchars($productItem['name'] ?? 'Producto'); ?>
                                    </a>
                                </h3>
                                <div class="flex items-center justify-between mb-2">
                                    <span class="text-lg font-bold text-primary-500">
                                        $<?php echo number_format($productItem['price'] ?? 0, 0, ',', '.'); ?>
                                    </span>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <button onclick="addToCart(<?php echo $productItem['id'] ?? 0; ?>)" 
                                            class="flex-1 bg-gradient-to-r from-primary-500 to-secondary-500 text-white px-3 py-2 rounded-full text-sm font-semibold hover:shadow-lg transform hover:scale-105 transition-all duration-300"
                                            title="Agregar al carrito">
                                        <i class="fas fa-shopping-cart mr-1"></i> Agregar
                                    </button>
                                    <a href="product-details.php?id=<?php echo $productItem['id'] ?? 0; ?>" 
                                       class="w-10 h-10 bg-gray-100 rounded-full flex items-center justify-center hover:bg-primary-500 hover:text-white transition-colors duration-300"
                                       title="Ver detalles">
                                        <i class="fas fa-eye text-sm"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <!-- Slider Navigation -->
                <button class="absolute left-0 top-1/2 transform -translate-y-1/2 -translate-x-4 w-12 h-12 bg-white rounded-full shadow-lg flex items-center justify-center hover:bg-primary-500 hover:text-white transition-colors duration-300" id="prev-new">
                    <i class="fas fa-chevron-left"></i>
                </button>
                <button class="absolute right-0 top-1/2 transform -translate-y-1/2 translate-x-4 w-12 h-12 bg-white rounded-full shadow-lg flex items-center justify-center hover:bg-primary-500 hover:text-white transition-colors duration-300" id="next-new">
                    <i class="fas fa-chevron-right"></i>
                </button>
            </div>
        </div>
    </section>

    <!-- Coming Soon -->
    <section class="py-16 bg-gradient-to-br from-primary-50 to-secondary-50">
        <div class="container mx-auto px-4">
            <div class="text-center mb-12" data-aos="fade-up">
                <h2 class="text-3xl lg:text-4xl font-bold text-gray-900 mb-4">
                    Próximamente
                </h2>
                <p class="text-gray-600 text-lg max-w-2xl mx-auto">
                    Sé la primera en conocer nuestros próximos lanzamientos exclusivos
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                <!-- Coming Soon Card 1 -->
                <?php
                // Aquí podrías cargar productos próximos dinámicamente desde la base de datos
                $comingSoonProducts = [
                    [
                        'name' => 'Colección Otoño 2024',
                        'description' => 'Tonos cálidos y texturas aterciopeladas',
                        'price' => 45000,
                        'launch_date' => '15 Sep 2024',
                        'launch_month' => 'SEPTIEMBRE',
                        'image' => 'assets/images/coming-soon/autumn.jpg'
                    ],
                    [
                        'name' => 'Edición Limitada Navidad',
                        'description' => 'Paleta exclusiva con 20 sombras',
                        'price' => 189000,
                        'launch_date' => '1 Dic 2024',
                        'launch_month' => 'DICIEMBRE',
                        'image' => 'assets/images/coming-soon/christmas.jpg'
                    ],
                    [
                        'name' => 'Línea Cuidado Facial',
                        'description' => 'Rutina completa con ingredientes naturales',
                        'price' => 28000,
                        'launch_date' => '20 Oct 2024',
                        'launch_month' => 'OCTUBRE',
                        'image' => 'assets/images/coming-soon/skincare.jpg'
                    ]
                ];
                
                foreach ($comingSoonProducts as $index => $product):
                    $delay = ($index + 1) * 100;
                ?>
                <div class="bg-white rounded-2xl shadow-lg overflow-hidden" data-aos="fade-up" data-aos-delay="<?php echo $delay; ?>">
                    <div class="relative">
                        <img src="<?php echo $product['image']; ?>" 
                             alt="<?php echo $product['name']; ?>" 
                             class="w-full h-48 object-cover">
                        
                        <div class="absolute inset-0 bg-gradient-to-t from-black/60 to-transparent"></div>
                        
                        <div class="absolute bottom-4 left-4 right-4 text-white">
                            <h3 class="text-xl font-bold mb-2"><?php echo $product['name']; ?></h3>
                            <p class="text-sm opacity-90"><?php echo $product['description']; ?></p>
                        </div>
                        
                        <div class="absolute top-4 right-4">
                            <span class="bg-secondary-500 text-white px-3 py-1 rounded-full text-xs font-semibold"><?php echo $product['launch_month']; ?></span>
                        </div>
                    </div>
                    
                    <div class="p-6">
                        <div class="flex items-center justify-between mb-4">
                            <span class="text-2xl font-bold text-primary-500">Desde $<?php echo number_format($product['price'], 0, ',', '.'); ?></span>
                            <div class="text-right">
                                <p class="text-sm text-gray-500">Lanzamiento</p>
                                <p class="font-semibold text-gray-900"><?php echo $product['launch_date']; ?></p>
                            </div>
                        </div>
                        
                        <button class="w-full bg-gradient-to-r from-primary-500 to-secondary-500 text-white py-3 rounded-full font-semibold hover:shadow-lg transform hover:scale-105 transition-all duration-300">
                            Notificarme
                        </button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Newsletter -->
    <section class="py-16 bg-gradient-to-r from-primary-500 to-secondary-500">
        <div class="container mx-auto px-4">
            <div class="max-w-4xl mx-auto text-center text-white" data-aos="fade-up">
                <h2 class="text-3xl lg:text-4xl font-bold mb-4">
                    Únete a la Comunidad Odisea
                </h2>
                <p class="text-xl mb-8 opacity-90">
                    Recibe ofertas exclusivas, tips de belleza y sé la primera en conocer nuestros nuevos productos
                </p>
                
                <div class="max-w-md mx-auto">
                    <form id="newsletter-form" action="newsletter-subscribe.php" method="post" class="flex flex-col sm:flex-row gap-4">
                        <input type="email" 
                               name="email"
                               id="newsletter-email"
                               placeholder="Tu email aquí..." 
                               class="flex-1 px-6 py-4 rounded-full text-gray-900 focus:outline-none focus:ring-4 focus:ring-white/30"
                               required>
                        <button type="submit" id="newsletter-submit" class="bg-white text-primary-500 px-8 py-4 rounded-full font-semibold hover:bg-gray-100 transition-colors duration-300 disabled:opacity-50 disabled:cursor-not-allowed">
                            <span class="submit-text">Suscribirme</span>
                            <span class="loading-text hidden">Enviando...</span>
                        </button>
                    </form>
                    <p class="text-sm mt-4 opacity-75">
                        Al suscribirte aceptas recibir emails promocionales. Puedes cancelar en cualquier momento.
                    </p>
                </div>
                
                <!-- Benefits -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-8 mt-12">
                    <div class="text-center">
                        <div class="w-16 h-16 bg-white/20 rounded-full flex items-center justify-center mx-auto mb-4">
                            <i class="fas fa-gift text-2xl"></i>
                        </div>
                        <h3 class="font-semibold mb-2">Ofertas Exclusivas</h3>
                        <p class="text-sm opacity-90">Descuentos especiales solo para suscriptoras</p>
                    </div>
                    <div class="text-center">
                        <div class="w-16 h-16 bg-white/20 rounded-full flex items-center justify-center mx-auto mb-4">
                            <i class="fas fa-star text-2xl"></i>
                        </div>
                        <h3 class="font-semibold mb-2">Acceso Anticipado</h3>
                        <p class="text-sm opacity-90">Compra nuevos productos antes que nadie</p>
                    </div>
                    <div class="text-center">
                        <div class="w-16 h-16 bg-white/20 rounded-full flex items-center justify-center mx-auto mb-4">
                            <i class="fas fa-heart text-2xl"></i>
                        </div>
                        <h3 class="font-semibold mb-2">Tips de Belleza</h3>
                        <p class="text-sm opacity-90">Consejos y tutoriales de expertos</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-gray-900 text-white">
        <div class="container mx-auto px-4 py-16">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
                <!-- Company Info -->
                <div class="space-y-6">
                    <div>
                        <h3 class="text-2xl font-bold bg-gradient-to-r from-primary-400 to-secondary-400 bg-clip-text text-transparent mb-4">
                            Odisea
                        </h3>
                        <p class="text-gray-300 leading-relaxed">
                            Tu destino para el maquillaje perfecto. Descubre las mejores marcas y productos de belleza con la calidad que mereces.
                        </p>
                    </div>
                    
                    <div class="space-y-3">
                        <div class="flex items-center space-x-3">
                            <i class="fas fa-map-marker-alt text-primary-400"></i>
                            <span class="text-gray-300">Barranquilla, Colombia</span>
                        </div>
                        <div class="flex items-center space-x-3">
                            <i class="fas fa-phone text-primary-400"></i>
                            <span class="text-gray-300">+57 300 123 4567</span>
                        </div>
                        <div class="flex items-center space-x-3">
                            <i class="fas fa-envelope text-primary-400"></i>
                            <span class="text-gray-300">contacto@odisea.com</span>
                        </div>
                    </div>
                </div>

                <!-- Quick Links -->
                <div>
                    <h4 class="text-lg font-semibold mb-6">Enlaces Rápidos</h4>
                    <ul class="space-y-3">
                        <li><a href="sobre-nosotros.php" class="text-gray-300 hover:text-primary-400 transition-colors duration-300">Sobre Nosotros</a></li>
                        <li><a href="productos.php" class="text-gray-300 hover:text-primary-400 transition-colors duration-300">Catálogo</a></li>
                        <li><a href="ofertas.php" class="text-gray-300 hover:text-primary-400 transition-colors duration-300">Ofertas</a></li>
                        <li><a href="blog.php" class="text-gray-300 hover:text-primary-400 transition-colors duration-300">Blog</a></li>
                        <li><a href="contacto.php" class="text-gray-300 hover:text-primary-400 transition-colors duration-300">Contacto</a></li>
                    </ul>
                </div>

                <!-- Customer Service -->
                <div>
                    <h4 class="text-lg font-semibold mb-6">Atención al Cliente</h4>
                    <ul class="space-y-3">
                        <li><a href="mi-cuenta.php" class="text-gray-300 hover:text-primary-400 transition-colors duration-300">Mi Cuenta</a></li>
                        <li><a href="mis-pedidos.php" class="text-gray-300 hover:text-primary-400 transition-colors duration-300">Mis Pedidos</a></li>
                        <li><a href="envios-devoluciones.php" class="text-gray-300 hover:text-primary-400 transition-colors duration-300">Envíos y Devoluciones</a></li>
                        <li><a href="faq.php" class="text-gray-300 hover:text-primary-400 transition-colors duration-300">Preguntas Frecuentes</a></li>
                        <li><a href="terminos.php" class="text-gray-300 hover:text-primary-400 transition-colors duration-300">Términos y Condiciones</a></li>
                    </ul>
                </div>

                <!-- Social & Payment -->
                <div>
                    <h4 class="text-lg font-semibold mb-6">Síguenos</h4>
                    <div class="flex space-x-4 mb-8">
                        <a href="https://instagram.com/odiseamakeup" target="_blank" class="w-10 h-10 bg-primary-500 rounded-full flex items-center justify-center hover:bg-primary-600 transition-colors duration-300">
                            <i class="fab fa-instagram"></i>
                        </a>
                        <a href="https://facebook.com/odiseamakeup" target="_blank" class="w-10 h-10 bg-primary-500 rounded-full flex items-center justify-center hover:bg-primary-600 transition-colors duration-300">
                            <i class="fab fa-facebook"></i>
                        </a>
                        <a href="https://tiktok.com/@odiseamakeup" target="_blank" class="w-10 h-10 bg-primary-500 rounded-full flex items-center justify-center hover:bg-primary-600 transition-colors duration-300">
                            <i class="fab fa-tiktok"></i>
                        </a>
                        <a href="https://youtube.com/odiseamakeup" target="_blank" class="w-10 h-10 bg-primary-500 rounded-full flex items-center justify-center hover:bg-primary-600 transition-colors duration-300">
                            <i class="fab fa-youtube"></i>
                        </a>
                    </div>
                    
                    <h5 class="font-semibold mb-4">Métodos de Pago</h5>
                    <div class="grid grid-cols-3 gap-2">
                        <div class="bg-white rounded p-2 flex items-center justify-center">
                            <i class="fab fa-cc-visa text-blue-600 text-xl"></i>
                        </div>
                        <div class="bg-white rounded p-2 flex items-center justify-center">
                            <i class="fab fa-cc-mastercard text-red-600 text-xl"></i>
                        </div>
                        <div class="bg-white rounded p-2 flex items-center justify-center">
                            <i class="fab fa-paypal text-blue-500 text-xl"></i>
                        </div>
                        <div class="bg-white rounded p-2 flex items-center justify-center text-xs font-bold text-gray-700">
                            NEQUI
                        </div>
                        <div class="bg-white rounded p-2 flex items-center justify-center text-xs font-bold text-gray-700">
                            DAVI
                        </div>
                        <div class="bg-white rounded p-2 flex items-center justify-center text-xs font-bold text-gray-700">
                            BANCO
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Bottom Footer -->
            <div class="border-t border-gray-800 mt-12 pt-8">
                <div class="flex flex-col md:flex-row justify-between items-center">
                    <p class="text-gray-400 text-sm">
                        © <?php echo date('Y'); ?> Odisea Makeup. Todos los derechos reservados.
                    </p>
                    <div class="flex space-x-6 mt-4 md:mt-0">
                        <a href="privacidad.php" class="text-gray-400 hover:text-primary-400 text-sm transition-colors duration-300">Política de Privacidad</a>
                        <a href="terminos.php" class="text-gray-400 hover:text-primary-400 text-sm transition-colors duration-300">Términos de Uso</a>
                        <a href="cookies.php" class="text-gray-400 hover:text-primary-400 text-sm transition-colors duration-300">Cookies</a>
                    </div>
                </div>
            </div>
        </div>
    </footer>

    <!-- Shopping Cart Sidebar -->
    <div class="fixed inset-0 z-50 overflow-hidden hidden" id="cart-sidebar">
        <div class="absolute inset-0 bg-black bg-opacity-50" id="cart-overlay"></div>
        
        <div class="absolute right-0 top-0 h-full w-full max-w-md bg-white shadow-xl transform translate-x-full transition-transform duration-300" id="cart-panel">
            <div class="flex flex-col h-full">
                <!-- Cart Header -->
                <div class="flex items-center justify-between p-6 border-b border-gray-200">
                    <h2 class="text-xl font-semibold text-gray-900">Carrito de Compras</h2>
                    <button class="text-gray-400 hover:text-gray-600 transition-colors duration-300" id="close-cart">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
                
                <!-- Cart Items -->
                <div class="flex-1 overflow-y-auto p-6" id="cart-items">
                    <?php if (!isset($_SESSION['cart']) || count($_SESSION['cart']) === 0): ?>
                    <!-- Empty Cart -->
                    <div class="text-center py-12" id="empty-cart">
                        <i class="fas fa-shopping-bag text-6xl text-gray-300 mb-4"></i>
                        <h3 class="text-lg font-semibold text-gray-900 mb-2">Tu carrito está vacío</h3>
                        <p class="text-gray-600 mb-6">Agrega algunos productos para comenzar</p>
                        <button class="bg-gradient-to-r from-primary-500 to-secondary-500 text-white px-6 py-3 rounded-full font-semibold hover:shadow-lg transform hover:scale-105 transition-all duration-300" id="continue-shopping">
                            Continuar Comprando
                        </button>
                    </div>
                    <?php else: ?>
                    <!-- Cart Items List -->
                    <div class="space-y-4" id="cart-items-list">
                        <?php 
                        $subtotal = 0;
                        foreach ($_SESSION['cart'] as $item): 
                            $subtotal += $item['price'] * $item['quantity'];
                        ?>
                        <!-- Cart Item -->
                        <div class="flex items-center space-x-4 p-4 bg-gray-50 rounded-lg">
                            <img src="<?php echo $item['image']; ?>" alt="<?php echo $item['name']; ?>" class="w-15 h-15 object-cover rounded">
                            <div class="flex-1">
                                <h4 class="font-semibold text-gray-900"><?php echo $item['name']; ?></h4>
                                <?php if (isset($item['variant'])): ?>
                                <p class="text-sm text-gray-600"><?php echo $item['variant']; ?></p>
                                <?php endif; ?>
                                <div class="flex items-center justify-between mt-2">
                                    <div class="flex items-center space-x-2">
                                        <button class="w-8 h-8 bg-white border border-gray-300 rounded-full flex items-center justify-center hover:bg-gray-100 transition-colors duration-300 cart-decrease" data-id="<?php echo $item['id']; ?>">
                                            <i class="fas fa-minus text-xs"></i>
                                        </button>
                                        <span class="font-semibold"><?php echo $item['quantity']; ?></span>
                                        <button class="w-8 h-8 bg-white border border-gray-300 rounded-full flex items-center justify-center hover:bg-gray-100 transition-colors duration-300 cart-increase" data-id="<?php echo $item['id']; ?>">
                                            <i class="fas fa-plus text-xs"></i>
                                        </button>
                                    </div>
                                    <span class="font-bold text-primary-500"><?php echo number_format($item['price'], 0, ',', '.'); ?></span>
                                </div>
                            </div>
                            <button class="text-gray-400 hover:text-red-500 transition-colors duration-300 cart-remove" data-id="<?php echo $item['id']; ?>">
                                <i class="fas fa-trash text-sm"></i>
                            </button>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
                
                <!-- Cart Footer -->
                <?php if (isset($_SESSION['cart']) && count($_SESSION['cart']) > 0): ?>
                <div class="border-t border-gray-200 p-6 space-y-4" id="cart-footer">
                    <!-- Coupon Code -->
                    <div class="flex space-x-2">
                        <input type="text" placeholder="Código de cupón" class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500">
                        <button class="bg-gray-100 text-gray-700 px-4 py-2 rounded-lg font-semibold hover:bg-gray-200 transition-colors duration-300">
                            Aplicar
                        </button>
                    </div>
                    
                    <!-- Cart Summary -->
                    <div class="space-y-2">
                        <?php
                        $subtotal = isset($subtotal) ? $subtotal : 0;
                        $shipping = 15000; // Ejemplo de costo de envío
                        $tax = round($subtotal * 0.19); // IVA del 19%
                        $total = $subtotal + $shipping + $tax;
                        ?>
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-600">Subtotal:</span>
                            <span class="font-semibold"><?php echo number_format($subtotal, 0, ',', '.'); ?></span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-600">Envío:</span>
                            <span class="font-semibold"><?php echo number_format($shipping, 0, ',', '.'); ?></span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-600">IVA:</span>
                            <span class="font-semibold"><?php echo number_format($tax, 0, ',', '.'); ?></span>
                        </div>
                        <hr class="my-2">
                        <div class="flex justify-between text-lg font-bold">
                            <span>Total:</span>
                            <span class="text-primary-500"><?php echo number_format($total, 0, ',', '.'); ?></span>
                        </div>
                    </div>
                    
                    <!-- Checkout Button -->
                    <button class="w-full bg-gradient-to-r from-primary-500 to-secondary-500 text-white py-4 rounded-full font-semibold hover:shadow-lg transform hover:scale-105 transition-all duration-300">
                        Proceder al Pago
                    </button>
                    
                    <!-- Continue Shopping -->
                    <button class="w-full border-2 border-primary-500 text-primary-500 py-3 rounded-full font-semibold hover:bg-primary-500 hover:text-white transition-all duration-300" id="continue-shopping-footer">
                        Continuar Comprando
                    </button>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- WhatsApp Floating Button -->
    <div class="fixed bottom-6 right-6 z-40">
        <a href="https://wa.me/573001234567?text=Hola,%20me%20interesa%20conocer%20más%20sobre%20sus%20productos" 
           target="_blank" 
           class="w-14 h-14 bg-green-500 rounded-full flex items-center justify-center text-white shadow-lg hover:bg-green-600 hover:shadow-xl transform hover:scale-110 transition-all duration-300 animate-bounce-slow">
            <i class="fab fa-whatsapp text-2xl"></i>
        </a>
    </div>

    <!-- Back to Top Button -->
    <button class="fixed bottom-6 left-6 w-12 h-12 bg-primary-500 text-white rounded-full flex items-center justify-center shadow-lg hover:bg-primary-600 hover:shadow-xl transform hover:scale-110 transition-all duration-300 opacity-0 invisible" id="back-to-top">
        <i class="fas fa-chevron-up"></i>
    </button>

    <!-- Scripts -->
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script src="<?php echo ASSETS_URL; ?>/js/main.js"></script>
    
    <script>
        // Initialize AOS
        AOS.init({
            duration: 800,
            easing: 'ease-in-out',
            once: true,
            offset: 100
        });

        // Cart functionality
        document.addEventListener('DOMContentLoaded', function() {
            const cartToggle = document.getElementById('cart-toggle');
            const cartSidebar = document.getElementById('cart-sidebar');
            const cartOverlay = document.getElementById('cart-overlay');
            const closeCart = document.getElementById('close-cart');
            const cartPanel = document.getElementById('cart-panel');
            const continueShopping = document.getElementById('continue-shopping');
            const continueShoppingFooter = document.getElementById('continue-shopping-footer');
            const backToTop = document.getElementById('back-to-top');
            const mobileMenuToggle = document.getElementById('mobile-menu-toggle');
            const mobileMenu = document.getElementById('mobile-menu');

            // Cart toggle
            cartToggle.addEventListener('click', function() {
                cartSidebar.classList.remove('hidden');
                setTimeout(() => {
                    cartPanel.classList.remove('translate-x-full');
                }, 10);
            });

            // Close cart
            function closeCartSidebar() {
                cartPanel.classList.add('translate-x-full');
                setTimeout(() => {
                    cartSidebar.classList.add('hidden');
                }, 300);
            }

            closeCart.addEventListener('click', closeCartSidebar);
            cartOverlay.addEventListener('click', closeCartSidebar);
            if (continueShopping) continueShopping.addEventListener('click', closeCartSidebar);
            if (continueShoppingFooter) continueShoppingFooter.addEventListener('click', closeCartSidebar);

            // Back to top
            window.addEventListener('scroll', function() {
                if (window.pageYOffset > 300) {
                    backToTop.classList.remove('opacity-0', 'invisible');
                } else {
                    backToTop.classList.add('opacity-0', 'invisible');
                }
            });

            backToTop.addEventListener('click', function() {
                window.scrollTo({
                    top: 0,
                    behavior: 'smooth'
                });
            });

            // Mobile menu
            mobileMenuToggle.addEventListener('click', function() {
                mobileMenu.classList.toggle('hidden');
            });

            // Cart quantity buttons
            document.querySelectorAll('.cart-increase').forEach(button => {
                button.addEventListener('click', function() {
                    const id = this.getAttribute('data-id');
                    updateCartQuantity(id, 1);
                });
            });

            document.querySelectorAll('.cart-decrease').forEach(button => {
                button.addEventListener('click', function() {
                    const id = this.getAttribute('data-id');
                    updateCartQuantity(id, -1);
                });
            });

            document.querySelectorAll('.cart-remove').forEach(button => {
                button.addEventListener('click', function() {
                    const id = this.getAttribute('data-id');
                    removeFromCart(id);
                });
            });

            // Update cart quantity
            function updateCartQuantity(id, change) {
                fetch('cart-update.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `id=${id}&change=${change}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Reload cart contents
                        location.reload();
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                });
            }

            // Remove from cart
            function removeFromCart(id) {
                fetch('cart-remove.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `id=${id}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Reload cart contents
                        location.reload();
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                });
            }

            // Add to cart
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
            
            // Toggle wishlist
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
                        const heartIcon = document.querySelector(`button[onclick*="${productId}"] i`);
                        if (heartIcon) {
                            if (data.action === 'added') {
                                heartIcon.style.color = '#e11d48'; // Color rojo para favorito
                                heartIcon.classList.add('fas');
                                heartIcon.classList.remove('far');
                                showNotification('Agregado a favoritos ❤️', 'success');
                            } else {
                                heartIcon.style.color = ''; // Color original
                                heartIcon.classList.add('far');
                                heartIcon.classList.remove('fas');
                                showNotification('Removido de favoritos', 'info');
                            }
                        }
                    } else {
                        if (data.message && data.message.includes('login')) {
                            showNotification('Debes iniciar sesión para agregar a favoritos', 'warning');
                            // Opcional: redirigir al login
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
            
            // Quick view
            function quickView(productId) {
                console.log('Quick view for product:', productId);
                window.open(`product.php?id=${productId}`, '_blank');
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
            
            // Update cart count
            function updateCartCount() {
                fetch('cart-count.php')
                .then(response => response.json())
                .then(data => {
                    const cartBadge = document.querySelector('.cart-count');
                    if (cartBadge) {
                        cartBadge.textContent = data.count || 0;
                    }
                })
                .catch(error => {
                    console.error('Error updating cart count:', error);
                });
            }

            // New arrivals slider
            const slider = document.getElementById('new-arrivals-slider');
            const prevBtn = document.getElementById('prev-new');
            const nextBtn = document.getElementById('next-new');

            if (slider && prevBtn && nextBtn) {
                prevBtn.addEventListener('click', () => {
                    slider.scrollBy({ left: -300, behavior: 'smooth' });
                });

                nextBtn.addEventListener('click', () => {
                    slider.scrollBy({ left: 300, behavior: 'smooth' });
                });
            }
        });
    </script>

    <!-- Circular Carousel JavaScript -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const carousel = document.getElementById('category-carousel');
            const categoryItems = document.querySelectorAll('.category-item');
            
            if (!carousel || categoryItems.length === 0) return;
            
            let currentRotation = 0;
            let isVisible = new Array(categoryItems.length).fill(true);
            
            // Function to update category positions
            function updateCategoryPositions() {
                const totalCategories = categoryItems.length;
                const radius = window.innerWidth < 768 ? 120 : 150;
                
                categoryItems.forEach((item, index) => {
                    const angle = (index / totalCategories) * 360 + currentRotation;
                    const x = radius * Math.cos((angle * Math.PI) / 180);
                    const y = radius * Math.sin((angle * Math.PI) / 180);
                    
                    // Check if item is in visible area (right half of circle)
                    const isInVisibleArea = Math.cos((angle * Math.PI) / 180) > -0.3;
                    
                    if (isInVisibleArea && !isVisible[index]) {
                        // Item entering view - fade in from left
                        item.style.opacity = '0';
                        item.style.transform = `translate(calc(50vw + ${x}px), calc(50% + ${y}px)) translateX(-50px) scale(0.8)`;
                        
                        setTimeout(() => {
                            item.style.transition = 'all 0.8s ease-out';
                            item.style.opacity = '1';
                            item.style.transform = `translate(calc(50vw + ${x}px), calc(50% + ${y}px)) translateX(0) scale(1)`;
                        }, 50);
                        
                        isVisible[index] = true;
                    } else if (!isInVisibleArea && isVisible[index]) {
                        // Item leaving view - fade out to right
                        item.style.transition = 'all 0.8s ease-out';
                        item.style.opacity = '0';
                        item.style.transform = `translate(calc(50vw + ${x}px), calc(50% + ${y}px)) translateX(50px) scale(0.8)`;
                        isVisible[index] = false;
                    } else if (isInVisibleArea) {
                        // Item in view - normal position
                        item.style.transition = 'all 0.3s ease';
                        item.style.opacity = '1';
                        item.style.transform = `translate(calc(50vw + ${x}px), calc(50% + ${y}px)) translateX(0) scale(1)`;
                    }
                });
            }
            
            // Rotation animation
            function animateCarousel() {
                currentRotation += 0.2; // Slow rotation speed
                if (currentRotation >= 360) currentRotation = 0;
                
                updateCategoryPositions();
                requestAnimationFrame(animateCarousel);
            }
            
            // Initialize positions
            updateCategoryPositions();
            
            // Start animation
            setTimeout(() => {
                animateCarousel();
            }, 1000);
            
            // Handle window resize
            window.addEventListener('resize', updateCategoryPositions);
            
            // Add hover effects
            categoryItems.forEach(item => {
                item.addEventListener('mouseenter', function() {
                    this.style.zIndex = '20';
                    this.style.transform += ' scale(1.15)';
                });
                
                item.addEventListener('mouseleave', function() {
                    this.style.zIndex = '10';
                    this.style.transform = this.style.transform.replace(' scale(1.15)', '');
                });
            });
            
            // Random category shuffling effect every 10 seconds
            setInterval(() => {
                const visibleItems = categoryItems.filter((_, index) => isVisible[index]);
                
                visibleItems.forEach((item, index) => {
                    setTimeout(() => {
                        item.style.transition = 'all 0.5s ease-out';
                        item.style.transform += ' rotate(360deg)';
                        
                        setTimeout(() => {
                            item.style.transform = item.style.transform.replace(' rotate(360deg)', '');
                        }, 500);
                    }, index * 100);
                });
            }, 10000);
        });
    </script>

    <!-- Modal de Notificación para Newsletter -->
    <div id="newsletter-modal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
        <div class="bg-white rounded-lg p-8 max-w-md mx-4 transform transition-all duration-300 scale-95 opacity-0" id="newsletter-modal-content">
            <div class="text-center">
                <div id="newsletter-icon" class="w-16 h-16 mx-auto mb-4 flex items-center justify-center rounded-full">
                    <!-- Ícono se agregará dinámicamente -->
                </div>
                <h3 id="newsletter-title" class="text-xl font-bold text-gray-900 mb-2"></h3>
                <p id="newsletter-message" class="text-gray-600 mb-6"></p>
                <button onclick="closeNewsletterModal()" class="bg-primary-500 hover:bg-primary-600 text-white px-6 py-2 rounded-full font-semibold transition-colors duration-300">
                    ¡Entendido!
                </button>
            </div>
        </div>
    </div>

</body>
</html>
