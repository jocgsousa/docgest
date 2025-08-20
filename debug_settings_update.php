<?php

require_once 'backend/config/database.php';
require_once 'backend/models/Settings.php';

echo "=== Debug Settings Update ===\n";

// Dados de teste (similares aos enviados pelo frontend)
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

echo "Dados para atualizar:\n";
print_r($testData);
echo "\n";

try {
    $settings = new Settings();
    
    echo "1. Testando configurações existentes...\n";
    $existing = $settings->getAll();
    echo "Configurações atuais: " . count($existing) . " encontradas\n";
    
    echo "\n2. Testando atualização individual...\n";
    $result = $settings->set('app_name', 'NovoTok Documentos');
    echo "Resultado set individual: " . ($result ? 'SUCESSO' : 'FALHA') . "\n";
    
    echo "\n3. Verificando se a configuração foi atualizada...\n";
    $appName = $settings->get('app_name');
    echo "app_name atual: " . $appName . "\n";
    
    echo "\n4. Testando atualização múltipla...\n";
    $result = $settings->setMultiple($testData);
    echo "Resultado setMultiple: " . ($result ? 'SUCESSO' : 'FALHA') . "\n";
    
    if (!$result) {
        echo "ERRO: setMultiple falhou!\n";
    }
    
    echo "\n5. Verificando configurações após atualização...\n";
    $updated = $settings->getAll();
    echo "app_name após update: " . $updated['app_name'] . "\n";
    echo "max_file_size após update: " . $updated['max_file_size'] . "\n";
    
} catch (Exception $e) {
    echo "ERRO: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}

echo "\n=== Fim do Debug ===\n";