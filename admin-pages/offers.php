<?php
session_start();
require_once '../config/database.php';
require_once '../models/Offer.php';
require_once '../models/Product.php';
require_once '../models/Category.php';
require_once '../models/Brand.php';

// Verificar autenticación del admin
if (!isset($_SESSION['admin_id'])) {
    header('Location: ../admin/index.php');
    exit();
}

$offerModel = new Offer();
$productModel = new Product();
$categoryModel = new Category();
$brandModel = new Brand();

// Crear tabla si no existe
$offerModel->createTable();

// Procesar acciones
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    try {
        switch ($_POST['action']) {
            case 'create':
                $data = [
                    'title' => trim($_POST['title']),
                    'description' => trim($_POST['description']),
                    'discount_percentage' => floatval($_POST['discount_percentage']),
                    'start_date' => $_POST['start_date'],
                    'end_date' => $_POST['end_date'],
                    'is_active' => isset($_POST['is_active']) ? 1 : 0,
                    'max_uses' => !empty($_POST['max_uses']) ? intval($_POST['max_uses']) : null,
                    'min_purchase_amount' => floatval($_POST['min_purchase_amount']),
                    'banner_text' => trim($_POST['banner_text']),
                    'banner_color' => $_POST['banner_color'],
                    'priority' => intval($_POST['priority']),
                    'applicable_products' => !empty($_POST['applicable_products']) ? json_encode($_POST['applicable_products']) : null,
                    'applicable_categories' => !empty($_POST['applicable_categories']) ? json_encode($_POST['applicable_categories']) : null,
                    'applicable_brands' => !empty($_POST['applicable_brands']) ? json_encode($_POST['applicable_brands']) : null
                ];
                
                $offerModel->create($data);
                $message = 'Oferta creada exitosamente';
                $messageType = 'success';
                break;
                
            case 'update':
                $id = intval($_POST['id']);
                $data = [
                    'title' => trim($_POST['title']),
                    'description' => trim($_POST['description']),
                    'discount_percentage' => floatval($_POST['discount_percentage']),
                    'start_date' => $_POST['start_date'],
                    'end_date' => $_POST['end_date'],
                    'is_active' => isset($_POST['is_active']) ? 1 : 0,
                    'max_uses' => !empty($_POST['max_uses']) ? intval($_POST['max_uses']) : null,
                    'min_purchase_amount' => floatval($_POST['min_purchase_amount']),
                    'banner_text' => trim($_POST['banner_text']),
                    'banner_color' => $_POST['banner_color'],
                    'priority' => intval($_POST['priority']),
                    'applicable_products' => !empty($_POST['applicable_products']) ? json_encode($_POST['applicable_products']) : null,
                    'applicable_categories' => !empty($_POST['applicable_categories']) ? json_encode($_POST['applicable_categories']) : null,
                    'applicable_brands' => !empty($_POST['applicable_brands']) ? json_encode($_POST['applicable_brands']) : null
                ];
                
                $offerModel->update($id, $data);
                $message = 'Oferta actualizada exitosamente';
                $messageType = 'success';
                break;
                
            case 'delete':
                $id = intval($_POST['id']);
                $offerModel->delete($id);
                $message = 'Oferta eliminada exitosamente';
                $messageType = 'success';
                break;
                
            case 'toggle_status':
                $id = intval($_POST['id']);
                $offer = $offerModel->findById($id);
                if ($offer) {
                    $offerModel->update($id, ['is_active' => $offer['is_active'] ? 0 : 1]);
                    $message = 'Estado de la oferta actualizado';
                    $messageType = 'success';
                }
                break;
                
            case 'create_sample':
                $offerModel->createSampleOffer();
                $message = 'Oferta de muestra creada exitosamente';
                $messageType = 'success';
                break;
        }
    } catch (Exception $e) {
        $message = 'Error: ' . $e->getMessage();
        $messageType = 'error';
    }
}

// Desactivar ofertas expiradas
$offerModel->deactivateExpiredOffers();

// Obtener datos
$offers = $offerModel->findAll([], 'priority DESC, created_at DESC');
$stats = $offerModel->getStats();
$products = $productModel->findAll(['status' => 'active'], 'name ASC');
$categories = $categoryModel->findAll([], 'name ASC');
$brands = $brandModel->findAll([], 'name ASC');
$activeOffer = $offerModel->getMainOffer();
$timeLeft = $offerModel->getMainOfferTimeLeft();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Ofertas - OdiseaStore Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: {
                            50: '#fdf8f6',
                            100: '#f2e8e5',
                            200: '#eaddd7',
                            300: '#e0cec7',
                            400: '#d2bab0',
                            500: '#b08d80',
                            600: '#a67c76',
                            700: '#8d635d',
                            800: '#745044',
                            900: '#5b3d2b'
                        },
                        secondary: {
                            50: '#faf9f7',
                            100: '#f1efed',
                            200: '#e8e3df',
                            300: '#d4ccc4',
                            400: '#b8a99c',
                            500: '#a67c76',
                            600: '#8d635d',
                            700: '#745044',
                            800: '#5b3d2b',
                            900: '#422a12'
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
</head>
<body class="bg-gray-50">
    <div class="flex h-screen overflow-hidden">
        <?php include '../admin/includes/sidebar.php'; ?>
        
        <div class="flex-1 flex flex-col overflow-hidden">
            <?php include '../admin/includes/header.php'; ?>
            
            <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-50 p-4 lg:p-6">
                <!-- Page Header -->
                <div class="mb-8">
                    <div class="flex items-center justify-between">
                        <div>
                            <h1 class="text-3xl font-bold text-gray-900">Gestión de Ofertas</h1>
                            <p class="text-gray-600 mt-1">Administra las ofertas y descuentos de tu tienda</p>
                        </div>
                        <div class="flex items-center space-x-4">
                            <a href="../ofertas.php" target="_blank" class="text-sm text-gray-600 hover:text-primary-600">
                                <i class="fas fa-external-link-alt mr-1"></i>
                                Ver Página de Ofertas
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Mensajes -->
        <?php if ($message): ?>
        <div class="mb-6 p-4 rounded-lg <?php echo $messageType === 'success' ? 'bg-green-50 text-green-800 border border-green-200' : 'bg-red-50 text-red-800 border border-red-200'; ?>">
            <i class="fas <?php echo $messageType === 'success' ? 'fa-check-circle' : 'fa-exclamation-triangle'; ?> mr-2"></i>
            <?php echo htmlspecialchars($message); ?>
        </div>
        <?php endif; ?>

        <!-- Estadísticas y Estado Actual -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <!-- Stats Cards -->
            <div class="bg-white p-6 rounded-lg shadow">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-blue-100 text-blue-600">
                        <i class="fas fa-percentage text-xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Total Ofertas</p>
                        <p class="text-2xl font-bold text-gray-900"><?php echo $stats['total']; ?></p>
                    </div>
                </div>
            </div>

            <div class="bg-white p-6 rounded-lg shadow">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-green-100 text-green-600">
                        <i class="fas fa-check-circle text-xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Ofertas Activas</p>
                        <p class="text-2xl font-bold text-gray-900"><?php echo $stats['active']; ?></p>
                    </div>
                </div>
            </div>

            <div class="bg-white p-6 rounded-lg shadow">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-red-100 text-red-600">
                        <i class="fas fa-clock text-xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Ofertas Expiradas</p>
                        <p class="text-2xl font-bold text-gray-900"><?php echo $stats['expired']; ?></p>
                    </div>
                </div>
            </div>

            <div class="bg-white p-6 rounded-lg shadow">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-purple-100 text-purple-600">
                        <i class="fas fa-chart-line text-xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Usos Totales</p>
                        <p class="text-2xl font-bold text-gray-900"><?php echo $stats['total_uses']; ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Oferta Actual y Contador -->
        <?php if ($activeOffer): ?>
        <div class="bg-gradient-to-r from-red-500 to-orange-500 text-white p-6 rounded-lg shadow-lg mb-8">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between">
                <div class="mb-4 md:mb-0">
                    <h3 class="text-2xl font-bold"><?php echo htmlspecialchars($activeOffer['title']); ?></h3>
                    <p class="text-red-100"><?php echo htmlspecialchars($activeOffer['banner_text']); ?></p>
                    <p class="text-sm text-red-100 mt-2">
                        Descuento: <?php echo $activeOffer['discount_percentage']; ?>% | 
                        Usos: <?php echo $activeOffer['current_uses']; ?><?php echo $activeOffer['max_uses'] ? '/' . $activeOffer['max_uses'] : ''; ?>
                    </p>
                </div>
                <?php if ($timeLeft): ?>
                <div class="text-center">
                    <p class="text-sm text-red-100 mb-2">Tiempo restante:</p>
                    <div class="grid grid-cols-4 gap-2 text-center">
                        <div>
                            <div class="bg-white/20 rounded px-3 py-2">
                                <div class="text-xl font-bold"><?php echo $timeLeft['days']; ?></div>
                                <div class="text-xs">Días</div>
                            </div>
                        </div>
                        <div>
                            <div class="bg-white/20 rounded px-3 py-2">
                                <div class="text-xl font-bold"><?php echo $timeLeft['hours']; ?></div>
                                <div class="text-xs">Horas</div>
                            </div>
                        </div>
                        <div>
                            <div class="bg-white/20 rounded px-3 py-2">
                                <div class="text-xl font-bold"><?php echo $timeLeft['minutes']; ?></div>
                                <div class="text-xs">Min</div>
                            </div>
                        </div>
                        <div>
                            <div class="bg-white/20 rounded px-3 py-2">
                                <div class="text-xl font-bold"><?php echo $timeLeft['seconds']; ?></div>
                                <div class="text-xs">Seg</div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php else: ?>
        <div class="bg-gray-100 border-2 border-dashed border-gray-300 p-6 rounded-lg text-center mb-8">
            <i class="fas fa-percentage text-4xl text-gray-400 mb-4"></i>
            <h3 class="text-lg font-medium text-gray-600 mb-2">No hay ofertas activas</h3>
            <p class="text-gray-500 mb-4">Crea una nueva oferta para que aparezca en el contador del sitio web</p>
            <form method="POST" class="inline">
                <input type="hidden" name="action" value="create_sample">
                <button type="submit" class="bg-primary-600 text-white px-4 py-2 rounded-lg hover:bg-primary-700">
                    <i class="fas fa-plus mr-2"></i>
                    Crear Oferta de Muestra
                </button>
            </form>
        </div>
        <?php endif; ?>

        <!-- Acciones -->
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-2xl font-bold text-gray-900">Gestión de Ofertas</h2>
            <button onclick="openCreateModal()" class="bg-primary-600 text-white px-4 py-2 rounded-lg hover:bg-primary-700">
                <i class="fas fa-plus mr-2"></i>
                Nueva Oferta
            </button>
        </div>

        <!-- Lista de Ofertas -->
        <div class="bg-white shadow overflow-hidden rounded-lg">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Oferta</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Descuento</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Período</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Estado</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Usos</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if (empty($offers)): ?>
                    <tr>
                        <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                            <i class="fas fa-percentage text-4xl mb-4"></i>
                            <p>No hay ofertas creadas</p>
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($offers as $offer): ?>
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div>
                                <div class="text-sm font-medium text-gray-900">
                                    <?php echo htmlspecialchars($offer['title']); ?>
                                </div>
                                <div class="text-sm text-gray-500">
                                    <?php echo htmlspecialchars(substr($offer['description'], 0, 50)) . (strlen($offer['description']) > 50 ? '...' : ''); ?>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                <?php echo $offer['discount_percentage']; ?>% OFF
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            <div><?php echo date('d/m/Y H:i', strtotime($offer['start_date'])); ?></div>
                            <div class="text-gray-500"><?php echo date('d/m/Y H:i', strtotime($offer['end_date'])); ?></div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <?php
                            $now = new DateTime();
                            $endDate = new DateTime($offer['end_date']);
                            $isExpired = $endDate <= $now;
                            $isActive = $offer['is_active'] && !$isExpired;
                            ?>
                            <?php if ($isExpired): ?>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                    Expirada
                                </span>
                            <?php elseif ($isActive): ?>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                    Activa
                                </span>
                            <?php else: ?>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                    Inactiva
                                </span>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            <?php echo $offer['current_uses']; ?><?php echo $offer['max_uses'] ? '/' . $offer['max_uses'] : ''; ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <div class="flex space-x-2">
                                <button onclick="editOffer(<?php echo htmlspecialchars(json_encode($offer)); ?>)" 
                                        class="text-indigo-600 hover:text-indigo-900">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <?php if (!$isExpired): ?>
                                <form method="POST" class="inline">
                                    <input type="hidden" name="action" value="toggle_status">
                                    <input type="hidden" name="id" value="<?php echo $offer['id']; ?>">
                                    <button type="submit" class="text-yellow-600 hover:text-yellow-900" 
                                            title="<?php echo $offer['is_active'] ? 'Desactivar' : 'Activar'; ?>">
                                        <i class="fas fa-<?php echo $offer['is_active'] ? 'pause' : 'play'; ?>"></i>
                                    </button>
                                </form>
                                <?php endif; ?>
                                <form method="POST" class="inline" onsubmit="return confirm('¿Estás seguro de eliminar esta oferta?')">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?php echo $offer['id']; ?>">
                                    <button type="submit" class="text-red-600 hover:text-red-900">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal para Crear/Editar Oferta -->
    <div id="offerModal" class="fixed inset-0 z-50 overflow-y-auto hidden">
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 transition-opacity bg-gray-500 bg-opacity-75" id="offerModalOverlay"></div>
            
            <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full">
                <form method="POST" id="offerForm">
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-medium text-gray-900" id="modalTitle">Nueva Oferta</h3>
                            <button type="button" onclick="closeModal()" class="text-gray-400 hover:text-gray-600 transition-colors">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    
                        
                        <div class="space-y-4">
                            <input type="hidden" name="action" id="formAction" value="create">
                            <input type="hidden" name="id" id="offerId">
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Título *</label>
                                    <input type="text" name="title" id="title" required
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Descuento (%) *</label>
                                    <input type="number" name="discount_percentage" id="discount_percentage" min="0" max="100" step="0.01" required
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                                </div>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Descripción</label>
                                <textarea name="description" id="description" rows="2"
                                          class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent resize-none"></textarea>
                            </div>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Fecha de Inicio *</label>
                                    <input type="datetime-local" name="start_date" id="start_date" required
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Fecha de Fin *</label>
                                    <input type="datetime-local" name="end_date" id="end_date" required
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                                </div>
                            </div>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Texto del Banner</label>
                                    <input type="text" name="banner_text" id="banner_text"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Color del Banner</label>
                                    <input type="color" name="banner_color" id="banner_color" value="#ef4444"
                                           class="w-full h-10 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary-500">
                                </div>
                            </div>
                            
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Límite de Usos</label>
                                    <input type="number" name="max_uses" id="max_uses" min="1" placeholder="Sin límite"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Compra Mínima ($)</label>
                                    <input type="number" name="min_purchase_amount" id="min_purchase_amount" min="0" step="0.01" placeholder="0.00"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Prioridad</label>
                                    <input type="number" name="priority" id="priority" value="1" min="1"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                                </div>
                            </div>
                            
                            <div class="flex items-center">
                                <input type="checkbox" name="is_active" id="is_active" value="1" checked
                                       class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded">
                                <label for="is_active" class="ml-2 block text-sm text-gray-900">Activar inmediatamente</label>
                            </div>
                        </div>
                        
                        <div class="flex justify-end space-x-3 pt-4 border-t border-gray-200">
                            <button type="button" onclick="closeModal()" 
                                    class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                                Cancelar
                            </button>
                            <button type="submit" 
                                    class="px-4 py-2 text-sm font-medium text-white bg-primary-600 border border-transparent rounded-md hover:bg-primary-700">
                                Guardar
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function openCreateModal() {
            document.getElementById('modalTitle').textContent = 'Nueva Oferta';
            document.getElementById('formAction').value = 'create';
            document.getElementById('offerForm').reset();
            document.getElementById('offerId').value = '';
            document.getElementById('is_active').checked = true;
            
            // Set default dates
            const now = new Date();
            const endDate = new Date(now.getTime() + 7 * 24 * 60 * 60 * 1000); // 7 days from now
            
            document.getElementById('start_date').value = now.toISOString().slice(0, 16);
            document.getElementById('end_date').value = endDate.toISOString().slice(0, 16);
            
            document.getElementById('offerModal').classList.remove('hidden');
        }
        
        function editOffer(offer) {
            document.getElementById('modalTitle').textContent = 'Editar Oferta';
            document.getElementById('formAction').value = 'update';
            document.getElementById('offerId').value = offer.id;
            
            document.getElementById('title').value = offer.title;
            document.getElementById('description').value = offer.description || '';
            document.getElementById('discount_percentage').value = offer.discount_percentage;
            document.getElementById('start_date').value = offer.start_date.replace(' ', 'T');
            document.getElementById('end_date').value = offer.end_date.replace(' ', 'T');
            document.getElementById('banner_text').value = offer.banner_text || '';
            document.getElementById('banner_color').value = offer.banner_color || '#ef4444';
            document.getElementById('max_uses').value = offer.max_uses || '';
            document.getElementById('min_purchase_amount').value = offer.min_purchase_amount || 0;
            document.getElementById('priority').value = offer.priority || 1;
            document.getElementById('is_active').checked = offer.is_active == 1;
            
            document.getElementById('offerModal').classList.remove('hidden');
        }
        
        function closeModal() {
            const modal = document.getElementById('offerModal');
            modal.classList.add('hidden');
        }
        
        // Close modal when clicking outside or pressing ESC
        document.getElementById('offerModal').addEventListener('click', function(e) {
            if (e.target === this || e.target.id === 'offerModalOverlay') {
                closeModal();
            }
        });
        
        // Close modal with ESC key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                const modal = document.getElementById('offerModal');
                if (!modal.classList.contains('hidden')) {
                    closeModal();
                }
            }
        });
        
        // Form validation
        document.getElementById('offerForm').addEventListener('submit', function(e) {
            const startDate = new Date(document.getElementById('start_date').value);
            const endDate = new Date(document.getElementById('end_date').value);
            
            if (endDate <= startDate) {
                e.preventDefault();
                alert('La fecha de fin debe ser posterior a la fecha de inicio.');
                return false;
            }
            
            const discount = parseFloat(document.getElementById('discount_percentage').value);
            if (discount <= 0 || discount > 100) {
                e.preventDefault();
                alert('El porcentaje de descuento debe estar entre 0.01 y 100.');
                return false;
            }
        });
        
        // Auto-refresh countdown every second if there's an active offer
        <?php if ($activeOffer && $timeLeft): ?>
        function updateCountdown() {
            const endDate = new Date('<?php echo $activeOffer['end_date']; ?>').getTime();
            const now = new Date().getTime();
            const distance = endDate - now;
            
            if (distance < 0) {
                location.reload();
                return;
            }
            
            const days = Math.floor(distance / (1000 * 60 * 60 * 24));
            const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
            const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
            const seconds = Math.floor((distance % (1000 * 60)) / 1000);
            
            // Update DOM elements if they exist
            const daysEl = document.querySelector('.countdown-days');
            const hoursEl = document.querySelector('.countdown-hours');
            const minutesEl = document.querySelector('.countdown-minutes');
            const secondsEl = document.querySelector('.countdown-seconds');
            
            if (daysEl) daysEl.textContent = days;
            if (hoursEl) hoursEl.textContent = hours;
            if (minutesEl) minutesEl.textContent = minutes;
            if (secondsEl) secondsEl.textContent = seconds;
        }
        
        setInterval(updateCountdown, 1000);
        <?php endif; ?>
    </script>
            </main>
        </div>
    </div>
</body>
</html>
