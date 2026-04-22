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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_message'])) {
    $messageText = trim($_POST['message_text'] ?? '');
    $doctorId = $_POST['doctor_id'] ?? null;
    if ($messageText && $doctorId) {
        try {
            $stmt = $pdo->prepare('INSERT INTO messages (sender_id, receiver_id, message_text, message_type, status) VALUES (?, ?, ?, ?, ?)');
            $stmt->execute([$patientId, $doctorId, $messageText, 'text', 'sent']);
        } catch (Exception $e) {}
    }
}

$doctorId = $_GET['doctor_id'] ?? null;
$doctorStmt = $pdo->prepare('SELECT DISTINCT u.* FROM users u JOIN appointments a ON u.id = a.doctor_id WHERE a.patient_id = ? AND u.role = "doctor" ORDER BY u.full_name');
$doctorStmt->execute([$patientId]);
$doctors = $doctorStmt->fetchAll();

if ($doctorId) {
    $chatStmt = $pdo->prepare('SELECT * FROM messages WHERE (sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?) ORDER BY created_at ASC LIMIT 100');
    $chatStmt->execute([$patientId, $doctorId, $doctorId, $patientId]);
    $chatHistory = $chatStmt->fetchAll();
    $readStmt = $pdo->prepare('UPDATE messages SET is_read = TRUE WHERE receiver_id = ? AND sender_id = ? AND is_read = FALSE');
    $readStmt->execute([$patientId, $doctorId]);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messages</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        body { background: #f5f7fa; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .header { background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; padding: 20px; }
        .container-custom { max-width: 1400px; margin: 0 auto; padding: 20px; height: calc(100vh - 80px); display: flex; }
        .doctors-list { width: 30%; background: white; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); overflow-y: auto; margin-right: 20px; }
        .chat-area { width: 70%; background: white; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); display: flex; flex-direction: column; }
        .doctor-item { padding: 15px; border-bottom: 1px solid #e0e0e0; cursor: pointer; transition: all 0.3s ease; }
        .doctor-item:hover { background: #f5f5f5; }
        .doctor-item.active { background: #10b981; color: white; }
        .chat-messages { flex: 1; overflow-y: auto; padding: 20px; }
        .message { margin-bottom: 15px; display: flex; }
        .message.patient { justify-content: flex-end; }
        .message-content { background: #e8f0fe; padding: 12px 15px; border-radius: 15px; max-width: 70%; word-wrap: break-word; }
        .message.patient .message-content { background: #10b981; color: white; }
        .chat-input-area { padding: 20px; border-top: 1px solid #e0e0e0; background: #f9f9f9; }
        .input-group { gap: 10px; display: flex; }
        .chat-input { flex: 1; padding: 12px 15px; border: 2px solid #e0e0e0; border-radius: 20px; font-size: 0.95rem; }
        .chat-input:focus { outline: none; border-color: #10b981; }
        .empty-state { display: flex; flex-direction: column; align-items: center; justify-content: center; height: 100%; color: #999; }
        @media(max-width: 768px) { .container-custom { flex-direction: column; } .doctors-list { width: 100%; margin-right: 0; margin-bottom: 20px; max-height: 150px; } .chat-area { width: 100%; } }
    </style>
</head>
<body>
<div class="header">
    <div style="max-width: 1400px; margin: 0 auto;">
        <h1 style="margin: 0;"><i class="bi bi-chat-dots"></i> Messages</h1>
    </div>
</div>
<div class="container-custom">
<div class="doctors-list"><div style="padding:15px;border-bottom:1px solid #e0e0e0;background:#f9f9f9"><h6 style="margin:0">Your Doctors</h6></div>
<?php if(count($doctors)>0):foreach($doctors as $d):?><div class="doctor-item <?=$d['id']==$doctorId?'active':''?>" onclick="location.href='?doctor_id=<?=$d['id']?>'"><strong><?=htmlspecialchars($d['full_name'])?></strong><div style="font-size:0.85rem;opacity:0.8">Dr. <?=htmlspecialchars($d['specialty'])?></div></div><?php endforeach;else:?><div class="doctor-item" style="cursor:default"><p style="margin:0;color:#999">No doctors yet</p></div><?php endif;?></div>
<div class="chat-area"><?php if($doctorId && !empty($doctors)):?><div style="padding:20px;border-bottom:1px solid #e0e0e0;background:#f9f9f9"><h5 style="margin:0">Chat with Dr. <?=htmlspecialchars($doctorId)?></h5></div>
<div class="chat-messages" id="chatMessages"><?php if(!empty($chatHistory)):foreach($chatHistory as $m):?><div class="message <?=$m['sender_id']==$patientId?'patient':''?>"><div><div class="message-content"><?=htmlspecialchars($m['message_text'])?></div><div style="font-size:0.8rem;color:#999;margin-top:5px"><?=date('h:i A',strtotime($m['created_at']))?></div></div></div><?php endforeach;else:?><div class="empty-state"><i class="bi bi-chat-left" style="font-size:3rem;margin-bottom:10px;opacity:0.3"></i><p>No messages yet</p></div><?php endif;?></div>
<div class="chat-input-area"><form method="POST"><input type="hidden" name="doctor_id" value="<?=$doctorId?>"><div class="input-group"><input type="text" name="message_text" class="chat-input" placeholder="Type your message..." required autofocus><button type="submit" name="send_message" style="background:#10b981;color:white;border:none;padding:10px 20px;border-radius:20px;cursor:pointer">Send</button></div></form></div><?php else:?><div class="empty-state"><i class="bi bi-chat-left" style="font-size:3rem;margin-bottom:10px"></i><p>Select a doctor to start messaging</p></div><?php endif;?></div></div>
<script>setTimeout(()=>{if(document.getElementById('chatMessages')){location.reload()}},3000)</script>
</body></html>
