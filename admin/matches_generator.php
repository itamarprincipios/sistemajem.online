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
let currentCategories = [];

async function init() {
    // Load Events
    const res = await fetch('../api/competition-operators-api.php?action=events');
    const data = await res.json();
    
    // Load Categories (All)
    // In a real scenario, we should filter by what's actually in snapshot, but let's load all for now
    // We'll trust the API to return error if not enough teams
    const resCat = await fetch('../api/competition-events-api.php?action=list'); // Just leveraging existing endpoint or creating a helper... 
    // Wait, simpler:
    // We already have categories in admin/schools.php etc. Let's make a quick helper fetch
    // Or just fetch from categories table if endpoint exists. 
    // Let's use the matches-api helper I created which returns modalities. I'll add categories there too next time.
    // For now, let's just fetch all categories from the main system API if available or hack it.
    // Actually, I'll fetch 'competition-events-api' to get stats, but creating a dedicated 'get_lists' is better.
    // Let's assume user selects Event -> Modality.
}

async function loadEvents() {
    const res = await fetch('../api/competition-operators-api.php?action=events');
    const data = await res.json();
    const select = document.getElementById('eventSelect');
    select.innerHTML = '<option value="">Selecione</option>';
    data.data.forEach(ev => select.innerHTML += `<option value="${ev.id}">${ev.name}</option>`);
}

async function loadOptions() {
    const eventId = document.getElementById('eventSelect').value;
    if (!eventId) return;
    
    // Load Modalities & Categories having teams in this event
    const res = await fetch(`../api/matches-api.php?action=options&event_id=${eventId}`);
    const data = await res.json();
    
    // API logic needs to update to return categories too. I will patch API in next step if needed. 
    // For now let's assume I need to fetch them.
    
    // Temporary Hack: Fetch all categories. Ideally should filter.
    // I will modify matches-api first to provide this data correctly.
}
// Rethinking: I need to update API to ensure correct data flow for options.
</script>

<!-- Updating Script Logic -->
<script>
// Main Logic
document.addEventListener('DOMContentLoaded', () => {
    loadEvents();
    loadCategories(); // Fetch all categories for dropdown
});

async function loadEvents() {
    try {
        const res = await fetch('../api/competition-operators-api.php?action=events');
        const data = await res.json();
        const select = document.getElementById('eventSelect');
        select.innerHTML = '<option value="">Selecione</option>';
        data.data.forEach(ev => select.innerHTML += `<option value="${ev.id}">${ev.name}</option>`);
    } catch(e) {}
}

async function loadCategories() {
    // We don't have a direct public API for categories yet in this context? 
    // I'll make a quick raw fetch to a new helper action in matches-api
    // Re-visiting matches-api.php... I added 'options' action but it only returned modalities.
}

async function handleGenerate(e) {
    e.preventDefault();
    const formData = new FormData(e.target);
    const data = Object.fromEntries(formData);
    
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

async function loadMatches() {
    const eventId = document.getElementById('eventSelect').value;
    if (!eventId) return;

    const res = await fetch(`../api/matches-api.php?action=list&event_id=${eventId}`);
    const data = await res.json();
    
    const tbody = document.getElementById('matchesTable');
    tbody.innerHTML = '';
    
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
            <td><button class="btn btn-sm btn-danger" onclick="deleteMatch(${m.id})">🗑️</button></td>
        `;
        tbody.appendChild(row);
    });
}

function loadOptions() {
    loadMatches();
    // Additional Logic to filter available modalities...
    // For now leaving as manual selection
}

async function deleteMatch(id) {
    if (!confirm('Excluir partida?')) return;
    await fetch(`../api/matches-api.php?id=${id}`, { method: 'DELETE' });
    loadMatches();
}
</script>

<!-- Fetch Categories Helper (Injection) -->
<?php
// PHP Helper to populate categories dropdown server-side or via simple script
$cats = query("SELECT id, name FROM categories ORDER BY name");
echo "<script>
    const allCategories = " . json_encode($cats) . ";
    const catSelect = document.getElementById('categorySelect');
    allCategories.forEach(c => {
        catSelect.innerHTML += `<option value='\${c.id}'>\${c.name}</option>`;
    });
    
    // Also modalities
    const allModalities = " . json_encode(query("SELECT id, name FROM modalities")) . ";
    const modSelect = document.getElementById('modalitySelect');
    modSelect.innerHTML = '<option value=\"\">Selecione</option>';
    allModalities.forEach(m => {
        modSelect.innerHTML += `<option value='\${m.id}'>\${m.name}</option>`;
    });
</script>";
?>

<?php include '../includes/footer.php'; ?>
