# 🔍 Sistema de Búsqueda en Tiempo Real con AJAX

## Descripción General

Se ha implementado un sistema completo de búsqueda en tiempo real con AJAX que funciona en todas las páginas del sitio web. El sistema proporciona resultados instantáneos mientras el usuario escribe, con una interfaz moderna y responsive.

## ✨ Características Principales

- **Búsqueda instantánea**: Resultados en tiempo real con debouncing de 300ms
- **Búsqueda inteligente**: Busca en productos, categorías y marcas
- **Algoritmo de relevancia**: Los resultados se ordenan por relevancia
- **Filtros rápidos**: Botones para búsquedas predefinidas (Ofertas, Nuevos, etc.)
- **Responsive**: Funciona perfectamente en desktop y móvil
- **Accesibilidad**: Navegación por teclado y atajos (Ctrl+K)
- **Rate limiting**: Protección contra spam (60 búsquedas por minuto)

## 🏗️ Arquitectura del Sistema

### Frontend

**Archivo**: `assets/js/global-search.js`
- Clase `GlobalSearch` que maneja toda la funcionalidad
- Eventos de teclado, mouse y táctiles
- Debouncing inteligente para optimizar peticiones
- Estados de loading, error y sin resultados

**Componente UI**: `includes/global-search.php`
- Modal overlay para la búsqueda
- Estados visuales (vacío, cargando, resultados, error)
- Filtros rápidos
- Diseño responsive

### Backend

**API Endpoint**: `api/search-realtime.php`
- Endpoint optimizado para búsqueda rápida
- Sanitización de inputs con `InputSanitizer`
- Rate limiting con `RateLimiter`
- Algoritmo de relevancia personalizado

**Modelos actualizados**:
- `Product::searchProductsRealtime()` - Búsqueda optimizada de productos
- `Product::countSearchResults()` - Conteo total de resultados
- `Category::searchCategories()` - Búsqueda en categorías
- `Brand::searchBrands()` - Búsqueda en marcas

## 📁 Archivos Involucrados

```
assets/js/global-search.js          - JavaScript principal
includes/global-search.php          - Componente UI
includes/global-header.php          - Header actualizado
includes/footer.php                 - Footer con scripts
api/search-realtime.php            - API endpoint
models/Product.php                  - Modelo actualizado
models/Category.php                 - Modelo actualizado
models/Brand.php                    - Modelo actualizado
demo-search.php                     - Página de demostración
```

## 🚀 Cómo Usar

### Para Usuarios

1. **Abrir búsqueda**:
   - Clic en el icono 🔍 en el header
   - Atajo de teclado: `Ctrl+K` (Windows) o `Cmd+K` (Mac)

2. **Buscar**:
   - Escribir cualquier término
   - Los resultados aparecen automáticamente
   - Usar filtros rápidos para búsquedas predefinidas

3. **Navegar resultados**:
   - Clic en cualquier resultado para ir al producto/categoría/marca
   - Enter para ir al primer resultado
   - "Ver todos los resultados" para página de búsqueda completa

### Para Desarrolladores

1. **Incluir en una página**:
```php
<?php include 'includes/global-search.php'; ?>
```

2. **Cargar el script**:
```html
<script src="assets/js/global-search.js"></script>
```

3. **Inicialización automática**:
El script se inicializa automáticamente cuando el DOM está listo.

## 🛠️ Configuración

### Rate Limiting
Editar en `api/search-realtime.php`:
```php
// 60 búsquedas por minuto
RateLimiter::checkLimit('search_realtime', 60, 60)
```

### Límite de resultados
```php
// Máximo 20 resultados para búsqueda rápida
$limit = min(20, max(1, (int)($input['limit'] ?? 8)));
```

### Tiempo de debouncing
Editar en `assets/js/global-search.js`:
```javascript
// 300ms de debounce
setTimeout(() => {
    this.performSearch();
}, 300);
```

## 📊 Algoritmo de Relevancia

El sistema usa un algoritmo de puntuación que considera:

1. **Coincidencia exacta** (100 puntos)
2. **Empieza con el término** (80 puntos)
3. **Contiene el término** (60 puntos)
4. **Coincidencia de palabras** (20 puntos por palabra)

Los productos destacados (`is_featured = 1`) tienen prioridad adicional.

## 🔧 API Response Format

```json
{
    "success": true,
    "results": [
        {
            "type": "product",
            "id": 123,
            "name": "iPhone 15 Pro",
            "category_name": "Smartphones",
            "price": 999.99,
            "original_price": null,
            "image": "assets/images/iphone-15-pro.jpg",
            "relevance": 100
        }
    ],
    "total": 25,
    "query": "iphone",
    "execution_time": "15.67ms"
}
```

## 🎨 Personalización de Estilos

El componente usa clases de Tailwind CSS. Para personalizar:

1. **Colores del modal**: Editar clases `bg-` y `text-` en `global-search.php`
2. **Animaciones**: Modificar clases `transition-` y `duration-`
3. **Responsive**: Ajustar breakpoints `sm:`, `md:`, `lg:`

## 🔐 Seguridad

- **Rate limiting**: 60 búsquedas por minuto por IP
- **Sanitización**: Todos los inputs son sanitizados
- **Validación**: Longitud mínima y máxima de búsqueda
- **CORS**: Headers configurados para peticiones cross-origin
- **SQL Injection**: Uso de prepared statements

## 🧪 Testing

**Página de prueba**: `demo-search.php`

**Script de prueba**: `test_search.php`
```bash
php test_search.php
```

**Datos de prueba**: 
```bash
php setup_categories_brands.php  # Crear categorías y marcas
php setup_search_data.php        # Agregar productos de prueba
```

## 📱 Soporte Móvil

- **Touch-friendly**: Botones y áreas de toque optimizados
- **Viewport**: Modal se ajusta al tamaño de pantalla
- **Scroll**: Resultados con scroll interno en pantallas pequeñas
- **Teclado virtual**: Compatible con teclados móviles

## 🚀 Rendimiento

- **Debouncing**: Reduce peticiones innecesarias
- **Caché de resultados**: Los resultados se cachean localmente
- **Límite de resultados**: Máximo 8-20 resultados para búsqueda rápida
- **Índices de base de datos**: Asegurar índices en campos de búsqueda

## 📈 Métricas y Analytics

Para implementar analytics, agregar en `performSearch()`:
```javascript
// Track search events
gtag('event', 'search', {
    search_term: query,
    results_count: data.total
});
```

## 🔄 Actualizaciones Futuras

- [ ] Histórico de búsquedas
- [ ] Sugerencias de búsqueda
- [ ] Búsqueda por voz
- [ ] Filtros avanzados en el modal
- [ ] Navegación por teclado entre resultados
- [ ] Caché inteligente de resultados
- [ ] Analytics detallados

---

## 🎉 Estado Actual

✅ **Sistema completamente funcional**
- Búsqueda en tiempo real implementada
- Interface responsive y moderna
- API optimizada y segura
- Productos de prueba agregados
- Documentación completa

**Demo disponible en**: `http://localhost:8000/demo-search.php`
