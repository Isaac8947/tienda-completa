<?php
session_start();

// Mostrar todos los errores para depuración
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config/database.php';
require_once 'models/Product.php';

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    // Activar algunos productos si no lo están
    $conn->exec("UPDATE products SET status = 'active' WHERE status != 'active' LIMIT 5");
    
    $productModel = new Product($conn);
    
    echo "<h2>Prueba rápida de productos</h2>";
    
    // Obtener productos activos
    $stmt = $conn->prepare("SELECT id, name, status FROM products WHERE status = 'active' LIMIT 5");
    $stmt->execute();
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($products)) {
        echo "<p>No hay productos activos.</p>";
    } else {
        echo "<h3>Productos activos encontrados:</h3>";
        foreach ($products as $product) {
            echo "<p>";
            echo "ID: " . $product['id'] . " - ";
            echo "Nombre: " . htmlspecialchars($product['name']) . " - ";
            echo "Estado: " . $product['status'];
            echo " <a href='product.php?id=" . $product['id'] . "' target='_blank'>Ver producto</a>";
            echo "</p>";
        }
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
