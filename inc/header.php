<?php
if (!defined('APP_INIT')) {
    define('APP_INIT', true);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hospital Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../styles.css">
    <style>
        .logout-btn:hover {
            background: #ff5252 !important;
            transform: scale(1.05);
            box-shadow: 0 8px 20px rgba(255, 107, 107, 0.4);
        }
        .user-display:hover {
            background: rgba(255, 255, 255, 0.35) !important;
            transform: scale(1.02);
        }
    </style>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark shadow-lg" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 0 0 35px 35px; border-bottom: 5px solid rgba(255, 255, 255, 0.3);">
    <div class="container-fluid">
        <a class="navbar-brand" href="../dashboard.php" style="font-weight: 900; font-size: 1.8rem; font-family: 'Poppins', sans-serif; animation: bounce 3s infinite;">
            🏥 HMS
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" style="border: 2px solid white; animation: wiggle 0.5s;">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <?php if (!empty($_SESSION['user_id'])): ?>
                    <li class="nav-item">
                        <span class="nav-link user-display" style="font-weight: 700; font-size: 1.05rem; background: rgba(255, 255, 255, 0.25); padding: 8px 16px !important; border-radius: 25px; margin-right: 10px; border: 2px solid rgba(255, 255, 255, 0.5); cursor: pointer; transition: all 0.3s ease;">
                            <i class="bi bi-person-circle"></i> <?= sanitize($_SESSION['name']) ?>
                        </span>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link logout-btn" href="../logout.php" style="font-weight: 700; background: #ff6b6b; padding: 8px 18px !important; border-radius: 25px; color: white !important; transition: all 0.3s ease;">
                            <i class="bi bi-box-arrow-right"></i> Logout
                        </a>
                    </li>
                <?php else: ?>
                    <li class="nav-item"><a class="nav-link" href="../login.php" style="font-weight: 700;">🔐 Login</a></li>
                    <li class="nav-item"><a class="nav-link" href="../register.php" style="font-weight: 700;">📝 Register</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>
<div class="container py-4" style="position: relative; z-index: 1;">
