<?php
require_once '../config/config.php';
require_once '../includes/auth.php';
require_once '../includes/db.php';

requireAdmin();

$pageTitle = 'Gerenciar Professores';

// Get pending requests count (inactive professors)
$pendingCount = queryOne("SELECT COUNT(*) as count FROM users WHERE role = 'professor' AND is_active = 0 AND secretaria_id = ?", [CURRENT_TENANT_ID])['count'] ?? 0;

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<div class="main-content">
    <div class="top-bar">
        <h1 class="top-bar-title">Corpo Docente</h1>
    </div>
    
    <div class="content-wrapper" style="max-width: 1400px; margin: 0 auto;">
        <!-- Header Elite Hero -->
        <div class="glass-card" style="margin-bottom: 2rem; border: none; background: linear-gradient(135deg, rgba(255,255,255,0.05) 0%, rgba(255,255,255,0.02) 100%); display: flex; justify-content: space-between; align-items: center; padding: 2.5rem; box-shadow: 0 20px 40px rgba(0,0,0,0.2);">
            <div>
                <h2 style="font-size: 1.75rem; font-weight: 800; letter-spacing: -0.02em; margin-bottom: 0.5rem; color: #fff;">Gestão de Professores</h2>
                <p style="color: var(--text-secondary); font-size: 1rem; max-width: 500px; line-height: 1.5;">Administre o acesso dos professores, valide novos cadastros e gerencie vínculos escolares.</p>
            </div>
            <button class="btn btn-primary" onclick="openProfessorModal()" style="height: 54px; padding: 0 1.75rem; border-radius: 14px; font-weight: 600; box-shadow: 0 10px 20px rgba(59, 130, 246, 0.25);">
                <span style="font-size: 1.4rem; margin-right: 8px;">+</span> Novo Professor
            </button>
        </div>

        <!-- Elite Tabs -->
        <div style="display: flex; gap: 2rem; margin-bottom: 2rem; border-bottom: 1px solid rgba(255,255,255,0.05); padding: 0 1rem;">
            <button class="tab-btn-elite active" onclick="switchTab('professors')" id="tab-professors">
                Professores Ativos
            </button>
            <button class="tab-btn-elite" onclick="switchTab('requests')" id="tab-requests" style="position: relative;">
                Aprovações Pendentes
                <?php if ($pendingCount > 0): ?>
                    <span class="count-badge"><?php echo $pendingCount; ?></span>
                <?php endif; ?>
            </button>
        </div>
        
        <!-- Tab: Professores Ativos -->
        <div id="content-professors" class="tab-content">
            <div style="display: flex; gap: 1rem; margin-bottom: 1.5rem;">
                <div style="position: relative; flex: 1; max-width: 400px;">
                    <input type="text" id="searchProfessors" class="form-input" placeholder="Pesquisar por nome ou email..." style="padding-left: 2.5rem; border-radius: 12px; background: rgba(255,255,255,0.03);">
                    <span style="position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); opacity: 0.4;">🔍</span>
                </div>
                <select id="filterSchool" class="form-select" style="width: 250px; border-radius: 12px; background: rgba(255,255,255,0.03);">
                    <option value="">Todas as instituições</option>
                </select>
            </div>
            
            <div class="glass-card" style="padding: 0; overflow: hidden; border-radius: 20px; border: 1px solid rgba(255,255,255,0.05);">
                <div class="table-container">
                    <table class="table" id="professorsTable" style="margin: 0;">
                        <thead>
                            <tr style="background: rgba(255,255,255,0.02);">
                                <th style="padding: 1.5rem 2rem; width: 350px;">Professor</th>
                                <th style="padding: 1.5rem 1rem;">Lotação Escolar</th>
                                <th style="padding: 1.5rem 1rem;">Status</th>
                                <th style="padding: 1.5rem 2rem; text-align: right;">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr><td colspan="4" style="text-align: center; padding: 4rem; color: var(--text-muted);">Sincronizando corpo docente...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <!-- Tab: Aprovações Pendentes (Lista Elite) -->
        <div id="content-requests" class="tab-content" style="display: none;">
            <div class="glass-card" style="padding: 0; overflow: hidden; border-radius: 20px; border: 1px solid rgba(245, 158, 11, 0.1);">
                <div class="table-container">
                    <table class="table" id="requestsTable" style="margin: 0;">
                        <thead>
                            <tr style="background: rgba(245, 158, 11, 0.03);">
                                <th style="padding: 1.5rem 2rem;">Solicitante</th>
                                <th style="padding: 1.5rem 1rem;">Escola Pretendida</th>
                                <th style="padding: 1.5rem 1rem;">Data do Pedido</th>
                                <th style="padding: 1.5rem 2rem; text-align: right;">Decisão</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr><td colspan="4" style="text-align: center; padding: 4rem; color: var(--text-muted);">Verificando solicitações pendentes...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .tab-btn-elite {
        padding: 1.25rem 0.5rem;
        background: none;
        border: none;
        color: var(--text-secondary);
        font-weight: 500;
        cursor: pointer;
        border-bottom: 2px solid transparent;
        transition: all 0.2s ease;
        font-size: 1rem;
    }
    .tab-btn-elite.active {
        color: var(--primary);
        border-bottom-color: var(--primary);
        font-weight: 700;
    }
    .count-badge {
        background: var(--warning);
        color: #000;
        font-size: 0.75rem;
        padding: 2px 8px;
        border-radius: 10px;
        font-weight: 800;
        margin-left: 0.5rem;
    }
    .avatar-prof {
        width: 44px;
        height: 44px;
        border-radius: 14px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: 0.95rem;
        color: #fff;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    }
    .badge-status {
        padding: 5px 12px;
        border-radius: 50px;
        font-size: 0.8rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.02em;
    }
    .badge-status-active { background: rgba(34, 197, 94, 0.1); color: #4ade80; border: 1px solid rgba(34, 197, 94, 0.1); }
    
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
    .btn-action-circle:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,0.2); }
    .btn-edit-prof:hover { background: var(--primary); color: #fff; border-color: var(--primary); }
    .btn-delete-prof:hover { background: #f87171; color: #fff; border-color: #f87171; }
    .btn-approve:hover { background: #22c55e; color: #fff; border-color: #22c55e; }
</style>

<!-- Modal Professor -->
<div class="modal-overlay" id="professorModal">
    <div class="modal">
        <div class="modal-header">
            <h3 class="modal-title" id="modalTitle">Novo Professor</h3>
            <button class="modal-close" onclick="closeProfessorModal()">×</button>
        </div>
        <div class="modal-body">
            <form id="professorForm">
                <input type="hidden" id="professorId">
                <div class="form-group">
                    <label class="form-label">Nome Completo *</label>
                    <input type="text" id="name" class="form-input" required>
                </div>
                <div class="form-row" style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div class="form-group">
                        <label class="form-label">Email *</label>
                        <input type="email" id="email" class="form-input" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">CPF *</label>
                        <input type="text" id="cpf" class="form-input" data-format="cpf" required>
                    </div>
                </div>
                <div class="form-row" style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div class="form-group">
                        <label class="form-label">Telefone</label>
                        <input type="text" id="phone" class="form-input" data-format="phone">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Escola *</label>
                        <select id="schoolId" class="form-select" required></select>
                    </div>
                </div>
                <div class="form-group" id="passwordGroup">
                    <label class="form-label">Senha *</label>
                    <input type="password" id="password" class="form-input" minlength="6">
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="closeProfessorModal()">Cancelar</button>
            <button class="btn btn-primary" onclick="saveProfessor()">Salvar Professor</button>
        </div>
    </div>
</div>

<script>
let currentTab = 'professors';

// Utils Elite
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

function switchTab(tab) {
    currentTab = tab;
    document.querySelectorAll('.tab-btn-elite').forEach(btn => btn.classList.remove('active'));
    document.getElementById(`tab-${tab}`).classList.add('active');
    document.querySelectorAll('.tab-content').forEach(content => content.style.display = 'none');
    document.getElementById(`content-${tab}`).style.display = 'block';
    if (tab === 'professors') loadProfessors(); else loadRequests();
}

async function loadSchools() {
    try {
        const response = await fetch('../api/schools-api.php');
        const data = await response.json();
        if (data.success) {
            const s1 = document.getElementById('schoolId');
            const s2 = document.getElementById('filterSchool');
            s1.innerHTML = '<option value="">Selecione...</option>';
            s2.innerHTML = '<option value="">Todas as instituições</option>';
            data.data.forEach(s => {
                s1.innerHTML += `<option value="${s.id}">${s.name}</option>`;
                s2.innerHTML += `<option value="${s.id}">${s.name}</option>`;
            });
        }
    } catch (e) {}
}

async function loadProfessors() {
    try {
        const search = document.getElementById('searchProfessors').value;
        const schoolFilter = document.getElementById('filterSchool').value;
        const res = await fetch('../api/professors-api.php?action=list');
        const data = await res.json();
        if (data.success) {
            let profs = data.data;
            if (search) profs = profs.filter(p => p.name.toLowerCase().includes(search.toLowerCase()) || p.email.toLowerCase().includes(search.toLowerCase()));
            if (schoolFilter) profs = profs.filter(p => p.school_id == schoolFilter);
            renderProfessorsTable(profs);
        }
    } catch (e) { Toast.error('Erro de sincronização'); }
}

function renderProfessorsTable(professors) {
    const tbody = document.querySelector('#professorsTable tbody');
    tbody.innerHTML = '';
    if (professors.length === 0) {
        tbody.innerHTML = '<tr><td colspan="4" style="text-align: center; padding: 4rem; color: var(--text-muted);">Nenhum docente ativo.</td></tr>';
        return;
    }
    professors.forEach(prof => {
        const color = stringToColor(prof.name);
        const initials = getInitials(prof.name);
        const row = document.createElement('tr');
        row.style.borderBottom = '1px solid rgba(255,255,255,0.05)';
        row.innerHTML = `
            <td style="padding: 1.25rem 2rem;">
                <div style="display: flex; align-items: center; gap: 1rem;">
                    <div class="avatar-prof" style="background: ${color}20; color: ${color}; border: 1px solid ${color}30;">${initials}</div>
                    <div class="user-meta">
                        <span style="font-weight: 600; color: #fff;">${prof.name}</span>
                        <span style="font-size: 0.85rem; color: var(--text-muted);">${prof.email}</span>
                    </div>
                </div>
            </td>
            <td><span style="font-size: 0.95rem; color: var(--text-secondary);">${prof.school_name || '-'}</span></td>
            <td><span class="badge-status badge-status-active">● Ativo</span></td>
            <td style="padding-right: 2rem; text-align: right;">
                <div style="display: flex; justify-content: flex-end; gap: 0.5rem;">
                    <button class="btn-action-circle btn-edit-prof" onclick="editProfessor(${prof.id})">✏️</button>
                    <button class="btn-action-circle btn-delete-prof" onclick="toggleProfessorStatus(${prof.id}, 1)">🚫</button>
                </div>
            </td>
        `;
        tbody.appendChild(row);
    });
}

async function loadRequests() {
    try {
        const res = await fetch('../api/professors-api.php?action=requests');
        const data = await res.json();
        if (data.success) renderRequestsTable(data.data);
    } catch (e) {}
}

function renderRequestsTable(requests) {
    const tbody = document.querySelector('#requestsTable tbody');
    tbody.innerHTML = '';
    if (requests.length === 0) {
        tbody.innerHTML = '<tr><td colspan="4" style="text-align: center; padding: 4rem; color: var(--text-muted);">Sem registros pendentes.</td></tr>';
        return;
    }
    requests.forEach(req => {
        const color = stringToColor(req.name);
        const initials = getInitials(req.name);
        const row = document.createElement('tr');
        row.style.borderBottom = '1px solid rgba(255,255,255,0.05)';
        row.innerHTML = `
            <td style="padding: 1.25rem 2rem;">
                <div style="display: flex; align-items: center; gap: 1rem;">
                    <div class="avatar-prof" style="background: ${color}20; color: ${color}; border: 1px solid ${color}30;">${initials}</div>
                    <div class="user-meta">
                        <span style="font-weight: 600; color: #fff;">${req.name}</span>
                        <span style="font-size: 0.85rem; color: var(--text-muted);">CPF: ${req.cpf}</span>
                    </div>
                </div>
            </td>
            <td><span style="font-size: 0.95rem; color: var(--text-secondary);">${req.school_name}</span></td>
            <td><span style="font-size: 0.85rem; color: var(--text-muted);">${new Date(req.created_at).toLocaleDateString('pt-BR')}</span></td>
            <td style="padding-right: 2rem; text-align: right;">
                <div style="display: flex; justify-content: flex-end; gap: 0.75rem;">
                    <button class="btn-action-circle btn-approve" onclick="approveRequest(${req.id})" title="Aprovar">✅</button>
                    <button class="btn-action-circle btn-delete-prof" onclick="rejectRequest(${req.id})" title="Rejeitar">❌</button>
                </div>
            </td>
        `;
        tbody.appendChild(row);
    });
}

function openProfessorModal(id = null) {
    const modal = document.getElementById('professorModal');
    const form = document.getElementById('professorForm');
    form.reset();
    if (id) {
        document.getElementById('modalTitle').textContent = 'Editar Professor';
        loadProfessor(id);
    } else {
        document.getElementById('modalTitle').textContent = 'Novo Professor';
        document.getElementById('professorId').value = '';
    }
    modal.classList.add('active');
}
function editProfessor(id) { openProfessorModal(id); }
function closeProfessorModal() { document.getElementById('professorModal').classList.remove('active'); }

async function loadProfessor(id) {
    try {
        const res = await fetch(`../api/professors-api.php?action=get&id=${id}`);
        const data = await res.json();
        if (data.success && data.data) {
            const p = data.data;
            document.getElementById('professorId').value = p.id;
            document.getElementById('name').value = p.name;
            document.getElementById('email').value = p.email;
            document.getElementById('cpf').value = p.cpf;
            document.getElementById('phone').value = p.phone || '';
            document.getElementById('schoolId').value = p.school_id;
        }
    } catch (e) {}
}

async function saveProfessor() {
    const id = document.getElementById('professorId').value;
    const data = {
        id: id || undefined,
        name: document.getElementById('name').value,
        email: document.getElementById('email').value,
        cpf: document.getElementById('cpf').value,
        phone: document.getElementById('phone').value,
        school_id: document.getElementById('schoolId').value,
        password: document.getElementById('password').value || undefined
    };
    try {
        const res = await fetch('../api/professors-api.php', {
            method: id ? 'PUT' : 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });
        if ((await res.json()).success) {
            Toast.success('Salvo!');
            closeProfessorModal();
            loadProfessors();
        }
    } catch (e) {}
}

async function approveRequest(id) {
    const res = await fetch('../api/professors-api.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'approve_request', request_id: id })
    });
    if ((await res.json()).success) { loadRequests(); loadProfessors(); Toast.success('Aprovado!'); }
}

async function rejectRequest(id) {
    if (!confirm('Rejeitar cadastro?')) return;
    const res = await fetch('../api/professors-api.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'reject_request', request_id: id })
    });
    if ((await res.json()).success) { loadRequests(); Toast.success('Rejeitado'); }
}

async function toggleProfessorStatus(id, currentStatus) {
    const res = await fetch('../api/professors-api.php', {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id, is_active: !currentStatus })
    });
    if ((await res.json()).success) loadProfessors();
}

document.getElementById('searchProfessors').addEventListener('input', loadProfessors);
document.getElementById('filterSchool').addEventListener('change', loadProfessors);
loadSchools();
loadProfessors();
</script>

<?php include '../includes/footer.php'; ?>
