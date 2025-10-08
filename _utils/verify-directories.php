<?php
// Script para verificar y crear directorios necesarios
echo "🔍 Verificando directorios necesarios...\n";

$directories = [
    'uploads/products',
    'uploads/categories', 
    'uploads/brands',
    'uploads/banners',
    'uploads/news',
    'assets/images/brands',
    'cache',
    'public/images'
];

$created = 0;
$existing = 0;

foreach ($directories as $dir) {
    if (!is_dir($dir)) {
        if (mkdir($dir, 0755, true)) {
            echo "✅ Creado: $dir\n";
            $created++;
        } else {
            echo "❌ Error creando: $dir\n";
        }
    } else {
        echo "✅ Existe: $dir\n";
        $existing++;
    }
}

echo "\n📊 Resumen:\n";
echo "• Directorios existentes: $existing\n";
echo "• Directorios creados: $created\n";
echo "• Total verificados: " . count($directories) . "\n";

// Verificar permisos
echo "\n🔐 Verificando permisos de escritura...\n";
foreach ($directories as $dir) {
    if (is_writable($dir)) {
        echo "✅ $dir - Escribible\n";
    } else {
        echo "⚠️  $dir - No escribible\n";
    }
}

echo "\n🎉 ¡Verificación completada!\n";
?>
