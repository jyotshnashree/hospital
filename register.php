<?php
include 'db.php';
$error = '';
$role = sanitize($_GET['role'] ?? 'patient');
$is_doctor = $role === 'doctor';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Security error. Please try again.';
    } else {
        $full_name = sanitize($_POST['full_name'] ?? '');
        $email = sanitize($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        $age = intval($_POST['age'] ?? 0);
        $gender = sanitize($_POST['gender'] ?? '');
        
        $medical_history = '';
        $specialty = '';
        $license_number = '';
        
        if ($is_doctor) {
            $specialty = sanitize($_POST['specialty'] ?? '');
            $license_number = sanitize($_POST['license_number'] ?? '');
            if (!$full_name || !$email || !$password || !$confirm_password || !$specialty || !$license_number) {
                $error = 'Please complete all required fields.';
            }
        } else {
            $medical_history = sanitize($_POST['medical_history'] ?? '');
            if (!$full_name || !$email || !$password || !$confirm_password || !$age || !$gender) {
                $error = 'Please complete all required fields.';
            }
        }

        if (!$error && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Please enter a valid email address.';
        } elseif (!$error && $password !== $confirm_password) {
            $error = 'Passwords do not match.';
        } elseif (!$error && strlen($password) < 6) {
            $error = 'Password must be at least 6 characters.';
        } else if (!$error) {
            $stmt = $pdo->prepare('SELECT COUNT(*) FROM users WHERE email = ?');
            $stmt->execute([$email]);
            if ($stmt->fetchColumn() > 0) {
                $error = 'Email is already registered.';
            } else {
                $user_role = $is_doctor ? 'doctor' : 'patient';
                $stmt = $pdo->prepare('INSERT INTO users (full_name, email, password, role, age, gender, medical_history, specialty, license_number, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())');
                $stmt->execute([$full_name, $email, password_hash($password, PASSWORD_DEFAULT), $user_role, $age, $gender, $medical_history, $specialty, $license_number]);
                flash('success', 'Registration completed. Please log in.');
                header('Location: login.php');
                exit;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Registration</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
</head>
<body>
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="text-center mb-5">
                <div style="font-size: 4rem; animation: bounce 2s infinite; display: inline-block; margin-bottom: 1rem;"><?= $is_doctor ? '👨‍⚕️' : '📝' ?></div>
                <h2 class="mt-4" style="font-size: 2.5rem;">Create Your Account</h2>
                <p class="text-muted" style="font-weight: 500;"><?= $is_doctor ? '🏥 Register as a doctor' : '👩‍⚕️ Register as a patient to book appointments' ?></p>
            </div>
            <div class="card shadow">
                <div class="card-body">
                    <?php if ($error): ?>
                        <div class="alert alert-danger" role="alert">
                            <strong style="font-size: 1.2rem;">❌ Oops!</strong><br>
                            <?= $error ?>
                        </div>
                    <?php endif; ?>
                    <form method="post" onsubmit="return validateRegistration()">
                        <input type="hidden" name="csrf_token" value="<?= escape(generateCSRFToken()) ?>">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">👤 Full Name</label>
                                <input type="text" name="full_name" id="full_name" class="form-control" placeholder="John Doe" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">📧 Email Address</label>
                                <input type="email" name="email" id="email" class="form-control" placeholder="john@example.com" required>
                            </div>
                            
                            <?php if ($is_doctor): ?>
                            <div class="col-md-6">
                                <label class="form-label">🔬 Specialization</label>
                                <input type="text" name="specialty" id="specialty" class="form-control" placeholder="e.g., Cardiology" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">📜 License Number</label>
                                <input type="text" name="license_number" id="license_number" class="form-control" placeholder="Medical License #" required>
                            </div>
                            <?php else: ?>
                            <div class="col-md-4">
                                <label class="form-label">🎂 Age</label>
                                <input type="number" name="age" id="age" class="form-control" placeholder="25" min="1" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">👥 Gender</label>
                                <select name="gender" id="gender" class="form-select" required>
                                    <option value="">Choose...</option>
                                    <option value="Male">♂️ Male</option>
                                    <option value="Female">♀️ Female</option>
                                    <option value="Other">🤷 Other</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">🔐 Password</label>
                                <input type="password" name="password" id="password" class="form-control" placeholder="Min. 6 characters" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">✔️ Confirm Password</label>
                                <input type="password" name="confirm_password" id="confirm_password" class="form-control" placeholder="Confirm password" required>
                            </div>
                            <div class="col-md-6">
                            </div>
                            <div class="col-12">
                                <label class="form-label">📋 Medical History (Optional)</label>
                                <textarea name="medical_history" id="medical_history" class="form-control" rows="3" placeholder="Any previous medical conditions..."></textarea>
                            </div>
                            <?php endif; ?>
                            
                            <?php if ($is_doctor): ?>
                            <div class="col-md-6">
                                <label class="form-label">🔐 Password</label>
                                <input type="password" name="password" id="password" class="form-control" placeholder="Min. 6 characters" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">✔️ Confirm Password</label>
                                <input type="password" name="confirm_password" id="confirm_password" class="form-control" placeholder="Confirm password" required>
                            </div>
                            <?php endif; ?>
                        </div>
                        <div class="mt-5 d-grid">
                            <button type="submit" class="btn btn-primary btn-lg">✨ Create My Account</button>
                        </div>
                    </form>
                    <hr style="border-color: rgba(102, 126, 234, 0.2); margin-top: 2rem;">
                    <div class="mt-4 text-center">
                        <p class="text-muted" style="margin-bottom: 1rem;">Already have an account?</p>
                        <a href="login.php" class="btn btn-outline-primary btn-lg">🔐 Login Here</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="scripts.js"></script>
</body>
</html>
