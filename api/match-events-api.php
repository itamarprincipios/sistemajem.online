<?php
ob_start(); // Buffer at VERY top
require_once '../config/config.php';
require_once '../includes/auth.php';
require_once '../includes/db.php';
require_once '../includes/knockout_generator_v2.php';

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
        $time = $input['event_time'] ?? null;
        
        execute("INSERT INTO match_events (match_id, team_id, athlete_id, event_type, event_time, created_at) VALUES (?, ?, ?, ?, ?, NOW())", [$matchId, $teamId, $athleteId, $type, $time]);
        
        if ($type === 'GOAL') {
             $match = queryOne("SELECT team_a_id, team_b_id FROM matches WHERE id = ?", [$matchId]);
             if ($match['team_a_id'] == $teamId) {
                 execute("UPDATE matches SET score_team_a = score_team_a + 1 WHERE id = ?", [$matchId]);
             } else {
                 execute("UPDATE matches SET score_team_b = score_team_b + 1 WHERE id = ?", [$matchId]);
             }
        }
        echo json_encode(['success' => true]);
        
    } elseif ($action === 'list_events') {
        $matchId = $input['match_id'] ?? $_GET['match_id'];
        
        $events = query("
            SELECT e.*, a.name_snapshot as athlete_name, a.jersey_number
            FROM match_events e
            LEFT JOIN competition_team_athletes a ON e.athlete_id = a.id
            WHERE e.match_id = ?
            ORDER BY e.created_at ASC
        ", [$matchId]);
        
        echo json_encode(['success' => true, 'data' => $events]);
        
    } elseif ($action === 'status') {
        $matchId = $input['match_id'];
        $status = $input['status'];
        
        // Calculate winner when finishing match
        if ($status === 'finished') {
            $match = queryOne("SELECT team_a_id, team_b_id, score_team_a, score_team_b FROM matches WHERE id = ?", [$matchId]);
            
            $winnerId = null;
            if ($match['score_team_a'] > $match['score_team_b']) {
                $winnerId = $match['team_a_id'];
            } elseif ($match['score_team_b'] > $match['score_team_a']) {
                $winnerId = $match['team_b_id'];
            }
            // If tie, winner_team_id stays NULL
            
            $sql = "UPDATE matches SET status = ?, end_time = NOW(), winner_team_id = ? WHERE id = ?";
            $params = [$status, $winnerId, $matchId];
        } else {
            $sql = "UPDATE matches SET status = ?";
            if ($status === 'live') {
                $sql .= ", start_time = NOW()";
            }
            $sql .= " WHERE id = ?";
            $params = [$status, $matchId];
        }
        
        if (execute($sql, $params)) {
            // Automatic phase generation when match is finished
            if ($status === 'finished') {
                try {
                    $generated = checkAndGenerateNextPhase($matchId);
                    if ($generated > 0) {
                        error_log("Auto-generated $generated matches for next phase");
                    }
                } catch (Exception $e) {
                    error_log("Auto-generation failed: " . $e->getMessage());
                }
            }
            
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Erro ao atualizar banco']);
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
