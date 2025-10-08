<?php
session_start();

// Simular login de administrador para pruebas
$_SESSION['user_id'] = 1;
$_SESSION['role'] = 'admin';
$_SESSION['username'] = 'admin';
$_SESSION['email'] = 'admin@odisea.com';

echo "Sesión de administrador activada correctamente.";
echo "<br><br>";
echo "<a href='index.php'>Ir al Panel de Administrador</a><br>";
echo "<a href='pedidos.php'>Ir a Gestión de Pedidos</a><br>";
echo "<br>";
echo "Datos de sesión:";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";
?>
