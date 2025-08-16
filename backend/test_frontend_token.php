<?php

require_once __DIR__ . '/utils/JWT.php';
require_once __DIR__ . '/config/config.php';

echo "=== TESTE DE TOKEN DO FRONTEND ===\n\n";

// Simular uma requisição do frontend
echo "1. Simulando login para obter token:\n";

// Dados de login
$loginData = [
    "email" => "admin@docgest.com",
    "password" => "123456"
];

// Fazer requisição de login
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'http://localhost:8000/api/auth/login');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($loginData));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json'
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);

$loginResponse = curl_exec($ch);
$loginHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "Código HTTP do login: " . $loginHttpCode . "\n";
echo "Resposta do login: " . $loginResponse . "\n\n";

$loginData = json_decode($loginResponse, true);

if ($loginData && $loginData['success'] && isset($loginData['data']['token'])) {
    $token = $loginData['data']['token'];
    echo "2. Token obtido do login:\n";
    echo $token . "\n\n";
    
    // Testar /auth/me com este token
    echo "3. Testando /auth/me com token do login:\n";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'http://localhost:8000/api/auth/me');
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $token
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    $meResponse = curl_exec($ch);
    $meHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "Código HTTP do /auth/me: " . $meHttpCode . "\n";
    echo "Resposta do /auth/me: " . $meResponse . "\n\n";
    
    // Testar criação de empresa com este token
    echo "4. Testando criação de empresa com token do login:\n";
    
    $companyData = [
        "nome" => "TESTE FRONTEND",
        "cnpj" => "79.432.023/0001-25",
        "email" => "frontend@teste.com",
        "telefone" => "11987654321",
        "endereco" => "Rua Teste Frontend",
        "cidade" => "São Paulo",
        "estado" => "SP",
        "cep" => "01234567",
        "plano_id" => "1",
        "data_vencimento" => "2025-09-14"
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'http://localhost:8000/api/companies');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($companyData));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $token
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    $companyResponse = curl_exec($ch);
    $companyHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "Código HTTP da criação de empresa: " . $companyHttpCode . "\n";
    echo "Resposta da criação de empresa: " . $companyResponse . "\n\n";
    
} else {
    echo "Erro no login. Não foi possível obter o token.\n";
}

// Verificar se há algum problema com o formato da data
echo "5. Testando validação da data diretamente:\n";
$testDate = "2025-09-14";
$dateTime = DateTime::createFromFormat('Y-m-d', $testDate);
if ($dateTime && $dateTime->format('Y-m-d') === $testDate) {
    echo "Data '$testDate' é válida no formato Y-m-d\n";
} else {
    echo "Data '$testDate' é inválida no formato Y-m-d\n";
}

?>