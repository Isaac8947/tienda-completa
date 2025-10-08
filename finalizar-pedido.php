<?php
// Configuración de sesión segura
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 0); // Cambiar a 1 en HTTPS
ini_set('session.cookie_samesite', 'Strict');

session_start();

require_once 'config/database.php';
require_once 'config/global-settings.php';
require_once 'models/Product.php';

// Verificar que hay productos en el carrito
$cart = $_SESSION['cart'] ?? [];
if (empty($cart)) {
    header('Location: carrito.php?error=empty_cart');
    exit;
}

// Calcular totales del carrito
$subtotal = 0;
$cartCount = 0;
$cartItems = [];

// Crear instancia del modelo de productos
$database = new Database();
$db = $database->getConnection();
$productModel = new Product($db);

foreach ($cart as $item) {
    // Obtener información actualizada del producto
    $productId = $item['product_id'] ?? $item['id'] ?? 0;
    if ($productId > 0) {
        $product = $productModel->findById($productId);
        if ($product) {
            $itemTotal = $product['price'] * ($item['quantity'] ?? 1);
            $subtotal += $itemTotal;
            $cartCount += ($item['quantity'] ?? 1);
            
            // Determinar imagen del producto
            $productImage = 'assets/images/placeholder.svg';
            if (!empty($product['main_image'])) {
                $testImage = 'uploads/products/' . $product['main_image'];
                if (file_exists($testImage)) {
                    $productImage = $testImage;
                }
            }
            
            $cartItems[] = [
                'id' => $productId,
                'name' => $product['name'],
                'price' => $product['price'],
                'quantity' => $item['quantity'] ?? 1,
                'total' => $itemTotal,
                'image' => $productImage
            ];
        }
    }
}

// Cálculos finales
$tax = $subtotal * 0.19; // 19% IVA
$shipping = $subtotal > 150000 ? 0 : 15000; // Envío gratis para compras > 150.000
$total = $subtotal + $tax + $shipping;

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <title>Finalizar Pedido - Odisea Makeup</title>
    <meta name="description" content="Completa tu pedido proporcionando tus datos de contacto y dirección de envío.">
    
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=Playfair+Display:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: {
                            50: '#fdf8f6',
                            100: '#f2e8e5',
                            200: '#eaddd7',
                            300: '#e0cec7',
                            400: '#d2bab0',
                            500: '#b08d80',
                            600: '#a07870',
                            700: '#8b6456',
                            800: '#6d4c41',
                            900: '#4e342e'
                        },
                        secondary: {
                            50: '#fefbf3',
                            100: '#fdf4e1',
                            200: '#fae8c2',
                            300: '#f7d794',
                            400: '#f2c464',
                            500: '#c4a575',
                            600: '#b5966a',
                            700: '#9a7f56',
                            800: '#7d6544',
                            900: '#634f35'
                        }
                    }
                }
            }
        }
    </script>
</head>

<body class="bg-gray-50 font-sans">
    <!-- Header -->
    <?php include 'includes/header.php'; ?>

    <!-- Main Content -->
    <main class="pt-32 pb-12">
        <div class="container mx-auto px-4 max-w-6xl">
            
            <!-- Breadcrumb -->
            <nav class="text-sm mb-8" aria-label="Breadcrumb">
                <ol class="flex items-center space-x-2">
                    <li><a href="index.php" class="text-gray-500 hover:text-primary-600">Inicio</a></li>
                    <li><i class="fas fa-chevron-right text-gray-400 text-xs"></i></li>
                    <li><a href="carrito.php" class="text-gray-500 hover:text-primary-600">Carrito</a></li>
                    <li><i class="fas fa-chevron-right text-gray-400 text-xs"></i></li>
                    <li class="text-primary-600 font-medium">Finalizar Pedido</li>
                </ol>
            </nav>

            <div class="grid lg:grid-cols-3 gap-8">
                
                <!-- Formulario de datos -->
                <div class="lg:col-span-2">
                    <div class="bg-white rounded-2xl shadow-lg p-8">
                        <div class="flex items-center mb-6">
                            <div class="w-10 h-10 bg-primary-500 text-white rounded-full flex items-center justify-center mr-4">
                                <i class="fas fa-user"></i>
                            </div>
                            <h2 class="text-2xl font-bold text-gray-800">Datos de Contacto</h2>
                        </div>

                        <form id="orderForm" action="procesar-pedido.php" method="POST">
                            <!-- Datos personales -->
                            <div class="grid md:grid-cols-2 gap-6 mb-6">
                                <div>
                                    <label for="firstName" class="block text-sm font-medium text-gray-700 mb-2">
                                        Nombres <span class="text-red-500">*</span>
                                    </label>
                                    <input type="text" 
                                           id="firstName" 
                                           name="firstName" 
                                           required
                                           class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition-colors"
                                           placeholder="Tu nombre">
                                </div>
                                <div>
                                    <label for="lastName" class="block text-sm font-medium text-gray-700 mb-2">
                                        Apellidos <span class="text-red-500">*</span>
                                    </label>
                                    <input type="text" 
                                           id="lastName" 
                                           name="lastName" 
                                           required
                                           class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition-colors"
                                           placeholder="Tus apellidos">
                                </div>
                            </div>

                            <div class="grid md:grid-cols-2 gap-6 mb-6">
                                <div>
                                    <label for="phone" class="block text-sm font-medium text-gray-700 mb-2">
                                        Número de Teléfono <span class="text-red-500">*</span>
                                    </label>
                                    <input type="tel" 
                                           id="phone" 
                                           name="phone" 
                                           required
                                           pattern="[0-9]{10}"
                                           class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition-colors"
                                           placeholder="3001234567">
                                    <p class="text-xs text-gray-500 mt-1">10 dígitos sin espacios</p>
                                </div>
                                <div>
                                    <label for="cedula" class="block text-sm font-medium text-gray-700 mb-2">
                                        Cédula de Ciudadanía
                                    </label>
                                    <input type="text" 
                                           id="cedula" 
                                           name="cedula"
                                           class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition-colors"
                                           placeholder="12345678 (opcional)">
                                </div>
                            </div>

                            <!-- Email -->
                            <div class="mb-6">
                                <label for="email" class="block text-sm font-medium text-gray-700 mb-2">
                                    Correo Electrónico <span class="text-red-500">*</span>
                                </label>
                                <input type="email" 
                                       id="email" 
                                       name="email" 
                                       required
                                       class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition-colors"
                                       placeholder="tu@email.com">
                            </div>

                            <!-- Dirección de envío -->
                            <div class="border-t pt-8 mt-8">
                                <div class="flex items-center mb-6">
                                    <div class="w-10 h-10 bg-secondary-500 text-white rounded-full flex items-center justify-center mr-4">
                                        <i class="fas fa-map-marker-alt"></i>
                                    </div>
                                    <h3 class="text-xl font-semibold text-gray-800">Dirección de Envío</h3>
                                </div>

                                <div class="grid md:grid-cols-2 gap-6 mb-6">
                                    <div>
                                        <label for="department" class="block text-sm font-medium text-gray-700 mb-2">
                                            Departamento <span class="text-red-500">*</span>
                                        </label>
                                        <select id="department" 
                                                name="department" 
                                                required
                                                class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition-colors">
                                            <option value="">Selecciona tu departamento</option>
                                            <option value="Amazonas">Amazonas</option>
                                            <option value="Antioquia">Antioquia</option>
                                            <option value="Arauca">Arauca</option>
                                            <option value="Atlántico">Atlántico</option>
                                            <option value="Bolívar">Bolívar</option>
                                            <option value="Boyacá">Boyacá</option>
                                            <option value="Caldas">Caldas</option>
                                            <option value="Caquetá">Caquetá</option>
                                            <option value="Casanare">Casanare</option>
                                            <option value="Cauca">Cauca</option>
                                            <option value="Cesar">Cesar</option>
                                            <option value="Chocó">Chocó</option>
                                            <option value="Córdoba">Córdoba</option>
                                            <option value="Cundinamarca">Cundinamarca</option>
                                            <option value="Guainía">Guainía</option>
                                            <option value="Guaviare">Guaviare</option>
                                            <option value="Huila">Huila</option>
                                            <option value="La Guajira">La Guajira</option>
                                            <option value="Magdalena">Magdalena</option>
                                            <option value="Meta">Meta</option>
                                            <option value="Nariño">Nariño</option>
                                            <option value="Norte de Santander">Norte de Santander</option>
                                            <option value="Putumayo">Putumayo</option>
                                            <option value="Quindío">Quindío</option>
                                            <option value="Risaralda">Risaralda</option>
                                            <option value="San Andrés y Providencia">San Andrés y Providencia</option>
                                            <option value="Santander">Santander</option>
                                            <option value="Sucre">Sucre</option>
                                            <option value="Tolima">Tolima</option>
                                            <option value="Valle del Cauca">Valle del Cauca</option>
                                            <option value="Vaupés">Vaupés</option>
                                            <option value="Vichada">Vichada</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label for="city" class="block text-sm font-medium text-gray-700 mb-2">
                                            Ciudad <span class="text-red-500">*</span>
                                        </label>
                                        <input type="text" 
                                               id="city" 
                                               name="city" 
                                               required
                                               class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition-colors"
                                               placeholder="Tu ciudad">
                                    </div>
                                </div>

                                <div class="mb-6">
                                    <label for="address" class="block text-sm font-medium text-gray-700 mb-2">
                                        Dirección Completa <span class="text-red-500">*</span>
                                    </label>
                                    <textarea id="address" 
                                              name="address" 
                                              required
                                              rows="3"
                                              class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition-colors"
                                              placeholder="Calle 123 #45-67, Apartamento 8B, Barrio Centro"></textarea>
                                    <p class="text-xs text-gray-500 mt-1">Incluye todos los detalles: calle, número, apartamento, barrio</p>
                                </div>
                            </div>

                            <!-- Notas adicionales -->
                            <div class="border-t pt-8 mt-8">
                                <label for="notes" class="block text-sm font-medium text-gray-700 mb-2">
                                    Notas o Comentarios (Opcional)
                                </label>
                                <textarea id="notes" 
                                          name="notes" 
                                          rows="3"
                                          class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition-colors"
                                          placeholder="Instrucciones especiales, referencias de ubicación, etc."></textarea>
                            </div>

                            <!-- Términos y condiciones -->
                            <div class="mt-8">
                                <label class="flex items-start">
                                    <input type="checkbox" 
                                           id="terms" 
                                           name="terms" 
                                           required
                                           class="w-4 h-4 text-primary-600 border-gray-300 rounded focus:ring-primary-500 mt-1">
                                    <span class="ml-3 text-sm text-gray-600">
                                        Acepto los <a href="#" class="text-primary-600 hover:text-primary-700 underline">términos y condiciones</a> 
                                        y autorizo el procesamiento de mis datos personales conforme a la 
                                        <a href="#" class="text-primary-600 hover:text-primary-700 underline">política de privacidad</a>.
                                        <span class="text-red-500">*</span>
                                    </span>
                                </label>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Resumen del pedido -->
                <div class="lg:col-span-1">
                    <div class="bg-white rounded-2xl shadow-lg p-6 sticky top-8">
                        <h3 class="text-xl font-semibold text-gray-800 mb-6">Resumen del Pedido</h3>
                        
                        <!-- Items del carrito -->
                        <div class="space-y-4 mb-6">
                            <?php foreach ($cartItems as $item): ?>
                            <div class="flex items-center space-x-3">
                                <div class="w-16 h-16 bg-gray-100 rounded-lg overflow-hidden">
                                    <img src="<?php echo htmlspecialchars($item['image'] ?? 'assets/images/placeholder.svg'); ?>" 
                                         alt="<?php echo htmlspecialchars($item['name'] ?? 'Producto'); ?>"
                                         class="w-full h-full object-cover"
                                         onerror="this.onerror=null; this.src='assets/images/placeholder.svg'">
                                </div>
                                <div class="flex-1">
                                    <h4 class="font-medium text-gray-800 text-sm"><?php echo htmlspecialchars($item['name'] ?? 'Producto'); ?></h4>
                                    <p class="text-sm text-gray-600">Cantidad: <?php echo $item['quantity'] ?? 1; ?></p>
                                    <p class="font-semibold text-primary-600">$<?php echo number_format($item['total'] ?? 0, 0, ',', '.'); ?></p>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>

                        <!-- Totales -->
                        <div class="border-t pt-4 space-y-2">
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-600">Subtotal:</span>
                                <span class="font-medium">$<?php echo number_format($subtotal, 0, ',', '.'); ?></span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-600">IVA (19%):</span>
                                <span class="font-medium">$<?php echo number_format($tax, 0, ',', '.'); ?></span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-600">Envío:</span>
                                <span class="font-medium">
                                    <?php if ($shipping == 0): ?>
                                        <span class="text-green-600">¡Gratis!</span>
                                    <?php else: ?>
                                        $<?php echo number_format($shipping, 0, ',', '.'); ?>
                                    <?php endif; ?>
                                </span>
                            </div>
                            <hr class="my-2">
                            <div class="flex justify-between text-lg font-bold">
                                <span>Total:</span>
                                <span class="text-primary-600">$<?php echo number_format($total, 0, ',', '.'); ?></span>
                            </div>
                        </div>

                        <!-- Método de pago -->
                        <div class="mt-6 p-4 bg-green-50 rounded-xl border border-green-200">
                            <div class="flex items-center mb-2">
                                <i class="fas fa-truck text-green-600 mr-2"></i>
                                <span class="font-semibold text-green-800">Pago Contra Entrega</span>
                            </div>
                            <p class="text-sm text-green-700">
                                Pagas cuando recibas tu pedido en la puerta de tu casa. 
                                Aceptamos efectivo y transferencias.
                            </p>
                        </div>

                        <!-- Botón finalizar -->
                        <button type="submit" 
                                form="orderForm"
                                id="submitOrder"
                                class="w-full mt-6 bg-gradient-to-r from-primary-500 to-secondary-500 text-white py-4 px-6 rounded-xl font-semibold hover:from-primary-600 hover:to-secondary-600 transition-all duration-300 transform hover:scale-105 flex items-center justify-center">
                            <i class="fab fa-whatsapp mr-2"></i>
                            Finalizar Pedido por WhatsApp
                        </button>
                        
                        <p class="text-xs text-center text-gray-500 mt-3">
                            Al finalizar, serás redirigido a WhatsApp con tu pedido
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Loading Modal -->
    <div id="loadingModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
        <div class="bg-white rounded-2xl p-8 text-center max-w-sm mx-4">
            <div class="animate-spin rounded-full h-16 w-16 border-b-4 border-primary-500 mx-auto mb-4"></div>
            <h3 class="text-lg font-semibold text-gray-800 mb-2">Procesando tu pedido...</h3>
            <p class="text-gray-600">Te redirigiremos a WhatsApp en un momento</p>
        </div>
    </div>

    <!-- Footer -->
    <?php include 'includes/footer.php'; ?>

    <!-- JavaScript -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('orderForm');
            const submitBtn = document.getElementById('submitOrder');
            const loadingModal = document.getElementById('loadingModal');
            
            // Formatear número de teléfono
            const phoneInput = document.getElementById('phone');
            phoneInput.addEventListener('input', function(e) {
                // Solo permitir números
                let value = e.target.value.replace(/\D/g, '');
                if (value.length > 10) {
                    value = value.substring(0, 10);
                }
                e.target.value = value;
            });
            
            // Manejar envío del formulario
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                
                // Validar formulario
                if (!form.checkValidity()) {
                    form.reportValidity();
                    return;
                }
                
                // Mostrar loading
                loadingModal.classList.remove('hidden');
                submitBtn.disabled = true;
                
                // Enviar formulario
                const formData = new FormData(form);
                
                fetch('procesar-pedido.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Redirigir a WhatsApp
                        window.location.href = data.whatsapp_url;
                    } else {
                        throw new Error(data.message || 'Error al procesar el pedido');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    loadingModal.classList.add('hidden');
                    submitBtn.disabled = false;
                    alert('Error al procesar el pedido: ' + error.message);
                });
            });
        });
    </script>
</body>
</html>
