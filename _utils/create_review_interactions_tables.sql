-- Script para crear las tablas necesarias para likes y respuestas de reseñas

-- Tabla para los likes de reseñas
CREATE TABLE IF NOT EXISTS review_likes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    review_id INT NOT NULL,
    user_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (review_id) REFERENCES reviews(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES customers(id) ON DELETE CASCADE,
    UNIQUE KEY unique_review_user (review_id, user_id)
);

-- Tabla para las respuestas a reseñas
CREATE TABLE IF NOT EXISTS review_replies (
    id INT AUTO_INCREMENT PRIMARY KEY,
    review_id INT NOT NULL,
    user_id INT NOT NULL,
    reply_text TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (review_id) REFERENCES reviews(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES customers(id) ON DELETE CASCADE
);

-- Índices para mejor rendimiento
CREATE INDEX idx_review_likes_review_id ON review_likes(review_id);
CREATE INDEX idx_review_likes_user_id ON review_likes(user_id);
CREATE INDEX idx_review_replies_review_id ON review_replies(review_id);
CREATE INDEX idx_review_replies_user_id ON review_replies(user_id);
