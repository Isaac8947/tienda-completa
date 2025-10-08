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
    error_log("Error loading search page: " . $e->getMessage());
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
    <title><?php echo $searchQuery ? 'Resultados para "' . htmlspecialchars($searchQuery) . '"' : 'Búsqueda de Productos'; ?> - Odisea Makeup Store</title>
    <meta name="description" content="<?php echo $searchQuery ? 'Encuentra productos de maquillaje y belleza para "' . htmlspecialchars($searchQuery) . '". Descubre las mejores marcas y productos.' : 'Busca y descubre productos de maquillaje, belleza y cuidado personal. Encuentra las mejores marcas y ofertas.'; ?>">
    
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
                           value="<?php echo htmlspecialchars($searchQuery); ?>"
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
                    <a href="search.php" class="flex items-center px-4 py-3 rounded-xl bg-primary-50 text-primary-600 font-medium">
                        <i class="fas fa-search w-5 mr-3"></i>
                        Búsqueda
                    </a>
                    <a href="catalogo.php" class="flex items-center px-4 py-3 rounded-xl text-gray-700 hover:bg-primary-50 hover:text-primary-600 transition-colors mobile-tap">
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
                    <li class="text-primary-600 font-medium">
                        <?php echo $searchQuery ? 'Resultados para "' . htmlspecialchars($searchQuery) . '"' : 'Búsqueda'; ?>
                    </li>
                </ol>
            </nav>
        </div>
    </section>
    
    <!-- Search Hero Section -->
    <section class="py-12 md:py-20 relative overflow-hidden bg-mesh">
        <!-- Background Elements -->
        <div class="absolute inset-0">
            <div class="absolute top-10 left-10 w-64 h-64 bg-gradient-to-r from-primary-200/30 to-secondary-200/30 rounded-full blur-3xl animate-float"></div>
            <div class="absolute bottom-10 right-10 w-80 h-80 bg-gradient-to-r from-secondary-200/30 to-accent-200/30 rounded-full blur-3xl animate-float" style="animation-delay: 2s;"></div>
        </div>
        
        <div class="container mx-auto px-4 relative z-10">
            <div class="text-center mb-16" data-aos="fade-up">
                <?php if ($searchQuery): ?>
                    <div class="inline-block mb-6">
                        <span class="text-sm font-medium tracking-widest uppercase text-primary-600 bg-primary-50 px-6 py-2 rounded-full">
                            Resultados de Búsqueda
                        </span>
                    </div>
                    <h1 class="text-3xl md:text-4xl lg:text-6xl font-serif font-bold mb-6 gradient-text text-shadow">
                        "<?php echo htmlspecialchars($searchQuery); ?>"
                    </h1>
                    <p class="text-lg md:text-xl lg:text-2xl text-gray-600 max-w-3xl mx-auto mb-8 font-light leading-relaxed">
                        Encontramos <?php echo number_format($totalProducts); ?> productos que coinciden con tu búsqueda
                    </p>
                <?php else: ?>
                    <div class="inline-block mb-6">
                        <span class="text-sm font-medium tracking-widest uppercase text-primary-600 bg-primary-50 px-6 py-2 rounded-full">
                            Búsqueda de Productos
                        </span>
                    </div>
                    <h1 class="text-3xl md:text-4xl lg:text-6xl font-serif font-bold mb-6 gradient-text text-shadow">
                        Encuentra lo que Buscas
                    </h1>
                    <p class="text-lg md:text-xl lg:text-2xl text-gray-600 max-w-3xl mx-auto mb-8 font-light leading-relaxed">
                        Descubre productos de maquillaje, belleza y cuidado personal con nuestros filtros avanzados
                    </p>
                    
                    <!-- Main Search Bar -->
                    <div class="max-w-2xl mx-auto">
                        <form method="GET" action="search.php" class="relative">
                            <input type="text" 
                                   name="q" 
                                   placeholder="¿Qué estás buscando hoy?"
                                   class="w-full px-6 py-4 md:px-8 md:py-6 text-lg border-2 border-primary-200 rounded-2xl focus:outline-none focus:ring-4 focus:ring-primary-500/50 focus:border-primary-500 bg-white/90 backdrop-blur-sm shadow-xl">
                            <button type="submit" 
                                    class="absolute right-2 top-1/2 transform -translate-y-1/2 bg-gradient-to-r from-primary-500 to-secondary-500 text-white px-6 md:px-8 py-3 md:py-4 rounded-xl font-semibold hover:shadow-xl transform hover:scale-105 transition-all duration-300">
                                <i class="fas fa-search mr-2"></i>
                                Buscar
                            </button>
                        </form>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>
