-- Datos iniciales para Odisea Makeup Store
USE odisea_makeup;

-- Insertar admin por defecto
INSERT INTO admins (username, email, password, full_name, role) VALUES 
('admin', 'admin@odisea.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrador Principal', 'super_admin');

-- Insertar categorías principales
INSERT INTO categories (name, slug, description, icon, is_featured, sort_order) VALUES
('Rostro', 'rostro', 'Productos para el rostro: bases, correctores, rubor, contorno', 'fas fa-palette', TRUE, 1),
('Ojos', 'ojos', 'Maquillaje para ojos: sombras, delineadores, máscaras', 'fas fa-eye', TRUE, 2),
('Labios', 'labios', 'Productos para labios: labiales, gloss, delineadores', 'fas fa-kiss-wink-heart', TRUE, 3),
('Cuidado Facial', 'cuidado-facial', 'Productos de skincare y cuidado de la piel', 'fas fa-spa', TRUE, 4),
('Herramientas', 'herramientas', 'Brochas, esponjas y herramientas de maquillaje', 'fas fa-paint-brush', TRUE, 5),
('Uñas', 'unas', 'Esmaltes y productos para el cuidado de uñas', 'fas fa-hand-paper', FALSE, 6);

-- Insertar subcategorías
INSERT INTO categories (parent_id, name, slug, description, sort_order) VALUES
(1, 'Base de Maquillaje', 'base-maquillaje', 'Bases líquidas, en polvo y cremas', 1),
(1, 'Correctores', 'correctores', 'Correctores de ojeras y imperfecciones', 2),
(1, 'Rubor', 'rubor', 'Rubores en polvo, crema y líquidos', 3),
(1, 'Contorno', 'contorno', 'Productos para contornear el rostro', 4),
(1, 'Iluminadores', 'iluminadores', 'Highlighters y productos iluminadores', 5),
(2, 'Sombras', 'sombras', 'Sombras individuales y paletas', 1),
(2, 'Delineadores', 'delineadores', 'Delineadores líquidos, en gel y lápiz', 2),
(2, 'Máscaras de Pestañas', 'mascaras-pestanas', 'Máscaras para pestañas', 3),
(2, 'Cejas', 'cejas', 'Productos para definir y rellenar cejas', 4),
(3, 'Labiales', 'labiales', 'Labiales mate, cremosos y líquidos', 1),
(3, 'Gloss', 'gloss', 'Brillos labiales', 2),
(3, 'Delineadores de Labios', 'delineadores-labios', 'Lápices delineadores para labios', 3);

-- Insertar marcas
INSERT INTO brands (name, slug, description, is_featured) VALUES
('Fenty Beauty', 'fenty-beauty', 'Marca inclusiva de Rihanna con amplia gama de tonos', TRUE),
('Urban Decay', 'urban-decay', 'Marca conocida por sus paletas de sombras vibrantes', TRUE),
('NARS', 'nars', 'Marca francesa de maquillaje profesional', TRUE),
('Charlotte Tilbury', 'charlotte-tilbury', 'Marca británica de lujo', TRUE),
('MAC', 'mac', 'Make-up Art Cosmetics, marca profesional', TRUE),
('Rare Beauty', 'rare-beauty', 'Marca de Selena Gomez enfocada en la inclusión', TRUE),
('Glossier', 'glossier', 'Marca minimalista de belleza natural', FALSE),
('The Ordinary', 'the-ordinary', 'Marca de skincare con ingredientes activos', FALSE),
('Huda Beauty', 'huda-beauty', 'Marca del Medio Oriente con productos vibrantes', FALSE),
('Kylie Cosmetics', 'kylie-cosmetics', 'Marca de Kylie Jenner', FALSE);

-- Insertar productos de ejemplo
INSERT INTO products (brand_id, category_id, name, slug, description, short_description, sku, price, compare_price, stock_quantity, main_image, is_featured, status) VALUES
(1, 7, 'Pro Filt\'r Soft Matte Foundation', 'fenty-pro-filtr-foundation', 'Base de maquillaje de cobertura completa con acabado mate suave. Disponible en 50 tonos para todos los tipos de piel.', 'Base mate de cobertura completa en 50 tonos', 'FENTY-FOUND-001', 89000, 95000, 25, '/uploads/products/fenty-foundation.jpg', TRUE, 'active'),
(2, 12, 'Naked Heat Eyeshadow Palette', 'urban-decay-naked-heat', 'Paleta de 12 sombras en tonos cálidos y ardientes. Perfecta para looks ahumados y dramáticos.', 'Paleta de 12 sombras en tonos cálidos', 'UD-NAKED-HEAT', 156000, 175000, 15, '/uploads/products/naked-heat.jpg', TRUE, 'active'),
(3, 9, 'Orgasm Blush', 'nars-orgasm-blush', 'El rubor más icónico de NARS. Tono coral dorado con destellos sutiles que favorece a todos los tonos de piel.', 'Rubor icónico en tono coral dorado', 'NARS-ORGASM-001', 78000, NULL, 30, '/uploads/products/nars-orgasm.jpg', TRUE, 'active'),
(4, 16, 'Pillow Talk Lipstick', 'charlotte-tilbury-pillow-talk', 'Labial cremoso en el tono nude-rosado más famoso del mundo. Fórmula hidratante y de larga duración.', 'Labial cremoso en tono nude-rosado', 'CT-PILLOW-TALK', 95000, NULL, 20, '/uploads/products/pillow-talk.jpg', TRUE, 'active'),
(5, 16, 'Ruby Woo Lipstick', 'mac-ruby-woo', 'Labial mate en rojo clásico. El rojo más icónico de MAC con acabado mate intenso.', 'Labial mate en rojo clásico', 'MAC-RUBY-WOO', 72000, NULL, 18, '/uploads/products/ruby-woo.jpg', FALSE, 'active'),
(6, 9, 'Soft Pinch Liquid Blush', 'rare-beauty-soft-pinch', 'Rubor líquido de larga duración con aplicador de precisión. Fórmula buildable y natural.', 'Rubor líquido de larga duración', 'RARE-BLUSH-001', 67000, NULL, 22, '/uploads/products/rare-blush.jpg', FALSE, 'active'),
(7, 17, 'Cloud Paint Blush', 'glossier-cloud-paint', 'Rubor en gel con acabado natural y saludable. Fácil de difuminar y mezclar.', 'Rubor en gel con acabado natural', 'GLOS-CLOUD-001', 54000, NULL, 28, '/uploads/products/cloud-paint.jpg', FALSE, 'active'),
(8, 4, 'Niacinamide 10% + Zinc 1%', 'the-ordinary-niacinamide', 'Serum concentrado para reducir poros y controlar la grasa. Ideal para pieles mixtas y grasas.', 'Serum de niacinamida al 10%', 'TO-NIACIN-001', 32000, NULL, 45, '/uploads/products/niacinamide.jpg', FALSE, 'active');

-- Insertar variantes de productos (tonos de base)
INSERT INTO product_variants (product_id, name, sku, stock_quantity, attributes) VALUES
(1, 'Tono 110', 'FENTY-FOUND-110', 8, '{"shade": "110", "undertone": "neutral"}'),
(1, 'Tono 150', 'FENTY-FOUND-150', 6, '{"shade": "150", "undertone": "neutral"}'),
(1, 'Tono 220', 'FENTY-FOUND-220', 5, '{"shade": "220", "undertone": "warm"}'),
(1, 'Tono 290', 'FENTY-FOUND-290', 4, '{"shade": "290", "undertone": "neutral"}'),
(1, 'Tono 350', 'FENTY-FOUND-350', 2, '{"shade": "350", "undertone": "warm"}');

-- Insertar atributos de productos
INSERT INTO product_attributes (name, slug, type, options, is_filterable) VALUES
('Color', 'color', 'color', NULL, TRUE),
('Tono de Piel', 'skin-tone', 'select', '["Claro", "Medio", "Oscuro"]', TRUE),
('Tipo de Piel', 'skin-type', 'multiselect', '["Grasa", "Seca", "Mixta", "Sensible", "Normal"]', TRUE),
('Acabado', 'finish', 'select', '["Mate", "Satinado", "Brillante", "Natural"]', TRUE),
('Cobertura', 'coverage', 'select', '["Ligera", "Media", "Completa"]', TRUE),
('Resistente al Agua', 'waterproof', 'boolean', NULL, TRUE);

-- Insertar configuraciones del sistema
INSERT INTO settings (key_name, value, type, group_name, description) VALUES
('site_name', 'Odisea Makeup', 'text', 'general', 'Nombre del sitio web'),
('site_description', 'Tu destino para el maquillaje perfecto', 'text', 'general', 'Descripción del sitio'),
('contact_email', 'contacto@odisea.com', 'text', 'contact', 'Email de contacto'),
('contact_phone', '+57 300 123 4567', 'text', 'contact', 'Teléfono de contacto'),
('contact_address', 'Barranquilla, Colombia', 'text', 'contact', 'Dirección física'),
('currency', 'COP', 'text', 'store', 'Moneda por defecto'),
('tax_rate', '19', 'number', 'store', 'Tasa de IVA en porcentaje'),
('free_shipping_threshold', '150000', 'number', 'shipping', 'Monto mínimo para envío gratis'),
('default_shipping_cost', '15000', 'number', 'shipping', 'Costo de envío por defecto'),
('items_per_page', '20', 'number', 'display', 'Productos por página'),
('enable_reviews', 'true', 'boolean', 'features', 'Habilitar reseñas de productos'),
('enable_wishlist', 'true', 'boolean', 'features', 'Habilitar lista de deseos'),
('maintenance_mode', 'false', 'boolean', 'system', 'Modo de mantenimiento');

-- Insertar banners promocionales
INSERT INTO banners (title, subtitle, description, image, link_url, link_text, position, is_active, sort_order) VALUES
('Nueva Colección Otoño 2024', 'Descubre los tonos más trendy', 'Paletas exclusivas con los colores de la temporada', '/uploads/banners/autumn-collection.jpg', '/collections/autumn-2024', 'Ver Colección', 'hero', TRUE, 1),
('Envío Gratis', 'En compras superiores a $150.000', 'Aprovecha nuestro envío gratuito a nivel nacional', '/uploads/banners/free-shipping.jpg', '/shipping-info', 'Más Info', 'hero', TRUE, 2),
('Black Friday', '50% OFF en productos seleccionados', 'Las mejores ofertas del año te esperan', '/uploads/banners/black-friday.jpg', '/offers', 'Ver Ofertas', 'hero', FALSE, 3);

-- Insertar artículos de blog/noticias
INSERT INTO news (title, slug, excerpt, content, author_id, status, is_featured, published_at) VALUES
('Tendencias de Maquillaje Otoño 2024', 'tendencias-maquillaje-otono-2024', 'Descubre las tendencias que marcarán la temporada otoñal', 'El otoño 2024 trae consigo una paleta de colores cálidos y terrosos que prometen revolucionar nuestros looks de maquillaje...', 1, 'published', TRUE, NOW()),
('Cómo Elegir la Base Perfecta', 'como-elegir-base-perfecta', 'Guía completa para encontrar tu tono ideal', 'Elegir la base de maquillaje correcta puede ser todo un desafío. En esta guía te enseñamos paso a paso...', 1, 'published', FALSE, NOW()),
('Rutina de Skincare para Principiantes', 'rutina-skincare-principiantes', 'Los pasos básicos para cuidar tu piel', 'Una buena rutina de skincare es la base de cualquier look de maquillaje. Aquí te explicamos los pasos fundamentales...', 1, 'published', FALSE, NOW());

-- Insertar cupones de descuento
INSERT INTO coupons (code, type, value, minimum_amount, usage_limit, is_active, expires_at) VALUES
('BIENVENIDA10', 'percentage', 10.00, 50000, 100, TRUE, DATE_ADD(NOW(), INTERVAL 30 DAY)),
('ENVIOGRATIS', 'fixed', 15000, 100000, NULL, TRUE, DATE_ADD(NOW(), INTERVAL 60 DAY)),
('BLACKFRIDAY50', 'percentage', 50.00, 200000, 500, FALSE, DATE_ADD(NOW(), INTERVAL 90 DAY));

-- Insertar cliente de prueba
INSERT INTO customers (email, password, first_name, last_name, phone, email_verified) VALUES
('cliente@test.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'María', 'González', '3001234567', TRUE);

-- Insertar dirección de prueba
INSERT INTO customer_addresses (customer_id, first_name, last_name, address_line_1, city, state, postal_code, phone, is_default) VALUES
(1, 'María', 'González', 'Calle 72 #52-45', 'Barranquilla', 'Atlántico', '080001', '3001234567', TRUE);
