// Dados do Quiz
const quizData = [
    {
        question: "O que é Phishing?",
        options: {
            a: "Um antivírus moderno.",
            b: "Um golpe cibernético que visa roubar dados pessoais.",
            c: "Um sistema de proteção de rede.",
            d: "Um método de autenticação em dois fatores."
        },
        correct: "b"
    },
    {
        question: "Como geralmente funciona o Phishing?",
        options: {
            a: "Por links enviados por mensagens falsas.",
            b: "Por atualização automática do sistema.",
            c: "Por criptografia de arquivos.",
            d: "Por bloqueio de sites maliciosos."
        },
        correct: "b"
    },
    {
        question: "Uma forma de evitar Phishing é:",
        options: {
            a: "Clicar em qualquer link recebido.",
            b: "Verificar o endereço real dos links.",
            c: "Compartilhar senhas com colegas.",
            d: "Instalar softwares piratas."
        },
        correct: "b"
    },
    {
        question: "O que é um ataque de SQL Injection?",
        options: {
            a: "Uso de antivírus falsos.",
            b: "Código inserido que manipula comandos SQL.",
            c: "Uma senha muito fraca.",
            d: "Atualização de banco de dados."
        },
        correct: "b"
    },
    {
        question: "Como o SQL Injection funciona?",
        options: {
            a: "Enviando mensagens falsas.",
            b: "Por execução de comandos SQL maliciosos inseridos.",
            c: "Por força bruta em senhas.",
            d: "Por instalar programas maliciosos."
        },
        correct: "b"
    },
    {
        question: "Para evitar SQL Injection, é importante:",
        options: {
            a: "Usar senhas simples.",
            b: "Validar entradas e usar comandos preparados.",
            c: "Clicar em links de e-mail.",
            d: "Compartilhar senhas."
        },
        correct: "b"
    },
    {
        question: "O que é um ataque de força bruta?",
        options: {
            a: "Um ataque físico ao servidor.",
            b: "Tentativas repetidas para descobrir senhas.",
            c: "Uso de antivírus.",
            d: "Enviar e-mails falsos."
        },
        correct: "b"
    },
    {
        question: "Como funciona a força bruta?",
        options: {
            a: "Advinha senhas por tentativa e erro.",
            b: "Instala programas legítimos.",
            c: "Desliga o computador da vítima.",
            d: "Altera configurações do antivírus."
        },
        correct: "a"
    },
    {
        question: "Como evitar ataques de força bruta?",
        options: {
            a: "Usar senhas fracas.",
            b: "Desativar contas inativas e usar autenticação em dois fatores.",
            c: "Compartilhar senhas com amigos.",
            d: "Deixar o login aberto."
        },
        correct: "b"
    },
    {
        question: "O que é XSS (Cross-Site Scripting)?",
        options: {
            a: "Inserção de script malicioso em site vulnerável.",
            b: "Um tipo de antivírus.",
            c: "Um ataque por força bruta.",
            d: "Uma senha segura."
        },
        correct: "a"
    },
    {
        question: "Qual o objetivo do XSS?",
        options: {
            a: "Acelerar o computador.",
            b: "Roubar informações do usuário.",
            c: "Proteger dados pessoais.",
            d: "Atualizar o navegador."
        },
        correct: "b"
    },
    {
        question: "Como o XSS funciona?",
        options: {
            a: "Com códigos maliciosos executados pelo navegador.",
            b: "Instalando atualizações automáticas.",
            c: "Por e-mails falsos.",
            d: "Por senhas fortes."
        },
        correct: "a"
    },
    {
        question: "Como evitar XSS como desenvolvedor?",
        options: {
            a: "Ignorar entradas do usuário.",
            b: "Filtrar entradas e atualizar sistemas.",
            c: "Usar apenas senhas curtas.",
            d: "Desabilitar o antivírus."
        },
        correct: "b"
    },
    {
        question: "Como evitar XSS como usuário?",
        options: {
            a: "Clicar em todos os links.",
            b: "Digitar manualmente o endereço do site.",
            c: "Compartilhar informações pessoais em qualquer site.",
            d: "Usar senhas fracas."
        },
        correct: "b"
    },
    {
        question: "O que fazer ao sofrer um ataque cibernético?",
        options: {
            a: "Ignorar o problema.",
            b: "Trocar senhas e acionar autoridades.",
            c: "Compartilhar mais dados para despistar.",
            d: "Deixar como está."
        },
        correct: "b"
    }
];

// Variáveis globais
let currentQuestion = 0;
let score = 0;
let selectedAnswer = null;
let quizCompleted = false;

// Elementos do DOM
const welcomeScreen = document.getElementById('welcome-screen');
const quizScreen = document.getElementById('quiz-screen');
const resultsScreen = document.getElementById('results-screen');
const startBtn = document.getElementById('start-btn');
const nextBtn = document.getElementById('next-btn');
const restartBtn = document.getElementById('restart-btn');
const homeBtn = document.getElementById('home-btn');
const questionText = document.getElementById('question-text');
const options = document.querySelectorAll('.option');
const progressFill = document.getElementById('progress-fill');
const progressText = document.getElementById('progress-text');
const scoreElement = document.getElementById('score');
const finalScore = document.getElementById('final-score');
const percentage = document.getElementById('percentage');
const scoreTitle = document.getElementById('score-title');
const scoreDescription = document.getElementById('score-description');

// Event Listeners
startBtn.addEventListener('click', startQuiz);
nextBtn.addEventListener('click', nextQuestion);
restartBtn.addEventListener('click', restartQuiz);
homeBtn.addEventListener('click', goHome);

options.forEach(option => {
    option.addEventListener('click', () => selectOption(option));
});

// Funções
function startQuiz() {
    currentQuestion = 0;
    score = 0;
    selectedAnswer = null;
    quizCompleted = false;
    
    showScreen(quizScreen);
    loadQuestion();
    updateProgress();
    updateScore();
}

function loadQuestion() {
    const question = quizData[currentQuestion];
    questionText.textContent = question.question;
    
    options.forEach((option, index) => {
        const optionLetter = String.fromCharCode(97 + index); // a, b, c, d
        const optionText = option.querySelector('.option-text');
        optionText.textContent = question.options[optionLetter];
        option.dataset.option = optionLetter;
        
        // Reset classes
        option.classList.remove('selected', 'correct', 'incorrect');
        option.disabled = false;
    });
    
    selectedAnswer = null;
    nextBtn.disabled = true;
}

function selectOption(selectedOption) {
    if (quizCompleted) return;
    
    // Remove seleção anterior
    options.forEach(option => {
        option.classList.remove('selected');
    });
    
    // Seleciona nova opção
    selectedOption.classList.add('selected');
    selectedAnswer = selectedOption.dataset.option;
    
    // Habilita botão próximo
    nextBtn.disabled = false;
}

function nextQuestion() {
    if (selectedAnswer === null) return;
    
    // Verifica resposta
    const question = quizData[currentQuestion];
    const isCorrect = selectedAnswer === question.correct;
    
    if (isCorrect) {
        score++;
    }
    
    // Mostra feedback visual
    options.forEach(option => {
        const optionLetter = option.dataset.option;
        option.disabled = true;
        
        if (optionLetter === question.correct) {
            option.classList.add('correct');
        } else if (optionLetter === selectedAnswer && !isCorrect) {
            option.classList.add('incorrect');
        }
    });
    
    // Aguarda um pouco antes de ir para próxima pergunta
    setTimeout(() => {
        currentQuestion++;
        
        if (currentQuestion < quizData.length) {
            loadQuestion();
            updateProgress();
            updateScore();
        } else {
            showResults();
        }
    }, 1500);
}

function showResults() {
    quizCompleted = true;
    const percentageScore = Math.round((score / quizData.length) * 100);
    
    finalScore.textContent = score;
    percentage.textContent = `${percentageScore}%`;
    
    // Define mensagem baseada na pontuação
    if (percentageScore >= 90) {
        scoreTitle.textContent = "Excelente!";
        scoreDescription.textContent = "Você demonstrou um conhecimento excepcional sobre segurança cibernética! Continue assim!";
    } else if (percentageScore >= 70) {
        scoreTitle.textContent = "Muito Bom!";
        scoreDescription.textContent = "Você tem um bom conhecimento sobre segurança cibernética. Continue estudando!";
    } else if (percentageScore >= 50) {
        scoreTitle.textContent = "Bom!";
        scoreDescription.textContent = "Você tem conhecimentos básicos sobre segurança cibernética. Há espaço para melhorar!";
    } else {
        scoreTitle.textContent = "Precisa Melhorar";
        scoreDescription.textContent = "É importante estudar mais sobre segurança cibernética para se proteger melhor!";
    }
    
    showScreen(resultsScreen);
}

function updateProgress() {
    const progress = ((currentQuestion + 1) / quizData.length) * 100;
    progressFill.style.width = `${progress}%`;
    progressText.textContent = `${currentQuestion + 1}/${quizData.length}`;
}

function updateScore() {
    scoreElement.textContent = score;
}

function showScreen(screen) {
    // Esconde todas as telas
    welcomeScreen.classList.remove('active');
    quizScreen.classList.remove('active');
    resultsScreen.classList.remove('active');
    
    // Mostra a tela desejada
    screen.classList.add('active');
}

function restartQuiz() {
    startQuiz();
}

function goHome() {
    showScreen(welcomeScreen);
}

// Inicialização
document.addEventListener('DOMContentLoaded', () => {
    // Garante que a tela de boas-vindas está visível
    showScreen(welcomeScreen);
}); 