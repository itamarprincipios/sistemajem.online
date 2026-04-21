<?php
/**
 * Students API - CRUD Operations for Students (Athletes)
 */

require_once '../config/config.php';
require_once '../includes/auth.php';
require_once '../includes/db.php';

requireProfessor();

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';
$schoolId = getCurrentSchoolId();

// Helper to handle file upload
function handleUpload($file, $type) {
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
        return null;
    }
    
    $allowedTypes = ['image/jpeg', 'image/png', 'image/jpg', 'application/pdf'];
    if (!in_array($file['type'], $allowedTypes)) {
        throw new Exception("Tipo de arquivo inválido para $type. Use JPG, PNG ou PDF.");
    }
    
    $uploadDir = '../uploads/' . $type . 's/';
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }
    
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid() . '.' . $extension;
    $targetPath = $uploadDir . $filename;
    
    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
        return 'uploads/' . $type . 's/' . $filename;
    }
    
    throw new Exception("Erro ao salvar arquivo de $type.");
}

try {
    switch ($method) {
        case 'GET':
            if ($action === 'list') {
                // List all students for the school
                $students = query("
                    SELECT s.*, u.name as professor_name 
                    FROM students s
                    LEFT JOIN users u ON s.created_by_user_id = u.id
                    WHERE s.school_id = ? AND s.secretaria_id = ?
                    ORDER BY s.name
                ", [$schoolId, CURRENT_TENANT_ID]);
                
                $currentUserId = getCurrentUserId();
                
                echo json_encode(['success' => true, 'data' => $students, 'current_user_id' => $currentUserId]);
                
            } elseif ($action === 'details') {
                $id = $_GET['id'] ?? null;
                if (!$id) throw new Exception('ID não fornecido');
                
                $student = queryOne("SELECT * FROM students WHERE id = ? AND school_id = ? AND secretaria_id = ?", [$id, $schoolId, CURRENT_TENANT_ID]);
                
                if ($student) {
                    echo json_encode(['success' => true, 'data' => $student]);
                } else {
                    throw new Exception('Aluno não encontrado');
                }
                
            } else {
                throw new Exception('Ação não especificada');
            }
            break;
            
        case 'POST':
            // Handle multipart/form-data
            $data = $_POST;
            
            // Validate Document Number (RG/Certidão)
            if (empty($data['document_number'])) {
                throw new Exception('Número do documento (RG ou Certidão) é obrigatório');
            }
            
            // Validate Phone
            if (empty($data['phone'])) {
                throw new Exception('Telefone é obrigatório');
            }
            
            // Check if Document already exists
            $exists = queryOne("SELECT id FROM students WHERE document_number = ? AND secretaria_id = ? AND id != ?", [$data['document_number'], CURRENT_TENANT_ID, $data['id'] ?? 0]);
            if ($exists) {
                throw new Exception('Documento já cadastrado para outro aluno');
            }
            
            // Calculate age from birth_date
            $birthDate = new DateTime($data['birth_date']);
            $today = new DateTime();
            $age = $today->diff($birthDate)->y;
            
            // Handle file uploads
            $photoPath = null;
            $docPath = null;
            
            if (isset($_FILES['photo'])) {
                $photoPath = handleUpload($_FILES['photo'], 'photo');
            }
            
            if (isset($_FILES['document_photo'])) {
                $docPath = handleUpload($_FILES['document_photo'], 'document');
            }
            
            if (isset($data['id']) && $data['id']) {
                // Update
                $userId = getCurrentUserId();
                
                // Verify ownership
                $student = queryOne("SELECT created_by_user_id FROM students WHERE id = ? AND school_id = ?", [$data['id'], $schoolId]);
                if (!$student) throw new Exception('Aluno não encontrado');
                
                if ($student['created_by_user_id'] && $student['created_by_user_id'] != $userId) {
                    throw new Exception('Você não tem permissão para editar este aluno');
                }
                
                $sql = "UPDATE students SET 
                        name = ?, document_number = ?, birth_date = ?, gender = ?, phone = ?, age = ?";
                $params = [
                    $data['name'], 
                    $data['document_number'], 
                    $data['birth_date'], 
                    $data['gender'],
                    $data['phone'],
                    $age
                ];
                
                if ($photoPath) {
                    $sql .= ", photo_path = ?";
                    $params[] = $photoPath;
                }
                
                if ($docPath) {
                    $sql .= ", document_path = ?";
                    $params[] = $docPath;
                }
                
                $sql .= " WHERE id = ? AND school_id = ? AND secretaria_id = ?";
                $params[] = $data['id'];
                $params[] = $schoolId;
                $params[] = CURRENT_TENANT_ID;
                
                if (execute($sql, $params)) {
                    echo json_encode(['success' => true]);
                } else {
                    throw new Exception('Erro ao atualizar aluno');
                }
                
            } else {
                // Create
                if (!$photoPath || !$docPath) {
                    // Optional: enforce uploads on creation? User said "needs to contain", implying required.
                    // Let's make them required for new students.
                    throw new Exception('Foto do aluno e foto do documento são obrigatórias');
                }
                
                $userId = getCurrentUserId();
                
                if (!$schoolId) {
                    throw new Exception('Erro: Seu usuário não está vinculado a nenhuma escola. Entre em contato com o administrador.');
                }

                $sql = "INSERT INTO students (secretaria_id, school_id, name, document_number, birth_date, gender, phone, age, photo_path, document_path, created_by_user_id) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                
                if (execute($sql, [
                    CURRENT_TENANT_ID,
                    $schoolId,
                    $data['name'], 
                    $data['document_number'], 
                    $data['birth_date'], 
                    $data['gender'],
                    $data['phone'],
                    $age,
                    $photoPath,
                    $docPath,
                    $userId
                ])) {
                    echo json_encode(['success' => true, 'id' => lastInsertId()]);
                } else {
                    throw new Exception('Erro ao cadastrar aluno. Verifique os dados e tente novamente.');
                }
            }
            break;
            
        case 'DELETE':
            $id = $_GET['id'] ?? null;
            if (!$id) throw new Exception('ID não fornecido');
            
            // Check if student is enrolled in any team
            $enrolledResult = queryOne("SELECT COUNT(*) as count FROM enrollments WHERE student_id = ?", [$id]);
            $enrolled = $enrolledResult ? $enrolledResult['count'] : 0;
            
            if ($enrolled > 0) {
                throw new Exception('Não é possível excluir aluno inscrito em equipes');
            }
            
            // Get file paths to delete files
            $student = queryOne("SELECT photo_path, document_path, created_by_user_id FROM students WHERE id = ? AND school_id = ?", [$id, $schoolId]);
            
            if (!$student) throw new Exception('Aluno não encontrado');
            
            $userId = getCurrentUserId();
            if ($student['created_by_user_id'] && $student['created_by_user_id'] != $userId) {
                throw new Exception('Você não tem permissão para excluir este aluno');
            }
            
            if (execute("DELETE FROM students WHERE id = ? AND school_id = ? AND secretaria_id = ?", [$id, $schoolId, CURRENT_TENANT_ID])) {
                // Delete files if they exist
                if ($student['photo_path'] && file_exists('../' . $student['photo_path'])) {
                    unlink('../' . $student['photo_path']);
                }
                if ($student['document_path'] && file_exists('../' . $student['document_path'])) {
                    unlink('../' . $student['document_path']);
                }
                
                echo json_encode(['success' => true]);
            } else {
                throw new Exception('Erro ao excluir aluno');
            }
            break;
            
        default:
            throw new Exception('Método não permitido');
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
