<?php
echo "=== PRUEBA DE FUNCIONALIDADES ===\n";

// Test 1: Verificar que las imágenes se construyen correctamente
echo "1. Probando construcción de URLs de imágenes:\n";

require_once 'config/global-settings.php';
require_once 'config/database.php';
require_once 'models/Product.php';

$productModel = new Product();
$products = $productModel->getProductsWithFilters([], 2, 0);

foreach ($products as $product) {
    echo "Producto: " . $product['name'] . "\n";
    echo "main_image field: " . ($product['main_image'] ?? 'NULL') . "\n";
    
    // Test como está en catalogo.php ahora
    $productImage = !empty($product['main_image']) ? BASE_URL . '/uploads/products/' . $product['main_image'] : BASE_URL . '/assets/images/placeholder-product.svg';
    echo "URL construida: " . $productImage . "\n";
    
    // Verificar si el archivo existe
    $localPath = 'uploads/products/' . $product['main_image'];
    if (file_exists($localPath)) {
        echo "✓ Archivo existe localmente\n";
    } else {
        echo "✗ Archivo NO existe: " . $localPath . "\n";
    }
    echo "---\n";
}

// Test 2: Verificar redirección a product.php
echo "\n2. Probando acceso a product.php:\n";
if (file_exists('product.php')) {
    echo "✓ Archivo product.php existe\n";
    
    // Verificar sintaxis PHP
    $output = [];
    $return_var = 0;
    exec('php -l product.php 2>&1', $output, $return_var);
    
    if ($return_var === 0) {
        echo "✓ Sintaxis PHP correcta\n";
    } else {
        echo "✗ Error de sintaxis en product.php:\n";
        echo implode("\n", $output) . "\n";
    }
} else {
    echo "✗ Archivo product.php NO existe\n";
}

// Test 3: Verificar wishlist-toggle.php
echo "\n3. Probando acceso a wishlist-toggle.php:\n";
if (file_exists('wishlist-toggle.php')) {
    echo "✓ Archivo wishlist-toggle.php existe\n";
    
    // Verificar sintaxis PHP
    $output = [];
    $return_var = 0;
    exec('php -l wishlist-toggle.php 2>&1', $output, $return_var);
    
    if ($return_var === 0) {
        echo "✓ Sintaxis PHP correcta\n";
    } else {
        echo "✗ Error de sintaxis en wishlist-toggle.php:\n";
        echo implode("\n", $output) . "\n";
    }
} else {
    echo "✗ Archivo wishlist-toggle.php NO existe\n";
}

echo "\n=== FIN DE PRUEBAS ===\n";
?>
