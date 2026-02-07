<?php
require_once 'includes/db.php';
$events = query("SELECT id, name, active_flag FROM competition_events WHERE active_flag = 1");
header('Content-Type: application/json');
echo json_encode($events, JSON_PRETTY_PRINT);
