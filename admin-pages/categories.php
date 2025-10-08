<?php
session_start();
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../models/Category.php';
require_once '../admin/auth-check.php';

$categoryModel = new Category();
$categories = $categoryModel->getAll();

// Procesar formulario de agregar categoría
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add') {
        $name = $_POST['name'];
        $description = $_POST['description'];
        $slug = strtolower(str_replace(' ', '-', $name));
        $isActive = isset($_POST['status']) ? 1 : 0;
        
        // Procesar imagen si se subió
        $image = '';
        if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
            $uploadDir = '../uploads/categories/';
            // Crear directorio si no existe
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            $fileName = time() . '_' . basename($_FILES['image']['name']);
            $targetFile = $uploadDir . $fileName;
            
            if (move_uploaded_file($_FILES['image']['tmp_name'], $targetFile)) {
                $image = 'uploads/categories/' . $fileName;
            }
        }
        
        $categoryModel->create([
            'name' => $name,
            'description' => $description,
            'slug' => $slug,
            'image' => $image,
            'is_active' => $isActive
        ]);
        
        header('Location: categories.php?success=1');
        exit;
    } elseif ($_POST['action'] === 'edit') {
        $id = $_POST['id'];
        $name = $_POST['name'];
        $description = $_POST['description'];
        $slug = strtolower(str_replace(' ', '-', $name));
        $isActive = isset($_POST['status']) ? 1 : 0;
        
        $currentCategory = $categoryModel->getById($id);
        $image = $currentCategory['image'];
        
        // Procesar imagen si se subió una nueva
        if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
            $uploadDir = '../uploads/categories/';
            // Crear directorio si no existe
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            $fileName = time() . '_' . basename($_FILES['image']['name']);
            $targetFile = $uploadDir . $fileName;
            
            if (move_uploaded_file($_FILES['image']['tmp_name'], $targetFile)) {
                // Eliminar imagen anterior si existe
                if (!empty($image) && file_exists('../' . $image)) {
                    unlink('../' . $image);
                }
                $image = 'uploads/categories/' . $fileName;
            }
        }
        
        $categoryModel->update($id, [
            'name' => $name,
            'description' => $description,
            'slug' => $slug,
            'image' => $image,
            'is_active' => $isActive
        ]);
        
        header('Location: categories.php?updated=1');
        exit;
    } elseif ($_POST['action'] === 'delete') {
        $id = $_POST['id'];
        $category = $categoryModel->getById($id);
        
        // Eliminar imagen si existe
        if (!empty($category['image']) && file_exists('../' . $category['image'])) {
            unlink('../' . $category['image']);
        }
        
        $categoryModel->delete($id);
        
        header('Location: categories.php?deleted=1');
        exit;
    }
}

// Obtener categoría para editar
$editCategory = null;
if (isset($_GET['edit'])) {
    $editId = $_GET['edit'];
    $editCategory = $categoryModel->getById($editId);
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Categorías - Odisea Admin</title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Custom Admin CSS -->
    <link rel="stylesheet" href="../admin/assets/css/admin.css">
    
    <!-- Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
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
</head>
<body class="bg-gray-50">
    <div class="flex h-screen overflow-hidden">
        <!-- Sidebar -->
        <?php include '../admin/includes/sidebar.php'; ?>
        
        <!-- Main Content -->
        <div class="flex-1 flex flex-col overflow-hidden">
            <!-- Top Header -->
            <?php include '../admin/includes/header.php'; ?>
            
            <!-- Categories Content -->
            <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-50 p-4 lg:p-6">
                <!-- Page Header -->
                <div class="mb-8">
                    <h1 class="text-2xl lg:text-3xl font-bold text-gray-900 mb-2">Gestión de Categorías</h1>
                    <p class="text-gray-600">Administra las categorías de productos de tu tienda</p>
                </div>

                <!-- Success Messages -->
                <?php if (isset($_GET['success'])): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
                    <i class="fas fa-check-circle mr-2"></i>
                    Categoría agregada exitosamente.
                </div>
                <?php endif; ?>
                
                <?php if (isset($_GET['updated'])): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
                    <i class="fas fa-check-circle mr-2"></i>
                    Categoría actualizada exitosamente.
                </div>
                <?php endif; ?>
                
                <?php if (isset($_GET['deleted'])): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
                    <i class="fas fa-check-circle mr-2"></i>
                    Categoría eliminada exitosamente.
                </div>
                <?php endif; ?>

                <!-- Categories Table -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100">
                    <div class="p-6 border-b border-gray-100">
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
                            <h2 class="text-lg font-semibold text-gray-900 mb-4 sm:mb-0">Lista de Categorías</h2>
                            <button class="bg-primary-500 hover:bg-primary-600 text-white px-4 py-2 rounded-lg font-medium transition-colors duration-200" id="addCategoryBtn">
                                <i class="fas fa-plus mr-2"></i>
                                Agregar Categoría
                            </button>
                        </div>
                    </div>
                    
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-50 border-b border-gray-100">
                                <tr>
                                    <th class="text-left py-3 px-6 text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                                    <th class="text-left py-3 px-6 text-xs font-medium text-gray-500 uppercase tracking-wider">Imagen</th>
                                    <th class="text-left py-3 px-6 text-xs font-medium text-gray-500 uppercase tracking-wider">Nombre</th>
                                    <th class="text-left py-3 px-6 text-xs font-medium text-gray-500 uppercase tracking-wider">Slug</th>
                                    <th class="text-left py-3 px-6 text-xs font-medium text-gray-500 uppercase tracking-wider">Estado</th>
                                    <th class="text-left py-3 px-6 text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <?php foreach ($categories as $category): ?>
                                <tr class="hover:bg-gray-50 transition-colors duration-200">
                                    <td class="py-4 px-6 text-sm font-medium text-gray-900"><?php echo $category['id']; ?></td>
                                    <td class="py-4 px-6">
                                        <?php if (!empty($category['image'])): ?>
                                        <img src="<?php echo BASE_URL . '/' . $category['image']; ?>" alt="<?php echo $category['name']; ?>" class="w-12 h-12 rounded-lg object-cover">
                                        <?php else: ?>
                                        <div class="w-12 h-12 bg-gray-200 rounded-lg flex items-center justify-center">
                                            <i class="fas fa-image text-gray-400"></i>
                                        </div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="py-4 px-6 text-sm font-medium text-gray-900"><?php echo htmlspecialchars($category['name']); ?></td>
                                    <td class="py-4 px-6 text-sm text-gray-600 font-mono"><?php echo htmlspecialchars($category['slug']); ?></td>
                                    <td class="py-4 px-6">
                                        <?php if ($category['is_active'] == 1): ?>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                            <i class="fas fa-check mr-1"></i>
                                            Activo
                                        </span>
                                        <?php else: ?>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                            <i class="fas fa-times mr-1"></i>
                                            Inactivo
                                        </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="py-4 px-6">
                                        <div class="flex space-x-2">
                                            <a href="?edit=<?php echo $category['id']; ?>" class="bg-blue-500 hover:bg-blue-600 text-white px-3 py-1.5 rounded text-sm font-medium transition-colors duration-200">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <button class="bg-red-500 hover:bg-red-600 text-white px-3 py-1.5 rounded text-sm font-medium transition-colors duration-200 delete-btn" data-id="<?php echo $category['id']; ?>">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </main>
        </div>
    </div>
    
    <!-- Modal para agregar/editar categoría -->
    <div class="fixed inset-0 z-50 overflow-y-auto hidden" id="categoryModal">
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 transition-opacity bg-gray-500 bg-opacity-75" id="categoryModalOverlay"></div>
            
            <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-medium text-gray-900" id="modalTitle">
                            <?php echo $editCategory ? 'Editar Categoría' : 'Agregar Categoría'; ?>
                        </h3>
                        <button type="button" class="text-gray-400 hover:text-gray-600 close-modal">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    
                    <form method="POST" enctype="multipart/form-data" class="space-y-4">
                        <input type="hidden" name="action" value="<?php echo $editCategory ? 'edit' : 'add'; ?>">
                        <?php if ($editCategory): ?>
                        <input type="hidden" name="id" value="<?php echo $editCategory['id']; ?>">
                        <?php endif; ?>
                        
                        <div>
                            <label for="name" class="block text-sm font-medium text-gray-700 mb-2">Nombre</label>
                            <input type="text" id="name" name="name" value="<?php echo $editCategory ? htmlspecialchars($editCategory['name']) : ''; ?>" required 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                        </div>
                        
                        <div>
                            <label for="description" class="block text-sm font-medium text-gray-700 mb-2">Descripción</label>
                            <textarea id="description" name="description" rows="3" 
                                      class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent"><?php echo $editCategory ? htmlspecialchars($editCategory['description']) : ''; ?></textarea>
                        </div>
                        
                        <div>
                            <label for="image" class="block text-sm font-medium text-gray-700 mb-2">Imagen</label>
                            <input type="file" id="image" name="image" accept="image/*" 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                            <?php if ($editCategory && !empty($editCategory['image'])): ?>
                            <div class="mt-2">
                                <p class="text-sm text-gray-600 mb-2">Imagen actual:</p>
                                <img src="<?php echo BASE_URL . '/' . $editCategory['image']; ?>" alt="<?php echo $editCategory['name']; ?>" class="w-20 h-20 rounded-lg object-cover">
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="flex items-center">
                            <input type="checkbox" id="status" name="status" <?php echo ($editCategory && $editCategory['is_active'] == 1) ? 'checked' : ''; ?> 
                                   class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded">
                            <label for="status" class="ml-2 block text-sm text-gray-900">Categoría activa</label>
                        </div>
                        
                        <div class="flex justify-end space-x-3 pt-4 border-t border-gray-200">
                            <button type="button" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 close-modal">
                                Cancelar
                            </button>
                            <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-primary-600 border border-transparent rounded-md hover:bg-primary-700">
                                <?php echo $editCategory ? 'Actualizar' : 'Guardar'; ?>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Modal para confirmar eliminación -->
    <div class="fixed inset-0 z-50 overflow-y-auto hidden" id="deleteModal">
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 transition-opacity bg-gray-500 bg-opacity-75" id="deleteModalOverlay"></div>
            
            <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-md sm:w-full">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="flex items-center mb-4">
                        <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100 sm:mx-0 sm:h-10 sm:w-10">
                            <i class="fas fa-exclamation-triangle text-red-600"></i>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-lg font-medium text-gray-900">Confirmar Eliminación</h3>
                        </div>
                    </div>
                    
                    <p class="text-sm text-gray-500 mb-4">
                        ¿Estás seguro de que deseas eliminar esta categoría? Esta acción no se puede deshacer.
                    </p>
                    
                    <form method="POST">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" id="deleteId">
                        
                        <div class="flex justify-end space-x-3">
                            <button type="button" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50" id="cancelDelete">
                                Cancelar
                            </button>
                            <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-red-600 border border-transparent rounded-md hover:bg-red-700">
                                Eliminar
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const categoryModal = document.getElementById('categoryModal');
            const deleteModal = document.getElementById('deleteModal');
            const addCategoryBtn = document.getElementById('addCategoryBtn');
            const closeButtons = document.querySelectorAll('.close-modal');
            const deleteButtons = document.querySelectorAll('.delete-btn');
            const cancelDelete = document.getElementById('cancelDelete');
            const categoryModalOverlay = document.getElementById('categoryModalOverlay');
            const deleteModalOverlay = document.getElementById('deleteModalOverlay');
            
            // Mostrar modal para agregar categoría
            addCategoryBtn.addEventListener('click', function() {
                document.getElementById('modalTitle').textContent = 'Agregar Categoría';
                document.querySelector('#categoryModal form input[name="action"]').value = 'add';
                document.querySelector('#categoryModal form').reset();
                categoryModal.classList.remove('hidden');
            });
            
            // Cerrar modales
            closeButtons.forEach(function(btn) {
                btn.addEventListener('click', function() {
                    categoryModal.classList.add('hidden');
                    deleteModal.classList.add('hidden');
                });
            });
            
            // Cerrar modal al hacer clic en overlay
            categoryModalOverlay.addEventListener('click', function() {
                categoryModal.classList.add('hidden');
            });
            
            deleteModalOverlay.addEventListener('click', function() {
                deleteModal.classList.add('hidden');
            });
            
            // Mostrar modal para confirmar eliminación
            deleteButtons.forEach(function(btn) {
                btn.addEventListener('click', function() {
                    const id = this.getAttribute('data-id');
                    document.getElementById('deleteId').value = id;
                    deleteModal.classList.remove('hidden');
                });
            });
            
            // Cancelar eliminación
            cancelDelete.addEventListener('click', function() {
                deleteModal.classList.add('hidden');
            });
            
            <?php if ($editCategory): ?>
            // Mostrar modal de edición si hay una categoría para editar
            categoryModal.classList.remove('hidden');
            <?php endif; ?>
        });
    </script>
</body>
</html>
