<?php
require_once 'config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    echo "=== ACTUALIZANDO IMÁGENES DE PRODUCTOS ===\n";
    
    // Mapeo de productos con sus imágenes
    $productImages = [
        'Samsung Galaxy S24 Ultra' => 'samsung-galaxy-s24-ultra.svg',
        'MacBook Air M3' => 'macbook-air-m3.svg', 
        'AirPods Pro 3' => 'airpods-pro-3.svg'
    ];
    
    foreach ($productImages as $productName => $imageName) {
        $stmt = $db->prepare("UPDATE products SET main_image = ? WHERE name LIKE ?");
        $stmt->execute([$imageName, "%$productName%"]);
        
        if ($stmt->rowCount() > 0) {
            echo "✅ Actualizado: $productName -> $imageName\n";
        } else {
            echo "⚠️  No se encontró: $productName\n";
        }
    }
    
    // Mostrar productos actualizados
    echo "\n=== PRODUCTOS CON IMÁGENES ===\n";
    $stmt = $db->prepare("SELECT id, name, main_image FROM products WHERE main_image IS NOT NULL AND main_image != '' LIMIT 10");
    $stmt->execute();
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($products as $product) {
        echo "ID: {$product['id']}, Nombre: {$product['name']}, Imagen: {$product['main_image']}\n";
        
        $imagePath = "uploads/products/{$product['main_image']}";
        if (file_exists($imagePath)) {
            echo "  ✅ Imagen existe\n";
        } else {
            echo "  ❌ Imagen NO existe\n";
        }
    }
    
    echo "\n✅ Actualización completada\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>