<?php
require_once 'config/config.php';
require_once 'includes/db.php';

echo "--- DATA FIX: Modality Mismatch in Event 3 ---\n";
try {
    // 1. Get Society ID
    $mod = queryOne("SELECT id FROM modalities WHERE name LIKE '%Society%'");
    $socId = $mod['id'];
    echo "Correct Society ID: $socId\n";

    // 2. Update Teams in Event 3
    $teamsUpdated = execute("UPDATE competition_teams SET modality_id = ? WHERE competition_event_id = 3", [$socId]);
    echo "Teams updated in Event 3: $teamsUpdated\n";

    // 3. Update Matches in Event 3
    $matchesUpdated = execute("UPDATE matches SET modality_id = ? WHERE competition_event_id = 3", [$socId]);
    echo "Matches updated in Event 3: $matchesUpdated\n";

    // 4. Update Registrations (if they belong to this flow)
    // Actually, registrations might be shared, but let's see.
    // If we want to be thorough, we'd need to know which schools are in Society.
    // But for now, fixing teams and matches is enough for the dashboard.

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
