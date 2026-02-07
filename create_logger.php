<?php
require_once 'config/config.php';
require_once 'includes/db.php';

$output = "--- SERVER DATA DEBUG V2 ---\n";
$output .= "Time: " . date('Y-m-d H:i:s') . "\n\n";

try {
    // 1. Modality Info
    $mod = queryOne("SELECT id FROM modalities WHERE name LIKE '%Society%'");
    if (!$mod) {
        $output .= "CRITICAL: Society Modality NOT FOUND in DB!\n";
    } else {
        $modId = $mod['id'];
        $output .= "Society Modality ID: $modId\n";
        
        // 2. Check Registrations
        $regTotal = queryOne("SELECT COUNT(*) as c FROM registrations WHERE modality_id = ?", [$modId]);
        $regApproved = queryOne("SELECT COUNT(*) as c FROM registrations WHERE modality_id = ? AND status = 'approved'", [$modId]);
        $output .= "Registrations (Total): {$regTotal['c']}\n";
        $output .= "Registrations (Approved): {$regApproved['c']}\n";
        
        // 3. Check Teams in Active Event
        $event = queryOne("SELECT id, name FROM competition_events WHERE active_flag = TRUE LIMIT 1");
        if ($event) {
            $output .= "Active Event: {$event['name']} (ID: {$event['id']})\n";
            $teams = queryOne("SELECT COUNT(*) as c FROM competition_teams WHERE competition_event_id = ? AND modality_id = ?", [$event['id'], $modId]);
            $output .= "Teams in this Event: {$teams['c']}\n";
            
            $matches = queryOne("SELECT COUNT(*) as c FROM matches WHERE competition_event_id = ? AND modality_id = ?", [$event['id'], $modId]);
            $output .= "Matches in this Event: {$matches['c']}\n";
        } else {
            $output .= "No Event found with active_flag = TRUE\n";
        }
    }

    // List all users named Gustavo
    $u = query("SELECT id, name, email, role FROM users WHERE name LIKE '%Gustavo%'");
    $output .= "\nUsers matching 'Gustavo': " . count($u) . "\n";
    foreach ($u as $user) {
        $output .= " - {$user['name']} | {$user['email']} | {$user['role']}\n";
    }

} catch (Exception $e) {
    $output .= "\nERROR: " . $e->getMessage() . "\n";
}

file_put_contents('server_debug.txt', $output);
echo "Debug data written to server_debug.txt";
