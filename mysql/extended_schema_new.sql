-- Extended Hospital Management System - Additional Tables
-- Add these tables to support new features

USE `hospital`;

-- Messages Table - For Doctor-Patient Communication
CREATE TABLE IF NOT EXISTS `messages` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `sender_id` INT NOT NULL,
  `receiver_id` INT NOT NULL,
  `appointment_id` INT,
  `message_text` LONGTEXT NOT NULL,
  `message_type` ENUM('text', 'voice', 'video') DEFAULT 'text',
  `status` ENUM('sent', 'delivered', 'read') DEFAULT 'sent',
  `is_read` BOOLEAN DEFAULT FALSE,
  `read_at` TIMESTAMP NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY(`sender_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY(`receiver_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY(`appointment_id`) REFERENCES `appointments`(`id`) ON DELETE SET NULL,
  INDEX `idx_sender_id` (`sender_id`),
  INDEX `idx_receiver_id` (`receiver_id`),
  INDEX `idx_created_at` (`created_at`),
  INDEX `idx_is_read` (`is_read`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Doctor Availability / Timetable Table
CREATE TABLE IF NOT EXISTS `doctor_availability` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `doctor_id` INT NOT NULL,
  `day_of_week` ENUM('Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday') NOT NULL,
  `start_time` TIME NOT NULL,
  `end_time` TIME NOT NULL,
  `consultation_duration` INT DEFAULT 30,
  `max_patients_per_day` INT DEFAULT 15,
  `is_active` BOOLEAN DEFAULT TRUE,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY(`doctor_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  INDEX `idx_doctor_id` (`doctor_id`),
  INDEX `idx_day_of_week` (`day_of_week`),
  UNIQUE KEY `uc_doctor_day` (`doctor_id`, `day_of_week`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Doctor Time Slots Table - Individual appointment slots
CREATE TABLE IF NOT EXISTS `time_slots` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `doctor_id` INT NOT NULL,
  `appointment_date` DATE NOT NULL,
  `start_time` TIME NOT NULL,
  `end_time` TIME NOT NULL,
  `is_available` BOOLEAN DEFAULT TRUE,
  `booked_by_patient_id` INT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY(`doctor_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY(`booked_by_patient_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
  INDEX `idx_doctor_id` (`doctor_id`),
  INDEX `idx_appointment_date` (`appointment_date`),
  INDEX `idx_is_available` (`is_available`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Notifications Table
CREATE TABLE IF NOT EXISTS `notifications` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `title` VARCHAR(255) NOT NULL,
  `message` TEXT NOT NULL,
  `type` ENUM('appointment', 'prescription', 'bill', 'message', 'reminder', 'general') DEFAULT 'general',
  `related_id` INT,
  `related_type` VARCHAR(50),
  `is_read` BOOLEAN DEFAULT FALSE,
  `read_at` TIMESTAMP NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY(`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  INDEX `idx_user_id` (`user_id`),
  INDEX `idx_is_read` (`is_read`),
  INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Communication Sessions Table - For video/voice calls
CREATE TABLE IF NOT EXISTS `communication_sessions` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `appointment_id` INT NOT NULL,
  `initiator_id` INT NOT NULL,
  `receiver_id` INT NOT NULL,
  `session_type` ENUM('chat', 'voice', 'video') NOT NULL,
  `session_token` VARCHAR(255),
  `start_time` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `end_time` TIMESTAMP NULL,
  `duration_minutes` INT,
  `status` ENUM('initiated', 'ringing', 'connected', 'ended', 'missed') DEFAULT 'initiated',
  `call_quality` VARCHAR(50),
  `notes` TEXT,
  FOREIGN KEY(`appointment_id`) REFERENCES `appointments`(`id`) ON DELETE CASCADE,
  FOREIGN KEY(`initiator_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY(`receiver_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  INDEX `idx_appointment_id` (`appointment_id`),
  INDEX `idx_initiator_id` (`initiator_id`),
  INDEX `idx_receiver_id` (`receiver_id`),
  INDEX `idx_start_time` (`start_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Pharmacy Inventory Table - Medicine management
CREATE TABLE IF NOT EXISTS `pharmacy_inventory` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `medicine_name` VARCHAR(255) NOT NULL,
  `generic_name` VARCHAR(255),
  `manufacturer` VARCHAR(255),
  `batch_number` VARCHAR(100),
  `quantity` INT NOT NULL,
  `unit` VARCHAR(50),
  `price_per_unit` DECIMAL(10, 2),
  `expiry_date` DATE,
  `storage_location` VARCHAR(100),
  `is_active` BOOLEAN DEFAULT TRUE,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_medicine_name` (`medicine_name`),
  INDEX `idx_quantity` (`quantity`),
  INDEX `idx_expiry_date` (`expiry_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Extended Prescriptions Table with medicine details
ALTER TABLE `prescriptions` ADD COLUMN IF NOT EXISTS `doctor_id` INT AFTER `appointment_id`;
ALTER TABLE `prescriptions` ADD COLUMN IF NOT EXISTS `patient_id` INT AFTER `doctor_id`;
ALTER TABLE `prescriptions` ADD COLUMN IF NOT EXISTS `quantity` INT AFTER `dosage`;
ALTER TABLE `prescriptions` ADD COLUMN IF NOT EXISTS `refills_allowed` INT DEFAULT 0 AFTER `quantity`;
ALTER TABLE `prescriptions` ADD COLUMN IF NOT EXISTS `status` ENUM('active', 'completed', 'expired', 'cancelled') DEFAULT 'active' AFTER `refills_allowed`;
ALTER TABLE `prescriptions` ADD FOREIGN KEY (`doctor_id`) REFERENCES `users`(`id`) ON DELETE SET NULL;
ALTER TABLE `prescriptions` ADD FOREIGN KEY (`patient_id`) REFERENCES `users`(`id`) ON DELETE SET NULL;

-- Add missing columns to users table if not present
ALTER TABLE `users` ADD COLUMN IF NOT EXISTS `registration_type` ENUM('self-registered', 'admin-allocated') DEFAULT 'self-registered';
ALTER TABLE `users` ADD COLUMN IF NOT EXISTS `doctor_id_allocated` VARCHAR(50);
ALTER TABLE `users` ADD COLUMN IF NOT EXISTS `license_number` VARCHAR(100);
ALTER TABLE `users` ADD COLUMN IF NOT EXISTS `qualifications` TEXT;
ALTER TABLE `users` ADD COLUMN IF NOT EXISTS `years_of_experience` INT;
ALTER TABLE `users` ADD COLUMN IF NOT EXISTS `consultation_fee` DECIMAL(10, 2);
ALTER TABLE `users` ADD COLUMN IF NOT EXISTS `profile_image` VARCHAR(255);
ALTER TABLE `users` ADD COLUMN IF NOT EXISTS `bio` TEXT;

-- Add status column to appointments if missing
ALTER TABLE `appointments` ADD COLUMN IF NOT EXISTS `consultation_type` ENUM('online', 'offline') DEFAULT 'online';
ALTER TABLE `appointments` ADD COLUMN IF NOT EXISTS `problem_description` TEXT;

-- Prescription_History Table for tracking prescription refills
CREATE TABLE IF NOT EXISTS `prescription_history` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `prescription_id` INT NOT NULL,
  `action_type` ENUM('created', 'refilled', 'expired', 'cancelled') NOT NULL,
  `refill_count` INT DEFAULT 0,
  `performed_by` INT,
  `notes` TEXT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY(`prescription_id`) REFERENCES `prescriptions`(`id`) ON DELETE CASCADE,
  FOREIGN KEY(`performed_by`) REFERENCES `users`(`id`) ON DELETE SET NULL,
  INDEX `idx_prescription_id` (`prescription_id`),
  INDEX `idx_action_type` (`action_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
