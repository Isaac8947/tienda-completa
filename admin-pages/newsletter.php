<?php
session_start();
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../admin/auth-check.php';

// Conectar a la base de datos
$db = new Database();
$conn = $db->getConnection();

// Filtros
$statusFilter = isset($_GET['status']) ? $_GET['status'] : '';
$searchTerm = isset($_GET['search']) ? trim($_GET['search']) : '';

// Construir consulta SQL
$sql = "SELECT * FROM newsletter_subscribers WHERE 1=1";
$params = [];

if ($statusFilter !== '') {
    $sql .= " AND is_active = ?";
    $params[] = $statusFilter;
}

if ($searchTerm) {
    $sql .= " AND (email LIKE ? OR name LIKE ?)";
    $params[] = "%$searchTerm%";
    $params[] = "%$searchTerm%";
}

$sql .= " ORDER BY subscribed_at DESC";

$stmt = $conn->prepare($sql);
$stmt->execute($params);
$subscribers = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Estadísticas
$statsStmt = $conn->prepare("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active,
        SUM(CASE WHEN is_active = 0 THEN 1 ELSE 0 END) as inactive,
        COUNT(CASE WHEN DATE(subscribed_at) = CURDATE() THEN 1 END) as today
    FROM newsletter_subscribers
");
$statsStmt->execute();
$stats = $statsStmt->fetch(PDO::FETCH_ASSOC);

// Procesar acciones
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'toggle_status':
                $subscriberId = $_POST['subscriber_id'];
                $newStatus = $_POST['new_status'];
                
                $updateStmt = $conn->prepare("UPDATE newsletter_subscribers SET is_active = ?, unsubscribed_at = ? WHERE id = ?");
                $unsubscribedAt = $newStatus == 0 ? date('Y-m-d H:i:s') : null;
                $updateStmt->execute([$newStatus, $unsubscribedAt, $subscriberId]);
                
                header('Location: newsletter.php?updated=1');
                exit;
                break;
                
            case 'delete':
                $subscriberId = $_POST['subscriber_id'];
                $deleteStmt = $conn->prepare("DELETE FROM newsletter_subscribers WHERE id = ?");
                $deleteStmt->execute([$subscriberId]);
                
                header('Location: newsletter.php?deleted=1');
                exit;
                break;
                
            case 'export':
                // Exportar suscriptores a CSV
                $exportStmt = $conn->prepare("SELECT email, name, is_active, subscribed_at FROM newsletter_subscribers WHERE is_active = 1 ORDER BY subscribed_at DESC");
                $exportStmt->execute();
                $exportData = $exportStmt->fetchAll(PDO::FETCH_ASSOC);
                
                header('Content-Type: text/csv');
                header('Content-Disposition: attachment; filename="newsletter_subscribers_' . date('Y-m-d') . '.csv"');
                
                $output = fopen('php://output', 'w');
                fputcsv($output, ['Email', 'Nombre', 'Estado', 'Fecha de Suscripción']);
                
                foreach ($exportData as $row) {
                    fputcsv($output, [
                        $row['email'],
                        $row['name'] ?: 'Sin nombre',
                        $row['is_active'] ? 'Activo' : 'Inactivo',
                        $row['subscribed_at']
                    ]);
                }
                
                fclose($output);
                exit;
                break;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Newsletter - Odisea Admin</title>
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
                        <h1 class="text-2xl lg:text-3xl font-bold text-gray-900">Gestión de Newsletter</h1>
                        <p class="text-gray-600 mt-1">Administra los suscriptores del newsletter</p>
                    </div>
                    <div class="flex flex-col sm:flex-row space-y-2 sm:space-y-0 sm:space-x-3">
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="action" value="export">
                            <button type="submit" class="inline-flex items-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-lg shadow-sm transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2">
                                <i class="fas fa-download mr-2"></i>
                                Exportar CSV
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Stats Cards -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                        <div class="flex items-center">
                            <div class="p-2 bg-blue-100 rounded-lg">
                                <i class="fas fa-users text-blue-600 text-xl"></i>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-600">Total Suscriptores</p>
                                <p class="text-2xl font-bold text-gray-900"><?php echo number_format($stats['total'] ?? 0); ?></p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                        <div class="flex items-center">
                            <div class="p-2 bg-green-100 rounded-lg">
                                <i class="fas fa-check-circle text-green-600 text-xl"></i>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-600">Activos</p>
                                <p class="text-2xl font-bold text-gray-900"><?php echo number_format($stats['active'] ?? 0); ?></p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                        <div class="flex items-center">
                            <div class="p-2 bg-red-100 rounded-lg">
                                <i class="fas fa-times-circle text-red-600 text-xl"></i>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-600">Inactivos</p>
                                <p class="text-2xl font-bold text-gray-900"><?php echo number_format($stats['inactive'] ?? 0); ?></p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                        <div class="flex items-center">
                            <div class="p-2 bg-yellow-100 rounded-lg">
                                <i class="fas fa-calendar-day text-yellow-600 text-xl"></i>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-600">Hoy</p>
                                <p class="text-2xl font-bold text-gray-900"><?php echo number_format($stats['today']); ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Success/Error Messages -->
                <?php if (isset($_GET['updated'])): ?>
                <div class="mb-6 p-4 rounded-lg bg-green-100 text-green-800">
                    <i class="fas fa-check-circle mr-2"></i>
                    Estado del suscriptor actualizado exitosamente.
                </div>
                <?php endif; ?>
                
                <?php if (isset($_GET['deleted'])): ?>
                <div class="mb-6 p-4 rounded-lg bg-green-100 text-green-800">
                    <i class="fas fa-check-circle mr-2"></i>
                    Suscriptor eliminado exitosamente.
                </div>
                <?php endif; ?>

                <!-- Filters -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
                    <form method="GET" class="flex flex-col lg:flex-row lg:items-end space-y-4 lg:space-y-0 lg:space-x-4">
                        <div class="flex-1">
                            <label for="search" class="block text-sm font-medium text-gray-700 mb-2">Buscar</label>
                            <input type="text" id="search" name="search" value="<?php echo htmlspecialchars($searchTerm); ?>" 
                                   placeholder="Buscar por email o nombre..." 
                                   class="block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 text-sm">
                        </div>
                        
                        <div class="w-full lg:w-48">
                            <label for="status" class="block text-sm font-medium text-gray-700 mb-2">Estado</label>
                            <select id="status" name="status" 
                                    class="block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 text-sm">
                                <option value="">Todos</option>
                                <option value="1" <?php echo $statusFilter === '1' ? 'selected' : ''; ?>>Activos</option>
                                <option value="0" <?php echo $statusFilter === '0' ? 'selected' : ''; ?>>Inactivos</option>
                            </select>
                        </div>
                        
                        <div class="flex space-x-2">
                            <button type="submit" class="inline-flex items-center px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white text-sm font-medium rounded-lg shadow-sm transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2">
                                <i class="fas fa-search mr-2"></i>
                                Filtrar
                            </button>
                            <a href="newsletter.php" class="inline-flex items-center px-4 py-2 bg-gray-300 hover:bg-gray-400 text-gray-700 text-sm font-medium rounded-lg shadow-sm transition-colors duration-200">
                                <i class="fas fa-times mr-2"></i>
                                Limpiar
                            </a>
                        </div>
                    </form>
                </div>

                <!-- Subscribers Table -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nombre</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Estado</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fecha de Suscripción</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fecha de Baja</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($subscribers as $subscriber): ?>
                                <tr class="hover:bg-gray-50 transition-colors duration-200">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div class="flex-shrink-0 h-8 w-8 bg-primary-100 rounded-full flex items-center justify-center">
                                                <i class="fas fa-envelope text-primary-600 text-sm"></i>
                                            </div>
                                            <div class="ml-3">
                                                <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($subscriber['email']); ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?php echo $subscriber['name'] ? htmlspecialchars($subscriber['name']) : '<span class="text-gray-400">Sin nombre</span>'; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <?php if ($subscriber['is_active']): ?>
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
                                        <?php echo date('d/m/Y H:i', strtotime($subscriber['subscribed_at'])); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?php if ($subscriber['unsubscribed_at']): ?>
                                            <?php echo date('d/m/Y H:i', strtotime($subscriber['unsubscribed_at'])); ?>
                                        <?php else: ?>
                                            <span class="text-gray-400">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm">
                                        <div class="flex items-center justify-end space-x-2">
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="action" value="toggle_status">
                                                <input type="hidden" name="subscriber_id" value="<?php echo $subscriber['id']; ?>">
                                                <input type="hidden" name="new_status" value="<?php echo $subscriber['is_active'] ? 0 : 1; ?>">
                                                <button type="submit" 
                                                        class="inline-flex items-center p-1.5 <?php echo $subscriber['is_active'] ? 'text-red-600 hover:text-red-900 hover:bg-red-50' : 'text-green-600 hover:text-green-900 hover:bg-green-50'; ?> rounded-lg transition-colors duration-200"
                                                        title="<?php echo $subscriber['is_active'] ? 'Desactivar' : 'Activar'; ?>">
                                                    <i class="fas <?php echo $subscriber['is_active'] ? 'fa-ban' : 'fa-check'; ?> text-sm"></i>
                                                </button>
                                            </form>
                                            <form method="POST" style="display: inline;" onsubmit="return confirm('¿Estás seguro de que deseas eliminar este suscriptor?')">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="subscriber_id" value="<?php echo $subscriber['id']; ?>">
                                                <button type="submit" 
                                                        class="inline-flex items-center p-1.5 text-red-600 hover:text-red-900 hover:bg-red-50 rounded-lg transition-colors duration-200"
                                                        title="Eliminar">
                                                    <i class="fas fa-trash text-sm"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                
                                <?php if (empty($subscribers)): ?>
                                <tr>
                                    <td colspan="6" class="px-6 py-12 text-center">
                                        <div class="flex flex-col items-center justify-center text-gray-500">
                                            <i class="fas fa-envelope text-4xl text-gray-300 mb-4"></i>
                                            <h3 class="text-lg font-medium text-gray-900 mb-1">No hay suscriptores</h3>
                                            <p class="text-sm text-gray-500">Los suscriptores del newsletter aparecerán aquí</p>
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
</body>
</html>
