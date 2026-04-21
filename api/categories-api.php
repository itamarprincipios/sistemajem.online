<?php
/**
 * Categories API - CRUD Operations
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
            $categories = query("SELECT * FROM categories WHERE secretaria_id = ? ORDER BY min_birth_year DESC", [CURRENT_TENANT_ID]);
            echo json_encode(['success' => true, 'data' => $categories]);
            break;
            
        case 'POST':
            requireAdmin(); // Only admins can create/modify
            $data = json_decode(file_get_contents('php://input'), true);
            
            $sql = "INSERT INTO categories (secretaria_id, name, min_birth_year, max_birth_year) VALUES (?, ?, ?, ?)";
            
            if (execute($sql, [CURRENT_TENANT_ID, $data['name'], $data['min_birth_year'], $data['max_birth_year']])) {
                echo json_encode(['success' => true, 'id' => lastInsertId()]);
            } else {
                throw new Exception('Erro ao criar categoria');
            }
            break;
            
        case 'PUT':
            requireAdmin(); // Only admins can create/modify
            $data = json_decode(file_get_contents('php://input'), true);
            
            $sql = "UPDATE categories SET name = ?, min_birth_year = ?, max_birth_year = ? WHERE id = ? AND secretaria_id = ?";
            
            if (execute($sql, [$data['name'], $data['min_birth_year'], $data['max_birth_year'], $data['id'], CURRENT_TENANT_ID])) {
                echo json_encode(['success' => true]);
            } else {
                throw new Exception('Erro ao atualizar categoria');
            }
            break;
            
        case 'DELETE':
            requireAdmin(); // Only admins can delete
            $id = $_GET['id'] ?? null;
            if (!$id) {
                throw new Exception('ID não fornecido');
            }
            
            // Check if category has registrations
            $hasRegistrations = queryOne("SELECT COUNT(*) as count FROM registrations WHERE category_id = ? AND secretaria_id = ?", [$id, CURRENT_TENANT_ID])['count'];
            
            if ($hasRegistrations > 0) {
                throw new Exception('Não é possível excluir categoria com inscrições vinculadas');
            }
            
            if (execute("DELETE FROM categories WHERE id = ? AND secretaria_id = ?", [$id, CURRENT_TENANT_ID])) {
                echo json_encode(['success' => true]);
            } else {
                throw new Exception('Erro ao excluir categoria');
            }
            break;
            
        default:
            throw new Exception('Método não permitido');
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
