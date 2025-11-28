<?php
/**
 * SACSWeb Educacional - Visualização de Módulo
 * Sistema para Ensino de Ataques Cibernéticos e Proteções
 */

require_once '../config/config.php';
requireLogin();

$user = getCurrentUser();
$userPreferences = getUserPreferences($user['id'] ?? null);
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

// Separar quizzes dos outros exercícios
$quizzes = [];
$outros_exercicios = [];
foreach ($exercicios as $exercicio) {
    if ($exercicio['tipo'] === 'quiz') {
        $quizzes[] = $exercicio;
    } else {
        $outros_exercicios[] = $exercicio;
    }
}

// Quiz agora é processado em quiz_modulo.php

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

// Processamento de quiz agora está em quiz_modulo.php
// Progresso baseado em leitura será atualizado via JavaScript/AJAX
// Máximo de progresso por leitura: 70% (restante 30% vem do quiz)
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($modulo['titulo']) ?> - <?= SISTEMA_NOME ?></title>
    <link rel="icon" type="image/png" href="<?= ASSETS_URL ?>/images/icone.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/themes/prism.min.css" rel="stylesheet">
    <link href="<?= ASSETS_URL ?>/css/sacsweb-unified.css" rel="stylesheet">
    <script>
        window.SACSWEB_PREFERENCES = <?= json_encode($userPreferences, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    </script>
    <script src="<?= ASSETS_URL ?>/js/preferences.js" defer></script>
</head>
<body class="page-modulo">
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg bg-glass navbar-light shadow-sm">
        <div class="container">
            <a class="navbar-brand fw-bold" href="dashboard.php">
                <img src="<?= ASSETS_URL ?>/images/icone.png" alt="SACSWeb Logo" style="height: 40px; margin-right: 10px;"> <span class="text-primary">SACSWeb</span>
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
                    <li class="nav-item">
                        <a class="nav-link" href="ranking.php">
                            <i class="fas fa-trophy"></i> Ranking
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

                <!-- Quiz do Módulo -->
                <?php if (!empty($quizzes)): ?>
                <div class="content-section bg-glass p-4 mb-4" id="quiz-section">
                    <h3 class="mb-4">
                        <i class="fas fa-question-circle text-info"></i> Quiz do Módulo
                    </h3>
                    
                    <div class="text-center">
                        <p class="lead mb-4">Teste seus conhecimentos sobre o conteúdo aprendido</p>
                        <p class="mb-4">
                            <i class="fas fa-question-circle text-primary"></i> 
                            <strong><?= count($quizzes) ?></strong> perguntas disponíveis
                        </p>
                        <a href="quiz_modulo.php?id=<?= $modulo_id ?>" class="btn btn-primary btn-lg">
                            <i class="fas fa-play"></i> Iniciar Quiz
                        </a>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Outros Exercícios (não-quiz) -->
                <?php if (!empty($outros_exercicios)): ?>
                <div class="content-section bg-glass p-4 mb-4">
                    <h3 class="mb-4">
                        <i class="fas fa-tasks text-success"></i> Outros Exercícios
                    </h3>
                    
                    <?php foreach ($outros_exercicios as $index => $exercicio): ?>
                    <div class="card mb-3">
                        <div class="card-body">
                            <h5 class="card-title">
                                <span class="badge bg-primary me-2"><?= $index + 1 ?></span>
                                <?= htmlspecialchars($exercicio['titulo']) ?>
                            </h5>
                            <p class="card-text"><?= htmlspecialchars($exercicio['descricao']) ?></p>
                            
                            <?php if ($exercicio['tipo'] === 'codigo'): ?>
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
                            <small>Concluído</small>
                        </div>
                        
                        <div class="progress progress-custom mb-3">
                            <div class="progress-bar bg-success" style="width: <?= $modulo['progresso_usuario'] ?>%"></div>
                        </div>
                        
                        <div class="row text-center">
                            <div class="col-6">
                                <small>Iniciado</small>
                                <div class="fw-bold"><?= formatarData($modulo['data_inicio']) ?></div>
                            </div>
                            <div class="col-6">
                                <small>Pontos</small>
                                <div class="fw-bold"><?= $modulo['pontos_obtidos'] ?>/<?= $modulo['pontos_maximos'] ?></div>
                            </div>
                        </div>
                        
                        <?php if ($modulo['data_conclusao']): ?>
                            <hr>
                            <div class="text-center">
                                <small>Concluído em</small>
                                <div class="fw-bold text-success"><?= formatarData($modulo['data_conclusao']) ?></div>
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="text-center">
                            <i class="fas fa-play-circle" style="font-size: 3rem;"></i>
                            <p class="mt-2">Módulo não iniciado</p>
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
                    <div class="d-flex align-items-center mb-2 p-2 rounded">
                        <div class="flex-shrink-0 me-2">
                            <i class="fas fa-book text-primary"></i>
                        </div>
                        <div class="flex-grow-1">
                            <a href="modulo.php?id=<?= $mod_rel['id'] ?>" class="text-decoration-none">
                                <div class="fw-bold"><?= htmlspecialchars($mod_rel['titulo']) ?></div>
                                <small><?= ucfirst($mod_rel['nivel']) ?></small>
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
                    
                    <div class="warning-box mb-3 text-always-dark">
                        <strong>⚠️ Importante:</strong>
                        <p class="mb-0 text-always-dark">Este conteúdo é para fins educacionais. Nunca teste vulnerabilidades em sistemas reais sem autorização.</p>
                    </div>
                    
                    <div class="info-box text-always-dark">
                        <strong>💡 Dica:</strong>
                        <p class="mb-0 text-always-dark">Pratique os conceitos em ambientes controlados e sempre siga as melhores práticas de segurança.</p>
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
    
    <script>
        // Sistema de Progresso Baseado em Scroll
        (function() {
            const MODULO_ID = <?= $modulo_id ?>;
            const CURRENT_PROGRESS = <?= $modulo['progresso_usuario'] ?? 0 ?>;
            const CSRF_TOKEN = '<?= generateCSRFToken() ?>';
            const MAX_READING_PROGRESS = 70;
            
            let lastProgressUpdate = CURRENT_PROGRESS;
            let lastScrollUpdate = 0;
            const scrollThrottle = 2000; // Atualizar a cada 2 segundos
            
            // Função para calcular progresso baseado em scroll
            function calculateScrollProgress() {
                const windowHeight = window.innerHeight;
                const documentHeight = document.documentElement.scrollHeight;
                const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
                
                // Calcular porcentagem de scroll (0-100%)
                const scrollPercentage = (scrollTop / (documentHeight - windowHeight)) * 100;
                
                // Converter para progresso de leitura (0-70%)
                const readingProgress = Math.min(MAX_READING_PROGRESS, (scrollPercentage / 100) * MAX_READING_PROGRESS);
                
                return Math.max(0, Math.min(MAX_READING_PROGRESS, readingProgress));
            }
            
            // Função para atualizar progresso via AJAX
            function updateReadingProgress(newProgress) {
                if (newProgress <= lastProgressUpdate || newProgress > MAX_READING_PROGRESS) return;
                
                fetch('modulo_progresso.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        modulo_id: MODULO_ID,
                        progresso: newProgress,
                        csrf_token: CSRF_TOKEN
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        lastProgressUpdate = newProgress;
                        updateProgressBars(newProgress);
                    }
                })
                .catch(error => console.error('Erro ao atualizar progresso:', error));
            }
            
            // Função para atualizar barras de progresso visuais
            function updateProgressBars(progress) {
                document.querySelectorAll('.progress-bar').forEach(bar => {
                    if (bar && bar.style) {
                        bar.style.width = progress + '%';
                    }
                });
                
                // Atualizar texto de progresso se existir
                const progressTexts = document.querySelectorAll('[data-progress-text]');
                progressTexts.forEach(text => {
                    text.textContent = progress.toFixed(1) + '%';
                });
            }
            
            // Event listener para scroll
            window.addEventListener('scroll', function() {
                const now = Date.now();
                if (now - lastScrollUpdate < scrollThrottle) return;
                
                lastScrollUpdate = now;
                const newProgress = calculateScrollProgress();
                
                if (newProgress > lastProgressUpdate) {
                    updateReadingProgress(newProgress);
                }
            }, { passive: true });
            
            // Atualizar progresso inicial baseado na posição atual do scroll
            setTimeout(() => {
                const initialProgress = calculateScrollProgress();
                if (initialProgress > lastProgressUpdate) {
                    updateReadingProgress(initialProgress);
                }
            }, 1000);
        })();
    </script>
</body>
</html>
