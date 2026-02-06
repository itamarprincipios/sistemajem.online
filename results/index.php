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
        
        .phase-nav { display: flex; align-items: center; justify-content: center; gap: 2rem; margin: 2rem 0; padding: 1.5rem; background: rgba(0,0,0,0.3); border-radius: 12px; }
        .phase-nav button { background: #1e293b; border: 1px solid #334155; color: #94a3b8; padding: 0.75rem 1.5rem; border-radius: 8px; cursor: pointer; font-size: 1.2rem; font-weight: 800; }
        .phase-nav button:hover:not(:disabled) { background: #10b981; color: white; border-color: #10b981; }
        .phase-nav button:disabled { opacity: 0.3; cursor: not-allowed; }
        .phase-title { font-size: 1.8rem; font-weight: 800; color: #10b981; min-width: 300px; text-align: center; }
        
        .subtitle { font-size: 1.2rem; font-weight: 700; color: #64748b; text-align: center; margin-bottom: 1.5rem; }
        
        .grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(350px, 1fr)); gap: 1.5rem; }
        .card { background: #1e293b; padding: 1.5rem; border-radius: 16px; border: 1px solid #334155; }
        .card:hover { transform: translateY(-4px); border-color: #10b981; transition: all 0.2s; }
        
        .card-header { display: flex; justify-content: space-between; margin-bottom: 1rem; font-size: 0.85rem; color: #94a3b8; }
        .badge { padding: 2px 8px; border-radius: 4px; font-weight: 600; font-size: 0.75rem; }
        .badge-live { background: rgba(239, 68, 68, 0.2); color: #ef4444; border: 1px solid #ef4444; }
        .badge-finished { background: rgba(148, 163, 184, 0.2); color: #94a3b8; border: 1px solid #94a3b8; }
        .badge-scheduled { background: rgba(59, 130, 246, 0.2); color: #3b82f6; border: 1px solid #3b82f6; }
        
        .teams { display: flex; flex-direction: column; gap: 0.75rem; margin-bottom: 1rem; }
        .team { display: flex; justify-content: space-between; font-size: 1.1rem; font-weight: 600; }
        .vs { text-align: center; color: #475569; font-weight: 800; font-size: 0.8rem; }
        
        .empty { text-align: center; padding: 3rem; color: #64748b; }
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
            'third_place': '3º LUGAR'
        };
        const PHASE_ORDER = ['group_stage', 'round_of_16', 'quarter_final', 'semi_final', 'final', 'third_place'];
        
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
                if (!mods[mid]) mods[mid] = { name: m.modality_name, cats: {} };
                const cid = m.category_id;
                if (!mods[mid].cats[cid]) mods[mid].cats[cid] = { name: m.category_name, matches: [] };
                mods[mid].cats[cid].matches.push(m);
            });
            
            const modIds = Object.keys(mods);
            if (!state.modality) state.modality = modIds[0];
            
            // Render modality tabs
            document.getElementById('tabs').innerHTML = modIds.map(mid => 
                `<button class="tab-btn ${state.modality == mid ? 'active' : ''}" onclick="switchMod('${mid}')">${mods[mid].name}</button>`
            ).join('');
            
            // Render active modality content
            const mod = mods[state.modality];
            const catIds = Object.keys(mod.cats);
            if (!state.category[state.modality]) state.category[state.modality] = catIds[0];
            
            let html = '<div class="category-tabs">';
            html += catIds.map(cid => 
                `<button class="cat-btn ${state.category[state.modality] == cid ? 'active' : ''}" onclick="switchCat('${cid}')">🏆 ${mod.cats[cid].name}</button>`
            ).join('');
            html += '</div>';
            
            // Render active category content
            const catId = state.category[state.modality];
            const cat = mod.cats[catId];
            if (!state.phase[catId]) state.phase[catId] = 'group_stage';
            
            const phases = PHASE_ORDER.filter(p => p === 'group_stage' || cat.matches.some(m => m.phase === p));
            const phaseIdx = phases.indexOf(state.phase[catId]);
            const canPrev = phaseIdx > 0;
            const canNext = phaseIdx < phases.length - 1;
            
            html += '<div class="phase-nav">';
            html += `<button ${!canPrev ? 'disabled' : ''} onclick="switchPhase('${catId}', -1)">←</button>`;
            html += `<div class="phase-title">${PHASES[state.phase[catId]]}</div>`;
            html += `<button ${!canNext ? 'disabled' : ''} onclick="switchPhase('${catId}', 1)">→</button>`;
            html += '</div>';
            html += '<div class="subtitle">TABELA</div>';
            
            const phaseMatches = cat.matches.filter(m => m.phase === state.phase[catId]);
            if (phaseMatches.length === 0) {
                html += '<div class="empty">Nenhum jogo nesta fase</div>';
            } else {
                html += '<div class="grid">';
                phaseMatches.forEach(m => {
                    const time = new Date(m.scheduled_time);
                    const status = m.status === 'live' ? 'live' : (m.status === 'finished' ? 'finished' : 'scheduled');
                    const statusText = status === 'live' ? 'Ao Vivo' : (status === 'finished' ? 'Encerrado' : 'Agendado');
                    
                    // Clean school names
                    const cleanName = (name) => {
                        if (!name) return 'TBD';
                        return name
                            .replace(/^ESCOLA MUNICIPAL\s+/i, '')
                            .replace(/^ESCOLA\s+/i, '')
                            .replace(/^MUNICIPAL\s+/i, '');
                    };
                    
                    const teamA = cleanName(m.team_a_name);
                    const teamB = cleanName(m.team_b_name);
                    
                    // Determine winner/draw for finished matches
                    let teamAStyle = '';
                    let teamBStyle = '';
                    if (status === 'finished') {
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
                    
                    html += '<div class="card">';
                    html += `<div class="card-header">`;
                    html += `<span>📅 ${time.toLocaleDateString('pt-BR')} ${time.toLocaleTimeString('pt-BR', {hour:'2-digit',minute:'2-digit'})}</span>`;
                    html += `<span class="badge badge-${status}">${statusText}</span>`;
                    html += `</div>`;
                    html += `<div style="font-size:0.75rem;color:#10b981;font-weight:800;margin-bottom:0.5rem">${m.modality_name}${m.group_name ? ' • Grupo ' + m.group_name : ''}</div>`;
                    html += '<div class="teams">';
                    html += `<div class="team"><span style="${teamAStyle}">${teamA}</span>${status !== 'scheduled' ? '<span>'+m.score_team_a+'</span>' : ''}</div>`;
                    html += '<div class="vs">VS</div>';
                    html += `<div class="team"><span style="${teamBStyle}">${teamB}</span>${status !== 'scheduled' ? '<span>'+m.score_team_b+'</span>' : ''}</div>`;
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
        
        function switchPhase(catId, dir) {
            const cat = matches.filter(m => m.category_id == catId);
            const phases = PHASE_ORDER.filter(p => p === 'group_stage' || cat.some(m => m.phase === p));
            const idx = phases.indexOf(state.phase[catId]);
            const newIdx = idx + dir;
            if (newIdx >= 0 && newIdx < phases.length) {
                state.phase[catId] = phases[newIdx];
                render();
            }
        }
        
        load();
        setInterval(load, 10000);
    </script>
</body>
</html>
