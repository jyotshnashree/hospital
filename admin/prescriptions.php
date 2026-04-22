<?php
include '../db.php';
checkAuth();
if (getUserRole() !== 'admin') {
    header('Location: ../portals.php');
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_prescription'])) {
    $appointment_id = $_POST['appointment_id'] ?? '';
    $medication = $_POST['medication'] ?? '';
    $dosage = $_POST['dosage'] ?? '';
    $frequency = $_POST['frequency'] ?? '';
    $duration = $_POST['duration'] ?? '';
    $instructions = $_POST['instructions'] ?? '';

    if ($appointment_id && $medication) {
        try {
            $stmt = $pdo->prepare('
                INSERT INTO prescriptions (appointment_id, medication, dosage, frequency, duration, instructions)
                VALUES (?, ?, ?, ?, ?, ?)
            ');
            $stmt->execute([$appointment_id, $medication, $dosage, $frequency, $duration, $instructions]);
            $_SESSION['success'] = '✅ Prescription added successfully!';
            header('Location: prescriptions.php');
            exit;
        } catch (Exception $e) {
            $_SESSION['error'] = '❌ Error adding prescription: ' . $e->getMessage();
        }
    } else {
        $_SESSION['error'] = '❌ Please fill in all required fields.';
    }
}

// Handle delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $delete_id = $_POST['delete_id'];
    try {
        $stmt = $pdo->prepare('DELETE FROM prescriptions WHERE id = ?');
        $stmt->execute([$delete_id]);
        $_SESSION['success'] = '✅ Prescription deleted successfully!';
        header('Location: prescriptions.php');
        exit;
    } catch (Exception $e) {
        $_SESSION['error'] = '❌ Error deleting prescription: ' . $e->getMessage();
    }
}

// Get all prescriptions with details
$prescriptions = $pdo->query('
    SELECT pr.*, u.full_name AS patient_name, d.full_name AS doctor_name, a.appointment_date, a.reason 
    FROM prescriptions pr 
    JOIN appointments a ON pr.appointment_id = a.id 
    JOIN users u ON a.patient_id = u.id 
    JOIN users d ON a.doctor_id = d.id 
    ORDER BY pr.created_at DESC
')->fetchAll();

// Get all appointments for the modal form
$appointments = $pdo->query('
    SELECT a.id, a.reason, a.appointment_date, 
           CONCAT(p.full_name, " - ", d.full_name) AS appointment_info 
    FROM appointments a 
    JOIN users p ON a.patient_id = p.id 
    JOIN users d ON a.doctor_id = d.id 
    ORDER BY a.appointment_date DESC
')->fetchAll();
?>
<?php include '../inc/header.php'; ?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1>💊 All Prescriptions</h1>
    <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addPrescriptionModal">
        <i class="fas fa-plus"></i> Add Prescription
    </button>
</div>

<?php if (isset($_SESSION['success'])): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?= $_SESSION['success']; unset($_SESSION['success']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if (isset($_SESSION['error'])): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?= $_SESSION['error']; unset($_SESSION['error']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- Statistics Cards -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card bg-info text-white">
            <div class="card-body">
                <h6 class="card-title">Total Prescriptions</h6>
                <h3><?= count($prescriptions) ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-success text-white">
            <div class="card-body">
                <h6 class="card-title">Active Medications</h6>
                <h3><?= count(array_unique(array_column($prescriptions, 'medication'))) ?></h3>
            </div>
        </div>
    </div>
</div>

<div class="card shadow-sm p-4">
    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead>
            <tr>
                <th>#</th>
                <th>Patient</th>
                <th>Doctor</th>
                <th>Appointment</th>
                <th>Medication</th>
                <th>Dosage</th>
                <th>Frequency</th>
                <th>Duration</th>
                <th>Instructions</th>
                <th>Date</th>
                <th>Actions</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($prescriptions as $prescription): ?>
                <tr>
                    <td><span class="badge bg-primary"><?= $prescription['id'] ?></span></td>
                    <td><strong><?= sanitize($prescription['patient_name']) ?></strong></td>
                    <td><?= sanitize($prescription['doctor_name']) ?></td>
                    <td><?= sanitize($prescription['reason'] ?? 'Appointment') ?></td>
                    <td><strong style="color: #667eea;"><?= sanitize($prescription['medication']) ?></strong></td>
                    <td><span class="badge bg-info"><?= sanitize($prescription['dosage']) ?></span></td>
                    <td><?= sanitize($prescription['frequency']) ?></td>
                    <td><?= sanitize($prescription['duration']) ?></td>
                    <td><?= sanitize($prescription['instructions']) ?></td>
                    <td><?= date('M d, Y', strtotime($prescription['created_at'])) ?></td>
                    <td>
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="delete_id" value="<?= $prescription['id'] ?>">
                            <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Delete this prescription?')">
                                <i class="fas fa-trash"></i>
                            </button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($prescriptions)): ?>
                <tr><td colspan="11" class="text-muted text-center py-4">
                    <i class="fas fa-capsules fa-2x mb-2" style="color: #ddd;"></i><br>
                    No prescriptions found.
                </td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add Prescription Modal -->
<div class="modal fade" id="addPrescriptionModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title">💊 Add New Prescription</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <!-- Appointment Selection -->
                    <div class="mb-3">
                        <label class="form-label fw-bold">Appointment <span class="text-danger">*</span></label>
                        <select class="form-select" name="appointment_id" required>
                            <option value="">-- Select Appointment --</option>
                            <?php foreach ($appointments as $appt): ?>
                                <option value="<?= $appt['id'] ?>">
                                    <?= sanitize($appt['appointment_info']) ?> - <?= sanitize($appt['reason']) ?> (<?= date('M d, Y', strtotime($appt['appointment_date'])) ?>)
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
