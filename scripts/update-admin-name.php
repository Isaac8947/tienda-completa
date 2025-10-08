<?php
require_once '../config/database.php';

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    echo "Actualizando datos del administrador...\n";
    
    // Actualizar el full_name del admin principal
    $stmt = $conn->prepare("UPDATE admins SET full_name = ? WHERE id = 1");
    $stmt->execute(['Administrador Principal']);
    
    echo "✓ Nombre completo del administrador actualizado.\n";
    
    // Verificar el cambio
    $stmt = $conn->query('SELECT username, full_name FROM admins WHERE id = 1');
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($admin) {
        echo "\nDatos actualizados:\n";
        echo "- Username: " . $admin['username'] . "\n";
        echo "- Full name: " . $admin['full_name'] . "\n";
    }
    
    echo "\n✅ Admin actualizado correctamente!\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
