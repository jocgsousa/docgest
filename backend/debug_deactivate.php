<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/models/User.php';
require_once __DIR__ . '/controllers/UserController.php';

echo "=== DEBUG DESATIVAÇÃO DE USUÁRIO ===\n";

try {
    // Simular dados da sessão
    $_SESSION = [
        'user_id' => 1,
        'user_type' => 'super_admin',
        'empresa_id' => null
    ];
    
    $userController = new UserController();
    
    echo "1. Verificando usuário antes da desativação...\n";
    $database = new Database();
    $conn = $database->getConnection();
    $stmt = $conn->prepare('SELECT id, nome, email, ativo FROM usuarios WHERE id = 2');
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        echo "   - ID: {$user['id']}\n";
        echo "   - Nome: {$user['nome']}\n";
        echo "   - Email: {$user['email']}\n";
        echo "   - Ativo: {$user['ativo']}\n";
    } else {
        echo "   - Usuário não encontrado!\n";
        exit(1);
    }
    
    echo "\n2. Tentando desativar usuário...\n";
    
    // Capturar output
    ob_start();
    $userController->deactivate(2);
    $output = ob_get_clean();
    
    echo "   - Output do controller: $output\n";
    
    echo "\n3. Verificando usuário após tentativa de desativação...\n";
    $stmt = $conn->prepare('SELECT id, nome, email, ativo FROM usuarios WHERE id = 2');
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        echo "   - ID: {$user['id']}\n";
        echo "   - Nome: {$user['nome']}\n";
        echo "   - Email: {$user['email']}\n";
        echo "   - Ativo: {$user['ativo']}\n";
    }
    
} catch (Exception $e) {
    echo "ERRO: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}

echo "\n=== FIM DEBUG ===\n";
?>