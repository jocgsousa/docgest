<?php
require_once __DIR__ . '/config/database.php';

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    echo "Conexão com banco de dados: OK\n";
    
    // Testar se a tabela usuarios existe
    $stmt = $conn->query("DESCRIBE usuarios");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Estrutura da tabela usuarios:\n";
    foreach ($columns as $column) {
        echo "- {$column['Field']}: {$column['Type']}\n";
    }
    
    // Testar se o usuário ID 2 existe
    $stmt = $conn->prepare("SELECT id, nome, email, ativo FROM usuarios WHERE id = 2");
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        echo "\nUsuário ID 2 encontrado:\n";
        echo "- Nome: {$user['nome']}\n";
        echo "- Email: {$user['email']}\n";
        echo "- Ativo: {$user['ativo']}\n";
    } else {
        echo "\nUsuário ID 2 não encontrado\n";
    }
    
    // Testar update simples
    echo "\nTestando UPDATE na tabela usuarios...\n";
    $stmt = $conn->prepare("UPDATE usuarios SET data_atualizacao = NOW() WHERE id = 2");
    $result = $stmt->execute();
    
    if ($result) {
        echo "UPDATE executado com sucesso\n";
        echo "Linhas afetadas: " . $stmt->rowCount() . "\n";
    } else {
        echo "Erro no UPDATE: " . implode(', ', $stmt->errorInfo()) . "\n";
    }
    
} catch (Exception $e) {
    echo "Erro: " . $e->getMessage() . "\n";
}
?>