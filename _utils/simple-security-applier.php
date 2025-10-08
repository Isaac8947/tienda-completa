<?php
/**
 * SIMPLE SECURITY APPLIER
 * Applies basic security to key files
 */

echo "🔧 APPLYING BASIC SECURITY MEASURES\n";
echo "===================================\n\n";

// Create backup directory
$backupDir = '_backups/security_backup_' . date('Y-m-d_H-i-s');
if (!is_dir($backupDir)) {
    mkdir($backupDir, 0755, true);
}

echo "📋 Creating backups...\n";

// Files to secure
$files = ['login.php', 'register.php', 'cart-add.php', 'cart-remove.php', 'search.php'];

foreach ($files as $file) {
    if (file_exists($file)) {
        // Create backup
        copy($file, $backupDir . '/' . $file);
        
        // Read content
        $content = file_get_contents($file);
        
        // Add basic security header if not present
        if (!strpos($content, 'security-headers.php')) {
            $securityInclude = "require_once 'includes/security-headers.php';\n";
            $content = str_replace('<?php', "<?php\n" . $securityInclude, $content);
            
            // Write back
            file_put_contents($file, $content);
            echo "✅ $file secured\n";
        } else {
            echo "⚡ $file already has security headers\n";
        }
    }
}

echo "\n🛡️  BASIC SECURITY APPLIED!\n";
echo "==========================\n\n";

echo "📋 MANUAL STEPS STILL NEEDED:\n";
echo "1. Add CSRF tokens to forms\n";
echo "2. Sanitize user inputs\n";
echo "3. Implement rate limiting\n";
echo "4. Enable HTTPS in production\n\n";

echo "🔧 TO COMPLETE SECURITY:\n";
echo "1. Review the secure files in includes/\n";
echo "2. Add to each form: <?php echo CSRFProtection::getTokenInput('form_name'); ?>\n";
echo "3. Sanitize inputs: InputSanitizer::sanitizeString(\$input)\n";
echo "4. Test with: php _tests/security-test-suite.php\n\n";

echo "📁 Backups saved in: $backupDir\n";
?>
