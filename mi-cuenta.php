<?php
session_start();
require_once 'config/config.php';
require_once 'config/database.php';
require_once 'config/global-settings.php';
require_once 'includes/CSRFProtection.php';

// Verificar si el usuario está autenticado
if (!isset($_SESSION['user_id'])) {
    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
    header('Location: login.php');
    exit;
}

$database = new Database();
$db = $database->getConnection();

// Obtener información del cliente
$customerQuery = "SELECT * FROM customers WHERE id = ?";
$customerStmt = $db->prepare($customerQuery);
$customerStmt->execute([$_SESSION['user_id']]);
$customer = $customerStmt->fetch();

// Sección actual (por defecto: dashboard)
$section = isset($_GET['section']) ? $_GET['section'] : 'dashboard';

// Inicializar variables
$welcomeMessage = isset($_GET['welcome']) ? true : false;
$orders = [];
$success_message = null;
$error_message = null;

// Obtener estadísticas del usuario
$ordersCountQuery = "SELECT COUNT(*) as total FROM orders WHERE customer_id = ?";
$ordersCountStmt = $db->prepare($ordersCountQuery);
$ordersCountStmt->execute([$_SESSION['user_id']]);
$ordersCount = $ordersCountStmt->fetch()['total'];

$wishlistCountQuery = "SELECT COUNT(*) as total FROM wishlists WHERE customer_id = ?";
$wishlistCountStmt = $db->prepare($wishlistCountQuery);
$wishlistCountStmt->execute([$_SESSION['user_id']]);
$wishlistCount = $wishlistCountStmt->fetch()['total'];

// Obtener todos los pedidos
$ordersQuery = "SELECT o.*, GROUP_CONCAT(CONCAT(oi.quantity, 'x ', p.name) SEPARATOR ', ') as items
                FROM orders o 
                LEFT JOIN order_items oi ON o.id = oi.order_id
                LEFT JOIN products p ON oi.product_id = p.id
                WHERE o.customer_id = ?
                GROUP BY o.id
                ORDER BY o.created_at DESC";
$ordersStmt = $db->prepare($ordersQuery);
$ordersStmt->execute([$_SESSION['user_id']]);
$orders = $ordersStmt->fetchAll();

// Obtener pedidos recientes
$recentOrdersQuery = "SELECT o.*, GROUP_CONCAT(CONCAT(oi.quantity, 'x ', p.name) SEPARATOR ', ') as items
                      FROM orders o 
                      LEFT JOIN order_items oi ON o.id = oi.order_id
                      LEFT JOIN products p ON oi.product_id = p.id
                      WHERE o.customer_id = ?
                      GROUP BY o.id
                      ORDER BY o.created_at DESC LIMIT 5";
$recentOrdersStmt = $db->prepare($recentOrdersQuery);
$recentOrdersStmt->execute([$_SESSION['user_id']]);
$recentOrders = $recentOrdersStmt->fetchAll();

// Obtener lista de deseos
$wishlistQuery = "SELECT w.*, p.name, p.price, p.compare_price, p.main_image, p.inventory_quantity, b.name as brand_name
                  FROM wishlists w 
                  JOIN products p ON w.product_id = p.id
                  LEFT JOIN brands b ON p.brand_id = b.id
                  WHERE w.customer_id = ?
                  ORDER BY w.created_at DESC";
$wishlistStmt = $db->prepare($wishlistQuery);
$wishlistStmt->execute([$_SESSION['user_id']]);
$wishlistItems = $wishlistStmt->fetchAll();

// Procesar actualizaciones de perfil
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $updateQuery = "UPDATE customers SET first_name = ?, last_name = ?, email = ?, phone = ? WHERE id = ?";
    $updateStmt = $db->prepare($updateQuery);
    
    if ($updateStmt->execute([
        $_POST['first_name'],
        $_POST['last_name'], 
        $_POST['email'],
        $_POST['phone'],
        $_SESSION['user_id']
    ])) {
        $success_message = "Perfil actualizado correctamente";
        // Recargar datos del cliente
        $customerStmt->execute([$_SESSION['user_id']]);
        $customer = $customerStmt->fetch();
    }
}

// Procesar cambio de contraseña
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $currentPassword = $_POST['current_password'];
    $newPassword = $_POST['new_password'];
    $confirmPassword = $_POST['confirm_password'];
    
    if ($newPassword === $confirmPassword) {
        if (password_verify($currentPassword, $customer['password'])) {
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $passwordQuery = "UPDATE customers SET password = ? WHERE id = ?";
            $passwordStmt = $db->prepare($passwordQuery);
            
            if ($passwordStmt->execute([$hashedPassword, $_SESSION['user_id']])) {
                $success_message = "Contraseña cambiada correctamente";
            } else {
                $error_message = "Error al cambiar la contraseña";
            }
        } else {
            $error_message = "La contraseña actual no es correcta";
        }
    } else {
        $error_message = "Las nuevas contraseñas no coinciden";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Cuenta - OdiseaStore</title>
    <meta name="description" content="Gestiona tu cuenta, revisa tus pedidos y actualiza tu información personal en OdiseaStore.">
    
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

        /* Mobile-specific styles with Android optimization */
        @media (max-width: 768px) {
            body {
                font-size: 14px;
                line-height: 1.5;
                overflow-x: hidden;
            }
            
            .mobile-hero-bg {
                background: linear-gradient(135deg, #fdf8f6 0%, #f2e8e5 50%, #eaddd7 100%);
            }
            
            .mobile-card-shadow {
                box-shadow: 0 4px 12px rgba(176, 141, 128, 0.15);
            }
            
            .mobile-gradient {
                background: linear-gradient(135deg, #b08d80 0%, #c4a575 100%);
            }
            
            .touch-target {
                min-height: 48px;
                min-width: 48px;
                padding: 12px 16px;
            }

            /* Mobile navigation improvements */
            #mobile-menu-toggle {
                cursor: pointer;
                user-select: none;
                -webkit-user-select: none;
                -webkit-tap-highlight-color: rgba(176, 141, 128, 0.2);
            }
            
            #mobile-menu-toggle:active {
                transform: scale(0.98);
            }
            
            #mobile-menu {
                transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
                transform-origin: top;
                max-height: 0;
                overflow: hidden;
                opacity: 0;
            }
            
            #mobile-menu:not(.hidden) {
                max-height: 600px;
                opacity: 1;
            }
            
            #mobile-menu.menu-open {
                max-height: 600px;
                opacity: 1;
                transform: scaleY(1);
            }
            
            #mobile-menu-icon {
                transition: transform 0.3s ease;
            }
            
            #mobile-menu-icon.rotate-180 {
                transform: rotate(180deg);
            }
            
            /* Mobile form improvements - Android specific */
            .form-input {
                font-size: 16px; /* Prevents zoom on mobile browsers */
                padding: 16px 20px;
                border-radius: 12px;
                box-shadow: 0 2px 8px rgba(0,0,0,0.1);
                -webkit-appearance: none;
                -moz-appearance: none;
                appearance: none;
            }
            
            .form-input:focus {
                outline: none;
                border-color: #b08d80;
                box-shadow: 0 0 0 3px rgba(176, 141, 128, 0.2);
            }
            
            /* Mobile cards responsive */
            .stats-card-mobile {
                padding: 1.5rem;
                min-height: 140px;
                border-radius: 20px;
            }
            
            /* Container adjustments for Android */
            .container {
                padding-left: 16px;
                padding-right: 16px;
                max-width: 100%;
            }
            
            /* Typography improvements for mobile */
            .responsive-text-4xl { 
                font-size: 1.75rem !important; 
                line-height: 1.3;
            }
            .responsive-text-3xl { 
                font-size: 1.5rem !important; 
                line-height: 1.3;
            }
            .responsive-text-2xl { 
                font-size: 1.25rem !important; 
                line-height: 1.4;
            }
            .responsive-text-xl { 
                font-size: 1.125rem !important; 
            }
            
            /* Mobile spacing improvements */
            .mobile-spacing {
                padding: 1rem;
                margin-bottom: 1rem;
            }
            
            /* Grid improvements for Android */
            .mobile-grid {
                grid-template-columns: 1fr;
                gap: 1rem;
            }
            
            .mobile-grid-2 {
                grid-template-columns: 1fr 1fr;
                gap: 0.75rem;
            }
            
            /* Button improvements for Android */
            .mobile-button {
                padding: 14px 24px;
                font-size: 16px;
                border-radius: 12px;
                min-height: 48px;
                display: flex;
                align-items: center;
                justify-content: center;
                touch-action: manipulation;
            }
            
            /* Card improvements for Android */
            .mobile-card {
                border-radius: 16px;
                padding: 1.25rem;
                margin-bottom: 1rem;
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            }
            
            /* Android specific fixes */
            * {
                -webkit-tap-highlight-color: rgba(176, 141, 128, 0.2);
            }
            
            input, select, textarea {
                -webkit-appearance: none;
                -webkit-border-radius: 12px;
                border-radius: 12px;
            }
            
            /* Fix for Android viewport issues */
            .viewport-fix {
                width: 100%;
                min-width: 320px;
                max-width: 100vw;
                overflow-x: hidden;
            }
        }
        
        /* Extra small screens (phones in portrait) */
        @media (max-width: 480px) {
            .container {
                padding-left: 12px;
                padding-right: 12px;
            }
            
            .mobile-spacing {
                padding: 0.75rem;
            }
            
            .mobile-card {
                padding: 1rem;
                border-radius: 12px;
            }
            
            .mobile-button {
                padding: 12px 20px;
                font-size: 14px;
            }
            
            .stats-card-mobile {
                padding: 1rem;
                min-height: 120px;
            }
            
            .responsive-text-4xl { 
                font-size: 1.5rem !important; 
            }
            .responsive-text-3xl { 
                font-size: 1.25rem !important; 
            }
        }

        /* Enhanced animations */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        @keyframes slideInRight {
            from {
                opacity: 0;
                transform: translateX(30px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }
        
        @keyframes pulseGlow {
            0%, 100% {
                box-shadow: 0 0 20px rgba(176, 141, 128, 0.3);
            }
            50% {
                box-shadow: 0 0 30px rgba(176, 141, 128, 0.6);
            }
        }

        /* Enhanced input styles */
        .form-input {
            transition: all 0.3s ease;
            border-radius: 12px;
            border: 2px solid #e5e7eb;
        }
        
        .form-input:focus {
            border-color: #b08d80;
            box-shadow: 0 0 0 3px rgba(176, 141, 128, 0.1);
            transform: translateY(-1px);
        }
        
        .form-input.success {
            border-color: #10b981;
            background-color: #f0fdf4;
        }
        
        .form-input.error {
            border-color: #ef4444;
            background-color: #fef2f2;
            animation: shake 0.5s ease-in-out;
        }
        
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }

        /* Enhanced stats cards */
        .stats-card-enhanced {
            position: relative;
            overflow: hidden;
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .stats-card-enhanced::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.6s;
        }
        
        .stats-card-enhanced:hover::before {
            left: 100%;
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

        /* Button enhancements */
        .btn-enhanced {
            position: relative;
            overflow: hidden;
            transition: all 0.3s ease;
            transform: translateZ(0);
        }
        
        .btn-enhanced:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
        }
        
        .btn-enhanced:active {
            transform: translateY(0);
        }

        /* Responsive text */
        @media (max-width: 640px) {
            .responsive-text-xl { font-size: 1.125rem; }
            .responsive-text-2xl { font-size: 1.25rem; }
            .responsive-text-3xl { font-size: 1.5rem; }
            .responsive-text-4xl { font-size: 1.875rem; }
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

        /* Account specific styles */
        .sidebar-item {
            position: relative;
            transition: all 0.3s ease;
        }
        
        .sidebar-item::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            height: 100%;
            width: 4px;
            background: linear-gradient(135deg, #b08d80, #c4a575);
            transform: scaleY(0);
            transition: transform 0.3s ease;
        }
        
        .sidebar-item.active::before,
        .sidebar-item:hover::before {
            transform: scaleY(1);
        }

        .form-input {
            transition: all 0.3s ease;
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
        }
        
        .form-input:focus {
            background: rgba(255, 255, 255, 1);
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(176, 141, 128, 0.15);
        }

        .notification {
            animation: slideInDown 0.5s ease-out;
        }
        
        @keyframes slideInDown {
            from {
                transform: translateY(-100%);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .stats-card {
            background: linear-gradient(135deg, rgba(255,255,255,0.9) 0%, rgba(255,255,255,0.7) 100%);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255,255,255,0.3);
        }

        .order-timeline {
            position: relative;
        }
        
        .order-timeline::before {
            content: '';
            position: absolute;
            left: 20px;
            top: 0;
            bottom: 0;
            width: 2px;
            background: linear-gradient(to bottom, #b08d80, #c4a575);
        }
        
        .timeline-dot {
            position: relative;
            z-index: 1;
            background: white;
            border: 3px solid #b08d80;
        }

        .product-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .product-card:hover {
            transform: translateY(-12px) scale(1.02);
            box-shadow: 0 30px 60px rgba(0, 0, 0, 0.15);
            background: rgba(255, 255, 255, 1);
        }

        .wishlist-heart {
            transition: all 0.3s ease;
        }
        
        .wishlist-heart:hover {
            transform: scale(1.2);
            color: #b08d80;
        }
    </style>
</head>

<body class="font-sans bg-white overflow-x-hidden viewport-fix">
    <!-- Header -->
    <?php include 'includes/header.php'; ?>

    <!-- Main Content -->
    <main class="pt-20 md:pt-32">
        <!-- Breadcrumb -->
        <section class="bg-mesh py-6 md:py-8">
            <div class="container mx-auto px-4">
                <nav class="text-sm" data-aos="fade-right">
                    <ol class="flex items-center space-x-2 md:space-x-3 flex-wrap">
                        <li>
                            <a href="index.php" class="text-gray-600 hover:text-primary-600 transition-colors duration-300 flex items-center">
                                <i class="fas fa-home mr-1 text-sm"></i>
                                <span class="text-xs md:text-sm">Inicio</span>
                            </a>
                        </li>
                        <li class="text-gray-400">
                            <i class="fas fa-chevron-right text-xs"></i>
                        </li>
                        <li class="text-primary-600 font-semibold flex items-center">
                            <i class="fas fa-user-circle mr-1 text-sm"></i>
                            <span class="text-xs md:text-sm">Mi Cuenta</span>
                        </li>
                    </ol>
                </nav>
            </div>
        </section>

        <!-- Account Dashboard -->
        <section class="py-8 md:py-16 bg-gradient-to-br from-luxury-rose/30 via-white to-luxury-gold/30 relative overflow-hidden">
            <!-- Background Elements -->
            <div class="absolute inset-0 hidden md:block">
                <div class="absolute top-20 left-10 w-72 h-72 bg-gradient-to-r from-primary-200/30 to-secondary-200/30 rounded-full blur-3xl animate-float"></div>
                <div class="absolute bottom-20 right-10 w-96 h-96 bg-gradient-to-r from-secondary-200/30 to-accent-200/30 rounded-full blur-3xl animate-float" style="animation-delay: 2s;"></div>
            </div>

            <div class="container mx-auto px-4 relative z-10">
                <!-- Notifications -->
                <?php if (isset($success_message)): ?>
                <div class="mb-6 md:mb-8 notification" data-aos="fade-down">
                    <div class="glass-effect border border-green-200 text-green-800 px-4 md:px-6 py-3 md:py-4 rounded-xl md:rounded-2xl mobile-card-shadow">
                        <div class="flex items-center">
                            <div class="w-8 h-8 md:w-10 md:h-10 bg-green-100 rounded-full flex items-center justify-center mr-3 md:mr-4 flex-shrink-0">
                                <i class="fas fa-check-circle text-green-600 text-sm md:text-base"></i>
                            </div>
                            <div>
                                <h4 class="font-semibold text-sm md:text-base">¡Éxito!</h4>
                                <p class="text-xs md:text-sm"><?php echo $success_message; ?></p>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <?php if (isset($error_message)): ?>
                <div class="mb-6 md:mb-8 notification" data-aos="fade-down">
                    <div class="glass-effect border border-red-200 text-red-800 px-4 md:px-6 py-3 md:py-4 rounded-xl md:rounded-2xl mobile-card-shadow">
                        <div class="flex items-center">
                            <div class="w-8 h-8 md:w-10 md:h-10 bg-red-100 rounded-full flex items-center justify-center mr-3 md:mr-4 flex-shrink-0">
                                <i class="fas fa-exclamation-circle text-red-600 text-sm md:text-base"></i>
                            </div>
                            <div>
                                <h4 class="font-semibold text-sm md:text-base">Error</h4>
                                <p class="text-xs md:text-sm"><?php echo $error_message; ?></p>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <div class="flex flex-col xl:flex-row gap-6 md:gap-8">
                    <!-- Mobile Navigation Toggle -->
                    <div class="xl:hidden mb-4 md:mb-6">
                        <button id="mobile-menu-toggle" onclick="toggleMobileMenu()" class="w-full flex items-center justify-between bg-white/90 backdrop-blur-sm border border-gray-200 rounded-xl md:rounded-2xl p-3 md:p-4 mobile-card-shadow hover:shadow-xl transition-all duration-300 touch-target">
                            <div class="flex items-center space-x-3">
                                <div class="w-8 h-8 md:w-10 md:h-10 bg-gradient-to-r from-primary-500 to-secondary-500 rounded-full flex items-center justify-center flex-shrink-0">
                                    <i class="fas fa-user text-white text-sm md:text-base"></i>
                                </div>
                                <div class="text-left">
                                    <span class="font-semibold text-gray-900 text-sm md:text-base block">Menú de Cuenta</span>
                                    <div class="text-xs md:text-sm text-gray-500">Toca para navegar</div>
                                </div>
                            </div>
                            <i class="fas fa-chevron-down transform transition-transform duration-300 text-gray-400" id="mobile-menu-icon"></i>
                        </button>
                        
                        <!-- Mobile Navigation Menu -->
                        <div id="mobile-menu" class="hidden mt-3 md:mt-4 bg-white/95 backdrop-blur-sm border border-gray-200 rounded-xl md:rounded-2xl shadow-xl overflow-hidden">
                            <div class="p-3 md:p-4">
                                <div class="grid grid-cols-2 gap-2 md:gap-3">
                                    <a href="?section=dashboard" class="flex flex-col items-center p-3 rounded-xl transition-all duration-300 touch-target <?php echo $section === 'dashboard' ? 'bg-gradient-to-br from-primary-50 to-secondary-50 text-primary-700' : 'hover:bg-gray-50'; ?>">
                                        <div class="w-10 h-10 rounded-xl flex items-center justify-center mb-2 <?php echo $section === 'dashboard' ? 'bg-gradient-to-br from-primary-500 to-secondary-500 text-white' : 'bg-gray-100'; ?>">
                                            <i class="fas fa-home text-sm"></i>
                                        </div>
                                        <span class="text-xs font-medium text-center">Dashboard</span>
                                    </a>
                                    <a href="?section=orders" class="flex flex-col items-center p-3 rounded-xl transition-all duration-300 touch-target <?php echo $section === 'orders' ? 'bg-gradient-to-br from-primary-50 to-secondary-50 text-primary-700' : 'hover:bg-gray-50'; ?>">
                                        <div class="w-10 h-10 rounded-xl flex items-center justify-center mb-2 <?php echo $section === 'orders' ? 'bg-gradient-to-br from-primary-500 to-secondary-500 text-white' : 'bg-gray-100'; ?> relative">
                                            <i class="fas fa-shopping-bag text-sm"></i>
                                            <?php if ($ordersCount > 0): ?>
                                            <span class="absolute -top-1 -right-1 bg-red-500 text-white rounded-full w-4 h-4 flex items-center justify-center" style="font-size: 10px;"><?php echo $ordersCount > 9 ? '9+' : $ordersCount; ?></span>
                                            <?php endif; ?>
                                        </div>
                                        <span class="text-xs font-medium text-center">Pedidos</span>
                                    </a>
                                    <a href="?section=wishlist" class="flex flex-col items-center p-3 rounded-xl transition-all duration-300 touch-target <?php echo $section === 'wishlist' ? 'bg-gradient-to-br from-primary-50 to-secondary-50 text-primary-700' : 'hover:bg-gray-50'; ?>">
                                        <div class="w-10 h-10 rounded-xl flex items-center justify-center mb-2 <?php echo $section === 'wishlist' ? 'bg-gradient-to-br from-primary-500 to-secondary-500 text-white' : 'bg-gray-100'; ?> relative">
                                            <i class="fas fa-heart text-sm"></i>
                                            <?php if ($wishlistCount > 0): ?>
                                            <span class="absolute -top-1 -right-1 bg-pink-500 text-white rounded-full w-4 h-4 flex items-center justify-center" style="font-size: 10px;"><?php echo $wishlistCount > 9 ? '9+' : $wishlistCount; ?></span>
                                            <?php endif; ?>
                                        </div>
                                        <span class="text-xs font-medium text-center">Favoritos</span>
                                    </a>
                                    <a href="?section=profile" class="flex flex-col items-center p-3 rounded-xl transition-all duration-300 touch-target <?php echo $section === 'profile' ? 'bg-gradient-to-br from-primary-50 to-secondary-50 text-primary-700' : 'hover:bg-gray-50'; ?>">
                                        <div class="w-10 h-10 rounded-xl flex items-center justify-center mb-2 <?php echo $section === 'profile' ? 'bg-gradient-to-br from-primary-500 to-secondary-500 text-white' : 'bg-gray-100'; ?>">
                                            <i class="fas fa-user-edit text-sm"></i>
                                        </div>
                                        <span class="text-xs font-medium text-center">Perfil</span>
                                    </a>
                                </div>
                                <div class="mt-3 pt-3 border-t border-gray-200 space-y-1">
                                    <a href="?section=password" class="flex items-center p-2 rounded-lg transition-all duration-300 touch-target <?php echo $section === 'password' ? 'bg-gradient-to-br from-primary-50 to-secondary-50 text-primary-700' : 'hover:bg-gray-50'; ?>">
                                        <div class="w-8 h-8 rounded-lg flex items-center justify-center mr-3 flex-shrink-0 <?php echo $section === 'password' ? 'bg-gradient-to-br from-primary-500 to-secondary-500 text-white' : 'bg-gray-100'; ?>">
                                            <i class="fas fa-lock text-xs"></i>
                                        </div>
                                        <span class="font-medium text-sm">Cambiar Contraseña</span>
                                    </a>
                                    <a href="logout.php" class="flex items-center p-2 rounded-lg transition-all duration-300 touch-target text-red-600 hover:bg-red-50">
                                        <div class="w-8 h-8 rounded-lg flex items-center justify-center mr-3 flex-shrink-0 bg-red-100">
                                            <i class="fas fa-sign-out-alt text-xs"></i>
                                        </div>
                                        <span class="font-medium text-sm">Cerrar Sesión</span>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Sidebar (Hidden on mobile) -->
                    <div class="xl:w-1/4 hidden xl:block">
                        <div class="glass-effect rounded-3xl luxury-shadow overflow-hidden sticky top-8" data-aos="fade-right">
                            <!-- User Profile Header -->
                            <div class="bg-gradient-to-r from-primary-500 to-secondary-500 p-8 text-white relative overflow-hidden">
                                <div class="absolute inset-0 bg-black/20"></div>
                                <div class="relative z-10">
                                    <div class="flex items-center space-x-4 mb-4">
                                        <div class="w-20 h-20 bg-white/20 rounded-full flex items-center justify-center backdrop-blur-sm border border-white/30">
                                            <i class="fas fa-user text-3xl"></i>
                                        </div>
                                        <div>
                                            <h3 class="text-xl font-bold font-serif"><?php echo htmlspecialchars($customer['first_name'] . ' ' . $customer['last_name']); ?></h3>
                                            <p class="text-white/80 text-sm"><?php echo htmlspecialchars($customer['email']); ?></p>
                                            <div class="flex items-center mt-2">
                                                <div class="w-2 h-2 bg-green-400 rounded-full mr-2 animate-pulse"></div>
                                                <span class="text-xs text-white/90">En línea</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="grid grid-cols-2 gap-4 mt-6">
                                        <div class="text-center">
                                            <div class="text-2xl font-bold"><?php echo $ordersCount; ?></div>
                                            <div class="text-xs text-white/80">Pedidos</div>
                                        </div>
                                        <div class="text-center">
                                            <div class="text-2xl font-bold"><?php echo $wishlistCount; ?></div>
                                            <div class="text-xs text-white/80">Favoritos</div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Navigation Menu -->
                            <nav class="p-6">
                                <ul class="space-y-2">
                                    <li>
                                        <a href="?section=dashboard" class="sidebar-item flex items-center space-x-4 px-4 py-4 rounded-xl transition-all duration-300 group <?php echo $section === 'dashboard' ? 'active bg-gradient-to-r from-primary-50 to-secondary-50 text-primary-700' : 'text-gray-600 hover:bg-gray-50'; ?>">
                                            <div class="w-10 h-10 rounded-lg flex items-center justify-center <?php echo $section === 'dashboard' ? 'bg-gradient-to-br from-primary-500 to-secondary-500 text-white' : 'bg-gray-100 text-gray-500 group-hover:bg-primary-100 group-hover:text-primary-600'; ?> transition-all duration-300">
                                                <i class="fas fa-home"></i>
                                            </div>
                                            <div>
                                                <span class="font-semibold">Dashboard</span>
                                                <div class="text-xs text-gray-500">Panel principal</div>
                                            </div>
                                        </a>
                                    </li>
                                    <li>
                                        <a href="?section=orders" class="sidebar-item flex items-center space-x-4 px-4 py-4 rounded-xl transition-all duration-300 group <?php echo $section === 'orders' ? 'active bg-gradient-to-r from-primary-50 to-secondary-50 text-primary-700' : 'text-gray-600 hover:bg-gray-50'; ?>">
                                            <div class="w-10 h-10 rounded-lg flex items-center justify-center <?php echo $section === 'orders' ? 'bg-gradient-to-br from-primary-500 to-secondary-500 text-white' : 'bg-gray-100 text-gray-500 group-hover:bg-primary-100 group-hover:text-primary-600'; ?> transition-all duration-300">
                                                <i class="fas fa-shopping-bag"></i>
                                            </div>
                                            <div class="flex-1">
                                                <span class="font-semibold">Mis Pedidos</span>
                                                <div class="text-xs text-gray-500">Historial de compras</div>
                                            </div>
                                            <?php if ($ordersCount > 0): ?>
                                            <span class="bg-gradient-to-r from-primary-500 to-secondary-500 text-white text-xs px-2 py-1 rounded-full font-medium"><?php echo $ordersCount; ?></span>
                                            <?php endif; ?>
                                        </a>
                                    </li>
                                    <li>
                                        <a href="?section=wishlist" class="sidebar-item flex items-center space-x-4 px-4 py-4 rounded-xl transition-all duration-300 group <?php echo $section === 'wishlist' ? 'active bg-gradient-to-r from-primary-50 to-secondary-50 text-primary-700' : 'text-gray-600 hover:bg-gray-50'; ?>">
                                            <div class="w-10 h-10 rounded-lg flex items-center justify-center <?php echo $section === 'wishlist' ? 'bg-gradient-to-br from-primary-500 to-secondary-500 text-white' : 'bg-gray-100 text-gray-500 group-hover:bg-primary-100 group-hover:text-primary-600'; ?> transition-all duration-300">
                                                <i class="fas fa-heart"></i>
                                            </div>
                                            <div class="flex-1">
                                                <span class="font-semibold">Lista de Deseos</span>
                                                <div class="text-xs text-gray-500">Productos favoritos</div>
                                            </div>
                                            <?php if ($wishlistCount > 0): ?>
                                            <span class="bg-gradient-to-r from-red-500 to-pink-500 text-white text-xs px-2 py-1 rounded-full font-medium animate-pulse-soft"><?php echo $wishlistCount; ?></span>
                                            <?php endif; ?>
                                        </a>
                                    </li>
                                    <li>
                                        <a href="?section=profile" class="sidebar-item flex items-center space-x-4 px-4 py-4 rounded-xl transition-all duration-300 group <?php echo $section === 'profile' ? 'active bg-gradient-to-r from-primary-50 to-secondary-50 text-primary-700' : 'text-gray-600 hover:bg-gray-50'; ?>">
                                            <div class="w-10 h-10 rounded-lg flex items-center justify-center <?php echo $section === 'profile' ? 'bg-gradient-to-br from-primary-500 to-secondary-500 text-white' : 'bg-gray-100 text-gray-500 group-hover:bg-primary-100 group-hover:text-primary-600'; ?> transition-all duration-300">
                                                <i class="fas fa-user-edit"></i>
                                            </div>
                                            <div>
                                                <span class="font-semibold">Editar Perfil</span>
                                                <div class="text-xs text-gray-500">Información personal</div>
                                            </div>
                                        </a>
                                    </li>
                                    <li>
                                        <a href="?section=password" class="sidebar-item flex items-center space-x-4 px-4 py-4 rounded-xl transition-all duration-300 group <?php echo $section === 'password' ? 'active bg-gradient-to-r from-primary-50 to-secondary-50 text-primary-700' : 'text-gray-600 hover:bg-gray-50'; ?>">
                                            <div class="w-10 h-10 rounded-lg flex items-center justify-center <?php echo $section === 'password' ? 'bg-gradient-to-br from-primary-500 to-secondary-500 text-white' : 'bg-gray-100 text-gray-500 group-hover:bg-primary-100 group-hover:text-primary-600'; ?> transition-all duration-300">
                                                <i class="fas fa-lock"></i>
                                            </div>
                                            <div>
                                                <span class="font-semibold">Seguridad</span>
                                                <div class="text-xs text-gray-500">Cambiar contraseña</div>
                                            </div>
                                        </a>
                                    </li>
                                    <li class="pt-4 border-t border-gray-200">
                                        <a href="logout.php" class="sidebar-item flex items-center space-x-4 px-4 py-4 rounded-xl transition-all duration-300 group text-red-600 hover:bg-red-50">
                                            <div class="w-10 h-10 rounded-lg flex items-center justify-center bg-red-100 text-red-500 group-hover:bg-red-200 transition-all duration-300">
                                                <i class="fas fa-sign-out-alt"></i>
                                            </div>
                                            <div>
                                                <span class="font-semibold">Cerrar Sesión</span>
                                                <div class="text-xs text-red-400">Salir de la cuenta</div>
                                            </div>
                                        </a>
                                    </li>
                                </ul>
                            </nav>
                        </div>
                    </div>

                    <!-- Main Content -->
                    <div class="xl:w-3/4">
                        <?php if ($section === 'dashboard'): ?>
                        <!-- Dashboard -->
                        <div class="space-y-6 md:space-y-8" data-aos="fade-left">
                            <!-- Enhanced Welcome Header -->
                            <div class="glass-effect rounded-2xl md:rounded-3xl luxury-shadow p-4 md:p-6 lg:p-8 relative overflow-hidden shimmer-effect mb-6 md:mb-8 mobile-card">
                                <div class="absolute inset-0 bg-gradient-to-r from-primary-500/10 to-secondary-500/10 hidden md:block"></div>
                                <div class="relative z-10">
                                    <div class="flex flex-col md:flex-row items-start md:items-center justify-between">
                                        <div class="flex-1 w-full">
                                            <div class="flex flex-col md:flex-row md:items-center md:space-x-4">
                                                <!-- Mobile Crown Icon -->
                                                <div class="md:hidden flex justify-center mb-4">
                                                    <div class="w-12 h-12 bg-gradient-to-br from-primary-400 to-secondary-400 rounded-full flex items-center justify-center animate-float mobile-card-shadow">
                                                        <i class="fas fa-crown text-white text-lg"></i>
                                                    </div>
                                                </div>
                                                
                                                <div class="flex-1 text-center md:text-left">
                                                    <h1 class="text-xl md:text-2xl lg:text-3xl xl:text-4xl font-bold font-serif gradient-text mb-2 md:mb-3 responsive-text-4xl">
                                                        ¡Hola, <?php echo htmlspecialchars($customer['first_name']); ?>!
                                                        <span class="inline-block animate-bounce-gentle">👋</span>
                                                    </h1>
                                                    <p class="text-gray-600 text-sm md:text-base lg:text-lg mb-3 md:mb-4">Bienvenido a tu panel de control personal</p>
                                                    <div class="flex flex-col md:flex-row md:items-center space-y-2 md:space-y-0 md:space-x-6 text-xs md:text-sm text-gray-500">
                                                        <div class="flex items-center justify-center md:justify-start">
                                                            <i class="fas fa-calendar-alt mr-2 text-primary-500"></i>
                                                            <span>Miembro desde <?php echo date('M Y', strtotime($customer['created_at'])); ?></span>
                                                        </div>
                                                        <div class="flex items-center justify-center md:justify-start">
                                                            <i class="fas fa-envelope mr-2 text-primary-500"></i>
                                                            <span class="truncate max-w-48 md:max-w-none"><?php echo htmlspecialchars($customer['email']); ?></span>
                                                        </div>
                                                        <div class="flex items-center justify-center md:justify-start">
                                                            <div class="w-2 h-2 bg-green-500 rounded-full mr-2 animate-pulse"></div>
                                                            <span class="text-green-600 font-medium">En línea</span>
                                                        </div>
                                                    </div>
                                                </div>
                                                
                                                <!-- Desktop Crown Icon -->
                                                <div class="hidden md:block mt-4 md:mt-0 md:ml-6 flex-shrink-0">
                                                    <div class="w-16 h-16 lg:w-24 lg:h-24 xl:w-32 xl:h-32 bg-gradient-to-br from-primary-400 to-secondary-400 rounded-full flex items-center justify-center animate-float shadow-2xl">
                                                        <i class="fas fa-crown text-white text-xl lg:text-2xl xl:text-4xl"></i>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Enhanced Stats Cards -->
                            <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 md:gap-6">
                                <!-- Total Orders Card -->
                                <div class="group bg-gradient-to-br from-blue-50 to-blue-100 rounded-xl md:rounded-3xl p-4 md:p-6 border border-blue-200/50 hover:shadow-2xl transition-all duration-500 hover:-translate-y-1 md:hover:-translate-y-2 cursor-pointer mobile-card stats-card-mobile">
                                    <div class="flex flex-col md:flex-row items-center md:items-start md:justify-between mb-3 md:mb-4">
                                        <div class="w-10 h-10 md:w-14 md:h-14 bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl md:rounded-2xl flex items-center justify-center shadow-lg group-hover:shadow-xl transition-all duration-300 group-hover:scale-110 mb-2 md:mb-0">
                                            <i class="fas fa-shopping-bag text-white text-sm md:text-xl"></i>
                                        </div>
                                        <div class="text-center md:text-right">
                                            <div class="w-6 h-6 md:w-8 md:h-8 bg-blue-500/20 rounded-full flex items-center justify-center mx-auto md:mx-0">
                                                <i class="fas fa-arrow-up text-blue-600 text-xs"></i>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="text-center md:text-left">
                                        <p class="text-2xl md:text-3xl font-bold text-blue-700 mb-1 group-hover:text-blue-800 transition-colors"><?php echo $ordersCount; ?></p>
                                        <p class="text-xs md:text-sm font-semibold text-blue-600 mb-1">Total de Pedidos</p>
                                        <p class="text-xs text-blue-500">Compras realizadas</p>
                                    </div>
                                </div>

                                <!-- Wishlist Card -->
                                <div class="group bg-gradient-to-br from-red-50 to-pink-100 rounded-xl md:rounded-3xl p-4 md:p-6 border border-red-200/50 hover:shadow-2xl transition-all duration-500 hover:-translate-y-1 md:hover:-translate-y-2 cursor-pointer mobile-card stats-card-mobile">
                                    <div class="flex flex-col md:flex-row items-center md:items-start md:justify-between mb-3 md:mb-4">
                                        <div class="w-10 h-10 md:w-14 md:h-14 bg-gradient-to-br from-red-500 to-pink-500 rounded-xl md:rounded-2xl flex items-center justify-center shadow-lg group-hover:shadow-xl transition-all duration-300 group-hover:scale-110 mb-2 md:mb-0">
                                            <i class="fas fa-heart text-white text-sm md:text-xl animate-pulse"></i>
                                        </div>
                                        <div class="text-center md:text-right">
                                            <?php if ($wishlistCount > 0): ?>
                                            <div class="w-6 h-6 md:w-8 md:h-8 bg-red-500/20 rounded-full flex items-center justify-center animate-pulse mx-auto md:mx-0">
                                                <i class="fas fa-star text-red-600 text-xs"></i>
                                            </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="text-center md:text-left">
                                        <p class="text-2xl md:text-3xl font-bold text-red-600 mb-1 group-hover:text-red-700 transition-colors"><?php echo $wishlistCount; ?></p>
                                        <p class="text-xs md:text-sm font-semibold text-red-500 mb-1">Lista de Deseos</p>
                                        <p class="text-xs text-red-400">Productos favoritos</p>
                                    </div>
                                </div>

                                <!-- Account Status Card -->
                                <div class="group bg-gradient-to-br from-green-50 to-emerald-100 rounded-xl md:rounded-3xl p-4 md:p-6 border border-green-200/50 hover:shadow-2xl transition-all duration-500 hover:-translate-y-1 md:hover:-translate-y-2 cursor-pointer mobile-card stats-card-mobile">
                                    <div class="flex flex-col md:flex-row items-center md:items-start md:justify-between mb-3 md:mb-4">
                                        <div class="w-10 h-10 md:w-14 md:h-14 bg-gradient-to-br from-green-500 to-emerald-500 rounded-xl md:rounded-2xl flex items-center justify-center shadow-lg group-hover:shadow-xl transition-all duration-300 group-hover:scale-110 mb-2 md:mb-0">
                                            <i class="fas fa-shield-check text-white text-sm md:text-xl"></i>
                                        </div>
                                        <div class="text-center md:text-right">
                                            <div class="w-2 h-2 md:w-3 md:h-3 bg-green-500 rounded-full animate-pulse mx-auto md:mx-0"></div>
                                        </div>
                                    </div>
                                    <div class="text-center md:text-left">
                                        <p class="text-lg md:text-2xl font-bold text-green-600 mb-1 group-hover:text-green-700 transition-colors">Activa</p>
                                        <p class="text-xs md:text-sm font-semibold text-green-500 mb-1">Estado de Cuenta</p>
                                        <p class="text-xs text-green-400">Verificada y segura</p>
                                    </div>
                                </div>

                                <!-- Member Since Card -->
                                <div class="group bg-gradient-to-br from-purple-50 to-purple-100 rounded-xl md:rounded-3xl p-4 md:p-6 border border-purple-200/50 hover:shadow-2xl transition-all duration-500 hover:-translate-y-1 md:hover:-translate-y-2 cursor-pointer mobile-card stats-card-mobile">
                                    <div class="flex flex-col md:flex-row items-center md:items-start md:justify-between mb-3 md:mb-4">
                                        <div class="w-10 h-10 md:w-14 md:h-14 bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl md:rounded-2xl flex items-center justify-center shadow-lg group-hover:shadow-xl transition-all duration-300 group-hover:scale-110 mb-2 md:mb-0">
                                            <i class="fas fa-crown text-white text-sm md:text-xl"></i>
                                        </div>
                                        <div class="text-center md:text-right">
                                            <div class="w-6 h-6 md:w-8 md:h-8 bg-purple-500/20 rounded-full flex items-center justify-center mx-auto md:mx-0">
                                                <i class="fas fa-gem text-purple-600 text-xs"></i>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="text-center md:text-left">
                                        <p class="text-sm md:text-xl font-bold text-purple-600 mb-1 group-hover:text-purple-700 transition-colors"><?php echo date('M Y', strtotime($customer['created_at'])); ?></p>
                                        <p class="text-xs md:text-sm font-semibold text-purple-500 mb-1">Miembro desde</p>
                                        <p class="text-xs text-purple-400">Cliente VIP</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Recent Orders -->
                            <div class="glass-effect rounded-2xl md:rounded-3xl luxury-shadow p-6 md:p-8 mobile-card">
                                <div class="flex flex-col md:flex-row items-start md:items-center justify-between mb-6 md:mb-8">
                                    <div>
                                        <h2 class="text-xl md:text-2xl font-bold font-serif text-gray-800 responsive-text-2xl">Pedidos Recientes</h2>
                                        <p class="text-sm md:text-base text-gray-600">Tus últimas compras</p>
                                    </div>
                                    <a href="?section=orders" class="bg-gradient-to-r from-primary-500 to-secondary-500 text-white px-4 md:px-6 py-2 md:py-3 rounded-lg md:rounded-xl font-semibold hover:shadow-lg transition-all duration-300 shimmer-effect text-sm md:text-base mobile-button mt-4 md:mt-0">
                                        Ver todos <i class="fas fa-arrow-right ml-2"></i>
                                    </a>
                                </div>

                                <?php if (empty($recentOrders)): ?>
                                <div class="text-center py-12 md:py-16">
                                    <div class="w-16 h-16 md:w-24 md:h-24 bg-gradient-to-br from-gray-100 to-gray-200 rounded-full flex items-center justify-center mx-auto mb-4 md:mb-6">
                                        <i class="fas fa-shopping-bag text-2xl md:text-4xl text-gray-400"></i>
                                    </div>
                                    <h3 class="text-lg md:text-xl font-semibold text-gray-600 mb-2 md:mb-3 responsive-text-xl">Aún no tienes pedidos</h3>
                                    <p class="text-sm md:text-base text-gray-500 mb-6 md:mb-8 px-4">¡Explora nuestros productos y realiza tu primera compra!</p>
                                    <a href="catalogo.php" class="bg-gradient-to-r from-primary-500 to-secondary-500 text-white px-6 md:px-8 py-3 md:py-4 rounded-lg md:rounded-xl font-semibold hover:shadow-lg transition-all duration-300 mobile-button">
                                        <i class="fas fa-search mr-2"></i>
                                        Explorar Productos
                                    </a>
                                </div>
                                <?php else: ?>
                                <div class="space-y-3 md:space-y-4">
                                    <?php foreach ($recentOrders as $order): ?>
                                    <div class="bg-white/70 backdrop-blur-sm border border-white/20 rounded-xl md:rounded-2xl p-4 md:p-6 hover:shadow-lg transition-all duration-300 hover:bg-white/90 mobile-card">
                                        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between">
                                            <div class="flex-1">
                                                <div class="flex items-start md:items-center space-x-3 md:space-x-4 mb-3">
                                                    <div class="w-10 h-10 md:w-12 md:h-12 bg-gradient-to-br from-primary-500 to-secondary-500 rounded-lg md:rounded-xl flex items-center justify-center flex-shrink-0">
                                                        <i class="fas fa-receipt text-white text-sm md:text-base"></i>
                                                    </div>
                                                    <div class="min-w-0 flex-1">
                                                        <h4 class="font-bold text-gray-800 text-sm md:text-base">Pedido #<?php echo $order['id']; ?></h4>
                                                        <p class="text-xs md:text-sm text-gray-600 line-clamp-2"><?php echo $order['items'] ?: 'Sin detalles'; ?></p>
                                                    </div>
                                                </div>
                                                <div class="flex items-center text-xs text-gray-500 ml-13 md:ml-16">
                                                    <i class="fas fa-clock mr-2"></i>
                                                    <?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?>
                                                </div>
                                            </div>
                                            <div class="mt-4 lg:mt-0 lg:text-right flex flex-row lg:flex-col items-center lg:items-end justify-between lg:justify-center">
                                                <p class="text-xl md:text-2xl font-bold gradient-text lg:mb-2">$<?php echo number_format($order['total_amount'], 0, ',', '.'); ?></p>
                                                <span class="inline-block px-3 py-1 rounded-full text-xs font-semibold
                                                    <?php
                                                     switch($order['status']) {
                                                        case 'completed': echo 'bg-green-100 text-green-800'; break;
                                                        case 'processing': echo 'bg-blue-100 text-blue-800'; break;
                                                        case 'shipped': echo 'bg-purple-100 text-purple-800'; break;
                                                        default: echo 'bg-gray-100 text-gray-800';
                                                    }
                                                    ?>">
                                                    <?php
                                                     $statusLabels = [
                                                        'pending' => 'Pendiente',
                                                        'processing' => 'Procesando',
                                                        'shipped' => 'Enviado',
                                                        'delivered' => 'Entregado',
                                                        'completed' => 'Completado',
                                                        'cancelled' => 'Cancelado'
                                                    ];
                                                    echo $statusLabels[$order['status']] ?? ucfirst($order['status']);
                                                    ?>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <?php elseif ($section === 'wishlist'): ?>
                        <!-- Wishlist -->
                        <div class="glass-effect rounded-3xl luxury-shadow p-8" data-aos="fade-left">
                            <div class="flex items-center justify-between mb-8">
                                <div>
                                    <h2 class="text-3xl font-bold font-serif gradient-text">Mi Lista de Deseos</h2>
                                    <p class="text-gray-600 mt-2"><?php echo count($wishlistItems); ?> productos guardados</p>
                                </div>
                                <div class="flex items-center space-x-2 text-sm text-gray-500">
                                    <i class="fas fa-heart text-red-500"></i>
                                    <span>Productos favoritos</span>
                                </div>
                            </div>

                            <?php if (empty($wishlistItems)): ?>
                            <div class="text-center py-20">
                                <div class="w-32 h-32 bg-gradient-to-br from-red-100 to-pink-100 rounded-full flex items-center justify-center mx-auto mb-8">
                                    <i class="fas fa-heart text-6xl text-red-300"></i>
                                </div>
                                <h3 class="text-2xl font-bold text-gray-700 mb-4">Tu lista de deseos está vacía</h3>
                                <p class="text-gray-500 mb-8 max-w-md mx-auto">Guarda tus productos favoritos para comprarlos más tarde y no perder de vista lo que más te gusta</p>
                                <a href="catalogo.php" class="bg-gradient-to-r from-primary-500 to-secondary-500 text-white px-8 py-4 rounded-xl font-semibold hover:shadow-lg transition-all duration-300">
                                    <i class="fas fa-search mr-2"></i>
                                    Explorar Productos
                                </a>
                            </div>
                            <?php else: ?>
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                                <?php foreach ($wishlistItems as $item): ?>
                                <div class="product-card rounded-2xl overflow-hidden luxury-shadow group">
                                    <div class="relative overflow-hidden">
                                        <img src="<?php echo $item['main_image'] ? BASE_URL . '/' . $item['main_image'] : '/placeholder.svg?height=256&width=256&text=Producto'; ?>"
                                              alt="<?php echo htmlspecialchars($item['name']); ?>"
                                             class="w-full h-64 object-cover group-hover:scale-110 transition-transform duration-500">
                                        
                                        <!-- Overlay -->
                                        <div class="absolute inset-0 bg-gradient-to-t from-black/50 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                                        
                                        <!-- Remove from wishlist -->
                                        <button onclick="removeFromWishlist(<?php echo $item['product_id']; ?>)"
                                                 class="absolute top-4 right-4 w-10 h-10 bg-white/90 backdrop-blur-sm rounded-full flex items-center justify-center luxury-shadow hover:bg-red-50 transition-all duration-300 wishlist-heart"
                                                title="Remover de favoritos">
                                            <i class="fas fa-times text-red-500"></i>
                                        </button>
                                        
                                        <?php if ($item['inventory_quantity'] <= 0): ?>
                                        <div class="absolute inset-0 bg-black/60 flex items-center justify-center">
                                            <span class="bg-red-500 text-white px-4 py-2 rounded-full text-sm font-bold">Agotado</span>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="p-6">
                                        <p class="text-sm font-semibold text-primary-600 mb-2"><?php echo htmlspecialchars($item['brand_name']); ?></p>
                                        <h3 class="font-bold text-gray-800 mb-3 line-clamp-2 text-lg"><?php echo htmlspecialchars($item['name']); ?></h3>
                                        
                                        <div class="flex items-center justify-between">
                                            <div>
                                                <span class="text-2xl font-bold gradient-text">$<?php echo number_format($item['price'], 0, ',', '.'); ?></span>
                                                <?php if ($item['compare_price'] && $item['compare_price'] > $item['price']): ?>
                                                <span class="text-sm text-gray-400 line-through ml-2">$<?php echo number_format($item['compare_price'], 0, ',', '.'); ?></span>
                                                <?php endif; ?>
                                            </div>
                                            
                                            <?php if ($item['inventory_quantity'] > 0): ?>
                                            <button onclick="addToCartFromWishlist(<?php echo $item['product_id']; ?>)"
                                                     class="bg-gradient-to-r from-primary-500 to-secondary-500 text-white px-4 py-2 rounded-lg text-sm font-semibold hover:shadow-lg transition-all duration-300">
                                                <i class="fas fa-shopping-cart mr-1"></i>
                                                Agregar
                                            </button>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <?php endif; ?>
                        </div>

                        <?php elseif ($section === 'orders'): ?>
                        <!-- Orders -->
                        <div class="glass-effect rounded-3xl luxury-shadow p-8" data-aos="fade-left">
                            <div class="flex items-center justify-between mb-8">
                                <div>
                                    <h2 class="text-3xl font-bold font-serif gradient-text">Mis Pedidos</h2>
                                    <p class="text-gray-600 mt-2">Historial completo de compras</p>
                                </div>
                                <div class="flex items-center space-x-2 text-sm text-gray-500">
                                    <i class="fas fa-shopping-bag text-primary-500"></i>
                                    <span><?php echo count($orders); ?> pedidos</span>
                                </div>
                            </div>

                            <?php if (empty($orders)): ?>
                            <div class="text-center py-20">
                                <div class="w-32 h-32 bg-gradient-to-br from-primary-100 to-secondary-100 rounded-full flex items-center justify-center mx-auto mb-8">
                                    <i class="fas fa-shopping-bag text-6xl text-primary-300"></i>
                                </div>
                                <h3 class="text-2xl font-bold text-gray-700 mb-4">No tienes pedidos aún</h3>
                                <p class="text-gray-500 mb-8 max-w-md mx-auto">¡Realiza tu primera compra y aparecerá aquí! Explora nuestros productos de belleza</p>
                                <a href="catalogo.php" class="bg-gradient-to-r from-primary-500 to-secondary-500 text-white px-8 py-4 rounded-xl font-semibold hover:shadow-lg transition-all duration-300">
                                    <i class="fas fa-shopping-cart mr-2"></i>
                                    Ir de Compras
                                </a>
                            </div>
                            <?php else: ?>
                            <div class="order-timeline space-y-8">
                                <?php foreach ($orders as $index => $order): ?>
                                <div class="relative">
                                    <div class="timeline-dot absolute left-4 w-8 h-8 rounded-full flex items-center justify-center">
                                        <i class="fas fa-shopping-bag text-primary-600 text-sm"></i>
                                    </div>
                                    <div class="ml-16 bg-white/70 backdrop-blur-sm border border-white/20 rounded-2xl p-6 hover:shadow-xl transition-all duration-300 hover:bg-white/90">
                                        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between mb-4">
                                            <div>
                                                <h3 class="text-xl font-bold text-gray-800 mb-2">Pedido #<?php echo $order['id']; ?></h3>
                                                <div class="flex items-center text-sm text-gray-600 space-x-4">
                                                    <div class="flex items-center">
                                                        <i class="fas fa-calendar mr-2"></i>
                                                        <?php echo date('d/m/Y', strtotime($order['created_at'])); ?>
                                                    </div>
                                                    <div class="flex items-center">
                                                        <i class="fas fa-clock mr-2"></i>
                                                        <?php echo date('H:i', strtotime($order['created_at'])); ?>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="mt-4 lg:mt-0 text-right">
                                                <span class="inline-block px-4 py-2 rounded-full text-sm font-bold
                                                    <?php
                                                     switch($order['status']) {
                                                        case 'completed': echo 'bg-green-100 text-green-800'; break;
                                                        case 'processing': echo 'bg-blue-100 text-blue-800'; break;
                                                        case 'shipped': echo 'bg-purple-100 text-purple-800'; break;
                                                        case 'delivered': echo 'bg-emerald-100 text-emerald-800'; break;
                                                        case 'cancelled': echo 'bg-red-100 text-red-800'; break;
                                                        default: echo 'bg-gray-100 text-gray-800';
                                                    }
                                                    ?>">
                                                    <?php
                                                     $statusLabels = [
                                                        'pending' => 'Pendiente',
                                                        'processing' => 'Procesando',
                                                        'shipped' => 'Enviado',
                                                        'delivered' => 'Entregado',
                                                        'completed' => 'Completado',
                                                        'cancelled' => 'Cancelado'
                                                    ];
                                                    echo $statusLabels[$order['status']] ?? ucfirst($order['status']);
                                                    ?>
                                                </span>
                                            </div>
                                        </div>
                                        
                                        <div class="border-t border-gray-100 pt-4">
                                            <div class="flex justify-between items-end">
                                                <div class="flex-1">
                                                    <p class="text-sm font-semibold text-gray-700 mb-2">Productos:</p>
                                                    <p class="text-gray-600"><?php echo $order['items'] ?: 'Ver detalles del pedido'; ?></p>
                                                </div>
                                                <div class="text-right ml-6">
                                                    <p class="text-sm text-gray-600 mb-1">Total:</p>
                                                    <p class="text-3xl font-bold gradient-text">$<?php echo number_format($order['total_amount'], 0, ',', '.'); ?></p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <?php endif; ?>
                        </div>

                        <?php elseif ($section === 'profile'): ?>
                        <!-- Profile Edit -->
                        <div class="glass-effect rounded-3xl luxury-shadow p-8" data-aos="fade-left">
                            <div class="mb-8">
                                <h2 class="text-3xl font-bold font-serif gradient-text">Editar Perfil</h2>
                                <p class="text-gray-600 mt-2">Actualiza tu información personal</p>
                            </div>
                            
                            <form method="POST" class="space-y-8">
                                <input type="hidden" name="update_profile" value="1">
                                
                                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                                    <div>
                                        <label for="first_name" class="block text-sm font-bold text-gray-700 mb-3">
                                            <i class="fas fa-user mr-2 text-primary-500"></i>
                                            Nombre
                                        </label>
                                        <input type="text" id="first_name" name="first_name"
                                                value="<?php echo htmlspecialchars($customer['first_name']); ?>"
                                                class="form-input w-full px-6 py-4 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent text-lg"
                                               required>
                                    </div>
                                    
                                    <div>
                                        <label for="last_name" class="block text-sm font-bold text-gray-700 mb-3">
                                            <i class="fas fa-user mr-2 text-primary-500"></i>
                                            Apellido
                                        </label>
                                        <input type="text" id="last_name" name="last_name"
                                                value="<?php echo htmlspecialchars($customer['last_name']); ?>"
                                                class="form-input w-full px-6 py-4 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent text-lg"
                                               required>
                                    </div>
                                </div>
                                
                                <div>
                                    <label for="email" class="block text-sm font-bold text-gray-700 mb-3">
                                        <i class="fas fa-envelope mr-2 text-primary-500"></i>
                                        Email
                                    </label>
                                    <input type="email" id="email" name="email"
                                            value="<?php echo htmlspecialchars($customer['email']); ?>"
                                            class="form-input w-full px-6 py-4 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent text-lg"
                                           required>
                                </div>
                                
                                <div>
                                    <label for="phone" class="block text-sm font-bold text-gray-700 mb-3">
                                        <i class="fas fa-phone mr-2 text-primary-500"></i>
                                        Teléfono
                                    </label>
                                    <input type="tel" id="phone" name="phone"
                                            value="<?php echo htmlspecialchars($customer['phone'] ?? ''); ?>"
                                            class="form-input w-full px-6 py-4 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent text-lg">
                                </div>
                                
                                <div class="pt-6">
                                    <button type="submit" class="bg-gradient-to-r from-primary-500 to-secondary-500 text-white px-10 py-4 rounded-xl font-bold text-lg hover:shadow-xl transition-all duration-300 shimmer-effect">
                                        <i class="fas fa-save mr-3"></i>
                                        Guardar Cambios
                                    </button>
                                </div>
                            </form>
                        </div>

                        <?php elseif ($section === 'password'): ?>
                        <!-- Password Change -->
                        <div class="glass-effect rounded-3xl luxury-shadow p-8" data-aos="fade-left">
                            <div class="mb-8">
                                <h2 class="text-3xl font-bold font-serif gradient-text">Cambiar Contraseña</h2>
                                <p class="text-gray-600 mt-2">Actualiza tu contraseña para mantener tu cuenta segura</p>
                            </div>
                            
                            <form method="POST" class="space-y-8" onsubmit="return validatePassword()">
                                <input type="hidden" name="change_password" value="1">
                                
                                <div>
                                    <label for="current_password" class="block text-sm font-bold text-gray-700 mb-3">
                                        <i class="fas fa-lock mr-2 text-primary-500"></i>
                                        Contraseña Actual
                                    </label>
                                    <input type="password" id="current_password" name="current_password"
                                            class="form-input w-full px-6 py-4 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent text-lg"
                                           required>
                                </div>
                                
                                <div>
                                    <label for="new_password" class="block text-sm font-bold text-gray-700 mb-3">
                                        <i class="fas fa-key mr-2 text-primary-500"></i>
                                        Nueva Contraseña
                                    </label>
                                    <input type="password" id="new_password" name="new_password"
                                            class="form-input w-full px-6 py-4 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent text-lg"
                                           minlength="6" required>
                                    <p class="text-sm text-gray-500 mt-2 flex items-center">
                                        <i class="fas fa-info-circle mr-2"></i>
                                        Mínimo 6 caracteres
                                    </p>
                                </div>
                                
                                <div>
                                    <label for="confirm_password" class="block text-sm font-bold text-gray-700 mb-3">
                                        <i class="fas fa-check-circle mr-2 text-primary-500"></i>
                                        Confirmar Nueva Contraseña
                                    </label>
                                    <input type="password" id="confirm_password" name="confirm_password"
                                            class="form-input w-full px-6 py-4 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent text-lg"
                                           required>
                                </div>
                                
                                <div class="pt-6">
                                    <button type="submit" class="bg-gradient-to-r from-primary-500 to-secondary-500 text-white px-10 py-4 rounded-xl font-bold text-lg hover:shadow-xl transition-all duration-300 shimmer-effect">
                                        <i class="fas fa-shield-alt mr-3"></i>
                                        Cambiar Contraseña
                                    </button>
                                </div>
                            </form>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </section>
    </main>

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

    <!-- Scripts -->
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    
    <!-- Mobile Menu Debug Script -->
    <script>
        // Simple function to ensure mobile menu works
        function toggleMobileMenu() {
            console.log('Toggle function called directly');
            const menu = document.getElementById('mobile-menu');
            const icon = document.getElementById('mobile-menu-icon');
            
            if (menu && icon) {
                const isHidden = menu.classList.contains('hidden');
                console.log('Menu state - hidden:', isHidden);
                
                if (isHidden) {
                    // Show menu
                    menu.classList.remove('hidden');
                    menu.style.display = 'block';
                    menu.style.maxHeight = '400px';
                    menu.style.opacity = '1';
                    icon.classList.add('rotate-180');
                    console.log('Menu shown');
                } else {
                    // Hide menu
                    menu.classList.add('hidden');
                    menu.style.display = 'none';
                    menu.style.maxHeight = '0';
                    menu.style.opacity = '0';
                    icon.classList.remove('rotate-180');
                    console.log('Menu hidden');
                }
            } else {
                console.error('Menu elements not found:', { menu: !!menu, icon: !!icon });
            }
        }
        
        // Close menu when clicking outside
        function closeMobileMenuOutside(event) {
            const menu = document.getElementById('mobile-menu');
            const toggle = document.getElementById('mobile-menu-toggle');
            
            if (menu && toggle && !menu.classList.contains('hidden')) {
                // Check if click is outside menu and toggle
                if (!menu.contains(event.target) && !toggle.contains(event.target)) {
                    toggleMobileMenu();
                }
            }
        }
        
        // Test JavaScript is working
        console.log('Mi-cuenta.php JavaScript loaded successfully');
        
        // Add click outside listener when DOM is loaded
        document.addEventListener('DOMContentLoaded', function() {
            document.addEventListener('click', closeMobileMenuOutside);
        });
    </script>
    
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

        // Password validation
        function validatePassword() {
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            if (newPassword !== confirmPassword) {
                alert('Las nuevas contraseñas no coinciden');
                return false;
            }
            
            if (newPassword.length < 6) {
                alert('La contraseña debe tener al menos 6 caracteres');
                return false;
            }
            
            return true;
        }

        // Mobile Menu Toggle Functionality - Ultra Simplified
        document.addEventListener('DOMContentLoaded', function() {
            console.log('DOM Content Loaded');
            
            const mobileMenuToggle = document.getElementById('mobile-menu-toggle');
            const mobileMenu = document.getElementById('mobile-menu');
            const mobileMenuIcon = document.getElementById('mobile-menu-icon');
            
            // Debug: Check if elements exist
            console.log('Mobile menu elements found:', {
                toggle: !!mobileMenuToggle,
                menu: !!mobileMenu,
                icon: !!mobileMenuIcon
            });
            
            // Only add event listener if onclick is not working
            if (mobileMenuToggle && !mobileMenuToggle.onclick) {
                console.log('Adding backup event listener');
                mobileMenuToggle.addEventListener('click', function(e) {
                    e.preventDefault();
                    console.log('Backup event listener triggered');
                    toggleMobileMenu();
                });
            }

            // Enhanced mobile optimizations
            function optimizeForMobile() {
                const isMobile = window.innerWidth <= 768;
                
                if (isMobile) {
                    document.body.classList.add('mobile-optimized');
                    
                    // Optimize touch targets
                    const touchTargets = document.querySelectorAll('.touch-target');
                    touchTargets.forEach(target => {
                        if (!target.style.minHeight) {
                            target.style.minHeight = '48px';
                            target.style.minWidth = '48px';
                        }
                    });
                    
                    // Optimize form inputs
                    const inputs = document.querySelectorAll('input, select, textarea');
                    inputs.forEach(input => {
                        if (!input.style.fontSize) {
                            input.style.fontSize = '16px';
                        }
                    });
                }
            }
            
            // Initialize optimizations
            optimizeForMobile();
            window.addEventListener('resize', optimizeForMobile);

            // Enhanced Stats Cards Animation
            const statsCards = document.querySelectorAll('.group');
            statsCards.forEach((card, index) => {
                card.style.animationDelay = `${index * 100}ms`;
                
                // Add intersection observer for scroll animations
                const observer = new IntersectionObserver((entries) => {
                    entries.forEach(entry => {
                        if (entry.isIntersecting) {
                            entry.target.style.animation = 'fadeInUp 0.6s ease forwards';
                        }
                    });
                }, { threshold: 0.1 });
                
                observer.observe(card);
            });

            // Auto-hide notifications
            const notifications = document.querySelectorAll('.notification');
            notifications.forEach(notification => {
                setTimeout(() => {
                    notification.style.transition = 'all 0.5s ease';
                    notification.style.opacity = '0';
                    notification.style.transform = 'translateY(-20px)';
                    setTimeout(() => notification.remove(), 500);
                }, 5000);
            });

            // Enhanced form validation with visual feedback
            const forms = document.querySelectorAll('form');
            forms.forEach(form => {
                const inputs = form.querySelectorAll('input');
                inputs.forEach(input => {
                    input.addEventListener('blur', function() {
                        validateInputVisual(this);
                    });
                    
                    input.addEventListener('input', function() {
                        if (this.classList.contains('error')) {
                            validateInputVisual(this);
                        }
                    });
                });
            });

            // Smooth scroll for better UX
            document.querySelectorAll('a[href^="#"]').forEach(anchor => {
                anchor.addEventListener('click', function(e) {
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

            // Performance optimization for mobile
            if ('IntersectionObserver' in window) {
                const imageObserver = new IntersectionObserver((entries) => {
                    entries.forEach(entry => {
                        if (entry.isIntersecting) {
                            const img = entry.target;
                            if (img.dataset.src) {
                                img.src = img.dataset.src;
                                img.removeAttribute('data-src');
                                imageObserver.unobserve(img);
                            }
                        }
                    });
                });
                
                document.querySelectorAll('img[data-src]').forEach(img => {
                    imageObserver.observe(img);
                });
            }
        });

        // Visual input validation function
        function validateInputVisual(input) {
            const value = input.value.trim();
            const type = input.type;
            let isValid = true;
            
            // Remove previous error states
            input.classList.remove('error', 'success');
            const errorMsg = input.parentNode.querySelector('.error-message');
            if (errorMsg) errorMsg.remove();
            
            // Validate based on input type
            switch (type) {
                case 'email':
                    isValid = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value);
                    if (!isValid && value) {
                        showInputError(input, 'Ingresa un email válido');
                    }
                    break;
                case 'tel':
                    isValid = /^[\d\s\-\+\(\)]+$/.test(value);
                    if (!isValid && value) {
                        showInputError(input, 'Ingresa un teléfono válido');
                    }
                    break;
                case 'password':
                    isValid = value.length >= 6;
                    if (!isValid && value) {
                        showInputError(input, 'La contraseña debe tener al menos 6 caracteres');
                    }
                    break;
                default:
                    isValid = value.length > 0;
                    if (!isValid && input.required) {
                        showInputError(input, 'Este campo es obligatorio');
                    }
            }
            
            // Add visual feedback
            if (isValid && value) {
                input.classList.add('success');
            } else if (!isValid && value) {
                input.classList.add('error');
            }
        }

        function showInputError(input, message) {
            input.classList.add('error');
            const errorDiv = document.createElement('div');
            errorDiv.className = 'error-message text-red-500 text-xs mt-1';
            errorDiv.textContent = message;
            input.parentNode.appendChild(errorDiv);
        }

        // Generate CSRF token for AJAX requests
        function getCSRFToken() {
            return '<?php echo CSRFProtection::generateToken("cart"); ?>';
        }
        
        // Remove from wishlist
        function removeFromWishlist(productId) {
            if (confirm('¿Estás seguro de que quieres remover este producto de tu lista de deseos?')) {
                // Add AJAX call here to remove from wishlist
                console.log('Removing product', productId, 'from wishlist');
                // Reload page or update UI
                location.reload();
            }
        }

        // Add to cart from wishlist
        function addToCartFromWishlist(productId) {
            const csrfToken = getCSRFToken();
            
            if (!csrfToken) {
                showNotification('Error de seguridad. Recarga la página.', 'error');
                return;
            }
            
            fetch('cart-add.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `product_id=${productId}&quantity=1&csrf_token=${csrfToken}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification('Producto agregado al carrito', 'success');
                } else {
                    showNotification(data.message || 'Error al agregar producto', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('Error al agregar producto', 'error');
            });
        }
        
        function showNotification(message, type) {
            const notification = document.createElement('div');
            const bgColor = type === 'success' ? 'bg-green-500' : 'bg-red-500';
            notification.className = `fixed top-4 right-4 ${bgColor} text-white px-6 py-3 rounded-lg luxury-shadow z-50 notification`;
            notification.innerHTML = `<i class="fas fa-${type === 'success' ? 'check' : 'exclamation-triangle'} mr-2"></i>${message}`;
            document.body.appendChild(notification);
            
            setTimeout(() => {
                notification.remove();
            }, 3000);
        }

        // Auto-hide notifications
        setTimeout(() => {
            const notifications = document.querySelectorAll('.notification');
            notifications.forEach(notification => {
                notification.style.opacity = '0';
                notification.style.transform = 'translateY(-20px)';
                setTimeout(() => notification.remove(), 300);
            });
        }, 5000);
    </script>
</body>
</html>
