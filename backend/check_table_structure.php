<?php
try {
    $pdo = new PDO(
        "mysql:host=localhost;port=3306;dbname=docgest;charset=utf8mb4",
        "root",
        ""
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Estrutura da tabela solicitacoes_exclusao:\n";
    $stmt = $pdo->query('DESCRIBE solicitacoes_exclusao');
    while($row = $stmt->fetch()) {
        echo $row['Field'] . ' - ' . $row['Type'] . "\n";
    }
    
    echo "\nPrimeiros registros:\n";
    $stmt = $pdo->query('SELECT * FROM solicitacoes_exclusao LIMIT 3');
    while($row = $stmt->fetch()) {
        print_r($row);
        echo "\n";
    }
    
} catch(PDOException $e) {
    echo "Erro: " . $e->getMessage() . "\n";
}
?>