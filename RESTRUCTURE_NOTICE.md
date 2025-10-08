# 📋 NOTA IMPORTANTE: Reorganización de Carpetas

## ⚠️ **CAMBIOS EN LA ESTRUCTURA DE ARCHIVOS**

**Fecha de reorganización:** 7 de octubre de 2025

### 🔄 **¿Qué cambió?**

Se realizó una **reorganización completa** de las carpetas y archivos del proyecto para mejorar la estructura y hacerla más profesional. Esto significa que **muchos archivos ya NO están en su ubicación original**.

### 📍 **Ubicaciones Anteriores vs Nuevas:**

#### **Páginas Principales:**
```
ANTES (raíz):          →  AHORA (pages/):
catalogo.php           →  pages/catalogo.php
ofertas.php            →  pages/ofertas.php  
carrito.php            →  pages/carrito.php
details.php            →  pages/details.php
marcas.php             →  pages/marcas.php
search.php             →  pages/search.php
mi-cuenta.php          →  pages/mi-cuenta.php
categoria.php          →  pages/categoria.php
```

#### **APIs y Funcionalidades:**
```
ANTES (raíz):              →  AHORA (api/):
cart-add.php               →  api/cart/cart-add.php
cart-remove.php            →  api/cart/cart-remove.php
cart-update.php            →  api/cart/cart-update.php
cart-content.php           →  api/cart/cart-content.php
cart-count.php             →  api/cart/cart-count.php
login.php                  →  api/auth/login.php
register.php               →  api/auth/register.php
logout.php                 →  api/auth/logout.php
product.php                →  api/products/product.php
procesar-pedido.php        →  api/procesar-pedido.php
finalizar-pedido.php       →  api/finalizar-pedido.php
review-*.php               →  api/review-*.php
wishlist-toggle.php        →  api/wishlist-toggle.php
```

#### **Scripts de Base de Datos:**
```
ANTES (raíz):                    →  AHORA (database/scripts/):
setup_categories_brands.php     →  database/scripts/setup_categories_brands.php
setup_initial_stock.php         →  database/scripts/setup_initial_stock.php
setup_inventory_system.php      →  database/scripts/setup_inventory_system.php
check_tables.php                →  database/scripts/check_tables.php
activate_products.php           →  database/scripts/activate_products.php
```

#### **Testing y Debug:**
```
ANTES (raíz):              →  AHORA (tests/debug/):
test-*.php                 →  [ELIMINADOS - eran temporales]
test-*.html                →  tests/debug/test-*.html
debug-*.php                →  [ELIMINADOS - eran temporales]  
debug-*.html               →  tests/debug/debug-*.html
```

#### **Documentación:**
```
ANTES (raíz):              →  AHORA (docs/):
README.md                  →  docs/README.md
CONTRIBUTING.md            →  docs/CONTRIBUTING.md
ROADMAP.md                 →  docs/ROADMAP.md
*.md                       →  docs/*.md
deploy-*.sh/.ps1           →  docs/deployment/
```

#### **Utilidades:**
```
ANTES (raíz):              →  AHORA (utils/):
fix-*.php                  →  utils/fix-*.php
update-*.php               →  utils/update-*.php
check-*.php                →  utils/check-*.php
```

### 🚨 **ACCIONES REQUERIDAS:**

#### **Si tienes enlaces o includes en tu código:**
```php
// ❌ ANTES (ya no funciona):
include 'catalogo.php';
require_once 'cart-add.php';
header('Location: ofertas.php');

// ✅ AHORA (actualizar a):
include 'pages/catalogo.php';
require_once 'api/cart/cart-add.php';  
header('Location: pages/ofertas.php');
```

#### **Si tienes formularios con action:**
```html
<!-- ❌ ANTES (ya no funciona): -->
<form action="cart-add.php">
<form action="login.php">
<form action="procesar-pedido.php">

<!-- ✅ AHORA (actualizar a): -->
<form action="api/cart/cart-add.php">
<form action="api/auth/login.php">
<form action="api/procesar-pedido.php">
```

#### **Si tienes enlaces directos:**
```html
<!-- ❌ ANTES (ya no funciona): -->
<a href="catalogo.php">Ver Catálogo</a>
<a href="ofertas.php">Ofertas</a>

<!-- ✅ AHORA (actualizar a): -->
<a href="pages/catalogo.php">Ver Catálogo</a>
<a href="pages/ofertas.php">Ofertas</a>
```

### 🆕 **NUEVA FUNCIONALIDAD:**

#### **Front Controller (Recomendado):**
Se agregó un **front controller** en `public/index.php` que permite URLs más limpias:

```
✨ URLs limpias disponibles:
http://localhost/odisea-makeup-store/public/catalogo
http://localhost/odisea-makeup-store/public/ofertas  
http://localhost/odisea-makeup-store/public/carrito
http://localhost/odisea-makeup-store/public/marcas
```

### 📝 **Para Desarrolladores:**

1. **Revisa todos los includes/requires** en tu código
2. **Actualiza los action de formularios**  
3. **Verifica enlaces internos**
4. **Considera usar el nuevo front controller** para URLs limpias
5. **Utiliza las nuevas funciones** en `includes/functions.php`

### 🎯 **Beneficios de los Cambios:**

- ✅ **Código más organizado** y fácil de mantener
- ✅ **Estructura profesional** estándar en la industria  
- ✅ **Mejor seguridad** (archivos sensibles protegidos)
- ✅ **Preparado para colaboradores** y crecimiento futuro
- ✅ **APIs bien organizadas** por funcionalidad

### 📞 **¿Necesitas Ayuda?**

Si encuentras errores después de estos cambios o necesitas ayuda actualizando tu código:

📧 **Contacto:** catla6273@gmail.com  
🐙 **GitHub:** Isaac8947

---

**⚠️ Esta nota es importante para cualquier persona que trabaje con el código o haga fork del repositorio.**