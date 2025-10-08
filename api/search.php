<?php
// Clean any previous output and set JSON response header
ob_start();
ob_clean();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../includes/InputSanitizer.php';
require_once '../includes/RateLimiter.php';

// Check rate limiting for search
if (!RateLimiter::checkLimit('search', 30, 60)) {
    http_response_code(429);
    echo json_encode(['error' => 'Too many requests']);
    exit;
}

session_start();
require_once '../config/database.php';
require_once '../models/Product.php';
require_once '../models/Brand.php';
require_once '../models/Category.php';

try {
    // Sanitize and validate search query
    $query = '';
    if (isset($_GET['q'])) {
        $searchQuery = InputSanitizer::sanitizeString($_GET['q'], 100);
        
        // Check for malicious content
        if (InputSanitizer::detectSQLInjection($searchQuery) || InputSanitizer::detectXSS($searchQuery)) {
            InputSanitizer::logSuspiciousActivity($searchQuery, 'SEARCH_ATTACK');
            http_response_code(400);
            echo json_encode(['error' => 'Invalid search query']);
            exit;
        }
        
        // Minimum search length
        if (strlen(trim($searchQuery)) >= 2) {
            $query = $searchQuery;
        }
    }

    // Return empty results if query is too short
    if (strlen($query) < 2) {
        echo json_encode(['results' => [], 'total' => 0]);
        exit;
    }

    $productModel = new Product();
    $brandModel = new Brand();
    $categoryModel = new Category();
    
    // Get search parameters
    $limit = isset($_GET['limit']) ? min((int)$_GET['limit'], 20) : 8;
    $type = isset($_GET['type']) ? $_GET['type'] : 'all';
    $offersOnly = isset($_GET['offers_only']) ? $_GET['offers_only'] : false;
    
    $results = [];
    
    // Always search brands first (unless specifically searching only products)
    if ($type === 'all' || $type === 'brands') {
        $brandLimit = $type === 'brands' ? $limit : 3;
        $brands = $brandModel->searchBrands($query, $brandLimit);
        
        // Format brands
        $results['brands'] = [];
        foreach ($brands as $brand) {
            $results['brands'][] = [
                'id' => $brand['id'],
                'name' => $brand['name'],
                'description' => $brand['description'] ?? '',
                'logo' => $brand['logo'] ?? null,
                'product_count' => $brand['product_count'] ?? 0,
                'url' => 'catalogo.php?marca=' . $brand['id']
            ];
        }
    }
    
    if ($type === 'all' || $type === 'products') {
        // Adjust product limit based on brand results
        $productLimit = $limit;
        if ($type === 'all' && !empty($results['brands'])) {
            // If we found brands, show fewer products to prioritize brands
            $productLimit = max(3, $limit - count($results['brands']));
        }
        
        // Search products
        $products = $productModel->searchProducts($query, [], $productLimit, 0);
        
        // If offers_only is requested, filter products with offers
        if ($offersOnly) {
            $products = array_filter($products, function($product) {
                return isset($product['compare_price']) && 
                       $product['compare_price'] > 0 && 
                       $product['compare_price'] > $product['price'];
            });
            $products = array_values($products); // Re-index array
        }
        
        // Format products
        $results['products'] = [];
        foreach ($products as $product) {
            // Construir la ruta completa de la imagen
            $imagePath = null;
            if (!empty($product['main_image'])) {
                // Si la imagen ya tiene una ruta completa, usarla tal como está
                if (strpos($product['main_image'], 'http') === 0 || strpos($product['main_image'], '/') === 0) {
                    $imagePath = $product['main_image'];
                } else {
                    // Construir la ruta completa para imágenes en uploads/products/
                    $imagePath = 'uploads/products/' . $product['main_image'];
                }
                
                // Verificar si el archivo existe
                $fullPath = '../' . $imagePath;
                if (!file_exists($fullPath)) {
                    // Si no existe, usar placeholder
                    $imagePath = 'assets/images/placeholder.svg';
                }
            } else {
                // Si no hay imagen, usar placeholder
                $imagePath = 'assets/images/placeholder.svg';
            }
            
            $results['products'][] = [
                'id' => $product['id'],
                'name' => $product['name'],
                'price' => $product['price'],
                'compare_price' => $product['compare_price'] ?? null,
                'sale_price' => $product['sale_price'] ?? null,
                'image' => $imagePath,
                'brand_name' => $product['brand_name'] ?? '',
                'category_name' => $product['category_name'] ?? '',
                'url' => 'product.php?id=' . $product['id']
            ];
        }
    }
    
    if ($type === 'all' || $type === 'categories') {
        // Search categories
        $categoryLimit = $type === 'categories' ? $limit : 2;
        $categories = $categoryModel->searchCategories($query, $categoryLimit);
        
        // Format categories
        $results['categories'] = [];
        foreach ($categories as $category) {
            $results['categories'][] = [
                'id' => $category['id'],
                'name' => $category['name'],
                'icon' => $category['icon'] ?? 'fas fa-tag',
                'url' => 'categoria.php?categoria=' . urlencode($category['slug'] ?? $category['name'])
            ];
        }
    }
    
    // Add total count prioritizing brands
    $totalBrands = isset($results['brands']) ? count($results['brands']) : 0;
    $totalProducts = isset($results['products']) ? count($results['products']) : 0;
    $totalCategories = isset($results['categories']) ? count($results['categories']) : 0;
    
    $results['total'] = $totalBrands + $totalProducts + $totalCategories;
    $results['has_brands'] = $totalBrands > 0;
    
    echo json_encode($results);
    
} catch (Exception $e) {
    error_log("Error in search API: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'error' => 'Server error',
        'products' => [],
        'brands' => [],
        'categories' => [],
        'total' => 0
    ]);
}
?>
