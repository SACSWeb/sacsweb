<?php
/**
 * SACSWeb Educacional - Endpoint para atualizar progresso de leitura
 * Atualiza progresso dinamicamente via AJAX
 */

require_once '../config/config.php';
requireLogin();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Método não permitido']);
    exit;
}

$user = getCurrentUser();
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['modulo_id']) || !isset($data['progresso']) || !isset($data['csrf_token'])) {
    echo json_encode(['success' => false, 'error' => 'Dados inválidos']);
    exit;
}

// Validar CSRF token
if (!validateCSRFToken($data['csrf_token'])) {
    echo json_encode(['success' => false, 'error' => 'Token CSRF inválido']);
    exit;
}

$modulo_id = (int)$data['modulo_id'];
$novo_progresso = (float)$data['progresso'];

// Limitar progresso máximo de leitura a 70%
$novo_progresso = min(70, max(0, $novo_progresso));

try {
    $pdo = connectDatabase();
    
    // Verificar se já existe progresso
    $stmt = $pdo->prepare("SELECT * FROM progresso_usuario WHERE usuario_id = ? AND modulo_id = ?");
    $stmt->execute([$user['id'], $modulo_id]);
    $progresso = $stmt->fetch();
    
    if ($progresso) {
        // Verificar se campos do novo modelo existem
        $stmt = $pdo->query("SHOW COLUMNS FROM progresso_usuario LIKE 'progresso_leitura'");
        $tem_novo_modelo = $stmt->rowCount() > 0;
        
        if ($tem_novo_modelo) {
            // NOVO MODELO: progresso_leitura (0-70) + progresso_quiz (0-30)
            $progresso_leitura = min(70, $novo_progresso);
            $progresso_quiz = isset($progresso['progresso_quiz']) ? (float)$progresso['progresso_quiz'] : 0;
            $progresso_total = min(100, $progresso_leitura + $progresso_quiz);
            
            // Atualizar apenas se for maior que o atual
            if ($progresso_leitura > ($progresso['progresso_leitura'] ?? 0)) {
                $stmt = $pdo->prepare("
                    UPDATE progresso_usuario 
                    SET progresso = ?,
                        progresso_leitura = ?
                    WHERE usuario_id = ? AND modulo_id = ?
                ");
                $stmt->execute([$progresso_total, $progresso_leitura, $user['id'], $modulo_id]);
            }
        } else {
            // MODELO ANTIGO: compatibilidade
            $stmt = $pdo->query("SHOW COLUMNS FROM progresso_usuario LIKE 'porcentagem_acertos'");
            $tem_campos_quiz = $stmt->rowCount() > 0;
            
            if ($tem_campos_quiz && isset($progresso['porcentagem_acertos']) && $progresso['porcentagem_acertos'] > 0) {
                // Já tem quiz feito, calcular progresso total
                $progresso_quiz = ($progresso['porcentagem_acertos'] / 100) * 30;
                $progresso_total = min(100, $novo_progresso + $progresso_quiz);
            } else {
                // Apenas leitura, máximo 70%
                $progresso_total = $novo_progresso;
            }
            
            // Atualizar apenas se for maior que o atual
            if ($progresso_total > $progresso['progresso']) {
                $stmt = $pdo->prepare("
                    UPDATE progresso_usuario 
                    SET progresso = ? 
                    WHERE usuario_id = ? AND modulo_id = ?
                ");
                $stmt->execute([$progresso_total, $user['id'], $modulo_id]);
            }
        }
    } else {
        // Criar novo progresso
        $stmt = $pdo->query("SHOW COLUMNS FROM progresso_usuario LIKE 'progresso_leitura'");
        $tem_novo_modelo = $stmt->rowCount() > 0;
        
        if ($tem_novo_modelo) {
            // NOVO MODELO
            $stmt = $pdo->prepare("
                INSERT INTO progresso_usuario 
                (usuario_id, modulo_id, progresso, progresso_leitura, data_inicio, pontos_obtidos)
                VALUES (?, ?, ?, ?, NOW(), 0)
            ");
            $stmt->execute([$user['id'], $modulo_id, $novo_progresso, $novo_progresso]);
        } else {
            // MODELO ANTIGO
            $stmt = $pdo->prepare("
                INSERT INTO progresso_usuario 
                (usuario_id, modulo_id, progresso, data_inicio, pontos_obtidos)
                VALUES (?, ?, ?, NOW(), 0)
            ");
            $stmt->execute([$user['id'], $modulo_id, $novo_progresso]);
        }
    }
    
    echo json_encode(['success' => true, 'progresso' => $novo_progresso]);
    
} catch (PDOException $e) {
    logMessage('Erro ao atualizar progresso: ' . $e->getMessage(), 'error');
    echo json_encode(['success' => false, 'error' => 'Erro ao atualizar progresso']);
}

