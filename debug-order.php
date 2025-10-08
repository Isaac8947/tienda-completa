<?php
// Debug para ver el contenido real de las órdenes
require_once 'config/database.php';

try {
    $db = Database::getConnection();
    
    // Obtener la última orden
    $stmt = $db->query("SELECT * FROM orders ORDER BY id DESC LIMIT 1");
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($order) {
        echo "<h2>DEBUG - Contenido de la Orden #" . $order['id'] . "</h2>";
        
        echo "<h3>Datos Raw:</h3>";
        echo "<p><strong>billing_address:</strong> " . htmlspecialchars($order['billing_address']) . "</p>";
        echo "<p><strong>shipping_address:</strong> " . htmlspecialchars($order['shipping_address']) . "</p>";
        
        echo "<h3>JSON Decoded billing_address:</h3>";
        $billingData = json_decode($order['billing_address'], true);
        if ($billingData) {
            echo "<pre>" . print_r($billingData, true) . "</pre>";
            
            echo "<h4>Datos formateados para mostrar:</h4>";
            echo "<p><strong>Nombre:</strong> " . htmlspecialchars(($billingData['first_name'] ?? '') . ' ' . ($billingData['last_name'] ?? '')) . "</p>";
            echo "<p><strong>Email:</strong> " . htmlspecialchars($billingData['email'] ?? 'N/A') . "</p>";
            echo "<p><strong>Teléfono:</strong> " . htmlspecialchars($billingData['phone'] ?? 'N/A') . "</p>";
            echo "<p><strong>Dirección:</strong> " . htmlspecialchars($billingData['address'] ?? 'N/A') . "</p>";
            echo "<p><strong>Ciudad:</strong> " . htmlspecialchars($billingData['city'] ?? 'N/A') . "</p>";
            echo "<p><strong>Departamento:</strong> " . htmlspecialchars($billingData['department'] ?? 'N/A') . "</p>";
            
        } else {
            echo "<p>Error al decodificar billing_address JSON</p>";
            echo "<p>Contenido: " . $order['billing_address'] . "</p>";
        }
        
        echo "<h3>JSON Decoded shipping_address:</h3>";
        $shippingData = json_decode($order['shipping_address'], true);
        if ($shippingData) {
            echo "<pre>" . print_r($shippingData, true) . "</pre>";
        } else {
            echo "<p>Error al decodificar shipping_address JSON</p>";
            echo "<p>Contenido: " . $order['shipping_address'] . "</p>";
        }
        
        // Verificar todos los campos disponibles
        echo "<h3>Todos los campos de la orden:</h3>";
        echo "<pre>" . print_r($order, true) . "</pre>";
        
    } else {
        echo "<p>No hay órdenes en la base de datos</p>";
    }
    
} catch (Exception $e) {
    echo "<p>Error: " . $e->getMessage() . "</p>";
}
?>
