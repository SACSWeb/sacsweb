-- SACSWeb Educacional - Setup Base do Banco de Dados (Versão 2.1)
-- Criação do banco, tabelas principais e usuário admin
-- Inclui campos para foto de perfil, username e tabelas de preferências e ranking
-- Versão: 2.1.0

DROP DATABASE IF EXISTS `sacsweb_educacional`;
CREATE DATABASE `sacsweb_educacional` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `sacsweb_educacional`;

-- Tabela de usuários
CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `username` varchar(50) DEFAULT NULL COMMENT 'Nome de usuário único',
  `senha_hash` varchar(255) NOT NULL,
  `tipo_usuario` enum('admin','professor','aluno') NOT NULL DEFAULT 'aluno',
  `nivel_conhecimento` enum('iniciante','intermediario','avancado') DEFAULT 'iniciante',
  `foto_perfil` varchar(255) DEFAULT NULL COMMENT 'Caminho da foto de perfil do usuário',
  `data_cadastro` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `ultimo_acesso` timestamp NULL DEFAULT NULL,
  `ativo` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_usuarios_email` (`email`),
  UNIQUE KEY `uk_usuarios_username` (`username`),
  KEY `idx_usuarios_username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de módulos
CREATE TABLE `modulos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `titulo` varchar(200) NOT NULL,
  `descricao` text NOT NULL,
  `nivel` enum('iniciante','intermediario','avancado') NOT NULL DEFAULT 'iniciante',
  `tipo_ataque` varchar(100) NOT NULL,
  `conteudo_teorico` longtext NOT NULL,
  `exemplo_pratico` longtext,
  `demonstracao_codigo` longtext,
  `linguagem_codigo` varchar(50) DEFAULT 'php',
  `tempo_estimado` int(11) DEFAULT 30,
  `pontos_maximos` int(11) DEFAULT 100,
  `ordem` int(11) NOT NULL DEFAULT 0,
  `ativo` tinyint(1) NOT NULL DEFAULT 1,
  `data_criacao` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_modulos_ordem` (`ordem`),
  KEY `idx_modulos_ativo` (`ativo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de progresso dos usuários (NOVO MODELO COM QUIZ)
CREATE TABLE `progresso_usuario` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usuario_id` int(11) NOT NULL,
  `modulo_id` int(11) NOT NULL,
  `progresso` decimal(5,2) DEFAULT 0.00 COMMENT 'Progresso geral (0-100): 70% leitura + 30% quiz',
  `pontos_obtidos` int(11) DEFAULT 0,
  `tempo_gasto` int(11) DEFAULT 0 COMMENT 'Tempo total gasto no módulo (segundos)',
  `progresso_leitura` decimal(5,2) DEFAULT 0.00 COMMENT 'Progresso da leitura (0-70)',
  `progresso_quiz` decimal(5,2) DEFAULT 0.00 COMMENT 'Progresso do quiz (0-30)',
  `questoes_totais` int(11) DEFAULT 0 COMMENT 'Total de questões do quiz do módulo',
  `questoes_acertadas` int(11) DEFAULT 0 COMMENT 'Quantidade de questões acertadas',
  `porcentagem_acertos` decimal(5,2) DEFAULT 0.00 COMMENT 'Porcentagem de acertos (0-100)',
  `quiz_completo` tinyint(1) DEFAULT 0 COMMENT 'Indica se o quiz foi completado',
  `data_inicio` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `data_conclusao` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_progresso_usuario_modulo` (`usuario_id`, `modulo_id`),
  KEY `idx_progresso_progresso` (`progresso`),
  KEY `idx_progresso_quiz` (`quiz_completo`, `porcentagem_acertos`),
  CONSTRAINT `fk_progresso_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_progresso_modulo` FOREIGN KEY (`modulo_id`) REFERENCES `modulos` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de exercícios (NOVO MODELO DE QUIZ)
CREATE TABLE `exercicios` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `modulo_id` int(11) NOT NULL,
  `titulo` varchar(200) NOT NULL,
  `descricao` text NOT NULL,
  `tipo` enum('quiz','codigo','teorico') NOT NULL DEFAULT 'quiz',
  `pergunta` text NOT NULL,
  `opcoes` json NOT NULL COMMENT 'JSON com opções: {"a": "Opção A", "b": "Opção B", "c": "Opção C", "d": "Opção D"}',
  `resposta_correta` varchar(10) NOT NULL COMMENT 'Letra da resposta correta (a, b, c ou d)',
  `explicacao` text COMMENT 'Explicação da resposta correta',
  `pontos` int(11) NOT NULL DEFAULT 10,
  `tempo_estimado` int(11) DEFAULT 15,
  `ordem` int(11) NOT NULL DEFAULT 0,
  `ativo` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `idx_exercicios_modulo` (`modulo_id`),
  KEY `idx_exercicios_ordem` (`ordem`),
  KEY `idx_exercicios_tipo` (`tipo`),
  CONSTRAINT `fk_exercicios_modulo` FOREIGN KEY (`modulo_id`) REFERENCES `modulos` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de logs de atividade
CREATE TABLE `logs_atividade` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usuario_id` int(11) DEFAULT NULL,
  `modulo_id` int(11) DEFAULT NULL,
  `acao` varchar(100) NOT NULL,
  `detalhes` text,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text,
  `data_hora` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_logs_usuario` (`usuario_id`),
  KEY `idx_logs_modulo` (`modulo_id`),
  KEY `idx_logs_data` (`data_hora`),
  KEY `idx_logs_acao` (`acao`),
  CONSTRAINT `fk_logs_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_logs_modulo` FOREIGN KEY (`modulo_id`) REFERENCES `modulos` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de preferências do usuário
CREATE TABLE `usuario_preferencias` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usuario_id` int(11) NOT NULL,
  `tema` enum('dark','light','auto') DEFAULT 'dark' COMMENT 'Tema visual preferido',
  `tamanho_fonte` enum('pequeno','medio','grande') DEFAULT 'medio' COMMENT 'Tamanho da fonte',
  `alto_contraste` tinyint(1) DEFAULT 0 COMMENT 'Alto contraste ativado',
  `reduzir_animacoes` tinyint(1) DEFAULT 0 COMMENT 'Reduzir animações',
  `leitor_tela` tinyint(1) DEFAULT 0 COMMENT 'Suporte a leitor de tela',
  `espacamento` enum('compacto','normal','amplo') DEFAULT 'normal' COMMENT 'Espaçamento entre elementos',
  `densidade_info` enum('baixa','media','alta') DEFAULT 'media' COMMENT 'Densidade de informações',
  `notificacoes_email` tinyint(1) DEFAULT 1 COMMENT 'Receber notificações por email',
  `notificacoes_push` tinyint(1) DEFAULT 0 COMMENT 'Receber notificações push',
  `data_atualizacao` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_preferencias_usuario` (`usuario_id`),
  CONSTRAINT `fk_preferencias_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Preferências de acessibilidade e visualização do usuário';

-- Tabela de tentativas de quiz (NOVO MODELO)
CREATE TABLE `quiz_tentativas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usuario_id` int(11) NOT NULL,
  `modulo_id` int(11) NOT NULL,
  `questoes_totais` int(11) NOT NULL COMMENT 'Total de questões do quiz',
  `questoes_acertadas` int(11) NOT NULL COMMENT 'Quantidade de questões acertadas',
  `porcentagem_acertos` decimal(5,2) NOT NULL COMMENT 'Porcentagem de acertos (0-100)',
  `pontos_obtidos` int(11) DEFAULT 0 COMMENT 'Pontos obtidos nesta tentativa',
  `tempo_gasto` int(11) DEFAULT 0 COMMENT 'Tempo gasto em segundos',
  `respostas` json DEFAULT NULL COMMENT 'JSON com todas as respostas: {"questao_id": "resposta_selecionada"}',
  `data_realizacao` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_quiz_usuario_modulo` (`usuario_id`, `modulo_id`),
  KEY `idx_quiz_data` (`data_realizacao`),
  KEY `idx_quiz_porcentagem` (`porcentagem_acertos`),
  CONSTRAINT `fk_quiz_tentativas_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_quiz_tentativas_modulo` FOREIGN KEY (`modulo_id`) REFERENCES `modulos` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Histórico de tentativas de quiz por módulo';

-- Tabela de ranking (cache de pontuações para ranking rápido)
CREATE TABLE `ranking_usuarios` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usuario_id` int(11) NOT NULL,
  `pontuacao_total` int(11) DEFAULT 0 COMMENT 'Pontuação total acumulada',
  `modulos_concluidos` int(11) DEFAULT 0 COMMENT 'Número de módulos concluídos',
  `exercicios_concluidos` int(11) DEFAULT 0 COMMENT 'Número de exercícios concluídos',
  `quizzes_completos` int(11) DEFAULT 0 COMMENT 'Número de quizzes completados',
  `nivel_conhecimento` enum('iniciante','intermediario','avancado') DEFAULT 'iniciante',
  `posicao_geral` int(11) DEFAULT NULL COMMENT 'Posição no ranking geral',
  `posicao_nivel` int(11) DEFAULT NULL COMMENT 'Posição no ranking do nível',
  `ultima_atualizacao` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_ranking_usuario` (`usuario_id`),
  KEY `idx_ranking_pontuacao` (`pontuacao_total`),
  KEY `idx_ranking_nivel` (`nivel_conhecimento`, `pontuacao_total`),
  CONSTRAINT `fk_ranking_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Cache de pontuações para ranking de usuários';

-- Inserir usuário admin padrão
INSERT INTO `usuarios` (`nome`, `email`, `username`, `senha_hash`, `tipo_usuario`, `nivel_conhecimento`, `ativo`) VALUES
('Jhonnatan', 'jhonnatan', 'jhonnatan', '$2y$12$wPRftKPj8KIcz.fQrVFw6OeMWCmsSInbN6mxvVoNpVl8Pezs7GmsC', 'admin', 'avancado', 1);

-- Inserir usuário de teste (sem permissões extras)
INSERT INTO `usuarios` (`nome`, `email`, `username`, `senha_hash`, `tipo_usuario`, `nivel_conhecimento`, `ativo`) VALUES
('teste', 'teste', 'teste', '$2y$10$aT2YUgBVJxA5xbC29uLl6upJejG75qWOslG.IhLUqKMOnNCxgBta6', 'aluno', 'iniciante', 1);

-- Inserir log de criação do banco
INSERT INTO `logs_atividade` (`acao`, `detalhes`, `ip_address`) VALUES
('SISTEMA_INICIALIZADO', 'Banco de dados SACSWeb Educacional criado com sucesso (Versão 2.1)', '127.0.0.1');

-- Otimizações
OPTIMIZE TABLE usuarios, modulos, progresso_usuario, exercicios, logs_atividade, usuario_preferencias, quiz_tentativas, ranking_usuarios;
ANALYZE TABLE usuarios, modulos, progresso_usuario, exercicios, logs_atividade, usuario_preferencias, quiz_tentativas, ranking_usuarios;

