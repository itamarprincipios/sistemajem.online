<?php
require_once 'config/config.php';
require_once 'includes/db.php';

echo "<h1>JEM - Database Migration (Súmula & Captains)</h1>";

$sqls = [
    "ALTER TABLE matches ADD COLUMN team_a_captain_id INT NULL AFTER team_a_lineup",
    "ALTER TABLE matches ADD COLUMN team_b_captain_id INT NULL AFTER team_b_lineup",
    "ALTER TABLE matches ADD COLUMN observations TEXT NULL AFTER referee_fourth",
    "ALTER TABLE matches ADD FOREIGN KEY (team_a_captain_id) REFERENCES competition_team_athletes(id) ON DELETE SET NULL",
    "ALTER TABLE matches ADD FOREIGN KEY (team_b_captain_id) REFERENCES competition_team_athletes(id) ON DELETE SET NULL"
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
