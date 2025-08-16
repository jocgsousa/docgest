<?php

require_once __DIR__ . '/utils/JWT.php';
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/controllers/AuthController.php';

// Simular um token do localStorage (você pode pegar do navegador)
echo "=== TESTE DE VALIDAÇÃO DE TOKEN ===\n\n";

// 1. Gerar um token válido
$userData = [
    'user_id' => 1,
    'email' => 'admin@docgest.com',
    'tipo_usuario' => 1,
    'empresa_id' => null,
    'filial_id' => null,
    'iat' => time(),
    'exp' => time() + JWT_EXPIRATION
];

$validToken = JWT::encode($userData);
echo "1. Token válido gerado:\n";
echo $validToken . "\n\n";

// 2. Testar decodificação
try {
    $decoded = JWT::decode($validToken);
    echo "2. Token decodificado com sucesso:\n";
    echo json_encode($decoded, JSON_PRETTY_PRINT) . "\n\n";
} catch (Exception $e) {
    echo "2. Erro ao decodificar token: " . $e->getMessage() . "\n\n";
}

// 3. Testar endpoint /auth/me simulando requisição
echo "3. Testando endpoint /auth/me:\n";

// Simular headers
$_SERVER['HTTP_AUTHORIZATION'] = 'Bearer ' . $validToken;
$_SERVER['REQUEST_METHOD'] = 'GET';

try {
    $controller = new AuthController();
    
    // Capturar output
    ob_start();
    $controller->me();
    $output = ob_get_clean();
    
    echo "Resposta do /auth/me:\n";
    echo $output . "\n\n";
    
} catch (Exception $e) {
    echo "Erro no endpoint /auth/me: " . $e->getMessage() . "\n\n";
}

// 4. Testar token expirado
echo "4. Testando token expirado:\n";
$expiredUserData = [
    'user_id' => 1,
    'email' => 'admin@docgest.com',
    'tipo_usuario' => 1,
    'empresa_id' => null,
    'filial_id' => null,
    'iat' => time() - 3600,
    'exp' => time() - 1800 // Expirado há 30 minutos
];

$expiredToken = JWT::encode($expiredUserData);
echo "Token expirado: " . $expiredToken . "\n";

try {
    $decoded = JWT::decode($expiredToken);
    echo "Token expirado decodificado (não deveria acontecer)\n";
} catch (Exception $e) {
    echo "Erro esperado: " . $e->getMessage() . "\n\n";
}

// 5. Verificar configurações JWT
echo "5. Configurações JWT:\n";
echo "JWT_SECRET definido: " . (defined('JWT_SECRET') ? 'Sim' : 'Não') . "\n";
echo "JWT_EXPIRATION: " . (defined('JWT_EXPIRATION') ? JWT_EXPIRATION . ' segundos (' . (JWT_EXPIRATION/3600) . ' horas)' : 'Não definido') . "\n";

?>