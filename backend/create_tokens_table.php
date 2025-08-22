<?php
require_once 'config/database.php';

try {
    $database = new Database();
    $pdo = $database->getConnection();
    
    echo "🔄 Criando tabela tokens_cadastro...\n";
    
    $createTableSQL = "
        CREATE TABLE IF NOT EXISTS tokens_cadastro (
            id INT AUTO_INCREMENT PRIMARY KEY,
            token_hash VARCHAR(64) NOT NULL UNIQUE,
            empresa_id INT NOT NULL,
            tipo_usuario ENUM('assinante','admin_empresa','super_admin') NOT NULL DEFAULT 'assinante',
            email_destinatario VARCHAR(255),
            criado_por INT NOT NULL,
            usado BOOLEAN DEFAULT FALSE,
            data_uso DATETIME NULL,
            usuario_criado_id INT NULL,
            data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            data_expiracao DATETIME NOT NULL,
            ativo BOOLEAN DEFAULT TRUE,
            FOREIGN KEY (empresa_id) REFERENCES empresas(id) ON DELETE CASCADE,
            FOREIGN KEY (criado_por) REFERENCES usuarios(id) ON DELETE CASCADE,
            FOREIGN KEY (usuario_criado_id) REFERENCES usuarios(id) ON DELETE SET NULL
        )
    ";
    
    $pdo->exec($createTableSQL);
    
    echo "📋 Criando índices...\n";
    
    $indexes = [
        "CREATE INDEX IF NOT EXISTS idx_tokens_cadastro_hash ON tokens_cadastro(token_hash)",
        "CREATE INDEX IF NOT EXISTS idx_tokens_cadastro_empresa ON tokens_cadastro(empresa_id)",
        "CREATE INDEX IF NOT EXISTS idx_tokens_cadastro_expiracao ON tokens_cadastro(data_expiracao)",
        "CREATE INDEX IF NOT EXISTS idx_tokens_cadastro_ativo ON tokens_cadastro(ativo)"
    ];
    
    foreach ($indexes as $indexSQL) {
        try {
            $pdo->exec($indexSQL);
        } catch (Exception $e) {
            echo "⚠️  Aviso ao criar índice: " . $e->getMessage() . "\n";
        }
    }
    
    echo "✅ Tabela tokens_cadastro criada com sucesso!\n";
    
} catch (Exception $e) {
    echo "❌ Erro ao criar tabela: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}
?>