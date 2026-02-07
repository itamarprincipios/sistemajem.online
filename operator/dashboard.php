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
        :root { --theme-color: #10b981; --theme-color-rgb: 16, 185, 129; }
        body { background: #0f172a; color: white; margin: 0; }
        .op-header { padding: 1rem 2rem; border-bottom: 1px solid #334155; display: flex; justify-content: space-between; align-items: center; background: #1e293b; position: sticky; top: 0; z-index: 100; }
        
        .dashboard-container { padding: 2rem; max-width: 1400px; margin: 0 auto; }
        
        .category-section { margin-bottom: 3rem; }
        .category-title { font-size: 1.5rem; font-weight: 800; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.75rem; color: var(--theme-color); border-left: 4px solid var(--theme-color); padding-left: 1rem; }
        
        .matches-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(350px, 1fr)); gap: 1.5rem; }
        
        .match-card { background: #1e293b; padding: 1.5rem; border-radius: 16px; border: 1px solid #334155; transition: transform 0.2s, border-color 0.2s; position: relative; }
        .match-card:hover { transform: translateY(-4px); border-color: var(--theme-color); }
        
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
            background: var(--theme-color);
            color: white;
            border-color: var(--theme-color);
        }
        .tab-btn.fem { color: #f472b6; }
        .tab-btn.active.fem { background: #ec4899; color: white; border-color: #ec4899; }
        .tab-content { display: none; }
        .tab-content.active { display: block; }
        
        /* Category Tabs (aligned with results) */
        .category-tabs { display: flex; gap: 0.5rem; margin-bottom: 1.5rem; padding: 0.5rem; background: rgba(0,0,0,0.2); border-radius: 12px; overflow-x: auto; }
        .cat-btn { background: transparent; color: #94a3b8; border: 1px solid transparent; padding: 0.5rem 1rem; border-radius: 8px; cursor: pointer; font-weight: 600; font-size: 0.85rem; transition: all 0.2s; }
        .cat-btn.active { background: rgba(var(--theme-color-rgb), 0.2); color: var(--theme-color); border-color: var(--theme-color); }
        .cat-btn.active.fem { background: rgba(236, 72, 153, 0.2); color: #ec4899; border-color: #ec4899; }
        .cat-btn.fem { color: #f472b6; }
        .cat-btn.fem:hover { color: #ec4899; }
        
        /* Phase Navigation (aligned with results) */
        .phase-navigation { display: flex; align-items: center; justify-content: center; gap: 2rem; margin: 2rem 0; padding: 1.5rem; background: rgba(0,0,0,0.3); border-radius: 12px; }
        .phase-nav-btn { background: #1e293b; border: 1px solid #334155; color: #94a3b8; padding: 0.75rem 1.5rem; border-radius: 8px; cursor: pointer; font-size: 1.2rem; font-weight: 800; transition: all 0.2s; }
        .phase-nav-btn:hover:not(:disabled) { background: var(--theme-color); color: white; border-color: var(--theme-color); }
        .phase-nav-btn:disabled { opacity: 0.3; cursor: not-allowed; }
        .phase-title { font-size: 1.8rem; font-weight: 800; color: var(--theme-color); min-width: 300px; text-align: center; }
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
        .inline-input:focus { border-color: var(--theme-color); outline: none; background: #0f172a; box-shadow: 0 0 0 3px rgba(var(--theme-color-rgb), 0.1); }
        
        /* Hide number input spinners */
        input::-webkit-outer-spin-button,
        input::-webkit-inner-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }
        input[type=number] {
            -moz-appearance: textfield;
        }
        
        .inline-save-btn { 
            background: var(--theme-color); 
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


    </style>
</head>
<body>
    <div class="op-header">
        <div style="font-size: 1.2rem; font-weight: 800; letter-spacing: -0.5px; color: var(--theme-color);">JEM OPERADOR <span style="font-size: 0.6rem; color: #64748b; vertical-align: middle; margin-left: 5px;">V2.1</span></div>
        <div style="display: flex; align-items: center; gap: 1.5rem;">
            <span id="matchCount" style="font-size: 0.85rem; color: #64748b; font-weight: 600;"></span>
            <span style="font-size: 0.9rem; color: #94a3b8;"><?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
            <a href="../logout.php" class="btn btn-sm btn-danger">Sair</a>
            <button onclick="toggleDebug()" style="background:none; border:none; color:#334155; cursor:pointer; font-size:0.5rem;" title="Debug">.</button>
        </div>
    </div>

    <div id="debugInfo" style="display:none; background:#000; color:#0f0; padding:1rem; font-family:monospace; font-size:0.75rem; border:1px solid #0f0; margin:1rem;">
        <pre id="debugOutput"></pre>
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
                    <h3 style="margin: 0; color: var(--theme-color);">📜 Súmula Oficial da Partida</h3>
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
    'third_place': 'DISPUTA DE 3º LUGAR',
    'podium': 'PÓDIO'
};

const PHASE_ORDER = ['group_stage', 'round_of_16', 'quarter_final', 'semi_final', 'final', 'third_place', 'podium'];

async function loadMatches() {
    try {
        const res = await fetch(`../api/matches-api.php?action=list&_t=${Date.now()}`); 
        const result = await res.json();
        
        const debugOut = document.getElementById('debugOutput');
        if (debugOut) debugOut.textContent = JSON.stringify(result, null, 2);

        if (result.success) {
            allMatches = result.data || [];
            render();
        } else {
            document.getElementById('matchesContainer').innerHTML = `<p style="text-align:center; color:#ef4444; padding-top:5rem;">Erro da API: ${result.error || 'Erro desconhecido'}</p>`;
        }
    } catch(e) {
        console.error(e);
        document.getElementById('matchesContainer').innerHTML = `<p style="text-align:center; color:#ef4444; padding-top:5rem;">Erro de conexão ou no sistema.</p>`;
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
    const globalIdx = PHASE_ORDER.indexOf(currentPhase);

    // Smart Arrow: Allow moving to the next phase if the current one is complete
    const isCurrentComplete = categoryMatches.length > 0 && 
                               categoryMatches.filter(m => m.phase === currentPhase).every(m => m.status === 'finished' || (m.score_team_a !== null && m.score_team_b !== null));

    if (newIndex >= availablePhases.length && isCurrentComplete && globalIdx < PHASE_ORDER.length - 1) {
        // Move to the next logical phase even if matches don't exist yet
        state.phase[key] = PHASE_ORDER[globalIdx + 1];
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
    modalityTabs.innerHTML = modIds.map(mid => {
        const mod = mods[mid];
        const isSociety = mod.name.toLowerCase().includes('society');
        return `<button class="tab-btn ${state.modality == mid ? 'active' : ''} ${isSociety ? 'society' : ''}" onclick="switchMod('${mid}')">${mod.name}</button>`;
    }).join('');

    // Render Category Tabs
    categoryTabs.innerHTML = catKeys.map(key => {
        const cat = mods[state.modality].cats[key];
        const isFem = cat.gender === 'F';
        const isSociety = mods[state.modality].name.toLowerCase().includes('society');
        const label = isFem ? cat.name + ' Fem' : cat.name;
        const activeClass = state.category[state.modality] == key ? 'active' : '';
        const femClass = isFem ? 'fem' : '';
        const societyClass = isSociety ? 'society' : '';
        return `<button class="cat-btn ${activeClass} ${femClass} ${societyClass}" onclick="switchCat('${key}')">🏆 ${label}</button>`;
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
    if (!activeMod) {
        container.innerHTML = `<p style="text-align:center; color: #64748b; padding-top: 5rem;">Modalidade (${state.modality}) não encontrada. Clique em uma aba acima.</p>`;
        return;
    }
    
    // Set dynamic theme color
    const modName = activeMod.name || '';
    const isSocietyMod = modName.toLowerCase().includes('society');
    const themeColor = isSocietyMod ? '#3b82f6' : '#10b981';
    const themeColorRgb = isSocietyMod ? '59, 130, 246' : '16, 185, 129';
    document.documentElement.style.setProperty('--theme-color', themeColor);
    document.documentElement.style.setProperty('--theme-color-rgb', themeColorRgb);

    const catKeys = Object.keys(activeMod.cats).sort((a,b) => {
        try {
            const nameA = activeMod.cats[a].name || '';
            const nameB = activeMod.cats[b].name || '';
            const genderA = activeMod.cats[a].gender || '';
            const genderB = activeMod.cats[b].gender || '';
            return nameA.localeCompare(nameB) || genderA.localeCompare(genderB);
        } catch(e) { return 0; }
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
            <button class="phase-nav-btn" onclick="switchPhase('${currCatKey}', -1)" ${!canPrev ? 'disabled' : ''} style="${!canPrev ? '' : `&:hover{background:${themeColor};border-color:${themeColor};}`}">←</button>
            <h2 class="phase-title" style="color: ${themeColor}">${PHASE_NAMES[currPhase] || currPhase.toUpperCase()}</h2>
            <button class="phase-nav-btn" onclick="switchPhase('${currCatKey}', 1)" ${!canNext ? 'disabled' : ''}>→</button>
        </div>
    `;

    if (currPhase === 'podium') {
        renderPodium(container, currCatId, currGender, navHtml, catMatches);
        renderTabs(modalityTabs, categoryTabs, modIds, mods, catKeys);
        return;
    }

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

            const isSociety = m.modality_name.toLowerCase().includes('society');
            const modColor = isSociety ? '#3b82f6' : '#10b981';

            html += `
                <div class="match-card ${isFem ? 'fem' : ''} ${isSociety ? 'society' : ''}" id="card-${m.id}" style="padding: 1.25rem;">
                    <div class="match-header" style="margin-bottom: 1.25rem; align-items: flex-start;">
                        <div style="flex: 1;">
                            <span class="label-pill">MODALIDADE</span>
                            <div class="modality-label" style="font-size: 0.85rem; color: ${modColor}; font-weight: 800; margin-bottom: 0;">
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
                                    <button onclick="finishMatchDirectly(${m.id})" class="inline-save-btn" style="flex: 1; opacity: 1; transform: none; pointer-events: auto; background: var(--theme-color); border: 1px solid rgba(0,0,0,0.2);">🏁 ENCERRAR</button>
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
                statusSpan.style.color = 'var(--theme-color)';
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

async function saveBestPlayer(catId, gender) {
    const val = document.getElementById('bestPlayerInput').value;
    const btn = document.getElementById('saveBestBtn');
    const status = document.getElementById('saveBestStatus');
    
    // Parse name and school (assuming format "Name - School")
    const parts = val.split('-').map(p => p.trim());
    const winnerName = parts[0] || '';
    const schoolName = parts[1] || '';

    try {
        const res = await fetch('../api/awards-api.php', {
            method: 'POST',
            body: JSON.stringify({
                event_id: EVENT_ID,
                modality_id: state.modality,
                category_id: catId,
                gender: gender,
                award_type: 'BEST_PLAYER',
                winner_name: winnerName,
                school_name: schoolName
            })
        });
        
        btn.innerHTML = '✅ SALVO';
        btn.style.background = '#059669';
        status.innerHTML = 'Eleito salvo no banco com sucesso!';
        status.style.color = 'var(--theme-color)';
    } catch (e) {
        status.innerHTML = 'Erro ao salvar no banco.';
        status.style.color = '#ef4444';
    }
    
    setTimeout(() => {
        btn.innerHTML = 'SALVAR';
        btn.style.background = 'var(--theme-color)';
        status.innerHTML = 'Nome e escola do eleito.';
        status.style.color = '#64748b';
    }, 2000);
}

async function saveBestGk(catId, gender) {
    const val = document.getElementById('bestGkInput').value;
    const btn = document.getElementById('saveGkBtn');
    const status = document.getElementById('saveGkStatus');
    
    const parts = val.split('-').map(p => p.trim());
    const winnerName = parts[0] || '';
    const schoolName = parts[1] || '';

    try {
        const res = await fetch('../api/awards-api.php', {
            method: 'POST',
            body: JSON.stringify({
                event_id: EVENT_ID,
                modality_id: state.modality,
                category_id: catId,
                gender: gender,
                award_type: 'BEST_GK',
                winner_name: winnerName,
                school_name: schoolName
            })
        });
        
        btn.innerHTML = '✅ SALVO';
        btn.style.background = '#059669';
        status.innerHTML = 'Goleiro salvo no banco com sucesso!';
        status.style.color = 'var(--theme-color)';
    } catch (e) {
        status.innerHTML = 'Erro ao salvar no banco.';
        status.style.color = '#ef4444';
    }
    
    setTimeout(() => {
        btn.innerHTML = 'SALVAR';
        btn.style.background = '#3b82f6';
        status.innerHTML = 'Nome e escola do goleiro.';
        status.style.color = '#64748b';
    }, 2000);
}



async function renderPodium(container, catId, gender, navHtml, catMatches) {
    container.innerHTML = `
        ${navHtml}
        <div style="text-align:center; padding: 3rem;">
            <div class="preview-badge" style="background: linear-gradient(135deg, #f59e0b, #d97706); color: white;">🏆 PREMIAÇÃO</div>
            <h2 style="color: #f59e0b; margin-bottom: 2rem; font-size: 2.5rem; text-shadow: 0 0 20px rgba(245, 158, 11, 0.4);">PÓDIO FINAL</h2>
            <div id="podiumContent" class="bracket-container" style="justify-content: center; align-items: flex-end; gap: 2rem; min-height: 400px;">
                Calculando medalhistas...
            </div>
        </div>
    `;

    try {
        const finalMatch = catMatches.find(m => m.phase === 'final');
        const thirdMatch = catMatches.find(m => m.phase === 'third_place');

        const getWinner = (m) => {
            if (!m) return null;
            const scoreA = parseInt(m.score_team_a);
            const scoreB = parseInt(m.score_team_b);
            if (isNaN(scoreA) || isNaN(scoreB)) return null;
            return scoreA > scoreB ? { name: m.team_a_name, id: m.team_a_id } : { name: m.team_b_name, id: m.team_b_id };
        };

        const getLoser = (m) => {
            if (!m) return null;
            const scoreA = parseInt(m.score_team_a);
            const scoreB = parseInt(m.score_team_b);
            if (isNaN(scoreA) || isNaN(scoreB)) return null;
            return scoreA > scoreB ? { name: m.team_b_name, id: m.team_b_id } : { name: m.team_a_name, id: m.team_a_id };
        };

        const first = getWinner(finalMatch);
        const second = getLoser(finalMatch);
        const third = getWinner(thirdMatch);

        // Fetch saved awards from DB
        let dbAwards = [];
        try {
            const res = await fetch(`../api/awards-api.php?event_id=${EVENT_ID}&modality_id=${state.modality}&category_id=${catId}&gender=${gender}&_t=${Date.now()}`);
            const result = await res.json();
            dbAwards = result.data || [];
        } catch(e) { console.error('Error fetching awards', e); }

        const findAward = (type) => {
            const a = dbAwards.find(x => x.award_type === type);
            if (!a) return '';
            return a.school_name ? `${a.winner_name} - ${a.school_name}` : a.winner_name;
        };

        const podiumDiv = document.getElementById('podiumContent');
        podiumDiv.innerHTML = '';
        podiumDiv.style.cssText = 'display: flex; flex-wrap: wrap; gap: 3rem; justify-content: center; align-items: stretch;';

        // --- LEFT: PODIUM ---
        const podiumWrapper = document.createElement('div');
        podiumWrapper.style.cssText = 'display: flex; align-items: flex-end; gap: 1.5rem; padding-bottom: 2rem;';
        
        const createPodiumCard = (team, pos, color, height, label, icon) => {
            const card = document.createElement('div');
            card.style.cssText = `display: flex; flex-direction: column; align-items: center; gap: 1rem; width: 200px;`;
            card.innerHTML = `
                <div style="font-size: 3rem; filter: drop-shadow(0 0 10px ${color}60);">${icon}</div>
                <div style="
                    background: #1e293b; border: 2px solid ${color}; 
                    border-radius: 16px 16px 0 0; width: 100%; height: ${height}px;
                    display: flex; flex-direction: column; justify-content: center; align-items: center;
                    box-shadow: 0 10px 30px -10px rgba(0,0,0,0.5);
                    position: relative; padding: 1rem; text-align: center;
                ">
                    <div style="color: ${color}; font-weight: 900; font-size: 1.2rem; margin-bottom: 0.5rem;">${label}</div>
                    <div style="color: white; font-weight: 700; font-size: 1rem; line-height: 1.2;">
                        ${team ? cleanName(team.name).toUpperCase() : '---'}
                    </div>
                </div>
            `;
            return card;
        };

        podiumWrapper.appendChild(createPodiumCard(second, 2, '#94a3b8', 160, '2º LUGAR', '🥈'));
        podiumWrapper.appendChild(createPodiumCard(first, 1, '#f59e0b', 220, 'CAMPEÃO', '🥇'));
        podiumWrapper.appendChild(createPodiumCard(third, 3, '#b45309', 120, '3º LUGAR', '🥉'));
        
        podiumDiv.appendChild(podiumWrapper);

        // --- RIGHT: INDIVIDUAL AWARDS ---
        const awardsWrapper = document.createElement('div');
        awardsWrapper.style.cssText = 'flex: 1; min-width: 350px; display: flex; flex-direction: column; gap: 1.5rem; text-align: left; background: rgba(0,0,0,0.2); padding: 2rem; border-radius: 20px; border: 1px solid #334155;';
        
        // 1. Calculate Top Scorer (AUTOMATIC)
        const scorers = {};
        catMatches.forEach(m => {
            (m.events || []).forEach(ev => {
                if (ev.event_type === 'GOAL') {
                    if (!scorers[ev.athlete_id]) scorers[ev.athlete_id] = { name: ev.athlete_name, goals: 0 };
                    scorers[ev.athlete_id].goals++;
                }
            });
        });
        const topScorer = Object.values(scorers).sort((a,b) => b.goals - a.goals)[0];

        // 3. Best Player / Gk (FROM DB)
        const savedPlayer = findAward('BEST_PLAYER');
        const savedGk = findAward('BEST_GK');

        awardsWrapper.innerHTML = `
            <h3 style="color: var(--theme-color); margin: 0 0 1rem 0; display: flex; align-items: center; gap: 10px;">✨ DESTAQUES INDIVIDUAIS</h3>
            
            <div style="background: rgba(255,255,255,0.03); padding: 1rem; border-radius: 12px; margin-bottom: 0.5rem;">
                <div style="color: #94a3b8; font-size: 0.75rem; font-weight: 800; text-transform: uppercase;">⚽ ARTILHEIRO (AUTOMÁTICO)</div>
                <div style="color: white; font-size: 1.2rem; font-weight: 700;">${topScorer ? topScorer.name : '---'}</div>
                <div style="color: var(--theme-color); font-size: 0.9rem; font-weight: 800;">${topScorer ? topScorer.goals + ' GOLS' : ''}</div>
            </div>

            <div style="background: rgba(255,255,255,0.03); padding: 1.25rem; border-radius: 12px; border: 1px dashed rgba(59, 130, 246, 0.3); margin-bottom: 0.5rem;">
                <div style="color: #3b82f6; font-size: 0.75rem; font-weight: 800; text-transform: uppercase; margin-bottom: 8px;">🧤 GOLEIRO MENOS VAZADO</div>
                <div style="display: flex; gap: 8px;">
                    <input type="text" id="bestGkInput" value="${savedGk}" 
                           placeholder="Ex: Francisco de Assis - Escola Francisco de Assis" 
                           style="flex: 1; background: #0f172a; border: 1px solid #334155; color: white; padding: 10px; border-radius: 8px; font-weight: 600;">
                    <button onclick="saveBestGk(${catId}, '${gender}')" 
                            style="background: #3b82f6; color: white; border: none; padding: 0 15px; border-radius: 8px; cursor: pointer; font-weight: 800; font-size: 0.8rem; transition: all 0.2s;"
                            id="saveGkBtn">
                        SALVAR
                    </button>
                </div>
                <div id="saveGkStatus" style="font-size: 0.7rem; color: #64748b; margin-top: 5px;">Digite o nome e a escola do goleiro.</div>
            </div>

            <div style="background: rgba(var(--theme-color-rgb), 0.05); padding: 1.25rem; border-radius: 12px; border: 1px dashed var(--theme-color);">
                <div style="color: var(--theme-color); font-size: 0.75rem; font-weight: 800; text-transform: uppercase; margin-bottom: 8px;">⭐ MELHOR JOGADOR (ELEITO PELOS TÉCNICOS)</div>
                <div style="display: flex; gap: 8px;">
                    <input type="text" id="bestPlayerInput" value="${savedPlayer}" 
                           placeholder="Ex: Samuel Silva - Escola Francisco de Assis" 
                           style="flex: 1; background: #0f172a; border: 1px solid #334155; color: white; padding: 10px; border-radius: 8px; font-weight: 600;">
                    <button onclick="saveBestPlayer(${catId}, '${gender}')" 
                            style="background: var(--theme-color); color: white; border: none; padding: 0 15px; border-radius: 8px; cursor: pointer; font-weight: 800; font-size: 0.8rem; transition: all 0.2s;"
                            id="saveBestBtn">
                        SALVAR
                    </button>
                </div>
                <div id="saveBestStatus" style="font-size: 0.7rem; color: #64748b; margin-top: 5px;">Digite o nome e a escola do eleito.</div>
            </div>
        `;

        podiumDiv.appendChild(awardsWrapper);

    } catch(e) {
        console.error(e);
        document.getElementById('podiumContent').innerHTML = 'Erro ao carregar pódio.';
    }
}

async function renderBracketPreview(container, catId, gender, phase, navHtml = '') {
    container.innerHTML = `
        ${navHtml}
        <div style="text-align:center; padding: 3rem;">
            <div class="preview-badge">✨ PRÉ-VISUALIZAÇÃO</div>
            <h2 style="color: var(--theme-color); margin-bottom: 0.5rem;">${PHASE_NAMES[phase]}</h2>
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

function toggleDebug() {
    const d = document.getElementById('debugInfo');
    d.style.display = d.style.display === 'none' ? 'block' : 'none';
}

loadMatches();
</script>
</body>
</html>
