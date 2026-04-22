<?php
require_once 'config/config.php';
require_once 'includes/db.php';
header('Content-Type: application/json');

$response = [
    'session' => $_SESSION,
    'constants' => [
        'CURRENT_TENANT_ID' => defined('CURRENT_TENANT_ID') ? CURRENT_TENANT_ID : 'not defined',
    ],
    'db_checks' => []
];

try {
    $pdo = getConnection();
    $response['db_checks']['connection'] = 'OK';
    
    // Check secretariats
    $response['db_checks']['secretarias_count'] = queryOne("SELECT COUNT(*) as c FROM secretarias")['c'];
    
    if (defined('CURRENT_TENANT_ID')) {
        $response['db_checks']['schools_count_for_tenant'] = queryOne("SELECT COUNT(*) as c FROM schools WHERE secretaria_id = ?", [CURRENT_TENANT_ID])['c'];
        $response['db_checks']['categories_count_for_tenant'] = queryOne("SELECT COUNT(*) as c FROM categories WHERE secretaria_id = ?", [CURRENT_TENANT_ID])['c'];
    }
    
} catch (Exception $e) {
    $response['db_checks']['error'] = $e->getMessage();
}

echo json_encode($response);
