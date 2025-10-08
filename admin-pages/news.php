<?php
session_start();
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../models/News.php';
require_once '../admin/auth-check.php';

$newsModel = new News();

// Obtener el ID del admin logueado (asumiendo que está en la sesión)
$adminId = $_SESSION['admin_id'] ?? 1; // Fallback para testing

// Filtros
$statusFilter = isset($_GET['status']) ? $_GET['status'] : '';
$categoryFilter = isset($_GET['category']) ? $_GET['category'] : '';
$searchTerm = isset($_GET['search']) ? trim($_GET['search']) : '';

// Obtener noticias
if ($searchTerm) {
    $news = $newsModel->searchNews($searchTerm, $statusFilter, $categoryFilter);
} else {
    $news = $newsModel->getAllWithAuthor($statusFilter);
    // Nota: La funcionalidad de categorías está deshabilitada ya que no existe la columna
    // if ($categoryFilter) {
    //     $news = array_filter($news, function($item) use ($categoryFilter) {
    //         return isset($item['category']) && $item['category'] === $categoryFilter;
    //     });
    // }
}

// Obtener estadísticas
$stats = $newsModel->getStats();

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add') {
        $title = trim($_POST['title']);
        $excerpt = trim($_POST['excerpt']);
        $content = trim($_POST['content']);
        // $category = $_POST['category']; // Campo no disponible en la tabla actual
        $status = $_POST['status'];
        $is_featured = isset($_POST['is_featured']) ? 1 : 0;
        $meta_title = trim($_POST['meta_title']);
        $meta_description = trim($_POST['meta_description']);
        // $tags = $_POST['tags'] ? json_encode(array_map('trim', explode(',', $_POST['tags']))) : null; // Campo no disponible
        
        // Generar slug
        $slug = $newsModel->generateSlug($title);
        
        // Procesar imagen
        $featured_image = '';
        if (isset($_FILES['featured_image']) && $_FILES['featured_image']['error'] === 0) {
            $uploadDir = '../uploads/news/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            $fileName = time() . '_' . basename($_FILES['featured_image']['name']);
            $targetFile = $uploadDir . $fileName;
            
            if (move_uploaded_file($_FILES['featured_image']['tmp_name'], $targetFile)) {
                $featured_image = 'uploads/news/' . $fileName;
            }
        }
        
        $published_at = $status === 'published' ? date('Y-m-d H:i:s') : null;
        
        $newsModel->create([
            'title' => $title,
            'slug' => $slug,
            'excerpt' => $excerpt,
            'content' => $content,
            'featured_image' => $featured_image,
            'author_id' => $adminId,
            // 'category' => $category, // Campo no disponible
            'status' => $status,
            'is_featured' => $is_featured,
            'meta_title' => $meta_title,
            'meta_description' => $meta_description,
            // 'tags' => $tags, // Campo no disponible
            'published_at' => $published_at
        ]);
        
        header('Location: news.php?success=1');
        exit;
    } elseif ($_POST['action'] === 'edit') {
        $id = $_POST['id'];
        $title = trim($_POST['title']);
        $excerpt = trim($_POST['excerpt']);
        $content = trim($_POST['content']);
        // $category = $_POST['category']; // Campo no disponible en la tabla actual
        $status = $_POST['status'];
        $is_featured = isset($_POST['is_featured']) ? 1 : 0;
        $meta_title = trim($_POST['meta_title']);
        $meta_description = trim($_POST['meta_description']);
        // $tags = $_POST['tags'] ? json_encode(array_map('trim', explode(',', $_POST['tags']))) : null; // Campo no disponible
        
        $currentNews = $newsModel->getById($id);
        if (!$currentNews) {
            header('Location: news.php?error=1');
            exit;
        }
        
        // Generar nuevo slug si el título cambió
        $slug = ($currentNews['title'] !== $title) ? 
               $newsModel->generateSlug($title, $id) : 
               $currentNews['slug'];
        
        $featured_image = $currentNews['featured_image'];
        
        // Procesar imagen si se subió una nueva
        if (isset($_FILES['featured_image']) && $_FILES['featured_image']['error'] === 0) {
            $uploadDir = '../uploads/news/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            $fileName = time() . '_' . basename($_FILES['featured_image']['name']);
            $targetFile = $uploadDir . $fileName;
            
            if (move_uploaded_file($_FILES['featured_image']['tmp_name'], $targetFile)) {
                // Eliminar imagen anterior si existe
                if (!empty($featured_image) && file_exists('../' . $featured_image)) {
                    unlink('../' . $featured_image);
                }
                $featured_image = 'uploads/news/' . $fileName;
            }
        }
        
        $published_at = $currentNews['published_at'];
        if ($status === 'published' && !$published_at) {
            $published_at = date('Y-m-d H:i:s');
        } elseif ($status !== 'published') {
            $published_at = null;
        }
        
        $newsModel->update($id, [
            'title' => $title,
            'slug' => $slug,
            'excerpt' => $excerpt,
            'content' => $content,
            'featured_image' => $featured_image,
            // 'category' => $category, // Campo no disponible
            'status' => $status,
            'is_featured' => $is_featured,
            'meta_title' => $meta_title,
            'meta_description' => $meta_description,
            // 'tags' => $tags, // Campo no disponible
            'published_at' => $published_at
        ]);
        
        header('Location: news.php?updated=1');
        exit;
    } elseif ($_POST['action'] === 'delete') {
        $id = $_POST['id'];
        $newsItem = $newsModel->getById($id);
        
        if ($newsItem) {
            // Eliminar imagen si existe
            if (!empty($newsItem['featured_image']) && file_exists('../' . $newsItem['featured_image'])) {
                unlink('../' . $newsItem['featured_image']);
            }
            
            $newsModel->delete($id);
        }
        
        header('Location: news.php?deleted=1');
        exit;
    } elseif ($_POST['action'] === 'toggle_featured') {
        $id = $_POST['id'];
        $newFeatured = $_POST['new_featured'];
        
        $newsModel->update($id, ['is_featured' => $newFeatured]);
        
        header('Location: news.php?updated=1');
        exit;
    }
}

// Obtener noticia para editar
$editNews = null;
if (isset($_GET['edit'])) {
    $editId = $_GET['edit'];
    $editNews = $newsModel->getById($editId);
}

// Definir categorías
$categories = [
    'beauty_tips' => 'Tips de Belleza',
    'product_reviews' => 'Reseñas de Productos',
    'trends' => 'Tendencias',
    'tutorials' => 'Tutoriales',
    'company_news' => 'Noticias de la Empresa'
];
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Noticias - Odisea Admin</title>
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
                        <h1 class="text-2xl lg:text-3xl font-bold text-gray-900">Gestión de Noticias</h1>
                        <p class="text-gray-600 mt-1">Administra el blog y noticias del sitio web</p>
                    </div>
                    <div class="flex flex-col sm:flex-row space-y-2 sm:space-y-0 sm:space-x-3">
                        <button onclick="openModal('add')" class="inline-flex items-center px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white text-sm font-medium rounded-lg shadow-sm transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2">
                            <i class="fas fa-plus mr-2"></i>
                            Nueva Noticia
                        </button>
                    </div>
                </div>

                <!-- Stats Cards -->
                <div class="grid grid-cols-1 md:grid-cols-5 gap-6 mb-8">
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                        <div class="flex items-center">
                            <div class="p-2 bg-blue-100 rounded-lg">
                                <i class="fas fa-newspaper text-blue-600 text-xl"></i>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-600">Total</p>
                                <p class="text-2xl font-bold text-gray-900"><?php echo number_format((int)($stats['total'] ?? 0)); ?></p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                        <div class="flex items-center">
                            <div class="p-2 bg-green-100 rounded-lg">
                                <i class="fas fa-check-circle text-green-600 text-xl"></i>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-600">Publicadas</p>
                                <p class="text-2xl font-bold text-gray-900"><?php echo number_format((int)($stats['published'] ?? 0)); ?></p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                        <div class="flex items-center">
                            <div class="p-2 bg-yellow-100 rounded-lg">
                                <i class="fas fa-edit text-yellow-600 text-xl"></i>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-600">Borradores</p>
                                <p class="text-2xl font-bold text-gray-900"><?php echo number_format((int)($stats['drafts'] ?? 0)); ?></p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                        <div class="flex items-center">
                            <div class="p-2 bg-purple-100 rounded-lg">
                                <i class="fas fa-star text-purple-600 text-xl"></i>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-600">Destacadas</p>
                                <p class="text-2xl font-bold text-gray-900"><?php echo number_format((int)($stats['featured'] ?? 0)); ?></p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                        <div class="flex items-center">
                            <div class="p-2 bg-indigo-100 rounded-lg">
                                <i class="fas fa-eye text-indigo-600 text-xl"></i>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-600">Visualizaciones</p>
                                <p class="text-2xl font-bold text-gray-900"><?php echo number_format((int)($stats['total_views'] ?? 0)); ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Success/Error Messages -->
                <?php if (isset($_GET['success'])): ?>
                <div class="mb-6 p-4 rounded-lg bg-green-100 text-green-800">
                    <i class="fas fa-check-circle mr-2"></i>
                    Noticia creada exitosamente.
                </div>
                <?php endif; ?>
                
                <?php if (isset($_GET['updated'])): ?>
                <div class="mb-6 p-4 rounded-lg bg-green-100 text-green-800">
                    <i class="fas fa-check-circle mr-2"></i>
                    Noticia actualizada exitosamente.
                </div>
                <?php endif; ?>
                
                <?php if (isset($_GET['deleted'])): ?>
                <div class="mb-6 p-4 rounded-lg bg-green-100 text-green-800">
                    <i class="fas fa-check-circle mr-2"></i>
                    Noticia eliminada exitosamente.
                </div>
                <?php endif; ?>

                <?php if (isset($_GET['error'])): ?>
                <div class="mb-6 p-4 rounded-lg bg-red-100 text-red-800">
                    <i class="fas fa-exclamation-circle mr-2"></i>
                    Ocurrió un error al procesar la solicitud.
                </div>
                <?php endif; ?>

                <!-- Filters -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
                    <form method="GET" class="flex flex-col lg:flex-row lg:items-end space-y-4 lg:space-y-0 lg:space-x-4">
                        <div class="flex-1">
                            <label for="search" class="block text-sm font-medium text-gray-700 mb-2">Buscar</label>
                            <input type="text" id="search" name="search" value="<?php echo htmlspecialchars($searchTerm); ?>" 
                                   placeholder="Buscar por título o contenido..." 
                                   class="block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 text-sm">
                        </div>
                        
                        <div class="w-full lg:w-48">
                            <label for="status" class="block text-sm font-medium text-gray-700 mb-2">Estado</label>
                            <select id="status" name="status" 
                                    class="block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 text-sm">
                                <option value="">Todos</option>
                                <option value="published" <?php echo $statusFilter === 'published' ? 'selected' : ''; ?>>Publicadas</option>
                                <option value="draft" <?php echo $statusFilter === 'draft' ? 'selected' : ''; ?>>Borradores</option>
                                <option value="archived" <?php echo $statusFilter === 'archived' ? 'selected' : ''; ?>>Archivadas</option>
                            </select>
                        </div>
                        
                        <!-- Categoría deshabilitada temporalmente
                        <div class="w-full lg:w-48">
                            <label for="category" class="block text-sm font-medium text-gray-700 mb-2">Categoría</label>
                            <select id="category" name="category" 
                                    class="block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 text-sm">
                                <option value="">Todas</option>
                                <?php foreach ($categories as $key => $label): ?>
                                <option value="<?php echo $key; ?>" <?php echo $categoryFilter === $key ? 'selected' : ''; ?>>
                                    <?php echo $label; ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        -->
                        
                        <div class="flex space-x-2">
                            <button type="submit" class="inline-flex items-center px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white text-sm font-medium rounded-lg shadow-sm transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2">
                                <i class="fas fa-search mr-2"></i>
                                Filtrar
                            </button>
                            <a href="news.php" class="inline-flex items-center px-4 py-2 bg-gray-300 hover:bg-gray-400 text-gray-700 text-sm font-medium rounded-lg shadow-sm transition-colors duration-200">
                                <i class="fas fa-times mr-2"></i>
                                Limpiar
                            </a>
                        </div>
                    </form>
                </div>

                <!-- News Table -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Noticia</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Categoría</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Autor</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Estado</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Vistas</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fecha</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($news as $article): ?>
                                <tr class="hover:bg-gray-50 transition-colors duration-200">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div class="flex-shrink-0 h-16 w-16">
                                                <?php if (!empty($article['featured_image'])): ?>
                                                <img src="../<?php echo htmlspecialchars($article['featured_image']); ?>" 
                                                     alt="<?php echo htmlspecialchars($article['title']); ?>" 
                                                     class="h-16 w-16 object-cover rounded-lg border border-gray-200">
                                                <?php else: ?>
                                                <div class="h-16 w-16 bg-gray-100 rounded-lg flex items-center justify-center border border-gray-200">
                                                    <i class="fas fa-newspaper text-gray-400 text-xl"></i>
                                                </div>
                                                <?php endif; ?>
                                            </div>
                                            <div class="ml-4">
                                                <div class="text-sm font-medium text-gray-900 line-clamp-2">
                                                    <?php echo htmlspecialchars($article['title']); ?>
                                                    <?php if ($article['is_featured']): ?>
                                                    <i class="fas fa-star text-yellow-500 ml-1" title="Destacada"></i>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="text-sm text-gray-500 line-clamp-1">
                                                    <?php echo htmlspecialchars(substr($article['excerpt'] ?: strip_tags($article['content']), 0, 100)) . '...'; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex px-2 py-1 text-xs font-medium bg-secondary-100 text-secondary-800 rounded-full">
                                            <?php echo isset($article['category']) ? ($categories[$article['category']] ?? $article['category']) : 'General'; ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?php echo htmlspecialchars($article['author_name'] ?? 'Admin'); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <?php
                                        $statusColors = [
                                            'published' => 'bg-green-100 text-green-800',
                                            'draft' => 'bg-yellow-100 text-yellow-800',
                                            'archived' => 'bg-gray-100 text-gray-800'
                                        ];
                                        $statusLabels = [
                                            'published' => 'Publicada',
                                            'draft' => 'Borrador',
                                            'archived' => 'Archivada'
                                        ];
                                        ?>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo $statusColors[$article['status']] ?? 'bg-gray-100 text-gray-800'; ?>">
                                            <?php echo $statusLabels[$article['status']] ?? $article['status']; ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <span class="inline-flex items-center">
                                            <i class="fas fa-eye text-gray-400 mr-1"></i>
                                            <?php echo number_format($article['views'] ?? 0); ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?php if ($article['published_at']): ?>
                                            <?php echo date('d/m/Y', strtotime($article['published_at'])); ?>
                                        <?php else: ?>
                                            <?php echo date('d/m/Y', strtotime($article['created_at'])); ?>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm">
                                        <div class="flex items-center justify-end space-x-2">
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="action" value="toggle_featured">
                                                <input type="hidden" name="id" value="<?php echo $article['id']; ?>">
                                                <input type="hidden" name="new_featured" value="<?php echo $article['is_featured'] ? 0 : 1; ?>">
                                                <button type="submit" 
                                                        class="inline-flex items-center p-1.5 <?php echo $article['is_featured'] ? 'text-yellow-600 hover:text-yellow-900 hover:bg-yellow-50' : 'text-gray-400 hover:text-yellow-600 hover:bg-yellow-50'; ?> rounded-lg transition-colors duration-200"
                                                        title="<?php echo $article['is_featured'] ? 'Quitar destacada' : 'Marcar como destacada'; ?>">
                                                    <i class="fas fa-star text-sm"></i>
                                                </button>
                                            </form>
                                            <button onclick="editNews(<?php echo htmlspecialchars(json_encode($article)); ?>)" 
                                                    class="inline-flex items-center p-1.5 text-primary-600 hover:text-primary-900 hover:bg-primary-50 rounded-lg transition-colors duration-200"
                                                    title="Editar noticia">
                                                <i class="fas fa-edit text-sm"></i>
                                            </button>
                                            <button onclick="deleteNews(<?php echo $article['id']; ?>)" 
                                                    class="inline-flex items-center p-1.5 text-red-600 hover:text-red-900 hover:bg-red-50 rounded-lg transition-colors duration-200"
                                                    title="Eliminar noticia">
                                                <i class="fas fa-trash text-sm"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                
                                <?php if (empty($news)): ?>
                                <tr>
                                    <td colspan="7" class="px-6 py-12 text-center">
                                        <div class="flex flex-col items-center justify-center text-gray-500">
                                            <i class="fas fa-newspaper text-4xl text-gray-300 mb-4"></i>
                                            <h3 class="text-lg font-medium text-gray-900 mb-1">No hay noticias disponibles</h3>
                                            <p class="text-sm text-gray-500">Comienza creando tu primera noticia</p>
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

    <!-- Modal para agregar/editar noticia -->
    <div id="newsModal" class="modal">
        <div class="bg-white rounded-lg shadow-xl max-w-4xl w-full mx-4 max-h-screen overflow-y-auto">
            <div class="flex justify-between items-center p-6 border-b">
                <h3 id="modalTitle" class="text-lg font-semibold text-gray-900">Agregar Noticia</h3>
                <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <form id="newsForm" method="POST" enctype="multipart/form-data" class="p-6">
                <input type="hidden" name="action" id="formAction" value="add">
                <input type="hidden" name="id" id="newsId">
                
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <div class="lg:col-span-2">
                        <label for="title" class="block text-sm font-medium text-gray-700 mb-2">Título *</label>
                        <input type="text" id="title" name="title" required 
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary-500">
                    </div>

                    <div class="lg:col-span-2">
                        <label for="excerpt" class="block text-sm font-medium text-gray-700 mb-2">Extracto</label>
                        <textarea id="excerpt" name="excerpt" rows="3" 
                                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary-500"
                                  placeholder="Breve descripción de la noticia..."></textarea>
                    </div>

                    <div class="lg:col-span-2">
                        <label for="content" class="block text-sm font-medium text-gray-700 mb-2">Contenido *</label>
                        <textarea id="content" name="content" rows="10" required 
                                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary-500"
                                  placeholder="Contenido completo de la noticia..."></textarea>
                    </div>

                    <div>
                        <label for="category" class="block text-sm font-medium text-gray-700 mb-2">Categoría *</label>
                        <select id="category" name="category" required 
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary-500">
                            <?php foreach ($categories as $key => $label): ?>
                            <option value="<?php echo $key; ?>"><?php echo $label; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label for="status" class="block text-sm font-medium text-gray-700 mb-2">Estado *</label>
                        <select id="status" name="status" required 
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary-500">
                            <option value="draft">Borrador</option>
                            <option value="published">Publicada</option>
                            <option value="archived">Archivada</option>
                        </select>
                    </div>

                    <div>
                        <label for="featured_image" class="block text-sm font-medium text-gray-700 mb-2">Imagen destacada</label>
                        <input type="file" id="featured_image" name="featured_image" accept="image/*" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary-500">
                        <div id="currentImage" class="mt-2 hidden">
                            <p class="text-sm text-gray-600">Imagen actual:</p>
                            <img id="currentImagePreview" src="" alt="Imagen actual" class="h-20 w-32 object-cover rounded-lg">
                        </div>
                    </div>

                    <div>
                        <label for="tags" class="block text-sm font-medium text-gray-700 mb-2">Tags</label>
                        <input type="text" id="tags" name="tags" 
                               placeholder="belleza, maquillaje, tips (separados por comas)"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary-500">
                    </div>

                    <div>
                        <label for="meta_title" class="block text-sm font-medium text-gray-700 mb-2">Meta título (SEO)</label>
                        <input type="text" id="meta_title" name="meta_title" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary-500">
                    </div>

                    <div>
                        <label for="meta_description" class="block text-sm font-medium text-gray-700 mb-2">Meta descripción (SEO)</label>
                        <textarea id="meta_description" name="meta_description" rows="2" 
                                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary-500"></textarea>
                    </div>

                    <div class="lg:col-span-2">
                        <label class="flex items-center">
                            <input type="checkbox" id="is_featured" name="is_featured" 
                                   class="rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                            <span class="ml-2 text-sm text-gray-700">Marcar como noticia destacada</span>
                        </label>
                    </div>
                </div>
                
                <div class="flex justify-end space-x-3 mt-6 pt-6 border-t">
                    <button type="button" onclick="closeModal()" 
                            class="px-4 py-2 text-gray-700 bg-gray-200 hover:bg-gray-300 rounded-md transition-colors">
                        Cancelar
                    </button>
                    <button type="submit" id="submitBtn" 
                            class="px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-md transition-colors">
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
                        <p class="text-sm text-gray-500">¿Estás seguro de que deseas eliminar esta noticia? Esta acción no se puede deshacer.</p>
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
        function openModal(action, news = null) {
            const modal = document.getElementById('newsModal');
            const form = document.getElementById('newsForm');
            const modalTitle = document.getElementById('modalTitle');
            const submitBtn = document.getElementById('submitBtn');
            
            if (action === 'add') {
                modalTitle.textContent = 'Agregar Noticia';
                submitBtn.textContent = 'Guardar';
                document.getElementById('formAction').value = 'add';
                form.reset();
                document.getElementById('currentImage').classList.add('hidden');
            } else if (action === 'edit' && news) {
                modalTitle.textContent = 'Editar Noticia';
                submitBtn.textContent = 'Actualizar';
                document.getElementById('formAction').value = 'edit';
                
                // Llenar campos con datos de la noticia
                document.getElementById('newsId').value = news.id;
                document.getElementById('title').value = news.title;
                document.getElementById('excerpt').value = news.excerpt || '';
                document.getElementById('content').value = news.content;
                document.getElementById('category').value = news.category;
                document.getElementById('status').value = news.status;
                document.getElementById('meta_title').value = news.meta_title || '';
                document.getElementById('meta_description').value = news.meta_description || '';
                document.getElementById('is_featured').checked = news.is_featured == 1;
                
                // Tags
                if (news.tags) {
                    try {
                        const tags = JSON.parse(news.tags);
                        document.getElementById('tags').value = tags.join(', ');
                    } catch (e) {
                        document.getElementById('tags').value = '';
                    }
                }
                
                // Mostrar imagen actual si existe
                if (news.featured_image) {
                    document.getElementById('currentImagePreview').src = '../' + news.featured_image;
                    document.getElementById('currentImage').classList.remove('hidden');
                } else {
                    document.getElementById('currentImage').classList.add('hidden');
                }
            }
            
            modal.classList.add('show');
        }
        
        function closeModal() {
            document.getElementById('newsModal').classList.remove('show');
        }
        
        function editNews(news) {
            openModal('edit', news);
        }
        
        function deleteNews(id) {
            document.getElementById('deleteId').value = id;
            document.getElementById('deleteModal').classList.add('show');
        }
        
        function closeDeleteModal() {
            document.getElementById('deleteModal').classList.remove('show');
        }
        
        // Cerrar modal al hacer clic fuera
        document.addEventListener('click', function(event) {
            const newsModal = document.getElementById('newsModal');
            const deleteModal = document.getElementById('deleteModal');
            
            if (event.target === newsModal) {
                closeModal();
            }
            if (event.target === deleteModal) {
                closeDeleteModal();
            }
        });

        <?php if ($editNews): ?>
        // Mostrar modal de edición si hay una noticia para editar
        openModal('edit', <?php echo json_encode($editNews); ?>);
        <?php endif; ?>
    </script>
</body>
</html>
