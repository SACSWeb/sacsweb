-- SACSWeb Educacional - Setup Base do Banco de Dados
-- Criação do banco, tabelas principais e usuário admin
-- Versão: 2.0.0

DROP DATABASE IF EXISTS `sacsweb_educacional`;
CREATE DATABASE `sacsweb_educacional` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `sacsweb_educacional`;

-- Tabela de usuários
CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `username` varchar(50) NOT NULL,
  `senha_hash` varchar(255) NOT NULL,
  `tipo_usuario` enum('admin','professor','aluno') NOT NULL DEFAULT 'aluno',
  `nivel_conhecimento` enum('iniciante','intermediario','avancado') DEFAULT 'iniciante',
  `data_cadastro` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `ultimo_acesso` timestamp NULL DEFAULT NULL,
  `ativo` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_usuarios_email` (`email`),
  UNIQUE KEY `uk_usuarios_username` (`username`)
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

-- Tabela de progresso dos usuários
CREATE TABLE `progresso_usuario` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usuario_id` int(11) NOT NULL,
  `modulo_id` int(11) NOT NULL,
  `progresso` decimal(5,2) DEFAULT 0.00,
  `pontos_obtidos` int(11) DEFAULT 0,
  `tempo_gasto` int(11) DEFAULT 0,
  `data_inicio` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `data_conclusao` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_progresso_usuario_modulo` (`usuario_id`, `modulo_id`),
  KEY `idx_progresso_progresso` (`progresso`),
  CONSTRAINT `fk_progresso_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_progresso_modulo` FOREIGN KEY (`modulo_id`) REFERENCES `modulos` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de exercícios
CREATE TABLE `exercicios` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `modulo_id` int(11) NOT NULL,
  `titulo` varchar(200) NOT NULL,
  `descricao` text NOT NULL,
  `tipo` enum('quiz','codigo','teorico') NOT NULL DEFAULT 'quiz',
  `pergunta` text NOT NULL,
  `opcoes` json DEFAULT NULL,
  `resposta_correta` text NOT NULL,
  `explicacao` text,
  `pontos` int(11) NOT NULL DEFAULT 10,
  `tempo_estimado` int(11) DEFAULT 15,
  `ordem` int(11) NOT NULL DEFAULT 0,
  `ativo` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `idx_exercicios_modulo` (`modulo_id`),
  KEY `idx_exercicios_ordem` (`ordem`),
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

-- Inserir usuário admin padrão
INSERT INTO `usuarios` (`nome`, `email`, `username`, `senha_hash`, `tipo_usuario`, `nivel_conhecimento`, `ativo`) VALUES
('Administrador', 'admin@sacsweb.com', 'admin', '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'avancado', 1);

-- Inserir log de criação do banco
INSERT INTO `logs_atividade` (`acao`, `detalhes`, `ip_address`) VALUES
('SISTEMA_INICIALIZADO', 'Banco de dados SACSWeb Educacional criado com sucesso', '127.0.0.1');

-- Otimizações
OPTIMIZE TABLE usuarios, modulos, progresso_usuario, exercicios, logs_atividade;
ANALYZE TABLE usuarios, modulos, progresso_usuario, exercicios, logs_atividade;
