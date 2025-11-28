<?php
/**
 * SACSWeb Educacional - Página de Login e Registro
 * Sistema para Ensino de Ataques Cibernéticos e Proteções
 * 
 * @package SACSWeb
 * @version 2.0.0
 * @author SACSWeb Development Team
 */

require_once '../config/config.php';

// ============================================================================
// FUNÇÕES DE PROCESSAMENTO
// ============================================================================

/**
 * Processa o registro de um novo usuário
 * 
 * Valida todos os dados de entrada, verifica duplicatas e cria a conta
 * 
 * @return void
 */
function processarRegistro() {
    // Sanitizar e validar entrada
    $nome = trim($_POST['nome'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $senha = $_POST['senha'] ?? '';
    $confirmarSenha = $_POST['confirmar_senha'] ?? '';
    $nivelConhecimento = $_POST['nivel_conhecimento'] ?? '';
    $aceitarTermos = isset($_POST['aceitar_termos']);
    
    // Validações básicas
    if (empty($nome) || empty($email) || empty($username) || empty($senha) || empty($confirmarSenha) || empty($nivelConhecimento)) {
        showError('Todos os campos são obrigatórios');
        return;
    }
    
    if (!$aceitarTermos) {
        showError('Você deve aceitar os termos de uso');
        return;
    }
    
    // Validações específicas
    if (strlen($nome) < 3) {
        showError('Nome deve ter pelo menos 3 caracteres');
        return;
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        showError('Email inválido');
        return;
    }
    
    // Validação de username
    if (strlen($username) < 3) {
        showError('Nome de usuário deve ter pelo menos 3 caracteres');
        return;
    }
    
    if (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        showError('Nome de usuário deve conter apenas letras, números e underscore');
        return;
    }
    
    if (strlen($senha) < 8) {
        showError('Senha deve ter pelo menos 8 caracteres');
        return;
    }
    
    if ($senha !== $confirmarSenha) {
        showError('As senhas não coincidem');
        return;
    }
    
    if (!in_array($nivelConhecimento, ['iniciante', 'intermediario', 'avancado'])) {
        showError('Nível de conhecimento inválido');
        return;
    }
    
    // Processar registro no banco de dados
    try {
        $pdo = connectDatabase();
        
        // Verificar se email já existe (prevenção de duplicatas)
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM usuarios WHERE email = ?");
        $stmt->execute([$email]);
        $result = $stmt->fetch();
        
        if ($result && $result['count'] > 0) {
            showError('Este email já está em uso');
            return;
        }
        
        // Verificar se username já existe
        // Primeiro verificar se campo username existe na tabela
        $stmt = $pdo->query("SHOW COLUMNS FROM usuarios LIKE 'username'");
        $hasUsernameField = $stmt->rowCount() > 0;
        
        if ($hasUsernameField) {
            $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM usuarios WHERE username = ?");
            $stmt->execute([$username]);
            $result = $stmt->fetch();
            
            if ($result && $result['count'] > 0) {
                showError('Este nome de usuário já está em uso');
                return;
            }
        } else {
            // Se não tem campo username, verificar se username não é igual a algum email existente
            $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM usuarios WHERE email = ?");
            $stmt->execute([$username]);
            $result = $stmt->fetch();
            
            if ($result && $result['count'] > 0) {
                showError('Este nome de usuário já está em uso');
                return;
            }
        }
        
        // Criar hash seguro da senha usando bcrypt
        $senhaHash = password_hash($senha, PASSWORD_BCRYPT, ['cost' => 12]);
        
        // Inserir novo usuário no banco de dados
        // Se campo username existe, incluir no INSERT
        if ($hasUsernameField) {
            $stmt = $pdo->prepare("
                INSERT INTO usuarios 
                (nome, email, username, senha_hash, tipo_usuario, nivel_conhecimento, ativo, data_cadastro) 
                VALUES (?, ?, ?, ?, 'aluno', ?, 1, NOW())
            ");
            $result = $stmt->execute([$nome, $email, $username, $senhaHash, $nivelConhecimento]);
        } else {
            // Se não tem campo username, usar email como identificador
            $stmt = $pdo->prepare("
                INSERT INTO usuarios 
                (nome, email, senha_hash, tipo_usuario, nivel_conhecimento, ativo, data_cadastro) 
                VALUES (?, ?, ?, 'aluno', ?, 1, NOW())
            ");
            $result = $stmt->execute([$nome, $email, $senhaHash, $nivelConhecimento]);
        }
        
        if ($result) {
            $userId = $pdo->lastInsertId();
            
            // Registrar atividade de registro
            $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
            $stmt = $pdo->prepare("
                INSERT INTO logs_atividade 
                (usuario_id, acao, detalhes, ip_address, data_hora) 
                VALUES (?, 'REGISTRO', 'Nova conta criada', ?, NOW())
            ");
            $stmt->execute([$userId, $ipAddress]);
            
            logMessage("Novo usuário registrado: " . $email, 'info');
            
            showSuccess('Conta criada com sucesso! Você pode fazer login agora.');
            $_SESSION['active_tab'] = 'login';
        } else {
            showError('Erro ao criar conta. Tente novamente.');
        }
    } catch (PDOException $e) {
        // Não expor detalhes do erro ao usuário (segurança)
        showError('Erro ao conectar com o banco de dados');
        logMessage('Erro no registro: ' . $e->getMessage(), 'error');
    }
}

/**
 * Processa o login de um usuário existente
 * 
 * Valida credenciais e cria sessão segura
 * 
 * @return void
 */
function processarLogin() {
    // Sanitizar entrada (pode ser email ou username)
    $emailOuUsername = trim($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';
    
    // Validações básicas
    if (empty($emailOuUsername) || empty($senha)) {
        showError('Email/usuário e senha são obrigatórios');
        return;
    }
    
    // Autenticar usuário (pode ser por email ou username)
    try {
        $pdo = connectDatabase();
        
        // Verificar se campo username existe
        $stmt = $pdo->query("SHOW COLUMNS FROM usuarios LIKE 'username'");
        $hasUsernameField = $stmt->rowCount() > 0;
        
        // Buscar usuário por email ou username
        if ($hasUsernameField) {
            $stmt = $pdo->prepare("SELECT id, nome, email, senha_hash, tipo_usuario FROM usuarios WHERE (email = ? OR username = ?) AND ativo = 1");
            $stmt->execute([$emailOuUsername, $emailOuUsername]);
        } else {
            // Se não tem campo username, buscar apenas por email
            $stmt = $pdo->prepare("SELECT id, nome, email, senha_hash, tipo_usuario FROM usuarios WHERE email = ? AND ativo = 1");
            $stmt->execute([$emailOuUsername]);
        }
        
        $user = $stmt->fetch();
        
        if ($user && password_verify($senha, $user['senha_hash'])) {
            // Criar sessão segura
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['nome'];
            $_SESSION['user_type'] = $user['tipo_usuario'];
            $_SESSION['csrf_token'] = generateCSRFToken();
            
            logMessage("Login realizado com sucesso para: " . $emailOuUsername, 'info');
            showSuccess('Login realizado com sucesso!');
            redirect('/sacsweb/website/dashboard.php');
        } else {
            logMessage("Tentativa de login falhada para: " . $emailOuUsername, 'warning');
            showError('Email/usuário ou senha incorretos');
        }
    } catch (PDOException $e) {
        logMessage('Erro na autenticação: ' . $e->getMessage(), 'error');
        showError('Erro ao conectar com o banco de dados');
    }
}

// ============================================================================
// PROCESSAMENTO DE REQUISIÇÕES
// ============================================================================

// Verificar se usuário já está logado
if (isLoggedIn()) {
    redirect('/sacsweb/website/dashboard.php');
}

// Processar formulários POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validar token CSRF
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (!validateCSRFToken($csrfToken)) {
        showError('Token de segurança inválido. Por favor, recarregue a página.');
    } else {
        // Processar ação específica
        $action = $_POST['action'] ?? 'login';
        
        if ($action === 'register') {
            processarRegistro();
        } else {
            processarLogin();
        }
    }
}

// Obter mensagens flash e determinar aba ativa
$messages = getFlashMessages();
$activeTab = $_SESSION['active_tab'] ?? 'login';
unset($_SESSION['active_tab']);
$userPreferences = getUserPreferences($_SESSION['user_id'] ?? null);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Sistema Educacional de Segurança Cibernética - Login e Registro">
    <title>Login - SACSWeb Educacional</title>
    <link rel="icon" type="image/png" href="<?= ASSETS_URL ?>/images/icone.png">
    
    <!-- Bootstrap 5.3.7 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome 6.0 -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <!-- Google Fonts - Inter -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <link href="<?= ASSETS_URL ?>/css/sacsweb-unified.css" rel="stylesheet">
    <script>
        window.SACSWEB_PREFERENCES = <?= json_encode($userPreferences, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    </script>
    <script src="<?= ASSETS_URL ?>/js/preferences.js" defer></script>
</head>
<body class="page-login">
    <!-- Hero Section de Fundo -->
    <div class="hero-section">
        <i class="fas fa-shield-alt hero-icon"></i>
    </div>

    <!-- Container Principal -->
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8 col-md-10">
                <div class="auth-container">
                    <!-- Cabeçalho -->
                    <div class="auth-header">
                        <img src="<?= ASSETS_URL ?>/images/icone.png" alt="SACSWeb Logo" class="logo-img">
                        <h1>SACSWeb</h1>
                        <p>Sistema Educacional de Segurança Cibernética</p>
                    </div>

                    <!-- Mensagens de Erro/Sucesso -->
                    <?php if (isset($messages['error'])): ?>
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle"></i> 
                            <?= htmlspecialchars($messages['error'], ENT_QUOTES, 'UTF-8') ?>
                        </div>
                    <?php endif; ?>

                    <?php if (isset($messages['success'])): ?>
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle"></i> 
                            <?= htmlspecialchars($messages['success'], ENT_QUOTES, 'UTF-8') ?>
                        </div>
                    <?php endif; ?>

                    <!-- Abas de Login/Registro -->
                    <ul class="nav nav-tabs" id="authTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link <?= $activeTab === 'login' ? 'active' : '' ?>" 
                                    id="login-tab" 
                                    data-bs-toggle="tab" 
                                    data-bs-target="#login" 
                                    type="button" 
                                    role="tab">
                                <i class="fas fa-sign-in-alt"></i> Entrar
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link <?= $activeTab === 'register' ? 'active' : '' ?>" 
                                    id="register-tab" 
                                    data-bs-toggle="tab" 
                                    data-bs-target="#register" 
                                    type="button" 
                                    role="tab">
                                <i class="fas fa-user-plus"></i> Registrar
                            </button>
                        </li>
                    </ul>

                    <!-- Conteúdo das Abas -->
                    <div class="tab-content" id="authTabsContent">
                        <!-- Aba de Login -->
                        <div class="tab-pane fade <?= $activeTab === 'login' ? 'show active' : '' ?>" 
                             id="login" 
                             role="tabpanel">
                            <form method="POST" action="" id="loginForm">
                                <input type="hidden" name="action" value="login">
                                <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                                
                                <div class="mb-3">
                                    <label for="loginEmail" class="form-label">
                                        <i class="fas fa-envelope"></i> Email ou Nome de Usuário
                                    </label>
                                    <input type="text" 
                                           class="form-control" 
                                           id="loginEmail" 
                                           name="email" 
                                           value="<?= htmlspecialchars($_POST['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>" 
                                           required
                                           autocomplete="username">
                                </div>

                                <div class="mb-3">
                                    <label for="loginPassword" class="form-label">
                                        <i class="fas fa-lock"></i> Senha
                                    </label>
                                    <input type="password" 
                                           class="form-control" 
                                           id="loginPassword" 
                                           name="senha" 
                                           required
                                           autocomplete="current-password">
                                </div>

                                <div class="mb-3 form-check">
                                    <input type="checkbox" 
                                           class="form-check-input" 
                                           id="rememberMe" 
                                           name="lembrar">
                                    <label class="form-check-label" for="rememberMe">
                                        Lembrar de mim
                                    </label>
                                </div>

                                <div class="d-grid">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-sign-in-alt"></i> Entrar no Sistema
                                    </button>
                                </div>
                            </form>
                        </div>

                        <!-- Aba de Registro -->
                        <div class="tab-pane fade <?= $activeTab === 'register' ? 'show active' : '' ?>" 
                             id="register" 
                             role="tabpanel">
                            <form method="POST" action="" id="registerForm">
                                <input type="hidden" name="action" value="register">
                                <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                                
                                <div class="mb-3">
                                    <label for="registerNome" class="form-label">
                                        <i class="fas fa-user"></i> Nome Completo
                                    </label>
                                    <input type="text" 
                                           class="form-control" 
                                           id="registerNome" 
                                           name="nome" 
                                           value="<?= htmlspecialchars($_POST['nome'] ?? '', ENT_QUOTES, 'UTF-8') ?>" 
                                           required
                                           autocomplete="name"
                                           minlength="3">
                                </div>

                                <div class="mb-3">
                                    <label for="registerEmail" class="form-label">
                                        <i class="fas fa-envelope"></i> Email
                                    </label>
                                    <input type="email" 
                                           class="form-control" 
                                           id="registerEmail" 
                                           name="email" 
                                           value="<?= htmlspecialchars($_POST['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>" 
                                           required
                                           autocomplete="email"
                                           placeholder="seu@email.com">
                                    <div class="form-text" id="emailHelp">
                                        Seu endereço de email
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="registerUsername" class="form-label">
                                        <i class="fas fa-at"></i> Nome de Usuário
                                    </label>
                                    <input type="text" 
                                           class="form-control" 
                                           id="registerUsername" 
                                           name="username" 
                                           value="<?= htmlspecialchars($_POST['username'] ?? '', ENT_QUOTES, 'UTF-8') ?>" 
                                           required
                                           autocomplete="username"
                                           minlength="3"
                                           pattern="[a-zA-Z0-9_]+"
                                           placeholder="nomeusuario">
                                    <div class="form-text" id="usernameHelp">
                                        Mínimo 3 caracteres, apenas letras, números e underscore
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="registerPassword" class="form-label">
                                                <i class="fas fa-lock"></i> Senha
                                            </label>
                                            <input type="password" 
                                                   class="form-control" 
                                                   id="registerPassword" 
                                                   name="senha" 
                                                   required
                                                   autocomplete="new-password"
                                                   minlength="8">
                                            <div class="password-strength" id="passwordStrength"></div>
                                            <div class="form-text">
                                                Mínimo 8 caracteres
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="registerConfirmPassword" class="form-label">
                                                <i class="fas fa-lock"></i> Confirmar Senha
                                            </label>
                                            <input type="password" 
                                                   class="form-control" 
                                                   id="registerConfirmPassword" 
                                                   name="confirmar_senha" 
                                                   required
                                                   autocomplete="new-password"
                                                   minlength="8">
                                        </div>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="registerNivel" class="form-label">
                                        <i class="fas fa-graduation-cap"></i> Nível de Conhecimento
                                    </label>
                                    <select class="form-control" 
                                            id="registerNivel" 
                                            name="nivel_conhecimento" 
                                            required>
                                        <option value="">Selecione seu nível</option>
                                        <option value="iniciante" <?= (isset($_POST['nivel_conhecimento']) && $_POST['nivel_conhecimento'] === 'iniciante') ? 'selected' : '' ?>>
                                            Iniciante - Primeiro contato com segurança
                                        </option>
                                        <option value="intermediario" <?= (isset($_POST['nivel_conhecimento']) && $_POST['nivel_conhecimento'] === 'intermediario') ? 'selected' : '' ?>>
                                            Intermediário - Algum conhecimento básico
                                        </option>
                                        <option value="avancado" <?= (isset($_POST['nivel_conhecimento']) && $_POST['nivel_conhecimento'] === 'avancado') ? 'selected' : '' ?>>
                                            Avançado - Experiência em TI/segurança
                                        </option>
                                    </select>
                                </div>

                                <div class="mb-3 form-check">
                                    <input type="checkbox" 
                                           class="form-check-input" 
                                           id="aceitarTermos" 
                                           name="aceitar_termos" 
                                           required>
                                    <label class="form-check-label" for="aceitarTermos">
                                        Aceito os <a href="#" data-bs-toggle="modal" data-bs-target="#termosModal">termos de uso</a> e 
                                        <a href="#" data-bs-toggle="modal" data-bs-target="#privacidadeModal">política de privacidade</a>
                                    </label>
                                </div>

                                <div class="d-grid">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-user-plus"></i> Criar Conta
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <hr class="my-4">

                    <div class="back-link">
                        <a href="../index.php">
                            <i class="fas fa-arrow-left"></i> Voltar para a Homepage
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Termos de Uso -->
    <div class="modal fade" id="termosModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Termos de Uso - SACSWeb Educacional</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <h6>1. Uso Educacional</h6>
                    <p>Este sistema é destinado exclusivamente para fins educacionais e de aprendizado sobre segurança cibernética.</p>
                    
                    <h6>2. Responsabilidade</h6>
                    <p>O usuário é responsável por usar o conhecimento adquirido de forma ética e legal.</p>
                    
                    <h6>3. Conteúdo</h6>
                    <p>Todo o conteúdo é fornecido "como está" para fins educacionais.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Política de Privacidade -->
    <div class="modal fade" id="privacidadeModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Política de Privacidade - SACSWeb Educacional</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <h6>1. Coleta de Dados</h6>
                    <p>Coletamos apenas dados necessários para o funcionamento do sistema educacional.</p>
                    
                    <h6>2. Uso dos Dados</h6>
                    <p>Os dados são usados exclusivamente para acompanhar o progresso educacional.</p>
                    
                    <h6>3. Segurança</h6>
                    <p>Implementamos medidas de segurança para proteger suas informações.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        /**
         * Validação de força da senha
         * Calcula a força baseada em critérios de segurança
         */
        function checkPasswordStrength(password) {
            const strengthBar = document.getElementById('passwordStrength');
            if (!strengthBar) return;
            
            let strength = 0;
            
            // Critérios de força
            if (password.length >= 8) strength++;
            if (/[a-z]/.test(password)) strength++;
            if (/[A-Z]/.test(password)) strength++;
            if (/[0-9]/.test(password)) strength++;
            if (/[^A-Za-z0-9]/.test(password)) strength++;

            // Aplicar classe de força
            strengthBar.className = 'password-strength';
            if (strength <= 2) {
                strengthBar.classList.add('strength-weak');
            } else if (strength <= 3) {
                strengthBar.classList.add('strength-medium');
            } else if (strength <= 4) {
                strengthBar.classList.add('strength-strong');
            } else {
                strengthBar.classList.add('strength-very-strong');
            }
        }

        /**
         * Verificação automática de disponibilidade
         * Verifica email e username automaticamente com debounce
         */
        let emailAvailable = false;
        let usernameAvailable = false;
        let emailTimeout = null;
        let usernameTimeout = null;

        /**
         * Verificação automática de email com debounce
         */
        async function checkEmailAvailability() {
            const emailInput = document.getElementById('registerEmail');
            const emailHelp = document.getElementById('emailHelp');
            
            if (!emailInput || !emailHelp) return;
            
            const email = emailInput.value.trim();
            
            if (!email) {
                emailInput.classList.remove('is-valid', 'is-invalid');
                emailHelp.className = 'form-text';
                emailHelp.textContent = 'Seu endereço de email';
                emailAvailable = false;
                return;
            }
            
            if (!email.includes('@')) {
                emailInput.classList.remove('is-valid');
                emailInput.classList.add('is-invalid');
                emailHelp.className = 'form-text text-danger';
                emailHelp.textContent = 'Email inválido';
                emailAvailable = false;
                return;
            }
            
            try {
                const response = await fetch('../auth/check_availability.php?email=' + encodeURIComponent(email), {
                    headers: { 'Accept': 'application/json' }
                });
                const data = await response.json();
                
                if (data.email) {
                    if (data.email.available) {
                        emailInput.classList.remove('is-invalid');
                        emailInput.classList.add('is-valid');
                        emailHelp.className = 'form-text text-success';
                        emailHelp.textContent = data.email.message || 'Email disponível';
                        emailAvailable = true;
                    } else {
                        emailInput.classList.remove('is-valid');
                        emailInput.classList.add('is-invalid');
                        emailHelp.className = 'form-text text-danger';
                        emailHelp.textContent = data.email.message || 'Email já está em uso';
                        emailAvailable = false;
                    }
                }
            } catch (error) {
                console.error('Erro ao verificar email:', error);
                emailInput.classList.remove('is-valid', 'is-invalid');
                emailHelp.className = 'form-text text-danger';
                emailHelp.textContent = 'Erro ao verificar. Tente novamente.';
                emailAvailable = false;
            }
        }

        /**
         * Verificação automática de username com debounce
         */
        async function checkUsernameAvailability() {
            const usernameInput = document.getElementById('registerUsername');
            const usernameHelp = document.getElementById('usernameHelp');
            
            if (!usernameInput || !usernameHelp) return;
            
            const username = usernameInput.value.trim();
            
            if (!username) {
                usernameInput.classList.remove('is-valid', 'is-invalid');
                usernameHelp.className = 'form-text';
                usernameHelp.textContent = 'Mínimo 3 caracteres, apenas letras, números e underscore';
                usernameAvailable = false;
                return;
            }
            
            if (username.length < 3) {
                usernameInput.classList.remove('is-valid');
                usernameInput.classList.add('is-invalid');
                usernameHelp.className = 'form-text text-danger';
                usernameHelp.textContent = 'Nome de usuário deve ter pelo menos 3 caracteres';
                usernameAvailable = false;
                return;
            }
            
            if (!/^[a-zA-Z0-9_]+$/.test(username)) {
                usernameInput.classList.remove('is-valid');
                usernameInput.classList.add('is-invalid');
                usernameHelp.className = 'form-text text-danger';
                usernameHelp.textContent = 'Apenas letras, números e underscore são permitidos';
                usernameAvailable = false;
                return;
            }
            
            try {
                const response = await fetch('../auth/check_availability.php?username=' + encodeURIComponent(username), {
                    headers: { 'Accept': 'application/json' }
                });
                const data = await response.json();
                
                if (data.username) {
                    if (data.username.available) {
                        usernameInput.classList.remove('is-invalid');
                        usernameInput.classList.add('is-valid');
                        usernameHelp.className = 'form-text text-success';
                        usernameHelp.textContent = data.username.message || 'Nome de usuário disponível';
                        usernameAvailable = true;
                    } else {
                        usernameInput.classList.remove('is-valid');
                        usernameInput.classList.add('is-invalid');
                        usernameHelp.className = 'form-text text-danger';
                        usernameHelp.textContent = data.username.message || 'Nome de usuário já está em uso';
                        usernameAvailable = false;
                    }
                }
            } catch (error) {
                console.error('Erro ao verificar username:', error);
                usernameInput.classList.remove('is-valid', 'is-invalid');
                usernameHelp.className = 'form-text text-danger';
                usernameHelp.textContent = 'Erro ao verificar. Tente novamente.';
                usernameAvailable = false;
            }
        }

        /**
         * Inicialização quando DOM estiver pronto
         */
        document.addEventListener('DOMContentLoaded', function() {
            // Validação da senha em tempo real
            const passwordInput = document.getElementById('registerPassword');
            if (passwordInput) {
                passwordInput.addEventListener('input', function() {
                    checkPasswordStrength(this.value);
                });
            }

            // Verificação automática de EMAIL com debounce (500ms)
            const emailInput = document.getElementById('registerEmail');
            if (emailInput) {
                emailInput.addEventListener('input', function() {
                    // Limpar timeout anterior
                    if (emailTimeout) {
                        clearTimeout(emailTimeout);
                    }
                    
                    // Resetar estado visual inicialmente
                    this.classList.remove('is-valid', 'is-invalid');
                    const emailHelp = document.getElementById('emailHelp');
                    if (emailHelp) {
                        emailHelp.className = 'form-text';
                        emailHelp.textContent = 'Verificando...';
                    }
                    emailAvailable = false;
                    
                    // Agendar verificação após 500ms de inatividade
                    emailTimeout = setTimeout(() => {
                        checkEmailAvailability();
                    }, 500);
                });
            }

            // Verificação automática de USERNAME com debounce (500ms)
            const usernameInput = document.getElementById('registerUsername');
            if (usernameInput) {
                usernameInput.addEventListener('input', function() {
                    // Limpar timeout anterior
                    if (usernameTimeout) {
                        clearTimeout(usernameTimeout);
                    }
                    
                    // Resetar estado visual inicialmente
                    this.classList.remove('is-valid', 'is-invalid');
                    const usernameHelp = document.getElementById('usernameHelp');
                    if (usernameHelp) {
                        usernameHelp.className = 'form-text';
                        usernameHelp.textContent = 'Verificando...';
                    }
                    usernameAvailable = false;
                    
                    // Agendar verificação após 500ms de inatividade
                    usernameTimeout = setTimeout(() => {
                        checkUsernameAvailability();
                    }, 500);
                });
            }

            // Validação do formulário de registro
            const registerForm = document.getElementById('registerForm');
            if (registerForm) {
                registerForm.addEventListener('submit', function(e) {
                    const password = document.getElementById('registerPassword').value;
                    const confirmPassword = document.getElementById('registerConfirmPassword').value;
                    const username = usernameInput ? usernameInput.value.trim() : '';
                    
                    if (password !== confirmPassword) {
                        e.preventDefault();
                        alert('As senhas não coincidem!');
                        return false;
                    }
                    
                    if (password.length < 8) {
                        e.preventDefault();
                        alert('A senha deve ter pelo menos 8 caracteres!');
                        return false;
                    }

                    // Validar username
                    if (username.length < 3 || !/^[a-zA-Z0-9_]+$/.test(username)) {
                        e.preventDefault();
                        alert('Informe um nome de usuário válido (mínimo 3 caracteres, apenas letras, números e underscore)');
                        return false;
                    }

                    // Verificar se email e username estão disponíveis (verificação automática)
                    if (!emailAvailable) {
                        e.preventDefault();
                        alert('Por favor, aguarde a verificação do email ou verifique se o email está disponível.');
                        return false;
                    }

                    if (!usernameAvailable) {
                        e.preventDefault();
                        alert('Por favor, aguarde a verificação do nome de usuário ou verifique se está disponível.');
                        return false;
                    }
                });
            }

            // Validação do formulário de login
            const loginForm = document.getElementById('loginForm');
            if (loginForm) {
                loginForm.addEventListener('submit', function(e) {
                    const email = document.getElementById('loginEmail').value;
                    const password = document.getElementById('loginPassword').value;
                    
                    if (!email || !password) {
                        e.preventDefault();
                        alert('Preencha todos os campos!');
                        return false;
                    }
                });
            }
        });
    </script>
</body>
</html>
