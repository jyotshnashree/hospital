<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    switch ($_SESSION['role']) {
        case 'admin':
            header('Location: admin/dashboard.php');
            break;
        case 'doctor':
            header('Location: doctor/dashboard.php');
            break;
        case 'patient':
            header('Location: patient/dashboard.php');
            break;
        default:
            header('Location: dashboard.php');
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hospital Management System - Portal Selection</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            padding: 40px 20px;
        }

        .portal-container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .header-section {
            text-align: center;
            color: white;
            margin-bottom: 60px;
            animation: slideDown 0.6s ease;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .header-section h1 {
            font-size: 3rem;
            font-weight: 700;
            margin-bottom: 10px;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.2);
        }

        .header-section p {
            font-size: 1.1rem;
            opacity: 0.95;
        }

        .hospital-logo {
            font-size: 5rem;
            margin-bottom: 20px;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.1); }
        }

        .portals-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
            margin-bottom: 50px;
        }

        .portal-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.15);
            overflow: hidden;
            transition: all 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
            cursor: pointer;
            position: relative;
            display: flex;
            flex-direction: column;
        }

        .portal-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 5px;
            background: linear-gradient(90deg, #667eea, #764ba2);
        }

        .portal-card:hover {
            transform: translateY(-15px);
            box-shadow: 0 25px 60px rgba(0, 0, 0, 0.25);
        }

        .portal-card.doctor::before {
            background: linear-gradient(90deg, #1e3c72, #2a5298);
        }

        .portal-card.patient::before {
            background: linear-gradient(90deg, #667eea, #764ba2);
        }

        .portal-card.admin::before {
            background: linear-gradient(90deg, #f093fb, #f5576c);
        }

        .portal-header {
            padding: 40px 30px 30px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-align: center;
        }

        .portal-card.doctor .portal-header {
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
        }

        .portal-card.admin .portal-header {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        }

        .portal-icon {
            font-size: 4rem;
            margin-bottom: 15px;
        }

        .portal-header h2 {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 8px;
        }

        .portal-header p {
            font-size: 0.95rem;
            opacity: 0.9;
        }

        .portal-body {
            padding: 30px;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
        }

        .portal-description {
            color: #666;
            margin-bottom: 25px;
            font-size: 0.95rem;
            line-height: 1.6;
        }

        .portal-features {
            list-style: none;
            margin-bottom: 25px;
            flex-grow: 1;
        }

        .portal-features li {
            padding: 8px 0;
            display: flex;
            align-items: center;
            gap: 10px;
            color: #555;
            font-size: 0.9rem;
        }

        .portal-features i {
            color: #667eea;
            font-weight: 700;
        }

        .portal-card.doctor .portal-features i {
            color: #1e3c72;
        }

        .portal-card.admin .portal-features i {
            color: #f5576c;
        }

        .portal-button {
            display: block;
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            text-align: center;
        }

        .portal-card.doctor .portal-button {
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
        }

        .portal-card.admin .portal-button {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        }

        .portal-button:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.2);
            text-decoration: none;
            color: white;
        }

        .info-section {
            background: rgba(255, 255, 255, 0.95);
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
            margin-top: 40px;
        }

        .info-section h3 {
            color: #333;
            font-weight: 700;
            margin-bottom: 20px;
            font-size: 1.5rem;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
        }

        .info-item {
            display: flex;
            gap: 15px;
            align-items: flex-start;
        }

        .info-item i {
            font-size: 1.8rem;
            color: #667eea;
            flex-shrink: 0;
            margin-top: 3px;
        }

        .info-item-content h4 {
            color: #333;
            font-weight: 600;
            margin-bottom: 5px;
            font-size: 0.95rem;
        }

        .info-item-content p {
            color: #666;
            font-size: 0.85rem;
            margin: 0;
        }

        .footer-section {
            text-align: center;
            color: white;
            margin-top: 50px;
            padding-top: 30px;
            border-top: 2px solid rgba(255, 255, 255, 0.2);
        }

        .footer-section p {
            margin: 10px 0;
            font-size: 0.95rem;
        }

        .footer-section a {
            color: white;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .footer-section a:hover {
            text-decoration: underline;
        }

        @media (max-width: 768px) {
            .header-section h1 {
                font-size: 2rem;
            }

            .portals-grid {
                grid-template-columns: 1fr;
                gap: 20px;
            }

            .info-grid {
                grid-template-columns: 1fr;
            }

            .portal-header {
                padding: 30px 20px 20px;
            }

            .portal-body {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
<div class="portal-container">
    <!-- Header Section -->
    <div class="header-section">
        <div class="hospital-logo">🏥</div>
        <h1>Hospital Management System</h1>
        <p>Secure Access Portal for Healthcare Professionals & Patients</p>
    </div>

    <!-- Portal Cards -->
    <div class="portals-grid">
        <!-- Doctor Portal -->
        <div class="portal-card doctor">
            <div class="portal-header">
                <div class="portal-icon">👨‍⚕️</div>
                <h2>Doctor Portal</h2>
                <p>Healthcare Professional Access</p>
            </div>
            <div class="portal-body">
                <div class="portal-description">
                    Manage your schedule, consult patients, and maintain medical records with our comprehensive doctor platform.
                </div>
                <ul class="portal-features">
                    <li><i class="bi bi-check-circle-fill"></i> Manage Appointments</li>
                    <li><i class="bi bi-check-circle-fill"></i> Patient Consultations</li>
                    <li><i class="bi bi-check-circle-fill"></i> Prescriptions</li>
                    <li><i class="bi bi-check-circle-fill"></i> Medical Records</li>
                    <li><i class="bi bi-check-circle-fill"></i> Schedule Availability</li>
                </ul>
                <a href="doctor_login.php" class="portal-button">
                    <i class="bi bi-box-arrow-in-right"></i> Doctor Login
                </a>
            </div>
        </div>

        <!-- Patient Portal -->
        <div class="portal-card patient">
            <div class="portal-header">
                <div class="portal-icon">👤</div>
                <h2>Patient Portal</h2>
                <p>Patient Care Management</p>
            </div>
            <div class="portal-body">
                <div class="portal-description">
                    Book appointments, consult doctors, manage your health records, and access prescriptions easily.
                </div>
                <ul class="portal-features">
                    <li><i class="bi bi-check-circle-fill"></i> Book Appointments</li>
                    <li><i class="bi bi-check-circle-fill"></i> Chat with Doctors</li>
                    <li><i class="bi bi-check-circle-fill"></i> View Medical Records</li>
                    <li><i class="bi bi-check-circle-fill"></i> Check Prescriptions</li>
                    <li><i class="bi bi-check-circle-fill"></i> Manage Bills</li>
                </ul>
                <a href="login.php?role=patient" class="portal-button">
                    <i class="bi bi-box-arrow-in-right"></i> Patient Login
                </a>
            </div>
        </div>

        <!-- Admin Portal -->
        <div class="portal-card admin">
            <div class="portal-header">
                <div class="portal-icon">🔐</div>
                <h2>Admin Portal</h2>
                <p>System Administration</p>
            </div>
            <div class="portal-body">
                <div class="portal-description">
                    Manage hospital staff, view system reports, and maintain overall hospital operations and data.
                </div>
                <ul class="portal-features">
                    <li><i class="bi bi-check-circle-fill"></i> Manage Staff</li>
                    <li><i class="bi bi-check-circle-fill"></i> System Reports</li>
                    <li><i class="bi bi-check-circle-fill"></i> Approve Doctors</li>
                    <li><i class="bi bi-check-circle-fill"></i> Audit Logs</li>
                    <li><i class="bi bi-check-circle-fill"></i> Payment Management</li>
                </ul>
                <a href="login.php?role=admin" class="portal-button">
                    <i class="bi bi-box-arrow-in-right"></i> Admin Login
                </a>
            </div>
        </div>
    </div>

    <!-- Info Section -->
    <div class="info-section">
        <h3>Why Choose Our Hospital Management System?</h3>
        <div class="info-grid">
            <div class="info-item">
                <i class="bi bi-shield-check"></i>
                <div class="info-item-content">
                    <h4>Secure & Encrypted</h4>
                    <p>Enterprise-grade security for all medical data</p>
                </div>
            </div>
            <div class="info-item">
                <i class="bi bi-lightning-charge"></i>
                <div class="info-item-content">
                    <h4>Fast & Reliable</h4>
                    <p>24/7 availability with minimal downtime</p>
                </div>
            </div>
            <div class="info-item">
                <i class="bi bi-person-check"></i>
                <div class="info-item-content">
                    <h4>User Friendly</h4>
                    <p>Intuitive interface for all users</p>
                </div>
            </div>
            <div class="info-item">
                <i class="bi bi-graph-up"></i>
                <div class="info-item-content">
                    <h4>Analytics & Reports</h4>
                    <p>Real-time insights and data analytics</p>
                </div>
            </div>
            <div class="info-item">
                <i class="bi bi-chat-dots"></i>
                <div class="info-item-content">
                    <h4>Direct Communication</h4>
                    <p>Secure messaging between doctors and patients</p>
                </div>
            </div>
            <div class="info-item">
                <i class="bi bi-calendar-check"></i>
                <div class="info-item-content">
                    <h4>Easy Scheduling</h4>
                    <p>Flexible appointment booking system</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer Section -->
    <div class="footer-section">
        <p>© 2026 Hospital Management System. All rights reserved.</p>
        <p>
            <a href="#">Privacy Policy</a> • 
            <a href="#">Terms & Conditions</a> • 
            <a href="#">Contact Support</a>
        </p>
    </div>
</div>
</body>
</html>
