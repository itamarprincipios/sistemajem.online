<?php
/**
 * Professors API - CRUD Operations and Request Approval
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
                // List all active professors
                $professors = query("
                    SELECT u.*, s.name as school_name 
                    FROM users u
                    LEFT JOIN schools s ON u.school_id = s.id
                    WHERE u.role = 'professor' AND u.is_active = 1 AND u.secretaria_id = ?
                    ORDER BY u.name
                ", [CURRENT_TENANT_ID]);
                echo json_encode(['success' => true, 'data' => $professors]);
                
            } elseif ($action === 'requests') {
                // List pending inactive professors (waiting for approval)
                $requests = query("
                    SELECT u.*, s.name as school_name 
                    FROM users u
                    LEFT JOIN schools s ON u.school_id = s.id
                    WHERE u.role = 'professor' AND u.is_active = 0 AND u.secretaria_id = ?
                    ORDER BY u.created_at DESC
                ", [CURRENT_TENANT_ID]);
                echo json_encode(['success' => true, 'data' => $requests]);
                
            } elseif ($action === 'get' && isset($_GET['id'])) {
                $professor = queryOne("SELECT * FROM users WHERE id = ? AND role = 'professor' AND secretaria_id = ?", [$_GET['id'], CURRENT_TENANT_ID]);
                if ($professor) {
                    unset($professor['password']); // Don't send password
                }
                echo json_encode(['success' => true, 'data' => $professor]);
                
            } else {
                throw new Exception('Ação não especificada');
            }
            break;
            
        case 'POST':
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (isset($data['action'])) {
                // Handle special actions
                if ($data['action'] === 'approve_request') {
                    // Approve registration (activate user)
                    $userId = $data['request_id'];
                    
                    if (execute("UPDATE users SET is_active = 1 WHERE id = ? AND secretaria_id = ?", [$userId, CURRENT_TENANT_ID])) {
                        echo json_encode(['success' => true, 'message' => 'Professor aprovado com sucesso']);
                    } else {
                        throw new Exception('Erro ao aprovar professor');
                    }
                    
                } elseif ($data['action'] === 'reject_request') {
                    // Reject registration (delete user)
                    $userId = $data['request_id'];
                    
                    if (execute("DELETE FROM users WHERE id = ? AND secretaria_id = ?", [$userId, CURRENT_TENANT_ID])) {
                        echo json_encode(['success' => true]);
                    } else {
                        throw new Exception('Erro ao rejeitar solicitação');
                    }
                    
                } else {
                    throw new Exception('Ação inválida');
                }
            } else {
                // Create new professor (manually by admin - active by default)
                if (emailExists($data['email'])) {
                    throw new Exception('Este email já está cadastrado');
                }
                
                if (!validateCPF($data['cpf'])) {
                    throw new Exception('CPF inválido');
                }
                
                $hashedPassword = hashPassword($data['password']);
                
                $sql = "INSERT INTO users (secretaria_id, name, email, password, cpf, phone, role, school_id, is_active) 
                        VALUES (?, ?, ?, ?, ?, ?, 'professor', ?, 1)";
                
                if (execute($sql, [
                    CURRENT_TENANT_ID,
                    $data['name'],
                    $data['email'],
                    $hashedPassword,
                    $data['cpf'],
                    $data['phone'] ?? null,
                    $data['school_id']
                ])) {
                    echo json_encode(['success' => true, 'id' => lastInsertId()]);
                } else {
                    throw new Exception('Erro ao criar professor');
                }
            }
            break;
            
        case 'PUT':
            $data = json_decode(file_get_contents('php://input'), true);
            
            // Update professor
            if (isset($data['is_active']) && count($data) === 2) {
                // Status toggle only
                $sql = "UPDATE users SET is_active = ? WHERE id = ? AND role = 'professor' AND secretaria_id = ?";
                $params = [$data['is_active'] ? 1 : 0, $data['id'], CURRENT_TENANT_ID];
            } else {
                // Full update
                $sql = "UPDATE users SET name = ?, email = ?, cpf = ?, phone = ?, school_id = ? WHERE id = ? AND role = 'professor' AND secretaria_id = ?";
                $params = [
                    $data['name'],
                    $data['email'],
                    $data['cpf'],
                    $data['phone'] ?? null,
                    $data['school_id'],
                    $data['id'],
                    CURRENT_TENANT_ID
                ];
                
                // Update password if provided
                if (!empty($data['password'])) {
                    $hashedPassword = hashPassword($data['password']);
                    $sql = "UPDATE users SET name = ?, email = ?, cpf = ?, phone = ?, school_id = ?, password = ? WHERE id = ? AND role = 'professor' AND secretaria_id = ?";
                    $params = [
                        $data['name'],
                        $data['email'],
                        $data['cpf'],
                        $data['phone'] ?? null,
                        $data['school_id'],
                        $hashedPassword,
                        $data['id'],
                        CURRENT_TENANT_ID
                    ];
                }
            }
            
            if (execute($sql, $params)) {
                echo json_encode(['success' => true]);
            } else {
                throw new Exception('Erro ao atualizar professor');
            }
            break;
            
        case 'DELETE':
            $id = $_GET['id'] ?? null;
            if (!$id) {
                throw new Exception('ID não fornecido');
            }
            
            // Check if professor has students
            $hasStudents = queryOne("SELECT COUNT(*) as count FROM students WHERE school_id IN (SELECT school_id FROM users WHERE id = ?)", [$id])['count'];
            
            if ($hasStudents > 0) {
                throw new Exception('Não é possível excluir professor com alunos cadastrados');
            }
            
            if (execute("DELETE FROM users WHERE id = ? AND role = 'professor' AND secretaria_id = ?", [$id, CURRENT_TENANT_ID])) {
                echo json_encode(['success' => true]);
            } else {
                throw new Exception('Erro ao excluir professor');
            }
            break;
            
        default:
            throw new Exception('Método não permitido');
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
