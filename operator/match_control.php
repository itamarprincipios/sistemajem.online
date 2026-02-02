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
    </style>
</head>
<body>

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
            <button class="btn-goal" onclick="openGoalModal('A')">⚽ GOL TIME A</button>
            <button class="btn btn-secondary" onclick="alert('Cartão não implementado no MVP')">🟨 Cartão</button>
        </div>
        <div class="team-controls">
            <button class="btn-goal" onclick="openGoalModal('B')">⚽ GOL TIME B</button>
            <button class="btn btn-secondary">🟨 Cartão</button>
        </div>
    </div>

    <div class="status-bar">
        <?php if ($match['status'] === 'scheduled'): ?>
            <button class="btn btn-primary" style="width: 100%" onclick="updateStatus('live')">▶️ INICIAR PARTIDA</button>
        <?php elseif ($match['status'] === 'live'): ?>
            <button class="btn btn-danger" style="width: 100%" onclick="updateStatus('finished')">🏁 ENCERRAR PARTIDA</button>
        <?php else: ?>
            <div style="width: 100%; text-align: center;">PARTIDA FINALIZADA</div>
        <?php endif; ?>
    </div>

    <!-- Modal Athletes Team A -->
    <div id="modalA" class="modal-sheet">
        <h3>Quem fez o gol? (Time A)</h3>
        <div class="athlete-list">
            <?php foreach ($athletesA as $at): ?>
                <button class="athlete-btn" onclick="registerGoal(<?php echo $match['team_a_id']; ?>, <?php echo $at['id']; ?>, 'A')">
                    <?php echo $at['jersey_number'] ? "#{$at['jersey_number']} " : ''; ?> <?php echo $at['name_snapshot']; ?>
                </button>
            <?php endforeach; ?>
            <button class="athlete-btn" style="background: #ef4444;" onclick="closeModals()">Cancelar</button>
        </div>
    </div>

    <!-- Modal Athletes Team B -->
    <div id="modalB" class="modal-sheet">
        <h3>Quem fez o gol? (Time B)</h3>
        <div class="athlete-list">
             <?php foreach ($athletesB as $at): ?>
                <button class="athlete-btn" onclick="registerGoal(<?php echo $match['team_b_id']; ?>, <?php echo $at['id']; ?>, 'B')">
                    <?php echo $at['jersey_number'] ? "#{$at['jersey_number']} " : ''; ?> <?php echo $at['name_snapshot']; ?>
                </button>
            <?php endforeach; ?>
            <button class="athlete-btn" style="background: #ef4444;" onclick="closeModals()">Cancelar</button>
        </div>
    </div>

    <script>
        const matchId = <?php echo $matchId; ?>;
        
        function openGoalModal(team) {
            closeModals();
            document.getElementById('modal' + team).classList.add('active');
        }
        
        function closeModals() {
            document.querySelectorAll('.modal-sheet').forEach(m => m.classList.remove('active'));
        }

        async function registerGoal(teamId, athleteId, teamSide) {
            closeModals();
            
            try {
                // Optimistic Update
                const scoreId = 'score' + teamSide;
                const el = document.getElementById(scoreId);
                let current = parseInt(el.textContent);
                el.textContent = current + 1;
                
                // API Call
                const res = await fetch('../api/match-events-api.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({
                        action: 'event',
                        match_id: matchId,
                        team_id: teamId,
                        athlete_id: athleteId,
                        event_type: 'GOAL'
                    })
                });
                
                const data = await res.json();
                if(!data.success) {
                    alert('Erro ao salvar gol!');
                    el.textContent = current; // Revert
                }
            } catch (e) {
                console.error(e);
            }
        }

        async function updateStatus(status) {
            if(!confirm('Confirmar mudança de status?')) return;
            
            await fetch('../api/match-events-api.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({
                    action: 'status',
                    match_id: matchId,
                    status: status
                })
            });
            window.location.reload();
        }
        
        // Simple Stopwatch (Client side only for demo)
        // In productio sync with server start_time
        let seconds = 0;
        setInterval(() => {
            seconds++;
            const m = Math.floor(seconds / 60).toString().padStart(2, '0');
            const s = (seconds % 60).toString().padStart(2, '0');
            document.getElementById('gameTimer').textContent = `${m}:${s}`;
        }, 1000);
    </script>
</body>
</html>
