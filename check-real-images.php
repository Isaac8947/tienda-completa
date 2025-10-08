<?php
require_once 'config/database.php';

$database = new Database();
$pdo = $database->getConnection();

echo "=== CONTENIDO DE TABLA PRODUCT_IMAGES ===\n\n";

$stmt = $pdo->query('SELECT * FROM product_images ORDER BY product_id, sort_order');
while ($row = $stmt->fetch()) {
    echo "ID: {$row['id']} | Product ID: {$row['product_id']} | Image: {$row['image_path']} | Primary: {$row['is_primary']} | Sort: {$row['sort_order']}\n";
    
    // Verificar si el archivo existe
    $imagePath = "uploads/products/{$row['image_path']}";
    if (file_exists($imagePath)) {
        echo "  ✅ Archivo existe\n";
    } else {
        echo "  ❌ Archivo NO existe\n";
    }
}

echo "\n=== PRODUCTOS Y SUS IMÁGENES REALES ===\n\n";

$stmt = $pdo->query('
    SELECT p.id, p.name, p.main_image as current_main_image, 
           pi.image_path as real_image, pi.is_primary
    FROM products p 
    LEFT JOIN product_images pi ON p.id = pi.product_id 
    WHERE pi.is_primary = 1 OR pi.id IS NULL
    ORDER BY p.id
');

while ($row = $stmt->fetch()) {
    echo "Producto ID: {$row['id']}\n";
    echo "Nombre: {$row['name']}\n";
    echo "Imagen actual (main_image): " . ($row['current_main_image'] ?: 'NULL') . "\n";
    echo "Imagen real (product_images): " . ($row['real_image'] ?: 'NULL') . "\n";
    echo "Es primaria: " . ($row['is_primary'] ? 'SÍ' : 'NO') . "\n";
    echo "---\n";
}
?>