<?php
/**
 * Configuração do Banco de Dados SACSWeb
 * Sistema de Segurança Cibernética
 */

// Configurações do banco de dados
define('DB_HOST', 'localhost');
define('DB_NAME', 'sacsweb');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// DSN (Data Source Name)
$dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;

// Opções do PDO
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    // Criar conexão PDO
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    
    // Definir timezone
    $pdo->exec("SET time_zone = '-03:00'");
    
} catch (PDOException $e) {
    // Em produção, você deve logar o erro em vez de exibi-lo
    die('Erro de conexão com o banco de dados: ' . $e->getMessage());
}

/**
 * Função para criar as tabelas do banco de dados
 */
function criarTabelas($pdo) {
    try {
        // Tabela de usuários
        $sql_usuarios = "CREATE TABLE IF NOT EXISTS usuarios (
            id INT AUTO_INCREMENT PRIMARY KEY,
            login VARCHAR(50) UNIQUE NOT NULL,
            senha VARCHAR(255) NOT NULL,
            nome VARCHAR(100) NOT NULL,
            email VARCHAR(100) UNIQUE NOT NULL,
            nivel ENUM('admin', 'user') DEFAULT 'user',
            avatar VARCHAR(10),
            data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            ultimo_acesso TIMESTAMP NULL,
            ativo BOOLEAN DEFAULT TRUE,
            INDEX idx_login (login),
            INDEX idx_email (email),
            INDEX idx_nivel (nivel)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $pdo->exec($sql_usuarios);
        
        // Tabela de sessões
        $sql_sessoes = "CREATE TABLE IF NOT EXISTS sessoes (
            id INT AUTO_INCREMENT PRIMARY KEY,
            usuario_id INT NOT NULL,
            token VARCHAR(255) UNIQUE NOT NULL,
            ip_address VARCHAR(45),
            user_agent TEXT,
            data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            data_expiracao TIMESTAMP NOT NULL,
            ativo BOOLEAN DEFAULT TRUE,
            FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
            INDEX idx_token (token),
            INDEX idx_usuario (usuario_id),
            INDEX idx_expiracao (data_expiracao)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $pdo->exec($sql_sessoes);
        
        // Tabela de tentativas de login
        $sql_tentativas = "CREATE TABLE IF NOT EXISTS tentativas_login (
            id INT AUTO_INCREMENT PRIMARY KEY,
            ip_address VARCHAR(45) NOT NULL,
            login VARCHAR(50) NOT NULL,
            sucesso BOOLEAN DEFAULT FALSE,
            data_tentativa TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_ip (ip_address),
            INDEX idx_login (login),
            INDEX idx_data (data_tentativa)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $pdo->exec($sql_tentativas);
        
        // Tabela de simulações
        $sql_simulacoes = "CREATE TABLE IF NOT EXISTS simulacoes (
            id INT AUTO_INCREMENT PRIMARY KEY,
            usuario_id INT NOT NULL,
            tipo VARCHAR(50) NOT NULL,
            nome VARCHAR(100) NOT NULL,
            descricao TEXT,
            status ENUM('pendente', 'em_andamento', 'concluida', 'erro') DEFAULT 'pendente',
            resultado JSON,
            data_inicio TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            data_fim TIMESTAMP NULL,
            FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
            INDEX idx_usuario (usuario_id),
            INDEX idx_status (status),
            INDEX idx_data_inicio (data_inicio)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $pdo->exec($sql_simulacoes);
        
        // Tabela de vulnerabilidades
        $sql_vulnerabilidades = "CREATE TABLE IF NOT EXISTS vulnerabilidades (
            id INT AUTO_INCREMENT PRIMARY KEY,
            simulacao_id INT NOT NULL,
            tipo VARCHAR(50) NOT NULL,
            severidade ENUM('baixa', 'media', 'alta', 'critica') NOT NULL,
            titulo VARCHAR(200) NOT NULL,
            descricao TEXT,
            solucao TEXT,
            data_descoberta TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (simulacao_id) REFERENCES simulacoes(id) ON DELETE CASCADE,
            INDEX idx_simulacao (simulacao_id),
            INDEX idx_severidade (severidade),
            INDEX idx_tipo (tipo)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $pdo->exec($sql_vulnerabilidades);
        
        return true;
        
    } catch (PDOException $e) {
        error_log('Erro ao criar tabelas: ' . $e->getMessage());
        return false;
    }
}

/**
 * Função para inserir usuários padrão
 */
function inserirUsuariosPadrao($pdo) {
    try {
        // Verificar se já existem usuários
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM usuarios");
        $total = $stmt->fetch()['total'];
        
        if ($total == 0) {
            // Hash das senhas (em produção, use password_hash)
            $senha_admin = password_hash('admin123', PASSWORD_DEFAULT);
            $senha_user = password_hash('user123', PASSWORD_DEFAULT);
            $senha_teste = password_hash('teste123', PASSWORD_DEFAULT);
            
            $usuarios = [
                [
                    'login' => 'admin',
                    'senha' => $senha_admin,
                    'nome' => 'Administrador',
                    'email' => 'admin@sacsweb.com',
                    'nivel' => 'admin',
                    'avatar' => 'AD'
                ],
                [
                    'login' => 'user',
                    'senha' => $senha_user,
                    'nome' => 'Usuário Padrão',
                    'email' => 'user@sacsweb.com',
                    'nivel' => 'user',
                    'avatar' => 'UP'
                ],
                [
                    'login' => 'teste',
                    'senha' => $senha_teste,
                    'nome' => 'Usuário Teste',
                    'email' => 'teste@sacsweb.com',
                    'nivel' => 'user',
                    'avatar' => 'UT'
                ]
            ];
            
            $stmt = $pdo->prepare("
                INSERT INTO usuarios (login, senha, nome, email, nivel, avatar) 
                VALUES (:login, :senha, :nome, :email, :nivel, :avatar)
            ");
            
            foreach ($usuarios as $usuario) {
                $stmt->execute($usuario);
            }
            
            return true;
        }
        
        return true;
        
    } catch (PDOException $e) {
        error_log('Erro ao inserir usuários padrão: ' . $e->getMessage());
        return false;
    }
}

/**
 * Função para obter estatísticas do sistema
 */
function obterEstatisticas($pdo) {
    try {
        $stats = [];
        
        // Total de usuários
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM usuarios WHERE ativo = 1");
        $stats['totalUsuarios'] = $stmt->fetch()['total'];
        
        // Usuários ativos (com último acesso nos últimos 30 dias)
        $stmt = $pdo->query("
            SELECT COUNT(*) as total 
            FROM usuarios 
            WHERE ativo = 1 
            AND ultimo_acesso >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        ");
        $stats['usuariosAtivos'] = $stmt->fetch()['total'];
        
        // Administradores
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM usuarios WHERE nivel = 'admin' AND ativo = 1");
        $stats['administradores'] = $stmt->fetch()['total'];
        
        // Usuários comuns
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM usuarios WHERE nivel = 'user' AND ativo = 1");
        $stats['usuariosComuns'] = $stmt->fetch()['total'];
        
        // Total de simulações
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM simulacoes");
        $stats['totalSimulacoes'] = $stmt->fetch()['total'];
        
        // Simulações concluídas
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM simulacoes WHERE status = 'concluida'");
        $stats['simulacoesConcluidas'] = $stmt->fetch()['total'];
        
        // Taxa de sucesso
        if ($stats['totalSimulacoes'] > 0) {
            $stats['taxaSucesso'] = round(($stats['simulacoesConcluidas'] / $stats['totalSimulacoes']) * 100);
        } else {
            $stats['taxaSucesso'] = 0;
        }
        
        // Total de vulnerabilidades
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM vulnerabilidades");
        $stats['totalVulnerabilidades'] = $stmt->fetch()['total'];
        
        // Vulnerabilidades críticas
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM vulnerabilidades WHERE severidade = 'critica'");
        $stats['vulnerabilidadesCriticas'] = $stmt->fetch()['total'];
        
        return $stats;
        
    } catch (PDOException $e) {
        error_log('Erro ao obter estatísticas: ' . $e->getMessage());
        return false;
    }
}

/**
 * Função para obter IP do usuário
 */
function obterIP() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        return $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        return $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        return $_SERVER['REMOTE_ADDR'];
    }
}

/**
 * Função para registrar tentativa de login
 */
function registrarTentativaLogin($pdo, $login, $sucesso) {
    try {
        $stmt = $pdo->prepare("
            INSERT INTO tentativas_login (ip_address, login, sucesso) 
            VALUES (:ip, :login, :sucesso)
        ");
        
        return $stmt->execute([
            'ip' => obterIP(),
            'login' => $login,
            'sucesso' => $sucesso
        ]);
        
    } catch (PDOException $e) {
        error_log('Erro ao registrar tentativa de login: ' . $e->getMessage());
        return false;
    }
}

/**
 * Função para verificar se IP está bloqueado
 */
function verificarIPBloqueado($pdo, $minutos = 5) {
    try {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as total 
            FROM tentativas_login 
            WHERE ip_address = :ip 
            AND sucesso = 0 
            AND data_tentativa >= DATE_SUB(NOW(), INTERVAL :minutos MINUTE)
        ");
        
        $stmt->execute([
            'ip' => obterIP(),
            'minutos' => $minutos
        ]);
        
        $total = $stmt->fetch()['total'];
        return $total >= 3; // Bloquear após 3 tentativas
        
    } catch (PDOException $e) {
        error_log('Erro ao verificar IP bloqueado: ' . $e->getMessage());
        return false;
    }
}

/**
 * Função para criar sessão
 */
function criarSessao($pdo, $usuario_id) {
    try {
        // Gerar token único
        $token = bin2hex(random_bytes(32));
        
        // Definir expiração (24 horas)
        $expiracao = date('Y-m-d H:i:s', strtotime('+24 hours'));
        
        $stmt = $pdo->prepare("
            INSERT INTO sessoes (usuario_id, token, ip_address, user_agent, data_expiracao) 
            VALUES (:usuario_id, :token, :ip, :user_agent, :expiracao)
        ");
        
        $stmt->execute([
            'usuario_id' => $usuario_id,
            'token' => $token,
            'ip' => obterIP(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'expiracao' => $expiracao
        ]);
        
        return $token;
        
    } catch (PDOException $e) {
        error_log('Erro ao criar sessão: ' . $e->getMessage());
        return false;
    }
}

/**
 * Função para verificar sessão
 */
function verificarSessao($pdo, $token) {
    try {
        $stmt = $pdo->prepare("
            SELECT s.*, u.nome, u.email, u.nivel, u.avatar 
            FROM sessoes s 
            JOIN usuarios u ON s.usuario_id = u.id 
            WHERE s.token = :token 
            AND s.ativo = 1 
            AND s.data_expiracao > NOW()
        ");
        
        $stmt->execute(['token' => $token]);
        return $stmt->fetch();
        
    } catch (PDOException $e) {
        error_log('Erro ao verificar sessão: ' . $e->getMessage());
        return false;
    }
}

/**
 * Função para invalidar sessão
 */
function invalidarSessao($pdo, $token) {
    try {
        $stmt = $pdo->prepare("UPDATE sessoes SET ativo = 0 WHERE token = :token");
        return $stmt->execute(['token' => $token]);
        
    } catch (PDOException $e) {
        error_log('Erro ao invalidar sessão: ' . $e->getMessage());
        return false;
    }
}

/**
 * Função para atualizar último acesso
 */
function atualizarUltimoAcesso($pdo, $usuario_id) {
    try {
        $stmt = $pdo->prepare("UPDATE usuarios SET ultimo_acesso = NOW() WHERE id = :id");
        return $stmt->execute(['id' => $usuario_id]);
        
    } catch (PDOException $e) {
        error_log('Erro ao atualizar último acesso: ' . $e->getMessage());
        return false;
    }
}

// Inicializar banco de dados se necessário
if (!function_exists('inicializarBanco')) {
    function inicializarBanco($pdo) {
        if (criarTabelas($pdo) && inserirUsuariosPadrao($pdo)) {
            return true;
        }
        return false;
    }
}

// Inicializar automaticamente
inicializarBanco($pdo);
?> 