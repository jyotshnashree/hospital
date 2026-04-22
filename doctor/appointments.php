<?php
include '../db.php';
checkAuth();
if (getUserRole() !== 'doctor') {
    header('Location: ../portals.php');
    exit;
}

$filter = $_GET['filter'] ?? 'upcoming';

// Handle appointment actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $aptId = intval($_POST['appointment_id'] ?? 0);
    $pdo->prepare('UPDATE appointments SET status = ? WHERE id = ?')
        ->execute([ucfirst($_POST['action']), $aptId]);
    header('Location: appointments.php?filter=' . $filter);
    exit;
}

// Get appointments
$query = 'SELECT a.*, u.full_name FROM appointments a JOIN users u ON a.patient_id = u.id WHERE 1=1';
if ($filter === 'today') $query .= ' AND DATE(a.appointment_date) = CURDATE()';
elseif ($filter === 'pending') $query .= ' AND a.status = "Pending"';
elseif ($filter === 'completed') $query .= ' AND a.status = "Completed"';
else $query .= ' AND a.appointment_date >= CURDATE()';
$query .= ' ORDER BY a.appointment_date DESC';

$appointments = $pdo->query($query)->fetchAll();
$totalCount = $pdo->query('SELECT COUNT(*) c FROM appointments')->fetch()['c'];
$pendingCount = $pdo->query('SELECT COUNT(*) c FROM appointments WHERE status = "Pending"')->fetch()['c'];
$approvedCount = $pdo->query('SELECT COUNT(*) c FROM appointments WHERE status = "Approved"')->fetch()['c'];
?>

<?php include '../inc/header.php'; ?>

<div style="margin-bottom: 2rem;">
    <h1><i class="bi bi-calendar-event" style="color: #667eea;"></i> Appointments</h1>
    <p class="text-muted">View and manage your appointments</p>
</div>

<div class="row mb-4">
    <div class="col-lg-3"><div class="card shadow p-3"><h6 class="text-muted">Total</h6><h3 style="color: #667eea;"><?= $totalCount ?></h3></div></div>
    <div class="col-lg-3"><div class="card shadow p-3"><h6 class="text-muted">Pending</h6><h3 style="color: #f59e0b;"><?= $pendingCount ?></h3></div></div>
    <div class="col-lg-3"><div class="card shadow p-3"><h6 class="text-muted">Approved</h6><h3 style="color: #10b981;"><?= $approvedCount ?></h3></div></div>
    <div class="col-lg-3"><div class="card shadow p-3"><a href="appointments.php" class="btn btn-primary w-100"><i class="bi bi-arrow-clockwise"></i> Refresh</a></div></div>
</div>

<div class="card shadow p-4">
    <h5 style="margin-bottom: 20px;"><i class="bi bi-list-check"></i> Appointments List</h5>
    <?php if (!empty($appointments)): ?>
        <div class="table-responsive">
            <table class="table table-hover">
                <thead style="background: #f8f9fa;">
                <tr><th>Patient</th><th>Date & Time</th><th>Status</th><th>Actions</th></tr>
                </thead>
                <tbody>
                <?php foreach ($appointments as $apt): ?>
                    <tr>
                        <td><strong><?= sanitize($apt['full_name']) ?></strong></td>
                        <td><?= date('M d, Y h:i A', strtotime($apt['appointment_date'] . ' ' . $apt['appointment_time'])) ?></td>
                        <td><span class="badge bg-<?php if ($apt['status'] === 'Pending') echo 'warning'; elseif ($apt['status'] === 'Approved') echo 'success'; else echo 'info'; ?>"><?= $apt['status'] ?></span></td>
                        <td>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="appointment_id" value="<?= $apt['id'] ?>">
                                <?php if ($apt['status'] === 'Pending'): ?>
                                    <button type="submit" name="action" value="Approved" class="btn btn-sm btn-success"><i class="bi bi-check"></i></button>
                                <?php endif; ?>
                                <?php if ($apt['status'] === 'Approved'): ?>
                                    <button type="submit" name="action" value="Completed" class="btn btn-sm btn-info"><i class="bi bi-check2"></i></button>
                                <?php endif; ?>
                                <button type="submit" name="action" value="Cancelled" class="btn btn-sm btn-danger" onclick="return confirm('Cancel?');"><i class="bi bi-x"></i></button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <p class="text-center text-muted py-5">No appointments found</p>
    <?php endif; ?>
</div>

<?php include '../inc/footer.php'; ?>

