<?php
require_once '../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    echo "Iniciando configuración de base de datos para cupones...\n";
    
    // Leer y ejecutar el script SQL
    $sql = file_get_contents(__DIR__ . '/database-setup-coupons.sql');
    
    // Dividir en declaraciones individuales
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    
    $db->beginTransaction();
    
    foreach ($statements as $statement) {
        if (!empty($statement) && !preg_match('/^--/', $statement)) {
            echo "Ejecutando: " . substr($statement, 0, 50) . "...\n";
            $db->exec($statement);
        }
    }
    
    $db->commit();
    
    echo "\n✅ Configuración de cupones completada exitosamente!\n";
    echo "\nTablas creadas:\n";
    echo "- coupons: Tabla principal de cupones\n";
    echo "- coupon_usage: Tabla de historial de uso\n";
    echo "\nDatos de ejemplo insertados:\n";
    echo "- 5 cupones de ejemplo con diferentes configuraciones\n";
    echo "- Registros de uso de ejemplo\n";
    echo "\nTipos de cupones incluidos:\n";
    echo "- WELCOME10: Descuento porcentual para nuevos clientes\n";
    echo "- SAVE20: Descuento fijo con monto mínimo\n";
    echo "- FREESHIP: Cupón de envío gratis\n";
    echo "- VIP25: Cupón exclusivo con restricciones\n";
    echo "- CLEARANCE50: Cupón programado (inactivo)\n";
    
} catch (Exception $e) {
    if (isset($db)) {
        $db->rollback();
    }
    echo "\n❌ Error durante la configuración: " . $e->getMessage() . "\n";
    echo "Línea: " . $e->getLine() . "\n";
    echo "Archivo: " . $e->getFile() . "\n";
}
?>
