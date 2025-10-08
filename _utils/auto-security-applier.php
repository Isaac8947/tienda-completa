<?php
/**
 * AUTOMATED SECURITY APPLIER
 * Applies security fixes automatically to all vulnerable files
 */

require_once 'includes/InputSanitizer.php';
require_once 'includes/CSRFProtection.php';
require_once 'includes/RateLimiter.php';

class AutoSecurityApplier {
    private $backupDir;
    private $appliedFixes = [];
    
    public function __construct() {
        echo "🔧 AUTOMATED SECURITY APPLICATION\n";
        echo "=================================\n\n";
        
        // Create backup directory
        $this->backupDir = '_backups/security_backup_' . date('Y-m-d_H-i-s');
        if (!is_dir($this->backupDir)) {
            mkdir($this->backupDir, 0755, true);
        }
    }
    
    public function applyAllSecurityMeasures() {
        $this->backupOriginalFiles();
        $this->applyToLoginRegister();
        $this->applyToCartSystem();
        $this->applyToReviewSystem();
        $this->applyToProductPages();
        $this->applyToSearchPage();
        $this->createSecureIndexPage();
        $this->generateImplementationGuide();
    }
    
    private function backupOriginalFiles() {
        echo "📋 Creating backup of original files...\n";
        
        $filesToBackup = [
            'index.php', 'login.php', 'register.php', 'details.php', 'product.php',
            'search.php', 'cart-add.php', 'cart-remove.php', 'wishlist-toggle.php',
            'review-like.php', 'review-reply.php'
        ];
        
        foreach ($filesToBackup as $file) {
            if (file_exists($file)) {
                copy($file, $this->backupDir . '/' . $file);
            }
        }
        
        echo "✅ Backup created in: {$this->backupDir}\n\n";
    }
    
    private function applyToLoginRegister() {
        echo "🔒 Securing login and registration...\n";
        
        // Secure login.php
        if (file_exists('login.php')) {
            $content = file_get_contents('login.php');
            
            // Add security imports at the top
            $securityHeader = '<?php
require_once \'includes/security-headers.php\';
require_once \'includes/LoginProtection.php\';
require_once \'includes/CSRFProtection.php\';
require_once \'includes/InputSanitizer.php\';
require_once \'includes/RateLimiter.php\';

// Block suspicious IPs
if (RateLimiter::isBlocked()) {
    header(\'HTTP/1.1 403 Forbidden\');
    exit(\'Access denied\');
}

// Check if this is a login attempt
if ($_SERVER[\'REQUEST_METHOD\'] === \'POST\') {
    // Validate CSRF token
    if (!isset($_POST[\'csrf_token\']) || !CSRFProtection::validateToken($_POST[\'csrf_token\'], \'login\')) {
        header(\'Location: login.php?error=invalid_request\');
        exit;
    }
    
    // Use secure login validation
    $email = $_POST[\'email\'] ?? \'\';
    $password = $_POST[\'password\'] ?? \'\';
    
    $result = LoginProtection::validateLogin($email, $password);
    
    if ($result[\'success\']) {
        header(\'Location: mi-cuenta.php\');
        exit;
    } else {
        $error = $result[\'error\'];
    }
}

// Generate CSRF token for the form
$csrfToken = CSRFProtection::generateToken(\'login\');
';
            
            if (!strpos($content, 'security-headers.php')) {
                $content = str_replace('<?php', $securityHeader, $content, 1);
            }
            
            // Add CSRF token to form
            if (strpos($content, 'type="password"') && !strpos($content, 'csrf_token')) {
                $content = str_replace(
                    'type="password"',
                    'type="password">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>"',
                    $content
                );
            }
            
            file_put_contents('login.php', $content);
            $this->appliedFixes[] = 'Login page secured with CSRF, rate limiting, and input validation';
        }
        
        // Secure register.php (similar process)
        if (file_exists('register.php')) {
            $content = file_get_contents('register.php');
            
            $securityHeader = '<?php
require_once \'includes/security-headers.php\';
require_once \'includes/CSRFProtection.php\';
require_once \'includes/InputSanitizer.php\';
require_once \'includes/RateLimiter.php\';

// Block suspicious IPs
if (RateLimiter::isBlocked()) {
    header(\'HTTP/1.1 403 Forbidden\');
    exit(\'Access denied\');
}

// Check rate limiting for registration
if ($_SERVER[\'REQUEST_METHOD\'] === \'POST\') {
    if (!RateLimiter::checkLimit(\'register\', 3, 3600)) {
        header(\'Location: register.php?error=too_many_attempts\');
        exit;
    }
    
    // Validate CSRF token
    if (!isset($_POST[\'csrf_token\']) || !CSRFProtection::validateToken($_POST[\'csrf_token\'], \'register\')) {
        header(\'Location: register.php?error=invalid_request\');
        exit;
    }
    
    // Sanitize all inputs
    foreach ($_POST as $key => $value) {
        if ($key !== \'csrf_token\') {
            $_POST[$key] = InputSanitizer::sanitizeString($value);
        }
    }
}

$csrfToken = CSRFProtection::generateToken(\'register\');
';
            
            if (!strpos($content, 'security-headers.php')) {
                $content = str_replace('<?php', $securityHeader, $content, 1);
            }
            
            file_put_contents('register.php', $content);
            $this->appliedFixes[] = 'Registration page secured';
        }
    }
    
    private function applyToCartSystem() {
        echo "🛒 Securing cart system...\n";
        
        $cartFiles = ['cart-add.php', 'cart-remove.php', 'cart-update.php'];
        
        foreach ($cartFiles as $file) {
            if (file_exists($file)) {
                $content = file_get_contents($file);
                
                $securityHeader = '<?php
require_once \'includes/security-headers.php\';
require_once \'includes/CSRFProtection.php\';
require_once \'includes/InputSanitizer.php\';
require_once \'includes/RateLimiter.php\';

// Set JSON response header
header(\'Content-Type: application/json\');

// Block suspicious IPs
if (RateLimiter::isBlocked()) {
    http_response_code(403);
    echo json_encode([\'success\' => false, \'error\' => \'Access denied\']);
    exit;
}

// Check rate limiting
if (!RateLimiter::checkLimit(\'cart\', 30, 300)) {
    http_response_code(429);
    echo json_encode([\'success\' => false, \'error\' => \'Too many requests\']);
    exit;
}

// Validate request method
if ($_SERVER[\'REQUEST_METHOD\'] !== \'POST\') {
    http_response_code(405);
    echo json_encode([\'success\' => false, \'error\' => \'Method not allowed\']);
    exit;
}

// Validate CSRF token
if (!isset($_POST[\'csrf_token\']) || !CSRFProtection::validateToken($_POST[\'csrf_token\'], \'cart\')) {
    http_response_code(403);
    echo json_encode([\'success\' => false, \'error\' => \'Invalid request\']);
    exit;
}

// Sanitize all inputs
foreach ($_POST as $key => $value) {
    if ($key !== \'csrf_token\') {
        $_POST[$key] = InputSanitizer::sanitizeString($value);
    }
}

// Additional validation for numeric IDs
if (isset($_POST[\'product_id\'])) {
    $_POST[\'product_id\'] = InputSanitizer::sanitizeInt($_POST[\'product_id\'], 1, 999999);
}
if (isset($_POST[\'quantity\'])) {
    $_POST[\'quantity\'] = InputSanitizer::sanitizeInt($_POST[\'quantity\'], 1, 100);
}
';
                
                if (!strpos($content, 'security-headers.php')) {
                    $content = str_replace('<?php', $securityHeader, $content, 1);
                }
                
                file_put_contents($file, $content);
                $this->appliedFixes[] = "$file secured with comprehensive validation";
            }
        }
    }
    
    private function applyToReviewSystem() {
        echo "💬 Securing review system...\n";
        
        $reviewFiles = ['review-like.php', 'review-reply.php'];
        
        foreach ($reviewFiles as $file) {
            if (file_exists($file)) {
                $content = file_get_contents($file);
                
                $securityHeader = '<?php
require_once \'includes/security-headers.php\';
require_once \'includes/CSRFProtection.php\';
require_once \'includes/InputSanitizer.php\';
require_once \'includes/RateLimiter.php\';

header(\'Content-Type: application/json\');

// Start session for user validation
session_start();

// Block suspicious IPs
if (RateLimiter::isBlocked()) {
    http_response_code(403);
    echo json_encode([\'success\' => false, \'error\' => \'Access denied\']);
    exit;
}

// Check rate limiting for reviews
if (!RateLimiter::checkLimit(\'review\', 10, 600)) {
    http_response_code(429);
    echo json_encode([\'success\' => false, \'error\' => \'Too many requests. Please wait.\']);
    exit;
}

// Validate user authentication
if (!isset($_SESSION[\'user_id\']) || empty($_SESSION[\'user_id\'])) {
    http_response_code(401);
    echo json_encode([\'success\' => false, \'error\' => \'Authentication required\']);
    exit;
}

// Validate request method
if ($_SERVER[\'REQUEST_METHOD\'] !== \'POST\') {
    http_response_code(405);
    echo json_encode([\'success\' => false, \'error\' => \'Method not allowed\']);
    exit;
}

// Validate CSRF token
if (!isset($_POST[\'csrf_token\']) || !CSRFProtection::validateToken($_POST[\'csrf_token\'], \'review\')) {
    http_response_code(403);
    echo json_encode([\'success\' => false, \'error\' => \'Invalid request token\']);
    exit;
}

// Sanitize inputs
foreach ($_POST as $key => $value) {
    if ($key !== \'csrf_token\') {
        $_POST[$key] = InputSanitizer::sanitizeString($value);
        
        // Check for malicious content
        if (InputSanitizer::detectXSS($value) || InputSanitizer::detectSQLInjection($value)) {
            InputSanitizer::logSuspiciousActivity($value, \'REVIEW_ATTACK\');
            http_response_code(400);
            echo json_encode([\'success\' => false, \'error\' => \'Invalid content detected\']);
            exit;
        }
    }
}
';
                
                if (!strpos($content, 'security-headers.php')) {
                    $content = str_replace('<?php', $securityHeader, $content, 1);
                }
                
                file_put_contents($file, $content);
                $this->appliedFixes[] = "$file secured with user authentication and XSS protection";
            }
        }
    }
    
    private function applyToProductPages() {
        echo "📦 Securing product pages...\n";
        
        $productFiles = ['product.php', 'details.php'];
        
        foreach ($productFiles as $file) {
            if (file_exists($file)) {
                $content = file_get_contents($file);
                
                $securityHeader = '<?php
require_once \'includes/security-headers.php\';
require_once \'includes/InputSanitizer.php\';

// Sanitize product ID from URL
if (isset($_GET[\'id\'])) {
    $productId = InputSanitizer::sanitizeInt($_GET[\'id\'], 1, 999999);
    if ($productId <= 0) {
        header(\'Location: /404.php\');
        exit;
    }
    $_GET[\'id\'] = $productId;
}
';
                
                if (!strpos($content, 'security-headers.php')) {
                    $content = str_replace('<?php', $securityHeader, $content, 1);
                }
                
                file_put_contents($file, $content);
                $this->appliedFixes[] = "$file secured with input validation";
            }
        }
    }
    
    private function applyToSearchPage() {
        echo "🔍 Securing search functionality...\n";
        
        if (file_exists('search.php')) {
            $content = file_get_contents('search.php');
            
            $securityHeader = '<?php
require_once \'includes/security-headers.php\';
require_once \'includes/InputSanitizer.php\';
require_once \'includes/RateLimiter.php\';

// Check rate limiting for search
if (!RateLimiter::checkLimit(\'search\', 20, 300)) {
    header(\'Location: /?error=too_many_searches\');
    exit;
}

// Sanitize and validate search query
if (isset($_GET[\'q\'])) {
    $searchQuery = InputSanitizer::sanitizeString($_GET[\'q\'], 100);
    
    // Check for malicious content
    if (InputSanitizer::detectSQLInjection($searchQuery) || InputSanitizer::detectXSS($searchQuery)) {
        InputSanitizer::logSuspiciousActivity($searchQuery, \'SEARCH_ATTACK\');
        header(\'Location: /?error=invalid_search\');
        exit;
    }
    
    // Minimum search length
    if (strlen(trim($searchQuery)) < 2) {
        header(\'Location: /?error=search_too_short\');
        exit;
    }
    
    $_GET[\'q\'] = $searchQuery;
}
';
            
            if (!strpos($content, 'security-headers.php')) {
                $content = str_replace('<?php', $securityHeader, $content, 1);
            }
            
            file_put_contents('search.php', $content);
            $this->appliedFixes[] = 'Search page secured with comprehensive validation';
        }
    }
    
    private function createSecureIndexPage() {
        echo "🏠 Securing main index page...\n";
        
        if (file_exists('index.php')) {
            $content = file_get_contents('index.php');
            
            $securityHeader = '<?php
require_once \'includes/security-headers.php\';
require_once \'includes/SecurityConfig.php\';
';
            
            if (!strpos($content, 'security-headers.php')) {
                $content = str_replace('<?php', $securityHeader, $content, 1);
            }
            
            file_put_contents('index.php', $content);
            $this->appliedFixes[] = 'Index page secured with security headers';
        }
    }
    
    private function generateImplementationGuide() {
        echo "\n📋 Generating implementation guide...\n";
        
        $guide = '# SECURITY IMPLEMENTATION GUIDE
Generated: ' . date('Y-m-d H:i:s') . '

## ✅ APPLIED SECURITY FIXES

';
        foreach ($this->appliedFixes as $fix) {
            $guide .= "- $fix\n";
        }
        
        $guide .= '
## 🔧 MANUAL STEPS REQUIRED

### 1. Add CSRF tokens to forms
Add this to all forms that don\'t have it yet:
```php
<?php $csrfToken = CSRFProtection::generateToken(\'form_name\'); ?>
<input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
```

### 2. Update JavaScript for AJAX calls
Add CSRF token to all AJAX requests:
```javascript
// Get CSRF token
function getCSRFToken() {
    return "<?php echo CSRFProtection::generateToken(\'ajax\'); ?>";
}

// Include in AJAX data
data: {
    csrf_token: getCSRFToken(),
    // ... other data
}
```

### 3. Enable HTTPS (Production)
- Install SSL certificate
- Update .htaccess to redirect HTTP to HTTPS
- Update session.cookie_secure in php.ini

### 4. Configure Web Server
Add to .htaccess:
```apache
# Security Headers
Header always set X-Frame-Options DENY
Header always set X-Content-Type-Options nosniff
Header always set X-XSS-Protection "1; mode=block"
Header always set Referrer-Policy "strict-origin-when-cross-origin"

# Hide server info
ServerTokens Prod
Header unset Server
Header unset X-Powered-By
```

### 5. Monitor Security Logs
- Check _logs/security.log daily
- Set up alerts for multiple failed login attempts
- Monitor for SQL injection attempts

### 6. Regular Security Maintenance
- Update PHP and dependencies monthly
- Run security tests weekly: `php _tests/security-test-suite.php`
- Review access logs for suspicious activity

## 🚨 CRITICAL SECURITY CHECKLIST

- [ ] All forms have CSRF protection
- [ ] All user inputs are sanitized
- [ ] Rate limiting is active on all forms
- [ ] HTTPS is enabled in production
- [ ] Security logs are monitored
- [ ] File upload restrictions are enforced
- [ ] Session security is configured
- [ ] Admin panel access is restricted
- [ ] Database uses prepared statements
- [ ] Error messages don\'t reveal system info

## 📞 EMERGENCY RESPONSE

If you detect an attack:
1. Check _logs/security.log for details
2. Block suspicious IPs manually if needed
3. Review all recent changes
4. Update passwords if compromised
5. Run full security scan

## 🔄 TESTING

Test your security with:
```bash
php _tests/security-test-suite.php
php _tests/security-validator.php
```

Your application is now significantly more secure! 🛡️
';
        
        file_put_contents('SECURITY_IMPLEMENTATION_GUIDE.md', $guide);
        echo "✅ Implementation guide created: SECURITY_IMPLEMENTATION_GUIDE.md\n\n";
    }
    
    public function showSummary() {
        echo "🎉 SECURITY APPLICATION COMPLETED!\n";
        echo "==================================\n\n";
        
        echo "📊 SUMMARY:\n";
        echo "- Files backed up to: {$this->backupDir}\n";
        echo "- Security fixes applied: " . count($this->appliedFixes) . "\n";
        echo "- Implementation guide created\n\n";
        
        echo "🔐 YOUR SITE IS NOW MUCH MORE SECURE!\n\n";
        
        echo "⚡ IMMEDIATE ACTIONS:\n";
        echo "1. Test all functionality to ensure it still works\n";
        echo "2. Add CSRF tokens to any remaining forms\n";
        echo "3. Enable HTTPS for production\n";
        echo "4. Monitor security logs in _logs/\n\n";
        
        echo "📱 NEXT: Test your security with:\n";
        echo "php _tests/security-test-suite.php\n\n";
    }
}

// Execute automated security application
$applier = new AutoSecurityApplier();
$applier->applyAllSecurityMeasures();
$applier->showSummary();
?>
