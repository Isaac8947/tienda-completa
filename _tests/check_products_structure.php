<?php
require_once '../config/database.php';

try {
    $db = new Database();
    $pdo = $db->getConnection();
    
    $stmt = $pdo->query('DESCRIBE products');
    echo "Estructura de la tabla products:\n";
    echo "Campo\t\t\tTipo\t\t\tDefault\n";
    echo "------------------------------------------------------------\n";
    
    while($row = $stmt->fetch()) {
        echo $row['Field'] . "\t\t" . $row['Type'] . "\t\t" . ($row['Default'] ?? 'NULL') . "\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
