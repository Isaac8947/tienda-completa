<?php
session_start();

echo "<h2>Debug del Carrito</h2>";
echo "<pre>";
print_r($_SESSION['cart'] ?? []);
echo "</pre>";

if (isset($_SESSION['cart']) && !empty($_SESSION['cart'])) {
    echo "<h3>Análisis de cada item:</h3>";
    foreach ($_SESSION['cart'] as $key => $item) {
        echo "<h4>Item $key:</h4>";
        echo "<ul>";
        foreach ($item as $field => $value) {
            echo "<li><strong>$field:</strong> " . (is_array($value) ? json_encode($value) : $value) . "</li>";
        }
        echo "</ul>";
        
        // Verificar imagen específicamente
        if (isset($item['image'])) {
            echo "<p><strong>Imagen encontrada:</strong> " . $item['image'] . "</p>";
            $imagePath = 'uploads/products/' . $item['image'];
            if (file_exists($imagePath)) {
                echo "<p style='color: green;'>✅ Archivo de imagen existe: $imagePath</p>";
            } else {
                echo "<p style='color: red;'>❌ Archivo de imagen NO existe: $imagePath</p>";
            }
        } else {
            echo "<p style='color: orange;'>⚠️ No hay información de imagen en este item</p>";
        }
        echo "<hr>";
    }
} else {
    echo "<p>El carrito está vacío</p>";
}
?>