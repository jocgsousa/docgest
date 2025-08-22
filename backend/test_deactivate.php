<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/models/User.php';
require_once __DIR__ . '/utils/JWT.php';

try {
    echo "=== TESTE DE DESATIVAÇÃO DE USUÁRIO ===\n\n";
    
    // Simular token de admin
    $adminData = [
        'user_id' => 1,
        'email' => 'admin@docgest.com',
        'tipo_usuario' => 1 // Super Admin
    ];
    
    $userModel = new User();
    $userId = 2;
    
    echo "1. Buscando usuário ID $userId...\n";
    $user = $userModel->findById($userId);
    
    if (!$user) {
        echo "ERRO: Usuário não encontrado\n";
        exit(1);
    }
    
    echo "Usuário encontrado:\n";
    echo "- Nome: {$user['nome']}\n";
    echo "- Email: {$user['email']}\n";
    echo "- Ativo: {$user['ativo']}\n";
    echo "- Empresa ID: {$user['empresa_id']}\n\n";
    
    echo "2. Verificando permissões...\n";
    if ($adminData['tipo_usuario'] == 2 && $user['empresa_id'] != $adminData['empresa_id']) {
        echo "ERRO: Sem permissão para desativar este usuário\n";
        exit(1);
    }
    
    if ($user['id'] == $adminData['user_id']) {
        echo "ERRO: Não pode desativar o próprio usuário\n";
        exit(1);
    }
    
    echo "Permissões OK\n\n";
    
    echo "3. Tentando desativar usuário...\n";
    $success = $userModel->deactivate($userId);
    
    echo "Resultado da desativação: " . ($success ? 'SUCESSO' : 'FALHA') . "\n";
    
    if ($success) {
        echo "\n4. Verificando se usuário foi desativado...\n";
        $updatedUser = $userModel->findById($userId);
        echo "Status ativo após desativação: {$updatedUser['ativo']}\n";
        
        if ($updatedUser['ativo'] == 0) {
            echo "✓ Usuário desativado com sucesso!\n";
        } else {
            echo "✗ Usuário ainda está ativo\n";
        }
    }
    
} catch (Exception $e) {
    echo "EXCEÇÃO: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
?>