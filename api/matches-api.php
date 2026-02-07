<?php
/**
 * Matches API
 * Management and Generation of Matches
 */

require_once '../config/config.php';
require_once '../includes/auth.php';
require_once '../includes/db.php';

requireLogin(); // accessible by admin and operator (read-only for operator in some cases)

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

try {
    switch ($method) {
        case 'GET':
            if ($action === 'list') {
                $filters = [];
                $params = [];
                $sql = "
                    SELECT m.*, 
                           t1.school_name_snapshot as team_a_name, 
                           t2.school_name_snapshot as team_b_name,
                           t1.group_name,
                           mdl.name as modality_name,
                           cat.name as category_name,
                           ce.name as event_name
                    FROM matches m
                    LEFT JOIN competition_teams t1 ON m.team_a_id = t1.id
                    LEFT JOIN competition_teams t2 ON m.team_b_id = t2.id
                    LEFT JOIN modalities mdl ON m.modality_id = mdl.id
                    LEFT JOIN categories cat ON m.category_id = cat.id
                    LEFT JOIN competition_events ce ON m.competition_event_id = ce.id
                ";

                if (isset($_GET['event_id']) && $_GET['event_id']) {
                    $filters[] = "m.competition_event_id = ?";
                    $params[] = $_GET['event_id'];
                }
                
                if (isset($_GET['modality_id']) && $_GET['modality_id']) {
                    $filters[] = "m.modality_id = ?";
                    $params[] = $_GET['modality_id'];
                }

                // If Operator, filter by their assignments
                if (!isAdmin()) {
                    $userId = getCurrentUserId();
                    $opInfo = queryOne("SELECT * FROM competition_operators WHERE user_id = ? AND active = 1", [$userId]);
                    if ($opInfo) {
                        if ($opInfo['assigned_modality_id']) {
                            $filters[] = "m.modality_id = ?";
                            $params[] = $opInfo['assigned_modality_id'];
                        }
                        // Note: Venue filter removed to prevent matches from "disappearing" 
                        // when an operator schedules a game for a specific court.
                    }
                }

                if (!empty($filters)) {
                    $sql .= " WHERE " . implode(" AND ", $filters);
                }

                $sql .= " ORDER BY m.scheduled_time ASC, m.id ASC";

                $matches = query($sql, $params);
                
                // Fetch events for these matches (goals/scorers)
                if (!empty($matches)) {
                    $matchIds = array_map(function($m) { return $m['id']; }, $matches);
                    $placeholders = implode(',', array_fill(0, count($matchIds), '?'));
                    
                    $events = query("
                        SELECT me.*, a.name_snapshot as athlete_name
                        FROM match_events me
                        LEFT JOIN competition_team_athletes a ON me.athlete_id = a.id
                        WHERE me.match_id IN ($placeholders) AND me.event_type IN ('GOAL', 'OWN_GOAL', 'YELLOW_CARD', 'RED_CARD')
                        ORDER BY me.created_at ASC
                    ", $matchIds);
                    
                    // Group events by match_id
                    $eventsByMatch = [];
                    foreach ($events as $ev) {
                        $eventsByMatch[$ev['match_id']][] = $ev;
                    }
                    
                    // Attach to matches
                    foreach ($matches as &$m) {
                        $m['events'] = $eventsByMatch[$m['id']] ?? [];
                    }
                }

                ob_clean(); // Safety against whitespace
                
                if ($matches === false) {
                     echo json_encode(['success' => false, 'error' => 'Erro interno ao carregar partidas.']);
                } else {
                     echo json_encode(['success' => true, 'data' => $matches]);
                }
            } elseif ($action === 'options') {
                // Return filters for UI (Modalities/Cats available in competition)
                 $eventId = $_GET['event_id'] ?? 0;
                 
                 $modalities = query("
                    SELECT DISTINCT m.id, m.name 
                    FROM competition_teams ct
                    JOIN modalities m ON ct.modality_id = m.id
                    WHERE ct.competition_event_id = ?
                    ORDER BY m.name
                 ", [$eventId]);
                 
                 $categories = query("
                    SELECT DISTINCT c.id, c.name 
                    FROM competition_teams ct
                    JOIN categories c ON ct.category_id = c.id
                    WHERE ct.competition_event_id = ?
                    ORDER BY c.name
                 ", [$eventId]);
                 
                 echo json_encode(['success' => true, 'data' => [
                     'modalities' => $modalities, 
                     'categories' => $categories
                 ]]);
            } elseif ($action === 'team_counts') {
                // Get team counts by gender for a specific event and category
                $eventId = $_GET['event_id'] ?? 0;
                $categoryId = $_GET['category_id'] ?? 0;
                
                $maleCount = queryOne("
                    SELECT COUNT(*) as count 
                    FROM competition_teams 
                    WHERE competition_event_id = ? AND category_id = ? AND gender = 'M'
                ", [$eventId, $categoryId])['count'] ?? 0;
                
                $femaleCount = queryOne("
                    SELECT COUNT(*) as count 
                    FROM competition_teams 
                    WHERE competition_event_id = ? AND category_id = ? AND gender = 'F'
                ", [$eventId, $categoryId])['count'] ?? 0;
                
                echo json_encode(['success' => true, 'data' => [
                    'male' => (int)$maleCount,
                    'female' => (int)$femaleCount
                ]]);
            }
            if ($action === 'list_groups') {
                $eventId = $_GET['event_id'] ?? 0;
                $categoryId = $_GET['category_id'] ?? 0;
                $gender = $_GET['gender'] ?? 'M';

                $groups = query("
                    SELECT DISTINCT group_name FROM competition_teams 
                    WHERE competition_event_id = ? AND category_id = ? AND gender = ? AND group_name IS NOT NULL
                    ORDER BY group_name
                ", [$eventId, $categoryId, $gender]);
                
                $data = [];
                foreach ($groups as $group) {
                    $groupName = $group['group_name'];
                    $teams = query("
                        SELECT ct.*, s.name as school_name 
                        FROM competition_teams ct
                        JOIN schools s ON ct.school_id = s.id
                        WHERE ct.competition_event_id = ? 
                        AND ct.category_id = ? 
                        AND ct.gender = ? 
                        AND ct.group_name = ?
                        ORDER BY ct.id
                    ", [$eventId, $categoryId, $gender, $groupName]);
                    
                    $data[] = [
                        'group_name' => $groupName,
                        'teams' => $teams
                    ];
                }
                
                echo json_encode(['success' => true, 'data' => $data]);
                exit;
            }

            if ($action === 'list_available_teams') {
                $eventId = $_GET['event_id'] ?? 0;
                $categoryId = $_GET['category_id'] ?? 0;
                $gender = $_GET['gender'] ?? 'M';

                $teams = query("
                    SELECT ct.*, s.name as school_name 
                    FROM competition_teams ct
                    JOIN schools s ON ct.school_id = s.id
                    WHERE ct.competition_event_id = ? 
                    AND ct.category_id = ? 
                    AND ct.gender = ? 
                    AND ct.group_name IS NULL
                    ORDER BY s.name
                ", [$eventId, $categoryId, $gender]);
                
                echo json_encode(['success' => true, 'data' => $teams]);
                exit;
            }
            break;

        case 'POST':
            if (!isAdmin()) throw new Exception('Apenas admin pode gerar jogos');
            
            $data = json_decode(file_get_contents('php://input'), true);
            
            if ($action === 'assign_team_group') {
                $teamId = $data['team_id'];
                $groupName = $data['group_name'];

                execute("UPDATE competition_teams SET group_name = ? WHERE id = ?", [$groupName, $teamId]);
                
                echo json_encode(['success' => true, 'message' => 'Equipe atribuída ao grupo com sucesso!']);
                exit;
            }

            if ($action === 'generate_group_stage') {
                $eventId = $data['event_id'];
                $categoryId = $data['category_id'];
                $gender = $data['gender'];
                
                // 1. Get Teams
                $teams = query("
                    SELECT id FROM competition_teams 
                    WHERE competition_event_id = ? AND category_id = ? AND gender = ?
                ", [$eventId, $categoryId, $gender]);
                
                if (count($teams) < 4) throw new Exception('Mínimo de 4 equipes para gerar fase de grupos');
                
                // 2. Shuffle Teams (Random Draw)
                shuffle($teams);
                
                // 3. Divide into groups of 4
                $teamCount = count($teams);
                $teamsPerGroup = 4;
                $numGroups = min(8, ceil($teamCount / $teamsPerGroup)); // Max 8 groups
                $groupNames = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H'];
                $matchesGenerated = 0;
                
                // Clear existing matches for this category/gender if any?
                // Ideally we should warn the user before this. Assuming UI handles confirmation.
                $futsalId = queryOne("SELECT id FROM modalities WHERE name LIKE '%Futsal%'")['id'] ?? 1; // Fallback
                
                // Clear previous matches for this specific segment
                // This is important to avoid duplicates if re-generating
                execute("
                    DELETE FROM matches 
                    WHERE competition_event_id = ? AND category_id = ? 
                    AND team_a_id IN (SELECT id FROM competition_teams WHERE gender = ?)
                ", [$eventId, $categoryId, $gender]);

                // 4. Assign groups and generate matches
                for ($i = 0; $i < $teamCount; $i++) {
                    $groupIndex = floor($i / $teamsPerGroup);
                    if ($groupIndex >= $numGroups) break; // Safety
                    
                    $groupName = $groupNames[$groupIndex];
                    
                    // Update team group
                    execute("UPDATE competition_teams SET group_name = ? WHERE id = ?", 
                        [$groupName, $teams[$i]['id']]
                    );
                }
                
                // 5. Generate matches for each group
                for ($g = 0; $g < $numGroups; $g++) {
                    $groupName = $groupNames[$g];
                    
                    // Get teams in this group (now assigned)
                    $groupTeams = query("
                        SELECT id FROM competition_teams 
                        WHERE competition_event_id = ? 
                        AND category_id = ? 
                        AND gender = ? 
                        AND group_name = ?
                    ", [$eventId, $categoryId, $gender, $groupName]);
                    
                    // Round robin
                    for ($i = 0; $i < count($groupTeams); $i++) {
                        for ($j = $i + 1; $j < count($groupTeams); $j++) {
                            execute("
                                INSERT INTO matches 
                                (competition_event_id, modality_id, category_id, team_a_id, team_b_id, phase, scheduled_time, status)
                                VALUES (?, ?, ?, ?, ?, 'group_stage', ?, 'scheduled')
                            ", [
                                $eventId,
                                $futsalId,
                                $categoryId,
                                $groupTeams[$i]['id'],
                                $groupTeams[$j]['id'],
                                date('Y-m-d H:i:s', strtotime('+1 day 08:00:00'))
                            ]);
                            $matchesGenerated++;
                        }
                    }
                }
                
                echo json_encode([
                    'success' => true, 
                    'message' => "Sorteio realizado! $numGroups grupos formados e $matchesGenerated partidas geradas."
                ]);
                exit;

            } elseif ($action === 'clear_group_stage') {
                $eventId = $data['event_id'];
                $categoryId = $data['category_id'];
                $gender = $data['gender'];

                // 1. Clear matches for this category/gender
                execute("
                    DELETE FROM matches 
                    WHERE competition_event_id = ? AND category_id = ? 
                    AND team_a_id IN (SELECT id FROM competition_teams WHERE gender = ?)
                ", [$eventId, $categoryId, $gender]);

                // 2. Reset team groups
                execute("
                    UPDATE competition_teams 
                    SET group_name = NULL 
                    WHERE competition_event_id = ? AND category_id = ? AND gender = ?
                ", [$eventId, $categoryId, $gender]);

                echo json_encode(['success' => true, 'message' => 'Grupos e partidas excluídos com sucesso!']);
                exit;

            } elseif ($action === 'generate') {
                $eventId = $data['event_id'];
                $modalityId = $data['modality_id'];
                $categoryId = $data['category_id'];
                $type = $data['type']; // 'round_robin' (todos contra todos) or 'elimination'
                
                // 1. Get Teams
                $teams = query("
                    SELECT id FROM competition_teams 
                    WHERE competition_event_id = ? AND modality_id = ? AND category_id = ?
                ", [$eventId, $modalityId, $categoryId]);
                
                if (count($teams) < 2) throw new Exception('Mínimo de 2 equipes para gerar jogos');
                
                $generatedCount = 0;
                
                if ($type === 'round_robin') {
                    // Algorithm: All vs All
                    for ($i = 0; $i < count($teams); $i++) {
                        for ($j = $i + 1; $j < count($teams); $j++) {
                            $teamA = $teams[$i];
                            $teamB = $teams[$j];
                            
                            $sqlInsert = "
                                INSERT INTO matches 
                                (competition_event_id, modality_id, category_id, team_a_id, team_b_id, phase, scheduled_time, status)
                                VALUES (?, ?, ?, ?, ?, 'group_stage', ?, 'scheduled')
                            ";
                            
                            // Default time: Tomorrow 8am (Placeholder)
                            $defaultTime = date('Y-m-d H:i:s', strtotime('+1 day 08:00:00'));
                            
                            execute($sqlInsert, [$eventId, $modalityId, $categoryId, $teamA['id'], $teamB['id'], $defaultTime]);
                            $generatedCount++;
                        }
                    }
                } elseif ($type === 'groups_knockout') {
                    // 1. Shuffle
                    shuffle($teams);
                    $numTeams = count($teams);
                    
                    // 2. Decide number of groups (approx 4 teams per group)
                    // If 5 teams -> 1 group of 5 or 2 groups (3 and 2)? 
                    // Let's use ceil(N/4)
                    $numGroups = ceil($numTeams / 4);
                    if ($numGroups < 1) $numGroups = 1;
                    
                    $groups = [];
                    for ($i = 0; $i < $numGroups; $i++) {
                        $groups[] = chr(65 + $i); // A, B, C...
                    }
                    
                    // 3. Assign teams to groups and update DB
                    for ($i = 0; $i < $numTeams; $i++) {
                        $groupIndex = $i % $numGroups;
                        $groupName = "Grupo " . $groups[$groupIndex];
                        $teamId = $teams[$i]['id'];
                        
                        execute("UPDATE competition_teams SET group_name = ? WHERE id = ?", [$groupName, $teamId]);
                        $teams[$i]['group_name'] = $groupName;
                    }
                    
                    // 4. Generate matches within each group
                    foreach ($groups as $gChar) {
                        $gName = "Grupo " . $gChar;
                        $groupTeams = array_filter($teams, function($t) use ($gName) {
                            return $t['group_name'] === $gName;
                        });
                        $groupTeams = array_values($groupTeams); // reset keys
                        
                        for ($i = 0; $i < count($groupTeams); $i++) {
                            for ($j = $i + 1; $j < count($groupTeams); $j++) {
                                $teamA = $groupTeams[$i];
                                $teamB = $groupTeams[$j];
                                
                                $sqlInsert = "
                                    INSERT INTO matches 
                                    (competition_event_id, modality_id, category_id, team_a_id, team_b_id, phase, scheduled_time, status)
                                    VALUES (?, ?, ?, ?, ?, 'group_stage', ?, 'scheduled')
                                ";
                                $defaultTime = date('Y-m-d H:i:s', strtotime('+1 day 08:00:00'));
                                execute($sqlInsert, [$eventId, $modalityId, $categoryId, $teamA['id'], $teamB['id'], $defaultTime]);
                                $generatedCount++;
                            }
                        }
                    }
                    
                } else {
                    throw new Exception('Formato ainda não implementado');
                }
                
                execute("INSERT INTO audit_logs (user_id, action, entity, entity_id, changes) VALUES (?, 'MATCH_GENERATE', 'event', ?, ?)", 
                    [getCurrentUserId(), $eventId, "Generated $generatedCount matches"]
                );
                
                echo json_encode(['success' => true, 'message' => "$generatedCount partidas geradas com sucesso!"]);
            }
            break;

        case 'PUT':
            // Update Match (Schedule, Venue)
            $data = json_decode(file_get_contents('php://input'), true);
            $id = $data['id'] ?? null;
            
            if (!$id) throw new Exception('ID da partida obrigatório');
            
            // Build Update Query dynamic
            $fields = [];
            $params = [];
            
            if (isset($data['scheduled_time'])) {
                $fields[] = "scheduled_time = ?";
                // Sanitize: browser sends YYYY-MM-DDTHH:MM, DB needs YYYY-MM-DD HH:MM:SS
                $val = str_replace('T', ' ', $data['scheduled_time']);
                if (strlen($val) === 16) $val .= ':00';
                $params[] = $val;
            }
            
            if (isset($data['venue'])) {
                $fields[] = "venue = ?";
                $params[] = $data['venue'];
            }
            
            if (isset($data['status'])) {
                $fields[] = "status = ?";
                $params[] = $data['status'];
            }

            if (empty($fields)) {
                echo json_encode(['success' => true, 'message' => 'Nada a atualizar']);
                exit;
            }
            
            $params[] = $id;
            $sql = "UPDATE matches SET " . implode(', ', $fields) . " WHERE id = ?";
            
            $pdo = getConnection();
            $stmt = $pdo->prepare($sql);
            if ($stmt->execute($params)) {
                echo json_encode(['success' => true, 'affected' => $stmt->rowCount()]);
            } else {
                throw new Exception('Erro ao atualizar partida no banco');
            }
            break;
            
        case 'DELETE':
             if (!isAdmin()) throw new Exception('Apenas admin pode excluir');
             
             if ($action === 'clear') {
                 $eventId = $_GET['event_id'] ?? null;
                 $modalityId = $_GET['modality_id'] ?? null;
                 $categoryId = $_GET['category_id'] ?? null;
                 
                 if (!$eventId || !$modalityId || !$categoryId) {
                     throw new Exception('Filtros incompletos para limpar lista');
                 }
                 
                 execute("DELETE FROM matches WHERE competition_event_id = ? AND modality_id = ? AND category_id = ?", 
                    [$eventId, $modalityId, $categoryId]);
                    
                 echo json_encode(['success' => true]);
             } else {
                 $id = $_GET['id'];
                 execute("DELETE FROM matches WHERE id = ?", [$id]);
                 echo json_encode(['success' => true]);
             }
             break;

        default:
            throw new Exception('Método não permitido');
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
