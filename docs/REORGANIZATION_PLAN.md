# 📁 Plan de Reorganización de Código - Odisea Makeup Store

## 🎯 Estructura Objetivo

```
odisea-makeup-store/
├── 📁 app/                          # Lógica principal de la aplicación
│   ├── 📁 Controllers/              # Controladores MVC
│   ├── 📁 Models/                   # Modelos de datos
│   ├── 📁 Views/                    # Vistas/Templates
│   ├── 📁 Services/                 # Servicios de negocio
│   └── 📁 Middleware/               # Middleware y filtros
├── 📁 public/                       # Punto de entrada web (Document Root)
│   ├── index.php                    # Front controller
│   ├── 📁 assets/                   # Assets públicos
│   │   ├── 📁 css/
│   │   ├── 📁 js/
│   │   └── 📁 images/
│   └── 📁 uploads/                  # Archivos subidos por usuarios
├── 📁 pages/                        # Páginas principales
│   ├── catalogo.php
│   ├── carrito.php
│   ├── ofertas.php
│   └── ...
├── 📁 api/                          # Endpoints API
│   ├── 📁 cart/                     # APIs del carrito
│   ├── 📁 products/                 # APIs de productos
│   └── 📁 auth/                     # APIs de autenticación
├── 📁 admin/                        # Panel administrativo
│   ├── 📁 controllers/
│   ├── 📁 views/
│   └── 📁 assets/
├── 📁 config/                       # Configuraciones
│   ├── database.php
│   ├── app.php
│   └── constants.php
├── 📁 includes/                     # Includes compartidos
│   ├── 📁 components/               # Componentes reutilizables
│   ├── 📁 layouts/                  # Layouts base
│   └── 📁 partials/                 # Fragmentos HTML
├── 📁 utils/                        # Utilidades y helpers
│   ├── 📁 helpers/                  # Funciones auxiliares
│   ├── 📁 validators/               # Validadores
│   └── 📁 formatters/               # Formateadores
├── 📁 database/                     # Base de datos
│   ├── 📁 migrations/               # Migraciones
│   ├── 📁 seeds/                    # Datos de prueba
│   └── 📁 scripts/                  # Scripts de setup
├── 📁 storage/                      # Almacenamiento temporal
│   ├── 📁 cache/                    # Caché
│   ├── 📁 logs/                     # Logs
│   └── 📁 sessions/                 # Sesiones
├── 📁 tests/                        # Tests y debugging
│   ├── 📁 unit/                     # Tests unitarios
│   ├── 📁 integration/              # Tests de integración
│   └── 📁 debug/                    # Archivos de debug
├── 📁 docs/                         # Documentación
│   ├── 📁 api/                      # Documentación API
│   ├── 📁 deployment/               # Guías de deployment
│   └── 📁 development/              # Guías de desarrollo
└── 📁 vendor/                       # Dependencias (futuro Composer)
```

## 🔄 Mapeo de Archivos Actuales

### Páginas Principales → pages/
- index.php → public/index.php
- catalogo.php → pages/catalogo.php
- carrito.php → pages/carrito.php
- ofertas.php → pages/ofertas.php
- details.php → pages/details.php
- marcas.php → pages/marcas.php
- search.php → pages/search.php
- mi-cuenta.php → pages/mi-cuenta.php

### APIs del Carrito → api/cart/
- cart-add.php → api/cart/add.php
- cart-remove.php → api/cart/remove.php
- cart-update.php → api/cart/update.php
- cart-content.php → api/cart/content.php
- cart-count.php → api/cart/count.php

### Autenticación → api/auth/
- login.php → api/auth/login.php
- register.php → api/auth/register.php
- logout.php → api/auth/logout.php

### Tests y Debug → tests/debug/
- test-*.php → tests/debug/
- debug-*.php → tests/debug/

### Documentación → docs/
- *.md → docs/

### Scripts de Setup → database/scripts/
- setup_*.php → database/scripts/
- create-*.php → database/scripts/

## 🎯 Beneficios de esta Reorganización

1. **Separación de Responsabilidades** - Cada carpeta tiene un propósito específico
2. **Escalabilidad** - Estructura preparada para crecimiento
3. **Mantenibilidad** - Fácil localizar y modificar código
4. **Seguridad** - Document root limpio, archivos sensibles fuera del alcance web
5. **Estándares** - Sigue convenciones modernas de PHP
6. **Colaboración** - Estructura clara para nuevos desarrolladores

¿Procedemos con la reorganización?