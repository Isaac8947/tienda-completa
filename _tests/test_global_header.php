<?php
echo "=== TEST GLOBAL HEADER ===\n";

// Activar errores
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Simular sesión
session_start();

echo "1. Incluyendo dependencias...\n";

try {
    require_once 'config/global-settings.php';
    echo "2. global-settings.php incluido exitosamente\n";
    
    echo "3. Iniciando buffer de salida...\n";
    ob_start();
    
    echo "4. Incluyendo global-header.php...\n";
    include 'includes/global-header.php';
    
    $header_content = ob_get_contents();
    ob_end_clean();
    
    echo "5. global-header.php incluido exitosamente\n";
    echo "6. Longitud del contenido: " . strlen($header_content) . " caracteres\n";
    
    // Verificar si hay errores en el contenido
    if (strpos($header_content, 'Fatal error') !== false || 
        strpos($header_content, 'Parse error') !== false ||
        strpos($header_content, 'Warning') !== false) {
        echo "7. ERRORES ENCONTRADOS EN EL HEADER:\n";
        echo $header_content . "\n";
    } else {
        echo "7. Header generado sin errores PHP\n";
        echo "8. Primeros 200 caracteres:\n";
        echo substr($header_content, 0, 200) . "...\n";
    }
    
    echo "=== TEST COMPLETADO EXITOSAMENTE ===\n";
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
?>
