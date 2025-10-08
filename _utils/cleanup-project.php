<?php
/**
 * Script de Limpieza del Proyecto
 * Elimina archivos basura y optimiza la estructura del proyecto
 */

echo "🧹 INICIANDO LIMPIEZA DEL PROYECTO\n";
echo "==================================\n\n";

// Archivos a eliminar - organizados por categoría
$filesToDelete = [
    // ========== ARCHIVOS DE DEBUG ==========
    'debug_tecnologia.php',
    'debug_simple.php', 
    'debug_related_section.php',
    'debug_related_products.php',
    'debug_products_check.php',
    'debug_products.php',
    'debug_categories_images.php',
    'debug_categoria.php',
    'product-debug.php',
    'debug-quickview.html',
    
    // ========== ARCHIVOS DE TEST ==========
    'test_categoria_links.php',
    'test_categories_debug.php',
    'test_ofertas_output.html',
    'test_simple_tecnologia.php',
    'test_table_categories.php',
    'test-android-responsive.html',
    'test-cart-debug.html',
    'test-cart-simple.php',
    'test-javascript.html',
    'test-js-functions.html',
    'test-redirect.html',
    'test_search.php',
    
    // ========== ARCHIVOS DE VERIFICACIÓN ==========
    'check_brands_structure.php',
    'check_brands.php',
    'check_category_images.php',
    'check_related_products.php',
    
    // ========== ARCHIVOS DE SETUP/DEMO ==========
    'setup_categories.php',
    'setup_search_data.php',
    'demo-search.php',
    
    // ========== BACKUPS Y DUPLICADOS ==========
    'search-backup.php',
    'search-clean.php',  // Mantenemos search.php principal
    'catalogo-original-backup.php',
    'index-techstore.php', // Mantenemos index.php principal
    
    // ========== PÁGINAS PRODUCT REDUNDANTES ==========
    'product-details.php', // Solo API, reemplazado por product.php
    'details.php', // Redundante con product.php
    
    // ========== ARCHIVOS OBSOLETOS ==========
    'fix_categories.php',
    'search-advanced.php', // Funcionalidad integrada en search.php
    'catalogo-ajax.php', // Si no se usa
];

// Directorios completos a limpiar
$dirsToClean = [
    '_tests' => [
        'action' => 'selective', // No eliminar todo, solo ciertos archivos
        'keep' => ['check_products_structure.php'] // Archivos importantes a mantener
    ]
];

$deletedFiles = 0;
$keptFiles = 0;
$errors = 0;

echo "📂 ELIMINANDO ARCHIVOS BASURA...\n";
echo "--------------------------------\n";

foreach ($filesToDelete as $file) {
    $fullPath = __DIR__ . '/../' . $file;
    
    if (file_exists($fullPath)) {
        if (unlink($fullPath)) {
            echo "✅ Eliminado: $file\n";
            $deletedFiles++;
        } else {
            echo "❌ Error eliminando: $file\n";
            $errors++;
        }
    } else {
        echo "⚪ No existe: $file\n";
    }
}

echo "\n📁 LIMPIANDO DIRECTORIOS DE TEST...\n";
echo "----------------------------------\n";

// Limpiar directorio _tests selectivamente
$testsDir = __DIR__ . '/../_tests/';
if (is_dir($testsDir)) {
    $files = scandir($testsDir);
    foreach ($files as $file) {
        if ($file === '.' || $file === '..') continue;
        
        // Mantener archivos importantes
        $keepFiles = [
            'check_products_structure.php',
            'test_functionality.php', 
            'test_global_header.php'
        ];
        
        if (!in_array($file, $keepFiles)) {
            $filePath = $testsDir . $file;
            if (is_file($filePath)) {
                if (unlink($filePath)) {
                    echo "✅ Eliminado test: _tests/$file\n";
                    $deletedFiles++;
                } else {
                    echo "❌ Error eliminando test: _tests/$file\n";
                    $errors++;
                }
            }
        } else {
            echo "💾 Mantenido: _tests/$file\n";
            $keptFiles++;
        }
    }
}

echo "\n🔄 ACTUALIZANDO REFERENCIAS A PRODUCT PAGES...\n";
echo "---------------------------------------------\n";

// Actualizar referencias de details.php y product-details.php a product.php
$filesToUpdate = [
    'assets/js/global-search.js',
    'assets/js/main-clean.js', 
    'assets/js/main-backup.js',
    'search.php',
    'includes/global-search.php'
];

foreach ($filesToUpdate as $file) {
    $fullPath = __DIR__ . '/../' . $file;
    
    if (file_exists($fullPath)) {
        $content = file_get_contents($fullPath);
        $originalContent = $content;
        
        // Reemplazar referencias
        $content = str_replace('details.php', 'product.php', $content);
        $content = str_replace('product-details.php', 'product.php', $content);
        
        if ($content !== $originalContent) {
            if (file_put_contents($fullPath, $content)) {
                echo "✅ Actualizado: $file\n";
            } else {
                echo "❌ Error actualizando: $file\n";
                $errors++;
            }
        } else {
            echo "⚪ Sin cambios: $file\n";
        }
    }
}

echo "\n🗂️ LIMPIANDO CACHE...\n";
echo "-------------------\n";

// Limpiar archivos de cache antiguos
$cacheDir = __DIR__ . '/../cache/';
if (is_dir($cacheDir)) {
    $files = glob($cacheDir . '*.json');
    foreach ($files as $file) {
        // Mantener cache reciente (menos de 1 día)
        if (filemtime($file) < strtotime('-1 day')) {
            if (unlink($file)) {
                echo "✅ Cache eliminado: " . basename($file) . "\n";
                $deletedFiles++;
            }
        }
    }
}

echo "\n📊 CREANDO REPORTE DE LIMPIEZA...\n";
echo "--------------------------------\n";

// Crear reporte
$report = [
    'fecha_limpieza' => date('Y-m-d H:i:s'),
    'archivos_eliminados' => $deletedFiles,
    'archivos_mantenidos' => $keptFiles,
    'errores' => $errors,
    'archivos_principales' => [
        'product.php' => 'Página principal de productos ✅',
        'search.php' => 'Página de búsqueda ✅', 
        'catalogo.php' => 'Catálogo de productos ✅',
        'categoria.php' => 'Página de categorías ✅',
        'ofertas.php' => 'Página de ofertas ✅',
        'index.php' => 'Página principal ✅'
    ],
    'archivos_eliminados_list' => $filesToDelete,
    'recomendaciones' => [
        '1. Usar solo product.php para mostrar productos individuales',
        '2. Mantener search.php actualizado con funciones AJAX',
        '3. Revisar periódicamente archivos no utilizados',
        '4. Usar el directorio _utils para scripts de mantenimiento'
    ]
];

$reportContent = "# REPORTE DE LIMPIEZA DEL PROYECTO\n";
$reportContent .= "Fecha: " . $report['fecha_limpieza'] . "\n\n";
$reportContent .= "## ESTADÍSTICAS\n";
$reportContent .= "- ✅ Archivos eliminados: " . $report['archivos_eliminados'] . "\n";
$reportContent .= "- 💾 Archivos mantenidos: " . $report['archivos_mantenidos'] . "\n";
$reportContent .= "- ❌ Errores: " . $report['errores'] . "\n\n";

$reportContent .= "## ARCHIVOS PRINCIPALES ACTIVOS\n";
foreach ($report['archivos_principales'] as $file => $status) {
    $reportContent .= "- $file: $status\n";
}

$reportContent .= "\n## RECOMENDACIONES\n";
foreach ($report['recomendaciones'] as $rec) {
    $reportContent .= "- $rec\n";
}

file_put_contents(__DIR__ . '/cleanup-report.md', $reportContent);

echo "\n🎉 LIMPIEZA COMPLETADA!\n";
echo "======================\n";
echo "✅ Archivos eliminados: $deletedFiles\n";
echo "💾 Archivos mantenidos: $keptFiles\n";
echo "❌ Errores: $errors\n";
echo "📄 Reporte guardado en: _utils/cleanup-report.md\n\n";

echo "🚀 ESTRUCTURA OPTIMIZADA:\n";
echo "- ✅ product.php (página principal de productos)\n";
echo "- ✅ search.php (búsqueda con AJAX)\n";
echo "- ✅ catalogo.php (listado de productos)\n";
echo "- ✅ categoria.php (productos por categoría)\n";
echo "- ✅ ofertas.php (productos en oferta)\n";
echo "- ✅ index.php (página principal)\n\n";

echo "🔧 PRÓXIMOS PASOS RECOMENDADOS:\n";
echo "1. Verificar que product.php funciona correctamente\n";
echo "2. Probar el sistema de búsqueda AJAX\n";
echo "3. Revisar navegación entre páginas\n";
echo "4. Ejecutar tests de funcionalidad\n\n";

?>
