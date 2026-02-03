<?php
require_once 'config/config.php';
require_once 'includes/db.php';

echo "<h1>JEM Diagnostic Tool</h1>";

$matchId = 419; // The match from the screenshot
$match = queryOne("SELECT * FROM matches WHERE id = ?", [$matchId]);

if (!$match) {
    echo "<p style='color:red'>Match $matchId not found in database!</p>";
} else {
    echo "<h3>Match $matchId Data:</h3>";
    echo "<pre>";
    print_r($match);
    echo "</pre>";
    
    echo "<h3>Attempting manual update to 'live'...</h3>";
    $sql = "UPDATE matches SET status = 'live', start_time = NOW() WHERE id = ?";
    $result = execute($sql, [$matchId]);
    
    if ($result) {
        $updatedMatch = queryOne("SELECT status, start_time FROM matches WHERE id = ?", [$matchId]);
        echo "<p style='color:green'>Update successfully executed. New status: " . $updatedMatch['status'] . "</p>";
        echo "<p>New start_time: " . $updatedMatch['start_time'] . "</p>";
    } else {
        echo "<p style='color:red'>Update FAILED!</p>";
    }
}
?>
