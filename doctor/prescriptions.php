<?php
include '../db.php';
checkAuth();
if (getUserRole() !== 'doctor') {
    header('Location: ../portals.php');
    exit;
}

// Get all prescriptions
$prescriptions = $pdo->query('
    SELECT p.*, a.appointment_date, u.full_name 
    FROM prescriptions p
    JOIN appointments a ON p.appointment_id = a.id
    JOIN users u ON a.patient_id = u.id
    ORDER BY p.created_at DESC
')->fetchAll();

// Get appointments for form
$appointments = $pdo->query('
    SELECT a.id, a.appointment_date, u.full_name, u.id as patient_id
    FROM appointments a
    JOIN users u ON a.patient_id = u.id
    ORDER BY a.appointment_date DESC
')->fetchAll();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $apt = $_POST['appointment_id'] ?? 0;
    $med = $_POST['medication'] ?? '';
    if ($apt && $med) {
        $pdo->prepare('INSERT INTO prescriptions (appointment_id, medication, dosage, frequency, duration, instructions) VALUES (?,?,?,?,?,?)')
            ->execute([$apt, $med, $_POST['dosage'] ?? '', $_POST['frequency'] ?? '', $_POST['duration'] ?? '', $_POST['instructions'] ?? '']);
        $_SESSION['success'] = 'Prescription added!';
        header('Location: prescriptions.php');
        exit;
    }
}
?>

<?php include '../inc/header.php'; ?>

<div style="margin-bottom: 2rem;">
    <h1><i class="bi bi-prescription2" style="color: #667eea;"></i> Prescriptions</h1>
    <p class="text-muted">Create and manage prescriptions</p>
</div>

<?php if (isset($_SESSION['success'])): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <?= $_SESSION['success'] ?>
        <button class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php unset($_SESSION['success']); ?>
<?php endif; ?>

<!-- Add Prescription -->
<div class="card p-4 mb-4">
    <h5><i class="bi bi-plus-circle"></i> New Prescription</h5>
    <form method="POST">
        <div class="row mb-3">
            <div class="col-md-6">
                <label class="form-label">Appointment</label>
                <select name="appointment_id" class="form-select" required>
                    <option value="">Select</option>
                    <?php foreach ($appointments as $a): ?>
                        <option value="<?= $a['id'] ?>"><?= sanitize($a['full_name']) ?> - <?= $a['appointment_date'] ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label">Medication</label>
                <input type="text" name="medication" class="form-control" required>
            </div>
        </div>
        <div class="row mb-3">
            <div class="col-md-3">
                <label class="form-label">Dosage</label>
                <input type="text" name="dosage" class="form-control">
            </div>
            <div class="col-md-3">
                <label class="form-label">Frequency</label>
                <input type="text" name="frequency" class="form-control">
            </div>
            <div class="col-md-3">
                <label class="form-label">Duration</label>
                <input type="text" name="duration" class="form-control">
            </div>
            <div class="col-md-3">
                <label class="form-label">&nbsp;</label>
                <button type="submit" class="btn btn-success w-100"><i class="bi bi-check"></i> Save</button>
            </div>
        </div>
        <div class="mb-3">
            <label class="form-label">Instructions</label>
            <textarea name="instructions" class="form-control" rows="2"></textarea>
        </div>
    </form>
</div>

<!-- List -->
<div class="card p-4">
    <h5><i class="bi bi-list-check"></i> All Prescriptions</h5>
    <?php if (!empty($prescriptions)): ?>
        <div class="table-responsive">
            <table class="table table-hover">
                <thead style="background: #f8f9fa;">
                <tr>
                    <th>Patient</th>
                    <th>Date</th>
                    <th>Medication</th>
                    <th>Dosage</th>
                    <th>Frequency</th>
                    <th>Duration</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($prescriptions as $p): ?>
                    <tr>
                        <td><?= sanitize($p['full_name']) ?></td>
                        <td><?= $p['appointment_date'] ?></td>
                        <td><?= sanitize($p['medication']) ?></td>
                        <td><?= sanitize($p['dosage']) ?></td>
                        <td><?= sanitize($p['frequency']) ?></td>
                        <td><?= sanitize($p['duration']) ?></td>
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
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title">💊 Add New Prescription</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <!-- Appointment Selection -->
                    <div class="mb-3">
                        <label class="form-label fw-bold">Patient Appointment <span class="text-danger">*</span></label>
                        <select class="form-select" name="appointment_id" required onchange="updatePatientInfo()">
                            <option value="">-- Select Appointment --</option>
                            <?php foreach ($appointments as $appt): ?>
                                <option value="<?= $appt['id'] ?>">
                                    <?= sanitize($appt['patient_name']) ?> - <?= $appt['reason'] ?> (<?= date('M d, Y', strtotime($appt['appointment_date'])) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Medication -->
                    <div class="mb-3">
                        <label class="form-label fw-bold">Medication Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="medication" placeholder="e.g., Amoxicillin" required>
                    </div>

                    <!-- Dosage -->
                    <div class="mb-3">
                        <label class="form-label fw-bold">Dosage</label>
                        <input type="text" class="form-control" name="dosage" placeholder="e.g., 500mg">
                    </div>

                    <!-- Frequency -->
                    <div class="mb-3">
                        <label class="form-label fw-bold">Frequency</label>
                        <select class="form-select" name="frequency">
                            <option value="">-- Select Frequency --</option>
                            <option value="Once daily">Once daily</option>
                            <option value="Twice daily">Twice daily</option>
                            <option value="Three times daily">Three times daily</option>
                            <option value="Four times daily">Four times daily</option>
                            <option value="Every 6 hours">Every 6 hours</option>
                            <option value="Every 8 hours">Every 8 hours</option>
                            <option value="As needed">As needed</option>
                        </select>
                    </div>

                    <!-- Duration -->
                    <div class="mb-3">
                        <label class="form-label fw-bold">Duration</label>
                        <input type="text" class="form-control" name="duration" placeholder="e.g., 7 days, 2 weeks">
                    </div>

                    <!-- Instructions -->
                    <div class="mb-3">
                        <label class="form-label fw-bold">Instructions</label>
                        <textarea class="form-control" name="instructions" rows="3" placeholder="e.g., Take with food, avoid dairy products"></textarea>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="add_prescription" class="btn btn-success">
                        <i class="fas fa-save"></i> Save Prescription
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include '../inc/footer.php'; ?>
