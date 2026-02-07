<?php
require_once 'config/config.php';
require_once 'includes/db.php';

$matchId = $_GET['id'] ?? 508;

echo "=== VERIFICANDO DADOS DA PARTIDA $matchId ===\n\n";

$match = queryOne("SELECT * FROM matches WHERE id = ?", [$matchId]);

if (!$match) {
    die("Partida não encontrada");
}

echo "Colunas disponíveis:\n";
foreach (array_keys($match) as $col) {
    echo "- $col\n";
}

echo "\n=== DADOS DE ARBITRAGEM ===\n";
echo "referee_primary: " . ($match['referee_primary'] ?? 'COLUNA NÃO EXISTE') . "\n";
echo "referee_assistant: " . ($match['referee_assistant'] ?? 'COLUNA NÃO EXISTE') . "\n";
echo "referee_fourth: " . ($match['referee_fourth'] ?? 'COLUNA NÃO EXISTE') . "\n";
