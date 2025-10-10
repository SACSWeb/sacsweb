<?php
/**
 * SACSWeb Educacional - Página Principal
 * Sistema para Ensino de Ataques Cibernéticos e Proteções
 * TCC - Foco Educacional
 */

require_once 'config/config.php';

// Se já estiver logado, redirecionar para dashboard
if (isLoggedIn()) {
    redirect('/sacsweb/website/dashboard.php');
}

$messages = getFlashMessages();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SACSWeb Educacional - Aprendendo Segurança Cibernética</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #667eea;
            --secondary-color: #764ba2;
            --accent-color: #f093fb;
            --danger-color: #ff6b6b;
            --success-color: #51cf66;
            --warning-color: #ffd43b;
        }

        body {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .hero-section {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 60px 40px;
            text-align: center;
            color: white;
            margin-bottom: 40px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
        }

        .hero-title {
            font-size: 3.5rem;
            font-weight: 700;
            margin-bottom: 20px;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
        }

        .hero-subtitle {
            font-size: 1.3rem;
            margin-bottom: 30px;
            opacity: 0.9;
        }

        .auth-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(15px);
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.15);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .form-control {
            border-radius: 12px;
            border: 2px solid #e9ecef;
            padding: 15px 20px;
            font-size: 16px;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
            transform: translateY(-2px);
        }

        .btn-primary {
            background: linear-gradient(45deg, var(--primary-color), var(--secondary-color));
            border: none;
            border-radius: 12px;
            padding: 15px 30px;
            font-size: 16px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.4);
        }

        .btn-outline-primary {
            border: 2px solid var(--primary-color);
            color: var(--primary-color);
            border-radius: 12px;
            padding: 15px 30px;
            font-size: 16px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-outline-primary:hover {
            background: var(--primary-color);
            color: white;
            transform: translateY(-3px);
        }

        .nav-tabs {
            border: none;
            margin-bottom: 30px;
        }

        .nav-tabs .nav-link {
            border: none;
            border-radius: 12px;
            margin-right: 10px;
            padding: 15px 30px;
            font-weight: 600;
            color: #6c757d;
            transition: all 0.3s ease;
        }

        .nav-tabs .nav-link.active {
            background: var(--primary-color);
            color: white;
            transform: translateY(-2px);
        }

        .nav-tabs .nav-link:hover {
            transform: translateY(-2px);
        }

        .features-section {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 40px;
            margin-top: 40px;
            color: white;
        }

        .feature-card {
            text-align: center;
            padding: 20px;
            margin-bottom: 20px;
        }

        .feature-icon {
            font-size: 3rem;
            margin-bottom: 20px;
            color: var(--accent-color);
        }

        .cyber-particles {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: -1;
        }

        .particle {
            position: absolute;
            width: 4px;
            height: 4px;
            background: rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            animation: float 6s infinite linear;
        }

        @keyframes float {
            0% { transform: translateY(100vh) rotate(0deg); opacity: 0; }
            10% { opacity: 1; }
            90% { opacity: 1; }
            100% { transform: translateY(-100px) rotate(360deg); opacity: 0; }
        }

        .alert {
            border-radius: 12px;
            border: none;
            padding: 15px 20px;
        }

        .form-text {
            font-size: 14px;
            color: #6c757d;
        }

        .password-strength {
            height: 5px;
            border-radius: 3px;
            margin-top: 10px;
            transition: all 0.3s ease;
        }

        .strength-weak { background: var(--danger-color); width: 25%; }
        .strength-medium { background: var(--warning-color); width: 50%; }
        .strength-strong { background: var(--success-color); width: 75%; }
        .strength-very-strong { background: var(--success-color); width: 100%; }
    </style>
</head>
<body>
    <!-- Partículas de fundo -->
    <div class="cyber-particles" id="particles"></div>

    <div class="container py-5">
        <!-- Seção Hero -->
        <div class="hero-section">
            <h1 class="hero-title">
                <i class="fas fa-shield-alt"></i> SACSWeb Educacional
            </h1>
            <p class="hero-subtitle">
                Aprenda sobre ataques cibernéticos e como se proteger deles através de 
                <strong>explicações teóricas</strong> e <strong>demonstrações práticas</strong>
            </p>
            <div class="row justify-content-center">
                <div class="col-md-8">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="feature-card">
                                <div class="feature-icon">
                                    <i class="fas fa-book-open"></i>
                                </div>
                                <h5>Teoria</h5>
                                <small>Entenda como e por que os ataques acontecem</small>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="feature-card">
                                <div class="feature-icon">
                                    <i class="fas fa-code"></i>
                                </div>
                                <h5>Prática</h5>
                                <small>Veja vulnerabilidades em código real</small>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="feature-card">
                                <div class="feature-icon">
                                    <i class="fas fa-shield-alt"></i>
                                </div>
                                <h5>Proteção</h5>
                                <small>Aprenda a se defender</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Container de Autenticação -->
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="auth-container">
                    <!-- Mensagens de erro/sucesso -->
                    <?php if (isset($messages['error'])): ?>
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle"></i> <?= htmlspecialchars($messages['error']) ?>
                        </div>
                    <?php endif; ?>

                    <?php if (isset($messages['success'])): ?>
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle"></i> <?= htmlspecialchars($messages['success']) ?>
                        </div>
                    <?php endif; ?>

                    <!-- Abas de Login/Registro -->
                    <ul class="nav nav-tabs" id="authTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="login-tab" data-bs-toggle="tab" data-bs-target="#login" type="button" role="tab">
                                <i class="fas fa-sign-in-alt"></i> Entrar
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="register-tab" data-bs-toggle="tab" data-bs-target="#register" type="button" role="tab">
                                <i class="fas fa-user-plus"></i> Registrar
                            </button>
                        </li>
                    </ul>

                    <!-- Conteúdo das Abas -->
                    <div class="tab-content" id="authTabsContent">
                        <!-- Aba de Login -->
                        <div class="tab-pane fade show active" id="login" role="tabpanel">
                            <form method="POST" action="auth/login.php" id="loginForm">
                                <div class="mb-3">
                                    <label for="loginEmail" class="form-label">
                                        <i class="fas fa-envelope"></i> Email ou Nome de Usuário
                                    </label>
                                    <input type="text" class="form-control" id="loginEmail" name="email" required>
                                    <div class="form-text">
                                        Use "admin" para acessar como administrador
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="loginPassword" class="form-label">
                                        <i class="fas fa-lock"></i> Senha
                                    </label>
                                    <input type="password" class="form-control" id="loginPassword" name="senha" required>
                                    <div class="form-text">
                                        Senha padrão do admin: "admin123"
                                    </div>
                                </div>

                                <div class="mb-3 form-check">
                                    <input type="checkbox" class="form-check-input" id="rememberMe" name="lembrar">
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
                        <div class="tab-pane fade" id="register" role="tabpanel">
                            <form method="POST" action="auth/register.php" id="registerForm">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="registerNome" class="form-label">
                                                <i class="fas fa-user"></i> Nome Completo
                                            </label>
                                            <input type="text" class="form-control" id="registerNome" name="nome" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="registerEmail" class="form-label">
                                                <i class="fas fa-envelope"></i> Email
                                            </label>
                                            <input type="email" class="form-control" id="registerEmail" name="email" required>
                                        </div>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="registerUsername" class="form-label">
                                        <i class="fas fa-at"></i> Nome de Usuário
                                    </label>
                                    <div class="input-group">
                                        <input type="text" class="form-control" id="registerUsername" name="username" required>
                                        <button class="btn btn-outline-primary" type="button" id="checkUsernameBtn">
                                            Verificar
                                        </button>
                                    </div>
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
                                            <input type="password" class="form-control" id="registerPassword" name="senha" required>
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
                                            <input type="password" class="form-control" id="registerConfirmPassword" name="confirmar_senha" required>
                                        </div>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="registerNivel" class="form-label">
                                        <i class="fas fa-graduation-cap"></i> Nível de Conhecimento
                                    </label>
                                    <select class="form-control" id="registerNivel" name="nivel_conhecimento" required>
                                        <option value="">Selecione seu nível</option>
                                        <option value="iniciante">Iniciante - Primeiro contato com segurança</option>
                                        <option value="intermediario">Intermediário - Algum conhecimento básico</option>
                                        <option value="avancado">Avançado - Experiência em TI/segurança</option>
                                    </select>
                                </div>

                                <div class="mb-3 form-check">
                                    <input type="checkbox" class="form-check-input" id="aceitarTermos" name="aceitar_termos" required>
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

                    <!-- Informações adicionais -->
                    <div class="text-center">
                        <h6 class="text-muted mb-3">
                            <i class="fas fa-info-circle"></i> Sobre o SACSWeb Educacional
                        </h6>
                        <p class="text-muted small">
                            Este sistema foi desenvolvido como TCC para ensinar sobre segurança cibernética de forma 
                            <strong>teórica</strong> e <strong>prática</strong>. Aprenda sobre ataques como SQL Injection, 
                            XSS, Phishing e muito mais através de módulos interativos e demonstrações de código.
                        </p>
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

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    <script>
        // Criar partículas de fundo
        function createParticles() {
            const particlesContainer = document.getElementById('particles');
            const particleCount = 50;

            for (let i = 0; i < particleCount; i++) {
                const particle = document.createElement('div');
                particle.className = 'particle';
                particle.style.left = Math.random() * 100 + '%';
                particle.style.animationDelay = Math.random() * 6 + 's';
                particle.style.animationDuration = (Math.random() * 3 + 3) + 's';
                particlesContainer.appendChild(particle);
            }
        }

        // Validação de força da senha
        function checkPasswordStrength(password) {
            const strengthBar = document.getElementById('passwordStrength');
            let strength = 0;
            
            if (password.length >= 8) strength++;
            if (/[a-z]/.test(password)) strength++;
            if (/[A-Z]/.test(password)) strength++;
            if (/[0-9]/.test(password)) strength++;
            if (/[^A-Za-z0-9]/.test(password)) strength++;

            strengthBar.className = 'password-strength';
            if (strength <= 2) strengthBar.classList.add('strength-weak');
            else if (strength <= 3) strengthBar.classList.add('strength-medium');
            else if (strength <= 4) strengthBar.classList.add('strength-strong');
            else strengthBar.classList.add('strength-very-strong');
        }

        // Validação de formulários
        document.addEventListener('DOMContentLoaded', function() {
            createParticles();

            // Validação da senha
            const passwordInput = document.getElementById('registerPassword');
            if (passwordInput) {
                passwordInput.addEventListener('input', function() {
                    checkPasswordStrength(this.value);
                });
            }

            // Validação do formulário de registro
            const registerForm = document.getElementById('registerForm');
            const registerUsername = document.getElementById('registerUsername');
            const checkUsernameBtn = document.getElementById('checkUsernameBtn');
            const usernameHelp = document.getElementById('usernameHelp');
            let lastCheckedUsername = '';
            let usernameAvailable = false;

            async function checkUsernameAvailability(username) {
                if (!username || username.length < 3) {
                    usernameHelp.className = 'form-text text-danger';
                    usernameHelp.textContent = 'Informe um nome de usuário válido (mínimo 3 caracteres)';
                    usernameAvailable = false;
                    return;
                }
                usernameHelp.className = 'form-text';
                usernameHelp.textContent = 'Verificando...';
                try {
                    const response = await fetch('auth/check_username.php?username=' + encodeURIComponent(username), {
                        headers: { 'Accept': 'application/json' }
                    });
                    const data = await response.json();
                    if (data.available) {
                        usernameHelp.className = 'form-text text-success';
                        usernameHelp.textContent = 'Nome de usuário disponível';
                        usernameAvailable = true;
                    } else {
                        usernameHelp.className = 'form-text text-danger';
                        usernameHelp.textContent = data.message || 'Este nome de usuário já está em uso';
                        usernameAvailable = false;
                    }
                } catch (e) {
                    usernameHelp.className = 'form-text text-danger';
                    usernameHelp.textContent = 'Erro ao verificar disponibilidade. Tente novamente.';
                    usernameAvailable = false;
                }
                lastCheckedUsername = username;
            }

            if (checkUsernameBtn && registerUsername) {
                checkUsernameBtn.addEventListener('click', function() {
                    checkUsernameAvailability(registerUsername.value.trim());
                });
                registerUsername.addEventListener('blur', function() {
                    const current = registerUsername.value.trim();
                    if (current && current !== lastCheckedUsername) {
                        checkUsernameAvailability(current);
                    }
                });
                registerUsername.addEventListener('input', function() {
                    usernameAvailable = false; // invalidar verificação ao digitar
                    usernameHelp.className = 'form-text';
                    usernameHelp.textContent = 'Mínimo 3 caracteres, apenas letras, números e underscore';
                });
            }
            if (registerForm) {
                registerForm.addEventListener('submit', function(e) {
                    const password = document.getElementById('registerPassword').value;
                    const confirmPassword = document.getElementById('registerConfirmPassword').value;
                    const username = registerUsername ? registerUsername.value.trim() : '';
                    
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

                    if (username.length < 3 || !/^[_a-zA-Z0-9]+$/.test(username)) {
                        e.preventDefault();
                        alert('Informe um nome de usuário válido (mínimo 3 caracteres, apenas letras, números e underscore)');
                        return false;
                    }

                    if (!usernameAvailable) {
                        e.preventDefault();
                        alert('Por favor, verifique a disponibilidade do nome de usuário.');
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