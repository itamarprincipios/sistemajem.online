<?php
require_once '../config/config.php';
require_once '../includes/auth.php';
require_once '../includes/db.php';

requireLogin(); // Should be requireOperator() but we'll use requireLogin temporarily and check role
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] === 'professor') {
    die("Acesso negado.");
}

$pageTitle = 'Painel do Operador';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>JEM - Operador</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
    <style>
        body { background: #0f172a; color: white; }
        .op-header { padding: 1rem; border-bottom: 1px solid #334155; display: flex; justify-content: space-between; align-items: center; }
        .match-card { background: #1e293b; padding: 1.5rem; border-radius: 12px; margin-bottom: 1rem; border: 1px solid #334155; }
        .match-time { color: #94a3b8; font-size: 0.9rem; margin-bottom: 0.5rem; }
        .match-teams { font-size: 1.25rem; font-weight: bold; margin-bottom: 1rem; display: flex; justify-content: space-between; }
        .status-live { color: #ef4444; font-weight: bold; animation: pulse 2s infinite; }
        @keyframes pulse { 0% { opacity: 1; } 50% { opacity: 0.5; } 100% { opacity: 1; } }
    </style>
</head>
<body>
    <div class="op-header">
        <div style="font-weight: bold;">PAINEL DE JOGO</div>
        <div>
            User: <?php echo htmlspecialchars($_SESSION['user_name']); ?>
            <a href="../logout.php" style="color: #ef4444; margin-left: 1rem; text-decoration: none;">Sair</a>
        </div>
    </div>

    <div style="padding: 1rem; max-width: 600px; margin: 0 auto;">
        <h2 style="margin-bottom: 1.5rem;">Meus Jogos</h2>
        <div id="matchesList">Carregando...</div>
    </div>

<script>
async function loadMatches() {
    try {
        // Fetch matches assigned to this operator (via venue/modality or all)
        // For now, listing all active matches of the day
        const res = await fetch('../api/matches-api.php?action=list'); 
        const data = await res.json();
        
        const list = document.getElementById('matchesList');
        list.innerHTML = '';
        
        if (data.data.length === 0) {
            list.innerHTML = '<p style="text-align:center; color: #64748b;">Nenhum jogo encontrado.</p>';
            return;
        }
        
        data.data.forEach(m => {
            const isLive = m.status === 'live';
            const isFinished = m.status === 'finished';
            
            const div = document.createElement('div');
            div.className = 'match-card';
            div.innerHTML = `
                <div style="display:flex; justify-content:space-between;">
                    <span class="match-time">📅 ${new Date(m.scheduled_time).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})} - ${m.category_name}</span>
                    <span class="${isLive ? 'status-live' : ''}">${isLive ? '● AO VIVO' : (isFinished ? 'Finalizado' : 'Agendado')}</span>
                </div>
                <div class="match-teams">
                    <span>${m.team_a_name}</span>
                    <span style="color:#64748b">vs</span>
                    <span>${m.team_b_name}</span>
                </div>
                <div style="text-align:center;">
                    ${isFinished 
                        ? `<button class="btn btn-secondary" disabled>Encerrado</button>` 
                        : `<a href="match_control.php?id=${m.id}" class="btn ${isLive ? 'btn-danger' : 'btn-primary'}" style="width:100%; display:block; text-align:center; text-decoration:none;">${isLive ? 'RETOMAR CONTROLE' : 'INICIAR PARTIDA'}</a>`
                    }
                </div>
            `;
            list.appendChild(div);
        });
    } catch(e) {
        console.error(e);
    }
}
loadMatches();
</script>
</body>
</html>
