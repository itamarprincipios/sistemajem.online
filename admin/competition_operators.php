<?php
require_once '../config/config.php';
require_once '../includes/auth.php';
require_once '../includes/db.php';

requireAdmin();

$pageTitle = 'Gestão de Operadores';
include '../includes/header.php';
include '../includes/sidebar.php';
?>

<div class="main-content">
    <div class="top-bar">
        <h1 class="top-bar-title">Operadores de Jogos</h1>
    </div>
    
    <div class="content-wrapper" style="max-width: 1400px; margin: 0 auto;">
        <!-- Header Hero - Elite UI -->
        <div class="glass-card" style="margin-bottom: 2.5rem; border: none; background: linear-gradient(135deg, rgba(255,255,255,0.05) 0%, rgba(255,255,255,0.02) 100%); display: flex; justify-content: space-between; align-items: center; padding: 2.5rem; box-shadow: 0 20px 40px rgba(0,0,0,0.2);">
            <div>
                <h2 style="font-size: 1.75rem; font-weight: 800; letter-spacing: -0.02em; margin-bottom: 0.5rem; color: #fff;">Equipe de Campo</h2>
                <p style="color: var(--text-secondary); font-size: 1rem; max-width: 500px; line-height: 1.5;">Gerencie as pessoas responsáveis pelo lançamento de resultados e gestão das súmulas em tempo real.</p>
            </div>
            <button class="btn btn-primary" onclick="openCreateModal()" style="height: 54px; padding: 0 1.75rem; border-radius: 14px; font-size: 1rem; box-shadow: 0 10px 20px rgba(59, 130, 246, 0.25);">
                <span style="font-size: 1.4rem; margin-right: 8px;">+</span> Novo Operador
            </button>
        </div>

        <!-- Tabela Elite -->
        <div class="glass-card" style="padding: 0; overflow: hidden; border-radius: 20px; border: 1px solid rgba(255,255,255,0.05);">
            <div class="table-container">
                <table class="table" style="margin: 0; border-collapse: separate; border-spacing: 0;">
                    <thead>
                        <tr style="background: rgba(255,255,255,0.02);">
                            <th style="padding: 1.5rem 2rem; width: 350px;">Operador</th>
                            <th style="padding: 1.5rem 1rem;">Evento Atribuído</th>
                            <th style="padding: 1.5rem 1rem;">Permissões de Acesso</th>
                            <th style="padding: 1.5rem 2rem; text-align: right;">Ações</th>
                        </tr>
                    </thead>
                    <tbody id="operatorsTable">
                        <tr><td colspan="4" style="text-align: center; padding: 6rem; color: var(--text-secondary);">
                            <div style="font-size: 2.5rem; margin-bottom: 1rem;">🧤</div>
                            Sincronizando equipe...
                        </td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<style>
    .avatar-circle {
        width: 42px;
        height: 42px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: 0.9rem;
        color: #fff;
        text-transform: uppercase;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    }
    .user-meta {
        display: flex;
        flex-direction: column;
        gap: 2px;
    }
    .badge-pill {
        padding: 6px 14px;
        border-radius: 50px;
        font-size: 0.8rem;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        letter-spacing: 0.01em;
        transition: all 0.2s ease;
    }
    .badge-pill:hover {
        filter: brightness(1.1);
        transform: translateY(-1px);
    }
    .badge-event-elite { background: rgba(14, 165, 233, 0.12); color: #38bdf8; border: 1px solid rgba(14, 165, 233, 0.1); }
    .badge-modality-elite { background: rgba(139, 92, 246, 0.12); color: #a78bfa; border: 1px solid rgba(139, 92, 246, 0.1); }
    .badge-venue-elite { background: rgba(245, 158, 11, 0.12); color: #fbbf24; border: 1px solid rgba(245, 158, 11, 0.1); }
    
    .row-hover:hover {
        background: rgba(255,255,255,0.035) !important;
    }
    .btn-delete-circle {
        width: 38px;
        height: 38px;
        border-radius: 10px;
        background: rgba(239, 68, 68, 0.08);
        border: 1px solid rgba(239, 68, 68, 0.15);
        color: #f87171;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.2s ease;
    }
    .btn-delete-circle:hover {
        background: #ef4444;
        color: #fff;
        transform: scale(1.05);
        box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
    }
</style>

<!-- Modal Create -->
<div class="modal-overlay" id="createModal">
    <div class="modal">
        <div class="modal-header">
            <h3 class="modal-title">Novo Operador</h3>
            <button class="modal-close" onclick="closeCreateModal()">×</button>
        </div>
        <div class="modal-body">
            <form id="createForm" onsubmit="handleCreate(event)">
                <div class="form-group">
                    <label class="form-label">Nome Completo</label>
                    <input type="text" name="name" class="form-input" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Email (Login)</label>
                    <input type="email" name="email" class="form-input" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Senha Inicial</label>
                    <input type="password" name="password" class="form-input" required minlength="6">
                </div>
                
                <hr style="border-color: var(--border); margin: 1.5rem 0;">
                
                <div class="form-group">
                    <label class="form-label">Evento</label>
                    <select name="competition_event_id" id="eventSelect" class="form-select" required>
                        <option value="">Carregando...</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Restringir Modalidade (Opcional)</label>
                    <select name="assigned_modality_id" id="modalitySelect" class="form-select">
                        <option value="">Todas as modalidades</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Restringir Local (Opcional)</label>
                    <input type="text" name="assigned_venue" class="form-input" placeholder="Ex: Ginásio Principal">
                </div>

                <button type="submit" class="btn btn-primary" style="width: 100%;">Cadastrar Operador</button>
            </form>
        </div>
    </div>
</div>

<script>
// Utils para design Elite
function stringToColor(str) {
    let hash = 0;
    for (let i = 0; i < str.length; i++) {
        hash = str.charCodeAt(i) + ((hash << 5) - hash);
    }
    let color = '#';
    for (let i = 0; i < 3; i++) {
        let value = (hash >> (i * 8)) & 0xFF;
        color += ('00' + value.toString(16)).substr(-2);
    }
    return color;
}

function getInitials(name) {
    return name.split(' ').map(n => n[0]).slice(0, 2).join('');
}

async function loadData() {
    try {
        const [resOp, resEv, resMod] = await Promise.all([
            fetch('../api/competition-operators-api.php?action=list'),
            fetch('../api/competition-operators-api.php?action=events'),
            fetch('../api/competition-operators-api.php?action=modalities')
        ]);
        
        const dataOp = await resOp.json();
        const dataEv = await resEv.json();
        const dataMod = await resMod.json();
        
        renderTable(dataOp.data);
        populateSelects(dataEv.data, dataMod.data);
    } catch (e) {
        console.error(e);
        Toast.error('Erro de conexão crítica');
    }
}

function renderTable(operators) {
    const tbody = document.getElementById('operatorsTable');
    tbody.innerHTML = '';
    
    if (!operators || operators.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="4" style="text-align: center; padding: 6rem 2rem;">
                    <div style="font-size: 3rem; margin-bottom: 1rem; opacity: 0.5;">🧤</div>
                    <div style="font-weight: 500; font-size: 1.1rem; color: var(--text-secondary);">Equipe ainda não formada</div>
                    <div style="font-size: 0.9rem; color: var(--text-muted); margin-top: 0.5rem;">Os operadores cadastrados aparecerão aqui para gestão rápida.</div>
                </td>
            </tr>
        `;
        return;
    }
    
    operators.forEach(op => {
        const color = stringToColor(op.name);
        const initials = getInitials(op.name);
        const row = document.createElement('tr');
        row.className = 'row-hover';
        row.style.borderBottom = '1px solid rgba(255,255,255,0.05)';
        
        row.innerHTML = `
            <td style="padding: 1.25rem 2rem;">
                <div style="display: flex; align-items: center; gap: 1rem;">
                    <div class="avatar-circle" style="background: ${color}20; color: ${color}; border: 1px solid ${color}30;">${initials}</div>
                    <div class="user-meta">
                        <span style="font-weight: 600; color: #fff; font-size: 1rem;">${op.name}</span>
                        <span style="font-size: 0.85rem; color: var(--text-muted);">${op.email}</span>
                    </div>
                </div>
            </td>
            <td style="padding: 1.25rem 1rem;">
                <span class="badge-pill badge-event-elite">⭐ ${op.event_name}</span>
            </td>
            <td style="padding: 1.25rem 1rem;">
                <div style="display: flex; flex-wrap: wrap; gap: 0.65rem;">
                    ${op.modality_name ? \`<span class="badge-pill badge-modality-elite">⚽ \${op.modality_name}</span>\` : '<span class="badge-pill" style="background: rgba(255,255,255,0.05); color: var(--text-muted);">Acesso Global</span>'}
                    ${op.assigned_venue ? \`<span class="badge-pill badge-venue-elite">📍 \${op.assigned_venue}</span>\` : ''}
                </div>
            </td>
            <td style="padding: 1.25rem 2rem; text-align: right;">
                <div style="display: flex; justify-content: flex-end;">
                    <button class="btn-delete-circle" onclick="deleteOperator(\${op.id})" title="Remover operador">
                        <span style="font-size: 1.1rem;">🗑️</span>
                    </button>
                </div>
            </td>
        `;
        tbody.appendChild(row);
    });
}

function populateSelects(events, modalities) {
    const eventSelect = document.getElementById('eventSelect');
    eventSelect.innerHTML = '<option value="">Selecione o Evento</option>';
    events.forEach(ev => {
        eventSelect.innerHTML += `<option value="${ev.id}">${ev.name}</option>`;
    });

    const modSelect = document.getElementById('modalitySelect');
    modSelect.innerHTML = '<option value="">Todas as modalidades</option>';
    modalities.forEach(mod => {
        modSelect.innerHTML += `<option value="${mod.id}">${mod.name}</option>`;
    });
}

async function handleCreate(e) {
    e.preventDefault();
    const formData = new FormData(e.target);
    const data = Object.fromEntries(formData);
    
    try {
        const res = await fetch('../api/competition-operators-api.php?action=create', {
            method: 'POST',
            body: JSON.stringify(data)
        });
        const result = await res.json();
        
        if (result.success) {
            Toast.success('Operador cadastrado!');
            closeCreateModal();
            loadData();
            e.target.reset();
        } else {
            Toast.error(result.error);
        }
    } catch (err) {
        Toast.error('Erro ao cadastrar');
    }
}

async function deleteOperator(id) {
    if (!confirm('Remover acesso deste operador?')) return;
    try {
        const delRes = await fetch('../api/competition-operators-api.php?id=' + id, { method: 'DELETE' });
        
        if ((await delRes.json()).success) {
            Toast.success('Operador removido');
            loadData();
        }
    } catch (e) {
        Toast.error('Erro ao remover');
    }
}

function openCreateModal() { document.getElementById('createModal').classList.add('active'); }
function closeCreateModal() { document.getElementById('createModal').classList.remove('active'); }

loadData();
</script>

<?php include '../includes/footer.php'; ?>
