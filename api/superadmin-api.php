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
            
            $pdo = getConnection();
            $pdo->beginTransaction();

            try {
                // 1. Criar a Secretaria
                $sqlSec = "INSERT INTO secretarias (nome, slug, email) VALUES (?, ?, ?)";
                $stmtSec = $pdo->prepare($sqlSec);
                $stmtSec->execute([$data['nome'], $data['slug'], $data['email']]);
                $secretariaId = $pdo->lastInsertId();

                // 2. Criar o Usuário Administrador
                if (!empty($data['password'])) {
                    $hashedPassword = password_hash($data['password'], PASSWORD_BCRYPT);
                    $sqlUser = "INSERT INTO users (secretaria_id, name, email, password, role, is_active) VALUES (?, ?, ?, ?, 'admin', 1)";
                    $stmtUser = $pdo->prepare($sqlUser);
                    $stmtUser->execute([$secretariaId, 'Admin ' . $data['nome'], $data['email'], $hashedPassword]);
                }

                $pdo->commit();
                echo json_encode(['success' => true, 'id' => $secretariaId]);
            } catch (Exception $e) {
                $pdo->rollBack();
                throw $e;
            }
            break;
            
        case 'PUT':
            $data = json_decode(file_get_contents('php://input'), true);
            
            // Validar slug único (exceto para a atual)
            $exists = queryOne("SELECT id FROM secretarias WHERE slug = ? AND id != ?", [$data['slug'], $data['id']]);
            if ($exists) {
                throw new Exception('Este slug já está em uso por outra secretaria.');
            }
            
            $pdo = getConnection();
            $pdo->beginTransaction();

            try {
                // 1. Atualizar a Secretaria
                $sqlSec = "UPDATE secretarias SET nome = ?, slug = ?, email = ?, is_active = ? WHERE id = ?";
                $stmtSec = $pdo->prepare($sqlSec);
                $stmtSec->execute([
                    $data['nome'],
                    $data['slug'],
                    $data['email'] ?? null,
                    $data['is_active'] ? 1 : 0,
                    $data['id']
                ]);

                // 2. Atualizar Senha do Administrador (se fornecida)
                if (!empty($data['password'])) {
                    $hashedPassword = password_hash($data['password'], PASSWORD_BCRYPT);
                    // Procura o admin vinculado a este e-mail nesta secretaria
                    $adminExists = queryOne("SELECT id FROM users WHERE secretaria_id = ? AND role = 'admin'", [$data['id']]);
                    
                    if ($adminExists) {
                        execute("UPDATE users SET password = ?, email = ? WHERE id = ?", [$hashedPassword, $data['email'], $adminExists['id']]);
                    } else {
                        // Se não existir (caso bizarro), cria um novo
                        execute("INSERT INTO users (secretaria_id, name, email, password, role, is_active) VALUES (?, ?, ?, ?, 'admin', 1)", 
                            [$data['id'], 'Admin ' . $data['nome'], $data['email'], $hashedPassword]);
                    }
                }

                $pdo->commit();
                echo json_encode(['success' => true]);
            } catch (Exception $e) {
                $pdo->rollBack();
                throw $e;
            }
            break;
            
        default:
            throw new Exception('Método não permitido');
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
