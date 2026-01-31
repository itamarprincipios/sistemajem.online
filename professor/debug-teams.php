<?php
/**
 * Debug - Verificar equipes no banco
 */

require_once '../config/config.php';
require_once '../includes/auth.php';
require_once '../includes/db.php';

requireProfessor();

$schoolId = getCurrentSchoolId();

echo "<h2>Debug - Equipes da Escola ID: $schoolId</h2>";
echo "<pre>";

// Get all teams
$teams = query("
    SELECT 
        r.id,
        r.school_id,
        r.status,
        r.created_by_user_id,
        m.name as modality_name,
        c.name as category_name
    FROM registrations r
    JOIN modalities m ON r.modality_id = m.id
    JOIN categories c ON r.category_id = c.id
    WHERE r.school_id = ?
    ORDER BY r.id DESC
", [$schoolId]);

echo "Total de equipes encontradas: " . count($teams) . "\n\n";

foreach ($teams as $team) {
    echo "ID: {$team['id']}\n";
    echo "Modalidade: {$team['modality_name']}\n";
    echo "Categoria: {$team['category_name']}\n";
    echo "Status: {$team['status']}\n";
    echo "School ID: {$team['school_id']}\n";
    echo "Created by User ID: {$team['created_by_user_id']}\n";
    echo "---\n";
}

echo "\n\n=== Verificando equipe ID 9 especificamente ===\n";
$team9 = queryOne("SELECT * FROM registrations WHERE id = 9");
if ($team9) {
    echo "✅ Equipe ID 9 EXISTE!\n";
    print_r($team9);
} else {
    echo "❌ Equipe ID 9 NÃO EXISTE no banco de dados\n";
}

echo "</pre>";
