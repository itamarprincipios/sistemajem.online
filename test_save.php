<?php
require_once 'includes/db.php';

$payload = [
    'action' => 'save_appointments',
    'match_id' => 559,
    'staff' => [
        'team_a_coach' => 'Abel Ferreira',
        'team_a_assistant' => 'João Rodrigues',
        'team_b_coach' => 'Felipe Luis',
        'team_b_assistant' => 'Roger Guedes'
    ],
    'referees' => [
        'primary' => 'Alexadre de Moraes',
        'assistant' => 'Falvio Dino',
        'fourth' => 'Dias Toffoli'
    ],
    'captains' => [
        'team_a' => null,
        'team_b' => null
    ],
    'observations' => 'Teste de salvamento via script debug',
    'athletes' => [] // Empty for test
];

// Mocking the API logic
try {
    $matchId = $payload['match_id'];
    $staff = $payload['staff'];
    $input = $payload;

    beginTransaction();
    echo "Transaction started...\n";

    execute("
        UPDATE matches 
        SET team_a_coach = ?, 
            team_a_assistant = ?, 
            team_b_coach = ?, 
            team_b_assistant = ?,
            referee_primary = ?,
            referee_assistant = ?,
            referee_fourth = ?,
            team_a_captain_id = ?,
            team_b_captain_id = ?,
            observations = ?
        WHERE id = ?
    ", [
        $staff['team_a_coach'] ?? null,
        $staff['team_a_assistant'] ?? null,
        $staff['team_b_coach'] ?? null,
        $staff['team_b_assistant'] ?? null,
        $input['referees']['primary'] ?? null,
        $input['referees']['assistant'] ?? null,
        $input['referees']['fourth'] ?? null,
        $input['captains']['team_a'] ?? null,
        $input['captains']['team_b'] ?? null,
        $input['observations'] ?? null,
        $matchId
    ]);
    
    echo "Update executed.\n";
    commit();
    echo "Commit successful.\n";
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    rollback();
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
