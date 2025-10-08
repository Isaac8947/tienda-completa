<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../config/config.php';
require_once '../models/Product.php';
require_once '../models/Brand.php';
require_once '../models/Category.php';

// Test direct API call
$query = 'a';
$brandModel = new Brand();
$brands = $brandModel->searchBrands($query, 3);

echo "=== TEST API SEARCH BRANDS ===\n";
echo "Query: '$query'\n";
echo "Raw results from searchBrands():\n";
print_r($brands);

$results = [];
$results['brands'] = [];
foreach ($brands as $brand) {
    $results['brands'][] = [
        'id' => $brand['id'],
        'name' => $brand['name'],
        'description' => $brand['description'] ?? '',
        'logo' => $brand['logo'] ?? null,
        'product_count' => $brand['product_count'] ?? 0,
        'url' => 'catalogo.php?marca=' . $brand['id']
    ];
}

$results['total'] = count($results['brands']);
$results['has_brands'] = !empty($results['brands']);

echo "\nFormatted results:\n";
echo json_encode($results, JSON_PRETTY_PRINT);
?>
