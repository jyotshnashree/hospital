<?php
/**
 * Doctor Dashboard - Main doctor interface
 */

include '../db.php';
checkAuth();
if (getUserRole() !== 'doctor') {
    header('Location: ../portals.php');
    exit;
}

$doctorId = $_SESSION['user_id'];

// Fetch doctor information
$stmt = $pdo->prepare('SELECT * FROM users WHERE id = ? AND role = "doctor"');
$stmt->execute([$doctorId]);
$doctor = $stmt->fetch();

if (!$doctor) {
    header('Location: ../login.php');
    exit;
}

// Get statistics for this doctor
$todayStmt = $pdo->prepare('
    SELECT COUNT(*) as count FROM appointments 
    WHERE doctor_id = ? AND DATE(appointment_date) = CURDATE()
');
$todayStmt->execute([$doctorId]);
$todayCount = $todayStmt->fetch()['count'] ?? 0;

$upcomingStmt = $pdo->prepare('
    SELECT COUNT(*) as count FROM appointments 
    WHERE doctor_id = ? AND appointment_date >= CURDATE()
');
$upcomingStmt->execute([$doctorId]);
$upcomingCount = $upcomingStmt->fetch()['count'] ?? 0;

$patientStmt = $pdo->prepare('
    SELECT COUNT(DISTINCT patient_id) as count FROM appointments
    WHERE doctor_id = ?
');
$patientStmt->execute([$doctorId]);
$patientCount = $patientStmt->fetch()['count'] ?? 0;

// Get prescriptions count for this doctor's appointments
$prescriptionStmt = $pdo->prepare('
    SELECT COUNT(*) as count FROM prescriptions p
    JOIN appointments a ON p.appointment_id = a.id
    WHERE a.doctor_id = ?
');
$prescriptionStmt->execute([$doctorId]);
$prescriptionCount = $prescriptionStmt->fetch()['count'] ?? 0;

// Get today's appointments
$todayAppointments = $pdo->prepare('
    SELECT a.*, u.full_name as patient_name, u.phone, u.email 
    FROM appointments a 
    JOIN users u ON a.patient_id = u.id 
    WHERE a.doctor_id = ? AND DATE(a.appointment_date) = CURDATE()
    ORDER BY a.appointment_time ASC
    LIMIT 100
');
$todayAppointments->execute([$doctorId]);
$todayAppointments = $todayAppointments->fetchAll();

// Get upcoming appointments
$upcomingAppointments = $pdo->prepare('
    SELECT a.*, u.full_name as patient_name, u.phone, u.email 
    FROM appointments a 
    JOIN users u ON a.patient_id = u.id 
    WHERE a.doctor_id = ? AND DATE(a.appointment_date) >= CURDATE()
    ORDER BY a.appointment_date ASC, a.appointment_time ASC
    LIMIT 20
');
$upcomingAppointments->execute([$doctorId]);
$upcomingAppointments = $upcomingAppointments->fetchAll();

// Get pending appointments
$pendingAppts = $pdo->prepare('
    SELECT COUNT(*) as count FROM appointments 
    WHERE doctor_id = ? AND status = "Pending"
');
$pendingAppts->execute([$doctorId]);
$pendingCount = $pendingAppts->fetch()['count'] ?? 0;
?>

<?php include '../inc/header.php'; ?>

<div style="margin-bottom: 2.5rem; animation: slideInUp 0.6s ease-out;">
    <div>
        <h1 style="font-size: 3rem; margin-bottom: 0.5rem;">
            <i class="bi bi-stethoscope" style="color: #667eea;"></i> Doctor Dashboard
        </h1>
        <p class="text-muted" style="font-weight: 500; font-size: 1.1rem;">Welcome back, Dr. <?= sanitize($doctor['full_name']) ?> 👋</p>
    </div>
</div>

<!-- Statistics Cards -->
<div class="row mb-4">
    <div class="col-lg-3 mb-3">
        <a href="appointments.php?filter=today" style="text-decoration: none;">
            <div class="card shadow p-4" style="border-left: 4px solid #667eea; cursor: pointer; transition: all 0.3s ease;" onmouseover="this.style.boxShadow='0 0.5rem 1rem rgba(102, 126, 234, 0.4)'; this.style.transform='translateY(-5px)';" onmouseout="this.style.boxShadow='0 0.125rem 0.25rem rgba(0, 0, 0, 0.075)'; this.style.transform='translateY(0)';">
                <h6 style="color: #999; font-size: 0.9rem; margin-bottom: 10px;">Today's Appointments</h6>
                <div style="font-size: 2rem; font-weight: 800; color: #667eea;"><?= $todayCount ?></div>
            </div>
        </a>
    </div>
    <div class="col-lg-3 mb-3">
        <a href="appointments.php" style="text-decoration: none;">
            <div class="card shadow p-4" style="border-left: 4px solid #f59e0b; cursor: pointer; transition: all 0.3s ease;" onmouseover="this.style.boxShadow='0 0.5rem 1rem rgba(245, 158, 11, 0.4)'; this.style.transform='translateY(-5px)';" onmouseout="this.style.boxShadow='0 0.125rem 0.25rem rgba(0, 0, 0, 0.075)'; this.style.transform='translateY(0)';">
                <h6 style="color: #999; font-size: 0.9rem; margin-bottom: 10px;">Upcoming Appointments</h6>
                <div style="font-size: 2rem; font-weight: 800; color: #f59e0b;"><?= $upcomingCount ?></div>
            </div>
        </a>
    </div>
    <div class="col-lg-3 mb-3">
        <a href="appointments.php" style="text-decoration: none;">
            <div class="card shadow p-4" style="border-left: 4px solid #10b981; cursor: pointer; transition: all 0.3s ease;" onmouseover="this.style.boxShadow='0 0.5rem 1rem rgba(16, 185, 129, 0.4)'; this.style.transform='translateY(-5px)';" onmouseout="this.style.boxShadow='0 0.125rem 0.25rem rgba(0, 0, 0, 0.075)'; this.style.transform='translateY(0)';">
                <h6 style="color: #999; font-size: 0.9rem; margin-bottom: 10px;">Total Patients</h6>
                <div style="font-size: 2rem; font-weight: 800; color: #10b981;"><?= $patientCount ?></div>
            </div>
        </a>
    </div>
    <div class="col-lg-3 mb-3">
        <a href="prescriptions.php" style="text-decoration: none;">
            <div class="card shadow p-4" style="border-left: 4px solid #ffc107; cursor: pointer; transition: all 0.3s ease;" onmouseover="this.style.boxShadow='0 0.5rem 1rem rgba(255, 193, 7, 0.4)'; this.style.transform='translateY(-5px)';" onmouseout="this.style.boxShadow='0 0.125rem 0.25rem rgba(0, 0, 0, 0.075)'; this.style.transform='translateY(0)';">
                <h6 style="color: #999; font-size: 0.9rem; margin-bottom: 10px;">Prescriptions</h6>
                <div style="font-size: 2rem; font-weight: 800; color: #ffc107;"><?= $prescriptionCount ?></div>
            </div>
        </a>
    </div>
</div>

<!-- Main Content Row -->
<div class="row">
    <!-- Today's Appointments -->
    <div class="col-lg-6 mb-4">
        <div class="card shadow p-4">
            <h5 style="font-size: 1.3rem; margin-bottom: 20px;"><i class="bi bi-calendar-check"></i> Today's Appointments</h5>
            
            <?php if (!empty($todayAppointments)): ?>
                <div class="table-responsive">
                    <table class="table table-sm table-hover">
                        <thead style="background: #f8f9fa;">
                        <tr>
                            <th style="font-weight: 700;">Time</th>
                            <th style="font-weight: 700;">Patient</th>
                            <th style="font-weight: 700;">Status</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($todayAppointments as $appt): ?>
                            <tr>
                                <td style="font-weight: 700;">
                                    <i class="bi bi-clock"></i> <?= date('h:i A', strtotime($appt['appointment_time'])) ?>
                                </td>
                                <td>
                                    <strong><?= sanitize($appt['patient_name']) ?></strong>
                                </td>
                                <td>
                                    <span class="badge" style="background: #d1fae5; color: #065f46; padding: 4px 8px; font-size: 0.8rem;">
                                        <?= $appt['status'] ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <a href="appointments.php?filter=today" class="btn btn-sm btn-primary" style="border-radius: 6px; margin-top: 15px;">
                    <i class="bi bi-arrow-right"></i> View All
                </a>
            <?php else: ?>
                <div style="text-align: center; padding: 40px; background: #f8f9fa; border-radius: 8px;">
                    <i class="bi bi-inbox" style="font-size: 2rem; color: #ccc; display: block; margin-bottom: 10px;"></i>
                    <p style="color: #999;">No appointments today.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="col-lg-6 mb-4">
        <div class="card shadow p-4">
            <h5 style="font-size: 1.3rem; margin-bottom: 20px;"><i class="bi bi-lightning-fill"></i> Quick Actions</h5>
            
            <div style="display: flex; flex-direction: column; gap: 10px;">
                <a href="appointments.php" class="btn btn-outline-primary" style="border-radius: 8px; padding: 12px; text-align: left;">
                    <i class="bi bi-calendar-event"></i> View All Appointments
                    <span style="float: right; color: #667eea; font-weight: 700;"><?= $upcomingCount ?></span>
                </a>
                <a href="prescriptions.php" class="btn btn-outline-success" style="border-radius: 8px; padding: 12px; text-align: left;">
                    <i class="bi bi-prescription2"></i> Manage Prescriptions
                    <span style="float: right; color: #10b981; font-weight: 700;"><?= $prescriptionCount ?></span>
                </a>
                <a href="messages.php" class="btn btn-outline-info" style="border-radius: 8px; padding: 12px; text-align: left;">
                    <i class="bi bi-chat-dots"></i> View Messages
                </a>
                <a href="availability.php" class="btn btn-outline-warning" style="border-radius: 8px; padding: 12px; text-align: left;">
                    <i class="bi bi-calendar-range"></i> Manage Availability
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Upcoming Appointments Section -->
<div class="card shadow p-4">
    <h5 style="font-size: 1.3rem; margin-bottom: 20px;"><i class="bi bi-calendar-range"></i> Upcoming Appointments</h5>
    
    <?php if (!empty($upcomingAppointments)): ?>
        <div class="table-responsive">
            <table class="table table-hover">
                <thead style="background: #f8f9fa; border-bottom: 2px solid #e0e0e0;">
                <tr>
                    <th style="font-weight: 700; padding: 15px;">Date & Time</th>
                    <th style="font-weight: 700; padding: 15px;">Patient</th>
                    <th style="font-weight: 700; padding: 15px;">Contact</th>
                    <th style="font-weight: 700; padding: 15px;">Status</th>
                    <th style="font-weight: 700; padding: 15px;">Action</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($upcomingAppointments as $appt): ?>
                    <tr>
                        <td style="padding: 15px;">
                            <i class="bi bi-calendar3"></i> <?= date('M d, Y', strtotime($appt['appointment_date'])) ?>
                            <br>
                            <i class="bi bi-clock"></i> <?= date('h:i A', strtotime($appt['appointment_time'])) ?>
                        </td>
                        <td style="padding: 15px;"><strong><?= sanitize($appt['patient_name']) ?></strong></td>
                        <td style="padding: 15px;">
                            <i class="bi bi-telephone"></i> <?= sanitize($appt['phone']) ?>
                        </td>
                        <td style="padding: 15px;">
                            <span class="badge" style="background: #d1fae5; color: #065f46; padding: 6px 12px; border-radius: 6px; font-weight: 600;">
                                <?= $appt['status'] ?>
                            </span>
                        </td>
                        <td style="padding: 15px;">
                            <a href="appointments.php" class="btn btn-sm btn-primary" style="border-radius: 6px; padding: 5px 10px;">
                                <i class="bi bi-arrow-right"></i> View
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div style="text-align: center; padding: 60px 20px; background: #f8f9fa; border-radius: 8px;">
            <i class="bi bi-inbox" style="font-size: 3rem; color: #ccc; display: block; margin-bottom: 15px;"></i>
            <p style="color: #999; font-size: 1rem;">No upcoming appointments.</p>
        </div>
    <?php endif; ?>
</div>
<?php
/**
 * Doctor Dashboard - Main doctor interface
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include '../db.php';

// Check if user is logged in and is a doctor
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'doctor') {
    header('Location: ../doctor_login.php');
    exit;
}

$doctorId = $_SESSION['user_id'];

// Fetch doctor information
$stmt = $pdo->prepare('SELECT * FROM users WHERE id = ? AND role = "doctor"');
$stmt->execute([$doctorId]);
$doctor = $stmt->fetch();

if (!$doctor) {
    header('Location: ../login.php');
    exit;
}

// Get today's appointments
$todayAppointments = $pdo->query('
    SELECT a.*, u.full_name as patient_name, u.phone, u.email 
    FROM appointments a 
    JOIN users u ON a.patient_id = u.id 
    WHERE DATE(a.appointment_date) = CURDATE()
    ORDER BY a.appointment_time ASC
    LIMIT 10
')->fetchAll();

// Get upcoming appointments  
$upcomingAppointments = $pdo->query('
    SELECT a.*, u.full_name as patient_name, u.phone, u.email 
    FROM appointments a 
    JOIN users u ON a.patient_id = u.id 
    WHERE a.appointment_date >= CURDATE()
    ORDER BY a.appointment_date ASC, a.appointment_time ASC
    LIMIT 5
')->fetchAll();

// Get total patients
$patientsCount = $pdo->query('
    SELECT COUNT(DISTINCT patient_id) as total_patients 
    FROM appointments
')->fetch()['total_patients'] ?? 0;

// Get unread messages
$unreadMessages = 0;
try {
    $messagesStmt = $pdo->prepare('
        SELECT COUNT(*) as unread_count 
        FROM messages 
        WHERE receiver_id = ? AND is_read = FALSE
    ');
    $messagesStmt->execute([$doctorId]);
    $result = $messagesStmt->fetch();
    $unreadMessages = $result['unread_count'] ?? 0;
} catch (Exception $e) {
    // Messages table might not exist yet
    $unreadMessages = 0;
}

// Get pending prescriptions
$activePrescriptions = 0;
try {
    $prescriptionsStmt = $pdo->prepare('
        SELECT COUNT(*) as pending_count 
        FROM prescriptions 
        WHERE status = "active"
    ');
    $prescriptionsStmt->execute();
    $result = $prescriptionsStmt->fetch();
    $activePrescriptions = $result['pending_count'] ?? 0;
} catch (Exception $e) {
    // Prescriptions table might not have required columns
    $activePrescriptions = 0;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doctor Dashboard - Hospital Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        body {
            background: #f5f7fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 1200px;
            margin: 0 auto;
        }

        .header-left h1 {
            margin: 0;
            font-size: 1.8rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .header-right {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }

        .nav-btn {
            background: rgba(255, 255, 255, 0.2);
            border: none;
            color: white;
            padding: 8px 15px;
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            font-size: 0.9rem;
        }

        .nav-btn:hover {
            background: rgba(255, 255, 255, 0.3);
            color: white;
        }

        .container-custom {
            max-width: 1200px;
            margin: 0 auto;
            padding: 30px 20px;
        }

        .section-title {
            color: #333;
            font-size: 1.5rem;
            margin-bottom: 20px;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            border-left: 5px solid #667eea;
        }

        .stat-number {
            font-size: 2.5rem;
            font-weight: 700;
            color: #667eea;
            margin-bottom: 5px;
        }

        .stat-label {
            color: #666;
            font-size: 0.95rem;
        }

        .appointment-card {
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
            transition: all 0.3s ease;
            background: white;
        }

        .appointment-card:hover {
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            border-color: #667eea;
        }

        .appointment-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 10px;
        }

        .patient-info {
            font-weight: 600;
            color: #333;
        }

        .appointment-time {
            color: #667eea;
            font-weight: 600;
        }

        .appointment-details {
            font-size: 0.9rem;
            color: #666;
            margin-bottom: 8px;
        }

        .appointment-actions {
            display: flex;
            gap: 8px;
            margin-top: 12px;
        }

        .btn-small {
            padding: 6px 12px;
            font-size: 0.85rem;
            border-radius: 5px;
            border: none;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            color: white;
        }

        .btn-primary-small {
            background: #667eea;
        }

        .btn-primary-small:hover {
            background: #5568d3;
            color: white;
        }

        .btn-success-small {
            background: #10b981;
        }

        .btn-success-small:hover {
            background: #059669;
            color: white;
        }

        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: #666;
            background: white;
            border-radius: 8px;
        }

        .empty-state i {
            font-size: 3rem;
            color: #ddd;
            margin-bottom: 10px;
            display: block;
        }

        .status-badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
        }

        .status-pending { background: #fef3c7; color: #92400e; }
        .status-approved { background: #d1fae5; color: #065f46; }
        .status-completed { background: #dbeafe; color: #1e40af; }

        @media (max-width: 768px) {
            .header-content {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }
        }
    </style>
</head>
<body>
<div class="header">
    <div class="header-content">
        <div class="header-left">
            <h1><i class="bi bi-stethoscope"></i> Dr. <?= htmlspecialchars($doctor['full_name']) ?></h1>
        </div>
        <div class="header-right">
            <a href="appointments.php" class="nav-btn">
                <i class="bi bi-calendar-check"></i> Appointments
            </a>
            <a href="patients.php" class="nav-btn">👥 Patients</a>
            <a href="messages.php" class="nav-btn">💬 Messages</a>
            <a href="prescriptions.php" class="nav-btn">💊 Prescriptions</a>
            <a href="availability.php" class="nav-btn">⏰ Schedule</a>
            <a href="../logout.php" class="nav-btn">🚪 Logout</a>
        </div>
    </div>
</div>

<div class="container-custom">
    <!-- Statistics -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-number"><?= $patientsCount ?></div>
            <div class="stat-label">Total Patients</div>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?= $unreadMessages ?></div>
            <div class="stat-label">Unread Messages</div>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?= $activePrescriptions ?></div>
            <div class="stat-label">Active Prescriptions</div>
        </div>
    </div>

    <!-- Today's Appointments -->
    <div class="section-title"><i class="bi bi-calendar-event"></i> Today's Appointments</div>
    <div style="background: white; padding: 25px; border-radius: 10px; margin-bottom: 30px; box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);">
        <?php if (count($todayAppointments) > 0): ?>
            <?php foreach ($todayAppointments as $apt): ?>
                <div class="appointment-card">
                    <div class="appointment-header">
                        <div>
                            <div class="patient-info">👤 <?= htmlspecialchars($apt['patient_name']) ?></div>
                            <div class="appointment-details">📞 <?= htmlspecialchars($apt['phone']) ?></div>
                            <div class="appointment-details">💬 <?= htmlspecialchars($apt['reason'] ?? 'No reason specified') ?></div>
                        </div>
                        <div>
                            <div class="appointment-time">⏰ <?= date('h:i A', strtotime($apt['appointment_time'])) ?></div>
                            <span class="status-badge status-<?= strtolower($apt['status']) ?>">
                                <?= ucfirst($apt['status']) ?>
                            </span>
                        </div>
                    </div>
                    <div class="appointment-actions">
                        <a href="messages.php?patient_id=<?= $apt['patient_id'] ?>" class="btn-small btn-primary-small">
                            <i class="bi bi-chat-dots"></i> Chat
                        </a>
                        <a href="prescriptions.php?appointment_id=<?= $apt['id'] ?>" class="btn-small btn-success-small">
                            <i class="bi bi-prescription2"></i> Prescription
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="empty-state">
                <i class="bi bi-calendar-x"></i>
                <p>No appointments scheduled for today</p>
            </div>
        <?php endif; ?>
    </div>

    <!-- Upcoming Appointments -->
    <div class="section-title"><i class="bi bi-calendar-week"></i> Upcoming Appointments</div>
    <div style="background: white; padding: 25px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);">
        <?php if (count($upcomingAppointments) > 0): ?>
            <?php foreach (array_slice($upcomingAppointments, 0, 5) as $apt): ?>
                <div class="appointment-card">
                    <div class="appointment-header">
                        <div>
                            <div class="patient-info">👤 <?= htmlspecialchars($apt['patient_name']) ?></div>
                            <div class="appointment-details">📅 <?= date('l, F j, Y', strtotime($apt['appointment_date'])) ?></div>
                            <div class="appointment-details">⏰ <?= date('h:i A', strtotime($apt['appointment_time'])) ?></div>
                        </div>
                        <span class="status-badge status-<?= strtolower($apt['status']) ?>">
                            <?= ucfirst($apt['status']) ?>
                        </span>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="empty-state">
                <i class="bi bi-calendar-check"></i>
                <p>No upcoming appointments</p>
            </div>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
