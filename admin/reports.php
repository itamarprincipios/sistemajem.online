<?php
require_once '../config/config.php';
require_once '../includes/auth.php';
require_once '../includes/db.php';

requireAdmin();

$pageTitle = 'Relatórios Avançados';

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<div class="main-content">
    <div class="top-bar">
        <h1 class="top-bar-title">Relatórios Avançados</h1>
        <div class="user-menu">
            <div class="user-info">
                <div class="user-name"><?php echo htmlspecialchars(getCurrentUserName()); ?></div>
                <div class="user-role">Administrador</div>
            </div>
        </div>
    </div>
    
    <div class="content-wrapper">
        <div style="display: grid; grid-template-columns: 300px 1fr; gap: 2rem; align-items: start;">
            
            <!-- Filters Sidebar -->
            <div class="glass-card" id="filtersPanel" style="padding: 1.5rem; position: sticky; top: 2rem;">
                <h3 style="margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.5rem;">
                    <span>🔍</span> Filtros de Relatório
                </h3>
                
                <div class="form-group">
                    <label class="form-label">Escola</label>
                    <select id="filterSchool" class="form-select">
                        <option value="">Todas as Escolas</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Modalidade</label>
                    <select id="filterModality" class="form-select">
                        <option value="">Todas as Modalidades</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Categoria</label>
                    <select id="filterCategory" class="form-select">
                        <option value="">Todas as Categorias</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Gênero</label>
                    <select id="filterGender" class="form-select">
                        <option value="">Todos</option>
                        <option value="M">Masculino</option>
                        <option value="F">Feminino</option>
                        <option value="mixed">Misto</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Status da Inscrição</label>
                    <select id="filterStatus" class="form-select">
                        <option value="">Todos</option>
                        <option value="approved">Aprovadas</option>
                        <option value="pending">Pendentes</option>
                        <option value="rejected">Rejeitadas</option>
                    </select>
                </div>

                <div style="display: grid; gap: 0.5rem; margin-top: 2rem;">
                    <button class="btn btn-primary" onclick="generateReport()" style="width: 100%;">
                        📊 Gerar Relatório
                    </button>
                    <button class="btn btn-secondary" onclick="resetFilters()" style="width: 100%;">
                        Limpar Filtros
                    </button>
                </div>
            </div>

            <!-- Results Area -->
            <div>
                 <!-- Print Header (Hidden on screen) -->
                <div id="printHeader" style="display: none; margin-bottom: 2rem; border-bottom: 2px solid #ddd; padding-bottom: 1rem;">
                    <div style="display: flex; justify-content: space-between; align-items: flex-end;">
                        <div>
                            <h2 style="margin: 0; font-size: 1.5rem; color: #333;" id="printSchoolName">Relatório Geral</h2>
                            <p style="margin: 0.5rem 0 0 0; color: #666; font-size: 1.1rem;">
                                <strong>Diretor(a):</strong> <span id="printDirectorName">N/A</span>
                            </p>
                        </div>
                        <div style="text-align: right;">
                            <p style="margin: 0; color: #666;">Jogos Escolares Municipais</p>
                            <p style="margin: 0; font-size: 0.9rem; color: #888;"><?php echo date('d/m/Y H:i'); ?></p>
                        </div>
                    </div>
                </div>

                <!-- Header Actions -->
                <div class="glass-card" style="margin-bottom: 1.5rem; display: flex; justify-content: space-between; align-items: center; padding: 1rem 1.5rem;">
                    <div>
                        <h3 style="margin: 0;">Resultados</h3>
                        <small style="color: var(--text-secondary);" id="resultsCount">Nenhum resultado gerado</small>
                    </div>
                    <div style="display: flex; gap: 1rem;">
                        <button class="btn btn-secondary" onclick="printReport()" disabled id="btnPrint">
                            🖨️ Imprimir
                        </button>
                        <!-- Future: Export to Excel/PDF -->
                    </div>
                </div>

                <!-- Report Content -->
                <div class="glass-card" id="reportContainer">
                    <div class="table-container">
                        <table class="table" id="reportTable">
                            <thead>
                                <tr>
                                    <th>Escola</th>
                                    <th>Modalidade</th>
                                    <th>Categoria</th>
                                    <th>Gênero</th>
                                    <th>Professor</th>
                                    <th>Atletas</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td colspan="7" style="text-align: center; padding: 3rem; color: var(--text-secondary);">
                                        Selecione os filtros e clique em "Gerar Relatório" para ver os dados.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<style>
@media print {
    .sidebar, .top-bar, .btn {
        display: none !important;
    }
    
    /* Hide filters panel explicitly */
    #filtersPanel {
        display: none !important;
    }

    /* Show Print Header */
    #printHeader {
        display: block !important;
    }

    .main-content {
        margin-left: 0 !important;
        padding: 0 !important;
        width: 100% !important;
    }
    .content-wrapper {
        padding: 0 !important;
        max-width: none !important;
    }
    .glass-card {
        box-shadow: none !important;
        border: none !important;
        padding: 0 !important;
        margin: 0 !important;
        background: none !important;
    }
    
    /* Make table compact for printing */
    table {
        width: 100% !important;
        font-size: 10pt !important;
    }
    td, th {
        padding: 4px !important;
        border: 1px solid #ddd !important;
    }
    
    /* Grid layout reset */
    .content-wrapper > div {
        display: block !important;
    }
}
</style>

<script>
let availableSchools = [];

// Load filter options
async function loadOptions() {
    try {
        // Load Schools
        const schoolsRes = await fetch('../api/schools-api.php');
        const schoolsData = await schoolsRes.json();
        if (schoolsData.success) {
            availableSchools = schoolsData.data; // Store for valid usage
            populateSelect('filterSchool', schoolsData.data);
        }

        // Load Modalities
        const modalitiesRes = await fetch('../api/modalities-api.php');
        const modalitiesData = await modalitiesRes.json();
        if (modalitiesData.success) {
            populateSelect('filterModality', modalitiesData.data);
        }

        // Load Categories
        const categoriesRes = await fetch('../api/categories-api.php');
        const categoriesData = await categoriesRes.json();
        if (categoriesData.success) {
            populateSelect('filterCategory', categoriesData.data);
        }
    } catch (error) {
        console.error('Error loading options:', error);
        Toast.error('Erro ao carregar filtros');
    }
}

function populateSelect(id, data) {
    const select = document.getElementById(id);
    data.forEach(item => {
        select.innerHTML += `<option value="${item.id}">${item.name}</option>`;
    });
}

// Generate Report
async function generateReport() {
    const schoolId = document.getElementById('filterSchool').value;
    const params = new URLSearchParams({
        action: 'detailed_report',
        school_id: schoolId,
        modality_id: document.getElementById('filterModality').value,
        category_id: document.getElementById('filterCategory').value,
        gender: document.getElementById('filterGender').value,
        status: document.getElementById('filterStatus').value
    });

    // Update Print Header logic
    updatePrintHeader(schoolId);

    try {
        // Show loading
        document.querySelector('#reportTable tbody').innerHTML = '<tr><td colspan="7" style="text-align: center; padding: 2rem;">Carregando dados...</td></tr>';
        
        const response = await fetch(`../api/reports-api.php?${params.toString()}`);
        const data = await response.json();

        if (data.success) {
            renderReport(data.data);
        } else {
            Toast.error('Erro ao gerar relatório');
        }
    } catch (error) {
        console.error('Error:', error);
        Toast.error('Erro ao gerar relatório');
    }
}

function updatePrintHeader(schoolId) {
    const headerSchool = document.getElementById('printSchoolName');
    const headerDirector = document.getElementById('printDirectorName');
    
    if (schoolId) {
        const school = availableSchools.find(s => s.id == schoolId);
        if (school) {
            headerSchool.textContent = school.name.toUpperCase();
            headerDirector.textContent = school.director ? school.director.toUpperCase() : 'NÃO INFORMADO';
        } else {
            headerSchool.textContent = 'RELATÓRIO GERAL';
            headerDirector.textContent = 'N/A';
        }
    } else {
        // Default header if no school selected
        headerSchool.textContent = 'RELATÓRIO GERAL (TODAS AS ESCOLAS)';
        headerDirector.textContent = '-';
    }
}

function renderReport(data) {
    const tbody = document.querySelector('#reportTable tbody');
    const resultCount = document.getElementById('resultsCount');
    const btnPrint = document.getElementById('btnPrint');

    if (data.length === 0) {
        tbody.innerHTML = '<tr><td colspan="7" style="text-align: center; padding: 3rem; color: var(--text-secondary);">Nenhum registro encontrado com os filtros selecionados.</td></tr>';
        resultCount.textContent = '0 resultados encontrados';
        btnPrint.disabled = true;
        return;
    }

    resultCount.textContent = `${data.length} resultados encontrados`;
    btnPrint.disabled = false;
    tbody.innerHTML = '';

    const genderMap = {'M': 'Masculino', 'F': 'Feminino', 'mixed': 'Misto'};
    const statusMap = {
        'pending': '<span class="badge badge-warning">Pendente</span>', 
        'approved': '<span class="badge badge-success">Aprovada</span>', 
        'rejected': '<span class="badge badge-error">Rejeitada</span>'
    };

    data.forEach(row => {
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td>${row.school_name}</td>
            <td>${row.modality_name}</td>
            <td>${row.category_name}</td>
            <td>${genderMap[row.gender] || row.gender}</td>
            <td>${row.professor_name || 'N/A'}</td>
            <td style="text-align: center;">${row.athlete_count}</td>
            <td>${statusMap[row.status] || row.status}</td>
        `;
        tbody.appendChild(tr);
    });
}

function resetFilters() {
    document.getElementById('filterSchool').value = '';
    document.getElementById('filterModality').value = '';
    document.getElementById('filterCategory').value = '';
    document.getElementById('filterGender').value = '';
    document.getElementById('filterStatus').value = '';
    
    // Clear results
    document.querySelector('#reportTable tbody').innerHTML = '<tr><td colspan="7" style="text-align: center; padding: 3rem; color: var(--text-secondary);">Selecione os filtros e clique em "Gerar Relatório".</td></tr>';
    document.getElementById('resultsCount').textContent = 'Filtros limpos';
    document.getElementById('btnPrint').disabled = true;
    
    // Reset Print Header
    updatePrintHeader('');
}

function printReport() {
    window.print();
}

// Init
loadOptions();
</script>

<?php include '../includes/footer.php'; ?>
