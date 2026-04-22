<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include '../db.php';
checkAuth();
if (getUserRole() !== 'admin') {
    header('Location: ../portals.php');
    exit;
}

$totalDoctors = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'doctor'")->fetchColumn();
$totalPatients = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'patient'")->fetchColumn();
$totalAppointments = $pdo->query("SELECT COUNT(*) FROM appointments")->fetchColumn();
$pendingAppointments = $pdo->query("SELECT COUNT(*) FROM appointments WHERE status = 'Pending'")->fetchColumn();
$totalBills = $pdo->query("SELECT COUNT(*) FROM bills")->fetchColumn();

// Patient Reports
$newPatientsThisMonth = $pdo->query("
    SELECT COUNT(*) FROM users 
    WHERE role = 'patient' AND MONTH(created_at) = MONTH(NOW()) AND YEAR(created_at) = YEAR(NOW())
")->fetchColumn();

$patientsWithAppointments = $pdo->query("
    SELECT COUNT(DISTINCT patient_id) FROM appointments
")->fetchColumn();

$totalPaidBills = $pdo->query("
    SELECT SUM(amount) FROM bills WHERE status = 'paid'
")->fetchColumn() ?? 0;

$totalPendingBills = $pdo->query("
    SELECT SUM(amount) FROM bills WHERE status = 'pending'
")->fetchColumn() ?? 0;

$recentPatients = $pdo->query("
    SELECT id, full_name, email, phone, created_at 
    FROM users 
    WHERE role = 'patient' 
    ORDER BY created_at DESC 
    LIMIT 10
")->fetchAll();

$patientStats = $pdo->query("
    SELECT 
        (SELECT COUNT(*) FROM users WHERE role = 'patient') as total,
        (SELECT COUNT(*) FROM appointments WHERE MONTH(appointment_date) = MONTH(NOW())) as appointments_this_month,
        (SELECT SUM(amount) FROM bills WHERE status = 'paid') as total_revenue,
        (SELECT COUNT(*) FROM bills WHERE status = 'pending') as pending_bills
")->fetch();

// Additional Reports
$appointmentCompletionRate = $pdo->query("
    SELECT 
        ROUND((COUNT(CASE WHEN status = 'Completed' THEN 1 END) / COUNT(*)) * 100, 2) as completion_rate
    FROM appointments
")->fetch();

$topPatientsByAppointments = $pdo->query("
    SELECT u.id, u.full_name, COUNT(a.id) as appointment_count
    FROM users u
    LEFT JOIN appointments a ON u.id = a.patient_id
    WHERE u.role = 'patient'
    GROUP BY u.id
    ORDER BY appointment_count DESC
    LIMIT 5
")->fetchAll();

$topDoctorsByPatients = $pdo->query("
    SELECT u.id, u.full_name, u.specialty, COUNT(DISTINCT a.patient_id) as patient_count
    FROM users u
    LEFT JOIN appointments a ON u.id = a.doctor_id
    WHERE u.role = 'doctor'
    GROUP BY u.id
    ORDER BY patient_count DESC
    LIMIT 5
")->fetchAll();

$averageBillingPerPatient = $pdo->query("
    SELECT 
        ROUND(SUM(amount) / NULLIF(COUNT(DISTINCT patient_id), 0), 2) as avg_billing,
        SUM(amount) as total_amount,
        COUNT(DISTINCT patient_id) as patient_count
    FROM bills
")->fetch();

$appointmentStatusBreakdown = $pdo->query("
    SELECT 
        status,
        COUNT(*) as count
    FROM appointments
    GROUP BY status
")->fetchAll();

$prescriptionStats = $pdo->query("
    SELECT COUNT(*) as total_prescriptions
    FROM prescriptions
")->fetch();

$patientGrowthLastMonth = $pdo->query("
    SELECT 
        COUNT(CASE WHEN MONTH(created_at) = MONTH(DATE_SUB(NOW(), INTERVAL 1 MONTH)) THEN 1 END) as last_month,
        COUNT(CASE WHEN MONTH(created_at) = MONTH(NOW()) THEN 1 END) as this_month
    FROM users
    WHERE role = 'patient'
")->fetch();
?>
<?php include '../inc/header.php'; ?>
<div style="margin-bottom: 2.5rem; animation: slideInUp 0.6s ease-out;">
    <h1 style="font-size: 3rem; margin-bottom: 0.5rem;">👨‍💼 Admin Dashboard</h1>
    <p class="text-muted" style="font-weight: 500; font-size: 1.1rem;">✨ Overview of your hospital management system</p>
</div>
<div class="row g-4 mb-5">
    <div class="col-md-3">
        <a href="doctors.php" style="text-decoration: none; color: inherit;">
            <div class="card dashboard-card shadow" style="cursor: pointer; transition: transform 0.3s, box-shadow 0.3s;" onmouseover="this.style.transform='translateY(-5px)'; this.style.boxShadow='0 15px 40px rgba(102, 126, 234, 0.4)';" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 2px 10px rgba(0,0,0,0.1)';">
                <div style="font-size: 3.5rem; animation: bounce 2s infinite;">👨‍⚕️</div>
                <h5 class="mt-3">Doctors</h5>
                <div class="value"><?= $totalDoctors ?></div>
            </div>
        </a>
    </div>
    <div class="col-md-3">
        <a href="patients.php" style="text-decoration: none; color: inherit;">
            <div class="card dashboard-card shadow" style="cursor: pointer; transition: transform 0.3s, box-shadow 0.3s;" onmouseover="this.style.transform='translateY(-5px)'; this.style.boxShadow='0 15px 40px rgba(102, 126, 234, 0.4)';" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 2px 10px rgba(0,0,0,0.1)';">
                <div style="font-size: 3.5rem; animation: bounce 2.2s infinite;">👥</div>
                <h5 class="mt-3">Patients</h5>
                <div class="value"><?= $totalPatients ?></div>
            </div>
        </a>
    </div>
    <div class="col-md-3">
        <a href="appointments.php" style="text-decoration: none; color: inherit;">
            <div class="card dashboard-card shadow" style="cursor: pointer; transition: transform 0.3s, box-shadow 0.3s;" onmouseover="this.style.transform='translateY(-5px)'; this.style.boxShadow='0 15px 40px rgba(102, 126, 234, 0.4)';" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 2px 10px rgba(0,0,0,0.1)';">
                <div style="font-size: 3.5rem; animation: bounce 2.4s infinite;">
                    <i class="bi bi-calendar-check" style="color: #667eea;"></i>
                </div>
                <h5 class="mt-3">Appointments</h5>
                <div class="value"><?= $totalAppointments ?></div>
            </div>
        </a>
    </div>
    <div class="col-md-3">
        <a href="appointments.php?status=pending" style="text-decoration: none; color: inherit;">
            <div class="card dashboard-card shadow" style="cursor: pointer; transition: transform 0.3s, box-shadow 0.3s;" onmouseover="this.style.transform='translateY(-5px)'; this.style.boxShadow='0 15px 40px rgba(102, 126, 234, 0.4)';" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 2px 10px rgba(0,0,0,0.1)';">
                <div style="font-size: 3.5rem; animation: bounce 2.6s infinite;">
                    <i class="bi bi-hourglass-split" style="color: #764ba2;"></i>
                </div>
                <h5 class="mt-3">Pending</h5>
                <div class="value"><?= $pendingAppointments ?></div>
            </div>
        </a>
    </div>
    <div class="col-md-3">
        <a href="bills.php" style="text-decoration: none; color: inherit;">
            <div class="card dashboard-card shadow" style="cursor: pointer; transition: transform 0.3s, box-shadow 0.3s;" onmouseover="this.style.transform='translateY(-5px)'; this.style.boxShadow='0 15px 40px rgba(102, 126, 234, 0.4)';" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 2px 10px rgba(0,0,0,0.1)';">
                <div style="font-size: 3.5rem; animation: bounce 2.8s infinite;">
                    <i class="bi bi-receipt" style="color: #10b981;"></i>
                </div>
                <h5 class="mt-3">Bills</h5>
                <div class="value"><?= $totalBills ?></div>
            </div>
        </a>
    </div>
</div>
<div class="row g-4">
    <div class="col-md-4">
        <div class="card shadow p-4">
            <h5 class="mb-4" style="font-size: 1.5rem;">⚙️ Core Management</h5>
            <ul class="list-group list-group-flush">
                <li class="list-group-item border-0 pb-2"><a href="doctors.php" class="btn btn-outline-primary w-100" style="font-weight: 700;">👨‍⚕️ Manage Doctors</a></li>
                <li class="list-group-item border-0 pb-2"><a href="patients.php" class="btn btn-outline-primary w-100" style="font-weight: 700;">👥 Manage Patients</a></li>
                <li class="list-group-item border-0 pb-2"><a href="appointments.php" class="btn btn-outline-primary w-100" style="font-weight: 700;">📅 View Appointments</a></li>
                <li class="list-group-item border-0 pb-2"><a href="bills.php" class="btn btn-outline-primary w-100" style="font-weight: 700;"><i class="bi bi-cash-coin"></i> Bills Management</a></li>
                <li class="list-group-item border-0"><a href="reports.php" class="btn btn-outline-primary w-100" style="font-weight: 700;"><i class="bi bi-file-earmark-text-fill"></i> Patient Reports</a></li>
            </ul>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card shadow p-4">
            <h5 class="mb-4" style="font-size: 1.5rem;">🆕 New Features</h5>
            <ul class="list-group list-group-flush">
                <li class="list-group-item border-0 pb-2"><a href="analytics.php" class="btn btn-outline-success w-100" style="font-weight: 700;">📊 Analytics Dashboard</a></li>
                <li class="list-group-item border-0 pb-2"><a href="medical_records.php" class="btn btn-outline-success w-100" style="font-weight: 700;">📋 Digital Medical Records</a></li>
                <li class="list-group-item border-0 pb-2"><a href="pharmacy.php" class="btn btn-outline-success w-100" style="font-weight: 700;">💊 Pharmacy Module</a></li>
                <li class="list-group-item border-0 pb-2"><a href="payments.php" class="btn btn-outline-success w-100" style="font-weight: 700;">💳 Online Payments</a></li>
                <li class="list-group-item border-0"><a href="notifications.php" class="btn btn-outline-success w-100" style="font-weight: 700;">📬 Notifications</a></li>
            </ul>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card shadow p-4">
            <h5 class="mb-4" style="font-size: 1.5rem;">🤖 AI & Tools</h5>
            <ul class="list-group list-group-flush">
                <li class="list-group-item border-0 pb-2"><a href="symptom_checker.php" class="btn btn-outline-info w-100" style="font-weight: 700;">🤖 Symptom Checker</a></li>
                <li class="list-group-item border-0 pb-3"></li>
                <div class="alert alert-success" style="margin: 0;">
                    <strong>✨ New Features Added:</strong>
                    <ul style="margin: 10px 0 0 0; padding-left: 20px;">
                        <li>📊 Charts & Analytics</li>
                        <li>💊 Pharmacy Management</li>
                        <li>💳 Payment Integration</li>
                        <li>📬 Email/SMS Alerts</li>
                        <li>📋 Digital Records (EMR)</li>
                        <li>📄 PDF Reports</li>
                        <li>🤖 AI Symptom Check</li>
                    </ul>
                </div>
            </ul>
        </div>
    </div>
</div>

<div class="row g-4 mt-2">
    <div class="col-md-12">
        <div class="card shadow p-4">
            <h5 class="mb-3" style="font-size: 1.5rem;">👋 Welcome, <?= sanitize($_SESSION['name']) ?>!</h5>
            <p style="font-size: 1.1rem; line-height: 1.6;">You are logged in as an <strong>Administrator</strong>. Use the management options to oversee doctors, patients, appointments, and billing information. Keep your hospital management system organized and efficient! 💪</p>
            <hr style="border-color: rgba(102, 126, 234, 0.2); margin: 1.5rem 0;">
            <p class="text-muted mb-0" style="font-size: 1rem;"><small>📌 <strong>Pro tip:</strong> Check the Analytics Dashboard for real-time hospital metrics, and use the AI Symptom Checker for patient triage.</small></p>
        </div>
    </div>
</div>

<!-- 👥 PATIENT REPORTS SECTION -->
<div class="row g-4 mt-5">
    <div class="col-md-12">
        <h3 style="font-size: 2rem; margin-bottom: 1.5rem;">👥 Patient Reports & Analytics</h3>
    </div>
</div>

<!-- Patient Statistics Cards -->
<div class="row g-4 mb-5">
    <div class="col-md-3">
        <div class="card shadow" style="border-left: 4px solid #667eea; transition: transform 0.3s;" onmouseover="this.style.transform='translateY(-5px)'" onmouseout="this.style.transform='translateY(0)'">
            <div class="card-body">
                <div style="font-size: 2.5rem; margin-bottom: 1rem;">📋</div>
                <h6 class="card-title text-muted">Total Patients</h6>
                <div style="font-size: 2.5rem; font-weight: bold; color: #667eea;"><?= $totalPatients ?></div>
                <small class="text-muted d-block mt-2">Registered patients</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card shadow" style="border-left: 4px solid #10b981; transition: transform 0.3s;" onmouseover="this.style.transform='translateY(-5px)'" onmouseout="this.style.transform='translateY(0)'">
            <div class="card-body">
                <div style="font-size: 2.5rem; margin-bottom: 1rem;">🆕</div>
                <h6 class="card-title text-muted">New This Month</h6>
                <div style="font-size: 2.5rem; font-weight: bold; color: #10b981;"><?= $newPatientsThisMonth ?></div>
                <small class="text-muted d-block mt-2">New registrations</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card shadow" style="border-left: 4px solid #f59e0b; transition: transform 0.3s;" onmouseover="this.style.transform='translateY(-5px)'" onmouseout="this.style.transform='translateY(0)'">
            <div class="card-body">
                <div style="font-size: 2.5rem; margin-bottom: 1rem;">📅</div>
                <h6 class="card-title text-muted">With Appointments</h6>
                <div style="font-size: 2.5rem; font-weight: bold; color: #f59e0b;"><?= $patientsWithAppointments ?></div>
                <small class="text-muted d-block mt-2">Have booked appointments</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card shadow" style="border-left: 4px solid #8b5cf6; transition: transform 0.3s;" onmouseover="this.style.transform='translateY(-5px)'" onmouseout="this.style.transform='translateY(0)'">
            <div class="card-body">
                <div style="font-size: 2.5rem; margin-bottom: 1rem;">💰</div>
                <h6 class="card-title text-muted">Pending Bills</h6>
                <div style="font-size: 2.5rem; font-weight: bold; color: #8b5cf6;"><?= number_format($patientStats['pending_bills'] ?? 0) ?></div>
                <small class="text-muted d-block mt-2">Awaiting payment</small>
            </div>
        </div>
    </div>
</div>

<!-- Billing Summary -->
<div class="row g-4 mb-5">
    <div class="col-md-6">
        <div class="card shadow p-4">
            <h5 class="mb-4">💳 Patient Billing Summary</h5>
            <div style="display: flex; justify-content: space-around; align-items: center;">
                <div style="text-align: center;">
                    <div style="font-size: 3rem; font-weight: bold; color: #10b981;">$<?= number_format($totalPaidBills, 2) ?></div>
                    <small class="text-muted d-block mt-2">Total Paid</small>
                </div>
                <div style="border-left: 2px solid #e5e7eb; padding-left: 2rem; padding-right: 2rem;">
                    <div style="text-align: center;">
                        <div style="font-size: 3rem; font-weight: bold; color: #ef4444;">$<?= number_format($totalPendingBills, 2) ?></div>
                        <small class="text-muted d-block mt-2">Total Pending</small>
                    </div>
                </div>
            </div>
            <hr style="margin: 1.5rem 0;">
            <div style="text-align: center;">
                <small class="text-muted">Total Revenue: <strong style="color: #667eea;">$<?= number_format($totalPaidBills + $totalPendingBills, 2) ?></strong></small>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card shadow p-4">
            <h5 class="mb-4">📊 Monthly Statistics</h5>
            <div style="padding: 1rem 0;">
                <div style="margin-bottom: 1.5rem;">
                    <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                        <span class="text-muted">Appointments This Month</span>
                        <strong style="color: #667eea;"><?= $patientStats['appointments_this_month'] ?? 0 ?></strong>
                    </div>
                    <div style="background: #e5e7eb; height: 8px; border-radius: 4px;">
                        <div style="background: #667eea; height: 100%; width: <?= min(100, (($patientStats['appointments_this_month'] ?? 0) / $totalAppointments * 100)) ?>%; border-radius: 4px;"></div>
                    </div>
                </div>
                <div style="margin-bottom: 1.5rem;">
                    <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                        <span class="text-muted">Paid Bills</span>
                        <strong style="color: #10b981;"><?= count($pdo->query("SELECT id FROM bills WHERE status = 'paid'")->fetchAll()) ?></strong>
                    </div>
                    <div style="background: #e5e7eb; height: 8px; border-radius: 4px;">
                        <div style="background: #10b981; height: 100%; width: <?= min(100, (count($pdo->query("SELECT id FROM bills WHERE status = 'paid'")->fetchAll()) / $totalBills * 100)) ?>%; border-radius: 4px;"></div>
                    </div>
                </div>
                <div>
                    <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                        <span class="text-muted">Pending Bills</span>
                        <strong style="color: #ef4444;"><?= $patientStats['pending_bills'] ?? 0 ?></strong>
                    </div>
                    <div style="background: #e5e7eb; height: 8px; border-radius: 4px;">
                        <div style="background: #ef4444; height: 100%; width: <?= min(100, (($patientStats['pending_bills'] ?? 0) / $totalBills * 100)) ?>%; border-radius: 4px;"></div>
    </div>
</div>

<!-- Appointment Status Breakdown -->
<div class="row g-4 mb-5">
    <div class="col-md-6">
        <div class="card shadow p-4">
            <h5 class="mb-4">📅 Appointment Status Report</h5>
            <div style="padding: 1rem 0;">
                <?php foreach ($appointmentStatusBreakdown as $status): ?>
                <div style="margin-bottom: 1.5rem;">
                    <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                        <span class="text-muted text-capitalize"><?= ucfirst($status['status']) ?></span>
                        <strong><?= $status['count'] ?></strong>
                    </div>
                    <div style="background: #e5e7eb; height: 10px; border-radius: 4px; overflow: hidden;">
                        <div style="background: 
                            <?php 
                                if ($status['status'] == 'Completed') echo '#10b981';
                                elseif ($status['status'] == 'Pending') echo '#f59e0b';
                                elseif ($status['status'] == 'Approved') echo '#667eea';
                                else echo '#ef4444';
                            ?>; 
                            height: 100%; 
                            width: <?= min(100, (($status['count'] / $totalAppointments) * 100)) ?>%; 
                            border-radius: 4px;
                        "></div>
                    </div>
                </div>
                <?php endforeach; ?>
                <hr style="margin: 1rem 0;">
                <div style="text-align: center;">
                    <strong>Completion Rate: <span style="color: #667eea; font-size: 1.3rem;"><?= $appointmentCompletionRate['completion_rate'] ?? 0 ?>%</span></strong>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card shadow p-4">
            <h5 class="mb-4">💊 Prescription & Growth Metrics</h5>
            <div style="padding: 1rem 0;">
                <div style="margin-bottom: 2rem;">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <span class="text-muted">Total Prescriptions</span>
                        <strong style="font-size: 1.5rem; color: #667eea;"><?= $prescriptionStats['total_prescriptions'] ?? 0 ?></strong>
                    </div>
                </div>
                <hr style="margin: 1rem 0;">
                <div style="margin-bottom: 2rem;">
                    <strong class="d-block mb-3">📈 Patient Growth</strong>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                        <div style="background: #f3f4f6; padding: 1rem; border-radius: 8px; text-align: center;">
                            <small class="text-muted d-block">Last Month</small>
                            <div style="font-size: 1.8rem; font-weight: bold; color: #764ba2;"><?= $patientGrowthLastMonth['last_month'] ?? 0 ?></div>
                        </div>
                        <div style="background: #f3f4f6; padding: 1rem; border-radius: 8px; text-align: center;">
                            <small class="text-muted d-block">This Month</small>
                            <div style="font-size: 1.8rem; font-weight: bold; color: #667eea;"><?= $patientGrowthLastMonth['this_month'] ?? 0 ?></div>
                        </div>
                    </div>
                    <?php 
                        $growth = $patientGrowthLastMonth['last_month'] > 0 
                            ? round((($patientGrowthLastMonth['this_month'] - $patientGrowthLastMonth['last_month']) / $patientGrowthLastMonth['last_month']) * 100, 2)
                            : 0;
                    ?>
                    <div style="text-align: center; margin-top: 1rem;">
                        <small class="text-muted">Growth: <strong style="color: <?= $growth >= 0 ? '#10b981' : '#ef4444' ?>;"><?= $growth >= 0 ? '+' : '' ?><?= $growth ?>%</strong></small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Top Patients & Doctors Reports -->
<div class="row g-4 mb-5">
    <div class="col-md-6">
        <div class="card shadow p-4">
            <h5 class="mb-4">⭐ Top Patients by Appointments</h5>
            <div class="table-responsive">
                <table class="table table-sm table-hover mb-0">
                    <thead style="background: #f3f4f6;">
                        <tr>
                            <th>Patient Name</th>
                            <th style="text-align: right;">Appointments</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($topPatientsByAppointments)): ?>
                            <?php foreach ($topPatientsByAppointments as $patient): ?>
                            <tr style="border-bottom: 1px solid #e5e7eb;">
                                <td><strong><?= sanitize($patient['full_name']) ?></strong></td>
                                <td style="text-align: right;">
                                    <span style="background: #667eea; color: white; padding: 4px 8px; border-radius: 20px; font-size: 0.85rem;">
                                        <?= $patient['appointment_count'] ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="2" class="text-center text-muted py-3">No appointment data available</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card shadow p-4">
            <h5 class="mb-4">👨‍⚕️ Top Doctors by Patients Treated</h5>
            <div class="table-responsive">
                <table class="table table-sm table-hover mb-0">
                    <thead style="background: #f3f4f6;">
                        <tr>
                            <th>Doctor Name</th>
                            <th>Specialty</th>
                            <th style="text-align: right;">Patients</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($topDoctorsByPatients)): ?>
                            <?php foreach ($topDoctorsByPatients as $doctor): ?>
                            <tr style="border-bottom: 1px solid #e5e7eb;">
                                <td><strong><?= sanitize($doctor['full_name']) ?></strong></td>
                                <td><small class="text-muted"><?= sanitize($doctor['specialty'] ?? 'General') ?></small></td>
                                <td style="text-align: right;">
                                    <span style="background: #10b981; color: white; padding: 4px 8px; border-radius: 20px; font-size: 0.85rem;">
                                        <?= $doctor['patient_count'] ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="3" class="text-center text-muted py-3">No doctor data available</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Billing Analytics Report -->
<div class="row g-4 mb-5">
    <div class="col-md-6">
        <div class="card shadow p-4">
            <h5 class="mb-4">💰 Billing Analytics</h5>
            <div style="padding: 1rem 0;">
                <div style="margin-bottom: 2rem;">
                    <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                        <span class="text-muted">Average Billing per Patient</span>
                    </div>
                    <div style="font-size: 2.5rem; font-weight: bold; color: #667eea;">$<?= number_format($averageBillingPerPatient['avg_billing'] ?? 0, 2) ?></div>
                </div>
                <hr style="margin: 1.5rem 0;">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div style="background: #f3f4f6; padding: 1rem; border-radius: 8px;">
                        <small class="text-muted d-block mb-2">Total Billing Amount</small>
                        <strong style="font-size: 1.3rem; color: #764ba2;">$<?= number_format($averageBillingPerPatient['total_amount'] ?? 0, 2) ?></strong>
                    </div>
                    <div style="background: #f3f4f6; padding: 1rem; border-radius: 8px;">
                        <small class="text-muted d-block mb-2">Total Patients Billed</small>
                        <strong style="font-size: 1.3rem; color: #764ba2;"><?= $averageBillingPerPatient['patient_count'] ?? 0 ?></strong>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card shadow p-4">
            <h5 class="mb-4">📊 Revenue Summary</h5>
            <div style="padding: 1rem 0;">
                <div style="margin-bottom: 1.5rem;">
                    <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                        <span class="text-muted">Collection Rate</span>
                        <strong style="color: #667eea;">
                            <?php 
                                $totalRevenue = ($totalPaidBills ?? 0) + ($totalPendingBills ?? 0);
                                $collectionRate = $totalRevenue > 0 ? round((($totalPaidBills ?? 0) / $totalRevenue) * 100, 2) : 0;
                            ?>
                            <?= $collectionRate ?>%
                        </strong>
                    </div>
                    <div style="background: #e5e7eb; height: 10px; border-radius: 4px; overflow: hidden;">
                        <div style="background: #10b981; height: 100%; width: <?= $collectionRate ?>%; border-radius: 4px;"></div>
                    </div>
                </div>
                <div style="margin-bottom: 1.5rem;">
                    <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                        <span class="text-muted">Outstanding Amount</span>
                        <strong style="color: #ef4444;">$<?= number_format($totalPendingBills ?? 0, 2) ?></strong>
                    </div>
                    <div style="background: #e5e7eb; height: 10px; border-radius: 4px; overflow: hidden;">
                        <div style="background: #ef4444; height: 100%; width: <?= 100 - $collectionRate ?>%; border-radius: 4px;"></div>
                    </div>
                </div>
                <hr style="margin: 1rem 0;">
                <div style="text-align: center;">
                    <small class="text-muted">Total Value: <strong style="color: #667eea;">$<?= number_format($totalRevenue, 2) ?></strong></small>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Recent Patients Table -->
<div class="row g-4 mb-5">
    <div class="col-md-12">
        <div class="card shadow p-4">
            <h5 class="mb-4">🆕 Recently Registered Patients</h5>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
                        <tr>
                            <th>Patient Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Registered</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($recentPatients)): ?>
                            <?php foreach ($recentPatients as $patient): ?>
                            <tr>
                                <td>
                                    <strong><?= sanitize($patient['full_name']) ?></strong>
                                </td>
                                <td><?= sanitize($patient['email']) ?></td>
                                <td><?= sanitize($patient['phone'] ?? 'N/A') ?></td>
                                <td>
                                    <small class="text-muted">
                                        <?= date('M d, Y', strtotime($patient['created_at'] ?? 'now')) ?>
                                    </small>
                                </td>
                                <td>
                                    <a href="patients.php?id=<?= $patient['id'] ?>" class="btn btn-sm btn-outline-primary">
                                        View Profile
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">No recent patients found</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php include '../inc/footer.php'; ?>
