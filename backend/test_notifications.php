<?php
require_once 'config/database.php';

try {
    $database = new Database();
    $pdo = $database->getConnection();
    
    // Verificar notificações de solicitação de exclusão
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM notificacoes WHERE tipo = 'warning' AND titulo LIKE '%Solicitação de Exclusão%'");
    $stmt->execute();
    $result = $stmt->fetch();
    
    echo "🔔 Notificações de solicitação de exclusão: " . $result['total'] . PHP_EOL;
    
    // Verificar últimas notificações
    $stmt = $pdo->prepare("SELECT id, titulo, mensagem, usuario_destinatario_id, data_criacao FROM notificacoes WHERE tipo = 'warning' ORDER BY data_criacao DESC LIMIT 3");
    $stmt->execute();
    $notifications = $stmt->fetchAll();
    
    if (count($notifications) > 0) {
        echo "\n📋 Últimas notificações de warning:\n";
        foreach ($notifications as $notif) {
            echo "- ID {$notif['id']}: {$notif['titulo']} (Usuário: {$notif['usuario_destinatario_id']}) - {$notif['data_criacao']}\n";
        }
    } else {
        echo "\n❌ Nenhuma notificação de warning encontrada\n";
    }
    
    // Verificar super admins
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM usuarios WHERE tipo_usuario = 1 AND ativo = 1");
    $stmt->execute();
    $result = $stmt->fetch();
    
    echo "\n👑 Super administradores ativos: " . $result['total'] . PHP_EOL;
    
} catch (Exception $e) {
    echo "❌ Erro: " . $e->getMessage() . PHP_EOL;
}
?>