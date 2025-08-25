<?php
/**
 * SACSWeb Educacional - Setup do Banco de Dados
 * Script para configurar o banco de dados automaticamente
 */

// Configurações do banco
$host = 'localhost';
$username = 'root';
$password = '';
$database = 'sacsweb_educacional';

// Função para executar scripts SQL
function executeSQLScript($host, $username, $password, $database, $scriptFile) {
    try {
        // Conectar ao MySQL sem selecionar banco específico
        $pdo = new PDO("mysql:host=$host", $username, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);
        
        // Ler o arquivo SQL
        if (!file_exists($scriptFile)) {
            throw new Exception("Arquivo SQL não encontrado: $scriptFile");
        }
        
        $sql = file_get_contents($scriptFile);
        if ($sql === false) {
            throw new Exception("Erro ao ler arquivo SQL: $scriptFile");
        }
        
        // Dividir o SQL em comandos individuais
        $commands = array_filter(array_map('trim', explode(';', $sql)));
        
        $successCount = 0;
        $errorCount = 0;
        
        foreach ($commands as $command) {
            $command = trim($command);
            if (empty($command) || strpos($command, '--') === 0) {
                continue; // Pular comentários e linhas vazias
            }
            
            try {
                $stmt = $pdo->prepare($command);
                $stmt->execute();
                $stmt->closeCursor();
                $successCount++;
                
                // Pequena pausa para evitar sobrecarga
                usleep(10000); // 10ms
                
            } catch (PDOException $e) {
                $errorCount++;
                echo "<div class='alert alert-danger'>Erro no comando: " . htmlspecialchars(substr($command, 0, 100)) . "...<br>";
                echo "Erro: " . htmlspecialchars($e->getMessage()) . "</div>";
            }
        }
        
        return [
            'success' => $successCount,
            'errors' => $errorCount,
            'total' => count($commands)
        ];
        
    } catch (Exception $e) {
        throw new Exception("Erro na execução: " . $e->getMessage());
    }
}

// Processar formulário
$message = '';
$messageType = '';
$setupResults = null;

if ($_POST && isset($_POST['setup_database'])) {
    try {
        echo "<div class='alert alert-info'>Iniciando configuração do banco de dados...</div>";
        
        // Passo 1: Executar setup_base.sql
        echo "<div class='alert alert-primary'>Passo 1: Criando estrutura base do banco...</div>";
        $baseResults = executeSQLScript($host, $username, $password, '', 'database/setup_base.sql');
        
        if ($baseResults['errors'] > 0) {
            throw new Exception("Erros encontrados na criação da estrutura base: {$baseResults['errors']} erros");
        }
        
        echo "<div class='alert alert-success'>✓ Estrutura base criada com sucesso! ({$baseResults['success']} comandos executados)</div>";
        
        // Passo 2: Executar setup_modulos.sql
        echo "<div class='alert alert-primary'>Passo 2: Inserindo módulos educacionais...</div>";
        $modulosResults = executeSQLScript($host, $username, $password, $database, 'database/setup_modulos.sql');
        
        if ($modulosResults['errors'] > 0) {
            throw new Exception("Erros encontrados na inserção dos módulos: {$modulosResults['errors']} erros");
        }
        
        echo "<div class='alert alert-success'>✓ Módulos educacionais inseridos com sucesso! ({$modulosResults['success']} comandos executados)</div>";
        
        $setupResults = [
            'base' => $baseResults,
            'modulos' => $modulosResults,
            'total_success' => $baseResults['success'] + $modulosResults['success'],
            'total_errors' => $baseResults['errors'] + $modulosResults['errors']
        ];
        
        $message = "Banco de dados configurado com sucesso!";
        $messageType = "success";
        
    } catch (Exception $e) {
        $message = "Erro durante a configuração: " . $e->getMessage();
        $messageType = "danger";
    }
}

// Verificar se o banco já existe
$databaseExists = false;
$tableCount = 0;

try {
    $pdo = new PDO("mysql:host=$host", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Verificar se o banco existe
    $stmt = $pdo->query("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = '$database'");
    $databaseExists = $stmt->fetch();
    
    if ($databaseExists) {
        // Verificar tabelas
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = '$database'");
        $tableCount = $stmt->fetch()['count'];
    }
    
} catch (PDOException $e) {
    $databaseExists = false;
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setup Banco de Dados - SACSWeb Educacional</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        .setup-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
        }
        .status-card {
            transition: transform 0.3s ease;
        }
        .status-card:hover {
            transform: translateY(-5px);
        }
    </style>
</head>
<body>
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="setup-container p-5">
                    <!-- Header -->
                    <div class="text-center mb-5">
                        <h1 class="display-4 fw-bold text-primary mb-3">
                            <i class="fas fa-database"></i> SACSWeb Educacional
                        </h1>
                        <p class="lead text-muted">Configuração Automática do Banco de Dados</p>
                        <hr class="my-4">
                    </div>

                    <!-- Status do Banco -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="card status-card h-100 border-0 shadow-sm">
                                <div class="card-body text-center">
                                    <i class="fas fa-database fa-3x text-<?php echo $databaseExists ? 'success' : 'warning'; ?> mb-3"></i>
                                    <h5 class="card-title">Status do Banco</h5>
                                    <p class="card-text">
                                        <?php if ($databaseExists): ?>
                                            <span class="badge bg-success">Banco Existe</span><br>
                                            <small class="text-muted"><?php echo $tableCount; ?> tabelas encontradas</small>
                                        <?php else: ?>
                                            <span class="badge bg-warning">Banco Não Existe</span><br>
                                            <small class="text-muted">Necessário configuração</small>
                                        <?php endif; ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="card status-card h-100 border-0 shadow-sm">
                                <div class="card-body text-center">
                                    <i class="fas fa-server fa-3x text-info mb-3"></i>
                                    <h5 class="card-title">Configuração</h5>
                                    <p class="card-text">
                                        <strong>Host:</strong> <?php echo htmlspecialchars($host); ?><br>
                                        <strong>Usuário:</strong> <?php echo htmlspecialchars($username); ?><br>
                                        <strong>Banco:</strong> <?php echo htmlspecialchars($database); ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Mensagens -->
                    <?php if ($message): ?>
                        <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
                            <i class="fas fa-<?php echo $messageType === 'success' ? 'check-circle' : 'exclamation-triangle'; ?>"></i>
                            <?php echo htmlspecialchars($message); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <!-- Resultados da Configuração -->
                    <?php if ($setupResults): ?>
                        <div class="card border-success mb-4">
                            <div class="card-header bg-success text-white">
                                <h5 class="mb-0"><i class="fas fa-check-circle"></i> Configuração Concluída</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <h6>Estrutura Base:</h6>
                                        <ul class="list-unstyled">
                                            <li><i class="fas fa-check text-success"></i> <?php echo $setupResults['base']['success']; ?> comandos executados</li>
                                            <li><i class="fas fa-times text-danger"></i> <?php echo $setupResults['base']['errors']; ?> erros</li>
                                        </ul>
                                    </div>
                                    <div class="col-md-6">
                                        <h6>Módulos Educacionais:</h6>
                                        <ul class="list-unstyled">
                                            <li><i class="fas fa-check text-success"></i> <?php echo $setupResults['modulos']['success']; ?> comandos executados</li>
                                            <li><i class="fas fa-times text-danger"></i> <?php echo $setupResults['modulos']['errors']; ?> erros</li>
                                        </ul>
                                    </div>
                                </div>
                                <hr>
                                <div class="text-center">
                                    <strong>Total: <?php echo $setupResults['total_success']; ?> comandos executados com sucesso</strong>
                                    <?php if ($setupResults['total_errors'] > 0): ?>
                                        <br><span class="text-danger"><?php echo $setupResults['total_errors']; ?> erros encontrados</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Formulário de Setup -->
                    <div class="card border-primary">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="fas fa-cogs"></i> Configurar Banco de Dados</h5>
                        </div>
                        <div class="card-body">
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle"></i>
                                <strong>Importante:</strong> Este processo irá:
                                <ul class="mb-0 mt-2">
                                                                <li>Criar o banco de dados <code>sacsweb_educacional</code></li>
                            <li>Criar todas as tabelas necessárias</li>
                            <li>Inserir usuário admin padrão (admin@sacsweb.com / admin123)</li>
                            <li>Inserir 6 módulos iniciantes com exercícios</li>
                                </ul>
                            </div>
                            
                            <form method="POST" class="text-center">
                                <button type="submit" name="setup_database" class="btn btn-primary btn-lg" 
                                        onclick="return confirm('Tem certeza que deseja configurar o banco de dados?')">
                                    <i class="fas fa-play"></i> Iniciar Configuração
                                </button>
                            </form>
                        </div>
                    </div>

                    <!-- Próximos Passos -->
                    <div class="card border-info mt-4">
                        <div class="card-header bg-info text-white">
                            <h5 class="mb-0"><i class="fas fa-arrow-right"></i> Próximos Passos</h5>
                        </div>
                        <div class="card-body">
                            <ol class="mb-0">
                                <li>Execute a configuração do banco de dados</li>
                                <li>Acesse o sistema em <code>index.php</code></li>
                                <li>Faça login com: <code>admin@sacsweb.com</code> / <code>admin123</code></li>
                                <li>Explore os módulos educacionais e exercícios</li>
                            </ol>
                        </div>
                    </div>

                    <!-- Links Úteis -->
                    <div class="text-center mt-4">
                        <a href="index.php" class="btn btn-outline-primary me-2">
                            <i class="fas fa-home"></i> Ir para o Sistema
                        </a>
                        <a href="README.md" class="btn btn-outline-secondary" target="_blank">
                            <i class="fas fa-book"></i> Documentação
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
