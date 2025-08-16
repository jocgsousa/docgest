<?php

require_once __DIR__ . '/../config/database.php';

class Plan {
    private $db;
    
    public function __construct() {
        $this->db = getConnection();
    }
    
    /**
     * Lista todos os planos ativos
     */
    public function listAll() {
        $sql = "SELECT * FROM planos WHERE ativo = 1 ORDER BY preco ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }
    
    /**
     * Lista planos com filtros e paginação
     */
    public function list($filters = [], $page = 1, $pageSize = 10) {
        $offset = ($page - 1) * $pageSize;
        
        $where = ['p.ativo = 1'];
        $params = [];
        
        if (isset($filters['search']) && !empty($filters['search'])) {
            $where[] = '(p.nome LIKE :search OR p.descricao LIKE :search)';
            $params[':search'] = '%' . $filters['search'] . '%';
        }
        
        $whereClause = implode(' AND ', $where);
        
        // Contar total
        $countSql = "SELECT COUNT(*) as total FROM planos p WHERE $whereClause";
        $countStmt = $this->db->prepare($countSql);
        $countStmt->execute($params);
        $total = $countStmt->fetch()['total'];
        
        // Buscar dados
        $sql = "SELECT p.*, 
                       COUNT(e.id) as total_empresas
                FROM planos p
                LEFT JOIN empresas e ON p.id = e.plano_id AND e.ativo = 1
                WHERE $whereClause
                GROUP BY p.id
                ORDER BY p.preco ASC
                LIMIT :limit OFFSET :offset";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':limit', $pageSize, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        $stmt->execute();
        $data = $stmt->fetchAll();
        
        return [
            'data' => $data,
            'total' => $total
        ];
    }
    
    /**
     * Busca plano por ID
     */
    public function findById($id) {
        $sql = "SELECT p.*, 
                       COUNT(e.id) as total_empresas
                FROM planos p
                LEFT JOIN empresas e ON p.id = e.plano_id AND e.ativo = 1
                WHERE p.id = :id AND p.ativo = 1
                GROUP BY p.id";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetch();
    }
    
    /**
     * Cria novo plano
     */
    public function create($data) {
        $sql = "INSERT INTO planos (nome, descricao, preco, limite_usuarios, limite_documentos, limite_assinaturas)
                VALUES (:nome, :descricao, :preco, :limite_usuarios, :limite_documentos, :limite_assinaturas)";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':nome', $data['nome']);
        $stmt->bindValue(':descricao', $data['descricao'] ?? null);
        $stmt->bindValue(':preco', $data['preco']);
        $stmt->bindValue(':limite_usuarios', $data['limite_usuarios']);
        $stmt->bindValue(':limite_documentos', $data['limite_documentos']);
        $stmt->bindValue(':limite_assinaturas', $data['limite_assinaturas']);
        
        if ($stmt->execute()) {
            $id = $this->db->lastInsertId();
            return $this->findById($id);
        }
        
        return false;
    }
    
    /**
     * Atualiza plano
     */
    public function update($id, $data) {
        $fields = [];
        $params = [':id' => $id];
        
        $allowedFields = ['nome', 'descricao', 'preco', 'limite_usuarios', 'limite_documentos', 'limite_assinaturas'];
        
        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $fields[] = "$field = :$field";
                $params[":$field"] = $data[$field];
            }
        }
        
        if (empty($fields)) {
            return $this->findById($id);
        }
        
        $sql = "UPDATE planos SET " . implode(', ', $fields) . " WHERE id = :id";
        
        $stmt = $this->db->prepare($sql);
        
        if ($stmt->execute($params)) {
            return $this->findById($id);
        }
        
        return false;
    }
    
    /**
     * Remove plano (soft delete)
     */
    public function delete($id) {
        // Verificar se há empresas usando este plano
        $sql = "SELECT COUNT(*) as count FROM empresas WHERE plano_id = :id AND ativo = 1";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetch();
        
        if ($result['count'] > 0) {
            throw new Exception('Não é possível excluir este plano pois há empresas utilizando-o');
        }
        
        $sql = "UPDATE planos SET ativo = 0 WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        
        return $stmt->execute();
    }
    
    /**
     * Verifica se nome já existe
     */
    public function nameExists($nome, $excludeId = null) {
        $sql = "SELECT COUNT(*) as count FROM planos WHERE nome = :nome AND ativo = 1";
        
        if ($excludeId) {
            $sql .= " AND id != :exclude_id";
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':nome', $nome);
        
        if ($excludeId) {
            $stmt->bindValue(':exclude_id', $excludeId, PDO::PARAM_INT);
        }
        
        $stmt->execute();
        $result = $stmt->fetch();
        
        return $result['count'] > 0;
    }
    
    /**
     * Estatísticas de planos
     */
    public function getStats() {
        $sql = "SELECT 
                    COUNT(*) as total_planos,
                    AVG(preco) as preco_medio,
                    MIN(preco) as menor_preco,
                    MAX(preco) as maior_preco
                FROM planos 
                WHERE ativo = 1";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        
        return $stmt->fetch();
    }
    
    /**
     * Planos mais utilizados
     */
    public function getMostUsed($limit = 5) {
        $sql = "SELECT p.*, COUNT(e.id) as total_empresas
                FROM planos p
                LEFT JOIN empresas e ON p.id = e.plano_id AND e.ativo = 1
                WHERE p.ativo = 1
                GROUP BY p.id
                ORDER BY total_empresas DESC, p.nome ASC
                LIMIT :limit";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }
    
    /**
     * Busca empresas de um plano
     */
    public function getCompanies($planId) {
        $sql = "SELECT e.id, e.nome, e.cnpj, e.email, e.data_vencimento
                FROM empresas e
                WHERE e.plano_id = :plano_id AND e.ativo = 1
                ORDER BY e.nome ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':plano_id', $planId, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }
}

?>