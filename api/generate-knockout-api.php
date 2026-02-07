<?php
/**
 * Generate Knockout Matches API
 * Handles creation of knockout phase matches from the dashboard
 */

require_once '../config/config.php';
require_once '../includes/auth.php';
require_once '../includes/db.php';
require_once '../includes/knockout_generator.php';

requireLogin(); // Only operators/admins can generate matches

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    $eventId = $input['event_id'] ?? null;
    $modalityId = $input['modality_id'] ?? null;
    $categoryId = $input['category_id'] ?? null;
    $gender = $input['gender'] ?? 'M';
    $phase = $input['phase'] ?? '';
    
    if (!$eventId || !$modalityId || !$categoryId || !$phase) {
        throw new Exception('Missing required parameters');
    }

    // Settings for generation (could be passed from frontend later)
    // For now, use current time or latest match time
    $baseTime = date('Y-m-d 08:00:00', strtotime('tomorrow'));
    $venue = 'Quadra 1'; // Default venue

    $count = 0;
    switch ($phase) {
        case 'round_of_16':
            $count = generateRoundOf16($eventId, $modalityId, $categoryId, $gender, $baseTime, $venue);
            break;
        case 'quarter_final':
            $count = generateQuarterfinals($eventId, $modalityId, $categoryId, $gender, $baseTime, $venue);
            break;
        case 'semi_final':
            $count = generateSemifinals($eventId, $modalityId, $categoryId, $gender, $baseTime, $venue);
            break;
        case 'final':
            $count = generateFinal($eventId, $modalityId, $categoryId, $gender, $baseTime, $venue);
            break;
        default:
            throw new Exception('Invalid phase for generation');
    }

    echo json_encode([
        'success' => true, 
        'count' => $count,
        'message' => "Success! Generated $count matches for $phase."
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
