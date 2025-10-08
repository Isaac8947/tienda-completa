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
  
  // Initialize news banner
  safeInitialize('News Banner', initializeNewsBanner)
  
  // Initialize hero slider
  safeInitialize('Hero Slider', initializeHeroSlider)
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
  
  // Obtener el CSRF token
  const csrfToken = document.querySelector('meta[name="cart-csrf-token"]')?.getAttribute('content') || 
                   document.querySelector('input[name="csrf_token"]')?.value || '';
  
  fetch('cart-add.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: `product_id=${productId}&quantity=${quantity}&csrf_token=${encodeURIComponent(csrfToken)}`
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
  console.log('quickView function called with productId:', productId);
  
  // Validate productId
  if (!productId || isNaN(productId)) {
    console.error('Invalid productId:', productId);
    showNotification('Error: ID de producto inválido', 'error');
    return;
  }
  
  console.log('Redirecting to product page...');
  const targetUrl = `product.php?id=${productId}`;
  console.log('Target URL:', targetUrl);
  
  try {
    window.location.href = targetUrl;
  } catch (error) {
    console.error('Error redirecting:', error);
    showNotification('Error al abrir el producto', 'error');
  }
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

// News Banner Functions
function initializeNewsBanner() {
  // Auto-show banner after page load
  setTimeout(() => {
    showNewsBanner()
  }, 2000)
}

function initializeHeroSlider() {
  const slides = document.querySelectorAll('.hero-slide')
  totalSlides = slides.length
  
  if (totalSlides > 0) {
    currentSlide = 0
    updateSlider()
    
    // Auto-advance slides every 5 seconds
    setInterval(() => {
      nextSlide()
    }, 5000)
  }
}

window.showNewsBanner = function() {
  const banner = document.getElementById('newsFloatingBanner')
  if (!banner) return
  
  const newsId = banner.dataset.newsId
  if (!newsId) return
  
  const isDebugMode = true // Change to false in production
  const storageKey = `newsBanner_${newsId}_dismissed`
  const isDismissed = localStorage.getItem(storageKey)

  console.log('🔍 Debug Banner:', {
    banner: 'Element found',
    debugMode: isDebugMode,
    storageKey: storageKey,
    isDismissed: isDismissed,
    willShow: isDebugMode || !isDismissed
  })

  if (isDebugMode || !isDismissed) {
    console.log('✅ Showing banner...')
    try {
      banner.style.right = '1rem'
      banner.style.opacity = '1'
      banner.style.visibility = 'visible'
      banner.style.transform = 'translateX(0)'
    } catch (e) {
      console.error('Error showing banner:', e)
    }

    // Auto-hide after 10 seconds if not closed manually
    setTimeout(() => {
      if (banner.style.opacity === '1') {
        closeNewsBanner()
      }
    }, 10000)
  }
}

window.closeNewsBanner = function() {
  const banner = document.getElementById('newsFloatingBanner')
  if (!banner) return
  
  const newsId = banner.dataset.newsId
  if (!newsId) return
  
  const storageKey = `newsBanner_${newsId}_dismissed`

  console.log('❌ Closing banner:', {
    banner: 'Element found',
    storageKey: storageKey
  })

  try {
    banner.style.right = '-400px'
    banner.style.opacity = '0'
    banner.style.transform = 'translateX(100%)'
    
    setTimeout(() => {
      banner.style.visibility = 'hidden'
    }, 300)

    // Remember that this specific news was closed
    localStorage.setItem(storageKey, 'true')
    console.log('💾 Saved in localStorage:', storageKey)
  } catch (e) {
    console.error('Error closing banner:', e)
  }
}

// Debug function to clear localStorage (use in console)
window.clearNewsBannerStorage = function() {
  const keys = Object.keys(localStorage).filter(key => key.startsWith('newsBanner_'))
  keys.forEach(key => {
    localStorage.removeItem(key)
    console.log('🗑️ Deleted:', key)
  })
  console.log('✅ localStorage cleared. Reload page to see banner.')
}

// Hero Banner Slider Functions
let currentSlide = 0
let totalSlides = 0

window.previousSlide = function() {
  const slides = document.querySelectorAll('.hero-slide')
  if (slides.length === 0) return
  
  currentSlide = currentSlide === 0 ? slides.length - 1 : currentSlide - 1
  updateSlider()
}

window.nextSlide = function() {
  const slides = document.querySelectorAll('.hero-slide')
  if (slides.length === 0) return
  
  currentSlide = (currentSlide + 1) % slides.length
  updateSlider()
}

window.goToSlide = function(index) {
  const slides = document.querySelectorAll('.hero-slide')
  if (slides.length === 0 || index < 0 || index >= slides.length) return
  
  currentSlide = index
  updateSlider()
}

function updateSlider() {
  const slides = document.querySelectorAll('.hero-slide')
  const indicators = document.querySelectorAll('.slide-indicator')
  
  slides.forEach((slide, index) => {
    if (index === currentSlide) {
      slide.style.opacity = '1'
      slide.style.zIndex = '10'
    } else {
      slide.style.opacity = '0'
      slide.style.zIndex = '5'
    }
  })
  
  indicators.forEach((indicator, index) => {
    if (index === currentSlide) {
      indicator.style.backgroundColor = 'white'
    } else {
      indicator.style.backgroundColor = 'rgba(255, 255, 255, 0.5)'
    }
  })
}

// Lightbox Functions
window.closeLightbox = function() {
  const lightbox = document.getElementById('imageLightbox')
  if (lightbox) {
    lightbox.style.opacity = '0'
    setTimeout(() => {
      lightbox.style.display = 'none'
    }, 300)
  }
}

window.openLightbox = function(imageSrc, title, description) {
  const lightbox = document.getElementById('imageLightbox')
  const lightboxImage = document.getElementById('lightboxImage')
  const lightboxTitle = document.getElementById('lightboxTitle')
  const lightboxDescription = document.getElementById('lightboxDescription')
  
  if (lightbox && lightboxImage) {
    lightboxImage.src = imageSrc
    if (lightboxTitle) lightboxTitle.textContent = title || ''
    if (lightboxDescription) lightboxDescription.textContent = description || ''
    
    lightbox.style.display = 'flex'
    setTimeout(() => {
      lightbox.style.opacity = '1'
    }, 10)
  }
}

// Android Responsiveness Improvements
function initializeAndroidOptimizations() {
  // Fix viewport height issues on Android
  function setViewportHeight() {
    const vh = window.innerHeight * 0.01;
    document.documentElement.style.setProperty('--vh', `${vh}px`);
  }

  // Set initial viewport height
  setViewportHeight();

  // Update on resize and orientation change
  window.addEventListener('resize', setViewportHeight);
  window.addEventListener('orientationchange', () => {
    setTimeout(setViewportHeight, 100);
  });

  // Improve touch performance
  document.addEventListener('touchstart', function() {}, {passive: true});
  document.addEventListener('touchmove', function() {}, {passive: true});

  // Fix Android keyboard issues
  function handleAndroidKeyboard() {
    const isAndroid = /Android/i.test(navigator.userAgent);
    if (!isAndroid) return;

    let initialHeight = window.innerHeight;
    
    window.addEventListener('resize', function() {
      const currentHeight = window.innerHeight;
      const heightDifference = initialHeight - currentHeight;
      
      // If height decreased significantly, keyboard is probably open
      if (heightDifference > 150) {
        document.body.classList.add('keyboard-open');
      } else {
        document.body.classList.remove('keyboard-open');
      }
    });
  }

  handleAndroidKeyboard();

  // Improve scroll performance on Android
  function improveScrolling() {
    const isAndroid = /Android/i.test(navigator.userAgent);
    if (!isAndroid) return;

    // Add momentum scrolling
    document.body.style.webkitOverflowScrolling = 'touch';
    
    // Prevent rubber band scrolling issues
    let isScrolling = false;
    
    document.addEventListener('touchstart', function() {
      isScrolling = true;
    }, {passive: true});
    
    document.addEventListener('touchend', function() {
      isScrolling = false;
    }, {passive: true});
  }

  improveScrolling();

  // Fix Android Chrome address bar issues
  function handleAddressBar() {
    const isAndroid = /Android/i.test(navigator.userAgent);
    const isChrome = /Chrome/i.test(navigator.userAgent);
    
    if (!isAndroid || !isChrome) return;

    let timeout;
    
    function adjustForAddressBar() {
      clearTimeout(timeout);
      timeout = setTimeout(() => {
        const vh = window.innerHeight * 0.01;
        document.documentElement.style.setProperty('--vh', `${vh}px`);
      }, 100);
    }

    window.addEventListener('scroll', adjustForAddressBar, {passive: true});
  }

  handleAddressBar();

  // Improve form inputs on Android
  function improveFormInputs() {
    const inputs = document.querySelectorAll('input, textarea, select');
    
    inputs.forEach(input => {
      // Prevent zoom on focus for Android
      input.addEventListener('focus', function() {
        if (window.innerWidth < 768) {
          const viewport = document.querySelector('meta[name="viewport"]');
          if (viewport) {
            viewport.setAttribute('content', 
              'width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no'
            );
          }
        }
      });
      
      input.addEventListener('blur', function() {
        if (window.innerWidth < 768) {
          const viewport = document.querySelector('meta[name="viewport"]');
          if (viewport) {
            viewport.setAttribute('content', 
              'width=device-width, initial-scale=1.0, maximum-scale=5.0, user-scalable=yes, viewport-fit=cover'
            );
          }
        }
      });
    });
  }

  improveFormInputs();

  console.log('Android optimizations initialized');
}

// Initialize Android optimizations
document.addEventListener('DOMContentLoaded', function() {
  safeInitialize('Android Optimizations', initializeAndroidOptimizations);
});

// CSS classes for keyboard state
const style = document.createElement('style');
style.textContent = `
  .keyboard-open {
    height: 100vh !important;
    overflow: hidden;
  }
  
  .keyboard-open .fixed-bottom {
    position: static !important;
  }
  
  .keyboard-open .hero-section {
    min-height: 50vh !important;
  }
  
  @media (max-width: 767px) {
    .keyboard-open .container {
      padding-bottom: 0 !important;
    }
  }
`;
document.head.appendChild(style);

// Cart Sidebar Functions
window.openCartSidebar = function() {
  // Option 1: Redirect to cart page (simple solution)
  window.location.href = 'carrito.php';
  
  // Option 2: Open cart in modal/sidebar (more advanced)
  // Uncomment the code below if you want a sidebar instead of redirect
  /*
  createCartSidebar();
  */
}

// Function to create and show cart sidebar (optional advanced feature)
function createCartSidebar() {
  // Check if sidebar already exists
  let sidebar = document.getElementById('cart-sidebar');
  
  if (!sidebar) {
    // Create sidebar HTML
    sidebar = document.createElement('div');
    sidebar.id = 'cart-sidebar';
    sidebar.className = 'fixed top-0 right-0 h-full w-80 bg-white shadow-2xl transform translate-x-full transition-transform duration-300 z-50';
    
    sidebar.innerHTML = `
      <div class="h-full flex flex-col">
        <!-- Header -->
        <div class="p-4 border-b border-gray-200 flex items-center justify-between">
          <h3 class="text-lg font-semibold">Mi Carrito</h3>
          <button onclick="closeCartSidebar()" class="text-gray-500 hover:text-gray-700">
            <i class="fas fa-times text-xl"></i>
          </button>
        </div>
        
        <!-- Loading state -->
        <div id="cart-sidebar-content" class="flex-1 flex items-center justify-center">
          <div class="text-center">
            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary-500 mx-auto mb-2"></div>
            <p class="text-gray-500">Cargando carrito...</p>
          </div>
        </div>
        
        <!-- Footer -->
        <div class="p-4 border-t border-gray-200">
          <a href="carrito.php" class="w-full bg-primary-500 text-white py-3 rounded-lg block text-center font-medium hover:bg-primary-600 transition">
            Ver Carrito Completo
          </a>
        </div>
      </div>
    `;
    
    document.body.appendChild(sidebar);
    
    // Create overlay
    const overlay = document.createElement('div');
    overlay.id = 'cart-sidebar-overlay';
    overlay.className = 'fixed inset-0 bg-black bg-opacity-50 z-40 opacity-0 transition-opacity duration-300';
    overlay.onclick = closeCartSidebar;
    document.body.appendChild(overlay);
  }
  
  // Show sidebar
  setTimeout(() => {
    sidebar.classList.remove('translate-x-full');
    document.getElementById('cart-sidebar-overlay').classList.remove('opacity-0');
    document.body.classList.add('overflow-hidden');
  }, 10);
  
  // Load cart content
  loadCartSidebarContent();
}

window.closeCartSidebar = function() {
  const sidebar = document.getElementById('cart-sidebar');
  const overlay = document.getElementById('cart-sidebar-overlay');
  
  if (sidebar && overlay) {
    sidebar.classList.add('translate-x-full');
    overlay.classList.add('opacity-0');
    document.body.classList.remove('overflow-hidden');
  }
}

function loadCartSidebarContent() {
  const content = document.getElementById('cart-sidebar-content');
  if (!content) return;
  
  fetch('cart-sidebar-content.php')
    .then(response => response.text())
    .then(html => {
      content.innerHTML = html;
    })
    .catch(error => {
      console.error('Error loading cart content:', error);
      content.innerHTML = `
        <div class="text-center p-4">
          <p class="text-gray-500">Error al cargar el carrito</p>
          <button onclick="loadCartSidebarContent()" class="mt-2 text-primary-500 hover:text-primary-600">
            Intentar de nuevo
          </button>
        </div>
      `;
    });
}

// Toggle mobile search function (if not already defined)
if (typeof window.toggleMobileSearch === 'undefined') {
  window.toggleMobileSearch = function() {
    const searchContainer = document.getElementById('mobile-search-container');
    if (searchContainer) {
      searchContainer.classList.toggle('hidden');
      const searchInput = searchContainer.querySelector('input');
      if (searchInput && !searchContainer.classList.contains('hidden')) {
        setTimeout(() => searchInput.focus(), 100);
      }
    }
  }
}

console.log('Cart sidebar functions loaded successfully');
