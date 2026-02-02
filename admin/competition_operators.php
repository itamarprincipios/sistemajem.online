<?php
require_once '../config/config.php';
require_once '../includes/auth.php';
require_once '../includes/db.php';

requireAdmin();

$pageTitle = 'Gestão de Operadores';
include '../includes/header.php';
include '../includes/sidebar.php';
?>

<div class="main-content">
    <div class="top-bar">
        <h1 class="top-bar-title">Operadores de Jogos</h1>
    </div>
    
    <div class="content-wrapper">
        <div class="glass-card" style="margin-bottom: 2rem;">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <h2>Equipe de Campo</h2>
                    <p style="color: var(--text-secondary);">Cadastre pessoas para operar as partidas (súmula eletrônica)</p>
                </div>
                <button class="btn btn-primary" onclick="openCreateModal()">+ Novo Operador</button>
            </div>
        </div>

        <div class="table-container glass-card">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Nome</th>
                        <th>Email</th>
                        <th>Evento Atribuído</th>
                        <th>Permissão (Modalidade/Local)</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody id="operatorsTable">
                    <!-- Loaded dynamically -->
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Create -->
<div class="modal-overlay" id="createModal">
    <div class="modal">
        <div class="modal-header">
            <h3 class="modal-title">Novo Operador</h3>
            <button class="modal-close" onclick="closeCreateModal()">×</button>
        </div>
        <div class="modal-body">
            <form id="createForm" onsubmit="handleCreate(event)">
                <div class="form-group">
                    <label class="form-label">Nome Completo</label>
                    <input type="text" name="name" class="form-input" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Email (Login)</label>
                    <input type="email" name="email" class="form-input" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Senha Inicial</label>
                    <input type="password" name="password" class="form-input" required minlength="6">
                </div>
                
                <hr style="border-color: var(--border); margin: 1.5rem 0;">
                
                <div class="form-group">
                    <label class="form-label">Evento</label>
                    <select name="competition_event_id" id="eventSelect" class="form-select" required>
                        <option value="">Carregando...</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Restringir Modalidade (Opcional)</label>
                    <select name="assigned_modality_id" id="modalitySelect" class="form-select">
                        <option value="">Todas as modalidades</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Restringir Local (Opcional)</label>
                    <input type="text" name="assigned_venue" class="form-input" placeholder="Ex: Ginásio Principal">
                </div>

                <button type="submit" class="btn btn-primary" style="width: 100%;">Cadastrar Operador</button>
            </form>
        </div>
    </div>
</div>

<script>
async function loadData() {
    try {
        // Load Operators
        const resOp = await fetch('../api/competition-operators-api.php?action=list');
        const dataOp = await resOp.json();
        
        // Load Events for Select
        const resEv = await fetch('../api/competition-operators-api.php?action=events');
        const dataEv = await resEv.json();
        
        // Load Modalities for Select
        const resMod = await fetch('../api/competition-operators-api.php?action=modalities');
        const dataMod = await resMod.json();
        
        renderTable(dataOp.data);
        populateSelects(dataEv.data, dataMod.data);
        
    } catch (e) {
        console.error(e);
        Toast.error('Erro ao carregar dados');
    }
}

function renderTable(operators) {
    const tbody = document.getElementById('operatorsTable');
    tbody.innerHTML = '';
    
    if (operators.length === 0) {
        tbody.innerHTML = '<tr><td colspan="5" style="text-align:center; padding: 2rem;">Nenhum operador cadastrado</td></tr>';
        return;
    }
    
    operators.forEach(op => {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td style="font-weight: 500;">${op.name}</td>
            <td>${op.email}</td>
            <td><span class="badge badge-info">${op.event_name}</span></td>
            <td>
                ${op.modality_name ? `<span class="badge">${op.modality_name}</span>` : '<span class="badge">Todas</span>'}
                ${op.assigned_venue ? `<span class="badge">${op.assigned_venue}</span>` : ''}
            </td>
            <td>
                <button class="btn btn-sm btn-danger" onclick="deleteOperator(${op.id})">Remover</button>
            </td>
        `;
        tbody.appendChild(row);
    });
}

function populateSelects(events, modalities) {
    const eventSelect = document.getElementById('eventSelect');
    eventSelect.innerHTML = '<option value="">Selecione o Evento</option>';
    events.forEach(ev => {
        eventSelect.innerHTML += `<option value="${ev.id}">${ev.name}</option>`;
    });

    const modSelect = document.getElementById('modalitySelect');
    modSelect.innerHTML = '<option value="">Todas as modalidades</option>';
    modalities.forEach(mod => {
        modSelect.innerHTML += `<option value="${mod.id}">${mod.name}</option>`;
    });
}

async function handleCreate(e) {
    e.preventDefault();
    const formData = new FormData(e.target);
    const data = Object.fromEntries(formData);
    
    try {
        const res = await fetch('../api/competition-operators-api.php?action=create', {
            method: 'POST',
            body: JSON.stringify(data)
        });
        const result = await res.json();
        
        if (result.success) {
            Toast.success('Operador cadastrado!');
            closeCreateModal();
            loadData();
            e.target.reset();
        } else {
            Toast.error(result.error);
        }
    } catch (err) {
        Toast.error('Erro ao cadastrar');
    }
}

async function deleteOperator(id) {
    if (!confirm('Remover acesso deste operador?')) return;
    try {
        const res = await fetch(`../api/competition-operators-api.php?action=list&id=${id}`, { method: 'DELETE' }); // Note: GET params on DELETE is standard, but some servers block body
        // Actually fetch implementation uses $_GET['id'] for delete logic so we append query string.
        const delRes = await fetch('../api/competition-operators-api.php?id=' + id, { method: 'DELETE' });
        
        if ((await delRes.json()).success) {
            Toast.success('Operador removido');
            loadData();
        }
    } catch (e) {
        Toast.error('Erro ao remover');
    }
}

function openCreateModal() { document.getElementById('createModal').classList.add('active'); }
function closeCreateModal() { document.getElementById('createModal').classList.remove('active'); }

loadData();
</script>

<?php include '../includes/footer.php'; ?>
