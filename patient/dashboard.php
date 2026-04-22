<?php
/**
 * Patient Dashboard - Main patient interface
 */

include '../db.php';
checkAuth();
if (getUserRole() !== 'patient') {
    header('Location: ../portals.php');
    exit;
}

$patientId = $_SESSION['user_id'];

// Fetch patient information
$stmt = $pdo->prepare('SELECT * FROM users WHERE id = ? AND role = "patient"');
$stmt->execute([$patientId]);
$patient = $stmt->fetch();

if (!$patient) {
    header('Location: ../login.php');
    exit;
}

// Get statistics
$upcomingStmt = $pdo->prepare('
    SELECT COUNT(*) as count FROM appointments 
    WHERE patient_id = ? AND appointment_date >= CURDATE()
');
$upcomingStmt->execute([$patientId]);
$upcomingCount = $upcomingStmt->fetch()['count'] ?? 0;

$doctorsStmt = $pdo->prepare('
    SELECT COUNT(DISTINCT doctor_id) as count FROM appointments
    WHERE patient_id = ?
');
$doctorsStmt->execute([$patientId]);
$doctorsCount = $doctorsStmt->fetch()['count'] ?? 0;

$messagesStmt = $pdo->prepare('
    SELECT COUNT(*) as count FROM messages
    WHERE receiver_id = ? AND is_read = FALSE
');
$messagesStmt->execute([$patientId]);
$unreadMessages = $messagesStmt->fetch()['count'] ?? 0;

$billsStmt = $pdo->prepare('
    SELECT COUNT(*) as count FROM bills
    WHERE patient_id = ? AND status = "pending"
');
$billsStmt->execute([$patientId]);
$pendingBills = $billsStmt->fetch()['count'] ?? 0;

// Get upcoming appointments
$apptStmt = $pdo->prepare('
    SELECT a.*, u.full_name as doctor_name, u.specialty
    FROM appointments a
    JOIN users u ON a.doctor_id = u.id
    WHERE a.patient_id = ? AND a.appointment_date >= CURDATE()
    ORDER BY a.appointment_date ASC, a.appointment_time ASC
    LIMIT 50
');
$apptStmt->execute([$patientId]);
$upcomingAppointments = $apptStmt->fetchAll();

// Get prescriptions for this patient
$prescriptionStmt = $pdo->prepare('
    SELECT p.*, a.appointment_date, u.full_name as doctor_name
    FROM prescriptions p
    JOIN appointments a ON p.appointment_id = a.id
    JOIN users u ON a.doctor_id = u.id
    WHERE a.patient_id = ?
    ORDER BY p.created_at DESC
    LIMIT 10
');
$prescriptionStmt->execute([$patientId]);
$prescriptions = $prescriptionStmt->fetchAll();

// Get patient's doctors
$doctorsStmt = $pdo->prepare('
    SELECT DISTINCT u.id, u.full_name, u.specialty, u.phone
    FROM appointments a
    JOIN users u ON a.doctor_id = u.id
    WHERE a.patient_id = ? AND u.role = "doctor"
    ORDER BY u.full_name ASC
    LIMIT 10
');
$doctorsStmt->execute([$patientId]);
$patientDoctors = $doctorsStmt->fetchAll();
?>

<?php include '../inc/header.php'; ?>

<div style="margin-bottom: 2.5rem; animation: slideInUp 0.6s ease-out;">
    <div>
        <h1 style="font-size: 3rem; margin-bottom: 0.5rem;">
            <i class="bi bi-person-heart" style="color: #667eea;"></i> Patient Dashboard
        </h1>
        <p class="text-muted" style="font-weight: 500; font-size: 1.1rem;">Welcome back, <?= sanitize($patient['full_name']) ?> 👋</p>
    </div>
</div>

<!-- Statistics Cards -->
<div class="row mb-4">
    <div class="col-lg-3 mb-3">
        <a href="appointments.php" style="text-decoration: none;">
            <div class="card shadow p-4" style="border-left: 4px solid #667eea; cursor: pointer; transition: all 0.3s ease;" onmouseover="this.style.boxShadow='0 0.5rem 1rem rgba(102, 126, 234, 0.4)'; this.style.transform='translateY(-5px)';" onmouseout="this.style.boxShadow='0 0.125rem 0.25rem rgba(0, 0, 0, 0.075)'; this.style.transform='translateY(0)';">
                <h6 style="color: #999; font-size: 0.9rem; margin-bottom: 10px;">Upcoming Appointments</h6>
                <div style="font-size: 2rem; font-weight: 800; color: #667eea;"><?= $upcomingCount ?></div>
            </div>
        </a>
    </div>
    <div class="col-lg-3 mb-3">
        <a href="doctors.php" style="text-decoration: none;">
            <div class="card shadow p-4" style="border-left: 4px solid #f59e0b; cursor: pointer; transition: all 0.3s ease;" onmouseover="this.style.boxShadow='0 0.5rem 1rem rgba(245, 158, 11, 0.4)'; this.style.transform='translateY(-5px)';" onmouseout="this.style.boxShadow='0 0.125rem 0.25rem rgba(0, 0, 0, 0.075)'; this.style.transform='translateY(0)';">
                <h6 style="color: #999; font-size: 0.9rem; margin-bottom: 10px;">My Doctors</h6>
                <div style="font-size: 2rem; font-weight: 800; color: #f59e0b;"><?= $doctorsCount ?></div>
            </div>
        </a>
    </div>
    <div class="col-lg-3 mb-3">
        <a href="messages.php" style="text-decoration: none;">
            <div class="card shadow p-4" style="border-left: 4px solid #10b981; cursor: pointer; transition: all 0.3s ease;" onmouseover="this.style.boxShadow='0 0.5rem 1rem rgba(16, 185, 129, 0.4)'; this.style.transform='translateY(-5px)';" onmouseout="this.style.boxShadow='0 0.125rem 0.25rem rgba(0, 0, 0, 0.075)'; this.style.transform='translateY(0)';">
                <h6 style="color: #999; font-size: 0.9rem; margin-bottom: 10px;">Unread Messages</h6>
                <div style="font-size: 2rem; font-weight: 800; color: #10b981;"><?= $unreadMessages ?></div>
            </div>
        </a>
    </div>
    <div class="col-lg-3 mb-3">
        <a href="billing.php" style="text-decoration: none;">
            <div class="card shadow p-4" style="border-left: 4px solid #ffc107; cursor: pointer; transition: all 0.3s ease;" onmouseover="this.style.boxShadow='0 0.5rem 1rem rgba(255, 193, 7, 0.4)'; this.style.transform='translateY(-5px)';" onmouseout="this.style.boxShadow='0 0.125rem 0.25rem rgba(0, 0, 0, 0.075)'; this.style.transform='translateY(0)';">
                <h6 style="color: #999; font-size: 0.9rem; margin-bottom: 10px;">Pending Bills</h6>
                <div style="font-size: 2rem; font-weight: 800; color: #ffc107;"><?= $pendingBills ?></div>
            </div>
        </a>
    </div>
</div>

<!-- Main Content Row -->
<div class="row">
    <!-- Upcoming Appointments -->
    <div class="col-lg-6 mb-4">
        <div class="card shadow p-4" style="border-top: 4px solid #667eea;">
            <h5 style="font-size: 1.4rem; margin-bottom: 20px; font-weight: 700;"><i class="bi bi-calendar-check" style="color: #667eea;"></i> Upcoming Appointments</h5>
            
            <?php if (!empty($upcomingAppointments)): ?>
                <div class="table-responsive">
                    <table class="table table-hover" style="margin-bottom: 0;">
                        <thead style="background: linear-gradient(135deg, #f8f9fa 0%, #eff0f5 100%); border-bottom: 2px solid #e0e0e0;">
                        <tr>
                            <th style="font-weight: 700; padding: 15px; color: #333;">Date & Time</th>
                            <th style="font-weight: 700; padding: 15px; color: #333;">Doctor</th>
                            <th style="font-weight: 700; padding: 15px; color: #333;">Specialty</th>
                            <th style="font-weight: 700; padding: 15px; color: #333;">Status</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($upcomingAppointments as $appt): ?>
                            <tr style="border-bottom: 1px solid #f0f0f0; transition: all 0.3s ease;" onmouseover="this.style.backgroundColor='#f8f9fa';" onmouseout="this.style.backgroundColor='white';">
                                <td style="padding: 15px;">
                                    <i class="bi bi-calendar3" style="color: #667eea;"></i> <strong><?= date('M d, Y', strtotime($appt['appointment_date'])) ?></strong>
                                    <br>
                                    <small style="color: #999;"><i class="bi bi-clock"></i> <?= date('h:i A', strtotime($appt['appointment_time'])) ?></small>
                                </td>
                                <td style="padding: 15px;">
                                    <strong style="color: #333;"><?= sanitize($appt['doctor_name']) ?></strong>
                                </td>
                                <td style="padding: 15px;">
                                    <span style="color: #667eea; font-weight: 600;"><?= sanitize($appt['specialty'] ?? 'General') ?></span>
                                </td>
                                <td style="padding: 15px;">
                                    <span class="badge" style="background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%); color: #065f46; padding: 6px 12px; border-radius: 6px; font-weight: 600; font-size: 0.8rem;">
                                        ✓ Scheduled
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <a href="appointments.php" class="btn btn-sm" style="border-radius: 6px; margin-top: 15px; background: #667eea; color: white; border: none; font-weight: 600; padding: 8px 16px;">
                    <i class="bi bi-arrow-right"></i> View All Appointments
                </a>
            <?php else: ?>
                <div style="text-align: center; padding: 50px 20px; background: linear-gradient(135deg, #f8f9fa 0%, #eff0f5 100%); border-radius: 8px; border: 2px dashed #ddd;">
                    <i class="bi bi-inbox" style="font-size: 2.5rem; color: #ccc; display: block; margin-bottom: 15px;"></i>
                    <p style="color: #999; font-weight: 500; margin: 0;">No upcoming appointments scheduled.</p>
                    <p style="color: #bbb; font-size: 0.9rem; margin-top: 5px;">Schedule your first appointment today!</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="col-lg-6 mb-4">
        <div class="card shadow p-4" style="border-top: 4px solid #f59e0b;">
            <h5 style="font-size: 1.4rem; margin-bottom: 20px; font-weight: 700;"><i class="bi bi-lightning-fill" style="color: #f59e0b;"></i> Quick Actions</h5>
            
            <div style="display: flex; flex-direction: column; gap: 12px;">
                <a href="book_appointment.php" class="btn btn-outline-primary" style="border-radius: 8px; padding: 14px 16px; text-align: left; border: 2px solid #667eea; font-weight: 600; transition: all 0.3s ease;" onmouseover="this.style.backgroundColor='#f0f4ff'; this.style.boxShadow='0 0.25rem 0.5rem rgba(102, 126, 234, 0.2)';" onmouseout="this.style.backgroundColor='white'; this.style.boxShadow='none';">
                    <i class="bi bi-calendar-plus" style="color: #667eea; margin-right: 10px;"></i> Book Appointment
                </a>
                <a href="doctors.php" class="btn btn-outline-success" style="border-radius: 8px; padding: 14px 16px; text-align: left; border: 2px solid #10b981; font-weight: 600; transition: all 0.3s ease;" onmouseover="this.style.backgroundColor='#f0fdf4'; this.style.boxShadow='0 0.25rem 0.5rem rgba(16, 185, 129, 0.2)';" onmouseout="this.style.backgroundColor='white'; this.style.boxShadow='none';">
                    <i class="bi bi-person-badge" style="color: #10b981; margin-right: 10px;"></i> Browse All Doctors
                    <span style="float: right; color: #10b981; font-weight: 700;">→</span>
                </a>
                <a href="messages.php" class="btn btn-outline-info" style="border-radius: 8px; padding: 14px 16px; text-align: left; border: 2px solid #0ea5e9; font-weight: 600; transition: all 0.3s ease;" onmouseover="this.style.backgroundColor='#f0f9ff'; this.style.boxShadow='0 0.25rem 0.5rem rgba(14, 165, 233, 0.2)';" onmouseout="this.style.backgroundColor='white'; this.style.boxShadow='none';">
                    <i class="bi bi-chat-dots" style="color: #0ea5e9; margin-right: 10px;"></i> Messages
                    <span style="float: right; background: #0ea5e9; color: white; padding: 2px 8px; border-radius: 12px; font-size: 0.75rem; font-weight: 700;"><?= $unreadMessages > 0 ? $unreadMessages : '0' ?></span>
                </a>
                <a href="billing.php" class="btn btn-outline-warning" style="border-radius: 8px; padding: 14px 16px; text-align: left; border: 2px solid #f59e0b; font-weight: 600; transition: all 0.3s ease;" onmouseover="this.style.backgroundColor='#fffbf0'; this.style.boxShadow='0 0.25rem 0.5rem rgba(245, 158, 11, 0.2)';" onmouseout="this.style.backgroundColor='white'; this.style.boxShadow='none';">
                    <i class="bi bi-receipt" style="color: #f59e0b; margin-right: 10px;"></i> Billing
                    <span style="float: right; color: #f59e0b; font-weight: 700;">→</span>
                </a>
            </div>
        </div>
    </div>
</div>

<!-- My Doctors Section -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card shadow p-4" style="border-top: 4px solid #10b981;">
            <h5 style="font-size: 1.4rem; margin-bottom: 20px; font-weight: 700;"><i class="bi bi-person-heart" style="color: #10b981;"></i> My Doctors (<?= count($patientDoctors) ?>)</h5>
            
            <?php if (!empty($patientDoctors)): ?>
                <div class="row">
                    <?php foreach ($patientDoctors as $doctor): ?>
                        <div class="col-md-6 col-lg-4 mb-3">
                            <div class="card" style="border: 2px solid #f0f0f0; border-radius: 10px; transition: all 0.3s ease; height: 100%;" onmouseover="this.style.boxShadow='0 0.5rem 1rem rgba(16, 185, 129, 0.2)'; this.style.transform='translateY(-5px)'; this.style.borderColor='#10b981';" onmouseout="this.style.boxShadow='none'; this.style.transform='translateY(0)'; this.style.borderColor='#f0f0f0';">
                                <div class="card-body p-4">
                                    <div style="display: flex; align-items: center; margin-bottom: 15px;">
                                        <div style="width: 50px; height: 50px; background: linear-gradient(135deg, #10b981 0%, #059669 100%); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-weight: 700; font-size: 1.2rem; margin-right: 12px;">
                                            <?= substr($doctor['full_name'], 0, 1) ?>
                                        </div>
                                        <div>
                                            <h6 style="margin: 0; font-weight: 700; color: #333;">Dr. <?= sanitize($doctor['full_name']) ?></h6>
                                            <small style="color: #10b981; font-weight: 600;"><?= sanitize($doctor['specialty'] ?? 'General') ?></small>
                                        </div>
                                    </div>
                                    
                                    <div style="border-top: 1px solid #f0f0f0; padding-top: 12px;">
                                        <?php if (!empty($doctor['phone'])): ?>
                                            <div style="font-size: 0.9rem; color: #666; margin-bottom: 12px;">
                                                <i class="bi bi-telephone" style="color: #10b981;"></i> <?= sanitize($doctor['phone']) ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <a href="book_appointment.php" class="btn btn-sm" style="width: 100%; background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; border: none; border-radius: 6px; font-weight: 600; margin-top: 10px; padding: 8px 12px;">
                                        <i class="bi bi-calendar-plus"></i> Book Appointment
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div style="text-align: center; padding: 50px 20px; background: linear-gradient(135deg, #f0fdf4 0%, #dbeafe 100%); border-radius: 8px; border: 2px dashed #10b981;">
                    <i class="bi bi-inbox" style="font-size: 2.5rem; color: #a7f3d0; display: block; margin-bottom: 15px;"></i>
                    <p style="color: #065f46; font-weight: 600; margin: 0; font-size: 1.1rem;">No doctors yet</p>
                    <p style="color: #10b981; font-size: 0.9rem; margin-top: 5px;">Book your first appointment to connect with a doctor!</p>
                    <a href="book_appointment.php" class="btn btn-sm" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; border: none; border-radius: 6px; font-weight: 600; margin-top: 12px; padding: 8px 20px;">
                        <i class="bi bi-calendar-plus"></i> Book Now
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Prescriptions Section -->
<div class="card shadow p-4" style="border-top: 4px solid #ffc107;">
    <h5 style="font-size: 1.4rem; margin-bottom: 20px; font-weight: 700;"><i class="bi bi-prescription2" style="color: #ffc107;"></i> My Prescriptions</h5>
    
    <?php if (!empty($prescriptions)): ?>
        <div class="table-responsive">
            <table class="table table-hover" style="margin-bottom: 0;">
                <thead style="background: linear-gradient(135deg, #fffbf0 0%, #fef3c7 100%); border-bottom: 2px solid #e0e0e0;">
                <tr>
                    <th style="font-weight: 700; padding: 15px; color: #333;">Medication</th>
                    <th style="font-weight: 700; padding: 15px; color: #333;">Doctor</th>
                    <th style="font-weight: 700; padding: 15px; color: #333;">Dosage</th>
                    <th style="font-weight: 700; padding: 15px; color: #333;">Frequency</th>
                    <th style="font-weight: 700; padding: 15px; color: #333;">Duration</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($prescriptions as $presc): ?>
                    <tr style="border-bottom: 1px solid #f0f0f0; transition: all 0.3s ease;" onmouseover="this.style.backgroundColor='#fffbf0';" onmouseout="this.style.backgroundColor='white';">
                        <td style="padding: 15px;"><strong style="color: #333;"><?= sanitize($presc['medication']) ?></strong></td>
                        <td style="padding: 15px;">
                            <span style="color: #666;">Dr. <?= sanitize($presc['doctor_name']) ?></span>
                        </td>
                        <td style="padding: 15px;">
                            <span style="background: #fef3c7; color: #b45309; padding: 4px 10px; border-radius: 6px; font-weight: 600; font-size: 0.85rem;"><?= sanitize($presc['dosage']) ?></span>
                        </td>
                        <td style="padding: 15px; color: #666;"><?= sanitize($presc['frequency']) ?></td>
                        <td style="padding: 15px; color: #666;"><?= sanitize($presc['duration']) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div style="text-align: center; padding: 60px 20px; background: linear-gradient(135deg, #fffbf0 0%, #fef3c7 100%); border-radius: 8px; border: 2px dashed #ffc107;">
            <i class="bi bi-prescription2" style="font-size: 3rem; color: #fcd34d; display: block; margin-bottom: 15px;"></i>
            <p style="color: #b45309; font-weight: 600; margin: 0; font-size: 1.1rem;">No prescriptions yet</p>
            <p style="color: #d97706; font-size: 0.9rem; margin-top: 5px;">Prescriptions from your doctors will appear here.</p>
        </div>
    <?php endif; ?>
</div>

<?php include '../inc/footer.php'; ?>
