-- Extended Hospital Management System - New Tables for Advanced Features
-- Run this SQL to add new functionality:

-- Notifications Table
CREATE TABLE IF NOT EXISTS `notifications` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `type` ENUM('email', 'sms', 'both') DEFAULT 'email',
  `message` LONGTEXT,
  `status` ENUM('pending', 'sent', 'failed') DEFAULT 'pending',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY(`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  INDEX `idx_user_id` (`user_id`),
  INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Medical Records Extension
ALTER TABLE `users` ADD COLUMN IF NOT EXISTS `medical_history` LONGTEXT AFTER `address`;
ALTER TABLE `users` ADD COLUMN IF NOT EXISTS `dob` DATE AFTER `age`;

-- Payments Table (if not exists)
CREATE TABLE IF NOT EXISTS `payments` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `bill_id` INT NOT NULL,
  `amount` DECIMAL(10, 2) NOT NULL,
  `payment_method` ENUM('Cash', 'Credit Card', 'Debit Card', 'Bank Transfer', 'Insurance', 'Online Wallet') DEFAULT 'Cash',
  `transaction_id` VARCHAR(100) UNIQUE,
  `paid_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY(`bill_id`) REFERENCES `bills`(`id`) ON DELETE CASCADE,
  INDEX `idx_bill_id` (`bill_id`),
  INDEX `idx_transaction_id` (`transaction_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Analytics View for Dashboard
CREATE VIEW IF NOT EXISTS `hospital_analytics` AS
SELECT 
  (SELECT COUNT(*) FROM users WHERE role = 'patient') as total_patients,
  (SELECT COUNT(*) FROM users WHERE role = 'doctor') as total_doctors,
  (SELECT COUNT(*) FROM appointments) as total_appointments,
  (SELECT COUNT(*) FROM appointments WHERE status = 'Pending') as pending_appointments,
  (SELECT SUM(amount) FROM payments) as total_revenue,
  (SELECT COUNT(*) FROM bills) as total_bills;

-- Create indexes for better performance
CREATE INDEX IF NOT EXISTS `idx_appointment_status` ON `appointments`(`status`);
CREATE INDEX IF NOT EXISTS `idx_bill_status` ON `bills`(`status`);
CREATE INDEX IF NOT EXISTS `idx_payment_date` ON `payments`(`paid_at`);

-- Display success
SELECT 'Extended database schema created successfully!' as status;
