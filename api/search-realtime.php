<?php
/**
 * API endpoint para búsqueda en tiempo real
 * Proporciona resultados rápidos para el buscador global
 */

require_once '../includes/security-headers.php';
require_once '../includes/InputSanitizer.php';
require_once '../includes/RateLimiter.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Rate limiting más permisivo para búsqueda en tiempo real
if (!RateLimiter::checkLimit('search_realtime', 60, 60)) {
    http_response_code(429);
    echo json_encode([
        'success' => false, 
        'message' => 'Demasiadas búsquedas. Espera un momento.'
    ]);
    exit;
}

session_start();
require_once '../config/database.php';
require_once '../models/Product.php';
require_once '../models/Category.php';
require_once '../models/Brand.php';

try {
    // Get input data
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || !isset($input['query'])) {
        throw new Exception('Datos de entrada inválidos');
    }
    
    $query = InputSanitizer::sanitizeString($input['query'], 100);
    $limit = min(20, max(1, (int)($input['limit'] ?? 8))); // Maximum 20 results for quick search
    
    if (empty($query)) {
        echo json_encode([
            'success' => true,
            'results' => [],
            'total' => 0,
            'query' => $query
        ]);
        exit;
    }
    
    $productModel = new Product();
    $categoryModel = new Category();
    $brandModel = new Brand();
    
    $results = [];
    $totalResults = 0;
    
    // Search products (limit to 6 for quick search)
    $productLimit = min(6, $limit);
    $products = $productModel->searchProductsRealtime($query, $productLimit);
    
    foreach ($products as $product) {
        $results[] = [
            'type' => 'product',
            'id' => $product['id'],
            'name' => $product['name'],
            'category_name' => $product['category_name'] ?? '',
            'price' => $product['price'],
            'original_price' => $product['original_price'] ?? null,
            'image' => $product['main_image'] ?? '',
            'relevance' => calculateRelevance($query, $product['name'])
        ];
    }
    
    $totalResults += count($products);
    
    // Search categories if we have space (limit to 3)
    if (count($results) < $limit) {
        $categoryLimit = min(3, $limit - count($results));
        $categories = $categoryModel->searchCategories($query, $categoryLimit);
        
        foreach ($categories as $category) {
            $results[] = [
                'type' => 'category',
                'id' => $category['id'],
                'name' => $category['name'],
                'icon' => getCategoryIcon($category['name']),
                'product_count' => $category['product_count'] ?? 0,
                'relevance' => calculateRelevance($query, $category['name'])
            ];
        }
        
        $totalResults += count($categories);
    }
    
    // Search brands if we have space (limit to 2)
    if (count($results) < $limit) {
        $brandLimit = min(2, $limit - count($results));
        $brands = $brandModel->searchBrands($query, $brandLimit);
        
        foreach ($brands as $brand) {
            $results[] = [
                'type' => 'brand',
                'id' => $brand['id'],
                'name' => $brand['name'],
                'relevance' => calculateRelevance($query, $brand['name'])
            ];
        }
        
        $totalResults += count($brands);
    }
    
    // Sort by relevance
    usort($results, function($a, $b) {
        return $b['relevance'] - $a['relevance'];
    });
    
    // Get total count for "view all" link
    $totalCount = $productModel->countSearchResults($query);
    
    echo json_encode([
        'success' => true,
        'results' => array_slice($results, 0, $limit),
        'total' => $totalCount,
        'query' => $query,
        'execution_time' => round((microtime(true) - $_SERVER['REQUEST_TIME_FLOAT']) * 1000, 2) . 'ms'
    ]);
    
} catch (Exception $e) {
    error_log("Real-time search error: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => 'Error en la búsqueda. Intenta nuevamente.',
        'error' => $e->getMessage()
    ]);
}

/**
 * Calculate search relevance score
 */
function calculateRelevance($query, $text) {
    $query = strtolower($query);
    $text = strtolower($text);
    $score = 0;
    
    // Exact match gets highest score
    if ($query === $text) {
        return 100;
    }
    
    // Starts with query gets high score
    if (strpos($text, $query) === 0) {
        $score += 80;
    }
    
    // Contains query gets medium score
    if (strpos($text, $query) !== false) {
        $score += 60;
    }
    
    // Word match gets some score
    $queryWords = explode(' ', $query);
    $textWords = explode(' ', $text);
    
    foreach ($queryWords as $queryWord) {
        if (strlen($queryWord) < 3) continue;
        
        foreach ($textWords as $textWord) {
            if (strpos($textWord, $queryWord) !== false) {
                $score += 20;
            }
        }
    }
    
    return $score;
}

/**
 * Get appropriate icon for category
 */
function getCategoryIcon($categoryName) {
    $icons = [
        'tecnologia' => 'mobile-alt',
        'belleza' => 'heart',
        'hogar' => 'home',
        'deporte' => 'dumbbell',
        'ropa' => 'tshirt',
        'libros' => 'book',
        'juguetes' => 'gamepad',
        'electrónicos' => 'tv',
        'computadoras' => 'laptop',
        'teléfonos' => 'phone',
        'accesorios' => 'gem',
        'salud' => 'medkit'
    ];
    
    $name = strtolower($categoryName);
    foreach ($icons as $key => $icon) {
        if (strpos($name, $key) !== false) {
            return $icon;
        }
    }
    
    return 'tag';
}
?>
