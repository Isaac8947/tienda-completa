<!-- Sidebar -->
<style>
/* Estilos personalizados para el scrollbar del sidebar */
#sidebar nav::-webkit-scrollbar {
    width: 6px;
}

#sidebar nav::-webkit-scrollbar-track {
    background: rgba(255, 255, 255, 0.05);
    border-radius: 3px;
}

#sidebar nav::-webkit-scrollbar-thumb {
    background: linear-gradient(135deg, rgba(176, 141, 128, 0.3), rgba(196, 165, 117, 0.3));
    border-radius: 3px;
}

#sidebar nav::-webkit-scrollbar-thumb:hover {
    background: linear-gradient(135deg, rgba(176, 141, 128, 0.5), rgba(196, 165, 117, 0.5));
}

/* Para Firefox */
#sidebar nav {
    scrollbar-width: thin;
    scrollbar-color: rgba(176, 141, 128, 0.3) rgba(255, 255, 255, 0.05);
}

.nav-item {
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
    overflow: hidden;
}

.nav-item::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(90deg, rgba(255,255,255,0.1) 0%, rgba(255,255,255,0.05) 100%);
    opacity: 0;
    transition: opacity 0.3s ease;
    z-index: 1;
}

.nav-item:hover::before {
    opacity: 1;
}

.nav-item.active {
    background: linear-gradient(135deg, rgba(255, 255, 255, 0.15), rgba(255, 255, 255, 0.08));
    border-left: 4px solid rgba(255, 255, 255, 0.6);
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
    backdrop-filter: blur(10px);
}

.nav-item.active .nav-icon {
    color: rgba(255, 255, 255, 0.9) !important;
    filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.2));
}

.nav-item.active span {
    color: rgba(255, 255, 255, 0.95) !important;
    font-weight: 600;
}

.badge-notification {
    background: linear-gradient(135deg, #ff6b6b, #ee5a52);
    animation: pulse 2s infinite;
    box-shadow: 0 0 0 0 rgba(255, 107, 107, 0.4);
}

@keyframes pulse {
    0% {
        box-shadow: 0 0 0 0 rgba(255, 107, 107, 0.4);
    }
    70% {
        box-shadow: 0 0 0 10px rgba(255, 107, 107, 0);
    }
    100% {
        box-shadow: 0 0 0 0 rgba(255, 107, 107, 0);
    }
}

.section-header {
    margin-top: 2rem;
    margin-bottom: 0.5rem;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    padding-bottom: 0.5rem;
}

.section-header:first-child {
    margin-top: 0;
}
</style>

<aside id="sidebar" class="fixed inset-y-0 left-0 z-30 w-72 bg-gradient-to-br from-gray-900 via-gray-800 to-gray-900 shadow-2xl transform -translate-x-full transition-transform duration-300 ease-in-out lg:translate-x-0 lg:static lg:inset-0">
    <!-- Header del Sidebar -->
    <div class="flex items-center justify-between h-20 px-6 bg-gradient-to-r from-gray-800/80 to-gray-900/80 backdrop-blur-sm border-b border-white/10">
        <div class="flex items-center space-x-3">
            <div class="w-10 h-10 bg-gradient-to-r from-pink-500 to-purple-600 rounded-xl flex items-center justify-center shadow-lg">
                <i class="fas fa-palette text-white text-lg"></i>
            </div>
            <div>
                <h2 class="text-white font-bold text-lg">Odisea Admin</h2>
                <p class="text-white/60 text-xs">Panel de Control</p>
            </div>
        </div>
        <button id="closeSidebar" class="lg:hidden text-white/70 hover:text-white p-2 rounded-lg hover:bg-white/10 transition-colors">
            <i class="fas fa-times text-xl"></i>
        </button>
    </div>

    <!-- Navegación -->
    <nav class="flex-1 px-4 py-6 space-y-2 overflow-y-auto">
        <!-- Dashboard -->
        <div class="space-y-3">
            <div class="section-header px-4 py-2">
                <h3 class="text-sm font-bold text-white/80 uppercase tracking-wider">Panel Principal</h3>
            </div>
            
            <div class="space-y-1">
                <a href="index.php" 
                   class="nav-item flex items-center space-x-4 py-3 px-4 rounded-2xl group <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>">
                    <div class="w-10 h-10 bg-gradient-to-r from-blue-500/20 to-cyan-500/20 rounded-xl flex items-center justify-center group-hover:from-blue-500/30 group-hover:to-cyan-500/30 transition-all duration-300">
                        <i class="fas fa-chart-line nav-icon text-blue-400 group-hover:text-blue-300"></i>
                    </div>
                    <div class="flex-1">
                        <span class="font-medium text-white/90 group-hover:text-blue-200 transition-colors duration-300">Dashboard</span>
                        <p class="text-xs text-white/50">Vista general</p>
                    </div>
                </a>
            </div>
        </div>

        <!-- Products Section -->
        <div class="space-y-3">
            <div class="section-header px-4 py-2">
                <h3 class="text-sm font-bold text-white/80 uppercase tracking-wider">Productos</h3>
            </div>
            
            <div class="space-y-1">
                <a href="products.php" 
                   class="nav-item flex items-center space-x-4 py-3 px-4 rounded-2xl group <?php echo basename($_SERVER['PHP_SELF']) == 'products.php' ? 'active' : ''; ?>">
                    <div class="w-10 h-10 bg-gradient-to-r from-purple-500/20 to-pink-500/20 rounded-xl flex items-center justify-center group-hover:from-purple-500/30 group-hover:to-pink-500/30 transition-all duration-300">
                        <i class="fas fa-box nav-icon text-purple-400 group-hover:text-purple-300"></i>
                    </div>
                    <div class="flex-1 flex items-center justify-between">
                        <div>
                            <span class="font-medium text-white/90 group-hover:text-purple-200 transition-colors duration-300">Todos los Productos</span>
                            <p class="text-xs text-white/50">Inventario general</p>
                        </div>
                        <?php if (isset($stats) && isset($stats['low_stock_products']) && $stats['low_stock_products'] > 0): ?>
                        <span class="badge-notification text-white text-xs px-2 py-1 rounded-full font-semibold shadow-lg">
                            <?php echo $stats['low_stock_products']; ?>
                        </span>
                        <?php endif; ?>
                    </div>
                </a>
            </div>
        </div>

        <!-- Orders Section -->
        <div class="space-y-3">
            <div class="section-header px-4 py-2">
                <h3 class="text-sm font-bold text-white/80 uppercase tracking-wider">Pedidos</h3>
            </div>
            
            <div class="space-y-1">
                <a href="pedidos.php" 
                   class="nav-item flex items-center space-x-4 py-3 px-4 rounded-2xl group <?php echo basename($_SERVER['PHP_SELF']) == 'pedidos.php' ? 'active' : ''; ?>">
                    <div class="w-10 h-10 bg-gradient-to-r from-blue-500/20 to-cyan-500/20 rounded-xl flex items-center justify-center group-hover:from-blue-500/30 group-hover:to-cyan-500/30 transition-all duration-300">
                        <i class="fas fa-shopping-cart nav-icon text-blue-400 group-hover:text-blue-300"></i>
                    </div>
                    <div class="flex-1 flex items-center justify-between">
                        <div>
                            <span class="font-medium text-white/90 group-hover:text-blue-200 transition-colors duration-300">Todos los Pedidos</span>
                            <p class="text-xs text-white/50">Gestionar órdenes</p>
                        </div>
                        <?php if (isset($stats) && isset($stats['pending_orders']) && $stats['pending_orders'] > 0): ?>
                        <span class="badge-notification text-white text-xs px-2 py-1 rounded-full font-semibold shadow-lg">
                            <?php echo $stats['pending_orders']; ?>
                        </span>
                        <?php endif; ?>
                    </div>
                </a>
            </div>
        </div>

        <!-- Customers Section -->
        <div class="space-y-3">
            <div class="section-header px-4 py-2">
                <h3 class="text-sm font-bold text-white/80 uppercase tracking-wider">Clientes</h3>
            </div>
            
            <div class="space-y-1">
                <a href="customers.php" 
                   class="nav-item flex items-center space-x-4 py-3 px-4 rounded-2xl group <?php echo basename($_SERVER['PHP_SELF']) == 'customers.php' ? 'active' : ''; ?>">
                    <div class="w-10 h-10 bg-gradient-to-r from-green-500/20 to-emerald-500/20 rounded-xl flex items-center justify-center group-hover:from-green-500/30 group-hover:to-emerald-500/30 transition-all duration-300">
                        <i class="fas fa-users nav-icon text-green-400 group-hover:text-green-300"></i>
                    </div>
                    <div class="flex-1 flex items-center justify-between">
                        <div>
                            <span class="font-medium text-white/90 group-hover:text-green-200 transition-colors duration-300">Lista de Clientes</span>
                            <p class="text-xs text-white/50">Base de clientes</p>
                        </div>
                        <?php if (isset($stats) && isset($stats['new_customers_month']) && $stats['new_customers_month'] > 0): ?>
                        <span class="bg-green-500 text-white text-xs px-2 py-1 rounded-full font-semibold shadow-lg">
                            +<?php echo $stats['new_customers_month']; ?>
                        </span>
                        <?php endif; ?>
                    </div>
                </a>
            </div>
        </div>

        <!-- Reports Section -->
        <div class="space-y-3">
            <div class="section-header px-4 py-2">
                <h3 class="text-sm font-bold text-white/80 uppercase tracking-wider">Reportes</h3>
            </div>
            
            <div class="space-y-1">
                <a href="reports.php" 
                   class="nav-item flex items-center space-x-4 py-3 px-4 rounded-2xl group <?php echo basename($_SERVER['PHP_SELF']) == 'reports.php' ? 'active' : ''; ?>">
                    <div class="w-10 h-10 bg-gradient-to-r from-yellow-500/20 to-orange-500/20 rounded-xl flex items-center justify-center group-hover:from-yellow-500/30 group-hover:to-orange-500/30 transition-all duration-300">
                        <i class="fas fa-chart-bar nav-icon text-yellow-400 group-hover:text-yellow-300"></i>
                    </div>
                    <div class="flex-1">
                        <span class="font-medium text-white/90 group-hover:text-yellow-200 transition-colors duration-300">Reportes de Ventas</span>
                        <p class="text-xs text-white/50">Análisis y estadísticas</p>
                    </div>
                </a>
                
                <a href="newsletter-subscribers.php" 
                   class="nav-item flex items-center space-x-4 py-3 px-4 rounded-2xl group <?php echo basename($_SERVER['PHP_SELF']) == 'newsletter-subscribers.php' ? 'active' : ''; ?>">
                    <div class="w-10 h-10 bg-gradient-to-r from-indigo-500/20 to-blue-500/20 rounded-xl flex items-center justify-center group-hover:from-indigo-500/30 group-hover:to-blue-500/30 transition-all duration-300">
                        <i class="fas fa-envelope nav-icon text-indigo-400 group-hover:text-indigo-300"></i>
                    </div>
                    <div class="flex-1">
                        <span class="font-medium text-white/90 group-hover:text-indigo-200 transition-colors duration-300">Newsletter</span>
                        <p class="text-xs text-white/50">Suscriptores</p>
                    </div>
                </a>
            </div>
        </div>

        <!-- Settings Section -->
        <div class="space-y-3">
            <div class="section-header px-4 py-2">
                <h3 class="text-sm font-bold text-white/80 uppercase tracking-wider">Configuración</h3>
            </div>
            
            <div class="space-y-1">
                <a href="settings.php" 
                   class="nav-item flex items-center space-x-4 py-3 px-4 rounded-2xl group <?php echo basename($_SERVER['PHP_SELF']) == 'settings.php' ? 'active' : ''; ?>">
                    <div class="w-10 h-10 bg-gradient-to-r from-gray-500/20 to-slate-500/20 rounded-xl flex items-center justify-center group-hover:from-gray-500/30 group-hover:to-slate-500/30 transition-all duration-300">
                        <i class="fas fa-cog nav-icon text-gray-400 group-hover:text-gray-300"></i>
                    </div>
                    <div class="flex-1">
                        <span class="font-medium text-white/90 group-hover:text-gray-200 transition-colors duration-300">Configuración</span>
                        <p class="text-xs text-white/50">Ajustes generales</p>
                    </div>
                </a>
                
                <a href="logout.php" 
                   class="nav-item flex items-center space-x-4 py-3 px-4 rounded-2xl group hover:bg-red-500/10 transition-all duration-300">
                    <div class="w-10 h-10 bg-gradient-to-r from-red-500/20 to-pink-500/20 rounded-xl flex items-center justify-center group-hover:from-red-500/30 group-hover:to-pink-500/30 transition-all duration-300">
                        <i class="fas fa-sign-out-alt nav-icon text-red-400 group-hover:text-red-300"></i>
                    </div>
                    <div class="flex-1">
                        <span class="font-medium text-white/90 group-hover:text-red-200 transition-colors duration-300">Cerrar Sesión</span>
                        <p class="text-xs text-white/50">Salir del sistema</p>
                    </div>
                </a>
            </div>
        </div>
    </nav>
</aside>
