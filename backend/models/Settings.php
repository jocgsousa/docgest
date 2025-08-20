<?php

require_once __DIR__ . '/../config/database.php';

class Settings {
    private $conn;
    private $table = 'configuracoes';
    
    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }
    
    /**
     * Busca todas as configurações
     */
    public function getAll() {
        $sql = "SELECT * FROM {$this->table} WHERE ativo = 1 ORDER BY categoria, chave";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        
        $settings = [];
        while ($row = $stmt->fetch()) {
            $value = $this->convertValue($row['valor'], $row['tipo']);
            $settings[$row['chave']] = $value;
        }
        
        return $settings;
    }
    
    /**
     * Busca configurações por categoria
     */
    public function getByCategory($category) {
        $sql = "SELECT * FROM {$this->table} WHERE categoria = :categoria AND ativo = 1 ORDER BY chave";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':categoria', $category);
        $stmt->execute();
        
        $settings = [];
        while ($row = $stmt->fetch()) {
            $value = $this->convertValue($row['valor'], $row['tipo']);
            $settings[$row['chave']] = $value;
        }
        
        return $settings;
    }
    
    /**
     * Busca uma configuração específica
     */
    public function get($key) {
        $sql = "SELECT * FROM {$this->table} WHERE chave = :chave AND ativo = 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':chave', $key);
        $stmt->execute();
        
        $row = $stmt->fetch();
        if ($row) {
            return $this->convertValue($row['valor'], $row['tipo']);
        }
        
        return null;
    }
    
    /**
     * Atualiza uma configuração
     */
    public function set($key, $value) {
        // Primeiro verifica se a configuração existe
        $sql = "SELECT tipo FROM {$this->table} WHERE chave = :chave";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':chave', $key);
        $stmt->execute();
        
        $existing = $stmt->fetch();
        if (!$existing) {
            return false; // Configuração não existe
        }
        
        // Converte o valor para string baseado no tipo
        $stringValue = $this->convertToString($value, $existing['tipo']);
        
        // Atualiza a configuração
        $sql = "UPDATE {$this->table} SET valor = :valor, data_atualizacao = CURRENT_TIMESTAMP WHERE chave = :chave";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':valor', $stringValue);
        $stmt->bindParam(':chave', $key);
        
        return $stmt->execute();
    }
    
    /**
     * Atualiza múltiplas configurações
     */
    public function setMultiple($settings) {
        $this->conn->beginTransaction();
        
        try {
            foreach ($settings as $key => $value) {
                if (!$this->set($key, $value)) {
                    throw new Exception("Erro ao atualizar configuração: {$key}");
                }
            }
            
            $this->conn->commit();
            return true;
        } catch (Exception $e) {
            $this->conn->rollback();
            return false;
        }
    }
    
    /**
     * Cria uma nova configuração
     */
    public function create($key, $value, $type = 'string', $description = '', $category = 'geral') {
        $sql = "INSERT INTO {$this->table} (chave, valor, tipo, descricao, categoria) VALUES (:chave, :valor, :tipo, :descricao, :categoria)";
        $stmt = $this->conn->prepare($sql);
        
        $stringValue = $this->convertToString($value, $type);
        
        $stmt->bindParam(':chave', $key);
        $stmt->bindParam(':valor', $stringValue);
        $stmt->bindParam(':tipo', $type);
        $stmt->bindParam(':descricao', $description);
        $stmt->bindParam(':categoria', $category);
        
        return $stmt->execute();
    }
    
    /**
     * Remove uma configuração
     */
    public function delete($key) {
        $sql = "UPDATE {$this->table} SET ativo = 0 WHERE chave = :chave";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':chave', $key);
        
        return $stmt->execute();
    }
    
    /**
     * Lista todas as configurações com metadados
     */
    public function getAllWithMetadata() {
        $sql = "SELECT * FROM {$this->table} WHERE ativo = 1 ORDER BY categoria, chave";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        
        $settings = [];
        while ($row = $stmt->fetch()) {
            $settings[] = [
                'id' => $row['id'],
                'chave' => $row['chave'],
                'valor' => $this->convertValue($row['valor'], $row['tipo']),
                'tipo' => $row['tipo'],
                'descricao' => $row['descricao'],
                'categoria' => $row['categoria'],
                'data_criacao' => $row['data_criacao'],
                'data_atualizacao' => $row['data_atualizacao']
            ];
        }
        
        return $settings;
    }
    
    /**
     * Converte valor da string do banco para o tipo correto
     */
    private function convertValue($value, $type) {
        switch ($type) {
            case 'boolean':
                return $value === 'true' || $value === '1' || $value === 1;
            case 'number':
                return is_numeric($value) ? (float)$value : 0;
            case 'json':
                return json_decode($value, true) ?: [];
            case 'string':
            default:
                return $value;
        }
    }
    
    /**
     * Converte valor para string para armazenar no banco
     */
    private function convertToString($value, $type) {
        switch ($type) {
            case 'boolean':
                return $value ? 'true' : 'false';
            case 'number':
                return (string)$value;
            case 'json':
                return json_encode($value);
            case 'string':
            default:
                return (string)$value;
        }
    }
    
    /**
     * Restaura configurações padrão
     */
    public function restoreDefaults() {
        $defaults = [
            'app_name' => 'DocGest',
            'max_file_size' => '10',
            'allowed_file_types' => 'pdf,doc,docx,jpg,jpeg,png',
            'smtp_host' => '',
            'smtp_port' => '587',
            'smtp_username' => '',
            'smtp_password' => '',
            'smtp_from_email' => '',
            'smtp_from_name' => '',
            'email_notifications' => true,
            'whatsapp_notifications' => false,
            'signature_reminders' => true,
            'expiration_alerts' => true,
            'password_min_length' => '8',
            'require_password_complexity' => true,
            'session_timeout' => '24',
            'max_login_attempts' => '5',
            'signature_expiration_days' => '30',
            'auto_reminder_days' => '7',
            'max_signers_per_document' => '10'
        ];
        
        return $this->setMultiple($defaults);
    }
}