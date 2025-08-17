<?php

require_once __DIR__ . '/../config/database.php';

class DocumentAssinante {
    private $db;
    private $table = 'documento_assinantes';
    
    public function __construct() {
        $this->db = getConnection();
    }
    
    public function create($data) {
        $sql = "INSERT INTO {$this->table} (documento_id, usuario_id, status, observacoes) 
                VALUES (:documento_id, :usuario_id, :status, :observacoes)";
        
        $stmt = $this->db->prepare($sql);
        
        $stmt->bindParam(':documento_id', $data['documento_id']);
        $stmt->bindParam(':usuario_id', $data['usuario_id']);
        $stmt->bindParam(':status', $data['status']);
        $stmt->bindParam(':observacoes', $data['observacoes']);
        
        if ($stmt->execute()) {
            return $this->db->lastInsertId();
        }
        
        return false;
    }
    
    public function findByDocumentId($documento_id) {
        $sql = "SELECT da.*, u.nome as usuario_nome, u.email as usuario_email 
                FROM {$this->table} da 
                LEFT JOIN usuarios u ON da.usuario_id = u.id 
                WHERE da.documento_id = :documento_id AND da.ativo = 1
                ORDER BY da.data_criacao ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':documento_id', $documento_id);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function findByUserId($usuario_id, $filters = [], $page = 1, $pageSize = 10) {
        $where = "da.usuario_id = :usuario_id AND da.ativo = 1";
        $params = [':usuario_id' => $usuario_id];
        
        if (isset($filters['status']) && !empty($filters['status'])) {
            $where .= " AND da.status = :status";
            $params[':status'] = $filters['status'];
        }
        
        if (isset($filters['search']) && !empty($filters['search'])) {
            $where .= " AND (d.titulo LIKE :search OR d.descricao LIKE :search)";
            $params[':search'] = '%' . $filters['search'] . '%';
        }
        
        // Contar total
        $countSql = "SELECT COUNT(*) as total 
                     FROM {$this->table} da 
                     LEFT JOIN documentos d ON da.documento_id = d.id 
                     WHERE {$where}";
        
        $countStmt = $this->db->prepare($countSql);
        $countStmt->execute($params);
        $total = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Buscar dados paginados
        $offset = ($page - 1) * $pageSize;
        $sql = "SELECT da.*, d.titulo, d.descricao, d.status as documento_status, 
                       d.data_criacao as documento_data_criacao,
                       u.nome as criado_por_nome, e.nome as empresa_nome, f.nome as filial_nome
                FROM {$this->table} da 
                LEFT JOIN documentos d ON da.documento_id = d.id 
                LEFT JOIN usuarios u ON d.criado_por = u.id 
                LEFT JOIN empresas e ON d.empresa_id = e.id 
                LEFT JOIN filiais f ON d.filial_id = f.id 
                WHERE {$where}
                ORDER BY da.data_criacao DESC 
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
    
    public function updateStatus($id, $status, $observacoes = null) {
        $sql = "UPDATE {$this->table} SET status = :status";
        $params = [':status' => $status, ':id' => $id];
        
        if ($status === 'visualizado' && !$this->hasVisualized($id)) {
            $sql .= ", data_visualizacao = NOW()";
        }
        
        if ($status === 'assinado') {
            $sql .= ", data_assinatura = NOW()";
        }
        
        if ($observacoes !== null) {
            $sql .= ", observacoes = :observacoes";
            $params[':observacoes'] = $observacoes;
        }
        
        $sql .= " WHERE id = :id";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }
    
    private function hasVisualized($id) {
        $sql = "SELECT data_visualizacao FROM {$this->table} WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result && $result['data_visualizacao'] !== null;
    }
    
    public function delete($id) {
        $sql = "UPDATE {$this->table} SET ativo = 0 WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }
    
    public function deleteByDocumentId($documento_id) {
        $sql = "UPDATE {$this->table} SET ativo = 0 WHERE documento_id = :documento_id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':documento_id', $documento_id);
        return $stmt->execute();
    }
}