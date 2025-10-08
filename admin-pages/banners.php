<?php
session_start();
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../models/Banner.php';
require_once '../admin/auth-check.php';

$bannerModel = new Banner();
$banners = $bannerModel->getAll();

// Procesar formulario de agregar banner
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add') {
        $image = $_POST['image'] ?? '';
        $position = $_POST['position'];
        $sort_order = $_POST['sort_order'] ?? 0;
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        $starts_at = $_POST['starts_at'] ?? null;
        $expires_at = $_POST['expires_at'] ?? null;
        
        // Procesar imagen
        if (isset($_FILES['image_file']) && $_FILES['image_file']['error'] === 0) {
            $uploadDir = '../uploads/banners/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            $fileName = time() . '_' . basename($_FILES['image_file']['name']);
            $targetFile = $uploadDir . $fileName;
            
            if (move_uploaded_file($_FILES['image_file']['tmp_name'], $targetFile)) {
                $image = 'uploads/banners/' . $fileName;
            }
        }
        
        $bannerModel->create([
            'image' => $image,
            'position' => $position,
            'sort_order' => $sort_order,
            'is_active' => $is_active,
            'starts_at' => $starts_at,
            'expires_at' => $expires_at
        ]);
        
        header('Location: banners.php?success=1');
        exit;
    } elseif ($_POST['action'] === 'edit') {
        $id = $_POST['id'];
        $image = $_POST['image'] ?? '';
        $position = $_POST['position'];
        $sort_order = $_POST['sort_order'] ?? 0;
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        $starts_at = $_POST['starts_at'] ?? null;
        $expires_at = $_POST['expires_at'] ?? null;
        
        $currentBanner = $bannerModel->getById($id);
        if (!$currentBanner) {
            header('Location: banners.php?error=1');
            exit;
        }
        $currentImage = $currentBanner['image'];
        
        // Procesar imagen si se subió una nueva
        if (isset($_FILES['image_file']) && $_FILES['image_file']['error'] === 0) {
            $uploadDir = '../uploads/banners/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            $fileName = time() . '_' . basename($_FILES['image_file']['name']);
            $targetFile = $uploadDir . $fileName;
            
            if (move_uploaded_file($_FILES['image_file']['tmp_name'], $targetFile)) {
                // Eliminar imagen anterior si existe
                if (!empty($currentImage) && file_exists('../' . $currentImage)) {
                    unlink('../' . $currentImage);
                }
                $image = 'uploads/banners/' . $fileName;
            } else {
                $image = $currentImage;
            }
        } else {
            $image = $currentImage;
        }
        
        $bannerModel->update($id, [
            'image' => $image,
            'position' => $position,
            'sort_order' => $sort_order,
            'is_active' => $is_active,
            'starts_at' => $starts_at,
            'expires_at' => $expires_at
        ]);
        
        header('Location: banners.php?updated=1');
        exit;
    } elseif ($_POST['action'] === 'delete') {
        $id = $_POST['id'];
        $banner = $bannerModel->getById($id);
        
        if ($banner) {
            // Eliminar imagen si existe
            if (!empty($banner['image']) && file_exists('../' . $banner['image'])) {
                unlink('../' . $banner['image']);
            }
            
            $bannerModel->delete($id);
        }
        
        header('Location: banners.php?deleted=1');
        exit;
    }
}

// Obtener banner para editar
$editBanner = null;
if (isset($_GET['edit'])) {
    $editId = $_GET['edit'];
    $editBanner = $bannerModel->getById($editId);
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Banners - Odisea Admin</title>
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
        }
        .modal.show {
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .switch {
            position: relative;
            display: inline-block;
            width: 44px;
            height: 24px;
        }
        .switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }
        .slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            transition: .4s;
            border-radius: 24px;
        }
        .slider:before {
            position: absolute;
            content: "";
            height: 18px;
            width: 18px;
            left: 3px;
            bottom: 3px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
        }
        input:checked + .slider {
            background-color: #10b981;
        }
        input:checked + .slider:before {
            transform: translateX(20px);
        }
    </style>
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
                        <h1 class="text-2xl lg:text-3xl font-bold text-gray-900">Gestión de Banners</h1>
                        <p class="text-gray-600 mt-1">Administra los banners y sliders del sitio web</p>
                    </div>
                    <div class="flex flex-col sm:flex-row space-y-2 sm:space-y-0 sm:space-x-3">
                        <button onclick="openModal('add')" class="inline-flex items-center px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white text-sm font-medium rounded-lg shadow-sm transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2">
                            <i class="fas fa-plus mr-2"></i>
                            Nuevo Banner
                        </button>
                    </div>
                </div>

                <!-- Success/Error Messages -->
                <?php if (isset($_GET['success'])): ?>
                <div class="mb-6 p-4 rounded-lg bg-green-100 text-green-800">
                    <i class="fas fa-check-circle mr-2"></i>
                    Banner agregado exitosamente.
                </div>
                <?php endif; ?>
                
                <?php if (isset($_GET['updated'])): ?>
                <div class="mb-6 p-4 rounded-lg bg-green-100 text-green-800">
                    <i class="fas fa-check-circle mr-2"></i>
                    Banner actualizado exitosamente.
                </div>
                <?php endif; ?>
                
                <?php if (isset($_GET['deleted'])): ?>
                <div class="mb-6 p-4 rounded-lg bg-green-100 text-green-800">
                    <i class="fas fa-check-circle mr-2"></i>
                    Banner eliminado exitosamente.
                </div>
                <?php endif; ?>

                <?php if (isset($_GET['error'])): ?>
                <div class="mb-6 p-4 rounded-lg bg-red-100 text-red-800">
                    <i class="fas fa-exclamation-circle mr-2"></i>
                    Ocurrió un error al procesar la solicitud.
                </div>
                <?php endif; ?>

                <!-- Banners Table -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Imagen</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Posición</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Orden</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Estado</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fechas</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($banners as $banner): ?>
                                <tr class="hover:bg-gray-50 transition-colors duration-200">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                        #<?php echo $banner['id']; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <?php if (!empty($banner['image'])): ?>
                                        <img src="../<?php echo htmlspecialchars($banner['image']); ?>" 
                                             alt="Banner" 
                                             class="h-16 w-24 object-cover rounded-lg border border-gray-200">
                                        <?php else: ?>
                                        <div class="h-16 w-24 bg-gray-100 rounded-lg flex items-center justify-center border border-gray-200">
                                            <i class="fas fa-image text-gray-400 text-xl"></i>
                                        </div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex px-2 py-1 text-xs font-medium bg-secondary-100 text-secondary-800 rounded-full">
                                            <?php echo ucfirst(str_replace('_', ' ', $banner['position'])); ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                            <?php echo $banner['sort_order']; ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <?php if ($banner['is_active'] == 1): ?>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                            <div class="w-1.5 h-1.5 bg-green-400 rounded-full mr-1.5"></div>
                                            Activo
                                        </span>
                                        <?php else: ?>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                            <div class="w-1.5 h-1.5 bg-red-400 rounded-full mr-1.5"></div>
                                            Inactivo
                                        </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?php if ($banner['starts_at']): ?>
                                            <div class="text-xs">Inicio: <?php echo date('d/m/Y', strtotime($banner['starts_at'])); ?></div>
                                        <?php endif; ?>
                                        <?php if ($banner['expires_at']): ?>
                                            <div class="text-xs">Fin: <?php echo date('d/m/Y', strtotime($banner['expires_at'])); ?></div>
                                        <?php endif; ?>
                                        <?php if (!$banner['starts_at'] && !$banner['expires_at']): ?>
                                            <span class="text-gray-400">Sin fechas</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm">
                                        <div class="flex items-center justify-end space-x-2">
                                            <button onclick="editBanner(<?php echo htmlspecialchars(json_encode($banner)); ?>)" 
                                                    class="inline-flex items-center p-1.5 text-primary-600 hover:text-primary-900 hover:bg-primary-50 rounded-lg transition-colors duration-200"
                                                    title="Editar banner">
                                                <i class="fas fa-edit text-sm"></i>
                                            </button>
                                            <button onclick="deleteBanner(<?php echo $banner['id']; ?>)" 
                                                    class="inline-flex items-center p-1.5 text-red-600 hover:text-red-900 hover:bg-red-50 rounded-lg transition-colors duration-200"
                                                    title="Eliminar banner">
                                                <i class="fas fa-trash text-sm"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                
                                <?php if (empty($banners)): ?>
                                <tr>
                                    <td colspan="7" class="px-6 py-12 text-center">
                                        <div class="flex flex-col items-center justify-center text-gray-500">
                                            <i class="fas fa-images text-4xl text-gray-300 mb-4"></i>
                                            <h3 class="text-lg font-medium text-gray-900 mb-1">No hay banners disponibles</h3>
                                            <p class="text-sm text-gray-500">Comienza agregando tu primer banner para el sitio web</p>
                                        </div>
                                    </td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </main>
        </div>
    </div>
    <!-- Modal para agregar/editar banner -->
    <div id="bannerModal" class="modal">
        <div class="bg-white rounded-lg shadow-xl max-w-md w-full mx-4 max-h-screen overflow-y-auto">
            <div class="flex justify-between items-center p-6 border-b">
                <h3 id="modalTitle" class="text-lg font-semibold text-gray-900">Agregar Banner</h3>
                <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <form id="bannerForm" method="POST" enctype="multipart/form-data" class="p-6">
                <input type="hidden" name="action" id="formAction" value="add">
                <input type="hidden" name="id" id="bannerId">
                
                <div class="mb-4">
                    <label for="image" class="block text-sm font-medium text-gray-700 mb-2">Imagen del Banner</label>
                    <input type="text" id="image" name="image" placeholder="URL de la imagen" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>

                <div class="mb-4">
                    <label for="image_file" class="block text-sm font-medium text-gray-700 mb-2">O subir imagen</label>
                    <input type="file" id="image_file" name="image_file" accept="image/*" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <div id="currentImage" class="mt-2 hidden">
                        <p class="text-sm text-gray-600">Imagen actual:</p>
                        <img id="currentImagePreview" src="" alt="Imagen actual" class="h-20 w-32 object-cover rounded-lg">
                    </div>
                </div>
                
                <div class="mb-4">
                    <label for="position" class="block text-sm font-medium text-gray-700 mb-2">Posición</label>
                    <select id="position" name="position" required 
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="hero">Hero/Principal</option>
                        <option value="category">Categoría</option>
                        <option value="product">Producto</option>
                        <option value="footer">Footer</option>
                    </select>
                </div>

                <div class="mb-4">
                    <label for="sort_order" class="block text-sm font-medium text-gray-700 mb-2">Orden de visualización</label>
                    <input type="number" id="sort_order" name="sort_order" value="0" min="0" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>

                <div class="mb-4">
                    <label for="starts_at" class="block text-sm font-medium text-gray-700 mb-2">Fecha de inicio (opcional)</label>
                    <input type="date" id="starts_at" name="starts_at" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>

                <div class="mb-4">
                    <label for="expires_at" class="block text-sm font-medium text-gray-700 mb-2">Fecha de expiración (opcional)</label>
                    <input type="date" id="expires_at" name="expires_at" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                
                <div class="mb-6">
                    <label class="flex items-center">
                        <span class="text-sm font-medium text-gray-700 mr-3">Estado:</span>
                        <label class="switch">
                            <input type="checkbox" id="is_active" name="is_active" checked>
                            <span class="slider"></span>
                        </label>
                        <span class="ml-2 text-sm text-gray-600">Activo</span>
                    </label>
                </div>
                
                <div class="flex justify-end space-x-3">
                    <button type="button" onclick="closeModal()" 
                            class="px-4 py-2 text-gray-700 bg-gray-200 hover:bg-gray-300 rounded-md transition-colors">
                        Cancelar
                    </button>
                    <button type="submit" id="submitBtn" 
                            class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-md transition-colors">
                        Guardar
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Modal para confirmar eliminación -->
    <div id="deleteModal" class="modal">
        <div class="bg-white rounded-lg shadow-xl max-w-md w-full mx-4">
            <div class="p-6">
                <div class="flex items-center mb-4">
                    <div class="flex-shrink-0">
                        <i class="fas fa-exclamation-triangle text-red-600 text-2xl"></i>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-lg font-medium text-gray-900">Confirmar Eliminación</h3>
                        <p class="text-sm text-gray-500">¿Estás seguro de que deseas eliminar este banner? Esta acción no se puede deshacer.</p>
                    </div>
                </div>
                
                <form method="POST">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" id="deleteId">
                    
                    <div class="flex justify-end space-x-3">
                        <button type="button" onclick="closeDeleteModal()" 
                                class="px-4 py-2 text-gray-700 bg-gray-200 hover:bg-gray-300 rounded-md transition-colors">
                            Cancelar
                        </button>
                        <button type="submit" 
                                class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-md transition-colors">
                            Eliminar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script>
        function openModal(action, banner = null) {
            const modal = document.getElementById('bannerModal');
            const form = document.getElementById('bannerForm');
            const modalTitle = document.getElementById('modalTitle');
            const submitBtn = document.getElementById('submitBtn');
            
            if (action === 'add') {
                modalTitle.textContent = 'Agregar Banner';
                submitBtn.textContent = 'Guardar';
                document.getElementById('formAction').value = 'add';
                form.reset();
                document.getElementById('is_active').checked = true;
                document.getElementById('currentImage').classList.add('hidden');
            } else if (action === 'edit' && banner) {
                modalTitle.textContent = 'Editar Banner';
                submitBtn.textContent = 'Actualizar';
                document.getElementById('formAction').value = 'edit';
                
                // Llenar campos con datos del banner
                document.getElementById('bannerId').value = banner.id;
                document.getElementById('image').value = banner.image || '';
                document.getElementById('position').value = banner.position;
                document.getElementById('sort_order').value = banner.sort_order || 0;
                document.getElementById('starts_at').value = banner.starts_at || '';
                document.getElementById('expires_at').value = banner.expires_at || '';
                document.getElementById('is_active').checked = banner.is_active == 1;
                
                // Mostrar imagen actual si existe
                if (banner.image) {
                    document.getElementById('currentImagePreview').src = '../' + banner.image;
                    document.getElementById('currentImage').classList.remove('hidden');
                } else {
                    document.getElementById('currentImage').classList.add('hidden');
                }
            }
            
            modal.classList.add('show');
        }
        
        function closeModal() {
            document.getElementById('bannerModal').classList.remove('show');
        }
        
        function editBanner(banner) {
            openModal('edit', banner);
        }
        
        function deleteBanner(id) {
            document.getElementById('deleteId').value = id;
            document.getElementById('deleteModal').classList.add('show');
        }
        
        function closeDeleteModal() {
            document.getElementById('deleteModal').classList.remove('show');
        }
        
        // Cerrar modal al hacer clic fuera
        document.addEventListener('click', function(event) {
            const bannerModal = document.getElementById('bannerModal');
            const deleteModal = document.getElementById('deleteModal');
            
            if (event.target === bannerModal) {
                closeModal();
            }
            if (event.target === deleteModal) {
                closeDeleteModal();
            }
        });
    </script>
</body>
</html>
