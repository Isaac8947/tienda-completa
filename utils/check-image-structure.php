<?php
require_once 'config/database.php';

$database = new Database();
$pdo = $database->getConnection();

echo "=== TABLAS RELACIONADAS CON IMÁGENES ===\n\n";

$tables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
foreach($tables as $table) {
    if(strpos($table, 'image') !== false || strpos($table, 'media') !== false || strpos($table, 'photo') !== false) {
        echo "Tabla: {$table}\n";
        $columns = $pdo->query("DESCRIBE {$table}")->fetchAll();
        foreach($columns as $col) {
            echo "  - {$col['Field']} ({$col['Type']})\n";
        }
        echo "\n";
    }
}

echo "=== ESTRUCTURA DE TABLA PRODUCTS (CAMPOS DE IMAGEN) ===\n\n";
$columns = $pdo->query('DESCRIBE products')->fetchAll();
foreach($columns as $col) {
    if(strpos($col['Field'], 'image') !== false || strpos($col['Field'], 'photo') !== false || strpos($col['Field'], 'media') !== false) {
        echo "Campo: {$col['Field']} - Tipo: {$col['Type']} - Null: {$col['Null']} - Default: {$col['Default']}\n";
    }
}

echo "\n=== SAMPLE DE PRODUCTOS CON CAMPOS DE IMAGEN ===\n\n";
$stmt = $pdo->query('SELECT id, name, main_image, image, images FROM products LIMIT 5');
while ($row = $stmt->fetch()) {
    echo "ID: {$row['id']}\n";
    echo "Nombre: {$row['name']}\n";
    echo "main_image: " . ($row['main_image'] ?: 'NULL') . "\n";
    echo "image: " . ($row['image'] ?: 'NULL') . "\n";
    echo "images: " . ($row['images'] ?: 'NULL') . "\n";
    echo "---\n";
}
?>