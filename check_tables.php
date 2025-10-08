<?php
$pdo = new PDO('mysql:host=localhost;dbname=odisea_makeup', 'root', '');
$result = $pdo->query('DESCRIBE orders');
echo "=== ESTRUCTURA TABLA ORDERS ===\n";
while ($row = $result->fetch()) {
    echo $row['Field'] . ' - ' . $row['Type'] . "\n";
}

echo "\n=== ESTRUCTURA TABLA ORDER_ITEMS ===\n";
$result = $pdo->query('DESCRIBE order_items');
while ($row = $result->fetch()) {
    echo $row['Field'] . ' - ' . $row['Type'] . "\n";
}

echo "\n=== ESTRUCTURA TABLA INVENTORY_HISTORY ===\n";
$result = $pdo->query('DESCRIBE inventory_history');
while ($row = $result->fetch()) {
    echo $row['Field'] . ' - ' . $row['Type'] . "\n";
}
?>
