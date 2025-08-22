<?php
try {
    $pdo = new PDO(
        "mysql:host=localhost;dbname=docgest;charset=utf8mb4",
        "root",
        ""
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "Conexão com o banco de dados bem-sucedida!\n";
    
    // Testar se a tabela existe
    $stmt = $pdo->query("SHOW TABLES LIKE 'solicitacoes_exclusao'");
    if ($stmt->rowCount() > 0) {
        echo "Tabela 'solicitacoes_exclusao' encontrada.\n";
        
        // Contar registros
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM solicitacoes_exclusao");
        $result = $stmt->fetch();
        echo "Total de solicitações: " . $result['total'] . "\n";
    } else {
        echo "Tabela 'solicitacoes_exclusao' não encontrada.\n";
    }
    
} catch(PDOException $e) {
    echo "Erro de conexão: " . $e->getMessage() . "\n";
    echo "Código do erro: " . $e->getCode() . "\n";
}
?>