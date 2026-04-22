<?php
require_once '../config/config.php';
require_once '../includes/auth.php';
require_once '../includes/db.php';

requireAdmin();

$pageTitle = 'Modalidades Esportivas';

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<div class="main-content">
    <div class="top-bar">
        <h1 class="top-bar-title">Esportes e Disciplinas</h1>
    </div>
    
    <div class="content-wrapper" style="max-width: 1400px; margin: 0 auto;">
        <!-- Header Elite Hero -->
        <div class="glass-card" style="margin-bottom: 2.5rem; border: none; background: linear-gradient(135deg, rgba(255,255,255,0.05) 0%, rgba(255,255,255,0.02) 100%); display: flex; justify-content: space-between; align-items: center; padding: 2.5rem; box-shadow: 0 20px 40px rgba(0,0,0,0.2);">
            <div>
                <h2 style="font-size: 1.75rem; font-weight: 800; letter-spacing: -0.02em; margin-bottom: 0.5rem; color: #fff;">Gestão de Modalidades</h2>
                <p style="color: var(--text-secondary); font-size: 1rem; max-width: 500px; line-height: 1.5;">Cadastre os esportes da competição e defina as regras de gênero para cada um.</p>
            </div>
            <button class="btn btn-primary" onclick="openModal()" style="height: 54px; padding: 0 1.75rem; border-radius: 14px; font-weight: 600; box-shadow: 0 10px 20px rgba(59, 130, 246, 0.25);">
                <span style="font-size: 1.4rem; margin-right: 8px;">+</span> Nova Modalidade
            </button>
        </div>

        <!-- Tabela Elite -->
        <div class="glass-card" style="padding: 0; overflow: hidden; border-radius: 20px; border: 1px solid rgba(255,255,255,0.05);">
            <div class="table-container">
                <table class="table" id="modalitiesTable" style="margin: 0; border-collapse: separate; border-spacing: 0;">
                    <thead>
                        <tr style="background: rgba(255,255,255,0.02);">
                            <th style="padding: 1.5rem 2rem; width: 400px;">Modalidade Esportiva</th>
                            <th style="padding: 1.5rem 1rem;">Configuração de Gênero</th>
                            <th style="padding: 1.5rem 1rem; text-align: center;">Status</th>
                            <th style="padding: 1.5rem 2rem; text-align: right;">Ações</th>
                        </tr>
                    </thead>
                    <tbody id="modalitiesBody">
                        <tr>
                            <td colspan="4" style="text-align: center; padding: 6rem; color: var(--text-secondary);">
                                <div style="font-size: 2.5rem; margin-bottom: 1rem;">🏃💨</div>
                                Sincronizando modalidades...
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<style>
    .avatar-sport {
        width: 44px;
        height: 44px;
        border-radius: 14px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 800;
        font-size: 0.95rem;
        color: #fff;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    }
    .badge-mixed-true { background: rgba(14, 165, 233, 0.1); color: #38bdf8; border: 1px solid rgba(14, 165, 233, 0.1); }
    .badge-mixed-false { background: rgba(255, 255, 255, 0.05); color: var(--text-muted); border: 1px solid rgba(255,255,255,0.05); }
    
    .badge-pill-elite {
        padding: 6px 14px;
        border-radius: 50px;
        font-size: 0.8rem;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }
    .btn-action-circle {
        width: 36px;
        height: 36px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.2s ease;
        border: 1px solid rgba(255,255,255,0.1);
        background: rgba(255,255,255,0.05);
        color: var(--text-secondary);
    }
    .btn-action-circle:hover { background: var(--primary); color: #fff; border-color: var(--primary); transform: translateY(-2px); box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3); }
    .btn-delete-elite:hover { background: #f87171 !important; color: #fff; border-color: #f87171 !important; }
</style>

<!-- Modal -->
<div class="modal-overlay" id="modalityModal">
    <div class="modal">
        <div class="modal-header">
            <h3 class="modal-title" id="modalTitle">Nova Modalidade</h3>
            <button class="modal-close" onclick="closeModal()">×</button>
        </div>
        <div class="modal-body">
            <form id="modalityForm">
                <input type="hidden" id="modalityId">
                <div class="form-group">
                    <label class="form-label">Nome da Modalidade *</label>
                    <input type="text" id="name" class="form-input" placeholder="Ex: Futsal, Vôlei de Quadra" required>
                </div>
                <div class="form-group" style="background: rgba(255,255,255,0.02); padding: 1rem; border-radius: 12px; border: 1px solid rgba(255,255,255,0.05);">
                    <label style="display: flex; align-items: center; gap: 0.75rem; cursor: pointer; margin: 0;">
                        <input type="checkbox" id="allowsMixed" style="width: 18px; height: 18px; cursor: pointer; accent-color: var(--primary);">
                        <span class="form-label" style="margin: 0; font-size: 0.95rem;">Permitir equipes mistas</span>
                    </label>
                    <small style="color: var(--text-muted); display: block; margin-top: 0.5rem; line-height: 1.4;">Ative se meninos e meninas puderem compor o mesmo time nesta modalidade.</small>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="closeModal()">Cancelar</button>
            <button class="btn btn-primary" onclick="saveModality()">Salvar Modalidade</button>
        </div>
    </div>
</div>

<script>
// Utils Design Elite
function stringToColor(str) {
    let hash = 0;
    for (let i = 0; i < str.length; i++) { hash = str.charCodeAt(i) + ((hash << 5) - hash); }
    let color = '#';
    for (let i = 0; i < 3; i++) {
        let value = (hash >> (i * 8)) & 0xFF;
        color += ('00' + value.toString(16)).substr(-2);
    }
    return color;
}
function getInitials(name) { return name.split(' ').map(n => n[0]).slice(0, 2).join(''); }

async function loadModalities() {
    try {
        const response = await fetch('../api/modalities-api.php');
        const data = await response.json();
        if (data.success) renderTable(data.data);
    } catch (error) { Toast.error('Erro de sincronização'); }
}

function renderTable(modalities) {
    const tbody = document.getElementById('modalitiesBody');
    tbody.innerHTML = '';
    if (modalities.length === 0) {
        tbody.innerHTML = '<tr><td colspan="4" style="text-align: center; padding: 4rem; color: var(--text-muted);">Nenhuma modalidade cadastrada.</td></tr>';
        return;
    }
    modalities.sort((a, b) => a.name.localeCompare(b.name));
    modalities.forEach(mod => {
        const color = stringToColor(mod.name);
        const initials = getInitials(mod.name);
        const row = document.createElement('tr');
        row.style.borderBottom = '1px solid rgba(255,255,255,0.05)';
        row.innerHTML = `
            <td style="padding: 1.25rem 2rem;">
                <div style="display: flex; align-items: center; gap: 1.25rem;">
                    <div class="avatar-sport" style="background: ${color}20; color: ${color}; border: 1px solid ${color}30;">${initials}</div>
                    <span style="font-weight: 600; color: #fff; font-size: 1.05rem;">${mod.name}</span>
                </div>
            </td>
            <td>
                <span class="badge-pill-elite ${mod.allows_mixed == 1 ? 'badge-mixed-true' : 'badge-mixed-false'}">
                    ${mod.allows_mixed == 1 ? '👫 Permite Misto' : '👤 Gênero Único'}
                </span>
            </td>
            <td style="text-align: center;">
                <span class="badge-pill-elite" style="background: rgba(34, 197, 94, 0.1); color: #4ade80;">Ativo</span>
            </td>
            <td style="padding: 1.25rem 2rem; text-align: right;">
                <div style="display: flex; justify-content: flex-end; gap: 0.75rem;">
                    <button class="btn-action-circle" onclick="editModality(${mod.id})" title="Editar">✏️</button>
                    <button class="btn-action-circle btn-delete-elite" onclick="deleteModality(${mod.id})" title="Excluir">🗑️</button>
                </div>
            </td>
        `;
        tbody.appendChild(row);
    });
}

function openModal(id = null) {
    const modal = document.getElementById('modalityModal');
    const form = document.getElementById('modalityForm');
    form.reset();
    if (id) { document.getElementById('modalTitle').textContent = 'Editar Modalidade'; loadModality(id); }
    else { document.getElementById('modalTitle').textContent = 'Nova Modalidade'; document.getElementById('modalityId').value = ''; }
    modal.classList.add('active');
}
function closeModal() { document.getElementById('modalityModal').classList.remove('active'); }

async function loadModality(id) {
    try {
        const response = await fetch('../api/modalities-api.php');
        const data = await response.json();
        if (data.success) {
            const mod = data.data.find(m => m.id == id);
            if (mod) {
                document.getElementById('modalityId').value = mod.id;
                document.getElementById('name').value = mod.name;
                document.getElementById('allowsMixed').checked = mod.allows_mixed == 1;
            }
        }
    } catch (e) {}
}

async function saveModality() {
    const id = document.getElementById('modalityId').value;
    const name = document.getElementById('name').value.trim();
    const allowsMixed = document.getElementById('allowsMixed').checked;
    if (!name) { Toast.error('Preencha o nome'); return; }
    const data = { id: id || undefined, name, allows_mixed: allowsMixed };
    try {
        const response = await fetch('../api/modalities-api.php', {
            method: id ? 'PUT' : 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });
        if ((await response.json()).success) { Toast.success('Operação concluída!'); closeModal(); loadModalities(); }
    } catch (e) { Toast.error('Erro ao salvar'); }
}

async function deleteModality(id) {
    if (!confirm('Excluir esta modalidade?')) return;
    try {
        const response = await fetch(`../api/modalities-api.php?id=${id}`, { method: 'DELETE' });
        if ((await response.json()).success) { Toast.success('Removido!'); loadModalities(); }
    } catch (e) { Toast.error('Erro ao remover'); }
}

document.getElementById('modalityModal').addEventListener('click', (e) => { if (e.target.id === 'modalityModal') closeModal(); });
loadModalities();
</script>

<?php include '../includes/footer.php'; ?>
