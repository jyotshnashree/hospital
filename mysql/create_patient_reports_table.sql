-- Create Patient Reports Table
-- Run this SQL to add patient reports functionality

CREATE TABLE IF NOT EXISTS `patient_reports` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `patient_id` INT NOT NULL,
  `report_type` VARCHAR(100) NOT NULL,
  `content` LONGTEXT NOT NULL,
  `appointment_id` INT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY(`patient_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY(`appointment_id`) REFERENCES `appointments`(`id`) ON DELETE SET NULL,
  INDEX `idx_patient_id` (`patient_id`),
  INDEX `idx_created_at` (`created_at`),
  INDEX `idx_appointment_id` (`appointment_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
