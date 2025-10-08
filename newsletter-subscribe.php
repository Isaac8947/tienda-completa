<?php
// Iniciar output buffering para capturar cualquier output no deseado
ob_start();

// Suprimir errores para respuesta JSON limpia
error_reporting(0);
ini_set('display_errors', 0);

// Security headers
require_once 'includes/security-headers.php';

// Load security classes
require_once 'includes/CSRFProtection.php';
require_once 'includes/InputSanitizer.php';
require_once 'includes/RateLimiter.php';

// Initialize security components
$csrf = new CSRFProtection();

session_start();
require_once 'config/config.php';
require_once 'config/database.php';

// Limpiar cualquier output previo
ob_clean();

// Set JSON response header ANTES de cualquier output
header('Content-Type: application/json; charset=utf-8');

// Function to send clean JSON response
function sendJsonResponse($data, $httpCode = 200) {
    ob_clean();
    http_response_code($httpCode);
    echo json_encode($data);
    ob_end_flush();
    exit;
}

// Rate limiting check - más permisivo para newsletter
if (!RateLimiter::checkLimit('newsletter_subscribe', 10, 600)) {
    sendJsonResponse([
        'success' => false,
        'message' => 'Demasiados intentos. Intenta en unos minutos.'
    ], 429);
}

// Validate CSRF token
$csrfValid = false;
if (isset($_POST['csrf_token'])) {
    try {
        // Usar el método de token global reutilizable
        $csrfValid = CSRFProtection::validateGlobalToken($_POST['csrf_token']);
        
        // Si no es válido con token global, intentar con otros contextos
        if (!$csrfValid) {
            $csrfValid = $csrf->validateToken($_POST['csrf_token'], 'newsletter', false) ||
                        $csrf->validateToken($_POST['csrf_token'], 'default', false);
        }
    } catch (Exception $e) {
        error_log("CSRF validation error in newsletter: " . $e->getMessage());
        $csrfValid = false;
    }
}

if (!$csrfValid) {
    sendJsonResponse([
        'success' => false,
        'message' => 'Token de seguridad inválido'
    ]);
}

// Verificar si se recibieron los datos necesarios
if (!isset($_POST['email']) || empty($_POST['email'])) {
    sendJsonResponse([
        'success' => false,
        'message' => 'Por favor, introduce tu email'
    ]);
}

$email = InputSanitizer::sanitizeEmail($_POST['email']);
$name = InputSanitizer::sanitizeString($_POST['name'] ?? '');

if (!$email) {
    sendJsonResponse([
        'success' => false,
        'message' => 'Por favor, introduce un email válido'
    ]);
}

try {
    // Conectar a la base de datos
    $db = new Database();
    $conn = $db->getConnection();

    // Verificar si el email ya está suscrito
    $sql = "SELECT id, is_active FROM newsletter_subscribers WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$email]);
    $existing = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($existing) {
        if ($existing['is_active']) {
            sendJsonResponse([
                'success' => false,
                'message' => 'Este email ya está suscrito a nuestro newsletter'
            ]);
        } else {
            // Reactivar suscripción si estaba inactiva
            $sql = "UPDATE newsletter_subscribers SET is_active = 1, name = ?, unsubscribed_at = NULL, subscribed_at = NOW() WHERE email = ?";
            $stmt = $conn->prepare($sql);
            $result = $stmt->execute([$name, $email]);
            
            if ($result) {
                sendJsonResponse([
                    'success' => true,
                    'message' => '¡Bienvenido de nuevo! Tu suscripción ha sido reactivada.'
                ]);
            } else {
                sendJsonResponse([
                    'success' => false,
                    'message' => 'Error al reactivar tu suscripción. Por favor, inténtalo de nuevo.'
                ]);
            }
        }
    }

    // Insertar el nuevo suscriptor
    $sql = "INSERT INTO newsletter_subscribers (email, name, is_active, subscribed_at) VALUES (?, ?, 1, NOW())";
    $stmt = $conn->prepare($sql);
    $result = $stmt->execute([$email, $name]);

    if ($result) {
        // Enviar email de confirmación (en un entorno real)
        // mail($email, 'Confirmación de suscripción', 'Gracias por suscribirte a nuestro newsletter...');
        
        sendJsonResponse([
            'success' => true,
            'message' => '¡Gracias por suscribirte! Pronto recibirás nuestras novedades.'
        ]);
    } else {
        sendJsonResponse([
            'success' => false,
            'message' => 'Error al procesar tu suscripción. Por favor, inténtalo de nuevo.'
        ]);
    }

} catch (PDOException $e) {
    // Log del error (en un entorno de producción deberías usar un sistema de logs)
    error_log("Error en newsletter-subscribe.php: " . $e->getMessage());
    
    // Verificar si es un error de duplicado
    if ($e->getCode() == 23000) {
        sendJsonResponse([
            'success' => false,
            'message' => 'Este email ya está suscrito a nuestro newsletter'
        ]);
    } else {
        sendJsonResponse([
            'success' => false,
            'message' => 'Error al procesar tu suscripción. Por favor, inténtalo de nuevo.'
        ]);
    }
} catch (Exception $e) {
    error_log("Error general en newsletter-subscribe.php: " . $e->getMessage());
    sendJsonResponse([
        'success' => false,
        'message' => 'Error interno del servidor. Por favor, inténtalo de nuevo.'
    ]);
}
