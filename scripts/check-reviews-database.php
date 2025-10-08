<?php
/**
 * Script para verificar la estructura de la base de datos de reseñas
 */

require_once '../config/database.php';

echo "🔍 VERIFICANDO ESTRUCTURA DE BASE DE DATOS\n";
echo "==========================================\n\n";

try {
    $database = new Database();
    $db = $database->getConnection();
    
    echo "📊 Conectado a la base de datos...\n\n";
    
    // 1. Verificar todas las tablas existentes
    echo "📋 TABLAS EXISTENTES:\n";
    echo "---------------------\n";
    $tablesQuery = "SHOW TABLES";
    $tablesStmt = $db->query($tablesQuery);
    $allTables = $tablesStmt->fetchAll(PDO::FETCH_COLUMN);
    
    foreach ($allTables as $table) {
        echo "📦 $table\n";
    }
    
    // 2. Verificar específicamente las tablas de reseñas
    echo "\n🔍 VERIFICANDO TABLAS DE RESEÑAS:\n";
    echo "=================================\n";
    
    $reviewTables = ['reviews', 'review_likes'];
    
    foreach ($reviewTables as $table) {
        if (in_array($table, $allTables)) {
            echo "✅ Tabla '$table' EXISTE\n";
            
            // Mostrar estructura de la tabla
            echo "   📋 Estructura:\n";
            $structureQuery = "DESCRIBE $table";
            $structureStmt = $db->query($structureQuery);
            $columns = $structureStmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($columns as $column) {
                echo "      • {$column['Field']} ({$column['Type']}) ";
                if ($column['Key'] === 'PRI') echo "[PRIMARY KEY] ";
                if ($column['Key'] === 'MUL') echo "[INDEX] ";
                if ($column['Null'] === 'NO') echo "[NOT NULL] ";
                if ($column['Default'] !== null) echo "[DEFAULT: {$column['Default']}] ";
                echo "\n";
            }
            
            // Contar registros
            $countQuery = "SELECT COUNT(*) FROM $table";
            $count = $db->query($countQuery)->fetchColumn();
            echo "   📊 Registros: $count\n\n";
            
        } else {
            echo "❌ Tabla '$table' NO EXISTE\n\n";
        }
    }
    
    // 3. Verificar tabla de productos (necesaria para reseñas)
    echo "🛍️ VERIFICANDO TABLA DE PRODUCTOS:\n";
    echo "==================================\n";
    
    if (in_array('products', $allTables)) {
        echo "✅ Tabla 'products' EXISTE\n";
        
        // Verificar columnas relacionadas con reseñas
        $productStructureQuery = "DESCRIBE products";
        $productStructure = $db->query($productStructureQuery)->fetchAll(PDO::FETCH_ASSOC);
        
        $reviewRelatedColumns = ['rating', 'review_count', 'views'];
        echo "   📋 Columnas relacionadas con reseñas:\n";
        
        foreach ($productStructure as $column) {
            if (in_array($column['Field'], $reviewRelatedColumns) || 
                strpos($column['Field'], 'rating') !== false ||
                strpos($column['Field'], 'review') !== false) {
                echo "      • {$column['Field']} ({$column['Type']}) ";
                if ($column['Default'] !== null) echo "[DEFAULT: {$column['Default']}] ";
                echo "\n";
            }
        }
        
        $productCount = $db->query("SELECT COUNT(*) FROM products")->fetchColumn();
        echo "   📊 Total productos: $productCount\n\n";
        
    } else {
        echo "❌ Tabla 'products' NO EXISTE\n\n";
    }
    
    // 4. Verificar tabla de customers (necesaria para reseñas)
    echo "👥 VERIFICANDO TABLA DE CUSTOMERS:\n";
    echo "=================================\n";
    
    if (in_array('customers', $allTables)) {
        echo "✅ Tabla 'customers' EXISTE\n";
        $customerCount = $db->query("SELECT COUNT(*) FROM customers")->fetchColumn();
        echo "   📊 Total customers: $customerCount\n\n";
    } else {
        echo "❌ Tabla 'customers' NO EXISTE\n\n";
    }
    
    // 5. Si existe tabla reviews, mostrar algunas reseñas de ejemplo
    if (in_array('reviews', $allTables)) {
        echo "📝 RESEÑAS EXISTENTES (últimas 5):\n";
        echo "=================================\n";
        
        $reviewsQuery = "
            SELECT r.*, p.name as product_name 
            FROM reviews r 
            LEFT JOIN products p ON r.product_id = p.id 
            ORDER BY r.created_at DESC 
            LIMIT 5
        ";
        
        $reviewsStmt = $db->query($reviewsQuery);
        $reviews = $reviewsStmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($reviews)) {
            echo "ℹ️  No hay reseñas en la base de datos\n";
        } else {
            foreach ($reviews as $review) {
                echo "   📝 ID: {$review['id']}\n";
                echo "   🛍️  Producto: " . ($review['product_name'] ?: 'ID ' . $review['product_id']) . "\n";
                echo "   ⭐ Rating: {$review['rating']}/5\n";
                echo "   💭 Comentario: " . substr($review['comment'], 0, 100) . "...\n";
                echo "   📅 Fecha: {$review['created_at']}\n";
                if (isset($review['likes'])) echo "   👍 Likes: {$review['likes']}\n";
                if (isset($review['dislikes'])) echo "   👎 Dislikes: {$review['dislikes']}\n";
                echo "   ―――――――――――――――――――――――――\n";
            }
        }
    }
    
    echo "\n🎯 RESUMEN:\n";
    echo "===========\n";
    echo "✅ Conexión a DB: OK\n";
    echo (in_array('reviews', $allTables) ? "✅" : "❌") . " Tabla reviews: " . (in_array('reviews', $allTables) ? "EXISTE" : "NO EXISTE") . "\n";
    echo (in_array('review_likes', $allTables) ? "✅" : "❌") . " Tabla review_likes: " . (in_array('review_likes', $allTables) ? "EXISTE" : "NO EXISTE") . "\n";
    echo (in_array('products', $allTables) ? "✅" : "❌") . " Tabla products: " . (in_array('products', $allTables) ? "EXISTE" : "NO EXISTE") . "\n";
    echo (in_array('customers', $allTables) ? "✅" : "❌") . " Tabla customers: " . (in_array('customers', $allTables) ? "EXISTE" : "NO EXISTE") . "\n";
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "Verifica la configuración de la base de datos.\n";
}
?>
