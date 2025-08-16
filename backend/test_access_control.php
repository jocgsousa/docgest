<?php

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/utils/JWT.php';

echo "=== TESTE DE CONTROLE DE ACESSO ===\n\n";

// Função para fazer requisição HTTP
function makeRequest($url, $method = 'GET', $data = null, $token = null) {
    $ch = curl_init();
    
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        $token ? 'Authorization: Bearer ' . $token : ''
    ]);
    
    if ($data) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    }
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return [
        'status' => $httpCode,
        'data' => json_decode($response, true)
    ];
}

// Gerar tokens para diferentes tipos de usuários
$superAdminToken = JWT::encode([
    'id' => 1,
    'nome' => 'Super Admin',
    'email' => 'admin@docgest.com',
    'tipo_usuario' => 1,
    'empresa_id' => null,
    'filial_id' => null
]);

$adminEmpresaToken = JWT::encode([
    'id' => 2,
    'nome' => 'Admin Empresa',
    'email' => 'admin@exemplo.com',
    'tipo_usuario' => 2,
    'empresa_id' => 1,
    'filial_id' => 1
]);

$baseUrl = 'http://localhost:8001/api';

echo "1. Testando listagem de empresas com Super Admin:\n";
$response = makeRequest($baseUrl . '/companies', 'GET', null, $superAdminToken);
echo "Status: {$response['status']}\n";
if ($response['data']['success']) {
    echo "Empresas encontradas: " . count($response['data']['data']) . "\n";
} else {
    echo "Erro: " . ($response['data']['message'] ?? 'Erro desconhecido') . "\n";
}
echo "\n";

echo "2. Testando listagem de empresas com Admin de Empresa:\n";
$response = makeRequest($baseUrl . '/companies', 'GET', null, $adminEmpresaToken);
echo "Status: {$response['status']}\n";
if ($response['data']['success']) {
    echo "Empresas encontradas: " . count($response['data']['data']) . "\n";
    if (count($response['data']['data']) > 0) {
        echo "ID da empresa: " . $response['data']['data'][0]['id'] . "\n";
    }
} else {
    echo "Erro: " . ($response['data']['message'] ?? 'Erro desconhecido') . "\n";
}
echo "\n";

echo "3. Testando listagem de empresas (all) com Super Admin:\n";
$response = makeRequest($baseUrl . '/companies/all', 'GET', null, $superAdminToken);
echo "Status: {$response['status']}\n";
if ($response['data']['success']) {
    echo "Empresas encontradas: " . count($response['data']['data']) . "\n";
} else {
    echo "Erro: " . ($response['data']['message'] ?? 'Erro desconhecido') . "\n";
}
echo "\n";

echo "4. Testando listagem de empresas (all) com Admin de Empresa:\n";
$response = makeRequest($baseUrl . '/companies/all', 'GET', null, $adminEmpresaToken);
echo "Status: {$response['status']}\n";
if ($response['data']['success']) {
    echo "Empresas encontradas: " . count($response['data']['data']) . "\n";
    if (count($response['data']['data']) > 0) {
        echo "ID da empresa: " . $response['data']['data'][0]['id'] . "\n";
    }
} else {
    echo "Erro: " . ($response['data']['message'] ?? 'Erro desconhecido') . "\n";
}
echo "\n";

echo "5. Testando listagem de filiais com Super Admin:\n";
$response = makeRequest($baseUrl . '/branches', 'GET', null, $superAdminToken);
echo "Status: {$response['status']}\n";
if ($response['data']['success']) {
    echo "Filiais encontradas: " . count($response['data']['data']) . "\n";
} else {
    echo "Erro: " . ($response['data']['message'] ?? 'Erro desconhecido') . "\n";
}
echo "\n";

echo "6. Testando listagem de filiais com Admin de Empresa:\n";
$response = makeRequest($baseUrl . '/branches', 'GET', null, $adminEmpresaToken);
echo "Status: {$response['status']}\n";
if ($response['data']['success']) {
    echo "Filiais encontradas: " . count($response['data']['data']) . "\n";
    if (count($response['data']['data']) > 0) {
        echo "Empresa ID da primeira filial: " . $response['data']['data'][0]['empresa_id'] . "\n";
    }
} else {
    echo "Erro: " . ($response['data']['message'] ?? 'Erro desconhecido') . "\n";
}
echo "\n";

echo "7. Testando listagem de filiais (all) com Admin de Empresa:\n";
$response = makeRequest($baseUrl . '/branches/all', 'GET', null, $adminEmpresaToken);
echo "Status: {$response['status']}\n";
if ($response['data']['success']) {
    echo "Filiais encontradas: " . count($response['data']['data']) . "\n";
    if (count($response['data']['data']) > 0) {
        echo "Empresa ID da primeira filial: " . $response['data']['data'][0]['empresa_id'] . "\n";
    }
} else {
    echo "Erro: " . ($response['data']['message'] ?? 'Erro desconhecido') . "\n";
}
echo "\n";

echo "=== TESTE CONCLUÍDO ===\n";