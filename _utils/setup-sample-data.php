<?php
// Script para agregar datos de prueba a la base de datos
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
    
    // Verificar si ya existen datos
    $existingCategories = $categoryModel->getAll();
    if (!empty($existingCategories)) {
        echo "Ya existen categorías en la base de datos. No se insertarán datos de prueba.\n";
        exit;
    }
    
    // Crear categorías
    $categories = [
        [
            'name' => 'Rostro',
            'slug' => 'rostro',
            'description' => 'Bases, correctores, polvos y productos para el rostro',
            'is_active' => 1,
            'sort_order' => 1
        ],
        [
            'name' => 'Ojos',
            'slug' => 'ojos',
            'description' => 'Sombras, delineadores, máscaras y productos para ojos',
            'is_active' => 1,
            'sort_order' => 2
        ],
        [
            'name' => 'Labios',
            'slug' => 'labios',
            'description' => 'Labiales, brillos y productos para labios',
            'is_active' => 1,
            'sort_order' => 3
        ],
        [
            'name' => 'Cejas',
            'slug' => 'cejas',
            'description' => 'Productos para definir y arreglar las cejas',
            'is_active' => 1,
            'sort_order' => 4
        ]
    ];
    
    $categoryIds = [];
    foreach ($categories as $category) {
        $id = $categoryModel->create($category);
        $categoryIds[$category['slug']] = $id;
        echo "Categoría '{$category['name']}' creada con ID: $id\n";
    }
    
    // Crear marcas
    $brands = [
        [
            'name' => 'TechMaster',
            'slug' => 'techmaster',
            'description' => 'Marca líder en innovación tecnológica y gadgets',
            'website' => 'https://techmaster.com',
            'is_active' => 1
        ],
        [
            'name' => 'Urban Decay',
            'slug' => 'urban-decay',
            'description' => 'Marca americana de dispositivos inteligentes',
            'website' => 'https://urbandecay.com',
            'is_active' => 1
        ],
        [
            'name' => 'NARS',
            'slug' => 'nars',
            'description' => 'Marca francesa de audio premium',
            'website' => 'https://audioparis.com',
            'is_active' => 1
        ],
        [
            'name' => 'Charlotte Tilbury',
            'slug' => 'charlotte-tilbury',
            'description' => 'Marca británica de computadoras y accesorios',
            'website' => 'https://charlottetilbury.com',
            'is_active' => 1
        ],
        [
            'name' => 'MAC',
            'slug' => 'mac',
            'description' => 'SmartTech - Marca profesional de domótica',
            'website' => 'https://smarttech.com',
            'is_active' => 1
        ]
    ];
    
    $brandIds = [];
    foreach ($brands as $brand) {
        $id = $brandModel->create($brand);
        $brandIds[$brand['slug']] = $id;
        echo "Marca '{$brand['name']}' creada con ID: $id\n";
    }
    
    // Crear productos
    $products = [
        [
            'name' => "Pro Filt'r Soft Matte Foundation",
            'slug' => 'pro-filtr-soft-matte-foundation',
            'description' => 'Laptop ultradelgada de alto rendimiento con pantalla 4K y batería de larga duración.',
            'short_description' => 'Base mate de larga duración con cobertura completa',
            'sku' => 'FB-001',
            'price' => 89000,
            'cost_price' => 45000,
                'stock_quantity' => 25,
            'category_id' => $categoryIds['rostro'],
            'brand_id' => $brandIds['techmaster'],
            'status' => 'active',
            'is_featured' => 1,
            'is_new' => 1,
            'meta_title' => "UltraBook Pro - TechMaster",
            'meta_description' => 'Laptop TechMaster UltraBook Pro con pantalla 4K y diseño ultraligero.'
        ],
        [
            'name' => 'Naked Heat Eyeshadow Palette',
            'slug' => 'naked-heat-eyeshadow-palette',
            'description' => 'Paleta de sombras con 12 tonos cálidos en acabados mate y metálico. Perfecta para crear looks desde naturales hasta dramáticos.',
            'short_description' => 'Paleta de 12 sombras en tonos cálidos',
            'sku' => 'UD-002',
            'price' => 156000,
            'compare_price' => 175000,
            'cost_price' => 78000,
                'stock_quantity' => 15,
            'category_id' => $categoryIds['ojos'],
            'brand_id' => $brandIds['urban-decay'],
            'status' => 'active',
            'is_featured' => 1,
            'meta_title' => 'Naked Heat Palette - Urban Decay',
            'meta_description' => 'Paleta de sombras Urban Decay con 12 tonos cálidos'
        ],
        [
            'name' => 'Orgasm Blush',
            'slug' => 'orgasm-blush',
            'description' => 'El rubor más icónico de NARS. Un tono coral dorado con destellos sutiles que favorece a todos los tonos de piel.',
            'short_description' => 'Rubor icónico en tono coral dorado',
            'sku' => 'NR-003',
            'price' => 78000,
            'cost_price' => 39000,
                'stock_quantity' => 30,
            'category_id' => $categoryIds['rostro'],
            'brand_id' => $brandIds['nars'],
            'status' => 'active',
            'is_featured' => 1,
            'meta_title' => 'Orgasm Blush - NARS',
            'meta_description' => 'El rubor más vendido de NARS en tono coral dorado'
        ],
        [
            'name' => 'Pillow Talk Lipstick',
            'slug' => 'pillow-talk-lipstick',
            'description' => 'Labial cremoso en el tono nude-rosado más vendido del mundo. Fórmula hidratante con acabado satinado.',
            'short_description' => 'Labial cremoso en tono nude-rosado',
            'sku' => 'CT-004',
            'price' => 95000,
            'cost_price' => 47500,
                'stock_quantity' => 20,
            'category_id' => $categoryIds['labios'],
            'brand_id' => $brandIds['charlotte-tilbury'],
            'status' => 'active',
            'is_featured' => 1,
            'meta_title' => 'Pillow Talk Lipstick - Charlotte Tilbury',
            'meta_description' => 'El labial nude más vendido de Charlotte Tilbury'
        ],
        [
            'name' => 'Ruby Woo Lipstick',
            'slug' => 'ruby-woo-lipstick',
            'description' => 'Labial mate en rojo clásico. Fórmula de larga duración que no se transfiere y proporciona color intenso.',
            'short_description' => 'Labial mate en rojo clásico',
            'sku' => 'MAC-005',
            'price' => 65000,
            'cost_price' => 32500,
                'stock_quantity' => 40,
            'category_id' => $categoryIds['labios'],
            'brand_id' => $brandIds['mac'],
            'status' => 'active',
            'is_featured' => 0,
            'is_new' => 1,
            'meta_title' => 'Ruby Woo Lipstick - MAC',
            'meta_description' => 'Auriculares inalámbricos de alta fidelidad AudioParis'
        ],
        [
            'name' => 'Brow Wiz Pencil',
            'slug' => 'brow-wiz-pencil',
            'description' => 'Lápiz de cejas ultrafino para definir y rellenar las cejas con precisión. Disponible en múltiples tonos.',
            'short_description' => 'Lápiz de cejas ultrafino de precisión',
            'sku' => 'ABH-006',
            'price' => 52000,
            'cost_price' => 26000,
                'stock_quantity' => 35,
            'category_id' => $categoryIds['cejas'],
            'brand_id' => $brandIds['techmaster'], // Usando TechMaster como ejemplo
            'status' => 'active',
            'is_featured' => 0,
            'meta_title' => 'Brow Wiz Pencil - Anastasia Beverly Hills',
            'meta_description' => 'Lápiz de cejas ultrafino para definición precisa'
        ]
    ];
    
    foreach ($products as $product) {
        $id = $productModel->create($product);
        echo "Producto '{$product['name']}' creado con ID: $id\n";
    }
    
    echo "\n¡Datos de prueba creados exitosamente!\n";
    echo "Categorías: " . count($categories) . "\n";
    echo "Marcas: " . count($brands) . "\n";
    echo "Productos: " . count($products) . "\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}
?>
