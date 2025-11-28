# ⚙️ Configuração - SACSWeb Educacional

## Visão Geral

O diretório `config/` contém todos os arquivos de configuração e funções principais do sistema SACSWeb Educacional. Este diretório é responsável por gerenciar conexões com banco de dados, autenticação, sessões e funções auxiliares.

## 📁 Arquivos

### `config.php`
Arquivo principal de configuração do sistema. Define constantes e funções auxiliares.

**Constantes definidas:**
- `SITE_URL`: URL base do sistema
- `ASSETS_URL`: URL dos recursos estáticos
- `UPLOAD_DIR`: Diretório de uploads
- `MAX_FILE_SIZE`: Tamanho máximo de arquivo
- `ALLOWED_EXTENSIONS`: Extensões permitidas
- `BACKUP_DIR`: Diretório de backups

**Funções:**
- `redirect($url)`: Redireciona para uma URL e encerra execução
- `showError($message)`: Define mensagem de erro na sessão
- `showSuccess($message)`: Define mensagem de sucesso na sessão
- `getFlashMessages()`: Obtém e limpa mensagens flash da sessão (erro/sucesso)
- `sanitize($input)`: Sanitiza entrada do usuário (array ou string) usando `htmlspecialchars`
- `isAdmin()`: Verifica se o usuário atual é administrador
- `formatarData($data)`: Formata data no padrão brasileiro (d/m/Y H:i)

### `database.php`
Arquivo de configuração e funções do banco de dados. Gerencia conexões, autenticação e preferências.

**Constantes definidas:**
- `DB_HOST`: Host do banco de dados
- `DB_NAME`: Nome do banco de dados
- `DB_USER`: Usuário do banco
- `DB_PASS`: Senha do banco
- `DB_CHARSET`: Charset (utf8mb4)
- `JWT_SECRET`: Chave secreta para JWT
- `SESSION_SECRET`: Chave secreta para sessões
- `SISTEMA_NOME`: Nome do sistema
- `SISTEMA_VERSAO`: Versão do sistema
- `TIMEZONE`: Timezone (America/Sao_Paulo)
- `SESSAO_EXPIRACAO`: Tempo de expiração da sessão (3600 segundos)
- `LOG_ENABLED`: Habilita/desabilita logs
- `LOG_FILE`: Caminho do arquivo de log

**Funções de Banco de Dados:**
- `connectDatabase()`: Conecta ao banco de dados MySQL usando PDO. Retorna objeto PDO ou encerra execução em caso de erro
- `logMessage($message, $level = 'info')`: Registra mensagem no log do sistema. Cria diretório se não existir. Níveis: 'info', 'warning', 'error', 'critical'

**Funções de Autenticação:**
- `authenticateUser($email, $senha)`: Autentica usuário com email e senha. Verifica hash da senha usando `password_verify`. Retorna dados do usuário ou `false`
- `isLoggedIn()`: Verifica se usuário está logado (verifica `$_SESSION['user_id']`)
- `getCurrentUser()`: Obtém dados completos do usuário logado do banco. Retorna array com id, nome, email, username, tipo_usuario, nivel_conhecimento, foto_perfil ou `null`
- `requireLogin()`: Redireciona para página de autenticação se usuário não estiver logado

**Funções de Segurança:**
- `generateCSRFToken()`: Gera token CSRF aleatório e armazena na sessão. Retorna o token
- `validateCSRFToken($token)`: Valida token CSRF usando comparação segura (`hash_equals`). Retorna `true` ou `false`

**Funções de Preferências:**
- `getDefaultPreferences()`: Retorna array com preferências padrão (tema, tamanho_fonte, alto_contraste, etc.)
- `getUserPreferences(?int $userId, bool $forceRefresh = false)`: Obtém preferências do usuário do banco. Cria registro com padrões se não existir. Implementa cache simples. Retorna array de preferências

### `config.env.example`
Template de arquivo de configuração de ambiente. Contém variáveis de exemplo para:
- Configurações do servidor
- Configurações do banco de dados
- Configurações de segurança
- Configurações do cliente

### `env.example`
Arquivo de exemplo alternativo para variáveis de ambiente.

## 🔧 Uso das Funções

### Conexão com Banco de Dados
```php
require_once '../config/config.php';
$pdo = connectDatabase();
$stmt = $pdo->prepare("SELECT * FROM modulos WHERE id = ?");
$stmt->execute([$id]);
```

### Autenticação
```php
require_once '../config/config.php';
requireLogin(); // Redireciona se não estiver logado
$user = getCurrentUser(); // Obtém dados do usuário
```

### Proteção CSRF
```php
// Gerar token no formulário
$token = generateCSRFToken();
echo '<input type="hidden" name="csrf_token" value="' . $token . '">';

// Validar no processamento
if (validateCSRFToken($_POST['csrf_token'])) {
    // Processar formulário
}
```

### Mensagens Flash
```php
// Definir mensagem
showError('Erro ao salvar dados');
showSuccess('Dados salvos com sucesso');

// Exibir mensagem
$messages = getFlashMessages();
if (isset($messages['error'])) {
    echo '<div class="alert alert-danger">' . $messages['error'] . '</div>';
}
```

### Preferências do Usuário
```php
$user = getCurrentUser();
$preferences = getUserPreferences($user['id']);
// $preferences['tema'], $preferences['tamanho_fonte'], etc.
```

### Logs
```php
logMessage('Usuário fez login', 'info');
logMessage('Erro na conexão', 'error');
logMessage('Ação crítica realizada', 'critical');
```

## 🔐 Segurança

Todas as funções implementam:
- **Prepared statements**: Prevenção de SQL Injection
- **Hash de senhas**: Bcrypt para senhas
- **Sanitização**: Escape de HTML em saídas
- **Validação CSRF**: Proteção contra ataques CSRF
- **Sessões seguras**: Controle de expiração e validação

## 📚 Documentação Adicional

- **[README Principal](../README.md)** - Visão geral do projeto
- **[Website](../website/README.md)** - Documentação das páginas
- **[Assets](../assets/README.md)** - Recursos estáticos
- **[Database](../database/README.md)** - Documentação do banco

---

**⚙️ Configuração SACSWeb Educacional** - Sistema de configuração e funções principais
