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
    $action = $input['action'] ?? $_GET['action'] ?? '';

    if ($action === 'ping') {
        echo json_encode(['success' => true, 'message' => 'pong', 'session' => $_SESSION['user_name'] ?? 'Guest']);
    } elseif ($action === 'event') {
        $matchId = $input['match_id'];
        $teamId = $input['team_id'];
        $athleteId = $input['athlete_id'];
        $type = $input['event_type'];
        $time = $input['event_time'] ?? null;
        $athleteIdIn = $input['athlete_id_in'] ?? null;
        
        execute("INSERT INTO match_events (match_id, team_id, athlete_id, athlete_id_in, event_type, event_time, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())", [$matchId, $teamId, $athleteId, $athleteIdIn, $type, $time]);
        
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
            SELECT e.*, 
                   a.name_snapshot as athlete_name, a.jersey_number,
                   a2.name_snapshot as athlete_in_name, a2.jersey_number as jersey_in
            FROM match_events e
            LEFT JOIN competition_team_athletes a ON e.athlete_id = a.id
            LEFT JOIN competition_team_athletes a2 ON e.athlete_id_in = a2.id
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
                $sql .= ", start_time = COALESCE(start_time, NOW())";
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
     } elseif ($action === 'save_appointments') {
        $matchId = $input['match_id'];
        $staff = $input['staff']; // [team_a_coach, team_a_assistant, team_b_coach, team_b_assistant]
        $athletes = $input['athletes']; // [[id, jersey_number], ...]

        beginTransaction();
        try {
            // Update Staff, Referees, Captains and Observations in matches table
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

            // Update jersey numbers
            foreach ($athletes as $at) {
                execute("UPDATE competition_team_athletes SET jersey_number = ? WHERE id = ?", [
                    $at['jersey_number'] ?? null,
                    $at['id']
                ]);
            }

            commit();
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            rollback();
            throw $e;
        }
    } elseif ($action === 'update_lineup') {
        $matchId = $input['match_id'];
        $teamSide = $input['team_side']; // 'A' or 'B'
        $lineup = $input['lineup']; // Array of IDs

        $column = $teamSide === 'A' ? 'team_a_lineup' : 'team_b_lineup';
        execute("UPDATE matches SET $column = ? WHERE id = ?", [json_encode($lineup), $matchId]);
        echo json_encode(['success' => true]);
    } elseif ($action === 'get_match_sumula') {
        $matchId = $input['match_id'] ?? $_GET['match_id'];

        // 1. Match Data
        $match = queryOne("
            SELECT m.*, 
                   t1.school_name_snapshot as team_a_name, 
                   t2.school_name_snapshot as team_b_name,
                   c.name as category_name,
                   mod.name as modality_name
            FROM matches m
            JOIN competition_teams t1 ON m.team_a_id = t1.id
            JOIN competition_teams t2 ON m.team_b_id = t2.id
            JOIN categories c ON m.category_id = c.id
            JOIN modalities mod ON m.modality_id = mod.id
            WHERE m.id = ?
        ", [$matchId]);

        if (!$match) throw new Exception("Partida não encontrada");

        // 2. Athletes
        $athletesA = query("SELECT id, name_snapshot, jersey_number FROM competition_team_athletes WHERE competition_team_id = ?", [$match['team_a_id']]);
        $athletesB = query("SELECT id, name_snapshot, jersey_number FROM competition_team_athletes WHERE competition_team_id = ?", [$match['team_b_id']]);

        // 3. Events
        $events = query("
            SELECT e.*, 
                   a.name_snapshot as athlete_name, a.jersey_number,
                   a2.name_snapshot as athlete_in_name, a2.jersey_number as jersey_in
            FROM match_events e
            LEFT JOIN competition_team_athletes a ON e.athlete_id = a.id
            LEFT JOIN competition_team_athletes a2 ON e.athlete_id_in = a2.id
            WHERE e.match_id = ?
            ORDER BY e.created_at ASC
        ", [$matchId]);

        echo json_encode([
            'success' => true,
            'data' => [
                'match' => $match,
                'athletes_a' => $athletesA,
                'athletes_b' => $athletesB,
                'events' => $events
            ]
        ]);
    } else {
        throw new Exception('Invalid action: ' . $action);
    }
} catch (Exception $e) {
    ob_clean();
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
ob_end_flush();
