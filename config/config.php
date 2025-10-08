<?php
// Configuración general de la aplicación Odisea
define('APP_NAME', 'Odisea Makeup');
define('APP_VERSION', '1.0.0');

// URLs base - Detección automática
if (!defined('BASE_URL')) {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    
    // Si estamos usando un servidor de desarrollo (php -S)
    if (isset($_SERVER['SERVER_SOFTWARE']) && strpos($_SERVER['SERVER_SOFTWARE'], 'Development Server') !== false) {
        $baseUrl = $protocol . $host;
    } else {
        // Si estamos en XAMPP o Apache normal
        $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
        $basePath = dirname($scriptName);
        
        // Si estamos en un subdirectorio como admin-pages, quitarlo para apuntar a la raíz
        $basePath = str_replace('/admin-pages', '', $basePath);
        $basePath = str_replace('\\admin-pages', '', $basePath);
        
        if ($basePath === '/' || $basePath === '\\') {
            $basePath = '';
        }
        $baseUrl = $protocol . $host . $basePath;
    }
    
    // Limpiar la URL base
    $baseUrl = rtrim($baseUrl, '/');
    
    define('BASE_URL', $baseUrl);
}

define('ADMIN_URL', BASE_URL . '/admin');
define('API_URL', BASE_URL . '/api');
define('ASSETS_URL', BASE_URL . '/assets');
define('UPLOADS_URL', BASE_URL . '/uploads');
define('UPLOADS_PATH', __DIR__ . '/../uploads/');

// Crear directorio de uploads si no existe
if (!file_exists(UPLOADS_PATH)) {
    mkdir(UPLOADS_PATH, 0755, true);
    mkdir(UPLOADS_PATH . 'products/', 0755, true);
    mkdir(UPLOADS_PATH . 'categories/', 0755, true);
    mkdir(UPLOADS_PATH . 'brands/', 0755, true);
    mkdir(UPLOADS_PATH . 'banners/', 0755, true);
    mkdir(UPLOADS_PATH . 'news/', 0755, true);
    mkdir(UPLOADS_PATH . 'avatars/', 0755, true);
}

// Configuración de zona horaria
date_default_timezone_set('America/Bogota');

// Configuración de errores
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/error.log');

// Configuración de email
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'tu-email@gmail.com');
define('SMTP_PASSWORD', 'tu-app-password');
define('FROM_EMAIL', 'noreply@odisea.com');
define('FROM_NAME', 'Odisea Makeup');

// Configuración de pagos
define('PAYPAL_CLIENT_ID', 'tu-paypal-client-id');
define('PAYPAL_CLIENT_SECRET', 'tu-paypal-client-secret');
define('PAYPAL_MODE', 'sandbox'); // 'live' para producción

// Configuración de redes sociales
define('WHATSAPP_NUMBER', '573001234567');
define('INSTAGRAM_URL', 'https://instagram.com/odiseamakeup');
define('FACEBOOK_URL', 'https://facebook.com/odiseamakeup');
define('TIKTOK_URL', 'https://tiktok.com/@odiseamakeup');

// Configuración de la aplicación
define('DEFAULT_LANGUAGE', 'es');
define('DEFAULT_CURRENCY', 'COP');
define('DEFAULT_TIMEZONE', 'America/Bogota');
define('ITEMS_PER_PAGE', 20);
define('MAX_UPLOAD_SIZE', 5 * 1024 * 1024); // 5MB

// Configuración de seguridad
define('CSRF_TOKEN_NAME', '_token');
define('SESSION_LIFETIME', 7200); // 2 horas
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOCKOUT_DURATION', 900); // 15 minutos

// Funciones de utilidad
function formatPrice($price, $currency = 'COP') {
    switch ($currency) {
        case 'COP':
            return '$' . number_format($price, 0, ',', '.');
        case 'USD':
            return '$' . number_format($price, 2, '.', ',');
        default:
            return number_format($price, 2);
    }
}

function generateSlug($string) {
    $string = strtolower(trim($string));
    $string = preg_replace('/[^a-z0-9\s-]/', '', $string);
    $string = preg_replace('/[\s-]+/', '-', $string);
    return trim($string, '-');
}

function createSlug($string) {
    return generateSlug($string);
}

function uploadImage($file, $folder = 'uploads') {
    $uploadDir = __DIR__ . '/../uploads/' . $folder . '/';
    $webPath = 'uploads/' . $folder . '/';
    
    // Create directory if it doesn't exist
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    // Check if file was uploaded without errors
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => 'Error en la subida del archivo.'];
    }
    
    // Validate file type
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($mimeType, $allowedTypes)) {
        return ['success' => false, 'message' => 'Tipo de archivo no permitido. Solo se permiten imágenes.'];
    }
    
    // Validate file size (max 5MB)
    if ($file['size'] > 5 * 1024 * 1024) {
        return ['success' => false, 'message' => 'El archivo es demasiado grande. Máximo 5MB.'];
    }
    
    // Generate unique filename
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid() . '_' . time() . '.' . $extension;
    $targetPath = $uploadDir . $filename;
    $webFilePath = $webPath . $filename;
    
    // Move uploaded file
    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
        return ['success' => true, 'path' => $webFilePath, 'filename' => $filename];
    } else {
        return ['success' => false, 'message' => 'Error al mover el archivo subido.'];
    }
}

function uploadSingleImage($file, $folder = 'uploads') {
    return uploadImage($file, $folder);
}

function reArrayFiles($filePost) {
    $fileArray = [];
    $fileCount = count($filePost['name']);
    $fileKeys = array_keys($filePost);

    for ($i = 0; $i < $fileCount; $i++) {
        foreach ($fileKeys as $key) {
            $fileArray[$i][$key] = $filePost[$key][$i];
        }
    }

    return $fileArray;
}

function sanitizeInput($data) {
    if (is_array($data)) {
        return array_map('sanitizeInput', $data);
    }
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function validateURL($url) {
    return filter_var($url, FILTER_VALIDATE_URL) !== false;
}

function isLoggedIn() {
    return isset($_SESSION['customer_id']) && !empty($_SESSION['customer_id']);
}

function isAdminLoggedIn() {
    return isset($_SESSION['admin_id']) && !empty($_SESSION['admin_id']);
}

function getCurrentCustomer() {
    if (isLoggedIn()) {
        return [
            'id' => $_SESSION['customer_id'],
            'email' => $_SESSION['customer_email'] ?? '',
            'name' => $_SESSION['customer_name'] ?? ''
        ];
    }
    return null;
}

function getCurrentAdmin() {
    if (isAdminLoggedIn()) {
        return [
            'id' => $_SESSION['admin_id'],
            'email' => $_SESSION['admin_email'] ?? '',
            'name' => $_SESSION['admin_name'] ?? '',
            'role' => $_SESSION['admin_role'] ?? 'admin'
        ];
    }
    return null;
}

function redirectTo($url) {
    if (!headers_sent()) {
        header("Location: $url");
        exit();
    } else {
        echo "<script>window.location.href='$url';</script>";
        exit();
    }
}

function generateToken($length = 32) {
    return bin2hex(random_bytes($length));
}

function generateCSRFToken() {
    if (!isset($_SESSION[CSRF_TOKEN_NAME])) {
        $_SESSION[CSRF_TOKEN_NAME] = generateToken();
    }
    return $_SESSION[CSRF_TOKEN_NAME];
}

function validateCSRFToken($token) {
    return isset($_SESSION[CSRF_TOKEN_NAME]) && hash_equals($_SESSION[CSRF_TOKEN_NAME], $token);
}

function logActivity($userType, $userId, $action, $description = '', $data = []) {
    try {
        require_once __DIR__ . '/database.php';
        $db = new Database();
        $conn = $db->getConnection();
        
        $sql = "INSERT INTO activity_logs (user_type, user_id, action, description, ip_address, user_agent, created_at) 
                VALUES (:user_type, :user_id, :action, :description, :ip_address, :user_agent, NOW())";
        
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            ':user_type' => $userType,
            ':user_id' => $userId,
            ':action' => $action,
            ':description' => $description,
            ':ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
            ':user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
        ]);
    } catch (Exception $e) {
        error_log("Error logging activity: " . $e->getMessage());
    }
}

function sendEmail($to, $subject, $body, $isHTML = true) {
    // Implementar envío de email con PHPMailer o similar
    // Por ahora solo log para desarrollo
    error_log("Email to: $to, Subject: $subject");
    return true;
}

function uploadFile($file, $directory, $allowedTypes = ['jpg', 'jpeg', 'png', 'gif']) {
    if (!isset($file['tmp_name']) || empty($file['tmp_name'])) {
        throw new Exception('No se ha seleccionado ningún archivo');
    }
    
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('Error al subir el archivo');
    }
    
    if ($file['size'] > MAX_UPLOAD_SIZE) {
        throw new Exception('El archivo es demasiado grande');
    }
    
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($extension, $allowedTypes)) {
        throw new Exception('Tipo de archivo no permitido');
    }
    
    $filename = uniqid() . '.' . $extension;
    $uploadPath = UPLOADS_PATH . $directory . '/' . $filename;
    
    if (!move_uploaded_file($file['tmp_name'], $uploadPath)) {
        throw new Exception('Error al mover el archivo');
    }
    
    return '/uploads/' . $directory . '/' . $filename;
}

function deleteFile($filePath) {
    $fullPath = __DIR__ . '/..' . $filePath;
    if (file_exists($fullPath)) {
        return unlink($fullPath);
    }
    return false;
}

function formatDate($date, $format = 'd/m/Y H:i') {
    if (empty($date)) return '';
    return date($format, strtotime($date));
}

function timeAgo($datetime) {
    $time = time() - strtotime($datetime);
    
    if ($time < 60) return 'hace un momento';
    if ($time < 3600) return 'hace ' . floor($time/60) . ' minutos';
    if ($time < 86400) return 'hace ' . floor($time/3600) . ' horas';
    if ($time < 2592000) return 'hace ' . floor($time/86400) . ' días';
    if ($time < 31536000) return 'hace ' . floor($time/2592000) . ' meses';
    
    return 'hace ' . floor($time/31536000) . ' años';
}

function truncateText($text, $length = 100, $suffix = '...') {
    if (strlen($text) <= $length) {
        return $text;
    }
    return substr($text, 0, $length) . $suffix;
}

function generateOrderNumber() {
    return 'ODI-' . date('Y') . '-' . str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);
}

function calculateTax($amount, $rate = 19) {
    return ($amount * $rate) / 100;
}

function calculateDiscount($amount, $discount, $type = 'percentage') {
    if ($type === 'percentage') {
        return ($amount * $discount) / 100;
    }
    return min($discount, $amount);
}

// Autoload de clases
spl_autoload_register(function ($className) {
    $directories = [
        __DIR__ . '/../models/',
        __DIR__ . '/../controllers/',
        __DIR__ . '/../lib/',
    ];
    
    foreach ($directories as $directory) {
        $file = $directory . $className . '.php';
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
});

// Configuración de headers de seguridad
if (!headers_sent()) {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('X-XSS-Protection: 1; mode=block');
    header('Referrer-Policy: strict-origin-when-cross-origin');
}
