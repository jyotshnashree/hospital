<?php
/**
 * MySQL Helper Functions
 * Hospital Management System
 */

require_once __DIR__ . '/config.php';

/**
 * Execute a SELECT query and return results
 *
 * @param string $query SQL query
 * @param array $params Parameters for prepared statement
 * @return array Results
 */
function queryFetch($query, $params = [])
{
    try {
        $pdo = getPDO();
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        logError("Query Error: " . $e->getMessage());
        return [];
    }
}

/**
 * Execute a SELECT query and return single row
 *
 * @param string $query SQL query
 * @param array $params Parameters for prepared statement
 * @return array|false Single row or false
 */
function queryFetchOne($query, $params = [])
{
    try {
        $pdo = getPDO();
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        logError("Query Error: " . $e->getMessage());
        return false;
    }
}

/**
 * Execute INSERT, UPDATE, or DELETE query
 *
 * @param string $query SQL query
 * @param array $params Parameters for prepared statement
 * @return bool Success status
 */
function queryExecute($query, $params = [])
{
    try {
        $pdo = getPDO();
        $stmt = $pdo->prepare($query);
        return $stmt->execute($params);
    } catch (PDOException $e) {
        logError("Execute Error: " . $e->getMessage());
        return false;
    }
}

/**
 * Get last inserted ID
 *
 * @return string Last insert ID
 */
function getLastInsertId()
{
    try {
        $pdo = getPDO();
        return $pdo->lastInsertId();
    } catch (PDOException $e) {
        logError("LastInsertId Error: " . $e->getMessage());
        return 0;
    }
}

/**
 * Insert record into table
 *
 * @param string $table Table name
 * @param array $data Column => Value pairs
 * @return bool|int Success or Last Insert ID
 */
function insertRecord($table, $data)
{
    try {
        $columns = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));
        $query = "INSERT INTO `$table` ($columns) VALUES ($placeholders)";

        queryExecute($query, array_values($data));
        return getLastInsertId();
    } catch (Exception $e) {
        logError("Insert Error: " . $e->getMessage());
        return false;
    }
}

/**
 * Update record in table
 *
 * @param string $table Table name
 * @param array $data Column => Value pairs
 * @param array $where WHERE conditions
 * @return bool Success status
 */
function updateRecord($table, $data, $where)
{
    try {
        $setClauses = [];
        $params = [];

        foreach ($data as $column => $value) {
            $setClauses[] = "`$column` = ?";
            $params[] = $value;
        }

        $whereClause = [];
        foreach ($where as $column => $value) {
            $whereClause[] = "`$column` = ?";
            $params[] = $value;
        }

        $query = "UPDATE `$table` SET " . implode(', ', $setClauses) . " WHERE " . implode(' AND ', $whereClause);
        return queryExecute($query, $params);
    } catch (Exception $e) {
        logError("Update Error: " . $e->getMessage());
        return false;
    }
}

/**
 * Delete record from table
 *
 * @param string $table Table name
 * @param array $where WHERE conditions
 * @return bool Success status
 */
function deleteRecord($table, $where)
{
    try {
        $whereClause = [];
        $params = [];

        foreach ($where as $column => $value) {
            $whereClause[] = "`$column` = ?";
            $params[] = $value;
        }

        $query = "DELETE FROM `$table` WHERE " . implode(' AND ', $whereClause);
        return queryExecute($query, $params);
    } catch (Exception $e) {
        logError("Delete Error: " . $e->getMessage());
        return false;
    }
}

/**
 * Get record count
 *
 * @param string $table Table name
 * @param array $where WHERE conditions (optional)
 * @return int Count
 */
function getRecordCount($table, $where = [])
{
    try {
        $query = "SELECT COUNT(*) as count FROM `$table`";
        $params = [];

        if (!empty($where)) {
            $whereClauses = [];
            foreach ($where as $column => $value) {
                $whereClauses[] = "`$column` = ?";
                $params[] = $value;
            }
            $query .= " WHERE " . implode(' AND ', $whereClauses);
        }

        $result = queryFetchOne($query, $params);
        return $result['count'] ?? 0;
    } catch (Exception $e) {
        logError("Count Error: " . $e->getMessage());
        return 0;
    }
}

/**
 * Check if record exists
 *
 * @param string $table Table name
 * @param array $where WHERE conditions
 * @return bool Record exists
 */
function recordExists($table, $where)
{
    return getRecordCount($table, $where) > 0;
}

/**
 * Execute a transaction
 *
 * @param callable $callback Function to execute
 * @return bool Success status
 */
function transaction($callback)
{
    try {
        $pdo = getPDO();
        $pdo->beginTransaction();
        $result = call_user_func($callback, $pdo);
        if ($result) {
            $pdo->commit();
            return true;
        } else {
            $pdo->rollBack();
            return false;
        }
    } catch (Exception $e) {
        $pdo->rollBack();
        logError("Transaction Error: " . $e->getMessage());
        return false;
    }
}

/**
 * Log error to file
 *
 * @param string $message Error message
 */
function logError($message)
{
    $logFile = __DIR__ . '/../logs/mysql_errors.log';
    if (!is_dir(dirname($logFile))) {
        mkdir(dirname($logFile), 0755, true);
    }
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[$timestamp] $message\n", FILE_APPEND);
}

/**
 * Sanitize user input
 *
 * @param string $input User input
 * @return string Sanitized input
 */
function sanitizeInput($input)
{
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

/**
 * Get database info
 *
 * @return array Database information
 */
function getDatabaseInfo()
{
    try {
        $pdo = getPDO();
        $result = $pdo->query("
            SELECT 
                DATABASE() as db_name,
                VERSION() as mysql_version,
                USER() as current_user
        ")->fetch(PDO::FETCH_ASSOC);
        return $result;
    } catch (PDOException $e) {
        logError("Database Info Error: " . $e->getMessage());
        return [];
    }
}

/**
 * Get all tables in database
 *
 * @return array Table names
 */
function getAllTables()
{
    try {
        $pdo = getPDO();
        $pdo->exec("USE `" . DB_NAME . "`");
        $result = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
        return $result;
    } catch (PDOException $e) {
        logError("Get Tables Error: " . $e->getMessage());
        return [];
    }
}

?>
