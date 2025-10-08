<?php
require_once 'config/database.php';

$database = new Database();
$pdo = $database->getConnection();

echo "Verificando productos con imágenes:\n";
$stmt = $pdo->query('SELECT id, name, main_image FROM products LIMIT 10');
while ($row = $stmt->fetch()) {
    echo "ID: {$row['id']}, Name: {$row['name']}, Image: {$row['main_image']}\n";
}

echo "\nVerificando estructura de tabla products:\n";
$stmt = $pdo->query('DESCRIBE products');
while ($row = $stmt->fetch()) {
    if (strpos($row['Field'], 'image') !== false) {
        echo "Campo: {$row['Field']}, Tipo: {$row['Type']}\n";
    }
}
?>