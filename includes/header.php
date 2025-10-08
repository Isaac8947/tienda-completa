<?php
// Asegurarse de que la sesión esté iniciada
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Función para verificar si una página está activa (solo si no existe)
if (!function_exists('isActive')) {
    function isActive($page) {
        $currentPage = basename($_SERVER['PHP_SELF']);
        return ($currentPage == $page) ? 'text-primary-500' : '';
    }
}

// Obtener categorías para el menú
$categories = [];
try {
    // Intentar usar el modelo existente si está disponible
    if (isset($categoryModel) && is_object($categoryModel)) {
        $categories = $categoryModel->getCategoryTreeWithIcons();
    } else {
        // Si no existe el modelo, crear uno nuevo
        require_once __DIR__ . '/../models/Category.php';
        $categoryModel = new Category();
        $categories = $categoryModel->getCategoryTreeWithIcons();
    }
} catch (Exception $e) {
    error_log("Error loading categories in header: " . $e->getMessage());
    $categories = [];
}

// Si no hay categorías de la DB, no usar fallback para debug
$usingFallback = empty($categories);
if ($usingFallback) {
    error_log("Header: Using fallback categories - no categories found in database");
}
?>

<body class="font-sans bg-white overflow-x-hidden">
    <!-- Header -->
    <header class="fixed top-0 left-0 right-0 z-50 transition-all duration-500" id="header">
        <!-- Top Bar -->
        <div class="bg-gradient-to-r from-primary-500 to-secondary-500 text-white py-2 text-sm">
            <div class="container mx-auto px-4">
                <div class="flex justify-between items-center">
                    <div class="flex items-center space-x-6">
                        <?php if (!empty($contactSettings['site_phone'])): ?>
                        <a href="tel:<?php echo str_replace(' ', '', $contactSettings['site_phone']); ?>" class="flex items-center space-x-2 hover:text-primary-200 transition-colors">
                            <i class="fas fa-phone text-xs"></i>
                            <span><?php echo htmlspecialchars($contactSettings['site_phone']); ?></span>
                        </a>
                        <?php endif; ?>
                        
                        <?php if (!empty($contactSettings['site_email'])): ?>
                        <a href="mailto:<?php echo htmlspecialchars($contactSettings['site_email']); ?>" class="flex items-center space-x-2 hover:text-primary-200 transition-colors">
                            <i class="fas fa-envelope text-xs"></i>
                            <span><?php echo htmlspecialchars($contactSettings['site_email']); ?></span>
                        </a>
                        <?php endif; ?>
                    </div>
                    <div class="flex items-center space-x-4">
                        <span class="text-xs">Síguenos:</span>
                        
                        <?php if (!empty($contactSettings['social_instagram'])): ?>
                        <a href="<?php echo htmlspecialchars($contactSettings['social_instagram']); ?>" target="_blank" class="hover:text-primary-200 transition-colors transform hover:scale-110" title="Instagram">
                            <i class="fab fa-instagram"></i>
                        </a>
                        <?php endif; ?>
                        
                        <?php if (!empty($contactSettings['social_facebook'])): ?>
                        <a href="<?php echo htmlspecialchars($contactSettings['social_facebook']); ?>" target="_blank" class="hover:text-primary-200 transition-colors transform hover:scale-110" title="Facebook">
                            <i class="fab fa-facebook"></i>
                        </a>
                        <?php endif; ?>
                        
                        <?php if (!empty($contactSettings['social_tiktok'])): ?>
                        <a href="<?php echo htmlspecialchars($contactSettings['social_tiktok']); ?>" target="_blank" class="hover:text-primary-200 transition-colors transform hover:scale-110" title="TikTok">
                            <i class="fab fa-tiktok"></i>
                        </a>
                        <?php endif; ?>
                        
                        <?php if (!empty($contactSettings['social_youtube'])): ?>
                        <a href="<?php echo htmlspecialchars($contactSettings['social_youtube']); ?>" target="_blank" class="hover:text-primary-200 transition-colors transform hover:scale-110" title="YouTube">
                            <i class="fab fa-youtube"></i>
                        </a>
                        <?php endif; ?>
                        
                        <?php if (!empty($contactSettings['social_twitter'])): ?>
                        <a href="<?php echo htmlspecialchars($contactSettings['social_twitter']); ?>" target="_blank" class="hover:text-primary-200 transition-colors transform hover:scale-110" title="Twitter">
                            <i class="fab fa-twitter"></i>
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Main Header -->
        <div class="glass-effect backdrop-blur-md hidden md:block">
            <div class="container mx-auto px-4 py-4">
                <div class="flex items-center justify-between">
                    <!-- Logo -->
                    <div class="flex items-center">
                        <a href="/" class="text-3xl font-serif font-bold gradient-text">
                            ElectroShop
                        </a>
                        <span class="ml-2 text-xs text-gray-500 font-light">TECH</span>
                    </div>
                    
                    <!-- Search Bar -->
                    <div class="hidden md:flex flex-1 max-w-xl mx-8">
                        <div class="relative w-full group">
                            <input type="text"
                                   placeholder="Buscar productos, marcas..."
                                   class="w-full px-6 py-4 pr-14 bg-white/80 backdrop-blur-sm border border-gray-200 rounded-2xl focus:outline-none focus:ring-2 focus:ring-primary-500/50 focus:border-primary-500 transition-all duration-300 text-gray-700 placeholder-gray-400">
                            <button class="absolute right-4 top-1/2 transform -translate-y-1/2 w-8 h-8 bg-gradient-to-r from-primary-500 to-secondary-500 rounded-xl flex items-center justify-center text-white hover:shadow-lg transition-all duration-300">
                                <i class="fas fa-search text-sm"></i>
                            </button>
                        </div>
                    </div>
                    
                    <!-- Header Actions -->
                    <div class="flex items-center space-x-6">
                        <!-- User Account -->
                        <div class="relative group">
                            <button class="flex items-center space-x-2 text-gray-700 hover:text-primary-500 transition-colors p-2 rounded-xl hover:bg-primary-50">
                                <div class="w-8 h-8 bg-gradient-to-r from-primary-500 to-secondary-500 rounded-full flex items-center justify-center text-white text-sm">
                                    <i class="fas fa-user"></i>
                                </div>
                                <span class="hidden lg:block font-medium">Mi Cuenta</span>
                            </button>
                            
                            <!-- Dropdown Menu -->
                            <div class="absolute right-0 mt-2 w-56 bg-white/95 backdrop-blur-md rounded-2xl shadow-2xl opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-300 z-50 border border-gray-100">
                                <div class="py-3">
                                    <?php if (isset($_SESSION['user_id'])): ?>
                                        <a href="mi-cuenta.php" class="flex items-center px-4 py-3 text-sm text-gray-700 hover:bg-primary-50 hover:text-primary-600 transition-colors">
                                            <i class="fas fa-user-circle mr-3 text-primary-500"></i>
                                            Mi Perfil
                                        </a>
                                        <a href="mis-pedidos.php" class="flex items-center px-4 py-3 text-sm text-gray-700 hover:bg-primary-50 hover:text-primary-600 transition-colors">
                                            <i class="fas fa-shopping-bag mr-3 text-primary-500"></i>
                                            Mis Pedidos
                                        </a>
                                        <a href="lista-deseos.php" class="flex items-center px-4 py-3 text-sm text-gray-700 hover:bg-primary-50 hover:text-primary-600 transition-colors">
                                            <i class="fas fa-heart mr-3 text-primary-500"></i>
                                            Lista de Deseos
                                        </a>
                                        <hr class="my-2 border-gray-100">
                                        <a href="logout.php" class="flex items-center px-4 py-3 text-sm text-gray-700 hover:bg-red-50 hover:text-red-600 transition-colors">
                                            <i class="fas fa-sign-out-alt mr-3 text-red-500"></i>
                                            Cerrar Sesión
                                        </a>
                                    <?php else: ?>
                                        <a href="login.php" class="flex items-center px-4 py-3 text-sm text-gray-700 hover:bg-primary-50 hover:text-primary-600 transition-colors">
                                            <i class="fas fa-sign-in-alt mr-3 text-primary-500"></i>
                                            Iniciar Sesión
                                        </a>
                                        <a href="register.php" class="flex items-center px-4 py-3 text-sm text-gray-700 hover:bg-primary-50 hover:text-primary-600 transition-colors">
                                            <i class="fas fa-user-plus mr-3 text-primary-500"></i>
                                            Registrarse
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Wishlist -->
                        <button class="relative p-2 text-gray-700 hover:text-primary-500 transition-colors rounded-xl hover:bg-primary-50">
                            <i class="fas fa-heart text-xl"></i>
                            <?php if (isset($_SESSION['wishlist']) && count($_SESSION['wishlist']) > 0): ?>
                            <span class="absolute -top-1 -right-1 bg-gradient-to-r from-red-500 to-pink-500 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center font-medium shadow-lg">
                                <?php echo count($_SESSION['wishlist']); ?>
                            </span>
                            <?php endif; ?>
                        </button>
                        
                        <!-- Shopping Cart -->
                        <a href="carrito.php" class="relative p-2 text-gray-700 hover:text-primary-500 transition-colors rounded-xl hover:bg-primary-50">
                            <i class="fas fa-shopping-bag text-xl"></i>
                            <span class="absolute -top-1 -right-1 bg-gradient-to-r from-primary-500 to-secondary-500 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center font-medium shadow-lg" id="cart-count">
                                <?php echo isset($_SESSION['cart']) ? array_sum(array_column($_SESSION['cart'], 'quantity')) : 0; ?>
                            </span>
                        </a>
                        
                        <!-- Mobile Menu Toggle -->
                        <button class="md:hidden p-2 text-gray-700 hover:text-primary-500 transition-colors rounded-xl hover:bg-primary-50" id="mobile-menu-toggle">
                            <i class="fas fa-bars text-xl"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Navigation -->
        <nav class="bg-white/90 backdrop-blur-md border-t border-gray-100 hidden md:block">
            <div class="container mx-auto px-4">
                <div class="flex items-center justify-between">
                    <!-- Categories Menu -->
                    <div class="relative group">
                        <button class="flex items-center space-x-3 px-6 py-4 text-gray-700 hover:text-primary-500 transition-colors font-medium">
                            <i class="fas fa-th-large"></i>
                            <span>Categorías</span>
                            <i class="fas fa-chevron-down text-sm transition-transform group-hover:rotate-180"></i>
                        </button>
                        
                        <!-- Mega Menu -->
                        <div class="absolute left-0 top-full w-screen max-w-6xl bg-white/95 backdrop-blur-md shadow-2xl opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-300 z-40 rounded-2xl border border-gray-100 mt-2">
                            <div class="grid grid-cols-4 gap-8 p-8">
                                <?php
                                // Cargar categorías dinámicamente desde la base de datos
                                if (!empty($categories)):
                                    foreach ($categories as $category):
                                ?>
                                <div class="group/item">
                                    <div class="flex items-center space-x-3 mb-4">
                                        <div class="w-10 h-10 bg-gradient-to-r from-primary-500 to-secondary-500 rounded-xl flex items-center justify-center text-white">
                                            <i class="fas <?php echo $category['icon']; ?>"></i>
                                        </div>
                                        <h3 class="font-semibold text-gray-900 text-lg"><?php echo $category['name']; ?></h3>
                                    </div>
                                    <ul class="space-y-3">
                                        <?php if (!empty($category['subcategories'])): ?>
                                            <?php foreach ($category['subcategories'] as $subcategory): ?>
                                            <li>
                                                <a href="categoria.php?categoria=<?php echo urlencode($subcategory['slug']); ?>" 
                                                   class="text-gray-600 hover:text-primary-500 transition-colors text-sm hover:translate-x-1 transform duration-200 block">
                                                    <?php echo $subcategory['name']; ?>
                                                </a>
                                            </li>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <li>
                                                <a href="categoria.php?categoria=<?php echo urlencode($category['slug']); ?>" 
                                                   class="text-gray-600 hover:text-primary-500 transition-colors text-sm hover:translate-x-1 transform duration-200 block">
                                                    Ver todo en <?php echo $category['name']; ?>
                                                </a>
                                            </li>
                                        <?php endif; ?>
                                    </ul>
                                </div>
                                <?php 
                                    endforeach;
                                else:
                                    // Fallback categories
                                    $fallbackCategories = [
                                        [
                                            'name' => 'Tecnología',
                                            'icon' => 'fa-laptop',
                                            'items' => ['Smartphones', 'Laptops', 'Tablets', 'Accesorios', 'Gaming']
                                        ],
                                        [
                                            'name' => 'Hogar',
                                            'icon' => 'fa-home',
                                            'items' => ['Muebles', 'Decoración', 'Cocina', 'Baño', 'Jardín']
                                        ],
                                        [
                                            'name' => 'Moda',
                                            'icon' => 'fa-tshirt',
                                            'items' => ['Ropa Mujer', 'Ropa Hombre', 'Zapatos', 'Accesorios', 'Bolsos']
                                        ],
                                        [
                                            'name' => 'Belleza',
                                            'icon' => 'fa-spa',
                                            'items' => ['Cuidado Facial', 'Maquillaje', 'Cabello', 'Fragancias', 'Cuidado Corporal']
                                        ]
                                    ];
                                    
                                    foreach ($fallbackCategories as $category):
                                ?>
                                <div class="group/item">
                                    <div class="flex items-center space-x-3 mb-4">
                                        <div class="w-10 h-10 bg-gradient-to-r from-primary-500 to-secondary-500 rounded-xl flex items-center justify-center text-white">
                                            <i class="fas <?php echo $category['icon']; ?>"></i>
                                        </div>
                                        <h3 class="font-semibold text-gray-900 text-lg"><?php echo $category['name']; ?></h3>
                                    </div>
                                    <ul class="space-y-3">
                                        <?php foreach ($category['items'] as $item): ?>
                                        <li>
                                            <a href="categoria.php?categoria=<?php echo urlencode(strtolower($item)); ?>" 
                                               class="text-gray-600 hover:text-primary-500 transition-colors text-sm hover:translate-x-1 transform duration-200 block">
                                                <?php echo $item; ?>
                                            </a>
                                        </li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                                <?php 
                                    endforeach;
                                endif; 
                                ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Main Navigation -->
                    <div class="hidden md:flex items-center space-x-8">
                        <a href="index.php" class="text-gray-700 hover:text-primary-500 transition-colors font-medium py-4 border-b-2 border-transparent hover:border-primary-500">Inicio</a>
                        <a href="nuevos.php" class="text-gray-700 hover:text-primary-500 transition-colors font-medium py-4 border-b-2 border-transparent hover:border-primary-500">Novedades</a>
                        <a href="ofertas.php" class="text-gray-700 hover:text-primary-500 transition-colors font-medium py-4 border-b-2 border-transparent hover:border-primary-500">Ofertas</a>
                        <a href="marcas.php" class="text-gray-700 hover:text-primary-500 transition-colors font-medium py-4 border-b-2 border-transparent hover:border-primary-500">Marcas</a>
                        <a href="blog.php" class="text-gray-700 hover:text-primary-500 transition-colors font-medium py-4 border-b-2 border-transparent hover:border-primary-500">Blog</a>
                    </div>
                    
                    <!-- Promo Banner -->
                    <div class="hidden lg:block">
                        <div class="bg-gradient-to-r from-primary-500 to-secondary-500 text-white px-6 py-2 rounded-full text-sm font-medium animate-glow">
                            <i class="fas fa-shipping-fast mr-2"></i>
                            Envío GRATIS en compras +$150.000
                        </div>
                    </div>
                </div>
            </div>
        </nav>
    </header>

    <!-- Mobile Header -->
    <header class="md:hidden fixed top-0 left-0 right-0 z-50 bg-white/95 backdrop-blur-md shadow-lg" id="mobile-header">
        <div class="px-4 py-3">
            <div class="flex items-center justify-between">
                <!-- Mobile Menu Button -->
                <button class="touch-target p-2 text-gray-700 hover:text-primary-500 transition-colors rounded-xl hover:bg-primary-50" id="mobile-menu-btn">
                    <i class="fas fa-bars text-xl"></i>
                </button>

                <!-- Mobile Logo -->
                <div class="flex items-center">
                    <a href="/" class="text-2xl font-serif font-bold gradient-text">
                        OdiseaStore
                    </a>
                </div>

                <!-- Mobile Actions -->
                <div class="flex items-center space-x-2">
                    <!-- Search Button -->
                    <button class="touch-target p-2 text-gray-700 hover:text-primary-500 transition-colors rounded-xl hover:bg-primary-50" id="mobile-search-btn">
                        <i class="fas fa-search text-lg"></i>
                    </button>

                    <!-- Cart Button -->
                    <a href="carrito.php" class="touch-target relative p-2 text-gray-700 hover:text-primary-500 transition-colors rounded-xl hover:bg-primary-50">
                        <i class="fas fa-shopping-bag text-lg"></i>
                        <span class="absolute -top-1 -right-1 bg-gradient-to-r from-primary-500 to-secondary-500 text-white text-xs rounded-full h-4 w-4 flex items-center justify-center font-medium shadow-lg" id="mobile-cart-count">
                            <?php echo isset($_SESSION['cart']) ? array_sum(array_column($_SESSION['cart'], 'quantity')) : 0; ?>
                        </span>
                    </a>
                </div>
            </div>
        </div>

        <!-- Mobile Search Bar (Hidden by default) -->
        <div class="px-4 pb-3 hidden" id="mobile-search-bar">
            <form action="catalogo.php" method="GET" class="relative">
                <input type="text"
                       name="q"
                       placeholder="Buscar productos, marcas..."
                       class="w-full px-4 py-3 pr-12 bg-gray-50 border border-gray-200 rounded-2xl focus:outline-none focus:ring-2 focus:ring-primary-500/50 focus:border-primary-500 transition-all duration-300 text-gray-700 placeholder-gray-400"
                       required
                       minlength="2">
                <button type="submit" class="absolute right-3 top-1/2 transform -translate-y-1/2 w-8 h-8 bg-gradient-to-r from-primary-500 to-secondary-500 rounded-xl flex items-center justify-center text-white">
                    <i class="fas fa-search text-sm"></i>
                </button>
            </form>
        </div>
    </header>

    <!-- Mobile Menu -->
    <div class="md:hidden bg-white border-t border-gray-200 hidden" id="mobile-menu">
        <div class="px-4 py-4 space-y-4">
            <!-- Mobile Search -->
            <form action="catalogo.php" method="get" class="relative">
                <input type="text" 
                       name="q"
                       placeholder="Buscar productos..." 
                       class="w-full px-4 py-3 pr-12 border border-gray-300 rounded-full focus:outline-none focus:ring-2 focus:ring-primary-500">
                <button type="submit" class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400">
                    <i class="fas fa-search"></i>
                </button>
            </form>
            
            <!-- Mobile Navigation Links -->
            <div class="space-y-2">
                <a href="index.php" class="block py-2 text-gray-700 hover:text-primary-500 transition-colors <?php echo isActive('index.php'); ?>">Inicio</a>
                <a href="catalogo.php" class="block py-2 text-gray-700 hover:text-primary-500 transition-colors <?php echo isActive('catalogo.php'); ?>">Catálogo</a>
                <a href="nuevos.php" class="block py-2 text-gray-700 hover:text-primary-500 transition-colors <?php echo isActive('nuevos.php'); ?>">Lo Más Nuevo</a>
                <a href="ofertas.php" class="block py-2 text-gray-700 hover:text-primary-500 transition-colors <?php echo isActive('ofertas.php'); ?>">Ofertas</a>
                <a href="marcas.php" class="block py-2 text-gray-700 hover:text-primary-500 transition-colors <?php echo isActive('marcas.php'); ?>">Marcas</a>
            </div>
        </div>
    </div>

    <!-- Mobile Navigation Drawer -->
    <div class="md:hidden fixed inset-0 z-40 hidden" id="mobile-menu-overlay">
        <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" id="mobile-menu-backdrop"></div>

        <div class="mobile-drawer absolute left-0 top-0 h-full w-80 bg-white shadow-2xl" id="mobile-menu-drawer">
            <div class="flex flex-col h-full">
                <!-- Drawer Header -->
                <div class="flex items-center justify-between p-6 border-b border-gray-100 bg-gradient-to-r from-primary-50 to-secondary-50">
                    <div class="flex items-center">
                        <div class="w-10 h-10 bg-gradient-to-r from-primary-500 to-secondary-500 rounded-full flex items-center justify-center text-white mr-3">
                            <i class="fas fa-user text-lg"></i>
                        </div>
                        <div>
                            <div class="font-semibold text-gray-900">
                                <?php echo isset($_SESSION['user_name']) ? htmlspecialchars($_SESSION['user_name']) : 'Invitado'; ?>
                            </div>
                            <div class="text-sm text-gray-600">Bienvenido</div>
                        </div>
                    </div>
                    <button class="touch-target p-2 text-gray-500 hover:text-gray-700 rounded-xl" id="mobile-menu-close">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>

                <!-- Drawer Content -->
                <div class="flex-1 overflow-y-auto py-6">
                    <!-- Quick Actions -->
                    <div class="px-6 mb-6">
                        <div class="grid grid-cols-2 gap-3">
                            <a href="ofertas.php" class="flex items-center justify-center p-4 bg-gradient-to-r from-red-50 to-pink-50 rounded-2xl border border-red-100 hover:shadow-md transition-all duration-300">
                                <div class="text-center">
                                    <i class="fas fa-fire text-red-500 text-xl mb-2"></i>
                                    <div class="text-sm font-semibold text-red-700">Ofertas</div>
                                </div>
                            </a>
                            <a href="lista-deseos.php" class="flex items-center justify-center p-4 bg-gradient-to-r from-pink-50 to-rose-50 rounded-2xl border border-pink-100 hover:shadow-md transition-all duration-300">
                                <div class="text-center">
                                    <i class="fas fa-heart text-pink-500 text-xl mb-2"></i>
                                    <div class="text-sm font-semibold text-pink-700">Favoritos</div>
                                </div>
                            </a>
                        </div>
                    </div>

                    <!-- Categories -->
                    <div class="px-6 mb-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                            <i class="fas fa-th-large text-primary-500 mr-3"></i>
                            Categorías
                            <?php if ($usingFallback): ?>
                            <span class="ml-2 text-xs bg-red-100 text-red-600 px-2 py-1 rounded-full">Demo</span>
                            <?php endif; ?>
                        </h3>
                        <div class="space-y-2">
                            <?php
                            // Categorías dinámicas desde la base de datos
                            if (!empty($categories)):
                                foreach ($categories as $category):
                            ?>
                            <a href="categoria.php?categoria=<?php echo urlencode($category['slug']); ?>" class="flex items-center p-3 rounded-xl hover:bg-primary-50 transition-colors duration-300 group">
                                <i class="fas <?php echo $category['icon']; ?> text-primary-500 mr-4 w-5"></i>
                                <span class="font-medium text-gray-700 group-hover:text-primary-600"><?php echo $category['name']; ?></span>
                                <i class="fas fa-chevron-right text-gray-400 ml-auto group-hover:text-primary-500 transition-colors"></i>
                            </a>
                            <?php 
                                endforeach;
                            else: 
                                // Fallback categories
                                $fallbackCategories = [
                                    ['name' => 'Tecnología', 'icon' => 'fa-laptop', 'slug' => 'tecnologia'],
                                    ['name' => 'Hogar', 'icon' => 'fa-home', 'slug' => 'hogar'],
                                    ['name' => 'Moda', 'icon' => 'fa-tshirt', 'slug' => 'moda'],
                                    ['name' => 'Belleza', 'icon' => 'fa-spa', 'slug' => 'belleza'],
                                    ['name' => 'Deportes', 'icon' => 'fa-dumbbell', 'slug' => 'deportes']
                                ];
                                foreach ($fallbackCategories as $category):
                            ?>
                            <a href="categoria.php?categoria=<?php echo $category['slug']; ?>" class="flex items-center p-3 rounded-xl hover:bg-primary-50 transition-colors duration-300 group">
                                <i class="fas <?php echo $category['icon']; ?> text-primary-500 mr-4 w-5"></i>
                                <span class="font-medium text-gray-700 group-hover:text-primary-600"><?php echo $category['name']; ?></span>
                                <i class="fas fa-chevron-right text-gray-400 ml-auto group-hover:text-primary-500 transition-colors"></i>
                            </a>
                            <?php 
                                endforeach;
                            endif; 
                            ?>
                        </div>
                    </div>

                    <!-- Main Navigation -->
                    <div class="px-6 mb-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                            <i class="fas fa-compass text-secondary-500 mr-3"></i>
                            Navegación
                        </h3>
                        <div class="space-y-2">
                            <a href="index.php" class="flex items-center p-3 rounded-xl hover:bg-secondary-50 transition-colors duration-300 group">
                                <i class="fas fa-home text-secondary-500 mr-4 w-5"></i>
                                <span class="font-medium text-gray-700 group-hover:text-secondary-600">Inicio</span>
                            </a>
                            <a href="catalogo.php" class="flex items-center p-3 rounded-xl hover:bg-secondary-50 transition-colors duration-300 group">
                                <i class="fas fa-th-list text-secondary-500 mr-4 w-5"></i>
                                <span class="font-medium text-gray-700 group-hover:text-secondary-600">Catálogo</span>
                            </a>
                            <a href="ofertas.php" class="flex items-center p-3 rounded-xl hover:bg-secondary-50 transition-colors duration-300 group">
                                <i class="fas fa-fire text-secondary-500 mr-4 w-5"></i>
                                <span class="font-medium text-gray-700 group-hover:text-secondary-600">Ofertas</span>
                            </a>
                            <a href="marcas.php" class="flex items-center p-3 rounded-xl hover:bg-secondary-50 transition-colors duration-300 group">
                                <i class="fas fa-tags text-secondary-500 mr-4 w-5"></i>
                                <span class="font-medium text-gray-700 group-hover:text-secondary-600">Marcas</span>
                            </a>
                        </div>
                    </div>

                    <!-- Account Section -->
                    <div class="px-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                            <i class="fas fa-user-circle text-accent-500 mr-3"></i>
                            Mi Cuenta
                        </h3>
                        <div class="space-y-2">
                            <?php if (isset($_SESSION['user_id'])): ?>
                                <a href="mi-cuenta.php" class="flex items-center p-3 rounded-xl hover:bg-accent-50 transition-colors duration-300 group">
                                    <i class="fas fa-user text-accent-500 mr-4 w-5"></i>
                                    <span class="font-medium text-gray-700 group-hover:text-accent-600">Mi Perfil</span>
                                </a>
                                <a href="mis-pedidos.php" class="flex items-center p-3 rounded-xl hover:bg-accent-50 transition-colors duration-300 group">
                                    <i class="fas fa-shopping-bag text-accent-500 mr-4 w-5"></i>
                                    <span class="font-medium text-gray-700 group-hover:text-accent-600">Mis Pedidos</span>
                                </a>
                                <a href="logout.php" class="flex items-center p-3 rounded-xl hover:bg-red-50 transition-colors duration-300 group">
                                    <i class="fas fa-sign-out-alt text-red-500 mr-4 w-5"></i>
                                    <span class="font-medium text-gray-700 group-hover:text-red-600">Cerrar Sesión</span>
                                </a>
                            <?php else: ?>
                                <a href="login.php" class="flex items-center p-3 rounded-xl hover:bg-accent-50 transition-colors duration-300 group">
                                    <i class="fas fa-sign-in-alt text-accent-500 mr-4 w-5"></i>
                                    <span class="font-medium text-gray-700 group-hover:text-accent-600">Iniciar Sesión</span>
                                </a>
                                <a href="register.php" class="flex items-center p-3 rounded-xl hover:bg-accent-50 transition-colors duration-300 group">
                                    <i class="fas fa-user-plus text-accent-500 mr-4 w-5"></i>
                                    <span class="font-medium text-gray-700 group-hover:text-accent-600">Registrarse</span>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Drawer Footer -->
                <div class="border-t border-gray-100 p-6">
                    <div class="text-center">
                        <div class="text-sm text-gray-500 mb-2">Conecta con nosotros</div>
                        <div class="flex justify-center space-x-4">
                            <a href="#" class="w-8 h-8 bg-gradient-to-r from-blue-500 to-blue-600 rounded-full flex items-center justify-center text-white hover:shadow-lg transition-all">
                                <i class="fab fa-facebook-f text-sm"></i>
                            </a>
                            <a href="#" class="w-8 h-8 bg-gradient-to-r from-pink-500 to-rose-500 rounded-full flex items-center justify-center text-white hover:shadow-lg transition-all">
                                <i class="fab fa-instagram text-sm"></i>
                            </a>
                            <a href="#" class="w-8 h-8 bg-gradient-to-r from-blue-400 to-blue-500 rounded-full flex items-center justify-center text-white hover:shadow-lg transition-all">
                                <i class="fab fa-twitter text-sm"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
    // Mobile menu functionality
    document.addEventListener('DOMContentLoaded', function() {
        const mobileMenuToggle = document.getElementById('mobile-menu-toggle');
        const mobileMenuBtn = document.getElementById('mobile-menu-btn');
        const mobileMenuOverlay = document.getElementById('mobile-menu-overlay');
        const mobileMenuDrawer = document.getElementById('mobile-menu-drawer');
        const mobileMenuClose = document.getElementById('mobile-menu-close');
        const mobileMenuBackdrop = document.getElementById('mobile-menu-backdrop');
        const mobileSearchBtn = document.getElementById('mobile-search-btn');
        const mobileSearchBar = document.getElementById('mobile-search-bar');

        function openMobileMenu() {
            mobileMenuOverlay.classList.remove('hidden');
            setTimeout(() => {
                mobileMenuDrawer.style.transform = 'translateX(0)';
            }, 10);
        }

        function closeMobileMenu() {
            mobileMenuDrawer.style.transform = 'translateX(-100%)';
            setTimeout(() => {
                mobileMenuOverlay.classList.add('hidden');
            }, 300);
        }

        // Toggle mobile search
        function toggleMobileSearch() {
            mobileSearchBar.classList.toggle('hidden');
            if (!mobileSearchBar.classList.contains('hidden')) {
                const input = mobileSearchBar.querySelector('input');
                setTimeout(() => input.focus(), 100);
            }
        }

        // Desktop header menu toggle
        if (mobileMenuToggle) {
            mobileMenuToggle.addEventListener('click', openMobileMenu);
        }

        // Mobile header menu button
        if (mobileMenuBtn) {
            mobileMenuBtn.addEventListener('click', openMobileMenu);
        }

        // Mobile search button
        if (mobileSearchBtn) {
            mobileSearchBtn.addEventListener('click', toggleMobileSearch);
        }

        if (mobileMenuClose) {
            mobileMenuClose.addEventListener('click', closeMobileMenu);
        }

        if (mobileMenuBackdrop) {
            mobileMenuBackdrop.addEventListener('click', closeMobileMenu);
        }

        // Close menu on escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && !mobileMenuOverlay.classList.contains('hidden')) {
                closeMobileMenu();
            }
        });
    });
    </script>

    <style>
    .mobile-drawer {
        transform: translateX(-100%);
        transition: transform 0.3s ease-in-out;
    }

    .touch-target {
        min-width: 44px;
        min-height: 44px;
    }

    /* Ensure proper spacing when mobile header is shown */
    @media (max-width: 768px) {
        body {
            padding-top: 70px; /* Height of mobile header */
        }
        
        .mobile-drawer {
            max-width: 320px;
        }

        /* Hide desktop header elements completely on mobile */
        .glass-effect.backdrop-blur-md.hidden.md\\:block {
            display: none !important;
        }
    }
    
    /* Desktop header spacing */
    @media (min-width: 769px) {
        body {
            padding-top: 140px; /* Height of desktop header */
        }
        
        /* Hide mobile header on desktop */
        #mobile-header {
            display: none !important;
        }
    }
    </style>
</header>
