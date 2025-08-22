<?php
require_once 'config/database.php';

try {
    $pdo = getConnection();
    
    // Verificar quais tabelas existem
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "📋 Tabelas existentes no banco de dados:\n";
    foreach ($tables as $table) {
        echo "- {$table}\n";
    }
    
    // Verificar tabelas necessárias
    $requiredTables = ['usuarios', 'empresas'];
    $missingTables = [];
    
    foreach ($requiredTables as $table) {
        if (!in_array($table, $tables)) {
            $missingTables[] = $table;
        }
    }
    
    if (empty($missingTables)) {
        echo "\n✅ Todas as tabelas necessárias existem.\n";
    } else {
        echo "\n❌ Tabelas em falta: " . implode(', ', $missingTables) . "\n";
        echo "Execute o script database.sql completo para criar todas as tabelas.\n";
    }
    
} catch (Exception $e) {
    echo "❌ Erro: " . $e->getMessage() . "\n";
}
?>