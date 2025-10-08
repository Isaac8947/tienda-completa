<?php
/**
 * Componente de búsqueda global con funcionalidad AJAX en tiempo real
 * Para ser incluido en todas las páginas
 */
?>

<!-- Search Component -->
<div id="global-search-container" class="relative">
    <!-- Search Button/Icon -->
    <button id="search-toggle" class="p-2 text-gray-600 hover:text-primary-500 transition-colors">
        <i class="fas fa-search text-lg"></i>
    </button>
    
    <!-- Search Overlay -->
    <div id="search-overlay" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden">
        <div class="flex items-start justify-center pt-20 px-4">
            <div class="bg-white rounded-2xl shadow-2xl w-full max-w-4xl relative">
                <!-- Search Header -->
                <div class="p-6 border-b border-gray-200">
                    <div class="flex items-center justify-between">
                        <h3 class="text-xl font-semibold text-gray-900">Buscar Productos</h3>
                        <button id="search-close" class="text-gray-400 hover:text-gray-600 text-2xl">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
                
                <!-- Search Input -->
                <div class="p-6 border-b border-gray-200">
                    <div class="relative">
                        <input type="text" 
                               id="global-search-input" 
                               placeholder="Buscar productos, marcas, categorías..." 
                               class="w-full px-6 py-4 pl-12 pr-4 text-lg border-2 border-gray-200 rounded-xl focus:border-primary-500 focus:ring-0 bg-gray-50 focus:bg-white transition-all"
                               autocomplete="off">
                        <i class="fas fa-search absolute left-4 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                        <div id="search-loading" class="absolute right-4 top-1/2 transform -translate-y-1/2 hidden">
                            <div class="animate-spin rounded-full h-5 w-5 border-b-2 border-primary-500"></div>
                        </div>
                    </div>
                    
                    <!-- Quick Filters -->
                    <div id="search-quick-filters" class="flex flex-wrap gap-2 mt-4">
                        <button class="quick-search-filter px-3 py-1 text-sm bg-gray-100 hover:bg-primary-100 text-gray-700 hover:text-primary-700 rounded-full transition-all" data-filter="ofertas">
                            🔥 Ofertas
                        </button>
                        <button class="quick-search-filter px-3 py-1 text-sm bg-gray-100 hover:bg-primary-100 text-gray-700 hover:text-primary-700 rounded-full transition-all" data-filter="nuevos">
                            ✨ Nuevos
                        </button>
                        <button class="quick-search-filter px-3 py-1 text-sm bg-gray-100 hover:bg-primary-100 text-gray-700 hover:text-primary-700 rounded-full transition-all" data-filter="populares">
                            ⭐ Populares
                        </button>
                        <button class="quick-search-filter px-3 py-1 text-sm bg-gray-100 hover:bg-primary-100 text-gray-700 hover:text-primary-700 rounded-full transition-all" data-filter="tecnologia">
                            📱 Tecnología
                        </button>
                    </div>
                </div>
                
                <!-- Search Results -->
                <div id="search-results-container" class="max-h-96 overflow-y-auto">
                    <!-- Empty State -->
                    <div id="search-empty-state" class="text-center py-12 px-6">
                        <div class="text-6xl text-gray-300 mb-4">
                            <i class="fas fa-search"></i>
                        </div>
                        <h4 class="text-lg font-medium text-gray-600 mb-2">Comienza a escribir para buscar</h4>
                        <p class="text-gray-500">Encuentra productos, marcas y categorías</p>
                    </div>
                    
                    <!-- Loading State -->
                    <div id="search-results-loading" class="hidden text-center py-12 px-6">
                        <div class="animate-pulse">
                            <div class="flex items-center space-x-4 mb-4">
                                <div class="w-16 h-16 bg-gray-200 rounded-lg"></div>
                                <div class="flex-1">
                                    <div class="h-4 bg-gray-200 rounded mb-2"></div>
                                    <div class="h-3 bg-gray-200 rounded w-2/3"></div>
                                </div>
                            </div>
                            <div class="flex items-center space-x-4 mb-4">
                                <div class="w-16 h-16 bg-gray-200 rounded-lg"></div>
                                <div class="flex-1">
                                    <div class="h-4 bg-gray-200 rounded mb-2"></div>
                                    <div class="h-3 bg-gray-200 rounded w-2/3"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Results -->
                    <div id="search-results" class="hidden">
                        <!-- Dynamic content will be inserted here -->
                    </div>
                    
                    <!-- No Results -->
                    <div id="search-no-results" class="hidden text-center py-12 px-6">
                        <div class="text-5xl text-gray-300 mb-4">
                            <i class="fas fa-search-minus"></i>
                        </div>
                        <h4 class="text-lg font-medium text-gray-600 mb-2">No se encontraron resultados</h4>
                        <p class="text-gray-500 mb-4">Intenta con otros términos de búsqueda</p>
                        <button id="show-all-products" class="bg-primary-500 text-white px-6 py-2 rounded-lg hover:bg-primary-600 transition-colors">
                            Ver todos los productos
                        </button>
                    </div>
                </div>
                
                <!-- Search Footer -->
                <div id="search-footer" class="hidden p-4 border-t border-gray-200 bg-gray-50 rounded-b-2xl">
                    <div class="flex items-center justify-between text-sm text-gray-600">
                        <span id="search-results-count">0 resultados</span>
                        <a href="#" id="view-all-results" class="text-primary-600 hover:text-primary-700 font-medium">
                            Ver todos los resultados →
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Search Styles -->
<style>
.search-result-item {
    @apply flex items-center p-4 hover:bg-gray-50 cursor-pointer border-b border-gray-100 transition-colors;
}

.search-result-item:last-child {
    @apply border-b-0;
}

.search-result-image {
    @apply w-16 h-16 object-cover rounded-lg bg-gray-100;
}

.search-result-content {
    @apply flex-1 ml-4;
}

.search-result-title {
    @apply font-medium text-gray-900 text-sm mb-1 line-clamp-1;
}

.search-result-category {
    @apply text-xs text-gray-500 mb-2;
}

.search-result-price {
    @apply font-semibold text-primary-600;
}

.search-result-discount {
    @apply text-xs text-gray-500 line-through ml-2;
}

.search-category-item {
    @apply flex items-center p-3 hover:bg-primary-50 cursor-pointer border-b border-gray-100 transition-colors;
}

.search-category-icon {
    @apply w-8 h-8 bg-primary-100 text-primary-600 rounded-lg flex items-center justify-center text-sm;
}

.search-brand-item {
    @apply flex items-center p-3 hover:bg-gray-50 cursor-pointer border-b border-gray-100 transition-colors;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    #search-overlay .max-w-4xl {
        @apply max-w-full mx-2;
    }
    
    #search-results-container {
        @apply max-h-80;
    }
}
</style>
