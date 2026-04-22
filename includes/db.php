<?php
/**
 * Sistema JEM - Database Helper Functions
 * PDO database connection and query helpers
 */

require_once __DIR__ . '/../config/config.php';

/**
 * Get database connection
 */
function getConnection() {
    static $pdo = null;
    
    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
            
            // Load tenant context
            // Priority 1: URL Slug (for deep links and routing)
            if (defined('CURRENT_TENANT_SLUG') && CURRENT_TENANT_SLUG) {
                $stmt = $pdo->prepare("SELECT id, nome FROM secretarias WHERE slug = ? AND is_active = 1");
                $stmt->execute([CURRENT_TENANT_SLUG]);
                $tenant = $stmt->fetch();
                if ($tenant) {
                    if (!defined('CURRENT_TENANT_ID')) define('CURRENT_TENANT_ID', $tenant['id']);
                    if (!defined('CURRENT_TENANT_NAME')) define('CURRENT_TENANT_NAME', $tenant['nome']);
                } else {
                    // Redirect or error if tenant not found from slug
                    if (!strpos($_SERVER['REQUEST_URI'], 'superadmin')) {
                        header("Location: /");
                        exit;
                    }
                }
            } 
            // Priority 2: Session (for unified login at root)
            elseif (isset($_SESSION['secretaria_id']) && $_SESSION['secretaria_id']) {
                $stmt = $pdo->prepare("SELECT id, nome FROM secretarias WHERE id = ? AND is_active = 1");
                $stmt->execute([$_SESSION['secretaria_id']]);
                $tenant = $stmt->fetch();
                if ($tenant) {
                    if (!defined('CURRENT_TENANT_ID')) define('CURRENT_TENANT_ID', $tenant['id']);
                    if (!defined('CURRENT_TENANT_NAME')) define('CURRENT_TENANT_NAME', $tenant['nome']);
                }
            }
        } catch (PDOException $e) {
            error_log("Database connection failed: " . $e->getMessage());
            die("Erro de conexão com o banco de dados. Por favor, tente novamente mais tarde.");
        }
    }
    
    return $pdo;
}

/**
 * Execute a query and return all results
 */
function query($sql, $params = []) {
    try {
        $pdo = getConnection();
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Query error: " . $e->getMessage());
        return false;
    }
}

/**
 * Execute a query and return single result
 */
function queryOne($sql, $params = []) {
    try {
        $pdo = getConnection();
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch();
    } catch (PDOException $e) {
        error_log("Query error: " . $e->getMessage());
        return false;
    }
}

/**
 * Execute an insert/update/delete query
 */
function execute($sql, $params = []) {
    try {
        $pdo = getConnection();
        $stmt = $pdo->prepare($sql);
        return $stmt->execute($params);
    } catch (PDOException $e) {
        error_log("Execute error: " . $e->getMessage());
        return false;
    }
}

/**
 * Execute an insert/update/delete query and return row count
 */
function executeWithCount($sql, $params = []) {
    try {
        $pdo = getConnection();
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount();
    } catch (PDOException $e) {
        error_log("Execute error: " . $e->getMessage());
        return -1;
    }
}

/**
 * Get last insert ID
 */
function lastInsertId() {
    $pdo = getConnection();
    return $pdo->lastInsertId();
}

/**
 * Begin transaction
 */
function beginTransaction() {
    $pdo = getConnection();
    return $pdo->beginTransaction();
}

/**
 * Commit transaction
 */
function commit() {
    $pdo = getConnection();
    return $pdo->commit();
}

/**
 * Rollback transaction
 */
function rollback() {
    $pdo = getConnection();
    return $pdo->rollBack();
}

/**
 * Sanitize input
 */
function sanitize($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

/**
 * Validate CPF
 */
function validateCPF($cpf) {
    // Remove non-numeric characters
    $cpf = preg_replace('/[^0-9]/', '', $cpf);
    
    // Check if has 11 digits
    if (strlen($cpf) != 11) {
        return false;
    }
    
    // Check if all digits are the same
    if (preg_match('/(\d)\1{10}/', $cpf)) {
        return false;
    }
    
    // Validate first digit
    for ($t = 9; $t < 11; $t++) {
        for ($d = 0, $c = 0; $c < $t; $c++) {
            $d += $cpf[$c] * (($t + 1) - $c);
        }
        $d = ((10 * $d) % 11) % 10;
        if ($cpf[$c] != $d) {
            return false;
        }
    }
    
    return true;
}

/**
 * Format CPF
 */
function formatCPF($cpf) {
    $cpf = preg_replace('/[^0-9]/', '', $cpf);
    return preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $cpf);
}

/**
 * Format phone
 */
function formatPhone($phone) {
    $phone = preg_replace('/[^0-9]/', '', $phone);
    if (strlen($phone) == 11) {
        return preg_replace('/(\d{2})(\d{5})(\d{4})/', '($1) $2-$3', $phone);
    } elseif (strlen($phone) == 10) {
        return preg_replace('/(\d{2})(\d{4})(\d{4})/', '($1) $2-$3', $phone);
    }
    return $phone;
}

/**
 * Calculate age from birth date
 */
function calculateAge($birthDate) {
    $birth = new DateTime($birthDate);
    $today = new DateTime();
    return $today->diff($birth)->y;
}
