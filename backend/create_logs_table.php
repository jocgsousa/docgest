<?php
require_once 'config/database.php';

try {
    $pdo = getConnection();
    
    echo "Criando tabela de logs...\n";
    
    // SQL para criar a tabela
    $createTableSQL = "
        CREATE TABLE IF NOT EXISTS logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            level ENUM('debug', 'info', 'warning', 'error') NOT NULL,
            message TEXT NOT NULL,
            context TEXT NULL,
            user_id INT NULL,
            empresa_id INT NULL,
            ip_address VARCHAR(45) NULL,
            user_agent TEXT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_level (level),
            INDEX idx_user_id (user_id),
            INDEX idx_empresa_id (empresa_id),
            INDEX idx_created_at (created_at),
            FOREIGN KEY (user_id) REFERENCES usuarios(id) ON DELETE SET NULL,
            FOREIGN KEY (empresa_id) REFERENCES empresas(id) ON DELETE SET NULL
        )
    ";
    
    $pdo->exec($createTableSQL);
    echo "Tabela 'logs' criada com sucesso!\n";
    
    // Verificar se já existem logs
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM logs");
    $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    if ($count == 0) {
        echo "Inserindo logs de exemplo...\n";
        
        $insertSQL = "
            INSERT INTO logs (level, message, context, user_id, empresa_id, ip_address, user_agent) VALUES
            ('info', 'Sistema iniciado', 'Sistema de logs implementado', NULL, NULL, '127.0.0.1', 'DocGest System'),
            ('info', 'Usuário fez login', 'Login realizado com sucesso', 1, NULL, '192.168.1.100', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'),
            ('warning', 'Tentativa de login com credenciais inválidas', 'Email: teste@exemplo.com', NULL, NULL, '192.168.1.101', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'),
            ('error', 'Erro ao processar documento', 'Arquivo corrompido ou formato inválido', 2, 1, '192.168.1.102', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'),
            ('info', 'Documento criado', 'Documento \"Contrato de Serviços\" criado com sucesso', 2, 1, '192.168.1.102', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'),
            ('info', 'Assinatura realizada', 'Documento assinado digitalmente', 3, 1, '192.168.1.103', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'),
            ('debug', 'Cache limpo', 'Cache do sistema foi limpo automaticamente', NULL, NULL, '127.0.0.1', 'DocGest System'),
            ('warning', 'Plano próximo do vencimento', 'Empresa XYZ tem plano vencendo em 5 dias', NULL, 1, '127.0.0.1', 'DocGest System'),
            ('error', 'Falha no envio de email', 'SMTP timeout ao enviar notificação', NULL, NULL, '127.0.0.1', 'DocGest System'),
            ('info', 'Backup realizado', 'Backup automático do banco de dados concluído', NULL, NULL, '127.0.0.1', 'DocGest System')
        ";
        
        $pdo->exec($insertSQL);
        echo "Logs de exemplo inseridos com sucesso!\n";
    } else {
        echo "Tabela já contém $count logs.\n";
    }
    
    echo "\nSistema de logs configurado com sucesso!\n";
    
} catch (Exception $e) {
    echo "Erro: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}
?>