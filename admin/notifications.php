<?php
/**
 * Email & SMS Notifications Module
 * Hospital Management System - Send notifications to patients and doctors
 */

include '../db.php';
checkAuth();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $recipient_type = $_POST['recipient_type'] ?? 'patient';
    $recipient_id = $_POST['recipient_id'] ?? null;
    $notification_type = $_POST['notification_type'] ?? 'email';
    $message = $_POST['message'] ?? '';
    
    if ($recipient_id && $message) {
        // Get recipient details
        $stmt = $pdo->prepare("SELECT full_name, email, phone FROM users WHERE id = ?");
        $stmt->execute([$recipient_id]);
        $recipient = $stmt->fetch();
        
        if ($recipient) {
            try {
                // Log notification
                $logStmt = $pdo->prepare("
                    INSERT INTO notifications (user_id, type, message, status)
                    VALUES (?, ?, ?, ?)
                ");
                $logStmt->execute([$recipient_id, $notification_type, $message, 'sent']);
                
                // In production: Send actual email/SMS
                // sendEmail($recipient['email'], $message);
                // sendSMS($recipient['phone'], $message);
                
                $_SESSION['flash']['success'] = "Notification sent to {$recipient['full_name']}";
                header('Location: notifications.php');
                exit;
            } catch (Exception $e) {
                $_SESSION['flash']['error'] = "Error: Table might not exist. Run mysql/create_notifications_table.sql";
            }
        }
    }
}

// Get all users for dropdown
$patients = $pdo->query("SELECT id, full_name, email FROM users WHERE role = 'patient' ORDER BY full_name")->fetchAll();
$doctors = $pdo->query("SELECT id, full_name, email FROM users WHERE role = 'doctor' ORDER BY full_name")->fetchAll();

// Get statistics
$stats = [
    'total_patients' => $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'patient'")->fetchColumn() ?? 0,
    'total_appointments' => $pdo->query("SELECT COUNT(*) FROM appointments")->fetchColumn() ?? 0,
    'pending_appointments' => $pdo->query("SELECT COUNT(*) FROM appointments WHERE status = 'Pending'")->fetchColumn() ?? 0,
    'completed_appointments' => $pdo->query("SELECT COUNT(*) FROM appointments WHERE status = 'Completed'")->fetchColumn() ?? 0,
];

// Get notification history (with error handling)
$notifications = [];
try {
    $notifications = $pdo->query("
        SELECT 
            n.id,
            n.type,
            n.message,
            n.status,
            n.created_at,
            u.full_name,
            u.email
        FROM notifications n
        JOIN users u ON n.user_id = u.id
        ORDER BY n.created_at DESC
        LIMIT 50
    ")->fetchAll();
} catch (Exception $e) {
    // Notifications table might not exist
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications - Hospital Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../styles.css">
</head>
<body>
<?php include '../inc/header.php'; ?>

<div class="container py-5">
    <div style="margin-bottom: 2.5rem;">
        <h1 style="font-size: 2.5rem;">📬 Email & SMS Notifications</h1>
        <p class="text-muted">Send notifications to patients and doctors</p>
    </div>

    <!-- Statistics Cards -->
    <div class="row g-3 mb-4">
        <div class="col-md-3 col-sm-6">
            <div class="card shadow text-center p-3" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none;">
                <div style="font-size: 2.5rem;">👥</div>
                <h6 class="mt-2 mb-0">Total Patients</h6>
                <div style="font-size: 1.8rem; font-weight: bold; margin-top: 8px;">
                    <?= number_format($stats['total_patients']) ?>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="card shadow text-center p-3" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: white; border: none;">
                <div style="font-size: 2.5rem;">📅</div>
                <h6 class="mt-2 mb-0">Total Appointments</h6>
                <div style="font-size: 1.8rem; font-weight: bold; margin-top: 8px;">
                    <?= number_format($stats['total_appointments']) ?>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="card shadow text-center p-3" style="background: linear-gradient(135deg, #fa709a 0%, #fee140 100%); color: white; border: none;">
                <div style="font-size: 2.5rem;">⏳</div>
                <h6 class="mt-2 mb-0">Pending</h6>
                <div style="font-size: 1.8rem; font-weight: bold; margin-top: 8px;">
                    <?= number_format($stats['pending_appointments']) ?>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="card shadow text-center p-3" style="background: linear-gradient(135deg, #30cfd0 0%, #330867 100%); color: white; border: none;">
                <div style="font-size: 2.5rem;">✅</div>
                <h6 class="mt-2 mb-0">Completed</h6>
                <div style="font-size: 1.8rem; font-weight: bold; margin-top: 8px;">
                    <?= number_format($stats['completed_appointments']) ?>
                </div>
            </div>
        </div>
    </div>

    <?php if (!empty($_SESSION['flash']['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <?= $_SESSION['flash']['success'] ?>
            <?php unset($_SESSION['flash']['success']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (!empty($_SESSION['flash']['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <?= $_SESSION['flash']['error'] ?>
            <?php unset($_SESSION['flash']['error']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Setup Guide if notifications table doesn't exist -->
    <div class="alert alert-info alert-dismissible fade show mb-4">
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        <strong>📬 First Time Setup:</strong><br>
        If you see an error about "notifications table", run this SQL in phpMyAdmin:
        <div style="background: #f5f5f5; padding: 10px; margin-top: 10px; border-radius: 5px; font-family: monospace; font-size: 12px;">
            <button onclick="copySQL()" class="btn btn-sm btn-secondary float-end">📋 Copy</button>
            <div id="sqlText">
CREATE TABLE IF NOT EXISTS `notifications` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `type` ENUM('email', 'sms', 'both') DEFAULT 'email',
  `message` LONGTEXT NOT NULL,
  `status` ENUM('pending', 'sent', 'failed') DEFAULT 'pending',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY(`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  INDEX `idx_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
            </div>
        </div>
        <small style="display: block; margin-top: 10px;">Or see: <code>mysql/create_notifications_table.sql</code></small>
    </div>

    <div class="row">
        <div class="col-lg-6">
            <div class="card shadow mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">✉️ Send New Notification</h5>
                </div>
                <div class="card-body">
                    <form method="post">
                        <div class="mb-3">
                            <label class="form-label">Recipient Type</label>
                            <select name="recipient_type" class="form-select" id="recipientType" required>
                                <option value="patient">👥 Patient</option>
                                <option value="doctor">👨‍⚕️ Doctor</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Select Recipient</label>
                            <select name="recipient_id" class="form-select" id="recipientSelect" required>
                                <option value="">Choose a recipient...</option>
                                <?php foreach ($patients as $patient): ?>
                                    <option value="<?= $patient['id'] ?>" class="patient-option">
                                        <?= htmlspecialchars($patient['full_name']) ?> (<?= htmlspecialchars($patient['email']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Notification Type</label>
                            <select name="notification_type" class="form-select" required>
                                <option value="email">📧 Email</option>
                                <option value="sms">📱 SMS</option>
                                <option value="both">📬 Both Email & SMS</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Message</label>
                            <textarea name="message" class="form-control" rows="4" placeholder="Enter notification message..." required></textarea>
                            <small class="text-muted">Characters remaining: <span id="charCount">500</span> / 500</small>
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary btn-lg">
                                📤 Send Notification
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card shadow">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">📋 Quick Templates</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <button class="btn btn-outline-primary w-100 mb-2" onclick="useTemplate('Your appointment is scheduled. Please arrive 15 minutes early.')">
                            📅 Appointment Reminder
                        </button>
                        <button class="btn btn-outline-primary w-100 mb-2" onclick="useTemplate('Your appointment has been approved. Please visit on the scheduled date.')">
                            ✅ Appointment Approved
                        </button>
                        <button class="btn btn-outline-primary w-100 mb-2" onclick="useTemplate('Your prescription is ready for pickup at the pharmacy.')">
                            💊 Prescription Ready
                        </button>
                        <button class="btn btn-outline-primary w-100 mb-2" onclick="useTemplate('Your bill is due. Please make payment at the earliest.')">
                            💳 Payment Reminder
                        </button>
                        <button class="btn btn-outline-primary w-100" onclick="useTemplate('Thank you for choosing our hospital. We value your health!')">
                            👋 Thank You Message
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow mt-4">
        <div class="card-header bg-success text-white">
            <h5 class="mb-0">📬 Notification History</h5>
        </div>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Recipient</th>
                        <th>Type</th>
                        <th>Message</th>
                        <th>Status</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($notifications)): ?>
                        <?php foreach ($notifications as $notif): ?>
                            <tr>
                                <td><?= htmlspecialchars($notif['full_name']) ?></td>
                                <td>
                                    <span class="badge bg-info">
                                        <?= $notif['type'] === 'sms' ? '📱' : '📧' ?> <?= ucfirst($notif['type']) ?>
                                    </span>
                                </td>
                                <td><?= htmlspecialchars(substr($notif['message'], 0, 50)) ?>...</td>
                                <td><span class="badge bg-success">✓ <?= ucfirst($notif['status']) ?></span></td>
                                <td><?= date('M d, Y H:i', strtotime($notif['created_at'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">No notifications sent yet</td>
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
// Character counter
document.querySelector('textarea[name="message"]').addEventListener('input', function() {
    let remaining = 500 - this.value.length;
    document.getElementById('charCount').textContent = Math.max(0, remaining);
});

// Recipient type filter
document.getElementById('recipientType').addEventListener('change', function() {
    const type = this.value;
    const select = document.getElementById('recipientSelect');
    const options = select.querySelectorAll('option');
    
    options.forEach(opt => {
        if (opt.classList.contains(type + '-option') || opt.value === '') {
            opt.style.display = 'block';
        } else {
            opt.style.display = 'none';
        }
    });
});

// Template usage
function useTemplate(message) {
    document.querySelector('textarea[name="message"]').value = message;
    document.getElementById('charCount').textContent = 500 - message.length;
    document.querySelector('textarea[name="message"]').focus();
}

// Copy SQL to clipboard
function copySQL() {
    const sqlElement = document.getElementById('sqlText');
    const text = sqlElement.innerText;
    navigator.clipboard.writeText(text).then(() => {
        alert('SQL copied to clipboard!');
    }).catch(err => {
        console.error('Failed to copy:', err);
        alert('Copy failed. Please copy manually.');
    });
}

// Initial doctor-option hiding
<?php if (empty($doctors)): ?>
    document.addEventListener('DOMContentLoaded', function() {
        document.getElementById('recipientType').value = 'patient';
    });
<?php endif; ?>
</script>
</body>
</html>
