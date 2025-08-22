<?php

require_once __DIR__ . '/../config/database.php';

class DocumentType {
    private $conn;
    private $table = 'tipos_documentos';
    
    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }
    
    public function findAll() {
        try {
            $query = "SELECT * FROM {$this->table} ORDER BY nome ASC";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Erro ao buscar tipos de documentos: " . $e->getMessage());
            return false;
        }
    }
    
    public function findAllPaginated($page = 1, $limit = 10, $search = '', $status = '') {
        try {
            $offset = ($page - 1) * $limit;
            
            // Construir condições WHERE
            $whereConditions = [];
            $params = [];
            
            if (!empty($search)) {
                $whereConditions[] = "(nome LIKE :search OR descricao LIKE :search)";
                $params[':search'] = "%{$search}%";
            }
            
            if ($status !== '') {
                $whereConditions[] = "ativo = :status";
                $params[':status'] = $status;
            }
            
            $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';
            
            // Query para contar total de registros
            $countQuery = "SELECT COUNT(*) as total FROM {$this->table} {$whereClause}";
            $countStmt = $this->conn->prepare($countQuery);
            foreach ($params as $key => $value) {
                $countStmt->bindValue($key, $value);
            }
            $countStmt->execute();
            $total = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            // Query para buscar dados paginados
            $dataQuery = "SELECT * FROM {$this->table} {$whereClause} ORDER BY nome ASC LIMIT :limit OFFSET :offset";
            $dataStmt = $this->conn->prepare($dataQuery);
            
            foreach ($params as $key => $value) {
                $dataStmt->bindValue($key, $value);
            }
            $dataStmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $dataStmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            
            $dataStmt->execute();
            $data = $dataStmt->fetchAll(PDO::FETCH_ASSOC);
            
            return [
                'data' => $data,
                'total' => (int)$total
            ];
        } catch (PDOException $e) {
            error_log("Erro ao buscar tipos de documentos paginados: " . $e->getMessage());
            return ['data' => [], 'total' => 0];
        }
    }
    
    public function findById($id) {
        try {
            $query = "SELECT * FROM {$this->table} WHERE id = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Erro ao buscar tipo de documento por ID: " . $e->getMessage());
            return false;
        }
    }
    
    public function create($data) {
        try {
            $query = "INSERT INTO {$this->table} (nome, descricao, ativo, data_criacao) 
                     VALUES (:nome, :descricao, :ativo, NOW())";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':nome', $data['nome']);
            $stmt->bindParam(':descricao', $data['descricao']);
            $stmt->bindParam(':ativo', $data['ativo'], PDO::PARAM_BOOL);
            
            if ($stmt->execute()) {
                return $this->conn->lastInsertId();
            }
            
            return false;
        } catch (PDOException $e) {
            error_log("Erro ao criar tipo de documento: " . $e->getMessage());
            return false;
        }
    }
    
    public function update($id, $data) {
        try {
            $query = "UPDATE {$this->table} 
                     SET nome = :nome, descricao = :descricao, ativo = :ativo, data_atualizacao = NOW() 
                     WHERE id = :id";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->bindParam(':nome', $data['nome']);
            $stmt->bindParam(':descricao', $data['descricao']);
            $stmt->bindParam(':ativo', $data['ativo'], PDO::PARAM_BOOL);
            
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Erro ao atualizar tipo de documento: " . $e->getMessage());
            return false;
        }
    }
    
    public function delete($id) {
        try {
            $query = "DELETE FROM {$this->table} WHERE id = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Erro ao excluir tipo de documento: " . $e->getMessage());
            return false;
        }
    }
    
    public function existsByName($nome, $excludeId = null) {
        try {
            $query = "SELECT COUNT(*) FROM {$this->table} WHERE nome = :nome";
            
            if ($excludeId) {
                $query .= " AND id != :exclude_id";
            }
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':nome', $nome);
            
            if ($excludeId) {
                $stmt->bindParam(':exclude_id', $excludeId, PDO::PARAM_INT);
            }
            
            $stmt->execute();
            
            return $stmt->fetchColumn() > 0;
        } catch (PDOException $e) {
            error_log("Erro ao verificar existência do tipo de documento: " . $e->getMessage());
            return false;
        }
    }
    
    public function hasDocuments($id) {
        try {
            $query = "SELECT COUNT(*) FROM documentos WHERE tipo_documento_id = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchColumn() > 0;
        } catch (PDOException $e) {
            error_log("Erro ao verificar documentos vinculados: " . $e->getMessage());
            return false;
        }
    }
    
    public function getActiveTypes() {
        try {
            $query = "SELECT * FROM {$this->table} WHERE ativo = 1 ORDER BY nome ASC";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Erro ao buscar tipos de documentos ativos: " . $e->getMessage());
            return false;
        }
    }
}