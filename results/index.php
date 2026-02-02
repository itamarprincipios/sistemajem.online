<?php
require_once '../config/config.php'; // Public access, but need DB config
require_once '../includes/db.php';

// Get Active Event
$event = queryOne("SELECT * FROM competition_events WHERE active_flag = 1 LIMIT 1");

if (!$event) {
    // Fallback: get latest live or planned
    $event = queryOne("SELECT * FROM competition_events ORDER BY created_at DESC LIMIT 1");
}

$pageTitle = 'JEM - Resultados';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>JEM - Resultados <?php echo $event ? htmlspecialchars($event['name']) : ''; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root { --primary: #10b981; --dark: #0f172a; --card: #1e293b; --text: #f8fafc; }
        body { margin: 0; font-family: 'Inter', sans-serif; background: var(--dark); color: var(--text); }
        
        .header { background: linear-gradient(to right, #059669, #10b981); padding: 1.5rem; text-align: center; box-shadow: 0 4px 20px rgba(0,0,0,0.3); }
        .header h1 { margin: 0; font-weight: 800; letter-spacing: -1px; }
        .header p { margin: 0.5rem 0 0; opacity: 0.9; }
        
        .container { max-width: 800px; margin: 0 auto; padding: 2rem 1rem; }
        
        .section-title { font-size: 0.9rem; text-transform: uppercase; letter-spacing: 1px; color: #94a3b8; margin-bottom: 1rem; border-bottom: 1px solid #334155; padding-bottom: 0.5rem; }
        
        .match-card { background: var(--card); border-radius: 16px; padding: 1.5rem; margin-bottom: 1.5rem; display: grid; gap: 1rem; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); border: 1px solid #334155; position: relative; overflow: hidden; }
        
        .live-indicator { position: absolute; top: 1rem; right: 1rem; color: #ef4444; font-weight: bold; font-size: 0.8rem; display: flex; align-items: center; gap: 0.5rem; }
        .live-dot { width: 8px; height: 8px; background: #ef4444; border-radius: 50%; animation: pulse 1.5s infinite; }
        @keyframes pulse { 0% { opacity: 1; transform: scale(1); } 50% { opacity: 0.5; transform: scale(1.2); } 100% { opacity: 1; transform: scale(1); } }
        
        .scoreboard { display: flex; justify-content: space-between; align-items: center; margin: 0.5rem 0; }
        .team { text-align: center; flex: 1; }
        .team-name { font-weight: 600; font-size: 1.1rem; margin-bottom: 0.5rem; color: #e2e8f0; }
        .score { font-size: 3rem; font-weight: 800; font-variant-numeric: tabular-nums; }
        
        .match-info { text-align: center; font-size: 0.85rem; color: #64748b; }
        
        .empty-state { text-align: center; padding: 3rem; color: #64748b; }
    </style>
</head>
<body>

    <header class="header">
        <h1>🏆 <?php echo $event ? htmlspecialchars($event['name']) : 'JEM - Jogos Escolares'; ?></h1>
        <p>Acompanhamento em Tempo Real</p>
    </header>

    <div class="container">
        
        <div id="live-matches">
            <!-- Populated by JS -->
        </div>

        <div id="finished-matches">
            <!-- Populated by JS -->
        </div>

    </div>

<script>
    const API_URL = '../api/matches-api.php?action=list&event_id=<?php echo $event['id']; ?>';

    async function loadResults() {
        try {
            const res = await fetch(API_URL);
            const data = await res.json();
            
            const liveDiv = document.getElementById('live-matches');
            const finishedDiv = document.getElementById('finished-matches');
            
            let liveHtml = '<div class="section-title">AO VIVO AGORA</div>';
            let finishedHtml = '<div class="section-title">RESULTADOS RECENTES</div>';
            let hasLive = false;
            let hasFinished = false;
            
            data.data.forEach(m => {
                if (m.status === 'live') {
                    hasLive = true;
                    liveHtml += createCard(m, true);
                } else if (m.status === 'finished') {
                    hasFinished = true;
                    finishedHtml += createCard(m, false);
                }
            });
            
            if (!hasLive) liveHtml = '';
            
            liveDiv.innerHTML = liveHtml;
            finishedDiv.innerHTML = finishedHtml;
            
        } catch(e) {
            console.error('Err', e);
        }
    }

    function createCard(m, isLive) {
        return `
            <div class="match-card" style="${isLive ? 'border-color: #059669;' : ''}">
                ${isLive ? '<div class="live-indicator"><div class="live-dot"></div> EM ANDAMENTO</div>' : ''}
                <div class="match-info">${m.category_name} - ${m.modality_name} • ${isLive ? '⏱️ JOGANDO' : '🏁 FINALIZADO'}</div>
                
                <div class="scoreboard">
                    <div class="team">
                        <div class="team-name">${m.team_a_name}</div>
                        <div class="score">${m.score_team_a}</div>
                    </div>
                    <div style="font-size: 1.5rem; color: #475569;">x</div>
                    <div class="team">
                         <div class="team-name">${m.team_b_name}</div>
                         <div class="score">${m.score_team_b}</div>
                    </div>
                </div>
            </div>
        `;
    }

    // Auto Refresh
    loadResults();
    setInterval(loadResults, 10000); // 10s polling
</script>
</body>
</html>
