<?php
require_once 'config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    echo "<h2>Activando productos</h2>";
    
    // Mostrar productos actuales
    $currentSql = "SELECT id, name, status FROM products ORDER BY id LIMIT 10";
    $currentStmt = $db->prepare($currentSql);
    $currentStmt->execute();
    $products = $currentStmt->fetchAll();
    
    echo "<h3>Estado actual de productos:</h3>";
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>ID</th><th>Nombre</th><th>Estado Actual</th></tr>";
    foreach ($products as $product) {
        echo "<tr>";
        echo "<td>{$product['id']}</td>";
        echo "<td>{$product['name']}</td>";
        echo "<td>{$product['status']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Activar los primeros 10 productos
    $updateSql = "UPDATE products SET status = 'active' WHERE id <= 20";
    $updateStmt = $db->prepare($updateSql);
    $success = $updateStmt->execute();
    
    if ($success) {
        echo "<div style='background: #dff0d8; padding: 10px; margin: 10px 0;'>";
        echo "✅ Productos activados exitosamente";
        echo "</div>";
        
        // Mostrar productos después de la actualización
        $updatedStmt = $db->prepare($currentSql);
        $updatedStmt->execute();
        $updatedProducts = $updatedStmt->fetchAll();
        
        echo "<h3>Estado después de activación:</h3>";
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>ID</th><th>Nombre</th><th>Nuevo Estado</th><th>Ver Producto</th></tr>";
        foreach ($updatedProducts as $product) {
            echo "<tr>";
            echo "<td>{$product['id']}</td>";
            echo "<td>{$product['name']}</td>";
            echo "<td>{$product['status']}</td>";
            echo "<td><a href='product.php?id={$product['id']}' target='_blank'>Ver</a></td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<div style='background: #f2dede; padding: 10px; margin: 10px 0;'>";
        echo "❌ Error al activar productos";
        echo "</div>";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
