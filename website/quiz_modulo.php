<?php
/**
 * SACSWeb Educacional - Quiz do Módulo
 * Página dedicada para realizar o quiz de um módulo
 */

require_once '../config/config.php';
requireLogin();

$user = getCurrentUser();
$userPreferences = getUserPreferences($user['id'] ?? null);
$pdo = connectDatabase();

// Verificar se foi passado um ID de módulo
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    redirect('modulos.php');
}

$modulo_id = (int)$_GET['id'];

// Buscar informações do módulo
$stmt = $pdo->prepare("
    SELECT m.*, 
           COALESCE(p.progresso, 0) as progresso_usuario
    FROM modulos m 
    LEFT JOIN progresso_usuario p ON m.id = p.modulo_id AND p.usuario_id = ?
    WHERE m.id = ?
");
$stmt->execute([$user['id'], $modulo_id]);
$modulo = $stmt->fetch();

if (!$modulo) {
    redirect('modulos.php');
}

// Buscar quizzes do módulo
$stmt = $pdo->prepare("
    SELECT * FROM exercicios 
    WHERE modulo_id = ? AND tipo = 'quiz'
    ORDER BY ordem
");
$stmt->execute([$modulo_id]);
$quizzes = $stmt->fetchAll();

if (empty($quizzes)) {
    redirect("modulo.php?id=$modulo_id");
}

// Preparar dados dos quizzes para JavaScript
$quizzes_data = [];
foreach ($quizzes as $quiz) {
    $opcoes = json_decode($quiz['opcoes'], true);
    $opcoes_associativas = [];
    
    if ($opcoes) {
        if (is_array($opcoes) && isset($opcoes['a'])) {
            $opcoes_associativas = $opcoes;
        } else {
            for ($i = 0; $i < count($opcoes); $i += 2) {
                if (isset($opcoes[$i+1])) {
                    $opcoes_associativas[$opcoes[$i]] = $opcoes[$i+1];
                }
            }
        }
    }
    
    $quizzes_data[] = [
        'id' => (int)$quiz['id'],
        'pergunta' => $quiz['pergunta'],
        'opcoes' => $opcoes_associativas,
        'resposta_correta' => strtolower(trim($quiz['resposta_correta'])),
        'explicacao' => $quiz['explicacao'] ?? '',
        'pontos' => (int)$quiz['pontos']
    ];
}

// Processar submissão do quiz
$quiz_resultado = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_quiz'])) {
    $respostas = $_POST['respostas'] ?? [];
    
    $questoes_totais = count($quizzes);
    $questoes_acertadas = 0;
    $pontos_totais = 0;
    $pontos_obtidos = 0;
    
    // Processar cada resposta
    $resultados_detalhados = [];
    foreach ($quizzes as $quiz) {
        $quiz_id = $quiz['id'];
        $resposta_usuario = strtolower(trim($respostas[$quiz_id] ?? ''));
        $resposta_correta = strtolower(trim($quiz['resposta_correta']));
        $pontos_questao = (int)$quiz['pontos'];
        $pontos_totais += $pontos_questao;
        
        // Comparar respostas normalizadas (lowercase e trim)
        $acertou = ($resposta_usuario === $resposta_correta);
        
        if ($acertou) {
            $questoes_acertadas++;
            $pontos_obtidos += $pontos_questao;
        }
        
        $resultados_detalhados[$quiz_id] = [
            'quiz' => $quiz,
            'resposta_usuario' => $resposta_usuario,
            'resposta_correta' => $resposta_correta,
            'acertou' => $acertou,
            'pontos' => $acertou ? $pontos_questao : 0
        ];
    }
    
    // Calcular porcentagem de acertos ANTES de usar
    $porcentagem_acertos = $questoes_totais > 0 ? round(($questoes_acertadas / $questoes_totais) * 100, 2) : 0;
    
    // Salvar resultado no banco de dados
    try {
        // Buscar progresso ATUALIZADO do banco (não usar $modulo['progresso_usuario'] que pode estar desatualizado)
        $stmt = $pdo->prepare("SELECT * FROM progresso_usuario WHERE usuario_id = ? AND modulo_id = ?");
        $stmt->execute([$user['id'], $modulo_id]);
        $progresso = $stmt->fetch();
        
        // Calcular progresso de leitura (máximo 70%)
        // Se existe progresso no banco, usar progresso_leitura se disponível, senão usar progresso * 0.7
        if ($progresso) {
            // Verificar se tem campo progresso_leitura (novo modelo)
            $stmt_check = $pdo->query("SHOW COLUMNS FROM progresso_usuario LIKE 'progresso_leitura'");
            $tem_progresso_leitura = $stmt_check->rowCount() > 0;
            
            if ($tem_progresso_leitura && isset($progresso['progresso_leitura'])) {
                $progresso_leitura = min(70, (float)$progresso['progresso_leitura']);
            } else {
                // Modelo antigo: assumir que progresso atual é de leitura (máximo 70%)
                $progresso_leitura = min(70, (float)$progresso['progresso']);
            }
        } else {
            // Não existe progresso ainda, assumir 0% de leitura
            $progresso_leitura = 0;
        }
        
        // Calcular progresso do quiz (0-30%)
        $progresso_quiz = ($porcentagem_acertos / 100) * 30;
        $novo_progresso = min(100, $progresso_leitura + $progresso_quiz);
        $quiz_completo = ($porcentagem_acertos >= 100) ? 1 : 0;
        
        if ($progresso) {
            $stmt = $pdo->prepare("
                UPDATE progresso_usuario SET
                    progresso = ?,
                    progresso_leitura = ?,
                    progresso_quiz = ?,
                    pontos_obtidos = ?,
                    questoes_totais = ?,
                    questoes_acertadas = ?,
                    porcentagem_acertos = ?,
                    quiz_completo = ?,
                    data_conclusao = CASE WHEN ? >= 100 THEN NOW() ELSE data_conclusao END
                WHERE id = ?
            ");
            $stmt->execute([
                $novo_progresso,
                $progresso_leitura,
                $progresso_quiz,
                $pontos_obtidos,
                $questoes_totais,
                $questoes_acertadas,
                $porcentagem_acertos,
                $quiz_completo,
                $novo_progresso,
                $progresso['id']
            ]);
        } else {
            $stmt = $pdo->prepare("
                INSERT INTO progresso_usuario 
                (usuario_id, modulo_id, progresso, progresso_leitura, progresso_quiz, pontos_obtidos, 
                 questoes_totais, questoes_acertadas, porcentagem_acertos, quiz_completo, data_inicio)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([
                $user['id'],
                $modulo_id,
                $novo_progresso,
                $progresso_leitura,
                $progresso_quiz,
                $pontos_obtidos,
                $questoes_totais,
                $questoes_acertadas,
                $porcentagem_acertos,
                $quiz_completo
            ]);
        }
        
        // Salvar tentativa no histórico
        try {
            $stmt = $pdo->prepare("
                INSERT INTO quiz_tentativas 
                (usuario_id, modulo_id, questoes_totais, questoes_acertadas, porcentagem_acertos, 
                 pontos_obtidos, respostas, data_realizacao)
                VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([
                $user['id'],
                $modulo_id,
                $questoes_totais,
                $questoes_acertadas,
                $porcentagem_acertos,
                $pontos_obtidos,
                json_encode($respostas)
            ]);
        } catch (PDOException $e) {
            logMessage('Erro ao salvar tentativa de quiz: ' . $e->getMessage(), 'error');
        }
        
        $quiz_resultado = [
            'questoes_totais' => $questoes_totais,
            'questoes_acertadas' => $questoes_acertadas,
            'porcentagem_acertos' => $porcentagem_acertos,
            'pontos_obtidos' => $pontos_obtidos,
            'pontos_totais' => $pontos_totais,
            'detalhado' => $resultados_detalhados,
            'novo_progresso' => $novo_progresso
        ];
        
        logMessage("Quiz concluído: {$questoes_acertadas}/{$questoes_totais} ({$porcentagem_acertos}%)", 'info');
        
    } catch (PDOException $e) {
        logMessage('Erro ao salvar resultado do quiz: ' . $e->getMessage(), 'error');
        showError('Erro ao salvar resultado. Tente novamente.');
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quiz - <?= htmlspecialchars($modulo['titulo']) ?> - <?= SISTEMA_NOME ?></title>
    <link rel="icon" type="image/png" href="<?= ASSETS_URL ?>/images/icone.png">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="<?= ASSETS_URL ?>/css/sacsweb-unified.css" rel="stylesheet">
    <script>
        window.SACSWEB_PREFERENCES = <?= json_encode($userPreferences, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    </script>
    <script src="<?= ASSETS_URL ?>/js/preferences.js" defer></script>
    <style>
        /* Reset e configurações básicas */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #333;
            line-height: 1.6;
            transition: background-color 0.3s ease, color 0.3s ease;
        }
        
        /* Adaptação ao tema escuro/claro */
        :root.theme-light,
        html.theme-light,
        body.theme-light {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: var(--light-text, #333);
        }
        
        :root.theme-dark,
        html.theme-dark,
        body.theme-dark {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: var(--text-light, #e0e0e0);
        }
        
        :root.theme-auto,
        html.theme-auto,
        body.theme-auto {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        .container {
            width: 100%;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }

        /* Telas */
        .screen {
            display: none;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: #333;
            transition: background-color 0.3s ease, color 0.3s ease, border-color 0.3s ease;
        }
        
        /* Adaptação ao tema escuro */
        :root.theme-dark .screen,
        html.theme-dark .screen,
        body.theme-dark .screen {
            background: rgba(26, 26, 26, 0.95) !important;
            border: 1px solid rgba(255, 255, 255, 0.1) !important;
            color: var(--text-light, #e0e0e0) !important;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3) !important;
        }
        
        :root.theme-light .screen,
        html.theme-light .screen,
        body.theme-light .screen {
            background: rgba(255, 255, 255, 0.95) !important;
            color: var(--light-text, #212529) !important;
            border: 1px solid rgba(255, 255, 255, 0.2) !important;
        }
        
        :root.theme-auto .screen,
        html.theme-auto .screen,
        body.theme-auto .screen {
            background: rgba(255, 255, 255, 0.95);
            color: #333;
        }
        
        @media (prefers-color-scheme: dark) {
            :root.theme-auto .screen,
            html.theme-auto .screen,
            body.theme-auto .screen {
                background: rgba(26, 26, 26, 0.95) !important;
                border: 1px solid rgba(255, 255, 255, 0.1) !important;
                color: var(--text-light, #e0e0e0) !important;
            }
        }

        .screen.active {
            display: block;
            animation: fadeIn 0.5s ease-in-out;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Tela de Boas-vindas */
        .welcome-content {
            text-align: center;
        }

        .logo {
            font-size: 4rem;
            color: #667eea;
            margin-bottom: 20px;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% {
                transform: scale(1);
            }
            50% {
                transform: scale(1.05);
            }
        }

        .welcome-content h1 {
            font-size: 2.5rem;
            font-weight: 700;
            color: #333;
            margin-bottom: 15px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            transition: color 0.3s ease;
        }
        
        :root.theme-dark .welcome-content h1,
        html.theme-dark .welcome-content h1,
        body.theme-dark .welcome-content h1 {
            color: var(--text-light, #e0e0e0) !important;
            -webkit-text-fill-color: var(--text-light, #e0e0e0) !important;
        }
        
        :root.theme-light .welcome-content h1,
        html.theme-light .welcome-content h1,
        body.theme-light .welcome-content h1 {
            color: var(--light-text, #212529) !important;
            -webkit-text-fill-color: transparent;
        }
        
        :root.theme-dark .welcome-content p,
        html.theme-dark .welcome-content p,
        body.theme-dark .welcome-content p {
            color: var(--text-muted, #9ca3af) !important;
        }
        
        :root.theme-light .welcome-content p,
        html.theme-light .welcome-content p,
        body.theme-light .welcome-content p {
            color: var(--light-muted, #6c757d) !important;
        }

        .welcome-content p {
            font-size: 1.1rem;
            color: #666;
            margin-bottom: 40px;
            transition: color 0.3s ease;
        }

        .quiz-info {
            display: flex;
            justify-content: center;
            gap: 30px;
            margin-bottom: 40px;
            flex-wrap: wrap;
        }

        .info-item {
            display: flex;
            align-items: center;
            gap: 10px;
            color: #667eea;
            font-weight: 500;
        }

        .info-item i {
            font-size: 1.2rem;
        }

        /* Botões */
        .btn {
            padding: 15px 30px;
            border: none;
            border-radius: 50px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
            font-family: inherit;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 15px 30px rgba(102, 126, 234, 0.4);
        }

        .btn-secondary {
            background: #f8f9fa;
            color: #667eea;
            border: 2px solid #667eea;
        }

        .btn-secondary:hover {
            background: #667eea;
            color: white;
            transform: translateY(-2px);
        }

        .btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none !important;
        }

        /* Tela do Quiz */
        .quiz-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #f0f0f0;
        }

        .progress-container {
            display: flex;
            align-items: center;
            gap: 15px;
            flex: 1;
        }

        .progress-bar {
            flex: 1;
            height: 8px;
            background: #e9ecef;
            border-radius: 10px;
            overflow: hidden;
        }

        .progress-fill {
            height: 100%;
            background: linear-gradient(135deg, #667eea, #764ba2);
            border-radius: 10px;
            transition: width 0.3s ease;
            width: 0%;
        }

        #progress-text {
            font-weight: 600;
            color: #667eea;
            min-width: 50px;
        }

        .score-container {
            display: flex;
            align-items: center;
            gap: 8px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 10px 20px;
            border-radius: 25px;
            font-weight: 600;
        }

        .question-container {
            margin-bottom: 30px;
        }

        #question-text {
            font-size: 1.4rem;
            font-weight: 600;
            color: #333;
            margin-bottom: 30px;
            line-height: 1.5;
            transition: color 0.3s ease;
        }
        
        :root.theme-dark #question-text,
        html.theme-dark #question-text,
        body.theme-dark #question-text {
            color: var(--text-light, #e0e0e0) !important;
        }
        
        :root.theme-light #question-text,
        html.theme-light #question-text,
        body.theme-light #question-text {
            color: var(--light-text, #212529) !important;
        }
        
        @media (prefers-color-scheme: dark) {
            :root.theme-auto #question-text,
            html.theme-auto #question-text,
            body.theme-auto #question-text {
                color: var(--text-light, #e0e0e0) !important;
            }
        }

        .options-container {
            display: grid;
            gap: 15px;
        }

        .option {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 20px;
            background: #f8f9fa;
            border: 2px solid #e9ecef;
            border-radius: 15px;
            cursor: pointer;
            transition: all 0.3s ease;
            text-align: left;
            font-family: inherit;
            font-size: 1rem;
        }

        .option:hover {
            background: #e9ecef;
            border-color: #667eea;
            transform: translateX(5px);
        }

        .option.selected {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border-color: #667eea;
        }

        .option.correct {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
            border-color: #28a745;
        }

        .option.incorrect {
            background: linear-gradient(135deg, #dc3545, #fd7e14);
            color: white;
            border-color: #dc3545;
        }

        .option-letter {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            font-weight: 600;
            font-size: 1.1rem;
        }

        .option-text {
            flex: 1;
            font-weight: 500;
        }

        .quiz-footer {
            display: flex;
            justify-content: flex-end;
        }

        /* Tela de Resultados */
        .results-content {
            text-align: center;
        }

        .results-header {
            margin-bottom: 30px;
        }

        .results-icon {
            font-size: 4rem;
            color: #667eea;
            margin-bottom: 20px;
        }

        .results-header h1 {
            font-size: 2.5rem;
            font-weight: 700;
            color: #333;
            margin-bottom: 10px;
            transition: color 0.3s ease;
        }
        
        :root.theme-dark .results-header h1,
        html.theme-dark .results-header h1,
        body.theme-dark .results-header h1 {
            color: var(--text-light, #e0e0e0) !important;
        }
        
        :root.theme-light .results-header h1,
        html.theme-light .results-header h1,
        body.theme-light .results-header h1 {
            color: var(--light-text, #212529) !important;
        }
        
        @media (prefers-color-scheme: dark) {
            :root.theme-auto .results-header h1,
            html.theme-auto .results-header h1,
            body.theme-auto .results-header h1 {
                color: var(--text-light, #e0e0e0) !important;
            }
        }

        .score-display {
            margin-bottom: 30px;
        }

        .score-circle {
            display: inline-flex;
            align-items: baseline;
            font-size: 3rem;
            font-weight: 700;
            color: #667eea;
            margin-bottom: 10px;
        }

        .score-max {
            font-size: 1.5rem;
            color: #666;
            transition: color 0.3s ease;
        }
        
        :root.theme-dark .score-max,
        html.theme-dark .score-max,
        body.theme-dark .score-max {
            color: var(--text-muted, #9ca3af) !important;
        }
        
        :root.theme-light .score-max,
        html.theme-light .score-max,
        body.theme-light .score-max {
            color: var(--light-muted, #6c757d) !important;
        }
        
        @media (prefers-color-scheme: dark) {
            :root.theme-auto .score-max,
            html.theme-auto .score-max,
            body.theme-auto .score-max {
                color: var(--text-muted, #9ca3af) !important;
            }
        }

        .score-percentage {
            font-size: 1.2rem;
            color: #666;
            font-weight: 500;
            transition: color 0.3s ease;
        }
        
        :root.theme-dark .score-percentage,
        html.theme-dark .score-percentage,
        body.theme-dark .score-percentage {
            color: var(--text-muted, #9ca3af) !important;
        }
        
        :root.theme-light .score-percentage,
        html.theme-light .score-percentage,
        body.theme-light .score-percentage {
            color: var(--light-muted, #6c757d) !important;
        }
        
        @media (prefers-color-scheme: dark) {
            :root.theme-auto .score-percentage,
            html.theme-auto .score-percentage,
            body.theme-auto .score-percentage {
                color: var(--text-muted, #9ca3af) !important;
            }
        }

        .score-message {
            margin-bottom: 40px;
        }

        .score-message h3 {
            font-size: 1.5rem;
            font-weight: 600;
            color: #333;
            margin-bottom: 10px;
            transition: color 0.3s ease;
        }
        
        :root.theme-dark .score-message h3,
        html.theme-dark .score-message h3,
        body.theme-dark .score-message h3 {
            color: var(--text-light, #e0e0e0) !important;
        }
        
        :root.theme-light .score-message h3,
        html.theme-light .score-message h3,
        body.theme-light .score-message h3 {
            color: var(--light-text, #212529) !important;
        }
        
        @media (prefers-color-scheme: dark) {
            :root.theme-auto .score-message h3,
            html.theme-auto .score-message h3,
            body.theme-auto .score-message h3 {
                color: var(--text-light, #e0e0e0) !important;
            }
        }

        .score-message p {
            color: #666;
            font-size: 1.1rem;
            transition: color 0.3s ease;
        }
        
        :root.theme-dark .score-message p,
        html.theme-dark .score-message p,
        body.theme-dark .score-message p {
            color: var(--text-muted, #9ca3af) !important;
        }
        
        :root.theme-light .score-message p,
        html.theme-light .score-message p,
        body.theme-light .score-message p {
            color: var(--light-muted, #6c757d) !important;
        }
        
        @media (prefers-color-scheme: dark) {
            :root.theme-auto .score-message p,
            html.theme-auto .score-message p,
            body.theme-auto .score-message p {
                color: var(--text-muted, #9ca3af) !important;
            }
        }

        .results-actions {
            display: flex;
            gap: 15px;
            justify-content: center;
            flex-wrap: wrap;
        }

        /* Responsividade */
        @media (max-width: 768px) {
            .container {
                padding: 10px;
            }
            
            .screen {
                padding: 20px;
            }
            
            .welcome-content h1 {
                font-size: 2rem;
            }
            
            .quiz-info {
                flex-direction: column;
                gap: 15px;
            }
            
            .quiz-header {
                flex-direction: column;
                gap: 15px;
                align-items: stretch;
            }
            
            .score-container {
                align-self: center;
            }
            
            #question-text {
                font-size: 1.2rem;
            }
            
            .option {
                padding: 15px;
            }
            
            .results-actions {
                flex-direction: column;
            }
            
            .btn {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body class="page-quiz">
    <div class="container">
        <?php if ($quiz_resultado): ?>
            <!-- Tela de Resultados -->
            <div id="results-screen" class="screen active">
                <div class="results-content">
                    <div class="results-header">
                        <div class="results-icon">
                            <i class="fas fa-medal"></i>
                        </div>
                        <h1>Quiz Concluído!</h1>
                    </div>

                    <div class="score-display">
                        <div class="score-circle">
                            <span id="final-score"><?= $quiz_resultado['questoes_acertadas'] ?></span>
                            <span class="score-max">/<?= $quiz_resultado['questoes_totais'] ?></span>
                        </div>
                        <div class="score-percentage">
                            <span id="percentage"><?= $quiz_resultado['porcentagem_acertos'] ?>%</span>
                        </div>
                    </div>

                    <div class="score-message">
                        <?php if ($quiz_resultado['porcentagem_acertos'] >= 100): ?>
                            <h3 style="color: #28a745;">Perfeito!</h3>
                            <p>Você acertou todas as questões! Módulo concluído com sucesso!</p>
                        <?php elseif ($quiz_resultado['porcentagem_acertos'] >= 80): ?>
                            <h3 style="color: #28a745;">Excelente!</h3>
                            <p>Você demonstrou um conhecimento excepcional sobre este módulo!</p>
                        <?php elseif ($quiz_resultado['porcentagem_acertos'] >= 60): ?>
                            <h3 style="color: #ffc107;">Muito Bom!</h3>
                            <p>Você tem um bom conhecimento. Continue estudando!</p>
                        <?php else: ?>
                            <h3 style="color: #dc3545;">Continue Tentando!</h3>
                            <p>Revise o conteúdo e tente novamente para melhorar seu desempenho.</p>
                        <?php endif; ?>
                    </div>

                    <div class="results-actions">
                        <a href="modulo.php?id=<?= $modulo_id ?>" class="btn btn-primary">
                            <i class="fas fa-arrow-left"></i> Voltar ao Módulo
                        </a>
                        <button id="restart-btn" class="btn btn-secondary">
                            <i class="fas fa-redo"></i> Fazer Novamente
                        </button>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <!-- Tela de Boas-vindas -->
            <div id="welcome-screen" class="screen active">
                <div class="welcome-content">
                    <div class="logo">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                    <h1>Quiz de Segurança Cibernética</h1>
                    <p>Teste seus conhecimentos sobre <?= htmlspecialchars($modulo['titulo']) ?></p>
                    
                    <div class="quiz-info">
                        <div class="info-item">
                            <i class="fas fa-question-circle"></i>
                            <span><?= count($quizzes) ?> perguntas</span>
                        </div>
                        <div class="info-item">
                            <i class="fas fa-clock"></i>
                            <span>Sem limite de tempo</span>
                        </div>
                        <div class="info-item">
                            <i class="fas fa-trophy"></i>
                            <span>Pontuação final</span>
                        </div>
                    </div>

                    <button id="start-btn" class="btn btn-primary">
                        <i class="fas fa-play"></i> Começar Quiz
                    </button>
                </div>
            </div>

            <!-- Tela do Quiz -->
            <div id="quiz-screen" class="screen">
                <form method="POST" id="quizForm">
                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                    
                    <div class="quiz-header">
                        <div class="progress-container">
                            <div class="progress-bar">
                                <div id="progress-fill" class="progress-fill"></div>
                            </div>
                            <span id="progress-text">1/<?= count($quizzes) ?></span>
                        </div>
                        <div class="score-container">
                            <i class="fas fa-star"></i>
                            <span id="score">0</span>
                        </div>
                    </div>

                    <div class="question-container">
                        <h2 id="question-text">Carregando pergunta...</h2>
                        
                        <div class="options-container">
                            <button type="button" class="option" data-option="a">
                                <span class="option-letter">A</span>
                                <span class="option-text">Carregando opção...</span>
                            </button>
                            <button type="button" class="option" data-option="b">
                                <span class="option-letter">B</span>
                                <span class="option-text">Carregando opção...</span>
                            </button>
                            <button type="button" class="option" data-option="c">
                                <span class="option-letter">C</span>
                                <span class="option-text">Carregando opção...</span>
                            </button>
                            <button type="button" class="option" data-option="d">
                                <span class="option-letter">D</span>
                                <span class="option-text">Carregando opção...</span>
                            </button>
                        </div>
                        
                        <!-- Campos hidden para respostas -->
                        <?php foreach ($quizzes as $quiz): ?>
                            <input type="hidden" name="respostas[<?= $quiz['id'] ?>]" id="resposta-<?= $quiz['id'] ?>" value="">
                        <?php endforeach; ?>
                    </div>

                    <div class="quiz-footer">
                        <button type="button" id="prev-btn" class="btn btn-secondary" disabled>
                            <i class="fas fa-chevron-left"></i> Anterior
                        </button>
                        <button type="button" id="next-btn" class="btn btn-primary" disabled>
                            Próxima Pergunta
                            <i class="fas fa-arrow-right"></i>
                        </button>
                        <button type="submit" name="submit_quiz" id="submit-btn" class="btn btn-primary" style="display: none;" disabled>
                            <i class="fas fa-check"></i> Finalizar Quiz
                        </button>
                    </div>
                </form>
            </div>
        <?php endif; ?>
    </div>

    <script>
        // Dados do Quiz
        const quizData = <?= json_encode($quizzes_data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
        
        // Variáveis globais
        let currentQuestion = 0;
        let score = 0;
        let selectedAnswer = null;
        let quizCompleted = false;
        let answered = false;
        
        // Elementos do DOM
        const welcomeScreen = document.getElementById('welcome-screen');
        const quizScreen = document.getElementById('quiz-screen');
        const resultsScreen = document.getElementById('results-screen');
        const startBtn = document.getElementById('start-btn');
        const nextBtn = document.getElementById('next-btn');
        const prevBtn = document.getElementById('prev-btn');
        const submitBtn = document.getElementById('submit-btn');
        const restartBtn = document.getElementById('restart-btn');
        const questionText = document.getElementById('question-text');
        const options = document.querySelectorAll('.option');
        const progressFill = document.getElementById('progress-fill');
        const progressText = document.getElementById('progress-text');
        const scoreElement = document.getElementById('score');
        
        // Event Listeners
        if (startBtn) startBtn.addEventListener('click', startQuiz);
        if (nextBtn) nextBtn.addEventListener('click', nextQuestion);
        if (prevBtn) prevBtn.addEventListener('click', prevQuestion);
        if (restartBtn) restartBtn.addEventListener('click', () => {
            window.location.href = window.location.href.split('?')[0] + '?id=<?= $modulo_id ?>';
        });
        
        if (submitBtn) {
            submitBtn.addEventListener('click', function(e) {
                let allAnswered = true;
                quizData.forEach(q => {
                    const input = document.getElementById(`resposta-${q.id}`);
                    if (!input || !input.value) allAnswered = false;
                });
                if (!allAnswered) {
                    e.preventDefault();
                    alert('Por favor, responda todas as questões antes de finalizar.');
                    return false;
                }
            });
        }
        
        options.forEach(option => {
            option.addEventListener('click', () => selectOption(option));
        });
        
        // Funções
        function startQuiz() {
            currentQuestion = 0;
            score = 0;
            selectedAnswer = null;
            quizCompleted = false;
            answered = false;
            
            quizData.forEach(q => {
                const input = document.getElementById(`resposta-${q.id}`);
                if (input) input.value = '';
            });
            
            showScreen(quizScreen);
            loadQuestion();
            updateProgress();
            updateScore();
            updateButtons(); // Garantir que os botões estão corretos ao iniciar
        }
        
        function loadQuestion() {
            if (currentQuestion >= quizData.length) return;
            
            const question = quizData[currentQuestion];
            questionText.textContent = question.pergunta;
            answered = false;
            selectedAnswer = null;
            
            const optionLetters = ['a', 'b', 'c', 'd'];
            options.forEach((option, index) => {
                const letter = optionLetters[index];
                const optionTextEl = option.querySelector('.option-text');
                
                if (question.opcoes && question.opcoes[letter]) {
                    optionTextEl.textContent = question.opcoes[letter];
                    option.dataset.option = letter;
                    option.style.display = 'flex';
                    option.classList.remove('selected', 'correct', 'incorrect');
                    option.disabled = false;
                } else {
                    option.style.display = 'none';
                }
            });
            
            const input = document.getElementById(`resposta-${question.id}`);
            if (input && input.value) {
                const savedAnswer = input.value;
                const savedOption = document.querySelector(`[data-option="${savedAnswer}"]`);
                if (savedOption) {
                    savedOption.classList.add('selected');
                    selectedAnswer = savedAnswer;
                    answered = true;
                }
            }
            
            updateButtons();
        }
        
        function selectOption(selectedOption) {
            if (quizCompleted || answered) return;
            
            options.forEach(opt => opt.classList.remove('selected'));
            selectedOption.classList.add('selected');
            selectedAnswer = selectedOption.dataset.option;
            
            const question = quizData[currentQuestion];
            const input = document.getElementById(`resposta-${question.id}`);
            if (input) input.value = selectedAnswer;
            
            // Atualizar botões para habilitar "Finalizar" se for a última questão
            updateButtons();
        }
        
        function nextQuestion() {
            if (selectedAnswer === null || answered) return;
            
            const question = quizData[currentQuestion];
            const isCorrect = selectedAnswer === question.resposta_correta;
            
            if (isCorrect) score++;
            
            answered = true;
            options.forEach(option => {
                const optionLetter = option.dataset.option;
                option.disabled = true;
                
                if (optionLetter === question.resposta_correta) {
                    option.classList.add('correct');
                } else if (optionLetter === selectedAnswer && !isCorrect) {
                    option.classList.add('incorrect');
                }
            });
            
            // Atualizar botões imediatamente para mostrar "Finalizar" se for a última
            updateButtons();
            
            setTimeout(() => {
                // Se não for a última questão, avança para a próxima
                if (currentQuestion < quizData.length - 1) {
                    currentQuestion++;
                    loadQuestion();
                    updateProgress();
                    updateScore();
                }
                // Se for a última questão, não avança - apenas mantém o botão "Finalizar" visível
            }, 1500);
        }
        
        function prevQuestion() {
            if (currentQuestion > 0) {
                currentQuestion--;
                loadQuestion();
                updateProgress();
                updateScore();
            }
        }
        
        function updateProgress() {
            const progress = ((currentQuestion + 1) / quizData.length) * 100;
            if (progressFill) progressFill.style.width = progress + '%';
            if (progressText) progressText.textContent = `${currentQuestion + 1}/${quizData.length}`;
        }
        
        function updateScore() {
            if (scoreElement) scoreElement.textContent = score;
        }
        
        function updateButtons() {
            // Botão Anterior
            if (prevBtn) prevBtn.disabled = currentQuestion === 0;
            
            // Verificar se estamos na última questão
            const isLastQuestion = currentQuestion === quizData.length - 1;
            
            if (isLastQuestion) {
                // Última questão: mostrar botão Finalizar, esconder Próxima
                if (submitBtn) {
                    submitBtn.style.display = 'inline-flex';
                    submitBtn.disabled = selectedAnswer === null || answered;
                }
                if (nextBtn) {
                    nextBtn.style.display = 'none';
                }
            } else {
                // Não é a última: mostrar botão Próxima, esconder Finalizar
                if (nextBtn) {
                    nextBtn.style.display = 'inline-flex';
                    nextBtn.disabled = selectedAnswer === null || answered;
                }
                if (submitBtn) {
                    submitBtn.style.display = 'none';
                }
            }
        }
        
        function showScreen(screen) {
            if (welcomeScreen) welcomeScreen.classList.remove('active');
            if (quizScreen) quizScreen.classList.remove('active');
            if (resultsScreen) resultsScreen.classList.remove('active');
            if (screen) screen.classList.add('active');
        }
    </script>
</body>
</html>
