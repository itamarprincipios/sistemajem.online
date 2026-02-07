<?php
require_once 'includes/db.php';

echo "ACTIVE EVENTS:\n";
$events = query("SELECT id, name, active_flag FROM competition_events");
foreach ($events as $e) echo "{$e['id']} | {$e['name']} | Active: {$e['active_flag']}\n";

$modId = 12; // Society
$catId = 5;  // Fraldinha

echo "\nMATCHES SUMMARY FOR MOD 12, CAT 5:\n";
$summary = query("
    SELECT phase, status, gender, count(*) as cnt
    FROM matches 
    WHERE modality_id = ? AND category_id = ?
    GROUP BY phase, status, gender
", [$modId, $catId]);
foreach ($summary as $s) echo "Phase: {$s['phase']} | Status: {$s['status']} | Gender: {$s['gender']} | Count: {$s['cnt']}\n";

echo "\nUNFINISHED GROUP MATCHES (FEM):\n";
$unfinished = query("
    SELECT id, team_a_id, team_b_id, status
    FROM matches 
    WHERE modality_id = ? AND category_id = ? AND phase = 'group_stage' AND status != 'finished' AND (gender = 'F' OR gender IS NULL)
", [$modId, $catId]);
foreach ($unfinished as $u) echo "ID: {$u['id']} | A: {$u['team_a_id']} | B: {$u['team_b_id']} | Status: {$u['status']}\n";

echo "\nGROUPS IN CATEGORY 5 (FEM):\n";
$groups = query("
    SELECT DISTINCT group_name 
    FROM competition_teams 
    WHERE category_id = 5 AND gender = 'F'
");
foreach ($groups as $g) echo "Group: {$g['group_name']}\n";
