<?php
require_once 'config/config.php';
require_once 'includes/auth.php';
require_once 'includes/db.php';

requireLogin();

$matchId = $_GET['match_id'] ?? 508;

echo "<h2>DEBUG: Súmula Match ID $matchId</h2>";

// Exact same query as sumula.php line 15
$match = queryOne("SELECT * FROM matches WHERE id = ?", [$matchId]);

if (!$match) {
    die("Partida não encontrada");
}

echo "<h3>Dados retornados pela query:</h3>";
echo "<pre>";
echo "referee_primary: '" . ($match['referee_primary'] ?? 'NULL') . "'\n";
echo "referee_assistant: '" . ($match['referee_assistant'] ?? 'NULL') . "'\n";
echo "referee_fourth: '" . ($match['referee_fourth'] ?? 'NULL') . "'\n";
echo "\n";
echo "Valor após htmlspecialchars:\n";
echo "referee_primary: '" . htmlspecialchars($match['referee_primary'] ?: 'Não informado') . "'\n";
echo "referee_assistant: '" . htmlspecialchars($match['referee_assistant'] ?: 'Não informado') . "'\n";
echo "referee_fourth: '" . htmlspecialchars($match['referee_fourth'] ?: 'Não informado') . "'\n";
echo "</pre>";

echo "<h3>Todas as colunas:</h3>";
echo "<pre>";
print_r($match);
echo "</pre>";
