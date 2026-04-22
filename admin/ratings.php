<?php
include '../db.php';
checkAuth();
if (getUserRole() !== 'admin') {
    header('Location: ../portals.php');
    exit;
}

$show_form = false;
$edit_id = null;
$edit_rating = null;

// Create doctor ratings table if not exists
$pdo->exec("
    CREATE TABLE IF NOT EXISTS doctor_ratings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        doctor_id INT NOT NULL,
        patient_id INT NOT NULL,
        rating INT NOT NULL,
        review TEXT,
        category VARCHAR(100),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_doctor (doctor_id),
        INDEX idx_patient (patient_id),
        FOREIGN KEY (doctor_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (patient_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");

// Handle add/update rating
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_rating'])) {
    $doctor_id = intval($_POST['doctor_id'] ?? 0);
    $patient_id = intval($_POST['patient_id'] ?? 0);
    $rating = intval($_POST['rating'] ?? 0);
    $category = sanitize($_POST['category'] ?? 'Overall Experience');
    $review = sanitize($_POST['review'] ?? '');
    $rating_id = intval($_POST['rating_id'] ?? 0);
    
    if ($doctor_id <= 0) {
        $_SESSION['error'] = '❌ Please select a doctor.';
        header('Location: ratings.php?show_form=1');
        exit;
    }
    
    if ($rating < 1 || $rating > 5) {
        $_SESSION['error'] = '❌ Please select a valid rating.';
        header('Location: ratings.php?show_form=1');
        exit;
    }
    
    try {
        if ($rating_id > 0) {
            $stmt = $pdo->prepare('UPDATE doctor_ratings SET rating=?, review=?, category=? WHERE id=?');
            $stmt->execute([$rating, $review, $category, $rating_id]);
            $_SESSION['success'] = '✅ Doctor rating updated successfully!';
        } else {
            $stmt = $pdo->prepare('INSERT INTO doctor_ratings (doctor_id, patient_id, rating, review, category) VALUES (?, ?, ?, ?, ?)');
            $stmt->execute([$doctor_id, $patient_id, $rating, $review, $category]);
            $_SESSION['success'] = '✅ Doctor rating added successfully!';
        }
        header('Location: ratings.php');
        exit;
    } catch (Exception $e) {
        $_SESSION['error'] = '❌ Failed to save rating. Please try again.';
        header('Location: ratings.php?show_form=1');
        exit;
    }
}

// Handle delete rating
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_rating'])) {
    $rating_id = intval($_POST['rating_id'] ?? 0);
    
    try {
        $stmt = $pdo->prepare('DELETE FROM doctor_ratings WHERE id=?');
        if ($stmt->execute([$rating_id])) {
            $_SESSION['success'] = '✅ Doctor rating deleted successfully!';
        }
    } catch (Exception $e) {
        $_SESSION['error'] = '❌ Failed to delete rating.';
    }
    header('Location: ratings.php');
    exit;
}

if (isset($_GET['show_form'])) $show_form = true;
if (isset($_GET['edit']) && intval($_GET['edit']) > 0) {
    $edit_id = intval($_GET['edit']);
    $show_form = true;
    $stmt = $pdo->prepare('SELECT * FROM doctor_ratings WHERE id=?');
    $stmt->execute([$edit_id]);
    $edit_rating = $stmt->fetch();
}

// Get all doctor ratings
$ratings = $pdo->query('
    SELECT 
        dr.*,
        d.full_name as doctor_name,
        p.full_name as patient_name
    FROM doctor_ratings dr
    JOIN users d ON dr.doctor_id = d.id
    JOIN users p ON dr.patient_id = p.id
    ORDER BY dr.created_at DESC
')->fetchAll();

// Calculate doctor statistics
$doctor_stats = [];
foreach ($ratings as $rating) {
    $doc_id = $rating['doctor_id'];
    if (!isset($doctor_stats[$doc_id])) {
        $doctor_stats[$doc_id] = ['name' => $rating['doctor_name'], 'total' => 0, 'sum' => 0];
    }
    $doctor_stats[$doc_id]['total']++;
    $doctor_stats[$doc_id]['sum'] += $rating['rating'];
}

$totalRatings = count($ratings);
$averageRating = !empty($ratings) ? round(array_sum(array_map(fn($r) => $r['rating'], $ratings)) / $totalRatings, 1) : 0;

// Get doctors for dropdown
$doctors = $pdo->query('SELECT id, full_name FROM users WHERE role = "doctor" ORDER BY full_name ASC')->fetchAll();

// Get patients for dropdown
$patients = $pdo->query('SELECT id, full_name FROM users WHERE role = "patient" ORDER BY full_name ASC')->fetchAll();

?>
<?php include '../inc/header.php'; ?>
<div style="margin-bottom: 2.5rem; animation: slideInUp 0.6s ease-out;">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h1 style="font-size: 3rem; margin-bottom: 0.5rem;">
                <i class="bi bi-star-fill" style="color: #ffc107;"></i> Doctor Ratings
            </h1>
            <p class="text-muted" style="font-weight: 500; font-size: 1.1rem;">⭐ Manage doctor feedback from patients</p>
        </div>
        <?php if (!$show_form): ?>
            <a href="?show_form=1" class="btn btn-success btn-lg">
                <i class="bi bi-plus-circle-fill"></i> Add Rating
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
        <div class="card shadow p-4" style="border-left: 4px solid #ffc107;">
            <h6 style="color: #666; margin-bottom: 10px; font-weight: 600;">Average Rating</h6>
            <div style="font-size: 2.5rem; font-weight: 800; color: #ffc107;">
                <?= $averageRating ?>/5
            </div>
            <p style="color: #999; margin: 0; font-size: 0.9rem;">from all doctors</p>
        </div>
    </div>
    <div class="col-md-6 col-lg-3">
        <div class="card shadow p-4" style="border-left: 4px solid #667eea;">
            <h6 style="color: #666; margin-bottom: 10px; font-weight: 600;">Total Ratings</h6>
            <div style="font-size: 2.5rem; font-weight: 800; color: #667eea;">
                <?= $totalRatings ?>
            </div>
            <p style="color: #999; margin: 0; font-size: 0.9rem;">doctor ratings</p>
        </div>
    </div>
    <div class="col-md-6 col-lg-3">
        <div class="card shadow p-4" style="border-left: 4px solid #10b981;">
            <h6 style="color: #666; margin-bottom: 10px; font-weight: 600;">Rated Doctors</h6>
            <div style="font-size: 2.5rem; font-weight: 800; color: #10b981;">
                <?= count($doctor_stats) ?>
            </div>
            <p style="color: #999; margin: 0; font-size: 0.9rem;">have ratings</p>
        </div>
    </div>
</div>
<div class="row g-4 mb-4">
    <div class="col-md-6 col-lg-3">
        <div class="card shadow p-4" style="border-left: 4px solid #ffc107;">
            <h6 style="color: #666; margin-bottom: 10px; font-weight: 600;">Average Rating</h6>
            <div style="font-size: 2.5rem; font-weight: 800; color: #ffc107;">
                <?= $averageRating ?>/5
            </div>
            <p style="color: #999; margin: 0; font-size: 0.9rem;">from all doctors</p>
        </div>
    </div>
    <div class="col-md-6 col-lg-3">
        <div class="card shadow p-4" style="border-left: 4px solid #667eea;">
            <h6 style="color: #666; margin-bottom: 10px; font-weight: 600;">Total Ratings</h6>
            <div style="font-size: 2.5rem; font-weight: 800; color: #667eea;">
                <?= $totalRatings ?>
            </div>
            <p style="color: #999; margin: 0; font-size: 0.9rem;">doctor ratings</p>
        </div>
    </div>
    <div class="col-md-6 col-lg-3">
        <div class="card shadow p-4" style="border-left: 4px solid #10b981;">
            <h6 style="color: #666; margin-bottom: 10px; font-weight: 600;">Rated Doctors</h6>
            <div style="font-size: 2.5rem; font-weight: 800; color: #10b981;">
                <?= count($doctor_stats) ?>
            </div>
            <p style="color: #999; margin: 0; font-size: 0.9rem;">have ratings</p>
        </div>
    </div>
</div>

<?php if ($show_form): ?>
    <div class="card shadow p-5 mb-4" style="background: white; border-radius: 15px;">
        <h3 style="margin-bottom: 30px; color: #ffc107; font-weight: 700;">
            <i class="bi bi-star-fill"></i> <?= $edit_rating ? 'Edit Doctor Rating' : 'Add Doctor Rating' ?>
        </h3>
        
        <form method="POST" style="max-width: 700px;">
            <?php if ($edit_rating): ?>
                <input type="hidden" name="rating_id" value="<?= $edit_rating['id'] ?>">
            <?php endif; ?>
            
            <div class="mb-3">
                <label style="font-weight: 600; color: #333; margin-bottom: 10px; display: block;">Select Doctor *</label>
                <select name="doctor_id" required style="width: 100%; padding: 12px 15px; border: 2px solid #ddd; border-radius: 8px; font-size: 1rem; color: #333; background: white; cursor: pointer;">
                    <option value="">-- Select Doctor --</option>
                    <?php foreach ($doctors as $doc): ?>
                        <option value="<?= $doc['id'] ?>" <?= $edit_rating && $edit_rating['doctor_id'] == $doc['id'] ? 'selected' : '' ?>><?= sanitize($doc['full_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="mb-3">
                <label style="font-weight: 600; color: #333; margin-bottom: 10px; display: block;">Patient *</label>
                <select name="patient_id" required style="width: 100%; padding: 12px 15px; border: 2px solid #ddd; border-radius: 8px; font-size: 1rem; color: #333; background: white; cursor: pointer;">
                    <option value="">-- Select Patient --</option>
                    <?php foreach ($patients as $pat): ?>
                        <option value="<?= $pat['id'] ?>" <?= $edit_rating && $edit_rating['patient_id'] == $pat['id'] ? 'selected' : '' ?>><?= sanitize($pat['full_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="mb-3">
                <label style="font-weight: 600; color: #333; margin-bottom: 10px; display: block;">Rating *</label>
                <div style="display: flex; flex-direction: column; gap: 10px;">
                    <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; padding: 10px 15px; border: 2px solid #ddd; border-radius: 8px;">
                        <input type="radio" name="rating" value="5" required <?= $edit_rating && $edit_rating['rating'] == 5 ? 'checked' : '' ?>>
                        <span>⭐⭐⭐⭐⭐ Excellent</span>
                    </label>
                    <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; padding: 10px 15px; border: 2px solid #ddd; border-radius: 8px;">
                        <input type="radio" name="rating" value="4" required <?= $edit_rating && $edit_rating['rating'] == 4 ? 'checked' : '' ?>>
                        <span>⭐⭐⭐⭐ Good</span>
                    </label>
                    <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; padding: 10px 15px; border: 2px solid #ddd; border-radius: 8px;">
                        <input type="radio" name="rating" value="3" required <?= $edit_rating && $edit_rating['rating'] == 3 ? 'checked' : '' ?>>
                        <span>⭐⭐⭐ Average</span>
                    </label>
                    <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; padding: 10px 15px; border: 2px solid #ddd; border-radius: 8px;">
                        <input type="radio" name="rating" value="2" required <?= $edit_rating && $edit_rating['rating'] == 2 ? 'checked' : '' ?>>
                        <span>⭐⭐ Poor</span>
                    </label>
                    <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; padding: 10px 15px; border: 2px solid #ddd; border-radius: 8px;">
                        <input type="radio" name="rating" value="1" required <?= $edit_rating && $edit_rating['rating'] == 1 ? 'checked' : '' ?>>
                        <span>⭐ Very Poor</span>
                    </label>
                </div>
            </div>

            <div class="mb-3">
                <label style="font-weight: 600; color: #333; margin-bottom: 10px; display: block;">Category *</label>
                <select name="category" style="width: 100%; padding: 12px 15px; border: 2px solid #ddd; border-radius: 8px; font-size: 1rem; color: #333; background: white; cursor: pointer;" required>
                    <option value="Overall Experience" <?= $edit_rating && $edit_rating['category'] === 'Overall Experience' ? 'selected' : '' ?>>Overall Experience</option>
                    <option value="Professionalism" <?= $edit_rating && $edit_rating['category'] === 'Professionalism' ? 'selected' : '' ?>>Professionalism</option>
                    <option value="Communication" <?= $edit_rating && $edit_rating['category'] === 'Communication' ? 'selected' : '' ?>>Communication</option>
                    <option value="Treatment Quality" <?= $edit_rating && $edit_rating['category'] === 'Treatment Quality' ? 'selected' : '' ?>>Treatment Quality</option>
                    <option value="Punctuality" <?= $edit_rating && $edit_rating['category'] === 'Punctuality' ? 'selected' : '' ?>>Punctuality</option>
                    <option value="Attentiveness" <?= $edit_rating && $edit_rating['category'] === 'Attentiveness' ? 'selected' : '' ?>>Attentiveness</option>
                    <option value="Empathy" <?= $edit_rating && $edit_rating['category'] === 'Empathy' ? 'selected' : '' ?>>Empathy</option>
                </select>
            </div>

            <div class="mb-4">
                <label style="font-weight: 600; color: #333; margin-bottom: 10px; display: block;">Review (Optional)</label>
                <textarea name="review" style="width: 100%; padding: 12px 15px; border: 2px solid #ddd; border-radius: 8px; font-size: 1rem; color: #333; background: white; font-family: Arial, sans-serif; resize: vertical;" rows="4" placeholder="Share your feedback..."><?= $edit_rating ? sanitize($edit_rating['review']) : '' ?></textarea>
            </div>

            <div style="display: flex; gap: 10px;">
                <button type="submit" name="save_rating" value="1" class="btn btn-success btn-lg" style="flex: 1;">
                    <i class="bi bi-check-circle-fill"></i> Save Rating
                </button>
                <a href="ratings.php" class="btn btn-secondary btn-lg" style="flex: 1; text-decoration: none;">
                    <i class="bi bi-x-circle-fill"></i> Cancel
                </a>
            </div>
        </form>
    </div>
<?php endif; ?>

<!-- Doctor Ratings Table -->
<div class="card shadow p-4 mb-4">
    <h5 style="font-size: 1.5rem; margin-bottom: 20px;"><i class="bi bi-star-fill" style="color: #ffc107;"></i> Doctor Ratings</h5>
    <div class="table-responsive">
        <table class="table table-sm table-hover">
            <thead>
            <tr>
                <th style="font-weight: 700;">Doctor</th>
                <th style="font-weight: 700;">Patient</th>
                <th style="font-weight: 700;">Rating</th>
                <th style="font-weight: 700;">Category</th>
                <th style="font-weight: 700;">Review</th>
                <th style="font-weight: 700;">Date</th>
                <th style="font-weight: 700;">Actions</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($ratings as $rating): ?>
                <tr>
                    <td><strong><?= sanitize($rating['doctor_name']) ?></strong></td>
                    <td><?= sanitize($rating['patient_name']) ?></td>
                    <td>
                        <span style="color: #ffc107; font-size: 1.1rem;">
                            <?php for ($i = 0; $i < $rating['rating']; $i++): ?>
                                ⭐
                            <?php endfor; ?>
                        </span>
                        <span style="color: #999; margin-left: 5px;"><?= $rating['rating'] ?>/5</span>
                    </td>
                    <td><span class="badge" style="background: #667eea; color: white;"><?= sanitize($rating['category']) ?></span></td>
                    <td>
                        <?php if ($rating['review']): ?>
                            <small style="color: #666;"><?= substr(sanitize($rating['review']), 0, 30) ?>...</small>
                        <?php else: ?>
                            <span style="color: #999;">-</span>
                        <?php endif; ?>
                    </td>
                    <td><?= date('M d, Y', strtotime($rating['created_at'])) ?></td>
                    <td>
                        <a href="?edit=<?= $rating['id'] ?>&show_form=1" class="btn btn-sm btn-primary">
                            <i class="bi bi-pencil"></i>
                        </a>
                        <form method="POST" style="display: inline;" onsubmit="return confirm('Delete this rating?');">
                            <input type="hidden" name="rating_id" value="<?= $rating['id'] ?>">
                            <button type="submit" name="delete_rating" value="1" class="btn btn-sm btn-danger">
                                <i class="bi bi-trash"></i>
                            </button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($ratings)): ?>
                <tr><td colspan="7" class="text-muted text-center py-4"><em>No doctor ratings yet.</em></td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Doctor Performance Summary -->
<?php if (!empty($doctor_stats)): ?>
    <div class="card shadow p-4 mt-4">
        <h5 style="font-size: 1.5rem; margin-bottom: 20px;"><i class="bi bi-bar-chart-fill"></i> Doctor Performance Summary</h5>
        <div class="table-responsive">
            <table class="table table-sm table-hover">
                <thead>
                <tr>
                    <th style="font-weight: 700;">Doctor</th>
                    <th style="font-weight: 700;">Average Rating</th>
                    <th style="font-weight: 700;">Total Ratings</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($doctor_stats as $doc_id => $stat): ?>
                    <tr>
                        <td><strong><?= sanitize($stat['name']) ?></strong></td>
                        <td>
                            <span style="color: #ffc107; font-size: 1rem;">
                                <?php $avg = round($stat['sum'] / $stat['total'], 1); ?>
                                <?php for ($i = 0; $i < floor($avg); $i++): ?>
                                    ⭐
                                <?php endfor; ?>
                            </span>
                            <strong style="margin-left: 8px;"><?= $avg ?>/5</strong>
                        </td>
                        <td><?= $stat['total'] ?> rating<?= $stat['total'] !== 1 ? 's' : '' ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

<?php include '../inc/footer.php'; ?>
