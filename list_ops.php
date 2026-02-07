<?php
require_once 'config/config.php';
require_once 'includes/db.php';

echo "--- OPERATORS LIST ---\n";
try {
    $ops = query("SELECT id, name, email, role FROM users WHERE role = 'operator'");
    foreach ($ops as $o) {
        echo " - ID: {$o['id']}, Name: {$o['name']}, Email: {$o['email']}\n";
        $assignments = query("SELECT co.*, m.name as mod_name FROM competition_operators co LEFT JOIN modalities m ON co.assigned_modality_id = m.id WHERE co.user_id = ?", [$o['id']]);
        foreach ($assignments as $a) {
            echo "   * Event ID: {$a['competition_event_id']}, Modality: {$a['mod_name']} (ID: {$a['assigned_modality_id']})\n";
        }
    }
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
