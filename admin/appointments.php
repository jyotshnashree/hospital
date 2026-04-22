<?php
include '../db.php';
checkAuth();
if (getUserRole() !== 'admin') {
    header('Location: ../portals.php');
    exit;
}

$error = '';
$success = '';
$show_form = false;

// Handle adding new appointment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_appointment'])) {
    $patient_id = intval($_POST['patient_id'] ?? 0);
    $doctor_id = intval($_POST['doctor_id'] ?? 0);
    $appointment_date = trim($_POST['appointment_date'] ?? '');
    $appointment_time = trim($_POST['appointment_time'] ?? '');
    $reason = trim($_POST['reason'] ?? '');
    
    if (!$patient_id || !$doctor_id || !$appointment_date || !$appointment_time) {
        $error = 'Please fill in all required fields.';
        $show_form = true;
    } else {
        try {
            // Check if patient exists
            $patientCheck = $pdo->prepare('SELECT id FROM users WHERE id = ? AND role = "patient"');
            $patientCheck->execute([$patient_id]);
            if (!$patientCheck->fetch()) {
                $error = 'Selected patient does not exist.';
                $show_form = true;
            } else {
                // Check if doctor exists
                $doctorCheck = $pdo->prepare('SELECT id FROM users WHERE id = ? AND role = "doctor"');
                $doctorCheck->execute([$doctor_id]);
                if (!$doctorCheck->fetch()) {
                    $error = 'Selected doctor does not exist.';
                    $show_form = true;
                } else {
                    $stmt = $pdo->prepare('INSERT INTO appointments (patient_id, doctor_id, appointment_date, appointment_time, reason, status) VALUES (?, ?, ?, ?, ?, ?)');
                    $result = $stmt->execute([$patient_id, $doctor_id, $appointment_date, $appointment_time, $reason, 'Pending']);
                    
                    if ($result) {
                        $success = 'Appointment scheduled successfully!';
                    } else {
                        $error = 'Failed to schedule appointment. Please try again.';
                        $show_form = true;
                    }
                }
            }
        } catch (PDOException $e) {
            $error = 'Database error: ' . $e->getMessage();
            $show_form = true;
        }
    }
}

if (isset($_GET['show_form'])) {
    $show_form = true;
}

$appointments = $pdo->query('SELECT a.*, p.full_name AS patient_name, d.full_name AS doctor_name FROM appointments a JOIN users p ON a.patient_id = p.id JOIN users d ON a.doctor_id = d.id ORDER BY a.appointment_date DESC, a.appointment_time DESC')->fetchAll();

// Get patients and doctors for dropdown
$patients = $pdo->query('SELECT id, full_name FROM users WHERE role = "patient" ORDER BY full_name')->fetchAll();
$doctors = $pdo->query('SELECT id, full_name FROM users WHERE role = "doctor" ORDER BY full_name')->fetchAll();
?>
<?php include '../inc/header.php'; ?>
<div style="margin-bottom: 2.5rem; animation: slideInUp 0.6s ease-out;">
    <h1 style="font-size: 3rem; margin-bottom: 0.5rem;">
        <i class="bi bi-calendar-check" style="color: #667eea;"></i> All Appointments
    </h1>
    <p class="text-muted" style="font-weight: 500; font-size: 1.1rem;">✨ Manage all scheduled appointments</p>
</div>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div></div>
    <?php if (!$show_form): ?>
        <a href="?show_form=1" class="btn btn-primary btn-lg">
            <i class="bi bi-calendar-plus"></i> Add New Appointment
        </a>
    <?php endif; ?>
</div>

<?php if ($show_form): ?>
    <div class="card shadow p-5 mb-4" style="background: white; border-radius: 15px;">
        <h3 style="margin-bottom: 30px; color: #667eea; font-weight: 700;">
            <i class="bi bi-calendar-plus-fill"></i> Schedule New Appointment
        </h3>
        
        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert" style="margin-bottom: 20px;">
                <i class="bi bi-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <form method="POST" style="max-width: 600px;">
            <div class="mb-3">
                <label style="font-weight: 600; color: #333; margin-bottom: 10px; display: block;">Select Patient *</label>
                <select name="patient_id" style="width: 100%; padding: 12px 15px; border: 2px solid #ddd; border-radius: 8px; font-size: 1rem; color: #333; background: white; cursor: pointer;" required>
                    <option value="">-- Select a Patient --</option>
                    <?php foreach ($patients as $patient): ?>
                        <option value="<?= $patient['id'] ?>"><?= htmlspecialchars($patient['full_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="mb-3">
                <label style="font-weight: 600; color: #333; margin-bottom: 10px; display: block;">Select Doctor *</label>
                <select name="doctor_id" style="width: 100%; padding: 12px 15px; border: 2px solid #ddd; border-radius: 8px; font-size: 1rem; color: #333; background: white; cursor: pointer;" required>
                    <option value="">-- Select a Doctor --</option>
                    <?php foreach ($doctors as $doctor): ?>
                        <option value="<?= $doctor['id'] ?>">Dr. <?= htmlspecialchars($doctor['full_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label style="font-weight: 600; color: #333; margin-bottom: 10px; display: block;">Appointment Date *</label>
                    <input type="date" name="appointment_date" style="width: 100%; padding: 12px 15px; border: 2px solid #ddd; border-radius: 8px; font-size: 1rem; color: #333; background: white; cursor: pointer;" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label style="font-weight: 600; color: #333; margin-bottom: 10px; display: block;">Appointment Time *</label>
                    <input type="time" name="appointment_time" style="width: 100%; padding: 12px 15px; border: 2px solid #ddd; border-radius: 8px; font-size: 1rem; color: #333; background: white; cursor: pointer;" required>
                </div>
            </div>

            <div class="mb-4">
                <label style="font-weight: 600; color: #333; margin-bottom: 10px; display: block;">Reason for Visit</label>
                <textarea name="reason" style="width: 100%; padding: 12px 15px; border: 2px solid #ddd; border-radius: 8px; font-size: 1rem; color: #333; background: white; font-family: Arial, sans-serif; resize: vertical;" rows="4" placeholder="Enter the reason for the appointment..."></textarea>
            </div>

            <div style="display: flex; gap: 10px;">
                <button type="submit" name="add_appointment" value="1" class="btn btn-success btn-lg" style="flex: 1;">
                    <i class="bi bi-check-circle-fill"></i> Schedule Appointment
                </button>
                <a href="appointments.php" class="btn btn-secondary btn-lg" style="flex: 1; text-decoration: none;">
                    <i class="bi bi-x-circle-fill"></i> Cancel
                </a>
            </div>
        </form>
    </div>
<?php endif; ?>

<?php if ($success): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert" style="margin-bottom: 20px;">
        <i class="bi bi-check-circle"></i> <?= htmlspecialchars($success) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="card shadow p-4">
    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead>
            <tr>
                <th style="font-weight: 700; color: white;">#️⃣ ID</th>
                <th style="font-weight: 700; color: white;">👤 Patient</th>
                <th style="font-weight: 700; color: white;">👨‍⚕️ Doctor</th>
                <th style="font-weight: 700; color: white;">📅 Date</th>
                <th style="font-weight: 700; color: white;">⏰ Time</th>
                <th style="font-weight: 700; color: white;">🏷️ Status</th>
                <th style="font-weight: 700; color: white;">📝 Reason</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($appointments as $appointment): ?>
                <tr>
                    <td><strong><?= $appointment['id'] ?></strong></td>
                    <td><?= sanitize($appointment['patient_name']) ?></td>
                    <td><?= sanitize($appointment['doctor_name']) ?></td>
                    <td><?= sanitize($appointment['appointment_date']) ?></td>
                    <td><?= sanitize($appointment['appointment_time']) ?></td>
                    <td>
                        <?php if ($appointment['status'] === 'Pending'): ?>
                            <span class="badge badge-primary">⏳ <?= sanitize($appointment['status']) ?></span>
                        <?php elseif ($appointment['status'] === 'Approved'): ?>
                            <span class="badge" style="background: linear-gradient(135deg, #84fab0 0%, #8fd3f4 100%);">✅ <?= sanitize($appointment['status']) ?></span>
                        <?php elseif ($appointment['status'] === 'Completed'): ?>
                            <span class="badge" style="background: linear-gradient(135deg, #84fab0 0%, #8fd3f4 100%);">✅ <?= sanitize($appointment['status']) ?></span>
                        <?php else: ?>
                            <span class="badge" style="background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);">❌ <?= sanitize($appointment['status']) ?></span>
                        <?php endif; ?>
                    </td>
                    <td><?= sanitize($appointment['reason']) ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($appointments)): ?>
                <tr><td colspan="7" class="text-muted text-center py-4"><em>No appointments scheduled yet. 📭</em></td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include '../inc/footer.php'; ?>

