<?php
require_once '../config/config.php';
require_once '../includes/auth.php';
require_once '../includes/db.php';

requireLogin(); // Should check for Operator/Admin role

header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';

try {
    if ($action === 'event') {
        $matchId = $input['match_id'];
        $teamId = $input['team_id'];
        $athleteId = $input['athlete_id'];
        $type = $input['event_type'];
        
        // 1. Insert Event
        execute("
            INSERT INTO match_events (match_id, team_id, athlete_id, event_type, created_at) 
            VALUES (?, ?, ?, ?, NOW())
        ", [$matchId, $teamId, $athleteId, $type]);
        
        // 2. Update Score if GOAL
        if ($type === 'GOAL') {
            // Determine which team score to increment
             $match = queryOne("SELECT team_a_id, team_b_id FROM matches WHERE id = ?", [$matchId]);
             
             if ($match['team_a_id'] == $teamId) {
                 execute("UPDATE matches SET score_team_a = score_team_a + 1 WHERE id = ?", [$matchId]);
             } else {
                 execute("UPDATE matches SET score_team_b = score_team_b + 1 WHERE id = ?", [$matchId]);
             }
        }
        
        echo json_encode(['success' => true]);
        
    } elseif ($action === 'status') {
        $matchId = $input['match_id'];
        $status = $input['status'];
        
        $sql = "UPDATE matches SET status = ?";
        if ($status === 'live') {
            $sql .= ", start_time = NOW()";
        } elseif ($status === 'finished') {
            $sql .= ", end_time = NOW()";
        }
        $sql .= " WHERE id = ?";
        
        if (execute($sql, [$status, $matchId])) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Falha ao atualizar o banco de dados']);
        }
        
    } else {
        throw new Exception('Invalid action');
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
