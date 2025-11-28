<?php
/**
 * SACSWeb Educacional - Página de Perfil
 * Permite que usuários editem seu perfil e façam upload de foto
 * Versão: 2.1.0
 */

require_once '../config/config.php';
requireLogin();

$user = getCurrentUser();
$userPreferences = getUserPreferences($user['id'] ?? null);
$pdo = connectDatabase();
$messages = getFlashMessages();

// Buscar dados completos do usuário
try {
    $stmt = $pdo->prepare("SELECT id, nome, email, username, nivel_conhecimento, foto_perfil, data_cadastro FROM usuarios WHERE id = ?");
    $stmt->execute([$user['id']]);
    $userData = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$userData) {
        showError('Usuário não encontrado');
        redirect('dashboard.php');
    }
} catch (PDOException $e) {
    logMessage('Erro ao buscar dados do usuário: ' . $e->getMessage(), 'error');
    showError('Erro ao carregar dados do perfil');
    redirect('dashboard.php');
}

// Processar atualização de perfil
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['atualizar_perfil'])) {
    // Validar CSRF token
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        showError('Token de segurança inválido. Por favor, recarregue a página.');
        redirect('perfil.php');
    }
    
    try {
        // Sanitizar dados
        $nome = trim($_POST['nome'] ?? '');
        $email = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
        $nivel_conhecimento = in_array($_POST['nivel_conhecimento'] ?? 'iniciante', ['iniciante', 'intermediario', 'avancado']) 
            ? $_POST['nivel_conhecimento'] : 'iniciante';
        
        // Validar dados
        if (empty($nome) || strlen($nome) < 3) {
            showError('Nome deve ter pelo menos 3 caracteres');
            redirect('perfil.php');
        }
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            showError('Email inválido');
            redirect('perfil.php');
        }
        
        // Verificar se email já está em uso por outro usuário
        $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = ? AND id != ?");
        $stmt->execute([$email, $user['id']]);
        if ($stmt->fetch()) {
            showError('Este email já está em uso por outro usuário');
            redirect('perfil.php');
        }
        
        // Atualizar dados do usuário
        $stmt = $pdo->prepare("
            UPDATE usuarios 
            SET nome = ?, email = ?, nivel_conhecimento = ?
            WHERE id = ?
        ");
        $stmt->execute([$nome, $email, $nivel_conhecimento, $user['id']]);
        
        showSuccess('Perfil atualizado com sucesso!');
        logMessage("Perfil atualizado para usuário ID: " . $user['id'], 'info');
        
        // Atualizar dados locais
        $userData['nome'] = $nome;
        $userData['email'] = $email;
        $userData['nivel_conhecimento'] = $nivel_conhecimento;
        
    } catch (PDOException $e) {
        logMessage('Erro ao atualizar perfil: ' . $e->getMessage(), 'error');
        showError('Erro ao atualizar perfil. Tente novamente.');
    }
    
    redirect('perfil.php');
}

// Processar upload de foto
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_foto'])) {
    // Validar CSRF token
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        showError('Token de segurança inválido. Por favor, recarregue a página.');
        redirect('perfil.php');
    }
    
    if (isset($_FILES['foto_perfil']) && $_FILES['foto_perfil']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['foto_perfil'];
        
        // Validar tipo de arquivo
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        if (!in_array($mimeType, $allowedTypes)) {
            showError('Tipo de arquivo não permitido. Use JPG, PNG, GIF ou WEBP.');
            redirect('perfil.php');
        }
        
        // Validar tamanho (máximo 5MB)
        if ($file['size'] > 5242880) {
            showError('Arquivo muito grande. Tamanho máximo: 5MB');
            redirect('perfil.php');
        }
        
        // Criar diretório de uploads se não existir
        $uploadDir = dirname(__DIR__) . '/uploads/perfis/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        // Gerar nome único para o arquivo
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $fileName = 'perfil_' . $user['id'] . '_' . time() . '.' . $extension;
        $filePath = $uploadDir . $fileName;
        $relativePath = 'uploads/perfis/' . $fileName;
        
        // Mover arquivo
        if (move_uploaded_file($file['tmp_name'], $filePath)) {
            // Remover foto antiga se existir
            if (!empty($userData['foto_perfil']) && file_exists(dirname(__DIR__) . '/' . $userData['foto_perfil'])) {
                @unlink(dirname(__DIR__) . '/' . $userData['foto_perfil']);
            }
            
            // Atualizar no banco de dados
            $stmt = $pdo->prepare("UPDATE usuarios SET foto_perfil = ? WHERE id = ?");
            $stmt->execute([$relativePath, $user['id']]);
            
            $userData['foto_perfil'] = $relativePath;
            showSuccess('Foto de perfil atualizada com sucesso!');
            logMessage("Foto de perfil atualizada para usuário ID: " . $user['id'], 'info');
        } else {
            showError('Erro ao fazer upload da foto. Tente novamente.');
        }
    } else {
        showError('Erro ao fazer upload. Verifique o arquivo e tente novamente.');
    }
    
    redirect('perfil.php');
}

// Processar remoção de foto
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remover_foto'])) {
    // Validar CSRF token
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        showError('Token de segurança inválido.');
        redirect('perfil.php');
    }
    
    try {
        // Remover arquivo físico
        if (!empty($userData['foto_perfil']) && file_exists(dirname(__DIR__) . '/' . $userData['foto_perfil'])) {
            @unlink(dirname(__DIR__) . '/' . $userData['foto_perfil']);
        }
        
        // Atualizar no banco
        $stmt = $pdo->prepare("UPDATE usuarios SET foto_perfil = NULL WHERE id = ?");
        $stmt->execute([$user['id']]);
        
        $userData['foto_perfil'] = null;
        showSuccess('Foto de perfil removida com sucesso!');
    } catch (PDOException $e) {
        logMessage('Erro ao remover foto: ' . $e->getMessage(), 'error');
        showError('Erro ao remover foto.');
    }
    
    redirect('perfil.php');
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meu Perfil - <?= SISTEMA_NOME ?></title>
    <link rel="icon" type="image/png" href="<?= ASSETS_URL ?>/images/icone.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="<?= ASSETS_URL ?>/css/sacsweb-unified.css" rel="stylesheet">
    <script>
        window.SACSWEB_PREFERENCES = <?= json_encode($userPreferences, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    </script>
    <script src="<?= ASSETS_URL ?>/js/preferences.js" defer></script>
    
    <style>
        .profile-header {
            background: linear-gradient(135deg, var(--primary-pink), var(--accent-blue));
            border-radius: 15px;
            padding: 40px;
            text-align: center;
            color: white;
            margin-bottom: 30px;
        }
        
        .profile-picture {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            border: 5px solid white;
            box-shadow: 0 5px 15px rgba(0,0,0,0.3);
            margin-bottom: 20px;
        }
        
        .profile-picture-placeholder {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            background: rgba(255,255,255,0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            border: 5px solid white;
            font-size: 4rem;
        }
        
        .profile-section {
            background: var(--dark-gray);
            border: 1px solid var(--light-gray);
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 20px;
        }
        
        .profile-section h5 {
            color: var(--primary-pink);
            margin-bottom: 20px;
            font-weight: 600;
        }
        
        .upload-area {
            border: 2px dashed var(--light-gray);
            border-radius: 10px;
            padding: 30px;
            text-align: center;
            transition: all 0.3s ease;
            cursor: pointer;
        }
        
        .upload-area:hover {
            border-color: var(--primary-pink);
            background: var(--light-gray);
        }
        
        .upload-area.dragover {
            border-color: var(--primary-pink);
            background: var(--light-gray);
        }
    </style>
</head>
<body class="page-perfil">
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg bg-glass navbar-light shadow-sm">
        <div class="container">
            <a class="navbar-brand fw-bold text-primary" href="dashboard.php">
                <img src="<?= ASSETS_URL ?>/images/icone.png" alt="SACSWeb Logo" style="height: 40px; margin-right: 10px;"> <span class="text-primary">SACSWeb</span> <span>Educacional</span>
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
                        <a class="nav-link" href="modulos.php">
                            <i class="fas fa-book"></i> Módulos
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="exercicios.php">
                            <i class="fas fa-tasks"></i> Exercícios
                        </a>
                    </li>
                    <li class="nav-item">
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="ranking.php">
                            <i class="fas fa-trophy"></i> Ranking
                        </a>
                    </li>
                </ul>
                
                <div class="navbar-nav">
                    <div class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <?php if (!empty($userData['foto_perfil'])): ?>
                                <img src="../<?= htmlspecialchars($userData['foto_perfil']) ?>" alt="Foto" style="width: 30px; height: 30px; border-radius: 50%; margin-right: 5px;">
                            <?php else: ?>
                                <i class="fas fa-user-circle"></i>
                            <?php endif; ?>
                            <?= htmlspecialchars($userData['nome']) ?>
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item active" href="perfil.php">
                                <i class="fas fa-user"></i> Meu Perfil
                            </a></li>
                            <li><a class="dropdown-item" href="configuracoes.php">
                                <i class="fas fa-cog"></i> Configurações
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

    <div class="container mt-4 mb-5">
        <!-- Header do Perfil -->
        <div class="profile-header">
            <?php if (!empty($userData['foto_perfil'])): ?>
                <img src="../<?= htmlspecialchars($userData['foto_perfil']) ?>" alt="Foto de Perfil" class="profile-picture">
            <?php else: ?>
                <div class="profile-picture-placeholder">
                    <i class="fas fa-user"></i>
                </div>
            <?php endif; ?>
            <h2 class="mb-2"><?= htmlspecialchars($userData['nome']) ?></h2>
            <p class="mb-0 opacity-75">
                <i class="fas fa-graduation-cap"></i> Nível: <?= ucfirst($userData['nivel_conhecimento']) ?>
            </p>
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

        <div class="row">
            <!-- Foto de Perfil -->
            <div class="col-md-4 mb-4">
                <div class="profile-section">
                    <h5><i class="fas fa-image"></i> Foto de Perfil</h5>
                    
                    <form method="POST" action="" enctype="multipart/form-data" id="uploadForm">
                        <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                        
                        <div class="upload-area" id="uploadArea">
                            <i class="fas fa-cloud-upload-alt fa-3x mb-3"></i>
                            <p class="mb-2">Clique ou arraste uma imagem aqui</p>
                            <small class="text-light">JPG, PNG, GIF ou WEBP (máx. 5MB)</small>
                            <input type="file" name="foto_perfil" id="fotoInput" accept="image/*" style="display: none;" required>
                        </div>
                        
                        <div class="d-grid gap-2 mt-3">
                            <button type="submit" name="upload_foto" class="btn btn-primary">
                                <i class="fas fa-upload"></i> Enviar Foto
                            </button>
                            <?php if (!empty($userData['foto_perfil'])): ?>
                                <button type="submit" name="remover_foto" class="btn btn-danger" onclick="return confirm('Tem certeza que deseja remover a foto?');">
                                    <i class="fas fa-trash"></i> Remover Foto
                                </button>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Informações do Perfil -->
            <div class="col-md-8">
                <div class="profile-section">
                    <h5><i class="fas fa-user-edit"></i> Informações Pessoais</h5>
                    
                    <form method="POST" action="">
                        <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                        
                        <div class="mb-3">
                            <label for="nome" class="form-label">Nome Completo</label>
                            <input type="text" class="form-control" id="nome" name="nome" 
                                   value="<?= htmlspecialchars($userData['nome']) ?>" 
                                   required minlength="3" maxlength="100">
                        </div>
                        
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" 
                                   value="<?= htmlspecialchars($userData['email']) ?>" 
                                   required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="nivel_conhecimento" class="form-label">Nível de Conhecimento</label>
                            <select class="form-select" id="nivel_conhecimento" name="nivel_conhecimento" required>
                                <option value="iniciante" <?= $userData['nivel_conhecimento'] === 'iniciante' ? 'selected' : '' ?>>Iniciante</option>
                                <option value="intermediario" <?= $userData['nivel_conhecimento'] === 'intermediario' ? 'selected' : '' ?>>Intermediário</option>
                                <option value="avancado" <?= $userData['nivel_conhecimento'] === 'avancado' ? 'selected' : '' ?>>Avançado</option>
                            </select>
                            <div class="form-text">Seu nível atual de conhecimento em segurança cibernética</div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Data de Cadastro</label>
                            <input type="text" class="form-control" 
                                   value="<?= formatarData($userData['data_cadastro']) ?>" 
                                   disabled>
                        </div>
                        
                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <a href="dashboard.php" class="btn btn-secondary">
                                <i class="fas fa-times"></i> Cancelar
                            </a>
                            <button type="submit" name="atualizar_perfil" class="btn btn-primary">
                                <i class="fas fa-save"></i> Salvar Alterações
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Upload de foto com drag and drop
        const uploadArea = document.getElementById('uploadArea');
        const fotoInput = document.getElementById('fotoInput');
        
        uploadArea.addEventListener('click', () => fotoInput.click());
        
        uploadArea.addEventListener('dragover', (e) => {
            e.preventDefault();
            uploadArea.classList.add('dragover');
        });
        
        uploadArea.addEventListener('dragleave', () => {
            uploadArea.classList.remove('dragover');
        });
        
        uploadArea.addEventListener('drop', (e) => {
            e.preventDefault();
            uploadArea.classList.remove('dragover');
            
            if (e.dataTransfer.files.length > 0) {
                fotoInput.files = e.dataTransfer.files;
                // Preview da imagem
                const file = e.dataTransfer.files[0];
                if (file.type.startsWith('image/')) {
                    const reader = new FileReader();
                    reader.onload = (e) => {
                        uploadArea.innerHTML = `
                            <img src="${e.target.result}" style="max-width: 100%; max-height: 200px; border-radius: 10px; margin-bottom: 10px;">
                            <p class="mb-0">${file.name}</p>
                            <small class="text-light">Clique para trocar</small>
                        `;
                    };
                    reader.readAsDataURL(file);
                }
            }
        });
        
        fotoInput.addEventListener('change', (e) => {
            if (e.target.files.length > 0) {
                const file = e.target.files[0];
                if (file.type.startsWith('image/')) {
                    const reader = new FileReader();
                    reader.onload = (e) => {
                        uploadArea.innerHTML = `
                            <img src="${e.target.result}" style="max-width: 100%; max-height: 200px; border-radius: 10px; margin-bottom: 10px;">
                            <p class="mb-0">${file.name}</p>
                            <small class="text-light">Clique para trocar</small>
                        `;
                    };
                    reader.readAsDataURL(file);
                }
            }
        });
    </script>
</body>
</html>

