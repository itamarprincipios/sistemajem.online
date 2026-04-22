<?php
require_once 'config/config.php';
header('Content-Type: application/json');
echo json_encode([
    'session_id' => session_id(),
    'session_data' => $_SESSION,
    'get_data' => $_GET,
    'defined_constants' => [
        'CURRENT_TENANT_SLUG' => defined('CURRENT_TENANT_SLUG') ? CURRENT_TENANT_SLUG : 'not defined',
        'CURRENT_TENANT_ID' => defined('CURRENT_TENANT_ID') ? CURRENT_TENANT_ID : 'not defined'
    ]
]);
