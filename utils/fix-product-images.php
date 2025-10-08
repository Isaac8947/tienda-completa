<?php
require_once 'config/database.php';

$database = new Database();
$pdo = $database->getConnection();

echo "=== CORRIGIENDO RUTAS DE IMÁGENES Y ACTUALIZANDO MAIN_IMAGE ===\n\n";

// Primero, corregir las rutas en product_images que tienen el prefijo duplicado
$stmt = $pdo->query("SELECT * FROM product_images WHERE image_path LIKE 'uploads/products/%'");
while ($row = $stmt->fetch()) {
    $oldPath = $row['image_path'];
    $newPath = str_replace('uploads/products/', '', $oldPath);
    
    echo "Producto ID: {$row['product_id']}\n";
    echo "Ruta antigua: {$oldPath}\n";
    echo "Ruta nueva: {$newPath}\n";
    
    // Verificar si el archivo existe con la nueva ruta
    $fullPath = "uploads/products/{$newPath}";
    if (file_exists($fullPath)) {
        echo "✅ Archivo existe con nueva ruta\n";
        
        // Actualizar la ruta en la base de datos
        $updateStmt = $pdo->prepare("UPDATE product_images SET image_path = ? WHERE id = ?");
        $updateStmt->execute([$newPath, $row['id']]);
        echo "✅ Ruta actualizada en BD\n";
    } else {
        echo "❌ Archivo no existe: {$fullPath}\n";
    }
    echo "---\n";
}

echo "\n=== ACTUALIZANDO MAIN_IMAGE DE PRODUCTOS CON IMÁGENES REALES ===\n\n";

// Actualizar main_image de productos que tienen imágenes primarias
$stmt = $pdo->query("
    SELECT p.id, p.name, p.main_image, pi.image_path
    FROM products p
    INNER JOIN product_images pi ON p.id = pi.product_id
    WHERE pi.is_primary = 1
");

while ($row = $stmt->fetch()) {
    echo "Producto: {$row['name']} (ID: {$row['id']})\n";
    echo "Main image actual: {$row['main_image']}\n";
    echo "Imagen real: {$row['image_path']}\n";
    
    // Actualizar main_image con la imagen real
    $updateStmt = $pdo->prepare("UPDATE products SET main_image = ? WHERE id = ?");
    $updateStmt->execute([$row['image_path'], $row['id']]);
    echo "✅ main_image actualizado\n";
    echo "---\n";
}

echo "\n=== ELIMINANDO SVGs DE PRUEBA PARA PRODUCTOS QUE NO TIENEN IMÁGENES REALES ===\n\n";

// Para productos sin imágenes reales, limpiar main_image
$stmt = $pdo->query("
    SELECT p.id, p.name, p.main_image
    FROM products p
    LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
    WHERE pi.id IS NULL AND p.main_image IN ('samsung-galaxy-s24-ultra.svg', 'macbook-air-m3.svg', 'airpods-pro-3.svg')
");

while ($row = $stmt->fetch()) {
    echo "Producto sin imagen real: {$row['name']} (ID: {$row['id']})\n";
    echo "Limpiando main_image: {$row['main_image']}\n";
    
    // Limpiar main_image
    $updateStmt = $pdo->prepare("UPDATE products SET main_image = NULL WHERE id = ?");
    $updateStmt->execute([$row['id']]);
    echo "✅ main_image limpiado\n";
    echo "---\n";
}

echo "\n✅ PROCESO COMPLETADO\n";
?>