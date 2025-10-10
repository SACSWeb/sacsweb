<?php
/**
 * SACSWeb Educacional - Logout
 * Sistema para Ensino de Ataques Cibernéticos e Proteções
 */

require_once '../config/config.php';

// Registrar logout se usuário estiver logado
if (isLoggedIn()) {
    $user = getCurrentUser();
    if ($user) {
        try {
            $pdo = connectDatabase();
            
            // Registrar atividade de logout
            $stmt = $pdo->prepare("INSERT INTO logs_atividade (usuario_id, acao, descricao, ip_address) VALUES (?, 'LOGOUT', 'Logout realizado', ?)");
            $stmt->execute([$user['id'], $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1']);
            
            logMessage("Logout realizado por: " . $user['email'], 'info');
        } catch (PDOException $e) {
            logMessage('Erro ao registrar logout: ' . $e->getMessage(), 'error');
        }
    }
}

// Limpar cookie de "lembrar" se existir
if (isset($_COOKIE['remember_token'])) {
    setcookie('remember_token', '', time() - 3600, '/');
}

// Destruir sessão
session_destroy();

// Redirecionar para página inicial
redirect('/sacsweb/');
?>
