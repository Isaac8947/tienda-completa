<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Start session to check authentication
session_start();

require_once '../../config/database.php';
require_once '../../includes/InputSanitizer.php';

try {
    // Check if user is logged in
    if (!isset($_SESSION['user_id'])) {
        echo json_encode([
            'success' => false,
            'message' => 'Debes iniciar sesión para escribir una reseña'
        ]);
        exit;
    }

    // Validate request method
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode([
            'success' => false,
            'message' => 'Método no permitido'
        ]);
        exit;
    }

    // Get and validate input data
    $product_id = intval($_POST['product_id'] ?? 0);
    $rating = intval($_POST['rating'] ?? 0);
    $title = InputSanitizer::sanitizeString($_POST['title'] ?? '', 100);
    $comment = InputSanitizer::sanitizeText($_POST['comment'] ?? '', 1000);
    $customer_id = $_SESSION['user_id'];

    // Validation
    if ($product_id <= 0) {
        echo json_encode([
            'success' => false,
            'message' => 'ID de producto inválido'
        ]);
        exit;
    }

    if ($rating < 1 || $rating > 5) {
        echo json_encode([
            'success' => false,
            'message' => 'La calificación debe estar entre 1 y 5 estrellas'
        ]);
        exit;
    }

    if (empty($title) || strlen(trim($title)) < 5) {
        echo json_encode([
            'success' => false,
            'message' => 'El título debe tener al menos 5 caracteres'
        ]);
        exit;
    }

    if (empty($comment) || strlen(trim($comment)) < 10) {
        echo json_encode([
            'success' => false,
            'message' => 'El comentario debe tener al menos 10 caracteres'
        ]);
        exit;
    }

    // Check for malicious content
    if (InputSanitizer::detectXSS($title) || InputSanitizer::detectXSS($comment)) {
        echo json_encode([
            'success' => false,
            'message' => 'Contenido no válido detectado'
        ]);
        exit;
    }

    // Connect to database
    $database = new Database();
    $db = $database->getConnection();

    // Check if product exists
    $productCheck = $db->prepare("SELECT id FROM products WHERE id = ? AND status = 'active'");
    $productCheck->execute([$product_id]);
    if (!$productCheck->fetch()) {
        echo json_encode([
            'success' => false,
            'message' => 'Producto no encontrado'
        ]);
        exit;
    }

    // Check if user already reviewed this product
    $existingReview = $db->prepare("SELECT id FROM reviews WHERE product_id = ? AND customer_id = ?");
    $existingReview->execute([$product_id, $customer_id]);
    if ($existingReview->fetch()) {
        echo json_encode([
            'success' => false,
            'message' => 'Ya has escrito una reseña para este producto. Puedes editarla desde tu perfil.'
        ]);
        exit;
    }

    // Get customer name for the review
    $customerInfo = $db->prepare("SELECT first_name, last_name FROM customers WHERE id = ?");
    $customerInfo->execute([$customer_id]);
    $customer = $customerInfo->fetch();
    
    $reviewer_name = trim(($customer['first_name'] ?? '') . ' ' . ($customer['last_name'] ?? ''));
    if (empty($reviewer_name)) {
        $reviewer_name = 'Usuario Verificado';
    }

    // Insert the review
    $insertReview = $db->prepare("
        INSERT INTO reviews (
            product_id, 
            customer_id, 
            reviewer_name,
            rating, 
            title, 
            comment, 
            status,
            created_at
        ) VALUES (?, ?, ?, ?, ?, ?, 'approved', NOW())
    ");

    if ($insertReview->execute([$product_id, $customer_id, $reviewer_name, $rating, $title, $comment])) {
        // Update product rating statistics
        $updateStats = $db->prepare("
            UPDATE products 
            SET 
                average_rating = (
                    SELECT AVG(rating) 
                    FROM reviews 
                    WHERE product_id = ? AND status = 'approved'
                ),
                total_reviews = (
                    SELECT COUNT(*) 
                    FROM reviews 
                    WHERE product_id = ? AND status = 'approved'
                ),
                five_star_count = (
                    SELECT COUNT(*) 
                    FROM reviews 
                    WHERE product_id = ? AND rating = 5 AND status = 'approved'
                ),
                four_star_count = (
                    SELECT COUNT(*) 
                    FROM reviews 
                    WHERE product_id = ? AND rating = 4 AND status = 'approved'
                ),
                three_star_count = (
                    SELECT COUNT(*) 
                    FROM reviews 
                    WHERE product_id = ? AND rating = 3 AND status = 'approved'
                ),
                two_star_count = (
                    SELECT COUNT(*) 
                    FROM reviews 
                    WHERE product_id = ? AND rating = 2 AND status = 'approved'
                ),
                one_star_count = (
                    SELECT COUNT(*) 
                    FROM reviews 
                    WHERE product_id = ? AND rating = 1 AND status = 'approved'
                )
            WHERE id = ?
        ");
        
        $updateStats->execute([
            $product_id, $product_id, $product_id, 
            $product_id, $product_id, $product_id, 
            $product_id, $product_id
        ]);

        echo json_encode([
            'success' => true,
            'message' => 'Reseña publicada exitosamente',
            'review_id' => $db->lastInsertId()
        ]);

    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Error al guardar la reseña. Inténtalo de nuevo.'
        ]);
    }

} catch (Exception $e) {
    error_log("Error creating review: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error interno del servidor'
    ]);
}
?>
