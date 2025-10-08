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
$product = $productModel->getById($product_id);

if (!$product) {
    header('Location: 404.php');
    exit;
}

// Get additional product information
$brand = null;
if ($product['brand_id']) {
    $brand = $brandModel->getById($product['brand_id']);
}

$category = null;
if ($product['category_id']) {
    $category = $categoryModel->getById($product['category_id']);
}

// Get product reviews and ratings
$reviews = $reviewModel->getReviewsWithInteractions($product_id);
$ratingData = $reviewModel->getAverageRating($product_id);
$averageRating = $ratingData['average'] ?? 0;
$reviewCount = $ratingData['count'] ?? 0;
$ratingDistribution = $reviewModel->getRatingDistribution($product_id);

// Process product images
$productImages = [];
if (!empty($product['main_image'])) {
    $imagePath = str_replace('uploads/products/', '', $product['main_image']);
    $imagePath = str_replace('assets/images/products/', '', $imagePath);
    $productImages[] = BASE_URL . '/uploads/products/' . $imagePath;
}

// Add additional images if available
if (!empty($product['additional_images'])) {
    $additionalImages = json_decode($product['additional_images'], true);
    if (is_array($additionalImages)) {
        foreach ($additionalImages as $img) {
            $imagePath = str_replace('uploads/products/', '', $img);
            $imagePath = str_replace('assets/images/products/', '', $imagePath);
            $productImages[] = BASE_URL . '/uploads/products/' . $imagePath;
        }
    }
}

// If no images, use placeholder
if (empty($productImages)) {
    $productImages[] = BASE_URL . '/public/images/product-placeholder-1.svg';
}

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
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($product['name']); ?> - Odisea Makeup Store</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .star-rating {
            display: inline-flex;
            gap: 2px;
        }
        .star {
            color: #d1d5db;
            cursor: pointer;
            transition: color 0.2s;
        }
        .star.active {
            color: #fbbf24;
        }
        .star:hover {
            color: #fbbf24;
        }
        .image-zoom {
            transform-origin: center;
            transition: transform 0.3s ease;
        }
        .image-zoom:hover {
            transform: scale(1.1);
        }
        .thumbnail {
            transition: all 0.3s ease;
        }
        .thumbnail.active {
            border-color: #3b82f6;
            transform: scale(1.05);
        }
        .fade-in {
            animation: fadeIn 0.5s ease-in;
        }
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Header -->
    <header class="bg-white shadow-sm border-b">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <div class="flex items-center">
                    <a href="<?php echo BASE_URL; ?>" class="text-2xl font-bold text-gray-900">Odisea Makeup Store</a>
                </div>
                <nav class="hidden md:flex space-x-8">
                    <a href="<?php echo BASE_URL; ?>" class="text-gray-600 hover:text-gray-900">Inicio</a>
                    <a href="<?php echo BASE_URL; ?>/catalogo.php" class="text-gray-600 hover:text-gray-900">Catálogo</a>
                    <a href="<?php echo BASE_URL; ?>/ofertas.php" class="text-gray-600 hover:text-gray-900">Ofertas</a>
                    <a href="<?php echo BASE_URL; ?>/marcas.php" class="text-gray-600 hover:text-gray-900">Marcas</a>
                </nav>
                <div class="flex items-center space-x-4">
                    <button class="text-gray-600 hover:text-gray-900">
                        <i class="fas fa-search"></i>
                    </button>
                    <a href="<?php echo BASE_URL; ?>/carrito.php" class="text-gray-600 hover:text-gray-900 relative">
                        <i class="fas fa-shopping-cart"></i>
                        <span id="cart-count" class="absolute -top-2 -right-2 bg-blue-600 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center">0</span>
                    </a>
                </div>
            </div>
        </div>
    </header>

    <!-- Breadcrumb -->
    <nav class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
        <ol class="flex items-center space-x-2 text-sm text-gray-500">
            <li><a href="<?php echo BASE_URL; ?>" class="hover:text-gray-700">Inicio</a></li>
            <li><i class="fas fa-chevron-right text-xs"></i></li>
            <li><a href="<?php echo BASE_URL; ?>/catalogo.php" class="hover:text-gray-700">Catálogo</a></li>
            <?php if ($category): ?>
            <li><i class="fas fa-chevron-right text-xs"></i></li>
            <li><a href="<?php echo BASE_URL; ?>/categoria.php?id=<?php echo $category['id']; ?>" class="hover:text-gray-700"><?php echo htmlspecialchars($category['name']); ?></a></li>
            <?php endif; ?>
            <li><i class="fas fa-chevron-right text-xs"></i></li>
            <li class="text-gray-900"><?php echo htmlspecialchars($product['name']); ?></li>
        </ol>
    </nav>

    <!-- Main Content -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="lg:grid lg:grid-cols-2 lg:gap-x-8 lg:items-start">
            <!-- Image Gallery -->
            <div class="flex flex-col-reverse">
                <!-- Image thumbnails -->
                <?php if (count($productImages) > 1): ?>
                <div class="hidden mt-6 w-full max-w-2xl mx-auto sm:block lg:max-w-none">
                    <div class="grid grid-cols-4 gap-6" id="thumbnails">
                        <?php foreach ($productImages as $index => $image): ?>
                        <button class="thumbnail <?php echo $index === 0 ? 'active' : ''; ?> relative h-24 bg-white rounded-md flex items-center justify-center text-sm font-medium uppercase text-gray-900 cursor-pointer hover:bg-gray-50 focus:outline-none focus:ring focus:ring-offset-4 focus:ring-blue-500 border-2 border-transparent" onclick="changeMainImage(<?php echo $index; ?>)">
                            <span class="sr-only">Imagen <?php echo $index + 1; ?></span>
                            <img src="<?php echo $image; ?>" alt="<?php echo htmlspecialchars($product['name']); ?> - Vista <?php echo $index + 1; ?>" class="w-full h-full object-center object-cover rounded-md" loading="lazy">
                        </button>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Main image -->
                <div class="w-full aspect-w-1 aspect-h-1 relative">
                    <div class="bg-white rounded-lg overflow-hidden relative">
                        <img id="mainImage" src="<?php echo $productImages[0]; ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" class="w-full h-full object-center object-cover image-zoom fade-in">
                        
                        <?php if (count($productImages) > 1): ?>
                        <!-- Navigation arrows -->
                        <button onclick="previousImage()" class="absolute left-4 top-1/2 transform -translate-y-1/2 bg-white bg-opacity-75 hover:bg-opacity-100 rounded-full p-2 shadow-lg transition-all">
                            <i class="fas fa-chevron-left text-gray-600"></i>
                        </button>
                        <button onclick="nextImage()" class="absolute right-4 top-1/2 transform -translate-y-1/2 bg-white bg-opacity-75 hover:bg-opacity-100 rounded-full p-2 shadow-lg transition-all">
                            <i class="fas fa-chevron-right text-gray-600"></i>
                        </button>
                        <?php endif; ?>
                        
                        <!-- Zoom indicator -->
                        <div class="absolute top-4 right-4 bg-black bg-opacity-50 text-white px-2 py-1 rounded text-sm">
                            <i class="fas fa-search-plus mr-1"></i>Hover para zoom
                        </div>
                        
                        <?php if ($discount > 0): ?>
                        <!-- Discount badge -->
                        <div class="absolute top-4 left-4">
                            <span class="bg-gradient-to-r from-red-500 to-pink-500 text-white px-3 py-1 rounded-full text-sm font-semibold shadow-lg">
                                -<?php echo $discount; ?>% OFF
                            </span>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Product info -->
            <div class="mt-10 px-4 sm:px-0 sm:mt-16 lg:mt-0">
                <h1 class="text-3xl font-bold tracking-tight text-gray-900"><?php echo htmlspecialchars($product['name']); ?></h1>
                
                <?php if ($brand): ?>
                <!-- Brand -->
                <div class="mt-2">
                    <p class="text-lg font-medium text-gray-600"><?php echo htmlspecialchars($brand['name']); ?></p>
                </div>
                <?php endif; ?>
                
                <!-- Rating -->
                <div class="mt-3 flex items-center">
                    <div class="flex items-center">
                        <div class="star-rating">
                            <?php 
                            $rating = $averageRating ?: 0;
                            for ($i = 1; $i <= 5; $i++): 
                            ?>
                                <i class="fas fa-star star <?php echo $i <= $rating ? 'active' : ''; ?>"></i>
                            <?php endfor; ?>
                        </div>
                        <p class="ml-2 text-sm text-gray-600"><?php echo number_format($rating, 1); ?> de 5 estrellas</p>
                    </div>
                    <?php if ($reviewCount > 0): ?>
                    <p class="ml-4 text-sm text-blue-600 hover:text-blue-500">
                        <a href="#reviews">Ver <?php echo $reviewCount; ?> reseña<?php echo $reviewCount > 1 ? 's' : ''; ?></a>
                    </p>
                    <?php endif; ?>
                </div>

                <!-- Price -->
                <div class="mt-4">
                    <p class="text-3xl font-bold text-gray-900">$<?php echo number_format($product['price'], 2); ?></p>
                    <?php if (!empty($product['compare_price']) && $product['compare_price'] > $product['price']): ?>
                    <p class="text-lg text-gray-500 line-through">$<?php echo number_format($product['compare_price'], 2); ?></p>
                    <p class="text-sm text-green-600 font-medium">Ahorra $<?php echo number_format($product['compare_price'] - $product['price'], 2); ?> (<?php echo $discount; ?>%)</p>
                    <?php endif; ?>
                </div>

                <!-- Description -->
                <div class="mt-6">
                    <h3 class="sr-only">Descripción</h3>
                    <div class="text-base text-gray-700 space-y-6">
                        <p><?php echo nl2br(htmlspecialchars($product['description'])); ?></p>
                    </div>
                </div>

                <!-- Product Details -->
                <?php if (!empty($product['specifications'])): ?>
                <div class="mt-6">
                    <h3 class="text-lg font-medium text-gray-900">Especificaciones</h3>
                    <div class="mt-4 text-sm text-gray-600">
                        <?php echo nl2br(htmlspecialchars($product['specifications'])); ?>
                    </div>
                </div>
                <?php endif; ?>
                        </li>
                    </ul>
                </div>

                <!-- Color selection -->
                <div class="mt-6">
                    <h3 class="text-sm font-medium text-gray-900">Color</h3>
                    <fieldset class="mt-2">
                        <legend class="sr-only">Elige un color</legend>
                        <div class="flex items-center space-x-3">
                            <label class="relative -m-0.5 flex cursor-pointer items-center justify-center rounded-full p-0.5 focus:outline-none ring-gray-700">
                                <input type="radio" name="color-choice" value="Negro" class="sr-only" checked>
                                <span class="h-8 w-8 bg-gray-900 border border-black border-opacity-10 rounded-full"></span>
                            </label>
                            <label class="relative -m-0.5 flex cursor-pointer items-center justify-center rounded-full p-0.5 focus:outline-none ring-gray-400">
                                <input type="radio" name="color-choice" value="Blanco" class="sr-only">
                                <span class="h-8 w-8 bg-white border border-black border-opacity-10 rounded-full"></span>
                            </label>
                            <label class="relative -m-0.5 flex cursor-pointer items-center justify-center rounded-full p-0.5 focus:outline-none ring-blue-500">
                                <input type="radio" name="color-choice" value="Azul" class="sr-only">
                                <span class="h-8 w-8 bg-blue-500 border border-black border-opacity-10 rounded-full"></span>
                            </label>
                        </div>
                    </fieldset>
                </div>

                <!-- Quantity and Add to Cart -->
                <div class="mt-8">
                    <div class="flex items-center space-x-4">
                        <div class="flex items-center">
                            <label for="quantity" class="sr-only">Cantidad</label>
                            <select id="quantity" name="quantity" class="rounded-md border border-gray-300 text-left text-base font-medium text-gray-700 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 sm:text-sm">
                                <option value="1">1</option>
                                <option value="2">2</option>
                                <option value="3">3</option>
                                <option value="4">4</option>
                                <option value="5">5</option>
                            </select>
                        </div>
                        <button type="button" onclick="addToCart(<?php echo $product['id']; ?>)" class="flex-1 bg-blue-600 border border-transparent rounded-md py-3 px-8 flex items-center justify-center text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors">
                            <i class="fas fa-shopping-cart mr-2"></i>
                            Añadir al carrito
                        </button>
                    </div>
                    
                    <div class="mt-4 flex space-x-4">
                        <button type="button" onclick="addToWishlist(<?php echo $product['id']; ?>)" class="flex-1 bg-pink-600 border border-transparent rounded-md py-3 px-8 flex items-center justify-center text-base font-medium text-white hover:bg-pink-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-pink-500 transition-colors">
                            <i class="fas fa-heart mr-2"></i>
                            Añadir a favoritos
                        </button>
                        
                        <a href="<?php echo BASE_URL; ?>/carrito.php" class="flex-1 bg-gray-900 border border-transparent rounded-md py-3 px-8 flex items-center justify-center text-base font-medium text-white hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 transition-colors">
                            Ver carrito
                        </a>
                    </div>
                </div>

                <!-- Shipping info -->
                <div class="mt-6 border-t border-gray-200 pt-6">
                    <div class="flex items-center text-sm text-gray-600">
                        <i class="fas fa-truck mr-2 text-green-500"></i>
                        Envío gratis en pedidos superiores a €50
                    </div>
                    <div class="flex items-center text-sm text-gray-600 mt-2">
                        <i class="fas fa-undo mr-2 text-blue-500"></i>
                        Devoluciones gratuitas en 30 días
                    </div>
                </div>
            </div>
        </div>

        <!-- Reviews Section -->
        <div class="mt-16 lg:mt-24" id="reviews">
            <div class="border-t border-gray-200 pt-16">
                <h2 class="text-2xl font-bold tracking-tight text-gray-900">Reseñas de clientes</h2>
                
                <!-- Review Summary -->
                <div class="mt-6 grid grid-cols-1 gap-8 lg:grid-cols-3">
                    <div class="lg:col-span-1">
                        <div class="bg-white rounded-lg border p-6">
                            <div class="flex items-center">
                                <div class="star-rating text-2xl">
                                    <i class="fas fa-star star active"></i>
                                    <i class="fas fa-star star active"></i>
                                    <i class="fas fa-star star active"></i>
                                    <i class="fas fa-star star active"></i>
                                    <i class="fas fa-star star"></i>
                                </div>
                                <span class="ml-3 text-2xl font-bold text-gray-900">4.0</span>
                            </div>
                            <p class="mt-2 text-sm text-gray-600">Basado en 127 reseñas</p>
                            
                            <!-- Rating breakdown -->
                            <div class="mt-6 space-y-3">
                                <div class="flex items-center text-sm">
                                    <span class="w-3">5</span>
                                    <i class="fas fa-star text-yellow-400 ml-1 mr-2"></i>
                                    <div class="flex-1 bg-gray-200 rounded-full h-2 mx-3">
                                        <div class="bg-yellow-400 h-2 rounded-full" style="width: 60%"></div>
                                    </div>
                                    <span class="text-gray-600">60%</span>
                                </div>
                                <div class="flex items-center text-sm">
                                    <span class="w-3">4</span>
                                    <i class="fas fa-star text-yellow-400 ml-1 mr-2"></i>
                                    <div class="flex-1 bg-gray-200 rounded-full h-2 mx-3">
                                        <div class="bg-yellow-400 h-2 rounded-full" style="width: 25%"></div>
                                    </div>
                                    <span class="text-gray-600">25%</span>
                                </div>
                                <div class="flex items-center text-sm">
                                    <span class="w-3">3</span>
                                    <i class="fas fa-star text-yellow-400 ml-1 mr-2"></i>
                                    <div class="flex-1 bg-gray-200 rounded-full h-2 mx-3">
                                        <div class="bg-yellow-400 h-2 rounded-full" style="width: 10%"></div>
                                    </div>
                                    <span class="text-gray-600">10%</span>
                                </div>
                                <div class="flex items-center text-sm">
                                    <span class="w-3">2</span>
                                    <i class="fas fa-star text-yellow-400 ml-1 mr-2"></i>
                                    <div class="flex-1 bg-gray-200 rounded-full h-2 mx-3">
                                        <div class="bg-yellow-400 h-2 rounded-full" style="width: 3%"></div>
                                    </div>
                                    <span class="text-gray-600">3%</span>
                                </div>
                                <div class="flex items-center text-sm">
                                    <span class="w-3">1</span>
                                    <i class="fas fa-star text-yellow-400 ml-1 mr-2"></i>
                                    <div class="flex-1 bg-gray-200 rounded-full h-2 mx-3">
                                        <div class="bg-yellow-400 h-2 rounded-full" style="width: 2%"></div>
                                    </div>
                                    <span class="text-gray-600">2%</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Write Review Form -->
                    <div class="lg:col-span-2">
                        <div class="bg-white rounded-lg border p-6">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Escribe una reseña</h3>
                            <form id="reviewForm">
                                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                                    <div>
                                        <label for="reviewerName" class="block text-sm font-medium text-gray-700">Nombre</label>
                                        <input type="text" id="reviewerName" name="reviewerName" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                    </div>
                                    <div>
                                        <label for="reviewerEmail" class="block text-sm font-medium text-gray-700">Email</label>
                                        <input type="email" id="reviewerEmail" name="reviewerEmail" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                    </div>
                                </div>
                                
                                <div class="mt-6">
                                    <label class="block text-sm font-medium text-gray-700">Calificación</label>
                                    <div class="mt-2 star-rating text-2xl" id="userRating">
                                        <i class="fas fa-star star" data-rating="1"></i>
                                        <i class="fas fa-star star" data-rating="2"></i>
                                        <i class="fas fa-star star" data-rating="3"></i>
                                        <i class="fas fa-star star" data-rating="4"></i>
                                        <i class="fas fa-star star" data-rating="5"></i>
                                    </div>
                                </div>

                                <div class="mt-6">
                                    <label for="reviewTitle" class="block text-sm font-medium text-gray-700">Título de la reseña</label>
                                    <input type="text" id="reviewTitle" name="reviewTitle" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                </div>

                                <div class="mt-6">
                                    <label for="reviewText" class="block text-sm font-medium text-gray-700">Tu reseña</label>
                                    <textarea id="reviewText" name="reviewText" rows="4" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"></textarea>
                                </div>

                                <div class="mt-6">
                                    <button type="submit" class="bg-blue-600 border border-transparent rounded-md py-2 px-4 text-sm font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors">
                                        Publicar reseña
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Individual Reviews -->
                <div class="mt-12 space-y-8" id="reviewsList">
                    <?php if (empty($reviews)): ?>
                    <div class="text-center py-12 bg-gray-50 rounded-lg">
                        <i class="fas fa-comments text-4xl text-gray-400 mb-4"></i>
                        <p class="text-gray-600">Aún no hay reseñas para este producto.</p>
                        <p class="text-sm text-gray-500 mt-2">¡Sé el primero en escribir una reseña!</p>
                    </div>
                    <?php else: ?>
                        <?php foreach ($reviews as $review): ?>
                        <div class="bg-white rounded-lg border p-6" data-review-id="<?php echo $review['id']; ?>">
                            <div class="flex items-start justify-between">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0">
                                        <div class="h-10 w-10 rounded-full bg-blue-500 flex items-center justify-center">
                                            <span class="text-sm font-medium text-white">
                                                <?php echo strtoupper(substr($review['first_name'], 0, 1) . substr($review['last_name'], 0, 1)); ?>
                                            </span>
                                        </div>
                                    </div>
                                    <div class="ml-4">
                                        <h4 class="text-sm font-bold text-gray-900">
                                            <?php echo htmlspecialchars($review['first_name'] . ' ' . $review['last_name']); ?>
                                        </h4>
                                        <div class="flex items-center mt-1">
                                            <div class="star-rating">
                                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                                <i class="fas fa-star star <?php echo $i <= $review['rating'] ? 'active' : ''; ?>"></i>
                                                <?php endfor; ?>
                                            </div>
                                            <span class="ml-2 text-sm text-gray-600">
                                                <?php echo timeAgo($review['created_at']); ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mt-4">
                                <?php if (!empty($review['title'])): ?>
                                <h5 class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($review['title']); ?></h5>
                                <?php endif; ?>
                                <p class="mt-2 text-sm text-gray-700">
                                    <?php echo nl2br(htmlspecialchars($review['comment'])); ?>
                                </p>
                            </div>
                            
                            <div class="mt-4 flex items-center text-sm text-gray-500">
                                <button class="like-btn flex items-center hover:text-gray-700 transition-colors <?php echo $review['user_has_liked'] ? 'text-blue-600' : ''; ?>" 
                                        onclick="toggleLike(<?php echo $review['id']; ?>)">
                                    <i class="fas fa-thumbs-up mr-1"></i>
                                    <span class="like-count"><?php echo $review['like_count']; ?></span>
                                    <span class="ml-1"><?php echo $review['like_count'] == 1 ? 'Me gusta' : 'Me gusta'; ?></span>
                                </button>
                                
                                <button class="ml-4 flex items-center hover:text-gray-700 transition-colors reply-btn" 
                                        onclick="toggleReplyForm(<?php echo $review['id']; ?>)">
                                    <i class="fas fa-reply mr-1"></i>
                                    Responder
                                </button>
                            </div>
                            
                            <!-- Reply Form (Hidden by default) -->
                            <div class="reply-form mt-4 hidden" id="replyForm<?php echo $review['id']; ?>">
                                <div class="bg-gray-50 rounded-lg p-4">
                                    <textarea 
                                        class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 resize-none"
                                        rows="3" 
                                        placeholder="Escribe tu respuesta..."
                                        id="replyText<?php echo $review['id']; ?>"
                                        maxlength="500"></textarea>
                                    <div class="mt-2 flex justify-between items-center">
                                        <small class="text-gray-500">Máximo 500 caracteres</small>
                                        <div class="space-x-2">
                                            <button 
                                                class="px-4 py-2 text-sm text-gray-600 hover:text-gray-800 transition-colors"
                                                onclick="toggleReplyForm(<?php echo $review['id']; ?>)">
                                                Cancelar
                                            </button>
                                            <button 
                                                class="px-4 py-2 bg-blue-600 text-white text-sm rounded-lg hover:bg-blue-700 transition-colors"
                                                onclick="submitReply(<?php echo $review['id']; ?>)">
                                                Publicar respuesta
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Replies -->
                            <?php if (!empty($review['replies'])): ?>
                            <div class="replies mt-6 ml-8 space-y-4" id="replies<?php echo $review['id']; ?>">
                                <?php foreach ($review['replies'] as $reply): ?>
                                <div class="bg-gray-50 rounded-lg p-4">
                                    <div class="flex items-center mb-2">
                                        <div class="h-8 w-8 rounded-full bg-green-500 flex items-center justify-center">
                                            <span class="text-xs font-medium text-white">
                                                <?php echo strtoupper(substr($reply['first_name'], 0, 1) . substr($reply['last_name'], 0, 1)); ?>
                                            </span>
                                        </div>
                                        <div class="ml-3">
                                            <p class="text-sm font-medium text-gray-900">
                                                <?php echo htmlspecialchars($reply['first_name'] . ' ' . $reply['last_name']); ?>
                                            </p>
                                            <p class="text-xs text-gray-500">
                                                <?php echo timeAgo($reply['created_at']); ?>
                                            </p>
                                        </div>
                                    </div>
                                    <p class="text-sm text-gray-700">
                                        <?php echo nl2br(htmlspecialchars($reply['reply_text'])); ?>
                                    </p>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <?php else: ?>
                            <div class="replies mt-6 ml-8 space-y-4 hidden" id="replies<?php echo $review['id']; ?>">
                                <!-- Replies will be added here dynamically -->
                            </div>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                                    </div>
                                </div>
                                <div class="ml-4">
                                    <h4 class="text-sm font-bold text-gray-900">Juan López</h4>
                                    <div class="flex items-center mt-1">
                                        <div class="star-rating">
                                            <i class="fas fa-star star active"></i>
                                            <i class="fas fa-star star active"></i>
                                            <i class="fas fa-star star active"></i>
                                            <i class="fas fa-star star active"></i>
                                            <i class="fas fa-star star"></i>
                                        </div>
                                        <span class="ml-2 text-sm text-gray-600">hace 1 semana</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="mt-4">
                            <h5 class="text-sm font-medium text-gray-900">Muy buena relación calidad-precio</h5>
                            <p class="mt-2 text-sm text-gray-700">
                                Por el precio que tienen, ofrecen una calidad excelente. La batería dura realmente las 30 horas que prometen. El único detalle es que el estuche es un poco grande, pero no es un problema mayor.
                            </p>
                        </div>
                        <div class="mt-4 flex items-center text-sm text-gray-500">
                            <button class="flex items-center hover:text-gray-700">
                                <i class="fas fa-thumbs-up mr-1"></i>
                                Útil (8)
                            </button>
                            <button class="ml-4 flex items-center hover:text-gray-700">
                                <i class="fas fa-reply mr-1"></i>
                                Responder
                            </button>
                        </div>
                    </div>

                    <!-- Review 3 -->
                    <div class="bg-white rounded-lg border p-6">
                        <div class="flex items-start justify-between">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <div class="h-10 w-10 rounded-full bg-purple-500 flex items-center justify-center">
                                        <span class="text-sm font-medium text-white">AR</span>
                                    </div>
                                </div>
                                <div class="ml-4">
                                    <h4 class="text-sm font-bold text-gray-900">Ana Rodríguez</h4>
                                    <div class="flex items-center mt-1">
                                        <div class="star-rating">
                                            <i class="fas fa-star star active"></i>
                                            <i class="fas fa-star star active"></i>
                                            <i class="fas fa-star star active"></i>
                                            <i class="fas fa-star star"></i>
                                            <i class="fas fa-star star"></i>
                                        </div>
                                        <span class="ml-2 text-sm text-gray-600">hace 2 semanas</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="mt-4">
                            <h5 class="text-sm font-medium text-gray-900">Buenos pero con algunos detalles</h5>
                            <p class="mt-2 text-sm text-gray-700">
                                En general son buenos auriculares. El sonido es claro y la conexión Bluetooth es estable. Sin embargo, después de usarlos por más de 3 horas seguidas, empiezan a resultar un poco incómodos en las orejas.
                            </p>
                        </div>
                        <div class="mt-4 flex items-center text-sm text-gray-500">
                            <button class="flex items-center hover:text-gray-700">
                                <i class="fas fa-thumbs-up mr-1"></i>
                                Útil (5)
                            </button>
                            <button class="ml-4 flex items-center hover:text-gray-700">
                                <i class="fas fa-reply mr-1"></i>
                                Responder
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Load More Reviews -->
                <div class="mt-8 text-center">
                    <button class="bg-white border border-gray-300 rounded-md py-2 px-4 text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors">
                        Ver más reseñas
                    </button>
                </div>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="bg-gray-900 mt-24">
        <div class="max-w-7xl mx-auto py-12 px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                <div>
                    <h3 class="text-white text-lg font-semibold mb-4">TechStore</h3>
                    <p class="text-gray-400 text-sm">Tu tienda de confianza para productos tecnológicos de alta calidad.</p>
                </div>
                <div>
                    <h4 class="text-white text-sm font-semibold mb-4">Productos</h4>
                    <ul class="space-y-2">
                        <li><a href="#" class="text-gray-400 hover:text-white text-sm">Auriculares</a></li>
                        <li><a href="#" class="text-gray-400 hover:text-white text-sm">Smartphones</a></li>
                        <li><a href="#" class="text-gray-400 hover:text-white text-sm">Laptops</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="text-white text-sm font-semibold mb-4">Soporte</h4>
                    <ul class="space-y-2">
                        <li><a href="#" class="text-gray-400 hover:text-white text-sm">Contacto</a></li>
                        <li><a href="#" class="text-gray-400 hover:text-white text-sm">Devoluciones</a></li>
                        <li><a href="#" class="text-gray-400 hover:text-white text-sm">Garantía</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="text-white text-sm font-semibold mb-4">Síguenos</h4>
                    <div class="flex space-x-4">
                        <a href="#" class="text-gray-400 hover:text-white">
                            <i class="fab fa-facebook-f"></i>
                        </a>
                        <a href="#" class="text-gray-400 hover:text-white">
                            <i class="fab fa-twitter"></i>
                        </a>
                        <a href="#" class="text-gray-400 hover:text-white">
                            <i class="fab fa-instagram"></i>
                        </a>
                    </div>
                </div>
            </div>
            <div class="mt-8 pt-8 border-t border-gray-800 text-center">
                <p class="text-gray-400 text-sm">&copy; 2024 TechStore. Todos los derechos reservados.</p>
            </div>
        </div>
    </footer>

    <script>
        // Image gallery functionality
        const images = [
            <?php foreach ($productImages as $index => $image): ?>
            '<?php echo addslashes($image); ?>'<?php echo $index < count($productImages) - 1 ? ',' : ''; ?>
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
        const userRatingStars = document.querySelectorAll('#userRating .star');
        let userRating = 0;
        
        userRatingStars.forEach((star, index) => {
            star.addEventListener('click', () => {
                userRating = index + 1;
                updateUserRating();
            });
            
            star.addEventListener('mouseenter', () => {
                highlightStars(index + 1);
            });
        });
        
        document.getElementById('userRating').addEventListener('mouseleave', () => {
            updateUserRating();
        });
        
        function highlightStars(rating) {
            userRatingStars.forEach((star, index) => {
                if (index < rating) {
                    star.classList.add('active');
                } else {
                    star.classList.remove('active');
                }
            });
        }
        
        function updateUserRating() {
            highlightStars(userRating);
        }
        
        // Review form submission
        document.getElementById('reviewForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            if (userRating === 0) {
                alert('Por favor, selecciona una calificación');
                return;
            }
            
            const formData = new FormData(this);
            const reviewData = {
                name: formData.get('reviewerName'),
                email: formData.get('reviewerEmail'),
                rating: userRating,
                title: formData.get('reviewTitle'),
                text: formData.get('reviewText'),
                date: new Date().toLocaleDateString('es-ES')
            };
            
            addReview(reviewData);
            this.reset();
            userRating = 0;
            updateUserRating();
            
            alert('¡Gracias por tu reseña! Ha sido publicada exitosamente.');
        });
        
        function addReview(reviewData) {
            const reviewsList = document.getElementById('reviewsList');
            const reviewElement = document.createElement('div');
            reviewElement.className = 'bg-white rounded-lg border p-6';
            
            const starsHtml = Array.from({length: 5}, (_, i) => 
                `<i class="fas fa-star star ${i < reviewData.rating ? 'active' : ''}"></i>`
            ).join('');
            
            reviewElement.innerHTML = `
                <div class="flex items-start justify-between">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="h-10 w-10 rounded-full bg-blue-500 flex items-center justify-center">
                                <span class="text-sm font-medium text-white">${reviewData.name.charAt(0).toUpperCase()}</span>
                            </div>
                        </div>
                        <div class="ml-4">
                            <h4 class="text-sm font-bold text-gray-900">${reviewData.name}</h4>
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
                    <h5 class="text-sm font-medium text-gray-900">${reviewData.title}</h5>
                    <p class="mt-2 text-sm text-gray-700">${reviewData.text}</p>
                </div>
                <div class="mt-4 flex items-center text-sm text-gray-500">
                    <button class="flex items-center hover:text-gray-700">
                        <i class="fas fa-thumbs-up mr-1"></i>
                        Útil (0)
                    </button>
                    <button class="ml-4 flex items-center hover:text-gray-700">
                        <i class="fas fa-reply mr-1"></i>
                        Responder
                    </button>
                </div>
            `;
            
            reviewsList.insertBefore(reviewElement, reviewsList.firstChild);
        }
        
        // Keyboard navigation for image gallery
        document.addEventListener('keydown', function(e) {
            if (e.key === 'ArrowLeft') {
                previousImage();
            } else if (e.key === 'ArrowRight') {
                nextImage();
            }
        });
        
        // Smooth scroll to reviews
        document.querySelector('a[href="#reviews"]').addEventListener('click', function(e) {
            e.preventDefault();
            document.getElementById('reviews').scrollIntoView({
                behavior: 'smooth'
            });
        });
        
        // Cart functionality
        function addToCart(productId) {
            const quantity = document.getElementById('quantity').value;
            
            fetch('<?php echo BASE_URL; ?>/cart-add.php', {
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
                    showNotification(data.message || 'Error al agregar al carrito', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('Error al agregar al carrito', 'error');
            });
        }
        
        // Wishlist functionality
        function addToWishlist(productId) {
            fetch('<?php echo BASE_URL; ?>/wishlist-toggle.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `product_id=${productId}`
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
        }
        
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
        function toggleLike(reviewId) {
            fetch('<?php echo BASE_URL; ?>/review-like.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ review_id: reviewId })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const likeBtn = document.querySelector(`[data-review-id="${reviewId}"] .like-btn`);
                    const likeCount = likeBtn.querySelector('.like-count');
                    
                    likeCount.textContent = data.like_count;
                    
                    if (data.action === 'added') {
                        likeBtn.classList.add('text-blue-600');
                        showNotification('Like agregado', 'success');
                    } else {
                        likeBtn.classList.remove('text-blue-600');
                        showNotification('Like removido', 'info');
                    }
                } else {
                    showNotification(data.message || 'Error al procesar like', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('Error al procesar like', 'error');
            });
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
        
        function submitReply(reviewId) {
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
            
            fetch('<?php echo BASE_URL; ?>/review-reply.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ 
                    review_id: reviewId,
                    reply_text: text
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Add the new reply to the replies section
                    const repliesContainer = document.getElementById(`replies${reviewId}`);
                    
                    if (repliesContainer.classList.contains('hidden')) {
                        repliesContainer.classList.remove('hidden');
                    }
                    
                    const replyHTML = `
                        <div class="bg-gray-50 rounded-lg p-4">
                            <div class="flex items-center mb-2">
                                <div class="h-8 w-8 rounded-full bg-green-500 flex items-center justify-center">
                                    <span class="text-xs font-medium text-white">${data.reply.user_initials}</span>
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm font-medium text-gray-900">${data.reply.user_name}</p>
                                    <p class="text-xs text-gray-500">${data.reply.time_ago}</p>
                                </div>
                            </div>
                            <p class="text-sm text-gray-700">${data.reply.reply_text}</p>
                        </div>
                    `;
                    
                    repliesContainer.insertAdjacentHTML('beforeend', replyHTML);
                    
                    // Clear and hide the form
                    replyText.value = '';
                    toggleReplyForm(reviewId);
                    
                    showNotification('Respuesta agregada exitosamente', 'success');
                } else {
                    showNotification(data.message || 'Error al agregar respuesta', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('Error al agregar respuesta', 'error');
            });
        }
    </script>
</body>
</html>