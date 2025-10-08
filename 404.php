<?php
// Página de Error 404
$pageTitle = 'Página no encontrada - Odisea Makeup Store';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .gradient-bg {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Header Simple -->
    <header class="bg-white shadow-sm">
        <div class="container mx-auto px-4 py-4">
            <div class="flex items-center justify-between">
                <a href="index.php" class="flex items-center space-x-2">
                    <div class="w-8 h-8 bg-primary-500 rounded-lg flex items-center justify-center">
                        <i class="fas fa-gem text-white text-sm"></i>
                    </div>
                    <span class="text-xl font-bold text-gray-900">Odisea</span>
                </a>
                
                <nav class="hidden md:flex items-center space-x-6">
                    <a href="index.php" class="text-gray-700 hover:text-primary-500 transition-colors">Inicio</a>
                    <a href="catalogo.php" class="text-gray-700 hover:text-primary-500 transition-colors">Catálogo</a>
                    <a href="ofertas.php" class="text-gray-700 hover:text-primary-500 transition-colors">Ofertas</a>
                </nav>
            </div>
        </div>
    </header>

    <!-- Contenido Principal -->
    <main class="flex-1">
        <div class="min-h-screen flex items-center justify-center px-4">
            <div class="max-w-lg w-full text-center">
                <!-- Icono de Error -->
                <div class="mb-8">
                    <div class="inline-flex items-center justify-center w-24 h-24 bg-red-100 rounded-full mb-4">
                        <i class="fas fa-exclamation-triangle text-red-500 text-3xl"></i>
                    </div>
                </div>

                <!-- Mensaje de Error -->
                <h1 class="text-6xl font-bold text-gray-900 mb-4">404</h1>
                <h2 class="text-2xl font-semibold text-gray-800 mb-4">Página no encontrada</h2>
                <p class="text-gray-600 mb-8 leading-relaxed">
                    Lo sentimos, la página que estás buscando no existe o ha sido movida. 
                    Puede que el enlace esté roto o que hayas ingresado una URL incorrecta.
                </p>

                <!-- Botones de Acción -->
                <div class="space-y-4 sm:space-y-0 sm:space-x-4 sm:flex sm:justify-center">
                    <a href="index.php" 
                       class="inline-flex items-center justify-center px-6 py-3 border border-transparent text-base font-medium rounded-md text-white bg-primary-600 hover:bg-primary-700 transition-colors duration-200">
                        <i class="fas fa-home mr-2"></i>
                        Volver al Inicio
                    </a>
                    
                    <a href="catalogo.php" 
                       class="inline-flex items-center justify-center px-6 py-3 border border-gray-300 text-base font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 transition-colors duration-200">
                        <i class="fas fa-shopping-bag mr-2"></i>
                        Ver Catálogo
                    </a>
                </div>

                <!-- Enlaces Útiles -->
                <div class="mt-12 pt-8 border-t border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">¿Qué te gustaría hacer?</h3>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-sm">
                        <a href="catalogo.php?categoria=rostro" class="flex items-center justify-center py-3 px-4 bg-pink-50 text-pink-700 rounded-lg hover:bg-pink-100 transition-colors">
                            <i class="fas fa-palette mr-2"></i>
                            Maquillaje Rostro
                        </a>
                        <a href="catalogo.php?categoria=ojos" class="flex items-center justify-center py-3 px-4 bg-blue-50 text-blue-700 rounded-lg hover:bg-blue-100 transition-colors">
                            <i class="fas fa-eye mr-2"></i>
                            Maquillaje Ojos
                        </a>
                        <a href="ofertas.php" class="flex items-center justify-center py-3 px-4 bg-red-50 text-red-700 rounded-lg hover:bg-red-100 transition-colors">
                            <i class="fas fa-tags mr-2"></i>
                            Ver Ofertas
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Footer Simple -->
    <footer class="bg-gray-900 text-white py-8">
        <div class="container mx-auto px-4 text-center">
            <div class="flex items-center justify-center space-x-2 mb-4">
                <div class="w-6 h-6 bg-primary-500 rounded flex items-center justify-center">
                    <i class="fas fa-gem text-white text-xs"></i>
                </div>
                <span class="text-lg font-semibold">Odisea Makeup Store</span>
            </div>
            <p class="text-gray-400 text-sm">
                © <?php echo date('Y'); ?> Odisea Makeup Store. Todos los derechos reservados.
            </p>
        </div>
    </footer>

    <script>
        // Configuración de Tailwind CSS para colores personalizados
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
</body>
</html>
