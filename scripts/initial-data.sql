-- Datos iniciales para Odisea Makeup Store
USE odisea_makeup;

-- Insertar administrador por defecto
INSERT INTO admins (name, email, password, role, is_active) VALUES
('Administrador', 'admin@odisea.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'super_admin', TRUE);

-- Insertar categorías principales
INSERT INTO categories (name, slug, description, sort_order, is_active) VALUES
('Rostro', 'rostro', 'Productos para el cuidado y maquillaje del rostro', 1, TRUE),
('Ojos', 'ojos', 'Maquillaje para ojos: sombras, delineadores, máscaras', 2, TRUE),
('Labios', 'labios', 'Labiales, glosses y productos para labios', 3, TRUE),
('Cejas', 'cejas', 'Productos para definir y cuidar las cejas', 4, TRUE),
('Cuidado de la Piel', 'cuidado-piel', 'Productos para el cuidado facial y corporal', 5, TRUE),
('Herramientas', 'herramientas', 'Brochas, esponjas y herramientas de maquillaje', 6, TRUE);

-- Insertar subcategorías
INSERT INTO categories (name, slug, description, parent_id, sort_order, is_active) VALUES
-- Rostro
('Base de Maquillaje', 'base-maquillaje', 'Bases líquidas, en polvo y cremas', 1, 1, TRUE),
('Corrector', 'corrector', 'Correctores para ojeras y imperfecciones', 1, 2, TRUE),
('Polvo Compacto', 'polvo-compacto', 'Polvos para fijar y matificar', 1, 3, TRUE),
('Rubor', 'rubor', 'Rubores en polvo, crema y líquidos', 1, 4, TRUE),
('Contorno', 'contorno', 'Productos para contornear y destacar', 1, 5, TRUE),
('Iluminador', 'iluminador', 'Iluminadores para dar brillo natural', 1, 6, TRUE),

-- Ojos
('Sombras', 'sombras', 'Sombras individuales y paletas', 2, 1, TRUE),
('Delineadores', 'delineadores', 'Delineadores líquidos, en gel y lápiz', 2, 2, TRUE),
('Máscaras de Pestañas', 'mascaras-pestanas', 'Máscaras para volumen y longitud', 2, 3, TRUE),
('Pestañas Postizas', 'pestanas-postizas', 'Pestañas individuales y completas', 2, 4, TRUE),

-- Labios
('Labiales', 'labiales', 'Labiales mate, cremosos y líquidos', 3, 1, TRUE),
('Gloss', 'gloss', 'Brillos labiales transparentes y con color', 3, 2, TRUE),
('Delineador de Labios', 'delineador-labios', 'Lápices para definir los labios', 3, 3, TRUE),

-- Cejas
('Lápiz de Cejas', 'lapiz-cejas', 'Lápices para definir las cejas', 4, 1, TRUE),
('Gel de Cejas', 'gel-cejas', 'Geles para fijar y dar color', 4, 2, TRUE),
('Pomada de Cejas', 'pomada-cejas', 'Pomadas cremosas para cejas', 4, 3, TRUE),

-- Cuidado de la Piel
('Limpiadores', 'limpiadores', 'Limpiadores faciales y desmaquillantes', 5, 1, TRUE),
('Hidratantes', 'hidratantes', 'Cremas y serums hidratantes', 5, 2, TRUE),
('Protector Solar', 'protector-solar', 'Protectores solares faciales', 5, 3, TRUE),
('Mascarillas', 'mascarillas', 'Mascarillas faciales de tratamiento', 5, 4, TRUE),

-- Herramientas
('Brochas', 'brochas', 'Brochas para rostro y ojos', 6, 1, TRUE),
('Esponjas', 'esponjas', 'Esponjas para aplicar base', 6, 2, TRUE),
('Accesorios', 'accesorios', 'Otros accesorios de maquillaje', 6, 3, TRUE);

-- Insertar marcas
INSERT INTO brands (name, slug, description, is_active) VALUES
('Fenty Beauty', 'fenty-beauty', 'Marca inclusiva de Rihanna con amplia gama de tonos', TRUE),
('MAC Cosmetics', 'mac-cosmetics', 'Marca profesional de maquillaje', TRUE),
('Urban Decay', 'urban-decay', 'Maquillaje rebelde y de alta calidad', TRUE),
('NARS', 'nars', 'Marca francesa de maquillaje artístico', TRUE),
('Charlotte Tilbury', 'charlotte-tilbury', 'Maquillaje de lujo inspirado en Hollywood', TRUE),
('Rare Beauty', 'rare-beauty', 'Marca de Selena Gomez enfocada en la autoestima', TRUE),
('Glossier', 'glossier', 'Belleza minimalista y natural', TRUE),
('Huda Beauty', 'huda-beauty', 'Marca del Medio Oriente con productos innovadores', TRUE),
('Anastasia Beverly Hills', 'anastasia-beverly-hills', 'Especialistas en cejas y contorno', TRUE),
('Too Faced', 'too-faced', 'Maquillaje divertido y femenino', TRUE);

-- Insertar productos de ejemplo
INSERT INTO products (name, slug, sku, description, short_description, price, compare_price, category_id, brand_id, status, featured, is_new, main_image, inventory_quantity) VALUES
-- Bases de maquillaje
('Fenty Beauty Pro Filt\'r Soft Matte Foundation', 'fenty-pro-filtr-foundation', 'FB-PF-001', 'Base de maquillaje mate de larga duración con cobertura completa. Disponible en 50 tonos para todos los tipos de piel.', 'Base mate de larga duración con 50 tonos inclusivos', 89000, 95000, 7, 1, 'active', TRUE, TRUE, '/uploads/products/fenty-foundation.jpg', 25),

('MAC Studio Fix Fluid Foundation', 'mac-studio-fix-foundation', 'MAC-SF-001', 'Base líquida de cobertura media a completa con acabado natural. Controla el brillo hasta por 24 horas.', 'Base líquida de 24 horas de duración', 125000, NULL, 7, 2, 'active', TRUE, FALSE, '/uploads/products/mac-foundation.jpg', 30),

-- Correctores
('NARS Radiant Creamy Concealer', 'nars-radiant-concealer', 'NARS-RC-001', 'Corrector cremoso de cobertura completa que ilumina e hidrata. Perfecto para ojeras y imperfecciones.', 'Corrector cremoso iluminador de cobertura completa', 95000, NULL, 8, 4, 'active', FALSE, FALSE, '/uploads/products/nars-concealer.jpg', 20),

-- Sombras
('Urban Decay Naked Heat Palette', 'urban-decay-naked-heat', 'UD-NH-001', 'Paleta de 12 sombras en tonos cálidos y especiados. Fórmula cremosa y pigmentada.', 'Paleta de sombras en tonos cálidos', 185000, 200000, 13, 3, 'active', TRUE, FALSE, '/uploads/products/ud-naked-heat.jpg', 15),

('Huda Beauty Desert Dusk Palette', 'huda-desert-dusk-palette', 'HB-DD-001', 'Paleta de 18 sombras inspirada en el desierto. Mezcla de tonos mate, metálicos y duocromos.', 'Paleta de 18 sombras inspirada en el desierto', 220000, NULL, 13, 8, 'active', TRUE, TRUE, '/uploads/products/huda-desert-dusk.jpg', 12),

-- Labiales
('Charlotte Tilbury Matte Revolution Lipstick', 'charlotte-tilbury-matte-revolution', 'CT-MR-001', 'Labial mate de larga duración con fórmula hidratante. Acabado aterciopelado y cómodo.', 'Labial mate hidratante de larga duración', 115000, NULL, 17, 5, 'active', FALSE, FALSE, '/uploads/products/ct-matte-lipstick.jpg', 40),

('Rare Beauty Soft Pinch Liquid Blush', 'rare-beauty-soft-pinch-blush', 'RB-SP-001', 'Rubor líquido de larga duración con acabado natural. Fácil de difuminar y construir.', 'Rubor líquido de acabado natural', 75000, NULL, 10, 6, 'active', TRUE, TRUE, '/uploads/products/rare-beauty-blush.jpg', 35),

-- Máscaras de pestañas
('Too Faced Better Than Sex Mascara', 'too-faced-better-than-sex', 'TF-BTS-001', 'Máscara de pestañas para volumen extremo. Fórmula cremosa que no se descama.', 'Máscara para volumen extremo', 85000, NULL, 15, 10, 'active', FALSE, FALSE, '/uploads/products/tf-mascara.jpg', 50),

-- Productos para cejas
('Anastasia Beverly Hills Brow Wiz', 'abh-brow-wiz', 'ABH-BW-001', 'Lápiz de cejas ultra fino para definición precisa. Incluye cepillo spoolie.', 'Lápiz de cejas ultra fino con spoolie', 65000, NULL, 20, 9, 'active', FALSE, FALSE, '/uploads/products/abh-brow-wiz.jpg', 60),

-- Herramientas
('Beauty Blender Original', 'beauty-blender-original', 'BB-OR-001', 'Esponja de maquillaje original para aplicación perfecta de base. Se expande con agua.', 'Esponja de maquillaje original', 45000, NULL, 26, 1, 'active', FALSE, FALSE, '/uploads/products/beauty-blender.jpg', 100);

-- Insertar configuraciones del sitio
INSERT INTO site_settings (setting_key, setting_value, setting_type) VALUES
('site_name', 'Odisea Makeup', 'text'),
('site_description', 'Tu destino para el maquillaje de alta calidad', 'textarea'),
('contact_email', 'contacto@odisea.com', 'text'),
('contact_phone', '+57 300 123 4567', 'text'),
('contact_address', 'Calle 123 #45-67, Bogotá, Colombia', 'textarea'),
('shipping_cost', '15000', 'number'),
('free_shipping_threshold', '150000', 'number'),
('tax_rate', '19', 'number'),
('currency', 'COP', 'text'),
('items_per_page', '20', 'number'),
('allow_guest_checkout', 'true', 'boolean'),
('require_email_verification', 'true', 'boolean'),
('maintenance_mode', 'false', 'boolean');

-- Insertar banners de ejemplo
INSERT INTO banners (title, subtitle, description, image, link_url, link_text, position, sort_order, is_active) VALUES
('Nueva Colección Primavera', 'Descubre los colores de la temporada', 'Explora nuestra nueva colección con los tonos más frescos y vibrantes para esta primavera.', '/uploads/banners/spring-collection.jpg', '/categoria/rostro', 'Ver Colección', 'hero', 1, TRUE),
('Envío Gratis', 'En compras superiores a $150.000', 'Aprovecha nuestro envío gratuito en toda Colombia para pedidos superiores a $150.000.', '/uploads/banners/free-shipping.jpg', '/productos', 'Comprar Ahora', 'hero', 2, TRUE),
('Marcas Exclusivas', 'Las mejores marcas internacionales', 'Encuentra productos de Fenty Beauty, MAC, Urban Decay y muchas más marcas premium.', '/uploads/banners/premium-brands.jpg', '/marcas', 'Ver Marcas', 'hero', 3, TRUE);

-- Insertar cupones de ejemplo
INSERT INTO coupons (code, type, value, minimum_amount, usage_limit, is_active, expires_at) VALUES
('BIENVENIDA20', 'percentage', 20.00, 100000, 100, TRUE, DATE_ADD(NOW(), INTERVAL 30 DAY)),
('ENVIOGRATIS', 'fixed', 15000.00, 80000, 50, TRUE, DATE_ADD(NOW(), INTERVAL 15 DAY)),
('PRIMAVERA15', 'percentage', 15.00, 150000, NULL, TRUE, DATE_ADD(NOW(), INTERVAL 60 DAY));
