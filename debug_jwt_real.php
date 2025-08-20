<?php

echo "=== Debug JWT Real ===\n";

// Primeiro, vamos fazer login para obter um token válido
echo "1. Fazendo login para obter token válido...\n";

$loginData = [
    'email' => 'admin@example.com',
    'password' => 'admin123'
];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'http://localhost:8000/api/auth/login');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($loginData));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json'
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$loginResponse = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "Status do login: $httpCode\n";
echo "Resposta do login: $loginResponse\n\n";

if ($httpCode !== 200) {
    echo "ERRO: Não foi possível fazer login\n";
    exit(1);
}

$loginData = json_decode($loginResponse, true);
if (!isset($loginData['data']['token'])) {
    echo "ERRO: Token não encontrado na resposta do login\n";
    exit(1);
}

$token = $loginData['data']['token'];
echo "Token obtido: $token\n\n";

// Agora vamos testar a atualização das configurações com o token real
echo "2. Testando atualização de configurações com token real...\n";

$settingsData = [
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

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'http://localhost:8000/api/settings');
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($settingsData));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Authorization: Bearer ' . $token
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_VERBOSE, true);
curl_setopt($ch, CURLOPT_STDERR, fopen('php://temp', 'w+'));

$updateResponse = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

echo "Status da atualização: $httpCode\n";
echo "Resposta da atualização: $updateResponse\n";

if ($curlError) {
    echo "Erro cURL: $curlError\n";
}

if ($httpCode === 500) {
    echo "\n3. ERRO 500 detectado! Vamos investigar...\n";
    
    // Vamos testar o JWT diretamente
    echo "Testando validação JWT diretamente...\n";
    
    try {
        require_once 'backend/utils/JWT.php';
        require_once 'backend/config/config.php';
        
        // Simular o header Authorization
        $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer ' . $token;
        
        $user = JWT::validateToken();
        echo "JWT válido! Usuário: " . print_r($user, true) . "\n";
        
        // Verificar se é Super Admin
        if (isset($user['tipo_usuario']) && $user['tipo_usuario'] == 1) {
            echo "✓ Usuário é Super Admin\n";
        } else {
            echo "✗ Usuário NÃO é Super Admin (tipo: " . ($user['tipo_usuario'] ?? 'não definido') . ")\n";
        }
        
    } catch (Exception $e) {
        echo "ERRO na validação JWT: " . $e->getMessage() . "\n";
    }
    
    // Testar o modelo diretamente
    echo "\nTestando modelo Settings diretamente...\n";
    try {
        require_once 'backend/models/Settings.php';
        
        $settings = new Settings();
        $result = $settings->setMultiple($settingsData);
        
        echo "Resultado setMultiple: " . ($result ? 'SUCESSO' : 'FALHA') . "\n";
        
        if ($result) {
            echo "✓ Modelo funcionando corretamente\n";
        } else {
            echo "✗ Problema no modelo\n";
        }
        
    } catch (Exception $e) {
        echo "ERRO no modelo: " . $e->getMessage() . "\n";
    }
}

echo "\n=== Fim do Debug JWT Real ===\n";