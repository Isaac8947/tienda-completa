<?php
session_start();
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../admin/auth-check.php';
require_once '../models/Settings.php';

// Verificar si el admin está logueado
if (!isAdminLoggedIn()) {
    redirectTo(ADMIN_URL . '/login.php');
}

// Procesar formulario de configuración
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $settingsModel = new Settings();
    
    // Configuraciones básicas de la tienda
    $configurations = [
        'site_name' => $_POST['site_name'] ?? '',
        'site_description' => $_POST['site_description'] ?? '',
        
        // Información de contacto
        'contact_phone' => $_POST['contact_phone'] ?? '',
        'contact_email' => $_POST['contact_email'] ?? '',
        'contact_address' => $_POST['contact_address'] ?? '',
        'contact_hours' => $_POST['contact_hours'] ?? '',
        
        // Redes sociales
        'social_facebook' => $_POST['social_facebook'] ?? '',
        'social_instagram' => $_POST['social_instagram'] ?? '',
        'social_tiktok' => $_POST['social_tiktok'] ?? '',
        'social_youtube' => $_POST['social_youtube'] ?? '',
        
        // Configuraciones comerciales
        'currency' => $_POST['currency'] ?? 'COP',
        'tax_rate' => $_POST['tax_rate'] ?? '0.00',
        'shipping_cost' => $_POST['shipping_cost'] ?? '0.00',
        'free_shipping_threshold' => $_POST['free_shipping_threshold'] ?? '100000.00',
        
        // Configuraciones del sistema
        'maintenance_mode' => isset($_POST['maintenance_mode']) ? '1' : '0',
        'allow_registration' => isset($_POST['allow_registration']) ? '1' : '0',
        'email_notifications' => isset($_POST['email_notifications']) ? '1' : '0'
    ];
    
    // Procesar logo si se subió
    if (isset($_FILES['site_logo']) && $_FILES['site_logo']['error'] === 0) {
        $uploadDir = '../assets/images/';
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        
        $fileName = 'logo.' . pathinfo($_FILES['site_logo']['name'], PATHINFO_EXTENSION);
        $targetFile = $uploadDir . $fileName;
        
        if (move_uploaded_file($_FILES['site_logo']['tmp_name'], $targetFile)) {
            $configurations['site_logo'] = 'assets/images/' . $fileName;
        }
    }
    
    // Guardar todas las configuraciones
    $success = true;
    foreach ($configurations as $key => $value) {
        if (!$settingsModel->set($key, $value)) {
            $success = false;
            break;
        }
    }
    
    if ($success) {
        $message = 'Configuraciones guardadas correctamente';
        $messageType = 'success';
    } else {
        $message = 'Error al guardar las configuraciones';
        $messageType = 'error';
    }
}

// Obtener configuraciones actuales
$settingsModel = new Settings();
$currentSettings = $settingsModel->getAllSettings();

// Valores por defecto
$defaults = [
    'site_name' => 'Odisea Makeup Store',
    'site_description' => 'Tu tienda de maquillaje y belleza de confianza',
    'contact_phone' => '+57 300 123 4567',
    'contact_email' => 'contacto@odisea.com',
    'contact_address' => '',
    'contact_hours' => 'Lun - Vie: 9:00 AM - 6:00 PM',
    'social_facebook' => '',
    'social_instagram' => '',
    'social_tiktok' => '',
    'social_youtube' => '',
    'currency' => 'COP',
    'tax_rate' => '0.00',
    'shipping_cost' => '5000.00',
    'free_shipping_threshold' => '100000.00',
    'site_logo' => 'assets/images/logo.png',
    'maintenance_mode' => '0',
    'allow_registration' => '1',
    'email_notifications' => '1'
];

// Combinar configuraciones actuales con valores por defecto
$settings = array_merge($defaults, $currentSettings);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configuración General - Odisea Admin</title>
    
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
                        }
                    }
                }
            }
        }
    </script>
    
    <style>
        .tab-link {
            @apply px-4 py-2 text-sm font-medium text-gray-600 hover:text-primary-600 hover:bg-primary-50 rounded-lg transition-colors cursor-pointer;
        }
        .tab-link.active {
            @apply bg-primary-100 text-primary-700 font-semibold;
        }
        .tab-content {
            @apply hidden;
        }
        .tab-content.active {
            @apply block;
        }
    </style>
</head>
<body class="bg-gray-50">
    <div class="flex h-screen overflow-hidden">
        <!-- Sidebar -->
        <?php include '../admin/includes/sidebar.php'; ?>
        
        <!-- Main Content -->
        <div class="flex-1 flex flex-col overflow-hidden">
            <!-- Top Header -->
            <?php include '../admin/includes/header.php'; ?>
            
            <!-- Main Content -->
            <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-50 p-6">
                <!-- Page Header -->
                <div class="mb-6">
                    <h1 class="text-3xl font-bold text-gray-900 flex items-center">
                        <i class="fas fa-cogs text-primary-500 mr-3"></i>
                        Configuración General
                    </h1>
                    <p class="text-gray-600 mt-2">Personaliza todos los aspectos de tu tienda online</p>
                </div>

                <!-- Mensaje de confirmación -->
                <?php if (!empty($message)): ?>
                <div class="mb-6 p-4 rounded-lg <?php echo $messageType === 'success' ? 'bg-green-100 border border-green-300 text-green-800' : 'bg-red-100 border border-red-300 text-red-800'; ?>">
                    <div class="flex items-center">
                        <i class="fas <?php echo $messageType === 'success' ? 'fa-check-circle' : 'fa-exclamation-triangle'; ?> mr-2"></i>
                        <?php echo htmlspecialchars($message); ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Navegación de pestañas -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 mb-6">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <nav class="flex space-x-4">
                            <a href="#tienda" class="tab-link active" onclick="showTab(event, 'tienda')">
                                <i class="fas fa-store mr-2"></i>Tienda
                            </a>
                            <a href="#contacto" class="tab-link" onclick="showTab(event, 'contacto')">
                                <i class="fas fa-phone mr-2"></i>Contacto
                            </a>
                            <a href="#redes" class="tab-link" onclick="showTab(event, 'redes')">
                                <i class="fas fa-share-alt mr-2"></i>Redes Sociales
                            </a>
                            <a href="#comercial" class="tab-link" onclick="showTab(event, 'comercial')">
                                <i class="fas fa-dollar-sign mr-2"></i>Comercial
                            </a>
                            <a href="#sistema" class="tab-link" onclick="showTab(event, 'sistema')">
                                <i class="fas fa-cog mr-2"></i>Sistema
                            </a>
                        </nav>
                    </div>
                </div>

                <!-- Formulario -->
                <form method="POST" enctype="multipart/form-data">
                    
                    <!-- 1. Información de la Tienda -->
                    <div id="tienda" class="tab-content active bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
                        <div class="flex items-center mb-6">
                            <div class="bg-blue-500 p-3 rounded-lg mr-4">
                                <i class="fas fa-store text-white text-xl"></i>
                            </div>
                            <div>
                                <h2 class="text-xl font-semibold text-gray-900">Información de la Tienda</h2>
                                <p class="text-gray-600">Configura el nombre, descripción y logo de tu tienda</p>
                            </div>
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="site_name" class="block text-sm font-medium text-gray-700 mb-2">
                                    Nombre de la Tienda *
                                </label>
                                <input type="text" id="site_name" name="site_name" required
                                       value="<?php echo htmlspecialchars($settings['site_name']); ?>"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                            </div>
                            
                            <div>
                                <label for="site_logo" class="block text-sm font-medium text-gray-700 mb-2">
                                    Logo de la Tienda
                                </label>
                                <input type="file" id="site_logo" name="site_logo" accept="image/*"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                                <?php if (!empty($settings['site_logo'])): ?>
                                    <p class="text-sm text-gray-500 mt-1">Actual: <?php echo basename($settings['site_logo']); ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="mt-6">
                            <label for="site_description" class="block text-sm font-medium text-gray-700 mb-2">
                                Descripción de la Tienda
                            </label>
                            <textarea id="site_description" name="site_description" rows="3"
                                      class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                                      placeholder="Describe tu tienda..."><?php echo htmlspecialchars($settings['site_description']); ?></textarea>
                        </div>
                    </div>

                    <!-- 2. Información de Contacto -->
                    <div id="contacto" class="tab-content bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
                        <div class="flex items-center mb-6">
                            <div class="bg-green-500 p-3 rounded-lg mr-4">
                                <i class="fas fa-phone text-white text-xl"></i>
                            </div>
                            <div>
                                <h2 class="text-xl font-semibold text-gray-900">Información de Contacto</h2>
                                <p class="text-gray-600">Datos que aparecerán en el header y footer</p>
                            </div>
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="contact_phone" class="block text-sm font-medium text-gray-700 mb-2">
                                    <i class="fas fa-phone text-green-500 mr-2"></i>Teléfono
                                </label>
                                <input type="text" id="contact_phone" name="contact_phone"
                                       value="<?php echo htmlspecialchars($settings['contact_phone']); ?>"
                                       placeholder="+57 300 123 4567"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                            </div>
                            
                            <div>
                                <label for="contact_email" class="block text-sm font-medium text-gray-700 mb-2">
                                    <i class="fas fa-envelope text-blue-500 mr-2"></i>Email
                                </label>
                                <input type="email" id="contact_email" name="contact_email"
                                       value="<?php echo htmlspecialchars($settings['contact_email']); ?>"
                                       placeholder="contacto@odisea.com"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                            </div>
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6">
                            <div>
                                <label for="contact_address" class="block text-sm font-medium text-gray-700 mb-2">
                                    <i class="fas fa-map-marker-alt text-red-500 mr-2"></i>Dirección
                                </label>
                                <input type="text" id="contact_address" name="contact_address"
                                       value="<?php echo htmlspecialchars($settings['contact_address']); ?>"
                                       placeholder="Calle 123 #45-67, Ciudad"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                            </div>
                            
                            <div>
                                <label for="contact_hours" class="block text-sm font-medium text-gray-700 mb-2">
                                    <i class="fas fa-clock text-purple-500 mr-2"></i>Horarios
                                </label>
                                <input type="text" id="contact_hours" name="contact_hours"
                                       value="<?php echo htmlspecialchars($settings['contact_hours']); ?>"
                                       placeholder="Lun - Vie: 9:00 AM - 6:00 PM"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                            </div>
                        </div>
                    </div>

                    <!-- 3. Redes Sociales -->
                    <div id="redes" class="tab-content bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
                        <div class="flex items-center mb-6">
                            <div class="bg-pink-500 p-3 rounded-lg mr-4">
                                <i class="fas fa-share-alt text-white text-xl"></i>
                            </div>
                            <div>
                                <h2 class="text-xl font-semibold text-gray-900">Redes Sociales</h2>
                                <p class="text-gray-600">Enlaces a tus perfiles sociales</p>
                            </div>
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="social_facebook" class="block text-sm font-medium text-gray-700 mb-2">
                                    <i class="fab fa-facebook text-blue-600 mr-2"></i>Facebook
                                </label>
                                <input type="url" id="social_facebook" name="social_facebook"
                                       value="<?php echo htmlspecialchars($settings['social_facebook']); ?>"
                                       placeholder="https://facebook.com/tupagina"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                            </div>
                            
                            <div>
                                <label for="social_instagram" class="block text-sm font-medium text-gray-700 mb-2">
                                    <i class="fab fa-instagram text-pink-600 mr-2"></i>Instagram
                                </label>
                                <input type="url" id="social_instagram" name="social_instagram"
                                       value="<?php echo htmlspecialchars($settings['social_instagram']); ?>"
                                       placeholder="https://instagram.com/tuperfil"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                            </div>
                            
                            <div>
                                <label for="social_tiktok" class="block text-sm font-medium text-gray-700 mb-2">
                                    <i class="fab fa-tiktok text-black mr-2"></i>TikTok
                                </label>
                                <input type="url" id="social_tiktok" name="social_tiktok"
                                       value="<?php echo htmlspecialchars($settings['social_tiktok']); ?>"
                                       placeholder="https://tiktok.com/@tuperfil"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                            </div>
                            
                            <div>
                                <label for="social_youtube" class="block text-sm font-medium text-gray-700 mb-2">
                                    <i class="fab fa-youtube text-red-600 mr-2"></i>YouTube
                                </label>
                                <input type="url" id="social_youtube" name="social_youtube"
                                       value="<?php echo htmlspecialchars($settings['social_youtube']); ?>"
                                       placeholder="https://youtube.com/tucanal"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                            </div>
                        </div>
                    </div>

                    <!-- 4. Configuración Comercial -->
                    <div id="comercial" class="tab-content bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
                        <div class="flex items-center mb-6">
                            <div class="bg-yellow-500 p-3 rounded-lg mr-4">
                                <i class="fas fa-dollar-sign text-white text-xl"></i>
                            </div>
                            <div>
                                <h2 class="text-xl font-semibold text-gray-900">Configuración Comercial</h2>
                                <p class="text-gray-600">Moneda, impuestos y envíos</p>
                            </div>
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="currency" class="block text-sm font-medium text-gray-700 mb-2">Moneda</label>
                                <select id="currency" name="currency"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                                    <option value="COP" <?php echo $settings['currency'] === 'COP' ? 'selected' : ''; ?>>COP ($)</option>
                                    <option value="USD" <?php echo $settings['currency'] === 'USD' ? 'selected' : ''; ?>>USD ($)</option>
                                    <option value="EUR" <?php echo $settings['currency'] === 'EUR' ? 'selected' : ''; ?>>EUR (€)</option>
                                </select>
                            </div>
                            
                            <div>
                                <label for="tax_rate" class="block text-sm font-medium text-gray-700 mb-2">Tasa de Impuesto (%)</label>
                                <input type="number" id="tax_rate" name="tax_rate" step="0.01"
                                       value="<?php echo htmlspecialchars($settings['tax_rate']); ?>"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                            </div>
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6">
                            <div>
                                <label for="shipping_cost" class="block text-sm font-medium text-gray-700 mb-2">Costo de Envío</label>
                                <input type="number" id="shipping_cost" name="shipping_cost" step="0.01"
                                       value="<?php echo htmlspecialchars($settings['shipping_cost']); ?>"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                            </div>
                            
                            <div>
                                <label for="free_shipping_threshold" class="block text-sm font-medium text-gray-700 mb-2">Envío Gratis desde</label>
                                <input type="number" id="free_shipping_threshold" name="free_shipping_threshold" step="0.01"
                                       value="<?php echo htmlspecialchars($settings['free_shipping_threshold']); ?>"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                            </div>
                        </div>
                    </div>

                    <!-- 5. Configuración del Sistema -->
                    <div id="sistema" class="tab-content bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
                        <div class="flex items-center mb-6">
                            <div class="bg-gray-500 p-3 rounded-lg mr-4">
                                <i class="fas fa-cog text-white text-xl"></i>
                            </div>
                            <div>
                                <h2 class="text-xl font-semibold text-gray-900">Configuración del Sistema</h2>
                                <p class="text-gray-600">Configuraciones generales del sistema</p>
                            </div>
                        </div>
                        
                        <div class="space-y-4">
                            <div class="flex items-center">
                                <input type="checkbox" id="maintenance_mode" name="maintenance_mode" value="1"
                                       <?php echo $settings['maintenance_mode'] ? 'checked' : ''; ?>
                                       class="w-4 h-4 text-primary-600 bg-gray-100 border-gray-300 rounded focus:ring-primary-500">
                                <label for="maintenance_mode" class="ml-2 text-sm font-medium text-gray-900">
                                    Modo de mantenimiento
                                </label>
                            </div>
                            
                            <div class="flex items-center">
                                <input type="checkbox" id="allow_registration" name="allow_registration" value="1"
                                       <?php echo $settings['allow_registration'] ? 'checked' : ''; ?>
                                       class="w-4 h-4 text-primary-600 bg-gray-100 border-gray-300 rounded focus:ring-primary-500">
                                <label for="allow_registration" class="ml-2 text-sm font-medium text-gray-900">
                                    Permitir registro de usuarios
                                </label>
                            </div>
                            
                            <div class="flex items-center">
                                <input type="checkbox" id="email_notifications" name="email_notifications" value="1"
                                       <?php echo $settings['email_notifications'] ? 'checked' : ''; ?>
                                       class="w-4 h-4 text-primary-600 bg-gray-100 border-gray-300 rounded focus:ring-primary-500">
                                <label for="email_notifications" class="ml-2 text-sm font-medium text-gray-900">
                                    Enviar notificaciones por email
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Botón de guardar -->
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                        <div class="flex justify-between items-center">
                            <div>
                                <p class="text-sm text-gray-600">
                                    Los cambios se aplicarán automáticamente en toda la tienda
                                </p>
                            </div>
                            <button type="submit" class="bg-primary-600 hover:bg-primary-700 text-white px-6 py-3 rounded-lg font-medium transition-colors flex items-center">
                                <i class="fas fa-save mr-2"></i>
                                Guardar Configuración
                            </button>
                        </div>
                    </div>

                </form>
            </main>
        </div>
    </div>

    <script>
        function showTab(event, tabName) {
            // Remover clase active de todos los links
            const tabLinks = document.querySelectorAll('.tab-link');
            tabLinks.forEach(link => link.classList.remove('active'));
            
            // Ocultar todos los contenidos
            const tabContents = document.querySelectorAll('.tab-content');
            tabContents.forEach(content => content.classList.remove('active'));
            
            // Activar tab seleccionado
            event.target.classList.add('active');
            document.getElementById(tabName).classList.add('active');
            
            event.preventDefault();
        }

        // Actualizar vista previa en tiempo real
        document.getElementById('contact_phone').addEventListener('input', function() {
            const previewPhone = document.getElementById('preview-phone');
            if (previewPhone) {
                previewPhone.textContent = this.value || '+57 300 123 4567';
            }
        });

        document.getElementById('contact_email').addEventListener('input', function() {
            const previewEmail = document.getElementById('preview-email');
            if (previewEmail) {
                previewEmail.textContent = this.value || 'contacto@odisea.com';
            }
        });
    </script>
</body>
</html>
