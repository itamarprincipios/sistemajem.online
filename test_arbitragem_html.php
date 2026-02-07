<?php
require_once 'config/config.php';
require_once 'includes/auth.php';
require_once 'includes/db.php';

requireLogin();

$matchId = $_GET['match_id'] ?? 508;
$match = queryOne("SELECT * FROM matches WHERE id = ?", [$matchId]);

if (!$match) {
    die("Partida não encontrada");
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Test Arbitragem Section</title>
</head>
<body>
    <h2>TEST: Seção de Arbitragem</h2>
    
    <h3>Valores das variáveis:</h3>
    <pre>
referee_primary: <?= var_export($match['referee_primary'], true) ?>

referee_assistant: <?= var_export($match['referee_assistant'], true) ?>

referee_fourth: <?= var_export($match['referee_fourth'], true) ?>
    </pre>
    
    <h3>HTML Renderizado (igual ao sumula.php):</h3>
    <div style="border: 2px solid red; padding: 20px;">
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
    </div>
    
    <h3>Código-fonte da seção:</h3>
    <pre><?= htmlspecialchars('
<div class="section">
    <div class="section-title">👨‍⚖️ Arbitragem</div>
    <div class="info-grid">
        <div class="info-item">
            <div class="info-label">Árbitro Principal</div>
            <div class="info-value">' . htmlspecialchars($match['referee_primary'] ?: 'Não informado') . '</div>
        </div>
        <div class="info-item">
            <div class="info-label">Árbitro Assistente</div>
            <div class="info-value">' . htmlspecialchars($match['referee_assistant'] ?: 'Não informado') . '</div>
        </div>
        <div class="info-item" style="grid-column: span 2;">
            <div class="info-label">4º Árbitro</div>
            <div class="info-value">' . htmlspecialchars($match['referee_fourth'] ?: 'Não informado') . '</div>
        </div>
    </div>
</div>
    ') ?></pre>
</body>
</html>
