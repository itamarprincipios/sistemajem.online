<?php
require_once '../config/config.php';
require_once '../includes/auth.php';
require_once '../includes/db.php';

requireLogin();

$matchId = $_GET['id'] ?? 0;
// Verify permission (skipped for MVP speed, assuming operator is valid)

// Load Match Data
$match = queryOne("
    SELECT m.*, 
           t1.school_name_snapshot as team_a_name, 
           t2.school_name_snapshot as team_b_name
    FROM matches m
    JOIN competition_teams t1 ON m.team_a_id = t1.id
    JOIN competition_teams t2 ON m.team_b_id = t2.id
    WHERE m.id = ?
", [$matchId]);

if (!$match) die("Partida não encontrada");

// Load Athletes for Modals
$athletesA = query("SELECT id, name_snapshot, jersey_number FROM competition_team_athletes WHERE competition_team_id = ?", [$match['team_a_id']]);
$athletesB = query("SELECT id, name_snapshot, jersey_number FROM competition_team_athletes WHERE competition_team_id = ?", [$match['team_b_id']]);

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Controle de Partida #<?php echo $matchId; ?></title>
    <link rel="stylesheet" href="../assets/css/styles.css">
    <style>
        body { background: #000; color: white; overflow-x: hidden; }
        .score-board { display: grid; grid-template-columns: 1fr auto 1fr; gap: 1rem; align-items: center; padding: 1rem; background: #111; border-bottom: 2px solid #333; }
        .team-box { text-align: center; }
        .team-name { font-size: 1.2rem; font-weight: bold; margin-bottom: 0.5rem; color: #ccc; }
        .team-score { font-size: 4rem; font-weight: 800; color: #fff; line-height: 1; }
        .timer-box { text-align: center; }
        .timer { font-size: 2.5rem; font-family: monospace; font-weight: bold; color: #fbbf24; }
        
        .controls { padding: 1rem; display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; height: calc(100vh - 250px); align-content: start; }
        .team-controls { display: flex; flex-direction: column; gap: 1rem; }
        
        .btn-goal { background: #10b981; color: white; border: none; padding: 1.5rem; font-size: 1.5rem; font-weight: bold; border-radius: 12px; cursor: pointer; box-shadow: 0 4px #059669; }
        .btn-goal:active { transform: translateY(4px); box-shadow: none; }
        
        .status-bar { padding: 1rem; background: #222; display: flex; justify-content: space-between; align-items: center; position: fixed; bottom: 0; width: 100%; box-sizing: border-box; }
        
        .modal-sheet { display: none; position: fixed; bottom: 0; left: 0; width: 100%; background: #1e293b; border-radius: 20px 20px 0 0; z-index: 1000; padding: 1rem; max-height: 80vh; overflow-y: auto; }
        .modal-sheet.active { display: block; animation: slideUp 0.3s; }
        @keyframes slideUp { from { transform: translateY(100%); } to { transform: translateY(0); } }
        
        .athlete-list { display: grid; gap: 0.5rem; margin-top: 1rem; }
        .athlete-btn { background: #334155; border: 1px solid #475569; color: white; padding: 1rem; text-align: left; border-radius: 8px; font-size: 1.1rem; }
        
        /* Loading State */
        .btn.loading { opacity: 0.7; pointer-events: none; position: relative; }
        .btn.loading::after {
            content: "";
            position: absolute;
            width: 16px;
            height: 16px;
            top: 50%;
            left: 50%;
            margin-top: -8px;
            margin-left: -8px;
            border: 2px solid white;
            border-top-color: transparent;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }
        @keyframes spin { to { transform: rotate(360deg); } }
    </style>
</head>
<body>

    <div style="padding: 1rem; border-bottom: 1px solid #333;">
        <a href="dashboard.php" style="color: #94a3b8; text-decoration: none; display: flex; align-items: center; gap: 0.5rem; font-weight: 600;">
            ⬅️ Voltar ao Painel
        </a>
    </div>

    <div class="score-board">
        <div class="team-box">
            <div class="team-name"><?php echo $match['team_a_name']; ?></div>
            <div class="team-score" id="scoreA"><?php echo $match['score_team_a']; ?></div>
        </div>
        <div class="timer-box">
            <div class="timer" id="gameTimer">00:00</div>
            <div style="font-size: 0.8rem; color: #666;">TEMPO DE JOGO</div>
        </div>
        <div class="team-box">
            <div class="team-name"><?php echo $match['team_b_name']; ?></div>
            <div class="team-score" id="scoreB"><?php echo $match['score_team_b']; ?></div>
        </div>
    </div>

    <div class="controls">
        <div class="team-controls">
            <button class="btn-goal" onclick="openEventModal('A', 'GOAL')">⚽ GOL TIME A</button>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.5rem;">
                <button class="btn btn-secondary" style="background: #fbbf24; color: black; border: none;" onclick="openEventModal('A', 'YELLOW_CARD')">🟨 Cartão</button>
                <button class="btn btn-secondary" style="background: #ef4444; color: white; border: none;" onclick="openEventModal('A', 'RED_CARD')">🟥 Cartão</button>
            </div>
        </div>
        <div class="team-controls">
            <button class="btn-goal" onclick="openEventModal('B', 'GOAL')">⚽ GOL TIME B</button>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.5rem;">
                <button class="btn btn-secondary" style="background: #fbbf24; color: black; border: none;" onclick="openEventModal('B', 'YELLOW_CARD')">🟨 Cartão</button>
                <button class="btn btn-secondary" style="background: #ef4444; color: white; border: none;" onclick="openEventModal('B', 'RED_CARD')">🟥 Cartão</button>
            </div>
        </div>
    </div>

    <div class="status-bar">
        <?php if ($match['status'] === 'scheduled'): ?>
            <button class="btn btn-primary" style="width: 100%" onclick="updateStatus('live', this)">▶️ INICIAR PARTIDA</button>
        <?php elseif ($match['status'] === 'live'): ?>
            <button class="btn btn-danger" style="width: 100%" onclick="updateStatus('finished', this)">🏁 ENCERRAR PARTIDA</button>
        <?php else: ?>
            <div style="width: 100%; text-align: center;">PARTIDA FINALIZADA</div>
        <?php endif; ?>
    </div>

    <!-- Dynamic Modal -->
    <div id="eventModal" class="modal-sheet">
        <h3 id="modalTitle">Selecionar Atleta</h3>
        <div id="modalAthleteList" class="athlete-list">
            <!-- Populated by JS -->
        </div>
        <button class="athlete-btn" style="background: #ef4444; margin-top: 1rem; width: 100%;" onclick="closeModals()">Cancelar</button>
    </div>

    <script>
        const matchId = <?php echo $matchId; ?>;
        const matchStatus = '<?php echo $match['status']; ?>';
        
        // Robust sync: Calculate elapsed seconds ON THE SERVER
        <?php 
            $elapsed = 0;
            if ($match['status'] === 'live' && $match['start_time']) {
                $elapsed = time() - strtotime($match['start_time']);
                if ($elapsed < 0) $elapsed = 0; // Resilience against minor clock skew
            }
        ?>
        let seconds = <?php echo $elapsed; ?>;
        
        console.log("Match Status:", matchStatus);
        console.log("Elapsed seconds from server:", seconds);

        const athletesA = <?php echo json_encode($athletesA); ?>;
        const athletesB = <?php echo json_encode($athletesB); ?>;
        const teamA_id = <?php echo $match['team_a_id']; ?>;
        const teamB_id = <?php echo $match['team_b_id']; ?>;

        const apiBase = window.location.origin + '/api/match-events-api.php';
        console.log("API Base URL:", apiBase);

        // Connection test on load
        fetch(apiBase, {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({action: 'ping'})
        }).then(r => r.json()).then(d => console.log("API Ping Test:", d)).catch(e => console.error("API Ping Failed:", e));

        function openEventModal(teamSide, eventType) {
            closeModals();
            const modal = document.getElementById('eventModal');
            const title = document.getElementById('modalTitle');
            const list = document.getElementById('modalAthleteList');
            
            const eventLabels = { 'GOAL': 'GOL', 'YELLOW_CARD': 'Cartão Amarelo', 'RED_CARD': 'Cartão Vermelho' };
            title.textContent = `${eventLabels[eventType]} - Selecionar Atleta`;
            
            const teamId = teamSide === 'A' ? teamA_id : teamB_id;
            const athletes = teamSide === 'A' ? athletesA : athletesB;
            
            list.innerHTML = athletes.map(at => `
                <button class="athlete-btn" onclick="registerEvent(${teamId}, ${at.id}, '${eventType}', '${teamSide}')">
                    ${at.jersey_number ? '#' + at.jersey_number + ' ' : ''} ${at.name_snapshot}
                </button>
            `).join('') + `
                <button class="athlete-btn" style="background: #4b5563;" onclick="registerEvent(${teamId}, null, '${eventType}', '${teamSide}')">
                    Atleta não listado
                </button>
            `;
            
            modal.classList.add('active');
        }
        
        function closeModals() {
            document.querySelectorAll('.modal-sheet').forEach(m => m.classList.remove('active'));
        }

        async function registerEvent(teamId, athleteId, eventType, teamSide) {
            closeModals();
            
            try {
                // Optimistic Update for Score
                if (eventType === 'GOAL') {
                    const scoreId = 'score' + teamSide;
                    const el = document.getElementById(scoreId);
                    el.textContent = parseInt(el.textContent) + 1;
                }
                
                const res = await fetch('../api/match-events-api.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({
                        action: 'event',
                        match_id: matchId,
                        team_id: teamId,
                        athlete_id: athleteId,
                        event_type: eventType
                    })
                });
                
                const data = await res.json();
                if(!data.success) {
                    alert('Erro ao salvar evento!');
                    window.location.reload(); // Revert
                }
            } catch (e) {
                console.error(e);
            }
        }

        async function updateStatus(status, btn) {
            if(!confirm('Confirmar mudança de status?')) return;
            
            if (btn) btn.classList.add('loading');
            
            try {
                const payload = {
                    action: 'status',
                    match_id: matchId,
                    status: status
                };
                console.log("Sending status update to:", apiBase, payload);

                const res = await fetch(apiBase + '?t=' + Date.now(), {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify(payload)
                });
                
                if (!res.ok) {
                    throw new Error(`HTTP Error: ${res.status}`);
                }

                const data = await res.json();
                console.log("Status update response:", data);

                if (data.success) {
                    window.location.reload();
                } else {
                    alert('Erro ao atualizar status: ' + (data.error || 'Erro desconhecido'));
                    if (btn) btn.classList.remove('loading');
                }
            } catch (e) {
                alert('Erro de conexão ou erro no servidor: ' + e.message);
                console.error("Fetch error:", e);
                if (btn) btn.classList.remove('loading');
            }
        }
        
        // Chronometer logic
        if (matchStatus === 'live') {
            const updateUI = () => {
                const m = Math.floor(seconds / 60).toString().padStart(2, '0');
                const s = (seconds % 60).toString().padStart(2, '0');
                document.getElementById('gameTimer').textContent = `${m}:${s}`;
            };
            
            updateUI();
            setInterval(() => {
                seconds++;
                updateUI();
            }, 1000);
        } else if (matchStatus === 'finished') {
             document.getElementById('gameTimer').textContent = 'ENCERRADO';
        } else {
             document.getElementById('gameTimer').textContent = '00:00';
        }
    </script>
</body>
</html>
