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
                           mod.name as modality_name,
                           cat.name as category_name,
                           ce.name as event_name
                    FROM matches m
                    JOIN competition_teams t1 ON m.team_a_id = t1.id
                    JOIN competition_teams t2 ON m.team_b_id = t2.id
                    JOIN modalities mod ON m.modality_id = mod.id
                    JOIN categories cat ON m.category_id = cat.id
                    JOIN competition_events ce ON m.competition_event_id = ce.id
                ";

                if (isset($_GET['event_id']) && $_GET['event_id']) {
                    $filters[] = "m.competition_event_id = ?";
                    $params[] = $_GET['event_id'];
                }
                
                if (isset($_GET['modality_id']) && $_GET['modality_id']) {
                    $filters[] = "m.modality_id = ?";
                    $params[] = $_GET['modality_id'];
                }

                if (!empty($filters)) {
                    $sql .= " WHERE " . implode(" AND ", $filters);
                }

                $sql .= " ORDER BY m.scheduled_time ASC, m.id ASC";

                // Bypass query() wrapper to debug SQL error
                try {
                    $pdo = getConnection();
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute($params);
                    $matches = $stmt->fetchAll();
                    
                    ob_clean();
                    echo json_encode(['success' => true, 'data' => $matches]);
                } catch (PDOException $e) {
                    ob_clean();
                    echo json_encode(['success' => false, 'error' => 'SQL Error: ' . $e->getMessage()]);
                }
            } else {
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
            }
            break;

        case 'POST':
            if (!isAdmin()) throw new Exception('Apenas admin pode gerar jogos');
            
            $data = json_decode(file_get_contents('php://input'), true);
            
            if ($action === 'generate') {
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
                $params[] = $data['scheduled_time'];
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
            
            if (execute($sql, $params)) {
                echo json_encode(['success' => true]);
            } else {
                throw new Exception('Erro ao atualizar partida');
            }
            break;
            
        case 'DELETE':
             if (!isAdmin()) throw new Exception('Apenas admin pode excluir');
             $id = $_GET['id'];
             execute("DELETE FROM matches WHERE id = ?", [$id]);
             echo json_encode(['success' => true]);
             break;

        default:
            throw new Exception('Método não permitido');
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
