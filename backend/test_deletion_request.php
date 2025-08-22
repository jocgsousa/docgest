<?php
require_once 'config/database.php';
require_once 'controllers/UserController.php';
require_once 'utils/Response.php';
require_once 'utils/JWT.php';
require_once 'models/User.php';

try {
    $database = new Database();
    $pdo = $database->getConnection();
    
    echo "🧪 TESTE DE SOLICITAÇÃO DE EXCLUSÃO\n";
    echo "=====================================\n\n";
    
    // Verificar usuários disponíveis
    $stmt = $pdo->prepare("SELECT id, nome, email, tipo_usuario, empresa_id FROM usuarios WHERE ativo = 1 LIMIT 5");
    $stmt->execute();
    $users = $stmt->fetchAll();
    
    echo "👥 Usuários disponíveis:\n";
    foreach ($users as $user) {
        $tipo = $user['tipo_usuario'] == 1 ? 'Super Admin' : ($user['tipo_usuario'] == 2 ? 'Admin Empresa' : 'Usuário');
        echo "- ID {$user['id']}: {$user['nome']} ({$user['email']}) - {$tipo} - Empresa: {$user['empresa_id']}\n";
    }
    
    // Verificar se há pelo menos um super admin e um admin de empresa
    $superAdmin = null;
    $adminEmpresa = null;
    
    foreach ($users as $user) {
        if ($user['tipo_usuario'] == 1 && !$superAdmin) {
            $superAdmin = $user;
        }
        if ($user['tipo_usuario'] == 2 && !$adminEmpresa) {
            $adminEmpresa = $user;
        }
    }
    
    if (!$superAdmin) {
        echo "\n❌ Não foi encontrado um super administrador para o teste\n";
        exit(1);
    }
    
    if (!$adminEmpresa) {
        echo "\n❌ Não foi encontrado um administrador de empresa para o teste\n";
        exit(1);
    }
    
    echo "\n🎯 Teste: Super Admin {$superAdmin['nome']} solicitando exclusão do admin {$adminEmpresa['nome']}\n";
    
    // Simular dados da solicitação
    $requestData = [
        'usuario_alvo_id' => $adminEmpresa['id'],
        'usuario_solicitante_id' => $superAdmin['id'],
        'empresa_id' => $adminEmpresa['empresa_id'],
        'motivo' => 'dados_incorretos',
        'detalhes' => 'Admin inativo há mais de 6 meses - teste automático'
    ];
    
    // Criar a solicitação diretamente no banco
    $stmt = $pdo->prepare("
        INSERT INTO solicitacoes_exclusao 
        (usuario_alvo_id, usuario_solicitante_id, empresa_id, motivo, detalhes, status) 
        VALUES (:usuario_alvo_id, :usuario_solicitante_id, :empresa_id, :motivo, :detalhes, 'pendente')
    ");
    
    $stmt->execute($requestData);
    $requestId = $pdo->lastInsertId();
    
    echo "✅ Solicitação criada com ID: {$requestId}\n";
    
    // Simular a chamada do método notifySuperAdmins
    $userController = new UserController();
    $reflection = new ReflectionClass($userController);
    $method = $reflection->getMethod('notifySuperAdmins');
    $method->setAccessible(true);
    
    // Chamar o método privado
    $method->invoke($userController, $requestId, $requestData, $adminEmpresa);
    
    echo "✅ Método notifySuperAdmins executado\n";
    
    // Verificar se as notificações foram criadas
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM notificacoes WHERE tipo = 'warning' AND titulo LIKE '%Solicitação de Exclusão%'");
    $stmt->execute();
    $result = $stmt->fetch();
    
    echo "\n🔔 Notificações criadas: {$result['total']}\n";
    
    if ($result['total'] > 0) {
        $stmt = $pdo->prepare("SELECT id, titulo, mensagem, usuario_destinatario_id, data_criacao FROM notificacoes WHERE tipo = 'warning' AND titulo LIKE '%Solicitação de Exclusão%' ORDER BY data_criacao DESC LIMIT 3");
        $stmt->execute();
        $notifications = $stmt->fetchAll();
        
        echo "\n📋 Últimas notificações:\n";
        foreach ($notifications as $notif) {
            echo "- ID {$notif['id']}: {$notif['titulo']} (Usuário: {$notif['usuario_destinatario_id']}) - {$notif['data_criacao']}\n";
            echo "  Mensagem: {$notif['mensagem']}\n\n";
        }
    }
    
    echo "\n🎉 Teste concluído!\n";
    
} catch (Exception $e) {
    echo "❌ Erro: " . $e->getMessage() . PHP_EOL;
    echo "Stack trace: " . $e->getTraceAsString() . PHP_EOL;
}
?>