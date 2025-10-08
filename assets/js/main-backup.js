// Main JavaScript for Odisea Makeup Store
console.log('main.js loaded successfully')

document.addEventListener("DOMContentLoaded", () => {
  console.log('DOMContentLoaded fired in main.js')
  
  // Initialize all components
  try {
    initializeHeader()
  } catch (e) {
    console.error('Error initializing header:', e)
  }
  
  try {
    initializeMobileMenu()
  } catch (e) {
    console.error('Error initializing mobile menu:', e)
  }
  
  try {
    initializeCart()
  } catch (e) {
    console.error('Error initializing cart:', e)
  }
  
  try {
    initializeProductSliders()
  } catch (e) {
    console.error('Error initializing product sliders:', e)
  }
  
  try {
    initializeBackToTop()
  } catch (e) {
    console.error('Error initializing back to top:', e)
  }
  
  try {
    initializeTooltips()
  } catch (e) {
    console.error('Error initializing tooltips:', e)
  }
  
  try {
    initializeSearch()
  } catch (e) {
    console.error('Error initializing search:', e)
  }
  
  try {
    initializeNewsletterForm()
  } catch (e) {
    console.error('Error initializing newsletter form:', e)
  }
  
  try {
    initializeProductQuickView()
  } catch (e) {
    console.error('Error initializing product quick view:', e)
  }
  
  try {
    initializeWishlist()
  } catch (e) {
    console.error('Error initializing wishlist:', e)
  }
  
  try {
    initializeLazyLoading()
  } catch (e) {
    console.error('Error initializing lazy loading:', e)
  }
  
  // Initialize cart display on page load
  if (typeof updateCartCount === 'function') {
    try {
      updateCartCount()
    } catch (e) {
      console.error('Error updating cart count:', e)
    }
  }

  // Inicializar AOS (Animate On Scroll)
  const AOS = window.AOS // Declare the AOS variable
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

  // Anime.js for logo animation on page load
  if (typeof anime !== "undefined") {
    try {
      anime({
        targets: '#desktop-logo-odisea, #mobile-logo-odisea',
        translateY: [-20, 0],
        opacity: [0, 1],
        easing: 'easeOutExpo',
        duration: 1200,
        delay: 500
      });

      anime({
        targets: '#desktop-logo-makeup, #mobile-logo-makeup',
        translateY: [20, 0],
        opacity: [0, 1],
        easing: 'easeOutExpo',
        duration: 1200,
        delay: 700
      });
    } catch (e) {
      console.error('Error initializing anime.js:', e)
    }
  }
})

// Header Scroll Effect - New Safe Version
function initializeHeader() {
  console.log('Initializing safe header scroll effects...')
  
  // Simple, safe scroll handler
  function safeScrollHandler() {
    try {
      const scrollY = window.scrollY || window.pageYOffset || 0
      
      // Only handle back to top button for now
      const backToTopButton = document.querySelector('#back-to-top')
      
      if (backToTopButton) {
        if (scrollY > 100) {
          backToTopButton.classList.remove('opacity-0', 'invisible')
        } else {
          backToTopButton.classList.add('opacity-0', 'invisible')
        }
      }
      
      // Simple header shadow effect (safer approach)
      const desktopHeader = document.querySelector('#desktop-header')
      const mobileHeader = document.querySelector('#mobile-header')
      
      if (scrollY > 50) {
        if (desktopHeader) {
          desktopHeader.style.boxShadow = '0 4px 6px -1px rgba(0, 0, 0, 0.1)'
        }
        if (mobileHeader) {
          mobileHeader.style.boxShadow = '0 4px 6px -1px rgba(0, 0, 0, 0.1)'
        }
      } else {
        if (desktopHeader) {
          desktopHeader.style.boxShadow = 'none'
        }
        if (mobileHeader) {
          mobileHeader.style.boxShadow = 'none'
        }
      }
      
    } catch (error) {
      console.error('Error in scroll handler:', error)
      // Remove the event listener if there's an error
      window.removeEventListener('scroll', throttledScrollHandler)
    }
  }
  
  // Throttle scroll events
  let scrollTimeout
  function throttledScrollHandler() {
    if (scrollTimeout) {
      return
    }
    scrollTimeout = setTimeout(() => {
      safeScrollHandler()
      scrollTimeout = null
    }, 16) // ~60fps
  }
  
  // Add the scroll listener
  window.addEventListener('scroll', throttledScrollHandler, { passive: true })
}

// Mobile Menu
function initializeMobileMenu() {
  const mobileMenuBtn = document.getElementById("mobile-menu-btn")
  const mobileMenuOverlay = document.getElementById("mobile-menu-overlay")
  const mobileMenuDrawer = document.getElementById("mobile-menu-drawer")
  const mobileMenuClose = document.getElementById("mobile-menu-close")
  const mobileMenuBackdrop = document.getElementById("mobile-menu-backdrop")

  if (mobileMenuBtn && mobileMenuOverlay && mobileMenuDrawer) {
    mobileMenuBtn.addEventListener("click", () => {
      const overlay = document.getElementById("mobile-menu-overlay")
      const drawer = document.getElementById("mobile-menu-drawer")
      
      if (overlay && overlay.classList) {
        overlay.classList.remove("hidden")
      }
      
      setTimeout(() => {
        if (drawer && drawer.classList) {
          drawer.classList.add("open")
        }
      }, 10)
      
      if (document.body && document.body.style) {
        document.body.style.overflow = 'hidden'
      }
    })
  }

  if (mobileMenuClose && mobileMenuOverlay && mobileMenuDrawer) {
    mobileMenuClose.addEventListener("click", () => {
      const drawer = document.getElementById("mobile-menu-drawer")
      const overlay = document.getElementById("mobile-menu-overlay")
      
      if (drawer && drawer.classList) {
        drawer.classList.remove("open")
      }
      
      setTimeout(() => {
        if (overlay && overlay.classList) {
          overlay.classList.add("hidden")
        }
        if (document.body && document.body.style) {
          document.body.style.overflow = ''
        }
      }, 300)
    })
  }

  if (mobileMenuBackdrop && mobileMenuOverlay && mobileMenuDrawer) {
    mobileMenuBackdrop.addEventListener("click", () => {
      const drawer = document.getElementById("mobile-menu-drawer")
      const overlay = document.getElementById("mobile-menu-overlay")
      
      if (drawer && drawer.classList) {
        drawer.classList.remove("open")
      }
      
      setTimeout(() => {
        if (overlay && overlay.classList) {
          overlay.classList.add("hidden")
        }
        if (document.body && document.body.style) {
          document.body.style.overflow = ''
        }
      }, 300)
    })
  }
}

// Shopping Cart
function initializeCart() {
  const cartToggle = document.getElementById("cart-toggle")
  const cartSidebar = document.getElementById("cart-sidebar")
  const cartPanel = document.getElementById("cart-panel")
  const cartOverlay = document.getElementById("cart-overlay")
  const closeCart = document.getElementById("close-cart")
  const continueShoppingBtns = document.querySelectorAll("#continue-shopping, #continue-shopping-footer")

  // Open cart
  if (cartToggle) {
    cartToggle.addEventListener("click", () => {
      cartSidebar.classList.remove("hidden")
      setTimeout(() => {
        cartPanel.classList.remove("translate-x-full")
      }, 10)
    })
  }

  // Close cart
  function closeCartSidebar() {
    cartPanel.classList.add("translate-x-full")
    setTimeout(() => {
      cartSidebar.classList.add("hidden")
    }, 300)
  }

  if (closeCart) {
    closeCart.addEventListener("click", closeCartSidebar)
  }

  if (cartOverlay) {
    cartOverlay.addEventListener("click", closeCartSidebar)
  }

  continueShoppingBtns.forEach((btn) => {
    btn.addEventListener("click", closeCartSidebar)
  })

  // Cart quantity buttons
  document.querySelectorAll(".cart-increase").forEach((button) => {
    button.addEventListener("click", function () {
      const id = this.getAttribute("data-id")
      updateCartQuantity(id, 1)
    })
  })

  document.querySelectorAll(".cart-decrease").forEach((button) => {
    button.addEventListener("click", function () {
      const id = this.getAttribute("data-id")
      updateCartQuantity(id, -1)
    })
  })

  document.querySelectorAll(".cart-remove").forEach((button) => {
    button.addEventListener("click", function () {
      const id = this.getAttribute("data-id")
      removeFromCart(id)
    })
  })

  // Add to cart buttons
  document.querySelectorAll(".add-to-cart").forEach((button) => {
    button.addEventListener("click", function (e) {
      e.preventDefault()
      const id = this.getAttribute("data-id")
      const quantity = 1
      addToCart(id, quantity)
    })
  })

  // Product quantity inputs
  const quantityInputs = document.querySelectorAll(".quantity-input")
  if (quantityInputs.length > 0) {
    quantityInputs.forEach((input) => {
      const decreaseBtn = input.parentElement.querySelector(".quantity-decrease")
      const increaseBtn = input.parentElement.querySelector(".quantity-increase")

      decreaseBtn.addEventListener("click", () => {
        const value = Number.parseInt(input.value)
        if (value > 1) {
          input.value = value - 1
        }
      })

      increaseBtn.addEventListener("click", () => {
        const value = Number.parseInt(input.value)
        input.value = value + 1
      })
    })
  }
}

// Cart functions
function updateCartQuantity(id, change) {
  fetch("cart-update.php", {
    method: "POST",
    headers: {
      "Content-Type": "application/x-www-form-urlencoded",
    },
    body: `id=${id}&change=${change}`,
  })
    .then((response) => response.json())
    .then((data) => {
      if (data.success) {
        // Update cart contents dynamically
        updateCartDisplay()
      }
    })
    .catch((error) => {
      console.error("Error:", error)
    })
}

function removeFromCart(id) {
  fetch("cart-remove.php", {
    method: "POST",
    headers: {
      "Content-Type": "application/x-www-form-urlencoded",
    },
    body: `id=${id}`,
  })
    .then((response) => response.json())
    .then((data) => {
      if (data.success) {
        // Update cart contents dynamically
        updateCartDisplay()
      }
    })
    .catch((error) => {
      console.error("Error:", error)
    })
}

function updateCartDisplay() {
  fetch("cart-content.php")
    .then((response) => {
      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`)
      }
      return response.json()
    })
    .then((data) => {
      if (data.success) {
        // Update cart count in header
        const cartCountEl = document.getElementById("cart-count")
        const cartHeaderCountEl = document.getElementById("cart-header-count")
        if (cartCountEl) {
          cartCountEl.textContent = data.itemCount
        }
        if (cartHeaderCountEl) {
          cartHeaderCountEl.textContent = `${data.itemCount} ${data.itemCount === 1 ? 'producto' : 'productos'}`
        }
        
        // Update cart items container
        const cartItemsEl = document.getElementById("cart-items")
        if (cartItemsEl) {
          if (data.isEmpty) {
            // Show empty cart
            cartItemsEl.innerHTML = `
              <div class="flex flex-col items-center justify-center py-12 px-4" id="empty-cart">
                <div class="w-24 h-24 bg-gray-100 rounded-full flex items-center justify-center mb-6">
                  <i class="fas fa-shopping-bag text-4xl text-gray-400"></i>
                </div>
                <h3 class="text-xl font-semibold text-gray-900 mb-3">Tu carrito está vacío</h3>
                <p class="text-gray-600 text-center mb-8 max-w-sm">Descubre nuestros increíbles productos de maquillaje y agrega algunos a tu carrito</p>
                <button class="bg-gradient-to-r from-primary-500 to-primary-600 hover:from-primary-600 hover:to-primary-700 text-white px-8 py-3 rounded-lg font-semibold transition-all duration-300 transform hover:scale-105 hover:shadow-lg" id="continue-shopping">
                  <i class="fas fa-search mr-2"></i>
                  Explorar Productos
                </button>
              </div>
            `
          } else {
            // Show cart items
            let itemsHTML = '<div class="space-y-3" id="cart-items-list">'
            data.items.forEach((item, index) => {
              itemsHTML += `
                <div class="bg-white border border-gray-200 rounded-lg p-3 shadow-sm">
                  <div class="flex items-start space-x-3">
                    <!-- Product Image -->
                    <div class="flex-shrink-0">
                      <img src="${item.image || 'assets/images/placeholder-product.svg'}" 
                           alt="${item.name}" 
                           class="w-16 h-16 sm:w-20 sm:h-20 object-cover rounded-lg border border-gray-200">
                    </div>
                    
                    <!-- Product Info -->
                    <div class="flex-1 min-w-0">
                      <h4 class="font-semibold text-gray-900 text-sm sm:text-base truncate">${item.name}</h4>
                      <p class="text-primary-600 font-medium text-sm">$${parseFloat(item.price).toLocaleString('es-CO')}</p>
                      
                      <!-- Quantity Controls -->
                      <div class="flex items-center justify-between mt-2">
                        <div class="flex items-center space-x-2">
                          <button onclick="updateCartQuantity(${item.id}, -1)" 
                                  class="w-7 h-7 rounded-full bg-gray-100 hover:bg-gray-200 flex items-center justify-center transition-colors border border-gray-300">
                            <i class="fas fa-minus text-xs text-gray-600"></i>
                          </button>
                          <span class="w-8 text-center font-semibold text-sm">${item.quantity}</span>
                          <button onclick="updateCartQuantity(${item.id}, 1)" 
                                  class="w-7 h-7 rounded-full bg-gray-100 hover:bg-gray-200 flex items-center justify-center transition-colors border border-gray-300">
                            <i class="fas fa-plus text-xs text-gray-600"></i>
                          </button>
                        </div>
                        
                        <!-- Total Price & Remove Button -->
                        <div class="flex items-center space-x-2">
                          <span class="font-bold text-gray-900 text-sm">$${(parseFloat(item.price) * parseInt(item.quantity)).toLocaleString('es-CO')}</span>
                          <button onclick="removeFromCart(${item.id})" 
                                  class="w-7 h-7 rounded-full bg-red-50 hover:bg-red-100 flex items-center justify-center transition-colors border border-red-200">
                            <i class="fas fa-trash text-xs text-red-500"></i>
                          </button>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              `
            })
            itemsHTML += '</div>'
            
            // Add cart summary
            itemsHTML += `
              <div class="border-t border-gray-200 pt-4 mt-4">
                <!-- Cart Summary -->
                <div class="bg-gray-50 rounded-lg p-4 space-y-3">
                  <h3 class="font-semibold text-gray-900 text-base">Resumen del pedido</h3>
                  <div class="space-y-2 text-sm">
                    <div class="flex justify-between text-gray-600">
                      <span>Subtotal (${data.itemCount} ${data.itemCount === 1 ? 'producto' : 'productos'})</span>
                      <span>$${parseFloat(data.subtotal).toLocaleString('es-CO')}</span>
                    </div>
                    <div class="flex justify-between text-gray-600">
                      <span>IVA (19%)</span>
                      <span>$${parseFloat(data.tax).toLocaleString('es-CO')}</span>
                    </div>
                    <div class="border-t border-gray-300 pt-2">
                      <div class="flex justify-between text-base font-bold text-gray-900">
                        <span>Total</span>
                        <span class="text-primary-600">$${parseFloat(data.total).toLocaleString('es-CO')}</span>
                      </div>
                    </div>
                  </div>
                </div>
                
                <!-- Action Buttons -->
                <div class="mt-4 space-y-3">
                  <button class="w-full bg-gradient-to-r from-primary-500 to-primary-600 hover:from-primary-600 hover:to-primary-700 text-white py-3 px-4 rounded-lg font-semibold transition-all duration-300 transform hover:scale-105 hover:shadow-lg">
                    <i class="fas fa-shopping-cart mr-2"></i>
                    Finalizar Compra
                  </button>
                  <button class="w-full border-2 border-gray-300 text-gray-700 py-2.5 px-4 rounded-lg font-medium hover:bg-gray-50 hover:border-gray-400 transition-all duration-300" id="continue-shopping-footer">
                    <i class="fas fa-arrow-left mr-2"></i>
                    Continuar Comprando
                  </button>
                </div>
              </div>
            `
            
            cartItemsEl.innerHTML = itemsHTML
          }
          
          // Re-attach continue shopping event listeners
          const continueShoppingBtns = document.querySelectorAll("#continue-shopping, #continue-shopping-footer")
          continueShoppingBtns.forEach(btn => {
            btn.addEventListener("click", () => {
              const cartPanel = document.getElementById("cart-panel")
              const cartSidebar = document.getElementById("cart-sidebar")
              if (cartPanel) cartPanel.classList.add("translate-x-full")
              setTimeout(() => {
                if (cartSidebar) cartSidebar.classList.add("hidden")
              }, 300)
            })
          })
        }
      } else {
        console.error("❌ Cart API returned error:", data)
      }
    })
    .catch((error) => {
      console.error("❌ Error updating cart display:", error)
    })
}

// Show notification function
function showNotification(message, type = 'info') {
  const notification = document.createElement('div');
  notification.className = `fixed top-4 right-4 z-50 p-4 rounded-lg shadow-lg text-white max-w-sm transition-all duration-300 ${
      type === 'success' ? 'bg-green-500' : 
      type === 'error' ? 'bg-red-500' : 
      type === 'warning' ? 'bg-yellow-500' : 'bg-blue-500'
  }`;
  notification.innerHTML = `
      <div class="flex items-center">
          <span class="mr-2">
              ${type === 'success' ? '✓' : type === 'error' ? '✗' : type === 'warning' ? '⚠' : 'ℹ'}
          </span>
          <span>${message}</span>
          <button onclick="this.parentElement.parentElement.remove()" class="ml-auto text-white hover:text-gray-200 text-lg">&times;</button>
      </div>
  `;
  document.body.appendChild(notification);
  
  setTimeout(() => {
      if (notification.parentElement) {
          notification.remove();
      }
  }, 3000);
}

function addToCart(id, quantity = 1) {
  fetch("cart-add.php", {
    method: "POST",
    headers: {
      "Content-Type": "application/x-www-form-urlencoded",
    },
    body: `id=${id}&quantity=${quantity}`,
  })
    .then((response) => response.json())
    .then((data) => {
      if (data.success) {
        // Update cart display dynamically with a small delay to ensure backend processing
        setTimeout(() => {
          updateCartDisplay()
        }, 100)

        // Show success notification
        showNotification("¡Producto agregado al carrito!", "success")

        // Open cart sidebar
        const cartSidebar = document.getElementById("cart-sidebar")
        const cartPanel = document.getElementById("cart-panel")
        if (cartSidebar && cartPanel) {
          cartSidebar.classList.remove("hidden")
          setTimeout(() => {
            cartPanel.classList.remove("translate-x-full")
          }, 10)
        }
      } else {
        showNotification(data.message || "Error al agregar al carrito. Inténtalo de nuevo.", "error")
      }
    })
    .catch((error) => {
      console.error("❌ Error in addToCart:", error)
      showNotification("Error al agregar al carrito. Inténtalo de nuevo.", "error")
    })
}

// Product Sliders
function initializeProductSliders() {
  const newArrivalsSlider = document.getElementById("new-arrivals-slider")
  const prevBtn = document.getElementById("prev-new")
  const nextBtn = document.getElementById("next-new")

  if (newArrivalsSlider && prevBtn && nextBtn) {
    prevBtn.addEventListener("click", () => {
      newArrivalsSlider.scrollBy({ left: -300, behavior: "smooth" })
    })

    nextBtn.addEventListener("click", () => {
      newArrivalsSlider.scrollBy({ left: 300, behavior: "smooth" })
    })
  }
}

// Back to Top Button
function initializeBackToTop() {
  const backToTopBtn = document.getElementById("back-to-top")

  if (backToTopBtn) {
    window.addEventListener("scroll", () => {
      if (window.pageYOffset > 300) {
        backToTopBtn.classList.remove("opacity-0", "invisible")
      } else {
        backToTopBtn.classList.add("opacity-0", "invisible")
      }
    })

    backToTopBtn.addEventListener("click", () => {
      window.scrollTo({
        top: 0,
        behavior: "smooth",
      })
    })
  }
}

// Tooltips
function initializeTooltips() {
  const tooltipElements = document.querySelectorAll("[data-tooltip]")

  tooltipElements.forEach((element) => {
    element.addEventListener("mouseenter", function () {
      showTooltip(this, this.dataset.tooltip)
    })

    element.addEventListener("mouseleave", () => {
      hideTooltip()
    })
  })
}

function showTooltip(element, text) {
  const tooltip = document.createElement("div")
  tooltip.className = "absolute z-50 px-3 py-2 text-sm text-white bg-gray-900 rounded-lg shadow-lg"
  tooltip.textContent = text
  tooltip.id = "tooltip"

  document.body.appendChild(tooltip)

  const rect = element.getBoundingClientRect()
  tooltip.style.left = rect.left + rect.width / 2 - tooltip.offsetWidth / 2 + "px"
  tooltip.style.top = rect.top - tooltip.offsetHeight - 8 + "px"
}

function hideTooltip() {
  const tooltip = document.getElementById("tooltip")
  if (tooltip) {
    tooltip.remove()
  }
}

// Search Functionality
function initializeSearch() {
  const searchInputs = document.querySelectorAll('input[type="text"][placeholder*="Buscar"]')

  searchInputs.forEach((input) => {
    let searchTimeout

    input.addEventListener("input", function () {
      clearTimeout(searchTimeout)
      const query = this.value.trim()

      if (query.length >= 2) {
        searchTimeout = setTimeout(() => {
          performSearch(query)
        }, 300)
      }
    })

    input.addEventListener("keypress", function (e) {
      if (e.key === "Enter") {
        e.preventDefault()
        const query = this.value.trim()
        if (query) {
          window.location.href = `/search?q=${encodeURIComponent(query)}`
        }
      }
    })
  })
}

function performSearch(query) {
  // Show search suggestions
  console.log("Searching for:", query)
  // Implement search suggestions here
}

// Newsletter Form
function initializeNewsletterForm() {
  const newsletterForm = document.getElementById("newsletter-form")
  if (newsletterForm) {
    newsletterForm.addEventListener("submit", function (e) {
      e.preventDefault()

      // Cambiar estado del botón a loading
      const submitBtn = document.getElementById("newsletter-submit")
      const submitText = submitBtn.querySelector(".submit-text")
      const loadingText = submitBtn.querySelector(".loading-text")
      
      submitBtn.disabled = true
      submitText.classList.add("hidden")
      loadingText.classList.remove("hidden")

      const formData = new FormData(this)

      fetch("newsletter-subscribe.php", {
        method: "POST",
        body: formData,
      })
        .then((response) => response.json())
        .then((data) => {
          if (data.success) {
            showNewsletterModal("success", "¡Suscripción exitosa!", data.message)
            this.reset()
          } else {
            showNewsletterModal("error", "Error de suscripción", data.message || "Error al suscribirse. Inténtalo de nuevo.")
          }
        })
        .catch((error) => {
          console.error("Error:", error)
          showNewsletterModal("error", "Error de conexión", "Error al suscribirse. Inténtalo de nuevo.")
        })
        .finally(() => {
          // Restaurar estado del botón
          submitBtn.disabled = false
          submitText.classList.remove("hidden")
          loadingText.classList.add("hidden")
        })
    })
  }
}

// Funciones del modal de newsletter
function showNewsletterModal(type, title, message) {
  const modal = document.getElementById("newsletter-modal")
  const modalContent = document.getElementById("newsletter-modal-content")
  const icon = document.getElementById("newsletter-icon")
  const titleEl = document.getElementById("newsletter-title")
  const messageEl = document.getElementById("newsletter-message")

  // Configurar contenido según el tipo
  if (type === "success") {
    icon.className = "w-16 h-16 mx-auto mb-4 flex items-center justify-center rounded-full bg-green-100"
    icon.innerHTML = '<i class="fas fa-check text-green-500 text-2xl"></i>'
    titleEl.className = "text-xl font-bold text-green-600 mb-2"
  } else {
    icon.className = "w-16 h-16 mx-auto mb-4 flex items-center justify-center rounded-full bg-red-100"
    icon.innerHTML = '<i class="fas fa-exclamation-triangle text-red-500 text-2xl"></i>'
    titleEl.className = "text-xl font-bold text-red-600 mb-2"
  }

  titleEl.textContent = title
  messageEl.textContent = message

  // Mostrar modal con animación
  modal.classList.remove("hidden")
  setTimeout(() => {
    modalContent.classList.remove("scale-95", "opacity-0")
    modalContent.classList.add("scale-100", "opacity-100")
  }, 50)
}

function closeNewsletterModal() {
  const modal = document.getElementById("newsletter-modal")
  const modalContent = document.getElementById("newsletter-modal-content")

  // Ocultar modal con animación
  modalContent.classList.remove("scale-100", "opacity-100")
  modalContent.classList.add("scale-95", "opacity-0")
  
  setTimeout(() => {
    modal.classList.add("hidden")
  }, 300)
}

// Cerrar modal al hacer clic fuera de él
document.addEventListener("DOMContentLoaded", function() {
  const modal = document.getElementById("newsletter-modal")
  if (modal) {
    modal.addEventListener("click", function(e) {
      if (e.target === modal) {
        closeNewsletterModal()
      }
    })
  }
})

// Product Quick View
function initializeProductQuickView() {
  document.addEventListener("click", (e) => {
    if (e.target.closest(".quick-view-btn")) {
      e.preventDefault()
      const productId = e.target.closest(".quick-view-btn").dataset.productId
      openQuickView(productId)
    }
  })
}

function openQuickView(productId) {
  // Create modal
  const modal = document.createElement("div")
  modal.className = "fixed inset-0 z-50 overflow-y-auto"
  modal.innerHTML = `
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 transition-opacity bg-gray-500 bg-opacity-75" onclick="this.parentElement.parentElement.remove()"></div>
            <div class="inline-block w-full max-w-4xl p-6 my-8 overflow-hidden text-left align-middle transition-all transform bg-white shadow-xl rounded-2xl">
                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-2xl font-bold text-gray-900">Vista Rápida</h3>
                    <button class="text-gray-400 hover:text-gray-600" onclick="this.closest('.fixed').remove()">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <div>
                        <img src="/placeholder.svg?height=400&width=400" alt="Producto" class="w-full h-96 object-cover rounded-lg">
                    </div>
                    <div class="space-y-4">
                        <div>
                            <h4 class="text-xl font-semibold text-gray-900">Nombre del Producto</h4>
                            <p class="text-gray-600">Marca del Producto</p>
                        </div>
                        <div class="flex items-center space-x-2">
                            <div class="flex text-yellow-400">
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                            </div>
                            <span class="text-sm text-gray-500">(124 reseñas)</span>
                        </div>
                        <div class="flex items-center space-x-3">
                            <span class="text-3xl font-bold text-primary-500">$89.000</span>
                            <span class="text-lg text-gray-400 line-through">$95.000</span>
                        </div>
                        <p class="text-gray-600">Descripción del producto aquí...</p>
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Cantidad</label>
                                <div class="flex items-center space-x-3">
                                    <button class="w-10 h-10 border border-gray-300 rounded-full flex items-center justify-center hover:bg-gray-100">
                                        <i class="fas fa-minus"></i>
                                    </button>
                                    <span class="font-semibold">1</span>
                                    <button class="w-10 h-10 border border-gray-300 rounded-full flex items-center justify-center hover:bg-gray-100">
                                        <i class="fas fa-plus"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="flex space-x-4">
                                <button class="flex-1 bg-gradient-to-r from-primary-500 to-secondary-500 text-white py-3 rounded-full font-semibold hover:shadow-lg transform hover:scale-105 transition-all duration-300">
                                    Agregar al Carrito
                                </button>
                                <button class="w-12 h-12 border-2 border-primary-500 text-primary-500 rounded-full flex items-center justify-center hover:bg-primary-500 hover:text-white transition-colors duration-300">
                                    <i class="fas fa-heart"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `

  document.body.appendChild(modal)
  document.body.style.overflow = "hidden"

  // Close modal when clicking outside
  modal.addEventListener("click", (e) => {
    if (e.target === modal) {
      modal.remove()
      document.body.style.overflow = ""
    }
  })
}

// Wishlist Functionality
function initializeWishlist() {
  document.addEventListener("click", (e) => {
    if (e.target.closest(".wishlist-btn")) {
      e.preventDefault()
      const btn = e.target.closest(".wishlist-btn")
      const productId = btn.dataset.productId
      toggleWishlist(productId, btn)
    }
  })
}

function toggleWishlist(productId, btn) {
  const icon = btn.querySelector("i")
  const isInWishlist = icon.classList.contains("fas")

  if (isInWishlist) {
    // Remove from wishlist
    icon.classList.remove("fas")
    icon.classList.add("far")
    btn.classList.remove("text-red-500")
    btn.classList.add("text-gray-400")
    alert("Producto removido de la lista de deseos")
  } else {
    // Add to wishlist
    icon.classList.remove("far")
    icon.classList.add("fas")
    btn.classList.remove("text-gray-400")
    btn.classList.add("text-red-500")
    alert("Producto agregado a la lista de deseos")

    // Animate heart
    btn.classList.add("animate-bounce")
    setTimeout(() => {
      btn.classList.remove("animate-bounce")
    }, 600)
  }
}

// Lazy Loading for Images
function initializeLazyLoading() {
  const images = document.querySelectorAll("img[data-src]")

  const imageObserver = new IntersectionObserver((entries, observer) => {
    entries.forEach((entry) => {
      if (entry.isIntersecting) {
        const img = entry.target
        img.src = img.dataset.src
        img.classList.remove("skeleton")
        img.classList.add("animate-fade-in")
        observer.unobserve(img)
      }
    })
  })

  images.forEach((img) => {
    img.classList.add("skeleton")
    imageObserver.observe(img)
  })
}

// Utility Functions
function debounce(func, wait) {
  let timeout
  return function executedFunction(...args) {
    const later = () => {
      clearTimeout(timeout)
      func(...args)
    }
    clearTimeout(timeout)
    timeout = setTimeout(later, wait)
  }
}

function throttle(func, limit) {
  let inThrottle
  return function () {
    const args = arguments

    if (!inThrottle) {
      func.apply(this, args)
      inThrottle = true
      setTimeout(() => (inThrottle = false), limit)
    }
  }
}

// Format Price
function formatPrice(price) {
  return new Intl.NumberFormat("es-CO", {
    style: "currency",
    currency: "COP",
    minimumFractionDigits: 0,
  }).format(price)
}

// Local Storage Helpers
function setLocalStorage(key, value) {
  try {
    localStorage.setItem(key, JSON.stringify(value))
  } catch (e) {
    console.error("Error saving to localStorage:", e)
  }
}

function getLocalStorage(key) {
  try {
    const item = localStorage.getItem(key)
    return item ? JSON.parse(item) : null
  } catch (e) {
    console.error("Error reading from localStorage:", e)
    return null
  }
}

// Session Storage Helpers
function setSessionStorage(key, value) {
  try {
    sessionStorage.setItem(key, JSON.stringify(value))
  } catch (e) {
    console.error("Error saving to sessionStorage:", e)
  }
}

function getSessionStorage(key) {
  try {
    const item = sessionStorage.getItem(key)
    return item ? JSON.parse(item) : null
  } catch (e) {
    console.error("Error reading from sessionStorage:", e)
    return null
  }
}

// Cookie Helpers
function setCookie(name, value, days) {
  const expires = new Date()
  expires.setTime(expires.getTime() + days * 24 * 60 * 60 * 1000)
  document.cookie = `${name}=${value};expires=${expires.toUTCString()};path=/`
}

function getCookie(name) {
  const nameEQ = name + "="
  const ca = document.cookie.split(";")
  for (let i = 0; i < ca.length; i++) {
    let c = ca[i]
    while (c.charAt(0) === " ") c = c.substring(1, c.length)
    if (c.indexOf(nameEQ) === 0) return c.substring(nameEQ.length, c.length)
  }
  return null
}

// Performance Monitoring
function measurePerformance(name, fn) {
  const start = performance.now()
  const result = fn()
  const end = performance.now()
  console.log(`${name} took ${end - start} milliseconds`)
  return result
}

// Error Handling
window.addEventListener("error", (e) => {
  console.error("JavaScript Error:", e.error)
  // Send error to logging service in production
})

window.addEventListener("unhandledrejection", (e) => {
  console.error("Unhandled Promise Rejection:", e.reason)
  // Send error to logging service in production
})

// Global functions that HTML needs
window.updateCartQuantity = function(id, change) {
  fetch('cart-update.php', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/x-www-form-urlencoded',
    },
    body: `id=${id}&change=${change}`
  })
  .then(response => response.json())
  .then(data => {
    if (data.success) {
      location.reload();
    }
  })
  .catch(error => {
    console.error('Error:', error);
  });
}

window.removeFromCart = function(id) {
  fetch('cart-remove.php', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/x-www-form-urlencoded',
    },
    body: `id=${id}`
  })
  .then(response => response.json())
  .then(data => {
    if (data.success) {
      location.reload();
    }
  })
  .catch(error => {
    console.error('Error:', error);
  });
}

window.addToCart = function(productId, quantity = 1) {
  const button = event.target.closest('button');
  const originalText = button.innerHTML;
  button.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Agregando...';
  button.disabled = true;
  
  fetch('cart-add.php', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/x-www-form-urlencoded',
    },
    body: `product_id=${productId}&quantity=${quantity}`
  })
  .then(response => response.json())
  .then(data => {
    if (data.success) {
      showNotification('Producto agregado al carrito ✨', 'success');
      if (typeof updateCartCount === 'function') {
        updateCartCount();
      }

      button.innerHTML = '<i class="fas fa-check mr-2"></i>¡Agregado!';
      button.classList.add('bg-green-500');

      setTimeout(() => {
        button.innerHTML = originalText;
        button.classList.remove('bg-green-500');
        button.disabled = false;
      }, 2000);
    } else {
      showNotification(data.message || 'Error al agregar producto', 'error');
      button.innerHTML = originalText;
      button.disabled = false;
    }
  })
  .catch(error => {
    console.error('Error:', error);
    showNotification('Error al agregar producto', 'error');
    button.innerHTML = originalText;
    button.disabled = false;
  });
}

window.toggleWishlist = function(productId) {
  const button = event.target.closest('button');
  const icon = button.querySelector('i');

  fetch('wishlist-toggle.php', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/x-www-form-urlencoded',
    },
    body: `product_id=${productId}`
  })
  .then(response => response.json())
  .then(data => {
    if (data.success) {
      if (data.action === 'added') {
        icon.style.color = '#e11d48';
        icon.classList.add('fas');
        icon.classList.remove('far');
        showNotification('Agregado a favoritos ❤️', 'success');
        button.classList.add('animate-bounce');
        setTimeout(() => button.classList.remove('animate-bounce'), 600);
      } else {
        icon.style.color = '';
        icon.classList.add('far');
        icon.classList.remove('fas');
        showNotification('Removido de favoritos', 'info');
      }
    } else {
      showNotification(data.message || 'Error al procesar favoritos', 'error');
    }
  })
  .catch(error => {
    console.error('Error:', error);
    showNotification('Error al procesar favoritos', 'error');
  });
}

window.quickView = function(productId) {
  window.location.href = `product.php?id=${productId}`;
}

window.showNotification = function(message, type = 'info') {
  const notification = document.createElement('div');
  const colors = {
    success: 'from-green-500 to-emerald-500',
    error: 'from-red-500 to-rose-500',
    warning: 'from-yellow-500 to-orange-500',
    info: 'from-blue-500 to-indigo-500'
  };

  const icons = {
    success: '✓',
    error: '✗',
    warning: '⚠',
    info: 'ℹ'
  };

  notification.className = `fixed top-8 right-8 md:top-6 md:right-6 z-50 p-6 md:p-4 rounded-2xl shadow-2xl text-white max-w-sm transition-all duration-500 transform translate-x-full bg-gradient-to-r ${colors[type] || colors.info}`;
  notification.innerHTML = `
    <div class="flex items-center">
      <span class="mr-3 text-2xl md:text-xl">
        ${icons[type] || icons.info}
      </span>
      <span class="font-medium text-sm md:text-xs">${message}</span>
      <button onclick="this.parentElement.parentElement.remove()" class="ml-4 text-white hover:text-gray-200 text-xl md:text-lg">×</button>
    </div>
  `;

  document.body.appendChild(notification);

  setTimeout(() => {
    notification.classList.remove('translate-x-full');
  }, 100);

  setTimeout(() => {
    if (notification.parentElement) {
      notification.classList.add('translate-x-full');
      setTimeout(() => {
        notification.remove();
      }, 500);
    }
  }, 4000);
}

window.updateCartCount = function() {
  fetch('cart-count.php')
  .then(response => response.json())
  .then(data => {
    const cartBadges = document.querySelectorAll('#cart-count, #mobile-cart-count');
    cartBadges.forEach(badge => {
      if (badge) {
        badge.textContent = data.count || 0;
        if (data.count > 0) {
          badge.classList.add('animate-bounce');
          setTimeout(() => badge.classList.remove('animate-bounce'), 600);
        }
      }
    });
  })
  .catch(error => {
    console.error('Error updating cart count:', error);
  });
}
