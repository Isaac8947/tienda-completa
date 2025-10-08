<?php
session_start();
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../admin/auth-check.php';

// Inicializar variables
$message = '';
$messageType = '';

// Procesar formulario si se envía
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $db = new Database();
        $conn = $db->getConnection();
        
        if (isset($_POST['action'])) {
            switch ($_POST['action']) {
                case 'update_payment_settings':
                    // Actualizar configuraciones de pago
                    $settings = [
                        'paypal_enabled' => isset($_POST['paypal_enabled']) ? 1 : 0,
                        'paypal_client_id' => $_POST['paypal_client_id'] ?? '',
                        'paypal_secret' => $_POST['paypal_secret'] ?? '',
                        'paypal_sandbox' => isset($_POST['paypal_sandbox']) ? 1 : 0,
                        'stripe_enabled' => isset($_POST['stripe_enabled']) ? 1 : 0,
                        'stripe_public_key' => $_POST['stripe_public_key'] ?? '',
                        'stripe_secret_key' => $_POST['stripe_secret_key'] ?? '',
                        'stripe_webhook_secret' => $_POST['stripe_webhook_secret'] ?? '',
                        'bank_transfer_enabled' => isset($_POST['bank_transfer_enabled']) ? 1 : 0,
                        'bank_account_info' => $_POST['bank_account_info'] ?? '',
                        'cash_on_delivery_enabled' => isset($_POST['cash_on_delivery_enabled']) ? 1 : 0,
                        'cod_fee' => floatval($_POST['cod_fee'] ?? 0),
                        'minimum_order_amount' => floatval($_POST['minimum_order_amount'] ?? 0),
                        'payment_currency' => $_POST['payment_currency'] ?? 'USD',
                        'tax_rate' => floatval($_POST['tax_rate'] ?? 0)
                    ];
                    
                    foreach ($settings as $key => $value) {
                        $stmt = $conn->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?");
                        $stmt->execute([$key, $value, $value]);
                    }
                    
                    $message = 'Configuraciones de pago actualizadas correctamente';
                    $messageType = 'success';
                    break;
                    
                case 'create_payment_method':
                    // Crear nuevo método de pago personalizado
                    $stmt = $conn->prepare("INSERT INTO payment_methods (name, description, is_active, settings) VALUES (?, ?, ?, ?)");
                    $stmt->execute([
                        $_POST['method_name'],
                        $_POST['method_description'],
                        isset($_POST['method_active']) ? 1 : 0,
                        json_encode($_POST['method_settings'] ?? [])
                    ]);
                    
                    $message = 'Método de pago creado correctamente';
                    $messageType = 'success';
                    break;
                    
                case 'delete_payment_method':
                    $stmt = $conn->prepare("DELETE FROM payment_methods WHERE id = ?");
                    $stmt->execute([$_POST['method_id']]);
                    
                    $message = 'Método de pago eliminado correctamente';
                    $messageType = 'success';
                    break;
            }
        }
        
    } catch (Exception $e) {
        $message = 'Error: ' . $e->getMessage();
        $messageType = 'error';
    }
}

// Obtener configuraciones actuales
$currentSettings = [];
try {
    $db = new Database();
    $conn = $db->getConnection();
    
    $stmt = $conn->query("SELECT setting_key, setting_value FROM settings WHERE setting_key LIKE '%payment%' OR setting_key IN ('paypal_%', 'stripe_%', 'bank_%', 'cash_%', 'cod_%', 'minimum_order_amount', 'payment_currency', 'tax_rate')");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $currentSettings[$row['setting_key']] = $row['setting_value'];
    }
    
    // Obtener métodos de pago personalizados
    $stmt = $conn->query("SELECT * FROM payment_methods ORDER BY name");
    $customMethods = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    $currentSettings = [];
    $customMethods = [];
}

// Función para obtener valor de configuración
function getSetting($key, $default = '') {
    global $currentSettings;
    return $currentSettings[$key] ?? $default;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configuración de Pagos - Odisea Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
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
                        <h1 class="text-2xl lg:text-3xl font-bold text-gray-900">Configuración de Pagos</h1>
                        <p class="text-gray-600 mt-1">Gestiona los métodos de pago y configuraciones financieras</p>
                    </div>
                    <div class="flex space-x-3">
                        <button onclick="testPaymentConnection()" class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-colors">
                            <i class="fas fa-plug mr-2"></i>
                            Probar Conexión
                        </button>
                    </div>
                </div>

                <!-- Success/Error Messages -->
                <?php if (!empty($message)): ?>
                <div class="mb-6 p-4 rounded-lg <?php echo $messageType === 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                    <i class="fas <?php echo $messageType === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?> mr-2"></i>
                    <?php echo htmlspecialchars($message); ?>
                </div>
                <?php endif; ?>

                <form method="POST" class="space-y-8">
                    <input type="hidden" name="action" value="update_payment_settings">
                    
                    <!-- PayPal Configuration -->
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                        <div class="flex items-center mb-6">
                            <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center mr-4">
                                <i class="fab fa-paypal text-blue-600 text-xl"></i>
                            </div>
                            <div>
                                <h3 class="text-lg font-medium text-gray-900">PayPal</h3>
                                <p class="text-sm text-gray-500">Configuración de pagos con PayPal</p>
                            </div>
                            <div class="ml-auto">
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" name="paypal_enabled" value="1" class="sr-only peer" <?php echo getSetting('paypal_enabled') ? 'checked' : ''; ?>>
                                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                                </label>
                            </div>
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Client ID</label>
                                <input type="text" name="paypal_client_id" value="<?php echo htmlspecialchars(getSetting('paypal_client_id')); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Client Secret</label>
                                <input type="password" name="paypal_secret" value="<?php echo htmlspecialchars(getSetting('paypal_secret')); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                            </div>
                        </div>
                        
                        <div class="mt-4">
                            <label class="flex items-center">
                                <input type="checkbox" name="paypal_sandbox" value="1" class="w-4 h-4 text-primary-600 bg-gray-100 border-gray-300 rounded focus:ring-primary-500" <?php echo getSetting('paypal_sandbox') ? 'checked' : ''; ?>>
                                <span class="ml-2 text-sm text-gray-700">Modo Sandbox (Pruebas)</span>
                            </label>
                        </div>
                    </div>

                    <!-- Stripe Configuration -->
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                        <div class="flex items-center mb-6">
                            <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center mr-4">
                                <i class="fab fa-stripe text-purple-600 text-xl"></i>
                            </div>
                            <div>
                                <h3 class="text-lg font-medium text-gray-900">Stripe</h3>
                                <p class="text-sm text-gray-500">Configuración de pagos con Stripe</p>
                            </div>
                            <div class="ml-auto">
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" name="stripe_enabled" value="1" class="sr-only peer" <?php echo getSetting('stripe_enabled') ? 'checked' : ''; ?>>
                                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-purple-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-purple-600"></div>
                                </label>
                            </div>
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Publishable Key</label>
                                <input type="text" name="stripe_public_key" value="<?php echo htmlspecialchars(getSetting('stripe_public_key')); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Secret Key</label>
                                <input type="password" name="stripe_secret_key" value="<?php echo htmlspecialchars(getSetting('stripe_secret_key')); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                            </div>
                        </div>
                        
                        <div class="mt-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Webhook Secret</label>
                            <input type="password" name="stripe_webhook_secret" value="<?php echo htmlspecialchars(getSetting('stripe_webhook_secret')); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                            <p class="mt-1 text-xs text-gray-500">Webhook endpoint: https://tudominio.com/webhooks/stripe</p>
                        </div>
                    </div>

                    <!-- Bank Transfer Configuration -->
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                        <div class="flex items-center mb-6">
                            <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center mr-4">
                                <i class="fas fa-university text-green-600 text-xl"></i>
                            </div>
                            <div>
                                <h3 class="text-lg font-medium text-gray-900">Transferencia Bancaria</h3>
                                <p class="text-sm text-gray-500">Configuración de pagos por transferencia bancaria</p>
                            </div>
                            <div class="ml-auto">
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" name="bank_transfer_enabled" value="1" class="sr-only peer" <?php echo getSetting('bank_transfer_enabled') ? 'checked' : ''; ?>>
                                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-green-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-green-600"></div>
                                </label>
                            </div>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Información de la cuenta bancaria</label>
                            <textarea name="bank_account_info" rows="4" class="w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500" placeholder="Incluye: Banco, Número de cuenta, CLABE, Beneficiario, etc."><?php echo htmlspecialchars(getSetting('bank_account_info')); ?></textarea>
                        </div>
                    </div>

                    <!-- Cash on Delivery Configuration -->
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                        <div class="flex items-center mb-6">
                            <div class="w-12 h-12 bg-yellow-100 rounded-lg flex items-center justify-center mr-4">
                                <i class="fas fa-truck text-yellow-600 text-xl"></i>
                            </div>
                            <div>
                                <h3 class="text-lg font-medium text-gray-900">Pago Contraentrega</h3>
                                <p class="text-sm text-gray-500">Configuración de pago al momento de la entrega</p>
                            </div>
                            <div class="ml-auto">
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" name="cash_on_delivery_enabled" value="1" class="sr-only peer" <?php echo getSetting('cash_on_delivery_enabled') ? 'checked' : ''; ?>>
                                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-yellow-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-yellow-600"></div>
                                </label>
                            </div>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Comisión por pago contraentrega ($)</label>
                            <input type="number" name="cod_fee" value="<?php echo htmlspecialchars(getSetting('cod_fee')); ?>" step="0.01" min="0" class="w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                            <p class="mt-1 text-xs text-gray-500">Comisión adicional que se aplicará por usar pago contraentrega</p>
                        </div>
                    </div>

                    <!-- General Payment Settings -->
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-6">Configuración General</h3>
                        
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Moneda de Pago</label>
                                <select name="payment_currency" class="w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                                    <option value="USD" <?php echo getSetting('payment_currency') === 'USD' ? 'selected' : ''; ?>>USD - Dólar Estadounidense</option>
                                    <option value="MXN" <?php echo getSetting('payment_currency') === 'MXN' ? 'selected' : ''; ?>>MXN - Peso Mexicano</option>
                                    <option value="EUR" <?php echo getSetting('payment_currency') === 'EUR' ? 'selected' : ''; ?>>EUR - Euro</option>
                                    <option value="GBP" <?php echo getSetting('payment_currency') === 'GBP' ? 'selected' : ''; ?>>GBP - Libra Esterlina</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Monto Mínimo de Orden ($)</label>
                                <input type="number" name="minimum_order_amount" value="<?php echo htmlspecialchars(getSetting('minimum_order_amount')); ?>" step="0.01" min="0" class="w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Tasa de Impuesto (%)</label>
                                <input type="number" name="tax_rate" value="<?php echo htmlspecialchars(getSetting('tax_rate')); ?>" step="0.01" min="0" max="100" class="w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                            </div>
                        </div>
                    </div>

                    <!-- Save Button -->
                    <div class="flex justify-end space-x-4">
                        <button type="button" onclick="resetForm()" class="px-6 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors">
                            Cancelar
                        </button>
                        <button type="submit" class="px-6 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-lg transition-colors">
                            <i class="fas fa-save mr-2"></i>
                            Guardar Configuración
                        </button>
                    </div>
                </form>

                <!-- Payment Methods Status -->
                <div class="mt-8 bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-6">Estado de Métodos de Pago</h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                        <div class="p-4 border border-gray-200 rounded-lg">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm font-medium text-gray-900">PayPal</p>
                                    <p class="text-xs text-gray-500">Pagos en línea</p>
                                </div>
                                <div class="flex items-center">
                                    <div class="w-2 h-2 rounded-full <?php echo getSetting('paypal_enabled') ? 'bg-green-500' : 'bg-red-500'; ?> mr-2"></div>
                                    <span class="text-xs <?php echo getSetting('paypal_enabled') ? 'text-green-600' : 'text-red-600'; ?>">
                                        <?php echo getSetting('paypal_enabled') ? 'Activo' : 'Inactivo'; ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="p-4 border border-gray-200 rounded-lg">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm font-medium text-gray-900">Stripe</p>
                                    <p class="text-xs text-gray-500">Tarjetas de crédito</p>
                                </div>
                                <div class="flex items-center">
                                    <div class="w-2 h-2 rounded-full <?php echo getSetting('stripe_enabled') ? 'bg-green-500' : 'bg-red-500'; ?> mr-2"></div>
                                    <span class="text-xs <?php echo getSetting('stripe_enabled') ? 'text-green-600' : 'text-red-600'; ?>">
                                        <?php echo getSetting('stripe_enabled') ? 'Activo' : 'Inactivo'; ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="p-4 border border-gray-200 rounded-lg">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm font-medium text-gray-900">Transferencia</p>
                                    <p class="text-xs text-gray-500">Pago bancario</p>
                                </div>
                                <div class="flex items-center">
                                    <div class="w-2 h-2 rounded-full <?php echo getSetting('bank_transfer_enabled') ? 'bg-green-500' : 'bg-red-500'; ?> mr-2"></div>
                                    <span class="text-xs <?php echo getSetting('bank_transfer_enabled') ? 'text-green-600' : 'text-red-600'; ?>">
                                        <?php echo getSetting('bank_transfer_enabled') ? 'Activo' : 'Inactivo'; ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="p-4 border border-gray-200 rounded-lg">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm font-medium text-gray-900">Contraentrega</p>
                                    <p class="text-xs text-gray-500">Pago al recibir</p>
                                </div>
                                <div class="flex items-center">
                                    <div class="w-2 h-2 rounded-full <?php echo getSetting('cash_on_delivery_enabled') ? 'bg-green-500' : 'bg-red-500'; ?> mr-2"></div>
                                    <span class="text-xs <?php echo getSetting('cash_on_delivery_enabled') ? 'text-green-600' : 'text-red-600'; ?>">
                                        <?php echo getSetting('cash_on_delivery_enabled') ? 'Activo' : 'Inactivo'; ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script>
        function testPaymentConnection() {
            // Aquí puedes implementar pruebas de conexión con APIs de pago
            alert('Funcionalidad de prueba de conexión en desarrollo');
        }
        
        function resetForm() {
            if (confirm('¿Estás seguro de que quieres cancelar los cambios?')) {
                location.reload();
            }
        }
        
        // Toggle visibility of sensitive fields
        document.querySelectorAll('input[type="password"]').forEach(input => {
            const container = input.parentElement;
            const toggleBtn = document.createElement('button');
            toggleBtn.type = 'button';
            toggleBtn.className = 'absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-gray-600';
            toggleBtn.innerHTML = '<i class="fas fa-eye"></i>';
            
            container.style.position = 'relative';
            container.appendChild(toggleBtn);
            
            toggleBtn.addEventListener('click', () => {
                const isPassword = input.type === 'password';
                input.type = isPassword ? 'text' : 'password';
                toggleBtn.innerHTML = isPassword ? '<i class="fas fa-eye-slash"></i>' : '<i class="fas fa-eye"></i>';
            });
        });
    </script>
</body>
</html>
