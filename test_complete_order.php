<?php
/**
 * Test que replica exactamente el flujo de finalizar-pedido.php
 */

// Configurar sesión
session_start();

// Simular carrito con un producto real
require_once 'config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Obtener un producto activo con stock
    $stmt = $db->query("SELECT id, name, stock, price FROM products WHERE status = 'active' AND stock > 0 LIMIT 1");
    $product = $stmt->fetch();
    
    if (!$product) {
        throw new Exception("No hay productos disponibles para probar");
    }
    
    // Configurar carrito como lo hace el sistema real
    $_SESSION['cart'] = [
        [
            'id' => $product['id'],
            'name' => $product['name'],
            'price' => $product['price'],
            'compare_price' => $product['price'],
            'discount_percentage' => 0,
            'is_on_sale' => false,
            'quantity' => 1,
            'image' => 'uploads/products/default.jpg',
            'variant' => null
        ]
    ];
    
    echo "<h2>🛒 Test Completo - Finalizar Pedido</h2>";
    echo "<p><strong>Producto en carrito:</strong> {$product['name']} (ID: {$product['id']}, Stock: {$product['stock']})</p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test - Finalizar Pedido</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .form-group { margin: 10px 0; }
        label { display: block; font-weight: bold; margin-bottom: 5px; }
        input, select, textarea { width: 300px; padding: 8px; border: 1px solid #ddd; border-radius: 4px; }
        button { background: #007cba; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; }
        button:hover { background: #005a87; }
        button:disabled { background: #ccc; cursor: not-allowed; }
        .result { margin: 20px 0; padding: 15px; border-radius: 5px; }
        .success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .loading { background: #fff3cd; color: #856404; border: 1px solid #ffeaa7; }
    </style>
</head>
<body>
    
    <form id="testForm">
        <h3>📋 Datos del Cliente</h3>
        
        <div class="form-group">
            <label>Nombre *</label>
            <input type="text" name="firstName" value="María" required>
        </div>
        
        <div class="form-group">
            <label>Apellido *</label>
            <input type="text" name="lastName" value="González" required>
        </div>
        
        <div class="form-group">
            <label>Teléfono *</label>
            <input type="tel" name="phone" value="3134567890" required>
        </div>
        
        <div class="form-group">
            <label>Email *</label>
            <input type="email" name="email" value="maria@test.com" required>
        </div>
        
        <div class="form-group">
            <label>Departamento *</label>
            <select name="department" required>
                <option value="Cundinamarca">Cundinamarca</option>
            </select>
        </div>
        
        <div class="form-group">
            <label>Ciudad *</label>
            <input type="text" name="city" value="Bogotá" required>
        </div>
        
        <div class="form-group">
            <label>Dirección *</label>
            <textarea name="address" required>Carrera 15 #34-56, Apto 301</textarea>
        </div>
        
        <div class="form-group">
            <label>Cédula</label>
            <input type="text" name="cedula" value="1234567890">
        </div>
        
        <div class="form-group">
            <label>Notas adicionales</label>
            <textarea name="notes">Pedido de prueba - Test del sistema</textarea>
        </div>
        
        <div class="form-group">
            <label>
                <input type="checkbox" name="terms" required> 
                Acepto los términos y condiciones *
            </label>
        </div>
        
        <button type="submit" id="submitBtn">🚀 Procesar Pedido</button>
    </form>
    
    <div id="result"></div>
    
    <script>
        document.getElementById('testForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const form = this;
            const submitBtn = document.getElementById('submitBtn');
            const resultDiv = document.getElementById('result');
            
            // Mostrar loading
            submitBtn.disabled = true;
            submitBtn.textContent = '⏳ Procesando...';
            resultDiv.innerHTML = '<div class="result loading">🔄 Enviando pedido al servidor...</div>';
            
            try {
                // Enviar formulario
                const formData = new FormData(form);
                
                console.log('Enviando datos:', Object.fromEntries(formData));
                
                const response = await fetch('procesar-pedido.php', {
                    method: 'POST',
                    body: formData
                });
                
                console.log('Response status:', response.status);
                console.log('Response headers:', response.headers);
                
                // Intentar obtener el texto raw primero
                const responseText = await response.text();
                console.log('Response text:', responseText);
                
                resultDiv.innerHTML += '<div class="result"><strong>📄 Respuesta RAW:</strong><br><pre>' + 
                                      escapeHtml(responseText) + '</pre></div>';
                
                // Intentar parsear JSON
                let data;
                try {
                    data = JSON.parse(responseText);
                    resultDiv.innerHTML += '<div class="result success">✅ JSON válido parseado</div>';
                } catch (jsonError) {
                    resultDiv.innerHTML += '<div class="result error">❌ Error parseando JSON: ' + jsonError.message + '</div>';
                    throw new Error('Respuesta no es JSON válido: ' + jsonError.message);
                }
                
                if (data.success) {
                    resultDiv.innerHTML += '<div class="result success">' +
                        '<h4>🎉 ¡Pedido creado exitosamente!</h4>' +
                        '<p><strong>Order ID:</strong> ' + data.order_id + '</p>' +
                        '<p><strong>WhatsApp URL:</strong> <a href="' + data.whatsapp_url + '" target="_blank">Ver mensaje</a></p>' +
                        '</div>';
                } else {
                    throw new Error(data.message || 'Error desconocido');
                }
                
            } catch (error) {
                console.error('Error:', error);
                resultDiv.innerHTML += '<div class="result error">' +
                    '<h4>❌ Error procesando pedido</h4>' +
                    '<p><strong>Error:</strong> ' + error.message + '</p>' +
                    '</div>';
            } finally {
                submitBtn.disabled = false;
                submitBtn.textContent = '🚀 Procesar Pedido';
            }
        });
        
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
    </script>
</body>
</html>
