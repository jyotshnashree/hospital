<?php
session_start();
include '../db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'patient') {
    header('Location: ../login.php?role=patient');
    exit;
}

$patientId = $_SESSION['user_id'];
$filter = $_GET['filter'] ?? 'all';

// Get bills for this patient
$query = 'SELECT b.*, u.full_name as doctor_name FROM bills b 
          JOIN users u ON b.patient_id = u.id 
          WHERE b.patient_id = ? AND u.role = "patient"';
$params = [$patientId];

if ($filter === 'pending') {
    $query .= ' AND b.status = ?';
    $params[] = 'pending';
} elseif ($filter === 'paid') {
    $query .= ' AND b.status = ?';
    $params[] = 'paid';
}

$query .= ' ORDER BY b.created_at DESC';
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$bills = $stmt->fetchAll();

// Calculate totals
$totalPending = 0;
$pendingCount = 0;
$paidCount = 0;

foreach ($bills as $bill) {
    if ($bill['status'] === 'pending') {
        $totalPending += $bill['amount'];
        $pendingCount++;
    } else {
        $paidCount++;
    }
}

// Get payment history
$paymentStmt = $pdo->prepare('
    SELECT p.*, b.description, b.amount 
    FROM payments p 
    JOIN bills b ON p.bill_id = b.id 
    WHERE b.patient_id = ? 
    ORDER BY p.paid_at DESC 
    LIMIT 5
');
$paymentStmt->execute([$patientId]);
$recentPayments = $paymentStmt->fetchAll();
?>
<?php include '../inc/header.php'; ?>
<div style="margin-bottom: 2.5rem; animation: slideInUp 0.6s ease-out;">
    <div>
        <h1 style="font-size: 3rem; margin-bottom: 0.5rem;">
            <i class="bi bi-receipt" style="color: #667eea;"></i> My Bills
        </h1>
        <p class="text-muted" style="font-weight: 500; font-size: 1.1rem;">📋 View and pay your medical bills</p>
    </div>
</div>

<?php if (isset($_SESSION['success'])): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?= $_SESSION['success'] ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php unset($_SESSION['success']); ?>
<?php endif; ?>

<?php if (isset($_SESSION['error'])): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?= $_SESSION['error'] ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php unset($_SESSION['error']); ?>
<?php endif; ?>

<!-- Statistics Cards -->
<div class="row g-4 mb-4">
    <div class="col-md-6 col-lg-3">
        <div class="card shadow p-4" style="border-left: 4px solid #ffc107;">
            <h6 style="color: #666; margin-bottom: 10px; font-weight: 600;">Total Pending</h6>
            <div style="font-size: 2rem; font-weight: 800; color: #ffc107;">
                $<?= number_format($totalPending, 2) ?>
            </div>
            <p style="color: #999; margin: 0; font-size: 0.9rem;"><?= $pendingCount ?> bill<?= $pendingCount !== 1 ? 's' : '' ?></p>
        </div>
    </div>
    <div class="col-md-6 col-lg-3">
        <div class="card shadow p-4" style="border-left: 4px solid #10b981;">
            <h6 style="color: #666; margin-bottom: 10px; font-weight: 600;">Paid Bills</h6>
            <div style="font-size: 2rem; font-weight: 800; color: #10b981;">
                <?= $paidCount ?>
            </div>
            <p style="color: #999; margin: 0; font-size: 0.9rem;">completed</p>
        </div>
    </div>
</div>

<div class="row">
    <!-- Bills Section -->
    <div class="col-lg-8">
        <div class="card shadow p-4 mb-4">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h5 style="font-size: 1.5rem; margin: 0;"><i class="bi bi-file-earmark-text"></i> Your Bills</h5>
            </div>

            <!-- Filter Buttons -->
            <div style="display: flex; gap: 8px; margin-bottom: 20px;">
                <a href="?filter=all" class="btn <?= $filter === 'all' ? 'btn-primary' : 'btn-outline-primary' ?>" style="font-weight: 600;">
                    All (<?= $pendingCount + $paidCount ?>)
                </a>
                <a href="?filter=pending" class="btn <?= $filter === 'pending' ? 'btn-warning' : 'btn-outline-warning' ?>" style="font-weight: 600;">
                    Pending (<?= $pendingCount ?>)
                </a>
                <a href="?filter=paid" class="btn <?= $filter === 'paid' ? 'btn-success' : 'btn-outline-success' ?>" style="font-weight: 600;">
                    Paid (<?= $paidCount ?>)
                </a>
            </div>

            <!-- Bills List -->
            <div>
                <?php if (!empty($bills)): ?>
                    <?php foreach ($bills as $bill): ?>
                        <div class="card p-4 mb-3" style="border-left: 4px solid <?= $bill['status'] === 'pending' ? '#ffc107' : '#10b981' ?>;">
                            <div class="row">
                                <div class="col-md-8">
                                    <h6 style="margin-bottom: 12px; font-weight: 700;">
                                        <i class="bi bi-cash-coin"></i> Invoice #<?= $bill['id'] ?>
                                    </h6>
                                    <p style="margin: 8px 0; color: #666;">
                                        <strong>Description:</strong> <?= sanitize($bill['description']) ?>
                                    </p>
                                    <small style="color: #999;">📅 <?= date('M d, Y', strtotime($bill['created_at'])) ?></small>
                                </div>
                                <div class="col-md-4" style="text-align: right;">
                                    <div style="font-size: 1.8rem; font-weight: 800; color: #667eea; margin-bottom: 10px;">
                                        $<?= number_format($bill['amount'], 2) ?>
                                    </div>
                                    <span class="badge" style="background: <?= $bill['status'] === 'pending' ? '#ffc107' : '#10b981' ?>; color: <?= $bill['status'] === 'pending' ? '#000' : '#fff' ?>; padding: 6px 12px; font-weight: 600; margin-bottom: 10px; display: inline-block;">
                                        <?= ucfirst($bill['status']) ?>
                                    </span>
                                    
                                    <?php if ($bill['status'] === 'pending'): ?>
                                        <div style="display: flex; gap: 6px; flex-direction: column; margin-top: 10px;">
                                            <!-- UPI Payment Form -->
                                            <form method="POST" action="../admin/payments.php" style="display: block; width: 100%;">
                                                <input type="hidden" name="bill_id" value="<?= $bill['id'] ?>">
                                                <input type="hidden" name="payment_method" value="UPI">
                                                <button type="submit" name="process_payment" value="1" class="btn btn-sm" style="background: #ff6b6b; color: white; border: none; width: 100%; font-weight: 600; display: flex; align-items: center; justify-content: center; gap: 6px;">
                                                    <i class="bi bi-phone-fill"></i> Pay via UPI
                                                </button>
                                            </form>
                                            
                                            <!-- Card Payment Form -->
                                            <form method="POST" action="../admin/payments.php" style="display: block; width: 100%;">
                                                <input type="hidden" name="bill_id" value="<?= $bill['id'] ?>">
                                                <input type="hidden" name="payment_method" value="Card">
                                                <button type="submit" name="process_payment" value="1" class="btn btn-sm" style="background: #4c6ef5; color: white; border: none; width: 100%; font-weight: 600; display: flex; align-items: center; justify-content: center; gap: 6px;">
                                                    <i class="bi bi-credit-card-2-front-fill"></i> Pay via Card
                                                </button>
                                            </form>
                                        </div>
                                    <?php else: ?>
                                        <span class="badge" style="background: #d1fae5; color: #065f46; padding: 6px 12px; font-weight: 600;">
                                            ✅ Paid
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div style="text-align: center; padding: 40px; background: #f8f9fa; border-radius: 8px;">
                        <i class="bi bi-receipt" style="font-size: 3rem; color: #ddd; display: block; margin-bottom: 10px;"></i>
                        <p style="color: #999; font-weight: 500;">
                            <?php if ($filter === 'pending'): ?>
                                No pending bills. All set! 🎉
                            <?php elseif ($filter === 'paid'): ?>
                                No paid bills yet.
                            <?php else: ?>
                                No bills available.
                            <?php endif; ?>
                        </p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Payment History & Methods -->
    <div class="col-lg-4">
        <div class="card shadow p-4 mb-4">
            <h5 style="font-size: 1.2rem; margin-bottom: 15px;"><i class="bi bi-clock-history"></i> Recent Payments</h5>
            <div class="table-responsive">
                <table class="table table-sm table-hover" style="margin-bottom: 0;">
                    <thead>
                    <tr>
                        <th style="font-weight: 700; padding-bottom: 10px;">Amount & Description</th>
                        <th style="font-weight: 700; padding-bottom: 10px; text-align: right;">Method & Date</th>
                    </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($recentPayments)): ?>
                            <?php foreach ($recentPayments as $payment): ?>
                                <tr>
                                    <td>
                                        <strong style="color: #10b981;">$<?= number_format($payment['amount'], 2) ?></strong><br>
                                        <small style="color: #999;"><?= sanitize(substr($payment['description'], 0, 25)) ?>...</small>
                                    </td>
                                    <td style="text-align: right;">
                                        <?php if (!empty($payment['payment_method']) && $payment['payment_method'] === 'UPI'): ?>
                                            <span class="badge" style="background: #ff6b6b; color: white; padding: 6px 10px; border-radius: 4px; font-weight: 600;"><i class="bi bi-phone-fill"></i> UPI</span>
                                        <?php elseif (!empty($payment['payment_method']) && $payment['payment_method'] === 'Card'): ?>
                                            <span class="badge" style="background: #4c6ef5; color: white; padding: 6px 10px; border-radius: 4px; font-weight: 600;"><i class="bi bi-credit-card-2-front-fill"></i> Card</span>
                                        <?php elseif (!empty($payment['payment_method'])): ?>
                                            <span class="badge" style="background: #667eea; color: white; padding: 6px 10px; border-radius: 4px; font-weight: 600;"><?= sanitize($payment['payment_method']) ?></span>
                                        <?php else: ?>
                                            <span class="badge" style="background: #999; color: white; padding: 6px 10px; border-radius: 4px; font-weight: 600;">
                                                <i class="bi bi-question-circle"></i> Unknown
                                            </span>
                                        <?php endif; ?>
                                        <br>
                                        <small style="color: #999;"><?= date('M d, Y', strtotime($payment['paid_at'])) ?></small>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="2" class="text-muted text-center py-4" style="font-size: 0.9rem;">No payments yet</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Payment Methods Info -->
        <div class="card shadow p-4" style="background: #f0f4ff; border: 1px solid #dde0ff;">
            <h6 style="color: #667eea; font-weight: 700; margin-bottom: 15px;">
                <i class="bi bi-info-circle-fill"></i> Payment Methods
            </h6>
            <div style="display: flex; flex-direction: column; gap: 12px;">
                <!-- UPI Card -->
                <div style="background: white; padding: 12px; border-radius: 6px; border-left: 4px solid #ff6b6b;">
                    <h6 style="color: #ff6b6b; margin-bottom: 4px; font-weight: 700;">
                        <i class="bi bi-phone-fill"></i> UPI Payment
                    </h6>
                    <small style="color: #666;">Instant transfer using Unified Payments Interface. Fast & secure.</small>
                </div>
                
                <!-- Card Card -->
                <div style="background: white; padding: 12px; border-radius: 6px; border-left: 4px solid #4c6ef5;">
                    <h6 style="color: #4c6ef5; margin-bottom: 4px; font-weight: 700;">
                        <i class="bi bi-credit-card-2-front-fill"></i> Card Payment
                    </h6>
                    <small style="color: #666;">Credit/Debit cards accepted. Visa, Mastercard & more.</small>
                </div>
            </div>

            <!-- Security Note -->
            <div style="background: #ecfdf5; padding: 10px; border-radius: 6px; margin-top: 12px; border-left: 3px solid #10b981;">
                <small style="color: #065f46; font-weight: 500;">🔒 All payments are secured with encryption</small>
            </div>
        </div>
    </div>
</div>

<?php include '../inc/footer.php'; ?>
