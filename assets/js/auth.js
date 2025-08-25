const express = require('express');
const bcrypt = require('bcryptjs');
const jwt = require('jsonwebtoken');
const { body, validationResult } = require('express-validator');
const database = require('../database/init');
const securityMiddleware = require('../middleware/security');

const router = express.Router();

// Middleware de validação para login
const validateLogin = [
  body('email').isEmail().normalizeEmail().withMessage('Email inválido'),
  body('senha').isLength({ min: 1 }).withMessage('Senha é obrigatória')
];

// Middleware de validação para registro
const validateRegister = [
  body('nome').isLength({ min: 2, max: 100 }).withMessage('Nome deve ter entre 2 e 100 caracteres'),
  body('email').isEmail().normalizeEmail().withMessage('Email inválido'),
  body('senha').isLength({ min: 8 }).withMessage('Senha deve ter pelo menos 8 caracteres'),
  body('tipo_usuario').isIn(['aluno', 'instrutor']).withMessage('Tipo de usuário inválido')
];

// Rota de login
router.post('/login', validateLogin, securityMiddleware.sanitizeInput, async (req, res) => {
  try {
    // Verificar erros de validação
    const errors = validationResult(req);
    if (!errors.isEmpty()) {
      return res.status(400).json({
        sucesso: false,
        mensagem: 'Dados inválidos',
        erros: errors.array()
      });
    }

    const { email, senha } = req.body;
    const ipAddress = req.ip || req.connection.remoteAddress;

    // Registrar tentativa de login
    await database.recordLoginAttempt(email, ipAddress, false);

    // Verificar se a conta está bloqueada
    const contaBloqueada = await database.isAccountLocked(email);
    if (contaBloqueada) {
      await database.insertSecurityLog(null, 'tentativa_login_conta_bloqueada', ipAddress, 
        `Tentativa de login em conta bloqueada: ${email}`, 'alto');
      
      return res.status(423).json({
        sucesso: false,
        mensagem: 'Conta temporariamente bloqueada devido a múltiplas tentativas de login'
      });
    }

    // Buscar usuário
    const usuario = await database.getUserByEmail(email);
    if (!usuario || !usuario.ativo) {
      await database.insertSecurityLog(null, 'login_usuario_inexistente', ipAddress, 
        `Tentativa de login com usuário inexistente: ${email}`, 'medio');
      
      return res.status(401).json({
        sucesso: false,
        mensagem: 'Email ou senha incorretos'
      });
    }

    // Verificar senha
    const senhaValida = await bcrypt.compare(senha, usuario.senha_hash);
    if (!senhaValida) {
      // Incrementar tentativas falhadas
      await database.incrementFailedLoginAttempts(email);
      
      // Verificar se deve bloquear a conta
      const tentativasAtuais = usuario.tentativas_login + 1;
      if (tentativasAtuais >= 5) {
        await database.lockAccount(email);
        await database.insertSecurityLog(usuario.id, 'conta_bloqueada', ipAddress, 
          `Conta bloqueada após ${tentativasAtuais} tentativas falhadas`, 'alto');
        
        return res.status(423).json({
          sucesso: false,
          mensagem: 'Conta bloqueada devido a múltiplas tentativas de login'
        });
      }

      await database.insertSecurityLog(usuario.id, 'login_senha_incorreta', ipAddress, 
        `Senha incorreta para usuário: ${email}`, 'medio');
      
      return res.status(401).json({
        sucesso: false,
        mensagem: 'Email ou senha incorretos'
      });
    }

    // Login bem-sucedido
    await database.recordLoginAttempt(email, ipAddress, true);
    await database.updateUserLoginInfo(usuario.id, ipAddress);
    
    // Gerar token JWT
    const token = jwt.sign(
      {
        id: usuario.id,
        email: usuario.email,
        tipo_usuario: usuario.tipo_usuario,
        sessionId: require('crypto').randomUUID()
      },
      process.env.JWT_SECRET,
      { expiresIn: process.env.JWT_EXPIRES_IN || '1h' }
    );

    // Log de login bem-sucedido
    await database.insertSecurityLog(usuario.id, 'login_sucesso', ipAddress, 
      `Login bem-sucedido para usuário: ${email}`, 'baixo');

    res.json({
      sucesso: true,
      mensagem: 'Login realizado com sucesso',
      dados: {
        token,
        usuario: {
          id: usuario.id,
          nome: usuario.nome,
          email: usuario.email,
          tipo_usuario: usuario.tipo_usuario
        }
      }
    });

  } catch (error) {
    console.error('Erro no login:', error);
    res.status(500).json({
      sucesso: false,
      mensagem: 'Erro interno do servidor'
    });
  }
});

// Rota de registro
router.post('/registro', validateRegister, securityMiddleware.sanitizeInput, async (req, res) => {
  try {
    // Verificar erros de validação
    const errors = validationResult(req);
    if (!errors.isEmpty()) {
      return res.status(400).json({
        sucesso: false,
        mensagem: 'Dados inválidos',
        erros: errors.array()
      });
    }

    const { nome, email, senha, tipo_usuario } = req.body;
    const ipAddress = req.ip || req.connection.remoteAddress;

    // Verificar se o email já existe
    const usuarioExistente = await database.getUserByEmail(email);
    if (usuarioExistente) {
      return res.status(409).json({
        sucesso: false,
        mensagem: 'Email já cadastrado'
      });
    }

    // Gerar hash da senha
    const saltRounds = 12;
    const senhaHash = await bcrypt.hash(senha, saltRounds);

    // Inserir novo usuário
    const [result] = await database.getConnection().execute(`
      INSERT INTO usuarios_seguros (nome, email, senha_hash, tipo_usuario, email_verificado, ativo)
      VALUES (?, ?, ?, ?, ?, ?)
    `, [nome, email, senhaHash, tipo_usuario, false, true]);

    const novoUsuarioId = result.insertId;

    // Log de registro
    await database.insertSecurityLog(novoUsuarioId, 'registro_usuario', ipAddress, 
      `Novo usuário registrado: ${email}`, 'baixo');

    res.status(201).json({
      sucesso: true,
      mensagem: 'Usuário registrado com sucesso',
      dados: {
        id: novoUsuarioId,
        nome,
        email,
        tipo_usuario
      }
    });

  } catch (error) {
    console.error('Erro no registro:', error);
    res.status(500).json({
      sucesso: false,
      mensagem: 'Erro interno do servidor'
    });
  }
});

// Rota para verificar token
router.get('/verificar', securityMiddleware.authenticateToken, async (req, res) => {
  try {
    const usuario = await database.getUserByEmail(req.user.email);
    
    if (!usuario || !usuario.ativo) {
      return res.status(401).json({
        sucesso: false,
        mensagem: 'Token inválido ou usuário inativo'
      });
    }

    res.json({
      sucesso: true,
      mensagem: 'Token válido',
      dados: {
        id: usuario.id,
        nome: usuario.nome,
        email: usuario.email,
        tipo_usuario: usuario.tipo_usuario
      }
    });

  } catch (error) {
    console.error('Erro ao verificar token:', error);
    res.status(500).json({
      sucesso: false,
      mensagem: 'Erro interno do servidor'
    });
  }
});

// Rota de logout
router.post('/logout', securityMiddleware.authenticateToken, async (req, res) => {
  try {
    const ipAddress = req.ip || req.connection.remoteAddress;
    
    // Log de logout
    await database.insertSecurityLog(req.user.id, 'logout', ipAddress, 
      `Logout realizado pelo usuário: ${req.user.email}`, 'baixo');

    res.json({
      sucesso: true,
      mensagem: 'Logout realizado com sucesso'
    });

  } catch (error) {
    console.error('Erro no logout:', error);
    res.status(500).json({
      sucesso: false,
      mensagem: 'Erro interno do servidor'
    });
  }
});

// Rota para obter informações do usuário
router.get('/perfil', securityMiddleware.authenticateToken, async (req, res) => {
  try {
    const usuario = await database.getUserByEmail(req.user.email);
    
    if (!usuario) {
      return res.status(404).json({
        sucesso: false,
        mensagem: 'Usuário não encontrado'
      });
    }

    res.json({
      sucesso: true,
      dados: {
        id: usuario.id,
        nome: usuario.nome,
        email: usuario.email,
        tipo_usuario: usuario.tipo_usuario,
        email_verificado: usuario.email_verificado,
        data_criacao: usuario.data_criacao,
        ultimo_login: usuario.ultimo_login
      }
    });

  } catch (error) {
    console.error('Erro ao obter perfil:', error);
    res.status(500).json({
      sucesso: false,
      mensagem: 'Erro interno do servidor'
    });
  }
});

module.exports = router; 