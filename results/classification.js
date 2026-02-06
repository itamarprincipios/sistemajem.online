/**
 * Classification Tab Functions
 * Handles group standings and knockout bracket visualization
 */

let currentCategoryId = null;
let currentModalityId = null;

// Sub-tab switching
function switchSubTab(containerId, tabId) {
    const container = document.getElementById(containerId);
    if (!container) return;

    // Update buttons
    container.querySelectorAll('.sub-tab-btn').forEach(btn => {
        btn.classList.remove('active');
    });
    container.querySelector(`[data-subtab="${tabId}"]`)?.classList.add('active');

    // Update content
    container.querySelectorAll('.sub-tab-content').forEach(content => {
        content.style.display = 'none';
    });
    container.querySelector(`#${tabId}`)?.style.display = 'block';
}

// Load and display group standings
async function loadGroupStandings(eventId, modalityId, categoryId) {
    const container = document.getElementById('group-standings-content');
    if (!container) return;

    container.innerHTML = '<div class="loading-spinner"></div>';

    try {
        const res = await fetch(`../api/standings-api.php?action=group_standings&event_id=${eventId}&modality_id=${modalityId}&category_id=${categoryId}&_t=${Date.now()}`);
        const data = await res.json();

        if (!data.success) {
            container.innerHTML = `<div class="empty-state">Erro ao carregar classificação</div>`;
            return;
        }

        const standings = data.data;

        if (Object.keys(standings).length === 0) {
            container.innerHTML = '<div class="empty-state">Nenhuma partida finalizada ainda</div>';
            return;
        }

        let html = '<div class="standings-container">';

        // Sort groups alphabetically
        const sortedGroups = Object.keys(standings).sort();

        for (const groupName of sortedGroups) {
            const teams = standings[groupName];

            html += `
                <div class="group-section">
                    <div class="group-title">Grupo ${groupName}</div>
                    <table class="standings-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Equipe</th>
                                <th>Pts</th>
                                <th>PJ</th>
                                <th>VIT</th>
                                <th>E</th>
                                <th>DER</th>
                                <th>GM</th>
                                <th>GC</th>
                                <th>SG</th>
                            </tr>
                        </thead>
                        <tbody>
            `;

            teams.forEach(team => {
                const isQualified = team.position <= 2;
                const rowClass = isQualified ? 'qualified' : '';

                html += `
                    <tr class="${rowClass}">
                        <td>${team.position}</td>
                        <td class="team-name">${team.team_name}</td>
                        <td class="points">${team.points}</td>
                        <td>${team.played}</td>
                        <td>${team.won}</td>
                        <td>${team.drawn}</td>
                        <td>${team.lost}</td>
                        <td>${team.goals_for}</td>
                        <td>${team.goals_against}</td>
                        <td>${team.goal_difference > 0 ? '+' : ''}${team.goal_difference}</td>
                    </tr>
                `;
            });

            html += `
                        </tbody>
                    </table>
                </div>
            `;
        }

        html += '</div>';
        container.innerHTML = html;

    } catch (e) {
        console.error('Error loading standings:', e);
        container.innerHTML = '<div class="empty-state">Erro ao carregar dados</div>';
    }
}

// Load and display knockout bracket
async function loadKnockoutBracket(eventId, modalityId, categoryId) {
    const container = document.getElementById('knockout-bracket-content');
    if (!container) return;

    container.innerHTML = '<div class="loading-spinner"></div>';

    try {
        const res = await fetch(`../api/standings-api.php?action=knockout_bracket&event_id=${eventId}&modality_id=${modalityId}&category_id=${categoryId}&_t=${Date.now()}`);
        const data = await res.json();

        if (!data.success) {
            container.innerHTML = '<div class="empty-state">Erro ao carregar chaves</div>';
            return;
        }

        const bracket = data.data;
        const phaseNames = {
            'round_of_16': 'Oitavas de Final',
            'quarter_final': 'Quartas de Final',
            'semi_final': 'Semifinal',
            'third_place': 'Disputa de 3º Lugar',
            'final': 'Final'
        };

        let hasMatches = false;
        let html = '<div class="bracket-container">';

        // Display phases in order
        const phaseOrder = ['round_of_16', 'quarter_final', 'semi_final', 'third_place', 'final'];

        for (const phase of phaseOrder) {
            const matches = bracket[phase];
            if (!matches || matches.length === 0) continue;

            hasMatches = true;

            html += `
                <div class="bracket-phase">
                    <div class="bracket-phase-title">${phaseNames[phase]}</div>
                    <div class="bracket-matches">
            `;

            matches.forEach(match => {
                const isFinished = match.status === 'finished';
                const teamAWon = isFinished && match.winner_team_id == match.team_a_id;
                const teamBWon = isFinished && match.winner_team_id == match.team_b_id;

                html += `
                    <div class="bracket-match">
                        <div class="bracket-match-info">
                            ${new Date(match.scheduled_time).toLocaleDateString('pt-BR')} • ${match.venue || 'Local TBD'}
                        </div>
                        <div class="bracket-teams">
                            <div class="bracket-team ${teamAWon ? 'winner' : ''}">
                                <span class="bracket-team-name">${match.team_a_name}</span>
                                ${isFinished ? `<span class="bracket-score">${match.score_team_a}</span>` : ''}
                            </div>
                            <div class="bracket-vs">${isFinished ? '' : 'vs'}</div>
                            <div class="bracket-team ${teamBWon ? 'winner' : ''}">
                                <span class="bracket-team-name">${match.team_b_name}</span>
                                ${isFinished ? `<span class="bracket-score">${match.score_team_b}</span>` : ''}
                            </div>
                        </div>
                    </div>
                `;
            });

            html += `
                    </div>
                </div>
            `;
        }

        html += '</div>';

        if (!hasMatches) {
            container.innerHTML = '<div class="empty-state">Nenhuma partida eliminatória ainda</div>';
        } else {
            container.innerHTML = html;
        }

    } catch (e) {
        console.error('Error loading bracket:', e);
        container.innerHTML = '<div class="empty-state">Erro ao carregar dados</div>';
    }
}

// Load classification data for a category
function loadClassificationData(eventId, modalityId, categoryId) {
    currentCategoryId = categoryId;
    currentModalityId = modalityId;

    loadGroupStandings(eventId, modalityId, categoryId);
    loadKnockoutBracket(eventId, modalityId, categoryId);
}
