<!-- Footer -->
<footer class="bg-gray-900 text-white">
    <div class="container mx-auto px-4 py-16">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
            <!-- Company Info -->
            <div class="space-y-6">
                <div>
                    <h3 class="text-2xl font-bold bg-gradient-to-r from-primary-400 to-secondary-400 bg-clip-text text-transparent mb-4">
                        ElectroShop
                    </h3>
                    <p class="text-gray-300 leading-relaxed">
                        Tu tienda de tecnología y productos electrónicos. Descubre las mejores marcas y dispositivos con la calidad que mereces.
                    </p>
                </div>
                
                <div class="space-y-3">
                    <div class="flex items-center space-x-3">
                        <i class="fas fa-map-marker-alt text-primary-400"></i>
                        <span class="text-gray-300">Barranquilla, Colombia</span>
                    </div>
                    <div class="flex items-center space-x-3">
                        <i class="fas fa-phone text-primary-400"></i>
                        <span class="text-gray-300">+57 300 123 4567</span>
                    </div>
                    <div class="flex items-center space-x-3">
                        <i class="fas fa-envelope text-primary-400"></i>
                        <span class="text-gray-300">contacto@electroshop.com</span>
                    </div>
                </div>
            </div>

            <!-- Quick Links -->
            <div>
                <h4 class="text-lg font-semibold mb-6">Enlaces Rápidos</h4>
                <ul class="space-y-3">
                    <li><a href="sobre-nosotros.php" class="text-gray-300 hover:text-primary-400 transition-colors duration-300">Sobre Nosotros</a></li>
                    <li><a href="productos.php" class="text-gray-300 hover:text-primary-400 transition-colors duration-300">Catálogo</a></li>
                    <li><a href="ofertas.php" class="text-gray-300 hover:text-primary-400 transition-colors duration-300">Ofertas</a></li>
                    <li><a href="blog.php" class="text-gray-300 hover:text-primary-400 transition-colors duration-300">Blog</a></li>
                    <li><a href="contacto.php" class="text-gray-300 hover:text-primary-400 transition-colors duration-300">Contacto</a></li>
                </ul>
            </div>

            <!-- Customer Service -->
            <div>
                <h4 class="text-lg font-semibold mb-6">Atención al Cliente</h4>
                <ul class="space-y-3">
                    <li><a href="mi-cuenta.php" class="text-gray-300 hover:text-primary-400 transition-colors duration-300">Mi Cuenta</a></li>
                    <li><a href="mis-pedidos.php" class="text-gray-300 hover:text-primary-400 transition-colors duration-300">Mis Pedidos</a></li>
                    <li><a href="envios-devoluciones.php" class="text-gray-300 hover:text-primary-400 transition-colors duration-300">Envíos y Devoluciones</a></li>
                    <li><a href="faq.php" class="text-gray-300 hover:text-primary-400 transition-colors duration-300">Preguntas Frecuentes</a></li>
                    <li><a href="terminos.php" class="text-gray-300 hover:text-primary-400 transition-colors duration-300">Términos y Condiciones</a></li>
                </ul>
            </div>

            <!-- Social & Payment -->
            <div>
                <h4 class="text-lg font-semibold mb-6">Síguenos</h4>
                <div class="flex space-x-4 mb-8">
                    <a href="https://instagram.com/electroshop" target="_blank" class="w-10 h-10 bg-primary-500 rounded-full flex items-center justify-center hover:bg-primary-600 transition-colors duration-300">
                        <i class="fab fa-instagram"></i>
                    </a>
                    <a href="https://facebook.com/electroshop" target="_blank" class="w-10 h-10 bg-primary-500 rounded-full flex items-center justify-center hover:bg-primary-600 transition-colors duration-300">
                        <i class="fab fa-facebook"></i>
                    </a>
                    <a href="https://tiktok.com/@electroshop" target="_blank" class="w-10 h-10 bg-primary-500 rounded-full flex items-center justify-center hover:bg-primary-600 transition-colors duration-300">
                        <i class="fab fa-tiktok"></i>
                    </a>
                    <a href="https://youtube.com/electroshop" target="_blank" class="w-10 h-10 bg-primary-500 rounded-full flex items-center justify-center hover:bg-primary-600 transition-colors duration-300">
                        <i class="fab fa-youtube"></i>
                    </a>
                </div>
                
                <h5 class="font-semibold mb-4">Métodos de Pago</h5>
                <div class="grid grid-cols-3 gap-2">
                    <div class="bg-white rounded p-2 flex items-center justify-center">
                        <i class="fab fa-cc-visa text-blue-600 text-xl"></i>
                    </div>
                    <div class="bg-white rounded p-2 flex items-center justify-center">
                        <i class="fab fa-cc-mastercard text-red-600 text-xl"></i>
                    </div>
                    <div class="bg-white rounded p-2 flex items-center justify-center">
                        <i class="fab fa-paypal text-blue-500 text-xl"></i>
                    </div>
                    <div class="bg-white rounded p-2 flex items-center justify-center text-xs font-bold text-gray-700">
                        NEQUI
                    </div>
                    <div class="bg-white rounded p-2 flex items-center justify-center text-xs font-bold text-gray-700">
                        DAVI
                    </div>
                    <div class="bg-white rounded p-2 flex items-center justify-center text-xs font-bold text-gray-700">
                        BANCO
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Bottom Footer -->
        <div class="border-t border-gray-800 mt-12 pt-8">
            <div class="flex flex-col md:flex-row justify-between items-center">
                <p class="text-gray-400 text-sm">
                    © <?php echo date('Y'); ?> ElectroShop. Todos los derechos reservados.
                </p>
                <div class="flex space-x-6 mt-4 md:mt-0">
                    <a href="privacidad.php" class="text-gray-400 hover:text-primary-400 text-sm transition-colors duration-300">Política de Privacidad</a>
                    <a href="terminos.php" class="text-gray-400 hover:text-primary-400 text-sm transition-colors duration-300">Términos de Uso</a>
                    <a href="cookies.php" class="text-gray-400 hover:text-primary-400 text-sm transition-colors duration-300">Cookies</a>
                </div>
            </div>
        </div>
    </div>
</footer>

<!-- WhatsApp Floating Button -->
<div class="fixed bottom-6 right-6 z-40">
    <a href="https://wa.me/573001234567?text=Hola,%20me%20interesa%20conocer%20más%20sobre%20sus%20productos" 
       target="_blank" 
       class="w-14 h-14 bg-green-500 rounded-full flex items-center justify-center text-white shadow-lg hover:bg-green-600 hover:shadow-xl transform hover:scale-110 transition-all duration-300 animate-bounce-slow">
        <i class="fab fa-whatsapp text-2xl"></i>
    </a>
</div>

<!-- Back to Top Button -->
<button class="fixed bottom-6 left-6 w-12 h-12 bg-primary-500 text-white rounded-full flex items-center justify-center shadow-lg hover:bg-primary-600 hover:shadow-xl transform hover:scale-110 transition-all duration-300 opacity-0 invisible" id="back-to-top">
    <i class="fas fa-chevron-up"></i>
</button>

<!-- Global Search Script -->
<script src="assets/js/global-search.js"></script>
