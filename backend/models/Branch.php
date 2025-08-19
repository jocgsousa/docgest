<?php

require_once __DIR__ . '/../config/database.php';

class Branch {
    private $conn;
    private $table = 'filiais';
    
    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }
    
    /**
     * Lista filiais com paginação e filtros
     */
    public function list($filters = [], $page = 1, $pageSize = 10) {
        $conditions = ['f.ativo = 1'];
        $params = [];
        
        // Filtro por empresa (para admins de empresa)
        if (isset($filters['empresa_id'])) {
            $conditions[] = 'f.empresa_id = :empresa_id';
            $params[':empresa_id'] = $filters['empresa_id'];
        }
        
        // Filtro de busca
        if (isset($filters['search']) && !empty($filters['search'])) {
            $conditions[] = '(f.nome LIKE :search OR f.cnpj LIKE :search OR f.email LIKE :search)';
            $params[':search'] = '%' . $filters['search'] . '%';
        }
        
        // Filtro por status
        if (isset($filters['status'])) {
            if ($filters['status'] === 'ativo') {
                $conditions[] = 'f.ativo = 1';
            } elseif ($filters['status'] === 'inativo') {
                $conditions[] = 'f.ativo = 0';
            }
        }
        
        $whereClause = implode(' AND ', $conditions);
        
        // Contar total
        $countSql = "SELECT COUNT(*) as total 
                     FROM {$this->table} f
                     LEFT JOIN empresas e ON f.empresa_id = e.id
                     WHERE {$whereClause}";
        
        $countStmt = $this->conn->prepare($countSql);
        foreach ($params as $key => $value) {
            $countStmt->bindValue($key, $value);
        }
        $countStmt->execute();
        $total = $countStmt->fetch()['total'];
        
        // Buscar dados
        $offset = ($page - 1) * $pageSize;
        $sql = "SELECT f.*, e.nome as empresa_nome
                FROM {$this->table} f
                LEFT JOIN empresas e ON f.empresa_id = e.id
                WHERE {$whereClause}
                ORDER BY f.nome ASC
                LIMIT :limit OFFSET :offset";
        
        $stmt = $this->conn->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit', $pageSize, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        return [
            'data' => $stmt->fetchAll(),
            'total' => $total
        ];
    }
    
    /**
     * Busca filial por ID
     */
    public function findById($id) {
        $sql = "SELECT f.*, e.nome as empresa_nome
                FROM {$this->table} f
                LEFT JOIN empresas e ON f.empresa_id = e.id
                WHERE f.id = :id";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        
        return $stmt->fetch();
    }
    
    /**
     * Busca filiais por empresa
     */
    public function findByEmpresa($empresaId) {
        $sql = "SELECT f.*
                FROM {$this->table} f
                WHERE f.empresa_id = :empresa_id AND f.ativo = 1
                ORDER BY f.nome ASC";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':empresa_id', $empresaId);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }
    
    /**
     * Cria nova filial
     */
    public function create($data) {
        $sql = "INSERT INTO {$this->table} (empresa_id, nome, cnpj, inscricao_estadual, endereco, cidade, estado, cep, telefone, email, responsavel, observacoes)
                VALUES (:empresa_id, :nome, :cnpj, :inscricao_estadual, :endereco, :cidade, :estado, :cep, :telefone, :email, :responsavel, :observacoes)";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':empresa_id', $data['empresa_id']);
        $stmt->bindParam(':nome', $data['nome']);
        $stmt->bindValue(':cnpj', $data['cnpj'] ?? null);
        $stmt->bindValue(':inscricao_estadual', $data['inscricao_estadual'] ?? null);
        $stmt->bindValue(':endereco', $data['endereco'] ?? null);
        $stmt->bindValue(':cidade', $data['cidade'] ?? null);
        $stmt->bindValue(':estado', $data['estado'] ?? null);
        $stmt->bindValue(':cep', $data['cep'] ?? null);
        $stmt->bindValue(':telefone', $data['telefone'] ?? null);
        $stmt->bindValue(':email', $data['email'] ?? null);
        $stmt->bindValue(':responsavel', $data['responsavel'] ?? null);
        $stmt->bindValue(':observacoes', $data['observacoes'] ?? null);
        
        if ($stmt->execute()) {
            return $this->conn->lastInsertId();
        }
        
        return false;
    }
    
    /**
     * Atualiza filial
     */
    public function update($id, $data) {
        $sql = "UPDATE {$this->table} 
                SET nome = :nome, cnpj = :cnpj, inscricao_estadual = :inscricao_estadual,
                    endereco = :endereco, cidade = :cidade, estado = :estado, cep = :cep,
                    telefone = :telefone, email = :email, responsavel = :responsavel,
                    observacoes = :observacoes, data_atualizacao = CURRENT_TIMESTAMP
                WHERE id = :id";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id', $id);
        $stmt->bindParam(':nome', $data['nome']);
        $stmt->bindValue(':cnpj', $data['cnpj'] ?? null);
        $stmt->bindValue(':inscricao_estadual', $data['inscricao_estadual'] ?? null);
        $stmt->bindValue(':endereco', $data['endereco'] ?? null);
        $stmt->bindValue(':cidade', $data['cidade'] ?? null);
        $stmt->bindValue(':estado', $data['estado'] ?? null);
        $stmt->bindValue(':cep', $data['cep'] ?? null);
        $stmt->bindValue(':telefone', $data['telefone'] ?? null);
        $stmt->bindValue(':email', $data['email'] ?? null);
        $stmt->bindValue(':responsavel', $data['responsavel'] ?? null);
        $stmt->bindValue(':observacoes', $data['observacoes'] ?? null);
        
        return $stmt->execute();
    }
    
    /**
     * Soft delete - marca como inativo
     */
    public function delete($id) {
        $sql = "UPDATE {$this->table} 
                SET ativo = 0, data_atualizacao = CURRENT_TIMESTAMP 
                WHERE id = :id";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id', $id);
        
        return $stmt->execute();
    }
    
    /**
     * Verifica se CNPJ já existe
     */
    public function cnpjExists($cnpj, $excludeId = null) {
        $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE cnpj = :cnpj";
        
        if ($excludeId) {
            $sql .= " AND id != :exclude_id";
        }
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':cnpj', $cnpj);
        
        if ($excludeId) {
            $stmt->bindParam(':exclude_id', $excludeId);
        }
        
        $stmt->execute();
        $result = $stmt->fetch();
        
        return $result['count'] > 0;
    }
    
    /**
     * Lista todas as filiais ativas (para selects)
     */
    public function listAll($empresaId = null) {
        $sql = "SELECT f.id, f.nome, f.empresa_id, e.nome as empresa_nome
                FROM {$this->table} f
                LEFT JOIN empresas e ON f.empresa_id = e.id
                WHERE f.ativo = 1";
        
        $params = [];
        
        if ($empresaId) {
            $sql .= " AND f.empresa_id = :empresa_id";
            $params[':empresa_id'] = $empresaId;
        }
        
        $sql .= " ORDER BY f.nome ASC";
        
        $stmt = $this->conn->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();
        
        return $stmt->fetchAll();
    }
    
    /**
     * Conta o número total de filiais ativas
     */
    public function count() {
        $sql = "SELECT COUNT(*) as total FROM {$this->table} WHERE ativo = 1";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        
        $result = $stmt->fetch();
        return $result ? (int)$result['total'] : 0;
    }
    
    /**
     * Conta o número de filiais de uma empresa
     */
    public function countByCompany($empresaId) {
        $sql = "SELECT COUNT(*) as total FROM {$this->table} WHERE empresa_id = :empresa_id AND ativo = 1";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':empresa_id', $empresaId);
        $stmt->execute();
        
        $result = $stmt->fetch();
        return $result ? (int)$result['total'] : 0;
    }
}

?>