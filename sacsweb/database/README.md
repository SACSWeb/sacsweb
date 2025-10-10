# 🗄Banco de Dados - SACSWeb Educacional

## Importação do Banco de Dados

Para aplicar o banco de dados do **SACSWeb Educacional**, siga os passos abaixo:

1. **Importar `setup_base.sql` no PHPMyAdmin**  
   - Este script cria a estrutura básica do banco de dados e tabelas principais.  
   - Recomenda-se sempre começar por ele para garantir que todas as dependências estejam corretas.

2. **Importar `setup_modulos.sql` dentro do banco `sacsweb_educacional`**  
   - Este script adiciona todos os módulos educacionais, exercícios e quizzes.  
   - A separação facilita a manutenção e a adição de novos módulos sem interferir na estrutura base do sistema.

> ⚠**Observação**: Seguir esta ordem de importação é fundamental para manter a integridade do banco e simplificar futuras atualizações de módulos educacionais.

---
