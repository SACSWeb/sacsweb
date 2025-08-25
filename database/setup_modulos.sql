-- SACSWeb Educacional - Setup Módulos
-- Arquivo para inserir módulos e exercícios educacionais
-- Execute após setup_base.sql
-- Versão: 1.0 - Apenas Nível Iniciante (corrigido para JSON_ARRAY)

USE sacsweb_educacional;

-- ==========================
-- MÓDULOS - NÍVEL INICIANTE APENAS
-- ==========================

INSERT INTO `modulos` (`titulo`, `descricao`, `nivel`, `tipo_ataque`, `conteudo_teorico`, `exemplo_pratico`, `demonstracao_codigo`, `linguagem_codigo`, `tempo_estimado`, `pontos_maximos`, `ordem`, `ativo`) VALUES
-- NÍVEL INICIANTE - 6 módulos fundamentais
('Introdução à Segurança da Web', 'Fundamentos básicos sobre vulnerabilidades web e como elas funcionam', 'iniciante', 'Geral', 'A segurança da web é fundamental para proteger aplicações e usuários. Neste módulo, você aprenderá o que são vulnerabilidades, como elas são exploradas e por que são perigosas. A segurança web abrange todos os aspectos de proteção de aplicações web contra ataques cibernéticos, incluindo proteção de dados, autenticação segura e prevenção de vulnerabilidades comuns.', 'Vamos analisar um site real que foi hackeado para entender o impacto das vulnerabilidades. Sites como Yahoo, Equifax e outros grandes portais já foram comprometidos por falhas de segurança básicas.', '<!DOCTYPE html>
<html>
<head>
    <title>Site Vulnerável - Exemplo</title>
    <meta charset="utf-8">
</head>
<body>
    <h1>Bem-vindo ao Site de Exemplo</h1>
    <p>Este site demonstra vulnerabilidades comuns:</p>
    <ul>
        <li>Formulários sem validação</li>
        <li>Mensagens de erro que revelam informações</li>
        <li>Cookies não seguros</li>
    </ul>
    <script>
        // Código JavaScript que pode ser explorado
        var userInput = location.hash.substring(1);
        document.write("Olá, " + userInput);
    </script>
</body>
</html>', 'html', 30, 100, 1, 1),

('OWASP Top 10 - Visão Geral', 'Conheça as 10 vulnerabilidades mais críticas da web segundo a OWASP', 'iniciante', 'OWASP', 'A OWASP (Open Web Application Security Project) mantém uma lista das 10 vulnerabilidades mais críticas que afetam aplicações web. Este módulo apresenta uma visão geral de cada uma delas, explicando por que são perigosas e como podem ser exploradas. O OWASP Top 10 é atualizado regularmente e serve como referência para desenvolvedores e profissionais de segurança.', 'Analisaremos casos reais de cada vulnerabilidade do Top 10, incluindo exemplos de sites que foram comprometidos por essas falhas. Veremos como atacantes exploram essas vulnerabilidades em aplicações reais.', '// Exemplo de vulnerabilidade OWASP Top 1 - Broken Access Control
// Código vulnerável que permite acesso não autorizado
function getUserProfile(userId) {
    // SEM VERIFICAÇÃO DE PERMISSÃO!
    return database.query("SELECT * FROM users WHERE id = " + userId);
}

// Exemplo de vulnerabilidade OWASP Top 2 - Cryptographic Failures
// Senha armazenada em texto plano
$password = $_POST["password"];
$query = "INSERT INTO users (username, password) VALUES (\'$username\', \'$password\')";

// Exemplo de vulnerabilidade OWASP Top 3 - Injection
// SQL Injection básico
$userInput = $_GET["id"];
$query = "SELECT * FROM products WHERE id = " . $userInput;', 'javascript', 45, 120, 2, 1),

('Fundamentos de HTTP e Sessões', 'Entenda como funcionam as requisições HTTP e o gerenciamento de sessões', 'iniciante', 'HTTP', 'HTTP (HyperText Transfer Protocol) é o protocolo base da web. Entender como funcionam requisições, respostas, cookies e sessões é essencial para identificar vulnerabilidades. Este módulo explica os conceitos fundamentais de comunicação web, incluindo métodos HTTP, headers, cookies de sessão e como os atacantes podem interceptar e manipular essas comunicações.', 'Vamos interceptar e analisar requisições HTTP usando ferramentas de desenvolvedor do navegador. Veremos como cookies são enviados, como sessões são gerenciadas e como essas informações podem ser comprometidas.', 'GET /login.php HTTP/1.1
Host: example.com
Cookie: session=abc123; user_id=456
User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64)
Accept: text/html,application/xhtml+xml
Referer: https://example.com/

username=admin&password=test123

// Exemplo de resposta HTTP
HTTP/1.1 200 OK
Set-Cookie: session=new_session_id; HttpOnly; Secure
Content-Type: text/html
Content-Length: 1234

<!DOCTYPE html>
<html>
<head><title>Login</title></head>
<body>
    <h1>Bem-vindo, admin!</h1>
</body>
</html>', 'http', 40, 110, 3, 1),

('Introdução a XSS (Cross-Site Scripting)', 'Aprenda sobre ataques XSS e como eles funcionam', 'iniciante', 'XSS', 'XSS (Cross-Site Scripting) permite que atacantes injetem código JavaScript malicioso em páginas web. Este módulo explica os conceitos básicos e tipos de XSS, incluindo XSS Refletido, Armazenado e DOM-Based. Você aprenderá como identificar vulnerabilidades XSS, como elas são exploradas e quais são os impactos práticos para usuários e aplicações.', 'Vamos criar um exemplo de XSS refletido e ver como funciona na prática. Criaremos uma página vulnerável que reflete entrada do usuário sem sanitização, permitindo a execução de código malicioso.', '<form action="search.php" method="GET">
    <input type="text" name="q" value="<?php echo $_GET["q"]; ?>">
    <input type="submit" value="Buscar">
</form>

<div id="results">
    <?php
    if (isset($_GET["q"])) {
        echo "Resultados para: " . $_GET["q"];
    }
    ?>
</div>', 'php', 50, 130, 4, 1),

('Introdução a SQL Injection', 'Conceitos básicos de injeção SQL e como explorar vulnerabilidades', 'iniciante', 'SQL Injection', 'SQL Injection permite que atacantes manipulem consultas de banco de dados inserindo código SQL malicioso. Este módulo explica os conceitos básicos, incluindo como identificar vulnerabilidades, diferentes tipos de injeção SQL e como elas podem ser exploradas. Você aprenderá sobre injeção em formulários de login, busca e outras funcionalidades que interagem com bancos de dados.', 'Vamos analisar um formulário de login vulnerável e ver como explorá-lo. Criaremos uma aplicação PHP com vulnerabilidades SQL Injection e demonstraremos diferentes técnicas de exploração.', '// CÓDIGO VULNERÁVEL - NÃO USE EM PRODUÇÃO!
<?php
if ($_POST["login"]) {
    $username = $_POST["username"];
    $password = $_POST["password"];
    $query = "SELECT * FROM users WHERE username = \"" . $username . "\" AND password = \"" . $password . "\"";
    $result = mysql_query($query);
    if (mysql_num_rows($result) > 0) {
        echo "Login bem-sucedido!";
        $_SESSION["logged_in"] = true;
    } else {
        echo "Usuário ou senha incorretos.";
    }
}
?>', 'php', 55, 140, 5, 1),

('Noções de Validação e Sanitização', 'Por que validar entrada do usuário e como fazer corretamente', 'iniciante', 'Geral', 'A validação e sanitização de dados são fundamentais para a segurança web. Este módulo explica as diferenças entre whitelist e blacklist, erros comuns em formulários e como implementar validações seguras.', 'Vamos comparar formulários seguros e inseguros para entender a diferença. Criaremos exemplos práticos de validação inadequada e implementaremos soluções seguras.', '<?php
// VALIDAÇÃO SEGURA
function validateInputWhitelist($input) {
    if (!preg_match("/^[a-zA-Z0-9\\s]+$/", $input)) {
        die("Caracteres inválidos detectados!");
    }
    return $input;
}
?>', 'php', 35, 100, 6, 1);

-- ==========================
-- EXERCÍCIOS PARA OS MÓDULOS INICIANTES
-- ==========================

INSERT INTO `exercicios` 
(`modulo_id`, `titulo`, `descricao`, `tipo`, `pergunta`, `opcoes`, `resposta_correta`, `explicacao`, `pontos`, `tempo_estimado`, `ordem`) VALUES
-- Exercícios para Módulo 1
(1, 'Quiz: O que são Vulnerabilidades?', 'Teste seus conhecimentos sobre vulnerabilidades básicas', 'quiz',
 'Qual é a definição correta de uma vulnerabilidade web?',
 JSON_ARRAY('a', 'Um bug no código que permite ataque', 'b', 'Uma função segura do sistema', 'c', 'Um recurso do navegador', 'd', 'Uma configuração padrão'),
 'a',
 'Uma vulnerabilidade é um ponto fraco em um sistema que pode ser explorado por um atacante para comprometer a segurança.', 10, 15, 1),

(1, 'Quiz: Impacto das Vulnerabilidades', 'Entenda o impacto das vulnerabilidades em sistemas web', 'quiz',
 'Qual é o impacto mais comum de uma vulnerabilidade web?',
 JSON_ARRAY('a', 'Melhoria no desempenho', 'b', 'Comprometimento de dados', 'c', 'Atualização automática', 'd', 'Backup automático'),
 'b',
 'Vulnerabilidades web frequentemente resultam no comprometimento de dados sensíveis.', 15, 20, 2),

-- Exercícios para Módulo 2
(2, 'Quiz: OWASP Top 10', 'Teste seu conhecimento sobre OWASP Top 10', 'quiz',
 'Qual vulnerabilidade é a mais crítica segundo OWASP 2021?',
 JSON_ARRAY('a', 'XSS', 'b', 'SQL Injection', 'c', 'Broken Access Control', 'd', 'CSRF'),
 'c',
 'O OWASP Top 10 lista as vulnerabilidades mais críticas; Broken Access Control é uma das mais exploradas.', 15, 20, 1),

(2, 'Quiz: Vulnerabilidades OWASP', 'Identifique vulnerabilidades comuns do OWASP', 'quiz',
 'Qual das seguintes é uma vulnerabilidade OWASP Top 10?',
 JSON_ARRAY('a', 'Falta de cores no site', 'b', 'Injection de código malicioso', 'c', 'Fonte muito pequena', 'd', 'Layout responsivo'),
 'b',
 'Injection (SQL, NoSQL, LDAP, etc.) é uma das vulnerabilidades mais críticas.', 15, 20, 2),

-- Exercícios para Módulo 3
(3, 'Quiz: Comandos HTTP', 'Teste seu conhecimento sobre comandos HTTP', 'quiz',
 'Qual comando permite visualizar headers HTTP no terminal?',
 JSON_ARRAY('a', 'curl -I http://site.com', 'b', 'ls -la', 'c', 'ping site.com', 'd', 'dir'),
 'a',
 'Curl com -I permite visualizar headers HTTP sem baixar o conteúdo.', 15, 20, 1),

(3, 'Quiz: Cookies e Sessões', 'Entenda como funcionam cookies e sessões', 'quiz',
 'O que é um cookie de sessão?',
 JSON_ARRAY('a', 'Arquivo de texto no computador', 'b', 'Identificador único para manter estado', 'c', 'Programa antivírus', 'd', 'Tipo de vírus'),
 'b',
 'Cookies de sessão permitem ao servidor reconhecer usuários entre requisições.', 15, 20, 2),

-- Exercícios para Módulo 4 - Quiz Completo sobre XSS
(4, 'Quiz: Introdução a XSS (Cross-Site Scripting)', 'Teste completo sobre XSS com 10 questões', 'quiz',
 'O que é XSS (Cross-Site Scripting)?',
 JSON_ARRAY('a', 'Um ataque que explora falhas em redes sem fio', 'b', 'Um ataque que insere código malicioso em páginas web', 'c', 'Um tipo de SQL Injection', 'd', 'Uma forma de autenticação de dois fatores'),
 'b',
 'XSS é um ataque que permite injetar código malicioso (geralmente JavaScript) em páginas web.', 25, 30, 1),

(4, 'Quiz: Objetivo do XSS', 'Entenda o principal objetivo dos ataques XSS', 'quiz',
 'Qual é o principal objetivo de um ataque XSS?',
 JSON_ARRAY('a', 'Criptografar dados do usuário', 'b', 'Roubar informações de sessão ou executar scripts maliciosos', 'c', 'Aumentar a velocidade do site', 'd', 'Prevenir ataques de injeção'),
 'b',
 'O objetivo principal é roubar informações de sessão, cookies ou executar ações em nome da vítima.', 25, 30, 2),

(4, 'Quiz: Linguagem do XSS', 'Identifique a linguagem usada em ataques XSS', 'quiz',
 'Em qual linguagem normalmente é injetado o código malicioso em ataques XSS?',
 JSON_ARRAY('a', 'JavaScript', 'b', 'Python', 'c', 'SQL', 'd', 'PHP'),
 'a',
 'JavaScript é a linguagem mais comum para ataques XSS, pois é executada no navegador da vítima.', 25, 30, 3),

(4, 'Quiz: XSS Refletido vs Armazenado', 'Diferencie os tipos de XSS', 'quiz',
 'Qual é a diferença entre XSS Refletido e Armazenado?',
 JSON_ARRAY('a', 'Refletido afeta apenas servidores, armazenado afeta navegadores', 'b', 'Refletido injeta dados em APIs, armazenado em bancos de dados', 'c', 'Refletido é temporário (via URL ou input), armazenado fica gravado no servidor/banco', 'd', 'Não existe diferença, são o mesmo ataque'),
 'c',
 'XSS Refletido é temporário e via URL/input, enquanto XSS Armazenado fica persistente no servidor.', 25, 30, 4),

(4, 'Quiz: Payload XSS', 'Identifique exemplos de payload XSS', 'quiz',
 'Qual das seguintes opções é um exemplo de payload XSS simples?',
 JSON_ARRAY('a', '<script>alert(\'XSS\')</script>', 'b', 'DROP TABLE users;', 'c', '../etc/passwd', 'd', 'rm -rf /'),
 'a',
 '<script>alert(\'XSS\')</script> é um payload XSS clássico que executa JavaScript no navegador.', 25, 30, 5),

(4, 'Quiz: Usos do XSS', 'Entenda o que pode ser feito com XSS', 'quiz',
 'Um ataque XSS pode ser usado para:',
 JSON_ARRAY('a', 'Desligar fisicamente o servidor', 'b', 'Roubar cookies de autenticação', 'c', 'Substituir senhas no banco de dados diretamente', 'd', 'Desfragmentar o disco do servidor'),
 'b',
 'XSS pode roubar cookies de autenticação, permitindo acesso não autorizado a contas.', 25, 30, 6),

(4, 'Quiz: Prevenção XSS', 'Aprenda técnicas de prevenção', 'quiz',
 'Qual técnica ajuda a prevenir XSS?',
 JSON_ARRAY('a', 'Criptografar o banco de dados', 'b', 'Usar VPN no servidor', 'c', 'Validar e escapar entradas do usuário corretamente', 'd', 'Ativar backup automático'),
 'c',
 'Validar e escapar entradas do usuário é fundamental para prevenir XSS.', 25, 30, 7),

(4, 'Quiz: XSS DOM-Based', 'Entenda XSS DOM-Based', 'quiz',
 'O que é XSS DOM-Based?',
 JSON_ARRAY('a', 'Quando o ataque altera o sistema operacional do servidor', 'b', 'Quando o ataque ocorre apenas em bancos de dados', 'c', 'Quando o script malicioso é executado manipulando o DOM no navegador da vítima', 'd', 'Um ataque exclusivo contra servidores Apache'),
 'c',
 'XSS DOM-Based ocorre quando o script malicioso manipula o DOM no navegador da vítima.', 25, 30, 8),

(4, 'Quiz: Headers de Segurança', 'Conheça headers que previnem XSS', 'quiz',
 'Qual cabeçalho HTTP pode ajudar a mitigar XSS?',
 JSON_ARRAY('a', 'X-Frame-Options', 'b', 'Content-Security-Policy (CSP)', 'c', 'Strict-Transport-Security', 'd', 'Server: Apache'),
 'b',
 'Content-Security-Policy (CSP) é um header que ajuda a prevenir XSS controlando recursos executáveis.', 25, 30, 9),

(4, 'Quiz: Gravidade do XSS', 'Entenda a gravidade dos ataques XSS', 'quiz',
 'Qual das opções descreve melhor a gravidade do XSS?',
 JSON_ARRAY('a', 'É um ataque inofensivo, apenas mostra pop-ups', 'b', 'Pode comprometer contas de usuários, roubar dados e executar ações em nome da vítima', 'c', 'Só funciona em navegadores antigos', 'd', 'Apenas causa lentidão no site'),
 'b',
 'XSS é muito grave pois pode comprometer contas, roubar dados e executar ações em nome da vítima.', 25, 30, 10),

-- Exercícios para Módulo 5
(5, 'Quiz: SQL Injection Blind', 'Teste seu conhecimento sobre SQL Injection cego', 'quiz',
 'Qual técnica permite descobrir dados sem retorno visível?',
 JSON_ARRAY('a', 'Union-based', 'b', 'Boolean-based', 'c', 'DOM-Based XSS', 'd', 'CSRF'),
 'b',
 'Boolean-based SQL Injection permite inferir informações usando respostas booleanas.', 20, 25, 1),

(5, 'Quiz: Prevenção SQL Injection', 'Aprenda como prevenir SQL Injection', 'quiz',
 'Qual é a melhor forma de prevenir SQL Injection?',
 JSON_ARRAY('a', 'Usar senhas fortes', 'b', 'Prepared Statements', 'c', 'Firewall', 'd', 'Antivírus'),
 'b',
 'Prepared Statements separam dados de código SQL.', 20, 25, 2),

-- Exercícios para Módulo 6
(6, 'Quiz: Validação Segura', 'Teste seu conhecimento sobre validação segura', 'quiz',
 'Qual função PHP é mais segura para escapar saída HTML?',
 JSON_ARRAY('a', 'echo()', 'b', 'htmlspecialchars()', 'c', 'print()', 'd', 'printf()'),
 'b',
 'Use htmlspecialchars() para escapar saída HTML e prevenir XSS.', 20, 25, 1),

(6, 'Quiz: Whitelist vs Blacklist', 'Entenda a diferença entre abordagens de validação', 'quiz',
 'Qual abordagem de validação é mais segura?',
 JSON_ARRAY('a', 'Blacklist', 'b', 'Whitelist', 'c', 'Ambas são iguais', 'd', 'Nenhuma das duas'),
 'b',
 'Whitelist é mais segura pois define exatamente o que é permitido.', 15, 20, 2);

-- Mensagem de conclusão
SELECT 'Módulos iniciantes inseridos com sucesso!' AS mensagem;
SELECT COUNT(*) AS total_modulos FROM modulos;
SELECT COUNT(*) AS total_exercicios FROM exercicios;
SELECT 'Apenas módulos do nível INICIANTE foram criados' AS observacao;
