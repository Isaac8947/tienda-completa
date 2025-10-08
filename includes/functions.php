<?php
/**
 * Funciones auxiliares globales de la aplicación
 * 
 * Este archivo contiene funciones de utilidad que se usan
 * en toda la aplicación para mantener el código DRY.
 * 
 * @author Isaac8947 (catla6273@gmail.com)
 * @version 2.0
 */

/**
 * Función para cargar vistas con datos
 * 
 * @param string $view Nombre de la vista
 * @param array $data Datos para pasar a la vista
 * @return void
 */
function load_view($view, $data = []) {
    // Extraer variables para la vista
    extract($data);
    
    // Construir ruta de la vista
    $view_file = INCLUDES_PATH . '/views/' . $view . '.php';
    
    if (file_exists($view_file)) {
        require_once $view_file;
    } else {
        throw new Exception("Vista no encontrada: {$view}");
    }
}

/**
 * Función para cargar componentes reutilizables
 * 
 * @param string $component Nombre del componente
 * @param array $data Datos para el componente
 * @return void
 */
function load_component($component, $data = []) {
    extract($data);
    
    $component_file = INCLUDES_PATH . '/components/' . $component . '.php';
    
    if (file_exists($component_file)) {
        require_once $component_file;
    } else {
        throw new Exception("Componente no encontrado: {$component}");
    }
}

/**
 * Función para generar URLs limpias
 * 
 * @param string $path Ruta de la URL
 * @return string URL completa
 */
function url($path = '') {
    $base_url = 'http' . (isset($_SERVER['HTTPS']) ? 's' : '') . '://' . $_SERVER['HTTP_HOST'];
    $script_path = dirname($_SERVER['SCRIPT_NAME']);
    
    if ($script_path !== '/') {
        $base_url .= $script_path;
    }
    
    return $base_url . '/' . ltrim($path, '/');
}

/**
 * Función para redireccionar
 * 
 * @param string $url URL de destino
 * @return void
 */
function redirect($url) {
    if (!headers_sent()) {
        header('Location: ' . url($url));
        exit;
    }
}

/**
 * Función para sanitizar datos de entrada
 * 
 * @param mixed $data Datos a sanitizar
 * @return mixed Datos sanitizados
 */
function sanitize_input($data) {
    if (is_array($data)) {
        return array_map('sanitize_input', $data);
    }
    
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

/**
 * Función para validar email
 * 
 * @param string $email Email a validar
 * @return bool True si es válido
 */
function validate_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Función para generar token CSRF
 * 
 * @return string Token CSRF
 */
function generate_csrf_token() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Función para verificar token CSRF
 * 
 * @param string $token Token a verificar
 * @return bool True si es válido
 */
function verify_csrf_token($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Función para logging simple
 * 
 * @param string $message Mensaje a loggear
 * @param string $level Nivel de log (error, info, debug)
 * @return void
 */
function log_message($message, $level = 'info') {
    $log_file = STORAGE_PATH . '/logs/app_' . date('Y-m-d') . '.log';
    $timestamp = date('Y-m-d H:i:s');
    $log_entry = "[{$timestamp}] [{$level}] {$message}" . PHP_EOL;
    
    // Crear directorio si no existe
    $log_dir = dirname($log_file);
    if (!is_dir($log_dir)) {
        mkdir($log_dir, 0755, true);
    }
    
    file_put_contents($log_file, $log_entry, FILE_APPEND | LOCK_EX);
}

/**
 * Función para formatear precios
 * 
 * @param float $price Precio a formatear
 * @param string $currency Símbolo de moneda
 * @return string Precio formateado
 */
function format_price($price, $currency = '$') {
    return $currency . number_format($price, 2, '.', ',');
}

/**
 * Función para respuestas JSON de API
 * 
 * @param array $data Datos a devolver
 * @param int $status_code Código de estado HTTP
 * @return void
 */
function json_response($data, $status_code = 200) {
    http_response_code($status_code);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}
?>