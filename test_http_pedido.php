<?php
/**
 * Test directo para procesar-pedido.php mediante HTTP request
 * Simula una petición real desde finalizar-pedido.php
 */

echo "<h2>Test de procesar-pedido.php via HTTP</h2>";

// Obtener un producto real primero
require_once 'config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    $stmt = $db->query("SELECT id, name, stock, price FROM products WHERE status = 'active' AND stock > 0 LIMIT 1");
    $product = $stmt->fetch();
    
    if (!$product) {
        echo "<p style='color: red;'>❌ No hay productos activos con stock para probar</p>";
        exit;
    }
    
    echo "<p><strong>Producto encontrado:</strong> {$product['name']} (ID: {$product['id']}, Stock: {$product['stock']}, Precio: \${$product['price']})</p>";
    
    // Preparar datos para enviar
    $postData = [
        'firstName' => 'Juan',
        'lastName' => 'Pérez',
        'phone' => '3123456789',
        'email' => 'juan@test.com',
        'department' => 'Bogotá',
        'city' => 'Bogotá',
        'address' => 'Calle 123 #45-67',
        'cedula' => '12345678',
        'notes' => 'Pedido de prueba',
        'terms' => 'on'
    ];
    
    echo "<h3>Simulando carrito y enviando request...</h3>";
    
    // Iniciar nueva sesión para el carrito
    session_start();
    $_SESSION['cart'] = [
        [
            'id' => $product['id'],
            'quantity' => 1,
            'name' => $product['name'],
            'price' => $product['price']
        ]
    ];
    
    echo "<p><strong>Carrito creado:</strong> 1x {$product['name']}</p>";
    
    // Configurar context para el HTTP request
    $postString = http_build_query($postData);
    
    $context = stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => [
                'Content-Type: application/x-www-form-urlencoded',
                'Content-Length: ' . strlen($postString),
                'Cookie: ' . session_name() . '=' . session_id()
            ],
            'content' => $postString
        ]
    ]);
    
    $url = 'http://localhost/odisea-makeup-store/procesar-pedido.php';
    
    echo "<p><strong>Enviando request a:</strong> $url</p>";
    echo "<p><strong>Datos enviados:</strong></p>";
    echo "<pre>" . print_r($postData, true) . "</pre>";
    
    // Realizar request
    $response = file_get_contents($url, false, $context);
    
    echo "<h3>Respuesta del servidor:</h3>";
    
    if ($response === false) {
        echo "<p style='color: red;'>❌ Error al realizar la petición HTTP</p>";
    } else {
        echo "<div style='background: #f0f0f0; padding: 10px; border-radius: 5px;'>";
        echo "<strong>Raw response:</strong><br>";
        echo "<code>" . htmlspecialchars($response) . "</code>";
        echo "</div>";
        
        // Intentar decodificar JSON
        $decoded = json_decode($response, true);
        
        if ($decoded !== null) {
            echo "<h4 style='color: green;'>✅ JSON válido recibido</h4>";
            echo "<ul>";
            foreach ($decoded as $key => $value) {
                if (is_array($value)) {
                    echo "<li><strong>{$key}:</strong> " . print_r($value, true) . "</li>";
                } else {
                    echo "<li><strong>{$key}:</strong> " . htmlspecialchars($value) . "</li>";
                }
            }
            echo "</ul>";
            
            if ($decoded['success']) {
                echo "<p style='color: green; font-weight: bold;'>🎉 ¡Pedido procesado exitosamente!</p>";
                if (isset($decoded['order_id'])) {
                    echo "<p>Order ID generado: {$decoded['order_id']}</p>";
                }
            } else {
                echo "<p style='color: red;'>❌ Error procesando pedido: {$decoded['message']}</p>";
            }
        } else {
            echo "<h4 style='color: red;'>❌ Respuesta no es JSON válido</h4>";
            echo "<p>JSON Error: " . json_last_error_msg() . "</p>";
        }
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
?>
