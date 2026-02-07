<?php
// Test if sumula.php exists and what version it is
echo "<h2>Verificando arquivo sumula.php</h2>";

$file = __DIR__ . '/sumula.php';

if (file_exists($file)) {
    echo "✅ Arquivo existe!<br>";
    echo "Última modificação: " . date("Y-m-d H:i:s", filemtime($file)) . "<br>";
    
    $content = file_get_contents($file);
    
    // Check if it's the new version
    if (strpos($content, 'COALESCE(t1.school_name_snapshot') !== false) {
        echo "✅ <strong>VERSÃO NOVA detectada!</strong><br>";
    } else {
        echo "❌ <strong>VERSÃO ANTIGA ou INCORRETA!</strong><br>";
    }
    
    // Show first query
    $start = strpos($content, 'SELECT m.*');
    if ($start !== false) {
        $snippet = substr($content, $start, 500);
        echo "<h3>Query encontrada:</h3>";
        echo "<pre style='background: #f0f0f0; padding: 10px;'>";
        echo htmlspecialchars($snippet);
        echo "</pre>";
    }
} else {
    echo "❌ <strong>ARQUIVO NÃO EXISTE!</strong><br>";
}

// Try to access it directly
echo "<hr>";
echo "<h3>Teste direto:</h3>";
echo "<a href='sumula.php?match_id=507' target='_blank' style='background: #4CAF50; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Abrir Súmula ID 507</a>";
?>
