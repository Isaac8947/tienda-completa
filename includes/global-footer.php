<?php
// Incluir configuraciones globales si no están cargadas
if (!defined('GLOBAL_SETTINGS_LOADED')) {
    require_once __DIR__ . '/../config/global-settings.php';
}

// Obtener información de contacto y redes sociales
$contactInfo = getContactInfo();
$socialNetworks = getSocialSettings();
?>

<!-- Footer -->
<footer class="bg-gray-900 text-white relative overflow-hidden">
    <div class="absolute inset-0 bg-gradient-to-br from-gray-900 via-gray-800 to-gray-900"></div>
    
    <div class="container mx-auto px-4 py-20 relative z-10">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-12">
            <!-- Company Info -->
            <div>
                <h3 class="text-2xl font-serif font-bold gradient-text mb-6"><?php echo SITE_NAME; ?></h3>
                <p class="text-gray-300 mb-8 leading-relaxed"><?php echo SITE_DESCRIPTION; ?></p>
                
                <div class="space-y-4">
                    <div class="flex items-center space-x-4 group">
                        <div class="w-12 h-12 bg-gradient-to-r from-primary-500 to-secondary-500 rounded-xl flex items-center justify-center group-hover:scale-110 transition-transform duration-300">
                            <i class="fas fa-map-marker-alt"></i>
                        </div>
                        <span class="text-gray-300"><?php echo htmlspecialchars($contactInfo['address']); ?></span>
                    </div>
                    
                    <?php if (!empty($contactInfo['phone'])): ?>
                    <div class="flex items-center space-x-4 group">
                        <div class="w-12 h-12 bg-gradient-to-r from-primary-500 to-secondary-500 rounded-xl flex items-center justify-center group-hover:scale-110 transition-transform duration-300">
                            <i class="fas fa-phone"></i>
                        </div>
                        <span class="text-gray-300"><?php echo htmlspecialchars($contactInfo['phone']); ?></span>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($contactInfo['email'])): ?>
                    <div class="flex items-center space-x-4 group">
                        <div class="w-12 h-12 bg-gradient-to-r from-primary-500 to-secondary-500 rounded-xl flex items-center justify-center group-hover:scale-110 transition-transform duration-300">
                            <i class="fas fa-envelope"></i>
                        </div>
                        <span class="text-gray-300"><?php echo htmlspecialchars($contactInfo['email']); ?></span>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Quick Links -->
            <div>
                <h4 class="text-xl font-semibold mb-8 text-white">Enlaces Rápidos</h4>
                <ul class="space-y-4">
                    <li><a href="<?php echo BASE_URL; ?>" class="text-gray-300 hover:text-primary-400 transition-colors duration-300 flex items-center"><i class="fas fa-chevron-right mr-2 text-xs"></i> Inicio</a></li>
                    <li><a href="<?php echo BASE_URL; ?>/catalogo.php" class="text-gray-300 hover:text-primary-400 transition-colors duration-300 flex items-center"><i class="fas fa-chevron-right mr-2 text-xs"></i> Catálogo</a></li>
                    <li><a href="<?php echo BASE_URL; ?>/ofertas.php" class="text-gray-300 hover:text-primary-400 transition-colors duration-300 flex items-center"><i class="fas fa-chevron-right mr-2 text-xs"></i> Ofertas</a></li>
                    <li><a href="<?php echo BASE_URL; ?>/mi-cuenta.php" class="text-gray-300 hover:text-primary-400 transition-colors duration-300 flex items-center"><i class="fas fa-chevron-right mr-2 text-xs"></i> Mi Cuenta</a></li>
                    <li><a href="#" class="text-gray-300 hover:text-primary-400 transition-colors duration-300 flex items-center"><i class="fas fa-chevron-right mr-2 text-xs"></i> Contacto</a></li>
                </ul>
            </div>
            
            <!-- Categories -->
            <div>
                <h4 class="text-xl font-semibold mb-8 text-white">Categorías</h4>
                <ul class="space-y-4">
                    <li><a href="#" class="text-gray-300 hover:text-primary-400 transition-colors duration-300 flex items-center"><i class="fas fa-chevron-right mr-2 text-xs"></i> Labiales</a></li>
                    <li><a href="#" class="text-gray-300 hover:text-primary-400 transition-colors duration-300 flex items-center"><i class="fas fa-chevron-right mr-2 text-xs"></i> Bases</a></li>
                    <li><a href="#" class="text-gray-300 hover:text-primary-400 transition-colors duration-300 flex items-center"><i class="fas fa-chevron-right mr-2 text-xs"></i> Sombras</a></li>
                    <li><a href="#" class="text-gray-300 hover:text-primary-400 transition-colors duration-300 flex items-center"><i class="fas fa-chevron-right mr-2 text-xs"></i> Máscaras</a></li>
                    <li><a href="#" class="text-gray-300 hover:text-primary-400 transition-colors duration-300 flex items-center"><i class="fas fa-chevron-right mr-2 text-xs"></i> Cuidado Facial</a></li>
                </ul>
            </div>
            
            <!-- Social & Payment -->
            <div>
                <h4 class="text-xl font-semibold mb-8 text-white">Síguenos</h4>
                <div class="flex space-x-4 mb-10">
                    <?php if (!empty($socialNetworks['instagram'])): ?>
                    <a href="<?php echo htmlspecialchars($socialNetworks['instagram']); ?>" target="_blank" 
                       class="w-12 h-12 bg-gradient-to-r from-primary-500 to-secondary-500 rounded-xl flex items-center justify-center hover:scale-110 transition-all duration-300 shadow-lg"
                       title="Instagram">
                        <i class="fab fa-instagram text-lg"></i>
                    </a>
                    <?php endif; ?>
                    
                    <?php if (!empty($socialNetworks['facebook'])): ?>
                    <a href="<?php echo htmlspecialchars($socialNetworks['facebook']); ?>" target="_blank" 
                       class="w-12 h-12 bg-gradient-to-r from-primary-500 to-secondary-500 rounded-xl flex items-center justify-center hover:scale-110 transition-all duration-300 shadow-lg"
                       title="Facebook">
                        <i class="fab fa-facebook text-lg"></i>
                    </a>
                    <?php endif; ?>
                    
                    <?php if (!empty($socialNetworks['tiktok'])): ?>
                    <a href="<?php echo htmlspecialchars($socialNetworks['tiktok']); ?>" target="_blank" 
                       class="w-12 h-12 bg-gradient-to-r from-primary-500 to-secondary-500 rounded-xl flex items-center justify-center hover:scale-110 transition-all duration-300 shadow-lg"
                       title="TikTok">
                        <i class="fab fa-tiktok text-lg"></i>
                    </a>
                    <?php endif; ?>
                    
                    <?php if (!empty($socialNetworks['youtube'])): ?>
                    <a href="<?php echo htmlspecialchars($socialNetworks['youtube']); ?>" target="_blank" 
                       class="w-12 h-12 bg-gradient-to-r from-primary-500 to-secondary-500 rounded-xl flex items-center justify-center hover:scale-110 transition-all duration-300 shadow-lg"
                       title="YouTube">
                        <i class="fab fa-youtube text-lg"></i>
                    </a>
                    <?php endif; ?>
                    
                    <?php if (!empty($socialNetworks['twitter'])): ?>
                    <a href="<?php echo htmlspecialchars($socialNetworks['twitter']); ?>" target="_blank" 
                       class="w-12 h-12 bg-gradient-to-r from-primary-500 to-secondary-500 rounded-xl flex items-center justify-center hover:scale-110 transition-all duration-300 shadow-lg"
                       title="Twitter">
                        <i class="fab fa-twitter text-lg"></i>
                    </a>
                    <?php endif; ?>
                </div>
                
                <h5 class="font-semibold mb-6 text-white">Métodos de Pago</h5>
                <div class="flex space-x-3">
                    <div class="w-12 h-8 bg-white rounded flex items-center justify-center">
                        <i class="fab fa-cc-visa text-blue-600 text-lg"></i>
                    </div>
                    <div class="w-12 h-8 bg-white rounded flex items-center justify-center">
                        <i class="fab fa-cc-mastercard text-red-500 text-lg"></i>
                    </div>
                    <div class="w-12 h-8 bg-white rounded flex items-center justify-center">
                        <i class="fab fa-paypal text-blue-500 text-lg"></i>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Newsletter -->
        <div class="border-t border-gray-700 mt-16 pt-12">
            <div class="text-center max-w-2xl mx-auto">
                <h3 class="text-2xl font-bold text-white mb-4">¡Mantente al día!</h3>
                <p class="text-gray-300 mb-8">Suscríbete a nuestro newsletter y recibe ofertas exclusivas, tips de belleza y las últimas tendencias en maquillaje.</p>
                
                <form class="flex flex-col sm:flex-row gap-4 max-w-md mx-auto">
                    <input type="email" placeholder="Tu email" 
                           class="flex-1 px-4 py-3 rounded-lg bg-gray-800 border border-gray-600 text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                    <button type="submit" 
                            class="bg-gradient-to-r from-primary-500 to-secondary-500 text-white px-8 py-3 rounded-lg font-semibold hover:shadow-lg transform hover:scale-105 transition-all duration-300">
                        Suscribirse
                    </button>
                </form>
            </div>
        </div>
        
        <!-- Copyright -->
        <div class="border-t border-gray-700 mt-16 pt-8 text-center">
            <p class="text-gray-400">
                &copy; <?php echo date('Y'); ?> <?php echo SITE_NAME; ?>. Todos los derechos reservados. 
                <span class="text-primary-400">Hecho con ❤️ en Colombia</span>
            </p>
        </div>
    </div>
</footer>

<!-- WhatsApp Floating Button -->
<?php if (!empty($contactInfo['whatsapp_number'])): ?>
<div class="fixed bottom-8 right-8 z-40">
    <a href="https://wa.me/<?php echo $contactInfo['whatsapp_number']; ?>?text=Hola,%20me%20interesa%20conocer%20más%20sobre%20sus%20productos"
       target="_blank"
       class="w-16 h-16 bg-gradient-to-r from-green-500 to-green-600 rounded-full flex items-center justify-center text-white shadow-2xl hover:shadow-3xl transform hover:scale-110 transition-all duration-300 animate-bounce">
        <i class="fab fa-whatsapp text-2xl"></i>
    </a>
</div>
<?php endif; ?>
