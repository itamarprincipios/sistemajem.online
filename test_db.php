<?php
/**
 * Database Connection Test
 * Upload this file to the root of your website and access it via browser
 * URL: https://sistemajem.online/test_db.php
 */

echo "<h1>🔍 Teste de Conexão - Sistema JEM</h1>";
echo "<hr>";

// Test 1: Check if config file exists
echo "<h2>1. Verificando arquivos de configuração:</h2>";
$config_production = __DIR__ . '/config/config.production.php';
$config_default = __DIR__ . '/config/config.php';

if (file_exists($config_production)) {
    echo "✅ config.production.php encontrado<br>";
} else {
    echo "❌ config.production.php NÃO encontrado<br>";
}

if (file_exists($config_default)) {
    echo "✅ config.php encontrado<br>";
} else {
    echo "❌ config.php NÃO encontrado<br>";
}

echo "<hr>";

// Test 2: Try different database credentials
echo "<h2>2. Testando credenciais do banco de dados:</h2>";

$credentials = [
    [
        'host' => 'localhost',
        'user' => 'u199671261_admdatabase',
        'pass' => 'yv!B6+Lp',
        'name' => 'u199671261_database'
    ],
    [
        'host' => 'localhost',
        'user' => 'u199671261_admdatabase',
        'pass' => 'yv!B6+Lp',
        'name' => 'u199671261_bancodedados'
    ],
];

foreach ($credentials as $index => $cred) {
    echo "<h3>Teste " . ($index + 1) . ":</h3>";
    echo "Host: {$cred['host']}<br>";
    echo "User: {$cred['user']}<br>";
    echo "Pass: " . str_repeat('*', strlen($cred['pass'])) . "<br>";
    echo "Database: {$cred['name']}<br>";
    
    try {
        $dsn = "mysql:host={$cred['host']};charset=utf8mb4";
        $pdo = new PDO($dsn, $cred['user'], $cred['pass']);
        echo "✅ <strong>Conexão com MySQL bem-sucedida!</strong><br>";
        
        // Try to select database
        $pdo->exec("USE `{$cred['name']}`");
        echo "✅ <strong>Banco de dados '{$cred['name']}' selecionado com sucesso!</strong><br>";
        
        // List tables
        $stmt = $pdo->query("SHOW TABLES");
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        if (count($tables) > 0) {
            echo "✅ <strong>Tabelas encontradas (" . count($tables) . "):</strong><br>";
            echo "<ul>";
            foreach ($tables as $table) {
                echo "<li>$table</li>";
            }
            echo "</ul>";
        } else {
            echo "⚠️ <strong>Banco de dados vazio. Você precisa importar o SQL!</strong><br>";
        }
        
        echo "<br><strong style='color: green;'>✅ ESTAS SÃO AS CREDENCIAIS CORRETAS!</strong><br>";
        break;
        
    } catch (PDOException $e) {
        echo "❌ <strong>Erro:</strong> " . $e->getMessage() . "<br>";
    }
    
    echo "<hr>";
}

// Test 3: PHP Info
echo "<h2>3. Informações do PHP:</h2>";
echo "Versão do PHP: " . phpversion() . "<br>";
echo "PDO MySQL disponível: " . (extension_loaded('pdo_mysql') ? '✅ Sim' : '❌ Não') . "<br>";

echo "<hr>";
echo "<p><strong>⚠️ IMPORTANTE:</strong> Delete este arquivo após o teste por segurança!</p>";
?>
