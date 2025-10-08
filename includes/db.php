<?php
// includes/db.php
// Configura aquí tus datos de conexión
$host = 'localhost';
$db   = 'odisea_makeup';
$user = 'root'; // Cambia si usas otro usuario
$pass = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

function getPDO() {
    global $dsn, $user, $pass, $options;
    return new PDO($dsn, $user, $pass, $options);
}
