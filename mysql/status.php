<?php
/**
 * MySQL Quick Test & Status Check
 * Hospital Management System
 */

require_once __DIR__ . '/config.php';

class MySQLStatus
{
    public static function checkConnection()
    {
        try {
            $pdo = getPDO();
            return [
                'status' => 'Connected ✓',
                'code' => 200
            ];
        } catch (PDOException $e) {
            return [
                'status' => 'Failed ✗',
                'error' => $e->getMessage(),
                'code' => 500
            ];
        }
    }

    public static function checkDatabase()
    {
        try {
            $pdo = getPDO();
            $result = $pdo->query("SELECT DATABASE() as db_name")->fetch(PDO::FETCH_ASSOC);
            return [
                'database' => $result['db_name'] ?? 'Unknown',
                'status' => 'Connected ✓',
                'code' => 200
            ];
        } catch (PDOException $e) {
            return [
                'status' => 'Failed ✗',
                'error' => $e->getMessage(),
                'code' => 500
            ];
        }
    }

    public static function checkTables()
    {
        try {
            $pdo = getPDO();
            $pdo->exec("USE `" . DB_NAME . "`");
            $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
            
            $expected = ['users', 'appointments', 'prescriptions', 'bills', 'payments', 'reports'];
            $found = array_intersect($expected, $tables);
            
            return [
                'total_tables' => count($tables),
                'tables' => $tables,
                'expected_found' => count($found),
                'status' => count($found) === count($expected) ? 'All tables found ✓' : 'Some tables missing ⚠',
                'code' => count($found) === count($expected) ? 200 : 206
            ];
        } catch (PDOException $e) {
            return [
                'status' => 'Failed ✗',
                'error' => $e->getMessage(),
                'code' => 500
            ];
        }
    }

    public static function checkUsers()
    {
        try {
            $pdo = getPDO();
            $pdo->exec("USE `" . DB_NAME . "`");
            $result = $pdo->query("SELECT COUNT(*) as count FROM users")->fetch(PDO::FETCH_ASSOC);
            $count = $result['count'] ?? 0;
            
            return [
                'user_count' => $count,
                'status' => $count > 0 ? 'Users found ✓' : 'No users found ⚠',
                'code' => $count > 0 ? 200 : 206
            ];
        } catch (PDOException $e) {
            return [
                'status' => 'Failed ✗',
                'error' => $e->getMessage(),
                'code' => 500
            ];
        }
    }

    public static function getFullReport()
    {
        return [
            'connection' => self::checkConnection(),
            'database' => self::checkDatabase(),
            'tables' => self::checkTables(),
            'users' => self::checkUsers(),
            'config' => [
                'host' => DB_HOST,
                'port' => DB_PORT,
                'database' => DB_NAME,
                'charset' => DB_CHARSET
            ]
        ];
    }
}

// Display report if accessed via web or CLI
if (php_sapi_name() === 'cli') {
    echo "\n========================\n";
    echo "MySQL Status Report\n";
    echo "========================\n\n";
    
    $report = MySQLStatus::getFullReport();
    
    echo "CONNECTION: " . $report['connection']['status'] . "\n";
    echo "DATABASE: " . $report['database']['status'] . " (" . $report['database']['database'] . ")\n";
    echo "TABLES: " . $report['tables']['status'] . " (" . $report['tables']['total_tables'] . " found)\n";
    echo "USERS: " . $report['users']['status'] . " (" . $report['users']['user_count'] . " found)\n\n";
    
    echo "Configuration:\n";
    echo "  Host: " . $report['config']['host'] . "\n";
    echo "  Port: " . $report['config']['port'] . "\n";
    echo "  Database: " . $report['config']['database'] . "\n";
    echo "  Charset: " . $report['config']['charset'] . "\n";
    echo "\n========================\n\n";
} else {
    // JSON response for web access
    header('Content-Type: application/json');
    echo json_encode(MySQLStatus::getFullReport(), JSON_PRETTY_PRINT);
}

?>
