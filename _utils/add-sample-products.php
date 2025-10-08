<?php
// Script para agregar solo productos de prueba
require_once 'config/database.php';
require_once 'models/Category.php';
require_once 'models/Brand.php';
require_once 'models/Product.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Crear instancias de los modelos
    $categoryModel = new Category();
    $brandModel = new Brand();
    $productModel = new Product();
    
    // Obtener IDs de categorías existentes
    $categories = $categoryModel->getAll();
    $categoryIds = [];
    foreach ($categories as $category) {
        $categoryIds[strtolower($category['slug'])] = $category['id'];
    }
    
    // Obtener IDs de marcas existentes
    $brands = $brandModel->getAll();
    $brandIds = [];
    foreach ($brands as $brand) {
        $brandIds[strtolower($brand['name'])] = $brand['id'];
    }
    
    // Verificar si ya existen productos
    $existingProducts = $productModel->getAll();
    if (!empty($existingProducts)) {
        echo "Ya existen productos en la base de datos.\n";
        echo "Productos existentes: " . count($existingProducts) . "\n";
        exit;
    }
    
    // Crear productos de muestra
    $products = [
        [
            'name' => 'Base de Maquillaje Líquida HD',
            'slug' => 'base-maquillaje-liquida-hd',
            'description' => 'Base de maquillaje líquida de alta definición con cobertura completa y acabado natural. Perfecta para todo tipo de piel.',
            'short_description' => 'Base líquida HD con cobertura completa',
            'sku' => 'BASE-HD-001',
            'price' => 45000.00,
            'compare_price' => 55000.00,
            'stock_quantity' => 50,
            'inventory_quantity' => 50,
            'category_id' => $categoryIds['rostro'] ?? 1,
            'brand_id' => $brandIds['nars'] ?? 3,
            'status' => 'active',
            'is_featured' => 1,
            'main_image' => 'uploads/products/base-liquida-hd.jpg',
            'ingredients' => 'Agua, Cyclopentasiloxane, Dimethicone, Glicerina',
            'how_to_use' => 'Aplicar con una esponja húmeda o brocha sobre el rostro limpio',
            'benefits' => 'Cobertura completa, larga duración, acabado natural'
        ],
        [
            'name' => 'Paleta de Sombras Naked Heat',
            'slug' => 'naked-heat-eyeshadow-palette',
            'description' => 'Paleta de sombras con 12 tonos cálidos inspirados en el atardecer. Texturas mate y brillantes.',
            'short_description' => 'Paleta 12 sombras tonos cálidos',
            'sku' => 'NAKED-HEAT-001',
            'price' => 120000.00,
            'compare_price' => 140000.00,
            'stock_quantity' => 30,
            'inventory_quantity' => 30,
            'category_id' => $categoryIds['ojos'] ?? 2,
            'brand_id' => $brandIds['urban decay'] ?? 2,
            'status' => 'active',
            'is_featured' => 1,
            'main_image' => 'uploads/products/naked-heat-palette.jpg',
            'how_to_use' => 'Aplicar con brocha sobre los párpados',
            'benefits' => 'Colores intensos, larga duración, fácil difuminado'
        ],
        [
            'name' => 'Labial Líquido Mate',
            'slug' => 'labial-liquido-mate',
            'description' => 'Labial líquido con acabado mate de larga duración. No transfiere y es resistente al agua.',
            'short_description' => 'Labial líquido mate larga duración',
            'sku' => 'LABIAL-MATE-001',
            'price' => 35000.00,
            'compare_price' => 42000.00,
            'stock_quantity' => 80,
            'inventory_quantity' => 80,
            'category_id' => $categoryIds['labios'] ?? 3,
            'brand_id' => $brandIds['mac'] ?? 5,
            'status' => 'active',
            'is_featured' => 1,
            'main_image' => 'uploads/products/labial-mate.jpg',
            'how_to_use' => 'Aplicar directamente sobre los labios limpios',
            'benefits' => 'Acabado mate, no transfiere, larga duración'
        ],
        [
            'name' => 'Gel para Cejas Transparente',
            'slug' => 'gel-cejas-transparente',
            'description' => 'Gel fijador transparente para cejas. Mantiene las cejas en su lugar todo el día sin residuos.',
            'short_description' => 'Gel fijador transparente para cejas',
            'sku' => 'GEL-CEJAS-001',
            'price' => 28000.00,
            'stock_quantity' => 60,
            'inventory_quantity' => 60,
            'category_id' => $categoryIds['cejas'] ?? 4,
            'brand_id' => $brandIds['charlotte tilbury'] ?? 4,
            'status' => 'active',
            'is_featured' => 1,
            'main_image' => 'uploads/products/gel-cejas.jpg',
            'how_to_use' => 'Aplicar con el cepillo sobre las cejas peinándolas hacia arriba',
            'benefits' => 'Fijación todo el día, transparente, no apelmaza'
        ],
        [
            'name' => 'Rubor en Polvo',
            'slug' => 'rubor-polvo',
            'description' => 'Rubor en polvo con pigmentación intensa y acabado natural. Disponible en varios tonos.',
            'short_description' => 'Rubor en polvo pigmentación intensa',
            'sku' => 'RUBOR-001',
            'price' => 38000.00,
            'stock_quantity' => 45,
            'inventory_quantity' => 45,
            'category_id' => $categoryIds['rostro'] ?? 1,
            'brand_id' => $brandIds['nars'] ?? 3,
            'status' => 'active',
            'is_featured' => 0,
            'main_image' => 'uploads/products/rubor-polvo.jpg'
        ],
        [
            'name' => 'Máscara de Pestañas Volumen',
            'slug' => 'mascara-pestanas-volumen',
            'description' => 'Máscara de pestañas que proporciona volumen extremo sin apelmazarse. Resistente al agua.',
            'short_description' => 'Máscara volumen extremo waterproof',
            'sku' => 'MASCARA-VOL-001',
            'price' => 42000.00,
            'stock_quantity' => 70,
            'inventory_quantity' => 70,
            'category_id' => $categoryIds['ojos'] ?? 2,
            'brand_id' => $brandIds['mac'] ?? 5,
            'status' => 'active',
            'is_featured' => 0,
            'main_image' => 'uploads/products/mascara-volumen.jpg'
        ]
    ];
    
    $createdCount = 0;
    foreach ($products as $productData) {
        try {
            $result = $productModel->create($productData);
            if ($result) {
                echo "Producto '{$productData['name']}' creado con ID: {$result}\n";
                $createdCount++;
            } else {
                echo "Error al crear producto '{$productData['name']}'\n";
            }
        } catch (Exception $e) {
            echo "Error al crear producto '{$productData['name']}': " . $e->getMessage() . "\n";
        }
    }
    
    echo "\n✅ Proceso completado!\n";
    echo "📦 Productos creados: {$createdCount}\n";
    echo "🎯 La base de datos está lista para usar\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}
?>
