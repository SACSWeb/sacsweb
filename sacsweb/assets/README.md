# 🎨 Assets - SACSWeb Educacional

## 📁 Recursos Estáticos

### **`css/`** - Arquivos de Estilo
- **`App.css`** - Estilos principais da aplicação
- **`index.css`** - Estilos da página inicial
- **`sacsweb.css`** - Estilos específicos do sistema

### **`js/`** - Arquivos JavaScript
- **`App.js`** - Aplicação principal React
- **`auth.js`** - Sistema de autenticação
- **`Dashboard.js`** - Componente do dashboard
- **`Header.js`** - Componente do cabeçalho
- **`index.js`** - Ponto de entrada da aplicação
- **`init.js`** - Inicialização do sistema
- **`Login.js`** - Componente de login
- **`Register.js`** - Componente de registro

### **`images/`** - Imagens e Ícones
- Ícones do sistema
- Imagens de fundo
- Logos e marcas
- Screenshots de demonstração

## 🎨 Sistema de Design

### **Paleta de Cores**
```css
:root {
  /* Cores principais */
  --primary-color: #667eea;
  --primary-dark: #5a6fd8;
  --secondary-color: #764ba2;
  
  /* Cores de estado */
  --success-color: #28a745;
  --warning-color: #ffc107;
  --danger-color: #dc3545;
  --info-color: #17a2b8;
  
  /* Cores neutras */
  --light-color: #f8f9fa;
  --dark-color: #343a40;
  --muted-color: #6c757d;
  
  /* Gradientes */
  --gradient-primary: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  --gradient-secondary: linear-gradient(135deg, #667eea 0%, #5a6fd8 100%);
}
```

### **Tipografia**
```css
/* Fontes */
body {
  font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
  font-size: 16px;
  line-height: 1.6;
}

/* Títulos */
h1, h2, h3, h4, h5, h6 {
  font-weight: 600;
  line-height: 1.2;
}

h1 { font-size: 2.5rem; }
h2 { font-size: 2rem; }
h3 { font-size: 1.75rem; }
h4 { font-size: 1.5rem; }
h5 { font-size: 1.25rem; }
h6 { font-size: 1rem; }
```

### **Espaçamentos**
```css
:root {
  /* Sistema de espaçamento baseado em 8px */
  --spacing-xs: 0.25rem;   /* 4px */
  --spacing-sm: 0.5rem;    /* 8px */
  --spacing-md: 1rem;      /* 16px */
  --spacing-lg: 1.5rem;    /* 24px */
  --spacing-xl: 2rem;      /* 32px */
  --spacing-xxl: 3rem;     /* 48px */
}

/* Classes utilitárias */
.m-0 { margin: 0; }
.m-1 { margin: var(--spacing-sm); }
.m-2 { margin: var(--spacing-md); }
.m-3 { margin: var(--spacing-lg); }
.m-4 { margin: var(--spacing-xl); }
.m-5 { margin: var(--spacing-xxl); }

.p-0 { padding: 0; }
.p-1 { padding: var(--spacing-sm); }
.p-2 { padding: var(--spacing-md); }
.p-3 { padding: var(--spacing-lg); }
.p-4 { padding: var(--spacing-xl); }
.p-5 { padding: var(--spacing-xxl); }
```

## 🎭 Componentes Visuais

### **Botões**
```css
.btn {
  display: inline-block;
  padding: 0.75rem 1.5rem;
  border: none;
  border-radius: 0.5rem;
  font-weight: 500;
  text-decoration: none;
  text-align: center;
  cursor: pointer;
  transition: all 0.3s ease;
}

.btn-primary {
  background: var(--gradient-primary);
  color: white;
}

.btn-primary:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
}

.btn-secondary {
  background: var(--gradient-secondary);
  color: white;
}

.btn-outline {
  background: transparent;
  border: 2px solid var(--primary-color);
  color: var(--primary-color);
}

.btn-outline:hover {
  background: var(--primary-color);
  color: white;
}
```

### **Cards**
```css
.card {
  background: rgba(255, 255, 255, 0.95);
  border-radius: 1rem;
  backdrop-filter: blur(10px);
  box-shadow: 0 8px 32px rgba(31, 38, 135, 0.1);
  transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.card:hover {
  transform: translateY(-5px);
  box-shadow: 0 12px 40px rgba(31, 38, 135, 0.15);
}

.card-header {
  padding: var(--spacing-lg);
  border-bottom: 1px solid rgba(0, 0, 0, 0.1);
}

.card-body {
  padding: var(--spacing-lg);
}

.card-footer {
  padding: var(--spacing-lg);
  border-top: 1px solid rgba(0, 0, 0, 0.1);
}
```

### **Formulários**
```css
.form-group {
  margin-bottom: var(--spacing-md);
}

.form-label {
  display: block;
  margin-bottom: var(--spacing-sm);
  font-weight: 500;
  color: var(--dark-color);
}

.form-control {
  width: 100%;
  padding: 0.75rem;
  border: 2px solid #e9ecef;
  border-radius: 0.5rem;
  font-size: 1rem;
  transition: border-color 0.3s ease;
}

.form-control:focus {
  outline: none;
  border-color: var(--primary-color);
  box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.form-control.is-invalid {
  border-color: var(--danger-color);
}

.form-control.is-valid {
  border-color: var(--success-color);
}
```

## 🎮 Animações e Transições

### **Transições Suaves**
```css
/* Transições globais */
* {
  transition: all 0.3s ease;
}

/* Transições específicas */
.fade-in {
  animation: fadeIn 0.5s ease-in;
}

.slide-up {
  animation: slideUp 0.5s ease-out;
}

.scale-in {
  animation: scaleIn 0.3s ease-out;
}

/* Keyframes */
@keyframes fadeIn {
  from { opacity: 0; }
  to { opacity: 1; }
}

@keyframes slideUp {
  from { transform: translateY(20px); opacity: 0; }
  to { transform: translateY(0); opacity: 1; }
}

@keyframes scaleIn {
  from { transform: scale(0.9); opacity: 0; }
  to { transform: scale(1); opacity: 1; }
}
```

### **Hover Effects**
```css
.hover-lift {
  transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.hover-lift:hover {
  transform: translateY(-5px);
  box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
}

.hover-glow {
  transition: box-shadow 0.3s ease;
}

.hover-glow:hover {
  box-shadow: 0 0 20px rgba(102, 126, 234, 0.4);
}

.hover-rotate {
  transition: transform 0.3s ease;
}

.hover-rotate:hover {
  transform: rotate(5deg);
}
```

## 📱 Responsividade

### **Media Queries**
```css
/* Mobile First Approach */

/* Base (mobile) */
.container {
  padding: var(--spacing-md);
}

/* Tablet (768px+) */
@media (min-width: 768px) {
  .container {
    padding: var(--spacing-lg);
  }
}

/* Desktop (992px+) */
@media (min-width: 992px) {
  .container {
    padding: var(--spacing-xl);
  }
}

/* Large Desktop (1200px+) */
@media (min-width: 1200px) {
  .container {
    padding: var(--spacing-xxl);
  }
}
```

### **Grid System**
```css
.grid {
  display: grid;
  gap: var(--spacing-md);
}

.grid-1 { grid-template-columns: 1fr; }
.grid-2 { grid-template-columns: repeat(2, 1fr); }
.grid-3 { grid-template-columns: repeat(3, 1fr); }
.grid-4 { grid-template-columns: repeat(4, 1fr); }

@media (max-width: 768px) {
  .grid-2, .grid-3, .grid-4 {
    grid-template-columns: 1fr;
  }
}
```

## 🎯 Utilitários CSS

### **Flexbox Utilities**
```css
.d-flex { display: flex; }
.d-inline-flex { display: inline-flex; }

.justify-start { justify-content: flex-start; }
.justify-center { justify-content: center; }
.justify-end { justify-content: flex-end; }
.justify-between { justify-content: space-between; }
.justify-around { justify-content: space-around; }

.align-start { align-items: flex-start; }
.align-center { align-items: center; }
.align-end { align-items: flex-end; }
.align-stretch { align-items: stretch; }

.flex-column { flex-direction: column; }
.flex-row { flex-direction: row; }
.flex-wrap { flex-wrap: wrap; }
.flex-nowrap { flex-wrap: nowrap; }
```

### **Text Utilities**
```css
.text-left { text-align: left; }
.text-center { text-align: center; }
.text-right { text-align: right; }
.text-justify { text-align: justify; }

.text-uppercase { text-transform: uppercase; }
.text-lowercase { text-transform: lowercase; }
.text-capitalize { text-transform: capitalize; }

.text-bold { font-weight: bold; }
.text-normal { font-weight: normal; }
.text-light { font-weight: 300; }

.text-truncate {
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}
```

### **Display Utilities**
```css
.d-none { display: none; }
.d-block { display: block; }
.d-inline { display: inline; }
.d-inline-block { display: inline-block; }

.d-table { display: table; }
.d-table-row { display: table-row; }
.d-table-cell { display: table-cell; }

.d-grid { display: grid; }
.d-flex { display: flex; }
```

## 🚀 Performance

### **Otimizações CSS**
```css
/* Usar transform em vez de position para animações */
.animate {
  transform: translateX(0);
  transition: transform 0.3s ease;
}

.animate:hover {
  transform: translateX(10px);
}

/* Usar will-change para otimizar animações */
.will-animate {
  will-change: transform, opacity;
}

/* Usar contain para isolar elementos */
.isolated {
  contain: layout, style, paint;
}
```

### **Lazy Loading**
```css
/* Imagens com lazy loading */
.lazy-image {
  opacity: 0;
  transition: opacity 0.3s ease;
}

.lazy-image.loaded {
  opacity: 1;
}

/* Placeholder para imagens */
.image-placeholder {
  background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
  background-size: 200% 100%;
  animation: loading 1.5s infinite;
}

@keyframes loading {
  0% { background-position: 200% 0; }
  100% { background-position: -200% 0; }
}
```

## 🔧 Desenvolvimento

### **Estrutura de Arquivos**
```
assets/
├── css/
│   ├── App.css              # Estilos principais
│   ├── index.css            # Estilos da página inicial
│   ├── sacsweb.css          # Estilos específicos
│   └── components/          # Estilos de componentes
├── js/
│   ├── App.js               # Aplicação principal
│   ├── components/          # Componentes React
│   ├── utils/               # Utilitários JavaScript
│   └── services/            # Serviços e APIs
├── images/
│   ├── icons/               # Ícones do sistema
│   ├── backgrounds/         # Imagens de fundo
│   └── logos/               # Logos e marcas
└── README.md                # Este arquivo
```

### **Convenções de Nomenclatura**
```css
/* BEM (Block Element Modifier) */
.card { }                    /* Block */
.card__header { }            /* Element */
.card__header--large { }     /* Modifier */

/* Componentes */
.btn { }
.btn--primary { }
.btn--secondary { }
.btn--outline { }

/* Estados */
.is-active { }
.is-disabled { }
.is-hidden { }
.is-visible { }

/* Utilitários */
.text-center { }
.m-0 { }
.p-0 { }
.d-flex { }
```

### **Organização de Código**
```css
/* 1. Imports */
@import 'variables.css';
@import 'reset.css';

/* 2. Variáveis CSS */
:root {
  /* Cores */
  /* Tipografia */
  /* Espaçamentos */
}

/* 3. Reset/Base */
* { }
body { }
html { }

/* 4. Componentes */
.btn { }
.card { }
.form { }

/* 5. Utilitários */
.text-center { }
.m-0 { }
.d-flex { }

/* 6. Media Queries */
@media (min-width: 768px) { }
@media (min-width: 992px) { }
```

## 📚 Documentação Adicional

- **📖 [README Principal](../README.md)** - Visão geral do projeto
- **🚀 [Backend](../backend/README.md)** - Documentação da API
- **🌐 [Website](../website/README.md)** - Documentação do frontend
- **🗄️ [Banco de Dados](../database/README.md)** - Documentação do banco

---

**🎨 Assets SACSWeb Educacional** - Recursos visuais e interativos para uma experiência de usuário excepcional
