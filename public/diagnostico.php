<?php
/**
 * Diagnóstico de Conexão
 * Verifica se o banco de dados está acessível e se as colunas existem
 */

echo "<h1>Diagnóstico do Sistema</h1>";
echo "<pre>";

// Test 1: Check if config file exists
echo "=== TESTE 1: Arquivo de Configuração ===\n";
if (file_exists('../config/config.php')) {
    echo "✅ config.php existe\n";
    require_once '../config/config.php';
    echo "✅ config.php carregado\n";
} else {
    echo "❌ config.php NÃO encontrado\n";
    exit;
}

// Test 2: Check database connection
echo "\n=== TESTE 2: Conexão com Banco de Dados ===\n";
try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET,
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    echo "✅ Conexão estabelecida\n";
    echo "   Host: " . DB_HOST . "\n";
    echo "   Database: " . DB_NAME . "\n";
    echo "   User: " . DB_USER . "\n";
} catch (PDOException $e) {
    echo "❌ Erro de conexão: " . $e->getMessage() . "\n";
    exit;
}

// Test 3: Check if registrations table exists
echo "\n=== TESTE 3: Tabela 'registrations' ===\n";
try {
    $stmt = $pdo->query("SHOW TABLES LIKE 'registrations'");
    if ($stmt->rowCount() > 0) {
        echo "✅ Tabela 'registrations' existe\n";
    } else {
        echo "❌ Tabela 'registrations' NÃO encontrada\n";
        exit;
    }
} catch (PDOException $e) {
    echo "❌ Erro: " . $e->getMessage() . "\n";
    exit;
}

// Test 4: Check if new columns exist
echo "\n=== TESTE 4: Novas Colunas da Equipe Técnica ===\n";
try {
    $stmt = $pdo->query("DESCRIBE registrations");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $requiredColumns = [
        'tecnico_nome',
        'tecnico_celular',
        'auxiliar_tecnico_nome',
        'auxiliar_tecnico_celular',
        'chefe_delegacao_nome',
        'chefe_delegacao_celular'
    ];
    
    foreach ($requiredColumns as $col) {
        if (in_array($col, $columns)) {
            echo "✅ Coluna '$col' existe\n";
        } else {
            echo "❌ Coluna '$col' NÃO existe\n";
        }
    }
} catch (PDOException $e) {
    echo "❌ Erro: " . $e->getMessage() . "\n";
}

// Test 5: Check if db.php exists
echo "\n=== TESTE 5: Arquivo db.php ===\n";
if (file_exists('../includes/db.php')) {
    echo "✅ db.php existe\n";
} else {
    echo "❌ db.php NÃO encontrado\n";
}

echo "\n=== DIAGNÓSTICO COMPLETO ===\n";
echo "Se todos os testes passaram, o sistema deveria estar funcionando.\n";
echo "</pre>";
