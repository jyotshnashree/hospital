<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include '../db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'patient') {
    header('Location: ../login.php?role=patient');
    exit;
}

$patientId = $_SESSION['user_id'];
$filter = $_GET['filter'] ?? 'upcoming';

$query = 'SELECT a.*, u.full_name as doctor_name, u.specialty, u.phone FROM appointments a 
          JOIN users u ON a.doctor_id = u.id 
          WHERE a.patient_id = ?';
$params = [$patientId];

if ($filter === 'upcoming') {
    $query .= ' AND a.appointment_date >= CURDATE() AND a.status != ?';
    $params[] = 'Cancelled';
} elseif ($filter === 'past') {
    $query .= ' AND a.appointment_date < CURDATE()';
} elseif ($filter === 'completed') {
    $query .= ' AND a.status = ?';
    $params[] = 'Completed';
}

$query .= ' ORDER BY a.appointment_date DESC';
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$appointments = $stmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_appointment'])) {
    $appointmentId = $_POST['appointment_id'] ?? null;
    if ($appointmentId) {
        $updateStmt = $pdo->prepare('UPDATE appointments SET status = ? WHERE id = ? AND patient_id = ?');
        $updateStmt->execute(['Cancelled', $appointmentId, $patientId]);
        header('Refresh: 1');
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Appointments</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        body { background: #f5f7fa; }
        .header { background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; padding: 20px; }
        .container-custom { max-width: 1000px; margin: 0 auto; padding: 30px 20px; }
        .appointment-card { background: white; padding: 20px; border-radius: 8px; margin-bottom: 15px; border-left: 5px solid #10b981; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .status-pending { background: #fef3c7; color: #92400e; }
        .status-approved { background: #dbeafe; color: #1e40af; }
        .status-completed { background: #d1fae5; color: #065f46; }
        .status-cancelled { background: #fee2e2; color: #991b1b; }
        .status-badge { display: inline-block; padding: 5px 12px; border-radius: 20px; font-size: 0.85rem; font-weight: 600; }
        .empty-state { text-align: center; padding: 40px; background: white; border-radius: 8px; }
        .action-btn { padding: 8px 15px; margin-right: 5px; margin-bottom: 0px; border: none; border-radius: 5px; cursor: pointer; font-size: 0.9rem; display: inline-block; white-space: nowrap; }
    </style>
</head>
<body>
<div class="header">
    <div style="max-width: 1000px; margin: 0 auto;">
        <h1 style="margin: 0;"><i class="bi bi-calendar-event"></i> My Appointments</h1>
    </div>
</div>

<div class="container-custom">
    <div style="margin-bottom: 20px;">
        <a href="?filter=upcoming" class="btn <?= $filter === 'upcoming' ? 'btn-primary' : 'btn-outline-primary' ?>">
            <i class="bi bi-calendar-range"></i> Upcoming
        </a>
        <a href="?filter=past" class="btn <?= $filter === 'past' ? 'btn-primary' : 'btn-outline-primary' ?>">
            <i class="bi bi-calendar-x"></i> Past
        </a>
        <a href="?filter=completed" class="btn <?= $filter === 'completed' ? 'btn-primary' : 'btn-outline-primary' ?>">
            <i class="bi bi-calendar-check"></i> Completed
        </a>
        <a href="dashboard.php" class="btn btn-secondary"><i class="bi bi-arrow-left"></i> Back</a>
    </div>

    <?php if (count($appointments) > 0): ?>
        <?php foreach ($appointments as $a): ?>
        <div class="appointment-card">
            <div class="row">
                <div class="col-md-9">
                    <h5 style="margin: 0 0 10px 0;"><i class="bi bi-stethoscope" style="color: #10b981;"></i> Dr. <?= htmlspecialchars($a['doctor_name']) ?></h5>
                    <p style="margin: 5px 0; color: #666;"><strong><i class="bi bi-hospital"></i> Specialty:</strong> <?= htmlspecialchars($a['specialty'] ?? 'General') ?></p>
                    <p style="margin: 5px 0; color: #666;"><strong><i class="bi bi-calendar-event"></i> Date:</strong> <?= date('M d, Y', strtotime($a['appointment_date'])) ?> at <?= date('h:i A', strtotime($a['appointment_time'])) ?></p>
                    <p style="margin: 5px 0; color: #666;"><strong><i class="bi bi-telephone"></i> Contact:</strong> <?= htmlspecialchars($a['phone'] ?? 'N/A') ?></p>
                    <?php if ($a['reason']): ?>
                        <p style="margin: 5px 0; color: #666;"><strong><i class="bi bi-chat-left-text"></i> Reason:</strong> <?= htmlspecialchars($a['reason']) ?></p>
                    <?php endif; ?>
                    <p style="margin: 5px 0; color: #666;"><strong><i class="bi bi-info-circle"></i> Type:</strong> <span style="background: #f0f0f0; padding: 3px 8px; border-radius: 3px;"><?= ucfirst($a['consultation_type'] ?? 'Unknown') ?></span></p>
                </div>
                <div class="col-md-3" style="text-align: right;">
                    <span class="status-badge status-<?= strtolower($a['status']) ?>">
                        <?= htmlspecialchars($a['status']) ?>
                    </span>
                    <div style="margin-top: 12px; display: flex; flex-direction: column; gap: 8px; align-items: flex-end;">
                        <div style="display: flex; gap: 8px; flex-wrap: wrap; justify-content: flex-end;">
                            <a href="messages.php?doctor_id=<?= $a['doctor_id'] ?>" class="action-btn" style="background: #667eea; color: white;"><i class="bi bi-chat-dots"></i> Chat</a>
                            <a href="prescriptions.php" class="action-btn" style="background: #10b981; color: white;"><i class="bi bi-file-text"></i> Prescription</a>
                            <?php if (strtotime($a['appointment_date'] . ' ' . $a['appointment_time']) > time() && $a['status'] !== 'Cancelled'): ?>
                                <form method="POST" style="display: inline;" onsubmit="return confirm('Cancel this appointment?');">
                                    <input type="hidden" name="appointment_id" value="<?= $a['id'] ?>">
                                    <button type="submit" name="cancel_appointment" class="action-btn" style="background: #ef4444; color: white;"><i class="bi bi-x-circle"></i> Cancel</button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="empty-state">
            <i class="bi bi-calendar-event" style="font-size: 3rem; color: #ddd; display: block; margin-bottom: 10px;"></i>
            <p style="color: #666;">No appointments <?= $filter !== 'upcoming' ? $filter : '' ?>.</p>
            <a href="doctors.php" class="btn btn-primary">Book Appointment</a>
        </div>
    <?php endif; ?>
</div>
</body>
</html>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
<style>body{background:#f5f7fa}
.header{background:linear-gradient(135deg,#10b981 0%,#059669 100%);color:white;padding:20px}
.container-custom{max-width:1000px;margin:0 auto;padding:30px 20px}
.appointment-card{background:white;padding:20px;border-radius:8px;margin-bottom:15px;border-left:5px solid #10b981;box-shadow:0 2px 10px rgba(0,0,0,0.05)}
.status-pending{background:#fef3c7;color:#92400e}
.status-approved{background:#dbeafe;color:#1e40af}
.status-completed{background:#d1fae5;color:#065f46}
.status-cancelled{background:#fee2e2;color:#991b1b}
.status-badge{display:inline-block;padding:5px 12px;border-radius:20px;font-size:0.85rem;font-weight:600}
.empty-state{text-align:center;padding:40px;background:white;border-radius:8px}
.action-btn{padding:8px 15px;margin-right:5px;margin-bottom:5px;border:none;border-radius:5px;cursor:pointer;font-size:0.9rem}</style>
</head><body>
<div class="header"><div style="max-width:1000px;margin:0 auto"><h1 style="margin:0"><i class="bi bi-calendar-event"></i> My Appointments</h1></div></div>
<div class="container-custom">
<div style="margin-bottom:20px"><a href="?filter=upcoming" class="btn <?=$filter==='upcoming'?'btn-primary':'btn-outline-primary'?>">Upcoming</a>
<a href="?filter=past" class="btn <?=$filter==='past'?'btn-primary':'btn-outline-primary'?>">Past</a>
<a href="?filter=completed" class="btn <?=$filter==='completed'?'btn-primary':'btn-outline-primary'?>">Completed</a>
<a href="dashboard.php" class="btn btn-secondary">Back</a></div>
<?php if(count($appointments)>0):foreach($appointments as $a):?>
<div class="appointment-card"><div class="row"><div class="col-md-9"><h5 style="margin:0 0 10px 0">👨‍⚕️ Dr. <?=htmlspecialchars($a['doctor_name'])?></h5>
<p style="margin:5px 0;color:#666"><strong>Specialty:</strong> <?=htmlspecialchars($a['specialty']??'General')?></p>
<p style="margin:5px 0;color:#666"><strong>📅 Date:</strong> <?=date('M d, Y',strtotime($a['appointment_date']))?> at <?=date('h:i A',strtotime($a['appointment_time']))?></p>
<p style="margin:5px 0;color:#666"><strong>📞 Contact:</strong> <?=htmlspecialchars($a['phone']??'N/A')?></p>
<?php if($a['reason']):?><p style="margin:5px 0;color:#666"><strong>Reason:</strong> <?=htmlspecialchars($a['reason'])?></p><?php endif;?>
<p style="margin:5px 0;color:#666"><strong>Type:</strong> <span style="background:#f0f0f0;padding:3px 8px;border-radius:3px"><?=ucfirst($a['consultation_type']??'Unknown')?></span></p></div>
<div class="col-md-3" style="text-align:right"><span class="status-badge status-<?=strtolower($a['status'])?>">
<?=htmlspecialchars($a['status'])?></span><br><br>
<a href="messages.php?doctor_id=<?=$a['doctor_id']?>" class="action-btn" style="background:#667eea;color:white">💬 Chat</a>
<a href="prescriptions.php" class="action-btn" style="background:#10b981;color:white">📋 Prescription</a>
<?php if(strtotime($a['appointment_date'].' '.$a['appointment_time'])>time()&&$a['status']!='Cancelled'):?>
<form method="POST" style="display:inline" onsubmit="return confirm('Cancel this appointment?')"><input type="hidden" name="appointment_id" value="<?=$a['id']?>"><button type="submit" name="cancel_appointment" class="action-btn" style="background:#ef4444;color:white">✕ Cancel</button></form><?php endif;?></div></div></div><?php endforeach;else:?>
<div class="empty-state"><i class="bi bi-calendar-event" style="font-size:3rem;color:#ddd;display:block;margin-bottom:10px"></i>
<p style="color:#666">No appointments <?=$filter!=='upcoming'?$filter:''?>.</p>
<a href="doctors.php" class="btn btn-primary">Book Appointment</a></div><?php endif;?></div></body></html>
                <th>Date</th>
                <th>Time</th>
                <th>Status</th>
                <th>Reason</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($appointments as $appointment): ?>
                <tr>
                    <td><?= $appointment['id'] ?></td>
                    <td>Dr. <?= sanitize($appointment['doctor_name']) ?></td>
                    <td><?= sanitize($appointment['appointment_date']) ?></td>
                    <td><?= sanitize($appointment['appointment_time']) ?></td>
                    <td><?= sanitize($appointment['status']) ?></td>
                    <td><?= sanitize($appointment['reason']) ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($appointments)): ?>
                <tr><td colspan="6" class="text-muted">No appointments created yet.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php include '../inc/footer.php'; ?>
