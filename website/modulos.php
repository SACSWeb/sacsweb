<?php
require_once '../config/config.php';
requireLogin();

$user = getCurrentUser();
$pdo = connectDatabase();

// Buscar módulos organizados por nível
$stmt = $pdo->prepare("
    SELECT m.*, 
           COALESCE(p.progresso, 0) as progresso_usuario,
           p.data_inicio,
           p.data_conclusao,
           p.pontos_obtidos
    FROM modulos m 
    LEFT JOIN progresso_usuario p ON m.id = p.modulo_id AND p.usuario_id = ?
    ORDER BY m.nivel, m.ordem
");
$stmt->execute([$user['id']]);
$modulos = $stmt->fetchAll();

// Organizar módulos por nível
$modulos_por_nivel = [
    'iniciante' => [],
    'intermediario' => [],
    'avancado' => []
];

foreach ($modulos as $modulo) {
    $modulos_por_nivel[$modulo['nivel']][] = $modulo;
}

// Buscar estatísticas do usuário
$stmt = $pdo->prepare("
    SELECT 
        COUNT(DISTINCT m.id) as total_modulos,
        COUNT(DISTINCT CASE WHEN p.data_conclusao IS NOT NULL THEN m.id END) as modulos_concluidos,
        SUM(COALESCE(p.pontos_obtidos, 0)) as total_pontos,
        AVG(COALESCE(p.progresso, 0)) as progresso_medio
    FROM modulos m
    LEFT JOIN progresso_usuario p ON m.id = p.modulo_id AND p.usuario_id = ?
");
$stmt->execute([$user['id']]);
$stats = $stmt->fetch();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Módulos de Aprendizado - <?= SISTEMA_NOME ?></title>
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
        
        .level-card {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            border: none;
            border-radius: 15px;
        }
        
        .level-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
        }
        
        .level-iniciante { border-left: 5px solid #28a745; }
        .level-intermediario { border-left: 5px solid #ffc107; }
        .level-avancado { border-left: 5px solid #dc3545; }
        
        .module-item {
            transition: all 0.3s ease;
            border-radius: 10px;
            border: 1px solid rgba(0,0,0,0.1);
        }
        
        .module-item:hover {
            transform: translateX(5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
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
                        <a class="nav-link active" href="modulos.php">
                            <i class="fas fa-graduation-cap"></i> Módulos
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="exercicios.php">
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
                <i class="fas fa-graduation-cap text-primary"></i> Módulos de Aprendizado
            </h1>
            <p class="lead mb-4">Explore nossa trilha completa de segurança cibernética</p>
            
            <!-- Estatísticas Rápidas -->
            <div class="row justify-content-center">
                <div class="col-md-8">
                    <div class="row text-center">
                        <div class="col-md-3 mb-2">
                            <div class="bg-primary bg-opacity-10 rounded p-3">
                                <h4 class="text-primary mb-0"><?= $stats['total_modulos'] ?></h4>
                                <small class="text-muted">Total de Módulos</small>
                            </div>
                        </div>
                        <div class="col-md-3 mb-2">
                            <div class="bg-success bg-opacity-10 rounded p-3">
                                <h4 class="text-success mb-0"><?= $stats['modulos_concluidos'] ?></h4>
                                <small class="text-muted">Concluídos</small>
                            </div>
                        </div>
                        <div class="col-md-3 mb-2">
                            <div class="bg-warning bg-opacity-10 rounded p-3">
                                <h4 class="text-warning mb-0"><?= number_format($stats['progresso_medio'], 1) ?>%</h4>
                                <small class="text-muted">Progresso Médio</small>
                            </div>
                        </div>
                        <div class="col-md-3 mb-2">
                            <div class="bg-info bg-opacity-10 rounded p-3">
                                <h4 class="text-info mb-0"><?= $stats['total_pontos'] ?></h4>
                                <small class="text-muted">Total de Pontos</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Níveis de Aprendizado -->
        <div class="row">
            <!-- Nível Iniciante -->
            <div class="col-lg-4 mb-4">
                <div class="card level-card level-iniciante bg-glass border-0 shadow h-100">
                    <div class="card-body text-center p-4">
                        <div class="mb-3">
                            <i class="fas fa-seedling text-success" style="font-size: 3rem;"></i>
                        </div>
                        <h3 class="card-title text-success fw-bold">🌱 Nível Iniciante</h3>
                        <p class="card-text text-muted">Fundamentos de Segurança Web</p>
                        <div class="d-grid">
                            <button class="btn btn-outline-success" type="button" data-bs-toggle="collapse" data-bs-target="#nivel-iniciante">
                                <i class="fas fa-chevron-down"></i> Ver Módulos
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Nível Intermediário -->
            <div class="col-lg-4 mb-4">
                <div class="card level-card level-intermediario bg-glass border-0 shadow h-100">
                    <div class="card-body text-center p-4">
                        <div class="mb-3">
                            <i class="fas fa-bolt text-warning" style="font-size: 3rem;"></i>
                        </div>
                        <h3 class="card-title text-warning fw-bold">⚡ Nível Intermediário</h3>
                        <p class="card-text text-muted">Exploração e Boas Práticas</p>
                        <div class="d-grid">
                            <button class="btn btn-outline-warning" type="button" data-bs-toggle="collapse" data-bs-target="#nivel-intermediario">
                                <i class="fas fa-chevron-down"></i> Ver Módulos
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Nível Avançado -->
            <div class="col-lg-4 mb-4">
                <div class="card level-card level-avancado bg-glass border-0 shadow h-100">
                    <div class="card-body text-center p-4">
                        <div class="mb-3">
                            <i class="fas fa-shield-alt text-danger" style="font-size: 3rem;"></i>
                        </div>
                        <h3 class="card-title text-danger fw-bold">🛡️ Nível Avançado</h3>
                        <p class="card-text text-muted">Pentest e Defesas Modernas</p>
                        <div class="d-grid">
                            <button class="btn btn-outline-danger" type="button" data-bs-toggle="collapse" data-bs-target="#nivel-avancado">
                                <i class="fas fa-chevron-down"></i> Ver Módulos
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Módulos por Nível -->
        <?php foreach ($modulos_por_nivel as $nivel => $modulos_nivel): ?>
            <div class="collapse mb-4" id="nivel-<?= $nivel ?>">
                <div class="card bg-glass border-0 shadow">
                    <div class="card-header bg-transparent border-0">
                        <h4 class="mb-0">
                            <?php if ($nivel === 'iniciante'): ?>
                                <i class="fas fa-seedling text-success"></i> 🌱 Nível Iniciante
                            <?php elseif ($nivel === 'intermediario'): ?>
                                <i class="fas fa-bolt text-warning"></i> ⚡ Nível Intermediário
                            <?php else: ?>
                                <i class="fas fa-shield-alt text-danger"></i> 🛡️ Nível Avançado
                            <?php endif; ?>
                        </h4>
                    </div>
                    <div class="card-body">
                        <?php if (empty($modulos_nivel)): ?>
                            <p class="text-muted text-center">Nenhum módulo disponível neste nível.</p>
                        <?php else: ?>
                            <?php foreach ($modulos_nivel as $modulo): ?>
                                <div class="module-item p-3 mb-3 bg-white rounded">
                                    <div class="row align-items-center">
                                        <div class="col-md-8">
                                            <h5 class="mb-2">
                                                <i class="fas fa-book text-primary"></i>
                                                <?= htmlspecialchars($modulo['titulo']) ?>
                                            </h5>
                                            <p class="text-muted mb-2"><?= htmlspecialchars($modulo['descricao']) ?></p>
                                            <div class="d-flex align-items-center gap-3">
                                                <span class="badge bg-<?= $modulo['tipo_ataque'] === 'XSS' ? 'warning' : ($modulo['tipo_ataque'] === 'SQL Injection' ? 'danger' : 'info') ?>">
                                                    <?= htmlspecialchars($modulo['tipo_ataque']) ?>
                                                </span>
                                                <span class="badge bg-secondary">
                                                    <i class="fas fa-clock"></i> <?= $modulo['tempo_estimado'] ?> min
                                                </span>
                                                <?php if ($modulo['progresso_usuario'] > 0): ?>
                                                    <span class="badge bg-success">
                                                        <i class="fas fa-check"></i> Em Progresso
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <div class="col-md-4 text-end">
                                            <?php if ($modulo['progresso_usuario'] > 0): ?>
                                                <div class="mb-2">
                                                    <small class="text-muted">Progresso: <?= $modulo['progresso_usuario'] ?>%</small>
                                                    <div class="progress progress-custom">
                                                        <div class="progress-bar bg-success" style="width: <?= $modulo['progresso_usuario'] ?>%"></div>
                                                    </div>
                                                </div>
                                            <?php endif; ?>
                                            
                                            <a href="modulo.php?id=<?= $modulo['id'] ?>" class="btn btn-primary btn-sm">
                                                <?php if ($modulo['progresso_usuario'] > 0): ?>
                                                    <i class="fas fa-play"></i> Continuar
                                                <?php else: ?>
                                                    <i class="fas fa-play"></i> Iniciar
                                                <?php endif; ?>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>

        <!-- Call to Action -->
        <div class="text-center mt-5">
            <div class="bg-glass rounded-4 p-4">
                <h4 class="mb-3">Pronto para começar sua jornada?</h4>
                <p class="text-muted mb-4">Escolha um módulo do nível iniciante para dar os primeiros passos na segurança cibernética.</p>
                <button class="btn btn-primary btn-lg" type="button" data-bs-toggle="collapse" data-bs-target="#nivel-iniciante">
                    <i class="fas fa-rocket"></i> Começar Agora
                </button>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-expandir o primeiro nível ao carregar a página
        document.addEventListener('DOMContentLoaded', function() {
            // Mostrar estatísticas animadas
            const stats = document.querySelectorAll('.bg-primary, .bg-success, .bg-warning, .bg-info');
            stats.forEach(stat => {
                const number = stat.querySelector('h4');
                const finalValue = parseInt(number.textContent);
                let currentValue = 0;
                const increment = finalValue / 20;
                
                const timer = setInterval(() => {
                    currentValue += increment;
                    if (currentValue >= finalValue) {
                        currentValue = finalValue;
                        clearInterval(timer);
                    }
                    number.textContent = Math.floor(currentValue);
                }, 50);
            });
        });
    </script>
</body>
</html>
