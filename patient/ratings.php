<?php
include '../db.php';
checkAuth();
if (getUserRole() !== 'patient') {
    header('Location: ../portals.php');
    exit;
}

$patient_id = $_SESSION['user_id'];
$show_form = false;

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

// Handle add rating
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_rating'])) {
    $doctor_id = intval($_POST['doctor_id'] ?? 0);
    $rating = intval($_POST['rating'] ?? 0);
    $category = sanitize($_POST['category'] ?? 'Overall Experience');
    $review = sanitize($_POST['review'] ?? '');
    
    if ($doctor_id <= 0) {
        $_SESSION['error'] = '❌ Please select a doctor to rate.';
        header('Location: ratings.php?show_form=1');
        exit;
    }
    
    if ($rating < 1 || $rating > 5) {
        $_SESSION['error'] = '❌ Please select a valid rating.';
        header('Location: ratings.php?show_form=1');
        exit;
    }
    
    // Insert rating
    try {
        $stmt = $pdo->prepare('INSERT INTO doctor_ratings (doctor_id, patient_id, rating, review, category) VALUES (?, ?, ?, ?, ?)');
        if ($stmt->execute([$doctor_id, $patient_id, $rating, $review, $category])) {
            $_SESSION['success'] = '✅ Thank you! Your doctor rating has been submitted!';
            header('Location: ratings.php');
            exit;
        }
    } catch (Exception $e) {
        $_SESSION['error'] = '❌ Failed to submit rating. Please try again.';
        header('Location: ratings.php?show_form=1');
        exit;
    }
}

if (isset($_GET['show_form'])) $show_form = true;

// Get my doctor ratings
$stmt = $pdo->prepare('
    SELECT 
        dr.*,
        d.full_name AS doctor_name
    FROM doctor_ratings dr
    JOIN users d ON dr.doctor_id = d.id
    WHERE dr.patient_id = ?
    ORDER BY dr.created_at DESC
');
$stmt->execute([$patient_id]);
$my_ratings = $stmt->fetchAll();

// Get doctors for dropdown
$doctors = $pdo->query('SELECT id, full_name FROM users WHERE role = "doctor" ORDER BY full_name ASC')->fetchAll();

// Get rating statistics
$totalRatings = count($my_ratings);
$averageRating = !empty($my_ratings) ? round(array_sum(array_map(fn($r) => $r['rating'], $my_ratings)) / $totalRatings, 1) : 0;
?>
<?php include '../inc/header.php'; ?>
<div style="margin-bottom: 2.5rem; animation: slideInUp 0.6s ease-out;">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h1 style="font-size: 3rem; margin-bottom: 0.5rem;">
                <i class="bi bi-star-fill" style="color: #ffc107;"></i> Doctor Ratings
            </h1>
            <p class="text-muted" style="font-weight: 500; font-size: 1.1rem;">⭐ Rate doctors and share your feedback</p>
        </div>
        <?php if (!$show_form): ?>
            <a href="?show_form=1" class="btn btn-success btn-lg">
                <i class="bi bi-plus-circle-fill"></i> Rate a Doctor
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

<?php if (count($my_ratings) > 0): ?>
    <div class="row g-4 mb-4">
        <div class="col-md-6 col-lg-4">
            <div class="card shadow p-4" style="border-left: 4px solid #ffc107;">
                <h6 style="color: #666; margin-bottom: 10px; font-weight: 600;">Your Average Rating Given</h6>
                <div style="font-size: 2.5rem; font-weight: 800; color: #ffc107; margin-bottom: 5px;">
                    <?php $avg = round(array_sum(array_map(fn($r) => $r['rating'], $my_ratings)) / count($my_ratings), 1); echo $avg; ?>/5
                </div>
                <p style="color: #999; margin: 0; font-size: 0.9rem;">from <?= count($my_ratings) ?> ratings</p>
            </div>
        </div>
        <div class="col-md-6 col-lg-4">
            <div class="card shadow p-4" style="border-left: 4px solid #4c6ef5;">
                <h6 style="color: #666; margin-bottom: 10px; font-weight: 600;">Doctors Rated</h6>
                <div style="font-size: 2.5rem; font-weight: 800; color: #4c6ef5;">
                    <?= count(array_unique(array_map(fn($r) => $r['doctor_id'], $my_ratings))) ?>
                </div>
                <p style="color: #999; margin: 0; font-size: 0.9rem;">unique doctors</p>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php if ($show_form): ?>
    <div class="card shadow p-5 mb-4" style="background: white; border-radius: 15px;">
        <h3 style="margin-bottom: 30px; color: #ffc107; font-weight: 700;">
            <i class="bi bi-star-fill"></i> Rate a Doctor
        </h3>
        
        <form method="POST" style="max-width: 700px;">
            <div class="mb-3">
                <label style="font-weight: 600; color: #333; margin-bottom: 10px; display: block;">Select Doctor *</label>
                <select name="doctor_id" required style="width: 100%; padding: 12px 15px; border: 2px solid #ddd; border-radius: 8px; font-size: 1rem; color: #333; background: white; cursor: pointer;">
                    <option value="">-- Select a Doctor --</option>
                    <?php foreach ($doctors as $doctor): ?>
                        <option value="<?= $doctor['id'] ?>"><?= sanitize($doctor['full_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="mb-3">
                <label style="font-weight: 600; color: #333; margin-bottom: 10px; display: block;">Your Rating *</label>
                <div style="display: flex; flex-direction: column; gap: 10px;">
                    <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; padding: 10px 15px; border: 2px solid #ddd; border-radius: 8px; transition: all 0.3s;">
                        <input type="radio" name="rating" value="5" required style="cursor: pointer;">
                        <span style="font-size: 1.3rem;">⭐⭐⭐⭐⭐ Excellent</span>
                    </label>
                    <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; padding: 10px 15px; border: 2px solid #ddd; border-radius: 8px; transition: all 0.3s;">
                        <input type="radio" name="rating" value="4" required style="cursor: pointer;">
                        <span style="font-size: 1.3rem;">⭐⭐⭐⭐ Good</span>
                    </label>
                    <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; padding: 10px 15px; border: 2px solid #ddd; border-radius: 8px; transition: all 0.3s;">
                        <input type="radio" name="rating" value="3" required style="cursor: pointer;">
                        <span style="font-size: 1.3rem;">⭐⭐⭐ Average</span>
                    </label>
                    <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; padding: 10px 15px; border: 2px solid #ddd; border-radius: 8px; transition: all 0.3s;">
                        <input type="radio" name="rating" value="2" required style="cursor: pointer;">
                        <span style="font-size: 1.3rem;">⭐⭐ Poor</span>
                    </label>
                    <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; padding: 10px 15px; border: 2px solid #ddd; border-radius: 8px; transition: all 0.3s;">
                        <input type="radio" name="rating" value="1" required style="cursor: pointer;">
                        <span style="font-size: 1.3rem;">⭐ Very Poor</span>
                    </label>
                </div>
            </div>

            <div class="mb-3">
                <label style="font-weight: 600; color: #333; margin-bottom: 10px; display: block;">Category *</label>
                <select name="category" style="width: 100%; padding: 12px 15px; border: 2px solid #ddd; border-radius: 8px; font-size: 1rem; color: #333; background: white; cursor: pointer;" required>
                    <option value="Overall Experience">Overall Experience</option>
                    <option value="Professionalism">Professionalism</option>
                    <option value="Communication">Communication Skills</option>
                    <option value="Treatment Quality">Treatment Quality</option>
                    <option value="Punctuality">Punctuality</option>
                    <option value="Attentiveness">Attentiveness</option>
                    <option value="Empathy">Empathy & Compassion</option>
                </select>
            </div>

            <div class="mb-4">
                <label style="font-weight: 600; color: #333; margin-bottom: 10px; display: block;">Your Feedback (Optional)</label>
                <textarea name="review" style="width: 100%; padding: 12px 15px; border: 2px solid #ddd; border-radius: 8px; font-size: 1rem; color: #333; background: white; font-family: Arial, sans-serif; resize: vertical;" rows="4" placeholder="Tell us about the doctor's performance..."></textarea>
            </div>

            <div style="display: flex; gap: 10px;">
                <button type="submit" name="add_rating" value="1" class="btn btn-success btn-lg" style="flex: 1;">
                    <i class="bi bi-check-circle-fill"></i> Submit Rating
                </button>
                <a href="ratings.php" class="btn btn-secondary btn-lg" style="flex: 1; text-decoration: none;">
                    <i class="bi bi-x-circle-fill"></i> Cancel
                </a>
            </div>
        </form>
    </div>
<?php endif; ?>

<div class="card shadow p-4">
    <h5 style="font-size: 1.5rem; margin-bottom: 20px;"><i class="bi bi-star-fill" style="color: #ffc107;"></i> My Doctor Ratings</h5>
    <div class="table-responsive">
        <table class="table table-sm table-hover">
            <thead>
            <tr>
                <th style="font-weight: 700;">Doctor</th>
                <th style="font-weight: 700;">Rating</th>
                <th style="font-weight: 700;">Category</th>
                <th style="font-weight: 700;">Feedback</th>
                <th style="font-weight: 700;">Date</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($my_ratings as $rating): ?>
                <tr>
                    <td><strong><?= sanitize($rating['doctor_name']) ?></strong></td>
                    <td>
                        <span style="color: #ffc107; font-size: 1.1rem;">
                            <?php for ($i = 0; $i < $rating['rating']; $i++): ?>
                                ⭐
                            <?php endfor; ?>
                        </span>
                        <span style="color: #999; margin-left: 5px;"><?= $rating['rating'] ?>/5</span>
                    </td>
                    <td><span class="badge" style="background: #4c6ef5; color: white;"><?= sanitize($rating['category']) ?></span></td>
                    <td>
                        <?php if ($rating['review']): ?>
                            <small style="color: #666;"><?= substr(sanitize($rating['review']), 0, 30) ?>...</small>
                        <?php else: ?>
                            <span style="color: #999;">-</span>
                        <?php endif; ?>
                    </td>
                    <td><?= date('M d, Y', strtotime($rating['created_at'])) ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($my_ratings)): ?>
                <tr><td colspan="5" class="text-muted text-center py-4"><em>You haven't rated any doctors yet.</em></td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include '../inc/footer.php'; ?>
