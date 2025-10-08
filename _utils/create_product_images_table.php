<?php
require_once 'config/database.php';

try {
    $db = new Database();
    $pdo = $db->getConnection();
    
    echo "=== CREANDO TABLA PRODUCT_IMAGES ===\n";
    
    $sql = "CREATE TABLE IF NOT EXISTS product_images (
        id INT(11) NOT NULL AUTO_INCREMENT,
        product_id INT(11) NOT NULL,
        image_path VARCHAR(255) NOT NULL,
        alt_text VARCHAR(255) DEFAULT NULL,
        sort_order INT(11) DEFAULT 0,
        is_primary TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY idx_product_id (product_id),
        KEY idx_sort_order (sort_order),
        FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $pdo->exec($sql);
    echo "✅ Tabla product_images creada exitosamente\n";
    
    echo "\n=== VERIFICANDO ESTRUCTURA ===\n";
    $stmt = $pdo->query('DESCRIBE product_images');
    while($row = $stmt->fetch()) {
        echo sprintf("%-15s %-30s %-10s %-20s\n", 
            $row['Field'], 
            $row['Type'], 
            $row['Null'], 
            $row['Default'] ?? 'NULL'
        );
    }
    
    echo "\n=== MIGRANDO IMÁGENES EXISTENTES ===\n";
    
    // Obtener productos con main_image
    $stmt = $pdo->query("SELECT id, main_image, gallery FROM products WHERE main_image IS NOT NULL OR gallery IS NOT NULL");
    $products = $stmt->fetchAll();
    
    foreach ($products as $product) {
        $productId = $product['id'];
        $sortOrder = 0;
        
        // Migrar main_image como imagen principal
        if (!empty($product['main_image'])) {
            $stmt = $pdo->prepare("INSERT INTO product_images (product_id, image_path, alt_text, sort_order, is_primary) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$productId, $product['main_image'], 'Imagen principal', $sortOrder++, 1]);
            echo "   Migrada imagen principal para producto ID: $productId\n";
        }
        
        // Migrar gallery images
        if (!empty($product['gallery'])) {
            $gallery = json_decode($product['gallery'], true);
            if (is_array($gallery)) {
                foreach ($gallery as $imagePath) {
                    if (!empty($imagePath)) {
                        $stmt = $pdo->prepare("INSERT INTO product_images (product_id, image_path, alt_text, sort_order, is_primary) VALUES (?, ?, ?, ?, ?)");
                        $stmt->execute([$productId, $imagePath, 'Imagen adicional', $sortOrder++, 0]);
                        echo "   Migrada imagen de galería para producto ID: $productId\n";
                    }
                }
            }
        }
    }
    
    echo "\n✅ Migración completada exitosamente\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
