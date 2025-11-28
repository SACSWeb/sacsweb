<?php
/**
 * SACSWeb Educacional - Verificação Automática de Disponibilidade
 * Verifica disponibilidade de email e username automaticamente via AJAX
 * 
 * @package SACSWeb
 * @version 2.0.0
 */

require_once '../config/config.php';

// Configurar headers JSON
header('Content-Type: application/json; charset=utf-8');

// Obter parâmetros
$email = trim($_GET['email'] ?? $_POST['email'] ?? '');
$username = trim($_GET['username'] ?? $_POST['username'] ?? '');

$response = [
    'email' => ['available' => true, 'message' => ''],
    'username' => ['available' => true, 'message' => '']
];

try {
    $pdo = connectDatabase();
    
    // Verificar se campo username existe na tabela
    $stmt = $pdo->query("SHOW COLUMNS FROM usuarios LIKE 'username'");
    $hasUsernameField = $stmt->rowCount() > 0;
    
    // Verificar disponibilidade de EMAIL
    if (!empty($email)) {
        // Validação básica de email
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $response['email'] = [
                'available' => false,
                'message' => 'Email inválido'
            ];
        } else {
            // Verificar se email já existe
            $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM usuarios WHERE email = ?");
            $stmt->execute([$email]);
            $exists = ((int)$stmt->fetch()['count']) > 0;
            
            if ($exists) {
                $response['email'] = [
                    'available' => false,
                    'message' => 'Este email já está em uso'
                ];
            } else {
                $response['email'] = [
                    'available' => true,
                    'message' => 'Email disponível'
                ];
            }
        }
    }
    
    // Verificar disponibilidade de USERNAME
    if (!empty($username)) {
        // Validação básica de username
        if (strlen($username) < 3) {
            $response['username'] = [
                'available' => false,
                'message' => 'Nome de usuário deve ter pelo menos 3 caracteres'
            ];
        } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
            $response['username'] = [
                'available' => false,
                'message' => 'Apenas letras, números e underscore são permitidos'
            ];
        } else {
            if ($hasUsernameField) {
                // Verificar se username já existe (se campo existe)
                $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM usuarios WHERE username = ?");
                $stmt->execute([$username]);
                $exists = ((int)$stmt->fetch()['count']) > 0;
            } else {
                // Se não tem campo username, verificar se email já está em uso (username = email)
                $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM usuarios WHERE email = ?");
                $stmt->execute([$username]);
                $exists = ((int)$stmt->fetch()['count']) > 0;
            }
            
            if ($exists) {
                $response['username'] = [
                    'available' => false,
                    'message' => 'Este nome de usuário já está em uso'
                ];
            } else {
                $response['username'] = [
                    'available' => true,
                    'message' => 'Nome de usuário disponível'
                ];
            }
        }
    }
    
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    
} catch (PDOException $e) {
    logMessage('Erro ao verificar disponibilidade: ' . $e->getMessage(), 'error');
    http_response_code(500);
    echo json_encode([
        'error' => true,
        'message' => 'Erro ao verificar disponibilidade. Tente novamente.'
    ], JSON_UNESCAPED_UNICODE);
}
?>

