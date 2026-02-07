<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require 'config/config.php';
require 'includes/db.php';

echo "<pre>";
echo "--- GUSTAVO DATA DEBUG ---\n";

try {
    $gustavo = queryOne("SELECT * FROM users WHERE name LIKE '%Gustavo%'");
    if (!$gustavo) {
        echo "User Gustavo NOT FOUND\n";
    } else {
        echo "User: " . $gustavo['name'] . " (ID: " . $gustavo['id'] . ") Role: " . $gustavo['role'] . "\n";
        
        $opInfo = query("SELECT co.*, m.name as modality_name, ce.name as event_name 
                         FROM competition_operators co 
                         LEFT JOIN modalities m ON co.assigned_modality_id = m.id
                         LEFT JOIN competition_events ce ON co.competition_event_id = ce.id
                         WHERE co.user_id = ? AND co.active = 1", [$gustavo['id']]);
        
        echo "Operator entries found: " . count($opInfo) . "\n";
        print_r($opInfo);
        
        foreach ($opInfo as $op) {
            $count = queryOne("SELECT COUNT(*) as c FROM matches WHERE competition_event_id = ? AND modality_id = ?", 
                             [$op['competition_event_id'], $op['assigned_modality_id']]);
            echo "Matches for Event " . $op['competition_event_id'] . " and Modality " . $op['assigned_modality_id'] . ": " . $count['c'] . "\n";
        }
    }

    $allSociety = queryOne("SELECT COUNT(*) as c FROM matches m JOIN modalities mod ON m.modality_id = mod.id WHERE mod.name LIKE '%Society%'");
    echo "Total Society Matches in DB: " . $allSociety['c'] . "\n";

    $activeEvents = query("SELECT id, name FROM competition_events WHERE status != 'finished'");
    echo "Active Events: " . count($activeEvents) . "\n";
    print_r($activeEvents);

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
echo "</pre>";
