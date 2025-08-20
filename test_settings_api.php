<?php

// Testar a API /settings de forma simples
require_once 'backend/config/config.php';
require_once 'backend/config/database.php';
require_once 'backend/controllers/SettingsController.php';
require_once 'backend/models/Settings.php';
require_once 'backend/utils/Response.php';
require_once 'backend/utils/JWT.php';

echo "=== TESTE SIMPLES DA API /settings ===\n\n";

try {
    // 1. Testar modelo Settings diretamente
    echo "1. Testando modelo Settings:\n";
    echo "============================\n";
    
    $settings = new Settings();
    $allSettings = $settings->getAll();
    
    echo "Configurações encontradas: " . count($allSettings) . "\n";
    
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
    
    // 2. Testar endpoint /app-info (público)
    echo "\n2. Testando endpoint /app-info:\n";
    echo "===============================\n";
    
    $controller = new SettingsController();
    
    ob_start();
    try {
        $controller->getAppInfo();
        $appInfoOutput = ob_get_contents();
    } catch (Exception $e) {
        $appInfoOutput = "Erro: " . $e->getMessage();
    }
    ob_end_clean();
    
    echo "Resposta do /app-info (sem headers):\n";
    // Extrair apenas o JSON da resposta
    if (preg_match('/\{.*\}/', $appInfoOutput, $matches)) {
        $jsonPart = $matches[0];
        echo $jsonPart . "\n";
        
        $appInfoData = json_decode($jsonPart, true);
        if ($appInfoData && isset($appInfoData['data'])) {
            echo "\nDados extraídos do /app-info:\n";
            foreach ($appInfoData['data'] as $key => $value) {
                echo "- $key: '$value'\n";
            }
        }
    } else {
        echo "Não foi possível extrair JSON da resposta\n";
        echo "Resposta completa: $appInfoOutput\n";
    }
    
    // 3. Simular resposta JSON para o frontend
    echo "\n3. Simulando resposta JSON para o frontend:\n";
    echo "==========================================\n";
    
    $frontendResponse = [
        'success' => true,
        'message' => 'Configurações obtidas com sucesso',
        'data' => $allSettings
    ];
    
    echo json_encode($frontendResponse, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    
    // 4. Verificar se os dados estão corretos
    echo "\n\n4. Verificação final:\n";
    echo "====================\n";
    
    if (isset($allSettings['app_name']) && $allSettings['app_name'] === 'NovoTok') {
        echo "✅ app_name está correto: '{$allSettings['app_name']}'\n";
    } else {
        echo "❌ app_name incorreto ou ausente\n";
    }
    
    if (isset($allSettings['allowed_file_types']) && $allSettings['allowed_file_types'] === 'pdf,doc,docx,jpg,jpeg,png') {
        echo "✅ allowed_file_types está correto: '{$allSettings['allowed_file_types']}'\n";
    } else {
        echo "❌ allowed_file_types incorreto ou ausente\n";
    }
    
} catch (Exception $e) {
    echo "❌ Erro geral: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}

echo "\n\n=== TESTE CONCLUÍDO ===\n";

?>