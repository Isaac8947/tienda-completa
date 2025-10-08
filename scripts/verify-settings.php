<?php
require_once '../config/database.php';

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    echo "Verificando tabla settings...\n\n";
    
    // Verificar estructura de la tabla
    $stmt = $conn->query("DESCRIBE settings");
    echo "Estructura de la tabla:\n";
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "- {$row['Field']}: {$row['Type']}\n";
    }
    
    echo "\n";
    
    // Contar registros
    $stmt = $conn->query("SELECT COUNT(*) as total FROM settings");
    $count = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "Total de configuraciones: {$count['total']}\n\n";
    
    // Mostrar algunas configuraciones
    $stmt = $conn->query("SELECT setting_key, setting_value FROM settings LIMIT 5");
    echo "Configuraciones de ejemplo:\n";
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "- {$row['setting_key']}: {$row['setting_value']}\n";
    }
    
    echo "\n✅ La tabla settings está funcionando correctamente!\n";
    
} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
