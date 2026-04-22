-- Hospital Management System - MySQL Database Schema
-- Create this database and tables using phpMyAdmin or MySQL CLI

-- Create Database
CREATE DATABASE IF NOT EXISTS `hospital` 
CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;

USE `hospital`;

-- Users Table - Stores Admin, Doctors, and Patients
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `full_name` VARCHAR(255) NOT NULL,
  `email` VARCHAR(255) UNIQUE NOT NULL,
  `password` VARCHAR(255) NOT NULL,
  `role` ENUM('admin', 'doctor', 'patient') NOT NULL,
  `phone` VARCHAR(20),
  `specialty` VARCHAR(100),
  `age` INT,
  `gender` ENUM('Male', 'Female', 'Other'),
  `medical_history` TEXT,
  `dob` DATE,
  `address` TEXT,
  `is_active` BOOLEAN DEFAULT TRUE,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_email` (`email`),
  INDEX `idx_role` (`role`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Appointments Table
CREATE TABLE IF NOT EXISTS `appointments` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `patient_id` INT NOT NULL,
  `doctor_id` INT NOT NULL,
  `appointment_date` DATE NOT NULL,
  `appointment_time` TIME NOT NULL,
  `reason` TEXT,
  `status` ENUM('Pending', 'Approved', 'Completed', 'Cancelled') DEFAULT 'Pending',
  `notes` TEXT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY(`patient_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY(`doctor_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  INDEX `idx_patient_id` (`patient_id`),
  INDEX `idx_doctor_id` (`doctor_id`),
  INDEX `idx_appointment_date` (`appointment_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Prescriptions Table
CREATE TABLE IF NOT EXISTS `prescriptions` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `appointment_id` INT NOT NULL,
  `medication` VARCHAR(255) NOT NULL,
  `dosage` VARCHAR(100),
  `duration` VARCHAR(100),
  `instructions` TEXT,
  `frequency` VARCHAR(100),
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY(`appointment_id`) REFERENCES `appointments`(`id`) ON DELETE CASCADE,
  INDEX `idx_appointment_id` (`appointment_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Bills Table
CREATE TABLE IF NOT EXISTS `bills` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `patient_id` INT NOT NULL,
  `appointment_id` INT,
  `amount` DECIMAL(10, 2) NOT NULL,
  `description` TEXT,
  `status` ENUM('pending', 'paid', 'cancelled') DEFAULT 'pending',
  `due_date` DATE,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY(`patient_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY(`appointment_id`) REFERENCES `appointments`(`id`) ON DELETE SET NULL,
  INDEX `idx_patient_id` (`patient_id`),
  INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Payments Table
CREATE TABLE IF NOT EXISTS `payments` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `bill_id` INT NOT NULL,
  `amount` DECIMAL(10, 2) NOT NULL,
  `payment_method` VARCHAR(50) DEFAULT 'Cash',
  `transaction_id` VARCHAR(100),
  `paid_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY(`bill_id`) REFERENCES `bills`(`id`) ON DELETE CASCADE,
  INDEX `idx_bill_id` (`bill_id`),
  INDEX `idx_transaction_id` (`transaction_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Reports Table
CREATE TABLE IF NOT EXISTS `reports` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `appointment_id` INT NOT NULL,
  `report_type` VARCHAR(100),
  `report_content` LONGTEXT,
  `lab_results` LONGTEXT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY(`appointment_id`) REFERENCES `appointments`(`id`) ON DELETE CASCADE,
  INDEX `idx_appointment_id` (`appointment_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create Indexes for Better Performance
ALTER TABLE `users` ADD UNIQUE INDEX `uc_email` (`email`);
ALTER TABLE `appointments` ADD INDEX `idx_status` (`status`);
ALTER TABLE `bills` ADD INDEX `idx_created_at` (`created_at`);
ALTER TABLE `payments` ADD INDEX `idx_created_at` (`created_at`);

-- Insert Admin User
-- Password hash for 'admin123' using bcrypt
INSERT INTO `users` (`full_name`, `email`, `password`, `role`) 
VALUES ('Admin User', 'admin@hospital.com', '$2y$10$x5cLXh2v8yLI.Q7QW4lJX.E.rHvOqJjVKqC.z2X5TCm4L0bfqzCbe', 'admin')
ON DUPLICATE KEY UPDATE `email`=`email`;

-- Insert Sample Doctors
-- Password hash for 'doctor123' using bcrypt
INSERT INTO `users` (`full_name`, `email`, `password`, `role`, `phone`, `specialty`) VALUES
('Dr. Sarah Miller', 'sarah@hospital.com', '$2y$10$l1L4O6yZy.KqNwX7tBK.H.7tUQZq0ZwJQZmVc2.zQMLT4Q5Q2nPK2', 'doctor', '555-1001', 'Cardiology'),
('Dr. James Wilson', 'james@hospital.com', '$2y$10$l1L4O6yZy.KqNwX7tBK.H.7tUQZq0ZwJQZmVc2.zQMLT4Q5Q2nPK2', 'doctor', '555-1002', 'Neurology'),
('Dr. Emily Johnson', 'emily@hospital.com', '$2y$10$l1L4O6yZy.KqNwX7tBK.H.7tUQZq0ZwJQZmVc2.zQMLT4Q5Q2nPK2', 'doctor', '555-1003', 'Orthopedics'),
('Dr. Michael Brown', 'michael@hospital.com', '$2y$10$l1L4O6yZy.KqNwX7tBK.H.7tUQZq0ZwJQZmVc2.zQMLT4Q5Q2nPK2', 'doctor', '555-1004', 'Dermatology'),
('Dr. Lisa Anderson', 'lisa@hospital.com', '$2y$10$l1L4O6yZy.KqNwX7tBK.H.7tUQZq0ZwJQZmVc2.zQMLT4Q5Q2nPK2', 'doctor', '555-1005', 'Pediatrics')
ON DUPLICATE KEY UPDATE `email`=`email`;
