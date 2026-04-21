<?php
require_once '../config/config.php';
require_once '../includes/auth.php';
require_once '../includes/db.php';

requireSuperAdmin();

$pageTitle = 'Dashboard Global';

include '../includes/header.php';
include '../includes/sidebar.php';

// Pegar estatísticas globais
$totalSecretarias = queryOne("SELECT COUNT(*) as count FROM secretarias")['count'];
$totalEscolas = queryOne("SELECT COUNT(*) as count FROM schools")['count'];
$totalAlunos = queryOne("SELECT COUNT(*) as count FROM students")['count'];
?>

<div class="main-content">
    <div class="top-bar">
        <h1 class="top-bar-title">Dashboard Global</h1>
    </div>
    
    <div class="content-wrapper">
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon" style="background: rgba(59, 130, 246, 0.1); color: var(--primary);">🏛️</div>
                <div class="stat-info">
                    <div class="stat-value"><?php echo $totalSecretarias; ?></div>
                    <div class="stat-label">Secretarias</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background: rgba(16, 185, 129, 0.1); color: #10b981;">🏫</div>
                <div class="stat-info">
                    <div class="stat-value"><?php echo $totalEscolas; ?></div>
                    <div class="stat-label">Total de Escolas</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background: rgba(245, 158, 11, 0.1); color: #f59e0b;">👥</div>
                <div class="stat-info">
                    <div class="stat-value"><?php echo $totalAlunos; ?></div>
                    <div class="stat-label">Total de Alunos</div>
                </div>
            </div>
        </div>
        
        <div class="glass-card" style="margin-top: 2rem; padding: 2rem; text-align: center;">
            <h2>Bem-vindo ao Painel Multi-SaaS</h2>
            <p style="color: var(--text-secondary); max-width: 600px; margin: 1rem auto;">
                Este é o centro de controle global do Sistema JEM. Aqui você pode gerenciar todas as 
                Secretarias de Educação, monitorar o uso do sistema e configurar novos clientes.
            </p>
            <div style="margin-top: 2rem;">
                <a href="secretarias.php" class="btn btn-primary">Gerenciar Secretarias</a>
            </div>
        </div>
    </div>
</div>

<style>
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
        gap: 1.5rem;
    }
    .stat-card {
        background: var(--bg-secondary);
        border: 1px solid var(--border);
        border-radius: 1rem;
        padding: 1.5rem;
        display: flex;
        align-items: center;
        gap: 1rem;
    }
    .stat-icon {
        width: 48px;
        height: 48px;
        border-radius: 0.75rem;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
    }
    .stat-value {
        font-size: 1.5rem;
        font-weight: 700;
        color: var(--text-primary);
    }
    .stat-label {
        font-size: 0.875rem;
        color: var(--text-muted);
    }
</style>

<?php include '../includes/footer.php'; ?>
