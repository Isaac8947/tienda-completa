<?php
// Test script for brand search functionality
require_once 'config/database.php';
require_once 'models/Brand.php';

try {
    $brandModel = new Brand();
    
    // Test search
    $testQuery = '';  // Empty query to get all brands first
    echo "Getting all active brands...\n";
    
    $sql = "SELECT name FROM brands WHERE is_active = 1 LIMIT 5";
    $db = new Database();
    $conn = $db->getConnection();
    $stmt = $conn->query($sql);
    $allBrands = $stmt->fetchAll();
    
    echo "Available brands:\n";
    foreach ($allBrands as $brand) {
        echo "- {$brand['name']}\n";
    }
    
    // Test with first available brand
    if (!empty($allBrands)) {
        $testQuery = substr($allBrands[0]['name'], 0, 3); // First 3 characters
        echo "\nTesting brand search with query: '$testQuery'\n";
        
        $brands = $brandModel->searchBrands($testQuery, 5);
        
        echo "Found " . count($brands) . " brands:\n";
        foreach ($brands as $brand) {
            echo "- {$brand['name']} ({$brand['product_count']} products)\n";
        }
        
        // Test API endpoint
        echo "\nTesting API endpoint...\n";
        $url = "http://localhost/odisea-makeup-store/api/search.php?q=" . urlencode($testQuery) . "&type=brands&limit=3";
    } else {
        echo "No brands found in database\n";
        $url = 'http://localhost/odisea-makeup-store/api/search.php?q=a&type=brands&limit=3';
    }
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "API Response (HTTP $httpCode):\n";
    echo $response . "\n";
    
    if ($httpCode === 200) {
        $data = json_decode($response, true);
        if ($data && isset($data['brands'])) {
            echo "API returned " . count($data['brands']) . " brands\n";
        }
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
