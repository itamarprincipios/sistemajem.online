<?php
require_once 'includes/db.php';

$modId = 12; // Society
$catId = 5;  // Fraldinha

echo "TEAMS IN SOCIETY FRALDINHA FEM:\n";
$teams = query("
    SELECT id, school_name_snapshot, group_name
    FROM competition_teams
    WHERE modality_id = ? AND category_id = ? AND gender = 'F'
", [$modId, $catId]);

foreach ($teams as $t) {
    echo "ID: {$t['id']} | Name: {$t['school_name_snapshot']} | Group: {$t['group_name']}\n";
    // Search any matches for this team
    $matches = query("
        SELECT id, team_a_id, team_b_id, modality_id, category_id, phase, status 
        FROM matches 
        WHERE team_a_id = ? OR team_b_id = ?
    ", [$t['id'], $t['id']]);
    foreach ($matches as $m) {
        echo "  Match ID: {$m['id']} | Mod: {$m['modality_id']} | Cat: {$m['category_id']} | Phase: {$m['phase']} | Status: {$m['status']}\n";
    }
}
