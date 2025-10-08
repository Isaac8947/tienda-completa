<?php
session_start();
header('Content-Type: application/json');

echo "=== DEBUG API REVIEWS ===\n\n";

// 1. Check session
echo "1. SESIÓN:\n";
echo "user_id: " . (isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'NO SET') . "\n";
echo "customer_id: " . (isset($_SESSION['customer_id']) ? $_SESSION['customer_id'] : 'NO SET') . "\n";
echo "name: " . (isset($_SESSION['name']) ? $_SESSION['name'] : 'NO SET') . "\n\n";

// 2. Check POST data
echo "2. DATOS POST:\n";
foreach ($_POST as $key => $value) {
    echo "$key: $value\n";
}
echo "\n";

// 3. Check method
echo "3. MÉTODO: " . $_SERVER['REQUEST_METHOD'] . "\n\n";

// 4. Check database connection
try {
    require_once '../../config/database.php';
    $database = new Database();
    $db = $database->getConnection();
    echo "4. BASE DE DATOS: ✅ Conectado\n\n";
    
    // 5. Check reviews table structure
    echo "5. ESTRUCTURA TABLA REVIEWS:\n";
    $stmt = $db->query("DESCRIBE reviews");
    while ($row = $stmt->fetch()) {
        echo "   " . $row['Field'] . " (" . $row['Type'] . ")\n";
    }
    
} catch (Exception $e) {
    echo "4. BASE DE DATOS: ❌ Error: " . $e->getMessage() . "\n";
}
?>
