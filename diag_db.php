<?php
require_once 'includes/db.php';

$res = [];

$res['modalities'] = query("SELECT id, name FROM modalities");
$res['categories'] = query("SELECT id, name FROM categories");
$res['event'] = queryOne("SELECT id, name FROM competition_events WHERE active_flag = 1");

if ($res['event']) {
    $eventId = $res['event']['id'];
    $res['matches_summary'] = query("
        SELECT m.modality_id, m.category_id, mod.name as mod_name, cat.name as cat_name, m.phase, m.status, count(*) as cnt
        FROM matches m
        JOIN modalities mod ON m.modality_id = mod.id
        JOIN categories cat ON m.category_id = cat.id
        WHERE m.competition_event_id = ?
        GROUP BY m.modality_id, m.category_id, m.phase, m.status
    ", [$eventId]);
    
    // Check for Fraldinha Fem specifically
    $res['fraldinha_fem_teams'] = query("
        SELECT id, school_name_snapshot, group_name, gender 
        FROM competition_teams 
        WHERE category_id IN (SELECT id FROM categories WHERE name LIKE '%Fraldinha%')
        AND gender = 'F'
    ");
}

header('Content-Type: application/json');
echo json_encode($res, JSON_PRETTY_PRINT);
