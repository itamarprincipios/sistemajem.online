<?php
/**
 * Force Next Phase Generation Script
 * Triggers the automatic generation logic for phases that are complete but didn't generate the next round
 * (e.g. because of code updates or manual data fixes)
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/knockout_generator_v2.php';

header('Content-Type: text/plain');

echo "🚀 Starting Force Phase Generation...\n\n";

// 1. Find all categories that have finished matches in quarter_final
// We focus on quarter_final -> semi_final transition based on user request
$phasesToCheck = ['quarter_final'];

foreach ($phasesToCheck as $currentPhase) {
    echo "Checking phase: $currentPhase\n";
    
    // Get unique categories in this phase
    $categories = query("
        SELECT DISTINCT competition_event_id, modality_id, category_id 
        FROM matches 
        WHERE phase = ?
    ", [$currentPhase]);
    
    foreach ($categories as $cat) {
        $eventId = $cat['competition_event_id'];
        $modId = $cat['modality_id'];
        $catId = $cat['category_id'];
        
        echo "  - Checking Category $catId (Event $eventId)... ";
        
        // Check if phase is complete
        if (checkPhaseComplete($eventId, $modId, $catId, $currentPhase)) {
            echo "Phase complete! Attempting generation... ";
            
            try {
                $generated = generateNextKnockoutRound($eventId, $modId, $catId, $currentPhase);
                
                if ($generated > 0) {
                    echo "✅ SUCCESS: Generated $generated matches for next phase.\n";
                } else {
                    echo "⚠️ No matches generated (maybe already exists or no winners).\n";
                }
            } catch (Exception $e) {
                echo "❌ ERROR: " . $e->getMessage() . "\n";
            }
        } else {
            echo "Phase NOT complete or no matches. Skipping.\n";
        }
    }
}

echo "\n🏁 Process Complete!\n";
