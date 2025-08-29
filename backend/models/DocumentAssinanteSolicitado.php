<?php

require_once __DIR__ . '/../config/database.php';

class DocumentAssinanteSolicitado {
    private $db;
    private $table = 'documento_assinantes_solicitados';
    
    public function __construct() {
        $this->db = getConnection();
    }
    
    public function create($data) {
        $sql = "INSERT INTO {$this->table} (documento_id, usuario_id, status, data_solicitacao) 
                VALUES (:documento_id, :usuario_id, :status, :data_solicitacao)";
        
        $stmt = $this->db->prepare($sql);
        
        $stmt->bindParam(':documento_id', $data['documento_id']);
        $stmt->bindParam(':usuario_id', $data['usuario_id']);
        $stmt->bindParam(':status', $data['status']);
        $stmt->bindParam(':data_solicitacao', $data['data_solicitacao']);
        
        if ($stmt->execute()) {
            return $this->db->lastInsertId();
        }
        
        return false;
    }
    
    public function findByDocumentId($documento_id) {
        $sql = "SELECT das.*, u.nome as usuario_nome, u.email as usuario_email 
                FROM {$this->table} das 
                LEFT JOIN usuarios u ON das.usuario_id = u.id 
                WHERE das.documento_id = :documento_id
                ORDER BY das.data_solicitacao ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':documento_id', $documento_id);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function findByUserId($usuario_id, $filters = [], $page = 1, $pageSize = 10) {
        $where = "das.usuario_id = :usuario_id";
        $params = [':usuario_id' => $usuario_id];
        
        if (isset($filters['status']) && !empty($filters['status'])) {
            $where .= " AND das.status = :status";
            $params[':status'] = $filters['status'];
        }
        
        if (isset($filters['search']) && !empty($filters['search'])) {
            $where .= " AND (d.titulo LIKE :search OR d.descricao LIKE :search)";
            $params[':search'] = '%' . $filters['search'] . '%';
        }
        
        // Contar total
        $countSql = "SELECT COUNT(*) as total 
                     FROM {$this->table} das 
                     LEFT JOIN documentos d ON das.documento_id = d.id 
                     WHERE {$where}";
        
        $countStmt = $this->db->prepare($countSql);
        $countStmt->execute($params);
        $total = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Buscar dados paginados
        $offset = ($page - 1) * $pageSize;
        $sql = "SELECT das.*, d.titulo, d.descricao, d.status as documento_status, 
                       d.data_criacao as documento_data_criacao, d.solicitar_assinatura,
                       u.nome as criado_por_nome, e.nome as empresa_nome, f.nome as filial_nome
                FROM {$this->table} das 
                LEFT JOIN documentos d ON das.documento_id = d.id 
                LEFT JOIN usuarios u ON d.criado_por = u.id 
                LEFT JOIN empresas e ON d.empresa_id = e.id 
                LEFT JOIN filiais f ON d.filial_id = f.id 
                WHERE {$where}
                ORDER BY das.data_solicitacao DESC 
                LIMIT :limit OFFSET :offset";
        
        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit', $pageSize, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        return [
            'data' => $stmt->fetchAll(PDO::FETCH_ASSOC),
            'total' => $total
        ];
    }
    
    public function updateStatus($id, $data) {
        if (is_string($data)) {
            // Compatibilidade com versão antiga que só passava status
            $sql = "UPDATE {$this->table} SET status = :status WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':status', $data);
            $stmt->bindParam(':id', $id);
            return $stmt->execute();
        }
        
        // Nova versão que aceita array de dados
        $fields = [];
        $params = [':id' => $id];
        
        foreach ($data as $key => $value) {
            $fields[] = "{$key} = :{$key}";
            $params[":{$key}"] = $value;
        }
        
        $sql = "UPDATE {$this->table} SET " . implode(', ', $fields) . " WHERE id = :id";
        
        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        return $stmt->execute();
    }
    
    public function findByDocumentAndUser($documento_id, $usuario_id) {
        $sql = "SELECT * FROM {$this->table} 
                WHERE documento_id = :documento_id AND usuario_id = :usuario_id";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':documento_id', $documento_id);
        $stmt->bindParam(':usuario_id', $usuario_id);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    public function findByDocument($documento_id) {
        $sql = "SELECT das.*, u.nome as usuario_nome, u.email as usuario_email 
                FROM {$this->table} das 
                LEFT JOIN usuarios u ON das.usuario_id = u.id 
                WHERE das.documento_id = :documento_id
                ORDER BY das.data_solicitacao ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':documento_id', $documento_id);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function deleteByDocumentId($documento_id) {
        $sql = "DELETE FROM {$this->table} WHERE documento_id = :documento_id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':documento_id', $documento_id);
        return $stmt->execute();
    }
    
    /**
     * Conta documentos solicitados para assinatura onde o usuário é assinante
     */
    public function countByUser($userId) {
        $sql = "SELECT COUNT(DISTINCT das.documento_id) as total 
                FROM {$this->table} das 
                LEFT JOIN documentos d ON das.documento_id = d.id 
                WHERE das.usuario_id = :usuario_id AND d.ativo = 1";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':usuario_id', $userId);
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['total'];
    }
    
    /**
     * Conta documentos pendentes solicitados para assinatura onde o usuário é assinante
     */
    public function countPendingByUser($userId) {
        $sql = "SELECT COUNT(DISTINCT das.documento_id) as total 
                FROM {$this->table} das 
                LEFT JOIN documentos d ON das.documento_id = d.id 
                WHERE das.usuario_id = :usuario_id AND das.status = 'pendente' AND d.ativo = 1";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':usuario_id', $userId);
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['total'];
    }
}