<?php
// Archivo de prueba PHP
echo "<h1>PHP está funcionando correctamente!</h1>";
echo "<p>Versión de PHP: " . PHP_VERSION . "</p>";
echo "<p>Fecha actual: " . date('Y-m-d H:i:s') . "</p>";

// Verificar conexión a base de datos
try {
    require_once '../config/database.php';
    echo "<p style='color: green;'>✅ Conexión a base de datos: OK</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error de conexión: " . $e->getMessage() . "</p>";
}
?>
