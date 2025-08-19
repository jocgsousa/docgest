<?php

require_once __DIR__ . '/../config/database.php';

class Company {
    private $db;
    
    public function __construct() {
        $this->db = getConnection();
    }
    
    /**
     * Lista empresas com filtros e paginação
     */
    public function list($filters = [], $page = 1, $pageSize = 10) {
        $offset = ($page - 1) * $pageSize;
        
        $where = ['e.ativo = 1'];
        $params = [];
        
        if (isset($filters['search']) && !empty($filters['search'])) {
            $where[] = '(e.nome LIKE :search OR e.cnpj LIKE :search OR e.email LIKE :search)';
            $params[':search'] = '%' . $filters['search'] . '%';
        }
        
        if (isset($filters['plano_id'])) {
            $where[] = 'e.plano_id = :plano_id';
            $params[':plano_id'] = $filters['plano_id'];
        }
        
        if (isset($filters['empresa_id'])) {
            $where[] = 'e.id = :empresa_id';
            $params[':empresa_id'] = $filters['empresa_id'];
        }
        
        $whereClause = implode(' AND ', $where);
        
        // Contar total
        $countSql = "SELECT COUNT(*) as total FROM empresas e WHERE $whereClause";
        $countStmt = $this->db->prepare($countSql);
        $countStmt->execute($params);
        $total = $countStmt->fetch()['total'];
        
        // Buscar dados
        $sql = "SELECT e.*, p.nome as plano_nome, p.preco as plano_preco,
                       p.limite_usuarios, p.limite_documentos, p.limite_assinaturas
                FROM empresas e
                LEFT JOIN planos p ON e.plano_id = p.id
                WHERE $whereClause
                ORDER BY e.nome ASC
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
     * Busca empresa por ID
     */
    public function findById($id) {
        $sql = "SELECT e.*, p.nome as plano_nome, p.preco as plano_preco,
                       p.limite_usuarios, p.limite_documentos, p.limite_assinaturas
                FROM empresas e
                LEFT JOIN planos p ON e.plano_id = p.id
                WHERE e.id = :id AND e.ativo = 1";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetch();
    }
    
    /**
     * Busca empresa por CNPJ
     */
    public function findByCnpj($cnpj) {
        $sql = "SELECT * FROM empresas WHERE cnpj = :cnpj AND ativo = 1";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':cnpj', $cnpj);
        $stmt->execute();
        
        return $stmt->fetch();
    }
    
    /**
     * Cria nova empresa
     */
    public function create($data) {
        $sql = "INSERT INTO empresas (nome, cnpj, email, telefone, endereco, cidade, estado, cep, plano_id, data_vencimento)
                VALUES (:nome, :cnpj, :email, :telefone, :endereco, :cidade, :estado, :cep, :plano_id, :data_vencimento)";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':nome', $data['nome']);
        $stmt->bindValue(':cnpj', $data['cnpj']);
        $stmt->bindValue(':email', $data['email']);
        $stmt->bindValue(':telefone', $data['telefone']);
        $stmt->bindValue(':endereco', $data['endereco'] ?? null);
        $stmt->bindValue(':cidade', $data['cidade'] ?? null);
        $stmt->bindValue(':estado', $data['estado'] ?? null);
        $stmt->bindValue(':cep', $data['cep'] ?? null);
        $stmt->bindValue(':plano_id', $data['plano_id']);
        $stmt->bindValue(':data_vencimento', $data['data_vencimento']);
        
        if ($stmt->execute()) {
            $id = $this->db->lastInsertId();
            return $this->findById($id);
        }
        
        return false;
    }
    
    /**
     * Atualiza empresa
     */
    public function update($id, $data) {
        $fields = [];
        $params = [':id' => $id];
        
        $allowedFields = ['nome', 'cnpj', 'email', 'telefone', 'endereco', 'cidade', 'estado', 'cep', 'plano_id', 'data_vencimento'];
        
        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $fields[] = "$field = :$field";
                $params[":$field"] = $data[$field];
            }
        }
        
        if (empty($fields)) {
            return $this->findById($id);
        }
        
        $sql = "UPDATE empresas SET " . implode(', ', $fields) . " WHERE id = :id";
        
        $stmt = $this->db->prepare($sql);
        
        if ($stmt->execute($params)) {
            return $this->findById($id);
        }
        
        return false;
    }
    
    /**
     * Remove empresa (soft delete)
     */
    public function delete($id) {
        $sql = "UPDATE empresas SET ativo = 0 WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        
        return $stmt->execute();
    }
    
    /**
     * Verifica se CNPJ já existe
     */
    public function cnpjExists($cnpj, $excludeId = null) {
        $sql = "SELECT COUNT(*) as count FROM empresas WHERE cnpj = :cnpj AND ativo = 1";
        
        if ($excludeId) {
            $sql .= " AND id != :exclude_id";
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':cnpj', $cnpj);
        
        if ($excludeId) {
            $stmt->bindValue(':exclude_id', $excludeId, PDO::PARAM_INT);
        }
        
        $stmt->execute();
        $result = $stmt->fetch();
        
        return $result['count'] > 0;
    }
    
    /**
     * Verifica se email já existe
     */
    public function emailExists($email, $excludeId = null) {
        $sql = "SELECT COUNT(*) as count FROM empresas WHERE email = :email AND ativo = 1";
        
        if ($excludeId) {
            $sql .= " AND id != :exclude_id";
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':email', $email);
        
        if ($excludeId) {
            $stmt->bindValue(':exclude_id', $excludeId, PDO::PARAM_INT);
        }
        
        $stmt->execute();
        $result = $stmt->fetch();
        
        return $result['count'] > 0;
    }
    
    /**
     * Busca empresas com vencimento próximo
     */
    public function findExpiringCompanies($days = 7) {
        $sql = "SELECT e.*, p.nome as plano_nome
                FROM empresas e
                LEFT JOIN planos p ON e.plano_id = p.id
                WHERE e.ativo = 1 
                AND e.data_vencimento <= DATE_ADD(CURDATE(), INTERVAL :days DAY)
                AND e.data_vencimento >= CURDATE()
                ORDER BY e.data_vencimento ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':days', $days, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }
    
    /**
     * Busca empresas vencidas
     */
    public function findExpiredCompanies() {
        $sql = "SELECT e.*, p.nome as plano_nome
                FROM empresas e
                LEFT JOIN planos p ON e.plano_id = p.id
                WHERE e.ativo = 1 
                AND e.data_vencimento < CURDATE()
                ORDER BY e.data_vencimento ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }
    
    /**
     * Conta empresas por plano
     */
    public function countByPlan() {
        $sql = "SELECT p.nome as plano_nome, COUNT(e.id) as total
                FROM planos p
                LEFT JOIN empresas e ON p.id = e.plano_id AND e.ativo = 1
                GROUP BY p.id, p.nome
                ORDER BY p.nome";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }
    
    /**
     * Estatísticas gerais de empresas
     */
    public function getStats() {
        $sql = "SELECT 
                    COUNT(*) as total_empresas,
                    COUNT(CASE WHEN data_vencimento >= CURDATE() THEN 1 END) as empresas_ativas,
                    COUNT(CASE WHEN data_vencimento < CURDATE() THEN 1 END) as empresas_vencidas,
                    COUNT(CASE WHEN data_vencimento BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY) THEN 1 END) as vencendo_7_dias
                FROM empresas 
                WHERE ativo = 1";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        
        return $stmt->fetch();
    }
    
    /**
     * Atualiza data de vencimento
     */
    public function updateExpiration($id, $dataVencimento) {
        $sql = "UPDATE empresas SET data_vencimento = :data_vencimento WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->bindValue(':data_vencimento', $dataVencimento);
        
        return $stmt->execute();
    }
    
    /**
     * Lista todas as empresas ativas (para selects)
     */
    public function listAll() {
        $sql = "SELECT id, nome, cnpj FROM empresas WHERE ativo = 1 ORDER BY nome";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }
    
    /**
     * Lista empresa específica do usuário (para selects)
     */
    public function listByUser($empresaId) {
        $sql = "SELECT id, nome, cnpj FROM empresas WHERE id = :empresa_id AND ativo = 1";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':empresa_id', $empresaId, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }
    
    /**
     * Obtém empresas para relatório
     */
    public function getForReport($filters = []) {
        $conditions = ['e.ativo = 1'];
        $params = [];
        
        if (!empty($filters['start_date'])) {
            $conditions[] = 'DATE(e.data_criacao) >= :start_date';
            $params[':start_date'] = $filters['start_date'];
        }
        
        if (!empty($filters['end_date'])) {
            $conditions[] = 'DATE(e.data_criacao) <= :end_date';
            $params[':end_date'] = $filters['end_date'];
        }
        
        $whereClause = implode(' AND ', $conditions);
        
        $sql = "SELECT e.nome, e.cnpj, e.email, p.nome as plano, 
                       e.data_vencimento,
                       CASE WHEN e.ativo = 1 THEN 'Ativa' ELSE 'Inativa' END as status
                FROM empresas e
                LEFT JOIN planos p ON e.plano_id = p.id
                WHERE {$whereClause}
                ORDER BY e.nome ASC";
        
        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Conta total de empresas ativas
     */
    public function count() {
        $sql = "SELECT COUNT(*) FROM empresas WHERE ativo = 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        
        return $stmt->fetchColumn();
    }
    
    /**
     * Calcula o uso do plano de uma empresa
     */
    public function getPlanUsage($empresaId) {
        $sql = "SELECT e.id, e.nome, p.limite_usuarios, p.limite_documentos, p.limite_assinaturas, p.limite_filiais,
                       (SELECT COUNT(*) FROM usuarios WHERE empresa_id = e.id AND ativo = 1) as usuarios_usados,
                       (SELECT COUNT(*) FROM documentos WHERE empresa_id = e.id AND ativo = 1) as documentos_usados,
                       (SELECT COUNT(*) FROM assinaturas WHERE empresa_id = e.id AND ativo = 1) as assinaturas_usadas
                FROM empresas e
                LEFT JOIN planos p ON e.plano_id = p.id
                WHERE e.id = :empresa_id AND e.ativo = 1";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':empresa_id', $empresaId);
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result) {
            // Calcular percentuais
            $result['percentual_usuarios'] = $result['limite_usuarios'] > 0 ? 
                round(($result['usuarios_usados'] / $result['limite_usuarios']) * 100, 2) : 0;
            $result['percentual_documentos'] = $result['limite_documentos'] > 0 ? 
                round(($result['documentos_usados'] / $result['limite_documentos']) * 100, 2) : 0;
            $result['percentual_assinaturas'] = $result['limite_assinaturas'] > 0 ? 
                round(($result['assinaturas_usadas'] / $result['limite_assinaturas']) * 100, 2) : 0;
        }
        
        return $result;
    }
}

?>