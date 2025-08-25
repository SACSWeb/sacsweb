# Quiz de Segurança Cibernética

Um quiz interativo sobre ataques cibernéticos desenvolvido para TCC, focado em educar sobre segurança digital.

## 📋 Sobre o Projeto

Este quiz foi desenvolvido para testar conhecimentos sobre diferentes tipos de ataques cibernéticos, incluindo:

- **Phishing**: Golpes que visam roubar dados pessoais
- **SQL Injection**: Ataques que manipulam bancos de dados
- **Força Bruta**: Tentativas repetidas de descobrir senhas
- **XSS (Cross-Site Scripting)**: Inserção de scripts maliciosos

## 🎯 Funcionalidades

- **15 perguntas** sobre segurança cibernética
- **Interface moderna** e responsiva
- **Feedback visual** para respostas corretas e incorretas
- **Barra de progresso** em tempo real
- **Sistema de pontuação** com percentual
- **Mensagens personalizadas** baseadas no desempenho
- **Design responsivo** para diferentes dispositivos

## 🚀 Como Usar

1. Abra o arquivo `index.html` em qualquer navegador moderno
2. Clique em "Começar Quiz" na tela de boas-vindas
3. Responda as perguntas selecionando uma das opções (A, B, C ou D)
4. Clique em "Próxima Pergunta" para continuar
5. Ao final, veja sua pontuação e feedback personalizado

## 📁 Estrutura do Projeto

```
quiz-cibernetica/
├── index.html          # Arquivo principal HTML
├── styles.css          # Estilos CSS
├── script.js           # Lógica JavaScript
└── README.md           # Documentação
```

## 🎨 Tecnologias Utilizadas

- **HTML5**: Estrutura semântica
- **CSS3**: Estilização moderna com gradientes e animações
- **JavaScript**: Lógica interativa do quiz
- **Font Awesome**: Ícones
- **Google Fonts**: Tipografia Inter

## 📊 Perguntas Incluídas

O quiz contém 15 perguntas sobre:

1. Definição de Phishing
2. Funcionamento do Phishing
3. Prevenção de Phishing
4. Definição de SQL Injection
5. Funcionamento do SQL Injection
6. Prevenção de SQL Injection
7. Definição de Força Bruta
8. Funcionamento da Força Bruta
9. Prevenção de Força Bruta
10. Definição de XSS
11. Objetivo do XSS
12. Funcionamento do XSS
13. Prevenção de XSS (desenvolvedor)
14. Prevenção de XSS (usuário)
15. Ações em caso de ataque cibernético

## 🎯 Gabarito

As respostas corretas são:
- Pergunta 1: B
- Pergunta 2: B
- Pergunta 3: B
- Pergunta 4: B
- Pergunta 5: B
- Pergunta 6: B
- Pergunta 7: B
- Pergunta 8: A
- Pergunta 9: B
- Pergunta 10: A
- Pergunta 11: B
- Pergunta 12: A
- Pergunta 13: B
- Pergunta 14: B
- Pergunta 15: B

## 🌟 Características do Design

- **Tema de segurança**: Cores azuis e roxas que remetem à tecnologia
- **Animações suaves**: Transições e efeitos visuais
- **Feedback imediato**: Cores diferentes para respostas corretas/incorretas
- **Responsivo**: Funciona em desktop, tablet e mobile
- **Acessível**: Interface clara e intuitiva

## 📱 Compatibilidade

- Chrome (recomendado)
- Firefox
- Safari
- Edge
- Dispositivos móveis

## 🔧 Personalização

Para adicionar ou modificar perguntas, edite o array `quizData` no arquivo `script.js`. Cada pergunta deve seguir o formato:

```javascript
{
    question: "Sua pergunta aqui?",
    options: {
        a: "Opção A",
        b: "Opção B", 
        c: "Opção C",
        d: "Opção D"
    },
    correct: "a" // Letra da resposta correta
}
```

## 📄 Licença

Este projeto foi desenvolvido para fins educacionais como parte de um TCC sobre segurança cibernética.

---

**Desenvolvido com ❤️ para conscientização sobre segurança digital** 