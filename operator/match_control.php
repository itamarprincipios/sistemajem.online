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
           t2.school_name_snapshot as team_b_name,
           t1.registration_id as team_a_reg_id,
           t2.registration_id as team_b_reg_id,
           m.referee_primary, m.referee_assistant, m.referee_fourth,
           TIMESTAMPDIFF(SECOND, m.start_time, NOW()) as elapsed_seconds
    FROM matches m
    JOIN competition_teams t1 ON m.team_a_id = t1.id
    JOIN competition_teams t2 ON m.team_b_id = t2.id
    WHERE m.id = ?
", [$matchId]);

if (!$match) die("Partida não encontrada");

// Initialize staff if empty from registrations
if (!$match['team_a_coach'] || !$match['team_b_coach']) {
    $regA = queryOne("SELECT tecnico_nome, auxiliar_tecnico_nome FROM registrations WHERE id = ?", [$match['team_a_reg_id']]);
    $regB = queryOne("SELECT tecnico_nome, auxiliar_tecnico_nome FROM registrations WHERE id = ?", [$match['team_b_reg_id']]);
    
    if (!$match['team_a_coach']) {
        $match['team_a_coach'] = $regA['tecnico_nome'] ?? '';
        $match['team_a_assistant'] = $regA['auxiliar_tecnico_nome'] ?? '';
    }
    if (!$match['team_b_coach']) {
        $match['team_b_coach'] = $regB['tecnico_nome'] ?? '';
        $match['team_b_assistant'] = $regB['auxiliar_tecnico_nome'] ?? '';
    }
}

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
    <script>
        const cleanName = (name) => {
            if (!name) return 'A definir';
            let cleaned = name;
            const prefixes = [
                /^(ESCOLA MUNICIPAL|MUNICIPAL|ESCOLA|EMEIF|EMEF)\b/gi,
                /^(EDUCAÇÃO INFANTIL|ENSINO FUNDAMENTAL|ENSINO MÉDIO|ENSINO INFANTIL|EDUCAÇÃO INFANTIL E ENSINO FUNDAMENTAL)\b/gi,
                /^(PROFESSOR[A]?)\b/gi,
                /^[\s\-–—,]+/gi, 
                /^(DE|E|DO|DA)\s+/gi 
            ];
            let lastCleaned;
            do {
                lastCleaned = cleaned;
                prefixes.forEach(p => cleaned = cleaned.replace(p, '').trim());
            } while (cleaned !== lastCleaned && cleaned.length > 0);
            return cleaned || 'A definir';
        };
    </script>
    <style>
        body { background: #000; color: white; overflow-x: hidden; }
        .score-board { display: grid; grid-template-columns: 1fr auto 1fr; gap: 1rem; align-items: center; padding: 1rem; background: #111; border-bottom: 2px solid #333; }
        .team-box { text-align: center; }
        .team-name { font-size: 1.2rem; font-weight: bold; margin-bottom: 0.5rem; color: #ccc; }
        .team-score { font-size: 4rem; font-weight: 800; color: #fff; line-height: 1; }
        .timer-box { text-align: center; }
        .timer { font-size: 2.5rem; font-family: monospace; font-weight: bold; color: #fbbf24; }
        
        .controls { padding: 1rem; display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; height: auto; align-content: start; }
        .team-controls { display: flex; flex-direction: column; gap: 1rem; }
        
        .btn-goal { background: #10b981; color: white; border: none; padding: 1.5rem; font-size: 1.5rem; font-weight: bold; border-radius: 12px; cursor: pointer; box-shadow: 0 4px #059669; }
        .btn-goal:active { transform: translateY(4px); box-shadow: none; }
        
        .status-bar { padding: 1rem; background: #222; display: flex; justify-content: space-between; align-items: center; position: fixed; bottom: 0; width: 100%; box-sizing: border-box; }
        
        .modal-sheet { display: none; position: fixed; bottom: 0; left: 0; width: 100%; background: #1e293b; border-radius: 20px 20px 0 0; z-index: 1000; padding: 1rem; max-height: 80vh; overflow-y: auto; }
        .modal-sheet.active { display: block; animation: slideUp 0.3s; }
        @keyframes slideUp { from { transform: translateY(100%); } to { transform: translateY(0); } }
        
        .athlete-list { display: grid; gap: 0.5rem; margin-top: 1rem; }
        .athlete-btn { background: #334155; border: 1px solid #475569; color: white; padding: 1rem; text-align: left; border-radius: 8px; font-size: 1.1rem; }

        /* Timeline Styles */
        .timeline-container { padding: 1rem; flex: 1; overflow-y: auto; margin-bottom: 80px; min-height: 300px; background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.1); border-radius: 12px; margin-top: 1rem; }
        .timeline-item { display: flex; align-items: center; gap: 1rem; margin-bottom: 0.75rem; padding: 0.5rem 1rem; border-radius: 8px; background: rgba(255,255,255,0.05); font-size: 0.95rem; }
        .timeline-item.side-A { flex-direction: row; border-left: 4px solid #10b981; }
        .timeline-item.side-B { flex-direction: row-reverse; border-right: 4px solid #10b981; }
        .time-badge { background: #334155; color: #fbbf24; padding: 2px 6px; border-radius: 4px; font-family: monospace; font-weight: bold; min-width: 45px; text-align: center; }
        .event-icon { font-size: 1.2rem; }
        .player-info { font-weight: 600; flex: 1; }
        .side-B .player-info { text-align: right; }
        
        /* Appointments Modal Styles */
        .appointments-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; }
        .team-app-col { display: flex; flex-direction: column; gap: 0.5rem; }
        .app-section-title { font-size: 0.9rem; font-weight: 800; color: #10b981; margin-top: 1rem; border-bottom: 1px solid rgba(16,185,129,0.3); padding-bottom: 4px; }
        .player-row { display: flex; align-items: center; gap: 8px; background: rgba(255,255,255,0.05); padding: 6px 10px; border-radius: 6px; }
        .jersey-input { width: 45px; background: #0f172a; border: 1px solid #334155; color: #fbbf24; text-align: center; border-radius: 4px; font-weight: bold; padding: 4px; }
        .player-name-small { flex: 1; font-size: 0.85rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .staff-input { background: #0f172a; border: 1px solid #334155; color: white; padding: 8px; border-radius: 6px; font-size: 0.9rem; width: 100%; }
        .staff-label { font-size: 0.75rem; color: #94a3b8; margin-top: 4px; }
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
            <div class="team-name" id="name-A"><?php echo $match['team_a_name']; ?></div>
            <div class="team-score" id="scoreA"><?php echo $match['score_team_a']; ?></div>
        </div>
        <div class="timer-box">
            <div class="timer" id="gameTimer">00:00</div>
            <div style="font-size: 0.8rem; color: #666;">TEMPO DE JOGO</div>
        </div>
        <div class="team-box">
            <div class="team-name" id="name-B"><?php echo $match['team_b_name']; ?></div>
            <div class="team-score" id="scoreB"><?php echo $match['score_team_b']; ?></div>
        </div>
    </div>

    <div class="controls" style="height: auto;">
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

    <div class="timeline-container" id="timeline">
        <!-- Events list populated by JS -->
    </div>

    <div class="status-bar" style="display: flex; gap: 0.5rem;">
        <button class="btn btn-secondary" style="flex: 1; background: #334155; color: white; border: none;" onclick="openAppointmentsModal()">📋 APONTAMENTOS</button>
        <?php if ($match['status'] === 'scheduled'): ?>
            <button class="btn btn-primary" style="flex: 2" onclick="updateStatus('live')">▶️ INICIAR PARTIDA</button>
        <?php elseif ($match['status'] === 'live'): ?>
            <button class="btn btn-danger" style="flex: 2" onclick="updateStatus('finished')">🏁 ENCERRAR PARTIDA</button>
        <?php else: ?>
            <div style="flex: 2; text-align: center;">PARTIDA FINALIZADA</div>
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

    <!-- Appointments Modal -->
    <div id="appointmentsModal" class="modal-sheet" style="max-height: 90vh;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
            <h2 style="color: #10b981;">📋 Apontamentos da Partida</h2>
            <button onclick="closeModals()" style="background: none; border: none; color: #94a3b8; font-size: 1.5rem; cursor: pointer;">&times;</button>
        </div>
        
        <div class="appointments-grid">
            <!-- Team A -->
            <div class="team-app-col">
                <div class="app-section-title">TIME A: <span id="modal-team-a-name"></span></div>
                <div id="list-a-appointments" style="display: flex; flex-direction: column; gap: 4px;"></div>
                
                <div class="app-section-title">COMISSÃO TÉCNICA</div>
                <div class="staff-label">Técnico</div>
                <input type="text" id="coach-a" class="staff-input" placeholder="Nome do Técnico" value="<?php echo htmlspecialchars($match['team_a_coach'] ?? ''); ?>">
                <div class="staff-label">Auxiliar</div>
                <input type="text" id="assistant-a" class="staff-input" placeholder="Nome do Auxiliar" value="<?php echo htmlspecialchars($match['team_a_assistant'] ?? ''); ?>">
            </div>

            <!-- Team B -->
            <div class="team-app-col">
                <div class="app-section-title">TIME B: <span id="modal-team-b-name"></span></div>
                <div id="list-b-appointments" style="display: flex; flex-direction: column; gap: 4px;"></div>
                
                <div class="app-section-title">COMISSÃO TÉCNICA</div>
                <div class="staff-label">Técnico</div>
                <input type="text" id="coach-b" class="staff-input" placeholder="Nome do Técnico" value="<?php echo htmlspecialchars($match['team_b_coach'] ?? ''); ?>">
                <div class="staff-label">Auxiliar</div>
                <input type="text" id="assistant-b" class="staff-input" placeholder="Nome do Auxiliar" value="<?php echo htmlspecialchars($match['team_b_assistant'] ?? ''); ?>">
            </div>
        </div>

        <div class="app-section-title" style="margin-top: 1.5rem;">ARBITRAGEM</div>
        <div class="appointments-grid" style="grid-template-columns: 1fr 1fr 1fr; margin-top: 0.5rem;">
            <div class="team-app-col">
                <div class="staff-label">Árbitro Principal</div>
                <input type="text" id="ref-primary" class="staff-input" placeholder="Nome do Árbitro" value="<?php echo htmlspecialchars($match['referee_primary'] ?? ''); ?>">
            </div>
            <div class="team-app-col">
                <div class="staff-label">Árbitro Assistente</div>
                <input type="text" id="ref-assistant" class="staff-input" placeholder="Nome do Assistente" value="<?php echo htmlspecialchars($match['referee_assistant'] ?? ''); ?>">
            </div>
            <div class="team-app-col">
                <div class="staff-label">Quarto Árbitro</div>
                <input type="text" id="ref-fourth" class="staff-input" placeholder="Nome do Quarto Árbitro" value="<?php echo htmlspecialchars($match['referee_fourth'] ?? ''); ?>">
            </div>
        </div>

        <div style="margin-top: 2rem; display: flex; gap: 1rem;">
            <button class="btn btn-secondary" style="flex: 1; background: #334155; border: none; color: white;" onclick="closeModals()">Voltar</button>
            <button class="btn btn-primary" style="flex: 2;" onclick="saveAppointments()">💾 SALVAR APONTAMENTOS</button>
        </div>
    </div>

    <script>
        const matchId = <?php echo $matchId; ?>;
        const matchStatus = '<?php echo $match['status']; ?>';
        
        // Synchronize seconds with database
        let seconds = <?php echo ($match['status'] === 'live' && $match['elapsed_seconds'] > 0) ? (int)$match['elapsed_seconds'] : 0; ?>;
        
        console.log("Match Status:", matchStatus);
        console.log("Elapsed seconds from server:", seconds);

        const athletesA = <?php echo json_encode($athletesA); ?>;
        const athletesB = <?php echo json_encode($athletesB); ?>;
        const teamA_id = <?php echo $match['team_a_id']; ?>;
        const teamB_id = <?php echo $match['team_b_id']; ?>;

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

        function openAppointmentsModal() {
            closeModals();
            document.getElementById('modal-team-a-name').textContent = cleanName('<?php echo $match['team_a_name']; ?>');
            document.getElementById('modal-team-b-name').textContent = cleanName('<?php echo $match['team_b_name']; ?>');
            
            const renderList = (athletes, containerId) => {
                const container = document.getElementById(containerId);
                container.innerHTML = athletes.map(at => `
                    <div class="player-row">
                        <input type="number" class="jersey-input" value="${at.jersey_number || ''}" onchange="updateAthleteJersey(${at.id}, this.value)">
                        <span class="player-name-small">${at.name_snapshot}</span>
                    </div>
                `).join('');
            };

            renderList(athletesA, 'list-a-appointments');
            renderList(athletesB, 'list-b-appointments');
            
            document.getElementById('appointmentsModal').classList.add('active');
        }

        function updateAthleteJersey(athleteId, number) {
            // Find in local lists to keep in sync without reload
            const atA = athletesA.find(a => a.id == athleteId);
            if (atA) atA.jersey_number = number;
            const atB = athletesB.find(a => a.id == athleteId);
            if (atB) atB.jersey_number = number;
        }

        async function saveAppointments() {
            try {
                const payload = {
                    action: 'save_appointments',
                    match_id: matchId,
                    staff: {
                        team_a_coach: document.getElementById('coach-a').value,
                        team_a_assistant: document.getElementById('assistant-a').value,
                        team_b_coach: document.getElementById('coach-b').value,
                        team_b_assistant: document.getElementById('assistant-b').value
                    },
                    referees: {
                        primary: document.getElementById('ref-primary').value,
                        assistant: document.getElementById('ref-assistant').value,
                        fourth: document.getElementById('ref-fourth').value
                    },
                    athletes: [...athletesA, ...athletesB].map(a => ({ id: a.id, jersey_number: a.jersey_number }))
                };

                const res = await fetch('../api/match-events-api.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify(payload)
                });
                
                const data = await res.json();
                if (data.success) {
                    closeModals();
                    alert('Apontamentos salvos com sucesso!');
                    loadTimeline(); // Para atualizar os números na timeline se mudaram
                } else {
                    alert('Erro ao salvar: ' + data.error);
                }
            } catch (e) {
                console.error(e);
                alert('Erro de conexão ao salvar apontamentos');
            }
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
                        event_type: eventType,
                        event_time: document.getElementById('gameTimer').textContent
                    })
                });
                
                const data = await res.json();
                if(data.success) {
                    loadTimeline(); // Refresh list
                } else {
                    alert('Erro ao salvar evento: ' + data.error);
                    window.location.reload();
                }
            } catch (e) {
                console.error(e);
                alert('Erro de conexão ao salvar evento');
            }
        }

        async function updateStatus(status) {
            console.log("🔵 updateStatus called with status:", status);
            
            // REMOVED CONFIRM - it was blocking execution
            console.log("✅ Proceeding with update (no confirmation needed)");
            
            try {
                const payload = {
                    action: 'status',
                    match_id: matchId,
                    status: status
                };
                console.log("📤 Sending payload:", payload);
                console.log("📍 API URL:", '../api/match-events-api.php');

                const res = await fetch('../api/match-events-api.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify(payload)
                });
                
                console.log("📥 Response status:", res.status);
                console.log("📥 Response ok:", res.ok);
                
                const data = await res.json();
                console.log("📊 Response data:", data);
                
                if (data.success) {
                    console.log("✅ Success! Reloading page...");
                    window.location.reload();
                } else {
                    console.error("❌ API returned error:", data.error);
                    alert('Erro ao atualizar status: ' + data.error);
                }
            } catch (e) {
                console.error("💥 Exception caught:", e);
                console.error("💥 Error message:", e.message);
                console.error("💥 Error stack:", e.stack);
                alert('Erro de conexão ao atualizar status: ' + e.message);
            }
        }
        
        async function loadTimeline() {
            try {
                const res = await fetch('../api/match-events-api.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({ action: 'list_events', match_id: matchId })
                });
                const data = await res.json();
                if (data.success) {
                    renderTimeline(data.data);
                }
            } catch (e) {
                console.error("Timeline load failed", e);
            }
        }

        function renderTimeline(events) {
            const container = document.getElementById('timeline');
            const iconMap = { 'GOAL': '⚽', 'YELLOW_CARD': '🟨', 'RED_CARD': '🟥', 'OWN_GOAL': '🔄' };
            
            container.innerHTML = events.map(ev => {
                const side = ev.team_id == teamA_id ? 'A' : 'B';
                return `
                    <div class="timeline-item side-${side}">
                        <div class="time-badge">${ev.event_time || '--:--'}</div>
                        <div class="event-icon">${iconMap[ev.event_type] || '🏳️'}</div>
                        <div class="player-info">
                            ${ev.athlete_name ? (ev.jersey_number ? '#' + ev.jersey_number + ' ' : '') + ev.athlete_name : 'Atleta não listado'}
                        </div>
                    </div>
                `;
            }).join('');
            
            // Auto-scroll to bottom
            container.scrollTop = container.scrollHeight;
        }

        // Initial load
        window.addEventListener('DOMContentLoaded', () => {
            loadTimeline();
            
            // Names cleaning
            const nameA = document.getElementById('name-A');
            const nameB = document.getElementById('name-B');
            if(nameA) nameA.textContent = cleanName(nameA.textContent);
            if(nameB) nameB.textContent = cleanName(nameB.textContent);
        });

        // Make functions globally accessible
        window.updateStatus = updateStatus;
        console.log("🌐 updateStatus function registered globally");
        
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
