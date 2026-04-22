-- Hospital Management System - Sample Data Insert
-- Copy and paste this entire SQL into phpMyAdmin or MySQL CLI
-- This will populate your database with realistic test data

-- ========================================
-- 1. INSERT SAMPLE PATIENTS
-- ========================================
INSERT IGNORE INTO `users` (`full_name`, `email`, `phone`, `role`, `gender`, `dob`, `address`, `password`)
VALUES 
('John Smith', 'john.smith@email.com', '555-0001', 'patient', 'Male', '1990-05-15', '123 Main St', '$2y$10$0.RfYwX1gIcHPJjfEzGfIuHkN.3vWCwH5lfvD5n1J61mBCzK5t5fm'),
('Sarah Johnson', 'sarah.johnson@email.com', '555-0002', 'patient', 'Female', '1985-03-22', '456 Oak Ave', '$2y$10$0.RfYwX1gIcHPJjfEzGfIuHkN.3vWCwH5lfvD5n1J61mBCzK5t5fm'),
('Michael Davis', 'michael.davis@email.com', '555-0003', 'patient', 'Male', '1992-07-10', '789 Pine Rd', '$2y$10$0.RfYwX1gIcHPJjfEzGfIuHkN.3vWCwH5lfvD5n1J61mBCzK5t5fm'),
('Emily Wilson', 'emily.wilson@email.com', '555-0004', 'patient', 'Female', '1988-11-30', '321 Elm St', '$2y$10$0.RfYwX1gIcHPJjfEzGfIuHkN.3vWCwH5lfvD5n1J61mBCzK5t5fm'),
('David Brown', 'david.brown@email.com', '555-0005', 'patient', 'Male', '1995-01-20', '654 Maple Dr', '$2y$10$0.RfYwX1gIcHPJjfEzGfIuHkN.3vWCwH5lfvD5n1J61mBCzK5t5fm'),
('Jessica Rodriguez', 'jessica.r@email.com', '555-0006', 'patient', 'Female', '1993-09-12', '987 Cedar Ln', '$2y$10$0.RfYwX1gIcHPJjfEzGfIuHkN.3vWCwH5lfvD5n1J61mBCzK5t5fm'),
('Robert Martinez', 'robert.m@email.com', '555-0007', 'patient', 'Male', '1987-06-25', '147 Birch Way', '$2y$10$0.RfYwX1gIcHPJjfEzGfIuHkN.3vWCwH5lfvD5n1J61mBCzK5t5fm'),
('Amanda Taylor', 'amanda.t@email.com', '555-0008', 'patient', 'Female', '1991-04-18', '258 Spruce Ct', '$2y$10$0.RfYwX1gIcHPJjfEzGfIuHkN.3vWCwH5lfvD5n1J61mBCzK5t5fm'),
('Christopher Lee', 'chris.lee@email.com', '555-0009', 'patient', 'Male', '1989-08-05', '369 Ash Pl', '$2y$10$0.RfYwX1gIcHPJjfEzGfIuHkN.3vWCwH5lfvD5n1J61mBCzK5t5fm'),
('Michelle Harris', 'michelle.h@email.com', '555-0010', 'patient', 'Female', '1994-02-14', '741 Walnut St', '$2y$10$0.RfYwX1gIcHPJjfEzGfIuHkN.3vWCwH5lfvD5n1J61mBCzK5t5fm');

-- Password for all patients: patient123

-- ========================================
-- 2. INSERT SAMPLE APPOINTMENTS
-- ========================================
-- Get patient and doctor IDs first, then run these:

-- Completed Appointments (4)
INSERT INTO `appointments` (`patient_id`, `doctor_id`, `appointment_date`, `appointment_time`, `reason`, `notes`, `status`)
SELECT u1.id, u2.id, '2025-12-01', '09:00:00', 'Regular checkup', 'Patient is healthy', 'Completed'
FROM users u1, users u2 WHERE u1.email = 'john.smith@email.com' AND u2.role = 'doctor' LIMIT 1;

INSERT INTO `appointments` (`patient_id`, `doctor_id`, `appointment_date`, `appointment_time`, `reason`, `notes`, `status`)
SELECT u1.id, u2.id, '2025-12-05', '10:30:00', 'Follow-up visit', 'Treatment going well', 'Completed'
FROM users u1, users u2 WHERE u1.email = 'sarah.johnson@email.com' AND u2.role = 'doctor' LIMIT 1;

INSERT INTO `appointments` (`patient_id`, `doctor_id`, `appointment_date`, `appointment_time`, `reason`, `notes`, `status`)
SELECT u1.id, u2.id, '2025-12-10', '14:00:00', 'Lab test results', 'All tests normal', 'Completed'
FROM users u1, users u2 WHERE u1.email = 'michael.davis@email.com' AND u2.role = 'doctor' LIMIT 1;

INSERT INTO `appointments` (`patient_id`, `doctor_id`, `appointment_date`, `appointment_time`, `reason`, `notes`, `status`)
SELECT u1.id, u2.id, '2025-12-15', '11:00:00', 'Medication review', 'Continue current medication', 'Completed'
FROM users u1, users u2 WHERE u1.email = 'emily.wilson@email.com' AND u2.role = 'doctor' LIMIT 1;

-- Approved Appointments (2)
INSERT INTO `appointments` (`patient_id`, `doctor_id`, `appointment_date`, `appointment_time`, `reason`, `notes`, `status`)
SELECT u1.id, u2.id, '2026-04-15', '09:00:00', 'Consultation', 'First appointment', 'Approved'
FROM users u1, users u2 WHERE u1.email = 'david.brown@email.com' AND u2.role = 'doctor' LIMIT 1;

INSERT INTO `appointments` (`patient_id`, `doctor_id`, `appointment_date`, `appointment_time`, `reason`, `notes`, `status`)
SELECT u1.id, u2.id, '2026-04-18', '10:30:00', 'Blood test', 'Annual checkup', 'Approved'
FROM users u1, users u2 WHERE u1.email = 'jessica.r@email.com' AND u2.role = 'doctor' LIMIT 1;

-- Pending Appointments (4)
INSERT INTO `appointments` (`patient_id`, `doctor_id`, `appointment_date`, `appointment_time`, `reason`, `notes`, `status`)
SELECT u1.id, u2.id, '2026-04-20', '14:00:00', 'Emergency visit', 'Urgent consultation needed', 'Pending'
FROM users u1, users u2 WHERE u1.email = 'robert.m@email.com' AND u2.role = 'doctor' LIMIT 1;

INSERT INTO `appointments` (`patient_id`, `doctor_id`, `appointment_date`, `appointment_time`, `reason`, `notes`, `status`)
SELECT u1.id, u2.id, '2026-04-22', '11:00:00', 'Specialist appointment', 'Cardiology consultation', 'Pending'
FROM users u1, users u2 WHERE u1.email = 'amanda.t@email.com' AND u2.role = 'doctor' LIMIT 1;

INSERT INTO `appointments` (`patient_id`, `doctor_id`, `appointment_date`, `appointment_time`, `reason`, `notes`, `status`)
SELECT u1.id, u2.id, '2026-04-25', '15:30:00', 'Physical exam', 'Health assessment', 'Pending'
FROM users u1, users u2 WHERE u1.email = 'chris.lee@email.com' AND u2.role = 'doctor' LIMIT 1;

INSERT INTO `appointments` (`patient_id`, `doctor_id`, `appointment_date`, `appointment_time`, `reason`, `notes`, `status`)
SELECT u1.id, u2.id, '2026-04-28', '09:30:00', 'Dental check', 'Routine checkup', 'Pending'
FROM users u1, users u2 WHERE u1.email = 'michelle.h@email.com' AND u2.role = 'doctor' LIMIT 1;

-- ========================================
-- 3. INSERT SAMPLE PRESCRIPTIONS
-- ========================================
-- Insert prescriptions for the first 4 appointments
INSERT INTO `prescriptions` (`appointment_id`, `medication`, `dosage`, `frequency`, `duration`, `instructions`)
SELECT a.id, 'Amoxicillin', '500mg', 'Once daily', '7 days', 'Take with food'
FROM appointments a WHERE a.reason = 'Regular checkup' LIMIT 1;

INSERT INTO `prescriptions` (`appointment_id`, `medication`, `dosage`, `frequency`, `duration`, `instructions`)
SELECT a.id, 'Ibuprofen', '200mg', 'Twice daily', '5 days', 'Take after meals'
FROM appointments a WHERE a.reason = 'Follow-up visit' LIMIT 1;

INSERT INTO `prescriptions` (`appointment_id`, `medication`, `dosage`, `frequency`, `duration`, `instructions`)
SELECT a.id, 'Aspirin', '100mg', 'Once daily', '30 days', 'Take with water'
FROM appointments a WHERE a.reason = 'Lab test results' LIMIT 1;

INSERT INTO `prescriptions` (`appointment_id`, `medication`, `dosage`, `frequency`, `duration`, `instructions`)
SELECT a.id, 'Vitamin D', '1000 IU', 'Once daily', '90 days', 'Take in morning'
FROM appointments a WHERE a.reason = 'Medication review' LIMIT 1;

-- ========================================
-- 4. INSERT SAMPLE BILLS
-- ========================================
INSERT INTO `bills` (`patient_id`, `appointment_id`, `amount`, `description`, `status`, `due_date`)
SELECT u.id, a.id, 250, 'Consultation fee', 'Pending', DATE_ADD(NOW(), INTERVAL 30 DAY)
FROM users u, appointments a WHERE u.email = 'john.smith@email.com' AND a.reason = 'Regular checkup' LIMIT 1;

INSERT INTO `bills` (`patient_id`, `appointment_id`, `amount`, `description`, `status`, `due_date`)
SELECT u.id, a.id, 150, 'Lab tests', 'Paid', DATE_ADD(NOW(), INTERVAL 30 DAY)
FROM users u, appointments a WHERE u.email = 'sarah.johnson@email.com' AND a.reason = 'Follow-up visit' LIMIT 1;

INSERT INTO `bills` (`patient_id`, `appointment_id`, `amount`, `description`, `status`, `due_date`)
SELECT u.id, a.id, 300, 'Emergency visit', 'Pending', DATE_ADD(NOW(), INTERVAL 30 DAY)
FROM users u, appointments a WHERE u.email = 'michael.davis@email.com' AND a.reason = 'Lab test results' LIMIT 1;

INSERT INTO `bills` (`patient_id`, `appointment_id`, `amount`, `description`, `status`, `due_date`)
SELECT u.id, a.id, 175, 'Physical examination', 'Paid', DATE_ADD(NOW(), INTERVAL 30 DAY)
FROM users u, appointments a WHERE u.email = 'emily.wilson@email.com' AND a.reason = 'Medication review' LIMIT 1;

INSERT INTO `bills` (`patient_id`, `appointment_id`, `amount`, `description`, `status`, `due_date`)
SELECT u.id, a.id, 400, 'Surgery consultation', 'Pending', DATE_ADD(NOW(), INTERVAL 30 DAY)
FROM users u, appointments a WHERE u.email = 'david.brown@email.com' AND a.reason = 'Consultation' LIMIT 1;

INSERT INTO `bills` (`patient_id`, `appointment_id`, `amount`, `description`, `status`, `due_date`)
SELECT u.id, a.id, 225, 'Follow-up treatment', 'Paid', DATE_ADD(NOW(), INTERVAL 30 DAY)
FROM users u, appointments a WHERE u.email = 'jessica.r@email.com' AND a.reason = 'Blood test' LIMIT 1;

-- ========================================
-- 5. INSERT SAMPLE PAYMENTS (for paid bills)
-- ========================================
INSERT INTO `payments` (`bill_id`, `amount`, `payment_method`, `transaction_id`)
SELECT id, amount, 'Credit Card', CONCAT('TXN_', UNIX_TIMESTAMP())
FROM bills WHERE status = 'Paid' AND id NOT IN (SELECT bill_id FROM payments);

-- ========================================
-- 6. VERIFY DATA
-- ========================================
-- Count totals to verify:
SELECT 
    (SELECT COUNT(*) FROM users WHERE role = 'patient') AS 'Total Patients',
    (SELECT COUNT(*) FROM appointments) AS 'Total Appointments',
    (SELECT COUNT(*) FROM appointments WHERE status = 'Pending') AS 'Pending Appointments',
    (SELECT COUNT(*) FROM appointments WHERE status = 'Completed') AS 'Completed Appointments',
    (SELECT COUNT(*) FROM appointments WHERE status = 'Approved') AS 'Approved Appointments',
    (SELECT COUNT(*) FROM bills) AS 'Total Bills',
    (SELECT COUNT(*) FROM bills WHERE status = 'Paid') AS 'Paid Bills',
    (SELECT COUNT(*) FROM prescriptions) AS 'Total Prescriptions',
    (SELECT COUNT(*) FROM payments) AS 'Total Payments';
