-- Hospital Management System - Fix Admin Login Issue
-- Run this SQL to fix invalid password hashes without reimporting the entire schema

-- Update Admin User password hash (admin123)
UPDATE `users` 
SET `password` = '$2y$10$x5cLXh2v8yLI.Q7QW4lJX.E.rHvOqJjVKqC.z2X5TCm4L0bfqzCbe'
WHERE `email` = 'admin@hospital.com' AND `role` = 'admin';

-- Update Doctors password hashes (doctor123)
UPDATE `users` 
SET `password` = '$2y$10$l1L4O6yZy.KqNwX7tBK.H.7tUQZq0ZwJQZmVc2.zQMLT4Q5Q2nPK2'
WHERE `role` = 'doctor';

-- Verify the fix worked
SELECT `email`, `role`, SUBSTRING(`password`, 1, 30) as 'password_preview' FROM `users` WHERE `role` IN ('admin', 'doctor');
