<?php
// Simple diagnostic version
require_once '../config/config.php';
require_once '../includes/auth.php';
require_once '../includes/db.php';

requireLogin();

echo "<!DOCTYPE html>";
echo "<html><head><title>Diagnostic</title></head><body>";
echo "<h1>Diagnostic Test</h1>";
echo "<p>PHP is working!</p>";
echo "<p>User: " . ($_SESSION['user_name'] ?? 'Unknown') . "</p>";

// Test database
try {
    $activeEvent = queryOne("SELECT id, name FROM competition_events WHERE active_flag = TRUE LIMIT 1");
    echo "<p>Active Event: " . ($activeEvent ? $activeEvent['name'] : 'None') . "</p>";
    
    $modalities = query("SELECT id, name FROM modalities ORDER BY name");
    echo "<p>Modalities found: " . count($modalities) . "</p>";
    
    $categories = query("SELECT id, name FROM categories ORDER BY name");
    echo "<p>Categories found: " . count($categories) . "</p>";
    
} catch (Exception $e) {
    echo "<p style='color:red'>Database Error: " . $e->getMessage() . "</p>";
}

echo "</body></html>";
?>
