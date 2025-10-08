<?php
/**
 * Odisea Makeup Store - Front Controller
 * 
 * Este es el punto de entrada principal de la aplicación.
 * Todas las requests pasan por aquí para un mejor control y seguridad.
 * 
 * @author Isaac8947 (catla6273@gmail.com)
 * @version 2.0
 * @license Proprietary with Open Contributions
 */

// Configuración inicial
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Definir constantes del sistema
define('ROOT_PATH', dirname(__DIR__));
define('APP_PATH', ROOT_PATH . '/app');
define('CONFIG_PATH', ROOT_PATH . '/config');
define('INCLUDES_PATH', ROOT_PATH . '/includes');
define('PAGES_PATH', ROOT_PATH . '/pages');
define('API_PATH', ROOT_PATH . '/api');
define('STORAGE_PATH', ROOT_PATH . '/storage');

// Autoloader simple para futuras clases
spl_autoload_register(function ($class) {
    $paths = [
        APP_PATH . '/Controllers/',
        APP_PATH . '/Models/',
        APP_PATH . '/Services/',
        APP_PATH . '/Middleware/'
    ];
    
    foreach ($paths as $path) {
        $file = $path . $class . '.php';
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
});

// Cargar configuración
if (file_exists(CONFIG_PATH . '/app.php')) {
    require_once CONFIG_PATH . '/app.php';
}

if (file_exists(CONFIG_PATH . '/database.php')) {
    require_once CONFIG_PATH . '/database.php';
}

// Cargar includes principales
if (file_exists(INCLUDES_PATH . '/functions.php')) {
    require_once INCLUDES_PATH . '/functions.php';
}

// Obtener la ruta solicitada
$request_uri = $_SERVER['REQUEST_URI'];
$script_name = $_SERVER['SCRIPT_NAME'];
$request_uri = str_replace(dirname($script_name), '', $request_uri);
$request_uri = trim($request_uri, '/');

// Router simple
switch ($request_uri) {
    case '':
    case 'index':
    case 'home':
        // Página principal - mostrar la página original
        if (file_exists(ROOT_PATH . '/index.php')) {
            require_once ROOT_PATH . '/index.php';
        } else {
            require_once PAGES_PATH . '/home.php';
        }
        break;
        
    case 'catalogo':
        require_once PAGES_PATH . '/catalogo.php';
        break;
        
    case 'ofertas':
        require_once PAGES_PATH . '/ofertas.php';
        break;
        
    case 'carrito':
        require_once PAGES_PATH . '/carrito.php';
        break;
        
    case 'marcas':
        require_once PAGES_PATH . '/marcas.php';
        break;
        
    case 'search':
        require_once PAGES_PATH . '/search.php';
        break;
        
    case 'mi-cuenta':
        require_once PAGES_PATH . '/mi-cuenta.php';
        break;
        
    case 'details':
        require_once PAGES_PATH . '/details.php';
        break;
        
    case 'categoria':
        require_once PAGES_PATH . '/categoria.php';
        break;
        
    default:
        // Verificar si es una API request
        if (strpos($request_uri, 'api/') === 0) {
            $api_path = str_replace('api/', '', $request_uri);
            $api_file = API_PATH . '/' . $api_path . '.php';
            
            if (file_exists($api_file)) {
                require_once $api_file;
            } else {
                http_response_code(404);
                echo json_encode(['error' => 'API endpoint not found']);
            }
        } else {
            // Página no encontrada
            http_response_code(404);
            require_once ROOT_PATH . '/404.php';
        }
        break;
}
?>