<?php
require_once '../includes/security-headers.php';
require_once '../includes/InputSanitizer.php';
require_once '../includes/RateLimiter.php';

header('Content-Type: application/json');

// CORS headers
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Check rate limiting
if (!RateLimiter::checkLimit('search_ajax', 30, 60)) {
    http_response_code(429);
    echo json_encode(['success' => false, 'message' => 'Demasiadas búsquedas. Intenta en un momento.']);
    exit;
}

session_start();
require_once '../config/database.php';
require_once '../models/Product.php';
require_once '../models/Category.php';
require_once '../models/Brand.php';
require_once '../models/Review.php';

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        throw new Exception('Datos de entrada inválidos');
    }
    
    $query = InputSanitizer::sanitizeString($input['query'] ?? '', 100);
    $filters = $input['filters'] ?? [];
    $sort = InputSanitizer::sanitizeString($input['sort'] ?? 'relevance', 20);
    $page = max(1, (int)($input['page'] ?? 1));
    $perPage = 12;
    $offset = ($page - 1) * $perPage;
    
    // Validate and sanitize filters
    $sanitizedFilters = [];
    
    if (isset($filters['categories']) && is_array($filters['categories'])) {
        $sanitizedFilters['categories'] = array_map('intval', $filters['categories']);
    }
    
    if (isset($filters['brands']) && is_array($filters['brands'])) {
        $sanitizedFilters['brands'] = array_map('intval', $filters['brands']);
    }
    
    if (isset($filters['min_price'])) {
        $sanitizedFilters['min_price'] = max(0, floatval($filters['min_price']));
    }
    
    if (isset($filters['max_price'])) {
        $sanitizedFilters['max_price'] = max(0, floatval($filters['max_price']));
    }
    
    if (isset($filters['min_rating'])) {
        $sanitizedFilters['min_rating'] = max(1, min(5, intval($filters['min_rating'])));
    }
    
    if (isset($filters['has_discount'])) {
        $sanitizedFilters['has_discount'] = (bool)$filters['has_discount'];
    }
    
    $productModel = new Product();
    $products = [];
    $totalProducts = 0;
    
    // Perform search based on query and filters
    if (!empty($query) || !empty($sanitizedFilters)) {
        $searchResults = $productModel->advancedSearch($query, $sanitizedFilters, $sort, $perPage, $offset);
        $products = $searchResults['products'] ?? [];
        $totalProducts = $searchResults['total'] ?? 0;
    } else {
        // Show featured products if no search criteria
        switch ($sort) {
            case 'newest':
                $products = $productModel->getNewestProducts($perPage, $offset);
                break;
            case 'rating':
                $products = $productModel->getTopRatedProducts($perPage, $offset);
                break;
            case 'popular':
                $products = $productModel->getPopularProducts($perPage, $offset);
                break;
            default:
                $products = $productModel->getFeaturedProducts($perPage, $offset);
                break;
        }
        $totalProducts = count($products);
    }
    
    // Process products for response
    $processedProducts = [];
    foreach ($products as $product) {
        // Calculate discount
        $discount = 0;
        if (!empty($product['compare_price']) && $product['compare_price'] > $product['price']) {
            $discount = round((($product['compare_price'] - $product['price']) / $product['compare_price']) * 100);
        }
        
        // Set product image
        $productImage = null;
        if (!empty($product['main_image'])) {
            if (strpos($product['main_image'], 'uploads/products/') === 0) {
                $productImage = BASE_URL . '/' . $product['main_image'];
            } else {
                $productImage = BASE_URL . '/uploads/products/' . $product['main_image'];
            }
        }
        
        $processedProducts[] = [
            'id' => $product['id'],
            'name' => $product['name'],
            'price' => $product['price'],
            'discount_price' => $product['discount_price'] ?? null,
            'discount' => $discount,
            'image' => $productImage,
            'average_rating' => $product['average_rating'] ?? 5,
            'review_count' => $product['review_count'] ?? rand(10, 50),
            'is_new' => $product['is_new'] ?? false,
            'category_id' => $product['category_id'] ?? null,
            'brand_id' => $product['brand_id'] ?? null
        ];
    }
    
    $totalPages = ceil($totalProducts / $perPage);
    
    echo json_encode([
        'success' => true,
        'products' => $processedProducts,
        'total' => $totalProducts,
        'page' => $page,
        'totalPages' => $totalPages,
        'perPage' => $perPage
    ]);
    
} catch (Exception $e) {
    error_log("Search API Error: " . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error en la búsqueda. Intenta nuevamente.'
    ]);
}
?>
