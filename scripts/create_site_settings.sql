-- Crear tabla de configuraciones para WhatsApp y mensajes
CREATE TABLE IF NOT EXISTS site_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT NOT NULL,
    setting_type ENUM('text', 'number', 'textarea', 'boolean') DEFAULT 'text',
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_setting_key (setting_key)
);

-- Insertar configuraciones predeterminadas
INSERT INTO site_settings (setting_key, setting_value, setting_type, description) VALUES
('whatsapp_number', '573123456789', 'text', 'Número de WhatsApp para recibir pedidos (formato: código país + número sin +)'),
('whatsapp_message_template', '🛍️ *NUEVO PEDIDO - {STORE_NAME}*\n\n📋 *Número de Pedido:* #{ORDER_NUMBER}\n📅 *Fecha:* {DATE}\n\n👤 *DATOS DEL CLIENTE*\n• *Nombre:* {CUSTOMER_NAME}\n• *Teléfono:* {CUSTOMER_PHONE}\n• *Email:* {CUSTOMER_EMAIL}\n• *Cédula:* {CUSTOMER_CEDULA}\n\n📍 *DIRECCIÓN DE ENVÍO*\n• *Departamento:* {SHIPPING_DEPARTMENT}\n• *Ciudad:* {SHIPPING_CITY}\n• *Dirección:* {SHIPPING_ADDRESS}\n\n🛒 *PRODUCTOS PEDIDOS*\n{PRODUCTS_LIST}\n\n💰 *RESUMEN DE COSTOS*\n• Subtotal: ${SUBTOTAL}\n• IVA (19%): ${TAX}\n• Envío: {SHIPPING}\n• *TOTAL: ${TOTAL}*\n\n💳 *MÉTODO DE PAGO*\nPago contra entrega 🚚\n(Efectivo o transferencia al recibir)\n\n{NOTES}\n✅ *¡Hola! Este es mi pedido desde la página web.*\n¿Podrías confirmarme la disponibilidad y tiempo de entrega?\n\n¡Gracias! 😊', 'textarea', 'Plantilla del mensaje de WhatsApp (usar variables entre {})'),
('store_name', 'Odisea Makeup', 'text', 'Nombre de la tienda que aparece en los mensajes'),
('shipping_free_threshold', '150000', 'number', 'Monto mínimo para envío gratis'),
('shipping_cost', '15000', 'number', 'Costo de envío estándar'),
('tax_rate', '19', 'number', 'Porcentaje de IVA (sin símbolo %)'),
('whatsapp_enabled', '1', 'boolean', 'Habilitar integración con WhatsApp');

-- ✅ Configuraciones creadas exitosamente
-- 📋 Para personalizar: Admin → Pedidos → Config. WhatsApp 