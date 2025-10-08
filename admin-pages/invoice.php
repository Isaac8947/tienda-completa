<?php
session_start();
require_once '../config/database.php';

// Verificar que se proporcionó un ID de pedido
if (!isset($_GET['order_id'])) {
    header('Location: ../admin/pedidos.php');
    exit;
}

$orderId = (int)$_GET['order_id'];

// Obtener datos del pedido directamente
$database = new Database();
$db = $database->getConnection();

$orderQuery = "SELECT * FROM orders WHERE id = ?";
$orderStmt = $db->prepare($orderQuery);
$orderStmt->execute([$orderId]);
$order = $orderStmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    header('Location: ../admin/pedidos.php?error=order_not_found');
    exit;
}

// Obtener items del pedido
$itemsQuery = "SELECT oi.*, p.name as product_name, p.main_image as product_image 
               FROM order_items oi 
               LEFT JOIN products p ON oi.product_id = p.id 
               WHERE oi.order_id = ?";
$itemsStmt = $db->prepare($itemsQuery);
$itemsStmt->execute([$orderId]);
$items = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);

// Decodificar datos JSON
$billingData = json_decode($order['billing_address'], true);
$shippingData = json_decode($order['shipping_address'], true);

// Datos de la empresa
$companyData = [
    'name' => 'Odisea Makeup',
    'address' => 'Calle 123 #45-67',
    'city' => 'Barranquilla, Colombia', 
    'phone' => '+57 300 123 4567',
    'email' => 'info@odiseamakeup.com',
    'website' => 'www.odiseamakeup.com',
    'tax_id' => '901234567-1'
];

// Calcular subtotal si no existe
if (!isset($order['subtotal'])) {
    $subtotal = 0;
    foreach ($items as $item) {
        $subtotal += $item['price'] * $item['quantity'];
    }
    $order['subtotal'] = $subtotal;
}

// Generar número de factura
$invoiceNumber = 'INV-' . date('Y') . '-' . str_pad($orderId, 4, '0', STR_PAD_LEFT);

// Verificar si se quiere descargar como PDF
$downloadPDF = isset($_GET['download']) && $_GET['download'] === 'pdf';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Factura <?php echo $invoiceNumber; ?> - Odisea Admin</title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Custom Admin CSS -->
    <link rel="stylesheet" href="../admin/assets/css/admin.css">
    
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: {
                            50: '#fdf2f8',
                            100: '#fce7f3',
                            200: '#fbcfe8',
                            300: '#f9a8d4',
                            400: '#f472b6',
                            500: '#ec4899',
                            600: '#db2777',
                            700: '#be185d',
                            800: '#9d174d',
                            900: '#831843'
                        },
                        admin: {
                            50: '#f8fafc',
                            100: '#f1f5f9',
                            200: '#e2e8f0',
                            300: '#cbd5e1',
                            400: '#94a3b8',
                            500: '#64748b',
                            600: '#475569',
                            700: '#334155',
                            800: '#1e293b',
                            900: '#0f172a'
                        }
                    }
                }
            }
        }
    </script>
    
    <!-- Print Styles -->
    <style>
        @media print {
            .no-print {
                display: none !important;
            }
            
            .print-container {
                max-width: none !important;
                margin: 0 !important;
                padding: 0 !important;
                box-shadow: none !important;
            }
            
            body {
                background: white !important;
            }
        }
    </style>
</head>
<body class="bg-gray-50">
    <?php if (!$downloadPDF): ?>
    <div class="flex h-screen overflow-hidden">
        <?php include '../admin/includes/sidebar.php'; ?>
        
        <div class="flex-1 flex flex-col overflow-hidden">
            <?php include '../admin/includes/header.php'; ?>
            
            <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-50 p-4 lg:p-6">
                <!-- Page Header -->
                <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between mb-8 no-print">
                    <div class="mb-4 lg:mb-0">
                        <h1 class="text-2xl lg:text-3xl font-bold text-gray-900">Factura <?php echo $invoiceNumber; ?></h1>
                        <p class="text-gray-600 mt-1">Pedido #<?php echo $order['id']; ?></p>
                    </div>
                    <div class="flex flex-col sm:flex-row space-y-2 sm:space-y-0 sm:space-x-3">
                        <button onclick="window.print()" class="bg-blue-500 text-white px-6 py-2 rounded-lg hover:bg-blue-600 transition-all duration-200 flex items-center justify-center">
                            <i class="fas fa-print mr-2"></i>
                            Imprimir
                        </button>
                        <a href="?order_id=<?php echo $orderId; ?>&download=pdf" class="bg-red-500 text-white px-6 py-2 rounded-lg hover:bg-red-600 transition-all duration-200 flex items-center justify-center">
                            <i class="fas fa-file-pdf mr-2"></i>
                            Descargar PDF
                        </a>
                        <a href="orders.php?view=<?php echo $orderId; ?>" class="bg-gray-500 text-white px-6 py-2 rounded-lg hover:bg-gray-600 transition-all duration-200 flex items-center justify-center">
                            <i class="fas fa-arrow-left mr-2"></i>
                            Volver al Pedido
                        </a>
                    </div>
                </div>
    <?php endif; ?>
                
                <!-- Invoice Content -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden print-container max-w-4xl mx-auto">
                    <div class="p-8">
                        <!-- Invoice Header -->
                        <div class="flex justify-between items-start mb-8">
                            <div>
                                <h2 class="text-3xl font-bold text-primary-600"><?php echo $companyData['name']; ?></h2>
                                <div class="mt-2 text-gray-600">
                                    <p><?php echo $companyData['address']; ?></p>
                                    <p><?php echo $companyData['city']; ?></p>
                                    <p>Tel: <?php echo $companyData['phone']; ?></p>
                                    <p>Email: <?php echo $companyData['email']; ?></p>
                                    <p>Web: <?php echo $companyData['website']; ?></p>
                                </div>
                            </div>
                            <div class="text-right">
                                <h3 class="text-2xl font-bold text-gray-900">FACTURA</h3>
                                <p class="text-lg font-semibold text-primary-600"><?php echo $invoiceNumber; ?></p>
                                <div class="mt-4 text-sm text-gray-600">
                                    <p><span class="font-medium">Fecha:</span> <?php echo date('d/m/Y', strtotime($order['created_at'])); ?></p>
                                    <p><span class="font-medium">Pedido:</span> #<?php echo $order['id']; ?></p>
                                    <p><span class="font-medium">Estado:</span> 
                                        <span class="px-2 py-1 text-xs rounded-full <?php echo getStatusBadgeClass($order['status']); ?>">
                                            <?php echo ucfirst($order['status']); ?>
                                        </span>
                                    </p>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Customer Information -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-8">
                            <div>
                                <h4 class="text-lg font-semibold text-gray-900 mb-3">Facturar a:</h4>
                                <div class="bg-gray-50 rounded-lg p-4">
                                    <p class="font-semibold"><?php echo htmlspecialchars(($billingData['first_name'] ?? '') . ' ' . ($billingData['last_name'] ?? '')); ?></p>
                                    <p><?php echo htmlspecialchars($billingData['email'] ?? 'N/A'); ?></p>
                                    <?php if (!empty($billingData['phone'])): ?>
                                    <p><?php echo htmlspecialchars($billingData['phone']); ?></p>
                                    <?php endif; ?>
                                    <div class="mt-2">
                                        <p><?php echo htmlspecialchars($billingData['address'] ?? 'N/A'); ?></p>
                                        <?php if (!empty($billingData['city'])): ?>
                                        <p><?php echo htmlspecialchars($billingData['city']); ?></p>
                                        <?php endif; ?>
                                        <?php if (!empty($billingData['department'])): ?>
                                        <p><?php echo htmlspecialchars($billingData['department']); ?></p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            
                            <div>
                                <h4 class="text-lg font-semibold text-gray-900 mb-3">Enviar a:</h4>
                                <div class="bg-gray-50 rounded-lg p-4">
                                    <p class="font-semibold"><?php echo htmlspecialchars(($billingData['first_name'] ?? '') . ' ' . ($billingData['last_name'] ?? '')); ?></p>
                                    <div class="mt-2">
                                        <p><?php echo htmlspecialchars($shippingData['address'] ?? ($billingData['address'] ?? 'N/A')); ?></p>
                                        <p><?php echo htmlspecialchars($shippingData['city'] ?? ($billingData['city'] ?? 'N/A')); ?></p>
                                        <p><?php echo htmlspecialchars($shippingData['department'] ?? ($billingData['department'] ?? 'N/A')); ?></p>
                                        <?php if (!empty($billingData['phone'])): ?>
                                        <p><?php echo htmlspecialchars($billingData['phone']); ?></p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Items Table -->
                        <div class="mb-8">
                            <h4 class="text-lg font-semibold text-gray-900 mb-4">Productos</h4>
                            <div class="overflow-x-auto">
                                <table class="w-full">
                                    <thead>
                                        <tr class="border-b-2 border-gray-200">
                                            <th class="text-left py-3 px-4 font-semibold text-gray-700">Producto</th>
                                            <th class="text-center py-3 px-4 font-semibold text-gray-700">Cantidad</th>
                                            <th class="text-right py-3 px-4 font-semibold text-gray-700">Precio Unit.</th>
                                            <th class="text-right py-3 px-4 font-semibold text-gray-700">Total</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($items as $item): ?>
                                        <tr class="border-b border-gray-100">
                                            <td class="py-4 px-4">
                                                <div>
                                                    <p class="font-medium text-gray-900"><?php echo htmlspecialchars($item['product_name']); ?></p>
                                                    <?php if (!empty($item['product_sku'])): ?>
                                                    <p class="text-sm text-gray-500">SKU: <?php echo $item['product_sku']; ?></p>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                            <td class="py-4 px-4 text-center"><?php echo $item['quantity']; ?></td>
                                            <td class="py-4 px-4 text-right">$<?php echo number_format($item['price'], 2); ?></td>
                                            <td class="py-4 px-4 text-right font-medium">$<?php echo number_format($item['price'] * $item['quantity'], 2); ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        
                        <!-- Totals -->
                        <div class="flex justify-end">
                            <div class="w-full max-w-sm">
                                <div class="bg-gray-50 rounded-lg p-6">
                                    <div class="space-y-3">
                                        <div class="flex justify-between text-sm">
                                            <span class="text-gray-600">Subtotal:</span>
                                            <span class="font-medium">$<?php echo number_format($order['subtotal'] ?? 0, 2); ?></span>
                                        </div>
                                        
                                        <?php if (isset($order['discount_amount']) && $order['discount_amount'] > 0): ?>
                                        <div class="flex justify-between text-sm">
                                            <span class="text-gray-600">Descuento:</span>
                                            <span class="font-medium text-red-600">-$<?php echo number_format($order['discount_amount'], 2); ?></span>
                                        </div>
                                        <?php endif; ?>
                                        
                                        <div class="flex justify-between text-sm">
                                            <span class="text-gray-600">Envío:</span>
                                            <span class="font-medium">$<?php echo number_format($order['shipping_amount'] ?? 0, 2); ?></span>
                                        </div>
                                        
                                        <?php if (isset($order['tax_amount']) && $order['tax_amount'] > 0): ?>
                                        <div class="flex justify-between text-sm">
                                            <span class="text-gray-600">Impuestos:</span>
                                            <span class="font-medium">$<?php echo number_format($order['tax_amount'], 2); ?></span>
                                        </div>
                                        <?php endif; ?>
                                        
                                        <hr class="border-gray-200">
                                        
                                        <div class="flex justify-between text-lg font-bold">
                                            <span class="text-gray-900">Total:</span>
                                            <span class="text-primary-600">$<?php echo number_format($order['total'], 2); ?></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Payment Information -->
                        <div class="mt-8 pt-8 border-t border-gray-200">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                                <div>
                                    <h4 class="text-lg font-semibold text-gray-900 mb-3">Información de Pago</h4>
                                    <div class="space-y-2 text-sm">
                                        <p><span class="font-medium text-gray-700">Método:</span> <?php echo ucfirst($order['payment_method']); ?></p>
                                        <p><span class="font-medium text-gray-700">Estado:</span> 
                                            <span class="px-2 py-1 text-xs rounded-full <?php echo $order['payment_status'] === 'paid' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'; ?>">
                                                <?php echo ucfirst($order['payment_status']); ?>
                                            </span>
                                        </p>
                                        <?php if (!empty($order['payment_reference'])): ?>
                                        <p><span class="font-medium text-gray-700">Referencia:</span> <?php echo $order['payment_reference']; ?></p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <div>
                                    <h4 class="text-lg font-semibold text-gray-900 mb-3">Información Fiscal</h4>
                                    <div class="space-y-2 text-sm">
                                        <p><span class="font-medium text-gray-700">NIT/Tax ID:</span> <?php echo $companyData['tax_id'] ?? 'N/A'; ?></p>
                                        <p><span class="font-medium text-gray-700">Moneda:</span> <?php echo strtoupper($order['currency'] ?? 'COP'); ?></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Footer -->
                        <div class="mt-8 pt-8 border-t border-gray-200 text-center text-sm text-gray-600">
                            <p>Gracias por su compra en <?php echo $companyData['name']; ?></p>
                            <p class="mt-2">Para consultas sobre esta factura, contacte: <?php echo $companyData['email']; ?></p>
                        </div>
                    </div>
                </div>
                
    <?php if (!$downloadPDF): ?>
            </main>
        </div>
    </div>
    <?php endif; ?>
    
    <script src="../admin/assets/js/admin.js"></script>
</body>
</html>

<?php
// Función para obtener la clase CSS según el estado del pedido
function getStatusBadgeClass($status) {
    switch ($status) {
        case 'pending':
            return 'bg-yellow-100 text-yellow-800';
        case 'processing':
            return 'bg-blue-100 text-blue-800';
        case 'shipped':
            return 'bg-purple-100 text-purple-800';
        case 'delivered':
            return 'bg-green-100 text-green-800';
        case 'cancelled':
            return 'bg-red-100 text-red-800';
        default:
            return 'bg-gray-100 text-gray-800';
    }
}
?>
