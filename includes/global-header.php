<?php
// Incluir configuraciones globales
require_once __DIR__ . '/../config/global-settings.php';

// Obtener información de contacto y redes sociales
$contactInfo = getContactInfo();
$socialNetworks = getSocialSettings();
?>

<!-- Top Bar -->
<div class="bg-gradient-to-r from-primary-500 to-secondary-500 text-white py-2 text-sm">
    <div class="container mx-auto px-4">
        <div class="flex justify-between items-center">
            <div class="flex items-center space-x-6">
                <?php if (!empty($contactInfo['phone'])): ?>
                <a href="tel:<?php echo str_replace(' ', '', $contactInfo['phone']); ?>" class="flex items-center space-x-2 hover:text-primary-200 transition-colors">
                    <i class="fas fa-phone text-xs"></i>
                    <span><?php echo htmlspecialchars($contactInfo['phone']); ?></span>
                </a>
                <?php endif; ?>
                
                <?php if (!empty($contactInfo['email'])): ?>
                <a href="mailto:<?php echo htmlspecialchars($contactInfo['email']); ?>" class="flex items-center space-x-2 hover:text-primary-200 transition-colors">
                    <i class="fas fa-envelope text-xs"></i>
                    <span><?php echo htmlspecialchars($contactInfo['email']); ?></span>
                </a>
                <?php endif; ?>
            </div>
            <div class="flex items-center space-x-4">
                <span class="text-xs">Síguenos:</span>
                
                <?php if (!empty($socialNetworks['instagram'])): ?>
                <a href="<?php echo htmlspecialchars($socialNetworks['instagram']); ?>" target="_blank" class="hover:text-primary-200 transition-colors transform hover:scale-110" title="Instagram">
                    <i class="fab fa-instagram"></i>
                </a>
                <?php endif; ?>
                
                <?php if (!empty($socialNetworks['facebook'])): ?>
                <a href="<?php echo htmlspecialchars($socialNetworks['facebook']); ?>" target="_blank" class="hover:text-primary-200 transition-colors transform hover:scale-110" title="Facebook">
                    <i class="fab fa-facebook"></i>
                </a>
                <?php endif; ?>
                
                <?php if (!empty($socialNetworks['tiktok'])): ?>
                <a href="<?php echo htmlspecialchars($socialNetworks['tiktok']); ?>" target="_blank" class="hover:text-primary-200 transition-colors transform hover:scale-110" title="TikTok">
                    <i class="fab fa-tiktok"></i>
                </a>
                <?php endif; ?>
                
                <?php if (!empty($socialNetworks['youtube'])): ?>
                <a href="<?php echo htmlspecialchars($socialNetworks['youtube']); ?>" target="_blank" class="hover:text-primary-200 transition-colors transform hover:scale-110" title="YouTube">
                    <i class="fab fa-youtube"></i>
                </a>
                <?php endif; ?>
                
                <?php if (!empty($socialNetworks['twitter'])): ?>
                <a href="<?php echo htmlspecialchars($socialNetworks['twitter']); ?>" target="_blank" class="hover:text-primary-200 transition-colors transform hover:scale-110" title="Twitter">
                    <i class="fab fa-twitter"></i>
                </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Main Header -->
<div class="glass-effect backdrop-blur-md">
    <div class="container mx-auto px-4 py-4">
        <div class="flex items-center justify-between">
            <!-- Logo -->
            <div class="flex items-center">
                <a href="<?php echo BASE_URL; ?>" class="text-3xl font-serif font-bold gradient-text">
                    <?php echo SITE_NAME; ?>
                </a>
                <span class="ml-2 text-xs text-gray-500 font-light">TECH</span>
            </div>
            
            <!-- Navigation -->
            <nav class="hidden lg:flex items-center space-x-8">
                <a href="<?php echo BASE_URL; ?>" class="nav-link font-medium text-gray-700 hover:text-primary-500 transition-colors">
                    Inicio
                </a>
                <a href="<?php echo BASE_URL; ?>/catalogo.php" class="nav-link font-medium text-gray-700 hover:text-primary-500 transition-colors">
                    Catálogo
                </a>
                <a href="<?php echo BASE_URL; ?>/ofertas.php" class="nav-link font-medium text-gray-700 hover:text-primary-500 transition-colors">
                    Ofertas
                </a>
                <a href="<?php echo BASE_URL; ?>/mi-cuenta.php" class="nav-link font-medium text-gray-700 hover:text-primary-500 transition-colors">
                    Mi Cuenta
                </a>
            </nav>
            
            <!-- User Actions -->
            <div class="flex items-center space-x-4">
                <!-- Global Search Component -->
                <?php include __DIR__ . '/global-search.php'; ?>
                
                <!-- Cart -->
                <a href="#" class="relative p-2 text-gray-600 hover:text-primary-500 transition-colors">
                    <i class="fas fa-shopping-bag text-lg"></i>
                    <span class="absolute -top-1 -right-1 bg-primary-500 text-white text-xs w-5 h-5 rounded-full flex items-center justify-center">
                        0
                    </span>
                </a>
                
                <!-- Mobile Menu -->
                <button class="lg:hidden p-2 text-gray-600 hover:text-primary-500 transition-colors">
                    <i class="fas fa-bars text-lg"></i>
                </button>
            </div>
        </div>
    </div>
</div>
