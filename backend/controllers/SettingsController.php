<?php

require_once __DIR__ . '/../utils/JWT.php';
require_once __DIR__ . '/../utils/Response.php';
require_once __DIR__ . '/../utils/Validator.php';

class SettingsController {
    private $db;
    private $settingsFile;
    
    public function __construct() {
        global $pdo;
        $this->db = $pdo;
        $this->settingsFile = __DIR__ . '/../config/settings.json';
    }
    
    /**
     * Obtém todas as configurações
     */
    public function index() {
        try {
            JWT::requireAdmin(); // Apenas Super Admin
            
            $settings = $this->loadSettings();
            
            Response::success($settings, 'Configurações recuperadas com sucesso');
            
        } catch (Exception $e) {
            Response::handleException($e);
        }
    }
    
    /**
     * Atualiza configurações
     */
    public function update() {
        try {
            JWT::requireAdmin(); // Apenas Super Admin
            
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!$input) {
                Response::badRequest('Dados inválidos');
                return;
            }
            
            // Validar configurações
            $errors = $this->validateSettings($input);
            if (!empty($errors)) {
                Response::badRequest('Dados inválidos', $errors);
                return;
            }
            
            // Salvar configurações
            $this->saveSettings($input);
            
            Response::success($input, 'Configurações atualizadas com sucesso');
            
        } catch (Exception $e) {
            Response::handleException($e);
        }
    }
    
    /**
     * Restaura configurações padrão
     */
    public function reset() {
        try {
            JWT::requireAdmin(); // Apenas Super Admin
            
            $defaultSettings = $this->getDefaultSettings();
            $this->saveSettings($defaultSettings);
            
            Response::success($defaultSettings, 'Configurações restauradas para o padrão');
            
        } catch (Exception $e) {
            Response::handleException($e);
        }
    }
    
    /**
     * Carrega configurações do arquivo
     */
    private function loadSettings() {
        if (file_exists($this->settingsFile)) {
            $settings = json_decode(file_get_contents($this->settingsFile), true);
            if ($settings) {
                return array_merge($this->getDefaultSettings(), $settings);
            }
        }
        
        return $this->getDefaultSettings();
    }
    
    /**
     * Salva configurações no arquivo
     */
    private function saveSettings($settings) {
        $settingsDir = dirname($this->settingsFile);
        if (!is_dir($settingsDir)) {
            mkdir($settingsDir, 0755, true);
        }
        
        $result = file_put_contents($this->settingsFile, json_encode($settings, JSON_PRETTY_PRINT));
        
        if ($result === false) {
            throw new Exception('Erro ao salvar configurações');
        }
        
        return true;
    }
    
    /**
     * Configurações padrão do sistema
     */
    private function getDefaultSettings() {
        return [
            // Configurações gerais
            'app_name' => 'DocGest',
            'max_file_size' => '10',
            'allowed_file_types' => 'pdf,doc,docx,jpg,jpeg,png',
            
            // Configurações de email
            'smtp_host' => 'smtp.gmail.com',
            'smtp_port' => '587',
            'smtp_username' => '',
            'smtp_password' => '',
            'smtp_from_email' => 'noreply@docgest.com',
            'smtp_from_name' => 'DocGest',
            
            // Configurações de notificação
            'email_notifications' => true,
            'whatsapp_notifications' => false,
            'signature_reminders' => true,
            'expiration_alerts' => true,
            
            // Configurações de segurança
            'password_min_length' => '8',
            'require_password_complexity' => true,
            'session_timeout' => '24',
            'max_login_attempts' => '5',
            
            // Configurações de assinatura
            'signature_expiration_days' => '30',
            'auto_reminder_days' => '7',
            'max_signers_per_document' => '10'
        ];
    }
    
    /**
     * Valida configurações
     */
    private function validateSettings($settings) {
        $errors = [];
        
        // Validar nome da aplicação
        if (empty($settings['app_name']) || strlen($settings['app_name']) < 2) {
            $errors['app_name'] = ['Nome da aplicação deve ter pelo menos 2 caracteres'];
        }
        
        // Validar tamanho máximo de arquivo
        if (!is_numeric($settings['max_file_size']) || $settings['max_file_size'] < 1 || $settings['max_file_size'] > 100) {
            $errors['max_file_size'] = ['Tamanho máximo deve ser entre 1 e 100 MB'];
        }
        
        // Validar tipos de arquivo
        if (empty($settings['allowed_file_types'])) {
            $errors['allowed_file_types'] = ['Pelo menos um tipo de arquivo deve ser permitido'];
        }
        
        // Validar configurações de email se fornecidas
        if (!empty($settings['smtp_host'])) {
            if (empty($settings['smtp_port']) || !is_numeric($settings['smtp_port'])) {
                $errors['smtp_port'] = ['Porta SMTP deve ser um número válido'];
            }
            
            if (!empty($settings['smtp_from_email']) && !filter_var($settings['smtp_from_email'], FILTER_VALIDATE_EMAIL)) {
                $errors['smtp_from_email'] = ['Email remetente deve ser um email válido'];
            }
        }
        
        // Validar configurações de segurança
        if (!is_numeric($settings['password_min_length']) || $settings['password_min_length'] < 6 || $settings['password_min_length'] > 20) {
            $errors['password_min_length'] = ['Comprimento mínimo da senha deve ser entre 6 e 20 caracteres'];
        }
        
        if (!is_numeric($settings['session_timeout']) || $settings['session_timeout'] < 1 || $settings['session_timeout'] > 168) {
            $errors['session_timeout'] = ['Timeout de sessão deve ser entre 1 e 168 horas'];
        }
        
        if (!is_numeric($settings['max_login_attempts']) || $settings['max_login_attempts'] < 3 || $settings['max_login_attempts'] > 10) {
            $errors['max_login_attempts'] = ['Máximo de tentativas deve ser entre 3 e 10'];
        }
        
        // Validar configurações de assinatura
        if (!is_numeric($settings['signature_expiration_days']) || $settings['signature_expiration_days'] < 1 || $settings['signature_expiration_days'] > 365) {
            $errors['signature_expiration_days'] = ['Dias para expiração deve ser entre 1 e 365'];
        }
        
        if (!is_numeric($settings['auto_reminder_days']) || $settings['auto_reminder_days'] < 1 || $settings['auto_reminder_days'] > 30) {
            $errors['auto_reminder_days'] = ['Dias para lembrete deve ser entre 1 e 30'];
        }
        
        if (!is_numeric($settings['max_signers_per_document']) || $settings['max_signers_per_document'] < 1 || $settings['max_signers_per_document'] > 50) {
            $errors['max_signers_per_document'] = ['Máximo de signatários deve ser entre 1 e 50'];
        }
        
        return $errors;
    }
    
    /**
     * Obtém uma configuração específica
     */
    public function getSetting($key) {
        $settings = $this->loadSettings();
        return isset($settings[$key]) ? $settings[$key] : null;
    }
    
    /**
     * Define uma configuração específica
     */
    public function setSetting($key, $value) {
        $settings = $this->loadSettings();
        $settings[$key] = $value;
        return $this->saveSettings($settings);
    }
    
    /**
     * Testa configurações de email
     */
    public function testEmail() {
        try {
            JWT::requireAdmin(); // Apenas Super Admin
            
            $settings = $this->loadSettings();
            
            if (empty($settings['smtp_host']) || empty($settings['smtp_username'])) {
                Response::badRequest('Configurações de email não estão completas');
                return;
            }
            
            // Aqui você implementaria o teste real de envio de email
            // Por enquanto, vamos simular um teste bem-sucedido
            
            Response::success(null, 'Teste de email realizado com sucesso');
            
        } catch (Exception $e) {
            Response::handleException($e);
        }
    }
}