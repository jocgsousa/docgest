<?php

require_once __DIR__ . '/../models/Settings.php';
require_once __DIR__ . '/../utils/Response.php';
require_once __DIR__ . '/../utils/Validator.php';
require_once __DIR__ . '/../utils/JWT.php';

class SettingsController {
    private $settingsModel;
    
    public function __construct() {
        $this->settingsModel = new Settings();
    }
    
    /**
     * Busca todas as configurações
     */
    public function index() {
        try {
            // Verificar autenticação e permissão (apenas Super Admin)
            $user = JWT::requireAdmin();
            
            $settings = $this->settingsModel->getAll();
            Response::success($settings);
            
        } catch (Exception $e) {
            Response::error('Erro ao buscar configurações: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * Busca configurações por categoria
     */
    public function getByCategory($category) {
        try {
            // Verificar autenticação
            $user = JWT::validateToken();
            
            // Verificar permissão
            if ($user['tipo_usuario'] != 1 && $user['tipo_usuario'] != 2) {
                Response::error('Acesso negado', 403);
            }
            
            $settings = $this->settingsModel->getByCategory($category);
            Response::success($settings);
            
        } catch (Exception $e) {
            Response::error('Erro ao buscar configurações: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * Busca uma configuração específica
     */
    public function show($key) {
        try {
            // Verificar autenticação
            $user = JWT::validateToken();
            
            // Verificar permissão
            if ($user['tipo_usuario'] != 1 && $user['tipo_usuario'] != 2) {
                Response::error('Acesso negado', 403);
            }
            
            $setting = $this->settingsModel->get($key);
            
            if ($setting === null) {
                Response::error('Configuração não encontrada', 404);
            }
            
            Response::success(['value' => $setting]);
            
        } catch (Exception $e) {
            Response::error('Erro ao buscar configuração: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * Atualiza configurações
     */
    public function update() {
        try {
            // Verificar autenticação
            $user = JWT::validateToken();
            
            // Verificar permissão (apenas Super Admin pode atualizar configurações)
            if ($user['tipo_usuario'] != 1) {
                Response::error('Acesso negado', 403);
            }
            
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (empty($input)) {
                Response::error('Dados não fornecidos', 400);
            }
            
            // Filtrar apenas campos válidos de configuração
            $validKeys = [
                'app_name', 'max_file_size', 'allowed_file_types', 'smtp_host', 'smtp_port',
                'smtp_username', 'smtp_password', 'smtp_from_email', 'smtp_from_name',
                'email_notifications', 'whatsapp_notifications', 'signature_reminders',
                'expiration_alerts', 'password_min_length', 'require_password_complexity',
                'session_timeout', 'max_login_attempts', 'signature_expiration_days',
                'auto_reminder_days', 'max_signers_per_document'
            ];
            
            $filteredInput = [];
            foreach ($validKeys as $key) {
                if (isset($input[$key])) {
                    $filteredInput[$key] = $input[$key];
                }
            }
            
            if (empty($filteredInput)) {
                Response::error('Nenhuma configuração válida fornecida', 400);
            }
            
            // Validar configurações específicas
            $this->validateSettings($filteredInput);
            
            // Atualizar configurações
            $success = $this->settingsModel->setMultiple($filteredInput);
            
            if (!$success) {
                Response::error('Erro ao atualizar configurações', 500);
            }
            
            Response::success(['message' => 'Configurações atualizadas com sucesso']);
            
        } catch (Exception $e) {
            Response::error('Erro ao atualizar configurações: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * Atualiza uma configuração específica
     */
    public function updateSingle($key) {
        try {
            // Verificar autenticação
            $user = JWT::validateToken();
            
            // Verificar permissão (apenas Super Admin pode atualizar configurações)
            if ($user['tipo_usuario'] != 1) {
                Response::error('Acesso negado', 403);
            }
            
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($input['value'])) {
                Response::error('Valor não fornecido', 400);
            }
            
            // Validar configuração específica
            $this->validateSingleSetting($key, $input['value']);
            
            // Atualizar configuração
            $success = $this->settingsModel->set($key, $input['value']);
            
            if (!$success) {
                Response::error('Erro ao atualizar configuração', 500);
            }
            
            Response::success(['message' => 'Configuração atualizada com sucesso']);
            
        } catch (Exception $e) {
            Response::error('Erro ao atualizar configuração: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * Cria uma nova configuração
     */
    public function store() {
        try {
            // Verificar autenticação
            $user = JWT::validateToken();
            
            // Verificar permissão (apenas Super Admin pode criar configurações)
            if ($user['tipo_usuario'] != 1) {
                Response::error('Acesso negado', 403);
            }
            
            $input = json_decode(file_get_contents('php://input'), true);
            
            // Validação
            $validator = Validator::make($input)
                ->required('chave', 'Chave é obrigatória')
                ->required('valor', 'Valor é obrigatório')
                ->required('tipo', 'Tipo é obrigatório')
                ->in('tipo', ['string', 'number', 'boolean', 'json'], 'Tipo deve ser: string, number, boolean ou json');
            
            $success = $this->settingsModel->create(
                $input['chave'],
                $input['valor'],
                $input['tipo'],
                $input['descricao'] ?? '',
                $input['categoria'] ?? 'geral'
            );
            
            if (!$success) {
                Response::error('Erro ao criar configuração', 500);
            }
            
            Response::success(['message' => 'Configuração criada com sucesso'], 201);
            
        } catch (Exception $e) {
            Response::error('Erro ao criar configuração: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * Remove uma configuração
     */
    public function delete($key) {
        try {
            // Verificar autenticação
            $user = JWT::validateToken();
            
            // Verificar permissão (apenas Super Admin pode remover configurações)
            if ($user['tipo_usuario'] != 1) {
                Response::error('Acesso negado', 403);
            }
            
            $success = $this->settingsModel->delete($key);
            
            if (!$success) {
                Response::error('Erro ao remover configuração', 500);
            }
            
            Response::success(['message' => 'Configuração removida com sucesso']);
            
        } catch (Exception $e) {
            Response::error('Erro ao remover configuração: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * Lista todas as configurações com metadados
     */
    public function getAllWithMetadata() {
        try {
            // Verificar autenticação
            $user = JWT::validateToken();
            
            // Verificar permissão (apenas Super Admin pode ver metadados)
            if ($user['tipo_usuario'] != 1) {
                Response::error('Acesso negado', 403);
            }
            
            $settings = $this->settingsModel->getAllWithMetadata();
            Response::success($settings);
            
        } catch (Exception $e) {
            Response::error('Erro ao buscar configurações: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * Restaura configurações padrão
     */
    public function restoreDefaults() {
        try {
            // Verificar autenticação
            $user = JWT::validateToken();
            
            // Verificar permissão (apenas Super Admin pode restaurar padrões)
            if ($user['tipo_usuario'] != 1) {
                Response::error('Acesso negado', 403);
            }
            
            $success = $this->settingsModel->restoreDefaults();
            
            if (!$success) {
                Response::error('Erro ao restaurar configurações padrão', 500);
            }
            
            Response::success(['message' => 'Configurações padrão restauradas com sucesso']);
            
        } catch (Exception $e) {
            Response::error('Erro ao restaurar configurações: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * Valida múltiplas configurações
     */
    private function validateSettings($settings) {
        foreach ($settings as $key => $value) {
            $this->validateSingleSetting($key, $value);
        }
    }
    
    /**
     * Valida uma configuração específica
     */
    private function validateSingleSetting($key, $value) {
        switch ($key) {
            case 'max_file_size':
                if (!is_numeric($value) || $value <= 0 || $value > 100) {
                    throw new Exception('Tamanho máximo de arquivo deve ser entre 1 e 100 MB');
                }
                break;
                
            case 'smtp_port':
                if (!is_numeric($value) || $value <= 0 || $value > 65535) {
                    throw new Exception('Porta SMTP deve ser entre 1 e 65535');
                }
                break;
                
            case 'password_min_length':
                if (!is_numeric($value) || $value < 6 || $value > 20) {
                    throw new Exception('Comprimento mínimo da senha deve ser entre 6 e 20 caracteres');
                }
                break;
                
            case 'session_timeout':
                if (!is_numeric($value) || $value < 1 || $value > 168) {
                    throw new Exception('Timeout de sessão deve ser entre 1 e 168 horas');
                }
                break;
                
            case 'max_login_attempts':
                if (!is_numeric($value) || $value < 3 || $value > 10) {
                    throw new Exception('Máximo de tentativas de login deve ser entre 3 e 10');
                }
                break;
                
            case 'signature_expiration_days':
                if (!is_numeric($value) || $value < 1 || $value > 365) {
                    throw new Exception('Dias para expiração deve ser entre 1 e 365');
                }
                break;
                
            case 'auto_reminder_days':
                if (!is_numeric($value) || $value < 1 || $value > 30) {
                    throw new Exception('Dias para lembrete deve ser entre 1 e 30');
                }
                break;
                
            case 'max_signers_per_document':
                if (!is_numeric($value) || $value < 1 || $value > 50) {
                    throw new Exception('Máximo de signatários deve ser entre 1 e 50');
                }
                break;
                
            case 'smtp_from_email':
                if (!empty($value) && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    throw new Exception('Email remetente deve ser um email válido');
                }
                break;
                
            case 'allowed_file_types':
                if (!empty($value)) {
                    $types = explode(',', $value);
                    $allowedTypes = ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png', 'gif', 'txt', 'rtf', 'odt'];
                    foreach ($types as $type) {
                        $type = trim($type);
                        if (!in_array($type, $allowedTypes)) {
                            throw new Exception("Tipo de arquivo '{$type}' não é permitido");
                        }
                    }
                }
                break;
        }
    }
    
    /**
     * Retorna informações públicas da aplicação (sem autenticação)
     */
    public function getAppInfo() {
        try {
            // Buscar apenas o nome da aplicação
            $appName = $this->settingsModel->get('app_name');
            
            // Se não encontrar, usar valor padrão
            if (!$appName) {
                $appName = 'DocGest';
            }
            
            $appInfo = [
                'app_name' => $appName,
                'version' => '1.0.0',
                'api_version' => 'v1'
            ];
            
            Response::success($appInfo, 'Informações da aplicação obtidas com sucesso');
            
        } catch (Exception $e) {
            // Em caso de erro, retornar valores padrão
            $appInfo = [
                'app_name' => 'DocGest',
                'version' => '1.0.0',
                'api_version' => 'v1'
            ];
            
            Response::success($appInfo, 'Informações padrão da aplicação');
        }
    }
    
    /**
     * Retorna configurações públicas de upload (sem autenticação)
     */
    public function getUploadConfig() {
        try {
            // Buscar configurações de upload
            $allowedFileTypes = $this->settingsModel->get('allowed_file_types');
            $maxFileSize = $this->settingsModel->get('max_file_size');
            
            // Valores padrão se não encontrar
            if (!$allowedFileTypes) {
                $allowedFileTypes = 'pdf,doc,docx,txt';
            }
            if (!$maxFileSize) {
                $maxFileSize = 10;
            }
            
            $uploadConfig = [
                'allowed_file_types' => $allowedFileTypes,
                'max_file_size' => (int)$maxFileSize,
                'max_file_size_bytes' => (int)$maxFileSize * 1024 * 1024
            ];
            
            Response::success($uploadConfig, 'Configurações de upload obtidas com sucesso');
            
        } catch (Exception $e) {
            // Em caso de erro, retornar valores padrão
            $uploadConfig = [
                'allowed_file_types' => 'pdf,doc,docx,txt',
                'max_file_size' => 10,
                'max_file_size_bytes' => 10 * 1024 * 1024
            ];
            
            Response::success($uploadConfig, 'Configurações padrão de upload');
        }
    }
}