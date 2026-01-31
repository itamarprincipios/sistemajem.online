<?php
/**
 * Professor Teams API - CRUD Operations for Teams
 */

require_once '../config/config.php';
require_once '../includes/auth.php';
require_once '../includes/db.php';

requireProfessor();

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';
$schoolId = getCurrentSchoolId();

if (!$schoolId && $action !== 'list') {
    // For list action we might return empty, but for others we need school_id
    // Actually, even for list we need it to filter.
    // Let's handle it inside the try block or just fail gracefully.
}

try {
    if (!$schoolId) {
        throw new Exception('Erro: Seu usuário não está vinculado a nenhuma escola. Entre em contato com o administrador.');
    }

    switch ($method) {
        case 'GET':
            if ($action === 'list') {
                // List teams for the professor's school
                $teams = query("
                    SELECT 
                        r.*,
                        m.name as modality_name,
                        c.name as category_name,
                        u.name as professor_name,
                        (SELECT COUNT(*) FROM enrollments e WHERE e.registration_id = r.id) as athlete_count
                    FROM registrations r
                    JOIN modalities m ON r.modality_id = m.id
                    JOIN categories c ON r.category_id = c.id
                    LEFT JOIN users u ON r.created_by_user_id = u.id
                    WHERE r.school_id = ?
                    ORDER BY r.created_at DESC
                ", [$schoolId]);
                

                
                $currentUserId = getCurrentUserId();
                
                echo json_encode(['success' => true, 'data' => $teams, 'current_user_id' => $currentUserId]);
                
            } elseif ($action === 'details') {
                $id = $_GET['id'] ?? null;
                if (!$id) throw new Exception('ID não fornecido');
                
                // Get team details
                $team = queryOne("
                    SELECT 
                        r.*,
                        m.name as modality_name,
                        c.name as category_name,
                        s.name as school_name
                    FROM registrations r
                    JOIN modalities m ON r.modality_id = m.id
                    JOIN categories c ON r.category_id = c.id
                    JOIN schools s ON r.school_id = s.id
                    WHERE r.id = ? AND r.school_id = ?
                ", [$id, $schoolId]);
                
                if (!$team) throw new Exception('Equipe não encontrada');
                
                // Get enrolled athletes
                $athletes = query("
                    SELECT s.*, e.id as enrollment_id
                    FROM students s
                    JOIN enrollments e ON s.id = e.student_id
                    WHERE e.registration_id = ?
                    ORDER BY s.name
                ", [$id]);
                
                $team['athletes'] = $athletes;
                
                echo json_encode(['success' => true, 'data' => $team]);
                
            } else {
                throw new Exception('Ação não especificada');
            }
            break;
            
        case 'POST':
            $data = json_decode(file_get_contents('php://input'), true);
            
            if ($action === 'create') {
                // Create new team (registration)
                if (empty($data['modality_id']) || empty($data['category_id']) || empty($data['gender'])) {
                    throw new Exception('Preencha todos os campos obrigatórios');
                }
                
                // Validate team staff fields (required)
                if (empty($data['tecnico_nome']) || empty($data['tecnico_celular'])) {
                    throw new Exception('Preencha os dados do Técnico (nome e celular)');
                }
                if (empty($data['auxiliar_tecnico_nome']) || empty($data['auxiliar_tecnico_celular'])) {
                    throw new Exception('Preencha os dados do Auxiliar Técnico (nome e celular)');
                }
                if (empty($data['chefe_delegacao_nome']) || empty($data['chefe_delegacao_celular'])) {
                    throw new Exception('Preencha os dados do Chefe de Delegação (nome e celular)');
                }
                
                // Check if team already exists
                $exists = queryOne("
                    SELECT id FROM registrations 
                    WHERE school_id = ? AND modality_id = ? AND category_id = ? AND gender = ?
                ", [$schoolId, $data['modality_id'], $data['category_id'], $data['gender']]);
                
                if ($exists) {
                    throw new Exception('Esta equipe já está cadastrada');
                }
                
                $userId = getCurrentUserId();
                
                $sql = "INSERT INTO registrations (
                    school_id, modality_id, category_id, gender, created_by_user_id, status,
                    tecnico_nome, tecnico_celular,
                    auxiliar_tecnico_nome, auxiliar_tecnico_celular,
                    chefe_delegacao_nome, chefe_delegacao_celular
                ) VALUES (?, ?, ?, ?, ?, 'pending', ?, ?, ?, ?, ?, ?)";
                
                if (execute($sql, [
                    $schoolId, 
                    $data['modality_id'], 
                    $data['category_id'], 
                    $data['gender'], 
                    $userId,
                    $data['tecnico_nome'],
                    $data['tecnico_celular'],
                    $data['auxiliar_tecnico_nome'],
                    $data['auxiliar_tecnico_celular'],
                    $data['chefe_delegacao_nome'],
                    $data['chefe_delegacao_celular']
                ])) {
                    echo json_encode(['success' => true, 'id' => lastInsertId()]);
                } else {
                    throw new Exception('Erro ao criar equipe');
                }
                
            } elseif ($action === 'add_athlete') {
                // Add athlete to team
                $teamId = $data['team_id'];
                $studentId = $data['student_id'];
                
                // Verify team ownership
                $team = queryOne("SELECT * FROM registrations WHERE id = ? AND school_id = ?", [$teamId, $schoolId]);
                if (!$team) throw new Exception('Equipe não encontrada');
                
                $userId = getCurrentUserId();
                if ($team['created_by_user_id'] && $team['created_by_user_id'] != $userId) {
                    throw new Exception('Você não tem permissão para editar esta equipe');
                }
                
                // Verify student ownership
                $student = queryOne("SELECT * FROM students WHERE id = ? AND school_id = ?", [$studentId, $schoolId]);
                if (!$student) throw new Exception('Aluno não encontrado');
                
                // Check if already enrolled
                $exists = queryOne("SELECT id FROM enrollments WHERE registration_id = ? AND student_id = ?", [$teamId, $studentId]);
                if ($exists) throw new Exception('Aluno já inscrito nesta equipe');
                
                // Check gender compatibility
                if ($team['gender'] !== 'mixed' && $team['gender'] !== $student['gender']) {
                    throw new Exception('Gênero do aluno incompatível com a equipe');
                }
                
                // Check age compatibility - validate birth year is within category range
                // Get category birth year range
                $category = queryOne("SELECT min_birth_year, max_birth_year FROM categories WHERE id = ?", [$team['category_id']]);
                if (!$category) throw new Exception('Categoria não encontrada');
                
                $birthYear = (int)date('Y', strtotime($student['birth_date']));
                
                if ($birthYear < $category['min_birth_year'] || $birthYear > $category['max_birth_year']) {
                    throw new Exception("Aluno não se enquadra na faixa de anos de nascimento da categoria ({$category['min_birth_year']}-{$category['max_birth_year']}). Ano de nascimento do aluno: {$birthYear}");
                }

                // Check for multi-modality rules
                // Rule: Student can participate in 'Atletismo' + 1 other modality.
                // Cannot participate in 2 "collective" (non-Atletismo) modalities.
                
                // Get current team's modality name
                $currentModality = queryOne("SELECT name FROM modalities WHERE id = ?", [$team['modality_id']]);
                if (!$currentModality) throw new Exception('Modalidade não encontrada');
                
                $currentModalityName = $currentModality['name'];

                // Check if current modality is a type of Atletismo
                $isAtletismo = strpos($currentModalityName, 'Atletismo') !== false;

                // If the new team is NOT Atletismo, we must check if the student is already in another non-Atletismo team
                if (!$isAtletismo) {
                    $existingEnrollments = query("
                        SELECT m.name as modality_name
                        FROM enrollments e
                        JOIN registrations r ON e.registration_id = r.id
                        JOIN modalities m ON r.modality_id = m.id
                        WHERE e.student_id = ?
                    ", [$studentId]);

                    foreach ($existingEnrollments as $enrollment) {
                        // Check if existing enrollment is NOT Atletismo
                        if (strpos($enrollment['modality_name'], 'Atletismo') === false) {
                            throw new Exception("O aluno já está inscrito em outra modalidade coletiva ({$enrollment['modality_name']}). É permitido participar apenas de uma modalidade coletiva + Atletismo.");
                        }
                    }
                }
                
                if (execute("INSERT INTO enrollments (registration_id, student_id) VALUES (?, ?)", [$teamId, $studentId])) {
                    echo json_encode(['success' => true]);
                } else {
                    throw new Exception('Erro ao adicionar aluno');
                }
            }
            break;
            
        case 'DELETE':
            $id = $_GET['id'] ?? null;
            $type = $_GET['type'] ?? 'team';
            
            if ($type === 'team') {
                // Delete team (only if pending)
                $team = queryOne("SELECT status, created_by_user_id FROM registrations WHERE id = ? AND school_id = ?", [$id, $schoolId]);
                
                if (!$team) throw new Exception('Equipe não encontrada');
                
                $userId = getCurrentUserId();
                if ($team['created_by_user_id'] && $team['created_by_user_id'] != $userId) {
                    throw new Exception('Você não tem permissão para excluir esta equipe');
                }
                
                if ($team['status'] !== 'pending') throw new Exception('Apenas equipes pendentes podem ser excluídas');
                
                // Delete enrollments first
                execute("DELETE FROM enrollments WHERE registration_id = ?", [$id]);
                
                if (execute("DELETE FROM registrations WHERE id = ?", [$id])) {
                    echo json_encode(['success' => true]);
                } else {
                    throw new Exception('Erro ao excluir equipe');
                }
                
            } elseif ($type === 'athlete') {
                // Remove athlete from team
                $enrollmentId = $id; // Here ID is enrollment_id
                
                // Verify ownership via join
                $enrollment = queryOne("
                    SELECT e.id, r.created_by_user_id 
                    FROM enrollments e 
                    JOIN registrations r ON e.registration_id = r.id 
                    WHERE e.id = ? AND r.school_id = ?
                ", [$enrollmentId, $schoolId]);
                
                if (!$enrollment) throw new Exception('Inscrição não encontrada');
                
                $userId = getCurrentUserId();
                if ($enrollment['created_by_user_id'] && $enrollment['created_by_user_id'] != $userId) {
                    throw new Exception('Você não tem permissão para remover atletas desta equipe');
                }
                
                if (execute("DELETE FROM enrollments WHERE id = ?", [$enrollmentId])) {
                    echo json_encode(['success' => true]);
                } else {
                    throw new Exception('Erro ao remover aluno');
                }
            }
            break;
            
        default:
            throw new Exception('Método não permitido');
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
