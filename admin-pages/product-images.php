<?php
session_start();
require_once '../config/config.php';
require_once '../models/Product.php';
require_once '../models/Admin.php';

// Verificar si el admin está logueado
if (!isAdminLoggedIn()) {
    redirectTo(ADMIN_URL . '/login.php');
}

// Verificar que se proporcionó un ID de producto
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: products.php');
    exit;
}

$productId = (int)$_GET['id'];
$productModel = new Product();

// Obtener el producto
$product = $productModel->findById($productId);
if (!$product) {
    header('Location: products.php?error=product_not_found');
    exit;
}

// Obtener imágenes del producto
$productImages = $productModel->getProductImages($productId);

$message = '';
$messageType = '';

// Procesar acciones AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'upload_image':
            if (!empty($_FILES['image']['name'])) {
                $uploadResult = uploadImage($_FILES['image'], 'products');
                if ($uploadResult['success']) {
                    $altText = $_POST['alt_text'] ?? ('Imagen adicional - ' . $product['name']);
                    $isPrimary = ($_POST['is_primary'] ?? 0) == 1;
                    
                    // Verificar límite de 5 imágenes
                    $currentCount = $productModel->countProductImages($productId);
                    if ($currentCount >= 5) {
                        echo json_encode(['success' => false, 'message' => 'Máximo 5 imágenes por producto']);
                        exit;
                    }
                    
                    $success = $productModel->addProductImageNew($productId, $uploadResult['path'], $altText, $isPrimary);
                    
                    if ($success) {
                        echo json_encode([
                            'success' => true, 
                            'message' => 'Imagen subida correctamente',
                            'reload' => true
                        ]);
                    } else {
                        echo json_encode(['success' => false, 'message' => 'Error al guardar la imagen']);
                    }
                } else {
                    echo json_encode(['success' => false, 'message' => $uploadResult['message']]);
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'No se seleccionó ninguna imagen']);
            }
            exit;
            
        case 'delete_image':
            $imageId = (int)($_POST['image_id'] ?? 0);
            if ($imageId) {
                $success = $productModel->removeProductImage($imageId);
                echo json_encode([
                    'success' => $success,
                    'message' => $success ? 'Imagen eliminada correctamente' : 'Error al eliminar la imagen',
                    'reload' => $success
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'ID de imagen inválido']);
            }
            exit;
            
        case 'set_primary':
            $imageId = (int)($_POST['image_id'] ?? 0);
            if ($imageId) {
                $success = $productModel->setPrimaryImage($productId, $imageId);
                echo json_encode([
                    'success' => $success,
                    'message' => $success ? 'Imagen principal actualizada' : 'Error al actualizar imagen principal',
                    'reload' => $success
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'ID de imagen inválido']);
            }
            exit;
            
        case 'update_order':
            $orders = json_decode($_POST['orders'] ?? '[]', true);
            if (!empty($orders)) {
                $success = $productModel->updateImageOrder($productId, $orders);
                echo json_encode([
                    'success' => $success,
                    'message' => $success ? 'Orden actualizado correctamente' : 'Error al actualizar el orden'
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Datos de orden inválidos']);
            }
            exit;
    }
}

$admin = new Admin();
$adminData = $admin->findById($_SESSION['admin_id']);
?><!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Imágenes - <?php echo htmlspecialchars($product['name']); ?> | Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>
    
    <style>
        .image-card {
            transition: all 0.3s ease;
        }
        .image-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }
        .primary-badge {
            background: linear-gradient(45deg, #f59e0b, #fbbf24);
        }
        .sortable-ghost {
            opacity: 0.5;
        }
        .sortable-chosen {
            transform: scale(1.05);
        }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Include Admin Header -->
    <?php include '../admin/includes/header.php'; ?>
    
    <div class="flex h-screen bg-gray-50">
        <!-- Include Admin Sidebar -->
        <?php include '../admin/includes/sidebar.php'; ?>
        
        <!-- Main Content -->
        <div class="flex-1 flex flex-col overflow-hidden">
            <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-50">
                <div class="container mx-auto px-6 py-8">
                    <!-- Header -->
                    <div class="mb-8">
                        <div class="flex items-center justify-between">
                            <div>
                                <h1 class="text-3xl font-bold text-gray-900 flex items-center">
                                    <i class="fas fa-images text-blue-500 mr-3"></i>
                                    Gestión de Imágenes
                                </h1>
                                <p class="text-gray-600 mt-2">
                                    Producto: <span class="font-semibold"><?php echo htmlspecialchars($product['name']); ?></span>
                                </p>
                            </div>
                            <div class="flex space-x-3">
                                <a href="products-edit.php?id=<?php echo $productId; ?>" 
                                   class="bg-gray-500 text-white px-4 py-2 rounded-lg hover:bg-gray-600 transition-colors">
                                    <i class="fas fa-edit mr-2"></i>Editar Producto
                                </a>
                                <a href="products.php" 
                                   class="bg-blue-500 text-white px-4 py-2 rounded-lg hover:bg-blue-600 transition-colors">
                                    <i class="fas fa-arrow-left mr-2"></i>Volver a Productos
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Stats Cards -->
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                        <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
                            <div class="flex items-center">
                                <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center mr-4">
                                    <i class="fas fa-images text-blue-600 text-xl"></i>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-600">Total Imágenes</p>
                                    <p class="text-2xl font-bold text-gray-900"><?php echo count($productImages); ?></p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
                            <div class="flex items-center">
                                <div class="w-12 h-12 bg-yellow-100 rounded-lg flex items-center justify-center mr-4">
                                    <i class="fas fa-star text-yellow-600 text-xl"></i>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-600">Imagen Principal</p>
                                    <p class="text-2xl font-bold text-gray-900">
                                        <?php 
                                        $hasPrimary = false;
                                        foreach ($productImages as $img) {
                                            if ($img['is_primary']) {
                                                $hasPrimary = true;
                                                break;
                                            }
                                        }
                                        echo $hasPrimary ? 'Sí' : 'No';
                                        ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
                            <div class="flex items-center">
                                <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center mr-4">
                                    <i class="fas fa-plus text-green-600 text-xl"></i>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-600">Disponibles</p>
                                    <p class="text-2xl font-bold text-gray-900"><?php echo 5 - count($productImages); ?></p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
                            <div class="flex items-center">
                                <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center mr-4">
                                    <i class="fas fa-eye text-purple-600 text-xl"></i>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-600">Vistas Producto</p>
                                    <p class="text-2xl font-bold text-gray-900"><?php echo number_format($product['views'] ?? 0); ?></p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Upload New Image -->
                    <?php if (count($productImages) < 5): ?>
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mb-8">
                        <h2 class="text-xl font-semibold text-gray-900 mb-4 flex items-center">
                            <i class="fas fa-upload text-green-500 mr-2"></i>
                            Subir Nueva Imagen
                        </h2>
                        
                        <form id="upload-form" enctype="multipart/form-data" class="space-y-4">
                            <input type="hidden" name="action" value="upload_image">
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label for="image" class="block text-sm font-medium text-gray-700 mb-2">
                                        Seleccionar Imagen *
                                    </label>
                                    <input type="file" id="image" name="image" accept="image/*" required
                                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                    <p class="text-xs text-gray-500 mt-1">Máximo 5MB. Formatos: JPG, PNG, WebP</p>
                                </div>
                                
                                <div>
                                    <label for="alt_text" class="block text-sm font-medium text-gray-700 mb-2">
                                        Texto Alternativo
                                    </label>
                                    <input type="text" id="alt_text" name="alt_text" 
                                           placeholder="Descripción de la imagen"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                </div>
                            </div>
                            
                            <div class="flex items-center space-x-4">
                                <label class="flex items-center">
                                    <input type="checkbox" id="is_primary" name="is_primary" value="1"
                                           class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500">
                                    <span class="ml-2 text-sm text-gray-700">Establecer como imagen principal</span>
                                </label>
                                
                                <button type="submit" 
                                        class="bg-blue-500 text-white px-6 py-2 rounded-lg hover:bg-blue-600 transition-colors">
                                    <i class="fas fa-upload mr-2"></i>Subir Imagen
                                </button>
                            </div>
                            
                            <!-- Preview -->
                            <div id="preview-container" class="hidden mt-4">
                                <p class="text-sm text-gray-700 mb-2">Vista previa:</p>
                                <img id="preview-image" src="" alt="Vista previa" class="w-32 h-32 object-cover rounded-lg border border-gray-200">
                            </div>
                        </form>
                    </div>
                    <?php else: ?>
                    <div class="bg-yellow-50 border border-yellow-200 rounded-xl p-6 mb-8">
                        <div class="flex items-center">
                            <i class="fas fa-exclamation-triangle text-yellow-600 text-xl mr-3"></i>
                            <div>
                                <h3 class="text-lg font-semibold text-yellow-800">Límite de imágenes alcanzado</h3>
                                <p class="text-yellow-700">Has alcanzado el límite máximo de 5 imágenes por producto. Elimina alguna imagen para subir una nueva.</p>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Images Gallery -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                        <div class="flex items-center justify-between mb-6">
                            <h2 class="text-xl font-semibold text-gray-900 flex items-center">
                                <i class="fas fa-th-large text-blue-500 mr-2"></i>
                                Galería de Imágenes
                                <?php if (count($productImages) > 1): ?>
                                <span class="text-sm text-gray-500 ml-2">(Arrastra para reordenar)</span>
                                <?php endif; ?>
                            </h2>
                        </div>

                        <?php if (empty($productImages)): ?>
                        <div class="text-center py-12">
                            <i class="fas fa-images text-gray-300 text-6xl mb-4"></i>
                            <h3 class="text-lg font-semibold text-gray-500 mb-2">No hay imágenes</h3>
                            <p class="text-gray-400">Sube la primera imagen de este producto</p>
                        </div>
                        <?php else: ?>
                        <div id="images-grid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                            <?php foreach ($productImages as $image): ?>
                            <div class="image-card bg-white border border-gray-200 rounded-xl overflow-hidden" data-image-id="<?php echo $image['id']; ?>">
                                <!-- Image -->
                                <div class="relative">
                                    <img src="<?php echo BASE_URL . '/' . $image['image_path']; ?>" 
                                         alt="<?php echo htmlspecialchars($image['alt_text']); ?>"
                                         class="w-full h-48 object-cover">
                                    
                                    <!-- Primary Badge -->
                                    <?php if ($image['is_primary']): ?>
                                    <div class="absolute top-3 left-3">
                                        <span class="primary-badge text-white text-xs font-bold px-2 py-1 rounded-full shadow-lg">
                                            <i class="fas fa-star mr-1"></i>Principal
                                        </span>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <!-- Actions -->
                                    <div class="absolute top-3 right-3 flex space-x-2">
                                        <?php if (!$image['is_primary']): ?>
                                        <button onclick="setPrimary(<?php echo $image['id']; ?>)" 
                                                class="bg-yellow-500 text-white p-2 rounded-full hover:bg-yellow-600 transition-colors"
                                                title="Establecer como principal">
                                            <i class="fas fa-star text-xs"></i>
                                        </button>
                                        <?php endif; ?>
                                        
                                        <button onclick="deleteImage(<?php echo $image['id']; ?>)" 
                                                class="bg-red-500 text-white p-2 rounded-full hover:bg-red-600 transition-colors"
                                                title="Eliminar imagen">
                                            <i class="fas fa-trash text-xs"></i>
                                        </button>
                                    </div>
                                    
                                    <!-- Drag Handle -->
                                    <?php if (count($productImages) > 1): ?>
                                    <div class="absolute bottom-3 left-3">
                                        <div class="bg-black bg-opacity-50 text-white p-2 rounded cursor-move" title="Arrastra para reordenar">
                                            <i class="fas fa-grip-vertical text-xs"></i>
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                </div>
                                
                                <!-- Info -->
                                <div class="p-4">
                                    <p class="text-sm text-gray-600 mb-2">
                                        <strong>Alt Text:</strong> <?php echo htmlspecialchars($image['alt_text'] ?: 'Sin descripción'); ?>
                                    </p>
                                    <p class="text-xs text-gray-500">
                                        <strong>Orden:</strong> <?php echo $image['sort_order']; ?> | 
                                        <strong>Subida:</strong> <?php echo date('d/m/Y H:i', strtotime($image['created_at'])); ?>
                                    </p>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Loading Overlay -->
    <div id="loading" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
        <div class="bg-white rounded-lg p-6 flex items-center space-x-3">
            <i class="fas fa-spinner fa-spin text-blue-500 text-xl"></i>
            <span class="text-gray-700">Procesando...</span>
        </div>
    </div>

    <script>
        // Initialize sortable if there are multiple images
        <?php if (count($productImages) > 1): ?>
        const sortable = Sortable.create(document.getElementById('images-grid'), {
            animation: 150,
            ghostClass: 'sortable-ghost',
            chosenClass: 'sortable-chosen',
            onEnd: function(evt) {
                updateImageOrder();
            }
        });
        <?php endif; ?>

        // Preview uploaded image
        document.getElementById('image').addEventListener('change', function(e) {
            const file = e.target.files[0];
            const preview = document.getElementById('preview-container');
            const previewImg = document.getElementById('preview-image');
            
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    previewImg.src = e.target.result;
                    preview.classList.remove('hidden');
                };
                reader.readAsDataURL(file);
            } else {
                preview.classList.add('hidden');
            }
        });

        // Upload form submission
        document.getElementById('upload-form').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            showLoading();
            
            fetch(window.location.href, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                hideLoading();
                
                if (data.success) {
                    showMessage(data.message, 'success');
                    if (data.reload) {
                        setTimeout(() => window.location.reload(), 1500);
                    }
                } else {
                    showMessage(data.message, 'error');
                }
            })
            .catch(error => {
                hideLoading();
                showMessage('Error de conexión', 'error');
            });
        });

        // Delete image
        function deleteImage(imageId) {
            if (!confirm('¿Estás seguro de que deseas eliminar esta imagen?')) {
                return;
            }
            
            showLoading();
            
            const formData = new FormData();
            formData.append('action', 'delete_image');
            formData.append('image_id', imageId);
            
            fetch(window.location.href, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                hideLoading();
                
                if (data.success) {
                    showMessage(data.message, 'success');
                    if (data.reload) {
                        setTimeout(() => window.location.reload(), 1500);
                    }
                } else {
                    showMessage(data.message, 'error');
                }
            })
            .catch(error => {
                hideLoading();
                showMessage('Error de conexión', 'error');
            });
        }

        // Set primary image
        function setPrimary(imageId) {
            showLoading();
            
            const formData = new FormData();
            formData.append('action', 'set_primary');
            formData.append('image_id', imageId);
            
            fetch(window.location.href, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                hideLoading();
                
                if (data.success) {
                    showMessage(data.message, 'success');
                    if (data.reload) {
                        setTimeout(() => window.location.reload(), 1500);
                    }
                } else {
                    showMessage(data.message, 'error');
                }
            })
            .catch(error => {
                hideLoading();
                showMessage('Error de conexión', 'error');
            });
        }

        // Update image order
        function updateImageOrder() {
            const imageCards = document.querySelectorAll('.image-card');
            const orders = {};
            
            imageCards.forEach((card, index) => {
                const imageId = card.dataset.imageId;
                orders[imageId] = index;
            });
            
            const formData = new FormData();
            formData.append('action', 'update_order');
            formData.append('orders', JSON.stringify(orders));
            
            fetch(window.location.href, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showMessage(data.message, 'success');
                } else {
                    showMessage(data.message, 'error');
                }
            })
            .catch(error => {
                showMessage('Error al actualizar orden', 'error');
            });
        }

        // Utility functions
        function showLoading() {
            document.getElementById('loading').classList.remove('hidden');
        }

        function hideLoading() {
            document.getElementById('loading').classList.add('hidden');
        }

        function showMessage(message, type) {
            // Create toast notification
            const toast = document.createElement('div');
            toast.className = `fixed top-4 right-4 p-4 rounded-lg shadow-lg z-50 ${
                type === 'success' ? 'bg-green-500 text-white' : 'bg-red-500 text-white'
            }`;
            toast.innerHTML = `
                <div class="flex items-center space-x-2">
                    <i class="fas fa-${type === 'success' ? 'check' : 'exclamation-triangle'}"></i>
                    <span>${message}</span>
                </div>
            `;
            
            document.body.appendChild(toast);
            
            setTimeout(() => {
                toast.remove();
            }, 3000);
        }
    </script>
</body>
</html>
