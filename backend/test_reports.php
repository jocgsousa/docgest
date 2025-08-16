<?php
require_once 'config/config.php';
require_once 'controllers/ReportController.php';
require_once 'utils/JWT.php';
require_once 'utils/Response.php';

try {
    echo "Testando instanciação do ReportController...\n";
    $controller = new ReportController();
    echo "ReportController instanciado com sucesso!\n";
    
    // Simular autenticação
    $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJkb2NnZXN0IiwiYXVkIjoiZG9jZ2VzdCIsImlhdCI6MTczNDk2NzI4NCwiZXhwIjoxNzM0OTcwODg0LCJkYXRhIjp7ImlkIjoxLCJub21lIjoiU3VwZXIgQWRtaW4iLCJlbWFpbCI6ImFkbWluQGRvY2dlc3QuY29tIiwidGlwb191c3VhcmlvIjoxLCJlbXByZXNhX2lkIjpudWxsLCJmaWxpYWxfaWQiOm51bGx9fQ.invalid';
    
    echo "Testando método users()...\n";
    
    // Capturar output
    ob_start();
    $controller->users();
    $output = ob_get_clean();
    
    echo "Output do método users(): " . substr($output, 0, 200) . "...\n";
    
} catch (Exception $e) {
    echo "Erro: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}
?>