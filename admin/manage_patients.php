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
    $patient_id = $_POST['patient_id'] ?? null;

    if ($action === 'activate' && $patient_id) {
        $stmt = $pdo->prepare('UPDATE users SET is_active = 1 WHERE id = ? AND role = "patient"');
        $stmt->execute([$patient_id]);
    } elseif ($action === 'deactivate' && $patient_id) {
        $stmt = $pdo->prepare('UPDATE users SET is_active = 0 WHERE id = ? AND role = "patient"');
        $stmt->execute([$patient_id]);
    }
}

$query = 'SELECT * FROM users WHERE role = "patient"';
if ($search) {
    $query .= ' AND (full_name LIKE ? OR email LIKE ?)';
    $searchTerm = '%' . $search . '%';
    $stmt = $pdo->prepare($query);
    $stmt->execute([$searchTerm, $searchTerm]);
} else {
    $stmt = $pdo->prepare($query);
    $stmt->execute();
}
$patients = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Patients</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        body { background: #f5f7fa; }
        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; }
        .container-custom { max-width: 1200px; margin: 0 auto; padding: 30px 20px; }
        .patient-card { background: white; padding: 20px; border-radius: 8px; margin-bottom: 15px; border-left: 5px solid #667eea; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .status-active { background: #d1fae5; color: #065f46; }
        .status-inactive { background: #fee2e2; color: #991b1b; }
        .status-badge { display: inline-block; padding: 5px 12px; border-radius: 20px; font-size: 0.85rem; font-weight: 600; }
        .search-box { margin-bottom: 20px; }
    </style>
</head>
<body>
<div class="header">
    <div style="max-width: 1200px; margin: 0 auto;">
        <h1 style="margin: 0;"><i class="bi bi-people"></i> Manage Patients</h1>
    </div>
</div>

<div class="container-custom">
    <div class="search-box">
        <input type="text" id="searchBox" class="form-control" placeholder="🔍 Search patients by name or email..." 
               onkeyup="window.location.href='?search=' + document.getElementById('searchBox').value">
    </div>

    <?php if (count($patients) > 0): ?>
        <div style="background: white; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.05);">
            <table class="table" style="margin: 0;">
                <thead style="background: #f5f7fa;">
                    <tr>
                        <th><i class="bi bi-person"></i> Name</th>
                        <th><i class="bi bi-envelope"></i> Email</th>
                        <th><i class="bi bi-telephone"></i> Phone</th>
                        <th style="text-align: center;">Status</th>
                        <th style="text-align: center;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($patients as $patient): ?>
                    <tr>
                        <td><?= htmlspecialchars($patient['full_name']) ?></td>
                        <td><?= htmlspecialchars($patient['email']) ?></td>
                        <td><?= htmlspecialchars($patient['phone'] ?? 'N/A') ?></td>
                        <td style="text-align: center;">
                            <span class="status-badge status-<?= $patient['is_active'] ? 'active' : 'inactive' ?>">
                                <?= $patient['is_active'] ? '✓ Active' : '✗ Inactive' ?>
                            </span>
                        </td>
                        <td style="text-align: center;">
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="patient_id" value="<?= $patient['id'] ?>">
                                <?php if ($patient['is_active']): ?>
                                    <button type="submit" name="action" value="deactivate" class="btn btn-sm btn-danger">
                                        <i class="bi bi-x-circle"></i> Deactivate
                                    </button>
                                <?php else: ?>
                                    <button type="submit" name="action" value="activate" class="btn btn-sm btn-success">
                                        <i class="bi bi-check-circle"></i> Activate
                                    </button>
                                <?php endif; ?>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div style="background: white; padding: 40px; border-radius: 8px; text-align: center;">
            <i class="bi bi-inbox" style="font-size: 3rem; color: #ddd; display: block; margin-bottom: 10px;"></i>
            <p style="color: #666;">No patients found.</p>
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