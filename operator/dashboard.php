<?php
require_once '../config/config.php';
require_once '../includes/auth.php';
require_once '../includes/db.php';

requireLogin(); // Should be requireOperator() but we'll use requireLogin temporarily and check role
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] === 'professor') {
    die("Acesso negado.");
}

$pageTitle = 'Painel do Operador';

// Get active event
$activeEvent = queryOne("SELECT id, name FROM competition_events WHERE active_flag = TRUE LIMIT 1");
$activeEventId = $activeEvent['id'] ?? 'null';
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
        .phase-nav-btn { background: #1e293b; border: 1px solid #334155; color: #94a3b8; padding: 0.75rem 1.5rem; border-radius: 8px; cursor: pointer; font-size: 1.2rem; font-weight: 800; transition: all 0.2s; }
        .phase-nav-btn:hover:not(:disabled) { background: #10b981; color: white; border-color: #10b981; }
        .phase-nav-btn:disabled { opacity: 0.3; cursor: not-allowed; }
        .phase-title { font-size: 1.8rem; font-weight: 800; color: #10b981; min-width: 300px; text-align: center; }
        .phase-subtitle { font-size: 1.2rem; font-weight: 700; color: #64748b; text-align: center; margin-bottom: 1.5rem; }

        @keyframes pulse { 0% { opacity: 1; } 50% { opacity: 0.5; } 100% { opacity: 1; } }
        
        /* Inline Edit Styles (Premium Redesign) */
        .inline-input { 
            background: rgba(0,0,0,0.2); 
            border: 1px solid rgba(51, 65, 85, 0.5); 
            color: #f8fafc; 
            border-radius: 8px; 
            padding: 6px 10px; 
            font-size: 0.85rem; 
            width: 100%; 
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            font-family: inherit;
        }
        .inline-input:hover { border-color: #475569; background: rgba(0,0,0,0.3); }
        .inline-input:focus { border-color: #10b981; outline: none; background: #0f172a; box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1); }
        
        .inline-save-btn { 
            background: #10b981; 
            color: white; 
            border: none; 
            border-radius: 6px; 
            padding: 6px 12px; 
            cursor: pointer; 
            font-size: 0.75rem; 
            font-weight: 800; 
            text-transform: uppercase;
            letter-spacing: 0.5px;
            opacity: 0; 
            transform: translateY(10px);
            pointer-events: none; 
            transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
        }
        .inline-save-btn.active { opacity: 1; transform: translateY(0); pointer-events: auto; }
        .save-status { font-size: 0.75rem; padding: 4px 8px; border-radius: 4px; font-weight: 600; }

        .label-pill {
            font-size: 0.6rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #64748b;
            font-weight: 800;
            margin-bottom: 4px;
            display: block;
        }

        /* Modal Súmula */
        .modal-sumula {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.85);
            backdrop-filter: blur(5px);
            z-index: 2000;
            padding: 2rem;
            box-sizing: border-box;
        }
        .modal-sumula.active { display: flex; align-items: center; justify-content: center; }
        .sumula-content {
            background: #1e293b;
            width: 100%;
            max-width: 900px;
            max-height: 90vh;
            border-radius: 20px;
            display: flex;
            flex-direction: column;
            border: 1px solid #334155;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
        }
        .sumula-header { padding: 1.5rem; border-bottom: 1px solid #334155; display: flex; justify-content: space-between; align-items: center; }
        .sumula-body { padding: 1.5rem; overflow-y: auto; flex: 1; font-family: 'Inter', sans-serif; }
        .sumula-text {
            background: #0f172a;
            color: #f8fafc;
            padding: 2rem;
            border-radius: 12px;
            white-space: pre-wrap;
            font-family: monospace;
            font-size: 0.9rem;
            line-height: 1.5;
            border: 1px solid #1e293b;
        }
        .sumula-footer { padding: 1.5rem; border-top: 1px solid #334155; display: flex; gap: 1rem; justify-content: flex-end; }

        /* Bracket Styles */
        .bracket-container {
            display: flex;
            gap: 2rem;
            padding: 2rem;
            overflow-x: auto;
            align-items: center;
            justify-content: center;
            min-height: 400px;
        }
        .bracket-column {
            display: flex;
            flex-direction: column;
            justify-content: space-around;
            gap: 2rem;
        }
        .bracket-match {
            background: #1e293b;
            border: 1px solid #334155;
            border-radius: 12px;
            width: 250px;
            overflow: hidden;
            position: relative;
        }
        .bracket-match::after {
            content: '';
            position: absolute;
            right: -2rem;
            top: 50%;
            width: 2rem;
            height: 2px;
            background: #334155;
            display: none; /* Will show based on logic */
        }
        .bracket-team {
            padding: 0.75rem 1rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.9rem;
            font-weight: 600;
        }
        .bracket-team:first-child { border-bottom: 1px solid #334155; }
        .bracket-team .team-name {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 180px;
        }
        .bracket-team .team-rank {
            font-size: 0.7rem;
            color: #64748b;
            background: rgba(0,0,0,0.3);
            padding: 2px 6px;
            border-radius: 4px;
        }
        .bracket-btn-container {
            margin-top: 2rem;
            text-align: center;
        }
        .preview-badge {
            background: #f59e0b;
            color: #000;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 800;
            margin-bottom: 1rem;
            display: inline-block;
        }

    </style>
</head>
<body>
    <div class="op-header">
        <div style="font-size: 1.2rem; font-weight: 800; letter-spacing: -0.5px; color: #10b981;">JEM OPERADOR <span style="font-size: 0.6rem; color: #64748b; vertical-align: middle; margin-left: 5px;">V2.1</span></div>
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

    <!-- Modal Súmula -->
    <div id="sumulaModal" class="modal-sumula">
        <div class="sumula-content">
            <div class="sumula-header">
                <div>
                    <h3 style="margin: 0; color: #10b981;">📜 Súmula Oficial da Partida</h3>
                    <p style="margin: 0; font-size: 0.8rem; color: #94a3b8;">Texto formatado para documento técnico</p>
                </div>
                <button onclick="closeSumula()" style="background: none; border: none; color: #94a3b8; font-size: 1.5rem; cursor: pointer;">&times;</button>
            </div>
            <div class="sumula-body">
                <div id="sumulaText" class="sumula-text">Gerando...</div>
            </div>
            <div class="sumula-footer">
                <button class="btn btn-secondary" onclick="closeSumula()">Fechar</button>
                <button class="btn btn-primary" onclick="copySumula()">📋 Copiar Texto</button>
            </div>
        </div>
    </div>

    <!-- Schedule Modal Removed -->

<script>
const EVENT_ID = <?php echo $activeEventId; ?>;

// Global Utilities
const cleanName = (name) => {
    if (!name) return 'A definir';
    let cleaned = name;
    
    // Iteratively remove known prefixes until no more can be removed
    const prefixes = [
        /^(ESCOLA MUNICIPAL|MUNICIPAL|ESCOLA|EMEIF|EMEF)\b/gi,
        /^(EDUCAÇÃO INFANTIL|ENSINO FUNDAMENTAL|ENSINO MÉDIO|ENSINO INFANTIL|EDUCAÇÃO INFANTIL E ENSINO FUNDAMENTAL|FUNDAMENTAL)\b/gi,
        /^(PROFESSOR[A]?)\b/gi,
        /^[\s\-–—,]+/gi, // Symbols and spaces
        /^(DE|E|DO|DA)\s+/gi // Prepositions
    ];


    let lastCleaned;
    do {
        lastCleaned = cleaned;
        prefixes.forEach(p => cleaned = cleaned.replace(p, '').trim());
    } while (cleaned !== lastCleaned && cleaned.length > 0);

    return cleaned || 'A definir';
};

let allMatches = []; 
let state = {
    modality: null,
    category: {}, // key: modality_id, value: catKey (cid_gender)
    phase: {}     // key: catKey, value: phase_id
};

const saveState = () => {
    localStorage.setItem('jem_dashboard_state', JSON.stringify(state));
};

const loadState = () => {
    const saved = localStorage.getItem('jem_dashboard_state');
    if (saved) {
        try {
            const parsed = JSON.parse(saved);
            // Deep merge or validate could be done here, but simple assignment for now
            state = { ...state, ...parsed };
        } catch(e) { console.error('Error loading saved state', e); }
    }
};

loadState();

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
    saveState();
    render();
}

function switchCat(key) {
    state.category[state.modality] = key;
    saveState();
    render();
}

function switchPhase(key, direction) {
    const currentPhase = state.phase[key] || 'group_stage';
    const [catId, gender] = key.split('_');
    const categoryMatches = allMatches.filter(m => m.modality_id == state.modality && m.category_id == catId && (m.team_gender || 'M') == gender);
    
    const availablePhases = PHASE_ORDER.filter(phase => 
        phase === 'group_stage' || phase === currentPhase || categoryMatches.some(m => m.phase === phase)
    );
    
    const currentIndex = availablePhases.indexOf(currentPhase);
    const newIndex = currentIndex + direction;

    // Smart Arrow: Allow moving to the next phase if the current one is complete
    const isCurrentComplete = categoryMatches.length > 0 && 
                               categoryMatches.filter(m => m.phase === currentPhase).every(m => m.status === 'finished' || (m.score_team_a !== null && m.score_team_b !== null));

    if (newIndex >= availablePhases.length && isCurrentComplete && currentIndex < PHASE_ORDER.length - 1) {
        // Move to the next logical phase even if matches don't exist yet
        state.phase[key] = PHASE_ORDER[currentIndex + 1];
    } else if (newIndex >= 0 && newIndex < availablePhases.length) {
        state.phase[key] = availablePhases[newIndex];
    } else {
        return;
    }
    
    saveState();
    render();
}

function renderTabs(modalityTabs, categoryTabs, modIds, mods, catKeys) {
    // Render Modality Tabs
    modalityTabs.innerHTML = modIds.map(mid => 
        `<button class="tab-btn ${state.modality == mid ? 'active' : ''}" onclick="switchMod('${mid}')">${mods[mid].name}</button>`
    ).join('');

    // Render Category Tabs
    categoryTabs.innerHTML = catKeys.map(key => {
        const cat = mods[state.modality].cats[key];
        const isFem = cat.gender === 'F';
        const label = isFem ? cat.name + ' Fem' : cat.name;
        const activeClass = state.category[state.modality] == key ? 'active' : '';
        const femClass = isFem ? 'fem' : '';
        return `<button class="cat-btn ${activeClass} ${femClass}" onclick="switchCat('${key}')">🏆 ${label}</button>`;
    }).join('');
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
        const mid = String(m.modality_id);
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
    if (modIds.length === 0) return; // Should not happen if allMatches.length > 0

    if (!state.modality || !modIds.includes(String(state.modality))) {
        state.modality = modIds[0];
    }

    const activeMod = mods[state.modality];
    const catKeys = Object.keys(activeMod.cats).sort((a,b) => {
        return activeMod.cats[a].name.localeCompare(activeMod.cats[b].name) || activeMod.cats[a].gender.localeCompare(activeMod.cats[b].gender);
    });

    if (!state.category[state.modality] || !catKeys.includes(state.category[state.modality])) {
        state.category[state.modality] = catKeys[0];
    }

    // Now we can safely check for PREVIEW mode
    const currCatKey = state.category[state.modality];
    const [currCatId, currGender] = currCatKey.split('_');
    const currPhase = state.phase[currCatKey] || 'group_stage';
    const catMatches = activeMod.cats[currCatKey].matches;

    // Phase Logic
    if (!state.phase[currCatKey]) state.phase[currCatKey] = 'group_stage';
    
    const availablePhases = PHASE_ORDER.filter(phase => 
        phase === 'group_stage' || phase === currPhase || catMatches.some(m => m.phase === phase)
    );
    const currPhaseIdx = availablePhases.indexOf(currPhase);
    const isCurrentComplete = catMatches.length > 0 && 
                               catMatches.filter(m => m.phase === currPhase).every(m => m.status === 'finished' || (m.score_team_a !== null && m.score_team_b !== null));

    const canPrev = currPhaseIdx > 0;
    const canNext = (currPhaseIdx < availablePhases.length - 1) || 
                    (isCurrentComplete && PHASE_ORDER.indexOf(currPhase) < PHASE_ORDER.length - 1);

    const navHtml = `
        <div class="phase-navigation">
            <button class="phase-nav-btn" onclick="switchPhase('${currCatKey}', -1)" ${!canPrev ? 'disabled' : ''}>←</button>
            <h2 class="phase-title">${PHASE_NAMES[currPhase] || currPhase.toUpperCase()}</h2>
            <button class="phase-nav-btn" onclick="switchPhase('${currCatKey}', 1)" ${!canNext ? 'disabled' : ''}>→</button>
        </div>
    `;

    const phaseMatches = catMatches.filter(m => m.phase === currPhase);

    if (phaseMatches.length === 0 && currPhase !== 'group_stage') {
        renderBracketPreview(container, currCatId, currGender, currPhase, navHtml);
        renderTabs(modalityTabs, categoryTabs, modIds, mods, catKeys);
        return;
    }

    // Render Tabs
    renderTabs(modalityTabs, categoryTabs, modIds, mods, catKeys);

    let html = `
        ${navHtml}
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
            const teamA = cleanName(m.team_a_name);
            const teamB = cleanName(m.team_b_name);

            const formatForInput = (date) => {
                const y = date.getFullYear();
                const mo = String(date.getMonth() + 1).padStart(2, '0');
                const d = String(date.getDate()).padStart(2, '0');
                const h = String(date.getHours()).padStart(2, '0');
                const mi = String(date.getMinutes()).padStart(2, '0');
                return `${y}-${mo}-${d}T${h}:${mi}`;
            };

            html += `
                <div class="match-card ${isFem ? 'fem' : ''}" id="card-${m.id}" style="padding: 1.25rem;">
                    <div class="match-header" style="margin-bottom: 1.25rem; align-items: flex-start;">
                        <div style="flex: 1;">
                            <span class="label-pill">MODALIDADE</span>
                            <div class="modality-label" style="font-size: 0.85rem; color: #10b981; font-weight: 800; margin-bottom: 0;">
                                ${m.modality_name}${m.group_name ? ' • Grupo ' + m.group_name : ''}
                            </div>
                        </div>
                        <div style="display: flex; flex-direction: column; align-items: flex-end; gap: 4px;">
                            <span class="status-badge" style="background:${genderColor}20; color:${genderColor}; border:1px solid ${genderColor}40;">${genderLabel}</span>
                            <span class="status-badge ${isLive ? 'status-live' : (isFinished ? 'status-finished' : 'status-scheduled')}">
                                ${isLive ? 'Ao Vivo' : (isFinished ? 'Encerrado' : 'Agendado')}
                            </span>
                        </div>
                    </div>

                    <div style="background: rgba(0,0,0,0.15); border-radius: 12px; padding: 1rem; margin-bottom: 1.25rem;">
                        <span class="label-pill" style="text-align: center;">DATA E HORÁRIO</span>
                        <input type="datetime-local" class="inline-input" value="${formatForInput(time)}" onchange="markDirty(${m.id})" id="time-${m.id}" style="text-align: center; font-weight: 700; margin-bottom: 0;">
                    </div>

                    <div class="match-teams" style="gap: 1.25rem; margin-bottom: 1.25rem;">
                        <div class="team-row">
                            <span style="flex: 1; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">${teamA}</span>
                            <input type="number" min="0" class="inline-input" value="${m.score_team_a || 0}" 
                                   onchange="markDirty(${m.id})" id="score-a-${m.id}" 
                                   style="width: 50px; text-align: center; font-weight: 800; background: #0f172a; ${isFinished ? 'pointer-events: none;' : ''}"
                                   ${isFinished ? 'readonly' : ''}>
                        </div>
                        <div class="vs-divider" style="margin: 0; color: #334155;">VS</div>
                        <div class="team-row">
                            <span style="flex: 1; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">${teamB}</span>
                            <input type="number" min="0" class="inline-input" value="${m.score_team_b || 0}" 
                                   onchange="markDirty(${m.id})" id="score-b-${m.id}" 
                                   style="width: 50px; text-align: center; font-weight: 800; background: #0f172a; ${isFinished ? 'pointer-events: none;' : ''}"
                                   ${isFinished ? 'readonly' : ''}>
                        </div>
                    </div>

                    <div style="margin-bottom: 1.25rem;">
                        <span class="label-pill">LOCAL</span>
                        <input type="text" class="inline-input" value="${m.venue || ''}" placeholder="Ex: Quadra 1" oninput="markDirty(${m.id})" id="venue-${m.id}">
                    </div>

                    <div class="match-footer" style="flex-direction: column; gap: 10px;">
                        ${!isFinished ? `
                            <div style="display: flex; flex-direction: column; gap: 0.5rem; width: 100%;">
                                <div style="display: flex; align-items: center; gap: 0.5rem; width: 100%;">
                                    <button class="inline-save-btn" id="save-${m.id}" onclick="saveMatch(${m.id})" style="flex: 1;">💾 SALVAR</button>
                                    <button onclick="finishMatchDirectly(${m.id})" class="inline-save-btn" style="flex: 1; background: #10b981; border-color: #059669;">🏁 ENCERRAR</button>
                                </div>
                                <a href="match_control.php?id=${m.id}" class="btn-control ${isLive ? 'btn-live' : ''}" style="width: 100%; padding: 0.75rem;">
                                    ${isLive ? '⏱️ GERENCIAR PLACAR' : '🎮 INICIAR PARTIDA'}
                                </a>
                                <span id="status-${m.id}" class="save-status" style="text-align: center;"></span>
                            </div>
                        ` : `
                            <div style="display: flex; flex-direction: column; gap: 10px;">
                                <div style="text-align:center; width:100%; color:#64748b; font-weight:800; padding: 0.75rem; background: rgba(0,0,0,0.1); border-radius: 8px; font-size: 0.8rem;">PARTIDA ENCERRADA</div>
                                <a href="sumula.php?match_id=${m.id}" target="_blank" class="btn btn-primary" style="width: 100%; background: #334155; border: 1px solid #475569; padding: 0.75rem; text-decoration: none; display: block; text-align: center; color: white; border-radius: 8px;">📜 VER SÚMULA</a>
                            </div>
                        `}
                    </div>
                </div>
            `;
        });
    }

    html += '</div>';
    container.innerHTML = html;
}

// Inline Editing Logic
function markDirty(id) {
    const btn = document.getElementById(`save-${id}`);
    if (btn) btn.classList.add('active');
}

async function finishMatchDirectly(id) {
    const match = allMatches.find(m => m.id == id);
    if (!match) return;

    const scoreA = parseInt(document.getElementById(`score-a-${id}`).value);
    const scoreB = parseInt(document.getElementById(`score-b-${id}`).value);

    let winnerId = null;
    if (scoreA > scoreB) winnerId = match.team_a_id;
    else if (scoreB > scoreA) winnerId = match.team_b_id;
    else {
        // Tie handling
        if (confirm('A partida terminou empatada. Houve disputa de pênaltis? Se sim, clique OK para ir à tela de gerenciamento de súmula e registrar o vencedor.')) {
            window.location.href = `match_control.php?id=${id}`;
            return;
        }
        return; 
    }

    if (!confirm(`Deseja encerrar a partida com o placar ${scoreA} x ${scoreB}?`)) return;

    try {
        const res = await fetch('../api/matches-api.php', {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                id: id,
                status: 'finished',
                score_team_a: scoreA,
                score_team_b: scoreB,
                winner_team_id: winnerId
            })
        });
        const result = await res.json();
        if (result.success) {
            alert('Partida encerrada com sucesso!');
            loadMatches();
        } else {
            alert('Erro: ' + result.error);
        }
    } catch(err) {
        console.error(err);
        alert('Erro ao encerrar partida.');
    }
}

async function saveMatch(id) {
    const time = document.getElementById(`time-${id}`).value;
    const venue = document.getElementById(`venue-${id}`).value;
    const statusSpan = document.getElementById(`status-${id}`);
    const btn = document.getElementById(`save-${id}`);

    if (statusSpan) {
        statusSpan.textContent = '⏳ Salvando...';
        statusSpan.style.color = '#94a3b8';
    }

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
            if (statusSpan) {
                statusSpan.textContent = '✅ Salvo!';
                statusSpan.style.color = '#10b981';
                setTimeout(() => { statusSpan.textContent = ''; }, 2000);
            }
            if (btn) btn.classList.remove('active');
            
            // Update local data without full reload to prevent UI jump
            const m = allMatches.find(match => match.id == id);
            if (m) {
                m.scheduled_time = time;
                m.venue = venue;
            }
        } else {
            alert('Erro ao salvar: ' + result.error);
        }
    } catch(err) {
        console.error(err);
        if (statusSpan) {
            statusSpan.textContent = '❌ Erro';
            statusSpan.style.color = '#ef4444';
        }
    }
}

async function generateSumula(id) {
    const modal = document.getElementById('sumulaModal');
    const textDiv = document.getElementById('sumulaText');
    modal.classList.add('active');
    textDiv.textContent = 'Gerando súmula detalhada...';

    try {
        const res = await fetch(`../api/match-events-api.php?action=get_match_sumula&match_id=${id}`);
        const result = await res.json();
        
        console.log('API Response:', result); // Debug
        
        if (!result.success) throw new Error(result.error || 'Erro desconhecido na API');
        
        const data = result.data;
        const m = data.match;
        const e = data.events;
        const placeholder = "----XXXX----";

        // Helper to format date
        const dateObj = new Date(m.scheduled_time);
        const day = String(dateObj.getDate()).padStart(2, '0');
        const month = String(dateObj.getMonth() + 1).padStart(2, '0');
        const year = dateObj.getFullYear();
        const hour = String(dateObj.getHours()).padStart(2, '0');
        const min = String(dateObj.getMinutes()).padStart(2, '0');

        // Lists
        const goals = e.filter(ev => ev.event_type === 'GOAL').map(ev => 
            `- ${ev.event_time}' | ${ev.team_id == m.team_a_id ? 'EQUIPE A' : 'EQUIPE B'} | Nº ${ev.jersey_number || '??'} - ${ev.athlete_name || 'ATLETA NÃO LISTADO'}`
        ).join('\n') || placeholder;

        const subs = e.filter(ev => ev.event_type === 'SUBSTITUTION').map(ev => 
            `- ${ev.event_time}' | ${ev.team_id == m.team_a_id ? 'EQUIPE A' : 'EQUIPE B'} | SAIU: ${ev.athlete_name} | ENTROU: ${ev.athlete_in_name}`
        ).join('\n') || placeholder;

        const cards = e.filter(ev => ['YELLOW_CARD', 'RED_CARD'].includes(ev.event_type)).map(ev => 
            `- ${ev.event_time}' | ${ev.team_id == m.team_a_id ? 'EQUIPE A' : 'EQUIPE B'} | Nº ${ev.jersey_number || '??'} (${ev.athlete_name}) | ${ev.event_type === 'YELLOW_CARD' ? 'CA' : 'CV'}`
        ).join('\n') || placeholder;

        const formatAthletes = (list, captainId) => {
            return list.map(a => `${String(a.jersey_number || '??').padStart(2, '0')} - ${a.name_snapshot}${a.id == captainId ? ' (C)' : ''}`).join('\n') || placeholder;
        };

        const markdown = `
# SÚMULA OFICIAL DE PARTIDA

### DADOS DA PARTIDA
- COMPETIÇÃO: ${m.modality_name.toUpperCase()} | FASE: ${PHASE_NAMES[m.phase] || m.phase.toUpperCase()}
- DATA: ${day}/${month}/${year} ÀS ${hour}:${min} | LOCAL: ${m.venue || placeholder}
- ÁRBITRO PRINCIPAL: ${m.referee_primary || placeholder}
- EQUIPE DE ARBITRAGEM: ${[m.referee_assistant, m.referee_fourth].filter(x => x).join(' / ') || placeholder}

---

### EQUIPE A: ${m.team_a_name.toUpperCase()}
**ATLETAS (Nº E NOME):**
${formatAthletes(data.athletes_a, m.team_a_captain_id)}

**COMISSÃO TÉCNICA:**
- TÉCNICO: ${m.team_a_coach || placeholder}
- AUXILIAR: ${m.team_a_assistant || placeholder}

---

### EQUIPE B: ${m.team_b_name.toUpperCase()}
**ATLETAS (Nº E NOME):**
${formatAthletes(data.athletes_b, m.team_b_captain_id)}

**COMISSÃO TÉCNICA:**
- TÉCNICO: ${m.team_b_coach || placeholder}
- AUXILIAR: ${m.team_b_assistant || placeholder}

---

### EVENTOS CRONOLÓGICOS (GOLS)
${goals}

---

### SUBSTITUIÇÕES
${subs}

---

### RELATO DISCIPLINAR (CARTÕES)
${cards}

---

### RELATÓRIO TÉCNICO E OBSERVAÇÕES
- **PLACAR FINAL:** ${m.team_a_name}: ${m.score_team_a} X ${m.team_b_name}: ${m.score_team_b} 
- **OBSERVAÇÕES DO ÁRBITRO:**
${m.observations || 'NADA HOUVE'}

---
*Gerado automaticamente pelo Sistema JEM em ${new Date().toLocaleString('pt-BR')}*
        `.trim();

        textDiv.textContent = markdown;
    } catch (err) {
        console.error('Erro completo:', err);
        textDiv.textContent = `Erro ao carregar dados da súmula.\n\nDetalhes: ${err.message}\n\nVerifique o console do navegador (F12) para mais informações.`;
    }
}

function closeSumula() {
    document.getElementById('sumulaModal').classList.remove('active');
}

function copySumula() {
    const text = document.getElementById('sumulaText').textContent;
    navigator.clipboard.writeText(text).then(() => {
        alert('Súmula copiada para a área de transferência!');
    });
}

async function renderBracketPreview(container, catId, gender, phase) {
    container.innerHTML = `
        <div style="text-align:center; padding: 3rem;">
            <div class="preview-badge">✨ PRÉ-VISUALIZAÇÃO</div>
            <h2 style="color: #10b981; margin-bottom: 0.5rem;">${PHASE_NAMES[phase]}</h2>
            <p style="color: #94a3b8; font-size: 0.9rem; margin-bottom: 2rem;">Os jogos ainda não foram gerados. Confira o chaveamento previsto:</p>
            <div id="bracketLoading" class="bracket-container">Calculando cruzamentos...</div>
            <div id="bracketActions" style="display:none;" class="bracket-btn-container">
                <button onclick="generateKnockout('${phase}')" class="btn btn-primary" style="padding: 1rem 2rem; font-size: 1rem; border-radius: 12px; box-shadow: 0 10px 15px -3px rgba(16, 185, 129, 0.3);">
                    ⚡ Confirmar e Gerar Jogos
                </button>
            </div>
        </div>
    `;

    try {
        const bracketDiv = document.getElementById('bracketLoading');
        let matches = [];

        if (phase === 'round_of_16') {
            // Group Stage -> Round of 16 (Existing logic)
            const res = await fetch(`../api/standings-api.php?action=group_standings&event_id=${EVENT_ID}&modality_id=${state.modality}&category_id=${catId}&gender=${gender}`);
            const result = await res.json();
            const standings = result.data;
            const groups = Object.keys(standings).sort();
            for(let i=0; i < groups.length; i++) {
                const groupA = groups[i];
                const groupB = groups[(i + 1) % groups.length];
                const firstA = (standings[groupA] || []).find(t => t.position === 1);
                const secondB = (standings[groupB] || []).find(t => t.position === 2);
                if (firstA && secondB) {
                    matches.push({ 
                        a: { name: firstA.team_name, rank: `1º ${groupA}` }, 
                        b: { name: secondB.team_name, rank: `2º ${groupB}` }, 
                        label: `Jogo ${matches.length + 1}` 
                    });
                }
            }
        } else {
            // Subsequent phases: Find winners of the previous phase
            const prevPhaseMap = {
                'quarter_final': 'round_of_16',
                'semi_final': 'quarter_final',
                'final': 'semi_final',
                'third_place': 'semi_final'
            };
            const prevPhase = prevPhaseMap[phase];
            const prevMatches = allMatches.filter(m => 
                m.modality_id == state.modality && m.category_id == catId && 
                (m.team_gender || 'M') == gender && m.phase === prevPhase
            ).sort((a,b) => new Date(a.scheduled_time) - new Date(b.scheduled_time));

            if (prevMatches.length === 0) {
                bracketDiv.innerHTML = `<p style="color: #ef4444;">Fase anterior (${PHASE_NAMES[prevPhase]}) não encontrada.</p>`;
                return;
            }

            for(let i=0; i < prevMatches.length; i += 2) {
                const m1 = prevMatches[i];
                const m2 = prevMatches[i+1];
                if (!m1 || !m2) break;

                const getWinner = (m) => {
                    if (m.status !== 'finished') return { name: `Vencedor Jogo ${m.id}`, rank: '...' };
                    const isA = (m.score_team_a || 0) > (m.score_team_b || 0); // Simplified, ignores penalties for now
                    return { 
                        name: isA ? m.team_a_name : m.team_b_name, 
                        rank: isA ? `Venc. Jogo ${m.id}` : `Venc. Jogo ${m.id}` 
                    };
                };

                const getLoser = (m) => {
                    if (m.status !== 'finished') return { name: `Perdedor Jogo ${m.id}`, rank: '...' };
                    const isA = (m.score_team_a || 0) > (m.score_team_b || 0);
                    return { 
                        name: isA ? m.team_b_name : m.team_a_name, 
                        rank: isA ? `Perd. Jogo ${m.id}` : `Perd. Jogo ${m.id}` 
                    };
                };

                if (phase === 'third_place') {
                    matches.push({ a: getLoser(m1), b: getLoser(m2), label: 'Disputa de 3º' });
                } else {
                    matches.push({ a: getWinner(m1), b: getWinner(m2), label: `Jogo ${matches.length + 1}` });
                }
            }
        }

        if (matches.length === 0) {
            bracketDiv.innerHTML = '<p style="color: #ef4444;">Não há dados suficientes para prever esta fase.</p>';
            return;
        }

        bracketDiv.innerHTML = '';
        const column = document.createElement('div');
        column.className = 'bracket-column';
        matches.forEach(m => {
            const matchDiv = document.createElement('div');
            matchDiv.className = 'bracket-match';
            matchDiv.innerHTML = `
                <div style="padding: 4px 10px; font-size: 0.6rem; color: #64748b; background: rgba(0,0,0,0.2); border-bottom: 1px solid #334155;">${m.label}</div>
                <div class="bracket-team">
                    <span class="team-name">${cleanName(m.a.name)}</span>
                    <span class="team-rank">${m.a.rank}</span>
                </div>
                <div class="bracket-team">
                    <span class="team-name">${cleanName(m.b.name)}</span>
                    <span class="team-rank">${m.b.rank}</span>
                </div>
            `;
            column.appendChild(matchDiv);
        });
        bracketDiv.appendChild(column);
        document.getElementById('bracketActions').style.display = 'block';

    } catch(e) {
        console.error(e);
        document.getElementById('bracketLoading').innerHTML = '<p style="color: #ef4444;">Erro ao calcular o chaveamento.</p>';
    }
}

async function generateKnockout(phase) {
    if (!confirm(`Deseja gerar os jogos para ${PHASE_NAMES[phase]}?`)) return;

    const [catId, gender] = (state.category[state.modality] || '0_M').split('_');

    try {
        const res = await fetch('../api/generate-knockout-api.php', {
            method: 'POST',
            body: JSON.stringify({
                event_id: EVENT_ID,
                modality_id: state.modality,
                category_id: catId,
                gender: gender,
                phase: phase
            })
        });

        const result = await res.json();
        if (result.success) {
            alert(result.message);
            // Reload EVERYTHING
            loadMatches(); 
        } else {
            alert('Erro: ' + result.error);
        }
    } catch(e) {
        console.error(e);
        alert('Erro ao processar solicitação.');
    }
}

loadMatches();
</script>
</body>
</html>
