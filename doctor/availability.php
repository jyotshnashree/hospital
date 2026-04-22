<?php
/**
 * Doctor Availability - Manage working hours and availability
 */

include '../db.php';
checkAuth();
if (getUserRole() !== 'doctor') {
    header('Location: ../portals.php');
    exit;
}

$doctorId = $_SESSION['user_id'];
$daysOfWeek = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
$defaultAvailability = [
    'Monday' => ['09:00', '17:00', true],
    'Tuesday' => ['09:00', '17:00', true],
    'Wednesday' => ['09:00', '17:00', true],
    'Thursday' => ['09:00', '17:00', true],
    'Friday' => ['09:00', '17:00', true],
    'Saturday' => ['10:00', '14:00', false],
    'Sunday' => ['00:00', '00:00', false],
];

// Get doctor's availability from users table
try {
    $doctor = $pdo->query("SELECT * FROM users WHERE id = {$doctorId} AND role = 'doctor'")->fetch();
} catch (Exception $e) {
    $doctor = null;
}
?>

<?php include '../inc/header.php'; ?>

<div style="margin-bottom: 2.5rem; animation: slideInUp 0.6s ease-out;">
    <div>
        <h1 style="font-size: 3rem; margin-bottom: 0.5rem;">
            <i class="bi bi-calendar-range" style="color: #667eea;"></i> Manage Availability
        </h1>
        <p class="text-muted" style="font-weight: 500; font-size: 1.1rem;">⏰ Set your working hours and availability</p>
    </div>
</div>

<!-- Availability Schedule -->
<div class="card shadow p-4">
    <h5 style="font-size: 1.3rem; margin-bottom: 20px;"><i class="bi bi-calendar-check"></i> Weekly Schedule</h5>
    
    <div class="table-responsive">
        <table class="table table-hover">
            <thead style="background: #f8f9fa; border-bottom: 2px solid #e0e0e0;">
            <tr>
                <th style="font-weight: 700; padding: 15px; color: #333;">Day</th>
                <th style="font-weight: 700; padding: 15px; color: #333;">Start Time</th>
                <th style="font-weight: 700; padding: 15px; color: #333;">End Time</th>
                <th style="font-weight: 700; padding: 15px; color: #333;">Available</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($daysOfWeek as $day): 
                $schedule = $defaultAvailability[$day];
            ?>
                <tr>
                    <td style="padding: 15px; font-weight: 600;"><?= $day ?></td>
                    <td style="padding: 15px;">
                        <input type="time" value="<?= $schedule[0] ?>" class="form-control" style="max-width: 150px; border-radius: 8px; border: 2px solid #e0e0e0;">
                    </td>
                    <td style="padding: 15px;">
                        <input type="time" value="<?= $schedule[1] ?>" class="form-control" style="max-width: 150px; border-radius: 8px; border: 2px solid #e0e0e0;">
                    </td>
                    <td style="padding: 15px;">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" <?= $schedule[2] ? 'checked' : '' ?> style="width: 45px; height: 25px; cursor: pointer;">
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div style="margin-top: 20px;">
        <button type="submit" class="btn btn-success" style="border-radius: 8px; padding: 10px 20px;">
            <i class="bi bi-check-circle"></i> Save Schedule
        </button>
        <button type="reset" class="btn btn-outline-secondary" style="border-radius: 8px; padding: 10px 20px;">
            <i class="bi bi-arrow-clockwise"></i> Reset
        </button>
    </div>
</div>

<!-- Information Section -->
<div class="card shadow p-4 mt-4">
    <h5 style="font-size: 1.3rem; margin-bottom: 15px;"><i class="bi bi-info-circle"></i> Information</h5>
    <div style="background: #e3f2fd; border-left: 4px solid #2196F3; padding: 15px; border-radius: 4px;">
        <p style="margin: 0; color: #1565c0;">
            <strong>Schedule Note:</strong> Your availability schedule helps patients book appointments during your working hours. 
            You can enable or disable availability for each day of the week.
        </p>
    </div>
</div>

<style>
    .form-check-input:checked {
        background-color: #10b981 !important;
        border-color: #10b981 !important;
    }
    .form-control:focus {
        outline: none;
        border-color: #667eea !important;
        box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1) !important;
    }
</style>
<?php
/**
 * Doctor Availability - Manage schedule and time slots
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include '../db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'doctor') {
    header('Location: ../doctor_login.php');
    exit;
}

include '../db.php';

$doctorId = $_SESSION['user_id'];
$message = '';

// Handle saving availability
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_availability'])) {
    $dayOfWeek = $_POST['day_of_week'] ?? '';
    $startTime = $_POST['start_time'] ?? '';
    $endTime = $_POST['end_time'] ?? '';
    $consultationDuration = $_POST['consultation_duration'] ?? 30;

    if ($dayOfWeek && $startTime && $endTime) {
        try {
            // Check if already exists
            $checkStmt = $pdo->prepare('SELECT id FROM doctor_availability WHERE doctor_id = ? AND day_of_week = ?');
            $checkStmt->execute([$doctorId, $dayOfWeek]);
            $exists = $checkStmt->fetch();

            if ($exists) {
                // Update
                $stmt = $pdo->prepare('
                    UPDATE doctor_availability 
                    SET start_time = ?, end_time = ?, consultation_duration = ?
                    WHERE doctor_id = ? AND day_of_week = ?
                ');
                $stmt->execute([$startTime, $endTime, $consultationDuration, $doctorId, $dayOfWeek]);
                $message = 'Schedule updated successfully!';
            } else {
                // Insert
                $stmt = $pdo->prepare('
                    INSERT INTO doctor_availability (doctor_id, day_of_week, start_time, end_time, consultation_duration)
                    VALUES (?, ?, ?, ?, ?)
                ');
                $stmt->execute([$doctorId, $dayOfWeek, $startTime, $endTime, $consultationDuration]);
                $message = 'Schedule added successfully!';
            }
        } catch (Exception $e) {
            $message = 'Error saving schedule: ' . $e->getMessage();
        }
    }
}

// Get doctor's availability
$availabilityStmt = $pdo->prepare('
    SELECT * FROM doctor_availability
    WHERE doctor_id = ?
    ORDER BY FIELD(day_of_week, "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday", "Sunday")
');
$availabilityStmt->execute([$doctorId]);
$availability = $availabilityStmt->fetchAll();

// Get time slots for next 30 days
$slotsStmt = $pdo->prepare('
    SELECT * FROM time_slots
    WHERE doctor_id = ? AND appointment_date >= CURDATE()
    ORDER BY appointment_date ASC, start_time ASC
    LIMIT 50
');
$slotsStmt->execute([$doctorId]);
$slots = $slotsStmt->fetchAll();

$days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Availability - Doctor Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        body { background: #f5f7fa; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; }
        .container-custom { max-width: 1200px; margin: 0 auto; padding: 30px 20px; }
        .section { background: white; padding: 25px; border-radius: 10px; margin-bottom: 30px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .schedule-card { border: 1px solid #e0e0e0; padding: 15px; border-radius: 8px; margin-bottom: 15px; }
        .schedule-card:hover { box-shadow: 0 5px 15px rgba(0,0,0,0.1); }
        .slot-badge { display: inline-block; padding: 5px 12px; border-radius: 20px; font-size: 0.85rem; font-weight: 600; }
        .slot-available { background: #d1fae5; color: #065f46; }
        .slot-booked { background: #fee2e2; color: #7f1d1d; }
        .form-control, .form-select { border: 2px solid #e0e0e0; border-radius: 8px; padding: 10px 15px; }
        .form-control:focus, .form-select:focus { outline: none; border-color: #667eea; box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1); }
    </style>
</head>
<body>
<div class="header">
    <div style="max-width: 1200px; margin: 0 auto;">
        <h1 style="margin: 0;"><i class="bi bi-calendar-check"></i> My Availability</h1>
        <p style="margin: 5px 0 0 0;">Set your consultation schedule</p>
    </div>
</div>

<div class="container-custom">
    <?php if ($message): ?>
        <div class="alert alert-info alert-dismissible fade show" role="alert">
            <i class="bi bi-info-circle"></i> <?= htmlspecialchars($message) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Set Weekly Schedule -->
    <div class="section">
        <h4><i class="bi bi-calendar-week"></i> Set Your Weekly Schedule</h4>
        <form method="POST" id="scheduleForm">
            <div class="row mb-3">
                <div class="col-md-3">
                    <label class="form-label"><strong>Day of Week *</strong></label>
                    <select name="day_of_week" class="form-control" required>
                        <option value="">-- Select Day --</option>
                        <?php foreach ($days as $day): ?>
                            <option value="<?= $day ?>"><?= $day ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label"><strong>Start Time *</strong></label>
                    <input type="time" name="start_time" class="form-control" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label"><strong>End Time *</strong></label>
                    <input type="time" name="end_time" class="form-control" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label"><strong>Consultation Duration (minutes)</strong></label>
                    <select name="consultation_duration" class="form-control">
                        <option value="15">15 minutes</option>
                        <option value="30" selected>30 minutes</option>
                        <option value="45">45 minutes</option>
                        <option value="60">1 hour</option>
                    </select>
                </div>
            </div>
            <button type="submit" name="save_availability" class="btn btn-primary btn-lg">
                <i class="bi bi-check"></i> Save Schedule
            </button>
        </form>
    </div>

    <!-- Current Schedule -->
    <div class="section">
        <h4><i class="bi bi-list-check"></i> Your Current Weekly Schedule</h4>
        <?php if (count($availability) > 0): ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Day</th>
                            <th>Start Time</th>
                            <th>End Time</th>
                            <th>Consultation Duration</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($availability as $avail): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($avail['day_of_week']) ?></strong></td>
                                <td><?= date('h:i A', strtotime($avail['start_time'])) ?></td>
                                <td><?= date('h:i A', strtotime($avail['end_time'])) ?></td>
                                <td><?= $avail['consultation_duration'] ?> minutes</td>
                                <td>
                                    <span class="badge <?= $avail['is_active'] ? 'bg-success' : 'bg-secondary' ?>">
                                        <?= $avail['is_active'] ? 'Active' : 'Inactive' ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div style="padding: 40px; text-align: center; color: #999;">
                <i class="bi bi-calendar-x" style="font-size: 2rem; display: block; margin-bottom: 10px; opacity: 0.5;"></i>
                <p>No schedule set yet. Add your weekly schedule above.</p>
            </div>
        <?php endif; ?>
    </div>

    <!-- Upcoming Time Slots -->
    <div class="section">
        <h4><i class="bi bi-hourglass-split"></i> Your Time Slots (Next 30 Days)</h4>
        <div class="row mb-3">
            <div class="col-12">
                <small style="color: #666;">These slots are automatically generated based on your schedule. Available slots can be booked by patients.</small>
            </div>
        </div>
        
        <?php if (count($slots) > 0): ?>
            <?php 
            $currentDate = '';
            foreach ($slots as $slot):
                if ($slot['appointment_date'] !== $currentDate):
                    if ($currentDate !== ''): ?>
                        </div>
                    <?php endif;
                    $currentDate = $slot['appointment_date'];
                    ?>
                    <div style="margin-top: 20px;">
                        <h6 style="color: #667eea; margin-bottom: 10px;">📅 <?= date('l, F j, Y', strtotime($slot['appointment_date'])) ?></h6>
            <?php endif; ?>
                        
                        <div class="schedule-card" style="display: flex; justify-content: space-between; align-items: center;">
                            <div>
                                <strong>⏰ <?= date('h:i A', strtotime($slot['start_time'])) ?> - <?= date('h:i A', strtotime($slot['end_time'])) ?></strong>
                            </div>
                            <div>
                                <span class="slot-badge <?= $slot['is_available'] ? 'slot-available' : 'slot-booked' ?>">
                                    <?= $slot['is_available'] ? '✓ Available' : '✕ Booked' ?>
                                </span>
                            </div>
                        </div>
            <?php endforeach; ?>
                    </div>
        <?php else: ?>
            <div style="padding: 40px; text-align: center; color: #999;">
                <i class="bi bi-hourglass" style="font-size: 2rem; display: block; margin-bottom: 10px; opacity: 0.5;"></i>
                <p>No time slots available. Set your schedule first.</p>
            </div>
        <?php endif; ?>
    </div>

    <div style="text-align: center; padding: 20px;">
        <a href="dashboard.php" class="btn btn-secondary">🏠 Back to Dashboard</a>
    </div>
</div>
</body>
</html>
