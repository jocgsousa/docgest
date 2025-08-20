<?php

// Habilitar exibição de erros
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/error.log');

echo "=== Debug Frontend Error 500 ===\n";

// Simular exatamente o que o frontend está enviando
$_SERVER['REQUEST_METHOD'] = 'PUT';
$_SERVER['REQUEST_URI'] = '/api/settings';
$_SERVER['HTTP_AUTHORIZATION'] = 'Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJ1c2VyX2lkIjo0LCJlbWFpbCI6InRlc3RAZG9jZ2VzdC5jb20iLCJ0aXBvX3VzdWFyaW8iOjEsImVtcHJlc2FfaWQiOjEsImZpbGlhbF9pZCI6bnVsbCwiaWF0IjoxNzU1NjUxMTI2LCJleHAiOjE3NTU3Mzc1MjZ9.p60tGgYgLx9hGF2pXlHMpiWL6ajRj8XSOqLBRNmE48k';
$_SERVER['CONTENT_TYPE'] = 'application/json';

// Dados que o frontend está enviando (baseado no erro)
$frontendData = json_encode([
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
]);

echo "Dados do frontend: " . $frontendData . "\n\n";

try {
    echo "=== Iniciando processamento ===\n";
    
    // Incluir dependências
    require_once 'backend/config/config.php';
    require_once 'backend/config/database.php';
    require_once 'backend/utils/JWT.php';
    require_once 'backend/utils/Response.php';
    require_once 'backend/controllers/SettingsController.php';
    
    echo "✓ Dependências carregadas\n";
    
    // Verificar JWT
    $jwt = new JWT();
    $token = str_replace('Bearer ', '', $_SERVER['HTTP_AUTHORIZATION']);
    $payload = $jwt->validateToken($token);
    
    echo "✓ Token validado: " . json_encode($payload) . "\n";
    
    // Processar dados diretamente (simular o que vem do frontend)
    $data = json_decode($frontendData, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Erro na decodificação JSON: ' . json_last_error_msg());
    }
    
    echo "✓ JSON decodificado: " . count($data) . " campos\n";
    
    // Simular $_POST com os dados
    $_POST = $data;
    
    // Criar um arquivo temporário para simular php://input
    $tempFile = tempnam(sys_get_temp_dir(), 'debug_input');
    file_put_contents($tempFile, $frontendData);
    
    // Substituir php://input temporariamente
    $originalInput = 'php://input';
    
    // Instanciar controller
    $controller = new SettingsController();
    
    echo "✓ Controller instanciado\n";
    
    // Simular o método update diretamente
    echo "=== Testando validação individual ===\n";
    
    // Usar reflexão para acessar métodos privados
     $reflection = new ReflectionClass($controller);
     $validateMethod = $reflection->getMethod('validateSingleSetting');
     $validateMethod->setAccessible(true);
     
     foreach ($data as $key => $value) {
         try {
             $validateMethod->invoke($controller, $key, $value);
             echo "✓ $key: válido\n";
         } catch (Exception $e) {
             echo "✗ $key: " . $e->getMessage() . "\n";
         }
     }
    
    echo "\n=== Testando modelo Settings ===\n";
    
    require_once 'backend/models/Settings.php';
    $settings = new Settings();
    
    $result = $settings->setMultiple($data);
    echo "✓ Settings->setMultiple executado: " . ($result ? 'sucesso' : 'falha') . "\n";
    
    // Limpar arquivo temporário
    unlink($tempFile);
    
} catch (Exception $e) {
    echo "✗ ERRO CAPTURADO: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
    
    // Log do erro
    error_log("Frontend Error 500: " . $e->getMessage() . " - " . $e->getTraceAsString());
}

echo "\n=== Fim do debug ===\n";