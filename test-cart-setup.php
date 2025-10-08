<?php
session_start();

// Simular productos en el carrito para pruebas
$_SESSION['cart'] = [
    'product_1' => [
        'product_id' => 1,
        'name' => 'Producto de Prueba 1',
        'price' => 25000,
        'quantity' => 2,
        'image' => 'default.jpg',
        'sku' => 'TEST001'
    ],
    'product_2' => [
        'product_id' => 2,
        'name' => 'Producto de Prueba 2',
        'price' => 35000,
        'quantity' => 1,
        'image' => 'default.jpg',
        'sku' => 'TEST002',
        'compare_price' => 45000,
        'discount_percentage' => 22
    ]
];

echo "Carrito de prueba creado. <a href='carrito.php'>Ver carrito</a>";
?>