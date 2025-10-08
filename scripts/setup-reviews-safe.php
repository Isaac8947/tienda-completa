<?php
require_once '../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    echo "Verificando productos existentes...\n";
    
    $stmt = $db->query("SELECT id, name FROM products LIMIT 10");
    $products = $stmt->fetchAll();
    
    if (empty($products)) {
        echo "❌ No se encontraron productos en la base de datos.\n";
        echo "💡 Por favor, agrega algunos productos primero.\n";
    } else {
        echo "✅ Productos encontrados:\n";
        foreach ($products as $product) {
            echo "  - ID: {$product['id']} - {$product['name']}\n";
        }
        
        // Crear tabla sin datos de ejemplo si no hay productos suficientes
        echo "\nCreando tabla de reviews...\n";
        
        $sql = "CREATE TABLE IF NOT EXISTS `reviews` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `product_id` int(11) NOT NULL,
          `customer_id` int(11) DEFAULT NULL,
          `customer_name` varchar(255) NOT NULL,
          `customer_email` varchar(255) NOT NULL,
          `rating` tinyint(1) NOT NULL CHECK (rating >= 1 AND rating <= 5),
          `title` varchar(255) DEFAULT NULL,
          `comment` text NOT NULL,
          `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
          `verified_purchase` tinyint(1) NOT NULL DEFAULT 0,
          `helpful_votes` int(11) NOT NULL DEFAULT 0,
          `total_votes` int(11) NOT NULL DEFAULT 0,
          `reply_text` text DEFAULT NULL,
          `reply_date` timestamp NULL DEFAULT NULL,
          `admin_id` int(11) DEFAULT NULL,
          `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
          `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
          PRIMARY KEY (`id`),
          KEY `idx_product_id` (`product_id`),
          KEY `idx_customer_id` (`customer_id`),
          KEY `idx_status` (`status`),
          KEY `idx_rating` (`rating`),
          KEY `idx_created_at` (`created_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $db->exec($sql);
        echo "✅ Tabla 'reviews' creada exitosamente.\n";
        
        // Insertar datos de ejemplo solo si hay productos
        if (count($products) >= 2) {
            $firstProductId = $products[0]['id'];
            $secondProductId = $products[1]['id'];
            
            $insertSql = "INSERT INTO `reviews` (`product_id`, `customer_id`, `customer_name`, `customer_email`, `rating`, `title`, `comment`, `status`, `verified_purchase`, `helpful_votes`, `total_votes`) VALUES
            ($firstProductId, NULL, 'María González', 'maria@ejemplo.com', 5, 'Excelente producto', 'Me encanta este producto, la calidad es increíble y el resultado es perfecto. Lo recomiendo 100%.', 'approved', 1, 15, 18),
            ($firstProductId, NULL, 'Ana López', 'ana@ejemplo.com', 4, 'Muy bueno', 'Buen producto, aunque el precio es un poco alto. La calidad lo justifica.', 'approved', 1, 8, 10),
            ($secondProductId, NULL, 'Carmen Ruiz', 'carmen@ejemplo.com', 5, 'Perfecto', 'Exactamente lo que esperaba. Llegó rápido y en perfectas condiciones.', 'approved', 1, 12, 12),
            ($firstProductId, NULL, 'Laura Martín', 'laura@ejemplo.com', 3, 'Regular', 'El producto está bien pero esperaba más por el precio que tiene.', 'pending', 0, 2, 5),
            ($secondProductId, NULL, 'Isabel Jiménez', 'isabel@ejemplo.com', 5, 'Increíble', 'Este es mi producto favorito de maquillaje. La cobertura es perfecta y dura todo el día.', 'approved', 1, 25, 27),
            ($firstProductId, NULL, 'Elena Sánchez', 'elena@ejemplo.com', 5, 'Recomendado', 'Lo compré por las reseñas y no me arrepiento. Es fantástico.', 'pending', 1, 0, 0)";
            
            $db->exec($insertSql);
            echo "✅ Datos de ejemplo insertados.\n";
        }
        
        echo "\n🎉 Setup de reviews completado!\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
