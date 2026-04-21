<?php
/**
 * Sidebar Navigation Component
 */

$currentPage = basename($_SERVER['PHP_SELF']);
$userRole = getCurrentUserRole();
?>
<style>
    .sidebar {
        width: 260px;
        background: var(--bg-secondary);
        border-right: 1px solid var(--border);
        position: fixed;
        top: 0;
        left: 0;
        height: 100vh;
        overflow-y: auto;
        z-index: 200;
    }
    
    .sidebar-header {
        padding: 1.5rem;
        border-bottom: 1px solid var(--border);
    }
    
    .sidebar-logo {
        font-size: 1.5rem;
        font-weight: 800;
        background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        text-decoration: none;
    }
    
    .sidebar-nav {
        padding: 1rem 0;
    }
    
    .nav-section {
        margin-bottom: 1.5rem;
    }
    
    .nav-section-title {
        padding: 0.5rem 1.5rem;
        font-size: 0.75rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: var(--text-muted);
    }
    
    .nav-link {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        padding: 0.75rem 1.5rem;
        color: var(--text-secondary);
        text-decoration: none;
        transition: all var(--transition-fast);
        position: relative;
    }
    
    .nav-link:hover {
        background: rgba(255, 255, 255, 0.05);
        color: var(--text-primary);
    }
    
    .nav-link.active {
        background: rgba(59, 130, 246, 0.1);
        color: var(--primary);
        font-weight: 600;
    }
    
    .nav-link.active::before {
        content: '';
        position: absolute;
        left: 0;
        top: 0;
        bottom: 0;
        width: 3px;
        background: var(--primary);
    }
    
    .nav-icon {
        font-size: 1.25rem;
    }
    
    @media (max-width: 768px) {
        .sidebar {
            transform: translateX(-100%);
            transition: transform var(--transition-base);
        }
        
        .sidebar.open {
            transform: translateX(0);
        }
    }
</style>

<aside class="sidebar">
    <div class="sidebar-header">
        <a href="<?php echo SITE_URL; ?>" class="sidebar-logo">Sistema JEM</a>
    </div>
    
    <nav class="sidebar-nav">
        <?php if ($userRole === 'super_admin'): ?>
            <!-- Super Admin Navigation -->
            <div class="nav-section">
                <div class="nav-section-title">Global</div>
                <a href="<?php echo SITE_URL; ?>/superadmin/" 
                   class="nav-link <?php echo ($currentPage === 'index.php' && strpos($_SERVER['REQUEST_URI'], 'superadmin') !== false) ? 'active' : ''; ?>">
                    <span class="nav-icon">🗺️</span>
                    <span>Dashboard Global</span>
                </a>
                <a href="<?php echo SITE_URL; ?>/superadmin/secretarias.php" 
                   class="nav-link <?php echo $currentPage === 'secretarias.php' ? 'active' : ''; ?>">
                    <span class="nav-icon">🏛️</span>
                    <span>Secretarias</span>
                </a>
            </div>
            
            <div class="nav-section">
                <div class="nav-section-title">Sistema</div>
                <a href="<?php echo SITE_URL; ?>/admin/reports.php" 
                   class="nav-link">
                    <span class="nav-icon">📉</span>
                    <span>Logs Globais</span>
                </a>
            </div>

        <?php elseif ($userRole === 'admin'): ?>
            <!-- Admin Navigation -->
            <div class="nav-section">
                <div class="nav-section-title">Principal</div>
                <a href="<?php echo SITE_URL; ?>/admin/dashboard.php" 
                   class="nav-link <?php echo $currentPage === 'dashboard.php' ? 'active' : ''; ?>">
                    <span class="nav-icon">📊</span>
                    <span>Dashboard</span>
                </a>
            </div>
            
            <div class="nav-section">
                <div class="nav-section-title">Gerenciamento</div>
                <a href="<?php echo SITE_URL; ?>/admin/schools.php" 
                   class="nav-link <?php echo $currentPage === 'schools.php' ? 'active' : ''; ?>">
                    <span class="nav-icon">🏫</span>
                    <span>Escolas</span>
                </a>
                <a href="<?php echo SITE_URL; ?>/admin/categories.php" 
                   class="nav-link <?php echo $currentPage === 'categories.php' ? 'active' : ''; ?>">
                    <span class="nav-icon">📋</span>
                    <span>Categorias</span>
                </a>
                <a href="<?php echo SITE_URL; ?>/admin/modalities.php" 
                   class="nav-link <?php echo $currentPage === 'modalities.php' ? 'active' : ''; ?>">
                    <span class="nav-icon">⚽</span>
                    <span>Modalidades</span>
                </a>
                <a href="<?php echo SITE_URL; ?>/admin/professors.php" 
                   class="nav-link <?php echo $currentPage === 'professors.php' ? 'active' : ''; ?>">
                    <span class="nav-icon">👨‍🏫</span>
                    <span>Professores</span>
                </a>
            </div>

            <div class="nav-section">
                <div class="nav-section-title">Competição</div>
                <a href="<?php echo SITE_URL; ?>/admin/competition_events.php" 
                   class="nav-link <?php echo $currentPage === 'competition_events.php' ? 'active' : ''; ?>">
                    <span class="nav-icon">🏆</span>
                    <span>Eventos</span>
                </a>
                <a href="<?php echo SITE_URL; ?>/admin/competition_operators.php" 
                   class="nav-link <?php echo $currentPage === 'competition_operators.php' ? 'active' : ''; ?>">
                    <span class="nav-icon">🛂</span>
                    <span>Operadores</span>
                </a>
                <a href="<?php echo SITE_URL; ?>/admin/matches_generator.php" 
                   class="nav-link <?php echo $currentPage === 'matches_generator.php' ? 'active' : ''; ?>">
                    <span class="nav-icon">⚔️</span>
                    <span>Gerar Jogos</span>
                </a>
            </div>
            
            <div class="nav-section">
                <div class="nav-section-title">Inscrições</div>
                <a href="<?php echo SITE_URL; ?>/admin/registrations.php" 
                   class="nav-link <?php echo $currentPage === 'registrations.php' ? 'active' : ''; ?>">
                    <span class="nav-icon">✅</span>
                    <span>Aprovar Inscrições</span>
                </a>
                <a href="<?php echo SITE_URL; ?>/admin/teams.php" 
                   class="nav-link <?php echo $currentPage === 'teams.php' ? 'active' : ''; ?>">
                    <span class="nav-icon">👥</span>
                    <span>Equipes</span>
                </a>
            </div>
            
            <div class="nav-section">
                <div class="nav-section-title">Relatórios</div>
                <a href="<?php echo SITE_URL; ?>/admin/reports.php" 
                   class="nav-link <?php echo $currentPage === 'reports.php' ? 'active' : ''; ?>">
                    <span class="nav-icon">📈</span>
                    <span>Relatórios</span>
                </a>
            </div>
            
        <?php elseif ($userRole === 'operator'): ?>
            <!-- Operator Navigation -->
            <div class="nav-section">
                <div class="nav-section-title">Principal</div>
                <a href="<?php echo SITE_URL; ?>/operator/dashboard.php" 
                   class="nav-link <?php echo $currentPage === 'dashboard.php' ? 'active' : ''; ?>">
                    <span class="nav-icon">📊</span>
                    <span>Minhas Partidas</span>
                </a>
            </div>
            
            <div class="nav-section">
                <div class="nav-section-title">Competição</div>
                <a href="<?php echo SITE_URL; ?>/operator/knockout_manager.php" 
                   class="nav-link <?php echo $currentPage === 'knockout_manager.php' ? 'active' : ''; ?>">
                    <span class="nav-icon">🏆</span>
                    <span>Mata-Mata</span>
                </a>
                <a href="<?php echo SITE_URL; ?>/operator/knockout_manual.php" 
                   class="nav-link <?php echo $currentPage === 'knockout_manual.php' ? 'active' : ''; ?>">
                    <span class="nav-icon">✏️</span>
                    <span>Mata-Mata Manual</span>
                </a>
            </div>
            
        <?php elseif ($userRole === 'professor'): ?>
            <!-- Professor Navigation -->
            <div class="nav-section">
                <div class="nav-section-title">Principal</div>
                <a href="<?php echo SITE_URL; ?>/professor/dashboard.php" 
                   class="nav-link <?php echo $currentPage === 'dashboard.php' ? 'active' : ''; ?>">
                    <span class="nav-icon">📊</span>
                    <span>Dashboard</span>
                </a>
            </div>
            
            <div class="nav-section">
                <div class="nav-section-title">Gestão</div>
                <a href="<?php echo SITE_URL; ?>/professor/students.php" 
                   class="nav-link <?php echo $currentPage === 'students.php' ? 'active' : ''; ?>">
                    <span class="nav-icon">➕</span>
                    <span>Cadastrar Aluno</span>
                </a>
                <a href="<?php echo SITE_URL; ?>/professor/teams.php" 
                   class="nav-link <?php echo $currentPage === 'teams.php' ? 'active' : ''; ?>">
                    <span class="nav-icon">👥</span>
                    <span>Minhas Equipes</span>
                </a>
            </div>
        <?php endif; ?>
        
        <div class="nav-section">
            <div class="nav-section-title">Conta</div>
            <a href="<?php echo SITE_URL; ?>/logout.php" class="nav-link">
                <span class="nav-icon">🚪</span>
                <span>Sair</span>
            </a>
        </div>
    </nav>
</aside>
