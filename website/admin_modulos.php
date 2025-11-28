<?php
/**
 * SACSWeb Educacional - Dashboard Administrativo de Módulos
 * Sistema para Ensino de Ataques Cibernéticos e Proteções
 * 
 * @package SACSWeb
 * @version 2.0.0
 */

require_once '../config/config.php';

// Verificar se usuário está logado e é admin
requireLogin();

if (!isAdmin()) {
    showError('Acesso negado. Apenas administradores podem acessar esta página.');
    redirect('/sacsweb/website/dashboard.php');
}

// Obter dados do usuário atual
$user = getCurrentUser();
$userPreferences = getUserPreferences($user['id'] ?? null);

// Obter módulos do banco de dados
try {
    $pdo = connectDatabase();
    
    // Buscar todos os módulos
    $stmt = $pdo->query("
        SELECT m.*, 
               COUNT(DISTINCT p.id) as total_progresso,
               COUNT(DISTINCT e.id) as total_exercicios
        FROM modulos m
        LEFT JOIN progresso_usuario p ON m.id = p.modulo_id
        LEFT JOIN exercicios e ON m.id = e.modulo_id
        GROUP BY m.id
        ORDER BY m.ordem ASC, m.data_criacao DESC
    ");
    $modulos = $stmt->fetchAll();
    
    // Estatísticas
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM modulos");
    $totalModulos = $stmt->fetch()['total'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM modulos WHERE ativo = 1");
    $modulosAtivos = $stmt->fetch()['total'];
    
} catch (PDOException $e) {
    logMessage('Erro ao obter módulos: ' . $e->getMessage(), 'error');
    $modulos = [];
    $totalModulos = 0;
    $modulosAtivos = 0;
}

$messages = getFlashMessages();
$csrfToken = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Módulos - SACSWeb Admin</title>
    <link rel="icon" type="image/png" href="<?= ASSETS_URL ?>/images/icone.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="<?= ASSETS_URL ?>/css/sacsweb-unified.css" rel="stylesheet">
    <script>
        window.SACSWEB_PREFERENCES = <?= json_encode($userPreferences, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
        window.CSRF_TOKEN = <?= json_encode($csrfToken, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    </script>
    <script src="<?= ASSETS_URL ?>/js/preferences.js" defer></script>
    <style>
        .admin-table {
            background: var(--dark-gray);
            border-radius: var(--border-radius-base);
        }
        .admin-table thead {
            background: var(--light-gray);
        }
        .admin-table tbody tr {
            border-bottom: 1px solid var(--light-gray);
        }
        .admin-table tbody tr:hover {
            background: var(--light-gray);
        }
        .form-section {
            background: var(--dark-gray);
            border-radius: var(--border-radius-base);
            padding: 1.5rem;
            margin-bottom: 2rem;
        }
        .badge-status {
            font-size: 0.85rem;
            padding: 0.4rem 0.8rem;
        }
    </style>
</head>
<body class="page-dashboard">
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg bg-glass navbar-light shadow-sm">
        <div class="container">
            <a class="navbar-brand fw-bold text-primary" href="dashboard.php">
                <img src="<?= ASSETS_URL ?>/images/icone.png" alt="SACSWeb Logo" style="height: 40px; margin-right: 10px;"> <span class="text-primary">SACSWeb</span> <span>Admin</span>
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard.php">
                            <i class="fas fa-home"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="admin_modulos.php">
                            <i class="fas fa-cog"></i> Gerenciar Módulos
                        </a>
                    </li>
                </ul>
                
                <div class="navbar-nav">
                    <div class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user-circle"></i> <?= htmlspecialchars($user['nome']) ?>
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="dashboard.php">
                                <i class="fas fa-arrow-left"></i> Voltar ao Dashboard
                            </a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="logout.php">
                                <i class="fas fa-sign-out-alt"></i> Sair
                            </a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <!-- Header -->
        <div class="bg-glass rounded-4 p-4 mb-4">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1 class="display-5 fw-bold mb-2">
                        <i class="fas fa-book text-primary"></i> Gerenciar Módulos Educacionais
                    </h1>
                    <p class="lead mb-0">Crie, edite e gerencie módulos de aprendizado</p>
                </div>
                <div class="col-md-4 text-end">
                    <div class="row text-center">
                        <div class="col-6">
                            <div class="h3 mb-0"><?= $totalModulos ?></div>
                            <small>Total</small>
                        </div>
                        <div class="col-6">
                            <div class="h3 mb-0 text-success"><?= $modulosAtivos ?></div>
                            <small>Ativos</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Mensagens -->
        <?php if (isset($messages['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <i class="fas fa-check-circle"></i> <?= htmlspecialchars($messages['success']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($messages['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="fas fa-exclamation-triangle"></i> <?= htmlspecialchars($messages['error']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div id="alertContainer"></div>

        <!-- Formulário de Criação/Edição -->
        <div class="form-section" id="formSection" style="display: none;">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h3 id="formTitle">Criar Novo Módulo</h3>
                <button type="button" class="btn btn-secondary btn-sm" onclick="fecharFormulario()">
                    <i class="fas fa-times"></i> Fechar
                </button>
            </div>
            
            <form id="moduloForm" onsubmit="salvarModulo(event)">
                <input type="hidden" id="moduloId" name="id" value="">
                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                
                <div class="row">
                    <div class="col-md-8 mb-3">
                        <label for="titulo" class="form-label">Título do Módulo <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="titulo" name="titulo" required maxlength="200">
                    </div>
                    
                    <div class="col-md-4 mb-3">
                        <label for="nivel" class="form-label">Nível <span class="text-danger">*</span></label>
                        <select class="form-select" id="nivel" name="nivel" required>
                            <option value="iniciante">Iniciante</option>
                            <option value="intermediario">Intermediário</option>
                            <option value="avancado">Avançado</option>
                        </select>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label for="descricao" class="form-label">Descrição <span class="text-danger">*</span></label>
                    <textarea class="form-control" id="descricao" name="descricao" rows="3" required></textarea>
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="tipo_ataque" class="form-label">Tipo de Ataque</label>
                        <input type="text" class="form-control" id="tipo_ataque" name="tipo_ataque" maxlength="100" placeholder="Ex: XSS, SQL Injection, CSRF">
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label for="linguagem_codigo" class="form-label">Linguagem do Código</label>
                        <select class="form-select" id="linguagem_codigo" name="linguagem_codigo">
                            <option value="php">PHP</option>
                            <option value="javascript">JavaScript</option>
                            <option value="html">HTML</option>
                            <option value="sql">SQL</option>
                            <option value="python">Python</option>
                            <option value="java">Java</option>
                            <option value="http">HTTP</option>
                        </select>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label for="conteudo_teorico" class="form-label">Conteúdo Teórico <span class="text-danger">*</span></label>
                    <textarea class="form-control" id="conteudo_teorico" name="conteudo_teorico" rows="8" required></textarea>
                    <small class="form-text">Conteúdo educacional principal do módulo</small>
                </div>
                
                <div class="mb-3">
                    <label for="exemplo_pratico" class="form-label">Exemplo Prático</label>
                    <textarea class="form-control" id="exemplo_pratico" name="exemplo_pratico" rows="4"></textarea>
                    <small class="form-text">Exemplos práticos e casos de uso</small>
                </div>
                
                <div class="mb-3">
                    <label for="demonstracao_codigo" class="form-label">Demonstração de Código</label>
                    <textarea class="form-control" id="demonstracao_codigo" name="demonstracao_codigo" rows="6" style="font-family: 'Courier New', monospace;"></textarea>
                    <small class="form-text">Código de exemplo ou demonstração</small>
                </div>
                
                <div class="row">
                    <div class="col-md-3 mb-3">
                        <label for="tempo_estimado" class="form-label">Tempo Estimado (minutos)</label>
                        <input type="number" class="form-control" id="tempo_estimado" name="tempo_estimado" min="1" max="600" value="30">
                    </div>
                    
                    <div class="col-md-3 mb-3">
                        <label for="pontos_maximos" class="form-label">Pontos Máximos</label>
                        <input type="number" class="form-control" id="pontos_maximos" name="pontos_maximos" min="1" max="1000" value="100">
                    </div>
                    
                    <div class="col-md-3 mb-3">
                        <label for="ordem" class="form-label">Ordem</label>
                        <input type="number" class="form-control" id="ordem" name="ordem" min="0" value="0">
                        <small class="form-text">Ordem de exibição (menor = primeiro)</small>
                    </div>
                    
                    <div class="col-md-3 mb-3">
                        <div class="form-check mt-4">
                            <input class="form-check-input" type="checkbox" id="ativo" name="ativo" checked>
                            <label class="form-check-label" for="ativo">
                                Módulo Ativo
                            </label>
                        </div>
                    </div>
                </div>
                
                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                    <button type="button" class="btn btn-secondary" onclick="fecharFormulario()">Cancelar</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Salvar Módulo
                    </button>
                </div>
            </form>
        </div>

        <!-- Lista de Módulos -->
        <div class="card bg-glass border-0 shadow">
            <div class="card-header bg-transparent border-0 d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="fas fa-list text-primary"></i> Módulos Cadastrados
                </h5>
                <button type="button" class="btn btn-primary" onclick="abrirFormulario()">
                    <i class="fas fa-plus"></i> Novo Módulo
                </button>
            </div>
            <div class="card-body p-0">
                <?php if (empty($modulos)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-book fa-3x mb-3"></i>
                        <h6>Nenhum módulo cadastrado</h6>
                        <p>Clique em "Novo Módulo" para começar</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table admin-table mb-0">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Título</th>
                                    <th>Nível</th>
                                    <th>Tipo</th>
                                    <th>Ordem</th>
                                    <th>Status</th>
                                    <th>Progresso</th>
                                    <th>Exercícios</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($modulos as $modulo): ?>
                                    <tr>
                                        <td><?= $modulo['id'] ?></td>
                                        <td>
                                            <strong><?= htmlspecialchars($modulo['titulo']) ?></strong>
                                            <br>
                                            <small class="text-muted"><?= htmlspecialchars(substr($modulo['descricao'], 0, 60)) ?>...</small>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?= $modulo['nivel'] === 'iniciante' ? 'success' : ($modulo['nivel'] === 'intermediario' ? 'warning' : 'danger') ?>">
                                                <?= ucfirst($modulo['nivel']) ?>
                                            </span>
                                        </td>
                                        <td><?= htmlspecialchars($modulo['tipo_ataque'] ?: 'Geral') ?></td>
                                        <td><?= $modulo['ordem'] ?></td>
                                        <td>
                                            <?php if ($modulo['ativo']): ?>
                                                <span class="badge bg-success badge-status">Ativo</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary badge-status">Inativo</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <small><?= $modulo['total_progresso'] ?> usuários</small>
                                        </td>
                                        <td>
                                            <small><?= $modulo['total_exercicios'] ?> exercícios</small>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm" role="group">
                                                <button type="button" class="btn btn-outline-primary" onclick="editarModulo(<?= $modulo['id'] ?>)" title="Editar">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button type="button" class="btn btn-outline-danger" onclick="confirmarDeletar(<?= $modulo['id'] ?>, '<?= htmlspecialchars(addslashes($modulo['titulo'])) ?>')" title="Deletar">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Modal de Confirmação -->
    <div class="modal fade" id="confirmModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Confirmar Exclusão</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Tem certeza que deseja deletar o módulo <strong id="moduloNomeConfirm"></strong>?</p>
                    <p class="text-danger small">
                        <i class="fas fa-exclamation-triangle"></i> 
                        Se houver progresso de usuários associado, o módulo será apenas desativado.
                    </p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-danger" id="confirmDeleteBtn">
                        <i class="fas fa-trash"></i> Deletar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Variáveis globais
        let moduloEditando = null;
        const confirmModal = new bootstrap.Modal(document.getElementById('confirmModal'));
        let moduloParaDeletar = null;

        // Abrir formulário para criar novo módulo
        function abrirFormulario() {
            moduloEditando = null;
            document.getElementById('formTitle').textContent = 'Criar Novo Módulo';
            document.getElementById('moduloForm').reset();
            document.getElementById('moduloId').value = '';
            document.getElementById('ativo').checked = true;
            document.getElementById('formSection').style.display = 'block';
            document.getElementById('formSection').scrollIntoView({ behavior: 'smooth' });
        }

        // Fechar formulário
        function fecharFormulario() {
            document.getElementById('formSection').style.display = 'none';
            moduloEditando = null;
        }

        // Editar módulo
        async function editarModulo(id) {
            try {
                const response = await fetch(`admin_modulos_action.php?action=get&id=${id}`);
                const data = await response.json();
                
                if (data.sucesso && data.dados) {
                    const modulo = data.dados;
                    moduloEditando = id;
                    
                    // Preencher formulário
                    document.getElementById('formTitle').textContent = 'Editar Módulo';
                    document.getElementById('moduloId').value = modulo.id;
                    document.getElementById('titulo').value = modulo.titulo;
                    document.getElementById('descricao').value = modulo.descricao;
                    document.getElementById('nivel').value = modulo.nivel;
                    document.getElementById('tipo_ataque').value = modulo.tipo_ataque || '';
                    document.getElementById('conteudo_teorico').value = modulo.conteudo_teorico;
                    document.getElementById('exemplo_pratico').value = modulo.exemplo_pratico || '';
                    document.getElementById('demonstracao_codigo').value = modulo.demonstracao_codigo || '';
                    document.getElementById('linguagem_codigo').value = modulo.linguagem_codigo || 'php';
                    document.getElementById('tempo_estimado').value = modulo.tempo_estimado || 30;
                    document.getElementById('pontos_maximos').value = modulo.pontos_maximos || 100;
                    document.getElementById('ordem').value = modulo.ordem || 0;
                    document.getElementById('ativo').checked = modulo.ativo == 1;
                    
                    // Mostrar formulário
                    document.getElementById('formSection').style.display = 'block';
                    document.getElementById('formSection').scrollIntoView({ behavior: 'smooth' });
                } else {
                    mostrarAlerta('Erro ao carregar módulo: ' + (data.mensagem || 'Erro desconhecido'), 'danger');
                }
            } catch (error) {
                mostrarAlerta('Erro ao carregar módulo: ' + error.message, 'danger');
            }
        }

        // Salvar módulo (criar ou atualizar)
        async function salvarModulo(event) {
            event.preventDefault();
            
            const formData = new FormData(event.target);
            const action = moduloEditando ? 'update' : 'create';
            
            formData.append('action', action);
            
            try {
                const response = await fetch('admin_modulos_action.php?action=' + action, {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.sucesso) {
                    mostrarAlerta(data.mensagem, 'success');
                    fecharFormulario();
                    // Recarregar página após 1 segundo
                    setTimeout(() => {
                        window.location.reload();
                    }, 1000);
                } else {
                    mostrarAlerta(data.mensagem || 'Erro ao salvar módulo', 'danger');
                }
            } catch (error) {
                mostrarAlerta('Erro ao salvar módulo: ' + error.message, 'danger');
            }
        }

        // Confirmar deletar
        function confirmarDeletar(id, titulo) {
            moduloParaDeletar = id;
            document.getElementById('moduloNomeConfirm').textContent = titulo;
            confirmModal.show();
        }

        // Deletar módulo
        document.getElementById('confirmDeleteBtn').addEventListener('click', async function() {
            if (!moduloParaDeletar) return;
            
            const formData = new FormData();
            formData.append('action', 'delete');
            formData.append('id', moduloParaDeletar);
            formData.append('csrf_token', window.CSRF_TOKEN);
            
            try {
                const response = await fetch('admin_modulos_action.php?action=delete', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.sucesso) {
                    mostrarAlerta(data.mensagem, 'success');
                    confirmModal.hide();
                    // Recarregar página após 1 segundo
                    setTimeout(() => {
                        window.location.reload();
                    }, 1000);
                } else {
                    mostrarAlerta(data.mensagem || 'Erro ao deletar módulo', 'danger');
                    confirmModal.hide();
                }
            } catch (error) {
                mostrarAlerta('Erro ao deletar módulo: ' + error.message, 'danger');
                confirmModal.hide();
            }
            
            moduloParaDeletar = null;
        });

        // Mostrar alerta
        function mostrarAlerta(mensagem, tipo) {
            const alertContainer = document.getElementById('alertContainer');
            const alert = document.createElement('div');
            alert.className = `alert alert-${tipo} alert-dismissible fade show`;
            alert.innerHTML = `
                ${mensagem}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            alertContainer.appendChild(alert);
            
            // Remover após 5 segundos
            setTimeout(() => {
                alert.remove();
            }, 5000);
        }
    </script>
</body>
</html>

