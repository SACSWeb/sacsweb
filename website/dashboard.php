<?php
/**
 * SACSWeb Educacional - Dashboard Educacional
 * Sistema para Ensino de Ataques Cibernéticos e Proteções
 * TCC - Foco no Aprendizado
 */

require_once '../config/config.php';

// Verificar se usuário está logado
requireLogin();

// Obter dados do usuário atual
$user = getCurrentUser();

// Obter estatísticas educacionais
try {
    $pdo = connectDatabase();
    
    // Contar módulos disponíveis
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM modulos WHERE ativo = 1");
    $totalModulos = $stmt->fetch()['total'];
    
    // Verificar progresso do usuário
    $stmt = $pdo->prepare("SELECT COUNT(*) as concluidos FROM progresso_usuario WHERE usuario_id = ? AND progresso >= 95");
    $stmt->execute([$user['id']]);
    $modulosConcluidos = $stmt->fetch()['concluidos'];
    
    // Calcular pontuação total
    $stmt = $pdo->prepare("SELECT SUM(pontos_obtidos) as total FROM progresso_usuario WHERE usuario_id = ?");
    $stmt->execute([$user['id']]);
    $pontuacaoTotal = $stmt->fetch()['total'] ?? 0;
    
    // Obter módulos em progresso
    $stmt = $pdo->prepare("
        SELECT m.*, pu.progresso, pu.pontos_obtidos, pu.tempo_gasto, pu.data_inicio
        FROM modulos m 
        LEFT JOIN progresso_usuario pu ON m.id = pu.modulo_id AND pu.usuario_id = ?
        WHERE m.ativo = 1 
        ORDER BY m.ordem ASC
    ");
    $stmt->execute([$user['id']]);
    $modulos = $stmt->fetchAll();
    
    // Obter últimas atividades educacionais
    $stmt = $pdo->prepare("SELECT * FROM logs_atividade WHERE usuario_id = ? ORDER BY data_hora DESC LIMIT 5");
    $stmt->execute([$user['id']]);
    $ultimasAtividades = $stmt->fetchAll();
    
    // Calcular progresso geral
    $progressoGeral = $totalModulos > 0 ? round(($modulosConcluidos / $totalModulos) * 100) : 0;
    
} catch (PDOException $e) {
    logMessage('Erro ao obter estatísticas do dashboard: ' . $e->getMessage(), 'error');
    $totalModulos = 0;
    $modulosConcluidos = 0;
    $pontuacaoTotal = 0;
    $modulos = [];
    $ultimasAtividades = [];
    $progressoGeral = 0;
}

$messages = getFlashMessages();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Educacional - SACSWeb</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        /* Estilos mínimos para complementar o Bootstrap */
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        
        .bg-glass {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .stats-card {
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white;
            transition: transform 0.3s ease;
        }
        
        .stats-card:hover {
            transform: translateY(-5px);
        }
        
        .module-card {
            transition: transform 0.3s ease;
        }
        
        .module-card:hover {
            transform: translateY(-5px);
        }
        
        .activity-icon {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
        }
        
        .icon-login { background: #28a745; color: white; }
        .icon-module { background: #007bff; color: white; }
        .icon-exercise { background: #ffc107; color: white; }
        .icon-quiz { background: #17a2b8; color: white; }
        
        /* Corrigir z-index do dropdown */
        .dropdown-menu {
            z-index: 1050 !important;
        }
        
        /* Garantir que o navbar tenha z-index adequado */
        .navbar {
            z-index: 1030;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg bg-glass navbar-light shadow-sm">
        <div class="container">
            <a class="navbar-brand fw-bold text-primary" href="#">
                <i class="fas fa-shield-alt"></i> SACSWeb Educacional
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="dashboard.php">
                            <i class="fas fa-home"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="modulos.php">
                            <i class="fas fa-book"></i> Módulos de Aprendizado
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="exercicios.php">
                            <i class="fas fa-tasks"></i> Exercícios Práticos
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="progresso.php">
                            <i class="fas fa-chart-line"></i> Meu Progresso
                        </a>
                    </li>
                    <?php if (isAdmin()): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="admin.php">
                            <i class="fas fa-cog"></i> Administração
                        </a>
                    </li>
                    <?php endif; ?>
                </ul>
                
                <div class="navbar-nav">
                    <div class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user-circle"></i> <?= htmlspecialchars($user['nome']) ?>
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="perfil.php">
                                <i class="fas fa-user"></i> Meu Perfil
                            </a></li>
                            <li><a class="dropdown-item" href="configuracoes.php">
                                <i class="fas fa-cog"></i> Configurações
                            </a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="logout.php">
                                <i class="fas fa-sign-out-alt"></i> Sair
                            </a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <!-- Seção de Boas-vindas -->
        <div class="bg-glass rounded-4 p-5 text-center text-dark mb-4">
            <h1 class="display-4 fw-bold mb-3">Bem-vindo ao seu Centro de Aprendizado, <?= htmlspecialchars($user['nome']) ?>!</h1>
            <p class="lead mb-4">Sistema Educacional de Segurança Cibernética</p>
            <div class="row justify-content-center">
                <div class="col-md-6">
                    <p class="mb-0">
                        <i class="fas fa-graduation-cap"></i> 
                        Nível: <strong><?= ucfirst($user['nivel_conhecimento']) ?></strong> | 
                        <i class="fas fa-calendar text-primary"></i> 
                        Membro desde: <?= !empty($user['data_cadastro']) ? formatarData($user['data_cadastro']) : 'Data não disponível' ?>
                    </p>
                </div>
            </div>
        </div>

        <?php if (isset($messages['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <i class="fas fa-check-circle"></i> <?= htmlspecialchars($messages['success']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Estatísticas Educacionais -->
        <div class="row mb-4">
            <div class="col-md-3 mb-3">
                <div class="card stats-card h-100 border-0 shadow">
                    <div class="card-body text-center p-4">
                        <div class="display-4 fw-bold mb-2"><?= $totalModulos ?></div>
                        <div class="h5">Módulos Disponíveis</div>
                        <small class="opacity-75">Para Aprendizado</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card stats-card h-100 border-0 shadow">
                    <div class="card-body text-center p-4">
                        <div class="display-4 fw-bold mb-2"><?= $modulosConcluidos ?></div>
                        <div class="h5">Módulos Concluídos</div>
                        <small class="opacity-75">Conhecimento Adquirido</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card stats-card h-100 border-0 shadow">
                    <div class="card-body text-center p-4">
                        <div class="display-4 fw-bold mb-2"><?= $pontuacaoTotal ?></div>
                        <div class="h5">Pontos Totais</div>
                        <small class="opacity-75">Desempenho Geral</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card stats-card h-100 border-0 shadow">
                    <div class="card-body text-center p-4">
                        <div class="display-4 fw-bold mb-2"><?= $progressoGeral ?>%</div>
                        <div class="h5">Progresso Geral</div>
                        <small class="opacity-75">Jornada de Aprendizado</small>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Módulos de Aprendizado -->
            <div class="col-lg-8">
                <div class="card bg-glass border-0 shadow mb-4">
                    <div class="card-header bg-transparent border-0">
                        <h5 class="mb-0">
                            <i class="fas fa-graduation-cap text-primary"></i> Meus Módulos de Aprendizado
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($modulos)): ?>
                            <div class="row">
                                <?php foreach ($modulos as $modulo): ?>
                                    <?php 
                                    $progresso = 0;
                                    $status = 'not-started';
                                    $statusText = 'Não Iniciado';
                                    
                                    if (isset($modulo['progresso']) && $modulo['progresso'] >= 95) {
                                        $progresso = 100;
                                        $status = 'completed';
                                        $statusText = 'Concluído';
                                    } elseif (isset($modulo['progresso']) && $modulo['progresso'] > 0) {
                                        $progresso = $modulo['progresso'];
                                        $status = 'in-progress';
                                        $statusText = 'Em Progresso';
                                    }
                                    ?>
                                    <div class="col-md-6 mb-3">
                                        <div class="card module-card h-100 border-0 shadow-sm position-relative">
                                            <div class="position-absolute top-0 end-0 m-2">
                                                <span class="badge bg-<?= $status === 'completed' ? 'success' : ($status === 'in-progress' ? 'warning' : 'secondary') ?>" title="<?= $statusText ?>">
                                                    <i class="fas fa-<?= $status === 'completed' ? 'check' : ($status === 'in-progress' ? 'play' : 'circle') ?>"></i>
                                                </span>
                                            </div>
                                            <div class="card-body">
                                                <h6 class="card-title">
                                                    <i class="fas fa-<?= $modulo['tipo_ataque'] === 'SQL Injection' ? 'database' : ($modulo['tipo_ataque'] === 'XSS' ? 'code' : 'shield-alt') ?> text-primary"></i>
                                                    <?= htmlspecialchars($modulo['titulo']) ?>
                                                </h6>
                                                <p class="card-text small text-muted">
                                                    <?= htmlspecialchars($modulo['descricao']) ?>
                                                </p>
                                                
                                                <div class="mb-3">
                                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                                        <small class="text-muted">Progresso</small>
                                                        <small class="text-muted"><?= round($progresso) ?>%</small>
                                                    </div>
                                                    <div class="progress" style="height: 8px;">
                                                        <div class="progress-bar bg-<?= $status === 'completed' ? 'success' : ($status === 'in-progress' ? 'warning' : 'secondary') ?>" 
                                                             style="width: <?= $progresso ?>%"></div>
                                                    </div>
                                                </div>
                                                
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <span class="badge bg-<?= $modulo['nivel'] === 'iniciante' ? 'success' : ($modulo['nivel'] === 'intermediario' ? 'warning' : 'danger') ?>">
                                                        <?= ucfirst($modulo['nivel']) ?>
                                                    </span>
                                                    <a href="modulo.php?id=<?= $modulo['id'] ?>" class="btn btn-sm btn-outline-primary">
                                                        <?php if ($status === 'completed'): ?>
                                                            <i class="fas fa-redo"></i> Revisar
                                                        <?php elseif ($status === 'in-progress'): ?>
                                                            <i class="fas fa-play"></i> Continuar
                                                        <?php else: ?>
                                                            <i class="fas fa-play"></i> Começar
                                                        <?php endif; ?>
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="text-center text-muted py-5">
                                <i class="fas fa-book fa-3x mb-3"></i>
                                <h6>Nenhum módulo disponível</h6>
                                <p>Entre em contato com o administrador para configurar os módulos de aprendizado.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Ações Rápidas -->
                <div class="card bg-glass border-0 shadow">
                    <div class="card-header bg-transparent border-0">
                        <h5 class="mb-0">
                            <i class="fas fa-bolt text-primary"></i> Ações Rápidas para Aprendizado
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <a href="modulos.php" class="btn btn-primary w-100">
                                    <i class="fas fa-book-open"></i> Explorar Módulos
                                </a>
                            </div>
                            <div class="col-md-6 mb-3">
                                <a href="exercicios.php" class="btn btn-success w-100">
                                    <i class="fas fa-tasks"></i> Fazer Exercícios
                                </a>
                            </div>
                            <div class="col-md-6 mb-3">
                                <a href="progresso.php" class="btn btn-info w-100">
                                    <i class="fas fa-chart-line"></i> Ver Progresso Detalhado
                                </a>
                            </div>
                            <div class="col-md-6 mb-3">
                                <a href="ranking.php" class="btn btn-warning w-100">
                                    <i class="fas fa-trophy"></i> Ranking de Alunos
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="col-lg-4">
                <!-- Informações do Usuário -->
                <div class="card bg-glass border-0 shadow mb-4">
                    <div class="card-header bg-transparent border-0">
                        <h6 class="mb-0">
                            <i class="fas fa-user-graduate text-primary"></i> Meu Perfil Educacional
                        </h6>
                    </div>
                    <div class="card-body text-center">
                        <div class="mb-3">
                            <i class="fas fa-user-circle fa-5x text-primary"></i>
                        </div>
                        <h6><?= htmlspecialchars($user['nome']) ?></h6>
                        <p class="text-muted small"><?= htmlspecialchars($user['email']) ?></p>
                        
                        <div class="mb-3">
                            <span class="badge bg-<?= $user['tipo_usuario'] === 'admin' ? 'danger' : 'primary' ?> mb-2">
                                <?= $user['tipo_usuario'] === 'admin' ? 'Administrador' : 'Aluno' ?>
                            </span>
                            <span class="badge bg-info">
                                <?= ucfirst($user['nivel_conhecimento']) ?>
                            </span>
                        </div>
                        
                        <div class="text-start small">
                            <p class="mb-1">
                            <i class="fas fa-calendar text-primary"></i> 
                            Membro desde: <?= !empty($user['data_cadastro']) ? formatarData($user['data_cadastro']) : 'Data não disponível' ?>

                            </p>
                            <p class="mb-0">
                                <i class="fas fa-star text-warning"></i> 
                                Pontuação: <?= $pontuacaoTotal ?> pontos
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Próximos Passos -->
                <div class="card bg-glass border-0 shadow mb-4">
                    <div class="card-header bg-transparent border-0">
                        <h6 class="mb-0">
                            <i class="fas fa-route text-primary"></i> Próximos Passos
                        </h6>
                    </div>
                    <div class="card-body">
                        <?php if ($modulosConcluidos == 0): ?>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle"></i>
                                <strong>Primeiro módulo:</strong> Comece com "Introdução à Segurança Cibernética"
                            </div>
                        <?php elseif ($modulosConcluidos < $totalModulos): ?>
                            <div class="alert alert-success">
                                <i class="fas fa-check-circle"></i>
                                <strong>Continue aprendendo:</strong> Você tem <?= $totalModulos - $modulosConcluidos ?> módulos restantes
                            </div>
                        <?php else: ?>
                            <div class="alert alert-warning">
                                <i class="fas fa-trophy"></i>
                                <strong>Parabéns!</strong> Você completou todos os módulos básicos!
                            </div>
                        <?php endif; ?>
                        
                        <div class="d-grid">
                            <a href="modulos.php" class="btn btn-outline-primary">
                                <i class="fas fa-arrow-right"></i> Continuar Aprendendo
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Atividades Recentes -->
                <div class="card bg-glass border-0 shadow">
                    <div class="card-header bg-transparent border-0">
                        <h6 class="mb-0">
                            <i class="fas fa-history text-primary"></i> Atividades Recentes
                        </h6>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($ultimasAtividades)): ?>
                            <?php foreach ($ultimasAtividades as $atividade): ?>
                                <div class="d-flex align-items-center py-2">
                                    <div class="activity-icon icon-<?= strtolower($atividade['acao']) ?>">
                                        <i class="fas fa-<?= $atividade['acao'] === 'LOGIN' ? 'sign-in-alt' : ($atividade['acao'] === 'MODULO' ? 'book' : 'circle') ?>"></i>
                                    </div>
                                    <div>
                                        <div class="fw-bold"><?= htmlspecialchars($atividade['acao']) ?></div>
                                        <small class="text-muted"><?= formatarData($atividade['data_hora']) ?></small>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="text-center text-muted py-4">
                                <i class="fas fa-inbox fa-3x mb-3"></i>
                                <p>Nenhuma atividade recente</p>
                                <small>Comece explorando os módulos!</small>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Animações e interatividade
        document.addEventListener('DOMContentLoaded', function() {
            // Animar cards ao carregar
            const cards = document.querySelectorAll('.stats-card, .module-card');
            cards.forEach((card, index) => {
                setTimeout(() => {
                    card.style.opacity = '0';
                    card.style.transform = 'translateY(20px)';
                    card.style.transition = 'all 0.6s ease';
                    
                    setTimeout(() => {
                        card.style.opacity = '1';
                        card.style.transform = 'translateY(0)';
                    }, 100);
                }, index * 100);
            });

            // Tooltips para status dos módulos
            const tooltipTriggerList = [].slice.call(document.querySelectorAll('[title]'));
            tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
        });
    </script>
</body>
</html> 