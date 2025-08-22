<?php
require_once 'config/database.php';

try {
    $pdo = getConnection();
    
    // Verificar se a tabela solicitacoes_exclusao existe
    $stmt = $pdo->query("SHOW TABLES LIKE 'solicitacoes_exclusao'");
    $tableExists = $stmt->rowCount() > 0;
    
    if ($tableExists) {
        echo "✅ Tabela 'solicitacoes_exclusao' existe no banco de dados.\n";
        
        // Verificar estrutura da tabela
        $stmt = $pdo->query("DESCRIBE solicitacoes_exclusao");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "\n📋 Estrutura da tabela:\n";
        foreach ($columns as $column) {
            echo "- {$column['Field']}: {$column['Type']} ({$column['Null']}, {$column['Key']})\n";
        }
        
        // Verificar se há registros
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM solicitacoes_exclusao");
        $count = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        echo "\n📊 Total de registros: {$count}\n";
        
    } else {
        echo "❌ Tabela 'solicitacoes_exclusao' NÃO existe no banco de dados.\n";
        echo "Execute o script database.sql para criar a tabela.\n";
    }
    
} catch (PDOException $e) {
    echo "❌ Erro ao conectar com o banco: " . $e->getMessage() . "\n";
}
?>