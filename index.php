<?php
include 'db.php';
if (!empty($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hospital Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="styles.css">
</head>
<body>
<div class="container py-5">
    <div class="text-center mb-5">
        <div style="font-size: 5rem; animation: bounce 2s infinite; display: inline-block;">🏥</div>
        <h1 class="display-4 mb-3 mt-4">Hospital<br>Management<br>System</h1>
        <p class="text-muted fs-5" style="font-weight: 600;">✨ A secure, modern system with role-based access ✨</p>
    </div>

    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow">
                <div class="card-body text-center">
                    <h4 class="card-title mb-2">🎉 Welcome to HMS</h4>
                    <p class="card-text" style="font-size: 1.1rem; font-weight: 500;">Select your login portal or register as a new patient.</p>
                    <div class="d-grid gap-3 mt-5">
                        <a href="portals.php" class="btn btn-primary btn-lg">
                            <i class="bi bi-door-open"></i> View All Login Portals
                        </a>
                        <a href="doctor_login.php" class="btn btn-outline-info btn-lg">
                            <i class="bi bi-stethoscope"></i> Doctor Login
                        </a>
                        <a href="login.php?role=patient" class="btn btn-outline-primary btn-lg">
                            <i class="bi bi-person-heart"></i> Patient Login
                        </a>
                        <a href="register.php" class="btn btn-outline-success btn-lg">
                            <i class="bi bi-clipboard-check"></i> Register New Account
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row justify-content-center mt-5">
        <div class="col-md-10">
            <div class="row g-3">
                <div class="col-md-4">
                    <div class="card dashboard-card">
                        <div style="font-size: 3.5rem; animation: bounce 2s infinite;">📋</div>
                        <h5 class="mt-3">Easy Appointments</h5>
                        <small style="font-weight: 500;">Schedule appointments with doctors</small>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card dashboard-card">
                        <div style="font-size: 3.5rem; animation: bounce 2.3s infinite;">💊</div>
                        <h5 class="mt-3">Digital Prescriptions</h5>
                        <small style="font-weight: 500;">View and manage your prescriptions</small>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card dashboard-card">
                        <div style="font-size: 3.5rem; animation: bounce 2.6s infinite;">💰</div>
                        <h5 class="mt-3">Billing Management</h5>
                        <small style="font-weight: 500;">Track your medical bills easily</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- New Features Section -->
    <div class="container mt-5">
        <h2 class="text-center mb-4" style="font-size: 2rem; font-weight: bold;">✨ Advanced Features</h2>
        <div class="row justify-content-center">
            <div class="col-md-10">
                <div class="row g-3">
                    <div class="col-md-4 col-sm-6">
                        <div class="card dashboard-card">
                            <div style="font-size: 3rem;">📊</div>
                            <h5 class="mt-3">Analytics Dashboard</h5>
                            <small style="font-weight: 500;">Real-time charts & hospital metrics</small>
                        </div>
                    </div>
                    <div class="col-md-4 col-sm-6">
                        <div class="card dashboard-card">
                            <div style="font-size: 3rem;">🤖</div>
                            <h5 class="mt-3">AI Symptom Checker</h5>
                            <small style="font-weight: 500;">Smart symptom analysis tool</small>
                        </div>
                    </div>
                    <div class="col-md-4 col-sm-6">
                        <div class="card dashboard-card">
                            <div style="font-size: 3rem;">📋</div>
                            <h5 class="mt-3">Digital Medical Records</h5>
                            <small style="font-weight: 500;">Complete patient health history</small>
                        </div>
                    </div>
                    <div class="col-md-4 col-sm-6">
                        <div class="card dashboard-card">
                            <div style="font-size: 3rem;">💊</div>
                            <h5 class="mt-3">Pharmacy Module</h5>
                            <small style="font-weight: 500;">Medication inventory management</small>
                        </div>
                    </div>
                    <div class="col-md-4 col-sm-6">
                        <div class="card dashboard-card">
                            <div style="font-size: 3rem;">💳</div>
                            <h5 class="mt-3">Online Payments</h5>
                            <small style="font-weight: 500;">Secure payment integration</small>
                        </div>
                    </div>
                    <div class="col-md-4 col-sm-6">
                        <div class="card dashboard-card">
                            <div style="font-size: 3rem;">📬</div>
                            <h5 class="mt-3">Notifications</h5>
                            <small style="font-weight: 500;">Email & SMS alerts</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="container mt-5 mb-5">
        <div class="row justify-content-center">
            <div class="col-md-10">
                <div class="alert alert-info">
                    <h5>📖 New Features Guide</h5>
                    <p class="mb-0">
                        Check out our <strong><a href="NEW_FEATURES_GUIDE.md" style="color: #0056b3;">NEW_FEATURES_GUIDE.md</a></strong> 
                        for detailed information about all the advanced features, setup instructions, and integration guides.
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
