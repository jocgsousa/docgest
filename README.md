# DocGest - Sistema de Gestão de Documentos e Assinaturas Eletrônicas

## Descrição

O DocGest é um sistema completo para gestão de documentos e assinaturas eletrônicas, desenvolvido para empresas que precisam de uma solução eficiente e segura para gerenciar seus documentos e processos de assinatura.

## Funcionalidades

### 🔐 Autenticação e Autorização
- Sistema de login seguro com JWT
- Três níveis de usuário:
  - **Super Admin**: Gerencia toda a plataforma
  - **Admin Empresa**: Gerencia usuários e documentos da empresa
  - **Assinante**: Apenas assina documentos

### 🏢 Gestão de Empresas
- Cadastro e gerenciamento de empresas
- Controle de planos e vencimentos
- Gestão de filiais
- Dashboard com estatísticas

### 👥 Gestão de Usuários
- Cadastro de usuários por empresa
- Controle de permissões por tipo
- Vinculação a empresas e filiais

### 📋 Gestão de Planos
- Criação e edição de planos
- Definição de limites (usuários, documentos, assinaturas)
- Controle de preços e recursos

### 📄 Gestão de Documentos
- Upload de documentos (PDF, DOC, DOCX, imagens)
- Controle de status (rascunho, enviado, assinado, cancelado)
- Busca e filtros avançados
- Download de documentos
- Estatísticas por status

### ✍️ Gestão de Assinaturas
- Criação de processos de assinatura
- Múltiplos signatários por documento
- Controle de ordem de assinatura
- Tokens únicos para cada signatário
- Envio de lembretes
- Controle de expiração
- Página pública para assinatura

## Tecnologias Utilizadas

### Backend (PHP)
- **PHP 7.4+**
- **MySQL 8.0+**
- **PDO** para conexão com banco
- **JWT** para autenticação
- **Arquitetura MVC**

### Frontend (React)
- **React 18**
- **React Router** para roteamento
- **Styled Components** para estilização
- **Axios** para requisições HTTP
- **Context API** para gerenciamento de estado

## Estrutura do Projeto

```
docgest/
├── backend/
│   ├── config/
│   │   ├── database.php
│   │   └── config.php
│   ├── controllers/
│   │   ├── AuthController.php
│   │   ├── UserController.php
│   │   ├── CompanyController.php
│   │   ├── PlanController.php
│   │   ├── DocumentController.php
│   │   └── SignatureController.php
│   ├── models/
│   │   ├── User.php
│   │   ├── Company.php
│   │   ├── Plan.php
│   │   ├── Document.php
│   │   └── Signature.php
│   ├── utils/
│   │   ├── JWT.php
│   │   ├── Response.php
│   │   └── Validator.php
│   ├── routes/
│   │   └── api.php
│   ├── uploads/
│   ├── logs/
│   ├── .htaccess
│   └── index.php
├── frontend/
│   ├── public/
│   ├── src/
│   │   ├── components/
│   │   │   ├── Button.js
│   │   │   ├── Input.js
│   │   │   ├── Modal.js
│   │   │   ├── Table.js
│   │   │   ├── Header.js
│   │   │   └── Sidebar.js
│   │   ├── contexts/
│   │   │   └── AuthContext.js
│   │   ├── pages/
│   │   │   ├── Login.js
│   │   │   ├── Dashboard.js
│   │   │   ├── Users.js
│   │   │   ├── Companies.js
│   │   │   ├── Plans.js
│   │   │   ├── Documents.js
│   │   │   └── Signatures.js
│   │   ├── services/
│   │   │   └── api.js
│   │   ├── App.js
│   │   └── index.js
│   ├── package.json
│   └── package-lock.json
├── database.sql
└── README.md
```

## Instalação e Configuração

### Pré-requisitos
- PHP 7.4 ou superior
- MySQL 8.0 ou superior
- Node.js 16 ou superior
- Servidor web (Apache/Nginx)

### Backend

1. **Configure o banco de dados:**
   ```sql
   -- Execute o arquivo database.sql no MySQL
   mysql -u root -p < database.sql
   ```

2. **Configure as credenciais do banco:**
   - Edite o arquivo `backend/config/database.php`
   - Ajuste host, usuário e senha conforme necessário

3. **Configure o servidor web:**
   - Aponte o DocumentRoot para a pasta `backend/`
   - Certifique-se de que o mod_rewrite está habilitado

4. **Permissões:**
   ```bash
   chmod 755 backend/uploads/
   chmod 755 backend/logs/
   ```

### Frontend

1. **Instale as dependências:**
   ```bash
   cd frontend
   npm install
   ```

2. **Configure a URL da API:**
   - Edite `frontend/src/services/api.js`
   - Ajuste a `baseURL` conforme necessário

3. **Execute em desenvolvimento:**
   ```bash
   npm start
   ```

4. **Build para produção:**
   ```bash
   npm run build
   ```

## Usuários Padrão

Após executar o script SQL, os seguintes usuários estarão disponíveis:

| Email | Senha | Tipo | Descrição |
|-------|-------|------|----------|
| admin@docgest.com | 123456 | Super Admin | Administrador da plataforma |
| admin@exemplo.com | 123456 | Admin Empresa | Administrador da empresa exemplo |
| usuario@exemplo.com | 123456 | Assinante | Usuário assinante |

## API Endpoints

### Autenticação
- `POST /auth/login` - Login do usuário
- `POST /auth/register` - Registro de usuário
- `POST /auth/logout` - Logout do usuário
- `GET /auth/me` - Dados do usuário logado

### Usuários
- `GET /users` - Listar usuários
- `GET /users/{id}` - Buscar usuário por ID
- `POST /users` - Criar usuário
- `PUT /users/{id}` - Atualizar usuário
- `DELETE /users/{id}` - Excluir usuário
- `GET /users/stats` - Estatísticas de usuários

### Empresas
- `GET /companies` - Listar empresas
- `GET /companies/{id}` - Buscar empresa por ID
- `POST /companies` - Criar empresa
- `PUT /companies/{id}` - Atualizar empresa
- `DELETE /companies/{id}` - Excluir empresa
- `GET /companies/stats` - Estatísticas de empresas

### Planos
- `GET /plans` - Listar planos
- `GET /plans/{id}` - Buscar plano por ID
- `POST /plans` - Criar plano
- `PUT /plans/{id}` - Atualizar plano
- `DELETE /plans/{id}` - Excluir plano
- `GET /plans/stats` - Estatísticas de planos

### Documentos
- `GET /documents` - Listar documentos
- `GET /documents/{id}` - Buscar documento por ID
- `POST /documents` - Criar documento (com upload)
- `PUT /documents/{id}` - Atualizar documento
- `DELETE /documents/{id}` - Excluir documento
- `GET /documents/{id}/download` - Download do documento
- `GET /documents/stats` - Estatísticas de documentos
- `PUT /documents/{id}/status` - Atualizar status

### Assinaturas
- `GET /signatures` - Listar assinaturas
- `GET /signatures/{id}` - Buscar assinatura por ID
- `POST /signatures` - Criar assinatura
- `PUT /signatures/{id}/cancel` - Cancelar assinatura
- `POST /signatures/{id}/reminder` - Enviar lembrete
- `GET /signatures/stats` - Estatísticas de assinaturas
- `GET /sign/{token}` - Página de assinatura
- `POST /sign/{token}` - Processar assinatura

## Segurança

- **Autenticação JWT** com expiração configurável
- **Validação de dados** em todas as entradas
- **Sanitização** de uploads de arquivos
- **Headers de segurança** configurados
- **Proteção CORS** configurada
- **Logs de auditoria** para ações importantes

## Contribuição

1. Faça um fork do projeto
2. Crie uma branch para sua feature (`git checkout -b feature/AmazingFeature`)
3. Commit suas mudanças (`git commit -m 'Add some AmazingFeature'`)
4. Push para a branch (`git push origin feature/AmazingFeature`)
5. Abra um Pull Request

## Licença

Este projeto está sob a licença MIT. Veja o arquivo `LICENSE` para mais detalhes.

## Suporte

Para suporte técnico, entre em contato através do email: suporte@docgest.com

---

**DocGest** - Gestão de Documentos e Assinaturas Eletrônicas
Versão 1.0 - 2024