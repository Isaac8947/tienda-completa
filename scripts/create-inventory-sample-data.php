<?php
require_once '../config/config.php';
require_once '../models/Product.php';
require_once '../models/InventoryHistory.php';

echo "Creando datos de ejemplo para el historial de inventario...\n";

$product = new Product();
$inventoryHistory = new InventoryHistory();

// Obtener productos existentes
$products = $product->getAll(['status' => 'active']);

if (empty($products)) {
    echo "No hay productos disponibles para crear historial.\n";
    exit;
}

// Crear movimientos de ejemplo para cada producto
foreach (array_slice($products, 0, 3) as $prod) {
    echo "Creando historial para: " . $prod['name'] . "\n";
    
    // Movimiento de entrada (restock)
    $inventoryHistory->recordStockIn(
        $prod['id'], 
        50, 
        'Reabastecimiento inicial',
        [
            'reference_type' => 'restock',
            'notes' => 'Stock inicial del producto'
        ]
    );
    
    // Simular algunas ventas
    for ($i = 0; $i < 3; $i++) {
        $saleQuantity = rand(1, 5);
        $product->reduceStockBySale($prod['id'], $saleQuantity, rand(1000, 9999));
        
        // Esperar un poco para que las fechas sean diferentes
        sleep(1);
    }
    
    // Movimiento de ajuste
    $inventoryHistory->adjustStock(
        $prod['id'],
        rand(10, 30),
        'Ajuste por inventario físico',
        [
            'notes' => 'Corrección después de conteo físico'
        ]
    );
    
    echo "Historial creado para " . $prod['name'] . "\n";
}

echo "¡Datos de ejemplo creados exitosamente!\n";
?>
