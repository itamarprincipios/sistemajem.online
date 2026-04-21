<?php
/**
 * Super Admin API - Secretariat Management
 */

require_once '../config/config.php';
require_once '../includes/auth.php';
require_once '../includes/db.php';

requireSuperAdmin();

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

try {
    switch ($method) {
        case 'GET':
            if ($action === 'list') {
                $secretarias = query("
                    SELECT 
                        s.*,
                        (SELECT COUNT(*) FROM schools sc WHERE sc.secretaria_id = s.id) as school_count,
                        (SELECT COUNT(*) FROM users u WHERE u.secretaria_id = s.id AND u.role = 'admin') as admin_count
                    FROM secretarias s
                    ORDER BY s.nome
                ");
                echo json_encode(['success' => true, 'data' => $secretarias]);
            } elseif ($action === 'get' && isset($_GET['id'])) {
                $secretaria = queryOne("SELECT * FROM secretarias WHERE id = ?", [$_GET['id']]);
                echo json_encode(['success' => true, 'data' => $secretaria]);
            }
            break;
            
        case 'POST':
            $data = json_decode(file_get_contents('php://input'), true);
            
            // Validar slug único
            $exists = queryOne("SELECT id FROM secretarias WHERE slug = ?", [$data['slug']]);
            if ($exists) {
                throw new Exception('Este slug já está em uso por outra secretaria.');
            }
            
            $sql = "INSERT INTO secretarias (nome, slug, email) VALUES (?, ?, ?)";
            
            if (execute($sql, [
                $data['nome'],
                $data['slug'],
                $data['email'] ?? null
            ])) {
                echo json_encode(['success' => true, 'id' => lastInsertId()]);
            } else {
                throw new Exception('Erro ao criar secretaria');
            }
            break;
            
        case 'PUT':
            $data = json_decode(file_get_contents('php://input'), true);
            
            // Validar slug único (exceto para a atual)
            $exists = queryOne("SELECT id FROM secretarias WHERE slug = ? AND id != ?", [$data['slug'], $data['id']]);
            if ($exists) {
                throw new Exception('Este slug já está em uso por outra secretaria.');
            }
            
            $sql = "UPDATE secretarias SET nome = ?, slug = ?, email = ?, is_active = ? WHERE id = ?";
            
            if (execute($sql, [
                $data['nome'],
                $data['slug'],
                $data['email'] ?? null,
                $data['is_active'] ? 1 : 0,
                $data['id']
            ])) {
                echo json_encode(['success' => true]);
            } else {
                throw new Exception('Erro ao atualizar secretaria');
            }
            break;
            
        default:
            throw new Exception('Método não permitido');
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
