<?php
require_once '../config/config.php';
require_once '../includes/auth.php';
require_once '../includes/db.php';

requireAdmin();

$pageTitle = 'Dashboard - Admin';

// Get statistics
$totalSchools = queryOne("SELECT COUNT(*) as count FROM schools WHERE secretaria_id = ?", [CURRENT_TENANT_ID])['count'] ?? 0;
$totalProfessors = queryOne("SELECT COUNT(*) as count FROM users WHERE role = 'professor' AND secretaria_id = ?", [CURRENT_TENANT_ID])['count'] ?? 0;
$totalStudents = queryOne("SELECT COUNT(*) as count FROM students WHERE secretaria_id = ?", [CURRENT_TENANT_ID])['count'] ?? 0;
$totalRegistrations = queryOne("SELECT COUNT(*) as count FROM registrations WHERE secretaria_id = ?", [CURRENT_TENANT_ID])['count'] ?? 0;
$pendingRegistrations = queryOne("SELECT COUNT(*) as count FROM registrations WHERE status = 'pending' AND secretaria_id = ?", [CURRENT_TENANT_ID])['count'] ?? 0;
$approvedRegistrations = queryOne("SELECT COUNT(*) as count FROM registrations WHERE status = 'approved' AND secretaria_id = ?", [CURRENT_TENANT_ID])['count'] ?? 0;

// Get recent registrations
$recentRegistrations = query("
    SELECT r.*, s.name as school_name, m.name as modality_name, c.name as category_name
    FROM registrations r
    JOIN schools s ON r.school_id = s.id
    JOIN modalities m ON r.modality_id = m.id
    JOIN categories c ON r.category_id = c.id
    WHERE r.secretaria_id = ?
    ORDER BY r.created_at DESC
    LIMIT 5
", [CURRENT_TENANT_ID]);

// Get school summary (Logistics)
$schoolSummary = query("
    SELECT 
        s.name as school_name,
        (SELECT COUNT(*) FROM registrations r WHERE r.school_id = s.id AND r.secretaria_id = s.secretaria_id) as team_count,
        (SELECT COUNT(*) FROM students st WHERE st.school_id = s.id AND st.secretaria_id = s.secretaria_id) as student_count
    FROM schools s
    WHERE s.secretaria_id = ?
    ORDER BY s.name ASC
", [CURRENT_TENANT_ID]);

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<div class="main-content">
    <div class="top-bar">
        <h1 class="top-bar-title">Dashboard</h1>
        <div class="user-menu">
            <div class="user-info">
                <div class="user-name"><?php echo htmlspecialchars(getCurrentUserName()); ?></div>
                <div class="user-role">Administrador</div>
            </div>
        </div>
    </div>
    
    <div class="content-wrapper">
        <!-- Statistics Cards -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.5rem; margin-bottom: 2rem;">
            <div class="glass-card">
                <div style="display: flex; align-items: center; justify-content: space-between;">
                    <div>
                        <div style="color: var(--text-secondary); font-size: 0.875rem; margin-bottom: 0.5rem;">Total de Escolas</div>
                        <div style="font-size: 2rem; font-weight: 700;"><?php echo $totalSchools; ?></div>
                    </div>
                    <div style="font-size: 3rem;">🏫</div>
                </div>
            </div>
            
            <div class="glass-card">
                <div style="display: flex; align-items: center; justify-content: space-between;">
                    <div>
                        <div style="color: var(--text-secondary); font-size: 0.875rem; margin-bottom: 0.5rem;">Professores</div>
                        <div style="font-size: 2rem; font-weight: 700;"><?php echo $totalProfessors; ?></div>
                    </div>
                    <div style="font-size: 3rem;">👨‍🏫</div>
                </div>
            </div>
            
            <div class="glass-card">
                <div style="display: flex; align-items: center; justify-content: space-between;">
                    <div>
                        <div style="color: var(--text-secondary); font-size: 0.875rem; margin-bottom: 0.5rem;">Alunos Cadastrados</div>
                        <div style="font-size: 2rem; font-weight: 700;"><?php echo $totalStudents; ?></div>
                    </div>
                    <div style="font-size: 3rem;">👨‍🎓</div>
                </div>
            </div>
            
            <div class="glass-card">
                <div style="display: flex; align-items: center; justify-content: space-between;">
                    <div>
                        <div style="color: var(--text-secondary); font-size: 0.875rem; margin-bottom: 0.5rem;">Inscrições Pendentes</div>
                        <div style="font-size: 2rem; font-weight: 700; color: var(--warning);"><?php echo $pendingRegistrations; ?></div>
                    </div>
                    <div style="font-size: 3rem;">⏳</div>
                </div>
            </div>
        </div>
        
        <!-- Quick Actions -->
        <div class="glass-card" style="margin-bottom: 2rem;">
            <h2 style="margin-bottom: 1.5rem;">Ações Rápidas</h2>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                <a href="schools.php" class="btn btn-primary">
                    <span>🏫</span>
                    <span>Gerenciar Escolas</span>
                </a>
                <a href="professors.php" class="btn btn-primary">
                    <span>👨‍🏫</span>
                    <span>Gerenciar Professores</span>
                </a>
                <a href="registrations.php" class="btn btn-primary">
                    <span>✅</span>
                    <span>Aprovar Inscrições</span>
                </a>
                <a href="reports.php" class="btn btn-primary">
                    <span>📊</span>
                    <span>Gerar Relatórios</span>
                </a>
            </div>
        </div>
        
        <!-- School Summary (Logistics) -->
        <div class="glass-card" style="margin-bottom: 2rem;">
            <h2 style="margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.75rem;">
                <span>📊</span> Resumo por Escola
            </h2>
            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Escola</th>
                            <th style="text-align: center;">Total de Equipes</th>
                            <th style="text-align: center;">Total de Alunos</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($schoolSummary as $school): ?>
                            <tr>
                                <td style="font-weight: 600;"><?php echo htmlspecialchars($school['school_name']); ?></td>
                                <td style="text-align: center; font-weight: 700; color: var(--primary);">
                                    <?php echo $school['team_count']; ?>
                                </td>
                                <td style="text-align: center; font-weight: 700; color: #6366f1;">
                                    <?php echo $school['student_count']; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Recent Registrations -->
        <div class="glass-card">
            <h2 style="margin-bottom: 1.5rem;">Inscrições Recentes</h2>
            <?php if (empty($recentRegistrations)): ?>
                <p style="color: var(--text-secondary); text-align: center; padding: 2rem;">
                    Nenhuma inscrição encontrada
                </p>
            <?php else: ?>
                <div class="table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Escola</th>
                                <th>Modalidade</th>
                                <th>Categoria</th>
                                <th>Gênero</th>
                                <th>Status</th>
                                <th>Data</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentRegistrations as $reg): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($reg['school_name']); ?></td>
                                    <td><?php echo htmlspecialchars($reg['modality_name']); ?></td>
                                    <td><?php echo htmlspecialchars($reg['category_name']); ?></td>
                                    <td>
                                        <?php 
                                        $genderLabels = ['M' => 'Masculino', 'F' => 'Feminino', 'mixed' => 'Misto'];
                                        echo $genderLabels[$reg['gender']] ?? $reg['gender'];
                                        ?>
                                    </td>
                                    <td>
                                        <?php
                                        $statusClasses = [
                                            'pending' => 'badge-warning',
                                            'approved' => 'badge-success',
                                            'rejected' => 'badge-error'
                                        ];
                                        $statusLabels = [
                                            'pending' => 'Pendente',
                                            'approved' => 'Aprovada',
                                            'rejected' => 'Rejeitada'
                                        ];
                                        ?>
                                        <span class="badge <?php echo $statusClasses[$reg['status']]; ?>">
                                            <?php echo $statusLabels[$reg['status']]; ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('d/m/Y', strtotime($reg['created_at'])); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
