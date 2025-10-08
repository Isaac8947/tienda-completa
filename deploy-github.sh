#!/bin/bash
# 🚀 Script simple para subir a GitHub

echo "🛍️ ODISEA MAKEUP STORE - GITHUB DEPLOYMENT"
echo "============================================="
echo ""

# Verificar Git
if ! command -v git &> /dev/null; then
    echo "❌ Git no está instalado"
    exit 1
fi

echo "✅ Git encontrado"

# Solicitar información
read -p "Username de GitHub: " GITHUB_USER
read -p "Nombre del repositorio (odisea-makeup-store): " REPO_NAME
REPO_NAME=${REPO_NAME:-odisea-makeup-store}

echo ""
echo "🔧 Configurando Git..."

# Configurar Git
git config --global user.name "$GITHUB_USER"
git config --global init.defaultBranch main

# Inicializar si no existe
if [ ! -d ".git" ]; then
    echo "🚀 Inicializando repositorio..."
    git init
fi

# Agregar archivos
echo "📦 Agregando archivos..."
git add .

# Commit
echo "💾 Creando commit..."
git commit -m "🎉 Initial commit - Odisea Makeup Store E-commerce

✨ Features: Responsive design, Shopping cart, User auth, Mobile navigation
🚀 Tech: PHP 8+, MySQL, Tailwind CSS, JavaScript ES6+
📱 Mobile optimized and PWA ready"

# Configurar remoto
echo "🔗 Configurando repositorio remoto..."
git remote add origin https://github.com/$GITHUB_USER/$REPO_NAME.git

# Push
echo "🚀 Subiendo a GitHub..."
git branch -M main
git push -u origin main

if [ $? -eq 0 ]; then
    echo ""
    echo "🎉 ¡ÉXITO! Proyecto subido a GitHub"
    echo "https://github.com/$GITHUB_USER/$REPO_NAME"
else
    echo "❌ Error al subir. Verifica que el repo existe en GitHub"
fi