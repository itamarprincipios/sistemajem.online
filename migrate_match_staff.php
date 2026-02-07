<?php
require_once 'config/config.php';
require_once 'includes/db.php';

echo "<h1>JEM - Database Migration (Match Staff)</h1>";

$sql = "
ALTER TABLE matches 
ADD COLUMN team_a_coach VARCHAR(255) NULL AFTER winner_team_id,
ADD COLUMN team_a_assistant VARCHAR(255) NULL AFTER team_a_coach,
ADD COLUMN team_b_coach VARCHAR(255) NULL AFTER team_a_assistant,
ADD COLUMN team_b_assistant VARCHAR(255) NULL AFTER team_b_coach;
";

try {
    $pdo = getConnection();
    $pdo->exec($sql);
    echo "<p style='color: green;'>✅ Migration executed successfully!</p>";
    echo "<p>Technical staff columns added to 'matches' table.</p>";
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
        echo "<p style='color: blue;'>ℹ️ Migration already executed (columns already exist).</p>";
    } else {
        echo "<p style='color: red;'>❌ Migration failed: " . $e->getMessage() . "</p>";
    }
}

echo "<br><a href='operator/match_control.php?id=" . ($_GET['id'] ?? '') . "'>Back to Match Control</a>";
?>
