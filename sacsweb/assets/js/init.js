const mysql = require('mysql2/promise');
const bcrypt = require('bcryptjs');
const path = require('path');
const fs = require('fs');

class Database {
  constructor() {
    this.connection = null;
    this.config = {
      host: process.env.DB_HOST || 'localhost',
      port: process.env.DB_PORT || 3306,
      user: process.env.DB_USER || 'sacsweb_user',
      password: process.env.DB_PASSWORD || 'SACSWeb2024!',
      database: process.env.DB_NAME || 'sacsweb',
      charset: process.env.DB_CHARSET || 'utf8mb4',
      waitForConnections: true,
      connectionLimit: 10,
      queueLimit: 0,
      acquireTimeout: 60000,
      timeout: 60000,
      reconnect: true
    };
  }

  async init() {
    try {
      // Primeiro conectar sem especificar o banco para criá-lo se não existir
      const tempConfig = { ...this.config };
      delete tempConfig.database;
      
      const tempConnection = await mysql.createConnection(tempConfig);
      
      // Criar banco de dados se não existir
      await tempConnection.execute(`CREATE DATABASE IF NOT EXISTS ${this.config.database} CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci`);
      await tempConnection.end();
      
      // Conectar ao banco específico
      this.connection = await mysql.createConnection(this.config);
      
      console.log('Conectado ao banco de dados MySQL');
      
      await this.createTables();
      await this.createDefaultAdmin();
      
      console.log('✅ Banco de dados inicializado com sucesso');
    } catch (error) {
      console.error('Erro ao inicializar banco de dados:', error);
      throw error;
    }
  }

  async createTables() {
    const tables = [
      // Tabela de usuários seguros
      `CREATE TABLE IF NOT EXISTS usuarios_seguros (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nome VARCHAR(100) NOT NULL,
        email VARCHAR(255) UNIQUE NOT NULL,
        senha_hash VARCHAR(255) NOT NULL,
        tipo_usuario ENUM('admin', 'instrutor', 'aluno') DEFAULT 'aluno',
        ativo BOOLEAN DEFAULT TRUE,
        email_verificado BOOLEAN DEFAULT FALSE,
        tentativas_login INT DEFAULT 0,
        conta_bloqueada BOOLEAN DEFAULT FALSE,
        data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        ultimo_login DATETIME,
        ip_ultimo_acesso VARCHAR(45),
        INDEX idx_email (email),
        INDEX idx_tipo_usuario (tipo_usuario),
        INDEX idx_ativo (ativo)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci`,

      // Tabela de sessões
      `CREATE TABLE IF NOT EXISTS sessoes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        id_sessao VARCHAR(255) UNIQUE NOT NULL,
        id_usuario INT,
        ip_acesso VARCHAR(45),
        data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        data_expiracao DATETIME NOT NULL,
        ativa BOOLEAN DEFAULT TRUE,
        FOREIGN KEY (id_usuario) REFERENCES usuarios_seguros(id) ON DELETE CASCADE,
        INDEX idx_id_sessao (id_sessao),
        INDEX idx_id_usuario (id_usuario),
        INDEX idx_data_expiracao (data_expiracao)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci`,

      // Tabela de logs de segurança
      `CREATE TABLE IF NOT EXISTS logs_seguranca (
        id INT AUTO_INCREMENT PRIMARY KEY,
        id_usuario INT,
        acao VARCHAR(100) NOT NULL,
        ip_acesso VARCHAR(45),
        detalhes TEXT,
        nivel_risco ENUM('baixo', 'medio', 'alto', 'critico') DEFAULT 'baixo',
        data_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (id_usuario) REFERENCES usuarios_seguros(id) ON DELETE SET NULL,
        INDEX idx_id_usuario (id_usuario),
        INDEX idx_acao (acao),
        INDEX idx_nivel_risco (nivel_risco),
        INDEX idx_data_registro (data_registro)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci`,

      // Tabela de tentativas de login
      `CREATE TABLE IF NOT EXISTS tentativas_login (
        id INT AUTO_INCREMENT PRIMARY KEY,
        email VARCHAR(255) NOT NULL,
        ip_acesso VARCHAR(45) NOT NULL,
        sucesso BOOLEAN DEFAULT FALSE,
        data_tentativa TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_email_ip (email, ip_acesso),
        INDEX idx_data_tentativa (data_tentativa),
        INDEX idx_sucesso (sucesso)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci`,

      // Tabela de tokens de autenticação
      `CREATE TABLE IF NOT EXISTS tokens_autenticacao (
        id INT AUTO_INCREMENT PRIMARY KEY,
        id_usuario INT NOT NULL,
        hash_token VARCHAR(255) NOT NULL,
        tipo_token ENUM('acesso', 'renovacao', 'reset') NOT NULL,
        data_expiracao DATETIME NOT NULL,
        revogado BOOLEAN DEFAULT FALSE,
        data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (id_usuario) REFERENCES usuarios_seguros(id) ON DELETE CASCADE,
        INDEX idx_id_usuario (id_usuario),
        INDEX idx_hash_token (hash_token),
        INDEX idx_tipo_token (tipo_token),
        INDEX idx_data_expiracao (data_expiracao)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci`,

      // Tabela de configurações de segurança
      `CREATE TABLE IF NOT EXISTS configuracoes_seguranca (
        id INT AUTO_INCREMENT PRIMARY KEY,
        chave_config VARCHAR(100) UNIQUE NOT NULL,
        valor_config TEXT NOT NULL,
        descricao TEXT,
        data_atualizacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_chave_config (chave_config)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci`
    ];

    try {
      for (let i = 0; i < tables.length; i++) {
        await this.connection.execute(tables[i]);
        console.log(`Tabela ${i + 1} criada/verificada com sucesso`);
      }
      console.log('Todas as tabelas foram criadas com sucesso');
    } catch (error) {
      console.error('Erro ao criar tabelas:', error);
      throw error;
    }
  }

  async createDefaultAdmin() {
    const adminEmail = 'admin@sacsweb.com';
    const adminPassword = 'Admin@SACS2024!';
    
    try {
      // Verificar se já existe um admin
      const [rows] = await this.connection.execute(
        'SELECT id FROM usuarios_seguros WHERE email = ?',
        [adminEmail]
      );

      if (rows.length > 0) {
        console.log('Usuário admin já existe');
        return;
      }

      // Gerar hash da senha
      const saltRounds = 12;
      const passwordHash = await bcrypt.hash(adminPassword, saltRounds);

      // Inserir admin
      await this.connection.execute(`
        INSERT INTO usuarios_seguros (nome, email, senha_hash, tipo_usuario, email_verificado, ativo)
        VALUES (?, ?, ?, ?, ?, ?)
      `, [
        'Administrador SACSWeb',
        adminEmail,
        passwordHash,
        'admin',
        1,
        1
      ]);

      console.log('✅ Usuário admin criado com sucesso');
      console.log(`📧 Email: ${adminEmail}`);
      console.log(`🔑 Senha: ${adminPassword}`);
      console.log('⚠️  IMPORTANTE: Altere a senha do admin após o primeiro login!');
    } catch (error) {
      console.error('Erro ao criar admin:', error);
      throw error;
    }
  }

  async insertSecurityLog(userId, action, ipAddress, details, riskLevel = 'baixo') {
    try {
      await this.connection.execute(`
        INSERT INTO logs_seguranca (id_usuario, acao, ip_acesso, detalhes, nivel_risco)
        VALUES (?, ?, ?, ?, ?)
      `, [userId, action, ipAddress, details, riskLevel]);
    } catch (error) {
      console.error('Erro ao inserir log de segurança:', error);
    }
  }

  async recordLoginAttempt(email, ipAddress, success) {
    try {
      await this.connection.execute(`
        INSERT INTO tentativas_login (email, ip_acesso, sucesso)
        VALUES (?, ?, ?)
      `, [email, ipAddress, success ? 1 : 0]);
    } catch (error) {
      console.error('Erro ao registrar tentativa de login:', error);
    }
  }

  async getUserByEmail(email) {
    try {
      const [rows] = await this.connection.execute(
        'SELECT * FROM usuarios_seguros WHERE email = ?',
        [email]
      );
      return rows[0] || null;
    } catch (error) {
      console.error('Erro ao buscar usuário:', error);
      throw error;
    }
  }

  async updateUserLoginInfo(userId, ipAddress) {
    try {
      await this.connection.execute(`
        UPDATE usuarios_seguros 
        SET ultimo_login = NOW(), 
            ip_ultimo_acesso = ?,
            tentativas_login = 0,
            conta_bloqueada = FALSE
        WHERE id = ?
      `, [ipAddress, userId]);
    } catch (error) {
      console.error('Erro ao atualizar informações de login:', error);
      throw error;
    }
  }

  async incrementFailedLoginAttempts(email) {
    try {
      await this.connection.execute(`
        UPDATE usuarios_seguros 
        SET tentativas_login = tentativas_login + 1
        WHERE email = ?
      `, [email]);
    } catch (error) {
      console.error('Erro ao incrementar tentativas falhadas:', error);
      throw error;
    }
  }

  async lockAccount(email) {
    try {
      await this.connection.execute(`
        UPDATE usuarios_seguros 
        SET conta_bloqueada = TRUE
        WHERE email = ?
      `, [email]);
    } catch (error) {
      console.error('Erro ao bloquear conta:', error);
      throw error;
    }
  }

  async isAccountLocked(email) {
    try {
      const [rows] = await this.connection.execute(`
        SELECT conta_bloqueada 
        FROM usuarios_seguros 
        WHERE email = ?
      `, [email]);

      if (rows.length === 0) {
        return false;
      }

      return rows[0].conta_bloqueada === 1;
    } catch (error) {
      console.error('Erro ao verificar bloqueio da conta:', error);
      throw error;
    }
  }

  async unlockAccount(email) {
    try {
      await this.connection.execute(`
        UPDATE usuarios_seguros 
        SET conta_bloqueada = FALSE,
            tentativas_login = 0
        WHERE email = ?
      `, [email]);
    } catch (error) {
      console.error('Erro ao desbloquear conta:', error);
      throw error;
    }
  }

  async close() {
    if (this.connection) {
      try {
        await this.connection.end();
        console.log('Banco de dados fechado com sucesso');
      } catch (error) {
        console.error('Erro ao fechar banco de dados:', error);
      }
    }
  }

  // Método para obter a conexão (útil para queries complexas)
  getConnection() {
    return this.connection;
  }
}

module.exports = new Database(); 