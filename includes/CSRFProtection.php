<?php
/**
 * CSRF Protection Class
 */
class CSRFProtection {
    private static $tokenName = 'csrf_token';
    private static $sessionKey = 'csrf_tokens';
    
    public static function generateToken($formName = 'default') {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        
        $token = bin2hex(random_bytes(32));
        
        if (!isset($_SESSION[self::$sessionKey])) {
            $_SESSION[self::$sessionKey] = [];
        }
        
        // Store token with timestamp
        $_SESSION[self::$sessionKey][$formName] = [
            'token' => $token,
            'time' => time()
        ];
        
        return $token;
    }
    
    public static function validateToken($token, $formName = 'default', $oneTimeUse = true) {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION[self::$sessionKey][$formName])) {
            return false;
        }

        $storedData = $_SESSION[self::$sessionKey][$formName];

        // Check if token is expired (15 minutes)
        if (time() - $storedData['time'] > 900) {
            unset($_SESSION[self::$sessionKey][$formName]);
            return false;
        }

        // Validate token
        $isValid = hash_equals($storedData['token'], $token);

        // Remove token after use (solo si oneTimeUse es true)
        if ($isValid && $oneTimeUse) {
            unset($_SESSION[self::$sessionKey][$formName]);
        }

        return $isValid;
    }
    
    // Método para generar token global reutilizable
    public static function generateGlobalToken() {
        return self::generateToken('global');
    }
    
    // Método para validar token global (reutilizable)
    public static function validateGlobalToken($token) {
        return self::validateToken($token, 'global', false); // No eliminar después del uso
    }    public static function getTokenInput($formName = 'default') {
        $token = self::generateToken($formName);
        return "<input type='hidden' name='" . self::$tokenName . "' value='$token'>";
    }
    
    public static function getTokenValue($formName = 'default') {
        return self::generateToken($formName);
    }
}
?>