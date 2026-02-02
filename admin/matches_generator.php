<?php
require_once '../config/config.php';
require_once '../includes/auth.php';
require_once '../includes/db.php';

requireAdmin();

$pageTitle = 'Gerador de Jogos';
include '../includes/header.php';
include '../includes/sidebar.php';
?>

<div class="main-content">
    <div class="top-bar">
        <h1 class="top-bar-title">Gerenciar Partidas</h1>
    </div>
    
    <div class="content-wrapper">
        <div class="glass-card" style="margin-bottom: 2rem;">
            <h2>Gerador Automático</h2>
            <p class="text-secondary" style="margin-bottom: 1.5rem;">Selecione os parâmetros para criar a tabela de jogos.</p>
            
            <form id="generatorForm" onsubmit="handleGenerate(event)" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; align-items: end;">
                <div class="form-group">
                    <label class="form-label">Evento</label>
                    <select id="eventSelect" name="event_id" class="form-select" onchange="loadOptions()" required>
                        <option value="">Carregando...</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Modalidade</label>
                    <select id="modalitySelect" name="modality_id" class="form-select" required>
                        <option value="">Selecione o Evento</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Categoria</label>
                    <select id="categorySelect" name="category_id" class="form-select" required>
                        <!-- Loaded via JS -->
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Formato</label>
                    <select name="type" class="form-select" required>
                        <option value="round_robin">Todos contra Todos (Fase de Grupos)</option>
                        <option value="elimination" disabled>Mata-mata (Em breve)</option>
                    </select>
                </div>
                
                <button type="submit" class="btn btn-primary" style="height: 42px;">⚡ Gerar Jogos</button>
            </form>
        </div>

        <div class="glass-card">
            <div style="display: flex; justify-content: space-between; margin-bottom: 1rem;">
                <h3>Partidas Agendadas</h3>
                <button class="btn btn-sm btn-danger" onclick="clearAllMatches()">Limpar Lista</button>
            </div>
            
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Data/Hora</th>
                            <th>Categoria</th>
                            <th>Home</th>
                            <th>Away</th>
                            <th>Local</th>
                            <th>Status</th>
                            <th>Ação</th>
                        </tr>
                    </thead>
                    <tbody id="matchesTable">
                        <!-- Loaded via JS -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    loadEvents();
});

async function loadEvents() {
    try {
        const res = await fetch('../api/competition-operators-api.php?action=events');
        const data = await res.json();
        const select = document.getElementById('eventSelect');
        select.innerHTML = '<option value="">Selecione o Evento</option>';
        data.data.forEach(ev => select.innerHTML += `<option value="${ev.id}">${ev.name}</option>`);
    } catch(e) { console.error(e); }
}

async function loadOptions() {
    const eventId = document.getElementById('eventSelect').value;
    const modSelect = document.getElementById('modalitySelect');
    const catSelect = document.getElementById('categorySelect');
    
    // Reset
    modSelect.innerHTML = '<option value="">Carregando...</option>';
    catSelect.innerHTML = '<option value="">Carregando...</option>';
    
    if (!eventId) {
        modSelect.innerHTML = '<option value="">Selecione o Evento primeiro</option>';
        catSelect.innerHTML = '<option value="">Selecione o Evento primeiro</option>';
        return;
    }
    
    try {
        const res = await fetch(`../api/matches-api.php?action=options&event_id=${eventId}`);
        const data = await res.json();
        
        // Populate Modalities
        modSelect.innerHTML = '<option value="">Selecione a Modalidade</option>';
        if (data.data.modalities.length > 0) {
            data.data.modalities.forEach(m => {
                modSelect.innerHTML += `<option value="${m.id}">${m.name}</option>`;
            });
        } else {
             modSelect.innerHTML = '<option value="">Sem equipes cadastradas</option>';
        }

        // Populate Categories
        catSelect.innerHTML = '<option value="">Selecione a Categoria</option>';
        if (data.data.categories.length > 0) {
            data.data.categories.forEach(c => {
                catSelect.innerHTML += `<option value="${c.id}">${c.name}</option>`;
            });
        } else {
             catSelect.innerHTML = '<option value="">Sem equipes cadastradas</option>';
        }
        
        loadMatches(); // Also refresh the list below
        
    } catch(e) {
        console.error(e);
        modSelect.innerHTML = '<option value="">Erro ao carregar</option>';
    }
}

async function handleGenerate(e) {
    e.preventDefault();
    const formData = new FormData(e.target);
    const data = Object.fromEntries(formData);
    
    if (!data.event_id || !data.modality_id || !data.category_id) {
        Toast.error('Selecione todos os campos!');
        return;
    }
    
    if (!confirm('Gerar partidas? Isso criará novos jogos.')) return;
    
    try {
        const res = await fetch('../api/matches-api.php?action=generate', {
            method: 'POST',
            body: JSON.stringify(data)
        });
        const result = await res.json();
        
        if (result.success) {
            Toast.success(result.message);
            loadMatches();
        } else {
            Toast.error(result.error);
        }
    } catch (e) {
        Toast.error('Erro na geração');
    }
}

// Store matches globally to access them for edit
let currentMatches = [];

async function loadMatches() {
    const eventId = document.getElementById('eventSelect').value;
    const tbody = document.getElementById('matchesTable');
    tbody.innerHTML = '<tr><td colspan="8" style="text-align:center">Carregando...</td></tr>';

    if (!eventId) {
         tbody.innerHTML = '';
         return;
    }

    try {
        const res = await fetch(`../api/matches-api.php?action=list&event_id=${eventId}`);
        const data = await res.json();
        
        currentMatches = data.data; // Store
        tbody.innerHTML = '';
        
        if (data.data.length === 0) {
            tbody.innerHTML = '<tr><td colspan="8" style="text-align:center">Nenhuma partida gerada para este evento.</td></tr>';
            return;
        }
        
        data.data.forEach(m => {
            const row = document.createElement('tr');
            row.innerHTML = `
                <td>#${m.id}</td>
                <td>${new Date(m.scheduled_time).toLocaleString('pt-BR')}</td>
                <td>${m.category_name} (${m.modality_name})</td>
                <td style="font-weight:bold">${m.team_a_name}</td>
                <td style="font-weight:bold">${m.team_b_name}</td>
                <td>${m.venue || '-'}</td>
                <td><span class="badge">${m.status}</span></td>
                <td>
                    <button class="btn btn-sm btn-secondary" onclick="openEditModal(${m.id})">✏️</button>
                    <button class="btn btn-sm btn-danger" onclick="deleteMatch(${m.id})">🗑️</button>
                </td>
            `;
            tbody.appendChild(row);
        });
    } catch (e) {
        console.error(e);
        tbody.innerHTML = '<tr><td colspan="8" style="text-align:center; color:red">Erro ao carregar partidas</td></tr>';
    }
}

// Edit Modal Logic
let currentMatchId = null;

function openEditModal(id) {
    const match = currentMatches.find(m => m.id == id);
    if (!match) return;
    
    currentMatchId = match.id;
    
    // Format datetime for input (YYYY-MM-DDTHH:MM)
    const date = new Date(match.scheduled_time);
    date.setMinutes(date.getMinutes() - date.getTimezoneOffset());
    const dateStr = date.toISOString().slice(0, 16);
    
    document.getElementById('editTime').value = dateStr;
    document.getElementById('editVenue').value = match.venue || '';
    document.getElementById('editModalTitle').textContent = `Editar Jogo #${match.id}: ${match.team_a_name} x ${match.team_b_name}`;
    
    document.getElementById('editModal').classList.add('active');
}

function closeEditModal() {
    document.getElementById('editModal').classList.remove('active');
}

async function handleEditSave(e) {
    e.preventDefault();
    
    const time = document.getElementById('editTime').value;
    const venue = document.getElementById('editVenue').value;
    
    try {
        const res = await fetch('../api/matches-api.php', {
            method: 'PUT',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                id: currentMatchId,
                scheduled_time: time,
                venue: venue
            })
        });
        
        const result = await res.json();
        
        if (result.success) {
            Toast.success('Partida atualizada!');
            closeEditModal();
            loadMatches();
        } else {
            Toast.error('Erro ao atualizar');
        }
    } catch(err) {
        Toast.error('Erro de conexão');
    }
}
</script>

<!-- Edit Modal -->
<div class="modal-overlay" id="editModal">
    <div class="modal">
        <div class="modal-header">
            <h3 class="modal-title" id="editModalTitle">Editar Partida</h3>
            <button class="modal-close" onclick="closeEditModal()">×</button>
        </div>
        <div class="modal-body">
            <form onsubmit="handleEditSave(event)">
                <div class="form-group">
                    <label class="form-label">Data e Horário</label>
                    <input type="datetime-local" id="editTime" class="form-input" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Local (Quadra/Campo)</label>
                    <input type="text" id="editVenue" class="form-input" placeholder="Ex: Quadra 1">
                </div>
                <button type="submit" class="btn btn-primary" style="width: 100%;">Salvar Alterações</button>
            </form>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
