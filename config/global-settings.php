<?php
/**
 * Configuraciones Globales del Sitio
 * Este archivo carga las configuraciones desde la base de datos y las hace disponibles globalmente
 */

// Solo incluir si no se ha incluido antes
if (!defined('GLOBAL_SETTINGS_LOADED')) {
    define('GLOBAL_SETTINGS_LOADED', true);
    
    // Incluir dependencias
    require_once __DIR__ . '/database.php';
    require_once __DIR__ . '/../models/Settings.php';
    
    // Inicializar modelo de configuraciones
    $settingsModel = new Settings();
    
    // Obtener todas las configuraciones
    $GLOBALS['site_settings'] = $settingsModel->getAllSettings();
    
    // Crear constantes para configuraciones críticas solo si no están ya definidas
    if (!defined('SITE_NAME')) {
        if (!empty($GLOBALS['site_settings']['site_name'])) {
            define('SITE_NAME', $GLOBALS['site_settings']['site_name']);
        } else {
            define('SITE_NAME', 'Odisea Makeup Store');
        }
    }
    
    if (!defined('SITE_DESCRIPTION')) {
        if (!empty($GLOBALS['site_settings']['site_description'])) {
            define('SITE_DESCRIPTION', $GLOBALS['site_settings']['site_description']);
        } else {
            define('SITE_DESCRIPTION', 'Tu tienda de maquillaje y belleza de confianza');
        }
    }
    
    if (!defined('SITE_EMAIL')) {
        if (!empty($GLOBALS['site_settings']['site_email'])) {
            define('SITE_EMAIL', $GLOBALS['site_settings']['site_email']);
        } else {
            define('SITE_EMAIL', 'contacto@odisea.com');
        }
    }
    
    if (!defined('SITE_PHONE')) {
        if (!empty($GLOBALS['site_settings']['site_phone'])) {
            define('SITE_PHONE', $GLOBALS['site_settings']['site_phone']);
        } else {
            define('SITE_PHONE', '+57 300 123 4567');
        }
    }
    
    // Definir BASE_URL solo si no está ya definida
    if (!defined('BASE_URL')) {
        if (!empty($GLOBALS['site_settings']['base_url'])) {
            define('BASE_URL', $GLOBALS['site_settings']['base_url']);
        } else {
            // Obtener automáticamente la URL base
            if (php_sapi_name() === 'cli') {
                // Para CLI, usar configuración por defecto
                define('BASE_URL', 'http://localhost/odisea-makeup-store');
            } else {
                $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
                $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
                $scriptPath = $_SERVER['SCRIPT_NAME'] ?? '/index.php';
                $path = dirname($scriptPath);
                $path = $path === '/' ? '' : $path;
                define('BASE_URL', $protocol . '://' . $host . $path);
            }
        }
    }
    
    // Función helper para obtener configuraciones
    if (!function_exists('getSetting')) {
        function getSetting($key, $default = '') {
            return isset($GLOBALS['site_settings'][$key]) ? $GLOBALS['site_settings'][$key] : $default;
        }
    }
    
    // Función helper para obtener configuraciones de redes sociales
    if (!function_exists('getSocialSettings')) {
        function getSocialSettings() {
            $socials = [];
            $socialKeys = ['social_facebook', 'social_instagram', 'social_twitter', 'social_youtube', 'social_tiktok'];
            
            foreach ($socialKeys as $key) {
                if (!empty($GLOBALS['site_settings'][$key])) {
                    $socials[str_replace('social_', '', $key)] = $GLOBALS['site_settings'][$key];
                }
            }
            
            return $socials;
        }
    }
    
    // Función helper para obtener configuraciones de contacto
    if (!function_exists('getContactInfo')) {
        function getContactInfo() {
            return [
                'email' => getSetting('site_email', 'contacto@odisea.com'),
                'phone' => getSetting('site_phone', '+57 300 123 4567'),
                'address' => getSetting('site_address', 'Barranquilla, Colombia'),
                'whatsapp_number' => str_replace(['+', ' ', '-'], '', getSetting('site_phone', '573001234567'))
            ];
        }
    }
}
