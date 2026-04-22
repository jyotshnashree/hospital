<?php
/**
 * Patient - Browse Doctors
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include '../db.php'; // Include only ONCE

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'patient') {
    header('Location: ../login.php?role=patient');
    exit;
}

$patientId      = $_SESSION['user_id'];
$specialtyFilter = $_GET['specialty'] ?? '';
$searchQuery    = trim($_GET['search'] ?? '');

// Build doctor query
$query  = 'SELECT * FROM users WHERE role = "doctor" AND is_active = TRUE';
$params = [];

if ($specialtyFilter) {
    $query   .= ' AND specialty = ?';
    $params[] = $specialtyFilter;
}

if ($searchQuery) {
    $query   .= ' AND (full_name LIKE ? OR specialty LIKE ?)';
    $params[] = '%' . $searchQuery . '%';
    $params[] = '%' . $searchQuery . '%';
}

$query .= ' ORDER BY full_name ASC';

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$doctors = $stmt->fetchAll();

// Get unique specialties for filter buttons
$specialtiesStmt = $pdo->query(
    'SELECT DISTINCT specialty FROM users WHERE role = "doctor" AND is_active = TRUE ORDER BY specialty'
);
$specialties = $specialtiesStmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Find Doctors - Patient Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        body { background: #f5f7fa; }

        /* ── Header ── */
        .header {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            padding: 20px 20px 30px;
        }
        .header-inner { max-width: 1200px; margin: 0 auto; }
        .header h1  { margin: 0; }
        .header p   { margin: 5px 0 0 0; opacity: .85; }

        /* ── Main container ── */
        .container-custom { max-width: 1200px; margin: 0 auto; padding: 30px 20px 50px; }

        /* ── Search bar ── */
        .search-bar {
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,.06);
            margin-bottom: 24px;
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            align-items: center;
        }
        .search-bar input {
            flex: 1;
            min-width: 200px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            padding: 10px 15px;
            font-size: .95rem;
        }
        .search-bar input:focus {
            outline: none;
            border-color: #10b981;
            box-shadow: 0 0 0 3px rgba(16,185,129,.12);
        }
        .search-bar button {
            background: #10b981;
            color: white;
            border: none;
            border-radius: 8px;
            padding: 10px 20px;
            font-weight: 600;
            cursor: pointer;
        }
        .search-bar button:hover { background: #059669; }

        /* ── Specialty filter pills ── */
        .filter-pills { margin-bottom: 24px; display: flex; flex-wrap: wrap; gap: 8px; }
        .filter-pills a {
            padding: 6px 16px;
            border-radius: 20px;
            font-size: .85rem;
            font-weight: 600;
            text-decoration: none;
            border: 2px solid #10b981;
            color: #10b981;
            transition: all .2s;
        }
        .filter-pills a:hover,
        .filter-pills a.active {
            background: #10b981;
            color: white;
        }

        /* ── Doctor cards grid ── */
        .doctors-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 22px;
        }

        /* ── Single doctor card ── */
        .doctor-card {
            background: white;
            border-radius: 12px;
            padding: 22px;
            box-shadow: 0 2px 10px rgba(0,0,0,.06);
            border-left: 5px solid #10b981;
            transition: transform .25s, box-shadow .25s;
            display: flex;
            flex-direction: column;
        }
        .doctor-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 24px rgba(0,0,0,.1);
        }

        .doctor-avatar {
            width: 56px;
            height: 56px;
            border-radius: 50%;
            background: linear-gradient(135deg, #10b981, #059669);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: white;
            margin-bottom: 14px;
            flex-shrink: 0;
        }
        .doctor-name {
            font-size: 1.15rem;
            font-weight: 700;
            color: #1a1a2e;
            margin-bottom: 4px;
        }
        .doctor-specialty {
            display: inline-block;
            background: #f0fdf4;
            color: #059669;
            font-weight: 600;
            font-size: .82rem;
            padding: 3px 10px;
            border-radius: 12px;
            margin-bottom: 14px;
        }

        .doctor-meta { border-top: 1px solid #f0f0f0; padding-top: 14px; margin-bottom: 14px; }
        .doctor-meta-item {
            font-size: .88rem;
            color: #555;
            margin-bottom: 6px;
            display: flex;
            align-items: flex-start;
            gap: 8px;
        }
        .doctor-meta-item i { color: #10b981; margin-top: 2px; flex-shrink: 0; }

        .doctor-bio {
            font-size: .85rem;
            color: #666;
            font-style: italic;
            margin-bottom: 14px;
            flex: 1;
        }

        /* Card action buttons */
        .card-actions { display: flex; gap: 8px; margin-top: auto; }
        .btn-book-appt {
            flex: 1;
            background: #10b981;
            color: white;
            padding: 9px 14px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            font-size: .9rem;
            text-align: center;
            transition: background .2s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
        }
        .btn-book-appt:hover { background: #059669; color: white; }

        .btn-msg {
            padding: 9px 14px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            font-size: .9rem;
            border: 2px solid #6366f1;
            color: #6366f1;
            text-align: center;
            transition: all .2s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
        }
        .btn-msg:hover { background: #6366f1; color: white; }

        /* ── Empty state ── */
        .empty-state { text-align: center; padding: 70px 20px; }
        .empty-state i { font-size: 3.5rem; color: #ccc; display: block; margin-bottom: 16px; }
        .empty-state h3 { color: #444; }
        .empty-state p  { color: #888; }

        /* ── Results count ── */
        .results-count { font-size: .9rem; color: #666; margin-bottom: 18px; }
        .results-count span { font-weight: 700; color: #10b981; }
    </style>
</head>
<body>

<!-- ══ Header ══════════════════════════════════════════════════════════════ -->
<div class="header">
    <div class="header-inner">
        <div style="display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:10px;">
            <div>
                <h1><i class="bi bi-search"></i> Find a Doctor</h1>
                <p>Browse our network of qualified healthcare professionals</p>
            </div>
            <a href="dashboard.php"
               style="color:white; border:2px solid rgba(255,255,255,.6); padding:8px 18px;
                      border-radius:8px; text-decoration:none; font-weight:600; font-size:.9rem;">
                <i class="bi bi-arrow-left me-1"></i> Dashboard
            </a>
        </div>
    </div>
</div>

<!-- ══ Main content ══════════════════════════════════════════════════════════ -->
<div class="container-custom">

    <!-- Search bar -->
    <div class="search-bar">
        <form method="GET" style="display:contents;">
            <?php if ($specialtyFilter): ?>
                <input type="hidden" name="specialty" value="<?= htmlspecialchars($specialtyFilter) ?>">
            <?php endif; ?>
            <input type="text" name="search" placeholder="🔍  Search by name or specialty…"
                   value="<?= htmlspecialchars($searchQuery) ?>">
            <button type="submit"><i class="bi bi-search me-1"></i> Search</button>
            <?php if ($searchQuery || $specialtyFilter): ?>
                <a href="doctors.php"
                   style="padding:10px 16px; border-radius:8px; border:2px solid #e0e0e0;
                          color:#666; text-decoration:none; font-weight:600;">
                    Clear
                </a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Specialty filter pills -->
    <div class="filter-pills">
        <a href="doctors.php<?= $searchQuery ? '?search='.urlencode($searchQuery) : '' ?>"
           class="<?= $specialtyFilter === '' ? 'active' : '' ?>">
            All Specialties
        </a>
        <?php foreach ($specialties as $spec): ?>
            <?php
                $href = '?specialty=' . urlencode($spec['specialty']);
                if ($searchQuery) $href .= '&search=' . urlencode($searchQuery);
            ?>
            <a href="<?= $href ?>"
               class="<?= $specialtyFilter === $spec['specialty'] ? 'active' : '' ?>">
                <?= htmlspecialchars($spec['specialty']) ?>
            </a>
        <?php endforeach; ?>
    </div>

    <!-- Results count -->
    <?php $total = count($doctors); ?>
    <p class="results-count">
        Showing <span><?= $total ?></span> doctor<?= $total !== 1 ? 's' : '' ?>
        <?= $specialtyFilter ? ' in <strong>' . htmlspecialchars($specialtyFilter) . '</strong>' : '' ?>
        <?= $searchQuery     ? ' matching "<strong>' . htmlspecialchars($searchQuery) . '</strong>"' : '' ?>
    </p>

    <!-- Doctors grid -->
    <?php if ($total > 0): ?>
        <div class="doctors-grid">
            <?php foreach ($doctors as $doc): ?>
                <div class="doctor-card">

                    <!-- Avatar -->
                    <div class="doctor-avatar">
                        <i class="bi bi-person-fill"></i>
                    </div>

                    <!-- Name & specialty badge -->
                    <div class="doctor-name">Dr. <?= htmlspecialchars($doc['full_name']) ?></div>
                    <div class="doctor-specialty">
                        <?= htmlspecialchars($doc['specialty'] ?? 'General Practitioner') ?>
                    </div>

                    <!-- Meta details -->
                    <div class="doctor-meta">
                        <?php if (!empty($doc['phone'])): ?>
                            <div class="doctor-meta-item">
                                <i class="bi bi-telephone-fill"></i>
                                <?= htmlspecialchars($doc['phone']) ?>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($doc['years_of_experience'])): ?>
                            <div class="doctor-meta-item">
                                <i class="bi bi-award-fill"></i>
                                <?= (int)$doc['years_of_experience'] ?> years experience
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($doc['consultation_fee'])): ?>
                            <div class="doctor-meta-item">
                                <i class="bi bi-cash-coin"></i>
                                Consultation fee: $<?= number_format($doc['consultation_fee'], 2) ?>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($doc['email'])): ?>
                            <div class="doctor-meta-item">
                                <i class="bi bi-envelope-fill"></i>
                                <?= htmlspecialchars($doc['email']) ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Bio excerpt -->
                    <?php if (!empty($doc['bio'])): ?>
                        <div class="doctor-bio">
                            "<?= htmlspecialchars(mb_substr($doc['bio'], 0, 110)) ?><?= mb_strlen($doc['bio']) > 110 ? '…' : '' ?>"
                        </div>
                    <?php endif; ?>

                    <!-- Action buttons — FIXED hrefs -->
                    <div class="card-actions">
                        <a href="book_appointment.php?doctor_id=<?= (int)$doc['id'] ?>"
                           class="btn-book-appt">
                            <i class="bi bi-calendar-plus-fill"></i> Book Appointment
                        </a>
                        <a href="messages.php?doctor_id=<?= (int)$doc['id'] ?>"
                           class="btn-msg">
                            <i class="bi bi-chat-dots-fill"></i> Message
                        </a>
                    </div>

                </div><!-- /.doctor-card -->
            <?php endforeach; ?>
        </div><!-- /.doctors-grid -->

    <?php else: ?>
        <div class="empty-state">
            <i class="bi bi-search"></i>
            <h3>No doctors found</h3>
            <p>
                <?php if ($searchQuery || $specialtyFilter): ?>
                    Try adjusting your search or filter.
                <?php else: ?>
                    No doctors are currently available.
                <?php endif; ?>
            </p>
            <a href="doctors.php" class="btn btn-success mt-3">
                <i class="bi bi-arrow-clockwise me-1"></i> View All Doctors
            </a>
        </div>
    <?php endif; ?>

</div><!-- /.container-custom -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>