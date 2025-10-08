<?php
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../models/Product.php';

$productModel = new Product();
$products = $productModel->findAll([], ['limit' => 5]);

echo "Verificando rutas de imágenes en productos:\n\n";

foreach ($products as $product) {
    echo "ID: " . $product['id'] . "\n";
    echo "Nombre: " . $product['name'] . "\n";
    echo "main_image: '" . $product['main_image'] . "'\n";
    echo "Ruta construida: '" . BASE_URL . '/' . $product['main_image'] . "'\n";
    echo "Archivo existe: " . (file_exists('../' . $product['main_image']) ? "SÍ" : "NO") . "\n";
    echo "---\n";
}
?>
