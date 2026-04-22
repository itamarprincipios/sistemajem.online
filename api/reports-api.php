<?php
/**
 * Reports API - System Statistics and Aggregated Data
 */

require_once '../config/config.php';
require_once '../includes/auth.php';
require_once '../includes/db.php';

requireAdmin();

header('Content-Type: application/json');

try {
    $action = $_GET['action'] ?? 'stats';
    
    if ($action === 'stats') {
        // 1. General Totals
        $totals = [
            'schools' => queryOne("SELECT COUNT(*) as count FROM schools WHERE secretaria_id = ?", [CURRENT_TENANT_ID])['count'],
            'professors' => queryOne("SELECT COUNT(*) as count FROM users WHERE role = 'professor' AND is_active = 1 AND secretaria_id = ?", [CURRENT_TENANT_ID])['count'],
            'students' => queryOne("SELECT COUNT(*) as count FROM students WHERE secretaria_id = ?", [CURRENT_TENANT_ID])['count'],
            'teams' => queryOne("SELECT COUNT(*) as count FROM registrations WHERE status = 'approved' AND secretaria_id = ?", [CURRENT_TENANT_ID])['count']
        ];
        
        // 2. Registrations by Modality
        $byModality = query("
            SELECT 
                m.name,
                COUNT(r.id) as team_count,
                (SELECT COUNT(*) FROM enrollments e JOIN registrations r2 ON e.registration_id = r2.id WHERE r2.modality_id = m.id AND r2.status = 'approved' AND r2.secretaria_id = ?) as student_count
            FROM modalities m
            LEFT JOIN registrations r ON m.id = r.modality_id AND r.status = 'approved' AND r.secretaria_id = ?
            WHERE m.secretaria_id = ?
            GROUP BY m.id, m.name
            ORDER BY team_count DESC
        ", [CURRENT_TENANT_ID, CURRENT_TENANT_ID, CURRENT_TENANT_ID]);
        
        // 3. Registrations by School
        $bySchool = query("
            SELECT 
                s.name,
                COUNT(r.id) as team_count,
                (SELECT COUNT(*) FROM enrollments e JOIN registrations r2 ON e.registration_id = r2.id WHERE r2.school_id = s.id AND r2.status = 'approved' AND r2.secretaria_id = ?) as student_count
            FROM schools s
            LEFT JOIN registrations r ON s.id = r.school_id AND r.status = 'approved' AND r.secretaria_id = ?
            WHERE s.secretaria_id = ?
            GROUP BY s.id, s.name
            ORDER BY team_count DESC
        ", [CURRENT_TENANT_ID, CURRENT_TENANT_ID, CURRENT_TENANT_ID]);
        
        // 4. Registrations by Category
        $byCategory = query("
            SELECT 
                c.name,
                COUNT(r.id) as team_count
            FROM categories c
            LEFT JOIN registrations r ON c.id = r.category_id AND r.status = 'approved' AND r.secretaria_id = ?
            WHERE c.secretaria_id = ?
            GROUP BY c.id, c.name
            ORDER BY c.min_birth_year DESC
        ", [CURRENT_TENANT_ID, CURRENT_TENANT_ID]);
        
        echo json_encode([
            'success' => true,
            'data' => [
                'totals' => $totals,
                'byModality' => $byModality,
                'bySchool' => $bySchool,
                'byCategory' => $byCategory
            ]
        ]);
        
    } elseif ($action === 'detailed_report') {
        // Detailed report with filters
        $schoolId = $_GET['school_id'] ?? '';
        $modalityId = $_GET['modality_id'] ?? '';
        $categoryId = $_GET['category_id'] ?? '';
        $gender = $_GET['gender'] ?? '';
        $status = $_GET['status'] ?? '';

        $params = [CURRENT_TENANT_ID];
        $where = ["r.secretaria_id = ?"];

        if ($schoolId) {
            $where[] = "r.school_id = ?";
            $params[] = $schoolId;
        }
        if ($modalityId) {
            $where[] = "r.modality_id = ?";
            $params[] = $modalityId;
        }
        if ($categoryId) {
            $where[] = "r.category_id = ?";
            $params[] = $categoryId;
        }
        if ($gender) {
            $where[] = "r.gender = ?";
            $params[] = $gender;
        }
        if ($status) {
            $where[] = "r.status = ?";
            $params[] = $status;
        }

        $whereSql = implode(' AND ', $where);

        $sql = "
            SELECT 
                r.id,
                r.gender,
                r.status,
                s.name as school_name,
                m.name as modality_name,
                c.name as category_name,
                u.name as professor_name,
                (SELECT COUNT(*) FROM enrollments e WHERE e.registration_id = r.id) as athlete_count
            FROM registrations r
            JOIN schools s ON r.school_id = s.id
            JOIN modalities m ON r.modality_id = m.id
            JOIN categories c ON r.category_id = c.id
            LEFT JOIN users u ON r.created_by_user_id = u.id
            WHERE $whereSql
            ORDER BY s.name, m.name, c.name
        ";

        $results = query($sql, $params);

        // Add Logistics Summary if school is selected
        $summary = null;
        if ($schoolId) {
            $totalTeams = queryOne("SELECT COUNT(*) as c FROM registrations WHERE school_id = ? AND status = 'approved' AND secretaria_id = ?", [$schoolId, CURRENT_TENANT_ID])['c'];
            
            $totalAthletes = queryOne("
                SELECT COUNT(DISTINCT e.student_id) as c 
                FROM enrollments e 
                JOIN registrations r ON e.registration_id = r.id 
                WHERE r.school_id = ? AND r.status = 'approved' AND r.secretaria_id = ?
            ", [$schoolId, CURRENT_TENANT_ID])['c'];

            $totalStaff = queryOne("
                SELECT COUNT(DISTINCT r.created_by_user_id) as c 
                FROM registrations r 
                WHERE r.school_id = ? AND r.status = 'approved' AND r.secretaria_id = ?
            ", [$schoolId, CURRENT_TENANT_ID])['c'];

            $summary = [
                'total_teams' => (int)$totalTeams,
                'total_athletes' => (int)$totalAthletes,
                'total_staff' => (int)$totalStaff,
                'grand_total' => (int)$totalAthletes + (int)$totalStaff
            ];
        }

        echo json_encode(['success' => true, 'data' => $results, 'summary' => $summary]);

    } else {
        throw new Exception('Ação inválida');
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
