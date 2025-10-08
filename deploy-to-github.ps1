# 🚀 Script para subir Odisea Makeup Store a GitHub
# ============================================================

Write-Host "🛍️ ODISEA MAKEUP STORE - GITHUB DEPLOYMENT" -ForegroundColor Cyan
Write-Host "=============================================" -ForegroundColor Cyan
Write-Host ""

# Verificar si Git está instalado
try {
    $gitVersion = git --version
    Write-Host "✅ Git encontrado: $gitVersion" -ForegroundColor Green
} catch {
    Write-Host "❌ Error: Git no está instalado o no está en el PATH" -ForegroundColor Red
    Write-Host "Por favor instala Git desde: https://git-scm.com/download/win" -ForegroundColor Yellow
    exit 1
}

# Verificar si estamos en el directorio correcto
if (!(Test-Path "index.php") -or !(Test-Path "config") -or !(Test-Path "models")) {
    Write-Host "❌ Error: No estás en el directorio del proyecto Odisea Makeup Store" -ForegroundColor Red
    Write-Host "Navega al directorio del proyecto antes de ejecutar este script" -ForegroundColor Yellow
    exit 1
}

Write-Host "✅ Directorio del proyecto verificado" -ForegroundColor Green

# Solicitar información del repositorio
Write-Host ""
Write-Host "📝 CONFIGURACIÓN DEL REPOSITORIO" -ForegroundColor Yellow
Write-Host "================================" -ForegroundColor Yellow

$repoName = Read-Host "Nombre del repositorio (ej: odisea-makeup-store)"
if ([string]::IsNullOrWhiteSpace($repoName)) {
    $repoName = "odisea-makeup-store"
    Write-Host "Usando nombre por defecto: $repoName" -ForegroundColor Gray
}

$githubUsername = Read-Host "Tu username de GitHub"
if ([string]::IsNullOrWhiteSpace($githubUsername)) {
    Write-Host "❌ Error: El username de GitHub es requerido" -ForegroundColor Red
    exit 1
}

$userEmail = Read-Host "Tu email de GitHub"
if ([string]::IsNullOrWhiteSpace($userEmail)) {
    Write-Host "❌ Error: El email es requerido" -ForegroundColor Red
    exit 1
}

Write-Host ""
Write-Host "🔧 CONFIGURANDO GIT..." -ForegroundColor Blue

# Configurar Git globalmente
git config --global user.name "$githubUsername"
git config --global user.email "$userEmail"
git config --global init.defaultBranch main

Write-Host "✅ Configuración de Git completada" -ForegroundColor Green

# Verificar si ya es un repositorio Git
if (Test-Path ".git") {
    Write-Host "⚠️  Este directorio ya es un repositorio Git" -ForegroundColor Yellow
    $reinit = Read-Host "¿Quieres reinicializar? (s/N)"
    if ($reinit -eq "s" -or $reinit -eq "S") {
        Remove-Item ".git" -Recurse -Force
        Write-Host "🗑️  Repositorio Git anterior eliminado" -ForegroundColor Yellow
    } else {
        Write-Host "Usando repositorio Git existente..." -ForegroundColor Gray
    }
}

# Inicializar repositorio Git
if (!(Test-Path ".git")) {
    Write-Host "🚀 Inicializando repositorio Git..." -ForegroundColor Blue
    git init
    Write-Host "✅ Repositorio Git inicializado" -ForegroundColor Green
}

# Verificar archivos sensibles antes de commit
Write-Host ""
Write-Host "🔍 VERIFICANDO ARCHIVOS SENSIBLES..." -ForegroundColor Blue

$sensitiveFiles = @(
    "config/database.php",
    "config/config.php",
    ".env"
)

foreach ($file in $sensitiveFiles) {
    if (Test-Path $file) {
        Write-Host "⚠️  Archivo sensible encontrado: $file" -ForegroundColor Yellow
        Write-Host "   Asegúrate de que no contenga credenciales reales de producción" -ForegroundColor Gray
    }
}

# Crear .gitignore si no existe
if (!(Test-Path ".gitignore")) {
    Write-Host "❌ .gitignore no encontrado, pero debería haber sido creado..." -ForegroundColor Red
    Write-Host "Verifica que el archivo .gitignore se haya creado correctamente" -ForegroundColor Yellow
}

# Agregar archivos al staging area
Write-Host ""
Write-Host "📦 PREPARANDO ARCHIVOS PARA COMMIT..." -ForegroundColor Blue

git add .
$addResult = $LASTEXITCODE

if ($addResult -eq 0) {
    Write-Host "✅ Archivos agregados al staging area" -ForegroundColor Green
} else {
    Write-Host "❌ Error al agregar archivos" -ForegroundColor Red
    exit 1
}

# Mostrar estado del repositorio
Write-Host ""
Write-Host "📊 ESTADO DEL REPOSITORIO:" -ForegroundColor Blue
git status --short

# Hacer commit inicial
Write-Host ""
Write-Host "💾 CREANDO COMMIT INICIAL..." -ForegroundColor Blue

$commitMessage = "🎉 Initial commit - Odisea Makeup Store E-commerce

✨ Features implemented:
• Responsive design with Tailwind CSS
• Product catalog with advanced filters
• Shopping cart with AJAX functionality  
• User authentication and profiles
• Mobile-first navigation with hamburger menu
• Scroll animations and smooth transitions
• CSRF protection and input validation
• Real-time search functionality
• Wishlist and reviews system
• Special offers and discounts

🚀 Tech Stack:
• PHP 8+ with OOP architecture
• MySQL database with PDO
• Vanilla JavaScript ES6+
• Tailwind CSS framework
• Font Awesome icons
• AOS animations library

📱 Mobile optimized and PWA ready"

git commit -m "$commitMessage"
$commitResult = $LASTEXITCODE

if ($commitResult -eq 0) {
    Write-Host "✅ Commit inicial creado exitosamente" -ForegroundColor Green
} else {
    Write-Host "❌ Error al crear commit inicial" -ForegroundColor Red
    exit 1
}

# Agregar origen remoto
Write-Host ""
Write-Host "🔗 CONFIGURANDO REPOSITORIO REMOTO..." -ForegroundColor Blue

$remoteUrl = "https://github.com/$githubUsername/$repoName.git"
git remote add origin $remoteUrl

Write-Host "✅ Origen remoto agregado: $remoteUrl" -ForegroundColor Green

# Información sobre crear el repositorio en GitHub
Write-Host ""
Write-Host "🌐 CREAR REPOSITORIO EN GITHUB" -ForegroundColor Yellow
Write-Host "==============================" -ForegroundColor Yellow
Write-Host "ANTES de continuar, necesitas crear el repositorio en GitHub:" -ForegroundColor White
Write-Host ""
Write-Host "1. Ve a: https://github.com/new" -ForegroundColor Cyan
Write-Host "2. Repository name: $repoName" -ForegroundColor Cyan  
Write-Host "3. Description: E-commerce moderno desarrollado en PHP con Tailwind CSS" -ForegroundColor Cyan
Write-Host "4. ✅ Public (recomendado para portafolio)" -ForegroundColor Cyan
Write-Host "5. ❌ NO inicializar con README, .gitignore ni license" -ForegroundColor Cyan
Write-Host "6. Click 'Create repository'" -ForegroundColor Cyan
Write-Host ""

$continuar = Read-Host "¿Ya creaste el repositorio en GitHub? (s/N)"

if ($continuar -ne "s" -and $continuar -ne "S") {
    Write-Host ""
    Write-Host "📋 RESUMEN DE COMANDOS PARA CONTINUAR MANUALMENTE:" -ForegroundColor Yellow
    Write-Host "=================================================" -ForegroundColor Yellow
    Write-Host "git branch -M main" -ForegroundColor White
    Write-Host "git push -u origin main" -ForegroundColor White
    Write-Host ""
    Write-Host "Ejecuta estos comandos después de crear el repositorio en GitHub" -ForegroundColor Gray
    exit 0
}

# Renombrar branch a main y push
Write-Host ""
Write-Host "🚀 SUBIENDO A GITHUB..." -ForegroundColor Blue

git branch -M main

Write-Host "Realizando push inicial..." -ForegroundColor Gray
git push -u origin main
$pushResult = $LASTEXITCODE

if ($pushResult -eq 0) {
    Write-Host ""
    Write-Host "🎉 ¡ÉXITO! PROYECTO SUBIDO A GITHUB" -ForegroundColor Green
    Write-Host "===================================" -ForegroundColor Green
    Write-Host ""
    Write-Host "🔗 Tu repositorio está disponible en:" -ForegroundColor Cyan
    Write-Host "   $remoteUrl" -ForegroundColor White
    Write-Host ""
    Write-Host "📋 Próximos pasos recomendados:" -ForegroundColor Yellow
    Write-Host "   1. Configura GitHub Pages si quieres demo online" -ForegroundColor Gray
    Write-Host "   2. Agrega topics/tags al repositorio para mejor visibilidad" -ForegroundColor Gray
    Write-Host "   3. Considera configurar GitHub Actions para CI/CD" -ForegroundColor Gray
    Write-Host "   4. Añade issues templates y contributing guidelines" -ForegroundColor Gray
    Write-Host ""
    Write-Host "✨ ¡Tu portafolio de e-commerce está listo para mostrar al mundo!" -ForegroundColor Magenta
} else {
    Write-Host ""
    Write-Host "❌ ERROR AL SUBIR A GITHUB" -ForegroundColor Red
    Write-Host "=========================" -ForegroundColor Red
    Write-Host ""
    Write-Host "Posibles causas:" -ForegroundColor Yellow
    Write-Host "1. El repositorio no existe en GitHub" -ForegroundColor Gray
    Write-Host "2. No tienes permisos para el repositorio" -ForegroundColor Gray
    Write-Host "3. Necesitas autenticación (token personal)" -ForegroundColor Gray
    Write-Host ""
    Write-Host "💡 Solución manual:" -ForegroundColor Cyan
    Write-Host "   git push -u origin main" -ForegroundColor White
    Write-Host ""
    Write-Host "Si necesitas token de acceso:" -ForegroundColor Yellow
    Write-Host "   https://github.com/settings/tokens" -ForegroundColor Cyan
}

Write-Host ""
Write-Host "Presiona cualquier tecla para salir..." -ForegroundColor Gray
$null = $Host.UI.RawUI.ReadKey("NoEcho,IncludeKeyDown")