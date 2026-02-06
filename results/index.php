<?php
require_once '../config/config.php';
require_once '../includes/db.php';

$event = queryOne("SELECT * FROM competition_events WHERE active_flag = 1 LIMIT 1");
if (!$event) {
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
    <link rel="stylesheet" href="classification-styles.css">
    <style>
        :root { --primary: #10b981; --dark: #0f172a; --card: #1e293b; --text: #f8fafc; }
        body { margin: 0; font-family: 'Inter', sans-serif; background: var(--dark); color: var(--text); }
        
        .header { background: linear-gradient(to right, #059669, #10b981); padding: 1.5rem; text-align: center; box-shadow: 0 4px 20px rgba(0,0,0,0.3); }
        .header h1 { margin: 0; font-weight: 800; letter-spacing: -1px; }
        .header p { margin: 0.5rem 0 0; opacity: 0.9; }
        
        .container { max-width: 900px; margin: 0 auto; padding: 2rem 1rem; }
        
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

        /* Main Tabs */
        .tabs-container { 
            display: flex; 
            gap: 0.5rem; 
            margin-bottom: 2rem; 
            overflow-x: auto; 
            padding-bottom: 0.5rem;
            border-bottom: 1px solid #334155;
            scrollbar-width: none;
        }
        .tabs-container::-webkit-scrollbar { display: none; }
        
        .tab-btn {
            background: #1e293b;
            color: #94a3b8;
            border: 1px solid #334155;
            padding: 0.75rem 1.25rem;
            border-radius: 12px 12px 0 0;
            cursor: pointer;
            font-weight: 600;
            white-space: nowrap;
            transition: all 0.2s;
            border-bottom: none;
            font-size: 0.9rem;
        }
        
        .tab-btn.active {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
        }

        .tab-content { display: none; animation: fadeIn 0.3s ease; }
        .tab-content.active { display: block; }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .scorers-list {
            font-size: 0.75rem;
            color: #94a3b8;
            margin-top: 0.5rem;
            display: flex;
            flex-direction: column;
            gap: 2px;
            max-width: 100%;
        }
        .scorer-item {
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .sub-tab-content {
            display: none;
        }
        .sub-tab-content.active {
            display: block;
        }
    </style>
</head>
<body>

    <header class="header">
        <h1>🏆 <?php echo $event ? htmlspecialchars($event['name']) : 'JEM - Jogos Escolares'; ?></h1>
        <p>Acompanhamento em Tempo Real</p>
    </header>

    <div class="container">
        
        <div id="tabs-container" class="tabs-container">
            <!-- Modality tabs generated here -->
        </div>

        <div id="results-container">
            <!-- Content for each modality -->
        </div>

    </div>

<script src="classification.js"></script>
<script>
    const EVENT_ID = <?php echo $event['id']; ?>;
    const API_URL = '../api/matches-api.php?action=list&event_id=' + EVENT_ID;
    let currentTabId = null;
    let categoriesCache = {};

    function switchTab(safeId) {
        currentTabId = safeId;
        document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
        document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
        
        const btn = document.querySelector(`.tab-btn[data-id="${safeId}"]`);
        const content = document.getElementById(`content-${safeId}`);
        
        if (btn) btn.classList.add('active');
        if (content) content.classList.add('active');
    }

    async function loadCategoriesForModality(modalityId, safeId) {
        if (categoriesCache[modalityId]) {
            return categoriesCache[modalityId];
        }
        
        try {
            const res = await fetch(`../api/standings-api.php?action=categories_by_modality&event_id=${EVENT_ID}&modality_id=${modalityId}`);
            const data = await res.json();
            categoriesCache[modalityId] = data.success ? data.data : [];
            
            // Update select if it exists
            const select = document.getElementById(`category-select-${safeId}`);
            if (select && select.options.length === 1) {
                categoriesCache[modalityId].forEach(cat => {
                    const option = document.createElement('option');
                    option.value = cat.id;
                    option.textContent = cat.name;
                    select.appendChild(option);
                });
            }
            
            return categoriesCache[modalityId];
        } catch (e) {
            console.error('Error loading categories:', e);
            return [];
        }
    }

    async function loadResults() {
        try {
            const res = await fetch(API_URL + '&_t=' + Date.now());
            const data = await res.json();
            const matches = data.data;

            const tabsContainer = document.getElementById('tabs-container');
            const resultsContainer = document.getElementById('results-container');
            
            if (matches.length === 0) {
                resultsContainer.innerHTML = '<div class="empty-state">Nenhum resultado registrado ainda.</div>';
                tabsContainer.innerHTML = '';
                return;
            }

            // Group by Modality
            const groups = matches.reduce((acc, m) => {
                if (m.status !== 'finished' && m.status !== 'live') return acc;
                
                const mod = m.modality_name || 'Geral';
                if (!acc[mod]) acc[mod] = {matches: [], modalityId: m.modality_id};
                acc[mod].matches.push(m);
                return acc;
            }, {});

            const sortedMods = Object.keys(groups).sort();
            
            tabsContainer.innerHTML = '';
            resultsContainer.innerHTML = '';

            const safeIds = sortedMods.map(mod => "mod_" + mod.replace(/[^a-z0-9]/gi, '_'));
            if (!currentTabId || !safeIds.includes(currentTabId)) {
                currentTabId = safeIds[0];
            }

            sortedMods.forEach((mod, index) => {
                const safeId = safeIds[index];
                const isActive = currentTabId === safeId;
                const modalityId = groups[mod].modalityId;

                // Create Tab
                const btn = document.createElement('button');
                btn.className = `tab-btn ${isActive ? 'active' : ''}`;
                btn.innerHTML = mod;
                btn.setAttribute('data-id', safeId);
                btn.onclick = () => switchTab(safeId);
                tabsContainer.appendChild(btn);

                // Create Content
                const content = document.createElement('div');
                content.className = `tab-content ${isActive ? 'active' : ''}`;
                content.id = `content-${safeId}`;

                // Sub-tabs for this modality
                const subTabsHtml = `
                    <div class="sub-tabs-container">
                        <button class="sub-tab-btn active" data-subtab="jogos" onclick="switchSubTab('content-${safeId}', 'jogos')">🎮 JOGOS</button>
                        <button class="sub-tab-btn" data-subtab="classificacao" onclick="switchSubTab('content-${safeId}', 'classificacao'); loadCategoriesForModality(${modalityId}, '${safeId}')">📊 CLASSIFICAÇÃO</button>
                    </div>
                `;

                content.innerHTML = subTabsHtml;

                // JOGOS sub-tab
                const jogosContent = document.createElement('div');
                jogosContent.className = 'sub-tab-content active';
                jogosContent.id = 'jogos';

                const liveMatches = groups[mod].matches.filter(m => m.status === 'live');
                const finishedMatches = groups[mod].matches.filter(m => m.status === 'finished');

                let jogosHtml = '';
                if (liveMatches.length > 0) {
                    jogosHtml += '<div class="section-title">AO VIVO AGORA</div>';
                    liveMatches.forEach(m => jogosHtml += createCard(m, true));
                }

                if (finishedMatches.length > 0) {
                    jogosHtml += '<div class="section-title">RESULTADOS RECENTES</div>';
                    finishedMatches.forEach(m => jogosHtml += createCard(m, false));
                }

                jogosContent.innerHTML = jogosHtml;
                content.appendChild(jogosContent);

                // CLASSIFICAÇÃO sub-tab
                const classContent = document.createElement('div');
                classContent.className = 'sub-tab-content';
                classContent.id = 'classificacao';
                
                let classHtml = '<div style="margin-bottom: 1.5rem;">';
                classHtml += '<label style="display: block; margin-bottom: 0.5rem; color: #94a3b8; font-size: 0.9rem;">Selecione a Categoria:</label>';
                classHtml += `<select id="category-select-${safeId}" class="form-select" style="width: 100%; max-width: 300px; padding: 0.75rem; background: var(--card); color: var(--text); border: 1px solid #334155; border-radius: 8px; font-size: 0.9rem;" onchange="loadClassificationData(${EVENT_ID}, ${modalityId}, this.value); document.getElementById('classification-tabs-${safeId}').style.display = 'flex';">`;
                classHtml += '<option value="">Escolha uma categoria...</option>';
                classHtml += '</select></div>';
                
                classHtml += `
                    <div class="sub-tabs-container" style="display: none;" id="classification-tabs-${safeId}">
                        <button class="sub-tab-btn active" data-subtab="group-standings" onclick="switchSubTab('classification-content-${safeId}', 'group-standings')">Fase de Grupos</button>
                        <button class="sub-tab-btn" data-subtab="knockout-bracket" onclick="switchSubTab('classification-content-${safeId}', 'knockout-bracket')">Eliminatórias</button>
                    </div>
                    <div id="classification-content-${safeId}">
                        <div id="group-standings" class="sub-tab-content active">
                            <div id="group-standings-content"></div>
                        </div>
                        <div id="knockout-bracket" class="sub-tab-content">
                            <div id="knockout-bracket-content"></div>
                        </div>
                    </div>
                `;
                
                classContent.innerHTML = classHtml;
                content.appendChild(classContent);

                resultsContainer.appendChild(content);
            });
            
        } catch(e) {
            console.error('Error loading results:', e);
            document.getElementById('results-container').innerHTML = '<div class="empty-state">Erro ao carregar resultados. Verifique o console.</div>';
        }
    }

    function createCard(m, isLive) {
        const events = m.events || [];
        const teamA_events = events.filter(e => e.team_id == m.team_a_id);
        const teamB_events = events.filter(e => e.team_id == m.team_b_id);

        const renderEvents = (evs) => {
            if (evs.length === 0) return '';
            const html = evs.map(e => {
                const name = e.athlete_name || 'Atleta';
                let icon = '⚽';
                let suffix = '';
                
                if (e.event_type === 'YELLOW_CARD') icon = '🟨';
                else if (e.event_type === 'RED_CARD') icon = '🟥';
                else if (e.event_type === 'OWN_GOAL') suffix = ' (GC)';
                
                return `<div class="scorer-item">${icon} ${name}${suffix}</div>`;
            }).join('');
            return `<div class="scorers-list">${html}</div>`;
        };

        return `
            <div class="match-card" style="${isLive ? 'border-color: #059669;' : ''}">
                ${isLive ? '<div class="live-indicator"><div class="live-dot"></div> EM ANDAMENTO</div>' : ''}
                <div class="match-info">${m.category_name} - ${m.modality_name} • ${isLive ? '⏱️ JOGANDO' : '🏁 FINALIZADO'}</div>
                
                <div class="scoreboard">
                    <div class="team">
                        <div class="team-name">${m.team_a_name}</div>
                        <div class="score">${m.score_team_a}</div>
                        ${renderEvents(teamA_events)}
                    </div>
                    <div style="font-size: 1.5rem; color: #475569; margin-top: -1rem">x</div>
                    <div class="team">
                         <div class="team-name">${m.team_b_name}</div>
                         <div class="score">${m.score_team_b}</div>
                         ${renderEvents(teamB_events)}
                    </div>
                </div>
            </div>
        `;
    }

    // Auto Refresh
    loadResults();
    setInterval(loadResults, 10000);
</script>
</body>
</html>
