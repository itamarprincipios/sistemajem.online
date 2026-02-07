<?php
require_once 'includes/db.php';

$sql = "SELECT id, category_id, modality_id, gender, group_name, school_name_snapshot 
        FROM competition_teams 
        WHERE competition_event_id = 3 AND category_id = 5 AND gender = 'F'
        LIMIT 50";

$teams = query($sql);
header('Content-Type: application/json');
echo json_encode($teams, JSON_PRETTY_PRINT);
