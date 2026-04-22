<?php
/**
 * Sample Data Insertion Script
 * Run this once to populate the database with test data
 */

include __DIR__ . '/../db.php';

// Sample Doctors
$doctors = [
    ['name' => 'Dr. John Smith', 'email' => 'dr.john@hospital.com', 'password' => password_hash('password123', PASSWORD_BCRYPT), 'specialty' => 'Cardiology'],
    ['name' => 'Dr. Sarah Johnson', 'email' => 'dr.sarah@hospital.com', 'password' => password_hash('password123', PASSWORD_BCRYPT), 'specialty' => 'General Surgery'],
    ['name' => 'Dr. Michael Brown', 'email' => 'dr.michael@hospital.com', 'password' => password_hash('password123', PASSWORD_BCRYPT), 'specialty' => 'Orthopedics'],
];

// Sample Patients
$patients = [
    ['name' => 'Alice Williams', 'email' => 'alice@hospital.com', 'password' => password_hash('password123', PASSWORD_BCRYPT), 'age' => 35, 'gender' => 'Female'],
    ['name' => 'Bob Anderson', 'email' => 'bob@hospital.com', 'password' => password_hash('password123', PASSWORD_BCRYPT), 'age' => 45, 'gender' => 'Male'],
    ['name' => 'Carol Martinez', 'email' => 'carol@hospital.com', 'password' => password_hash('password123', PASSWORD_BCRYPT), 'age' => 28, 'gender' => 'Female'],
    ['name' => 'David Taylor', 'email' => 'david@hospital.com', 'password' => password_hash('password123', PASSWORD_BCRYPT), 'age' => 52, 'gender' => 'Male'],
    ['name' => 'Emma Davis', 'email' => 'emma@hospital.com', 'password' => password_hash('password123', PASSWORD_BCRYPT), 'age' => 40, 'gender' => 'Female'],
];

try {
    // Insert Doctors
    echo "Inserting doctors...\n";
    foreach ($doctors as $doctor) {
        $stmt = $pdo->prepare("INSERT INTO users (full_name, email, password, role, specialty) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$doctor['name'], $doctor['email'], $doctor['password'], 'doctor', $doctor['specialty']]);
    }
    echo "✓ Inserted " . count($doctors) . " doctors\n";

    // Insert Patients
    echo "Inserting patients...\n";
    foreach ($patients as $patient) {
        $stmt = $pdo->prepare("INSERT INTO users (full_name, email, password, role, age, gender) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$patient['name'], $patient['email'], $patient['password'], 'patient', $patient['age'], $patient['gender']]);
    }
    echo "✓ Inserted " . count($patients) . " patients\n";

    // Get doctor and patient IDs
    $doctors = $pdo->query("SELECT id FROM users WHERE role = 'doctor'")->fetchAll();
    $patients = $pdo->query("SELECT id FROM users WHERE role = 'patient'")->fetchAll();

    // Insert Sample Appointments
    echo "Inserting appointments...\n";
    $appointments = [
        ['patient_id' => $patients[0]['id'], 'doctor_id' => $doctors[0]['id'], 'date' => date('Y-m-d', strtotime('+5 days')), 'time' => '09:00', 'reason' => 'Chest pain checkup', 'status' => 'Pending'],
        ['patient_id' => $patients[1]['id'], 'doctor_id' => $doctors[1]['id'], 'date' => date('Y-m-d', strtotime('+3 days')), 'time' => '10:30', 'reason' => 'Surgical consultation', 'status' => 'Pending'],
        ['patient_id' => $patients[2]['id'], 'doctor_id' => $doctors[2]['id'], 'date' => date('Y-m-d', strtotime('+7 days')), 'time' => '14:00', 'reason' => 'Knee injury assessment', 'status' => 'Approved'],
        ['patient_id' => $patients[3]['id'], 'doctor_id' => $doctors[0]['id'], 'date' => date('Y-m-d', strtotime('+2 days')), 'time' => '11:00', 'reason' => 'Heart condition follow-up', 'status' => 'Pending'],
        ['patient_id' => $patients[4]['id'], 'doctor_id' => $doctors[1]['id'], 'date' => date('Y-m-d', strtotime('+10 days')), 'time' => '15:30', 'reason' => 'Pre-surgery examination', 'status' => 'Approved'],
        ['patient_id' => $patients[0]['id'], 'doctor_id' => $doctors[2]['id'], 'date' => date('Y-m-d', strtotime('+6 days')), 'time' => '13:00', 'reason' => 'Bone density test', 'status' => 'Completed'],
    ];

    foreach ($appointments as $apt) {
        $stmt = $pdo->prepare("INSERT INTO appointments (patient_id, doctor_id, appointment_date, appointment_time, reason, status) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$apt['patient_id'], $apt['doctor_id'], $apt['date'], $apt['time'], $apt['reason'], $apt['status']]);
    }
    echo "✓ Inserted " . count($appointments) . " appointments\n";

    echo "\n✅ Sample data inserted successfully!\n";
    echo "You can now login with:\n";
    echo "  Admin: Use your existing admin account\n";
    echo "  Doctor: dr.john@hospital.com / password123\n";
    echo "  Patient: alice@hospital.com / password123\n";

} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
