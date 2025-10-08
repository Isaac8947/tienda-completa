<?php
// Prueba de navegación del admin corregida
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

// Simular sesión de administrador
$_SESSION['user_id'] = 1;
$_SESSION['role'] = 'admin';
$_SESSION['admin_id'] = 1;
$_SESSION['admin_name'] = 'Administrador';
$_SESSION['admin_email'] = 'admin@odiseamakeup.com';

echo "<h2>🔧 Testing Admin Navigation Fix</h2>";

echo "<h3>1. Session Check:</h3>";
echo "✅ User ID: " . ($_SESSION['user_id'] ?? 'Not set') . "<br>";
echo "✅ Role: " . ($_SESSION['role'] ?? 'Not set') . "<br>";
echo "✅ Admin ID: " . ($_SESSION['admin_id'] ?? 'Not set') . "<br>";

echo "<h3>2. File Checks:</h3>";
$files_to_check = [
    'admin/index.php' => 'Dashboard del Admin',
    'admin/pedidos.php' => 'Página de Pedidos',
    'admin/includes/sidebar.php' => 'Sidebar de Navegación'
];

foreach ($files_to_check as $file => $description) {
    if (file_exists($file)) {
        echo "✅ $description: Existe<br>";
    } else {
        echo "❌ $description: No encontrado<br>";
    }
}

echo "<h3>3. Testing Direct Links:</h3>";
echo "<p><a href='admin/index.php' target='_blank'>➡️ Admin Dashboard</a></p>";
echo "<p><a href='admin/pedidos.php' target='_blank'>➡️ Sección de Pedidos</a></p>";

echo "<h3>4. Sidebar Syntax Check:</h3>";
try {
    $sidebar_content = file_get_contents('admin/includes/sidebar.php');
    if (strpos($sidebar_content, 'pedidos.php') !== false) {
        echo "✅ El enlace a pedidos.php está presente en el sidebar<br>";
    } else {
        echo "❌ No se encontró el enlace a pedidos.php en el sidebar<br>";
    }
    
    // Verificar que no haya código corrupto
    if (strpos($sidebar_content, '>>') !== false || strpos($sidebar_content, 'nav::-webkit-scrollbar-thumb {') !== false) {
        echo "⚠️ Posible código corrupto detectado en sidebar<br>";
    } else {
        echo "✅ Sidebar parece estar limpio de corrupción<br>";
    }
} catch (Exception $e) {
    echo "❌ Error leyendo sidebar: " . $e->getMessage() . "<br>";
}

echo "<h3>🎯 Next Steps:</h3>";
echo "<ol>";
echo "<li>Hacer login como administrador</li>";
echo "<li>Navegar al panel de administración</li>";
echo "<li>Hacer clic en 'Pedidos' en el menú lateral</li>";
echo "<li>Verificar que la página se carga correctamente</li>";
echo "</ol>";
?>
