<?php
session_start();

// Verificar autenticación de admin
require_once '../config/database.php';
require_once '../models/Admin.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

$database = new Database();
$db = $database->getConnection();
$admin = new Admin($db);

// Verificar que el admin existe
$adminData = $admin->getById($_SESSION['admin_id']);
if (!$adminData) {
    session_destroy();
    header('Location: login.php');
    exit;
}

require_once '../models/SiteSettings.php';
$settings = new SiteSettings($db);

$message = '';
$messageType = '';

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $data = [
            'whatsapp_number' => trim($_POST['whatsapp_number'] ?? ''),
            'whatsapp_message_template' => trim($_POST['whatsapp_message_template'] ?? ''),
            'store_name' => trim($_POST['store_name'] ?? ''),
            'shipping_free_threshold' => (int)($_POST['shipping_free_threshold'] ?? 0),
            'shipping_cost' => (int)($_POST['shipping_cost'] ?? 0),
            'tax_rate' => (float)($_POST['tax_rate'] ?? 0)
        ];
        
        // Validaciones
        if (empty($data['whatsapp_number'])) {
            throw new Exception('El número de WhatsApp es obligatorio');
        }
        
        if (empty($data['whatsapp_message_template'])) {
            throw new Exception('La plantilla del mensaje es obligatoria');
        }
        
        if (empty($data['store_name'])) {
            throw new Exception('El nombre de la tienda es obligatorio');
        }
        
        // Validar formato del número de WhatsApp
        if (!preg_match('/^[0-9]{10,15}$/', $data['whatsapp_number'])) {
            throw new Exception('El número de WhatsApp debe contener solo números (10-15 dígitos)');
        }
        
        if ($settings->updateWhatsAppSettings($data)) {
            $message = 'Configuración actualizada exitosamente';
            $messageType = 'success';
        } else {
            throw new Exception('Error al actualizar la configuración');
        }
        
    } catch (Exception $e) {
        $message = $e->getMessage();
        $messageType = 'error';
    }
}

// Obtener configuraciones actuales
$currentSettings = $settings->getWhatsAppSettings();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configuración de Pedidos y WhatsApp - Admin Panel</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-50">
    <div class="flex h-screen overflow-hidden">
        <?php include 'includes/sidebar.php'; ?>
        
        <div class="flex-1 flex flex-col overflow-hidden">
            <?php include 'includes/header.php'; ?>
            
            <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-50 p-4 lg:p-6">
                <!-- Page Header -->
                <div class="mb-8">
                    <div class="flex items-center justify-between">
                        <div>
                            <h1 class="text-3xl font-bold text-gray-900">
                                <i class="fas fa-whatsapp text-green-500 mr-2"></i>
                                Configuración de Pedidos y WhatsApp
                            </h1>
                            <p class="text-gray-600 mt-1">
                                Personaliza el número y mensajes de WhatsApp para los pedidos
                            </p>
                        </div>
                    </div>
                </div>
            <?php if ($message): ?>
                <div class="mb-6 p-4 rounded-lg <?php echo $messageType === 'success' ? 'bg-green-50 border-l-4 border-green-400 text-green-700' : 'bg-red-50 border-l-4 border-red-400 text-red-700'; ?>">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <i class="fas <?php echo $messageType === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?>"></i>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm"><?php echo htmlspecialchars($message); ?></p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <form method="POST" class="space-y-8">
                <!-- Configuración de WhatsApp -->
                <div class="bg-white shadow rounded-lg">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h2 class="text-lg font-medium text-gray-900">
                            <i class="fas fa-whatsapp text-green-500 mr-2"></i>
                            Configuración de WhatsApp
                        </h2>
                        <p class="text-sm text-gray-600">Configura el número de WhatsApp donde llegan los pedidos</p>
                    </div>
                    
                    <div class="px-6 py-4 space-y-6">
                        <div>
                            <label for="whatsapp_number" class="block text-sm font-medium text-gray-700">
                                Número de WhatsApp *
                            </label>
                            <div class="mt-1 relative rounded-md shadow-sm">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i class="fas fa-phone text-gray-400"></i>
                                </div>
                                <input 
                                    type="text" 
                                    id="whatsapp_number" 
                                    name="whatsapp_number" 
                                    value="<?php echo htmlspecialchars($currentSettings['whatsapp_number'] ?? '3022387799'); ?>"
                                    class="pl-10 block w-full border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                                    placeholder="573123456789"
                                    required
                                >
                            </div>
                            <p class="mt-2 text-sm text-gray-500">
                                Formato: código del país + número sin espacios ni símbolos (ej: 573123456789)
                            </p>
                        </div>
                        
                        <div>
                            <label for="store_name" class="block text-sm font-medium text-gray-700">
                                Nombre de la Tienda *
                            </label>
                            <input 
                                type="text" 
                                id="store_name" 
                                name="store_name" 
                                value="<?php echo htmlspecialchars($currentSettings['store_name'] ?? 'Odisea Makeup'); ?>"
                                class="mt-1 block w-full border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                                required
                            >
                        </div>
                    </div>
                </div>

                <!-- Configuración del Mensaje -->
                <div class="bg-white shadow rounded-lg">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h2 class="text-lg font-medium text-gray-900">
                            <i class="fas fa-message text-blue-500 mr-2"></i>
                            Plantilla del Mensaje
                        </h2>
                        <p class="text-sm text-gray-600">Personaliza el mensaje que reciben los clientes</p>
                    </div>
                    
                    <div class="px-6 py-4">
                        <div>
                            <label for="whatsapp_message_template" class="block text-sm font-medium text-gray-700">
                                Mensaje de WhatsApp *
                            </label>
                            <textarea 
                                id="whatsapp_message_template" 
                                name="whatsapp_message_template" 
                                rows="15" 
                                class="mt-1 block w-full border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 font-mono text-sm"
                                required
                            ><?php echo htmlspecialchars($currentSettings['whatsapp_message_template'] ?? SiteSettings::getDefaultTemplate()); ?></textarea>
                            
                            <div class="mt-4 p-4 bg-blue-50 rounded-lg">
                                <h4 class="text-sm font-medium text-blue-900 mb-2">Variables disponibles:</h4>
                                <div class="grid grid-cols-2 gap-2 text-sm text-blue-700">
                                    <div><code>{STORE_NAME}</code> - Nombre de la tienda</div>
                                    <div><code>{ORDER_NUMBER}</code> - Número del pedido</div>
                                    <div><code>{DATE}</code> - Fecha del pedido</div>
                                    <div><code>{CUSTOMER_NAME}</code> - Nombre completo</div>
                                    <div><code>{CUSTOMER_PHONE}</code> - Teléfono</div>
                                    <div><code>{CUSTOMER_EMAIL}</code> - Email</div>
                                    <div><code>{CUSTOMER_CEDULA}</code> - Cédula</div>
                                    <div><code>{SHIPPING_DEPARTMENT}</code> - Departamento</div>
                                    <div><code>{SHIPPING_CITY}</code> - Ciudad</div>
                                    <div><code>{SHIPPING_ADDRESS}</code> - Dirección</div>
                                    <div><code>{PRODUCTS_LIST}</code> - Lista de productos</div>
                                    <div><code>{SUBTOTAL}</code> - Subtotal</div>
                                    <div><code>{TAX}</code> - IVA</div>
                                    <div><code>{TAX_RATE}</code> - Porcentaje IVA</div>
                                    <div><code>{SHIPPING}</code> - Costo envío</div>
                                    <div><code>{TOTAL}</code> - Total</div>
                                    <div><code>{NOTES}</code> - Notas del cliente</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Configuración de Costos -->
                <div class="bg-white shadow rounded-lg">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h2 class="text-lg font-medium text-gray-900">
                            <i class="fas fa-dollar-sign text-green-500 mr-2"></i>
                            Configuración de Costos
                        </h2>
                        <p class="text-sm text-gray-600">Configura costos de envío e impuestos</p>
                    </div>
                    
                    <div class="px-6 py-4 grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div>
                            <label for="tax_rate" class="block text-sm font-medium text-gray-700">
                                IVA (%)
                            </label>
                            <input 
                                type="number" 
                                id="tax_rate" 
                                name="tax_rate" 
                                value="<?php echo htmlspecialchars($currentSettings['tax_rate'] ?? 19); ?>"
                                step="0.01"
                                min="0"
                                max="100"
                                class="mt-1 block w-full border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                            >
                        </div>
                        
                        <div>
                            <label for="shipping_cost" class="block text-sm font-medium text-gray-700">
                                Costo de Envío
                            </label>
                            <input 
                                type="number" 
                                id="shipping_cost" 
                                name="shipping_cost" 
                                value="<?php echo htmlspecialchars($currentSettings['shipping_cost'] ?? 15000); ?>"
                                min="0"
                                class="mt-1 block w-full border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                            >
                        </div>
                        
                        <div>
                            <label for="shipping_free_threshold" class="block text-sm font-medium text-gray-700">
                                Envío Gratis desde
                            </label>
                            <input 
                                type="number" 
                                id="shipping_free_threshold" 
                                name="shipping_free_threshold" 
                                value="<?php echo htmlspecialchars($currentSettings['shipping_free_threshold'] ?? 50000); ?>"
                                min="0"
                                class="mt-1 block w-full border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                            >
                        </div>
                    </div>
                </div>

                <!-- Botones de acción -->
                <div class="flex justify-end space-x-4">
                    <button 
                        type="button" 
                        onclick="window.location.href='pedidos.php'"
                        class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500"
                    >
                        <i class="fas fa-arrow-left mr-2"></i>
                        Volver a Pedidos
                    </button>
                    
                    <button 
                        type="submit"
                        class="px-6 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500"
                    >
                        <i class="fas fa-save mr-2"></i>
                        Guardar Configuración
                    </button>
                </div>
            </form>
                
            </main>
        </div>
    </div>

    <script>
        // Preview del mensaje en tiempo real
        document.getElementById('whatsapp_message_template').addEventListener('input', function() {
            // Aquí se podría agregar un preview en tiempo real si se desea
        });
    </script>
</body>
</html>
