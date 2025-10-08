<?php
session_start();

// Simular datos de un pedido real
$_SESSION['cart'] = [
    1 => [
        'id' => 1,
        'name' => 'Labial Rojo Intenso',
        'price' => 25000,
        'quantity' => 2,
        'stock' => 10,
        'image' => 'labial1.jpg'
    ],
    2 => [
        'id' => 2,
        'name' => 'Base Líquida Natural',
        'price' => 45000,
        'quantity' => 1,
        'stock' => 5,
        'image' => 'base1.jpg'
    ]
];

$_SESSION['order_data'] = [
    'customer_data' => [
        'first_name' => 'Ana',
        'last_name' => 'García',
        'email' => 'ana@gmail.com',
        'phone' => '3101234567',
        'cedula' => '12345678',
        'notes' => 'Entregar en horario de oficina'
    ],
    'shipping_address' => [
        'department' => 'Bogotá D.C.',
        'city' => 'Bogotá',
        'address' => 'Carrera 10 # 20-30, Apto 501'
    ]
];

echo "✅ Sesión simulada creada\n";
echo "Carrito: " . count($_SESSION['cart']) . " productos\n";
echo "Cliente: " . $_SESSION['order_data']['customer_data']['first_name'] . " " . $_SESSION['order_data']['customer_data']['last_name'] . "\n";
echo "Email: " . $_SESSION['order_data']['customer_data']['email'] . "\n\n";

echo "📋 Para probar el pedido, haz una petición POST a:\n";
echo "http://localhost/odisea-makeup-store/procesar-pedido.php\n\n";

echo "🔗 O haz clic aquí para simular:\n";
?>

<!DOCTYPE html>
<html>
<head>
    <title>Simulador de Pedido</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 p-8">
    <div class="max-w-2xl mx-auto bg-white p-6 rounded-lg shadow">
        <h1 class="text-2xl font-bold mb-4">🛒 Simulador de Pedido - Prueba WhatsApp</h1>
        
        <div class="mb-4">
            <h3 class="font-semibold">Datos del Cliente:</h3>
            <p><?php echo $_SESSION['order_data']['customer_data']['first_name'] . " " . $_SESSION['order_data']['customer_data']['last_name']; ?></p>
            <p><?php echo $_SESSION['order_data']['customer_data']['email']; ?></p>
            <p><?php echo $_SESSION['order_data']['customer_data']['phone']; ?></p>
        </div>
        
        <div class="mb-4">
            <h3 class="font-semibold">Productos en el Carrito:</h3>
            <ul>
                <?php foreach ($_SESSION['cart'] as $item): ?>
                    <li class="ml-4">• <?php echo $item['name'] . " x" . $item['quantity'] . " - $" . number_format($item['price'], 0, ',', '.'); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        
        <div class="mb-6">
            <h3 class="font-semibold">Dirección:</h3>
            <p><?php echo $_SESSION['order_data']['shipping_address']['address']; ?></p>
            <p><?php echo $_SESSION['order_data']['shipping_address']['city'] . ", " . $_SESSION['order_data']['shipping_address']['department']; ?></p>
        </div>
        
        <button onclick="enviarPedido()" class="bg-green-600 text-white px-6 py-3 rounded-lg hover:bg-green-700 transition">
            🚀 Simular Envío de Pedido
        </button>
        
        <div id="resultado" class="mt-4"></div>
    </div>
    
    <script>
        async function enviarPedido() {
            const resultado = document.getElementById('resultado');
            resultado.innerHTML = '<div class="bg-blue-100 p-3 rounded">⏳ Procesando pedido...</div>';
            
            try {
                const response = await fetch('procesar-pedido.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({
                        action: 'create_order'
                    })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    resultado.innerHTML = `
                        <div class="bg-green-100 p-4 rounded-lg">
                            <h3 class="font-bold text-green-800">✅ Pedido Creado Exitosamente</h3>
                            <p><strong>ID Pedido:</strong> ${data.order_id}</p>
                            <p><strong>WhatsApp URL:</strong> <a href="${data.whatsapp_url}" target="_blank" class="text-blue-600 hover:underline">Abrir WhatsApp</a></p>
                            <button onclick="window.open('${data.whatsapp_url}', '_blank')" class="mt-2 bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600">
                                📱 Abrir WhatsApp
                            </button>
                        </div>
                    `;
                } else {
                    resultado.innerHTML = `
                        <div class="bg-red-100 p-4 rounded-lg">
                            <h3 class="font-bold text-red-800">❌ Error</h3>
                            <p>${data.message || data.error}</p>
                        </div>
                    `;
                }
            } catch (error) {
                resultado.innerHTML = `
                    <div class="bg-red-100 p-4 rounded-lg">
                        <h3 class="font-bold text-red-800">❌ Error de Conexión</h3>
                        <p>${error.message}</p>
                    </div>
                `;
            }
        }
    </script>
</body>
</html>
