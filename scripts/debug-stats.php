<?php
require_once '../config/config.php';
require_once '../models/InventoryHistory.php';

$inventoryHistory = new InventoryHistory();
$stats = $inventoryHistory->getMovementStats('30 days');

echo "Tipo de datos de \$stats: " . gettype($stats) . "\n";
echo "Contenido de \$stats:\n";
var_dump($stats);

if (is_array($stats)) {
    echo "\nProcesando estadísticas:\n";
    $totalIn = 0;
    $totalOut = 0;
    $totalAdjustments = 0;
    
    foreach ($stats as $stat) {
        echo "Stat: " . print_r($stat, true) . "\n";
        
        if (is_array($stat) && isset($stat['movement_type'])) {
            switch ($stat['movement_type']) {
                case 'in':
                    $totalIn += isset($stat['total_quantity']) ? (int)$stat['total_quantity'] : 0;
                    break;
                case 'out':
                    $totalOut += isset($stat['total_quantity']) ? (int)$stat['total_quantity'] : 0;
                    break;
                case 'adjustment':
                    $totalAdjustments += isset($stat['total_movements']) ? (int)$stat['total_movements'] : 0;
                    break;
            }
        }
    }
    
    echo "Total Entradas: $totalIn\n";
    echo "Total Salidas: $totalOut\n";
    echo "Total Ajustes: $totalAdjustments\n";
}
?>
