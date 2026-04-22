-- Add license_number column to users table for doctor registration
ALTER TABLE `users` ADD COLUMN IF NOT EXISTS `license_number` VARCHAR(100) AFTER `specialty`;
