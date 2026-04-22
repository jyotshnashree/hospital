<?php
/**
 * Enhanced Analytics & Charts Dashboard
 * Hospital Management System - Advanced reporting and visualization
 */

include '../db.php';
checkAuth();

// Get statistics
$stats = [
    'total_patients' => $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'patient'")->fetchColumn() ?? 0,
    'total_doctors' => $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'doctor'")->fetchColumn() ?? 0,
    'total_appointments' => $pdo->query("SELECT COUNT(*) FROM appointments")->fetchColumn() ?? 0,
    'pending_appointments' => $pdo->query("SELECT COUNT(*) FROM appointments WHERE status = 'Pending'")->fetchColumn() ?? 0,
    'total_revenue' => $pdo->query("SELECT COALESCE(SUM(amount), 0) FROM payments")->fetchColumn() ?? 0,
    'total_bills' => $pdo->query("SELECT COUNT(*) FROM bills")->fetchColumn() ?? 0,
];

// Appointments by status
$appointmentsByStatus = $pdo->query("
    SELECT status, COUNT(*) as count 
    FROM appointments 
    GROUP BY status
")->fetchAll(PDO::FETCH_KEY_PAIR);

// Monthly appointments
$monthlyAppointments = $pdo->query("
    SELECT DATE_FORMAT(appointment_date, '%Y-%m') as month, COUNT(*) as count 
    FROM appointments 
    WHERE appointment_date >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
    GROUP BY DATE_FORMAT(appointment_date, '%Y-%m')
    ORDER BY month DESC
    LIMIT 12
")->fetchAll(PDO::FETCH_KEY_PAIR);

// Top doctors by appointments
$topDoctors = $pdo->query("
    SELECT u.full_name, COUNT(a.id) as appointment_count
    FROM users u
    LEFT JOIN appointments a ON u.id = a.doctor_id
    WHERE u.role = 'doctor'
    GROUP BY u.id, u.full_name
    ORDER BY appointment_count DESC
    LIMIT 5
")->fetchAll();

// Revenue by month
$monthlyRevenue = $pdo->query("
    SELECT DATE_FORMAT(paid_at, '%Y-%m') as month, SUM(amount) as revenue
    FROM payments
    WHERE paid_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
    GROUP BY DATE_FORMAT(paid_at, '%Y-%m')
    ORDER BY month DESC
    LIMIT 12
")->fetchAll(PDO::FETCH_KEY_PAIR);

// Patient growth
$patientGrowth = $pdo->query("
    SELECT DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(*) as count
    FROM users
    WHERE role = 'patient' AND created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
    GROUP BY DATE_FORMAT(created_at, '%Y-%m')
    ORDER BY month ASC
")->fetchAll(PDO::FETCH_KEY_PAIR);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analytics Dashboard - Hospital Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../styles.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
</head>
<body>
<?php include '../inc/header.php'; ?>

<div class="container-fluid py-5">
    <div style="margin-bottom: 2.5rem;">
        <h1 style="font-size: 2.5rem;">📊 Analytics & Reports Dashboard</h1>
        <p class="text-muted">Hospital performance metrics and insights</p>
    </div>

    <!-- Key Metrics -->
    <div class="row mb-4">
        <div class="col-md-2 col-sm-6 mb-3">
            <div class="card shadow text-center p-3">
                <div style="font-size: 2rem;">👥</div>
                <h6 class="mt-2">Total Patients</h6>
                <div style="font-size: 1.8rem; color: #667eea; font-weight: bold;">
                    <?= number_format($stats['total_patients']) ?>
                </div>
            </div>
        </div>
        <div class="col-md-2 col-sm-6 mb-3">
            <div class="card shadow text-center p-3">
                <div style="font-size: 2rem;">👨‍⚕️</div>
                <h6 class="mt-2">Total Doctors</h6>
                <div style="font-size: 1.8rem; color: #27ae60; font-weight: bold;">
                    <?= number_format($stats['total_doctors']) ?>
                </div>
            </div>
        </div>
        <div class="col-md-2 col-sm-6 mb-3">
            <div class="card shadow text-center p-3">
                <div style="font-size: 2rem;">📅</div>
                <h6 class="mt-2">Total Appointments</h6>
                <div style="font-size: 1.8rem; color: #3498db; font-weight: bold;">
                    <?= number_format($stats['total_appointments']) ?>
                </div>
            </div>
        </div>
        <div class="col-md-2 col-sm-6 mb-3">
            <div class="card shadow text-center p-3">
                <div style="font-size: 2rem;">⏳</div>
                <h6 class="mt-2">Pending</h6>
                <div style="font-size: 1.8rem; color: #f39c12; font-weight: bold;">
                    <?= number_format($stats['pending_appointments']) ?>
                </div>
            </div>
        </div>
        <div class="col-md-2 col-sm-6 mb-3">
            <div class="card shadow text-center p-3">
                <div style="font-size: 2rem;">💰</div>
                <h6 class="mt-2">Total Revenue</h6>
                <div style="font-size: 1.8rem; color: #27ae60; font-weight: bold;">
                    $<?= number_format($stats['total_revenue'], 2) ?>
                </div>
            </div>
        </div>
        <div class="col-md-2 col-sm-6 mb-3">
            <div class="card shadow text-center p-3">
                <div style="font-size: 2rem;">📜</div>
                <h6 class="mt-2">Total Bills</h6>
                <div style="font-size: 1.8rem; color: #e74c3c; font-weight: bold;">
                    <?= number_format($stats['total_bills']) ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Row 1 -->
    <div class="row mb-4">
        <div class="col-lg-6">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Appointments by Status</h5>
                </div>
                <div class="card-body">
                    <canvas id="appointmentStatusChart"></canvas>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card shadow">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">Top Doctors by Appointments</h5>
                </div>
                <div class="card-body">
                    <canvas id="topDoctorsChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Row 2 -->
    <div class="row mb-4">
        <div class="col-lg-12">
            <div class="card shadow">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">Monthly Appointments Trend (Last 12 Months)</h5>
                </div>
                <div class="card-body">
                    <canvas id="monthlyAppointmentsChart" style="max-height: 300px;"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Row 3 -->
    <div class="row mb-4">
        <div class="col-lg-6">
            <div class="card shadow">
                <div class="card-header bg-warning text-white">
                    <h5 class="mb-0">Monthly Revenue Trend</h5>
                </div>
                <div class="card-body">
                    <canvas id="monthlyRevenueChart"></canvas>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card shadow">
                <div class="card-header bg-danger text-white">
                    <h5 class="mb-0">Patient Growth Trend</h5>
                </div>
                <div class="card-body">
                    <canvas id="patientGrowthChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Additional Metrics -->
    <div class="card shadow mt-4">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">Top Performing Doctors</h5>
        </div>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Doctor Name</th>
                        <th>Appointments</th>
                        <th>Performance</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($topDoctors)): ?>
                        <?php foreach ($topDoctors as $doctor): ?>
                            <tr>
                                <td><?= htmlspecialchars($doctor['full_name']) ?></td>
                                <td><?= number_format($doctor['appointment_count']) ?></td>
                                <td>
                                    <div class="progress" style="height: 20px;">
                                        <div class="progress-bar" style="width: <?= ($doctor['appointment_count'] / max(1, current(array_column($topDoctors, 'appointment_count'))) * 100) ?>%">
                                            <?= round(($doctor['appointment_count'] / max(1, current(array_column($topDoctors, 'appointment_count'))) * 100), 1) ?>%
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="3" class="text-center text-muted py-4">No data available</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include '../inc/footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Common Chart Options
    const chartOptions = {
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
            legend: {
                position: 'bottom',
                labels: {
                    boxWidth: 12,
                    font: { size: 12 },
                    padding: 15
                }
            }
        }
    };

    // Appointment Status Chart
    const statusCtx = document.getElementById('appointmentStatusChart').getContext('2d');
    new Chart(statusCtx, {
        type: 'doughnut',
        data: {
            labels: <?= json_encode(array_keys($appointmentsByStatus ?? [])) ?>,
            datasets: [{
                data: <?= json_encode(array_values($appointmentsByStatus ?? [])) ?>,
                backgroundColor: ['#FFC107', '#28A745', '#17A2B8', '#DC3545']
            }]
        },
        options: { 
            responsive: true, 
            plugins: { legend: { position: 'bottom' } } 
        }
    });

    // Top Doctors Chart
    const doctorsCtx = document.getElementById('topDoctorsChart').getContext('2d');
    new Chart(doctorsCtx, {
        type: 'bar',
        data: {
            labels: <?= json_encode(array_column($topDoctors, 'full_name')) ?>,
            datasets: [{
                label: 'Appointments',
                data: <?= json_encode(array_column($topDoctors, 'appointment_count')) ?>,
                backgroundColor: '#667eea'
            }]
        },
        options: { 
            ...chartOptions,
            scales: { 
                y: { 
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1
                    }
                } 
            }
        }
    });

    // Monthly Appointments Chart
    const monthlyCtx = document.getElementById('monthlyAppointmentsChart').getContext('2d');
    new Chart(monthlyCtx, {
        type: 'line',
        data: {
            labels: <?= json_encode(array_keys(array_reverse($monthlyAppointments ?? []))) ?>,
            datasets: [{
                label: 'Appointments',
                data: <?= json_encode(array_values(array_reverse($monthlyAppointments ?? []))) ?>,
                borderColor: '#3498db',
                backgroundColor: 'rgba(52, 152, 219, 0.1)',
                tension: 0.4,
                fill: true,
                borderWidth: 2
            }]
        },
        options: { 
            ...chartOptions,
            scales: { 
                y: { 
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1
                    }
                } 
            }
        }
    });

    // Monthly Revenue Chart
    const revenueCtx = document.getElementById('monthlyRevenueChart').getContext('2d');
    new Chart(revenueCtx, {
        type: 'bar',
        data: {
            labels: <?= json_encode(array_keys(array_reverse($monthlyRevenue ?? []))) ?>,
            datasets: [{
                label: 'Revenue ($)',
                data: <?= json_encode(array_values(array_reverse($monthlyRevenue ?? []))) ?>,
                backgroundColor: '#27ae60'
            }]
        },
        options: { 
            ...chartOptions,
            scales: { 
                y: { 
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return '$' + value.toLocaleString();
                        }
                    }
                } 
            }
        }
    });

    // Patient Growth Chart
    const growthCtx = document.getElementById('patientGrowthChart').getContext('2d');
    new Chart(growthCtx, {
        type: 'line',
        data: {
            labels: <?= json_encode(array_keys($patientGrowth ?? [])) ?>,
            datasets: [{
                label: 'New Patients',
                data: <?= json_encode(array_values($patientGrowth ?? [])) ?>,
                borderColor: '#e74c3c',
                backgroundColor: 'rgba(231, 76, 60, 0.1)',
                tension: 0.4,
                fill: true,
                borderWidth: 2
            }]
        },
        options: { 
            ...chartOptions,
            scales: { 
                y: { 
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1
                    }
                } 
            }
        }
    });
</script>
</body>
</html>
