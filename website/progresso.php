<?php
/**
 * SACSWeb Educacional - Página de Meu Progresso
 * Exibe gráficos e estatísticas do progresso do usuário
 * Versão: 2.1.0
 */

require_once '../config/config.php';
requireLogin();

$user = getCurrentUser();
$userPreferences = getUserPreferences($user['id'] ?? null);
$pdo = connectDatabase();

// Buscar estatísticas do progresso
try {
    // Estatísticas gerais
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(DISTINCT m.id) as total_modulos,
            COUNT(DISTINCT CASE WHEN p.progresso >= 95 THEN m.id END) as modulos_concluidos,
            COUNT(DISTINCT CASE WHEN p.progresso > 0 AND p.progresso < 95 THEN m.id END) as modulos_em_andamento,
            SUM(COALESCE(p.pontos_obtidos, 0)) as total_pontos,
            AVG(COALESCE(p.progresso, 0)) as progresso_medio
        FROM modulos m
        LEFT JOIN progresso_usuario p ON m.id = p.modulo_id AND p.usuario_id = ?
        WHERE m.ativo = 1
    ");
    $stmt->execute([$user['id']]);
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Progresso por nível
    $stmt = $pdo->prepare("
        SELECT 
            m.nivel,
            COUNT(DISTINCT m.id) as total,
            COUNT(DISTINCT CASE WHEN p.progresso >= 95 THEN m.id END) as concluidos,
            AVG(COALESCE(p.progresso, 0)) as progresso_medio
        FROM modulos m
        LEFT JOIN progresso_usuario p ON m.id = p.modulo_id AND p.usuario_id = ?
        WHERE m.ativo = 1
        GROUP BY m.nivel
    ");
    $stmt->execute([$user['id']]);
    $progresso_por_nivel = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Progresso ao longo do tempo (últimos 30 dias)
    $stmt = $pdo->prepare("
        SELECT 
            DATE(data_inicio) as data,
            COUNT(*) as modulos_iniciados,
            SUM(pontos_obtidos) as pontos_dia
        FROM progresso_usuario
        WHERE usuario_id = ? 
        AND data_inicio >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        GROUP BY DATE(data_inicio)
        ORDER BY data ASC
    ");
    $stmt->execute([$user['id']]);
    $progresso_temporal = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Módulos mais recentes
    if ($tem_campos_quiz) {
        $stmt = $pdo->prepare("
            SELECT m.*, p.progresso, p.data_inicio, p.data_conclusao, p.pontos_obtidos, 
                   p.questoes_totais, p.questoes_acertadas, p.porcentagem_acertos
            FROM modulos m
            INNER JOIN progresso_usuario p ON m.id = p.modulo_id
            WHERE p.usuario_id = ?
            ORDER BY p.data_inicio DESC
            LIMIT 10
        ");
    } else {
        $stmt = $pdo->prepare("
            SELECT m.*, p.progresso, p.data_inicio, p.data_conclusao, p.pontos_obtidos, 
                   0 as questoes_totais, 0 as questoes_acertadas, 0 as porcentagem_acertos
            FROM modulos m
            INNER JOIN progresso_usuario p ON m.id = p.modulo_id
            WHERE p.usuario_id = ?
            ORDER BY p.data_inicio DESC
            LIMIT 10
        ");
    }
    $stmt->execute([$user['id']]);
    $modulos_recentes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Estatísticas de exercícios
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(DISTINCT e.id) as total_exercicios,
            COUNT(DISTINCT CASE WHEN p.progresso >= 95 THEN e.id END) as exercicios_concluidos
        FROM exercicios e
        INNER JOIN modulos m ON e.modulo_id = m.id
        LEFT JOIN progresso_usuario p ON m.id = p.modulo_id AND p.usuario_id = ?
        WHERE e.ativo = 1
    ");
    $stmt->execute([$user['id']]);
    $stats_exercicios = $stmt->fetch(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    logMessage('Erro ao buscar progresso: ' . $e->getMessage(), 'error');
    $stats = ['total_modulos' => 0, 'modulos_concluidos' => 0, 'modulos_em_andamento' => 0, 'total_pontos' => 0, 'progresso_medio' => 0, 'total_acertos' => 0, 'total_questoes' => 0, 'porcentagem_acertos_geral' => 0];
    $progresso_por_nivel = [];
    $progresso_temporal = [];
    $modulos_recentes = [];
    $stats_exercicios = ['total_exercicios' => 0, 'exercicios_concluidos' => 0];
    $historico_quizzes = [];
    $tem_campos_quiz = false;
}

// Verificar se campos de quiz existem (para uso no template)
if (!isset($tem_campos_quiz)) {
    try {
        $stmt = $pdo->query("SHOW COLUMNS FROM progresso_usuario LIKE 'questoes_totais'");
        $tem_campos_quiz = $stmt->rowCount() > 0;
    } catch (PDOException $e) {
        $tem_campos_quiz = false;
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meu Progresso - <?= SISTEMA_NOME ?></title>
    <link rel="icon" type="image/png" href="<?= ASSETS_URL ?>/images/icone.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="<?= ASSETS_URL ?>/css/sacsweb-unified.css" rel="stylesheet">
    <script>
        window.SACSWEB_PREFERENCES = <?= json_encode($userPreferences, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    </script>
    <script src="<?= ASSETS_URL ?>/js/preferences.js" defer></script>
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    
    <style>
        .stats-card {
            background: linear-gradient(135deg, var(--primary-pink), var(--accent-blue));
            color: white;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 20px;
            transition: transform 0.3s ease;
        }
        
        .stats-card:hover {
            transform: translateY(-5px);
        }
        
        .progress-section {
            background: var(--dark-gray);
            border: 1px solid var(--light-gray);
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 20px;
        }
        
        .progress-section h5 {
            color: var(--primary-pink);
            margin-bottom: 20px;
            font-weight: 600;
        }
        
        .chart-container {
            position: relative;
            height: 300px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body class="page-progresso">
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg bg-glass navbar-light shadow-sm">
        <div class="container">
            <a class="navbar-brand fw-bold text-primary" href="dashboard.php">
                <img src="<?= ASSETS_URL ?>/images/icone.png" alt="SACSWeb Logo" style="height: 40px; margin-right: 10px;"> <span class="text-primary">SACSWeb</span> <span>Educacional</span>
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard.php">
                            <i class="fas fa-home"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="modulos.php">
                            <i class="fas fa-book"></i> Módulos
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="exercicios.php">
                            <i class="fas fa-tasks"></i> Exercícios
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="progresso.php">
                            <i class="fas fa-chart-line"></i> Meu Progresso
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="ranking.php">
                            <i class="fas fa-trophy"></i> Ranking
                        </a>
                    </li>
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

    <div class="container mt-4 mb-5">
        <!-- Header -->
        <div class="bg-glass rounded-4 p-5 text-center mb-4">
            <h1 class="display-5 fw-bold mb-3">
                <i class="fas fa-chart-line text-primary"></i> Meu Progresso
            </h1>
            <p class="lead">Acompanhe sua jornada de aprendizado</p>
        </div>

        <!-- Estatísticas Principais -->
        <div class="row mb-4">
            <div class="col-md-3 mb-3">
                <div class="stats-card text-center">
                    <div class="display-4 fw-bold mb-2"><?= $stats['total_modulos'] ?></div>
                    <div class="h5">Módulos Disponíveis</div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="stats-card text-center">
                    <div class="display-4 fw-bold mb-2"><?= $stats['modulos_concluidos'] ?></div>
                    <div class="h5">Módulos Concluídos</div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="stats-card text-center">
                    <div class="display-4 fw-bold mb-2"><?= number_format($stats['progresso_medio'], 1) ?>%</div>
                    <div class="h5">Progresso Médio</div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="stats-card text-center">
                    <div class="display-4 fw-bold mb-2"><?= $stats['total_pontos'] ?></div>
                    <div class="h5">Pontos Totais</div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Gráfico de Progresso por Nível -->
            <div class="col-lg-6 mb-4">
                <div class="progress-section">
                    <h5><i class="fas fa-chart-pie"></i> Progresso por Nível</h5>
                    <div class="chart-container">
                        <canvas id="nivelChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Gráfico de Progresso Temporal -->
            <div class="col-lg-6 mb-4">
                <div class="progress-section">
                    <h5><i class="fas fa-chart-line"></i> Progresso nos Últimos 30 Dias</h5>
                    <div class="chart-container">
                        <canvas id="temporalChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Módulos Recentes -->
        <div class="progress-section">
            <h5><i class="fas fa-history"></i> Módulos Recentes</h5>
            <?php if (!empty($modulos_recentes)): ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Módulo</th>
                                <th>Nível</th>
                                <th>Progresso</th>
                                <th>Pontos</th>
                                <th>Status</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($modulos_recentes as $modulo): ?>
                                <tr>
                                    <td><?= htmlspecialchars($modulo['titulo']) ?></td>
                                    <td>
                                        <span class="badge bg-<?= $modulo['nivel'] === 'iniciante' ? 'success' : ($modulo['nivel'] === 'intermediario' ? 'warning' : 'danger') ?>">
                                            <?= ucfirst($modulo['nivel']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="progress" style="height: 20px;">
                                            <div class="progress-bar" role="progressbar" 
                                                 style="width: <?= $modulo['progresso'] ?>%">
                                                <?= number_format($modulo['progresso'], 1) ?>%
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <?php if ($modulo['questoes_totais'] > 0): ?>
                                            <span class="badge bg-<?= $modulo['porcentagem_acertos'] >= 80 ? 'success' : ($modulo['porcentagem_acertos'] >= 60 ? 'warning' : 'danger') ?>">
                                                <?= $modulo['questoes_acertadas'] ?>/<?= $modulo['questoes_totais'] ?> (<?= $modulo['porcentagem_acertos'] ?>%)
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= $modulo['pontos_obtidos'] ?? 0 ?></td>
                                    <td>
                                        <?php if ($modulo['progresso'] >= 95): ?>
                                            <span class="badge bg-success">Concluído</span>
                                        <?php elseif ($modulo['progresso'] > 0): ?>
                                            <span class="badge bg-warning">Em Andamento</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Não Iniciado</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="modulo.php?id=<?= $modulo['id'] ?>" class="btn btn-sm btn-primary">
                                            <i class="fas fa-eye"></i> Ver
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="text-center">Nenhum módulo iniciado ainda. <a href="modulos.php">Comece agora!</a></p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Gráfico de Progresso por Nível
        const nivelCtx = document.getElementById('nivelChart').getContext('2d');
        const nivelChart = new Chart(nivelCtx, {
            type: 'doughnut',
            data: {
                labels: <?= json_encode(array_column($progresso_por_nivel, 'nivel')) ?>,
                datasets: [{
                    data: <?= json_encode(array_column($progresso_por_nivel, 'progresso_medio')) ?>,
                    backgroundColor: [
                        'rgba(40, 167, 69, 0.8)',
                        'rgba(255, 193, 7, 0.8)',
                        'rgba(220, 53, 69, 0.8)'
                    ],
                    borderColor: [
                        'rgba(40, 167, 69, 1)',
                        'rgba(255, 193, 7, 1)',
                        'rgba(220, 53, 69, 1)'
                    ],
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            color: '#e0e0e0'
                        }
                    }
                }
            }
        });

        // Gráfico de Progresso Temporal
        const temporalCtx = document.getElementById('temporalChart').getContext('2d');
        const progressoTemporal = <?= json_encode($progresso_temporal) ?>;
        const labels = progressoTemporal.map(item => new Date(item.data).toLocaleDateString('pt-BR'));
        const pontos = progressoTemporal.map(item => item.pontos_dia);
        
        const temporalChart = new Chart(temporalCtx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Pontos Obtidos',
                    data: pontos,
                    borderColor: 'rgba(255, 20, 147, 1)',
                    backgroundColor: 'rgba(255, 20, 147, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        labels: {
                            color: '#e0e0e0'
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            color: '#e0e0e0'
                        },
                        grid: {
                            color: 'rgba(255, 255, 255, 0.1)'
                        }
                    },
                    x: {
                        ticks: {
                            color: '#e0e0e0'
                        },
                        grid: {
                            color: 'rgba(255, 255, 255, 0.1)'
                        }
                    }
                }
            }
        });
    </script>
</body>
</html>

