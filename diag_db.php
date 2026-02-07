<?php
require_once 'includes/db.php';

echo "MODALITIES:\n";
$modalities = query("SELECT id, name FROM modalities");
foreach ($modalities as $m) echo "{$m['id']}: {$m['name']}\n";

echo "\nCATEGORIES:\n";
$categories = query("SELECT id, name FROM categories");
foreach ($categories as $c) echo "{$c['id']}: {$c['name']}\n";

echo "\nACTIVE EVENT:\n";
$event = queryOne("SELECT id, name FROM competition_events WHERE active_flag = 1");
if ($event) echo "{$event['id']}: {$event['name']}\n";

if ($event) {
    echo "\nMATCHES FOR FRALDINHA FEM (Assuming Category 1, Modality 2 - adjust if needed):\n";
    // I will try to find the category ID for 'Fraldinha' and modality for 'Society'
    $fraldinhaId = 0;
    foreach ($categories as $c) if (stripos($c['name'], 'Fraldinha') !== false) $fraldinhaId = $c['id'];
    
    $societyId = 0;
    foreach ($modalities as $m) if (stripos($m['name'], 'Society') !== false) $societyId = $m['id'];
    
    echo "Fraldinha ID: $fraldinhaId, Society ID: $societyId\n";
    
    if ($fraldinhaId && $societyId) {
        $matches = query("SELECT id, team_a_id, team_b_id, score_team_a, score_team_b, status, phase 
                          FROM matches 
                          WHERE competition_event_id = ? AND modality_id = ? AND category_id = ?
                          ORDER BY phase, scheduled_time", [$event['id'], $societyId, $fraldinhaId]);
        
        foreach ($matches as $m) {
            echo "ID: {$m['id']} | {$m['phase']} | TeamA: {$m['team_a_id']} vs TeamB: {$m['team_b_id']} | Score: {$m['score_team_a']}x{$m['score_team_b']} | Status: {$m['status']}\n";
        }
    }
}
