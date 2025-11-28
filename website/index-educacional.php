<?php
/**
 * SACSWeb Educacional - Página Principal
 * Sistema para Ensino de Ataques Cibernéticos e Proteções
 * Versão: 2.0.0
 */

// Verificar se o sistema foi instalado
if (!file_exists('../install/installed.lock')) {
    header('Location: ../install/install-educacional.php');
    exit;
}

// Incluir configurações
if (file_exists('../config/config.php')) {
    require_once '../config/config.php';
} else {
    require_once '../config/database-educacional.php';
}

// Iniciar sessão
session_start();

// Verificar se usuário está logado
$loggedIn = isset($_SESSION['user_id']);
$user = null;

if ($loggedIn) {
    try {
        $pdo = connectDatabase();
        $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = ? AND ativo = 1");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();
        
        if (!$user) {
            // Usuário não encontrado ou inativo
            session_destroy();
            $loggedIn = false;
        }
    } catch (Exception $e) {
        logMessage('Erro ao verificar usuário: ' . $e->getMessage(), 'error');
        session_destroy();
        $loggedIn = false;
    }
}

// Processar logout
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: index-educacional.php');
    exit;
}

// Processar login
$loginError = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$loggedIn) {
    $email = $_POST['email'] ?? '';
    $senha = $_POST['senha'] ?? '';
    
    if (empty($email) || empty($senha)) {
        $loginError = 'Email e senha são obrigatórios';
    } else {
        try {
            $pdo = connectDatabase();
            $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE email = ? AND ativo = 1");
            $stmt->execute([$email]);
            $userData = $stmt->fetch();
            
            if ($userData && password_verify($senha, $userData['senha_hash'])) {
                // Login bem-sucedido
                $_SESSION['user_id'] = $userData['id'];
                $_SESSION['user_name'] = $userData['nome'];
                $_SESSION['user_type'] = $userData['tipo_usuario'];
                
                // Atualizar último acesso
                $stmt = $pdo->prepare("UPDATE usuarios SET ultimo_acesso = NOW() WHERE id = ?");
                $stmt->execute([$userData['id']]);
                
                // Registrar atividade
                $stmt = $pdo->prepare("INSERT INTO historico_atividades (usuario_id, tipo_atividade, detalhes) VALUES (?, ?, ?)");
                $stmt->execute([$userData['id'], 'login', 'Login realizado com sucesso']);
                
                header('Location: index-educacional.php');
                exit;
            } else {
                $loginError = 'Credenciais inválidas';
            }
        } catch (Exception $e) {
            logMessage('Erro no login: ' . $e->getMessage(), 'error');
            $loginError = 'Erro interno do sistema';
        }
    }
}

// Buscar estatísticas do sistema
$stats = [];
try {
    if ($loggedIn) {
        $pdo = connectDatabase();
        
        // Total de categorias
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM categorias_ataques WHERE ativo = 1");
        $stats['categorias'] = $stmt->fetch()['total'];
        
        // Total de tipos de ataques
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM tipos_ataques WHERE ativo = 1");
        $stats['ataques'] = $stmt->fetch()['total'];
        
        // Total de exercícios
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM exercicios WHERE ativo = 1");
        $stats['exercicios'] = $stmt->fetch()['total'];
        
        // Total de quizzes
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM quizzes WHERE ativo = 1");
        $stats['quizzes'] = $stmt->fetch()['total'];
        
        // Progresso do usuário
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM progresso_alunos WHERE usuario_id = ? AND concluido = 1");
        $stmt->execute([$user['id']]);
        $stats['progresso'] = $stmt->fetch()['total'];
        
        // Pontos do usuário
        $stats['pontos'] = $user['pontos'] ?? 0;
    }
} catch (Exception $e) {
    logMessage('Erro ao buscar estatísticas: ' . $e->getMessage(), 'error');
}

$assetsBaseUrl = defined('ASSETS_URL') ? ASSETS_URL : '../assets';
$preferenceDefaults = function_exists('getDefaultPreferences') ? getDefaultPreferences() : [
    'tema' => 'dark',
    'tamanho_fonte' => 'medio',
    'alto_contraste' => 0,
    'reduzir_animacoes' => 0,
    'leitor_tela' => 0,
    'espacamento' => 'normal',
    'densidade_info' => 'media',
    'notificacoes_email' => 1,
    'notificacoes_push' => 0
];
$userPreferences = function_exists('getUserPreferences')
    ? getUserPreferences($loggedIn && $user ? (int)$user['id'] : null)
    : $preferenceDefaults;
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SACSWeb Educacional - Sistema de Ensino de Ataques Cibernéticos</title>
    <link rel="icon" type="image/png" href="<?= ASSETS_URL ?>/images/icone.png">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="<?= $assetsBaseUrl ?>/css/sacsweb-unified.css" rel="stylesheet">
    <script>
        window.SACSWEB_PREFERENCES = <?= json_encode($userPreferences, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    </script>
    <script src="<?= $assetsBaseUrl ?>/js/preferences.js" defer></script>
</head>
<body class="page-index-educacional">
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="#">
                <img src="<?= $assetsBaseUrl ?>/images/icone.png" alt="SACSWeb Logo" style="height: 40px; margin-right: 10px;"> <span class="text-primary">SACSWeb</span> <span>Educacional</span>
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="#sobre">Sobre</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#recursos">Recursos</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#contato">Contato</a>
                    </li>
                </ul>
                
                <ul class="navbar-nav">
                    <?php if ($loggedIn): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                                <i class="fas fa-user"></i> <?php echo htmlspecialchars($user['nome']); ?>
                            </a>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                                <li><a class="dropdown-item" href="perfil.php"><i class="fas fa-user-edit"></i> Perfil</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="?logout=1"><i class="fas fa-sign-out-alt"></i> Sair</a></li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="#" data-bs-toggle="modal" data-bs-target="#loginModal">
                                <i class="fas fa-sign-in-alt"></i> Entrar
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#" data-bs-toggle="modal" data-bs-target="#registerModal">
                                <i class="fas fa-user-plus"></i> Cadastrar
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container py-5">
        <!-- Hero Section -->
        <div class="hero-section p-5 text-center mb-5">
            <h1 class="hero-title mb-4">
                Aprenda Segurança Cibernética de Forma Interativa
            </h1>
            <p class="lead mb-4">
                O SACSWeb Educacional é um sistema completo para ensinar sobre ataques cibernéticos, 
                vulnerabilidades e medidas de proteção através de teoria e prática.
            </p>
            <?php if (!$loggedIn): ?>
                <div class="d-flex justify-content-center gap-3">
                    <button class="btn btn-primary btn-lg" data-bs-toggle="modal" data-bs-target="#loginModal">
                        <i class="fas fa-sign-in-alt"></i> Começar Agora
                    </button>
                    <a href="#sobre" class="btn btn-outline-primary btn-lg">
                        <i class="fas fa-info-circle"></i> Saiba Mais
                    </a>
                </div>
            <?php else: ?>
                <div class="d-flex justify-content-center gap-3">
                    <a href="dashboard.php" class="btn btn-primary btn-lg">
                        <i class="fas fa-tachometer-alt"></i> Acessar Dashboard
                    </a>
                    <a href="modulos.php" class="btn btn-outline-primary btn-lg">
                        <i class="fas fa-book"></i> Ver Módulos
                    </a>
                </div>
            <?php endif; ?>
        </div>

        <?php if ($loggedIn): ?>
        <!-- Dashboard Rápido -->
        <div class="row mb-5">
            <div class="col-12">
                <h2 class="text-white mb-4 text-center">
                    <i class="fas fa-tachometer-alt"></i> Seu Progresso
                </h2>
            </div>
            
            <div class="col-md-3 mb-3">
                <div class="stats-card p-4 text-center">
                    <div class="progress-ring mb-3">
                        <svg width="120" height="120">
                            <circle class="progress-bg" cx="60" cy="60" r="45"></circle>
                            <circle class="progress-fill" cx="60" cy="60" r="45" 
                                    stroke-dashoffset="<?php echo 283 - (283 * ($stats['progresso'] / max($stats['ataques'], 1))); ?>"></circle>
                        </svg>
                    </div>
                    <h4><?php echo $stats['progresso']; ?>/<?php echo $stats['ataques']; ?></h4>
                    <p class="mb-0">Módulos Concluídos</p>
                </div>
            </div>
            
            <div class="col-md-3 mb-3">
                <div class="stats-card p-4 text-center">
                    <i class="fas fa-star fa-3x mb-3"></i>
                    <h4><?php echo $stats['pontos']; ?></h4>
                    <p class="mb-0">Pontos Acumulados</p>
                </div>
            </div>
            
            <div class="col-md-3 mb-3">
                <div class="stats-card p-4 text-center">
                    <i class="fas fa-trophy fa-3x mb-3"></i>
                    <h4><?php echo $stats['exercicios']; ?></h4>
                    <p class="mb-0">Exercícios Disponíveis</p>
                </div>
            </div>
            
            <div class="col-md-3 mb-3">
                <div class="stats-card p-4 text-center">
                    <i class="fas fa-question-circle fa-3x mb-3"></i>
                    <h4><?php echo $stats['quizzes']; ?></h4>
                    <p class="mb-0">Quizzes para Testar</p>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Recursos -->
        <div id="recursos" class="row mb-5">
            <div class="col-12">
                <h2 class="text-white mb-4 text-center">
                    <i class="fas fa-star"></i> Recursos Educacionais
                </h2>
            </div>
            
            <div class="col-md-4 mb-4">
                <div class="feature-card p-4 text-center h-100">
                    <div class="feature-icon">
                        <i class="fas fa-book-open"></i>
                    </div>
                    <h4>Teoria Completa</h4>
                    <p class="text-light">
                        Explicações detalhadas sobre cada tipo de ataque cibernético, 
                        como funcionam e por que acontecem.
                    </p>
                </div>
            </div>
            
            <div class="col-md-4 mb-4">
                <div class="feature-card p-4 text-center h-100">
                    <div class="feature-icon">
                        <i class="fas fa-code"></i>
                    </div>
                    <h4>Exercícios Práticos</h4>
                    <p class="text-light">
                        Aplique o conhecimento em exercícios práticos de código 
                        e simulações de vulnerabilidades.
                    </p>
                </div>
            </div>
            
            <div class="col-md-4 mb-4">
                <div class="feature-card p-4 text-center h-100">
                    <div class="feature-icon">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                    <h4>Medidas de Proteção</h4>
                    <p class="text-light">
                        Aprenda as melhores práticas para proteger sistemas 
                        contra ataques cibernéticos.
                    </p>
                </div>
            </div>
        </div>

        <!-- Categorias de Ataques -->
        <div class="row mb-5">
            <div class="col-12">
                <h2 class="text-white mb-4 text-center">
                    <i class="fas fa-list"></i> Categorias de Ataques
                </h2>
            </div>
            
            <?php
            try {
                if ($loggedIn) {
                    $pdo = connectDatabase();
                    $stmt = $pdo->query("SELECT * FROM categorias_ataques WHERE ativo = 1 ORDER BY ordem");
                    $categorias = $stmt->fetchAll();
                    
                    foreach ($categorias as $categoria): ?>
                        <div class="col-md-4 mb-4">
                            <div class="feature-card p-4 text-center h-100">
                                <div class="feature-icon" style="color: <?php echo $categoria['cor']; ?>">
                                    <i class="fas fa-<?php echo $categoria['icone']; ?>"></i>
                                </div>
                                <h4><?php echo htmlspecialchars($categoria['nome']); ?></h4>
                                <p class="text-light">
                                    <?php echo htmlspecialchars($categoria['descricao']); ?>
                                </p>
                                <?php if ($loggedIn): ?>
                                    <a href="categoria.php?id=<?php echo $categoria['id']; ?>" class="btn btn-outline-primary">
                                        <i class="fas fa-arrow-right"></i> Explorar
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach;
                }
            } catch (Exception $e) {
                logMessage('Erro ao buscar categorias: ' . $e->getMessage(), 'error');
            }
            ?>
        </div>

        <!-- Sobre -->
        <div id="sobre" class="hero-section p-5 mb-5">
            <div class="row">
                <div class="col-md-6">
                    <h3 class="mb-4">
                        <i class="fas fa-graduation-cap"></i> Sobre o SACSWeb Educacional
                    </h3>
                    <p class="lead">
                        Desenvolvido como projeto de TCC, o SACSWeb Educacional é um sistema 
                        inovador que combina teoria e prática para ensinar segurança cibernética.
                    </p>
                    <p>
                        Nosso objetivo é proporcionar uma experiência de aprendizado interativa 
                        e envolvente, permitindo que os alunos compreendam não apenas os conceitos 
                        teóricos, mas também as aplicações práticas e medidas de proteção.
                    </p>
                    <ul class="list-unstyled">
                        <li><i class="fas fa-check text-success me-2"></i> Conteúdo estruturado e organizado</li>
                        <li><i class="fas fa-check text-success me-2"></i> Exercícios práticos interativos</li>
                        <li><i class="fas fa-check text-success me-2"></i> Sistema de gamificação</li>
                        <li><i class="fas fa-check text-success me-2"></i> Acompanhamento de progresso</li>
                    </ul>
                </div>
                <div class="col-md-6">
                    <div class="text-center">
                        <i class="fas fa-shield-alt text-primary" style="font-size: 8rem;"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Contato -->
        <div id="contato" class="hero-section p-5">
            <div class="text-center">
                <h3 class="mb-4">
                    <i class="fas fa-envelope"></i> Entre em Contato
                </h3>
                <p class="lead mb-4">
                    Tem dúvidas sobre o sistema ou sugestões de melhoria? 
                    Entre em contato conosco!
                </p>
                <div class="row justify-content-center">
                    <div class="col-md-4">
                        <div class="text-center mb-3">
                            <i class="fas fa-envelope fa-2x text-primary mb-2"></i>
                            <h5>Email</h5>
                            <p>suporte@sacsweb-educacional.com</p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="text-center mb-3">
                            <i class="fas fa-github fa-2x text-primary mb-2"></i>
                            <h5>GitHub</h5>
                            <p>github.com/seu-usuario/sacsweb-educacional</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Login -->
    <div class="modal fade" id="loginModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-sign-in-alt"></i> Entrar no Sistema
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="post">
                    <div class="modal-body">
                        <?php if ($loginError): ?>
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($loginError); ?>
                            </div>
                        <?php endif; ?>
                        
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>
                        <div class="mb-3">
                            <label for="senha" class="form-label">Senha</label>
                            <input type="password" class="form-control" id="senha" name="senha" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-sign-in-alt"></i> Entrar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal de Registro -->
    <div class="modal fade" id="registerModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-user-plus"></i> Criar Conta
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> 
                        Para criar uma conta, entre em contato com o administrador do sistema.
                    </div>
                    <p class="mb-0">
                        O registro de novos usuários é controlado pelos administradores 
                        para garantir a segurança e qualidade do sistema educacional.
                    </p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    <script>
        // Animar progresso do usuário
        document.addEventListener('DOMContentLoaded', function() {
            const progressFill = document.querySelector('.progress-fill');
            if (progressFill) {
                setTimeout(() => {
                    progressFill.style.transition = 'stroke-dashoffset 1s ease-in-out';
                }, 500);
            }
        });
    </script>
</body>
</html> 