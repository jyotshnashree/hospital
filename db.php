<?php
/**
 * Database Configuration and Connection
 * FINAL FIXED VERSION (InfinityFree Safe)
 */

// Prevent multiple inclusions
if (defined('DB_INITIALIZED')) {
    return;
}
define('DB_INITIALIZED', true);

// Start Session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// DATABASE CONFIG - LOCAL DEVELOPMENT
define('DB_HOST', 'localhost');
define('DB_PORT', 3306);
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'hospital');
define('DB_CHARSET', 'utf8mb4');

// SECURITY HEADERS (safe)
if (!headers_sent()) {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
}

// =====================
// HELPER FUNCTIONS
// =====================

function sanitize($value) {
    return htmlspecialchars(trim($value), ENT_QUOTES, 'UTF-8');
}

function escape($value) {
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

// CSRF PROTECTION
function generateCSRFToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validateCSRFToken($token = null) {
    $token = $token ?? ($_POST['csrf_token'] ?? '');
    if (empty($token) || empty($_SESSION['csrf_token'])) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

function checkAuth() {
    if (empty($_SESSION['user_id'])) {
        header('Location: login.php');
        exit;
    }
}

function getUserRole() {
    return $_SESSION['role'] ?? '';
}

function flash($key, $message = null) {
    if ($message !== null) {
        $_SESSION['flash'][$key] = $message;
    } else {
        $msg = $_SESSION['flash'][$key] ?? '';
        unset($_SESSION['flash'][$key]);
        return $msg;
    }
}

// =====================
// DATABASE CONNECTION
// =====================

try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";port=" . DB_PORT . ";charset=" . DB_CHARSET;

    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

} catch (PDOException $e) {
    // DO NOT expose real error
    die('Database connection failed. Please try again later.');
}
?>