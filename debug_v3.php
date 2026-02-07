<?php
require_once 'config/config.php';
require_once 'includes/db.php';

echo "--- DATA CHECK V3 ---\n";
try {
    $socMod = queryOne("SELECT id FROM modalities WHERE name LIKE '%Society%'");
    $socId = $socMod['id'];
    echo "Society Modality ID: $socId\n";

    $allRegs = queryOne("SELECT COUNT(*) as c FROM registrations WHERE modality_id = ?", [$socId]);
    $appRegs = queryOne("SELECT COUNT(*) as c FROM registrations WHERE modality_id = ? AND status = 'approved'", [$socId]);
    echo "Society Registrations: {$allRegs['c']} total, {$appRegs['c']} approved.\n";

    $events = query("SELECT id, name, active_flag FROM competition_events");
    foreach ($events as $e) {
        $teams = queryOne("SELECT COUNT(*) as c FROM competition_teams WHERE competition_event_id = ? AND modality_id = ?", [$e['id'], $socId]);
        $matches = queryOne("SELECT COUNT(*) as c FROM matches WHERE competition_event_id = ? AND modality_id = ?", [$e['id'], $socId]);
        echo "Event {$e['id']} ({$e['name']}) [Active: {$e['active_flag']}]: {$teams['c']} teams, {$matches['c']} matches (Society).\n";
    }

    $gustavo = queryOne("SELECT id FROM users WHERE name LIKE '%Gustavo%'");
    if ($gustavo) {
        $ops = query("SELECT * FROM competition_operators WHERE user_id = ?", [$gustavo['id']]);
        echo "Gustavo (UID {$gustavo['id']}) assignments: " . count($ops) . "\n";
        foreach ($ops as $op) {
            echo " - Event ID: {$op['competition_event_id']}, Modality ID: {$op['assigned_modality_id']}, Active: {$op['active']}\n";
        }
    }

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
