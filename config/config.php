<?php
/**
 * Sistema JEM - Configuration File
 * Database and system configuration
 */

// Database Configuration - Hostinger Production
define('DB_HOST', 'localhost');
define('DB_NAME', 'u199671261_database');
define('DB_USER', 'u199671261_admdatabase');
define('DB_PASS', 'yv!B6+Lp');
define('DB_CHARSET', 'utf8mb4');

// Tenant Detection (Micro-SaaS)
$tenant_slug = $_GET['tenant'] ?? null;
define('CURRENT_TENANT_SLUG', $tenant_slug);

// System Configuration
define('SITE_NAME', 'Sistema JEM');
define('SITE_URL', 'https://sistemajem.online');
define('BASE_PATH', __DIR__ . '/..');

// Upload Configuration
define('UPLOAD_DIR', BASE_PATH . '/uploads');
define('STUDENT_PHOTOS_DIR', UPLOAD_DIR . '/students');
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'gif']);

// Session Configuration
define('SESSION_LIFETIME', 3600 * 8); // 8 hours
define('SESSION_NAME', 'JEM_SESSION');

// Pagination
define('ITEMS_PER_PAGE', 10);

// Timezone
date_default_timezone_set('America/Sao_Paulo');

// Error Reporting (Production)
error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', BASE_PATH . '/logs/php-errors.log');

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_name(SESSION_NAME);
    
    // Ensure session cookie is available across all paths
    session_set_cookie_params([
        'lifetime' => SESSION_LIFETIME,
        'path' => '/',
        'domain' => '', // Default to current domain
        'secure' => true,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    
    session_start();
    
    // Set session timeout
    if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY'] > SESSION_LIFETIME)) {
        session_unset();
        session_destroy();
        session_start();
    }
    $_SESSION['LAST_ACTIVITY'] = time();
}
