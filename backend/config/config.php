<?php

// Configurações gerais da aplicação
define('APP_NAME', 'DocGest');
define('APP_VERSION', '1.0.0');
define('APP_ENV', 'development'); // development, production

// Configurações de CORS
define('CORS_ALLOWED_ORIGINS', [
    '*',
    'http://localhost:3000',
    'http://127.0.0.1:3000'
]);

// Configurações JWT
define('JWT_SECRET', 'docgest_jwt_secret_key_2024');
define('JWT_ALGORITHM', 'HS256');
define('JWT_EXPIRATION', 86400); // 24 horas em segundos

// Configurações de upload
define('UPLOAD_PATH', __DIR__ . '/../uploads/');
define('MAX_FILE_SIZE', 10 * 1024 * 1024); // 10MB
define('ALLOWED_FILE_TYPES', ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png']);

// Configurações de paginação
define('DEFAULT_PAGE_SIZE', 20);
define('MAX_PAGE_SIZE', 100);

// Configurações de log
define('LOG_PATH', __DIR__ . '/../logs/');
define('LOG_LEVEL', 'INFO'); // DEBUG, INFO, WARNING, ERROR

// Configurações de email
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', '');
define('SMTP_PASSWORD', '');
define('SMTP_FROM_EMAIL', 'noreply@docgest.com');
define('SMTP_FROM_NAME', 'DocGest');

// Configurações WhatsApp
define('WHATSAPP_API_URL', 'https://api.whatsapp.com/send');
define('WHATSAPP_TOKEN', '');

// Timezone
date_default_timezone_set('America/Sao_Paulo');

// Configurações de erro
if (APP_ENV === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// Headers CORS
function setCorsHeaders() {
    $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
    
    if (in_array('*', CORS_ALLOWED_ORIGINS)) {
        header('Access-Control-Allow-Origin: *');
    } elseif (in_array($origin, CORS_ALLOWED_ORIGINS)) {
        header("Access-Control-Allow-Origin: $origin");
    }
    
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
    header('Access-Control-Allow-Credentials: true');
    header('Content-Type: application/json; charset=UTF-8');
    
    // Handle preflight requests
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(200);
        exit();
    }
}

// Função para criar diretórios se não existirem
function createDirectoryIfNotExists($path) {
    if (!is_dir($path)) {
        mkdir($path, 0755, true);
    }
}

// Criar diretórios necessários
createDirectoryIfNotExists(UPLOAD_PATH);
createDirectoryIfNotExists(LOG_PATH);

?>