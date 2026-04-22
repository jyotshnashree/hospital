<?php
include 'db.php';

// Handle role-based routing
$role = isset($_GET['role']) ? strtolower(trim($_GET['role'])) : '';

// Redirect doctor logins to dedicated doctor login page
if ($role === 'doctor') {
    header('Location: doctor_login.php');
    exit;
}

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    switch ($_SESSION['role']) {
        case 'admin':
            header('Location: admin/dashboard.php');
            exit;
        case 'doctor':
            header('Location: doctor/dashboard.php');
            exit;
        case 'patient':
            header('Location: patient/dashboard.php');
            exit;
        default:
            header('Location: dashboard.php');
            exit;
    }
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Security error. Please try again.';
    } else {
        $email = sanitize($_POST['email'] ?? '');
        $password = trim($_POST['password'] ?? '');
        $roleCheck = isset($_POST['role']) ? strtolower(trim($_POST['role'])) : 'patient';

        if (!$email || !$password) {
            $error = 'Please enter both email and password.';
        } else {
            // For admin login, check role explicitly
            if ($roleCheck === 'admin') {
                $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ? AND role = ? LIMIT 1');
                $stmt->execute([$email, 'admin']);
            } else {
                // For patient login, match any non-admin, non-doctor user
                $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ? AND role = ? LIMIT 1');
                $stmt->execute([$email, 'patient']);
            }
            
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['name'] = $user['full_name'];
                
                // Redirect based on role
                if ($user['role'] === 'admin') {
                    header('Location: admin/dashboard.php');
                } elseif ($user['role'] === 'doctor') {
                    header('Location: doctor/dashboard.php');
                } else {
                    header('Location: patient/dashboard.php');
                }
                exit;
            }

            $error = 'Invalid email or password.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= ($role === 'admin') ? 'Admin Login' : 'Patient Login' ?> - Hospital Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="styles.css">
</head>
<body>
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="text-center mb-5">
                <div style="font-size: 4rem; animation: bounce 2s infinite; display: inline-block; margin-bottom: 1rem;">🔐</div>
                <h2 class="mt-4" style="font-size: 2.5rem;">
                    <?php if ($role === 'admin'): ?>
                        Admin Login
                    <?php else: ?>
                        Patient Login
                    <?php endif; ?>
                </h2>
                <p class="text-muted" style="font-weight: 500;">Access your hospital account</p>
            </div>

            <!-- Role Selector Tabs -->
            <div class="btn-group w-100 mb-4" role="group" style="box-shadow: 0 4px 15px rgba(0,0,0,0.1);">
                <a href="login.php?role=patient" class="btn btn-outline-primary <?= ($role !== 'admin') ? 'active' : '' ?> flex-grow-1">
                    <i class="bi bi-person-heart"></i> Patient Login
                </a>
                <a href="login.php?role=admin" class="btn btn-outline-danger <?= ($role === 'admin') ? 'active' : '' ?> flex-grow-1">
                    <i class="bi bi-shield-lock"></i> Admin Login
                </a>
                <a href="doctor_login.php" class="btn btn-outline-info flex-grow-1">
                    <i class="bi bi-stethoscope"></i> Doctor Login
                </a>
            </div>

            <div class="card shadow">
                <div class="card-body">
                    <?php if ($error): ?>
                        <div class="alert alert-danger" role="alert">
                            <strong style="font-size: 1.2rem;">❌ Oops!</strong><br>
                            <?= $error ?>
                        </div>
                    <?php endif; ?>
                    <form method="post" onsubmit="return validateLogin()">
                        <input type="hidden" name="csrf_token" value="<?= escape(generateCSRFToken()) ?>">
                        <input type="hidden" name="role" value="<?= ($role === 'admin') ? 'admin' : 'patient' ?>">
                        <div class="mb-4">
                            <label class="form-label">📧 Email Address</label>
                            <input type="email" name="email" id="email" class="form-control" placeholder="your@email.com" required>
                        </div>
                        <div class="mb-4">
                            <label class="form-label">🔑 Password</label>
                            <input type="password" name="password" id="password" class="form-control" placeholder="Enter your password" required>
                        </div>
                        <div class="d-grid mb-3">
                            <button type="submit" class="btn btn-primary btn-lg">
                                🚀 
                                <?php if ($role === 'admin'): ?>
                                    Login as Admin
                                <?php else: ?>
                                    Login to Account
                                <?php endif; ?>
                            </button>
                        </div>
                    </form>
                    <hr style="border-color: rgba(102, 126, 234, 0.2);">
                    <div class="mt-4 text-center">
                        <p class="text-muted" style="margin-bottom: 1rem;">Don't have an account yet?</p>
                        <a href="register.php" class="btn btn-outline-primary btn-lg">📝 Create New Account</a>
                    </div>
                    <div class="mt-4 p-3" style="background: linear-gradient(135deg, rgba(132, 250, 176, 0.1), rgba(143, 211, 244, 0.1)); border-radius: 20px;">
                        <p style="font-size: 0.95rem; margin: 0; color: #2d3748;">
                            <strong>💡 Demo Accounts:</strong><br>
                            <?php if ($role === 'admin'): ?>
                                <strong>Admin:</strong><br>
                                📧 admin@hospital.com<br>
                                🔐 admin123
                            <?php else: ?>
                                <strong>Patient:</strong><br>
                                📧 patient@hospital.com<br>
                                🔐 patient123
                            <?php endif; ?>
                        </p>
                    </div>
                </div>
            </div>

            <div class="mt-4 text-center">
                <p class="text-muted">
                    <a href="portals.php" class="text-decoration-none">← Back to Portal Selection</a>
                </p>
            </div>
        </div>
    </div>
</div>
<script src="scripts.js"></script>
</body>
</html>
