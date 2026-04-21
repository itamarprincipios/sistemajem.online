<?php
/**
 * Modalities API - CRUD Operations
 */

require_once '../config/config.php';
require_once '../includes/auth.php';
require_once '../includes/db.php';

requireLogin();

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        case 'GET':
            // Allow both admin and professor to read
            $modalities = query("SELECT * FROM modalities WHERE secretaria_id = ? ORDER BY name", [CURRENT_TENANT_ID]);
            echo json_encode(['success' => true, 'data' => $modalities]);
            break;
            
        case 'POST':
            requireAdmin(); // Only admins can create/modify
            $data = json_decode(file_get_contents('php://input'), true);
            
            $sql = "INSERT INTO modalities (secretaria_id, name, allows_mixed) VALUES (?, ?, ?)";
            
            if (execute($sql, [CURRENT_TENANT_ID, $data['name'], $data['allows_mixed'] ? 1 : 0])) {
                echo json_encode(['success' => true, 'id' => lastInsertId()]);
            } else {
                throw new Exception('Erro ao criar modalidade');
            }
            break;
            
        case 'PUT':
            requireAdmin(); // Only admins can create/modify
            $data = json_decode(file_get_contents('php://input'), true);
            
            $sql = "UPDATE modalities SET name = ?, allows_mixed = ? WHERE id = ? AND secretaria_id = ?";
            
            if (execute($sql, [$data['name'], $data['allows_mixed'] ? 1 : 0, $data['id'], CURRENT_TENANT_ID])) {
                echo json_encode(['success' => true]);
            } else {
                throw new Exception('Erro ao atualizar modalidade');
            }
            break;
            
        case 'DELETE':
            requireAdmin(); // Only admins can delete
            $id = $_GET['id'] ?? null;
            if (!$id) {
                throw new Exception('ID não fornecido');
            }
            
            // Check if modality has registrations
            $hasRegistrations = queryOne("SELECT COUNT(*) as count FROM registrations WHERE modality_id = ? AND secretaria_id = ?", [$id, CURRENT_TENANT_ID])['count'];
            
            if ($hasRegistrations > 0) {
                throw new Exception('Não é possível excluir modalidade com inscrições vinculadas');
            }
            
            if (execute("DELETE FROM modalities WHERE id = ? AND secretaria_id = ?", [$id, CURRENT_TENANT_ID])) {
                echo json_encode(['success' => true]);
            } else {
                throw new Exception('Erro ao excluir modalidade');
            }
            break;
            
        default:
            throw new Exception('Método não permitido');
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
