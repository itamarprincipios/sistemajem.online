<?php
/**
 * Sistema JEM - Production Configuration File
 * Settings specific for Hostgator production environment
 */

// Database Configuration - Hostgator
define('DB_HOST', 'localhost');
define('DB_NAME', 'itama742_jogos');      // ALTERE AQUI
define('DB_USER', 'itama742_1409');       // ALTERE AQUI
define('DB_PASS', 'admin1409');           // ALTERE AQUI
define('DB_CHARSET', 'utf8mb4');

// System Configuration
define('SITE_NAME', 'Sistema JEM');
define('SITE_URL', 'http://jgsescolares.online'); // ALTERE AQUI
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

// Error Reporting (ENABLED FOR DEBUGGING)
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', BASE_PATH . '/error_log');
