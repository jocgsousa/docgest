<?php

// Incluir arquivos necessários
require_once 'backend/config/config.php';
require_once 'backend/config/database.php';
require_once 'backend/models/User.php';
require_once 'backend/utils/JWT.php';
require_once 'backend/utils/Response.php';

echo "=== DEBUG DA RESPOSTA DA API /settings ===\n\n";

try {
    // 1. Verificar usuário Super Admin
    $userModel = new User();
    $existingAdmin = $userModel->findByEmail('admin@test.com');
    
    if (!$existingAdmin) {
        echo "❌ Usuário Super Admin não encontrado\n";
        exit(1);
    }
    
    echo "1. Usuário Super Admin encontrado (ID: {$existingAdmin['id']})\n";
    
    // 2. Gerar token JWT válido
    $token = JWT::generateUserToken($existingAdmin);
    echo "2. Token JWT gerado com sucesso\n";
    
    // 3. Fazer requisição para /settings
    echo "\n3. Fazendo requisição para /settings...\n";
    
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
        exit(1);
    }
    
    echo "✅ Requisição bem-sucedida\n";
    echo "\n4. Resposta RAW da API:\n";
    echo "$response\n";
    
    // 4. Decodificar JSON
    $data = json_decode($response, true);
    
    if (!$data) {
        echo "\n❌ Erro ao decodificar JSON\n";
        echo "JSON Error: " . json_last_error_msg() . "\n";
        exit(1);
    }
    
    echo "\n5. Estrutura decodificada:\n";
    echo "Tipo de \$data: " . gettype($data) . "\n";
    echo "Chaves principais: " . implode(', ', array_keys($data)) . "\n";
    
    if (isset($data['data'])) {
        echo "\nTipo de \$data['data']: " . gettype($data['data']) . "\n";
        
        if (is_array($data['data'])) {
            echo "Número de itens em \$data['data']: " . count($data['data']) . "\n";
            
            echo "\n6. Primeiros 3 itens de \$data['data']:\n";
            for ($i = 0; $i < min(3, count($data['data'])); $i++) {
                echo "\nItem $i:\n";
                echo "Tipo: " . gettype($data['data'][$i]) . "\n";
                
                if (is_array($data['data'][$i])) {
                    echo "Chaves: " . implode(', ', array_keys($data['data'][$i])) . "\n";
                    echo "Conteúdo: " . json_encode($data['data'][$i], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
                } else {
                    echo "Valor: " . $data['data'][$i] . "\n";
                }
            }
            
            // 7. Tentar converter para formato chave-valor
            echo "\n7. Tentando converter para formato chave-valor...\n";
            $settings = [];
            $sucessos = 0;
            $erros = 0;
            
            foreach ($data['data'] as $index => $setting) {
                echo "\nProcessando item $index:\n";
                echo "Tipo: " . gettype($setting) . "\n";
                
                if (is_array($setting)) {
                    echo "Chaves disponíveis: " . implode(', ', array_keys($setting)) . "\n";
                    
                    if (isset($setting['chave']) && isset($setting['valor'])) {
                        $settings[$setting['chave']] = $setting['valor'];
                        echo "✅ Convertido: {$setting['chave']} = {$setting['valor']}\n";
                        $sucessos++;
                    } else {
                        echo "❌ Campos 'chave' ou 'valor' não encontrados\n";
                        $erros++;
                    }
                } else {
                    echo "❌ Item não é um array\n";
                    $erros++;
                }
            }
            
            echo "\n=== RESULTADO DA CONVERSÃO ===\n";
            echo "Sucessos: $sucessos\n";
            echo "Erros: $erros\n";
            echo "Total de configurações convertidas: " . count($settings) . "\n";
            
            if (count($settings) > 0) {
                echo "\nConfiguração convertidas:\n";
                echo json_encode($settings, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
            }
        } else {
            echo "❌ \$data['data'] não é um array\n";
        }
    } else {
        echo "❌ Chave 'data' não encontrada na resposta\n";
    }
    
} catch (Exception $e) {
    echo "❌ Erro: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}

echo "\n=== DEBUG CONCLUÍDO ===\n";
?>