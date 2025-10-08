<?php
// Test script to verify authentication structure

echo "<h1>Test de Estructura de Autenticación</h1>";

echo "<h2>Archivos de Login:</h2>";
echo "<ul>";

// Check login.php
if (file_exists('login.php')) {
    echo "<li>✅ login.php existe</li>";
    $loginContent = file_get_contents('login.php');
    if (strpos($loginContent, 'login-new.php') !== false) {
        echo "<li>✅ login.php redirige a login-new.php</li>";
    } else {
        echo "<li>❌ login.php NO redirige correctamente</li>";
    }
} else {
    echo "<li>❌ login.php NO existe</li>";
}

// Check login-new.php
if (file_exists('login-new.php')) {
    echo "<li>✅ login-new.php existe</li>";
    $loginNewContent = file_get_contents('login-new.php');
    if (strpos($loginNewContent, 'action="login-new.php') !== false) {
        echo "<li>✅ login-new.php form envía a sí mismo</li>";
    } else {
        echo "<li>❌ login-new.php form NO envía a sí mismo</li>";
    }
} else {
    echo "<li>❌ login-new.php NO existe</li>";
}

echo "</ul>";

echo "<h2>Archivos de Registro:</h2>";
echo "<ul>";

// Check register.php
if (file_exists('register.php')) {
    echo "<li>✅ register.php existe</li>";
    $registerContent = file_get_contents('register.php');
    if (strpos($registerContent, 'register-new.php') !== false) {
        echo "<li>✅ register.php redirige a register-new.php</li>";
    } else {
        echo "<li>❌ register.php NO redirige correctamente</li>";
    }
} else {
    echo "<li>❌ register.php NO existe</li>";
}

// Check register-new.php
if (file_exists('register-new.php')) {
    echo "<li>✅ register-new.php existe</li>";
    $registerNewContent = file_get_contents('register-new.php');
    if (strpos($registerNewContent, 'action="register-new.php') !== false) {
        echo "<li>✅ register-new.php form envía a sí mismo</li>";
    } else {
        echo "<li>❌ register-new.php form NO envía a sí mismo</li>";
    }
    
    if (strpos($registerNewContent, 'login-new.php?registered=success') !== false) {
        echo "<li>✅ register-new.php redirige a login-new.php después del registro exitoso</li>";
    } else {
        echo "<li>❌ register-new.php NO redirige correctamente después del registro</li>";
    }
} else {
    echo "<li>❌ register-new.php NO existe</li>";
}

echo "</ul>";

echo "<h2>Dependencias:</h2>";
echo "<ul>";

// Check required includes/models
$requiredFiles = [
    'config/database.php',
    'models/Customer.php',
    'includes/LoginProtection.php',
    'includes/CSRFProtection.php'
];

foreach ($requiredFiles as $file) {
    if (file_exists($file)) {
        echo "<li>✅ $file existe</li>";
    } else {
        echo "<li>❌ $file NO existe</li>";
    }
}

echo "</ul>";

echo "<h2>Resumen:</h2>";
echo "<p>Si todos los elementos arriba están marcados con ✅, entonces la estructura de autenticación debería funcionar correctamente.</p>";
?>
