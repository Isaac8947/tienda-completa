<?php
require_once 'config/config.php';
require_once 'models/Brand.php';

$brandModel = new Brand();

// Obtener todas las marcas activas
$brands = $brandModel->getActive();

echo "=== MARCAS ACTIVAS EN LA BASE DE DATOS ===\n";
foreach ($brands as $brand) {
    echo "ID: {$brand['id']}, Nombre: {$brand['name']}, Activa: {$brand['is_active']}\n";
}

echo "\n=== PRUEBA DE BÚSQUEDA ===\n";
$searchResults = $brandModel->searchBrands('a', 10);
echo "Buscando marcas con 'a': " . count($searchResults) . " resultados\n";
foreach ($searchResults as $brand) {
    echo "- {$brand['name']} (Productos: {$brand['product_count']})\n";
}
?>
