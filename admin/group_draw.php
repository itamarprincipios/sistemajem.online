<?php
require_once '../config/config.php';
require_once '../includes/auth.php';
require_once '../includes/db.php';

requireAdmin();

$eventId = $_GET['event_id'] ?? 0;
$categoryId = $_GET['category_id'] ?? 0;
$gender = $_GET['gender'] ?? 'M';

// Get event and category info
$event = queryOne("SELECT * FROM competition_events WHERE id = ?", [$eventId]);
$category = queryOne("SELECT * FROM categories WHERE id = ?", [$categoryId]);

if (!$event || !$category) {
    header('Location: matches_generator_new.php');
    exit;
}

$genderLabel = $gender === 'M' ? 'Masculino' : 'Feminino';
$pageTitle = "Sorteio de Grupos - {$category['name']} {$genderLabel}";

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<div class="main-content">
    <div class="top-bar">
        <h1 class="top-bar-title"><?php echo htmlspecialchars($pageTitle); ?></h1>
        <a href="matches_generator_new.php" class="btn btn-secondary">← Voltar</a>
    </div>
    
    <div class="content-wrapper">
        <div class="glass-card" style="margin-bottom: 2rem;">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <h2><?php echo htmlspecialchars($event['name']); ?></h2>
                    <p style="color: var(--text-secondary); margin-top: 0.5rem;">
                        <?php echo htmlspecialchars($category['name']); ?> - <?php echo $genderLabel; ?>
                    </p>
                </div>
                <div style="display: flex; gap: 1rem;">
                    <button class="btn btn-primary" onclick="generateGroupsAutomatically()">
                        ⚡ Gerar Fase de Grupos Automaticamente
                    </button>
                    <button class="btn" style="background: linear-gradient(135deg, #8b5cf6 0%, #6366f1 100%); color: white; border: none;" onclick="drawGroups()">
                        🎲 Sortear Fase de Grupos
                    </button>
                </div>
            </div>
        </div>

        <div id="groupsContainer" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 1rem;">
            <!-- Groups will be loaded here -->
        </div>
    </div>
</div>

<script>
const eventId = <?php echo $eventId; ?>;
const categoryId = <?php echo $categoryId; ?>;
const gender = '<?php echo $gender; ?>';

document.addEventListener('DOMContentLoaded', () => {
    loadGroups();
});

async function loadGroups() {
    const container = document.getElementById('groupsContainer');
    container.innerHTML = '<div class="glass-card" style="grid-column: 1 / -1; text-align: center; padding: 3rem;"><p>Carregando grupos...</p></div>';

    try {
        const res = await fetch(`../api/matches-api.php?action=list_groups&event_id=${eventId}&category_id=${categoryId}&gender=${gender}`);
        const result = await res.json();
        
        if (result.success && result.data.length > 0) {
            renderGroups(result.data);
        } else {
            container.innerHTML = `
                <div class="glass-card" style="grid-column: 1 / -1; text-align: center; padding: 3rem;">
                    <h3 style="color: var(--text-secondary); margin-bottom: 1rem;">Nenhum grupo sorteado ainda</h3>
                    <p style="color: var(--text-secondary);">
                        Clique em um dos botões acima para sortear os grupos ou gerar automaticamente a fase de grupos
                    </p>
                </div>
            `;
        }
    } catch (e) {
        console.error(e);
        Toast.error('Erro ao carregar grupos');
    }
}

function renderGroups(groups) {
    const container = document.getElementById('groupsContainer');
    container.innerHTML = '';
    
    groups.forEach((group, index) => {
        const groupCard = document.createElement('div');
        groupCard.className = 'glass-card';
        groupCard.style.padding = '0';
        groupCard.style.overflow = 'hidden';
        
        let teamsHtml = '';
        group.teams.forEach((team, i) => {
            teamsHtml += `
                <div style="display: flex; align-items: center; padding: 0.8rem 1.2rem; border-bottom: 1px solid rgba(255,255,255,0.05); gap: 1rem;">
                    <span style="color: var(--text-secondary); font-size: 0.9rem; min-width: 15px;">${i + 1}</span>
                    <div style="width: 24px; height: 16px; background: rgba(255,255,255,0.1); border-radius: 2px; display: flex; align-items: center; justify-content: center; font-size: 0.6rem;">
                         🏛️
                    </div>
                    <span style="font-weight: 500;">${team.school_name}</span>
                </div>
            `;
        });

        groupCard.innerHTML = `
            <div style="background: rgba(255,255,255,0.03); padding: 0.8rem 1.2rem; border-bottom: 1px solid rgba(255,255,255,0.08);">
                <h3 style="font-size: 1.1rem; color: #fff;">Grupo ${group.group_name}</h3>
                <span style="font-size: 0.75rem; color: var(--text-secondary); text-transform: uppercase;">Equipe</span>
            </div>
            <div style="background: transparent;">
                ${teamsHtml}
            </div>
        `;
        container.appendChild(groupCard);
    });
}

async function generateGroupsAutomatically() {
    if (!confirm('Isso irá sortear os grupos E gerar todas as partidas automaticamente. Continuar?')) return;
    
    Toast.info('Gerando fase de grupos automaticamente...');
    
    try {
        const res = await fetch('../api/matches-api.php?action=generate_group_stage', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                event_id: eventId,
                category_id: categoryId,
                gender: gender
            })
        });
        
        const result = await res.json();
        
        if (result.success) {
            Toast.success(result.message);
            loadGroups();
        } else {
            Toast.error(result.error || 'Erro ao gerar grupos');
        }
    } catch (e) {
        console.error(e);
        Toast.error('Erro na conexão com o servidor');
    }
}

async function drawGroups() {
    // Para esta fase, vamos focar no automático primeiro conforme solicitado
    Toast.info('Esta funcionalidade será integrada no sorteio automático.');
}
</script>

<?php include '../includes/footer.php'; ?>
