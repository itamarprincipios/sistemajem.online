<?php
require_once 'includes/db.php';
require_once 'includes/knockout_generator.php';

$eventId = 3;
$modId = 12;
$catId = 5;
$gender = 'F';

header('Content-Type: text/plain; charset=UTF-8');

echo "VERIFYING JOINED GENDER LOGIC (V2)\n";
echo "==================================\n";

$isComplete = isPhaseComplete($eventId, $modId, $catId, 'group_stage', $gender);
echo "isPhaseComplete(Gender: F): " . ($isComplete ? "YES (Correct)" : "NO (Incorrect)") . "\n";

$mCheck = isPhaseComplete($eventId, $modId, $catId, 'group_stage', 'M');
echo "isPhaseComplete(Gender: M): " . ($mCheck ? "YES (Incorrect if group stage not finished)" : "NO (Correct)") . "\n";

// Manual count
$sql = "SELECT COUNT(*) as total, 
        SUM(CASE WHEN m.status = 'finished' THEN 1 ELSE 0 END) as finished
        FROM matches m
        JOIN competition_teams t ON m.team_a_id = t.id
        WHERE m.competition_event_id = ? 
        AND m.modality_id = ? 
        AND m.category_id = ? 
        AND m.phase = ?
        AND t.gender = ?";

$resF = queryOne($sql, [$eventId, $modId, $catId, 'group_stage', 'F']);
echo "FEM Matches: {$resF['finished']} / {$resF['total']}\n";

$resM = queryOne($sql, [$eventId, $modId, $catId, 'group_stage', 'M']);
echo "MAS Matches: {$resM['finished']} / {$resM['total']}\n";

if ($isComplete && !$mCheck) {
    echo "\nSUCCESS: Gender-specific check working correctly! Fem can generate, Mas is waiting.\n";
} else {
    echo "\nNOTE: Double check the 'M' gender status if the Male group stage is also finished.\n";
}
