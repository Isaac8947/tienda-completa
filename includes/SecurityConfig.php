<?php
/**
 * Security Configuration File
 */
class SecurityConfig {
    
    public static function configureSession() {
        ini_set('session.cookie_httponly', 1);
        ini_set('session.use_only_cookies', 1);
        ini_set('session.cookie_secure', isset($_SERVER['HTTPS']) ? 1 : 0);
        ini_set('session.cookie_samesite', 'Strict');
        ini_set('session.gc_maxlifetime', 1800);
        
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
            session_regenerate_id(true);
        }
    }
    
    public static function configurePHP() {
        error_reporting(0);
        ini_set('display_errors', 0);
        ini_set('log_errors', 1);
        ini_set('error_log', '_logs/php_errors.log');
        ini_set('allow_url_fopen', 0);
        ini_set('allow_url_include', 0);
        ini_set('expose_php', 0);
    }
    
    public static function setSecurityHeaders() {
        require_once 'includes/security-headers.php';
    }
}

// Initialize security configuration
SecurityConfig::configurePHP();
SecurityConfig::configureSession();
?>