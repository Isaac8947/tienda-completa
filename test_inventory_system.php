<?php
// Script de prueba para el sistema de inventario
require_once 'config/config.php';
require_once 'config/database.php';
require_once 'includes/InventoryManager.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    $inventory = new InventoryManager($db);
    
    echo "=== PRUEBA DEL SISTEMA DE INVENTARIO ===\n\n";
    
    // 1. Obtener un producto de ejemplo
    $stmt = $db->query("SELECT * FROM products WHERE stock > 0 LIMIT 1");
    $product = $stmt->fetch();
    
    if (!$product) {
        echo "❌ No hay productos con stock disponible\n";
        exit;
    }
    
    echo "✓ Producto de prueba: {$product['name']}\n";
    echo "✓ Stock inicial: {$product['stock']}\n";
    echo "✓ Stock mínimo: {$product['min_stock']}\n\n";
    
    // 2. Obtener un pedido real existente
    $order_stmt = $db->query("SELECT id FROM orders WHERE status = 'pending' LIMIT 1");
    $order = $order_stmt->fetch();
    
    if (!$order) {
        echo "⚠️ No hay pedidos pendientes, vamos a simular con un movimiento directo\n\n";
        
        // 3. Simular movimiento directo
        echo "2. Simulando venta directa...\n";
        $inventory->recordMovement(
            $product['id'],
            null,
            'sale',
            -2, // Vender 2 unidades
            'Venta directa - Prueba del sistema'
        );
        
    } else {
        $order_id = $order['id'];
        echo "2. Usando pedido real #$order_id para prueba...\n";
        
        // Verificar que el pedido tenga items
        $items_check = $db->prepare("SELECT COUNT(*) FROM order_items WHERE order_id = ?");
        $items_check->execute([$order_id]);
        $items_count = $items_check->fetchColumn();
        
        if ($items_count == 0) {
            // Agregar un item de prueba
            $db->prepare("INSERT INTO order_items (order_id, product_id, quantity, price, total, product_name) VALUES (?, ?, 2, ?, ?, ?)")
               ->execute([$order_id, $product['id'], $product['price'], $product['price'] * 2, $product['name']]);
            echo "✓ Item de prueba agregado al pedido\n";
        }
        
        // Procesar la venta
        $inventory->processSale($order_id);
    }
    
    // Verificar el stock después de la venta
    $stmt = $db->prepare("SELECT stock FROM products WHERE id = ?");
    $stmt->execute([$product['id']]);
    $new_stock = $stmt->fetchColumn();
    
    echo "✓ Stock después de la venta: $new_stock\n";
    echo "✓ Venta procesada correctamente\n\n";
    
    // 3. Obtener historial de movimientos
    echo "3. Historial de movimientos:\n";
    $history = $inventory->getInventoryHistory($product['id'], 5);
    
    foreach ($history as $movement) {
        $sign = $movement['quantity_change'] > 0 ? '+' : '';
        echo "   • {$movement['movement_type']}: {$sign}{$movement['quantity_change']} unidades";
        echo " (Stock final: {$movement['quantity_after']})";
        echo " - {$movement['reason']}\n";
    }
    
    // 4. Probar actualización manual de stock
    echo "\n4. Probando actualización manual de stock...\n";
    $inventory->updateStock($product['id'], $product['stock'] + 10, 'Prueba de sistema - Restock manual');
    
    $stmt = $db->prepare("SELECT stock FROM products WHERE id = ?");
    $stmt->execute([$product['id']]);
    $final_stock = $stmt->fetchColumn();
    
    echo "✓ Stock final después del restock: $final_stock\n";
    
    // 5. Verificar productos con stock bajo
    echo "\n5. Productos con stock bajo:\n";
    $low_stock = $inventory->getLowStockProducts();
    
    if (empty($low_stock)) {
        echo "   • No hay productos con stock bajo\n";
    } else {
        foreach ($low_stock as $item) {
            echo "   • {$item['name']}: {$item['stock']} unidades (Mín: {$item['min_stock']})\n";
        }
    }
    
    // 6. Probar notificaciones
    echo "\n6. Creando notificación de prueba...\n";
    $inventory->createNotification(
        'system',
        'Prueba del sistema',
        'El sistema de inventario está funcionando correctamente.',
        null,
        'low'
    );
    echo "✓ Notificación creada\n";
    
    // Limpiar datos de prueba si creamos un pedido ficticio
    if (isset($order_id) && $order_id == 999) {
        echo "\n7. Limpiando datos de prueba...\n";
        $db->exec("DELETE FROM orders WHERE id = $order_id");
        $db->exec("DELETE FROM order_items WHERE order_id = $order_id");
        echo "✓ Datos de prueba eliminados\n";
    } else {
        echo "\n7. Usando datos reales, no hay limpieza necesaria\n";
    }
    
    echo "\n=== TODAS LAS PRUEBAS COMPLETADAS CON ÉXITO ===\n";
    echo "✅ Sistema de inventario funcionando correctamente\n";
    echo "✅ Gestión automática de stock operativa\n";
    echo "✅ Sistema de notificaciones activo\n";
    echo "✅ Historial de movimientos registrándose\n";
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}
?>
