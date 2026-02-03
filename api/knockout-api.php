<?php
require_once '../config/config.php';
require_once '../includes/auth.php';
require_once '../includes/db.php';
require_once '../includes/knockout_generator.php';

header('Content-Type: application/json');

try {
    requireLogin(); // Allow operators to manage knockout
    
    $action = $_GET['action'] ?? $_POST['action'] ?? '';
    
    if ($action === 'standings') {
        $eventId = $_GET['event_id'];
        $modalityId = $_GET['modality_id'];
        $categoryId = $_GET['category_id'];
        $gender = $_GET['gender'] ?? null;
        
        $standings = calculateGroupStandings($eventId, $modalityId, $categoryId, $gender);
        
        echo json_encode([
            'success' => true,
            'standings' => $standings
        ]);
        
    } elseif ($action === 'knockout_status') {
        $eventId = $_GET['event_id'];
        $modalityId = $_GET['modality_id'];
        $categoryId = $_GET['category_id'];
        $gender = $_GET['gender'] ?? null;
        
        // Check which phases exist and are complete
        $phases = ['group_stage', 'round_of_16', 'quarter_final', 'semi_final'];
        $status = [
            'can_generate' => false,
            'next_phase' => null,
            'message' => ''
        ];
        
        // Check group stage
        if (isPhaseComplete($eventId, $modalityId, $categoryId, 'group_stage', $gender)) {
            // Check if R16 exists
            $r16Count = queryOne("SELECT COUNT(*) as cnt FROM matches 
                                  WHERE competition_event_id = ? 
                                  AND modality_id = ? 
                                  AND category_id = ? 
                                  AND phase = 'round_of_16'", 
                                 [$eventId, $modalityId, $categoryId])['cnt'];
            
            if ($r16Count == 0) {
                $status['can_generate'] = true;
                $status['next_phase'] = 'round_of_16';
                $status['message'] = 'Fase de Grupos concluída! Pronto para gerar Oitavas de Final.';
            } elseif (isPhaseComplete($eventId, $modalityId, $categoryId, 'round_of_16', $gender)) {
                // Check QF
                $qfCount = queryOne("SELECT COUNT(*) as cnt FROM matches 
                                     WHERE competition_event_id = ? 
                                     AND modality_id = ? 
                                     AND category_id = ? 
                                     AND phase = 'quarter_final'", 
                                    [$eventId, $modalityId, $categoryId])['cnt'];
                
                if ($qfCount == 0) {
                    $status['can_generate'] = true;
                    $status['next_phase'] = 'quarter_final';
                    $status['message'] = 'Oitavas concluídas! Pronto para gerar Quartas de Final.';
                } elseif (isPhaseComplete($eventId, $modalityId, $categoryId, 'quarter_final', $gender)) {
                    // Check SF
                    $sfCount = queryOne("SELECT COUNT(*) as cnt FROM matches 
                                         WHERE competition_event_id = ? 
                                         AND modality_id = ? 
                                         AND category_id = ? 
                                         AND phase = 'semi_final'", 
                                        [$eventId, $modalityId, $categoryId])['cnt'];
                    
                    if ($sfCount == 0) {
                        $status['can_generate'] = true;
                        $status['next_phase'] = 'semi_final';
                        $status['message'] = 'Quartas concluídas! Pronto para gerar Semifinais.';
                    } elseif (isPhaseComplete($eventId, $modalityId, $categoryId, 'semi_final', $gender)) {
                        // Check Final
                        $finalCount = queryOne("SELECT COUNT(*) as cnt FROM matches 
                                                WHERE competition_event_id = ? 
                                                AND modality_id = ? 
                                                AND category_id = ? 
                                                AND phase = 'final'", 
                                               [$eventId, $modalityId, $categoryId])['cnt'];
                        
                        if ($finalCount == 0) {
                            $status['can_generate'] = true;
                            $status['next_phase'] = 'final';
                            $status['message'] = 'Semifinais concluídas! Pronto para gerar a Final.';
                        } else {
                            $status['message'] = 'Competição completa!';
                        }
                    } else {
                        $status['message'] = 'Aguardando conclusão das Semifinais.';
                    }
                } else {
                    $status['message'] = 'Aguardando conclusão das Quartas de Final.';
                }
            } else {
                $status['message'] = 'Aguardando conclusão das Oitavas de Final.';
            }
        } else {
            $status['message'] = 'Aguardando conclusão da Fase de Grupos.';
        }
        
        // Get existing knockout matches
        $matches = query("SELECT m.*, 
                          t1.school_name_snapshot as team_a_name,
                          t2.school_name_snapshot as team_b_name
                          FROM matches m
                          JOIN competition_teams t1 ON m.team_a_id = t1.id
                          JOIN competition_teams t2 ON m.team_b_id = t2.id
                          WHERE m.competition_event_id = ? 
                          AND m.modality_id = ? 
                          AND m.category_id = ? 
                          AND m.phase != 'group_stage'
                          ORDER BY m.phase, m.scheduled_time", 
                        [$eventId, $modalityId, $categoryId]);
        
        echo json_encode([
            'success' => true,
            'status' => $status,
            'matches' => $matches
        ]);
        
    } elseif ($action === 'generate') {
        $eventId = $_POST['event_id'];
        $modalityId = $_POST['modality_id'];
        $categoryId = $_POST['category_id'];
        $gender = $_POST['gender'] ?? null;
        $phase = $_POST['phase'];
        $datetime = $_POST['datetime'];
        $venue = $_POST['venue'];
        
        $matchesCreated = 0;
        
        switch ($phase) {
            case 'round_of_16':
                $matchesCreated = generateRoundOf16($eventId, $modalityId, $categoryId, $gender, $datetime, $venue);
                break;
            case 'quarter_final':
                $matchesCreated = generateQuarterfinals($eventId, $modalityId, $categoryId, $gender, $datetime, $venue);
                break;
            case 'semi_final':
                $matchesCreated = generateSemifinals($eventId, $modalityId, $categoryId, $gender, $datetime, $venue);
                break;
            case 'final':
                $matchesCreated = generateFinal($eventId, $modalityId, $categoryId, $gender, $datetime, $venue);
                break;
            default:
                throw new Exception('Fase inválida');
        }
        
        echo json_encode([
            'success' => true,
            'matches_created' => $matchesCreated
        ]);
        
    } else {
        throw new Exception('Ação inválida');
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
