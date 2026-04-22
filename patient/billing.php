<?php
include '../db.php';
checkAuth();
if (getUserRole() !== 'patient') {
    header('Location: ../dashboard.php');
    exit;
}
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['generate_bill'])) {
        $appointment_id = intval($_POST['appointment_id'] ?? 0);
        $amount = floatval($_POST['amount'] ?? 0);
        if ($appointment_id && $amount > 0) {
            $stmt = $pdo->prepare('SELECT id FROM appointments WHERE id = ? AND patient_id = ?');
            $stmt->execute([$appointment_id, $_SESSION['user_id']]);
            if ($stmt->fetch()) {
                $pdo->prepare('INSERT INTO bills (patient_id, appointment_id, amount, status, created_at) VALUES (?, ?, ?, "Unpaid", NOW())')
                    ->execute([$_SESSION['user_id'], $appointment_id, $amount]);
                $success = 'Bill generated successfully.';
            }
        } else {
            $error = 'Please select an appointment and enter a valid amount.';
        }
    }
    if (isset($_POST['pay_bill'])) {
        $bill_id = intval($_POST['bill_id'] ?? 0);
        $method = sanitize($_POST['payment_method'] ?? 'Cash');
        if ($bill_id) {
            $stmt = $pdo->prepare('SELECT amount FROM bills WHERE id = ? AND patient_id = ?');
            $stmt->execute([$bill_id, $_SESSION['user_id']]);
            $bill = $stmt->fetch();
            if ($bill) {
                $pdo->prepare('INSERT INTO payments (bill_id, amount, payment_method, paid_at) VALUES (?, ?, ?, NOW())')
                    ->execute([$bill_id, $bill['amount'], $method]);
                $pdo->prepare('UPDATE bills SET status = "Paid" WHERE id = ?')->execute([$bill_id]);
                $success = 'Payment recorded successfully.';
            }
        }
    }
}
$appointments = $pdo->prepare('SELECT id, appointment_date, appointment_time FROM appointments WHERE patient_id = ? ORDER BY appointment_date DESC');
$appointments->execute([$_SESSION['user_id']]);
$appointments = $appointments->fetchAll();
$bills = $pdo->prepare('SELECT b.*, a.appointment_date FROM bills b LEFT JOIN appointments a ON b.appointment_id = a.id WHERE b.patient_id = ? ORDER BY b.created_at DESC');
$bills->execute([$_SESSION['user_id']]);
$bills = $bills->fetchAll();
$payments = $pdo->prepare('SELECT p.*, b.amount AS bill_amount FROM payments p JOIN bills b ON p.bill_id = b.id WHERE b.patient_id = ? ORDER BY p.paid_at DESC');
$payments->execute([$_SESSION['user_id']]);
$payments = $payments->fetchAll();
?>
<?php include '../inc/header.php'; ?>
<h1 class="mb-4">Billing</h1>
<?php if ($error): ?>
    <div class="alert alert-danger"><?= $error ?></div>
<?php endif; ?>
<?php if ($success): ?>
    <div class="alert alert-success"><?= $success ?></div>
<?php endif; ?>
<div class="row g-4 mb-4">
    <div class="col-md-6">
        <div class="card shadow-sm p-4">
            <h5>Generate Bill</h5>
            <form method="post">
                <input type="hidden" name="generate_bill" value="1">
                <div class="mb-3">
                    <label class="form-label">Appointment</label>
                    <select name="appointment_id" class="form-select" required>
                        <option value="">Select appointment</option>
                        <?php foreach ($appointments as $appointment): ?>
                            <option value="<?= $appointment['id'] ?>">#<?= $appointment['id'] ?> - <?= sanitize($appointment['appointment_date']) ?> <?= sanitize($appointment['appointment_time']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Amount</label>
                    <input type="number" step="0.01" name="amount" class="form-control" required>
                </div>
                <button class="btn btn-primary">Create Bill</button>
            </form>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card shadow-sm p-4">
            <h5>Open Bills</h5>
            <div class="table-responsive">
                <table class="table table-sm">
                    <thead>
                    <tr>
                        <th>#</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Date</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($bills as $bill): ?>
                        <tr>
                            <td><?= $bill['id'] ?></td>
                            <td>$<?= number_format($bill['amount'], 2) ?></td>
                            <td><?= sanitize($bill['status']) ?></td>
                            <td><?= sanitize($bill['created_at']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($bills)): ?>
                        <tr><td colspan="4" class="text-muted">No bills found.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<div class="card shadow-sm p-4 mb-4">
    <h5>Pay Bill</h5>
    <form method="post" onsubmit="return confirm('Submit payment for this bill?')">
        <input type="hidden" name="pay_bill" value="1">
        <div class="mb-3">
            <label class="form-label">Unpaid Bill</label>
            <select name="bill_id" class="form-select" required>
                <option value="">Select unpaid bill</option>
                <?php foreach ($bills as $bill): ?>
                    <?php if ($bill['status'] === 'Unpaid'): ?>
                        <option value="<?= $bill['id'] ?>">#<?= $bill['id'] ?> - $<?= number_format($bill['amount'], 2) ?></option>
                    <?php endif; ?>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="mb-3">
            <label class="form-label">Payment Method</label>
            <select name="payment_method" class="form-select" required>
                <option value="Cash">Cash</option>
                <option value="Card">Card</option>
                <option value="Online">Online</option>
            </select>
        </div>
        <button class="btn btn-success">Pay Bill</button>
    </form>
</div>
<div class="card shadow-sm p-4 mb-4">
    <h5>Payment History</h5>
    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead>
            <tr>
                <th>#</th>
                <th>Bill ID</th>
                <th>Amount</th>
                <th>Method</th>
                <th>Paid At</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($payments as $payment): ?>
                <tr>
                    <td><?= $payment['id'] ?></td>
                    <td><?= $payment['bill_id'] ?></td>
                    <td>$<?= number_format($payment['amount'], 2) ?></td>
                    <td><?= sanitize($payment['payment_method']) ?></td>
                    <td><?= sanitize($payment['paid_at']) ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($payments)): ?>
                <tr><td colspan="5" class="text-muted">No payments recorded.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php include '../inc/footer.php'; ?>
