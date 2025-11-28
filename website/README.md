# Website - SACSWeb Educacional

## Visão Geral

O diretório `website/` contém todas as páginas PHP da interface web do sistema SACSWeb Educacional. Este diretório é responsável por toda a interação do usuário com o sistema.

## 📁 Arquivos e Funções

### Páginas Principais

#### `dashboard.php`
Página principal do sistema após login. Exibe:
- Estatísticas do usuário (módulos concluídos, pontuação total, progresso geral)
- Lista de módulos com progresso individual
- Últimas atividades educacionais
- Acesso rápido aos módulos e exercícios

**Funções utilizadas:**
- `requireLogin()`: Garante que usuário está logado
- `getCurrentUser()`: Obtém dados do usuário atual
- `getUserPreferences()`: Carrega preferências de tema e acessibilidade
- `connectDatabase()`: Conecta ao banco de dados
- `logMessage()`: Registra atividades

#### `modulos.php`
Lista todos os módulos educacionais organizados por nível (iniciante, intermediário, avançado). Exibe:
- Módulos disponíveis com progresso do usuário
- Estatísticas gerais (total de módulos, concluídos, pontos)
- Filtros por nível de dificuldade
- Links para visualização e quiz de cada módulo

**Funções utilizadas:**
- `requireLogin()`: Verifica autenticação
- `getCurrentUser()`: Dados do usuário
- `getUserPreferences()`: Preferências de interface
- `connectDatabase()`: Conexão com banco

#### `modulo.php`
Visualização detalhada de um módulo específico. Exibe:
- Conteúdo teórico completo do módulo
- Informações sobre tempo estimado e pontos
- Progresso atual do usuário
- Botão para iniciar quiz do módulo
- Sistema de progresso baseado em scroll

**Funções utilizadas:**
- `requireLogin()`: Autenticação
- `getCurrentUser()`: Dados do usuário
- `getUserPreferences()`: Preferências
- `connectDatabase()`: Banco de dados
- `sanitize()`: Sanitização de entrada

#### `quiz_modulo.php`
Página dedicada para realizar o quiz de um módulo. Funcionalidades:
- Navegação questão por questão
- Feedback imediato após cada resposta
- Explicações detalhadas para cada questão
- Cálculo automático de pontuação
- Salvamento de tentativas no banco
- Atualização de progresso do módulo

**Funções utilizadas:**
- `requireLogin()`: Autenticação
- `getCurrentUser()`: Dados do usuário
- `getUserPreferences()`: Preferências
- `connectDatabase()`: Banco de dados
- `validateCSRFToken()`: Proteção CSRF
- `sanitize()`: Sanitização
- `redirect()`: Redirecionamento

#### `exercicios.php`
Lista todos os exercícios disponíveis no sistema. Exibe:
- Exercícios organizados por módulo
- Tipo de exercício (quiz)
- Pontos e tempo estimado
- Status de conclusão

**Funções utilizadas:**
- `requireLogin()`: Autenticação
- `getCurrentUser()`: Dados do usuário
- `getUserPreferences()`: Preferências
- `connectDatabase()`: Banco de dados

#### `exercicio.php`
Visualização individual de um exercício específico. Exibe:
- Pergunta do exercício
- Opções de resposta (A, B, C, D)
- Feedback após resposta
- Explicação detalhada

**Funções utilizadas:**
- `requireLogin()`: Autenticação
- `getCurrentUser()`: Dados do usuário
- `getUserPreferences()`: Preferências
- `connectDatabase()`: Banco de dados
- `sanitize()`: Sanitização

#### `ranking.php`
Sistema de ranking de alunos. Exibe:
- Ranking ordenado por pontuação
- Filtros por nível e período
- Estatísticas de acertos por quiz
- Contagem de quizzes completos
- Destaque da posição do usuário logado
- Medalhas para top 3

**Funções utilizadas:**
- `requireLogin()`: Autenticação
- `getCurrentUser()`: Dados do usuário
- `getUserPreferences()`: Preferências
- `connectDatabase()`: Banco de dados
- `sanitize()`: Sanitização de filtros

#### `login.php`
Página de login e registro de usuários. Funcionalidades:
- Login com email ou username
- Registro de novos usuários
- Verificação automática de disponibilidade de email e username
- Validação de força de senha
- Modais de Termos e Privacidade
- Proteção CSRF

**Funções utilizadas:**
- `authenticateUser()`: Autenticação
- `generateCSRFToken()`: Geração de token CSRF
- `validateCSRFToken()`: Validação de token
- `connectDatabase()`: Banco de dados
- `sanitize()`: Sanitização
- `showError()` / `showSuccess()`: Mensagens flash
- `redirect()`: Redirecionamento após login

#### `logout.php`
Processamento de logout do usuário. Funcionalidades:
- Destruição da sessão
- Limpeza de dados de autenticação
- Redirecionamento para página de login

**Funções utilizadas:**
- `connectDatabase()`: Banco de dados (se necessário para limpeza)

#### `perfil.php`
Página de perfil do usuário. Exibe:
- Informações pessoais
- Estatísticas de aprendizado
- Histórico de atividades
- Opções de edição de perfil

**Funções utilizadas:**
- `requireLogin()`: Autenticação
- `getCurrentUser()`: Dados do usuário
- `getUserPreferences()`: Preferências
- `connectDatabase()`: Banco de dados

#### `configuracoes.php`
Página de configurações e preferências. Permite:
- Alterar tema (escuro/claro/automático)
- Ajustar tamanho de fonte
- Configurar acessibilidade (alto contraste, redução de animações)
- Configurar notificações

**Funções utilizadas:**
- `requireLogin()`: Autenticação
- `getCurrentUser()`: Dados do usuário
- `getUserPreferences()`: Preferências atuais
- `connectDatabase()`: Banco de dados
- `validateCSRFToken()`: Proteção CSRF
- `sanitize()`: Sanitização

#### `progresso.php`
Visualização detalhada de progresso do usuário. Exibe:
- Progresso por módulo
- Gráficos e estatísticas
- Histórico de atividades
- Tempo de estudo

**Funções utilizadas:**
- `requireLogin()`: Autenticação
- `getCurrentUser()`: Dados do usuário
- `getUserPreferences()`: Preferências
- `connectDatabase()`: Banco de dados

#### `admin_modulos.php`
Página de administração de módulos (apenas para admins). Permite:
- Visualizar todos os módulos
- Criar, editar e excluir módulos
- Gerenciar exercícios dos módulos

**Funções utilizadas:**
- `requireLogin()`: Autenticação
- `isAdmin()`: Verificação de permissão admin
- `getCurrentUser()`: Dados do usuário
- `getUserPreferences()`: Preferências
- `connectDatabase()`: Banco de dados
- `validateCSRFToken()`: Proteção CSRF
- `sanitize()`: Sanitização

#### `admin_modulos_action.php`
Processamento de ações administrativas (criar, editar, excluir módulos).

**Funções utilizadas:**
- `requireLogin()`: Autenticação
- `isAdmin()`: Verificação de permissão
- `connectDatabase()`: Banco de dados
- `validateCSRFToken()`: Proteção CSRF
- `sanitize()`: Sanitização
- `logMessage()`: Log de ações

### Arquivos Auxiliares

#### `functions.php`
Contém funções auxiliares utilizadas pelas páginas:
- `validarEmail()`: Valida formato de email
- `validarForcaSenha()`: Valida força de senha
- `gerarAvatar()`: Gera avatar a partir do nome
- `formatarData()`: Formata data
- `obterSaudacao()`: Retorna saudação baseada na hora
- `limparDados()`: Limpa dados de entrada
- `respostaJSON()`: Retorna resposta JSON
- `isAjax()`: Verifica se é requisição AJAX
- `redirecionar()`: Redireciona para URL
- `verificarLogin()`: Verifica login via cookie
- `fazerLogout()`: Processa logout
- `obterUsuarioLogado()`: Obtém usuário logado
- `verificarPermissao()`: Verifica permissões
- `registrarAtividade()`: Registra atividade no log
- `obterAtividadesRecentes()`: Obtém atividades recentes

#### `auth.php`
Página de autenticação alternativa (redireciona para login).

#### `database.php`
Conexão alternativa ao banco (não utilizado, usa `config/database.php`).

#### `index-educacional.php`
Página inicial educacional do sistema.

#### `index.html`
Página HTML estática inicial.

#### `install.php`
Página de instalação/configuração inicial.

#### `modulo_progresso.php`
Atualização de progresso do módulo via AJAX.

## 🔐 Segurança

Todas as páginas implementam:
- Verificação de autenticação (`requireLogin()`)
- Proteção CSRF em formulários
- Sanitização de entrada (`sanitize()`)
- Prepared statements para SQL
- Escape de saída HTML

## 🎨 Interface

Todas as páginas utilizam:
- Bootstrap 5.3.7 para layout responsivo
- Font Awesome para ícones
- CSS unificado (`sacsweb-unified.css`) com sistema de temas
- JavaScript para interatividade (`preferences.js`)

## 📚 Documentação Adicional

- **[README Principal](../README.md)** - Visão geral do projeto
- **[Config](../config/README.md)** - Funções de configuração
- **[Assets](../assets/README.md)** - Recursos estáticos

---

**Website SACSWeb Educacional** - Interface web completa do sistema educacional
