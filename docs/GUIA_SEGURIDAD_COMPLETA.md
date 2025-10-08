# 🛡️ GUÍA COMPLETA DE SEGURIDAD - ODISEA MAKEUP STORE

## ✅ LO QUE YA ESTÁ IMPLEMENTADO

✅ **Clases de Seguridad Creadas:**
- `includes/security-headers.php` - Headers de seguridad
- `includes/CSRFProtection.php` - Protección contra CSRF
- `includes/InputSanitizer.php` - Sanitización de entradas
- `includes/RateLimiter.php` - Limitación de tasa
- `includes/SecureUploader.php` - Subida segura de archivos
- `includes/LoginProtection.php` - Protección de login
- `includes/SecurityConfig.php` - Configuración de seguridad

✅ **Headers de Seguridad Aplicados:**
- Archivos principales ya incluyen security-headers.php
- Protección contra clickjacking, XSS, MIME sniffing

✅ **Directorio de Logs Creado:**
- `_logs/` - Logs de seguridad protegidos
- Monitoreo de actividad sospechosa

---

## 🚨 PASOS CRÍTICOS PARA COMPLETAR LA SEGURIDAD

### 1. AÑADIR CSRF A TODOS LOS FORMULARIOS

**En cada formulario HTML, añade:**
```php
<?php 
require_once 'includes/CSRFProtection.php';
$csrfToken = CSRFProtection::generateToken('nombre_formulario');
?>

<form method="POST">
    <?php echo CSRFProtection::getTokenInput('nombre_formulario'); ?>
    <!-- resto del formulario -->
</form>
```

**En el procesamiento PHP:**
```php
if (!CSRFProtection::validateToken($_POST['csrf_token'], 'nombre_formulario')) {
    die('Token CSRF inválido');
}
```

### 2. SANITIZAR TODAS LAS ENTRADAS

**Para cada input del usuario:**
```php
require_once 'includes/InputSanitizer.php';

// Sanitizar strings
$nombre = InputSanitizer::sanitizeString($_POST['nombre']);

// Sanitizar emails
$email = InputSanitizer::sanitizeEmail($_POST['email']);

// Sanitizar números
$cantidad = InputSanitizer::sanitizeInt($_POST['cantidad'], 1, 100);

// Escapar para HTML
echo InputSanitizer::escapeHTML($texto);
```

### 3. IMPLEMENTAR RATE LIMITING

**En páginas críticas (login, registro, contacto):**
```php
require_once 'includes/RateLimiter.php';

// Al inicio del archivo
if (!RateLimiter::checkLimit('login', 5, 900)) {
    die('Demasiados intentos. Inténtalo más tarde.');
}
```

### 4. PROTEGER ARCHIVOS AJAX

**En cart-add.php, review-like.php, etc:**
```php
// Al inicio del archivo
require_once 'includes/security-headers.php';
require_once 'includes/RateLimiter.php';
require_once 'includes/CSRFProtection.php';

if (!RateLimiter::checkLimit('cart', 20, 300)) {
    http_response_code(429);
    echo json_encode(['error' => 'Demasiadas peticiones']);
    exit;
}

if (!CSRFProtection::validateToken($_POST['csrf_token'], 'cart')) {
    http_response_code(403);
    echo json_encode(['error' => 'Token inválido']);
    exit;
}
```

### 5. ACTUALIZAR JAVASCRIPT PARA CSRF

**En tus archivos JavaScript:**
```javascript
// Función para obtener token CSRF
function getCSRFToken(formName = 'default') {
    // Podrías obtenerlo de un meta tag o endpoint
    return document.querySelector('meta[name="csrf-token"]').content;
}

// En llamadas AJAX
$.ajax({
    url: 'cart-add.php',
    method: 'POST',
    data: {
        csrf_token: getCSRFToken('cart'),
        product_id: productId,
        quantity: quantity
    },
    success: function(response) {
        // manejar respuesta
    }
});
```

---

## 🔧 IMPLEMENTACIÓN ESPECÍFICA POR ARCHIVO

### LOGIN.PHP
```php
<?php
require_once 'includes/security-headers.php';
require_once 'includes/LoginProtection.php';
require_once 'includes/CSRFProtection.php';

if ($_POST) {
    $result = LoginProtection::validateLogin($_POST['email'], $_POST['password']);
    if (!$result['success']) {
        $error = $result['error'];
    }
}

$csrfToken = CSRFProtection::generateToken('login');
?>

<!-- En el HTML del formulario -->
<input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
```

### CART-ADD.PHP
```php
<?php
require_once 'includes/security-headers.php';
require_once 'includes/RateLimiter.php';
require_once 'includes/CSRFProtection.php';
require_once 'includes/InputSanitizer.php';

header('Content-Type: application/json');

if (!RateLimiter::checkLimit('cart', 30, 300)) {
    http_response_code(429);
    echo json_encode(['success' => false, 'error' => 'Demasiadas peticiones']);
    exit;
}

if (!CSRFProtection::validateToken($_POST['csrf_token'], 'cart')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Token inválido']);
    exit;
}

$productId = InputSanitizer::sanitizeInt($_POST['product_id'], 1, 999999);
$quantity = InputSanitizer::sanitizeInt($_POST['quantity'], 1, 100);

// resto de tu lógica...
?>
```

### SEARCH.PHP
```php
<?php
require_once 'includes/security-headers.php';
require_once 'includes/InputSanitizer.php';
require_once 'includes/RateLimiter.php';

if (!RateLimiter::checkLimit('search', 20, 300)) {
    header('Location: /?error=too_many_searches');
    exit;
}

if (isset($_GET['q'])) {
    $searchQuery = InputSanitizer::sanitizeString($_GET['q'], 100);
    
    if (InputSanitizer::detectSQLInjection($searchQuery) || InputSanitizer::detectXSS($searchQuery)) {
        InputSanitizer::logSuspiciousActivity($searchQuery, 'SEARCH_ATTACK');
        header('Location: /?error=invalid_search');
        exit;
    }
    
    $_GET['q'] = $searchQuery;
}
?>
```

---

## 🔍 TESTING Y MONITOREO

### Ejecutar Tests de Seguridad
```bash
php _tests/security-test-suite.php
php _tests/security-validator.php
```

### Monitorear Logs
```bash
# Ver logs de seguridad
tail -f _logs/security.log

# Ver intentos de login fallidos
grep "FAILED LOGIN" _logs/security.log

# Ver ataques detectados
grep "SUSPICIOUS" _logs/security.log
```

### Configurar Alertas
Crea un script que revise los logs y te envíe alertas:
```php
// monitor-security.php
$suspicious = file_get_contents('_logs/security.log');
if (strpos($suspicious, 'ATTACK') !== false) {
    mail('admin@tudominio.com', 'Ataque detectado', $suspicious);
}
```

---

## 🚀 CONFIGURACIÓN DE PRODUCCIÓN

### .htaccess para Apache
```apache
# Seguridad adicional
Header always set X-Frame-Options DENY
Header always set X-Content-Type-Options nosniff
Header always set X-XSS-Protection "1; mode=block"

# Redireccionar a HTTPS
RewriteEngine On
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]

# Proteger archivos sensibles
<Files "*.log">
    Order Allow,Deny
    Deny from all
</Files>

<Files "config.php">
    Order Allow,Deny
    Deny from all
</Files>
```

### PHP.ini (Configuración del servidor)
```ini
; Deshabilitar funciones peligrosas
disable_functions = exec,system,shell_exec,passthru,eval

; Configurar sesiones
session.cookie_httponly = 1
session.cookie_secure = 1
session.cookie_samesite = Strict
session.gc_maxlifetime = 1800

; Ocultar información del servidor
expose_php = Off

; Configurar subida de archivos
file_uploads = On
upload_max_filesize = 5M
max_file_uploads = 3
```

---

## 📊 CHECKLIST DE SEGURIDAD FINAL

- [ ] ✅ Headers de seguridad aplicados
- [ ] ⚠️  CSRF tokens en todos los formularios
- [ ] ⚠️  Sanitización de todas las entradas
- [ ] ⚠️  Rate limiting en formularios críticos
- [ ] ⚠️  Validación de sesiones de usuario
- [ ] ⚠️  Protección de archivos AJAX
- [ ] ⚠️  JavaScript actualizado con CSRF
- [ ] ⚠️  HTTPS habilitado (producción)
- [ ] ✅ Logs de seguridad configurados
- [ ] ⚠️  Monitoreo de logs automatizado
- [ ] ⚠️  Backup de archivos originales
- [ ] ⚠️  Tests de penetración ejecutados

---

## 🆘 RESPUESTA A EMERGENCIAS

### Si detectas un ataque:
1. **Revisa logs:** `tail -f _logs/security.log`
2. **Bloquea IP manualmente:** Añade a `.htaccess`
3. **Cambia passwords críticos**
4. **Revisa integridad de archivos**
5. **Actualiza todas las dependencias**

### Contacto para soporte:
- Revisa documentación en `/docs/security`
- Ejecuta tests: `php _tests/security-test-suite.php`
- Monitorea: `_logs/security.log`

---

## 🎯 PRÓXIMOS PASOS INMEDIATOS

1. **Añade CSRF a tu formulario de login AHORA**
2. **Prueba que todo sigue funcionando**
3. **Ejecuta el test de seguridad**
4. **Implementa gradualmente en otros formularios**
5. **Habilita HTTPS en producción**

¡Tu aplicación está mucho más segura! 🛡️🚀
