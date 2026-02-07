<?php
// Mock session for Gustavo
session_start();
$_SESSION['user_id'] = 7; // Gustavo's ID from debug scripts
$_SESSION['user_role'] = 'operator';
$_SESSION['logged_in'] = true;

require_once 'config/config.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';

echo "--- API SIMULATION FOR GUSTAVO ---\n";

// Emulate GET request to matches-api.php?action=list
$_GET['action'] = 'list';

try {
    // 1. Check opInfo again in this context
    $userId = 7;
    $opInfo = queryOne("SELECT * FROM competition_operators WHERE user_id = ? AND active = 1", [$userId]);
    echo "Operator Info in DB for user 7:\n";
    print_r($opInfo);

    // 2. Logic from matches-api.php
    $filters = [];
    $params = [];
    if ($opInfo) {
        if ($opInfo['assigned_modality_id']) {
            $filters[] = "m.modality_id = ?";
            $params[] = $opInfo['assigned_modality_id'];
        }
    }
    
    $sql = "SELECT m.id, m.modality_id, m.competition_event_id FROM matches m";
    if (!empty($filters)) {
        $sql .= " WHERE " . implode(" AND ", $filters);
    }
    
    $matches = query($sql, $params);
    echo "\nMatches returned by API logic: " . count($matches) . "\n";
    if (count($matches) > 0) {
        echo "Sample IDs: ";
        foreach (array_slice($matches, 0, 10) as $m) echo $m['id'] . " ";
        echo "\n";
    }

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
