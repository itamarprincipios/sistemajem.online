<?php
require_once 'includes/db.php';

$eventId = 3; 
$modId = 12;
$catId = 5;
$gender = 'M';

$sql = "
    SELECT m.id, m.score_team_a, m.score_team_b, m.status
    FROM matches m
    LEFT JOIN competition_teams t1 ON m.team_a_id = t1.id
    WHERE m.competition_event_id = ? AND m.modality_id = ? AND m.category_id = ? AND t1.gender = ?
    LIMIT 5
";

$data = query($sql, [$eventId, $modId, $catId, $gender]);
header('Content-Type: application/json');
echo json_encode($data, JSON_PRETTY_PRINT);
