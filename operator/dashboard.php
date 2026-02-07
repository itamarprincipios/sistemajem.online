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
        body { background: #0f172a; color: white; margin: 0; }
        .op-header { padding: 1rem 2rem; border-bottom: 1px solid #334155; display: flex; justify-content: space-between; align-items: center; background: #1e293b; position: sticky; top: 0; z-index: 100; }
        
        .dashboard-container { padding: 2rem; max-width: 1400px; margin: 0 auto; }
        
        .category-section { margin-bottom: 3rem; }
        .category-title { font-size: 1.5rem; font-weight: 800; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.75rem; color: #10b981; border-left: 4px solid #10b981; padding-left: 1rem; }
        
        .matches-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(350px, 1fr)); gap: 1.5rem; }
        
        .match-card { background: #1e293b; padding: 1.5rem; border-radius: 16px; border: 1px solid #334155; transition: transform 0.2s, border-color 0.2s; position: relative; }
        .match-card:hover { transform: translateY(-4px); border-color: #10b981; }
        
        /* Female Card Styles */
        .match-card.fem { border-color: rgba(236, 72, 153, 0.3); }
        .match-card.fem:hover { border-color: #ec4899; box-shadow: 0 0 15px rgba(236, 72, 153, 0.15); }
        .match-card.fem .modality-label { color: #f472b6 !important; }
        
        .match-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; font-size: 0.85rem; color: #94a3b8; }
        .status-badge { padding: 2px 8px; border-radius: 4px; font-weight: 600; font-size: 0.75rem; text-transform: uppercase; }
        .status-live { background: rgba(239, 68, 68, 0.2); color: #ef4444; border: 1px solid #ef4444; animation: pulse 2s infinite; }
        .status-scheduled { background: rgba(59, 130, 246, 0.2); color: #3b82f6; border: 1px solid #3b82f6; }
        .status-finished { background: rgba(148, 163, 184, 0.2); color: #94a3b8; border: 1px solid #94a3b8; }

        .match-teams { display: flex; flex-direction: column; gap: 0.75rem; margin-bottom: 1.5rem; }
        .team-row { display: flex; justify-content: space-between; align-items: center; font-size: 1.1rem; font-weight: 600; }
        .vs-divider { text-align: center; margin: 0.5rem 0; color: #475569; font-weight: 800; font-size: 0.8rem; }
        
        .match-footer { display: flex; gap: 0.5rem; }
        
        /* Tabs Styles */
        .tabs-container { 
            display: flex; 
            gap: 0.5rem; 
            margin-bottom: 2rem; 
            overflow-x: auto; 
            padding-bottom: 0.5rem;
            border-bottom: 1px solid #334155;
        }
        .tab-btn {
            background: #1e293b;
            color: #94a3b8;
            border: 1px solid #334155;
            padding: 0.75rem 1.5rem;
            border-radius: 12px 12px 0 0;
            cursor: pointer;
            font-weight: 600;
            white-space: nowrap;
            transition: all 0.2s;
            border-bottom: none;
        }
        .tab-btn.active {
            background: #10b981;
            color: white;
            border-color: #10b981;
        }
        .tab-btn.fem { color: #f472b6; }
        .tab-btn.active.fem { background: #ec4899; color: white; border-color: #ec4899; }
        .tab-content { display: none; }
        .tab-content.active { display: block; }
        
        /* Category Tabs (aligned with results) */
        .category-tabs { display: flex; gap: 0.5rem; margin-bottom: 1.5rem; padding: 0.5rem; background: rgba(0,0,0,0.2); border-radius: 12px; overflow-x: auto; }
        .cat-btn { background: transparent; color: #94a3b8; border: 1px solid transparent; padding: 0.5rem 1rem; border-radius: 8px; cursor: pointer; font-weight: 600; font-size: 0.85rem; transition: all 0.2s; }
        .cat-btn.active { background: rgba(16, 185, 129, 0.2); color: #10b981; border-color: #10b981; }
        .cat-btn.active.fem { background: rgba(236, 72, 153, 0.2); color: #ec4899; border-color: #ec4899; }
        .cat-btn.fem { color: #f472b6; }
        .cat-btn.fem:hover { color: #ec4899; }
        
        /* Phase Navigation (aligned with results) */
        .phase-navigation { display: flex; align-items: center; justify-content: center; gap: 2rem; margin: 2rem 0; padding: 1.5rem; background: rgba(0,0,0,0.3); border-radius: 12px; }
        .phase-navigation button { background: #1e293b; border: 1px solid #334155; color: #94a3b8; padding: 0.75rem 1.5rem; border-radius: 8px; cursor: pointer; font-size: 1.2rem; font-weight: 800; transition: all 0.2s; }
        .phase-navigation button:hover:not(:disabled) { background: #10b981; color: white; border-color: #10b981; }
        .phase-navigation button:disabled { opacity: 0.3; cursor: not-allowed; }
        .phase-title { font-size: 1.8rem; font-weight: 800; color: #10b981; min-width: 300px; text-align: center; }
        .phase-subtitle { font-size: 1.2rem; font-weight: 700; color: #64748b; text-align: center; margin-bottom: 1.5rem; }
            font-weight: 800;
            transition: all 0.2s;
        }
        .phase-nav-btn:hover:not(:disabled) {
            background: #10b981;
            color: white;
            border-color: #10b981;
        }
        .phase-nav-btn:disabled {
            opacity: 0.3;
            cursor: not-allowed;
        }
        .phase-title {
            font-size: 1.8rem;
            font-weight: 800;
            color: #10b981;
            text-align: center;
            min-width: 300px;
        }
        .phase-subtitle {
            font-size: 1.2rem;
            font-weight: 700;
            color: #64748b;
            text-align: center;
            margin-bottom: 1.5rem;
        }

        @keyframes pulse { 0% { opacity: 1; } 50% { opacity: 0.5; } 100% { opacity: 1; } }
        
        /* Modal Styles */
        .modal-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); display: none; align-items: center; justify-content: center; z-index: 1000; }
        .modal-overlay.active { display: flex; }
        .modal { background: #1e293b; border-radius: 16px; padding: 2rem; width: 100%; max-width: 400px; border: 1px solid #334155; }
        .modal-title { margin: 0 0 1.5rem 0; font-size: 1.25rem; }
        .form-group { margin-bottom: 1rem; }
        .form-label { display: block; margin-bottom: 0.5rem; font-size: 0.9rem; color: #94a3b8; }
        .form-input { width: 100%; padding: 0.75rem; border-radius: 8px; border: 1px solid #334155; background: #0f172a; color: white; font-size: 1rem; }
    </style>
</head>
<body>
    <div class="op-header">
        <div style="font-size: 1.2rem; font-weight: 800; letter-spacing: -0.5px; color: #10b981;">JEM OPERADOR</div>
        <div style="display: flex; align-items: center; gap: 1.5rem;">
            <a href="knockout_manual.php" style="background: linear-gradient(135deg, #8b5cf6, #d946ef); color: white; padding: 0.5rem 1rem; border-radius: 8px; text-decoration: none; font-weight: 600; font-size: 0.9rem; display: flex; align-items: center; gap: 0.5rem; transition: transform 0.2s;" onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform='translateY(0)'">
                ✏️ Mata-Mata Manual
            </a>
            <a href="knockout_manager.php" style="background: #334155; color: white; padding: 0.5rem 1rem; border-radius: 8px; text-decoration: none; font-weight: 600; font-size: 0.9rem; display: flex; align-items: center; gap: 0.5rem; transition: background 0.2s;" onmouseover="this.style.background='#475569'" onmouseout="this.style.background='#334155'">
                🏆 Mata-Mata Auto
            </a>
            <span id="matchCount" style="font-size: 0.85rem; color: #64748b; font-weight: 600;"></span>
            <span style="font-size: 0.9rem; color: #94a3b8;"><?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
            <a href="../logout.php" style="color: #ef4444; text-decoration: none; font-size: 0.9rem; font-weight: 600;">Sair</a>
        </div>
    </div>

    <div class="dashboard-container">
        <div id="modalityTabs" class="tabs-container">
            <!-- Modality Tabs generated here -->
        </div>
        <div id="categoryTabs" class="category-tabs" style="display: flex; gap: 0.5rem; margin-bottom: 1.5rem; padding: 0.5rem; background: rgba(0,0,0,0.2); border-radius: 12px; overflow-x: auto;">
            <!-- Category Tabs generated here -->
        </div>
        <div id="matchesContainer">
            <!-- Populated by JS grouped by Category -->
        </div>
    </div>

    <!-- Schedule Modal -->
    <div class="modal-overlay" id="scheduleModal">
        <div class="modal">
            <h3 class="modal-title">Agendar Partida</h3>
            <form id="scheduleForm">
                <input type="hidden" id="matchId">
                <div class="form-group">
                    <label class="form-label">Data e Hora</label>
                    <input type="datetime-local" id="matchTime" class="form-input" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Local (Quadra/Campo)</label>
                    <input type="text" id="matchVenue" class="form-input" placeholder="Ex: Quadra 1">
                </div>
                <div style="display: flex; gap: 0.5rem; margin-top: 1.5rem;">
                    <button type="button" class="schedule-btn" onclick="closeModal()">Cancelar</button>
                    <button type="submit" class="btn-control" style="background: #10b981;">Salvar</button>
                </div>
            </form>
        </div>
    </div>

<script>
let allMatches = []; 
let state = {
    modality: null,
    category: {}, // key: modality_id, value: catKey (cid_gender)
    phase: {}     // key: catKey, value: phase_id
};

// Phase mapping
const PHASE_NAMES = {
    'group_stage': 'FASE DE GRUPOS',
    'round_of_16': 'OITAVAS DE FINAL',
    'quarter_final': 'QUARTAS DE FINAL',
    'semi_final': 'SEMIFINAL',
    'final': 'FINAL',
    'third_place': 'DISPUTA DE 3º LUGAR'
};

const PHASE_ORDER = ['group_stage', 'round_of_16', 'quarter_final', 'semi_final', 'final', 'third_place'];

async function loadMatches() {
    try {
        const res = await fetch(`../api/matches-api.php?action=list&_t=${Date.now()}`); 
        const data = await res.json();
        allMatches = data.data;
        render();
    } catch(e) {
        console.error(e);
    }
}

function switchMod(id) {
    state.modality = id;
    render();
}

function switchCat(key) {
    state.category[state.modality] = key;
    render();
}

function switchPhase(key, direction) {
    const currentPhase = state.phase[key] || 'group_stage';
    const [catId, gender] = key.split('_');
    const categoryMatches = allMatches.filter(m => m.modality_id == state.modality && m.category_id == catId && (m.team_gender || 'M') == gender);
    
    const availablePhases = PHASE_ORDER.filter(phase => 
        phase === 'group_stage' || categoryMatches.some(m => m.phase === phase)
    );
    
    const currentIndex = availablePhases.indexOf(currentPhase);
    if (currentIndex === -1) return; 
    
    const newIndex = currentIndex + direction;
    if (newIndex < 0 || newIndex >= availablePhases.length) return;
    
    state.phase[key] = availablePhases[newIndex];
    render();
}

function render() {
    const modalityTabs = document.getElementById('modalityTabs');
    const categoryTabs = document.getElementById('categoryTabs');
    const container = document.getElementById('matchesContainer');
    const countDisplay = document.getElementById('matchCount');
    
    modalityTabs.innerHTML = '';
    categoryTabs.innerHTML = '';
    container.innerHTML = '';
    
    if (countDisplay) countDisplay.textContent = `(${allMatches.length} jogos)`;
    
    if (allMatches.length === 0) {
        container.innerHTML = '<p style="text-align:center; color: #64748b; padding-top: 5rem;">Nenhum jogo encontrado.</p>';
        return;
    }

    // Grouping by Modality
    const mods = {};
    allMatches.forEach(m => {
        const mid = m.modality_id;
        const gender = m.team_gender || 'M';
        if (!mods[mid]) mods[mid] = { name: m.modality_name, cats: {} };
        
        const catKey = m.category_id + '_' + gender;
        if (!mods[mid].cats[catKey]) {
            mods[mid].cats[catKey] = { 
                id: m.category_id,
                name: m.category_name, 
                gender: gender,
                matches: [] 
            };
        }
        mods[mid].cats[catKey].matches.push(m);
    });

    const modIds = Object.keys(mods).sort();
    if (!state.modality || !modIds.includes(state.modality)) state.modality = modIds[0];

    // Render Modality Tabs
    modalityTabs.innerHTML = modIds.map(mid => 
        `<button class="tab-btn ${state.modality == mid ? 'active' : ''}" onclick="switchMod('${mid}')">${mods[mid].name}</button>`
    ).join('');

    // Render Category Tabs for Active Modality
    const activeMod = mods[state.modality];
    const catKeys = Object.keys(activeMod.cats).sort((a,b) => {
        return activeMod.cats[a].name.localeCompare(activeMod.cats[b].name) || activeMod.cats[a].gender.localeCompare(activeMod.cats[b].gender);
    });

    if (!state.category[state.modality] || !catKeys.includes(state.category[state.modality])) {
        state.category[state.modality] = catKeys[0];
    }

    categoryTabs.innerHTML = catKeys.map(key => {
        const cat = activeMod.cats[key];
        const isFem = cat.gender === 'F';
        const label = isFem ? cat.name + ' Fem' : cat.name;
        const activeClass = state.category[state.modality] == key ? 'active' : '';
        const femClass = isFem ? 'fem' : '';
        return `<button class="cat-btn ${activeClass} ${femClass}" onclick="switchCat('${key}')">🏆 ${label}</button>`;
    }).join('');

    // Render Matches for Selected Category and Phase
    const currentCatKey = state.category[state.modality];
    const cat = activeMod.cats[currentCatKey];
    if (!state.phase[currentCatKey]) state.phase[currentCatKey] = 'group_stage';
    
    const currentPhase = state.phase[currentCatKey];
    const categoryMatches = cat.matches;
    const phaseMatches = categoryMatches.filter(m => m.phase === currentPhase);

    const availablePhases = PHASE_ORDER.filter(phase => 
        phase === 'group_stage' || categoryMatches.some(m => m.phase === phase)
    );
    const phaseIdx = availablePhases.indexOf(currentPhase);
    const canPrev = phaseIdx > 0;
    const canNext = phaseIdx < availablePhases.length - 1;

    let html = `
        <div class="phase-navigation">
            <button class="phase-nav-btn" onclick="switchPhase('${currentCatKey}', -1)" ${!canPrev ? 'disabled' : ''}>←</button>
            <h2 class="phase-title">${PHASE_NAMES[currentPhase] || currentPhase.toUpperCase()}</h2>
            <button class="phase-nav-btn" onclick="switchPhase('${currentCatKey}', 1)" ${!canNext ? 'disabled' : ''}>→</button>
        </div>
        <div class="phase-subtitle">TABELA</div>
        <div class="matches-grid">
    `;

    if (phaseMatches.length === 0) {
        html += '<p style="text-align:center; color: #64748b; padding: 3rem; width: 100%;">Nenhum jogo nesta fase.</p>';
    } else {
        phaseMatches.forEach(m => {
            const isLive = m.status === 'live';
            const isFinished = m.status === 'finished';
            const time = new Date(m.scheduled_time);
            const isFem = m.team_gender === 'F';
            const genderLabel = isFem ? '♀️ Fem' : '♂️ Masc';
            const genderColor = isFem ? '#ec4899' : '#10b981';
            const cleanName = (name) => {
                if (!name) return 'A definir';
                return name
                    .replace(/^(ESCOLA MUNICIPAL |MUNICIPAL |ESCOLA )*(DE )*(ENSINO INFANTIL E FUNDAMENTAL |ENSINO FUNDAMENTAL |ENSINO INFANTIL |ENSINO INFANTIL - FUNDAMENTAL )*/i, '')
                    .trim();
            };

            const teamA = cleanName(m.team_a_name);
            const teamB = cleanName(m.team_b_name);

            html += `
                <div class="match-card ${isFem ? 'fem' : ''}">
                    <div class="match-header">
                        <span>📅 ${time.toLocaleDateString('pt-BR')} às ${time.toLocaleTimeString('pt-BR', {hour: '2-digit', minute:'2-digit'})}</span>
                        <span class="status-badge" style="background:${genderColor}20; color:${genderColor}; border:1px solid ${genderColor}40; padding:2px 8px; border-radius:4px; font-weight:600; font-size:0.75rem;">${genderLabel}</span>
                        <span class="status-badge ${isLive ? 'status-live' : (isFinished ? 'status-finished' : 'status-scheduled')}">
                            ${isLive ? 'Ao Vivo' : (isFinished ? 'Encerrado' : 'Agendado')}
                        </span>
                    </div>
                    <div class="modality-label" style="margin-bottom: 0.5rem; font-size: 0.75rem; color: #10b981; font-weight: 800;">
                        ${m.modality_name}${m.group_name ? ' • Grupo ' + m.group_name : ''}
                    </div>
                    <div class="match-teams">
                        <div class="team-row">
                            <span>${teamA}</span>
                            ${isFinished || isLive ? `<span style="color:white">${m.score_team_a}</span>` : ''}
                        </div>
                        <div class="vs-divider">VS</div>
                        <div class="team-row">
                            <span>${teamB}</span>
                            ${isFinished || isLive ? `<span style="color:white">${m.score_team_b}</span>` : ''}
                        </div>
                    </div>
                    <div style="margin-bottom: 1rem; font-size: 0.8rem; color: #64748b;">
                        📍 ${m.venue || 'Local não definido'}
                    </div>
                    <div class="match-footer">
                        ${!isFinished ? `
                            <button class="schedule-btn" onclick="openModal(${m.id})">🕒 Agendar</button>
                            <a href="match_control.php?id=${m.id}" class="btn-control ${isLive ? 'btn-live' : ''}">
                                ${isLive ? 'RETOMAR' : 'INICIAR'}
                            </a>
                        ` : `<div style="text-align:center; width:100%; color:#64748b; font-weight:700">PARTIDA ENCERRADA</div>`}
                    </div>
                </div>
            `;
        });
    }

    html += '</div>';
    container.innerHTML = html;
}

// Modal Logic
function openModal(id) {
    const match = allMatches.find(m => m.id == id);
    if (!match) return;
    
    document.getElementById('matchId').value = id;
    
    // Format for datetime-local (YYYY-MM-DDTHH:MM)
    const date = new Date(match.scheduled_time);
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    const hours = String(date.getHours()).padStart(2, '0');
    const minutes = String(date.getMinutes()).padStart(2, '0');
    
    document.getElementById('matchTime').value = `${year}-${month}-${day}T${hours}:${minutes}`;
    document.getElementById('matchVenue').value = match.venue || '';
    
    document.getElementById('scheduleModal').classList.add('active');
}

function closeModal() {
    document.getElementById('scheduleModal').classList.remove('active');
}

document.getElementById('scheduleForm').onsubmit = async (e) => {
    e.preventDefault();
    const id = document.getElementById('matchId').value;
    const time = document.getElementById('matchTime').value;
    const venue = document.getElementById('matchVenue').value;
    
    try {
        const res = await fetch('../api/matches-api.php', {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                id: id,
                scheduled_time: time,
                venue: venue
            })
        });
        const result = await res.json();
        if (result.success) {
            closeModal();
            loadMatches();
        } else {
            alert('Erro ao salvar: ' + result.error);
        }
    } catch(err) {
        console.error(err);
    }
};

loadMatches();
</script>
</body>
</html>
