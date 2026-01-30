<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema JEM - Jogos Escolares Municipais</title>
    <link rel="stylesheet" href="assets/css/styles.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        .hero {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }
        
        .hero::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(59, 130, 246, 0.1) 0%, transparent 70%);
            animation: rotate 20s linear infinite;
        }
        
        @keyframes rotate {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        
        .hero-content {
            position: relative;
            z-index: 1;
            text-align: center;
            max-width: 800px;
            padding: 2rem;
        }
        
        .hero-title {
            font-size: 4rem;
            font-weight: 800;
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 1.5rem;
            animation: fadeInUp 0.8s ease;
        }
        
        .hero-subtitle {
            font-size: 1.5rem;
            color: var(--text-secondary);
            margin-bottom: 2rem;
            animation: fadeInUp 0.8s ease 0.2s both;
        }
        
        .hero-buttons {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
            animation: fadeInUp 0.8s ease 0.4s both;
        }
        
        .features {
            padding: 4rem 2rem;
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            margin-top: 3rem;
        }
        
        .feature-card {
            text-align: center;
            animation: fadeInUp 0.8s ease;
        }
        
        .feature-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
        }
        
        .feature-title {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        
        .feature-description {
            color: var(--text-secondary);
        }
        
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        @media (max-width: 768px) {
            .hero-title {
                font-size: 2.5rem;
            }
            
            .hero-subtitle {
                font-size: 1.25rem;
            }
        }
    </style>
</head>
<body>
    <div class="hero">
        <div class="hero-content">
            <img src="assets/images/ncircuits-logo.png" alt="N Circuits Technologies" style="max-width: 250px; margin-bottom: 2rem; animation: fadeInUp 0.8s ease;">
            <h1 class="hero-title">Sistema JEM</h1>
            <p class="hero-subtitle">
                Gerenciamento completo dos Jogos Escolares Municipais
            </p>
            <div class="hero-buttons">
                <a href="login.php" class="btn btn-primary btn-lg">Acessar Sistema</a>
                <a href="register.php" class="btn btn-secondary btn-lg">Solicitar Acesso</a>
            </div>
        </div>
    </div>
    
    <div class="features">
        <h2 class="text-center" style="font-size: 2.5rem; margin-bottom: 1rem;">Funcionalidades</h2>
        <p class="text-center" style="color: var(--text-secondary); font-size: 1.25rem;">
            Tudo que você precisa para gerenciar os jogos escolares
        </p>
        
        <div class="features-grid">
            <div class="feature-card glass-card">
                <div class="feature-icon">🏫</div>
                <h3 class="feature-title">Gestão de Escolas</h3>
                <p class="feature-description">
                    Cadastre e gerencie todas as escolas participantes com facilidade
                </p>
            </div>
            
            <div class="feature-card glass-card">
                <div class="feature-icon">👨‍🏫</div>
                <h3 class="feature-title">Portal do Professor</h3>
                <p class="feature-description">
                    Professores podem cadastrar alunos e criar inscrições de equipes
                </p>
            </div>
            
            <div class="feature-card glass-card">
                <div class="feature-icon">⚽</div>
                <h3 class="feature-title">Modalidades Esportivas</h3>
                <p class="feature-description">
                    Gerencie múltiplas modalidades e categorias de idade
                </p>
            </div>
            
            <div class="feature-card glass-card">
                <div class="feature-icon">👥</div>
                <h3 class="feature-title">Cadastro de Atletas</h3>
                <p class="feature-description">
                    Sistema completo para registro de alunos atletas
                </p>
            </div>
            
            <div class="feature-card glass-card">
                <div class="feature-icon">✅</div>
                <h3 class="feature-title">Aprovação de Inscrições</h3>
                <p class="feature-description">
                    Processo organizado de revisão e aprovação de equipes
                </p>
            </div>
            
            <div class="feature-card glass-card">
                <div class="feature-icon">📊</div>
                <h3 class="feature-title">Relatórios Completos</h3>
                <p class="feature-description">
                    Gere relatórios detalhados e exporte dados em CSV
                </p>
            </div>
        </div>
    </div>
    
    <footer style="background: linear-gradient(135deg, rgba(15, 23, 42, 0.95) 0%, rgba(30, 41, 59, 0.95) 100%); padding: 4rem 2rem 2rem; margin-top: 4rem; border-top: 1px solid rgba(59, 130, 246, 0.2);">
        <div style="max-width: 1200px; margin: 0 auto;">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 3rem; margin-bottom: 3rem;">
                <!-- Logo e Descrição -->
                <div style="text-align: center;">
                    <img src="assets/images/ncircuits-logo.png" alt="N Circuits Technologies" style="max-width: 200px; margin-bottom: 1.5rem; filter: brightness(1.1);">
                    <h3 style="color: #fff; font-size: 1.5rem; font-weight: 700; margin-bottom: 1rem;">N Circuits Technologies</h3>
                    <p style="color: var(--text-secondary); line-height: 1.6; margin-bottom: 1.5rem;">
                        Somos especializados no desenvolvimento de aplicações web personalizadas, criadas para resolver problemas reais e atender às particularidades de cada cliente. Atuamos com soluções modernas, seguras e escaláveis.
                    </p>
                    <a href="https://wa.me/5595991248941" target="_blank" class="btn btn-primary" style="display: inline-flex; align-items: center; gap: 0.5rem; text-decoration: none;">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/>
                        </svg>
                        Fale Conosco
                    </a>
                </div>
                
                <!-- Serviços -->
                <div style="text-align: left;">
                    <h4 style="color: #fff; font-size: 1.25rem; font-weight: 600; margin-bottom: 1.5rem; border-bottom: 2px solid var(--primary); padding-bottom: 0.5rem; display: inline-block;">Nossos Serviços</h4>
                    <ul style="list-style: none; padding: 0; margin: 0; color: var(--text-secondary); line-height: 2;">
                        <li style="padding-left: 1.5rem; position: relative; margin-bottom: 0.5rem;">
                            <span style="position: absolute; left: 0; color: var(--primary);">✓</span>
                            Desenvolvimento de sites profissionais
                        </li>
                        <li style="padding-left: 1.5rem; position: relative; margin-bottom: 0.5rem;">
                            <span style="position: absolute; left: 0; color: var(--primary);">✓</span>
                            Sistemas online sob medida
                        </li>
                        <li style="padding-left: 1.5rem; position: relative; margin-bottom: 0.5rem;">
                            <span style="position: absolute; left: 0; color: var(--primary);">✓</span>
                            Aplicações web responsivas
                        </li>
                        <li style="padding-left: 1.5rem; position: relative; margin-bottom: 0.5rem;">
                            <span style="position: absolute; left: 0; color: var(--primary);">✓</span>
                            Painéis administrativos e dashboards
                        </li>
                        <li style="padding-left: 1.5rem; position: relative; margin-bottom: 0.5rem;">
                            <span style="position: absolute; left: 0; color: var(--primary);">✓</span>
                            Integração com APIs e automações
                        </li>
                    </ul>
                </div>
                
                <!-- Mais Serviços -->
                <div style="text-align: left;">
                    <h4 style="color: #fff; font-size: 1.25rem; font-weight: 600; margin-bottom: 1.5rem; border-bottom: 2px solid var(--primary); padding-bottom: 0.5rem; display: inline-block;">Soluções Especializadas</h4>
                    <ul style="list-style: none; padding: 0; margin: 0; color: var(--text-secondary); line-height: 2;">
                        <li style="padding-left: 1.5rem; position: relative; margin-bottom: 0.5rem;">
                            <span style="position: absolute; left: 0; color: var(--primary);">✓</span>
                            Lojas virtuais (E-commerce)
                        </li>
                        <li style="padding-left: 1.5rem; position: relative; margin-bottom: 0.5rem;">
                            <span style="position: absolute; left: 0; color: var(--primary);">✓</span>
                            Sistemas de gestão (ERP, CRM, escolar, etc.)
                        </li>
                        <li style="padding-left: 1.5rem; position: relative; margin-bottom: 0.5rem;">
                            <span style="position: absolute; left: 0; color: var(--primary);">✓</span>
                            Otimização de desempenho e SEO
                        </li>
                        <li style="padding-left: 1.5rem; position: relative; margin-bottom: 0.5rem;">
                            <span style="position: absolute; left: 0; color: var(--primary);">✓</span>
                            Hospedagem, implantação e suporte contínuo
                        </li>
                        <li style="padding-left: 1.5rem; position: relative; margin-bottom: 0.5rem;">
                            <span style="position: absolute; left: 0; color: var(--primary);">✓</span>
                            Consultoria em tecnologia e transformação digital
                        </li>
                    </ul>
                </div>
            </div>
            
            <!-- Copyright -->
            <div style="text-align: center; padding-top: 2rem; border-top: 1px solid rgba(255, 255, 255, 0.1);">
                <p style="color: var(--text-muted); margin: 0;">
                    &copy; 2024 Sistema JEM - Jogos Escolares Municipais | Desenvolvido por <strong style="color: var(--primary);">N Circuits Technologies</strong>
                </p>
            </div>
        </div>
    </footer>
</body>
</html>
