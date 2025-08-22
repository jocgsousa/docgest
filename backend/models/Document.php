<?php

require_once __DIR__ . '/../config/database.php';

class Document {
    private $db;
    private $table = 'documentos';
    
    public function __construct() {
        $this->db = getConnection();
    }
    
    public function create($data) {
        // Gerar hash único para o documento
        $hash_acesso = hash('sha256', uniqid() . microtime(true) . random_bytes(16));
        
        $sql = "INSERT INTO {$this->table} (titulo, descricao, nome_arquivo, caminho_arquivo, tamanho_arquivo, tipo_arquivo, status, tipo_documento_id, prazo_assinatura, competencia, validade_legal, criado_por, empresa_id, filial_id, hash_acesso) 
                VALUES (:titulo, :descricao, :nome_arquivo, :caminho_arquivo, :tamanho_arquivo, :tipo_arquivo, :status, :tipo_documento_id, :prazo_assinatura, :competencia, :validade_legal, :criado_por, :empresa_id, :filial_id, :hash_acesso)";
        
        $stmt = $this->db->prepare($sql);
        
        $stmt->bindParam(':titulo', $data['titulo']);
        $stmt->bindParam(':descricao', $data['descricao']);
        $stmt->bindParam(':nome_arquivo', $data['nome_arquivo']);
        $stmt->bindParam(':caminho_arquivo', $data['caminho_arquivo']);
        $stmt->bindParam(':tamanho_arquivo', $data['tamanho_arquivo']);
        $stmt->bindParam(':tipo_arquivo', $data['tipo_arquivo']);
        $stmt->bindParam(':status', $data['status']);
        $stmt->bindParam(':tipo_documento_id', $data['tipo_documento_id']);
        $stmt->bindParam(':prazo_assinatura', $data['prazo_assinatura']);
        $stmt->bindParam(':competencia', $data['competencia']);
        $stmt->bindParam(':validade_legal', $data['validade_legal']);
        $stmt->bindParam(':criado_por', $data['criado_por']);
        $stmt->bindParam(':empresa_id', $data['empresa_id']);
        $stmt->bindParam(':filial_id', $data['filial_id']);
        $stmt->bindParam(':hash_acesso', $hash_acesso);
        
        if ($stmt->execute()) {
            return $this->db->lastInsertId();
        }
        
        return false;
    }
    
    public function findByHash($hash) {
        $sql = "SELECT d.*, u.nome as criado_por_nome, e.nome as empresa_nome, f.nome as filial_nome, td.nome as tipo_documento_nome 
                FROM {$this->table} d 
                LEFT JOIN usuarios u ON d.criado_por = u.id 
                LEFT JOIN empresas e ON d.empresa_id = e.id 
                LEFT JOIN filiais f ON d.filial_id = f.id 
                LEFT JOIN tipos_documentos td ON d.tipo_documento_id = td.id 
                WHERE d.hash_acesso = :hash AND d.ativo = 1";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':hash', $hash);
        $stmt->execute();
        
        $document = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($document) {
            // Buscar assinantes do documento
            $assinantesSql = "SELECT da.usuario_id, u.nome, u.email 
                             FROM documento_assinantes da 
                             LEFT JOIN usuarios u ON da.usuario_id = u.id 
                             WHERE da.documento_id = :documento_id AND da.ativo = 1";
            
            $assinantesStmt = $this->db->prepare($assinantesSql);
            $assinantesStmt->bindParam(':documento_id', $document['id']);
            $assinantesStmt->execute();
            
            $document['assinantes'] = $assinantesStmt->fetchAll(PDO::FETCH_ASSOC);
        }
        
        return $document;
    }
    
    public function findById($id) {
        $sql = "SELECT d.*, u.nome as criado_por_nome, e.nome as empresa_nome, f.nome as filial_nome, td.nome as tipo_documento_nome 
                FROM {$this->table} d 
                LEFT JOIN usuarios u ON d.criado_por = u.id 
                LEFT JOIN empresas e ON d.empresa_id = e.id 
                LEFT JOIN filiais f ON d.filial_id = f.id 
                LEFT JOIN tipos_documentos td ON d.tipo_documento_id = td.id 
                WHERE d.id = :id AND d.ativo = 1";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        
        $document = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($document) {
            // Buscar assinantes do documento
            $assinantesSql = "SELECT da.usuario_id, u.nome, u.email 
                             FROM documento_assinantes da 
                             LEFT JOIN usuarios u ON da.usuario_id = u.id 
                             WHERE da.documento_id = :documento_id AND da.ativo = 1";
            
            $assinantesStmt = $this->db->prepare($assinantesSql);
            $assinantesStmt->bindParam(':documento_id', $id);
            $assinantesStmt->execute();
            
            $document['assinantes'] = $assinantesStmt->fetchAll(PDO::FETCH_ASSOC);
        }
        
        return $document;
    }
    
    public function findAll($filters = [], $page = 1, $pageSize = 10) {
        $conditions = ['d.ativo = 1'];
        $params = [];
        
        if (!empty($filters['search'])) {
            $conditions[] = '(d.titulo LIKE :search OR d.descricao LIKE :search)';
            $params[':search'] = '%' . $filters['search'] . '%';
        }
        
        if (!empty($filters['status'])) {
            $conditions[] = 'd.status = :status';
            $params[':status'] = $filters['status'];
        }
        
        if (!empty($filters['empresa_id'])) {
            $conditions[] = 'd.empresa_id = :empresa_id';
            $params[':empresa_id'] = $filters['empresa_id'];
        }
        
        if (!empty($filters['filial_id'])) {
            $conditions[] = 'd.filial_id = :filial_id';
            $params[':filial_id'] = $filters['filial_id'];
        }
        
        if (!empty($filters['criado_por'])) {
            $conditions[] = 'd.criado_por = :criado_por';
            $params[':criado_por'] = $filters['criado_por'];
        }
        
        if (!empty($filters['tipo_documento_id'])) {
            $conditions[] = 'd.tipo_documento_id = :tipo_documento_id';
            $params[':tipo_documento_id'] = $filters['tipo_documento_id'];
        }
        
        if (!empty($filters['validade_inicio']) && !empty($filters['validade_fim'])) {
            $conditions[] = 'd.prazo_assinatura BETWEEN :validade_inicio AND :validade_fim';
            $params[':validade_inicio'] = $filters['validade_inicio'];
            $params[':validade_fim'] = $filters['validade_fim'];
        }
        
        if (!empty($filters['competencia'])) {
            $conditions[] = 'DATE_FORMAT(d.competencia, "%Y-%m") = :competencia';
            $params[':competencia'] = $filters['competencia'];
        }
        
        $whereClause = implode(' AND ', $conditions);
        
        // Count total
        $countSql = "SELECT COUNT(*) as total FROM {$this->table} d WHERE {$whereClause}";
        $countStmt = $this->db->prepare($countSql);
        foreach ($params as $key => $value) {
            $countStmt->bindValue($key, $value);
        }
        $countStmt->execute();
        $total = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Get data
        $offset = ($page - 1) * $pageSize;
        $sql = "SELECT d.*, u.nome as criado_por_nome, e.nome as empresa_nome, td.nome as tipo_documento_nome 
                FROM {$this->table} d 
                LEFT JOIN usuarios u ON d.criado_por = u.id 
                LEFT JOIN empresas e ON d.empresa_id = e.id 
                LEFT JOIN tipos_documentos td ON d.tipo_documento_id = td.id 
                WHERE {$whereClause} 
                ORDER BY d.data_criacao DESC 
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
            'total' => $total,
            'page' => $page,
            'pageSize' => $pageSize,
            'totalPages' => ceil($total / $pageSize)
        ];
    }
    
    public function update($id, $data) {
        $fields = [];
        $params = [':id' => $id];
        
        $allowedFields = ['titulo', 'descricao', 'nome_arquivo', 'caminho_arquivo', 'tamanho_arquivo', 'tipo_arquivo', 'status', 'tipo_documento_id', 'prazo_assinatura', 'competencia', 'validade_legal', 'empresa_id', 'filial_id', 'hash_acesso'];
        
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
    
    /**
     * Conta total de documentos
     */
    public function count() {
        $sql = "SELECT COUNT(*) as total FROM {$this->table} WHERE ativo = 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['total'];
    }
    
    /**
     * Conta documentos por empresa
     */
    public function countByCompany($companyId) {
        $sql = "SELECT COUNT(*) as total FROM {$this->table} WHERE empresa_id = :empresa_id AND ativo = 1";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':empresa_id', $companyId);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['total'];
    }
    
    /**
     * Conta documentos por usuário
     */
    public function countByUser($userId) {
        $sql = "SELECT COUNT(*) as total FROM {$this->table} WHERE criado_por = :criado_por AND ativo = 1";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':criado_por', $userId);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['total'];
    }
    
    /**
     * Documentos recentes (global)
     */
    public function getRecent($limit = 10) {
        $sql = "SELECT d.*, u.nome as criado_por_nome 
                FROM {$this->table} d 
                LEFT JOIN usuarios u ON d.criado_por = u.id 
                WHERE d.ativo = 1 
                ORDER BY d.data_criacao DESC 
                LIMIT :limit";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Documentos recentes por empresa
     */
    public function getRecentByCompany($companyId, $limit = 10) {
        $sql = "SELECT d.*, u.nome as criado_por_nome 
                FROM {$this->table} d 
                LEFT JOIN usuarios u ON d.criado_por = u.id 
                WHERE d.empresa_id = :empresa_id AND d.ativo = 1 
                ORDER BY d.data_criacao DESC 
                LIMIT :limit";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':empresa_id', $companyId);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Documentos recentes por usuário
     */
    public function getRecentByUser($userId, $limit = 10) {
        $sql = "SELECT d.*, u.nome as criado_por_nome 
                FROM {$this->table} d 
                LEFT JOIN usuarios u ON d.criado_por = u.id 
                WHERE d.criado_por = :criado_por AND d.ativo = 1 
                ORDER BY d.data_criacao DESC 
                LIMIT :limit";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':criado_por', $userId);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
     }
     
     public function getStats($filters = []) {
        $conditions = ['d.ativo = 1'];
        $params = [];
        
        if (!empty($filters['empresa_id'])) {
            $conditions[] = 'd.empresa_id = :empresa_id';
            $params[':empresa_id'] = $filters['empresa_id'];
        }
        
        if (!empty($filters['filial_id'])) {
            $conditions[] = 'd.filial_id = :filial_id';
            $params[':filial_id'] = $filters['filial_id'];
        }
        
        if (!empty($filters['criado_por'])) {
            $conditions[] = 'd.criado_por = :criado_por';
            $params[':criado_por'] = $filters['criado_por'];
        }
        
        $whereClause = implode(' AND ', $conditions);
        
        // Estatísticas gerais
        $sql = "SELECT 
                    COUNT(*) as total_documentos,
                    COUNT(CASE WHEN d.status = 'rascunho' THEN 1 END) as rascunhos,
                    COUNT(CASE WHEN d.status = 'enviado' THEN 1 END) as enviados,
                    COUNT(CASE WHEN d.status = 'assinado' THEN 1 END) as assinados,
                    COUNT(CASE WHEN d.status = 'cancelado' THEN 1 END) as cancelados,
                    SUM(d.tamanho_arquivo) as tamanho_total
                FROM {$this->table} d 
                WHERE {$whereClause}";
        
        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();
        $geral = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Estatísticas por status
        $sql = "SELECT d.status, COUNT(*) as total 
                FROM {$this->table} d 
                WHERE {$whereClause} 
                GROUP BY d.status";
        
        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();
        $porStatus = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $porStatus[$row['status']] = $row['total'];
        }
        
        // Documentos recentes
        $sql = "SELECT d.*, u.nome as criado_por_nome 
                FROM {$this->table} d 
                LEFT JOIN usuarios u ON d.criado_por = u.id 
                WHERE {$whereClause} 
                ORDER BY d.data_criacao DESC 
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
    
    public function getForSelect($filters = []) {
        $conditions = ['ativo = 1', "status IN ('rascunho', 'enviado')"];
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
        
        $sql = "SELECT id, titulo FROM {$this->table} WHERE {$whereClause} ORDER BY titulo";
        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function updateStatus($id, $status) {
        $sql = "UPDATE {$this->table} SET status = :status, data_atualizacao = NOW() WHERE id = :id AND ativo = 1";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':id', $id);
        $stmt->bindParam(':status', $status);
        
        return $stmt->execute();
    }
    
    
    /**
     * Obtém documentos para relatório
     */
    public function getForReport($filters = []) {
        $conditions = ['ativo = 1'];
        $params = [];
        
        if (!empty($filters['empresa_id'])) {
            $conditions[] = 'empresa_id = :empresa_id';
            $params[':empresa_id'] = $filters['empresa_id'];
        }
        
        if (!empty($filters['criado_por'])) {
            $conditions[] = 'criado_por = :criado_por';
            $params[':criado_por'] = $filters['criado_por'];
        }
        
        if (!empty($filters['start_date'])) {
            $conditions[] = 'DATE(data_criacao) >= :start_date';
            $params[':start_date'] = $filters['start_date'];
        }
        
        if (!empty($filters['end_date'])) {
            $conditions[] = 'DATE(data_criacao) <= :end_date';
            $params[':end_date'] = $filters['end_date'];
        }
        
        $whereClause = implode(' AND ', $conditions);
        
        $sql = "SELECT d.titulo, d.status, u.nome as criado_por, e.nome as empresa, 
                       d.data_criacao, d.tamanho_arquivo
                FROM {$this->table} d 
                LEFT JOIN usuarios u ON d.criado_por = u.id 
                LEFT JOIN empresas e ON d.empresa_id = e.id 
                WHERE {$whereClause} 
                ORDER BY d.data_criacao DESC";
        
        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getDocumentTypes() {
        $sql = "SELECT * FROM tipos_documentos WHERE ativo = 1 ORDER BY nome ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getDocumentTypeById($id) {
        $sql = "SELECT * FROM tipos_documentos WHERE id = :id AND ativo = 1";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}