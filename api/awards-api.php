<?php
/**
 * Awards API
 * Management of Individual Awards (Best Player, Best Goalkeeper)
 */

require_once '../config/config.php';
require_once '../includes/auth.php';
require_once '../includes/db.php';

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];

// GET is public, POST requires login
if ($method !== 'GET') {
    requireLogin();
}

try {
    switch ($method) {
        case 'GET':
            $eventId = $_GET['event_id'] ?? null;
            $modalityId = $_GET['modality_id'] ?? null;
            $categoryId = $_GET['category_id'] ?? null;
            $gender = $_GET['gender'] ?? 'M';

            if (!$eventId || !$modalityId || !$categoryId) {
                throw new Exception('Filtros incompletos (event, modality, category).');
            }

            $awards = query("
                SELECT * FROM competition_awards 
                WHERE competition_event_id = ? AND modality_id = ? AND category_id = ? AND gender = ?
            ", [$eventId, $modalityId, $categoryId, $gender]);

            echo json_encode(['success' => true, 'data' => $awards]);
            break;

        case 'POST':
            $data = json_decode(file_get_contents('php://input'), true);
            
            $eventId = $data['event_id'] ?? null;
            $modalityId = $data['modality_id'] ?? null;
            $categoryId = $data['category_id'] ?? null;
            $gender = $data['gender'] ?? 'M';
            $awardType = $data['award_type'] ?? null; // BEST_PLAYER, BEST_GK
            $winnerName = $data['winner_name'] ?? '';
            $schoolName = $data['school_name'] ?? '';

            if (!$eventId || !$modalityId || !$categoryId || !$awardType) {
                throw new Exception('Dados incompletos para salvar premiação.');
            }

            // Check if exists to Update, or Insert
            $existing = queryOne("
                SELECT id FROM competition_awards 
                WHERE competition_event_id = ? AND modality_id = ? AND category_id = ? AND gender = ? AND award_type = ?
            ", [$eventId, $modalityId, $categoryId, $gender, $awardType]);

            if ($existing) {
                execute("
                    UPDATE competition_awards 
                    SET winner_name = ?, school_name = ? 
                    WHERE id = ?
                ", [$winnerName, $schoolName, $existing['id']]);
            } else {
                execute("
                    INSERT INTO competition_awards (competition_event_id, modality_id, category_id, gender, award_type, winner_name, school_name)
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ", [$eventId, $modalityId, $categoryId, $gender, $awardType, $winnerName, $schoolName]);
            }

            echo json_encode(['success' => true, 'message' => 'Premiação salva com sucesso!']);
            break;

        default:
            throw new Exception('Método não permitido.');
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
