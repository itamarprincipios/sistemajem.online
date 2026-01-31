<?php
/**
 * Debug - Verificar dados da equipe ID 10
 */

require_once '../config/config.php';
require_once '../includes/auth.php';
require_once '../includes/db.php';

requireProfessor();

$teamId = 10;

echo "<h2>Debug - Equipe ID: $teamId</h2>";
echo "<pre>";

// Get team basic data
$team = queryOne("SELECT * FROM registrations WHERE id = ?", [$teamId]);

if (!$team) {
    echo "❌ Equipe não existe!\n";
    die();
}

echo "✅ Equipe encontrada!\n\n";
echo "=== DADOS DA EQUIPE ===\n";
print_r($team);

echo "\n\n=== VERIFICANDO RELAÇÕES ===\n";

// Check modality
$modality = queryOne("SELECT * FROM modalities WHERE id = ?", [$team['modality_id']]);
if ($modality) {
    echo "✅ Modalidade encontrada: {$modality['name']}\n";
} else {
    echo "❌ ERRO: Modalidade ID {$team['modality_id']} NÃO EXISTE!\n";
}

// Check category
$category = queryOne("SELECT * FROM categories WHERE id = ?", [$team['category_id']]);
if ($category) {
    echo "✅ Categoria encontrada: {$category['name']}\n";
} else {
    echo "❌ ERRO: Categoria ID {$team['category_id']} NÃO EXISTE!\n";
}

// Check school
$school = queryOne("SELECT * FROM schools WHERE id = ?", [$team['school_id']]);
if ($school) {
    echo "✅ Escola encontrada: {$school['name']}\n";
} else {
    echo "❌ ERRO: Escola ID {$team['school_id']} NÃO EXISTE!\n";
}

echo "\n\n=== TESTE DE JOIN ===\n";
$joinTest = queryOne("
    SELECT 
        r.id,
        m.name as modality_name,
        c.name as category_name,
        s.name as school_name
    FROM registrations r
    LEFT JOIN modalities m ON r.modality_id = m.id
    LEFT JOIN categories c ON r.category_id = c.id
    LEFT JOIN schools s ON r.school_id = s.id
    WHERE r.id = ?
", [$teamId]);

if ($joinTest) {
    echo "✅ JOIN funcionou!\n";
    print_r($joinTest);
} else {
    echo "❌ JOIN falhou!\n";
}

echo "</pre>";
