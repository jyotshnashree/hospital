-- Create Patient Ratings Table
-- Run this SQL to add patient ratings functionality

CREATE TABLE IF NOT EXISTS `patient_ratings` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `patient_id` INT NOT NULL,
  `doctor_id` INT,
  `rating` INT NOT NULL CHECK(rating >= 1 AND rating <= 5),
  `review` TEXT,
  `category` VARCHAR(100),
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY(`patient_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY(`doctor_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
  INDEX `idx_patient_id` (`patient_id`),
  INDEX `idx_doctor_id` (`doctor_id`),
  INDEX `idx_rating` (`rating`),
  INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
