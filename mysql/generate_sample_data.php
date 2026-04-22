<?php
/**
 * Hospital Management System - Sample Data Generator
 * Populates database with test data for all features
 */

include 'db.php';

try {
    echo "🏥 Hospital Management System - Sample Data Generator\n";
    echo "====================================================\n\n";

    // 1. Insert Sample Patients
    echo "📝 Creating sample patients...\n";
    $patients = [
        ['John Smith', 'john.smith@email.com', '555-0001', 'Male', '1990-05-15', '123 Main St'],
        ['Sarah Johnson', 'sarah.johnson@email.com', '555-0002', 'Female', '1985-03-22', '456 Oak Ave'],
        ['Michael Davis', 'michael.davis@email.com', '555-0003', 'Male', '1992-07-10', '789 Pine Rd'],
        ['Emily Wilson', 'emily.wilson@email.com', '555-0004', 'Female', '1988-11-30', '321 Elm St'],
        ['David Brown', 'david.brown@email.com', '555-0005', 'Male', '1995-01-20', '654 Maple Dr'],
        ['Jessica Rodriguez', 'jessica.r@email.com', '555-0006', 'Female', '1993-09-12', '987 Cedar Ln'],
        ['Robert Martinez', 'robert.m@email.com', '555-0007', 'Male', '1987-06-25', '147 Birch Way'],
        ['Amanda Taylor', 'amanda.t@email.com', '555-0008', 'Female', '1991-04-18', '258 Spruce Ct'],
        ['Christopher Lee', 'chris.lee@email.com', '555-0009', 'Male', '1989-08-05', '369 Ash Pl'],
        ['Michelle Harris', 'michelle.h@email.com', '555-0010', 'Female', '1994-02-14', '741 Walnut St'],
    ];

    $patient_ids = [];
    foreach ($patients as $patient) {
        $stmt = $pdo->prepare("
            INSERT IGNORE INTO users (full_name, email, phone, role, gender, dob, address, password)
            VALUES (?, ?, ?, 'patient', ?, ?, ?, ?)
        ");
        // Use a common password hash for all: 'patient123'
        $password_hash = password_hash('patient123', PASSWORD_BCRYPT);
        $stmt->execute([$patient[0], $patient[1], $patient[2], $patient[3], $patient[4], $patient[5], $password_hash]);
        
        // Get the inserted patient ID
        $id_stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $id_stmt->execute([$patient[1]]);
        $id_result = $id_stmt->fetch();
        if ($id_result) {
            $patient_ids[] = $id_result['id'];
        }
    }
    echo "✅ Created " . count($patient_ids) . " patients\n\n";

    // 2. Get doctor IDs
    echo "👨‍⚕️ Getting doctor information...\n";
    $doctors = $pdo->query("SELECT id FROM users WHERE role = 'doctor'")->fetchAll(PDO::FETCH_COLUMN);
    echo "✅ Found " . count($doctors) . " doctors\n\n";

    if (empty($doctors)) {
        echo "⚠️ No doctors found. Please run the main schema.sql first.\n";
        exit(1);
    }

    // 3. Create Sample Appointments
    echo "📅 Creating sample appointments...\n";
    $appointment_data = [
        // Completed appointments (past dates)
        ['Completed', '2025-12-01', '09:00:00', 'Regular checkup', 'Patient is healthy'],
        ['Completed', '2025-12-05', '10:30:00', 'Follow-up visit', 'Treatment going well'],
        ['Completed', '2025-12-10', '14:00:00', 'Lab test results', 'All tests normal'],
        ['Completed', '2025-12-15', '11:00:00', 'Medication review', 'Continue current medication'],
        
        // Approved appointments (near future)
        ['Approved', '2026-04-15', '09:00:00', 'Consultation', 'First appointment'],
        ['Approved', '2026-04-18', '10:30:00', 'Blood test', 'Annual checkup'],
        
        // Pending appointments (upcoming)
        ['Pending', '2026-04-20', '14:00:00', 'Emergency visit', 'Urgent consultation needed'],
        ['Pending', '2026-04-22', '11:00:00', 'Specialist appointment', 'Cardiology consultation'],
        ['Pending', '2026-04-25', '15:30:00', 'Physical exam', 'Health assessment'],
        ['Pending', '2026-04-28', '09:30:00', 'Dental check', 'Routine checkup'],
    ];

    $appointment_ids = [];
    foreach ($appointment_data as $index => $appt) {
        if ($index < count($patient_ids) && $index < count($doctors)) {
            $stmt = $pdo->prepare("
                INSERT INTO appointments (patient_id, doctor_id, appointment_date, appointment_time, reason, notes, status)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $patient_ids[$index],
                $doctors[$index % count($doctors)],
                $appt[1],
                $appt[2],
                $appt[3],
                $appt[4],
                $appt[0]
            ]);
            $appointment_ids[] = $pdo->lastInsertId();
        }
    }
    echo "✅ Created " . count($appointment_ids) . " appointments\n\n";

    // 4. Create Sample Prescriptions
    echo "💊 Creating sample prescriptions...\n";
    $prescription_data = [
        ['Amoxicillin', '500mg', 'Once daily', '7 days', 'Take with food'],
        ['Ibuprofen', '200mg', 'Twice daily', '5 days', 'Take after meals'],
        ['Aspirin', '100mg', 'Once daily', '30 days', 'Take with water'],
        ['Vitamin D', '1000 IU', 'Once daily', '90 days', 'Take in morning'],
    ];

    foreach ($prescription_data as $index => $rx) {
        if ($index < count($appointment_ids)) {
            $stmt = $pdo->prepare("
                INSERT INTO prescriptions (appointment_id, medication, dosage, frequency, duration, instructions)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $appointment_ids[$index],
                $rx[0],
                $rx[1],
                $rx[2],
                $rx[3],
                $rx[4]
            ]);
        }
    }
    echo "✅ Created sample prescriptions\n\n";

    // 5. Create Sample Bills
    echo "💳 Creating sample bills...\n";
    $bill_amounts = [250, 150, 300, 175, 400, 225];
    $bill_descriptions = [
        'Consultation fee',
        'Lab tests',
        'Emergency visit',
        'Physical examination',
        'Surgery consultation',
        'Follow-up treatment'
    ];
    $bill_statuses = ['pending', 'pending', 'paid', 'pending', 'paid', 'pending'];

    foreach ($appointment_ids as $index => $appt_id) {
        if ($index < count($patient_ids)) {
            $stmt = $pdo->prepare("
                INSERT INTO bills (patient_id, appointment_id, amount, description, status, due_date)
                VALUES (?, ?, ?, ?, ?, DATE_ADD(NOW(), INTERVAL 30 DAY))
            ");
            $stmt->execute([
                $patient_ids[$index],
                $appt_id,
                $bill_amounts[$index % count($bill_amounts)],
                $bill_descriptions[$index % count($bill_descriptions)],
                $bill_statuses[$index % count($bill_statuses)]
            ]);
        }
    }
    echo "✅ Created sample bills\n\n";

    // 6. Create Sample Payments (for paid bills)
    echo "💰 Creating sample payments...\n";
    $paid_bills = $pdo->query("SELECT id, amount FROM bills WHERE status = 'paid'")->fetchAll();
    foreach ($paid_bills as $bill) {
        $stmt = $pdo->prepare("
            INSERT INTO payments (bill_id, amount, payment_method, transaction_id)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([
            $bill['id'],
            $bill['amount'],
            'Credit Card',
            'TXN_' . uniqid()
        ]);
    }
    echo "✅ Created " . count($paid_bills) . " payment records\n\n";

    // 7. Display Statistics
    echo "📊 FINAL STATISTICS\n";
    echo "==================\n\n";

    $stats = [
        'Total Patients' => $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'patient'")->fetchColumn(),
        'Total Doctors' => $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'doctor'")->fetchColumn(),
        'Total Appointments' => $pdo->query("SELECT COUNT(*) FROM appointments")->fetchColumn(),
        'Pending Appointments' => $pdo->query("SELECT COUNT(*) FROM appointments WHERE status = 'Pending'")->fetchColumn(),
        'Approved Appointments' => $pdo->query("SELECT COUNT(*) FROM appointments WHERE status = 'Approved'")->fetchColumn(),
        'Completed Appointments' => $pdo->query("SELECT COUNT(*) FROM appointments WHERE status = 'Completed'")->fetchColumn(),
        'Cancelled Appointments' => $pdo->query("SELECT COUNT(*) FROM appointments WHERE status = 'Cancelled'")->fetchColumn(),
        'Total Bills' => $pdo->query("SELECT COUNT(*) FROM bills")->fetchColumn(),
        'Pending Bills' => $pdo->query("SELECT COUNT(*) FROM bills WHERE status = 'pending'")->fetchColumn(),
        'Paid Bills' => $pdo->query("SELECT COUNT(*) FROM bills WHERE status = 'paid'")->fetchColumn(),
        'Total Prescriptions' => $pdo->query("SELECT COUNT(*) FROM prescriptions")->fetchColumn(),
        'Total Payments' => $pdo->query("SELECT COUNT(*) FROM payments")->fetchColumn(),
        'Total Revenue' => $pdo->query("SELECT COALESCE(SUM(amount), 0) FROM payments")->fetchColumn(),
    ];

    foreach ($stats as $key => $value) {
        if (strpos($key, 'Revenue') !== false) {
            echo "💰 $key: \$$value\n";
        } else {
            echo "✓ $key: $value\n";
        }
    }

    echo "\n✅ Sample data generation complete!\n\n";
    echo "🌐 You can now view the statistics at:\n";
    echo "   http://localhost/hospital/statistics.php\n";
    echo "   http://localhost/hospital/admin/dashboard.php\n";
    echo "   http://localhost/hospital/admin/notifications.php\n\n";

    echo "🔐 Test Credentials:\n";
    echo "   Admin:    admin@hospital.com / admin123\n";
    echo "   Doctor:   sarah@hospital.com / doctor123\n";
    echo "   Patient:  john.smith@email.com / patient123\n\n";

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>
