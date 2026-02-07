<?php
require_once '../config/config.php';
require_once '../includes/auth.php';
require_once '../includes/db.php';

requireAdmin();

$pageTitle = 'Gerador de Jogos';
include '../includes/header.php';
include '../includes/sidebar.php';
?>

<div class="main-content">
    <div class="top-bar">
        <h1 class="top-bar-title">Gerenciar Partidas</h1>
    </div>
    
    <div class="content-wrapper">
        <div class="glass-card">
            <h2>Geradores Específicos</h2>
            <p class="text-secondary" style="margin-bottom: 1.5rem;">Selecione o gerador de jogos para a modalidade desejada.</p>
            
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1rem;">
                <!-- Futsal Card -->
                <div style="background: rgba(16, 185, 129, 0.1); border: 1px solid rgba(16, 185, 129, 0.2); border-radius: 12px; padding: 1.5rem; text-align: center;">
                    <div style="font-size: 2.5rem; margin-bottom: 1rem;">⚽</div>
                    <h3 style="color: #10b981; margin-bottom: 0.5rem;">Futsal</h3>
                    <p style="color: var(--text-secondary); font-size: 0.9rem; margin-bottom: 1.5rem;">
                        Gerador automático de fase de grupos e chaves para torneios de Futsal.
                    </p>
                    <a href="matches_generator_futsal.php" class="btn btn-primary" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); border: none; width: 100%; display: block;">
                        Acessar Gerador
                    </a>
                </div>

                <!-- Placeholder for other sports -->
                <div style="background: rgba(255, 255, 255, 0.05); border: 1px dashed rgba(255, 255, 255, 0.1); border-radius: 12px; padding: 1.5rem; text-align: center; opacity: 0.5;">
                    <div style="font-size: 2.5rem; margin-bottom: 1rem;">🏐</div>
                    <h3 style="margin-bottom: 0.5rem;">Vôlei</h3>
                    <p style="color: var(--text-secondary); font-size: 0.9rem; margin-bottom: 1.5rem;">
                        Em breve
                    </p>
                    <button class="btn btn-secondary" disabled style="width: 100%;">
                        Indisponível
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>


