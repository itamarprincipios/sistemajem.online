<?php
require_once 'config/config.php';
require_once 'includes/db.php';

echo "<h2>Verificando estrutura da tabela matches</h2>";

$columns = query("SHOW COLUMNS FROM matches");

echo "<h3>Colunas existentes:</h3>";
echo "<table border='1' style='border-collapse: collapse;'>";
echo "<tr><th>Campo</th><th>Tipo</th><th>Null</th><th>Key</th><th>Default</th></tr>";
foreach ($columns as $col) {
    $highlight = '';
    if (in_array($col['Field'], ['team_a_captain_id', 'team_b_captain_id', 'observations'])) {
        $highlight = 'style="background-color: #90EE90;"';
    }
    echo "<tr $highlight>";
    echo "<td><strong>{$col['Field']}</strong></td>";
    echo "<td>{$col['Type']}</td>";
    echo "<td>{$col['Null']}</td>";
    echo "<td>{$col['Key']}</td>";
    echo "<td>{$col['Default']}</td>";
    echo "</tr>";
}
echo "</table>";

echo "<h3>Verificando se as colunas da Súmula existem:</h3>";
$hasCapA = false;
$hasCapB = false;
$hasObs = false;

foreach ($columns as $col) {
    if ($col['Field'] === 'team_a_captain_id') $hasCapA = true;
    if ($col['Field'] === 'team_b_captain_id') $hasCapB = true;
    if ($col['Field'] === 'observations') $hasObs = true;
}

echo "<ul>";
echo "<li>team_a_captain_id: " . ($hasCapA ? "✅ EXISTE" : "❌ NÃO EXISTE") . "</li>";
echo "<li>team_b_captain_id: " . ($hasCapB ? "✅ EXISTE" : "❌ NÃO EXISTE") . "</li>";
echo "<li>observations: " . ($hasObs ? "✅ EXISTE" : "❌ NÃO EXISTE") . "</li>";
echo "</ul>";

if (!$hasCapA || !$hasCapB || !$hasObs) {
    echo "<h2 style='color: red;'>⚠️ ATENÇÃO: Você precisa rodar a migração!</h2>";
    echo "<p>Execute este SQL no banco de dados:</p>";
    echo "<pre style='background: #f0f0f0; padding: 10px;'>";
    echo "ALTER TABLE matches 
ADD COLUMN team_a_captain_id INT NULL AFTER team_a_lineup,
ADD COLUMN team_b_captain_id INT NULL AFTER team_b_lineup,
ADD COLUMN observations TEXT NULL AFTER referee_fourth,
ADD FOREIGN KEY (team_a_captain_id) REFERENCES competition_team_athletes(id) ON DELETE SET NULL,
ADD FOREIGN KEY (team_b_captain_id) REFERENCES competition_team_athletes(id) ON DELETE SET NULL;";
    echo "</pre>";
}
