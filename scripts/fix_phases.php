<?php
/**
 * Fix Knockout Phases Script
 * Fixes database records where phase='round_of_16' but should be 'quarter_final' or 'semi_final'
 * based on the number of matches.
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db.php';

header('Content-Type: text/plain');

echo "🔧 Starting Phase Fix...\n\n";

// Find all events/categories with round_of_16 matches
$round16Groups = query("
    SELECT competition_event_id, modality_id, category_id, COUNT(*) as match_count
    FROM matches
    WHERE phase = 'round_of_16'
    GROUP BY competition_event_id, modality_id, category_id
");

$updatedCount = 0;

foreach ($round16Groups as $group) {
    $count = $group['match_count'];
    $newPhase = null;
    $eventId = $group['competition_event_id'];
    $modId = $group['modality_id'];
    $catId = $group['category_id'];
    
    echo "Checking Event $eventId, Mod $modId, Cat $catId: has $count matches in round_of_16... ";
    
    if ($count <= 2) {
        $newPhase = 'semi_final';
    } elseif ($count <= 4) {
        $newPhase = 'quarter_final';
    }
    
    if ($newPhase) {
        echo "Should be '$newPhase'. Updating...\n";
        
        // Update matches
        execute("
            UPDATE matches 
            SET phase = ? 
            WHERE competition_event_id = ? 
            AND modality_id = ? 
            AND category_id = ? 
            AND phase = 'round_of_16'
        ", [$newPhase, $eventId, $modId, $catId]);
        
        $updatedCount += $count;
    } else {
        echo "Correct as Round of 16.\n";
    }
}

echo "\n✅ Fix complete! Updated $updatedCount matches.\n";
