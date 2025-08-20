<?php

require_once 'backend/controllers/SettingsController.php';
require_once 'backend/utils/JWT.php';

echo "=== Debug SettingsController ===\n";

// Simular dados de entrada (como vem do frontend)
$inputData = json_encode([
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

echo "Dados de entrada (JSON):\n";
echo $inputData . "\n\n";

// Simular php://input
file_put_contents('php://temp/maxmemory:1048576', $inputData);

echo "1. Testando decodificação JSON...\n";
$decoded = json_decode($inputData, true);
if ($decoded === null) {
    echo "ERRO: Falha na decodificação JSON - " . json_last_error_msg() . "\n";
} else {
    echo "SUCESSO: JSON decodificado corretamente\n";
    echo "Dados decodificados:\n";
    print_r($decoded);
}

echo "\n2. Testando validação individual...\n";
try {
    $controller = new SettingsController();
    
    // Usar reflexão para acessar método privado
    $reflection = new ReflectionClass($controller);
    $validateMethod = $reflection->getMethod('validateSingleSetting');
    $validateMethod->setAccessible(true);
    
    foreach ($decoded as $key => $value) {
        try {
            $validateMethod->invoke($controller, $key, $value);
            echo "✓ {$key}: VÁLIDO\n";
        } catch (Exception $e) {
            echo "✗ {$key}: ERRO - " . $e->getMessage() . "\n";
        }
    }
    
} catch (Exception $e) {
    echo "ERRO na validação: " . $e->getMessage() . "\n";
}

echo "\n3. Testando validação múltipla...\n";
try {
    $controller = new SettingsController();
    
    // Usar reflexão para acessar método privado
    $reflection = new ReflectionClass($controller);
    $validateAllMethod = $reflection->getMethod('validateSettings');
    $validateAllMethod->setAccessible(true);
    
    $validateAllMethod->invoke($controller, $decoded);
    echo "✓ Validação múltipla: SUCESSO\n";
    
} catch (Exception $e) {
    echo "✗ Validação múltipla: ERRO - " . $e->getMessage() . "\n";
}

echo "\n=== Fim do Debug Controller ===\n";