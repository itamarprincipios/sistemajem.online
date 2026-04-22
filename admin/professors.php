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
        <h1 class="top-bar-title">Professores</h1>
        <div class="user-menu">
            <div class="user-info">
                <div class="user-name"><?php echo htmlspecialchars(getCurrentUserName()); ?></div>
                <div class="user-role">Administrador</div>
            </div>
        </div>
    </div>
    
    <div class="content-wrapper">
        <!-- Tabs -->
        <div style="display: flex; gap: 1rem; margin-bottom: 2rem; border-bottom: 2px solid var(--border);">
            <button 
                class="tab-btn active" 
                onclick="switchTab('professors')"
                id="tab-professors"
                style="padding: 1rem 1.5rem; background: none; border: none; color: var(--text-secondary); cursor: pointer; border-bottom: 2px solid transparent; margin-bottom: -2px; transition: all 0.2s;"
            >
                Professores Ativos
            </button>
            <button 
                class="tab-btn" 
                onclick="switchTab('requests')"
                id="tab-requests"
                style="padding: 1rem 1.5rem; background: none; border: none; color: var(--text-secondary); cursor: pointer; border-bottom: 2px solid transparent; margin-bottom: -2px; transition: all 0.2s; position: relative;"
            >
                Aprovações Pendentes
                <?php if ($pendingCount > 0): ?>
                    <span class="badge badge-warning" style="margin-left: 0.5rem;"><?php echo $pendingCount; ?></span>
                <?php endif; ?>
            </button>
        </div>
        
        <!-- Tab: Professores Ativos -->
        <div id="content-professors" class="tab-content">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; flex-wrap: wrap; gap: 1rem;">
                <div style="display: flex; gap: 1rem; flex-wrap: wrap;">
                    <input 
                        type="text" 
                        id="searchProfessors" 
                        class="form-input" 
                        placeholder="Buscar professor..."
                        style="width: 300px;"
                    >
                    <select id="filterSchool" class="form-select" style="width: 200px;">
                        <option value="">Todas as escolas</option>
                    </select>
                </div>
                <button class="btn btn-primary" onclick="openProfessorModal()">
                    <span>➕</span>
                    <span>Novo Professor</span>
                </button>
            </div>
            
            <div class="glass-card">
                <div class="table-container">
                    <table class="table" id="professorsTable">
                        <thead>
                            <tr>
                                <th>Nome</th>
                                <th>Email</th>
                                <th>Escola</th>
                                <th>Status</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr><td colspan="5" style="text-align: center; padding: 2rem;">Carregando...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <!-- Tab: Aprovações Pendentes -->
        <div id="content-requests" class="tab-content" style="display: none;">
            <div class="glass-card">
                <h3 style="margin-bottom: 1.5rem;">Professores Aguardando Aprovação</h3>
                <div class="table-container">
                    <table class="table" id="requestsTable">
                        <thead>
                            <tr>
                                <th>Nome</th>
                                <th>Email</th>
                                <th>CPF</th>
                                <th>Telefone</th>
                                <th>Escola</th>
                                <th>Data Cadastro</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr><td colspan="7" style="text-align: center; padding: 2rem;">Carregando...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

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
                        <select id="schoolId" class="form-select" required>
                            <option value="">Selecione...</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-group" id="passwordGroup">
                    <label class="form-label">Senha *</label>
                    <input type="password" id="password" class="form-input" minlength="6">
                    <small style="color: var(--text-secondary); font-size: 0.875rem;">
                        Mínimo 6 caracteres
                    </small>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="closeProfessorModal()">Cancelar</button>
            <button class="btn btn-primary" onclick="saveProfessor()">Salvar</button>
        </div>
    </div>
</div>

<style>
.tab-btn.active {
    color: var(--primary) !important;
    border-bottom-color: var(--primary) !important;
    font-weight: 600;
}
</style>

<script>
let currentTab = 'professors';

// Switch tabs
function switchTab(tab) {
    currentTab = tab;
    
    // Update tab buttons
    document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
    document.getElementById(`tab-${tab}`).classList.add('active');
    
    // Update content
    document.querySelectorAll('.tab-content').forEach(content => content.style.display = 'none');
    document.getElementById(`content-${tab}`).style.display = 'block';
    
    // Load data
    if (tab === 'professors') {
        loadProfessors();
    } else {
        loadRequests();
    }
}

// Load schools for dropdowns
async function loadSchools() {
    try {
        const response = await fetch('../api/schools-api.php');
        const data = await response.json();
        
        if (data.success) {
            const schoolSelect = document.getElementById('schoolId');
            const filterSelect = document.getElementById('filterSchool');
            
            schoolSelect.innerHTML = '<option value="">Selecione...</option>';
            filterSelect.innerHTML = '<option value="">Todas as escolas</option>';
            
            data.data.forEach(school => {
                schoolSelect.innerHTML += `<option value="${school.id}">${school.name}</option>`;
                filterSelect.innerHTML += `<option value="${school.id}">${school.name}</option>`;
            });
        }
    } catch (error) {
        console.error('Error:', error);
    }
}

// Load professors
async function loadProfessors() {
    try {
        const search = document.getElementById('searchProfessors').value;
        const schoolFilter = document.getElementById('filterSchool').value;
        
        const response = await fetch('../api/professors-api.php?action=list');
        const data = await response.json();
        
        if (data.success) {
            let professors = data.data;
            
            // Apply filters
            if (search) {
                professors = professors.filter(p => 
                    p.name.toLowerCase().includes(search.toLowerCase()) ||
                    p.email.toLowerCase().includes(search.toLowerCase())
                );
            }
            
            if (schoolFilter) {
                professors = professors.filter(p => p.school_id == schoolFilter);
            }
            
            renderProfessorsTable(professors);
        }
    } catch (error) {
        console.error('Error:', error);
        Toast.error('Erro ao carregar professores');
    }
}

// Render professors table
function renderProfessorsTable(professors) {
    const tbody = document.querySelector('#professorsTable tbody');
    tbody.innerHTML = '';
    
    if (professors.length === 0) {
        tbody.innerHTML = '<tr><td colspan="5" style="text-align: center; padding: 2rem; color: var(--text-secondary);">Nenhum professor ativo encontrado</td></tr>';
        return;
    }
    
    professors.forEach(prof => {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>${prof.name}</td>
            <td>${prof.email}</td>
            <td>${prof.school_name || '-'}</td>
            <td><span class="badge badge-success">Ativo</span></td>
            <td>
                <button class="btn btn-sm btn-secondary" onclick="editProfessor(${prof.id})" style="margin-right: 0.5rem;">Editar</button>
                <button class="btn btn-sm btn-danger" onclick="toggleProfessorStatus(${prof.id}, 1)">Desativar</button>
            </td>
        `;
        tbody.appendChild(row);
    });
}

// Load requests (inactive professors)
async function loadRequests() {
    try {
        const response = await fetch('../api/professors-api.php?action=requests');
        const data = await response.json();
        
        if (data.success) {
            renderRequestsTable(data.data);
        }
    } catch (error) {
        console.error('Error:', error);
        Toast.error('Erro ao carregar solicitações');
    }
}

// Render requests table
function renderRequestsTable(requests) {
    const tbody = document.querySelector('#requestsTable tbody');
    tbody.innerHTML = '';
    
    if (requests.length === 0) {
        tbody.innerHTML = '<tr><td colspan="7" style="text-align: center; padding: 2rem; color: var(--text-secondary);">Nenhuma solicitação pendente</td></tr>';
        return;
    }
    
    requests.forEach(req => {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>${req.name}</td>
            <td>${req.email}</td>
            <td>${req.cpf}</td>
            <td>${req.phone || '-'}</td>
            <td>${req.school_name}</td>
            <td>${new Date(req.created_at).toLocaleDateString('pt-BR')}</td>
            <td>
                <button class="btn btn-sm btn-success" onclick="approveRequest(${req.id})" style="margin-right: 0.5rem;">Aprovar</button>
                <button class="btn btn-sm btn-danger" onclick="rejectRequest(${req.id})">Rejeitar</button>
            </td>
        `;
        tbody.appendChild(row);
    });
}

// Open professor modal
function openProfessorModal(id = null) {
    const modal = document.getElementById('professorModal');
    const form = document.getElementById('professorForm');
    form.reset();
    
    if (id) {
        document.getElementById('modalTitle').textContent = 'Editar Professor';
        document.getElementById('passwordGroup').style.display = 'block'; // Allow password change
        document.getElementById('password').required = false;
        document.getElementById('password').placeholder = 'Deixe em branco para manter a atual';
        loadProfessor(id);
    } else {
        document.getElementById('modalTitle').textContent = 'Novo Professor';
        document.getElementById('passwordGroup').style.display = 'block';
        document.getElementById('password').required = true;
        document.getElementById('password').placeholder = 'Mínimo 6 caracteres';
        document.getElementById('professorId').value = '';
    }
    
    modal.classList.add('active');
}

// Edit professor wrapper
function editProfessor(id) {
    openProfessorModal(id);
}

// Load professor data
async function loadProfessor(id) {
    try {
        const response = await fetch(`../api/professors-api.php?action=get&id=${id}`);
        const data = await response.json();
        
        if (data.success && data.data) {
            const prof = data.data;
            document.getElementById('professorId').value = prof.id;
            document.getElementById('name').value = prof.name;
            document.getElementById('email').value = prof.email;
            document.getElementById('cpf').value = prof.cpf;
            document.getElementById('phone').value = prof.phone || '';
            document.getElementById('schoolId').value = prof.school_id;
        }
    } catch (error) {
        console.error('Error:', error);
        Toast.error('Erro ao carregar dados do professor');
    }
}

function closeProfessorModal() {
    document.getElementById('professorModal').classList.remove('active');
}

// Save professor
async function saveProfessor() {
    const id = document.getElementById('professorId').value;
    const name = document.getElementById('name').value.trim();
    const email = document.getElementById('email').value.trim();
    const cpf = document.getElementById('cpf').value.trim();
    const schoolId = document.getElementById('schoolId').value;
    const password = document.getElementById('password').value;
    
    if (!name || !email || !cpf || !schoolId) {
        Toast.error('Preencha todos os campos obrigatórios');
        return;
    }
    
    if (!Validation.cpf(cpf)) {
        Toast.error('CPF inválido');
        return;
    }
    
    // Password validation: required for new, optional for edit
    if (!id && (!password || password.length < 6)) {
        Toast.error('A senha deve ter no mínimo 6 caracteres');
        return;
    }
    
    if (id && password && password.length < 6) {
        Toast.error('A senha deve ter no mínimo 6 caracteres');
        return;
    }
    
    const data = {
        id: id || undefined,
        name, email, cpf,
        phone: document.getElementById('phone').value.trim(),
        school_id: schoolId,
        password: password || undefined
    };
    
    try {
        const response = await fetch('../api/professors-api.php', {
            method: id ? 'PUT' : 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });
        
        const result = await response.json();
        
        if (result.success) {
            Toast.success(id ? 'Professor atualizado com sucesso!' : 'Professor cadastrado com sucesso!');
            closeProfessorModal();
            loadProfessors();
        } else {
            Toast.error(result.error || 'Erro ao salvar professor');
        }
    } catch (error) {
        console.error('Error:', error);
        Toast.error('Erro ao salvar professor');
    }
}

// Approve request
async function approveRequest(id) {
    try {
        const response = await fetch('../api/professors-api.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'approve_request', request_id: id })
        });
        
        const result = await response.json();
        
        if (result.success) {
            Toast.success('Professor aprovado com sucesso!');
            loadRequests();
            loadProfessors();
        } else {
            Toast.error(result.error || 'Erro ao aprovar solicitação');
        }
    } catch (error) {
        console.error('Error:', error);
        Toast.error('Erro ao aprovar solicitação');
    }
}

// Reject request
async function rejectRequest(id) {
    if (!confirm('Tem certeza que deseja rejeitar este cadastro? O usuário será excluído.')) return;
    
    try {
        const response = await fetch('../api/professors-api.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'reject_request', request_id: id })
        });
        
        const result = await response.json();
        
        if (result.success) {
            Toast.success('Solicitação rejeitada');
            loadRequests();
        } else {
            Toast.error(result.error || 'Erro ao rejeitar solicitação');
        }
    } catch (error) {
        console.error('Error:', error);
        Toast.error('Erro ao rejeitar solicitação');
    }
}

// Toggle professor status
async function toggleProfessorStatus(id, currentStatus) {
    const action = currentStatus ? 'desativar' : 'ativar';
    if (!confirm(`Tem certeza que deseja ${action} este professor?`)) return;
    
    try {
        const response = await fetch('../api/professors-api.php', {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id, is_active: !currentStatus })
        });
        
        const result = await response.json();
        
        if (result.success) {
            Toast.success(`Professor ${action}do com sucesso!`);
            loadProfessors();
        } else {
            Toast.error(result.error || `Erro ao ${action} professor`);
        }
    } catch (error) {
        console.error('Error:', error);
        Toast.error(`Erro ao ${action} professor`);
    }
}

// Search and filter
document.getElementById('searchProfessors').addEventListener('input', loadProfessors);
document.getElementById('filterSchool').addEventListener('change', loadProfessors);

// Close modal on outside click
document.getElementById('professorModal').addEventListener('click', (e) => {
    if (e.target.id === 'professorModal') closeProfessorModal();
});

// Initialize
loadSchools();
loadProfessors();
</script>

<?php include '../includes/footer.php'; ?>
