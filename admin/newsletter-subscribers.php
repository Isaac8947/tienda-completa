<?php
session_start();
require_once '../config/database.php';
require_once '../config/global-settings.php';

// Simple authentication check (you should implement proper admin authentication)
if (!isset($_SESSION['admin_user']) && !isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

$database = new Database();
$db = $database->getConnection();

// Obtener estadísticas
$stats = [];
try {
    // Total subscribers
    $totalQuery = "SELECT COUNT(*) as total FROM newsletter_subscribers WHERE is_active = 1";
    $totalStmt = $db->prepare($totalQuery);
    $totalStmt->execute();
    $stats['total'] = $totalStmt->fetch()['total'];
    
    // Today's subscribers
    $todayQuery = "SELECT COUNT(*) as today FROM newsletter_subscribers WHERE DATE(created_at) = CURDATE() AND is_active = 1";
    $todayStmt = $db->prepare($todayQuery);
    $todayStmt->execute();
    $stats['today'] = $todayStmt->fetch()['today'];
    
    // This week's subscribers
    $weekQuery = "SELECT COUNT(*) as week FROM newsletter_subscribers WHERE YEARWEEK(created_at) = YEARWEEK(NOW()) AND is_active = 1";
    $weekStmt = $db->prepare($weekQuery);
    $weekStmt->execute();
    $stats['week'] = $weekStmt->fetch()['week'];
    
    // Get recent subscribers
    $subscribersQuery = "SELECT * FROM newsletter_subscribers ORDER BY created_at DESC LIMIT 50";
    $subscribersStmt = $db->prepare($subscribersQuery);
    $subscribersStmt->execute();
    $subscribers = $subscribersStmt->fetchAll();
    
} catch (Exception $e) {
    $stats = ['total' => 0, 'today' => 0, 'week' => 0];
    $subscribers = [];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Suscriptores Newsletter - Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-50">
    <div class="container mx-auto px-4 py-8">
        <div class="flex items-center justify-between mb-8">
            <h1 class="text-3xl font-bold text-gray-800">
                <i class="fas fa-envelope mr-3 text-blue-600"></i>
                Suscriptores Newsletter
            </h1>
            <a href="../index.php" class="bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700 transition-colors">
                <i class="fas fa-arrow-left mr-2"></i>
                Volver al sitio
            </a>
        </div>

        <!-- Statistics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="bg-white rounded-xl shadow-lg p-6 border-l-4 border-blue-500">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-semibold text-gray-600 mb-1">Total Suscriptores</p>
                        <p class="text-3xl font-bold text-blue-600"><?php echo $stats['total']; ?></p>
                    </div>
                    <div class="w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center">
                        <i class="fas fa-users text-blue-600 text-2xl"></i>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-xl shadow-lg p-6 border-l-4 border-green-500">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-semibold text-gray-600 mb-1">Hoy</p>
                        <p class="text-3xl font-bold text-green-600"><?php echo $stats['today']; ?></p>
                    </div>
                    <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center">
                        <i class="fas fa-calendar-day text-green-600 text-2xl"></i>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-xl shadow-lg p-6 border-l-4 border-purple-500">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-semibold text-gray-600 mb-1">Esta Semana</p>
                        <p class="text-3xl font-bold text-purple-600"><?php echo $stats['week']; ?></p>
                    </div>
                    <div class="w-16 h-16 bg-purple-100 rounded-full flex items-center justify-center">
                        <i class="fas fa-calendar-week text-purple-600 text-2xl"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Subscribers Table -->
        <div class="bg-white rounded-xl shadow-lg overflow-hidden">
            <div class="px-6 py-4 bg-gray-50 border-b">
                <h2 class="text-xl font-semibold text-gray-800">Suscriptores Recientes</h2>
            </div>
            
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Email
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Fuente
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Fecha
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Estado
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                IP
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php if (empty($subscribers)): ?>
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center text-gray-500">
                                <i class="fas fa-inbox text-4xl mb-4 block text-gray-300"></i>
                                No hay suscriptores aún
                            </td>
                        </tr>
                        <?php else: ?>
                            <?php foreach ($subscribers as $subscriber): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center mr-3">
                                            <i class="fas fa-at text-blue-600"></i>
                                        </div>
                                        <div>
                                            <div class="text-sm font-semibold text-gray-900">
                                                <?php echo htmlspecialchars($subscriber['email']); ?>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
                                        <?php echo htmlspecialchars($subscriber['source']); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php echo date('d/m/Y H:i', strtotime($subscriber['created_at'])); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?php if ($subscriber['is_active']): ?>
                                    <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                        <i class="fas fa-check-circle mr-1"></i>
                                        Activo
                                    </span>
                                    <?php else: ?>
                                    <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                                        <i class="fas fa-times-circle mr-1"></i>
                                        Inactivo
                                    </span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php echo htmlspecialchars($subscriber['ip_address'] ?? 'N/A'); ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Export Options -->
        <div class="mt-8 flex justify-center space-x-4">
            <button onclick="exportToCSV()" class="bg-green-600 text-white px-6 py-3 rounded-lg hover:bg-green-700 transition-colors">
                <i class="fas fa-file-csv mr-2"></i>
                Exportar CSV
            </button>
            <button onclick="refreshData()" class="bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 transition-colors">
                <i class="fas fa-sync mr-2"></i>
                Actualizar
            </button>
        </div>
    </div>

    <script>
        function refreshData() {
            window.location.reload();
        }

        function exportToCSV() {
            const rows = [];
            rows.push(['Email', 'Fuente', 'Fecha', 'Estado', 'IP']);
            
            document.querySelectorAll('tbody tr').forEach(row => {
                const cells = row.querySelectorAll('td');
                if (cells.length > 1) {
                    const rowData = [
                        cells[0].textContent.trim(),
                        cells[1].textContent.trim(),
                        cells[2].textContent.trim(),
                        cells[3].textContent.trim(),
                        cells[4].textContent.trim()
                    ];
                    rows.push(rowData);
                }
            });

            const csvContent = rows.map(row => row.join(',')).join('\n');
            const blob = new Blob([csvContent], { type: 'text/csv' });
            const url = window.URL.createObjectURL(blob);
            const link = document.createElement('a');
            link.href = url;
            link.download = `newsletter-subscribers-${new Date().toISOString().split('T')[0]}.csv`;
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }
    </script>
</body>
</html>
