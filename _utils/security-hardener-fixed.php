<?php
/**
 * SECURITY HARDENING SCRIPT (Fixed Version)
 * Applies security measures to make the application impenetrable
 */

class SecurityHardener {
    private $errors = [];
    private $applied = [];
    
    public function __construct() {
        echo "🛡️  SECURITY HARDENING PROCESS\n";
        echo "==============================\n\n";
    }
    
    public function hardenAll() {
        $this->createSecurityHeaders();
        $this->createCSRFProtection();
        $this->createInputSanitizer();
        $this->createRateLimiter();
        $this->createSecureUploader();
        $this->createSecurityConfig();
        $this->createLoginProtection();
        $this->createLogsDirectory();
        $this->generateSecurityReport();
    }
    
    // 1. Security Headers
    public function createSecurityHeaders() {
        echo "🔒 Creating Security Headers...\n";
        
        $securityHeaders = '<?php
/**
 * SECURITY HEADERS - Apply to all pages
 * Include this at the top of every page
 */

// Prevent clickjacking
header("X-Frame-Options: DENY");

// Prevent MIME type sniffing
header("X-Content-Type-Options: nosniff");

// Enable XSS protection
header("X-XSS-Protection: 1; mode=block");

// Referrer policy
header("Referrer-Policy: strict-origin-when-cross-origin");

// Content Security Policy
header("Content-Security-Policy: default-src \'self\'; script-src \'self\' \'unsafe-inline\' https://cdnjs.cloudflare.com https://cdn.jsdelivr.net; style-src \'self\' \'unsafe-inline\' https://cdnjs.cloudflare.com https://fonts.googleapis.com; font-src \'self\' https://fonts.gstatic.com; img-src \'self\' data: https:; connect-src \'self\';");

// Strict Transport Security (HTTPS)
if (isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] === "on") {
    header("Strict-Transport-Security: max-age=31536000; includeSubDomains; preload");
}

// Feature Policy
header("Permissions-Policy: geolocation=(), microphone=(), camera=()");

// Remove server information
header_remove("X-Powered-By");
header_remove("Server");

?>';
        
        file_put_contents('includes/security-headers.php', $securityHeaders);
        $this->applied[] = "Security headers created";
    }
    
    // 2. CSRF Protection
    public function createCSRFProtection() {
        echo "🔒 Creating CSRF Protection...\n";
        
        $csrfClass = '<?php
/**
 * CSRF Protection Class
 */
class CSRFProtection {
    private static $tokenName = \'csrf_token\';
    private static $sessionKey = \'csrf_tokens\';
    
    public static function generateToken($formName = \'default\') {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        
        $token = bin2hex(random_bytes(32));
        
        if (!isset($_SESSION[self::$sessionKey])) {
            $_SESSION[self::$sessionKey] = [];
        }
        
        // Store token with timestamp
        $_SESSION[self::$sessionKey][$formName] = [
            \'token\' => $token,
            \'time\' => time()
        ];
        
        return $token;
    }
    
    public static function validateToken($token, $formName = \'default\') {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!isset($_SESSION[self::$sessionKey][$formName])) {
            return false;
        }
        
        $storedData = $_SESSION[self::$sessionKey][$formName];
        
        // Check if token is expired (15 minutes)
        if (time() - $storedData[\'time\'] > 900) {
            unset($_SESSION[self::$sessionKey][$formName]);
            return false;
        }
        
        // Validate token
        $isValid = hash_equals($storedData[\'token\'], $token);
        
        // Remove token after use (one-time use)
        if ($isValid) {
            unset($_SESSION[self::$sessionKey][$formName]);
        }
        
        return $isValid;
    }
    
    public static function getTokenInput($formName = \'default\') {
        $token = self::generateToken($formName);
        return "<input type=\'hidden\' name=\'" . self::$tokenName . "\' value=\'$token\'>";
    }
    
    public static function getTokenValue($formName = \'default\') {
        return self::generateToken($formName);
    }
}
?>';
        
        file_put_contents('includes/CSRFProtection.php', $csrfClass);
        $this->applied[] = "CSRF Protection class created";
    }
    
    // 3. Input Sanitizer
    public function createInputSanitizer() {
        echo "🔒 Creating Input Sanitizer...\n";
        
        $sanitizerClass = '<?php
/**
 * Input Sanitization and Validation Class
 */
class InputSanitizer {
    
    public static function sanitizeString($input, $maxLength = 255) {
        if (!is_string($input)) {
            return \'\';
        }
        
        // Remove null bytes
        $input = str_replace(chr(0), \'\', $input);
        
        // Trim whitespace
        $input = trim($input);
        
        // Limit length
        $input = substr($input, 0, $maxLength);
        
        // Remove dangerous characters
        $input = preg_replace(\'/[\\x00-\\x08\\x0B\\x0C\\x0E-\\x1F\\x7F]/\', \'\', $input);
        
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
        return htmlspecialchars($input, ENT_QUOTES | ENT_HTML5, \'UTF-8\');
    }
    
    public static function validateEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
    
    public static function validatePassword($password) {
        // Minimum 8 characters, at least one uppercase, one lowercase, one number
        return strlen($password) >= 8 && 
               preg_match(\'/[A-Z]/\', $password) && 
               preg_match(\'/[a-z]/\', $password) && 
               preg_match(\'/[0-9]/\', $password);
    }
    
    public static function detectSQLInjection($input) {
        $input = strtolower($input);
        $sqlKeywords = [\'union\', \'select\', \'insert\', \'update\', \'delete\', \'drop\', \'exec\'];
        
        foreach ($sqlKeywords as $keyword) {
            if (strpos($input, $keyword) !== false) {
                return true;
            }
        }
        
        return false;
    }
    
    public static function detectXSS($input) {
        $xssPatterns = [
            \'<script\',
            \'javascript:\',
            \'onload=\',
            \'onerror=\',
            \'<iframe\'
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
            $ip = $_SERVER[\'REMOTE_ADDR\'] ?? \'unknown\';
        }
        
        $logEntry = date(\'Y-m-d H:i:s\') . " - SUSPICIOUS - Type: $type - IP: $ip - Input: " . substr($input, 0, 100) . PHP_EOL;
        
        $logDir = \'_logs\';
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        file_put_contents(\'_logs/security.log\', $logEntry, FILE_APPEND | LOCK_EX);
    }
}
?>';
        
        file_put_contents('includes/InputSanitizer.php', $sanitizerClass);
        $this->applied[] = "Input Sanitizer class created";
    }
    
    // 4. Rate Limiter
    public function createRateLimiter() {
        echo "🔒 Creating Rate Limiter...\n";
        
        $rateLimiterClass = '<?php
/**
 * Rate Limiting Class
 */
class RateLimiter {
    private static $logFile = \'_logs/rate_limit.log\';
    
    public static function checkLimit($action, $limit = 5, $timeWindow = 300, $ip = null) {
        if (!$ip) {
            $ip = $_SERVER[\'REMOTE_ADDR\'] ?? \'unknown\';
        }
        
        $key = $action . \'_\' . $ip;
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
            $parts = explode(\'|\', $line);
            if (count($parts) >= 3 && $parts[0] === $key && (int)$parts[1] >= $since) {
                $attempts[] = (int)$parts[1];
            }
        }
        
        return $attempts;
    }
    
    private static function logAttempt($key, $timestamp) {
        $logEntry = $key . \'|\' . $timestamp . \'|\' . \'attempt\' . PHP_EOL;
        file_put_contents(self::$logFile, $logEntry, FILE_APPEND | LOCK_EX);
    }
    
    private static function logBlocked($action, $ip, $attempts) {
        $logEntry = date(\'Y-m-d H:i:s\') . " - RATE LIMIT - Action: $action - IP: $ip - Attempts: $attempts" . PHP_EOL;
        file_put_contents(\'_logs/security.log\', $logEntry, FILE_APPEND | LOCK_EX);
    }
    
    public static function blockIP($ip, $duration = 3600) {
        $blockFile = \'_logs/blocked_ips.log\';
        $logDir = dirname($blockFile);
        
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        $expiry = time() + $duration;
        $logEntry = $ip . \'|\' . $expiry . PHP_EOL;
        file_put_contents($blockFile, $logEntry, FILE_APPEND | LOCK_EX);
    }
    
    public static function isBlocked($ip = null) {
        if (!$ip) {
            $ip = $_SERVER[\'REMOTE_ADDR\'] ?? \'unknown\';
        }
        
        $blockFile = \'_logs/blocked_ips.log\';
        
        if (!file_exists($blockFile)) {
            return false;
        }
        
        $lines = file($blockFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $currentTime = time();
        $stillBlocked = [];
        
        foreach ($lines as $line) {
            $parts = explode(\'|\', $line);
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
?>';
        
        file_put_contents('includes/RateLimiter.php', $rateLimiterClass);
        $this->applied[] = "Rate Limiter class created";
    }
    
    // 5. Secure File Uploader
    public function createSecureUploader() {
        echo "🔒 Creating Secure File Uploader...\n";
        
        $uploaderClass = '<?php
/**
 * Secure File Upload Class
 */
class SecureUploader {
    private static $allowedTypes = [
        \'image/jpeg\' => \'jpg\',
        \'image/png\' => \'png\',
        \'image/gif\' => \'gif\',
        \'image/webp\' => \'webp\'
    ];
    
    private static $maxFileSize = 5242880; // 5MB
    private static $uploadDir = \'uploads/\';
    
    public static function uploadFile($file, $subDir = \'\') {
        // Check if file was uploaded without errors
        if (!isset($file[\'error\']) || $file[\'error\'] !== UPLOAD_ERR_OK) {
            return [\'success\' => false, \'error\' => \'Upload error occurred\'];
        }
        
        // Check file size
        if ($file[\'size\'] > self::$maxFileSize) {
            return [\'success\' => false, \'error\' => \'File too large\'];
        }
        
        // Check MIME type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file[\'tmp_name\']);
        finfo_close($finfo);
        
        if (!array_key_exists($mimeType, self::$allowedTypes)) {
            return [\'success\' => false, \'error\' => \'Invalid file type\'];
        }
        
        // Generate secure filename
        $extension = self::$allowedTypes[$mimeType];
        $filename = self::generateSecureFilename() . \'.\' . $extension;
        
        // Create upload directory if it does not exist
        $targetDir = self::$uploadDir . $subDir;
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0755, true);
        }
        
        $targetPath = $targetDir . $filename;
        
        // Move uploaded file
        if (move_uploaded_file($file[\'tmp_name\'], $targetPath)) {
            chmod($targetPath, 0644);
            
            return [
                \'success\' => true,
                \'filename\' => $filename,
                \'path\' => $targetPath
            ];
        } else {
            return [\'success\' => false, \'error\' => \'Failed to move uploaded file\'];
        }
    }
    
    private static function generateSecureFilename() {
        return bin2hex(random_bytes(16)) . \'_\' . time();
    }
}
?>';
        
        file_put_contents('includes/SecureUploader.php', $uploaderClass);
        $this->applied[] = "Secure File Uploader class created";
    }
    
    // 6. Security Configuration
    public function createSecurityConfig() {
        echo "🔒 Creating Security Configuration...\n";
        
        $securityConfig = '<?php
/**
 * Security Configuration File
 */
class SecurityConfig {
    
    public static function configureSession() {
        ini_set(\'session.cookie_httponly\', 1);
        ini_set(\'session.use_only_cookies\', 1);
        ini_set(\'session.cookie_secure\', isset($_SERVER[\'HTTPS\']) ? 1 : 0);
        ini_set(\'session.cookie_samesite\', \'Strict\');
        ini_set(\'session.gc_maxlifetime\', 1800);
        
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
            session_regenerate_id(true);
        }
    }
    
    public static function configurePHP() {
        error_reporting(0);
        ini_set(\'display_errors\', 0);
        ini_set(\'log_errors\', 1);
        ini_set(\'error_log\', \'_logs/php_errors.log\');
        ini_set(\'allow_url_fopen\', 0);
        ini_set(\'allow_url_include\', 0);
        ini_set(\'expose_php\', 0);
    }
    
    public static function setSecurityHeaders() {
        require_once \'includes/security-headers.php\';
    }
}

// Initialize security configuration
SecurityConfig::configurePHP();
SecurityConfig::configureSession();
?>';
        
        file_put_contents('includes/SecurityConfig.php', $securityConfig);
        $this->applied[] = "Security Configuration created";
    }
    
    // 7. Login Protection
    public function createLoginProtection() {
        echo "🔒 Creating Login Protection...\n";
        
        $loginProtection = '<?php
/**
 * Login Protection and Security Class
 */
require_once \'includes/RateLimiter.php\';
require_once \'includes/InputSanitizer.php\';

class LoginProtection {
    private static $maxAttempts = 5;
    private static $lockoutTime = 900; // 15 minutes
    
    public static function validateLogin($email, $password) {
        $ip = $_SERVER[\'REMOTE_ADDR\'] ?? \'unknown\';
        
        // Check if IP is blocked
        if (RateLimiter::isBlocked($ip)) {
            return [\'success\' => false, \'error\' => \'IP temporarily blocked\'];
        }
        
        // Check rate limiting
        if (!RateLimiter::checkLimit(\'login\', self::$maxAttempts, self::$lockoutTime, $ip)) {
            RateLimiter::blockIP($ip, self::$lockoutTime);
            return [\'success\' => false, \'error\' => \'Too many login attempts\'];
        }
        
        // Sanitize inputs
        $email = InputSanitizer::sanitizeEmail($email);
        $password = InputSanitizer::sanitizeString($password, 100);
        
        // Validate email format
        if (!InputSanitizer::validateEmail($email)) {
            self::logFailedAttempt($email, $ip, \'Invalid email\');
            return [\'success\' => false, \'error\' => \'Invalid credentials\'];
        }
        
        // Check for malicious content
        if (InputSanitizer::detectSQLInjection($email) || InputSanitizer::detectXSS($email)) {
            InputSanitizer::logSuspiciousActivity($email, \'MALICIOUS_LOGIN\', $ip);
            RateLimiter::blockIP($ip, 3600);
            return [\'success\' => false, \'error\' => \'Invalid request\'];
        }
        
        // Authenticate user (implement your authentication logic here)
        $user = self::authenticateUser($email, $password);
        
        if ($user) {
            self::createSecureSession($user);
            self::logSuccessfulLogin($user[\'id\'], $ip);
            return [\'success\' => true, \'user\' => $user];
        } else {
            self::logFailedAttempt($email, $ip, \'Invalid credentials\');
            return [\'success\' => false, \'error\' => \'Invalid credentials\'];
        }
    }
    
    private static function authenticateUser($email, $password) {
        // Implement your user authentication logic here
        // This is a placeholder - replace with your actual authentication
        return false;
    }
    
    private static function createSecureSession($user) {
        session_regenerate_id(true);
        
        $_SESSION[\'user_id\'] = $user[\'id\'];
        $_SESSION[\'user_email\'] = $user[\'email\'];
        $_SESSION[\'login_time\'] = time();
        $_SESSION[\'last_activity\'] = time();
        $_SESSION[\'ip_address\'] = $_SERVER[\'REMOTE_ADDR\'] ?? \'unknown\';
    }
    
    public static function validateSession() {
        if (!isset($_SESSION[\'user_id\'])) {
            return false;
        }
        
        // Check session timeout (30 minutes)
        if (isset($_SESSION[\'last_activity\']) && (time() - $_SESSION[\'last_activity\']) > 1800) {
            self::destroySession();
            return false;
        }
        
        // Check IP for session hijacking
        if (isset($_SESSION[\'ip_address\']) && $_SESSION[\'ip_address\'] !== ($_SERVER[\'REMOTE_ADDR\'] ?? \'unknown\')) {
            self::destroySession();
            return false;
        }
        
        $_SESSION[\'last_activity\'] = time();
        return true;
    }
    
    public static function destroySession() {
        $_SESSION = [];
        
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), \'\', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        
        session_destroy();
    }
    
    private static function logFailedAttempt($email, $ip, $reason) {
        $logEntry = date(\'Y-m-d H:i:s\') . " - FAILED LOGIN - Email: $email - IP: $ip - Reason: $reason" . PHP_EOL;
        
        $logDir = \'_logs\';
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        file_put_contents(\'_logs/security.log\', $logEntry, FILE_APPEND | LOCK_EX);
    }
    
    private static function logSuccessfulLogin($userId, $ip) {
        $logEntry = date(\'Y-m-d H:i:s\') . " - LOGIN SUCCESS - User: $userId - IP: $ip" . PHP_EOL;
        file_put_contents(\'_logs/security.log\', $logEntry, FILE_APPEND | LOCK_EX);
    }
}
?>';
        
        file_put_contents('includes/LoginProtection.php', $loginProtection);
        $this->applied[] = "Login Protection class created";
    }
    
    // 8. Create Logs Directory
    public function createLogsDirectory() {
        echo "🔒 Creating Logs Directory...\n";
        
        $logDir = '_logs';
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
            $this->applied[] = "Logs directory created";
        }
        
        // Create .htaccess to protect logs
        $htaccess = 'Order Deny,Allow
Deny from all
<Files "*.log">
    Order Deny,Allow
    Deny from all
</Files>';
        
        file_put_contents($logDir . '/.htaccess', $htaccess);
        $this->applied[] = "Log protection .htaccess created";
    }
    
    // 9. Generate Security Report
    public function generateSecurityReport() {
        echo "\n📊 GENERATING SECURITY REPORT...\n";
        
        echo "\n✅ SECURITY HARDENING COMPLETED!\n";
        echo "================================\n\n";
        
        echo "📋 Applied Security Measures:\n";
        foreach ($this->applied as $measure) {
            echo "✅ $measure\n";
        }
        
        echo "\n🔧 IMPLEMENTATION CHECKLIST:\n";
        echo "□ Include security-headers.php in all pages\n";
        echo "□ Add CSRF tokens to all forms\n";
        echo "□ Validate all inputs with InputSanitizer\n";
        echo "□ Implement rate limiting on forms\n";
        echo "□ Use SecureUploader for file uploads\n";
        echo "□ Test login protection\n";
        echo "□ Configure HTTPS for production\n";
        echo "□ Monitor security logs regularly\n\n";
        
        echo "🚨 URGENT SECURITY ACTIONS:\n";
        echo "1. Add to ALL page headers: require_once 'includes/security-headers.php';\n";
        echo "2. Add CSRF to forms: echo CSRFProtection::getTokenInput('formname');\n";
        echo "3. Validate inputs: InputSanitizer::sanitizeString(\$input);\n";
        echo "4. Check rate limits: RateLimiter::checkLimit('action', 5, 300);\n";
        echo "5. Test security with: php _tests/security-test-suite.php\n\n";
    }
}

// Execute security hardening
$hardener = new SecurityHardener();
$hardener->hardenAll();
?>
