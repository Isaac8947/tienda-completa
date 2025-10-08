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

#sideb                   <div class="space-y-1">
                <a href="pedidos.php" 
                   class="nav-item flex items-center space-x-4 py-3 px-4 rounded-2xl group <?php echo basename($_SERVER['PHP_SELF']) == 'pedidos.php' ? 'active' : ''; ?>">  <div class="space-y-1">
                <a href="pedidos.php" 
                   class="nav-item flex items-center space-x-4 py-3 px-4 rounded-2xl group <?php echo basename($_SERVER['PHP_SELF']) == 'pedidos.php' ? 'active' : ''; ?>">nav::-webkit-scrollbar-thumb {
    background: linear-gradient(135deg, rgba(176, 141, 128, 0.3), rgba(196, 165, 117, 0.3));
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

/* Gradientes personalizados */
.gradient-bg {
    background: linear-gradient(135deg, #1e293b 0%, #334155 50%, #475569 100%);
}

.gradient-text {
    background: linear-gradient(135deg, #b08d80, #c4a575);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.glass-effect {
    background: rgba(255, 255, 255, 0.1);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.1);
}

/* Animaciones */
.nav-item {
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.nav-item:hover {
    transform: translateX(4px);
}

.nav-item.active {
    background: linear-gradient(135deg, rgba(176, 141, 128, 0.2), rgba(196, 165, 117, 0.2));
    border-right: 4px solid;
    border-image: linear-gradient(135deg, #b08d80, #c4a575) 1;
}

.section-header {
    position: relative;
    overflow: hidden;
}

.section-header::before {
    content: '';
    position: absolute;
    left: 0;
    bottom: 0;
    width: 30px;
    height: 2px;
    background: linear-gradient(135deg, #b08d80, #c4a575);
    border-radius: 1px;
}

.nav-icon {
    transition: all 0.3s ease;
}

.nav-item:hover .nav-icon {
    transform: scale(1.1);
    filter: drop-shadow(0 0 8px rgba(176, 141, 128, 0.5));
}

.badge-notification {
    background: linear-gradient(135deg, #ef4444, #dc2626);
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0%, 100% {
        opacity: 1;
        transform: scale(1);
    }
    50% {
        opacity: 0.8;
        transform: scale(1.05);
    }
}

/* Mobile sidebar animation */
#sidebar.mobile-open {
    transform: translateX(0);
}

#sidebar {
    transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}
</style>

<div class="gradient-bg text-white w-72 fixed inset-y-0 left-0 transform -translate-x-full md:relative md:translate-x-0 transition-transform duration-300 ease-in-out z-50 flex flex-col shadow-2xl border-r border-white/10"
     id="sidebar">
    
    <!-- Logo Section -->
    <div class="flex items-center justify-between px-6 py-8 border-b border-white/10 bg-gradient-to-r from-white/5 to-transparent">
        <div class="flex items-center space-x-3">
            <div class="relative">
                <div class="w-12 h-12 bg-gradient-to-br from-primary-400 via-primary-500 to-secondary-500 rounded-2xl flex items-center justify-center shadow-lg">
                    <i class="fas fa-crown text-white text-xl"></i>
                </div>
                <div class="absolute -top-1 -right-1 w-4 h-4 bg-gradient-to-r from-green-400 to-emerald-500 rounded-full border-2 border-white/20"></div>
            </div>
            <div>
                <span class="text-2xl font-serif font-bold gradient-text">Odisea</span>
                <div class="flex items-center space-x-2 mt-1">
                    <span class="text-xs bg-gradient-to-r from-primary-500 to-secondary-500 text-white px-3 py-1 rounded-full font-semibold shadow-sm">
                        ADMIN
                    </span>
                    <span class="text-xs text-white/60">Panel</span>
                </div>
            </div>
        </div>
        
        <!-- Collapse button for desktop -->
        <button class="hidden md:block p-2 rounded-xl hover:bg-white/10 transition-colors duration-300" id="sidebar-collapse">
            <i class="fas fa-chevron-left text-white/70 hover:text-white transition-colors duration-300"></i>
        </button>
    </div>

    <!-- Navigation -->
    <nav class="flex-1 px-4 py-6 overflow-y-auto space-y-6">
        
        <!-- Dashboard -->
        <?php 
        // Determinar la ruta correcta al dashboard
        $currentDir = dirname($_SERVER['PHP_SELF']);
        $dashboardUrl = '';
        if (strpos($currentDir, '/admin-pages') !== false) {
            $dashboardUrl = '../admin/index.php';
        } else if (strpos($currentDir, '/admin') !== false) {
            $dashboardUrl = 'index.php';
        } else {
            $dashboardUrl = 'admin/index.php';
        }
        
        $isDashboard = basename($_SERVER['PHP_SELF']) == 'index.php' && strpos($_SERVER['PHP_SELF'], '/admin/') !== false;
        ?>
        
        <div class="space-y-2">
            <a href="<?php echo $dashboardUrl; ?>" 
               class="nav-item flex items-center space-x-4 py-4 px-4 rounded-2xl group <?php echo $isDashboard ? 'active' : ''; ?>">
                <div class="w-10 h-10 bg-gradient-to-r from-blue-500/20 to-indigo-500/20 rounded-xl flex items-center justify-center group-hover:from-blue-500/30 group-hover:to-indigo-500/30 transition-all duration-300">
                    <i class="fas fa-tachometer-alt nav-icon text-blue-400 group-hover:text-blue-300"></i>
                </div>
                <div class="flex-1">
                    <span class="font-semibold text-white group-hover:text-blue-200 transition-colors duration-300">Dashboard</span>
                    <p class="text-xs text-white/60 group-hover:text-blue-300/80">Panel principal</p>
                </div>
            </a>
        </div>

        <!-- Divider -->
        <div class="my-6 mx-4 border-t border-white/10"></div>

        <!-- Products Section -->
        <div class="space-y-3">
            <div class="section-header px-4 pb-3 mb-4 text-xs font-bold text-white/80 uppercase tracking-wider border-b border-white/10">
                <h3 class="text-sm font-bold text-white/80 uppercase tracking-wider">Productos</h3>
            </div>
            
            <div class="space-y-1">
                <a href="products.php" 
                   class="nav-item flex items-center space-x-4 py-3 px-4 rounded-2xl group <?php echo basename($_SERVER['PHP_SELF']) == 'products.php' ? 'active' : ''; ?>">
                    <div class="w-10 h-10 bg-gradient-to-r from-purple-500/20 to-pink-500/20 rounded-xl flex items-center justify-center group-hover:from-purple-500/30 group-hover:to-pink-500/30 transition-all duration-300">
                        <i class="fas fa-box nav-icon text-purple-400 group-hover:text-purple-300"></i>
                    </div>
                    <div class="flex-1">
                        <span class="font-medium text-white/90 group-hover:text-purple-200 transition-colors duration-300">Todos los Productos</span>
                        <p class="text-xs text-white/50">Gestionar inventario</p>
                    </div>
                </a>
                
                <a href="../admin-pages/products-add.php" 
                   class="nav-item flex items-center space-x-4 py-3 px-4 rounded-2xl group <?php echo basename($_SERVER['PHP_SELF']) == 'products-add.php' ? 'active' : ''; ?>">
                    <div class="w-10 h-10 bg-gradient-to-r from-green-500/20 to-emerald-500/20 rounded-xl flex items-center justify-center group-hover:from-green-500/30 group-hover:to-emerald-500/30 transition-all duration-300">
                        <i class="fas fa-plus nav-icon text-green-400 group-hover:text-green-300"></i>
                    </div>
                    <div class="flex-1">
                        <span class="font-medium text-white/90 group-hover:text-green-200 transition-colors duration-300">Agregar Producto</span>
                        <p class="text-xs text-white/50">Nuevo producto</p>
                    </div>
                </a>
                
                <a href="../admin-pages/inventory.php" 
                   class="nav-item flex items-center space-x-4 py-3 px-4 rounded-2xl group <?php echo basename($_SERVER['PHP_SELF']) == 'inventory.php' ? 'active' : ''; ?>">
                    <div class="w-10 h-10 bg-gradient-to-r from-blue-500/20 to-cyan-500/20 rounded-xl flex items-center justify-center group-hover:from-blue-500/30 group-hover:to-cyan-500/30 transition-all duration-300">
                        <i class="fas fa-history nav-icon text-blue-400 group-hover:text-blue-300"></i>
                    </div>
                    <div class="flex-1">
                        <span class="font-medium text-white/90 group-hover:text-blue-200 transition-colors duration-300">Historial de Inventario</span>
                        <p class="text-xs text-white/50">Movimientos de stock</p>
                    </div>
                </a>
                
                <a href="gestion-inventario.php" 
                   class="nav-item flex items-center space-x-4 py-3 px-4 rounded-2xl group <?php echo basename($_SERVER['PHP_SELF']) == 'gestion-inventario.php' ? 'active' : ''; ?>">
                    <div class="w-10 h-10 bg-gradient-to-r from-emerald-500/20 to-teal-500/20 rounded-xl flex items-center justify-center group-hover:from-emerald-500/30 group-hover:to-teal-500/30 transition-all duration-300">
                        <i class="fas fa-boxes nav-icon text-emerald-400 group-hover:text-emerald-300"></i>
                    </div>
                    <div class="flex-1">
                        <span class="font-medium text-white/90 group-hover:text-emerald-200 transition-colors duration-300">Gestión de Stock</span>
                        <p class="text-xs text-white/50">Control de inventario</p>
                    </div>
                </a>
                
                <a href="../admin-pages/categories.php" 
                   class="nav-item flex items-center space-x-4 py-3 px-4 rounded-2xl group <?php echo basename($_SERVER['PHP_SELF']) == 'categories.php' ? 'active' : ''; ?>">
                    <div class="w-10 h-10 bg-gradient-to-r from-orange-500/20 to-red-500/20 rounded-xl flex items-center justify-center group-hover:from-orange-500/30 group-hover:to-red-500/30 transition-all duration-300">
                        <i class="fas fa-tags nav-icon text-orange-400 group-hover:text-orange-300"></i>
                    </div>
                    <div class="flex-1">
                        <span class="font-medium text-white/90 group-hover:text-orange-200 transition-colors duration-300">Categorías</span>
                        <p class="text-xs text-white/50">Organizar productos</p>
                    </div>
                </a>
                
                <a href="../admin-pages/brands.php" 
                   class="nav-item flex items-center space-x-4 py-3 px-4 rounded-2xl group <?php echo basename($_SERVER['PHP_SELF']) == 'brands.php' ? 'active' : ''; ?>">
                    <div class="w-10 h-10 bg-gradient-to-r from-yellow-500/20 to-amber-500/20 rounded-xl flex items-center justify-center group-hover:from-yellow-500/30 group-hover:to-amber-500/30 transition-all duration-300">
                        <i class="fas fa-award nav-icon text-yellow-400 group-hover:text-yellow-300"></i>
                    </div>
                    <div class="flex-1">
                        <span class="font-medium text-white/90 group-hover:text-yellow-200 transition-colors duration-300">Marcas</span>
                        <p class="text-xs text-white/50">Gestionar marcas</p>
                    </div>
                </a>
                
                <a href="../admin-pages/offers.php" 
                   class="nav-item flex items-center space-x-4 py-3 px-4 rounded-2xl group <?php echo basename($_SERVER['PHP_SELF']) == 'offers.php' ? 'active' : ''; ?>">
                    <div class="w-10 h-10 bg-gradient-to-r from-red-500/20 to-pink-500/20 rounded-xl flex items-center justify-center group-hover:from-red-500/30 group-hover:to-pink-500/30 transition-all duration-300">
                        <i class="fas fa-tag nav-icon text-red-400 group-hover:text-red-300"></i>
                    </div>
                    <div class="flex-1">
                        <span class="font-medium text-white/90 group-hover:text-red-200 transition-colors duration-300">Ofertas</span>
                        <p class="text-xs text-white/50">Gestionar ofertas y descuentos</p>
                    </div>
                </a>
            </div>
        </div>

        <!-- Orders Section -->
        <div class="space-y-3">
            <div class="section-header px-4 py-2">
                <h3 class="text-sm font-bold text-white/80 uppercase tracking-wider">Pedidos</h3>
            </div>
            
            <?php 
            // Determinar la ruta correcta a gestion-pedidos.php
            $pedidosUrl = '';
            if (strpos($currentDir, '/admin-pages') !== false) {
                $pedidosUrl = '../admin/gestion-pedidos.php';
            } else if (strpos($currentDir, '/admin') !== false) {
                $pedidosUrl = 'gestion-pedidos.php';
            } else {
                $pedidosUrl = 'admin/gestion-pedidos.php';
            }
            ?>
            
            <div class="space-y-1">
                <a href="<?php echo $pedidosUrl; ?>" 
                   class="nav-item flex items-center space-x-4 py-3 px-4 rounded-2xl group <?php echo basename($_SERVER['PHP_SELF']) == 'gestion-pedidos.php' ? 'active' : ''; ?>">
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
                
                <a href="configuracion-whatsapp.php" 
                   class="nav-item flex items-center space-x-4 py-3 px-4 rounded-2xl group <?php echo basename($_SERVER['PHP_SELF']) == 'configuracion-whatsapp.php' ? 'active' : ''; ?>">
                    <div class="w-10 h-10 bg-gradient-to-r from-green-500/20 to-emerald-500/20 rounded-xl flex items-center justify-center group-hover:from-green-500/30 group-hover:to-emerald-500/30 transition-all duration-300">
                        <i class="fab fa-whatsapp nav-icon text-green-400 group-hover:text-green-300"></i>
                    </div>
                    <div class="flex-1">
                        <span class="font-medium text-white/90 group-hover:text-green-200 transition-colors duration-300">Config. WhatsApp</span>
                        <p class="text-xs text-white/50">Número y mensajes</p>
                    </div>
                </a>
                
                <a href="invoices.php" 
                   class="nav-item flex items-center space-x-4 py-3 px-4 rounded-2xl group <?php echo basename($_SERVER['PHP_SELF']) == 'invoices.php' ? 'active' : ''; ?>">
                    <div class="w-10 h-10 bg-gradient-to-r from-indigo-500/20 to-purple-500/20 rounded-xl flex items-center justify-center group-hover:from-indigo-500/30 group-hover:to-purple-500/30 transition-all duration-300">
                        <i class="fas fa-file-invoice nav-icon text-indigo-400 group-hover:text-indigo-300"></i>
                    </div>
                    <div class="flex-1">
                        <span class="font-medium text-white/90 group-hover:text-indigo-200 transition-colors duration-300">Facturas</span>
                        <p class="text-xs text-white/50">Documentos fiscales</p>
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
                <a href="../admin-pages/customers.php" 
                   class="nav-item flex items-center space-x-4 py-3 px-4 rounded-2xl group <?php echo basename($_SERVER['PHP_SELF']) == 'customers.php' ? 'active' : ''; ?>">
                    <div class="w-10 h-10 bg-gradient-to-r from-teal-500/20 to-green-500/20 rounded-xl flex items-center justify-center group-hover:from-teal-500/30 group-hover:to-green-500/30 transition-all duration-300">
                        <i class="fas fa-users nav-icon text-teal-400 group-hover:text-teal-300"></i>
                    </div>
                    <div class="flex-1">
                        <span class="font-medium text-white/90 group-hover:text-teal-200 transition-colors duration-300">Todos los Clientes</span>
                        <p class="text-xs text-white/50">Base de clientes</p>
                    </div>
                </a>
                
                <a href="../admin-pages/reviews.php" 
                   class="nav-item flex items-center space-x-4 py-3 px-4 rounded-2xl group <?php echo basename($_SERVER['PHP_SELF']) == 'reviews.php' ? 'active' : ''; ?>">
                    <div class="w-10 h-10 bg-gradient-to-r from-yellow-500/20 to-orange-500/20 rounded-xl flex items-center justify-center group-hover:from-yellow-500/30 group-hover:to-orange-500/30 transition-all duration-300">
                        <i class="fas fa-star nav-icon text-yellow-400 group-hover:text-yellow-300"></i>
                    </div>
                    <div class="flex-1">
                        <span class="font-medium text-white/90 group-hover:text-yellow-200 transition-colors duration-300">Reseñas</span>
                        <p class="text-xs text-white/50">Opiniones clientes</p>
                    </div>
                </a>
            </div>
        </div>

        <!-- Marketing Section -->
        <div class="space-y-3">
            <div class="section-header px-4 py-2">
                <h3 class="text-sm font-bold text-white/80 uppercase tracking-wider">Marketing</h3>
            </div>
            
            <div class="space-y-1">
                <a href="../admin-pages/coupons.php" 
                   class="nav-item flex items-center space-x-4 py-3 px-4 rounded-2xl group <?php echo basename($_SERVER['PHP_SELF']) == 'coupons.php' ? 'active' : ''; ?>">
                    <div class="w-10 h-10 bg-gradient-to-r from-pink-500/20 to-rose-500/20 rounded-xl flex items-center justify-center group-hover:from-pink-500/30 group-hover:to-rose-500/30 transition-all duration-300">
                        <i class="fas fa-ticket-alt nav-icon text-pink-400 group-hover:text-pink-300"></i>
                    </div>
                    <div class="flex-1">
                        <span class="font-medium text-white/90 group-hover:text-pink-200 transition-colors duration-300">Cupones</span>
                        <p class="text-xs text-white/50">Descuentos y ofertas</p>
                    </div>
                </a>
                
                <a href="../admin-pages/banners.php" 
                   class="nav-item flex items-center space-x-4 py-3 px-4 rounded-2xl group <?php echo basename($_SERVER['PHP_SELF']) == 'banners.php' ? 'active' : ''; ?>">
                    <div class="w-10 h-10 bg-gradient-to-r from-purple-500/20 to-indigo-500/20 rounded-xl flex items-center justify-center group-hover:from-purple-500/30 group-hover:to-indigo-500/30 transition-all duration-300">
                        <i class="fas fa-image nav-icon text-purple-400 group-hover:text-purple-300"></i>
                    </div>
                    <div class="flex-1">
                        <span class="font-medium text-white/90 group-hover:text-purple-200 transition-colors duration-300">Banners</span>
                        <p class="text-xs text-white/50">Promociones visuales</p>
                    </div>
                </a>
                
                <a href="../admin-pages/news.php" 
                   class="nav-item flex items-center space-x-4 py-3 px-4 rounded-2xl group <?php echo basename($_SERVER['PHP_SELF']) == 'news.php' ? 'active' : ''; ?>">
                    <div class="w-10 h-10 bg-gradient-to-r from-blue-500/20 to-teal-500/20 rounded-xl flex items-center justify-center group-hover:from-blue-500/30 group-hover:to-teal-500/30 transition-all duration-300">
                        <i class="fas fa-newspaper nav-icon text-blue-400 group-hover:text-blue-300"></i>
                    </div>
                    <div class="flex-1">
                        <span class="font-medium text-white/90 group-hover:text-blue-200 transition-colors duration-300">Noticias</span>
                        <p class="text-xs text-white/50">Blog y contenido</p>
                    </div>
                </a>
                
                <a href="../admin-pages/newsletter.php" 
                   class="nav-item flex items-center space-x-4 py-3 px-4 rounded-2xl group <?php echo basename($_SERVER['PHP_SELF']) == 'newsletter.php' ? 'active' : ''; ?>">
                    <div class="w-10 h-10 bg-gradient-to-r from-green-500/20 to-teal-500/20 rounded-xl flex items-center justify-center group-hover:from-green-500/30 group-hover:to-teal-500/30 transition-all duration-300">
                        <i class="fas fa-envelope nav-icon text-green-400 group-hover:text-green-300"></i>
                    </div>
                    <div class="flex-1">
                        <span class="font-medium text-white/90 group-hover:text-green-200 transition-colors duration-300">Newsletter</span>
                        <p class="text-xs text-white/50">Email marketing</p>
                    </div>
                </a>
            </div>
        </div>

        <!-- Analytics Section -->
        <div class="space-y-3">
            <div class="section-header px-4 py-2">
                <h3 class="text-sm font-bold text-white/80 uppercase tracking-wider">Análisis</h3>
            </div>
            
            <div class="space-y-1">
                <a href="../admin-pages/analytics.php" 
                   class="nav-item flex items-center space-x-4 py-3 px-4 rounded-2xl group <?php echo basename($_SERVER['PHP_SELF']) == 'analytics.php' ? 'active' : ''; ?>">
                    <div class="w-10 h-10 bg-gradient-to-r from-cyan-500/20 to-blue-500/20 rounded-xl flex items-center justify-center group-hover:from-cyan-500/30 group-hover:to-blue-500/30 transition-all duration-300">
                        <i class="fas fa-chart-bar nav-icon text-cyan-400 group-hover:text-cyan-300"></i>
                    </div>
                    <div class="flex-1">
                        <span class="font-medium text-white/90 group-hover:text-cyan-200 transition-colors duration-300">Dashboard Analytics</span>
                        <p class="text-xs text-white/50">Métricas generales</p>
                    </div>
                </a>
                
                <a href="../admin-pages/performance.php" 
                   class="nav-item flex items-center space-x-4 py-3 px-4 rounded-2xl group <?php echo basename($_SERVER['PHP_SELF']) == 'performance.php' ? 'active' : ''; ?>">
                    <div class="w-10 h-10 bg-gradient-to-r from-emerald-500/20 to-green-500/20 rounded-xl flex items-center justify-center group-hover:from-emerald-500/30 group-hover:to-green-500/30 transition-all duration-300">
                        <i class="fas fa-tachometer-alt nav-icon text-emerald-400 group-hover:text-emerald-300"></i>
                    </div>
                    <div class="flex-1">
                        <span class="font-medium text-white/90 group-hover:text-emerald-200 transition-colors duration-300">Performance</span>
                        <p class="text-xs text-white/50">Rendimiento tienda</p>
                    </div>
                </a>
                
                <a href="../admin-pages/reports.php" 
                   class="nav-item flex items-center space-x-4 py-3 px-4 rounded-2xl group <?php echo basename($_SERVER['PHP_SELF']) == 'reports.php' ? 'active' : ''; ?>">
                    <div class="w-10 h-10 bg-gradient-to-r from-orange-500/20 to-red-500/20 rounded-xl flex items-center justify-center group-hover:from-orange-500/30 group-hover:to-red-500/30 transition-all duration-300">
                        <i class="fas fa-file-export nav-icon text-orange-400 group-hover:text-orange-300"></i>
                    </div>
                    <div class="flex-1">
                        <span class="font-medium text-white/90 group-hover:text-orange-200 transition-colors duration-300">Reportes</span>
                        <p class="text-xs text-white/50">Exportar datos</p>
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
                <a href="../admin-pages/settings.php" 
                   class="nav-item flex items-center space-x-4 py-3 px-4 rounded-2xl group <?php echo basename($_SERVER['PHP_SELF']) == 'settings.php' ? 'active' : ''; ?>">
                    <div class="w-10 h-10 bg-gradient-to-r from-gray-500/20 to-slate-500/20 rounded-xl flex items-center justify-center group-hover:from-gray-500/30 group-hover:to-slate-500/30 transition-all duration-300">
                        <i class="fas fa-cog nav-icon text-gray-400 group-hover:text-gray-300"></i>
                    </div>
                    <div class="flex-1">
                        <span class="font-medium text-white/90 group-hover:text-gray-200 transition-colors duration-300">Configuración</span>
                        <p class="text-xs text-white/50">Ajustes generales</p>
                    </div>
                </a>
                
                <!-- Shipping functionality not implemented yet
                <a href="../admin-pages/shipping.php" 
                   class="nav-item flex items-center space-x-4 py-3 px-4 rounded-2xl group <?php echo basename($_SERVER['PHP_SELF']) == 'shipping.php' ? 'active' : ''; ?>">
                    <div class="w-10 h-10 bg-gradient-to-r from-blue-500/20 to-indigo-500/20 rounded-xl flex items-center justify-center group-hover:from-blue-500/30 group-hover:to-indigo-500/30 transition-all duration-300">
                        <i class="fas fa-truck nav-icon text-blue-400 group-hover:text-blue-300"></i>
                    </div>
                    <div class="flex-1">
                        <span class="font-medium text-white/90 group-hover:text-blue-200 transition-colors duration-300">Envíos</span>
                        <p class="text-xs text-white/50">Métodos de envío</p>
                    </div>
                </a>
                -->
                
                <a href="../admin-pages/payments.php" 
                   class="nav-item flex items-center space-x-4 py-3 px-4 rounded-2xl group <?php echo basename($_SERVER['PHP_SELF']) == 'payments.php' ? 'active' : ''; ?>">
                    <div class="w-10 h-10 bg-gradient-to-r from-green-500/20 to-emerald-500/20 rounded-xl flex items-center justify-center group-hover:from-green-500/30 group-hover:to-emerald-500/30 transition-all duration-300">
                        <i class="fas fa-credit-card nav-icon text-green-400 group-hover:text-green-300"></i>
                    </div>
                    <div class="flex-1">
                        <span class="font-medium text-white/90 group-hover:text-green-200 transition-colors duration-300">Pagos</span>
                        <p class="text-xs text-white/50">Métodos de pago</p>
                    </div>
                </a>
            </div>
        </div>
    </nav>

    <!-- Footer Section -->
    <div class="px-6 py-4 border-t border-white/10 bg-gradient-to-r from-white/5 to-transparent">
        <div class="flex items-center justify-between">
            <div class="text-xs text-white/60">
                <p class="font-medium">Odisea Admin v2.0</p>
                <p>© 2024 Todos los derechos</p>
            </div>
            <div class="flex items-center space-x-2">
                <div class="w-2 h-2 bg-green-400 rounded-full animate-pulse"></div>
                <span class="text-xs text-white/60">Online</span>
            </div>
        </div>
    </div>
</div>

<!-- Mobile sidebar overlay -->
<div class="fixed inset-0 bg-black/50 backdrop-blur-sm z-30 md:hidden opacity-0 invisible transition-all duration-300" 
     id="sidebar-overlay"></div>

<!-- JavaScript for sidebar functionality -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const sidebar = document.getElementById('sidebar');
    const sidebarOverlay = document.getElementById('sidebar-overlay');
    const mobileMenuBtn = document.getElementById('mobile-menu-btn');
    const sidebarCollapse = document.getElementById('sidebar-collapse');
    
    // Mobile menu toggle
    if (mobileMenuBtn) {
        mobileMenuBtn.addEventListener('click', function() {
            sidebar.classList.toggle('mobile-open');
            
            if (sidebar.classList.contains('mobile-open')) {
                sidebarOverlay.classList.remove('opacity-0', 'invisible');
                sidebarOverlay.classList.add('opacity-100', 'visible');
            } else {
                sidebarOverlay.classList.remove('opacity-100', 'visible');
                sidebarOverlay.classList.add('opacity-0', 'invisible');
            }
        });
    }
    
    // Close sidebar when clicking overlay
    if (sidebarOverlay) {
        sidebarOverlay.addEventListener('click', function() {
            sidebar.classList.remove('mobile-open');
            sidebarOverlay.classList.remove('opacity-100', 'visible');
            sidebarOverlay.classList.add('opacity-0', 'invisible');
        });
    }
    
    // Sidebar collapse for desktop (optional feature)
    if (sidebarCollapse) {
        sidebarCollapse.addEventListener('click', function() {
            sidebar.classList.toggle('collapsed');
            
            // You can implement collapse functionality here
            // For example, hide text and show only icons
        });
    }
    
    // Add smooth scroll behavior to navigation
    const navLinks = document.querySelectorAll('.nav-item');
    navLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            // Add loading state
            const icon = this.querySelector('.nav-icon');
            if (icon) {
                icon.style.transform = 'scale(1.2) rotate(360deg)';
                setTimeout(() => {
                    icon.style.transform = '';
                }, 300);
            }
        });
    });
    
    // Auto-hide mobile sidebar on window resize
    window.addEventListener('resize', function() {
        if (window.innerWidth >= 768) {
            sidebar.classList.remove('mobile-open');
            sidebarOverlay.classList.remove('opacity-100', 'visible');
            sidebarOverlay.classList.add('opacity-0', 'invisible');
        }
    });
    
    // Add keyboard navigation
    document.addEventListener('keydown', function(e) {
        // ESC key to close mobile sidebar
        if (e.key === 'Escape' && sidebar.classList.contains('mobile-open')) {
            sidebar.classList.remove('mobile-open');
            sidebarOverlay.classList.remove('opacity-100', 'visible');
            sidebarOverlay.classList.add('opacity-0', 'invisible');
        }
    });
});
</script>