<?php
require_once '../includes/security-headers.php';
require_once '../includes/CSRFProtection.php';

header('Content-Type: application/json');
session_start();

// Get context from query parameter
$context = $_GET['context'] ?? 'general';

// Validate context
$validContexts = ['cart', 'wishlist', 'review', 'general'];
if (!in_array($context, $validContexts)) {
    $context = 'general';
}

try {
    $token = CSRFProtection::generateToken($context);
    echo json_encode([
        'success' => true,
        'token' => $token,
        'context' => $context
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Could not generate token'
    ]);
}
?>
