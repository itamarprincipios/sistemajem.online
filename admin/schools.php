<?php
require_once '../config/config.php';
require_once '../includes/auth.php';
require_once '../includes/db.php';

requireAdmin();

$pageTitle = 'Gerenciar Escolas';

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<div class="main-content">
    <div class="top-bar">
        <h1 class="top-bar-title">Escolas</h1>
        <div class="user-menu">
            <div class="user-info">
                <div class="user-name"><?php echo htmlspecialchars(getCurrentUserName()); ?></div>
                <div class="user-role">Administrador</div>
            </div>
        </div>
    </div>
    
    <div class="content-wrapper" style="max-width: 1400px; margin: 0 auto;">
        <!-- Header Elite Hero -->
        <div class="glass-card" style="margin-bottom: 2.5rem; border: none; background: linear-gradient(135deg, rgba(255,255,255,0.05) 0%, rgba(255,255,255,0.02) 100%); display: flex; justify-content: space-between; align-items: center; padding: 2.5rem; box-shadow: 0 20px 40px rgba(0,0,0,0.2);">
            <div>
                <h2 style="font-size: 1.75rem; font-weight: 800; letter-spacing: -0.02em; margin-bottom: 0.5rem; color: #fff;">Gestão Acadêmica</h2>
                <p style="color: var(--text-secondary); font-size: 1rem; max-width: 500px; line-height: 1.5;">Configure as instituições de ensino, diretores e informações de contato.</p>
                <div style="margin-top: 1.5rem; position: relative; width: 320px;">
                    <input type="text" id="searchInput" class="form-input" placeholder="Buscar instituição..." style="padding-left: 2.5rem; height: 48px; border-radius: 12px; background: rgba(255,255,255,0.03);">
                    <span style="position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); opacity: 0.5;">🔍</span>
                </div>
            </div>
            <button class="btn btn-primary" onclick="openModal()" style="height: 54px; padding: 0 1.75rem; border-radius: 14px; font-weight: 600; box-shadow: 0 10px 20px rgba(59, 130, 246, 0.25);">
                <span style="font-size: 1.4rem; margin-right: 8px;">+</span> Nova Escola
            </button>
        </div>
        
        <!-- Tabela Elite -->
        <div class="glass-card" style="padding: 0; overflow: hidden; border-radius: 20px; border: 1px solid rgba(255,255,255,0.05);">
            <div class="table-container">
                <table class="table" id="schoolsTable" style="margin: 0; border-collapse: separate; border-spacing: 0;">
                    <thead>
                        <tr style="background: rgba(255,255,255,0.02);">
                            <th style="padding: 1.5rem 2rem; width: 400px;">Instituição / Direção</th>
                            <th style="padding: 1.5rem 1rem;">Localidade</th>
                            <th style="padding: 1.5rem 1rem;">Contato</th>
                            <th style="padding: 1.5rem 2rem; text-align: right;">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td colspan="4" style="text-align: center; padding: 6rem; color: var(--text-secondary);">
                                <div style="font-size: 2.5rem; margin-bottom: 1rem;">🏫</div>
                                Sincronizando dados acadêmicos...
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <!-- Paginação Elite -->
            <div id="pagination" style="padding: 1.5rem; border-top: 1px solid rgba(255,255,255,0.05); display: flex; justify-content: center; gap: 0.5rem;"></div>
        </div>
    </div>
</div>

<style>
    .avatar-school {
        width: 46px;
        height: 46px;
        border-radius: 14px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: 1rem;
        color: #fff;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    }
    .row-hover:hover {
        background: rgba(255,255,255,0.03) !important;
    }
    .badge-pill-elite {
        padding: 6px 14px;
        border-radius: 50px;
        font-size: 0.8rem;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }
    .badge-municipality-elite { background: rgba(139, 92, 246, 0.1); color: #a78bfa; border: 1px solid rgba(139, 92, 246, 0.1); }
    
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
    .btn-delete-elite:hover { background: #f87171 !important; color: #fff; border-color: #f87171 !important; transform: translateY(-2px); box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3); }
</style>

<!-- Modal -->
<div class="modal-overlay" id="schoolModal">
    <div class="modal">
        <div class="modal-header">
            <h3 class="modal-title" id="modalTitle">Nova Escola</h3>
            <button class="modal-close" onclick="closeModal()">×</button>
        </div>
        <div class="modal-body">
            <form id="schoolForm">
                <input type="hidden" id="schoolId">
                
                <div class="form-group">
                    <label class="form-label">Nome *</label>
                    <input type="text" id="name" class="form-input" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Município *</label>
                    <input type="text" id="municipality" class="form-input" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Endereço</label>
                    <input type="text" id="address" class="form-input">
                </div>
                
                <div class="form-row" style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div class="form-group">
                        <label class="form-label">Telefone</label>
                        <input type="text" id="phone" class="form-input" data-format="phone">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Email</label>
                        <input type="email" id="email" class="form-input">
                    </div>
                </div>
                
                <div class="form-row" style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div class="form-group">
                        <label class="form-label">Diretor</label>
                        <input type="text" id="director" class="form-input">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Coordenador</label>
                        <input type="text" id="coordinator" class="form-input">
                    </div>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="closeModal()">Cancelar</button>
            <button class="btn btn-primary" onclick="saveSchool()">Salvar</button>
        </div>
    </div>
</div>

<script>
let currentPage = 1;
let searchTerm = '';

// Utils Design Elite
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

// Carregar escolas
async function loadSchools() {
    try {
        const response = await fetch(`../api/schools-api.php?action=list&page=${currentPage}&search=${searchTerm}`);
        const data = await response.json();
        
        if (data.success) {
            renderTable(data.data);
            renderPagination(data.page, data.pages);
        }
    } catch (error) {
        console.error('Error:', error);
        Toast.error('Erro de sincronização');
    }
}

// Renderizar tabela Elite
function renderTable(schools) {
    const tbody = document.querySelector('#schoolsTable tbody');
    tbody.innerHTML = '';
    
    if (!schools || schools.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="4" style="text-align: center; padding: 6rem; color: var(--text-muted);">
                    <div style="font-size: 3rem; margin-bottom: 1rem; opacity: 0.3;">🏫</div>
                    Nenhuma instituição localizada com os filtros atuais.
                </td>
            </tr>
        `;
        return;
    }
    
    schools.forEach(school => {
        const color = stringToColor(school.name);
        const initials = getInitials(school.name);
        const row = document.createElement('tr');
        row.className = 'row-hover';
        row.style.borderBottom = '1px solid rgba(255,255,255,0.05)';
        
        row.innerHTML = \`
            <td style="padding: 1.25rem 2rem;">
                <div style="display: flex; align-items: center; gap: 1.25rem;">
                    <div class="avatar-school" style="background: \${color}20; color: \${color}; border: 1px solid \${color}30;">\${initials}</div>
                    <div style="display: flex; flex-direction: column;">
                        <span style="font-weight: 600; color: #fff; font-size: 1rem; margin-bottom: 2px;">\${school.name}</span>
                        <span style="font-size: 0.85rem; color: var(--text-muted);">\${school.director ? 'Dir: ' + school.director : 'Direção não informada'}</span>
                    </div>
                </div>
            </td>
            <td style="padding: 1.25rem 1rem;">
                <span class="badge-pill-elite badge-municipality-elite">📍 \${school.municipality}</span>
            </td>
            <td style="padding: 1.25rem 1rem;">
                <div style="display: flex; flex-direction: column;">
                    <span style="font-size: 0.9rem; color: var(--text-secondary);">\${school.email || '-'}</span>
                    <span style="font-size: 0.8rem; color: var(--text-muted);">\${school.phone || ''}</span>
                </div>
            </td>
            <td style="padding: 1.25rem 2rem; text-align: right;">
                <div style="display: flex; justify-content: flex-end; gap: 0.75rem;">
                    <button class="btn-action-circle" onclick="editSchool(\${school.id})" title="Editar instituição">
                        <span style="font-size: 1rem;">✏️</span>
                    </button>
                    <button class="btn-action-circle btn-delete-elite" onclick="deleteSchool(\${school.id})" title="Remover permanentemente">
                        <span style="font-size: 1rem;">🗑️</span>
                    </button>
                </div>
            </td>
        \`;
        tbody.appendChild(row);
    });
}

// Renderizar paginação
function renderPagination(page, totalPages) {
    const pagination = document.getElementById('pagination');
    if (totalPages <= 1) { pagination.innerHTML = ''; return; }
    let html = '';
    for (let i = 1; i <= totalPages; i++) {
        html += `<button class="btn btn-sm ${i === page ? 'btn-primary' : 'btn-secondary'}" onclick="changePage(${i})" style="margin: 0 0.25rem;">${i}</button>`;
    }
    pagination.innerHTML = html;
}

function changePage(page) { currentPage = page; loadSchools(); }

document.getElementById('searchInput').addEventListener('input', (e) => {
    searchTerm = e.target.value;
    currentPage = 1;
    loadSchools();
});

function openModal(id = null) {
    const modal = document.getElementById('schoolModal');
    const form = document.getElementById('schoolForm');
    form.reset();
    if (id) {
        document.getElementById('modalTitle').textContent = 'Editar Escola';
        loadSchool(id);
    } else {
        document.getElementById('modalTitle').textContent = 'Nova Escola';
        document.getElementById('schoolId').value = '';
    }
    modal.classList.add('active');
}

function closeModal() { document.getElementById('schoolModal').classList.remove('active'); }

async function loadSchool(id) {
    try {
        const response = await fetch(`../api/schools-api.php?action=get&id=${id}`);
        const data = await response.json();
        if (data.success && data.data) {
            const school = data.data;
            document.getElementById('schoolId').value = school.id;
            document.getElementById('name').value = school.name;
            document.getElementById('municipality').value = school.municipality;
            document.getElementById('address').value = school.address || '';
            document.getElementById('phone').value = school.phone || '';
            document.getElementById('email').value = school.email || '';
            document.getElementById('director').value = school.director || '';
            document.getElementById('coordinator').value = school.coordinator || '';
        }
    } catch (error) { Toast.error('Erro ao carregar dados'); }
}

async function saveSchool() {
    const id = document.getElementById('schoolId').value;
    const name = document.getElementById('name').value.trim();
    const municipality = document.getElementById('municipality').value.trim();
    if (!name || !municipality) { Toast.error('Preencha os campos obrigatórios'); return; }
    const data = {
        id: id || undefined,
        name, municipality,
        address: document.getElementById('address').value.trim(),
        phone: document.getElementById('phone').value.trim(),
        email: document.getElementById('email').value.trim(),
        director: document.getElementById('director').value.trim(),
        coordinator: document.getElementById('coordinator').value.trim()
    };
    try {
        const response = await fetch('../api/schools-api.php', {
            method: id ? 'PUT' : 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });
        const result = await response.json();
        if (result.success) {
            Toast.success('Operação concluída!');
            closeModal();
            loadSchools();
        } else { Toast.error(result.error); }
    } catch (error) { Toast.error('Erro ao salvar'); }
}

async function deleteSchool(id, force = false) {
    if (!force && !confirm('Tem certeza?')) return;
    try {
        let url = `../api/schools-api.php?id=${id}`;
        if (force) url += '&force=true';
        const response = await fetch(url, { method: 'DELETE' });
        const result = await response.json();
        if (result.success) {
            Toast.success('Removido!');
            loadSchools();
        } else if (result.error && result.error.includes('DEPENDENCY_ERROR')) {
            if (confirm('⚠️ ATENÇÃO: Dependências encontradas. Deseja forçar a remoção?')) {
                deleteSchool(id, true);
            }
        }
    } catch (error) { Toast.error('Erro ao remover'); }
}

document.getElementById('schoolModal').addEventListener('click', (e) => { if (e.target.id === 'schoolModal') closeModal(); });

loadSchools();
</script>

<?php include '../includes/footer.php'; ?>
