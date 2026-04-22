<?php
/**
 * MySQL Database Configuration
 * Hospital Management System
 */

// Database Connection Parameters
define('DB_HOST', 'localhost');
define('DB_PORT', 3306);
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'hospital');

// Character Set
define('DB_CHARSET', 'utf8mb4');
define('DB_COLLATE', 'utf8mb4_unicode_ci');

// SSL Configuration (Optional)
define('DB_SSL', false);

// Connection Timeout
define('DB_TIMEOUT', 10);

/**
 * Establish MySQL Connection using PDO
 */
function getDBConnection()
{
    try {
        $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";charset=" . DB_CHARSET;
        
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_TIMEOUT => DB_TIMEOUT,
        ];
        
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        
        // Create database if it doesn't exist
        $pdo->exec("CREATE DATABASE IF NOT EXISTS " . DB_NAME . " CHARACTER SET " . DB_CHARSET . " COLLATE " . DB_COLLATE);
        
        // Select the database
        $pdo->exec("USE " . DB_NAME);
        
        return $pdo;
    } catch (PDOException $e) {
        die('Database Connection Error: ' . $e->getMessage());
    }
}

/**
 * Get Active Database Connection (Singleton Pattern)
 */
$pdo = null;

function getPDO()
{
    global $pdo;
    if ($pdo === null) {
        $pdo = getDBConnection();
    }
    return $pdo;
}
?>
