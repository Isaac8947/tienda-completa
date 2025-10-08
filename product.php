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
require_once 'models/Brand.php';
require_once 'models/Category.php';
require_once 'models/Review.php';
require_once 'models/Settings.php';
require_once 'includes/CSRFProtection.php';

// Initialize models for header
$categoryModel = new Category();
$settingsModel = new Settings();

// Get contact settings for header
$contactSettings = [
    'site_phone' => $settingsModel->get('site_phone', ''),
    'site_email' => $settingsModel->get('site_email', ''),
    'social_instagram' => $settingsModel->get('social_instagram', ''),
    'social_facebook' => $settingsModel->get('social_facebook', ''),
    'social_tiktok' => $settingsModel->get('social_tiktok', ''),
    'social_youtube' => $settingsModel->get('social_youtube', ''),
    'social_twitter' => $settingsModel->get('social_twitter', '')
];

// Get categories for mega menu
$dbCategories = $categoryModel->getAll(['is_active' => 1]);

// Get product ID from URL
$product_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$product_id) {
    header('Location: index.php');
    exit;
}

// Initialize models
$productModel = new Product();
$brandModel = new Brand();
$categoryModel = new Category();
$reviewModel = new Review();

// Get product details
$product = $productModel->getProductWithDetails($product_id);

if (!$product) {
    header('Location: 404.php');
    exit;
}

// Get additional product information (brand and category are already included)
$brand = null;
if ($product['brand_id']) {
    $brand = [
        'id' => $product['brand_id'],
        'name' => $product['brand_name'],
        'slug' => $product['brand_slug']
    ];
}

$category = null;
if ($product['category_id']) {
    $category = [
        'id' => $product['category_id'],
        'name' => $product['category_name'],
        'slug' => $product['category_slug']
    ];
}

// Get product reviews and ratings
$reviews = $reviewModel->getReviewsWithInteractions($product_id);
$ratingData = $reviewModel->getAverageRating($product_id);
$averageRating = $ratingData['average'] ?? 0;
$reviewCount = $ratingData['count'] ?? 0;
$ratingDistribution = $reviewModel->getRatingDistribution($product_id);

// Process product images using new product_images table
$productImages = [];
$productImagesData = $productModel->getProductImages($product_id);

if (!empty($productImagesData)) {
    foreach ($productImagesData as $imageData) {
        // Asegurar que la ruta sea correcta
        $imagePath = $imageData['image_path'];
        
        // Limpiar posibles rutas duplicadas
        $imagePath = str_replace('uploads/products/', '', $imagePath);
        $imagePath = str_replace('assets/images/products/', '', $imagePath);
        
        // Construir la URL completa
        $fullImagePath = BASE_URL . '/uploads/products/' . $imagePath;
        
        $productImages[] = [
            'url' => $fullImagePath,
            'alt' => $imageData['alt_text'] ?: htmlspecialchars($product['name']),
            'is_primary' => $imageData['is_primary']
        ];
    }
}

// Fallback: si no hay imágenes en la nueva tabla, usar main_image del producto (compatibilidad)
if (empty($productImages) && !empty($product['main_image'])) {
    $imagePath = str_replace('uploads/products/', '', $product['main_image']);
    $imagePath = str_replace('assets/images/products/', '', $imagePath);
    $productImages[] = [
        'url' => BASE_URL . '/uploads/products/' . $imagePath,
        'alt' => htmlspecialchars($product['name']),
        'is_primary' => true
    ];
}

// Si aún no hay imágenes, usar placeholder
if (empty($productImages)) {
    $productImages[] = [
        'url' => BASE_URL . '/public/images/product-placeholder-1.svg',
        'alt' => 'Imagen no disponible',
        'is_primary' => true
    ];
}

// Extraer solo las URLs para compatibilidad con el código existente
$productImagesUrls = array_column($productImages, 'url');

// Calculate discount
$discount = 0;
if (!empty($product['compare_price']) && $product['compare_price'] > $product['price']) {
    $discount = round((($product['compare_price'] - $product['price']) / $product['compare_price']) * 100);
}

// Helper function for time ago
function timeAgo($datetime) {
    $time = time() - strtotime($datetime);
    
    if ($time < 60) return 'hace unos segundos';
    if ($time < 3600) return 'hace ' . floor($time/60) . ' minutos';
    if ($time < 86400) return 'hace ' . floor($time/3600) . ' horas';
    if ($time < 2592000) return 'hace ' . floor($time/86400) . ' días';
    if ($time < 31536000) return 'hace ' . floor($time/2592000) . ' meses';
    return 'hace ' . floor($time/31536000) . ' años';
}

// ============================================
// PRODUCTOS RELACIONADOS
// ============================================

$relatedProducts = [];

try {
    if ($product['category_id']) {
        // Obtener productos de la misma categoría (excluyendo el producto actual)
        $sql = "SELECT p.*, 
                       c.name as category_name, 
                       b.name as brand_name,
                       CASE 
                           WHEN p.compare_price > 0 AND p.compare_price > p.price 
                           THEN ROUND(((p.compare_price - p.price) / p.compare_price) * 100)
                           ELSE 0 
                       END as discount_percentage
                FROM products p 
                LEFT JOIN categories c ON p.category_id = c.id 
                LEFT JOIN brands b ON p.brand_id = b.id 
                WHERE p.category_id = ? 
                  AND p.id != ? 
                  AND p.status = 'active' 
                  AND p.inventory_quantity > 0
                ORDER BY 
                  CASE WHEN p.is_featured = 1 THEN 0 ELSE 1 END,
                  p.views DESC,
                  RAND()
                LIMIT 8";
        
        $db = new Database();
        $pdo = $db->getConnection();
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$product['category_id'], $product_id]);
        $relatedProducts = $stmt->fetchAll();
        
        // Si no hay suficientes productos de la misma categoría, complementar con productos populares
        if (count($relatedProducts) < 4) {
            $neededProducts = 8 - count($relatedProducts);
            $excludeIds = array_merge([$product_id], array_column($relatedProducts, 'id'));
            $excludeIdsStr = implode(',', $excludeIds);
            
            $sql = "SELECT p.*, 
                           c.name as category_name, 
                           b.name as brand_name,
                           CASE 
                               WHEN p.compare_price > 0 AND p.compare_price > p.price 
                               THEN ROUND(((p.compare_price - p.price) / p.compare_price) * 100)
                               ELSE 0 
                           END as discount_percentage
                    FROM products p 
                    LEFT JOIN categories c ON p.category_id = c.id 
                    LEFT JOIN brands b ON p.brand_id = b.id 
                    WHERE p.id NOT IN ($excludeIdsStr)
                      AND p.status = 'active' 
                      AND p.inventory_quantity > 0
                    ORDER BY 
                      CASE WHEN p.is_featured = 1 THEN 0 ELSE 1 END,
                      p.views DESC,
                      RAND()
                    LIMIT $neededProducts";
            
            $stmt = $pdo->query($sql);
            $additionalProducts = $stmt->fetchAll();
            $relatedProducts = array_merge($relatedProducts, $additionalProducts);
        }
        
        // Agregar imágenes a cada producto relacionado
        foreach ($relatedProducts as &$relatedProduct) {
            $productImagesData = $productModel->getProductImages($relatedProduct['id']);
            
            if (!empty($productImagesData)) {
                $primaryImage = null;
                foreach ($productImagesData as $img) {
                    if ($img['is_primary']) {
                        $primaryImage = $img;
                        break;
                    }
                }
                
                if (!$primaryImage) {
                    $primaryImage = $productImagesData[0];
                }
                
                $relatedProduct['main_image_url'] = BASE_URL . '/' . $primaryImage['image_path'];
            } else {
                // Fallback a main_image si existe
                if (!empty($relatedProduct['main_image'])) {
                    $imagePath = str_replace('uploads/products/', '', $relatedProduct['main_image']);
                    $relatedProduct['main_image_url'] = BASE_URL . '/uploads/products/' . $imagePath;
                } else {
                    $relatedProduct['main_image_url'] = BASE_URL . '/public/images/product-placeholder-1.svg';
                }
            }
        }
    }
} catch (Exception $e) {
    error_log("Error loading related products: " . $e->getMessage());
    $relatedProducts = [];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($product['name']); ?> - OdiseaStore</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            font-family: 'Inter', sans-serif;
        }
        
        .star-rating {
            display: inline-flex;
            gap: 2px;
        }
        .star {
            color: #e5e7eb;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        .star.active {
            color: #f59e0b;
        }
        .star:hover {
            color: #f59e0b;
            transform: scale(1.1);
        }
        
        .image-zoom {
            transform-origin: center;
            transition: transform 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .image-zoom:hover {
            transform: scale(1.05);
        }
        
        .thumbnail {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }
        .thumbnail::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(45deg, transparent 30%, rgba(255,255,255,0.1) 50%, transparent 70%);
            transform: translateX(-100%);
            transition: transform 0.6s;
        }
        .thumbnail:hover::before {
            transform: translateX(100%);
        }
        .thumbnail.active {
            border-color: #3b82f6;
            box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.2);
        }
        
        .fade-in {
            animation: fadeIn 0.6s cubic-bezier(0.4, 0, 0.2, 1);
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .mobile-dot {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .mobile-dot.active {
            background-color: #3b82f6;
            transform: scale(1.3);
        }
        
        .glass-effect {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .gradient-border {
            background: linear-gradient(white, white) padding-box,
                        linear-gradient(45deg, #3b82f6, #8b5cf6) border-box;
            border: 2px solid transparent;
        }
        
        .hover-lift {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .hover-lift:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--primary-600) 0%, var(--primary-700) 100%);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .btn-primary:hover {
            background: linear-gradient(135deg, var(--primary-700) 0%, var(--primary-800) 100%);
            transform: translateY(-1px);
            box-shadow: 0 8px 25px rgba(176, 141, 128, 0.3);
        }
        
        .btn-secondary {
            background: linear-gradient(135deg, var(--secondary-500) 0%, var(--secondary-600) 100%);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .btn-secondary:hover {
            background: linear-gradient(135deg, var(--secondary-600) 0%, var(--secondary-700) 100%);
            transform: translateY(-1px);
            box-shadow: 0 8px 25px rgba(196, 165, 117, 0.3);
        }
        
        /* Botones elegantes con animaciones avanzadas */
        .elegant-btn {
            position: relative;
            overflow: hidden;
            border: none;
            border-radius: 12px;
            padding: 12px 24px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            cursor: pointer;
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
        }
        
        .elegant-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.4), transparent);
            transition: left 0.5s;
        }
        
        .elegant-btn:hover::before {
            left: 100%;
        }
        
        .elegant-btn:hover {
            transform: translateY(-2px) scale(1.02);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.15);
        }
        
        .elegant-btn:active {
            transform: translateY(-1px) scale(0.98);
            transition: all 0.1s;
        }
        
        /* Botón principal elegante */
        .btn-primary-elegant {
            background: linear-gradient(135deg, var(--primary-600), var(--primary-700), var(--secondary-500));
            background-size: 200% 200%;
            color: white;
            box-shadow: 0 4px 15px rgba(176, 141, 128, 0.3);
            animation: gradientShift 3s ease infinite;
        }
        
        @keyframes gradientShift {
            0%, 100% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
        }
        
        .btn-primary-elegant:hover {
            animation-duration: 1s;
            box-shadow: 0 8px 30px rgba(176, 141, 128, 0.4);
        }
        
        /* Botón secundario elegante */
        .btn-secondary-elegant {
            background: linear-gradient(135deg, var(--luxury-rose), var(--luxury-gold));
            color: var(--primary-800);
            box-shadow: 0 4px 15px rgba(244, 230, 225, 0.5);
        }
        
        .btn-secondary-elegant:hover {
            box-shadow: 0 8px 30px rgba(244, 230, 225, 0.6);
            color: var(--primary-900);
        }
        
        /* Botón ghost elegante */
        .btn-ghost-elegant {
            background: rgba(255, 255, 255, 0.1);
            border: 2px solid var(--primary-300);
            color: var(--primary-700);
            backdrop-filter: blur(15px);
        }
        
        .btn-ghost-elegant:hover {
            background: rgba(176, 141, 128, 0.1);
            border-color: var(--primary-500);
            color: var(--primary-800);
        }
        
        /* Botones circulares con efecto ripple */
        .btn-circle {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .btn-circle::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.3);
            transform: translate(-50%, -50%);
            transition: width 0.3s, height 0.3s;
        }
        
        .btn-circle:active::after {
            width: 100px;
            height: 100px;
        }
        
        /* Botones de navegación con efectos de lujo */
        .nav-btn {
            position: relative;
            padding: 8px 16px;
            border-radius: 8px;
            transition: all 0.3s ease;
            overflow: hidden;
        }
        
        .nav-btn::before {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            width: 0;
            height: 2px;
            background: linear-gradient(90deg, var(--primary-500), var(--secondary-500));
            transform: translateX(-50%);
            transition: width 0.3s ease;
        }
        
        .nav-btn:hover::before {
            width: 100%;
        }
        
        .nav-btn:hover {
            background: rgba(176, 141, 128, 0.05);
            transform: translateY(-1px);
        }
        
        /* Animaciones de pulsación */
        @keyframes pulse-luxury {
            0%, 100% { box-shadow: 0 0 0 0 rgba(176, 141, 128, 0.4); }
            50% { box-shadow: 0 0 0 10px rgba(176, 141, 128, 0); }
        }
        
        .btn-pulse {
            animation: pulse-luxury 2s infinite;
        }
        
        /* Efectos de cristal */
        .glass-btn {
            background: rgba(255, 255, 255, 0.25);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.37);
        }
        
        .glass-btn:hover {
            background: rgba(255, 255, 255, 0.35);
            border-color: rgba(255, 255, 255, 0.3);
            transform: translateY(-2px);
        }
        
        .review-card {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            border: 1px solid #f3f4f6;
        }
        .review-card:hover {
            border-color: #e5e7eb;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        }
        
        .mobile-carousel {
            overflow-x: auto;
            scroll-snap-type: x mandatory;
            scrollbar-width: none;
            -ms-overflow-style: none;
        }
        .mobile-carousel::-webkit-scrollbar {
            display: none;
        }
        .mobile-carousel-item {
            scroll-snap-align: start;
            flex: 0 0 280px;
        }
        
        .product-card {
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            border: 1px solid #f3f4f6;
        }
        .product-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            border-color: #e5e7eb;
        }
        
        .section-divider {
            background: linear-gradient(90deg, transparent, #e5e7eb, transparent);
            height: 1px;
        }
        
        @media (max-width: 640px) {
            .mobile-carousel {
                display: flex;
            }
            .desktop-grid {
                display: none;
            }
        }
        @media (min-width: 641px) {
            .mobile-carousel {
                display: none;
            }
            .desktop-grid {
                display: grid;
            }
        }

        /* Header styles */
        .glass-effect {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .gradient-text {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .animate-glow {
            animation: glow 2s ease-in-out infinite alternate;
        }
        
        @keyframes glow {
            from { box-shadow: 0 0 20px -10px rgba(59, 130, 246, 0.5); }
            to { box-shadow: 0 0 30px -5px rgba(59, 130, 246, 0.8); }
        }

        .touch-target {
            min-width: 44px;
            min-height: 44px;
        }

        /* Header scroll behavior */
        .header-hidden {
            transform: translateY(-100%);
            transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .header-visible {
            transform: translateY(0);
            transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        /* Better mobile header */
        @media (max-width: 768px) {
            #mobile-header {
                position: fixed !important;
                top: 0 !important;
                width: 100% !important;
                z-index: 60 !important;
                backdrop-filter: blur(10px);
                -webkit-backdrop-filter: blur(10px);
            }
        }

        /* Better desktop header */
        @media (min-width: 769px) {
            #desktop-header {
                position: fixed !important;
                top: 0 !important;
                width: 100% !important;
                z-index: 50 !important;
            }
        }

        /* Custom Tailwind colors - Matching index.php palette */
        :root {
            --primary-50: #fdf8f6;
            --primary-100: #f2e8e5;
            --primary-200: #eaddd7;
            --primary-300: #e0cec7;
            --primary-400: #d2bab0;
            --primary-500: #b08d80;
            --primary-600: #a67c76;
            --primary-700: #8d635d;
            --primary-800: #745044;
            --primary-900: #5b3d2b;
            
            --secondary-50: #fefdfb;
            --secondary-100: #fdf6f0;
            --secondary-200: #f9e6d3;
            --secondary-300: #f4d3b0;
            --secondary-400: #eab676;
            --secondary-500: #c4a575;
            --secondary-600: #b39256;
            --secondary-700: #9e7d3a;
            --secondary-800: #896820;
            --secondary-900: #745407;
            
            --luxury-rose: #f4e6e1;
            --luxury-gold: #f7f1e8;
            --luxury-pearl: #fefdfb;
            --luxury-bronze: #d2bab0;
            --luxury-champagne: #f9e6d3;
        }

        /* Force gradient colors for all elements - Using luxury palette */
        .bg-gradient-to-r.from-primary-500.to-secondary-500 {
            background: linear-gradient(135deg, #b08d80 0%, #c4a575 100%) !important;
        }

        /* Ensure primary colors work */
        .bg-primary-500 {
            background-color: #b08d80 !important;
        }
        
        .text-primary-500 {
            color: #b08d80 !important;
        }
        
        .border-primary-500 {
            border-color: #b08d80 !important;
        }

        .bg-secondary-500 {
            background-color: #c4a575 !important;
        }
        
        .text-secondary-500 {
            color: #c4a575 !important;
        }

        /* Ensure no extra spacing at top */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            margin: 0 !important;
            padding: 0 !important;
            padding-top: 0 !important;
        }
        
        html {
            margin: 0 !important;
            padding: 0 !important;
            padding-top: 0 !important;
        }

        /* Force headers to top */
        #desktop-header,
        #mobile-header {
            top: 0 !important;
            margin-top: 0 !important;
            padding-top: 0;
        }

        /* Remove any browser default spacing */
        body::before,
        html::before {
            display: none !important;
        }
    </style>

    <!-- Additional inline styles to ensure no spacing issues -->
    <style>
        html, body {
            margin: 0 !important;
            padding: 0 !important;
            top: 0 !important;
        }
        
        body {
            padding-top: 0 !important;
            margin-top: 0 !important;
        }
    </style>
</head>
<body class="font-sans bg-gradient-to-br from-luxury-50 via-white to-rose-50 min-h-screen m-0 p-0">
    <!-- Desktop Header -->
    <header class="hidden md:block fixed top-0 left-0 right-0 z-50 header-visible" id="desktop-header">
        <!-- Top Bar -->
        <div class="bg-gradient-to-r text-white py-2 text-sm" style="background: linear-gradient(135deg, #b08d80 0%, #c4a575 100%);">
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
                            <button type="submit" class="absolute right-4 top-1/2 transform -translate-y-1/2 btn-circle elegant-btn btn-primary-elegant group">
                                <i class="fas fa-search text-sm group-hover:scale-110 transition-transform duration-300"></i>
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
                            <button class="nav-btn flex items-center space-x-2 text-gray-700 hover:text-primary-500 transition-all duration-300 p-2 rounded-xl group">
                                <div class="w-8 h-8 bg-gradient-to-r from-primary-500 to-secondary-500 rounded-full flex items-center justify-center text-white text-sm group-hover:scale-110 transition-transform duration-300">
                                    <i class="fas fa-user"></i>
                                </div>
                                <span class="font-medium group-hover:font-semibold transition-all duration-300">Mi Cuenta</span>
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
                        <button class="btn-circle nav-btn text-gray-700 hover:text-primary-500 transition-all duration-300 group btn-pulse">
                            <i class="fas fa-heart text-xl group-hover:scale-110 transition-transform duration-300"></i>
                            <?php if (isset($_SESSION['wishlist']) && count($_SESSION['wishlist']) > 0): ?>
                            <span class="absolute -top-1 -right-1 bg-gradient-to-r from-red-500 to-pink-500 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center font-medium shadow-lg animate-pulse">
                                <?php echo count($_SESSION['wishlist']); ?>
                            </span>
                            <?php endif; ?>
                        </button>

                        <!-- Shopping Cart -->
                        <a href="carrito.php" class="btn-circle nav-btn text-gray-700 hover:text-primary-500 transition-all duration-300 group relative">
                            <i class="fas fa-shopping-bag text-xl group-hover:scale-110 transition-transform duration-300"></i>
                            <span class="absolute -top-1 -right-1 bg-gradient-to-r from-primary-500 to-secondary-500 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center font-medium shadow-lg animate-pulse" id="cart-count">
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
                        <button class="nav-btn flex items-center space-x-3 px-6 py-4 text-gray-700 hover:text-primary-500 transition-all duration-300 font-medium group">
                            <i class="fas fa-th-large group-hover:rotate-12 transition-transform duration-300"></i>
                            <span class="group-hover:font-semibold transition-all duration-300">Categorías</span>
                            <i class="fas fa-chevron-down text-sm transition-transform group-hover:rotate-180 duration-300"></i>
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
                        <div class="bg-gradient-to-r text-white px-6 py-2 rounded-full text-sm font-medium shadow-lg border border-white/20" style="background: linear-gradient(135deg, #b08d80 0%, #c4a575 100%);">
                            <i class="fas fa-shipping-fast mr-2"></i>
                            Envío GRATIS en compras +$150.000
                        </div>
                    </div>
                </div>
            </div>
        </nav>
    </header>
    
    <!-- Mobile Header -->
    <header class="md:hidden fixed top-0 left-0 right-0 z-60 bg-white/95 backdrop-blur-md shadow-lg header-visible" id="mobile-header">
        <div class="px-4 py-3">
            <div class="flex items-center justify-between">
                <!-- Mobile Menu Button -->
                <button class="btn-circle nav-btn text-gray-700 hover:text-primary-500 transition-all duration-300 group" id="mobile-menu-btn">
                    <i class="fas fa-bars text-xl group-hover:rotate-90 transition-transform duration-300"></i>
                </button>

                <!-- Mobile Logo -->
                <div class="flex items-center">
                    <a href="/" class="text-2xl font-serif font-bold gradient-text opacity-0" id="mobile-logo-odisea">
                        Odisea
                    </a>
                    <span class="ml-1 text-xs text-gray-500 font-light opacity-0" id="mobile-logo-makeup">STORE</span>
                </div>

                <!-- Mobile Actions -->
                <div class="flex items-center space-x-2">
                    <!-- Search Button -->
                    <button class="btn-circle nav-btn text-gray-700 hover:text-primary-500 transition-all duration-300 group" id="mobile-search-btn">
                        <i class="fas fa-search text-lg group-hover:scale-110 transition-transform duration-300"></i>
                    </button>

                    <!-- Cart Button -->
                    <a href="carrito.php" class="btn-circle nav-btn relative text-gray-700 hover:text-primary-500 transition-all duration-300 group">
                        <i class="fas fa-shopping-bag text-lg group-hover:scale-110 transition-transform duration-300"></i>
                        <span class="absolute -top-1 -right-1 bg-gradient-to-r from-primary-500 to-secondary-500 text-white text-xs rounded-full h-4 w-4 flex items-center justify-center font-medium shadow-lg animate-pulse" id="mobile-cart-count">
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
                       placeholder="Buscar productos..."
                       class="w-full px-4 py-3 pl-12 pr-4 bg-gray-50 rounded-2xl border-0 focus:ring-2 focus:ring-primary-500 focus:bg-white transition-all duration-300"
                       autocomplete="off">
                <i class="fas fa-search absolute left-4 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
            </form>
            
            <!-- Mobile Search Results -->
            <div id="mobile-search-results" class="absolute top-full left-4 right-4 bg-white rounded-2xl shadow-xl border border-gray-200 mt-2 max-h-96 overflow-auto z-50 hidden">
                <div id="mobile-search-results-content" class="p-4">
                    <!-- Los resultados se cargarán aquí -->
                </div>
            </div>
        </div>
    </header>

    <!-- Mobile Menu Drawer -->
    <div id="mobile-menu" class="md:hidden fixed inset-0 z-50 hidden">
        <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" onclick="closeMobileMenu()"></div>
        <div id="mobile-menu-panel" class="absolute left-0 top-0 bottom-0 w-80 bg-white transform -translate-x-full transition-transform duration-300 ease-out overflow-y-auto">
            <div class="p-6">
                <!-- Header -->
                <div class="flex items-center justify-between mb-8">
                    <h2 class="text-2xl font-serif font-bold gradient-text">Menú</h2>
                    <button onclick="closeMobileMenu()" class="w-8 h-8 flex items-center justify-center rounded-lg text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                
                <!-- Navigation -->
                <nav class="space-y-2 mb-8">
                    <a href="index.php" class="flex items-center px-4 py-3 rounded-xl text-gray-700 hover:bg-primary-50 hover:text-primary-600 transition-colors">
                        <i class="fas fa-home w-5 mr-3"></i>
                        Inicio
                    </a>
                    <a href="catalogo.php" class="flex items-center px-4 py-3 rounded-xl text-gray-700 hover:bg-primary-50 hover:text-primary-600 transition-colors">
                        <i class="fas fa-th-large w-5 mr-3"></i>
                        Catálogo
                    </a>
                    <a href="ofertas.php" class="flex items-center px-4 py-3 rounded-xl text-gray-700 hover:bg-primary-50 hover:text-primary-600 transition-colors">
                        <i class="fas fa-tags w-5 mr-3"></i>
                        Ofertas
                    </a>
                    <a href="marcas.php" class="flex items-center px-4 py-3 rounded-xl text-gray-700 hover:bg-primary-50 hover:text-primary-600 transition-colors">
                        <i class="fas fa-star w-5 mr-3"></i>
                        Marcas
                    </a>
                </nav>
                
                <!-- Categories -->
                <div class="mb-8">
                    <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-4">Categorías</h3>
                    <div class="space-y-1">
                        <?php foreach (array_slice($dbCategories, 0, 5) as $category): ?>
                        <a href="categoria.php?categoria=<?php echo $category['slug']; ?>" 
                           class="block px-4 py-2 text-gray-600 hover:text-primary-600 hover:bg-primary-50 rounded-lg transition-colors">
                            <?php echo htmlspecialchars($category['name']); ?>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <!-- User Actions -->
                <div class="pt-6 border-t border-gray-200">
                    <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="mi-cuenta.php" class="flex items-center px-4 py-3 rounded-xl text-gray-700 hover:bg-primary-50 hover:text-primary-600 transition-colors">
                        <i class="fas fa-user w-5 mr-3"></i>
                        Mi Cuenta
                    </a>
                    <a href="lista-deseos.php" class="flex items-center px-4 py-3 rounded-xl text-gray-700 hover:bg-primary-50 hover:text-primary-600 transition-colors">
                        <i class="fas fa-heart w-5 mr-3"></i>
                        Favoritos
                    </a>
                    <?php else: ?>
                    <a href="login.php" class="flex items-center px-4 py-3 rounded-xl text-gray-700 hover:bg-primary-50 hover:text-primary-600 transition-colors">
                        <i class="fas fa-sign-in-alt w-5 mr-3"></i>
                        Iniciar Sesión
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Main Content -->
    <div class="pt-20 md:pt-28"></div>

    <!-- Improved breadcrumb with better visual hierarchy -->
    <nav class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
        <ol class="flex items-center space-x-3 text-sm">
            <li><a href="<?php echo BASE_URL; ?>" class="text-gray-500 hover:text-blue-600 transition-colors">Inicio</a></li>
            <li><i class="fas fa-chevron-right text-xs text-gray-300"></i></li>
            <li><a href="<?php echo BASE_URL; ?>/catalogo.php" class="text-gray-500 hover:text-blue-600 transition-colors">Catálogo</a></li>
            <?php if ($category): ?>
            <li><i class="fas fa-chevron-right text-xs text-gray-300"></i></li>
            <li><a href="<?php echo BASE_URL; ?>/categoria.php?categoria=<?php echo urlencode($category['slug'] ?? $category['name']); ?>" class="text-gray-500 hover:text-blue-600 transition-colors"><?php echo htmlspecialchars($category['name']); ?></a></li>
            <?php endif; ?>
            <li><i class="fas fa-chevron-right text-xs text-gray-300"></i></li>
            <li class="text-gray-900 font-medium"><?php echo htmlspecialchars($product['name']); ?></li>
        </ol>
    </nav>

    <!-- Redesigned main content with improved layout and spacing -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pb-16">
        <div class="lg:grid lg:grid-cols-2 lg:gap-x-12 xl:gap-x-16 lg:items-start">
            <!-- Enhanced image gallery with better visual effects -->
            <div class="flex flex-col-reverse">
                <?php if (count($productImagesUrls) > 1): ?>
                <div class="hidden mt-6 w-full max-w-2xl mx-auto sm:block lg:max-w-none">
                    <div class="grid grid-cols-4 gap-4" id="thumbnails">
                        <?php foreach ($productImages as $index => $imageData): ?>
                        <button class="thumbnail <?php echo $index === 0 ? 'active' : ''; ?> relative h-20 bg-white rounded-xl flex items-center justify-center cursor-pointer hover:shadow-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 border-2 border-transparent overflow-hidden" onclick="changeMainImage(<?php echo $index; ?>)">
                            <img src="<?php echo $imageData['url']; ?>" alt="<?php echo htmlspecialchars($imageData['alt']); ?>" class="w-full h-full object-center object-cover rounded-lg" loading="lazy">
                        </button>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <div class="sm:hidden flex justify-center space-x-2 mt-6" id="mobile-dots">
                    <?php foreach ($productImages as $index => $imageData): ?>
                    <button class="mobile-dot <?php echo $index === 0 ? 'active' : ''; ?> w-2 h-2 rounded-full bg-gray-300" onclick="changeMainImage(<?php echo $index; ?>)" data-index="<?php echo $index; ?>"></button>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <div class="w-full aspect-square relative">
                    <div class="bg-white rounded-2xl overflow-hidden relative h-96 sm:h-[500px] md:h-full shadow-lg">
                        <img id="mainImage" src="<?php echo $productImagesUrls[0] ?? ($productImages[0]['url'] ?? ''); ?>" alt="<?php echo htmlspecialchars($productImages[0]['alt'] ?? $product['name']); ?>" class="w-full h-full object-center object-cover image-zoom fade-in">
                        
                        <?php if (count($productImagesUrls) > 1): ?>
                        <button onclick="previousImage()" class="absolute left-4 top-1/2 transform -translate-y-1/2 btn-circle glass-btn hover:scale-110 group">
                            <i class="fas fa-chevron-left text-gray-700 group-hover:text-primary-600 transition-colors duration-300"></i>
                        </button>
                        <button onclick="nextImage()" class="absolute right-4 top-1/2 transform -translate-y-1/2 btn-circle glass-btn hover:scale-110 group">
                            <i class="fas fa-chevron-right text-gray-700 group-hover:text-primary-600 transition-colors duration-300"></i>
                        </button>
                        <?php endif; ?>
                        
                        <?php if ($discount > 0): ?>
                        <div class="absolute top-6 left-6">
                            <span class="bg-gradient-to-r from-red-500 to-pink-500 text-white px-4 py-2 rounded-full text-sm font-semibold shadow-lg">
                                -<?php echo $discount; ?>% OFF
                            </span>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Redesigned product information with better typography and spacing -->
            <div class="mt-10 px-4 sm:px-0 lg:mt-0">
                <div class="space-y-6">
                    <div>
                        <h1 class="text-3xl md:text-4xl font-bold tracking-tight text-gray-900 leading-tight"><?php echo htmlspecialchars($product['name']); ?></h1>
                        
                        <?php if ($brand): ?>
                        <p class="mt-2 text-lg font-medium text-blue-600"><?php echo htmlspecialchars($brand['name']); ?></p>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Enhanced rating display -->
                    <div class="flex flex-col sm:flex-row sm:items-center gap-4">
                        <div class="flex items-center space-x-2">
                            <div class="star-rating text-lg">
                                <?php 
                                $rating = $averageRating ?: 0;
                                for ($i = 1; $i <= 5; $i++): 
                                ?>
                                    <i class="fas fa-star star <?php echo $i <= $rating ? 'active' : ''; ?>"></i>
                                <?php endfor; ?>
                            </div>
                            <span class="text-sm font-medium text-gray-700"><?php echo number_format($rating, 1); ?></span>
                        </div>
                        <?php if ($reviewCount > 0): ?>
                        <a href="#reviews" class="text-sm text-blue-600 hover:text-blue-700 font-medium transition-colors">
                            <?php echo $reviewCount; ?> reseña<?php echo $reviewCount > 1 ? 's' : ''; ?>
                        </a>
                        <?php endif; ?>
                    </div>

                    <!-- Improved price display with better visual hierarchy -->
                    <div class="space-y-2">
                        <div class="flex items-baseline space-x-3">
                            <p class="text-4xl font-bold text-gray-900">$<?php echo number_format($product['price'], 0, ',', '.'); ?></p>
                            <?php if (!empty($product['compare_price']) && $product['compare_price'] > $product['price']): ?>
                            <p class="text-xl text-gray-500 line-through">$<?php echo number_format($product['compare_price'], 0, ',', '.'); ?></p>
                            <?php endif; ?>
                        </div>
                        <?php if (!empty($product['compare_price']) && $product['compare_price'] > $product['price']): ?>
                        <p class="text-sm text-green-600 font-semibold bg-green-50 px-3 py-1 rounded-full inline-block">
                            Ahorra $<?php echo number_format($product['compare_price'] - $product['price'], 0, ',', '.'); ?> (<?php echo $discount; ?>%)
                        </p>
                        <?php endif; ?>
                    </div>

                    <!-- Enhanced description section -->
                    <div class="prose prose-gray max-w-none">
                        <p class="text-gray-700 leading-relaxed"><?php echo nl2br(htmlspecialchars($product['description'])); ?></p>
                    </div>

                    <?php if (!empty($product['specifications'])): ?>
                    <div class="bg-gray-50 rounded-xl p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-3">Especificaciones</h3>
                        <div class="text-sm text-gray-700 leading-relaxed">
                            <?php echo nl2br(htmlspecialchars($product['specifications'])); ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Redesigned color selection with better visual feedback -->
                    <div>
                        <h3 class="text-sm font-semibold text-gray-900 mb-3">Color</h3>
                        <div class="flex items-center space-x-3">
                            <label class="relative cursor-pointer group">
                                <input type="radio" name="color-choice" value="Negro" class="sr-only" checked>
                                <span class="block h-10 w-10 bg-gray-900 border-2 border-gray-300 rounded-full group-hover:scale-110 transition-transform"></span>
                                <span class="absolute inset-0 rounded-full border-2 border-blue-500 opacity-0 group-hover:opacity-100 transition-opacity"></span>
                            </label>
                            <label class="relative cursor-pointer group">
                                <input type="radio" name="color-choice" value="Blanco" class="sr-only">
                                <span class="block h-10 w-10 bg-white border-2 border-gray-300 rounded-full group-hover:scale-110 transition-transform"></span>
                                <span class="absolute inset-0 rounded-full border-2 border-blue-500 opacity-0 group-hover:opacity-100 transition-opacity"></span>
                            </label>
                            <label class="relative cursor-pointer group">
                                <input type="radio" name="color-choice" value="Azul" class="sr-only">
                                <span class="block h-10 w-10 bg-blue-500 border-2 border-gray-300 rounded-full group-hover:scale-110 transition-transform"></span>
                                <span class="absolute inset-0 rounded-full border-2 border-blue-500 opacity-0 group-hover:opacity-100 transition-opacity"></span>
                            </label>
                        </div>
                    </div>

                    <!-- Enhanced quantity and cart section with better button design -->
                    <div class="space-y-4">
                        <div class="flex items-center space-x-4">
                            <div>
                                <label for="quantity" class="block text-sm font-medium text-gray-700 mb-2">Cantidad</label>
                                <select id="quantity" name="quantity" class="rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-base font-medium px-4 py-2">
                                    <option value="1">1</option>
                                    <option value="2">2</option>
                                    <option value="3">3</option>
                                    <option value="4">4</option>
                                    <option value="5">5</option>
                                </select>
                            </div>
                            <div class="flex-1">
                                <button type="button" onclick="addToCart(<?php echo $product['id']; ?>, event); return false;" class="w-full elegant-btn btn-primary-elegant text-white rounded-xl py-4 px-8 flex items-center justify-center text-base font-semibold focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 group">
                                    <i class="fas fa-shopping-cart mr-3 group-hover:scale-110 transition-transform duration-300"></i>
                                    <span class="group-hover:tracking-wide transition-all duration-300">Añadir al carrito</span>
                                </button>
                            </div>
                        </div>
                        
                        <div class="grid grid-cols-2 gap-3">
                            <button type="button" onclick="addToWishlist(<?php echo $product['id']; ?>)" class="elegant-btn btn-secondary-elegant rounded-xl py-3 px-6 flex items-center justify-center text-sm font-semibold focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-secondary-500 group">
                                <i class="fas fa-heart mr-2 group-hover:scale-110 transition-transform duration-300"></i>
                                <span class="group-hover:tracking-wide transition-all duration-300">Favoritos</span>
                            </button>
                            
                            <a href="<?php echo BASE_URL; ?>/carrito.php" class="bg-gray-900 hover:bg-gray-800 text-white rounded-xl py-3 px-6 flex items-center justify-center text-sm font-semibold transition-all focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2">
                                Ver carrito
                            </a>
                        </div>
                    </div>

                    <!-- Enhanced shipping info with better visual design -->
                    <div class="bg-gradient-to-r from-green-50 to-blue-50 rounded-xl p-6 space-y-3">
                        <div class="flex items-center text-sm text-gray-700">
                            <div class="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center mr-3">
                                <i class="fas fa-truck text-green-600 text-xs"></i>
                            </div>
                            <span class="font-medium">Envío gratis en pedidos superiores a €50</span>
                        </div>
                        <div class="flex items-center text-sm text-gray-700">
                            <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center mr-3">
                                <i class="fas fa-undo text-blue-600 text-xs"></i>
                            </div>
                            <span class="font-medium">Devoluciones gratuitas en 30 días</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Section divider -->
        <div class="section-divider my-20"></div>

        <!-- Redesigned reviews section with better layout and styling -->
        <div class="mt-20" id="reviews">
            <div class="text-center mb-12">
                <h2 class="text-3xl font-bold text-gray-900 mb-4">Reseñas de clientes</h2>
                <p class="text-gray-600 max-w-2xl mx-auto">Descubre lo que opinan nuestros clientes sobre este producto</p>
            </div>
            
            <div class="grid grid-cols-1 gap-8 lg:grid-cols-3 mb-12">
                <!-- Enhanced review summary with better visual design -->
                <div class="lg:col-span-1">
                    <div class="bg-white rounded-2xl border border-gray-100 p-8 hover-lift">
                        <div class="text-center">
                            <div class="star-rating text-3xl justify-center mb-3">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                <i class="fas fa-star star <?php echo $i <= round($averageRating) ? 'active' : ''; ?>"></i>
                                <?php endfor; ?>
                            </div>
                            <div class="text-4xl font-bold text-gray-900 mb-2"><?php echo number_format($averageRating, 1); ?></div>
                            <p class="text-gray-600 mb-6">Basado en <?php echo $reviewCount; ?> reseña<?php echo $reviewCount != 1 ? 's' : ''; ?></p>
                        </div>
                        
                        <div class="space-y-3">
                            <?php 
                            $totalReviews = $reviewCount > 0 ? $reviewCount : 1;
                            for ($rating = 5; $rating >= 1; $rating--): 
                                $count = $ratingDistribution[$rating] ?? 0;
                                $percentage = $totalReviews > 0 ? round(($count / $totalReviews) * 100) : 0;
                            ?>
                            <div class="flex items-center text-sm">
                                <span class="w-4 text-gray-600 font-medium"><?php echo $rating; ?></span>
                                <i class="fas fa-star text-yellow-400 ml-2 mr-3 text-xs"></i>
                                <div class="flex-1 bg-gray-100 rounded-full h-2 mx-3 overflow-hidden">
                                    <div class="bg-gradient-to-r from-yellow-400 to-orange-400 h-2 rounded-full transition-all duration-500" style="width: <?php echo $percentage; ?>%"></div>
                                </div>
                                <span class="text-gray-600 font-medium w-10 text-right"><?php echo $percentage; ?>%</span>
                            </div>
                            <?php endfor; ?>
                        </div>
                    </div>
                </div>

                <!-- Enhanced review form with better styling -->
                <div class="lg:col-span-2">
                    <div class="bg-white rounded-2xl border border-gray-100 p-8 hover-lift">
                        <h3 class="text-xl font-semibold text-gray-900 mb-6">Escribe una reseña</h3>
                        <form id="reviewForm" class="space-y-6">
                            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                                <div>
                                    <label for="reviewerName" class="block text-sm font-medium text-gray-700 mb-2">Nombre</label>
                                    <input type="text" id="reviewerName" name="reviewerName" required class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 transition-colors">
                                </div>
                                <div>
                                    <label for="reviewerEmail" class="block text-sm font-medium text-gray-700 mb-2">Email</label>
                                    <input type="email" id="reviewerEmail" name="reviewerEmail" required class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 transition-colors">
                                </div>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-3">Calificación</label>
                                <div class="star-rating text-3xl" id="userRating">
                                    <i class="fas fa-star star" data-rating="1"></i>
                                    <i class="fas fa-star star" data-rating="2"></i>
                                    <i class="fas fa-star star" data-rating="3"></i>
                                    <i class="fas fa-star star" data-rating="4"></i>
                                    <i class="fas fa-star star" data-rating="5"></i>
                                </div>
                            </div>

                            <div>
                                <label for="reviewTitle" class="block text-sm font-medium text-gray-700 mb-2">Título de la reseña</label>
                                <input type="text" id="reviewTitle" name="reviewTitle" required class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 transition-colors">
                            </div>

                            <div>
                                <label for="reviewText" class="block text-sm font-medium text-gray-700 mb-2">Tu reseña</label>
                                <textarea id="reviewText" name="reviewText" rows="4" required class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 transition-colors resize-none"></textarea>
                            </div>

                            <button type="submit" class="btn-primary text-white rounded-lg py-3 px-6 font-semibold focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                                Publicar reseña
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Enhanced individual reviews with better card design -->
            <div class="space-y-6" id="reviewsList">
                <?php if (empty($reviews)): ?>
                <div class="text-center py-16 bg-white rounded-2xl border border-gray-100">
                    <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-comments text-2xl text-gray-400"></i>
                    </div>
                    <h3 class="text-lg font-medium text-gray-900 mb-2">Aún no hay reseñas</h3>
                    <p class="text-gray-600">¡Sé el primero en escribir una reseña!</p>
                </div>
                <?php else: ?>
                    <?php foreach ($reviews as $review): ?>
                    <?php 
                        $display_name = !empty($review['reviewer_name']) 
                            ? $review['reviewer_name'] 
                            : trim($review['first_name'] . ' ' . $review['last_name']);
                        
                        $initials = '';
                        if (!empty($review['first_name']) && !empty($review['last_name'])) {
                            $initials = strtoupper(substr($review['first_name'], 0, 1) . substr($review['last_name'], 0, 1));
                        } elseif (!empty($display_name)) {
                            $names = explode(' ', $display_name);
                            $initials = strtoupper(substr($names[0], 0, 1));
                            if (count($names) > 1) {
                                $initials .= strtoupper(substr($names[count($names)-1], 0, 1));
                            }
                        }
                    ?>
                    <div class="review-card bg-white rounded-2xl p-6 hover-lift" data-review-id="<?php echo $review['id']; ?>">
                        <div class="flex items-start space-x-4">
                            <div class="flex-shrink-0">
                                <div class="w-12 h-12 bg-gradient-to-br from-blue-500 to-purple-500 rounded-full flex items-center justify-center">
                                    <span class="text-sm font-semibold text-white"><?php echo $initials; ?></span>
                                </div>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center justify-between mb-2">
                                    <h4 class="text-base font-semibold text-gray-900"><?php echo htmlspecialchars($display_name); ?></h4>
                                    <span class="text-sm text-gray-500"><?php echo timeAgo($review['created_at']); ?></span>
                                </div>
                                
                                <div class="flex items-center mb-3">
                                    <div class="star-rating">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <i class="fas fa-star star <?php echo $i <= $review['rating'] ? 'active' : ''; ?>"></i>
                                        <?php endfor; ?>
                                    </div>
                                </div>
                                
                                <?php if (!empty($review['title'])): ?>
                                <h5 class="text-base font-medium text-gray-900 mb-2"><?php echo htmlspecialchars($review['title']); ?></h5>
                                <?php endif; ?>
                                
                                <p class="text-gray-700 leading-relaxed mb-4"><?php echo nl2br(htmlspecialchars($review['comment'])); ?></p>
                                
                                <div class="flex items-center space-x-6 text-sm">
                                    <button class="like-btn flex items-center space-x-1 text-gray-500 hover:text-blue-600 transition-colors <?php echo $review['user_has_liked'] ? 'text-blue-600' : ''; ?>" 
                                            onclick="toggleLike(<?php echo $review['id']; ?>)">
                                        <i class="fas fa-thumbs-up"></i>
                                        <span class="like-count font-medium"><?php echo $review['like_count']; ?></span>
                                    </button>
                                    
                                    <button class="dislike-btn flex items-center space-x-1 text-gray-500 hover:text-red-600 transition-colors <?php echo $review['user_has_disliked'] ? 'text-red-600' : ''; ?>" 
                                            onclick="toggleDislike(<?php echo $review['id']; ?>)">
                                        <i class="fas fa-thumbs-down"></i>
                                        <span class="dislike-count font-medium"><?php echo $review['dislike_count'] ?? 0; ?></span>
                                    </button>
                                    
                                    <button class="flex items-center space-x-1 text-gray-500 hover:text-gray-700 transition-colors reply-btn" 
                                            onclick="toggleReplyForm(<?php echo $review['id']; ?>)">
                                        <i class="fas fa-reply"></i>
                                        <span>Responder</span>
                                    </button>
                                </div>
                                
                                <!-- Enhanced reply form -->
                                <div class="reply-form mt-4 hidden" id="replyForm<?php echo $review['id']; ?>">
                                    <div class="bg-gray-50 rounded-xl p-4">
                                        <textarea 
                                            class="w-full p-3 border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 resize-none transition-colors"
                                            rows="3" 
                                            placeholder="Escribe tu respuesta..."
                                            id="replyText<?php echo $review['id']; ?>"
                                            maxlength="500"></textarea>
                                        <div class="mt-3 flex justify-between items-center">
                                            <small class="text-gray-500">Máximo 500 caracteres</small>
                                            <div class="space-x-3">
                                                <button 
                                                    class="px-4 py-2 text-sm text-gray-600 hover:text-gray-800 transition-colors"
                                                    onclick="toggleReplyForm(<?php echo $review['id']; ?>)">
                                                    Cancelar
                                                </button>
                                                <button 
                                                    class="px-4 py-2 bg-blue-600 text-white text-sm rounded-lg hover:bg-blue-700 transition-colors"
                                                    onclick="submitReply(<?php echo $review['id']; ?>)">
                                                    Publicar
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Replies section -->
                                <?php if (!empty($review['replies'])): ?>
                                <div class="replies mt-6 space-y-4" id="replies<?php echo $review['id']; ?>">
                                    <?php foreach ($review['replies'] as $reply): ?>
                                    <div class="bg-gray-50 rounded-xl p-4 ml-8">
                                        <div class="flex items-center mb-2">
                                            <div class="w-8 h-8 bg-gradient-to-br from-green-500 to-teal-500 rounded-full flex items-center justify-center mr-3">
                                                <span class="text-xs font-medium text-white">
                                                    <?php echo strtoupper(substr($reply['first_name'], 0, 1) . substr($reply['last_name'], 0, 1)); ?>
                                                </span>
                                            </div>
                                            <div>
                                                <p class="text-sm font-medium text-gray-900">
                                                    <?php echo htmlspecialchars($reply['first_name'] . ' ' . $reply['last_name']); ?>
                                                </p>
                                                <p class="text-xs text-gray-500">
                                                    <?php echo timeAgo($reply['created_at']); ?>
                                                </p>
                                            </div>
                                        </div>
                                        <p class="text-sm text-gray-700 leading-relaxed">
                                            <?php echo nl2br(htmlspecialchars($reply['reply_text'])); ?>
                                        </p>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                                <?php else: ?>
                                <div class="replies mt-6 space-y-4 hidden" id="replies<?php echo $review['id']; ?>">
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <!-- Enhanced related products section with better design -->
    <?php if (!empty($relatedProducts)): ?>
    <section class="bg-white py-20">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">
                    Productos Relacionados
                </h2>
                <p class="text-lg text-gray-600 max-w-2xl mx-auto">
                    Descubre otros productos de la categoría 
                    <span class="font-semibold text-blue-600"><?php echo htmlspecialchars($category['name'] ?? 'similar'); ?></span> 
                    que también podrían interesarte
                </p>
            </div>

            <!-- Mobile Carousel -->
            <div class="block md:hidden relative">
                <div class="mobile-carousel flex space-x-6 pb-6" id="mobileCarousel">
                    <?php 
                    $mobileProducts = array_slice($relatedProducts, 0, 5);
                    foreach ($mobileProducts as $relatedProduct): 
                    ?>
                    <div class="mobile-carousel-item w-72 product-card bg-white rounded-2xl shadow-sm overflow-hidden">
                        <div class="relative aspect-square overflow-hidden">
                            <a href="details.php?id=<?php echo $relatedProduct['id']; ?>">
                                <img src="<?php echo htmlspecialchars($relatedProduct['main_image_url']); ?>" 
                                     alt="<?php echo htmlspecialchars($relatedProduct['name']); ?>"
                                     class="w-full h-full object-cover transition-transform duration-500 hover:scale-105"
                                     loading="lazy"
                                     onerror="this.src='<?php echo BASE_URL; ?>/public/images/product-placeholder-1.svg'">
                            </a>
                            
                            <?php if ($relatedProduct['discount_percentage'] > 0): ?>
                            <div class="absolute top-3 left-3">
                                <span class="bg-gradient-to-r from-red-500 to-pink-500 text-white px-3 py-1 rounded-full text-xs font-bold">
                                    -<?php echo $relatedProduct['discount_percentage']; ?>%
                                </span>
                            </div>
                            <?php endif; ?>
                        </div>

                        <div class="p-4">
                            <?php if (!empty($relatedProduct['brand_name'])): ?>
                            <p class="text-xs text-gray-500 mb-1 font-medium"><?php echo htmlspecialchars($relatedProduct['brand_name']); ?></p>
                            <?php endif; ?>
                            
                            <h3 class="font-semibold text-gray-900 mb-2 line-clamp-2 text-sm">
                                <a href="details.php?id=<?php echo $relatedProduct['id']; ?>" class="hover:text-blue-600 transition-colors">
                                    <?php echo htmlspecialchars($relatedProduct['name']); ?>
                                </a>
                            </h3>
                            
                            <div class="flex items-center justify-between mb-3">
                                <div class="flex items-center space-x-2">
                                    <span class="text-lg font-bold text-gray-900">
                                        $<?php echo number_format($relatedProduct['price'], 0, ',', '.'); ?>
                                    </span>
                                    <?php if (!empty($relatedProduct['compare_price']) && $relatedProduct['compare_price'] > $relatedProduct['price']): ?>
                                    <span class="text-sm text-gray-400 line-through">
                                        $<?php echo number_format($relatedProduct['compare_price'], 0, ',', '.'); ?>
                                    </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <?php if ($relatedProduct['inventory_quantity'] > 0): ?>
                            <button onclick="addToCart(<?php echo $relatedProduct['id']; ?>)" 
                                    class="w-full btn-primary text-white py-2.5 rounded-xl text-sm font-semibold">
                                <i class="fas fa-shopping-cart mr-2"></i>Agregar
                            </button>
                            <?php else: ?>
                            <button disabled class="w-full bg-gray-300 text-gray-500 py-2.5 rounded-xl text-sm font-semibold cursor-not-allowed">
                                Agotado
                            </button>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="flex justify-center space-x-2 mt-6">
                    <div class="flex space-x-1" id="carouselIndicators"></div>
                </div>
            </div>

            <!-- Desktop Grid -->
            <div class="hidden md:grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-8">
                <?php foreach ($relatedProducts as $relatedProduct): ?>
                <div class="product-card bg-white rounded-2xl shadow-sm overflow-hidden group">
                    <div class="relative aspect-square overflow-hidden">
                        <a href="details.php?id=<?php echo $relatedProduct['id']; ?>">
                            <img src="<?php echo htmlspecialchars($relatedProduct['main_image_url']); ?>" 
                                 alt="<?php echo htmlspecialchars($relatedProduct['name']); ?>"
                                 class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-105"
                                 loading="lazy"
                                 onerror="this.src='<?php echo BASE_URL; ?>/public/images/product-placeholder-1.svg'">
                        </a>
                        
                        <?php if ($relatedProduct['discount_percentage'] > 0): ?>
                        <div class="absolute top-4 left-4">
                            <span class="bg-gradient-to-r from-red-500 to-pink-500 text-white px-3 py-1 rounded-full text-sm font-bold">
                                -<?php echo $relatedProduct['discount_percentage']; ?>%
                            </span>
                        </div>
                        <?php endif; ?>
                        
                        <div class="absolute top-4 right-4 opacity-0 group-hover:opacity-100 transition-opacity duration-300">
                            <div class="flex flex-col space-y-2">
                                <button onclick="toggleWishlist(<?php echo $relatedProduct['id']; ?>)" 
                                        class="w-10 h-10 glass-effect rounded-full flex items-center justify-center hover:bg-red-500 hover:text-white transition-all"
                                        title="Agregar a favoritos">
                                    <i class="fas fa-heart text-sm"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="p-6">
                        <?php if (!empty($relatedProduct['brand_name'])): ?>
                        <p class="text-sm text-gray-500 mb-2 font-medium"><?php echo htmlspecialchars($relatedProduct['brand_name']); ?></p>
                        <?php endif; ?>
                        
                        <h3 class="font-semibold text-gray-900 mb-3 line-clamp-2 group-hover:text-blue-600 transition-colors">
                            <a href="details.php?id=<?php echo $relatedProduct['id']; ?>">
                                <?php echo htmlspecialchars($relatedProduct['name']); ?>
                            </a>
                        </h3>
                        
                        <div class="flex items-center justify-between mb-4">
                            <div class="flex items-center space-x-2">
                                <span class="text-xl font-bold text-gray-900">
                                    $<?php echo number_format($relatedProduct['price'], 0, ',', '.'); ?>
                                </span>
                                <?php if (!empty($relatedProduct['compare_price']) && $relatedProduct['compare_price'] > $relatedProduct['price']): ?>
                                <span class="text-sm text-gray-400 line-through">
                                    $<?php echo number_format($relatedProduct['compare_price'], 0, ',', '.'); ?>
                                </span>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="flex items-center justify-between mb-4">
                            <div class="flex text-yellow-400">
                                <?php for ($i = 0; $i < 5; $i++): ?>
                                <i class="fas fa-star text-sm"></i>
                                <?php endfor; ?>
                            </div>
                            
                            <?php if ($relatedProduct['inventory_quantity'] > 0): ?>
                                <span class="text-sm text-green-600 flex items-center">
                                    <i class="fas fa-check-circle mr-1 text-xs"></i>
                                    Disponible
                                </span>
                            <?php else: ?>
                                <span class="text-sm text-red-600 flex items-center">
                                    <i class="fas fa-times-circle mr-1 text-xs"></i>
                                    Agotado
                                </span>
                            <?php endif; ?>
                        </div>
                        
                        <?php if ($relatedProduct['inventory_quantity'] > 0): ?>
                        <button onclick="addToCart(<?php echo $relatedProduct['id']; ?>)" 
                                class="w-full btn-primary text-white py-3 rounded-xl font-semibold">
                            <i class="fas fa-shopping-cart mr-2"></i>
                            Agregar al carrito
                        </button>
                        <?php else: ?>
                        <button disabled class="w-full bg-gray-300 text-gray-500 py-3 rounded-xl font-semibold cursor-not-allowed">
                            Agotado
                        </button>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <?php if ($category): ?>
            <div class="text-center mt-12">
                <a href="categoria.php?categoria=<?php echo urlencode($category['slug'] ?? $category['name']); ?>" 
                   class="inline-flex items-center btn-primary text-white px-8 py-4 rounded-xl font-semibold text-lg hover-lift">
                    Ver todos los productos de <?php echo htmlspecialchars($category['name']); ?>
                    <i class="fas fa-arrow-right ml-3"></i>
                </a>
            </div>
            <?php endif; ?>
        </div>
    </section>
    <?php endif; ?>

    <!-- Enhanced footer with better design -->
    <footer class="bg-gray-900 text-white">
        <div class="max-w-7xl mx-auto py-16 px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                <div class="md:col-span-2">
                    <h3 class="text-2xl font-bold bg-gradient-to-r from-blue-400 to-purple-400 bg-clip-text text-transparent mb-4">OdiseaStore</h3>
                    <p class="text-gray-400 text-sm leading-relaxed max-w-md">Tu tienda de confianza para productos tecnológicos de alta calidad. Ofrecemos la mejor selección con garantía y servicio excepcional.</p>
                </div>
                <div>
                    <h4 class="text-white font-semibold mb-4">Productos</h4>
                    <ul class="space-y-2">
                        <li><a href="#" class="text-gray-400 hover:text-white text-sm transition-colors">Auriculares</a></li>
                        <li><a href="#" class="text-gray-400 hover:text-white text-sm transition-colors">Smartphones</a></li>
                        <li><a href="#" class="text-gray-400 hover:text-white text-sm transition-colors">Laptops</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="text-white font-semibold mb-4">Soporte</h4>
                    <ul class="space-y-2">
                        <li><a href="#" class="text-gray-400 hover:text-white text-sm transition-colors">Contacto</a></li>
                        <li><a href="#" class="text-gray-400 hover:text-white text-sm transition-colors">Devoluciones</a></li>
                        <li><a href="#" class="text-gray-400 hover:text-white text-sm transition-colors">Garantía</a></li>
                    </ul>
                </div>
            </div>
            <div class="section-divider my-8"></div>
            <div class="flex flex-col md:flex-row justify-between items-center">
                <p class="text-gray-400 text-sm">&copy; 2024 OdiseaStore. Todos los derechos reservados.</p>
                <div class="flex space-x-6 mt-4 md:mt-0">
                    <a href="#" class="text-gray-400 hover:text-white transition-colors">
                        <i class="fab fa-facebook-f text-lg"></i>
                    </a>
                    <a href="#" class="text-gray-400 hover:text-white transition-colors">
                        <i class="fab fa-twitter text-lg"></i>
                    </a>
                    <a href="#" class="text-gray-400 hover:text-white transition-colors">
                        <i class="fab fa-instagram text-lg"></i>
                    </a>
                </div>
            </div>
        </div>
    </footer>

    <!-- Enhanced login modal with better design -->
    <div id="loginModal" class="fixed inset-0 bg-black bg-opacity-50 backdrop-blur-sm overflow-y-auto h-full w-full z-50 hidden">
        <div class="relative top-20 mx-auto p-6 border-0 w-96 shadow-2xl rounded-2xl bg-white">
            <div class="text-center">
                <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-gradient-to-br from-pink-500 to-purple-500 mb-6">
                    <i class="fas fa-heart text-white text-2xl"></i>
                </div>
                <h3 class="text-xl font-bold text-gray-900 mb-4">Iniciar Sesión Requerido</h3>
                <p class="text-gray-600 mb-8 leading-relaxed">
                    Para agregar productos a tu lista de favoritos, necesitas iniciar sesión en tu cuenta.
                </p>
                <div class="space-y-3">
                    <button id="loginBtn" class="w-full btn-primary text-white py-3 rounded-xl font-semibold">
                        Iniciar Sesión
                    </button>
                    <button id="continueGuestBtn" class="w-full bg-gray-100 text-gray-700 py-3 rounded-xl font-semibold hover:bg-gray-200 transition-colors">
                        Continuar como Invitado
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Header functionality
        document.addEventListener('DOMContentLoaded', function() {
            // Mobile menu functionality
            const mobileMenuBtn = document.getElementById('mobile-menu-btn');
            const mobileMenu = document.getElementById('mobile-menu');
            const mobileMenuPanel = document.getElementById('mobile-menu-panel');
            const mobileSearchBtn = document.getElementById('mobile-search-btn');
            const mobileSearchBar = document.getElementById('mobile-search-bar');

            // Mobile menu toggle
            if (mobileMenuBtn && mobileMenu && mobileMenuPanel) {
                mobileMenuBtn.addEventListener('click', function() {
                    mobileMenu.classList.remove('hidden');
                    setTimeout(() => {
                        mobileMenuPanel.classList.remove('-translate-x-full');
                    }, 10);
                });

                // Close mobile menu function
                window.closeMobileMenu = function() {
                    mobileMenuPanel.classList.add('-translate-x-full');
                    setTimeout(() => {
                        mobileMenu.classList.add('hidden');
                    }, 300);
                };
            }

            // Mobile search toggle
            if (mobileSearchBtn && mobileSearchBar) {
                mobileSearchBtn.addEventListener('click', function() {
                    if (mobileSearchBar.classList.contains('hidden')) {
                        mobileSearchBar.classList.remove('hidden');
                        const searchInput = mobileSearchBar.querySelector('input');
                        if (searchInput) {
                            setTimeout(() => searchInput.focus(), 100);
                        }
                    } else {
                        mobileSearchBar.classList.add('hidden');
                    }
                });
            }

            // Header scroll behavior
            let lastScrollTop = 0;
            const desktopHeader = document.getElementById('desktop-header');
            const mobileHeader = document.getElementById('mobile-header');
            const scrollThreshold = 100;

            function handleHeaderScroll() {
                const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
                
                // Only hide header when scrolling down significantly
                if (scrollTop > scrollThreshold) {
                    if (scrollTop > lastScrollTop) {
                        // Scrolling down - hide header
                        if (desktopHeader) {
                            desktopHeader.classList.remove('header-visible');
                            desktopHeader.classList.add('header-hidden');
                        }
                        if (mobileHeader) {
                            mobileHeader.classList.remove('header-visible');
                            mobileHeader.classList.add('header-hidden');
                        }
                    } else {
                        // Scrolling up - show header
                        if (desktopHeader) {
                            desktopHeader.classList.remove('header-hidden');
                            desktopHeader.classList.add('header-visible');
                        }
                        if (mobileHeader) {
                            mobileHeader.classList.remove('header-hidden');
                            mobileHeader.classList.add('header-visible');
                        }
                    }
                } else {
                    // At top of page - always show header
                    if (desktopHeader) {
                        desktopHeader.classList.remove('header-hidden');
                        desktopHeader.classList.add('header-visible');
                    }
                    if (mobileHeader) {
                        mobileHeader.classList.remove('header-hidden');
                        mobileHeader.classList.add('header-visible');
                    }
                }
                
                lastScrollTop = scrollTop <= 0 ? 0 : scrollTop; // For Mobile or negative scrolling
            }

            // Throttled scroll listener for better performance
            let ticking = false;
            function requestTick() {
                if (!ticking) {
                    requestAnimationFrame(handleHeaderScroll);
                    ticking = true;
                    setTimeout(() => { ticking = false; }, 100);
                }
            }

            window.addEventListener('scroll', requestTick);
            
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
                if (container) {
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
            }
            
            function hideResults(container) {
                if (container) {
                    container.classList.add('hidden');
                }
            }
            
            function displayResults(data, container) {
                if (!container || !data) return;
                
                if (!data.products || !data.brands || !data.categories) {
                    hideResults(container);
                    return;
                }

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
                if (data.categories && data.categories.length > 0) {
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
                if (data.brands && data.brands.length > 0) {
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
                if (data.products && data.products.length > 0) {
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
                                <a href="catalogo.php?q=${encodeURIComponent((desktopSearchInput && desktopSearchInput.value) || (mobileSearchInput && mobileSearchInput.value) || '')}" class="block text-center text-sm text-primary-600 hover:text-primary-700 font-medium">
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
                    if (desktopSearchInput) desktopSearchInput.blur();
                    if (mobileSearchInput) mobileSearchInput.blur();
                }
            });
            
            // Hide results when clicking outside
            document.addEventListener('click', function(e) {
                if (desktopResults && !e.target.closest('#desktop-search-input') && !e.target.closest('#desktop-search-results')) {
                    hideResults(desktopResults);
                }
                if (mobileResults && !e.target.closest('#mobile-search-input') && !e.target.closest('#mobile-search-results')) {
                    hideResults(mobileResults);
                }
                
                // Hide mobile search when clicking outside
                if (mobileSearchBar && !mobileSearchBar.contains(e.target) && (!mobileSearchBtn || !mobileSearchBtn.contains(e.target))) {
                    mobileSearchBar.classList.add('hidden');
                }
            });

            // Logo animations
            setTimeout(() => {
                const desktopLogoOdisea = document.getElementById('desktop-logo-odisea');
                const desktopLogoMakeup = document.getElementById('desktop-logo-makeup');
                const mobileLogoOdisea = document.getElementById('mobile-logo-odisea');
                const mobileLogoMakeup = document.getElementById('mobile-logo-makeup');

                if (desktopLogoOdisea) {
                    desktopLogoOdisea.style.opacity = '1';
                    desktopLogoOdisea.style.transform = 'translateY(0)';
                    desktopLogoOdisea.style.transition = 'all 0.8s ease-out';
                }
                if (desktopLogoMakeup) {
                    setTimeout(() => {
                        desktopLogoMakeup.style.opacity = '1';
                        desktopLogoMakeup.style.transition = 'all 0.6s ease-out';
                    }, 300);
                }
                if (mobileLogoOdisea) {
                    mobileLogoOdisea.style.opacity = '1';
                    mobileLogoOdisea.style.transform = 'translateY(0)';
                    mobileLogoOdisea.style.transition = 'all 0.8s ease-out';
                }
                if (mobileLogoMakeup) {
                    setTimeout(() => {
                        mobileLogoMakeup.style.opacity = '1';
                        mobileLogoMakeup.style.transition = 'all 0.6s ease-out';
                    }, 300);
                }
            }, 500);
        });
    </script>

    <script>
        // Debug: Verificar si CSRFProtection está funcionando
        <?php 
        try {
            $debugToken = CSRFProtection::generateGlobalToken();
            echo "console.log('Debug: Token generado en PHP:', '$debugToken');";
        } catch (Exception $e) {
            echo "console.error('Error generando token CSRF:', '" . addslashes($e->getMessage()) . "');";
        }
        ?>
        
        // Inicializar token CSRF global al cargar la página
        window.globalCSRFToken = '<?php echo CSRFProtection::generateGlobalToken(); ?>';
        console.log('Token CSRF inicial:', window.globalCSRFToken);
        console.log('Token CSRF inicial (length):', window.globalCSRFToken.length);
        
        // Función para obtener token CSRF
        function getCSRFToken() {
            if (window.globalCSRFToken) {
                console.log('Usando token guardado:', window.globalCSRFToken.substring(0, 10) + '...');
                return window.globalCSRFToken;
            }
            console.error('No hay token CSRF disponible');
            return null;
        }
        
        // Función para actualizar token CSRF
        function updateCSRFToken(newToken) {
            if (newToken) {
                window.globalCSRFToken = newToken;
                console.log('Token CSRF actualizado:', newToken.substring(0, 10) + '...');
            }
        }
        
        // Image gallery functionality
        const images = [
            <?php foreach ($productImagesUrls as $index => $imageUrl): ?>
            '<?php echo addslashes($imageUrl); ?>'<?php echo $index < count($productImagesUrls) - 1 ? ',' : ''; ?>
            <?php endforeach; ?>
        ];
        
        let currentImageIndex = 0;
        
        function changeMainImage(index) {
            currentImageIndex = index;
            const mainImage = document.getElementById('mainImage');
            mainImage.src = images[index];
            mainImage.classList.remove('fade-in');
            setTimeout(() => mainImage.classList.add('fade-in'), 10);
            
            // Update thumbnail active state
            const thumbnails = document.querySelectorAll('.thumbnail');
            thumbnails.forEach((thumb, i) => {
                if (i === index) {
                    thumb.classList.add('active');
                } else {
                    thumb.classList.remove('active');
                }
            });
            
            // Update mobile dots active state
            const mobileDots = document.querySelectorAll('.mobile-dot');
            mobileDots.forEach((dot, i) => {
                if (i === index) {
                    dot.classList.add('active');
                } else {
                    dot.classList.remove('active');
                }
            });
        }
        
        function nextImage() {
            currentImageIndex = (currentImageIndex + 1) % images.length;
            changeMainImage(currentImageIndex);
        }
        
        function previousImage() {
            currentImageIndex = (currentImageIndex - 1 + images.length) % images.length;
            changeMainImage(currentImageIndex);
        }
        
        // Star rating functionality for user reviews
        const userRatingElement = document.getElementById('userRating');
        const userRatingStars = userRatingElement ? userRatingElement.querySelectorAll('.star') : [];
        let userRating = 0;
        
        if (userRatingStars.length > 0) {
            userRatingStars.forEach((star, index) => {
                star.addEventListener('click', () => {
                    userRating = index + 1;
                    updateUserRating();
                });
                
                star.addEventListener('mouseenter', () => {
                    highlightStars(index + 1);
                });
            });
            
            if (userRatingElement) {
                userRatingElement.addEventListener('mouseleave', () => {
                    updateUserRating();
                });
            }
        }
        
        function highlightStars(rating) {
            if (userRatingStars.length > 0) {
                userRatingStars.forEach((star, index) => {
                    if (index < rating) {
                        star.classList.add('active');
                    } else {
                        star.classList.remove('active');
                    }
                });
            }
        }
        
        function updateUserRating() {
            highlightStars(userRating);
        }
        
        // Review form submission
        const reviewForm = document.getElementById('reviewForm');
        if (reviewForm) {
            reviewForm.addEventListener('submit', async function(e) {
                e.preventDefault();
                
                if (userRating === 0) {
                    alert('Por favor, selecciona una calificación');
                    return;
                }
            
            const formData = new FormData(this);
            const reviewData = {
                product_id: <?php echo $product_id; ?>,
                reviewer_name: formData.get('reviewerName'),
                rating: userRating,
                title: formData.get('reviewTitle'),
                comment: formData.get('reviewText')
            };
            
            try {
                const response = await fetch('<?php echo BASE_URL; ?>/review-submit.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(reviewData)
                });
                
                const result = await response.json();
                
                if (result.success) {
                    addReviewToDOM(result.review);
                    this.reset();
                    userRating = 0;
                    updateUserRating();
                    showNotification('¡Gracias por tu reseña! Ha sido publicada exitosamente.', 'success');
                    
                    // Actualizar estadísticas de reviews
                    location.reload(); // Recargar para actualizar las estadísticas
                } else {
                    showNotification(result.error || 'Error al enviar la reseña', 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                showNotification('Error al enviar la reseña', 'error');
            }
        });
        } // Cerrar el if del reviewForm
        
        function addReviewToDOM(review) {
            const reviewsList = document.getElementById('reviewsList');
            const reviewElement = document.createElement('div');
            reviewElement.className = 'bg-white rounded-lg border p-6';
            reviewElement.setAttribute('data-review-id', review.id);
            
            const starsHtml = Array.from({length: 5}, (_, i) => 
                `<i class="fas fa-star star ${i < review.rating ? 'active' : ''}"></i>`
            ).join('');
            
            reviewElement.innerHTML = `
                <div class="flex items-start justify-between">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="h-10 w-10 rounded-full bg-blue-500 flex items-center justify-center">
                                <span class="text-sm font-medium text-white">${review.reviewer_name.charAt(0).toUpperCase()}</span>
                            </div>
                        </div>
                        <div class="ml-4">
                            <h4 class="text-sm font-bold text-gray-900">${review.reviewer_name}</h4>
                            <div class="flex items-center mt-1">
                                <div class="star-rating">
                                    ${starsHtml}
                                </div>
                                <span class="ml-2 text-sm text-gray-600">ahora</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="mt-4">
                    <h5 class="text-sm font-medium text-gray-900">${review.title}</h5>
                    <p class="mt-2 text-sm text-gray-700">${review.comment}</p>
                </div>
                <div class="mt-4 flex items-center text-sm text-gray-500">
                    <button class="like-btn flex items-center hover:text-gray-700 transition-colors" 
                            onclick="toggleLike(${review.id})">
                        <i class="fas fa-thumbs-up mr-1"></i>
                        <span class="like-count">${review.like_count}</span>
                        <span class="ml-1">Me gusta</span>
                    </button>
                    
                    <button class="dislike-btn ml-3 flex items-center hover:text-gray-700 transition-colors" 
                            onclick="toggleDislike(${review.id})">
                        <i class="fas fa-thumbs-down mr-1"></i>
                        <span class="dislike-count">${review.dislike_count}</span>
                        <span class="ml-1">No me gusta</span>
                    </button>
                    
                    <button class="ml-4 flex items-center hover:text-gray-700 transition-colors reply-btn" 
                            onclick="toggleReplyForm(${review.id})">
                        <i class="fas fa-reply mr-1"></i>
                        Responder
                    </button>
                </div>
                
                <!-- Reply Form (hidden by default) -->
                <div id="replyForm${review.id}" class="hidden mt-4 pl-4 border-l-2 border-gray-200">
                    <textarea id="replyText${review.id}" class="w-full p-3 border border-gray-300 rounded-lg resize-none" 
                              rows="3" placeholder="Escribe tu respuesta..."></textarea>
                    <div class="mt-2 flex justify-end space-x-2">
                        <button onclick="toggleReplyForm(${review.id})" 
                                class="px-4 py-2 text-sm text-gray-600 hover:text-gray-800">Cancelar</button>
                        <button onclick="submitReply(${review.id})" 
                                class="px-4 py-2 bg-blue-600 text-white text-sm rounded hover:bg-blue-700">Enviar</button>
                    </div>
                </div>
                
                <!-- Replies Container -->
                <div id="replies${review.id}" class="hidden mt-4 space-y-3"></div>
            `;
            
            reviewsList.insertBefore(reviewElement, reviewsList.firstChild);
        }

        // Review form submission handler
        document.getElementById('reviewForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            // Get form data
            const formData = new FormData(e.target);
            const reviewData = {
                product_id: formData.get('product_id'),
                rating: formData.get('rating'),
                title: formData.get('title'),
                comment: formData.get('comment'),
                reviewer_name: formData.get('reviewerName')
            };
            
            // Validate required fields
            if (!reviewData.rating || !reviewData.title || !reviewData.comment) {
                showNotification('Por favor, completa todos los campos requeridos.', 'error');
                return;
            }
            
            try {
                const csrfToken = await getReviewCSRFToken();
                
                const response = await fetch('review-submit.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-Token': csrfToken
                    },
                    body: JSON.stringify({
                        ...reviewData,
                        csrf_token: csrfToken
                    })
                });
                
                if (!response.ok) {
                    throw new Error(`Error HTTP: ${response.status}`);
                }
                
                const result = await response.json();
                
                if (result.success) {
                    showNotification('Reseña enviada correctamente.', 'success');
                    e.target.reset();
                    
                    // Add the new review to DOM
                    if (result.review) {
                        addReviewToDOM({
                            id: result.review.id,
                            rating: result.review.rating,
                            title: result.review.title,
                            comment: result.review.comment,
                            reviewer_name: result.review.reviewer_name,
                            like_count: 0,
                            dislike_count: 0
                        });
                    }
                } else {
                    showNotification(result.message || 'Error al enviar la reseña.', 'error');
                }
            } catch (error) {
                console.error('Error submitting review:', error);
                showNotification('Error de conexión. Inténtalo de nuevo.', 'error');
            }
        });
        
        // Smooth scroll to reviews
        const reviewsLink = document.querySelector('a[href="#reviews"]');
        if (reviewsLink) {
            reviewsLink.addEventListener('click', function(e) {
                e.preventDefault();
                const reviewsSection = document.getElementById('reviews');
                if (reviewsSection) {
                    reviewsSection.scrollIntoView({
                        behavior: 'smooth'
                    });
                }
            });
        }
        
        // Cart functionality
        function addToCart(productId, event) {
            // Prevenir cualquier comportamiento por defecto
            if (event) {
                event.preventDefault();
                event.stopPropagation();
            }
            
            const quantity = document.getElementById('quantity').value;
            const csrfToken = getCSRFToken();
            
            console.log('=== CART ADD DEBUG ===');
            console.log('Product ID:', productId);
            console.log('Quantity:', quantity);
            console.log('CSRF Token:', csrfToken);
            console.log('Token length:', csrfToken ? csrfToken.length : 'null/undefined');
            console.log('Event object:', event);
            
            if (!csrfToken) {
                showNotification('Error de seguridad. Recarga la página.', 'error');
                return false;
            }
            
            // Usar FormData para asegurar que los datos se envíen correctamente
            const formData = new FormData();
            formData.append('product_id', productId);
            formData.append('quantity', quantity);
            formData.append('csrf_token', csrfToken);
            
            // Debug: mostrar lo que estamos enviando
            console.log('FormData entries:');
            for (let [key, value] of formData.entries()) {
                console.log(`  ${key}: ${value}`);
            }
            
            fetch('<?php echo BASE_URL; ?>/cart-add.php', {
                method: 'POST',
                body: formData  // No necesitamos Content-Type header con FormData
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.text(); // Primero obtener como texto para debuggear
            })
            .then(text => {
                try {
                    const data = JSON.parse(text);
                    if (data.success) {
                        showNotification('Producto agregado al carrito', 'success');
                        updateCartCount();
                    } else {
                        // Si se proporciona un nuevo token CSRF, guardarlo para futuras solicitudes
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
            
            return false; // Prevenir cualquier comportamiento por defecto
        }
        
        // Wishlist functionality
        function addToWishlist(productId) {
            const csrfToken = getWishlistCSRFToken();
            
            if (!csrfToken) {
                showNotification('Error de seguridad. Recarga la página.', 'error');
                return;
            }
            
            fetch('<?php echo BASE_URL; ?>/wishlist-toggle.php', {
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
                } else if (data.requires_login) {
                    // Mostrar modal de login
                    showLoginModal();
                } else {
                    showNotification(data.message || 'Error al procesar favoritos', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('Error al procesar favoritos', 'error');
            });
        }
        
        // Modal de login functionality
        function showLoginModal() {
            document.getElementById('loginModal').classList.remove('hidden');
        }
        
        function hideLoginModal() {
            document.getElementById('loginModal').classList.add('hidden');
        }
        
        // Event listeners para el modal - Verificar que existan los elementos
        document.addEventListener('DOMContentLoaded', function() {
            const loginBtn = document.getElementById('loginBtn');
            const continueGuestBtn = document.getElementById('continueGuestBtn');
            const loginModal = document.getElementById('loginModal');
            
            if (loginBtn) {
                loginBtn.addEventListener('click', function() {
                    window.location.href = '<?php echo BASE_URL; ?>/login.php';
                });
            }
            
            if (continueGuestBtn) {
                continueGuestBtn.addEventListener('click', function() {
                    hideLoginModal();
                    showNotification('Puedes seguir navegando como invitado', 'info');
                });
            }
            
            // Cerrar modal al hacer click fuera
            if (loginModal) {
                loginModal.addEventListener('click', function(e) {
                    if (e.target === this) {
                        hideLoginModal();
                    }
                });
            }
        });
        
        // Cerrar modal con ESC
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                hideLoginModal();
            }
        });
        
        // Notification system
        function showNotification(message, type = 'info') {
            const notification = document.createElement('div');
            const colors = {
                success: 'from-green-500 to-emerald-500',
                error: 'from-red-500 to-rose-500',
                warning: 'from-yellow-500 to-orange-500',
                info: 'from-blue-500 to-indigo-500'
            };

            notification.className = `fixed top-8 right-8 z-50 p-4 rounded-2xl shadow-2xl text-white max-w-sm transition-all duration-500 transform translate-x-full bg-gradient-to-r ${colors[type] || colors.info}`;
            notification.innerHTML = `
                <div class="flex items-center">
                    <span class="mr-3">${type === 'success' ? '✓' : type === 'error' ? '✗' : 'ℹ'}</span>
                    <span class="font-medium text-sm">${message}</span>
                    <button onclick="this.parentElement.parentElement.remove()" class="ml-4 text-white hover:text-gray-200">×</button>
                </div>
            `;

            document.body.appendChild(notification);
            setTimeout(() => notification.style.transform = 'translateX(0)', 100);
            setTimeout(() => {
                if (notification.parentElement) {
                    notification.style.transform = 'translateX(100%)';
                    setTimeout(() => notification.remove(), 500);
                }
            }, 4000);
        }
        
        // Update cart count
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
        
        // Initialize cart count on page load
        updateCartCount();
        
        // Review interaction functions
        // Dynamic CSRF token generation for reviews
        async function getReviewCSRFToken() {
            try {
                const response = await fetch('<?php echo BASE_URL; ?>/api/get-csrf-token.php?context=review');
                const data = await response.json();
                return data.token || '<?php echo CSRFProtection::generateToken("review"); ?>';
            } catch (error) {
                console.warn('Using fallback CSRF token');
                return '<?php echo CSRFProtection::generateToken("review"); ?>';
            }
        }
        
        async function toggleLike(reviewId) {
            const likeBtn = document.querySelector(`[data-review-id="${reviewId}"] .like-btn`);
            const dislikeBtn = document.querySelector(`[data-review-id="${reviewId}"] .dislike-btn`);
            const likeCount = likeBtn.querySelector('.like-count');
            
            try {
                const csrfToken = await getReviewCSRFToken();
                
                const requestData = { 
                    review_id: reviewId,
                    action: 'like',
                    csrf_token: csrfToken
                };
                
                console.log('Sending like request:', requestData);
                
                const response = await fetch('<?php echo BASE_URL; ?>/review-like-simple.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(requestData)
                });
                
                const data = await response.json();
                console.log('Like response:', data);
                
                if (data.success) {
                    if (data.authenticated) {
                        // Authenticated user - update from server
                        likeCount.textContent = data.like_count;
                        
                        if (data.action === 'added') {
                            likeBtn.classList.add('text-blue-600');
                            dislikeBtn.classList.remove('text-red-600'); // Remove dislike if present
                            showNotification('Like agregado', 'success');
                        } else {
                            likeBtn.classList.remove('text-blue-600');
                            showNotification('Like removido', 'info');
                        }
                    } else {
                        // Anonymous user - handle client-side
                        const likeKey = `like_${reviewId}`;
                        const dislikeKey = `dislike_${reviewId}`;
                        const currentlyLiked = localStorage.getItem(likeKey) === 'true';
                        
                        if (currentlyLiked) {
                            // Remove anonymous like
                            localStorage.removeItem(likeKey);
                            likeBtn.classList.remove('text-blue-600');
                            const currentCount = parseInt(likeCount.textContent) || 0;
                            likeCount.textContent = Math.max(0, currentCount - 1);
                            showNotification('Like removido', 'info');
                        } else {
                            // Add anonymous like and remove dislike if present
                            localStorage.setItem(likeKey, 'true');
                            localStorage.removeItem(dislikeKey);
                            likeBtn.classList.add('text-blue-600');
                            dislikeBtn.classList.remove('text-red-600');
                            const currentCount = parseInt(likeCount.textContent) || 0;
                            likeCount.textContent = currentCount + 1;
                            showNotification(data.message, 'success');
                        }
                    }
                } else {
                    showNotification(data.message || 'Error al procesar like', 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                showNotification('Error al procesar like', 'error');
            }
        }
        
        async function toggleDislike(reviewId) {
            const likeBtn = document.querySelector(`[data-review-id="${reviewId}"] .like-btn`);
            const dislikeBtn = document.querySelector(`[data-review-id="${reviewId}"] .dislike-btn`);
            const dislikeCount = dislikeBtn.querySelector('.dislike-count');
            
            try {
                const csrfToken = await getReviewCSRFToken();
                
                const requestData = { 
                    review_id: reviewId,
                    action: 'dislike',
                    csrf_token: csrfToken
                };
                
                console.log('Sending dislike request:', requestData);
                
                const response = await fetch('<?php echo BASE_URL; ?>/review-like-simple.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(requestData)
                });
                
                const data = await response.json();
                console.log('Dislike response:', data);
                
                if (data.success) {
                    if (data.authenticated) {
                        // Authenticated user - update from server
                        dislikeCount.textContent = data.dislike_count;
                        
                        if (data.action === 'added') {
                            dislikeBtn.classList.add('text-red-600');
                            likeBtn.classList.remove('text-blue-600'); // Remove like if present
                            showNotification('Dislike agregado', 'info');
                        } else {
                            dislikeBtn.classList.remove('text-red-600');
                            showNotification('Dislike removido', 'info');
                        }
                    } else {
                        // Anonymous user - handle client-side
                        const likeKey = `like_${reviewId}`;
                        const dislikeKey = `dislike_${reviewId}`;
                        const currentlyDisliked = localStorage.getItem(dislikeKey) === 'true';
                        
                        if (currentlyDisliked) {
                            // Remove anonymous dislike
                            localStorage.removeItem(dislikeKey);
                            dislikeBtn.classList.remove('text-red-600');
                            const currentCount = parseInt(dislikeCount.textContent) || 0;
                            dislikeCount.textContent = Math.max(0, currentCount - 1);
                            showNotification('Dislike removido', 'info');
                        } else {
                            // Add anonymous dislike and remove like if present
                            localStorage.setItem(dislikeKey, 'true');
                            localStorage.removeItem(likeKey);
                            dislikeBtn.classList.add('text-red-600');
                            likeBtn.classList.remove('text-blue-600');
                            const currentCount = parseInt(dislikeCount.textContent) || 0;
                            dislikeCount.textContent = currentCount + 1;
                            showNotification('Dislike agregado', 'info');
                        }
                    }
                } else {
                    showNotification(data.message || 'Error al procesar dislike', 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                showNotification('Error al procesar dislike', 'error');
            }
        }
        
        function toggleReplyForm(reviewId) {
            const replyForm = document.getElementById(`replyForm${reviewId}`);
            const replyText = document.getElementById(`replyText${reviewId}`);
            
            if (replyForm.classList.contains('hidden')) {
                replyForm.classList.remove('hidden');
                replyText.focus();
            } else {
                replyForm.classList.add('hidden');
                replyText.value = '';
            }
        }
        
        async function submitReply(reviewId) {
            const replyText = document.getElementById(`replyText${reviewId}`);
            const text = replyText.value.trim();
            
            if (text.length < 10) {
                showNotification('La respuesta debe tener al menos 10 caracteres', 'warning');
                return;
            }
            
            if (text.length > 500) {
                showNotification('La respuesta no puede exceder 500 caracteres', 'warning');
                return;
            }
            
            try {
                const csrfToken = await getReviewCSRFToken();
                
                const response = await fetch('<?php echo BASE_URL; ?>/review-reply.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-Token': csrfToken
                    },
                    body: JSON.stringify({ 
                        review_id: reviewId,
                        reply_text: text,
                        csrf_token: csrfToken
                    })
                });
                
                if (!response.ok) {
                    throw new Error(`Error HTTP: ${response.status}`);
                }
                
                const data = await response.json();
                
                if (data.success) {
                    showNotification('Respuesta enviada correctamente', 'success');
                    toggleReplyForm(reviewId);
                    replyText.value = '';
                    
                    // Reload replies to show the new one
                    loadReplies(reviewId);
                } else {
                    showNotification(data.message || 'Error al enviar respuesta', 'error');
                }
            } catch (error) {
                console.error('Error submitting reply:', error);
                showNotification('Error de conexión. Inténtalo de nuevo.', 'error');
            }
        }
        
        // Load replies for a review
        async function loadReplies(reviewId) {
            try {
                const response = await fetch(`review-replies.php?review_id=${reviewId}`);
                const result = await response.json();
                
                if (result.success && result.replies.length > 0) {
                    const repliesContainer = document.getElementById(`replies${reviewId}`);
                    repliesContainer.innerHTML = result.replies.map(reply => `
                        <div class="pl-4 border-l-2 border-gray-200">
                            <div class="flex items-start">
                                <div class="flex-shrink-0">
                                    <div class="h-8 w-8 rounded-full bg-gray-400 flex items-center justify-center">
                                        <span class="text-xs font-medium text-white">${reply.author_name.charAt(0).toUpperCase()}</span>
                                    </div>
                                </div>
                                <div class="ml-3">
                                    <h5 class="text-sm font-medium text-gray-900">${reply.author_name}</h5>
                                    <p class="text-sm text-gray-700 mt-1">${reply.reply_text}</p>
                                    <span class="text-xs text-gray-500">${reply.created_at}</span>
                                </div>
                            </div>
                        </div>
                    `).join('');
                    repliesContainer.classList.remove('hidden');
                }
            } catch (error) {
                console.error('Error loading replies:', error);
            }
        }
        
        // Initialize anonymous likes and dislikes on page load
        function initializeAnonymousReactions() {
            <?php 
            $isLoggedIn = (isset($_SESSION['customer_id']) && !empty($_SESSION['customer_id'])) || 
                         (isset($_SESSION['user_id']) && !empty($_SESSION['user_id']));
            if (!$isLoggedIn): 
            ?>
            document.querySelectorAll('[data-review-id]').forEach(reviewElement => {
                const reviewId = reviewElement.getAttribute('data-review-id');
                const likeBtn = reviewElement.querySelector('.like-btn');
                const dislikeBtn = reviewElement.querySelector('.dislike-btn');
                const likeKey = `like_${reviewId}`;
                const dislikeKey = `dislike_${reviewId}`;
                
                if (localStorage.getItem(likeKey) === 'true') {
                    likeBtn.classList.add('text-blue-600');
                }
                
                if (localStorage.getItem(dislikeKey) === 'true') {
                    dislikeBtn.classList.add('text-red-600');
                }
            });
            <?php endif; ?>
        }
        
        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            initializeAnonymousReactions();
            initializeMobileCarousel();
        });
        
        // Mobile Carousel Functionality
        function initializeMobileCarousel() {
            const carousel = document.getElementById('mobileCarousel');
            const indicators = document.getElementById('carouselIndicators');
            
            if (!carousel || !indicators) return;
            
            const items = carousel.querySelectorAll('.mobile-carousel-item');
            if (items.length === 0) return;
            
            let currentIndex = 0;
            const totalItems = items.length;
            
            // Generate indicators
            for (let i = 0; i < totalItems; i++) {
                const indicator = document.createElement('button');
                indicator.className = `w-2 h-2 rounded-full transition-colors ${i === 0 ? 'bg-primary-600' : 'bg-gray-300'}`;
                indicator.addEventListener('click', () => scrollToItem(i));
                indicators.appendChild(indicator);
            }
            
            // Scroll to specific item
            function scrollToItem(index) {
                if (index < 0 || index >= totalItems) return;
                
                const item = items[index];
                const scrollLeft = item.offsetLeft - carousel.offsetLeft;
                
                carousel.scrollTo({
                    left: scrollLeft,
                    behavior: 'smooth'
                });
                
                updateIndicators(index);
                currentIndex = index;
            }
            
            // Update indicators
            function updateIndicators(activeIndex) {
                const dots = indicators.querySelectorAll('button');
                dots.forEach((dot, index) => {
                    if (index === activeIndex) {
                        dot.classList.remove('bg-gray-300');
                        dot.classList.add('bg-primary-600');
                    } else {
                        dot.classList.remove('bg-primary-600');
                        dot.classList.add('bg-gray-300');
                    }
                });
            }
            
            // Handle scroll events
            let scrollTimeout;
            carousel.addEventListener('scroll', function() {
                clearTimeout(scrollTimeout);
                scrollTimeout = setTimeout(() => {
                    const scrollLeft = carousel.scrollLeft;
                    const itemWidth = items[0].offsetWidth + 16; // item width + gap
                    const newIndex = Math.round(scrollLeft / itemWidth);
                    
                    if (newIndex !== currentIndex && newIndex >= 0 && newIndex < totalItems) {
                        updateIndicators(newIndex);
                        currentIndex = newIndex;
                    }
                }, 100);
            });
            
            // Touch swipe support
            let startX = 0;
            let scrollStart = 0;
            
            carousel.addEventListener('touchstart', function(e) {
                startX = e.touches[0].clientX;
                scrollStart = carousel.scrollLeft;
            });
            
            carousel.addEventListener('touchmove', function(e) {
                if (!startX) return;
                
                const currentX = e.touches[0].clientX;
                const diff = startX - currentX;
                carousel.scrollLeft = scrollStart + diff;
            });
            
            carousel.addEventListener('touchend', function() {
                startX = 0;
                scrollStart = 0;
            });
        }
        
        // Related Products Carousel functionality
        function scrollCarousel(direction) {
            const carousel = document.getElementById('relatedProductsCarousel');
            const itemWidth = 288; // 280px + 8px gap
            const scrollAmount = direction === 'left' ? -itemWidth : itemWidth;
            
            carousel.scrollBy({
                left: scrollAmount,
                behavior: 'smooth'
            });
        }
        
        function scrollToSlide(index) {
            const carousel = document.getElementById('relatedProductsCarousel');
            const itemWidth = 288; // 280px + 8px gap
            
            carousel.scrollTo({
                left: index * itemWidth,
                behavior: 'smooth'
            });
            
            updateCarouselDots(index);
        }
        
        function updateCarouselDots(activeIndex) {
            const dots = document.querySelectorAll('.carousel-dot');
            dots.forEach((dot, index) => {
                if (index === activeIndex) {
                    dot.classList.remove('bg-gray-300');
                    dot.classList.add('bg-primary-600', 'active');
                } else {
                    dot.classList.remove('bg-primary-600', 'active');
                    dot.classList.add('bg-gray-300');
                }
            });
        }
        
        // Initialize carousel scroll listener
        document.addEventListener('DOMContentLoaded', function() {
            const carousel = document.getElementById('relatedProductsCarousel');
            if (carousel) {
                let scrollTimeout;
                carousel.addEventListener('scroll', function() {
                    clearTimeout(scrollTimeout);
                    scrollTimeout = setTimeout(() => {
                        const scrollLeft = carousel.scrollLeft;
                        const itemWidth = 288; // 280px + 8px gap
                        const newIndex = Math.round(scrollLeft / itemWidth);
                        updateCarouselDots(newIndex);
                    }, 100);
                });
            }
        });

    </script>

    <!-- Modal de Login -->
    <div id="loginModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50 hidden">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3 text-center">
                <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-pink-100">
                    <i class="fas fa-heart text-pink-600 text-xl"></i>
                </div>
                <h3 class="text-lg font-medium text-gray-900 mt-4">Iniciar Sesión Requerido</h3>
                <div class="mt-2 px-7 py-3">
                    <p class="text-sm text-gray-500">
                        Para agregar productos a tu lista de favoritos, necesitas iniciar sesión en tu cuenta.
                    </p>
                </div>
                <div class="items-center px-4 py-3">
                    <div class="flex space-x-3">
                        <button id="loginBtn" class="px-4 py-2 bg-blue-600 text-white text-base font-medium rounded-md w-full shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-300">
                            Iniciar Sesión
                        </button>
                        <button id="continueGuestBtn" class="px-4 py-2 bg-gray-300 text-gray-700 text-base font-medium rounded-md w-full shadow-sm hover:bg-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-300">
                            Continuar como Invitado
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

</body>
</html>
