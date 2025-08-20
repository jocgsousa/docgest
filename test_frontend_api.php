<?php

// Testar as APIs que o frontend está chamando via HTTP real
echo "=== TESTE DAS APIs VIA HTTP REAL ===\n\n";

// Função para fazer requisições HTTP
function makeHttpRequest($url, $headers = []) {
    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'header' => implode("\r\n", $headers),
            'timeout' => 10
        ]
    ]);
    
    $response = @file_get_contents($url, false, $context);
    $httpCode = 200;
    
    if (isset($http_response_header)) {
        foreach ($http_response_header as $header) {
            if (preg_match('/HTTP\/\d\.\d\s+(\d+)/', $header, $matches)) {
                $httpCode = intval($matches[1]);
            }
        }
    }
    
    return [
        'body' => $response,
        'code' => $httpCode,
        'headers' => $http_response_header ?? []
    ];
}

try {
    $baseUrl = 'http://localhost:8000';
    
    // 1. Testar endpoint /app-info (público)
    echo "1. Testando GET /app-info (público):\n";
    echo "====================================\n";
    
    $response = makeHttpRequest($baseUrl . '/app-info');
    
    echo "Status Code: {$response['code']}\n";
    echo "Response Body: {$response['body']}\n";
    
    if ($response['code'] === 200 && $response['body']) {
        $data = json_decode($response['body'], true);
        if ($data && isset($data['data'])) {
            echo "✅ /app-info funcionando\n";
            echo "Dados recebidos:\n";
            foreach ($data['data'] as $key => $value) {
                echo "  - $key: '$value'\n";
            }
        } else {
            echo "❌ Resposta JSON inválida\n";
        }
    } else {
        echo "❌ Erro na requisição /app-info\n";
    }
    
    // 2. Testar endpoint /settings (protegido)
    echo "\n2. Testando GET /settings (protegido, sem token):\n";
    echo "================================================\n";
    
    $response = makeHttpRequest($baseUrl . '/settings');
    
    echo "Status Code: {$response['code']}\n";
    echo "Response Body: {$response['body']}\n";
    
    if ($response['code'] === 401 || $response['code'] === 403) {
        echo "✅ Endpoint protegido funcionando (retornou erro de autenticação)\n";
    } else if ($response['code'] === 200) {
        echo "⚠️ Endpoint deveria estar protegido mas retornou dados\n";
    } else {
        echo "❌ Erro inesperado no endpoint /settings\n";
    }
    
    // 3. Testar com token inválido
    echo "\n3. Testando GET /settings com token inválido:\n";
    echo "============================================\n";
    
    $headers = ['Authorization: Bearer token-invalido'];
    $response = makeHttpRequest($baseUrl . '/settings', $headers);
    
    echo "Status Code: {$response['code']}\n";
    echo "Response Body: {$response['body']}\n";
    
    if ($response['code'] === 401 || $response['code'] === 403) {
        echo "✅ Validação de token funcionando\n";
    } else {
        echo "❌ Validação de token não está funcionando\n";
    }
    
    // 4. Verificar se o servidor está respondendo corretamente
    echo "\n4. Verificando servidor backend:\n";
    echo "===============================\n";
    
    $response = makeHttpRequest($baseUrl);
    echo "Status Code para raiz: {$response['code']}\n";
    
    if ($response['code'] === 404) {
        echo "✅ Servidor backend está rodando (404 é esperado para rota raiz)\n";
    } else if ($response['code'] === 200) {
        echo "✅ Servidor backend está rodando\n";
    } else {
        echo "❌ Problema com servidor backend\n";
    }
    
    // 5. Testar rota inexistente
    echo "\n5. Testando rota inexistente:\n";
    echo "============================\n";
    
    $response = makeHttpRequest($baseUrl . '/rota-inexistente');
    echo "Status Code: {$response['code']}\n";
    
    if ($response['code'] === 404) {
        echo "✅ Tratamento de rotas inexistentes funcionando\n";
    } else {
        echo "❌ Problema no tratamento de rotas\n";
    }
    
} catch (Exception $e) {
    echo "❌ Erro geral: " . $e->getMessage() . "\n";
}

echo "\n\n=== TESTE CONCLUÍDO ===\n";
echo "\nResumo:\n";
echo "- Se /app-info está funcionando, o problema não é no servidor\n";
echo "- Se /settings está protegido, a autenticação está funcionando\n";
echo "- O frontend deve estar falhando na autenticação JWT\n";
echo "- Verifique o localStorage do navegador para ver se há token válido\n";

?>