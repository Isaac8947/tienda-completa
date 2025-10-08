<?php
session_start();
require_once 'config/config.php';
require_once 'config/global-settings.php';
require_once 'models/Customer.php';

// Redirigir si ya está autenticado
if (isset($_SESSION['user_id'])) {
    header('Location: mi-cuenta.php');
    exit;
}

$error = '';
$success = '';
$formData = [
    'first_name' => '',
    'last_name' => '',
    'email' => '',
    'phone' => ''
];

// Procesar el formulario de registro
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Recoger datos del formulario
    $formData = [
        'first_name' => trim($_POST['first_name']),
        'last_name' => trim($_POST['last_name']),
        'email' => trim($_POST['email']),
        'phone' => trim($_POST['phone']),
        'password' => $_POST['password'],
        'confirm_password' => $_POST['confirm_password']
    ];
    
    // Validaciones
    if (empty($formData['first_name']) || empty($formData['last_name']) || empty($formData['email']) || 
        empty($formData['phone']) || empty($formData['password']) || empty($formData['confirm_password'])) {
        $error = 'Por favor, completa todos los campos.';
    } elseif (!filter_var($formData['email'], FILTER_VALIDATE_EMAIL)) {
        $error = 'Por favor, introduce un email válido.';
    } elseif ($formData['password'] !== $formData['confirm_password']) {
        $error = 'Las contraseñas no coinciden.';
    } elseif (strlen($formData['password']) < 6) {
        $error = 'La contraseña debe tener al menos 6 caracteres.';
    } else {
        // Verificar si el email ya existe
        $customerModel = new Customer();
        $existingCustomer = $customerModel->getCustomerByEmail($formData['email']);
        
        if ($existingCustomer) {
            $error = 'Este email ya está registrado. Por favor, utiliza otro o inicia sesión.';
        } else {
            // Crear el nuevo cliente
            $hashedPassword = password_hash($formData['password'], PASSWORD_DEFAULT);
            
            $newCustomer = [
                'first_name' => $formData['first_name'],
                'last_name' => $formData['last_name'],
                'email' => $formData['email'],
                'phone' => $formData['phone'],
                'password' => $hashedPassword,
                'status' => 1 // Activo por defecto
            ];
            
            $customerId = $customerModel->createCustomer($newCustomer);
            
            if ($customerId) {
                // Iniciar sesión automáticamente
                $_SESSION['user_id'] = $customerId;
                $_SESSION['user_name'] = $formData['first_name'];
                $_SESSION['user_email'] = $formData['email'];
                
                // Redirigir a la página de cuenta
                header('Location: mi-cuenta.php?welcome=1');
                exit;
            } else {
                $error = 'Error al crear la cuenta. Por favor, inténtalo de nuevo.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crear Cuenta - Odisea Makeup</title>
    <meta name="description" content="Regístrate en Odisea Makeup para disfrutar de una experiencia de compra personalizada y acceder a ofertas exclusivas.">
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?php echo ASSETS_URL; ?>/css/style.css">
    
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
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
                            50: '#fff7ed',
                            100: '#ffedd5',
                            200: '#fed7aa',
                            300: '#fdba74',
                            400: '#fb923c',
                            500: '#f97316',
                            600: '#ea580c',
                            700: '#c2410c',
                            800: '#9a3412',
                            900: '#7c2d12'
                        }
                    },
                    fontFamily: {
                        'sans': ['Poppins', 'sans-serif']
                    }
                }
            }
        }
    </script>
</head>
<body class="font-sans bg-gray-50">
    <?php include 'includes/global-header.php'; ?>
    <?php include 'includes/header.php'; ?>

    <main class="container mx-auto px-4 py-12">
        <div class="max-w-2xl mx-auto">
            <div class="text-center mb-8">
                <h1 class="text-3xl font-bold text-gray-900 mb-2">Crear Cuenta</h1>
                <p class="text-gray-600">Únete a Odisea y disfruta de una experiencia de compra personalizada</p>
            </div>
            
            <?php if ($error): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
                    <?php echo $success; ?>
                </div>
            <?php endif; ?>
            
            <div class="bg-white rounded-xl shadow-md p-8">
                <form action="register.php" method="post">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="first_name" class="block text-sm font-medium text-gray-700 mb-2">Nombre</label>
                            <input 
                                type="text" 
                                id="first_name" 
                                name="first_name" 
                                value="<?php echo htmlspecialchars($formData['first_name']); ?>"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                                required
                            >
                        </div>
                        
                        <div>
                            <label for="last_name" class="block text-sm font-medium text-gray-700 mb-2">Apellido</label>
                            <input 
                                type="text" 
                                id="last_name" 
                                name="last_name" 
                                value="<?php echo htmlspecialchars($formData['last_name']); ?>"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                                required
                            >
                        </div>
                        
                        <div>
                            <label for="email" class="block text-sm font-medium text-gray-700 mb-2">Email</label>
                            <input 
                                type="email" 
                                id="email" 
                                name="email" 
                                value="<?php echo htmlspecialchars($formData['email']); ?>"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                                required
                            >
                        </div>
                        
                        <div>
                            <label for="phone" class="block text-sm font-medium text-gray-700 mb-2">Teléfono</label>
                            <input 
                                type="tel" 
                                id="phone" 
                                name="phone" 
                                value="<?php echo htmlspecialchars($formData['phone']); ?>"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                                required
                            >
                        </div>
                        
                        <div>
                            <label for="password" class="block text-sm font-medium text-gray-700 mb-2">Contraseña</label>
                            <input 
                                type="password" 
                                id="password" 
                                name="password" 
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                                required
                                minlength="6"
                            >
                            <p class="text-xs text-gray-500 mt-1">Mínimo 6 caracteres</p>
                        </div>
                        
                        <div>
                            <label for="confirm_password" class="block text-sm font-medium text-gray-700 mb-2">Confirmar Contraseña</label>
                            <input 
                                type="password" 
                                id="confirm_password" 
                                name="confirm_password" 
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                                required
                                minlength="6"
                            >
                        </div>
                    </div>
                    
                    <div class="mt-6">
                        <div class="flex items-start">
                            <input type="checkbox" id="terms" name="terms" class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded mt-1" required>
                            <label for="terms" class="ml-2 block text-sm text-gray-700">
                                Acepto los <a href="terminos.php" class="text-primary-600 hover:text-primary-500">Términos y Condiciones</a> y la <a href="privacidad.php" class="text-primary-600 hover:text-primary-500">Política de Privacidad</a>
                            </label>
                        </div>
                    </div>
                    
                    <div class="mt-8">
                        <button 
                            type="submit" 
                            class="w-full bg-gradient-to-r from-primary-500 to-secondary-500 text-white py-3 rounded-lg font-semibold hover:shadow-lg transform hover:scale-105 transition-all duration-300"
                        >
                            Crear Cuenta
                        </button>
                    </div>
                </form>
                
                <div class="mt-6">
                    <div class="relative">
                        <div class="absolute inset-0 flex items-center">
                            <div class="w-full border-t border-gray-300"></div>
                        </div>
                        <div class="relative flex justify-center text-sm">
                            <span class="px-2 bg-white text-gray-500">O regístrate con</span>
                        </div>
                    </div>
                    
                    <div class="mt-6 grid grid-cols-2 gap-3">
                        <a href="#" class="flex items-center justify-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                            <i class="fab fa-google text-red-500 mr-2"></i>
                            Google
                        </a>
                        <a href="#" class="flex items-center justify-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                            <i class="fab fa-facebook text-blue-600 mr-2"></i>
                            Facebook
                        </a>
                    </div>
                </div>
                
                <div class="mt-6 text-center">
                    <p class="text-sm text-gray-600">
                        ¿Ya tienes una cuenta? 
                        <a href="login.php" class="font-medium text-primary-600 hover:text-primary-500">Inicia Sesión</a>
                    </p>
                </div>
            </div>
        </div>
    </main>

    <?php include 'includes/global-footer.php'; ?>
    <?php include 'includes/footer.php'; ?>
    
    <script src="<?php echo ASSETS_URL; ?>/js/main.js"></script>
</body>
</html>
