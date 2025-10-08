-- Script para crear las tablas de cupones
-- Este script es seguro para ejecutar múltiples veces

-- Crear tabla de cupones
CREATE TABLE IF NOT EXISTS `coupons` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `code` varchar(50) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `type` enum('percentage','fixed') NOT NULL DEFAULT 'percentage',
  `value` decimal(10,2) NOT NULL,
  `minimum_amount` decimal(10,2) DEFAULT NULL,
  `maximum_discount` decimal(10,2) DEFAULT NULL,
  `usage_limit` int(11) DEFAULT NULL,
  `usage_limit_per_customer` int(11) DEFAULT NULL,
  `used_count` int(11) NOT NULL DEFAULT 0,
  `start_date` datetime NOT NULL,
  `end_date` datetime DEFAULT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `customer_ids` json DEFAULT NULL,
  `product_ids` json DEFAULT NULL,
  `category_ids` json DEFAULT NULL,
  `brand_ids` json DEFAULT NULL,
  `exclude_sale_items` tinyint(1) NOT NULL DEFAULT 0,
  `free_shipping` tinyint(1) NOT NULL DEFAULT 0,
  `created_by_admin_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`),
  KEY `idx_status` (`status`),
  KEY `idx_start_date` (`start_date`),
  KEY `idx_end_date` (`end_date`),
  KEY `idx_type` (`type`),
  KEY `idx_created_by_admin` (`created_by_admin_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Crear tabla de uso de cupones
CREATE TABLE IF NOT EXISTS `coupon_usage` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `coupon_id` int(11) NOT NULL,
  `customer_id` int(11) DEFAULT NULL,
  `order_id` int(11) DEFAULT NULL,
  `discount_amount` decimal(10,2) NOT NULL,
  `used_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_coupon_id` (`coupon_id`),
  KEY `idx_customer_id` (`customer_id`),
  KEY `idx_order_id` (`order_id`),
  KEY `idx_used_at` (`used_at`),
  CONSTRAINT `fk_coupon_usage_coupon` FOREIGN KEY (`coupon_id`) REFERENCES `coupons` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_coupon_usage_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_coupon_usage_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insertar datos de ejemplo (solo si no existen)
INSERT IGNORE INTO `coupons` (
  `code`, `name`, `description`, `type`, `value`, `minimum_amount`, `maximum_discount`,
  `usage_limit`, `usage_limit_per_customer`, `start_date`, `end_date`, `status`,
  `exclude_sale_items`, `free_shipping`, `created_by_admin_id`
) VALUES
(
  'WELCOME10', 
  'Bienvenida 10%', 
  'Descuento de bienvenida para nuevos clientes', 
  'percentage', 
  10.00, 
  50.00, 
  20.00,
  NULL, 
  1, 
  NOW(), 
  DATE_ADD(NOW(), INTERVAL 30 DAY), 
  'active',
  0, 
  0, 
  1
),
(
  'SAVE20', 
  'Ahorro $20', 
  'Descuento fijo de $20 en compras mayores a $100', 
  'fixed', 
  20.00, 
  100.00, 
  NULL,
  100, 
  NULL, 
  NOW(), 
  DATE_ADD(NOW(), INTERVAL 60 DAY), 
  'active',
  0, 
  0, 
  1
),
(
  'FREESHIP', 
  'Envío Gratis', 
  'Envío gratuito en todas las compras', 
  'percentage', 
  0.00, 
  30.00, 
  NULL,
  NULL, 
  NULL, 
  NOW(), 
  NULL, 
  'active',
  0, 
  1, 
  1
),
(
  'VIP25', 
  'VIP 25% OFF', 
  'Descuento exclusivo para clientes VIP', 
  'percentage', 
  25.00, 
  200.00, 
  50.00,
  50, 
  2, 
  NOW(), 
  DATE_ADD(NOW(), INTERVAL 90 DAY), 
  'active',
  1, 
  1, 
  1
),
(
  'CLEARANCE50', 
  'Liquidación 50%', 
  'Mega descuento en productos seleccionados', 
  'percentage', 
  50.00, 
  NULL, 
  100.00,
  200, 
  1, 
  DATE_ADD(NOW(), INTERVAL 7 DAY), 
  DATE_ADD(NOW(), INTERVAL 14 DAY), 
  'inactive',
  0, 
  0, 
  1
);

-- Insertar algunos usos de ejemplo (solo si no existen)
INSERT IGNORE INTO `coupon_usage` (
  `coupon_id`, `customer_id`, `order_id`, `discount_amount`
) VALUES
(1, 1, NULL, 5.50),
(1, 2, NULL, 8.20),
(2, 1, NULL, 20.00),
(3, 3, NULL, 0.00),
(4, 1, NULL, 25.00);

-- Actualizar contador de uso en cupones
UPDATE `coupons` c 
SET `used_count` = (
  SELECT COUNT(*) 
  FROM `coupon_usage` cu 
  WHERE cu.coupon_id = c.id
);
