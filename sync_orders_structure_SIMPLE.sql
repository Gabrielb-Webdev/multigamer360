-- =====================================================
-- SINCRONIZACIÓN SIMPLE DE ESTRUCTURA DE ÓRDENES
-- Sin uso de INFORMATION_SCHEMA
-- =====================================================

-- =====================================================
-- TABLA: orders
-- =====================================================

CREATE TABLE IF NOT EXISTS `orders` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `order_number` VARCHAR(50) NOT NULL,
  `user_id` INT(11) NULL,
  
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
  `shipping_method` VARCHAR(100) NOT NULL,
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
  `notes` TEXT NULL,
  
  -- Información de envío (tracking)
  `tracking_number` VARCHAR(100) NULL,
  `shipped_at` DATETIME NULL,
  `delivered_at` DATETIME NULL,
  
  -- Timestamps
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
  `completed_at` DATETIME NULL,
  
  PRIMARY KEY (`id`),
  UNIQUE KEY `order_number` (`order_number`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_status` (`status`),
  KEY `idx_payment_status` (`payment_status`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA: order_items
-- =====================================================

CREATE TABLE IF NOT EXISTS `order_items` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `order_id` INT(11) NOT NULL,
  `product_id` INT(11) NOT NULL,
  `product_name` VARCHAR(255) NOT NULL,
  `quantity` INT(11) NOT NULL DEFAULT 1,
  `price` DECIMAL(10,2) NOT NULL,
  `subtotal` DECIMAL(10,2) NOT NULL,
  
  PRIMARY KEY (`id`),
  KEY `idx_order_id` (`order_id`),
  KEY `idx_product_id` (`product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLAS OPCIONALES PARA HISTORIAL Y NOTAS
-- =====================================================

-- Tabla para historial de cambios de estado
CREATE TABLE IF NOT EXISTS `order_status_history` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `order_id` INT(11) NOT NULL,
  `old_status` VARCHAR(50) NULL,
  `new_status` VARCHAR(50) NOT NULL,
  `admin_id` INT(11) NULL,
  `note` TEXT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  
  PRIMARY KEY (`id`),
  KEY `idx_order_id` (`order_id`),
  KEY `idx_admin_id` (`admin_id`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla para notas administrativas en órdenes
CREATE TABLE IF NOT EXISTS `order_notes` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `order_id` INT(11) NOT NULL,
  `admin_id` INT(11) NOT NULL,
  `note` TEXT NOT NULL,
  `is_customer_visible` TINYINT(1) DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  
  PRIMARY KEY (`id`),
  KEY `idx_order_id` (`order_id`),
  KEY `idx_admin_id` (`admin_id`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- NOTA: Si las tablas ya existen, este script NO las modificará
-- Para agregar columnas faltantes a tablas existentes,
-- ejecuta manualmente los ALTER TABLE necesarios
-- =====================================================

SELECT 'Tablas de órdenes creadas correctamente' as resultado;
