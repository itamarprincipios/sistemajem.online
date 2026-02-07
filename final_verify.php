<?php
require_once 'includes/db.php';
require_once 'includes/knockout_generator.php';

$eventId = 3;
$modId = 12;
$catId = 5;
$gender = 'F';

header('Content-Type: text/plain; charset=UTF-8');

echo "FINAL VERIFICATION FOR KNOCKOUT GENERATION\n";
echo "=========================================\n";

// 1. Check isPhaseComplete for Gender F
$fComplete = isPhaseComplete($eventId, $modId, $catId, 'group_stage', 'F');
echo "Fem Group Stage Complete: " . ($fComplete ? "YES" : "NO") . "\n";

// 2. Check Match Counts for Gender F
$sqlCount = "SELECT COUNT(*) as cnt FROM matches m ";
if ($gender) $sqlCount .= "JOIN competition_teams t ON m.team_a_id = t.id ";
$sqlCount .= "WHERE m.competition_event_id = ? 
          AND m.modality_id = ? 
          AND m.category_id = ? 
          AND m.phase = 'round_of_16'";
$paramsCount = [$eventId, $modId, $catId];
if ($gender) { $sqlCount .= " AND t.gender = ?"; $paramsCount[] = 'F'; }

$r16Count = (int)queryOne($sqlCount, $paramsCount)['cnt'];
echo "Oitavas de Final (Fem) Matches Found: $r16Count\n";

if ($fComplete && $r16Count == 0) {
    echo "\nSTATUS: SUCCESS! Category is READY for knockout generation.\n";
} else {
    echo "\nSTATUS: BLOCKED! Check data (Is group stage finished? Are matches already generated?)\n";
}

// 3. Contrast with Gender M
$mComplete = isPhaseComplete($eventId, $modId, $catId, 'group_stage', 'M');
echo "\nMas Group Stage Complete: " . ($mComplete ? "YES" : "NO") . "\n";
if (!$mComplete) echo "Note: This is why it was blocked before (Male stage unfinished).\n";
