<?php
/**
 * Digital Medical Records (EMR) Module
 * Hospital Management System - Manage patient medical history
 */

include '../db.php';
checkAuth();

// Get patient medical records
$patient_id = $_GET['patient_id'] ?? null;
$patients = $pdo->query("
    SELECT id, full_name, email, phone, age, gender, dob, address, medical_history 
    FROM users 
    WHERE role = 'patient'
    ORDER BY full_name
")->fetchAll();

$selected_patient = null;
$patient_records = null;

if ($patient_id) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? AND role = 'patient'");
    $stmt->execute([$patient_id]);
    $selected_patient = $stmt->fetch();
    
    if ($selected_patient) {
        // Get all medical records for this patient
        $stmt = $pdo->prepare("
            SELECT 
                a.id,
                a.appointment_date,
                a.reason,
                a.notes,
                u.full_name as doctor_name,
                u.specialty,
                p.medication,
                p.dosage,
                p.frequency,
                p.instructions,
                r.report_content,
                a.status
            FROM appointments a
            JOIN users u ON a.doctor_id = u.id
            LEFT JOIN prescriptions p ON a.id = p.appointment_id
            LEFT JOIN reports r ON a.id = r.appointment_id
            WHERE a.patient_id = ?
            ORDER BY a.appointment_date DESC
        ");
        $stmt->execute([$patient_id]);
        $patient_records = $stmt->fetchAll();
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Digital Medical Records (EMR) - Hospital Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../styles.css">
</head>
<body>
<?php include '../inc/header.php'; ?>

<div class="container-fluid py-5">
    <div style="margin-bottom: 2.5rem;">
        <h1 style="font-size: 2.5rem;">📋 Digital Medical Records (EMR)</h1>
        <p class="text-muted">Comprehensive patient health history and records</p>
    </div>

    <div class="row">
        <!-- Patient Selection -->
        <div class="col-lg-3">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">👥 Select Patient</h5>
                </div>
                <div class="list-group list-group-flush">
                    <?php foreach ($patients as $patient): ?>
                        <a href="?patient_id=<?= $patient['id'] ?>" 
                           class="list-group-item list-group-item-action <?= $patient_id == $patient['id'] ? 'active' : '' ?>">
                            <div>
                                <strong><?= htmlspecialchars($patient['full_name']) ?></strong>
                                <br><small class="text-muted"><?= htmlspecialchars($patient['email']) ?></small>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Patient Records -->
        <div class="col-lg-9">
            <?php if ($selected_patient): ?>
                <!-- Patient Info Card -->
                <div class="card shadow mb-4">
                    <div class="card-header bg-info text-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">📋 Patient Information</h5>
                        <button class="btn btn-sm btn-light" onclick="window.print()">🖨️ Print</button>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <p><strong>Full Name:</strong> <?= htmlspecialchars($selected_patient['full_name']) ?></p>
                                <p><strong>Email:</strong> <?= htmlspecialchars($selected_patient['email']) ?></p>
                                <p><strong>Phone:</strong> <?= htmlspecialchars($selected_patient['phone'] ?? 'N/A') ?></p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Date of Birth:</strong> <?= $selected_patient['dob'] ? date('M d, Y', strtotime($selected_patient['dob'])) : 'N/A' ?></p>
                                <p><strong>Age:</strong> <?= htmlspecialchars($selected_patient['age'] ?? 'N/A') ?></p>
                                <p><strong>Gender:</strong> <?= htmlspecialchars($selected_patient['gender'] ?? 'N/A') ?></p>
                            </div>
                        </div>
                        <hr>
                        <p><strong>Address:</strong> <?= htmlspecialchars($selected_patient['address'] ?? 'Not provided') ?></p>
                        <p><strong>Medical History:</strong></p>
                        <div style="background: #f8f9fa; padding: 10px; border-radius: 5px;">
                            <?= htmlspecialchars($selected_patient['medical_history'] ?? 'No medical history recorded') ?>
                        </div>
                    </div>
                </div>

                <!-- Medical Records Timeline -->
                <div class="card shadow">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0">📅 Medical History & Appointments</h5>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($patient_records)): ?>
                            <div class="timeline">
                                <?php foreach ($patient_records as $index => $record): ?>
                                    <div class="timeline-item mb-4" style="position: relative; padding-left: 30px;">
                                        <div style="position: absolute; left: 0; top: 0; width: 20px; height: 20px; 
                                                    background: #667eea; border-radius: 50%; border: 3px solid white;"></div>
                                        
                                        <div class="card">
                                            <div class="card-header" style="background: #f8f9fa;">
                                                <strong>📅 <?= date('M d, Y', strtotime($record['appointment_date'])) ?></strong>
                                                <span class="badge bg-info ms-2"><?= ucfirst($record['status']) ?></span>
                                            </div>
                                            <div class="card-body">
                                                <p><strong>Doctor:</strong> Dr. <?= htmlspecialchars($record['doctor_name']) ?> (<?= htmlspecialchars($record['specialty']) ?>)</p>
                                                <p><strong>Reason:</strong> <?= htmlspecialchars($record['reason'] ?? 'Not specified') ?></p>
                                                <p><strong>Notes:</strong> <?= htmlspecialchars($record['notes'] ?? 'No notes') ?></p>
                                                
                                                <?php if ($record['medication']): ?>
                                                    <div class="mt-3">
                                                        <h6>💊 Prescription</h6>
                                                        <div style="background: #f0f8ff; padding: 10px; border-left: 3px solid #0066cc;">
                                                            <p><strong>Medication:</strong> <?= htmlspecialchars($record['medication']) ?></p>
                                                            <p><strong>Dosage:</strong> <?= htmlspecialchars($record['dosage']) ?></p>
                                                            <p><strong>Frequency:</strong> <?= htmlspecialchars($record['frequency']) ?></p>
                                                            <p><strong>Instructions:</strong> <?= htmlspecialchars($record['instructions']) ?></p>
                                                        </div>
                                                    </div>
                                                <?php endif; ?>
                                                
                                                <?php if ($record['report_content']): ?>
                                                    <div class="mt-3">
                                                        <h6>📊 Medical Report</h6>
                                                        <div style="background: #fffacd; padding: 10px; border-left: 3px solid #ccaa00;">
                                                            <?= nl2br(htmlspecialchars($record['report_content'])) ?>
                                                        </div>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info">
                                No medical records found for this patient.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

            <?php else: ?>
                <div class="alert alert-info">
                    👈 Select a patient from the list to view their medical records
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include '../inc/footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<style>
    .timeline-item:not(:last-child) {
        border-left: 2px solid #e0e0e0;
        margin-left: 9px;
    }
    
    @media print {
        .btn, .list-group-item { display: none; }
    }
</style>
</body>
</html>
