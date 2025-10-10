<?php
/**
 * SACSWeb Educacional - Sistema de Registro
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
    $nome = trim($_POST['nome'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $senha = $_POST['senha'] ?? '';
    $confirmarSenha = $_POST['confirmar_senha'] ?? '';
    $nivelConhecimento = $_POST['nivel_conhecimento'] ?? '';
    $aceitarTermos = isset($_POST['aceitar_termos']);
    
    // Validações
    if (empty($nome) || empty($email) || empty($username) || empty($senha) || empty($confirmarSenha) || empty($nivelConhecimento)) {
        $error = 'Todos os campos são obrigatórios';
    } elseif (!$aceitarTermos) {
        $error = 'Você deve aceitar os termos de uso';
    } elseif (strlen($nome) < 3) {
        $error = 'Nome deve ter pelo menos 3 caracteres';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Email inválido';
    } elseif (strlen($username) < 3 || !preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        $error = 'Nome de usuário deve ter pelo menos 3 caracteres e conter apenas letras, números e underscore';
    } elseif (strlen($senha) < 8) {
        $error = 'Senha deve ter pelo menos 8 caracteres';
    } elseif ($senha !== $confirmarSenha) {
        $error = 'As senhas não coincidem';
    } elseif (!in_array($nivelConhecimento, ['iniciante', 'intermediario', 'avancado'])) {
        $error = 'Nível de conhecimento inválido';
    } else {
        try {
            $pdo = connectDatabase();
            
            // Verificar se email já existe
            $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM usuarios WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()['count'] > 0) {
                $error = 'Este email já está em uso';
            } else {
                // Verificar se username já existe (corrigido)
                $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM usuarios WHERE username = ?");
                $stmt->execute([$username]);
                if ($stmt->fetch()['count'] > 0) {
                    $error = 'Este nome de usuário já está em uso';
                } else {
                    // Criar hash da senha
                    $senhaHash = password_hash($senha, PASSWORD_BCRYPT, ['cost' => 12]);
                    
                    // Inserir novo usuário (mantendo data_cadastro)
                    $stmt = $pdo->prepare("
                        INSERT INTO usuarios (nome, email, username, senha_hash, tipo_usuario, nivel_conhecimento, ativo, data_cadastro)
                        VALUES (?, ?, ?, ?, 'aluno', ?, 1, NOW())
                    ");
                    $result = $stmt->execute([$nome, $email, $username, $senhaHash, $nivelConhecimento]);
                    
                    if ($result) {
                        $userId = $pdo->lastInsertId();
                        
                        // Registrar atividade
                        $stmt = $pdo->prepare("INSERT INTO logs_atividade (usuario_id, acao, descricao, ip_address) VALUES (?, 'REGISTRO', 'Nova conta criada', ?)");
                        $stmt->execute([$userId, $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1']);
                        
                        logMessage("Novo usuário registrado: " . $email, 'info');
                        
                        $success = 'Conta criada com sucesso! Você pode fazer login agora.';
                    } else {
                        $error = 'Erro ao criar conta. Tente novamente.';
                    }
                }
            }
        } catch (PDOException $e) {
            $error = '';
            logMessage('Erro no registro: ' . $e->getMessage(), 'error');
        }
    }
}

// Redirecionar para index com mensagem
if ($error) {
    $_SESSION['error_message'] = $error;
} elseif ($success) {
    $_SESSION['success_message'] = $success;
}

redirect('/sacsweb/');
?>
