<?php
/**
 * Sistema JEM - Configuration File
 * Database and system configuration
 */

// Check for production configuration first
if (file_exists(__DIR__ . '/config.production.php')) {
    require_once __DIR__ . '/config.production.php';
} else {
    // Local Development Configuration
    define('DB_HOST', 'localhost');
    define('DB_NAME', 'jem_database');
    define('DB_USER', 'root');
    define('DB_PASS', '');
    define('DB_CHARSET', 'utf8mb4');
    
    // System Configuration
    define('SITE_NAME', 'Sistema JEM');
    define('SITE_URL', 'http://localhost:8000');
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

    // Error Reporting (Development)
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
}

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_name(SESSION_NAME);
    session_start();
    
    // Set session timeout
    if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY'] > SESSION_LIFETIME)) {
        session_unset();
        session_destroy();
        session_start();
    }
    $_SESSION['LAST_ACTIVITY'] = time();
}
