<?php
/**
 * Rate Limiting Class
 */
class RateLimiter {
    private static $logFile = '_logs/rate_limit.log';
    
    public static function checkLimit($action, $limit = 5, $timeWindow = 300, $ip = null) {
        if (!$ip) {
            $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        }
        
        $key = $action . '_' . $ip;
        $currentTime = time();
        
        // Create logs directory if it does not exist
        $logDir = dirname(self::$logFile);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        // Read existing attempts
        $attempts = self::getAttempts($key, $currentTime - $timeWindow);
        
        // Check if limit exceeded
        if (count($attempts) >= $limit) {
            self::logBlocked($action, $ip, count($attempts));
            return false;
        }
        
        // Log this attempt
        self::logAttempt($key, $currentTime);
        
        return true;
    }
    
    private static function getAttempts($key, $since) {
        if (!file_exists(self::$logFile)) {
            return [];
        }
        
        $lines = file(self::$logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $attempts = [];
        
        foreach ($lines as $line) {
            $parts = explode('|', $line);
            if (count($parts) >= 3 && $parts[0] === $key && (int)$parts[1] >= $since) {
                $attempts[] = (int)$parts[1];
            }
        }
        
        return $attempts;
    }
    
    private static function logAttempt($key, $timestamp) {
        $logEntry = $key . '|' . $timestamp . '|' . 'attempt' . PHP_EOL;
        file_put_contents(self::$logFile, $logEntry, FILE_APPEND | LOCK_EX);
    }
    
    private static function logBlocked($action, $ip, $attempts) {
        $logEntry = date('Y-m-d H:i:s') . " - RATE LIMIT - Action: $action - IP: $ip - Attempts: $attempts" . PHP_EOL;
        file_put_contents('_logs/security.log', $logEntry, FILE_APPEND | LOCK_EX);
    }
    
    public static function blockIP($ip, $duration = 3600) {
        $blockFile = '_logs/blocked_ips.log';
        $logDir = dirname($blockFile);
        
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        $expiry = time() + $duration;
        $logEntry = $ip . '|' . $expiry . PHP_EOL;
        file_put_contents($blockFile, $logEntry, FILE_APPEND | LOCK_EX);
    }
    
    public static function isBlocked($ip = null) {
        if (!$ip) {
            $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        }
        
        $blockFile = '_logs/blocked_ips.log';
        
        if (!file_exists($blockFile)) {
            return false;
        }
        
        $lines = file($blockFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $currentTime = time();
        $stillBlocked = [];
        
        foreach ($lines as $line) {
            $parts = explode('|', $line);
            if (count($parts) >= 2) {
                $blockedIP = $parts[0];
                $expiry = (int)$parts[1];
                
                if ($currentTime < $expiry) {
                    $stillBlocked[] = $line;
                    
                    if ($blockedIP === $ip) {
                        return true;
                    }
                }
            }
        }
        
        // Update the blocked IPs file
        file_put_contents($blockFile, implode(PHP_EOL, $stillBlocked) . PHP_EOL);
        
        return false;
    }
}
?>