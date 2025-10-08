# ⚠️ AVISO IMPORTANTE - REORGANIZACIÓN DE CARPETAS# 🛍️ Odisea Makeup Store



## 🔄 Reestructuración Completa del ProyectoUn e-commerce moderno y elegante desarrollado en PHP con diseño responsive y funcionalidades avanzadas.



**Fecha:** 7 de octubre de 2025  ## ✨ Características

**Versión:** 2.0  

### 🎨 **Diseño y UX**

### 📋 **Resumen de Cambios:**- **Responsive Design**: Completamente adaptable a móviles, tablets y desktop

- **Animaciones Fluidas**: Scroll animations, hover effects y transiciones suaves

Este proyecto ha sido **completamente reorganizado** para seguir estándares profesionales modernos. **Muchos archivos han cambiado de ubicación**.- **UI Moderna**: Uso de Tailwind CSS con gradientes y efectos visuales avanzados **Nota**: solo cambien la ubicion de los archivos tuve que reorganizar todo, espero y me comprendan.

- **Navigation Consistent**: Menú móvil unificado en todas las páginas

### 🚨 **IMPORTANTE PARA DESARROLLADORES:**

### 🛒 **Funcionalidades E-commerce**

Si estás trabajando con este código o hiciste fork del repositorio, **debes actualizar tus referencias** porque los archivos ya no están en su ubicación original.- **Catálogo de Productos**: Visualización con filtros avanzados y búsqueda en tiempo real

- **Carrito de Compras**: Gestión completa con AJAX y persistencia

### 📁 **Principales Cambios de Ubicación:**- **Sistema de Ofertas**: Descuentos automáticos y promociones especiales

- **Wishlist**: Lista de deseos para productos favoritos

| Tipo de Archivo | Ubicación Anterior | Nueva Ubicación |- **Reviews y Ratings**: Sistema de reseñas con calificaciones

|---|---|---|

| 📄 **Páginas** | `catalogo.php`, `ofertas.php`, etc. | `pages/catalogo.php`, `pages/ofertas.php` |### 🔐 **Seguridad**

| 🔌 **APIs** | `cart-add.php`, `login.php`, etc. | `api/cart/`, `api/auth/` |- **Protección CSRF**: Tokens de seguridad en formularios

| 🗄️ **DB Scripts** | `setup_*.php` | `database/scripts/` |- **Sanitización**: Limpieza de datos de entrada

| 📖 **Documentación** | `README.md`, `*.md` | `docs/` |- **Sesiones Seguras**: Configuración avanzada de sesiones PHP

| 🛠️ **Utilidades** | `fix-*.php`, `update-*.php` | `utils/` |- **Validación**: Validación tanto en frontend como backend

| 🧪 **Tests** | `test-*.php`, `debug-*.php` | `tests/debug/` |

## 📁 Estructura del Proyecto

### ✅ **Qué Hacer Si Encuentras Errores:**

### Archivos Principales (Producción)

1. **Actualizar includes/requires:**```

   ```php├── index.php               # Página principal

   // Cambiar: include 'catalogo.php';├── ofertas.php            # Página de ofertas

   // Por: include 'pages/catalogo.php';├── catalogo.php           # Catálogo de productos

   ```├── categoria.php          # Vista por categoría

├── details.php            # Detalles del producto

2. **Actualizar formularios:**├── search.php             # Búsqueda de productos

   ```html├── carrito.php            # Página del carrito

   <!-- Cambiar: action="cart-add.php" -->├── login.php / logout.php # Autenticación

   <!-- Por: action="api/cart/cart-add.php" -->├── register.php          # Registro de usuarios

   ```├── mi-cuenta.php         # Panel de usuario

├── newsletter-subscribe.php # Suscripción newsletter

3. **Actualizar enlaces:**├── wishlist-toggle.php   # Manejo de favoritos

   ```html└── 404.php               # Página de error

   <!-- Cambiar: href="ofertas.php" -->```

   <!-- Por: href="pages/ofertas.php" -->

   ```### Archivos del Carrito

```

### 🆕 **Nuevas Funcionalidades:**├── cart-add.php           # Agregar productos al carrito

├── cart-count.php         # Contador de productos

✨ **Front Controller:** `public/index.php` permite URLs limpias  ├── cart-content.php       # Contenido JSON del carrito

✨ **Funciones Auxiliares:** `includes/functions.php`  ├── cart-sidebar-content.php # HTML del sidebar del carrito

✨ **Configuración Centralizada:** `config/app.php`  ├── cart-update.php        # Actualizar cantidades

└── cart-remove.php        # Eliminar productos

### 📞 **Soporte:**```



Si necesitas ayuda con la migración: **catla6273@gmail.com**### Directorios Principales

```

---├── admin/                 # Panel de administración

├── admin-pages/          # Páginas específicas del admin

📖 **Ver detalles completos:** [RESTRUCTURE_NOTICE.md](RESTRUCTURE_NOTICE.md)├── assets/               # CSS, JS, imágenes estáticas

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

## 🤝 Contribuciones y Colaboración

¡Este proyecto está **abierto a contribuciones** de la comunidad! 🌟

### 💡 ¿Cómo Puedes Ayudar?

**Estoy buscando colaboradores que me ayuden a optimizar y mejorar el proyecto:**

- 🚀 **Optimización de performance** - Mejoras en velocidad y eficiencia
- 🔒 **Seguridad** - Implementar mejores prácticas de seguridad
- 📱 **Mobile optimization** - Mejorar experiencia en dispositivos móviles
- 🎨 **UX/UI** - Refinamiento visual y de experiencia de usuario
- 🔍 **SEO** - Mejoras en posicionamiento web
- 🐛 **Bug fixes** - Identificar y corregir errores
- 📖 **Documentación** - Mejorar guías y documentación

### 📋 Cómo Contribuir

1. **Lee la [Guía de Contribución](CONTRIBUTING.md)** 📖
2. **Haz fork del repositorio** 🍴
3. **Crea una rama para tu mejora** 🌿
4. **Implementa tus cambios** ⚡
5. **Envía un pull request** 📤

### 🏆 Reconocimiento

- Todos los contribuyentes aparecen en [CONTRIBUTORS.md](CONTRIBUTORS.md)
- Créditos en el código donde aplique
- Reconocimiento en futuras versiones

**¿Tienes ideas para mejorar el proyecto?** ¡Contáctame en catla6273@gmail.com!

## ⚖️ Licencia

Este proyecto usa una **Licencia de Propiedad con Contribuciones Abiertas**.

- ✅ **Permitido**: Estudiar código, contribuir mejoras, usar como referencia educativa
- ❌ **Restringido**: Uso comercial sin autorización, redistribuir como proyecto propio
- 🤝 **Contribuciones**: Bienvenidas y reconocidas, manteniendo créditos originales

Ver el archivo [LICENSE](LICENSE) para términos completos.

---
*Proyecto organizado y limpio - Open Source con Licencia Propietaria*
