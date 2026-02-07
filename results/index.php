<?php
require_once '../config/config.php';
require_once '../includes/db.php';

$event = queryOne("SELECT * FROM competition_events WHERE active_flag = 1 LIMIT 1");
if (!$event) {
    $event = queryOne("SELECT * FROM competition_events ORDER BY created_at DESC LIMIT 1");
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>JEM - Resultados</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root { --primary: #10b981; --dark: #0f172a; --card: #1e293b; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: var(--dark); color: white; }
        
        .header { background: linear-gradient(to right, #059669, #10b981); padding: 1.5rem; text-align: center; }
        .header h1 { font-weight: 800; }
        
        .container { max-width: 1400px; margin: 0 auto; padding: 2rem 1rem; }
        
        .tabs { display: flex; gap: 0.5rem; margin-bottom: 2rem; border-bottom: 1px solid #334155; padding-bottom: 0.5rem; overflow-x: auto; }
        .tab-btn { background: #1e293b; color: #94a3b8; border: 1px solid #334155; padding: 0.75rem 1.5rem; border-radius: 12px 12px 0 0; cursor: pointer; font-weight: 600; border-bottom: none; }
        .tab-btn.active { background: var(--primary); color: white; border-color: var(--primary); }
        .tab-content { display: none; }
        .tab-content.active { display: block; }
        
        .category-tabs { display: flex; gap: 0.5rem; margin-bottom: 1.5rem; padding: 0.5rem; background: rgba(0,0,0,0.2); border-radius: 12px; overflow-x: auto; }
        .cat-btn { background: transparent; color: #94a3b8; border: 1px solid transparent; padding: 0.5rem 1rem; border-radius: 8px; cursor: pointer; font-weight: 600; font-size: 0.85rem; }
        .cat-btn.active { background: rgba(16, 185, 129, 0.2); color: #10b981; border-color: #10b981; }
        .cat-btn.active.fem { background: rgba(236, 72, 153, 0.2); color: #ec4899; border-color: #ec4899; }
        .cat-btn.fem { color: #f472b6; }
        .cat-btn.fem:hover { color: #ec4899; }
        
        .phase-nav { display: flex; align-items: center; justify-content: center; gap: 2rem; margin: 2rem 0; padding: 1.5rem; background: rgba(0,0,0,0.3); border-radius: 12px; }
        .phase-nav button { background: #1e293b; border: 1px solid #334155; color: #94a3b8; padding: 0.75rem 1.5rem; border-radius: 8px; cursor: pointer; font-size: 1.2rem; font-weight: 800; }
        .phase-nav button:hover:not(:disabled) { background: #10b981; color: white; border-color: #10b981; }
        .phase-nav button:disabled { opacity: 0.3; cursor: not-allowed; }
        .phase-title { font-size: 1.8rem; font-weight: 800; color: #10b981; min-width: 300px; text-align: center; }
        
        .subtitle { font-size: 1.2rem; font-weight: 700; color: #64748b; text-align: center; margin-bottom: 1.5rem; }
        
        .grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(350px, 1fr)); gap: 1.5rem; }
        .card { background: #1e293b; padding: 1.5rem; border-radius: 16px; border: 1px solid #334155; }
        .card:hover { transform: translateY(-4px); border-color: #10b981; transition: all 0.2s; }
        
        /* Female Card Styles */
        .card.fem { border-color: rgba(236, 72, 153, 0.3); }
        .card.fem:hover { border-color: #ec4899; box-shadow: 0 0 15px rgba(236, 72, 153, 0.15); }
        .card.fem .modality-label { color: #f472b6 !important; }
        
        .card-header { display: flex; justify-content: space-between; margin-bottom: 1rem; font-size: 0.85rem; color: #94a3b8; }
        .badge { padding: 2px 8px; border-radius: 4px; font-weight: 600; font-size: 0.75rem; }
        .badge-live { background: rgba(239, 68, 68, 0.2); color: #ef4444; border: 1px solid #ef4444; }
        .badge-finished { background: rgba(148, 163, 184, 0.2); color: #94a3b8; border: 1px solid #94a3b8; }
        .badge-scheduled { background: rgba(59, 130, 246, 0.2); color: #3b82f6; border: 1px solid #3b82f6; }
        
        .teams { display: flex; flex-direction: column; gap: 0.75rem; margin-bottom: 1rem; }
        .team { display: flex; justify-content: space-between; font-size: 1.1rem; font-weight: 600; }
        .vs { text-align: center; color: #475569; font-weight: 800; font-size: 0.8rem; }
        
        .empty { text-align: center; padding: 3rem; color: #64748b; }
        
        /* Podium Styles */
        .podium-container {
            display: flex;
            justify-content: center;
            align-items: flex-end;
            gap: 2rem;
            padding: 3rem 1rem;
            min-height: 400px;
        }
        
        .podium-place {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 1rem;
            animation: slideUp 0.6s ease-out;
        }
        
        .podium-place.first { order: 2; }
        .podium-place.second { order: 1; }
        .podium-place.third { order: 3; }
        
        .trophy {
            font-size: 4rem;
            margin-bottom: 0.5rem;
            filter: drop-shadow(0 4px 8px rgba(0,0,0,0.3));
        }
        
        .podium-card {
            background: linear-gradient(135deg, #1e293b, #334155);
            border-radius: 20px;
            padding: 2rem 1.5rem;
            text-align: center;
            min-width: 200px;
            border: 2px solid;
            position: relative;
            overflow: hidden;
        }
        
        .podium-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
        }
        
        .podium-card.gold {
            border-color: #FFD700;
            box-shadow: 0 8px 32px rgba(255, 215, 0, 0.3);
        }
        .podium-card.gold::before { background: linear-gradient(90deg, #FFD700, #FFA500); }
        
        .podium-card.silver {
            border-color: #C0C0C0;
            box-shadow: 0 8px 32px rgba(192, 192, 192, 0.3);
        }
        .podium-card.silver::before { background: linear-gradient(90deg, #C0C0C0, #A8A8A8); }
        
        .podium-card.bronze {
            border-color: #CD7F32;
            box-shadow: 0 8px 32px rgba(205, 127, 50, 0.3);
        }
        .podium-card.bronze::before { background: linear-gradient(90deg, #CD7F32, #B87333); }
        
        .place-number {
            font-size: 3rem;
            font-weight: 800;
            margin-bottom: 0.5rem;
            background: linear-gradient(135deg, var(--color-start), var(--color-end));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .podium-card.gold .place-number { --color-start: #FFD700; --color-end: #FFA500; }
        .podium-card.silver .place-number { --color-start: #C0C0C0; --color-end: #A8A8A8; }
        .podium-card.bronze .place-number { --color-start: #CD7F32; --color-end: #B87333; }
        
        .school-name {
            font-size: 1.1rem;
            font-weight: 700;
            color: white;
            margin-bottom: 0.5rem;
            line-height: 1.3;
        }
        
        .podium-stats {
            display: flex;
            gap: 1rem;
            justify-content: center;
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid rgba(255,255,255,0.1);
        }
        
        .stat {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.25rem;
        }
        
        .stat-label {
            font-size: 0.7rem;
            color: #94a3b8;
            text-transform: uppercase;
        }
        
        .stat-value {
            font-size: 1.2rem;
            font-weight: 800;
            color: white;
        }
        
        @keyframes slideUp {
            from { opacity: 0; transform: translateY(50px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .podium-title {
            text-align: center;
            font-size: 2.5rem;
            font-weight: 800;
            background: linear-gradient(135deg, #FFD700, #FFA500, #FFD700);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 2rem;
            text-transform: uppercase;
            letter-spacing: 2px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>🏆 <?php echo htmlspecialchars($event['name'] ?? 'Jogos Escolares 2026'); ?></h1>
        <p>Acompanhamento em Tempo Real</p>
    </div>
    
    <div class="container">
        <div id="tabs" class="tabs"></div>
        <div id="content"></div>
    </div>

    <script>
        console.log('Script loaded!');
        
        const EVENT_ID = <?php echo $event['id']; ?>;
        let matches = [];
        let state = { modality: null, category: {}, phase: {} };
        
        const PHASES = {
            'group_stage': 'FASE DE GRUPOS',
            'round_of_16': 'OITAVAS',
            'quarter_final': 'QUARTAS',
            'semi_final': 'SEMIFINAL',
            'final': 'FINAL',
            'third_place': '3º LUGAR',
            'podium': 'PÓDIO'
        };
        const PHASE_ORDER = ['group_stage', 'round_of_16', 'quarter_final', 'semi_final', 'final', 'third_place', 'podium'];
        
        async function load() {
            try {
                console.log('Loading matches...');
                const res = await fetch(`../api/matches-api.php?action=list&event_id=${EVENT_ID}&_t=${Date.now()}`);
                const data = await res.json();
                matches = data.data || [];
                console.log('Loaded', matches.length, 'matches');
                render();
            } catch(e) {
                console.error('Error:', e);
                document.getElementById('content').innerHTML = '<div class="empty">Erro ao carregar</div>';
            }
        }
        
        function render() {
            if (matches.length === 0) {
                document.getElementById('content').innerHTML = '<div class="empty">Nenhum jogo encontrado</div>';
                return;
            }
            
            // Group by modality
            const mods = {};
            matches.forEach(m => {
                const mid = m.modality_id;
                const gender = m.team_gender || 'M';
                if (!mods[mid]) mods[mid] = { name: m.modality_name, cats: {} };
                
                // key is category_id + gender
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
            const modIds = Object.keys(mods);
            if (!state.modality) state.modality = modIds[0];
            
            // Render modality tabs
            document.getElementById('tabs').innerHTML = modIds.map(mid => 
                `<button class="tab-btn ${state.modality == mid ? 'active' : ''}" onclick="switchMod('${mid}')">${mods[mid].name}</button>`
            ).join('');
            
            // Render active modality content
            const mod = mods[state.modality];
            const catKeys = Object.keys(mod.cats);
            if (!state.category[state.modality]) state.category[state.modality] = catKeys[0];
            
            let html = '<div class="category-tabs">';
            html += catKeys.map(key => {
                const cat = mod.cats[key];
                const isFem = cat.gender === 'F';
                const label = isFem ? cat.name + ' Fem' : cat.name;
                const activeClass = state.category[state.modality] == key ? 'active' : '';
                const femClass = isFem ? 'fem' : '';
                
                return `<button class="cat-btn ${activeClass} ${femClass}" onclick="switchCat('${key}')">🏆 ${label}</button>`;
            }).join('');
            html += '</div>';
            
            // Render active category content
            const catKey = state.category[state.modality];
            const cat = mod.cats[catKey];
            if (!state.phase[catKey]) state.phase[catKey] = 'group_stage';
            
            // Build available phases
            let phases = PHASE_ORDER.filter(p => {
                if (p === 'group_stage') return true;
                if (p === 'podium') {
                    // Include podium if final and third place are finished
                    const finalMatch = cat.matches.find(m => m.phase === 'final' && m.status === 'finished');
                    const thirdMatch = cat.matches.find(m => m.phase === 'third_place' && m.status === 'finished');
                    return finalMatch && thirdMatch;
                }
                return cat.matches.some(m => m.phase === p);
            });
            
            const phaseIdx = phases.indexOf(state.phase[catKey]);
            const canPrev = phaseIdx > 0;
            const canNext = phaseIdx < phases.length - 1;
            
            html += '<div class="phase-nav">';
            html += `<button ${!canPrev ? 'disabled' : ''} onclick="switchPhase('${catKey}', -1)">←</button>`;
            html += `<div class="phase-title">${PHASES[state.phase[catKey]]}</div>`;
            html += `<button ${!canNext ? 'disabled' : ''} onclick="switchPhase('${catKey}', 1)">→</button>`;
            html += '</div>';
            html += '<div class="subtitle">TABELA</div>';
            
            const phaseMatches = cat.matches.filter(m => m.phase === state.phase[catKey]);
            
            // Special handling for podium
            if (state.phase[catKey] === 'podium') {
                const finalMatch = cat.matches.find(m => m.phase === 'final' && m.status === 'finished');
                const thirdPlaceMatch = cat.matches.find(m => m.phase === 'third_place' && m.status === 'finished');
                
                if (!finalMatch || !thirdPlaceMatch) {
                    html += '<div class="empty">Aguardando conclusão da final e disputa de 3º lugar</div>';
                } else {
                    // Determine winners
                    const champion = finalMatch.score_team_a > finalMatch.score_team_b ? 
                        { name: finalMatch.team_a_name, id: finalMatch.team_a_id } : 
                        { name: finalMatch.team_b_name, id: finalMatch.team_b_id };
                    
                    const runnerUp = finalMatch.score_team_a > finalMatch.score_team_b ? 
                        { name: finalMatch.team_b_name, id: finalMatch.team_b_id } : 
                        { name: finalMatch.team_a_name, id: finalMatch.team_a_id };
                    
                    const thirdPlace = thirdPlaceMatch.score_team_a > thirdPlaceMatch.score_team_b ? 
                        { name: thirdPlaceMatch.team_a_name, id: thirdPlaceMatch.team_a_id } : 
                        { name: thirdPlaceMatch.team_b_name, id: thirdPlaceMatch.team_b_id };
                    
                    // Clean names
                    const cleanName = (name) => {
                        if (!name) return 'TBD';
                        return name
                            .replace(/^ESCOLA MUNICIPAL\s+/i, '')
                            .replace(/^ESCOLA\s+/i, '')
                            .replace(/^MUNICIPAL\s+/i, '');
                    };
                    
                    // Calculate stats for each team
                    const getTeamStats = (teamId) => {
                        const teamMatches = cat.matches.filter(m => 
                            (m.team_a_id == teamId || m.team_b_id == teamId) && m.status === 'finished'
                        );
                        
                        let wins = 0, goals = 0;
                        teamMatches.forEach(m => {
                            if (m.team_a_id == teamId) {
                                goals += parseInt(m.score_team_a) || 0;
                                if (m.score_team_a > m.score_team_b) wins++;
                            } else {
                                goals += parseInt(m.score_team_b) || 0;
                                if (m.score_team_b > m.score_team_a) wins++;
                            }
                        });
                        
                        return { wins, goals };
                    };
                    
                    const champStats = getTeamStats(champion.id);
                    const runnerStats = getTeamStats(runnerUp.id);
                    const thirdStats = getTeamStats(thirdPlace.id);
                    
                    html += '<h2 class="podium-title">🏆 Pódio da Categoria 🏆</h2>';
                    html += '<div class="podium-container">';
                    
                    // 1st Place
                    html += '<div class="podium-place first">';
                    html += '<div class="trophy">🥇</div>';
                    html += '<div class="podium-card gold">';
                    html += '<div class="place-number">1º</div>';
                    html += `<div class="school-name">${cleanName(champion.name)}</div>`;
                    html += '<div class="podium-stats">';
                    html += `<div class="stat"><span class="stat-label">Vitórias</span><span class="stat-value">${champStats.wins}</span></div>`;
                    html += `<div class="stat"><span class="stat-label">Gols</span><span class="stat-value">${champStats.goals}</span></div>`;
                    html += '</div>';
                    html += '</div>';
                    html += '</div>';
                    
                    // 2nd Place
                    html += '<div class="podium-place second">';
                    html += '<div class="trophy">🥈</div>';
                    html += '<div class="podium-card silver">';
                    html += '<div class="place-number">2º</div>';
                    html += `<div class="school-name">${cleanName(runnerUp.name)}</div>`;
                    html += '<div class="podium-stats">';
                    html += `<div class="stat"><span class="stat-label">Vitórias</span><span class="stat-value">${runnerStats.wins}</span></div>`;
                    html += `<div class="stat"><span class="stat-label">Gols</span><span class="stat-value">${runnerStats.goals}</span></div>`;
                    html += '</div>';
                    html += '</div>';
                    html += '</div>';
                    
                    // 3rd Place
                    html += '<div class="podium-place third">';
                    html += '<div class="trophy">🥉</div>';
                    html += '<div class="podium-card bronze">';
                    html += '<div class="place-number">3º</div>';
                    html += `<div class="school-name">${cleanName(thirdPlace.name)}</div>`;
                    html += '<div class="podium-stats">';
                    html += `<div class="stat"><span class="stat-label">Vitórias</span><span class="stat-value">${thirdStats.wins}</span></div>`;
                    html += `<div class="stat"><span class="stat-label">Gols</span><span class="stat-value">${thirdStats.goals}</span></div>`;
                    html += '</div>';
                    html += '</div>';
                    html += '</div>';
                    
                    html += '</div>';
                }
            } else if (phaseMatches.length === 0) {
                html += '<div class="empty">Nenhum jogo nesta fase</div>';
            } else {
                html += '<div class="grid">';
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
                    
                    // Determine winner/draw for finished matches
                    let teamAStyle = '';
                    let teamBStyle = '';
                    if (isFinished) {
                        if (m.score_team_a > m.score_team_b) {
                            teamAStyle = 'color:#FFD700;font-weight:800;'; // Gold for winner
                        } else if (m.score_team_b > m.score_team_a) {
                            teamBStyle = 'color:#FFD700;font-weight:800;'; // Gold for winner
                        } else {
                            // Draw - both red
                            teamAStyle = 'color:#ef4444;font-weight:800;';
                            teamBStyle = 'color:#ef4444;font-weight:800;';
                        }
                    }
                    
                    html += `<div class="card ${isFem ? 'fem' : ''}">`;
                    html += `<div class="card-header">`;
                    html += `<span>📅 ${time.toLocaleDateString('pt-BR')} ${time.toLocaleTimeString('pt-BR', {hour:'2-digit',minute:'2-digit'})}</span>`;
                    html += `<span class="badge" style="background:${genderColor}20; color:${genderColor}; border:1px solid ${genderColor}40">${genderLabel}</span>`;
                    html += `<span class="badge badge-${m.status === 'live' ? 'live' : (m.status === 'finished' ? 'finished' : 'scheduled')}">${m.status === 'live' ? 'Ao Vivo' : (m.status === 'finished' ? 'Encerrado' : 'Agendado')}</span>`;
                    html += `</div>`;
                    html += `<div class="modality-label" style="font-size:0.75rem;color:#10b981;font-weight:800;margin-bottom:0.5rem">${m.modality_name}${m.group_name ? ' • Grupo ' + m.group_name : ''}</div>`;
                    html += '<div class="teams">';
                    html += `<div class="team"><span style="${teamAStyle}">${teamA}</span>${m.status !== 'scheduled' ? '<span>'+m.score_team_a+'</span>' : ''}</div>`;
                    html += '<div class="vs">VS</div>';
                    html += `<div class="team"><span style="${teamBStyle}">${teamB}</span>${m.status !== 'scheduled' ? '<span>'+m.score_team_b+'</span>' : ''}</div>`;
                    html += '</div>';
                    html += `<div style="font-size:0.8rem;color:#64748b">📍 ${m.venue || 'Local TBD'}</div>`;
                    html += '</div>';
                });
                html += '</div>';
            }
            
            document.getElementById('content').innerHTML = html;
        }
        
        function switchMod(id) {
            state.modality = id;
            render();
        }
        
        function switchCat(id) {
            state.category[state.modality] = id;
            render();
        }
        
        function switchPhase(key, dir) {
            const mod = matches.filter(m => m.modality_id == state.modality);
            
            // Extract category_id and gender from key
            const [catId, gender] = key.split('_');
            
            const catMatches = mod.filter(m => m.category_id == catId && (m.team_gender || 'M') == gender);
            
            // Build available phases (same logic as render)
            const phases = PHASE_ORDER.filter(p => {
                if (p === 'group_stage') return true;
                if (p === 'podium') {
                    const finalMatch = catMatches.find(m => m.phase === 'final' && m.status === 'finished');
                    const thirdMatch = catMatches.find(m => m.phase === 'third_place' && m.status === 'finished');
                    return finalMatch && thirdMatch;
                }
                return catMatches.some(m => m.phase === p);
            });
            
            const idx = phases.indexOf(state.phase[key]);
            const newIdx = idx + dir;
            if (newIdx >= 0 && newIdx < phases.length) {
                state.phase[key] = phases[newIdx];
                render();
            }
        }
        
        load();
        setInterval(load, 10000);
    </script>
</body>
</html>
