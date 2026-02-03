<?php
require_once '../config/config.php';
require_once '../includes/auth.php';
require_once '../includes/db.php';
require_once '../includes/knockout_generator.php';

requireLogin(); // Operators can manage knockout stages

$pageTitle = 'Gerenciador de Mata-Mata';
include '../includes/header.php';
include '../includes/sidebar.php';

// Get active event
$activeEvent = queryOne("SELECT id, name FROM competition_events WHERE active_flag = TRUE LIMIT 1");
if (!$activeEvent) {
    die("Nenhum evento ativo encontrado");
}

$eventId = $activeEvent['id'];

// Get modalities and categories
$modalities = query("SELECT id, name FROM modalities ORDER BY name");
$categories = query("SELECT id, name FROM categories ORDER BY name");
?>

<div class="main-content">
    <div class="top-bar">
        <h1 class="top-bar-title">🏆 Gerenciador de Mata-Mata</h1>
    </div>
    
    <div class="content-wrapper">
        <!-- Filter Section -->
        <div class="glass-card" style="margin-bottom: 2rem;">
            <h2>Selecionar Competição</h2>
            <form id="filterForm" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; align-items: end;">
                <div class="form-group">
                    <label class="form-label">Modalidade</label>
                    <select id="modalitySelect" class="form-select" required>
                        <option value="">Selecione...</option>
                        <?php foreach ($modalities as $mod): ?>
                            <option value="<?= $mod['id'] ?>"><?= htmlspecialchars($mod['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Categoria</label>
                    <select id="categorySelect" class="form-select" required>
                        <option value="">Selecione...</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Gênero</label>
                    <select id="genderSelect" class="form-select">
                        <option value="">Todos</option>
                        <option value="M">Masculino</option>
                        <option value="F">Feminino</option>
                    </select>
                </div>
                
                <button type="submit" class="btn btn-primary" style="height: 42px;">🔍 Carregar</button>
            </form>
        </div>

        <!-- Group Standings -->
        <div id="standingsSection" class="glass-card" style="margin-bottom: 2rem; display: none;">
            <h2>📊 Classificação da Fase de Grupos</h2>
            <div id="standingsContent"></div>
        </div>

        <!-- Knockout Generation -->
        <div id="generatorSection" class="glass-card" style="display: none;">
            <h2>⚡ Gerar Próxima Fase</h2>
            <div id="generatorContent"></div>
        </div>

        <!-- Current Knockout Matches -->
        <div id="knockoutMatchesSection" class="glass-card" style="display: none;">
            <h2>🎯 Partidas do Mata-Mata</h2>
            <div id="knockoutMatchesContent"></div>
        </div>
    </div>
</div>

<script>
const eventId = <?= $eventId ?>;

document.getElementById('filterForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    
    const modalityId = document.getElementById('modalitySelect').value;
    const categoryId = document.getElementById('categorySelect').value;
    const gender = document.getElementById('genderSelect').value;
    
    if (!modalityId || !categoryId) {
        alert('Selecione modalidade e categoria');
        return;
    }
    
    await loadStandings(modalityId, categoryId, gender);
    await loadKnockoutStatus(modalityId, categoryId, gender);
});

async function loadStandings(modalityId, categoryId, gender) {
    try {
        const params = new URLSearchParams({
            action: 'standings',
            event_id: eventId,
            modality_id: modalityId,
            category_id: categoryId,
            gender: gender
        });
        
        const res = await fetch(`../api/knockout-api.php?${params}`);
        const data = await res.json();
        
        if (!data.success) {
            alert('Erro ao carregar classificação: ' + data.error);
            return;
        }
        
        displayStandings(data.standings);
    } catch (e) {
        console.error(e);
        alert('Erro ao carregar classificação');
    }
}

function displayStandings(standings) {
    const section = document.getElementById('standingsSection');
    const content = document.getElementById('standingsContent');
    
    if (Object.keys(standings).length === 0) {
        content.innerHTML = '<p class="text-secondary">Nenhuma partida finalizada ainda.</p>';
        section.style.display = 'block';
        return;
    }
    
    let html = '<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1rem;">';
    
    for (const [group, teams] of Object.entries(standings)) {
        html += `
            <div class="glass-card" style="background: rgba(255,255,255,0.05);">
                <h3 style="text-align: center; margin-bottom: 1rem;">Grupo ${group}</h3>
                <table class="data-table" style="font-size: 0.9rem;">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Time</th>
                            <th>P</th>
                            <th>V</th>
                            <th>E</th>
                            <th>D</th>
                            <th>SG</th>
                            <th>Pts</th>
                        </tr>
                    </thead>
                    <tbody>
        `;
        
        teams.forEach(team => {
            const qualified = team.position <= 2 ? 'style="background: rgba(16, 185, 129, 0.2);"' : '';
            html += `
                <tr ${qualified}>
                    <td>${team.position}</td>
                    <td>${team.team_name}</td>
                    <td>${team.played}</td>
                    <td>${team.won}</td>
                    <td>${team.drawn}</td>
                    <td>${team.lost}</td>
                    <td>${team.goal_difference > 0 ? '+' : ''}${team.goal_difference}</td>
                    <td><strong>${team.points}</strong></td>
                </tr>
            `;
        });
        
        html += `
                    </tbody>
                </table>
            </div>
        `;
    }
    
    html += '</div>';
    content.innerHTML = html;
    section.style.display = 'block';
}

async function loadKnockoutStatus(modalityId, categoryId, gender) {
    try {
        const params = new URLSearchParams({
            action: 'knockout_status',
            event_id: eventId,
            modality_id: modalityId,
            category_id: categoryId,
            gender: gender
        });
        
        const res = await fetch(`../api/knockout-api.php?${params}`);
        const data = await res.json();
        
        if (!data.success) {
            alert('Erro ao verificar status: ' + data.error);
            return;
        }
        
        displayGeneratorOptions(data.status, modalityId, categoryId, gender);
        displayKnockoutMatches(data.matches);
    } catch (e) {
        console.error(e);
        alert('Erro ao verificar status do mata-mata');
    }
}

function displayGeneratorOptions(status, modalityId, categoryId, gender) {
    const section = document.getElementById('generatorSection');
    const content = document.getElementById('generatorContent');
    
    if (!status.can_generate) {
        content.innerHTML = `<p class="text-secondary">${status.message}</p>`;
        section.style.display = 'block';
        return;
    }
    
    const phaseNames = {
        'round_of_16': 'Oitavas de Final',
        'quarter_final': 'Quartas de Final',
        'semi_final': 'Semifinal',
        'final': 'Final'
    };
    
    content.innerHTML = `
        <div class="alert alert-success" style="margin-bottom: 1rem;">
            ✅ ${status.message}
        </div>
        <form id="generateForm" style="display: grid; gap: 1rem;">
            <input type="hidden" name="phase" value="${status.next_phase}">
            <input type="hidden" name="modality_id" value="${modalityId}">
            <input type="hidden" name="category_id" value="${categoryId}">
            <input type="hidden" name="gender" value="${gender}">
            
            <div class="form-group">
                <label class="form-label">Data e Hora da Primeira Partida</label>
                <input type="datetime-local" name="datetime" class="form-control" required>
            </div>
            
            <div class="form-group">
                <label class="form-label">Local</label>
                <input type="text" name="venue" class="form-control" placeholder="Ex: Ginásio Municipal" required>
            </div>
            
            <button type="submit" class="btn btn-success">
                🚀 Gerar ${phaseNames[status.next_phase]}
            </button>
        </form>
    `;
    
    document.getElementById('generateForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        await generateKnockoutPhase(new FormData(e.target));
    });
    
    section.style.display = 'block';
}

async function generateKnockoutPhase(formData) {
    if (!confirm('Confirmar geração da próxima fase?')) return;
    
    try {
        formData.append('action', 'generate');
        formData.append('event_id', eventId);
        
        const res = await fetch('../api/knockout-api.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await res.json();
        
        if (data.success) {
            alert(`✅ ${data.matches_created} partidas criadas com sucesso!`);
            location.reload();
        } else {
            alert('Erro: ' + data.error);
        }
    } catch (e) {
        console.error(e);
        alert('Erro ao gerar fase');
    }
}

function displayKnockoutMatches(matches) {
    const section = document.getElementById('knockoutMatchesSection');
    const content = document.getElementById('knockoutMatchesContent');
    
    if (matches.length === 0) {
        section.style.display = 'none';
        return;
    }
    
    const phaseNames = {
        'round_of_16': 'Oitavas de Final',
        'quarter_final': 'Quartas de Final',
        'semi_final': 'Semifinal',
        'third_place': '3º Lugar',
        'final': 'Final'
    };
    
    // Group by phase
    const byPhase = {};
    matches.forEach(m => {
        if (!byPhase[m.phase]) byPhase[m.phase] = [];
        byPhase[m.phase].push(m);
    });
    
    let html = '';
    for (const [phase, phaseMatches] of Object.entries(byPhase)) {
        html += `
            <h3 style="margin-top: 1.5rem;">${phaseNames[phase]}</h3>
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Data/Hora</th>
                            <th>Time A</th>
                            <th>Placar</th>
                            <th>Time B</th>
                            <th>Local</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
        `;
        
        phaseMatches.forEach(match => {
            const statusBadge = {
                'scheduled': '<span class="badge" style="background: #6b7280;">Agendado</span>',
                'live': '<span class="badge" style="background: #ef4444;">AO VIVO</span>',
                'finished': '<span class="badge" style="background: #10b981;">Finalizado</span>'
            };
            
            html += `
                <tr>
                    <td>${new Date(match.scheduled_time).toLocaleString('pt-BR')}</td>
                    <td>${match.team_a_name}</td>
                    <td style="text-align: center; font-weight: bold;">
                        ${match.status === 'finished' ? `${match.score_team_a} x ${match.score_team_b}` : '-'}
                    </td>
                    <td>${match.team_b_name}</td>
                    <td>${match.venue}</td>
                    <td>${statusBadge[match.status] || match.status}</td>
                </tr>
            `;
        });
        
        html += `
                    </tbody>
                </table>
            </div>
        `;
    }
    
    content.innerHTML = html;
    section.style.display = 'block';
}
</script>

<?php include '../includes/footer.php'; ?>
