<?php

// Script para testar especificamente o método update do SettingsController

// Simular dados do frontend
$frontendData = '{"app_name":"NovoTok Documentos","max_file_size":"10","allowed_file_types":"pdf,doc,docx,jpg,jpeg,png","smtp_host":"","smtp_port":"587","smtp_username":"gregoriociacom@gmail.com","smtp_password":"123456","smtp_from_email":"","smtp_from_name":"","email_notifications":true,"whatsapp_notifications":false,"signature_reminders":true,"expiration_alerts":true,"password_min_length":"8","require_password_complexity":true,"session_timeout":"24","max_login_attempts":"5","signature_expiration_days":"30","auto_reminder_days":"7","max_signers_per_document":"10"}';

// Token JWT válido obtido via PowerShell
$token = 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJ1c2VyX2lkIjo0LCJlbWFpbCI6InRlc3RAZG9jZ2VzdC5jb20iLCJ0aXBvX3VzdWFyaW8iOjEsImVtcHJlc2FfaWQiOjEsImZpbGlhbF9pZCI6bnVsbCwiaWF0IjoxNzU1NjUxNDY1LCJleHAiOjE3NTU3Mzc4NjV9.twSboVVodToe7Ab-PwUQZVjBJO1HWULWFBCJBxt05h0';
echo "✓ Token válido configurado\n\n";

// Configurar ambiente
$_SERVER['HTTP_AUTHORIZATION'] = 'Bearer ' . $token;
$_SERVER['REQUEST_METHOD'] = 'PUT';
$_SERVER['CONTENT_TYPE'] = 'application/json';

// Habilitar exibição de erros
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Habilitar log de erros
ini_set('log_errors', 1);
ini_set('error_log', 'debug_error.log');

echo "Dados do frontend: " . $frontendData . "\n\n";

// Criar arquivo temporário para simular php://input
$tempFile = tempnam(sys_get_temp_dir(), 'debug_input');
file_put_contents($tempFile, $frontendData);

// Substituir php://input usando stream wrapper
stream_wrapper_unregister('php');
stream_wrapper_register('php', 'DebugStreamWrapper');

class DebugStreamWrapper {
    private $position;
    private $data;
    
    public function stream_open($path, $mode, $options, &$opened_path) {
        if ($path === 'php://input') {
            global $frontendData;
            $this->data = $frontendData;
            $this->position = 0;
            return true;
        }
        return false;
    }
    
    public function stream_read($count) {
        $ret = substr($this->data, $this->position, $count);
        $this->position += strlen($ret);
        return $ret;
    }
    
    public function stream_eof() {
        return $this->position >= strlen($this->data);
    }
    
    public function stream_tell() {
        return $this->position;
    }
    
    public function stream_seek($offset, $whence) {
        switch ($whence) {
            case SEEK_SET:
                $this->position = $offset;
                break;
            case SEEK_CUR:
                $this->position += $offset;
                break;
            case SEEK_END:
                $this->position = strlen($this->data) + $offset;
                break;
        }
        return true;
    }
    
    public function stream_stat() {
        return array();
    }
}

try {
    echo "=== Testando método update completo ===\n";
    
    // Incluir dependências
    require_once 'backend/config/config.php';
    require_once 'backend/config/database.php';
    require_once 'backend/utils/JWT.php';
    require_once 'backend/utils/Response.php';
    require_once 'backend/controllers/SettingsController.php';
    
    echo "✓ Dependências carregadas\n";
    
    // Instanciar controller
    $controller = new SettingsController();
    
    echo "✓ Controller instanciado\n";
    
    // Capturar output do método update
    ob_start();
    
    try {
        $controller->update();
        $output = ob_get_contents();
        echo "✓ Método update executado com sucesso\n";
        echo "Output: " . $output . "\n";
    } catch (Exception $e) {
        $output = ob_get_contents();
        echo "✗ Erro no método update: " . $e->getMessage() . "\n";
        echo "Output capturado: " . $output . "\n";
        echo "Stack trace: " . $e->getTraceAsString() . "\n";
    }
    
    ob_end_clean();
    
} catch (Exception $e) {
    echo "✗ ERRO GERAL: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}

// Limpar
if (isset($tempFile) && file_exists($tempFile)) {
    unlink($tempFile);
}

echo "\n=== Fim do debug ===\n";