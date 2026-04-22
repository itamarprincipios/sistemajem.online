<?php
require_once '../config/config.php';
require_once '../includes/auth.php';
require_once '../includes/db.php';

requireAdmin();

$pageTitle = 'Gerenciar Categorias';

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<div class="main-content">
    <div class="top-bar">
        <h1 class="top-bar-title">Categorias Étarias</h1>
    </div>
    
    <div class="content-wrapper" style="max-width: 1400px; margin: 0 auto;">
        <!-- Header Elite Hero -->
        <div class="glass-card" style="margin-bottom: 2.5rem; border: none; background: linear-gradient(135deg, rgba(255,255,255,0.05) 0%, rgba(255,255,255,0.02) 100%); display: flex; justify-content: space-between; align-items: center; padding: 2.5rem; box-shadow: 0 20px 40px rgba(0,0,0,0.2);">
            <div>
                <h2 style="font-size: 1.75rem; font-weight: 800; letter-spacing: -0.02em; margin-bottom: 0.5rem; color: #fff;">Configuração de Categorias</h2>
                <p style="color: var(--text-secondary); font-size: 1rem; max-width: 500px; line-height: 1.5;">Defina os limites de idade e anos de nascimento para cada grupo competitivo.</p>
            </div>
            <button class="btn btn-primary" onclick="openModal()" style="height: 54px; padding: 0 1.75rem; border-radius: 14px; font-weight: 600; box-shadow: 0 10px 20px rgba(59, 130, 246, 0.25);">
                <span style="font-size: 1.4rem; margin-right: 8px;">+</span> Nova Categoria
            </button>
        </div>

        <!-- Tabela Elite -->
        <div class="glass-card" style="padding: 0; overflow: hidden; border-radius: 20px; border: 1px solid rgba(255,255,255,0.05);">
            <div class="table-container">
                <table class="table" id="categoriesTable" style="margin: 0; border-collapse: separate; border-spacing: 0;">
                    <thead>
                        <tr style="background: rgba(255,255,255,0.02);">
                            <th style="padding: 1.5rem 2rem; width: 350px;">Categoria</th>
                            <th style="padding: 1.5rem 1rem;">Janela de Nascimento</th>
                            <th style="padding: 1.5rem 1rem; text-align: center;">Vigência</th>
                            <th style="padding: 1.5rem 2rem; text-align: right;">Ações</th>
                        </tr>
                    </thead>
                    <tbody id="categoriesBody">
                        <tr>
                            <td colspan="4" style="text-align: center; padding: 6rem; color: var(--text-secondary);">
                                <div style="font-size: 2.5rem; margin-bottom: 1rem;">📋</div>
                                Sincronizando categorias étarias...
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<style>
    .avatar-cat {
        width: 44px;
        height: 44px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 800;
        font-size: 0.9rem;
        color: #fff;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    }
    .badge-year {
        background: rgba(255,255,255,0.05);
        border: 1px solid rgba(255,255,255,0.1);
        color: var(--text-primary);
        padding: 6px 14px;
        border-radius: 8px;
        font-weight: 700;
        font-family: monospace;
        font-size: 1rem;
    }
    .badge-pill-elite {
        padding: 5px 12px;
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
<div class="modal-overlay" id="categoryModal">
    <div class="modal">
        <div class="modal-header">
            <h3 class="modal-title" id="modalTitle">Nova Categoria</h3>
            <button class="modal-close" onclick="closeModal()">×</button>
        </div>
        <div class="modal-body">
            <form id="categoryForm">
                <input type="hidden" id="categoryId">
                <div class="form-group">
                    <label class="form-label">Nome da Categoria *</label>
                    <input type="text" id="name" class="form-input" placeholder="Ex: Fraldinha, Sub-11, Sub-13" required>
                </div>
                <div class="form-row" style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div class="form-group">
                        <label class="form-label">Ano de Nascimento Mínimo *</label>
                        <input type="number" id="minBirthYear" class="form-input" min="2000" max="2030" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Ano de Nascimento Máximo *</label>
                        <input type="number" id="maxBirthYear" class="form-input" min="2000" max="2030" required>
                    </div>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="closeModal()">Cancelar</button>
            <button class="btn btn-primary" onclick="saveCategory()">Salvar Categoria</button>
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

async function loadCategories() {
    try {
        const response = await fetch('../api/categories-api.php');
        const data = await response.json();
        if (data.success) renderTable(data.data);
    } catch (error) { Toast.error('Erro de sincronização'); }
}

function renderTable(categories) {
    const tbody = document.getElementById('categoriesBody');
    tbody.innerHTML = '';
    if (categories.length === 0) {
        tbody.innerHTML = '<tr><td colspan="4" style="text-align: center; padding: 4rem; color: var(--text-muted);">Nenhuma categoria cadastrada.</td></tr>';
        return;
    }
    categories.sort((a, b) => b.min_birth_year - a.min_birth_year);
    categories.forEach(cat => {
        const color = stringToColor(cat.name);
        const initials = getInitials(cat.name);
        const row = document.createElement('tr');
        row.style.borderBottom = '1px solid rgba(255,255,255,0.05)';
        row.innerHTML = `
            <td style="padding: 1.25rem 2rem;">
                <div style="display: flex; align-items: center; gap: 1.25rem;">
                    <div class="avatar-cat" style="background: ${color}20; color: ${color}; border: 1px solid ${color}30;">${initials}</div>
                    <span style="font-weight: 600; color: #fff; font-size: 1.05rem;">${cat.name}</span>
                </div>
            </td>
            <td>
                <div style="display: flex; align-items: center; gap: 0.5rem;">
                    <span class="badge-year">${cat.min_birth_year}</span>
                    <span style="opacity: 0.3;">➔</span>
                    <span class="badge-year">${cat.max_birth_year}</span>
                </div>
            </td>
            <td style="text-align: center;">
                <span class="badge-pill-elite" style="background: rgba(34, 197, 94, 0.1); color: #4ade80;">Ativo</span>
            </td>
            <td style="padding: 1.25rem 2rem; text-align: right;">
                <div style="display: flex; justify-content: flex-end; gap: 0.75rem;">
                    <button class="btn-action-circle" onclick="editCategory(${cat.id})" title="Editar">✏️</button>
                    <button class="btn-action-circle btn-delete-elite" onclick="deleteCategory(${cat.id})" title="Excluir">🗑️</button>
                </div>
            </td>
        `;
        tbody.appendChild(row);
    });
}

function openModal(id = null) {
    const modal = document.getElementById('categoryModal');
    const form = document.getElementById('categoryForm');
    form.reset();
    if (id) { document.getElementById('modalTitle').textContent = 'Editar Categoria'; loadCategory(id); }
    else { document.getElementById('modalTitle').textContent = 'Nova Categoria'; document.getElementById('categoryId').value = ''; }
    modal.classList.add('active');
}
function closeModal() { document.getElementById('categoryModal').classList.remove('active'); }

async function loadCategory(id) {
    try {
        const response = await fetch('../api/categories-api.php');
        const data = await response.json();
        if (data.success) {
            const cat = data.data.find(c => c.id == id);
            if (cat) {
                document.getElementById('categoryId').value = cat.id;
                document.getElementById('name').value = cat.name;
                document.getElementById('minBirthYear').value = cat.min_birth_year;
                document.getElementById('maxBirthYear').value = cat.max_birth_year;
            }
        }
    } catch (e) {}
}

async function saveCategory() {
    const id = document.getElementById('categoryId').value;
    const name = document.getElementById('name').value.trim();
    const minBirthYear = parseInt(document.getElementById('minBirthYear').value);
    const maxBirthYear = parseInt(document.getElementById('maxBirthYear').value);
    if (!name || !minBirthYear || !maxBirthYear) { Toast.error('Preencha os campos obrigatórios'); return; }
    const data = { id: id || undefined, name, min_birth_year: minBirthYear, max_birth_year: maxBirthYear };
    try {
        const response = await fetch('../api/categories-api.php', {
            method: id ? 'PUT' : 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });
        const result = await response.json();
        if (result.success) { Toast.success('Operação concluída!'); closeModal(); loadCategories(); }
        else { Toast.error(result.error); }
    } catch (e) { Toast.error('Erro ao salvar'); }
}

async function deleteCategory(id) {
    if (!confirm('Tem certeza?')) return;
    try {
        const response = await fetch(`../api/categories-api.php?id=${id}`, { method: 'DELETE' });
        if ((await response.json()).success) { Toast.success('Removido!'); loadCategories(); }
    } catch (e) { Toast.error('Erro ao remover'); }
}

document.getElementById('categoryModal').addEventListener('click', (e) => { if (e.target.id === 'categoryModal') closeModal(); });
loadCategories();
</script>

<?php include '../includes/footer.php'; ?>
