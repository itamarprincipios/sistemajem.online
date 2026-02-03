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
        .tab-content { display: none; }
        .tab-content.active { display: block; }

        .schedule-btn { background: #334155; color: white; border: none; padding: 0.5rem 1rem; border-radius: 8px; cursor: pointer; display: flex; align-items: center; gap: 0.5rem; font-size: 0.9rem; flex: 1; transition: background 0.2s; }
        .schedule-btn:hover { background: #475569; }
        
        .btn-control { background: linear-gradient(135deg, #8b5cf6, #d946ef); color: white; border: none; padding: 0.5rem 1rem; border-radius: 8px; cursor: pointer; font-weight: 700; flex: 2; text-decoration: none; text-align: center; font-size: 0.9rem; }
        .btn-live { background: #ef4444; }

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
            <span style="font-size: 0.9rem; color: #94a3b8;"><?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
            <a href="../logout.php" style="color: #ef4444; text-decoration: none; font-size: 0.9rem; font-weight: 600;">Sair</a>
        </div>
    </div>

    <div class="dashboard-container">
        <div id="tabsContainer" class="tabs-container">
            <!-- Tabs generated here -->
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
let currentTabId = null;

async function loadMatches() {
    try {
        // Cache busting with timestamp
        const res = await fetch(`../api/matches-api.php?action=list&_t=${Date.now()}`); 
        const data = await res.json();
        allMatches = data.data;
        
        renderGroups();
    } catch(e) {
        console.error(e);
    }
}

function switchTab(safeId) {
    currentTabId = safeId;
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
    
    document.querySelector(`.tab-btn[data-id="${safeId}"]`).classList.add('active');
    document.getElementById(`content-${safeId}`).classList.add('active');
}

function renderGroups() {
    const container = document.getElementById('matchesContainer');
    const tabsContainer = document.getElementById('tabsContainer');
    container.innerHTML = '';
    tabsContainer.innerHTML = '';
    
    if (allMatches.length === 0) {
        container.innerHTML = '<p style="text-align:center; color: #64748b; padding-top: 5rem;">Nenhum jogo encontrado.</p>';
        return;
    }

    // Grouping by Category
    const groups = allMatches.reduce((acc, m) => {
        const cat = m.category_name || 'Sem Categoria';
        if (!acc[cat]) acc[cat] = [];
        acc[cat].push(m);
        return acc;
    }, {});

    const sortedCats = Object.keys(groups).sort();

    sortedCats.forEach((cat, index) => {
        // Create a truly safe ID (alphanumeric only)
        const safeId = "cat-" + btoa(unescape(encodeURIComponent(cat))).replace(/[^a-zA-Z0-9]/g, '');
        
        // If no tab selected yet, default to first
        if (!currentTabId && index === 0) currentTabId = safeId;

        const isActive = currentTabId === safeId;

        // Create Tab Button
        const tabBtn = document.createElement('button');
        tabBtn.className = `tab-btn ${isActive ? 'active' : ''}`;
        tabBtn.innerHTML = `🏆 ${cat}`;
        tabBtn.setAttribute('data-id', safeId);
        tabBtn.onclick = () => switchTab(safeId);
        tabsContainer.appendChild(tabBtn);

        // Create Content Section
        const section = document.createElement('div');
        section.className = `tab-content ${isActive ? 'active' : ''}`;
        section.id = `content-${safeId}`;
        
        const grid = document.createElement('div');
        grid.className = 'matches-grid';
        
        groups[cat].forEach(m => {
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
        container.appendChild(section);
    });
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
