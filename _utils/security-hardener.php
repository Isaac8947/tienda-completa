<?php
/**
 * SECURITY HARDENING SCRIPT
 * Applies security measures to make the application impenetrable
 */

class SecurityHardener {
    private $errors = [];
    private $applied = [];
    
    public function __construct() {
        echo "🛡️  SECURITY H        // Check for common SQL injection patterns
        $patterns = [
            '/(\\\')|(\')|(%27)|(\\-\\-)|(;)|(\\|)|(\\*)|(%)/',
            '/((\\%3D)|(=))[^\\n]*((\\%27)|(\')|(-)|(%2D)){2,}/',
            '/((\\%3C)|<)((\\%2F)|\/)*[a-z0-9%]+((\\%3E)|>)/',
            '/((\\%3C)|<)((\\%69)|i|(\\%49))((\\%6D)|m|(\\%4D))((\\%67)|g|(\\%47))[^\\n]+((\\%3E)|>)/'
        ];G PROCESS\n";
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
    
    public static function cleanupExpiredTokens() {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!isset($_SESSION[self::$sessionKey])) {
            return;
        }
        
        $currentTime = time();
        foreach ($_SESSION[self::$sessionKey] as $formName => $data) {
            if ($currentTime - $data[\'time\'] > 900) {
                unset($_SESSION[self::$sessionKey][$formName]);
            }
        }
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
        $input = str_replace("\0", \'\', $input);
        
        // Trim whitespace
        $input = trim($input);
        
        // Limit length
        $input = substr($input, 0, $maxLength);
        
        // Remove control characters except newlines and tabs
        $input = preg_replace(\'/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/\', \'\', $input);
        
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
    
    public static function sanitizeFloat($input, $min = null, $max = null) {
        $float = filter_var($input, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
        $float = (float) $float;
        
        if ($min !== null && $float < $min) {
            return $min;
        }
        
        if ($max !== null && $float > $max) {
            return $max;
        }
        
        return $float;
    }
    
    public static function sanitizeHTML($input) {
        $allowedTags = \'<p><br><strong><b><em><i><u><ul><ol><li><a><img>\';
        return strip_tags($input, $allowedTags);
    }
    
    public static function escapeHTML($input) {
        return htmlspecialchars($input, ENT_QUOTES | ENT_HTML5, \'UTF-8\');
    }
    
    public static function validateEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
    
    public static function validatePhone($phone) {
        // Remove all non-numeric characters
        $phone = preg_replace(\'/[^0-9]/\', \'\', $phone);
        
        // Check if length is appropriate (7-15 digits)
        return strlen($phone) >= 7 && strlen($phone) <= 15;
    }
    
    public static function validatePassword($password) {
        // Minimum 8 characters, at least one uppercase, one lowercase, one number
        return strlen($password) >= 8 && 
               preg_match(\'/[A-Z]/\', $password) && 
               preg_match(\'/[a-z]/\', $password) && 
               preg_match(\'/[0-9]/\', $password);
    }
    
    public static function detectSQLInjection($input) {
        $sqlKeywords = [
            \'union\', \'select\', \'insert\', \'update\', \'delete\', \'drop\', \'create\', \'alter\',
            \'exec\', \'execute\', \'script\', \'javascript\', \'vbscript\', \'onload\', \'onerror\'
        ];
        
        $input = strtolower($input);
        
        foreach ($sqlKeywords as $keyword) {
            if (strpos($input, $keyword) !== false) {
                return true;
            }
        }
        
        // Check for common SQL injection patterns
        $patterns = [
            \'/(\\\\\')|(\\\')|(%27)|(\\\\-\\\\-)|(;)|(\\|)|(\\*)|(%)/',
            \'/((\\%3D)|(=))[^\\n]*((\\%27)|(\\\')|(-)|(%2D)){2,}/',
            \'/((\\%3C)|<)((\\%2F)|\/)*[a-z0-9%]+((\\%3E)|>)/',
            \'/((\\%3C)|<)((\\%69)|i|(\\%49))((\\%6D)|m|(\\%4D))((\\%67)|g|(\\%47))[^\\n]+((\\%3E)|>)/'
        ];
        
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $input)) {
                return true;
            }
        }
        
        return false;
    }
    
    public static function detectXSS($input) {
        $xssPatterns = [
            \'/<script[^>]*>.*?<\/script>/is\',
            \'/<iframe[^>]*>.*?<\/iframe>/is\',
            \'/javascript:/i\',
            \'/vbscript:/i\',
            \'/onload=/i\',
            \'/onerror=/i\',
            \'/onclick=/i\',
            \'/onmouseover=/i\'
        ];
        
        foreach ($xssPatterns as $pattern) {
            if (preg_match($pattern, $input)) {
                return true;
            }
        }
        
        return false;
    }
    
    public static function logSuspiciousActivity($input, $type, $ip = null) {
        if (!$ip) {
            $ip = $_SERVER[\'REMOTE_ADDR\'] ?? \'unknown\';
        }
        
        $logEntry = date(\'Y-m-d H:i:s\') . " - SUSPICIOUS ACTIVITY - Type: $type - IP: $ip - Input: " . substr($input, 0, 200) . "\\n";
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
        
        // Create logs directory if it doesn\'t exist
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
        $logEntry = $key . \'|\' . $timestamp . \'|\' . \'attempt\' . "\\n";
        file_put_contents(self::$logFile, $logEntry, FILE_APPEND | LOCK_EX);
    }
    
    private static function logBlocked($action, $ip, $attempts) {
        $logEntry = date(\'Y-m-d H:i:s\') . " - RATE LIMIT EXCEEDED - Action: $action - IP: $ip - Attempts: $attempts\\n";
        file_put_contents(\'_logs/security.log\', $logEntry, FILE_APPEND | LOCK_EX);
    }
    
    public static function cleanupOldEntries($maxAge = 86400) {
        if (!file_exists(self::$logFile)) {
            return;
        }
        
        $lines = file(self::$logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $currentTime = time();
        $validLines = [];
        
        foreach ($lines as $line) {
            $parts = explode(\'|\', $line);
            if (count($parts) >= 3 && ($currentTime - (int)$parts[1]) < $maxAge) {
                $validLines[] = $line;
            }
        }
        
        file_put_contents(self::$logFile, implode("\\n", $validLines) . "\\n");
    }
    
    public static function blockIP($ip, $duration = 3600) {
        $blockFile = \'_logs/blocked_ips.log\';
        $logDir = dirname($blockFile);
        
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        $expiry = time() + $duration;
        $logEntry = $ip . \'|\' . $expiry . "\\n";
        file_put_contents($blockFile, $logEntry, FILE_APPEND | LOCK_EX);
        
        $securityLog = date(\'Y-m-d H:i:s\') . " - IP BLOCKED - IP: $ip - Duration: {$duration}s\\n";
        file_put_contents(\'_logs/security.log\', $securityLog, FILE_APPEND | LOCK_EX);
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
        
        // Update the blocked IPs file with only non-expired entries
        file_put_contents($blockFile, implode("\\n", $stillBlocked) . "\\n");
        
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
        
        // Validate file content (check for embedded scripts)
        if (self::containsMaliciousContent($file[\'tmp_name\'])) {
            return [\'success\' => false, \'error\' => \'File contains malicious content\'];
        }
        
        // Generate secure filename
        $extension = self::$allowedTypes[$mimeType];
        $filename = self::generateSecureFilename() . \'.\' . $extension;
        
        // Create upload directory if it doesn\'t exist
        $targetDir = self::$uploadDir . $subDir;
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0755, true);
        }
        
        $targetPath = $targetDir . $filename;
        
        // Move uploaded file
        if (move_uploaded_file($file[\'tmp_name\'], $targetPath)) {
            // Set proper permissions
            chmod($targetPath, 0644);
            
            return [
                \'success\' => true,
                \'filename\' => $filename,
                \'path\' => $targetPath,
                \'url\' => $targetPath
            ];
        } else {
            return [\'success\' => false, \'error\' => \'Failed to move uploaded file\'];
        }
    }
    
    private static function generateSecureFilename() {
        return bin2hex(random_bytes(16)) . \'_\' . time();
    }
    
    private static function containsMaliciousContent($filePath) {
        $content = file_get_contents($filePath);
        
        // Check for PHP tags
        if (strpos($content, \'<?php\') !== false || strpos($content, \'<?\') !== false) {
            return true;
        }
        
        // Check for script tags
        if (stripos($content, \'<script\') !== false) {
            return true;
        }
        
        // Check for executable content
        $maliciousPatterns = [
            \'/eval\\s*\\(/i\',
            \'/exec\\s*\\(/i\',
            \'/system\\s*\\(/i\',
            \'/shell_exec\\s*\\(/i\',
            \'/passthru\\s*\\(/i\',
            \'/base64_decode\\s*\\(/i\'
        ];
        
        foreach ($maliciousPatterns as $pattern) {
            if (preg_match($pattern, $content)) {
                return true;
            }
        }
        
        return false;
    }
    
    public static function deleteFile($filename, $subDir = \'\') {
        $filePath = self::$uploadDir . $subDir . $filename;
        
        if (file_exists($filePath) && is_file($filePath)) {
            return unlink($filePath);
        }
        
        return false;
    }
    
    public static function validateImageDimensions($filePath, $maxWidth = 2000, $maxHeight = 2000) {
        $imageInfo = getimagesize($filePath);
        
        if (!$imageInfo) {
            return false;
        }
        
        return $imageInfo[0] <= $maxWidth && $imageInfo[1] <= $maxHeight;
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
    // Session Configuration
    public static function configureSession() {
        // Session security settings
        ini_set(\'session.cookie_httponly\', 1);
        ini_set(\'session.use_only_cookies\', 1);
        ini_set(\'session.cookie_secure\', isset($_SERVER[\'HTTPS\']) ? 1 : 0);
        ini_set(\'session.cookie_samesite\', \'Strict\');
        ini_set(\'session.gc_maxlifetime\', 1800); // 30 minutes
        ini_set(\'session.gc_probability\', 1);
        ini_set(\'session.gc_divisor\', 100);
        
        // Regenerate session ID
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
            session_regenerate_id(true);
        }
    }
    
    // PHP Security Settings
    public static function configurePHP() {
        // Disable dangerous functions
        $dangerousFunctions = [
            \'exec\', \'system\', \'shell_exec\', \'passthru\', \'eval\',
            \'file_get_contents\', \'file_put_contents\', \'fopen\', \'fwrite\'
        ];
        
        // Note: These should be configured in php.ini
        // disable_functions = exec,system,shell_exec,passthru,eval
        
        // Error reporting (hide in production)
        error_reporting(0);
        ini_set(\'display_errors\', 0);
        ini_set(\'log_errors\', 1);
        ini_set(\'error_log\', \'_logs/php_errors.log\');
        
        // Other security settings
        ini_set(\'allow_url_fopen\', 0);
        ini_set(\'allow_url_include\', 0);
        ini_set(\'expose_php\', 0);
    }
    
    // Database Security
    public static function getSecureDBConfig() {
        return [
            \'options\' => [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false,
                PDO::ATTR_PERSISTENT => false
            ],
            \'charset\' => \'utf8mb4\'
        ];
    }
    
    // Content Security Policy
    public static function getCSPPolicy() {
        return [
            \'default-src\' => "\'self\'",
            \'script-src\' => "\'self\' \'unsafe-inline\' https://cdnjs.cloudflare.com https://cdn.jsdelivr.net",
            \'style-src\' => "\'self\' \'unsafe-inline\' https://cdnjs.cloudflare.com https://fonts.googleapis.com",
            \'font-src\' => "\'self\' https://fonts.gstatic.com",
            \'img-src\' => "\'self\' data: https:",
            \'connect-src\' => "\'self\'",
            \'frame-ancestors\' => "\'none\'",
            \'base-uri\' => "\'self\'",
            \'form-action\' => "\'self\'"
        ];
    }
    
    // Security Headers
    public static function setSecurityHeaders() {
        // Include our security headers
        require_once \'includes/security-headers.php\';
        
        // Additional headers based on context
        if (isset($_SERVER[\'REQUEST_METHOD\']) && $_SERVER[\'REQUEST_METHOD\'] === \'POST\') {
            header(\'Cache-Control: no-cache, no-store, must-revalidate\');
            header(\'Pragma: no-cache\');
            header(\'Expires: 0\');
        }
    }
    
    // Input Validation Rules
    public static function getValidationRules() {
        return [
            \'email\' => [
                \'filter\' => FILTER_VALIDATE_EMAIL,
                \'maxlength\' => 320
            ],
            \'password\' => [
                \'minlength\' => 8,
                \'pattern\' => \'/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)[a-zA-Z\d@$!%*?&]{8,}$/\'
            ],
            \'phone\' => [
                \'pattern\' => \'/^[0-9+\-\s()]{7,20}$/\'
            ],
            \'name\' => [
                \'maxlength\' => 100,
                \'pattern\' => \'/^[a-zA-ZÀ-ÿ\s]{2,}$/u\'
            ],
            \'text\' => [
                \'maxlength\' => 1000
            ]
        ];
    }
    
    // Rate Limiting Configuration
    public static function getRateLimits() {
        return [
            \'login\' => [\'limit\' => 5, \'window\' => 900], // 5 attempts per 15 minutes
            \'register\' => [\'limit\' => 3, \'window\' => 3600], // 3 registrations per hour
            \'contact\' => [\'limit\' => 10, \'window\' => 3600], // 10 messages per hour
            \'review\' => [\'limit\' => 5, \'window\' => 3600], // 5 reviews per hour
            \'cart\' => [\'limit\' => 100, \'window\' => 3600], // 100 cart operations per hour
            \'api\' => [\'limit\' => 60, \'window\' => 60] // 60 API calls per minute
        ];
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
    private static $sessionTimeout = 1800; // 30 minutes
    
    public static function validateLogin($email, $password, $rememberMe = false) {
        $ip = $_SERVER[\'REMOTE_ADDR\'] ?? \'unknown\';
        
        // Check if IP is blocked
        if (RateLimiter::isBlocked($ip)) {
            return [\'success\' => false, \'error\' => \'IP temporarily blocked due to suspicious activity\'];
        }
        
        // Check rate limiting
        if (!RateLimiter::checkLimit(\'login\', self::$maxAttempts, self::$lockoutTime, $ip)) {
            RateLimiter::blockIP($ip, self::$lockoutTime);
            return [\'success\' => false, \'error\' => \'Too many login attempts. Try again later.\'];
        }
        
        // Sanitize inputs
        $email = InputSanitizer::sanitizeEmail($email);
        $password = InputSanitizer::sanitizeString($password, 100);
        
        // Validate email format
        if (!InputSanitizer::validateEmail($email)) {
            self::logFailedAttempt($email, $ip, \'Invalid email format\');
            return [\'success\' => false, \'error\' => \'Invalid email or password\'];
        }
        
        // Check for SQL injection attempts
        if (InputSanitizer::detectSQLInjection($email) || InputSanitizer::detectSQLInjection($password)) {
            InputSanitizer::logSuspiciousActivity($email . \' | \' . $password, \'SQL_INJECTION\', $ip);
            RateLimiter::blockIP($ip, 3600); // Block for 1 hour
            return [\'success\' => false, \'error\' => \'Invalid request\'];
        }
        
        // Authenticate user
        $user = self::authenticateUser($email, $password);
        
        if ($user) {
            self::createSecureSession($user, $rememberMe);
            self::logSuccessfulLogin($user[\'id\'], $ip);
            return [\'success\' => true, \'user\' => $user];
        } else {
            self::logFailedAttempt($email, $ip, \'Invalid credentials\');
            return [\'success\' => false, \'error\' => \'Invalid email or password\'];
        }
    }
    
    private static function authenticateUser($email, $password) {
        try {
            $db = new Database();
            $conn = $db->getConnection();
            
            $stmt = $conn->prepare("SELECT id, email, password, nombre, apellido, is_active FROM customers WHERE email = ? AND is_active = 1 LIMIT 1");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            
            if ($user && password_verify($password, $user[\'password\'])) {
                // Remove password from returned data
                unset($user[\'password\']);
                return $user;
            }
            
            return false;
        } catch (Exception $e) {
            error_log("Login authentication error: " . $e->getMessage());
            return false;
        }
    }
    
    private static function createSecureSession($user, $rememberMe = false) {
        // Regenerate session ID to prevent fixation
        session_regenerate_id(true);
        
        // Set session data
        $_SESSION[\'user_id\'] = $user[\'id\'];
        $_SESSION[\'user_email\'] = $user[\'email\'];
        $_SESSION[\'user_name\'] = $user[\'nombre\'] . \' \' . $user[\'apellido\'];
        $_SESSION[\'login_time\'] = time();
        $_SESSION[\'last_activity\'] = time();
        $_SESSION[\'ip_address\'] = $_SERVER[\'REMOTE_ADDR\'] ?? \'unknown\';
        $_SESSION[\'user_agent\'] = $_SERVER[\'HTTP_USER_AGENT\'] ?? \'unknown\';
        
        // Set remember me cookie if requested
        if ($rememberMe) {
            $token = bin2hex(random_bytes(32));
            $expires = time() + (30 * 24 * 60 * 60); // 30 days
            
            setcookie(\'remember_token\', $token, $expires, \'/\', \'\', isset($_SERVER[\'HTTPS\']), true);
            
            // Store token in database
            self::storeRememberToken($user[\'id\'], $token, $expires);
        }
    }
    
    private static function storeRememberToken($userId, $token, $expires) {
        try {
            $db = new Database();
            $conn = $db->getConnection();
            
            // Hash token before storing
            $hashedToken = password_hash($token, PASSWORD_DEFAULT);
            
            $stmt = $conn->prepare("INSERT INTO remember_tokens (user_id, token, expires_at) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE token = ?, expires_at = ?");
            $stmt->execute([$userId, $hashedToken, date(\'Y-m-d H:i:s\', $expires), $hashedToken, date(\'Y-m-d H:i:s\', $expires)]);
        } catch (Exception $e) {
            error_log("Remember token storage error: " . $e->getMessage());
        }
    }
    
    public static function validateSession() {
        if (!isset($_SESSION[\'user_id\'])) {
            return false;
        }
        
        // Check session timeout
        if (isset($_SESSION[\'last_activity\']) && (time() - $_SESSION[\'last_activity\']) > self::$sessionTimeout) {
            self::destroySession();
            return false;
        }
        
        // Check IP and User Agent for session hijacking
        if (isset($_SESSION[\'ip_address\']) && $_SESSION[\'ip_address\'] !== ($_SERVER[\'REMOTE_ADDR\'] ?? \'unknown\')) {
            self::logSuspiciousActivity(\'Session IP mismatch\');
            self::destroySession();
            return false;
        }
        
        if (isset($_SESSION[\'user_agent\']) && $_SESSION[\'user_agent\'] !== ($_SERVER[\'HTTP_USER_AGENT\'] ?? \'unknown\')) {
            self::logSuspiciousActivity(\'Session User-Agent mismatch\');
            self::destroySession();
            return false;
        }
        
        // Update last activity
        $_SESSION[\'last_activity\'] = time();
        
        return true;
    }
    
    public static function destroySession() {
        // Clear remember token if exists
        if (isset($_COOKIE[\'remember_token\'])) {
            setcookie(\'remember_token\', \'\', time() - 3600, \'/\');
            self::removeRememberToken($_SESSION[\'user_id\'] ?? null);
        }
        
        // Destroy session
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
    
    private static function removeRememberToken($userId) {
        if (!$userId) return;
        
        try {
            $db = new Database();
            $conn = $db->getConnection();
            
            $stmt = $conn->prepare("DELETE FROM remember_tokens WHERE user_id = ?");
            $stmt->execute([$userId]);
        } catch (Exception $e) {
            error_log("Remember token removal error: " . $e->getMessage());
        }
    }
    
    private static function logFailedAttempt($email, $ip, $reason) {
        $logEntry = date(\'Y-m-d H:i:s\') . " - FAILED LOGIN - Email: $email - IP: $ip - Reason: $reason\\n";
        file_put_contents(\'_logs/security.log\', $logEntry, FILE_APPEND | LOCK_EX);
    }
    
    private static function logSuccessfulLogin($userId, $ip) {
        $logEntry = date(\'Y-m-d H:i:s\') . " - SUCCESSFUL LOGIN - User ID: $userId - IP: $ip\\n";
        file_put_contents(\'_logs/security.log\', $logEntry, FILE_APPEND | LOCK_EX);
    }
    
    private static function logSuspiciousActivity($reason) {
        $ip = $_SERVER[\'REMOTE_ADDR\'] ?? \'unknown\';
        $logEntry = date(\'Y-m-d H:i:s\') . " - SUSPICIOUS ACTIVITY - Reason: $reason - IP: $ip\\n";
        file_put_contents(\'_logs/security.log\', $logEntry, FILE_APPEND | LOCK_EX);
    }
}
?>';
        
        file_put_contents('includes/LoginProtection.php', $loginProtection);
        $this->applied[] = "Login Protection class created";
    }
    
    // 8. Generate Security Report
    public function generateSecurityReport() {
        echo "\n📊 GENERATING SECURITY REPORT...\n";
        
        echo "\n✅ SECURITY HARDENING COMPLETED!\n";
        echo "================================\n\n";
        
        echo "📋 Applied Security Measures:\n";
        foreach ($this->applied as $measure) {
            echo "✅ $measure\n";
        }
        
        echo "\n🔧 NEXT STEPS TO COMPLETE SECURITY:\n";
        echo "1. Create logs directory: mkdir _logs\n";
        echo "2. Set proper permissions: chmod 755 _logs\n";
        echo "3. Update all forms to include CSRF protection\n";
        echo "4. Apply security headers to all pages\n";
        echo "5. Test rate limiting functionality\n";
        echo "6. Configure SSL/HTTPS for production\n";
        echo "7. Set up regular security log monitoring\n\n";
        
        echo "🚨 CRITICAL SECURITY CHECKLIST:\n";
        echo "□ Include security-headers.php in all pages\n";
        echo "□ Add CSRF tokens to all forms\n";
        echo "□ Validate all user inputs with InputSanitizer\n";
        echo "□ Implement rate limiting on all forms\n";
        echo "□ Use SecureUploader for file uploads\n";
        echo "□ Configure HTTPS in production\n";
        echo "□ Set up automated security log monitoring\n";
        echo "□ Regularly update dependencies\n";
        echo "□ Perform penetration testing\n\n";
    }
}

// Execute security hardening
$hardener = new SecurityHardener();
$hardener->hardenAll();
?>
