<?php
/**
 * Input Sanitization and Validation Class
 */
class InputSanitizer {
    
    public static function sanitizeString($input, $maxLength = 255) {
        if (!is_string($input)) {
            return '';
        }
        
        // Remove null bytes
        $input = str_replace(chr(0), '', $input);
        
        // Trim whitespace
        $input = trim($input);
        
        // Limit length
        $input = substr($input, 0, $maxLength);
        
        // Remove dangerous characters
        $input = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $input);
        
        return $input;
    }
    
    public static function sanitizeEmail($email) {
        $email = self::sanitizeString($email, 320);
        return filter_var($email, FILTER_SANITIZE_EMAIL);
    }
    
    public static function sanitizeInt($input, $min = null, $max = null) {
        $int = filter_var($input, FILTER_SANITIZE_NUMBER_INT);
        $int = (int) $int;
        
        if ($min !== null && $int < $min) {
            return $min;
        }
        
        if ($max !== null && $int > $max) {
            return $max;
        }
        
        return $int;
    }
    
    public static function escapeHTML($input) {
        return htmlspecialchars($input, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
    
    public static function validateEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
    
    public static function validatePassword($password) {
        // Minimum 8 characters, at least one uppercase, one lowercase, one number
        return strlen($password) >= 8 && 
               preg_match('/[A-Z]/', $password) && 
               preg_match('/[a-z]/', $password) && 
               preg_match('/[0-9]/', $password);
    }
    
    public static function detectSQLInjection($input) {
        $input = strtolower($input);
        $sqlKeywords = ['union', 'select', 'insert', 'update', 'delete', 'drop', 'exec'];
        
        foreach ($sqlKeywords as $keyword) {
            if (strpos($input, $keyword) !== false) {
                return true;
            }
        }
        
        return false;
    }
    
    public static function detectXSS($input) {
        $xssPatterns = [
            '<script',
            'javascript:',
            'onload=',
            'onerror=',
            '<iframe'
        ];
        
        $input = strtolower($input);
        
        foreach ($xssPatterns as $pattern) {
            if (strpos($input, $pattern) !== false) {
                return true;
            }
        }
        
        return false;
    }
    
    public static function logSuspiciousActivity($input, $type, $ip = null) {
        if (!$ip) {
            $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        }
        
        $logEntry = date('Y-m-d H:i:s') . " - SUSPICIOUS - Type: $type - IP: $ip - Input: " . substr($input, 0, 100) . PHP_EOL;
        
        $logDir = '_logs';
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        file_put_contents('_logs/security.log', $logEntry, FILE_APPEND | LOCK_EX);
    }
}
?>