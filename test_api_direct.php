<?php

// Simular uma requisição direta para a API /settings
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['REQUEST_URI'] = '/settings';
$_SERVER['HTTP_AUTHORIZATION'] = 'Bearer fake-token-for-test';

echo "=== TESTE DIRETO DA API /settings ===\n\n";

try {
    // Incluir as dependências
    require_once 'backend/controllers/SettingsController.php';
    require_once 'backend/models/Settings.php';
    require_once 'backend/utils/Response.php';
    require_once 'backend/utils/JWT.php';
    
    echo "1. Testando modelo Settings diretamente:\n";
    echo "========================================\n";
    
    $settings = new Settings();
    $allSettings = $settings->getAll();
    
    echo "Configurações encontradas: " . count($allSettings) . "\n";
    foreach ($allSettings as $key => $value) {
        $displayValue = is_bool($value) ? ($value ? 'true' : 'false') : $value;
        echo "- $key = '$displayValue'\n";
    }
    
    echo "\n2. Testando endpoint /app-info:\n";
    echo "===============================\n";
    
    $controller = new SettingsController();
    
    // Capturar a saída do método getAppInfo
    ob_start();
    try {
        $controller->getAppInfo();
        $appInfoOutput = ob_get_contents();
    } catch (Exception $e) {
        $appInfoOutput = "Erro: " . $e->getMessage();
    }
    ob_end_clean();
    
    echo "Resposta do /app-info:\n";
    echo $appInfoOutput . "\n";
    
    echo "\n3. Verificando campos críticos:\n";
    echo "===============================\n";
    
    $criticalFields = [
        'app_name' => 'Nome da aplicação',
        'allowed_file_types' => 'Tipos de arquivo permitidos',
        'max_file_size' => 'Tamanho máximo de arquivo',
        'email_notifications' => 'Notificações por email'
    ];
    
    foreach ($criticalFields as $field => $description) {
        if (isset($allSettings[$field])) {
            $value = $allSettings[$field];
            $displayValue = is_bool($value) ? ($value ? 'true' : 'false') : $value;
            echo "✅ $description ($field): '$displayValue'\n";
        } else {
            echo "❌ $description ($field): CAMPO NÃO ENCONTRADO\n";
        }
    }
    
    echo "\n4. Simulando resposta JSON para o frontend:\n";
    echo "==========================================\n";
    
    // Simular o que seria enviado para o frontend
    $frontendResponse = [];
    foreach ($allSettings as $key => $value) {
        $frontendResponse[$key] = $value;
    }
    
    echo json_encode($frontendResponse, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    echo "Erro geral: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}

echo "\n\n=== TESTE CONCLUÍDO ===\n";

?>