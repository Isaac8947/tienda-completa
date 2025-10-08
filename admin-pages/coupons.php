<?php
session_start();
require_once '../admin/auth-check.php';
require_once '../models/Coupon.php';
require_once '../models/Customer.php';
require_once '../models/Product.php';
require_once '../models/Category.php';
require_once '../models/Brand.php';
require_once '../models/Admin.php';

$coupon = new Coupon();
$customer = new Customer();
$product = new Product();
$category = new Category();
$brand = new Brand();

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'create':
            try {
                $data = [
                    'code' => strtoupper(trim($_POST['code'])),
                    'name' => trim($_POST['name']),
                    'description' => trim($_POST['description']),
                    'type' => $_POST['type'],
                    'value' => floatval($_POST['value']),
                    'minimum_amount' => !empty($_POST['minimum_amount']) ? floatval($_POST['minimum_amount']) : null,
                    'maximum_discount' => !empty($_POST['maximum_discount']) ? floatval($_POST['maximum_discount']) : null,
                    'usage_limit' => !empty($_POST['usage_limit']) ? intval($_POST['usage_limit']) : null,
                    'usage_limit_per_customer' => !empty($_POST['usage_limit_per_customer']) ? intval($_POST['usage_limit_per_customer']) : null,
                    'start_date' => $_POST['start_date'],
                    'end_date' => !empty($_POST['end_date']) ? $_POST['end_date'] : null,
                    'status' => $_POST['status'],
                    'customer_ids' => !empty($_POST['customer_ids']) ? json_encode(array_map('intval', explode(',', $_POST['customer_ids']))) : null,
                    'product_ids' => !empty($_POST['product_ids']) ? json_encode(array_map('intval', explode(',', $_POST['product_ids']))) : null,
                    'category_ids' => !empty($_POST['category_ids']) ? json_encode(array_map('intval', explode(',', $_POST['category_ids']))) : null,
                    'brand_ids' => !empty($_POST['brand_ids']) ? json_encode(array_map('intval', explode(',', $_POST['brand_ids']))) : null,
                    'exclude_sale_items' => isset($_POST['exclude_sale_items']) ? 1 : 0,
                    'free_shipping' => isset($_POST['free_shipping']) ? 1 : 0,
                    'created_by_admin_id' => $_SESSION['admin_id'],
                    'used_count' => 0
                ];
                
                // Validate required fields
                if (empty($data['code']) || empty($data['name']) || empty($data['type']) || $data['value'] <= 0) {
                    throw new Exception('Todos los campos obligatorios deben completarse');
                }
                
                // Check for duplicate code
                $existing = $coupon->findByColumn('code', $data['code']);
                if ($existing) {
                    throw new Exception('El código del cupón ya existe');
                }
                
                $result = $coupon->create($data);
                
                if ($result) {
                    echo json_encode(['success' => true, 'message' => 'Cupón creado exitosamente']);
                } else {
                    throw new Exception('Error al crear el cupón');
                }
                
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            }
            exit;
            
        case 'update':
            try {
                $id = intval($_POST['id']);
                $data = [
                    'name' => trim($_POST['name']),
                    'description' => trim($_POST['description']),
                    'type' => $_POST['type'],
                    'value' => floatval($_POST['value']),
                    'minimum_amount' => !empty($_POST['minimum_amount']) ? floatval($_POST['minimum_amount']) : null,
                    'maximum_discount' => !empty($_POST['maximum_discount']) ? floatval($_POST['maximum_discount']) : null,
                    'usage_limit' => !empty($_POST['usage_limit']) ? intval($_POST['usage_limit']) : null,
                    'usage_limit_per_customer' => !empty($_POST['usage_limit_per_customer']) ? intval($_POST['usage_limit_per_customer']) : null,
                    'start_date' => $_POST['start_date'],
                    'end_date' => !empty($_POST['end_date']) ? $_POST['end_date'] : null,
                    'status' => $_POST['status'],
                    'customer_ids' => !empty($_POST['customer_ids']) ? json_encode(array_map('intval', explode(',', $_POST['customer_ids']))) : null,
                    'product_ids' => !empty($_POST['product_ids']) ? json_encode(array_map('intval', explode(',', $_POST['product_ids']))) : null,
                    'category_ids' => !empty($_POST['category_ids']) ? json_encode(array_map('intval', explode(',', $_POST['category_ids']))) : null,
                    'brand_ids' => !empty($_POST['brand_ids']) ? json_encode(array_map('intval', explode(',', $_POST['brand_ids']))) : null,
                    'exclude_sale_items' => isset($_POST['exclude_sale_items']) ? 1 : 0,
                    'free_shipping' => isset($_POST['free_shipping']) ? 1 : 0
                ];
                
                $result = $coupon->update($id, $data);
                
                if ($result) {
                    echo json_encode(['success' => true, 'message' => 'Cupón actualizado exitosamente']);
                } else {
                    throw new Exception('Error al actualizar el cupón');
                }
                
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            }
            exit;
            
        case 'delete':
            try {
                $id = intval($_POST['id']);
                $result = $coupon->delete($id);
                
                if ($result) {
                    echo json_encode(['success' => true, 'message' => 'Cupón eliminado exitosamente']);
                } else {
                    throw new Exception('Error al eliminar el cupón');
                }
                
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            }
            exit;
            
        case 'bulk_status':
            try {
                $ids = array_map('intval', explode(',', $_POST['ids']));
                $status = $_POST['bulk_status'];
                
                $result = $coupon->bulkUpdateStatus($ids, $status);
                
                if ($result) {
                    echo json_encode(['success' => true, 'message' => 'Estado actualizado exitosamente']);
                } else {
                    throw new Exception('Error al actualizar el estado');
                }
                
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            }
            exit;
            
        case 'bulk_delete':
            try {
                $ids = array_map('intval', explode(',', $_POST['ids']));
                $result = $coupon->bulkDelete($ids);
                
                if ($result) {
                    echo json_encode(['success' => true, 'message' => 'Cupones eliminados exitosamente']);
                } else {
                    throw new Exception('Error al eliminar cupones');
                }
                
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            }
            exit;
            
        case 'duplicate':
            try {
                $id = intval($_POST['id']);
                $result = $coupon->duplicateCoupon($id);
                
                if ($result) {
                    echo json_encode(['success' => true, 'message' => 'Cupón duplicado exitosamente']);
                } else {
                    throw new Exception('Error al duplicar el cupón');
                }
                
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            }
            exit;
            
        case 'validate':
            try {
                $code = strtoupper(trim($_POST['code']));
                $customerId = !empty($_POST['customer_id']) ? intval($_POST['customer_id']) : null;
                $orderAmount = floatval($_POST['order_amount']);
                
                $validation = $coupon->validateCoupon($code, $customerId, $orderAmount);
                echo json_encode($validation);
                
            } catch (Exception $e) {
                echo json_encode(['valid' => false, 'message' => $e->getMessage()]);
            }
            exit;
            
        case 'generate_code':
            $newCode = $coupon->generateUniqueCode();
            echo json_encode(['code' => $newCode]);
            exit;
    }
}

// Get filters and pagination
$page = max(1, intval($_GET['page'] ?? 1));
$limit = 20;
$offset = ($page - 1) * $limit;

$filters = [
    'status' => $_GET['status'] ?? '',
    'type' => $_GET['type'] ?? '',
    'customer_specific' => $_GET['customer_specific'] ?? '',
    'search' => $_GET['search'] ?? ''
];

// Get data
$coupons = $coupon->getAllWithDetails($limit, $offset, $filters);
$totalCoupons = $coupon->countWithFilters($filters);
$totalPages = ceil($totalCoupons / $limit);
$stats = $coupon->getStats();
$customers = $customer->getAll();
$products = $product->getProductsWithFilters([], 1000); // Get all products with a high limit
$categories = $category->getAll();
$brands = $brand->getAll();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cupones - Odisea Admin</title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../admin/assets/css/admin.css">
    
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: {
                            50: '#fdf2f8', 100: '#fce7f3', 200: '#fbcfe8', 300: '#f9a8d4',
                            400: '#f472b6', 500: '#ec4899', 600: '#db2777', 700: '#be185d',
                            800: '#9d174d', 900: '#831843'
                        },
                        admin: {
                            50: '#f8fafc', 100: '#f1f5f9', 200: '#e2e8f0', 300: '#cbd5e1',
                            400: '#94a3b8', 500: '#64748b', 600: '#475569', 700: '#334155',
                            800: '#1e293b', 900: '#0f172a'
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
        }
        
        include '../admin/includes/sidebar.php'; 
        ?>
        
        <div class="flex-1 flex flex-col overflow-hidden">
            <?php include '../admin/includes/header.php'; ?>
            
            <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-50 p-4 lg:p-6">
                <!-- Page Header -->
                <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between mb-8">
                    <div class="mb-4 lg:mb-0">
                        <h1 class="text-2xl lg:text-3xl font-bold text-gray-900">Cupones</h1>
                        <p class="text-gray-600 mt-1">Gestiona los cupones de descuento de tu tienda</p>
                    </div>
                    <div class="flex flex-col sm:flex-row space-y-2 sm:space-y-0 sm:space-x-3">
                        <button type="button" class="bg-gray-100 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-200 transition-colors duration-200 flex items-center justify-center" onclick="openCouponValidator()">
                            <i class="fas fa-check-circle mr-2"></i>
                            <span class="hidden sm:inline">Validar Cupón</span>
                        </button>
                        <button type="button" class="bg-gradient-to-r from-primary-500 to-primary-600 text-white px-6 py-2 rounded-lg hover:from-primary-600 hover:to-primary-700 transition-all duration-200 flex items-center justify-center" onclick="openAddCouponModal()">
                            <i class="fas fa-plus mr-2"></i>
                            Nuevo Cupón
                        </button>
                    </div>
                </div>

                <!-- Statistics Cards -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 lg:gap-6 mb-8">
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                                    <i class="fas fa-ticket-alt text-blue-600 text-xl"></i>
                                </div>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-600">Total Cupones</p>
                                <p class="text-2xl font-bold text-gray-900"><?php echo number_format($stats['total_coupons']); ?></p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                                    <i class="fas fa-check-circle text-green-600 text-xl"></i>
                                </div>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-600">Cupones Activos</p>
                                <p class="text-2xl font-bold text-gray-900"><?php echo number_format($stats['active_coupons']); ?></p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <div class="w-12 h-12 bg-yellow-100 rounded-lg flex items-center justify-center">
                                    <i class="fas fa-chart-line text-yellow-600 text-xl"></i>
                                </div>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-600">Usos Totales</p>
                                <p class="text-2xl font-bold text-gray-900"><?php echo number_format($stats['total_usage']); ?></p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center">
                                    <i class="fas fa-dollar-sign text-purple-600 text-xl"></i>
                                </div>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-600">Descuento Total</p>
                                <p class="text-2xl font-bold text-gray-900">$<?php echo number_format($stats['total_discount_given'] ?? 0, 2); ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filters -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 lg:p-6 mb-6">
                    <form method="GET" class="space-y-4">
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
                            <!-- Search -->
                            <div class="lg:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Buscar</label>
                                <div class="relative">
                                    <input type="text" name="search" value="<?php echo htmlspecialchars($filters['search']); ?>" 
                                           placeholder="Código, nombre, descripción..." 
                                           class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <i class="fas fa-search text-gray-400"></i>
                                    </div>
                                </div>
                            </div>

                            <!-- Status -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Estado</label>
                                <select name="status" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                                    <option value="">Todos los estados</option>
                                    <option value="active" <?php echo $filters['status'] === 'active' ? 'selected' : ''; ?>>Activo</option>
                                    <option value="inactive" <?php echo $filters['status'] === 'inactive' ? 'selected' : ''; ?>>Inactivo</option>
                                    <option value="expired" <?php echo $filters['status'] === 'expired' ? 'selected' : ''; ?>>Expirado</option>
                                    <option value="scheduled" <?php echo $filters['status'] === 'scheduled' ? 'selected' : ''; ?>>Programado</option>
                                </select>
                            </div>

                            <!-- Type -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Tipo</label>
                                <select name="type" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                                    <option value="">Todos los tipos</option>
                                    <option value="percentage" <?php echo $filters['type'] === 'percentage' ? 'selected' : ''; ?>>Porcentaje</option>
                                    <option value="fixed" <?php echo $filters['type'] === 'fixed' ? 'selected' : ''; ?>>Monto Fijo</option>
                                </select>
                            </div>

                            <!-- Customer Specific -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Cliente Específico</label>
                                <select name="customer_specific" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                                    <option value="">Todos</option>
                                    <option value="1" <?php echo $filters['customer_specific'] === '1' ? 'selected' : ''; ?>>Solo específicos</option>
                                    <option value="0" <?php echo $filters['customer_specific'] === '0' ? 'selected' : ''; ?>>Generales</option>
                                </select>
                            </div>
                        </div>

                        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center space-y-4 sm:space-y-0">
                            <button type="submit" class="bg-gradient-to-r from-primary-500 to-primary-600 text-white px-6 py-2 rounded-lg hover:from-primary-600 hover:to-primary-700 transition-all duration-200 flex items-center">
                                <i class="fas fa-search mr-2"></i>
                                Filtrar
                            </button>
                            
                            <!-- Bulk Actions -->
                            <div class="bulk-actions" style="display: none;">
                                <div class="flex flex-wrap gap-2">
                                    <span class="text-sm text-gray-600 mr-2">Acciones en lote:</span>
                                    <button type="button" class="px-3 py-1 bg-green-100 text-green-800 rounded-lg hover:bg-green-200 transition-colors text-sm" onclick="bulkAction('active')">
                                        <i class="fas fa-check mr-1"></i>Activar
                                    </button>
                                    <button type="button" class="px-3 py-1 bg-yellow-100 text-yellow-800 rounded-lg hover:bg-yellow-200 transition-colors text-sm" onclick="bulkAction('inactive')">
                                        <i class="fas fa-pause mr-1"></i>Desactivar
                                    </button>
                                    <button type="button" class="px-3 py-1 bg-red-100 text-red-800 rounded-lg hover:bg-red-200 transition-colors text-sm" onclick="bulkDelete()">
                                        <i class="fas fa-trash mr-1"></i>Eliminar
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Coupons Table -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        <input type="checkbox" id="selectAll" class="rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Código</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nombre</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tipo</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Valor</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Límite de Uso</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Usado</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Vigencia</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Estado</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($coupons as $c): ?>
                                <tr class="hover:bg-gray-50 transition-colors duration-200">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <input type="checkbox" class="coupon-checkbox rounded border-gray-300 text-primary-600 focus:ring-primary-500" value="<?php echo $c['id']; ?>">
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex flex-col">
                                            <span class="font-semibold text-primary-600"><?php echo htmlspecialchars($c['code']); ?></span>
                                            <div class="flex space-x-1 mt-1">
                                                <?php if ($c['customer_ids']): ?>
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800">Específico</span>
                                                <?php endif; ?>
                                                <?php if ($c['free_shipping']): ?>
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">Envío Gratis</span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-sm">
                                            <div class="font-medium text-gray-900"><?php echo htmlspecialchars($c['name']); ?></div>
                                            <?php if ($c['description']): ?>
                                                <div class="text-gray-500 mt-1"><?php echo htmlspecialchars(substr($c['description'], 0, 50)) . (strlen($c['description']) > 50 ? '...' : ''); ?></div>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <?php if ($c['type'] === 'percentage'): ?>
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-primary-100 text-primary-800">Porcentaje</span>
                                        <?php else: ?>
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">Monto Fijo</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                                        <div class="text-gray-900">
                                            <?php if ($c['type'] === 'percentage'): ?>
                                                <span class="font-semibold"><?php echo $c['value']; ?>%</span>
                                                <?php if ($c['maximum_discount']): ?>
                                                    <div class="text-xs text-gray-500">Máx: $<?php echo number_format($c['maximum_discount'], 2); ?></div>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <span class="font-semibold">$<?php echo number_format($c['value'], 2); ?></span>
                                            <?php endif; ?>
                                            <?php if ($c['minimum_amount']): ?>
                                                <div class="text-xs text-gray-500">Mín: $<?php echo number_format($c['minimum_amount'], 2); ?></div>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                                        <?php if ($c['usage_limit']): ?>
                                            <div class="text-gray-900"><?php echo number_format($c['usage_limit']); ?></div>
                                            <?php if ($c['usage_limit_per_customer']): ?>
                                                <div class="text-xs text-gray-500"><?php echo $c['usage_limit_per_customer']; ?>/cliente</div>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span class="text-green-600 font-medium">Ilimitado</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                                        <div class="text-gray-900 font-semibold"><?php echo number_format($c['used_count']); ?></div>
                                        <?php if ($c['usage_limit']): ?>
                                            <div class="text-xs text-gray-500"><?php echo round(($c['used_count'] / $c['usage_limit']) * 100, 1); ?>%</div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                                        <div class="text-gray-900">
                                            <div><strong>Inicio:</strong> <?php echo date('d/m/Y', strtotime($c['start_date'])); ?></div>
                                            <?php if ($c['end_date']): ?>
                                                <div><strong>Fin:</strong> <?php echo date('d/m/Y', strtotime($c['end_date'])); ?></div>
                                            <?php else: ?>
                                                <div class="text-green-600">Sin expiración</div>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <?php
                                        $statusClasses = [
                                            'active' => 'bg-green-100 text-green-800',
                                            'inactive' => 'bg-gray-100 text-gray-800',
                                            'expired' => 'bg-red-100 text-red-800',
                                            'scheduled' => 'bg-yellow-100 text-yellow-800'
                                        ];
                                        $statusText = [
                                            'active' => 'Activo',
                                            'inactive' => 'Inactivo',
                                            'expired' => 'Expirado',
                                            'scheduled' => 'Programado'
                                        ];
                                        ?>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo $statusClasses[$c['current_status']]; ?>">
                                            <?php echo $statusText[$c['current_status']]; ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <div class="flex space-x-2">
                                            <button type="button" class="text-primary-600 hover:text-primary-900 transition-colors" onclick="editCoupon(<?php echo $c['id']; ?>)" title="Editar">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button type="button" class="text-blue-600 hover:text-blue-900 transition-colors" onclick="viewUsage(<?php echo $c['id']; ?>)" title="Ver uso">
                                                <i class="fas fa-chart-bar"></i>
                                            </button>
                                            <button type="button" class="text-gray-600 hover:text-gray-900 transition-colors" onclick="duplicateCoupon(<?php echo $c['id']; ?>)" title="Duplicar">
                                                <i class="fas fa-copy"></i>
                                            </button>
                                            <button type="button" class="text-red-600 hover:text-red-900 transition-colors" onclick="deleteCoupon(<?php echo $c['id']; ?>)" title="Eliminar">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <?php if ($totalPages > 1): ?>
                    <div class="bg-white px-4 py-3 flex items-center justify-between border-t border-gray-200 sm:px-6">
                        <div class="flex-1 flex justify-between sm:hidden">
                            <a href="?page=<?php echo max(1, $page - 1); ?>&<?php echo http_build_query(array_filter($filters)); ?>" 
                               class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                                Anterior
                            </a>
                            <a href="?page=<?php echo min($totalPages, $page + 1); ?>&<?php echo http_build_query(array_filter($filters)); ?>" 
                               class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                                Siguiente
                            </a>
                        </div>
                        <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                            <div>
                                <p class="text-sm text-gray-700">
                                    Mostrando <span class="font-medium"><?php echo (($page - 1) * $limit) + 1; ?></span> a 
                                    <span class="font-medium"><?php echo min($page * $limit, $totalCoupons); ?></span> de 
                                    <span class="font-medium"><?php echo $totalCoupons; ?></span> resultados
                                </p>
                            </div>
                            <div>
                                <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px">
                                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                        <a href="?page=<?php echo $i; ?>&<?php echo http_build_query(array_filter($filters)); ?>" 
                                           class="<?php echo $i === $page ? 'bg-primary-50 border-primary-500 text-primary-600 z-10' : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50'; ?> relative inline-flex items-center px-4 py-2 border text-sm font-medium <?php echo $i === 1 ? 'rounded-l-md' : ($i === $totalPages ? 'rounded-r-md' : ''); ?>">
                                            <?php echo $i; ?>
                                        </a>
                                    <?php endfor; ?>
                                </nav>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </main>
        </div>
    </div>

<!-- Add/Edit Coupon Modal -->
<div class="modal fade" id="addCouponModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Nuevo Cupón</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="couponForm">
                <div class="modal-body">
                    <div class="row">
                        <!-- Basic Information -->
                        <div class="col-md-6">
                            <h6 class="mb-3">Información Básica</h6>
                            
                            <div class="mb-3">
                                <label class="form-label">Código del Cupón *</label>
                                <div class="input-group">
                                    <input type="text" class="form-control" name="code" id="couponCode" required>
                                    <button type="button" class="btn btn-outline-secondary" onclick="generateCode()">
                                        <i class="fas fa-magic"></i> Generar
                                    </button>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Nombre del Cupón *</label>
                                <input type="text" class="form-control" name="name" required>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Descripción</label>
                                <textarea class="form-control" name="description" rows="3"></textarea>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <label class="form-label">Tipo de Descuento *</label>
                                    <select class="form-select" name="type" id="discountType" required>
                                        <option value="percentage">Porcentaje</option>
                                        <option value="fixed">Monto Fijo</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Valor *</label>
                                    <div class="input-group">
                                        <span class="input-group-text" id="valuePrefix">%</span>
                                        <input type="number" class="form-control" name="value" step="0.01" min="0" required>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Restrictions and Limits -->
                        <div class="col-md-6">
                            <h6 class="mb-3">Restricciones y Límites</h6>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <label class="form-label">Monto Mínimo</label>
                                    <input type="number" class="form-control" name="minimum_amount" step="0.01" min="0">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Descuento Máximo</label>
                                    <input type="number" class="form-control" name="maximum_discount" step="0.01" min="0">
                                </div>
                            </div>
                            
                            <div class="row mt-3">
                                <div class="col-md-6">
                                    <label class="form-label">Límite de Uso Total</label>
                                    <input type="number" class="form-control" name="usage_limit" min="1">
                                    <small class="text-muted">Dejar vacío para ilimitado</small>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Límite por Cliente</label>
                                    <input type="number" class="form-control" name="usage_limit_per_customer" min="1">
                                </div>
                            </div>
                            
                            <div class="row mt-3">
                                <div class="col-md-6">
                                    <label class="form-label">Fecha de Inicio *</label>
                                    <input type="datetime-local" class="form-control" name="start_date" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Fecha de Fin</label>
                                    <input type="datetime-local" class="form-control" name="end_date">
                                    <small class="text-muted">Dejar vacío para sin expiración</small>
                                </div>
                            </div>
                            
                            <div class="mt-3">
                                <label class="form-label">Estado</label>
                                <select class="form-select" name="status">
                                    <option value="active">Activo</option>
                                    <option value="inactive">Inactivo</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Advanced Options -->
                    <div class="row mt-4">
                        <div class="col-12">
                            <h6 class="mb-3">Opciones Avanzadas</h6>
                            
                            <div class="row">
                                <div class="col-md-3">
                                    <label class="form-label">Clientes Específicos</label>
                                    <select class="form-select" name="customer_ids" multiple data-placeholder="Seleccionar clientes">
                                        <?php foreach ($customers as $cust): ?>
                                        <option value="<?php echo $cust['id']; ?>">
                                            <?php echo htmlspecialchars($cust['first_name'] . ' ' . $cust['last_name'] . ' (' . $cust['email'] . ')'); ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <small class="text-muted">Dejar vacío para todos los clientes</small>
                                </div>
                                
                                <div class="col-md-3">
                                    <label class="form-label">Productos Específicos</label>
                                    <select class="form-select" name="product_ids" multiple data-placeholder="Seleccionar productos">
                                        <?php foreach ($products as $prod): ?>
                                        <option value="<?php echo $prod['id']; ?>">
                                            <?php echo htmlspecialchars($prod['name']); ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="col-md-3">
                                    <label class="form-label">Categorías</label>
                                    <select class="form-select" name="category_ids" multiple data-placeholder="Seleccionar categorías">
                                        <?php foreach ($categories as $cat): ?>
                                        <option value="<?php echo $cat['id']; ?>">
                                            <?php echo htmlspecialchars($cat['name']); ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="col-md-3">
                                    <label class="form-label">Marcas</label>
                                    <select class="form-select" name="brand_ids" multiple data-placeholder="Seleccionar marcas">
                                        <?php foreach ($brands as $brand_item): ?>
                                        <option value="<?php echo $brand_item['id']; ?>">
                                            <?php echo htmlspecialchars($brand_item['name']); ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="row mt-3">
                                <div class="col-md-6">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="exclude_sale_items" id="excludeSale">
                                        <label class="form-check-label" for="excludeSale">
                                            Excluir productos en oferta
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="free_shipping" id="freeShipping">
                                        <label class="form-check-label" for="freeShipping">
                                            Incluye envío gratis
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar Cupón</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Coupon Validator Modal -->
<div class="modal fade" id="validatorModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Validar Cupón</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="validatorForm">
                    <div class="mb-3">
                        <label class="form-label">Código del Cupón</label>
                        <input type="text" class="form-control" name="code" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Cliente (Opcional)</label>
                        <select class="form-select" name="customer_id">
                            <option value="">Sin cliente específico</option>
                            <?php foreach ($customers as $cust): ?>
                            <option value="<?php echo $cust['id']; ?>">
                                <?php echo htmlspecialchars($cust['first_name'] . ' ' . $cust['last_name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Monto del Pedido</label>
                        <input type="number" class="form-control" name="order_amount" step="0.01" min="0" value="100">
                    </div>
                    <button type="submit" class="btn btn-primary">Validar</button>
                </form>
                <div id="validationResult" class="mt-3"></div>
            </div>
        </div>
    </div>
</div>

<!-- Usage Modal -->
<div class="modal fade" id="usageModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Historial de Uso del Cupón</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="usageContent">
                    <!-- Content will be loaded via AJAX -->
                </div>
            </div>
        </div>
    </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="../admin/assets/js/coupon-manager.js"></script>
<script>
// Función para abrir el modal de agregar cupón
function openAddCouponModal() {
    const modal = new bootstrap.Modal(document.getElementById('addCouponModal'));
    modal.show();
}
</script>

</body>
</html>
