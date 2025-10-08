<?php
/**
 * Modelo para manejar configuraciones del sitio
 */
class SiteSettings {
    
    /**
     * Obtener conexión a la base de datos
     */
    private static function getDb() {
        require_once __DIR__ . '/../config/database.php';
        $database = new Database();
        return $database->getConnection();
    }
    
    /**
     * Crear tabla si no existe
     */
    private static function createTableIfNotExists() {
        $sql = "CREATE TABLE IF NOT EXISTS site_settings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            setting_key VARCHAR(100) NOT NULL UNIQUE,
            setting_value TEXT NOT NULL,
            setting_type ENUM('text', 'number', 'textarea', 'boolean') DEFAULT 'text',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_setting_key (setting_key)
        )";
        
        try {
            $db = self::getDb();
            $db->exec($sql);
        } catch (Exception $e) {
            error_log("Error creando tabla site_settings: " . $e->getMessage());
        }
    }
    
    /**
     * Obtener una configuración específica
     */
    public static function get($key, $default = null) {
        try {
            self::createTableIfNotExists();
            $db = self::getDb();
            $stmt = $db->prepare("SELECT setting_value FROM site_settings WHERE setting_key = ?");
            $stmt->execute([$key]);
            $result = $stmt->fetchColumn();
            
            return $result !== false ? $result : $default;
        } catch (Exception $e) {
            error_log("Error obteniendo configuración $key: " . $e->getMessage());
            return $default;
        }
    }
    
    /**
     * Establecer una configuración específica
     */
    public static function set($key, $value) {
        try {
            self::createTableIfNotExists();
            $db = self::getDb();
            $stmt = $db->prepare("
                INSERT INTO site_settings (setting_key, setting_value) 
                VALUES (?, ?) 
                ON DUPLICATE KEY UPDATE 
                setting_value = VALUES(setting_value), 
                updated_at = CURRENT_TIMESTAMP
            ");
            return $stmt->execute([$key, $value]);
        } catch (Exception $e) {
            error_log("Error estableciendo configuración $key: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obtener todas las configuraciones de WhatsApp
     */
    public static function getWhatsAppSettings() {
        try {
            self::createTableIfNotExists();
            $db = self::getDb();
            $stmt = $db->prepare("
                SELECT setting_key, setting_value 
                FROM site_settings 
                WHERE setting_key IN ('whatsapp_number', 'whatsapp_message_template', 'store_name', 'tax_rate', 'shipping_cost', 'shipping_free_threshold')
            ");
            $stmt->execute();
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $settings = [];
            foreach ($results as $row) {
                $settings[$row['setting_key']] = $row['setting_value'];
            }
            
            // Valores por defecto si no existen
            $defaults = [
                'whatsapp_number' => '3022387799',
                'store_name' => 'Odisea Makeup',
                'tax_rate' => 19,
                'shipping_cost' => 15000,
                'shipping_free_threshold' => 50000,
                'whatsapp_message_template' => self::getDefaultTemplate()
            ];
            
            return array_merge($defaults, $settings);
        } catch (Exception $e) {
            error_log("Error obteniendo configuraciones de WhatsApp: " . $e->getMessage());
            return [
                'whatsapp_number' => '3022387799',
                'store_name' => 'Odisea Makeup',
                'tax_rate' => 19,
                'shipping_cost' => 15000,
                'shipping_free_threshold' => 50000,
                'whatsapp_message_template' => self::getDefaultTemplate()
            ];
        }
    }
    
    /**
     * Actualizar configuraciones de WhatsApp
     */
    public static function updateWhatsAppSettings($settings) {
        try {
            self::createTableIfNotExists();
            $db = self::getDb();
            $db->beginTransaction();
            
            $stmt = $db->prepare("
                INSERT INTO site_settings (setting_key, setting_value) 
                VALUES (?, ?) 
                ON DUPLICATE KEY UPDATE 
                setting_value = VALUES(setting_value), 
                updated_at = CURRENT_TIMESTAMP
            ");
            
            foreach ($settings as $key => $value) {
                if (in_array($key, ['whatsapp_number', 'whatsapp_message_template', 'store_name', 'tax_rate', 'shipping_cost'])) {
                    $stmt->execute([$key, $value]);
                }
            }
            
            $db->commit();
            return true;
        } catch (Exception $e) {
            if (isset($db)) {
                $db->rollback();
            }
            error_log("Error actualizando configuraciones de WhatsApp: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Plantilla por defecto
     */
    private static function getDefaultTemplate() {
        return '🛍️ *NUEVO PEDIDO - {STORE_NAME}*

📋 *Número de Pedido:* #{ORDER_NUMBER}
📅 *Fecha:* {DATE}

👤 *DATOS DEL CLIENTE*
• *Nombre:* {CUSTOMER_NAME}
• *Teléfono:* {CUSTOMER_PHONE}
• *Email:* {CUSTOMER_EMAIL}
• *Cédula:* {CUSTOMER_CEDULA}

📍 *DIRECCIÓN DE ENVÍO*
• *Departamento:* {SHIPPING_DEPARTMENT}
• *Ciudad:* {SHIPPING_CITY}
• *Dirección:* {SHIPPING_ADDRESS}

🛒 *PRODUCTOS PEDIDOS*
{PRODUCTS_LIST}

💰 *RESUMEN DE COSTOS*
• Subtotal: ${SUBTOTAL}
• IVA ({TAX_RATE}%): ${TAX}
• Envío: {SHIPPING}
• *TOTAL: ${TOTAL}*

💳 *MÉTODO DE PAGO*
Pago contra entrega 🚚
(Efectivo o transferencia al recibir)

{NOTES}✅ *¡Hola! Este es mi pedido desde la página web.*
¿Podrías confirmarme la disponibilidad y tiempo de entrega?

¡Gracias! 😊';
    }
}
?>