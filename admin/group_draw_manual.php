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
$pageTitle = "Sorteio Manual - {$category['name']} {$genderLabel}";

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<div class="main-content">
    <div class="top-bar">
        <h1 class="top-bar-title"><?php echo htmlspecialchars($pageTitle); ?></h1>
        <a href="group_draw.php?event_id=<?php echo $eventId; ?>&category_id=<?php echo $categoryId; ?>&gender=<?php echo $gender; ?>" class="btn btn-secondary">← Voltar</a>
    </div>
    
    <div class="content-wrapper" style="display: grid; grid-template-columns: 1fr 300px; gap: 2rem; align-items: start;">
        
        <!-- Main: Groups Grid -->
        <div id="groupsGrid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 1.5rem;">
            <?php 
            $groupNames = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H'];
            foreach ($groupNames as $name): ?>
                <div class="glass-card" style="padding: 0; overflow: hidden;" data-group="<?php echo $name; ?>">
                    <div style="background: rgba(255,255,255,0.03); padding: 0.8rem 1.2rem; border-bottom: 1px solid rgba(255,255,255,0.08);">
                        <h3 style="font-size: 1.1rem; color: #fff;">Grupo <?php echo $name; ?></h3>
                    </div>
                    <div class="group-slots">
                        <?php for ($i = 1; $i <= 4; $i++): ?>
                            <div class="slot empty" onclick="openSelection('<?php echo $name; ?>', <?php echo $i; ?>)" style="display: flex; align-items: center; padding: 0.8rem 1.2rem; border-bottom: 1px solid rgba(255,255,255,0.05); gap: 1rem; cursor: pointer; transition: background 0.2s;">
                                <span style="color: var(--text-secondary); font-size: 0.9rem; min-width: 15px;"><?php echo $i; ?></span>
                                <span class="team-name" style="color: rgba(255,255,255,0.3); font-style: italic;">Clique para sortear...</span>
                            </div>
                        <?php endfor; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Sidebar: Available Teams -->
        <div class="glass-card" style="position: sticky; top: 1rem;">
            <h3 style="margin-bottom: 1rem; font-size: 1.1rem;">Equipes Disponíveis</h3>
            <p style="font-size: 0.8rem; color: var(--text-secondary); margin-bottom: 1rem;">
                Times que ainda não foram sorteados para nenhum grupo.
            </p>
            <div id="availableTeams" style="max-height: calc(100vh - 300px); overflow-y: auto; padding-right: 0.5rem;">
                <p style="color: var(--text-secondary); text-align: center; padding: 1rem;">Carregando...</p>
            </div>
        </div>
    </div>
</div>

<!-- Selection Modal -->
<div class="modal-overlay" id="selectionModal">
    <div class="modal" style="max-width: 400px;">
        <div class="modal-header">
            <h3 class="modal-title">Sorteando para o Grupo <span id="currentGroupLabel"></span></h3>
            <button class="modal-close" onclick="closeSelection()">×</button>
        </div>
        <div class="modal-body">
            <div id="teamListModal" style="display: flex; flex-direction: column; gap: 0.5rem; max-height: 400px; overflow-y: auto;">
                <!-- List of teams -->
            </div>
        </div>
    </div>
</div>

<style>
.slot:hover {
    background: rgba(255,255,255,0.05);
}
.slot.filled {
    cursor: default;
}
.slot.filled:hover {
    background: transparent;
}
.modal-team-item {
    padding: 0.8rem;
    background: rgba(255,255,255,0.05);
    border-radius: 8px;
    cursor: pointer;
    transition: background 0.2s;
}
.modal-team-item:hover {
    background: var(--primary-color);
}
</style>

<script>
const eventId = <?php echo $eventId; ?>;
const categoryId = <?php echo $categoryId; ?>;
const gender = '<?php echo $gender; ?>';

let currentSelection = { group: '', slot: null };
let availableTeams = [];
let assignedTeams = [];

document.addEventListener('DOMContentLoaded', () => {
    refreshData();
});

async function refreshData() {
    await Promise.all([
        loadAvailableTeams(),
        loadExistingAssignments()
    ]);
}

async function loadAvailableTeams() {
    try {
        const res = await fetch(`../api/matches-api.php?action=list_available_teams&event_id=${eventId}&category_id=${categoryId}&gender=${gender}`);
        const result = await res.json();
        if (result.success) {
            availableTeams = result.data;
            renderAvailableSidebar();
        }
    } catch (e) {
        console.error(e);
    }
}

async function loadExistingAssignments() {
    try {
        const res = await fetch(`../api/matches-api.php?action=list_groups&event_id=${eventId}&category_id=${categoryId}&gender=${gender}`);
        const result = await res.json();
        if (result.success) {
            assignedTeams = result.data;
            renderGrid();
        }
    } catch (e) {
        console.error(e);
    }
}

function renderAvailableSidebar() {
    const container = document.getElementById('availableTeams');
    if (availableTeams.length === 0) {
        container.innerHTML = '<p style="color: var(--text-secondary); text-align: center; padding: 1rem;">Todas as equipes já foram sorteadas.</p>';
        return;
    }

    container.innerHTML = availableTeams.map(team => `
        <div style="padding: 0.6rem; background: rgba(255,255,255,0.03); border-radius: 6px; margin-bottom: 0.5rem; font-size: 0.9rem; border: 1px solid rgba(255,255,255,0.05);">
            🏛️ ${team.school_name}
        </div>
    `).join('');
}

function renderGrid() {
    // Reset all slots
    document.querySelectorAll('.slot').forEach(slot => {
        slot.className = 'slot empty';
        slot.querySelector('.team-name').innerText = 'Clique para sortear...';
        slot.querySelector('.team-name').style.color = 'rgba(255,255,255,0.3)';
        slot.querySelector('.team-name').style.fontStyle = 'italic';
    });

    // Fill slots based on data
    assignedTeams.forEach(group => {
        const groupCard = document.querySelector(`[data-group="${group.group_name}"]`);
        if (!groupCard) return;

        const slots = groupCard.querySelectorAll('.slot');
        group.teams.forEach((team, i) => {
            if (slots[i]) {
                slots[i].className = 'slot filled';
                slots[i].querySelector('.team-name').innerText = team.school_name;
                slots[i].querySelector('.team-name').style.color = '#fff';
                slots[i].querySelector('.team-name').style.fontStyle = 'normal';
                slots[i].onclick = null; // Disable clicking
            }
        });
    });
}

function openSelection(group, slot) {
    if (availableTeams.length === 0) {
        Toast.warn('Não há equipes disponíveis para sorteio.');
        return;
    }

    currentSelection = { group, slot };
    document.getElementById('currentGroupLabel').innerText = group;
    
    const listContainer = document.getElementById('teamListModal');
    listContainer.innerHTML = availableTeams.map(team => `
        <div class="modal-team-item" onclick="assignTeam(${team.id})">
            🏛️ ${team.school_name}
        </div>
    `).join('');

    document.getElementById('selectionModal').style.display = 'flex';
}

function closeSelection() {
    document.getElementById('selectionModal').style.display = 'none';
}

async function assignTeam(teamId) {
    closeSelection();
    Toast.info('Registrando equipe...');

    try {
        const res = await fetch('../api/matches-api.php?action=assign_team_group', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                team_id: teamId,
                group_name: currentSelection.group
            })
        });
        
        const result = await res.json();
        if (result.success) {
            Toast.success(result.message);
            await refreshData();
        } else {
            Toast.error(result.message || 'Erro ao registrar');
        }
    } catch (e) {
        console.error(e);
        Toast.error('Erro na conexão');
    }
}
</script>

<?php include '../includes/footer.php'; ?>
