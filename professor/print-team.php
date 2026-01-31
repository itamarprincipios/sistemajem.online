<?php
/**
 * Print Team Sheet - Ficha de Impressão da Equipe
 */

require_once '../config/config.php';
require_once '../includes/auth.php';
require_once '../includes/db.php';

requireProfessor();

$teamId = $_GET['id'] ?? null;
if (!$teamId) {
    die('ID da equipe não fornecido');
}

$schoolId = getCurrentSchoolId();

// Debug info
error_log("Print Team - Team ID: $teamId, School ID: $schoolId");

// First, check if team exists at all
$teamExists = queryOne("SELECT id, school_id FROM registrations WHERE id = ?", [$teamId]);
error_log("Team exists check: " . ($teamExists ? "YES - School ID: " . $teamExists['school_id'] : "NO"));

// Get team details with all information
$team = queryOne("
    SELECT 
        r.*,
        m.name as modality_name,
        c.name as category_name,
        s.name as school_name,
        s.address as school_address,
        s.city as school_city,
        s.phone as school_phone
    FROM registrations r
    LEFT JOIN modalities m ON r.modality_id = m.id
    LEFT JOIN categories c ON r.category_id = c.id
    LEFT JOIN schools s ON r.school_id = s.id
    WHERE r.id = ?
", [$teamId]);

if (!$team) {
    echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Equipe não encontrada</title></head><body>";
    echo "<h2>❌ Equipe não encontrada</h2>";
    echo "<p><strong>Team ID:</strong> $teamId</p>";
    echo "<p><strong>School ID do Professor:</strong> $schoolId</p>";
    
    if ($teamExists) {
        echo "<p><strong>⚠️ A equipe existe no banco (ID: {$teamExists['id']}, School: {$teamExists['school_id']}), mas houve erro ao buscar detalhes.</strong></p>";
        echo "<p>Possível problema com JOINs nas tabelas modalities, categories ou schools.</p>";
    } else {
        echo "<p><strong>❌ A equipe não existe no banco de dados.</strong></p>";
    }
    
    echo "<hr>";
    echo "<p>A equipe que você está tentando imprimir não foi encontrada no banco de dados.</p>";
    echo "<p><strong>Possíveis causas:</strong></p>";
    echo "<ul>";
    echo "<li>A equipe foi excluída</li>";
    echo "<li>Você está visualizando dados em cache (antigos)</li>";
    echo "<li>O ID da equipe está incorreto</li>";
    echo "<li>Problema com dados relacionados (modalidade, categoria ou escola)</li>";
    echo "</ul>";
    echo "<p><strong>Solução:</strong></p>";
    echo "<ol>";
    echo "<li>Volte para a página <a href='teams.php'>Minhas Equipes</a></li>";
    echo "<li>Pressione <strong>Ctrl + Shift + R</strong> para recarregar sem cache</li>";
    echo "<li>Tente imprimir novamente</li>";
    echo "</ol>";
    echo "</body></html>";
    die();
}

// Debug: show team school_id
error_log("Team school_id: " . $team['school_id'] . ", Professor school_id: $schoolId");

// Verify school ownership
if ($team['school_id'] != $schoolId) {
    die("Você não tem permissão para visualizar esta equipe. Equipe pertence à escola ID {$team['school_id']}, mas você está na escola ID $schoolId");
}

// Get professor responsible
$professor = queryOne("
    SELECT u.name, u.email, u.phone
    FROM users u
    WHERE u.id = ?
", [$team['created_by_user_id']]);

// Get athletes
$athletes = query("
    SELECT s.*, e.id as enrollment_id
    FROM students s
    JOIN enrollments e ON s.id = e.student_id
    WHERE e.registration_id = ?
    ORDER BY s.name
", [$teamId]);

// Get school director
$director = queryOne("
    SELECT u.name, u.phone
    FROM users u
    JOIN schools s ON u.school_id = s.id
    WHERE s.id = ? AND u.role = 'director'
    LIMIT 1
", [$schoolId]);

$genderMap = [
    'male' => 'Masculino',
    'female' => 'Feminino',
    'mixed' => 'Misto'
];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ficha da Equipe - <?= htmlspecialchars($team['modality_name']) ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Arial', sans-serif;
            padding: 20mm;
            background: white;
            color: #000;
        }
        
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 3px solid #0056b3;
            padding-bottom: 20px;
        }
        
        .header h1 {
            font-size: 24px;
            color: #0056b3;
            margin-bottom: 5px;
        }
        
        .header h2 {
            font-size: 18px;
            color: #333;
            font-weight: normal;
        }
        
        .info-section {
            margin-bottom: 25px;
        }
        
        .info-section h3 {
            background: #0056b3;
            color: white;
            padding: 8px 12px;
            font-size: 14px;
            margin-bottom: 10px;
            text-transform: uppercase;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 10px;
            padding: 10px;
            background: #f8f9fa;
            border: 1px solid #dee2e6;
        }
        
        .info-item {
            display: flex;
            flex-direction: column;
        }
        
        .info-label {
            font-size: 11px;
            color: #666;
            font-weight: bold;
            text-transform: uppercase;
            margin-bottom: 3px;
        }
        
        .info-value {
            font-size: 13px;
            color: #000;
        }
        
        .staff-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            padding: 10px;
        }
        
        .staff-card {
            border: 2px solid #dee2e6;
            padding: 12px;
            border-radius: 5px;
        }
        
        .staff-card.director {
            border-color: #6f42c1;
        }
        
        .staff-card.professor {
            border-color: #0056b3;
        }
        
        .staff-card.tecnico {
            border-color: #28a745;
        }
        
        .staff-card.auxiliar {
            border-color: #ffc107;
        }
        
        .staff-card.chefe {
            border-color: #dc3545;
        }
        
        .staff-title {
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
            margin-bottom: 5px;
            color: #666;
        }
        
        .staff-name {
            font-size: 13px;
            font-weight: bold;
            margin-bottom: 3px;
        }
        
        .staff-phone {
            font-size: 12px;
            color: #666;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        
        thead {
            background: #0056b3;
            color: white;
        }
        
        th {
            padding: 10px;
            text-align: left;
            font-size: 12px;
            text-transform: uppercase;
        }
        
        td {
            padding: 10px;
            border-bottom: 1px solid #dee2e6;
            font-size: 13px;
        }
        
        tbody tr:nth-child(even) {
            background: #f8f9fa;
        }
        
        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 2px solid #dee2e6;
            text-align: center;
            font-size: 11px;
            color: #666;
        }
        
        @media print {
            body {
                padding: 10mm;
            }
            
            .no-print {
                display: none;
            }
        }
        
        .print-button {
            position: fixed;
            top: 20px;
            right: 20px;
            background: #0056b3;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            font-weight: bold;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        }
        
        .print-button:hover {
            background: #004494;
        }
    </style>
</head>
<body>
    <button class="print-button no-print" onclick="window.print()">🖨️ Imprimir</button>
    
    <div class="header">
        <h1>FICHA DE INSCRIÇÃO DA EQUIPE</h1>
        <h2>Jogos Escolares Municipais</h2>
    </div>
    
    <!-- School Information -->
    <div class="info-section">
        <h3>📚 Informações da Escola</h3>
        <div class="info-grid">
            <div class="info-item">
                <span class="info-label">Escola</span>
                <span class="info-value"><?= htmlspecialchars($team['school_name']) ?></span>
            </div>
            <div class="info-item">
                <span class="info-label">Cidade</span>
                <span class="info-value"><?= htmlspecialchars($team['school_city'] ?? 'N/A') ?></span>
            </div>
            <div class="info-item">
                <span class="info-label">Endereço</span>
                <span class="info-value"><?= htmlspecialchars($team['school_address'] ?? 'N/A') ?></span>
            </div>
            <div class="info-item">
                <span class="info-label">Telefone</span>
                <span class="info-value"><?= htmlspecialchars($team['school_phone'] ?? 'N/A') ?></span>
            </div>
        </div>
    </div>
    
    <!-- Team Information -->
    <div class="info-section">
        <h3>⚽ Informações da Equipe</h3>
        <div class="info-grid">
            <div class="info-item">
                <span class="info-label">Modalidade</span>
                <span class="info-value"><?= htmlspecialchars($team['modality_name']) ?></span>
            </div>
            <div class="info-item">
                <span class="info-label">Categoria</span>
                <span class="info-value"><?= htmlspecialchars($team['category_name']) ?></span>
            </div>
            <div class="info-item">
                <span class="info-label">Gênero</span>
                <span class="info-value"><?= $genderMap[$team['gender']] ?? $team['gender'] ?></span>
            </div>
            <div class="info-item">
                <span class="info-label">Status</span>
                <span class="info-value"><?= ucfirst($team['status']) ?></span>
            </div>
        </div>
    </div>
    
    <!-- Responsible Adults -->
    <div class="info-section">
        <h3>👥 Responsáveis</h3>
        <div class="staff-grid">
            <?php if ($director): ?>
            <div class="staff-card director">
                <div class="staff-title">👔 Diretor(a)</div>
                <div class="staff-name"><?= htmlspecialchars($director['name']) ?></div>
                <div class="staff-phone">📱 <?= htmlspecialchars($director['phone'] ?? 'N/A') ?></div>
            </div>
            <?php endif; ?>
            
            <?php if ($professor): ?>
            <div class="staff-card professor">
                <div class="staff-title">👨‍🏫 Professor(a) Responsável</div>
                <div class="staff-name"><?= htmlspecialchars($professor['name']) ?></div>
                <div class="staff-phone">📱 <?= htmlspecialchars($professor['phone'] ?? 'N/A') ?></div>
            </div>
            <?php endif; ?>
            
            <?php if ($team['chefe_delegacao_nome']): ?>
            <div class="staff-card chefe">
                <div class="staff-title">👔 Chefe de Delegação</div>
                <div class="staff-name"><?= htmlspecialchars($team['chefe_delegacao_nome']) ?></div>
                <div class="staff-phone">📱 <?= htmlspecialchars($team['chefe_delegacao_celular']) ?></div>
            </div>
            <?php endif; ?>
            
            <?php if ($team['tecnico_nome']): ?>
            <div class="staff-card tecnico">
                <div class="staff-title">🎯 Técnico</div>
                <div class="staff-name"><?= htmlspecialchars($team['tecnico_nome']) ?></div>
                <div class="staff-phone">📱 <?= htmlspecialchars($team['tecnico_celular']) ?></div>
            </div>
            <?php endif; ?>
            
            <?php if ($team['auxiliar_tecnico_nome']): ?>
            <div class="staff-card auxiliar">
                <div class="staff-title">🤝 Auxiliar Técnico</div>
                <div class="staff-name"><?= htmlspecialchars($team['auxiliar_tecnico_nome']) ?></div>
                <div class="staff-phone">📱 <?= htmlspecialchars($team['auxiliar_tecnico_celular']) ?></div>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Athletes List -->
    <div class="info-section">
        <h3>🏃 Atletas Inscritos (<?= count($athletes) ?>)</h3>
        <?php if (count($athletes) > 0): ?>
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Nome</th>
                    <th>Data de Nascimento</th>
                    <th>Gênero</th>
                    <th>RG</th>
                    <th>Telefone</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($athletes as $index => $athlete): ?>
                <tr>
                    <td><?= $index + 1 ?></td>
                    <td><?= htmlspecialchars($athlete['name']) ?></td>
                    <td><?= date('d/m/Y', strtotime($athlete['birth_date'])) ?></td>
                    <td><?= $athlete['gender'] === 'male' ? 'M' : 'F' ?></td>
                    <td><?= htmlspecialchars($athlete['rg'] ?? 'N/A') ?></td>
                    <td><?= htmlspecialchars($athlete['phone'] ?? 'N/A') ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
        <p style="padding: 20px; text-align: center; color: #666;">Nenhum atleta inscrito ainda.</p>
        <?php endif; ?>
    </div>
    
    <div class="footer">
        <p>Documento gerado em <?= date('d/m/Y H:i') ?> - Sistema JEM</p>
        <p><?= htmlspecialchars($team['school_name']) ?></p>
    </div>
</body>
</html>
