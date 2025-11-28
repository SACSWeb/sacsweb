<?php
/**
 * SACSWeb Educacional - Página de Configurações
 * Permite que usuários configurem preferências de acessibilidade e visualização
 * Versão: 2.1.0
 */

require_once '../config/config.php';
requireLogin();

$user = getCurrentUser();
$pdo = connectDatabase();
$messages = getFlashMessages();

$preferencias = getUserPreferences($user['id'] ?? null);

// Processar formulário de configurações
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['salvar_configuracoes'])) {
    // Validar CSRF token
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        showError('Token de segurança inválido. Por favor, recarregue a página.');
        redirect('configuracoes.php');
    }
    
    try {
        // Sanitizar e validar dados
        $tema = in_array($_POST['tema'] ?? 'dark', ['dark', 'light', 'auto']) ? $_POST['tema'] : 'dark';
        $tamanho_fonte = in_array($_POST['tamanho_fonte'] ?? 'medio', ['pequeno', 'medio', 'grande']) ? $_POST['tamanho_fonte'] : 'medio';
        $alto_contraste = isset($_POST['alto_contraste']) ? 1 : 0;
        $reduzir_animacoes = isset($_POST['reduzir_animacoes']) ? 1 : 0;
        $leitor_tela = isset($_POST['leitor_tela']) ? 1 : 0;
        $espacamento = in_array($_POST['espacamento'] ?? 'normal', ['compacto', 'normal', 'amplo']) ? $_POST['espacamento'] : 'normal';
        $densidade_info = in_array($_POST['densidade_info'] ?? 'media', ['baixa', 'media', 'alta']) ? $_POST['densidade_info'] : 'media';
        $notificacoes_email = isset($_POST['notificacoes_email']) ? 1 : 0;
        $notificacoes_push = isset($_POST['notificacoes_push']) ? 1 : 0;
        
        // Atualizar preferências
        $stmt = $pdo->prepare("
            UPDATE usuario_preferencias 
            SET tema = ?, tamanho_fonte = ?, alto_contraste = ?, 
                reduzir_animacoes = ?, leitor_tela = ?, espacamento = ?, 
                densidade_info = ?, notificacoes_email = ?, notificacoes_push = ?
            WHERE usuario_id = ?
        ");
        $stmt->execute([
            $tema, $tamanho_fonte, $alto_contraste, $reduzir_animacoes,
            $leitor_tela, $espacamento, $densidade_info, $notificacoes_email,
            $notificacoes_push, $user['id']
        ]);
        
        // Salvar também no localStorage via JavaScript
        showSuccess('Configurações salvas com sucesso!');
        
        // Atualizar preferências locais
        $preferencias = getUserPreferences($user['id'], true);
        
        logMessage("Configurações atualizadas para usuário ID: " . $user['id'], 'info');
        
    } catch (PDOException $e) {
        logMessage('Erro ao salvar configurações: ' . $e->getMessage(), 'error');
        showError('Erro ao salvar configurações. Tente novamente.');
    }
    
    redirect('configuracoes.php');
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configurações - <?= SISTEMA_NOME ?></title>
    <link rel="icon" type="image/png" href="<?= ASSETS_URL ?>/images/icone.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="<?= ASSETS_URL ?>/css/sacsweb-unified.css" rel="stylesheet">
    <script>
        window.SACSWEB_PREFERENCES = <?= json_encode($preferencias, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    </script>
    <script src="<?= ASSETS_URL ?>/js/preferences.js" defer></script>
    
    <style>
        .settings-section {
            background: var(--dark-gray);
            border: 1px solid var(--light-gray);
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 20px;
        }
        
        .settings-section h5 {
            color: var(--primary-pink);
            margin-bottom: 20px;
            font-weight: 600;
        }
        
        .form-check-input:checked {
            background-color: var(--primary-pink);
            border-color: var(--primary-pink);
        }
        
        .form-select, .form-control {
            background: var(--dark-gray);
            border-color: var(--light-gray);
            color: var(--text-light);
        }
        
        .form-select:focus, .form-control:focus {
            background: var(--dark-gray);
            border-color: var(--primary-pink);
            color: var(--text-light);
        }
        
        .preview-box {
            background: var(--light-gray);
            border-radius: 10px;
            padding: 20px;
            margin-top: 15px;
            min-height: 100px;
        }

        .form-text {
            font-size: 0.875rem;
            color: var(--text-muted);
            margin-top: 0.25rem;
            transition: color var(--transition-base);
        }
    </style>
</head>
<body class="page-configuracoes">
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
                            <li><a class="dropdown-item active" href="configuracoes.php">
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
                <i class="fas fa-cog text-primary"></i> Configurações
            </h1>
            <p class="lead text-light">Personalize sua experiência de aprendizado</p>
        </div>

        <!-- Mensagens -->
        <?php if (isset($messages['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <i class="fas fa-check-circle"></i> <?= htmlspecialchars($messages['success']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($messages['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="fas fa-exclamation-triangle"></i> <?= htmlspecialchars($messages['error']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <form method="POST" action="" id="configForm">
            <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
            
            <!-- Configurações de Aparência -->
            <div class="settings-section">
                <h5><i class="fas fa-palette"></i> Aparência e Tema</h5>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="tema" class="form-label">Tema Visual</label>
                        <select class="form-select" id="tema" name="tema" required>
                            <option value="dark" <?= ($preferencias['tema'] ?? 'dark') === 'dark' ? 'selected' : '' ?>>Escuro</option>
                            <option value="light" <?= ($preferencias['tema'] ?? 'dark') === 'light' ? 'selected' : '' ?>>Claro</option>
                            <option value="auto" <?= ($preferencias['tema'] ?? 'dark') === 'auto' ? 'selected' : '' ?>>Automático (Sistema)</option>
                        </select>
                        <div class="form-text">Escolha o tema visual preferido</div>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label for="tamanho_fonte" class="form-label">Tamanho da Fonte</label>
                        <select class="form-select" id="tamanho_fonte" name="tamanho_fonte" required>
                            <option value="pequeno" <?= ($preferencias['tamanho_fonte'] ?? 'medio') === 'pequeno' ? 'selected' : '' ?>>Pequeno</option>
                            <option value="medio" <?= ($preferencias['tamanho_fonte'] ?? 'medio') === 'medio' ? 'selected' : '' ?>>Médio</option>
                            <option value="grande" <?= ($preferencias['tamanho_fonte'] ?? 'medio') === 'grande' ? 'selected' : '' ?>>Grande</option>
                        </select>
                        <div class="form-text">Ajuste o tamanho do texto para melhor leitura</div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="espacamento" class="form-label">Espaçamento</label>
                        <select class="form-select" id="espacamento" name="espacamento" required>
                            <option value="compacto" <?= ($preferencias['espacamento'] ?? 'normal') === 'compacto' ? 'selected' : '' ?>>Compacto</option>
                            <option value="normal" <?= ($preferencias['espacamento'] ?? 'normal') === 'normal' ? 'selected' : '' ?>>Normal</option>
                            <option value="amplo" <?= ($preferencias['espacamento'] ?? 'normal') === 'amplo' ? 'selected' : '' ?>>Amplo</option>
                        </select>
                        <div class="form-text">Controle o espaçamento entre elementos</div>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label for="densidade_info" class="form-label">Densidade de Informações</label>
                        <select class="form-select" id="densidade_info" name="densidade_info" required>
                            <option value="baixa" <?= ($preferencias['densidade_info'] ?? 'media') === 'baixa' ? 'selected' : '' ?>>Baixa</option>
                            <option value="media" <?= ($preferencias['densidade_info'] ?? 'media') === 'media' ? 'selected' : '' ?>>Média</option>
                            <option value="alta" <?= ($preferencias['densidade_info'] ?? 'media') === 'alta' ? 'selected' : '' ?>>Alta</option>
                        </select>
                        <div class="form-text">Quantidade de informações exibidas</div>
                    </div>
                </div>
            </div>

            <!-- Configurações de Acessibilidade -->
            <div class="settings-section">
                <h5><i class="fas fa-universal-access"></i> Acessibilidade</h5>
                
                <div class="mb-3">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="alto_contraste" name="alto_contraste" 
                               <?= ($preferencias['alto_contraste'] ?? 0) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="alto_contraste">
                            Alto Contraste
                        </label>
                    </div>
                    <div class="form-text">Aumenta o contraste entre texto e fundo para melhor legibilidade</div>
                </div>
                
                <div class="mb-3">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="reduzir_animacoes" name="reduzir_animacoes" 
                               <?= ($preferencias['reduzir_animacoes'] ?? 0) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="reduzir_animacoes">
                            Reduzir Animações
                        </label>
                    </div>
                    <div class="form-text">Reduz ou remove animações para melhorar a experiência</div>
                </div>
                
                <div class="mb-3">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="leitor_tela" name="leitor_tela" 
                               <?= ($preferencias['leitor_tela'] ?? 0) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="leitor_tela">
                            Suporte a Leitor de Tela
                        </label>
                    </div>
                    <div class="form-text">Otimiza a página para leitores de tela</div>
                </div>
            </div>

            <!-- Configurações de Notificações -->
            <div class="settings-section">
                <h5><i class="fas fa-bell"></i> Notificações</h5>
                
                <div class="mb-3">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="notificacoes_email" name="notificacoes_email" 
                               <?= ($preferencias['notificacoes_email'] ?? 1) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="notificacoes_email">
                            Notificações por Email
                        </label>
                    </div>
                    <div class="form-text">Receba atualizações e lembretes por email</div>
                </div>
                
                <div class="mb-3">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="notificacoes_push" name="notificacoes_push" 
                               <?= ($preferencias['notificacoes_push'] ?? 0) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="notificacoes_push">
                            Notificações Push
                        </label>
                    </div>
                    <div class="form-text">Receba notificações no navegador</div>
                </div>
            </div>

            <!-- Botões de Ação -->
            <div class="d-grid gap-2 d-md-flex justify-content-md-end mb-4">
                <a href="dashboard.php" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Cancelar
                </a>
                <button type="submit" name="salvar_configuracoes" class="btn btn-primary">
                    <i class="fas fa-save"></i> Salvar Configurações
                </button>
            </div>
        </form>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        document.addEventListener('sacsweb:preferences-ready', function () {
            const form = document.getElementById('configForm');
            if (!form) {
                return;
            }

            form.addEventListener('submit', function () {
                const formData = new FormData(form);
                const preferencias = {
                    tema: formData.get('tema') || 'dark',
                    tamanho_fonte: formData.get('tamanho_fonte') || 'medio',
                    alto_contraste: formData.has('alto_contraste'),
                    reduzir_animacoes: formData.has('reduzir_animacoes'),
                    leitor_tela: formData.has('leitor_tela'),
                    espacamento: formData.get('espacamento') || 'normal',
                    densidade_info: formData.get('densidade_info') || 'media',
                    notificacoes_email: formData.has('notificacoes_email'),
                    notificacoes_push: formData.has('notificacoes_push')
                };

                if (window.SACSWEB_PREFERENCES_API) {
                    window.SACSWEB_PREFERENCES_API.apply(preferencias, { persist: true });
                } else {
                    try {
                        localStorage.setItem('sacsweb_preferencias', JSON.stringify(preferencias));
                    } catch (error) {
                        console.warn('SACSWeb: não foi possível salvar preferências locais.', error);
                    }
                }
            });
        });
    </script>
</body>
</html>

