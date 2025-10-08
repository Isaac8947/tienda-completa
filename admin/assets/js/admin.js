// Admin Panel JavaScript

document.addEventListener("DOMContentLoaded", () => {
  initializeSidebar()
  initializeDropdowns()
  initializeModals()
  initializeToasts()
  initializeFileUploads()
  initializeDataTables()
  initializeCharts()
  initializeFormValidation()
})

// Sidebar Management
function initializeSidebar() {
  const mobileMenuBtn = document.getElementById("mobile-menu-btn")
  const sidebar = document.getElementById("sidebar")
  const sidebarOverlay = document.getElementById("sidebar-overlay")

  if (mobileMenuBtn && sidebar) {
    mobileMenuBtn.addEventListener("click", () => {
      sidebar.classList.toggle("show")
      if (sidebarOverlay) {
        sidebarOverlay.style.display = sidebar.classList.contains("show") ? "block" : "none"
      }
    })
  }

  if (sidebarOverlay) {
    sidebarOverlay.addEventListener("click", () => {
      sidebar.classList.remove("show")
      sidebarOverlay.style.display = "none"
    })
  }

  // Close sidebar on window resize
  window.addEventListener("resize", () => {
    if (window.innerWidth >= 768) {
      sidebar.classList.remove("show")
      if (sidebarOverlay) {
        sidebarOverlay.style.display = "none"
      }
    }
  })
}

// Dropdown Management
function initializeDropdowns() {
  const dropdownButtons = document.querySelectorAll('[id$="-btn"]')

  dropdownButtons.forEach((button) => {
    const dropdownId = button.id.replace("-btn", "-dropdown")
    const dropdown = document.getElementById(dropdownId)

    if (dropdown) {
      button.addEventListener("click", (e) => {
        e.stopPropagation()

        // Close other dropdowns
        document.querySelectorAll('[id$="-dropdown"]').forEach((d) => {
          if (d !== dropdown) {
            d.classList.add("hidden")
          }
        })

        dropdown.classList.toggle("hidden")
      })
    }
  })

  // Close dropdowns when clicking outside
  document.addEventListener("click", () => {
    document.querySelectorAll('[id$="-dropdown"]').forEach((dropdown) => {
      dropdown.classList.add("hidden")
    })
  })
}

// Modal Management
function initializeModals() {
  const modals = document.querySelectorAll('[id$="-modal"]')

  modals.forEach((modal) => {
    modal.addEventListener("click", (e) => {
      if (e.target === modal) {
        closeModal(modal)
      }
    })
  })

  // Close modal with Escape key
  document.addEventListener("keydown", (e) => {
    if (e.key === "Escape") {
      const openModal = document.querySelector('[id$="-modal"]:not(.hidden)')
      if (openModal) {
        closeModal(openModal)
      }
    }
  })
}

function openModal(modalId) {
  const modal = document.getElementById(modalId)
  if (modal) {
    modal.classList.remove("hidden")
    document.body.style.overflow = "hidden"

    // Focus trap
    const focusableElements = modal.querySelectorAll('button, input, select, textarea, [tabindex]:not([tabindex="-1"])')
    if (focusableElements.length > 0) {
      focusableElements[0].focus()
    }
  }
}

function closeModal(modal) {
  if (typeof modal === "string") {
    modal = document.getElementById(modal)
  }

  if (modal) {
    modal.classList.add("hidden")
    document.body.style.overflow = ""
  }
}

// Toast Notifications
function initializeToasts() {
  // Auto-hide toasts after 5 seconds
  document.querySelectorAll(".toast").forEach((toast) => {
    setTimeout(() => {
      hideToast(toast)
    }, 5000)
  })
}

function showToast(message, type = "info", duration = 5000) {
  const toast = document.createElement("div")
  toast.className = `toast ${type}`
  toast.innerHTML = `
        <div class="flex items-start space-x-3">
            <div class="flex-shrink-0">
                ${getToastIcon(type)}
            </div>
            <div class="flex-1">
                <p class="text-sm font-medium text-gray-900">${message}</p>
            </div>
            <button class="flex-shrink-0 text-gray-400 hover:text-gray-600" onclick="hideToast(this.closest('.toast'))">
                <i class="fas fa-times"></i>
            </button>
        </div>
    `

  document.body.appendChild(toast)

  // Show toast
  setTimeout(() => {
    toast.classList.add("show")
  }, 100)

  // Auto hide
  setTimeout(() => {
    hideToast(toast)
  }, duration)

  return toast
}

function hideToast(toast) {
  if (toast) {
    toast.classList.remove("show")
    setTimeout(() => {
      if (toast.parentElement) {
        toast.remove()
      }
    }, 300)
  }
}

function getToastIcon(type) {
  const icons = {
    success: '<i class="fas fa-check-circle text-green-500"></i>',
    error: '<i class="fas fa-exclamation-circle text-red-500"></i>',
    warning: '<i class="fas fa-exclamation-triangle text-yellow-500"></i>',
    info: '<i class="fas fa-info-circle text-blue-500"></i>',
  }
  return icons[type] || icons.info
}

// File Upload Management
function initializeFileUploads() {
  const fileInputs = document.querySelectorAll('input[type="file"]')

  fileInputs.forEach((input) => {
    input.addEventListener("change", (e) => {
      handleFileUpload(e.target)
    })
  })
}

function handleFileUpload(input) {
  const files = input.files
  const previewContainer = input.closest(".file-upload-container")?.querySelector(".file-preview")

  if (previewContainer && files.length > 0) {
    previewContainer.innerHTML = ""

    Array.from(files).forEach((file, index) => {
      if (file.type.startsWith("image/")) {
        const reader = new FileReader()
        reader.onload = (e) => {
          const preview = createImagePreview(e.target.result, index)
          previewContainer.appendChild(preview)
        }
        reader.readAsDataURL(file)
      }
    })
  }
}

function createImagePreview(src, index) {
  const div = document.createElement("div")
  div.className = "image-preview"
  div.innerHTML = `
        <img src="${src}" alt="Preview ${index + 1}">
        <button type="button" class="remove-image" onclick="removeImagePreview(this)">
            <i class="fas fa-times"></i>
        </button>
    `
  return div
}

function removeImagePreview(button) {
  const preview = button.closest(".image-preview")
  if (preview) {
    preview.remove()
  }
}

// Data Tables
function initializeDataTables() {
  // Search functionality
  const searchInputs = document.querySelectorAll(".data-table-search")
  searchInputs.forEach((input) => {
    let searchTimeout
    input.addEventListener("input", function () {
      clearTimeout(searchTimeout)
      searchTimeout = setTimeout(() => {
        filterTable(this.value, this.closest(".data-table-container"))
      }, 300)
    })
  })

  // Sort functionality
  const sortButtons = document.querySelectorAll(".sort-button")
  sortButtons.forEach((button) => {
    button.addEventListener("click", function () {
      const column = this.dataset.column
      const direction = this.dataset.direction === "asc" ? "desc" : "asc"
      sortTable(column, direction, this.closest("table"))
      this.dataset.direction = direction
    })
  })
}

function filterTable(query, container) {
  const table = container.querySelector("table")
  const rows = table.querySelectorAll("tbody tr")

  rows.forEach((row) => {
    const text = row.textContent.toLowerCase()
    const matches = text.includes(query.toLowerCase())
    row.style.display = matches ? "" : "none"
  })
}

function sortTable(column, direction, table) {
  const tbody = table.querySelector("tbody")
  const rows = Array.from(tbody.querySelectorAll("tr"))

  rows.sort((a, b) => {
    const aValue = a.querySelector(`[data-column="${column}"]`)?.textContent || ""
    const bValue = b.querySelector(`[data-column="${column}"]`)?.textContent || ""

    if (direction === "asc") {
      return aValue.localeCompare(bValue, undefined, { numeric: true })
    } else {
      return bValue.localeCompare(aValue, undefined, { numeric: true })
    }
  })

  rows.forEach((row) => tbody.appendChild(row))
}

// Charts Initialization
function initializeCharts() {
  // This would be called for specific chart implementations
  // Chart.js configurations would go here
}

// Form Validation
function initializeFormValidation() {
  const forms = document.querySelectorAll("form[data-validate]")

  forms.forEach((form) => {
    form.addEventListener("submit", (e) => {
      if (!validateForm(form)) {
        e.preventDefault()
      }
    })

    // Real-time validation
    const inputs = form.querySelectorAll("input, select, textarea")
    inputs.forEach((input) => {
      input.addEventListener("blur", () => {
        validateField(input)
      })
    })
  })
}

function validateForm(form) {
  let isValid = true
  const inputs = form.querySelectorAll("input[required], select[required], textarea[required]")

  inputs.forEach((input) => {
    if (!validateField(input)) {
      isValid = false
    }
  })

  return isValid
}

function validateField(field) {
  const value = field.value.trim()
  const type = field.type
  let isValid = true
  let message = ""

  // Required validation
  if (field.hasAttribute("required") && !value) {
    isValid = false
    message = "Este campo es requerido"
  }

  // Email validation
  if (type === "email" && value && !isValidEmail(value)) {
    isValid = false
    message = "Ingresa un email válido"
  }

  // URL validation
  if (type === "url" && value && !isValidURL(value)) {
    isValid = false
    message = "Ingresa una URL válida"
  }

  // Number validation
  if (type === "number" && value) {
    const min = field.getAttribute("min")
    const max = field.getAttribute("max")
    const numValue = Number.parseFloat(value)

    if (min && numValue < Number.parseFloat(min)) {
      isValid = false
      message = `El valor mínimo es ${min}`
    }

    if (max && numValue > Number.parseFloat(max)) {
      isValid = false
      message = `El valor máximo es ${max}`
    }
  }

  // Update field appearance
  if (isValid) {
    field.classList.remove("error")
    hideFieldError(field)
  } else {
    field.classList.add("error")
    showFieldError(field, message)
  }

  return isValid
}

function showFieldError(field, message) {
  let errorElement = field.parentElement.querySelector(".field-error")

  if (!errorElement) {
    errorElement = document.createElement("div")
    errorElement.className = "field-error text-red-600 text-sm mt-1"
    field.parentElement.appendChild(errorElement)
  }

  errorElement.textContent = message
}

function hideFieldError(field) {
  const errorElement = field.parentElement.querySelector(".field-error")
  if (errorElement) {
    errorElement.remove()
  }
}

// Utility Functions
function isValidEmail(email) {
  const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
  return emailRegex.test(email)
}

function isValidURL(url) {
  try {
    new URL(url)
    return true
  } catch {
    return false
  }
}

function formatPrice(price) {
  return new Intl.NumberFormat("es-CO", {
    style: "currency",
    currency: "COP",
    minimumFractionDigits: 0,
  }).format(price)
}

function formatDate(date) {
  return new Intl.DateTimeFormat("es-CO", {
    year: "numeric",
    month: "short",
    day: "numeric",
    hour: "2-digit",
    minute: "2-digit",
  }).format(new Date(date))
}

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

// AJAX Helper Functions
function makeRequest(url, options = {}) {
  const defaultOptions = {
    method: "GET",
    headers: {
      "Content-Type": "application/json",
      "X-Requested-With": "XMLHttpRequest",
    },
  }

  const config = { ...defaultOptions, ...options }

  return fetch(url, config)
    .then((response) => {
      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`)
      }
      return response.json()
    })
    .catch((error) => {
      console.error("Request failed:", error)
      showToast("Error en la solicitud", "error")
      throw error
    })
}

// Export functions for global use
window.AdminJS = {
  showToast,
  hideToast,
  openModal,
  closeModal,
  formatPrice,
  formatDate,
  makeRequest,
  validateForm,
  validateField,
}
