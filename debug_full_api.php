<?php

// Simular variáveis de ambiente
$_SERVER['REQUEST_METHOD'] = 'PUT';
$_SERVER['REQUEST_URI'] = '/api/settings';
$_SERVER['HTTP_AUTHORIZATION'] = 'Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJkb2NnZXN0IiwiYXVkIjoiZG9jZ2VzdCIsImlhdCI6MTczNzU1NzI0MSwiZXhwIjoxNzM3NjQzNjQxLCJkYXRhIjp7ImlkIjoxLCJub21lIjoiQWRtaW5pc3RyYWRvciIsImVtYWlsIjoiYWRtaW5AZXhhbXBsZS5jb20iLCJ0aXBvX3VzdWFyaW8iOjF9fQ.Hs6Ej8Ej8Ej8Ej8Ej8Ej8Ej8Ej8Ej8Ej8Ej8Ej8Ej8';

echo "=== Debug Full API Call ===\n";

// Dados de teste
$testData = [
    'app_name' => 'NovoTok Documentos',
    'max_file_size' => '10',
    'allowed_file_types' => 'pdf,doc,docx,jpg,jpeg,png',
    'smtp_host' => '',
    'smtp_port' => '587',
    'smtp_username' => 'gregoriociacom@gmail.com',
    'smtp_password' => '123456',
    'smtp_from_email' => '',
    'smtp_from_name' => '',
    'email_notifications' => true,
    'whatsapp_notifications' => false,
    'signature_reminders' => true,
    'expiration_alerts' => true,
    'password_min_length' => '8',
    'require_password_complexity' => true,
    'session_timeout' => '24',
    'max_login_attempts' => '5',
    'signature_expiration_days' => '30',
    'auto_reminder_days' => '7',
    'max_signers_per_document' => '10'
];

echo "1. Simulando entrada de dados...\n";
$jsonData = json_encode($testData);
echo "JSON Data: " . $jsonData . "\n\n";

// Simular php://input
file_put_contents('php://temp/maxmemory:1048576', $jsonData);

echo "2. Testando JWT...\n";
try {
    require_once 'backend/utils/JWT.php';
    
    // Simular token válido (você pode usar um token real aqui)
    $mockUser = [
        'id' => 1,
        'nome' => 'Administrador',
        'email' => 'admin@example.com',
        'tipo_usuario' => 1
    ];
    
    echo "Token simulado para usuário: " . $mockUser['nome'] . " (tipo: " . $mockUser['tipo_usuario'] . ")\n";
    
} catch (Exception $e) {
    echo "ERRO JWT: " . $e->getMessage() . "\n";
}

echo "\n3. Testando SettingsController->update()...\n";
try {
    // Capturar saída
    ob_start();
    
    require_once 'backend/controllers/SettingsController.php';
    
    // Mock da validação JWT
    class MockJWT {
        public static function validateToken() {
            return [
                'id' => 1,
                'nome' => 'Administrador',
                'email' => 'admin@example.com',
                'tipo_usuario' => 1
            ];
        }
    }
    
    // Substituir JWT temporariamente
    if (!class_exists('JWT')) {
        class JWT extends MockJWT {}
    }
    
    // Simular php://input
    $tempFile = tempnam(sys_get_temp_dir(), 'php_input');
    file_put_contents($tempFile, $jsonData);
    
    // Redirecionar php://input
    $originalInput = 'php://input';
    
    // Criar controller e testar
    $controller = new SettingsController();
    
    // Usar reflexão para testar o método update
    $reflection = new ReflectionClass($controller);
    $updateMethod = $reflection->getMethod('update');
    $updateMethod->setAccessible(true);
    
    // Simular file_get_contents('php://input')
    $originalFileGetContents = 'file_get_contents';
    
    // Executar método update
    echo "Executando update()...\n";
    
    // Capturar qualquer saída
    $output = ob_get_contents();
    ob_end_clean();
    
    echo "Saída capturada: " . $output . "\n";
    
    // Tentar executar diretamente
    echo "\n4. Teste direto do modelo...\n";
    require_once 'backend/models/Settings.php';
    
    $settings = new Settings();
    $result = $settings->setMultiple($testData);
    
    echo "Resultado setMultiple: " . ($result ? 'SUCESSO' : 'FALHA') . "\n";
    
    if ($result) {
        echo "✓ Configurações atualizadas com sucesso no modelo\n";
        
        // Verificar se foram realmente salvas
        $updated = $settings->getAll();
        echo "app_name salvo: " . $updated['app_name'] . "\n";
        echo "max_file_size salvo: " . $updated['max_file_size'] . "\n";
    } else {
        echo "✗ Falha ao atualizar configurações no modelo\n";
    }
    
} catch (Exception $e) {
    ob_end_clean();
    echo "ERRO no controller: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}

echo "\n=== Fim do Debug Full API ===\n";