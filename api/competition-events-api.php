<?php
/**
 * Competition Events API
 * CRUD operations for Competition Events and Snapshot Logic
 */

require_once '../config/config.php';
require_once '../includes/auth.php';
require_once '../includes/db.php';

requireAdmin();

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

try {
    switch ($method) {
        case 'GET':
            if ($action === 'list') {
                $events = query("SELECT * FROM competition_events ORDER BY created_at DESC");
                echo json_encode(['success' => true, 'data' => $events]);
            } elseif ($action === 'get' && isset($_GET['id'])) {
                $event = queryOne("SELECT * FROM competition_events WHERE id = ?", [$_GET['id']]);
                
                // Get statistics
                $teamCount = queryOne("SELECT COUNT(*) as c FROM competition_teams WHERE competition_event_id = ?", [$_GET['id']])['c'];
                $athleteCount = queryOne("SELECT COUNT(*) as c FROM competition_team_athletes cta JOIN competition_teams ct ON cta.competition_team_id = ct.id WHERE ct.competition_event_id = ?", [$_GET['id']])['c'];
                $matchCount = queryOne("SELECT COUNT(*) as c FROM matches WHERE competition_event_id = ?", [$_GET['id']])['c'];
                
                $event['stats'] = [
                    'teams' => $teamCount,
                    'athletes' => $athleteCount,
                    'matches' => $matchCount
                ];

                echo json_encode(['success' => true, 'data' => $event]);
            } else {
                throw new Exception('Ação inválida');
            }
            break;

        case 'POST':
            $data = json_decode(file_get_contents('php://input'), true);
            
            if ($action === 'create') {
                $sql = "INSERT INTO competition_events (name, start_date, end_date, location_city, status) VALUES (?, ?, ?, ?, 'planning')";
                if (execute($sql, [
                    $data['name'],
                    $data['start_date'],
                    $data['end_date'] ?? null,
                    $data['location_city'] ?? null
                ])) {
                    echo json_encode(['success' => true, 'id' => lastInsertId()]);
                } else {
                    throw new Exception('Erro ao criar evento');
                }
            } elseif ($action === 'snapshot') {
                // IMPORTANT: Generate Snapshot
                $eventId = $data['event_id'];
                
                // 1. Check if event exists
                $event = queryOne("SELECT * FROM competition_events WHERE id = ?", [$eventId]);
                if (!$event) throw new Exception('Evento não encontrado');
                
                // 2. Clear existing snapshot (optional, strict mode for now)
                // execute("DELETE FROM competition_teams WHERE competition_event_id = ?", [$eventId]);
                
                // 3. Get Approved Registrations
                $registrations = query("
                    SELECT r.*, s.name as school_name 
                    FROM registrations r 
                    JOIN schools s ON r.school_id = s.id 
                    WHERE r.status = 'approved'
                ");
                
                $teamsImported = 0;
                $athletesImported = 0;
                
                foreach ($registrations as $reg) {
                    // Check if already imported (idempotency)
                    $exists = queryOne("SELECT id FROM competition_teams WHERE competition_event_id = ? AND registration_id = ?", [$eventId, $reg['id']]);
                    
                    if (!$exists) {
                        // Insert Team
                        execute("
                            INSERT INTO competition_teams 
                            (competition_event_id, registration_id, school_id, modality_id, category_id, gender, school_name_snapshot) 
                            VALUES (?, ?, ?, ?, ?, ?, ?)
                        ", [
                            $eventId, $reg['id'], $reg['school_id'], $reg['modality_id'], $reg['category_id'], $reg['gender'], $reg['school_name']
                        ]);
                        $teamId = lastInsertId();
                        $teamsImported++;
                        
                        // Insert Athletes (Enrollments)
                        $enrollments = query("
                            SELECT e.student_id, s.name 
                            FROM enrollments e 
                            JOIN students s ON e.student_id = s.id 
                            WHERE e.registration_id = ?
                        ", [$reg['id']]);
                        
                        foreach ($enrollments as $enr) {
                            execute("
                                INSERT INTO competition_team_athletes 
                                (competition_team_id, student_id, name_snapshot) 
                                VALUES (?, ?, ?)
                            ", [$teamId, $enr['student_id'], $enr['name']]);
                            $athletesImported++;
                        }
                    }
                }
                
                // Log action
                execute("INSERT INTO audit_logs (user_id, action, entity, entity_id, changes) VALUES (?, 'SNAPSHOT_GENERATE', 'event', ?, ?)", 
                    [getCurrentUserId(), $eventId, json_encode(['teams' => $teamsImported, 'athletes' => $athletesImported])]
                );

                echo json_encode([
                    'success' => true, 
                    'message' => "Snapshot gerado com sucesso! $teamsImported equipes e $athletesImported atletas importados."
                ]);
            } else {
                throw new Exception('Ação desconhecida');
            }
            break;

        case 'PUT':
            $data = json_decode(file_get_contents('php://input'), true);
            $id = $data['id'];
            
            // Allow status change
            if (isset($data['status'])) {
                 $sql = "UPDATE competition_events SET status = ? WHERE id = ?";
                 execute($sql, [$data['status'], $id]);
            }
            
            // Activate Flag
            if (isset($data['active_flag']) && $data['active_flag'] === true) {
                execute("UPDATE competition_events SET active_flag = FALSE"); // Reset others
                execute("UPDATE competition_events SET active_flag = TRUE WHERE id = ?", [$id]);
            }
            
            echo json_encode(['success' => true]);
            break;

        default:
            throw new Exception('Método não permitido');
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
