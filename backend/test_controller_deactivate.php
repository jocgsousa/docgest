<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/models/User.php';
require_once __DIR__ . '/controllers/UserController.php';
require_once __DIR__ . '/utils/JWT.php';
require_once __DIR__ . '/utils/Response.php';

echo "=== TESTE CONTROLLER DESATIVAÇÃO ===\n";

try {
    // Simular token JWT válido
    $payload = [
        'user_id' => 1,
        'tipo_usuario' => 1, // super admin
        'empresa_id' => null,
        'exp' => time() + 3600
    ];
    
    $token = JWT::encode($payload, JWT_SECRET);
    $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer ' . $token;
    
    echo "1. Token JWT criado e configurado\n";
    
    // Verificar usuário antes
    $database = new Database();
    $conn = $database->getConnection();
    
    echo "\n2. Status antes da desativação:\n";
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
    
    // Testar controller
    echo "\n3. Testando UserController->deactivate(2)...\n";
    
    // Capturar output do controller
    ob_start();
    
    try {
        $userController = new UserController();
        $userController->deactivate(2);
        echo "   - Controller executado sem exceções\n";
    } catch (Exception $e) {
        echo "   - Exceção capturada: " . $e->getMessage() . "\n";
        echo "   - Arquivo: " . $e->getFile() . "\n";
        echo "   - Linha: " . $e->getLine() . "\n";
    }
    
    $output = ob_get_clean();
    echo "   - Output do controller: " . trim($output) . "\n";
    
    // Verificar usuário depois
    echo "\n4. Status após tentativa de desativação:\n";
    $stmt = $conn->prepare('SELECT id, nome, email, ativo FROM usuarios WHERE id = 2');
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        echo "   - Usuário: {$user['nome']} ({$user['email']})\n";
        echo "   - Status ativo: {$user['ativo']}\n";
    }
    
} catch (Exception $e) {
    echo "ERRO GERAL: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}

echo "\n=== FIM TESTE ===\n";
?>