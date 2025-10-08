<?php
// Configuración de sesión segura (debe ir antes de session_start)
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 0); // Cambiar a 1 en HTTPS
ini_set('session.cookie_samesite', 'Strict');

session_start();
require_once 'config/config.php';
require_once 'config/database.php';
require_once 'models/Brand.php';
require_once 'models/Product.php';
require_once 'models/Category.php';
require_once 'models/Settings.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    $brandModel = new Brand($db);
    $productModel = new Product($db);
    $categoryModel = new Category();
    $settingsModel = new Settings();
    
    // Obtener configuraciones de contacto y redes sociales para el header
    $contactSettings = $settingsModel->getContactSettings();
    
    // Get filters from URL
    $searchQuery = isset($_GET['search']) ? trim($_GET['search']) : '';
    $sortBy = isset($_GET['sort']) ? $_GET['sort'] : 'name';
    $filterLetter = isset($_GET['letter']) ? $_GET['letter'] : '';
    $viewMode = isset($_GET['view']) ? $_GET['view'] : 'grid';
    
    // Get all active brands with product counts
    $brandsQuery = "SELECT b.*, COUNT(p.id) as product_count
                    FROM brands b
                    LEFT JOIN products p ON b.id = p.brand_id AND p.status = 'active'
                    WHERE b.is_active = 1";
    
    $params = [];
    
    // Apply search filter
    if (!empty($searchQuery)) {
        $brandsQuery .= " AND (b.name LIKE ? OR b.description LIKE ?)";
        $params[] = "%$searchQuery%";
        $params[] = "%$searchQuery%";
    }
    
    // Apply letter filter
    if (!empty($filterLetter)) {
        $brandsQuery .= " AND b.name LIKE ?";
        $params[] = "$filterLetter%";
    }
    
    $brandsQuery .= " GROUP BY b.id";
    
    // Apply sorting
    switch ($sortBy) {
        case 'name_desc':
            $brandsQuery .= " ORDER BY b.name DESC";
            break;
        case 'products_desc':
            $brandsQuery .= " ORDER BY product_count DESC";
            break;
        case 'products_asc':
            $brandsQuery .= " ORDER BY product_count ASC";
            break;
        case 'newest':
            $brandsQuery .= " ORDER BY b.created_at DESC";
            break;
        default:
            $brandsQuery .= " ORDER BY b.name ASC";
    }
    
    $brandsStmt = $db->prepare($brandsQuery);
    $brandsStmt->execute($params);
    $brands = $brandsStmt->fetchAll();
    
    // Get alphabet letters for filter
    $lettersQuery = "SELECT DISTINCT UPPER(LEFT(name, 1)) as letter
                     FROM brands
                     WHERE is_active = 1
                     ORDER BY letter";
    $lettersStmt = $db->prepare($lettersQuery);
    $lettersStmt->execute();
    $availableLetters = $lettersStmt->fetchAll(PDO::FETCH_COLUMN);
    
    // Get brand statistics
    $statsQuery = "SELECT
                    COUNT(DISTINCT b.id) as total_brands,
                    COUNT(DISTINCT p.id) as total_products,
                    ROUND(AVG(product_counts.product_count), 1) as avg_products_per_brand
                   FROM brands b
                   LEFT JOIN products p ON b.id = p.brand_id AND p.status = 'active'
                   LEFT JOIN (
                       SELECT brand_id, COUNT(*) as product_count
                       FROM products
                       WHERE status = 'active'
                       GROUP BY brand_id
                   ) product_counts ON b.id = product_counts.brand_id
                   WHERE b.is_active = 1";
    $statsStmt = $db->prepare($statsQuery);
    $statsStmt->execute();
    $stats = $statsStmt->fetch();
    
} catch (Exception $e) {
    error_log("Error in marcas.php: " . $e->getMessage());
    $brands = [];
    $availableLetters = [];
    $stats = ['total_brands' => 0, 'total_products' => 0, 'avg_products_per_brand' => 0];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Marcas Destacadas - ElectroShop</title>
    <meta name="description" content="Descubre las mejores marcas de tecnología, innovación y productos electrónicos. Encuentra tus dispositivos favoritos de marcas reconocidas mundialmente.">
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Playfair+Display:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        'sans': ['Inter', 'system-ui', 'sans-serif'],
                        'serif': ['Playfair Display', 'Georgia', 'serif'],
                    },
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
                            50: '#fdf8f6',
                            100: '#f2e8e5',
                            200: '#eaddd7',
                            300: '#e0cec7',
                            400: '#d2bab0',
                            500: '#bfa094',
                            600: '#a18072',
                            700: '#977669',
                            800: '#846358',
                            900: '#43302b',
                            rose: '#f4e6e1',
                            gold: '#f7f1e8',
                            pearl: '#fefdfb',
                            bronze: '#d2bab0',
                            champagne: '#f9e6d3'
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
                            900: '#881337',
                        },
                        gold: {
                            50: '#fffbeb',
                            100: '#fef3c7',
                            200: '#fde68a',
                            300: '#fcd34d',
                            400: '#fbbf24',
                            500: '#f59e0b',
                            600: '#d97706',
                            700: '#b45309',
                            800: '#92400e',
                            900: '#78350f',
                        }
                    },
                    animation: {
                        'glow': 'glow 2s ease-in-out infinite alternate',
                        'float': 'float 6s ease-in-out infinite',
                        'shimmer': 'shimmer 2s linear infinite',
                        'pulse-slow': 'pulse 3s cubic-bezier(0.4, 0, 0.6, 1) infinite',
                        'bounce-slow': 'bounce 3s infinite',
                        'gradient': 'gradient 8s ease infinite',
                        'sparkle': 'sparkle 1.5s ease-in-out infinite',
                    },
                    keyframes: {
                        glow: {
                            '0%': { 
                                boxShadow: '0 0 20px rgba(176, 141, 128, 0.3), 0 0 40px rgba(176, 141, 128, 0.1)',
                                transform: 'scale(1)'
                            },
                            '100%': { 
                                boxShadow: '0 0 30px rgba(196, 165, 117, 0.4), 0 0 60px rgba(196, 165, 117, 0.2)',
                                transform: 'scale(1.02)'
                            }
                        },
                        float: {
                            '0%, 100%': { transform: 'translateY(0px) rotate(0deg)' },
                            '33%': { transform: 'translateY(-10px) rotate(1deg)' },
                            '66%': { transform: 'translateY(-5px) rotate(-1deg)' }
                        },
                        shimmer: {
                            '0%': { transform: 'translateX(-100%)' },
                            '100%': { transform: 'translateX(100%)' }
                        },
                        gradient: {
                            '0%, 100%': { backgroundPosition: '0% 50%' },
                            '50%': { backgroundPosition: '100% 50%' }
                        },
                        sparkle: {
                            '0%, 100%': { opacity: '1', transform: 'scale(1)' },
                            '50%': { opacity: '0.4', transform: 'scale(0.8)' }
                        }
                    },
                    backgroundImage: {
                        'luxury-gradient': 'linear-gradient(135deg, #f43f5e 0%, #f59e0b 50%, #ec4899 100%)',
                        'luxury-gradient-2': 'linear-gradient(135deg, #fbbf24 0%, #f43f5e 50%, #ec4899 100%)',
                        'glass-gradient': 'linear-gradient(135deg, rgba(255, 255, 255, 0.4) 0%, rgba(255, 255, 255, 0.1) 100%)',
                    },
                    backdropBlur: {
                        'xs': '2px',
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
        :root {
            --luxury-gradient: linear-gradient(135deg, #b08d80, #c4a575);
            --luxury-gradient-hover: linear-gradient(135deg, #a07870, #b5966a);
            --luxury-shadow: rgba(176, 141, 128, 0.25);
            --glass-effect: rgba(255, 255, 255, 0.25);
            --glass-border: rgba(255, 255, 255, 0.18);
        }

        .luxury-gradient {
            background: var(--luxury-gradient);
        }

        .luxury-text {
            background: var(--luxury-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            background-size: 200% 200%;
            animation: gradient 8s ease infinite;
        }

        .glass-morphism {
            background: rgba(255, 255, 255, 0.25);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.18);
        }

        .glass-morphism-dark {
            background: rgba(0, 0, 0, 0.25);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .luxury-shadow {
            box-shadow: 
                0 25px 50px -12px rgba(176, 141, 128, 0.25),
                0 0 0 1px rgba(255, 255, 255, 0.05),
                inset 0 1px 0 rgba(255, 255, 255, 0.1);
        }

        .luxury-shadow-lg {
            box-shadow: 
                0 35px 60px -12px rgba(176, 141, 128, 0.35),
                0 0 0 1px rgba(255, 255, 255, 0.05),
                inset 0 1px 0 rgba(255, 255, 255, 0.1);
        }

        .hover-lift {
            transition: all 0.6s cubic-bezier(0.23, 1, 0.32, 1);
        }

        .hover-lift:hover {
            transform: translateY(-12px) scale(1.02);
            box-shadow: 
                0 40px 80px -12px rgba(176, 141, 128, 0.35),
                0 0 0 1px rgba(255, 255, 255, 0.1),
                inset 0 1px 0 rgba(255, 255, 255, 0.2);
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
            transition: left 0.8s ease;
        }

        .shimmer-effect:hover::before {
            left: 100%;
        }

        .brand-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            transition: all 0.6s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            position: relative;
            overflow: visible;
        }

        .brand-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--luxury-gradient);
            background-size: 200% 100%;
            animation: shimmer-gradient 3s ease-in-out infinite;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .brand-card:hover::before {
            opacity: 1;
        }

        .brand-card:hover {
            transform: translateY(-12px) scale(1.03);
            box-shadow: 0 30px 60px rgba(176, 141, 128, 0.25);
            border-color: rgba(176, 141, 128, 0.3);
        }

        @keyframes shimmer-gradient {
            0% { background-position: 200% 0; }
            100% { background-position: -200% 0; }
        }

        .filter-button {
            position: relative;
            overflow: hidden;
            font-weight: 600;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }

        .filter-button::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
            transition: left 0.6s ease;
        }

        .filter-button:hover::before {
            left: 100%;
        }

        .filter-button.active {
            background: var(--luxury-gradient);
            color: white;
            transform: scale(1.05);
            box-shadow: 0 8px 25px rgba(176, 141, 128, 0.3);
        }

        .alphabet-filter {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(45px, 1fr));
            gap: 0.75rem;
        }

        .search-highlight {
            background: linear-gradient(135deg, rgba(176, 141, 128, 0.2) 0%, rgba(196, 165, 117, 0.2) 100%);
            padding: 2px 6px;
            border-radius: 6px;
            font-weight: 600;
        }

        .floating-element {
            position: absolute;
            border-radius: 50%;
            background: linear-gradient(135deg, rgba(176, 141, 128, 0.1), rgba(196, 165, 117, 0.1));
            animation: float 8s ease-in-out infinite;
            pointer-events: none;
        }

        .floating-element:nth-child(1) { animation-delay: 0s; }
        .floating-element:nth-child(2) { animation-delay: -2s; }
        .floating-element:nth-child(3) { animation-delay: -4s; }
        .floating-element:nth-child(4) { animation-delay: -6s; }

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

        .masonry-grid {
            column-count: 1;
            column-gap: 2rem;
            column-fill: balance;
        }

        @media (min-width: 640px) {
            .masonry-grid { column-count: 2; }
        }

        @media (min-width: 768px) {
            .masonry-grid { column-count: 3; }
        }

        @media (min-width: 1024px) {
            .masonry-grid { column-count: 4; }
        }

        .masonry-item {
            break-inside: avoid;
            page-break-inside: avoid;
            margin-bottom: 2rem;
            display: inline-block;
            width: 100%;
        }

        .brand-logo {
            transition: all 0.4s ease;
            filter: grayscale(0.1) brightness(1.1);
            max-width: 100%;
            max-height: 100%;
            width: auto;
            height: auto;
            object-fit: contain;
        }

        .brand-logo:hover {
            filter: grayscale(0) brightness(1.2);
            transform: scale(1.05);
        }

        .stat-card {
            position: relative;
            overflow: hidden;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, rgba(176, 141, 128, 0.05), rgba(196, 165, 117, 0.05));
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .stat-card:hover::before {
            opacity: 1;
        }

        .view-toggle {
            transition: all 0.3s ease;
        }

        .view-toggle.active {
            background: var(--luxury-gradient);
            color: white;
            transform: scale(1.1);
            box-shadow: 0 4px 15px rgba(176, 141, 128, 0.3);
        }

        /* Custom Scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
        }

        ::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 10px;
        }

        ::-webkit-scrollbar-thumb {
            background: var(--luxury-gradient);
            border-radius: 10px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: var(--luxury-gradient-hover);
        }

        /* Loading States */
        .loading-spinner {
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        /* Enhanced Responsive Design */
        @media (max-width: 768px) {
            .brand-card {
                margin-bottom: 1.5rem;
                padding: 1rem;
            }
            
            .brand-card:hover {
                transform: translateY(-4px) scale(1.01);
            }
            
            .filter-button {
                min-width: 45px;
                height: 45px;
                font-size: 14px;
            }

            .alphabet-filter {
                grid-template-columns: repeat(auto-fit, minmax(40px, 1fr));
                gap: 0.5rem;
            }

            .brand-logo {
                max-width: 90%;
                max-height: 90%;
                object-fit: contain;
            }

            .masonry-grid {
                column-count: 1;
                column-gap: 1rem;
            }

            .masonry-item {
                margin-bottom: 1.5rem;
            }
            
            .brand-card {
                margin: 0 auto 1.5rem auto;
                width: 100%;
                max-width: 320px;
            }
            }
        }

        /* Accessibility Improvements */
        .focus-visible:focus {
            outline: 2px solid #f43f5e;
            outline-offset: 2px;
        }

        /* Print Styles */
        @media print {
            .floating-element,
            .shimmer-effect::before,
            .brand-card::before {
                display: none;
            }
        }
    </style>
</head>
<body class="font-sans bg-gradient-to-br from-luxury-50 via-white to-rose-50 min-h-screen">
    <!-- Header -->
    <?php include 'includes/header.php'; ?>
    
    <!-- Hero Section -->
    <div class="relative pt-32 pb-24 overflow-hidden">
        <!-- Background with luxury gradient -->
        <div class="absolute inset-0">
            <div class="absolute inset-0 bg-gradient-to-br from-luxury-50 via-white to-rose-50/50"></div>
            <div class="absolute inset-0 bg-gradient-to-tr from-gold-50/30 via-transparent to-rose-100/30"></div>
        </div>
        
        <!-- Floating Elements -->
        <div class="floating-element absolute top-20 left-10 w-32 h-32 opacity-60"></div>
        <div class="floating-element absolute top-40 right-20 w-24 h-24 opacity-40" style="animation-delay: -2s;"></div>
        <div class="floating-element absolute bottom-20 left-1/4 w-20 h-20 opacity-50" style="animation-delay: -4s;"></div>
        <div class="floating-element absolute top-60 right-1/3 w-16 h-16 opacity-30" style="animation-delay: -6s;"></div>
        
        <div class="container mx-auto px-4 relative">
            <div class="text-center max-w-5xl mx-auto" data-aos="fade-up">
                <!-- Badge -->
                <div class="inline-block mb-8" data-aos="fade-up" data-aos-delay="100">
                    <span class="luxury-gradient text-white px-8 py-3 rounded-full text-sm font-semibold tracking-wider uppercase shadow-lg">
                        <i class="fas fa-crown mr-2"></i>
                        Marcas Premium Exclusivas
                    </span>
                </div>
                
                <!-- Title -->
                <h1 class="text-6xl lg:text-8xl font-serif font-bold leading-tight mb-8" data-aos="fade-up" data-aos-delay="200">
                    <span class="luxury-text">Marcas</span>
                    <br>
                    <span class="text-gray-900">de</span>
                    <span class="luxury-text">Tecnología</span>
                    <br>
                    <span class="text-gray-900">Exclusivas</span>
                </h1>
                
                <!-- Subtitle -->
                <p class="text-xl lg:text-2xl text-gray-600 leading-relaxed font-light max-w-4xl mx-auto mb-16" data-aos="fade-up" data-aos-delay="300">
                    Descubre las marcas más innovadoras del mundo de la tecnología y el ecommerce. 
                    Desde los últimos lanzamientos en gadgets y electrónica hasta los clásicos de marcas reconocidas mundialmente.
                </p>
                
                <!-- Statistics Cards -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-8 mb-16" data-aos="fade-up" data-aos-delay="400">
                    <div class="stat-card glass-morphism rounded-3xl p-8 luxury-shadow hover-lift shimmer-effect">
                        <div class="w-20 h-20 luxury-gradient rounded-2xl flex items-center justify-center mx-auto mb-6 animate-pulse-slow">
                            <i class="fas fa-crown text-white text-3xl"></i>
                        </div>
                        <div class="text-5xl font-bold luxury-text mb-3"><?php echo $stats['total_brands']; ?></div>
                        <div class="text-gray-700 font-semibold text-lg">Marcas Exclusivas</div>
                        <div class="text-gray-500 text-sm mt-2">Cuidadosamente seleccionadas</div>
                    </div>
                    <div class="stat-card glass-morphism rounded-3xl p-8 luxury-shadow hover-lift shimmer-effect">
                        <div class="w-20 h-20 luxury-gradient rounded-2xl flex items-center justify-center mx-auto mb-6 animate-pulse-slow" style="animation-delay: 0.5s;">
                            <i class="fas fa-shopping-bag text-white text-3xl"></i>
                        </div>
                        <div class="text-5xl font-bold luxury-text mb-3"><?php echo number_format($stats['total_products']); ?></div>
                        <div class="text-gray-700 font-semibold text-lg">Productos Disponibles</div>
                        <div class="text-gray-500 text-sm mt-2">En stock y listos para ti</div>
                    </div>
                    <div class="stat-card glass-morphism rounded-3xl p-8 luxury-shadow hover-lift shimmer-effect">
                        <div class="w-20 h-20 luxury-gradient rounded-2xl flex items-center justify-center mx-auto mb-6 animate-pulse-slow" style="animation-delay: 1s;">
                            <i class="fas fa-chart-line text-white text-3xl"></i>
                        </div>
                        <div class="text-5xl font-bold luxury-text mb-3"><?php echo $stats['avg_products_per_brand']; ?></div>
                        <div class="text-gray-700 font-semibold text-lg">Productos por Marca</div>
                        <div class="text-gray-500 text-sm mt-2">Variedad garantizada</div>
                    </div>
                </div>
                
                <!-- CTA Buttons -->
                <div class="flex flex-col sm:flex-row gap-6 justify-center" data-aos="fade-up" data-aos-delay="500">
                    <a href="#brands-section" class="group relative overflow-hidden luxury-gradient text-white px-12 py-6 rounded-2xl font-bold text-xl luxury-shadow-lg transform hover:scale-105 transition-all duration-500 text-center">
                        <span class="relative z-10 flex items-center justify-center">
                            <i class="fas fa-sparkles mr-3 animate-sparkle"></i>
                            Explorar Marcas
                            <i class="fas fa-arrow-down ml-3 group-hover:translate-y-1 transition-transform"></i>
                        </span>
                        <div class="absolute inset-0 bg-gradient-to-r from-gold-500 to-rose-500 opacity-0 group-hover:opacity-100 transition-opacity duration-500"></div>
                    </a>
                    <a href="catalogo.php" class="group border-3 border-rose-500 text-rose-500 px-12 py-6 rounded-2xl font-bold text-xl hover:bg-rose-500 hover:text-white transition-all duration-500 text-center relative overflow-hidden">
                        <span class="relative z-10 flex items-center justify-center">
                            <i class="fas fa-shopping-cart mr-3"></i>
                            Ver Todos los Productos
                            <i class="fas fa-arrow-right ml-3 group-hover:translate-x-1 transition-transform"></i>
                        </span>
                        <div class="absolute inset-0 bg-rose-500 transform scale-x-0 group-hover:scale-x-100 transition-transform duration-500 origin-left"></div>
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Filters Section -->
    <div class="container mx-auto px-4 mb-20" id="brands-section" data-aos="fade-up">
        <div class="glass-morphism rounded-3xl p-10 luxury-shadow shimmer-effect">
            <form method="GET" action="" id="filtersForm" class="space-y-10">
                <!-- Search and View Controls -->
                <div class="flex flex-col lg:flex-row gap-8 items-center justify-between">
                    <!-- Search Bar -->
                    <div class="relative flex-1 max-w-3xl group">
                        <div class="absolute inset-y-0 left-0 pl-8 flex items-center pointer-events-none">
                            <i class="fas fa-search text-gray-400 text-2xl group-focus-within:text-rose-500 transition-colors duration-300"></i>
                        </div>
                        <input type="text"
                               id="brand-search-input"
                               name="search"
                               value="<?php echo htmlspecialchars($searchQuery); ?>"
                               placeholder="Buscar marcas por nombre o descripción..."
                               class="w-full pl-20 pr-8 py-6 bg-white/90 backdrop-blur-sm border-2 border-gray-200 rounded-2xl focus:outline-none focus:ring-4 focus:ring-rose-500/20 focus:border-rose-500 transition-all duration-300 text-lg font-medium hover:border-rose-300 shadow-lg"
                               autocomplete="off">
                        
                        <!-- Search Results Dropdown -->
                        <div id="brand-search-results" class="hidden absolute top-full left-0 right-0 bg-white rounded-2xl shadow-xl border border-gray-200 mt-2 max-h-96 overflow-auto z-50">
                            <div id="brand-search-results-content" class="p-4">
                                <!-- Los resultados se cargarán aquí -->
                            </div>
                        </div>
                    </div>
                    
                    <!-- View Mode Toggle -->
                    <div class="flex items-center space-x-6">
                        <span class="text-gray-700 font-bold text-lg">Vista:</span>
                        <div class="flex bg-white/80 backdrop-blur-sm rounded-2xl p-2 border-2 border-gray-200 shadow-lg">
                            <button type="button"
                                    onclick="setViewMode('grid')"
                                    class="px-6 py-3 rounded-xl transition-all duration-300 view-toggle <?php echo $viewMode === 'grid' ? 'active' : 'hover:bg-white/70 text-gray-600'; ?>">
                                <i class="fas fa-th-large text-xl"></i>
                            </button>
                            <button type="button"
                                    onclick="setViewMode('list')"
                                    class="px-6 py-3 rounded-xl transition-all duration-300 view-toggle <?php echo $viewMode === 'list' ? 'active' : 'hover:bg-white/70 text-gray-600'; ?>">
                                <i class="fas fa-list text-xl"></i>
                            </button>
                            <button type="button"
                                    onclick="setViewMode('masonry')"
                                    class="px-6 py-3 rounded-xl transition-all duration-300 view-toggle <?php echo $viewMode === 'masonry' ? 'active' : 'hover:bg-white/70 text-gray-600'; ?>">
                                <i class="fas fa-th text-xl"></i>
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- Alphabet Filter -->
                <div class="space-y-8">
                    <div class="flex items-center space-x-6">
                        <div class="w-16 h-16 luxury-gradient rounded-2xl flex items-center justify-center shadow-lg">
                            <i class="fas fa-filter text-white text-2xl"></i>
                        </div>
                        <h3 class="text-2xl font-bold text-gray-800">Filtrar por Letra</h3>
                    </div>
                    <div class="alphabet-filter">
                        <button type="button"
                                onclick="setLetter('')"
                                class="filter-button px-5 py-4 rounded-2xl text-sm font-bold transition-all duration-300 <?php echo empty($filterLetter) ? 'active' : 'bg-white/90 hover:bg-white border-2 border-gray-200 text-gray-700 hover:border-rose-300 shadow-md'; ?>">
                            Todas
                        </button>
                        <?php
                        for ($i = ord('A'); $i <= ord('Z'); $i++) {
                            $letter = chr($i);
                            $isActive = $filterLetter === $letter;
                            $isAvailable = in_array($letter, $availableLetters);
                        ?>
                        <button type="button"
                                onclick="setLetter('<?php echo $letter; ?>')"
                                class="filter-button px-5 py-4 rounded-2xl text-sm font-bold transition-all duration-300 <?php echo $isActive ? 'active' : ($isAvailable ? 'bg-white/90 hover:bg-white border-2 border-gray-200 text-gray-700 hover:border-rose-300 shadow-md' : 'bg-gray-100 text-gray-400 cursor-not-allowed border-2 border-gray-100'); ?>"
                                <?php echo !$isAvailable ? 'disabled' : ''; ?>>
                            <?php echo $letter; ?>
                        </button>
                        <?php } ?>
                    </div>
                </div>
                
                <!-- Sort Options -->
                <div class="flex flex-wrap gap-6 items-center">
                    <div class="flex items-center space-x-4">
                        <div class="w-14 h-14 luxury-gradient rounded-2xl flex items-center justify-center shadow-lg">
                            <i class="fas fa-sort text-white text-xl"></i>
                        </div>
                        <span class="text-2xl font-bold text-gray-800">Ordenar por:</span>
                    </div>
                    <select name="sort"
                            onchange="document.getElementById('filtersForm').submit()"
                            class="px-8 py-4 bg-white/90 backdrop-blur-sm border-2 border-gray-200 rounded-2xl focus:outline-none focus:ring-4 focus:ring-rose-500/20 focus:border-rose-500 transition-all duration-300 font-bold text-lg hover:border-rose-300 shadow-lg">
                        <option value="name" <?php echo $sortBy === 'name' ? 'selected' : ''; ?>>Nombre A-Z</option>
                        <option value="name_desc" <?php echo $sortBy === 'name_desc' ? 'selected' : ''; ?>>Nombre Z-A</option>
                        <option value="products_desc" <?php echo $sortBy === 'products_desc' ? 'selected' : ''; ?>>Más Productos</option>
                        <option value="products_asc" <?php echo $sortBy === 'products_asc' ? 'selected' : ''; ?>>Menos Productos</option>
                        <option value="newest" <?php echo $sortBy === 'newest' ? 'selected' : ''; ?>>Más Recientes</option>
                    </select>
                </div>
                
                <input type="hidden" name="view" value="<?php echo htmlspecialchars($viewMode); ?>" id="viewModeInput">
                <input type="hidden" name="letter" value="<?php echo htmlspecialchars($filterLetter); ?>" id="letterInput">
            </form>
        </div>
    </div>
    
    <!-- Results Info -->
    <div class="container mx-auto px-4 mb-12" data-aos="fade-up">
        <div class="flex flex-col sm:flex-row justify-between items-center glass-morphism rounded-2xl p-8 shadow-lg">
            <div class="text-xl text-gray-700 font-medium">
                <span class="font-bold luxury-text text-2xl"><?php echo count($brands); ?></span> marcas encontradas
                <?php if (!empty($searchQuery)): ?>
                para "<span class="search-highlight font-bold"><?php echo htmlspecialchars($searchQuery); ?></span>"
                <?php endif; ?>
                <?php if (!empty($filterLetter)): ?>
                que inician con "<span class="search-highlight font-bold"><?php echo htmlspecialchars($filterLetter); ?></span>"
                <?php endif; ?>
            </div>
            
            <?php if (!empty($searchQuery) || !empty($filterLetter)): ?>
            <a href="marcas.php" class="mt-6 sm:mt-0 px-8 py-3 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-2xl transition-all duration-300 font-semibold shadow-md hover:shadow-lg">
                <i class="fas fa-times mr-2"></i>Limpiar Filtros
            </a>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Brands Grid/List -->
    <div class="container mx-auto px-4 mb-24">
        <?php if (!empty($brands)): ?>
        
        <!-- Grid View -->
        <div id="grid-view" class="<?php echo $viewMode === 'grid' ? 'block' : 'hidden'; ?>">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-10" data-aos="fade-up">
                <?php foreach ($brands as $index => $brand): ?>
                <div class="brand-card group rounded-3xl overflow-hidden luxury-shadow hover-lift" data-aos="fade-up" data-aos-delay="<?php echo ($index % 8) * 100; ?>">
                    <!-- Brand Logo -->
                    <div class="relative aspect-square bg-gradient-to-br from-luxury-50 to-rose-50 p-6 md:p-10 flex items-center justify-center">
                        <?php if (!empty($brand['logo'])): ?>
                        <img src="<?php echo htmlspecialchars($brand['logo']); ?>"
                             alt="<?php echo htmlspecialchars($brand['name']); ?>"
                             class="brand-logo w-full h-full object-contain">
                        <?php else: ?>
                        <div class="w-20 h-20 md:w-28 md:h-28 luxury-gradient rounded-full flex items-center justify-center shadow-lg animate-pulse-slow">
                            <span class="text-white text-2xl md:text-3xl font-bold">
                                <?php echo strtoupper(substr($brand['name'], 0, 1)); ?>
                            </span>
                        </div>
                        <?php endif; ?>
                        
                        <!-- Product Count Badge -->
                        <div class="absolute top-3 right-3 md:top-6 md:right-6 luxury-gradient text-white px-2 py-1 md:px-4 md:py-2 rounded-full text-xs md:text-sm font-bold shadow-lg animate-bounce-slow">
                            <?php echo $brand['product_count']; ?> productos
                        </div>
                    </div>
                    
                    <!-- Brand Info -->
                    <div class="p-8">
                        <h3 class="text-2xl font-bold text-gray-800 mb-4 group-hover:text-rose-600 transition-colors">
                            <?php echo htmlspecialchars($brand['name']); ?>
                        </h3>
                        
                        <?php if (!empty($brand['description'])): ?>
                        <p class="text-gray-600 mb-6 line-clamp-3 leading-relaxed">
                            <?php echo htmlspecialchars($brand['description']); ?>
                        </p>
                        <?php endif; ?>
                        
                        <!-- Action Button -->
                        <a href="catalogo.php?brand=<?php echo $brand['id']; ?>"
                           class="inline-flex items-center w-full justify-center px-8 py-4 luxury-gradient text-white rounded-2xl font-bold hover:shadow-2xl transform hover:scale-105 transition-all duration-500 group">
                            <span>Ver Productos</span>
                            <i class="fas fa-arrow-right ml-3 group-hover:translate-x-2 transition-transform"></i>
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <!-- List View -->
        <div id="list-view" class="<?php echo $viewMode === 'list' ? 'block' : 'hidden'; ?>">
            <div class="space-y-8" data-aos="fade-up">
                <?php foreach ($brands as $index => $brand): ?>
                <div class="brand-card group rounded-3xl overflow-hidden luxury-shadow hover-lift" data-aos="fade-right" data-aos-delay="<?php echo ($index % 5) * 100; ?>">
                    <div class="flex flex-col sm:flex-row">
                        <!-- Brand Logo -->
                        <div class="relative w-full sm:w-64 h-48 sm:h-64 bg-gradient-to-br from-luxury-50 to-rose-50 p-6 md:p-10 flex items-center justify-center">
                            <?php if (!empty($brand['logo'])): ?>
                            <img src="<?php echo htmlspecialchars($brand['logo']); ?>"
                                 alt="<?php echo htmlspecialchars($brand['name']); ?>"
                                 class="brand-logo w-full h-full object-contain">
                            <?php else: ?>
                            <div class="w-16 h-16 md:w-24 md:h-24 luxury-gradient rounded-full flex items-center justify-center shadow-lg animate-pulse-slow">
                                <span class="text-white text-xl md:text-2xl font-bold">
                                    <?php echo strtoupper(substr($brand['name'], 0, 1)); ?>
                                </span>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Brand Info -->
                        <div class="flex-1 p-10 flex flex-col justify-between">
                            <div>
                                <div class="flex items-start justify-between mb-6">
                                    <h3 class="text-3xl font-bold text-gray-800 group-hover:text-rose-600 transition-colors">
                                        <?php echo htmlspecialchars($brand['name']); ?>
                                    </h3>
                                    <div class="luxury-gradient text-white px-6 py-3 rounded-full text-sm font-bold shadow-lg animate-bounce-slow">
                                        <?php echo $brand['product_count']; ?> productos
                                    </div>
                                </div>
                                
                                <?php if (!empty($brand['description'])): ?>
                                <p class="text-gray-600 mb-8 text-xl leading-relaxed">
                                    <?php echo htmlspecialchars($brand['description']); ?>
                                </p>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Action Button -->
                            <div class="flex justify-start">
                                <a href="catalogo.php?brand=<?php echo $brand['id']; ?>"
                                   class="inline-flex items-center px-10 py-5 luxury-gradient text-white rounded-2xl font-bold hover:shadow-2xl transform hover:scale-105 transition-all duration-500 group">
                                    <span>Ver Productos</span>
                                    <i class="fas fa-arrow-right ml-4 group-hover:translate-x-2 transition-transform"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <!-- Masonry View -->
        <div id="masonry-view" class="<?php echo $viewMode === 'masonry' ? 'block' : 'hidden'; ?>">
            <div class="masonry-grid" data-aos="fade-up">
                <?php foreach ($brands as $index => $brand): ?>
                <div class="masonry-item" data-aos="zoom-in" data-aos-delay="<?php echo ($index % 10) * 100; ?>">
                    <div class="brand-card group rounded-3xl overflow-hidden luxury-shadow hover-lift">
                        <!-- Brand Logo -->
                        <div class="relative aspect-video bg-gradient-to-br from-luxury-50 to-rose-50 p-8 flex items-center justify-center">
                            <?php if (!empty($brand['logo'])): ?>
                            <img src="<?php echo htmlspecialchars($brand['logo']); ?>"
                                 alt="<?php echo htmlspecialchars($brand['name']); ?>"
                                 class="brand-logo max-w-full max-h-full object-contain">
                            <?php else: ?>
                            <div class="w-20 h-20 luxury-gradient rounded-full flex items-center justify-center shadow-lg animate-pulse-slow">
                                <span class="text-white text-xl font-bold">
                                    <?php echo strtoupper(substr($brand['name'], 0, 1)); ?>
                                </span>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Brand Info -->
                        <div class="p-8">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="text-xl font-bold text-gray-800 group-hover:text-rose-600 transition-colors">
                                    <?php echo htmlspecialchars($brand['name']); ?>
                                </h3>
                                <span class="luxury-gradient text-white px-3 py-1 rounded-full text-xs font-bold shadow-lg animate-bounce-slow">
                                    <?php echo $brand['product_count']; ?>
                                </span>
                            </div>
                            
                            <?php if (!empty($brand['description'])): ?>
                            <p class="text-gray-600 mb-6 text-sm line-clamp-2 leading-relaxed">
                                <?php echo htmlspecialchars($brand['description']); ?>
                            </p>
                            <?php endif; ?>
                            
                            <!-- Action Button -->
                            <a href="catalogo.php?brand=<?php echo $brand['id']; ?>"
                               class="inline-flex items-center w-full justify-center px-6 py-3 luxury-gradient text-white rounded-2xl font-bold hover:shadow-lg transform hover:scale-105 transition-all duration-300 text-sm group">
                                <span>Ver Productos</span>
                                <i class="fas fa-arrow-right ml-2 text-xs group-hover:translate-x-1 transition-transform"></i>
                            </a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <?php else: ?>
        <!-- Empty State -->
        <div class="text-center py-24" data-aos="fade-up">
            <div class="w-40 h-40 mx-auto mb-10 rounded-full flex items-center justify-center luxury-gradient shadow-2xl animate-pulse-slow">
                <i class="fas fa-search text-5xl text-white"></i>
            </div>
            <h3 class="text-4xl font-serif font-bold mb-6 text-gray-800">
                No se encontraron marcas
            </h3>
            <p class="text-2xl mb-12 max-w-lg mx-auto text-gray-600 leading-relaxed">
                Intenta ajustar tus filtros de búsqueda para encontrar las marcas que buscas.
            </p>
            <a href="marcas.php" class="inline-flex items-center px-12 py-6 rounded-2xl font-bold text-white transition-all duration-500 luxury-gradient hover:shadow-2xl transform hover:scale-105 text-xl">
                <i class="fas fa-sparkles mr-3 animate-sparkle"></i>
                <span>Ver Todas las Marcas</span>
                <i class="fas fa-arrow-right ml-3"></i>
            </a>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Footer -->
    <?php include 'includes/footer.php'; ?>
    
    <!-- Scripts -->
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    
    <script>
        // Initialize AOS
        AOS.init({
            duration: 1200,
            easing: 'ease-out-cubic',
            once: true,
            offset: 100,
            delay: 100
        });
        
        // View mode functions
        function setViewMode(mode) {
            document.getElementById('viewModeInput').value = mode;
            
            // Hide all views
            document.getElementById('grid-view').classList.add('hidden');
            document.getElementById('list-view').classList.add('hidden');
            document.getElementById('masonry-view').classList.add('hidden');
            
            // Show selected view
            document.getElementById(mode + '-view').classList.remove('hidden');
            
            // Update toggle buttons
            document.querySelectorAll('.view-toggle').forEach(btn => {
                btn.classList.remove('active');
            });
            
            event.target.classList.add('active');
            
            // Update URL without page reload
            const url = new URL(window.location);
            url.searchParams.set('view', mode);
            window.history.replaceState({}, '', url);
            
            // Re-initialize AOS for new elements
            AOS.refresh();
        }
        
        // Letter filter function
        function setLetter(letter) {
            document.getElementById('letterInput').value = letter;
            document.getElementById('filtersForm').submit();
        }
        
        // Real-time AJAX search functionality
        let searchTimeout;
        const searchInput = document.getElementById('brand-search-input');
        const searchResults = document.getElementById('brand-search-results');
        const searchResultsContent = document.getElementById('brand-search-results-content');

        if (searchInput && searchResults && searchResultsContent) {
            // Real-time search functionality
            searchInput.addEventListener('input', function() {
                const query = this.value.trim();
                
                // Clear previous timeout
                clearTimeout(searchTimeout);
                
                // AJAX search for quick preview
                if (query.length >= 2) {
                    searchTimeout = setTimeout(() => {
                        performBrandSearch(query);
                    }, 300); // 300ms debounce
                } else if (query.length === 0) {
                    hideSearchResults();
                }
            });

            // Hide results when input loses focus (with delay to allow clicking results)
            searchInput.addEventListener('blur', function() {
                setTimeout(() => {
                    hideSearchResults();
                }, 250);
            });

            // Show results when input gains focus and has content
            searchInput.addEventListener('focus', function() {
                if (this.value.trim().length >= 2) {
                    searchResults.classList.remove('hidden');
                }
            });

            // Handle enter key
            searchInput.addEventListener('keydown', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    hideSearchResults();
                    document.getElementById('filtersForm').submit();
                }
            });
        }

        function performBrandSearch(query) {
            if (query.length < 2) {
                hideSearchResults();
                return;
            }
            
            showSearchLoading();
            
            // Search specifically for brands
            fetch(`api/search.php?q=${encodeURIComponent(query)}&type=brands&limit=8`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.text();
                })
                .then(text => {
                    try {
                        const data = JSON.parse(text);
                        displayBrandSearchResults(data, query);
                    } catch (e) {
                        console.error('JSON parse error:', e);
                        console.error('Response text:', text);
                        showBrandSearchError();
                    }
                })
                .catch(error => {
                    console.error('Error en búsqueda de marcas:', error);
                    showBrandSearchError();
                });
        }

        function showSearchLoading() {
            const searchResults = document.getElementById('brand-search-results');
            const searchResultsContent = document.getElementById('brand-search-results-content');
            
            if (searchResultsContent && searchResults) {
                searchResultsContent.innerHTML = `
                    <div class="flex items-center justify-center py-4">
                        <div class="animate-spin rounded-full h-6 w-6 border-b-2 border-rose-500"></div>
                        <span class="ml-2 text-gray-600">Buscando marcas...</span>
                    </div>
                `;
                searchResults.classList.remove('hidden');
            }
        }

        function showBrandSearchError() {
            const searchResults = document.getElementById('brand-search-results');
            const searchResultsContent = document.getElementById('brand-search-results-content');
            
            if (searchResultsContent && searchResults) {
                searchResultsContent.innerHTML = `
                    <div class="text-center py-4 text-gray-500">
                        <i class="fas fa-exclamation-triangle text-2xl mb-2"></i>
                        <p>Error en la búsqueda. Inténtalo de nuevo.</p>
                    </div>
                `;
                searchResults.classList.remove('hidden');
            }
        }

        function displayBrandSearchResults(data, query) {
            const searchResults = document.getElementById('brand-search-results');
            const searchResultsContent = document.getElementById('brand-search-results-content');
            
            if (!searchResultsContent || !searchResults) {
                console.error('Search elements not found');
                return;
            }
            
            if ((!data.brands || data.brands.length === 0) && 
                (!data.products || data.products.length === 0)) {
                searchResultsContent.innerHTML = `
                    <div class="text-center py-4 text-gray-500">
                        <i class="fas fa-search text-2xl mb-2"></i>
                        <p>No se encontraron resultados</p>
                        <div class="mt-3">
                            <button onclick="document.getElementById('filtersForm').submit()" 
                                    class="text-rose-600 hover:text-rose-700 font-medium">
                                Ver resultados completos
                            </button>
                        </div>
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
                            <i class="fas fa-star mr-2 text-rose-500"></i>Marcas
                        </h4>
                        <div class="space-y-1">
                `;
                
                const baseUrl = '<?php echo BASE_URL; ?>';
                const placeholderBrand = `${baseUrl}/assets/images/placeholder-brand.svg`;
                
                data.brands.forEach(brand => {
                    // Fix logo URL
                    let logoUrl;
                    if (brand.logo) {
                        if (brand.logo.startsWith('http') || brand.logo.startsWith('/')) {
                            logoUrl = brand.logo;
                        } else if (brand.logo.startsWith('uploads/')) {
                            logoUrl = `${baseUrl}/${brand.logo}`;
                        } else {
                            logoUrl = `${baseUrl}/uploads/brands/${brand.logo}`;
                        }
                    } else {
                        logoUrl = placeholderBrand;
                    }

                    resultsHTML += `
                        <div class="flex items-center space-x-3 p-2 hover:bg-rose-50 rounded-lg cursor-pointer transition-colors" 
                             onclick="window.location.href='catalogo.php?marca=${brand.id}'">
                            <img src="${logoUrl}" 
                                 alt="${brand.name}" 
                                 class="w-8 h-8 object-contain rounded border border-gray-200"
                                 onerror="this.src='${placeholderBrand}'">
                            <div class="flex-1 min-w-0">
                                <h5 class="font-medium text-gray-900 truncate text-sm">${brand.name}</h5>
                                <p class="text-xs text-rose-600">${brand.product_count || 0} productos</p>
                            </div>
                            <button onclick="event.stopPropagation(); window.open('catalogo.php?marca=${brand.id}', '_blank')" 
                                    class="w-6 h-6 bg-rose-50 hover:bg-rose-100 rounded-full flex items-center justify-center transition-colors"
                                    title="Ver productos">
                                <i class="fas fa-external-link-alt text-rose-600 text-xs"></i>
                            </button>
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
                
                const baseUrl = '<?php echo BASE_URL; ?>';
                const placeholderProduct = `${baseUrl}/assets/images/placeholder-product.svg`;
                
                data.products.forEach(product => {
                    const price = new Intl.NumberFormat('es-CO', {
                        style: 'currency',
                        currency: 'COP',
                        minimumFractionDigits: 0
                    }).format(product.price);

                    // Fix image URL
                    let imageUrl;
                    if (product.image) {
                        if (product.image.startsWith('http') || product.image.startsWith('/')) {
                            imageUrl = product.image;
                        } else if (product.image.startsWith('uploads/')) {
                            imageUrl = `${baseUrl}/${product.image}`;
                        } else {
                            imageUrl = `${baseUrl}/uploads/products/${product.image}`;
                        }
                    } else {
                        imageUrl = placeholderProduct;
                    }

                    resultsHTML += `
                        <div class="flex items-center space-x-3 p-2 hover:bg-gray-50 rounded-lg cursor-pointer transition-colors" onclick="window.location.href='product.php?id=${product.id}'">
                            <img src="${imageUrl}" 
                                 alt="${product.name}" 
                                 class="w-10 h-10 object-cover rounded-lg"
                                 onerror="this.src='${placeholderProduct}'">
                            <div class="flex-1 min-w-0">
                                <h5 class="font-medium text-gray-900 truncate text-sm">${product.name}</h5>
                                <p class="text-xs text-gray-500">${product.brand_name || 'Sin marca'}</p>
                                <p class="text-xs font-semibold text-primary-600">${price}</p>
                            </div>
                            <i class="fas fa-chevron-right text-gray-400 text-xs"></i>
                        </div>
                    `;
                });
                
                resultsHTML += '</div></div>';
            }

            // Always show "Ver más" button
            resultsHTML += `
                <div class="border-t pt-3 mt-3 space-y-2">
                    <button onclick="document.getElementById('filtersForm').submit()" 
                            class="block w-full text-center bg-gradient-to-r from-rose-500 to-pink-500 text-white py-3 rounded-xl font-semibold hover:from-rose-600 hover:to-pink-600 transition-all duration-300">
                        <i class="fas fa-search mr-2"></i>Ver todos los resultados para "${query}"
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
            const searchResults = document.getElementById('brand-search-results');
            if (searchResults) {
                searchResults.classList.add('hidden');
            }
        }

        // Remove the old search functionality to avoid conflicts
        const oldSearchInput = document.querySelector('input[name="search"]:not(#brand-search-input)');
        if (oldSearchInput && oldSearchInput !== searchInput) {
            const newEventHandler = function() {
                // Do nothing, handled by the new system
            };
            oldSearchInput.removeEventListener('input', newEventHandler);
        }
        
        // Add loading state to buttons
        document.querySelectorAll('a[href*="catalogo.php"]').forEach(button => {
            button.addEventListener('click', function() {
                const icon = this.querySelector('i.fa-arrow-right');
                if (icon) {
                    icon.className = 'fas fa-spinner fa-spin ml-2';
                }
                this.style.opacity = '0.8';
                this.style.pointerEvents = 'none';
            });
        });
        
        // Smooth scroll for anchor links
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
        
        // Enhanced hover effects for brand cards
        document.querySelectorAll('.brand-card').forEach(card => {
            card.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-12px) scale(1.03)';
                this.style.boxShadow = '0 30px 60px rgba(176, 141, 128, 0.25)';
            });
            
            card.addEventListener('mouseleave', function() {
                this.style.transform = '';
                this.style.boxShadow = '';
            });
        });
        
        // Keyboard navigation for filters
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' && e.target.classList.contains('filter-button')) {
                e.target.click();
            }
        });
        
        // Performance optimization: Lazy load images
        if ('IntersectionObserver' in window) {
            const imageObserver = new IntersectionObserver((entries, observer) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const img = entry.target;
                        img.src = img.dataset.src;
                        img.classList.remove('lazy');
                        imageObserver.unobserve(img);
                    }
                });
            });
            
            document.querySelectorAll('img[data-src]').forEach(img => {
                imageObserver.observe(img);
            });
        }
        
        // Add sparkle animation to random elements
        setInterval(() => {
            const sparkleElements = document.querySelectorAll('.animate-sparkle');
            sparkleElements.forEach(el => {
                el.style.animationDelay = Math.random() * 2 + 's';
            });
        }, 3000);
    </script>
</body>
</html>
