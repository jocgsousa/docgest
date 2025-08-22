<?php
require_once 'config/database.php';

try {
    $database = new Database();
    $pdo = $database->getConnection();
    
    echo "🔄 Iniciando migração da tabela solicitacoes_exclusao para solicitacoes_lgpd...\n";
    
    // Verificar se a tabela antiga existe
    $stmt = $pdo->query("SHOW TABLES LIKE 'solicitacoes_exclusao'");
    if ($stmt->rowCount() == 0) {
        echo "❌ Tabela 'solicitacoes_exclusao' não encontrada. Nada para migrar.\n";
        exit;
    }
    
    // Verificar se a nova tabela já existe
    $stmt = $pdo->query("SHOW TABLES LIKE 'solicitacoes_lgpd'");
    if ($stmt->rowCount() > 0) {
        echo "⚠️  Tabela 'solicitacoes_lgpd' já existe. Removendo para recriar...\n";
        $pdo->exec("DROP TABLE solicitacoes_lgpd");
    }
    
    // Criar nova tabela com estrutura atualizada
    echo "📋 Criando nova tabela 'solicitacoes_lgpd'...\n";
    $createTableSQL = "
        CREATE TABLE solicitacoes_lgpd (
            id INT AUTO_INCREMENT PRIMARY KEY,
            usuario_solicitante_id INT NOT NULL,
            usuario_alvo_id INT NOT NULL,
            tipo_solicitacao ENUM('exclusao','portabilidade','retificacao','acesso','oposicao','outros') NOT NULL DEFAULT 'exclusao',
            motivo ENUM('inatividade','mudanca_empresa','solicitacao_titular','violacao_politica','outros') NOT NULL,
            detalhes TEXT,
            status ENUM('pendente','aprovada','rejeitada','processada') DEFAULT 'pendente',
            justificativa_resposta TEXT,
            processado_por INT,
            data_processamento DATETIME,
            empresa_id INT NOT NULL,
            ativo BOOLEAN DEFAULT TRUE,
            data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            data_atualizacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (usuario_solicitante_id) REFERENCES usuarios(id) ON DELETE CASCADE,
            FOREIGN KEY (usuario_alvo_id) REFERENCES usuarios(id) ON DELETE CASCADE,
            FOREIGN KEY (processado_por) REFERENCES usuarios(id) ON DELETE SET NULL,
            FOREIGN KEY (empresa_id) REFERENCES empresas(id) ON DELETE CASCADE
        )
    ";
    
    $pdo->exec($createTableSQL);
    
    // Migrar dados da tabela antiga para a nova
    echo "📊 Migrando dados da tabela antiga...\n";
    $migrateDataSQL = "
        INSERT INTO solicitacoes_lgpd 
        (id, usuario_solicitante_id, usuario_alvo_id, tipo_solicitacao, motivo, detalhes, status, justificativa_resposta, processado_por, data_processamento, empresa_id, data_criacao)
        SELECT 
            id, 
            usuario_solicitante_id, 
            usuario_alvo_id, 
            'exclusao' as tipo_solicitacao,
            motivo, 
            detalhes,
            status, 
            justificativa_resposta,
            processado_por,
            data_processamento, 
            empresa_id, 
            data_solicitacao as data_criacao
         FROM solicitacoes_exclusao
     ";
    
    $pdo->exec($migrateDataSQL);
    
    // Contar registros migrados
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM solicitacoes_lgpd");
    $total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    echo "✅ {$total} registros migrados com sucesso!\n";
    
    // Criar índices para performance
    echo "🔍 Criando índices...\n";
    $indexes = [
        "CREATE INDEX idx_solicitacoes_lgpd_solicitante ON solicitacoes_lgpd(usuario_solicitante_id)",
        "CREATE INDEX idx_solicitacoes_lgpd_alvo ON solicitacoes_lgpd(usuario_alvo_id)",
        "CREATE INDEX idx_solicitacoes_lgpd_status ON solicitacoes_lgpd(status)",
        "CREATE INDEX idx_solicitacoes_lgpd_empresa ON solicitacoes_lgpd(empresa_id)",
        "CREATE INDEX idx_solicitacoes_lgpd_tipo ON solicitacoes_lgpd(tipo_solicitacao)"
    ];
    
    foreach ($indexes as $indexSQL) {
        try {
            $pdo->exec($indexSQL);
        } catch (Exception $e) {
            echo "⚠️  Aviso ao criar índice: " . $e->getMessage() . "\n";
        }
    }
    
    echo "\n🎉 Migração concluída com sucesso!\n";
    echo "📝 Próximos passos:\n";
    echo "   1. Teste a aplicação para garantir que tudo funciona\n";
    echo "   2. Se tudo estiver OK, remova a tabela antiga: DROP TABLE solicitacoes_exclusao;\n";
    echo "   3. Atualize o database.sql com a nova estrutura\n";
    
} catch (Exception $e) {
    echo "❌ Erro durante a migração: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}
?>