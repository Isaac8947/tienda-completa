<?php
/**
 * Script para agregar productos de prueba para el sistema de búsqueda
 */

require_once 'config/database.php';
require_once 'models/Product.php';
require_once 'models/Category.php';
require_once 'models/Brand.php';

try {
    $productModel = new Product();
    $categoryModel = new Category();
    $brandModel = new Brand();
    
    // Crear productos de prueba si no existen
    $testProducts = [
        [
            'name' => 'iPhone 15 Pro',
            'slug' => 'iphone-15-pro',
            'sku' => 'IPHONE-15-PRO',
            'description' => 'El nuevo iPhone 15 Pro con chip A17 Pro y cámara revolucionaria',
            'short_description' => 'Smartphone premium de Apple',
            'price' => 999.99,
            'category_id' => 44, // Smartphones
            'brand_id' => 7, // Apple
            'status' => 'active',
            'is_featured' => 1,
            'main_image' => 'assets/images/iphone-15-pro.jpg'
        ],
        [
            'name' => 'Samsung Galaxy S24 Ultra',
            'slug' => 'samsung-galaxy-s24-ultra',
            'sku' => 'GALAXY-S24-ULTRA',
            'description' => 'Galaxy S24 Ultra con S Pen integrado y cámara de 200MP',
            'short_description' => 'Smartphone Android premium',
            'price' => 899.99,
            'category_id' => 44, // Smartphones
            'brand_id' => 4, // Samsung
            'status' => 'active',
            'is_featured' => 1,
            'main_image' => 'assets/images/galaxy-s24-ultra.jpg'
        ],
        [
            'name' => 'MacBook Air M3',
            'slug' => 'macbook-air-m3',
            'sku' => 'MACBOOK-AIR-M3',
            'description' => 'La nueva MacBook Air con chip M3 más potente que nunca',
            'short_description' => 'Laptop ultraligera y potente',
            'price' => 1299.99,
            'category_id' => 45, // Laptops
            'brand_id' => 7, // Apple
            'status' => 'active',
            'is_featured' => 1,
            'main_image' => 'assets/images/macbook-air-m3.jpg'
        ],
        [
            'name' => 'AirPods Pro 3',
            'slug' => 'airpods-pro-3',
            'sku' => 'AIRPODS-PRO-3',
            'description' => 'Auriculares inalámbricos con cancelación de ruido adaptativa',
            'short_description' => 'Auriculares premium Apple',
            'price' => 249.99,
            'category_id' => 46, // Auriculares
            'brand_id' => 7, // Apple
            'status' => 'active',
            'is_featured' => 1,
            'main_image' => 'assets/images/airpods-pro-3.jpg'
        ],
        [
            'name' => 'iPad Pro 12.9"',
            'slug' => 'ipad-pro-12-9',
            'sku' => 'IPAD-PRO-12-9',
            'description' => 'iPad Pro con pantalla Liquid Retina XDR y chip M2',
            'short_description' => 'Tablet profesional',
            'price' => 799.99,
            'category_id' => 47, // Tablets
            'brand_id' => 7, // Apple
            'status' => 'active',
            'is_featured' => 1,
            'main_image' => 'assets/images/ipad-pro.jpg'
        ]
    ];
    
    echo "Agregando productos de prueba...\n";
    
    foreach ($testProducts as $product) {
        // Verificar si el producto ya existe
        $existing = $productModel->findAll(['slug' => $product['slug']], '', 1);
        
        if (empty($existing)) {
            $productModel->create($product);
            echo "✅ Producto agregado: {$product['name']}\n";
        } else {
            echo "⚠️ Producto ya existe: {$product['name']}\n";
        }
    }
    
    echo "\n=== VERIFICACIÓN DE BÚSQUEDA ===\n";
    
    // Probar búsqueda
    $queries = ['iphone', 'samsung', 'mac', 'air'];
    
    foreach ($queries as $query) {
        echo "\nBúsqueda: '$query'\n";
        $results = $productModel->searchProductsRealtime($query, 3);
        foreach ($results as $result) {
            echo "- {$result['name']} (\${$result['price']})\n";
        }
    }
    
    echo "\n🎉 Sistema de búsqueda listo para usar!\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}
?>
