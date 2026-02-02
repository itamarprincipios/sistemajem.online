<?php
require_once '../config/config.php';
require_once '../includes/auth.php';
require_once '../includes/db.php';

requireAdmin();

$pageTitle = 'Equipes Aprovadas';

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<div class="main-content">
    <div class="top-bar">
        <h1 class="top-bar-title">Equipes Aprovadas</h1>
        <div class="user-menu">
            <div class="user-info">
                <div class="user-name"><?php echo htmlspecialchars(getCurrentUserName()); ?></div>
                <div class="user-role">Administrador</div>
            </div>
        </div>
    </div>
    
    <div class="content-wrapper">
        <!-- Filters -->
        <div class="glass-card" style="margin-bottom: 2rem;">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1rem;">
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
                    <label class="form-label">Gênero</label>
                    <select id="filterGender" class="form-select">
                        <option value="">Todos</option>
                        <option value="M">Masculino</option>
                        <option value="F">Feminino</option>
                        <option value="mixed">Misto</option>
                    </select>
                </div>
            </div>
        </div>
        
        <!-- Teams Grid -->
        <div id="teamsGrid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(350px, 1fr)); gap: 1.5rem;">
            <!-- Teams loaded here -->
        </div>
        
        <!-- Empty State -->
        <div id="emptyState" style="display: none; text-align: center; padding: 4rem 2rem;">
            <div style="font-size: 4rem; margin-bottom: 1rem;">🏆</div>
            <h3 style="color: var(--text-secondary); margin-bottom: 0.5rem;">Nenhuma equipe encontrada</h3>
            <p style="color: var(--text-muted);">As equipes aprovadas aparecerão aqui</p>
        </div>
    </div>
</div>

<!-- Modal: Document Viewer -->
<div class="modal-overlay" id="documentModal">
    <div class="modal" style="max-width: 800px;">
        <div class="modal-header">
            <h3 class="modal-title" id="documentModalTitle">Documento de Identificação</h3>
            <button class="modal-close" onclick="closeDocumentModal()">×</button>
        </div>
        <div class="modal-body" id="documentModalContent" style="text-align: center; padding: 2rem;">
            <!-- Document loaded here -->
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="closeDocumentModal()">Fechar</button>
            <a id="downloadDocumentBtn" class="btn btn-primary" download style="display: none;">⬇️ Baixar Documento</a>
        </div>
    </div>
</div>

<!-- Modal: Team Details -->
<div class="modal-overlay" id="teamModal">
    <div class="modal" style="max-width: 900px;">
        <div class="modal-header">
            <h3 class="modal-title" id="teamModalTitle">Detalhes da Equipe</h3>
            <button class="modal-close" onclick="closeTeamModal()">×</button>
        </div>
        <div class="modal-body" id="teamModalContent">
            <!-- Content loaded dynamically -->
        </div>
        <div class="modal-footer">
            <button class="btn btn-danger" onclick="deleteCurrentTeam()" style="margin-right: auto;">🗑️ Excluir Equipe</button>
            <button class="btn btn-secondary" onclick="closeTeamModal()">Fechar</button>
            <button class="btn btn-primary" onclick="printTeamList()">🖨️ Imprimir Lista</button>
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
    cursor: pointer;
}

.team-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 12px 40px 0 rgba(0, 0, 0, 0.45);
    border-color: var(--primary);
}

.team-header {
    display: flex;
    align-items: center;
    gap: 1rem;
    margin-bottom: 1rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid var(--border);
}

.team-icon {
    font-size: 2.5rem;
}

.team-info h4 {
    margin: 0 0 0.25rem 0;
    font-size: 1.25rem;
}

.team-info p {
    margin: 0;
    color: var(--text-secondary);
    font-size: 0.875rem;
}

.team-stats {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 0.75rem;
}

.team-stat {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.875rem;
}

.team-stat-icon {
    font-size: 1.25rem;
}

.btn-sm {
    padding: 0.375rem 0.75rem;
    font-size: 0.875rem;
}

#documentModalContent img {
    max-width: 100%;
    max-height: 70vh;
    border-radius: var(--radius-md);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
}

#documentModalContent iframe {
    width: 100%;
    height: 70vh;
    border: none;
    border-radius: var(--radius-md);
}

.document-error {
    color: var(--danger);
    padding: 2rem;
    text-align: center;
}

.document-loading {
    color: var(--text-secondary);
    padding: 2rem;
    text-align: center;
}

@media print {
    body * {
        visibility: hidden;
    }
    #teamModalContent, #teamModalContent * {
        visibility: visible;
    }
    #teamModalContent {
        position: absolute;
        left: 0;
        top: 0;
        width: 100%;
    }
    .modal-overlay, .modal-header, .modal-footer {
        display: none !important;
    }
}

.btn-danger {
    background-color: #ef4444;
    color: white;
    border: none;
}
.btn-danger:hover {
    background-color: #dc2626;
}
</style>

<script>
let teams = [];
let currentTeam = null;

// Load filters
async function loadFilters() {
    try {
        const [schoolsRes, modalitiesRes, categoriesRes] = await Promise.all([
            fetch('../api/schools-api.php'),
            fetch('../api/modalities-api.php'),
            fetch('../api/categories-api.php')
        ]);
        
        const [schoolsData, modalitiesData, categoriesData] = await Promise.all([
            schoolsRes.json(),
            modalitiesRes.json(),
            categoriesRes.json()
        ]);
        
        if (schoolsData.success) {
            const select = document.getElementById('filterSchool');
            schoolsData.data.forEach(school => {
                select.innerHTML += `<option value="${school.id}">${school.name}</option>`;
            });
        }
        
        if (modalitiesData.success) {
            const select = document.getElementById('filterModality');
            modalitiesData.data.forEach(mod => {
                select.innerHTML += `<option value="${mod.id}">${mod.name}</option>`;
            });
        }
        
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

// Load teams
async function loadTeams() {
    try {
        const response = await fetch('../api/registrations-api.php?action=list');
        const data = await response.json();
        
        if (data.success) {
            // Filter only approved registrations
            teams = data.data.filter(t => t.status === 'approved');
            applyFiltersAndRender();
        } else {
            Toast.error('Erro ao carregar equipes');
        }
    } catch (error) {
        console.error('Error:', error);
        Toast.error('Erro ao carregar equipes');
    }
}

// Apply filters and render
function applyFiltersAndRender() {
    let filtered = [...teams];
    
    const schoolFilter = document.getElementById('filterSchool').value;
    const modalityFilter = document.getElementById('filterModality').value;
    const categoryFilter = document.getElementById('filterCategory').value;
    const genderFilter = document.getElementById('filterGender').value;
    
    if (schoolFilter) filtered = filtered.filter(t => t.school_id == schoolFilter);
    if (modalityFilter) filtered = filtered.filter(t => t.modality_id == modalityFilter);
    if (categoryFilter) filtered = filtered.filter(t => t.category_id == categoryFilter);
    if (genderFilter) filtered = filtered.filter(t => t.gender === genderFilter);
    
    renderTeams(filtered);
}

// Render teams
function renderTeams(data) {
    const grid = document.getElementById('teamsGrid');
    const emptyState = document.getElementById('emptyState');
    
    grid.innerHTML = '';
    
    if (data.length === 0) {
        grid.style.display = 'none';
        emptyState.style.display = 'block';
        return;
    }
    
    grid.style.display = 'grid';
    emptyState.style.display = 'none';
    
    const genderLabels = { 'M': 'Masculino', 'F': 'Feminino', 'mixed': 'Misto' };
    const genderIcons = { 'M': '♂️', 'F': '♀️', 'mixed': '⚥' };
    const modalityIcons = {
        'Futsal': '⚽',
        'Vôlei': '🏐',
        'Handebol': '🤾',
        'Basquete': '🏀',
        'Atletismo': '🏃',
        'Xadrez': '♟️',
        'Tênis de Mesa': '🏓',
        'Judô': '🥋'
    };
    
    data.forEach(team => {
        const card = document.createElement('div');
        card.className = 'team-card';
        card.onclick = () => viewTeamDetails(team.id);
        
        card.innerHTML = `
            <div class="team-header">
                <div class="team-icon">${modalityIcons[team.modality_name] || '🏆'}</div>
                <div class="team-info">
                    <h4>${team.school_name}</h4>
                    <p>${team.modality_name} - ${team.category_name}</p>
                </div>
            </div>
            <div class="team-stats">
                <div class="team-stat">
                    <span class="team-stat-icon">${genderIcons[team.gender]}</span>
                    <span>${genderLabels[team.gender]}</span>
                </div>
                <div class="team-stat">
                    <span class="team-stat-icon">👥</span>
                    <span>${team.athlete_count || 0} atletas</span>
                </div>
                <div class="team-stat">
                    <span class="team-stat-icon">📅</span>
                    <span>${new Date(team.created_at).toLocaleDateString('pt-BR')}</span>
                </div>
                <div class="team-stat">
                    <span class="team-stat-icon">✅</span>
                    <span>Aprovada</span>
                </div>
                <div class="team-stat" style="grid-column: span 2; border-top: 1px solid var(--border); padding-top: 0.5rem; margin-top: 0.25rem;">
                    <span class="team-stat-icon">👨‍🏫</span>
                    <span style="font-size: 0.8rem; color: var(--text-secondary);">Prof. ${team.professor_name || 'N/A'}</span>
                </div>
            </div>
        `;
        
        grid.appendChild(card);
    });
}

// View team details
async function viewTeamDetails(id) {
    try {
        const response = await fetch(`../api/registrations-api.php?action=details&id=${id}`);
        const data = await response.json();
        
        if (data.success) {
            const team = data.data;
            currentTeam = team; // Store for printing
            const genderLabels = { 'M': 'Masculino', 'F': 'Feminino', 'mixed': 'Misto' };
            
            document.getElementById('teamModalTitle').textContent = `${team.school_name} - ${team.modality_name}`;
            
            let athletesHtml = '<p style="color: var(--text-secondary);">Nenhum atleta inscrito</p>';
            if (team.athletes && team.athletes.length > 0) {
                athletesHtml = `
                    <div class="table-container">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Nome</th>
                                    <th>CPF</th>
                                    <th>Data Nascimento</th>
                                    <th>Idade</th>
                                    <th>Gênero</th>
                                    <th>Documento</th>
                                </tr>
                            </thead>
                            <tbody>
                `;
                
                team.athletes.forEach((athlete, index) => {
                    const documentButton = athlete.document_path 
                        ? `<button class="btn btn-sm btn-primary" onclick="viewDocument('${athlete.document_path}', '${athlete.name.replace(/'/g, "\\'")}')">📄 Ver Documento</button>`
                        : '<span style="color: var(--text-muted); font-size: 0.875rem;">Não enviado</span>';
                    
                    athletesHtml += `
                        <tr>
                            <td>${index + 1}</td>
                            <td><strong>${athlete.name}</strong></td>
                            <td>${athlete.document_number}</td>
                            <td>${new Date(athlete.birth_date).toLocaleDateString('pt-BR')}</td>
                            <td>${athlete.age} anos</td>
                            <td>${athlete.gender === 'M' ? 'Masculino' : 'Feminino'}</td>
                            <td>${documentButton}</td>
                        </tr>
                    `;
                });
                
                athletesHtml += '</tbody></table></div>';
            }
            
            const content = `
                <div style="margin-bottom: 2rem;">
                    <h4 style="margin-bottom: 1rem;">Informações da Equipe</h4>
                    <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 1rem; padding: 1rem; background: var(--bg-tertiary); border-radius: var(--radius-md);">
                        <div><strong>Escola:</strong> ${team.school_name}</div>
                        <div><strong>Modalidade:</strong> ${team.modality_name}</div>
                        <div><strong>Categoria:</strong> ${team.category_name}</div>
                        <div><strong>Gênero:</strong> ${genderLabels[team.gender]}</div>
                        <div><strong>Professor Responsável:</strong> ${team.professor_name || '<span style="color: var(--text-muted)">Não registrado</span>'}</div>
                        <div><strong>Telefone Professor:</strong> ${team.professor_phone || '<span style="color: var(--text-muted)">-</span>'}</div>
                        <div><strong>Total de Atletas:</strong> ${team.athletes?.length || 0}</div>
                        <div><strong>Data de Aprovação:</strong> ${new Date(team.updated_at).toLocaleDateString('pt-BR')}</div>
                    </div>
                </div>
                
                ${team.tecnico_nome ? `
                    <div style="margin-bottom: 2rem;">
                        <h4 style="margin-bottom: 1rem;">👥 Equipe Técnica</h4>
                        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem;">
                            <div style="padding: 1rem; background: var(--bg-tertiary); border-radius: var(--radius-md); border-left: 3px solid var(--primary);">
                                <div style="font-size: 0.75rem; color: var(--text-secondary); margin-bottom: 0.5rem; text-transform: uppercase; font-weight: 600;">👤 Técnico</div>
                                <div style="font-weight: 600; margin-bottom: 0.25rem;">${team.tecnico_nome}</div>
                                <div style="color: var(--text-secondary); font-size: 0.875rem;">📱 ${team.tecnico_celular}</div>
                            </div>
                            <div style="padding: 1rem; background: var(--bg-tertiary); border-radius: var(--radius-md); border-left: 3px solid var(--success);">
                                <div style="font-size: 0.75rem; color: var(--text-secondary); margin-bottom: 0.5rem; text-transform: uppercase; font-weight: 600;">🤝 Auxiliar Técnico</div>
                                <div style="font-weight: 600; margin-bottom: 0.25rem;">${team.auxiliar_tecnico_nome}</div>
                                <div style="color: var(--text-secondary); font-size: 0.875rem;">📱 ${team.auxiliar_tecnico_celular}</div>
                            </div>
                            <div style="padding: 1rem; background: var(--bg-tertiary); border-radius: var(--radius-md); border-left: 3px solid var(--warning);">
                                <div style="font-size: 0.75rem; color: var(--text-secondary); margin-bottom: 0.5rem; text-transform: uppercase; font-weight: 600;">👔 Chefe de Delegação</div>
                                <div style="font-weight: 600; margin-bottom: 0.25rem;">${team.chefe_delegacao_nome}</div>
                                <div style="color: var(--text-secondary); font-size: 0.875rem;">📱 ${team.chefe_delegacao_celular}</div>
                            </div>
                        </div>
                    </div>
                ` : ''}
                
                <div>
                    <h4 style="margin-bottom: 1rem;">Lista de Atletas Convocados</h4>
                    ${athletesHtml}
                </div>
            `;
            
            document.getElementById('teamModalContent').innerHTML = content;
            document.getElementById('teamModal').classList.add('active');
        }
    } catch (error) {
        console.error('Error:', error);
        Toast.error('Erro ao carregar detalhes da equipe');
    }
}

function closeTeamModal() {
    document.getElementById('teamModal').classList.remove('active');
}

async function deleteCurrentTeam() {
    if (!currentTeam) return;
    
    if (!confirm('Tem certeza que deseja excluir esta equipe?\n\nATENÇÃO: A equipe será removida, mas os alunos permanecerão cadastrados no sistema.')) {
        return;
    }

    try {
        const response = await fetch(`../api/registrations-api.php?action=delete&id=${currentTeam.id}`, {
            method: 'DELETE'
        });
        const data = await response.json();

        if (data.success) {
            // Show success message (using native alert if Toast not available, or just reload)
            // Assuming Toast is available as used elsewhere
            if (typeof Toast !== 'undefined') {
                Toast.success('Equipe excluída com sucesso');
            } else {
                alert('Equipe excluída com sucesso');
            }
            closeTeamModal();
            loadTeams();
        } else {
            if (typeof Toast !== 'undefined') {
                Toast.error(data.error || 'Erro ao excluir equipe');
            } else {
                alert(data.error || 'Erro ao excluir equipe');
            }
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Erro ao excluir equipe');
    }
}

function printTeamList() {
    if (!currentTeam) return;
    
    const printWindow = window.open('', '_blank');
    const currentYear = new Date().getFullYear();
    
    let athletesRows = '';
    if (currentTeam.athletes && currentTeam.athletes.length > 0) {
        currentTeam.athletes.forEach((athlete, index) => {
            athletesRows += `
                <tr>
                    <td style="text-align: center;">${index + 1}</td>
                    <td>${athlete.name}</td>
                    <td style="text-align: center;">${new Date(athlete.birth_date).toLocaleDateString('pt-BR')}</td>
                    <td style="text-align: center;">${athlete.age}</td>
                </tr>
            `;
        });
    } else {
        athletesRows = '<tr><td colspan="4" style="text-align: center;">Nenhum atleta inscrito</td></tr>';
    }
    
    const html = `
        <!DOCTYPE html>
        <html>
        <head>
            <title>Lista de Equipe - ${currentTeam.school_name}</title>
            <style>
                body {
                    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                    margin: 0;
                    padding: 40px;
                    color: #000;
                }
                .header {
                    text-align: center;
                    margin-bottom: 30px;
                    border-bottom: 2px solid #000;
                    padding-bottom: 20px;
                }
                .title {
                    font-size: 24px;
                    font-weight: bold;
                    text-transform: uppercase;
                    margin-bottom: 10px;
                }
                .subtitle {
                    font-size: 16px;
                    margin-bottom: 5px;
                }
                .info-grid {
                    display: grid;
                    grid-template-columns: 1fr 1fr;
                    gap: 15px;
                    margin-bottom: 30px;
                    background: #f8f9fa;
                    padding: 20px;
                    border: 1px solid #dee2e6;
                    border-radius: 5px;
                }
                .info-item {
                    font-size: 14px;
                }
                .info-label {
                    font-weight: bold;
                    margin-right: 5px;
                }
                table {
                    width: 100%;
                    border-collapse: collapse;
                    margin-bottom: 50px;
                }
                th, td {
                    border: 1px solid #000;
                    padding: 8px 12px;
                    font-size: 14px;
                }
                th {
                    background-color: #f0f0f0;
                }
                .signature-line {
                    height: 30px;
                    border-bottom: 1px dotted #999;
                    margin-bottom: 5px;
                }
                @media print {
                    body { padding: 0; }
                    .info-grid { background: none; border: 1px solid #000; }
                    th { background-color: #eee !important; -webkit-print-color-adjust: exact; }
                }
            </style>
        </head>
        <body>
            <div class="header">
                <div class="title">Jogos Escolares ${currentYear}</div>
                <div class="subtitle">Ficha de Inscrição de Equipe</div>
            </div>
            
            <div class="info-grid">
                <div class="info-item">
                    <span class="info-label">Escola:</span> ${currentTeam.school_name}
                </div>
                <div class="info-item">
                    <span class="info-label">Modalidade:</span> ${currentTeam.modality_name}
                </div>
                <div class="info-item">
                    <span class="info-label">Categoria:</span> ${currentTeam.category_name}
                </div>
                <div class="info-item">
                    <span class="info-label">Gênero:</span> ${currentTeam.gender === 'M' ? 'Masculino' : (currentTeam.gender === 'F' ? 'Feminino' : 'Misto')}
                </div>
            </div>
            
            <h3 style="margin-bottom: 15px; font-size: 18px;">Atletas Inscritos</h3>
            <table>
                <thead>
                    <tr>
                        <th style="width: 50px; text-align: center;">#</th>
                        <th>Nome do Aluno</th>
                        <th style="width: 120px; text-align: center;">Nascimento</th>
                        <th style="width: 80px; text-align: center;">Idade</th>
                    </tr>
                </thead>
                <tbody>
                    ${athletesRows}
                </tbody>
            </table>
            
            <div class="signatures">
                <div class="signature-box">
                    <div class="signature-line"></div>
                    <div class="signature-label">Técnico Responsável</div>
                    <div style="font-weight: bold; margin-bottom: 2px;">${currentTeam.tecnico_nome || ''}</div>
                    <div style="font-size: 12px;">${currentTeam.tecnico_celular || ''}</div>
                </div>
                <div class="signature-box">
                    <div class="signature-line"></div>
                    <div class="signature-label">Chefe de Delegação</div>
                    <div style="font-weight: bold; margin-bottom: 2px;">${currentTeam.chefe_delegacao_nome || currentTeam.director || ''}</div>
                    <div style="font-size: 12px;">${currentTeam.chefe_delegacao_celular || currentTeam.school_phone || ''}</div>
                </div>
                <div class="signature-box">
                    <div class="signature-line"></div>
                    <div class="signature-label">Auxiliar Técnico</div>
                    <div style="font-weight: bold; margin-bottom: 2px;">${currentTeam.auxiliar_tecnico_nome || ''}</div>
                    <div style="font-size: 12px;">${currentTeam.auxiliar_tecnico_celular || ''}</div>
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
    
    printWindow.document.write(html);
    printWindow.document.close();
}

// View document - Opens in new tab
function viewDocument(documentPath, studentName) {
    if (!documentPath) {
        Toast.error('Documento não disponível');
        return;
    }
    
    const fullPath = '../' + documentPath;
    
    // Open document in new tab
    window.open(fullPath, '_blank');
}

// Filter listeners
['filterSchool', 'filterModality', 'filterCategory', 'filterGender'].forEach(id => {
    document.getElementById(id).addEventListener('change', applyFiltersAndRender);
});

// Close modal on outside click
document.getElementById('teamModal').addEventListener('click', (e) => {
    if (e.target.id === 'teamModal') closeTeamModal();
});

document.getElementById('documentModal').addEventListener('click', (e) => {
    if (e.target.id === 'documentModal') closeDocumentModal();
});

// Initialize
loadFilters();
loadTeams();
</script>

<?php include '../includes/footer.php'; ?>
