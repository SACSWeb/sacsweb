import React, { useState } from 'react';
import { Link } from 'react-router-dom';

const Login = ({ onLogin }) => {
  const [formData, setFormData] = useState({
    email: '',
    password: ''
  });
  const [errors, setErrors] = useState({});
  const [loading, setLoading] = useState(false);
  const [message, setMessage] = useState('');

  const handleChange = (e) => {
    const { name, value } = e.target;
    setFormData(prev => ({
      ...prev,
      [name]: value
    }));
    
    // Limpar erro do campo quando usuário começa a digitar
    if (errors[name]) {
      setErrors(prev => ({
        ...prev,
        [name]: ''
      }));
    }
  };

  const validateForm = () => {
    const newErrors = {};

    if (!formData.email) {
      newErrors.email = 'Email é obrigatório';
    } else if (!/\S+@\S+\.\S+/.test(formData.email)) {
      newErrors.email = 'Email inválido';
    }

    if (!formData.password) {
      newErrors.password = 'Senha é obrigatória';
    } else if (formData.password.length < 8) {
      newErrors.password = 'Senha deve ter pelo menos 8 caracteres';
    }

    setErrors(newErrors);
    return Object.keys(newErrors).length === 0;
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    setMessage('');

    if (!validateForm()) {
      return;
    }

    setLoading(true);

    try {
      const response = await fetch('/api/auth/login', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify(formData),
      });

      const data = await response.json();

      if (response.ok) {
        onLogin(data.user, data.token);
        setMessage('Login realizado com sucesso!');
      } else {
        setMessage(data.error || 'Erro no login');
        if (data.code === 'ACCOUNT_LOCKED') {
          setMessage('Conta bloqueada devido a múltiplas tentativas. Tente novamente em alguns minutos.');
        }
      }
    } catch (error) {
      console.error('Erro no login:', error);
      setMessage('Erro de conexão. Tente novamente.');
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="auth-container">
      <div className="auth-card">
        <div className="card">
          <div className="card-header">
            <h1 className="card-title">🔒 Login SACSWeb</h1>
            <p className="card-subtitle">
              Acesse sua conta para continuar
            </p>
          </div>

          {message && (
            <div className={`alert ${message.includes('sucesso') ? 'alert-success' : 'alert-error'}`}>
              {message}
            </div>
          )}

          <form onSubmit={handleSubmit}>
            <div className="form-group">
              <label htmlFor="email" className="form-label">
                Email
              </label>
              <input
                type="email"
                id="email"
                name="email"
                className={`form-input ${errors.email ? 'error' : ''}`}
                value={formData.email}
                onChange={handleChange}
                placeholder="seu@email.com"
                disabled={loading}
              />
              {errors.email && (
                <div className="error-message">{errors.email}</div>
              )}
            </div>

            <div className="form-group">
              <label htmlFor="password" className="form-label">
                Senha
              </label>
              <input
                type="password"
                id="password"
                name="password"
                className={`form-input ${errors.password ? 'error' : ''}`}
                value={formData.password}
                onChange={handleChange}
                placeholder="Sua senha"
                disabled={loading}
              />
              {errors.password && (
                <div className="error-message">{errors.password}</div>
              )}
            </div>

            <button
              type="submit"
              className={`btn btn-primary ${loading ? 'loading' : ''}`}
              disabled={loading}
            >
              {loading && <div className="spinner"></div>}
              {loading ? 'Entrando...' : 'Entrar'}
            </button>
          </form>

          <div className="auth-links">
            <Link to="/register" className="link">
              Não tem uma conta? Cadastre-se
            </Link>
          </div>

          <div className="security-info">
            <p className="security-text">
              <strong>🔒 Segurança:</strong> Este sistema implementa proteções contra:
            </p>
            <ul className="security-list">
              <li>SQL Injection</li>
              <li>XSS (Cross-Site Scripting)</li>
              <li>Ataques de Força Bruta</li>
              <li>Rate Limiting</li>
            </ul>
          </div>
        </div>
      </div>
    </div>
  );
};

export default Login; 