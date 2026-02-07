<?php
// This script is intended to run on the server to debug data issues.
require_once 'config/config.php';
require_once 'includes/db.php';

$output = "--- SERVER DATA DEBUG ---\n";
$output .= "Time: " . date('Y-m-d H:i:s') . "\n\n";

try {
    // 1. Check Gustavo User
    $u = queryOne("SELECT id, name, role FROM users WHERE name LIKE '%Gustavo%'");
    if ($u) {
        $output .= "User Found: {$u['name']} (ID: {$u['id']}, Role: {$u['role']})\n";
        
        // 2. Check Operator Assignment
        $ops = query("SELECT co.*, m.name as modality_name, ce.name as event_name 
                      FROM competition_operators co 
                      LEFT JOIN modalities m ON co.assigned_modality_id = m.id
                      LEFT JOIN competition_events ce ON co.competition_event_id = ce.id
                      WHERE co.user_id = ?", [$u['id']]);
        $output .= "Operator Entries: " . count($ops) . "\n";
        foreach ($ops as $op) {
            $output .= " - Event: {$op['event_name']} (ID: {$op['competition_event_id']}), Modality: " . ($op['modality_name'] ?? 'Todas') . " (ID: " . ($op['assigned_modality_id'] ?? 'null') . "), Active: {$op['active']}\n";
        }
    } else {
        $output .= "User Gustavo NOT FOUND.\n";
    }

    // 3. Check Society Matches
    $socMatches = queryOne("SELECT COUNT(*) as c FROM matches m JOIN modalities mod ON m.modality_id = mod.id WHERE mod.name LIKE '%Society%'");
    $output .= "\nTotal Society Matches in DB: {$socMatches['c']}\n";

    // 4. Check Categories with Teams
    $cats = query("SELECT mod.name as mod_name, cat.name as cat_name, ct.gender, COUNT(*) as team_count 
                   FROM competition_teams ct 
                   JOIN modalities mod ON ct.modality_id = mod.id 
                   JOIN categories cat ON ct.category_id = cat.id 
                   GROUP BY mod.id, ct.category_id, ct.gender");
    $output .= "\nTeams per Category/Modality:\n";
    foreach ($cats as $c) {
        $output .= " - {$c['mod_name']} | {$c['cat_name']} ({$c['gender']}): {$c['team_count']} teams\n";
    }

    // 5. Recent Matches Sample
    $recent = query("SELECT m.id, mod.name as mod_name, m.phase, m.status 
                     FROM matches m 
                     JOIN modalities mod ON m.modality_id = mod.id 
                     ORDER BY m.id DESC LIMIT 5");
    $output .= "\nRecent Matches Sample:\n";
    foreach ($recent as $r) {
        $output .= " - ID: {$r['id']} | {$r['mod_name']} | {$r['phase']} | {$r['status']}\n";
    }

} catch (Exception $e) {
    $output .= "\nERROR: " . $e->getMessage() . "\n";
}

file_put_contents('server_debug.txt', $output);
echo "Debug data written to server_debug.txt";
