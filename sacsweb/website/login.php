<?php
/**
 * SACSWeb Educacional - Página de Login
 * Sistema para Ensino de Ataques Cibernéticos e Proteções
 */

require_once '../config/config.php';

// Processar login se formulário foi enviado
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';
    
    if (empty($email) || empty($senha)) {
        showError('Email e senha são obrigatórios');
    } else {
        $user = authenticateUser($email, $senha);
        
        if ($user) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['nome'];
            $_SESSION['user_type'] = $user['tipo_usuario'];
            $_SESSION['csrf_token'] = generateCSRFToken();
            
            showSuccess('Login realizado com sucesso!');
            redirect('/sacsweb/website/dashboard.php');
        } else {
            showError('Email ou senha incorretos');
        }
    }
}

// Se já estiver logado, redirecionar
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
    <title>Login - SACSWeb Educacional</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
        }
        .login-container {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 15px;
            backdrop-filter: blur(10px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
        }
        .btn-primary {
            background: #667eea;
            border-color: #667eea;
        }
        .btn-primary:hover {
            background: #5a6fd8;
            border-color: #5a6fd8;
        }
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        .cyber-bg {
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><circle cx="50" cy="50" r="2" fill="%23ffffff20"/><circle cx="20" cy="20" r="1" fill="%23ffffff10"/><circle cx="80" cy="30" r="1" fill="%23ffffff10"/><circle cx="30" cy="80" r="1" fill="%23ffffff10"/></svg>');
        }
    </style>
</head>
<body class="cyber-bg">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-5 col-md-7">
                <div class="login-container p-5">
                    <div class="text-center mb-4">
                        <h1 class="display-5 text-primary">
                            <i class="fas fa-shield-alt"></i> SACSWeb
                        </h1>
                        <p class="lead text-muted">Sistema Educacional de Segurança Cibernética</p>
                    </div>

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

                    <form method="POST" action="">
                        <div class="mb-3">
                            <label for="email" class="form-label">
                                <i class="fas fa-user"></i> Email/Login
                            </label>
                            <input type="text" class="form-control" id="email" name="email" 
                                   value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
                            <div class="form-text">
                                <small>Use "admin" para acessar como administrador</small>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="senha" class="form-label">
                                <i class="fas fa-lock"></i> Senha
                            </label>
                            <input type="password" class="form-control" id="senha" name="senha" required>
                            <div class="form-text">
                                <small>Senha padrão do admin: "admin123"</small>
                            </div>
                        </div>

                        <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">

                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fas fa-sign-in-alt"></i> Entrar
                            </button>
                        </div>
                    </form>

                    <hr>

                    <div class="text-center">
                        <small class="text-muted">
                            <i class="fas fa-info-circle"></i> 
                            Sistema educacional para aprendizado sobre segurança cibernética
                        </small>
                    </div>

                    <div class="mt-3 p-3 bg-light rounded">
                        <h6 class="text-primary">
                            <i class="fas fa-key"></i> Credenciais de Teste:
                        </h6>
                        <ul class="small mb-0">
                            <li><strong>Admin:</strong> admin / admin123</li>
                            <li>Após login, você pode criar outros usuários</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
</body>
</html>
