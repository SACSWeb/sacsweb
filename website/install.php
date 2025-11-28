<?php
/**
 * Script de Instalação - SACSWeb
 * Sistema de Segurança Cibernética
 */

// Verificar se já está instalado
if (file_exists('config/installed.txt')) {
    die('SACSWeb já está instalado. Remova o arquivo config/installed.txt para reinstalar.');
}

// Configurações iniciais
error_reporting(E_ALL);
ini_set('display_errors', 1);

?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instalação - SACSWeb</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 50%, #60a5fa 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .install-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
        }
        .step-indicator {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            color: white;
        }
        .step-active {
            background: #3b82f6;
        }
        .step-completed {
            background: #10b981;
        }
        .step-pending {
            background: #6b7280;
        }
    </style>
</head>
<body>
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="install-card p-5">
                    <div class="text-center mb-4">
                        <h1 class="text-primary mb-3">
                            <i class="fas fa-shield-alt"></i> SACSWeb
                        </h1>
                        <h4>Instalação do Sistema</h4>
                        <p class="text-light">Simulador de Ataque Cibernético para Sistemas Web</p>
                    </div>

                    <?php
                    $step = $_GET['step'] ?? 1;
                    $error = '';
                    $success = '';

                    // Processar formulário
                    if ($_POST) {
                        switch ($step) {
                            case 1:
                                // Verificar requisitos
                                $requirements = checkRequirements();
                                if ($requirements['all_ok']) {
                                    header('Location: install.php?step=2');
                                    exit;
                                } else {
                                    $error = 'Alguns requisitos não foram atendidos.';
                                }
                                break;

                            case 2:
                                // Configurar banco de dados
                                $db_config = $_POST['db_config'];
                                if (testDatabaseConnection($db_config)) {
                                    // Salvar configuração
                                    if (saveDatabaseConfig($db_config)) {
                                        header('Location: install.php?step=3');
                                        exit;
                                    } else {
                                        $error = 'Erro ao salvar configuração do banco de dados.';
                                    }
                                } else {
                                    $error = 'Erro ao conectar com o banco de dados. Verifique as credenciais.';
                                }
                                break;

                            case 3:
                                // Criar banco de dados
                                if (createDatabase()) {
                                    header('Location: install.php?step=4');
                                    exit;
                                } else {
                                    $error = 'Erro ao criar banco de dados.';
                                }
                                break;

                            case 4:
                                // Configurar administrador
                                $admin_config = $_POST['admin_config'];
                                if (createAdminUser($admin_config)) {
                                    // Marcar como instalado
                                    file_put_contents('config/installed.txt', date('Y-m-d H:i:s'));
                                    $success = 'SACSWeb instalado com sucesso!';
                                } else {
                                    $error = 'Erro ao criar usuário administrador.';
                                }
                                break;
                        }
                    }
                    ?>

                    <!-- Indicadores de Progresso -->
                    <div class="d-flex justify-content-between mb-4">
                        <div class="text-center">
                            <div class="step-indicator <?php echo $step >= 1 ? 'step-active' : 'step-pending'; ?>">
                                <?php echo $step > 1 ? '<i class="fas fa-check"></i>' : '1'; ?>
                            </div>
                            <small class="d-block mt-1">Requisitos</small>
                        </div>
                        <div class="text-center">
                            <div class="step-indicator <?php echo $step >= 2 ? 'step-active' : 'step-pending'; ?>">
                                <?php echo $step > 2 ? '<i class="fas fa-check"></i>' : '2'; ?>
                            </div>
                            <small class="d-block mt-1">Banco de Dados</small>
                        </div>
                        <div class="text-center">
                            <div class="step-indicator <?php echo $step >= 3 ? 'step-active' : 'step-pending'; ?>">
                                <?php echo $step > 3 ? '<i class="fas fa-check"></i>' : '3'; ?>
                            </div>
                            <small class="d-block mt-1">Estrutura</small>
                        </div>
                        <div class="text-center">
                            <div class="step-indicator <?php echo $step >= 4 ? 'step-active' : 'step-pending'; ?>">
                                <?php echo $step > 4 ? '<i class="fas fa-check"></i>' : '4'; ?>
                            </div>
                            <small class="d-block mt-1">Finalizar</small>
                        </div>
                    </div>

                    <?php if ($error): ?>
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle"></i> <?php echo $error; ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($success): ?>
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle"></i> <?php echo $success; ?>
                            <div class="mt-3">
                                <a href="index.php" class="btn btn-primary">
                                    <i class="fas fa-sign-in-alt"></i> Acessar Sistema
                                </a>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Conteúdo do Passo -->
                    <?php if ($step == 1): ?>
                        <h5><i class="fas fa-list-check"></i> Verificação de Requisitos</h5>
                        <p>O sistema verificará se todos os requisitos estão atendidos.</p>
                        
                        <?php $requirements = checkRequirements(); ?>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Requisito</th>
                                        <th>Status</th>
                                        <th>Versão</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>PHP</td>
                                        <td>
                                            <?php if ($requirements['php_ok']): ?>
                                                <span class="badge bg-success"><i class="fas fa-check"></i> OK</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger"><i class="fas fa-times"></i> Erro</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo $requirements['php_version']; ?></td>
                                    </tr>
                                    <tr>
                                        <td>Extensão PDO</td>
                                        <td>
                                            <?php if ($requirements['pdo_ok']): ?>
                                                <span class="badge bg-success"><i class="fas fa-check"></i> OK</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger"><i class="fas fa-times"></i> Erro</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>-</td>
                                    </tr>
                                    <tr>
                                        <td>Extensão MySQL</td>
                                        <td>
                                            <?php if ($requirements['mysql_ok']): ?>
                                                <span class="badge bg-success"><i class="fas fa-check"></i> OK</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger"><i class="fas fa-times"></i> Erro</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>-</td>
                                    </tr>
                                    <tr>
                                        <td>Permissões de Escrita</td>
                                        <td>
                                            <?php if ($requirements['write_ok']): ?>
                                                <span class="badge bg-success"><i class="fas fa-check"></i> OK</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger"><i class="fas fa-times"></i> Erro</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>-</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <?php if ($requirements['all_ok']): ?>
                            <form method="post" class="text-center">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-arrow-right"></i> Continuar
                                </button>
                            </form>
                        <?php else: ?>
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle"></i>
                                Corrija os requisitos não atendidos antes de continuar.
                            </div>
                        <?php endif; ?>

                    <?php elseif ($step == 2): ?>
                        <h5><i class="fas fa-database"></i> Configuração do Banco de Dados</h5>
                        <p>Configure a conexão com o banco de dados MySQL.</p>
                        
                        <form method="post">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="db_host" class="form-label">Host</label>
                                    <input type="text" class="form-control" id="db_host" name="db_config[host]" 
                                           value="localhost" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="db_port" class="form-label">Porta</label>
                                    <input type="number" class="form-control" id="db_port" name="db_config[port]" 
                                           value="3306" required>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="db_name" class="form-label">Nome do Banco</label>
                                    <input type="text" class="form-control" id="db_name" name="db_config[name]" 
                                           value="sacsweb" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="db_charset" class="form-label">Charset</label>
                                    <input type="text" class="form-control" id="db_charset" name="db_config[charset]" 
                                           value="utf8mb4" required>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="db_user" class="form-label">Usuário</label>
                                    <input type="text" class="form-control" id="db_user" name="db_config[user]" 
                                           value="root" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="db_pass" class="form-label">Senha</label>
                                    <input type="password" class="form-control" id="db_pass" name="db_config[pass]" 
                                           value="">
                                </div>
                            </div>
                            <div class="text-center">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-arrow-right"></i> Testar Conexão
                                </button>
                            </div>
                        </form>

                    <?php elseif ($step == 3): ?>
                        <h5><i class="fas fa-cogs"></i> Criação da Estrutura</h5>
                        <p>Criando tabelas e configurações iniciais do banco de dados...</p>
                        
                        <div class="progress mb-3">
                            <div class="progress-bar progress-bar-striped progress-bar-animated" 
                                 role="progressbar" style="width: 100%"></div>
                        </div>
                        
                        <form method="post">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-arrow-right"></i> Criar Estrutura
                            </button>
                        </form>

                    <?php elseif ($step == 4): ?>
                        <h5><i class="fas fa-user-shield"></i> Usuário Administrador</h5>
                        <p>Crie o usuário administrador do sistema.</p>
                        
                        <form method="post">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="admin_name" class="form-label">Nome Completo</label>
                                    <input type="text" class="form-control" id="admin_name" name="admin_config[name]" 
                                           value="Administrador" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="admin_email" class="form-label">E-mail</label>
                                    <input type="email" class="form-control" id="admin_email" name="admin_config[email]" 
                                           value="admin@sacsweb.com" required>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="admin_login" class="form-label">Login</label>
                                    <input type="text" class="form-control" id="admin_login" name="admin_config[login]" 
                                           value="admin" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="admin_password" class="form-label">Senha</label>
                                    <input type="password" class="form-control" id="admin_password" name="admin_config[password]" 
                                           value="admin123" required>
                                </div>
                            </div>
                            <div class="text-center">
                                <button type="submit" class="btn btn-success">
                                    <i class="fas fa-check"></i> Finalizar Instalação
                                </button>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php
/**
 * Funções de instalação
 */

function checkRequirements() {
    $requirements = [
        'php_ok' => version_compare(PHP_VERSION, '7.4.0', '>='),
        'php_version' => PHP_VERSION,
        'pdo_ok' => extension_loaded('pdo'),
        'mysql_ok' => extension_loaded('pdo_mysql'),
        'write_ok' => is_writable('.')
    ];
    
    $requirements['all_ok'] = $requirements['php_ok'] && $requirements['pdo_ok'] && 
                              $requirements['mysql_ok'] && $requirements['write_ok'];
    
    return $requirements;
}

function testDatabaseConnection($config) {
    try {
        $dsn = "mysql:host={$config['host']};port={$config['port']};charset={$config['charset']}";
        $pdo = new PDO($dsn, $config['user'], $config['pass']);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return true;
    } catch (PDOException $e) {
        return false;
    }
}

function saveDatabaseConfig($config) {
    $config_content = "<?php\n";
    $config_content .= "// Configuração do Banco de Dados SACSWeb\n";
    $config_content .= "// Gerado automaticamente pelo instalador\n\n";
    $config_content .= "define('DB_HOST', '{$config['host']}');\n";
    $config_content .= "define('DB_NAME', '{$config['name']}');\n";
    $config_content .= "define('DB_USER', '{$config['user']}');\n";
    $config_content .= "define('DB_PASS', '{$config['pass']}');\n";
    $config_content .= "define('DB_CHARSET', '{$config['charset']}');\n";
    
    return file_put_contents('config/database.php', $config_content);
}

function createDatabase() {
    try {
        // Incluir configuração do banco
        require_once 'config/database.php';
        require_once 'includes/functions.php';
        
        // Conectar ao banco
        $dsn = "mysql:host=" . DB_HOST . ";port=3306;charset=" . DB_CHARSET;
        $pdo = new PDO($dsn, DB_USER, DB_PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Criar banco de dados
        $pdo->exec("CREATE DATABASE IF NOT EXISTS " . DB_NAME . " CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $pdo->exec("USE " . DB_NAME);
        
        // Executar script SQL
        $sql = file_get_contents('database/sacsweb.sql');
        $pdo->exec($sql);
        
        return true;
    } catch (Exception $e) {
        return false;
    }
}

function createAdminUser($config) {
    try {
        require_once 'config/database.php';
        require_once 'includes/functions.php';
        
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $pdo = new PDO($dsn, DB_USER, DB_PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Criar usuário administrador
        $stmt = $pdo->prepare("
            INSERT INTO usuarios (login, senha, nome, email, nivel, avatar) 
            VALUES (:login, :senha, :nome, :email, 'admin', :avatar)
        ");
        
        $senha_hash = password_hash($config['password'], PASSWORD_DEFAULT);
        $avatar = gerarAvatar($config['name']);
        
        return $stmt->execute([
            'login' => $config['login'],
            'senha' => $senha_hash,
            'nome' => $config['name'],
            'email' => $config['email'],
            'avatar' => $avatar
        ]);
    } catch (Exception $e) {
        return false;
    }
}
?> 