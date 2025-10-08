<?php
// Script para agregar stock inicial a los productos
try {
    $pdo = new PDO('mysql:host=localhost;dbname=odisea_makeup', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Iniciando actualización de stock inicial...\n";
    
    // Obtener todos los productos que no tienen stock asignado
    $products = $pdo->query("SELECT id, name FROM products WHERE stock IS NULL OR stock = 0")->fetchAll();
    
    $updated = 0;
    foreach ($products as $product) {
        // Asignar stock aleatorio entre 5 y 100
        $stock = rand(5, 100);
        $min_stock = rand(3, 10);
        
        $stmt = $pdo->prepare("UPDATE products SET stock = ?, min_stock = ? WHERE id = ?");
        $stmt->execute([$stock, $min_stock, $product['id']]);
        
        echo "✓ Producto '{$product['name']}': Stock = $stock, Min Stock = $min_stock\n";
        $updated++;
    }
    
    echo "\n=== ACTUALIZACIÓN COMPLETADA ===\n";
    echo "Total de productos actualizados: $updated\n";
    
    // Crear algunas notificaciones de ejemplo
    $sample_notifications = [
        [
            'type' => 'stock',
            'title' => 'Stock bajo detectado',
            'message' => 'Algunos productos tienen stock por debajo del mínimo recomendado.',
            'priority' => 'high'
        ],
        [
            'type' => 'order',
            'title' => 'Nuevos pedidos recibidos',
            'message' => 'Hay nuevos pedidos que requieren atención.',
            'priority' => 'medium'
        ]
    ];
    
    $insert_notification = $pdo->prepare("INSERT INTO admin_notifications (type, title, message, priority) VALUES (?, ?, ?, ?)");
    
    foreach ($sample_notifications as $notification) {
        $insert_notification->execute([
            $notification['type'],
            $notification['title'],
            $notification['message'],
            $notification['priority']
        ]);
    }
    
    echo "✓ Notificaciones de ejemplo creadas\n";
    
} catch(PDOException $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
?>
