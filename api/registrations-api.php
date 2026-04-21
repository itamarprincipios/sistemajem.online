<?php
/**
 * Registrations API - CRUD and Approval Operations
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
                // List all registrations with counts
                $registrations = query("
                    SELECT 
                        r.*,
                        s.name as school_name,
                        m.name as modality_name,
                        c.name as category_name,
                        u.name as professor_name,
                        u.phone as professor_phone,
                        (SELECT COUNT(*) FROM enrollments e WHERE e.registration_id = r.id) as athlete_count
                    FROM registrations r
                    JOIN schools s ON r.school_id = s.id
                    JOIN modalities m ON r.modality_id = m.id
                    JOIN categories c ON r.category_id = c.id
                    LEFT JOIN users u ON r.created_by_user_id = u.id
                    WHERE r.secretaria_id = ?
                    ORDER BY 
                        CASE r.status 
                            WHEN 'pending' THEN 1 
                            WHEN 'approved' THEN 2 
                            WHEN 'rejected' THEN 3 
                        END,
                        r.created_at DESC
                ", [CURRENT_TENANT_ID]);
                
                echo json_encode(['success' => true, 'data' => $registrations]);
                
            } elseif ($action === 'details' && isset($_GET['id'])) {
                // Get registration details with athletes
                // Get registration details with athletes
                $id = $_GET['id'];
                
                $registration = queryOne("
                    SELECT 
                        r.*,
                        s.name as school_name,
                        s.director,
                        s.phone as school_phone,
                        m.name as modality_name,
                        c.name as category_name,
                        c.min_birth_year,
                        c.max_birth_year,
                        u.name as professor_name,
                        u.phone as professor_phone
                    FROM registrations r
                    JOIN schools s ON r.school_id = s.id
                    JOIN modalities m ON r.modality_id = m.id
                    JOIN categories c ON r.category_id = c.id
                    LEFT JOIN users u ON r.created_by_user_id = u.id
                    WHERE r.id = ? AND r.secretaria_id = ?
                ", [$id, CURRENT_TENANT_ID]);
                
                if (!$registration) {
                    throw new Exception('Inscrição não encontrada');
                }
                
                // Get athletes
                $athletes = query("
                    SELECT 
                        st.*,
                        TIMESTAMPDIFF(YEAR, st.birth_date, CURDATE()) as age
                    FROM enrollments e
                    JOIN students st ON e.student_id = st.id
                    WHERE e.registration_id = ?
                    ORDER BY st.name
                ", [$id]);
                
                $registration['athletes'] = $athletes;
                
                echo json_encode(['success' => true, 'data' => $registration]);
                
            } else {
                throw new Exception('Ação não especificada');
            }
            break;
            
        case 'PUT':
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($data['id']) || !isset($data['status'])) {
                throw new Exception('Dados incompletos');
            }
            
            $id = $data['id'];
            $status = $data['status'];
            $rejectionReason = $data['rejection_reason'] ?? null;
            
            if (!in_array($status, ['pending', 'approved', 'rejected'])) {
                throw new Exception('Status inválido');
            }
            
            if ($status === 'rejected' && empty($rejectionReason)) {
                throw new Exception('Motivo da rejeição é obrigatório');
            }
            
            $sql = "UPDATE registrations SET status = ?, rejection_reason = ? WHERE id = ? AND secretaria_id = ?";
            
            if (execute($sql, [$status, $rejectionReason, $id, CURRENT_TENANT_ID])) {
                echo json_encode(['success' => true]);
            } else {
                throw new Exception('Erro ao atualizar inscrição');
            }
            break;

        case 'DELETE':
            $id = $_GET['id'] ?? null;
            
            if (!$id) {
                throw new Exception('ID não fornecido');
            }
            
            // Delete enrollments first
            execute("DELETE FROM enrollments WHERE registration_id = ?", [$id]);
            
            // Delete registration
            if (execute("DELETE FROM registrations WHERE id = ? AND secretaria_id = ?", [$id, CURRENT_TENANT_ID])) {
                echo json_encode(['success' => true]);
            } else {
                throw new Exception('Erro ao excluir equipe');
            }
            break;
            
        default:
            throw new Exception('Método não permitido');
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
