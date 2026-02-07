<?php
require_once 'includes/db.php';

echo "SAMPLES FROM matches TABLE:\n";
$samples = query("
    SELECT id, competition_event_id, modality_id, category_id, phase, status, team_a_id, team_b_id, gender
    FROM matches 
    LIMIT 10
");
foreach ($samples as $s) {
    echo "ID: {$s['id']} | Event: {$s['competition_event_id']} | Mod: {$s['modality_id']} | Cat: {$s['category_id']} | Phase: {$s['phase']} | Status: {$s['status']} | Gender: {$s['gender']}\n";
}

echo "\nFINDING MATCHES BY TEAM ID FROM GROUP A (FEM):\n";
$teamInGroupA = queryOne("SELECT id, school_name_snapshot FROM competition_teams WHERE category_id = 5 AND group_name = 'A' AND gender = 'F' LIMIT 1");
if ($teamInGroupA) {
    echo "Team: {$teamInGroupA['school_name_snapshot']} (ID: {$teamInGroupA['id']})\n";
    $matches = query("
        SELECT id, team_a_id, team_b_id, modality_id, category_id, phase, status 
        FROM matches 
        WHERE team_a_id = ? OR team_b_id = ?
    ", [$teamInGroupA['id'], $teamInGroupA['id']]);
    foreach ($matches as $m) {
        echo "ID: {$m['id']} | A: {$m['team_a_id']} | B: {$m['team_b_id']} | Mod: {$m['modality_id']} | Cat: {$m['category_id']} | Phase: {$m['phase']} | Status: {$m['status']}\n";
    }
} else {
    echo "No teams found in Group A (Fem) Category 5.\n";
}
