<?php
require_once '../config/config.php';
require_once '../includes/auth.php';
require_once '../includes/db.php';

requireSuperAdmin();

$pageTitle = 'Gerenciar Secretarias';

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<div class="main-content">
    <div class="top-bar">
        <h1 class="top-bar-title">Secretarias de Educação</h1>
    </div>
    
    <div class="content-wrapper">
        <div style="display: flex; justify-content: flex-end; margin-bottom: 2rem;">
            <button class="btn btn-primary" onclick="openModal()">
                <span>➕</span>
                <span>Nova Secretaria</span>
            </button>
        </div>
        
        <div class="glass-card">
            <div class="table-container">
                <table class="table" id="secretariasTable">
                    <thead>
                        <tr>
                            <th>Nome</th>
                            <th>Slug (URL)</th>
                            <th>Escolas</th>
                            <th>Admins</th>
                            <th>Status</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr><td colspan="6" style="text-align: center; padding: 2rem;">Carregando...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal -->
<div class="modal-overlay" id="secretariaModal">
    <div class="modal">
        <div class="modal-header">
            <h3 class="modal-title" id="modalTitle">Nova Secretaria</h3>
            <button class="modal-close" onclick="closeModal()">×</button>
        </div>
        <div class="modal-body">
            <form id="secretariaForm">
                <input type="hidden" id="secretariaId">
                
                <div class="form-group">
                    <label class="form-label">Nome da Secretaria *</label>
                    <input type="text" id="nome" class="form-input" required placeholder="Ex: Secretaria de Boa Vista">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Slug da URL * (Minúsculo, sem espaços)</label>
                    <input type="text" id="slug" class="form-input" required placeholder="ex: boavista">
                    <small style="color: var(--text-muted)">A URL será: sistemajem.online/<b>slug</b>/</small>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Email de Contato (Login do Admin) *</label>
                    <input type="email" id="email" class="form-input" required placeholder="admin@cidade.com">
                </div>

                <div class="form-group">
                    <label class="form-label">Senha do Administrador <span id="pwdLabel">(Obrigatória no cadastro)</span></label>
                    <input type="password" id="password" class="form-input" placeholder="••••••••">
                </div>

                <div class="form-group" id="statusGroup" style="display: none;">
                    <label class="form-label">Status</label>
                    <select id="is_active" class="form-input">
                        <option value="1">Ativa</option>
                        <option value="0">Inativa</option>
                    </select>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="closeModal()">Cancelar</button>
            <button class="btn btn-primary" onclick="saveSecretaria()">Salvar</button>
        </div>
    </div>
</div>

<script>
async function loadSecretarias() {
    try {
        const response = await fetch(`../api/superadmin-api.php?action=list`);
        const data = await response.json();
        
        if (data.success) {
            renderTable(data.data);
        }
    } catch (error) {
        console.error('Error:', error);
    }
}

function renderTable(data) {
    const tbody = document.querySelector('#secretariasTable tbody');
    tbody.innerHTML = '';
    
    data.forEach(s => {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td><b>${s.nome}</b></td>
            <td><code>/${s.slug}/</code></td>
            <td>${s.school_count}</td>
            <td>${s.admin_count}</td>
            <td><span class="badge ${s.is_active == 1 ? 'badge-success' : 'badge-danger'}">${s.is_active == 1 ? 'Ativa' : 'Inativa'}</span></td>
            <td>
                <button class="btn btn-sm btn-secondary" onclick="editSecretaria(${s.id})">Editar</button>
            </td>
        `;
        tbody.appendChild(row);
    });
}

function openModal(id = null) {
    const modal = document.getElementById('secretariaModal');
    const form = document.getElementById('secretariaForm');
    form.reset();
    
    if (id) {
        document.getElementById('modalTitle').textContent = 'Editar Secretaria';
        document.getElementById('statusGroup').style.display = 'block';
        document.getElementById('pwdLabel').textContent = '(Deixe em branco para não alterar)';
        loadSecretaria(id);
    } else {
        document.getElementById('modalTitle').textContent = 'Nova Secretaria';
        document.getElementById('secretariaId').value = '';
        document.getElementById('statusGroup').style.display = 'none';
        document.getElementById('pwdLabel').textContent = '(Obrigatória)';
    }
    
    modal.classList.add('active');
}

function closeModal() {
    document.getElementById('secretariaModal').classList.remove('active');
}

async function loadSecretaria(id) {
    const response = await fetch(`../api/superadmin-api.php?action=get&id=${id}`);
    const data = await response.json();
    if (data.success) {
        const s = data.data;
        document.getElementById('secretariaId').value = s.id;
        document.getElementById('nome').value = s.nome;
        document.getElementById('slug').value = s.slug;
        document.getElementById('email').value = s.email || '';
        document.getElementById('is_active').value = s.is_active;
    }
}

function editSecretaria(id) {
    openModal(id);
}

async function saveSecretaria() {
    const id = document.getElementById('secretariaId').value;
    const password = document.getElementById('password').value;

    if (!id && !password) {
        Toast.error('A senha é obrigatória para novas secretarias');
        return;
    }

    const data = {
        id: id || undefined,
        nome: document.getElementById('nome').value,
        slug: document.getElementById('slug').value,
        email: document.getElementById('email').value,
        password: password || undefined,
        is_active: document.getElementById('is_active').value == "1"
    };
    
    const response = await fetch(`../api/superadmin-api.php`, {
        method: id ? 'PUT' : 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
    });
    
    const result = await response.json();
    if (result.success) {
        Toast.success('Secretaria salva com sucesso!');
        closeModal();
        loadSecretarias();
    } else {
        Toast.error(result.error || 'Erro ao salvar');
    }
}

loadSecretarias();
</script>

<?php include '../includes/footer.php'; ?>
