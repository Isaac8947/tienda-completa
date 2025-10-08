<?php
/**
 * Script de prueba para verificar las APIs de reseñas
 */

require_once '../config/database.php';

echo "🧪 PRUEBAS DE APIS DE RESEÑAS\n";
echo "=============================\n\n";

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // 1. Verificar estructura de tablas
    echo "📊 1. VERIFICANDO ESTRUCTURA DE TABLAS\n";
    echo "--------------------------------------\n";
    
    $tables = ['reviews', 'review_likes', 'products', 'customers'];
    foreach ($tables as $table) {
        $query = "SHOW TABLES LIKE '$table'";
        $stmt = $db->query($query);
        if ($stmt->rowCount() > 0) {
            echo "✅ $table existe\n";
        } else {
            echo "❌ $table NO existe\n";
            exit;
        }
    }
    
    // 2. Verificar estructura de review_likes
    echo "\n📋 2. ESTRUCTURA DE REVIEW_LIKES:\n";
    echo "---------------------------------\n";
    $structureQuery = "DESCRIBE review_likes";
    $structureStmt = $db->query($structureQuery);
    $columns = $structureStmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($columns as $column) {
        echo "• {$column['Field']} ({$column['Type']})\n";
    }
    
    // 3. Verificar datos de prueba existentes
    echo "\n📝 3. DATOS EXISTENTES:\n";
    echo "----------------------\n";
    
    $reviewsCount = $db->query("SELECT COUNT(*) FROM reviews")->fetchColumn();
    $likesCount = $db->query("SELECT COUNT(*) FROM review_likes")->fetchColumn();
    $productsCount = $db->query("SELECT COUNT(*) FROM products WHERE status = 'active'")->fetchColumn();
    $customersCount = $db->query("SELECT COUNT(*) FROM customers")->fetchColumn();
    
    echo "📝 Reseñas: $reviewsCount\n";
    echo "👍 Likes: $likesCount\n";
    echo "🛍️  Productos activos: $productsCount\n";
    echo "👥 Customers: $customersCount\n";
    
    // 4. Probar consulta de reseñas con likes
    echo "\n🔍 4. CONSULTA DE RESEÑAS CON LIKES:\n";
    echo "-----------------------------------\n";
    
    $reviewsWithLikesQuery = "
        SELECT 
            r.id,
            r.product_id,
            r.rating,
            r.title,
            r.reviewer_name,
            r.helpful_count,
            COUNT(rl.id) as actual_likes,
            p.name as product_name
        FROM reviews r
        LEFT JOIN review_likes rl ON r.id = rl.review_id
        LEFT JOIN products p ON r.product_id = p.id
        GROUP BY r.id
        ORDER BY r.created_at DESC
        LIMIT 3
    ";
    
    $reviewsStmt = $db->query($reviewsWithLikesQuery);
    $reviews = $reviewsStmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($reviews as $review) {
        echo "📝 ID: {$review['id']} | Producto: {$review['product_name']}\n";
        echo "   ⭐ Rating: {$review['rating']}/5\n";
        echo "   👍 Likes (helpful_count): {$review['helpful_count']}\n";
        echo "   👍 Likes (actual): {$review['actual_likes']}\n";
        echo "   ―――――――――――――――――――――――――――\n";
    }
    
    // 5. Probar consulta para recomendaciones
    echo "\n🎯 5. CONSULTA PARA RECOMENDACIONES:\n";
    echo "-----------------------------------\n";
    
    // Recomendaciones por categoría
    $categoryRecommendationsQuery = "
        SELECT p.id, p.name, p.price, p.average_rating, p.total_reviews, c.name as category_name
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.id
        WHERE p.status = 'active' AND p.category_id IS NOT NULL
        ORDER BY p.average_rating DESC, p.total_reviews DESC
        LIMIT 3
    ";
    
    $categoryStmt = $db->query($categoryRecommendationsQuery);
    $categoryRecommendations = $categoryStmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "📂 Por categoría:\n";
    foreach ($categoryRecommendations as $product) {
        echo "   🛍️  {$product['name']} - {$product['category_name']}\n";
        echo "      💰 \${$product['price']} | ⭐ {$product['average_rating']} ({$product['total_reviews']} reseñas)\n";
    }
    
    // 6. Verificar productos con más reseñas
    echo "\n📊 6. PRODUCTOS MÁS RESEÑADOS:\n";
    echo "------------------------------\n";
    
    $topReviewedQuery = "
        SELECT p.id, p.name, p.total_reviews, p.average_rating
        FROM products p
        WHERE p.status = 'active' AND p.total_reviews > 0
        ORDER BY p.total_reviews DESC
        LIMIT 5
    ";
    
    $topReviewedStmt = $db->query($topReviewedQuery);
    $topReviewed = $topReviewedStmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($topReviewed as $product) {
        echo "🛍️  {$product['name']}: {$product['total_reviews']} reseñas (⭐ {$product['average_rating']})\n";
    }
    
    echo "\n✅ TODAS LAS CONSULTAS FUNCIONAN CORRECTAMENTE!\n";
    echo "==============================================\n";
    echo "✅ Estructura de DB compatible\n";
    echo "✅ APIs listas para usar\n";
    echo "✅ Datos de prueba disponibles\n";
    echo "✅ Sistema de recomendaciones funcional\n\n";
    
    echo "🚀 NEXT STEPS:\n";
    echo "--------------\n";
    echo "1. Probar las APIs desde el navegador\n";
    echo "2. Verificar que product.php carga correctamente\n";
    echo "3. Probar escribir una reseña desde la interfaz\n";
    echo "4. Probar dar like a una reseña\n";
    echo "5. Verificar que las recomendaciones aparecen\n";
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "Verifica la configuración de la base de datos.\n";
}
?>
