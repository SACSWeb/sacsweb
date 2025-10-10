<?php
/**
 * Verificação de disponibilidade de nome de usuário (AJAX)
 */

require_once '../config/config.php';

header('Content-Type: application/json; charset=utf-8');

$username = trim($_GET['username'] ?? $_POST['username'] ?? '');

if ($username === '' || strlen($username) < 3 || !preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
    echo json_encode([
        'available' => false,
        'message' => 'Informe um nome de usuário válido (mínimo 3 caracteres, apenas letras, números e underscore)'
    ]);
    exit;
}

try {
    $pdo = connectDatabase();
    $stmt = $pdo->prepare('SELECT COUNT(*) as count FROM usuarios WHERE username = ?');
    $stmt->execute([$username]);
    $exists = ((int)$stmt->fetch()['count']) > 0;

    if ($exists) {
        echo json_encode([
            'available' => false,
            'message' => 'Este nome de usuário já está em uso'
        ]);
    } else {
        echo json_encode([
            'available' => true,
            'message' => 'Nome de usuário disponível'
        ]);
    }
} catch (PDOException $e) {
    logMessage('Erro ao verificar username: ' . $e->getMessage(), 'error');
    http_response_code(500);
    echo json_encode([
        'available' => false,
        'message' => 'Erro ao verificar disponibilidade. Tente novamente.'
    ]);
}
?>


