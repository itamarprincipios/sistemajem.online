<?php
/**
 * Teste do endpoint details da API
 */

require_once '../config/config.php';
require_once '../includes/auth.php';
require_once '../includes/db.php';

requireProfessor();

$schoolId = getCurrentSchoolId();

// Get first team ID for testing
$team = queryOne("SELECT id FROM registrations WHERE school_id = ? LIMIT 1", [$schoolId]);

if (!$team) {
    die("Nenhuma equipe encontrada para testar");
}

$teamId = $team['id'];

echo "<h2>Testando endpoint details com ID: $teamId</h2>";
echo "<pre>";

// Simulate API call
try {
    $teamDetails = queryOne("
        SELECT 
            r.*,
            m.name as modality_name,
            c.name as category_name,
            s.name as school_name,
            r.tecnico_nome,
            r.tecnico_celular,
            r.auxiliar_tecnico_nome,
            r.auxiliar_tecnico_celular,
            r.chefe_delegacao_nome,
            r.chefe_delegacao_celular
        FROM registrations r
        JOIN modalities m ON r.modality_id = m.id
        JOIN categories c ON r.category_id = c.id
        JOIN schools s ON r.school_id = s.id
        WHERE r.id = ? AND r.school_id = ?
    ", [$teamId, $schoolId]);
    
    if ($teamDetails) {
        echo "✅ Equipe encontrada!\n\n";
        echo "Dados retornados:\n";
        print_r($teamDetails);
        
        echo "\n\n=== Campos da Equipe Técnica ===\n";
        echo "tecnico_nome: " . ($teamDetails['tecnico_nome'] ?? 'NULL') . "\n";
        echo "tecnico_celular: " . ($teamDetails['tecnico_celular'] ?? 'NULL') . "\n";
        echo "auxiliar_tecnico_nome: " . ($teamDetails['auxiliar_tecnico_nome'] ?? 'NULL') . "\n";
        echo "auxiliar_tecnico_celular: " . ($teamDetails['auxiliar_tecnico_celular'] ?? 'NULL') . "\n";
        echo "chefe_delegacao_nome: " . ($teamDetails['chefe_delegacao_nome'] ?? 'NULL') . "\n";
        echo "chefe_delegacao_celular: " . ($teamDetails['chefe_delegacao_celular'] ?? 'NULL') . "\n";
    } else {
        echo "❌ Equipe não encontrada\n";
    }
} catch (Exception $e) {
    echo "❌ Erro: " . $e->getMessage() . "\n";
}

echo "</pre>";
