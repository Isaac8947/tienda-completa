<?php
// Verificar si el admin está logueado
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

// Verificar si la sesión es válida
require_once '../models/Admin.php';
$admin = new Admin();
$adminData = $admin->findById($_SESSION['admin_id']);

if (!$adminData || !$adminData['is_active']) {
    session_destroy();
    header('Location: login.php');
    exit();
}

// Actualizar última actividad
$admin->update($_SESSION['admin_id'], ['last_activity' => date('Y-m-d H:i:s')]);
?>
