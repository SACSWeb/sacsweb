<?php
require_once '../config/config.php';
requireLogin();

$user = getCurrentUser();
$pdo = connectDatabase();

// Buscar exercícios organizados por tipo e módulo
$stmt = $pdo->prepare("
    SELECT e.*, m.titulo as modulo_titulo, m.nivel as modulo_nivel,
           COALESCE(p.progresso, 0) as progresso_usuario,
           p.data_inicio,
           p.data_conclusao,
           p.pontos_obtidos
    FROM exercicios e 
    INNER JOIN modulos m ON e.modulo_id = m.id
    LEFT JOIN progresso_usuario p ON e.modulo_id = p.modulo_id AND p.usuario_id = ?
    WHERE e.ativo = 1
    ORDER BY m.nivel, m.ordem, e.ordem
");
$stmt->execute([$user['id']]);
$exercicios = $stmt->fetchAll();

// Organizar exercícios por tipo
$exercicios_por_tipo = [
    'quiz' => [],
    'codigo' => [],
    'teorico' => []
];

foreach ($exercicios as $exercicio) {
    $exercicios_por_tipo[$exercicio['tipo']][] = $exercicio;
}

// Buscar estatísticas dos exercícios
$stmt = $pdo->prepare("
    SELECT 
        COUNT(DISTINCT e.id) as total_exercicios,
        COUNT(DISTINCT CASE WHEN p.data_conclusao IS NOT NULL THEN e.id END) as exercicios_concluidos,
        SUM(COALESCE(p.pontos_obtidos, 0)) as total_pontos_exercicios,
        AVG(COALESCE(p.progresso, 0)) as progresso_medio_exercicios
    FROM exercicios e
    LEFT JOIN progresso_usuario p ON e.modulo_id = p.modulo_id AND p.usuario_id = ?
    WHERE e.ativo = 1
");
$stmt->execute([$user['id']]);
$stats_exercicios = $stmt->fetch();

// Buscar exercícios recomendados baseados no nível do usuário
$stmt = $pdo->prepare("
    SELECT e.*, m.titulo as modulo_titulo, m.nivel as modulo_nivel
    FROM exercicios e 
    INNER JOIN modulos m ON e.modulo_id = m.id
    WHERE e.ativo = 1 AND m.nivel = ?
    ORDER BY RAND()
    LIMIT 3
");
$stmt->execute([$user['nivel_conhecimento']]);
$exercicios_recomendados = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Exercícios - <?= SISTEMA_NOME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        
        .bg-glass {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .exercise-card {
            transition: all 0.3s ease;
            border-radius: 15px;
            border: 1px solid rgba(0,0,0,0.1);
        }
        
        .exercise-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.15);
        }
        
        .type-badge {
            font-size: 0.8rem;
            padding: 0.5rem 1rem;
        }
        
        .difficulty-badge {
            font-size: 0.7rem;
            padding: 0.3rem 0.8rem;
        }
        
        .progress-custom {
            height: 8px;
            border-radius: 10px;
        }
        
        .navbar {
            z-index: 1030;
        }
        
        .dropdown-menu {
            z-index: 1050 !important;
        }
        
        .stats-card {
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white;
            transition: transform 0.3s ease;
        }
        
        .stats-card:hover {
            transform: translateY(-5px);
        }
        
        .recommended-badge {
            position: absolute;
            top: -10px;
            right: -10px;
            background: #ff6b6b;
            color: white;
            border-radius: 50%;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.8rem;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg bg-glass navbar-light shadow-sm">
        <div class="container">
            <a class="navbar-brand fw-bold" href="dashboard.php">
                <i class="fas fa-shield-alt text-primary"></i> SACSWeb
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
                            <i class="fas fa-graduation-cap"></i> Módulos
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="exercicios.php">
                            <i class="fas fa-tasks"></i> Exercícios
                        </a>
                    </li>
                </ul>
                
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-user-circle"></i> <?= htmlspecialchars($user['nome']) ?>
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="perfil.php"><i class="fas fa-user"></i> Meu Perfil</a></li>
                            <li><a class="dropdown-item" href="configuracoes.php"><i class="fas fa-cog"></i> Configurações</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="logout.php"><i class="fas fa-sign-out-alt"></i> Sair</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <!-- Header -->
        <div class="bg-glass rounded-4 p-5 text-center text-dark mb-4">
            <h1 class="display-4 fw-bold mb-3">
                <i class="fas fa-tasks text-primary"></i> Exercícios Práticos
            </h1>
            <p class="lead mb-4">Teste seus conhecimentos e pratique o que aprendeu</p>
            
            <!-- Estatísticas dos Exercícios -->
            <div class="row justify-content-center">
                <div class="col-md-8">
                    <div class="row text-center">
                        <div class="col-md-3 mb-2">
                            <div class="bg-primary bg-opacity-10 rounded p-3">
                                <h4 class="text-primary mb-0"><?= $stats_exercicios['total_exercicios'] ?></h4>
                                <small class="text-muted">Total de Exercícios</small>
                            </div>
                        </div>
                        <div class="col-md-3 mb-2">
                            <div class="bg-success bg-opacity-10 rounded p-3">
                                <h4 class="text-primary mb-0"><?= $stats_exercicios['exercicios_concluidos'] ?></h4>
                                <small class="text-muted">Concluídos</small>
                            </div>
                        </div>
                        <div class="col-md-3 mb-2">
                            <div class="bg-warning bg-opacity-10 rounded p-3">
                                <h4 class="text-warning mb-0"><?= number_format($stats_exercicios['progresso_medio_exercicios'], 1) ?>%</h4>
                                <small class="text-muted">Progresso Médio</small>
                            </div>
                        </div>
                        <div class="col-md-3 mb-2">
                            <div class="bg-info bg-opacity-10 rounded p-3">
                                <h4 class="text-info mb-0"><?= $stats_exercicios['total_pontos_exercicios'] ?></h4>
                                <small class="text-muted">Total de Pontos</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Exercícios Recomendados -->
        <?php if (!empty($exercicios_recomendados)): ?>
        <div class="bg-glass rounded-4 p-4 mb-4">
            <h3 class="mb-4">
                <i class="fas fa-star text-warning"></i> Exercícios Recomendados para Você
            </h3>
            <div class="row">
                <?php foreach ($exercicios_recomendados as $exercicio): ?>
                <div class="col-md-4 mb-3">
                    <div class="card exercise-card h-100 position-relative">
                        <div class="recommended-badge">
                            <i class="fas fa-star"></i>
                        </div>
                        <div class="card-body">
                            <h5 class="card-title"><?= htmlspecialchars($exercicio['titulo']) ?></h5>
                            <p class="card-text text-muted"><?= htmlspecialchars($exercicio['descricao']) ?></p>
                            
                            <div class="mb-3">
                                <span class="badge bg-<?= $exercicio['tipo'] === 'quiz' ? 'info' : ($exercicio['tipo'] === 'codigo' ? 'warning' : 'secondary') ?> type-badge">
                                    <?= ucfirst($exercicio['tipo']) ?>
                                </span>
                                <span class="badge bg-<?= $exercicio['modulo_nivel'] === 'iniciante' ? 'success' : ($exercicio['modulo_nivel'] === 'intermediario' ? 'warning' : 'danger') ?> difficulty-badge">
                                    <?= ucfirst($exercicio['modulo_nivel']) ?>
                                </span>
                            </div>
                            
                            <small class="text-muted d-block mb-2">
                                <i class="fas fa-book"></i> <?= htmlspecialchars($exercicio['modulo_titulo']) ?>
                            </small>
                            
                            <a href="exercicio.php?id=<?= $exercicio['id'] ?>" class="btn btn-primary btn-sm">
                                <i class="fas fa-play"></i> Fazer Exercício
                            </a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Filtros -->
        <div class="bg-glass rounded-4 p-4 mb-4">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h4 class="mb-0">
                        <i class="fas fa-filter text-primary"></i> Filtrar Exercícios
                    </h4>
                </div>
                <div class="col-md-6">
                    <div class="btn-group w-100" role="group">
                        <button type="button" class="btn btn-outline-primary active" data-filter="todos">
                            <i class="fas fa-th"></i> Todos
                        </button>
                        <button type="button" class="btn btn-outline-info" data-filter="quiz">
                            <i class="fas fa-question-circle"></i> Quiz
                        </button>
                        <button type="button" class="btn btn-outline-warning" data-filter="codigo">
                            <i class="fas fa-code"></i> Código
                        </button>
                        <button type="button" class="btn btn-outline-secondary" data-filter="teorico">
                            <i class="fas fa-book"></i> Teórico
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Exercícios por Tipo -->
        <?php foreach ($exercicios_por_tipo as $tipo => $exercicios_tipo): ?>
        <div class="exercise-section mb-4" data-type="<?= $tipo ?>">
            <div class="bg-glass rounded-4 p-4">
                <h3 class="mb-4">
                    <?php if ($tipo === 'quiz'): ?>
                        <i class="fas fa-question-circle text-info"></i> Exercícios de Quiz
                    <?php elseif ($tipo === 'codigo'): ?>
                        <i class="fas fa-code text-warning"></i> Exercícios de Código
                    <?php else: ?>
                        <i class="fas fa-book text-secondary"></i> Exercícios Teóricos
                    <?php endif; ?>
                    <span class="badge bg-secondary ms-2"><?= count($exercicios_tipo) ?></span>
                </h3>
                
                <?php if (empty($exercicios_tipo)): ?>
                    <p class="text-muted text-center">Nenhum exercício deste tipo disponível.</p>
                <?php else: ?>
                    <div class="row">
                        <?php foreach ($exercicios_tipo as $exercicio): ?>
                        <div class="col-lg-6 mb-3">
                            <div class="card exercise-card h-100">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <h5 class="card-title mb-0"><?= htmlspecialchars($exercicio['titulo']) ?></h5>
                                        <span class="badge bg-<?= $exercicio['modulo_nivel'] === 'iniciante' ? 'success' : ($exercicio['modulo_nivel'] === 'intermediario' ? 'warning' : 'danger') ?> difficulty-badge">
                                            <?= ucfirst($exercicio['modulo_nivel']) ?>
                                        </span>
                                    </div>
                                    
                                    <p class="card-text text-muted"><?= htmlspecialchars($exercicio['descricao']) ?></p>
                                    
                                    <div class="mb-3">
                                        <small class="text-muted">
                                            <i class="fas fa-book"></i> <?= htmlspecialchars($exercicio['modulo_titulo']) ?>
                                        </small>
                                    </div>
                                    
                                    <?php if ($exercicio['progresso_usuario'] > 0): ?>
                                        <div class="mb-3">
                                            <small class="text-muted">Progresso: <?= $exercicio['progresso_usuario'] ?>%</small>
                                            <div class="progress progress-custom">
                                                <div class="progress-bar bg-success" style="width: <?= $exercicio['progresso_usuario'] ?>%"></div>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div class="d-flex justify-content-between align-items-center">
                                        <a href="exercicio.php?id=<?= $exercicio['id'] ?>" class="btn btn-primary btn-sm">
                                            <?php if ($exercicio['progresso_usuario'] > 0): ?>
                                                <i class="fas fa-play"></i> Continuar
                                            <?php else: ?>
                                                <i class="fas fa-play"></i> Iniciar
                                            <?php endif; ?>
                                        </a>
                                        
                                        <small class="text-muted">
                                            <i class="fas fa-clock"></i> <?= $exercicio['tempo_estimado'] ?? 15 ?> min
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>

        <!-- Call to Action -->
        <div class="text-center mt-5">
            <div class="bg-glass rounded-4 p-4">
                <h4 class="mb-3">Precisa de ajuda?</h4>
                <p class="text-muted mb-4">Se você tiver dúvidas sobre algum exercício, consulte o módulo correspondente ou entre em contato com o suporte.</p>
                <div class="d-flex justify-content-center gap-3">
                    <a href="modulos.php" class="btn btn-outline-primary">
                        <i class="fas fa-graduation-cap"></i> Ver Módulos
                    </a>
                    <a href="dashboard.php" class="btn btn-outline-secondary">
                        <i class="fas fa-home"></i> Voltar ao Dashboard
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Filtros de exercícios
        document.addEventListener('DOMContentLoaded', function() {
            const filterButtons = document.querySelectorAll('[data-filter]');
            const exerciseSections = document.querySelectorAll('.exercise-section');
            
            filterButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const filter = this.getAttribute('data-filter');
                    
                    // Atualizar botões ativos
                    filterButtons.forEach(btn => btn.classList.remove('active'));
                    this.classList.add('active');
                    
                    // Filtrar seções
                    exerciseSections.forEach(section => {
                        if (filter === 'todos' || section.getAttribute('data-type') === filter) {
                            section.style.display = 'block';
                        } else {
                            section.style.display = 'none';
                        }
                    });
                });
            });
            
            // Animações para os cards
            const cards = document.querySelectorAll('.exercise-card');
            cards.forEach((card, index) => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(20px)';
                
                setTimeout(() => {
                    card.style.transition = 'all 0.6s ease';
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, index * 100);
            });
        });
    </script>
</body>
</html>
