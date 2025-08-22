<?php
require_once __DIR__ . '/config/database.php';

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    $stmt = $conn->prepare('SELECT id, nome, email, ativo FROM usuarios WHERE id = 2');
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        echo "Status atual do usuário ID 2:\n";
        echo "- Nome: {$user['nome']}\n";
        echo "- Email: {$user['email']}\n";
        echo "- Ativo: {$user['ativo']}\n";
    } else {
        echo "Usuário não encontrado\n";
    }
    
} catch (Exception $e) {
    echo "Erro: " . $e->getMessage() . "\n";
}
?>