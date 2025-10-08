/**
 * Sistema de búsqueda global en tiempo real con AJAX
 * Funciona en todas las páginas del sitio
 */

class GlobalSearch {
    constructor() {
        this.searchContainer = document.getElementById('global-search-container');
        this.searchToggle = document.getElementById('search-toggle');
        this.searchOverlay = document.getElementById('search-overlay');
        this.searchClose = document.getElementById('search-close');
        this.searchInput = document.getElementById('global-search-input');
        this.searchLoading = document.getElementById('search-loading');
        
        // Results elements
        this.resultsContainer = document.getElementById('search-results-container');
        this.emptyState = document.getElementById('search-empty-state');
        this.resultsLoading = document.getElementById('search-results-loading');
        this.results = document.getElementById('search-results');
        this.noResults = document.getElementById('search-no-results');
        this.searchFooter = document.getElementById('search-footer');
        this.resultsCount = document.getElementById('search-results-count');
        this.viewAllResults = document.getElementById('view-all-results');
        
        // Quick filters
        this.quickFilters = document.querySelectorAll('.quick-search-filter');
        
        // Search state
        this.currentQuery = '';
        this.searchTimeout = null;
        this.isSearching = false;
        this.currentResults = [];
        
        this.init();
    }
    
    init() {
        if (!this.searchContainer) return;
        
        // Event listeners
        this.searchToggle?.addEventListener('click', () => this.openSearch());
        this.searchClose?.addEventListener('click', () => this.closeSearch());
        this.searchOverlay?.addEventListener('click', (e) => {
            if (e.target === this.searchOverlay) {
                this.closeSearch();
            }
        });
        
        // Search input events
        this.searchInput?.addEventListener('input', (e) => this.handleSearchInput(e.target.value));
        this.searchInput?.addEventListener('keydown', (e) => this.handleKeyDown(e));
        this.searchInput?.addEventListener('focus', () => this.handleFocus());
        
        // Quick filters
        this.quickFilters?.forEach(filter => {
            filter.addEventListener('click', () => this.handleQuickFilter(filter.dataset.filter));
        });
        
        // Other buttons
        document.getElementById('show-all-products')?.addEventListener('click', () => {
            window.location.href = 'catalogo.php';
        });
        
        // Keyboard shortcuts
        document.addEventListener('keydown', (e) => {
            // Ctrl/Cmd + K to open search
            if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
                e.preventDefault();
                this.openSearch();
            }
            
            // Escape to close search
            if (e.key === 'Escape' && !this.searchOverlay?.classList.contains('hidden')) {
                this.closeSearch();
            }
        });
    }
    
    openSearch() {
        if (this.searchOverlay) {
            this.searchOverlay.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
            
            // Focus input after animation
            setTimeout(() => {
                this.searchInput?.focus();
            }, 150);
        }
    }
    
    closeSearch() {
        if (this.searchOverlay) {
            this.searchOverlay.classList.add('hidden');
            document.body.style.overflow = '';
            this.clearSearch();
        }
    }
    
    handleSearchInput(query) {
        clearTimeout(this.searchTimeout);
        this.currentQuery = query.trim();
        
        if (this.currentQuery.length === 0) {
            this.showEmptyState();
            return;
        }
        
        if (this.currentQuery.length < 2) {
            return;
        }
        
        // Debounce search
        this.searchTimeout = setTimeout(() => {
            this.performSearch(this.currentQuery);
        }, 300);
    }
    
    handleKeyDown(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            if (this.currentResults.length > 0) {
                // Go to first result or search page
                const firstResult = this.currentResults[0];
                if (firstResult.type === 'product') {
                    window.location.href = `product.php?id=${firstResult.id}`;
                } else {
                    this.viewAllSearchResults();
                }
            } else if (this.currentQuery) {
                this.viewAllSearchResults();
            }
        }
        
        // Navigation with arrows (future enhancement)
        if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
            e.preventDefault();
            // TODO: Add keyboard navigation through results
        }
    }
    
    handleFocus() {
        if (this.currentQuery.length >= 2) {
            this.performSearch(this.currentQuery);
        }
    }
    
    handleQuickFilter(filter) {
        const filterQueries = {
            'ofertas': 'ofertas descuentos',
            'nuevos': 'nuevos productos',
            'populares': 'populares vendidos',
            'tecnologia': 'tecnología smartphone'
        };
        
        const query = filterQueries[filter] || filter;
        this.searchInput.value = '';
        this.performSearch(query);
        
        // Update active filter visual state
        this.quickFilters.forEach(btn => btn.classList.remove('bg-primary-100', 'text-primary-700'));
        const activeFilter = document.querySelector(`[data-filter="${filter}"]`);
        activeFilter?.classList.add('bg-primary-100', 'text-primary-700');
    }
    
    async performSearch(query) {
        if (this.isSearching) return;
        
        this.isSearching = true;
        this.showLoading();
        
        try {
            const response = await fetch('api/search-realtime.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    query: query,
                    limit: 8 // Limit for quick search
                })
            });
            
            if (!response.ok) {
                throw new Error('Search request failed');
            }
            
            const data = await response.json();
            
            if (data.success) {
                this.displayResults(data.results, data.total, query);
            } else {
                this.showError(data.message || 'Error en la búsqueda');
            }
        } catch (error) {
            console.error('Search error:', error);
            this.showError('Error de conexión. Intenta nuevamente.');
        } finally {
            this.isSearching = false;
            this.hideLoading();
        }
    }
    
    displayResults(results, total, query) {
        this.currentResults = results;
        this.hideAllStates();
        
        if (results.length === 0) {
            this.showNoResults();
            return;
        }
        
        // Organize results by type
        const products = results.filter(r => r.type === 'product');
        const categories = results.filter(r => r.type === 'category');
        const brands = results.filter(r => r.type === 'brand');
        
        let html = '';
        
        // Products section
        if (products.length > 0) {
            html += '<div class="p-4 bg-gray-50"><h5 class="font-semibold text-gray-900 text-sm">Productos</h5></div>';
            products.forEach(product => {
                html += this.renderProductItem(product);
            });
        }
        
        // Categories section
        if (categories.length > 0) {
            html += '<div class="p-4 bg-gray-50"><h5 class="font-semibold text-gray-900 text-sm">Categorías</h5></div>';
            categories.forEach(category => {
                html += this.renderCategoryItem(category);
            });
        }
        
        // Brands section
        if (brands.length > 0) {
            html += '<div class="p-4 bg-gray-50"><h5 class="font-semibold text-gray-900 text-sm">Marcas</h5></div>';
            brands.forEach(brand => {
                html += this.renderBrandItem(brand);
            });
        }
        
        this.results.innerHTML = html;
        this.results.classList.remove('hidden');
        this.searchFooter.classList.remove('hidden');
        
        // Update footer
        this.resultsCount.textContent = `${total} resultados encontrados`;
        this.viewAllResults.href = `search.php?q=${encodeURIComponent(query)}`;
        
        // Add click events to results
        this.addResultClickEvents();
    }
    
    renderProductItem(product) {
        const imageUrl = product.image || 'assets/images/placeholder.jpg';
        const price = parseFloat(product.price);
        const originalPrice = product.original_price ? parseFloat(product.original_price) : null;
        const hasDiscount = originalPrice && originalPrice > price;
        
        return `
            <div class="search-result-item" data-type="product" data-id="${product.id}">
                <img src="${imageUrl}" alt="${product.name}" class="search-result-image" 
                     onerror="this.src='assets/images/placeholder.jpg'">
                <div class="search-result-content">
                    <h6 class="search-result-title">${product.name}</h6>
                    <p class="search-result-category">${product.category_name || 'Sin categoría'}</p>
                    <div class="flex items-center">
                        <span class="search-result-price">$${price.toLocaleString()}</span>
                        ${hasDiscount ? `<span class="search-result-discount">$${originalPrice.toLocaleString()}</span>` : ''}
                        ${hasDiscount ? '<span class="ml-2 text-xs bg-red-100 text-red-600 px-2 py-1 rounded">Oferta</span>' : ''}
                    </div>
                </div>
                <div class="text-gray-400">
                    <i class="fas fa-chevron-right"></i>
                </div>
            </div>
        `;
    }
    
    renderCategoryItem(category) {
        return `
            <div class="search-category-item" data-type="category" data-id="${category.id}">
                <div class="search-category-icon">
                    <i class="fas fa-${category.icon || 'tag'}"></i>
                </div>
                <div class="ml-3 flex-1">
                    <h6 class="font-medium text-gray-900 text-sm">${category.name}</h6>
                    <p class="text-xs text-gray-500">${category.product_count || 0} productos</p>
                </div>
                <div class="text-gray-400">
                    <i class="fas fa-chevron-right"></i>
                </div>
            </div>
        `;
    }
    
    renderBrandItem(brand) {
        return `
            <div class="search-brand-item" data-type="brand" data-id="${brand.id}">
                <div class="w-8 h-8 bg-gray-100 rounded-lg flex items-center justify-center">
                    <span class="text-xs font-bold text-gray-600">${brand.name.charAt(0)}</span>
                </div>
                <div class="ml-3 flex-1">
                    <h6 class="font-medium text-gray-900 text-sm">${brand.name}</h6>
                    <p class="text-xs text-gray-500">Marca</p>
                </div>
                <div class="text-gray-400">
                    <i class="fas fa-chevron-right"></i>
                </div>
            </div>
        `;
    }
    
    addResultClickEvents() {
        const items = this.results.querySelectorAll('[data-type]');
        items.forEach(item => {
            item.addEventListener('click', () => {
                const type = item.dataset.type;
                const id = item.dataset.id;
                
                switch(type) {
                    case 'product':
                        window.location.href = `product.php?id=${id}`;
                        break;
                    case 'category':
                        window.location.href = `categoria.php?id=${id}`;
                        break;
                    case 'brand':
                        window.location.href = `marcas.php?brand=${id}`;
                        break;
                    default:
                        this.viewAllSearchResults();
                }
            });
        });
    }
    
    viewAllSearchResults() {
        if (this.currentQuery) {
            window.location.href = `search.php?q=${encodeURIComponent(this.currentQuery)}`;
        }
    }
    
    // State management methods
    showEmptyState() {
        this.hideAllStates();
        this.emptyState.classList.remove('hidden');
        this.searchFooter.classList.add('hidden');
    }
    
    showLoading() {
        this.hideAllStates();
        this.resultsLoading.classList.remove('hidden');
        this.searchLoading.classList.remove('hidden');
    }
    
    hideLoading() {
        this.searchLoading.classList.add('hidden');
    }
    
    showNoResults() {
        this.hideAllStates();
        this.noResults.classList.remove('hidden');
        this.searchFooter.classList.add('hidden');
    }
    
    showError(message) {
        this.hideAllStates();
        this.noResults.classList.remove('hidden');
        this.noResults.querySelector('h4').textContent = 'Error en la búsqueda';
        this.noResults.querySelector('p').textContent = message;
        this.searchFooter.classList.add('hidden');
    }
    
    hideAllStates() {
        this.emptyState.classList.add('hidden');
        this.resultsLoading.classList.add('hidden');
        this.results.classList.add('hidden');
        this.noResults.classList.add('hidden');
    }
    
    clearSearch() {
        this.searchInput.value = '';
        this.currentQuery = '';
        this.currentResults = [];
        this.showEmptyState();
        
        // Reset quick filters
        this.quickFilters.forEach(btn => {
            btn.classList.remove('bg-primary-100', 'text-primary-700');
        });
    }
}

// Initialize global search when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    window.globalSearch = new GlobalSearch();
});

// Also make it available globally for other scripts
window.GlobalSearch = GlobalSearch;
