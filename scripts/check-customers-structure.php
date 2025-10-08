<?php
require_once '../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    echo "ESTRUCTURA TABLA CUSTOMERS:\n";
    echo "===========================\n";
    
    $stmt = $db->query("DESCRIBE customers");
    while ($row = $stmt->fetch()) {
        echo "   " . $row['Field'] . " (" . $row['Type'] . ")\n";
    }
    
    echo "\nEJEMPLO DE DATOS:\n";
    echo "=================\n";
    
    $stmt = $db->query("SELECT id, first_name, last_name, email FROM customers LIMIT 3");
    while ($row = $stmt->fetch()) {
        echo "   ID: {$row['id']} | Nombre: {$row['first_name']} {$row['last_name']} | Email: {$row['email']}\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
