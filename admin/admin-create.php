<?php
// admin-create.php
require_once '../includes/db.php'; // Usa la conexión existente

// Usuario y contraseña fijos para admin
$username = 'admin';
$email = 'admin@admin.com';
$password = password_hash('123', PASSWORD_BCRYPT);
$full_name = 'Administrador';
$role = 'super_admin';

try {
    $pdo = getPDO();
    $stmt = $pdo->prepare("INSERT INTO admins (username, email, password, full_name, role) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$username, $email, $password, $full_name, $role]);
    echo 'Usuario admin creado correctamente.';
} catch (PDOException $e) {
    if ($e->getCode() == 23000) {
        echo 'El usuario o email ya existe.';
    } else {
        echo 'Error: ' . $e->getMessage();
    }
}
