<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include '../db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../portals.php');
    exit;
}

$search = $_GET['search'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $doctor_id = $_POST['doctor_id'] ?? null;

    if ($action === 'approve' && $doctor_id) {
        $stmt = $pdo->prepare('UPDATE users SET is_active = 1 WHERE id = ? AND role = "doctor"');
        $stmt->execute([$doctor_id]);
    } elseif ($action === 'deactivate' && $doctor_id) {
        $stmt = $pdo->prepare('UPDATE users SET is_active = 0 WHERE id = ? AND role = "doctor"');
        $stmt->execute([$doctor_id]);
    } elseif ($action === 'allocate_id' && $doctor_id) {
        $stmt = $pdo->prepare('SELECT COUNT(*) as total FROM users WHERE role = "doctor"');
        $stmt->execute();
        $count = $stmt->fetch()['total'];
        $allocated_id = 'DOC-' . date('Y') . '-' . str_pad($count + 1, 4, '0', STR_PAD_LEFT);
        $updateStmt = $pdo->prepare('UPDATE users SET doctor_id_allocated = ? WHERE id = ?');
        $updateStmt->execute([$allocated_id, $doctor_id]);
    }
}

$query = 'SELECT * FROM users WHERE role = "doctor"';
if ($search) {
    $query .= ' AND (full_name LIKE ? OR email LIKE ? OR specialty LIKE ?)';
    $searchTerm = '%' . $search . '%';
    $stmt = $pdo->prepare($query);
    $stmt->execute([$searchTerm, $searchTerm, $searchTerm]);
} else {
    $stmt = $pdo->prepare($query);
    $stmt->execute();
}
$doctors = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Doctors</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        body { background: #f5f7fa; }
        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; }
        .container-custom { max-width: 1200px; margin: 0 auto; padding: 30px 20px; }
        .doctor-card { background: white; padding: 20px; border-radius: 8px; margin-bottom: 15px; border-left: 5px solid #667eea; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .status-active { background: #d1fae5; color: #065f46; }
        .status-inactive { background: #fee2e2; color: #991b1b; }
        .status-badge { display: inline-block; padding: 5px 12px; border-radius: 20px; font-size: 0.85rem; font-weight: 600; }
        .action-button { margin-right: 5px; margin-bottom: 5px; }
        .search-box { margin-bottom: 20px; }
    </style>
</head>
<body>
<div class="header">
    <div style="max-width: 1200px; margin: 0 auto;">
        <h1 style="margin: 0;"><i class="bi bi-person-badge"></i> Manage Doctors</h1>
    </div>
</div>

<div class="container-custom">
    <div class="search-box">
        <input type="text" id="searchBox" class="form-control" placeholder="🔍 Search doctors by name, email, or specialty..." 
               onkeyup="window.location.href='?search=' + document.getElementById('searchBox').value">
    </div>

    <?php if (count($doctors) > 0): ?>
        <?php foreach ($doctors as $doc): ?>
        <div class="doctor-card">
            <div class="row">
                <div class="col-md-8">
                    <h5 style="margin: 0 0 10px 0;">👨‍⚕️ <?= htmlspecialchars($doc['full_name']) ?></h5>
                    <p style="margin: 5px 0; color: #666;">
                        <strong>Specialty:</strong> <?= htmlspecialchars($doc['specialty'] ?? 'Not Specified') ?>
                    </p>
                    <p style="margin: 5px 0; color: #666;">
                        <strong>License:</strong> <?= htmlspecialchars($doc['license_number'] ?? 'N/A') ?>
                    </p>
                    <p style="margin: 5px 0; color: #666;">
                        <strong>Email:</strong> <?= htmlspecialchars($doc['email']) ?>
                    </p>
                    <p style="margin: 5px 0; color: #666;">
                        <strong>Doctor ID:</strong> <?= $doc['doctor_id_allocated'] ? htmlspecialchars($doc['doctor_id_allocated']) : '<em>Not Allocated</em>' ?>
                    </p>
                </div>
                <div class="col-md-4" style="text-align: right;">
                    <span class="status-badge status-<?= $doc['is_active'] ? 'active' : 'inactive' ?>">
                        <?= $doc['is_active'] ? '✓ Active' : '✗ Inactive' ?>
                    </span><br><br>
                    <form method="POST" style="display: inline;">
                        <input type="hidden" name="doctor_id" value="<?= $doc['id'] ?>">
                        <?php if (!$doc['is_active']): ?>
                            <button type="submit" name="action" value="approve" class="btn btn-sm btn-success action-button">
                                <i class="bi bi-check-circle"></i> Approve
                            </button>
                        <?php else: ?>
                            <button type="submit" name="action" value="deactivate" class="btn btn-sm btn-danger action-button">
                                <i class="bi bi-x-circle"></i> Deactivate
                            </button>
                        <?php endif; ?>
                        <?php if (!$doc['doctor_id_allocated']): ?>
                            <button type="submit" name="action" value="allocate_id" class="btn btn-sm btn-primary action-button">
                                <i class="bi bi-plus-circle"></i> Allocate ID
                            </button>
                        <?php endif; ?>
                    </form>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div style="background: white; padding: 40px; border-radius: 8px; text-align: center;">
            <i class="bi bi-inbox" style="font-size: 3rem; color: #ddd; display: block; margin-bottom: 10px;"></i>
            <p style="color: #666;">No doctors found.</p>
        </div>
    <?php endif; ?>

    <div style="margin-top: 20px;">
        <a href="dashboard.php" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Back to Dashboard
        </a>
    </div>
</div>
</body>
</html>

