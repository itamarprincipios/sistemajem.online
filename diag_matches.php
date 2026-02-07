<?php
require_once 'includes/db.php';

$res = [];

// Find IDs
$modality = queryOne("SELECT id FROM modalities WHERE name LIKE '%Society%'");
$category = queryOne("SELECT id FROM categories WHERE name LIKE '%Fraldinha%'");

if ($modality && $category) {
    $modId = $modality['id'];
    $catId = $category['id'];
    
    $event = queryOne("SELECT id FROM competition_events WHERE active_flag = 1");
    $eventId = $event['id'];
    
    $res['summary'] = query("
        SELECT phase, status, count(*) as cnt
        FROM matches 
        WHERE competition_event_id = ? AND modality_id = ? AND category_id = ?
        GROUP BY phase, status
    ", [$eventId, $modId, $catId]);
    
    $res['unfinished_matches'] = query("
        SELECT id, team_a_id, team_b_id, scheduled_time, status, phase
        FROM matches 
        WHERE competition_event_id = ? AND modality_id = ? AND category_id = ?
        AND status != 'finished'
        ORDER BY scheduled_time
    ", [$eventId, $modId, $catId]);
    
    $res['teams_by_group'] = query("
        SELECT group_name, count(*) as team_count
        FROM competition_teams
        WHERE competition_event_id = ? AND modality_id = ? AND category_id = ? AND gender = 'F'
        GROUP BY group_name
    ", [$eventId, $modId, $catId]);
}

header('Content-Type: application/json');
echo json_encode($res, JSON_PRETTY_PRINT);
