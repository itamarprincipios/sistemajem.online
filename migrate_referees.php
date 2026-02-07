<?php
require_once 'config/config.php';
require_once 'includes/db.php';

echo "<h1>JEM - Database Migration (Referees)</h1>";

$sql = "
ALTER TABLE matches 
ADD COLUMN referee_primary VARCHAR(255) NULL AFTER team_b_assistant,
ADD COLUMN referee_assistant VARCHAR(255) NULL AFTER referee_primary,
ADD COLUMN referee_fourth VARCHAR(255) NULL AFTER referee_assistant;
";

try {
    $pdo = getConnection();
    $pdo->exec($sql);
    echo "<p style='color: green;'>✅ Migration executed successfully!</p>";
    echo "<p>Referee columns added to 'matches' table.</p>";
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
        echo "<p style='color: blue;'>ℹ️ Migration already executed (columns already exist).</p>";
    } else {
        echo "<p style='color: red;'>❌ Migration failed: " . $e->getMessage() . "</p>";
    }
}

echo "<br><a href='operator/match_control.php?id=" . ($_GET['id'] ?? '') . "'>Back to Match Control</a>";
?>
