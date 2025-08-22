<?php
require_once 'config/database.php';

// Simular dados de teste para o endpoint
$testData = [
    'usuario_alvo_id' => 1, // ID de um usuário existente
    'motivo' => 'dados_incorretos',
    'detalhes' => 'Teste de solicitação de exclusão via script'
];

// Simular sessão de usuário admin de empresa
$_SESSION = [
    'user_id' => 1,
    'tipo_usuario' => 2, // Admin de empresa
    'empresa_id' => 1
];

echo "🧪 Testando endpoint de solicitação de exclusão...\n";
echo "Dados de teste: " . json_encode($testData) . "\n\n";

try {
    $pdo = getConnection();
    
    // Verificar se o usuário alvo existe
    $stmt = $pdo->prepare("SELECT id, nome, email FROM usuarios WHERE id = ?");
    $stmt->execute([$testData['usuario_alvo_id']]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$usuario) {
        echo "❌ Usuário alvo não encontrado.\n";
        exit;
    }
    
    echo "👤 Usuário alvo encontrado: {$usuario['nome']} ({$usuario['email']})\n";
    
    // Inserir solicitação de exclusão
    $stmt = $pdo->prepare("
        INSERT INTO solicitacoes_exclusao 
        (usuario_solicitante_id, usuario_alvo_id, motivo, detalhes, empresa_id) 
        VALUES (?, ?, ?, ?, ?)
    ");
    
    $result = $stmt->execute([
        $_SESSION['user_id'],
        $testData['usuario_alvo_id'],
        $testData['motivo'],
        $testData['detalhes'],
        $_SESSION['empresa_id']
    ]);
    
    if ($result) {
        $solicitacaoId = $pdo->lastInsertId();
        echo "✅ Solicitação de exclusão criada com sucesso! ID: {$solicitacaoId}\n";
        
        // Verificar se foi inserida corretamente
        $stmt = $pdo->prepare("SELECT * FROM solicitacoes_exclusao WHERE id = ?");
        $stmt->execute([$solicitacaoId]);
        $solicitacao = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo "📋 Dados da solicitação criada:\n";
        echo "- ID: {$solicitacao['id']}\n";
        echo "- Solicitante: {$solicitacao['usuario_solicitante_id']}\n";
        echo "- Usuário alvo: {$solicitacao['usuario_alvo_id']}\n";
        echo "- Motivo: {$solicitacao['motivo']}\n";
        echo "- Status: {$solicitacao['status']}\n";
        echo "- Data: {$solicitacao['data_solicitacao']}\n";
        
    } else {
        echo "❌ Erro ao criar solicitação de exclusão.\n";
    }
    
} catch (Exception $e) {
    echo "❌ Erro: " . $e->getMessage() . "\n";
}
?>