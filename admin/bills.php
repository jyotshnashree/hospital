<?php
include '../db.php';
checkAuth();
if (getUserRole() !== 'admin') {
    header('Location: ../portals.php');
    exit;
}

$error = '';
$success = '';
$show_form = false;

// Handle add bill
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_bill'])) {
    $patient_id = intval($_POST['patient_id'] ?? 0);
    $amount = floatval($_POST['amount'] ?? 0);
    $description = sanitize($_POST['description'] ?? '');
    $appointment_id = intval($_POST['appointment_id'] ?? 0) ?: null;
    
    if ($patient_id <= 0 || $amount <= 0 || empty($description)) {
        $_SESSION['error'] = '❌ Invalid input. Please check all fields.';
        header('Location: bills.php?show_form=1');
        exit;
    }
    
    // Check if patient exists
    $stmt = $pdo->prepare('SELECT id FROM users WHERE id = ? AND role = "patient"');
    $stmt->execute([$patient_id]);
    if (!$stmt->fetch()) {
        $_SESSION['error'] = '❌ Patient not found.';
        header('Location: bills.php?show_form=1');
        exit;
    }
    
    // Insert bill
    $stmt = $pdo->prepare('INSERT INTO bills (patient_id, amount, description, appointment_id, status, created_at) VALUES (?, ?, ?, ?, "pending", NOW())');
    if ($stmt->execute([$patient_id, $amount, $description, $appointment_id])) {
        $_SESSION['success'] = '✅ Bill added successfully!';
        header('Location: bills.php');
        exit;
    } else {
        $_SESSION['error'] = '❌ Failed to add bill.';
        header('Location: bills.php?show_form=1');
        exit;
    }
}

// Handle pay bill
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['pay_bill'])) {
    $bill_id = intval($_POST['bill_id'] ?? 0);
    $method = sanitize($_POST['payment_method'] ?? 'Cash');
    if ($bill_id) {
        $stmt = $pdo->prepare('SELECT amount FROM bills WHERE id = ?');
        $stmt->execute([$bill_id]);
        $bill = $stmt->fetch();
        if ($bill) {
            $pdo->prepare('INSERT INTO payments (bill_id, amount, payment_method, paid_at) VALUES (?, ?, ?, NOW())')
                ->execute([$bill_id, $bill['amount'], $method]);
            $pdo->prepare('UPDATE bills SET status = "paid" WHERE id = ?')->execute([$bill_id]);
            $_SESSION['success'] = '✅ Payment recorded successfully and bill marked as paid!';
        }
    }
    header('Location: bills.php');
    exit;
}

if (isset($_GET['show_form'])) $show_form = true;

// Get bills for patients only
$bills = $pdo->query('SELECT b.*, u.full_name AS patient_name FROM bills b JOIN users u ON b.patient_id = u.id WHERE u.role = "patient" ORDER BY b.created_at DESC')->fetchAll();

// Get payment history for patients only
$payments = $pdo->query('SELECT p.*, u.full_name AS patient_name FROM payments p JOIN bills b ON p.bill_id = b.id JOIN users u ON b.patient_id = u.id WHERE u.role = "patient" ORDER BY p.paid_at DESC LIMIT 10')->fetchAll();

// Get patients for dropdown
$patients = $pdo->query('SELECT id, full_name FROM users WHERE role = "patient" ORDER BY full_name ASC')->fetchAll();
?>
<?php include '../inc/header.php'; ?>
<div style="margin-bottom: 2.5rem; animation: slideInUp 0.6s ease-out;">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h1 style="font-size: 3rem; margin-bottom: 0.5rem;">
                <i class="bi bi-cash-coin" style="color: #667eea;"></i> Bills Management
            </h1>
            <p class="text-muted" style="font-weight: 500; font-size: 1.1rem;">✨ Manage patient bills and payments</p>
        </div>
        <?php if (!$show_form): ?>
            <a href="?show_form=1" class="btn btn-success btn-lg">
                <i class="bi bi-plus-circle-fill"></i> Add New Bill
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
            <i class="bi bi-file-earmark-plus-fill"></i> Add New Bill
        </h3>
        
        <form method="POST" style="max-width: 700px;">
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
                    <label style="font-weight: 600; color: #333; margin-bottom: 10px; display: block;">Amount *</label>
                    <input type="number" name="amount" step="0.01" min="0.01" style="width: 100%; padding: 12px 15px; border: 2px solid #ddd; border-radius: 8px; font-size: 1rem; color: #333; background: white;" placeholder="0.00" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label style="font-weight: 600; color: #333; margin-bottom: 10px; display: block;">Appointment (Optional)</label>
                    <input type="number" name="appointment_id" style="width: 100%; padding: 12px 15px; border: 2px solid #ddd; border-radius: 8px; font-size: 1rem; color: #333; background: white;" placeholder="Appointment ID">
                </div>
            </div>

            <div class="mb-4">
                <label style="font-weight: 600; color: #333; margin-bottom: 10px; display: block;">Description *</label>
                <textarea name="description" style="width: 100%; padding: 12px 15px; border: 2px solid #ddd; border-radius: 8px; font-size: 1rem; color: #333; background: white; font-family: Arial, sans-serif; resize: vertical;" rows="3" placeholder="Enter bill description..." required></textarea>
            </div>

            <div style="display: flex; gap: 10px;">
                <button type="submit" name="add_bill" value="1" class="btn btn-success btn-lg" style="flex: 1;">
                    <i class="bi bi-check-circle-fill"></i> Add Bill
                </button>
                <a href="bills.php" class="btn btn-secondary btn-lg" style="flex: 1; text-decoration: none;">
                    <i class="bi bi-x-circle-fill"></i> Cancel
                </a>
            </div>
        </form>
    </div>
<?php endif; ?>

<div class="row g-4 mb-4">
    <div class="col-lg-6">
        <div class="card shadow p-4">
            <h5 class="mb-4" style="font-size: 1.5rem;"><i class="bi bi-wallet2" style="color: #28a745;"></i> Pending Bills</h5>
            <div class="table-responsive">
                <table class="table table-sm table-hover">
                    <thead>
                    <tr>
                        <th style="font-weight: 700;">#</th>
                        <th style="font-weight: 700;">Patient</th>
                        <th style="font-weight: 700;">Amount</th>
                        <th style="font-weight: 700;">Status</th>
                        <th style="font-weight: 700;">Payment</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($bills as $bill): ?>
                        <?php if ($bill['status'] === 'pending'): ?>
                        <tr>
                            <td><strong><?= $bill['id'] ?></strong></td>
                            <td><?= sanitize($bill['patient_name']) ?></td>
                            <td style="font-weight: 700; color: #667eea;">$<?= number_format($bill['amount'], 2) ?></td>
                            <td>
                                <span class="badge" style="background: #ffc107; color: #000;">⏳ Pending</span>
                            </td>
                            <td>
                                <div style="display: flex; flex-direction: column; gap: 6px;">
                                    <!-- Payment Method Buttons -->
                                    <div style="display: flex; gap: 4px; flex-wrap: wrap;">
                                        <!-- Cash -->
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="bill_id" value="<?= $bill['id'] ?>">
                                            <input type="hidden" name="payment_method" value="Cash">
                                            <button type="submit" name="pay_bill" value="1" class="btn btn-sm" style="background: #5cb85c; color: white; border: none; padding: 4px 8px; border-radius: 4px; font-weight: 600; font-size: 0.75rem;" title="Pay with Cash">
                                                💵 Cash
                                            </button>
                                        </form>
                                        
                                        <!-- Credit Card -->
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="bill_id" value="<?= $bill['id'] ?>">
                                            <input type="hidden" name="payment_method" value="Credit Card">
                                            <button type="submit" name="pay_bill" value="1" class="btn btn-sm" style="background: #0275d8; color: white; border: none; padding: 4px 8px; border-radius: 4px; font-weight: 600; font-size: 0.75rem;" title="Pay with Credit Card">
                                                💳 CC
                                            </button>
                                        </form>
                                        
                                        <!-- Debit Card -->
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="bill_id" value="<?= $bill['id'] ?>">
                                            <input type="hidden" name="payment_method" value="Debit Card">
                                            <button type="submit" name="pay_bill" value="1" class="btn btn-sm" style="background: #009688; color: white; border: none; padding: 4px 8px; border-radius: 4px; font-weight: 600; font-size: 0.75rem;" title="Pay with Debit Card">
                                                🏧 DC
                                            </button>
                                        </form>
                                        
                                        <!-- Online Transfer -->
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="bill_id" value="<?= $bill['id'] ?>">
                                            <input type="hidden" name="payment_method" value="Online Transfer">
                                            <button type="submit" name="pay_bill" value="1" class="btn btn-sm" style="background: #ff9800; color: white; border: none; padding: 4px 8px; border-radius: 4px; font-weight: 600; font-size: 0.75rem;" title="Pay via Online Transfer">
                                                🌐 OT
                                            </button>
                                        </form>
                                    </div>
                                    <div style="display: flex; gap: 4px; flex-wrap: wrap;">
                                        <!-- Cheque -->
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="bill_id" value="<?= $bill['id'] ?>">
                                            <input type="hidden" name="payment_method" value="Cheque">
                                            <button type="submit" name="pay_bill" value="1" class="btn btn-sm" style="background: #795548; color: white; border: none; padding: 4px 8px; border-radius: 4px; font-weight: 600; font-size: 0.75rem;" title="Pay by Cheque">
                                                ✓ Cheque
                                            </button>
                                        </form>
                                        
                                        <!-- Insurance -->
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="bill_id" value="<?= $bill['id'] ?>">
                                            <input type="hidden" name="payment_method" value="Insurance">
                                            <button type="submit" name="pay_bill" value="1" class="btn btn-sm" style="background: #9c27b0; color: white; border: none; padding: 4px 8px; border-radius: 4px; font-weight: 600; font-size: 0.75rem;" title="Pay via Insurance">
                                                🏥 Insurance
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <?php endif; ?>
                    <?php endforeach; ?>
                    <?php 
                    $pendingCount = 0;
                    foreach ($bills as $b) {
                        if ($b['status'] === 'pending') $pendingCount++;
                    }
                    if ($pendingCount === 0): ?>
                        <tr><td colspan="5" class="text-muted text-center py-4"><em>All bills are paid! 🎉</em></td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card shadow p-4">
            <h5 class="mb-4" style="font-size: 1.5rem;"><i class="bi bi-credit-card-2-front-fill" style="color: #4c6ef5;"></i> Payment History</h5>
            <div class="table-responsive">
                <table class="table table-sm table-hover">
                    <thead>
                    <tr>
                        <th style="font-weight: 700;">#</th>
                        <th style="font-weight: 700;">Patient</th>
                        <th style="font-weight: 700;">Amount</th>
                        <th style="font-weight: 700;">Method</th>
                        <th style="font-weight: 700;">Date</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($payments as $payment): ?>
                        <tr>
                            <td><strong><?= $payment['id'] ?></strong></td>
                            <td><?= sanitize($payment['patient_name']) ?></td>
                            <td style="font-weight: 700; color: #27ae60;">$<?= number_format($payment['amount'], 2) ?></td>
                            <td>
                                <?php 
                                    $method = $payment['payment_method'];
                                    if ($method === 'Cash'): ?>
                                    <span class="badge" style="background: #5cb85c; color: white;"><i class="bi bi-cash-coin"></i> Cash</span>
                                <?php elseif ($method === 'Credit Card'): ?>
                                    <span class="badge" style="background: #0275d8; color: white;"><i class="bi bi-credit-card-2-front-fill"></i> Credit Card</span>
                                <?php elseif ($method === 'Debit Card'): ?>
                                    <span class="badge" style="background: #009688; color: white;"><i class="bi bi-credit-card"></i> Debit Card</span>
                                <?php elseif ($method === 'Online Transfer'): ?>
                                    <span class="badge" style="background: #ff9800; color: white;"><i class="bi bi-arrow-left-right"></i> Online Transfer</span>
                                <?php elseif ($method === 'Cheque'): ?>
                                    <span class="badge" style="background: #795548; color: white;"><i class="bi bi-file-text"></i> Cheque</span>
                                <?php elseif ($method === 'Insurance'): ?>
                                    <span class="badge" style="background: #9c27b0; color: white;"><i class="bi bi-shield-check"></i> Insurance</span>
                                <?php elseif ($method === 'UPI'): ?>
                                    <span class="badge" style="background: #ff6b6b; color: white;"><i class="bi bi-phone-fill"></i> UPI</span>
                                <?php elseif ($method === 'Card'): ?>
                                    <span class="badge" style="background: #4c6ef5; color: white;"><i class="bi bi-credit-card-2-front-fill"></i> Card</span>
                                <?php else: ?>
                                    <span class="badge" style="background: #667eea; color: white;"><?= sanitize($method) ?></span>
                                <?php endif; ?>
                            </td>
                            <td><?= date('M d, Y', strtotime($payment['paid_at'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($payments)): ?>
                        <tr><td colspan="5" class="text-muted text-center py-4"><em>No payments recorded.</em></td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php include '../inc/footer.php'; ?>
