<?php
require_once '../config/database.php';

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    echo "Creando tabla de items de órdenes...\n";
    
    // Crear tabla order_items si no existe
    $sql = "CREATE TABLE IF NOT EXISTS order_items (
        id INT AUTO_INCREMENT PRIMARY KEY,
        order_id INT NOT NULL,
        product_id INT NOT NULL,
        quantity INT NOT NULL DEFAULT 1,
        price DECIMAL(10,2) NOT NULL,
        total DECIMAL(10,2) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
        FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $conn->exec($sql);
    echo "✓ Tabla 'order_items' creada exitosamente.\n";
    
    // Insertar algunos datos de ejemplo para testing
    echo "Insertando datos de ejemplo...\n";
    
    // Verificar si ya hay datos
    $stmt = $conn->query("SELECT COUNT(*) as count FROM order_items");
    $count = $stmt->fetch()['count'];
    
    if ($count == 0) {
        // Obtener algunas órdenes y productos existentes
        $stmt = $conn->query("SELECT id FROM orders LIMIT 5");
        $orders = $stmt->fetchAll();
        
        $stmt = $conn->query("SELECT id, price FROM products WHERE status = 'active' LIMIT 10");
        $products = $stmt->fetchAll();
        
        if (!empty($orders) && !empty($products)) {
            foreach ($orders as $order) {
                // Agregar 1-3 productos por orden
                $itemCount = rand(1, 3);
                $usedProducts = [];
                
                for ($i = 0; $i < $itemCount; $i++) {
                    $product = $products[array_rand($products)];
                    
                    // Evitar duplicados en la misma orden
                    if (in_array($product['id'], $usedProducts)) {
                        continue;
                    }
                    $usedProducts[] = $product['id'];
                    
                    $quantity = rand(1, 3);
                    $price = $product['price'];
                    $total = $quantity * $price;
                    
                    $stmt = $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity, price, total) VALUES (?, ?, ?, ?, ?)");
                    $stmt->execute([$order['id'], $product['id'], $quantity, $price, $total]);
                }
            }
            echo "✓ Datos de ejemplo insertados.\n";
        }
    } else {
        echo "✓ La tabla ya contiene datos.\n";
    }
    
    echo "\n🎉 Configuración de order_items completada!\n";
    
} catch (PDOException $e) {
    echo "❌ Error al configurar order_items: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "❌ Error general: " . $e->getMessage() . "\n";
}
?>
