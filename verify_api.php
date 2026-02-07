<?php
require_once 'includes/db.php';
require_once 'includes/knockout_generator.php';

$eventId = 3;
$modId = 12;
$catId = 5;
$gender = 'F';

echo "SIMULATING API LOGIC (NO AUTH)\n";
echo "=============================\n";

$status = [
    'can_generate' => false,
    'next_phase' => null,
    'message' => ''
];

if (isPhaseComplete($eventId, $modId, $catId, 'group_stage', $gender)) {
    echo "Phase Complete: YES\n";
    
    $sqlR16 = "SELECT COUNT(*) as cnt FROM matches m ";
    if ($gender) $sqlR16 .= "JOIN competition_teams t ON m.team_a_id = t.id ";
    $sqlR16 .= "WHERE m.competition_event_id = ? 
              AND m.modality_id = ? 
              AND m.category_id = ? 
              AND m.phase = 'round_of_16'";
    
    $paramsR16 = [$eventId, $modId, $catId];
    if ($gender) { $sqlR16 .= " AND t.gender = ?"; $paramsR16[] = $gender; }
    
    $resR16 = queryOne($sqlR16, $paramsR16);
    $r16Count = (int)$resR16['cnt'];
    echo "R16 Match Count: $r16Count\n";
    
    if ($r16Count == 0) {
        $status['can_generate'] = true;
        $status['next_phase'] = 'round_of_16';
        $status['message'] = 'Fase de Grupos concluída! Pronto para gerar Oitavas de Final.';
    }
} else {
    echo "Phase Complete: NO\n";
}

print_r($status);

if ($status['can_generate'] && $status['next_phase'] === 'round_of_16') {
    echo "\nSUCCESS: API logic now allows generating Oitavas for Fraldinha Fem!\n";
} else {
    echo "\nFAILURE: API logic still blocking generation.\n";
}
