<!-- Incluir el contenido principal de productos desde catalogo.php -->
<?php
// Este include contiene toda la lógica de productos y filtros
// Reutilizamos la funcionalidad de catalogo.php para mantener consistencia
?>

    <!-- Mobile Filters Button -->
    <div class="md:hidden sticky top-20 z-40 bg-white/95 backdrop-blur-md border-b border-gray-200 px-4 py-3">
        <div class="flex flex-col space-y-3">
            <!-- Primera fila: botón filtros y contador de productos -->
            <div class="flex items-center justify-between">
                <button onclick="toggleMobileFilters()" class="flex items-center bg-primary-500 text-white px-4 py-2 rounded-xl font-medium mobile-tap">
                    <i class="fas fa-filter mr-2"></i>
                    Filtros
                    <span id="active-filters-count" class="ml-2 bg-white/20 px-2 py-1 rounded-full text-xs hidden">0</span>
                </button>
                
                <div class="text-sm font-medium text-gray-700">
                    <span class="bg-gray-100 px-3 py-1 rounded-full">
                        <?php echo number_format($totalProducts); ?> productos
                    </span>
                </div>
            </div>
            
            <!-- Segunda fila: selector de ordenamiento -->
            <div class="flex items-center justify-between">
                <span class="text-sm text-gray-600">Ordenar por:</span>
                <select onchange="handleMobileSortChange(this.value)" class="text-sm border border-gray-200 rounded-lg px-3 py-2 bg-white min-w-0 flex-1 ml-3">
                    <option value="newest" <?php echo $filters['sort'] == 'newest' ? 'selected' : ''; ?>>Más nuevos</option>
                    <option value="price_low" <?php echo $filters['sort'] == 'price_low' ? 'selected' : ''; ?>>Precio: menor a mayor</option>
                    <option value="price_high" <?php echo $filters['sort'] == 'price_high' ? 'selected' : ''; ?>>Precio: mayor a menor</option>
                    <option value="name" <?php echo $filters['sort'] == 'name' ? 'selected' : ''; ?>>Nombre A-Z</option>
                </select>
            </div>
        </div>
    </div>
    
    <!-- Mobile Filters Modal -->
    <div id="mobile-filters" class="md:hidden fixed inset-0 z-50 hidden">
        <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" onclick="toggleMobileFilters()"></div>
        <div id="mobile-filters-panel" class="absolute bottom-0 left-0 right-0 bg-white rounded-t-3xl transform translate-y-full transition-transform duration-300 ease-out">
            <div class="flex flex-col h-[85vh] max-h-[600px]">
                <!-- Header - Fixed -->
                <div class="flex items-center justify-between p-6 border-b border-gray-200 flex-shrink-0">
                    <h3 class="text-xl font-serif font-bold text-gray-900">Filtros</h3>
                    <button onclick="toggleMobileFilters()" class="w-8 h-8 flex items-center justify-center rounded-lg text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                
                <!-- Content - Scrollable -->
                <div class="flex-1 overflow-y-auto p-6 mobile-filters-content">
                    <form method="GET" action="search.php" id="mobile-filters-form" class="space-y-6">
                        <!-- Search Filter -->
                        <div>
                            <h4 class="font-semibold text-gray-900 mb-3 flex items-center">
                                <i class="fas fa-search text-primary-500 mr-2"></i>
                                Buscar
                            </h4>
                            <input type="text" name="q" value="<?php echo htmlspecialchars($searchQuery); ?>" 
                                   placeholder="Buscar productos..." 
                                   class="w-full p-3 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 bg-white">
                        </div>
                        
                        <!-- Category Filter -->
                        <div>
                            <h4 class="font-semibold text-gray-900 mb-3 flex items-center">
                                <i class="fas fa-layer-group text-primary-500 mr-2"></i>
                                Categoría
                            </h4>
                            <select name="categoria" class="w-full p-3 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 bg-white">
                                <option value="">Todas las categorías</option>
                                <?php foreach ($categories as $category): ?>
                                <option value="<?php echo $category['id']; ?>" <?php echo $filters['category'] == $category['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($category['name']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <!-- Brand Filter -->
                        <div>
                            <h4 class="font-semibold text-gray-900 mb-3 flex items-center">
                                <i class="fas fa-tags text-primary-500 mr-2"></i>
                                Marca
                            </h4>
                            <select name="marca" class="w-full p-3 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 bg-white">
                                <option value="">Todas las marcas</option>
                                <?php foreach ($brands as $brand): ?>
                                <option value="<?php echo $brand['id']; ?>" <?php echo $filters['brand'] == $brand['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($brand['name']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <!-- Price Filter -->
                        <div>
                            <h4 class="font-semibold text-gray-900 mb-3 flex items-center">
                                <i class="fas fa-dollar-sign text-primary-500 mr-2"></i>
                                Rango de Precio
                            </h4>
                            <div class="grid grid-cols-2 gap-3">
                                <input type="number" name="precio_min" placeholder="Mínimo"
                                       value="<?php echo htmlspecialchars($filters['min_price']); ?>"
                                       class="p-3 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 bg-white">
                                <input type="number" name="precio_max" placeholder="Máximo"
                                       value="<?php echo htmlspecialchars($filters['max_price']); ?>"
                                       class="p-3 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 bg-white">
                            </div>
                        </div>
                        
                        <!-- Special Filters -->
                        <div>
                            <h4 class="font-semibold text-gray-900 mb-3 flex items-center">
                                <i class="fas fa-star text-primary-500 mr-2"></i>
                                Especiales
                            </h4>
                            <div class="space-y-3">
                                <label class="flex items-center p-3 rounded-xl hover:bg-primary-50 transition-colors cursor-pointer mobile-tap">
                                    <input type="checkbox" name="nuevos" value="1" <?php echo $filters['new_only'] ? 'checked' : ''; ?>
                                           class="rounded border-gray-300 text-primary-500 focus:ring-primary-500 w-5 h-5">
                                    <span class="ml-3 text-gray-700 font-medium">Solo productos nuevos</span>
                                </label>
                                <label class="flex items-center p-3 rounded-xl hover:bg-primary-50 transition-colors cursor-pointer mobile-tap">
                                    <input type="checkbox" name="destacados" value="1" <?php echo $filters['featured_only'] ? 'checked' : ''; ?>
                                           class="rounded border-gray-300 text-primary-500 focus:ring-primary-500 w-5 h-5">
                                    <span class="ml-3 text-gray-700 font-medium">Solo productos destacados</span>
                                </label>
                            </div>
                        </div>
                        
                        <input type="hidden" name="orden" value="<?php echo htmlspecialchars($filters['sort']); ?>">
                    </form>
                </div>
                
                <!-- Action Buttons - Fixed -->
                <div class="flex space-x-3 p-6 border-t border-gray-200 flex-shrink-0 bg-white">
                    <button onclick="clearMobileFilters()" class="flex-1 border border-gray-300 text-gray-700 py-3 rounded-xl font-semibold hover:bg-gray-50 transition-colors mobile-tap">
                        Limpiar
                    </button>
                    <button onclick="applyMobileFilters()" class="flex-1 bg-gradient-to-r from-primary-500 to-secondary-500 text-white py-3 rounded-xl font-semibold mobile-tap">
                        Aplicar Filtros
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Main Content -->
    <section class="py-8 md:py-16">
        <div class="container mx-auto px-4">
            <div class="flex flex-col lg:flex-row gap-8 lg:gap-12">
                <!-- Desktop Sidebar Filters -->
                <div class="hidden lg:block lg:w-1/4">
                    <div class="bg-white/90 backdrop-blur-sm rounded-3xl luxury-shadow p-8 sticky top-6 border border-white/50" data-aos="fade-right">
                        <h3 class="text-2xl font-serif font-bold text-gray-900 mb-8 flex items-center">
                            <div class="w-10 h-10 bg-gradient-to-r from-primary-500 to-secondary-500 rounded-xl flex items-center justify-center mr-3">
                                <i class="fas fa-filter text-white"></i>
                            </div>
                            Filtros
                        </h3>
                        
                        <form method="GET" action="search.php" id="filters-form" class="space-y-8">
                            <!-- Search Filter -->
                            <div>
                                <h4 class="font-semibold text-gray-900 mb-4 flex items-center text-lg">
                                    <div class="w-8 h-8 bg-gradient-to-r from-blue-400 to-blue-500 rounded-lg flex items-center justify-center mr-3">
                                        <i class="fas fa-search text-white text-sm"></i>
                                    </div>
                                    Buscar
                                </h4>
                                <input type="text" name="q" id="search-input" value="<?php echo htmlspecialchars($searchQuery); ?>" 
                                       placeholder="Buscar productos..." 
                                       class="w-full p-4 border border-gray-200 rounded-2xl focus:outline-none focus:ring-2 focus:ring-primary-500/50 focus:border-primary-500 transition-all duration-300 bg-white/80 backdrop-blur-sm">
                            </div>
                            
                            <!-- Category Filter -->
                            <div>
                                <h4 class="font-semibold text-gray-900 mb-4 flex items-center text-lg">
                                    <div class="w-8 h-8 bg-gradient-to-r from-secondary-400 to-secondary-500 rounded-lg flex items-center justify-center mr-3">
                                        <i class="fas fa-layer-group text-white text-sm"></i>
                                    </div>
                                    Categoría
                                </h4>
                                <select name="categoria" class="w-full p-4 border border-gray-200 rounded-2xl focus:outline-none focus:ring-2 focus:ring-primary-500/50 focus:border-primary-500 transition-all duration-300 bg-white/80 backdrop-blur-sm">
                                    <option value="">Todas las categorías</option>
                                    <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo $category['id']; ?>" <?php echo $filters['category'] == $category['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($category['name']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <!-- Brand Filter -->
                            <div>
                                <h4 class="font-semibold text-gray-900 mb-4 flex items-center text-lg">
                                    <div class="w-8 h-8 bg-gradient-to-r from-accent-400 to-accent-500 rounded-lg flex items-center justify-center mr-3">
                                        <i class="fas fa-tags text-white text-sm"></i>
                                    </div>
                                    Marca
                                </h4>
                                <select name="marca" class="w-full p-4 border border-gray-200 rounded-2xl focus:outline-none focus:ring-2 focus:ring-primary-500/50 focus:border-primary-500 transition-all duration-300 bg-white/80 backdrop-blur-sm">
                                    <option value="">Todas las marcas</option>
                                    <?php foreach ($brands as $brand): ?>
                                    <option value="<?php echo $brand['id']; ?>" <?php echo $filters['brand'] == $brand['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($brand['name']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <!-- Price Filter -->
                            <div>
                                <h4 class="font-semibold text-gray-900 mb-4 flex items-center text-lg">
                                    <div class="w-8 h-8 bg-gradient-to-r from-green-400 to-green-500 rounded-lg flex items-center justify-center mr-3">
                                        <i class="fas fa-dollar-sign text-white text-sm"></i>
                                    </div>
                                    Rango de Precio
                                </h4>
                                <div class="grid grid-cols-2 gap-4">
                                    <input type="number" name="precio_min" placeholder="Mínimo"
                                           value="<?php echo htmlspecialchars($filters['min_price']); ?>"
                                           class="p-4 border border-gray-200 rounded-2xl focus:outline-none focus:ring-2 focus:ring-primary-500/50 focus:border-primary-500 transition-all duration-300 bg-white/80 backdrop-blur-sm">
                                    <input type="number" name="precio_max" placeholder="Máximo"
                                           value="<?php echo htmlspecialchars($filters['max_price']); ?>"
                                           class="p-4 border border-gray-200 rounded-2xl focus:outline-none focus:ring-2 focus:ring-primary-500/50 focus:border-primary-500 transition-all duration-300 bg-white/80 backdrop-blur-sm">
                                </div>
                            </div>
                            
                            <!-- Special Filters -->
                            <div>
                                <h4 class="font-semibold text-gray-900 mb-4 flex items-center text-lg">
                                    <div class="w-8 h-8 bg-gradient-to-r from-yellow-400 to-yellow-500 rounded-lg flex items-center justify-center mr-3">
                                        <i class="fas fa-star text-white text-sm"></i>
                                    </div>
                                    Especiales
                                </h4>
                                <div class="space-y-4">
                                    <label class="flex items-center p-3 rounded-xl hover:bg-primary-50 transition-colors cursor-pointer">
                                        <input type="checkbox" name="nuevos" value="1" <?php echo $filters['new_only'] ? 'checked' : ''; ?>
                                               class="rounded-lg border-gray-300 text-primary-500 focus:ring-primary-500 w-5 h-5">
                                        <span class="ml-3 text-gray-700 font-medium">Solo productos nuevos</span>
                                    </label>
                                    <label class="flex items-center p-3 rounded-xl hover:bg-primary-50 transition-colors cursor-pointer">
                                        <input type="checkbox" name="destacados" value="1" <?php echo $filters['featured_only'] ? 'checked' : ''; ?>
                                               class="rounded-lg border-gray-300 text-primary-500 focus:ring-primary-500 w-5 h-5">
                                        <span class="ml-3 text-gray-700 font-medium">Solo productos destacados</span>
                                    </label>
                                </div>
                            </div>
                            
                            <!-- Sort Filter -->
                            <div>
                                <h4 class="font-semibold text-gray-900 mb-4 flex items-center text-lg">
                                    <div class="w-8 h-8 bg-gradient-to-r from-purple-400 to-purple-500 rounded-lg flex items-center justify-center mr-3">
                                        <i class="fas fa-sort text-white text-sm"></i>
                                    </div>
                                    Ordenar por
                                </h4>
                                <select name="orden" class="w-full p-4 border border-gray-200 rounded-2xl focus:outline-none focus:ring-2 focus:ring-primary-500/50 focus:border-primary-500 transition-all duration-300 bg-white/80 backdrop-blur-sm">
                                    <option value="newest" <?php echo $filters['sort'] == 'newest' ? 'selected' : ''; ?>>Más nuevos</option>
                                    <option value="price_low" <?php echo $filters['sort'] == 'price_low' ? 'selected' : ''; ?>>Precio: Menor a mayor</option>
                                    <option value="price_high" <?php echo $filters['sort'] == 'price_high' ? 'selected' : ''; ?>>Precio: Mayor a menor</option>
                                    <option value="name" <?php echo $filters['sort'] == 'name' ? 'selected' : ''; ?>>Nombre A-Z</option>
                                    <option value="featured" <?php echo $filters['sort'] == 'featured' ? 'selected' : ''; ?>>Destacados primero</option>
                                </select>
                            </div>
                            
                            <div class="space-y-4">
                                <button type="submit" class="w-full bg-gradient-to-r from-primary-500 to-secondary-500 text-white py-4 rounded-2xl font-semibold hover:shadow-xl transform hover:scale-105 transition-all duration-300">
                                    <i class="fas fa-search mr-2"></i>
                                    Aplicar Filtros
                                </button>
                                
                                <a href="search.php"
                                   class="w-full border-2 border-gray-300 text-gray-700 py-4 rounded-2xl font-semibold hover:bg-gray-50 hover:border-primary-300 transition-all duration-300 block text-center">
                                    <i class="fas fa-times mr-2"></i>
                                    Limpiar Filtros
                                </a>
                            </div>
                        </form>
                        
                        <!-- Quick Categories -->
                        <div class="mt-12 pt-8 border-t border-gray-200">
                            <h4 class="font-semibold text-gray-900 mb-6 text-lg">Categorías Populares</h4>
                            <div class="space-y-3">
                                <?php foreach (array_slice($categories, 0, 6) as $category): ?>
                                <a href="categoria.php?categoria=<?php echo $category['slug']; ?>"
                                   class="block px-4 py-3 text-gray-600 hover:text-primary-500 hover:bg-primary-50 rounded-xl transition-all duration-300 font-medium">
                                    <i class="fas fa-chevron-right mr-2 text-xs"></i>
                                    <?php echo htmlspecialchars($category['name']); ?>
                                </a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Products Section -->
                <div class="w-full lg:w-3/4">
                    <?php if (!empty($searchQuery)): ?>
                    <!-- Search Results Header -->
                    <div class="mb-8 bg-gradient-to-r from-primary-50 to-secondary-50 rounded-2xl p-6" data-aos="fade-up">
                        <div class="flex items-center justify-between">
                            <div>
                                <h2 class="text-xl font-semibold text-gray-900 mb-2">
                                    Resultados para: <span class="gradient-text">"<?php echo htmlspecialchars($searchQuery); ?>"</span>
                                </h2>
                                <p class="text-gray-600">
                                    Se encontraron <?php echo number_format($totalProducts); ?> productos
                                </p>
                            </div>
                            <button onclick="clearSearch()" class="text-gray-500 hover:text-gray-700 text-sm font-medium">
                                <i class="fas fa-times mr-1"></i>
                                Limpiar búsqueda
                            </button>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Include products grid from catalog -->
                    <?php include 'includes/products-grid.php'; ?>
                </div>
            </div>
        </div>
    </section>
