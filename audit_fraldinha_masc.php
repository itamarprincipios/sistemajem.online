<?php
require_once 'includes/db.php';

$eventId = 3; 
$modId = 12;
$catId = 5;
$gender = 'M';

$sql = "
    SELECT m.id, m.phase, m.status, t1.school_name_snapshot as team_a, t2.school_name_snapshot as team_b
    FROM matches m
    LEFT JOIN competition_teams t1 ON m.team_a_id = t1.id
    LEFT JOIN competition_teams t2 ON m.team_b_id = t2.id
    WHERE m.competition_event_id = ? AND m.modality_id = ? AND m.category_id = ? AND (t1.gender = ? OR t1.gender IS NULL)
";

$matches = query($sql, [$eventId, $modId, $catId, $gender]);
header('Content-Type: application/json');
echo json_encode($matches, JSON_PRETTY_PRINT);
