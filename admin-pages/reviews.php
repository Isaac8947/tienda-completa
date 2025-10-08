<?php
session_start();
require_once '../config/config.php';
require_once '../models/Review.php';
require_once '../models/Product.php';
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

$reviewModel = new Review();
$productModel = new Product();

// Manejar acciones AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    $action = $_POST['action'] ?? '';
    
    if ($action === 'update_status') {
        $reviewId = $_POST['review_id'] ?? '';
        $status = $_POST['status'] ?? '';
        
        if ($reviewId && $status) {
            $success = $reviewModel->updateStatus($reviewId, $status);
            echo json_encode(['success' => $success, 'message' => $success ? 'Estado actualizado' : 'Error al actualizar']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Datos inválidos']);
        }
        exit;
    }
    
    if ($action === 'add_reply') {
        $reviewId = $_POST['review_id'] ?? '';
        $replyText = $_POST['reply_text'] ?? '';
        
        if ($reviewId && $replyText) {
            $success = $reviewModel->addReply($reviewId, $replyText, $_SESSION['admin_id']);
            echo json_encode(['success' => $success, 'message' => $success ? 'Respuesta agregada' : 'Error al agregar respuesta']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Datos inválidos']);
        }
        exit;
    }
    
    if ($action === 'remove_reply') {
        $reviewId = $_POST['review_id'] ?? '';
        
        if ($reviewId) {
            $success = $reviewModel->removeReply($reviewId);
            echo json_encode(['success' => $success, 'message' => $success ? 'Respuesta eliminada' : 'Error al eliminar respuesta']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Datos inválidos']);
        }
        exit;
    }
    
    if ($action === 'bulk_action') {
        $bulkAction = $_POST['bulk_action'] ?? '';
        $reviewIds = json_decode($_POST['review_ids'] ?? '[]', true);
        
        if (empty($reviewIds)) {
            echo json_encode(['success' => false, 'message' => 'No se seleccionaron reseñas']);
            exit;
        }
        
        $success = false;
        $message = '';
        
        switch ($bulkAction) {
            case 'approve':
                $success = $reviewModel->bulkUpdateStatus($reviewIds, 'approved');
                $message = $success ? 'Reseñas aprobadas' : 'Error al aprobar reseñas';
                break;
                
            case 'reject':
                $success = $reviewModel->bulkUpdateStatus($reviewIds, 'rejected');
                $message = $success ? 'Reseñas rechazadas' : 'Error al rechazar reseñas';
                break;
                
            case 'pending':
                $success = $reviewModel->bulkUpdateStatus($reviewIds, 'pending');
                $message = $success ? 'Reseñas marcadas como pendientes' : 'Error al actualizar reseñas';
                break;
                
            case 'delete':
                $success = $reviewModel->bulkDelete($reviewIds);
                $message = $success ? 'Reseñas eliminadas' : 'Error al eliminar reseñas';
                break;
        }
        
        echo json_encode(['success' => $success, 'message' => $message]);
        exit;
    }
    
    echo json_encode(['success' => false, 'message' => 'Acción no reconocida']);
    exit;
}

// Filtros y paginación
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

$filters = [
    'search' => $_GET['search'] ?? '',
    'status' => $_GET['status'] ?? '',
    'rating' => $_GET['rating'] ?? '',
    'product_id' => $_GET['product_id'] ?? '',
    'verified_purchase' => $_GET['verified_purchase'] ?? ''
];

// Obtener reseñas con filtros
$reviews = $reviewModel->getAllWithDetails($limit, $offset, $filters);
$totalReviews = $reviewModel->countWithFilters($filters);
$totalPages = ceil($totalReviews / $limit);

// Obtener estadísticas
$stats = $reviewModel->getStats();
$ratingDistribution = $reviewModel->getRatingDistribution();

// Obtener productos para filtro
$products = $productModel->findAll(['status' => 'active']);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reseñas - Odisea Admin</title>
    
    <script src="https://cdn.tailwindcss.com"></script>
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
                        secondary: {
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../admin/assets/css/admin.css">
</head>
<body class="bg-gray-50">
    <div class="flex h-screen overflow-hidden">
        <?php include '../admin/includes/sidebar.php'; ?>
        
        <div class="flex-1 flex flex-col overflow-hidden">
            <?php include '../admin/includes/header.php'; ?>
            
            <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-50 p-4 lg:p-6">
                <!-- Page Header -->
                <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between mb-8">
                    <div class="mb-4 lg:mb-0">
                        <h1 class="text-2xl lg:text-3xl font-bold text-gray-900">Gestión de Reseñas</h1>
                        <p class="text-gray-600 mt-1">Administra las reseñas y comentarios de productos</p>
                    </div>
                </div>

                <!-- Stats Cards -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                        <div class="flex items-center">
                            <div class="p-3 bg-blue-100 rounded-lg">
                                <i class="fas fa-comments text-blue-600 text-xl"></i>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-600">Total Reseñas</p>
                                <p class="text-2xl font-bold text-gray-900"><?php echo number_format($stats['total_reviews'] ?? 0); ?></p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                        <div class="flex items-center">
                            <div class="p-3 bg-green-100 rounded-lg">
                                <i class="fas fa-check-circle text-green-600 text-xl"></i>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-600">Aprobadas</p>
                                <p class="text-2xl font-bold text-gray-900"><?php echo number_format($stats['approved_reviews'] ?? 0); ?></p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                        <div class="flex items-center">
                            <div class="p-3 bg-yellow-100 rounded-lg">
                                <i class="fas fa-clock text-yellow-600 text-xl"></i>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-600">Pendientes</p>
                                <p class="text-2xl font-bold text-gray-900"><?php echo number_format($stats['pending_reviews'] ?? 0); ?></p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                        <div class="flex items-center">
                            <div class="p-3 bg-purple-100 rounded-lg">
                                <i class="fas fa-star text-purple-600 text-xl"></i>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-600">Calificación Promedio</p>
                                <p class="text-2xl font-bold text-gray-900"><?php echo number_format($stats['average_rating'] ?? 0, 1); ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filters -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
                    <form method="GET" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-6 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Buscar</label>
                            <input type="text" name="search" value="<?php echo htmlspecialchars($filters['search']); ?>" 
                                   placeholder="Título, comentario, cliente..." 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Estado</label>
                            <select name="status" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500">
                                <option value="">Todos los estados</option>
                                <option value="pending" <?php echo $filters['status'] == 'pending' ? 'selected' : ''; ?>>Pendiente</option>
                                <option value="approved" <?php echo $filters['status'] == 'approved' ? 'selected' : ''; ?>>Aprobada</option>
                                <option value="rejected" <?php echo $filters['status'] == 'rejected' ? 'selected' : ''; ?>>Rechazada</option>
                            </select>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Calificación</label>
                            <select name="rating" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500">
                                <option value="">Todas las estrellas</option>
                                <option value="5" <?php echo $filters['rating'] == '5' ? 'selected' : ''; ?>>5 estrellas</option>
                                <option value="4" <?php echo $filters['rating'] == '4' ? 'selected' : ''; ?>>4 estrellas</option>
                                <option value="3" <?php echo $filters['rating'] == '3' ? 'selected' : ''; ?>>3 estrellas</option>
                                <option value="2" <?php echo $filters['rating'] == '2' ? 'selected' : ''; ?>>2 estrellas</option>
                                <option value="1" <?php echo $filters['rating'] == '1' ? 'selected' : ''; ?>>1 estrella</option>
                            </select>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Producto</label>
                            <select name="product_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500">
                                <option value="">Todos los productos</option>
                                <?php foreach ($products as $product): ?>
                                    <option value="<?php echo $product['id']; ?>" <?php echo $filters['product_id'] == $product['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($product['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Compra Verificada</label>
                            <select name="verified_purchase" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500">
                                <option value="">Todas</option>
                                <option value="1" <?php echo $filters['verified_purchase'] == '1' ? 'selected' : ''; ?>>Verificadas</option>
                                <option value="0" <?php echo $filters['verified_purchase'] == '0' ? 'selected' : ''; ?>>No verificadas</option>
                            </select>
                        </div>
                        
                        <div class="flex items-end">
                            <button type="submit" class="w-full bg-primary-600 hover:bg-primary-700 text-white px-4 py-2 rounded-lg transition-colors">
                                <i class="fas fa-search mr-2"></i>Filtrar
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Reviews Table -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <div class="flex items-center justify-between">
                            <h3 class="text-lg font-semibold text-gray-900">
                                Reseñas (<?php echo number_format($totalReviews); ?>)
                            </h3>
                            <div class="flex items-center space-x-2">
                                <input type="checkbox" class="rounded border-gray-300 text-primary-600 focus:ring-primary-500" id="select-all">
                                <label for="select-all" class="text-sm text-gray-600">Seleccionar todo</label>
                            </div>
                        </div>
                    </div>
                    
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                                        <input type="checkbox" class="rounded border-gray-300" id="select-all-header">
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Reseña</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Producto</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Cliente</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Calificación</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Estado</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Fecha</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Acciones</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <?php if (empty($reviews)): ?>
                                <tr>
                                    <td colspan="8" class="px-6 py-12 text-center text-gray-500">
                                        <i class="fas fa-comments text-4xl text-gray-300 mb-3"></i>
                                        <p>No se encontraron reseñas</p>
                                    </td>
                                </tr>
                                <?php else: ?>
                                    <?php foreach ($reviews as $review): ?>
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4">
                                            <input type="checkbox" class="rounded border-gray-300 text-primary-600 focus:ring-primary-500" value="<?php echo $review['id']; ?>">
                                        </td>
                                        <td class="px-6 py-4">
                                            <div>
                                                <div class="font-medium text-gray-900 mb-1">
                                                    <?php echo htmlspecialchars($review['title'] ?: 'Sin título'); ?>
                                                </div>
                                                <div class="text-sm text-gray-600 line-clamp-2">
                                                    <?php echo htmlspecialchars(substr($review['comment'], 0, 100)) . (strlen($review['comment']) > 100 ? '...' : ''); ?>
                                                </div>
                                                <?php if ($review['is_verified']): ?>
                                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800 mt-1">
                                                        <i class="fas fa-check-circle mr-1"></i>Compra verificada
                                                    </span>
                                                <?php endif; ?>
                                                <?php if (isset($review['reply_text']) && $review['reply_text']): ?>
                                                    <div class="mt-2 p-2 bg-blue-50 rounded border-l-4 border-blue-400">
                                                        <div class="text-xs font-medium text-blue-800 mb-1">
                                                            Respuesta del administrador
                                                        </div>
                                                        <div class="text-sm text-blue-700">
                                                            <?php echo htmlspecialchars($review['reply_text']); ?>
                                                        </div>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="flex items-center">
                                                <?php if ($review['product_image']): ?>
                                                    <img class="h-10 w-10 rounded object-cover mr-3" 
                                                         src="../<?php echo htmlspecialchars($review['product_image']); ?>" 
                                                         alt="<?php echo htmlspecialchars($review['product_name']); ?>">
                                                <?php else: ?>
                                                    <div class="h-10 w-10 bg-gray-200 rounded flex items-center justify-center mr-3">
                                                        <i class="fas fa-image text-gray-400"></i>
                                                    </div>
                                                <?php endif; ?>
                                                <div class="text-sm font-medium text-gray-900">
                                                    <?php echo htmlspecialchars(substr($review['product_name'], 0, 30)) . (strlen($review['product_name']) > 30 ? '...' : ''); ?>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div>
                                                <div class="text-sm font-medium text-gray-900">
                                                    <?php echo htmlspecialchars(($review['first_name'] ?? '') . ' ' . ($review['last_name'] ?? '')); ?>
                                                </div>
                                                <div class="text-sm text-gray-500">
                                                    <?php echo htmlspecialchars($review['customer_email_full'] ?? ''); ?>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="flex items-center">
                                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                                    <i class="fas fa-star <?php echo $i <= $review['rating'] ? 'text-yellow-400' : 'text-gray-300'; ?>"></i>
                                                <?php endfor; ?>
                                                <span class="ml-2 text-sm text-gray-600">(<?php echo $review['rating']; ?>)</span>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <?php 
                                            $status = $review['is_approved'] ? 'approved' : 'pending';
                                            ?>
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                                <?php 
                                                    echo $status === 'approved' ? 'bg-green-100 text-green-800' : 
                                                        ($status === 'rejected' ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800');
                                                ?>">
                                                <?php 
                                                    echo $status === 'approved' ? 'Aprobada' : 
                                                        ($status === 'rejected' ? 'Rechazada' : 'Pendiente');
                                                ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-500">
                                            <?php echo date('d/m/Y H:i', strtotime($review['created_at'])); ?>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="flex items-center space-x-2">
                                                <button onclick="openReviewModal(<?php echo $review['id']; ?>)" 
                                                        class="text-blue-600 hover:text-blue-900 transition-colors">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                
                                                <?php if (!$review['is_approved']): ?>
                                                <button onclick="updateReviewStatus(<?php echo $review['id']; ?>, 'approved')" 
                                                        class="text-green-600 hover:text-green-900 transition-colors" title="Aprobar">
                                                    <i class="fas fa-check"></i>
                                                </button>
                                                <?php endif; ?>
                                                
                                                <?php if ($review['is_approved']): ?>
                                                <button onclick="updateReviewStatus(<?php echo $review['id']; ?>, 'rejected')" 
                                                        class="text-red-600 hover:text-red-900 transition-colors" title="Rechazar">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                                <?php endif; ?>
                                                
                                                <button onclick="deleteReview(<?php echo $review['id']; ?>)" 
                                                        class="text-red-600 hover:text-red-900 transition-colors" title="Eliminar">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Pagination -->
                    <?php if ($totalPages > 1): ?>
                    <div class="px-6 py-4 border-t border-gray-200">
                        <div class="flex items-center justify-between">
                            <div class="text-sm text-gray-700">
                                Mostrando <?php echo (($page - 1) * $limit) + 1; ?> - <?php echo min($page * $limit, $totalReviews); ?> de <?php echo $totalReviews; ?> reseñas
                            </div>
                            <div class="flex space-x-2">
                                <?php if ($page > 1): ?>
                                    <a href="?page=<?php echo $page - 1; ?>&<?php echo http_build_query(array_filter($filters)); ?>" 
                                       class="bg-white border border-gray-300 text-gray-500 hover:bg-gray-50 px-3 py-2 rounded-lg text-sm transition-colors">
                                        Anterior
                                    </a>
                                <?php endif; ?>
                                
                                <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                                    <a href="?page=<?php echo $i; ?>&<?php echo http_build_query(array_filter($filters)); ?>" 
                                       class="<?php echo $i === $page ? 'bg-primary-600 text-white' : 'bg-white border border-gray-300 text-gray-500 hover:bg-gray-50'; ?> px-3 py-2 rounded-lg text-sm transition-colors">
                                        <?php echo $i; ?>
                                    </a>
                                <?php endfor; ?>
                                
                                <?php if ($page < $totalPages): ?>
                                    <a href="?page=<?php echo $page + 1; ?>&<?php echo http_build_query(array_filter($filters)); ?>" 
                                       class="bg-white border border-gray-300 text-gray-500 hover:bg-gray-50 px-3 py-2 rounded-lg text-sm transition-colors">
                                        Siguiente
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Bulk Actions -->
                <div class="fixed bottom-6 left-1/2 transform -translate-x-1/2 bg-white rounded-lg shadow-lg border border-gray-200 px-6 py-3 hidden" id="bulk-actions">
                    <div class="flex items-center space-x-4">
                        <span class="text-sm text-gray-600" id="selected-count">0 reseñas seleccionadas</span>
                        <div class="flex items-center space-x-2">
                            <button id="bulk-approve" class="bg-green-500 text-white px-4 py-2 rounded-lg hover:bg-green-600 transition-colors duration-200 text-sm">
                                <i class="fas fa-check mr-2"></i>Aprobar
                            </button>
                            <button id="bulk-reject" class="bg-red-500 text-white px-4 py-2 rounded-lg hover:bg-red-600 transition-colors duration-200 text-sm">
                                <i class="fas fa-times mr-2"></i>Rechazar
                            </button>
                            <button id="bulk-pending" class="bg-yellow-500 text-white px-4 py-2 rounded-lg hover:bg-yellow-600 transition-colors duration-200 text-sm">
                                <i class="fas fa-clock mr-2"></i>Pendiente
                            </button>
                            <button id="bulk-delete" class="bg-gray-500 text-white px-4 py-2 rounded-lg hover:bg-gray-600 transition-colors duration-200 text-sm">
                                <i class="fas fa-trash mr-2"></i>Eliminar
                            </button>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Review Detail Modal -->
    <div class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden" id="review-modal">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="bg-white rounded-lg shadow-xl max-w-2xl w-full p-6 max-h-[90vh] overflow-y-auto">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-xl font-semibold text-gray-900">Detalles de la Reseña</h3>
                    <button onclick="closeReviewModal()" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
                
                <div id="review-content">
                    <!-- Content will be loaded here -->
                </div>
            </div>
        </div>
    </div>

    <!-- Confirmation Modal -->
    <div class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden" id="confirmation-modal">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="bg-white rounded-lg shadow-xl max-w-md w-full p-6">
                <div class="flex items-center justify-center w-12 h-12 mx-auto bg-blue-100 rounded-full mb-4">
                    <i class="fas fa-question-circle text-blue-600 text-xl"></i>
                </div>
                <h3 class="text-lg font-semibold text-gray-900 text-center mb-2" id="modal-title">Confirmar acción</h3>
                <p class="text-gray-600 text-center mb-6" id="modal-message">¿Estás seguro de realizar esta acción?</p>
                <div class="flex space-x-3">
                    <button onclick="closeConfirmationModal()" class="flex-1 bg-gray-100 text-gray-700 py-2 px-4 rounded-lg hover:bg-gray-200 transition-colors duration-200">
                        Cancelar
                    </button>
                    <button onclick="confirmAction()" id="confirm-btn" class="flex-1 bg-primary-500 text-white py-2 px-4 rounded-lg hover:bg-primary-600 transition-colors duration-200">
                        Confirmar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="../admin/assets/js/admin.js"></script>
    <script>
        let currentAction = null;
        let currentData = null;

        // Bulk actions
        document.addEventListener('DOMContentLoaded', function() {
            const checkboxes = document.querySelectorAll('input[type="checkbox"][value]');
            const selectAll = document.getElementById('select-all');
            const selectAllHeader = document.getElementById('select-all-header');
            const bulkActions = document.getElementById('bulk-actions');
            const selectedCount = document.getElementById('selected-count');

            function updateBulkActions() {
                const selected = document.querySelectorAll('input[type="checkbox"][value]:checked');
                if (selected.length > 0) {
                    bulkActions.classList.remove('hidden');
                    selectedCount.textContent = `${selected.length} reseña${selected.length > 1 ? 's' : ''} seleccionada${selected.length > 1 ? 's' : ''}`;
                } else {
                    bulkActions.classList.add('hidden');
                }
            }

            checkboxes.forEach(checkbox => {
                checkbox.addEventListener('change', updateBulkActions);
            });

            [selectAll, selectAllHeader].forEach(checkbox => {
                if (checkbox) {
                    checkbox.addEventListener('change', function() {
                        checkboxes.forEach(cb => {
                            cb.checked = this.checked;
                        });
                        updateBulkActions();
                    });
                }
            });

            // Bulk action handlers
            document.getElementById('bulk-approve').addEventListener('click', () => bulkAction('approve'));
            document.getElementById('bulk-reject').addEventListener('click', () => bulkAction('reject'));
            document.getElementById('bulk-pending').addEventListener('click', () => bulkAction('pending'));
            document.getElementById('bulk-delete').addEventListener('click', () => bulkAction('delete'));
        });

        function getSelectedReviews() {
            const selected = document.querySelectorAll('input[type="checkbox"][value]:checked');
            return Array.from(selected).map(cb => cb.value);
        }

        function bulkAction(action) {
            const selected = getSelectedReviews();
            if (selected.length === 0) {
                alert('No hay reseñas seleccionadas');
                return;
            }

            const messages = {
                'approve': `¿Aprobar ${selected.length} reseña${selected.length > 1 ? 's' : ''}?`,
                'reject': `¿Rechazar ${selected.length} reseña${selected.length > 1 ? 's' : ''}?`,
                'pending': `¿Marcar como pendiente ${selected.length} reseña${selected.length > 1 ? 's' : ''}?`,
                'delete': `¿Eliminar permanentemente ${selected.length} reseña${selected.length > 1 ? 's' : ''}?`
            };

            showConfirmation('Confirmar acción', messages[action], () => {
                const formData = new FormData();
                formData.append('action', 'bulk_action');
                formData.append('bulk_action', action);
                formData.append('review_ids', JSON.stringify(selected));

                fetch('reviews.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert(data.message || 'Error al realizar la acción');
                    }
                });
            });
        }

        function updateReviewStatus(id, status) {
            const formData = new FormData();
            formData.append('action', 'update_status');
            formData.append('review_id', id);
            formData.append('status', status);

            fetch('reviews.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert(data.message || 'Error al actualizar el estado');
                }
            });
        }

        function deleteReview(id) {
            showConfirmation('Eliminar reseña', '¿Eliminar esta reseña permanentemente?', () => {
                bulkAction('delete');
                // Override selected reviews for single deletion
                const checkboxes = document.querySelectorAll('input[type="checkbox"][value]');
                checkboxes.forEach(cb => cb.checked = cb.value == id);
                bulkAction('delete');
            });
        }

        function openReviewModal(id) {
            // This would load review details via AJAX
            document.getElementById('review-modal').classList.remove('hidden');
        }

        function closeReviewModal() {
            document.getElementById('review-modal').classList.add('hidden');
        }

        function showConfirmation(title, message, callback) {
            document.getElementById('modal-title').textContent = title;
            document.getElementById('modal-message').textContent = message;
            currentAction = callback;
            document.getElementById('confirmation-modal').classList.remove('hidden');
        }

        function closeConfirmationModal() {
            document.getElementById('confirmation-modal').classList.add('hidden');
            currentAction = null;
        }

        function confirmAction() {
            if (currentAction) {
                currentAction();
            }
            closeConfirmationModal();
        }
    </script>
</body>
</html>
