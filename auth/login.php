<?php
/**
 * SACSWeb Educacional - Sistema de Login
 * Sistema para Ensino de Ataques Cibernéticos e Proteções
 */

require_once '../config/config.php';

// Se já estiver logado, redirecionar
if (isLoggedIn()) {
    redirect('/sacsweb/website/dashboard.php');
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';
    $lembrar = isset($_POST['lembrar']);
    
    if (empty($email) || empty($senha)) {
        $error = 'Email/usuário e senha são obrigatórios';
    } else {
        try {
            $pdo = connectDatabase();
            
            // Buscar usuário por email ou username
            $stmt = $pdo->prepare("SELECT id, nome, email, senha_hash, tipo_usuario, nivel_conhecimento, ativo FROM usuarios WHERE (email = ? OR email = ?) AND ativo = 1");
            $stmt->execute([$email, $email]);
            $user = $stmt->fetch();
            
            if ($user && password_verify($senha, $user['senha_hash'])) {
                // Login bem-sucedido
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['nome'];
                $_SESSION['user_type'] = $user['tipo_usuario'];
                $_SESSION['user_level'] = $user['nivel_conhecimento'];
                $_SESSION['csrf_token'] = generateCSRFToken();
                
                // Atualizar último acesso
                $stmt = $pdo->prepare("UPDATE usuarios SET ultimo_acesso = NOW() WHERE id = ?");
                $stmt->execute([$user['id']]);
                
                // Registrar atividade
                $stmt = $pdo->prepare("INSERT INTO logs_atividade (usuario_id, acao, descricao, ip_address) VALUES (?, 'LOGIN', 'Login realizado com sucesso', ?)");
                $stmt->execute([$user['id'], $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1']);
                
                // Configurar cookie de "lembrar" se solicitado
                if ($lembrar) {
                    $token = bin2hex(random_bytes(32));
                    setcookie('remember_token', $token, time() + (30 * 24 * 60 * 60), '/', '', false, true);
                    
                    // Salvar token no banco (implementar se necessário)
                }
                
                logMessage("Login realizado com sucesso para: " . $user['email'], 'info');
                
                // Redirecionar para dashboard
                redirect('/sacsweb/website/dashboard.php');
            } else {
                $error = 'Credenciais inválidas ou usuário inativo';
                logMessage("Tentativa de login falhada para: " . $email, 'warning');
            }
        } catch (PDOException $e) {
            $error = 'Erro ao conectar com o banco de dados';
            logMessage('Erro no login: ' . $e->getMessage(), 'error');
        }
    }
}

// Se chegou até aqui, houve erro - redirecionar para index com mensagem
if ($error) {
    $_SESSION['error_message'] = $error;
} elseif ($success) {
    $_SESSION['success_message'] = $success;
}

redirect('/sacsweb/');
?>
