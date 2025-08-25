<?php
/**
 * Funções Auxiliares do SACSWeb
 * Sistema de Segurança Cibernética
 */

/**
 * Função para validar email
 */
function validarEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

/**
 * Função para validar força da senha
 */
function validarForcaSenha($senha) {
    $pontos = 0;
    
    if (strlen($senha) >= 8) $pontos++;
    if (preg_match('/[a-z]/', $senha)) $pontos++;
    if (preg_match('/[A-Z]/', $senha)) $pontos++;
    if (preg_match('/[0-9]/', $senha)) $pontos++;
    if (preg_match('/[^A-Za-z0-9]/', $senha)) $pontos++;
    
    if ($pontos < 3) return 'fraca';
    if ($pontos < 4) return 'media';
    return 'forte';
}

/**
 * Função para gerar avatar
 */
function gerarAvatar($nome) {
    $palavras = explode(' ', $nome);
    $avatar = '';
    
    foreach ($palavras as $palavra) {
        $avatar .= strtoupper(substr($palavra, 0, 1));
        if (strlen($avatar) >= 2) break;
    }
    
    return $avatar;
}

/**
 * Função para formatar data
 */
function formatarData($data, $formato = 'd/m/Y H:i') {
    if (is_string($data)) {
        $data = new DateTime($data);
    }
    return $data->format($formato);
}

/**
 * Função para obter saudação baseada na hora
 */
function obterSaudacao() {
    $hora = (int)date('H');
    
    if ($hora < 12) {
        return 'Bom dia';
    } elseif ($hora < 18) {
        return 'Boa tarde';
    } else {
        return 'Boa noite';
    }
}

/**
 * Função para limpar dados de entrada
 */
function limparDados($dados) {
    if (is_array($dados)) {
        return array_map('limparDados', $dados);
    }
    return htmlspecialchars(trim($dados), ENT_QUOTES, 'UTF-8');
}

/**
 * Função para gerar resposta JSON
 */
function respostaJSON($sucesso, $mensagem, $dados = null) {
    header('Content-Type: application/json');
    echo json_encode([
        'sucesso' => $sucesso,
        'mensagem' => $mensagem,
        'dados' => $dados
    ]);
    exit;
}

/**
 * Função para verificar se é uma requisição AJAX
 */
function isAjax() {
    return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
}

/**
 * Função para redirecionar
 */
function redirecionar($url) {
    header("Location: $url");
    exit;
}

/**
 * Função para verificar se usuário está logado
 */
function verificarLogin($pdo) {
    if (!isset($_COOKIE['sacsweb_token'])) {
        return false;
    }
    
    $token = $_COOKIE['sacsweb_token'];
    $sessao = verificarSessao($pdo, $token);
    
    if ($sessao) {
        // Atualizar último acesso
        atualizarUltimoAcesso($pdo, $sessao['usuario_id']);
        return $sessao;
    }
    
    return false;
}

/**
 * Função para fazer logout
 */
function fazerLogout($pdo) {
    if (isset($_COOKIE['sacsweb_token'])) {
        $token = $_COOKIE['sacsweb_token'];
        invalidarSessao($pdo, $token);
        setcookie('sacsweb_token', '', time() - 3600, '/');
    }
    
    session_destroy();
}

/**
 * Função para obter dados do usuário logado
 */
function obterUsuarioLogado($pdo) {
    $sessao = verificarLogin($pdo);
    if ($sessao) {
        return [
            'id' => $sessao['usuario_id'],
            'nome' => $sessao['nome'],
            'email' => $sessao['email'],
            'nivel' => $sessao['nivel'],
            'avatar' => $sessao['avatar']
        ];
    }
    return null;
}

/**
 * Função para verificar permissões
 */
function verificarPermissao($nivel_necessario = 'user') {
    global $usuario_logado;
    
    if (!$usuario_logado) {
        return false;
    }
    
    if ($nivel_necessario === 'admin' && $usuario_logado['nivel'] !== 'admin') {
        return false;
    }
    
    return true;
}

/**
 * Função para registrar atividade
 */
function registrarAtividade($pdo, $usuario_id, $acao, $detalhes = null) {
    try {
        $stmt = $pdo->prepare("
            INSERT INTO atividades (usuario_id, acao, detalhes, ip_address) 
            VALUES (:usuario_id, :acao, :detalhes, :ip)
        ");
        
        return $stmt->execute([
            'usuario_id' => $usuario_id,
            'acao' => $acao,
            'detalhes' => $detalhes ? json_encode($detalhes) : null,
            'ip' => obterIP()
        ]);
        
    } catch (PDOException $e) {
        error_log('Erro ao registrar atividade: ' . $e->getMessage());
        return false;
    }
}

/**
 * Função para obter atividades recentes
 */
function obterAtividadesRecentes($pdo, $limite = 10) {
    try {
        $stmt = $pdo->prepare("
            SELECT a.*, u.nome as usuario_nome 
            FROM atividades a 
            JOIN usuarios u ON a.usuario_id = u.id 
            ORDER BY a.data_criacao DESC 
            LIMIT :limite
        ");
        
        $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll();
        
    } catch (PDOException $e) {
        error_log('Erro ao obter atividades: ' . $e->getMessage());
        return [];
    }
}

/**
 * Função para criar simulação
 */
function criarSimulacao($pdo, $usuario_id, $tipo, $nome, $descricao = null) {
    try {
        $stmt = $pdo->prepare("
            INSERT INTO simulacoes (usuario_id, tipo, nome, descricao, status) 
            VALUES (:usuario_id, :tipo, :nome, :descricao, 'pendente')
        ");
        
        $stmt->execute([
            'usuario_id' => $usuario_id,
            'tipo' => $tipo,
            'nome' => $nome,
            'descricao' => $descricao
        ]);
        
        return $pdo->lastInsertId();
        
    } catch (PDOException $e) {
        error_log('Erro ao criar simulação: ' . $e->getMessage());
        return false;
    }
}

/**
 * Função para obter simulações do usuário
 */
function obterSimulacoesUsuario($pdo, $usuario_id, $limite = 10) {
    try {
        $stmt = $pdo->prepare("
            SELECT * FROM simulacoes 
            WHERE usuario_id = :usuario_id 
            ORDER BY data_inicio DESC 
            LIMIT :limite
        ");
        
        $stmt->bindValue(':usuario_id', $usuario_id, PDO::PARAM_INT);
        $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll();
        
    } catch (PDOException $e) {
        error_log('Erro ao obter simulações: ' . $e->getMessage());
        return [];
    }
}

/**
 * Função para adicionar vulnerabilidade
 */
function adicionarVulnerabilidade($pdo, $simulacao_id, $tipo, $severidade, $titulo, $descricao = null, $solucao = null) {
    try {
        $stmt = $pdo->prepare("
            INSERT INTO vulnerabilidades (simulacao_id, tipo, severidade, titulo, descricao, solucao) 
            VALUES (:simulacao_id, :tipo, :severidade, :titulo, :descricao, :solucao)
        ");
        
        return $stmt->execute([
            'simulacao_id' => $simulacao_id,
            'tipo' => $tipo,
            'severidade' => $severidade,
            'titulo' => $titulo,
            'descricao' => $descricao,
            'solucao' => $solucao
        ]);
        
    } catch (PDOException $e) {
        error_log('Erro ao adicionar vulnerabilidade: ' . $e->getMessage());
        return false;
    }
}

/**
 * Função para obter vulnerabilidades da simulação
 */
function obterVulnerabilidadesSimulacao($pdo, $simulacao_id) {
    try {
        $stmt = $pdo->prepare("
            SELECT * FROM vulnerabilidades 
            WHERE simulacao_id = :simulacao_id 
            ORDER BY severidade DESC, data_descoberta DESC
        ");
        
        $stmt->execute(['simulacao_id' => $simulacao_id]);
        return $stmt->fetchAll();
        
    } catch (PDOException $e) {
        error_log('Erro ao obter vulnerabilidades: ' . $e->getMessage());
        return [];
    }
}

/**
 * Função para atualizar status da simulação
 */
function atualizarStatusSimulacao($pdo, $simulacao_id, $status, $resultado = null) {
    try {
        $sql = "UPDATE simulacoes SET status = :status";
        $params = ['simulacao_id' => $simulacao_id, 'status' => $status];
        
        if ($status === 'concluida') {
            $sql .= ", data_fim = NOW()";
        }
        
        if ($resultado !== null) {
            $sql .= ", resultado = :resultado";
            $params['resultado'] = json_encode($resultado);
        }
        
        $sql .= " WHERE id = :simulacao_id";
        
        $stmt = $pdo->prepare($sql);
        return $stmt->execute($params);
        
    } catch (PDOException $e) {
        error_log('Erro ao atualizar status da simulação: ' . $e->getMessage());
        return false;
    }
}

/**
 * Função para obter estatísticas do usuário
 */
function obterEstatisticasUsuario($pdo, $usuario_id) {
    try {
        $stats = [];
        
        // Total de simulações
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM simulacoes WHERE usuario_id = :usuario_id");
        $stmt->execute(['usuario_id' => $usuario_id]);
        $stats['totalSimulacoes'] = $stmt->fetch()['total'];
        
        // Simulações concluídas
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM simulacoes WHERE usuario_id = :usuario_id AND status = 'concluida'");
        $stmt->execute(['usuario_id' => $usuario_id]);
        $stats['simulacoesConcluidas'] = $stmt->fetch()['total'];
        
        // Taxa de sucesso
        if ($stats['totalSimulacoes'] > 0) {
            $stats['taxaSucesso'] = round(($stats['simulacoesConcluidas'] / $stats['totalSimulacoes']) * 100);
        } else {
            $stats['taxaSucesso'] = 0;
        }
        
        // Total de vulnerabilidades encontradas
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as total 
            FROM vulnerabilidades v 
            JOIN simulacoes s ON v.simulacao_id = s.id 
            WHERE s.usuario_id = :usuario_id
        ");
        $stmt->execute(['usuario_id' => $usuario_id]);
        $stats['totalVulnerabilidades'] = $stmt->fetch()['total'];
        
        // Vulnerabilidades críticas
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as total 
            FROM vulnerabilidades v 
            JOIN simulacoes s ON v.simulacao_id = s.id 
            WHERE s.usuario_id = :usuario_id AND v.severidade = 'critica'
        ");
        $stmt->execute(['usuario_id' => $usuario_id]);
        $stats['vulnerabilidadesCriticas'] = $stmt->fetch()['total'];
        
        return $stats;
        
    } catch (PDOException $e) {
        error_log('Erro ao obter estatísticas do usuário: ' . $e->getMessage());
        return false;
    }
}

/**
 * Função para obter tempo médio das simulações
 */
function obterTempoMedioSimulacoes($pdo, $usuario_id = null) {
    try {
        $sql = "
            SELECT AVG(TIMESTAMPDIFF(MINUTE, data_inicio, data_fim)) as tempo_medio 
            FROM simulacoes 
            WHERE status = 'concluida' AND data_fim IS NOT NULL
        ";
        
        if ($usuario_id) {
            $sql .= " AND usuario_id = :usuario_id";
        }
        
        $stmt = $pdo->prepare($sql);
        
        if ($usuario_id) {
            $stmt->execute(['usuario_id' => $usuario_id]);
        } else {
            $stmt->execute();
        }
        
        $resultado = $stmt->fetch();
        return $resultado['tempo_medio'] ? round($resultado['tempo_medio']) : 0;
        
    } catch (PDOException $e) {
        error_log('Erro ao obter tempo médio: ' . $e->getMessage());
        return 0;
    }
}

/**
 * Função para verificar se email já existe
 */
function emailExiste($pdo, $email, $excluir_id = null) {
    try {
        $sql = "SELECT id FROM usuarios WHERE email = :email";
        $params = ['email' => $email];
        
        if ($excluir_id) {
            $sql .= " AND id != :excluir_id";
            $params['excluir_id'] = $excluir_id;
        }
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetch() !== false;
        
    } catch (PDOException $e) {
        error_log('Erro ao verificar email: ' . $e->getMessage());
        return false;
    }
}

/**
 * Função para verificar se login já existe
 */
function loginExiste($pdo, $login, $excluir_id = null) {
    try {
        $sql = "SELECT id FROM usuarios WHERE login = :login";
        $params = ['login' => $login];
        
        if ($excluir_id) {
            $sql .= " AND id != :excluir_id";
            $params['excluir_id'] = $excluir_id;
        }
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetch() !== false;
        
    } catch (PDOException $e) {
        error_log('Erro ao verificar login: ' . $e->getMessage());
        return false;
    }
}

/**
 * Função para criar usuário
 */
function criarUsuario($pdo, $dados) {
    try {
        $stmt = $pdo->prepare("
            INSERT INTO usuarios (login, senha, nome, email, nivel, avatar) 
            VALUES (:login, :senha, :nome, :email, :nivel, :avatar)
        ");
        
        $dados['senha'] = password_hash($dados['senha'], PASSWORD_DEFAULT);
        $dados['avatar'] = gerarAvatar($dados['nome']);
        
        return $stmt->execute($dados);
        
    } catch (PDOException $e) {
        error_log('Erro ao criar usuário: ' . $e->getMessage());
        return false;
    }
}

/**
 * Função para autenticar usuário
 */
function autenticarUsuario($pdo, $login, $senha) {
    try {
        $stmt = $pdo->prepare("
            SELECT id, login, senha, nome, email, nivel, avatar, ativo 
            FROM usuarios 
            WHERE login = :login
        ");
        
        $stmt->execute(['login' => $login]);
        $usuario = $stmt->fetch();
        
        if ($usuario && $usuario['ativo'] && password_verify($senha, $usuario['senha'])) {
            // Remover senha do array
            unset($usuario['senha']);
            return $usuario;
        }
        
        return false;
        
    } catch (PDOException $e) {
        error_log('Erro ao autenticar usuário: ' . $e->getMessage());
        return false;
    }
}
?> 