<?php
require_once '../config/config.php';
requireLogin();

$usuario = getCurrentUser();
$exercicio_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$exercicio_id) {
    header('Location: exercicios.php');
    exit;
}

try {
    $pdo = connectDatabase();
    
    // Buscar dados do exercício
    $stmt = $pdo->prepare("
        SELECT e.*, m.titulo as modulo_titulo, m.nivel as modulo_nivel
        FROM exercicios e
        JOIN modulos m ON e.modulo_id = m.id
        WHERE e.id = ? AND e.ativo = 1
    ");
    $stmt->execute([$exercicio_id]);
    $exercicio = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$exercicio) {
        header('Location: exercicios.php');
        exit;
    }
    
    // Verificar progresso do usuário
    $stmt = $pdo->prepare("
        SELECT * FROM progresso_usuario 
        WHERE usuario_id = ? AND modulo_id = ?
    ");
    $stmt->execute([$usuario['id'], $exercicio['modulo_id']]);
    $progresso = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Processar submissão de resposta
    $mensagem = '';
    $tipo_mensagem = '';
    
    if ($_POST && isset($_POST['submit_resposta'])) {
        $resposta_usuario = trim($_POST['resposta']);
        $resposta_correta = $exercicio['resposta_correta'];
        
        if ($exercicio['tipo'] === 'quiz') {
            $resposta_usuario = $_POST['opcao_selecionada'] ?? '';
        }
        
        if ($resposta_usuario === $resposta_correta) {
            $mensagem = 'Parabéns! Resposta correta!';
            $tipo_mensagem = 'success';
            
            // Atualizar progresso
            if (!$progresso) {
                $stmt = $pdo->prepare("
                    INSERT INTO progresso_usuario (usuario_id, modulo_id, progresso, pontos_obtidos, status)
                    VALUES (?, ?, 25.0, ?, 'em_andamento')
                ");
                $stmt->execute([$usuario['id'], $exercicio['modulo_id'], $exercicio['pontos']]);
            } else {
                $novo_progresso = min(100, $progresso['progresso'] + 25);
                $novos_pontos = $progresso['pontos_obtidos'] + $exercicio['pontos'];
                
                $stmt = $pdo->prepare("
                    UPDATE progresso_usuario 
                    SET progresso = ?, pontos_obtidos = ?, status = ?
                    WHERE id = ?
                ");
                $status = $novo_progresso >= 100 ? 'concluido' : 'em_andamento';
                $stmt->execute([$novo_progresso, $novos_pontos, $status, $progresso['id']]);
            }
            
            // Log da atividade
            logMessage("Exercício {$exercicio['titulo']} concluído com sucesso", 'info');
            
        } else {
            $mensagem = 'Resposta incorreta. Tente novamente!';
            $tipo_mensagem = 'danger';
        }
    }
    
    // Buscar exercícios relacionados
    $stmt = $pdo->prepare("
        SELECT e.*, m.titulo as modulo_titulo
        FROM exercicios e
        JOIN modulos m ON e.modulo_id = m.id
        WHERE e.modulo_id = ? AND e.id != ? AND e.ativo = 1
        ORDER BY e.dificuldade, e.titulo
        LIMIT 5
    ");
    $stmt->execute([$exercicio['modulo_id'], $exercicio_id]);
    $exercicios_relacionados = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    logMessage("Erro ao carregar exercício: " . $e->getMessage(), 'error');
    $mensagem = 'Erro ao carregar exercício';
    $tipo_mensagem = 'danger';
}

// Preparar dados para o JavaScript
$quizData = [];
$opcoes_associativas = [];
if ($exercicio['tipo'] === 'quiz') {
    $opcoes = json_decode($exercicio['opcoes'], true);

    // Transformar em array associativo ['a'=>'texto', 'b'=>'texto', ...]
    if ($opcoes) {
        for ($i = 0; $i < count($opcoes); $i += 2) {
            $opcoes_associativas[$opcoes[$i]] = $opcoes[$i+1];
        }

        $quizData[] = [
            'question' => $exercicio['pergunta'],
            'options' => $opcoes_associativas,
            'correct' => $exercicio['resposta_correta'],
            'explanation' => $exercicio['explicacao'] ?? ''
        ];
    }
}
?>


<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($exercicio['titulo']); ?> - SACSWeb</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
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
            color: #333;
            line-height: 1.6;
        }

        .container {
            width: 100%;
            max-width: 1000px;
            margin: 0 auto;
            padding: 20px;
        }

        /* Header com navegação */
        .header {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 20px 30px;
            margin-bottom: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header-left {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .back-btn {
            background: #f8f9fa;
            color: #667eea;
            border: 2px solid #667eea;
            padding: 10px 20px;
            border-radius: 25px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .back-btn:hover {
            background: #667eea;
            color: white;
            transform: translateY(-2px);
        }

        .exercise-info {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .exercise-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: #333;
            margin: 0;
        }

        .exercise-meta {
            display: flex;
            gap: 20px;
            font-size: 0.9rem;
            color: #666;
        }

        .meta-item {
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .header-right {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 10px;
            color: #667eea;
            font-weight: 500;
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
        }

        .welcome-content p {
            font-size: 1.1rem;
            color: #666;
            margin-bottom: 40px;
        }

        .exercise-details {
            display: flex;
            justify-content: center;
            gap: 30px;
            margin-bottom: 40px;
            flex-wrap: wrap;
        }

        .detail-item {
            display: flex;
            align-items: center;
            gap: 10px;
            color: #667eea;
            font-weight: 500;
        }

        .detail-item i {
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
            justify-content: space-between;
            align-items: center;
        }

        .explanation {
            background: #e8f5e8;
            border: 1px solid #28a745;
            border-radius: 10px;
            padding: 20px;
            margin-top: 20px;
            display: none;
        }

        .explanation.show {
            display: block;
            animation: fadeIn 0.5s ease-in-out;
        }

        .explanation h4 {
            color: #28a745;
            margin-bottom: 10px;
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
        }

        .score-display {
            margin-bottom: 30px;
        }

        .score-circle {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            font-size: 2rem;
            font-weight: 700;
        }

        .score-max {
            font-size: 1rem;
            opacity: 0.8;
        }

        .score-percentage {
            font-size: 1.5rem;
            font-weight: 600;
            color: #667eea;
        }

        .score-message {
            margin-bottom: 40px;
        }

        .score-message h3 {
            font-size: 1.8rem;
            color: #333;
            margin-bottom: 10px;
        }

        .score-message p {
            color: #666;
            font-size: 1.1rem;
        }

        .results-actions {
            display: flex;
            justify-content: center;
            gap: 20px;
            flex-wrap: wrap;
        }

        /* Exercícios relacionados */
        .related-exercises {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 30px;
            margin-top: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .related-exercises h3 {
            color: #333;
            margin-bottom: 20px;
            font-size: 1.5rem;
        }

        .exercise-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
        }

        .exercise-card {
            background: #f8f9fa;
            border: 2px solid #e9ecef;
            border-radius: 15px;
            padding: 20px;
            transition: all 0.3s ease;
            text-decoration: none;
            color: inherit;
        }

        .exercise-card:hover {
            border-color: #667eea;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .exercise-card h4 {
            color: #333;
            margin-bottom: 10px;
            font-size: 1.1rem;
        }

        .exercise-card p {
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 15px;
        }

        .exercise-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.8rem;
            color: #667eea;
        }

        /* Responsividade */
        @media (max-width: 768px) {
            .container {
                padding: 10px;
            }

            .header-content {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }

            .exercise-meta {
                flex-direction: column;
                gap: 10px;
            }

            .screen {
                padding: 20px;
            }

            .welcome-content h1 {
                font-size: 2rem;
            }

            .exercise-details {
                flex-direction: column;
                gap: 15px;
            }

            .results-actions {
                flex-direction: column;
            }

            .exercise-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header com navegação -->
        <div class="header">
            <div class="header-content">
                <div class="header-left">
                    <a href="exercicios.php" class="back-btn">
                        <i class="fas fa-arrow-left"></i>
                        Voltar
                    </a>
                    <div class="exercise-info">
                        <h1 class="exercise-title"><?php echo htmlspecialchars($exercicio['titulo']); ?></h1>
                        <div class="exercise-meta">
                            <span class="meta-item">
                                <i class="fas fa-book"></i>
                                <?php echo htmlspecialchars($exercicio['modulo_titulo']); ?>
                            </span>
                            <span class="meta-item">
                                <i class="fas fa-signal"></i>
                                <?php echo ucfirst($exercicio['modulo_nivel']); ?>
                            </span>
                            <span class="meta-item">
                                <i class="fas fa-clock"></i>
                                <?php echo $exercicio['tempo_estimado']; ?> min
                            </span>
                            <span class="meta-item">
                                <i class="fas fa-star"></i>
                                <?php echo $exercicio['pontos']; ?> pts
                            </span>
                        </div>
                    </div>
                </div>
                <div class="header-right">
                    <div class="user-info">
                        <i class="fas fa-user-circle"></i>
                        <?php echo htmlspecialchars($usuario['nome']); ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tela de Boas-vindas -->
        <div id="welcome-screen" class="screen active">
            <div class="welcome-content">
                <div class="logo">
                    <i class="fas fa-question-circle"></i>
                </div>
                <h1><?php echo htmlspecialchars($exercicio['titulo']); ?></h1>
                <p><?php echo htmlspecialchars($exercicio['descricao']); ?></p>
                
                <div class="exercise-details">
                    <div class="detail-item">
                        <i class="fas fa-question-circle"></i>
                        <span>Quiz</span>
                    </div>
                    <div class="detail-item">
                        <i class="fas fa-book"></i>
                        <span><?php echo htmlspecialchars($exercicio['modulo_titulo']); ?></span>
                    </div>
                    <div class="detail-item">
                        <i class="fas fa-signal"></i>
                        <span><?php echo ucfirst($exercicio['modulo_nivel']); ?></span>
                    </div>
                    <div class="detail-item">
                        <i class="fas fa-clock"></i>
                        <span><?php echo $exercicio['tempo_estimado']; ?> minutos</span>
                    </div>
                    <div class="detail-item">
                        <i class="fas fa-star"></i>
                        <span><?php echo $exercicio['pontos']; ?> pontos</span>
                    </div>
                </div>

                <button id="start-btn" class="btn btn-primary">
                    <i class="fas fa-play"></i>
                    Começar Quiz
                </button>
            </div>
        </div>

        <!-- Tela do Quiz/Exercício -->
        <div id="quiz-screen" class="screen">
            <div class="quiz-header">
                <div class="progress-container">
                    <div class="progress-bar">
                        <div id="progress-fill" class="progress-fill"></div>
                    </div>
                    <span id="progress-text">1/1</span>
                </div>
                <div class="score-container">
                    <i class="fas fa-star"></i>
                    <span id="score">0</span>
                </div>
            </div>

            <div class="question-container">
                <h2 id="question-text"><?php echo htmlspecialchars($exercicio['pergunta']); ?></h2>
                
                <div class="options-container">
                    <?php 
                    if ($opcoes_associativas): 
                        $letras = ['a', 'b', 'c', 'd'];
                        foreach ($letras as $letra):
                            if (isset($opcoes_associativas[$letra])):
                    ?>
                        <button class="option" data-option="<?php echo $letra; ?>">
                            <span class="option-letter"><?php echo strtoupper($letra); ?></span>
                            <span class="option-text"><?php echo htmlspecialchars($opcoes_associativas[$letra]); ?></span>
                        </button>
                    <?php 
                            endif;
                        endforeach;
                    endif; 
                    ?>
                </div>


                <!-- Explicação (aparece após resposta) -->
                <?php if ($exercicio['explicacao']): ?>
                    <div class="explanation" id="explanation">
                        <h4><i class="fas fa-lightbulb"></i> Explicação:</h4>
                        <p><?php echo htmlspecialchars($exercicio['explicacao']); ?></p>
                    </div>
                <?php endif; ?>
            </div>

            <div class="quiz-footer">
                <div class="left-actions">
                    <button id="botaoVoltar" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i>
                        Voltar aos Exercícios
                    </button>
                </div>
                <div class="right-actions">
                    <button id="submit-btn" class="btn btn-primary" disabled>
                        <i class="fas fa-check"></i>
                        Verificar Resposta
                    </button>
                </div>
            </div>
        </div>

        <!-- Tela de Resultados -->
        <div id="results-screen" class="screen">
            <div class="results-content">
                <div class="results-header">
                    <div class="results-icon">
                        <i class="fas fa-medal"></i>
                    </div>
                    <h1>Exercício Concluído!</h1>
                </div>

                <div class="score-display">
                    <div class="score-circle">
                        <span id="final-score">0</span>
                        <span class="score-max">/<?php echo $exercicio['pontos']; ?></span>
                    </div>
                    <div class="score-percentage">
                        <span id="percentage">0%</span>
                    </div>
                </div>

                <div class="score-message">
                    <h3 id="score-title">Excelente!</h3>
                    <p id="score-description">Você demonstrou um conhecimento excepcional sobre este tópico!</p>
                </div>

                <div class="results-actions">
                    <button id="restart-btn" class="btn btn-primary">
                        <i class="fas fa-redo"></i>
                        Fazer Novamente
                    </button>
                    <a href="exercicios.php" class="btn btn-secondary">
                        <i class="fas fa-list"></i>
                        Ver Todos os Exercícios
                    </a>
                </div>
            </div>
        </div>

        <!-- Exercícios relacionados -->
        <?php if ($exercicios_relacionados): ?>
            <div class="related-exercises">
                <h3><i class="fas fa-link"></i> Exercícios Relacionados</h3>
                <div class="exercise-grid">
                    <?php foreach ($exercicios_relacionados as $ex): ?>
                        <a href="exercicio.php?id=<?php echo $ex['id']; ?>" class="exercise-card">
                            <h4><?php echo htmlspecialchars($ex['titulo']); ?></h4>
                            <p><?php echo htmlspecialchars($ex['descricao']); ?></p>
                            <div class="exercise-meta">
                                <span><?php echo ucfirst($ex['tipo']); ?></span>
                                <span><?php echo $ex['pontos']; ?> pts</span>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script>
        // Dados do exercício
        const exerciseData = <?php echo json_encode($quizData); ?>;
        const exerciseType = '<?php echo $exercicio['tipo']; ?>';
        const correctAnswer = '<?php echo addslashes($exercicio['resposta_correta']); ?>';
        const maxPoints = <?php echo $exercicio['pontos']; ?>;
        
        // Elementos DOM
        const welcomeScreen = document.getElementById('welcome-screen');
        const quizScreen = document.getElementById('quiz-screen');
        const resultsScreen = document.getElementById('results-screen');
        const startBtn = document.getElementById('start-btn');
        const submitBtn = document.getElementById('submit-btn');
        const restartBtn = document.getElementById('restart-btn');
        
        // Variáveis de estado
        let currentQuestion = 0;
        let score = 0;
        let selectedOption = null;
        let answered = false;
        let totalQuestions = 1; // Para exercícios individuais
        
        // Inicialização
        document.addEventListener('DOMContentLoaded', function() {
            // Event listeners
            startBtn.addEventListener('click', startExercise);
            submitBtn.addEventListener('click', submitAnswer);
            restartBtn.addEventListener('click', restartExercise);
            
                    // Configurar opções para todos os exercícios (agora todos são quiz)
        setupQuizOptions();
        });
        
        function startExercise() {
            welcomeScreen.classList.remove('active');
            quizScreen.classList.add('active');
            updateProgress();
        }
        
        function setupQuizOptions() {
            const options = document.querySelectorAll('.option');
            options.forEach(option => {
                option.addEventListener('click', function() {
                    if (answered) return;
                    
                    // Remover seleção anterior
                    options.forEach(opt => opt.classList.remove('selected'));
                    
                    // Selecionar opção atual
                    this.classList.add('selected');
                    selectedOption = this.dataset.option;
                    
                    // Habilitar botão de submit
                    submitBtn.disabled = false;
                });
            });
        }
        
        function submitAnswer() {
            if (answered) return;
            
            answered = true;
            handleQuizSubmission();
        }
        
        function handleQuizSubmission() {
            const options = document.querySelectorAll('.option');
            const correctOption = document.querySelector(`[data-option="${correctAnswer}"]`);
            
            if (selectedOption === correctAnswer) {
                score = maxPoints;
                correctOption.classList.add('correct');
                showExplanation();
            } else {
                correctOption.classList.add('correct');
                if (selectedOption) {
                    const selectedElement = document.querySelector(`[data-option="${selectedOption}"]`);
                    selectedElement.classList.add('incorrect');
                }
                showExplanation();
            }
            
            // Atualizar interface
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-check"></i> Respondido';
            
            // Mostrar resultados após delay
            setTimeout(showResults, 2000);
        }
        

        
        function showExplanation() {
            const explanation = document.getElementById('explanation');
            if (explanation) {
                explanation.classList.add('show');
            }
        }
        
        function showResults() {
            quizScreen.classList.remove('active');
            resultsScreen.classList.add('active');
            
            // Atualizar resultados
            document.getElementById('final-score').textContent = score;
            document.getElementById('percentage').textContent = Math.round((score / maxPoints) * 100) + '%';
            
            // Mensagem baseada na pontuação
            const percentage = (score / maxPoints) * 100;
            const scoreTitle = document.getElementById('score-title');
            const scoreDescription = document.getElementById('score-description');
            
            if (percentage >= 80) {
                scoreTitle.textContent = 'Excelente!';
                scoreDescription.textContent = 'Você demonstrou um conhecimento excepcional sobre XSS!';
            } else if (percentage >= 60) {
                scoreTitle.textContent = 'Muito Bom!';
                scoreDescription.textContent = 'Você tem uma boa compreensão sobre XSS!';
            } else if (percentage >= 40) {
                scoreTitle.textContent = 'Bom!';
                scoreDescription.textContent = 'Continue estudando para melhorar ainda mais seu conhecimento sobre XSS!';
            } else {
                scoreTitle.textContent = 'Continue Tentando!';
                scoreDescription.textContent = 'Não desista, XSS é um tópico complexo. Continue estudando!';
            }
        }
        
        function restartExercise() {
            // Resetar estado
            currentQuestion = 0;
            score = 0;
            selectedOption = null;
            answered = false;
            
            // Resetar interface
            if (exerciseType === 'quiz') {
                const options = document.querySelectorAll('.option');
                options.forEach(opt => {
                    opt.classList.remove('selected', 'correct', 'incorrect');
                });
            }
            
            // Resetar botões
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-check"></i> Verificar Resposta';
            
            // Esconder explicação
            const explanation = document.getElementById('explanation');
            if (explanation) {
                explanation.classList.remove('show');
            }
            
            // Voltar para tela de boas-vindas
            resultsScreen.classList.remove('active');
            welcomeScreen.classList.add('active');
        }
        
        function updateProgress() {
            const progressFill = document.getElementById('progress-fill');
            const progressText = document.getElementById('progress-text');
            
            if (progressFill && progressText) {
                const progress = ((currentQuestion + 1) / totalQuestions) * 100;
                progressFill.style.width = progress + '%';
                progressText.textContent = (currentQuestion + 1) + '/' + totalQuestions;
            }
        }

        // Encontra o botão no DOM pelo seu ID
        const botaoVoltar = document.getElementById("botaoVoltar");

        // Adiciona um ouvinte de evento de clique ao botão
        botaoVoltar.addEventListener("click", function() {
        // Chama a função history.back() para voltar à página anterior
        window.history.back();
        });
        
    </script>
</body>
</html>
