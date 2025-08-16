<?php
require_once 'config/database.php';

try {
    $db = getConnection();
    
    // Verificar usuários existentes
    $stmt = $db->query('SELECT id, nome, email, tipo_usuario FROM usuarios WHERE ativo = 1');
    $users = $stmt->fetchAll();
    
    echo "Usuários existentes:\n";
    foreach ($users as $user) {
        echo "ID: {$user['id']}, Nome: {$user['nome']}, Email: {$user['email']}, Tipo: {$user['tipo_usuario']}\n";
    }
    
    // Verificar se existe algum super admin
    $stmt = $db->query('SELECT * FROM usuarios WHERE tipo_usuario = 1 AND ativo = 1 LIMIT 1');
    $superAdmin = $stmt->fetch();
    
    if (!$superAdmin) {
        echo "\nCriando usuário Super Admin...\n";
        
        $stmt = $db->prepare('INSERT INTO usuarios (nome, email, senha, tipo_usuario, ativo, data_criacao) VALUES (?, ?, ?, ?, ?, NOW())');
        $hashedPassword = password_hash('123456', PASSWORD_DEFAULT);
        
        if ($stmt->execute(['Admin Sistema', 'admin@sistema.com', $hashedPassword, 1, 1])) {
            echo "Super Admin criado com sucesso!\n";
        } else {
            echo "Erro ao criar Super Admin\n";
        }
    } else {
        echo "\nSuper Admin já existe: {$superAdmin['email']}\n";
    }
    
} catch (Exception $e) {
    echo "Erro: " . $e->getMessage() . "\n";
}
?>