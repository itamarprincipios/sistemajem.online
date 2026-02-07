<?php
require_once 'includes/db.php';
require_once 'includes/knockout_generator.php';

$eventId = 3;
$modId = 12;
$catId = 5;
$gender = 'F';

header('Content-Type: text/plain; charset=UTF-8');

echo "DEEP DEBUG isPhaseComplete (V3)\n";
echo "==============================\n";

$sql = "SELECT COUNT(*) as total, 
        SUM(CASE WHEN m.status = 'finished' THEN 1 ELSE 0 END) as finished
        FROM matches m
        JOIN competition_teams t ON m.team_a_id = t.id
        WHERE m.competition_event_id = ? 
        AND m.modality_id = ? 
        AND m.category_id = ? 
        AND m.phase = ?
        AND t.gender = ?";

$params = [$eventId, $modId, $catId, 'group_stage', $gender];
$res = queryOne($sql, $params);

echo "Query result: total=" . var_export($res['total'], true) . ", finished=" . var_export($res['finished'], true) . "\n";

$isComplete = isPhaseComplete($eventId, $modId, $catId, 'group_stage', $gender);
echo "Final isPhaseComplete result: " . ($isComplete ? "TRUE" : "FALSE") . "\n";

echo "\nChecking for matches without joined team:\n";
$orphanSQL = "SELECT count(*) as cnt FROM matches m 
              LEFT JOIN competition_teams t ON m.team_a_id = t.id 
              WHERE m.competition_event_id = ? AND m.modality_id = ? AND m.category_id = ? AND m.phase = 'group_stage' AND t.id IS NULL";
$orphan = queryOne($orphanSQL, [$eventId, $modId, $catId]);
echo "Orphaned matches: {$orphan['cnt']}\n";

echo "\nChecking gender of some teams in these matches:\n";
$checkGender = query("SELECT DISTINCT t.gender, count(*) as cnt 
                      FROM matches m 
                      JOIN competition_teams t ON m.team_a_id = t.id 
                      WHERE m.competition_event_id = ? AND m.modality_id = ? AND m.category_id = ? AND m.phase = 'group_stage' 
                      GROUP BY t.gender", 
                      [$eventId, $modId, $catId]);
foreach ($checkGender as $g) echo "Gender: {$g['gender']} | Count: {$g['cnt']}\n";
