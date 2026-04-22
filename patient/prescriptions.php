<?php
include '../db.php';
checkAuth();
if (getUserRole() !== 'patient') {
    header('Location: ../portals.php');
    exit;
}

$patientId = $_SESSION['user_id'];

// Get prescriptions for this patient through appointments
$stmt = $pdo->prepare('
    SELECT p.*, a.appointment_date, u.full_name as doctor_name
    FROM prescriptions p
    JOIN appointments a ON p.appointment_id = a.id
    JOIN users u ON a.doctor_id = u.id
    WHERE a.patient_id = ?
    ORDER BY p.created_at DESC
');
$stmt->execute([$patientId]);
$prescriptions = $stmt->fetchAll();
?>

<?php include '../inc/header.php'; ?>

<div style="margin-bottom: 2.5rem; animation: slideInUp 0.6s ease-out;">
    <div>
        <h1 style="font-size: 3rem; margin-bottom: 0.5rem;">
            <i class="bi bi-prescription2" style="color: #667eea;"></i> My Prescriptions
        </h1>
        <p class="text-muted" style="font-weight: 500; font-size: 1.1rem;">View all your prescriptions</p>
    </div>
</div>

<!-- Prescriptions List -->
<div class="card shadow p-4">
    <h5 style="font-size: 1.3rem; margin-bottom: 20px;"><i class="bi bi-list-check"></i> All Prescriptions</h5>
    <?php if (!empty($prescriptions)): ?>
        <div class="table-responsive">
            <table class="table table-hover">
                <thead style="background: #f8f9fa;">
                <tr>
                    <th>Medication</th>
                    <th>Doctor</th>
                    <th>Dosage</th>
                    <th>Frequency</th>
                    <th>Duration</th>
                    <th>Date</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($prescriptions as $p): ?>
                    <tr>
                        <td><?= sanitize($p['medication']) ?></td>
                        <td>Dr. <?= sanitize($p['doctor_name']) ?></td>
                        <td><?= sanitize($p['dosage']) ?></td>
                        <td><?= sanitize($p['frequency']) ?></td>
                        <td><?= sanitize($p['duration']) ?></td>
                        <td><?= date('M d, Y', strtotime($p['appointment_date'])) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <p class="text-muted text-center py-4">No prescriptions yet</p>
    <?php endif; ?>
</div>

<?php include '../inc/footer.php'; ?>
