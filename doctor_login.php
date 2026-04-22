<?php
/**
 * Hospital Management System - Doctor Login Page
 * Specialized login panel for doctors
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include 'db.php';

$error = '';
$success = '';

// Check if already logged in as doctor
if (isset($_SESSION['user_id']) && $_SESSION['role'] === 'doctor') {
    header('Location: doctor/dashboard.php');
    exit;
} elseif (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $license = trim($_POST['license_number'] ?? '');

    if (!$email || !$password) {
        $error = 'Please enter both email and password.';
    } else {
        try {
            // Find doctor with matching email and role
            $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ? AND role = ? LIMIT 1');
            $stmt->execute([$email, 'doctor']);
            $doctor = $stmt->fetch();

            if ($doctor && password_verify($password, $doctor['password'])) {
                // Check if account is active
                if (!$doctor['is_active']) {
                    $error = '❌ Your account has been deactivated. Please contact the hospital administration.';
                } else {
                    // Check license number if provided
                    if ($license && $doctor['license_number'] !== $license) {
                        $error = '❌ License number does not match our records.';
                    } else {
                        // Successful login
                        $_SESSION['user_id'] = $doctor['id'];
                        $_SESSION['role'] = 'doctor';
                        $_SESSION['name'] = $doctor['full_name'];
                        $_SESSION['email'] = $doctor['email'];
                        $_SESSION['specialty'] = $doctor['specialty'] ?? '';
                        $_SESSION['license_number'] = $doctor['license_number'] ?? '';

                        // Log login activity
                        try {
                            $logStmt = $pdo->prepare('INSERT INTO audit_logs (user_id, action, details, timestamp) VALUES (?, ?, ?, NOW())');
                            $logStmt->execute([$doctor['id'], 'LOGIN', 'Doctor logged in']);
                        } catch (Exception $e) {
                            // Log table might not exist, continue anyway
                        }

                        header('Location: doctor/dashboard.php');
                        exit;
                    }
                }
            } else {
                $error = '❌ Invalid email or password. Please check your credentials and try again.';
            }
        } catch (Exception $e) {
            $error = '❌ Login error: ' . $e->getMessage();
        }
    }
}

// Demo doctor credentials for testing
$demoDoctor = ['email' => 'sarah@hospital.com', 'password' => 'doctor123'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doctor Login - Hospital Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .login-wrapper {
            width: 100%;
            max-width: 550px;
            padding: 20px;
        }

        .doctor-login-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            overflow: hidden;
            transition: transform 0.3s ease;
        }

        .doctor-login-card:hover {
            transform: translateY(-5px);
        }

        .login-header {
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            color: white;
            padding: 50px 20px;
            text-align: center;
            position: relative;
        }

        .login-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><circle cx="20" cy="20" r="20" fill="rgba(255,255,255,0.05)"/><circle cx="80" cy="80" r="25" fill="rgba(255,255,255,0.03)"/></svg>');
            opacity: 0.5;
        }

        .login-header-content {
            position: relative;
            z-index: 1;
        }

        .logo-icon {
            font-size: 4rem;
            margin-bottom: 15px;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }

        .login-header h1 {
            font-size: 2.2rem;
            font-weight: 700;
            margin-bottom: 10px;
        }

        .login-header p {
            font-size: 1rem;
            opacity: 0.95;
            margin-bottom: 5px;
        }

        .login-header .badge {
            display: inline-block;
            background: rgba(255, 255, 255, 0.2);
            color: white;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.85rem;
            margin-top: 10px;
        }

        .login-body {
            padding: 45px 40px;
        }

        .form-group {
            margin-bottom: 22px;
        }

        .form-group label {
            display: block;
            margin-bottom: 10px;
            font-weight: 600;
            color: #1e3c72;
            font-size: 0.95rem;
        }

        .form-group input {
            width: 100%;
            padding: 14px 16px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: #f9f9f9;
        }

        .form-group input:focus {
            outline: none;
            border-color: #1e3c72;
            background: white;
            box-shadow: 0 0 0 4px rgba(30, 60, 114, 0.1);
        }

        .form-group input::placeholder {
            color: #999;
        }

        .error-message {
            background: #fff3cd;
            border: 2px solid #ffc107;
            color: #856404;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 12px;
            font-weight: 500;
        }

        .error-message i {
            font-size: 1.3rem;
            flex-shrink: 0;
        }

        .success-message {
            background: #d4edda;
            border: 2px solid #28a745;
            color: #155724;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 12px;
            font-weight: 500;
        }

        .success-message i {
            font-size: 1.3rem;
            flex-shrink: 0;
        }

        .login-btn {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 1.05rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .login-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 15px 30px rgba(30, 60, 114, 0.3);
        }

        .login-btn:active {
            transform: translateY(0);
        }

        .form-divider {
            display: flex;
            align-items: center;
            margin: 25px 0;
            gap: 10px;
        }

        .form-divider span {
            color: #999;
            font-size: 0.9rem;
        }

        .form-divider::before,
        .form-divider::after {
            content: '';
            flex: 1;
            height: 2px;
            background: #e0e0e0;
        }

        .demo-section {
            background: #f5f5f5;
            padding: 20px;
            border-radius: 10px;
            margin-top: 25px;
        }

        .demo-title {
            font-weight: 600;
            color: #1e3c72;
            margin-bottom: 15px;
            font-size: 0.95rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .demo-btn {
            background: white;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            padding: 12px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: 500;
            color: #333;
        }

        .demo-btn:hover {
            background: #1e3c72;
            color: white;
            border-color: #1e3c72;
        }

        .doctor-benefits {
            background: #f0f4f8;
            padding: 20px;
            border-radius: 10px;
            margin-top: 25px;
        }

        .benefit-item {
            display: flex;
            gap: 12px;
            margin-bottom: 12px;
            font-size: 0.9rem;
            color: #333;
        }

        .benefit-item i {
            color: #1e3c72;
            font-weight: 700;
            flex-shrink: 0;
        }

        .register-link {
            text-align: center;
            margin-top: 25px;
            padding-top: 20px;
            border-top: 2px solid #e0e0e0;
        }

        .register-link p {
            color: #666;
            margin: 0 0 10px 0;
            font-size: 0.95rem;
        }

        .register-link a {
            color: #1e3c72;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .register-link a:hover {
            color: #2a5298;
            text-decoration: underline;
        }

        .other-portals {
            display: flex;
            gap: 10px;
            margin-top: 15px;
            justify-content: center;
        }

        .portal-link {
            padding: 8px 12px;
            background: white;
            border: 2px solid #e0e0e0;
            border-radius: 6px;
            color: #666;
            text-decoration: none;
            font-size: 0.85rem;
            transition: all 0.3s ease;
        }

        .portal-link:hover {
            background: #e0e0e0;
            color: #333;
        }

        .security-note {
            background: #e8f4f8;
            border-left: 4px solid #1e3c72;
            padding: 12px;
            border-radius: 6px;
            margin-top: 20px;
            font-size: 0.85rem;
            color: #333;
            display: flex;
            gap: 10px;
        }

        .security-note i {
            color: #1e3c72;
            flex-shrink: 0;
        }

        @media (max-width: 600px) {
            .login-body {
                padding: 30px 20px;
            }

            .login-header {
                padding: 35px 20px;
            }

            .login-header h1 {
                font-size: 1.8rem;
            }

            .logo-icon {
                font-size: 3rem;
            }
        }
    </style>
</head>
<body>
<div class="login-wrapper">
    <div class="doctor-login-card">
        <div class="login-header">
            <div class="login-header-content">
                <div class="logo-icon">👨‍⚕️</div>
                <h1>Doctor Portal</h1>
                <p>Hospital Management System</p>
                <div class="badge">
                    <i class="bi bi-shield-check"></i> Secure Access
                </div>
            </div>
        </div>

        <div class="login-body">
            <?php if ($error): ?>
                <div class="error-message">
                    <i class="bi bi-exclamation-triangle-fill"></i>
                    <div><?= htmlspecialchars($error) ?></div>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="success-message">
                    <i class="bi bi-check-circle-fill"></i>
                    <div><?= htmlspecialchars($success) ?></div>
                </div>
            <?php endif; ?>

            <form method="POST" onsubmit="return validateDoctorLogin()">
                <div class="form-group">
                    <label for="email">
                        <i class="bi bi-envelope"></i> Email Address
                    </label>
                    <input type="email" id="email" name="email" placeholder="your.email@hospital.com" required>
                </div>

                <div class="form-group">
                    <label for="password">
                        <i class="bi bi-lock"></i> Password
                    </label>
                    <input type="password" id="password" name="password" placeholder="Enter your secure password" required>
                </div>

                <div class="form-group">
                    <label for="license_number">
                        <i class="bi bi-card-text"></i> License Number (Optional)
                    </label>
                    <input type="text" id="license_number" name="license_number" placeholder="Your medical license number">
                </div>

                <button type="submit" class="login-btn">
                    <i class="bi bi-box-arrow-in-right"></i> Sign In to Portal
                </button>
            </form>

            <!-- Demo Section -->
            <div class="demo-section">
                <div class="demo-title">
                    <i class="bi bi-file-text"></i> Demo Credentials for Testing
                </div>
                <button type="button" class="demo-btn w-100" onclick="fillDemoCredentials('<?= $demoDoctor['email'] ?>', '<?= $demoDoctor['password'] ?>')">
                    <i class="bi bi-clipboard-check"></i> Load Demo Doctor Account
                </button>
                <small class="d-block mt-2 text-muted">Email: <?= $demoDoctor['email'] ?> | Password: <?= $demoDoctor['password'] ?></small>
            </div>

            <!-- Doctor Benefits -->
            <div class="doctor-benefits">
                <div style="font-weight: 600; color: #1e3c72; margin-bottom: 12px;">✨ Access Your Doctor Features</div>
                <div class="benefit-item">
                    <i class="bi bi-calendar-check"></i>
                    <span>Manage your appointments and schedule</span>
                </div>
                <div class="benefit-item">
                    <i class="bi bi-chat-dots"></i>
                    <span>Direct messaging with patients</span>
                </div>
                <div class="benefit-item">
                    <i class="bi bi-prescription2"></i>
                    <span>Create and manage prescriptions</span>
                </div>
                <div class="benefit-item">
                    <i class="bi bi-graph-up"></i>
                    <span>View patient medical records</span>
                </div>
                <div class="benefit-item">
                    <i class="bi bi-clock-history"></i>
                    <span>Track your availability</span>
                </div>
            </div>

            <!-- Security Note -->
            <div class="security-note">
                <i class="bi bi-info-circle-fill"></i>
                <span>For security, always ensure you're on the official hospital website before logging in.</span>
            </div>

            <!-- Registration Link -->
            <div class="register-link">
                <p>New doctor joining the hospital?</p>
                <a href="register.php?role=doctor">Create your doctor account</a>
                <div class="other-portals">
                    <a href="login.php?role=patient" class="portal-link">👤 Patient Login</a>
                    <a href="login.php?role=admin" class="portal-link">🔐 Admin Login</a>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function fillDemoCredentials(email, password) {
    document.getElementById('email').value = email;
    document.getElementById('password').value = password;
    document.getElementById('email').focus();
}

function validateDoctorLogin() {
    const email = document.getElementById('email').value.trim();
    const password = document.getElementById('password').value;

    if (!email || !password) {
        alert('Please enter both email and password');
        return false;
    }

    if (!email.includes('@')) {
        alert('Please enter a valid email address');
        return false;
    }

    if (password.length < 6) {
        alert('Password must be at least 6 characters');
        return false;
    }

    return true;
}

// Set focus on page load
window.addEventListener('load', function() {
    document.getElementById('email').focus();
});
</script>
</body>
</html>
