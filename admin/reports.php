<?php
/**
 * Patient Reports Management
 * Hospital Management System
 */

include '../db.php';
checkAuth();
if (getUserRole() !== 'admin') {
    header('Location: ../portals.php');
    exit;
}

// Ensure patient_reports table exists
try {
    $pdo->query("CREATE TABLE IF NOT EXISTS `patient_reports` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `patient_id` INT NOT NULL,
        `report_type` VARCHAR(100) NOT NULL,
        `content` LONGTEXT NOT NULL,
        `appointment_id` INT,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY(`patient_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
        FOREIGN KEY(`appointment_id`) REFERENCES `appointments`(`id`) ON DELETE SET NULL,
        INDEX `idx_patient_id` (`patient_id`),
        INDEX `idx_created_at` (`created_at`),
        INDEX `idx_appointment_id` (`appointment_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
} catch (Exception $e) {
    // Table might already exist, continue
}

$show_form = false;
$error = '';
$success = '';

// Handle add report
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_report'])) {
    $patient_id = intval($_POST['patient_id'] ?? 0);
    $report_type = sanitize($_POST['report_type'] ?? '');
    $content = sanitize($_POST['content'] ?? '');
    $appointment_id = intval($_POST['appointment_id'] ?? 0) ?: null;
    
    if ($patient_id <= 0 || empty($report_type) || empty($content)) {
        $_SESSION['error'] = '❌ Please fill in all required fields.';
        header('Location: reports.php?show_form=1');
        exit;
    }
    
    // Check if patient exists
    $stmt = $pdo->prepare('SELECT id FROM users WHERE id = ? AND role = "patient"');
    $stmt->execute([$patient_id]);
    if (!$stmt->fetch()) {
        $_SESSION['error'] = '❌ Patient not found.';
        header('Location: reports.php?show_form=1');
        exit;
    }
    
    // Insert report
    $stmt = $pdo->prepare('INSERT INTO patient_reports (patient_id, report_type, content, appointment_id, created_at) VALUES (?, ?, ?, ?, NOW())');
    if ($stmt->execute([$patient_id, $report_type, $content, $appointment_id])) {
        $_SESSION['success'] = '✅ Report added successfully!';
        header('Location: reports.php');
        exit;
    } else {
        $_SESSION['error'] = '❌ Failed to add report.';
        header('Location: reports.php?show_form=1');
        exit;
    }
}

// Handle PDF download
if (isset($_GET['action']) && $_GET['action'] === 'download_report' && isset($_GET['report_id'])) {
    $report_id = $_GET['report_id'];
    
    $stmt = $pdo->prepare("
        SELECT 
            r.id,
            r.report_type,
            r.content,
            r.created_at,
            u.full_name as patient_name,
            u.email,
            u.phone
        FROM patient_reports r
        JOIN users u ON r.patient_id = u.id
        WHERE r.id = ?
    ");
    $stmt->execute([$report_id]);
    $report = $stmt->fetch();
    
    if ($report) {
        header('Content-Type: text/html; charset=utf-8');
        ?><!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .header { text-align: center; margin-bottom: 30px; }
        .logo { font-size: 24px; font-weight: bold; color: #667eea; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th { background: #f0f0f0; padding: 10px; text-align: left; font-weight: bold; }
        td { padding: 10px; border-bottom: 1px solid #ddd; }
        .content { margin: 20px 0; padding: 15px; background: #f9f9f9; border-radius: 8px; white-space: pre-wrap; word-wrap: break-word; line-height: 1.6; font-family: Arial, sans-serif; }
        @media print { button { display: none; } }
    </style>
</head>
<body>
    <button onclick="window.print()" style="padding: 10px 20px;">🖨️ Print</button>
    <button onclick="window.close()" style="padding: 10px 20px;">❌ Close</button>
    
    <div class="header">
        <div class="logo">🏥 Hospital Management System</div>
        <div style="color: #666;">Patient Report</div>
    </div>
    
    <h3>Report Details</h3>
    <table>
        <tr><th>Report ID</th><td><?= htmlspecialchars($report['id']) ?></td></tr>
        <tr><th>Patient</th><td><?= htmlspecialchars($report['patient_name']) ?></td></tr>
        <tr><th>Email</th><td><?= htmlspecialchars($report['email']) ?></td></tr>
        <tr><th>Phone</th><td><?= htmlspecialchars($report['phone'] ?? 'N/A') ?></td></tr>
        <tr><th>Report Type</th><td><?= htmlspecialchars($report['report_type']) ?></td></tr>
        <tr><th>Date</th><td><?= date('M d, Y', strtotime($report['created_at'])) ?></td></tr>
    </table>
    
    <h3>Report Content</h3>
    <div class="content">
        <?php 
            // Convert literal \n strings to actual newlines
            $content = str_replace('\\n', "\n", $report['content']);
            echo nl2br(htmlspecialchars($content));
        ?>
    </div>
    
    <p style="margin-top: 40px; color: #999; font-size: 12px;">Generated on <?= date('M d, Y H:i') ?></p>
</body>
</html><?php
        exit;
    }
}

if (isset($_GET['show_form'])) $show_form = true;

// Get patient reports only
$reports = $pdo->query('SELECT r.*, u.full_name AS patient_name FROM patient_reports r JOIN users u ON r.patient_id = u.id WHERE u.role = "patient" ORDER BY r.created_at DESC')->fetchAll();

// Get patients for dropdown
$patients = $pdo->query('SELECT id, full_name FROM users WHERE role = "patient" ORDER BY full_name ASC')->fetchAll();
?>
<?php include '../inc/header.php'; ?>
<div style="margin-bottom: 2.5rem; animation: slideInUp 0.6s ease-out;">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h1 style="font-size: 3rem; margin-bottom: 0.5rem;">
                <i class="bi bi-file-earmark-text-fill" style="color: #667eea;"></i> Patient Reports
            </h1>
            <p class="text-muted" style="font-weight: 500; font-size: 1.1rem;">✨ Manage patient medical reports</p>
        </div>
        <?php if (!$show_form): ?>
            <a href="?show_form=1" class="btn btn-success btn-lg">
                <i class="bi bi-plus-circle-fill"></i> Add New Report
            </a>
        <?php endif; ?>
    </div>
</div>

<?php if (isset($_SESSION['error'])): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?= $_SESSION['error'] ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php unset($_SESSION['error']); ?>
<?php endif; ?>

<?php if (isset($_SESSION['success'])): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?= $_SESSION['success'] ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php unset($_SESSION['success']); ?>
<?php endif; ?>

<?php if ($show_form): ?>
    <div class="card shadow p-5 mb-4" style="background: white; border-radius: 15px;">
        <h3 style="margin-bottom: 30px; color: #667eea; font-weight: 700;">
            <i class="bi bi-file-earmark-plus-fill"></i> Add New Patient Report
        </h3>
        
        <form method="POST" style="max-width: 800px;">
            <div class="mb-3">
                <label style="font-weight: 600; color: #333; margin-bottom: 10px; display: block;">Select Patient *</label>
                <select name="patient_id" style="width: 100%; padding: 12px 15px; border: 2px solid #ddd; border-radius: 8px; font-size: 1rem; color: #333; background: white; cursor: pointer;" required>
                    <option value="">-- Choose Patient --</option>
                    <?php foreach ($patients as $patient): ?>
                        <option value="<?= $patient['id'] ?>"><?= sanitize($patient['full_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label style="font-weight: 600; color: #333; margin-bottom: 10px; display: block;">Report Type *</label>
                    <select name="report_type" style="width: 100%; padding: 12px 15px; border: 2px solid #ddd; border-radius: 8px; font-size: 1rem; color: #333; background: white; cursor: pointer;" required>
                        <option value="">-- Select Type --</option>
                        <option value="Medical Assessment">Medical Assessment</option>
                        <option value="Lab Results">Lab Results</option>
                        <option value="Diagnosis">Diagnosis</option>
                        <option value="Treatment Plan">Treatment Plan</option>
                        <option value="Discharge Summary">Discharge Summary</option>
                        <option value="Prescription Report">Prescription Report</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
                <div class="col-md-6 mb-3">
                    <label style="font-weight: 600; color: #333; margin-bottom: 10px; display: block;">Appointment (Optional)</label>
                    <input type="number" name="appointment_id" style="width: 100%; padding: 12px 15px; border: 2px solid #ddd; border-radius: 8px; font-size: 1rem; color: #333; background: white;" placeholder="Appointment ID">
                </div>
            </div>

            <div class="mb-4">
                <label style="font-weight: 600; color: #333; margin-bottom: 10px; display: block;">Report Content *</label>
                <textarea name="content" style="width: 100%; padding: 12px 15px; border: 2px solid #ddd; border-radius: 8px; font-size: 1rem; color: #333; background: white; font-family: Arial, sans-serif; resize: vertical;" rows="6" placeholder="Enter complete report content..." required></textarea>
            </div>

            <div style="display: flex; gap: 10px;">
                <button type="submit" name="add_report" value="1" class="btn btn-success btn-lg" style="flex: 1;">
                    <i class="bi bi-check-circle-fill"></i> Add Report
                </button>
                <a href="reports.php" class="btn btn-secondary btn-lg" style="flex: 1; text-decoration: none;">
                    <i class="bi bi-x-circle-fill"></i> Cancel
                </a>
            </div>
        </form>
    </div>
<?php endif; ?>

<div class="card shadow p-4">
    <h5 style="font-size: 1.5rem; margin-bottom: 20px;"><i class="bi bi-file-earmark-text-fill"></i> All Patient Reports</h5>
    <div class="table-responsive">
        <table class="table table-sm table-hover">
            <thead>
            <tr>
                <th style="font-weight: 700;">#</th>
                <th style="font-weight: 700;">Patient</th>
                <th style="font-weight: 700;">Report Type</th>
                <th style="font-weight: 700;">Date</th>
                <th style="font-weight: 700;">Action</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($reports as $report): ?>
                <tr>
                    <td><strong><?= $report['id'] ?></strong></td>
                    <td><?= sanitize($report['patient_name']) ?></td>
                    <td><span class="badge" style="background: #667eea; color: white;"><?= sanitize($report['report_type']) ?></span></td>
                    <td><?= date('M d, Y', strtotime($report['created_at'])) ?></td>
                    <td>
                        <a href="?action=download_report&report_id=<?= $report['id'] ?>" class="btn btn-sm btn-primary" target="_blank">
                            <i class="bi bi-download"></i> View
                        </a>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($reports)): ?>
                <tr>
                    <td colspan="5" class="text-center py-5">
                        <div style="padding: 20px;">
                            <i class="bi bi-file-earmark-text" style="font-size: 3rem; color: #ddd;"></i>
                            <p style="color: #999; margin-top: 15px; margin-bottom: 15px;">No reports available yet.</p>
                            <a href="generate_sample_reports.php" class="btn btn-primary btn-sm">
                                <i class="bi bi-plus-circle-fill"></i> Generate Sample Reports
                            </a>
                        </div>
                    </td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include '../inc/footer.php'; ?>
