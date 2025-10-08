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
$settingsModel = new Settings();

// Get contact settings for footer
$contactSettings = $settingsModel->getContactSettings();

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

// OBTENER CATEGORÍAS PARA LA SECCIÓN "EXPLORA POR CATEGORÍAS"
try {
    $categoriesForExplore = $categoryModel->getActiveCategories();
    
    // Agregar información adicional para cada categoría
    if (!empty($categoriesForExplore)) {
        foreach ($categoriesForExplore as &$cat) {
            // Contar productos por categoría
            try {
                $stmt = $pdo->prepare("SELECT COUNT(*) as product_count FROM products WHERE category_id = ? AND status = 'active'");
                $stmt->execute([$cat['id']]);
                $countResult = $stmt->fetch();
                $cat['product_count'] = $countResult['product_count'] ?? 0;
            } catch (Exception $e) {
                $cat['product_count'] = 0;
            }
            
            // Asignar imagen de categoría
            if (!empty($cat['image'])) {
                // Si la imagen ya incluye la ruta completa (uploads/categories/filename)
                if (strpos($cat['image'], 'uploads/') === 0) {
                    $cat['image_url'] = BASE_URL . '/' . $cat['image'];
                } else {
                    // Si solo es el nombre del archivo
                    $cat['image_url'] = BASE_URL . '/uploads/categories/' . $cat['image'];
                }
            } else {
                // Imágenes por defecto según el nombre de la categoría
                $categoryImages = [
                    'smartwatch' => '/public/images/categories/smartwatch.svg',
                    'tecnologia' => '/public/images/categories/tecnologia.svg',
                    'maquillaje-ojos' => '/public/images/categories/makeup-eyes.svg',
                    'maquillaje-labios' => '/public/images/categories/makeup-lips.svg',
                    'base-correctores' => '/public/images/categories/makeup-base.svg',
                    'cuidado-piel' => '/public/images/categories/skincare.svg',
                    'polvos-rubores' => '/public/images/categories/makeup-powder.svg',
                    'accesorios-herramientas' => '/public/images/categories/makeup-tools.svg',
                    'fragancias' => '/public/images/categories/perfumes.svg',
                    'cuidado-cabello' => '/public/images/categories/haircare.svg',
                    'audifonos' => '/public/images/categories/default.svg',
                    'smartphones' => '/public/images/categories/default.svg',
                    'laptops' => '/public/images/categories/default.svg',
                    'gaming' => '/public/images/categories/default.svg',
                    'accesorios' => '/public/images/categories/default.svg',
                    'wearables' => '/public/images/categories/default.svg'
                ];
                
                $categorySlug = strtolower($cat['slug'] ?? $cat['name']);
                $cat['image_url'] = BASE_URL . ($categoryImages[$categorySlug] ?? '/public/images/categories/default.svg');
            }
        }
    }
    
} catch (Exception $e) {
    error_log("Error loading categories: " . $e->getMessage());
    $categoriesForExplore = [];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($product['name']); ?> - OdiseaStore</title>
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=Playfair+Display:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    
    <!-- Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- AOS Animation -->
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    
    <!-- Tailwind CSS config -->
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
                        'pulse-soft': 'pulseSoft 3s ease-in-out infinite',
                        'fade-in-down': 'fadeInDown 0.8s ease-out',
                        'zoom-in': 'zoomIn 0.6s ease-out',
                        'slide-in-top': 'slideInTop 0.7s ease-out'
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
                        fadeInDown: {
                            '0%': { opacity: '0', transform: 'translateY(-30px)' },
                            '100%': { opacity: '1', transform: 'translateY(0)' }
                        },
                        scaleIn: {
                            '0%': { opacity: '0', transform: 'scale(0.9)' },
                            '100%': { opacity: '1', transform: 'scale(1)' }
                        },
                        zoomIn: {
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
                        slideInTop: {
                            '0%': { opacity: '0', transform: 'translateY(-50px)' },
                            '100%': { opacity: '1', transform: 'translateY(0)' }
                        },
                        bounceGentle: {
                            '0%, 100%': { transform: 'translateY(0)' },
                            '50%': { transform: 'translateY(-10px)' }
                        },
                        pulseSoft: {
                            '0%, 100%': { opacity: '1' },
                            '50%': { opacity: '0.8' }
                        }
                    }
                }
            }
        };
    </script>
    
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
        
        /* Custom scrollbar for description container */
        .custom-scrollbar::-webkit-scrollbar {
            width: 6px;
        }
        .custom-scrollbar::-webkit-scrollbar-track {
            background: #f3f4f6;
            border-radius: 3px;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: linear-gradient(135deg, #3b82f6, #8b5cf6);
            border-radius: 3px;
            transition: all 0.3s ease;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover {
            background: linear-gradient(135deg, #2563eb, #7c3aed);
        }
        /* Firefox */
        .custom-scrollbar {
            scrollbar-width: thin;
            scrollbar-color: #3b82f6 #f3f4f6;
        }
        
        /* Ensure related products section visibility */
        .related-products-section {
            position: relative;
            z-index: 10;
            display: block !important;
            visibility: visible !important;
            opacity: 1 !important;
        }
        
        .related-products-title {
            color: #111827 !important;
            font-weight: 700 !important;
            display: block !important;
            line-height: 1.2 !important;
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
            background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .btn-primary:hover {
            background: linear-gradient(135deg, #1d4ed8 0%, #1e40af 100%);
            transform: translateY(-1px);
            box-shadow: 0 8px 25px rgba(59, 130, 246, 0.3);
        }
        
        .btn-secondary {
            background: linear-gradient(135deg, #ec4899 0%, #be185d 100%);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .btn-secondary:hover {
            background: linear-gradient(135deg, #be185d 0%, #9d174d 100%);
            transform: translateY(-1px);
            box-shadow: 0 8px 25px rgba(236, 72, 153, 0.3);
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
        
        .scrollbar-hide {
            -ms-overflow-style: none;
            scrollbar-width: none;
        }
        .scrollbar-hide::-webkit-scrollbar {
            display: none;
        }
        
        .categories-carousel {
            scroll-snap-type: x mandatory;
            scroll-behavior: smooth;
        }
        
        .category-item {
            scroll-snap-align: start;
            flex: 0 0 120px;
        }
        
        .gradient-text {
            background: linear-gradient(135deg, #3b82f6 0%, #8b5cf6 100%);
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        .mobile-drawer {
            transform: translateX(-100%);
            transition: transform 0.3s ease-in-out;
        }
        
        .mobile-drawer.open {
            transform: translateX(0);
        }
        
        .touch-target {
            min-width: 44px;
            min-height: 44px;
        }
        
        /* Animaciones para logos */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .animate-glow {
            animation: glow 2s ease-in-out infinite alternate;
        }
        
        @keyframes glow {
            from {
                box-shadow: 0 0 10px rgba(59, 130, 246, 0.3);
            }
            to {
                box-shadow: 0 0 20px rgba(59, 130, 246, 0.6);
            }
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
    </style>
</head>
<body class="font-sans bg-white overflow-x-hidden">
    <!-- Desktop Header -->
    <header class="hidden md:block fixed top-0 left-0 right-0 z-50 transition-all duration-500" id="desktop-header">
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
    <header class="md:hidden fixed top-0 left-0 right-0 z-50 bg-white/95 backdrop-blur-md shadow-lg" id="mobile-header">
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
                    <span class="ml-1 text-xs text-gray-500 font-light opacity-0" id="mobile-logo-makeup">STORE</span>
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
                            <div class="text-sm text-gray-600">Bienvenido</div>
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
                            $mobileCategories = [
                                ['name' => 'Tecnología', 'icon' => '💻', 'url' => 'categoria.php?categoria=tecnologia'],
                                ['name' => 'Hogar', 'icon' => '🏠', 'url' => 'categoria.php?categoria=hogar'],
                                ['name' => 'Deportes', 'icon' => '⚽', 'url' => 'categoria.php?categoria=deportes'],
                                ['name' => 'Moda', 'icon' => '👕', 'url' => 'categoria.php?categoria=moda'],
                                ['name' => 'Belleza', 'icon' => '💄', 'url' => 'categoria.php?categoria=belleza'],
                                ['name' => 'Salud', 'icon' => '🏥', 'url' => 'categoria.php?categoria=salud']
                            ];

                            foreach ($mobileCategories as $category):
                            ?>
                            <a href="<?php echo $category['url']; ?>" class="flex items-center p-3 rounded-xl hover:bg-primary-50 transition-colors duration-300 group">
                                <span class="text-2xl mr-4"><?php echo $category['icon']; ?></span>
                                <span class="font-medium text-gray-700 group-hover:text-primary-600"><?php echo $category['name']; ?></span>
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

    <!-- Main Content with proper spacing for fixed header -->

    <!-- Improved breadcrumb with better visual hierarchy -->
    <nav class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 pt-32 md:pt-40">
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
                        <button onclick="previousImage()" class="absolute left-4 top-1/2 transform -translate-y-1/2 glass-effect rounded-full p-3 shadow-lg transition-all hover:scale-110">
                            <i class="fas fa-chevron-left text-gray-700"></i>
                        </button>
                        <button onclick="nextImage()" class="absolute right-4 top-1/2 transform -translate-y-1/2 glass-effect rounded-full p-3 shadow-lg transition-all hover:scale-110">
                            <i class="fas fa-chevron-right text-gray-700"></i>
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
                    
                    <div class="mt-3 flex flex-wrap gap-3 items-center">
                        <?php if ($brand): ?>
                        <div class="flex items-center">
                            <span class="text-sm font-medium text-secondary-600 mr-2">Marca:</span>
                            <a href="marcas.php" class="text-lg font-semibold text-primary-600 hover:text-primary-700 transition-colors">
                                <?php echo htmlspecialchars($brand['name']); ?>
                            </a>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($category): ?>
                        <div class="flex items-center">
                            <span class="text-sm font-medium text-secondary-600 mr-2">Categoría:</span>
                            <a href="categoria.php?categoria=<?php echo urlencode($category['slug'] ?? $category['name']); ?>" 
                               class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-gradient-to-r from-accent-100 to-secondary-100 text-accent-800 hover:from-accent-200 hover:to-secondary-200 transition-all duration-300">
                                <i class="fas fa-tag mr-1 text-xs"></i>
                                <?php echo htmlspecialchars($category['name']); ?>
                            </a>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>                    <!-- Enhanced rating display -->
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

                    <!-- Enhanced description section with luxury styling -->
                    <div class="relative">
                        <div class="bg-gradient-to-br from-luxury-pearl/20 via-white to-luxury-champagne/20 rounded-3xl p-8 border border-white/50 backdrop-blur-sm">
                            <div class="flex items-center mb-6">
                                <div class="w-12 h-12 bg-gradient-to-r from-primary-500 to-secondary-500 rounded-2xl flex items-center justify-center mr-4">
                                    <i class="fas fa-info-circle text-white text-lg"></i>
                                </div>
                                <div>
                                    <h3 class="text-xl font-serif font-bold text-gray-900 mb-1">Descripción del Producto</h3>
                                    <p class="text-sm text-secondary-600 font-medium">Conoce todos los detalles</p>
                                </div>
                            </div>
                            
                            <div class="prose prose-lg prose-gray max-w-none">
                                <!-- Contenedor con scroll para descripciones largas -->
                                <div class="max-h-64 overflow-y-auto text-gray-700 leading-8 text-lg font-light tracking-wide pr-2 custom-scrollbar">
                                    <?php 
                                    $description = htmlspecialchars($product['description']);
                                    // Dividir por párrafos para mejor presentación
                                    $paragraphs = explode("\n\n", $description);
                                    foreach ($paragraphs as $index => $paragraph): 
                                        if (trim($paragraph)): ?>
                                    <p class="mb-6 <?php echo $index === 0 ? 'text-xl font-medium text-gray-800 first-letter:text-4xl first-letter:font-bold first-letter:text-primary-600 first-letter:float-left first-letter:mr-2 first-letter:leading-none' : ''; ?>">
                                        <?php echo nl2br(trim($paragraph)); ?>
                                    </p>
                                    <?php endif; 
                                    endforeach; ?>
                                </div>
                            </div>
                            
                            <!-- Decorative elements -->
                            <div class="absolute top-4 right-4 opacity-20">
                                <div class="w-20 h-20 bg-gradient-to-br from-primary-200 to-secondary-200 rounded-full blur-xl"></div>
                            </div>
                            <div class="absolute bottom-4 left-4 opacity-10">
                                <div class="w-16 h-16 bg-gradient-to-tr from-luxury-gold to-luxury-champagne rounded-full blur-lg"></div>
                            </div>
                        </div>
                        
                        <!-- Side accent decoration -->
                        <div class="absolute -left-2 top-1/2 transform -translate-y-1/2 w-1 h-24 bg-gradient-to-b from-primary-500 to-secondary-500 rounded-full"></div>
                    </div>

                    <?php if (!empty($product['specifications'])): ?>
                    <div class="relative mt-8">
                        <div class="bg-gradient-to-br from-secondary-50/80 via-white to-accent-50/80 rounded-3xl p-8 border border-white/50 backdrop-blur-sm">
                            <div class="flex items-center mb-6">
                                <div class="w-12 h-12 bg-gradient-to-r from-secondary-500 to-accent-500 rounded-2xl flex items-center justify-center mr-4">
                                    <i class="fas fa-list-ul text-white text-lg"></i>
                                </div>
                                <div>
                                    <h3 class="text-xl font-serif font-bold text-gray-900 mb-1">Especificaciones Técnicas</h3>
                                    <p class="text-sm text-secondary-600 font-medium">Detalles del producto</p>
                                </div>
                            </div>
                            
                            <div class="text-gray-700 leading-8 text-lg font-light tracking-wide">
                                <?php 
                                $specifications = htmlspecialchars($product['specifications']);
                                // Dividir especificaciones en líneas para mejor presentación
                                $specs = explode("\n", $specifications);
                                foreach ($specs as $spec): 
                                    if (trim($spec)): ?>
                                <div class="flex items-start mb-3 group">
                                    <div class="w-2 h-2 bg-gradient-to-r from-secondary-500 to-accent-500 rounded-full mt-3 mr-4 group-hover:scale-125 transition-transform"></div>
                                    <p class="flex-1"><?php echo trim($spec); ?></p>
                                </div>
                                <?php endif; 
                                endforeach; ?>
                            </div>
                            
                            <!-- Decorative elements -->
                            <div class="absolute top-4 right-4 opacity-20">
                                <div class="w-16 h-16 bg-gradient-to-br from-secondary-200 to-accent-200 rounded-full blur-xl"></div>
                            </div>
                        </div>
                        
                        <!-- Side accent decoration -->
                        <div class="absolute -right-2 top-1/2 transform -translate-y-1/2 w-1 h-20 bg-gradient-to-b from-secondary-500 to-accent-500 rounded-full"></div>
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
                                <button type="button" onclick="addToCart(<?php echo $product['id']; ?>, event); return false;" class="w-full btn-primary text-white rounded-xl py-4 px-8 flex items-center justify-center text-base font-semibold focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                                    <i class="fas fa-shopping-cart mr-3"></i>
                                    Añadir al carrito
                                </button>
                            </div>
                        </div>
                        
                        <div class="grid grid-cols-2 gap-3">
                            <button type="button" onclick="addToWishlist(<?php echo $product['id']; ?>)" class="btn-secondary text-white rounded-xl py-3 px-6 flex items-center justify-center text-sm font-semibold focus:outline-none focus:ring-2 focus:ring-pink-500 focus:ring-offset-2">
                                <i class="fas fa-heart mr-2"></i>
                                Favoritos
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

    <!-- Divisor visual sutil -->
    <div class="w-full h-px bg-gradient-to-r from-transparent via-gray-300 to-transparent my-8"></div>

    <!-- Enhanced related products section with better design -->
    <?php if (!empty($relatedProducts)): ?>
    <section class="bg-gray-50 py-20 border-t border-gray-200 relative z-10 related-products-section" style="background: #f9fafb !important; min-height: 400px !important; display: block !important;">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-12" data-aos="fade-up">
                <div class="inline-block mb-6">
                    <span class="text-sm font-bold tracking-widest uppercase text-white bg-blue-600 px-6 py-3 rounded-full shadow-lg">
                        🛍️ Productos Relacionados
                    </span>
                </div>
                <h2 class="text-4xl md:text-5xl font-bold mb-6 text-gray-900 related-products-title" style="color: #111827 !important; font-size: 3rem !important; margin-bottom: 1.5rem !important;">
                    Te Puede Interesar
                </h2>
                <p class="text-xl text-gray-700 max-w-2xl mx-auto leading-relaxed font-medium">
                    Descubre otros productos de la categoría 
                    <span class="font-bold text-blue-600"><?php echo htmlspecialchars($category['name'] ?? 'similar'); ?></span> 
                    que también podrían gustarte
                </p>
            </div>

            <!-- Mobile Carousel -->
            <div class="block md:hidden relative">
                <div class="mobile-carousel flex space-x-6 pb-6" id="mobileCarousel">
                    <?php 
                    $mobileProducts = array_slice($relatedProducts, 0, 5);
                    foreach ($mobileProducts as $relatedProduct): 
                    ?>
                    <div class="mobile-carousel-item w-72 group animate-fade-in-up">
                        <div class="relative overflow-hidden rounded-3xl bg-white/80 backdrop-blur-sm border border-white/50 hover-lift transition-all duration-500 shimmer-effect">
                            <!-- Product Image -->
                            <div class="relative overflow-hidden rounded-t-3xl">
                                <a href="details.php?id=<?php echo $relatedProduct['id']; ?>">
                                    <img src="<?php echo htmlspecialchars($relatedProduct['main_image_url']); ?>" 
                                         alt="<?php echo htmlspecialchars($relatedProduct['name']); ?>"
                                         class="w-full h-64 object-cover group-hover:scale-110 transition-transform duration-700"
                                         loading="lazy"
                                         onerror="this.src='<?php echo BASE_URL; ?>/public/images/product-placeholder-1.svg'">
                                </a>
                                
                                <?php if ($relatedProduct['discount_percentage'] > 0): ?>
                                <div class="absolute top-4 left-4 z-10">
                                    <span class="bg-gradient-to-r from-red-500 to-pink-500 text-white px-3 py-1 rounded-full text-sm font-semibold shadow-lg">
                                        -<?php echo $relatedProduct['discount_percentage']; ?>%
                                    </span>
                                </div>
                                <?php endif; ?>

                                <!-- Quick Actions -->
                                <div class="absolute top-4 right-4 space-y-2 opacity-0 group-hover:opacity-100 transition-all duration-300 z-20">
                                    <button class="quick-action-btn w-10 h-10 bg-white/90 backdrop-blur-sm rounded-full flex items-center justify-center shadow-lg hover:bg-primary-500 hover:text-white transition-all duration-300 transform hover:scale-110 relative z-30"
                                            onclick="toggleWishlist(<?php echo $relatedProduct['id']; ?>)">
                                        <i class="far fa-heart text-sm"></i>
                                    </button>
                                    <button class="quick-action-btn w-10 h-10 bg-white/90 backdrop-blur-sm rounded-full flex items-center justify-center shadow-lg hover:bg-primary-500 hover:text-white transition-all duration-300 transform hover:scale-110 relative z-30"
                                            onclick="quickView(<?php echo $relatedProduct['id']; ?>)">
                                        <i class="fas fa-eye text-sm"></i>
                                    </button>
                                </div>

                                <!-- Overlay Gradient -->
                                <div class="absolute inset-0 bg-gradient-to-t from-black/20 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300 z-0 pointer-events-none"></div>
                            </div>

                            <!-- Product Info -->
                            <div class="p-4">
                                <?php if (!empty($relatedProduct['brand_name'])): ?>
                                <p class="text-xs font-medium mb-2 tracking-wide uppercase text-secondary-600">
                                    <?php echo htmlspecialchars($relatedProduct['brand_name']); ?>
                                </p>
                                <?php endif; ?>

                                <h3 class="font-semibold text-sm mb-3 line-clamp-2 text-gray-800 group-hover:text-primary-600 transition-colors">
                                    <a href="details.php?id=<?php echo $relatedProduct['id']; ?>">
                                        <?php echo htmlspecialchars($relatedProduct['name']); ?>
                                    </a>
                                </h3>

                                <!-- Price -->
                                <div class="flex items-center justify-between mb-4">
                                    <div class="flex items-center space-x-2">
                                        <span class="text-lg font-bold text-primary-600">
                                            $<?php echo number_format($relatedProduct['price'], 0, ',', '.'); ?>
                                        </span>
                                        <?php if (!empty($relatedProduct['compare_price']) && $relatedProduct['compare_price'] > $relatedProduct['price']): ?>
                                        <span class="text-sm line-through text-gray-400">
                                            $<?php echo number_format($relatedProduct['compare_price'], 0, ',', '.'); ?>
                                        </span>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <!-- Add to Cart Button -->
                                <?php if ($relatedProduct['inventory_quantity'] > 0): ?>
                                <button class="w-full py-3 rounded-2xl font-semibold text-white transition-all duration-300 transform group-hover:scale-105 bg-gradient-to-r from-primary-500 to-secondary-500 hover:from-secondary-500 hover:to-primary-500 shadow-lg hover:shadow-xl text-sm"
                                        onclick="addToCart(<?php echo $relatedProduct['id']; ?>)">
                                    <i class="fas fa-shopping-bag mr-2"></i>
                                    Agregar al Carrito
                                </button>
                                <?php else: ?>
                                <button disabled class="w-full py-3 rounded-2xl font-semibold text-gray-500 bg-gray-300 cursor-not-allowed text-sm">
                                    Agotado
                                </button>
                                <?php endif; ?>
                            </div>
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
                <div class="group animate-fade-in-up">
                    <div class="relative overflow-hidden rounded-3xl bg-white/80 backdrop-blur-sm border border-white/50 hover-lift transition-all duration-500 shimmer-effect">
                        <!-- Product Image -->
                        <div class="relative overflow-hidden rounded-t-3xl">
                            <a href="details.php?id=<?php echo $relatedProduct['id']; ?>">
                                <img src="<?php echo htmlspecialchars($relatedProduct['main_image_url']); ?>" 
                                     alt="<?php echo htmlspecialchars($relatedProduct['name']); ?>"
                                     class="w-full h-80 object-cover group-hover:scale-110 transition-transform duration-700"
                                     loading="lazy"
                                     onerror="this.src='<?php echo BASE_URL; ?>/public/images/product-placeholder-1.svg'">
                            </a>
                            
                            <?php if ($relatedProduct['discount_percentage'] > 0): ?>
                            <div class="absolute top-4 left-4 z-10">
                                <span class="bg-gradient-to-r from-red-500 to-pink-500 text-white px-3 py-1 rounded-full text-sm font-semibold shadow-lg">
                                    -<?php echo $relatedProduct['discount_percentage']; ?>%
                                </span>
                            </div>
                            <?php endif; ?>

                            <!-- Quick Actions -->
                            <div class="absolute top-4 right-4 space-y-2 opacity-0 group-hover:opacity-100 transition-all duration-300 z-20">
                                <button class="quick-action-btn w-12 h-12 bg-white/90 backdrop-blur-sm rounded-full flex items-center justify-center shadow-lg hover:bg-primary-500 hover:text-white transition-all duration-300 transform hover:scale-110 relative z-30"
                                        onclick="toggleWishlist(<?php echo $relatedProduct['id']; ?>)">
                                    <i class="far fa-heart"></i>
                                </button>
                                <button class="quick-action-btn w-12 h-12 bg-white/90 backdrop-blur-sm rounded-full flex items-center justify-center shadow-lg hover:bg-primary-500 hover:text-white transition-all duration-300 transform hover:scale-110 relative z-30"
                                        onclick="quickView(<?php echo $relatedProduct['id']; ?>)">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>

                            <!-- Overlay Gradient -->
                            <div class="absolute inset-0 bg-gradient-to-t from-black/20 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300 z-0 pointer-events-none"></div>
                        </div>

                        <!-- Product Info -->
                        <div class="p-6">
                            <?php if (!empty($relatedProduct['brand_name'])): ?>
                            <p class="text-sm font-medium mb-2 tracking-wide uppercase text-secondary-600">
                                <?php echo htmlspecialchars($relatedProduct['brand_name']); ?>
                            </p>
                            <?php endif; ?>

                            <h3 class="font-semibold text-lg mb-3 line-clamp-2 text-gray-800 group-hover:text-primary-600 transition-colors">
                                <a href="details.php?id=<?php echo $relatedProduct['id']; ?>">
                                    <?php echo htmlspecialchars($relatedProduct['name']); ?>
                                </a>
                            </h3>

                            <!-- Rating -->
                            <div class="flex items-center mb-4">
                                <div class="flex text-yellow-400 text-sm">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <i class="fas fa-star"></i>
                                    <?php endfor; ?>
                                </div>
                                <span class="text-sm text-gray-500 ml-2">
                                    Sin reseñas
                                </span>
                            </div>

                            <!-- Price -->
                            <div class="flex items-center justify-between mb-6">
                                <div class="flex items-center space-x-2">
                                    <span class="text-2xl font-bold text-primary-600">
                                        $<?php echo number_format($relatedProduct['price'], 0, ',', '.'); ?>
                                    </span>
                                    <?php if (!empty($relatedProduct['compare_price']) && $relatedProduct['compare_price'] > $relatedProduct['price']): ?>
                                    <span class="text-lg line-through text-gray-400">
                                        $<?php echo number_format($relatedProduct['compare_price'], 0, ',', '.'); ?>
                                    </span>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Add to Cart Button -->
                            <?php if ($relatedProduct['inventory_quantity'] > 0): ?>
                            <button class="w-full py-4 rounded-2xl font-semibold text-white transition-all duration-300 transform group-hover:scale-105 bg-gradient-to-r from-primary-500 to-secondary-500 hover:from-secondary-500 hover:to-primary-500 shadow-lg hover:shadow-xl"
                                    onclick="addToCart(<?php echo $relatedProduct['id']; ?>)">
                                <i class="fas fa-shopping-bag mr-2"></i>
                                Agregar al Carrito
                            </button>
                            <?php else: ?>
                            <button disabled class="w-full py-4 rounded-2xl font-semibold text-gray-500 bg-gray-300 cursor-not-allowed">
                                Agotado
                            </button>
                            <?php endif; ?>
                        </div>
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

    <!-- Carrusel Horizontal de Categorías -->
    <?php if (!empty($categoriesForExplore)): ?>
    <section class="py-16 bg-gradient-to-br from-gray-50 to-white">
        <div class="max-w-7xl mx-auto px-4">
            <!-- Título de la Sección -->
            <div class="text-center mb-12">
                <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">
                    <i class="fas fa-compass text-pink-500 mr-3"></i>
                    Explora por Categorías
                </h2>
                <p class="text-lg text-gray-600">
                    Descubre nuestras categorías especializadas
                </p>
            </div>

            <!-- Contenedor del Carrusel -->
            <div class="relative px-12">
                <!-- Botones de Navegación -->
                <button id="categoryScrollLeft" 
                        class="absolute left-0 top-1/2 transform -translate-y-1/2 z-20 bg-white shadow-lg rounded-full w-12 h-12 flex items-center justify-center text-gray-600 hover:text-pink-500 hover:shadow-xl transition-all duration-300 hover:scale-110">
                    <i class="fas fa-chevron-left"></i>
                </button>
                
                <button id="categoryScrollRight" 
                        class="absolute right-0 top-1/2 transform -translate-y-1/2 z-20 bg-white shadow-lg rounded-full w-12 h-12 flex items-center justify-center text-gray-600 hover:text-pink-500 hover:shadow-xl transition-all duration-300 hover:scale-110">
                    <i class="fas fa-chevron-right"></i>
                </button>

                <!-- Carrusel -->
                <div id="categoriesCarousel" class="overflow-x-auto scrollbar-hide scroll-smooth">
                    <div class="flex space-x-6 px-4 pb-4">
                        <?php foreach ($categoriesForExplore as $category): ?>
                        <div class="flex-shrink-0 text-center cursor-pointer group category-item" 
                             onclick="window.location.href='categoria.php?categoria=<?php echo urlencode($category['slug'] ?? $category['name']); ?>'"
                             style="min-width: 170px;">
                            
                            <!-- Imagen de Categoría -->
                            <div class="relative mb-4 mx-auto" style="width: 100px;">
                                <div class="w-24 h-24 mx-auto rounded-full overflow-hidden bg-gradient-to-br from-pink-400 to-purple-500 p-1 category-circle">
                                    <div class="w-full h-full rounded-full overflow-hidden bg-white flex items-center justify-center">
                                        <?php if (!empty($category['image_url'])): ?>
                                        <!-- Usar image_url calculada en el PHP -->
                                        <img src="<?php echo htmlspecialchars($category['image_url']); ?>" 
                                             alt="<?php echo htmlspecialchars($category['name']); ?>"
                                             class="w-16 h-16 object-contain"
                                             onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                        <?php endif; ?>
                                        
                                        <!-- Fallback icon si falla la imagen -->
                                        <div class="w-16 h-16 flex items-center justify-center" <?php echo !empty($category['image_url']) ? 'style="display: none;"' : ''; ?>>
                                            <?php 
                                            $categorySlug = strtolower($category['slug'] ?? $category['name']);
                                            $iconMap = [
                                                'maquillaje-ojos' => 'eye',
                                                'maquillaje-labios' => 'kiss-wink-heart',
                                                'base-correctores' => 'palette',
                                                'cuidado-piel' => 'spa',
                                                'polvos-rubores' => 'magic',
                                                'accesorios-herramientas' => 'brush',
                                                'fragancias' => 'spray-can',
                                                'cuidado-cabello' => 'cut'
                                            ];
                                            $icon = $iconMap[$categorySlug] ?? 'tag';
                                            ?>
                                            <i class="fas fa-<?php echo $icon; ?> text-2xl text-pink-500"></i>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Badge de productos - Mejorado -->
                                <div class="absolute top-0 right-0 bg-red-500 text-white text-xs font-bold rounded-full min-w-[24px] h-6 flex items-center justify-center px-1 shadow-lg transform translate-x-2 -translate-y-1">
                                    <?php echo min($category['product_count'] ?? 0, 99); ?>
                                </div>
                            </div>
                            
                            <!-- Información -->
                            <div class="px-2">
                                <h3 class="font-semibold text-gray-800 group-hover:text-pink-600 transition-colors text-sm mb-1 leading-tight">
                                    <?php echo htmlspecialchars($category['name']); ?>
                                </h3>
                                <p class="text-xs text-gray-500">
                                    <?php echo $category['product_count'] ?? 0; ?> productos
                                </p>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        
                        <!-- Botón Ver Todas -->
                        <div class="flex-shrink-0 text-center cursor-pointer group" 
                             onclick="window.location.href='catalogo.php'"
                             style="min-width: 170px;">
                            <div class="relative mb-4 mx-auto" style="width: 100px;">
                                <div class="w-24 h-24 mx-auto rounded-full bg-gray-200 group-hover:bg-gradient-to-br group-hover:from-pink-400 group-hover:to-purple-500 flex items-center justify-center group-hover:scale-110 transition-all duration-300 shadow-lg">
                                    <i class="fas fa-plus text-2xl text-gray-400 group-hover:text-white"></i>
                                </div>
                            </div>
                            <div class="px-2">
                                <h3 class="font-semibold text-gray-800 group-hover:text-pink-600 transition-colors text-sm mb-1">
                                    Ver Todas
                                </h3>
                                <p class="text-xs text-gray-500">
                                    Catálogo completo
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- JavaScript para el Carrusel -->
    <style>
    .scrollbar-hide {
        -ms-overflow-style: none;
        scrollbar-width: none;
    }
    .scrollbar-hide::-webkit-scrollbar {
        display: none;
    }
    
    #categoriesCarousel {
        scroll-behavior: smooth;
    }
    
    .category-item {
        transition: all 0.3s ease;
    }
    
    .category-item:hover {
        transform: translateY(-5px);
    }
    
    .category-circle {
        box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }
    
    .category-circle:hover {
        box-shadow: 0 20px 40px rgba(0,0,0,0.15);
        transform: scale(1.05);
    }
    </style>
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const carousel = document.getElementById('categoriesCarousel');
        const leftBtn = document.getElementById('categoryScrollLeft');
        const rightBtn = document.getElementById('categoryScrollRight');
        
        // Función para scroll
        function scrollCarousel(direction) {
            const scrollAmount = 250; // Cantidad de scroll
            
            if (direction === 'left') {
                carousel.scrollBy({ left: -scrollAmount, behavior: 'smooth' });
            } else {
                carousel.scrollBy({ left: scrollAmount, behavior: 'smooth' });
            }
        }
        
        // Event listeners para los botones
        leftBtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            scrollCarousel('left');
        });
        
        rightBtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            scrollCarousel('right');
        });
        
        // Verificar si los botones son necesarios
        function updateButtonVisibility() {
            const isScrollable = carousel.scrollWidth > carousel.clientWidth;
            
            if (isScrollable) {
                leftBtn.style.display = 'flex';
                rightBtn.style.display = 'flex';
                
                // Actualizar estado de los botones según posición
                const isAtStart = carousel.scrollLeft <= 10;
                const isAtEnd = carousel.scrollLeft >= carousel.scrollWidth - carousel.clientWidth - 10;
                
                leftBtn.style.opacity = isAtStart ? '0.5' : '1';
                rightBtn.style.opacity = isAtEnd ? '0.5' : '1';
                leftBtn.style.pointerEvents = isAtStart ? 'none' : 'auto';
                rightBtn.style.pointerEvents = isAtEnd ? 'none' : 'auto';
            } else {
                leftBtn.style.display = 'none';
                rightBtn.style.display = 'none';
            }
        }
        
        // Actualizar visibilidad inicial
        updateButtonVisibility();
        
        // Actualizar en scroll
        carousel.addEventListener('scroll', updateButtonVisibility);
        
        // Actualizar en resize
        window.addEventListener('resize', updateButtonVisibility);
        
        // Soporte para scroll táctil mejorado
        let isDown = false;
        let startX;
        let scrollStart;

        carousel.addEventListener('mousedown', (e) => {
            isDown = true;
            startX = e.pageX - carousel.offsetLeft;
            scrollStart = carousel.scrollLeft;
            carousel.style.cursor = 'grabbing';
        });

        carousel.addEventListener('mouseleave', () => {
            isDown = false;
            carousel.style.cursor = 'grab';
        });

        carousel.addEventListener('mouseup', () => {
            isDown = false;
            carousel.style.cursor = 'grab';
        });

        carousel.addEventListener('mousemove', (e) => {
            if (!isDown) return;
            e.preventDefault();
            const x = e.pageX - carousel.offsetLeft;
            const walk = (x - startX) * 2;
            carousel.scrollLeft = scrollStart - walk;
        });
    });
    </script>
    <?php endif; ?>

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
        
        // Header JavaScript functionality
        function initializeHeader() {
            // Logo animations
            setTimeout(() => {
                const desktopLogo = document.getElementById('desktop-logo-odisea');
                const desktopSubtext = document.getElementById('desktop-logo-makeup');
                const mobileLogo = document.getElementById('mobile-logo-odisea');
                const mobileSubtext = document.getElementById('mobile-logo-makeup');
                
                if (desktopLogo) {
                    desktopLogo.style.animation = 'fadeInUp 0.8s ease-out forwards';
                    desktopLogo.style.opacity = '1';
                }
                
                if (desktopSubtext) {
                    setTimeout(() => {
                        desktopSubtext.style.animation = 'fadeInUp 0.8s ease-out forwards';
                        desktopSubtext.style.opacity = '1';
                    }, 200);
                }
                
                if (mobileLogo) {
                    mobileLogo.style.animation = 'fadeInUp 0.8s ease-out forwards';
                    mobileLogo.style.opacity = '1';
                }
                
                if (mobileSubtext) {
                    setTimeout(() => {
                        mobileSubtext.style.animation = 'fadeInUp 0.8s ease-out forwards';
                        mobileSubtext.style.opacity = '1';
                    }, 200);
                }
            }, 100);
            
            // Mobile menu functionality
            const mobileMenuBtn = document.getElementById('mobile-menu-btn');
            const mobileMenuOverlay = document.getElementById('mobile-menu-overlay');
            const mobileMenuDrawer = document.getElementById('mobile-menu-drawer');
            const mobileMenuClose = document.getElementById('mobile-menu-close');
            const mobileMenuBackdrop = document.getElementById('mobile-menu-backdrop');
            
            if (mobileMenuBtn && mobileMenuOverlay && mobileMenuDrawer) {
                mobileMenuBtn.addEventListener('click', () => {
                    mobileMenuOverlay.classList.remove('hidden');
                    setTimeout(() => {
                        mobileMenuDrawer.classList.add('open');
                    }, 10);
                });
                
                const closeMobileMenu = () => {
                    mobileMenuDrawer.classList.remove('open');
                    setTimeout(() => {
                        mobileMenuOverlay.classList.add('hidden');
                    }, 300);
                };
                
                if (mobileMenuClose) {
                    mobileMenuClose.addEventListener('click', closeMobileMenu);
                }
                
                if (mobileMenuBackdrop) {
                    mobileMenuBackdrop.addEventListener('click', closeMobileMenu);
                }
            }
            
            // Mobile search functionality
            const mobileSearchBtn = document.getElementById('mobile-search-btn');
            const mobileSearchBar = document.getElementById('mobile-search-bar');
            
            if (mobileSearchBtn && mobileSearchBar) {
                mobileSearchBtn.addEventListener('click', () => {
                    mobileSearchBar.classList.toggle('hidden');
                    if (!mobileSearchBar.classList.contains('hidden')) {
                        const searchInput = document.getElementById('mobile-search-input');
                        if (searchInput) {
                            searchInput.focus();
                        }
                    }
                });
            }
        }
        
        // Event listeners para el modal - Verificar que existan los elementos
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize header functionality
            initializeHeader();
            
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
        
        // Categories Carousel functionality - Mejorado
        function scrollCategoriesCarousel(direction) {
            const carousel = document.getElementById('categoriesCarousel');
            if (!carousel) return;
            
            const itemWidth = 176; // 140px min-width + 36px spacing
            const visibleItems = Math.floor(carousel.clientWidth / itemWidth);
            const scrollAmount = direction === 'left' ? -(itemWidth * visibleItems) : (itemWidth * visibleItems);
            
            carousel.scrollBy({
                left: scrollAmount,
                behavior: 'smooth'
            });
        }

        // Initialize categories carousel
        document.addEventListener('DOMContentLoaded', function() {
            const carousel = document.getElementById('categoriesCarousel');
            if (carousel) {
                // Smooth scroll for touch devices
                let isScrolling = false;
                
                carousel.addEventListener('scroll', function() {
                    if (!isScrolling) {
                        isScrolling = true;
                        setTimeout(() => {
                            isScrolling = false;
                        }, 100);
                    }
                });
                
                // Add scroll indicators for mobile
                const indicators = document.getElementById('categoriesIndicators');
                if (indicators && window.innerWidth < 768) {
                    const items = carousel.querySelectorAll('[data-aos="zoom-in"]');
                    const itemsPerView = Math.floor(carousel.clientWidth / 176);
                    const totalPages = Math.ceil(items.length / itemsPerView);
                    
                    for (let i = 0; i < totalPages; i++) {
                        const dot = document.createElement('div');
                        dot.className = `w-2 h-2 rounded-full transition-colors duration-300 ${i === 0 ? 'bg-primary-600' : 'bg-gray-300'}`;
                        indicators.appendChild(dot);
                    }
                }
            }
        });

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

    <!-- Debug Info (solo para desarrollo) -->
    <?php if (isset($_GET['debug'])): ?>
    <div style="position: fixed; bottom: 10px; right: 10px; background: rgba(0,0,0,0.8); color: white; padding: 10px; border-radius: 5px; font-size: 12px; max-width: 300px; z-index: 9999;">
        <strong>Debug Info:</strong><br>
        BASE_URL: <?php echo BASE_URL; ?><br>
        Server: <?php echo $_SERVER['HTTP_HOST'] ?? 'N/A'; ?><br>
        <?php if (!empty($categoriesForExplore)): ?>
        <strong>Categorías:</strong><br>
        <?php foreach ($categoriesForExplore as $cat): ?>
            <?php echo $cat['name']; ?>: <?php echo $cat['image_url']; ?><br>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>
    <?php endif; ?>

</body>
</html>
