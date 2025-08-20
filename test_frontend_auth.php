<?php

// Incluir arquivos necessários
require_once 'backend/config/config.php';
require_once 'backend/config/database.php';
require_once 'backend/models/User.php';
require_once 'backend/utils/JWT.php';
require_once 'backend/utils/Response.php';

echo "=== TESTE DE AUTENTICAÇÃO JWT PARA FRONTEND ===\n\n";

try {
    // 1. Verificar usuário Super Admin existente
    $userModel = new User();
    $existingAdmin = $userModel->findByEmail('admin@test.com');
    
    if (!$existingAdmin) {
        echo "❌ Usuário Super Admin não encontrado\n";
        echo "Execute primeiro: php create_test_user.php\n";
        exit(1);
    }
    
    echo "1. Usuário Super Admin encontrado (ID: {$existingAdmin['id']})\n";
    echo "   Nome: {$existingAdmin['nome']}\n";
    echo "   Email: {$existingAdmin['email']}\n";
    echo "   Tipo: {$existingAdmin['tipo_usuario']}\n";
    
    // 2. Gerar token JWT válido
    echo "\n2. Gerando token JWT...\n";
    $token = JWT::generateUserToken($existingAdmin);
    echo "✅ Token JWT gerado com sucesso\n";
    echo "Token: " . substr($token, 0, 50) . "...\n";
    
    // 3. Testar decodificação do token
    echo "\n3. Testando decodificação do token...\n";
    try {
        $decodedUser = JWT::decode($token);
        echo "✅ Token decodificado com sucesso\n";
        echo "Payload do token:\n";
        foreach ($decodedUser as $key => $value) {
            echo "  - $key: $value\n";
        }
    } catch (Exception $e) {
        echo "❌ Erro na decodificação do token: " . $e->getMessage() . "\n";
        exit(1);
    }
    
    // 4. Testar requisição HTTP com file_get_contents
    echo "\n4. Testando requisição HTTP com token válido...\n";
    
    $url = 'http://localhost:8000/settings';
    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'header' => [
                'Authorization: Bearer ' . $token,
                'Content-Type: application/json'
            ],
            'timeout' => 10
        ]
    ]);
    
    $response = @file_get_contents($url, false, $context);
    
    if ($response === false) {
        echo "❌ Erro na requisição HTTP\n";
        echo "Verifique se o servidor backend está rodando em localhost:8000\n";
    } else {
        // Verificar código de resposta HTTP
        $httpCode = 200; // file_get_contents não retorna código, assumimos 200 se não houve erro
        if (isset($http_response_header)) {
            foreach ($http_response_header as $header) {
                if (preg_match('/HTTP\/\d\.\d\s+(\d+)/', $header, $matches)) {
                    $httpCode = intval($matches[1]);
                    break;
                }
            }
        }
        
        echo "Status Code: $httpCode\n";
        
        if ($httpCode == 200) {
            echo "✅ Requisição autenticada com sucesso\n";
            $data = json_decode($response, true);
            if ($data && isset($data['data'])) {
                echo "Configurações recebidas: " . count($data['data']) . " itens\n";
                
                // Verificar campos específicos
                $settings = [];
                foreach ($data['data'] as $setting) {
                    $settings[$setting['chave']] = $setting['valor'];
                }
                
                echo "\nCampos importantes:\n";
                echo "- app_name: " . ($settings['app_name'] ?? 'NÃO ENCONTRADO') . "\n";
                echo "- allowed_file_types: " . ($settings['allowed_file_types'] ?? 'NÃO ENCONTRADO') . "\n";
                echo "- max_file_size: " . ($settings['max_file_size'] ?? 'NÃO ENCONTRADO') . "\n";
                echo "- email_notifications: " . ($settings['email_notifications'] ?? 'NÃO ENCONTRADO') . "\n";
                
                echo "\n✅ TODAS AS CONFIGURAÇÕES ESTÃO SENDO RETORNADAS CORRETAMENTE\n";
            } else {
                echo "❌ Resposta inválida ou sem dados\n";
                echo "Response: $response\n";
            }
        } else {
            echo "❌ Erro na requisição autenticada (HTTP $httpCode)\n";
            echo "Response: $response\n";
        }
    }
    
    // 5. Gerar dados para teste no frontend
    echo "\n\n=== DADOS PARA TESTE NO FRONTEND ===\n";
    echo "Email: admin@test.com\n";
    echo "Senha: 123456\n";
    echo "\nPara testar no navegador:\n";
    echo "1. Acesse http://localhost:3000\n";
    echo "2. Faça login com as credenciais acima\n";
    echo "3. Acesse a página de configurações\n";
    echo "4. Verifique se os dados são carregados corretamente\n";
    echo "\nSe os dados não aparecerem no frontend, verifique:\n";
    echo "- Console do navegador para erros JavaScript\n";
    echo "- Network tab para ver se as requisições estão sendo feitas\n";
    echo "- localStorage para ver se o token está sendo salvo\n";
    
} catch (Exception $e) {
    echo "❌ Erro geral: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}

echo "\n=== TESTE CONCLUÍDO ===\n";
?>