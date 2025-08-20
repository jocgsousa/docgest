<?php

require_once __DIR__ . '/../config/database.php';

class User {
    private $conn;
    private $table = 'usuarios';
    
    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }
    
    /**
     * Busca usuário por email
     */
    public function findByEmail($email) {
        $sql = "SELECT u.*, e.nome as empresa_nome, e.cnpj as empresa_cnpj, e.codigo_empresa,
                       f.nome as filial_nome, p.nome as plano_nome
                FROM {$this->table} u
                LEFT JOIN empresas e ON u.empresa_id = e.id
                LEFT JOIN filiais f ON u.filial_id = f.id
                LEFT JOIN planos p ON e.plano_id = p.id
                WHERE u.email = :email AND u.ativo = 1";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        
        return $stmt->fetch();
    }
    
    /**
     * Busca usuário por ID
     */
    public function findById($id) {
        $sql = "SELECT u.*, e.nome as empresa_nome, e.cnpj as empresa_cnpj, e.codigo_empresa,
                       f.nome as filial_nome, p.nome as plano_nome
                FROM {$this->table} u
                LEFT JOIN empresas e ON u.empresa_id = e.id
                LEFT JOIN filiais f ON u.filial_id = f.id
                LEFT JOIN planos p ON e.plano_id = p.id
                WHERE u.id = :id AND u.ativo = 1";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        
        return $stmt->fetch();
    }
    
    /**
     * Lista usuários com filtros
     */
    public function list($filters = [], $page = 1, $pageSize = 20) {
        $where = ['u.ativo = 1'];
        $params = [];
        
        // Filtros
        if (!empty($filters['empresa_id'])) {
            $where[] = 'u.empresa_id = :empresa_id';
            $params[':empresa_id'] = $filters['empresa_id'];
        }
        
        if (!empty($filters['filial_id'])) {
            $where[] = 'u.filial_id = :filial_id';
            $params[':filial_id'] = $filters['filial_id'];
        }
        
        if (!empty($filters['tipo_usuario'])) {
            $where[] = 'u.tipo_usuario = :tipo_usuario';
            $params[':tipo_usuario'] = $filters['tipo_usuario'];
        }
        
        if (!empty($filters['search'])) {
            $where[] = '(u.nome LIKE :search OR u.email LIKE :search)';
            $params[':search'] = '%' . $filters['search'] . '%';
        }
        
        if (!empty($filters['exclude_user_id'])) {
            $where[] = 'u.id != :exclude_user_id';
            $params[':exclude_user_id'] = $filters['exclude_user_id'];
        }
        
        $whereClause = implode(' AND ', $where);
        
        // Contar total
        $countSql = "SELECT COUNT(*) FROM {$this->table} u WHERE {$whereClause}";
        $countStmt = $this->conn->prepare($countSql);
        $countStmt->execute($params);
        $total = $countStmt->fetchColumn();
        
        // Buscar dados
        $offset = ($page - 1) * $pageSize;
        $sql = "SELECT u.*, e.nome as empresa_nome, f.nome as filial_nome
                FROM {$this->table} u
                LEFT JOIN empresas e ON u.empresa_id = e.id
                LEFT JOIN filiais f ON u.filial_id = f.id
                WHERE {$whereClause}
                ORDER BY u.nome ASC
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
     * Cria um novo usuário
     */
    public function create($data) {
        $sql = "INSERT INTO {$this->table} 
                (nome, email, senha, cpf, telefone, profissao_id, tipo_usuario, empresa_id, filial_id, ativo, data_criacao)
                VALUES 
                (:nome, :email, :senha, :cpf, :telefone, :profissao_id, :tipo_usuario, :empresa_id, :filial_id, 1, NOW())";
        
        $stmt = $this->conn->prepare($sql);
        
        // Hash da senha
        $hashedPassword = password_hash($data['senha'], PASSWORD_DEFAULT);
        
        $stmt->bindParam(':nome', $data['nome']);
        $stmt->bindParam(':email', $data['email']);
        $stmt->bindParam(':senha', $hashedPassword);
        $stmt->bindParam(':cpf', $data['cpf']);
        $stmt->bindParam(':telefone', $data['telefone']);
        $stmt->bindParam(':profissao_id', $data['profissao_id']);
        $stmt->bindParam(':tipo_usuario', $data['tipo_usuario']);
        $stmt->bindParam(':empresa_id', $data['empresa_id']);
        $stmt->bindParam(':filial_id', $data['filial_id']);
        
        if ($stmt->execute()) {
            return $this->findById($this->conn->lastInsertId());
        }
        
        return false;
    }
    
    /**
     * Atualiza um usuário
     */
    public function update($id, $data) {
        $fields = [];
        $params = [':id' => $id];
        
        $allowedFields = ['nome', 'email', 'cpf', 'telefone', 'profissao_id', 'tipo_usuario', 'empresa_id', 'filial_id'];
        
        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $fields[] = "{$field} = :{$field}";
                $params[":{$field}"] = $data[$field];
            }
        }
        
        if (isset($data['senha'])) {
            $fields[] = "senha = :senha";
            $params[':senha'] = password_hash($data['senha'], PASSWORD_DEFAULT);
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
     * Desativa um usuário (soft delete)
     */
    public function delete($id) {
        $sql = "UPDATE {$this->table} SET ativo = 0, data_atualizacao = NOW() WHERE id = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id', $id);
        
        return $stmt->execute();
    }
    
    /**
     * Verifica se a senha está correta
     */
    public function verifyPassword($plainPassword, $hashedPassword) {
        return password_verify($plainPassword, $hashedPassword);
    }
    
    /**
     * Atualiza último login
     */
    public function updateLastLogin($id) {
        // Por enquanto, apenas atualiza data_atualizacao
        $sql = "UPDATE {$this->table} SET data_atualizacao = NOW() WHERE id = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id', $id);
        
        return $stmt->execute();
    }
    
    /**
     * Busca usuários por empresa
     */
    public function findByEmpresa($empresaId) {
        $sql = "SELECT u.*, f.nome as filial_nome
                FROM {$this->table} u
                LEFT JOIN filiais f ON u.filial_id = f.id
                WHERE u.empresa_id = :empresa_id AND u.ativo = 1
                ORDER BY u.nome ASC";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':empresa_id', $empresaId);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }
    
    /**
     * Busca usuários por filial
     */
    public function findByFilial($filialId) {
        $sql = "SELECT u.*
                FROM {$this->table} u
                WHERE u.filial_id = :filial_id AND u.ativo = 1
                ORDER BY u.nome ASC";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':filial_id', $filialId);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }
    
    /**
     * Conta usuários por tipo
     */
    public function countByType($empresaId = null) {
        $where = 'ativo = 1';
        $params = [];
        
        if ($empresaId) {
            $where .= ' AND empresa_id = :empresa_id';
            $params[':empresa_id'] = $empresaId;
        }
        
        $sql = "SELECT tipo_usuario, COUNT(*) as total
                FROM {$this->table}
                WHERE {$where}
                GROUP BY tipo_usuario";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll();
    }
    
    /**
     * Verifica se email já existe
     */
    public function emailExists($email, $excludeId = null) {
        $sql = "SELECT COUNT(*) FROM {$this->table} WHERE email = :email";
        $params = [':email' => $email];
        
        if ($excludeId) {
            $sql .= " AND id != :exclude_id";
            $params[':exclude_id'] = $excludeId;
        }
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchColumn() > 0;
    }
    
    /**
     * Verifica se CPF já existe
     */
    public function cpfExists($cpf, $excludeId = null) {
        $sql = "SELECT COUNT(*) FROM {$this->table} WHERE cpf = :cpf";
        $params = [':cpf' => $cpf];
        
        if ($excludeId) {
            $sql .= " AND id != :exclude_id";
            $params[':exclude_id'] = $excludeId;
        }
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchColumn() > 0;
    }
    
    /**
     * Obtém usuários para relatório
     */
    public function getForReport($filters = []) {
        $conditions = ['u.ativo = 1'];
        $params = [];
        
        if (!empty($filters['empresa_id'])) {
            $conditions[] = 'u.empresa_id = :empresa_id';
            $params[':empresa_id'] = $filters['empresa_id'];
        }
        
        if (!empty($filters['start_date'])) {
            $conditions[] = 'DATE(u.data_criacao) >= :start_date';
            $params[':start_date'] = $filters['start_date'];
        }
        
        if (!empty($filters['end_date'])) {
            $conditions[] = 'DATE(u.data_criacao) <= :end_date';
            $params[':end_date'] = $filters['end_date'];
        }
        
        $whereClause = implode(' AND ', $conditions);
        
        $sql = "SELECT u.nome, u.email, 
                       CASE 
                           WHEN u.tipo_usuario = 1 THEN 'Super Admin'
                           WHEN u.tipo_usuario = 2 THEN 'Admin Empresa'
                           WHEN u.tipo_usuario = 3 THEN 'Assinante'
                           ELSE 'Desconhecido'
                       END as tipo,
                       e.nome as empresa, 
                       CASE WHEN u.ativo = 1 THEN 'Ativo' ELSE 'Inativo' END as status,
                       u.data_criacao
                FROM {$this->table} u
                LEFT JOIN empresas e ON u.empresa_id = e.id
                WHERE {$whereClause}
                ORDER BY u.data_criacao DESC";
        
        $stmt = $this->conn->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Conta total de usuários ativos
     */
    public function count() {
        $sql = "SELECT COUNT(*) FROM {$this->table} WHERE ativo = 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        
        return $stmt->fetchColumn();
    }
    
    /**
     * Conta usuários ativos por empresa
     */
    public function countByCompany($empresaId) {
        $sql = "SELECT COUNT(*) FROM {$this->table} WHERE empresa_id = :empresa_id AND ativo = 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':empresa_id', $empresaId);
        $stmt->execute();
        
        return $stmt->fetchColumn();
    }
}

?>