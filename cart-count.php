<?php
// Configuración de sesión segura (debe ir antes de session_start)
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 0); // Cambiar a 1 en HTTPS
ini_set('session.cookie_samesite', 'Strict');

session_start();

header('Content-Type: application/json');

try {
    $cartCount = 0;
    
    if (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {
        foreach ($_SESSION['cart'] as $item) {
            $cartCount += isset($item['quantity']) ? (int)$item['quantity'] : 0;
        }
    }
    
    echo json_encode([
        'success' => true,
        'count' => $cartCount
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'count' => 0
    ]);
}
?>
