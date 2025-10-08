<?php
require_once 'config/config.php';

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Brand Search AJAX</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-gray-50">
    <div class="container mx-auto px-4 py-8">
        <h1 class="text-2xl font-bold mb-6">Test Brand Search AJAX</h1>
        
        <!-- Search Input -->
        <div class="relative mb-6">
            <input type="text" 
                   id="test-search" 
                   class="w-full px-4 py-3 pl-10 border border-gray-300 rounded-lg focus:ring-2 focus:ring-rose-500 focus:border-transparent"
                   placeholder="Buscar marcas...">
            <i class="fas fa-search absolute left-3 top-4 text-gray-400"></i>
        </div>
        
        <!-- Results Container -->
        <div id="test-results" class="bg-white rounded-lg shadow-lg p-4 hidden">
            <div id="test-results-content"></div>
        </div>
        
        <!-- Test Log -->
        <div class="mt-6">
            <h2 class="text-lg font-semibold mb-2">Test Log:</h2>
            <div id="test-log" class="bg-gray-100 p-4 rounded-lg text-sm font-mono"></div>
        </div>
    </div>

    <script>
        const testInput = document.getElementById('test-search');
        const testResults = document.getElementById('test-results');
        const testResultsContent = document.getElementById('test-results-content');
        const testLog = document.getElementById('test-log');
        
        let searchTimeout;
        
        function log(message) {
            const timestamp = new Date().toLocaleTimeString();
            testLog.innerHTML += `[${timestamp}] ${message}\n`;
            testLog.scrollTop = testLog.scrollHeight;
            console.log(message);
        }
        
        function showLoading() {
            testResultsContent.innerHTML = `
                <div class="flex items-center justify-center py-4">
                    <div class="animate-spin rounded-full h-6 w-6 border-b-2 border-rose-500"></div>
                    <span class="ml-2 text-gray-600">Buscando...</span>
                </div>
            `;
            testResults.classList.remove('hidden');
        }
        
        function hideResults() {
            testResults.classList.add('hidden');
        }
        
        function performSearch(query) {
            log(`Iniciando búsqueda para: "${query}"`);
            showLoading();
            
            const url = `api/search.php?q=${encodeURIComponent(query)}&type=brands&limit=8`;
            log(`URL: ${url}`);
            
            fetch(url)
                .then(response => {
                    log(`Respuesta HTTP: ${response.status}`);
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.text();
                })
                .then(text => {
                    log(`Texto recibido: ${text.substring(0, 100)}...`);
                    try {
                        const data = JSON.parse(text);
                        log(`JSON parseado correctamente`);
                        displayResults(data, query);
                    } catch (e) {
                        log(`Error parseando JSON: ${e.message}`);
                        showError(text);
                    }
                })
                .catch(error => {
                    log(`Error en fetch: ${error.message}`);
                    showError('Error de conexión');
                });
        }
        
        function displayResults(data, query) {
            let html = '<div class="space-y-3">';
            
            if (data.brands && data.brands.length > 0) {
                html += `<div class="mb-4">
                    <h3 class="font-semibold text-gray-700 mb-2">Marcas encontradas (${data.brands.length}):</h3>
                    <div class="space-y-2">`;
                
                data.brands.forEach(brand => {
                    html += `
                        <div class="flex items-center space-x-3 p-2 bg-gray-50 rounded-lg">
                            <div class="flex-1">
                                <h4 class="font-medium">${brand.name}</h4>
                                <p class="text-sm text-gray-500">${brand.product_count || 0} productos</p>
                            </div>
                        </div>
                    `;
                });
                
                html += '</div></div>';
            }
            
            if (data.products && data.products.length > 0) {
                html += `<div class="mb-4">
                    <h3 class="font-semibold text-gray-700 mb-2">Productos encontrados (${data.products.length}):</h3>
                    <div class="space-y-2">`;
                
                data.products.forEach(product => {
                    const price = new Intl.NumberFormat('es-CO', {
                        style: 'currency',
                        currency: 'COP',
                        minimumFractionDigits: 0
                    }).format(product.price);
                    
                    html += `
                        <div class="flex items-center space-x-3 p-2 bg-gray-50 rounded-lg">
                            <div class="flex-1">
                                <h4 class="font-medium">${product.name}</h4>
                                <p class="text-sm text-gray-500">${product.brand_name || 'Sin marca'} - ${price}</p>
                            </div>
                        </div>
                    `;
                });
                
                html += '</div></div>';
            }
            
            if ((!data.brands || data.brands.length === 0) && 
                (!data.products || data.products.length === 0)) {
                html += '<p class="text-center text-gray-500 py-4">No se encontraron resultados</p>';
            }
            
            html += '</div>';
            testResultsContent.innerHTML = html;
            
            log(`Resultados mostrados: ${(data.brands?.length || 0)} marcas, ${(data.products?.length || 0)} productos`);
        }
        
        function showError(message) {
            testResultsContent.innerHTML = `
                <div class="text-center py-4 text-red-500">
                    <i class="fas fa-exclamation-triangle text-2xl mb-2"></i>
                    <p>Error: ${message}</p>
                </div>
            `;
        }
        
        // Event listener
        testInput.addEventListener('input', function() {
            const query = this.value.trim();
            
            clearTimeout(searchTimeout);
            
            if (query.length >= 2) {
                searchTimeout = setTimeout(() => {
                    performSearch(query);
                }, 300);
            } else if (query.length === 0) {
                hideResults();
                log('Búsqueda limpiada');
            }
        });
        
        log('Test inicializado correctamente');
    </script>
</body>
</html>
