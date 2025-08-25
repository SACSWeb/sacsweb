# ⚙️ Configuração - SACSWeb Educacional

## 📁 Arquivos de Configuração

### **`database-educacional.php`** - Configurações PHP
- **Descrição**: Arquivo principal de configuração do sistema
- **Conteúdo**: Constantes, funções auxiliares, configurações de segurança
- **Uso**: Incluído em todos os arquivos PHP do sistema

### **`config.env.example`** - Variáveis de Ambiente
- **Descrição**: Template para configurações de ambiente
- **Conteúdo**: Configurações de banco, segurança, servidor
- **Uso**: Copiar para `.env` e configurar conforme ambiente

### **`env.example`** - Configurações Alternativas
- **Descrição**: Configurações de ambiente alternativas
- **Conteúdo**: Configurações para diferentes ambientes
- **Uso**: Referência para configurações específicas

## 🔧 Configurações do Sistema

### **Configurações de Banco de Dados**
```php
// Configurações do banco de dados
define('DB_HOST', 'localhost');
define('DB_NAME', 'sacsweb_educacional');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');
```

### **Configurações de Segurança**
```php
// Configurações de segurança
define('JWT_SECRET', 'sacsweb-jwt-secret-change-in-production');
define('SESSION_SECRET', 'sacsweb-session-secret-change-in-production');

// Configurações de sessão
define('SESSAO_EXPIRACAO', 86400); // 24 horas
define('SESSAO_NOME', 'sacsweb_session');
```

### **Configurações do Sistema**
```php
// Configurações do sistema
define('SISTEMA_NOME', 'SACSWeb Educacional');
define('SISTEMA_VERSAO', '2.0.0');
define('ENVIRONMENT', 'development'); // development, production, testing
define('DEBUG_MODE', true);
```

### **Configurações de Gamificação**
```php
// Configurações de gamificação
define('PONTOS_LOGIN_DIARIO', 1);
define('PONTOS_LEITURA_MODULO', 2);
define('PONTOS_EXERCICIO_CONCLUIDO', 10);
define('PONTOS_QUIZ_CORRETO', 5);

// Níveis de conquista
define('CONQUISTA_BRONZE', 100);
define('CONQUISTA_PRATA', 500);
define('CONQUISTA_OURO', 1000);
define('CONQUISTA_DIAMANTE', 2000);
```

## 🔒 Configurações de Segurança

### **Headers de Segurança**
```php
// Configurações de segurança adicional
if (SECURITY_HEADERS_ENABLED) {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('X-XSS-Protection: 1; mode=block');

    if (CONTENT_SECURITY_POLICY) {
        header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' https://cdnjs.cloudflare.com; style-src 'self' 'unsafe-inline' https://cdnjs.cloudflare.com; font-src 'self' https://cdnjs.cloudflare.com; img-src 'self' data: https:;");
    }
}
```

### **Configurações de Sessão**
```php
// Configurações de sessão
ini_set('session.gc_maxlifetime', SESSAO_EXPIRACAO);
ini_set('session.cookie_lifetime', SESSAO_EXPIRACAO);
ini_set('session.use_strict_mode', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_samesite', 'Strict');
```

### **Configurações de Upload**
```php
// Configurações de upload
define('MAX_FILE_SIZE', 10485760); // 10MB
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'txt']);

// Configurações de backup
define('BACKUP_ENABLED', true);
define('BACKUP_INTERVAL', 86400); // 24 horas
define('BACKUP_RETENTION', 7); // dias
```

## 📊 Configurações de Monitoramento

### **Logs do Sistema**
```php
// Configurações de log
define('LOG_ENABLED', true);
define('LOG_FILE', '../logs/sacsweb.log');
define('LOG_LEVEL', 'info');

// Configurações de auditoria
define('AUDIT_LOG_ENABLED', true);
define('AUDIT_LOG_RETENTION', 365); // dias
define('AUDIT_LOG_LEVEL', 'medium'); // low, medium, high
```

### **Monitoramento de Performance**
```php
// Configurações de monitoramento de performance
define('PERFORMANCE_MONITORING', true);
define('SLOW_QUERY_THRESHOLD', 1.0); // segundos
define('MEMORY_USAGE_LIMIT', 134217728); // 128MB
define('CPU_USAGE_LIMIT', 80); // porcentagem
```

### **Alertas do Sistema**
```php
// Configurações de alertas
define('ALERTS_ENABLED', true);
define('ALERT_EMAIL', 'admin@sacsweb-educacional.com');
define('ALERT_CRITICAL_LEVEL', 'high');
define('ALERT_WARNING_LEVEL', 'medium');
```

## 🌍 Configurações de Ambiente

### **Desenvolvimento**
```php
// Configurações de desenvolvimento local
if (ENVIRONMENT === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    ini_set('log_errors', 1);
    ini_set('error_log', LOG_FILE);
}
```

### **Produção**
```php
// Configurações de produção
if (ENVIRONMENT === 'production') {
    error_reporting(0);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    ini_set('error_log', LOG_FILE);
}
```

### **Teste**
```php
// Configurações de teste
define('TEST_MODE', false);
define('TEST_DATABASE', 'sacsweb_educacional_test');
define('MOCK_DATA_ENABLED', false);
```

## 🔧 Funções Auxiliares

### **Verificação de Requisitos**
```php
function checkSystemRequirements() {
    $requirements = [];

    // Verificar versão do PHP
    $requirements['php_version'] = [
        'required' => '7.4.0',
        'current' => PHP_VERSION,
        'status' => version_compare(PHP_VERSION, '7.4.0', '>=')
    ];

    // Verificar extensões necessárias
    $requiredExtensions = ['pdo', 'pdo_mysql', 'json', 'mbstring', 'openssl'];
    foreach ($requiredExtensions as $ext) {
        $requirements['extensions'][$ext] = [
            'name' => "Extensão $ext",
            'required' => true,
            'current' => extension_loaded($ext),
            'status' => extension_loaded($ext)
        ];
    }

    return $requirements;
}
```

### **Conexão com Banco**
```php
function connectDatabase() {
    try {
        $pdo = new PDO(getDatabaseDSN(), DB_USER, DB_PASS, getDatabaseOptions());
        logMessage('Conexão com banco de dados estabelecida', 'info');
        return $pdo;
    } catch (PDOException $e) {
        logMessage('Erro ao conectar ao banco de dados: ' . $e->getMessage(), 'critical');
        throw $e;
    }
}

function getDatabaseDSN() {
    return "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
}

function getDatabaseOptions() {
    return [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . DB_CHARSET
    ];
}
```

### **Sistema de Logs**
```php
function logMessage($message, $level = 'info', $context = []) {
    if (!getConfig('LOG_ENABLED', true)) {
        return;
    }

    $logLevel = getConfig('LOG_LEVEL', 'info');
    $logFile = getConfig('LOG_FILE', '../logs/sacsweb.log');

    $levels = ['debug' => 0, 'info' => 1, 'warning' => 2, 'error' => 3, 'critical' => 4];

    if ($levels[$level] < $levels[$logLevel]) {
        return;
    }

    $timestamp = date('Y-m-d H:i:s');
    $logEntry = "[$timestamp] [$level] $message";

    if (!empty($context)) {
        $logEntry .= ' ' . json_encode($context);
    }

    $logEntry .= PHP_EOL;

    // Criar diretório de logs se não existir
    $logDir = dirname($logFile);
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }

    file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
}
```

### **Validação e Sanitização**
```php
function sanitizeInput($input, $type = 'string') {
    switch ($type) {
        case 'email':
            return filter_var(trim($input), FILTER_SANITIZE_EMAIL);
        case 'url':
            return filter_var(trim($input), FILTER_SANITIZE_URL);
        case 'int':
            return filter_var(trim($input), FILTER_SANITIZE_NUMBER_INT);
        case 'float':
            return filter_var(trim($input), FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
        case 'string':
        default:
            return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }
}

function validateInput($input, $rules) {
    $errors = [];

    foreach ($rules as $field => $rule) {
        if (!isset($input[$field])) {
            if (isset($rule['required']) && $rule['required']) {
                $errors[$field] = "Campo $field é obrigatório";
            }
            continue;
        }

        $value = $input[$field];

        if (isset($rule['min_length']) && strlen($value) < $rule['min_length']) {
            $errors[$field] = "Campo $field deve ter pelo menos {$rule['min_length']} caracteres";
        }

        if (isset($rule['max_length']) && strlen($value) > $rule['max_length']) {
            $errors[$field] = "Campo $field deve ter no máximo {$rule['max_length']} caracteres";
        }

        if (isset($rule['pattern']) && !preg_match($rule['pattern'], $value)) {
            $errors[$field] = "Campo $field não está no formato correto";
        }
    }

    return $errors;
}
```

## 🚀 Configuração de Deploy

### **Variáveis de Ambiente**
```bash
# .env
# Configurações do Servidor
PORT=3001
NODE_ENV=production

# Configurações do Banco de Dados
DB_HOST=localhost
DB_USER=sacsweb_user
DB_PASS=senha_forte_aqui
DB_NAME=sacsweb_educacional

# Configurações de Segurança
JWT_SECRET=sacsweb-jwt-secret-producao-aqui
SESSION_SECRET=sacsweb-session-secret-producao-aqui

# Configurações do Cliente
CLIENT_URL=https://seudominio.com
```

### **Configurações de Produção**
```php
// Configurações específicas de produção
if (ENVIRONMENT === 'production') {
    // Desabilitar debug
    define('DEBUG_MODE', false);
    define('SHOW_ERRORS', false);
    
    // Configurações de segurança
    define('SESSION_COOKIE_SECURE', true);
    define('SESSION_COOKIE_HTTPONLY', true);
    
    // Configurações de performance
    define('CACHE_ENABLED', true);
    define('GZIP_ENABLED', true);
}
```

### **Configurações de Cache**
```php
// Configurações de cache
define('CACHE_ENABLED', false);
define('CACHE_DURATION', 3600); // 1 hora

// Configurações de compressão
define('GZIP_ENABLED', true);
define('GZIP_LEVEL', 6);
```

## 🔧 Manutenção

### **Backup Automático**
```php
function createAutoBackup() {
    if (!getConfig('AUTO_BACKUP_ENABLED', true)) {
        return;
    }
    
    try {
        $backupDir = '../backups/database/';
        if (!is_dir($backupDir)) {
            mkdir($backupDir, 0755, true);
        }
        
        $filename = $backupDir . 'backup_' . date('Y-m-d_H-i-s') . '.sql';
        
        $command = sprintf(
            'mysqldump --host=%s --user=%s --password=%s %s > %s',
            escapeshellarg(DB_HOST),
            escapeshellarg(DB_USER),
            escapeshellarg(DB_PASS),
            escapeshellarg(DB_NAME),
            escapeshellarg($filename)
        );
        
        exec($command, $output, $returnCode);
        
        if ($returnCode === 0) {
            logMessage('Backup automático criado com sucesso: ' . $filename, 'info');
        }
    } catch (Exception $e) {
        logMessage('Erro durante backup automático: ' . $e->getMessage(), 'error');
    }
}
```

### **Limpeza Automática**
```php
function cleanupOldData() {
    if (!getConfig('AUTO_CLEANUP_ENABLED', true)) {
        return;
    }
    
    try {
        $pdo = connectDatabase();
        
        // Limpar sessões antigas
        if (getConfig('CLEANUP_OLD_SESSIONS', true)) {
            $stmt = $pdo->prepare("DELETE FROM sessoes WHERE data_expiracao < NOW()");
            $stmt->execute();
        }
        
        // Limpar logs antigos
        if (getConfig('CLEANUP_OLD_LOGS', true)) {
            $retention = getConfig('AUDIT_LOG_RETENTION', 365);
            $stmt = $pdo->prepare("DELETE FROM logs_seguranca WHERE data_registro < DATE_SUB(NOW(), INTERVAL ? DAY)");
            $stmt->execute([$retention]);
        }
        
        logMessage('Limpeza automática de dados concluída', 'info');
        
    } catch (Exception $e) {
        logMessage('Erro durante limpeza automática: ' . $e->getMessage(), 'error');
    }
}
```

## 🆘 Solução de Problemas

### **Verificação de Configuração**
```php
function checkSystemHealth() {
    $health = [
        'database' => false,
        'filesystem' => false,
        'memory' => false,
        'timestamp' => date('Y-m-d H:i:s')
    ];
    
    try {
        $pdo = connectDatabase();
        $health['database'] = true;
        $pdo = null;
    } catch (Exception $e) {
        $health['database'] = false;
    }
    
    $health['filesystem'] = is_writable('../logs') && is_writable('../backups');
    $health['memory'] = memory_get_usage() < getConfig('MEMORY_USAGE_LIMIT', 134217728);
    
    return $health;
}
```

### **Debug de Configuração**
```php
function debugConfig() {
    if (!getConfig('DEBUG_MODE', false)) {
        return 'Debug desabilitado';
    }
    
    $debug = [
        'php_version' => PHP_VERSION,
        'extensions' => get_loaded_extensions(),
        'database_config' => [
            'host' => DB_HOST,
            'name' => DB_NAME,
            'user' => DB_USER,
            'charset' => DB_CHARSET
        ],
        'system_config' => [
            'environment' => ENVIRONMENT,
            'debug_mode' => DEBUG_MODE,
            'log_enabled' => LOG_ENABLED
        ]
    ];
    
    return json_encode($debug, JSON_PRETTY_PRINT);
}
```

## 📚 Documentação Adicional

- **📖 [README Principal](../README.md)** - Visão geral do projeto
- **🚀 [Backend](../backend/README.md)** - Documentação da API
- **🌐 [Website](../website/README.md)** - Documentação do frontend
- **🗄️ [Banco de Dados](../database/README.md)** - Documentação do banco
- **🔧 [Instalação](../install/README.md)** - Documentação da instalação

---

**⚙️ Configuração SACSWeb Educacional** - Sistema de configuração robusto e flexível
