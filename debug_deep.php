<?php
require_once 'config/config.php';
require_once 'includes/db.php';

echo "--- DEEP DATA CHECK ---\n";
try {
    // 1. All Modalities
    $mods = query("SELECT id, name FROM modalities");
    echo "MODALITIES:\n";
    foreach ($mods as $m) {
        echo " - ID: {$m['id']}, Name: {$m['name']}\n";
    }

    // 2. Event 3 Teams
    $teams = query("SELECT modality_id, COUNT(*) as c FROM competition_teams WHERE competition_event_id = 3 GROUP BY modality_id");
    echo "\nTEAMS IN EVENT 3 (by Modality ID):\n";
    foreach ($teams as $t) {
        echo " - Modality ID: {$t['modality_id']}, Count: {$t['c']}\n";
    }

    // 3. Event 3 Matches
    $matches = query("SELECT modality_id, COUNT(*) as c FROM matches WHERE competition_event_id = 3 GROUP BY modality_id");
    echo "\nMATCHES IN EVENT 3 (by Modality ID):\n";
    foreach ($matches as $m) {
        echo " - Modality ID: {$m['modality_id']}, Count: {$m['c']}\n";
    }
    
    // 4. Check if event is active
    $activeId = queryOne("SELECT id FROM competition_events WHERE active_flag = 1");
    echo "\nACTIVE EVENT ID (active_flag=1): " . ($activeId['id'] ?? 'NONE') . "\n";

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
