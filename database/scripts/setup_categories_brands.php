<?php
/**
 * Script para verificar y crear categorías y marcas necesarias
 */

require_once 'config/database.php';
require_once 'models/Category.php';
require_once 'models/Brand.php';

try {
    $categoryModel = new Category();
    $brandModel = new Brand();
    
    echo "=== VERIFICANDO CATEGORÍAS ===\n";
    $categories = $categoryModel->getAll();
    foreach ($categories as $cat) {
        echo "ID: {$cat['id']} - {$cat['name']}\n";
    }
    
    echo "\n=== VERIFICANDO MARCAS ===\n";
    $brands = $brandModel->getAll();
    foreach ($brands as $brand) {
        echo "ID: {$brand['id']} - {$brand['name']}\n";
    }
    
    // Crear categorías necesarias si no existen
    $requiredCategories = [
        ['name' => 'Smartphones', 'slug' => 'smartphones', 'is_active' => 1],
        ['name' => 'Laptops', 'slug' => 'laptops', 'is_active' => 1],
        ['name' => 'Auriculares', 'slug' => 'auriculares', 'is_active' => 1],
        ['name' => 'Tablets', 'slug' => 'tablets', 'is_active' => 1]
    ];
    
    echo "\n=== CREANDO CATEGORÍAS FALTANTES ===\n";
    foreach ($requiredCategories as $cat) {
        $existing = $categoryModel->findAll(['slug' => $cat['slug']], '', 1);
        if (empty($existing)) {
            $id = $categoryModel->create($cat);
            echo "✅ Categoría creada: {$cat['name']} (ID: $id)\n";
        } else {
            echo "⚠️ Categoría ya existe: {$cat['name']}\n";
        }
    }
    
    // Crear marcas necesarias si no existen
    $requiredBrands = [
        ['name' => 'Apple', 'slug' => 'apple', 'is_active' => 1],
        ['name' => 'Samsung', 'slug' => 'samsung', 'is_active' => 1]
    ];
    
    echo "\n=== CREANDO MARCAS FALTANTES ===\n";
    foreach ($requiredBrands as $brand) {
        $existing = $brandModel->findAll(['slug' => $brand['slug']], '', 1);
        if (empty($existing)) {
            $id = $brandModel->create($brand);
            echo "✅ Marca creada: {$brand['name']} (ID: $id)\n";
        } else {
            echo "⚠️ Marca ya existe: {$brand['name']}\n";
        }
    }
    
    echo "\n=== ESTADO FINAL ===\n";
    $categories = $categoryModel->getAll();
    $brands = $brandModel->getAll();
    
    echo "Categorías disponibles:\n";
    foreach ($categories as $cat) {
        echo "- ID: {$cat['id']} - {$cat['name']}\n";
    }
    
    echo "\nMarcas disponibles:\n";
    foreach ($brands as $brand) {
        echo "- ID: {$brand['id']} - {$brand['name']}\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}
?>
