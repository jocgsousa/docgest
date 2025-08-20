<?php

require_once 'backend/config/database.php';

echo "=== Verificando usuários no banco ===\n";

try {
    $pdo = getConnection();
    
    $stmt = $pdo->query('SELECT id, nome, email, tipo_usuario FROM usuarios LIMIT 5');
    
    echo "Usuários encontrados:\n";
    while($row = $stmt->fetch()) {
        echo "ID: {$row['id']}, Nome: {$row['nome']}, Email: {$row['email']}, Tipo: {$row['tipo_usuario']}\n";
    }
    
} catch (Exception $e) {
    echo "Erro: " . $e->getMessage() . "\n";
}

echo "\n=== Fim ===\n";