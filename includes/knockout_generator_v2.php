<?php
/**
 * Knockout Generator Library - V2
 * Automatic bracket generation based on group standings and match results
 */

require_once __DIR__ . '/db.php';

/**
 * Calculate group standings for a competition
 */
function calculateGroupStandings($eventId, $modalityId, $categoryId, $gender = null) {
    $sql = "SELECT id, group_name, school_name_snapshot 
            FROM competition_teams 
            WHERE competition_event_id = ? 
            AND modality_id = ? 
            AND category_id = ?";
    
    $params = [$eventId, $modalityId, $categoryId];
    
    if ($gender) {
        $sql .= " AND gender = ?";
        $params[] = $gender;
    }
    
    $teams = query($sql, $params);
    
    // Initialize standings
    $standings = [];
    foreach ($teams as $team) {
        $standings[$team['id']] = [
            'team_id' => $team['id'],
            'team_name' => $team['school_name_snapshot'],
            'group' => $team['group_name'],
            'played' => 0,
            'won' => 0,
            'drawn' => 0,
            'lost' => 0,
            'goals_for' => 0,
            'goals_against' => 0,
            'goal_difference' => 0,
            'points' => 0
        ];
    }
    
    // Get all finished group stage matches
    $matchSql = "SELECT team_a_id, team_b_id, score_team_a, score_team_b, winner_team_id
                 FROM matches 
                 WHERE competition_event_id = ? 
                 AND modality_id = ? 
                 AND category_id = ? 
                 AND phase = 'group_stage' 
                 AND status = 'finished'";
    
    $matches = query($matchSql, $params);
    
    // Calculate standings
    foreach ($matches as $match) {
        $teamA = $match['team_a_id'];
        $teamB = $match['team_b_id'];
        $scoreA = $match['score_team_a'];
        $scoreB = $match['score_team_b'];
        
        // Update played
        $standings[$teamA]['played']++;
        $standings[$teamB]['played']++;
        
        // Update goals
        $standings[$teamA]['goals_for'] += $scoreA;
        $standings[$teamA]['goals_against'] += $scoreB;
        $standings[$teamB]['goals_for'] += $scoreB;
        $standings[$teamB]['goals_against'] += $scoreA;
        
        // Update results
        if ($scoreA > $scoreB) {
            $standings[$teamA]['won']++;
            $standings[$teamA]['points'] += 3;
            $standings[$teamB]['lost']++;
        } elseif ($scoreB > $scoreA) {
            $standings[$teamB]['won']++;
            $standings[$teamB]['points'] += 3;
            $standings[$teamA]['lost']++;
        } else {
            $standings[$teamA]['drawn']++;
            $standings[$teamB]['drawn']++;
            $standings[$teamA]['points'] += 1;
            $standings[$teamB]['points'] += 1;
        }
        
        // Update goal difference
        $standings[$teamA]['goal_difference'] = $standings[$teamA]['goals_for'] - $standings[$teamA]['goals_against'];
        $standings[$teamB]['goal_difference'] = $standings[$teamB]['goals_for'] - $standings[$teamB]['goals_against'];
    }
    
    // Group by group_name
    $groupedStandings = [];
    foreach ($standings as $standing) {
        $group = $standing['group'];
        if (!isset($groupedStandings[$group])) {
            $groupedStandings[$group] = [];
        }
        $groupedStandings[$group][] = $standing;
    }
    
    // Sort each group
    foreach ($groupedStandings as $group => $teams) {
        usort($teams, function($a, $b) {
            if ($a['points'] != $b['points']) return $b['points'] - $a['points'];
            if ($a['goal_difference'] != $b['goal_difference']) return $b['goal_difference'] - $a['goal_difference'];
            if ($a['goals_for'] != $b['goals_for']) return $b['goals_for'] - $a['goals_for'];
            return 0;
        });
        $groupedStandings[$group] = $teams;
    }
    
    return $groupedStandings;
}

/**
 * Check if all matches in a phase are finished
 */
function checkPhaseComplete($eventId, $modalityId, $categoryId, $phase) {
    $result = queryOne("
        SELECT COUNT(*) as total,
               SUM(CASE WHEN status = 'finished' THEN 1 ELSE 0 END) as finished
        FROM matches
        WHERE competition_event_id = ?
        AND modality_id = ?
        AND category_id = ?
        AND phase = ?
    ", [$eventId, $modalityId, $categoryId, $phase]);
    
    return $result['total'] > 0 && $result['total'] == $result['finished'];
}

/**
 * Generate Round of 16 from group standings
 * Applies FIFA-style bracket: 1A vs 2B, 1B vs 2A, etc.
 */
function generateRoundOf16FromGroups($eventId, $modalityId, $categoryId) {
    // Check if groups are complete
    if (!checkPhaseComplete($eventId, $modalityId, $categoryId, 'group_stage')) {
        throw new Exception("Fase de grupos ainda não foi concluída");
    }
    
    // Check if Round of 16 already exists
    $existing = queryOne("
        SELECT COUNT(*) as count 
        FROM matches 
        WHERE competition_event_id = ? 
        AND modality_id = ? 
        AND category_id = ? 
        AND phase = 'round_of_16'
    ", [$eventId, $modalityId, $categoryId]);
    
    if ($existing['count'] > 0) {
        throw new Exception("Oitavas de final já foram geradas");
    }
    
    // Get standings
    $standings = calculateGroupStandings($eventId, $modalityId, $categoryId);
    
    // Get 1st and 2nd place from each group
    $firstPlace = [];
    $secondPlace = [];
    
    foreach ($standings as $group => $teams) {
        if (count($teams) >= 2) {
            $firstPlace[$group] = $teams[0];
            $secondPlace[$group] = $teams[1];
        }
    }
    
    // Create matchups (1A vs 2B, 1B vs 2A, 1C vs 2D, 1D vs 2C)
    $groups = array_keys($firstPlace);
    $matchups = [];
    
    for ($i = 0; $i < count($groups); $i++) {
        $groupA = $groups[$i];
        $groupB = $groups[($i + 1) % count($groups)]; // Next group (circular)
        
        $matchups[] = [
            'team_a' => $firstPlace[$groupA]['team_id'],
            'team_b' => $secondPlace[$groupB]['team_id']
        ];
    }
    
    // Insert matches
    $defaultTime = date('Y-m-d 08:00:00', strtotime('+1 day'));
    $generatedCount = 0;
    
    foreach ($matchups as $matchup) {
        execute("
            INSERT INTO matches 
            (competition_event_id, modality_id, category_id, phase, team_a_id, team_b_id, scheduled_time, venue, status)
            VALUES (?, ?, ?, 'round_of_16', ?, ?, ?, 'A definir', 'scheduled')
        ", [$eventId, $modalityId, $categoryId, $matchup['team_a'], $matchup['team_b'], $defaultTime]);
        
        $generatedCount++;
    }
    
    return $generatedCount;
}

/**
 * Generate next knockout round from previous round winners
 */
function generateNextKnockoutRound($eventId, $modalityId, $categoryId, $currentPhase) {
    // Phase progression map
    $phaseMap = [
        'round_of_16' => 'quarter_final',
        'quarter_final' => 'semi_final',
        'semi_final' => ['final', 'third_place']
    ];
    
    if (!isset($phaseMap[$currentPhase])) {
        throw new Exception("Fase inválida para geração automática");
    }
    
    $nextPhase = $phaseMap[$currentPhase];
    
    // Check if current phase is complete
    if (!checkPhaseComplete($eventId, $modalityId, $categoryId, $currentPhase)) {
        return 0; // Not ready yet
    }
    
    // Check if next phase already exists
    $phasesToCheck = is_array($nextPhase) ? $nextPhase : [$nextPhase];
    foreach ($phasesToCheck as $phase) {
        $existing = queryOne("
            SELECT COUNT(*) as count 
            FROM matches 
            WHERE competition_event_id = ? 
            AND modality_id = ? 
            AND category_id = ? 
            AND phase = ?
        ", [$eventId, $modalityId, $categoryId, $phase]);
        
        if ($existing['count'] > 0) {
            return 0; // Already generated
        }
    }
    
    // Get winners from current phase
    $winners = query("
        SELECT id, winner_team_id
        FROM matches
        WHERE competition_event_id = ?
        AND modality_id = ?
        AND category_id = ?
        AND phase = ?
        AND status = 'finished'
        ORDER BY id ASC
    ", [$eventId, $modalityId, $categoryId, $currentPhase]);
    
    $winnerIds = array_column($winners, 'winner_team_id');
    $matchIds = array_column($winners, 'id');
    
    if (empty($winnerIds)) {
        return 0;
    }
    
    $defaultTime = date('Y-m-d 08:00:00', strtotime('+1 day'));
    $generatedCount = 0;
    
    // Special case: Semifinals generate Final + 3rd Place
    if ($currentPhase === 'semi_final') {
        if (count($winnerIds) >= 2) {
            // Final: Winner 1 vs Winner 2
            execute("
                INSERT INTO matches 
                (competition_event_id, modality_id, category_id, phase, team_a_id, team_b_id, scheduled_time, venue, status, parent_match_id)
                VALUES (?, ?, ?, 'final', ?, ?, ?, 'A definir', 'scheduled', ?)
            ", [$eventId, $modalityId, $categoryId, $winnerIds[0], $winnerIds[1], $defaultTime, $matchIds[0]]);
            $generatedCount++;
            
            // 3rd Place: Loser 1 vs Loser 2
            $losers = query("
                SELECT CASE 
                    WHEN winner_team_id = team_a_id THEN team_b_id 
                    ELSE team_a_id 
                END as loser_team_id,
                id
                FROM matches
                WHERE competition_event_id = ?
                AND modality_id = ?
                AND category_id = ?
                AND phase = 'semi_final'
                AND status = 'finished'
                ORDER BY id ASC
            ", [$eventId, $modalityId, $categoryId]);
            
            if (count($losers) >= 2) {
                execute("
                    INSERT INTO matches 
                    (competition_event_id, modality_id, category_id, phase, team_a_id, team_b_id, scheduled_time, venue, status, parent_match_id)
                    VALUES (?, ?, ?, 'third_place', ?, ?, ?, 'A definir', 'scheduled', ?)
                ", [$eventId, $modalityId, $categoryId, $losers[0]['loser_team_id'], $losers[1]['loser_team_id'], $defaultTime, $losers[0]['id']]);
                $generatedCount++;
            }
        }
    } else {
        // Regular progression: Pair winners sequentially
        for ($i = 0; $i < count($winnerIds) - 1; $i += 2) {
            execute("
                INSERT INTO matches 
                (competition_event_id, modality_id, category_id, phase, team_a_id, team_b_id, scheduled_time, venue, status, parent_match_id)
                VALUES (?, ?, ?, ?, ?, ?, ?, 'A definir', 'scheduled', ?)
            ", [$eventId, $modalityId, $categoryId, $nextPhase, $winnerIds[$i], $winnerIds[$i + 1], $defaultTime, $matchIds[$i]]);
            $generatedCount++;
        }
    }
    
    return $generatedCount;
}

/**
 * Main function: Check and generate next phase after a match finishes
 */
function checkAndGenerateNextPhase($matchId) {
    // Get match details
    $match = queryOne("
        SELECT competition_event_id, modality_id, category_id, phase
        FROM matches
        WHERE id = ?
    ", [$matchId]);
    
    if (!$match) return 0;
    
    $eventId = $match['competition_event_id'];
    $modalityId = $match['modality_id'];
    $categoryId = $match['category_id'];
    $phase = $match['phase'];
    
    // Only auto-generate for knockout phases
    if ($phase === 'group_stage') {
        // Check if groups are complete, then generate Round of 16
        if (checkPhaseComplete($eventId, $modalityId, $categoryId, 'group_stage')) {
            try {
                return generateRoundOf16FromGroups($eventId, $modalityId, $categoryId);
            } catch (Exception $e) {
                error_log("Auto-generation failed: " . $e->getMessage());
                return 0;
            }
        }
    } else {
        // Generate next knockout round
        try {
            return generateNextKnockoutRound($eventId, $modalityId, $categoryId, $phase);
        } catch (Exception $e) {
            error_log("Auto-generation failed: " . $e->getMessage());
            return 0;
        }
    }
    
    return 0;
}
