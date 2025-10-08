<?php
/**
 * Login Protection and Security Class
 */
require_once 'includes/RateLimiter.php';
require_once 'includes/InputSanitizer.php';

class LoginProtection {
    private static $maxAttempts = 5;
    private static $lockoutTime = 900; // 15 minutes
    
    public static function validateLogin($email, $password) {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        
        // Check if IP is blocked
        if (RateLimiter::isBlocked($ip)) {
            return ['success' => false, 'error' => 'IP temporarily blocked'];
        }
        
        // Check rate limiting
        if (!RateLimiter::checkLimit('login', self::$maxAttempts, self::$lockoutTime, $ip)) {
            RateLimiter::blockIP($ip, self::$lockoutTime);
            return ['success' => false, 'error' => 'Too many login attempts'];
        }
        
        // Sanitize inputs
        $email = InputSanitizer::sanitizeEmail($email);
        $password = InputSanitizer::sanitizeString($password, 100);
        
        // Validate email format
        if (!InputSanitizer::validateEmail($email)) {
            self::logFailedAttempt($email, $ip, 'Invalid email');
            return ['success' => false, 'error' => 'Invalid credentials'];
        }
        
        // Check for malicious content
        if (InputSanitizer::detectSQLInjection($email) || InputSanitizer::detectXSS($email)) {
            InputSanitizer::logSuspiciousActivity($email, 'MALICIOUS_LOGIN', $ip);
            RateLimiter::blockIP($ip, 3600);
            return ['success' => false, 'error' => 'Invalid request'];
        }
        
        // Authenticate user (implement your authentication logic here)
        $user = self::authenticateUser($email, $password);
        
        if ($user) {
            self::createSecureSession($user);
            self::logSuccessfulLogin($user['id'], $ip);
            return ['success' => true, 'user' => $user];
        } else {
            self::logFailedAttempt($email, $ip, 'Invalid credentials');
            return ['success' => false, 'error' => 'Invalid credentials'];
        }
    }
    
    private static function authenticateUser($email, $password) {
        try {
            $db = new Database();
            $conn = $db->getConnection();
            
            $stmt = $conn->prepare("SELECT id, email, password, first_name, last_name, is_active FROM customers WHERE email = ? AND is_active = 1 LIMIT 1");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            
            if ($user && password_verify($password, $user['password'])) {
                // Remove password from returned data
                unset($user['password']);
                return $user;
            }
            
            return false;
        } catch (Exception $e) {
            error_log("Login authentication error: " . $e->getMessage());
            return false;
        }
    }
    
    private static function createSecureSession($user) {
        session_regenerate_id(true);
        
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['customer_id'] = $user['id']; // For compatibility
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
        $_SESSION['login_time'] = time();
        $_SESSION['last_activity'] = time();
        $_SESSION['ip_address'] = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    }
    
    public static function validateSession() {
        if (!isset($_SESSION['user_id'])) {
            return false;
        }
        
        // Check session timeout (30 minutes)
        if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > 1800) {
            self::destroySession();
            return false;
        }
        
        // Check IP for session hijacking
        if (isset($_SESSION['ip_address']) && $_SESSION['ip_address'] !== ($_SERVER['REMOTE_ADDR'] ?? 'unknown')) {
            self::destroySession();
            return false;
        }
        
        $_SESSION['last_activity'] = time();
        return true;
    }
    
    public static function destroySession() {
        $_SESSION = [];
        
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        
        session_destroy();
    }
    
    private static function logFailedAttempt($email, $ip, $reason) {
        $logEntry = date('Y-m-d H:i:s') . " - FAILED LOGIN - Email: $email - IP: $ip - Reason: $reason" . PHP_EOL;
        
        $logDir = '_logs';
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        file_put_contents('_logs/security.log', $logEntry, FILE_APPEND | LOCK_EX);
    }
    
    private static function logSuccessfulLogin($userId, $ip) {
        $logEntry = date('Y-m-d H:i:s') . " - LOGIN SUCCESS - User: $userId - IP: $ip" . PHP_EOL;
        file_put_contents('_logs/security.log', $logEntry, FILE_APPEND | LOCK_EX);
    }
}
?>