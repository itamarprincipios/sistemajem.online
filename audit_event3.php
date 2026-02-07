<?php
require_once 'includes/db.php';

$eventId = 3; // Society event

echo "MATCHES SUMMARY FOR EVENT 3:\n";
$summary = query("
    SELECT modality_id, category_id, phase, status, count(*) as cnt
    FROM matches 
    WHERE competition_event_id = ?
    GROUP BY modality_id, category_id, phase, status
", [$eventId]);
foreach ($summary as $s) echo "Mod: {$s['modality_id']} | Cat: {$s['category_id']} | Phase: {$s['phase']} | Status: {$s['status']} | Count: {$s['cnt']}\n";

echo "\nTEAM GENDER CHECK FOR CAT 5, MOD 12:\n";
$teams = query("
    SELECT id, school_name_snapshot, gender, group_name
    FROM competition_teams
    WHERE category_id = 5 AND modality_id = 12
");
foreach ($teams as $t) echo "ID: {$t['id']} | Name: {$t['school_name_snapshot']} | Gender: {$t['gender']} | Group: {$t['group_name']}\n";
