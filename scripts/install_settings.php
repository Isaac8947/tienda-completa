<?php
/**
 * Script para crear la tabla de configuraciones del sitio
 */

require_once '../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    echo "Creando tabla de configuraciones...\n";
    
    // Leer el archivo SQL
    $sql = file_get_contents('create_site_settings.sql');
    
    // Ejecutar cada statement
    $statements = explode(';', $sql);
    
    foreach ($statements as $statement) {
        $statement = trim($statement);
        if (!empty($statement)) {
            try {
                $db->exec($statement);
                echo "✓ Statement ejecutado exitosamente\n";
            } catch (PDOException $e) {
                if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                    echo "⚠ Configuración ya existe, actualizando...\n";
                } else {
                    echo "Error: " . $e->getMessage() . "\n";
                }
            }
        }
    }
    
    echo "✅ Configuraciones de sitio creadas exitosamente\n";
    
    // Verificar que se crearon las configuraciones
    $stmt = $db->query("SELECT setting_key, setting_value FROM site_settings");
    $settings = $stmt->fetchAll();
    
    echo "\nConfiguraciones disponibles:\n";
    foreach ($settings as $setting) {
        echo "- {$setting['setting_key']}: " . substr($setting['setting_value'], 0, 50) . "...\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
