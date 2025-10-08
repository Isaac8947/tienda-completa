<?php
// Debug para identificar errores en la sección de pedidos
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

require_once 'config/database.php';

try {
    echo "<h2>Testing Orders Section</h2>";
    
    // Test database connection
    echo "<h3>1. Testing Database Connection:</h3>";
    $database = new Database();
    $db = $database->getConnection();
    echo "✅ Database connection successful<br>";
    
    // Test basic order query
    echo "<h3>2. Testing Basic Order Query:</h3>";
    $stmt = $db->query("SELECT COUNT(*) as total FROM orders");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "📊 Total orders in database: " . $result['total'] . "<br>";
    
    // Test order details query
    echo "<h3>3. Testing Order Details Query:</h3>";
    $query = "SELECT o.*, 
                     CONCAT(JSON_UNQUOTE(JSON_EXTRACT(o.billing_address, '$.first_name')), ' ', 
                            JSON_UNQUOTE(JSON_EXTRACT(o.billing_address, '$.last_name'))) as customer_name,
                     JSON_UNQUOTE(JSON_EXTRACT(o.billing_address, '$.phone')) as customer_phone,
                     JSON_UNQUOTE(JSON_EXTRACT(o.shipping_address, '$.city')) as shipping_city,
                     (SELECT COUNT(*) FROM order_items oi WHERE oi.order_id = o.id) as item_count
              FROM orders o 
              ORDER BY o.created_at DESC LIMIT 5";
    
    $stmt = $db->query($query);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if ($orders) {
        echo "✅ Order query successful. Found " . count($orders) . " orders<br>";
        
        echo "<h3>4. Sample Order Data:</h3>";
        foreach ($orders as $order) {
            echo "<div style='border:1px solid #ccc; margin:10px; padding:10px;'>";
            echo "<strong>Order #" . $order['id'] . "</strong><br>";
            echo "Customer: " . htmlspecialchars($order['customer_name'] ?? 'N/A') . "<br>";
            echo "Phone: " . htmlspecialchars($order['customer_phone'] ?? 'N/A') . "<br>";
            echo "Status: " . htmlspecialchars($order['status']) . "<br>";
            echo "Total: $" . number_format($order['total'], 2) . "<br>";
            echo "Items: " . $order['item_count'] . "<br>";
            echo "Date: " . $order['created_at'] . "<br>";
            echo "</div>";
        }
    } else {
        echo "⚠️ No orders found<br>";
    }
    
    // Test order items query
    echo "<h3>5. Testing Order Items:</h3>";
    if ($orders) {
        $firstOrder = $orders[0];
        $itemsQuery = "SELECT oi.*, p.name as product_name 
                       FROM order_items oi 
                       LEFT JOIN products p ON oi.product_id = p.id 
                       WHERE oi.order_id = ?";
        $itemsStmt = $db->prepare($itemsQuery);
        $itemsStmt->execute([$firstOrder['id']]);
        $items = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);
        
        if ($items) {
            echo "✅ Order items query successful. Found " . count($items) . " items for order #" . $firstOrder['id'] . "<br>";
        } else {
            echo "⚠️ No items found for order #" . $firstOrder['id'] . "<br>";
        }
    }
    
    echo "<h3>6. Testing Session:</h3>";
    if (isset($_SESSION['user_id'])) {
        echo "✅ User session active: User ID " . $_SESSION['user_id'] . "<br>";
        echo "User role: " . ($_SESSION['role'] ?? 'Not set') . "<br>";
    } else {
        echo "⚠️ No user session found<br>";
    }
    
} catch (Exception $e) {
    echo "<h3>❌ Error encountered:</h3>";
    echo "<p style='color: red;'>" . $e->getMessage() . "</p>";
    echo "<p><strong>File:</strong> " . $e->getFile() . "</p>";
    echo "<p><strong>Line:</strong> " . $e->getLine() . "</p>";
}
?>
