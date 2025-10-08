<?php
session_start();

// Log de actividad antes de cerrar sesión
if (isset($_SESSION['admin_id'])) {
    require_once '../models/Admin.php';
    $admin = new Admin();
    $admin->logout($_SESSION['admin_id']);
}

// Destruir la sesión
session_destroy();

header('Location: login.php');
exit();
?>
