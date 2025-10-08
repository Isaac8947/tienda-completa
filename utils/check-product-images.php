<?php
require_once 'config/database.php';

$database = new Database();
$pdo = $database->getConnection();

echo "=== PRODUCTOS Y SUS IMÁGENES ===\n\n";

$stmt = $pdo->query('SELECT id, name, main_image FROM products ORDER BY id');
while ($row = $stmt->fetch()) {
    echo "ID: {$row['id']}\n";
    echo "Nombre: {$row['name']}\n";
    echo "Imagen: " . ($row['main_image'] ?: 'NULL') . "\n";
    
    // Verificar si el archivo existe
    if ($row['main_image']) {
        $imagePath = "uploads/products/{$row['main_image']}";
        if (file_exists($imagePath)) {
            echo "✅ Archivo existe: {$imagePath}\n";
        } else {
            echo "❌ Archivo NO existe: {$imagePath}\n";
        }
    }
    echo "---\n\n";
}

echo "\n=== ARCHIVOS EN uploads/products/ ===\n";
$uploadDir = 'uploads/products/';
if (is_dir($uploadDir)) {
    $files = scandir($uploadDir);
    foreach ($files as $file) {
        if ($file !== '.' && $file !== '..') {
            echo "📁 {$file}\n";
        }
    }
} else {
    echo "❌ Directorio uploads/products/ no existe\n";
}
?>