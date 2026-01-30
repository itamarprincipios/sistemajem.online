<?php
// server_check.php
// Upload this file to your public_html to diagnose Error 500

// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>Diagnóstico do Servidor JEM</h1>";

// 1. Check PHP Version
echo "<h2>1. Versão do PHP</h2>";
echo "Versão atual: " . phpversion() . "<br>";
if (version_compare(phpversion(), '8.0.0', '<')) {
    echo "<strong style='color:red'>ERRO: O sistema requer PHP 8.0 ou superior.</strong><br>";
} else {
    echo "<strong style='color:green'>OK: Versão compatível.</strong><br>";
}

// 2. Check Extensions
echo "<h2>2. Extensões Necessárias</h2>";
$required = ['pdo', 'pdo_mysql', 'mbstring', 'json', 'gd'];
foreach ($required as $ext) {
    if (extension_loaded($ext)) {
        echo "$ext: <span style='color:green'>Instalada</span><br>";
    } else {
        echo "$ext: <span style='color:red'>FALTANDO</span><br>";
    }
}

// 3. Check Database Connection
echo "<h2>3. Teste de Conexão com Banco</h2>";
if (file_exists('config/config.php')) {
    require_once 'config/config.php';
    try {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $pdo = new PDO($dsn, DB_USER, DB_PASS);
        echo "<strong style='color:green'>OK: Conexão realizada com sucesso!</strong>";
    } catch (PDOException $e) {
        echo "<strong style='color:red'>ERRO DE CONEXÃO:</strong> " . $e->getMessage();
    }
} else {
    echo "<strong style='color:orange'>AVISO: Arquivo config/config.php não encontrado.</strong>";
}

// 4. Check Permissions
echo "<h2>4. Permissões de Pasta</h2>";
$paths = ['uploads', 'uploads/students', 'logs'];
foreach ($paths as $path) {
    if (file_exists($path)) {
        $perms = substr(sprintf('%o', fileperms($path)), -4);
        $writable = is_writable($path) ? "Sim" : "Não";
        echo "$path: Permissões $perms | Gravável: $writable<br>";
    } else {
        echo "$path: <span style='color:red'>Não existe</span><br>";
    }
}

echo "<hr>";
echo "<h3>Teste concluído. Se você vê esta página, o PHP está funcionando.</h3>";
echo "Se o erro 500 persiste apenas no sistema, verifique o arquivo .htaccess.";
?>
