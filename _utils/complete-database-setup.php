<?php
// Script para completar la configuración de la base de datos
require_once 'config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    echo "🔧 Configurando datos adicionales...\n\n";
    
    // 1. Configuraciones del sitio
    $settings = [
        ['key_name' => 'site_name', 'value' => 'OdiseaStore', 'group_name' => 'general'],
        ['key_name' => 'site_description', 'value' => 'Tu tienda de maquillaje y belleza online', 'group_name' => 'general'],
        ['key_name' => 'contact_email', 'value' => 'info@odiseamakeup.com', 'group_name' => 'contact'],
        ['key_name' => 'contact_phone', 'value' => '+57 300 123 4567', 'group_name' => 'contact'],
        ['key_name' => 'shipping_cost', 'value' => '15000', 'group_name' => 'shipping'],
        ['key_name' => 'free_shipping_min', 'value' => '150000', 'group_name' => 'shipping'],
        ['key_name' => 'currency', 'value' => 'COP', 'group_name' => 'general'],
        ['key_name' => 'tax_rate', 'value' => '19', 'group_name' => 'general'],
        ['key_name' => 'featured_products_limit', 'value' => '8', 'group_name' => 'display'],
        ['key_name' => 'products_per_page', 'value' => '12', 'group_name' => 'display']
    ];
    
    foreach ($settings as $setting) {
        $sql = "INSERT INTO settings (key_name, value, group_name) VALUES (?, ?, ?) 
                ON DUPLICATE KEY UPDATE value = VALUES(value)";
        $stmt = $db->prepare($sql);
        $stmt->execute([$setting['key_name'], $setting['value'], $setting['group_name']]);
    }
    echo "✅ Configuraciones del sitio creadas\n";
    
    // 2. Crear un administrador por defecto
    $adminExists = $db->query("SELECT COUNT(*) FROM admins")->fetchColumn();
    if ($adminExists == 0) {
        $adminData = [
            'username' => 'admin',
            'email' => 'admin@odiseamakeup.com',
            'password' => password_hash('admin123', PASSWORD_DEFAULT),
            'full_name' => 'Administrador Principal',
            'role' => 'super_admin',
            'is_active' => 1
        ];
        
        $sql = "INSERT INTO admins (username, email, password, full_name, role, is_active) 
                VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $db->prepare($sql);
        $stmt->execute(array_values($adminData));
        echo "✅ Usuario administrador creado (admin/admin123)\n";
    } else {
        echo "ℹ️  Ya existe un usuario administrador\n";
    }
    
    // 3. Crear banners de ejemplo
    $bannerExists = $db->query("SELECT COUNT(*) FROM banners")->fetchColumn();
    if ($bannerExists == 0) {
        $banners = [
            [
                'title' => '¡Nueva Colección Primavera!',
                'subtitle' => 'Descubre los colores de la temporada',
                'description' => 'Paletas exclusivas con tonos vibrantes y naturales perfectos para esta primavera',
                'image' => 'uploads/banners/banner-primavera.jpg',
                'link_url' => 'catalogo.php?categoria=ojos',
                'link_text' => 'Ver Colección',
                'position' => 'hero',
                'is_active' => 1,
                'sort_order' => 1
            ],
            [
                'title' => 'Bases de Maquillaje',
                'subtitle' => '50% de descuento',
                'description' => 'Encuentra tu tono perfecto con nuestra amplia gama de bases',
                'image' => 'uploads/banners/banner-bases.jpg',
                'link_url' => 'catalogo.php?categoria=rostro',
                'link_text' => 'Comprar Ahora',
                'position' => 'sidebar',
                'is_active' => 1,
                'sort_order' => 1
            ]
        ];
        
        foreach ($banners as $banner) {
            $sql = "INSERT INTO banners (title, subtitle, description, image, link_url, link_text, position, is_active, sort_order) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $db->prepare($sql);
            $stmt->execute(array_values($banner));
        }
        echo "✅ Banners de ejemplo creados\n";
    } else {
        echo "ℹ️  Ya existen banners\n";
    }
    
    // 4. Crear artículos de noticias/blog
    $newsExists = $db->query("SELECT COUNT(*) FROM news")->fetchColumn();
    if ($newsExists == 0) {
        // Necesitamos el ID del admin para las noticias
        $adminId = $db->query("SELECT id FROM admins LIMIT 1")->fetchColumn();
        
        if ($adminId) {
            $newsArticles = [
                [
                    'title' => 'Tendencias de Maquillaje Primavera 2025',
                    'slug' => 'tendencias-maquillaje-primavera-2025',
                    'excerpt' => 'Descubre las tendencias de maquillaje que marcarán esta primavera',
                    'content' => 'Esta primavera trae consigo colores vibrantes y técnicas innovadoras...',
                    'featured_image' => 'uploads/news/tendencias-primavera.jpg',
                    'author_id' => $adminId,
                    'status' => 'published',
                    'is_featured' => 1,
                    'published_at' => date('Y-m-d H:i:s')
                ],
                [
                    'title' => 'Cómo elegir la base perfecta para tu tipo de piel',
                    'slug' => 'como-elegir-base-perfecta',
                    'excerpt' => 'Guía completa para encontrar la base de maquillaje ideal',
                    'content' => 'Elegir la base correcta es fundamental para un maquillaje perfecto...',
                    'featured_image' => 'uploads/news/guia-bases.jpg',
                    'author_id' => $adminId,
                    'status' => 'published',
                    'is_featured' => 1,
                    'published_at' => date('Y-m-d H:i:s', strtotime('-1 day'))
                ]
            ];
            
            foreach ($newsArticles as $article) {
                $sql = "INSERT INTO news (title, slug, excerpt, content, featured_image, author_id, status, is_featured, published_at) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = $db->prepare($sql);
                $stmt->execute(array_values($article));
            }
            echo "✅ Artículos de noticias creados\n";
        }
    } else {
        echo "ℹ️  Ya existen artículos de noticias\n";
    }
    
    // 5. Crear cupones de ejemplo
    $couponExists = $db->query("SELECT COUNT(*) FROM coupons")->fetchColumn();
    if ($couponExists == 0) {
        $coupons = [
            [
                'code' => 'BIENVENIDA20',
                'type' => 'percentage',
                'value' => 20.00,
                'minimum_amount' => 100000.00,
                'usage_limit' => 100,
                'is_active' => 1,
                'expires_at' => date('Y-m-d H:i:s', strtotime('+3 months'))
            ],
            [
                'code' => 'ENVIOGRATIS',
                'type' => 'fixed',
                'value' => 15000.00,
                'minimum_amount' => 80000.00,
                'usage_limit' => 50,
                'is_active' => 1,
                'expires_at' => date('Y-m-d H:i:s', strtotime('+1 month'))
            ]
        ];
        
        foreach ($coupons as $coupon) {
            $sql = "INSERT INTO coupons (code, type, value, minimum_amount, usage_limit, is_active, expires_at) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt = $db->prepare($sql);
            $stmt->execute(array_values($coupon));
        }
        echo "✅ Cupones de descuento creados\n";
    } else {
        echo "ℹ️  Ya existen cupones\n";
    }
    
    echo "\n🎉 ¡Base de datos completamente configurada!\n";
    echo "\n📊 Resumen de la base de datos:\n";
    
    // Mostrar resumen
    $tables = [
        'products' => 'Productos',
        'categories' => 'Categorías', 
        'brands' => 'Marcas',
        'admins' => 'Administradores',
        'banners' => 'Banners',
        'news' => 'Noticias',
        'coupons' => 'Cupones',
        'settings' => 'Configuraciones'
    ];
    
    foreach ($tables as $table => $name) {
        $count = $db->query("SELECT COUNT(*) FROM $table")->fetchColumn();
        echo "• $name: $count registros\n";
    }
    
    echo "\n🔐 Acceso de administrador:\n";
    echo "• URL: http://localhost/odisea-makeup-store/admin/\n";
    echo "• Usuario: admin\n";
    echo "• Contraseña: admin123\n";
    
    echo "\n🌐 Tu tienda está lista en:\n";
    echo "• http://localhost/odisea-makeup-store/\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}
?>
