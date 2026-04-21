<?php
/**
 * Sistema JEM - Authentication Functions
 * Login, logout, session management, and role verification
 */

require_once __DIR__ . '/db.php';

/**
 * Login user
 */
function login($email, $password) {
    // Se estivermos em um contexto de secretaria, filtrar por ela
    $params = [$email];
    $sql = "SELECT * FROM users WHERE email = ? AND is_active = 1";
    
    $user = queryOne($sql, $params);
    
    if ($user && password_verify($password, $user['password'])) {
        // Verificar se o usuário pertence a esta secretaria ou se é super_admin
        if ($user['role'] !== 'super_admin' && defined('CURRENT_TENANT_ID')) {
            if ($user['secretaria_id'] != CURRENT_TENANT_ID) {
                return false; // Usuário não pertence a esta secretaria
            }
        }

        // Set session variables
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['school_id'] = $user['school_id'];
        $_SESSION['secretaria_id'] = $user['secretaria_id'];
        $_SESSION['logged_in'] = true;
        
        return true;
    }
    
    return false;
}

/**
 * Logout user
 */
function logout() {
    session_unset();
    session_destroy();
    header('Location: ' . SITE_URL . '/login.php');
    exit;
}

/**
 * Check if user is logged in
 */
function isLoggedIn() {
    return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
}

/**
 * Check if user is admin
 */
function isAdmin() {
    return isLoggedIn() && $_SESSION['user_role'] === 'admin';
}

/**
 * Check if user is professor
 */
function isProfessor() {
    return isLoggedIn() && $_SESSION['user_role'] === 'professor';
}

/**
 * Check if user is super admin
 */
function isSuperAdmin() {
    return isLoggedIn() && $_SESSION['user_role'] === 'super_admin';
}

/**
 * Require login
 */
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: ' . SITE_URL . '/login.php');
        exit;
    }
}

/**
 * Require admin role
 */
function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        header('Location: ' . SITE_URL . '/index.php');
        exit;
    }
}

/**
 * Require professor role
 */
function requireProfessor() {
    requireLogin();
    if (!isProfessor() && !isAdmin() && !isSuperAdmin()) {
        header('Location: ' . SITE_URL . '/index.php');
        exit;
    }
}

/**
 * Require super admin role
 */
function requireSuperAdmin() {
    requireLogin();
    if (!isSuperAdmin()) {
        header('Location: ' . SITE_URL . '/index.php');
        exit;
    }
}

/**
 * Require operator role
 */
function requireOperator() {
    requireLogin();
    $role = getCurrentUserRole();
    if ($role !== 'operator' && $role !== 'admin') {
        header('Location: ' . SITE_URL . '/index.php');
        exit;
    }
}

/**
 * Get current user ID
 */
function getCurrentUserId() {
    return $_SESSION['user_id'] ?? null;
}

/**
 * Get current user name
 */
function getCurrentUserName() {
    return $_SESSION['user_name'] ?? '';
}

/**
 * Get current user role
 */
function getCurrentUserRole() {
    return $_SESSION['user_role'] ?? '';
}

/**
 * Get current user school ID
 */
function getCurrentSchoolId() {
    return $_SESSION['school_id'] ?? null;
}

/**
 * Hash password
 */
function hashPassword($password) {
    return password_hash($password, PASSWORD_BCRYPT);
}

/**
 * Generate random password
 */
function generatePassword($length = 8) {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%';
    $password = '';
    for ($i = 0; $i < $length; $i++) {
        $password .= $chars[random_int(0, strlen($chars) - 1)];
    }
    return $password;
}

/**
 * Validate email
 */
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Check if email exists
 */
function emailExists($email, $excludeUserId = null) {
    if ($excludeUserId) {
        $user = queryOne("SELECT id FROM users WHERE email = ? AND id != ?", [$email, $excludeUserId]);
    } else {
        $user = queryOne("SELECT id FROM users WHERE email = ?", [$email]);
    }
    return $user !== false;
}

/**
 * Check if CPF exists
 */
function cpfExists($cpf, $excludeUserId = null) {
    if ($excludeUserId) {
        $user = queryOne("SELECT id FROM users WHERE cpf = ? AND id != ?", [$cpf, $excludeUserId]);
    } else {
        $user = queryOne("SELECT id FROM users WHERE cpf = ?", [$cpf]);
    }
    return $user !== false;
}

/**
 * Get redirect URL based on role
 */
function getRedirectUrl($role) {
    switch ($role) {
        case 'super_admin':
            return SITE_URL . '/superadmin/dashboard.php';
        case 'admin':
            return SITE_URL . '/admin/dashboard.php';
        case 'professor':
            return SITE_URL . '/professor/dashboard.php';
        case 'operator':
            return SITE_URL . '/operator/dashboard.php';
        default:
            return SITE_URL . '/index.php';
    }
}
