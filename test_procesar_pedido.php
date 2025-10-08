<?php
/**
 * Test básico para procesar-pedido.php
 * Verifica que el archivo se puede cargar sin errores
 */

// Obtener un producto real de la base de datos
require_once 'config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Obtener un producto activo con stock
    $stmt = $db->query("SELECT id, name, stock FROM products WHERE status = 'active' AND stock > 0 LIMIT 1");
    $product = $stmt->fetch();
    
    if (!$product) {
        echo "❌ No hay productos activos con stock para probar\n";
        exit;
    }
    
    echo "Producto encontrado: {$product['name']} (ID: {$product['id']}, Stock: {$product['stock']})\n";
    
    // Simular datos de prueba en sesión
    session_start();
    
    $_SESSION['cart'] = [
        [
            'id' => $product['id'],
            'quantity' => 1
        ]
    ];
    
    // Simular POST data
    $_POST = [
        'firstName' => 'Test',
        'lastName' => 'User',
        'phone' => '1234567890',
        'email' => 'test@test.com',
        'department' => 'Test Dept',
        'city' => 'Test City',
        'address' => 'Test Address 123',
        'terms' => 'on'
    ];
    
    // Cambiar método de request
    $_SERVER['REQUEST_METHOD'] = 'POST';
    
    echo "Iniciando prueba de procesar-pedido.php...\n";
    
    // Capturar output
    ob_start();
    
    try {
        include 'procesar-pedido.php';
        $output = ob_get_contents();
        ob_end_clean();
        
        echo "Output capturado:\n";
        echo $output;
        echo "\n";
        
        // Verificar si es JSON válido
        $decoded = json_decode($output, true);
        if ($decoded !== null) {
            echo "✓ JSON válido\n";
            echo "Success: " . ($decoded['success'] ? 'true' : 'false') . "\n";
            if (isset($decoded['message'])) {
                echo "Message: " . $decoded['message'] . "\n";
            }
            if (isset($decoded['order_id'])) {
                echo "Order ID: " . $decoded['order_id'] . "\n";
            }
        } else {
            echo "✗ JSON inválido\n";
            echo "Error de JSON: " . json_last_error_msg() . "\n";
        }
        
    } catch (Exception $e) {
        ob_end_clean();
        echo "Error: " . $e->getMessage() . "\n";
    }
    
} catch (Exception $e) {
    echo "Error de conexión: " . $e->getMessage() . "\n";
}
?>
