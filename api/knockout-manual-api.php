<?php
require_once '../config/config.php';
require_once '../includes/auth.php';
require_once '../includes/db.php';
require_once '../includes/knockout_generator.php';

header('Content-Type: application/json');

try {
    requireLogin(); // Operators can manage knockout
    
    $action = $_GET['action'] ?? $_POST['action'] ?? '';
    
    // ==========================================
    // GET QUALIFIED TEAMS
    // ==========================================
    if ($action === 'qualified_teams') {
        $eventId = $_GET['event_id'];
        $modalityId = $_GET['modality_id'];
        $categoryId = $_GET['category_id'];
        $gender = $_GET['gender'] ?? null;
        
        // Get all teams from group stage
        $sql = "SELECT DISTINCT ct.id, ct.school_name_snapshot as name, ct.group_name as `group`
                FROM competition_teams ct
                WHERE ct.competition_event_id = ?
                AND ct.modality_id = ?
                AND ct.category_id = ?";
        
        $params = [$eventId, $modalityId, $categoryId];
        
        if ($gender) {
            $sql .= " AND ct.gender = ?";
            $params[] = $gender;
        }
        
        $sql .= " ORDER BY ct.group_name, ct.school_name_snapshot";
        
        $teams = query($sql, $params);
        
        // Add position from standings if available
        $standings = calculateGroupStandings($eventId, $modalityId, $categoryId, $gender);
        
        foreach ($teams as &$team) {
            $team['position'] = null;
            foreach ($standings as $groupTeams) {
                foreach ($groupTeams as $standingTeam) {
                    if ($standingTeam['team_id'] == $team['id']) {
                        $team['position'] = $standingTeam['position'];
                        break 2;
                    }
                }
            }
        }
        
        echo json_encode([
            'success' => true,
            'teams' => $teams
        ]);
        
    // ==========================================
    // GET PHASE MATCHES
    // ==========================================
    } elseif ($action === 'phase_matches') {
        $eventId = $_GET['event_id'];
        $modalityId = $_GET['modality_id'];
        $categoryId = $_GET['category_id'];
        $phase = $_GET['phase'];
        $gender = $_GET['gender'] ?? null;
        
        $sql = "SELECT m.*, 
                t1.school_name_snapshot as team_a_name,
                t2.school_name_snapshot as team_b_name
                FROM matches m
                JOIN competition_teams t1 ON m.team_a_id = t1.id
                JOIN competition_teams t2 ON m.team_b_id = t2.id
                WHERE m.competition_event_id = ?
                AND m.modality_id = ?
                AND m.category_id = ?
                AND m.phase = ?";
        
        $params = [$eventId, $modalityId, $categoryId, $phase];
        
        if ($gender) {
            $sql .= " AND (t1.gender = ? OR t2.gender = ?)";
            $params[] = $gender;
            $params[] = $gender;
        }
        
        $sql .= " ORDER BY m.scheduled_time";
        
        $matches = query($sql, $params);
        
        echo json_encode([
            'success' => true,
            'matches' => $matches
        ]);
        
    // ==========================================
    // CREATE MATCH
    // ==========================================
    } elseif ($action === 'create_match') {
        $eventId = $_POST['event_id'];
        $modalityId = $_POST['modality_id'];
        $categoryId = $_POST['category_id'];
        $phase = $_POST['phase'];
        $teamAId = $_POST['team_a_id'];
        $teamBId = $_POST['team_b_id'];
        $scheduledTime = $_POST['scheduled_time'];
        $venue = $_POST['venue'];
        $gender = $_POST['gender'] ?? null;
        
        // Validation 1: Teams must be different
        if ($teamAId == $teamBId) {
            throw new Exception('Os times devem ser diferentes');
        }
        
        // Validation 2: Valid phase
        $validPhases = ['round_of_16', 'quarter_final', 'semi_final', 'final', 'third_place'];
        if (!in_array($phase, $validPhases)) {
            throw new Exception('Fase inválida');
        }
        
        // Validation 3: Teams belong to this competition
        $teamCheck = queryOne("SELECT COUNT(*) as cnt FROM competition_teams 
                               WHERE id IN (?, ?) 
                               AND competition_event_id = ?
                               AND modality_id = ?
                               AND category_id = ?",
                              [$teamAId, $teamBId, $eventId, $modalityId, $categoryId]);
        
        if ($teamCheck['cnt'] != 2) {
            throw new Exception('Um ou ambos os times não pertencem a esta competição');
        }
        
        // Validation 4: Teams not already in another match in this phase
        $duplicateCheck = queryOne("SELECT COUNT(*) as cnt FROM matches 
                                    WHERE competition_event_id = ?
                                    AND modality_id = ?
                                    AND category_id = ?
                                    AND phase = ?
                                    AND (team_a_id IN (?, ?) OR team_b_id IN (?, ?))",
                                   [$eventId, $modalityId, $categoryId, $phase, 
                                    $teamAId, $teamBId, $teamAId, $teamBId]);
        
        if ($duplicateCheck['cnt'] > 0) {
            throw new Exception('Um ou ambos os times já estão em outro confronto desta fase');
        }
        
        // Insert match
        execute("INSERT INTO matches (
            competition_event_id, modality_id, category_id, phase,
            team_a_id, team_b_id, venue, scheduled_time, status
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'scheduled')", [
            $eventId, $modalityId, $categoryId, $phase,
            $teamAId, $teamBId, $venue, $scheduledTime
        ]);
        
        $matchId = lastInsertId();
        
        echo json_encode([
            'success' => true,
            'match_id' => $matchId,
            'message' => 'Confronto criado com sucesso'
        ]);
        
    // ==========================================
    // UPDATE MATCH
    // ==========================================
    } elseif ($action === 'update_match') {
        $matchId = $_POST['match_id'];
        $teamAId = $_POST['team_a_id'];
        $teamBId = $_POST['team_b_id'];
        $scheduledTime = $_POST['scheduled_time'];
        $venue = $_POST['venue'];
        
        // Check if match exists and is scheduled
        $match = queryOne("SELECT * FROM matches WHERE id = ?", [$matchId]);
        
        if (!$match) {
            throw new Exception('Partida não encontrada');
        }
        
        if ($match['status'] !== 'scheduled') {
            throw new Exception('Apenas partidas agendadas podem ser editadas');
        }
        
        // Validation 1: Teams must be different
        if ($teamAId == $teamBId) {
            throw new Exception('Os times devem ser diferentes');
        }
        
        // Validation 2: Teams not already in another match in this phase (excluding current match)
        $duplicateCheck = queryOne("SELECT COUNT(*) as cnt FROM matches 
                                    WHERE id != ?
                                    AND competition_event_id = ?
                                    AND modality_id = ?
                                    AND category_id = ?
                                    AND phase = ?
                                    AND (team_a_id IN (?, ?) OR team_b_id IN (?, ?))",
                                   [$matchId, $match['competition_event_id'], 
                                    $match['modality_id'], $match['category_id'], $match['phase'],
                                    $teamAId, $teamBId, $teamAId, $teamBId]);
        
        if ($duplicateCheck['cnt'] > 0) {
            throw new Exception('Um ou ambos os times já estão em outro confronto desta fase');
        }
        
        // Update match
        execute("UPDATE matches SET 
                 team_a_id = ?, 
                 team_b_id = ?, 
                 scheduled_time = ?, 
                 venue = ?
                 WHERE id = ?",
                [$teamAId, $teamBId, $scheduledTime, $venue, $matchId]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Confronto atualizado com sucesso'
        ]);
        
    // ==========================================
    // DELETE MATCH
    // ==========================================
    } elseif ($action === 'delete_match') {
        $matchId = $_POST['match_id'] ?? $_GET['match_id'];
        
        // Check if match exists and is scheduled
        $match = queryOne("SELECT status FROM matches WHERE id = ?", [$matchId]);
        
        if (!$match) {
            throw new Exception('Partida não encontrada');
        }
        
        if ($match['status'] !== 'scheduled') {
            throw new Exception('Apenas partidas agendadas podem ser excluídas');
        }
        
        // Delete match
        execute("DELETE FROM matches WHERE id = ?", [$matchId]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Confronto excluído com sucesso'
        ]);
        
    // ==========================================
    // GENERATE SUGGESTIONS (FIFA Pattern)
    // ==========================================
    } elseif ($action === 'suggest_fifa') {
        $eventId = $_POST['event_id'];
        $modalityId = $_POST['modality_id'];
        $categoryId = $_POST['category_id'];
        $phase = $_POST['phase'];
        $gender = $_POST['gender'] ?? null;
        
        $qualified = getQualifiedTeams($eventId, $modalityId, $categoryId, $gender, 2);
        
        // Organize by position and group
        $first = [];
        $second = [];
        foreach ($qualified as $team) {
            if ($team['position'] == 1) {
                $first[$team['group']] = $team;
            } else {
                $second[$team['group']] = $team;
            }
        }
        
        // Create matchups (1A vs 2B, 1B vs 2A, etc.)
        $groups = array_keys($first);
        $suggestions = [];
        
        for ($i = 0; $i < count($groups); $i++) {
            $groupA = $groups[$i];
            $groupB = $groups[($i + 1) % count($groups)]; // Next group, wrapping around
            
            if (isset($first[$groupA]) && isset($second[$groupB])) {
                $suggestions[] = [
                    'team_a_id' => $first[$groupA]['team_id'],
                    'team_a_name' => $first[$groupA]['team_name'],
                    'team_b_id' => $second[$groupB]['team_id'],
                    'team_b_name' => $second[$groupB]['team_name']
                ];
            }
        }
        
        echo json_encode([
            'success' => true,
            'suggestions' => $suggestions
        ]);
        
    } else {
        throw new Exception('Ação inválida');
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
