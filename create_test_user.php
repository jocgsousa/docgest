<?php

require_once 'backend/config/database.php';

echo "=== Criando usuário de teste ===\n";

try {
    $pdo = getConnection();
    
    // Verificar se já existe um usuário de teste
    $stmt = $pdo->prepare('SELECT id FROM usuarios WHERE email = ?');
    $stmt->execute(['test@docgest.com']);
    
    if ($stmt->fetch()) {
        echo "Usuário de teste já existe. Atualizando senha...\n";
        
        $hashedPassword = password_hash('123456', PASSWORD_DEFAULT);
        $stmt = $pdo->prepare('UPDATE usuarios SET senha = ? WHERE email = ?');
        $result = $stmt->execute([$hashedPassword, 'test@docgest.com']);
        
        if ($result) {
            echo "✓ Senha do usuário de teste atualizada\n";
        } else {
            echo "✗ Erro ao atualizar senha\n";
        }
    } else {
        echo "Criando novo usuário de teste...\n";
        
        $hashedPassword = password_hash('123456', PASSWORD_DEFAULT);
        $stmt = $pdo->prepare('INSERT INTO usuarios (nome, email, senha, tipo_usuario, ativo, empresa_id) VALUES (?, ?, ?, ?, ?, ?)');
        $result = $stmt->execute(['Usuário Teste', 'test@docgest.com', $hashedPassword, 1, 1, 1]);
        
        if ($result) {
            echo "✓ Usuário de teste criado\n";
        } else {
            echo "✗ Erro ao criar usuário\n";
        }
    }
    
    // Verificar usuários Super Admin
    echo "\nUsuários Super Admin (tipo 1):\n";
    $stmt = $pdo->query('SELECT id, nome, email, tipo_usuario FROM usuarios WHERE tipo_usuario = 1');
    while($row = $stmt->fetch()) {
        echo "ID: {$row['id']}, Nome: {$row['nome']}, Email: {$row['email']}\n";
    }
    
} catch (Exception $e) {
    echo "Erro: " . $e->getMessage() . "\n";
}

echo "\n=== Fim ===\n";