<?php
include 'db.php';
checkAuth();
$role = getUserRole();

if ($role === 'admin') {
    header('Location: admin/dashboard.php');
    exit;
}
if ($role === 'doctor') {
    header('Location: doctor/dashboard.php');
    exit;
}
if ($role === 'patient') {
    header('Location: patient/dashboard.php');
    exit;
}

// fallback
header('Location: login.php');
exit;
