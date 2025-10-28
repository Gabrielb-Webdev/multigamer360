-- =====================================================
-- MULTIGAMER360 - TABLAS PARA SISTEMA DE ÓRDENES
-- =====================================================

-- Crear tabla de órdenes (orders)
CREATE TABLE IF NOT EXISTS `orders` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `order_number` VARCHAR(50) NOT NULL UNIQUE COMMENT 'Número de orden único (MG360-YYYYMMDD-####)',
  `user_id` INT(11) NULL COMMENT 'ID del usuario (NULL si es invitado)',
  
  -- Información del cliente
  `customer_first_name` VARCHAR(100) NOT NULL,
  `customer_last_name` VARCHAR(100) NOT NULL,
  `customer_email` VARCHAR(255) NOT NULL,
  `customer_phone` VARCHAR(20) NOT NULL,
  
  -- Información de envío
  `shipping_address` VARCHAR(255) NULL,
  `shipping_city` VARCHAR(100) NULL,
  `shipping_province` VARCHAR(100) NULL,
  `shipping_postal_code` VARCHAR(10) NULL,
  `shipping_method` VARCHAR(100) NOT NULL COMMENT 'Nombre del método de envío',
  `shipping_cost` DECIMAL(10,2) NOT NULL DEFAULT 0,
  
  -- Información de pago
  `payment_method` VARCHAR(50) NOT NULL,
  `payment_status` ENUM('pending', 'paid', 'failed', 'refunded') DEFAULT 'pending',
  `payment_date` DATETIME NULL,
  
  -- Totales
  `subtotal` DECIMAL(10,2) NOT NULL,
  `discount_amount` DECIMAL(10,2) DEFAULT 0,
  `total_amount` DECIMAL(10,2) NOT NULL,
  
  -- Estado de la orden
  `status` ENUM('pending', 'processing', 'shipped', 'delivered', 'cancelled') DEFAULT 'pending',
  `notes` TEXT NULL COMMENT 'Notas adicionales',
  
  -- Timestamps
  `created_at` DATETIME NOT NULL,
  `updated_at` DATETIME NULL,
  `completed_at` DATETIME NULL,
  
  PRIMARY KEY (`id`),
  KEY `idx_order_number` (`order_number`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_status` (`status`),
  KEY `idx_created_at` (`created_at`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Crear tabla de items de orden (order_items)
CREATE TABLE IF NOT EXISTS `order_items` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `order_id` INT(11) NOT NULL,
  `product_id` INT(11) NOT NULL,
  `product_name` VARCHAR(255) NOT NULL COMMENT 'Nombre del producto al momento de la compra',
  `quantity` INT(11) NOT NULL DEFAULT 1,
  `price` DECIMAL(10,2) NOT NULL COMMENT 'Precio unitario al momento de la compra',
  `subtotal` DECIMAL(10,2) NOT NULL COMMENT 'Precio * Cantidad',
  
  PRIMARY KEY (`id`),
  KEY `idx_order_id` (`order_id`),
  KEY `idx_product_id` (`product_id`),
  FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Actualizar tabla coupon_usage para incluir order_id si no existe
ALTER TABLE `coupon_usage` 
ADD COLUMN IF NOT EXISTS `order_id` INT(11) NULL AFTER `user_id`,
ADD KEY IF NOT EXISTS `idx_order_id` (`order_id`);

-- Agregar created_at si no existe en coupon_usage
ALTER TABLE `coupon_usage`
ADD COLUMN IF NOT EXISTS `created_at` DATETIME NULL AFTER `discount_amount`;

-- Comentarios descriptivos
ALTER TABLE `orders` COMMENT = 'Tabla de órdenes de compra';
ALTER TABLE `order_items` COMMENT = 'Items individuales de cada orden';
