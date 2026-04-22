-- Create Medicines Table for Pharmacy Inventory

CREATE TABLE IF NOT EXISTS `medicines` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(255) NOT NULL,
  `generic_name` VARCHAR(255),
  `description` TEXT,
  `category` VARCHAR(100),
  `dosage` VARCHAR(100),
  `unit` VARCHAR(50),
  `quantity_in_stock` INT DEFAULT 0,
  `reorder_level` INT DEFAULT 10,
  `price` DECIMAL(10, 2),
  `supplier` VARCHAR(255),
  `expiry_date` DATE,
  `manufacturer` VARCHAR(255),
  `is_active` BOOLEAN DEFAULT TRUE,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_name` (`name`),
  INDEX `idx_category` (`category`),
  INDEX `idx_stock` (`quantity_in_stock`),
  INDEX `idx_expiry` (`expiry_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
