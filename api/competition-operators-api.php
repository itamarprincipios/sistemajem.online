<?php
/**
 * Competition Operators API
 * CRUD operations for Competition Operators
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
                $eventId = $_GET['event_id'] ?? null;
                
                $sql = "
                    SELECT co.*, u.name, u.email, u.phone, m.name as modality_name, ce.name as event_name 
                    FROM competition_operators co
                    JOIN users u ON co.user_id = u.id
                    JOIN competition_events ce ON co.competition_event_id = ce.id
                    LEFT JOIN modalities m ON co.assigned_modality_id = m.id
                ";
                
                $params = [];
                if ($eventId) {
                    $sql .= " WHERE co.competition_event_id = ?";
                    $params[] = $eventId;
                }
                
                $sql .= " ORDER BY co.created_at DESC";
                
                $operators = query($sql, $params);
                echo json_encode(['success' => true, 'data' => $operators]);
                
            } elseif ($action === 'events') {
                // Helper to get active events for dropdown
                 $events = query("SELECT id, name FROM competition_events WHERE status != 'finished'");
                 echo json_encode(['success' => true, 'data' => $events]);
                 
            } elseif ($action === 'modalities') {
                // Helper to get modalities
                 $modalities = query("SELECT id, name FROM modalities ORDER BY name");
                 echo json_encode(['success' => true, 'data' => $modalities]);
                 
            } else {
                throw new Exception('Ação inválida');
            }
            break;

        case 'POST':
            $data = json_decode(file_get_contents('php://input'), true);
            
            if ($action === 'create') {
                // 1. Create User (Role Operator)
                $email = $data['email'];
                
                // Check if user exists
                $user = queryOne("SELECT id FROM users WHERE email = ?", [$email]);
                
                if (!$user) {
                    $password = password_hash($data['password'], PASSWORD_BCRYPT);
                    $sqlUser = "INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, 'operator')";
                    if (execute($sqlUser, [$data['name'], $email, $password])) {
                        $userId = lastInsertId();
                    } else {
                        throw new Exception('Erro ao criar usuário');
                    }
                } else {
                    $userId = $user['id'];
                    // Update role if not admin
                     $existingRole = queryOne("SELECT role FROM users WHERE id = ?", [$userId])['role'];
                     if ($existingRole !== 'admin' && $existingRole !== 'operator') {
                         execute("UPDATE users SET role = 'operator' WHERE id = ?", [$userId]);
                     }
                }
                
                // 2. Create Operator Entry
                $sqlOp = "INSERT INTO competition_operators (user_id, competition_event_id, assigned_modality_id, assigned_venue) VALUES (?, ?, ?, ?)";
                
                if (execute($sqlOp, [
                    $userId,
                    $data['competition_event_id'],
                    $data['assigned_modality_id'] ?: null,
                    $data['assigned_venue'] ?: null
                ])) {
                    echo json_encode(['success' => true, 'id' => lastInsertId()]);
                } else {
                    throw new Exception('Erro ao vincular operador');
                }
                
            } else {
                throw new Exception('Ação desconhecida');
            }
            break;

        case 'DELETE':
            $id = $_GET['id'];
            if (!$id) throw new Exception('ID obrigatório');
            
            // Delete operator link (User remains)
             if (execute("DELETE FROM competition_operators WHERE id = ?", [$id])) {
                 echo json_encode(['success' => true]);
             } else {
                 throw new Exception('Erro ao excluir operador');
             }
            break;

        default:
            throw new Exception('Método não permitido');
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
