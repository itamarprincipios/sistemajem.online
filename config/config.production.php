<?php
/**
 * Sistema JEM - Production Configuration
 * Hostinger Server Configuration
 */

// Database Configuration - Hostinger
define('DB_HOST', 'localhost');
define('DB_NAME', 'u19967l261_database');
define('DB_USER', 'u19967l261_admdatabase');
define('DB_PASS', 'I@nna2111');
define('DB_CHARSET', 'utf8mb4');

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
