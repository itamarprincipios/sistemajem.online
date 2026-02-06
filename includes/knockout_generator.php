<?php
/**
 * Knockout Generator Library
 * Handles group standings calculation and knockout bracket generation
 */

/**
 * Calculate group standings for a competition
 * Returns teams ranked by points, goal difference, and goals scored
 */
function calculateGroupStandings($eventId, $modalityId, $categoryId, $gender = null) {
    // Get all teams in this competition
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
    
    $matchParams = [$eventId, $modalityId, $categoryId];
    if ($gender) {
        // Filter by teams of this gender
        $teamIds = array_column($teams, 'id');
        if (empty($teamIds)) return [];
        $placeholders = implode(',', array_fill(0, count($teamIds), '?'));
        $matchSql .= " AND (team_a_id IN ($placeholders) OR team_b_id IN ($placeholders))";
        $matchParams = array_merge($matchParams, $teamIds, $teamIds);
    }
    
    $matches = query($matchSql, $matchParams);
    
    // Process each match
    foreach ($matches as $match) {
        $teamA = $match['team_a_id'];
        $teamB = $match['team_b_id'];
        $scoreA = $match['score_team_a'];
        $scoreB = $match['score_team_b'];
        
        // Skip if teams not in our list
        if (!isset($standings[$teamA]) || !isset($standings[$teamB])) continue;
        
        // Update stats
        $standings[$teamA]['played']++;
        $standings[$teamB]['played']++;
        $standings[$teamA]['goals_for'] += $scoreA;
        $standings[$teamA]['goals_against'] += $scoreB;
        $standings[$teamB]['goals_for'] += $scoreB;
        $standings[$teamB]['goals_against'] += $scoreA;
        
        // Determine result
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
            $standings[$teamA]['points']++;
            $standings[$teamB]['points']++;
        }
    }
    
    // Calculate goal difference
    foreach ($standings as &$team) {
        $team['goal_difference'] = $team['goals_for'] - $team['goals_against'];
    }
    
    // Group by group_name
    $groupedStandings = [];
    foreach ($standings as $team) {
        $group = $team['group'] ?? 'Único';
        if (!isset($groupedStandings[$group])) {
            $groupedStandings[$group] = [];
        }
        $groupedStandings[$group][] = $team;
    }
    
    // Sort each group
    foreach ($groupedStandings as $group => &$teams) {
        usort($teams, function($a, $b) {
            // 1. Points
            if ($a['points'] != $b['points']) return $b['points'] - $a['points'];
            // 2. Goal difference
            if ($a['goal_difference'] != $b['goal_difference']) return $b['goal_difference'] - $a['goal_difference'];
            // 3. Goals scored
            if ($a['goals_for'] != $b['goals_for']) return $b['goals_for'] - $a['goals_for'];
            // 4. Alphabetical (tie)
            return strcmp($a['team_name'], $b['team_name']);
        });
        
        // Add position
        foreach ($teams as $idx => &$team) {
            $team['position'] = $idx + 1;
        }
    }
    
    return $groupedStandings;
}

/**
 * Get qualified teams for knockout stage
 * Returns top N teams from each group
 */
function getQualifiedTeams($eventId, $modalityId, $categoryId, $gender = null, $teamsPerGroup = 2) {
    $standings = calculateGroupStandings($eventId, $modalityId, $categoryId, $gender);
    
    $qualified = [];
    foreach ($standings as $groupName => $teams) {
        $groupQualified = array_slice($teams, 0, $teamsPerGroup);
        foreach ($groupQualified as $team) {
            $qualified[] = [
                'team_id' => $team['team_id'],
                'team_name' => $team['team_name'],
                'group' => $groupName,
                'position' => $team['position']
            ];
        }
    }
    
    return $qualified;
}

/**
    $sql = "SELECT COUNT(*) as total, 
            SUM(CASE WHEN status = 'finished' THEN 1 ELSE 0 END) as finished
            FROM matches 
            WHERE competition_event_id = ? 
            AND modality_id = ? 
            AND category_id = ? 
            AND phase = ?";
    
    $params = [$eventId, $modalityId, $categoryId, $phase];
    
    $result = queryOne($sql, $params);
    
    return $result['total'] > 0 && $result['total'] == $result['finished'];
}

/**
 * Generate Round of 16 matches
 * Matchups: 1A vs 2B, 1B vs 2A, 1C vs 2D, 1D vs 2C, etc.
 */
function generateRoundOf16($eventId, $modalityId, $categoryId, $gender, $baseDateTime, $venue) {
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
    $matches = [];
    
    for ($i = 0; $i < count($groups); $i++) {
        $groupA = $groups[$i];
        $groupB = $groups[($i + 1) % count($groups)]; // Next group, wrapping around
        
        if (isset($first[$groupA]) && isset($second[$groupB])) {
            $matches[] = [
                'team_a' => $first[$groupA],
                'team_b' => $second[$groupB]
            ];
        }
    }
    
    // Insert matches
    $datetime = new DateTime($baseDateTime);
    foreach ($matches as $idx => $match) {
        $scheduledTime = clone $datetime;
        $scheduledTime->modify('+' . ($idx * 60) . ' minutes'); // 60 min apart
        
        execute("INSERT INTO matches (
            competition_event_id, modality_id, category_id, phase,
            team_a_id, team_b_id, venue, scheduled_time, status
        ) VALUES (?, ?, ?, 'round_of_16', ?, ?, ?, ?, 'scheduled')", [
            $eventId, $modalityId, $categoryId,
            $match['team_a']['team_id'],
            $match['team_b']['team_id'],
            $venue,
            $scheduledTime->format('Y-m-d H:i:s')
        ]);
    }
    
    return count($matches);
}

/**
 * Generate Quarterfinals from Round of 16 winners
 */
function generateQuarterfinals($eventId, $modalityId, $categoryId, $gender, $baseDateTime, $venue) {
    // Get R16 matches in order
    $r16Matches = query("SELECT id, winner_team_id 
                         FROM matches 
                         WHERE competition_event_id = ? 
                         AND modality_id = ? 
                         AND category_id = ? 
                         AND phase = 'round_of_16' 
                         AND status = 'finished'
                         ORDER BY scheduled_time ASC", 
                        [$eventId, $modalityId, $categoryId]);
    
    if (count($r16Matches) < 8) {
        throw new Exception("Round of 16 não está completo");
    }
    
    // Create 4 QF matches from 8 winners
    $datetime = new DateTime($baseDateTime);
    $matchesCreated = 0;
    
    for ($i = 0; $i < 8; $i += 2) {
        $teamA = $r16Matches[$i]['winner_team_id'];
        $teamB = $r16Matches[$i + 1]['winner_team_id'];
        
        if (!$teamA || !$teamB) {
            throw new Exception("Vencedores das Oitavas ainda não definidos");
        }
        
        $scheduledTime = clone $datetime;
        $scheduledTime->modify('+' . ($matchesCreated * 60) . ' minutes');
        
        execute("INSERT INTO matches (
            competition_event_id, modality_id, category_id, phase,
            team_a_id, team_b_id, venue, scheduled_time, status
        ) VALUES (?, ?, ?, 'quarter_final', ?, ?, ?, ?, 'scheduled')", [
            $eventId, $modalityId, $categoryId,
            $teamA, $teamB, $venue,
            $scheduledTime->format('Y-m-d H:i:s')
        ]);
        
        $matchesCreated++;
    }
    
    return $matchesCreated;
}

/**
 * Generate Semifinals from Quarterfinal winners
 */
function generateSemifinals($eventId, $modalityId, $categoryId, $gender, $baseDateTime, $venue) {
    $qfMatches = query("SELECT id, winner_team_id 
                        FROM matches 
                        WHERE competition_event_id = ? 
                        AND modality_id = ? 
                        AND category_id = ? 
                        AND phase = 'quarter_final' 
                        AND status = 'finished'
                        ORDER BY scheduled_time ASC", 
                       [$eventId, $modalityId, $categoryId]);
    
    if (count($qfMatches) < 4) {
        throw new Exception("Quartas de Final não estão completas");
    }
    
    $datetime = new DateTime($baseDateTime);
    $matchesCreated = 0;
    
    for ($i = 0; $i < 4; $i += 2) {
        $teamA = $qfMatches[$i]['winner_team_id'];
        $teamB = $qfMatches[$i + 1]['winner_team_id'];
        
        if (!$teamA || !$teamB) {
            throw new Exception("Vencedores das Quartas ainda não definidos");
        }
        
        $scheduledTime = clone $datetime;
        $scheduledTime->modify('+' . ($matchesCreated * 60) . ' minutes');
        
        execute("INSERT INTO matches (
            competition_event_id, modality_id, category_id, phase,
            team_a_id, team_b_id, venue, scheduled_time, status
        ) VALUES (?, ?, ?, 'semi_final', ?, ?, ?, ?, 'scheduled')", [
            $eventId, $modalityId, $categoryId,
            $teamA, $teamB, $venue,
            $scheduledTime->format('Y-m-d H:i:s')
        ]);
        
        $matchesCreated++;
    }
    
    return $matchesCreated;
}

/**
 * Generate Final from Semifinal winners
 */
function generateFinal($eventId, $modalityId, $categoryId, $gender, $baseDateTime, $venue) {
    $sfMatches = query("SELECT id, winner_team_id 
                        FROM matches 
                        WHERE competition_event_id = ? 
                        AND modality_id = ? 
                        AND category_id = ? 
                        AND phase = 'semi_final' 
                        AND status = 'finished'
                        ORDER BY scheduled_time ASC", 
                       [$eventId, $modalityId, $categoryId]);
    
    if (count($sfMatches) < 2) {
        throw new Exception("Semifinais não estão completas");
    }
    
    $teamA = $sfMatches[0]['winner_team_id'];
    $teamB = $sfMatches[1]['winner_team_id'];
    
    if (!$teamA || !$teamB) {
        throw new Exception("Vencedores das Semifinais ainda não definidos");
    }
    
    execute("INSERT INTO matches (
        competition_event_id, modality_id, category_id, phase,
        team_a_id, team_b_id, venue, scheduled_time, status
    ) VALUES (?, ?, ?, 'final', ?, ?, ?, ?, 'scheduled')", [
        $eventId, $modalityId, $categoryId,
        $teamA, $teamB, $venue, $baseDateTime
    ]);
    
    return 1;
}


/**
 * Automatically generate next knockout phase when current phase is complete
 * Called after a match is finished
 */
function autoGenerateNextPhase($matchId) {
    // Get match details
    $match = queryOne("
        SELECT m.competition_event_id, m.modality_id, m.category_id, m.phase, t.gender 
        FROM matches m
        LEFT JOIN competition_teams t ON m.team_a_id = t.id
        WHERE m.id = ?
    ", [$matchId]);
    
    if (!$match) return;
    
    // Only process knockout phases (not group stage)
    if ($match['phase'] === 'group_stage') return;
    
    $eventId = $match['competition_event_id'];
    $modalityId = $match['modality_id'];
    $categoryId = $match['category_id'];
    $currentPhase = $match['phase'];
    $gender = $match['gender'];
    
    // Check if all matches in this phase are finished
    if (!isPhaseComplete($eventId, $modalityId, $categoryId, $currentPhase, $gender)) {
        return; // Phase not complete yet
    }
    
    // Define phase progression
    $phaseProgression = [
        'round_of_16' => 'quarter_final',
        'quarter_final' => 'semi_final',
        'semi_final' => ['final', 'third_place'],
        'third_place' => null,
        'final' => null
    ];
    
    $nextPhase = $phaseProgression[$currentPhase] ?? null;
    if (!$nextPhase) return;
    
    // Check if next phase already exists
    $phasesToCheck = is_array($nextPhase) ? $nextPhase : [$nextPhase];
    foreach ($phasesToCheck as $phaseToCheck) {
        $existing = queryOne("
            SELECT COUNT(*) as count 
            FROM matches 
            WHERE competition_event_id = ? 
            AND modality_id = ? 
            AND category_id = ? 
            AND phase = ?
        ", [$eventId, $modalityId, $categoryId, $phaseToCheck]);
        
        if ($existing['count'] > 0) {
            return; // Already generated
        }
    }
    
    // Get winners
    $winners = query("
        SELECT winner_team_id
        FROM matches 
        WHERE competition_event_id = ? 
        AND modality_id = ? 
        AND category_id = ? 
        AND phase = ? 
        AND status = 'finished'
        ORDER BY id ASC
    ", [$eventId, $modalityId, $categoryId, $currentPhase]);
    
    if (empty($winners)) return;
    
    $winnerTeamIds = array_column($winners, 'winner_team_id');
    $defaultTime = date('Y-m-d H:i:s', strtotime('+1 day 08:00:00'));
    $venue = 'A definir';
    $generatedCount = 0;
    
    // Generate matches
    if ($currentPhase === 'semi_final') {
        // Final + 3rd Place
        if (count($winnerTeamIds) >= 2) {
            execute("INSERT INTO matches (competition_event_id, modality_id, category_id, team_a_id, team_b_id, phase, scheduled_time, venue, status) VALUES (?, ?, ?, ?, ?, 'final', ?, ?, 'scheduled')", [$eventId, $modalityId, $categoryId, $winnerTeamIds[0], $winnerTeamIds[1], $defaultTime, $venue]);
            $generatedCount++;
            
            $losers = query("SELECT CASE WHEN winner_team_id = team_a_id THEN team_b_id ELSE team_a_id END as loser_team_id FROM matches WHERE competition_event_id = ? AND modality_id = ? AND category_id = ? AND phase = 'semi_final' AND status = 'finished' ORDER BY id ASC", [$eventId, $modalityId, $categoryId]);
            
            if (count($losers) >= 2) {
                execute("INSERT INTO matches (competition_event_id, modality_id, category_id, team_a_id, team_b_id, phase, scheduled_time, venue, status) VALUES (?, ?, ?, ?, ?, 'third_place', ?, ?, 'scheduled')", [$eventId, $modalityId, $categoryId, $losers[0]['loser_team_id'], $losers[1]['loser_team_id'], $defaultTime, $venue]);
                $generatedCount++;
            }
        }
    } else {
        for ($i = 0; $i < count($winnerTeamIds) - 1; $i += 2) {
            execute("INSERT INTO matches (competition_event_id, modality_id, category_id, team_a_id, team_b_id, phase, scheduled_time, venue, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'scheduled')", [$eventId, $modalityId, $categoryId, $winnerTeamIds[$i], $winnerTeamIds[$i + 1], $nextPhase, $defaultTime, $venue]);
            $generatedCount++;
        }
    }
    
    if ($generatedCount > 0) {
        execute("INSERT INTO audit_logs (user_id, action, entity, entity_id, changes) VALUES (1, 'AUTO_KNOCKOUT_GENERATE', 'match', ?, ?)", [$matchId, "Auto-generated $generatedCount matches for next phase"]);
    }
    
    return $generatedCount;
}
