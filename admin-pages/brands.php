<?php
session_start();
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../models/Brand.php';
require_once '../admin/auth-check.php';

$brandModel = new Brand();
$brands = $brandModel->getAll();

// Procesar formulario de agregar marca
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add') {
        $name = $_POST['name'];
        $description = $_POST['description'];
        $slug = strtolower(str_replace(' ', '-', $name));
        $isActive = isset($_POST['status']) ? 1 : 0;
        
        // Procesar logo si se subió
        $logo = '';
        if (isset($_FILES['logo']) && $_FILES['logo']['error'] === 0) {
            $uploadDir = '../assets/images/brands/';
            $fileName = time() . '_' . basename($_FILES['logo']['name']);
            $targetFile = $uploadDir . $fileName;
            
            if (move_uploaded_file($_FILES['logo']['tmp_name'], $targetFile)) {
                $logo = 'assets/images/brands/' . $fileName;
            }
        }
        
        $brandModel->create([
            'name' => $name,
            'description' => $description,
            'slug' => $slug,
            'logo' => $logo,
            'is_active' => $isActive
        ]);
        
        header('Location: brands.php?success=1');
        exit;
    } elseif ($_POST['action'] === 'edit') {
        $id = $_POST['id'];
        $name = $_POST['name'];
        $description = $_POST['description'];
        $slug = strtolower(str_replace(' ', '-', $name));
        $isActive = isset($_POST['status']) ? 1 : 0;
        
        $currentBrand = $brandModel->getById($id);
        $logo = $currentBrand['logo'];
        
        // Procesar logo si se subió uno nuevo
        if (isset($_FILES['logo']) && $_FILES['logo']['error'] === 0) {
            $uploadDir = '../assets/images/brands/';
            $fileName = time() . '_' . basename($_FILES['logo']['name']);
            $targetFile = $uploadDir . $fileName;
            
            if (move_uploaded_file($_FILES['logo']['tmp_name'], $targetFile)) {
                // Eliminar logo anterior si existe
                if (!empty($logo) && file_exists('../' . $logo)) {
                    unlink('../' . $logo);
                }
                $logo = 'assets/images/brands/' . $fileName;
            }
        }
        
        $brandModel->update($id, [
            'name' => $name,
            'description' => $description,
            'slug' => $slug,
            'logo' => $logo,
            'is_active' => $isActive
        ]);
        
        header('Location: brands.php?updated=1');
        exit;
    } elseif ($_POST['action'] === 'delete') {
        $id = $_POST['id'];
        $brand = $brandModel->getById($id);
        
        // Eliminar logo si existe
        if (!empty($brand['logo']) && file_exists('../' . $brand['logo'])) {
            unlink('../' . $brand['logo']);
        }
        
        $brandModel->delete($id);
        
        header('Location: brands.php?deleted=1');
        exit;
    }
}

// Obtener marca para editar
$editBrand = null;
if (isset($_GET['edit'])) {
    $editId = $_GET['edit'];
    $editBrand = $brandModel->getById($editId);
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Marcas - Odisea Admin</title>
    
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
                        <h1 class="text-2xl lg:text-3xl font-bold text-gray-900">Marcas</h1>
                        <p class="text-gray-600 mt-1">Gestiona las marcas de productos</p>
                    </div>
                    <div class="flex flex-col sm:flex-row space-y-2 sm:space-y-0 sm:space-x-3">
                        <button onclick="openAddModal()" class="bg-gradient-to-r from-primary-500 to-primary-600 text-white px-6 py-2 rounded-lg hover:from-primary-600 hover:to-primary-700 transition-all duration-200 flex items-center justify-center">
                            <i class="fas fa-plus mr-2"></i>
                            Agregar Marca
                        </button>
                    </div>
                </div>

                <!-- Success/Error Messages -->
                <?php if (isset($_GET['success'])): ?>
                <div class="mb-6 p-4 rounded-lg bg-green-100 text-green-800">
                    <i class="fas fa-check-circle mr-2"></i>
                    Marca agregada exitosamente.
                </div>
                <?php endif; ?>
                
                <?php if (isset($_GET['updated'])): ?>
                <div class="mb-6 p-4 rounded-lg bg-green-100 text-green-800">
                    <i class="fas fa-check-circle mr-2"></i>
                    Marca actualizada exitosamente.
                </div>
                <?php endif; ?>
                
                <?php if (isset($_GET['deleted'])): ?>
                <div class="mb-6 p-4 rounded-lg bg-green-100 text-green-800">
                    <i class="fas fa-check-circle mr-2"></i>
                    Marca eliminada exitosamente.
                </div>
                <?php endif; ?>

                <!-- Brands Table -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                    <!-- Table Header -->
                    <div class="px-4 lg:px-6 py-4 border-b border-gray-200 bg-gray-50">
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
                            <h3 class="text-lg font-semibold text-gray-900 mb-2 sm:mb-0">
                                <?php echo count($brands); ?> marcas registradas
                            </h3>
                        </div>
                    </div>

                    <!-- Desktop Table View -->
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Logo</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Marca</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Slug</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Estado</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($brands as $brand): ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="w-12 h-12 bg-gray-100 rounded-lg overflow-hidden flex-shrink-0 flex items-center justify-center">
                                            <?php if (!empty($brand['logo'])): ?>
                                                <img src="../<?php echo htmlspecialchars($brand['logo']); ?>" alt="<?php echo htmlspecialchars($brand['name']); ?>" class="w-full h-full object-cover">
                                            <?php else: ?>
                                                <i class="fas fa-image text-gray-400"></i>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div>
                                            <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($brand['name']); ?></div>
                                            <?php if (!empty($brand['description'])): ?>
                                                <div class="text-sm text-gray-500"><?php echo htmlspecialchars(substr($brand['description'], 0, 50)); ?><?php echo strlen($brand['description']) > 50 ? '...' : ''; ?></div>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 font-mono"><?php echo htmlspecialchars($brand['slug']); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                            <?php echo $brand['is_active'] == 1 ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                            <i class="fas <?php echo $brand['is_active'] == 1 ? 'fa-check' : 'fa-times'; ?> mr-1"></i>
                                            <?php echo $brand['is_active'] == 1 ? 'Activo' : 'Inactivo'; ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <div class="flex items-center space-x-3">
                                            <button onclick="openEditModal(<?php echo htmlspecialchars(json_encode($brand)); ?>)" class="text-primary-600 hover:text-primary-900" title="Editar">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button onclick="openDeleteModal(<?php echo $brand['id']; ?>, '<?php echo htmlspecialchars($brand['name']); ?>')" class="text-red-600 hover:text-red-900" title="Eliminar">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Empty State -->
                    <?php if (empty($brands)): ?>
                    <div class="text-center py-12">
                        <div class="w-24 h-24 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                            <i class="fas fa-tags text-gray-400 text-3xl"></i>
                        </div>
                        <h3 class="text-lg font-semibold text-gray-900 mb-2">No hay marcas registradas</h3>
                        <p class="text-gray-600 mb-6">Comienza agregando la primera marca para tus productos.</p>
                        <button onclick="openAddModal()" class="bg-primary-500 text-white px-6 py-2 rounded-lg hover:bg-primary-600 transition-colors duration-200">
                            <i class="fas fa-plus mr-2"></i>
                            Agregar Primera Marca
                        </button>
                    </div>
                    <?php endif; ?>
                </div>
            </main>
        </div>
    </div>
    
    <!-- Add/Edit Brand Modal -->
    <div class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden" id="brandModal">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="bg-white rounded-lg shadow-xl max-w-md w-full p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900" id="modalTitle">Agregar Marca</h3>
                    <button onclick="closeBrandModal()" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                
                <form method="POST" enctype="multipart/form-data" id="brandForm">
                    <input type="hidden" name="action" id="formAction" value="add">
                    <input type="hidden" name="id" id="brandId">
                    
                    <div class="space-y-4">
                        <div>
                            <label for="name" class="block text-sm font-medium text-gray-700 mb-2">Nombre *</label>
                            <input type="text" id="name" name="name" required
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                        </div>
                        
                        <div>
                            <label for="description" class="block text-sm font-medium text-gray-700 mb-2">Descripción</label>
                            <textarea id="description" name="description" rows="3"
                                      class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent"></textarea>
                        </div>
                        
                        <div>
                            <label for="logo" class="block text-sm font-medium text-gray-700 mb-2">Logo</label>
                            <input type="file" id="logo" name="logo" accept="image/*"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                            <div id="currentLogo" class="mt-2 hidden">
                                <p class="text-sm text-gray-600 mb-2">Logo actual:</p>
                                <img id="currentLogoImg" src="" alt="" class="w-20 h-20 object-cover rounded-lg">
                            </div>
                        </div>
                        
                        <div class="flex items-center">
                            <input type="checkbox" id="status" name="status" value="1"
                                   class="rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                            <label for="status" class="ml-2 text-sm text-gray-700">Marca activa</label>
                        </div>
                    </div>
                    
                    <div class="flex space-x-3 mt-6">
                        <button type="button" onclick="closeBrandModal()" class="flex-1 bg-gray-100 text-gray-700 py-2 px-4 rounded-lg hover:bg-gray-200 transition-colors duration-200">
                            Cancelar
                        </button>
                        <button type="submit" class="flex-1 bg-primary-500 text-white py-2 px-4 rounded-lg hover:bg-primary-600 transition-colors duration-200">
                            <span id="submitText">Guardar</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden" id="deleteModal">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="bg-white rounded-lg shadow-xl max-w-md w-full p-6">
                <div class="flex items-center justify-center w-12 h-12 mx-auto bg-red-100 rounded-full mb-4">
                    <i class="fas fa-exclamation-triangle text-red-600 text-xl"></i>
                </div>
                <h3 class="text-lg font-semibold text-gray-900 text-center mb-2">¿Eliminar marca?</h3>
                <p class="text-gray-600 text-center mb-6">¿Estás seguro de que deseas eliminar la marca "<span id="deleteBrandName"></span>"? Esta acción no se puede deshacer.</p>
                
                <form method="POST">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" id="deleteId">
                    
                    <div class="flex space-x-3">
                        <button type="button" onclick="closeDeleteModal()" class="flex-1 bg-gray-100 text-gray-700 py-2 px-4 rounded-lg hover:bg-gray-200 transition-colors duration-200">
                            Cancelar
                        </button>
                        <button type="submit" class="flex-1 bg-red-500 text-white py-2 px-4 rounded-lg hover:bg-red-600 transition-colors duration-200">
                            Eliminar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="../admin/assets/js/admin.js"></script>
    <script>
        function openAddModal() {
            document.getElementById('modalTitle').textContent = 'Agregar Marca';
            document.getElementById('formAction').value = 'add';
            document.getElementById('submitText').textContent = 'Guardar';
            document.getElementById('brandForm').reset();
            document.getElementById('brandId').value = '';
            document.getElementById('currentLogo').classList.add('hidden');
            document.getElementById('brandModal').classList.remove('hidden');
        }

        function openEditModal(brand) {
            document.getElementById('modalTitle').textContent = 'Editar Marca';
            document.getElementById('formAction').value = 'edit';
            document.getElementById('submitText').textContent = 'Actualizar';
            document.getElementById('brandId').value = brand.id;
            document.getElementById('name').value = brand.name;
            document.getElementById('description').value = brand.description || '';
            document.getElementById('status').checked = brand.is_active == 1;
            
            if (brand.logo) {
                document.getElementById('currentLogo').classList.remove('hidden');
                document.getElementById('currentLogoImg').src = '../' + brand.logo;
                document.getElementById('currentLogoImg').alt = brand.name;
            } else {
                document.getElementById('currentLogo').classList.add('hidden');
            }
            
            document.getElementById('brandModal').classList.remove('hidden');
        }

        function closeBrandModal() {
            document.getElementById('brandModal').classList.add('hidden');
        }

        function openDeleteModal(id, name) {
            document.getElementById('deleteId').value = id;
            document.getElementById('deleteBrandName').textContent = name;
            document.getElementById('deleteModal').classList.remove('hidden');
        }

        function closeDeleteModal() {
            document.getElementById('deleteModal').classList.add('hidden');
        }

        // Close modals when clicking outside
        document.getElementById('brandModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeBrandModal();
            }
        });

        document.getElementById('deleteModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeDeleteModal();
            }
        });

        <?php if ($editBrand): ?>
        // Show edit modal if there's a brand to edit
        openEditModal(<?php echo json_encode($editBrand); ?>);
        <?php endif; ?>
    </script>
</body>
</html>
