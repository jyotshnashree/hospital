<?php
/**
 * Pharmacy Management Module
 * Hospital Management System - Manage medicines inventory
 */

include '../db.php';
checkAuth();
if (getUserRole() !== 'admin') {
    header('Location: ../portals.php');
    exit;
}

$show_form = false;
$edit_id = null;
$edit_medicine = null;

// Create medicines table if not exists
$pdo->exec("
    CREATE TABLE IF NOT EXISTS medicines (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        generic_name VARCHAR(255),
        description TEXT,
        category VARCHAR(100),
        dosage VARCHAR(100),
        unit VARCHAR(50),
        quantity_in_stock INT DEFAULT 0,
        reorder_level INT DEFAULT 10,
        price DECIMAL(10, 2),
        supplier VARCHAR(255),
        expiry_date DATE,
        manufacturer VARCHAR(255),
        is_active BOOLEAN DEFAULT TRUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_name (name),
        INDEX idx_category (category),
        INDEX idx_stock (quantity_in_stock),
        INDEX idx_expiry (expiry_date)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");

// Handle add/update medicine
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_medicine'])) {
    $name = sanitize($_POST['name'] ?? '');
    $generic_name = sanitize($_POST['generic_name'] ?? '');
    $description = sanitize($_POST['description'] ?? '');
    $category = sanitize($_POST['category'] ?? '');
    $dosage = sanitize($_POST['dosage'] ?? '');
    $unit = sanitize($_POST['unit'] ?? '');
    $quantity = intval($_POST['quantity_in_stock'] ?? 0);
    $reorder = intval($_POST['reorder_level'] ?? 10);
    $price = floatval($_POST['price'] ?? 0);
    $supplier = sanitize($_POST['supplier'] ?? '');
    $expiry = sanitize($_POST['expiry_date'] ?? '');
    $manufacturer = sanitize($_POST['manufacturer'] ?? '');
    $medicine_id = intval($_POST['medicine_id'] ?? 0);
    
    if (empty($name)) {
        $_SESSION['error'] = '❌ Medicine name is required.';
        header('Location: pharmacy.php?show_form=1');
        exit;
    }
    
    try {
        if ($medicine_id > 0) {
            // Update
            $stmt = $pdo->prepare('
                UPDATE medicines 
                SET name=?, generic_name=?, description=?, category=?, dosage=?, unit=?, 
                    quantity_in_stock=?, reorder_level=?, price=?, supplier=?, expiry_date=?, 
                    manufacturer=?
                WHERE id=?
            ');
            $stmt->execute([$name, $generic_name, $description, $category, $dosage, $unit, $quantity, $reorder, $price, $supplier, $expiry ?: null, $manufacturer, $medicine_id]);
            $_SESSION['success'] = '✅ Medicine updated successfully!';
        } else {
            // Insert
            $stmt = $pdo->prepare('
                INSERT INTO medicines (name, generic_name, description, category, dosage, unit, 
                    quantity_in_stock, reorder_level, price, supplier, expiry_date, manufacturer)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ');
            $stmt->execute([$name, $generic_name, $description, $category, $dosage, $unit, $quantity, $reorder, $price, $supplier, $expiry ?: null, $manufacturer]);
            $_SESSION['success'] = '✅ Medicine added successfully!';
        }
        header('Location: pharmacy.php');
        exit;
    } catch (Exception $e) {
        $_SESSION['error'] = '❌ Failed to save medicine. Please try again.';
        header('Location: pharmacy.php?show_form=1');
        exit;
    }
}

// Handle delete medicine
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_medicine'])) {
    $medicine_id = intval($_POST['medicine_id'] ?? 0);
    
    try {
        $stmt = $pdo->prepare('DELETE FROM medicines WHERE id=?');
        if ($stmt->execute([$medicine_id])) {
            $_SESSION['success'] = '✅ Medicine deleted successfully!';
        }
    } catch (Exception $e) {
        $_SESSION['error'] = '❌ Failed to delete medicine.';
    }
    header('Location: pharmacy.php');
    exit;
}

if (isset($_GET['show_form'])) $show_form = true;
if (isset($_GET['edit']) && intval($_GET['edit']) > 0) {
    $edit_id = intval($_GET['edit']);
    $show_form = true;
    $stmt = $pdo->prepare('SELECT * FROM medicines WHERE id=?');
    $stmt->execute([$edit_id]);
    $edit_medicine = $stmt->fetch();
}

// Get all medicines
$medicines = $pdo->query('
    SELECT * FROM medicines 
    ORDER BY name ASC
')->fetchAll();

// Calculate statistics
$totalMedicines = count($medicines);
$lowStockCount = count(array_filter($medicines, fn($m) => $m['quantity_in_stock'] <= $m['reorder_level']));
$totalValue = array_sum(array_map(fn($m) => $m['quantity_in_stock'] * $m['price'], $medicines));
$expiredCount = count(array_filter($medicines, fn($m) => $m['expiry_date'] && strtotime($m['expiry_date']) < time()));

// Get prescriptions for reference
$prescriptions = $pdo->query("
    SELECT 
        p.id,
        p.medication,
        p.dosage,
        p.frequency,
        p.duration,
        p.instructions,
        a.appointment_date,
        u.full_name as patient_name,
        d.full_name as doctor_name,
        p.created_at
    FROM prescriptions p
    JOIN appointments a ON p.appointment_id = a.id
    JOIN users u ON a.patient_id = u.id
    JOIN users d ON a.doctor_id = d.id
    ORDER BY p.created_at DESC
    LIMIT 10
")->fetchAll();
?>
<?php include '../inc/header.php'; ?>
<div style="margin-bottom: 2.5rem; animation: slideInUp 0.6s ease-out;">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h1 style="font-size: 3rem; margin-bottom: 0.5rem;">
                <i class="bi bi-capsule"></i> Pharmacy Module
            </h1>
            <p class="text-muted" style="font-weight: 500; font-size: 1.1rem;">🏥 Manage medicines inventory</p>
        </div>
        <?php if (!$show_form): ?>
            <a href="?show_form=1" class="btn btn-success btn-lg">
                <i class="bi bi-plus-circle-fill"></i> Add Medicine
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

<!-- Statistics Cards -->
<div class="row g-4 mb-4">
    <div class="col-md-6 col-lg-3">
        <div class="card shadow p-4" style="border-left: 4px solid #667eea;">
            <h6 style="color: #666; margin-bottom: 10px; font-weight: 600;">Total Medicines</h6>
            <div style="font-size: 2.5rem; font-weight: 800; color: #667eea;">
                <?= $totalMedicines ?>
            </div>
            <p style="color: #999; margin: 0; font-size: 0.9rem;">in inventory</p>
        </div>
    </div>
    <div class="col-md-6 col-lg-3">
        <div class="card shadow p-4" style="border-left: 4px solid #f59e0b;">
            <h6 style="color: #666; margin-bottom: 10px; font-weight: 600;">Low Stock</h6>
            <div style="font-size: 2.5rem; font-weight: 800; color: #f59e0b;">
                <?= $lowStockCount ?>
            </div>
            <p style="color: #999; margin: 0; font-size: 0.9rem;">need reordering</p>
        </div>
    </div>
    <div class="col-md-6 col-lg-3">
        <div class="card shadow p-4" style="border-left: 4px solid #ef4444;">
            <h6 style="color: #666; margin-bottom: 10px; font-weight: 600;">Expired</h6>
            <div style="font-size: 2.5rem; font-weight: 800; color: #ef4444;">
                <?= $expiredCount ?>
            </div>
            <p style="color: #999; margin: 0; font-size: 0.9rem;">to be removed</p>
        </div>
    </div>
    <div class="col-md-6 col-lg-3">
        <div class="card shadow p-4" style="border-left: 4px solid #10b981;">
            <h6 style="color: #666; margin-bottom: 10px; font-weight: 600;">Inventory Value</h6>
            <div style="font-size: 2.5rem; font-weight: 800; color: #10b981;">
                $<?= number_format($totalValue, 2) ?>
            </div>
            <p style="color: #999; margin: 0; font-size: 0.9rem;">total stock value</p>
        </div>
    </div>
</div>

<!-- Add/Edit Medicine Form -->
<?php if ($show_form): ?>
    <div class="card shadow p-5 mb-4" style="background: white; border-radius: 15px;">
        <h3 style="margin-bottom: 30px; color: #667eea; font-weight: 700;">
            <i class="bi bi-capsule"></i> <?= $edit_medicine ? 'Edit Medicine' : 'Add New Medicine' ?>
        </h3>
        
        <form method="POST" style="max-width: 900px;">
            <?php if ($edit_medicine): ?>
                <input type="hidden" name="medicine_id" value="<?= $edit_medicine['id'] ?>">
            <?php endif; ?>
            
            <div class="row mb-3">
                <div class="col-md-6">
                    <label style="font-weight: 600; color: #333; margin-bottom: 10px; display: block;">Medicine Name *</label>
                    <input type="text" name="name" value="<?= $edit_medicine ? sanitize($edit_medicine['name']) : '' ?>" required
                        style="width: 100%; padding: 12px 15px; border: 2px solid #ddd; border-radius: 8px; font-size: 1rem;" placeholder="e.g., Amoxicillin">
                </div>
                <div class="col-md-6">
                    <label style="font-weight: 600; color: #333; margin-bottom: 10px; display: block;">Generic Name</label>
                    <input type="text" name="generic_name" value="<?= $edit_medicine ? sanitize($edit_medicine['generic_name']) : '' ?>"
                        style="width: 100%; padding: 12px 15px; border: 2px solid #ddd; border-radius: 8px; font-size: 1rem;" placeholder="Generic name">
                </div>
            </div>

            <div class="mb-3">
                <label style="font-weight: 600; color: #333; margin-bottom: 10px; display: block;">Description</label>
                <textarea name="description" style="width: 100%; padding: 12px 15px; border: 2px solid #ddd; border-radius: 8px; font-size: 1rem; font-family: Arial;"
                    rows="3" placeholder="Medicine description..."><?= $edit_medicine ? sanitize($edit_medicine['description']) : '' ?></textarea>
            </div>

            <div class="row mb-3">
                <div class="col-md-4">
                    <label style="font-weight: 600; color: #333; margin-bottom: 10px; display: block;">Category</label>
                    <select name="category" style="width: 100%; padding: 12px 15px; border: 2px solid #ddd; border-radius: 8px; font-size: 1rem;">
                        <option value="">Select Category</option>
                        <option value="Antibiotics" <?= $edit_medicine && $edit_medicine['category'] === 'Antibiotics' ? 'selected' : '' ?>>Antibiotics</option>
                        <option value="Analgesics" <?= $edit_medicine && $edit_medicine['category'] === 'Analgesics' ? 'selected' : '' ?>>Analgesics</option>
                        <option value="Antihistamines" <?= $edit_medicine && $edit_medicine['category'] === 'Antihistamines' ? 'selected' : '' ?>>Antihistamines</option>
                        <option value="Antacids" <?= $edit_medicine && $edit_medicine['category'] === 'Antacids' ? 'selected' : '' ?>>Antacids</option>
                        <option value="Anti-inflammatory" <?= $edit_medicine && $edit_medicine['category'] === 'Anti-inflammatory' ? 'selected' : '' ?>>Anti-inflammatory</option>
                        <option value="Vitamins" <?= $edit_medicine && $edit_medicine['category'] === 'Vitamins' ? 'selected' : '' ?>>Vitamins</option>
                        <option value="Others" <?= $edit_medicine && $edit_medicine['category'] === 'Others' ? 'selected' : '' ?>>Others</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label style="font-weight: 600; color: #333; margin-bottom: 10px; display: block;">Dosage</label>
                    <input type="text" name="dosage" value="<?= $edit_medicine ? sanitize($edit_medicine['dosage']) : '' ?>"
                        style="width: 100%; padding: 12px 15px; border: 2px solid #ddd; border-radius: 8px; font-size: 1rem;" placeholder="e.g., 500mg">
                </div>
                <div class="col-md-4">
                    <label style="font-weight: 600; color: #333; margin-bottom: 10px; display: block;">Unit</label>
                    <select name="unit" style="width: 100%; padding: 12px 15px; border: 2px solid #ddd; border-radius: 8px; font-size: 1rem;">
                        <option value="tablets" <?= $edit_medicine && $edit_medicine['unit'] === 'tablets' ? 'selected' : '' ?>>Tablets</option>
                        <option value="capsules" <?= $edit_medicine && $edit_medicine['unit'] === 'capsules' ? 'selected' : '' ?>>Capsules</option>
                        <option value="ml" <?= $edit_medicine && $edit_medicine['unit'] === 'ml' ? 'selected' : '' ?>>Milliliters (ml)</option>
                        <option value="bottles" <?= $edit_medicine && $edit_medicine['unit'] === 'bottles' ? 'selected' : '' ?>>Bottles</option>
                        <option value="tubes" <?= $edit_medicine && $edit_medicine['unit'] === 'tubes' ? 'selected' : '' ?>>Tubes</option>
                    </select>
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-3">
                    <label style="font-weight: 600; color: #333; margin-bottom: 10px; display: block;">Quantity in Stock *</label>
                    <input type="number" name="quantity_in_stock" value="<?= $edit_medicine ? $edit_medicine['quantity_in_stock'] : 0 ?>" required
                        style="width: 100%; padding: 12px 15px; border: 2px solid #ddd; border-radius: 8px; font-size: 1rem; min: 0;">
                </div>
                <div class="col-md-3">
                    <label style="font-weight: 600; color: #333; margin-bottom: 10px; display: block;">Reorder Level</label>
                    <input type="number" name="reorder_level" value="<?= $edit_medicine ? $edit_medicine['reorder_level'] : 10 ?>"
                        style="width: 100%; padding: 12px 15px; border: 2px solid #ddd; border-radius: 8px; font-size: 1rem; min: 0;">
                </div>
                <div class="col-md-3">
                    <label style="font-weight: 600; color: #333; margin-bottom: 10px; display: block;">Price ($)</label>
                    <input type="number" name="price" value="<?= $edit_medicine ? $edit_medicine['price'] : 0 ?>" step="0.01"
                        style="width: 100%; padding: 12px 15px; border: 2px solid #ddd; border-radius: 8px; font-size: 1rem; min: 0;">
                </div>
                <div class="col-md-3">
                    <label style="font-weight: 600; color: #333; margin-bottom: 10px; display: block;">Expiry Date</label>
                    <input type="date" name="expiry_date" value="<?= $edit_medicine ? $edit_medicine['expiry_date'] : '' ?>"
                        style="width: 100%; padding: 12px 15px; border: 2px solid #ddd; border-radius: 8px; font-size: 1rem;">
                </div>
            </div>

            <div class="row mb-4">
                <div class="col-md-6">
                    <label style="font-weight: 600; color: #333; margin-bottom: 10px; display: block;">Supplier</label>
                    <input type="text" name="supplier" value="<?= $edit_medicine ? sanitize($edit_medicine['supplier']) : '' ?>"
                        style="width: 100%; padding: 12px 15px; border: 2px solid #ddd; border-radius: 8px; font-size: 1rem;" placeholder="Supplier name">
                </div>
                <div class="col-md-6">
                    <label style="font-weight: 600; color: #333; margin-bottom: 10px; display: block;">Manufacturer</label>
                    <input type="text" name="manufacturer" value="<?= $edit_medicine ? sanitize($edit_medicine['manufacturer']) : '' ?>"
                        style="width: 100%; padding: 12px 15px; border: 2px solid #ddd; border-radius: 8px; font-size: 1rem;" placeholder="Manufacturer name">
                </div>
            </div>

            <div style="display: flex; gap: 10px;">
                <button type="submit" name="save_medicine" value="1" class="btn btn-success btn-lg" style="flex: 1;">
                    <i class="bi bi-check-circle-fill"></i> Save Medicine
                </button>
                <a href="pharmacy.php" class="btn btn-secondary btn-lg" style="flex: 1; text-decoration: none;">
                    <i class="bi bi-x-circle-fill"></i> Cancel
                </a>
            </div>
        </form>
    </div>
<?php endif; ?>

<!-- Medicines Inventory Table -->
<div class="card shadow p-4 mb-4">
    <h5 style="font-size: 1.5rem; margin-bottom: 20px;"><i class="bi bi-capsule"></i> Medicines Inventory</h5>
    <div class="table-responsive">
        <table class="table table-sm table-hover">
            <thead>
            <tr>
                <th style="font-weight: 700;">Name</th>
                <th style="font-weight: 700;">Category</th>
                <th style="font-weight: 700;">Dosage</th>
                <th style="font-weight: 700;">Stock</th>
                <th style="font-weight: 700;">Price</th>
                <th style="font-weight: 700;">Expiry</th>
                <th style="font-weight: 700;">Supplier</th>
                <th style="font-weight: 700;">Actions</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($medicines as $med): ?>
                <tr>
                    <td>
                        <strong><?= sanitize($med['name']) ?></strong>
                        <?php if ($med['generic_name']): ?>
                            <br><small style="color: #999;">Gen: <?= sanitize($med['generic_name']) ?></small>
                        <?php endif; ?>
                    </td>
                    <td><span class="badge" style="background: #667eea; color: white;"><?= sanitize($med['category']) ?: 'N/A' ?></span></td>
                    <td><?= sanitize($med['dosage']) ?: 'N/A' ?></td>
                    <td>
                        <?= $med['quantity_in_stock'] ?> <?= sanitize($med['unit']) ?>
                        <?php if ($med['quantity_in_stock'] <= $med['reorder_level']): ?>
                            <br><span style="color: #ef4444; font-weight: 600;">⚠️ Low Stock</span>
                        <?php endif; ?>
                        <?php if ($med['expiry_date'] && strtotime($med['expiry_date']) < time()): ?>
                            <br><span style="color: #ef4444; font-weight: 600;">❌ Expired</span>
                        <?php endif; ?>
                    </td>
                    <td>$<?= number_format($med['price'], 2) ?></td>
                    <td>
                        <?php if ($med['expiry_date']): ?>
                            <?= date('M d, Y', strtotime($med['expiry_date'])) ?>
                        <?php else: ?>
                            <span style="color: #999;">N/A</span>
                        <?php endif; ?>
                    </td>
                    <td><?= sanitize($med['supplier']) ?: '-' ?></td>
                    <td>
                        <a href="?edit=<?= $med['id'] ?>" class="btn btn-sm btn-primary">
                            <i class="bi bi-pencil"></i>
                        </a>
                        <form method="POST" style="display: inline;" onsubmit="return confirm('Delete this medicine?');">
                            <input type="hidden" name="medicine_id" value="<?= $med['id'] ?>">
                            <button type="submit" name="delete_medicine" value="1" class="btn btn-sm btn-danger">
                                <i class="bi bi-trash"></i>
                            </button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($medicines)): ?>
                <tr><td colspan="8" class="text-muted text-center py-4"><em>No medicines in inventory. Add one to get started!</em></td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Recent Prescriptions Reference -->
<div class="card shadow p-4">
    <h5 style="font-size: 1.5rem; margin-bottom: 20px;"><i class="bi bi-prescription"></i> Recent Prescriptions (Reference)</h5>
    <div class="table-responsive">
        <table class="table table-sm table-hover">
            <thead>
            <tr>
                <th style="font-weight: 700;">Medication</th>
                <th style="font-weight: 700;">Dosage</th>
                <th style="font-weight: 700;">Frequency</th>
                <th style="font-weight: 700;">Patient</th>
                <th style="font-weight: 700;">Doctor</th>
                <th style="font-weight: 700;">Date</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($prescriptions as $rx): ?>
                <tr>
                    <td><strong><?= sanitize($rx['medication']) ?></strong></td>
                    <td><?= sanitize($rx['dosage']) ?></td>
                    <td><?= sanitize($rx['frequency']) ?></td>
                    <td><?= sanitize($rx['patient_name']) ?></td>
                    <td><?= sanitize($rx['doctor_name']) ?></td>
                    <td><?= date('M d, Y', strtotime($rx['created_at'])) ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($prescriptions)): ?>
                <tr><td colspan="6" class="text-muted text-center py-4">No prescriptions yet.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include '../inc/footer.php'; ?>
