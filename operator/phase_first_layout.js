// Phase-First Layout Implementation
// Main tabs: PHASES (Grupos, Oitavas, Quartas, Semi, Final)
// Content: Matches grouped by CATEGORY within each phase

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

    // Group by PHASE first, then by CATEGORY
    const phaseGroups = {};
    allMatches.forEach(m => {
        const phase = m.phase || 'group_stage';
        const cat = m.category_name || 'Sem Categoria';

        if (!phaseGroups[phase]) phaseGroups[phase] = {};
        if (!phaseGroups[phase][cat]) phaseGroups[phase][cat] = [];
        phaseGroups[phase][cat].push(m);
    });

    // Get all phases that have matches
    const availablePhases = phaseOrder.filter(phase => phaseGroups[phase]);

    // Ensure currentTabId is valid or pick first
    const safeIds = availablePhases.map(phase => "phase_" + phase);
    if (!currentTabId || !safeIds.includes(currentTabId)) {
        currentTabId = safeIds[0];
    }

    // Create Phase Tabs (Main Navigation)
    availablePhases.forEach((phase, index) => {
        const safeId = safeIds[index];
        const isActive = currentTabId === safeId;

        // Create Phase Tab Button
        const tabBtn = document.createElement('button');
        tabBtn.className = `tab-btn ${isActive ? 'active' : ''}`;
        tabBtn.innerHTML = phaseNames[phase] || phase;
        tabBtn.setAttribute('data-id', safeId);
        tabBtn.onclick = () => switchTab(safeId);
        tabsContainer.appendChild(tabBtn);

        // Create Phase Content Section
        const section = document.createElement('div');
        section.className = `tab-content ${isActive ? 'active' : ''}`;
        section.id = `content-${safeId}`;

        // Get categories for this phase, sorted alphabetically
        const categories = Object.keys(phaseGroups[phase]).sort();

        // Create sections for each category
        categories.forEach(cat => {
            // Category Header
            const catHeader = document.createElement('div');
            catHeader.className = 'category-title';
            catHeader.innerHTML = `🏆 ${cat}`;
            section.appendChild(catHeader);

            // Matches Grid for this category
            const grid = document.createElement('div');
            grid.className = 'matches-grid';

            phaseGroups[phase][cat].forEach(m => {
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

            section.appendChild(grid);

            // Add spacing between categories
            const spacer = document.createElement('div');
            spacer.style.marginBottom = '3rem';
            section.appendChild(spacer);
        });

        container.appendChild(section);
    });
}
