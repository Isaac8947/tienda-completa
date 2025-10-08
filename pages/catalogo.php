<?php
session_start();
require_once 'config/database.php';
require_once 'config/global-settings.php';
require_once 'models/Product.php';
require_once 'models/Category.php';
require_once 'models/Brand.php';
require_once 'includes/CSRFProtection.php';

$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 12;
$offset = ($page - 1) * $perPage;

// Obtener filtros
$searchQuery = $_GET['q'] ?? '';
$filters = [
    'category' => $_GET['categoria'] ?? '',
    'brand' => $_GET['marca'] ?? '',
    'min_price' => $_GET['precio_min'] ?? '',
    'max_price' => $_GET['precio_max'] ?? '',
    'sort' => $_GET['orden'] ?? 'newest',
    'new_only' => $_GET['nuevos'] ?? '',
    'featured_only' => $_GET['destacados'] ?? '',
    'search' => $searchQuery,
    'offers_only' => $_GET['offers'] ?? ''
];

try {
    $productModel = new Product();
    $brandModel = new Brand();
    $categoryModel = new Category();
    
    // Obtener productos con filtros
    $products = $productModel->getProductsWithFilters($filters, $perPage, $offset);
    
    // Obtener total de productos para paginación
    $totalProducts = $productModel->countProductsWithFilters($filters);
    $totalPages = ceil($totalProducts / $perPage);
    
    // Obtener marcas y categorías para filtros
    $brands = $brandModel->getAll(['is_active' => 1]);
    $categories = $categoryModel->getAll(['is_active' => 1]);
    
    // Estadísticas para mostrar
    $stats = [
        'total_products' => $productModel->countProductsWithFilters([]),
        'total_brands' => count($brands),
        'total_categories' => count($categories)
    ];
    
} catch (Exception $e) {
    error_log("Error loading catalog page: " . $e->getMessage());
    $products = [];
    $brands = [];
    $categories = [];
    $totalProducts = 0;
    $totalPages = 0;
    $stats = ['total_products' => 0, 'total_brands' => 0, 'total_categories' => 0];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Catálogo Completo - ElectroShop</title>
    <meta name="description" content="Descubre nuestra selección de productos electrónicos, gadgets y tecnología. Encuentra lo último en innovación y calidad para tu hogar y oficina.">
    
    <?php
    // Generar CSRF token para el carrito
    $csrf = new CSRFProtection();
    $cartCsrfToken = $csrf->generateToken('cart');
    ?>
    <meta name="cart-csrf-token" content="<?php echo htmlspecialchars($cartCsrfToken); ?>">
    
    <!-- Performance optimizations -->
    <link rel="dns-prefetch" href="//fonts.googleapis.com">
    <link rel="dns-prefetch" href="//cdnjs.cloudflare.com">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Fonts -->
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
                        'bounce-in': 'bounceIn 0.6s ease-out',
                        'pulse-soft': 'pulseSoft 2s ease-in-out infinite'
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
                        bounceIn: {
                            '0%': { opacity: '0', transform: 'scale(0.3)' },
                            '50%': { opacity: '1', transform: 'scale(1.05)' },
                            '70%': { transform: 'scale(0.9)' },
                            '100%': { opacity: '1', transform: 'scale(1)' }
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
        
        .mobile-shadow {
            box-shadow: 0 4px 15px rgba(176, 141, 128, 0.15);
        }
        
        .hover-lift {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .hover-lift:hover {
            transform: translateY(-8px);
            box-shadow: 0 35px 60px -12px rgba(176, 141, 128, 0.35);
        }
        
        .mobile-hover:active {
            transform: scale(0.98);
            transition: transform 0.1s ease;
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
        
        .bg-mesh-mobile {
            background-image: 
                radial-gradient(at 20% 10%, rgba(176, 141, 128, 0.08) 0px, transparent 40%),
                radial-gradient(at 90% 20%, rgba(196, 165, 117, 0.08) 0px, transparent 40%);
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
        
        /* Mobile optimizations */
        @media (max-width: 768px) {
            .mobile-scroll {
                -webkit-overflow-scrolling: touch;
                scroll-behavior: smooth;
            }
                /* Botón flotante de filtros en mobile */
                @media (max-width: 768px) {
                    .floating-filter-btn {
                        position: fixed;
                        bottom: 24px;
                        left: 24px;
                        z-index: 50;
                        box-shadow: 0 8px 32px 0 rgba(176,141,128,0.18);
                        background: linear-gradient(90deg, #b08d80 0%, #e7c9a9 100%);
                        color: #fff;
                        border-radius: 1.5rem;
                        padding: 0.75rem 1.5rem;
                        font-weight: 600;
                        font-size: 1.1rem;
                        display: flex;
                        align-items: center;
                        gap: 0.5rem;
                        transition: box-shadow 0.2s, transform 0.2s;
                        animation: floatUp 0.7s cubic-bezier(.4,2,.3,1) 0s 1;
                    }
                    .floating-filter-btn:active {
                        box-shadow: 0 4px 16px 0 rgba(176,141,128,0.28);
                        transform: scale(0.97);
                    }
                    @keyframes floatUp {
                        from { opacity: 0; transform: translateY(40px); }
                        to { opacity: 1; transform: translateY(0); }
                    }
                }
            
            .mobile-tap {
                -webkit-tap-highlight-color: rgba(176, 141, 128, 0.2);
            }
            
            .mobile-filters-content {
                max-height: calc(85vh - 140px); /* 85vh menos header y botones */
                overflow-y: auto;
                -webkit-overflow-scrolling: touch;
            }
            
            .mobile-filters-content::-webkit-scrollbar {
                width: 4px;
            }
            
            .mobile-filters-content::-webkit-scrollbar-track {
                background: #f1f5f9;
                border-radius: 2px;
            }
            
            .mobile-filters-content::-webkit-scrollbar-thumb {
                background: #cbd5e1;
                border-radius: 2px;
            }
        }
        
        /* Custom scrollbar for mobile */
        .custom-scrollbar::-webkit-scrollbar {
            height: 4px;
        }
        
        .custom-scrollbar::-webkit-scrollbar-track {
            background: rgba(176, 141, 128, 0.1);
            border-radius: 10px;
        }
        
        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: rgba(176, 141, 128, 0.3);
            border-radius: 10px;
        }
        
        .custom-scrollbar::-webkit-scrollbar-thumb:hover {
            background: rgba(176, 141, 128, 0.5);
        }
    </style>
</head>

<body class="font-sans bg-white overflow-x-hidden">
    <!-- Mobile Header -->
    <header class="md:hidden fixed top-0 left-0 right-0 z-50 bg-white/95 backdrop-blur-md border-b border-gray-200">
        <div class="px-4 py-3">
            <div class="flex items-center justify-between">
                <!-- Menu Button -->
                <button onclick="toggleMobileMenu()" class="w-10 h-10 flex items-center justify-center rounded-xl bg-primary-50 text-primary-600 mobile-tap">
                    <i class="fas fa-bars text-lg"></i>
                </button>
                
                <!-- Logo -->
                <div class="flex-1 text-center">
                    <h1 class="text-xl font-serif font-bold gradient-text">Odisea</h1>
                </div>
                
                <!-- Actions -->
                <div class="flex items-center space-x-2">
                    <button onclick="toggleMobileSearch()" class="w-10 h-10 flex items-center justify-center rounded-xl bg-gray-50 text-gray-600 mobile-tap">
                        <i class="fas fa-search"></i>
                    </button>
                    <button onclick="openCartSidebar()" class="w-10 h-10 flex items-center justify-center rounded-xl bg-gray-50 text-gray-600 mobile-tap relative">
                        <i class="fas fa-shopping-bag"></i>
                        <span id="mobile-cart-count" class="absolute -top-1 -right-1 bg-primary-500 text-white text-xs rounded-full w-5 h-5 flex items-center justify-center font-semibold">0</span>
                    </button>
                </div>
            </div>
            
            <!-- Mobile Search Bar (Hidden by default) -->
            <div id="mobile-search-container" class="hidden mt-3 animate-fade-in-up relative">
                <div class="relative">
                    <input type="text" 
                           id="mobile-search-input"
                           placeholder="Buscar productos..." 
                           class="w-full pl-10 pr-4 py-3 bg-gray-50 rounded-2xl border-0 focus:ring-2 focus:ring-primary-500 focus:bg-white transition-all duration-300"
                           autocomplete="off">
                    <i class="fas fa-search absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                    <button class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-gray-600" onclick="toggleMobileSearch()">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <!-- Search Results Dropdown -->
                <div id="mobile-search-results" class="hidden absolute top-full left-0 right-0 bg-white rounded-2xl shadow-xl border border-gray-200 mt-2 max-h-96 overflow-auto z-50">
                    <div id="mobile-search-results-content" class="p-4">
                        <!-- Los resultados se cargarán aquí -->
                    </div>
                </div>
            </div>
        </div>
    </header>
    
    <!-- Mobile Menu Drawer -->
    <div id="mobile-menu" class="md:hidden fixed inset-0 z-50 hidden">
        <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" onclick="toggleMobileMenu()"></div>
        <div id="mobile-menu-panel" class="absolute left-0 top-0 bottom-0 w-80 bg-white transform -translate-x-full transition-transform duration-300 ease-out">
            <div class="p-6">
                <!-- Header -->
                <div class="flex items-center justify-between mb-8">
                    <h2 class="text-2xl font-serif font-bold gradient-text">Menú</h2>
                    <button onclick="toggleMobileMenu()" class="w-8 h-8 flex items-center justify-center rounded-lg text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                
                <!-- Navigation -->
                <nav class="space-y-2">
                    <a href="index.php" class="flex items-center px-4 py-3 rounded-xl text-gray-700 hover:bg-primary-50 hover:text-primary-600 transition-colors mobile-tap">
                        <i class="fas fa-home w-5 mr-3"></i>
                        Inicio
                    </a>
                    <a href="catalogo.php" class="flex items-center px-4 py-3 rounded-xl bg-primary-50 text-primary-600 font-medium">
                        <i class="fas fa-th-large w-5 mr-3"></i>
                        Catálogo
                    </a>
                    <a href="ofertas.php" class="flex items-center px-4 py-3 rounded-xl text-gray-700 hover:bg-primary-50 hover:text-primary-600 transition-colors mobile-tap">
                        <i class="fas fa-tags w-5 mr-3"></i>
                        Ofertas
                    </a>
                    <a href="marcas.php" class="flex items-center px-4 py-3 rounded-xl text-gray-700 hover:bg-primary-50 hover:text-primary-600 transition-colors mobile-tap">
                        <i class="fas fa-star w-5 mr-3"></i>
                        Marcas
                    </a>
                </nav>
                
                <!-- Categories -->
                <div class="mt-8">
                    <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-4">Categorías</h3>
                    <div class="space-y-1">
                        <?php foreach (array_slice($categories, 0, 5) as $category): ?>
                        <a href="categoria.php?categoria=<?php echo $category['slug']; ?>" 
                           class="block px-4 py-2 text-gray-600 hover:text-primary-600 hover:bg-primary-50 rounded-lg transition-colors mobile-tap">
                            <?php echo htmlspecialchars($category['name']); ?>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <!-- User Actions -->
                <div class="mt-8 pt-6 border-t border-gray-200">
                    <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="account.php" class="flex items-center px-4 py-3 rounded-xl text-gray-700 hover:bg-primary-50 hover:text-primary-600 transition-colors mobile-tap">
                        <i class="fas fa-user w-5 mr-3"></i>
                        Mi Cuenta
                    </a>
                    <a href="wishlist.php" class="flex items-center px-4 py-3 rounded-xl text-gray-700 hover:bg-primary-50 hover:text-primary-600 transition-colors mobile-tap">
                        <i class="fas fa-heart w-5 mr-3"></i>
                        Favoritos
                    </a>
                    <?php else: ?>
                    <a href="login.php" class="flex items-center px-4 py-3 rounded-xl text-gray-700 hover:bg-primary-50 hover:text-primary-600 transition-colors mobile-tap">
                        <i class="fas fa-sign-in-alt w-5 mr-3"></i>
                        Iniciar Sesión
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Desktop Header -->
    <div class="hidden md:block">
        <?php include 'includes/global-header.php'; ?>
        
    </div>
    
    <!-- Mobile Spacer -->
    <div class="md:hidden h-20"></div>
    
    <!-- Breadcrumb -->
    <section class="py-4 md:py-6 bg-gradient-to-r from-luxury-rose/30 to-luxury-gold/30 backdrop-blur-sm">
        <div class="container mx-auto px-4">
            <nav class="text-sm" data-aos="fade-right">
                <ol class="flex items-center space-x-2 md:space-x-3">
                    <li>
                        <a href="index.php" class="text-gray-600 hover:text-primary-500 transition-colors duration-300 flex items-center">
                            <i class="fas fa-home mr-1 md:mr-2"></i>
                            <span class="hidden sm:inline">Inicio</span>
                        </a>
                    </li>
                    <li class="text-gray-400">
                        <i class="fas fa-chevron-right text-xs"></i>
                    </li>
                    <li class="text-primary-600 font-medium">Catálogo</li>
                </ol>
            </nav>
        </div>
    </section>
    
    <!-- Mobile Hero -->
    <section class="md:hidden py-12 relative overflow-hidden bg-mesh-mobile">
        <div class="absolute inset-0">
            <div class="absolute top-5 right-5 w-32 h-32 bg-gradient-to-r from-primary-200/20 to-secondary-200/20 rounded-full blur-2xl animate-float"></div>
            <div class="absolute bottom-5 left-5 w-40 h-40 bg-gradient-to-r from-secondary-200/20 to-accent-200/20 rounded-full blur-2xl animate-float" style="animation-delay: 1s;"></div>
        </div>
        
        <div class="container mx-auto px-4 relative z-10">
            <div class="text-center" data-aos="fade-up">
                <div class="inline-block mb-4">
                    <span class="text-xs font-medium tracking-widest uppercase text-primary-600 bg-primary-50 px-4 py-2 rounded-full">
                        Colección Completa
                    </span>
                </div>
                <h1 class="text-3xl font-serif font-bold mb-4 gradient-text text-shadow">
                    Electrónica para Todos
                </h1>
                <p class="text-gray-600 mb-6 font-light leading-relaxed px-4">
                    Explora productos electrónicos, gadgets, computadoras, audio, hogar inteligente y mucho más.
                </p>
                
                <!-- Mobile Stats -->
                <div class="grid grid-cols-3 gap-4" data-aos="fade-up" data-aos-delay="200">
                    <div class="bg-white/80 backdrop-blur-sm rounded-2xl mobile-shadow p-4 border border-white/50">
                        <div class="text-2xl font-bold gradient-text mb-1"><?php echo $stats['total_products']; ?></div>
                        <div class="text-xs text-gray-600 font-medium">Productos</div>
                    </div>
                    <div class="bg-white/80 backdrop-blur-sm rounded-2xl mobile-shadow p-4 border border-white/50">
                        <div class="text-2xl font-bold gradient-text mb-1"><?php echo $stats['total_brands']; ?></div>
                        <div class="text-xs text-gray-600 font-medium">Marcas</div>
                    </div>
                    <div class="bg-white/80 backdrop-blur-sm rounded-2xl mobile-shadow p-4 border border-white/50">
                        <div class="text-2xl font-bold gradient-text mb-1"><?php echo $stats['total_categories']; ?></div>
                        <div class="text-xs text-gray-600 font-medium">Categorías</div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Desktop Hero -->
    <section class="hidden md:block py-20 relative overflow-hidden bg-mesh">
        <!-- Background Elements -->
        <div class="absolute inset-0">
            <div class="absolute top-10 left-10 w-64 h-64 bg-gradient-to-r from-primary-200/30 to-secondary-200/30 rounded-full blur-3xl animate-float"></div>
            <div class="absolute bottom-10 right-10 w-80 h-80 bg-gradient-to-r from-secondary-200/30 to-accent-200/30 rounded-full blur-3xl animate-float" style="animation-delay: 2s;"></div>
        </div>
        
        <div class="container mx-auto px-4 relative z-10">
            <div class="text-center mb-16" data-aos="fade-up">
                <div class="inline-block mb-6">
                    <span class="text-sm font-medium tracking-widest uppercase text-primary-600 bg-primary-50 px-6 py-2 rounded-full">
                        Colección Completa
                    </span>
                </div>
                <h1 class="text-4xl lg:text-6xl font-serif font-bold mb-6 gradient-text text-shadow">
                    Todo en Electrónica y Tecnología
                </h1>
                <p class="text-xl text-gray-600 max-w-3xl mx-auto mb-8 font-light leading-relaxed">
                    Descubre nuestra colección de productos electrónicos, desde lo más nuevo en smartphones y computadoras hasta accesorios, audio, gaming y soluciones para el hogar inteligente.
                </p>
                
                <!-- Desktop Stats -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-8 max-w-4xl mx-auto" data-aos="fade-up" data-aos-delay="200">
                    <div class="bg-white/80 backdrop-blur-sm rounded-3xl shadow-2xl p-8 hover-lift border border-white/50">
                        <div class="text-4xl font-bold gradient-text mb-2"><?php echo $stats['total_products']; ?></div>
                        <div class="text-gray-600 font-medium">Productos Únicos</div>
                        <div class="w-12 h-1 bg-gradient-to-r from-primary-500 to-secondary-500 rounded-full mx-auto mt-3"></div>
                    </div>
                    <div class="bg-white/80 backdrop-blur-sm rounded-3xl shadow-2xl p-8 hover-lift border border-white/50">
                        <div class="text-4xl font-bold gradient-text mb-2"><?php echo $stats['total_brands']; ?></div>
                        <div class="text-gray-600 font-medium">Marcas Premium</div>
                        <div class="w-12 h-1 bg-gradient-to-r from-secondary-500 to-accent-500 rounded-full mx-auto mt-3"></div>
                    </div>
                    <div class="bg-white/80 backdrop-blur-sm rounded-3xl shadow-2xl p-8 hover-lift border border-white/50">
                        <div class="text-4xl font-bold gradient-text mb-2"><?php echo $stats['total_categories']; ?></div>
                        <div class="text-gray-600 font-medium">Categorías</div>
                        <div class="w-12 h-1 bg-gradient-to-r from-accent-500 to-primary-500 rounded-full mx-auto mt-3"></div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Mobile Filters Button -->
    <!-- Botón flotante de filtros en mobile -->
    <div class="md:hidden">
        <button onclick="toggleMobileFilters()" class="floating-filter-btn shadow-lg">
            <i class="fas fa-filter"></i>
            Filtros
            <span id="active-filters-count" class="ml-2 bg-white/20 px-2 py-1 rounded-full text-xs hidden">0</span>
        </button>
    </div>
    
    <!-- Mobile Filters Modal -->
    <div id="mobile-filters" class="md:hidden fixed inset-0 z-50 hidden">
        <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" onclick="toggleMobileFilters()"></div>
        <div id="mobile-filters-panel" class="absolute bottom-0 left-0 right-0 bg-white rounded-t-3xl transform translate-y-full transition-transform duration-300 ease-out">
            <div class="flex flex-col h-[85vh] max-h-[600px]">
                <!-- Header - Fixed -->
                <div class="flex items-center justify-between p-6 border-b border-gray-200 flex-shrink-0">
                    <h3 class="text-xl font-serif font-bold text-gray-900">Filtros</h3>
                    <button onclick="toggleMobileFilters()" class="w-8 h-8 flex items-center justify-center rounded-lg text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                
                <!-- Content - Scrollable -->
                <div class="flex-1 overflow-y-auto p-6 mobile-filters-content">
                    <form method="GET" action="catalogo.php" id="mobile-filters-form" class="space-y-6">
                        <!-- Search Filter -->
                        <div>
                            <h4 class="font-semibold text-gray-900 mb-3 flex items-center">
                                <i class="fas fa-search text-primary-500 mr-2"></i>
                                Buscar
                            </h4>
                            <input type="text" name="q" value="<?php echo htmlspecialchars($searchQuery); ?>" 
                                   placeholder="Buscar productos..." 
                                   class="w-full p-3 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 bg-white">
                        </div>
                        
                        <!-- Category Filter -->
                        <div>
                            <h4 class="font-semibold text-gray-900 mb-3 flex items-center">
                                <i class="fas fa-layer-group text-primary-500 mr-2"></i>
                                Categoría
                            </h4>
                            <select name="categoria" class="w-full p-3 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 bg-white">
                                <option value="">Todas las categorías</option>
                                <?php foreach ($categories as $category): ?>
                                <option value="<?php echo $category['id']; ?>" <?php echo $filters['category'] == $category['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($category['name']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <!-- Brand Filter -->
                        <div>
                            <h4 class="font-semibold text-gray-900 mb-3 flex items-center">
                                <i class="fas fa-tags text-primary-500 mr-2"></i>
                                Marca
                            </h4>
                            <select name="marca" class="w-full p-3 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 bg-white">
                                <option value="">Todas las marcas</option>
                                <?php foreach ($brands as $brand): ?>
                                <option value="<?php echo $brand['id']; ?>" <?php echo $filters['brand'] == $brand['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($brand['name']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <!-- Price Filter -->
                        <div>
                            <h4 class="font-semibold text-gray-900 mb-3 flex items-center">
                                <i class="fas fa-dollar-sign text-primary-500 mr-2"></i>
                                Rango de Precio
                            </h4>
                            <div class="grid grid-cols-2 gap-3">
                                <input type="number" name="precio_min" placeholder="Mínimo"
                                       value="<?php echo htmlspecialchars($filters['min_price']); ?>"
                                       class="p-3 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 bg-white">
                                <input type="number" name="precio_max" placeholder="Máximo"
                                       value="<?php echo htmlspecialchars($filters['max_price']); ?>"
                                       class="p-3 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 bg-white">
                            </div>
                        </div>
                        
                        <!-- Special Filters -->
                        <div>
                            <h4 class="font-semibold text-gray-900 mb-3 flex items-center">
                                <i class="fas fa-star text-primary-500 mr-2"></i>
                                Especiales
                            </h4>
                            <div class="space-y-3">
                                <label class="flex items-center p-3 rounded-xl hover:bg-primary-50 transition-colors cursor-pointer mobile-tap">
                                    <input type="checkbox" name="nuevos" value="1" <?php echo $filters['new_only'] ? 'checked' : ''; ?>
                                           class="rounded border-gray-300 text-primary-500 focus:ring-primary-500 w-5 h-5">
                                    <span class="ml-3 text-gray-700 font-medium">Solo productos nuevos</span>
                                </label>
                                <label class="flex items-center p-3 rounded-xl hover:bg-primary-50 transition-colors cursor-pointer mobile-tap">
                                    <input type="checkbox" name="destacados" value="1" <?php echo $filters['featured_only'] ? 'checked' : ''; ?>
                                           class="rounded border-gray-300 text-primary-500 focus:ring-primary-500 w-5 h-5">
                                    <span class="ml-3 text-gray-700 font-medium">Solo productos destacados</span>
                                </label>
                            </div>
                        </div>
                        
                        <input type="hidden" name="orden" value="<?php echo htmlspecialchars($filters['sort']); ?>">
                    </form>
                </div>
                
                <!-- Action Buttons - Fixed -->
                <div class="flex space-x-3 p-6 border-t border-gray-200 flex-shrink-0 bg-white">
                    <button onclick="clearMobileFilters()" class="flex-1 border border-gray-300 text-gray-700 py-3 rounded-xl font-semibold hover:bg-gray-50 transition-colors mobile-tap">
                        Limpiar
                    </button>
                    <button onclick="applyMobileFilters()" class="flex-1 bg-gradient-to-r from-primary-500 to-secondary-500 text-white py-3 rounded-xl font-semibold mobile-tap">
                        Aplicar Filtros
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Main Content -->
    <section class="py-8 md:py-16">
        <div class="container mx-auto px-4">
            <div class="flex flex-col lg:flex-row gap-8 lg:gap-12">
                <!-- Desktop Sidebar Filters -->
                <div class="hidden lg:block lg:w-1/4">
                    <div class="bg-white/90 backdrop-blur-sm rounded-3xl luxury-shadow p-8 sticky top-6 border border-white/50" data-aos="fade-right">
                        <h3 class="text-2xl font-serif font-bold text-gray-900 mb-8 flex items-center">
                            <div class="w-10 h-10 bg-gradient-to-r from-primary-500 to-secondary-500 rounded-xl flex items-center justify-center mr-3">
                                <i class="fas fa-filter text-white"></i>
                            </div>
                            Filtros
                        </h3>
                        
                        <form method="GET" action="catalogo.php" id="filters-form" class="space-y-8">
                            <!-- Search Filter -->
                            <div>
                                <h4 class="font-semibold text-gray-900 mb-4 flex items-center text-lg">
                                    <div class="w-8 h-8 bg-gradient-to-r from-blue-400 to-blue-500 rounded-lg flex items-center justify-center mr-3">
                                        <i class="fas fa-search text-white text-sm"></i>
                                    </div>
                                    Buscar
                                </h4>
                                <input type="text" name="q" id="search-input" value="<?php echo htmlspecialchars($searchQuery); ?>" 
                                       placeholder="Buscar productos..." 
                                       class="w-full p-4 border border-gray-200 rounded-2xl focus:outline-none focus:ring-2 focus:ring-primary-500/50 focus:border-primary-500 transition-all duration-300 bg-white/80 backdrop-blur-sm">
                            </div>
                            
                            <!-- Category Filter -->
                            <div>
                                <h4 class="font-semibold text-gray-900 mb-4 flex items-center text-lg">
                                    <div class="w-8 h-8 bg-gradient-to-r from-secondary-400 to-secondary-500 rounded-lg flex items-center justify-center mr-3">
                                        <i class="fas fa-layer-group text-white text-sm"></i>
                                    </div>
                                    Categoría
                                </h4>
                                <select name="categoria" class="w-full p-4 border border-gray-200 rounded-2xl focus:outline-none focus:ring-2 focus:ring-primary-500/50 focus:border-primary-500 transition-all duration-300 bg-white/80 backdrop-blur-sm">
                                    <option value="">Todas las categorías</option>
                                    <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo $category['id']; ?>" <?php echo $filters['category'] == $category['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($category['name']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <!-- Brand Filter -->
                            <div>
                                <h4 class="font-semibold text-gray-900 mb-4 flex items-center text-lg">
                                    <div class="w-8 h-8 bg-gradient-to-r from-accent-400 to-accent-500 rounded-lg flex items-center justify-center mr-3">
                                        <i class="fas fa-tags text-white text-sm"></i>
                                    </div>
                                    Marca
                                </h4>
                                <select name="marca" class="w-full p-4 border border-gray-200 rounded-2xl focus:outline-none focus:ring-2 focus:ring-primary-500/50 focus:border-primary-500 transition-all duration-300 bg-white/80 backdrop-blur-sm">
                                    <option value="">Todas las marcas</option>
                                    <?php foreach ($brands as $brand): ?>
                                    <option value="<?php echo $brand['id']; ?>" <?php echo $filters['brand'] == $brand['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($brand['name']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <!-- Price Filter -->
                            <div>
                                <h4 class="font-semibold text-gray-900 mb-4 flex items-center text-lg">
                                    <div class="w-8 h-8 bg-gradient-to-r from-green-400 to-green-500 rounded-lg flex items-center justify-center mr-3">
                                        <i class="fas fa-dollar-sign text-white text-sm"></i>
                                    </div>
                                    Rango de Precio
                                </h4>
                                <div class="grid grid-cols-2 gap-4">
                                    <input type="number" name="precio_min" placeholder="Mínimo"
                                           value="<?php echo htmlspecialchars($filters['min_price']); ?>"
                                           class="p-4 border border-gray-200 rounded-2xl focus:outline-none focus:ring-2 focus:ring-primary-500/50 focus:border-primary-500 transition-all duration-300 bg-white/80 backdrop-blur-sm">
                                    <input type="number" name="precio_max" placeholder="Máximo"
                                           value="<?php echo htmlspecialchars($filters['max_price']); ?>"
                                           class="p-4 border border-gray-200 rounded-2xl focus:outline-none focus:ring-2 focus:ring-primary-500/50 focus:border-primary-500 transition-all duration-300 bg-white/80 backdrop-blur-sm">
                                </div>
                            </div>
                            
                            <!-- Special Filters -->
                            <div>
                                <h4 class="font-semibold text-gray-900 mb-4 flex items-center text-lg">
                                    <div class="w-8 h-8 bg-gradient-to-r from-yellow-400 to-yellow-500 rounded-lg flex items-center justify-center mr-3">
                                        <i class="fas fa-star text-white text-sm"></i>
                                    </div>
                                    Especiales
                                </h4>
                                <div class="space-y-4">
                                    <label class="flex items-center p-3 rounded-xl hover:bg-primary-50 transition-colors cursor-pointer">
                                        <input type="checkbox" name="nuevos" value="1" <?php echo $filters['new_only'] ? 'checked' : ''; ?>
                                               class="rounded-lg border-gray-300 text-primary-500 focus:ring-primary-500 w-5 h-5">
                                        <span class="ml-3 text-gray-700 font-medium">Solo productos nuevos</span>
                                    </label>
                                    <label class="flex items-center p-3 rounded-xl hover:bg-primary-50 transition-colors cursor-pointer">
                                        <input type="checkbox" name="destacados" value="1" <?php echo $filters['featured_only'] ? 'checked' : ''; ?>
                                               class="rounded-lg border-gray-300 text-primary-500 focus:ring-primary-500 w-5 h-5">
                                        <span class="ml-3 text-gray-700 font-medium">Solo productos destacados</span>
                                    </label>
                                </div>
                            </div>
                            
                            <!-- Sort Filter -->
                            <div>
                                <h4 class="font-semibold text-gray-900 mb-4 flex items-center text-lg">
                                    <div class="w-8 h-8 bg-gradient-to-r from-purple-400 to-purple-500 rounded-lg flex items-center justify-center mr-3">
                                        <i class="fas fa-sort text-white text-sm"></i>
                                    </div>
                                    Ordenar por
                                </h4>
                                <select name="orden" class="w-full p-4 border border-gray-200 rounded-2xl focus:outline-none focus:ring-2 focus:ring-primary-500/50 focus:border-primary-500 transition-all duration-300 bg-white/80 backdrop-blur-sm">
                                    <option value="newest" <?php echo $filters['sort'] == 'newest' ? 'selected' : ''; ?>>Más nuevos</option>
                                    <option value="price_low" <?php echo $filters['sort'] == 'price_low' ? 'selected' : ''; ?>>Precio: Menor a mayor</option>
                                    <option value="price_high" <?php echo $filters['sort'] == 'price_high' ? 'selected' : ''; ?>>Precio: Mayor a menor</option>
                                    <option value="name" <?php echo $filters['sort'] == 'name' ? 'selected' : ''; ?>>Nombre A-Z</option>
                                    <option value="featured" <?php echo $filters['sort'] == 'featured' ? 'selected' : ''; ?>>Destacados primero</option>
                                </select>
                            </div>
                            
                            <div class="space-y-4">
                                <button type="submit" class="w-full bg-gradient-to-r from-primary-500 to-secondary-500 text-white py-4 rounded-2xl font-semibold hover:shadow-xl transform hover:scale-105 transition-all duration-300">
                                    <i class="fas fa-search mr-2"></i>
                                    Aplicar Filtros
                                </button>
                                
                                <a href="catalogo.php"
                                   class="w-full border-2 border-gray-300 text-gray-700 py-4 rounded-2xl font-semibold hover:bg-gray-50 hover:border-primary-300 transition-all duration-300 block text-center">
                                    <i class="fas fa-times mr-2"></i>
                                    Limpiar Filtros
                                </a>
                            </div>
                        </form>
                        
                        <!-- Quick Categories -->
                        <div class="mt-12 pt-8 border-t border-gray-200">
                            <h4 class="font-semibold text-gray-900 mb-6 text-lg">Categorías Populares</h4>
                            <div class="space-y-3">
                                <?php foreach (array_slice($categories, 0, 6) as $category): ?>
                                <a href="categoria.php?categoria=<?php echo $category['slug']; ?>"
                                   class="block px-4 py-3 text-gray-600 hover:text-primary-500 hover:bg-primary-50 rounded-xl transition-all duration-300 font-medium">
                                    <i class="fas fa-chevron-right mr-2 text-xs"></i>
                                    <?php echo htmlspecialchars($category['name']); ?>
                                </a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Products Section -->
                <div class="w-full lg:w-3/4">
                    <!-- Desktop Results Header -->
                    <div class="hidden md:flex flex-col sm:flex-row justify-between items-start sm:items-center mb-12 bg-white/90 backdrop-blur-sm rounded-3xl luxury-shadow p-8 border border-white/50" data-aos="fade-up">
                        <div>
                            <h2 class="text-3xl font-serif font-bold text-gray-900 mb-3">
                                <?php echo number_format($totalProducts); ?> productos encontrados
                            </h2>
                            <p class="text-gray-600 font-light text-lg">
                                Página <?php echo $page; ?> de <?php echo max(1, $totalPages); ?>
                            </p>
                        </div>
                        
                        <!-- View Toggle -->
                        <div class="flex items-center space-x-3 mt-6 sm:mt-0">
                            <span class="text-gray-600 font-medium">Vista:</span>
                            <div class="flex bg-gray-100 rounded-2xl p-1">
                                <button onclick="toggleView('grid')" id="grid-view" 
                                        class="p-3 rounded-xl bg-gradient-to-r from-primary-500 to-secondary-500 text-white shadow-lg">
                                    <i class="fas fa-th"></i>
                                </button>
                                <button onclick="toggleView('list')" id="list-view" 
                                        class="p-3 rounded-xl text-gray-600 hover:bg-white hover:shadow-md transition-all duration-300">
                                    <i class="fas fa-list"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Mobile Results Header -->
                    <div class="md:hidden mb-6 bg-white/90 backdrop-blur-sm rounded-xl p-4 border border-gray-200" data-aos="fade-up">
                        <div class="text-center">
                            <h2 class="text-lg font-semibold text-gray-900 mb-1">
                                <?php echo number_format($totalProducts); ?> productos encontrados
                            </h2>
                            <p class="text-sm text-gray-600">
                                Página <?php echo $page; ?> de <?php echo max(1, $totalPages); ?>
                            </p>
                        </div>
                    </div>
                    
                    <?php if (!empty($products)): ?>
                        <!-- Products Grid -->
                        <div id="products-container" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 md:gap-8">
                            <?php foreach ($products as $product):
                                // Obtener información de la marca
                                $productBrand = !empty($product['brand_id']) ? $brandModel->getById($product['brand_id']) : null;
                                $brandName = $productBrand ? $productBrand['name'] : 'Sin marca';
                                
                                // Calcular descuento si hay precio de oferta
                                $discount = 0;
                                if (!empty($product['compare_price']) && $product['compare_price'] > $product['price']) {
                                    $discount = round((($product['compare_price'] - $product['price']) / $product['compare_price']) * 100);
                                }
                                
                                // Imagen del producto - verificar si ya incluye la ruta completa
                                if (!empty($product['main_image'])) {
                                    if (strpos($product['main_image'], 'uploads/products/') === 0) {
                                        // La ruta ya incluye uploads/products/
                                        $productImage = BASE_URL . '/' . $product['main_image'];
                                    } else {
                                        // Solo el nombre del archivo
                                        $productImage = BASE_URL . '/uploads/products/' . $product['main_image'];
                                    }
                                } else {
                                    $productImage = BASE_URL . '/assets/images/placeholder-product.svg';
                                }
                            ?>
                            <div class="product-card group" data-aos="fade-up">
                                <!-- Mobile Card -->
                                <div class="md:hidden bg-white rounded-2xl overflow-hidden mobile-shadow border border-gray-100 mobile-hover">
                                    <div class="relative">
                                        <img src="<?php echo $productImage; ?>"
                                             alt="<?php echo htmlspecialchars($product['name']); ?>"
                                             class="w-full h-48 object-cover"
                                             loading="lazy"
                                             onerror="this.src='<?php echo BASE_URL; ?>/assets/images/placeholder-product.svg'">
                                        
                                        <!-- Mobile Badges -->
                                        <div class="absolute top-2 left-2 space-y-1">
                                            <?php if ($discount > 0): ?>
                                            <span class="bg-red-500 text-white px-2 py-1 rounded-full text-xs font-semibold">
                                                -<?php echo $discount; ?>%
                                            </span>
                                            <?php endif; ?>
                                            
                                            <?php if (!empty($product['is_new']) && $product['is_new']): ?>
                                            <span class="bg-green-500 text-white px-2 py-1 rounded-full text-xs font-semibold block">
                                                NUEVO
                                            </span>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <!-- Mobile Quick Actions -->
                                        <div class="absolute top-2 right-2 space-y-1">
                                            <button class="w-8 h-8 bg-white/90 backdrop-blur-sm rounded-full flex items-center justify-center shadow-md mobile-tap <?php echo ($product['in_wishlist'] > 0) ? 'text-red-500' : 'text-gray-600'; ?>"
                                                    onclick="toggleWishlist(<?php echo $product['id']; ?>)"
                                                    title="Agregar a favoritos">
                                                <i class="<?php echo ($product['in_wishlist'] > 0) ? 'fas' : 'far'; ?> fa-heart text-sm"></i>
                                            </button>
                                            <button class="w-8 h-8 bg-white/90 backdrop-blur-sm rounded-full flex items-center justify-center shadow-md mobile-tap text-gray-600"
                                                    onclick="quickView(<?php echo $product['id']; ?>)"
                                                    title="Ver detalles">
                                                <i class="fas fa-eye text-sm"></i>
                                            </button>
                                        </div>
                                    </div>
                                    
                                    <div class="p-4">
                                        <div class="mb-2">
                                            <span class="text-xs font-medium text-secondary-600 uppercase tracking-wide">
                                                <?php echo htmlspecialchars($brandName); ?>
                                            </span>
                                        </div>
                                        <h3 class="font-semibold text-gray-900 mb-2 line-clamp-2 text-sm">
                                            <?php echo htmlspecialchars($product['name']); ?>
                                        </h3>
                                        
                                        <!-- Mobile Rating -->
                                        <div class="flex items-center mb-3">
                                            <div class="flex text-yellow-400">
                                                <?php for ($i = 0; $i < 5; $i++): ?>
                                                <i class="fas fa-star text-xs"></i>
                                                <?php endfor; ?>
                                            </div>
                                            <span class="text-xs text-gray-500 ml-2">(4.9)</span>
                                        </div>
                                        
                                        <!-- Mobile Price -->
                                        <div class="flex items-center justify-between mb-3">
                                            <div class="flex items-center space-x-2">
                                                <span class="text-lg font-bold text-primary-600">
                                                    $<?php echo number_format($product['price'], 0, ',', '.'); ?>
                                                </span>
                                                <?php if (!empty($product['compare_price']) && $product['compare_price'] > $product['price']): ?>
                                                <span class="text-sm text-gray-400 line-through">
                                                    $<?php echo number_format($product['compare_price'], 0, ',', '.'); ?>
                                                </span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        
                                        <!-- Mobile Add to Cart -->
                                        <?php if ($product['inventory_quantity'] > 0): ?>
                                        <button type="button" class="w-full bg-gradient-to-r from-primary-500 to-secondary-500 text-white py-2 rounded-xl font-semibold text-sm mobile-tap z-30 relative"
                                                onclick="addToCart(<?php echo $product['id']; ?>, 1); return false;">
                                            <i class="fas fa-shopping-cart mr-2"></i>
                                            Agregar
                                        </button>
                                        <?php else: ?>
                                        <button class="w-full bg-gray-300 text-gray-500 py-2 rounded-xl font-semibold text-sm cursor-not-allowed z-30 relative" disabled>
                                            Agotado
                                        </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <!-- Desktop Card -->
                                <div class="hidden md:block bg-white/90 backdrop-blur-sm rounded-3xl overflow-hidden hover-lift transition-all duration-500 shimmer-effect border border-white/50 relative z-10">
                                    <div class="relative overflow-hidden rounded-t-3xl z-20">
                                        <img src="<?php echo $productImage; ?>"
                                             alt="<?php echo htmlspecialchars($product['name']); ?>"
                                             class="w-full h-80 object-cover group-hover:scale-110 transition-transform duration-700"
                                             loading="lazy"
                                             onerror="this.src='<?php echo BASE_URL; ?>/assets/images/placeholder-product.svg'">
                                        
                                        <!-- Desktop Product Badges -->
                                        <div class="absolute top-4 left-4 space-y-2">
                                            <?php if ($discount > 0): ?>
                                            <span class="bg-gradient-to-r from-red-500 to-pink-500 text-white px-3 py-1 rounded-full text-sm font-semibold shadow-lg">
                                                -<?php echo $discount; ?>%
                                            </span>
                                            <?php endif; ?>
                                            
                                            <?php if (!empty($product['is_new']) && $product['is_new']): ?>
                                            <span class="bg-gradient-to-r from-green-500 to-emerald-500 text-white px-3 py-1 rounded-full text-sm font-semibold shadow-lg block">
                                                NUEVO
                                            </span>
                                            <?php endif; ?>
                                            
                                            <?php if (!empty($product['featured']) && $product['featured']): ?>
                                            <span class="bg-gradient-to-r from-primary-500 to-secondary-500 text-white px-3 py-1 rounded-full text-sm font-semibold shadow-lg block">
                                                DESTACADO
                                            </span>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <!-- Desktop Quick Actions -->
                                        <div class="absolute top-4 right-4 space-y-3 opacity-0 group-hover:opacity-100 transition-all duration-300 z-20">
                                            <button class="w-12 h-12 bg-white/90 backdrop-blur-sm rounded-full flex items-center justify-center shadow-lg hover:bg-primary-500 hover:text-white transition-all duration-300 transform hover:scale-110 z-30 relative <?php echo ($product['in_wishlist'] > 0) ? 'text-red-500' : ''; ?>"
                                                    onclick="toggleWishlist(<?php echo $product['id']; ?>)"
                                                    title="<?php echo ($product['in_wishlist'] > 0) ? 'Remover de favoritos' : 'Agregar a favoritos'; ?>">
                                                <i class="<?php echo ($product['in_wishlist'] > 0) ? 'fas' : 'far'; ?> fa-heart"></i>
                                            </button>
                                            <button class="w-12 h-12 bg-white/90 backdrop-blur-sm rounded-full flex items-center justify-center shadow-lg hover:bg-primary-500 hover:text-white transition-all duration-300 transform hover:scale-110 z-30 relative"
                                                    onclick="quickView(<?php echo $product['id']; ?>)"
                                                    title="Vista rápida">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                        </div>
                                        
                                        <!-- Desktop Add to Cart Overlay -->
                                        <div class="absolute bottom-0 left-0 right-0 bg-gradient-to-t from-black/60 via-black/30 to-transparent p-6 transform translate-y-full group-hover:translate-y-0 transition-transform duration-500 z-20">
                                            <?php if ($product['inventory_quantity'] > 0): ?>
                                            <button type="button" class="w-full bg-white/95 backdrop-blur-sm text-gray-900 py-3 rounded-2xl font-semibold hover:bg-primary-500 hover:text-white transition-all duration-300 shadow-lg z-30 relative"
                                                    onclick="addToCart(<?php echo $product['id']; ?>, 1); return false;">
                                                <i class="fas fa-shopping-cart mr-2"></i>
                                                Agregar al Carrito
                                            </button>
                                            <?php else: ?>
                                            <button class="w-full bg-gray-400 text-white py-3 rounded-2xl font-semibold cursor-not-allowed z-30 relative" disabled>
                                                <i class="fas fa-times mr-2"></i>
                                                Agotado
                                            </button>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <!-- Overlay Gradient -->
                                        <div class="absolute inset-0 bg-gradient-to-t from-black/20 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300 pointer-events-none z-10"></div>
                                    </div>
                                    
                                    <div class="p-6">
                                        <div class="mb-3">
                                            <span class="text-sm font-medium text-secondary-600 tracking-wide uppercase">
                                                <?php echo htmlspecialchars($brandName); ?>
                                            </span>
                                        </div>
                                        <h3 class="font-semibold text-lg text-gray-900 mb-3 line-clamp-2 group-hover:text-primary-600 transition-colors">
                                            <?php echo htmlspecialchars($product['name']); ?>
                                        </h3>
                                        
                                        <?php if (!empty($product['short_description'])): ?>
                                        <p class="text-sm text-gray-600 mb-4 line-clamp-2 font-light">
                                            <?php echo htmlspecialchars($product['short_description']); ?>
                                        </p>
                                        <?php endif; ?>
                                        
                                        <!-- Rating -->
                                        <div class="flex items-center mb-4">
                                            <div class="flex text-yellow-400">
                                                <?php for ($i = 0; $i < 5; $i++): ?>
                                                <i class="fas fa-star text-sm"></i>
                                                <?php endfor; ?>
                                            </div>
                                            <span class="text-sm text-gray-500 ml-2 font-light">(4.9)</span>
                                        </div>
                                        
                                        <!-- Price -->
                                        <div class="flex items-center justify-between mb-4">
                                            <div class="flex items-center space-x-3">
                                                <span class="text-2xl font-bold text-primary-600">
                                                    $<?php echo number_format($product['price'], 0, ',', '.'); ?>
                                                </span>
                                                <?php if (!empty($product['compare_price']) && $product['compare_price'] > $product['price']): ?>
                                                <span class="text-lg text-gray-400 line-through">
                                                    $<?php echo number_format($product['compare_price'], 0, ',', '.'); ?>
                                                </span>
                                                <?php endif; ?>
                                            </div>
                                            <?php if ($discount > 0): ?>
                                            <span class="text-sm text-green-600 font-semibold bg-green-50 px-2 py-1 rounded-full">
                                                Ahorra <?php echo $discount; ?>%
                                            </span>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <!-- Stock Status -->
                                        <div class="flex items-center justify-between">
                                            <?php if ($product['inventory_quantity'] > 0): ?>
                                                <span class="text-sm text-green-600 flex items-center font-medium">
                                                    <i class="fas fa-check-circle mr-2"></i>
                                                    En stock (<?php echo $product['inventory_quantity']; ?>)
                                                </span>
                                            <?php else: ?>
                                                <span class="text-sm text-red-600 flex items-center font-medium">
                                                    <i class="fas fa-times-circle mr-2"></i>
                                                    Agotado
                                                </span>
                                            <?php endif; ?>
                                            
                                            <button class="text-primary-500 hover:text-primary-600 text-sm font-semibold hover:underline transition-all duration-300"
                                                    onclick="window.location.href='product.php?id=<?php echo $product['id']; ?>'">
                                                Ver detalles →
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <!-- Mobile Pagination -->
                        <?php if ($totalPages > 1): ?>
                        <div class="mt-8 md:hidden" data-aos="fade-up">
                            <div class="flex justify-center items-center space-x-2">
                                <?php if ($page > 1): ?>
                                <a href="?page=<?php echo $page-1; ?>&<?php echo http_build_query($filters); ?>"
                                   class="px-4 py-2 bg-white border border-gray-200 rounded-xl text-gray-700 hover:bg-primary-50 hover:border-primary-300 hover:text-primary-600 transition-all duration-300 font-medium mobile-shadow mobile-tap">
                                    <i class="fas fa-chevron-left"></i>
                                </a>
                                <?php endif; ?>
                                
                                <span class="px-4 py-2 bg-gradient-to-r from-primary-500 to-secondary-500 text-white rounded-xl font-semibold mobile-shadow">
                                    <?php echo $page; ?> / <?php echo $totalPages; ?>
                                </span>
                                
                                <?php if ($page < $totalPages): ?>
                                <a href="?page=<?php echo $page+1; ?>&<?php echo http_build_query($filters); ?>"
                                   class="px-4 py-2 bg-white border border-gray-200 rounded-xl text-gray-700 hover:bg-primary-50 hover:border-primary-300 hover:text-primary-600 transition-all duration-300 font-medium mobile-shadow mobile-tap">
                                    <i class="fas fa-chevron-right"></i>
                                </a>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <!-- Desktop Pagination -->
                        <?php if ($totalPages > 1): ?>
                        <div class="hidden md:block mt-16" data-aos="fade-up">
                            <nav class="flex items-center justify-center space-x-3">
                                <?php if ($page > 1): ?>
                                <a href="?page=<?php echo $page-1; ?>&<?php echo http_build_query($filters); ?>"
                                   class="px-6 py-3 bg-white/90 backdrop-blur-sm border border-gray-200 rounded-2xl text-gray-700 hover:bg-primary-50 hover:border-primary-300 hover:text-primary-600 transition-all duration-300 font-medium shadow-lg">
                                    <i class="fas fa-chevron-left mr-2"></i>
                                    Anterior
                                </a>
                                <?php endif; ?>
                                
                                <?php
                                $start = max(1, $page - 2);
                                $end = min($totalPages, $page + 2);
                                for ($i = $start; $i <= $end; $i++):
                                ?>
                                <a href="?page=<?php echo $i; ?>&<?php echo http_build_query($filters); ?>"
                                   class="px-4 py-3 rounded-2xl transition-all duration-300 font-semibold shadow-lg <?php echo $i == $page ? 'bg-gradient-to-r from-primary-500 to-secondary-500 text-white' : 'bg-white/90 backdrop-blur-sm border border-gray-200 text-gray-700 hover:bg-primary-50 hover:border-primary-300 hover:text-primary-600'; ?>">
                                    <?php echo $i; ?>
                                </a>
                                <?php endfor; ?>
                                
                                <?php if ($page < $totalPages): ?>
                                <a href="?page=<?php echo $page+1; ?>&<?php echo http_build_query($filters); ?>"
                                   class="px-6 py-3 bg-white/90 backdrop-blur-sm border border-gray-200 rounded-2xl text-gray-700 hover:bg-primary-50 hover:border-primary-300 hover:text-primary-600 transition-all duration-300 font-medium shadow-lg">
                                    Siguiente
                                    <i class="fas fa-chevron-right ml-2"></i>
                                </a>
                                <?php endif; ?>
                            </nav>
                        </div>
                        <?php endif; ?>
                        
                    <?php else: ?>
                        <!-- No Products Found -->
                        <div class="text-center py-12 md:py-20" data-aos="fade-up">
                            <div class="w-24 h-24 md:w-32 md:h-32 bg-gradient-to-r from-primary-100 to-secondary-100 rounded-full flex items-center justify-center mx-auto mb-6 md:mb-8">
                                <i class="fas fa-search text-2xl md:text-4xl text-primary-500"></i>
                            </div>
                            <h3 class="text-2xl md:text-3xl font-serif font-bold text-gray-800 mb-3 md:mb-4">No se encontraron productos</h3>
                            <p class="text-lg md:text-xl text-gray-600 mb-6 md:mb-8 font-light max-w-md mx-auto px-4">
                                Intenta ajustar los filtros o explora nuestras categorías populares.
                            </p>
                            <a href="catalogo.php" 
                               class="inline-flex items-center bg-gradient-to-r from-primary-500 to-secondary-500 text-white px-6 md:px-8 py-3 md:py-4 rounded-xl md:rounded-2xl font-semibold hover:shadow-xl transform hover:scale-105 transition-all duration-300 mobile-tap">
                                <i class="fas fa-arrow-left mr-2"></i>
                                Ver todos los productos
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>

    <!-- Product Quick View Modal -->
    <div id="product-modal" class="fixed inset-0 bg-black/50 backdrop-blur-sm z-50 hidden flex items-center justify-center p-4">
        <div class="bg-white/95 backdrop-blur-md rounded-3xl max-w-6xl w-full max-h-[95vh] overflow-hidden shadow-2xl border border-white/50">
            <div class="relative">
                <!-- Modal Header -->
                <div class="flex justify-between items-center p-6 md:p-8 border-b border-gray-200">
                    <h3 class="text-2xl md:text-3xl font-serif font-bold text-gray-800">Vista Rápida</h3>
                    <button onclick="closeProductModal()" 
                            class="w-10 h-10 md:w-12 md:h-12 bg-gray-100 rounded-full flex items-center justify-center text-gray-400 hover:text-gray-600 hover:bg-gray-200 transition-all duration-300 mobile-tap">
                        <i class="fas fa-times text-lg"></i>
                    </button>
                </div>
                
                <!-- Modal Content -->
                <div class="overflow-y-auto max-h-[calc(95vh-100px)] mobile-scroll">
                    <div id="modal-content" class="p-6 md:p-8 grid grid-cols-1 lg:grid-cols-2 gap-8 md:gap-12">
                        <!-- Product content will be loaded here -->
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Call to Action -->
    <section class="py-12 md:py-20 relative overflow-hidden">
        <div class="absolute inset-0 bg-gradient-to-r from-primary-500 via-secondary-500 to-primary-500"></div>
        <div class="absolute inset-0 bg-black/20"></div>
        
        <div class="container mx-auto px-4 text-center relative z-10">
            <h2 class="text-3xl md:text-4xl lg:text-6xl font-serif font-bold text-white mb-6 md:mb-8" data-aos="fade-up">
                ¿No encuentras lo que buscas?
            </h2>
            <p class="text-lg md:text-xl lg:text-2xl text-white/90 mb-8 md:mb-12 max-w-3xl mx-auto font-light leading-relaxed px-4" data-aos="fade-up" data-aos-delay="100">
                Nuestro equipo de expertos en tecnología está aquí para ayudarte a encontrar el producto ideal para ti o tu empresa.
            </p>
            <div class="flex flex-col sm:flex-row gap-4 md:gap-6 justify-center px-4" data-aos="fade-up" data-aos-delay="200">
                <a href="#filtros" onclick="document.querySelector('#filtros').scrollIntoView({behavior: 'smooth'})" 
                   class="group bg-white text-primary-500 px-8 md:px-10 py-4 md:py-5 rounded-xl md:rounded-2xl font-semibold hover:shadow-2xl transform hover:scale-105 transition-all duration-300 inline-flex items-center justify-center mobile-tap">
                    <i class="fas fa-filter mr-3 group-hover:scale-110 transition-transform duration-300"></i>
                    Filtrar Productos
                </a>
                <a href="#" 
                   class="group border-2 border-white text-white px-8 md:px-10 py-4 md:py-5 rounded-xl md:rounded-2xl font-semibold hover:bg-white hover:text-primary-500 transition-all duration-300 inline-flex items-center justify-center mobile-tap">
                    <i class="fas fa-comments mr-3 group-hover:scale-110 transition-transform duration-300"></i>
                    Contactar Asesor
                </a>
            </div>
        </div>
    </section>
    
    <!-- Footer -->
    <div class="hidden md:block">
        <?php include 'includes/global-footer.php'; ?>
        <!-- Solo uno de los footers para evitar duplicado de WhatsApp -->
        <!-- <?php // include 'includes/footer.php'; ?> -->
    </div>
    
    <!-- Mobile Footer -->
    <footer class="md:hidden bg-gray-900 text-white py-8">
        <div class="container mx-auto px-4">
            <div class="text-center mb-6">
                <h3 class="text-2xl font-serif font-bold gradient-text mb-2">ElectroShop</h3>
                <p class="text-gray-400 text-sm">Tecnología, innovación y calidad para ti</p>
            </div>
            
            <div class="grid grid-cols-2 gap-6 mb-6">
                <div>
                    <h4 class="font-semibold mb-3">Enlaces</h4>
                    <ul class="space-y-2 text-sm text-gray-400">
                        <li><a href="index.php" class="hover:text-white transition-colors">Inicio</a></li>
                        <li><a href="catalogo.php" class="hover:text-white transition-colors">Catálogo</a></li>
                        <li><a href="ofertas.php" class="hover:text-white transition-colors">Ofertas</a></li>
                        <li><a href="marcas.php" class="hover:text-white transition-colors">Marcas</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="font-semibold mb-3">Ayuda</h4>
                    <ul class="space-y-2 text-sm text-gray-400">
                        <li><a href="#" class="hover:text-white transition-colors">Contacto</a></li>
                        <li><a href="#" class="hover:text-white transition-colors">Envíos</a></li>
                        <li><a href="#" class="hover:text-white transition-colors">Devoluciones</a></li>
                        <li><a href="#" class="hover:text-white transition-colors">FAQ</a></li>
                    </ul>
                </div>
            </div>
            
            <div class="text-center pt-6 border-t border-gray-800">
                <p class="text-xs text-gray-500">© 2024 ElectroShop. Todos los derechos reservados.</p>
            </div>
        </div>
    </footer>
    
    <!-- Include main.js for cart functionality -->
    <script src="assets/js/main.js"></script>
    
    <!-- JavaScript -->
    <script>
        // Inicializar token CSRF global
        window.globalCSRFToken = '<?php echo CSRFProtection::generateGlobalToken(); ?>';
        console.log('Token CSRF inicial (catalog):', window.globalCSRFToken);
        
        // Cart Sidebar Functions - Asegurar que esté disponible
        function openCartSidebar() {
            window.location.href = 'carrito.php';
        }
        
        // Mobile Search Function
        function toggleMobileSearch() {
            const container = document.getElementById('mobile-search-container');
            if (container) {
                container.classList.toggle('hidden');
                if (!container.classList.contains('hidden')) {
                    const input = container.querySelector('input');
                    if (input) input.focus();
                }
            }
        }
        
        // CSRF Token Management
        function getCSRFToken() {
            // Usar token global si está disponible
            if (window.globalCSRFToken) {
                return window.globalCSRFToken;
            }
            
            // Usar token del meta tag como fallback
            const metaToken = document.querySelector('meta[name="cart-csrf-token"]');
            if (metaToken) {
                return metaToken.getAttribute('content');
            }
            
            console.error('No CSRF token available');
            return null;
        }

        function updateCSRFToken(newToken) {
            window.globalCSRFToken = newToken;
            const metaToken = document.querySelector('meta[name="cart-csrf-token"]');
            if (metaToken) {
                metaToken.setAttribute('content', newToken);
            }
        }

        function updateCartCount() {
            fetch('<?php echo BASE_URL; ?>/cart-count.php')
            .then(response => response.json())
            .then(data => {
                const cartCount = document.getElementById('cart-count');
                if (cartCount) {
                    cartCount.textContent = data.count || 0;
                }
            })
            .catch(error => console.error('Error updating cart count:', error));
        }
        
        // Add to Cart Function
        function addToCart(productId, quantity = 1) {
            const csrfToken = getCSRFToken();
            
            console.log('=== CATALOG CART ADD DEBUG ===');
            console.log('Product ID:', productId);
            console.log('Quantity:', quantity);
            console.log('CSRF Token:', csrfToken);
            
            if (!csrfToken) {
                showNotification('Error de seguridad. Recarga la página.', 'error');
                return false;
            }
            
            // Usar FormData para enviar los datos
            const formData = new FormData();
            formData.append('product_id', productId);
            formData.append('quantity', quantity);
            formData.append('csrf_token', csrfToken);
            
            fetch('<?php echo BASE_URL; ?>/cart-add.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.text();
            })
            .then(text => {
                try {
                    const data = JSON.parse(text);
                    if (data.success) {
                        showNotification('Producto agregado al carrito', 'success');
                        updateCartCount();
                    } else {
                        // Si se proporciona un nuevo token CSRF, guardarlo
                        if (data.new_csrf_token) {
                            updateCSRFToken(data.new_csrf_token);
                            showNotification('Token de seguridad actualizado. Intenta de nuevo.', 'info');
                        } else {
                            showNotification(data.message || 'Error al agregar al carrito', 'error');
                        }
                    }
                } catch (e) {
                    console.error('JSON Parse Error:', e);
                    console.error('Response text:', text);
                    showNotification('Error de respuesta del servidor', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('Error al agregar al carrito', 'error');
            });
            
            return false;
        }
        
        // Quick View Function
        function quickView(productId) {
            window.open(`product.php?id=${productId}`, '_blank');
        }
        
        // Toggle View Function
        function toggleView(viewType) {
            const gridView = document.getElementById('grid-view');
            const listView = document.getElementById('list-view');
            const container = document.getElementById('products-container');
            
            if (viewType === 'grid') {
                gridView.classList.add('bg-gradient-to-r', 'from-primary-500', 'to-secondary-500', 'text-white', 'shadow-lg');
                gridView.classList.remove('text-gray-600', 'hover:bg-white', 'hover:shadow-md');
                
                listView.classList.remove('bg-gradient-to-r', 'from-primary-500', 'to-secondary-500', 'text-white', 'shadow-lg');
                listView.classList.add('text-gray-600', 'hover:bg-white', 'hover:shadow-md');
                
                container.classList.remove('space-y-6');
                container.classList.add('grid', 'grid-cols-1', 'sm:grid-cols-2', 'lg:grid-cols-3', 'gap-4', 'md:gap-8');
            } else {
                listView.classList.add('bg-gradient-to-r', 'from-primary-500', 'to-secondary-500', 'text-white', 'shadow-lg');
                listView.classList.remove('text-gray-600', 'hover:bg-white', 'hover:shadow-md');
                
                gridView.classList.remove('bg-gradient-to-r', 'from-primary-500', 'to-secondary-500', 'text-white', 'shadow-lg');
                gridView.classList.add('text-gray-600', 'hover:bg-white', 'hover:shadow-md');
                
                container.classList.add('space-y-6');
                container.classList.remove('grid', 'grid-cols-1', 'sm:grid-cols-2', 'lg:grid-cols-3', 'gap-4', 'md:gap-8');
            }
        }
        
        // Close Product Modal Function
        function closeProductModal() {
            const modal = document.getElementById('product-modal');
            if (modal) {
                modal.classList.add('hidden');
            }
        }
        
        // Notification Function
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
                    document.body.removeChild(notification);
                }, 300);
            }, 3000);
        }
        
        AOS.init({
            duration: 1000,
            easing: 'ease-out-cubic',
            once: true,
            offset: 50
        });

        // Mobile Menu Functions
        function toggleMobileMenu() {
            const menu = document.getElementById('mobile-menu');
            const panel = document.getElementById('mobile-menu-panel');
            
            if (menu.classList.contains('hidden')) {
                menu.classList.remove('hidden');
                setTimeout(() => {
                    panel.classList.remove('-translate-x-full');
                }, 10);
            } else {
                panel.classList.add('-translate-x-full');
                setTimeout(() => {
                    menu.classList.add('hidden');
                }, 300);
            }
        }

        // Mobile search is now handled by main.js
        // function toggleMobileSearch() removed to avoid conflicts

        // Mobile Filters Functions
        function toggleMobileFilters() {
            const filters = document.getElementById('mobile-filters');
            const panel = document.getElementById('mobile-filters-panel');
            
            if (filters.classList.contains('hidden')) {
                filters.classList.remove('hidden');
                setTimeout(() => {
                    panel.classList.remove('translate-y-full');
                }, 10);
            } else {
                panel.classList.add('translate-y-full');
                setTimeout(() => {
                    filters.classList.add('hidden');
                }, 300);
            }
        }

        function applyMobileFilters() {
            document.getElementById('mobile-filters-form').submit();
        }

        function clearMobileFilters() {
            window.location.href = 'catalogo.php';
        }

        function handleMobileSortChange(sortValue) {
            const url = new URL(window.location);
            url.searchParams.set('orden', sortValue);
            window.location.href = url.toString();
        }

        // Toggle wishlist
        function toggleWishlist(productId) {
            <?php if (isset($_SESSION['user_id'])): ?>
                fetch('wishlist-toggle.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `product_id=${productId}`
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.text();
                })
                .then(text => {
                    try {
                        const data = JSON.parse(text);
                        if (data.success) {
                            showNotification(data.message, 'success');
                            // Update wishlist icon
                            const wishBtns = document.querySelectorAll(`button[onclick="toggleWishlist(${productId})"] i`);
                            wishBtns.forEach(wishBtn => {
                                if (data.added) {
                                    wishBtn.classList.remove('far');
                                    wishBtn.classList.add('fas');
                                    wishBtn.parentElement.classList.add('text-red-500');
                                } else {
                                    wishBtn.classList.remove('fas');
                                    wishBtn.classList.add('far');
                                    wishBtn.parentElement.classList.remove('text-red-500');
                                }
                            });
                        } else {
                            showNotification(data.message || 'Error en favoritos', 'error');
                        }
                    } catch (e) {
                        console.error('JSON parse error:', e);
                        showNotification('Error al procesar la respuesta del servidor', 'error');
                    }
                })
                .catch(error => {
                    console.error('Fetch error:', error);
                    showNotification('Error en favoritos', 'error');
                });
            <?php else: ?>
                showNotification('Debes iniciar sesión para usar favoritos', 'warning');
                setTimeout(() => {
                    window.location.href = 'login.php';
                }, 2000);
            <?php endif; ?>
        }

        // Quick view
        function quickView(productId) {
            window.location.href = `product.php?id=${productId}`;
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
            
            notification.className = `fixed top-4 right-4 z-50 p-4 md:p-6 rounded-xl md:rounded-2xl shadow-2xl text-white max-w-sm transition-all duration-500 transform translate-x-full bg-gradient-to-r ${colors[type] || colors.info}`;
            notification.innerHTML = `
                <div class="flex items-center">
                    <span class="mr-3 text-xl md:text-2xl">
                        ${type === 'success' ? '✓' : type === 'error' ? '✗' : type === 'warning' ? '⚠' : 'ℹ'}
                    </span>
                    <span class="font-medium text-sm md:text-base">${message}</span>
                    <button onclick="this.parentElement.parentElement.remove()" class="ml-4 text-white hover:text-gray-200 text-xl">×</button>
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

        // Auto-submit filters form when changed (desktop only)
        document.querySelectorAll('#filters-form select, #filters-form input[type="checkbox"]').forEach(element => {
            element.addEventListener('change', function() {
                document.getElementById('filters-form').submit();
            });
        });

        // View toggle functionality (desktop only)
        function toggleView(viewType) {
            const container = document.getElementById('products-container');
            const gridBtn = document.getElementById('grid-view');
            const listBtn = document.getElementById('list-view');
            
            if (viewType === 'grid') {
                container.className = 'grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 md:gap-8';
                gridBtn.className = 'p-3 rounded-xl bg-gradient-to-r from-primary-500 to-secondary-500 text-white shadow-lg';
                listBtn.className = 'p-3 rounded-xl text-gray-600 hover:bg-white hover:shadow-md transition-all duration-300';
            } else {
                container.className = 'space-y-8';
                listBtn.className = 'p-3 rounded-xl bg-gradient-to-r from-primary-500 to-secondary-500 text-white shadow-lg';
                gridBtn.className = 'p-3 rounded-xl text-gray-600 hover:bg-white hover:shadow-md transition-all duration-300';
                
                // Modify cards for list view
                document.querySelectorAll('.product-card .hidden.md\\:block').forEach(card => {
                    card.className = 'hidden md:flex bg-white/90 backdrop-blur-sm rounded-3xl overflow-hidden hover-lift transition-all duration-500 shimmer-effect border border-white/50';
                });
            }
        }

        // Initialize cart count on page load
        document.addEventListener('DOMContentLoaded', function() {
            updateCartCount();
            
            // Update active filters count
            const activeFilters = <?php echo json_encode(array_filter($filters)); ?>;
            const activeCount = Object.keys(activeFilters).length;
            const filterCountBadge = document.getElementById('active-filters-count');
            
            if (activeCount > 0 && filterCountBadge) {
                filterCountBadge.textContent = activeCount;
                filterCountBadge.classList.remove('hidden');
            }
        });

        // Close modals when clicking outside
        document.addEventListener('click', function(e) {
            // Close mobile menu when clicking outside
            const mobileMenu = document.getElementById('mobile-menu');
            const mobileMenuPanel = document.getElementById('mobile-menu-panel');
            if (mobileMenu && !mobileMenu.classList.contains('hidden') && 
                !mobileMenuPanel.contains(e.target) && 
                !e.target.closest('button[onclick="toggleMobileMenu()"]')) {
                toggleMobileMenu();
            }
            
            // Close mobile filters when clicking outside
            const mobileFilters = document.getElementById('mobile-filters');
            const mobileFiltersPanel = document.getElementById('mobile-filters-panel');
            if (mobileFilters && !mobileFilters.classList.contains('hidden') && 
                !mobileFiltersPanel.contains(e.target) && 
                !e.target.closest('button[onclick="toggleMobileFilters()"]')) {
                toggleMobileFilters();
            }
        });

        // Prevent body scroll when modals are open
        function preventBodyScroll(prevent) {
            if (prevent) {
                document.body.style.overflow = 'hidden';
            } else {
                document.body.style.overflow = '';
            }
        }

        // Add scroll prevention to modal toggles
        const originalToggleMobileMenu = toggleMobileMenu;
        toggleMobileMenu = function() {
            const menu = document.getElementById('mobile-menu');
            const isHidden = menu.classList.contains('hidden');
            preventBodyScroll(isHidden);
            originalToggleMobileMenu();
        };

        const originalToggleMobileFilters = toggleMobileFilters;
        toggleMobileFilters = function() {
            const filters = document.getElementById('mobile-filters');
            const isHidden = filters.classList.contains('hidden');
            preventBodyScroll(isHidden);
            originalToggleMobileFilters();
        };

        // Real-time search functionality
        let searchTimeout;
        const searchInput = document.getElementById('mobile-search-input');
        const searchResults = document.getElementById('mobile-search-results');
        const searchResultsContent = document.getElementById('mobile-search-results-content');

        if (searchInput) {
            searchInput.addEventListener('input', function() {
                const query = this.value.trim();
                
                // Clear previous timeout
                clearTimeout(searchTimeout);
                
                if (query.length < 2) {
                    hideSearchResults();
                    return;
                }
                
                // Add loading indicator
                showSearchLoading();
                
                // Debounce search
                searchTimeout = setTimeout(() => {
                    performSearch(query);
                }, 300);
            });

            // Hide results when input loses focus (with delay to allow clicking results)
            searchInput.addEventListener('blur', function() {
                setTimeout(() => {
                    hideSearchResults();
                }, 200);
            });

            // Show results when input gains focus and has content
            searchInput.addEventListener('focus', function() {
                if (this.value.trim().length >= 2) {
                    searchResults.classList.remove('hidden');
                }
            });
        }

        function performSearch(query) {
            fetch(`api/search.php?q=${encodeURIComponent(query)}&limit=5`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.text();
                })
                .then(text => {
                    try {
                        const data = JSON.parse(text);
                        displaySearchResults(data);
                    } catch (e) {
                        console.error('JSON parse error:', e);
                        console.error('Response text:', text);
                        showSearchError();
                    }
                })
                .catch(error => {
                    console.error('Error en búsqueda:', error);
                    showSearchError();
                });
        }

        function showSearchLoading() {
            searchResultsContent.innerHTML = `
                <div class="flex items-center justify-center py-4">
                    <div class="animate-spin rounded-full h-6 w-6 border-b-2 border-primary-500"></div>
                    <span class="ml-2 text-gray-600">Buscando...</span>
                </div>
            `;
            searchResults.classList.remove('hidden');
        }

        function showSearchError() {
            searchResultsContent.innerHTML = `
                <div class="text-center py-4 text-gray-500">
                    <i class="fas fa-exclamation-triangle text-2xl mb-2"></i>
                    <p>Error en la búsqueda. Inténtalo de nuevo.</p>
                </div>
            `;
        }

        function displaySearchResults(data) {
            if ((!data.products || data.products.length === 0) && 
                (!data.brands || data.brands.length === 0) && 
                (!data.categories || data.categories.length === 0)) {
                searchResultsContent.innerHTML = `
                    <div class="text-center py-4 text-gray-500">
                        <i class="fas fa-search text-2xl mb-2"></i>
                        <p>No se encontraron resultados</p>
                    </div>
                `;
                searchResults.classList.remove('hidden');
                return;
            }

            let resultsHTML = '<div class="space-y-3">';
            
            // Show brands first if available
            if (data.brands && data.brands.length > 0) {
                resultsHTML += `
                    <div class="border-b border-gray-100 pb-3">
                        <h4 class="text-sm font-semibold text-gray-700 mb-2 flex items-center">
                            <i class="fas fa-star mr-2 text-primary-500"></i>Marcas
                        </h4>
                        <div class="space-y-1">
                `;
                
                data.brands.forEach(brand => {
                    let logoUrl;
                    if (brand.logo) {
                        if (brand.logo.startsWith('http') || brand.logo.startsWith('/')) {
                            logoUrl = brand.logo;
                        } else if (brand.logo.startsWith('uploads/')) {
                            logoUrl = `<?php echo BASE_URL; ?>/${brand.logo}`;
                        } else {
                            logoUrl = `<?php echo BASE_URL; ?>/uploads/brands/${brand.logo}`;
                        }
                    } else {
                        logoUrl = `<?php echo BASE_URL; ?>/assets/images/placeholder-brand.svg`;
                    }

                    resultsHTML += `
                        <div class="flex items-center space-x-3 p-2 hover:bg-primary-50 rounded-lg cursor-pointer transition-colors" 
                             onclick="window.location.href='${brand.url}'">
                            <img src="${logoUrl}" 
                                 alt="${brand.name}" 
                                 class="w-8 h-8 object-contain rounded border border-gray-200"
                                 onerror="this.src='<?php echo BASE_URL; ?>/assets/images/placeholder-brand.svg'">
                            <div class="flex-1 min-w-0">
                                <h5 class="font-medium text-gray-900 truncate text-sm">${brand.name}</h5>
                                <p class="text-xs text-primary-600">${brand.product_count || 0} productos</p>
                            </div>
                            <i class="fas fa-chevron-right text-gray-400 text-xs"></i>
                        </div>
                    `;
                });
                
                resultsHTML += '</div></div>';
            }
            
            // Show products after brands
            if (data.products && data.products.length > 0) {
                resultsHTML += `
                    <div>
                        <h4 class="text-sm font-semibold text-gray-700 mb-2 flex items-center">
                            <i class="fas fa-box mr-2 text-secondary-500"></i>Productos
                        </h4>
                        <div class="space-y-2">
                `;
                
                data.products.forEach(product => {
                    const price = new Intl.NumberFormat('es-CO', {
                        style: 'currency',
                        currency: 'COP',
                        minimumFractionDigits: 0
                    }).format(product.price);

                    // Fix image URL - check if image already has full path
                    let imageUrl;
                    if (product.image) {
                        if (product.image.startsWith('http') || product.image.startsWith('/')) {
                            imageUrl = product.image;
                        } else if (product.image.startsWith('uploads/')) {
                            imageUrl = `<?php echo BASE_URL; ?>/${product.image}`;
                        } else {
                            imageUrl = `<?php echo BASE_URL; ?>/uploads/products/${product.image}`;
                        }
                    } else {
                        imageUrl = `<?php echo BASE_URL; ?>/assets/images/placeholder-product.svg`;
                    }

                    resultsHTML += `
                        <div class="flex items-center space-x-3 p-3 hover:bg-gray-50 rounded-xl cursor-pointer transition-colors" onclick="window.location.href='${product.url}'">
                            <img src="${imageUrl}" 
                                 alt="${product.name}" 
                                 class="w-12 h-12 object-cover rounded-lg"
                                 onerror="this.src='<?php echo BASE_URL; ?>/assets/images/placeholder-product.svg'">
                            <div class="flex-1 min-w-0">
                                <h4 class="font-medium text-gray-900 truncate">${product.name}</h4>
                                <p class="text-sm text-gray-500">${product.brand_name || 'Sin marca'}</p>
                                <p class="text-sm font-semibold text-primary-600">${price}</p>
                            </div>
                            <div class="flex items-center space-x-2">
                                <button onclick="event.stopPropagation(); quickView(${product.id})" 
                                        class="w-8 h-8 bg-primary-50 hover:bg-primary-100 rounded-full flex items-center justify-center transition-colors"
                                        title="Vista rápida">
                                    <i class="fas fa-eye text-primary-600 text-sm"></i>
                                </button>
                                <i class="fas fa-chevron-right text-gray-400"></i>
                            </div>
                        </div>
                    `;
                });
                
                resultsHTML += '</div></div>';
            }

            // Always show "Ver más" button if there are results
            resultsHTML += `
                <div class="border-t pt-3 mt-3 space-y-2">
                    <button onclick="applySearchFilter('${encodeURIComponent(searchInput.value)}')" 
                            class="block w-full text-center bg-gradient-to-r from-primary-500 to-secondary-500 text-white py-3 rounded-xl font-semibold hover:from-primary-600 hover:to-secondary-600 transition-all duration-300">
                        <i class="fas fa-filter mr-2"></i>Filtrar por: "${searchInput.value}" (${data.total || 0})
                    </button>
                    <button onclick="hideSearchResults()" 
                            class="block w-full text-center text-gray-500 hover:text-gray-700 py-2 text-sm">
                        Cerrar búsqueda
                    </button>
                </div>
            `;

            resultsHTML += '</div>';
            searchResultsContent.innerHTML = resultsHTML;
            searchResults.classList.remove('hidden');
        }

        function hideSearchResults() {
            if (searchResults) {
                searchResults.classList.add('hidden');
            }
        }

        // Function to apply search filter to current products
        function applySearchFilter(searchTerm) {
            const searchInput = document.getElementById('search-input');
            searchInput.value = decodeURIComponent(searchTerm);
            
            // Hide search results
            hideSearchResults();
            
            // Scroll to filters section
            const filtersSection = document.getElementById('filtros');
            if (filtersSection) {
                filtersSection.scrollIntoView({ behavior: 'smooth' });
            }
            
            // Auto-apply the search
            setTimeout(() => {
                filterProducts();
            }, 500);
        }
    </script>
</body>
</html>
