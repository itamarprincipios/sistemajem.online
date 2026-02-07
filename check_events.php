<?php
require_once 'config/config.php';
require_once 'includes/db.php';

echo "--- EVENTS CHECK ---\n";
try {
    $events = query("SELECT id, name, active_flag FROM competition_events");
    foreach ($events as $e) {
        echo "ID: {$e['id']}, Name: {$e['name']}, Active: {$e['active_flag']}\n";
    }
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
