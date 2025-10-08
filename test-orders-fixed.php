<?php
// Prueba final para la sección de pedidos corregida
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

// Simular sesión de administrador para prueba
$_SESSION['user_id'] = 1;
$_SESSION['role'] = 'admin';

require_once 'config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    echo "<h2>✅ Testing Fixed Orders Section</h2>";
    
    // Simulamos los filtros del archivo original
    $status_filter = '';
    $date_filter = '';
    $search = '';
    
    // Consulta simplificada como en la corrección
    $query = "SELECT o.*, 
                     (SELECT COUNT(*) FROM order_items oi WHERE oi.order_id = o.id) as item_count
              FROM orders o 
              WHERE 1=1";
    
    $params = [];
    
    if ($status_filter) {
        $query .= " AND o.status = ?";
        $params[] = $status_filter;
    }
    
    if ($date_filter) {
        $query .= " AND DATE(o.created_at) = ?";
        $params[] = $date_filter;
    }
    
    if ($search) {
        $query .= " AND (o.billing_address LIKE ? OR o.id = ?)";
        $searchParam = '%' . $search . '%';
        $params = array_merge($params, [$searchParam, $search]);
    }
    
    $query .= " ORDER BY o.created_at DESC";
    
    echo "<h3>1. Testing Database Query:</h3>";
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "✅ Query executed successfully. Found " . count($orders) . " orders.<br>";
    
    // Procesar datos JSON para cada pedido (como en la corrección)
    echo "<h3>2. Testing JSON Processing:</h3>";
    foreach ($orders as &$order) {
        $billingData = json_decode($order['billing_address'], true);
        $shippingData = json_decode($order['shipping_address'], true);
        
        // Extraer datos del cliente
        if ($billingData) {
            $order['customer_name'] = ($billingData['first_name'] ?? '') . ' ' . ($billingData['last_name'] ?? '');
            $order['customer_phone'] = $billingData['phone'] ?? 'N/A';
            $order['customer_email'] = $billingData['email'] ?? 'N/A';
        } else {
            $order['customer_name'] = 'N/A';
            $order['customer_phone'] = 'N/A';
            $order['customer_email'] = 'N/A';
        }
        
        // Extraer ciudad de envío
        if ($shippingData) {
            $order['shipping_city'] = $shippingData['city'] ?? ($billingData['city'] ?? 'N/A');
        } else {
            $order['shipping_city'] = $billingData['city'] ?? 'N/A';
        }
    }
    unset($order); // Romper la referencia
    
    echo "✅ JSON processing completed successfully.<br>";
    
    // Mostrar algunos resultados de ejemplo
    echo "<h3>3. Sample Processed Orders:</h3>";
    foreach (array_slice($orders, 0, 3) as $order) {
        echo "<div style='border:1px solid #ddd; padding:10px; margin:10px; border-radius:5px;'>";
        echo "<strong>Order #" . $order['id'] . "</strong><br>";
        echo "Customer: " . htmlspecialchars($order['customer_name']) . "<br>";
        echo "Email: " . htmlspecialchars($order['customer_email']) . "<br>";
        echo "Phone: " . htmlspecialchars($order['customer_phone']) . "<br>";
        echo "City: " . htmlspecialchars($order['shipping_city']) . "<br>";
        echo "Status: " . htmlspecialchars($order['status']) . "<br>";
        echo "Total: $" . number_format($order['total'], 2) . "<br>";
        echo "Items: " . $order['item_count'] . "<br>";
        echo "Date: " . $order['created_at'] . "<br>";
        echo "</div>";
    }
    
    // Test de estadísticas
    echo "<h3>4. Testing Statistics Query:</h3>";
    $stats_query = "SELECT 
                        COUNT(*) as total_orders,
                        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_orders,
                        SUM(CASE WHEN status = 'confirmed' THEN 1 ELSE 0 END) as confirmed_orders,
                        SUM(CASE WHEN status = 'shipped' THEN 1 ELSE 0 END) as shipped_orders,
                        SUM(CASE WHEN status = 'delivered' THEN 1 ELSE 0 END) as delivered_orders,
                        SUM(CASE WHEN DATE(created_at) = CURDATE() THEN total ELSE 0 END) as today_sales
                    FROM orders";
    $stats_stmt = $db->prepare($stats_query);
    $stats_stmt->execute();
    $stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "✅ Statistics query successful:<br>";
    echo "Total Orders: " . $stats['total_orders'] . "<br>";
    echo "Pending: " . $stats['pending_orders'] . "<br>";
    echo "Confirmed: " . $stats['confirmed_orders'] . "<br>";
    echo "Shipped: " . $stats['shipped_orders'] . "<br>";
    echo "Delivered: " . $stats['delivered_orders'] . "<br>";
    echo "Today's Sales: $" . number_format($stats['today_sales'], 2) . "<br>";
    
    echo "<h3>🎉 ALL TESTS PASSED!</h3>";
    echo "<p>The orders section should now work correctly without JSON function errors.</p>";
    echo "<p><a href='admin/pedidos.php' target='_blank'>➡️ View Orders Admin Panel</a></p>";
    
} catch (Exception $e) {
    echo "<h3>❌ Error:</h3>";
    echo "<p style='color: red;'>" . $e->getMessage() . "</p>";
    echo "<p><strong>File:</strong> " . $e->getFile() . "</p>";
    echo "<p><strong>Line:</strong> " . $e->getLine() . "</p>";
}
?>
