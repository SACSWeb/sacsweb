# 🎨 Assets - SACSWeb Educacional

## Visão Geral

O diretório `assets/` contém todos os recursos estáticos do sistema SACSWeb Educacional, incluindo arquivos CSS, JavaScript e imagens.

## 📁 Estrutura de Diretórios

```
assets/
├── css/           # Arquivos de estilo
├── js/            # Arquivos JavaScript
└── images/        # Imagens e ícones
```

## 🎨 CSS (`css/`)

### `sacsweb-unified.css`
Arquivo CSS principal unificado do sistema. Contém:
- Sistema de temas (escuro/claro/automático)
- Variáveis CSS para cores, espaçamentos e tipografia
- Estilos para componentes (botões, cards, formulários)
- Animações e transições
- Responsividade (mobile-first)
- Acessibilidade (alto contraste, tamanhos de fonte)

**Funcionalidades:**
- Suporte a tema escuro/claro com variável CSS `--theme-mode`
- Variável `--text-always-dark` para textos que devem permanecer escuros
- Classes utilitárias para espaçamento, display, flexbox
- Media queries para responsividade
- Animações suaves e transições

### Outros arquivos CSS (legados, não utilizados)
- `App.css`: CSS legado da aplicação React
- `dashboard.css`: CSS legado do dashboard
- `exercicio.css`: CSS legado de exercícios
- `exercicios.css`: CSS legado da lista de exercícios
- `index-educacional.css`: CSS legado da página inicial
- `index.css`: CSS legado da página inicial
- `login.css`: CSS legado da página de login
- `modulo.css`: CSS legado de módulos
- `modulos.css`: CSS legado da lista de módulos
- `sacsweb-theme.css`: CSS legado de temas
- `sacsweb.css`: CSS legado principal

**Nota**: Estes arquivos são mantidos para referência, mas não são carregados nas páginas atuais.

## 📜 JavaScript (`js/`)

### `preferences.js`
Sistema de gerenciamento de preferências de acessibilidade e tema.

**Funcionalidades:**
- Carrega preferências do usuário de `window.SACSWEB_PREFERENCES`
- Aplica tema (escuro/claro/automático) baseado na preferência do sistema
- Gerencia tamanho de fonte (pequeno/médio/grande)
- Controla alto contraste
- Reduz animações quando solicitado
- Salva preferências no banco de dados via AJAX
- Atualiza variáveis CSS dinamicamente

**Funções principais:**
- Aplicação automática de tema ao carregar página
- Detecção de preferência de tema do sistema (dark mode)
- Atualização de classes CSS para acessibilidade
- Salvamento de preferências no servidor

### Outros arquivos JavaScript (legados, não utilizados)
- `App.js`: Aplicação principal React (legado)
- `auth.js`: Sistema de autenticação (legado)
- `Dashboard.js`: Componente do dashboard (legado)
- `Header.js`: Componente do cabeçalho (legado)
- `index.js`: Ponto de entrada da aplicação (legado)
- `init.js`: Inicialização do sistema (legado)
- `Login.js`: Componente de login (legado)
- `Register.js`: Componente de registro (legado)

**Nota**: Estes arquivos são mantidos para referência, mas não são utilizados no sistema atual.

## 🖼️ Imagens (`images/`)

### `icone.png`
Ícone/logo do sistema SACSWeb Educacional. Utilizado em:
- Navbar de todas as páginas
- Favicon
- Elementos visuais do sistema

### Outras imagens
- Imagens de fundo (se houver)
- Screenshots de demonstração (se houver)
- Outros recursos visuais

## 🎯 Sistema de Temas

O sistema utiliza variáveis CSS para gerenciar temas:

```css
:root {
  --theme-mode: light; /* ou dark */
  --bg-primary: #ffffff;
  --text-primary: #000000;
  --text-always-dark: #000000; /* Para textos que devem permanecer escuros */
}
```

O JavaScript `preferences.js` atualiza essas variáveis dinamicamente baseado nas preferências do usuário.

## 📱 Responsividade

Todos os estilos seguem abordagem mobile-first:
- Base: Mobile (< 768px)
- Tablet: 768px+
- Desktop: 992px+
- Large Desktop: 1200px+

## ♿ Acessibilidade

O sistema suporta:
- **Alto contraste**: Aumenta contraste de cores
- **Tamanho de fonte**: Pequeno, médio, grande
- **Redução de animações**: Remove/ reduz animações
- **Tema escuro**: Melhora legibilidade em ambientes escuros

## 📚 Documentação Adicional

- **[README Principal](../README.md)** - Visão geral do projeto
- **[Website](../website/README.md)** - Documentação das páginas
- **[Config](../config/README.md)** - Funções de configuração
- **[Database](../database/README.md)** - Documentação do banco

---

**🎨 Assets SACSWeb Educacional** - Recursos estáticos e sistema de temas
