<?php
session_start();
require_once '../config/config.php';
require_once '../models/Admin.php';

// Si ya está logueado, redirigir al dashboard
if (isset($_SESSION['admin_id'])) {
    header('Location: index.php');
    exit();
}

$error = '';

if ($_POST) {
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    
    if (empty($email) || empty($password)) {
        $error = 'Por favor complete todos los campos';
    } else {
        try {
            $admin = new Admin();
            $adminData = $admin->login($email, $password);
            
            if ($adminData) {
                $_SESSION['admin_id'] = $adminData['id'];
                $_SESSION['admin_name'] = $adminData['name'];
                $_SESSION['admin_email'] = $adminData['email'];
                $_SESSION['admin_role'] = $adminData['role'];
                
                header('Location: index.php');
                exit();
            } else {
                $error = 'Credenciales incorrectas';
            }
        } catch (Exception $e) {
            $error = 'Error del sistema: ' . $e->getMessage();
            error_log("Login error: " . $e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Odisea Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gradient-to-br from-pink-100 to-purple-100 min-h-screen flex items-center justify-center">
    <div class="bg-white p-8 rounded-2xl shadow-2xl w-full max-w-md">
        <div class="text-center mb-8">
            <div class="bg-gradient-to-r from-pink-500 to-purple-600 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4">
                <i class="fas fa-crown text-white text-2xl"></i>
            </div>
            <h1 class="text-3xl font-bold text-gray-800 mb-2">Odisea Admin</h1>
            <p class="text-gray-600">Accede al panel administrativo</p>
        </div>

        <?php if ($error): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                <i class="fas fa-exclamation-circle mr-2"></i>
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="space-y-6">
            <div>
                <label for="email" class="block text-sm font-medium text-gray-700 mb-2">
                    <i class="fas fa-envelope mr-2"></i>Email
                </label>
                <input type="email" id="email" name="email" required
                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent transition duration-200"
                       placeholder="admin@odisea.com"
                       value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
            </div>

            <div>
                <label for="password" class="block text-sm font-medium text-gray-700 mb-2">
                    <i class="fas fa-lock mr-2"></i>Contraseña
                </label>
                <div class="relative">
                    <input type="password" id="password" name="password" required
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-transparent transition duration-200"
                           placeholder="••••••••">
                    <button type="button" onclick="togglePassword()" 
                            class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-500 hover:text-gray-700">
                        <i class="fas fa-eye" id="toggleIcon"></i>
                    </button>
                </div>
            </div>

            <button type="submit" 
                    class="w-full bg-gradient-to-r from-pink-500 to-purple-600 text-white py-3 px-4 rounded-lg hover:from-pink-600 hover:to-purple-700 focus:ring-4 focus:ring-pink-200 transition duration-200 font-semibold">
                <i class="fas fa-sign-in-alt mr-2"></i>Iniciar Sesión
            </button>
        </form>

        <div class="mt-6 text-center">
            <a href="../index.php" class="text-pink-600 hover:text-pink-800 text-sm">
                <i class="fas fa-arrow-left mr-1"></i>Volver a la tienda
            </a>
        </div>
    </div>

    <script>
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
    </script>
</body>
</html>
