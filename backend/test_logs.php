<?php
require_once 'config/config.php';
require_once 'controllers/LogController.php';
require_once 'utils/JWT.php';
require_once 'utils/Response.php';

try {
    echo "Testando LogController...\n";
    $controller = new LogController();
    echo "LogController instanciado com sucesso!\n";
    
    // Simular autenticação
    $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJkb2NnZXN0IiwiYXVkIjoiZG9jZ2VzdCIsImlhdCI6MTczNDk2NzI4NCwiZXhwIjoxNzM0OTcwODg0LCJkYXRhIjp7ImlkIjoxLCJub21lIjoiU3VwZXIgQWRtaW4iLCJlbWFpbCI6ImFkbWluQGRvY2dlc3QuY29tIiwidGlwb191c3VhcmlvIjoxLCJlbXByZXNhX2lkIjpudWxsLCJmaWxpYWxfaWQiOm51bGx9fQ.invalid';
    
    echo "Testando método index()...\n";
    
    // Capturar output
    ob_start();
    $controller->index();
    $output = ob_get_clean();
    
    echo "Output do método index(): " . substr($output, 0, 200) . "...\n";
    
} catch (Exception $e) {
    echo "Erro: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}
?>