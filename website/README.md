# Website - SACSWeb Educacional

## Visão Geral
O **SACSWeb Educacional** é a interface web do sistema de aprendizado em segurança cibernética, desenvolvido como projeto de TCC. Fornece acesso a módulos, exercícios e dashboard para usuários logados.

> ⚠Projeto ainda em desenvolvimento, atualmente com 6 módulos iniciantes.

## Instalação Rápida

1. Baixe o projeto como `.zip`.
2. Extraia a pasta dentro de `htdocs` do **XAMPP**. 
    C:\xampp\htdocs\sacsweb
3. Configure o banco de dados:
- Abra o **PHPMyAdmin**.
- Crie o banco `sacsweb_educacional`.
- Importe **`setup_base.sql`** primeiro.
- Em seguida, importe **`setup_modulos.sql`**.

4. Acesse o sistema com:
    http://localhost/sacsweb/

## Funcionalidades Principais
- Login e registro de usuários
- Dashboard de progresso
- Visualização de módulos e exercícios
- Sistema de pontos e badges
- Interface moderna com **Bootstrap 5** e **Glass Morphism**

## Segurança
- Proteção contra **XSS** e **SQL Injection**
- Validação de sessões e CSRF
- Sanitização de entradas de usuário

## Responsividade
- Layout adaptável para desktop, tablets e celulares
- Grid system e breakpoints do Bootstrap 5

## Solução de Problemas
- **Permissões**: Certifique-se que `logs`, `backups` e `uploads` são graváveis
- **Conexão com banco**: Verifique se MySQL está rodando e credenciais corretas
- **Página em branco**: Ative `display_errors` no PHP e confira os logs

## Documentação Adicional
- **[README Principal](../README.md)** - Visão geral do projeto  
- **[Backend](../backend/README.md)** - Documentação da API  
- **[Banco de Dados](../database/README.md)** - Documentação do banco  
- **[Documentação Completa](../docs/README-EDUCACIONAL.md)** - Guia completo do sistema

---

**Website SACSWeb Educacional** - Interface moderna e responsiva do sistema educacional
