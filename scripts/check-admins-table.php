<?php
require_once '../config/database.php';

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    echo "Estructura de la tabla admins:\n";
    $stmt = $conn->query('DESCRIBE admins');
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "- " . $row['Field'] . " (" . $row['Type'] . ")\n";
    }
    
    echo "\nDatos de ejemplo de admin:\n";
    $stmt = $conn->query('SELECT * FROM admins LIMIT 1');
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($admin) {
        foreach ($admin as $key => $value) {
            echo "- $key: " . (is_null($value) ? 'NULL' : $value) . "\n";
        }
    } else {
        echo "No hay administradores en la base de datos.\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
