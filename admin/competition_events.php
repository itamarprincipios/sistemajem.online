<?php
require_once '../config/config.php';
require_once '../includes/auth.php';
require_once '../includes/db.php';

requireAdmin();

$pageTitle = 'Eventos da Competição';
include '../includes/header.php';
include '../includes/sidebar.php';
?>

<div class="main-content">
    <div class="top-bar">
        <h1 class="top-bar-title">Gestão de Eventos</h1>
        <div class="user-menu">
            <div class="user-info">
                <div class="user-name"><?php echo htmlspecialchars(getCurrentUserName()); ?></div>
                <div class="user-role">Administrador</div>
            </div>
        </div>
    </div>
    
    <div class="content-wrapper">
        <div class="glass-card" style="margin-bottom: 2rem;">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <h2>Meus Eventos</h2>
                    <p style="color: var(--text-secondary);">Gerencie as edições dos Jogos Escolares</p>
                </div>
                <button class="btn btn-primary" onclick="openCreateModal()">+ Novo Evento</button>
            </div>
        </div>

        <div id="eventsGrid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(350px, 1fr)); gap: 1.5rem;">
            <!-- Events loaded here -->
        </div>
    </div>
</div>

<!-- Modal Create -->
<div class="modal-overlay" id="createModal">
    <div class="modal">
        <div class="modal-header">
            <h3 class="modal-title">Novo Evento</h3>
            <button class="modal-close" onclick="closeCreateModal()">×</button>
        </div>
        <div class="modal-body">
            <form id="createForm" onsubmit="handleCreate(event)">
                <div class="form-group">
                    <label class="form-label">Nome do Evento</label>
                    <input type="text" name="name" class="form-input" placeholder="Ex: JEM 2026" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Cidade Sede</label>
                    <input type="text" name="location_city" class="form-input" placeholder="Ex: Rorainópolis" required>
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div class="form-group">
                        <label class="form-label">Data Início</label>
                        <input type="date" name="start_date" class="form-input" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Data Término</label>
                        <input type="date" name="end_date" class="form-input">
                    </div>
                </div>
                <button type="submit" class="btn btn-primary" style="width: 100%;">Criar Evento</button>
            </form>
        </div>
    </div>
</div>

<script>
async function loadEvents() {
    try {
        const res = await fetch('../api/competition-events-api.php?action=list');
        const data = await res.json();
        
        if (data.success) {
            renderEvents(data.data);
        }
    } catch (e) {
        console.error(e);
        Toast.error('Erro ao carregar eventos');
    }
}

function renderEvents(events) {
    const grid = document.getElementById('eventsGrid');
    grid.innerHTML = '';
    
    events.forEach(event => {
        const isActive = event.active_flag == 1;
        const statusColors = {
            'planning': 'var(--warning)',
            'ready': 'var(--info)',
            'live': 'var(--success)',
            'finished': 'var(--text-secondary)'
        };
        const statusLabels = {
            'planning': 'Planejamento',
            'ready': 'Pronto',
            'live': 'Em Andamento',
            'finished': 'Finalizado'
        };
        
        const card = document.createElement('div');
        card.className = 'glass-card';
        card.innerHTML = `
            <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 1rem;">
                <div>
                    ${isActive ? '<span class="badge badge-success" style="margin-bottom: 0.5rem; display: inline-block;">Ativo no Site</span>' : ''}
                    <h3 style="margin: 0; font-size: 1.25rem;">${event.name}</h3>
                    <p style="color: var(--text-secondary); font-size: 0.9rem; margin-top: 0.25rem;">
                        📍 ${event.location_city || 'Sem local'}
                    </p>
                </div>
                <span class="badge" style="background: ${statusColors[event.status]}20; color: ${statusColors[event.status]}; border: 1px solid ${statusColors[event.status]}">
                    ${statusLabels[event.status]}
                </span>
            </div>
            
            <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 0.5rem; margin-bottom: 1.5rem; background: rgba(0,0,0,0.2); padding: 1rem; border-radius: 8px; text-align: center;">
                <div>
                    <div style="font-weight: bold; font-size: 1.1rem;">-</div>
                    <div style="font-size: 0.75rem; color: var(--text-secondary);">Equipes</div>
                </div>
                <div>
                    <div style="font-weight: bold; font-size: 1.1rem;">-</div>
                    <div style="font-size: 0.75rem; color: var(--text-secondary);">Atletas</div>
                </div>
                 <div>
                    <div style="font-weight: bold; font-size: 1.1rem;">-</div>
                    <div style="font-size: 0.75rem; color: var(--text-secondary);">Partidas</div>
                </div>
            </div>

            <div style="display: grid; gap: 0.5rem;">
                <button class="btn btn-secondary" onclick="generateSnapshot(${event.id})" ${event.status === 'finished' ? 'disabled' : ''}>
                    📸 Atualizar Snapshot
                </button>
                ${event.status === 'planning' ? `
                    <button class="btn btn-primary" onclick="changeStatus(${event.id}, 'live')">▶️ Iniciar Competição</button>
                ` : ''}
                 ${event.status === 'live' ? `
                    <button class="btn btn-danger" onclick="changeStatus(${event.id}, 'finished')">🏁 Encerrar Evento</button>
                ` : ''}
                 ${!isActive ? `
                     <button class="btn btn-sm btn-secondary" onclick="setActive(${event.id})">⭐ Definir como Principal</button>
                 ` : ''}
            </div>
        `;
        grid.appendChild(card);
        
        // Load detailed stats lazily
        loadEventStats(event.id, card);
    });
}

// Lazy load stats
async function loadEventStats(id, cardElement) {
    try {
        const res = await fetch(`../api/competition-events-api.php?action=get&id=${id}`);
        const data = await res.json();
        if (data.success && data.data.stats) {
            const statsDiv = cardElement.querySelectorAll('div > div > div:first-child');
            statsDiv[0].textContent = data.data.stats.teams;
            statsDiv[1].textContent = data.data.stats.athletes;
            statsDiv[2].textContent = data.data.stats.matches;
        }
    } catch (e) { console.error(e); }
}

async function handleCreate(e) {
    e.preventDefault();
    const formData = new FormData(e.target);
    const data = Object.fromEntries(formData);
    
    try {
        const res = await fetch('../api/competition-events-api.php?action=create', {
            method: 'POST',
            body: JSON.stringify(data)
        });
        const result = await res.json();
        
        if (result.success) {
            Toast.success('Evento criado!');
            closeCreateModal();
            loadEvents();
        } else {
            Toast.error(result.error);
        }
    } catch (err) {
        Toast.error('Erro ao criar evento');
    }
}

async function generateSnapshot(id) {
    if (!confirm('Isto irá importar TODAS as equipes APROVADAS para a tabela de competição. Se já existirem, elas serão ignoradas (apenas novas serão adicionadas). Continuar?')) return;
    
    try {
        const res = await fetch('../api/competition-events-api.php?action=snapshot', {
            method: 'POST',
            body: JSON.stringify({ action: 'snapshot', event_id: id })
        });
        const result = await res.json();
        
        if (result.success) {
            Toast.success(result.message);
            loadEvents(); // Refresh stats
        } else {
            Toast.error(result.error);
        }
    } catch (err) {
        Toast.error('Erro ao gerar snapshot');
    }
}

async function changeStatus(id, status) {
    if (!confirm(`Tem certeza que deseja alterar o status para ${status}?`)) return;
    
    try {
        const res = await fetch('../api/competition-events-api.php', {
            method: 'PUT',
            body: JSON.stringify({ id, status })
        });
        
        if ((await res.json()).success) {
            Toast.success('Status atualizado');
            loadEvents();
        }
    } catch (err) {
        Toast.error('Erro ao atualizar status');
    }
}

async function setActive(id) {
     try {
        const res = await fetch('../api/competition-events-api.php', {
            method: 'PUT',
            body: JSON.stringify({ id, active_flag: true })
        });
        
        if ((await res.json()).success) {
            Toast.success('Evento definido como principal');
            loadEvents();
        }
    } catch (err) {
        Toast.error('Erro ao atualizar');
    }
}

function openCreateModal() { document.getElementById('createModal').classList.add('active'); }
function closeCreateModal() { document.getElementById('createModal').classList.remove('active'); }

loadEvents();
</script>

<?php include '../includes/footer.php'; ?>
