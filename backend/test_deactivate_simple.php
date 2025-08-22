<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/models/User.php';

echo "=== TESTE SIMPLES DE DESATIVAÇÃO ===\n";

try {
    // Verificar usuário antes
    $database = new Database();
    $conn = $database->getConnection();
    
    echo "1. Status antes da desativação:\n";
    $stmt = $conn->prepare('SELECT id, nome, email, ativo FROM usuarios WHERE id = 2');
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        echo "   - Usuário: {$user['nome']} ({$user['email']})\n";
        echo "   - Status ativo: {$user['ativo']}\n";
    } else {
        echo "   - Usuário não encontrado!\n";
        exit(1);
    }
    
    // Testar desativação direta no modelo
    echo "\n2. Testando desativação no modelo User...\n";
    $userModel = new User($conn);
    $result = $userModel->deactivate(2);
    
    echo "   - Resultado da desativação: " . ($result ? 'SUCESSO' : 'FALHA') . "\n";
    
    // Verificar usuário depois
    echo "\n3. Status após a desativação:\n";
    $stmt = $conn->prepare('SELECT id, nome, email, ativo FROM usuarios WHERE id = 2');
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        echo "   - Usuário: {$user['nome']} ({$user['email']})\n";
        echo "   - Status ativo: {$user['ativo']}\n";
    }
    
} catch (Exception $e) {
    echo "ERRO: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}

echo "\n=== FIM TESTE ===\n";
?>