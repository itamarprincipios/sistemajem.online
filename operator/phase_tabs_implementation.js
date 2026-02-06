// Phase Sub-tabs Implementation
// Add this JavaScript code to replace the existing renderGroups() function

function renderGroups() {
    const container = document.getElementById('matchesContainer');
    const tabsContainer = document.getElementById('tabsContainer');
    const countDisplay = document.getElementById('matchCount');

    container.innerHTML = '';
    tabsContainer.innerHTML = '';

    if (countDisplay) countDisplay.textContent = `(${allMatches.length} jogos)`;

    if (allMatches.length === 0) {
        container.innerHTML = '<p style="text-align:center; color: #64748b; padding-top: 5rem;">Nenhum jogo encontrado.</p>';
        return;
    }

    // Phase names and order
    const phaseOrder = ['group_stage', 'round_of_16', 'quarter_final', 'semi_final', 'third_place', 'final'];
    const phaseNames = {
        'group_stage': '📋 Fase de Grupos',
        'round_of_16': '🏆 Oitavas de Final',
        'quarter_final': '🥇 Quartas de Final',
        'semi_final': '🥈 Semifinal',
        'third_place': '🥉 3º Lugar',
        'final': '🏅 Final'
    };

    // Group by Category first, then by Phase
    const categoryGroups = {};
    allMatches.forEach(m => {
        const cat = m.category_name || 'Sem Categoria';
        const phase = m.phase || 'group_stage';

        if (!categoryGroups[cat]) categoryGroups[cat] = {};
        if (!categoryGroups[cat][phase]) categoryGroups[cat][phase] = [];
        categoryGroups[cat][phase].push(m);
    });

    const sortedCats = Object.keys(categoryGroups).sort();

    // Ensure currentTabId is still valid or pick first
    const safeIds = sortedCats.map(cat => "cat_" + cat.replace(/[^a-z0-9]/gi, '_'));
    if (!currentTabId || !safeIds.includes(currentTabId)) {
        currentTabId = safeIds[0];
    }

    sortedCats.forEach((cat, index) => {
        const safeId = safeIds[index];
        const isActive = currentTabId === safeId;

        // Create Category Tab Button
        const tabBtn = document.createElement('button');
        tabBtn.className = `tab-btn ${isActive ? 'active' : ''}`;
        tabBtn.innerHTML = `🏆 ${cat}`;
        tabBtn.setAttribute('data-id', safeId);
        tabBtn.onclick = () => switchTab(safeId);
        tabsContainer.appendChild(tabBtn);

        // Create Category Content Section
        const section = document.createElement('div');
        section.className = `tab-content ${isActive ? 'active' : ''}`;
        section.id = `content-${safeId}`;

        // Get phases for this category
        const phases = Object.keys(categoryGroups[cat]).sort((a, b) => {
            return phaseOrder.indexOf(a) - phaseOrder.indexOf(b);
        });

        // Create Phase Tabs
        const phaseTabsDiv = document.createElement('div');
        phaseTabsDiv.className = 'phase-tabs';

        phases.forEach((phase, phaseIndex) => {
            const phaseBtn = document.createElement('button');
            phaseBtn.className = `phase-tab-btn ${phaseIndex === 0 ? 'active' : ''}`;
            phaseBtn.innerHTML = phaseNames[phase] || phase;
            phaseBtn.setAttribute('data-phase', phase);
            phaseBtn.onclick = (e) => {
                e.stopPropagation();
                // Switch phase tabs within this category
                section.querySelectorAll('.phase-tab-btn').forEach(b => b.classList.remove('active'));
                section.querySelectorAll('.phase-content').forEach(c => c.classList.remove('active'));
                phaseBtn.classList.add('active');
                section.querySelector(`.phase-content[data-phase="${phase}"]`).classList.add('active');
            };
            phaseTabsDiv.appendChild(phaseBtn);
        });

        section.appendChild(phaseTabsDiv);

        // Create Phase Contents
        phases.forEach((phase, phaseIndex) => {
            const phaseContent = document.createElement('div');
            phaseContent.className = `phase-content ${phaseIndex === 0 ? 'active' : ''}`;
            phaseContent.setAttribute('data-phase', phase);

            const grid = document.createElement('div');
            grid.className = 'matches-grid';

            categoryGroups[cat][phase].forEach(m => {
                const isLive = m.status === 'live';
                const isFinished = m.status === 'finished';
                const time = new Date(m.scheduled_time);

                const card = document.createElement('div');
                card.className = 'match-card';
                card.innerHTML = `
                    <div class="match-header">
                        <span>📅 ${time.toLocaleDateString('pt-BR')} às ${time.toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' })}</span>
                        <span class="status-badge ${isLive ? 'status-live' : (isFinished ? 'status-finished' : 'status-scheduled')}">
                            ${isLive ? 'Ao Vivo' : (isFinished ? 'Encerrado' : 'Agendado')}
                        </span>
                    </div>
                    <div style="margin-bottom: 0.5rem; font-size: 0.75rem; color: #10b981; font-weight: 800;">
                        ${m.modality_name} • ${m.group_name || 'Mata-mata'}
                    </div>
                    <div class="match-teams">
                        <div class="team-row">
                            <span>${m.team_a_name}</span>
                            ${isFinished || isLive ? `<span style="color:white">${m.score_team_a}</span>` : ''}
                        </div>
                        <div class="vs-divider">VS</div>
                        <div class="team-row">
                            <span>${m.team_b_name}</span>
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
                `;
                grid.appendChild(card);
            });

            phaseContent.appendChild(grid);
            section.appendChild(phaseContent);
        });

        container.appendChild(section);
    });
}
