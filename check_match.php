<?php
require_once 'config/config.php';
require_once 'includes/db.php';

$matchId = $_GET['id'] ?? 507;

echo "<h2>Verificando partida ID: $matchId</h2>";

$match = queryOne("SELECT * FROM matches WHERE id = ?", [$matchId]);

if ($match) {
    echo "<h3>✅ Partida encontrada!</h3>";
    echo "<pre>";
    print_r($match);
    echo "</pre>";
} else {
    echo "<h3>❌ Partida NÃO encontrada no banco de dados</h3>";
    
    // Verificar últimas partidas
    echo "<h3>Últimas 10 partidas no banco:</h3>";
    $recent = query("SELECT id, team_a_id, team_b_id, status, scheduled_time FROM matches ORDER BY id DESC LIMIT 10");
    echo "<pre>";
    print_r($recent);
    echo "</pre>";
}
