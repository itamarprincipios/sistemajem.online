<?php
require_once '../config/config.php';
require_once '../includes/auth.php';
require_once '../includes/db.php';

requireLogin();

$matchId = $_GET['match_id'] ?? null;

if (!$matchId) {
    die("ID da partida não fornecido.");
}

// Get match data - with detailed debugging
try {
    // First, try simple query
    $matchSimple = queryOne("SELECT * FROM matches WHERE id = ?", [$matchId]);
    
    if (!$matchSimple) {
        die("Erro: Partida ID $matchId não existe na tabela matches. Verifique se o ID está correto.");
    }
    
    // Now try with joins
    $match = queryOne("
        SELECT m.*, 
               COALESCE(t1.school_name_snapshot, 'Equipe A') as team_a_name, 
               COALESCE(t2.school_name_snapshot, 'Equipe B') as team_b_name,
               COALESCE(c.name, 'Categoria') as category_name,
               COALESCE(mod.name, 'Modalidade') as modality_name
        FROM matches m
        LEFT JOIN competition_teams t1 ON m.team_a_id = t1.id
        LEFT JOIN competition_teams t2 ON m.team_b_id = t2.id
        LEFT JOIN categories c ON m.category_id = c.id
        LEFT JOIN modalities mod ON m.modality_id = mod.id
        WHERE m.id = ?
    ", [$matchId]);
    
    if (!$match) {
        die("Erro: Query com JOINs falhou. Match simples existe mas query complexa retornou vazio. IDs: team_a={$matchSimple['team_a_id']}, team_b={$matchSimple['team_b_id']}, category={$matchSimple['category_id']}, modality={$matchSimple['modality_id']}");
    }
} catch (Exception $e) {
    die("Erro na query: " . $e->getMessage());
}

// Get athletes
$athletesA = query("SELECT id, name_snapshot, jersey_number FROM competition_team_athletes WHERE competition_team_id = ? ORDER BY jersey_number", [$match['team_a_id']]);
$athletesB = query("SELECT id, name_snapshot, jersey_number FROM competition_team_athletes WHERE competition_team_id = ? ORDER BY jersey_number", [$match['team_b_id']]);

// Get events
$events = query("
    SELECT e.*, 
           a.name_snapshot as athlete_name, a.jersey_number,
           a2.name_snapshot as athlete_in_name
    FROM match_events e
    LEFT JOIN competition_team_athletes a ON e.athlete_id = a.id
    LEFT JOIN competition_team_athletes a2 ON e.athlete_id_in = a2.id
    WHERE e.match_id = ?
    ORDER BY e.event_time ASC, e.created_at ASC
", [$matchId]);

// Separate events by type
$goals = array_filter($events, fn($e) => $e['event_type'] === 'GOAL');
$cards = array_filter($events, fn($e) => in_array($e['event_type'], ['YELLOW_CARD', 'RED_CARD']));
$subs = array_filter($events, fn($e) => $e['event_type'] === 'SUBSTITUTION');

// Phase names
$phaseNames = [
    'group_stage' => 'Fase de Grupos',
    'round_of_16' => 'Oitavas de Final',
    'quarter_finals' => 'Quartas de Final',
    'semi_finals' => 'Semifinal',
    'final' => 'Final'
];

$phaseName = $phaseNames[$match['phase']] ?? strtoupper($match['phase']);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Súmula Oficial - Partida #<?= $matchId ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Arial', sans-serif;
            background: #f5f5f5;
            padding: 20px;
        }
        
        .container {
            max-width: 210mm;
            margin: 0 auto;
            background: white;
            padding: 40px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        
        .header {
            text-align: center;
            border-bottom: 3px solid #333;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        
        .header h1 {
            font-size: 24px;
            margin-bottom: 10px;
            text-transform: uppercase;
        }
        
        .header p {
            color: #666;
            font-size: 14px;
        }
        
        .section {
            margin-bottom: 25px;
        }
        
        .section-title {
            background: #333;
            color: white;
            padding: 8px 15px;
            font-size: 14px;
            font-weight: bold;
            text-transform: uppercase;
            margin-bottom: 10px;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin-bottom: 15px;
        }
        
        .info-item {
            padding: 8px;
            background: #f9f9f9;
            border-left: 3px solid #333;
        }
        
        .info-label {
            font-weight: bold;
            font-size: 12px;
            color: #666;
            text-transform: uppercase;
        }
        
        .info-value {
            font-size: 14px;
            margin-top: 3px;
        }
        
        .team-section {
            border: 2px solid #333;
            padding: 15px;
            margin-bottom: 20px;
        }
        
        .team-name {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 10px;
            text-transform: uppercase;
        }
        
        .athletes-list {
            columns: 2;
            column-gap: 20px;
        }
        
        .athlete-item {
            font-size: 13px;
            padding: 3px 0;
            break-inside: avoid;
        }
        
        .athlete-number {
            display: inline-block;
            width: 30px;
            font-weight: bold;
        }
        
        .captain {
            color: #d4af37;
            font-weight: bold;
        }
        
        .event-list {
            list-style: none;
        }
        
        .event-item {
            padding: 8px;
            margin-bottom: 5px;
            background: #f9f9f9;
            border-left: 3px solid #333;
            font-size: 13px;
        }
        
        .event-time {
            font-weight: bold;
            color: #333;
        }
        
        .observations {
            background: #fffbea;
            border: 1px solid #e6d89f;
            padding: 15px;
            margin-top: 20px;
            white-space: pre-wrap;
        }
        
        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 2px solid #333;
            text-align: center;
            font-size: 12px;
            color: #666;
        }
        
        .print-button {
            position: fixed;
            top: 20px;
            right: 20px;
            background: #4CAF50;
            color: white;
            border: none;
            padding: 15px 30px;
            font-size: 16px;
            cursor: pointer;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
            z-index: 1000;
        }
        
        .print-button:hover {
            background: #45a049;
        }
        
        @media print {
            body {
                background: white;
                padding: 0;
            }
            
            .container {
                max-width: 100%;
                box-shadow: none;
                padding: 20px;
            }
            
            .print-button {
                display: none;
            }
        }
    </style>
</head>
<body>
    <button class="print-button" onclick="window.print()">🖨️ IMPRIMIR / SALVAR PDF</button>
    
    <div class="container">
        <div class="header">
            <h1>📜 Súmula Oficial de Partida</h1>
            <p>Sistema de Gerenciamento de Jogos Escolares - JEM 2026</p>
        </div>
        
        <div class="section">
            <div class="section-title">Dados da Partida</div>
            <div class="info-grid">
                <div class="info-item">
                    <div class="info-label">Modalidade</div>
                    <div class="info-value"><?= htmlspecialchars($match['modality_name']) ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Categoria</div>
                    <div class="info-value"><?= htmlspecialchars($match['category_name']) ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Fase</div>
                    <div class="info-value"><?= htmlspecialchars($phaseName) ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Data e Hora</div>
                    <div class="info-value"><?= date('d/m/Y \à\s H:i', strtotime($match['scheduled_time'])) ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Local</div>
                    <div class="info-value"><?= htmlspecialchars($match['venue'] ?: 'A definir') ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Placar Final</div>
                    <div class="info-value" style="font-size: 18px; font-weight: bold;">
                        <?= $match['score_team_a'] ?> x <?= $match['score_team_b'] ?>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="section">
            <div class="section-title">Equipe A: <?= htmlspecialchars($match['team_a_name']) ?></div>
            <div class="team-section">
                <div style="margin-bottom: 15px;">
                    <strong>Técnico:</strong> <?= htmlspecialchars($match['team_a_coach'] ?: 'Não informado') ?><br>
                    <strong>Auxiliar:</strong> <?= htmlspecialchars($match['team_a_assistant'] ?: 'Não informado') ?>
                </div>
                <div class="team-name">Atletas:</div>
                <div class="athletes-list">
                    <?php foreach ($athletesA as $athlete): ?>
                        <div class="athlete-item">
                            <span class="athlete-number"><?= str_pad($athlete['jersey_number'] ?: '?', 2, '0', STR_PAD_LEFT) ?></span>
                            <?= htmlspecialchars($athlete['name_snapshot']) ?>
                            <?php if ($athlete['id'] == $match['team_a_captain_id']): ?>
                                <span class="captain">(C)</span>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        
        <div class="section">
            <div class="section-title">Equipe B: <?= htmlspecialchars($match['team_b_name']) ?></div>
            <div class="team-section">
                <div style="margin-bottom: 15px;">
                    <strong>Técnico:</strong> <?= htmlspecialchars($match['team_b_coach'] ?: 'Não informado') ?><br>
                    <strong>Auxiliar:</strong> <?= htmlspecialchars($match['team_b_assistant'] ?: 'Não informado') ?>
                </div>
                <div class="team-name">Atletas:</div>
                <div class="athletes-list">
                    <?php foreach ($athletesB as $athlete): ?>
                        <div class="athlete-item">
                            <span class="athlete-number"><?= str_pad($athlete['jersey_number'] ?: '?', 2, '0', STR_PAD_LEFT) ?></span>
                            <?= htmlspecialchars($athlete['name_snapshot']) ?>
                            <?php if ($athlete['id'] == $match['team_b_captain_id']): ?>
                                <span class="captain">(C)</span>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        
        <?php if (count($goals) > 0): ?>
        <div class="section">
            <div class="section-title">⚽ Gols</div>
            <ul class="event-list">
                <?php foreach ($goals as $goal): ?>
                    <li class="event-item">
                        <span class="event-time"><?= $goal['event_time'] ?>'</span> - 
                        <?= $goal['team_id'] == $match['team_a_id'] ? $match['team_a_name'] : $match['team_b_name'] ?> - 
                        Nº <?= $goal['jersey_number'] ?: '??' ?> 
                        <?= htmlspecialchars($goal['athlete_name'] ?: 'Atleta não identificado') ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>
        
        <?php if (count($cards) > 0): ?>
        <div class="section">
            <div class="section-title">🟨🟥 Cartões</div>
            <ul class="event-list">
                <?php foreach ($cards as $card): ?>
                    <li class="event-item">
                        <span class="event-time"><?= $card['event_time'] ?>'</span> - 
                        <?= $card['event_type'] === 'YELLOW_CARD' ? '🟨 Amarelo' : '🟥 Vermelho' ?> - 
                        <?= $card['team_id'] == $match['team_a_id'] ? $match['team_a_name'] : $match['team_b_name'] ?> - 
                        Nº <?= $card['jersey_number'] ?: '??' ?> 
                        <?= htmlspecialchars($card['athlete_name'] ?: 'Atleta não identificado') ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>
        
        <?php if (count($subs) > 0): ?>
        <div class="section">
            <div class="section-title">🔄 Substituições</div>
            <ul class="event-list">
                <?php foreach ($subs as $sub): ?>
                    <li class="event-item">
                        <span class="event-time"><?= $sub['event_time'] ?>'</span> - 
                        <?= $sub['team_id'] == $match['team_a_id'] ? $match['team_a_name'] : $match['team_b_name'] ?> - 
                        SAIU: <?= htmlspecialchars($sub['athlete_name'] ?: 'N/A') ?> → 
                        ENTROU: <?= htmlspecialchars($sub['athlete_in_name'] ?: 'N/A') ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>
        
        <div class="section">
            <div class="section-title">👨‍⚖️ Arbitragem</div>
            <div class="info-grid">
                <div class="info-item">
                    <div class="info-label">Árbitro Principal</div>
                    <div class="info-value"><?= htmlspecialchars($match['referee_primary'] ?: 'Não informado') ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Árbitro Assistente</div>
                    <div class="info-value"><?= htmlspecialchars($match['referee_assistant'] ?: 'Não informado') ?></div>
                </div>
                <div class="info-item" style="grid-column: span 2;">
                    <div class="info-label">4º Árbitro</div>
                    <div class="info-value"><?= htmlspecialchars($match['referee_fourth'] ?: 'Não informado') ?></div>
                </div>
            </div>
        </div>
        
        <?php if (!empty($match['observations'])): ?>
        <div class="section">
            <div class="section-title">📝 Observações do Árbitro</div>
            <div class="observations"><?= htmlspecialchars($match['observations']) ?></div>
        </div>
        <?php endif; ?>
        
        <div class="footer">
            <p>Documento gerado automaticamente pelo Sistema JEM</p>
            <p><?= date('d/m/Y H:i:s') ?></p>
        </div>
    </div>
</body>
</html>
