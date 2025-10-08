<?php
/**
 * Script de limpieza de caché
 * Ejecutar periódicamente para mantener el rendimiento
 */

$cacheDir = __DIR__ . '/cache/';
$maxAge = 3600; // 1 hora

if (is_dir($cacheDir)) {
    $files = glob($cacheDir . '*.json');
    $cleaned = 0;
    
    foreach ($files as $file) {
        if (is_file($file) && (time() - filemtime($file)) > $maxAge) {
            unlink($file);
            $cleaned++;
        }
    }
    
    echo "Caché limpiado: $cleaned archivos eliminados\n";
} else {
    echo "Directorio de caché no encontrado\n";
}
?>
