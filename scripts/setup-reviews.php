<?php
require_once '../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    echo "Ejecutando setup de reseñas...\n";
    
    // Leer el archivo SQL
    $sql = file_get_contents('reviews-setup.sql');
    
    // Ejecutar las consultas
    $statements = explode(';', $sql);
    
    foreach ($statements as $statement) {
        $statement = trim($statement);
        if (!empty($statement)) {
            $db->exec($statement);
            echo "✓ Ejecutado: " . substr($statement, 0, 50) . "...\n";
        }
    }
    
    echo "\n✅ Setup de reseñas completado exitosamente!\n";
    echo "📊 La tabla 'reviews' ha sido creada con datos de ejemplo.\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
