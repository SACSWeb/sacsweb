import React from 'react';

const Dashboard = ({ user }) => {
  const securityFeatures = [
    {
      icon: '🛡️',
      title: 'SQL Injection Protection',
      description: 'Detecção e bloqueio de tentativas de SQL Injection usando padrões maliciosos e prepared statements.'
    },
    {
      icon: '🚫',
      title: 'XSS Protection',
      description: 'Sanitização de entrada e headers de segurança para prevenir Cross-Site Scripting.'
    },
    {
      icon: '🔒',
      title: 'Brute Force Protection',
      description: 'Bloqueio automático de contas após múltiplas tentativas de login falhadas.'
    },
    {
      icon: '⏱️',
      title: 'Rate Limiting',
      description: 'Limitação de requisições por IP para prevenir ataques de DDoS e força bruta.'
    },
    {
      icon: '🔐',
      title: 'Secure Authentication',
      description: 'Hash de senhas com bcrypt, tokens JWT seguros e sessões protegidas.'
    },
    {
      icon: '📊',
      title: 'Security Logging',
      description: 'Logs detalhados de todas as ações para auditoria e detecção de atividades suspeitas.'
    }
  ];

  return (
    <div className="dashboard-container">
      <div className="container">
        <div className="dashboard-header">
          <h1 className="dashboard-title">
            Bem-vindo ao SACSWeb, {user?.name}!
          </h1>
          <p className="dashboard-subtitle">
            Sistema de Segurança Cibernética - Protegendo contra ataques modernos
          </p>
        </div>

        <div className="dashboard-grid">
          <div className="dashboard-card">
            <h3>👤 Informações da Conta</h3>
            <p><strong>Nome:</strong> {user?.name}</p>
            <p><strong>Email:</strong> {user?.email}</p>
            <p><strong>Função:</strong> {user?.role === 'admin' ? 'Administrador' : user?.role === 'instructor' ? 'Instrutor' : 'Estudante'}</p>
            <p><strong>Status:</strong> <span className="status-active">Ativo</span></p>
          </div>

          <div className="dashboard-card">
            <h3>🔒 Status de Segurança</h3>
            <p><strong>Autenticação:</strong> <span className="status-secure">Segura</span></p>
            <p><strong>Último Login:</strong> {new Date().toLocaleString('pt-BR')}</p>
            <p><strong>Proteções Ativas:</strong> Todas</p>
            <p><strong>Nível de Segurança:</strong> <span className="status-high">Alto</span></p>
          </div>

          <div className="dashboard-card">
            <h3>📈 Estatísticas</h3>
            <p><strong>Tentativas de Login:</strong> 0 (bem-sucedidas)</p>
            <p><strong>Ataques Bloqueados:</strong> 0</p>
            <p><strong>Logs de Segurança:</strong> Ativos</p>
            <p><strong>Monitoramento:</strong> 24/7</p>
          </div>
        </div>

        <div className="security-features">
          <h2>🛡️ Proteções de Segurança Implementadas</h2>
          <div className="features-grid">
            {securityFeatures.map((feature, index) => (
              <div key={index} className="feature-item">
                <div className="feature-icon">
                  {feature.icon}
                </div>
                <div className="feature-content">
                  <h4>{feature.title}</h4>
                  <p>{feature.description}</p>
                </div>
              </div>
            ))}
          </div>
        </div>

        <div className="dashboard-card">
          <h3>🎯 Sobre o SACSWeb</h3>
          <p>
            O SACSWeb é uma plataforma educacional especializada em segurança cibernética, 
            projetada para ensinar e demonstrar as melhores práticas de proteção contra 
            ataques cibernéticos comuns como SQL Injection, XSS, ataques de força bruta e outros.
          </p>
          <p>
            Este sistema implementa múltiplas camadas de segurança, incluindo validação de entrada, 
            sanitização de dados, proteção contra ataques automatizados e logging detalhado 
            para auditoria e análise de segurança.
          </p>
        </div>

        <div className="dashboard-card">
          <h3>⚠️ Aviso de Segurança</h3>
          <div className="alert alert-warning">
            <strong>Importante:</strong> Este é um sistema educacional. Em ambientes de produção, 
            sempre implemente medidas de segurança adicionais como:
            <ul style={{ marginTop: '0.5rem', marginLeft: '1.5rem' }}>
              <li>Autenticação de dois fatores (2FA)</li>
              <li>Certificados SSL/TLS</li>
              <li>Firewalls de aplicação web (WAF)</li>
              <li>Monitoramento de segurança em tempo real</li>
              <li>Backups regulares e seguros</li>
            </ul>
          </div>
        </div>
      </div>
    </div>
  );
};

export default Dashboard; 