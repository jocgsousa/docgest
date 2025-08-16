<?php

// Definir timezone
date_default_timezone_set('America/Sao_Paulo');

// Configurações de erro para desenvolvimento
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Headers CORS
header('Access-Control-Allow-Origin: http://localhost:3000');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Access-Control-Allow-Credentials: true');

// Responder a requisições OPTIONS (preflight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Headers de segurança
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: strict-origin-when-cross-origin');

// Incluir configurações
require_once __DIR__ . '/config/config.php';

// Função para criar diretórios necessários
function createDirectories() {
    createDirectoryIfNotExists(UPLOAD_PATH);
    createDirectoryIfNotExists(UPLOAD_PATH . 'documentos/');
    createDirectoryIfNotExists(LOG_PATH);
}

// Criar diretórios necessários
createDirectories();

// Incluir rotas
require_once __DIR__ . '/routes/api.php';

?>