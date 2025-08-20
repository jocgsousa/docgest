<?php

// Incluir arquivos necessários
require_once 'backend/config/config.php';
require_once 'backend/config/database.php';
require_once 'backend/models/User.php';
require_once 'backend/utils/JWT.php';
require_once 'backend/utils/Response.php';

echo "=== TESTE DE CARREGAMENTO DE CONFIGURAÇÕES NO FRONTEND ===\n\n";

try {
    // 1. Verificar usuário Super Admin
    $userModel = new User();
    $existingAdmin = $userModel->findByEmail('admin@test.com');
    
    if (!$existingAdmin) {
        echo "❌ Usuário Super Admin não encontrado\n";
        echo "Execute primeiro: php create_test_user.php\n";
        exit(1);
    }
    
    echo "1. Usuário Super Admin encontrado (ID: {$existingAdmin['id']})\n";
    
    // 2. Gerar token JWT válido
    echo "\n2. Gerando token JWT...\n";
    $token = JWT::generateUserToken($existingAdmin);
    echo "✅ Token JWT gerado com sucesso\n";
    
    // 3. Testar endpoint /settings com token válido
    echo "\n3. Testando endpoint /settings...\n";
    
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
        exit(1);
    }
    
    $data = json_decode($response, true);
    
    if (!$data || !isset($data['data'])) {
        echo "❌ Resposta inválida da API\n";
        echo "Response: $response\n";
        exit(1);
    }
    
    echo "✅ Endpoint /settings funcionando\n";
    echo "Configurações recebidas: " . count($data['data']) . " itens\n";
    
    // 4. Verificar estrutura dos dados retornados
    echo "\n4. Verificando estrutura dos dados...\n";
    
    // Converter array de configurações para formato chave-valor
    $settings = [];
    foreach ($data['data'] as $setting) {
        if (isset($setting['chave']) && isset($setting['valor'])) {
            $settings[$setting['chave']] = $setting['valor'];
        }
    }
    
    echo "Dados convertidos para formato chave-valor:\n";
    echo json_encode($settings, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
    
    // 5. Verificar campos específicos que devem aparecer no frontend
    echo "\n5. Verificando campos importantes...\n";
    
    $camposImportantes = [
        'app_name' => 'Nome da Aplicação',
        'allowed_file_types' => 'Tipos de Arquivo Permitidos',
        'max_file_size' => 'Tamanho Máximo do Arquivo',
        'email_notifications' => 'Notificações por Email',
        'whatsapp_notifications' => 'Notificações por WhatsApp',
        'signature_reminders' => 'Lembretes de Assinatura',
        'expiration_alerts' => 'Alertas de Expiração',
        'password_min_length' => 'Comprimento Mínimo da Senha',
        'require_password_complexity' => 'Exigir Complexidade da Senha',
        'session_timeout' => 'Timeout da Sessão',
        'max_login_attempts' => 'Máximo de Tentativas de Login',
        'signature_expiration_days' => 'Dias para Expiração da Assinatura',
        'auto_reminder_days' => 'Dias para Lembrete Automático',
        'max_signers_per_document' => 'Máximo de Assinantes por Documento',
        'smtp_host' => 'Servidor SMTP',
        'smtp_port' => 'Porta SMTP',
        'smtp_username' => 'Usuário SMTP',
        'smtp_password' => 'Senha SMTP',
        'smtp_from_email' => 'Email de Origem',
        'smtp_from_name' => 'Nome de Origem'
    ];
    
    $camposEncontrados = 0;
    $camposNaoEncontrados = [];
    
    foreach ($camposImportantes as $campo => $descricao) {
        if (isset($settings[$campo])) {
            echo "✅ $descricao ($campo): {$settings[$campo]}\n";
            $camposEncontrados++;
        } else {
            echo "❌ $descricao ($campo): NÃO ENCONTRADO\n";
            $camposNaoEncontrados[] = $campo;
        }
    }
    
    echo "\n=== RESUMO ===\n";
    echo "Campos encontrados: $camposEncontrados/" . count($camposImportantes) . "\n";
    
    if (count($camposNaoEncontrados) > 0) {
        echo "\n❌ Campos não encontrados na base de dados:\n";
        foreach ($camposNaoEncontrados as $campo) {
            echo "  - $campo\n";
        }
        echo "\nEsses campos precisam ser adicionados à tabela 'settings'\n";
    } else {
        echo "\n✅ TODOS OS CAMPOS ESTÃO PRESENTES NA BASE DE DADOS\n";
    }
    
    // 6. Simular o que o frontend deveria receber
    echo "\n6. Simulando dados para o frontend...\n";
    echo "O frontend deveria receber os dados no formato:\n";
    echo "{\n";
    echo "  \"success\": true,\n";
    echo "  \"message\": \"Sucesso\",\n";
    echo "  \"data\": {\n";
    foreach ($settings as $key => $value) {
        $valueFormatted = is_bool($value) ? ($value ? 'true' : 'false') : 
                         (is_numeric($value) ? $value : '\"' . $value . '\"');
        echo "    \"$key\": $valueFormatted,\n";
    }
    echo "  }\n";
    echo "}\n";
    
    echo "\n=== INSTRUÇÕES PARA TESTE NO NAVEGADOR ===\n";
    echo "1. Acesse http://localhost:3000\n";
    echo "2. Faça login com: admin@test.com / 123456\n";
    echo "3. Vá para a página de Configurações\n";
    echo "4. Abra o Console do navegador (F12)\n";
    echo "5. Procure por 'Configurações carregadas da API:' no console\n";
    echo "6. Verifique se todos os campos estão preenchidos corretamente\n";
    
} catch (Exception $e) {
    echo "❌ Erro geral: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}

echo "\n=== TESTE CONCLUÍDO ===\n";
?>