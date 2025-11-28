<?php
/**
 * SACSWeb Educacional - Página de Ranking de Alunos
 * Exibe leaderboard de alunos com filtros e destaque do usuário logado
 * Versão: 2.1.0
 */

require_once '../config/config.php';
requireLogin();

$user = getCurrentUser();
$userPreferences = getUserPreferences($user['id'] ?? null);
$pdo = connectDatabase();

// Filtros
$filtro_nivel = $_GET['nivel'] ?? 'todos';
$filtro_periodo = $_GET['periodo'] ?? 'geral';

// Validar filtros
$niveis_validos = ['todos', 'iniciante', 'intermediario', 'avancado'];
$periodos_validos = ['geral', 'mes', 'semana'];

if (!in_array($filtro_nivel, $niveis_validos)) {
    $filtro_nivel = 'todos';
}
if (!in_array($filtro_periodo, $periodos_validos)) {
    $filtro_periodo = 'geral';
}

// Buscar ranking
try {
    // Verificar se campos de quiz existem
    $stmt = $pdo->query("SHOW COLUMNS FROM progresso_usuario LIKE 'questoes_totais'");
    $tem_campos_quiz = $stmt->rowCount() > 0;
    
    // Construir query base
    if ($tem_campos_quiz) {
        $sql = "
            SELECT 
                u.id,
                u.nome,
                u.email,
                u.nivel_conhecimento,
                u.foto_perfil,
                COALESCE(SUM(p.pontos_obtidos), 0) as total_pontos,
                COUNT(DISTINCT CASE WHEN p.progresso >= 95 THEN p.modulo_id END) as modulos_concluidos,
                COUNT(DISTINCT p.modulo_id) as modulos_iniciados,
                COALESCE(SUM(p.questoes_acertadas), 0) as total_acertos,
                COALESCE(SUM(p.questoes_totais), 0) as total_questoes,
                COUNT(DISTINCT CASE WHEN p.quiz_completo = 1 THEN p.modulo_id END) as quizzes_completos,
                COUNT(DISTINCT p.modulo_id) as total_quizzes_tentados,
                CASE 
                    WHEN COALESCE(SUM(p.questoes_totais), 0) > 0 
                    THEN ROUND((COALESCE(SUM(p.questoes_acertadas), 0) / COALESCE(SUM(p.questoes_totais), 0)) * 100, 2)
                    ELSE 0 
                END as porcentagem_acertos_geral
            FROM usuarios u
            LEFT JOIN progresso_usuario p ON u.id = p.usuario_id
            WHERE u.ativo = 1 AND u.tipo_usuario = 'aluno'
        ";
    } else {
        $sql = "
            SELECT 
                u.id,
                u.nome,
                u.email,
                u.nivel_conhecimento,
                u.foto_perfil,
                COALESCE(SUM(p.pontos_obtidos), 0) as total_pontos,
                COUNT(DISTINCT CASE WHEN p.progresso >= 95 THEN p.modulo_id END) as modulos_concluidos,
                COUNT(DISTINCT p.modulo_id) as modulos_iniciados,
                0 as total_acertos,
                0 as total_questoes,
                0 as quizzes_completos,
                0 as total_quizzes_tentados,
                0 as porcentagem_acertos_geral
            FROM usuarios u
            LEFT JOIN progresso_usuario p ON u.id = p.usuario_id
            WHERE u.ativo = 1 AND u.tipo_usuario = 'aluno'
        ";
    }
    
    $params = [];
    
    // Aplicar filtro de nível
    if ($filtro_nivel !== 'todos') {
        $sql .= " AND u.nivel_conhecimento = ?";
        $params[] = $filtro_nivel;
    }
    
    // Aplicar filtro de período
    if ($filtro_periodo === 'mes') {
        $sql .= " AND p.data_inicio >= DATE_SUB(NOW(), INTERVAL 1 MONTH)";
    } elseif ($filtro_periodo === 'semana') {
        $sql .= " AND p.data_inicio >= DATE_SUB(NOW(), INTERVAL 1 WEEK)";
    }
    
    $sql .= " GROUP BY u.id, u.nome, u.email, u.nivel_conhecimento, u.foto_perfil
              ORDER BY total_pontos DESC, porcentagem_acertos_geral DESC, modulos_concluidos DESC
              LIMIT 100";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $ranking = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Adicionar posição
    $posicao = 1;
    $posicao_usuario = null;
    foreach ($ranking as &$aluno) {
        $aluno['posicao'] = $posicao;
        if ($aluno['id'] == $user['id']) {
            $posicao_usuario = $posicao;
        }
        $posicao++;
    }
    
    // Buscar posição do usuário se não estiver no top 100
    if ($posicao_usuario === null) {
        $sql_posicao = "
            SELECT COUNT(*) + 1 as posicao
            FROM (
                SELECT u.id, COALESCE(SUM(p.pontos_obtidos), 0) as total_pontos
                FROM usuarios u
                LEFT JOIN progresso_usuario p ON u.id = p.usuario_id
                WHERE u.ativo = 1 AND u.tipo_usuario = 'aluno'
        ";
        
        if ($filtro_nivel !== 'todos') {
            $sql_posicao .= " AND u.nivel_conhecimento = ?";
        }
        
        if ($filtro_periodo === 'mes') {
            $sql_posicao .= " AND p.data_inicio >= DATE_SUB(NOW(), INTERVAL 1 MONTH)";
        } elseif ($filtro_periodo === 'semana') {
            $sql_posicao .= " AND p.data_inicio >= DATE_SUB(NOW(), INTERVAL 1 WEEK)";
        }
        
        $sql_posicao .= " GROUP BY u.id
                          HAVING total_pontos > (
                              SELECT COALESCE(SUM(p2.pontos_obtidos), 0)
                              FROM progresso_usuario p2
                              WHERE p2.usuario_id = ?
                          )
            ) as subquery";
        
        $stmt_posicao = $pdo->prepare($sql_posicao);
        if ($tem_campos_quiz) {
            $params_posicao = $filtro_nivel !== 'todos' ? [$filtro_nivel, $user['id'], $user['id']] : [$user['id'], $user['id']];
        } else {
            $params_posicao = $filtro_nivel !== 'todos' ? [$filtro_nivel, $user['id']] : [$user['id']];
        }
        $stmt_posicao->execute($params_posicao);
        $resultado_posicao = $stmt_posicao->fetch(PDO::FETCH_ASSOC);
        $posicao_usuario = $resultado_posicao['posicao'] ?? null;
    }
    
    // Buscar dados do usuário para exibição
    $stmt_check_quiz = $pdo->query("SHOW COLUMNS FROM progresso_usuario LIKE 'quiz_completo'");
    $tem_campo_quiz_completo = $stmt_check_quiz->rowCount() > 0;
    
    if ($tem_campo_quiz_completo) {
        $stmt = $pdo->prepare("
            SELECT 
                COALESCE(SUM(p.pontos_obtidos), 0) as total_pontos,
                COUNT(DISTINCT CASE WHEN p.progresso >= 95 THEN p.modulo_id END) as modulos_concluidos,
                COALESCE(SUM(p.questoes_acertadas), 0) as total_acertos,
                COALESCE(SUM(p.questoes_totais), 0) as total_questoes,
                COUNT(DISTINCT CASE WHEN p.quiz_completo = 1 THEN p.modulo_id END) as quizzes_completos,
                COUNT(DISTINCT p.modulo_id) as total_quizzes_tentados,
                CASE 
                    WHEN COALESCE(SUM(p.questoes_totais), 0) > 0 
                    THEN ROUND((COALESCE(SUM(p.questoes_acertadas), 0) / COALESCE(SUM(p.questoes_totais), 0)) * 100, 2)
                    ELSE 0 
                END as porcentagem_acertos_geral
            FROM progresso_usuario p
            WHERE p.usuario_id = ?
        ");
    } else {
        $stmt = $pdo->prepare("
            SELECT 
                COALESCE(SUM(p.pontos_obtidos), 0) as total_pontos,
                COUNT(DISTINCT CASE WHEN p.progresso >= 95 THEN p.modulo_id END) as modulos_concluidos,
                COALESCE(SUM(p.questoes_acertadas), 0) as total_acertos,
                COALESCE(SUM(p.questoes_totais), 0) as total_questoes,
                0 as quizzes_completos,
                0 as total_quizzes_tentados,
                CASE 
                    WHEN COALESCE(SUM(p.questoes_totais), 0) > 0 
                    THEN ROUND((COALESCE(SUM(p.questoes_acertadas), 0) / COALESCE(SUM(p.questoes_totais), 0)) * 100, 2)
                    ELSE 0 
                END as porcentagem_acertos_geral
            FROM progresso_usuario p
            WHERE p.usuario_id = ?
        ");
    }
    $stmt->execute([$user['id']]);
    $dados_usuario = $stmt->fetch(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    logMessage('Erro ao buscar ranking: ' . $e->getMessage(), 'error');
    $ranking = [];
    $posicao_usuario = null;
    $dados_usuario = ['total_pontos' => 0, 'modulos_concluidos' => 0, 'total_acertos' => 0, 'total_questoes' => 0, 'quizzes_completos' => 0, 'total_quizzes_tentados' => 0, 'porcentagem_acertos_geral' => 0];
    $tem_campos_quiz = false;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ranking de Alunos - <?= SISTEMA_NOME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="<?= ASSETS_URL ?>/css/sacsweb-unified.css" rel="stylesheet">
    <script>
        window.SACSWEB_PREFERENCES = <?= json_encode($userPreferences, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    </script>
    <script src="<?= ASSETS_URL ?>/js/preferences.js" defer></script>
    
    <style>
        .ranking-header {
            background: linear-gradient(135deg, var(--primary-pink), var(--accent-blue));
            border-radius: 15px;
            padding: 30px;
            text-align: center;
            color: white;
            margin-bottom: 30px;
        }
        
        .ranking-card {
            background: var(--dark-gray);
            border: 1px solid var(--light-gray);
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 15px;
            transition: all 0.3s ease;
        }
        
        .ranking-card:hover {
            transform: translateX(5px);
            border-color: var(--primary-pink);
        }
        
        .ranking-card.user-position {
            background: linear-gradient(135deg, rgba(255, 20, 147, 0.2), rgba(0, 191, 255, 0.2));
            border-color: var(--primary-pink);
            border-width: 2px;
        }
        
        .position-badge {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 1.2rem;
            margin-right: 15px;
        }
        
        .position-1 { background: linear-gradient(135deg, #FFD700, #FFA500); color: #000; }
        .position-2 { background: linear-gradient(135deg, #C0C0C0, #808080); color: #000; }
        .position-3 { background: linear-gradient(135deg, #CD7F32, #8B4513); color: #fff; }
        .position-other { background: var(--light-gray); color: var(--text-light); }
        
        .user-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            object-fit: cover;
            margin-right: 15px;
        }
        
        .user-avatar-placeholder {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: var(--light-gray);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
            font-size: 1.5rem;
        }
        
        .filter-section {
            background: var(--dark-gray);
            border: 1px solid var(--light-gray);
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 30px;
        }
    </style>
</head>
<body class="page-ranking">
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg bg-glass navbar-light shadow-sm">
        <div class="container">
            <a class="navbar-brand fw-bold text-primary" href="dashboard.php">
                <img src="<?= ASSETS_URL ?>/images/icone.png" alt="SACSWeb Logo" style="height: 40px; margin-right: 10px;"> <span class="text-primary">SACSWeb</span> Educacional
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
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="ranking.php">
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
        <div class="ranking-header">
            <h1 class="display-5 fw-bold mb-3">
                <i class="fas fa-trophy"></i> Ranking de Alunos
            </h1>
            <p class="lead mb-0">Veja como você está se saindo em relação aos outros alunos</p>
        </div>

        <!-- Filtros -->
        <div class="filter-section">
            <form method="GET" action="" class="row g-3">
                <div class="col-md-6">
                    <label for="nivel" class="form-label">Filtrar por Nível</label>
                    <select class="form-select" id="nivel" name="nivel">
                        <option value="todos" <?= $filtro_nivel === 'todos' ? 'selected' : '' ?>>Todos os Níveis</option>
                        <option value="iniciante" <?= $filtro_nivel === 'iniciante' ? 'selected' : '' ?>>Iniciante</option>
                        <option value="intermediario" <?= $filtro_nivel === 'intermediario' ? 'selected' : '' ?>>Intermediário</option>
                        <option value="avancado" <?= $filtro_nivel === 'avancado' ? 'selected' : '' ?>>Avançado</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label for="periodo" class="form-label">Filtrar por Período</label>
                    <select class="form-select" id="periodo" name="periodo">
                        <option value="geral" <?= $filtro_periodo === 'geral' ? 'selected' : '' ?>>Geral (Todos os Tempos)</option>
                        <option value="mes" <?= $filtro_periodo === 'mes' ? 'selected' : '' ?>>Último Mês</option>
                        <option value="semana" <?= $filtro_periodo === 'semana' ? 'selected' : '' ?>>Última Semana</option>
                    </select>
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-filter"></i> Aplicar Filtros
                    </button>
                    <a href="ranking.php" class="btn btn-secondary">
                        <i class="fas fa-redo"></i> Limpar Filtros
                    </a>
                </div>
            </form>
        </div>

        <!-- Posição do Usuário -->
        <?php if ($posicao_usuario !== null): ?>
            <div class="alert alert-info mb-4">
                <div class="d-flex align-items-center">
                    <i class="fas fa-user-circle fa-2x me-3"></i>
                    <div>
                        <strong>Sua Posição:</strong> 
                        <span class="h4 mb-0">#<?= $posicao_usuario ?></span>
                        <br>
                        <small>
                            Pontos: <?= $dados_usuario['total_pontos'] ?> | 
                            Módulos Concluídos: <?= $dados_usuario['modulos_concluidos'] ?>
                            <?php if ($dados_usuario['total_questoes'] > 0): ?>
                            | Acertos: <?= $dados_usuario['total_acertos'] ?>/<?= $dados_usuario['total_questoes'] ?> (<?= $dados_usuario['porcentagem_acertos_geral'] ?>%)
                            <?php endif; ?>
                            <?php if (isset($dados_usuario['quizzes_completos']) && $dados_usuario['quizzes_completos'] > 0): ?>
                            | Quizzes Completos: <?= $dados_usuario['quizzes_completos'] ?>
                            <?php endif; ?>
                        </small>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Ranking -->
        <div class="mb-4">
            <?php if (!empty($ranking)): ?>
                <?php foreach ($ranking as $aluno): ?>
                    <div class="ranking-card <?= $aluno['id'] == $user['id'] ? 'user-position' : '' ?>">
                        <div class="d-flex align-items-center">
                            <!-- Posição -->
                            <div class="position-badge position-<?= $aluno['posicao'] <= 3 ? $aluno['posicao'] : 'other' ?>">
                                <?= $aluno['posicao'] ?>
                            </div>
                            
                            <!-- Avatar -->
                            <?php if (!empty($aluno['foto_perfil'])): ?>
                                <img src="../<?= htmlspecialchars($aluno['foto_perfil']) ?>" alt="Avatar" class="user-avatar">
                            <?php else: ?>
                                <div class="user-avatar-placeholder">
                                    <i class="fas fa-user"></i>
                                </div>
                            <?php endif; ?>
                            
                            <!-- Informações -->
                            <div class="flex-grow-1">
                                <h5 class="mb-1">
                                    <?= htmlspecialchars($aluno['nome']) ?>
                                    <?php if ($aluno['id'] == $user['id']): ?>
                                        <span class="badge bg-primary">Você</span>
                                    <?php endif; ?>
                                </h5>
                                <div class="d-flex flex-wrap gap-3">
                                    <span>
                                        <i class="fas fa-star text-warning"></i> 
                                        <?= $aluno['total_pontos'] ?> pontos
                                    </span>
                                    <span>
                                        <i class="fas fa-check-circle text-success"></i> 
                                        <?= $aluno['modulos_concluidos'] ?> módulos concluídos
                                    </span>
                                    <?php if ($aluno['total_questoes'] > 0): ?>
                                    <span>
                                        <i class="fas fa-check-double text-info"></i> 
                                        <?= $aluno['total_acertos'] ?>/<?= $aluno['total_questoes'] ?> acertos
                                        (<?= $aluno['porcentagem_acertos_geral'] ?>%)
                                    </span>
                                    <?php endif; ?>
                                    <?php if (isset($aluno['quizzes_completos']) && $aluno['quizzes_completos'] > 0): ?>
                                    <span>
                                        <i class="fas fa-tasks text-primary"></i> 
                                        <?= $aluno['quizzes_completos'] ?> quiz<?= $aluno['quizzes_completos'] > 1 ? 'zes' : '' ?> completo<?= $aluno['quizzes_completos'] > 1 ? 's' : '' ?>
                                    </span>
                                    <?php endif; ?>
                                    <span>
                                        <i class="fas fa-graduation-cap"></i> 
                                        <?= ucfirst($aluno['nivel_conhecimento']) ?>
                                    </span>
                                </div>
                            </div>
                            
                            <!-- Medalha para top 3 -->
                            <?php if ($aluno['posicao'] == 1): ?>
                                <i class="fas fa-trophy fa-3x text-warning"></i>
                            <?php elseif ($aluno['posicao'] == 2): ?>
                                <i class="fas fa-medal fa-3x text-secondary"></i>
                            <?php elseif ($aluno['posicao'] == 3): ?>
                                <i class="fas fa-award fa-3x" style="color: #CD7F32;"></i>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="alert alert-info text-center">
                    <i class="fas fa-info-circle fa-2x mb-3"></i>
                    <p class="mb-0">Nenhum aluno encontrado com os filtros selecionados.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

