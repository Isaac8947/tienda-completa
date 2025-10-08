# Sistema de Productos Recomendados - Carrusel Horizontal

## 🎯 **FUNCIONALIDAD IMPLEMENTADA**

### ✅ **Sección de Productos Recomendados**
- **Ubicación**: `product.php` - Sección de productos recomendados
- **Tipo**: Carrusel horizontal responsive con las mismas cartas que `index.php`
- **Filtros disponibles**: Misma Categoría, Misma Marca, Populares, Precio Similar

### ✅ **Características del Carrusel**

#### **Navegación**
- 🖱️ **Botones de navegación**: Anterior/Siguiente con hover effects
- 📱 **Responsive**: 1 producto en móvil, 2 en tablet, 4 en desktop
- ⌨️ **Auto-hide**: Los botones aparecen solo al hacer hover sobre el carrusel
- 🔄 **Smooth transitions**: Animaciones fluidas de 500ms

#### **Cartas de Productos**
- 🖼️ **Imágenes**: Zoom al hover con overlay gradient
- 🏷️ **Badges**: Descuentos, marca, rating con estrellas
- 💰 **Precios**: Precio actual y tachado si hay descuento
- ⭐ **Rating**: Sistema de estrellas con conteo de reseñas
- 🛒 **Botones**: Agregar al carrito, wishlist, vista rápida
- 🎨 **Efectos**: Hover lift, scale, color transitions

#### **Tabs de Recomendaciones**
- 📂 **Misma Categoría**: Productos de la misma categoría
- 🏷️ **Misma Marca**: Solo si el producto tiene marca
- 🔥 **Populares**: Por número de vistas/ventas
- 💲 **Precio Similar**: Rango de precios parecido

### ✅ **Responsive Design**

#### **Desktop (1024px+)**
- 4 productos visibles simultáneamente
- Navegación con botones laterales
- Hover effects completos

#### **Tablet (768px - 1023px)**
- 2 productos visibles simultáneamente
- Navegación táctil y botones
- Efectos hover adaptados

#### **Móvil (<768px)**
- 1 producto visible
- Navegación principalmente táctil
- Cartas optimizadas para pantalla pequeña

### ✅ **Integración con APIs**

#### **API de Recomendaciones** (`api/recommendations.php`)
```php
GET /api/recommendations.php?product_id=X&type=category
```

**Tipos soportados**:
- `category`: Misma categoría
- `brand`: Misma marca  
- `popular`: Más populares
- `price`: Precio similar

### ✅ **Estados y Manejo de Errores**

#### **Loading State**
- Spinner animado durante carga
- Mensaje "Cargando productos recomendados..."

#### **Empty State**
- Ícono de caja vacía
- Mensaje "No hay productos similares"
- Botón para ir al catálogo completo

#### **Error State**
- Ícono de advertencia
- Mensaje de error
- Botón para reintentar

### ✅ **Funciones JavaScript Implementadas**

#### **Carrusel**
```javascript
// Navegación del carrusel
navigateRecommendedCarousel(direction)

// Actualización de posición
updateRecommendedCarouselPosition()

// Responsividad automática
getItemsPerView()

// Carga de productos
loadRecommendations(type)
```

#### **Event Listeners**
- Click en tabs de recomendaciones
- Navegación anterior/siguiente
- Resize window para responsividad

### ✅ **Estilos CSS Aplicados**

#### **Carrusel Container**
```css
.group:hover .opacity-0 { opacity: 1; }
.hover-lift { transform: translateY(-8px); }
.shimmer-effect { background: linear-gradient(...); }
```

#### **Cartas de Producto**
- Rounded corners: `rounded-3xl`
- Glass morphism: `backdrop-blur-sm`
- Shadows: `shadow-lg hover:shadow-xl`
- Gradients: `from-primary-500 to-secondary-500`

### ✅ **Performance Optimizations**

#### **Lazy Loading**
- Imágenes cargadas on-demand
- Debounce en resize events (250ms)

#### **Efficient DOM Updates**
- Template literals para rendering
- Minimal DOM manipulation
- CSS transforms para animaciones

### ✅ **Accesibilidad**

#### **Navegación**
- Botones con aria-labels implícitos
- Focus states visibles
- Keyboard navigation support

#### **Contenido**
- Alt text en imágenes
- Semantic HTML structure
- Screen reader friendly

## 🔧 **CONFIGURACIÓN**

### **Items por Vista**
```javascript
const itemsPerView = {
    mobile: 1,    // <768px
    tablet: 2,    // 768px-1023px  
    desktop: 4    // >=1024px
};
```

### **Timing de Animaciones**
```javascript
// Carrusel transition
transition-duration: 500ms

// Hover effects
transition-duration: 300ms

// Loading debounce
debounceTime: 250ms
```

## 🎨 **DISEÑO VISUAL**

### **Colores Utilizados**
- Primary: `#ec4899` (Rosa)
- Secondary: `#f59e0b` (Ámbar)
- Background: `from-luxury-pearl/30 via-white to-luxury-champagne/30`
- Cards: `bg-white/80 backdrop-blur-sm`

### **Efectos Especiales**
- Glass morphism en cartas
- Gradient overlays en imágenes
- Smooth scale transforms
- Floating animations en botones

## ✅ **ESTADO ACTUAL**
- 🟢 **Completamente funcional**
- 🟢 **Responsive en todos los dispositivos**
- 🟢 **Integrado con sistema de recomendaciones**
- 🟢 **Mismas cartas que index.php**
- 🟢 **Carrusel horizontal implementado**
- 🟢 **Syntax validado sin errores**

El sistema está listo para usar y proporciona una experiencia de navegación fluida y atractiva para los productos recomendados.
