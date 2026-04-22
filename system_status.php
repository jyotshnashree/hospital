<?php
/**
 * Hospital Management System - Feature Verification & Setup
 * Check if all new modules are properly installed
 */

include 'db.php';

$checks = [
    'Database Connection' => false,
    'Users Table' => false,
    'Appointments Table' => false,
    'Notifications Table' => false,
    'Payments Table' => false,
    'Bills Table' => false,
    'Prescriptions Table' => false,
    'Admin User' => false,
];

try {
    // Test connection
    $pdo->query("SELECT 1");
    $checks['Database Connection'] = true;
    
    // Check tables
    $tables = $pdo->query("SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA='hospital'")->fetchAll();
    $table_names = array_column($tables, 'TABLE_NAME');
    
    $checks['Users Table'] = in_array('users', $table_names);
    $checks['Appointments Table'] = in_array('appointments', $table_names);
    $checks['Notifications Table'] = in_array('notifications', $table_names);
    $checks['Payments Table'] = in_array('payments', $table_names);
    $checks['Bills Table'] = in_array('bills', $table_names);
    $checks['Prescriptions Table'] = in_array('prescriptions', $table_names);
    
    // Check admin user
    $admin = $pdo->query("SELECT COUNT(*) FROM users WHERE email='admin@hospital.com' AND role='admin'")->fetchColumn();
    $checks['Admin User'] = $admin > 0;
    
} catch (Exception $e) {
    // Database connection failed
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Status - Hospital Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; padding: 20px 0; }
        .container { max-width: 800px; }
        .check-item { display: flex; justify-content: space-between; padding: 15px; border-bottom: 1px solid #ddd; }
        .check-item:last-child { border-bottom: none; }
        .status-badge { font-weight: bold; padding: 5px 15px; border-radius: 20px; }
        .status-ok { background: #d4edda; color: #155724; }
        .status-error { background: #f8d7da; color: #721c24; }
    </style>
</head>
<body>
<div class="container mt-5">
    <div class="card shadow">
        <div class="card-header bg-primary text-white">
            <h3 class="mb-0">🏥 Hospital Management System - Status Check</h3>
        </div>
        <div class="card-body">
            <h5 class="mb-4">System Requirements Verification</h5>
            
            <?php foreach ($checks as $check => $status): ?>
                <div class="check-item">
                    <span><strong><?= $check ?></strong></span>
                    <span class="status-badge <?= $status ? 'status-ok' : 'status-error' ?>">
                        <?= $status ? '✓ OK' : '✗ MISSING' ?>
                    </span>
                </div>
            <?php endforeach; ?>
            
            <div class="mt-4 p-3 bg-light rounded">
                <h6>System Status: <?php
                    $all_ok = !in_array(false, $checks);
                    echo $all_ok ? '✅ <strong class="text-success">READY</strong>' : '⚠️ <strong class="text-danger">INCOMPLETE</strong>';
                ?></h6>
            </div>
        </div>
    </div>

    <div class="card shadow mt-4">
        <div class="card-header bg-success text-white">
            <h5 class="mb-0">✨ Available Features</h5>
        </div>
        <div class="list-group list-group-flush">
            <a href="admin/dashboard.php" class="list-group-item list-group-item-action">
                <strong>👨‍💼 Admin Dashboard</strong> - Complete hospital management
            </a>
            <a href="admin/analytics.php" class="list-group-item list-group-item-action">
                <strong>📊 Analytics Dashboard</strong> - Charts & real-time metrics
            </a>
            <a href="admin/symptom_checker.php" class="list-group-item list-group-item-action">
                <strong>🤖 AI Symptom Checker</strong> - Smart symptom analysis
            </a>
            <a href="admin/medical_records.php" class="list-group-item list-group-item-action">
                <strong>📋 Digital Medical Records</strong> - Complete patient history
            </a>
            <a href="admin/pharmacy.php" class="list-group-item list-group-item-action">
                <strong>💊 Pharmacy Module</strong> - Medication management
            </a>
            <a href="admin/payments.php" class="list-group-item list-group-item-action">
                <strong>💳 Online Payments</strong> - Secure payment processing
            </a>
            <a href="admin/notifications.php" class="list-group-item list-group-item-action">
                <strong>📬 Notifications</strong> - Email & SMS alerts
            </a>
            <a href="statistics.php" class="list-group-item list-group-item-action">
                <strong>📈 Public Statistics</strong> - Hospital overview
            </a>
        </div>
    </div>

    <div class="card shadow mt-4">
        <div class="card-header bg-info text-white">
            <h5 class="mb-0">📖 Documentation</h5>
        </div>
        <div class="card-body">
            <p><strong>Setup Guide:</strong> See <code>NEW_FEATURES_GUIDE.md</code> for detailed feature documentation.</p>
            <p><strong>Database Schema:</strong> See <code>mysql/extended_schema.sql</code> for new tables.</p>
            <p><strong>Quick Start:</strong></p>
            <ol>
                <li>Login with admin credentials (admin@hospital.com / admin123)</li>
                <li>Navigate through admin dashboard to access new features</li>
                <li>Check Analytics Dashboard for hospital metrics</li>
                <li>Try the AI Symptom Checker for patient triage</li>
            </ol>
        </div>
    </div>

    <div class="card shadow mt-4 mb-4">
        <div class="card-header bg-warning text-dark">
            <h5 class="mb-0">⚙️ Production Setup</h5>
        </div>
        <div class="card-body">
            <h6>For production deployment, configure:</h6>
            <ul>
                <li>🔐 <strong>SSL Certificate</strong> - For secure payments</li>
                <li>💳 <strong>Payment Gateway</strong> - Stripe, PayPal, or Razorpay</li>
                <li>📧 <strong>Email Service</strong> - AWS SES, SendGrid, or SMTP</li>
                <li>📱 <strong>SMS Service</strong> - Twilio or local provider</li>
                <li>📄 <strong>PDF Library</strong> - TCPDF or mPDF</li>
                <li>💾 <strong>Database Backup</strong> - Automated daily backups</li>
                <li>🔒 <strong>Security</strong> - WAF, DDoS protection</li>
            </ul>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
