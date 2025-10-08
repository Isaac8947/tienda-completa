<?php
// Configuración de sesión segura (debe ir antes de session_start)
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 0); // Cambiar a 1 en HTTPS
ini_set('session.cookie_samesite', 'Strict');

// Iniciar sesión para manejar el carrito y la autenticación de usuarios
session_start();

require_once 'config/database.php';
require_once 'config/global-settings.php';
require_once 'includes/CSRFProtection.php';
require_once 'models/Product.php';
require_once 'models/Category.php';
require_once 'models/Brand.php';
require_once 'models/Offer.php';

// Variables para mensajes
$success_message = '';
$error_message = '';

// Procesar formulario de newsletter
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['newsletter_email'])) {
    // Validar token CSRF
    $csrf = new CSRFProtection();
    if (!$csrf->validateToken($_POST['csrf_token'] ?? '', 'newsletter')) {
        $error_message = 'Error de seguridad. Por favor, recarga la página e inténtalo de nuevo.';
    } else {
        $email = filter_var(trim($_POST['newsletter_email']), FILTER_VALIDATE_EMAIL);
        
        if ($email) {
            try {
                $database = new Database();
                $db = $database->getConnection();
                
                // Verificar si el email ya existe
                $checkQuery = "SELECT id FROM newsletter_subscribers WHERE email = ?";
                $checkStmt = $db->prepare($checkQuery);
                $checkStmt->execute([$email]);
                
                if ($checkStmt->rowCount() > 0) {
                    $error_message = '¡Este email ya está suscrito a nuestras ofertas!';
                } else {
                    // Insertar nuevo suscriptor
                    $insertQuery = "INSERT INTO newsletter_subscribers (email, source, created_at, is_active, user_agent, ip_address) VALUES (?, 'ofertas_page', NOW(), 1, ?, ?)";
                    $insertStmt = $db->prepare($insertQuery);
                    
                    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
                    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '';
                    
                    if ($insertStmt->execute([$email, $userAgent, $ipAddress])) {
                        $success_message = '¡Gracias por suscribirte! Pronto recibirás nuestras mejores ofertas.';
                        
                        // Log successful subscription
                        error_log("Newsletter subscription: $email from IP: $ipAddress");
                    } else {
                        $error_message = 'Hubo un error al procesar tu suscripción. Inténtalo de nuevo.';
                    }
                }
            } catch (Exception $e) {
                // Crear tabla de newsletter si no existe
                try {
                    $createTableQuery = "CREATE TABLE IF NOT EXISTS newsletter_subscribers (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        email VARCHAR(255) UNIQUE NOT NULL,
                        source VARCHAR(100) DEFAULT 'website',
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        is_active TINYINT(1) DEFAULT 1,
                        user_agent TEXT,
                        ip_address VARCHAR(45),
                        unsubscribed_at TIMESTAMP NULL,
                        INDEX idx_email (email),
                        INDEX idx_active (is_active),
                        INDEX idx_created (created_at)
                    )";
                    
                    $db->exec($createTableQuery);
                    
                    // Intentar insertar de nuevo
                    $insertQuery = "INSERT INTO newsletter_subscribers (email, source, created_at, is_active, user_agent, ip_address) VALUES (?, 'ofertas_page', NOW(), 1, ?, ?)";
                    $insertStmt = $db->prepare($insertQuery);
                    
                    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
                    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '';
                    
                    if ($insertStmt->execute([$email, $userAgent, $ipAddress])) {
                        $success_message = '¡Gracias por suscribirte! Pronto recibirás nuestras mejores ofertas.';
                        
                        // Log successful subscription
                        error_log("Newsletter subscription: $email from IP: $ipAddress");
                    } else {
                        $error_message = 'Hubo un error al procesar tu suscripción. Inténtalo de nuevo.';
                    }
                } catch (Exception $e2) {
                    error_log("Error creating newsletter table: " . $e2->getMessage());
                    $error_message = 'Error técnico. Por favor, inténtalo más tarde.';
                }
            }
        } else {
            $error_message = 'Por favor, ingresa un email válido.';
        }
    }
}

$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 12;
$offset = ($page - 1) * $perPage;

// Obtener filtros específicos para ofertas
$filters = [
    'category' => $_GET['categoria'] ?? '',
    'brand' => $_GET['marca'] ?? '',
    'min_discount' => $_GET['descuento_min'] ?? '',
    'max_price' => $_GET['precio_max'] ?? '',
    'sort' => $_GET['orden'] ?? 'discount_desc'
];

try {
    $productModel = new Product();
    $categoryModel = new Category();
    $offerModel = new Offer();
    
    // Obtener categorías activas
    $dbCategories = $categoryModel->getAll(['is_active' => 1]);
    
    // Crear tabla de ofertas si no existe
    $offerModel->createTable();
    
    // Obtener información de la oferta activa
    $activeOffer = $offerModel->getMainOffer();
    $offerTimeLeft = $offerModel->getMainOfferTimeLeft();
    
    // Cache simple para mejorar rendimiento
    $cacheKey = 'ofertas_' . md5(serialize($filters) . $page);
    $cacheFile = 'cache/' . $cacheKey . '.json';
    $cacheTime = 300; // 5 minutos
    
    // Verificar si existe caché válido
    if (file_exists($cacheFile) && (time() - filemtime($cacheFile)) < $cacheTime) {
        $cached = json_decode(file_get_contents($cacheFile), true);
        $products = $cached['products'];
        $totalProducts = $cached['totalProducts'];
        $totalPages = $cached['totalPages'];
        $brands = $cached['brands'] ?? [];
        $categories = $cached['categories'] ?? [];
        $stats = $cached['stats'];
    } else {
        // Obtener productos con descuentos (que tengan compare_price)
        $products = $productModel->getProductsOnSale($filters, $perPage, $offset);
        
        // Solo obtener datos adicionales si hay productos
        if (!empty($products)) {
            // Obtener total de productos en oferta
            $totalProducts = $productModel->countProductsOnSale($filters);
            $totalPages = ceil($totalProducts / $perPage);
            
            // Obtener marcas y categorías para filtros (solo cuando se necesiten)
            $brandModel = new Brand();
            $categoryModel = new Category();
            $brands = $brandModel->getActive();
            $categories = $categoryModel->getActive();
            
            // Estadísticas básicas de ofertas
            $discounts = [];
            foreach ($products as $product) {
                if (isset($product['compare_price']) && $product['compare_price'] > 0 && $product['price'] > 0) {
                    $discount = round((($product['compare_price'] - $product['price']) / $product['compare_price']) * 100);
                    if ($discount > 0) {
                        $discounts[] = $discount;
                    }
                }
            }
            
            $avg_discount = !empty($discounts) ? round(array_sum($discounts) / count($discounts)) : 0;
            $max_discount = !empty($discounts) ? max($discounts) : 0;
            
            $stats = [
                'total_offers' => $totalProducts,
                'current_page' => $page,
                'total_pages' => $totalPages,
                'avg_discount' => $avg_discount,
                'max_discount' => $max_discount
            ];
        } else {
            $totalProducts = 0;
            $totalPages = 0;
            $brands = [];
            $categories = [];
            $stats = ['total_offers' => 0, 'current_page' => $page, 'total_pages' => 0, 'avg_discount' => 0, 'max_discount' => 0];
        }
        
        // Guardar en caché
        if (!is_dir('cache')) {
            mkdir('cache', 0755, true);
        }
        $cacheData = [
            'products' => $products,
            'totalProducts' => $totalProducts,
            'totalPages' => $totalPages,
            'brands' => $brands,
            'categories' => $categories,
            'stats' => $stats
        ];
        file_put_contents($cacheFile, json_encode($cacheData));
    }
    
} catch (Exception $e) {
    error_log("Error loading offers page: " . $e->getMessage());
    $products = [];
    $brands = [];
    $categories = [];
    $totalProducts = 0;
    $totalPages = 0;
    $stats = ['total_offers' => 0, 'avg_discount' => 0, 'max_discount' => 0];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, user-scalable=yes, viewport-fit=cover">
    <title>Ofertas Especiales - OdiseaStore</title>
    <meta name="description" content="Descubre las mejores ofertas en maquillaje y productos de belleza. Descuentos especiales en marcas exclusivas.">
    
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
                        'gradient-shift': 'gradientShift 3s ease-in-out infinite',
                        'slide-in-left': 'slideInLeft 0.3s ease-out',
                        'slide-out-left': 'slideOutLeft 0.3s ease-in',
                        'fade-in': 'fadeIn 0.3s ease-out',
                        'fade-out': 'fadeOut 0.3s ease-in'
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
                        },
                        slideInLeft: {
                            '0%': { transform: 'translateX(-100%)' },
                            '100%': { transform: 'translateX(0)' }
                        },
                        slideOutLeft: {
                            '0%': { transform: 'translateX(0)' },
                            '100%': { transform: 'translateX(-100%)' }
                        },
                        fadeIn: {
                            '0%': { opacity: '0' },
                            '100%': { opacity: '1' }
                        },
                        fadeOut: {
                            '0%': { opacity: '1' },
                            '100%': { opacity: '0' }
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
            background: linear-gradient(135deg, #ef4444, #f97316, #eab308);
            background-size: 200% 200%;
            animation: gradientShift 3s ease-in-out infinite;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .luxury-shadow {
            box-shadow: 0 25px 50px -12px rgba(176, 141, 128, 0.25);
        }
        
        .offer-shadow {
            box-shadow: 0 25px 50px -12px rgba(239, 68, 68, 0.25);
        }
        
        .hover-lift {
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .hover-lift:hover {
            transform: translateY(-12px) scale(1.02);
            box-shadow: 0 35px 60px -12px rgba(239, 68, 68, 0.35);
        }
        
        /* Mobile hover effects */
        @media (max-width: 768px) {
            .hover-lift:hover {
                transform: translateY(-6px) scale(1.01);
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
                radial-gradient(at 40% 20%, rgba(239, 68, 68, 0.1) 0px, transparent 50%),
                radial-gradient(at 80% 0%, rgba(249, 115, 22, 0.1) 0px, transparent 50%),
                radial-gradient(at 0% 50%, rgba(234, 179, 8, 0.1) 0px, transparent 50%);
        }
        
        .line-clamp-2 {
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        
        .offer-badge {
            background: linear-gradient(135deg, #ef4444, #dc2626);
            position: relative;
            overflow: hidden;
        }
        
        .offer-badge::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
            animation: shimmer 2s infinite;
        }
        
        .countdown-digit {
            background: linear-gradient(135deg, #ef4444, #dc2626);
            color: white;
            border-radius: 16px;
            padding: 16px;
            min-width: 70px;
            text-align: center;
            box-shadow: 0 8px 25px rgba(239, 68, 68, 0.3);
            position: relative;
            overflow: hidden;
        }
        
        /* Mobile countdown digits */
        @media (max-width: 768px) {
            .countdown-digit {
                padding: 12px;
                min-width: 50px;
                border-radius: 12px;
            }
        }
        
        .countdown-digit::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            animation: shimmer 3s infinite;
        }
        
        .floating-element {
            position: absolute;
            border-radius: 50%;
            background: linear-gradient(135deg, rgba(239, 68, 68, 0.1), rgba(249, 115, 22, 0.1));
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
        
        /* Simplified product card styles */
        .product-card-simple {
            transition: all 0.3s ease;
        }
        
        .product-card-simple:hover {
            transform: translateY(-4px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
        }
        
        .filter-card {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(15px);
            border: 1px solid rgba(255, 255, 255, 0.3);
            transition: all 0.3s ease;
        }
        
        .filter-card:hover {
            background: rgba(255, 255, 255, 0.95);
            transform: translateY(-2px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
        }
        
        .stats-card {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(15px);
            border: 1px solid rgba(255, 255, 255, 0.3);
            position: relative;
            overflow: hidden;
        }
        
        .stats-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, rgba(239, 68, 68, 0.05), rgba(249, 115, 22, 0.05));
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        
        .stats-card:hover::before {
            opacity: 1;
        }
        
        .sparkle-icon {
            animation: sparkle 1.5s ease-in-out infinite;
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
        
        /* Mobile filters modal */
        .mobile-filters-modal {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: white;
            border-radius: 24px 24px 0 0;
            transform: translateY(100%);
            transition: transform 0.3s ease;
            z-index: 9998;
            max-height: 80vh;
            overflow-y: auto;
            -webkit-overflow-scrolling: touch;
        }
        
        .mobile-filters-modal.active {
            transform: translateY(0);
        }
        
        /* Mobile header styles */
        .mobile-header {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-bottom: 1px solid rgba(0, 0, 0, 0.1);
        }
        
        /* Touch-friendly buttons */
        .touch-button {
            min-height: 44px;
            min-width: 44px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        /* Loading states */
        .loading-skeleton {
            background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
            background-size: 200% 100%;
            animation: loading 1.5s infinite;
        }
        
        @keyframes loading {
            0% { background-position: 200% 0; }
            100% { background-position: -200% 0; }
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
            background: linear-gradient(135deg, #ef4444, #f97316);
            border-radius: 10px;
        }
        
        ::-webkit-scrollbar-thumb:hover {
            background: linear-gradient(135deg, #dc2626, #ea580c);
        }
        
        /* Prevent body scroll when modal is open */
        .modal-open {
            overflow: hidden;
            position: fixed;
            width: 100%;
        }
    </style>
</head>

<body class="font-sans bg-white overflow-x-hidden">
    <!-- Mobile Header -->
    <header class="md:hidden fixed top-0 left-0 right-0 z-50 mobile-header">
        <div class="px-4 py-3">
            <div class="flex items-center justify-between">
                <!-- Menu Button -->
                <button id="mobile-menu-btn" class="touch-button text-gray-700 hover:text-offer-500 transition-colors">
                    <i class="fas fa-bars text-xl"></i>
                </button>
                
                <!-- Logo -->
                <div class="flex items-center">
                    <a href="/" class="text-2xl font-serif font-bold gradient-text">
                        Odisea
                    </a>
                    <span class="ml-1 text-xs text-gray-500 font-light">store</span>
                </div>
                
                <!-- Actions -->
                <div class="flex items-center space-x-2">
                    <!-- Search -->
                    <button id="mobile-search-btn" class="touch-button text-gray-700 hover:text-offer-500 transition-colors">
                        <i class="fas fa-search text-lg"></i>
                    </button>
                    
                    <!-- Cart -->
                    <a href="carrito.php" class="touch-button relative text-gray-700 hover:text-offer-500 transition-colors">
                        <i class="fas fa-shopping-bag text-lg"></i>
                        <span class="absolute -top-1 -right-1 bg-gradient-to-r from-offer-500 to-orange-500 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center font-medium" id="mobile-cart-count">
                            <?php echo isset($_SESSION['cart']) ? array_sum(array_column($_SESSION['cart'], 'quantity')) : 0; ?>
                        </span>
                    </a>
                </div>
            </div>
            
            <!-- Mobile Search Bar (Hidden by default) -->
            <div id="mobile-search-bar" class="mt-3 hidden relative">
                <div class="relative">
                    <input type="text" 
                           id="mobile-search-input"
                           placeholder="Buscar ofertas..." 
                           class="w-full px-4 py-3 pr-12 bg-gray-50 border border-gray-200 rounded-2xl focus:outline-none focus:ring-2 focus:ring-offer-500/50 focus:border-offer-500 transition-all duration-300"
                           autocomplete="off">
                    <button class="absolute right-3 top-1/2 transform -translate-y-1/2 w-8 h-8 bg-gradient-to-r from-offer-500 to-orange-500 rounded-xl flex items-center justify-center text-white">
                        <i class="fas fa-search text-sm"></i>
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
                        <span class="ml-2 text-xs text-gray-500 font-light">store</span>
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
                        <a href="carrito.php" class="relative p-2 text-gray-700 hover:text-primary-500 transition-colors rounded-xl hover:bg-primary-50 group">
                            <i class="fas fa-shopping-bag text-xl group-hover:scale-110 transition-transform duration-300"></i>
                            <span class="absolute -top-1 -right-1 bg-gradient-to-r from-primary-500 to-secondary-500 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center font-medium shadow-lg transform group-hover:scale-110 transition-transform duration-300" id="cart-count">
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
                                // Mapeo de iconos para categorías
                                $categoryIcons = [
                                    'maquillaje' => 'fa-palette',
                                    'rostro' => 'fa-palette',
                                    'ojos' => 'fa-eye',
                                    'labios' => 'fa-kiss-wink-heart',
                                    'cejas' => 'fa-brow',
                                    'cuidado' => 'fa-spa',
                                    'herramientas' => 'fa-brush',
                                    'tecnologia' => 'fa-laptop',
                                    'hogar' => 'fa-home',
                                    'deportes' => 'fa-running',
                                    'moda' => 'fa-tshirt',
                                    'accesorios' => 'fa-gem'
                                ];
                                
                                // Mostrar categorías desde la base de datos
                                $categoryCount = 0;
                                foreach ($dbCategories as $category):
                                    if ($categoryCount >= 4) break; // Máximo 4 categorías en el grid
                                    $icon = $categoryIcons[$category['slug']] ?? 'fa-shopping-bag';
                                    $categoryCount++;
                                ?>
                                <div class="group/item">
                                    <div class="flex items-center space-x-3 mb-4">
                                        <div class="w-10 h-10 bg-gradient-to-r from-primary-500 to-secondary-500 rounded-xl flex items-center justify-center text-white">
                                            <i class="fas <?php echo $icon; ?>"></i>
                                        </div>
                                        <h3 class="font-semibold text-gray-900 text-lg"><?php echo htmlspecialchars($category['name']); ?></h3>
                                    </div>
                                    <div class="text-center">
                                        <a href="categoria.php?categoria=<?php echo urlencode($category['slug']); ?>" 
                                           class="inline-flex items-center text-primary-600 hover:text-primary-700 font-medium text-sm transition-colors">
                                            Ver todos 
                                            <i class="fas fa-arrow-right ml-2"></i>
                                        </a>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Main Navigation -->
                    <div class="hidden md:flex items-center space-x-8">
                        <a href="index.php" class="text-gray-700 hover:text-primary-500 transition-colors font-medium py-4 border-b-2 border-transparent hover:border-primary-500">Inicio</a>
                        <a href="nuevos.php" class="text-gray-700 hover:text-primary-500 transition-colors font-medium py-4 border-b-2 border-transparent hover:border-primary-500">Novedades</a>
                        <a href="ofertas.php" class="text-offer-500 font-medium py-4 border-b-2 border-offer-500">Ofertas</a>
                        <a href="marcas.php" class="text-gray-700 hover:text-primary-500 transition-colors font-medium py-4 border-b-2 border-transparent hover:border-primary-500">Marcas</a>
                        <a href="blog.php" class="text-gray-700 hover:text-primary-500 transition-colors font-medium py-4 border-b-2 border-transparent hover:border-primary-500">Blog</a>
                    </div>
                    
                    <!-- Promo Banner -->
                    <div class="hidden lg:block">
                        <div class="bg-gradient-to-r from-offer-500 to-orange-500 text-white px-6 py-2 rounded-full text-sm font-medium animate-pulse-glow">
                            <i class="fas fa-fire mr-2 sparkle-icon"></i>
                            ¡Ofertas por tiempo limitado!
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
            <div class="bg-gradient-to-r from-offer-500 to-orange-500 text-white p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-2xl font-serif font-bold">Odisea</h2>
                        <p class="text-sm opacity-90">store</p>
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
                    <a href="ofertas.php" class="flex items-center space-x-3 p-4 rounded-xl bg-offer-50 text-offer-600">
                        <i class="fas fa-fire text-offer-500"></i>
                        <span class="font-semibold">Ofertas</span>
                        <span class="ml-auto bg-offer-500 text-white text-xs px-2 py-1 rounded-full">HOT</span>
                    </a>
                    <a href="marcas.php" class="flex items-center space-x-3 p-4 rounded-xl hover:bg-gray-50 transition-colors">
                        <i class="fas fa-tags text-gray-400"></i>
                        <span class="font-medium text-gray-700">Marcas</span>
                    </a>
                    <a href="nuevos.php" class="flex items-center space-x-3 p-4 rounded-xl hover:bg-gray-50 transition-colors">
                        <i class="fas fa-star text-gray-400"></i>
                        <span class="font-medium text-gray-700">Novedades</span>
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
    
    <!-- Mobile Filters Modal -->
    <div id="mobile-filters-modal" class="mobile-filters-modal md:hidden">
        <div class="p-6">
            <!-- Header -->
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-xl font-bold text-gray-800 flex items-center">
                    <i class="fas fa-filter text-offer-500 mr-3"></i>
                    Filtrar Ofertas
                </h3>
                <button id="close-mobile-filters" class="touch-button text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            
            <!-- Filters Form -->
            <form method="GET" action="ofertas.php" id="mobile-filters-form" class="space-y-6">
                <!-- Category Filter -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-3">Categoría</label>
                    <select name="categoria" class="w-full p-4 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-offer-500/50 focus:border-offer-500 transition-all duration-300 bg-white">
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
                    <label class="block text-sm font-semibold text-gray-700 mb-3">Marca</label>
                    <select name="marca" class="w-full p-4 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-offer-500/50 focus:border-offer-500 transition-all duration-300 bg-white">
                        <option value="">Todas las marcas</option>
                        <?php foreach ($brands as $brand): ?>
                        <option value="<?php echo $brand['id']; ?>" <?php echo $filters['brand'] == $brand['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($brand['name']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <!-- Discount Filter -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-3">Descuento Mínimo</label>
                    <select name="descuento_min" class="w-full p-4 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-offer-500/50 focus:border-offer-500 transition-all duration-300 bg-white">
                        <option value="">Cualquier descuento</option>
                        <option value="10" <?php echo $filters['min_discount'] == '10' ? 'selected' : ''; ?>>Mínimo 10%</option>
                        <option value="20" <?php echo $filters['min_discount'] == '20' ? 'selected' : ''; ?>>Mínimo 20%</option>
                        <option value="30" <?php echo $filters['min_discount'] == '30' ? 'selected' : ''; ?>>Mínimo 30%</option>
                        <option value="50" <?php echo $filters['min_discount'] == '50' ? 'selected' : ''; ?>>Mínimo 50%</option>
                    </select>
                </div>
                
                <!-- Price Filter -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-3">Precio Máximo</label>
                    <input type="number" name="precio_max" placeholder="Precio máximo"
                           value="<?php echo htmlspecialchars($filters['max_price']); ?>"
                           class="w-full p-4 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-offer-500/50 focus:border-offer-500 transition-all duration-300 bg-white">
                </div>
                
                <!-- Sort Filter -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-3">Ordenar por</label>
                    <select name="orden" class="w-full p-4 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-offer-500/50 focus:border-offer-500 transition-all duration-300 bg-white">
                        <option value="discount_desc" <?php echo $filters['sort'] == 'discount_desc' ? 'selected' : ''; ?>>Mayor descuento</option>
                        <option value="discount_asc" <?php echo $filters['sort'] == 'discount_asc' ? 'selected' : ''; ?>>Menor descuento</option>
                        <option value="price_low" <?php echo $filters['sort'] == 'price_low' ? 'selected' : ''; ?>>Precio: Menor a mayor</option>
                        <option value="price_high" <?php echo $filters['sort'] == 'price_high' ? 'selected' : ''; ?>>Precio: Mayor a menor</option>
                        <option value="newest" <?php echo $filters['sort'] == 'newest' ? 'selected' : ''; ?>>Más recientes</option>
                    </select>
                </div>
                
                <!-- Action Buttons -->
                <div class="flex space-x-4 pt-4">
                    <button type="submit" class="flex-1 bg-gradient-to-r from-offer-500 to-orange-500 text-white py-4 rounded-xl font-semibold text-lg">
                        Aplicar Filtros
                    </button>
                    <a href="ofertas.php" class="flex-1 border-2 border-gray-300 text-gray-700 py-4 rounded-xl font-semibold text-lg text-center">
                        Limpiar
                    </a>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Breadcrumb -->
    <section class="pt-20 md:pt-32 pb-6 bg-gradient-to-r from-offer-50/50 to-orange-50/50 backdrop-blur-sm">
        <div class="container mx-auto px-4">
            <nav class="text-sm" data-aos="fade-right">
                <ol class="flex items-center space-x-3">
                    <li>
                        <a href="index.php" class="text-gray-600 hover:text-offer-500 transition-colors duration-300 flex items-center">
                            <i class="fas fa-home mr-2"></i>
                            Inicio
                        </a>
                    </li>
                    <li class="text-gray-400">
                        <i class="fas fa-chevron-right text-xs"></i>
                    </li>
                    <li class="text-offer-600 font-medium flex items-center">
                        <i class="fas fa-fire mr-2 sparkle-icon"></i>
                        Ofertas Especiales
                    </li>
                </ol>
            </nav>
        </div>
    </section>
    
    <!-- Hero Section -->
    <section class="py-12 md:py-24 relative overflow-hidden bg-mesh">
        <!-- Floating Elements (Desktop only) -->
        <div class="floating-element absolute top-20 left-10 w-64 h-64"></div>
        <div class="floating-element absolute bottom-20 right-10 w-80 h-80" style="animation-delay: -2s;"></div>
        <div class="floating-element absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 w-96 h-96" style="animation-delay: -4s;"></div>
        
        <div class="container mx-auto px-4 relative z-10">
            <div class="text-center mb-8 md:mb-16" data-aos="fade-up">
                <!-- Badge -->
                <div class="inline-flex items-center bg-gradient-to-r from-offer-500 to-orange-500 text-white px-4 md:px-8 py-3 md:py-4 rounded-full text-sm md:text-base font-semibold mb-6 md:mb-8 animate-pulse-glow">
                    <i class="fas fa-fire mr-2 md:mr-3 sparkle-icon"></i>
                    <?php if ($activeOffer): ?>
                        <?php echo strtoupper(htmlspecialchars($activeOffer['banner_text']) ?: 'OFERTAS POR TIEMPO LIMITADO'); ?>
                    <?php else: ?>
                        OFERTAS ESPECIALES
                    <?php endif; ?>
                    <i class="fas fa-fire ml-2 md:ml-3 sparkle-icon"></i>
                </div>
                
                <!-- Title -->
                <h1 class="text-4xl md:text-6xl lg:text-8xl font-serif font-bold mb-6 md:mb-8 text-shadow">
                    <?php if ($activeOffer): ?>
                        <span class="gradient-text"><?php echo htmlspecialchars($activeOffer['title']); ?></span>
                    <?php else: ?>
                        <span class="gradient-text">Ofertas</span>
                        <br>
                        <span class="text-gray-900">Especiales</span>
                    <?php endif; ?>
                </h1>
                
                <!-- Subtitle -->
                <p class="text-lg md:text-xl lg:text-2xl text-gray-600 max-w-4xl mx-auto mb-8 md:mb-16 font-light leading-relaxed px-4">
                    <?php if ($activeOffer && $activeOffer['description']): ?>
                        <?php echo htmlspecialchars($activeOffer['description']); ?>
                        <span class="font-semibold text-offer-600">¡Hasta <?php echo $activeOffer['discount_percentage']; ?>% de descuento!</span>
                    <?php else: ?>
                        Aprovecha nuestros descuentos exclusivos en los mejores productos de maquillaje y belleza premium.
                        <span class="font-semibold text-offer-600">¡Ofertas que no puedes dejar pasar!</span>
                    <?php endif; ?>
                </p>
                
                <!-- Countdown Timer -->
                <?php if ($offerTimeLeft && $activeOffer): ?>
                <div class="bg-white/90 backdrop-blur-sm rounded-2xl md:rounded-3xl luxury-shadow p-6 md:p-10 max-w-2xl md:max-w-3xl mx-auto mb-8 md:mb-16 border border-white/50" data-aos="fade-up" data-aos-delay="200">
                    <h3 class="text-xl md:text-3xl font-serif font-bold text-gray-900 mb-4 md:mb-8 flex items-center justify-center">
                        <i class="fas fa-clock mr-2 md:mr-4 text-offer-500 sparkle-icon"></i>
                        Las ofertas terminan en:
                    </h3>
                    <div id="countdown" class="grid grid-cols-4 gap-3 md:gap-8">
                        <div class="text-center">
                            <div class="countdown-digit text-xl md:text-4xl font-bold mb-2 md:mb-3" id="days">00</div>
                            <div class="text-xs md:text-sm text-gray-600 font-medium uppercase tracking-wide">Días</div>
                        </div>
                        <div class="text-center">
                            <div class="countdown-digit text-xl md:text-4xl font-bold mb-2 md:mb-3" id="hours">00</div>
                            <div class="text-xs md:text-sm text-gray-600 font-medium uppercase tracking-wide">Horas</div>
                        </div>
                        <div class="text-center">
                            <div class="countdown-digit text-xl md:text-4xl font-bold mb-2 md:mb-3" id="minutes">00</div>
                            <div class="text-xs md:text-sm text-gray-600 font-medium uppercase tracking-wide">Min</div>
                        </div>
                        <div class="text-center">
                            <div class="countdown-digit text-xl md:text-4xl font-bold mb-2 md:mb-3" id="seconds">00</div>
                            <div class="text-xs md:text-sm text-gray-600 font-medium uppercase tracking-wide">Seg</div>
                        </div>
                    </div>
                </div>
                <?php else: ?>
                <div class="bg-white/90 backdrop-blur-sm rounded-2xl md:rounded-3xl luxury-shadow p-6 md:p-10 max-w-2xl md:max-w-3xl mx-auto mb-8 md:mb-16 border border-white/50" data-aos="fade-up" data-aos-delay="200">
                    <div class="text-center">
                        <i class="fas fa-info-circle text-4xl md:text-6xl text-gray-400 mb-4"></i>
                        <h3 class="text-xl md:text-2xl font-serif font-bold text-gray-600 mb-2">No hay ofertas activas</h3>
                        <p class="text-sm md:text-base text-gray-500">Mantente atento para no perderte nuestras próximas ofertas especiales</p>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Stats -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 md:gap-8 max-w-5xl mx-auto" data-aos="fade-up" data-aos-delay="300">
                    <div class="stats-card rounded-2xl md:rounded-3xl offer-shadow p-6 md:p-10 hover-lift border border-white/50">
                        <div class="w-12 md:w-16 h-12 md:h-16 bg-gradient-to-r from-offer-500 to-orange-500 rounded-xl md:rounded-2xl flex items-center justify-center mx-auto mb-4 md:mb-6">
                            <i class="fas fa-tags text-white text-lg md:text-2xl sparkle-icon"></i>
                        </div>
                        <div class="text-3xl md:text-5xl font-bold gradient-text mb-2 md:mb-3"><?php echo $stats['total_offers']; ?></div>
                        <div class="text-gray-600 font-medium text-sm md:text-lg">Productos en Oferta</div>
                        <div class="w-12 md:w-16 h-1 bg-gradient-to-r from-offer-500 to-orange-500 rounded-full mx-auto mt-3 md:mt-4"></div>
                    </div>
                    <div class="stats-card rounded-2xl md:rounded-3xl offer-shadow p-6 md:p-10 hover-lift border border-white/50">
                        <div class="w-12 md:w-16 h-12 md:h-16 bg-gradient-to-r from-orange-500 to-yellow-500 rounded-xl md:rounded-2xl flex items-center justify-center mx-auto mb-4 md:mb-6">
                            <i class="fas fa-percentage text-white text-lg md:text-2xl sparkle-icon"></i>
                        </div>
                        <div class="text-3xl md:text-5xl font-bold gradient-text mb-2 md:mb-3"><?php echo isset($stats['avg_discount']) ? round($stats['avg_discount']) : 0; ?>%</div>
                        <div class="text-gray-600 font-medium text-sm md:text-lg">Descuento Promedio</div>
                        <div class="w-12 md:w-16 h-1 bg-gradient-to-r from-orange-500 to-yellow-500 rounded-full mx-auto mt-3 md:mt-4"></div>
                    </div>
                    <div class="stats-card rounded-2xl md:rounded-3xl offer-shadow p-6 md:p-10 hover-lift border border-white/50">
                        <div class="w-12 md:w-16 h-12 md:h-16 bg-gradient-to-r from-yellow-500 to-offer-500 rounded-xl md:rounded-2xl flex items-center justify-center mx-auto mb-4 md:mb-6">
                            <i class="fas fa-fire text-white text-lg md:text-2xl sparkle-icon"></i>
                        </div>
                        <div class="text-3xl md:text-5xl font-bold gradient-text mb-2 md:mb-3"><?php echo isset($stats['max_discount']) ? $stats['max_discount'] : 0; ?>%</div>
                        <div class="text-gray-600 font-medium text-sm md:text-lg">Descuento Máximo</div>
                        <div class="w-12 md:w-16 h-1 bg-gradient-to-r from-yellow-500 to-offer-500 rounded-full mx-auto mt-3 md:mt-4"></div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Mobile Filters Button -->
    <div class="md:hidden sticky top-16 z-40 bg-white/95 backdrop-blur-sm border-b border-gray-100 p-4">
        <div class="flex items-center justify-between">
            <button id="mobile-filters-btn" class="flex items-center space-x-2 bg-gradient-to-r from-offer-500 to-orange-500 text-white px-6 py-3 rounded-xl font-semibold shadow-lg">
                <i class="fas fa-filter"></i>
                <span>Filtros</span>
                <?php 
                $activeFilters = 0;
                if (!empty($filters['category'])) $activeFilters++;
                if (!empty($filters['brand'])) $activeFilters++;
                if (!empty($filters['min_discount'])) $activeFilters++;
                if (!empty($filters['max_price'])) $activeFilters++;
                if ($activeFilters > 0): 
                ?>
                <span class="bg-white text-offer-500 text-xs rounded-full px-2 py-1 font-bold"><?php echo $activeFilters; ?></span>
                <?php endif; ?>
            </button>
            
            <div class="text-sm text-gray-600">
                <span class="font-semibold text-offer-600"><?php echo number_format($totalProducts); ?></span> ofertas
            </div>
        </div>
    </div>
    
    <!-- Filters and Products -->
    <section class="py-8 md:py-20">
        <div class="container mx-auto px-4">
            <div class="flex flex-col xl:flex-row gap-8 md:gap-12">
                <!-- Desktop Sidebar Filters -->
                <div class="hidden xl:block xl:w-1/4">
                    <div class="filter-card rounded-3xl luxury-shadow p-8 sticky top-6" data-aos="fade-right">
                        <h3 class="text-3xl font-serif font-bold text-gray-900 mb-10 flex items-center">
                            <div class="w-12 h-12 bg-gradient-to-r from-offer-500 to-orange-500 rounded-xl flex items-center justify-center mr-4">
                                <i class="fas fa-filter text-white sparkle-icon"></i>
                            </div>
                            Filtrar Ofertas
                        </h3>
                        
                        <form method="GET" action="ofertas.php" id="filters-form" class="space-y-10">
                            <!-- Category Filter -->
                            <div>
                                <h4 class="font-semibold text-gray-900 mb-6 flex items-center text-xl">
                                    <div class="w-10 h-10 bg-gradient-to-r from-secondary-400 to-secondary-500 rounded-lg flex items-center justify-center mr-4">
                                        <i class="fas fa-layer-group text-white"></i>
                                    </div>
                                    Categoría
                                </h4>
                                <select name="categoria" class="w-full p-5 border-2 border-gray-200 rounded-2xl focus:outline-none focus:ring-2 focus:ring-offer-500/50 focus:border-offer-500 transition-all duration-300 bg-white/90 backdrop-blur-sm text-lg">
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
                                <h4 class="font-semibold text-gray-900 mb-6 flex items-center text-xl">
                                    <div class="w-10 h-10 bg-gradient-to-r from-orange-400 to-orange-500 rounded-lg flex items-center justify-center mr-4">
                                        <i class="fas fa-tags text-white"></i>
                                    </div>
                                    Marca
                                </h4>
                                <select name="marca" class="w-full p-5 border-2 border-gray-200 rounded-2xl focus:outline-none focus:ring-2 focus:ring-offer-500/50 focus:border-offer-500 transition-all duration-300 bg-white/90 backdrop-blur-sm text-lg">
                                    <option value="">Todas las marcas</option>
                                    <?php foreach ($brands as $brand): ?>
                                    <option value="<?php echo $brand['id']; ?>" <?php echo $filters['brand'] == $brand['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($brand['name']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <!-- Discount Filter -->
                            <div>
                                <h4 class="font-semibold text-gray-900 mb-6 flex items-center text-xl">
                                    <div class="w-10 h-10 bg-gradient-to-r from-green-400 to-green-500 rounded-lg flex items-center justify-center mr-4">
                                        <i class="fas fa-percentage text-white"></i>
                                    </div>
                                    Descuento Mínimo
                                </h4>
                                <select name="descuento_min" class="w-full p-5 border-2 border-gray-200 rounded-2xl focus:outline-none focus:ring-2 focus:ring-offer-500/50 focus:border-offer-500 transition-all duration-300 bg-white/90 backdrop-blur-sm text-lg">
                                    <option value="">Cualquier descuento</option>
                                    <option value="10" <?php echo $filters['min_discount'] == '10' ? 'selected' : ''; ?>>Mínimo 10%</option>
                                    <option value="20" <?php echo $filters['min_discount'] == '20' ? 'selected' : ''; ?>>Mínimo 20%</option>
                                    <option value="30" <?php echo $filters['min_discount'] == '30' ? 'selected' : ''; ?>>Mínimo 30%</option>
                                    <option value="50" <?php echo $filters['min_discount'] == '50' ? 'selected' : ''; ?>>Mínimo 50%</option>
                                </select>
                            </div>
                            
                            <!-- Price Filter -->
                            <div>
                                <h4 class="font-semibold text-gray-900 mb-6 flex items-center text-xl">
                                    <div class="w-10 h-10 bg-gradient-to-r from-yellow-400 to-yellow-500 rounded-lg flex items-center justify-center mr-4">
                                        <i class="fas fa-dollar-sign text-white"></i>
                                    </div>
                                    Precio Máximo
                                </h4>
                                <input type="number" name="precio_max" placeholder="Precio máximo"
                                       value="<?php echo htmlspecialchars($filters['max_price']); ?>"
                                       class="w-full p-5 border-2 border-gray-200 rounded-2xl focus:outline-none focus:ring-2 focus:ring-offer-500/50 focus:border-offer-500 transition-all duration-300 bg-white/90 backdrop-blur-sm text-lg">
                            </div>
                            
                            <!-- Sort Filter -->
                            <div>
                                <h4 class="font-semibold text-gray-900 mb-6 flex items-center text-xl">
                                    <div class="w-10 h-10 bg-gradient-to-r from-purple-400 to-purple-500 rounded-lg flex items-center justify-center mr-4">
                                        <i class="fas fa-sort text-white"></i>
                                    </div>
                                    Ordenar por
                                </h4>
                                <select name="orden" class="w-full p-5 border-2 border-gray-200 rounded-2xl focus:outline-none focus:ring-2 focus:ring-offer-500/50 focus:border-offer-500 transition-all duration-300 bg-white/90 backdrop-blur-sm text-lg">
                                    <option value="discount_desc" <?php echo $filters['sort'] == 'discount_desc' ? 'selected' : ''; ?>>Mayor descuento</option>
                                    <option value="discount_asc" <?php echo $filters['sort'] == 'discount_asc' ? 'selected' : ''; ?>>Menor descuento</option>
                                    <option value="price_low" <?php echo $filters['sort'] == 'price_low' ? 'selected' : ''; ?>>Precio: Menor a mayor</option>
                                    <option value="price_high" <?php echo $filters['sort'] == 'price_high' ? 'selected' : ''; ?>>Precio: Mayor a menor</option>
                                    <option value="newest" <?php echo $filters['sort'] == 'newest' ? 'selected' : ''; ?>>Más recientes</option>
                                </select>
                            </div>
                            
                            <div class="space-y-6">
                                <button type="submit" class="w-full bg-gradient-to-r from-offer-500 to-orange-500 text-white py-5 rounded-2xl font-semibold text-lg hover:shadow-xl transform hover:scale-105 transition-all duration-300">
                                    <i class="fas fa-search mr-3"></i>
                                    Aplicar Filtros
                                </button>
                                
                                <a href="ofertas.php"
                                   class="w-full border-2 border-gray-300 text-gray-700 py-5 rounded-2xl font-semibold text-lg hover:bg-gray-50 hover:border-offer-300 transition-all duration-300 block text-center">
                                    <i class="fas fa-times mr-3"></i>
                                    Limpiar Filtros
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Products Grid -->
                <div class="xl:w-3/4">
                    <!-- Results Header -->
                    <div class="hidden md:flex flex-col sm:flex-row justify-between items-start sm:items-center mb-12 filter-card rounded-3xl luxury-shadow p-8" data-aos="fade-up">
                        <div>
                            <h2 class="text-4xl font-serif font-bold text-gray-900 mb-4 flex items-center">
                                <i class="fas fa-fire text-offer-500 mr-4 sparkle-icon"></i>
                                <?php echo number_format($totalProducts); ?> ofertas disponibles
                            </h2>
                            <p class="text-gray-600 font-light text-xl">
                                Página <?php echo $page; ?> de <?php echo max(1, $totalPages); ?>
                            </p>
                        </div>
                        
                        <div class="mt-6 sm:mt-0">
                            <span class="bg-gradient-to-r from-offer-500 to-orange-500 text-white px-8 py-4 rounded-full text-lg font-semibold animate-pulse-glow">
                                🔥 ¡Ofertas por tiempo limitado!
                            </span>
                        </div>
                    </div>
                    
                    <?php if (isset($products) && is_array($products) && count($products) > 0): ?>
                        <!-- Products Grid -->
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                            <?php
                            // Optimización: obtener todas las marcas de una vez
                            $brandIds = array_unique(array_filter(array_column($products, 'brand_id')));
                            $productBrands = [];
                            if (!empty($brandIds) && isset($brandModel)) {
                                foreach ($brandIds as $brandId) {
                                    $brand = $brandModel->getById($brandId);
                                    if ($brand) {
                                        $productBrands[$brandId] = $brand;
                                    }
                                }
                            }
                            
                            foreach ($products as $index => $product):
                                // Obtener información de la marca (optimizado)
                                $brandName = 'Sin marca';
                                if (!empty($product['brand_id']) && isset($productBrands[$product['brand_id']])) {
                                    $brandName = $productBrands[$product['brand_id']]['name'];
                                }
                                
                                // Calcular descuento
                                $discount = 0;
                                $savings = 0;
                                if (!empty($product['compare_price']) && $product['compare_price'] > $product['price']) {
                                    $discount = round((($product['compare_price'] - $product['price']) / $product['compare_price']) * 100);
                                    $savings = $product['compare_price'] - $product['price'];
                                }
                                
                                // Imagen del producto con fallback optimizado
                                if (!empty($product['main_image'])) {
                                    if (strpos($product['main_image'], 'uploads/products/') === 0) {
                                        // La ruta ya incluye uploads/products/
                                        $productImage = $product['main_image'];
                                    } else {
                                        // Solo el nombre del archivo
                                        $productImage = 'uploads/products/' . $product['main_image'];
                                    }
                                } else {
                                    $productImage = '/placeholder.svg?height=400&width=400&text=Producto';
                                }
                            ?>
                            <div class="group relative bg-white rounded-2xl shadow-lg hover:shadow-2xl transition-all duration-300 overflow-hidden" data-aos="fade-up" data-aos-delay="<?php echo ($index % 6) * 100; ?>">
                                <!-- Discount Badge -->
                                <div class="absolute top-4 left-4 z-20">
                                    <div class="bg-gradient-to-r from-red-500 to-pink-500 text-white px-3 py-1 rounded-full text-sm font-bold shadow-lg">
                                        -<?php echo $discount; ?>%
                                    </div>
                                </div>
                                
                                <!-- Wishlist Button -->
                                <div class="absolute top-4 right-4 z-20">
                                    <button class="w-10 h-10 bg-white/90 backdrop-blur-sm rounded-full flex items-center justify-center shadow-lg hover:bg-red-500 hover:text-white transition-all duration-300 opacity-0 group-hover:opacity-100"
                                            onclick="toggleWishlist(<?php echo $product['id']; ?>)"
                                            title="Agregar a favoritos">
                                        <i class="fas fa-heart text-sm"></i>
                                    </button>
                                </div>
                                
                                <!-- View Details Button -->
                                <div class="absolute top-16 right-4 z-20">
                                    <button class="w-10 h-10 bg-white/90 backdrop-blur-sm rounded-full flex items-center justify-center shadow-lg hover:bg-blue-500 hover:text-white transition-all duration-300 opacity-0 group-hover:opacity-100"
                                            onclick="quickView(<?php echo $product['id']; ?>)"
                                            title="Ver detalles">
                                        <i class="fas fa-eye text-sm"></i>
                                    </button>
                                </div>
                                
                                <!-- Product Image -->
                                <div class="relative aspect-square overflow-hidden">
                                    <img src="<?php echo htmlspecialchars($productImage); ?>"
                                         alt="<?php echo htmlspecialchars($product['name']); ?>"
                                         class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
                                         loading="lazy"
                                         onerror="this.src='public/images/product-placeholder-1.svg'">
                                </div>
                                
                                <!-- Product Info -->
                                <div class="p-6">
                                    <!-- Brand -->
                                    <div class="text-sm text-gray-500 mb-2 font-medium">
                                        <?php echo htmlspecialchars($brandName); ?>
                                    </div>
                                    
                                    <!-- Product Name -->
                                    <h3 class="font-semibold text-lg text-gray-900 mb-3 line-clamp-2 group-hover:text-red-600 transition-colors">
                                        <?php echo htmlspecialchars($product['name']); ?>
                                    </h3>
                                    
                                    <!-- Prices -->
                                    <div class="flex items-center justify-between mb-4">
                                        <div class="flex items-center space-x-2">
                                            <span class="text-2xl font-bold text-red-600">
                                                $<?php echo number_format($product['price'], 0, ',', '.'); ?>
                                            </span>
                                            <span class="text-lg text-gray-400 line-through">
                                                $<?php echo number_format($product['compare_price'], 0, ',', '.'); ?>
                                            </span>
                                        </div>
                                    </div>
                                    
                                    <!-- Savings -->
                                    <div class="bg-green-50 border border-green-200 rounded-lg p-3 mb-4">
                                        <div class="text-center text-green-700 font-semibold">
                                            ¡Ahorras $<?php echo number_format($savings, 0, ',', '.'); ?>!
                                        </div>
                                    </div>
                                    
                                    <!-- Rating -->
                                    <div class="flex items-center justify-between mb-4">
                                        <div class="flex items-center space-x-1">
                                            <div class="flex text-yellow-400">
                                                <?php for ($i = 0; $i < 5; $i++): ?>
                                                <i class="fas fa-star text-sm"></i>
                                                <?php endfor; ?>
                                            </div>
                                            <span class="text-sm text-gray-500 ml-2">(4.9)</span>
                                        </div>
                                        
                                        <!-- Stock -->
                                        <?php if ($product['inventory_quantity'] > 0): ?>
                                            <span class="text-sm text-green-600 flex items-center">
                                                <i class="fas fa-check-circle mr-1"></i>
                                                Stock: <?php echo $product['inventory_quantity']; ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="text-sm text-red-600 flex items-center">
                                                <i class="fas fa-times-circle mr-1"></i>
                                                Agotado
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <!-- Add to Cart Button -->
                                    <div class="space-y-2">
                                        <?php if ($product['inventory_quantity'] > 0): ?>
                                        <button class="w-full bg-gradient-to-r from-red-500 to-pink-500 text-white py-3 rounded-xl font-semibold hover:shadow-lg transition-all duration-300 transform hover:scale-105"
                                                onclick="addToCart(<?php echo $product['id']; ?>)">
                                            <i class="fas fa-shopping-cart mr-2"></i>
                                            Agregar al carrito
                                        </button>
                                        <?php else: ?>
                                        <button class="w-full bg-gray-400 text-white py-3 rounded-xl font-semibold cursor-not-allowed" disabled>
                                            <i class="fas fa-times mr-2"></i>
                                            Agotado
                                        </button>
                                        <?php endif; ?>
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
                                <a href="?page=<?php echo $page-1; ?>&<?php echo http_build_query($filters); ?>"
                                   class="px-6 py-3 bg-white border border-gray-300 rounded-lg text-gray-700 hover:bg-red-50 hover:border-red-300 hover:text-red-600 transition-all duration-300 font-medium">
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
                                   class="px-4 py-3 rounded-lg transition-all duration-300 font-medium <?php echo $i == $page ? 'bg-red-500 text-white' : 'bg-white border border-gray-300 text-gray-700 hover:bg-red-50 hover:border-red-300 hover:text-red-600'; ?>">
                                    <?php echo $i; ?>
                                </a>
                                <?php endfor; ?>
                                
                                <?php if ($page < $totalPages): ?>
                                <a href="?page=<?php echo $page+1; ?>&<?php echo http_build_query($filters); ?>"
                                   class="px-6 py-3 bg-white border border-gray-300 rounded-lg text-gray-700 hover:bg-red-50 hover:border-red-300 hover:text-red-600 transition-all duration-300 font-medium">
                                    Siguiente
                                    <i class="fas fa-chevron-right ml-2"></i>
                                </a>
                                <?php endif; ?>
                            </nav>
                        </div>
                        <?php endif; ?>
                        
                    <?php else: ?>
                        <!-- No Offers Found -->
                        <div class="text-center py-16 md:py-24" data-aos="fade-up">
                            <div class="w-24 md:w-40 h-24 md:h-40 bg-gradient-to-r from-offer-100 to-orange-100 rounded-full flex items-center justify-center mx-auto mb-6 md:mb-10">
                                <i class="fas fa-tags text-3xl md:text-5xl text-offer-500 sparkle-icon"></i>
                            </div>
                            <h3 class="text-2xl md:text-4xl font-serif font-bold text-gray-800 mb-4 md:mb-6">No hay ofertas disponibles</h3>
                            <p class="text-lg md:text-2xl text-gray-600 mb-6 md:mb-10 font-light max-w-lg mx-auto px-4">
                                Intenta ajustar los filtros o revisa nuestro catálogo completo para encontrar productos increíbles.
                            </p>
                            <a href="catalogo.php" 
                               class="inline-flex items-center bg-gradient-to-r from-primary-500 to-secondary-500 text-white px-6 md:px-10 py-4 md:py-5 rounded-xl md:rounded-2xl font-semibold text-lg md:text-xl hover:shadow-xl transform hover:scale-105 transition-all duration-300 touch-button">
                                <i class="fas fa-arrow-left mr-2 md:mr-3"></i>
                                Ver todos los productos
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>

    <!-- Newsletter -->
    <section class="py-16 md:py-24 relative overflow-hidden">
        <div class="absolute inset-0 bg-gradient-to-r from-offer-500 via-orange-500 to-yellow-500"></div>
        <div class="absolute inset-0 bg-black/20"></div>
        
        <div class="container mx-auto px-4 text-center relative z-10">
            <!-- Mensaje de éxito o error -->
            <?php if (!empty($success_message)): ?>
            <div id="newsletter-message" class="mb-8 bg-green-100 border border-green-300 text-green-800 px-6 py-4 rounded-2xl max-w-2xl mx-auto backdrop-blur-sm shadow-lg" data-aos="fade-up">
                <div class="flex items-center justify-center">
                    <i class="fas fa-check-circle text-2xl mr-3"></i>
                    <div>
                        <h4 class="font-bold text-lg">¡Éxito!</h4>
                        <p><?php echo htmlspecialchars($success_message); ?></p>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($error_message)): ?>
            <div id="newsletter-message" class="mb-8 bg-red-100 border border-red-300 text-red-800 px-6 py-4 rounded-2xl max-w-2xl mx-auto backdrop-blur-sm shadow-lg" data-aos="fade-up">
                <div class="flex items-center justify-center">
                    <i class="fas fa-exclamation-triangle text-2xl mr-3"></i>
                    <div>
                        <h4 class="font-bold text-lg">Error</h4>
                        <p><?php echo htmlspecialchars($error_message); ?></p>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <h2 class="text-3xl md:text-5xl lg:text-7xl font-serif font-bold text-white mb-6 md:mb-10" data-aos="fade-up">
                ¡No te pierdas nuestras ofertas!
            </h2>
            <p class="text-lg md:text-xl lg:text-3xl text-white/90 mb-8 md:mb-16 max-w-4xl mx-auto font-light leading-relaxed px-4" data-aos="fade-up" data-aos-delay="100">
                Suscríbete y sé la primera en conocer nuestras ofertas exclusivas, descuentos especiales y lanzamientos únicos
            </p>
            
            <form id="newsletter-form" method="POST" action="" class="flex flex-col sm:flex-row gap-4 md:gap-8 justify-center max-w-3xl mx-auto px-4" data-aos="fade-up" data-aos-delay="200">
                <input type="hidden" name="csrf_token" value="<?php echo CSRFProtection::generateToken('newsletter'); ?>">
                <div class="flex-1 relative">
                    <input type="email" 
                           id="newsletter_email"
                           name="newsletter_email" 
                           placeholder="Tu email aquí..."
                           required
                           class="w-full px-6 md:px-10 py-4 md:py-6 rounded-xl md:rounded-2xl text-gray-900 focus:outline-none focus:ring-4 focus:ring-white/30 text-lg md:text-xl bg-white/95 backdrop-blur-sm shadow-lg border-2 border-transparent focus:border-white transition-all duration-300"
                           pattern="[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,}$">
                    <div id="email-error" class="hidden absolute -bottom-6 left-0 text-white/80 text-sm bg-red-500/20 px-3 py-1 rounded-lg backdrop-blur-sm">
                        Por favor, ingresa un email válido
                    </div>
                </div>
                <button type="submit" 
                        id="newsletter-submit"
                        class="bg-white text-offer-500 px-8 md:px-12 py-4 md:py-6 rounded-xl md:rounded-2xl font-semibold text-lg md:text-xl hover:shadow-2xl transform hover:scale-105 transition-all duration-300 shadow-lg touch-button disabled:opacity-50 disabled:cursor-not-allowed">
                    <span id="submit-text">
                        <i class="fas fa-bell mr-2 md:mr-3"></i>
                        <span class="hidden sm:inline">Suscribirse a </span>Ofertas
                    </span>
                </button>
            </form>
            <p class="text-sm md:text-lg mt-8 md:mt-12 text-white/75 font-light px-4">
                Al suscribirte recibirás ofertas exclusivas y podrás cancelar en cualquier momento.
                <br class="hidden md:block">
                <i class="fas fa-shield-alt mr-2 mt-2 md:mt-0"></i>
                Tu información está protegida y nunca será compartida.
            </p>
        </div>
    </section>
    
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
        
        // Cart functionality
        function addToCart(productId, quantity = 1) {
            const button = event.target;
            const originalText = button.innerHTML;
            
            // Show loading state
            button.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Agregando...';
            button.disabled = true;
            
            fetch('cart-add.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `product_id=${productId}&quantity=${quantity}&csrf_token=<?php echo CSRFProtection::generateToken("cart"); ?>`
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
                    
                    // Update cart count
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
        
        // Wishlist functionality
        function toggleWishlist(productId) {
            // Here you can implement wishlist functionality
            // For now, show a notification
            showNotification('Funcionalidad de favoritos próximamente', 'info');
        }
        
        // Quick view functionality
        function quickView(productId) {
            // Redirect to product details page
            window.location.href = `product.php?id=${productId}`;
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
        
        // Notification system
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

        // Real-time search functionality for offers
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
            fetch(`api/search.php?q=${encodeURIComponent(query)}&limit=5&offers_only=1`)
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
                    <div class="animate-spin rounded-full h-6 w-6 border-b-2 border-offer-500"></div>
                    <span class="ml-2 text-gray-600">Buscando ofertas...</span>
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
                (!data.brands || data.brands.length === 0)) {
                searchResultsContent.innerHTML = `
                    <div class="text-center py-4 text-gray-500">
                        <i class="fas fa-search text-2xl mb-2"></i>
                        <p>No se encontraron resultados en ofertas</p>
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
                            <i class="fas fa-star mr-2 text-offer-500"></i>Marcas con ofertas
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
                        <div class="flex items-center space-x-3 p-2 hover:bg-offer-50 rounded-lg cursor-pointer transition-colors" 
                             onclick="window.location.href='${brand.url}'">
                            <img src="${logoUrl}" 
                                 alt="${brand.name}" 
                                 class="w-8 h-8 object-contain rounded border border-gray-200"
                                 onerror="this.src='<?php echo BASE_URL; ?>/assets/images/placeholder-brand.svg'">
                            <div class="flex-1 min-w-0">
                                <h5 class="font-medium text-gray-900 truncate text-sm">${brand.name}</h5>
                                <p class="text-xs text-offer-600">${brand.product_count || 0} productos en oferta</p>
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
                            <i class="fas fa-tag mr-2 text-offer-500"></i>Productos en oferta
                        </h4>
                        <div class="space-y-2">
                `;
                
                data.products.forEach(product => {
                    const price = new Intl.NumberFormat('es-CO', {
                        style: 'currency',
                        currency: 'COP',
                        minimumFractionDigits: 0
                    }).format(product.price);

                    const comparePrice = product.compare_price ? new Intl.NumberFormat('es-CO', {
                        style: 'currency',
                        currency: 'COP',
                        minimumFractionDigits: 0
                    }).format(product.compare_price) : null;

                    const discount = product.compare_price && product.compare_price > product.price ? 
                        Math.round(((product.compare_price - product.price) / product.compare_price) * 100) : 0;

                    // Fix image URL
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
                            <div class="relative">
                                <img src="${imageUrl}" 
                                     alt="${product.name}" 
                                     class="w-12 h-12 object-cover rounded-lg"
                                     onerror="this.src='<?php echo BASE_URL; ?>/assets/images/placeholder-product.svg'">
                                ${discount > 0 ? `<span class="absolute -top-1 -right-1 bg-red-500 text-white text-xs px-1 rounded-full">-${discount}%</span>` : ''}
                            </div>
                            <div class="flex-1 min-w-0">
                                <h4 class="font-medium text-gray-900 truncate">${product.name}</h4>
                                <p class="text-sm text-gray-500">${product.brand_name || 'Sin marca'}</p>
                                <div class="flex items-center space-x-2">
                                    <p class="text-sm font-semibold text-offer-600">${price}</p>
                                    ${comparePrice ? `<p class="text-xs text-gray-400 line-through">${comparePrice}</p>` : ''}
                                </div>
                            </div>
                            <div class="flex items-center space-x-2">
                                <button onclick="event.stopPropagation(); quickView(${product.id})" 
                                        class="w-8 h-8 bg-offer-50 hover:bg-offer-100 rounded-full flex items-center justify-center transition-colors"
                                        title="Vista rápida">
                                    <i class="fas fa-eye text-offer-600 text-sm"></i>
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
                    <a href="catalogo.php?q=${encodeURIComponent(searchInput.value)}&offers=1" 
                       class="block text-center bg-gradient-to-r from-offer-500 to-orange-500 text-white py-3 rounded-xl font-semibold hover:from-offer-600 hover:to-orange-600 transition-all duration-300">
                        <i class="fas fa-search mr-2"></i>Ver todas las ofertas (${data.total || 0})
                    </a>
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

        // Mobile filters functionality
        const mobileFiltersBtn = document.getElementById('mobile-filters-btn');
        const mobileFiltersModal = document.getElementById('mobile-filters-modal');
        const closeMobileFilters = document.getElementById('close-mobile-filters');
        
        mobileFiltersBtn?.addEventListener('click', () => {
            mobileFiltersModal.classList.add('active');
            document.body.classList.add('modal-open');
        });
        
        closeMobileFilters?.addEventListener('click', () => {
            mobileFiltersModal.classList.remove('active');
            document.body.classList.remove('modal-open');
        });
        
        mobileFiltersModal?.addEventListener('click', (e) => {
            if (e.target === mobileFiltersModal) {
                mobileFiltersModal.classList.remove('active');
                document.body.classList.remove('modal-open');
            }
        });

        // Countdown Timer - Datos reales desde la base de datos
        function startCountdown() {
            <?php if ($offerTimeLeft && $activeOffer): ?>
            const countdownDate = new Date('<?php echo $activeOffer['end_date']; ?>').getTime();
            
            const timer = setInterval(function() {
                const now = new Date().getTime();
                const distance = countdownDate - now;
                
                const days = Math.floor(distance / (1000 * 60 * 60 * 24));
                const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
                const seconds = Math.floor((distance % (1000 * 60)) / 1000);
                
                document.getElementById("days").innerHTML = String(days).padStart(2, '0');
                document.getElementById("hours").innerHTML = String(hours).padStart(2, '0');
                document.getElementById("minutes").innerHTML = String(minutes).padStart(2, '0');
                document.getElementById("seconds").innerHTML = String(seconds).padStart(2, '0');
                
                if (distance < 0) {
                    clearInterval(timer);
                    document.getElementById("countdown").innerHTML = "<div class='text-2xl md:text-3xl font-bold text-offer-500'>¡Ofertas terminadas!</div>";
                    // Recargar la página para actualizar el estado de las ofertas
                    setTimeout(() => location.reload(), 2000);
                }
            }, 1000);
            <?php endif; ?>
        }
        
        <?php if ($offerTimeLeft && $activeOffer): ?>
        startCountdown();
        <?php endif; ?>

        // Auto-submit filters form when changed (desktop only)
        document.querySelectorAll('#filters-form select, #filters-form input').forEach(element => {
            element.addEventListener('change', function() {
                // Add loading state to form
                const form = document.getElementById('filters-form');
                const submitBtn = form?.querySelector('button[type="submit"]');
                if (submitBtn) {
                    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-3"></i>Aplicando...';
                    submitBtn.disabled = true;
                }
                
                setTimeout(() => {
                    document.getElementById('filters-form')?.submit();
                }, 300);
            });
        });

        // Newsletter form functionality
        document.addEventListener('DOMContentLoaded', function() {
            const newsletterForm = document.getElementById('newsletter-form');
            const emailInput = document.getElementById('newsletter_email');
            const submitButton = document.getElementById('newsletter-submit');
            const submitText = document.getElementById('submit-text');
            const emailError = document.getElementById('email-error');
            const newsletterMessage = document.getElementById('newsletter-message');

            // Auto-hide messages after 8 seconds
            if (newsletterMessage) {
                setTimeout(() => {
                    newsletterMessage.style.transition = 'all 0.5s ease';
                    newsletterMessage.style.opacity = '0';
                    newsletterMessage.style.transform = 'translateY(-20px)';
                    setTimeout(() => newsletterMessage.remove(), 500);
                }, 8000);
            }

            // Email validation function
            function validateEmail(email) {
                const emailRegex = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
                return emailRegex.test(email);
            }

            // Show/hide email error
            function showEmailError(show = true) {
                if (show) {
                    emailError.classList.remove('hidden');
                    emailInput.style.borderColor = '#ef4444';
                    emailInput.style.backgroundColor = 'rgba(239, 68, 68, 0.1)';
                } else {
                    emailError.classList.add('hidden');
                    emailInput.style.borderColor = 'transparent';
                    emailInput.style.backgroundColor = 'rgba(255, 255, 255, 0.95)';
                }
            }

            // Real-time email validation
            emailInput.addEventListener('input', function() {
                const email = this.value.trim();
                if (email && !validateEmail(email)) {
                    showEmailError(true);
                } else {
                    showEmailError(false);
                }
            });

            // Form submission
            if (newsletterForm) {
                newsletterForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    
                    const email = emailInput.value.trim();
                    
                    // Validate email
                    if (!email) {
                        showEmailError(true);
                        emailError.textContent = 'Por favor, ingresa tu email';
                        emailInput.focus();
                        return;
                    }
                    
                    if (!validateEmail(email)) {
                        showEmailError(true);
                        emailError.textContent = 'Por favor, ingresa un email válido';
                        emailInput.focus();
                        return;
                    }
                    
                    // Show loading state
                    const originalText = submitText.innerHTML;
                    submitText.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Suscribiendo...';
                    submitButton.disabled = true;
                    showEmailError(false);
                    
                    // Simulate processing delay for better UX
                    setTimeout(() => {
                        // Submit the form
                        const formData = new FormData(newsletterForm);
                        
                        fetch(window.location.href, {
                            method: 'POST',
                            body: formData
                        })
                        .then(response => response.text())
                        .then(html => {
                            // Parse response to check for success/error messages
                            const parser = new DOMParser();
                            const doc = parser.parseFromString(html, 'text/html');
                            const messageElement = doc.getElementById('newsletter-message');
                            
                            if (messageElement) {
                                // Show success/error message
                                const messageHTML = messageElement.outerHTML;
                                const section = newsletterForm.closest('section');
                                const container = section.querySelector('.container');
                                
                                // Insert message at the top
                                container.insertAdjacentHTML('afterbegin', messageHTML);
                                
                                // If success, clear form
                                if (messageElement.classList.contains('bg-green-100')) {
                                    emailInput.value = '';
                                    submitText.innerHTML = '<i class="fas fa-check mr-2"></i>¡Suscrito!';
                                    
                                    // Show celebration effect
                                    setTimeout(() => {
                                        submitText.innerHTML = originalText;
                                        submitButton.disabled = false;
                                    }, 3000);
                                } else {
                                    // Error case
                                    submitText.innerHTML = originalText;
                                    submitButton.disabled = false;
                                }
                            } else {
                                // Fallback: reload page
                                window.location.reload();
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            submitText.innerHTML = originalText;
                            submitButton.disabled = false;
                            showNotification('Error de conexión. Inténtalo de nuevo.', 'error');
                        });
                    }, 1000);
                });
            }

            // Enhanced form interactions
            emailInput.addEventListener('focus', function() {
                this.style.transform = 'translateY(-2px)';
                this.style.boxShadow = '0 8px 25px rgba(255, 255, 255, 0.2)';
            });

            emailInput.addEventListener('blur', function() {
                this.style.transform = 'translateY(0)';
                this.style.boxShadow = '';
            });

            // Update cart count on page load
            if (typeof updateCartCount === 'function') {
                updateCartCount();
            }
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

        // Lazy loading for images
        if ('IntersectionObserver' in window) {
            const imageObserver = new IntersectionObserver((entries, observer) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const img = entry.target;
                        img.src = img.dataset.src || img.src;
                        img.classList.remove('loading-skeleton');
                        observer.unobserve(img);
                    }
                });
            });

            document.querySelectorAll('img[loading="lazy"]').forEach(img => {
                imageObserver.observe(img);
            });
        }

        // Performance optimization: Debounce scroll events
        let ticking = false;
        function updateScrollEffects() {
            // Add scroll-based animations here if needed
            ticking = false;
        }

        window.addEventListener('scroll', function() {
            if (!ticking) {
                requestAnimationFrame(updateScrollEffects);
                ticking = true;
            }
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
    </script>
    
    <!-- Main JavaScript -->
    <script src="assets/js/main.js"></script>
</body>
</html>
