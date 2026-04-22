<?php
include '../db.php';
checkAuth();
if (getUserRole() !== 'admin') {
    header('Location: ../portals.php');
    exit;
}

$show_form = false;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_patient'])) {
    // Validate CSRF token
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $_SESSION['error'] = '❌ Security error. Please try again.';
        header('Location: patients.php?show_form=1');
        exit;
    }
    
    $full_name = sanitize($_POST['full_name'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $phone = sanitize($_POST['phone'] ?? '');
    $age = (!empty($_POST['age']) ? (int)$_POST['age'] : NULL);
    $gender = sanitize($_POST['gender'] ?? '');
    $dob = sanitize($_POST['dob'] ?? '');
    $address = sanitize($_POST['address'] ?? '');
    $medical_history = sanitize($_POST['medical_history'] ?? '');
    $password = trim($_POST['password'] ?? 'patient123');

    // Validation
    if (!$full_name || !$email) {
        $_SESSION['error'] = '❌ Please fill in Full Name and Email (required fields).';
        $show_form = true;
    } else if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['error'] = '❌ Please enter a valid email address.';
        $show_form = true;
    } else {
        try {
            // Check if email already exists
            $check = $pdo->prepare('SELECT id FROM users WHERE email = ? AND role = ?');
            $check->execute([$email, 'patient']);
            if ($check->fetch()) {
                $_SESSION['error'] = '❌ This email is already registered!';
                $show_form = true;
            } else {
                // Hash password
                $password_hash = password_hash($password, PASSWORD_BCRYPT);

                // Insert patient
                $stmt = $pdo->prepare('
                    INSERT INTO users (full_name, email, phone, role, age, gender, dob, address, medical_history, password, is_active)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ');
                $stmt->execute([$full_name, $email, $phone, 'patient', $age, $gender, $dob, $address, $medical_history, $password_hash, true]);
                
                $_SESSION['success'] = '✅ Patient added successfully!';
                header('Location: patients.php');
                exit;
            }
        } catch (Exception $e) {
            error_log('Patient add error: ' . $e->getMessage());
            $_SESSION['error'] = '❌ Error adding patient. Please try again.';
            $show_form = true;
        }
    }
    
    // Redirect to show error
    if (isset($_SESSION['error'])) {
        header('Location: patients.php?show_form=1');
        exit;
    }
}

if (isset($_GET['show_form'])) {
    $show_form = true;
}

// Handle delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    // Validate CSRF token
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $_SESSION['error'] = '❌ Security error. Please try again.';
        header('Location: patients.php');
        exit;
    }
    
    $delete_id = (int)$_POST['delete_id'];
    
    if ($delete_id > 0) {
        try {
            $stmt = $pdo->prepare('DELETE FROM users WHERE id = ? AND role = ?');
            $result = $stmt->execute([$delete_id, 'patient']);
            
            if ($stmt->rowCount() > 0) {
                $_SESSION['success'] = '✅ Patient deleted successfully!';
            } else {
                $_SESSION['error'] = '❌ Patient not found or could not be deleted.';
            }
        } catch (Exception $e) {
            error_log('Patient delete error: ' . $e->getMessage());
            $_SESSION['error'] = '❌ Error deleting patient. Please try again.';
        }
    } else {
        $_SESSION['error'] = '❌ Invalid patient ID.';
    }
    
    header('Location: patients.php');
    exit;
}

$patients = $pdo->query('SELECT * FROM users WHERE role = "patient" ORDER BY id DESC')->fetchAll();
?>
<?php include '../inc/header.php'; ?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 style="font-size: 3rem; margin-bottom: 0.5rem;">
            <i class="bi bi-people-fill" style="color: #667eea;"></i> Manage Patients
        </h1>
        <p class="text-muted" style="font-weight: 500; font-size: 1.1rem;">✨ View all registered patients</p>
    </div>
    <?php if (!$show_form): ?>
        <a href="?show_form=1" class="btn btn-success btn-lg">
            <i class="bi bi-person-plus-fill"></i> Add New Patient
        </a>
    <?php endif; ?>
</div>

<?php if ($show_form): ?>
    <div class="card shadow p-5 mb-4" style="background: white; border-radius: 15px;">
        <h3 style="margin-bottom: 30px; color: #28a745; font-weight: 700;">
            <i class="bi bi-person-check-fill"></i> Add New Patient
        </h3>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert" style="margin-bottom: 20px;">
                <?= $_SESSION['error'] ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>
        
        <form method="POST" style="max-width: 700px;">
            <input type="hidden" name="csrf_token" value="<?= escape(generateCSRFToken()) ?>">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label style="font-weight: 600; color: #333; margin-bottom: 10px; display: block;">Full Name *</label>
                    <input type="text" name="full_name" style="width: 100%; padding: 12px 15px; border: 2px solid #ddd; border-radius: 8px; font-size: 1rem; color: #333; background: white;" placeholder="Enter full name" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label style="font-weight: 600; color: #333; margin-bottom: 10px; display: block;">Email *</label>
                    <input type="email" name="email" style="width: 100%; padding: 12px 15px; border: 2px solid #ddd; border-radius: 8px; font-size: 1rem; color: #333; background: white;" placeholder="Enter email" required>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label style="font-weight: 600; color: #333; margin-bottom: 10px; display: block;">Phone</label>
                    <input type="tel" name="phone" style="width: 100%; padding: 12px 15px; border: 2px solid #ddd; border-radius: 8px; font-size: 1rem; color: #333; background: white;" placeholder="Enter phone number">
                </div>
                <div class="col-md-6 mb-3">
                    <label style="font-weight: 600; color: #333; margin-bottom: 10px; display: block;">Age</label>
                    <input type="number" name="age" style="width: 100%; padding: 12px 15px; border: 2px solid #ddd; border-radius: 8px; font-size: 1rem; color: #333; background: white;" placeholder="Enter age">
                </div>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label style="font-weight: 600; color: #333; margin-bottom: 10px; display: block;">Gender</label>
                    <select name="gender" style="width: 100%; padding: 12px 15px; border: 2px solid #ddd; border-radius: 8px; font-size: 1rem; color: #333; background: white; cursor: pointer;">
                        <option value="">-- Select Gender --</option>
                        <option value="Male">Male</option>
                        <option value="Female">Female</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
                <div class="col-md-6 mb-3">
                    <label style="font-weight: 600; color: #333; margin-bottom: 10px; display: block;">Date of Birth</label>
                    <input type="date" name="dob" style="width: 100%; padding: 12px 15px; border: 2px solid #ddd; border-radius: 8px; font-size: 1rem; color: #333; background: white;">
                </div>
            </div>

            <div class="mb-3">
                <label style="font-weight: 600; color: #333; margin-bottom: 10px; display: block;">Address</label>
                <textarea name="address" style="width: 100%; padding: 12px 15px; border: 2px solid #ddd; border-radius: 8px; font-size: 1rem; color: #333; background: white; font-family: Arial, sans-serif; resize: vertical;" rows="2" placeholder="Enter address..."></textarea>
            </div>

            <div class="mb-3">
                <label style="font-weight: 600; color: #333; margin-bottom: 10px; display: block;">Medical History</label>
                <textarea name="medical_history" style="width: 100%; padding: 12px 15px; border: 2px solid #ddd; border-radius: 8px; font-size: 1rem; color: #333; background: white; font-family: Arial, sans-serif; resize: vertical;" rows="2" placeholder="Enter medical history..."></textarea>
            </div>

            <div class="mb-4">
                <label style="font-weight: 600; color: #333; margin-bottom: 10px; display: block;">Password</label>
                <input type="text" name="password" style="width: 100%; padding: 12px 15px; border: 2px solid #ddd; border-radius: 8px; font-size: 1rem; color: #333; background: white;" placeholder="Default: patient123" value="patient123">
            </div>

            <div style="display: flex; gap: 10px;">
                <button type="submit" name="add_patient" value="1" class="btn btn-success btn-lg" style="flex: 1;">
                    <i class="bi bi-check-circle-fill"></i> Add Patient
                </button>
                <a href="patients.php" class="btn btn-secondary btn-lg" style="flex: 1; text-decoration: none;">
                    <i class="bi bi-x-circle-fill"></i> Cancel
                </a>
            </div>
        </form>
    </div>
<?php endif; ?>

<?php if (isset($_SESSION['success'])): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?= $_SESSION['success']; unset($_SESSION['success']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if (isset($_SESSION['error'])): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?= $_SESSION['error']; unset($_SESSION['error']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>
<div class="card shadow p-4">
    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead>
            <tr>
                <th style="font-weight: 700; color: white;">#️⃣ ID</th>
                <th style="font-weight: 700; color: white;">👤 Name</th>
                <th style="font-weight: 700; color: white;">📧 Email</th>
                <th style="font-weight: 700; color: white;">📱 Phone</th>
                <th style="font-weight: 700; color: white;">🎂 Age</th>
                <th style="font-weight: 700; color: white;">👥 Gender</th>
                <th style="font-weight: 700; color: white;">📋 Medical History</th>
                <th style="font-weight: 700; color: white;">⚙️ Actions</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($patients as $patient): ?>
                <tr>
                    <td><strong><?= $patient['id'] ?></strong></td>
                    <td><?= sanitize($patient['full_name']) ?></td>
                    <td><?= sanitize($patient['email']) ?></td>
                    <td><?= sanitize($patient['phone'] ?? '-') ?></td>
                    <td><?= sanitize($patient['age'] ?? '-') ?></td>
                    <td><?= sanitize($patient['gender'] ?? '-') ?></td>
                    <td><small style="color: #666;"><?= nl2br(sanitize($patient['medical_history'] ?? 'None')) ?></small></td>
                    <td>
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="csrf_token" value="<?= escape(generateCSRFToken()) ?>">
                            <input type="hidden" name="delete_id" value="<?= $patient['id'] ?>">
                            <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Delete this patient? This action cannot be undone.');">
                                <i class="bi bi-trash-fill"></i> Delete
                            </button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($patients)): ?>
                <tr><td colspan="8" class="text-muted text-center py-4">
                    <i class="fas fa-users fa-2x mb-2" style="color: #ddd;"></i><br>
                    <em>No registered patients yet. Click "Add New Patient" to get started! 📭</em>
                </td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<style>
/* Ensure modal is fully visible and interactive */
.modal-backdrop {
    z-index: 1040 !important;
}
.modal {
    z-index: 1050 !important;
}
.modal-content {
    z-index: 1060 !important;
}
.form-control, .form-select {
    background-color: white !important;
    color: #333 !important;
    border: 2px solid #e0e0e0 !important;
    padding: 10px 12px !important;
    font-size: 1rem !important;
    transition: all 0.3s ease !important;
}
.form-control:focus, .form-select:focus {
    background-color: white !important;
    color: #333 !important;
    border-color: #28a745 !important;
    box-shadow: 0 0 0 0.2rem rgba(40, 167, 69, 0.25) !important;
    outline: none !important;
}
.form-control::placeholder {
    color: #999 !important;
    opacity: 1 !important;
}
</style>

<?php include '../inc/footer.php'; ?>
