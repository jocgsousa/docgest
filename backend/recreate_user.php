<?php
require_once __DIR__ . '/config/database.php';

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    // Inserir usuário ID 2
    $stmt = $conn->prepare('INSERT INTO usuarios (id, nome, email, senha, tipo_usuario, empresa_id, ativo, data_criacao) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())');
    $result = $stmt->execute([2, 'Admin Empresa', 'admin@exemplo.com', password_hash('123456', PASSWORD_DEFAULT), 2, 1, 1]);
    
    if ($result) {
        echo "Usuário ID 2 recriado com sucesso\n";
    } else {
        echo "Erro ao recriar usuário\n";
    }
    
    // Verificar se foi criado
    $stmt = $conn->prepare('SELECT id, nome, email, ativo FROM usuarios WHERE id = 2');
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        echo "Usuário encontrado:\n";
        echo "- ID: {$user['id']}\n";
        echo "- Nome: {$user['nome']}\n";
        echo "- Email: {$user['email']}\n";
        echo "- Ativo: {$user['ativo']}\n";
    }
    
} catch (Exception $e) {
    echo "Erro: " . $e->getMessage() . "\n";
}
?>