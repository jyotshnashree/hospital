<?php
/**
 * MySQL Database Setup and Initialization
 * Hospital Management System
 */

require_once __DIR__ . '/config.php';

class MySQLSetup
{
    private $pdo;

    public function __construct()
    {
        try {
            $this->pdo = new PDO(
                "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";charset=" . DB_CHARSET,
                DB_USER,
                DB_PASS,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ]
            );
        } catch (PDOException $e) {
            die('Connection failed: ' . $e->getMessage());
        }
    }

    /**
     * Create Database
     */
    public function createDatabase()
    {
        try {
            $sql = "CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` 
                    CHARACTER SET " . DB_CHARSET . " 
                    COLLATE " . DB_COLLATE;
            $this->pdo->exec($sql);
            echo "✓ Database '" . DB_NAME . "' created successfully!\n";
            return true;
        } catch (PDOException $e) {
            echo "✗ Error creating database: " . $e->getMessage() . "\n";
            return false;
        }
    }

    /**
     * Create All Tables
     */
    public function createTables()
    {
        try {
            $this->pdo->exec("USE `" . DB_NAME . "`");

            // Users Table
            $this->pdo->exec("
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
                ) ENGINE=InnoDB DEFAULT CHARSET=" . DB_CHARSET . " COLLATE=" . DB_COLLATE . "
            ");
            echo "✓ Users table created!\n";

            // Appointments Table
            $this->pdo->exec("
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
                ) ENGINE=InnoDB DEFAULT CHARSET=" . DB_CHARSET . " COLLATE=" . DB_COLLATE . "
            ");
            echo "✓ Appointments table created!\n";

            // Prescriptions Table
            $this->pdo->exec("
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
                ) ENGINE=InnoDB DEFAULT CHARSET=" . DB_CHARSET . " COLLATE=" . DB_COLLATE . "
            ");
            echo "✓ Prescriptions table created!\n";

            // Bills Table
            $this->pdo->exec("
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
                ) ENGINE=InnoDB DEFAULT CHARSET=" . DB_CHARSET . " COLLATE=" . DB_COLLATE . "
            ");
            echo "✓ Bills table created!\n";

            // Payments Table
            $this->pdo->exec("
                CREATE TABLE IF NOT EXISTS `payments` (
                    `id` INT AUTO_INCREMENT PRIMARY KEY,
                    `bill_id` INT NOT NULL,
                    `amount` DECIMAL(10, 2) NOT NULL,
                    `payment_method` ENUM('Cash', 'Credit Card', 'Debit Card', 'Bank Transfer', 'Insurance') DEFAULT 'Cash',
                    `transaction_id` VARCHAR(100),
                    `paid_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY(`bill_id`) REFERENCES `bills`(`id`) ON DELETE CASCADE,
                    INDEX `idx_bill_id` (`bill_id`),
                    INDEX `idx_transaction_id` (`transaction_id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=" . DB_CHARSET . " COLLATE=" . DB_COLLATE . "
            ");
            echo "✓ Payments table created!\n";

            // Reports Table
            $this->pdo->exec("
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
                ) ENGINE=InnoDB DEFAULT CHARSET=" . DB_CHARSET . " COLLATE=" . DB_COLLATE . "
            ");
            echo "✓ Reports table created!\n";

            return true;
        } catch (PDOException $e) {
            echo "✗ Error creating tables: " . $e->getMessage() . "\n";
            return false;
        }
    }

    /**
     * Insert Sample Data
     */
    public function insertSampleData()
    {
        try {
            $this->pdo->exec("USE `" . DB_NAME . "`");

            // Insert Admin
            $stmt = $this->pdo->prepare("INSERT IGNORE INTO `users` (`full_name`, `email`, `password`, `role`) 
                                         VALUES (?, ?, ?, ?)");
            $stmt->execute(['Admin User', 'admin@hospital.com', password_hash('admin123', PASSWORD_BCRYPT), 'admin']);
            echo "✓ Admin user inserted!\n";

            // Insert Doctors
            $doctors = [
                ['Dr. Sarah Miller', 'sarah@hospital.com', '555-1001', 'Cardiology'],
                ['Dr. James Wilson', 'james@hospital.com', '555-1002', 'Neurology'],
                ['Dr. Emily Johnson', 'emily@hospital.com', '555-1003', 'Orthopedics'],
                ['Dr. Michael Brown', 'michael@hospital.com', '555-1004', 'Dermatology'],
                ['Dr. Lisa Anderson', 'lisa@hospital.com', '555-1005', 'Pediatrics'],
            ];

            $stmt = $this->pdo->prepare("INSERT IGNORE INTO `users` (`full_name`, `email`, `password`, `role`, `phone`, `specialty`) 
                                         VALUES (?, ?, ?, ?, ?, ?)");
            foreach ($doctors as $doctor) {
                $stmt->execute([$doctor[0], $doctor[1], password_hash('doctor123', PASSWORD_BCRYPT), 'doctor', $doctor[2], $doctor[3]]);
            }
            echo "✓ " . count($doctors) . " doctors inserted!\n";

            // Insert Patients
            $patients = [
                ['John Smith', 'john@email.com', '555-2001', '1990-05-15', '123 Main St', 34, 'Male', 'Hypertension'],
                ['Mary Johnson', 'mary@email.com', '555-2002', '1985-08-22', '456 Oak Ave', 39, 'Female', 'Diabetes Type 2'],
                ['Robert Williams', 'robert@email.com', '555-2003', '1992-03-10', '789 Pine Rd', 32, 'Male', 'None'],
            ];

            $stmt = $this->pdo->prepare("INSERT IGNORE INTO `users` (`full_name`, `email`, `password`, `role`, `phone`, `dob`, `address`, `age`, `gender`, `medical_history`) 
                                         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            foreach ($patients as $patient) {
                $stmt->execute([$patient[0], $patient[1], password_hash('patient123', PASSWORD_BCRYPT), 'patient', $patient[2], $patient[3], $patient[4], $patient[5], $patient[6], $patient[7]]);
            }
            echo "✓ " . count($patients) . " patients inserted!\n";

            return true;
        } catch (PDOException $e) {
            echo "✗ Error inserting sample data: " . $e->getMessage() . "\n";
            return false;
        }
    }

    /**
     * Run Full Setup
     */
    public function runFullSetup()
    {
        echo "\n========================================\n";
        echo "🏥 Hospital Management System - MySQL Setup\n";
        echo "========================================\n\n";

        if ($this->createDatabase() &&
            $this->createTables() &&
            $this->insertSampleData()) {
            echo "\n✅ Setup completed successfully!\n";
            echo "========================================\n";
            return true;
        } else {
            echo "\n❌ Setup failed!\n";
            echo "========================================\n";
            return false;
        }
    }
}

// Run setup when script is executed
if (php_sapi_name() === 'cli') {
    $setup = new MySQLSetup();
    $setup->runFullSetup();
}
?>
