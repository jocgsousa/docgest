<?php

echo "=== Debug HTTP Request ===\n";

// Simular exatamente o que acontece na requisição real
$_SERVER['REQUEST_METHOD'] = 'PUT';
$_SERVER['REQUEST_URI'] = '/api/settings';
$_SERVER['HTTP_AUTHORIZATION'] = 'Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJkb2NnZXN0IiwiYXVkIjoiZG9jZ2VzdCIsImlhdCI6MTczNzU1NzI0MSwiZXhwIjoxNzM3NjQzNjQxLCJkYXRhIjp7ImlkIjoxLCJub21lIjoiQWRtaW5pc3RyYWRvciIsImVtYWlsIjoiYWRtaW5AZXhhbXBsZS5jb20iLCJ0aXBvX3VzdWFyaW8iOjF9fQ.Hs6Ej8Ej8Ej8Ej8Ej8Ej8Ej8Ej8Ej8Ej8Ej8Ej8Ej8';
$_SERVER['CONTENT_TYPE'] = 'application/json';

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

$jsonData = json_encode($testData);

echo "1. Dados da requisição:\n";
echo "Method: " . $_SERVER['REQUEST_METHOD'] . "\n";
echo "URI: " . $_SERVER['REQUEST_URI'] . "\n";
echo "Content-Type: " . $_SERVER['CONTENT_TYPE'] . "\n";
echo "Data: " . $jsonData . "\n\n";

// Simular php://input
$tempFile = tempnam(sys_get_temp_dir(), 'php_input_debug');
file_put_contents($tempFile, $jsonData);

echo "2. Simulando processamento da API...\n";

try {
    // Capturar toda a saída
    ob_start();
    
    // Definir função personalizada para file_get_contents
    function custom_file_get_contents($filename) {
        global $jsonData;
        if ($filename === 'php://input') {
            return $jsonData;
        }
        return file_get_contents($filename);
    }
    
    // Incluir dependências
    require_once 'backend/controllers/SettingsController.php';
    require_once 'backend/utils/JWT.php';
    require_once 'backend/utils/Response.php';
    
    echo "Dependências carregadas...\n";
    
    // Criar controller
    $controller = new SettingsController();
    echo "Controller criado...\n";
    
    // Simular JWT válido
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
    
    // Substituir file_get_contents temporariamente
    $originalInput = file_get_contents('php://input');
    
    // Tentar executar o método update
    echo "Executando update()...\n";
    
    // Usar reflexão para acessar o método
    $reflection = new ReflectionClass($controller);
    $method = $reflection->getMethod('update');
    
    // Capturar erros
    set_error_handler(function($severity, $message, $file, $line) {
        echo "ERRO PHP: $message em $file:$line\n";
    });
    
    // Executar método
    try {
        // Simular entrada JSON
        $input = json_decode($jsonData, true);
        echo "JSON decodificado: " . print_r($input, true) . "\n";
        
        // Testar validação
        $validateMethod = $reflection->getMethod('validateSettings');
        $validateMethod->setAccessible(true);
        $validateMethod->invoke($controller, $input);
        echo "Validação passou...\n";
        
        // Testar modelo
        require_once 'backend/models/Settings.php';
        $settings = new Settings();
        $result = $settings->setMultiple($input);
        echo "setMultiple resultado: " . ($result ? 'SUCESSO' : 'FALHA') . "\n";
        
        if ($result) {
            echo "✓ Configurações atualizadas com sucesso!\n";
        } else {
            echo "✗ Falha ao atualizar configurações!\n";
        }
        
    } catch (Exception $e) {
        echo "EXCEÇÃO no método: " . $e->getMessage() . "\n";
        echo "Stack trace: " . $e->getTraceAsString() . "\n";
    }
    
    restore_error_handler();
    
    $output = ob_get_contents();
    ob_end_clean();
    
    echo "Saída capturada:\n" . $output . "\n";
    
} catch (Exception $e) {
    ob_end_clean();
    echo "ERRO GERAL: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}

echo "\n=== Fim do Debug HTTP Request ===\n";