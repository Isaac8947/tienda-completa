<?php
// Test específico para las consultas JSON de pedidos
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    echo "<h2>Testing JSON Queries for Orders</h2>";
    
    // Test 1: Verificar si las funciones JSON están disponibles
    echo "<h3>1. Testing JSON Functions Availability:</h3>";
    try {
        $test_query = "SELECT JSON_UNQUOTE(JSON_EXTRACT('{\"name\": \"test\"}', '$.name')) as result";
        $stmt = $db->query($test_query);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "✅ JSON functions work: " . $result['result'] . "<br>";
    } catch (Exception $e) {
        echo "❌ JSON functions not supported: " . $e->getMessage() . "<br>";
    }
    
    // Test 2: Verificar estructura de datos reales
    echo "<h3>2. Checking Real Data Structure:</h3>";
    $stmt = $db->query("SELECT id, billing_address, shipping_address FROM orders LIMIT 1");
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($order) {
        echo "<strong>Order ID:</strong> " . $order['id'] . "<br>";
        echo "<strong>billing_address:</strong> " . htmlspecialchars($order['billing_address']) . "<br>";
        echo "<strong>shipping_address:</strong> " . htmlspecialchars($order['shipping_address']) . "<br>";
        
        // Verificar si se puede decodificar como JSON válido
        $billing = json_decode($order['billing_address'], true);
        $shipping = json_decode($order['shipping_address'], true);
        
        if ($billing) {
            echo "✅ billing_address is valid JSON<br>";
            echo "Available keys: " . implode(', ', array_keys($billing)) . "<br>";
        } else {
            echo "❌ billing_address is not valid JSON<br>";
        }
        
        if ($shipping) {
            echo "✅ shipping_address is valid JSON<br>";
            echo "Available keys: " . implode(', ', array_keys($shipping)) . "<br>";
        } else {
            echo "❌ shipping_address is not valid JSON<br>";
        }
    }
    
    // Test 3: Probar la consulta actual de pedidos
    echo "<h3>3. Testing Current Orders Query:</h3>";
    
    try {
        $query = "SELECT o.*, 
                         CONCAT(JSON_UNQUOTE(JSON_EXTRACT(o.billing_address, '$.first_name')), ' ', 
                                JSON_UNQUOTE(JSON_EXTRACT(o.billing_address, '$.last_name'))) as customer_name,
                         JSON_UNQUOTE(JSON_EXTRACT(o.billing_address, '$.phone')) as customer_phone,
                         JSON_UNQUOTE(JSON_EXTRACT(o.shipping_address, '$.city')) as shipping_city,
                         (SELECT COUNT(*) FROM order_items oi WHERE oi.order_id = o.id) as item_count
                  FROM orders o 
                  LIMIT 3";
        
        $stmt = $db->query($query);
        $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "✅ Complex JSON query executed successfully<br>";
        echo "Orders found: " . count($orders) . "<br>";
        
        foreach ($orders as $order) {
            echo "<div style='border:1px solid #ddd; padding:10px; margin:5px;'>";
            echo "ID: " . $order['id'] . " | ";
            echo "Customer: " . ($order['customer_name'] ?: 'N/A') . " | ";
            echo "Phone: " . ($order['customer_phone'] ?: 'N/A') . " | ";
            echo "City: " . ($order['shipping_city'] ?: 'N/A');
            echo "</div>";
        }
        
    } catch (Exception $e) {
        echo "❌ Complex JSON query failed: " . $e->getMessage() . "<br>";
        
        // Intentar consulta más simple
        echo "<h4>Trying simpler query:</h4>";
        try {
            $simple_query = "SELECT o.id, o.status, o.total, o.created_at FROM orders o LIMIT 3";
            $stmt = $db->query($simple_query);
            $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo "✅ Simple query works. Orders found: " . count($orders) . "<br>";
        } catch (Exception $e2) {
            echo "❌ Even simple query failed: " . $e2->getMessage() . "<br>";
        }
    }
    
} catch (Exception $e) {
    echo "❌ Database connection failed: " . $e->getMessage() . "<br>";
}
?>

<h3>Suggested Fix:</h3>
<p>If JSON functions are not working, we should modify the orders query to use PHP to decode JSON instead of SQL.</p>
