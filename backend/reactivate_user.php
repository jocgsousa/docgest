<?php
require_once __DIR__ . '/config/database.php';

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    $stmt = $conn->prepare('UPDATE usuarios SET ativo = 1 WHERE id = 2');
    $result = $stmt->execute();
    
    echo 'Usuário ID 2 reativado: ' . ($result ? 'Sucesso' : 'Falha') . PHP_EOL;
    
    // Verificar status atual
    $stmt = $conn->prepare('SELECT id, nome, email, ativo FROM usuarios WHERE id = 2');
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        echo "Status atual do usuário:\n";
        echo "- Nome: {$user['nome']}\n";
        echo "- Email: {$user['email']}\n";
        echo "- Ativo: {$user['ativo']}\n";
    }
    
} catch (Exception $e) {
    echo "Erro: " . $e->getMessage() . PHP_EOL;
}
?>