-- Script para adicionar campo hash_acesso à tabela documentos
-- Este campo será usado para identificação segura dos documentos

USE docgest;

-- Adicionar coluna hash_acesso
ALTER TABLE documentos 
ADD COLUMN hash_acesso VARCHAR(64) UNIQUE NOT NULL DEFAULT '';

-- Criar índice para performance
CREATE INDEX idx_documentos_hash ON documentos(hash_acesso);

-- Gerar hash único para documentos existentes
UPDATE documentos 
SET hash_acesso = SHA2(CONCAT(id, titulo, UNIX_TIMESTAMP(data_criacao), RAND()), 256)
WHERE hash_acesso = '';

-- Verificar se todos os documentos têm hash
SELECT COUNT(*) as total_documentos, 
       COUNT(CASE WHEN hash_acesso != '' THEN 1 END) as com_hash
FROM documentos;

SELECT 'Script executado com sucesso!' as status;