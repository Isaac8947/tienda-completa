-- Tabla para los likes/dislikes de reseñas
CREATE TABLE IF NOT EXISTS review_likes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    review_id INT NOT NULL,
    customer_id INT NOT NULL,
    action ENUM('like', 'dislike') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (review_id) REFERENCES reviews(id) ON DELETE CASCADE,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
    UNIQUE KEY unique_review_customer (review_id, customer_id)
);

-- Agregar columnas para contar likes y dislikes en la tabla reviews si no existen
ALTER TABLE reviews 
ADD COLUMN IF NOT EXISTS likes INT DEFAULT 0,
ADD COLUMN IF NOT EXISTS dislikes INT DEFAULT 0;

-- Actualizar conteos existentes de likes y dislikes
UPDATE reviews r SET 
    likes = (SELECT COUNT(*) FROM review_likes rl WHERE rl.review_id = r.id AND rl.action = 'like'),
    dislikes = (SELECT COUNT(*) FROM review_likes rl WHERE rl.review_id = r.id AND rl.action = 'dislike');

-- Agregar índices para mejor performance
CREATE INDEX IF NOT EXISTS idx_review_likes_review_id ON review_likes(review_id);
CREATE INDEX IF NOT EXISTS idx_review_likes_customer_id ON review_likes(customer_id);
CREATE INDEX IF NOT EXISTS idx_reviews_product_rating ON reviews(product_id, rating);
CREATE INDEX IF NOT EXISTS idx_products_category_status ON products(category_id, status);
CREATE INDEX IF NOT EXISTS idx_products_brand_status ON products(brand_id, status);
CREATE INDEX IF NOT EXISTS idx_products_price ON products(price);
CREATE INDEX IF NOT EXISTS idx_products_views ON products(views);
CREATE INDEX IF NOT EXISTS idx_products_featured ON products(is_featured);
