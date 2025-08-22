<?php
require_once 'config/database.php';

try {
    $pdo = getConnection();
    
    // Primeiro, criar a tabela sem foreign keys
    $sql1 = "
    CREATE TABLE IF NOT EXISTS `solicitacoes_exclusao` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `usuario_solicitante_id` int(11) NOT NULL COMMENT 'ID do admin que fez a solicitação',
        `usuario_alvo_id` int(11) NOT NULL COMMENT 'ID do usuário a ser excluído',
        `motivo` enum('dados_incorretos','nao_utiliza_mais','duplicacao','violacao_termos','solicitacao_usuario','outro') NOT NULL COMMENT 'Motivo da solicitação',
        `detalhes` text DEFAULT NULL COMMENT 'Detalhes adicionais sobre o motivo',
        `status` enum('pendente','aprovada','rejeitada') NOT NULL DEFAULT 'pendente',
        `justificativa_resposta` text DEFAULT NULL COMMENT 'Justificativa da aprovação/rejeição',
        `processado_por` int(11) DEFAULT NULL COMMENT 'ID do super admin que processou',
        `data_solicitacao` timestamp NOT NULL DEFAULT current_timestamp(),
        `data_processamento` timestamp NULL DEFAULT NULL,
        `empresa_id` int(11) NOT NULL COMMENT 'Empresa do solicitante',
        PRIMARY KEY (`id`),
        KEY `idx_usuario_solicitante` (`usuario_solicitante_id`),
        KEY `idx_usuario_alvo` (`usuario_alvo_id`),
        KEY `idx_status` (`status`),
        KEY `idx_empresa` (`empresa_id`),
        KEY `idx_data_solicitacao` (`data_solicitacao`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Solicitações de exclusão de usuários (LGPD)';
    ";
    
    // Depois, adicionar as foreign keys
    $sql2 = "
    ALTER TABLE `solicitacoes_exclusao`
    ADD CONSTRAINT `fk_solicitacoes_exclusao_solicitante` FOREIGN KEY (`usuario_solicitante_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
    ADD CONSTRAINT `fk_solicitacoes_exclusao_alvo` FOREIGN KEY (`usuario_alvo_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
    ADD CONSTRAINT `fk_solicitacoes_exclusao_processador` FOREIGN KEY (`processado_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL,
    ADD CONSTRAINT `fk_solicitacoes_exclusao_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`) ON DELETE CASCADE;
    ";
    
    // Executar primeiro SQL (criar tabela)
    $pdo->exec($sql1);
    echo "✅ Tabela 'solicitacoes_exclusao' criada com sucesso!\n";
    
    // Executar segundo SQL (adicionar foreign keys) - apenas se a tabela não tinha constraints
    try {
        $pdo->exec($sql2);
        echo "✅ Foreign keys adicionadas com sucesso!\n";
    } catch (Exception $e) {
        if (strpos($e->getMessage(), 'Duplicate key name') !== false) {
            echo "ℹ️ Foreign keys já existem.\n";
        } else {
            echo "⚠️ Aviso ao adicionar foreign keys: " . $e->getMessage() . "\n";
        }
    }
    
    // Verificar se a tabela foi criada
    $stmt = $pdo->query("SHOW TABLES LIKE 'solicitacoes_exclusao'");
    $tableExists = $stmt->rowCount() > 0;
    
    if ($tableExists) {
        echo "✅ Confirmação: Tabela existe no banco de dados.\n";
        
        // Verificar estrutura da tabela
        $stmt = $pdo->query("DESCRIBE solicitacoes_exclusao");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "\n📋 Estrutura da tabela criada:\n";
        foreach ($columns as $column) {
            echo "- {$column['Field']}: {$column['Type']}\n";
        }
    }
    
} catch (Exception $e) {
    echo "❌ Erro ao criar tabela: " . $e->getMessage() . "\n";
}
?>