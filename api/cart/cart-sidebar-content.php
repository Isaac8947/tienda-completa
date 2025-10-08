<?php
session_start();

$cart = $_SESSION['cart'] ?? [];
$subtotal = 0;

if (empty($cart)): ?>
    <!-- Empty Cart -->
    <div class="text-center py-16" id="empty-cart">
        <div class="w-24 h-24 bg-gradient-to-r from-primary-100 to-secondary-100 rounded-full flex items-center justify-center mx-auto mb-6">
            <i class="fas fa-shopping-bag text-3xl text-primary-500"></i>
        </div>
        <h3 class="text-xl font-semibold text-gray-900 mb-3">Tu carrito está vacío</h3>
        <p class="text-gray-600 mb-8 font-light">Agrega algunos productos para comenzar</p>
        <button class="bg-gradient-to-r from-primary-500 to-secondary-500 text-white px-8 py-4 rounded-2xl font-semibold hover:shadow-lg transform hover:scale-105 transition-all duration-300" id="continue-shopping">
            Continuar Comprando
        </button>
    </div>
<?php else: ?>
    <!-- Cart Items List -->
    <div class="space-y-6" id="cart-items-list">
        <?php 
        foreach ($cart as $item):
            $subtotal += $item['price'] * $item['quantity'];
            $productImage = !empty($item['image']) ? 'uploads/products/' . $item['image'] : 'assets/images/placeholder-product.svg';
        ?>
        <!-- Cart Item -->
        <div class="flex items-center space-x-4 p-4 bg-gray-50 rounded-2xl hover:bg-gray-100 transition-colors duration-300">
            <img src="<?php echo htmlspecialchars($productImage); ?>" 
                 alt="<?php echo htmlspecialchars($item['name']); ?>" 
                 class="w-16 h-16 object-cover rounded-xl" loading="lazy">
            <div class="flex-1">
                <h4 class="font-semibold text-gray-900"><?php echo htmlspecialchars($item['name']); ?></h4>
                <?php if (isset($item['variant']) && $item['variant']): ?>
                <p class="text-sm text-gray-600"><?php echo htmlspecialchars($item['variant']); ?></p>
                <?php endif; ?>
                <div class="flex items-center justify-between mt-3">
                    <div class="flex items-center space-x-3">
                        <button class="w-8 h-8 bg-white border border-gray-300 rounded-full flex items-center justify-center hover:bg-primary-500 hover:text-white hover:border-primary-500 transition-all duration-300" 
                                onclick="updateCartQuantity(<?php echo $item['id']; ?>, -1)">
                            <i class="fas fa-minus text-xs"></i>
                        </button>
                        <span class="font-semibold text-lg"><?php echo $item['quantity']; ?></span>
                        <button class="w-8 h-8 bg-white border border-gray-300 rounded-full flex items-center justify-center hover:bg-primary-500 hover:text-white hover:border-primary-500 transition-all duration-300" 
                                onclick="updateCartQuantity(<?php echo $item['id']; ?>, 1)">
                            <i class="fas fa-plus text-xs"></i>
                        </button>
                    </div>
                    <div class="text-right">
                        <?php if (!empty($item['compare_price']) && $item['compare_price'] > $item['price']): ?>
                        <div class="flex items-center space-x-2">
                            <span class="text-sm text-gray-500 line-through">$<?php echo number_format($item['compare_price'], 0, ',', '.'); ?></span>
                            <span class="bg-red-500 text-white text-xs px-2 py-1 rounded-full">-<?php echo $item['discount_percentage']; ?>%</span>
                        </div>
                        <?php endif; ?>
                        <span class="font-bold text-primary-500 text-lg">$<?php echo number_format($item['price'], 0, ',', '.'); ?></span>
                    </div>
                </div>
            </div>
            <button class="w-10 h-10 bg-red-50 rounded-full flex items-center justify-center text-red-400 hover:text-red-600 hover:bg-red-100 transition-all duration-300" 
                    onclick="removeFromCart(<?php echo $item['id']; ?>)">
                <i class="fas fa-trash text-sm"></i>
            </button>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Update cart summary -->
    <script>
        // Update cart summary
        const cartSummary = document.getElementById('cart-summary');
        if (cartSummary) {
            const subtotal = <?php echo $subtotal; ?>;
            const shipping = 15000;
            const tax = Math.round(subtotal * 0.19);
            const total = subtotal + shipping + tax;
            
            cartSummary.innerHTML = `
                <div class="flex justify-between text-gray-600">
                    <span>Subtotal:</span>
                    <span class="font-semibold">$${subtotal.toLocaleString('es-CO')}</span>
                </div>
                <div class="flex justify-between text-gray-600">
                    <span>Envío:</span>
                    <span class="font-semibold">$${shipping.toLocaleString('es-CO')}</span>
                </div>
                <div class="flex justify-between text-gray-600">
                    <span>IVA:</span>
                    <span class="font-semibold">$${tax.toLocaleString('es-CO')}</span>
                </div>
                <hr class="my-4">
                <div class="flex justify-between text-xl font-bold">
                    <span>Total:</span>
                    <span class="text-primary-500">$${total.toLocaleString('es-CO')}</span>
                </div>
            `;
        }
    </script>
<?php endif; ?>
