<?php
// Archivo de prueba para verificar la factura
require_once 'config/database.php';

try {
    $db = Database::getConnection();
    
    // Buscar la primera orden disponible
    $stmt = $db->query("SELECT * FROM orders ORDER BY id DESC LIMIT 1");
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($order) {
        echo "<h2>Orden encontrada - ID: " . $order['id'] . "</h2>";
        echo "<p><strong>Total:</strong> $" . number_format($order['total'], 2) . "</p>";
        echo "<p><strong>Status:</strong> " . $order['status'] . "</p>";
        echo "<p><strong>Fecha:</strong> " . $order['created_at'] . "</p>";
        
        // Mostrar dirección de facturación
        $billingData = json_decode($order['billing_address'], true);
        echo "<h3>Datos de facturación:</h3>";
        if ($billingData) {
            echo "<p><strong>Nombre:</strong> " . htmlspecialchars($billingData['nombre'] ?? 'N/A') . "</p>";
            echo "<p><strong>Email:</strong> " . htmlspecialchars($billingData['email'] ?? 'N/A') . "</p>";
            echo "<p><strong>Dirección:</strong> " . htmlspecialchars($billingData['direccion'] ?? 'N/A') . "</p>";
        } else {
            echo "<p>No hay datos de facturación</p>";
        }
        
        // Buscar items de la orden
        $itemsStmt = $db->prepare("SELECT oi.*, p.name as product_name FROM order_items oi LEFT JOIN products p ON oi.product_id = p.id WHERE oi.order_id = ?");
        $itemsStmt->execute([$order['id']]);
        $items = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<h3>Items de la orden:</h3>";
        if ($items) {
            foreach ($items as $item) {
                echo "<p>- " . htmlspecialchars($item['product_name'] ?? 'Producto sin nombre') . 
                     " x" . $item['quantity'] . 
                     " = $" . number_format($item['price'] * $item['quantity'], 2) . "</p>";
            }
        } else {
            echo "<p>No hay items en esta orden</p>";
        }
        
        echo "<br><a href='admin-pages/invoice.php?id=" . $order['id'] . "' target='_blank'>Ver Factura</a>";
        
    } else {
        echo "<p>No hay órdenes en la base de datos</p>";
        
        // Mostrar estructura de la tabla orders
        echo "<h3>Estructura de la tabla orders:</h3>";
        $columns = $db->query("SHOW COLUMNS FROM orders")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($columns as $column) {
            echo "<p>- " . $column['Field'] . " (" . $column['Type'] . ")</p>";
        }
    }
    
} catch (Exception $e) {
    echo "<p>Error: " . $e->getMessage() . "</p>";
}
?>
