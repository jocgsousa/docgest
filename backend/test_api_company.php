<?php

require_once __DIR__ . '/utils/JWT.php';
require_once __DIR__ . '/config/config.php';

// Gerar token JWT válido para super admin
$userData = [
    'id' => 1,
    'nome' => 'Super Admin',
    'email' => 'admin@docgest.com',
    'tipo_usuario' => 1,
    'empresa_id' => null,
    'filial_id' => null
];

$token = JWT::encode($userData);
echo "Token gerado: " . $token . "\n\n";

// Dados da empresa para teste
$companyData = [
    "nome" => "TSTES",
    "cnpj" => "79.432.023/0001-25",
    "email" => "teste@gmail.com",
    "telefone" => "94981413567",
    "endereco" => "Rua Sete de Junho",
    "cidade" => "Marabá",
    "estado" => "PA",
    "cep" => "68500300",
    "plano_id" => "1",
    "data_vencimento" => "2025-09-14"
];

// Configurar cURL
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

echo "Fazendo requisição para API...\n";
echo "URL: http://localhost:8000/api/companies\n";
echo "Dados: " . json_encode($companyData, JSON_PRETTY_PRINT) . "\n\n";

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

if ($error) {
    echo "Erro cURL: " . $error . "\n";
} else {
    echo "Código HTTP: " . $httpCode . "\n";
    echo "Resposta: " . $response . "\n";
    
    // Decodificar resposta JSON
    $responseData = json_decode($response, true);
    if ($responseData) {
        echo "\nResposta formatada:\n";
        echo json_encode($responseData, JSON_PRETTY_PRINT) . "\n";
    }
}

?>