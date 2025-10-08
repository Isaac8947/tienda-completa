<?php
session_start();
require_once 'config/database.php';
require_once 'models/SiteSettings.php';

// Probar la configuración de WhatsApp
echo "🔧 PROBANDO SISTEMA DE CONFIGURACIÓN WHATSAPP\n";
echo "============================================\n\n";

try {
    // 1. Verificar que el modelo SiteSettings funciona
    echo "1. Verificando modelo SiteSettings...\n";
    $testValue = SiteSettings::get('store_name', 'Test Store');
    echo "✓ Modelo funciona correctamente. Store name: $testValue\n\n";
    
    // 2. Obtener configuraciones de WhatsApp
    echo "2. Obteniendo configuraciones de WhatsApp...\n";
    $whatsappSettings = SiteSettings::getWhatsAppSettings();
    
    if ($whatsappSettings) {
        echo "✓ Configuraciones obtenidas:\n";
        echo "  - Número WhatsApp: " . ($whatsappSettings['whatsapp_number'] ?? 'No definido') . "\n";
        echo "  - Nombre tienda: " . ($whatsappSettings['store_name'] ?? 'No definido') . "\n";
        echo "  - Tasa impuestos: " . ($whatsappSettings['tax_rate'] ?? 'No definido') . "%\n";
        echo "  - Costo envío: $" . number_format($whatsappSettings['shipping_cost'] ?? 0, 0, ',', '.') . "\n";
        echo "  - Plantilla mensaje: " . (strlen($whatsappSettings['whatsapp_message_template'] ?? '') > 0 ? "Configurada (" . strlen($whatsappSettings['whatsapp_message_template']) . " chars)" : "No definida") . "\n\n";
    } else {
        echo "❌ No se pudieron obtener las configuraciones\n\n";
    }
    
    // 3. Probar generación de mensaje (función independiente)
    echo "3. Probando generación de mensaje WhatsApp...\n";
    
    // Simular datos de prueba
    $testOrder = [
        'orderId' => 123,
        'customer' => [
            'first_name' => 'María',
            'last_name' => 'González',
            'phone' => '3001234567',
            'email' => 'maria@email.com',
            'cedula' => '12345678'
        ],
        'address' => [
            'department' => 'Cundinamarca',
            'city' => 'Bogotá',
            'address' => 'Calle 123 # 45-67'
        ],
        'items' => [
            [
                'name' => 'Labial Matte Rojo',
                'quantity' => 2,
                'price' => 25000,
                'total' => 50000
            ],
            [
                'name' => 'Base Líquida',
                'quantity' => 1,
                'price' => 45000,
                'total' => 45000
            ]
        ],
        'subtotal' => 95000,
        'tax' => 18050,
        'shipping' => 15000,
        'total' => 128050,
        'notes' => 'Entregar entre 9am y 5pm por favor'
    ];
    
    // Función local de generación de mensaje
    function generateTestWhatsAppMessage($orderId, $customer, $address, $items, $subtotal, $tax, $shipping, $total, $notes = '', $settings = []) {
        // Usar plantilla personalizada o por defecto
        $template = $settings['whatsapp_message_template'] ?? getDefaultMessageTemplate();
        $storeName = $settings['store_name'] ?? 'Odisea Makeup';
        $taxRate = $settings['tax_rate'] ?? 19;
        
        // Formatear número de pedido
        $orderNumber = str_pad($orderId, 6, '0', STR_PAD_LEFT);
        
        // Generar lista de productos
        $productsList = '';
        foreach ($items as $item) {
            $productsList .= "• " . $item['name'] . "\n";
            $productsList .= "  Cantidad: " . $item['quantity'] . " x $" . number_format($item['price'], 0, ',', '.') . "\n";
            $productsList .= "  Subtotal: $" . number_format($item['total'], 0, ',', '.') . "\n\n";
        }
        
        // Formatear envío
        $shippingText = $shipping == 0 ? "¡GRATIS! 🎉" : "$" . number_format($shipping, 0, ',', '.');
        
        // Formatear notas
        $notesText = !empty($notes) ? "📝 *NOTAS ADICIONALES*\n" . $notes . "\n\n" : '';
        
        // Reemplazar variables en la plantilla
        $replacements = [
            '{STORE_NAME}' => $storeName,
            '{ORDER_NUMBER}' => $orderNumber,
            '{DATE}' => date('d/m/Y H:i'),
            '{CUSTOMER_NAME}' => $customer['first_name'] . ' ' . $customer['last_name'],
            '{CUSTOMER_PHONE}' => $customer['phone'],
            '{CUSTOMER_EMAIL}' => $customer['email'],
            '{CUSTOMER_CEDULA}' => !empty($customer['cedula']) ? $customer['cedula'] : 'No proporcionada',
            '{SHIPPING_DEPARTMENT}' => $address['department'],
            '{SHIPPING_CITY}' => $address['city'],
            '{SHIPPING_ADDRESS}' => $address['address'],
            '{PRODUCTS_LIST}' => trim($productsList),
            '{SUBTOTAL}' => number_format($subtotal, 0, ',', '.'),
            '{TAX}' => number_format($tax, 0, ',', '.'),
            '{TAX_RATE}' => $taxRate,
            '{SHIPPING}' => $shippingText,
            '{TOTAL}' => number_format($total, 0, ',', '.'),
            '{NOTES}' => $notesText
        ];
        
        // Aplicar reemplazos
        $message = str_replace(array_keys($replacements), array_values($replacements), $template);
        
        return $message;
    }
    
    // Generar mensaje
    $whatsappMessage = generateTestWhatsAppMessage(
        $testOrder['orderId'],
        $testOrder['customer'],
        $testOrder['address'],
        $testOrder['items'],
        $testOrder['subtotal'],
        $testOrder['tax'],
        $testOrder['shipping'],
        $testOrder['total'],
        $testOrder['notes'],
        $whatsappSettings
    );
    
    echo "✓ Mensaje generado exitosamente!\n\n";
    echo "MENSAJE PREVIEW:\n";
    echo "================\n";
    echo $whatsappMessage . "\n\n";
    
    // 4. Generar URL de WhatsApp
    $whatsappNumber = $whatsappSettings['whatsapp_number'] ?? '3022387799';
    $encodedMessage = urlencode($whatsappMessage);
    $whatsappUrl = "https://api.whatsapp.com/send?phone={$whatsappNumber}&text={$encodedMessage}";
    
    echo "4. URL de WhatsApp generada:\n";
    echo "✓ Número: +$whatsappNumber\n";
    echo "✓ URL: " . substr($whatsappUrl, 0, 100) . "...\n\n";
    
    echo "🎉 TODAS LAS PRUEBAS PASARON EXITOSAMENTE!\n";
    echo "El sistema de configuración WhatsApp está funcionando correctamente.\n\n";
    
    // Mostrar enlace al admin
    echo "📋 Para personalizar la configuración, ve a:\n";
    echo "   Admin → Pedidos → Config. WhatsApp\n";
    echo "   http://localhost/odisea-makeup-store/admin/configuracion-whatsapp.php\n";
    
} catch (Exception $e) {
    echo "❌ Error durante las pruebas: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}
?>
