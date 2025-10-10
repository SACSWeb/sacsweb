<?php
/**
 * SACSWeb Educacional - Visualização de Módulo
 * Sistema para Ensino de Ataques Cibernéticos e Proteções
 */

require_once '../config/config.php';
requireLogin();

$user = getCurrentUser();
$pdo = connectDatabase();

// Verificar se foi passado um ID de módulo
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    redirect('modulos.php');
}

$modulo_id = (int)$_GET['id'];

// Buscar informações do módulo
$stmt = $pdo->prepare("
    SELECT m.*, 
           COALESCE(p.progresso, 0) as progresso_usuario,
           p.data_inicio,
           p.data_conclusao,
           p.pontos_obtidos
    FROM modulos m 
    LEFT JOIN progresso_usuario p ON m.id = p.modulo_id AND p.usuario_id = ?
    WHERE m.id = ?
");
$stmt->execute([$user['id'], $modulo_id]);
$modulo = $stmt->fetch();

if (!$modulo) {
    redirect('modulos.php');
}

// Buscar exercícios do módulo
$stmt = $pdo->prepare("
    SELECT * FROM exercicios 
    WHERE modulo_id = ? 
    ORDER BY ordem
");
$stmt->execute([$modulo_id]);
$exercicios = $stmt->fetchAll();

// Buscar módulos relacionados (mesmo nível)
$stmt = $pdo->prepare("
    SELECT id, titulo, nivel 
    FROM modulos 
    WHERE nivel = ? AND id != ? 
    ORDER BY ordem 
    LIMIT 3
");
$stmt->execute([$modulo['nivel'], $modulo_id]);
$modulos_relacionados = $stmt->fetchAll();

// Processar início do módulo
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['iniciar_modulo'])) {
    if ($modulo['progresso_usuario'] == 0) {
        $stmt = $pdo->prepare("
            INSERT INTO progresso_usuario (usuario_id, modulo_id, progresso, data_inicio, pontos_obtidos)
            VALUES (?, ?, 5, NOW(), 0)
            ON DUPLICATE KEY UPDATE 
            progresso = 5, data_inicio = NOW()
        ");
        $stmt->execute([$user['id'], $modulo_id]);
        
        // Log da atividade
        $stmt = $pdo->prepare("
            INSERT INTO logs_atividade (usuario_id, modulo_id, acao, detalhes, data_hora)
            VALUES (?, ?, 'inicio_modulo', ?, NOW())
        ");
        $stmt->execute([$user['id'], $modulo_id, "Iniciou o módulo: " . $modulo['titulo']]);
        
        redirect("modulo.php?id=$modulo_id");
    }
}

// Processar conclusão do módulo
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['concluir_modulo'])) {
    $stmt = $pdo->prepare("
        UPDATE progresso_usuario 
        SET progresso = 100, data_conclusao = NOW(), pontos_obtidos = ?
        WHERE usuario_id = ? AND modulo_id = ?
    ");
    $stmt->execute([$modulo['pontos_maximos'], $user['id'], $modulo_id]);
    
    // Log da atividade
    $stmt = $pdo->prepare("
        INSERT INTO logs_atividade (usuario_id, modulo_id, acao, detalhes, data_hora)
        VALUES (?, ?, 'conclusao_modulo', ?, NOW())
    ");
    $stmt->execute([$user['id'], $modulo_id, "Concluiu o módulo: " . $modulo['titulo']]);
    
    redirect("modulo.php?id=$modulo_id");
}

// Atualizar progresso baseado no tempo de leitura
if ($modulo['progresso_usuario'] > 0 && $modulo['progresso_usuario'] < 100) {
    $tempo_estimado = $modulo['tempo_estimado'] * 60; // em segundos
    $tempo_atual = time() - strtotime($modulo['data_inicio']);
    $novo_progresso = min(95, ($tempo_atual / $tempo_estimado) * 100);
    
    if ($novo_progresso > $modulo['progresso_usuario']) {
        $stmt = $pdo->prepare("
            UPDATE progresso_usuario 
            SET progresso = ? 
            WHERE usuario_id = ? AND modulo_id = ?
        ");
        $stmt->execute([$novo_progresso, $user['id'], $modulo_id]);
        $modulo['progresso_usuario'] = $novo_progresso;
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($modulo['titulo']) ?> - <?= SISTEMA_NOME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/themes/prism.min.css" rel="stylesheet">
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
        
        .module-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px;
        }
        
        .content-section {
            transition: all 0.3s ease;
            border-radius: 15px;
            border: 1px solid rgba(0,0,0,0.1);
        }
        
        .content-section:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
        }
        
        .progress-custom {
            height: 12px;
            border-radius: 10px;
        }
        
        .navbar {
            z-index: 1030;
        }
        
        .dropdown-menu {
            z-index: 1050 !important;
        }
        
        .code-block {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            border-left: 4px solid #007bff;
        }
        
        .warning-box {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 8px;
            padding: 15px;
            border-left: 4px solid #ffc107;
        }
        
        .info-box {
            background: #d1ecf1;
            border: 1px solid #bee5eb;
            border-radius: 8px;
            padding: 15px;
            border-left: 4px solid #17a2b8;
        }
        
        .danger-box {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            border-radius: 8px;
            padding: 15px;
            border-left: 4px solid #dc3545;
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
        <!-- Breadcrumb -->
        <nav aria-label="breadcrumb" class="mb-4">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="modulos.php">Módulos</a></li>
                <li class="breadcrumb-item active"><?= htmlspecialchars($modulo['titulo']) ?></li>
            </ol>
        </nav>

        <!-- Header do Módulo -->
        <div class="module-header p-5 mb-4">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1 class="display-5 fw-bold mb-3"><?= htmlspecialchars($modulo['titulo']) ?></h1>
                    <p class="lead mb-3"><?= htmlspecialchars($modulo['descricao']) ?></p>
                    
                    <div class="d-flex flex-wrap gap-3 mb-3">
                        <span class="badge bg-<?= $modulo['nivel'] === 'iniciante' ? 'success' : ($modulo['nivel'] === 'intermediario' ? 'warning' : 'danger') ?> fs-6">
                            <i class="fas fa-<?= $modulo['nivel'] === 'iniciante' ? 'seedling' : ($modulo['nivel'] === 'intermediario' ? 'bolt' : 'shield-alt') ?>"></i>
                            <?= ucfirst($modulo['nivel']) ?>
                        </span>
                        <span class="badge bg-<?= $modulo['tipo_ataque'] === 'XSS' ? 'warning' : ($modulo['tipo_ataque'] === 'SQL Injection' ? 'danger' : 'info') ?> fs-6">
                            <?= htmlspecialchars($modulo['tipo_ataque']) ?>
                        </span>
                        <span class="badge bg-secondary fs-6">
                            <i class="fas fa-clock"></i> <?= $modulo['tempo_estimado'] ?> min
                        </span>
                        <span class="badge bg-primary fs-6">
                            <i class="fas fa-star"></i> <?= $modulo['pontos_maximos'] ?> pts
                        </span>
                    </div>
                </div>
                
                <div class="col-md-4 text-center">
                    <?php if ($modulo['progresso_usuario'] == 0): ?>
                        <form method="POST" class="d-inline">
                            <button type="submit" name="iniciar_modulo" class="btn btn-success btn-lg">
                                <i class="fas fa-play"></i> Iniciar Módulo
                            </button>
                        </form>
                    <?php else: ?>
                        <div class="mb-3">
                            <h5 class="text-white">Seu Progresso</h5>
                            <div class="progress progress-custom mb-2">
                                <div class="progress-bar bg-success" style="width: <?= $modulo['progresso_usuario'] ?>%"></div>
                            </div>
                            <small class="text-white-50"><?= number_format($modulo['progresso_usuario'], 1) ?>% concluído</small>
                        </div>
                        
                        <?php if ($modulo['progresso_usuario'] >= 95): ?>
                            <form method="POST" class="d-inline">
                                <button type="submit" name="concluir_modulo" class="btn btn-primary btn-lg">
                                    <i class="fas fa-check"></i> Concluir Módulo
                                </button>
                            </form>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Conteúdo do Módulo -->
        <div class="row">
            <div class="col-lg-8">
                <!-- Conteúdo Teórico -->
                <div class="content-section bg-glass p-4 mb-4">
                    <h3 class="mb-4">
                        <i class="fas fa-book text-primary"></i> Conteúdo Teórico
                    </h3>
                    <div class="conteudo-teorico">
                        <?= nl2br(htmlspecialchars($modulo['conteudo_teorico'])) ?>
                    </div>
                </div>

                <!-- Exemplo Prático -->
                <div class="content-section bg-glass p-4 mb-4">
                    <h3 class="mb-4">
                        <i class="fas fa-code text-warning"></i> Exemplo Prático
                    </h3>
                    <div class="exemplo-pratico">
                        <?= nl2br(htmlspecialchars($modulo['exemplo_pratico'])) ?>
                    </div>
                </div>

                <!-- Demonstração de Código -->
                <?php if (!empty($modulo['demonstracao_codigo'])): ?>
                <div class="content-section bg-glass p-4 mb-4">
                    <h3 class="mb-4">
                        <i class="fas fa-laptop-code text-info"></i> Demonstração de Código
                    </h3>
                    <div class="code-block">
                        <pre><code class="language-<?= $modulo['linguagem_codigo'] ?? 'html' ?>"><?= htmlspecialchars($modulo['demonstracao_codigo']) ?></code></pre>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Exercícios -->
                <?php if (!empty($exercicios)): ?>
                <div class="content-section bg-glass p-4 mb-4">
                    <h3 class="mb-4">
                        <i class="fas fa-tasks text-success"></i> Exercícios do Módulo
                    </h3>
                    
                    <?php foreach ($exercicios as $index => $exercicio): ?>
                    <div class="card mb-3">
                        <div class="card-body">
                            <h5 class="card-title">
                                <span class="badge bg-primary me-2"><?= $index + 1 ?></span>
                                <?= htmlspecialchars($exercicio['titulo']) ?>
                            </h5>
                            <p class="card-text"><?= htmlspecialchars($exercicio['descricao']) ?></p>
                            
                            <?php if ($exercicio['tipo'] === 'quiz'): ?>
                                <span class="badge bg-info">Quiz</span>
                            <?php elseif ($exercicio['tipo'] === 'codigo'): ?>
                                <span class="badge bg-warning">Código</span>
                            <?php else: ?>
                                <span class="badge bg-secondary">Teórico</span>
                            <?php endif; ?>
                            
                            <div class="mt-3">
                                <a href="exercicio.php?id=<?= $exercicio['id'] ?>" class="btn btn-primary btn-sm">
                                    <i class="fas fa-play"></i> Fazer Exercício
                                </a>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>

            <!-- Sidebar -->
            <div class="col-lg-4">
                <!-- Progresso -->
                <div class="content-section bg-glass p-4 mb-4">
                    <h5 class="mb-3">
                        <i class="fas fa-chart-line text-primary"></i> Seu Progresso
                    </h5>
                    
                    <?php if ($modulo['progresso_usuario'] > 0): ?>
                        <div class="text-center mb-3">
                            <div class="display-6 fw-bold text-primary"><?= number_format($modulo['progresso_usuario'], 1) ?>%</div>
                            <small class="text-muted">Concluído</small>
                        </div>
                        
                        <div class="progress progress-custom mb-3">
                            <div class="progress-bar bg-success" style="width: <?= $modulo['progresso_usuario'] ?>%"></div>
                        </div>
                        
                        <div class="row text-center">
                            <div class="col-6">
                                <small class="text-muted">Iniciado</small>
                                <div class="fw-bold"><?= formatarData($modulo['data_inicio']) ?></div>
                            </div>
                            <div class="col-6">
                                <small class="text-muted">Pontos</small>
                                <div class="fw-bold"><?= $modulo['pontos_obtidos'] ?>/<?= $modulo['pontos_maximos'] ?></div>
                            </div>
                        </div>
                        
                        <?php if ($modulo['data_conclusao']): ?>
                            <hr>
                            <div class="text-center">
                                <small class="text-muted">Concluído em</small>
                                <div class="fw-bold text-success"><?= formatarData($modulo['data_conclusao']) ?></div>
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="text-center">
                            <i class="fas fa-play-circle text-muted" style="font-size: 3rem;"></i>
                            <p class="text-muted mt-2">Módulo não iniciado</p>
                            <form method="POST">
                                <button type="submit" name="iniciar_modulo" class="btn btn-success">
                                    <i class="fas fa-play"></i> Iniciar Agora
                                </button>
                            </form>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Módulos Relacionados -->
                <?php if (!empty($modulos_relacionados)): ?>
                <div class="content-section bg-glass p-4 mb-4">
                    <h5 class="mb-3">
                        <i class="fas fa-link text-info"></i> Módulos Relacionados
                    </h5>
                    
                    <?php foreach ($modulos_relacionados as $mod_rel): ?>
                    <div class="d-flex align-items-center mb-2 p-2 bg-white rounded">
                        <div class="flex-shrink-0 me-2">
                            <i class="fas fa-book text-primary"></i>
                        </div>
                        <div class="flex-grow-1">
                            <a href="modulo.php?id=<?= $mod_rel['id'] ?>" class="text-decoration-none">
                                <div class="fw-bold"><?= htmlspecialchars($mod_rel['titulo']) ?></div>
                                <small class="text-muted"><?= ucfirst($mod_rel['nivel']) ?></small>
                            </a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <!-- Dicas de Segurança -->
                <div class="content-section bg-glass p-4">
                    <h5 class="mb-3">
                        <i class="fas fa-lightbulb text-warning"></i> Dicas de Segurança
                    </h5>
                    
                    <div class="warning-box mb-3">
                        <strong>⚠️ Importante:</strong>
                        <p class="mb-0">Este conteúdo é para fins educacionais. Nunca teste vulnerabilidades em sistemas reais sem autorização.</p>
                    </div>
                    
                    <div class="info-box">
                        <strong>💡 Dica:</strong>
                        <p class="mb-0">Pratique os conceitos em ambientes controlados e sempre siga as melhores práticas de segurança.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Navegação entre Módulos -->
        <div class="row mt-5">
            <div class="col-12">
                <div class="bg-glass p-4 rounded-4">
                    <div class="row">
                        <div class="col-md-6">
                            <a href="modulos.php" class="btn btn-outline-primary">
                                <i class="fas fa-arrow-left"></i> Voltar aos Módulos
                            </a>
                        </div>
                        <div class="col-md-6 text-end">
                            <a href="dashboard.php" class="btn btn-outline-secondary">
                                <i class="fas fa-home"></i> Ir para Dashboard
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/components/prism-core.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/plugins/autoloader/prism-autoloader.min.js"></script>
    <script>
        // Auto-highlight code blocks
        Prism.highlightAll();
        
        // Smooth scrolling para links internos
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });
    </script>
</body>
</html>
