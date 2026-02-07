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
            <h2>Gerador</h2>
            <p class="text-secondary" style="margin-bottom: 1.5rem;">Selecione o gerador de jogos para a modalidade desejada.</p>
            
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1rem;">
                <!-- Futsal Card -->
                <div style="background: rgba(16, 185, 129, 0.1); border: 1px solid rgba(16, 185, 129, 0.2); border-radius: 12px; padding: 2rem; text-align: center;">
                    <div style="font-size: 3rem; margin-bottom: 1rem;">⚽</div>
                    <h3 style="color: #10b981; margin-bottom: 1rem;">Gerador de jogos futsal</h3>
                    <p style="color: var(--text-secondary); font-size: 0.95rem; margin-bottom: 2rem; max-width: 300px; margin-left: auto; margin-right: auto;">
                        Gerador automático de fase de grupos e chaves para torneios de Futsal.
                    </p>
                    <a href="matches_generator_futsal.php" class="btn btn-primary" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); border: none; width: 100%; display: block; padding: 0.8rem;">
                        Acessar Gerador
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>


