<?php
// Asegurar que tenemos los datos del admin
if (!isset($adminData)) {
    $adminData = [
        'full_name' => 'Administrador',
        'role' => 'admin',
        'avatar' => null
    ];
}
?>

<!-- Top Header -->
<header class="admin-header sticky top-0 z-40 bg-white/95 backdrop-blur-md border-b border-gray-200/50 shadow-lg">
    <div class="flex items-center justify-between px-4 lg:px-8 py-4">
        <!-- Mobile menu button -->
        <button class="md:hidden text-gray-600 hover:text-primary-500 focus:outline-none focus:text-primary-500 transition-colors duration-300 p-2 rounded-xl hover:bg-primary-50" id="mobile-menu-btn">
            <i class="fas fa-bars text-xl"></i>
        </button>

        <!-- Logo/Brand for Admin -->
        <div class="hidden md:flex items-center mr-8">
            <div class="flex items-center">
                <div class="w-10 h-10 bg-gradient-to-r from-primary-500 to-secondary-500 rounded-xl flex items-center justify-center mr-3">
                    <i class="fas fa-crown text-white"></i>
                </div>
                <div>
                    <h1 class="text-xl font-serif font-bold gradient-text">Odisea</h1>
                    <p class="text-xs text-gray-500 font-medium">ADMIN PANEL</p>
                </div>
            </div>
        </div>

        <!-- Search Bar -->
        <div class="flex-1 max-w-2xl mx-4 lg:mx-8">
            <div class="relative group">
                <input type="text"
                       placeholder="Buscar productos, pedidos, clientes..."
                       class="w-full pl-12 pr-6 py-4 bg-white/80 backdrop-blur-sm border border-gray-200 rounded-2xl focus:outline-none focus:ring-2 focus:ring-primary-500/50 focus:border-primary-500 transition-all duration-300 text-gray-700 placeholder-gray-400 shadow-sm hover:shadow-md">
                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                    <i class="fas fa-search text-gray-400 group-focus-within:text-primary-500 transition-colors duration-300"></i>
                </div>
                <div class="absolute inset-y-0 right-0 pr-4 flex items-center">
                    <kbd class="hidden sm:inline-flex items-center px-2 py-1 text-xs font-medium text-gray-400 bg-gray-100 rounded border">
                        ⌘K
                    </kbd>
                </div>
            </div>
        </div>

        <!-- Header Actions -->
        <div class="flex items-center space-x-3">
            <!-- Notifications -->
            <div class="relative">
                <button class="relative p-3 text-gray-600 hover:text-primary-500 focus:outline-none focus:text-primary-500 transition-all duration-300 rounded-xl hover:bg-primary-50 group" id="notifications-btn">
                    <i class="fas fa-bell text-xl group-hover:scale-110 transition-transform duration-300"></i>
                    <span class="absolute -top-1 -right-1 bg-gradient-to-r from-red-500 to-pink-500 text-white text-xs rounded-full h-6 w-6 flex items-center justify-center font-semibold shadow-lg animate-pulse">3</span>
                </button>
                
                <!-- Notifications Dropdown -->
                <div class="absolute right-0 mt-3 w-96 bg-white/95 backdrop-blur-md rounded-3xl shadow-2xl border border-gray-200/50 z-50 opacity-0 invisible transform translate-y-2 transition-all duration-300" id="notifications-dropdown">
                    <!-- Header -->
                    <div class="p-6 border-b border-gray-200/50">
                        <div class="flex items-center justify-between">
                            <h3 class="text-xl font-serif font-bold text-gray-900">Notificaciones</h3>
                            <button class="text-sm text-primary-600 hover:text-primary-700 font-medium px-3 py-1 rounded-full hover:bg-primary-50 transition-all duration-300">
                                Marcar todas como leídas
                            </button>
                        </div>
                    </div>
                    
                    <!-- Notifications List -->
                    <div class="max-h-96 overflow-y-auto">
                        <!-- Notification Item -->
                        <div class="p-4 border-b border-gray-100/50 hover:bg-primary-50/50 transition-all duration-300 group">
                            <div class="flex items-start space-x-4">
                                <div class="w-12 h-12 bg-gradient-to-r from-blue-100 to-blue-200 rounded-2xl flex items-center justify-center flex-shrink-0 group-hover:scale-110 transition-transform duration-300">
                                    <i class="fas fa-shopping-cart text-blue-600"></i>
                                </div>
                                <div class="flex-1">
                                    <p class="text-sm font-semibold text-gray-900 mb-1">Nuevo pedido recibido</p>
                                    <p class="text-sm text-gray-600 mb-2">Pedido #12345 por $89.000</p>
                                    <div class="flex items-center justify-between">
                                        <p class="text-xs text-gray-400">Hace 5 minutos</p>
                                        <span class="w-2 h-2 bg-blue-500 rounded-full"></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="p-4 border-b border-gray-100/50 hover:bg-primary-50/50 transition-all duration-300 group">
                            <div class="flex items-start space-x-4">
                                <div class="w-12 h-12 bg-gradient-to-r from-red-100 to-red-200 rounded-2xl flex items-center justify-center flex-shrink-0 group-hover:scale-110 transition-transform duration-300">
                                    <i class="fas fa-exclamation-triangle text-red-600"></i>
                                </div>
                                <div class="flex-1">
                                    <p class="text-sm font-semibold text-gray-900 mb-1">Stock bajo</p>
                                    <p class="text-sm text-gray-600 mb-2">Fenty Foundation - Solo 2 unidades</p>
                                    <div class="flex items-center justify-between">
                                        <p class="text-xs text-gray-400">Hace 1 hora</p>
                                        <span class="w-2 h-2 bg-red-500 rounded-full"></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="p-4 border-b border-gray-100/50 hover:bg-primary-50/50 transition-all duration-300 group">
                            <div class="flex items-start space-x-4">
                                <div class="w-12 h-12 bg-gradient-to-r from-green-100 to-green-200 rounded-2xl flex items-center justify-center flex-shrink-0 group-hover:scale-110 transition-transform duration-300">
                                    <i class="fas fa-user-plus text-green-600"></i>
                                </div>
                                <div class="flex-1">
                                    <p class="text-sm font-semibold text-gray-900 mb-1">Nuevo cliente registrado</p>
                                    <p class="text-sm text-gray-600 mb-2">maria@email.com se ha registrado</p>
                                    <div class="flex items-center justify-between">
                                        <p class="text-xs text-gray-400">Hace 2 horas</p>
                                        <span class="w-2 h-2 bg-green-500 rounded-full"></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Footer -->
                    <div class="p-6 border-t border-gray-200/50 bg-gray-50/50 rounded-b-3xl">
                        <a href="notifications.php" class="block text-center text-sm text-primary-600 hover:text-primary-700 font-semibold py-2 px-4 rounded-xl hover:bg-primary-50 transition-all duration-300">
                            Ver todas las notificaciones
                        </a>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="relative">
                <button class="p-3 text-gray-600 hover:text-primary-500 focus:outline-none focus:text-primary-500 transition-all duration-300 rounded-xl hover:bg-primary-50 group" id="quick-actions-btn">
                    <i class="fas fa-plus-circle text-xl group-hover:scale-110 group-hover:rotate-90 transition-all duration-300"></i>
                </button>
                
                <!-- Quick Actions Dropdown -->
                <div class="absolute right-0 mt-3 w-64 bg-white/95 backdrop-blur-md rounded-3xl shadow-2xl border border-gray-200/50 z-50 opacity-0 invisible transform translate-y-2 transition-all duration-300" id="quick-actions-dropdown">
                    <div class="p-3">
                        <div class="text-xs font-semibold text-gray-500 uppercase tracking-wide px-3 py-2 mb-2">Acciones Rápidas</div>
                        
                        <a href="products-add.php" class="flex items-center px-4 py-3 text-sm text-gray-700 hover:bg-primary-50 hover:text-primary-600 rounded-2xl transition-all duration-300 group">
                            <div class="w-10 h-10 bg-gradient-to-r from-blue-100 to-blue-200 rounded-xl flex items-center justify-center mr-3 group-hover:scale-110 transition-transform duration-300">
                                <i class="fas fa-box text-blue-600"></i>
                            </div>
                            <div>
                                <div class="font-medium">Agregar Producto</div>
                                <div class="text-xs text-gray-500">Nuevo producto al catálogo</div>
                            </div>
                        </a>
                        
                        <a href="orders-add.php" class="flex items-center px-4 py-3 text-sm text-gray-700 hover:bg-primary-50 hover:text-primary-600 rounded-2xl transition-all duration-300 group">
                            <div class="w-10 h-10 bg-gradient-to-r from-green-100 to-green-200 rounded-xl flex items-center justify-center mr-3 group-hover:scale-110 transition-transform duration-300">
                                <i class="fas fa-shopping-cart text-green-600"></i>
                            </div>
                            <div>
                                <div class="font-medium">Crear Pedido</div>
                                <div class="text-xs text-gray-500">Pedido manual</div>
                            </div>
                        </a>
                        
                        <a href="customers-add.php" class="flex items-center px-4 py-3 text-sm text-gray-700 hover:bg-primary-50 hover:text-primary-600 rounded-2xl transition-all duration-300 group">
                            <div class="w-10 h-10 bg-gradient-to-r from-purple-100 to-purple-200 rounded-xl flex items-center justify-center mr-3 group-hover:scale-110 transition-transform duration-300">
                                <i class="fas fa-user-plus text-purple-600"></i>
                            </div>
                            <div>
                                <div class="font-medium">Agregar Cliente</div>
                                <div class="text-xs text-gray-500">Nuevo cliente</div>
                            </div>
                        </a>
                        
                        <a href="coupons-add.php" class="flex items-center px-4 py-3 text-sm text-gray-700 hover:bg-primary-50 hover:text-primary-600 rounded-2xl transition-all duration-300 group">
                            <div class="w-10 h-10 bg-gradient-to-r from-orange-100 to-orange-200 rounded-xl flex items-center justify-center mr-3 group-hover:scale-110 transition-transform duration-300">
                                <i class="fas fa-ticket-alt text-orange-600"></i>
                            </div>
                            <div>
                                <div class="font-medium">Crear Cupón</div>
                                <div class="text-xs text-gray-500">Descuento especial</div>
                            </div>
                        </a>
                    </div>
                </div>
            </div>

            <!-- User Profile -->
            <div class="relative">
                <button class="flex items-center space-x-3 p-2 text-gray-600 hover:text-primary-500 focus:outline-none focus:text-primary-500 transition-all duration-300 rounded-2xl hover:bg-primary-50 group" id="user-menu-btn">
                    <div class="relative">
                        <div class="w-12 h-12 bg-gradient-to-r from-primary-500 to-secondary-500 rounded-2xl flex items-center justify-center shadow-lg group-hover:scale-110 transition-transform duration-300">
                            <?php if ($adminData['avatar']): ?>
                                <img src="<?php echo htmlspecialchars($adminData['avatar']); ?>" alt="Avatar" class="w-12 h-12 rounded-2xl object-cover">
                            <?php else: ?>
                                <i class="fas fa-user text-white text-lg"></i>
                            <?php endif; ?>
                        </div>
                        <div class="absolute -bottom-1 -right-1 w-4 h-4 bg-green-500 border-2 border-white rounded-full"></div>
                    </div>
                    <div class="hidden lg:block text-left">
                        <p class="text-sm font-semibold text-gray-900"><?php echo htmlspecialchars($adminData['full_name']); ?></p>
                        <p class="text-xs text-gray-500 capitalize"><?php echo ucfirst($adminData['role']); ?></p>
                    </div>
                    <i class="fas fa-chevron-down text-sm group-hover:rotate-180 transition-transform duration-300"></i>
                </button>
                
                <!-- User Menu Dropdown -->
                <div class="absolute right-0 mt-3 w-64 bg-white/95 backdrop-blur-md rounded-3xl shadow-2xl border border-gray-200/50 z-50 opacity-0 invisible transform translate-y-2 transition-all duration-300" id="user-menu-dropdown">
                    <div class="p-3">
                        <!-- User Info Header -->
                        <div class="px-4 py-3 border-b border-gray-200/50 mb-2">
                            <div class="flex items-center space-x-3">
                                <div class="w-12 h-12 bg-gradient-to-r from-primary-500 to-secondary-500 rounded-2xl flex items-center justify-center">
                                    <?php if ($adminData['avatar']): ?>
                                        <img src="<?php echo htmlspecialchars($adminData['avatar']); ?>" alt="Avatar" class="w-12 h-12 rounded-2xl object-cover">
                                    <?php else: ?>
                                        <i class="fas fa-user text-white"></i>
                                    <?php endif; ?>
                                </div>
                                <div>
                                    <p class="font-semibold text-gray-900"><?php echo htmlspecialchars($adminData['full_name']); ?></p>
                                    <p class="text-sm text-gray-500 capitalize"><?php echo ucfirst($adminData['role']); ?></p>
                                </div>
                            </div>
                        </div>
                        
                        <a href="profile.php" class="flex items-center px-4 py-3 text-sm text-gray-700 hover:bg-primary-50 hover:text-primary-600 rounded-2xl transition-all duration-300 group">
                            <div class="w-10 h-10 bg-gradient-to-r from-blue-100 to-blue-200 rounded-xl flex items-center justify-center mr-3 group-hover:scale-110 transition-transform duration-300">
                                <i class="fas fa-user text-blue-600"></i>
                            </div>
                            <div>
                                <div class="font-medium">Mi Perfil</div>
                                <div class="text-xs text-gray-500">Configurar cuenta</div>
                            </div>
                        </a>
                        
                        <a href="settings.php" class="flex items-center px-4 py-3 text-sm text-gray-700 hover:bg-primary-50 hover:text-primary-600 rounded-2xl transition-all duration-300 group">
                            <div class="w-10 h-10 bg-gradient-to-r from-purple-100 to-purple-200 rounded-xl flex items-center justify-center mr-3 group-hover:scale-110 transition-transform duration-300">
                                <i class="fas fa-cog text-purple-600"></i>
                            </div>
                            <div>
                                <div class="font-medium">Configuración</div>
                                <div class="text-xs text-gray-500">Ajustes del sistema</div>
                            </div>
                        </a>
                        
                        <div class="border-t border-gray-200/50 my-2"></div>
                        
                        <a href="../index.php" target="_blank" class="flex items-center px-4 py-3 text-sm text-gray-700 hover:bg-primary-50 hover:text-primary-600 rounded-2xl transition-all duration-300 group">
                            <div class="w-10 h-10 bg-gradient-to-r from-green-100 to-green-200 rounded-xl flex items-center justify-center mr-3 group-hover:scale-110 transition-transform duration-300">
                                <i class="fas fa-external-link-alt text-green-600"></i>
                            </div>
                            <div>
                                <div class="font-medium">Ver Tienda</div>
                                <div class="text-xs text-gray-500">Abrir en nueva pestaña</div>
                            </div>
                        </a>
                        
                        <div class="border-t border-gray-200/50 my-2"></div>
                        
                        <a href="logout.php" class="flex items-center px-4 py-3 text-sm text-red-600 hover:bg-red-50 hover:text-red-700 rounded-2xl transition-all duration-300 group">
                            <div class="w-10 h-10 bg-gradient-to-r from-red-100 to-red-200 rounded-xl flex items-center justify-center mr-3 group-hover:scale-110 transition-transform duration-300">
                                <i class="fas fa-sign-out-alt text-red-600"></i>
                            </div>
                            <div>
                                <div class="font-medium">Cerrar Sesión</div>
                                <div class="text-xs text-red-500">Salir del panel</div>
                            </div>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</header>

<!-- Required Styles -->
<style>
    .gradient-text {
        background: linear-gradient(135deg, #b08d80, #c4a575);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }
    
    /* Dropdown animations */
    #notifications-dropdown.show,
    #quick-actions-dropdown.show,
    #user-menu-dropdown.show {
        opacity: 1;
        visibility: visible;
        transform: translateY(0);
    }
    
    /* Custom scrollbar for notifications */
    #notifications-dropdown .max-h-96::-webkit-scrollbar {
        width: 4px;
    }
    
    #notifications-dropdown .max-h-96::-webkit-scrollbar-track {
        background: #f1f5f9;
        border-radius: 2px;
    }
    
    #notifications-dropdown .max-h-96::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 2px;
    }
    
    #notifications-dropdown .max-h-96::-webkit-scrollbar-thumb:hover {
        background: #94a3b8;
    }
</style>

<!-- Required JavaScript -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Dropdown functionality
    const dropdowns = [
        { btn: 'notifications-btn', dropdown: 'notifications-dropdown' },
        { btn: 'quick-actions-btn', dropdown: 'quick-actions-dropdown' },
        { btn: 'user-menu-btn', dropdown: 'user-menu-dropdown' }
    ];
    
    dropdowns.forEach(({ btn, dropdown }) => {
        const button = document.getElementById(btn);
        const dropdownEl = document.getElementById(dropdown);
        
        if (button && dropdownEl) {
            button.addEventListener('click', function(e) {
                e.stopPropagation();
                
                // Close other dropdowns
                dropdowns.forEach(({ dropdown: otherDropdown }) => {
                    if (otherDropdown !== dropdown) {
                        document.getElementById(otherDropdown)?.classList.remove('show');
                    }
                });
                
                // Toggle current dropdown
                dropdownEl.classList.toggle('show');
            });
        }
    });
    
    // Close dropdowns when clicking outside
    document.addEventListener('click', function(e) {
        dropdowns.forEach(({ dropdown }) => {
            const dropdownEl = document.getElementById(dropdown);
            if (dropdownEl && !dropdownEl.contains(e.target)) {
                dropdownEl.classList.remove('show');
            }
        });
    });
    
    // Mobile menu functionality
    const mobileMenuBtn = document.getElementById('mobile-menu-btn');
    if (mobileMenuBtn) {
        mobileMenuBtn.addEventListener('click', function() {
            // Toggle mobile sidebar (implement based on your sidebar structure)
            const sidebar = document.querySelector('.sidebar');
            if (sidebar) {
                sidebar.classList.toggle('mobile-open');
            }
        });
    }
    
    // Search functionality with keyboard shortcut
    document.addEventListener('keydown', function(e) {
        if ((e.metaKey || e.ctrlKey) && e.key === 'k') {
            e.preventDefault();
            const searchInput = document.querySelector('input[placeholder*="Buscar"]');
            if (searchInput) {
                searchInput.focus();
            }
        }
    });
    
    // Auto-hide notifications after interaction
    const notificationItems = document.querySelectorAll('#notifications-dropdown .hover\\:bg-primary-50\\/50');
    notificationItems.forEach(item => {
        item.addEventListener('click', function() {
            // Add fade out animation
            this.style.opacity = '0.5';
            this.style.transform = 'translateX(10px)';
            
            // Remove notification dot
            const dot = this.querySelector('.bg-blue-500, .bg-red-500, .bg-green-500');
            if (dot) {
                dot.style.opacity = '0';
            }
        });
    });
});
</script>
