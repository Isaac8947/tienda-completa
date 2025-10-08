<?php
session_start();

// Simular login directo para prueba
if (isset($_POST['quick_login'])) {
    $_SESSION['user_id'] = 1;
    $_SESSION['role'] = 'admin';
    $_SESSION['admin_id'] = 1;
    $_SESSION['admin_name'] = 'Administrador';
    $_SESSION['admin_email'] = 'admin@odiseamakeup.com';
    header('Location: admin/index.php');
    exit;
}

if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: test-quick-admin-login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quick Admin Login - Test</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center">
    <div class="bg-white p-8 rounded-lg shadow-md max-w-md w-full">
        <h1 class="text-2xl font-bold text-gray-800 mb-6 text-center">🔧 Quick Admin Login</h1>
        
        <?php if (isset($_SESSION['user_id'])): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                <p><strong>✅ Sesión Activa</strong></p>
                <p>User ID: <?php echo $_SESSION['user_id']; ?></p>
                <p>Role: <?php echo $_SESSION['role']; ?></p>
            </div>
            
            <div class="space-y-3">
                <a href="admin/index.php" class="w-full bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded block text-center">
                    🏠 Ir al Dashboard
                </a>
                
                <a href="admin/pedidos.php" class="w-full bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded block text-center">
                    🛒 Ir a Pedidos
                </a>
                
                <a href="?logout=1" class="w-full bg-red-500 hover:bg-red-700 text-white font-bold py-2 px-4 rounded block text-center">
                    🚪 Cerrar Sesión
                </a>
            </div>
        <?php else: ?>
            <div class="bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded mb-4">
                <p><strong>⚠️ No hay sesión activa</strong></p>
                <p>Usa el botón de abajo para hacer login rápido como administrador</p>
            </div>
            
            <form method="POST" class="space-y-4">
                <button type="submit" name="quick_login" class="w-full bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                    🚀 Login Rápido como Admin
                </button>
            </form>
        <?php endif; ?>
        
        <div class="mt-6 p-4 bg-gray-50 rounded">
            <h3 class="font-bold text-gray-700 mb-2">ℹ️ Información:</h3>
            <p class="text-sm text-gray-600">Esta es una herramienta de prueba para acceso rápido al panel de administración. Una vez dentro, podrás navegar a la sección de pedidos sin problemas.</p>
        </div>
    </div>
</body>
</html>
