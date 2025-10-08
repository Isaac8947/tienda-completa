-- Crear tabla de reseñas si no existe
CREATE TABLE IF NOT EXISTS `reviews` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `customer_id` int(11) DEFAULT NULL,
  `customer_name` varchar(255) NOT NULL,
  `customer_email` varchar(255) NOT NULL,
  `rating` tinyint(1) NOT NULL CHECK (rating >= 1 AND rating <= 5),
  `title` varchar(255) DEFAULT NULL,
  `comment` text NOT NULL,
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `verified_purchase` tinyint(1) NOT NULL DEFAULT 0,
  `helpful_votes` int(11) NOT NULL DEFAULT 0,
  `total_votes` int(11) NOT NULL DEFAULT 0,
  `reply_text` text DEFAULT NULL,
  `reply_date` timestamp NULL DEFAULT NULL,
  `admin_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_product_id` (`product_id`),
  KEY `idx_customer_id` (`customer_id`),
  KEY `idx_status` (`status`),
  KEY `idx_rating` (`rating`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insertar algunas reseñas de ejemplo (sin claves foráneas estrictas)
INSERT INTO `reviews` (`product_id`, `customer_id`, `customer_name`, `customer_email`, `rating`, `title`, `comment`, `status`, `verified_purchase`, `helpful_votes`, `total_votes`) VALUES
(1, NULL, 'María González', 'maria@ejemplo.com', 5, 'Excelente producto', 'Me encanta este producto, la calidad es increíble y el resultado es perfecto. Lo recomiendo 100%.', 'approved', 1, 15, 18),
(1, NULL, 'Ana López', 'ana@ejemplo.com', 4, 'Muy bueno', 'Buen producto, aunque el precio es un poco alto. La calidad lo justifica.', 'approved', 1, 8, 10),
(2, NULL, 'Carmen Ruiz', 'carmen@ejemplo.com', 5, 'Perfecto', 'Exactamente lo que esperaba. Llegó rápido y en perfectas condiciones.', 'approved', 1, 12, 12),
(1, NULL, 'Laura Martín', 'laura@ejemplo.com', 3, 'Regular', 'El producto está bien pero esperaba más por el precio que tiene.', 'pending', 0, 2, 5),
(3, NULL, 'Isabel Jiménez', 'isabel@ejemplo.com', 5, 'Increíble', 'Este es mi producto favorito de maquillaje. La cobertura es perfecta y dura todo el día.', 'approved', 1, 25, 27),
(2, NULL, 'Patricia Moreno', 'patricia@ejemplo.com', 2, 'No me gustó', 'No me convenció, no es lo que esperaba según las fotos.', 'rejected', 0, 1, 8),
(4, NULL, 'Rosa Fernández', 'rosa@ejemplo.com', 4, 'Buena compra', 'Contenta con la compra, aunque tardó un poco en llegar.', 'approved', 1, 6, 7),
(1, NULL, 'Elena Sánchez', 'elena@ejemplo.com', 5, 'Recomendado', 'Lo compré por las reseñas y no me arrepiento. Es fantástico.', 'pending', 1, 0, 0);
