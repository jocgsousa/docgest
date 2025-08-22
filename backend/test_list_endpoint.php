<?php
require_once 'config/config.php';
require_once 'config/database.php';
require_once 'vendor/autoload.php';
require_once 'controllers/UserController.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

echo "🧪 Testando endpoint de listagem de solicitações...\n";

// Simular dados de um super admin
$userData = [
    'id' => 1,
    'nome' => 'Super Admin',
    'email' => 'admin@docgest.com',
    'tipo_usuario' => 1, // 1 = super_admin
    'empresa_id' => 1
];

// Gerar token JWT válido
$secretKey = JWT_SECRET;
$payload = [
    'user_id' => $userData['id'],
    'email' => $userData['email'],
    'tipo_usuario' => $userData['tipo_usuario'],
    'empresa_id' => $userData['empresa_id'],
    'exp' => time() + 3600
];

$jwt = JWT::encode($payload, $secretKey, 'HS256');
echo "🔑 Token JWT gerado: " . substr($jwt, 0, 50) . "...\n";

// Simular requisição HTTP
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['HTTP_AUTHORIZATION'] = 'Bearer ' . $jwt;
$_GET['page'] = 1;
$_GET['page_size'] = 10;

try {
    $controller = new UserController();
    
    // Capturar saída
    ob_start();
    $controller->listDeletionRequests();
    $output = ob_get_clean();
    
    echo "📋 Resposta do endpoint:\n";
    echo $output . "\n";
    
    // Verificar se é JSON válido
    $data = json_decode($output, true);
    if ($data) {
        echo "✅ JSON válido retornado\n";
        if (isset($data['success']) && $data['success']) {
            echo "✅ Sucesso: " . count($data['data']) . " solicitações encontradas\n";
        } else {
            echo "❌ Erro: " . ($data['message'] ?? 'Erro desconhecido') . "\n";
        }
    } else {
        echo "❌ Resposta não é JSON válido\n";
    }
    
} catch (Exception $e) {
    echo "❌ Erro na execução: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}