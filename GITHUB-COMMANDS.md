# 📝 COMANDOS MANUALES PARA SUBIR A GITHUB
# ========================================

# 1. CONFIGURAR GIT (primera vez)
git config --global user.name "tu-username"
git config --global user.email "tu-email@gmail.com"
git config --global init.defaultBranch main

# 2. INICIALIZAR REPOSITORIO LOCAL
git init

# 3. AGREGAR ARCHIVOS
git add .

# 4. COMMIT INICIAL
git commit -m "🎉 Initial commit - Odisea Makeup Store E-commerce

✨ Features implemented:
- Responsive design with Tailwind CSS
- Product catalog with advanced filters  
- Shopping cart with AJAX functionality
- User authentication and profiles
- Mobile-first navigation
- Real-time search and filters
- CSRF protection and security
- Wishlist and reviews system

🚀 Tech Stack: PHP 8+, MySQL, Tailwind CSS, JavaScript ES6+"

# 5. CONFIGURAR REPOSITORIO REMOTO
git remote add origin https://github.com/TU-USERNAME/NOMBRE-REPO.git

# 6. RENOMBRAR BRANCH Y SUBIR
git branch -M main
git push -u origin main

# ============================================
# ALTERNATIVA: USANDO GITHUB CLI (si tienes gh instalado)
# ============================================

# Crear repo directamente desde línea de comandos
gh repo create odisea-makeup-store --public --description "E-commerce moderno en PHP con Tailwind CSS"

# Subir código
git push -u origin main

# ============================================
# NOTAS IMPORTANTES:
# ============================================

# ⚠️  ANTES de hacer push, asegúrate de:
# 1. Crear el repositorio en GitHub (si no usas gh cli)
# 2. Verificar que .gitignore excluye archivos sensibles
# 3. Revisar que config/database.php no tenga credenciales reales
# 4. Confirmar que todos los archivos necesarios están incluidos

# 🔗 Para crear repositorio manual en GitHub:
# https://github.com/new

# 💡 Si necesitas token de acceso personal:
# https://github.com/settings/tokens

# 📖 Para configurar GitHub Pages (demo en vivo):
# Settings > Pages > Deploy from branch > main