<?php
require_once '../config/config.php';
require_once '../includes/auth.php';
require_once '../includes/db.php';

requireAdmin();

$pageTitle = 'Gerador de Jogos - Society';
include '../includes/header.php';
include '../includes/sidebar.php';
?>

<div class="main-content">
    <div class="top-bar">
        <h1 class="top-bar-title">Gerador de Jogos Society</h1>
        <a href="matches_generator.php" class="btn btn-secondary">← Voltar</a>
    </div>
    
    <div class="content-wrapper">
        <div class="glass-card" style="margin-bottom: 2rem; display: flex; justify-content: space-between; align-items: center; border-left: 4px solid #3b82f6;">
            <div>
                <h2>Eventos de Society</h2>
                <p style="color: var(--text-secondary);">Selecione uma categoria para sortear os grupos.</p>
            </div>
        </div>

        <div id="eventsContainer">
            <!-- Events will be loaded here -->
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    loadEvents();
});

async function loadEvents() {
    try {
        const res = await fetch('../api/competition-events-api.php?action=list');
        const data = await res.json();
        
        if (data.success && data.data.length > 0) {
            // Filter only society events or events that have society teams
            const societyEvents = data.data.filter(e => e.name.toLowerCase().includes('society'));
            if (societyEvents.length > 0) {
                renderEvents(societyEvents);
            } else {
                renderEvents(data.data); // Fallback to all if none match specifically
            }
        } else {
            document.getElementById('eventsContainer').innerHTML = `
                <div class="glass-card" style="text-align: center; padding: 3rem;">
                    <p style="color: var(--text-secondary);">Nenhum evento cadastrado</p>
                </div>
            `;
        }
    } catch (e) {
        console.error(e);
        Toast.error('Erro ao carregar eventos');
    }
}

async function renderEvents(events) {
    const container = document.getElementById('eventsContainer');
    container.innerHTML = '';
    
    let renderedCount = 0;
    for (const event of events) {
        // Get categories for this event
        const categoriesRes = await fetch(`../api/matches-api.php?action=options&event_id=${event.id}`);
        const categoriesData = await categoriesRes.json();
        
        if (!categoriesData.success || categoriesData.data.categories.length === 0) {
            continue;
        }

        // Check if there is Society modality in this event
        const hasSociety = categoriesData.data.modalities.some(m => m.name.toLowerCase().includes('society'));
        if (!hasSociety && events.length > 1) continue; 
        
        renderedCount++;
        // Create event section
        const eventSection = document.createElement('div');
        eventSection.className = 'glass-card';
        eventSection.style.marginBottom = '2rem';
        
        eventSection.innerHTML = `
            <h3 style="margin-bottom: 1.5rem;">${event.name}</h3>
            <div id="categories-${event.id}" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 1rem;">
                <!-- Categories will be loaded here -->
            </div>
        `;
        
        container.appendChild(eventSection);
        
        // Render category cards
        renderCategoryCards(event.id, categoriesData.data.categories);
    }

    if (renderedCount === 0) {
        container.innerHTML = `
            <div class="glass-card" style="text-align: center; padding: 3rem;">
                <p style="color: var(--text-secondary);">Nenhum evento com times de Society encontrado.</p>
            </div>
        `;
    }
}

async function renderCategoryCards(eventId, categories) {
    const container = document.getElementById(`categories-${eventId}`);
    
    for (const category of categories) {
        // Get team counts for this category
        const teamsRes = await fetch(`../api/matches-api.php?action=team_counts&event_id=${eventId}&category_id=${category.id}`);
        const teamsData = await teamsRes.json();
        
        const maleCount = teamsData.data?.male || 0;
        const femaleCount = teamsData.data?.female || 0;
        
        const color = '#3b82f6'; // Society Blue
        
        // Masculine card
        if (maleCount > 0) {
            const maleCard = createCategoryCard(eventId, category, 'M', maleCount, color);
            container.appendChild(maleCard);
        }
        
        // Feminine card
        if (femaleCount > 0) {
            const femaleCard = createCategoryCard(eventId, category, 'F', femaleCount, '#ec4899');
            container.appendChild(femaleCard);
        }
    }
}

function createCategoryCard(eventId, category, gender, teamCount, color) {
    const card = document.createElement('div');
    card.className = 'glass-card';
    card.style.cursor = 'pointer';
    card.style.transition = 'transform 0.2s, box-shadow 0.2s';
    card.style.border = `2px solid ${color}40`;
    
    card.onmouseenter = () => {
        card.style.transform = 'translateY(-4px)';
        card.style.boxShadow = `0 8px 24px ${color}40`;
    };
    
    card.onmouseleave = () => {
        card.style.transform = 'translateY(0)';
        card.style.boxShadow = '';
    };
    
    const genderIcon = gender === 'M' ? '♂️' : '♀️';
    const genderLabel = gender === 'M' ? 'Masculino' : 'Feminino';
    
    card.innerHTML = `
        <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 1rem;">
            <div style="font-size: 2rem; background: ${color}20; width: 50px; height: 50px; border-radius: 12px; display: flex; align-items: center; justify-content: center;">
                ${genderIcon}
            </div>
            <div style="flex: 1;">
                <h4 style="margin: 0; color: ${color};">${category.name} ${genderLabel}</h4>
                <p style="margin: 0.25rem 0 0 0; color: var(--text-secondary); font-size: 0.875rem;">
                    ${teamCount} equipes cadastradas
                </p>
            </div>
        </div>
        <div style="padding: 0.75rem; background: rgba(0,0,0,0.2); border-radius: 8px; margin-bottom: 1rem;">
            <div style="font-size: 0.875rem; color: var(--text-secondary);">Status do Sorteio</div>
            <div style="font-weight: bold; color: ${color};">Aguardando sorteio</div>
        </div>
        <button class="btn btn-primary" style="width: 100%; background: ${color}; border: none;" onclick="openGroupDraw(${eventId}, ${category.id}, '${gender}')">
            🎲 Sortear Grupos
        </button>
    `;
    
    return card;
}

function openGroupDraw(eventId, categoryId, gender) {
    window.location.href = `group_draw.php?event_id=${eventId}&category_id=${categoryId}&gender=${gender}`;
}
</script>

<?php include '../includes/footer.php'; ?>
