<?php
require_once 'config/database.php';

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    $sql = file_get_contents('_utils/create_review_interactions_tables.sql');
    $conn->exec($sql);
    
    echo "Tablas creadas exitosamente\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
