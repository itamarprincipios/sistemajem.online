<?php
require_once 'config/config.php';
require_once 'includes/db.php';

echo "<h1>JEM - Database Migration (Substitutions & Lineups)</h1>";

$sqls = [
    "ALTER TABLE match_events MODIFY COLUMN event_type ENUM('GOAL', 'OWN_GOAL', 'YELLOW_CARD', 'RED_CARD', 'FOUL', 'TIMEOUT', 'SUBSTITUTION') NOT NULL",
    "ALTER TABLE match_events ADD COLUMN athlete_id_in INT NULL AFTER athlete_id",
    "ALTER TABLE match_events ADD FOREIGN KEY (athlete_id_in) REFERENCES competition_team_athletes(id) ON DELETE SET NULL",
    "ALTER TABLE matches ADD COLUMN team_a_lineup JSON NULL",
    "ALTER TABLE matches ADD COLUMN team_b_lineup JSON NULL"
];

$pdo = getConnection();
foreach ($sqls as $sql) {
    try {
        $pdo->exec($sql);
        echo "<p style='color: green;'>✅ Executed: <code style='background:#eee;padding:2px;'>$sql</code></p>";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column name') !== false || strpos($e->getMessage(), 'Duplicate key name') !== false) {
            echo "<p style='color: blue;'>ℹ️ Already exists: <code style='background:#eee;padding:2px;'>$sql</code></p>";
        } else {
            echo "<p style='color: red;'>❌ Failed: " . $e->getMessage() . "</p>";
        }
    }
}

echo "<br><a href='operator/match_control.php?id=" . ($_GET['id'] ?? '') . "'>Back to Match Control</a>";
?>
