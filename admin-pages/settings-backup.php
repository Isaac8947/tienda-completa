<?php
session_start();
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../admin/auth-check.php';
require_once '../models/Admin.php';

// Verificar si el admin está logueado
if (!isAdminLoggedIn()) {
    redirectTo(ADMIN_URL . '/login.php');
}

$admin = new Admin();
$adminData = $admin->findById($_SESSION['admin_id']);

// Procesar formulario de configuración
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $settings = [
        'site_name' => $_POST['site_name'],
        'site_description' => $_POST['site_description'],
        'site_email' => $_POST['site_email'],
        'site_phone' => $_POST['site_phone'],
        'site_address' => $_POST['site_address'],
        'currency' => $_POST['currency'],
        'tax_rate' => $_POST['tax_rate'],
        'shipping_cost' => $_POST['shipping_cost'],
        'free_shipping_threshold' => $_POST['free_shipping_threshold'],
        'maintenance_mode' => isset($_POST['maintenance_mode']) ? 1 : 0,
        'allow_registration' => isset($_POST['allow_registration']) ? 1 : 0,
        'email_notifications' => isset($_POST['email_notifications']) ? 1 : 0,
        // Redes sociales
        'social_facebook' => $_POST['social_facebook'] ?? '',
        'social_instagram' => $_POST['social_instagram'] ?? '',
        'social_twitter' => $_POST['social_twitter'] ?? '',
        'social_youtube' => $_POST['social_youtube'] ?? '',
        'social_tiktok' => $_POST['social_tiktok'] ?? ''
    ];
    
    // Procesar logo si se subió
    if (isset($_FILES['site_logo']) && $_FILES['site_logo']['error'] === 0) {
        $uploadDir = '../assets/images/';
        $fileName = 'logo.' . pathinfo($_FILES['site_logo']['name'], PATHINFO_EXTENSION);
        $targetFile = $uploadDir . $fileName;
        
        if (move_uploaded_file($_FILES['site_logo']['tmp_name'], $targetFile)) {
            $settings['site_logo'] = 'assets/images/' . $fileName;
        }
    }
    
    // Guardar configuraciones en la base de datos
    $db = new Database();
    $conn = $db->getConnection();
    
    foreach ($settings as $key => $value) {
        $stmt = $conn->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?");
        $stmt->execute([$key, $value, $value]);
    }
    
    $message = 'Configuración guardada correctamente';
    $messageType = 'success';
}

// Obtener configuraciones actuales
$db = new Database();
$conn = $db->getConnection();
$stmt = $conn->query("SELECT setting_key, setting_value FROM settings");
$currentSettings = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $currentSettings[$row['setting_key']] = $row['setting_value'];
}

// Valores por defecto
$defaults = [
    'site_name' => 'Odisea Makeup Store',
    'site_description' => 'Tu tienda de maquillaje online',
    'site_email' => 'info@odisea.com',
    'site_phone' => '+1234567890',
    'site_address' => 'Dirección de la tienda',
    'currency' => 'USD',
    'tax_rate' => '0.00',
    'shipping_cost' => '5.00',
    'free_shipping_threshold' => '50.00',
    'site_logo' => 'assets/images/logo.png',
    'maintenance_mode' => 0,
    'allow_registration' => 1,
    'email_notifications' => 1,
    // Redes sociales
    'social_facebook' => '',
    'social_instagram' => '',
    'social_twitter' => '',
    'social_youtube' => '',
    'social_tiktok' => ''
];

// Combinar configuraciones actuales con valores por defecto
$settings = array_merge($defaults, $currentSettings);

// Estadísticas para el sidebar
$stats = [
    'pending_orders' => $admin->getPendingOrders(),
    'low_stock_products' => $admin->getLowStockProducts()
];
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
        .config-card {
            background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
            border: 1px solid #e2e8f0;
            transition: all 0.3s ease;
        }
        .config-card:hover {
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
            transform: translateY(-2px);
        }
        .config-icon {
            background: linear-gradient(135deg, #ec4899 0%, #be185d 100%);
        }
        .setting-group {
            background: linear-gradient(135deg, #f1f5f9 0%, #ffffff 100%);
            border-left: 4px solid #ec4899;
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
            <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-50 p-4 lg:p-6">
                <!-- Page Header -->
                <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between mb-8">
                    <div class="mb-4 lg:mb-0">
                        <h1 class="text-3xl lg:text-4xl font-bold text-gray-900 flex items-center">
                            <i class="fas fa-cogs text-primary-500 mr-4"></i>
                            Configuración General
                        </h1>
                        <p class="text-gray-600 mt-2 text-lg">Personaliza todos los aspectos de tu tienda online</p>
                        <div class="flex items-center mt-3 text-sm text-gray-500">
                            <i class="fas fa-info-circle mr-2"></i>
                            Los cambios se aplicarán automáticamente en toda la tienda
                        </div>
                    </div>
                    <div class="flex space-x-3">
                        <button onclick="previewChanges()" class="bg-gray-100 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-200 transition-colors">
                            <i class="fas fa-eye mr-2"></i>Vista Previa
                        </button>
                        <button onclick="resetToDefaults()" class="bg-yellow-100 text-yellow-700 px-4 py-2 rounded-lg hover:bg-yellow-200 transition-colors">
                            <i class="fas fa-undo mr-2"></i>Restaurar
                        </button>
                    </div>
                </div>

                <?php if (!empty($message)): ?>
                <div class="mb-6 p-4 rounded-lg border <?php echo $messageType === 'success' ? 'bg-green-50 border-green-200 text-green-800' : 'bg-red-50 border-red-200 text-red-800'; ?>">
                    <div class="flex items-center">
                        <?php echo $messageType === 'success' ? '<i class="fas fa-check-circle mr-3 text-lg"></i>' : '<i class="fas fa-exclamation-triangle mr-3 text-lg"></i>'; ?>
                        <div>
                            <h4 class="font-semibold"><?php echo $messageType === 'success' ? '¡Éxito!' : 'Error'; ?></h4>
                            <p><?php echo $message; ?></p>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Settings Form -->
                <form method="POST" enctype="multipart/form-data" class="space-y-8">
                    
                    <!-- Quick Overview Cards -->
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                        <div class="config-card rounded-xl p-6">
                            <div class="flex items-center">
                                <div class="config-icon w-12 h-12 rounded-lg flex items-center justify-center text-white mr-4">
                                    <i class="fas fa-store text-xl"></i>
                                </div>
                                <div>
                                    <h3 class="font-semibold text-gray-900">Información</h3>
                                    <p class="text-sm text-gray-600">Datos básicos</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="config-card rounded-xl p-6">
                            <div class="flex items-center">
                                <div class="config-icon w-12 h-12 rounded-lg flex items-center justify-center text-white mr-4">
                                    <i class="fas fa-phone text-xl"></i>
                                </div>
                                <div>
                                    <h3 class="font-semibold text-gray-900">Contacto</h3>
                                    <p class="text-sm text-gray-600">Teléfono y email</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="config-card rounded-xl p-6">
                            <div class="flex items-center">
                                <div class="config-icon w-12 h-12 rounded-lg flex items-center justify-center text-white mr-4">
                                    <i class="fas fa-share-alt text-xl"></i>
                                </div>
                                <div>
                                    <h3 class="font-semibold text-gray-900">Redes Sociales</h3>
                                    <p class="text-sm text-gray-600">Enlaces sociales</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="config-card rounded-xl p-6">
                            <div class="flex items-center">
                                <div class="config-icon w-12 h-12 rounded-lg flex items-center justify-center text-white mr-4">
                                    <i class="fas fa-dollar-sign text-xl"></i>
                                </div>
                                <div>
                                    <h3 class="font-semibold text-gray-900">Comercio</h3>
                                    <p class="text-sm text-gray-600">Precios y envíos</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- 1. INFORMACIÓN BÁSICA DE LA TIENDA -->
                    <div class="setting-group rounded-xl p-8 mb-8">
                        <div class="flex items-center mb-6">
                            <div class="config-icon w-12 h-12 rounded-lg flex items-center justify-center text-white mr-4">
                                <i class="fas fa-store text-xl"></i>
                            </div>
                            <div>
                                <h2 class="text-2xl font-bold text-gray-900">Información de la Tienda</h2>
                                <p class="text-gray-600">Datos básicos que aparecerán en toda la tienda</p>
                            </div>
                        </div>
                        
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                            <div class="space-y-6">
                                <div>
                                    <label for="site_name" class="block text-sm font-semibold text-gray-700 mb-3">
                                        <i class="fas fa-tag text-primary-500 mr-2"></i>Nombre de la Tienda *
                                    </label>
                                    <input type="text" id="site_name" name="site_name" required
                                           value="<?php echo htmlspecialchars($settings['site_name']); ?>"
                                           placeholder="Ej: Odisea Makeup Store"
                                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition-colors">
                                    <p class="text-xs text-gray-500 mt-2">Este nombre aparecerá en el header y título de todas las páginas</p>
                                </div>
                                
                                <div>
                                    <label for="site_description" class="block text-sm font-semibold text-gray-700 mb-3">
                                        <i class="fas fa-align-left text-primary-500 mr-2"></i>Descripción de la Tienda
                                    </label>
                                    <textarea id="site_description" name="site_description" rows="3"
                                              placeholder="Tu tienda de maquillaje y belleza de confianza"
                                              class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition-colors"><?php echo htmlspecialchars($settings['site_description']); ?></textarea>
                                    <p class="text-xs text-gray-500 mt-2">Descripción para SEO y redes sociales</p>
                                </div>
                                
                                <div>
                                    <label for="site_address" class="block text-sm font-semibold text-gray-700 mb-3">
                                        <i class="fas fa-map-marker-alt text-primary-500 mr-2"></i>Dirección Física
                                    </label>
                                    <textarea id="site_address" name="site_address" rows="2"
                                              placeholder="Calle 123 #45-67, Barranquilla, Colombia"
                                              class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition-colors"><?php echo htmlspecialchars($settings['site_address']); ?></textarea>
                                    <p class="text-xs text-gray-500 mt-2">Aparecerá en el footer y páginas de contacto</p>
                                </div>
                            </div>
                            
                            <div class="space-y-6">
                                <div>
                                    <label for="site_logo" class="block text-sm font-semibold text-gray-700 mb-3">
                                        <i class="fas fa-image text-primary-500 mr-2"></i>Logo de la Tienda
                                    </label>
                                    <div class="border-2 border-dashed border-gray-300 rounded-lg p-6 text-center hover:border-primary-500 transition-colors">
                                        <?php if (!empty($settings['site_logo'])): ?>
                                            <img src="../<?php echo htmlspecialchars($settings['site_logo']); ?>" alt="Logo actual" class="h-16 mx-auto mb-4 object-contain">
                                            <p class="text-sm text-gray-600 mb-2">Logo actual</p>
                                        <?php endif; ?>
                                        <input type="file" id="site_logo" name="site_logo" accept="image/*" class="hidden">
                                        <button type="button" onclick="document.getElementById('site_logo').click()" class="bg-primary-500 text-white px-4 py-2 rounded-lg hover:bg-primary-600 transition-colors">
                                            <i class="fas fa-upload mr-2"></i>
                                            <?php echo !empty($settings['site_logo']) ? 'Cambiar Logo' : 'Subir Logo'; ?>
                                        </button>
                                        <p class="text-xs text-gray-500 mt-2">PNG, JPG hasta 2MB. Recomendado: 200x60px</p>
                                    </div>
                                </div>
                                
                                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                                    <div class="flex items-start">
                                        <i class="fas fa-lightbulb text-blue-500 mr-3 mt-1"></i>
                                        <div>
                                            <h4 class="font-semibold text-blue-900 mb-2">Consejos para tu tienda</h4>
                                            <ul class="text-sm text-blue-800 space-y-1">
                                                <li>• Usa un nombre memorable y fácil de recordar</li>
                                                <li>• La descripción debe reflejar tu propuesta de valor</li>
                                                <li>• El logo debe ser legible en tamaños pequeños</li>
                                                <li>• La dirección ayuda a generar confianza</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- 2. INFORMACIÓN DE CONTACTO -->
                    <div class="setting-group rounded-xl p-8 mb-8">
                        <div class="flex items-center mb-6">
                            <div class="config-icon w-12 h-12 rounded-lg flex items-center justify-center text-white mr-4">
                                <i class="fas fa-phone text-xl"></i>
                            </div>
                            <div>
                                <h2 class="text-2xl font-bold text-gray-900">Información de Contacto</h2>
                                <p class="text-gray-600">Datos que aparecerán en el header, footer y botón de WhatsApp</p>
                            </div>
                        </div>
                        
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                            <div class="space-y-6">
                                <div>
                                    <label for="site_email" class="block text-sm font-semibold text-gray-700 mb-3">
                                        <i class="fas fa-envelope text-primary-500 mr-2"></i>Email Principal *
                                    </label>
                                    <input type="email" id="site_email" name="site_email" required
                                           value="<?php echo htmlspecialchars($settings['site_email']); ?>"
                                           placeholder="contacto@odisea.com"
                                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition-colors">
                                    <p class="text-xs text-gray-500 mt-2">Email que aparecerá en el header superior y footer</p>
                                </div>
                                
                                <div>
                                    <label for="site_phone" class="block text-sm font-semibold text-gray-700 mb-3">
                                        <i class="fas fa-phone text-primary-500 mr-2"></i>Teléfono/WhatsApp *
                                    </label>
                                    <input type="text" id="site_phone" name="site_phone"
                                           value="<?php echo htmlspecialchars($settings['site_phone']); ?>"
                                           placeholder="+57 300 123 4567"
                                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition-colors">
                                    <p class="text-xs text-gray-500 mt-2">Incluye código de país. Se usará para el botón flotante de WhatsApp</p>
                                </div>
                            </div>
                            
                            <div class="bg-green-50 border border-green-200 rounded-lg p-6">
                                <div class="flex items-start">
                                    <i class="fab fa-whatsapp text-green-500 text-2xl mr-4 mt-1"></i>
                                    <div>
                                        <h4 class="font-semibold text-green-900 mb-2">Vista Previa WhatsApp</h4>
                                        <p class="text-sm text-green-800 mb-4">Así se verá tu botón flotante de WhatsApp:</p>
                                        <div class="bg-white rounded-lg p-4 border-2 border-green-300">
                                            <div class="flex items-center space-x-3">
                                                <div class="w-12 h-12 bg-green-500 rounded-full flex items-center justify-center">
                                                    <i class="fab fa-whatsapp text-white text-xl"></i>
                                                </div>
                                                <div>
                                                    <p class="font-semibold text-green-800">Chatea con nosotros</p>
                                                    <p class="text-sm text-green-600" id="phone-preview"><?php echo htmlspecialchars($settings['site_phone'] ?: '+57 300 123 4567'); ?></p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- 3. REDES SOCIALES -->
                    <div class="setting-group rounded-xl p-8 mb-8">
                        <div class="flex items-center mb-6">
                            <div class="config-icon w-12 h-12 rounded-lg flex items-center justify-center text-white mr-4">
                                <i class="fas fa-share-alt text-xl"></i>
                            </div>
                            <div>
                                <h2 class="text-2xl font-bold text-gray-900">Redes Sociales</h2>
                                <p class="text-gray-600">Enlaces que aparecerán en el header superior y footer de todas las páginas</p>
                            </div>
                        </div>
                        
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                            <div class="space-y-6">
                                <div>
                                    <label for="social_facebook" class="block text-sm font-semibold text-gray-700 mb-3">
                                        <i class="fab fa-facebook text-blue-600 mr-2"></i>Facebook
                                    </label>
                                    <input type="url" id="social_facebook" name="social_facebook"
                                           value="<?php echo htmlspecialchars($settings['social_facebook']); ?>"
                                           placeholder="https://facebook.com/odiseamakeup"
                                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition-colors">
                                </div>
                                
                                <div>
                                    <label for="social_instagram" class="block text-sm font-semibold text-gray-700 mb-3">
                                        <i class="fab fa-instagram text-pink-600 mr-2"></i>Instagram
                                    </label>
                                    <input type="url" id="social_instagram" name="social_instagram"
                                           value="<?php echo htmlspecialchars($settings['social_instagram']); ?>"
                                           placeholder="https://instagram.com/odiseamakeup"
                                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition-colors">
                                </div>
                                
                                <div>
                                    <label for="social_tiktok" class="block text-sm font-semibold text-gray-700 mb-3">
                                        <i class="fab fa-tiktok text-black mr-2"></i>TikTok
                                    </label>
                                    <input type="url" id="social_tiktok" name="social_tiktok"
                                           value="<?php echo htmlspecialchars($settings['social_tiktok']); ?>"
                                           placeholder="https://tiktok.com/@odiseamakeup"
                                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition-colors">
                                </div>
                            </div>
                            
                            <div class="space-y-6">
                                <div>
                                    <label for="social_youtube" class="block text-sm font-semibold text-gray-700 mb-3">
                                        <i class="fab fa-youtube text-red-600 mr-2"></i>YouTube
                                    </label>
                                    <input type="url" id="social_youtube" name="social_youtube"
                                           value="<?php echo htmlspecialchars($settings['social_youtube']); ?>"
                                           placeholder="https://youtube.com/odiseamakeup"
                                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition-colors">
                                </div>
                                
                                <div>
                                    <label for="social_twitter" class="block text-sm font-semibold text-gray-700 mb-3">
                                        <i class="fab fa-twitter text-blue-400 mr-2"></i>Twitter
                                    </label>
                                    <input type="url" id="social_twitter" name="social_twitter"
                                           value="<?php echo htmlspecialchars($settings['social_twitter']); ?>"
                                           placeholder="https://twitter.com/odiseamakeup"
                                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition-colors">
                                </div>
                                
                                <div class="bg-purple-50 border border-purple-200 rounded-lg p-6">
                                    <div class="flex items-start">
                                        <i class="fas fa-share-alt text-purple-500 mr-3 mt-1"></i>
                                        <div>
                                            <h4 class="font-semibold text-purple-900 mb-2">Vista Previa Redes</h4>
                                            <p class="text-sm text-purple-800 mb-4">Así aparecerán en el header superior:</p>
                                            <div class="flex space-x-2">
                                                <div class="w-8 h-8 bg-blue-600 rounded flex items-center justify-center">
                                                    <i class="fab fa-facebook text-white text-sm"></i>
                                                </div>
                                                <div class="w-8 h-8 bg-pink-600 rounded flex items-center justify-center">
                                                    <i class="fab fa-instagram text-white text-sm"></i>
                                                </div>
                                                <div class="w-8 h-8 bg-black rounded flex items-center justify-center">
                                                    <i class="fab fa-tiktok text-white text-sm"></i>
                                                </div>
                                                <div class="w-8 h-8 bg-red-600 rounded flex items-center justify-center">
                                                    <i class="fab fa-youtube text-white text-sm"></i>
                                                </div>
                                            </div>
                                            <p class="text-xs text-purple-600 mt-2">Solo aparecerán las redes con URL configurada</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- 4. CONFIGURACIÓN COMERCIAL -->
                                
                                <!-- Store Settings -->
                                <div id="store" class="tab-content">
                                    <h2 class="text-xl font-semibold text-gray-900 mb-6">Configuración de la Tienda</h2>
                                    
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                        <div>
                                            <label for="currency" class="block text-sm font-medium text-gray-700 mb-2">Moneda:</label>
                                            <select id="currency" name="currency"
                                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                                                <option value="USD" <?php echo $settings['currency'] === 'USD' ? 'selected' : ''; ?>>USD ($)</option>
                                                <option value="EUR" <?php echo $settings['currency'] === 'EUR' ? 'selected' : ''; ?>>EUR (€)</option>
                                                <option value="MXN" <?php echo $settings['currency'] === 'MXN' ? 'selected' : ''; ?>>MXN ($)</option>
                                                <option value="COP" <?php echo $settings['currency'] === 'COP' ? 'selected' : ''; ?>>COP ($)</option>
                                            </select>
                                        </div>
                                        
                                        <div>
                                            <label for="tax_rate" class="block text-sm font-medium text-gray-700 mb-2">Tasa de Impuesto (%):</label>
                                            <input type="number" id="tax_rate" name="tax_rate" step="0.01"
                                                   value="<?php echo htmlspecialchars($settings['tax_rate']); ?>"
                                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                                        </div>
                                    </div>
                                    
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                        <div>
                                            <label for="shipping_cost" class="block text-sm font-medium text-gray-700 mb-2">Costo de Envío:</label>
                                            <input type="number" id="shipping_cost" name="shipping_cost" step="0.01"
                                                   value="<?php echo htmlspecialchars($settings['shipping_cost']); ?>"
                                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                                        </div>
                                        
                                        <div>
                                            <label for="free_shipping_threshold" class="block text-sm font-medium text-gray-700 mb-2">Envío Gratis a partir de:</label>
                                            <input type="number" id="free_shipping_threshold" name="free_shipping_threshold" step="0.01"
                                                   value="<?php echo htmlspecialchars($settings['free_shipping_threshold']); ?>"
                                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- System Settings -->
                                <div id="system" class="tab-content">
                                    <h2 class="text-xl font-semibold text-gray-900 mb-6">Configuración del Sistema</h2>
                                    
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                        <div>
                                            <label for="maintenance_mode" class="block text-sm font-medium text-gray-700 mb-2">Modo de Mantenimiento:</label>
                                            <div class="flex items-center">
                                                <input type="checkbox" id="maintenance_mode" name="maintenance_mode"
                                                       value="1" <?php echo $settings['maintenance_mode'] ? 'checked' : ''; ?>
                                                       class="form-checkbox h-4 w-4 text-primary-600 focus:ring-primary-500">
                                                <span class="ml-2">Activar modo de mantenimiento</span>
                                            </div>
                                        </div>
                                        
                                        <div>
                                            <label for="allow_registration" class="block text-sm font-medium text-gray-700 mb-2">Permitir Registro:</label>
                                            <div class="flex items-center">
                                                <input type="checkbox" id="allow_registration" name="allow_registration"
                                                       value="1" <?php echo $settings['allow_registration'] ? 'checked' : ''; ?>
                                                       class="form-checkbox h-4 w-4 text-primary-600 focus:ring-primary-500">
                                                <span class="ml-2">Permitir registro de nuevos usuarios</span>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                        <div>
                                            <label for="email_notifications" class="block text-sm font-medium text-gray-700 mb-2">Notificaciones por Email:</label>
                                            <div class="flex items-center">
                                                <input type="checkbox" id="email_notifications" name="email_notifications"
                                                       value="1" <?php echo $settings['email_notifications'] ? 'checked' : ''; ?>
                                                       class="form-checkbox h-4 w-4 text-primary-600 focus:ring-primary-500">
                                                <span class="ml-2">Enviar notificaciones por email</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Redes Sociales -->
                            <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
                                <div class="p-6">
                                    <h2 class="text-xl font-semibold text-gray-900 mb-6">Redes Sociales</h2>
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
                                                   placeholder="https://instagram.com/tuusuario"
                                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                                        </div>
                                        
                                        <div>
                                            <label for="social_twitter" class="block text-sm font-medium text-gray-700 mb-2">
                                                <i class="fab fa-twitter text-blue-400 mr-2"></i>Twitter
                                            </label>
                                            <input type="url" id="social_twitter" name="social_twitter"
                                                   value="<?php echo htmlspecialchars($settings['social_twitter']); ?>"
                                                   placeholder="https://twitter.com/tuusuario"
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
                                        
                                        <div>
                                            <label for="social_tiktok" class="block text-sm font-medium text-gray-700 mb-2">
                                                <i class="fab fa-tiktok text-black mr-2"></i>TikTok
                                            </label>
                                            <input type="url" id="social_tiktok" name="social_tiktok"
                                                   value="<?php echo htmlspecialchars($settings['social_tiktok']); ?>"
                                                   placeholder="https://tiktok.com/@tuusuario"
                                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-actions mt-8">
                                <button type="submit" class="bg-primary-500 text-white px-4 py-2 rounded-lg hover:bg-primary-600 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2">
                                    <i class="fas fa-save mr-2"></i> Guardar Configuración
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </main>
        </div>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Funcionalidad de pestañas
            const tabButtons = document.querySelectorAll('.tab-link');
            const tabContents = document.querySelectorAll('.tab-content');
            
            tabButtons.forEach(function(button) {
                button.addEventListener('click', function() {
                    const tabId = this.getAttribute('href').substring(1);
                    
                    // Remover clase active de todos los botones y contenidos
                    tabButtons.forEach(function(btn) {
                        btn.classList.remove('active');
                    });
                    tabContents.forEach(function(content) {
                        content.classList.remove('active');
                    });
                    
                    // Agregar clase active al botón y contenido seleccionado
                    this.classList.add('active');
                    document.getElementById(tabId).classList.add('active');
                });
            });
        });
    </script>
</body>
</html>
