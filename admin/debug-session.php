<?php
session_start();

echo "<h2>Debug de Sesión</h2>";
echo "<pre>";
echo "SESSION DATA:\n";
print_r($_SESSION);

if (isset($_SESSION['user_id'])) {
    echo "\nUser ID: " . $_SESSION['user_id'];
    echo "\nRole: " . ($_SESSION['role'] ?? 'No definido');
    echo "\nIs Admin: " . (($_SESSION['role'] ?? '') === 'admin' ? 'SÍ' : 'NO');
} else {
    echo "\nNo hay sesión activa";
}

echo "\nSERVER DATA:\n";
echo "PHP_SELF: " . $_SERVER['PHP_SELF'];
echo "\nREQUEST_URI: " . $_SERVER['REQUEST_URI'];
echo "</pre>";
?>
