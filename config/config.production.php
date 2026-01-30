<?php
/**
 * Sistema JEM - Production Configuration File
 * 
 * INSTRUÇÕES PARA DEPLOY:
 * 1. Renomeie este arquivo para config.php no servidor de produção
 * 2. Configure as credenciais do banco de dados de produção
 * 3. Configure a URL do domínio de produção
 * 4. Verifique se todas as configurações estão corretas
 * 5. NUNCA faça commit deste arquivo com credenciais reais
 */

// ============================================
// DATABASE CONFIGURATION - PRODUÇÃO
// ============================================
// IMPORTANTE: Configure com as credenciais do seu servidor de produção
define('DB_HOST', 'localhost');  // Geralmente 'localhost' ou IP do servidor MySQL
define('DB_NAME', 'jem_database');  // Nome do banco de dados em produção
define('DB_USER', 'seu_usuario_mysql');  // ALTERAR: Usuário do MySQL em produção
define('DB_PASS', 'sua_senha_segura');  // ALTERAR: Senha do MySQL em produção
define('DB_CHARSET', 'utf8mb4');

// ============================================
// SYSTEM CONFIGURATION - PRODUÇÃO
// ============================================
define('SITE_NAME', 'Sistema JEM');
define('SITE_URL', 'https://seu-dominio.com');  // ALTERAR: URL completa do seu domínio
define('BASE_PATH', __DIR__ . '/..');

// ============================================
// UPLOAD CONFIGURATION
// ============================================
define('UPLOAD_DIR', BASE_PATH . '/uploads');
define('STUDENT_PHOTOS_DIR', UPLOAD_DIR . '/students');
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'gif']);

// ============================================
// SESSION CONFIGURATION
// ============================================
define('SESSION_LIFETIME', 3600 * 8); // 8 horas
define('SESSION_NAME', 'JEM_SESSION');

// Configurações de segurança de sessão
ini_set('session.cookie_httponly', 1);  // Previne acesso via JavaScript
ini_set('session.cookie_secure', 1);    // Apenas HTTPS (descomente quando tiver SSL)
ini_set('session.use_strict_mode', 1);  // Previne session fixation

// ============================================
// PAGINATION
// ============================================
define('ITEMS_PER_PAGE', 10);

// ============================================
// TIMEZONE
// ============================================
date_default_timezone_set('America/Sao_Paulo');

// ============================================
// ERROR REPORTING - PRODUÇÃO
// ============================================
// IMPORTANTE: Em produção, NUNCA exiba erros para o usuário
error_reporting(E_ALL);  // Registra todos os erros
ini_set('display_errors', 0);  // NÃO exibe erros na tela
ini_set('log_errors', 1);  // Registra erros em arquivo de log
ini_set('error_log', BASE_PATH . '/logs/php_errors.log');  // Caminho do log

// ============================================
// SESSION START
// ============================================
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

// ============================================
// SECURITY HEADERS
// ============================================
// Headers de segurança adicionais (caso não estejam no .htaccess)
if (!headers_sent()) {
    header('X-Frame-Options: SAMEORIGIN');
    header('X-Content-Type-Options: nosniff');
    header('X-XSS-Protection: 1; mode=block');
    header('Referrer-Policy: strict-origin-when-cross-origin');
}
