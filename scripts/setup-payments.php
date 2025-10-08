<?php
require_once '../config/database.php';

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    echo "Creando tabla de métodos de pago...\n";
    
    // Crear tabla payment_methods
    $sql = "CREATE TABLE IF NOT EXISTS payment_methods (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        description TEXT,
        provider VARCHAR(50),
        is_active TINYINT(1) DEFAULT 1,
        settings JSON,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $conn->exec($sql);
    echo "✓ Tabla 'payment_methods' creada exitosamente.\n";
    
    // Insertar configuraciones de pago por defecto en settings
    $paymentSettings = [
        ['paypal_enabled', '0', 'Habilitar pagos con PayPal', 'boolean'],
        ['paypal_client_id', '', 'PayPal Client ID', 'text'],
        ['paypal_secret', '', 'PayPal Client Secret', 'text'],
        ['paypal_sandbox', '1', 'PayPal modo sandbox', 'boolean'],
        ['stripe_enabled', '0', 'Habilitar pagos con Stripe', 'boolean'],
        ['stripe_public_key', '', 'Stripe Publishable Key', 'text'],
        ['stripe_secret_key', '', 'Stripe Secret Key', 'text'],
        ['stripe_webhook_secret', '', 'Stripe Webhook Secret', 'text'],
        ['bank_transfer_enabled', '1', 'Habilitar transferencia bancaria', 'boolean'],
        ['bank_account_info', 'Banco: Ejemplo Bank\nCuenta: 1234567890\nCLABE: 123456789012345678\nBeneficiario: Odisea Makeup Store', 'Información de cuenta bancaria', 'textarea'],
        ['cash_on_delivery_enabled', '1', 'Habilitar pago contraentrega', 'boolean'],
        ['cod_fee', '5.00', 'Comisión por pago contraentrega', 'number'],
        ['payment_currency', 'USD', 'Moneda de pago', 'select'],
        ['minimum_order_amount', '25.00', 'Monto mínimo de orden', 'number']
    ];
    
    echo "Insertando configuraciones de pago por defecto...\n";
    
    $stmt = $conn->prepare("INSERT INTO settings (setting_key, setting_value, description, setting_type) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), description = VALUES(description), setting_type = VALUES(setting_type)");
    
    foreach ($paymentSettings as $setting) {
        $stmt->execute($setting);
        echo "✓ Configuración '{$setting[0]}' insertada.\n";
    }
    
    echo "\n🎉 Configuración de pagos completada exitosamente!\n";
    echo "Se crearon " . count($paymentSettings) . " configuraciones de pago.\n";
    
} catch (PDOException $e) {
    echo "❌ Error al configurar pagos: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "❌ Error general: " . $e->getMessage() . "\n";
}
?>
