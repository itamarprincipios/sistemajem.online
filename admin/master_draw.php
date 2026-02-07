<?php
require_once '../config/config.php';
require_once '../includes/auth.php';
require_once '../includes/db.php';

requireAdmin();

$pageTitle = "Sorteio Mestre (Geral) - JEM 2026";
include '../includes/header.php';
include '../includes/sidebar.php';
?>

<div class="main-content">
    <div class="top-bar">
        <h1 class="top-bar-title">🏆 Sorteio Mestre do Evento</h1>
        <div style="display: flex; gap: 1rem;">
            <select id="eventSelector" class="form-input" style="width: 250px; margin: 0; background: rgba(255,255,255,0.05); color: #fff; border: 1px solid rgba(255,255,255,0.1);">
                <option value="">Selecione o Evento...</option>
            </select>
            <button class="btn btn-secondary" onclick="window.location.href='matches_generator_futsal.php'">← Voltar</button>
        </div>
    </div>
    
    <div class="content-wrapper" id="drawContainer" style="display: none;">
        <!-- Header Info & Actions -->
        <div class="glass-card" style="margin-bottom: 2rem; display: flex; justify-content: space-between; align-items: center; border-left: 4px solid #FFD700;">
            <div>
                <h2 id="eventNameTitle">Carregando...</h2>
                <p style="color: var(--text-secondary);">O sorteio realizado aqui define os grupos para TODAS as categorias deste evento.</p>
            </div>
            <button class="btn" style="background: #ef4444; color: white; border: none;" onclick="clearMasterDraw()">
                🗑️ Reiniciar Sorteio Geral
            </button>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 320px; gap: 2rem; align-items: start;">
            
            <!-- Groups Grid -->
            <div id="groupsGrid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 1.5rem;">
                <?php 
                $groupNames = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H'];
                foreach ($groupNames as $name): ?>
                    <div class="glass-card group-card" style="padding: 0; overflow: hidden; border: 1px solid rgba(255,255,255,0.05);" data-group="<?php echo $name; ?>">
                        <div class="group-header" style="background: linear-gradient(90deg, rgba(255,215,0,0.1) 0%, rgba(255,255,255,0.03) 100%); padding: 1rem 1.5rem; border-bottom: 1px solid rgba(255,255,255,0.08);">
                            <h3 style="font-size: 1.2rem; color: #FFD700; margin: 0;">Grupo <?php echo $name; ?></h3>
                        </div>
                        <div class="group-slots" style="min-height: 100px; padding-bottom: 0.5rem;">
                            <!-- Assigned schools here -->
                            <div class="drop-zone" ondragover="event.preventDefault()" ondrop="handleDrop(event, '<?php echo $name; ?>')" style="padding: 1.5rem; text-align: center; border: 2px dashed rgba(255,215,0,0.1); margin: 0.8rem; border-radius: 12px; color: rgba(255,255,255,0.2); font-size: 0.85rem;">
                                Arraste uma escola para cá
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Sidebar: Available Schools -->
            <div class="glass-card" style="position: sticky; top: 1rem; border-top: 4px solid #3b82f6;">
                <h3 style="margin-bottom: 1rem; font-size: 1.1rem; display: flex; align-items: center; gap: 0.5rem;">
                    🏫 Escolas no Evento
                </h3>
                <div style="margin-bottom: 1rem;">
                    <input type="text" id="schoolSearch" class="form-input" placeholder="Buscar escola..." style="font-size: 0.85rem; padding: 0.6rem;">
                </div>
                <div id="availableSchools" style="max-height: calc(100vh - 350px); overflow-y: auto; padding-right: 0.5rem;">
                    <p style="color: var(--text-secondary); text-align: center; padding: 1rem;">Selecione um evento para listar as escolas.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Empty State -->
    <div id="emptyState" style="padding: 5rem 2rem; text-align: center;">
        <div style="font-size: 4rem; margin-bottom: 1.5rem;">🏆</div>
        <h2 style="color: #fff; margin-bottom: 1rem;">Sorteio Mestre do Evento</h2>
        <p style="color: var(--text-secondary); max-width: 500px; margin: 0 auto 2rem auto;">
            Selecione um evento acima para começar o sorteio mestre. 
            Essa ferramenta permite definir os grupos globais das instituições de uma só vez.
        </p>
    </div>
</div>

<style>
.group-card {
    transition: all 0.3s ease;
}
.group-card:hover {
    border-color: rgba(255,215,0,0.3);
    box-shadow: 0 10px 30px rgba(0,0,0,0.3);
}
.school-item {
    padding: 0.8rem 1rem;
    background: rgba(255,255,255,0.03);
    border: 1px solid rgba(255,255,255,0.05);
    border-radius: 10px;
    margin-bottom: 0.6rem;
    cursor: grab;
    transition: all 0.2s;
    font-size: 0.9rem;
    display: flex;
    align-items: center;
    gap: 0.8rem;
}
.school-item:hover {
    background: rgba(255,255,255,0.08);
    border-color: #3b82f6;
    transform: translateX(4px);
}
.school-item.dragging {
    opacity: 0.5;
}
.assigned-school-item {
    margin: 0.5rem 0.8rem;
    padding: 0.7rem 1rem;
    background: rgba(255,215,0,0.05);
    border: 1px solid rgba(255,215,0,0.15);
    border-radius: 8px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    animation: slideIn 0.3s ease-out;
}
@keyframes slideIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}
.remove-btn {
    background: transparent;
    border: none;
    color: #ef4444;
    cursor: pointer;
    font-size: 1.1rem;
    padding: 0 0.5rem;
    opacity: 0.6;
}
.remove-btn:hover { opacity: 1; }

.drop-zone.active {
    background: rgba(255,215,0,0.08) !important;
    border-color: #FFD700 !important;
    color: #FFD700 !important;
}
</style>

<script>
const selector = document.getElementById('eventSelector');
const drawContainer = document.getElementById('drawContainer');
const emptyState = document.getElementById('emptyState');
let schools = [];

document.addEventListener('DOMContentLoaded', () => {
    loadEvents();
    selector.addEventListener('change', handleEventChange);
});

async function loadEvents() {
    try {
        const res = await fetch('../api/competition-events-api.php?action=list');
        const data = await res.json();
        if (data.success) {
            data.data.forEach(event => {
                const opt = document.createElement('option');
                opt.value = event.id;
                opt.textContent = event.name;
                selector.appendChild(opt);
            });
        }
    } catch (e) { console.error(e); }
}

async function handleEventChange() {
    const eventId = selector.value;
    if (!eventId) {
        drawContainer.style.display = 'none';
        emptyState.style.display = 'block';
        return;
    }

    drawContainer.style.display = 'grid';
    emptyState.style.display = 'none';
    document.getElementById('eventNameTitle').innerText = selector.options[selector.selectedIndex].text;
    
    await loadSchools(eventId);
}

async function loadSchools(eventId) {
    try {
        const res = await fetch(`../api/matches-api.php?action=list_schools_for_master_draw&event_id=${eventId}`);
        const result = await res.json();
        if (result.success) {
            schools = result.data;
            renderAll();
        }
    } catch (e) {
        console.error(e);
        Toast.error('Erro ao carregar escolas');
    }
}

function renderAll() {
    renderSidebar();
    renderGroups();
}

function renderSidebar() {
    const container = document.getElementById('availableSchools');
    const available = schools.filter(s => !s.assigned_group);
    
    if (available.length === 0) {
        container.innerHTML = '<p style="color: var(--text-secondary); text-align: center; padding: 1rem;">Nenhuma escola pendente de sorteio.</p>';
        return;
    }

    container.innerHTML = available.map(school => `
        <div class="school-item" draggable="true" ondragstart="handleDragStart(event, ${school.id}, '${school.school_name.replace(/'/g, "\\'")}')">
            <span>🏛️</span>
            <span style="flex: 1;">${school.school_name}</span>
        </div>
    `).join('');
}

function renderGroups() {
    // Clear assigned areas and check counts
    document.querySelectorAll('.group-slots').forEach(div => {
        const groupName = div.parentElement.dataset.group;
        const currentCount = schools.filter(s => s.assigned_group === groupName).length;
        
        let dropZone = '';
        if (currentCount < 4) {
            dropZone = `<div class="drop-zone" ondragover="event.preventDefault(); this.classList.add('active')" ondragleave="this.classList.remove('active')" ondrop="this.classList.remove('active'); handleDrop(event, '${groupName}')" style="padding: 1.5rem; text-align: center; border: 2px dashed rgba(255,215,0,0.1); margin: 0.8rem; border-radius: 12px; color: rgba(255,255,255,0.2); font-size: 0.85rem;">Arraste uma escola para cá</div>`;
        } else {
            dropZone = `<div style="padding: 1rem; text-align: center; color: #10b981; font-size: 0.8rem; font-weight: bold; background: rgba(16,185,129,0.05); border-radius: 8px; margin: 0.8rem;">✅ Grupo Completo</div>`;
        }
        div.innerHTML = dropZone;
    });

    const assigned = schools.filter(s => s.assigned_group);
    assigned.forEach(school => {
        const groupDiv = document.querySelector(`[data-group="${school.assigned_group}"] .group-slots`);
        if (groupDiv) {
            const item = document.createElement('div');
            item.className = 'assigned-school-item';
            item.innerHTML = `
                <span style="font-weight: 500;">🏛️ ${school.school_name}</span>
                <button class="remove-btn" onclick="removeFromGroup(${school.id})">×</button>
            `;
            groupDiv.prepend(item);
        }
    });
}

function handleDragStart(e, id, name) {
    e.dataTransfer.setData('schoolId', id);
    e.dataTransfer.setData('schoolName', name);
    e.target.classList.add('dragging');
}

async function handleDrop(e, group) {
    const schoolId = e.dataTransfer.getData('schoolId');
    const eventId = selector.value;
    
    // Check limit (4 schools per group)
    const schoolCount = schools.filter(s => s.assigned_group === group).length;
    if (schoolCount >= 4) {
        Toast.warn('Este grupo já está completo (limite de 4 escolas).');
        return;
    }
    
    // Save to backend
    await assignSchoolToGroup(eventId, schoolId, group);
}

async function assignSchoolToGroup(eventId, schoolId, group) {
    try {
        const res = await fetch('../api/matches-api.php?action=assign_school_master_group', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ event_id: eventId, school_id: schoolId, group_name: group })
        });
        const result = await res.json();
        if (result.success) {
            // Update local state and re-render
            const school = schools.find(s => s.id == schoolId);
            if (school) school.assigned_group = group;
            renderAll();
            Toast.success(result.message);
        }
    } catch (e) { console.error(e); Toast.error('Erro ao salvar'); }
}

async function removeFromGroup(schoolId) {
    const eventId = selector.value;
    try {
        const res = await fetch('../api/matches-api.php?action=assign_school_master_group', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ event_id: eventId, school_id: schoolId, group_name: null })
        });
        const result = await res.json();
        if (result.success) {
            const school = schools.find(s => s.id == schoolId);
            if (school) school.assigned_group = null;
            renderAll();
        }
    } catch (e) { console.error(e); }
}

async function clearMasterDraw() {
    if (!confirm('ATENÇÃO: Isso removerá TODOS os grupos de todas as categorias deste evento. Continuar?')) return;
    const eventId = selector.value;
    
    try {
        const res = await fetch('../api/matches-api.php?action=clear_master_draw', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ event_id: eventId })
        });
        const result = await res.json();
        if (result.success) {
            schools.forEach(s => s.assigned_group = null);
            renderAll();
            Toast.success(result.message);
        }
    } catch (e) { console.error(e); }
}
</script>

<?php include '../includes/footer.php'; ?>
