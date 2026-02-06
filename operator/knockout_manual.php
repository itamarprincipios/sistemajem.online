<?php
require_once '../config/config.php';
require_once '../includes/auth.php';
require_once '../includes/db.php';

requireLogin(); // Operators can manage knockout stages

$pageTitle = 'Lançamento Manual - Mata-Mata';
include '../includes/header.php';
include '../includes/sidebar.php';

// Get active event
$activeEvent = queryOne("SELECT id, name FROM competition_events WHERE active_flag = TRUE LIMIT 1");
if (!$activeEvent) {
    die("Nenhum evento ativo encontrado");
}

$eventId = $activeEvent['id'];

// Get modalities and categories
$modalities = query("SELECT id, name FROM modalities ORDER BY name");
$categories = query("SELECT id, name FROM categories ORDER BY name");
?>

<div class="main-content">
    <div class="top-bar">
        <h1 class="top-bar-title">✏️ Lançamento Manual - Mata-Mata</h1>
    </div>
    
    <div class="content-wrapper">
        <!-- Filter Section -->
        <div class="glass-card" style="margin-bottom: 2rem;">
            <h2>📋 Selecionar Competição</h2>
            <div style="background: rgba(59, 130, 246, 0.1); border: 1px solid #3b82f6; border-radius: 8px; padding: 1rem; margin-bottom: 1.5rem;">
                <p style="margin: 0; color: #93c5fd; font-size: 0.9rem;">
                    💡 <strong>Como usar:</strong> Selecione a modalidade, categoria, gênero e fase desejada, depois clique em "Carregar" para ver os times qualificados e criar os confrontos.
                </p>
            </div>
            <form id="filterForm" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; align-items: end;">
                <div class="form-group">
                    <label class="form-label">Modalidade</label>
                    <select id="modalitySelect" class="form-select" required>
                        <option value="">Selecione...</option>
                        <?php foreach ($modalities as $mod): ?>
                            <option value="<?= $mod['id'] ?>"><?= htmlspecialchars($mod['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Categoria</label>
                    <select id="categorySelect" class="form-select" required>
                        <option value="">Selecione...</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Gênero</label>
                    <select id="genderSelect" class="form-select">
                        <option value="">Todos</option>
                        <option value="M">Masculino</option>
                        <option value="F">Feminino</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Fase</label>
                    <select id="phaseSelect" class="form-select" required>
                        <option value="round_of_16">Oitavas de Final</option>
                        <option value="quarter_final">Quartas de Final</option>
                        <option value="semi_final">Semifinal</option>
                        <option value="third_place">3º Lugar</option>
                        <option value="final">Final</option>
                    </select>
                </div>
                
                <button type="submit" class="btn btn-primary" style="height: 42px;">🔍 Carregar</button>
            </form>
        </div>

        <!-- Match Creation Form -->
        <div id="creationSection" class="glass-card" style="margin-bottom: 2rem; display: none;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                <h2>➕ Criar Confronto</h2>
                <button id="suggestFifaBtn" class="btn btn-secondary" style="font-size: 0.9rem;">
                    💡 Sugerir Padrão FIFA
                </button>
            </div>
            
            <form id="matchForm" style="display: grid; gap: 1rem;">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div class="form-group">
                        <label class="form-label">Time A</label>
                        <select id="teamASelect" class="form-select" required>
                            <option value="">Selecione o time...</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Time B</label>
                        <select id="teamBSelect" class="form-select" required>
                            <option value="">Selecione o time...</option>
                        </select>
                    </div>
                </div>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div class="form-group">
                        <label class="form-label">Data e Hora</label>
                        <input type="datetime-local" id="scheduledTime" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Local</label>
                        <input type="text" id="venue" class="form-control" placeholder="Ex: Ginásio Municipal" required>
                    </div>
                </div>
                
                <div style="display: flex; gap: 1rem;">
                    <button type="submit" class="btn btn-success" style="flex: 1;">
                        ➕ Adicionar Confronto
                    </button>
                    <button type="button" id="clearFormBtn" class="btn btn-secondary">
                        🔄 Limpar
                    </button>
                </div>
            </form>
        </div>

        <!-- Matches List -->
        <div id="matchesSection" class="glass-card" style="display: none;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                <h2>🎯 Confrontos Criados</h2>
                <div id="matchCounter" style="font-size: 0.9rem; color: #94a3b8;"></div>
            </div>
            <div id="matchesContent"></div>
        </div>
    </div>
</div>

<!-- Edit Modal -->
<div id="editModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.7); z-index: 1000; align-items: center; justify-content: center;">
    <div class="glass-card" style="max-width: 600px; width: 90%; max-height: 90vh; overflow-y: auto;">
        <h2>✏️ Editar Confronto</h2>
        <form id="editForm" style="display: grid; gap: 1rem; margin-top: 1rem;">
            <input type="hidden" id="editMatchId">
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div class="form-group">
                    <label class="form-label">Time A</label>
                    <select id="editTeamA" class="form-select" required>
                        <option value="">Selecione...</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Time B</label>
                    <select id="editTeamB" class="form-select" required>
                        <option value="">Selecione...</option>
                    </select>
                </div>
            </div>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div class="form-group">
                    <label class="form-label">Data e Hora</label>
                    <input type="datetime-local" id="editScheduledTime" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Local</label>
                    <input type="text" id="editVenue" class="form-control" required>
                </div>
            </div>
            
            <div style="display: flex; gap: 1rem;">
                <button type="submit" class="btn btn-success" style="flex: 1;">💾 Salvar</button>
                <button type="button" id="cancelEditBtn" class="btn btn-secondary">❌ Cancelar</button>
            </div>
        </form>
    </div>
</div>

<script>
const eventId = <?= $eventId ?>;
let currentConfig = null;
let availableTeams = [];
let currentMatches = [];

// Phase names mapping
const phaseNames = {
    'round_of_16': 'Oitavas de Final',
    'quarter_final': 'Quartas de Final',
    'semi_final': 'Semifinal',
    'third_place': '3º Lugar',
    'final': 'Final'
};

// Expected match counts
const expectedMatches = {
    'round_of_16': 8,
    'quarter_final': 4,
    'semi_final': 2,
    'third_place': 1,
    'final': 1
};

// Load competition
document.getElementById('filterForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    
    const modalityId = document.getElementById('modalitySelect').value;
    const categoryId = document.getElementById('categorySelect').value;
    const gender = document.getElementById('genderSelect').value;
    const phase = document.getElementById('phaseSelect').value;
    
    if (!modalityId || !categoryId) {
        alert('Selecione modalidade e categoria');
        return;
    }
    
    currentConfig = { modalityId, categoryId, gender, phase };
    
    await loadQualifiedTeams();
    await loadPhaseMatches();
    
    document.getElementById('creationSection').style.display = 'block';
    document.getElementById('matchesSection').style.display = 'block';
});

// Load qualified teams
async function loadQualifiedTeams() {
    try {
        const params = new URLSearchParams({
            action: 'qualified_teams',
            event_id: eventId,
            modality_id: currentConfig.modalityId,
            category_id: currentConfig.categoryId,
            gender: currentConfig.gender
        });
        
        const res = await fetch(`../api/knockout-manual-api.php?${params}`);
        const data = await res.json();
        
        if (!data.success) {
            alert('Erro ao carregar times: ' + data.error);
            return;
        }
        
        availableTeams = data.teams;
        populateTeamSelects();
    } catch (e) {
        console.error(e);
        alert('Erro ao carregar times');
    }
}

// Populate team dropdowns
function populateTeamSelects() {
    const teamASelect = document.getElementById('teamASelect');
    const teamBSelect = document.getElementById('teamBSelect');
    const editTeamA = document.getElementById('editTeamA');
    const editTeamB = document.getElementById('editTeamB');
    
    if (availableTeams.length === 0) {
        const noTeamsMsg = '<option value="">Nenhum time encontrado - verifique se há jogos finalizados</option>';
        [teamASelect, teamBSelect, editTeamA, editTeamB].forEach(select => {
            select.innerHTML = noTeamsMsg;
        });
        alert('⚠️ Nenhum time encontrado para esta competição.\n\nVerifique se:\n- Os jogos da fase de grupos foram finalizados\n- A modalidade e categoria estão corretas\n- Há times cadastrados nesta competição');
        return;
    }
    
    const options = availableTeams.map(team => {
        const posText = team.position ? ` (${team.position}º - Grupo ${team.group})` : ` (Grupo ${team.group})`;
        return `<option value="${team.id}">${team.name}${posText}</option>`;
    }).join('');
    
    [teamASelect, teamBSelect, editTeamA, editTeamB].forEach(select => {
        select.innerHTML = '<option value="">Selecione...</option>' + options;
    });
}

// Load phase matches
async function loadPhaseMatches() {
    try {
        const params = new URLSearchParams({
            action: 'phase_matches',
            event_id: eventId,
            modality_id: currentConfig.modalityId,
            category_id: currentConfig.categoryId,
            phase: currentConfig.phase,
            gender: currentConfig.gender
        });
        
        const res = await fetch(`../api/knockout-manual-api.php?${params}`);
        const data = await res.json();
        
        if (!data.success) {
            alert('Erro ao carregar confrontos: ' + data.error);
            return;
        }
        
        currentMatches = data.matches;
        displayMatches();
    } catch (e) {
        console.error(e);
        alert('Erro ao carregar confrontos');
    }
}

// Display matches
function displayMatches() {
    const content = document.getElementById('matchesContent');
    const counter = document.getElementById('matchCounter');
    
    const expected = expectedMatches[currentConfig.phase];
    const current = currentMatches.length;
    
    counter.innerHTML = `${current} de ${expected} confrontos criados`;
    
    if (current === 0) {
        content.innerHTML = '<p class="text-secondary">Nenhum confronto criado ainda.</p>';
        return;
    }
    
    let html = '<div class="table-container"><table class="data-table"><thead><tr>';
    html += '<th>Data/Hora</th><th>Time A</th><th>vs</th><th>Time B</th><th>Local</th><th>Ações</th>';
    html += '</tr></thead><tbody>';
    
    currentMatches.forEach(match => {
        const date = new Date(match.scheduled_time).toLocaleString('pt-BR');
        html += `<tr>
            <td>${date}</td>
            <td>${match.team_a_name}</td>
            <td style="text-align: center; font-weight: bold;">vs</td>
            <td>${match.team_b_name}</td>
            <td>${match.venue}</td>
            <td>
                <button onclick="editMatch(${match.id})" class="btn btn-sm" style="background: #3b82f6; margin-right: 0.5rem;">✏️</button>
                <button onclick="deleteMatch(${match.id})" class="btn btn-sm" style="background: #ef4444;">🗑️</button>
            </td>
        </tr>`;
    });
    
    html += '</tbody></table></div>';
    content.innerHTML = html;
}

// Create match
document.getElementById('matchForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    
    const teamAId = document.getElementById('teamASelect').value;
    const teamBId = document.getElementById('teamBSelect').value;
    const scheduledTime = document.getElementById('scheduledTime').value;
    const venue = document.getElementById('venue').value;
    
    if (teamAId === teamBId) {
        alert('Os times devem ser diferentes!');
        return;
    }
    
    try {
        const formData = new FormData();
        formData.append('action', 'create_match');
        formData.append('event_id', eventId);
        formData.append('modality_id', currentConfig.modalityId);
        formData.append('category_id', currentConfig.categoryId);
        formData.append('phase', currentConfig.phase);
        formData.append('team_a_id', teamAId);
        formData.append('team_b_id', teamBId);
        formData.append('scheduled_time', scheduledTime);
        formData.append('venue', venue);
        formData.append('gender', currentConfig.gender);
        
        const res = await fetch('../api/knockout-manual-api.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await res.json();
        
        if (data.success) {
            alert('✅ Confronto criado com sucesso!');
            document.getElementById('matchForm').reset();
            await loadPhaseMatches();
        } else {
            alert('Erro: ' + data.error);
        }
    } catch (e) {
        console.error(e);
        alert('Erro ao criar confronto');
    }
});

// Clear form
document.getElementById('clearFormBtn').addEventListener('click', () => {
    document.getElementById('matchForm').reset();
});

// Edit match
function editMatch(matchId) {
    const match = currentMatches.find(m => m.id == matchId);
    if (!match) return;
    
    document.getElementById('editMatchId').value = match.id;
    document.getElementById('editTeamA').value = match.team_a_id;
    document.getElementById('editTeamB').value = match.team_b_id;
    document.getElementById('editScheduledTime').value = match.scheduled_time.replace(' ', 'T');
    document.getElementById('editVenue').value = match.venue;
    
    document.getElementById('editModal').style.display = 'flex';
}

// Update match
document.getElementById('editForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    
    const matchId = document.getElementById('editMatchId').value;
    const teamAId = document.getElementById('editTeamA').value;
    const teamBId = document.getElementById('editTeamB').value;
    const scheduledTime = document.getElementById('editScheduledTime').value;
    const venue = document.getElementById('editVenue').value;
    
    if (teamAId === teamBId) {
        alert('Os times devem ser diferentes!');
        return;
    }
    
    try {
        const formData = new FormData();
        formData.append('action', 'update_match');
        formData.append('match_id', matchId);
        formData.append('team_a_id', teamAId);
        formData.append('team_b_id', teamBId);
        formData.append('scheduled_time', scheduledTime);
        formData.append('venue', venue);
        
        const res = await fetch('../api/knockout-manual-api.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await res.json();
        
        if (data.success) {
            alert('✅ Confronto atualizado com sucesso!');
            document.getElementById('editModal').style.display = 'none';
            await loadPhaseMatches();
        } else {
            alert('Erro: ' + data.error);
        }
    } catch (e) {
        console.error(e);
        alert('Erro ao atualizar confronto');
    }
});

// Cancel edit
document.getElementById('cancelEditBtn').addEventListener('click', () => {
    document.getElementById('editModal').style.display = 'none';
});

// Delete match
async function deleteMatch(matchId) {
    if (!confirm('Tem certeza que deseja excluir este confronto?')) return;
    
    try {
        const formData = new FormData();
        formData.append('action', 'delete_match');
        formData.append('match_id', matchId);
        
        const res = await fetch('../api/knockout-manual-api.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await res.json();
        
        if (data.success) {
            alert('✅ Confronto excluído com sucesso!');
            await loadPhaseMatches();
        } else {
            alert('Erro: ' + data.error);
        }
    } catch (e) {
        console.error(e);
        alert('Erro ao excluir confronto');
    }
}

// Suggest FIFA pattern
document.getElementById('suggestFifaBtn').addEventListener('click', async () => {
    if (!confirm('Isso irá sugerir confrontos baseados no padrão FIFA (1A vs 2B, etc.). Continuar?')) return;
    
    try {
        const formData = new FormData();
        formData.append('action', 'suggest_fifa');
        formData.append('event_id', eventId);
        formData.append('modality_id', currentConfig.modalityId);
        formData.append('category_id', currentConfig.categoryId);
        formData.append('phase', currentConfig.phase);
        formData.append('gender', currentConfig.gender);
        
        const res = await fetch('../api/knockout-manual-api.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await res.json();
        
        if (data.success && data.suggestions.length > 0) {
            const suggestion = data.suggestions[0];
            document.getElementById('teamASelect').value = suggestion.team_a_id;
            document.getElementById('teamBSelect').value = suggestion.team_b_id;
            alert(`💡 Sugestão: ${suggestion.team_a_name} vs ${suggestion.team_b_name}`);
        } else {
            alert('Nenhuma sugestão disponível. Verifique se a fase de grupos está completa.');
        }
    } catch (e) {
        console.error(e);
        alert('Erro ao gerar sugestões');
    }
});
</script>

<?php include '../includes/footer.php'; ?>
