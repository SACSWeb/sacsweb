import React from 'react';
import { Link } from 'react-router-dom';

const Header = ({ isAuthenticated, user, onLogout }) => {
  return (
    <header className="header">
      <div className="container">
        <div className="header-content">
          <Link to="/" className="logo">
            <span className="logo-icon">🔒</span>
            <span className="logo-text">SACSWeb</span>
          </Link>
          
          <nav className="nav">
            {isAuthenticated ? (
              <div className="nav-authenticated">
                <span className="welcome-text">
                  Bem-vindo, {user?.name || 'Usuário'}!
                </span>
                <button 
                  onClick={onLogout}
                  className="btn btn-secondary"
                >
                  Sair
                </button>
              </div>
            ) : (
              <div className="nav-unauthenticated">
                <Link to="/login" className="btn btn-secondary">
                  Entrar
                </Link>
                <Link to="/register" className="btn btn-primary">
                  Cadastrar
                </Link>
              </div>
            )}
          </nav>
        </div>
      </div>
    </header>
  );
};

export default Header; 