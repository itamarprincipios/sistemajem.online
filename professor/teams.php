<?php
require_once '../config/config.php';
require_once '../includes/auth.php';
require_once '../includes/db.php';

requireProfessor();

$pageTitle = 'Minhas Equipes';

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<div class="main-content">
    <div class="top-bar">
        <h1 class="top-bar-title">Minhas Equipes</h1>
        <div class="user-menu">
            <div class="user-info">
                <div class="user-name"><?php echo htmlspecialchars(getCurrentUserName()); ?></div>
                <div class="user-role">Professor</div>
            </div>
        </div>
    </div>
    
    <div class="content-wrapper">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
            <div style="display: flex; gap: 1rem;">
                <select id="filterModality" class="form-select" style="width: 200px;">
                    <option value="">Todas as modalidades</option>
                </select>
                <select id="filterStatus" class="form-select" style="width: 200px;">
                    <option value="">Todos os status</option>
                    <option value="pending">Pendente</option>
                    <option value="approved">Aprovado</option>
                    <option value="rejected">Rejeitado</option>
                </select>
            </div>
            <button class="btn btn-primary" onclick="openTeamModal()">
                <span>➕</span>
                <span>Nova Equipe</span>
            </button>
        </div>
        
        <div id="teamsGrid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(350px, 1fr)); gap: 1.5rem;">
            <!-- Teams loaded here -->
        </div>
        
        <div id="emptyState" style="display: none; text-align: center; padding: 4rem 2rem;">
            <div style="font-size: 4rem; margin-bottom: 1rem;">📋</div>
            <h3 style="color: var(--text-secondary); margin-bottom: 0.5rem;">Nenhuma equipe cadastrada</h3>
            <p style="color: var(--text-muted);">Clique em "Nova Equipe" para começar</p>
        </div>
    </div>
</div>

<!-- Modal: New Team -->
<div class="modal-overlay" id="teamModal">
    <div class="modal">
        <div class="modal-header">
            <h3 class="modal-title">Nova Equipe</h3>
            <button class="modal-close" onclick="closeTeamModal()">×</button>
        </div>
        <div class="modal-body">
            <form id="teamForm">
                <div class="form-group">
                    <label class="form-label">Modalidade *</label>
                    <select id="modalityId" class="form-select" required>
                        <option value="">Selecione...</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Categoria *</label>
                    <select id="categoryId" class="form-select" required>
                        <option value="">Selecione...</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Gênero *</label>
                    <select id="gender" class="form-select" required>
                        <option value="">Selecione...</option>
                        <option value="M">Masculino</option>
                        <option value="F">Feminino</option>
                        <option value="mixed">Misto</option>
                    </select>
                </div>
                
                <hr style="margin: 1.5rem 0; border: none; border-top: 1px solid var(--border);">
                
                <h4 style="margin-bottom: 1rem; color: var(--text-primary); font-size: 1rem;">👥 Equipe Técnica</h4>
                
                <div class="form-group">
                    <label class="form-label">👤 Técnico - Nome *</label>
                    <input type="text" id="tecnicoNome" class="form-input" placeholder="Nome completo do técnico" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">📱 Técnico - Celular *</label>
                    <input type="tel" id="tecnicoCelular" class="form-input" placeholder="(00) 00000-0000" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">🤝 Auxiliar Técnico - Nome *</label>
                    <input type="text" id="auxiliarTecnicoNome" class="form-input" placeholder="Nome completo do auxiliar técnico" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">📱 Auxiliar Técnico - Celular *</label>
                    <input type="tel" id="auxiliarTecnicoCelular" class="form-input" placeholder="(00) 00000-0000" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">👔 Chefe de Delegação (Diretor) - Nome *</label>
                    <input type="text" id="chefeDelegacaoNome" class="form-input" placeholder="Nome completo do diretor" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">📱 Chefe de Delegação - Celular *</label>
                    <input type="tel" id="chefeDelegacaoCelular" class="form-input" placeholder="(00) 00000-0000" required>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="closeTeamModal()">Cancelar</button>
            <button class="btn btn-primary" onclick="saveTeam()">Criar Equipe</button>
        </div>
    </div>
</div>

<!-- Modal: Manage Athletes -->
<div class="modal-overlay" id="athletesModal">
    <div class="modal" style="max-width: 800px;">
        <div class="modal-header">
            <h3 class="modal-title" id="athletesModalTitle">Gerenciar Atletas</h3>
            <button class="modal-close" onclick="closeAthletesModal()">×</button>
        </div>
        <div class="modal-body">
            <div style="display: flex; gap: 1rem; margin-bottom: 1.5rem;" id="addAthleteSection">
                <select id="studentSelect" class="form-select" style="flex: 1;">
                    <option value="">Selecione um aluno para adicionar...</option>
                </select>
                <button class="btn btn-primary" onclick="addAthlete()">Adicionar</button>
            </div>
            
            <div class="table-container">
                <table class="table" id="athletesTable">
                    <thead>
                        <tr>
                            <th>Nome</th>
                            <th>Idade</th>
                            <th>Gênero</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Athletes loaded here -->
                    </tbody>
                </table>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="closeAthletesModal()">Fechar</button>
        </div>
    </div>
</div>

<!-- Modal: Badge Print -->
<div class="modal-overlay" id="badgeModal">
    <div class="modal" style="max-width: 450px;">
        <div class="modal-header">
            <h3 class="modal-title">Crachá do Atleta</h3>
            <button class="modal-close" onclick="closeBadgeModal()">×</button>
        </div>
        <div class="modal-body" style="padding: 0;">
            <div id="badgeContent" class="badge-container">
                <!-- Badge content will be loaded here -->
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="closeBadgeModal()">Fechar</button>
            <button class="btn btn-primary" onclick="window.print()">🖨️ Imprimir</button>
        </div>
    </div>
</div>

<style>
.team-card {
    background: var(--glass-bg);
    backdrop-filter: blur(10px);
    border: 1px solid var(--glass-border);
    border-radius: var(--radius-lg);
    padding: 1.5rem;
    transition: all var(--transition-base);
}

.team-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 12px 40px 0 rgba(0, 0, 0, 0.45);
    border-color: var(--primary);
}

.status-badge {
    padding: 0.25rem 0.75rem;
    border-radius: 999px;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
}

.status-pending { background: rgba(245, 158, 11, 0.1); color: #fbbf24; border: 1px solid rgba(245, 158, 11, 0.2); }
.status-approved { background: rgba(16, 185, 129, 0.1); color: #34d399; border: 1px solid rgba(16, 185, 129, 0.2); }
.status-rejected { background: rgba(239, 68, 68, 0.1); color: #f87171; border: 1px solid rgba(239, 68, 68, 0.2); }

/* Badge Styles */
.badge-container {
    background: white;
    padding: 2rem;
    border-radius: 1rem;
    color: #000;
    text-align: center;
    min-height: 500px;
    display: flex;
    flex-direction: column;
    justify-content: flex-start;
    align-items: center;
    position: relative;
    overflow: hidden;
    border: 2px solid #e5e7eb;
}

/* Decorative background elements */
.badge-container::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 15px;
    background: linear-gradient(90deg, #0056b3 0%, #00a859 100%); /* Blue to Green */
    z-index: 0;
}

.badge-container::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 0;
    width: 100%;
    height: 15px;
    background: linear-gradient(90deg, #00a859 0%, #0056b3 100%); /* Green to Blue */
    z-index: 0;
}

.badge-header {
    font-size: 1.5rem;
    font-weight: 800;
    color: #0056b3; /* Blue */
    margin-bottom: 1.5rem;
    text-transform: uppercase;
    letter-spacing: 1px;
    z-index: 1;
    border-bottom: 2px solid #00a859; /* Green underline */
    padding-bottom: 0.5rem;
    width: 100%;
}

.badge-photo-wrapper {
    position: relative;
    z-index: 1;
    margin-bottom: 1.5rem;
}

.badge-photo {
    width: 160px;
    height: 160px;
    border-radius: 50%;
    object-fit: cover;
    border: 4px solid #0056b3; /* Blue border */
    box-shadow: 0 4px 10px rgba(0,0,0,0.1);
}

.badge-info {
    position: relative;
    z-index: 1;
    background: #f8f9fa; /* Very light gray for contrast */
    padding: 1.5rem;
    border-radius: 0.5rem;
    width: 100%;
    max-width: 350px;
    border: 1px solid #e9ecef;
}

.badge-name {
    font-size: 1.6rem;
    font-weight: 700;
    margin-bottom: 1rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: #000;
}

.badge-field {
    display: flex;
    justify-content: space-between;
    padding: 0.5rem 0;
    border-bottom: 1px solid #dee2e6; /* Light gray border */
    font-size: 1rem;
}

.badge-field:last-child {
    border-bottom: none;
}

.badge-label {
    font-weight: 600;
    color: #0056b3; /* Blue label */
}

.badge-value {
    font-weight: 500;
    color: #000;
}

.badge-logo {
    position: absolute;
    bottom: 1rem;
    left: 50%;
    transform: translateX(-50%);
    font-size: 0.8rem;
    color: #6c757d;
    z-index: 1;
    font-weight: 500;
}

/* Print Styles */
@media print {
    body * {
        visibility: hidden;
    }
    
    #badgeModal,
    #badgeModal * {
        visibility: visible;
    }
    
    #badgeModal {
        position: fixed;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background: white;
    }
    
    .modal-header,
    .modal-footer,
    .modal-close {
        display: none !important;
    }
    
    .modal {
        box-shadow: none;
        max-width: 10cm;
        margin: 0 auto;
    }
    
    .badge-container {
        page-break-inside: avoid;
        min-height: 15cm;
    }
}
</style>

<script>
let currentTeamId = null;
let currentUserId = null;

// Load initial data
async function loadData() {
    try {
        const [modalitiesRes, categoriesRes, studentsRes] = await Promise.all([
            fetch('../api/modalities-api.php'),
            fetch('../api/categories-api.php'),
            fetch('../api/students-api.php?action=list_available') // We need to create this endpoint later
        ]);
        
        const modalities = await modalitiesRes.json();
        const categories = await categoriesRes.json();
        
        if (modalities.success) {
            const select = document.getElementById('modalityId');
            const filter = document.getElementById('filterModality');
            modalities.data.forEach(m => {
                select.innerHTML += `<option value="${m.id}">${m.name}</option>`;
                filter.innerHTML += `<option value="${m.id}">${m.name}</option>`;
            });
        }
        
        if (categories.success) {
            const select = document.getElementById('categoryId');
            const currentYear = new Date().getFullYear();
            categories.data.forEach(c => {
                const maxAge = currentYear - c.max_birth_year;
                select.innerHTML += `<option value="${c.id}">${c.name} (até ${maxAge} anos)</option>`;
            });
        }
        
        loadTeams();
    } catch (error) {
        console.error('Error:', error);
    }
}

// Load teams
async function loadTeams() {
    try {
        const response = await fetch('../api/professor-teams-api.php?action=list');
        const data = await response.json();
        
        if (data.success) {
            currentUserId = data.current_user_id;
            renderTeams(data.data);
        }
    } catch (error) {
        console.error('Error:', error);
        Toast.error('Erro ao carregar equipes');
    }
}

// Render teams
function renderTeams(teams) {
    const grid = document.getElementById('teamsGrid');
    const emptyState = document.getElementById('emptyState');
    const filterModality = document.getElementById('filterModality').value;
    const filterStatus = document.getElementById('filterStatus').value;
    
    // Apply filters
    const filtered = teams.filter(t => {
        if (filterModality && t.modality_id != filterModality) return false;
        if (filterStatus && t.status != filterStatus) return false;
        return true;
    });
    
    grid.innerHTML = '';
    
    if (filtered.length === 0) {
        grid.style.display = 'none';
        emptyState.style.display = 'block';
        return;
    }
    
    grid.style.display = 'grid';
    emptyState.style.display = 'none';
    
    const genderLabels = { 'M': 'Masculino', 'F': 'Feminino', 'mixed': 'Misto' };
    const statusLabels = { 'pending': 'Pendente', 'approved': 'Aprovada', 'rejected': 'Rejeitada' };
    
    filtered.forEach(team => {
        const isOwner = !team.created_by_user_id || team.created_by_user_id == currentUserId;
        const card = document.createElement('div');
        card.className = 'team-card';
        
        card.innerHTML = `
            <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 1rem;">
                <div>
                    <h3 style="margin: 0 0 0.25rem 0;">${team.modality_name}</h3>
                    <p style="color: var(--text-secondary); margin: 0; font-size: 0.875rem;">${team.category_name}</p>
                    ${!isOwner ? `<p style="color: var(--text-muted); margin: 0.25rem 0 0 0; font-size: 0.75rem;">Criado por: ${team.professor_name || 'Outro professor'}</p>` : ''}
                </div>
                <span class="status-badge status-${team.status}">${statusLabels[team.status]}</span>
            </div>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.5rem; margin-bottom: 1.5rem; font-size: 0.875rem;">
                <div>
                    <span style="color: var(--text-secondary);">Gênero:</span>
                    <div>${genderLabels[team.gender]}</div>
                </div>
                <div>
                    <span style="color: var(--text-secondary);">Atletas:</span>
                    <div>${team.athlete_count} inscritos</div>
                </div>
            </div>
            
            ${team.tecnico_nome ? `
                <div style="margin-top: 1rem; padding-top: 1rem; border-top: 1px solid var(--border);">
                    <div style="font-size: 0.75rem; font-weight: 600; color: var(--text-secondary); margin-bottom: 0.5rem; text-transform: uppercase;">Equipe Técnica</div>
                    <div style="display: grid; gap: 0.5rem; font-size: 0.875rem;">
                        <div style="display: flex; align-items: center; gap: 0.5rem;">
                            <span>👤</span>
                            <div>
                                <div style="font-weight: 500;">${team.tecnico_nome}</div>
                                <div style="color: var(--text-secondary); font-size: 0.75rem;">📱 ${team.tecnico_celular}</div>
                            </div>
                        </div>
                        <div style="display: flex; align-items: center; gap: 0.5rem;">
                            <span>🤝</span>
                            <div>
                                <div style="font-weight: 500;">${team.auxiliar_tecnico_nome}</div>
                                <div style="color: var(--text-secondary); font-size: 0.75rem;">📱 ${team.auxiliar_tecnico_celular}</div>
                            </div>
                        </div>
                        <div style="display: flex; align-items: center; gap: 0.5rem;">
                            <span>👔</span>
                            <div>
                                <div style="font-weight: 500;">${team.chefe_delegacao_nome}</div>
                                <div style="color: var(--text-secondary); font-size: 0.75rem;">📱 ${team.chefe_delegacao_celular}</div>
                            </div>
                        </div>
                    </div>
                </div>
            ` : ''}
            
            <div style="display: flex; gap: 0.5rem; margin-top: 1rem;">
                ${isOwner ? `
                    <button class="btn btn-sm btn-primary" onclick="manageAthletes(${team.id})" style="flex: 1;">
                        👥 Atletas
                    </button>
                    ${team.status === 'pending' ? `
                        <button class="btn btn-sm btn-danger" onclick="deleteTeam(${team.id})">
                            🗑️
                        </button>
                    ` : ''}
                ` : `
                    <button class="btn btn-sm btn-secondary" onclick="manageAthletes(${team.id})" style="flex: 1;">
                        👁️ Visualizar
                    </button>
                `}
            </div>
        `;
        
        grid.appendChild(card);
    });
}

// Create team
async function saveTeam() {
    const modalityId = document.getElementById('modalityId').value;
    const categoryId = document.getElementById('categoryId').value;
    const gender = document.getElementById('gender').value;
    const tecnicoNome = document.getElementById('tecnicoNome').value;
    const tecnicoCelular = document.getElementById('tecnicoCelular').value;
    const auxiliarTecnicoNome = document.getElementById('auxiliarTecnicoNome').value;
    const auxiliarTecnicoCelular = document.getElementById('auxiliarTecnicoCelular').value;
    const chefeDelegacaoNome = document.getElementById('chefeDelegacaoNome').value;
    const chefeDelegacaoCelular = document.getElementById('chefeDelegacaoCelular').value;
    
    if (!modalityId || !categoryId || !gender) {
        Toast.error('Preencha todos os campos da equipe');
        return;
    }
    
    if (!tecnicoNome || !tecnicoCelular) {
        Toast.error('Preencha os dados do Técnico');
        return;
    }
    
    if (!auxiliarTecnicoNome || !auxiliarTecnicoCelular) {
        Toast.error('Preencha os dados do Auxiliar Técnico');
        return;
    }
    
    if (!chefeDelegacaoNome || !chefeDelegacaoCelular) {
        Toast.error('Preencha os dados do Chefe de Delegação');
        return;
    }
    
    try {
        const response = await fetch('../api/professor-teams-api.php?action=create', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ 
                modality_id: modalityId, 
                category_id: categoryId, 
                gender,
                tecnico_nome: tecnicoNome,
                tecnico_celular: tecnicoCelular,
                auxiliar_tecnico_nome: auxiliarTecnicoNome,
                auxiliar_tecnico_celular: auxiliarTecnicoCelular,
                chefe_delegacao_nome: chefeDelegacaoNome,
                chefe_delegacao_celular: chefeDelegacaoCelular
            })
        });
        
        const result = await response.json();
        
        if (result.success) {
            Toast.success('Equipe criada com sucesso!');
            closeTeamModal();
            loadTeams();
        } else {
            Toast.error(result.error || 'Erro ao criar equipe');
        }
    } catch (error) {
        console.error('Error:', error);
        Toast.error('Erro ao criar equipe');
    }
}

// Manage athletes
async function manageAthletes(teamId) {
    currentTeamId = teamId;
    
    try {
        // Load team details and athletes
        const response = await fetch(`../api/professor-teams-api.php?action=details&id=${teamId}`);
        const data = await response.json();
        
        if (data.success) {
            const team = data.data;
            document.getElementById('athletesModalTitle').textContent = `${team.modality_name} - ${team.category_name}`;
            
            // Check ownership
            const isOwner = !team.created_by_user_id || team.created_by_user_id == currentUserId;
            
            // Toggle add section
            const addSection = document.getElementById('addAthleteSection');
            if (addSection) addSection.style.display = isOwner ? 'flex' : 'none';
            
            renderAthletesTable(team.athletes, isOwner);
            if (isOwner) loadAvailableStudents(); 
            
            document.getElementById('athletesModal').classList.add('active');
        }
    } catch (error) {
        console.error('Error:', error);
        Toast.error('Erro ao carregar detalhes');
    }
}

// Render athletes table
function renderAthletesTable(athletes, isOwner = true) {
    const tbody = document.querySelector('#athletesTable tbody');
    tbody.innerHTML = '';
    
    if (!athletes || athletes.length === 0) {
        tbody.innerHTML = '<tr><td colspan="4" style="text-align: center; padding: 1rem;">Nenhum atleta inscrito</td></tr>';
        return;
    }
    
    athletes.forEach(athlete => {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>${athlete.name}</td>
            <td>${athlete.age} anos</td>
            <td>${athlete.gender === 'M' ? 'Masc' : 'Fem'}</td>
            <td>
                <button class="btn btn-sm btn-secondary" onclick="printBadge(${athlete.id})" style="margin-right: 0.5rem;">🖨️ Crachá</button>
                ${isOwner ? `<button class="btn btn-sm btn-danger" onclick="removeAthlete(${athlete.enrollment_id})">Remover</button>` : ''}
            </td>
        `;
        tbody.appendChild(row);
    });
}

// Load available students for dropdown
async function loadAvailableStudents() {
    try {
        // We need an endpoint that lists students NOT in this team
        // For now, let's assume we implement a generic list endpoint and filter client-side or improve API later
        // Using a placeholder endpoint for now
        const response = await fetch('../api/students-api.php?action=list'); 
        const data = await response.json();
        
        if (data.success) {
            const select = document.getElementById('studentSelect');
            select.innerHTML = '<option value="">Selecione um aluno para adicionar...</option>';
            
            data.data.forEach(student => {
                select.innerHTML += `<option value="${student.id}">${student.name} (${student.age} anos)</option>`;
            });
        }
    } catch (error) {
        console.error('Error:', error);
    }
}

// Add athlete
async function addAthlete() {
    const studentId = document.getElementById('studentSelect').value;
    if (!studentId) return;
    
    try {
        const response = await fetch('../api/professor-teams-api.php?action=add_athlete', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ team_id: currentTeamId, student_id: studentId })
        });
        
        const result = await response.json();
        
        if (result.success) {
            Toast.success('Atleta adicionado!');
            manageAthletes(currentTeamId); // Reload list
        } else {
            Toast.error(result.error || 'Erro ao adicionar atleta');
        }
    } catch (error) {
        console.error('Error:', error);
        Toast.error('Erro ao adicionar atleta');
    }
}

// Remove athlete
async function removeAthlete(enrollmentId) {
    if (!confirm('Remover atleta da equipe?')) return;
    
    try {
        const response = await fetch(`../api/professor-teams-api.php?action=delete&id=${enrollmentId}&type=athlete`, {
            method: 'DELETE'
        });
        
        const result = await response.json();
        
        if (result.success) {
            Toast.success('Atleta removido!');
            manageAthletes(currentTeamId); // Reload list
        } else {
            Toast.error(result.error || 'Erro ao remover atleta');
        }
    } catch (error) {
        console.error('Error:', error);
        Toast.error('Erro ao remover atleta');
    }
}

// Delete team
async function deleteTeam(id) {
    if (!confirm('Tem certeza que deseja excluir esta equipe?')) return;
    
    try {
        const response = await fetch(`../api/professor-teams-api.php?action=delete&id=${id}&type=team`, {
            method: 'DELETE'
        });
        
        const result = await response.json();
        
        if (result.success) {
            Toast.success('Equipe excluída!');
            loadTeams();
        } else {
            Toast.error(result.error || 'Erro ao excluir equipe');
        }
    } catch (error) {
        console.error('Error:', error);
        Toast.error('Erro ao excluir equipe');
    }
}

// Print badge
async function printBadge(studentId) {
    try {
        // Load both student and current team info
        const [studentRes, teamRes] = await Promise.all([
            fetch(`../api/students-api.php?action=details&id=${studentId}`),
            fetch(`../api/professor-teams-api.php?action=details&id=${currentTeamId}`)
        ]);
        
        const studentData = await studentRes.json();
        const teamData = await teamRes.json();
        
        if (studentData.success && teamData.success) {
            const student = studentData.data;
            const team = teamData.data;
            const currentYear = new Date().getFullYear();
            
            const photoUrl = student.photo_path ? '../' + student.photo_path : 'https://ui-avatars.com/api/?name=' + encodeURIComponent(student.name) + '&size=150';
            
            // Create a new window for printing
            const printWindow = window.open('', '_blank');
            
            const badgeHtml = `
                <!DOCTYPE html>
                <html>
                <head>
                    <title>Crachá - ${student.name}</title>
                    <style>
                        body {
                            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                            margin: 0;
                            padding: 20px;
                            display: flex;
                            justify-content: center;
                            align-items: flex-start;
                            background: #f0f2f5;
                        }
                        
                        /* Badge Styles */
                        .badge-container {
                            background: white;
                            padding: 2rem;
                            border-radius: 1rem;
                            color: #000;
                            text-align: center;
                            width: 10cm;
                            height: 15cm;
                            display: flex;
                            flex-direction: column;
                            justify-content: flex-start;
                            align-items: center;
                            position: relative;
                            overflow: hidden;
                            border: 2px solid #e5e7eb;
                            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
                            box-sizing: border-box;
                        }

                        /* Decorative background elements */
                        .badge-container::before {
                            content: '';
                            position: absolute;
                            top: 0;
                            left: 0;
                            width: 100%;
                            height: 15px;
                            background: linear-gradient(90deg, #0056b3 0%, #00a859 100%);
                            z-index: 0;
                        }

                        .badge-container::after {
                            content: '';
                            position: absolute;
                            bottom: 0;
                            left: 0;
                            width: 100%;
                            height: 15px;
                            background: linear-gradient(90deg, #00a859 0%, #0056b3 100%);
                            z-index: 0;
                        }

                        .badge-header {
                            font-size: 1.4rem;
                            font-weight: 800;
                            color: #0056b3;
                            margin-bottom: 0.5rem;
                            text-transform: uppercase;
                            letter-spacing: 1px;
                            z-index: 1;
                            border-bottom: 2px solid #00a859;
                            padding-bottom: 0.25rem;
                            width: 100%;
                        }

                        .badge-photo-wrapper {
                            position: relative;
                            z-index: 1;
                            margin-bottom: 0.5rem;
                        }

                        .badge-photo {
                            width: 140px;
                            height: 140px;
                            border-radius: 50%;
                            object-fit: cover;
                            border: 4px solid #0056b3;
                            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
                        }

                        .badge-info {
                            position: relative;
                            z-index: 1;
                            background: #f8f9fa;
                            padding: 1rem;
                            border-radius: 0.5rem;
                            width: 100%;
                            max-width: 350px;
                            border: 1px solid #e9ecef;
                            box-sizing: border-box;
                            flex: 1;
                            display: flex;
                            flex-direction: column;
                            justify-content: center;
                        }

                        .badge-name {
                            font-size: 1.3rem;
                            font-weight: 700;
                            margin-bottom: 0.5rem;
                            text-transform: uppercase;
                            letter-spacing: 0.5px;
                            color: #000;
                            line-height: 1.1;
                        }

                        .badge-field {
                            display: flex;
                            justify-content: space-between;
                            padding: 0.35rem 0;
                            border-bottom: 1px solid #dee2e6;
                            font-size: 0.9rem;
                        }

                        .badge-field:last-child {
                            border-bottom: none;
                        }

                        .badge-label {
                            font-weight: 600;
                            color: #0056b3;
                        }

                        .badge-value {
                            font-weight: 500;
                            color: #000;
                            text-align: right;
                        }

                        .badge-logo {
                            margin-top: auto;
                            padding-top: 0.5rem;
                            font-size: 0.8rem;
                            color: #6c757d;
                            z-index: 1;
                            font-weight: 500;
                            position: relative;
                            bottom: auto;
                            left: auto;
                            transform: none;
                        }
                        
                        @media print {
                            body {
                                background: white;
                                padding: 0;
                            }
                            .badge-container {
                                box-shadow: none;
                                border: 1px solid #ddd;
                            }
                        }
                    </style>
                </head>
                <body>
                    <div class="badge-container">
                        <div class="badge-header">
                            Jogos Escolares ${currentYear}
                        </div>
                        <div class="badge-photo-wrapper">
                            <img src="${photoUrl}" alt="${student.name}" class="badge-photo" onerror="this.src='https://ui-avatars.com/api/?name=${encodeURIComponent(student.name)}&size=150'">
                        </div>
                        <div class="badge-info">
                            <div class="badge-name">${student.name}</div>
                            <div class="badge-field">
                                <span class="badge-label">Escola:</span>
                                <span class="badge-value">${team.school_name}</span>
                            </div>
                            <div class="badge-field">
                                <span class="badge-label">Modalidade:</span>
                                <span class="badge-value">${team.modality_name}</span>
                            </div>
                            <div class="badge-field">
                                <span class="badge-label">Categoria:</span>
                                <span class="badge-value">${team.category_name}</span>
                            </div>
                            <div class="badge-field">
                                <span class="badge-label">Idade:</span>
                                <span class="badge-value">${student.age} anos</span>
                            </div>
                            <div class="badge-field">
                                <span class="badge-label">Emergência:</span>
                                <span class="badge-value">${student.phone || 'Não informado'}</span>
                            </div>
                        </div>
                        <div class="badge-logo">
                            Sistema JEM - ${currentYear}
                        </div>
                    </div>
                    <script>
                        window.onload = function() {
                            window.print();
                        }
                    <\/script>
                </body>
                </html>
            `;
            
            printWindow.document.write(badgeHtml);
            printWindow.document.close();
            
        } else {
            Toast.error('Erro ao carregar dados para o crachá');
        }
    } catch (error) {
        console.error('Error:', error);
        Toast.error('Erro ao gerar crachá');
    }
}

function closeBadgeModal() {
    document.getElementById('badgeModal').classList.remove('active');
}

// Modals
function openTeamModal() {
    document.getElementById('teamModal').classList.add('active');
}
function closeTeamModal() {
    document.getElementById('teamModal').classList.remove('active');
}
function closeAthletesModal() {
    document.getElementById('athletesModal').classList.remove('active');
    loadTeams(); // Refresh main list to update counts
}

// Filter listeners
document.getElementById('filterModality').addEventListener('change', loadTeams);
document.getElementById('filterStatus').addEventListener('change', loadTeams);

// Initialize
loadData();
</script>

<?php include '../includes/footer.php'; ?>
