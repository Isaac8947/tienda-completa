<?php
session_start();
header('Content-Type: text/plain');

echo "=== DEBUG SESIÓN ACTUAL ===\n\n";

echo "1. SESIÓN:\n";
if (isset($_SESSION)) {
    foreach ($_SESSION as $key => $value) {
        echo "   $key: " . (is_array($value) ? json_encode($value) : $value) . "\n";
    }
} else {
    echo "   No hay sesión activa\n";
}

echo "\n2. DATOS DEL REQUEST:\n";
echo "   Method: " . $_SERVER['REQUEST_METHOD'] . "\n";
echo "   URI: " . $_SERVER['REQUEST_URI'] . "\n";

echo "\n3. LOGIN STATUS:\n";
echo "   user_id: " . (isset($_SESSION['user_id']) ? '✅ ' . $_SESSION['user_id'] : '❌ No set') . "\n";
echo "   customer_id: " . (isset($_SESSION['customer_id']) ? '✅ ' . $_SESSION['customer_id'] : '❌ No set') . "\n";
echo "   user_email: " . (isset($_SESSION['user_email']) ? '✅ ' . $_SESSION['user_email'] : '❌ No set') . "\n";
echo "   user_name: " . (isset($_SESSION['user_name']) ? '✅ ' . $_SESSION['user_name'] : '❌ No set') . "\n";

echo "\n4. TEST API REQUIREMENTS:\n";
echo "   Can write reviews: " . (isset($_SESSION['user_id']) ? '✅ YES' : '❌ NO - Must login') . "\n";
?>
