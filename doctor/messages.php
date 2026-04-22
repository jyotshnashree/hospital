<?php
/**
 * Doctor Messages - Patient communication
 */

include '../db.php';
checkAuth();
if (getUserRole() !== 'doctor') {
    header('Location: ../portals.php');
    exit;
}

$doctorId = $_SESSION['user_id'];

// Handle sending message
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_message'])) {
    $patientId = intval($_POST['patient_id'] ?? 0);
    $messageText = sanitize($_POST['message_text'] ?? '');

    if ($patientId > 0 && !empty($messageText)) {
        try {
            $stmt = $pdo->prepare('
                INSERT INTO messages (sender_id, receiver_id, message, is_read, created_at)
                VALUES (?, ?, ?, ?, NOW())
            ');
            $stmt->execute([$doctorId, $patientId, $messageText, 0]);
            $_SESSION['success'] = '✅ Message sent successfully!';
        } catch (Exception $e) {
            // Table might not exist yet
        }
    }
}

// Get patient list
try {
    $patients = $pdo->query('
        SELECT DISTINCT u.id, u.full_name, u.email, u.phone
        FROM users u
        WHERE u.role = "patient"
        ORDER BY u.full_name
    ')->fetchAll();
} catch (Exception $e) {
    $patients = [];
}

$selectedPatientId = $_GET['patient_id'] ?? ($patients[0]['id'] ?? null);

$messages = [];
if ($selectedPatientId) {
    $msgStmt = $pdo->prepare('
        SELECT * FROM messages 
        WHERE (sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?)
        ORDER BY created_at DESC
        LIMIT 50
    ');
    $msgStmt->execute([$doctorId, $selectedPatientId, $selectedPatientId, $doctorId]);
    $messages = array_reverse($msgStmt->fetchAll());
}
?>

<?php include '../inc/header.php'; ?>

<div style="margin-bottom: 2.5rem; animation: slideInUp 0.6s ease-out;">
    <div>
        <h1 style="font-size: 3rem; margin-bottom: 0.5rem;">
            <i class="bi bi-chat-dots" style="color: #667eea;"></i> Messages
        </h1>
        <p class="text-muted" style="font-weight: 500; font-size: 1.1rem;">💬 Communicate with your patients</p>
    </div>
</div>

<?php if (isset($_SESSION['success'])): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?= $_SESSION['success'] ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php unset($_SESSION['success']); ?>
<?php endif; ?>

<div class="row" style="height: 600px;">
    <!-- Patient List -->
    <div class="col-lg-3 mb-4">
        <div class="card shadow p-4" style="height: 100%; display: flex; flex-direction: column;">
            <h5 style="font-size: 1.2rem; margin-bottom: 15px;"><i class="bi bi-people"></i> Patients</h5>
            <div style="flex: 1; overflow-y: auto;">
                <?php if (!empty($patients)): ?>
                    <?php foreach ($patients as $patient): ?>
                        <a href="?patient_id=<?= $patient['id'] ?>" 
                           class="btn btn-outline-primary w-100 text-start mb-2" 
                           style="border-radius: 8px; padding: 12px; <?= $selectedPatientId == $patient['id'] ? 'background: #667eea; color: white; border-color: #667eea;' : '' ?>">
                            <i class="bi bi-person-circle"></i>
                            <div>
                                <strong><?= sanitize($patient['full_name']) ?></strong>
                                <br>
                                <small><?= sanitize($patient['email']) ?></small>
                            </div>
                        </a>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="text-muted text-center">No patients yet.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Chat Area -->
    <div class="col-lg-9 mb-4">
        <div class="card shadow p-4" style="height: 100%; display: flex; flex-direction: column;">
            <?php if ($selectedPatientId): ?>
                <!-- Messages -->
                <div style="flex: 1; overflow-y: auto; margin-bottom: 20px; border-bottom: 2px solid #e0e0e0; padding-bottom: 15px;">
                    <?php if (!empty($messages)): ?>
                        <?php foreach ($messages as $msg): ?>
                            <div style="margin-bottom: 15px; display: flex; <?= $msg['sender_id'] == $doctorId ? 'justify-content: flex-end;' : 'justify-content: flex-start;' ?>">
                                <div style="background: <?= $msg['sender_id'] == $doctorId ? '#667eea' : '#e0e0e0' ?>; color: <?= $msg['sender_id'] == $doctorId ? 'white' : 'black' ?>; padding: 12px 15px; border-radius: 8px; max-width: 60%;">
                                    <p style="margin: 0; word-wrap: break-word;"><?= sanitize($msg['message']) ?></p>
                                    <small style="opacity: 0.7; display: block; margin-top: 5px;">
                                        <?= date('M d, h:i A', strtotime($msg['created_at'])) ?>
                                    </small>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div style="text-align: center; padding: 40px; color: #999;">
                            <i class="bi bi-chat-left"></i> No messages yet. Start a conversation!
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Message Input -->
                <form method="POST">
                    <input type="hidden" name="patient_id" value="<?= $selectedPatientId ?>">
                    <div class="input-group">
                        <textarea name="message_text" class="form-control" placeholder="Type your message..." rows="2" style="border-radius: 8px; border: 2px solid #e0e0e0; resize: none;" required></textarea>
                        <button type="submit" name="send_message" class="btn btn-primary" style="border-radius: 8px; margin-left: 10px;">
                            <i class="bi bi-send"></i> Send
                        </button>
                    </div>
                </form>
            <?php else: ?>
                <div style="text-align: center; padding: 100px 20px;">
                    <i class="bi bi-inbox" style="font-size: 3rem; color: #ccc; display: block; margin-bottom: 15px;"></i>
                    <p style="color: #999; font-size: 1rem;">Select a patient to start messaging.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php
/**
 * Doctor-Patient Messaging System
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include '../db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'doctor') {
    header('Location: ../doctor_login.php');
    exit;
}

include '../db.php';

$doctorId = $_SESSION['user_id'];
$patientId = $_GET['patient_id'] ?? null;
$appointmentId = $_GET['appointment_id'] ?? null;

// Handle sending message
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_message'])) {
    $messageText = trim($_POST['message_text'] ?? '');
    $pId = $_POST['patient_id'] ?? null;

    if ($messageText && $pId) {
        try {
            $stmt = $pdo->prepare('
                INSERT INTO messages (sender_id, receiver_id, appointment_id, message_text, message_type, status)
                VALUES (?, ?, ?, ?, ?, ?)
            ');
            $stmt->execute([$doctorId, $pId, $appointmentId, $messageText, 'text', 'sent']);
        } catch (Exception $e) {
            // Table might not exist yet
        }
    }
}

// Get patient list for doctor
$patientStmt = $pdo->prepare('
    SELECT DISTINCT u.id, u.full_name, u.email, u.phone
    FROM appointments a
    JOIN users u ON a.patient_id = u.id
    WHERE u.role = "patient"
    ORDER BY u.full_name
');
$patientStmt->execute();
$patients = $patientStmt->fetchAll();

// Get selected patient details
if ($patientId) {
    $selectedPatientStmt = $pdo->prepare('SELECT * FROM users WHERE id = ? AND role = "patient"');
    $selectedPatientStmt->execute([$patientId]);
    $selectedPatient = $selectedPatientStmt->fetch();
}

// Get chat history
if ($patientId) {
    $chatStmt = $pdo->prepare('
        SELECT * FROM messages
        WHERE (sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?)
        ORDER BY created_at ASC
        LIMIT 100
    ');
    $chatStmt->execute([$doctorId, $patientId, $patientId, $doctorId]);
    $chatHistory = $chatStmt->fetchAll();

    // Mark messages as read
    $readStmt = $pdo->prepare('
        UPDATE messages SET is_read = TRUE, read_at = NOW()
        WHERE receiver_id = ? AND sender_id = ? AND is_read = FALSE
    ');
    $readStmt->execute([$doctorId, $patientId]);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messages - Doctor Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        body { background: #f5f7fa; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        
        .container-custom {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
            height: calc(100vh - 80px);
            display: flex;
        }

        .patients-list {
            width: 30%;
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            overflow-y: auto;
            margin-right: 20px;
        }

        .chat-area {
            width: 70%;
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            display: flex;
            flex-direction: column;
        }

        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
        }

        .patient-item {
            padding: 15px;
            border-bottom: 1px solid #e0e0e0;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .patient-item:hover {
            background: #f5f5f5;
        }

        .patient-item.active {
            background: #667eea;
            color: white;
            border-bottom-color: #667eea;
        }

        .chat-header {
            padding: 20px;
            border-bottom: 1px solid #e0e0e0;
            background: #f9f9f9;
        }

        .chat-messages {
            flex: 1;
            overflow-y: auto;
            padding: 20px;
            background: #fff;
        }

        .message {
            margin-bottom: 15px;
            display: flex;
            animation: slideIn 0.3s ease;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .message.doctor {
            justify-content: flex-end;
        }

        .message-content {
            background: #e8f0fe;
            padding: 12px 15px;
            border-radius: 15px;
            max-width: 70%;
            word-wrap: break-word;
        }

        .message.doctor .message-content {
            background: #667eea;
            color: white;
        }

        .message-time {
            font-size: 0.8rem;
            color: #999;
            margin-top: 5px;
        }

        .chat-input-area {
            padding: 20px;
            border-top: 1px solid #e0e0e0;
            background: #f9f9f9;
        }

        .input-group {
            gap: 10px;
        }

        .chat-input {
            flex: 1;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 20px;
            font-size: 0.95rem;
            transition: border-color 0.3s ease;
        }

        .chat-input:focus {
            outline: none;
            border-color: #667eea;
        }

        .send-btn {
            background: #667eea;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 20px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .send-btn:hover {
            background: #5568d3;
        }

        .empty-state {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100%;
            color: #999;
        }

        @media (max-width: 768px) {
            .container-custom {
                flex-direction: column;
            }

            .patients-list {
                width: 100%;
                margin-right: 0;
                margin-bottom: 20px;
                max-height: 200px;
            }

            .chat-area {
                width: 100%;
            }

            .message-content {
                max-width: 90%;
            }
        }
    </style>
</head>
<body>
<div class="header">
    <div style="max-width: 1400px; margin: 0 auto;">
        <h1 style="margin: 0;"><i class="bi bi-chat-dots"></i> Messages</h1>
        <p style="margin: 5px 0 0 0;">Chat with your patients</p>
    </div>
</div>

<div class="container-custom">
    <!-- Patients List -->
    <div class="patients-list">
        <div style="padding: 15px; border-bottom: 1px solid #e0e0e0; background: #f9f9f9;">
            <h6 style="margin: 0;">My Patients</h6>
        </div>
        <?php if (count($patients) > 0): ?>
            <?php foreach ($patients as $p): ?>
                <div class="patient-item <?= $p['id'] == $patientId ? 'active' : '' ?>" 
                     onclick="location.href='?patient_id=<?= $p['id'] ?>'">
                    <strong><?= htmlspecialchars($p['full_name']) ?></strong>
                    <div style="font-size: 0.85rem; opacity: 0.8;">📞 <?= htmlspecialchars($p['phone']) ?></div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="patient-item" style="cursor: default;">
                <p style="margin: 0; color: #999;">No patients yet</p>
            </div>
        <?php endif; ?>
    </div>

    <!-- Chat Area -->
    <div class="chat-area">
        <?php if ($patientId && isset($selectedPatient)): ?>
            <div class="chat-header">
                <h5 style="margin: 0;">👤 <?= htmlspecialchars($selectedPatient['full_name']) ?></h5>
                <small style="color: #666;">📞 <?= htmlspecialchars($selectedPatient['phone']) ?></small>
            </div>

            <div class="chat-messages" id="chatMessages">
                <?php if (!empty($chatHistory)): ?>
                    <?php foreach ($chatHistory as $msg): ?>
                        <div class="message <?= $msg['sender_id'] == $doctorId ? 'doctor' : 'patient' ?>">
                            <div>
                                <div class="message-content">
                                    <?= htmlspecialchars($msg['message_text']) ?>
                                </div>
                                <div class="message-time">
                                    <?= date('h:i A', strtotime($msg['created_at'])) ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="bi bi-chat-left" style="font-size: 3rem; margin-bottom: 10px; opacity: 0.3;"></i>
                        <p>No messages yet. Start the conversation!</p>
                    </div>
                <?php endif; ?>
            </div>

            <div class="chat-input-area">
                <form method="POST" onsubmit="scrollToBottom()">
                    <input type="hidden" name="patient_id" value="<?= $patientId ?>">
                    <div class="input-group">
                        <input type="text" name="message_text" class="chat-input" placeholder="Type your message..." required autofocus>
                        <button type="submit" name="send_message" class="send-btn">
                            <i class="bi bi-send"></i> Send
                        </button>
                    </div>
                </form>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <i class="bi bi-chat-left" style="font-size: 3rem; margin-bottom: 10px;"></i>
                <p>Select a patient to start messaging</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
    function scrollToBottom() {
        const chatMessages = document.getElementById('chatMessages');
        if (chatMessages) {
            chatMessages.scrollTop = chatMessages.scrollHeight;
        }
    }

    // Auto-scroll on page load
    window.addEventListener('load', scrollToBottom);

    // Auto-refresh messages every 3 seconds
    setInterval(() => {
        if (document.getElementById('chatMessages')) {
            location.reload();
        }
    }, 3000);
</script>
</body>
</html>
