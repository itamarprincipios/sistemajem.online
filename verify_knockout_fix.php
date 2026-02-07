<?php
require_once 'includes/db.php';
require_once 'includes/knockout_generator.php';

$eventId = 3;
$modId = 12;
$catId = 5;
$gender = 'F';

echo "CHECKING isPhaseComplete for Event 3, Mod 12, Cat 5, Gender F:\n";
$isComplete = isPhaseComplete($eventId, $modId, $catId, 'group_stage', $gender);
echo "Result: " . ($isComplete ? "TRUE (Correct)" : "FALSE (Incorrect)") . "\n";

echo "\nSIMULATING API CALL TO api/knockout-api.php?action=knockout_status&gender=F:\n";
// Since I can't easily call the API URL from PHP here without file_get_contents which might fail on some hosts, 
// I will just simulate the logic or use curl if available.
// Actually, I can just include the logic or run a separate powershell command.

// Instead, I'll just check if the r16Count with gender filter is 0
$sqlR16 = "SELECT COUNT(*) as cnt FROM matches WHERE competition_event_id = ? AND modality_id = ? AND category_id = ? AND phase = 'round_of_16' AND gender = ?";
$r16Count = queryOne($sqlR16, [$eventId, $modId, $catId, $gender])['cnt'];
echo "R16 Match Count for Gender F: $r16Count\n";

if ($isComplete && $r16Count == 0) {
    echo "\nSTATUS: SUCCESS! Knockout can now be generated for Fraldinha Fem.\n";
} else {
    echo "\nSTATUS: FAILED! Check parameters or logic.\n";
}
