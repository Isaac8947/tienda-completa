-- Tabla para reservas temporales de stock
CREATE TABLE IF NOT EXISTS `stock_reservations` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `product_id` int(11) NOT NULL,
    `session_id` varchar(128) NOT NULL,
    `quantity` int(11) NOT NULL,
    `expires_at` datetime NOT NULL,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `unique_reservation` (`product_id`, `session_id`),
    KEY `idx_expires_at` (`expires_at`),
    KEY `idx_session_id` (`session_id`),
    FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla para logs de cambios de estado de órdenes
CREATE TABLE IF NOT EXISTS `order_status_logs` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `order_id` int(11) NOT NULL,
    `previous_status` varchar(50) NOT NULL,
    `new_status` varchar(50) NOT NULL,
    `changed_by` int(11) DEFAULT NULL,
    `notes` text DEFAULT NULL,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_order_id` (`order_id`),
    KEY `idx_changed_by` (`changed_by`),
    KEY `idx_created_at` (`created_at`),
    FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
