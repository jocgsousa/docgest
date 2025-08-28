<?php

require_once __DIR__ . '/../config/database.php';

class DocumentType {
    private $conn;
    private $table = 'tipos_documentos';
    
    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }
    
    public function findAll($userId = null, $userType = null, $empresaId = null) {
        try {
            $query = "SELECT td.*, u.nome as criado_por_nome, e.nome as empresa_nome 
                     FROM {$this->table} td 
                     LEFT JOIN usuarios u ON td.criado_por = u.id 
                     LEFT JOIN empresas e ON td.empresa_id = e.id";
            
            $whereConditions = [];
            $params = [];
            
            // Aplicar filtros baseados no tipo de usuário
            if ($userType && $userType != 1) { // Não é Super Admin
                $whereConditions[] = "(td.empresa_id IS NULL OR td.empresa_id = :empresa_id)";
                $params[':empresa_id'] = $empresaId;
            }
            
            if (!empty($whereConditions)) {
                $query .= " WHERE " . implode(' AND ', $whereConditions);
            }
            
            $query .= " ORDER BY td.nome ASC";
            
            $stmt = $this->conn->prepare($query);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Erro ao buscar tipos de documentos: " . $e->getMessage());
            return false;
        }
    }
    
    public function findAllPaginated($page = 1, $limit = 10, $search = '', $status = '', $userId = null, $userType = null, $empresaId = null) {
        try {
            $offset = ($page - 1) * $limit;
            
            // Construir condições WHERE
            $whereConditions = [];
            $params = [];
            
            // Aplicar filtros baseados no tipo de usuário
            if ($userType && $userType != 1) { // Não é Super Admin
                $whereConditions[] = "(td.empresa_id IS NULL OR td.empresa_id = :empresa_id)";
                $params[':empresa_id'] = $empresaId;
            }
            
            if (!empty($search)) {
                $whereConditions[] = "(td.nome LIKE :search OR td.descricao LIKE :search)";
                $params[':search'] = "%{$search}%";
            }
            
            if ($status !== '') {
                $whereConditions[] = "td.ativo = :status";
                $params[':status'] = $status;
            }
            
            $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';
            
            // Query para contar total de registros
            $countQuery = "SELECT COUNT(*) as total FROM {$this->table} td 
                         LEFT JOIN usuarios u ON td.criado_por = u.id 
                         LEFT JOIN empresas e ON td.empresa_id = e.id 
                         {$whereClause}";
            $countStmt = $this->conn->prepare($countQuery);
            foreach ($params as $key => $value) {
                $countStmt->bindValue($key, $value);
            }
            $countStmt->execute();
            $total = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            // Query para buscar dados paginados
            $dataQuery = "SELECT td.*, u.nome as criado_por_nome, e.nome as empresa_nome 
                         FROM {$this->table} td 
                         LEFT JOIN usuarios u ON td.criado_por = u.id 
                         LEFT JOIN empresas e ON td.empresa_id = e.id 
                         {$whereClause} ORDER BY td.nome ASC LIMIT :limit OFFSET :offset";
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
            $query = "INSERT INTO {$this->table} (nome, descricao, criado_por, empresa_id, ativo, data_criacao) 
                     VALUES (:nome, :descricao, :criado_por, :empresa_id, :ativo, NOW())";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':nome', $data['nome']);
            $stmt->bindParam(':descricao', $data['descricao']);
            $stmt->bindParam(':criado_por', $data['criado_por'], PDO::PARAM_INT);
            $stmt->bindParam(':empresa_id', $data['empresa_id'], PDO::PARAM_INT);
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
    
    public function existsByName($nome, $empresaId = null, $excludeId = null) {
        try {
            $query = "SELECT COUNT(*) FROM {$this->table} WHERE nome = :nome AND empresa_id";
            
            if ($empresaId === null) {
                $query .= " IS NULL";
            } else {
                $query .= " = :empresa_id";
            }
            
            if ($excludeId) {
                $query .= " AND id != :exclude_id";
            }
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':nome', $nome);
            
            if ($empresaId !== null) {
                $stmt->bindParam(':empresa_id', $empresaId, PDO::PARAM_INT);
            }
            
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
    
    public function getActiveTypes($userId = null, $userType = null, $empresaId = null) {
        try {
            $query = "SELECT * FROM {$this->table} WHERE ativo = 1";
            $params = [];
            
            // Aplicar filtros baseados no tipo de usuário
            if ($userType && $userType != 1) { // Não é Super Admin
                $query .= " AND (empresa_id IS NULL OR empresa_id = :empresa_id)";
                $params[':empresa_id'] = $empresaId;
            }
            
            $query .= " ORDER BY nome ASC";
            
            $stmt = $this->conn->prepare($query);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Erro ao buscar tipos de documentos ativos: " . $e->getMessage());
            return false;
        }
    }
    
    public function canEdit($id, $userId, $userType, $empresaId = null) {
        try {
            // Super Admin pode editar tudo
            if ($userType == 1) {
                return true;
            }
            
            $query = "SELECT criado_por, empresa_id FROM {$this->table} WHERE id = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            
            $documentType = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$documentType) {
                return false;
            }
            
            // Não pode editar classificações do super admin (empresa_id = NULL)
            if ($documentType['empresa_id'] === null) {
                return false;
            }
            
            // Pode editar apenas se for da mesma empresa e criado por ele
            return $documentType['empresa_id'] == $empresaId && $documentType['criado_por'] == $userId;
            
        } catch (PDOException $e) {
            error_log("Erro ao verificar permissão de edição: " . $e->getMessage());
            return false;
        }
    }
    
    public function canDelete($id, $userId, $userType, $empresaId = null) {
        try {
            // Super Admin pode deletar tudo
            if ($userType == 1) {
                return true;
            }
            
            $query = "SELECT criado_por, empresa_id FROM {$this->table} WHERE id = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            
            $documentType = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$documentType) {
                return false;
            }
            
            // Não pode deletar classificações do super admin (empresa_id = NULL)
            if ($documentType['empresa_id'] === null) {
                return false;
            }
            
            // Pode deletar apenas se for da mesma empresa e criado por ele
            return $documentType['empresa_id'] == $empresaId && $documentType['criado_por'] == $userId;
            
        } catch (PDOException $e) {
            error_log("Erro ao verificar permissão de exclusão: " . $e->getMessage());
            return false;
        }
    }
}