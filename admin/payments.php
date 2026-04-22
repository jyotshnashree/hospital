<?php
/**
 * Online Payment Integration Module
 * Hospital Management System - Process online payments (UPI and Card)
 */

include '../db.php';
checkAuth();
if (getUserRole() !== 'admin') {
    header('Location: ../portals.php');
    exit;
}

// Handle payment processing
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['process_payment'])) {
    $bill_id = intval($_POST['bill_id'] ?? 0);
    $payment_method = sanitize($_POST['payment_method'] ?? '');
    $transaction_id = $_POST['transaction_id'] ?? null;
    
    if ($bill_id <= 0 || empty($payment_method)) {
        $_SESSION['error'] = '❌ Invalid payment details.';
        header('Location: payments.php');
        exit;
    }
    
    try {
        // Get bill details
        $stmt = $pdo->prepare('SELECT amount FROM bills WHERE id = ?');
        $stmt->execute([$bill_id]);
        $bill = $stmt->fetch();
        
        if (!$bill) {
            $_SESSION['error'] = '❌ Bill not found.';
            header('Location: payments.php');
            exit;
        }
        
        // Generate transaction ID if not provided
        if (empty($transaction_id)) {
            $transaction_id = 'TXN_' . strtoupper($payment_method[0]) . '_' . uniqid() . '_' . time();
        }
        
        // Record payment
        $stmt = $pdo->prepare('
            INSERT INTO payments (bill_id, amount, payment_method, transaction_id, paid_at)
            VALUES (?, ?, ?, ?, NOW())
        ');
        $stmt->execute([$bill_id, $bill['amount'], $payment_method, $transaction_id]);
        
        // Update bill status to paid
        $updateStmt = $pdo->prepare('UPDATE bills SET status = ? WHERE id = ?');
        $updateStmt->execute(['paid', $bill_id]);
        
        $_SESSION['success'] = "✅ Payment processed successfully via $payment_method! Transaction ID: $transaction_id";
        header('Location: payments.php');
        exit;
    } catch (Exception $e) {
        $_SESSION['error'] = '❌ Failed to process payment: ' . $e->getMessage();
        header('Location: payments.php');
        exit;
    }
}

// Get pending bills
$pendingBills = $pdo->query('
    SELECT 
        b.id,
        b.amount,
        b.description,
        b.due_date,
        u.full_name as patient_name,
        u.email,
        u.phone
    FROM bills b
    JOIN users u ON b.patient_id = u.id
    WHERE b.status = "pending"
    ORDER BY b.due_date ASC
')->fetchAll();

// Get payment history
$paymentHistory = $pdo->query('
    SELECT 
        p.id,
        p.amount,
        p.payment_method,
        p.transaction_id,
        p.paid_at,
        b.description,
        u.full_name as patient_name,
        u.email
    FROM payments p
    JOIN bills b ON p.bill_id = b.id
    JOIN users u ON b.patient_id = u.id
    ORDER BY p.paid_at DESC
    LIMIT 20
')->fetchAll();

// Calculate totals
$totalPending = array_sum(array_map(fn($b) => $b['amount'], $pendingBills));
$totalProcessed = array_sum(array_map(fn($p) => $p['amount'], $paymentHistory));
?>
<?php include '../inc/header.php'; ?>
<div style="margin-bottom: 2.5rem; animation: slideInUp 0.6s ease-out;">
    <div>
        <h1 style="font-size: 3rem; margin-bottom: 0.5rem;">
            <i class="bi bi-credit-card-2-front-fill" style="color: #667eea;"></i> Online Payment Processing
        </h1>
        <p class="text-muted" style="font-weight: 500; font-size: 1.1rem;">💳 Process patient payments via UPI and Card</p>
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

<!-- Statistics -->
<div class="row g-4 mb-4">
    <div class="col-md-6 col-lg-3">
        <div class="card shadow p-4" style="border-left: 4px solid #ffc107;">
            <h6 style="color: #666; margin-bottom: 10px; font-weight: 600;">Pending Payments</h6>
            <div style="font-size: 2rem; font-weight: 800; color: #ffc107;">
                <?= count($pendingBills) ?>
            </div>
            <p style="color: #999; margin: 0; font-size: 0.9rem;">Total: $<?= number_format($totalPending, 2) ?></p>
        </div>
    </div>
    <div class="col-md-6 col-lg-3">
        <div class="card shadow p-4" style="border-left: 4px solid #10b981;">
            <h6 style="color: #666; margin-bottom: 10px; font-weight: 600;">Processed Payments</h6>
            <div style="font-size: 2rem; font-weight: 800; color: #10b981;">
                <?= count($paymentHistory) ?>
            </div>
            <p style="color: #999; margin: 0; font-size: 0.9rem;">Total: $<?= number_format($totalProcessed, 2) ?></p>
        </div>
    </div>
    <div class="col-md-6 col-lg-3">
        <div class="card shadow p-4" style="border-left: 4px solid #ff6b6b;">
            <h6 style="color: #666; margin-bottom: 10px; font-weight: 600;"><i class="bi bi-phone-fill"></i> UPI Payments</h6>
            <div style="font-size: 2rem; font-weight: 800; color: #ff6b6b;">
                <?= count(array_filter($paymentHistory, fn($p) => $p['payment_method'] === 'UPI')) ?>
            </div>
            <p style="color: #999; margin: 0; font-size: 0.9rem;">via UPI</p>
        </div>
    </div>
    <div class="col-md-6 col-lg-3">
        <div class="card shadow p-4" style="border-left: 4px solid #4c6ef5;">
            <h6 style="color: #666; margin-bottom: 10px; font-weight: 600;"><i class="bi bi-credit-card-2-front-fill"></i> Card Payments</h6>
            <div style="font-size: 2rem; font-weight: 800; color: #4c6ef5;">
                <?= count(array_filter($paymentHistory, fn($p) => $p['payment_method'] === 'Card')) ?>
            </div>
            <p style="color: #999; margin: 0; font-size: 0.9rem;">via Card</p>
        </div>
    </div>
</div>

<div class="row">
    <!-- Pending Bills Section -->
    <div class="col-lg-8">
        <div class="card shadow p-4 mb-4">
            <h5 style="font-size: 1.5rem; margin-bottom: 20px;"><i class="bi bi-cash-coin"></i> Pending Bills for Payment</h5>
            <div>
                <?php if (!empty($pendingBills)): ?>
                    <?php foreach ($pendingBills as $bill): ?>
                        <div class="card p-4 mb-3" style="border-left: 4px solid #ffc107;">
                            <div class="row">
                                <div class="col-md-7">
                                    <h6 style="margin-bottom: 10px; font-weight: 700;">
                                        <i class="bi bi-receipt"></i> Bill #<?= $bill['id'] ?>
                                    </h6>
                                    <p style="margin: 5px 0; color: #666;">
                                        <strong>Patient:</strong> <?= sanitize($bill['patient_name']) ?>
                                    </p>
                                    <p style="margin: 5px 0; color: #666;">
                                        <strong>Email:</strong> <?= sanitize($bill['email']) ?>
                                    </p>
                                    <p style="margin: 5px 0; color: #666;">
                                        <strong>Description:</strong> <?= sanitize($bill['description']) ?>
                                    </p>
                                    <small style="color: #999;">📅 Due: <?= date('M d, Y', strtotime($bill['due_date'])) ?></small>
                                </div>
                                <div class="col-md-5" style="text-align: right;">
                                    <div style="font-size: 2rem; font-weight: 800; color: #667eea; margin-bottom: 15px;">
                                        $<?= number_format($bill['amount'], 2) ?>
                                    </div>
                                    <div style="display: flex; gap: 8px; flex-direction: column;">
                                        <!-- UPI Payment Form -->
                                        <form method="POST" style="display: block; width: 100%;">
                                            <input type="hidden" name="bill_id" value="<?= $bill['id'] ?>">
                                            <input type="hidden" name="payment_method" value="UPI">
                                            <button type="submit" name="process_payment" value="1" class="btn btn-sm" style="background: #ff6b6b; color: white; border: none; width: 100%; padding: 8px 12px; border-radius: 4px; font-weight: 600; display: flex; align-items: center; justify-content: center; gap: 6px; cursor: pointer;">
                                                <i class="bi bi-phone-fill"></i> Pay via UPI
                                            </button>
                                        </form>
                                        
                                        <!-- Card Payment Form -->
                                        <form method="POST" style="display: block; width: 100%;">
                                            <input type="hidden" name="bill_id" value="<?= $bill['id'] ?>">
                                            <input type="hidden" name="payment_method" value="Card">
                                            <button type="submit" name="process_payment" value="1" class="btn btn-sm" style="background: #4c6ef5; color: white; border: none; width: 100%; padding: 8px 12px; border-radius: 4px; font-weight: 600; display: flex; align-items: center; justify-content: center; gap: 6px; cursor: pointer;">
                                                <i class="bi bi-credit-card-2-front-fill"></i> Pay via Card
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div style="text-align: center; padding: 40px; background: #f8f9fa; border-radius: 8px;">
                        <i class="bi bi-check-circle" style="font-size: 3rem; color: #10b981; display: block; margin-bottom: 10px;"></i>
                        <p style="color: #666; font-weight: 500;">✅ No pending bills! All bills are paid.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Payment History Section -->
    <div class="col-lg-4">
        <div class="card shadow p-4">
            <h5 style="font-size: 1.5rem; margin-bottom: 20px;"><i class="bi bi-clock-history"></i> Recent Payments</h5>
            <div class="table-responsive">
                <table class="table table-sm table-hover">
                    <thead>
                    <tr>
                        <th style="font-weight: 700;">Amount</th>
                        <th style="font-weight: 700;">Method</th>
                        <th style="font-weight: 700;">Date</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($paymentHistory as $payment): ?>
                        <tr>
                            <td style="font-weight: 700; color: #10b981;">$<?= number_format($payment['amount'], 2) ?></td>
                            <td>
                                <?php if (!empty($payment['payment_method']) && $payment['payment_method'] === 'UPI'): ?>
                                    <span class="badge" style="background: #ff6b6b; color: white; padding: 6px 10px; border-radius: 4px; font-weight: 600;">
                                        <i class="bi bi-phone-fill"></i> UPI
                                    </span>
                                <?php elseif (!empty($payment['payment_method']) && $payment['payment_method'] === 'Card'): ?>
                                    <span class="badge" style="background: #4c6ef5; color: white; padding: 6px 10px; border-radius: 4px; font-weight: 600;">
                                        <i class="bi bi-credit-card-2-front-fill"></i> Card
                                    </span>
                                <?php elseif (!empty($payment['payment_method'])): ?>
                                    <span class="badge" style="background: #667eea; color: white; padding: 6px 10px; border-radius: 4px; font-weight: 600;"><?= sanitize($payment['payment_method']) ?></span>
                                <?php else: ?>
                                    <span class="badge" style="background: #999; color: white; padding: 6px 10px; border-radius: 4px; font-weight: 600;">
                                        <i class="bi bi-question-circle"></i> Unknown
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td><small><?= date('M d, Y', strtotime($payment['paid_at'])) ?></small></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($paymentHistory)): ?>
                        <tr><td colspan="3" class="text-muted text-center py-4"><em>No payments yet.</em></td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Payment Methods Info -->
            <div class="mt-4">
                <div class="alert alert-info" style="margin-bottom: 0;">
                    <strong>💡 Payment Methods:</strong>
                    <ul style="margin: 10px 0 0 0; padding-left: 20px;">
                        <li><strong style="color: #ff6b6b;">UPI:</strong> Unified Payments Interface for instant transfers</li>
                        <li><strong style="color: #4c6ef5;">Card:</strong> Credit/Debit card payments (Visa, Mastercard, etc.)</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../inc/footer.php'; ?>
