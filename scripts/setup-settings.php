<?php
require_once '../config/database.php';

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    echo "Creando tabla de configuraciones...\n";
    
    // Crear tabla settings
    $sql = "CREATE TABLE IF NOT EXISTS settings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        setting_key VARCHAR(100) NOT NULL UNIQUE,
        setting_value TEXT,
        description TEXT,
        setting_type ENUM('text', 'number', 'boolean', 'email', 'url', 'textarea', 'select') DEFAULT 'text',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $conn->exec($sql);
    echo "✓ Tabla 'settings' creada exitosamente.\n";
    
    // Insertar configuraciones por defecto
    $defaultSettings = [
        ['site_name', 'Odisea Makeup Store', 'Nombre del sitio web', 'text'],
        ['site_description', 'Tu tienda de maquillaje y belleza de confianza', 'Descripción del sitio', 'textarea'],
        ['site_keywords', 'maquillaje, belleza, cosméticos, makeup', 'Palabras clave SEO', 'textarea'],
        ['site_email', 'info@odiseamakeup.com', 'Email de contacto principal', 'email'],
        ['site_phone', '+1 234 567 8900', 'Teléfono de contacto', 'text'],
        ['site_address', '123 Beauty Street, Makeup City, MC 12345', 'Dirección física', 'textarea'],
        ['currency', 'USD', 'Moneda del sitio', 'text'],
        ['currency_symbol', '$', 'Símbolo de moneda', 'text'],
        ['timezone', 'America/New_York', 'Zona horaria', 'text'],
        ['items_per_page', '12', 'Productos por página', 'number'],
        ['enable_reviews', '1', 'Habilitar reseñas de productos', 'boolean'],
        ['enable_wishlist', '1', 'Habilitar lista de deseos', 'boolean'],
        ['enable_newsletter', '1', 'Habilitar newsletter', 'boolean'],
        ['min_order_amount', '25.00', 'Monto mínimo de pedido', 'number'],
        ['shipping_cost', '5.99', 'Costo de envío estándar', 'number'],
        ['free_shipping_threshold', '100.00', 'Monto para envío gratis', 'number'],
        ['tax_rate', '8.5', 'Tasa de impuesto (%)', 'number'],
        ['social_facebook', 'https://facebook.com/odiseamakeup', 'URL de Facebook', 'url'],
        ['social_instagram', 'https://instagram.com/odiseamakeup', 'URL de Instagram', 'url'],
        ['social_twitter', 'https://twitter.com/odiseamakeup', 'URL de Twitter', 'url'],
        ['social_youtube', 'https://youtube.com/odiseamakeup', 'URL de YouTube', 'url'],
        ['google_analytics', '', 'ID de Google Analytics', 'text'],
        ['facebook_pixel', '', 'ID de Facebook Pixel', 'text'],
        ['maintenance_mode', '0', 'Modo mantenimiento', 'boolean'],
        ['maintenance_message', 'Sitio en mantenimiento. Volveremos pronto.', 'Mensaje de mantenimiento', 'textarea'],
        ['smtp_host', '', 'Servidor SMTP', 'text'],
        ['smtp_port', '587', 'Puerto SMTP', 'number'],
        ['smtp_username', '', 'Usuario SMTP', 'text'],
        ['smtp_password', '', 'Contraseña SMTP', 'text'],
        ['smtp_encryption', 'tls', 'Encriptación SMTP', 'select']
    ];
    
    echo "Insertando configuraciones por defecto...\n";
    
    $stmt = $conn->prepare("INSERT INTO settings (setting_key, setting_value, description, setting_type) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), description = VALUES(description), setting_type = VALUES(setting_type)");
    
    foreach ($defaultSettings as $setting) {
        $stmt->execute($setting);
        echo "✓ Configuración '{$setting[0]}' insertada.\n";
    }
    
    echo "\n🎉 Configuración de settings completada exitosamente!\n";
    echo "Se crearon " . count($defaultSettings) . " configuraciones por defecto.\n";
    
} catch (PDOException $e) {
    echo "❌ Error al configurar settings: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "❌ Error general: " . $e->getMessage() . "\n";
}
?>
