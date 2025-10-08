<?php
// Test simple para performance.php
try {
    // Mock de sesión para evitar redirecciones
    $_SESSION['admin_id'] = 1;
    
    // Incluir el archivo para probar errores
    ob_start();
    include '../admin-pages/performance.php';
    ob_end_clean();
    
    echo "✅ El archivo performance.php se carga sin errores.\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
} catch (Error $e) {
    echo "❌ Error fatal: " . $e->getMessage() . "\n";
}
?>
