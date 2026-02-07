<?php
require_once 'includes/db.php';

$res = [];
$modId = 12;
$catId = 5;

// All teams for this cat/mod
$teams = query("SELECT id, gender, group_name FROM competition_teams WHERE modality_id = $modId AND category_id = $catId");
$teamMap = [];
foreach ($teams as $t) $teamMap[$t['id']] = $t;

// All group matches
$matches = query("SELECT id, team_a_id, team_b_id, status FROM matches WHERE modality_id = $modId AND category_id = $catId AND phase = 'group_stage'");

$stats = ['M' => ['total' => 0, 'finished' => 0, 'unfinished' => []], 'F' => ['total' => 0, 'finished' => 0, 'unfinished' => []]];

foreach ($matches as $m) {
    $gender = $teamMap[$m['team_a_id']]['gender'] ?? 'M';
    $stats[$gender]['total']++;
    if ($m['status'] === 'finished') {
        $stats[$gender]['finished']++;
    } else {
        $stats[$gender]['unfinished'][] = $m['id'];
    }
}

echo "FRALDINHA STATS:\n";
echo "FEM: Total: {$stats['F']['total']} | Finished: {$stats['F']['finished']} | Unfinished: ".count($stats['F']['unfinished'])."\n";
if (!empty($stats['F']['unfinished'])) echo "  Unfinished IDs: ".implode(',', $stats['F']['unfinished'])."\n";

echo "MAS: Total: {$stats['M']['total']} | Finished: {$stats['M']['finished']} | Unfinished: ".count($stats['M']['unfinished'])."\n";

// Groups check
$groupsFem = [];
foreach ($teams as $t) if ($t['gender'] === 'F') $groupsFem[$t['group_name']] = ($groupsFem[$t['group_name']] ?? 0) + 1;
echo "FEM GROUPS: ";
foreach ($groupsFem as $g => $count) echo "$g($count) ";
echo "\n";
