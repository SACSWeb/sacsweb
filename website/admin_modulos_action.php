<?php
/**
 * SACSWeb Educacional - Endpoint de Ações CRUD para Módulos
 * Sistema para Ensino de Ataques Cibernéticos e Proteções
 * 
 * @package SACSWeb
 * @version 2.0.0
 */

require_once '../config/config.php';

// Verificar se usuário está logado e é admin
requireLogin();

if (!isAdmin()) {
    header('HTTP/1.1 403 Forbidden');
    echo json_encode(['sucesso' => false, 'mensagem' => 'Acesso negado. Apenas administradores podem realizar esta ação.']);
    exit;
}

// Configurar header JSON
header('Content-Type: application/json; charset=utf-8');

// Obter método da requisição
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {
    $pdo = connectDatabase();
    $user = getCurrentUser();
    
    // Validar token CSRF
    if ($method === 'POST' || $method === 'DELETE') {
        $csrfToken = $_POST['csrf_token'] ?? $_GET['csrf_token'] ?? '';
        if (!validateCSRFToken($csrfToken)) {
            echo json_encode(['sucesso' => false, 'mensagem' => 'Token de segurança inválido.']);
            exit;
        }
    }
    
    switch ($action) {
        case 'create':
            criarModulo($pdo, $user['id']);
            break;
            
        case 'update':
            atualizarModulo($pdo, $user['id']);
            break;
            
        case 'delete':
            deletarModulo($pdo, $user['id']);
            break;
            
        case 'get':
            obterModulo($pdo);
            break;
            
        default:
            echo json_encode(['sucesso' => false, 'mensagem' => 'Ação não reconhecida.']);
            break;
    }
} catch (PDOException $e) {
    logMessage('Erro no admin_modulos_action: ' . $e->getMessage(), 'error');
    echo json_encode(['sucesso' => false, 'mensagem' => 'Erro ao processar requisição.']);
}

/**
 * Criar novo módulo
 */
function criarModulo($pdo, $userId) {
    // Validar e sanitizar dados
    $titulo = trim($_POST['titulo'] ?? '');
    $descricao = trim($_POST['descricao'] ?? '');
    $nivel = $_POST['nivel'] ?? 'iniciante';
    $tipo_ataque = trim($_POST['tipo_ataque'] ?? '');
    $conteudo_teorico = trim($_POST['conteudo_teorico'] ?? '');
    $exemplo_pratico = trim($_POST['exemplo_pratico'] ?? '');
    $demonstracao_codigo = trim($_POST['demonstracao_codigo'] ?? '');
    $linguagem_codigo = trim($_POST['linguagem_codigo'] ?? 'php');
    $tempo_estimado = intval($_POST['tempo_estimado'] ?? 30);
    $pontos_maximos = intval($_POST['pontos_maximos'] ?? 100);
    $ordem = intval($_POST['ordem'] ?? 0);
    $ativo = isset($_POST['ativo']) ? 1 : 0;
    
    // Validações
    if (empty($titulo) || empty($descricao) || empty($conteudo_teorico)) {
        echo json_encode(['sucesso' => false, 'mensagem' => 'Título, descrição e conteúdo teórico são obrigatórios.']);
        return;
    }
    
    if (!in_array($nivel, ['iniciante', 'intermediario', 'avancado'])) {
        echo json_encode(['sucesso' => false, 'mensagem' => 'Nível inválido.']);
        return;
    }
    
    if ($tempo_estimado < 1 || $tempo_estimado > 600) {
        echo json_encode(['sucesso' => false, 'mensagem' => 'Tempo estimado deve estar entre 1 e 600 minutos.']);
        return;
    }
    
    if ($pontos_maximos < 1 || $pontos_maximos > 1000) {
        echo json_encode(['sucesso' => false, 'mensagem' => 'Pontos máximos deve estar entre 1 e 1000.']);
        return;
    }
    
    // Sanitizar dados
    $titulo = htmlspecialchars($titulo, ENT_QUOTES, 'UTF-8');
    $descricao = htmlspecialchars($descricao, ENT_QUOTES, 'UTF-8');
    $tipo_ataque = htmlspecialchars($tipo_ataque, ENT_QUOTES, 'UTF-8');
    $conteudo_teorico = htmlspecialchars($conteudo_teorico, ENT_QUOTES, 'UTF-8');
    $exemplo_pratico = htmlspecialchars($exemplo_pratico, ENT_QUOTES, 'UTF-8');
    $demonstracao_codigo = htmlspecialchars($demonstracao_codigo, ENT_QUOTES, 'UTF-8');
    $linguagem_codigo = htmlspecialchars($linguagem_codigo, ENT_QUOTES, 'UTF-8');
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO modulos 
            (titulo, descricao, nivel, tipo_ataque, conteudo_teorico, exemplo_pratico, demonstracao_codigo, 
             linguagem_codigo, tempo_estimado, pontos_maximos, ordem, ativo, data_criacao) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        
        $result = $stmt->execute([
            $titulo, $descricao, $nivel, $tipo_ataque, $conteudo_teorico, 
            $exemplo_pratico, $demonstracao_codigo, $linguagem_codigo, 
            $tempo_estimado, $pontos_maximos, $ordem, $ativo
        ]);
        
        if ($result) {
            $moduloId = $pdo->lastInsertId();
            
            // Registrar atividade
            $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
            $stmt = $pdo->prepare("
                INSERT INTO logs_atividade 
                (usuario_id, acao, detalhes, ip_address, data_hora) 
                VALUES (?, 'MODULO_CRIADO', ?, ?, NOW())
            ");
            $stmt->execute([$userId, "Módulo criado: {$titulo} (ID: {$moduloId})", $ipAddress]);
            
            logMessage("Módulo criado: {$titulo} (ID: {$moduloId}) por usuário {$userId}", 'info');
            
            echo json_encode([
                'sucesso' => true, 
                'mensagem' => 'Módulo criado com sucesso!',
                'id' => $moduloId
            ]);
        } else {
            echo json_encode(['sucesso' => false, 'mensagem' => 'Erro ao criar módulo.']);
        }
    } catch (PDOException $e) {
        logMessage('Erro ao criar módulo: ' . $e->getMessage(), 'error');
        echo json_encode(['sucesso' => false, 'mensagem' => 'Erro ao criar módulo no banco de dados.']);
    }
}

/**
 * Atualizar módulo existente
 */
function atualizarModulo($pdo, $userId) {
    $moduloId = intval($_POST['id'] ?? 0);
    
    if ($moduloId <= 0) {
        echo json_encode(['sucesso' => false, 'mensagem' => 'ID do módulo inválido.']);
        return;
    }
    
    // Validar e sanitizar dados (mesmo processo de criar)
    $titulo = trim($_POST['titulo'] ?? '');
    $descricao = trim($_POST['descricao'] ?? '');
    $nivel = $_POST['nivel'] ?? 'iniciante';
    $tipo_ataque = trim($_POST['tipo_ataque'] ?? '');
    $conteudo_teorico = trim($_POST['conteudo_teorico'] ?? '');
    $exemplo_pratico = trim($_POST['exemplo_pratico'] ?? '');
    $demonstracao_codigo = trim($_POST['demonstracao_codigo'] ?? '');
    $linguagem_codigo = trim($_POST['linguagem_codigo'] ?? 'php');
    $tempo_estimado = intval($_POST['tempo_estimado'] ?? 30);
    $pontos_maximos = intval($_POST['pontos_maximos'] ?? 100);
    $ordem = intval($_POST['ordem'] ?? 0);
    $ativo = isset($_POST['ativo']) ? 1 : 0;
    
    // Validações
    if (empty($titulo) || empty($descricao) || empty($conteudo_teorico)) {
        echo json_encode(['sucesso' => false, 'mensagem' => 'Título, descrição e conteúdo teórico são obrigatórios.']);
        return;
    }
    
    if (!in_array($nivel, ['iniciante', 'intermediario', 'avancado'])) {
        echo json_encode(['sucesso' => false, 'mensagem' => 'Nível inválido.']);
        return;
    }
    
    // Verificar se módulo existe
    $stmt = $pdo->prepare("SELECT id, titulo FROM modulos WHERE id = ?");
    $stmt->execute([$moduloId]);
    $moduloExistente = $stmt->fetch();
    
    if (!$moduloExistente) {
        echo json_encode(['sucesso' => false, 'mensagem' => 'Módulo não encontrado.']);
        return;
    }
    
    // Sanitizar dados
    $titulo = htmlspecialchars($titulo, ENT_QUOTES, 'UTF-8');
    $descricao = htmlspecialchars($descricao, ENT_QUOTES, 'UTF-8');
    $tipo_ataque = htmlspecialchars($tipo_ataque, ENT_QUOTES, 'UTF-8');
    $conteudo_teorico = htmlspecialchars($conteudo_teorico, ENT_QUOTES, 'UTF-8');
    $exemplo_pratico = htmlspecialchars($exemplo_pratico, ENT_QUOTES, 'UTF-8');
    $demonstracao_codigo = htmlspecialchars($demonstracao_codigo, ENT_QUOTES, 'UTF-8');
    $linguagem_codigo = htmlspecialchars($linguagem_codigo, ENT_QUOTES, 'UTF-8');
    
    try {
        $stmt = $pdo->prepare("
            UPDATE modulos SET
                titulo = ?, descricao = ?, nivel = ?, tipo_ataque = ?, 
                conteudo_teorico = ?, exemplo_pratico = ?, demonstracao_codigo = ?,
                linguagem_codigo = ?, tempo_estimado = ?, pontos_maximos = ?, 
                ordem = ?, ativo = ?
            WHERE id = ?
        ");
        
        $result = $stmt->execute([
            $titulo, $descricao, $nivel, $tipo_ataque, $conteudo_teorico,
            $exemplo_pratico, $demonstracao_codigo, $linguagem_codigo,
            $tempo_estimado, $pontos_maximos, $ordem, $ativo, $moduloId
        ]);
        
        if ($result) {
            // Registrar atividade
            $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
            $stmt = $pdo->prepare("
                INSERT INTO logs_atividade 
                (usuario_id, modulo_id, acao, detalhes, ip_address, data_hora) 
                VALUES (?, ?, 'MODULO_ATUALIZADO', ?, ?, NOW())
            ");
            $stmt->execute([$userId, $moduloId, "Módulo atualizado: {$titulo}", $ipAddress]);
            
            logMessage("Módulo atualizado: {$titulo} (ID: {$moduloId}) por usuário {$userId}", 'info');
            
            echo json_encode([
                'sucesso' => true, 
                'mensagem' => 'Módulo atualizado com sucesso!'
            ]);
        } else {
            echo json_encode(['sucesso' => false, 'mensagem' => 'Erro ao atualizar módulo.']);
        }
    } catch (PDOException $e) {
        logMessage('Erro ao atualizar módulo: ' . $e->getMessage(), 'error');
        echo json_encode(['sucesso' => false, 'mensagem' => 'Erro ao atualizar módulo no banco de dados.']);
    }
}

/**
 * Deletar módulo
 */
function deletarModulo($pdo, $userId) {
    $moduloId = intval($_POST['id'] ?? $_GET['id'] ?? 0);
    
    if ($moduloId <= 0) {
        echo json_encode(['sucesso' => false, 'mensagem' => 'ID do módulo inválido.']);
        return;
    }
    
    // Verificar se módulo existe e obter título
    $stmt = $pdo->prepare("SELECT id, titulo FROM modulos WHERE id = ?");
    $stmt->execute([$moduloId]);
    $modulo = $stmt->fetch();
    
    if (!$modulo) {
        echo json_encode(['sucesso' => false, 'mensagem' => 'Módulo não encontrado.']);
        return;
    }
    
    try {
        // Verificar se há progresso associado
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM progresso_usuario WHERE modulo_id = ?");
        $stmt->execute([$moduloId]);
        $progresso = $stmt->fetch();
        
        if ($progresso['total'] > 0) {
            // Se houver progresso, apenas desativar o módulo
            $stmt = $pdo->prepare("UPDATE modulos SET ativo = 0 WHERE id = ?");
            $stmt->execute([$moduloId]);
            
            $acao = 'MODULO_DESATIVADO';
            $detalhes = "Módulo desativado (há progresso associado): {$modulo['titulo']}";
            $mensagem = 'Módulo desativado com sucesso (há progresso de usuários associado).';
        } else {
            // Se não houver progresso, deletar completamente
            $stmt = $pdo->prepare("DELETE FROM modulos WHERE id = ?");
            $stmt->execute([$moduloId]);
            
            $acao = 'MODULO_DELETADO';
            $detalhes = "Módulo deletado: {$modulo['titulo']}";
            $mensagem = 'Módulo deletado com sucesso!';
        }
        
        // Registrar atividade
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $stmt = $pdo->prepare("
            INSERT INTO logs_atividade 
            (usuario_id, modulo_id, acao, detalhes, ip_address, data_hora) 
            VALUES (?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([$userId, $moduloId, $acao, $detalhes, $ipAddress]);
        
        logMessage("Módulo {$acao}: {$modulo['titulo']} (ID: {$moduloId}) por usuário {$userId}", 'info');
        
        echo json_encode([
            'sucesso' => true, 
            'mensagem' => $mensagem
        ]);
    } catch (PDOException $e) {
        logMessage('Erro ao deletar módulo: ' . $e->getMessage(), 'error');
        echo json_encode(['sucesso' => false, 'mensagem' => 'Erro ao deletar módulo no banco de dados.']);
    }
}

/**
 * Obter dados de um módulo específico
 */
function obterModulo($pdo) {
    $moduloId = intval($_GET['id'] ?? 0);
    
    if ($moduloId <= 0) {
        echo json_encode(['sucesso' => false, 'mensagem' => 'ID do módulo inválido.']);
        return;
    }
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM modulos WHERE id = ?");
        $stmt->execute([$moduloId]);
        $modulo = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($modulo) {
            echo json_encode([
                'sucesso' => true,
                'dados' => $modulo
            ]);
        } else {
            echo json_encode(['sucesso' => false, 'mensagem' => 'Módulo não encontrado.']);
        }
    } catch (PDOException $e) {
        logMessage('Erro ao obter módulo: ' . $e->getMessage(), 'error');
        echo json_encode(['sucesso' => false, 'mensagem' => 'Erro ao obter módulo do banco de dados.']);
    }
}
?>

