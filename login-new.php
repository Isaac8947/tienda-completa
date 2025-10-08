<?php
require_once 'includes/security-headers.php';
require_once 'includes/LoginProtection.php';
require_once 'includes/CSRFProtection.php';
require_once 'includes/InputSanitizer.php';
require_once 'includes/RateLimiter.php';

// Block suspicious IPs
if (RateLimiter::isBlocked()) {
    header('HTTP/1.1 403 Forbidden');
    exit('Access denied');
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
$email = '';
$success = '';

// Check for success message from registration
if (isset($_GET['registered']) && $_GET['registered'] === 'success') {
    $success = 'Cuenta creada exitosamente. Ahora puedes iniciar sesión.';
}

// Procesar el formulario de inicio de sesión
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || !CSRFProtection::validateToken($_POST['csrf_token'], 'login')) {
        $error = 'Token de seguridad inválido.';
    } else {
        $email = InputSanitizer::sanitizeEmail($_POST['email']);
        $password = InputSanitizer::sanitizeString($_POST['password'], 100);
        $remember = isset($_POST['remember']) ? true : false;
        
        if (empty($email) || empty($password)) {
            $error = 'Por favor, completa todos los campos.';
        } else {
            // Use secure login validation
            $result = LoginProtection::validateLogin($email, $password, $remember);
            
            if ($result['success']) {
                // Redirect to intended page or account page
                $redirect = $_GET['redirect'] ?? 'mi-cuenta.php';
                header('Location: ' . $redirect);
                exit;
            } else {
                $error = $result['error'];
            }
        }
    }
}

// Generate CSRF token for the form
$csrfToken = CSRFProtection::generateToken('login');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión - OdiseaStore</title>
    <meta name="description" content="Inicia sesión en tu cuenta de OdiseaStore para acceder a tus pedidos, lista de deseos y más.">
    
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
            width: 300px;
            height: 300px;
            background: linear-gradient(45deg, #b08d80, #c4a575);
            top: -150px;
            right: -150px;
            animation: float 8s ease-in-out infinite;
        }
        
        .floating-shapes::after {
            width: 200px;
            height: 200px;
            background: linear-gradient(45deg, #c4a575, #b08d80);
            bottom: -100px;
            left: -100px;
            animation: float 6s ease-in-out infinite reverse;
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
        
        .social-btn {
            transition: all 0.3s ease;
        }
        
        .social-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>
<body class="min-h-screen gradient-bg">
    <?php include 'includes/header.php'; ?>

    <main class="relative min-h-screen flex items-center justify-center py-12 px-4">
        <div class="floating-shapes"></div>
        
        <div class="relative z-10 w-full max-w-lg">
            <!-- Header -->
            <div class="text-center mb-8" data-aos="fade-down">
                <div class="inline-flex items-center justify-center w-20 h-20 bg-white rounded-full shadow-lg mb-6">
                    <i class="fas fa-user-circle text-3xl text-primary-600"></i>
                </div>
                <h1 class="text-4xl font-serif font-bold text-gray-900 mb-2">Bienvenido de Vuelta</h1>
                <p class="text-gray-600 text-lg">Inicia sesión en tu cuenta para continuar</p>
            </div>

            <!-- Success Message -->
            <?php if ($success): ?>
            <div class="bg-green-50 border border-green-200 text-green-800 px-6 py-4 rounded-2xl mb-6 animate-slide-up" data-aos="fade-up">
                <div class="flex items-center">
                    <i class="fas fa-check-circle mr-3 text-green-600"></i>
                    <span><?php echo htmlspecialchars($success); ?></span>
                </div>
            </div>
            <?php endif; ?>

            <!-- Error Message -->
            <?php if ($error): ?>
            <div class="bg-red-50 border border-red-200 text-red-800 px-6 py-4 rounded-2xl mb-6 animate-slide-up" data-aos="fade-up">
                <div class="flex items-center">
                    <i class="fas fa-exclamation-circle mr-3 text-red-600"></i>
                    <span><?php echo htmlspecialchars($error); ?></span>
                </div>
            </div>
            <?php endif; ?>

            <!-- Login Form -->
            <div class="glass-effect rounded-3xl p-8 shadow-2xl" data-aos="fade-up" data-aos-delay="200">
                <form action="login-new.php<?php echo isset($_GET['redirect']) ? '?redirect=' . urlencode($_GET['redirect']) : ''; ?>" method="post" id="loginForm">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                    
                    <!-- Email Field -->
                    <div class="input-group mb-6">
                        <input 
                            type="email" 
                            id="email" 
                            name="email" 
                            value="<?php echo htmlspecialchars($email); ?>"
                            placeholder=" "
                            required
                            class="w-full px-4 py-4 border-2 border-gray-200 rounded-2xl focus:border-primary-500 focus:outline-none transition-all duration-300 bg-white/80 backdrop-blur-sm">
                        <label for="email">Correo Electrónico</label>
                        <div class="absolute right-4 top-4 text-gray-400">
                            <i class="fas fa-envelope"></i>
                        </div>
                    </div>

                    <!-- Password Field -->
                    <div class="input-group mb-6">
                        <input 
                            type="password" 
                            id="password" 
                            name="password" 
                            placeholder=" "
                            required
                            class="w-full px-4 py-4 border-2 border-gray-200 rounded-2xl focus:border-primary-500 focus:outline-none transition-all duration-300 bg-white/80 backdrop-blur-sm">
                        <label for="password">Contraseña</label>
                        <div class="absolute right-4 top-4 text-gray-400 cursor-pointer" onclick="togglePassword()">
                            <i class="fas fa-eye" id="toggleIcon"></i>
                        </div>
                    </div>

                    <!-- Remember Me & Forgot Password -->
                    <div class="flex items-center justify-between mb-8">
                        <label class="flex items-center cursor-pointer">
                            <input type="checkbox" name="remember" class="sr-only">
                            <div class="relative">
                                <input type="checkbox" name="remember" class="sr-only">
                                <div class="w-5 h-5 border-2 border-gray-300 rounded bg-white transition-all duration-200 checkbox-custom"></div>
                                <div class="absolute inset-0 flex items-center justify-center text-white text-xs opacity-0 transition-opacity duration-200 checkmark">
                                    <i class="fas fa-check"></i>
                                </div>
                            </div>
                            <span class="ml-3 text-gray-700">Recordarme</span>
                        </label>
                        <a href="forgot-password.php" class="text-primary-600 hover:text-primary-700 font-medium transition-colors">
                            ¿Olvidaste tu contraseña?
                        </a>
                    </div>

                    <!-- Login Button -->
                    <button 
                        type="submit" 
                        class="w-full bg-gradient-to-r from-primary-600 to-primary-700 text-white py-4 rounded-2xl font-semibold text-lg hover:from-primary-700 hover:to-primary-800 focus:outline-none focus:ring-4 focus:ring-primary-300 transition-all duration-300 transform hover:scale-105 mb-6">
                        <span class="flex items-center justify-center">
                            <i class="fas fa-sign-in-alt mr-2"></i>
                            Iniciar Sesión
                        </span>
                    </button>

                    <!-- Divider -->
                    <div class="relative mb-6">
                        <div class="absolute inset-0 flex items-center">
                            <div class="w-full border-t border-gray-300"></div>
                        </div>
                        <div class="relative flex justify-center text-sm">
                            <span class="px-4 bg-white text-gray-500">O continúa con</span>
                        </div>
                    </div>

                    <!-- Social Login Buttons -->
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

                    <!-- Register Link -->
                    <div class="text-center">
                        <p class="text-gray-600">
                            ¿No tienes cuenta? 
                            <a href="register.php" class="text-primary-600 hover:text-primary-700 font-semibold transition-colors">
                                Regístrate aquí
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
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const toggleIcon = document.getElementById('toggleIcon');
            
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
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            const email = document.getElementById('email').value;
            const password = document.getElementById('password').value;
            
            if (!email || !password) {
                e.preventDefault();
                showNotification('Por favor, completa todos los campos', 'error');
                return;
            }
            
            if (!isValidEmail(email)) {
                e.preventDefault();
                showNotification('Por favor, introduce un email válido', 'error');
                return;
            }
        });

        // Email validation
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
