<?php
include '../db.php';
checkAuth();
if (getUserRole() !== 'admin') {
    header('Location: ../portals.php');
    exit;
}
$error = '';
$success = '';
$editDoctor = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'delete') {
        $id = intval($_POST['doctor_id'] ?? 0);
        if ($id) {
            $stmt = $pdo->prepare('DELETE FROM users WHERE id = ? AND role = "doctor"');
            $stmt->execute([$id]);
            $success = 'Doctor record deleted successfully.';
        }
    } else {
        $full_name = sanitize($_POST['full_name'] ?? '');
        $email = sanitize($_POST['email'] ?? '');
        $specialty = sanitize($_POST['specialty'] ?? '');
        $phone = sanitize($_POST['phone'] ?? '');
        $password = $_POST['password'] ?? '';
        $id = intval($_POST['doctor_id'] ?? 0);

        if (!$full_name || !$email || !$specialty || !$phone) {
            $error = 'Please fill in all doctor fields.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Email is not valid.';
        } else {
            if ($id) {
                $sql = 'UPDATE users SET full_name = ?, email = ?, specialty = ?, phone = ? WHERE id = ? AND role = "doctor"';
                $params = [$full_name, $email, $specialty, $phone, $id];
                if ($password) {
                    $sql = 'UPDATE users SET full_name = ?, email = ?, specialty = ?, phone = ?, password = ? WHERE id = ? AND role = "doctor"';
                    $params = [$full_name, $email, $specialty, $phone, password_hash($password, PASSWORD_DEFAULT), $id];
                }
                $pdo->prepare($sql)->execute($params);
                $success = 'Doctor updated successfully.';
            } else {
                $stmt = $pdo->prepare('SELECT COUNT(*) FROM users WHERE email = ?');
                $stmt->execute([$email]);
                if ($stmt->fetchColumn() > 0) {
                    $error = 'That email is already registered.';
                } else {
                    $pdo->prepare('INSERT INTO users (full_name, email, password, role, specialty, phone, created_at) VALUES (?, ?, ?, "doctor", ?, ?, NOW())')
                        ->execute([$full_name, $email, password_hash($password ?: 'doctor123', PASSWORD_DEFAULT), $specialty, $phone]);
                    $success = 'Doctor added successfully.';
                }
            }
        }
    }
}

if ($_GET['action'] ?? '' === 'edit') {
    $editId = intval($_GET['id'] ?? 0);
    $stmt = $pdo->prepare('SELECT * FROM users WHERE id = ? AND role = "doctor"');
    $stmt->execute([$editId]);
    $editDoctor = $stmt->fetch();
}

$doctors = $pdo->query('SELECT * FROM users WHERE role = "doctor" ORDER BY id DESC')->fetchAll();
?>
<?php include '../inc/header.php'; ?>
<div style="margin-bottom: 2.5rem; animation: slideInUp 0.6s ease-out;">
    <h1 style="font-size: 3rem; margin-bottom: 0.5rem;">👨‍⚕️ Manage Doctors</h1>
    <p class="text-muted" style="font-weight: 500; font-size: 1.1rem;">✨ Add, edit, or remove doctors</p>
</div>
<?php if ($error): ?>
    <div class="alert alert-danger" role="alert">❌ <?= $error ?></div>
<?php endif; ?>
<?php if ($success): ?>
    <div class="alert alert-success" role="alert">✅ <?= $success ?></div>
<?php endif; ?>
<div class="row">
    <div class="col-lg-5 mb-4">
        <div class="card shadow p-4">
            <h5 style="font-size: 1.5rem; margin-bottom: 2rem;">➕ <?= $editDoctor ? 'Edit Doctor' : 'Add New Doctor' ?></h5>
            <form method="post" onsubmit="return confirm('Save doctor record?')">
                <input type="hidden" name="doctor_id" value="<?= $editDoctor['id'] ?? '' ?>">
                <input type="hidden" name="action" value="save">
                <div class="mb-4">
                    <label class="form-label">👤 Full Name</label>
                    <input type="text" name="full_name" class="form-control" placeholder="Dr. John Doe" value="<?= sanitize($editDoctor['full_name'] ?? '') ?>" required>
                </div>
                <div class="mb-4">
                    <label class="form-label">📧 Email</label>
                    <input type="email" name="email" class="form-control" placeholder="doctor@hospital.com" value="<?= sanitize($editDoctor['email'] ?? '') ?>" required>
                </div>
                <div class="mb-4">
                    <label class="form-label">🏥 Specialty</label>
                    <input type="text" name="specialty" class="form-control" placeholder="e.g., Cardiology" value="<?= sanitize($editDoctor['specialty'] ?? '') ?>" required>
                </div>
                <div class="mb-4">
                    <label class="form-label">📱 Phone</label>
                    <input type="text" name="phone" class="form-control" placeholder="555-1234" value="<?= sanitize($editDoctor['phone'] ?? '') ?>" required>
                </div>
                <div class="mb-4">
                    <label class="form-label">🔐 Password <small class="text-muted">(leave blank to keep)</small></label>
                    <input type="password" name="password" class="form-control" placeholder="Password">
                </div>
                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary btn-lg"><?= $editDoctor ? '✏️ Update Doctor' : '➕ Add Doctor' ?></button>
                    <?php if ($editDoctor): ?>
                        <a href="doctors.php" class="btn btn-outline-secondary">❌ Cancel</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>
    <div class="col-lg-7">
        <div class="card shadow p-4">
            <h5 style="font-size: 1.5rem; margin-bottom: 2rem;">👨‍⚕️ Doctor List (<?= count($doctors) ?> total)</h5>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                    <tr>
                        <th style="font-weight: 700; color: white;">#️⃣ ID</th>
                        <th style="font-weight: 700; color: white;">👤 Name</th>
                        <th style="font-weight: 700; color: white;">📧 Email</th>
                        <th style="font-weight: 700; color: white;">🏥 Specialty</th>
                        <th style="font-weight: 700; color: white;">📱 Phone</th>
                        <th style="font-weight: 700; color: white;">⚙️ Action</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($doctors as $doctor): ?>
                        <tr>
                            <td><strong><?= $doctor['id'] ?></strong></td>
                            <td><?= sanitize($doctor['full_name']) ?></td>
                            <td><?= sanitize($doctor['email']) ?></td>
                            <td><span class="badge badge-primary"><?= sanitize($doctor['specialty']) ?></span></td>
                            <td><?= sanitize($doctor['phone']) ?></td>
                            <td>
                                <a href="doctors.php?action=edit&id=<?= $doctor['id'] ?>" class="btn btn-sm btn-outline-primary" style="font-weight: 700;">✏️ Edit</a>
                                <form method="post" class="d-inline" onsubmit="return confirmDelete('Delete this doctor?')">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="doctor_id" value="<?= $doctor['id'] ?>">
                                    <button class="btn btn-sm btn-outline-danger" style="font-weight: 700;">❌ Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($doctors)): ?>
                        <tr><td colspan="6" class="text-muted text-center py-4"><em>No doctors added yet. 👨‍⚕️</em></td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php include '../inc/footer.php'; ?>
