<?php
/**
 * Standings API
 * Provides group standings and knockout bracket data for public results page
 */

require_once '../config/config.php';
require_once '../includes/db.php';
require_once '../includes/knockout_generator.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

try {
    $action = $_GET['action'] ?? '';
    
    if ($action === 'group_standings') {
        // Get parameters
        $eventId = $_GET['event_id'] ?? null;
        $modalityId = $_GET['modality_id'] ?? null;
        $categoryId = $_GET['category_id'] ?? null;
        $gender = $_GET['gender'] ?? null;
        
        if (!$eventId || !$modalityId || !$categoryId) {
            throw new Exception('Parâmetros obrigatórios: event_id, modality_id, category_id');
        }
        
        // Calculate standings using existing function
        $standings = calculateGroupStandings($eventId, $modalityId, $categoryId, $gender);
        
        echo json_encode([
            'success' => true,
            'data' => $standings
        ]);
        
    } elseif ($action === 'knockout_bracket') {
        // Get parameters
        $eventId = $_GET['event_id'] ?? null;
        $modalityId = $_GET['modality_id'] ?? null;
        $categoryId = $_GET['category_id'] ?? null;
        $gender = $_GET['gender'] ?? null;
        
        if (!$eventId || !$modalityId || !$categoryId) {
            throw new Exception('Parâmetros obrigatórios: event_id, modality_id, category_id');
        }
        
        // Get all knockout matches
        $sql = "SELECT m.*, 
                t1.school_name_snapshot as team_a_name,
                t2.school_name_snapshot as team_b_name
                FROM matches m
                JOIN competition_teams t1 ON m.team_a_id = t1.id
                JOIN competition_teams t2 ON m.team_b_id = t2.id
                WHERE m.competition_event_id = ? 
                AND m.modality_id = ? 
                AND m.category_id = ? 
                AND m.phase != 'group_stage'
                ORDER BY 
                    FIELD(m.phase, 'round_of_16', 'quarter_final', 'semi_final', 'third_place', 'final'),
                    m.scheduled_time ASC";
        
        $params = [$eventId, $modalityId, $categoryId];
        
        $matches = query($sql, $params);
        
        // Group by phase
        $bracket = [
            'round_of_16' => [],
            'quarter_final' => [],
            'semi_final' => [],
            'third_place' => [],
            'final' => []
        ];
        
        foreach ($matches as $match) {
            $phase = $match['phase'];
            if (isset($bracket[$phase])) {
                $bracket[$phase][] = $match;
            }
        }
        
        echo json_encode([
            'success' => true,
            'data' => $bracket
        ]);
        
    } elseif ($action === 'categories_by_modality') {
        // Get all categories that have matches in a specific modality
        $eventId = $_GET['event_id'] ?? null;
        $modalityId = $_GET['modality_id'] ?? null;
        
        if (!$eventId || !$modalityId) {
            throw new Exception('Parâmetros obrigatórios: event_id, modality_id');
        }
        
        $sql = "SELECT DISTINCT c.id, c.name, c.display_order
                FROM categories c
                JOIN matches m ON m.category_id = c.id
                WHERE m.competition_event_id = ?
                AND m.modality_id = ?
                ORDER BY c.display_order, c.name";
        
        $categories = query($sql, [$eventId, $modalityId]);
        
        echo json_encode([
            'success' => true,
            'data' => $categories
        ]);
        
    } else {
        throw new Exception('Ação inválida. Use: group_standings, knockout_bracket, ou categories_by_modality');
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
