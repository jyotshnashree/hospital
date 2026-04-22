<?php
/**
 * Patient - Book Appointment
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include '../db.php'; // Include only ONCE

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'patient') {
    header('Location: ../login.php?role=patient');
    exit;
}

$patientId = $_SESSION['user_id'];
$doctorId  = $_GET['doctor_id'] ?? null;
$message   = '';

// Handle appointment booking
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['book_appointment'])) {
    $doctorId        = $_POST['doctor_id']        ?? null;
    $appointmentDate = $_POST['appointment_date'] ?? '';
    $appointmentTime = $_POST['appointment_time'] ?? '';
    $reason          = trim($_POST['reason']      ?? '');
    $consultationType = $_POST['consultation_type'] ?? 'online';

    if (!$doctorId || !$appointmentDate || !$appointmentTime) {
        $message = 'Please select a doctor, date, and time.';
    } else {
        try {
            $stmt = $pdo->prepare('
                INSERT INTO appointments
                    (patient_id, doctor_id, appointment_date, appointment_time, reason, consultation_type, status)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ');
            $stmt->execute([
                $patientId,
                $doctorId,
                $appointmentDate,
                $appointmentTime,
                $reason,
                $consultationType,
                'Pending'
            ]);

            $message = 'Appointment booked successfully! Please wait for doctor approval.';
            header('Refresh: 2; url=appointments.php');
        } catch (Exception $e) {
            $message = 'Error booking appointment: ' . $e->getMessage();
        }
    }
}

// Get selected doctor details if doctor_id is provided
$doctor = null;
if ($doctorId) {
    $doctorStmt = $pdo->prepare('SELECT * FROM users WHERE id = ? AND role = "doctor"');
    $doctorStmt->execute([$doctorId]);
    $doctor = $doctorStmt->fetch();
}

// Get all active doctors for dropdown
$doctorsStmt = $pdo->query('SELECT id, full_name, specialty FROM users WHERE role = "doctor" AND is_active = TRUE ORDER BY full_name');
$doctors = $doctorsStmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Appointment - Patient Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        body { background: #f5f7fa; }

        /* ── Header ── */
        .header {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            padding: 20px;
        }
        .header-inner { max-width: 700px; margin: 0 auto; }
        .header h1  { margin: 0; }
        .header p   { margin: 5px 0 0 0; opacity: .85; }

        /* ── Back nav ── */
        .back-nav {
            max-width: 700px;
            margin: 20px auto 0;
            padding: 0 20px;
        }
        .back-nav a {
            color: #10b981;
            text-decoration: none;
            font-weight: 600;
        }
        .back-nav a:hover { text-decoration: underline; }

        /* ── Main container ── */
        .container-custom { max-width: 700px; margin: 0 auto; padding: 20px 20px 40px; }

        /* ── Form card ── */
        .form-section {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 2px 12px rgba(0,0,0,.07);
        }
        .form-label  { font-weight: 600; color: #333; margin-bottom: 6px; }
        .form-control,
        .form-select {
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            padding: 10px 15px;
            transition: border-color .2s, box-shadow .2s;
        }
        .form-control:focus,
        .form-select:focus {
            outline: none;
            border-color: #10b981;
            box-shadow: 0 0 0 3px rgba(16,185,129,.12);
        }

        /* ── Doctor info banner ── */
        .doctor-info {
            background: #f0fdf4;
            padding: 15px 18px;
            border-radius: 8px;
            margin-bottom: 22px;
            border-left: 5px solid #10b981;
        }
        .doctor-info strong { color: #065f46; }
        .doctor-info small  { color: #555; display: block; margin-top: 3px; }

        /* ── Buttons ── */
        .btn-book {
            background: #10b981;
            color: white;
            padding: 12px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            font-size: 1rem;
            transition: background .2s;
        }
        .btn-book:hover { background: #059669; color: white; }

        .alert { margin-bottom: 20px; }
    </style>
</head>
<body>

<!-- ══ Header ══════════════════════════════════════════════════════════════ -->
<div class="header">
    <div class="header-inner">
        <h1><i class="bi bi-calendar-plus"></i> Book Appointment</h1>
        <p>Schedule a consultation with a doctor</p>
    </div>
</div>

<!-- ══ Back link ════════════════════════════════════════════════════════════ -->
<div class="back-nav">
    <a href="dashboard.php"><i class="bi bi-arrow-left"></i> Back to Dashboard</a>
    &nbsp;/&nbsp;
    <a href="doctors.php"><i class="bi bi-people"></i> Browse Doctors</a>
</div>

<!-- ══ Main content ══════════════════════════════════════════════════════════ -->
<div class="container-custom">

    <!-- Alert message -->
    <?php if ($message): ?>
        <?php $isSuccess = strpos($message, 'successfully') !== false; ?>
        <div class="alert <?= $isSuccess ? 'alert-success' : 'alert-danger' ?> alert-dismissible fade show" role="alert">
            <i class="bi <?= $isSuccess ? 'bi-check-circle-fill' : 'bi-exclamation-circle-fill' ?> me-2"></i>
            <?= htmlspecialchars($message) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Form card -->
    <div class="form-section">
        <h5 class="mb-4" style="color:#10b981;"><i class="bi bi-clipboard2-pulse"></i> Appointment Details</h5>

        <form method="POST">

            <!-- Doctor selector -->
            <div class="mb-3">
                <label class="form-label">Select Doctor <span class="text-danger">*</span></label>
                <select name="doctor_id" id="doctorSelect" class="form-select" required
                        onchange="redirectWithDoctor(this.value)">
                    <option value="">-- Choose a Doctor --</option>
                    <?php foreach ($doctors as $doc): ?>
                        <option value="<?= $doc['id'] ?>"
                            <?= ($doctorId == $doc['id']) ? 'selected' : '' ?>>
                            Dr. <?= htmlspecialchars($doc['full_name']) ?>
                            (<?= htmlspecialchars($doc['specialty'] ?? 'General') ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Doctor info banner (shown only when a doctor is selected) -->
            <?php if ($doctor): ?>
                <div class="doctor-info">
                    <strong><i class="bi bi-stethoscope" style="color:#10b981;"></i>
                        Dr. <?= htmlspecialchars($doctor['full_name']) ?></strong>
                    <small><i class="bi bi-hospital me-1"></i>
                        <?= htmlspecialchars($doctor['specialty'] ?? 'General Practitioner') ?></small>
                    <?php if (!empty($doctor['years_of_experience'])): ?>
                        <small><i class="bi bi-award me-1"></i>
                            <?= (int)$doctor['years_of_experience'] ?> years experience</small>
                    <?php endif; ?>
                    <?php if (!empty($doctor['consultation_fee'])): ?>
                        <small><i class="bi bi-cash-coin me-1"></i>
                            Consultation Fee: $<?= number_format($doctor['consultation_fee'], 2) ?></small>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <!-- Date & Time -->
            <div class="row mb-3">
                <div class="col-md-6 mb-3 mb-md-0">
                    <label class="form-label">Appointment Date <span class="text-danger">*</span></label>
                    <input type="date" name="appointment_date" class="form-control" required
                           min="<?= date('Y-m-d') ?>"
                           value="<?= htmlspecialchars($_POST['appointment_date'] ?? '') ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Appointment Time <span class="text-danger">*</span></label>
                    <input type="time" name="appointment_time" class="form-control" required
                           value="<?= htmlspecialchars($_POST['appointment_time'] ?? '') ?>">
                </div>
            </div>

            <!-- Consultation type -->
            <div class="mb-3">
                <label class="form-label">Consultation Type <span class="text-danger">*</span></label>
                <select name="consultation_type" class="form-select" required>
                    <option value="online"  <?= (($_POST['consultation_type'] ?? '') === 'online')  ? 'selected' : '' ?>>
                        🖥️ Online (Chat / Video Call)
                    </option>
                    <option value="offline" <?= (($_POST['consultation_type'] ?? '') === 'offline') ? 'selected' : '' ?>>
                        🏥 In-Person Visit
                    </option>
                </select>
            </div>

            <!-- Reason -->
            <div class="mb-4">
                <label class="form-label">Describe Your Problem / Reason for Visit</label>
                <textarea name="reason" class="form-control" rows="4"
                          placeholder="e.g., I have been experiencing headaches for the past week..."
                ><?= htmlspecialchars($_POST['reason'] ?? '') ?></textarea>
            </div>

            <!-- Action buttons -->
            <div class="d-flex gap-2">
                <button type="submit" name="book_appointment" class="btn-book flex-grow-1">
                    <i class="bi bi-check-lg me-1"></i> Book Appointment
                </button>
                <a href="doctors.php"
                   class="btn btn-outline-secondary flex-grow-1 text-center"
                   style="padding:12px 20px; border-radius:8px; text-decoration:none; font-weight:600;">
                    Cancel
                </a>
            </div>

        </form>
    </div><!-- /.form-section -->

</div><!-- /.container-custom -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    /**
     * When the user picks a doctor from the dropdown,
     * reload the page so the doctor info banner appears.
     * We preserve any already-filled date/time values via sessionStorage.
     */
    function redirectWithDoctor(doctorId) {
        if (doctorId) {
            // Save form values so they survive the reload
            const date = document.querySelector('[name="appointment_date"]').value;
            const time = document.querySelector('[name="appointment_time"]').value;
            const type = document.querySelector('[name="consultation_type"]').value;
            const reason = document.querySelector('[name="reason"]').value;
            sessionStorage.setItem('appt_date',   date);
            sessionStorage.setItem('appt_time',   time);
            sessionStorage.setItem('appt_type',   type);
            sessionStorage.setItem('appt_reason', reason);
            location.href = 'book_appointment.php?doctor_id=' + encodeURIComponent(doctorId);
        }
    }

    // Restore saved values after redirect
    window.addEventListener('DOMContentLoaded', function () {
        const date   = sessionStorage.getItem('appt_date');
        const time   = sessionStorage.getItem('appt_time');
        const type   = sessionStorage.getItem('appt_type');
        const reason = sessionStorage.getItem('appt_reason');
        if (date)   document.querySelector('[name="appointment_date"]').value   = date;
        if (time)   document.querySelector('[name="appointment_time"]').value   = time;
        if (type)   document.querySelector('[name="consultation_type"]').value  = type;
        if (reason) document.querySelector('[name="reason"]').value             = reason;
        // Clear after restoring
        ['appt_date','appt_time','appt_type','appt_reason'].forEach(k => sessionStorage.removeItem(k));
    });
</script>
</body>
</html>