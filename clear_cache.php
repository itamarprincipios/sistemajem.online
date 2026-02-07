<?php
// Force clear any OPcache
if (function_exists('opcache_reset')) {
    opcache_reset();
    echo "✅ OPcache cleared!<br>";
} else {
    echo "ℹ️ OPcache not available<br>";
}

// Check current code version
echo "<h2>Verificando versão do código da API</h2>";

$apiFile = __DIR__ . '/api/match-events-api.php';
$content = file_get_contents($apiFile);

// Check if the new code is present
if (strpos($content, 'SELECT m.id, m.team_a_id, m.team_b_id') !== false) {
    echo "✅ <strong>CÓDIGO NOVO DETECTADO!</strong> A API está com a versão corrigida.<br>";
} else {
    echo "❌ <strong>CÓDIGO ANTIGO!</strong> A API ainda está com a versão antiga.<br>";
}

// Show a snippet of the query
$start = strpos($content, '// Now try with joins');
if ($start !== false) {
    $snippet = substr($content, $start, 500);
    echo "<h3>Trecho da query atual:</h3>";
    echo "<pre style='background: #f0f0f0; padding: 10px;'>";
    echo htmlspecialchars($snippet);
    echo "</pre>";
}

echo "<hr>";
echo "<h3>Última modificação do arquivo:</h3>";
echo date("Y-m-d H:i:s", filemtime($apiFile));
