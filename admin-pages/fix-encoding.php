<?php
// Script para corregir caracteres mal codificados en performance.php

$file = 'performance.php';
$content = file_get_contents($file);

// Correcciones de caracteres mal codificados
$replacements = [
    'MÃ©tricas' => 'Métricas',
    'anÃ¡lisis' => 'análisis',
    'ConversiÃ³n' => 'Conversión',
    'Ã"rdenes' => 'Órdenes',
    'Ãºnico' => 'único',
    'Ãºltimos' => 'Últimos',
    'dÃ­as' => 'días',
    'CrÃ­tico' => 'Crítico',
    'VerificaciÃ³n' => 'Verificación',
    'FunciÃ³n' => 'Función',
    'mÃ¡s' => 'más',
    'grÃ¡ficos' => 'gráficos'
];

foreach ($replacements as $wrong => $correct) {
    $content = str_replace($wrong, $correct, $content);
    echo "Corregido: $wrong -> $correct\n";
}

file_put_contents($file, $content);
echo "\n✅ Archivo corregido exitosamente!\n";
?>
