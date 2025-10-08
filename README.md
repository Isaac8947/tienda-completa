# 🛍️ Odisea Makeup Store

Un e-commerce moderno y elegante desarrollado en PHP con diseño responsive y funcionalidades avanzadas.

## ✨ Características

### 🎨 **Diseño y UX**
- **Responsive Design**: Completamente adaptable a móviles, tablets y desktop
- **Animaciones Fluidas**: Scroll animations, hover effects y transiciones suaves
- **UI Moderna**: Uso de Tailwind CSS con gradientes y efectos visuales avanzados
- **Navigation Consistent**: Menú móvil unificado en todas las páginas

### 🛒 **Funcionalidades E-commerce**
- **Catálogo de Productos**: Visualización con filtros avanzados y búsqueda en tiempo real
- **Carrito de Compras**: Gestión completa con AJAX y persistencia
- **Sistema de Ofertas**: Descuentos automáticos y promociones especiales
- **Wishlist**: Lista de deseos para productos favoritos
- **Reviews y Ratings**: Sistema de reseñas con calificaciones

### 🔐 **Seguridad**
- **Protección CSRF**: Tokens de seguridad en formularios
- **Sanitización**: Limpieza de datos de entrada
- **Sesiones Seguras**: Configuración avanzada de sesiones PHP
- **Validación**: Validación tanto en frontend como backend

## 📁 Estructura del Proyecto

### Archivos Principales (Producción)
```
├── index.php               # Página principal
├── ofertas.php            # Página de ofertas
├── catalogo.php           # Catálogo de productos
├── categoria.php          # Vista por categoría
├── details.php            # Detalles del producto
├── search.php             # Búsqueda de productos
├── carrito.php            # Página del carrito
├── login.php / logout.php # Autenticación
├── register.php          # Registro de usuarios
├── mi-cuenta.php         # Panel de usuario
├── newsletter-subscribe.php # Suscripción newsletter
├── wishlist-toggle.php   # Manejo de favoritos
└── 404.php               # Página de error
```

### Archivos del Carrito
```
├── cart-add.php           # Agregar productos al carrito
├── cart-count.php         # Contador de productos
├── cart-content.php       # Contenido JSON del carrito
├── cart-sidebar-content.php # HTML del sidebar del carrito
├── cart-update.php        # Actualizar cantidades
└── cart-remove.php        # Eliminar productos
```

### Directorios Principales
```
├── admin/                 # Panel de administración
├── admin-pages/          # Páginas específicas del admin
├── assets/               # CSS, JS, imágenes estáticas
├── cache/                # Archivos de caché (auto-generados)
├── config/               # Configuraciones de la aplicación
├── includes/             # Headers, footers, componentes reutilizables
├── models/               # Modelos de datos (Product, User, etc.)
├── scripts/              # Scripts de base de datos y configuración
└── uploads/              # Archivos subidos por usuarios
```

### Directorios de Organización
```
├── _backups/             # Versiones anteriores y archivos de respaldo
├── _deprecated/          # Archivos obsoletos pero conservados
├── _tests/               # Archivos de testing y debug
└── _utils/               # Herramientas de utilidad y documentación
```

## 🚀 Funcionalidades Principales

### ✅ Sistema de Carrito
- Sidebar lateral (igual en todas las páginas)
- Agregar/eliminar productos
- Actualizar cantidades
- Persistencia en sesión

### ✅ Sistema de Ofertas
- Productos con descuentos
- Filtros por categoría, marca, precio
- Paginación optimizada
- Sistema de caché

### ✅ Panel de Administración
- Gestión de productos
- Configuraciones globales
- Gestión de usuarios
- Analytics y reportes

### ✅ Configuraciones Globales
- Banner dinámico
- Información de contacto
- Redes sociales
- Configuraciones de tienda

## 🛠️ Mantenimiento

### Limpieza de Caché
```bash
php _utils/clean-cache.php
```

### Configuración de Base de Datos
Los scripts están en `/scripts/` para configuración inicial.

### Testing
Los archivos de test están en `/_tests/` para debugging.

## 📋 Estado del Proyecto
- ✅ Sistema de carrito funcional
- ✅ Página de ofertas optimizada
- ✅ Panel administrativo completo
- ✅ Configuraciones globales
- ✅ Sistema de autenticación
- ✅ Responsive design

---
*Proyecto organizado y limpio - $(Get-Date)*
