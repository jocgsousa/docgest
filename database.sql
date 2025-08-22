-- ================================================
-- CRIAÇÃO DO BANCO DE DADOS DOCGEST
-- ================================================
CREATE DATABASE IF NOT EXISTS docgest;
USE docgest;

-- ================================================
-- PLANOS
-- ================================================
CREATE TABLE planos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    descricao TEXT,
    preco DECIMAL(10,2) NOT NULL,
    limite_usuarios INT NOT NULL,
    limite_documentos INT NOT NULL,
    limite_assinaturas INT NOT NULL,
    limite_filiais INT NOT NULL DEFAULT 1,
    limite_armazenamento_mb INT NOT NULL DEFAULT 1024 COMMENT 'Limite de armazenamento em MB',
    dias INT NOT NULL DEFAULT 30 COMMENT 'Número de dias de vigência do plano',
    ativo BOOLEAN DEFAULT TRUE,
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    data_atualizacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- ================================================
-- EMPRESAS
-- ================================================
CREATE TABLE empresas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(150) NOT NULL,
    cnpj VARCHAR(18) UNIQUE NOT NULL,
    codigo_empresa VARCHAR(10) UNIQUE NOT NULL,
    email VARCHAR(150) NOT NULL,
    telefone VARCHAR(20),
    endereco TEXT,
    cidade VARCHAR(100),
    estado VARCHAR(2),
    cep VARCHAR(10),
    plano_id INT NOT NULL,
    data_vencimento DATE NOT NULL,
    ativo BOOLEAN DEFAULT TRUE,
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    data_atualizacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (plano_id) REFERENCES planos(id)
);

-- ================================================
-- FILIAIS
-- ================================================
CREATE TABLE filiais (
    id INT AUTO_INCREMENT PRIMARY KEY,
    empresa_id INT NOT NULL,
    nome VARCHAR(150) NOT NULL,
    cnpj VARCHAR(18) UNIQUE,
    inscricao_estadual VARCHAR(20),
    endereco TEXT,
    cidade VARCHAR(100),
    estado VARCHAR(2),
    cep VARCHAR(10),
    telefone VARCHAR(20),
    email VARCHAR(150),
    responsavel VARCHAR(150),
    observacoes TEXT,
    ativo BOOLEAN DEFAULT TRUE,
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    data_atualizacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (empresa_id) REFERENCES empresas(id) ON DELETE CASCADE
);

-- ================================================
-- PROFISSÕES
-- ================================================
CREATE TABLE profissoes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL UNIQUE,
    descricao TEXT,
    ativo BOOLEAN DEFAULT TRUE,
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    data_atualizacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- ================================================
-- USUÁRIOS
-- ================================================
CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(150) NOT NULL,
    email VARCHAR(150) UNIQUE NOT NULL,
    senha VARCHAR(255) NOT NULL,
    cpf VARCHAR(14) UNIQUE,
    telefone VARCHAR(20),
    profissao_id INT,
    tipo_usuario TINYINT NOT NULL COMMENT '1=Super Admin, 2=Admin Empresa, 3=Assinante',
    empresa_id INT,
    filial_id INT,
    ativo BOOLEAN DEFAULT TRUE,
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    data_atualizacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (profissao_id) REFERENCES profissoes(id) ON DELETE SET NULL,
    FOREIGN KEY (empresa_id) REFERENCES empresas(id) ON DELETE CASCADE,
    FOREIGN KEY (filial_id) REFERENCES filiais(id) ON DELETE SET NULL
);

-- ================================================
-- TIPOS DE DOCUMENTOS
-- ================================================
CREATE TABLE tipos_documentos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL UNIQUE,
    descricao TEXT,
    ativo BOOLEAN DEFAULT TRUE,
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    data_atualizacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- ================================================
-- DOCUMENTOS
-- ================================================
CREATE TABLE documentos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(200) NOT NULL,
    descricao TEXT,
    nome_arquivo VARCHAR(255) NOT NULL,
    caminho_arquivo VARCHAR(500) NOT NULL,
    tamanho_arquivo BIGINT,
    tipo_arquivo VARCHAR(100),
    hash_acesso VARCHAR(64) UNIQUE NOT NULL,
    status ENUM('rascunho','enviado','assinado','cancelado') DEFAULT 'rascunho',
    tipo_documento_id INT,
    prazo_assinatura DATE COMMENT 'Data limite até quando o documento pode ser assinado',
    competencia DATE COMMENT 'Mês/ano de competência do documento (ex: folha de pagamento)',
    validade_legal DATE COMMENT 'Data de validade legal do documento (se aplicável)',
    criado_por INT NOT NULL,
    empresa_id INT NOT NULL,
    filial_id INT,
    ativo BOOLEAN DEFAULT TRUE,
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    data_atualizacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (criado_por) REFERENCES usuarios(id),
    FOREIGN KEY (empresa_id) REFERENCES empresas(id) ON DELETE CASCADE,
    FOREIGN KEY (filial_id) REFERENCES filiais(id) ON DELETE SET NULL,
    FOREIGN KEY (tipo_documento_id) REFERENCES tipos_documentos(id) ON DELETE SET NULL
);

-- ================================================
-- ASSINATURAS
-- ================================================
CREATE TABLE assinaturas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    documento_id INT NOT NULL,
    status ENUM('pendente','assinado','rejeitado','cancelado','expirado') DEFAULT 'pendente',
    data_expiracao DATETIME,
    criado_por INT NOT NULL,
    empresa_id INT NOT NULL,
    filial_id INT,
    ativo BOOLEAN DEFAULT TRUE,
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    data_atualizacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (documento_id) REFERENCES documentos(id) ON DELETE CASCADE,
    FOREIGN KEY (criado_por) REFERENCES usuarios(id),
    FOREIGN KEY (empresa_id) REFERENCES empresas(id) ON DELETE CASCADE,
    FOREIGN KEY (filial_id) REFERENCES filiais(id) ON DELETE SET NULL
);

-- ================================================
-- SIGNATÁRIOS
-- ================================================
CREATE TABLE signatarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    assinatura_id INT NOT NULL,
    nome VARCHAR(150) NOT NULL,
    email VARCHAR(150) NOT NULL,
    ordem INT NOT NULL,
    status ENUM('pendente','assinado','rejeitado') DEFAULT 'pendente',
    token VARCHAR(100) NOT NULL,
    data_assinatura DATETIME,
    ip_assinatura VARCHAR(45),
    user_agent TEXT,
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    data_atualizacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (assinatura_id) REFERENCES assinaturas(id) ON DELETE CASCADE
);

-- ================================================
-- DOCUMENTO ASSINANTES (Vinculação de documentos a usuários específicos)
-- ================================================
CREATE TABLE documento_assinantes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    documento_id INT NOT NULL,
    usuario_id INT NOT NULL,
    status ENUM('pendente','visualizado','assinado','rejeitado') DEFAULT 'pendente',
    data_visualizacao DATETIME,
    data_assinatura DATETIME,
    observacoes TEXT,
    ativo BOOLEAN DEFAULT TRUE,
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    data_atualizacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (documento_id) REFERENCES documentos(id) ON DELETE CASCADE,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    UNIQUE KEY unique_documento_usuario (documento_id, usuario_id)
);

-- ================================================
-- INSERÇÃO DE DADOS INICIAIS
-- ================================================

-- Planos padrão
INSERT INTO planos (nome, descricao, preco, limite_usuarios, limite_documentos, limite_assinaturas, limite_filiais, limite_armazenamento_mb, dias) VALUES
('Plano Trial 5 Dias', 'Ideal para testes', 0, 5, 10, 10, 1, 100, 5),
('Plano Básico', 'Ideal para pequenas empresas', 99.90, 5, 50, 100, 2, 1024, 30),
('Plano Profissional', 'Para empresas médias', 199.90, 15, 200, 500, 5, 5120, 30),
('Plano Enterprise', 'Para grandes empresas', 399.90, 50, 1000, 2000, 999999, 51200, 365);

-- Profissões padrão
INSERT INTO profissoes (nome, descricao) VALUES
('Advogado', 'Profissional do direito'),
('Contador', 'Profissional de contabilidade'),
('Administrador', 'Profissional de administração'),
('Engenheiro', 'Profissional de engenharia'),
('Médico', 'Profissional da medicina'),
('Arquiteto', 'Profissional de arquitetura'),
('Consultor', 'Profissional de consultoria'),
('Analista', 'Profissional de análise'),
('Gerente', 'Profissional de gestão'),
('Diretor', 'Profissional de direção'),
('Empresário', 'Proprietário de empresa'),
('Outros', 'Outras profissões não listadas');

-- Tipos de documentos padrão
INSERT INTO tipos_documentos (nome, descricao) VALUES
('Contrato', 'Contratos em geral'),
('Holerite', 'Folha de pagamento'),
('Declaração', 'Declarações diversas'),
('Procuração', 'Procurações e mandatos'),
('Termo de Compromisso', 'Termos de compromisso e responsabilidade'),
('Ata', 'Atas de reunião'),
('Relatório', 'Relatórios técnicos e gerenciais'),
('Proposta Comercial', 'Propostas e orçamentos'),
('Acordo', 'Acordos e termos'),
('Certificado', 'Certificados diversos'),
('Autorização', 'Autorizações e permissões'),
('Outros', 'Outros tipos de documentos');

-- Empresa exemplo
INSERT INTO empresas (nome, cnpj, codigo_empresa, email, telefone, endereco, cidade, estado, cep, plano_id, data_vencimento) VALUES
('Empresa Exemplo LTDA', '95264309000103', 'EMP001', 'contato@exemplo.com', '(11) 99999-0000', 'Rua Exemplo, 123', 'São Paulo', 'SP', '01234-567', 1, DATE_ADD(CURDATE(), INTERVAL 30 DAY));

-- Filial exemplo
INSERT INTO filiais (empresa_id, nome, cnpj, inscricao_estadual, endereco, cidade, estado, cep, telefone, email, responsavel, observacoes) VALUES
(1, 'Matriz', '95264309000103', '1234567', 'Rua Exemplo, 123', 'São Paulo', 'SP', '68500300', '(11) 99999-0000', 'matriz@exemplo.com', 'Teste Responsavel', 'Teste Observacoes');

-- Usuários padrão (senha: 123456)
INSERT INTO usuarios (nome, email, senha, cpf, telefone, profissao_id, tipo_usuario, empresa_id, filial_id) VALUES
('Super Admin', 'admin@docgest.com', '$2y$10$1qj.4/H4WUQPboyYZm8iMOyQSipR3MKT.LCOAv.mfAVrpT405zEcG', '000.000.000-00', '(11) 99999-0001', 3, 1, NULL, NULL),
('Admin Empresa', 'admin@exemplo.com', '$2y$10$1qj.4/H4WUQPboyYZm8iMOyQSipR3MKT.LCOAv.mfAVrpT405zEcG', '111.111.111-11', '(11) 99999-0002', 3, 2, 1, 1),
('Usuário Teste', 'usuario@exemplo.com', '$2y$10$1qj.4/H4WUQPboyYZm8iMOyQSipR3MKT.LCOAv.mfAVrpT405zEcG', '222.222.222-22', '(11) 99999-0003', 1, 3, 1, 1);

-- Documento exemplo
INSERT INTO documentos (titulo, descricao, nome_arquivo, caminho_arquivo, tamanho_arquivo, tipo_arquivo, status, criado_por, empresa_id, filial_id) VALUES
('Contrato de Exemplo', 'Documento de exemplo para testes', 'contrato-exemplo.pdf', '/uploads/documentos/contrato-exemplo.pdf', 1024000, 'application/pdf', 'rascunho', 2, 1, 1);

-- ================================================
-- ÍNDICES PARA PERFORMANCE
-- ================================================
CREATE INDEX idx_usuarios_email ON usuarios(email);
CREATE INDEX idx_usuarios_empresa ON usuarios(empresa_id);
CREATE INDEX idx_usuarios_tipo ON usuarios(tipo_usuario);
CREATE INDEX idx_usuarios_profissao ON usuarios(profissao_id);
CREATE INDEX idx_profissoes_nome ON profissoes(nome);
CREATE INDEX idx_profissoes_ativo ON profissoes(ativo);
CREATE INDEX idx_empresas_cnpj ON empresas(cnpj);
CREATE INDEX idx_tipos_documentos_nome ON tipos_documentos(nome);
CREATE INDEX idx_tipos_documentos_ativo ON tipos_documentos(ativo);
CREATE INDEX idx_documentos_empresa ON documentos(empresa_id);
CREATE INDEX idx_documentos_status ON documentos(status);
CREATE INDEX idx_documentos_tipo ON documentos(tipo_documento_id);
CREATE INDEX idx_documentos_prazo_assinatura ON documentos(prazo_assinatura);
CREATE INDEX idx_documentos_competencia ON documentos(competencia);
CREATE INDEX idx_documentos_validade_legal ON documentos(validade_legal);
CREATE INDEX idx_assinaturas_documento ON assinaturas(documento_id);
CREATE INDEX idx_assinaturas_status ON assinaturas(status);
CREATE UNIQUE INDEX idx_signatarios_token ON signatarios(token);
CREATE INDEX idx_signatarios_email ON signatarios(email);

-- ================================================
-- VIEWS ÚTEIS
-- ================================================

-- View para usuários com informações completas
CREATE VIEW vw_usuarios_completo AS
SELECT 
    u.id,
    u.nome,
    u.email,
    u.cpf,
    u.telefone,
    u.profissao_id,
    p.nome as profissao_nome,
    u.tipo_usuario,
    CASE 
        WHEN u.tipo_usuario = 1 THEN 'Super Admin'
        WHEN u.tipo_usuario = 2 THEN 'Admin Empresa'
        WHEN u.tipo_usuario = 3 THEN 'Assinante'
        ELSE 'Desconhecido'
    END as tipo_usuario_nome,
    u.empresa_id,
    e.nome as empresa_nome,
    u.filial_id,
    f.nome as filial_nome,
    u.ativo,
    u.data_criacao
FROM usuarios u
LEFT JOIN profissoes p ON u.profissao_id = p.id
LEFT JOIN empresas e ON u.empresa_id = e.id
LEFT JOIN filiais f ON u.filial_id = f.id;

-- View para documentos com informações completas
CREATE VIEW vw_documentos_completo AS
SELECT 
    d.id,
    d.titulo,
    d.descricao,
    d.nome_arquivo,
    d.caminho_arquivo,
    d.tamanho_arquivo,
    d.tipo_arquivo,
    d.status,
    d.tipo_documento_id,
    td.nome as tipo_documento_nome,
    d.prazo_assinatura,
    d.competencia,
    d.validade_legal,
    d.criado_por,
    u.nome as criado_por_nome,
    d.empresa_id,
    e.nome as empresa_nome,
    d.filial_id,
    f.nome as filial_nome,
    d.data_criacao,
    d.data_atualizacao
FROM documentos d
LEFT JOIN usuarios u ON d.criado_por = u.id
LEFT JOIN empresas e ON d.empresa_id = e.id
LEFT JOIN filiais f ON d.filial_id = f.id
LEFT JOIN tipos_documentos td ON d.tipo_documento_id = td.id
WHERE d.ativo = 1;

-- View para assinaturas com informações completas
CREATE VIEW vw_assinaturas_completo AS
SELECT 
    a.id,
    a.documento_id,
    d.titulo as documento_titulo,
    a.status,
    a.data_expiracao,
    a.criado_por,
    u.nome as criado_por_nome,
    a.empresa_id,
    e.nome as empresa_nome,
    a.filial_id,
    f.nome as filial_nome,
    a.data_criacao,
    COUNT(s.id) as total_signatarios,
    SUM(CASE WHEN s.status = 'assinado' THEN 1 ELSE 0 END) as signatarios_assinados,
    SUM(CASE WHEN s.status = 'pendente' THEN 1 ELSE 0 END) as signatarios_pendentes
FROM assinaturas a
LEFT JOIN documentos d ON a.documento_id = d.id
LEFT JOIN usuarios u ON a.criado_por = u.id
LEFT JOIN empresas e ON a.empresa_id = e.id
LEFT JOIN filiais f ON a.filial_id = f.id
LEFT JOIN signatarios s ON a.id = s.assinatura_id
WHERE a.ativo = 1
GROUP BY a.id;

-- ================================================
-- TRIGGERS PARA AUDITORIA
-- ================================================

-- Trigger para atualizar data_atualizacao automaticamente
DELIMITER //
CREATE TRIGGER tr_usuarios_updated 
    BEFORE UPDATE ON usuarios 
    FOR EACH ROW 
BEGIN
    SET NEW.data_atualizacao = CURRENT_TIMESTAMP;
END//

CREATE TRIGGER tr_empresas_updated 
    BEFORE UPDATE ON empresas 
    FOR EACH ROW 
BEGIN
    SET NEW.data_atualizacao = CURRENT_TIMESTAMP;
END//

CREATE TRIGGER tr_documentos_updated 
    BEFORE UPDATE ON documentos 
    FOR EACH ROW 
BEGIN
    SET NEW.data_atualizacao = CURRENT_TIMESTAMP;
END//

DELIMITER ;

-- ================================================
-- NOTIFICAÇÕES
-- ================================================
CREATE TABLE notificacoes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(200) NOT NULL,
    mensagem TEXT NOT NULL,
    tipo ENUM('info','success','warning','error') DEFAULT 'info',
    usuario_destinatario_id INT NOT NULL,
    usuario_remetente_id INT,
    empresa_id INT,
    lida BOOLEAN DEFAULT FALSE,
    data_leitura DATETIME,
    ativo BOOLEAN DEFAULT TRUE,
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    data_atualizacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_destinatario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (usuario_remetente_id) REFERENCES usuarios(id) ON DELETE SET NULL,
    FOREIGN KEY (empresa_id) REFERENCES empresas(id) ON DELETE CASCADE
);

-- Índices para performance das notificações
CREATE INDEX idx_notificacoes_destinatario ON notificacoes(usuario_destinatario_id);
CREATE INDEX idx_notificacoes_empresa ON notificacoes(empresa_id);
CREATE INDEX idx_notificacoes_lida ON notificacoes(lida);
CREATE INDEX idx_notificacoes_tipo ON notificacoes(tipo);

-- ================================================
-- CONFIGURAÇÕES DO SISTEMA
-- ================================================
CREATE TABLE configuracoes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    chave VARCHAR(100) NOT NULL UNIQUE,
    valor TEXT,
    tipo ENUM('string','number','boolean','json') DEFAULT 'string',
    descricao TEXT,
    categoria VARCHAR(50) DEFAULT 'geral',
    ativo BOOLEAN DEFAULT TRUE,
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    data_atualizacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Índices para performance das configurações
CREATE INDEX idx_configuracoes_chave ON configuracoes(chave);
CREATE INDEX idx_configuracoes_categoria ON configuracoes(categoria);
CREATE INDEX idx_configuracoes_ativo ON configuracoes(ativo);

-- Inserção das configurações padrão
INSERT INTO configuracoes (chave, valor, tipo, descricao, categoria) VALUES
-- Configurações gerais
('app_name', 'DocGest', 'string', 'Nome da aplicação', 'geral'),
('max_file_size', '10', 'number', 'Tamanho máximo de arquivo em MB', 'geral'),
('allowed_file_types', 'pdf,doc,docx,jpg,jpeg,png', 'string', 'Tipos de arquivo permitidos', 'geral'),

-- Configurações de email
('smtp_host', '', 'string', 'Servidor SMTP', 'email'),
('smtp_port', '587', 'number', 'Porta SMTP', 'email'),
('smtp_username', '', 'string', 'Usuário SMTP', 'email'),
('smtp_password', '', 'string', 'Senha SMTP', 'email'),
('smtp_from_email', '', 'string', 'Email remetente', 'email'),
('smtp_from_name', '', 'string', 'Nome remetente', 'email'),

-- Configurações de notificação
('email_notifications', 'true', 'boolean', 'Notificações por email', 'notificacao'),
('whatsapp_notifications', 'false', 'boolean', 'Notificações por WhatsApp', 'notificacao'),
('signature_reminders', 'true', 'boolean', 'Lembretes de assinatura', 'notificacao'),
('expiration_alerts', 'true', 'boolean', 'Alertas de expiração', 'notificacao'),

-- Configurações de segurança
('password_min_length', '8', 'number', 'Comprimento mínimo da senha', 'seguranca'),
('require_password_complexity', 'true', 'boolean', 'Exigir complexidade de senha', 'seguranca'),
('session_timeout', '24', 'number', 'Timeout de sessão em horas', 'seguranca'),
('max_login_attempts', '5', 'number', 'Máximo de tentativas de login', 'seguranca'),

-- Configurações de assinatura
('signature_expiration_days', '30', 'number', 'Dias para expiração de assinatura', 'assinatura'),
('auto_reminder_days', '7', 'number', 'Dias para lembrete automático', 'assinatura'),
('max_signers_per_document', '10', 'number', 'Máximo de signatários por documento', 'assinatura');

-- ================================================
-- SOLICITAÇÕES DE EXCLUSÃO (LGPD)
-- ================================================
CREATE TABLE solicitacoes_exclusao (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_solicitante_id INT NOT NULL,
    usuario_alvo_id INT NOT NULL,
    motivo ENUM('inatividade','mudanca_empresa','solicitacao_titular','violacao_politica','outros') NOT NULL,
    motivo_detalhado TEXT,
    status ENUM('pendente','aprovada','rejeitada','processada') DEFAULT 'pendente',
    justificativa_resposta TEXT,
    processada_por INT,
    data_processamento DATETIME,
    empresa_id INT NOT NULL,
    ativo BOOLEAN DEFAULT TRUE,
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    data_atualizacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_solicitante_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (usuario_alvo_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (processada_por) REFERENCES usuarios(id) ON DELETE SET NULL,
    FOREIGN KEY (empresa_id) REFERENCES empresas(id) ON DELETE CASCADE
);

-- Índices para performance das solicitações de exclusão
CREATE INDEX idx_solicitacoes_exclusao_solicitante ON solicitacoes_exclusao(usuario_solicitante_id);
CREATE INDEX idx_solicitacoes_exclusao_alvo ON solicitacoes_exclusao(usuario_alvo_id);
CREATE INDEX idx_solicitacoes_exclusao_status ON solicitacoes_exclusao(status);
CREATE INDEX idx_solicitacoes_exclusao_empresa ON solicitacoes_exclusao(empresa_id);

-- ================================================
-- COMENTÁRIOS FINAIS
-- ================================================
-- Este banco de dados foi criado para o sistema DocGest
-- Sistema de gestão de documentos e assinaturas eletrônicas
-- Versão: 1.0
-- Data: 2024


