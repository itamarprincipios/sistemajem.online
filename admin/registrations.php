<?php
require_once '../config/config.php';
require_once '../includes/auth.php';
require_once '../includes/db.php';

requireAdmin();

$pageTitle = 'Aprovar Inscrições';

// Get counts
$pendingCount = queryOne("SELECT COUNT(*) as count FROM registrations WHERE status = 'pending' AND secretaria_id = ?", [CURRENT_TENANT_ID])['count'] ?? 0;
$approvedCount = queryOne("SELECT COUNT(*) as count FROM registrations WHERE status = 'approved' AND secretaria_id = ?", [CURRENT_TENANT_ID])['count'] ?? 0;
$rejectedCount = queryOne("SELECT COUNT(*) as count FROM registrations WHERE status = 'rejected' AND secretaria_id = ?", [CURRENT_TENANT_ID])['count'] ?? 0;

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<div class="main-content">
    <div class="top-bar">
        <h1 class="top-bar-title">Aprovar Inscrições</h1>
        <div class="user-menu">
            <div class="user-info">
                <div class="user-name"><?php echo htmlspecialchars(getCurrentUserName()); ?></div>
                <div class="user-role">Administrador</div>
            </div>
        </div>
    </div>
    
    <div class="content-wrapper">
        <!-- Stats Cards -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 2rem;">
            <div class="glass-card" style="padding: 1.5rem;">
                <div style="color: var(--text-secondary); font-size: 0.875rem; margin-bottom: 0.5rem;">Pendentes</div>
                <div style="font-size: 2rem; font-weight: 700; color: var(--warning);"><?php echo $pendingCount; ?></div>
            </div>
            <div class="glass-card" style="padding: 1.5rem;">
                <div style="color: var(--text-secondary); font-size: 0.875rem; margin-bottom: 0.5rem;">Aprovadas</div>
                <div style="font-size: 2rem; font-weight: 700; color: var(--success);"><?php echo $approvedCount; ?></div>
            </div>
            <div class="glass-card" style="padding: 1.5rem;">
                <div style="color: var(--text-secondary); font-size: 0.875rem; margin-bottom: 0.5rem;">Rejeitadas</div>
                <div style="font-size: 2rem; font-weight: 700; color: var(--error);"><?php echo $rejectedCount; ?></div>
            </div>
        </div>
        
        <!-- Filters -->
        <div class="glass-card" style="margin-bottom: 2rem;">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                <div class="form-group" style="margin: 0;">
                    <label class="form-label">Escola</label>
                    <select id="filterSchool" class="form-select">
                        <option value="">Todas</option>
                    </select>
                </div>
                <div class="form-group" style="margin: 0;">
                    <label class="form-label">Modalidade</label>
                    <select id="filterModality" class="form-select">
                        <option value="">Todas</option>
                    </select>
                </div>
                <div class="form-group" style="margin: 0;">
                    <label class="form-label">Categoria</label>
                    <select id="filterCategory" class="form-select">
                        <option value="">Todas</option>
                    </select>
                </div>
                <div class="form-group" style="margin: 0;">
                    <label class="form-label">Status</label>
                    <select id="filterStatus" class="form-select">
                        <option value="">Todos</option>
                        <option value="pending" selected>Pendentes</option>
                        <option value="approved">Aprovadas</option>
                        <option value="rejected">Rejeitadas</option>
                    </select>
                </div>
            </div>
        </div>
        
        <!-- Table -->
        <div class="glass-card">
            <div class="table-container">
                <table class="table" id="registrationsTable">
                    <thead>
                        <tr>
                            <th>Escola</th>
                            <th>Modalidade</th>
                            <th>Categoria</th>
                            <th>Gênero</th>
                            <th>Atletas</th>
                            <th>Status</th>
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

<!-- Modal: View Details -->
<div class="modal-overlay" id="detailsModal">
    <div class="modal" style="max-width: 800px;">
        <div class="modal-header">
            <h3 class="modal-title">Detalhes da Inscrição</h3>
            <button class="modal-close" onclick="closeDetailsModal()">×</button>
        </div>
        <div class="modal-body" id="detailsContent">
            <!-- Content loaded dynamically -->
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="closeDetailsModal()">Fechar</button>
        </div>
    </div>
</div>

<!-- Modal: Reject -->
<div class="modal-overlay" id="rejectModal">
    <div class="modal">
        <div class="modal-header">
            <h3 class="modal-title">Rejeitar Inscrição</h3>
            <button class="modal-close" onclick="closeRejectModal()">×</button>
        </div>
        <div class="modal-body">
            <input type="hidden" id="rejectRegistrationId">
            <div class="form-group">
                <label class="form-label">Motivo da Rejeição *</label>
                <textarea 
                    id="rejectionReason" 
                    class="form-textarea" 
                    rows="4"
                    placeholder="Explique o motivo da rejeição..."
                    required
                ></textarea>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="closeRejectModal()">Cancelar</button>
            <button class="btn btn-danger" onclick="confirmReject()">Rejeitar Inscrição</button>
        </div>
    </div>
</div>

<script>
let registrations = [];

// Load filters
async function loadFilters() {
    try {
        // Load schools
        const schoolsRes = await fetch('../api/schools-api.php');
        const schoolsData = await schoolsRes.json();
        if (schoolsData.success) {
            const select = document.getElementById('filterSchool');
            schoolsData.data.forEach(school => {
                select.innerHTML += `<option value="${school.id}">${school.name}</option>`;
            });
        }
        
        // Load modalities
        const modalitiesRes = await fetch('../api/modalities-api.php');
        const modalitiesData = await modalitiesRes.json();
        if (modalitiesData.success) {
            const select = document.getElementById('filterModality');
            modalitiesData.data.forEach(mod => {
                select.innerHTML += `<option value="${mod.id}">${mod.name}</option>`;
            });
        }
        
        // Load categories
        const categoriesRes = await fetch('../api/categories-api.php');
        const categoriesData = await categoriesRes.json();
        if (categoriesData.success) {
            const select = document.getElementById('filterCategory');
            categoriesData.data.forEach(cat => {
                select.innerHTML += `<option value="${cat.id}">${cat.name}</option>`;
            });
        }
    } catch (error) {
        console.error('Error loading filters:', error);
    }
}

// Load registrations
async function loadRegistrations() {
    try {
        const response = await fetch('../api/registrations-api.php?action=list');
        const data = await response.json();
        
        if (data.success) {
            registrations = data.data;
            applyFiltersAndRender();
        } else {
            Toast.error('Erro ao carregar inscrições');
        }
    } catch (error) {
        console.error('Error:', error);
        Toast.error('Erro ao carregar inscrições');
    }
}

// Apply filters and render
function applyFiltersAndRender() {
    let filtered = [...registrations];
    
    const schoolFilter = document.getElementById('filterSchool').value;
    const modalityFilter = document.getElementById('filterModality').value;
    const categoryFilter = document.getElementById('filterCategory').value;
    const statusFilter = document.getElementById('filterStatus').value;
    
    if (schoolFilter) filtered = filtered.filter(r => r.school_id == schoolFilter);
    if (modalityFilter) filtered = filtered.filter(r => r.modality_id == modalityFilter);
    if (categoryFilter) filtered = filtered.filter(r => r.category_id == categoryFilter);
    if (statusFilter) filtered = filtered.filter(r => r.status === statusFilter);
    
    renderTable(filtered);
}

// Render table
function renderTable(data) {
    const tbody = document.querySelector('#registrationsTable tbody');
    tbody.innerHTML = '';
    
    if (data.length === 0) {
        tbody.innerHTML = '<tr><td colspan="7" style="text-align: center; padding: 2rem; color: var(--text-secondary);">Nenhuma inscrição encontrada</td></tr>';
        return;
    }
    
    data.forEach(reg => {
        const genderLabels = { 'M': 'Masculino', 'F': 'Feminino', 'mixed': 'Misto' };
        const statusBadges = {
            'pending': '<span class="badge badge-warning">Pendente</span>',
            'approved': '<span class="badge badge-success">Aprovada</span>',
            'rejected': '<span class="badge badge-error">Rejeitada</span>'
        };
        
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>${reg.school_name}</td>
            <td>${reg.modality_name}</td>
            <td>${reg.category_name}</td>
            <td>${genderLabels[reg.gender]}</td>
            <td>${reg.athlete_count || 0} atletas</td>
            <td>${statusBadges[reg.status]}</td>
            <td>
                <button class="btn btn-sm btn-secondary" onclick="viewDetails(${reg.id})" style="margin-right: 0.5rem;">Ver Detalhes</button>
                ${reg.status === 'pending' ? `
                    <button class="btn btn-sm btn-success" onclick="approveRegistration(${reg.id})" style="margin-right: 0.5rem;">Aprovar</button>
                    <button class="btn btn-sm btn-danger" onclick="openRejectModal(${reg.id})">Rejeitar</button>
                ` : ''}
            </td>
        `;
        tbody.appendChild(row);
    });
}

// View details
async function viewDetails(id) {
    try {
        const response = await fetch(`../api/registrations-api.php?action=details&id=${id}`);
        const data = await response.json();
        
        if (data.success) {
            const reg = data.data;
            const genderLabels = { 'M': 'Masculino', 'F': 'Feminino', 'mixed': 'Misto' };
            
            let athletesHtml = '<p style="color: var(--text-secondary);">Nenhum atleta inscrito</p>';
            if (reg.athletes && reg.athletes.length > 0) {
                athletesHtml = '<div class="table-container"><table class="table"><thead><tr><th>Nome</th><th>CPF</th><th>Data Nascimento</th><th>Idade</th><th>Documento</th></tr></thead><tbody>';
                reg.athletes.forEach(athlete => {
                    const documentBtn = athlete.document_path 
                        ? `<a href="../${athlete.document_path}" target="_blank" class="btn btn-sm btn-info" style="text-decoration: none;">📄 Ver Documento</a>` 
                        : '<span style="color: var(--text-secondary);">Não enviado</span>';
                        
                    athletesHtml += `
                        <tr>
                            <td>${athlete.name}</td>
                            <td>${athlete.cpf || athlete.document_number || 'N/A'}</td>
                            <td>${new Date(athlete.birth_date).toLocaleDateString('pt-BR')}</td>
                            <td>${athlete.age} anos</td>
                            <td>${documentBtn}</td>
                        </tr>
                    `;
                });
                athletesHtml += '</tbody></table></div>';
            }
            
            const content = `
                <div style="margin-bottom: 1.5rem;">
                    <h4 style="margin-bottom: 1rem;">Informações da Inscrição</h4>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                        <div><strong>Escola:</strong> ${reg.school_name}</div>
                        <div><strong>Modalidade:</strong> ${reg.modality_name}</div>
                        <div><strong>Categoria:</strong> ${reg.category_name}</div>
                        <div><strong>Gênero:</strong> ${genderLabels[reg.gender]}</div>
                        <div><strong>Data:</strong> ${new Date(reg.created_at).toLocaleDateString('pt-BR')}</div>
                        <div><strong>Total de Atletas:</strong> ${reg.athletes?.length || 0}</div>
                    </div>
                    ${reg.rejection_reason ? `
                        <div style="margin-top: 1rem; padding: 1rem; background: rgba(239, 68, 68, 0.1); border-left: 3px solid var(--error); border-radius: var(--radius-md);">
                            <strong>Motivo da Rejeição:</strong><br>
                            ${reg.rejection_reason}
                        </div>
                    ` : ''}
                </div>
                <div>
                    <h4 style="margin-bottom: 1rem;">Lista de Atletas</h4>
                    ${athletesHtml}
                </div>
            `;
            
            document.getElementById('detailsContent').innerHTML = content;
            document.getElementById('detailsModal').classList.add('active');
        }
    } catch (error) {
        console.error('Error:', error);
        Toast.error('Erro ao carregar detalhes');
    }
}

function closeDetailsModal() {
    document.getElementById('detailsModal').classList.remove('active');
}

// Approve registration
async function approveRegistration(id) {
    if (!confirm('Tem certeza que deseja aprovar esta inscrição?')) return;
    
    try {
        const response = await fetch('../api/registrations-api.php', {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id, status: 'approved' })
        });
        
        const result = await response.json();
        
        if (result.success) {
            Toast.success('Inscrição aprovada com sucesso!');
            loadRegistrations();
        } else {
            Toast.error(result.error || 'Erro ao aprovar inscrição');
        }
    } catch (error) {
        console.error('Error:', error);
        Toast.error('Erro ao aprovar inscrição');
    }
}

// Reject modal
function openRejectModal(id) {
    document.getElementById('rejectRegistrationId').value = id;
    document.getElementById('rejectionReason').value = '';
    document.getElementById('rejectModal').classList.add('active');
}

function closeRejectModal() {
    document.getElementById('rejectModal').classList.remove('active');
}

async function confirmReject() {
    const id = document.getElementById('rejectRegistrationId').value;
    const reason = document.getElementById('rejectionReason').value.trim();
    
    if (!reason) {
        Toast.error('Por favor, informe o motivo da rejeição');
        return;
    }
    
    try {
        const response = await fetch('../api/registrations-api.php', {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id, status: 'rejected', rejection_reason: reason })
        });
        
        const result = await response.json();
        
        if (result.success) {
            Toast.success('Inscrição rejeitada');
            closeRejectModal();
            loadRegistrations();
        } else {
            Toast.error(result.error || 'Erro ao rejeitar inscrição');
        }
    } catch (error) {
        console.error('Error:', error);
        Toast.error('Erro ao rejeitar inscrição');
    }
}

// Filter listeners
['filterSchool', 'filterModality', 'filterCategory', 'filterStatus'].forEach(id => {
    document.getElementById(id).addEventListener('change', applyFiltersAndRender);
});

// Close modals on outside click
document.getElementById('detailsModal').addEventListener('click', (e) => {
    if (e.target.id === 'detailsModal') closeDetailsModal();
});
document.getElementById('rejectModal').addEventListener('click', (e) => {
    if (e.target.id === 'rejectModal') closeRejectModal();
});

// Initialize
loadFilters();
loadRegistrations();
</script>

<?php include '../includes/footer.php'; ?>
