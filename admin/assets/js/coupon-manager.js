/**
 * JavaScript utilities for Coupon Management
 * Advanced functionality for the coupons admin interface
 */

class CouponManager {
    constructor() {
        this.apiEndpoint = window.location.pathname;
        this.selectedCoupons = new Set();
        this.init();
    }

    init() {
        this.setupEventListeners();
        this.setupFormValidation();
        this.setupDateValidation();
        this.initializeComponents();
    }

    setupEventListeners() {
        // Bulk selection
        document.getElementById('selectAll')?.addEventListener('change', (e) => {
            this.selectAll(e.target.checked);
        });

        // Individual checkbox selection
        document.querySelectorAll('.coupon-checkbox').forEach(checkbox => {
            checkbox.addEventListener('change', () => this.updateSelection());
        });

        // Discount type change
        document.getElementById('discountType')?.addEventListener('change', (e) => {
            this.updateValuePrefix(e.target.value);
        });

        // Form submission
        document.getElementById('couponForm')?.addEventListener('submit', (e) => {
            this.handleFormSubmit(e);
        });

        // Validator form
        document.getElementById('validatorForm')?.addEventListener('submit', (e) => {
            this.handleValidatorSubmit(e);
        });

        // Real-time coupon validation
        document.querySelector('input[name="code"]')?.addEventListener('blur', (e) => {
            this.validateCodeUniqueness(e.target.value);
        });
    }

    setupFormValidation() {
        // Custom validation rules
        const form = document.getElementById('couponForm');
        if (!form) return;

        // Value validation based on type
        const valueInput = form.querySelector('input[name="value"]');
        const typeSelect = form.querySelector('select[name="type"]');
        
        if (valueInput && typeSelect) {
            const validateValue = () => {
                const type = typeSelect.value;
                const value = parseFloat(valueInput.value);
                
                if (type === 'percentage') {
                    if (value > 100) {
                        this.showFieldError(valueInput, 'El porcentaje no puede ser mayor a 100%');
                        return false;
                    }
                } else if (type === 'fixed') {
                    if (value <= 0) {
                        this.showFieldError(valueInput, 'El valor debe ser mayor a 0');
                        return false;
                    }
                }
                
                this.clearFieldError(valueInput);
                return true;
            };

            valueInput.addEventListener('blur', validateValue);
            typeSelect.addEventListener('change', validateValue);
        }
    }

    setupDateValidation() {
        const startDate = document.querySelector('input[name="start_date"]');
        const endDate = document.querySelector('input[name="end_date"]');
        
        if (startDate && endDate) {
            const validateDates = () => {
                if (startDate.value && endDate.value) {
                    if (new Date(startDate.value) >= new Date(endDate.value)) {
                        this.showFieldError(endDate, 'La fecha de fin debe ser posterior a la fecha de inicio');
                        return false;
                    }
                }
                this.clearFieldError(endDate);
                return true;
            };

            startDate.addEventListener('change', validateDates);
            endDate.addEventListener('change', validateDates);
        }
    }

    initializeComponents() {
        // Set default start date to now
        const startDateInput = document.querySelector('input[name="start_date"]');
        if (startDateInput && !startDateInput.value) {
            const now = new Date();
            now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
            startDateInput.value = now.toISOString().slice(0, 16);
        }

        // Initialize select2 for multi-select fields
        this.initializeMultiSelect();
    }

    initializeMultiSelect() {
        // Initialize Select2 for better multi-select experience
        const multiSelects = document.querySelectorAll('select[multiple]');
        multiSelects.forEach(select => {
            // Basic enhancement - you can integrate Select2 library here
            select.setAttribute('data-placeholder', select.dataset.placeholder || 'Seleccionar opciones...');
        });
    }

    selectAll(checked) {
        document.querySelectorAll('.coupon-checkbox').forEach(checkbox => {
            checkbox.checked = checked;
        });
        this.updateSelection();
    }

    updateSelection() {
        const checkboxes = document.querySelectorAll('.coupon-checkbox:checked');
        const selectedCount = checkboxes.length;
        
        // Update selected set
        this.selectedCoupons.clear();
        checkboxes.forEach(cb => this.selectedCoupons.add(cb.value));
        
        // Toggle bulk actions visibility
        const bulkActions = document.querySelector('.bulk-actions');
        if (bulkActions) {
            bulkActions.style.display = selectedCount > 0 ? 'block' : 'none';
        }

        // Update select all checkbox state
        const selectAll = document.getElementById('selectAll');
        if (selectAll) {
            const totalCheckboxes = document.querySelectorAll('.coupon-checkbox').length;
            selectAll.checked = selectedCount === totalCheckboxes;
            selectAll.indeterminate = selectedCount > 0 && selectedCount < totalCheckboxes;
        }

        return selectedCount;
    }

    updateValuePrefix(type) {
        const prefix = document.getElementById('valuePrefix');
        if (prefix) {
            prefix.textContent = type === 'percentage' ? '%' : '$';
        }
    }

    async generateCode() {
        try {
            const response = await this.apiCall('generate_code');
            if (response.code) {
                document.getElementById('couponCode').value = response.code;
            }
        } catch (error) {
            console.error('Error generating code:', error);
            this.showAlert('Error al generar código', 'error');
        }
    }

    async validateCodeUniqueness(code) {
        if (!code || code.length < 3) return;
        
        try {
            // Simple check - in a real implementation, you'd call the server
            const existingCode = document.querySelector(`[data-code="${code}"]`);
            if (existingCode) {
                this.showFieldError(
                    document.querySelector('input[name="code"]'),
                    'Este código ya existe'
                );
            }
        } catch (error) {
            console.error('Error validating code:', error);
        }
    }

    async handleFormSubmit(e) {
        e.preventDefault();
        
        const form = e.target;
        const formData = new FormData(form);
        const isEdit = formData.get('id');
        
        // Add action
        formData.append('action', isEdit ? 'update' : 'create');
        
        // Process multi-select fields
        this.processMultiSelectFields(formData);
        
        // Validate form
        if (!this.validateForm(form)) {
            return;
        }

        try {
            this.showLoading(true);
            const response = await this.apiCall(formData.get('action'), formData);
            
            if (response.success) {
                this.showAlert(response.message, 'success');
                setTimeout(() => location.reload(), 1500);
            } else {
                this.showAlert(response.message, 'error');
            }
        } catch (error) {
            console.error('Form submission error:', error);
            this.showAlert('Error al procesar la solicitud', 'error');
        } finally {
            this.showLoading(false);
        }
    }

    async handleValidatorSubmit(e) {
        e.preventDefault();
        
        const form = e.target;
        const formData = new FormData(form);
        formData.append('action', 'validate');

        try {
            const response = await this.apiCall('validate', formData);
            this.displayValidationResult(response);
        } catch (error) {
            console.error('Validation error:', error);
            this.displayValidationResult({
                valid: false,
                message: 'Error al validar el cupón'
            });
        }
    }

    processMultiSelectFields(formData) {
        const multiSelectFields = ['customer_ids', 'product_ids', 'category_ids', 'brand_ids'];
        
        multiSelectFields.forEach(field => {
            const select = document.querySelector(`select[name="${field}"]`);
            if (select && select.multiple) {
                const values = Array.from(select.selectedOptions).map(opt => opt.value);
                formData.set(field, values.join(','));
            }
        });
    }

    validateForm(form) {
        let isValid = true;
        
        // Clear previous errors
        form.querySelectorAll('.is-invalid').forEach(field => {
            this.clearFieldError(field);
        });

        // Required field validation
        const requiredFields = form.querySelectorAll('[required]');
        requiredFields.forEach(field => {
            if (!field.value.trim()) {
                this.showFieldError(field, 'Este campo es obligatorio');
                isValid = false;
            }
        });

        // Custom validations
        const codeField = form.querySelector('input[name="code"]');
        if (codeField && codeField.value) {
            if (!/^[A-Z0-9]+$/.test(codeField.value)) {
                this.showFieldError(codeField, 'El código solo puede contener letras mayúsculas y números');
                isValid = false;
            }
        }

        return isValid;
    }

    displayValidationResult(result) {
        const resultDiv = document.getElementById('validationResult');
        if (!resultDiv) return;

        if (result.valid) {
            resultDiv.innerHTML = `
                <div class="alert alert-success">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-check-circle me-2"></i>
                        <div>
                            <strong>✓ Cupón válido</strong><br>
                            <span class="text-muted">${result.message}</span>
                            ${result.discount ? `<br><strong>Descuento aplicable: $${result.discount}</strong>` : ''}
                        </div>
                    </div>
                </div>
            `;
        } else {
            resultDiv.innerHTML = `
                <div class="alert alert-danger">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-times-circle me-2"></i>
                        <div>
                            <strong>✗ Cupón inválido</strong><br>
                            <span>${result.message}</span>
                        </div>
                    </div>
                </div>
            `;
        }
    }

    async bulkAction(action, status = null) {
        const selectedCount = this.updateSelection();
        
        if (selectedCount === 0) {
            this.showAlert('Selecciona al menos un cupón', 'warning');
            return;
        }

        const actions = {
            'active': 'activar',
            'inactive': 'desactivar',
            'delete': 'eliminar'
        };

        const actionText = actions[action] || action;
        const confirmMessage = `¿${actionText.charAt(0).toUpperCase() + actionText.slice(1)} ${selectedCount} cupón(es)?`;
        
        if (!confirm(confirmMessage)) return;

        try {
            this.showLoading(true);
            
            const formData = new FormData();
            formData.append('action', action === 'delete' ? 'bulk_delete' : 'bulk_status');
            formData.append('ids', Array.from(this.selectedCoupons).join(','));
            
            if (status) {
                formData.append('bulk_status', status);
            }

            const response = await this.apiCall(formData.get('action'), formData);
            
            if (response.success) {
                this.showAlert(response.message, 'success');
                setTimeout(() => location.reload(), 1500);
            } else {
                this.showAlert(response.message, 'error');
            }
        } catch (error) {
            console.error('Bulk action error:', error);
            this.showAlert('Error al procesar la acción', 'error');
        } finally {
            this.showLoading(false);
        }
    }

    async duplicateCoupon(id) {
        if (!confirm('¿Duplicar este cupón?')) return;

        try {
            this.showLoading(true);
            const response = await this.apiCall('duplicate', { id });
            
            if (response.success) {
                this.showAlert(response.message, 'success');
                setTimeout(() => location.reload(), 1500);
            } else {
                this.showAlert(response.message, 'error');
            }
        } catch (error) {
            console.error('Duplicate error:', error);
            this.showAlert('Error al duplicar el cupón', 'error');
        } finally {
            this.showLoading(false);
        }
    }

    async deleteCoupon(id) {
        if (!confirm('¿Eliminar este cupón? Esta acción no se puede deshacer.')) return;

        try {
            this.showLoading(true);
            const response = await this.apiCall('delete', { id });
            
            if (response.success) {
                this.showAlert(response.message, 'success');
                setTimeout(() => location.reload(), 1500);
            } else {
                this.showAlert(response.message, 'error');
            }
        } catch (error) {
            console.error('Delete error:', error);
            this.showAlert('Error al eliminar el cupón', 'error');
        } finally {
            this.showLoading(false);
        }
    }

    async apiCall(action, data = {}) {
        const isFormData = data instanceof FormData;
        const body = isFormData ? data : new FormData();
        
        if (!isFormData) {
            body.append('action', action);
            Object.keys(data).forEach(key => body.append(key, data[key]));
        }

        const response = await fetch(this.apiEndpoint, {
            method: 'POST',
            body: body
        });

        if (!response.ok) {
            throw new Error('Network response was not ok');
        }

        return await response.json();
    }

    showFieldError(field, message) {
        field.classList.add('is-invalid');
        
        // Remove existing error message
        const existingError = field.parentNode.querySelector('.invalid-feedback');
        if (existingError) {
            existingError.remove();
        }

        // Add new error message
        const errorDiv = document.createElement('div');
        errorDiv.className = 'invalid-feedback';
        errorDiv.textContent = message;
        field.parentNode.appendChild(errorDiv);
    }

    clearFieldError(field) {
        field.classList.remove('is-invalid');
        const errorMsg = field.parentNode.querySelector('.invalid-feedback');
        if (errorMsg) {
            errorMsg.remove();
        }
    }

    showAlert(message, type = 'info') {
        // Create and show Bootstrap alert
        const alertDiv = document.createElement('div');
        alertDiv.className = `alert alert-${type === 'error' ? 'danger' : type} alert-dismissible fade show position-fixed`;
        alertDiv.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
        alertDiv.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        
        document.body.appendChild(alertDiv);
        
        // Auto-remove after 5 seconds
        setTimeout(() => {
            if (alertDiv.parentNode) {
                alertDiv.remove();
            }
        }, 5000);
    }

    showLoading(show) {
        const buttons = document.querySelectorAll('button[type="submit"]');
        buttons.forEach(btn => {
            if (show) {
                btn.disabled = true;
                btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Procesando...';
            } else {
                btn.disabled = false;
                btn.innerHTML = btn.dataset.originalText || 'Guardar';
            }
        });
    }

    // Utility methods for external access
    openCouponValidator() {
        const modal = new bootstrap.Modal(document.getElementById('validatorModal'));
        modal.show();
    }

    editCoupon(id) {
        // This would load coupon data and populate the modal
        // For now, just show a placeholder
        this.showAlert('Función de edición: cargar datos del cupón ID ' + id, 'info');
    }

    viewUsage(id) {
        const modal = new bootstrap.Modal(document.getElementById('usageModal'));
        document.getElementById('usageContent').innerHTML = '<div class="text-center"><i class="fas fa-spinner fa-spin"></i> Cargando historial de uso...</div>';
        modal.show();
        
        // Here you would load usage data via AJAX
        setTimeout(() => {
            document.getElementById('usageContent').innerHTML = `
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i> 
                    Función de historial de uso en desarrollo para cupón ID: ${id}
                </div>
            `;
        }, 1000);
    }
}

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    window.couponManager = new CouponManager();
});

// Global functions for backward compatibility
function generateCode() {
    window.couponManager?.generateCode();
}

function openCouponValidator() {
    window.couponManager?.openCouponValidator();
}

function editCoupon(id) {
    window.couponManager?.editCoupon(id);
}

function viewUsage(id) {
    window.couponManager?.viewUsage(id);
}

function duplicateCoupon(id) {
    window.couponManager?.duplicateCoupon(id);
}

function deleteCoupon(id) {
    window.couponManager?.deleteCoupon(id);
}

function bulkAction(status) {
    window.couponManager?.bulkAction(status, status);
}

function bulkDelete() {
    window.couponManager?.bulkAction('delete');
}
