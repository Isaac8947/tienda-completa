// Main JavaScript for Odisea Makeup Store - Clean Version
console.log('main.js loaded successfully - Clean Version')

document.addEventListener("DOMContentLoaded", () => {
  console.log('DOMContentLoaded fired in main.js')
  
  // Initialize only essential components with proper error handling
  safeInitialize('Mobile Menu', initializeMobileMenu)
  safeInitialize('Back to Top', initializeBackToTop)
  safeInitialize('Safe Header', initializeSafeHeader)
  
  // Initialize AOS safely
  initializeAOS()
  
  // Initialize Anime.js safely
  initializeAnime()
  
  // Initialize cart count
  if (typeof updateCartCount === 'function') {
    try {
      updateCartCount()
    } catch (e) {
      console.error('Error updating cart count:', e)
    }
  }
})

function safeInitialize(name, initFunction) {
  try {
    console.log(`Initializing ${name}...`)
    initFunction()
    console.log(`${name} initialized successfully`)
  } catch (e) {
    console.error(`Error initializing ${name}:`, e)
  }
}

// Safe Header Scroll Effect
function initializeSafeHeader() {
  let scrollTimeout
  
  function handleScroll() {
    if (scrollTimeout) return
    
    scrollTimeout = setTimeout(() => {
      try {
        const scrollY = window.scrollY || 0
        
        // Back to top button
        const backToTop = document.querySelector('#back-to-top')
        if (backToTop) {
          if (scrollY > 100) {
            backToTop.style.opacity = '1'
            backToTop.style.visibility = 'visible'
          } else {
            backToTop.style.opacity = '0'
            backToTop.style.visibility = 'hidden'
          }
        }
        
        // Simple header shadow
        const desktopHeader = document.querySelector('#desktop-header')
        const mobileHeader = document.querySelector('#mobile-header')
        
        const shadow = scrollY > 50 ? '0 4px 6px -1px rgba(0, 0, 0, 0.1)' : 'none'
        
        if (desktopHeader) desktopHeader.style.boxShadow = shadow
        if (mobileHeader) mobileHeader.style.boxShadow = shadow
        
      } catch (error) {
        console.error('Error in scroll handler:', error)
      }
      
      scrollTimeout = null
    }, 16)
  }
  
  window.addEventListener('scroll', handleScroll, { passive: true })
}

// Safe Mobile Menu
function initializeMobileMenu() {
  const mobileMenuBtn = document.querySelector("#mobile-menu-btn")
  const mobileMenuOverlay = document.querySelector("#mobile-menu-overlay")
  const mobileMenuDrawer = document.querySelector("#mobile-menu-drawer")
  const mobileMenuClose = document.querySelector("#mobile-menu-close")
  const mobileMenuBackdrop = document.querySelector("#mobile-menu-backdrop")

  function openMenu() {
    try {
      if (mobileMenuOverlay) mobileMenuOverlay.style.display = 'block'
      if (mobileMenuDrawer) mobileMenuDrawer.style.transform = 'translateX(0)'
      if (document.body) document.body.style.overflow = 'hidden'
    } catch (e) {
      console.error('Error opening menu:', e)
    }
  }

  function closeMenu() {
    try {
      if (mobileMenuDrawer) mobileMenuDrawer.style.transform = 'translateX(-100%)'
      setTimeout(() => {
        if (mobileMenuOverlay) mobileMenuOverlay.style.display = 'none'
        if (document.body) document.body.style.overflow = ''
      }, 300)
    } catch (e) {
      console.error('Error closing menu:', e)
    }
  }

  if (mobileMenuBtn) mobileMenuBtn.addEventListener("click", openMenu)
  if (mobileMenuClose) mobileMenuClose.addEventListener("click", closeMenu)
  if (mobileMenuBackdrop) mobileMenuBackdrop.addEventListener("click", closeMenu)
}

// Safe Back to Top
function initializeBackToTop() {
  const backToTopBtn = document.querySelector("#back-to-top")
  
  if (backToTopBtn) {
    backToTopBtn.addEventListener("click", () => {
      try {
        window.scrollTo({ top: 0, behavior: "smooth" })
      } catch (e) {
        window.scrollTo(0, 0)
      }
    })
  }
}

// Safe AOS initialization
function initializeAOS() {
  if (typeof AOS !== "undefined") {
    try {
      AOS.init({
        duration: 1000,
        easing: "ease-out-cubic",
        once: true,
        offset: 100,
      })
    } catch (e) {
      console.error('Error initializing AOS:', e)
    }
  }
}

// Safe Anime.js initialization
function initializeAnime() {
  if (typeof anime !== "undefined") {
    try {
      anime({
        targets: '#desktop-logo-odisea, #mobile-logo-odisea',
        translateY: [-20, 0],
        opacity: [0, 1],
        easing: 'easeOutExpo',
        duration: 1200,
        delay: 500
      })

      anime({
        targets: '#desktop-logo-makeup, #mobile-logo-makeup',
        translateY: [20, 0],
        opacity: [0, 1],
        easing: 'easeOutExpo',
        duration: 1200,
        delay: 700
      })
    } catch (e) {
      console.error('Error initializing anime.js:', e)
    }
  }
}

// Global functions for HTML
window.updateCartQuantity = function(id, change) {
  fetch('cart-update.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: `id=${id}&change=${change}`
  })
  .then(response => response.json())
  .then(data => { if (data.success) location.reload() })
  .catch(error => console.error('Error:', error))
}

window.removeFromCart = function(id) {
  fetch('cart-remove.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: `id=${id}`
  })
  .then(response => response.json())
  .then(data => { if (data.success) location.reload() })
  .catch(error => console.error('Error:', error))
}

window.addToCart = function(productId, quantity = 1) {
  const button = event.target.closest('button')
  const originalText = button.innerHTML
  button.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Agregando...'
  button.disabled = true
  
  fetch('cart-add.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: `product_id=${productId}&quantity=${quantity}`
  })
  .then(response => response.json())
  .then(data => {
    if (data.success) {
      showNotification('Producto agregado al carrito ✨', 'success')
      if (typeof updateCartCount === 'function') updateCartCount()
      button.innerHTML = '<i class="fas fa-check mr-2"></i>¡Agregado!'
      setTimeout(() => {
        button.innerHTML = originalText
        button.disabled = false
      }, 2000)
    } else {
      showNotification(data.message || 'Error al agregar producto', 'error')
      button.innerHTML = originalText
      button.disabled = false
    }
  })
  .catch(error => {
    console.error('Error:', error)
    showNotification('Error al agregar producto', 'error')
    button.innerHTML = originalText
    button.disabled = false
  })
}

window.toggleWishlist = function(productId) {
  const button = event.target.closest('button')
  const icon = button.querySelector('i')

  fetch('wishlist-toggle.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: `product_id=${productId}`
  })
  .then(response => response.json())
  .then(data => {
    if (data.success) {
      if (data.action === 'added') {
        icon.style.color = '#e11d48'
        showNotification('Agregado a favoritos ❤️', 'success')
      } else {
        icon.style.color = ''
        showNotification('Removido de favoritos', 'info')
      }
    } else {
      showNotification(data.message || 'Error al procesar favoritos', 'error')
    }
  })
  .catch(error => {
    console.error('Error:', error)
    showNotification('Error al procesar favoritos', 'error')
  })
}

window.quickView = function(productId) {
  window.location.href = `product.php?id=${productId}`
}

window.showNotification = function(message, type = 'info') {
  const notification = document.createElement('div')
  const colors = {
    success: 'from-green-500 to-emerald-500',
    error: 'from-red-500 to-rose-500',
    warning: 'from-yellow-500 to-orange-500',
    info: 'from-blue-500 to-indigo-500'
  }

  notification.className = `fixed top-8 right-8 z-50 p-4 rounded-2xl shadow-2xl text-white max-w-sm transition-all duration-500 transform translate-x-full bg-gradient-to-r ${colors[type] || colors.info}`
  notification.innerHTML = `
    <div class="flex items-center">
      <span class="mr-3">${type === 'success' ? '✓' : type === 'error' ? '✗' : 'ℹ'}</span>
      <span class="font-medium text-sm">${message}</span>
      <button onclick="this.parentElement.parentElement.remove()" class="ml-4 text-white hover:text-gray-200">×</button>
    </div>
  `

  document.body.appendChild(notification)
  setTimeout(() => notification.style.transform = 'translateX(0)', 100)
  setTimeout(() => {
    if (notification.parentElement) {
      notification.style.transform = 'translateX(100%)'
      setTimeout(() => notification.remove(), 500)
    }
  }, 4000)
}

window.updateCartCount = function() {
  fetch('cart-count.php')
  .then(response => response.json())
  .then(data => {
    const cartBadges = document.querySelectorAll('#cart-count, #mobile-cart-count')
    cartBadges.forEach(badge => {
      if (badge) badge.textContent = data.count || 0
    })
  })
  .catch(error => console.error('Error updating cart count:', error))
}

// Error handling
window.addEventListener("error", (e) => {
  console.error("JavaScript Error:", e.error)
})

window.addEventListener("unhandledrejection", (e) => {
  console.error("Unhandled Promise Rejection:", e.reason)
})
