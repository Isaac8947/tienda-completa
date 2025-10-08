<?php
require_once 'includes/security-headers.php';
require_once 'includes/CSRFProtection.php';
require_once 'includes/InputSanitizer.php';
require_once 'includes/RateLimiter.php';

// Check rate limiting
if (!RateLimiter::checkLimit('register', 5, 300)) {
    header('Location: login-new.php?error=too_many_attempts');
    exit;
}

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
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || !CSRFProtection::validateToken($_POST['csrf_token'], 'register')) {
        $error = 'Token de seguridad inválido.';
    } else {
        // Recoger y sanitizar datos del formulario
        $formData = [
            'first_name' => InputSanitizer::sanitizeString($_POST['first_name'], 50),
            'last_name' => InputSanitizer::sanitizeString($_POST['last_name'], 50),
            'email' => InputSanitizer::sanitizeEmail($_POST['email']),
            'phone' => InputSanitizer::sanitizeString($_POST['phone'], 20),
            'password' => InputSanitizer::sanitizeString($_POST['password'], 100),
            'confirm_password' => InputSanitizer::sanitizeString($_POST['confirm_password'], 100)
        ];
        
        // Validaciones
        if (empty($formData['first_name']) || empty($formData['last_name']) || empty($formData['email']) || 
            empty($formData['phone']) || empty($formData['password']) || empty($formData['confirm_password'])) {
            $error = 'Por favor, completa todos los campos.';
        } elseif (!filter_var($formData['email'], FILTER_VALIDATE_EMAIL)) {
            $error = 'Por favor, introduce un email válido.';
        } elseif ($formData['password'] !== $formData['confirm_password']) {
            $error = 'Las contraseñas no coinciden.';
        } elseif (strlen($formData['password']) < 8) {
            $error = 'La contraseña debe tener al menos 8 caracteres.';
        } elseif (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)/', $formData['password'])) {
            $error = 'La contraseña debe contener al menos una letra minúscula, una mayúscula y un número.';
        } elseif (!isset($_POST['terms']) || $_POST['terms'] !== '1') {
            $error = 'Debes aceptar los términos y condiciones.';
        } else {
            // Verificar si el email ya existe
            $customerModel = new Customer();
            $existingCustomer = $customerModel->getCustomerByEmail($formData['email']);
            
            if ($existingCustomer) {
                $error = 'Ya existe una cuenta con este email.';
            } else {
                // Crear nueva cuenta
                $hashedPassword = password_hash($formData['password'], PASSWORD_DEFAULT);
                
                $newCustomer = [
                    'first_name' => $formData['first_name'],
                    'last_name' => $formData['last_name'],
                    'email' => $formData['email'],
                    'phone' => $formData['phone'],
                    'password' => $hashedPassword,
                    'status' => 'active',
                    'created_at' => date('Y-m-d H:i:s')
                ];
                
                try {
                    $customerId = $customerModel->create($newCustomer);
                    
                    if ($customerId) {
                        // Registro exitoso - redirigir al login
                        header('Location: login-new.php?registered=success');
                        exit;
                    } else {
                        $error = 'Hubo un error al crear tu cuenta. Intenta nuevamente.';
                    }
                } catch (Exception $e) {
                    error_log("Registration error: " . $e->getMessage());
                    $error = 'Hubo un error al crear tu cuenta. Intenta nuevamente.';
                }
            }
        }
    }
}

// Generate CSRF token for the form
$csrfToken = CSRFProtection::generateToken('register');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crear Cuenta - OdiseaStore</title>
    <meta name="description" content="Crea tu cuenta en OdiseaStore y disfruta de una experiencia de compra personalizada.">
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=Playfair+Display:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
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
                            600: '#a67c76',
                            700: '#8d635d',
                            800: '#745044',
                            900: '#5b3d2b'
                        },
                        secondary: {
                            50: '#fefdfb',
                            100: '#fdf6f0',
                            200: '#f9e6d3',
                            300: '#f4d3b0',
                            400: '#eab676',
                            500: '#c4a575',
                            600: '#b39256',
                            700: '#9e7d3a',
                            800: '#896820',
                            900: '#745407'
                        }
                    },
                    fontFamily: {
                        'sans': ['Inter', 'sans-serif'],
                        'serif': ['Playfair Display', 'serif']
                    },
                    animation: {
                        'fade-in': 'fadeIn 0.5s ease-in-out',
                        'slide-up': 'slideUp 0.3s ease-out',
                        'float': 'float 6s ease-in-out infinite',
                        'pulse-soft': 'pulseSoft 2s ease-in-out infinite'
                    },
                    keyframes: {
                        fadeIn: {
                            '0%': { opacity: '0' },
                            '100%': { opacity: '1' }
                        },
                        slideUp: {
                            '0%': { opacity: '0', transform: 'translateY(20px)' },
                            '100%': { opacity: '1', transform: 'translateY(0)' }
                        },
                        float: {
                            '0%, 100%': { transform: 'translateY(0px)' },
                            '50%': { transform: 'translateY(-20px)' }
                        },
                        pulseSoft: {
                            '0%, 100%': { opacity: '1' },
                            '50%': { opacity: '0.8' }
                        }
                    }
                }
            }
        }
    </script>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- AOS Animation -->
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>

    <style>
        .glass-effect {
            background: rgba(255, 255, 255, 0.25);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.18);
        }
        
        .gradient-bg {
            background: linear-gradient(135deg, #fdf8f6 0%, #f2e8e5 25%, #eaddd7 50%, #e0cec7 75%, #d2bab0 100%);
        }
        
        .floating-shapes {
            position: absolute;
            width: 100%;
            height: 100%;
            overflow: hidden;
            z-index: 0;
        }
        
        .floating-shapes::before,
        .floating-shapes::after {
            content: '';
            position: absolute;
            border-radius: 50%;
            opacity: 0.1;
        }
        
        .floating-shapes::before {
            width: 400px;
            height: 400px;
            background: linear-gradient(45deg, #b08d80, #c4a575);
            top: -200px;
            left: -200px;
            animation: float 10s ease-in-out infinite;
        }
        
        .floating-shapes::after {
            width: 250px;
            height: 250px;
            background: linear-gradient(45deg, #c4a575, #b08d80);
            bottom: -125px;
            right: -125px;
            animation: float 8s ease-in-out infinite reverse;
        }
        
        .input-group {
            position: relative;
        }
        
        .input-group input:focus + label,
        .input-group input:not(:placeholder-shown) + label {
            transform: translateY(-32px) scale(0.9);
            color: #b08d80;
        }
        
        .input-group label {
            position: absolute;
            left: 16px;
            top: 16px;
            color: #6b7280;
            pointer-events: none;
            transition: all 0.3s ease;
            background: white;
            padding: 0 8px;
        }
        
        .password-strength {
            height: 4px;
            border-radius: 2px;
            transition: all 0.3s ease;
        }
        
        .social-btn {
            transition: all 0.3s ease;
        }
        
        .social-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }
        
        .step-indicator {
            position: relative;
        }
        
        .step-indicator::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 100%;
            width: 100%;
            height: 2px;
            background: #e5e7eb;
            transform: translateY(-50%);
            z-index: -1;
        }
        
        .step-indicator.active::after {
            background: #b08d80;
        }
    </style>
</head>
<body class="min-h-screen gradient-bg">
    <?php include 'includes/header.php'; ?>

    <main class="relative min-h-screen flex items-center justify-center py-12 px-4">
        <div class="floating-shapes"></div>
        
        <div class="relative z-10 w-full max-w-2xl">
            <!-- Header -->
            <div class="text-center mb-8" data-aos="fade-down">
                <div class="inline-flex items-center justify-center w-20 h-20 bg-white rounded-full shadow-lg mb-6">
                    <i class="fas fa-user-plus text-3xl text-primary-600"></i>
                </div>
                <h1 class="text-4xl font-serif font-bold text-gray-900 mb-2">Únete a OdiseaStore</h1>
                <p class="text-gray-600 text-lg">Crea tu cuenta y descubre productos increíbles</p>
            </div>

            <!-- Progress Steps -->
            <div class="flex items-center justify-center mb-8" data-aos="fade-up">
                <div class="flex items-center space-x-4">
                    <div class="step-indicator active flex items-center justify-center w-10 h-10 bg-primary-600 text-white rounded-full">
                        <i class="fas fa-user text-sm"></i>
                    </div>
                    <div class="flex items-center justify-center w-10 h-10 bg-gray-300 text-gray-600 rounded-full">
                        <i class="fas fa-check text-sm"></i>
                    </div>
                </div>
            </div>

            <!-- Error Message -->
            <?php if ($error): ?>
            <div class="bg-red-50 border border-red-200 text-red-800 px-6 py-4 rounded-2xl mb-6 animate-slide-up" data-aos="fade-up">
                <div class="flex items-center">
                    <i class="fas fa-exclamation-circle mr-3 text-red-600"></i>
                    <span><?php echo htmlspecialchars($error); ?></span>
                </div>
            </div>
            <?php endif; ?>

            <!-- Register Form -->
            <div class="glass-effect rounded-3xl p-8 shadow-2xl" data-aos="fade-up" data-aos-delay="200">
                <form action="register-new.php" method="post" id="registerForm">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                    
                    <!-- Personal Information -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <!-- First Name -->
                        <div class="input-group">
                            <input 
                                type="text" 
                                id="first_name" 
                                name="first_name" 
                                value="<?php echo htmlspecialchars($formData['first_name']); ?>"
                                placeholder=" "
                                required
                                class="w-full px-4 py-4 border-2 border-gray-200 rounded-2xl focus:border-primary-500 focus:outline-none transition-all duration-300 bg-white/80 backdrop-blur-sm">
                            <label for="first_name">Nombre</label>
                            <div class="absolute right-4 top-4 text-gray-400">
                                <i class="fas fa-user"></i>
                            </div>
                        </div>

                        <!-- Last Name -->
                        <div class="input-group">
                            <input 
                                type="text" 
                                id="last_name" 
                                name="last_name" 
                                value="<?php echo htmlspecialchars($formData['last_name']); ?>"
                                placeholder=" "
                                required
                                class="w-full px-4 py-4 border-2 border-gray-200 rounded-2xl focus:border-primary-500 focus:outline-none transition-all duration-300 bg-white/80 backdrop-blur-sm">
                            <label for="last_name">Apellido</label>
                            <div class="absolute right-4 top-4 text-gray-400">
                                <i class="fas fa-user"></i>
                            </div>
                        </div>
                    </div>

                    <!-- Email Field -->
                    <div class="input-group mb-6">
                        <input 
                            type="email" 
                            id="email" 
                            name="email" 
                            value="<?php echo htmlspecialchars($formData['email']); ?>"
                            placeholder=" "
                            required
                            class="w-full px-4 py-4 border-2 border-gray-200 rounded-2xl focus:border-primary-500 focus:outline-none transition-all duration-300 bg-white/80 backdrop-blur-sm">
                        <label for="email">Correo Electrónico</label>
                        <div class="absolute right-4 top-4 text-gray-400">
                            <i class="fas fa-envelope"></i>
                        </div>
                        <div id="email-feedback" class="mt-2 text-sm hidden"></div>
                    </div>

                    <!-- Phone Field -->
                    <div class="input-group mb-6">
                        <input 
                            type="tel" 
                            id="phone" 
                            name="phone" 
                            value="<?php echo htmlspecialchars($formData['phone']); ?>"
                            placeholder=" "
                            required
                            class="w-full px-4 py-4 border-2 border-gray-200 rounded-2xl focus:border-primary-500 focus:outline-none transition-all duration-300 bg-white/80 backdrop-blur-sm">
                        <label for="phone">Teléfono</label>
                        <div class="absolute right-4 top-4 text-gray-400">
                            <i class="fas fa-phone"></i>
                        </div>
                    </div>

                    <!-- Password Fields -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <!-- Password -->
                        <div class="input-group">
                            <input 
                                type="password" 
                                id="password" 
                                name="password" 
                                placeholder=" "
                                required
                                class="w-full px-4 py-4 border-2 border-gray-200 rounded-2xl focus:border-primary-500 focus:outline-none transition-all duration-300 bg-white/80 backdrop-blur-sm">
                            <label for="password">Contraseña</label>
                            <div class="absolute right-4 top-4 text-gray-400 cursor-pointer" onclick="togglePassword('password')">
                                <i class="fas fa-eye" id="toggleIcon1"></i>
                            </div>
                        </div>

                        <!-- Confirm Password -->
                        <div class="input-group">
                            <input 
                                type="password" 
                                id="confirm_password" 
                                name="confirm_password" 
                                placeholder=" "
                                required
                                class="w-full px-4 py-4 border-2 border-gray-200 rounded-2xl focus:border-primary-500 focus:outline-none transition-all duration-300 bg-white/80 backdrop-blur-sm">
                            <label for="confirm_password">Confirmar Contraseña</label>
                            <div class="absolute right-4 top-4 text-gray-400 cursor-pointer" onclick="togglePassword('confirm_password')">
                                <i class="fas fa-eye" id="toggleIcon2"></i>
                            </div>
                        </div>
                    </div>

                    <!-- Password Strength Indicator -->
                    <div class="mb-6">
                        <div class="password-strength bg-gray-200" id="passwordStrength"></div>
                        <div class="mt-2 text-sm text-gray-600" id="passwordRequirements">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                                <div id="req-length" class="flex items-center">
                                    <i class="fas fa-circle text-gray-300 text-xs mr-2"></i>
                                    <span>Mínimo 8 caracteres</span>
                                </div>
                                <div id="req-upper" class="flex items-center">
                                    <i class="fas fa-circle text-gray-300 text-xs mr-2"></i>
                                    <span>Una mayúscula</span>
                                </div>
                                <div id="req-lower" class="flex items-center">
                                    <i class="fas fa-circle text-gray-300 text-xs mr-2"></i>
                                    <span>Una minúscula</span>
                                </div>
                                <div id="req-number" class="flex items-center">
                                    <i class="fas fa-circle text-gray-300 text-xs mr-2"></i>
                                    <span>Un número</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Terms and Conditions -->
                    <div class="mb-8">
                        <label class="flex items-start cursor-pointer">
                            <input type="checkbox" name="terms" value="1" required class="sr-only">
                            <div class="relative mt-1">
                                <div class="w-5 h-5 border-2 border-gray-300 rounded bg-white transition-all duration-200 checkbox-custom"></div>
                                <div class="absolute inset-0 flex items-center justify-center text-white text-xs opacity-0 transition-opacity duration-200 checkmark">
                                    <i class="fas fa-check"></i>
                                </div>
                            </div>
                            <span class="ml-3 text-gray-700 leading-relaxed">
                                Acepto los 
                                <a href="#" class="text-primary-600 hover:text-primary-700 font-medium">términos y condiciones</a> 
                                y la 
                                <a href="#" class="text-primary-600 hover:text-primary-700 font-medium">política de privacidad</a>
                            </span>
                        </label>
                    </div>

                    <!-- Register Button -->
                    <button 
                        type="submit" 
                        class="w-full bg-gradient-to-r from-primary-600 to-primary-700 text-white py-4 rounded-2xl font-semibold text-lg hover:from-primary-700 hover:to-primary-800 focus:outline-none focus:ring-4 focus:ring-primary-300 transition-all duration-300 transform hover:scale-105 mb-6">
                        <span class="flex items-center justify-center">
                            <i class="fas fa-user-plus mr-2"></i>
                            Crear Mi Cuenta
                        </span>
                    </button>

                    <!-- Divider -->
                    <div class="relative mb-6">
                        <div class="absolute inset-0 flex items-center">
                            <div class="w-full border-t border-gray-300"></div>
                        </div>
                        <div class="relative flex justify-center text-sm">
                            <span class="px-4 bg-white text-gray-500">O regístrate con</span>
                        </div>
                    </div>

                    <!-- Social Registration Buttons -->
                    <div class="grid grid-cols-2 gap-4 mb-8">
                        <button type="button" class="social-btn flex items-center justify-center px-4 py-3 border border-gray-300 rounded-2xl hover:bg-gray-50 transition-all duration-300">
                            <i class="fab fa-google text-red-500 mr-2"></i>
                            <span class="text-gray-700 font-medium">Google</span>
                        </button>
                        <button type="button" class="social-btn flex items-center justify-center px-4 py-3 border border-gray-300 rounded-2xl hover:bg-gray-50 transition-all duration-300">
                            <i class="fab fa-facebook-f text-blue-600 mr-2"></i>
                            <span class="text-gray-700 font-medium">Facebook</span>
                        </button>
                    </div>

                    <!-- Login Link -->
                    <div class="text-center">
                        <p class="text-gray-600">
                            ¿Ya tienes cuenta? 
                            <a href="login.php" class="text-primary-600 hover:text-primary-700 font-semibold transition-colors">
                                Inicia sesión aquí
                            </a>
                        </p>
                    </div>
                </form>
            </div>

            <!-- Security Notice -->
            <div class="text-center mt-8" data-aos="fade-up" data-aos-delay="400">
                <p class="text-sm text-gray-500 flex items-center justify-center">
                    <i class="fas fa-shield-alt mr-2 text-green-500"></i>
                    Tu información está protegida con cifrado SSL
                </p>
            </div>
        </div>
    </main>

    <?php include 'includes/footer.php'; ?>

    <script>
        // Initialize AOS
        AOS.init({
            duration: 800,
            easing: 'ease-in-out',
            once: true
        });

        // Toggle password visibility
        function togglePassword(fieldId) {
            const passwordInput = document.getElementById(fieldId);
            const toggleIcon = document.getElementById(fieldId === 'password' ? 'toggleIcon1' : 'toggleIcon2');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.classList.remove('fa-eye');
                toggleIcon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                toggleIcon.classList.remove('fa-eye-slash');
                toggleIcon.classList.add('fa-eye');
            }
        }

        // Password strength checker
        document.getElementById('password').addEventListener('input', function() {
            const password = this.value;
            const strengthBar = document.getElementById('passwordStrength');
            const requirements = {
                length: password.length >= 8,
                upper: /[A-Z]/.test(password),
                lower: /[a-z]/.test(password),
                number: /\d/.test(password)
            };

            // Update requirement indicators
            Object.keys(requirements).forEach(req => {
                const element = document.getElementById(`req-${req}`);
                const icon = element.querySelector('i');
                if (requirements[req]) {
                    icon.classList.remove('fa-circle', 'text-gray-300');
                    icon.classList.add('fa-check-circle', 'text-green-500');
                    element.classList.add('text-green-600');
                    element.classList.remove('text-gray-600');
                } else {
                    icon.classList.add('fa-circle', 'text-gray-300');
                    icon.classList.remove('fa-check-circle', 'text-green-500');
                    element.classList.remove('text-green-600');
                    element.classList.add('text-gray-600');
                }
            });

            // Update strength bar
            const fulfilledCount = Object.values(requirements).filter(Boolean).length;
            const strengthPercentage = (fulfilledCount / 4) * 100;
            
            let strengthColor = 'bg-red-400';
            if (strengthPercentage >= 75) strengthColor = 'bg-green-400';
            else if (strengthPercentage >= 50) strengthColor = 'bg-yellow-400';
            else if (strengthPercentage >= 25) strengthColor = 'bg-orange-400';

            strengthBar.style.width = strengthPercentage + '%';
            strengthBar.className = `password-strength ${strengthColor}`;
        });

        // Confirm password validation
        document.getElementById('confirm_password').addEventListener('input', function() {
            const password = document.getElementById('password').value;
            const confirmPassword = this.value;
            
            if (confirmPassword && password !== confirmPassword) {
                this.classList.add('border-red-500');
                this.classList.remove('border-gray-200');
            } else {
                this.classList.remove('border-red-500');
                this.classList.add('border-gray-200');
            }
        });

        // Email validation
        document.getElementById('email').addEventListener('blur', function() {
            const email = this.value;
            const feedback = document.getElementById('email-feedback');
            
            if (email && !isValidEmail(email)) {
                feedback.textContent = 'Por favor, introduce un email válido';
                feedback.className = 'mt-2 text-sm text-red-600 block';
                this.classList.add('border-red-500');
                this.classList.remove('border-gray-200');
            } else {
                feedback.className = 'mt-2 text-sm hidden';
                this.classList.remove('border-red-500');
                this.classList.add('border-gray-200');
            }
        });

        // Custom checkbox functionality
        document.addEventListener('DOMContentLoaded', function() {
            const checkboxes = document.querySelectorAll('input[type="checkbox"]');
            
            checkboxes.forEach(checkbox => {
                const customBox = checkbox.parentNode.querySelector('.checkbox-custom');
                const checkmark = checkbox.parentNode.querySelector('.checkmark');
                
                if (customBox && checkmark) {
                    checkbox.addEventListener('change', function() {
                        if (this.checked) {
                            customBox.classList.add('bg-primary-600', 'border-primary-600');
                            customBox.classList.remove('border-gray-300');
                            checkmark.classList.remove('opacity-0');
                            checkmark.classList.add('opacity-100');
                        } else {
                            customBox.classList.remove('bg-primary-600', 'border-primary-600');
                            customBox.classList.add('border-gray-300');
                            checkmark.classList.add('opacity-0');
                            checkmark.classList.remove('opacity-100');
                        }
                    });
                }
            });
        });

        // Form validation
        document.getElementById('registerForm').addEventListener('submit', function(e) {
            const requiredFields = ['first_name', 'last_name', 'email', 'phone', 'password', 'confirm_password'];
            let isValid = true;
            
            requiredFields.forEach(fieldId => {
                const field = document.getElementById(fieldId);
                if (!field.value.trim()) {
                    isValid = false;
                    field.classList.add('border-red-500');
                }
            });
            
            const email = document.getElementById('email').value;
            if (email && !isValidEmail(email)) {
                isValid = false;
                showNotification('Por favor, introduce un email válido', 'error');
            }
            
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            if (password !== confirmPassword) {
                isValid = false;
                showNotification('Las contraseñas no coinciden', 'error');
            }
            
            if (password.length < 8) {
                isValid = false;
                showNotification('La contraseña debe tener al menos 8 caracteres', 'error');
            }
            
            const terms = document.querySelector('input[name="terms"]');
            if (!terms.checked) {
                isValid = false;
                showNotification('Debes aceptar los términos y condiciones', 'error');
            }
            
            if (!isValid) {
                e.preventDefault();
            }
        });

        // Email validation function
        function isValidEmail(email) {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return emailRegex.test(email);
        }

        // Show notification
        function showNotification(message, type = 'info') {
            const notification = document.createElement('div');
            notification.className = `fixed top-4 right-4 z-50 px-6 py-3 rounded-lg shadow-lg text-white ${
                type === 'error' ? 'bg-red-500' : 
                type === 'success' ? 'bg-green-500' : 'bg-blue-500'
            }`;
            notification.textContent = message;
            
            document.body.appendChild(notification);
            
            setTimeout(() => {
                notification.remove();
            }, 3000);
        }
    </script>
</body>
</html>
