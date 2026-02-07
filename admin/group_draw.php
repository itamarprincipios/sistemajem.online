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
    // TODO: Load existing groups or show empty state
    const container = document.getElementById('groupsContainer');
    container.innerHTML = `
        <div class="glass-card" style="grid-column: 1 / -1; text-align: center; padding: 3rem;">
            <h3 style="color: var(--text-secondary); margin-bottom: 1rem;">Nenhum grupo sorteado ainda</h3>
            <p style="color: var(--text-secondary);">
                Clique em um dos botões acima para sortear os grupos ou gerar automaticamente a fase de grupos
            </p>
        </div>
    `;
}

async function generateGroupsAutomatically() {
    if (!confirm('Isso irá sortear os grupos E gerar todas as partidas automaticamente. Continuar?')) return;
    
    Toast.info('Gerando fase de grupos automaticamente...');
    
    // TODO: Implement automatic generation
    Toast.success('Funcionalidade em desenvolvimento');
}

async function drawGroups() {
    if (!confirm('Isso irá sortear aleatoriamente os grupos. Continuar?')) return;
    
    Toast.info('Sorteando grupos...');
    
    // TODO: Implement group drawing
    Toast.success('Funcionalidade em desenvolvimento');
}
</script>

<?php include '../includes/footer.php'; ?>
