<?php
/**
 * SACSWeb Educacional - Configuração do Banco de Dados
 * Sistema para Ensino de Ataques Cibernéticos e Proteções
 * Versão: 2.0.0
 */

// Configurações do Banco de Dados
define('DB_HOST', 'localhost');
define('DB_NAME', 'sacsweb_educacional');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// Configurações de Segurança
define('JWT_SECRET', 'sacsweb_jwt_secret_key_2024');
define('SESSION_SECRET', 'sacsweb_session_secret_2024');

// Configurações do Sistema
define('SISTEMA_NOME', 'SACSWeb Educacional');
define('SISTEMA_VERSAO', '2.0.0');
define('TIMEZONE', 'America/Sao_Paulo');

// Configurações de Sessão
define('SESSAO_EXPIRACAO', 3600); // 1 hora

// Configurações de Log
define('LOG_ENABLED', true);
define('LOG_FILE', dirname(__DIR__) . '/logs/sacsweb.log');

// Configurar timezone
date_default_timezone_set(TIMEZONE);

// Função para conectar ao banco de dados
function connectDatabase() {
    try {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ];
        
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        return $pdo;
    } catch (PDOException $e) {
        logMessage('Erro ao conectar ao banco de dados: ' . $e->getMessage(), 'error');
        die('Erro de conexão com o banco de dados. Verifique as configurações.');
    }
}

// Função para registrar log
function logMessage($message, $level = 'info') {
    if (!LOG_ENABLED) {
        return;
    }
    
    $logDir = dirname(LOG_FILE);
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
    
    $timestamp = date('Y-m-d H:i:s');
    $logEntry = "[$timestamp] [$level] $message" . PHP_EOL;
    file_put_contents(LOG_FILE, $logEntry, FILE_APPEND | LOCK_EX);
}

// Função para autenticar usuário
function authenticateUser($email, $senha) {
    try {
        $pdo = connectDatabase();
        $stmt = $pdo->prepare("SELECT id, nome, email, senha_hash, tipo_usuario FROM usuarios WHERE email = ? AND ativo = 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($senha, $user['senha_hash'])) {
            logMessage("Login realizado com sucesso para: " . $email, 'info');
            return $user;
        }
        
        logMessage("Tentativa de login falhada para: " . $email, 'warning');
        return false;
    } catch (PDOException $e) {
        logMessage('Erro na autenticação: ' . $e->getMessage(), 'error');
        return false;
    }
}

// Função para verificar se usuário está logado
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

// Função para obter dados do usuário logado
function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    
    try {
        $pdo = connectDatabase();
        $stmt = $pdo->prepare("SELECT id, nome, email, username, tipo_usuario, nivel_conhecimento, foto_perfil FROM usuarios WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        return $stmt->fetch();
    } catch (PDOException $e) {
        logMessage('Erro ao obter dados do usuário: ' . $e->getMessage(), 'error');
        return null;
    }
}

// Função para redirecionar se não estiver logado
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: /sacsweb/website/auth.php');
        exit;
    }
}

// Função para gerar token CSRF
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Função para validar token CSRF
function validateCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Retorna os valores padrão de preferências de acessibilidade e tema.
 */
function getDefaultPreferences(): array {
    return [
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
}

/**
 * Busca as preferências do usuário, garantindo fallback seguro e cache simples.
 */
function getUserPreferences(?int $userId, bool $forceRefresh = false): array {
    $defaults = getDefaultPreferences();

    if (!$userId) {
        return $defaults;
    }

    static $preferencesCache = [];

    if ($forceRefresh && isset($preferencesCache[$userId])) {
        unset($preferencesCache[$userId]);
    }

    if (isset($preferencesCache[$userId])) {
        return $preferencesCache[$userId];
    }

    try {
        $pdo = connectDatabase();
        $stmt = $pdo->prepare("
            SELECT tema, tamanho_fonte, alto_contraste, reduzir_animacoes, leitor_tela,
                   espacamento, densidade_info, notificacoes_email, notificacoes_push
            FROM usuario_preferencias
            WHERE usuario_id = ?
        ");
        $stmt->execute([$userId]);
        $preferencias = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$preferencias) {
            $insert = $pdo->prepare("
                INSERT INTO usuario_preferencias (
                    usuario_id, tema, tamanho_fonte, alto_contraste, reduzir_animacoes,
                    leitor_tela, espacamento, densidade_info, notificacoes_email, notificacoes_push
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $insert->execute([
                $userId,
                $defaults['tema'],
                $defaults['tamanho_fonte'],
                $defaults['alto_contraste'],
                $defaults['reduzir_animacoes'],
                $defaults['leitor_tela'],
                $defaults['espacamento'],
                $defaults['densidade_info'],
                $defaults['notificacoes_email'],
                $defaults['notificacoes_push']
            ]);
            $preferencias = $defaults;
        } else {
            $preferencias = array_merge($defaults, $preferencias);
        }

        foreach (['alto_contraste', 'reduzir_animacoes', 'leitor_tela', 'notificacoes_email', 'notificacoes_push'] as $flag) {
            $preferencias[$flag] = (int)($preferencias[$flag] ?? 0);
        }

        $preferencesCache[$userId] = $preferencias;
        return $preferencias;
    } catch (PDOException $e) {
        logMessage('Erro ao carregar preferências do usuário: ' . $e->getMessage(), 'error');
        return $defaults;
    }
}

// Inicializar log
logMessage('Sistema SACSWeb Educacional iniciado', 'info');
?>
