<?php
require_once 'includes/db.php';
require_once 'includes/knockout_generator.php';

$eventId = 3;
$modId = 12;
$catId = 5;
$gender = 'F';

$standings = calculateGroupStandings($eventId, $modId, $catId, $gender);

header('Content-Type: application/json');
echo json_encode($standings, JSON_PRETTY_PRINT);
