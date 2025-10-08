<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== DIAGNÓSTICO DE PRODUCTO ===\n";

// Verificar configuración
if (!file_exists('config/database.php')) {
    echo "ERROR: No se encuentra config/database.php\n";
    exit;
}

require_once 'config/database.php';
require_once 'models/Product.php';

// Obtener ID del producto
$product_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
echo "ID del producto recibido: " . $product_id . "\n";

if (!$product_id) {
    echo "ERROR: No se proporcionó ID de producto válido\n";
    echo "URL actual: " . $_SERVER['REQUEST_URI'] . "\n";
    exit;
}

try {
    echo "Intentando conectar a la base de datos...\n";
    $database = new Database();
    $db = $database->getConnection();
    echo "Conexión exitosa\n";
    
    echo "Creando instancia del modelo Product...\n";
    $productModel = new Product();
    echo "Modelo creado exitosamente\n";
    
    echo "Buscando producto con ID: $product_id\n";
    $product = $productModel->getProductWithDetails($product_id);
    
    if (!$product) {
        echo "ERROR: Producto no encontrado en la base de datos\n";
        
        // Verificar si el producto existe en la tabla
        echo "Verificando si existe en la tabla products...\n";
        $checkQuery = "SELECT id, name, status FROM products WHERE id = ?";
        $stmt = $db->prepare($checkQuery);
        $stmt->execute([$product_id]);
        $simpleProduct = $stmt->fetch();
        
        if ($simpleProduct) {
            echo "Producto encontrado en tabla: " . json_encode($simpleProduct) . "\n";
        } else {
            echo "Producto NO existe en la tabla products\n";
            
            // Mostrar productos disponibles
            echo "Productos disponibles:\n";
            $allQuery = "SELECT id, name FROM products ORDER BY id LIMIT 10";
            $allStmt = $db->prepare($allQuery);
            $allStmt->execute();
            $allProducts = $allStmt->fetchAll();
            foreach ($allProducts as $p) {
                echo "- ID: {$p['id']}, Nombre: {$p['name']}\n";
            }
        }
    } else {
        echo "Producto encontrado exitosamente:\n";
        echo "Nombre: " . $product['name'] . "\n";
        echo "Precio: " . $product['price'] . "\n";
        echo "Estado: " . $product['status'] . "\n";
    }
    
} catch (Exception $e) {
    echo "EXCEPCIÓN: " . $e->getMessage() . "\n";
    echo "Archivo: " . $e->getFile() . "\n";
    echo "Línea: " . $e->getLine() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
?>
