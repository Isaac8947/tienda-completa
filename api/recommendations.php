<?php
session_start();
require_once '../config/database.php';
require_once '../models/Product.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

try {
    $database = new Database();
    $db = $database->getConnection();
    $productModel = new Product();
    
    $product_id = isset($_GET['product_id']) ? intval($_GET['product_id']) : 0;
    $type = isset($_GET['type']) ? $_GET['type'] : 'category';
    
    if (!$product_id) {
        echo json_encode(['success' => false, 'message' => 'Product ID required']);
        exit;
    }
    
    // Get current product info
    $currentProduct = $productModel->getProductWithDetails($product_id);
    if (!$currentProduct) {
        echo json_encode(['success' => false, 'message' => 'Product not found']);
        exit;
    }
    
    $products = [];
    $limit = 8;
    
    switch ($type) {
        case 'category':
            // Products from same category
            $query = "SELECT p.*, 
                        COALESCE(AVG(r.rating), 0) as average_rating,
                        COUNT(r.id) as total_reviews,
                        c.name as category_name,
                        b.name as brand_name
                      FROM products p
                      LEFT JOIN reviews r ON p.id = r.product_id
                      LEFT JOIN categories c ON p.category_id = c.id
                      LEFT JOIN brands b ON p.brand_id = b.id
                      WHERE p.category_id = ? 
                        AND p.id != ? 
                        AND p.status = 'active'
                        AND p.visibility = 'visible'
                      GROUP BY p.id
                      ORDER BY p.is_featured DESC, p.views DESC
                      LIMIT ?";
            $stmt = $db->prepare($query);
            $stmt->execute([$currentProduct['category_id'], $product_id, $limit]);
            break;
            
        case 'brand':
            // Products from same brand
            if (!$currentProduct['brand_id']) {
                $products = [];
                break;
            }
            $query = "SELECT p.*, 
                        COALESCE(AVG(r.rating), 0) as average_rating,
                        COUNT(r.id) as total_reviews,
                        c.name as category_name,
                        b.name as brand_name
                      FROM products p
                      LEFT JOIN reviews r ON p.id = r.product_id
                      LEFT JOIN categories c ON p.category_id = c.id
                      LEFT JOIN brands b ON p.brand_id = b.id
                      WHERE p.brand_id = ? 
                        AND p.id != ? 
                        AND p.status = 'active'
                        AND p.visibility = 'visible'
                      GROUP BY p.id
                      ORDER BY p.is_featured DESC, p.views DESC
                      LIMIT ?";
            $stmt = $db->prepare($query);
            $stmt->execute([$currentProduct['brand_id'], $product_id, $limit]);
            break;
            
        case 'popular':
            // Most popular products (by views and ratings)
            $query = "SELECT p.*, 
                        COALESCE(AVG(r.rating), 0) as average_rating,
                        COUNT(r.id) as total_reviews,
                        c.name as category_name,
                        b.name as brand_name
                      FROM products p
                      LEFT JOIN reviews r ON p.id = r.product_id
                      LEFT JOIN categories c ON p.category_id = c.id
                      LEFT JOIN brands b ON p.brand_id = b.id
                      WHERE p.id != ? 
                        AND p.status = 'active'
                        AND p.visibility = 'visible'
                      GROUP BY p.id
                      ORDER BY (p.views + COUNT(r.id) * 10) DESC, p.is_featured DESC
                      LIMIT ?";
            $stmt = $db->prepare($query);
            $stmt->execute([$product_id, $limit]);
            break;
            
        case 'price':
            // Products with similar price range (+/- 30%)
            $minPrice = $currentProduct['price'] * 0.7;
            $maxPrice = $currentProduct['price'] * 1.3;
            
            $query = "SELECT p.*, 
                        COALESCE(AVG(r.rating), 0) as average_rating,
                        COUNT(r.id) as total_reviews,
                        c.name as category_name,
                        b.name as brand_name
                      FROM products p
                      LEFT JOIN reviews r ON p.id = r.product_id
                      LEFT JOIN categories c ON p.category_id = c.id
                      LEFT JOIN brands b ON p.brand_id = b.id
                      WHERE p.price BETWEEN ? AND ? 
                        AND p.id != ? 
                        AND p.status = 'active'
                        AND p.visibility = 'visible'
                      GROUP BY p.id
                      ORDER BY ABS(p.price - ?) ASC, p.is_featured DESC
                      LIMIT ?";
            $stmt = $db->prepare($query);
            $stmt->execute([$minPrice, $maxPrice, $product_id, $currentProduct['price'], $limit]);
            break;
            
        default:
            $products = [];
    }
    
    if (isset($stmt)) {
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Format products for response
        foreach ($products as &$product) {
            // Ensure numeric values
            $product['average_rating'] = floatval($product['average_rating']);
            $product['total_reviews'] = intval($product['total_reviews']);
            $product['price'] = floatval($product['price']);
            
            // Handle main image
            if (empty($product['main_image'])) {
                $product['main_image'] = 'assets/images/placeholder-product.jpg';
            }
        }
    }
    
    // Update product views (for analytics)
    if (!empty($products)) {
        $updateQuery = "UPDATE products SET views = views + 1 WHERE id = ?";
        $updateStmt = $db->prepare($updateQuery);
        $updateStmt->execute([$product_id]);
    }
    
    echo json_encode([
        'success' => true,
        'products' => $products,
        'type' => $type,
        'count' => count($products)
    ]);
    
} catch (Exception $e) {
    error_log("Recommendations API Error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error al cargar recomendaciones'
    ]);
}
?>
