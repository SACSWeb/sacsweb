<?php
/**
 * API de Autenticação - SACSWeb
 * Sistema de Segurança Cibernética
 */

// Incluir configurações
require_once '../config/database.php';
require_once '../includes/functions.php';

// Configurar headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Verificar método da requisição
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Processar requisição
if ($method === 'POST') {
    $acao = $_POST['acao'] ?? '';
    
    switch ($acao) {
        case 'login':
            processarLogin($pdo);
            break;
            
        case 'registro':
            processarRegistro($pdo);
            break;
            
        case 'logout':
            processarLogout($pdo);
            break;
            
        case 'verificar_sessao':
            verificarSessaoAPI($pdo);
            break;
            
        default:
            respostaJSON(false, 'Ação não reconhecida');
            break;
    }
} else {
    respostaJSON(false, 'Método não permitido');
}

/**
 * Processar login
 */
function processarLogin($pdo) {
    $login = limparDados($_POST['login'] ?? '');
    $senha = $_POST['senha'] ?? '';
    $lembrar = isset($_POST['lembrar']);
    
    // Validar dados
    if (empty($login) || empty($senha)) {
        respostaJSON(false, 'Login e senha são obrigatórios');
    }
    
    // Verificar se IP está bloqueado
    if (verificarIPBloqueado($pdo)) {
        respostaJSON(false, 'IP bloqueado devido a múltiplas tentativas de login');
    }
    
    // Autenticar usuário
    $usuario = autenticarUsuario($pdo, $login, $senha);
    
    if ($usuario) {
        // Registrar tentativa bem-sucedida
        registrarTentativaLogin($pdo, $login, true);
        
        // Criar sessão
        $token = criarSessao($pdo, $usuario['id']);
        
        if ($token) {
            // Definir cookie
            $expira = $lembrar ? time() + (30 * 24 * 60 * 60) : time() + (24 * 60 * 60); // 30 dias ou 24 horas
            setcookie('sacsweb_token', $token, $expira, '/', '', false, true);
            
            // Registrar atividade
            registrarAtividade($pdo, $usuario['id'], 'login', ['ip' => obterIP()]);
            
            respostaJSON(true, 'Login realizado com sucesso', [
                'usuario' => $usuario,
                'token' => $token
            ]);
        } else {
            respostaJSON(false, 'Erro ao criar sessão');
        }
    } else {
        // Registrar tentativa falhada
        registrarTentativaLogin($pdo, $login, false);
        
        respostaJSON(false, 'Credenciais inválidas');
    }
}

/**
 * Processar registro
 */
function processarRegistro($pdo) {
    $nome = limparDados($_POST['nome'] ?? '');
    $sobrenome = limparDados($_POST['sobrenome'] ?? '');
    $email = limparDados($_POST['email'] ?? '');
    $usuario = limparDados($_POST['usuario'] ?? '');
    $senha = $_POST['senha'] ?? '';
    $confirmarSenha = $_POST['confirmarSenha'] ?? '';
    
    // Validar dados
    if (empty($nome) || empty($sobrenome) || empty($email) || empty($usuario) || empty($senha)) {
        respostaJSON(false, 'Todos os campos são obrigatórios');
    }
    
    if (strlen($nome) < 2 || strlen($sobrenome) < 2) {
        respostaJSON(false, 'Nome e sobrenome devem ter pelo menos 2 caracteres');
    }
    
    if (!validarEmail($email)) {
        respostaJSON(false, 'E-mail inválido');
    }
    
    if (strlen($usuario) < 3) {
        respostaJSON(false, 'Nome de usuário deve ter pelo menos 3 caracteres');
    }
    
    if (strlen($senha) < 8) {
        respostaJSON(false, 'Senha deve ter pelo menos 8 caracteres');
    }
    
    if ($senha !== $confirmarSenha) {
        respostaJSON(false, 'Senhas não coincidem');
    }
    
    // Verificar força da senha
    $forcaSenha = validarForcaSenha($senha);
    if ($forcaSenha === 'fraca') {
        respostaJSON(false, 'Senha muito fraca. Use letras maiúsculas, minúsculas, números e símbolos');
    }
    
    // Verificar se email já existe
    if (emailExiste($pdo, $email)) {
        respostaJSON(false, 'Este e-mail já está em uso');
    }
    
    // Verificar se login já existe
    if (loginExiste($pdo, $usuario)) {
        respostaJSON(false, 'Este nome de usuário já está em uso');
    }
    
    // Criar usuário
    $dados = [
        'login' => $usuario,
        'senha' => $senha,
        'nome' => $nome . ' ' . $sobrenome,
        'email' => $email,
        'nivel' => 'user'
    ];
    
    if (criarUsuario($pdo, $dados)) {
        respostaJSON(true, 'Conta criada com sucesso! Você pode fazer login agora.');
    } else {
        respostaJSON(false, 'Erro ao criar conta. Tente novamente.');
    }
}

/**
 * Processar logout
 */
function processarLogout($pdo) {
    fazerLogout($pdo);
    respostaJSON(true, 'Logout realizado com sucesso');
}

/**
 * Verificar sessão via API
 */
function verificarSessaoAPI($pdo) {
    $usuario = obterUsuarioLogado($pdo);
    
    if ($usuario) {
        respostaJSON(true, 'Sessão válida', ['usuario' => $usuario]);
    } else {
        respostaJSON(false, 'Sessão inválida ou expirada');
    }
}
?> 