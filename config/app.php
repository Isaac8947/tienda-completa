<?php
/**
 * Configuración principal de la aplicación
 * 
 * Este archivo contiene todas las configuraciones generales
 * de la aplicación Odisea Makeup Store.
 * 
 * @author Isaac8947 (catla6273@gmail.com)
 * @version 2.0
 */

// Configuración del entorno
define('ENVIRONMENT', 'development'); // development, production, testing

// Configuración de la aplicación
define('APP_NAME', 'Odisea Makeup Store');
define('APP_VERSION', '2.0');
define('APP_AUTHOR', 'Isaac8947');
define('APP_EMAIL', 'catla6273@gmail.com');

// Configuración de URLs
define('BASE_URL', 'http://localhost/odisea-makeup-store/');
define('ASSETS_URL', BASE_URL . 'public/assets/');
define('UPLOADS_URL', BASE_URL . 'public/uploads/');

// Configuración de sesiones
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 0); // Cambiar a 1 en HTTPS
session_start();

// Configuración de timezone
date_default_timezone_set('America/Mexico_City');

// Configuración de errores según el entorno
if (ENVIRONMENT === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    ini_set('log_errors', 1);
    ini_set('error_log', STORAGE_PATH . '/logs/php_errors.log');
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    ini_set('error_log', STORAGE_PATH . '/logs/php_errors.log');
}

// Configuración de caché
define('CACHE_ENABLED', true);
define('CACHE_TIME', 3600); // 1 hora

// Configuración de límites
define('MAX_UPLOAD_SIZE', '10M');
define('MAX_CART_ITEMS', 50);
define('MAX_LOGIN_ATTEMPTS', 5);

// Configuración de paginación
define('PRODUCTS_PER_PAGE', 12);
define('ORDERS_PER_PAGE', 10);

// Configuración de email (para futuro)
define('SMTP_HOST', 'localhost');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', '');
define('SMTP_PASSWORD', '');

// Configuración de WhatsApp
define('WHATSAPP_NUMBER', '+1234567890');
define('WHATSAPP_MESSAGE_TEMPLATE', 'Hola, me interesa el producto: %s');

// Claves de encriptación (cambiar en producción)
define('ENCRYPTION_KEY', 'your-secret-encryption-key-here');
define('HASH_SALT', 'your-salt-here');

// Configuración de redes sociales
define('FACEBOOK_URL', 'https://facebook.com/odiseamakeup');
define('INSTAGRAM_URL', 'https://instagram.com/odiseamakeup');
define('TWITTER_URL', 'https://twitter.com/odiseamakeup');

// Configuración de moneda
define('CURRENCY_SYMBOL', '$');
define('CURRENCY_CODE', 'USD');

// Configuración de idioma
define('DEFAULT_LANGUAGE', 'es');
define('DEFAULT_CHARSET', 'UTF-8');

// Headers de seguridad por defecto
if (!headers_sent()) {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('X-XSS-Protection: 1; mode=block');
    header('Referrer-Policy: strict-origin-when-cross-origin');
}
?>