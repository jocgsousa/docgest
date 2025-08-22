<?php
require_once 'config/config.php';
require_once 'config/database.php';

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    echo "Testando consulta direta das solicitações...\n";
    
    $query = "SELECT s.*, 
                     u_target.nome as target_name, 
                     u_requester.nome as requester_name,
                     c.nome as company_name
              FROM solicitacoes_exclusao s
              LEFT JOIN usuarios u_target ON s.usuario_alvo_id = u_target.id
              LEFT JOIN usuarios u_requester ON s.usuario_solicitante_id = u_requester.id
              LEFT JOIN empresas c ON s.empresa_id = c.id
              ORDER BY s.data_solicitacao DESC";
    
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $requests = $stmt->fetchAll();
    
    echo "Total de solicitações encontradas: " . count($requests) . "\n";
    
    foreach ($requests as $request) {
        echo "ID: {$request['id']} | ";
        echo "Alvo: {$request['target_name']} | ";
        echo "Solicitante: {$request['requester_name']} | ";
        echo "Empresa: {$request['company_name']} | ";
        echo "Status: {$request['status']} | ";
        echo "Motivo: {$request['motivo']}\n";
    }
    
    // Testar formato JSON
    $response = [
        'success' => true,
        'data' => $requests,
        'total' => count($requests),
        'page' => 1,
        'page_size' => count($requests)
    ];
    
    echo "\nResposta JSON:\n";
    echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
    
} catch (Exception $e) {
    echo "Erro: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}
?>