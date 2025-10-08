<?php
session_start();
require_once '../config/config.php';
require_once '../models/Product.php';
require_once '../models/InventoryHistory.php';
require_once '../models/Admin.php';

if (!isAdminLoggedIn()) {
    redirectTo(ADMIN_URL . '/login.php');
}

// Obtener datos del admin para el header
$adminData = [];
if (isset($_SESSION['admin_id'])) {
    $adminModel = new Admin();
    $adminData = $adminModel->findById($_SESSION['admin_id']);
    
    if (!$adminData) {
        $adminData = [
            'id' => $_SESSION['admin_id'],
            'name' => $_SESSION['admin_name'] ?? 'Administrador',
            'full_name' => $_SESSION['admin_name'] ?? 'Administrador',
            'email' => $_SESSION['admin_email'] ?? '',
            'role' => $_SESSION['admin_role'] ?? 'admin',
            'avatar' => null
        ];
    }
    
    if (!isset($adminData['full_name']) || empty($adminData['full_name'])) {
        $adminData['full_name'] = $adminData['name'] ?? 
                                  $adminData['first_name'] ?? 
                                  $_SESSION['admin_name'] ?? 
                                  'Administrador';
    }
}

$inventoryHistory = new InventoryHistory();
$product = new Product();

// Procesar acciones POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    $action = $_POST['action'] ?? '';
    
    if ($action === 'record_movement') {
        $productId = (int)$_POST['product_id'];
        $movementType = $_POST['movement_type'];
        $quantity = (int)$_POST['quantity'];
        $reason = $_POST['reason'] ?? '';
        $notes = $_POST['notes'] ?? '';
        
        try {
            $success = false;
            
            switch ($movementType) {
                case 'in':
                    $success = $inventoryHistory->recordStockIn($productId, $quantity, $reason, [
                        'notes' => $notes,
                        'reference_type' => 'restock'
                    ]);
                    break;
                    
                case 'out':
                    $success = $inventoryHistory->recordStockOut($productId, $quantity, $reason, [
                        'notes' => $notes,
                        'reference_type' => 'adjustment'
                    ]);
                    break;
                    
                case 'adjustment':
                    $success = $inventoryHistory->adjustStock($productId, $quantity, $reason, [
                        'notes' => $notes
                    ]);
                    break;
            }
            
            echo json_encode([
                'success' => $success,
                'message' => $success ? 'Movimiento registrado exitosamente' : 'Error al registrar movimiento'
            ]);
            
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ]);
        }
        exit;
    }
}

// Obtener filtros
$filters = [
    'product_id' => $_GET['product_id'] ?? '',
    'movement_type' => $_GET['movement_type'] ?? '',
    'reference_type' => $_GET['reference_type'] ?? '',
    'date_from' => $_GET['date_from'] ?? '',
    'date_to' => $_GET['date_to'] ?? date('Y-m-d')
];

// Obtener historial
$history = $inventoryHistory->getHistory($filters, 100);

// Obtener productos para el selector
$products = $product->getAll(['status' => 'active']);

// Obtener estadísticas
try {
    $stats = $inventoryHistory->getMovementStats('30 days');
    $topProducts = $inventoryHistory->getTopMovedProducts(5, '30 days');
} catch (Exception $e) {
    error_log("Error getting inventory stats: " . $e->getMessage());
    $stats = [];
    $topProducts = [];
}

// Validar que las estadísticas sean un array
if (!is_array($stats)) {
    $stats = [];
}

if (!is_array($topProducts)) {
    $topProducts = [];
}

// Función helper para calcular estadísticas
function calculateStatsByType($stats, $type, $field = 'total_quantity') {
    $total = 0;
    if (is_array($stats)) {
        foreach ($stats as $stat) {
            if (is_array($stat) && isset($stat['movement_type']) && $stat['movement_type'] === $type) {
                $total += isset($stat[$field]) ? (int)$stat[$field] : 0;
            }
        }
    }
    return $total;
}

// Make basic stats available for sidebar
$sidebarStats = [
    'pending_orders' => 0
];

try {
    $db = new Database();
    $pdo = $db->getConnection();
    
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM orders WHERE status = 'pending'");
    $stmt->execute();
    $sidebarStats['pending_orders'] = $stmt->fetch()['count'] ?? 0;
    
} catch (Exception $e) {
    error_log("Stats error in inventory.php: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Historial de Inventario - Odisea Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../admin/assets/css/admin.css">
    
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: {
                            50: '#fdf7f0',
                            100: '#fbeee1',
                            500: '#b08d80',
                            600: '#9d7a6b',
                            700: '#8a6b5e'
                        },
                        secondary: {
                            500: '#c4a575'
                        }
                    }
                }
            }
        }
    </script>
</head>

<body class="bg-gray-50">
    <div class="flex h-screen overflow-hidden">
        <?php 
        $stats = $sidebarStats; // Para que funcione el sidebar
        include '../admin/includes/sidebar.php'; 
        ?>
        
        <div class="flex-1 flex flex-col overflow-hidden">
            <?php include '../admin/includes/header.php'; ?>
            
            <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-50 p-4 lg:p-6">
                <!-- Header -->
                <div class="mb-8">
                    <div class="flex items-center justify-between">
                        <div>
                            <h1 class="text-3xl font-bold text-gray-900">Historial de Inventario</h1>
                            <p class="text-gray-600 mt-1">Gestiona y monitorea los movimientos de stock</p>
                        </div>
                        <button onclick="openMovementModal()" class="bg-primary-600 text-white px-6 py-3 rounded-xl hover:bg-primary-700 transition-colors duration-300 shadow-lg hover:shadow-xl font-medium">
                            <i class="fas fa-plus mr-2"></i>
                            Registrar Movimiento
                        </button>
                    </div>
                </div>

                <!-- Statistics Cards -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                        <div class="flex items-center">
                            <div class="p-3 bg-green-100 rounded-lg">
                                <i class="fas fa-arrow-up text-green-600 text-xl"></i>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-600">Entradas (30 días)</p>
                                <p class="text-2xl font-bold text-gray-900">
                                    <?php echo number_format(calculateStatsByType($stats, 'in')); ?>
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                        <div class="flex items-center">
                            <div class="p-3 bg-red-100 rounded-lg">
                                <i class="fas fa-arrow-down text-red-600 text-xl"></i>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-600">Salidas (30 días)</p>
                                <p class="text-2xl font-bold text-gray-900">
                                    <?php echo number_format(calculateStatsByType($stats, 'out')); ?>
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                        <div class="flex items-center">
                            <div class="p-3 bg-blue-100 rounded-lg">
                                <i class="fas fa-sync text-blue-600 text-xl"></i>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-600">Ajustes (30 días)</p>
                                <p class="text-2xl font-bold text-gray-900">
                                    <?php echo number_format(calculateStatsByType($stats, 'adjustment', 'total_movements')); ?>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filters -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-8">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Filtros</h3>
                    <form method="GET" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Producto</label>
                            <select name="product_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                                <option value="">Todos los productos</option>
                                <?php foreach ($products as $prod): ?>
                                <option value="<?php echo $prod['id']; ?>" <?php echo $filters['product_id'] == $prod['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($prod['name']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Tipo de Movimiento</label>
                            <select name="movement_type" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                                <option value="">Todos</option>
                                <option value="in" <?php echo $filters['movement_type'] === 'in' ? 'selected' : ''; ?>>Entrada</option>
                                <option value="out" <?php echo $filters['movement_type'] === 'out' ? 'selected' : ''; ?>>Salida</option>
                                <option value="adjustment" <?php echo $filters['movement_type'] === 'adjustment' ? 'selected' : ''; ?>>Ajuste</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Referencia</label>
                            <select name="reference_type" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                                <option value="">Todas</option>
                                <option value="sale" <?php echo $filters['reference_type'] === 'sale' ? 'selected' : ''; ?>>Venta</option>
                                <option value="restock" <?php echo $filters['reference_type'] === 'restock' ? 'selected' : ''; ?>>Reabastecimiento</option>
                                <option value="adjustment" <?php echo $filters['reference_type'] === 'adjustment' ? 'selected' : ''; ?>>Ajuste</option>
                                <option value="return" <?php echo $filters['reference_type'] === 'return' ? 'selected' : ''; ?>>Devolución</option>
                                <option value="damaged" <?php echo $filters['reference_type'] === 'damaged' ? 'selected' : ''; ?>>Dañado</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Fecha Desde</label>
                            <input type="date" name="date_from" value="<?php echo htmlspecialchars($filters['date_from']); ?>" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                        </div>

                        <div class="flex items-end">
                            <button type="submit" class="w-full bg-primary-600 text-white px-4 py-2 rounded-lg hover:bg-primary-700 transition-colors duration-300">
                                <i class="fas fa-search mr-2"></i>
                                Filtrar
                            </button>
                        </div>
                    </form>
                </div>

                <!-- History Table -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-900">Historial de Movimientos</h3>
                    </div>
                    
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Producto</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tipo</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Cantidad</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Stock</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Motivo</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Admin</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fecha</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php if (empty($history)): ?>
                                <tr>
                                    <td colspan="7" class="px-6 py-12 text-center text-gray-500">
                                        <i class="fas fa-history text-4xl mb-4"></i>
                                        <p>No hay movimientos de inventario registrados</p>
                                    </td>
                                </tr>
                                <?php else: ?>
                                <?php foreach ($history as $item): ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <?php if ($item['main_image']): ?>
                                            <img src="<?php echo BASE_URL . '/' . htmlspecialchars($item['main_image']); ?>" 
                                                 alt="<?php echo htmlspecialchars($item['product_name']); ?>" 
                                                 class="h-10 w-10 object-cover rounded mr-3">
                                            <?php else: ?>
                                            <div class="h-10 w-10 bg-gray-200 rounded mr-3 flex items-center justify-center">
                                                <i class="fas fa-image text-gray-400"></i>
                                            </div>
                                            <?php endif; ?>
                                            <div>
                                                <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($item['product_name']); ?></div>
                                                <div class="text-sm text-gray-500">SKU: <?php echo htmlspecialchars($item['sku']); ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <?php
                                        $typeClasses = [
                                            'in' => 'bg-green-100 text-green-800',
                                            'out' => 'bg-red-100 text-red-800',
                                            'adjustment' => 'bg-blue-100 text-blue-800'
                                        ];
                                        $typeLabels = [
                                            'in' => 'Entrada',
                                            'out' => 'Salida',
                                            'adjustment' => 'Ajuste'
                                        ];
                                        ?>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo $typeClasses[$item['movement_type']] ?? 'bg-gray-100 text-gray-800'; ?>">
                                            <?php echo $typeLabels[$item['movement_type']] ?? $item['movement_type']; ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="text-sm font-medium <?php echo $item['quantity_change'] >= 0 ? 'text-green-600' : 'text-red-600'; ?>">
                                            <?php echo $item['quantity_change'] >= 0 ? '+' : ''; ?><?php echo number_format($item['quantity_change']); ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?php echo number_format($item['quantity_before']); ?> → <?php echo number_format($item['quantity_after']); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?php echo htmlspecialchars($item['reason']); ?>
                                        <?php if ($item['notes']): ?>
                                        <div class="text-xs text-gray-500 mt-1"><?php echo htmlspecialchars($item['notes']); ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?php echo htmlspecialchars($item['admin_name'] ?? 'Sistema'); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?php echo date('d/m/Y H:i', strtotime($item['created_at'])); ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Modal para registrar movimiento -->
    <div id="movementModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
        <div class="bg-white rounded-xl shadow-xl w-full max-w-md mx-4">
            <div class="flex items-center justify-between p-6 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900">Registrar Movimiento de Inventario</h3>
                <button onclick="closeMovementModal()" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <form id="movementForm" class="p-6">
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Producto</label>
                        <select name="product_id" required class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                            <option value="">Seleccionar producto</option>
                            <?php foreach ($products as $prod): ?>
                            <option value="<?php echo $prod['id']; ?>"><?php echo htmlspecialchars($prod['name']); ?> (Stock: <?php echo $prod['inventory_quantity']; ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Tipo de Movimiento</label>
                        <select name="movement_type" required class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-primary-500 focus:border-primary-500" onchange="updateQuantityLabel(this.value)">
                            <option value="">Seleccionar tipo</option>
                            <option value="in">Entrada de Stock</option>
                            <option value="out">Salida de Stock</option>
                            <option value="adjustment">Ajuste de Stock</option>
                        </select>
                    </div>

                    <div>
                        <label id="quantityLabel" class="block text-sm font-medium text-gray-700 mb-2">Cantidad</label>
                        <input type="number" name="quantity" min="1" required class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                        <p id="quantityHelp" class="text-xs text-gray-500 mt-1"></p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Motivo</label>
                        <input type="text" name="reason" required class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-primary-500 focus:border-primary-500" placeholder="Ej: Reabastecimiento, Venta, Dañado, etc.">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Notas (opcional)</label>
                        <textarea name="notes" rows="3" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-primary-500 focus:border-primary-500" placeholder="Información adicional..."></textarea>
                    </div>
                </div>

                <div class="flex space-x-3 mt-6">
                    <button type="button" onclick="closeMovementModal()" class="flex-1 px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">
                        Cancelar
                    </button>
                    <button type="submit" class="flex-1 px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700">
                        Registrar
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openMovementModal() {
            document.getElementById('movementModal').classList.remove('hidden');
            document.getElementById('movementModal').classList.add('flex');
        }

        function closeMovementModal() {
            document.getElementById('movementModal').classList.add('hidden');
            document.getElementById('movementModal').classList.remove('flex');
            document.getElementById('movementForm').reset();
        }

        function updateQuantityLabel(movementType) {
            const label = document.getElementById('quantityLabel');
            const help = document.getElementById('quantityHelp');
            
            switch(movementType) {
                case 'in':
                    label.textContent = 'Cantidad a Ingresar';
                    help.textContent = 'Unidades que se añadirán al stock';
                    break;
                case 'out':
                    label.textContent = 'Cantidad a Retirar';
                    help.textContent = 'Unidades que se restarán del stock';
                    break;
                case 'adjustment':
                    label.textContent = 'Cantidad Final';
                    help.textContent = 'Cantidad total que debe quedar en stock';
                    break;
                default:
                    label.textContent = 'Cantidad';
                    help.textContent = '';
            }
        }

        document.getElementById('movementForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            formData.append('action', 'record_movement');
            
            try {
                const response = await fetch('', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    closeMovementModal();
                    location.reload(); // Recargar para mostrar el nuevo movimiento
                } else {
                    alert('Error: ' + result.message);
                }
            } catch (error) {
                alert('Error al registrar el movimiento');
                console.error(error);
            }
        });
    </script>
</body>
</html>
