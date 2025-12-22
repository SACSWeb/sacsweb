# 🛡️ SACSWeb Educacional - Sistema de Aprendizado em Segurança Cibernética

## 📋 Visão Geral

O **SACSWeb Educacional** é um sistema completo para ensino de ataques cibernéticos e proteções, desenvolvido como projeto de TCC. O sistema oferece uma trilha de aprendizado estruturada em três níveis, com módulos práticos, exercícios interativos e acompanhamento de progresso.

## 🚀 Funcionalidades Principais

### 📚 Sistema de Módulos
- Módulos educacionais organizados por nível de dificuldade (iniciante, intermediário, avançado)
- Conteúdo teórico, prático e demonstrações de código
- Progresso automático baseado no tempo de estudo
- Sistema de pontuação e conquistas

### 🎮 Exercícios Interativos
- **Quiz**: Testes de conhecimento teórico com múltiplas alternativas
- Sistema de feedback imediato com explicações detalhadas
- Navegação questão por questão

### 📊 Dashboard Educacional
- Visão geral do progresso individual
- Estatísticas de aprendizado (módulos concluídos, pontuação total)
- Recomendações personalizadas
- Histórico de atividades
- Acesso direto aos módulos e exercícios

### 🔐 Sistema de Autenticação
- Login/registro de usuários
- Diferentes níveis de acesso (admin, professor, aluno)
- Sessões seguras com expiração automática
- Verificação de disponibilidade de email e username em tempo real

### 🎯 Sistema de Progresso
- Acompanhamento individual por módulo
- Sistema de pontuação e ranking
- Histórico completo de atividades
- Cálculo automático de progresso baseado em leitura e quiz

### 🏆 Sistema de Ranking
- Ranking de alunos com filtros por nível e período
- Exibição de acertos por quiz
- Contagem de quizzes completos
- Destaque da posição do usuário logado

### ⚙️ Sistema de Configurações
- Preferências de tema (escuro/claro/automático)
- Ajuste de tamanho de fonte
- Controle de acessibilidade (alto contraste, redução de animações)
- Configurações de notificações

## 🏗️ Arquitetura do Sistema

### 📁 Estrutura de Diretórios
```
sacsweb/
├── config/           # Configurações do sistema
├── auth/             # Autenticação e registro
├── website/          # Interface principal
├── database/         # Scripts SQL
├── assets/           # Recursos estáticos (CSS, JS, imagens)
└── logs/            # Logs do sistema
```

### 🗄️ Banco de Dados
- **MySQL** com suporte a UTF-8
- Tabelas principais:
  - `usuarios`: Usuários do sistema
  - `modulos`: Conteúdo educacional
  - `exercicios`: Atividades práticas (quizzes)
  - `progresso_usuario`: Acompanhamento individual
  - `logs_atividade`: Histórico de ações
  - `usuario_preferencias`: Preferências de acessibilidade
  - `quiz_tentativas`: Histórico de tentativas de quiz

### 💻 Tecnologias Utilizadas
- **Backend**: PHP 8.0+
- **Frontend**: HTML5, CSS3, JavaScript
- **Framework**: Bootstrap 5.3.7
- **Banco**: MySQL 5.7+
- **Servidor**: XAMPP (Apache)

## 📚 Funções Principais do Sistema

### 🔧 Configuração (`config/`)
- `connectDatabase()`: Conecta ao banco de dados MySQL
- `logMessage()`: Registra mensagens no log do sistema
- `authenticateUser()`: Autentica usuário com email e senha
- `isLoggedIn()`: Verifica se usuário está logado
- `getCurrentUser()`: Obtém dados do usuário logado
- `requireLogin()`: Redireciona se usuário não estiver logado
- `generateCSRFToken()`: Gera token CSRF para proteção
- `validateCSRFToken()`: Valida token CSRF
- `getUserPreferences()`: Obtém preferências do usuário (tema, acessibilidade)
- `getDefaultPreferences()`: Retorna preferências padrão
- `redirect()`: Redireciona para uma URL
- `showError()`: Define mensagem de erro na sessão
- `showSuccess()`: Define mensagem de sucesso na sessão
- `getFlashMessages()`: Obtém e limpa mensagens da sessão
- `sanitize()`: Sanitiza entrada do usuário
- `isAdmin()`: Verifica se usuário é administrador
- `formatarData()`: Formata data no padrão brasileiro

### 🌐 Website (`website/`)
- `dashboard.php`: Página principal com estatísticas e progresso
- `modulos.php`: Lista de módulos organizados por nível
- `modulo.php`: Visualização detalhada de um módulo
- `quiz_modulo.php`: Página dedicada para realizar quiz de um módulo
- `exercicios.php`: Lista de exercícios disponíveis
- `exercicio.php`: Visualização individual de exercício
- `ranking.php`: Ranking de alunos com filtros
- `login.php`: Página de login e registro
- `logout.php`: Processamento de logout
- `perfil.php`: Perfil do usuário
- `configuracoes.php`: Configurações de preferências
- `progresso.php`: Visualização detalhada de progresso
- `admin_modulos.php`: Administração de módulos (apenas admin)
- `functions.php`: Funções auxiliares do website

### 🎨 Assets (`assets/`)
- `css/sacsweb-unified.css`: CSS unificado com sistema de temas
- `js/preferences.js`: Gerenciamento de preferências de acessibilidade
- `images/`: Ícones e imagens do sistema

### 🗄️ Database (`database/`)
- `setup_base2.sql`: Script de criação da estrutura base do banco
- `setup_modulos.sql`: Script de inserção de módulos e exercícios

## 🚀 Instalação e Configuração

### 📋 Pré-requisitos
- XAMPP 8.0+ (Apache + MySQL + PHP)
- PHP 8.0+
- MySQL 5.7+
- Navegador moderno com JavaScript habilitado

### ⚙️ Configuração Rápida

1. **Clone o repositório**
   ```bash
   git clone [url-do-repositorio]
   cd sacsweb
   ```

2. **Configure o banco de dados**
   - Acesse `http://localhost/sacsweb/setup-database.php`
   - Clique em "Iniciar Configuração" para execução automática
   - Ou execute manualmente os scripts SQL em `database/`

3. **Configure as credenciais**
   - Edite `config/database.php` se necessário
   - Credenciais padrão: `localhost`, `root`, sem senha

4. **Acesse o sistema**
   - URL: `http://localhost/sacsweb/`
   - Usuário: `admin`
   - Senha: `admin123`

## 👥 Usuários e Permissões

### 🔑 Tipos de Usuário
- **Admin**: Acesso total ao sistema, incluindo administração de módulos
- **Professor**: Gerenciamento de módulos e alunos
- **Aluno**: Acesso aos módulos e exercícios

## 🛡️ Segurança

### Medidas Implementadas
- **Hash de senhas**: Bcrypt com salt
- **Proteção CSRF**: Tokens de validação em formulários
- **Sanitização de entrada**: Prevenção de injeção
- **Prepared statements**: Prevenção de SQL Injection
- **Escape de saída**: Prevenção de XSS
- **Controle de sessão**: Expiração automática
- **Logs de auditoria**: Rastreamento de ações

## 📚 Documentação Adicional

- **[Website](website/README.md)** - Documentação das páginas do website
- **[Config](config/README.md)** - Documentação das funções de configuração
- **[Assets](assets/README.md)** - Documentação dos recursos estáticos
- **[Database](database/README.md)** - Documentação do banco de dados

---

## 👥 Criadores Originais

Este projeto foi desenvolvido como parte do TCC por:

- **Jhonnatan Paulino Dantas de Almeida**
- **Diogo Sousa Carvalho**
- **Lívia Pavan Oliveira**
- **Gabriel Oliveira Chaves dos Santos**

---

**⚠️ Aviso Legal**: Este sistema é destinado exclusivamente para fins educacionais. O uso para testes de segurança em sistemas reais sem autorização é ilegal e não é responsabilidade dos desenvolvedores.

**🎓 SACSWeb Educacional** - Transformando o aprendizado em segurança cibernética!
