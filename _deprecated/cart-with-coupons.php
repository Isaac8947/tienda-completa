<?php
/**
 * Frontend Coupon Integration Example
 * This file shows how to integrate coupons in the shopping cart/checkout process
 */

session_start();
require_once 'models/Coupon.php';
require_once 'models/Cart.php';

class CouponIntegration {
    private $coupon;
    private $cart;
    
    public function __construct() {
        $this->coupon = new Coupon();
        $this->cart = new Cart();
    }
    
    /**
     * Apply coupon to cart
     */
    public function applyCouponToCart($couponCode, $customerId = null) {
        try {
            // Get cart total
            $cartItems = $this->cart->getCartItems($_SESSION['cart_id'] ?? null);
            $cartTotal = $this->calculateCartTotal($cartItems);
            
            // Validate coupon
            $validation = $this->coupon->validateCoupon($couponCode, $customerId, $cartTotal, $cartItems);
            
            if (!$validation['valid']) {
                return [
                    'success' => false,
                    'message' => $validation['message']
                ];
            }
            
            // Store coupon in session
            $_SESSION['applied_coupon'] = [
                'id' => $validation['coupon']['id'],
                'code' => $validation['coupon']['code'],
                'name' => $validation['coupon']['name'],
                'discount' => $validation['discount'],
                'free_shipping' => $validation['coupon']['free_shipping']
            ];
            
            return [
                'success' => true,
                'message' => 'Cupón aplicado exitosamente',
                'discount' => $validation['discount'],
                'free_shipping' => $validation['coupon']['free_shipping'],
                'new_total' => $cartTotal - $validation['discount']
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Error al aplicar el cupón: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Remove coupon from cart
     */
    public function removeCouponFromCart() {
        unset($_SESSION['applied_coupon']);
        return [
            'success' => true,
            'message' => 'Cupón removido del carrito'
        ];
    }
    
    /**
     * Get available coupons for customer
     */
    public function getAvailableCoupons($customerId) {
        return $this->coupon->getCustomerCoupons($customerId);
    }
    
    /**
     * Calculate cart total
     */
    private function calculateCartTotal($cartItems) {
        $total = 0;
        foreach ($cartItems as $item) {
            $total += $item['price'] * $item['quantity'];
        }
        return $total;
    }
    
    /**
     * Get cart summary with coupon applied
     */
    public function getCartSummary($customerId = null) {
        $cartItems = $this->cart->getCartItems($_SESSION['cart_id'] ?? null);
        $subtotal = $this->calculateCartTotal($cartItems);
        $shipping = 10.00; // Default shipping cost
        $discount = 0;
        $freeShipping = false;
        
        // Apply coupon if exists
        if (isset($_SESSION['applied_coupon'])) {
            $discount = $_SESSION['applied_coupon']['discount'];
            $freeShipping = $_SESSION['applied_coupon']['free_shipping'];
        }
        
        if ($freeShipping) {
            $shipping = 0;
        }
        
        $total = $subtotal - $discount + $shipping;
        
        return [
            'items' => $cartItems,
            'subtotal' => $subtotal,
            'discount' => $discount,
            'shipping' => $shipping,
            'free_shipping' => $freeShipping,
            'total' => $total,
            'applied_coupon' => $_SESSION['applied_coupon'] ?? null,
            'available_coupons' => $customerId ? $this->getAvailableCoupons($customerId) : []
        ];
    }
}

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    $integration = new CouponIntegration();
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'apply_coupon':
            $result = $integration->applyCouponToCart(
                $_POST['coupon_code'],
                $_SESSION['customer_id'] ?? null
            );
            echo json_encode($result);
            exit;
            
        case 'remove_coupon':
            $result = $integration->removeCouponFromCart();
            echo json_encode($result);
            exit;
            
        case 'get_cart_summary':
            $summary = $integration->getCartSummary($_SESSION['customer_id'] ?? null);
            echo json_encode($summary);
            exit;
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Carrito de Compras - Odisea Makeup Store</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>

<div class="container mt-5">
    <h2>Carrito de Compras</h2>
    
    <div class="row">
        <div class="col-md-8">
            <!-- Cart Items -->
            <div class="card">
                <div class="card-header">
                    <h5>Productos en tu carrito</h5>
                </div>
                <div class="card-body" id="cartItems">
                    <!-- Cart items will be loaded here -->
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <!-- Coupon Section -->
            <div class="card mb-3">
                <div class="card-header">
                    <h6><i class="fas fa-ticket-alt"></i> Código de Descuento</h6>
                </div>
                <div class="card-body">
                    <div id="couponSection">
                        <div class="input-group">
                            <input type="text" class="form-control" id="couponCode" placeholder="Ingresa tu código">
                            <button class="btn btn-primary" type="button" onclick="applyCoupon()">
                                <i class="fas fa-check"></i> Aplicar
                            </button>
                        </div>
                        <small class="text-muted">¿Tienes un código de descuento? Ingrésalo aquí.</small>
                    </div>
                    
                    <div id="appliedCoupon" style="display: none;">
                        <div class="alert alert-success d-flex justify-content-between align-items-center">
                            <div>
                                <strong id="appliedCouponName"></strong><br>
                                <small>Descuento: $<span id="appliedCouponDiscount"></span></small>
                            </div>
                            <button class="btn btn-sm btn-outline-danger" onclick="removeCoupon()">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Available Coupons -->
            <div class="card mb-3" id="availableCoupons" style="display: none;">
                <div class="card-header">
                    <h6><i class="fas fa-gift"></i> Cupones Disponibles</h6>
                </div>
                <div class="card-body">
                    <div id="couponsList">
                        <!-- Available coupons will be loaded here -->
                    </div>
                </div>
            </div>
            
            <!-- Order Summary -->
            <div class="card">
                <div class="card-header">
                    <h6><i class="fas fa-calculator"></i> Resumen del Pedido</h6>
                </div>
                <div class="card-body">
                    <table class="table table-sm">
                        <tr>
                            <td>Subtotal:</td>
                            <td class="text-end">$<span id="subtotal">0.00</span></td>
                        </tr>
                        <tr id="discountRow" style="display: none;">
                            <td class="text-success">Descuento:</td>
                            <td class="text-end text-success">-$<span id="discount">0.00</span></td>
                        </tr>
                        <tr id="shippingRow">
                            <td>Envío:</td>
                            <td class="text-end">
                                <span id="shippingCost">$10.00</span>
                                <span id="freeShippingLabel" class="text-success" style="display: none;">GRATIS</span>
                            </td>
                        </tr>
                        <tr class="table-dark">
                            <td><strong>Total:</strong></td>
                            <td class="text-end"><strong>$<span id="total">0.00</span></strong></td>
                        </tr>
                    </table>
                    
                    <button class="btn btn-success w-100 mt-3">
                        <i class="fas fa-shopping-cart"></i> Proceder al Checkout
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
class CartManager {
    constructor() {
        this.loadCartSummary();
    }
    
    async loadCartSummary() {
        try {
            const response = await fetch('', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'action=get_cart_summary'
            });
            
            const data = await response.json();
            this.updateCartDisplay(data);
        } catch (error) {
            console.error('Error loading cart:', error);
        }
    }
    
    updateCartDisplay(data) {
        // Update summary
        document.getElementById('subtotal').textContent = data.subtotal.toFixed(2);
        document.getElementById('total').textContent = data.total.toFixed(2);
        
        // Update discount
        if (data.discount > 0) {
            document.getElementById('discount').textContent = data.discount.toFixed(2);
            document.getElementById('discountRow').style.display = 'table-row';
        } else {
            document.getElementById('discountRow').style.display = 'none';
        }
        
        // Update shipping
        if (data.free_shipping) {
            document.getElementById('shippingCost').style.display = 'none';
            document.getElementById('freeShippingLabel').style.display = 'inline';
        } else {
            document.getElementById('shippingCost').style.display = 'inline';
            document.getElementById('shippingCost').textContent = '$' + data.shipping.toFixed(2);
            document.getElementById('freeShippingLabel').style.display = 'none';
        }
        
        // Update applied coupon
        if (data.applied_coupon) {
            this.showAppliedCoupon(data.applied_coupon);
        } else {
            this.hideCouponForm();
        }
        
        // Show available coupons
        if (data.available_coupons && data.available_coupons.length > 0) {
            this.showAvailableCoupons(data.available_coupons);
        }
    }
    
    showAppliedCoupon(coupon) {
        document.getElementById('couponSection').style.display = 'none';
        document.getElementById('appliedCoupon').style.display = 'block';
        document.getElementById('appliedCouponName').textContent = coupon.name;
        document.getElementById('appliedCouponDiscount').textContent = coupon.discount.toFixed(2);
    }
    
    hideCouponForm() {
        document.getElementById('couponSection').style.display = 'block';
        document.getElementById('appliedCoupon').style.display = 'none';
        document.getElementById('couponCode').value = '';
    }
    
    showAvailableCoupons(coupons) {
        const container = document.getElementById('couponsList');
        container.innerHTML = '';
        
        coupons.forEach(coupon => {
            const couponEl = document.createElement('div');
            couponEl.className = 'border rounded p-2 mb-2';
            couponEl.innerHTML = `
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <strong>${coupon.code}</strong><br>
                        <small class="text-muted">${coupon.name}</small>
                    </div>
                    <button class="btn btn-sm btn-outline-primary" onclick="applyCouponCode('${coupon.code}')">
                        Usar
                    </button>
                </div>
            `;
            container.appendChild(couponEl);
        });
        
        document.getElementById('availableCoupons').style.display = 'block';
    }
    
    async applyCoupon(code) {
        try {
            const response = await fetch('', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: `action=apply_coupon&coupon_code=${encodeURIComponent(code)}`
            });
            
            const result = await response.json();
            
            if (result.success) {
                this.showAlert(result.message, 'success');
                this.loadCartSummary();
            } else {
                this.showAlert(result.message, 'error');
            }
        } catch (error) {
            console.error('Error applying coupon:', error);
            this.showAlert('Error al aplicar el cupón', 'error');
        }
    }
    
    async removeCoupon() {
        try {
            const response = await fetch('', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'action=remove_coupon'
            });
            
            const result = await response.json();
            
            if (result.success) {
                this.showAlert(result.message, 'success');
                this.loadCartSummary();
            }
        } catch (error) {
            console.error('Error removing coupon:', error);
        }
    }
    
    showAlert(message, type) {
        const alertDiv = document.createElement('div');
        alertDiv.className = `alert alert-${type === 'error' ? 'danger' : 'success'} alert-dismissible fade show position-fixed`;
        alertDiv.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
        alertDiv.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        
        document.body.appendChild(alertDiv);
        
        setTimeout(() => {
            if (alertDiv.parentNode) {
                alertDiv.remove();
            }
        }, 5000);
    }
}

// Initialize cart manager
const cartManager = new CartManager();

// Global functions
function applyCoupon() {
    const code = document.getElementById('couponCode').value.trim();
    if (code) {
        cartManager.applyCoupon(code);
    }
}

function applyCouponCode(code) {
    cartManager.applyCoupon(code);
}

function removeCoupon() {
    cartManager.removeCoupon();
}

// Allow Enter key to apply coupon
document.getElementById('couponCode').addEventListener('keypress', function(e) {
    if (e.key === 'Enter') {
        applyCoupon();
    }
});
</script>

</body>
</html>
