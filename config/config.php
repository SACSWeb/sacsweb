<?php
/**
 * SACSWeb Educacional - Configuração Principal
 * Sistema para Ensino de Ataques Cibernéticos e Proteções
 * Versão: 2.0.0
 */

// Iniciar sessão se ainda não foi iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Incluir configurações do banco de dados
require_once __DIR__ . '/database.php';

// Configurações gerais do sistema
define('SITE_URL', 'http://localhost/sacsweb');
define('ASSETS_URL', SITE_URL . '/assets');

// Configurações de upload
define('UPLOAD_DIR', dirname(__DIR__) . '/uploads/');
define('MAX_FILE_SIZE', 5242880); // 5MB
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'txt']);

// Configurações de backup
define('BACKUP_DIR', dirname(__DIR__) . '/backups/');

// Função para redirecionar
function redirect($url) {
    header("Location: $url");
    exit;
}

// Função para mostrar mensagem de erro
function showError($message) {
    $_SESSION['error_message'] = $message;
}

// Função para mostrar mensagem de sucesso
function showSuccess($message) {
    $_SESSION['success_message'] = $message;
}

// Função para obter e limpar mensagens da sessão
function getFlashMessages() {
    $messages = [];
    
    if (isset($_SESSION['error_message'])) {
        $messages['error'] = $_SESSION['error_message'];
        unset($_SESSION['error_message']);
    }
    
    if (isset($_SESSION['success_message'])) {
        $messages['success'] = $_SESSION['success_message'];
        unset($_SESSION['success_message']);
    }
    
    return $messages;
}

// Função para sanitizar entrada
function sanitize($input) {
    if (is_array($input)) {
        return array_map('sanitize', $input);
    }
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

// Função para verificar se é admin
function isAdmin() {
    $user = getCurrentUser();
    return $user && $user['tipo_usuario'] === 'admin';
}

// Função para formatar data brasileira
function formatarData($data) {
    return date('d/m/Y H:i', strtotime($data));
}

// Log de inicialização do sistema
logMessage('Sistema de configuração carregado com sucesso', 'info');
?>
