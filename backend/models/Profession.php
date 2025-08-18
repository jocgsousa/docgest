<?php

require_once __DIR__ . '/../config/database.php';

class Profession {
    private $conn;
    private $table = 'profissoes';
    
    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }
    
    /**
     * Busca profissão por ID
     */
    public function findById($id) {
        $sql = "SELECT * FROM {$this->table} WHERE id = :id AND ativo = 1";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        
        return $stmt->fetch();
    }
    
    /**
     * Busca profissão por nome
     */
    public function findByName($nome) {
        $sql = "SELECT * FROM {$this->table} WHERE nome = :nome";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':nome', $nome);
        $stmt->execute();
        
        return $stmt->fetch();
    }
    
    /**
     * Lista profissões com filtros
     */
    public function list($filters = [], $page = 1, $pageSize = 20) {
        $where = ['ativo = 1'];
        $params = [];
        
        // Filtros
        if (!empty($filters['search'])) {
            $where[] = '(nome LIKE :search OR descricao LIKE :search)';
            $params[':search'] = '%' . $filters['search'] . '%';
        }
        
        if (isset($filters['ativo'])) {
            $where[] = 'ativo = :ativo';
            $params[':ativo'] = $filters['ativo'];
        }
        
        $whereClause = implode(' AND ', $where);
        
        // Contar total
        $countSql = "SELECT COUNT(*) FROM {$this->table} WHERE {$whereClause}";
        $countStmt = $this->conn->prepare($countSql);
        $countStmt->execute($params);
        $total = $countStmt->fetchColumn();
        
        // Buscar dados
        $offset = ($page - 1) * $pageSize;
        $sql = "SELECT * FROM {$this->table} 
                WHERE {$whereClause}
                ORDER BY nome ASC
                LIMIT :offset, :pageSize";
        
        $stmt = $this->conn->prepare($sql);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->bindValue(':pageSize', $pageSize, PDO::PARAM_INT);
        $stmt->execute();
        
        return [
            'data' => $stmt->fetchAll(),
            'total' => $total
        ];
    }
    
    /**
     * Lista todas as profissões ativas (para selects)
     */
    public function listAll() {
        $sql = "SELECT id, nome FROM {$this->table} WHERE ativo = 1 ORDER BY nome ASC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }
    
    /**
     * Cria uma nova profissão
     */
    public function create($data) {
        // Verificar se já existe uma profissão com o mesmo nome
        if ($this->findByName($data['nome'])) {
            throw new Exception('Já existe uma profissão com este nome');
        }
        
        $sql = "INSERT INTO {$this->table} 
                (nome, descricao, ativo, data_criacao)
                VALUES 
                (:nome, :descricao, 1, NOW())";
        
        $stmt = $this->conn->prepare($sql);
        
        $stmt->bindParam(':nome', $data['nome']);
        $stmt->bindParam(':descricao', $data['descricao']);
        
        if ($stmt->execute()) {
            return $this->findById($this->conn->lastInsertId());
        }
        
        return false;
    }
    
    /**
     * Atualiza uma profissão
     */
    public function update($id, $data) {
        // Verificar se existe outra profissão com o mesmo nome
        if (isset($data['nome'])) {
            $existing = $this->findByName($data['nome']);
            if ($existing && $existing['id'] != $id) {
                throw new Exception('Já existe uma profissão com este nome');
            }
        }
        
        $fields = [];
        $params = [':id' => $id];
        
        $allowedFields = ['nome', 'descricao', 'ativo'];
        
        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $fields[] = "{$field} = :{$field}";
                $params[":{$field}"] = $data[$field];
            }
        }
        
        if (empty($fields)) {
            return false;
        }
        
        $fields[] = "data_atualizacao = NOW()";
        $fieldsStr = implode(', ', $fields);
        
        $sql = "UPDATE {$this->table} SET {$fieldsStr} WHERE id = :id";
        $stmt = $this->conn->prepare($sql);
        
        if ($stmt->execute($params)) {
            return $this->findById($id);
        }
        
        return false;
    }
    
    /**
     * Desativa uma profissão (soft delete)
     */
    public function delete($id) {
        // Verificar se existem usuários usando esta profissão
        $checkSql = "SELECT COUNT(*) FROM usuarios WHERE profissao_id = :id AND ativo = 1";
        $checkStmt = $this->conn->prepare($checkSql);
        $checkStmt->bindParam(':id', $id);
        $checkStmt->execute();
        
        if ($checkStmt->fetchColumn() > 0) {
            throw new Exception('Não é possível excluir esta profissão pois existem usuários vinculados a ela');
        }
        
        $sql = "UPDATE {$this->table} SET ativo = 0, data_atualizacao = NOW() WHERE id = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id', $id);
        
        return $stmt->execute();
    }
    
    /**
     * Ativa uma profissão
     */
    public function activate($id) {
        $sql = "UPDATE {$this->table} SET ativo = 1, data_atualizacao = NOW() WHERE id = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id', $id);
        
        return $stmt->execute();
    }
    
    /**
     * Conta quantos usuários usam uma profissão
     */
    public function countUsers($id) {
        $sql = "SELECT COUNT(*) FROM usuarios WHERE profissao_id = :id AND ativo = 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        
        return $stmt->fetchColumn();
    }
    
    /**
     * Busca profissão por código
     */
    public function findByCode($codigo) {
        // Como a tabela não tem campo codigo, vamos buscar por nome
        return $this->findByName($codigo);
    }
    
    /**
     * Atualiza profissão por código
     */
    public function updateByCode($codigo, $data) {
        // Como a tabela não tem campo codigo, vamos atualizar por nome
        return $this->updateByName($codigo, $data);
    }
    
    /**
     * Atualiza profissão por nome
     */
    public function updateByName($nome, $data) {
        $fields = [];
        $params = [':nome' => $nome];
        
        foreach ($data as $key => $value) {
            $fields[] = "{$key} = :{$key}";
            $params[":{$key}"] = $value;
        }
        
        $fields[] = "data_atualizacao = NOW()";
        
        $sql = "UPDATE {$this->table} SET " . implode(', ', $fields) . " WHERE nome = :nome";
        
        $stmt = $this->conn->prepare($sql);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        return $stmt->execute();
    }
}