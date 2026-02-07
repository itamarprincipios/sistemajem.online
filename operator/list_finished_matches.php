<?php
require_once '../config/config.php';
require_once '../includes/auth.php';
require_once '../includes/db.php';

requireLogin();

// Get all finished matches
$matches = query("
    SELECT m.id, m.team_a_id, m.team_b_id, m.status, m.scheduled_time,
           t1.school_name_snapshot as team_a_name,
           t2.school_name_snapshot as team_b_name,
           m.score_team_a, m.score_team_b
    FROM matches m
    LEFT JOIN competition_teams t1 ON m.team_a_id = t1.id
    LEFT JOIN competition_teams t2 ON m.team_b_id = t2.id
    WHERE m.status = 'finished'
    ORDER BY m.scheduled_time DESC
    LIMIT 20
");

echo "<h2>Partidas Encerradas no Banco de Dados</h2>";
echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
echo "<tr style='background: #333; color: white;'>";
echo "<th>ID</th><th>Data/Hora</th><th>Equipe A</th><th>Placar</th><th>Equipe B</th><th>Status</th><th>Ação</th>";
echo "</tr>";

foreach ($matches as $m) {
    echo "<tr>";
    echo "<td><strong>{$m['id']}</strong></td>";
    echo "<td>" . date('d/m/Y H:i', strtotime($m['scheduled_time'])) . "</td>";
    echo "<td>{$m['team_a_name']}</td>";
    echo "<td style='text-align: center; font-weight: bold;'>{$m['score_team_a']} x {$m['score_team_b']}</td>";
    echo "<td>{$m['team_b_name']}</td>";
    echo "<td>{$m['status']}</td>";
    echo "<td><a href='sumula.php?match_id={$m['id']}' target='_blank' style='background: #4CAF50; color: white; padding: 5px 10px; text-decoration: none; border-radius: 3px;'>Ver Súmula</a></td>";
    echo "</tr>";
}

echo "</table>";

if (count($matches) == 0) {
    echo "<p style='color: red; font-weight: bold;'>⚠️ Nenhuma partida encerrada encontrada no banco de dados!</p>";
}
?>
