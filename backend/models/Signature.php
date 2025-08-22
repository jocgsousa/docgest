<?php

require_once __DIR__ . '/../config/database.php';

class Signature {
    private $db;
    private $table = 'assinaturas';
    
    public function __construct() {
        $this->db = getConnection();
    }
    
    public function create($data) {
        $sql = "INSERT INTO {$this->table} (documento_id, status, data_expiracao, criado_por, empresa_id, filial_id) 
                VALUES (:documento_id, :status, :data_expiracao, :criado_por, :empresa_id, :filial_id)";
        
        $stmt = $this->db->prepare($sql);
        
        $stmt->bindParam(':documento_id', $data['documento_id']);
        $stmt->bindParam(':status', $data['status']);
        $stmt->bindParam(':data_expiracao', $data['data_expiracao']);
        $stmt->bindParam(':criado_por', $data['criado_por']);
        $stmt->bindParam(':empresa_id', $data['empresa_id']);
        $stmt->bindParam(':filial_id', $data['filial_id']);
        
        if ($stmt->execute()) {
            return $this->db->lastInsertId();
        }
        
        return false;
    }
    
    public function findById($id) {
        $sql = "SELECT a.*, d.titulo as documento_titulo, u.nome as criado_por_nome, e.nome as empresa_nome 
                FROM {$this->table} a 
                LEFT JOIN documentos d ON a.documento_id = d.id 
                LEFT JOIN usuarios u ON a.criado_por = u.id 
                LEFT JOIN empresas e ON a.empresa_id = e.id 
                WHERE a.id = :id AND a.ativo = 1";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        
        $signature = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($signature) {
            $signature['signatarios'] = $this->getSigners($id);
        }
        
        return $signature;
    }
    
    public function findAll($filters = [], $page = 1, $pageSize = 10) {
        $conditions = ['a.ativo = 1'];
        $params = [];
        
        if (!empty($filters['search'])) {
            $conditions[] = 'd.titulo LIKE :search';
            $params[':search'] = '%' . $filters['search'] . '%';
        }
        
        if (!empty($filters['status'])) {
            $conditions[] = 'a.status = :status';
            $params[':status'] = $filters['status'];
        }
        
        if (!empty($filters['empresa_id'])) {
            $conditions[] = 'a.empresa_id = :empresa_id';
            $params[':empresa_id'] = $filters['empresa_id'];
        }
        
        if (!empty($filters['filial_id'])) {
            $conditions[] = 'a.filial_id = :filial_id';
            $params[':filial_id'] = $filters['filial_id'];
        }
        
        if (!empty($filters['criado_por'])) {
            $conditions[] = 'a.criado_por = :criado_por';
            $params[':criado_por'] = $filters['criado_por'];
        }
        
        $whereClause = implode(' AND ', $conditions);
        
        // Count total
        $countSql = "SELECT COUNT(*) as total 
                     FROM {$this->table} a 
                     LEFT JOIN documentos d ON a.documento_id = d.id 
                     WHERE {$whereClause}";
        $countStmt = $this->db->prepare($countSql);
        foreach ($params as $key => $value) {
            $countStmt->bindValue($key, $value);
        }
        $countStmt->execute();
        $total = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Get data
        $offset = ($page - 1) * $pageSize;
        $sql = "SELECT a.*, d.titulo as documento_titulo, u.nome as criado_por_nome, e.nome as empresa_nome 
                FROM {$this->table} a 
                LEFT JOIN documentos d ON a.documento_id = d.id 
                LEFT JOIN usuarios u ON a.criado_por = u.id 
                LEFT JOIN empresas e ON a.empresa_id = e.id 
                WHERE {$whereClause} 
                ORDER BY a.data_criacao DESC 
                LIMIT :limit OFFSET :offset";
        
        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit', $pageSize, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        $signatures = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Add signers for each signature
        foreach ($signatures as &$signature) {
            $signature['signatarios'] = $this->getSigners($signature['id']);
        }
        
        return [
            'data' => $signatures,
            'total' => $total,
            'page' => $page,
            'pageSize' => $pageSize,
            'totalPages' => ceil($total / $pageSize)
        ];
    }
    
    public function update($id, $data) {
        $fields = [];
        $params = [':id' => $id];
        
        $allowedFields = ['status', 'data_expiracao'];
        
        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $fields[] = "{$field} = :{$field}";
                $params[":{$field}"] = $data[$field];
            }
        }
        
        if (empty($fields)) {
            return false;
        }
        
        $fields[] = 'data_atualizacao = NOW()';
        $fieldsStr = implode(', ', $fields);
        
        $sql = "UPDATE {$this->table} SET {$fieldsStr} WHERE id = :id AND ativo = 1";
        $stmt = $this->db->prepare($sql);
        
        return $stmt->execute($params);
    }
    
    public function delete($id) {
        $sql = "UPDATE {$this->table} SET ativo = 0, data_atualizacao = NOW() WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':id', $id);
        
        return $stmt->execute();
    }
    
    public function updateStatus($id, $status) {
        $sql = "UPDATE {$this->table} SET status = :status, data_atualizacao = NOW() WHERE id = :id AND ativo = 1";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':id', $id);
        $stmt->bindParam(':status', $status);
        
        return $stmt->execute();
    }
    
    public function getStats($filters = []) {
        $conditions = ['ativo = 1'];
        $params = [];
        
        if (!empty($filters['empresa_id'])) {
            $conditions[] = 'empresa_id = :empresa_id';
            $params[':empresa_id'] = $filters['empresa_id'];
        }
        
        if (!empty($filters['filial_id'])) {
            $conditions[] = 'filial_id = :filial_id';
            $params[':filial_id'] = $filters['filial_id'];
        }
        
        if (!empty($filters['criado_por'])) {
            $conditions[] = 'criado_por = :criado_por';
            $params[':criado_por'] = $filters['criado_por'];
        }
        
        $whereClause = implode(' AND ', $conditions);
        
        // Estatísticas gerais
        $sql = "SELECT 
                    COUNT(*) as total_assinaturas,
                    COUNT(CASE WHEN status = 'pendente' THEN 1 END) as pendentes,
                    COUNT(CASE WHEN status = 'assinado' THEN 1 END) as assinadas,
                    COUNT(CASE WHEN status = 'rejeitado' THEN 1 END) as rejeitadas,
                    COUNT(CASE WHEN status = 'expirado' THEN 1 END) as expiradas,
                    COUNT(CASE WHEN status = 'cancelado' THEN 1 END) as canceladas
                FROM {$this->table} 
                WHERE {$whereClause}";
        
        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();
        $geral = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Estatísticas por status
        $sql = "SELECT status, COUNT(*) as total 
                FROM {$this->table} 
                WHERE {$whereClause} 
                GROUP BY status";
        
        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();
        $porStatus = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $porStatus[$row['status']] = $row['total'];
        }
        
        // Assinaturas recentes
        $sql = "SELECT a.*, d.titulo as documento_titulo, u.nome as criado_por_nome 
                FROM {$this->table} a 
                LEFT JOIN documentos d ON a.documento_id = d.id 
                LEFT JOIN usuarios u ON a.criado_por = u.id 
                WHERE " . str_replace('ativo = 1', 'a.ativo = 1', $whereClause) . " 
                ORDER BY a.data_criacao DESC 
                LIMIT 5";
        
        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();
        $recentes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return [
            'geral' => $geral,
            'por_status' => $porStatus,
            'recentes' => $recentes
        ];
    }
    
    public function getSigners($signatureId) {
        $sql = "SELECT * FROM signatarios WHERE assinatura_id = :signature_id ORDER BY ordem";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':signature_id', $signatureId);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function addSigner($signatureId, $signerData) {
        $sql = "INSERT INTO signatarios (assinatura_id, nome, email, ordem, status, token) 
                VALUES (:assinatura_id, :nome, :email, :ordem, :status, :token)";
        
        $stmt = $this->db->prepare($sql);
        
        $stmt->bindParam(':assinatura_id', $signatureId);
        $stmt->bindParam(':nome', $signerData['nome']);
        $stmt->bindParam(':email', $signerData['email']);
        $stmt->bindParam(':ordem', $signerData['ordem']);
        $stmt->bindParam(':status', $signerData['status']);
        $stmt->bindParam(':token', $signerData['token']);
        
        return $stmt->execute();
    }
    
    public function updateSignerStatus($signerId, $status, $signedAt = null) {
        $sql = "UPDATE signatarios SET status = :status";
        $params = [':status' => $status, ':id' => $signerId];
        
        if ($signedAt) {
            $sql .= ", data_assinatura = :data_assinatura";
            $params[':data_assinatura'] = $signedAt;
        }
        
        $sql .= " WHERE id = :id";
        
        $stmt = $this->db->prepare($sql);
        
        return $stmt->execute($params);
    }
    
    public function getExpiredSignatures() {
        $sql = "SELECT * FROM {$this->table} 
                WHERE status = 'pendente' 
                AND data_expiracao < NOW() 
                AND ativo = 1";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getPendingSignatures($filters = []) {
        $conditions = ['status = \'pendente\'', 'ativo = 1'];
        $params = [];
        
        if (!empty($filters['empresa_id'])) {
            $conditions[] = 'empresa_id = :empresa_id';
            $params[':empresa_id'] = $filters['empresa_id'];
        }
        
        if (!empty($filters['filial_id'])) {
            $conditions[] = 'filial_id = :filial_id';
            $params[':filial_id'] = $filters['filial_id'];
        }
        
        $whereClause = implode(' AND ', $conditions);
        
        $sql = "SELECT a.*, d.titulo as documento_titulo 
                FROM {$this->table} a 
                LEFT JOIN documentos d ON a.documento_id = d.id 
                WHERE {$whereClause} 
                ORDER BY a.data_criacao DESC";
        
        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function countByUser($userId) {
        $sql = "SELECT COUNT(*) as total FROM {$this->table} WHERE criado_por = :user_id AND ativo = 1";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':user_id', $userId);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    }
    
    public function countByCompany($companyId) {
        $sql = "SELECT COUNT(*) as total FROM {$this->table} WHERE empresa_id = :company_id AND ativo = 1";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':company_id', $companyId);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    }
    
    /**
     * Conta assinaturas por empresa em um intervalo de datas
     */
    public function countByCompanyAndDateRange($companyId, $startDate, $endDate) {
        $sql = "SELECT COUNT(*) as total FROM {$this->table} 
                WHERE empresa_id = :company_id AND ativo = 1 
                AND DATE(data_criacao) BETWEEN :start_date AND :end_date";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':company_id', $companyId);
        $stmt->bindParam(':start_date', $startDate);
        $stmt->bindParam(':end_date', $endDate);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    }
    
    public function getSignerByToken($token) {
        $sql = "SELECT s.*, a.documento_id, d.titulo as documento_titulo, d.caminho_arquivo 
                FROM signatarios s 
                LEFT JOIN {$this->table} a ON s.assinatura_id = a.id 
                LEFT JOIN documentos d ON a.documento_id = d.id 
                WHERE s.token = :token AND a.ativo = 1";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':token', $token);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Conta total de assinaturas
     */
    public function count() {
        $sql = "SELECT COUNT(*) as total FROM {$this->table} WHERE ativo = 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['total'];
    }
    

    

    
    /**
     * Assinaturas recentes (global)
     */
    public function getRecent($limit = 10) {
        $sql = "SELECT a.*, d.titulo as documento_titulo, u.nome as criado_por_nome 
                FROM {$this->table} a 
                LEFT JOIN documentos d ON a.documento_id = d.id 
                LEFT JOIN usuarios u ON a.criado_por = u.id 
                WHERE a.ativo = 1 
                ORDER BY a.data_criacao DESC 
                LIMIT :limit";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Assinaturas recentes por empresa
     */
    public function getRecentByCompany($companyId, $limit = 10) {
        $sql = "SELECT a.*, d.titulo as documento_titulo, u.nome as criado_por_nome 
                FROM {$this->table} a 
                LEFT JOIN documentos d ON a.documento_id = d.id 
                LEFT JOIN usuarios u ON a.criado_por = u.id 
                WHERE a.empresa_id = :empresa_id AND a.ativo = 1 
                ORDER BY a.data_criacao DESC 
                LIMIT :limit";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':empresa_id', $companyId);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Assinaturas recentes por usuário
     */
    public function getRecentByUser($userId, $limit = 10) {
        $sql = "SELECT a.*, d.titulo as documento_titulo, u.nome as criado_por_nome 
                FROM {$this->table} a 
                LEFT JOIN documentos d ON a.documento_id = d.id 
                LEFT JOIN usuarios u ON a.criado_por = u.id 
                WHERE a.criado_por = :criado_por AND a.ativo = 1 
                ORDER BY a.data_criacao DESC 
                LIMIT :limit";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':criado_por', $userId);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Obtém assinaturas para relatório
     */
    public function getForReport($filters = []) {
        $conditions = ['s.ativo = 1'];
        $params = [];
        
        if (!empty($filters['empresa_id'])) {
            $conditions[] = 'd.empresa_id = :empresa_id';
            $params[':empresa_id'] = $filters['empresa_id'];
        }
        
        if (!empty($filters['criado_por'])) {
            $conditions[] = 'd.criado_por = :criado_por';
            $params[':criado_por'] = $filters['criado_por'];
        }
        
        if (!empty($filters['start_date'])) {
            $conditions[] = 'DATE(s.data_criacao) >= :start_date';
            $params[':start_date'] = $filters['start_date'];
        }
        
        if (!empty($filters['end_date'])) {
            $conditions[] = 'DATE(s.data_criacao) <= :end_date';
            $params[':end_date'] = $filters['end_date'];
        }
        
        $whereClause = implode(' AND ', $conditions);
        
        $sql = "SELECT s.nome_signatario, s.email_signatario, s.status, 
                       d.titulo as documento, u.nome as criado_por, e.nome as empresa,
                       s.data_criacao, s.data_assinatura
                FROM {$this->table} s
                LEFT JOIN documentos d ON s.documento_id = d.id
                LEFT JOIN usuarios u ON d.criado_por = u.id
                LEFT JOIN empresas e ON d.empresa_id = e.id
                WHERE {$whereClause}
                ORDER BY s.data_criacao DESC";
        
        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Conta assinaturas pendentes
     */
    public function countPending() {
        $sql = "SELECT COUNT(*) FROM {$this->table} WHERE status = 'pendente'";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        
        return $stmt->fetchColumn();
    }
    
    /**
     * Conta assinaturas pendentes por empresa
     */
    public function countPendingByCompany($empresaId) {
        $sql = "SELECT COUNT(*) FROM {$this->table} WHERE empresa_id = :empresa_id AND status = 'pendente'";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':empresa_id', $empresaId);
        $stmt->execute();
        
        return $stmt->fetchColumn();
    }
    
    /**
     * Conta assinaturas pendentes por usuário
     */
    public function countPendingByUser($userId) {
        $sql = "SELECT COUNT(*) FROM {$this->table} WHERE criado_por = :user_id AND status = 'pendente'";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':user_id', $userId);
        $stmt->execute();
        
        return $stmt->fetchColumn();
    }
}