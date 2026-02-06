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
    <style>
        :root { --primary: #10b981; --dark: #0f172a; --card: #1e293b; --text: #f8fafc; }
        body { margin: 0; font-family: 'Inter', sans-serif; background: var(--dark); color: var(--text); }
        
        .header { 
            background: linear-gradient(to right, #059669, #10b981); 
            padding: 1.5rem; 
            text-align: center; 
            box-shadow: 0 4px 20px rgba(0,0,0,0.3); 
        }
        .header h1 { margin: 0; font-weight: 800; letter-spacing: -1px; }
        .header p { margin: 0.5rem 0 0; opacity: 0.9; }
        
        .container { max-width: 1400px; margin: 0 auto; padding: 2rem 1rem; }
        
        /* Main Tabs (Modalities) */
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
            padding: 0.75rem 1.5rem;
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

        .tab-content { display: none; }
        .tab-content.active { display: block; }
        
        /* Category Sub-tabs */
        .category-tabs {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 1.5rem;
            padding: 0.5rem;
            background: rgba(0,0,0,0.2);
            border-radius: 12px;
            overflow-x: auto;
        }
        
        .category-tab-btn {
            background: transparent;
            color: #94a3b8;
            border: 1px solid transparent;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            font-size: 0.85rem;
            white-space: nowrap;
            transition: all 0.2s;
        }
        
        .category-tab-btn:hover {
            background: rgba(255,255,255,0.05);
        }
        
        .category-tab-btn.active {
            background: rgba(16, 185, 129, 0.2);
            color: #10b981;
            border-color: #10b981;
        }
        
        .category-content { display: none; }
        .category-content.active { display: block; }
        
        /* Phase Navigation */
        .phase-navigation {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 2rem;
            margin: 2rem 0;
            padding: 1.5rem;
            background: rgba(0,0,0,0.3);
            border-radius: 12px;
        }
        
        .phase-nav-btn {
            background: #1e293b;
            border: 1px solid #334155;
            color: #94a3b8;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1.2rem;
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
        
        /* Match Cards */
        .matches-grid { 
            display: grid; 
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr)); 
            gap: 1.5rem; 
        }
        
        .match-card { 
            background: #1e293b; 
            padding: 1.5rem; 
            border-radius: 16px; 
            border: 1px solid #334155; 
            transition: transform 0.2s, border-color 0.2s; 
            position: relative; 
        }
        
        .match-card:hover { 
            transform: translateY(-4px); 
            border-color: #10b981; 
        }
        
        .match-header { 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            margin-bottom: 1rem; 
            font-size: 0.85rem; 
            color: #94a3b8; 
        }
        
        .status-badge { 
            padding: 2px 8px; 
            border-radius: 4px; 
            font-weight: 600; 
            font-size: 0.75rem; 
            text-transform: uppercase; 
        }
        
        .status-live { 
            background: rgba(239, 68, 68, 0.2); 
            color: #ef4444; 
            border: 1px solid #ef4444; 
            animation: pulse 2s infinite; 
        }
        
        .status-scheduled { 
            background: rgba(59, 130, 246, 0.2); 
            color: #3b82f6; 
            border: 1px solid #3b82f6; 
        }
        
        .status-finished { 
            background: rgba(148, 163, 184, 0.2); 
            color: #94a3b8; 
            border: 1px solid #94a3b8; 
        }
        
        .match-teams { 
            display: flex; 
            flex-direction: column; 
            gap: 0.75rem; 
            margin-bottom: 1rem; 
        }
        
        .team-row { 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            font-size: 1.1rem; 
            font-weight: 600; 
        }
        
        .vs-divider { 
            text-align: center; 
            margin: 0.5rem 0; 
            color: #475569; 
            font-weight: 800; 
            font-size: 0.8rem; 
        }
        
        .empty-state { 
            text-align: center; 
            padding: 3rem; 
            color: #64748b; 
        }
        
        @keyframes pulse { 
            0% { opacity: 1; } 
            50% { opacity: 0.5; } 
            100% { opacity: 1; } 
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
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

        <div id="content-container">
            <!-- Content for each modality -->
        </div>
    </div>

<script>
    const EVENT_ID = <?php echo $event['id']; ?>;
    const API_URL = '../api/matches-api.php?action=list&event_id=' + EVENT_ID;
    
    let allMatches = [];
    let currentModalityId = null;
    let currentCategoryIds = {};
    let currentPhases = {};
    
    const PHASE_NAMES = {
        'group_stage': 'FASE DE GRUPOS',
        'round_of_16': 'OITAVAS DE FINAL',
        'quarter_final': 'QUARTAS DE FINAL',
        'semi_final': 'SEMIFINAL',
        'final': 'FINAL',
        'third_place': 'DISPUTA DE 3º LUGAR'
    };
    
    const PHASE_ORDER = ['group_stage', 'round_of_16', 'quarter_final', 'semi_final', 'final', 'third_place'];
    
    function switchModality(modalityId) {
        currentModalityId = modalityId;
        document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
        document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
        
        const btn = document.querySelector(`.tab-btn[data-id="${modalityId}"]`);
        const content = document.getElementById(`modality-${modalityId}`);
        
        if (btn) btn.classList.add('active');
        if (content) content.classList.add('active');
    }
    
    function switchCategory(modalityId, categoryId) {
        currentCategoryIds[modalityId] = categoryId;
        
        const container = document.getElementById(`modality-${modalityId}`);
        if (!container) return;
        
        container.querySelectorAll('.category-tab-btn').forEach(b => b.classList.remove('active'));
        container.querySelectorAll('.category-content').forEach(c => c.classList.remove('active'));
        
        const btn = container.querySelector(`.category-tab-btn[data-id="${categoryId}"]`);
        const content = container.querySelector(`#category-${categoryId}`);
        
        if (btn) btn.classList.add('active');
        if (content) content.classList.add('active');
    }
    
    function switchPhase(categoryId, direction) {
        const currentPhase = currentPhases[categoryId] || 'group_stage';
        const categoryMatches = allMatches.filter(m => m.category_id == categoryId);
        
        const availablePhases = PHASE_ORDER.filter(phase => 
            phase === 'group_stage' || categoryMatches.some(m => m.phase === phase)
        );
        
        const currentIndex = availablePhases.indexOf(currentPhase);
        if (currentIndex === -1) return;
        
        let newIndex = currentIndex + direction;
        if (newIndex < 0 || newIndex >= availablePhases.length) return;
        
        const newPhase = availablePhases[newIndex];
        currentPhases[categoryId] = newPhase;
        
        renderPhaseContent(categoryId);
    }
    
    function renderPhaseContent(categoryId) {
        const currentPhase = currentPhases[categoryId] || 'group_stage';
        const categoryMatches = allMatches.filter(m => m.category_id == categoryId);
        const phaseMatches = categoryMatches.filter(m => m.phase === currentPhase);
        
        const contentDiv = document.getElementById(`category-${categoryId}`);
        if (!contentDiv) return;
        
        const availablePhases = PHASE_ORDER.filter(phase => 
            phase === 'group_stage' || categoryMatches.some(m => m.phase === phase)
        );
        
        const currentIndex = availablePhases.indexOf(currentPhase);
        const canGoPrev = currentIndex > 0;
        const canGoNext = currentIndex < availablePhases.length - 1;
        
        contentDiv.innerHTML = `
            <div class="phase-navigation">
                <button class="phase-nav-btn" onclick="switchPhase(${categoryId}, -1)" ${!canGoPrev ? 'disabled' : ''}>←</button>
                <h2 class="phase-title">${PHASE_NAMES[currentPhase] || currentPhase.toUpperCase()}</h2>
                <button class="phase-nav-btn" onclick="switchPhase(${categoryId}, 1)" ${!canGoNext ? 'disabled' : ''}>→</button>
            </div>
            <div class="phase-subtitle">TABELA</div>
            <div class="matches-grid" id="grid-${categoryId}"></div>
        `;
        
        const grid = document.getElementById(`grid-${categoryId}`);
        
        if (phaseMatches.length === 0) {
            grid.innerHTML = '<p class="empty-state">Nenhum jogo nesta fase.</p>';
            return;
        }
        
        phaseMatches.forEach(m => {
            const isLive = m.status === 'live';
            const isFinished = m.status === 'finished';
            const time = new Date(m.scheduled_time);
            
            const card = document.createElement('div');
            card.className = 'match-card';
            card.innerHTML = `
                <div class="match-header">
                    <span>📅 ${time.toLocaleDateString('pt-BR')} às ${time.toLocaleTimeString('pt-BR', {hour: '2-digit', minute:'2-digit'})}</span>
                    <span class="status-badge ${isLive ? 'status-live' : (isFinished ? 'status-finished' : 'status-scheduled')}">
                        ${isLive ? 'Ao Vivo' : (isFinished ? 'Encerrado' : 'Agendado')}
                    </span>
                </div>
                <div style="margin-bottom: 0.5rem; font-size: 0.75rem; color: #10b981; font-weight: 800;">
                    ${m.modality_name}${m.group_name ? ' • Grupo ' + m.group_name : ''}
                </div>
                <div class="match-teams">
                    <div class="team-row">
                        <span>${m.team_a_name || 'A definir'}</span>
                        ${isFinished || isLive ? `<span style="color:white">${m.score_team_a}</span>` : ''}
                    </div>
                    <div class="vs-divider">VS</div>
                    <div class="team-row">
                        <span>${m.team_b_name || 'A definir'}</span>
                        ${isFinished || isLive ? `<span style="color:white">${m.score_team_b}</span>` : ''}
                    </div>
                </div>
                <div style="margin-bottom: 0.5rem; font-size: 0.8rem; color: #64748b;">
                    📍 ${m.venue || 'Local não definido'}
                </div>
            `;
            grid.appendChild(card);
        });
    }
    
    async function loadResults() {
        try {
            const res = await fetch(API_URL + '&_t=' + Date.now());
            const data = await res.json();
            allMatches = data.data;
            
            console.log('Loaded matches:', allMatches.length);
            console.log('Sample match:', allMatches[0]);
            
            renderPage();
        } catch(e) {
            console.error('Error loading results:', e);
            document.getElementById('content-container').innerHTML = '<div class="empty-state">Erro ao carregar resultados.</div>';
        }
    }
    
    function renderPage() {
        const tabsContainer = document.getElementById('tabs-container');
        const contentContainer = document.getElementById('content-container');
        
        console.log('Rendering page with', allMatches.length, 'matches');
        
        if (allMatches.length === 0) {
            contentContainer.innerHTML = '<div class="empty-state">Nenhum resultado registrado ainda.</div>';
            tabsContainer.innerHTML = '';
            return;
        }
        
        // Group by Modality
        const modalityGroups = allMatches.reduce((acc, m) => {
            const modId = m.modality_id;
            const modName = m.modality_name || 'Geral';
            if (!acc[modId]) acc[modId] = { name: modName, categories: {} };
            
            const catId = m.category_id;
            const catName = m.category_name || 'Sem Categoria';
            if (!acc[modId].categories[catId]) acc[modId].categories[catId] = { name: catName, matches: [] };
            acc[modId].categories[catId].matches.push(m);
            
            return acc;
        }, {});
        
        console.log('Modality groups:', modalityGroups);
        
        const modalityIds = Object.keys(modalityGroups).sort((a, b) => 
            modalityGroups[a].name.localeCompare(modalityGroups[b].name)
        );
        
        if (!currentModalityId || !modalityIds.includes(currentModalityId)) {
            currentModalityId = modalityIds[0];
        }
        
        console.log('Current modality:', currentModalityId);
        
        tabsContainer.innerHTML = '';
        contentContainer.innerHTML = '';
        
        modalityIds.forEach(modId => {
            const modData = modalityGroups[modId];
            const isActive = currentModalityId == modId;
            
            // Create Modality Tab
            const tabBtn = document.createElement('button');
            tabBtn.className = `tab-btn ${isActive ? 'active' : ''}`;
            tabBtn.innerHTML = modData.name;
            tabBtn.setAttribute('data-id', modId);
            tabBtn.onclick = () => switchModality(modId);
            tabsContainer.appendChild(tabBtn);
            
            // Create Modality Content
            const modalityContent = document.createElement('div');
            modalityContent.className = `tab-content ${isActive ? 'active' : ''}`;
            modalityContent.id = `modality-${modId}`;
            
            // Category Tabs
            const categoryIds = Object.keys(modData.categories).sort((a, b) => 
                modData.categories[a].name.localeCompare(modData.categories[b].name)
            );
            
            if (!currentCategoryIds[modId] || !categoryIds.includes(currentCategoryIds[modId])) {
                currentCategoryIds[modId] = categoryIds[0];
            }
            
            console.log('Categories for modality', modId, ':', categoryIds);
            
            const categoryTabsHtml = `
                <div class="category-tabs">
                    ${categoryIds.map(catId => {
                        const catData = modData.categories[catId];
                        const isActiveCat = currentCategoryIds[modId] == catId;
                        return `<button class="category-tab-btn ${isActiveCat ? 'active' : ''}" data-id="${catId}" onclick="switchCategory(${modId}, ${catId})">🏆 ${catData.name}</button>`;
                    }).join('')}
                </div>
            `;
            
            modalityContent.innerHTML = categoryTabsHtml;
            
            // Category Contents
            categoryIds.forEach(catId => {
                const isActiveCat = currentCategoryIds[modId] == catId;
                
                if (!currentPhases[catId]) {
                    currentPhases[catId] = 'group_stage';
                }
                
                const categoryContentDiv = document.createElement('div');
                categoryContentDiv.className = `category-content ${isActiveCat ? 'active' : ''}`;
                categoryContentDiv.id = `category-${catId}`;
                
                modalityContent.appendChild(categoryContentDiv);
                
                if (isActiveCat && isActive) {
                    console.log('Rendering phase content for category', catId);
                    renderPhaseContent(catId);
                }
            });
            
            contentContainer.appendChild(modalityContent);
        });
    }
    
    // Auto Refresh
    loadResults();
    setInterval(loadResults, 10000);
</script>
</body>
</html>
