<?php

require_once __DIR__ . '/../config/database.php';

class Notification {
    private $conn;
    private $table = 'notificacoes';
    
    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }
    
    /**
     * Lista notificações de um usuário
     */
    public function listByUser($userId, $limit = 10, $offset = 0) {
        $sql = "SELECT n.*, 
                       ur.nome as remetente_nome,
                       ur.email as remetente_email,
                       e.nome as empresa_nome
                FROM {$this->table} n
                LEFT JOIN usuarios ur ON n.usuario_remetente_id = ur.id
                LEFT JOIN empresas e ON n.empresa_id = e.id
                WHERE n.usuario_destinatario_id = :user_id AND n.ativo = 1
                ORDER BY n.data_criacao DESC
                LIMIT :limit OFFSET :offset";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }
    
    /**
     * Conta notificações não lidas de um usuário
     */
    public function countUnreadByUser($userId) {
        $sql = "SELECT COUNT(*) as total 
                FROM {$this->table} 
                WHERE usuario_destinatario_id = :user_id 
                AND lida = 0 AND ativo = 1";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        
        $result = $stmt->fetch();
        return $result['total'];
    }
    
    /**
     * Cria uma nova notificação
     */
    public function create($data) {
        $sql = "INSERT INTO {$this->table} 
                (titulo, mensagem, tipo, usuario_destinatario_id, usuario_remetente_id, empresa_id) 
                VALUES (:titulo, :mensagem, :tipo, :usuario_destinatario_id, :usuario_remetente_id, :empresa_id)";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':titulo', $data['titulo']);
        $stmt->bindParam(':mensagem', $data['mensagem']);
        $stmt->bindParam(':tipo', $data['tipo']);
        $stmt->bindParam(':usuario_destinatario_id', $data['usuario_destinatario_id']);
        $stmt->bindParam(':usuario_remetente_id', $data['usuario_remetente_id']);
        $stmt->bindParam(':empresa_id', $data['empresa_id']);
        
        if ($stmt->execute()) {
            return $this->conn->lastInsertId();
        }
        
        return false;
    }
    
    /**
     * Marca notificação como lida
     */
    public function markAsRead($id, $userId) {
        $sql = "UPDATE {$this->table} 
                SET lida = 1, data_leitura = NOW() 
                WHERE id = :id AND usuario_destinatario_id = :user_id";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        
        return $stmt->execute();
    }
    
    /**
     * Marca todas as notificações como lidas para um usuário
     */
    public function markAllAsRead($userId) {
        $sql = "UPDATE {$this->table} 
                SET lida = 1, data_leitura = NOW() 
                WHERE usuario_destinatario_id = :user_id AND lida = 0";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        
        return $stmt->execute();
    }
    
    /**
     * Busca notificação por ID
     */
    public function findById($id) {
        $sql = "SELECT n.*, 
                       ur.nome as remetente_nome,
                       ur.email as remetente_email,
                       ud.nome as destinatario_nome,
                       ud.email as destinatario_email,
                       e.nome as empresa_nome
                FROM {$this->table} n
                LEFT JOIN usuarios ur ON n.usuario_remetente_id = ur.id
                LEFT JOIN usuarios ud ON n.usuario_destinatario_id = ud.id
                LEFT JOIN empresas e ON n.empresa_id = e.id
                WHERE n.id = :id AND n.ativo = 1";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetch();
    }
    
    /**
     * Cria notificação para todos os usuários de uma empresa
     */
    public function createForCompany($data, $companyId) {
        // Busca todos os usuários da empresa
        $sql = "SELECT id FROM usuarios WHERE empresa_id = :company_id AND ativo = 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':company_id', $companyId, PDO::PARAM_INT);
        $stmt->execute();
        $users = $stmt->fetchAll();
        
        $created = 0;
        foreach ($users as $user) {
            $notificationData = $data;
            $notificationData['usuario_destinatario_id'] = $user['id'];
            $notificationData['empresa_id'] = $companyId;
            
            if ($this->create($notificationData)) {
                $created++;
            }
        }
        
        return $created;
    }
    
    /**
     * Cria notificação para todos os usuários do sistema (Super Admin)
     */
    public function createForAllUsers($data) {
        // Busca todos os usuários ativos
        $sql = "SELECT id, empresa_id FROM usuarios WHERE ativo = 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        $users = $stmt->fetchAll();
        
        $created = 0;
        foreach ($users as $user) {
            $notificationData = $data;
            $notificationData['usuario_destinatario_id'] = $user['id'];
            $notificationData['empresa_id'] = $user['empresa_id'];
            
            if ($this->create($notificationData)) {
                $created++;
            }
        }
        
        return $created;
    }
    
    /**
     * Remove notificação (soft delete)
     */
    public function delete($id, $userId) {
        $sql = "UPDATE {$this->table} 
                SET ativo = 0 
                WHERE id = :id AND usuario_destinatario_id = :user_id";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        
        return $stmt->execute();
    }
}