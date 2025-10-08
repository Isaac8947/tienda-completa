<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: text/html; charset=UTF-8');

echo "<h2>Diagnóstico Simple de Producto</h2>";

require_once 'config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    $product_id = isset($_GET['id']) ? intval($_GET['id']) : 14;
    echo "<p>Verificando producto con ID: <strong>$product_id</strong></p>";
    
    // Verificar si existe el producto (sin filtro de status)
    $sql = "SELECT id, name, status, price, main_image FROM products WHERE id = ?";
    $stmt = $db->prepare($sql);
    $stmt->execute([$product_id]);
    $product = $stmt->fetch();
    
    if ($product) {
        echo "<div style='background: #dff0d8; padding: 10px; margin: 10px 0; border-radius: 5px;'>";
        echo "<h3>✅ Producto encontrado:</h3>";
        echo "<ul>";
        echo "<li><strong>ID:</strong> " . $product['id'] . "</li>";
        echo "<li><strong>Nombre:</strong> " . $product['name'] . "</li>";
        echo "<li><strong>Estado:</strong> " . $product['status'] . "</li>";
        echo "<li><strong>Precio:</strong> $" . $product['price'] . "</li>";
        echo "<li><strong>Imagen:</strong> " . ($product['main_image'] ?: 'No definida') . "</li>";
        echo "</ul>";
        echo "</div>";
        
        if ($product['status'] !== 'active') {
            echo "<div style='background: #fcf8e3; padding: 10px; margin: 10px 0; border-radius: 5px;'>";
            echo "<strong>⚠️ PROBLEMA:</strong> El producto tiene status '{$product['status']}' pero debe ser 'active'";
            echo "</div>";
        }
        
    } else {
        echo "<div style='background: #f2dede; padding: 10px; margin: 10px 0; border-radius: 5px;'>";
        echo "<strong>❌ Producto no encontrado</strong>";
        echo "</div>";
        
        // Mostrar productos disponibles
        echo "<h3>Productos disponibles:</h3>";
        $allSql = "SELECT id, name, status FROM products ORDER BY id LIMIT 10";
        $allStmt = $db->prepare($allSql);
        $allStmt->execute();
        $allProducts = $allStmt->fetchAll();
        
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>ID</th><th>Nombre</th><th>Estado</th><th>Acción</th></tr>";
        foreach ($allProducts as $p) {
            echo "<tr>";
            echo "<td>{$p['id']}</td>";
            echo "<td>{$p['name']}</td>";
            echo "<td>{$p['status']}</td>";
            echo "<td><a href='?id={$p['id']}'>Ver</a> | <a href='product.php?id={$p['id']}' target='_blank'>Ir a producto</a></td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
} catch (Exception $e) {
    echo "<div style='background: #f2dede; padding: 10px; margin: 10px 0; border-radius: 5px;'>";
    echo "<strong>Error:</strong> " . $e->getMessage();
    echo "</div>";
}
?>
