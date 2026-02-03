<?php
ob_start(); // Buffer at VERY top
require_once '../config/config.php';
require_once '../includes/auth.php';
require_once '../includes/db.php';

header('Content-Type: application/json');

try {
    requireLogin(); 
    
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? '';

    if ($action === 'ping') {
        echo json_encode(['success' => true, 'message' => 'pong', 'session' => $_SESSION['user_name'] ?? 'Guest']);
    } elseif ($action === 'event') {
        $matchId = $input['match_id'];
        $teamId = $input['team_id'];
        $athleteId = $input['athlete_id'];
        $type = $input['event_type'];
        
        execute("INSERT INTO match_events (match_id, team_id, athlete_id, event_type, created_at) VALUES (?, ?, ?, ?, NOW())", [$matchId, $teamId, $athleteId, $type]);
        
        if ($type === 'GOAL') {
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
        $statusRequested = $input['status'];
        
        $count = 0;
        if ($statusRequested === 'live') {
            $count = executeWithCount("UPDATE matches SET status = 'live', start_time = NOW() WHERE id = ?", [$matchId]);
        } elseif ($statusRequested === 'finished') {
            $count = executeWithCount("UPDATE matches SET status = 'finished', end_time = NOW() WHERE id = ?", [$matchId]);
        } else {
            $count = executeWithCount("UPDATE matches SET status = ? WHERE id = ?", [$statusRequested, $matchId]);
        }
        
        if ($count > 0) {
            echo json_encode(['success' => true, 'updated' => $count]);
        } elseif ($count === 0) {
            echo json_encode(['success' => false, 'error' => 'Nenhuma alteração feita. Verifique se o ID existe e se o status já não é este.', 'id' => $matchId]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Erro no banco de dados ao atualizar status']);
        }
    } else {
        throw new Exception('Invalid action: ' . $action);
    }
} catch (Exception $e) {
    ob_clean();
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
ob_end_flush();
