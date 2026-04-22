<?php
/**
 * Hospital Statistics - Public View
 * Shows counts of patients, appointments, pending, etc.
 */

include 'db.php';

// Get statistics
$stats = [
    'total_patients' => $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'patient'")->fetchColumn() ?? 0,
    'total_doctors' => $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'doctor'")->fetchColumn() ?? 0,
    'total_appointments' => $pdo->query("SELECT COUNT(*) FROM appointments")->fetchColumn() ?? 0,
    'pending_appointments' => $pdo->query("SELECT COUNT(*) FROM appointments WHERE status = 'Pending'")->fetchColumn() ?? 0,
    'approved_appointments' => $pdo->query("SELECT COUNT(*) FROM appointments WHERE status = 'Approved'")->fetchColumn() ?? 0,
    'completed_appointments' => $pdo->query("SELECT COUNT(*) FROM appointments WHERE status = 'Completed'")->fetchColumn() ?? 0,
    'cancelled_appointments' => $pdo->query("SELECT COUNT(*) FROM appointments WHERE status = 'Cancelled'")->fetchColumn() ?? 0,
    'total_bills' => $pdo->query("SELECT COUNT(*) FROM bills")->fetchColumn() ?? 0,
    'pending_bills' => $pdo->query("SELECT COUNT(*) FROM bills WHERE status = 'pending'")->fetchColumn() ?? 0,
    'total_users' => $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn() ?? 0,
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hospital Statistics - Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .stat-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 20px;
            box-shadow: 0 8px 16px rgba(0,0,0,0.1);
            transition: transform 0.3s, box-shadow 0.3s;
        }
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 24px rgba(0,0,0,0.15);
        }
        .stat-number {
            font-size: 2.5rem;
            font-weight: bold;
            color: #667eea;
            margin: 10px 0;
        }
        .stat-label {
            font-size: 1.1rem;
            color: #666;
            font-weight: 500;
        }
        .stat-icon {
            font-size: 2.5rem;
            margin-bottom: 10px;
        }
        .header {
            color: white;
            text-align: center;
            margin-bottom: 40px;
            padding-top: 20px;
        }
        .header h1 {
            font-size: 2.5rem;
            font-weight: bold;
            margin-bottom: 10px;
        }
        .header p {
            font-size: 1.1rem;
            opacity: 0.9;
        }
        .container-custom {
            background: rgba(255,255,255,0.95);
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        .summary-section {
            margin-top: 30px;
            padding-top: 30px;
            border-top: 2px solid #e0e0e0;
        }
        .summary-row {
            display: flex;
            justify-content: space-around;
            flex-wrap: wrap;
            gap: 20px;
            margin-bottom: 20px;
        }
        .button-group {
            margin-top: 40px;
            text-align: center;
        }
        .button-group a {
            margin: 0 10px;
        }
    </style>
</head>
<body>

<div class="header">
    <h1>🏥 Hospital Management System</h1>
    <p>Hospital Statistics & Overview</p>
</div>

<div class="container">
    <div class="container-custom">
        
        <h2 style="color: #333; margin-bottom: 30px; text-align: center;">📊 Key Metrics</h2>

        <div class="row">
            <!-- Patients -->
            <div class="col-md-3 col-sm-6">
                <div class="stat-card">
                    <div class="stat-icon">👥</div>
                    <div class="stat-label">Total Patients</div>
                    <div class="stat-number"><?= $stats['total_patients'] ?></div>
                </div>
            </div>

            <!-- Doctors -->
            <div class="col-md-3 col-sm-6">
                <div class="stat-card">
                    <div class="stat-icon">👨‍⚕️</div>
                    <div class="stat-label">Total Doctors</div>
                    <div class="stat-number"><?= $stats['total_doctors'] ?></div>
                </div>
            </div>

            <!-- Total Appointments -->
            <div class="col-md-3 col-sm-6">
                <div class="stat-card">
                    <div class="stat-icon">📅</div>
                    <div class="stat-label">Total Appointments</div>
                    <div class="stat-number"><?= $stats['total_appointments'] ?></div>
                </div>
            </div>

            <!-- Total Users -->
            <div class="col-md-3 col-sm-6">
                <div class="stat-card">
                    <div class="stat-icon">👤</div>
                    <div class="stat-label">Total Users</div>
                    <div class="stat-number"><?= $stats['total_users'] ?></div>
                </div>
            </div>
        </div>

        <!-- Appointment Status Breakdown -->
        <div class="summary-section">
            <h3 style="color: #333; margin-bottom: 20px;">📈 Appointment Status Breakdown</h3>
            <div class="row">
                <div class="col-md-3 col-sm-6">
                    <div class="stat-card" style="border-left: 4px solid #FFC107;">
                        <div class="stat-icon">⏳</div>
                        <div class="stat-label">Pending</div>
                        <div class="stat-number" style="color: #FFC107;"><?= $stats['pending_appointments'] ?></div>
                    </div>
                </div>

                <div class="col-md-3 col-sm-6">
                    <div class="stat-card" style="border-left: 4px solid #28A745;">
                        <div class="stat-icon">✅</div>
                        <div class="stat-label">Approved</div>
                        <div class="stat-number" style="color: #28A745;"><?= $stats['approved_appointments'] ?></div>
                    </div>
                </div>

                <div class="col-md-3 col-sm-6">
                    <div class="stat-card" style="border-left: 4px solid #17A2B8;">
                        <div class="stat-icon">🎯</div>
                        <div class="stat-label">Completed</div>
                        <div class="stat-number" style="color: #17A2B8;"><?= $stats['completed_appointments'] ?></div>
                    </div>
                </div>

                <div class="col-md-3 col-sm-6">
                    <div class="stat-card" style="border-left: 4px solid #DC3545;">
                        <div class="stat-icon">❌</div>
                        <div class="stat-label">Cancelled</div>
                        <div class="stat-number" style="color: #DC3545;"><?= $stats['cancelled_appointments'] ?></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Billing Overview -->
        <div class="summary-section">
            <h3 style="color: #333; margin-bottom: 20px;">💳 Billing Overview</h3>
            <div class="row">
                <div class="col-md-6">
                    <div class="stat-card">
                        <div class="stat-icon">📜</div>
                        <div class="stat-label">Total Bills</div>
                        <div class="stat-number"><?= $stats['total_bills'] ?></div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="stat-card">
                        <div class="stat-icon">⚠️</div>
                        <div class="stat-label">Pending Bills</div>
                        <div class="stat-number" style="color: #DC3545;"><?= $stats['pending_bills'] ?></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="button-group">
            <a href="login.php" class="btn btn-primary btn-lg">🔐 Login to Admin Panel</a>
            <a href="index.php" class="btn btn-secondary btn-lg">🏠 Home</a>
        </div>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
