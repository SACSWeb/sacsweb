# 🗄️ Banco de Dados - SACSWeb Educacional

## Visão Geral

O diretório `database/` contém os scripts SQL para criação e população do banco de dados do sistema SACSWeb Educacional.

## 📁 Arquivos

### `setup_base2.sql`
Script principal de criação da estrutura base do banco de dados.

**Funcionalidades:**
- Cria o banco de dados `sacsweb_educacional` (se não existir)
- Define charset UTF-8
- Cria todas as tabelas necessárias:
  - `usuarios`: Usuários do sistema
  - `modulos`: Módulos educacionais
  - `exercicios`: Exercícios (quizzes) dos módulos
  - `progresso_usuario`: Acompanhamento de progresso por usuário
  - `logs_atividade`: Histórico de atividades
  - `usuario_preferencias`: Preferências de acessibilidade e tema
  - `quiz_tentativas`: Histórico de tentativas de quiz
- Cria índices para otimização
- Define chaves estrangeiras
- Insere usuário administrador padrão (admin/admin123)

**Estrutura das Tabelas:**

#### `usuarios`
Armazena informações dos usuários do sistema.
- `id`: ID único
- `nome`: Nome completo
- `email`: Email (único)
- `username`: Nome de usuário (único, opcional)
- `senha_hash`: Hash da senha (bcrypt)
- `tipo_usuario`: Tipo (admin, professor, aluno)
- `nivel_conhecimento`: Nível (iniciante, intermediario, avancado)
- `ativo`: Status ativo/inativo
- `data_criacao`: Data de criação

#### `modulos`
Armazena módulos educacionais.
- `id`: ID único
- `titulo`: Título do módulo
- `descricao`: Descrição
- `conteudo`: Conteúdo completo (HTML)
- `nivel`: Nível de dificuldade (iniciante, intermediario, avancado)
- `ordem`: Ordem de exibição
- `tempo_estimado`: Tempo estimado em minutos
- `pontos`: Pontos do módulo
- `ativo`: Status ativo/inativo
- `data_criacao`: Data de criação

#### `exercicios`
Armazena exercícios (quizzes) dos módulos.
- `id`: ID único
- `modulo_id`: ID do módulo (chave estrangeira)
- `titulo`: Título do exercício
- `descricao`: Descrição
- `tipo`: Tipo de exercício (quiz)
- `pergunta`: Pergunta do quiz
- `opcoes`: Opções em formato JSON (a, b, c, d)
- `resposta_correta`: Resposta correta (a, b, c ou d)
- `explicacao`: Explicação da resposta
- `pontos`: Pontos do exercício
- `tempo_estimado`: Tempo estimado em minutos
- `ordem`: Ordem de exibição
- `data_criacao`: Data de criação

#### `progresso_usuario`
Acompanha o progresso do usuário em cada módulo.
- `id`: ID único
- `usuario_id`: ID do usuário (chave estrangeira)
- `modulo_id`: ID do módulo (chave estrangeira)
- `progresso`: Percentual de progresso (0-100)
- `pontos_obtidos`: Pontos obtidos
- `tempo_gasto`: Tempo gasto em minutos
- `data_inicio`: Data de início
- `data_conclusao`: Data de conclusão
- `ultima_atualizacao`: Última atualização

#### `logs_atividade`
Registra atividades dos usuários.
- `id`: ID único
- `usuario_id`: ID do usuário (chave estrangeira)
- `acao`: Tipo de ação realizada
- `detalhes`: Detalhes em JSON
- `ip_address`: Endereço IP
- `data_hora`: Data e hora da ação

#### `usuario_preferencias`
Armazena preferências de acessibilidade e tema do usuário.
- `id`: ID único
- `usuario_id`: ID do usuário (chave estrangeira, único)
- `tema`: Tema (dark, light, auto)
- `tamanho_fonte`: Tamanho (pequeno, medio, grande)
- `alto_contraste`: Alto contraste (0 ou 1)
- `reduzir_animacoes`: Reduzir animações (0 ou 1)
- `leitor_tela`: Leitor de tela (0 ou 1)
- `espacamento`: Espaçamento (normal, amplo, compacto)
- `densidade_info`: Densidade de informações (baixa, media, alta)
- `notificacoes_email`: Notificações por email (0 ou 1)
- `notificacoes_push`: Notificações push (0 ou 1)
- `data_atualizacao`: Data de atualização

#### `quiz_tentativas`
Registra tentativas de quiz dos usuários.
- `id`: ID único
- `usuario_id`: ID do usuário (chave estrangeira)
- `modulo_id`: ID do módulo (chave estrangeira)
- `exercicio_id`: ID do exercício (chave estrangeira)
- `resposta`: Resposta fornecida
- `correta`: Se a resposta está correta (0 ou 1)
- `pontos_obtidos`: Pontos obtidos
- `data_tentativa`: Data e hora da tentativa

### `setup_modulos.sql`
Script de inserção de módulos e exercícios educacionais.

**Funcionalidades:**
- Insere módulos educacionais completos
- Insere exercícios (quizzes) para cada módulo
- Organiza por nível (iniciante, intermediário, avançado)
- Define ordem de exibição
- Configura pontos e tempo estimado

**Módulos Incluídos:**
- Módulos do nível iniciante
- Módulos do nível intermediário (se houver)
- Módulos do nível avançado (se houver)

**Estrutura dos Exercícios:**
- Cada exercício é um quiz com 4 alternativas (a, b, c, d)
- Opções armazenadas em formato JSON
- Explicação detalhada para cada questão
- Pontos e tempo estimado configurados

## 🚀 Como Usar

### Instalação Automática
1. Acesse `http://localhost/sacsweb/setup-database.php`
2. Clique em "Iniciar Configuração"
3. O sistema executará automaticamente os scripts SQL

### Instalação Manual
1. Acesse o PHPMyAdmin
2. Crie o banco `sacsweb_educacional`
3. Execute primeiro `setup_base2.sql`
4. Execute depois `setup_modulos.sql`

### Ordem de Execução
**IMPORTANTE**: Sempre execute `setup_base2.sql` antes de `setup_modulos.sql` para garantir que todas as tabelas estejam criadas.

## 🔧 Estrutura do Banco

### Relacionamentos
- `exercicios.modulo_id` → `modulos.id`
- `progresso_usuario.usuario_id` → `usuarios.id`
- `progresso_usuario.modulo_id` → `modulos.id`
- `logs_atividade.usuario_id` → `usuarios.id`
- `usuario_preferencias.usuario_id` → `usuarios.id`
- `quiz_tentativas.usuario_id` → `usuarios.id`
- `quiz_tentativas.modulo_id` → `modulos.id`
- `quiz_tentativas.exercicio_id` → `exercicios.id`

### Índices
O banco possui índices para otimização de consultas:
- Índices em chaves estrangeiras
- Índices em campos de busca frequente (email, username, nivel, etc.)

## 📊 Dados Padrão

### Usuário Administrador
- **Email**: `admin@sacsweb.com` ou `admin`
- **Senha**: `admin123`
- **Tipo**: `admin`
- **Nível**: `avancado`

## 🔐 Segurança

O banco implementa:
- **Hash de senhas**: Bcrypt para todas as senhas
- **Prepared statements**: Prevenção de SQL Injection
- **Validação de dados**: Constraints e tipos corretos
- **Chaves estrangeiras**: Integridade referencial

## 📚 Documentação Adicional

- **[README Principal](../README.md)** - Visão geral do projeto
- **[Website](../website/README.md)** - Documentação das páginas
- **[Config](../config/README.md)** - Funções de configuração
- **[Assets](../assets/README.md)** - Recursos estáticos

---

**🗄️ Banco de Dados SACSWeb Educacional** - Estrutura e scripts SQL do sistema
