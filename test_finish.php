<?php
require_once 'config/config.php';
require_once 'includes/auth.php';
require_once 'includes/db.php';

requireLogin();

// Test finishing match 419
$matchId = 419;

echo "<h1>Test: Finishing Match $matchId</h1>";

// 1. Check current status
$match = queryOne("SELECT id, status, start_time, end_time FROM matches WHERE id = ?", [$matchId]);
echo "<h2>Current Status:</h2>";
echo "<pre>" . print_r($match, true) . "</pre>";

// 2. Try to update
$sql = "UPDATE matches SET status = ?, end_time = NOW() WHERE id = ?";
$params = ['finished', $matchId];

echo "<h2>SQL to execute:</h2>";
echo "<pre>$sql</pre>";
echo "<pre>Params: " . print_r($params, true) . "</pre>";

try {
    $result = execute($sql, $params);
    echo "<h2>Execute Result:</h2>";
    echo "<pre>Result: " . ($result ? 'TRUE' : 'FALSE') . "</pre>";
    
    // 3. Check new status
    $matchAfter = queryOne("SELECT id, status, start_time, end_time FROM matches WHERE id = ?", [$matchId]);
    echo "<h2>Status After Update:</h2>";
    echo "<pre>" . print_r($matchAfter, true) . "</pre>";
    
    if ($matchAfter['status'] === 'finished') {
        echo "<h2 style='color: green;'>✅ SUCCESS! Match was finished.</h2>";
    } else {
        echo "<h2 style='color: red;'>❌ FAILED! Match status did not change.</h2>";
    }
} catch (Exception $e) {
    echo "<h2 style='color: red;'>ERROR:</h2>";
    echo "<pre>" . $e->getMessage() . "</pre>";
}
