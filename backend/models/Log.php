<?php

require_once __DIR__ . '/../config/database.php';

class Log {
    private $pdo;
    
    public function __construct() {
        $this->pdo = getConnection();
    }
    
    /**
     * Lista logs com filtros e paginação
     */
    public function getAll($filters = [], $page = 1, $pageSize = 20) {
        $conditions = [];
        $params = [];
        
        // Filtro por empresa (para Admin da Empresa)
        if (isset($filters['empresa_id'])) {
            $conditions[] = 'l.empresa_id = :empresa_id';
            $params['empresa_id'] = $filters['empresa_id'];
        }
        
        // Filtro por busca na mensagem
        if (isset($filters['search'])) {
            $conditions[] = 'l.message LIKE :search';
            $params['search'] = '%' . $filters['search'] . '%';
        }
        
        // Filtro por nível
        if (isset($filters['level'])) {
            $conditions[] = 'l.level = :level';
            $params['level'] = $filters['level'];
        }
        
        // Filtro por data inicial
        if (isset($filters['date_from'])) {
            $conditions[] = 'DATE(l.created_at) >= :date_from';
            $params['date_from'] = $filters['date_from'];
        }
        
        // Filtro por data final
        if (isset($filters['date_to'])) {
            $conditions[] = 'DATE(l.created_at) <= :date_to';
            $params['date_to'] = $filters['date_to'];
        }
        
        $whereClause = !empty($conditions) ? 'WHERE ' . implode(' AND ', $conditions) : '';
        
        // Query para contar total
        $countSql = "
            SELECT COUNT(*) as total
            FROM logs l
            LEFT JOIN usuarios u ON l.user_id = u.id
            $whereClause
        ";
        
        $countStmt = $this->pdo->prepare($countSql);
        $countStmt->execute($params);
        $total = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Query para buscar dados
        $offset = ($page - 1) * $pageSize;
        $sql = "
            SELECT 
                l.id,
                l.level,
                l.message,
                l.context,
                l.user_id,
                l.empresa_id,
                l.ip_address,
                l.user_agent,
                l.created_at,
                u.nome as user_name
            FROM logs l
            LEFT JOIN usuarios u ON l.user_id = u.id
            $whereClause
            ORDER BY l.created_at DESC
            LIMIT :limit OFFSET :offset
        ";
        
        $stmt = $this->pdo->prepare($sql);
        
        // Bind dos parâmetros de filtro
        foreach ($params as $key => $value) {
            $stmt->bindValue(':' . $key, $value);
        }
        
        // Bind dos parâmetros de paginação como inteiros
        $stmt->bindValue(':limit', $pageSize, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        
        $stmt->execute();
        $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return [
            'items' => $logs,
            'total' => (int)$total,
            'page' => $page,
            'pageSize' => $pageSize,
            'totalPages' => ceil($total / $pageSize)
        ];
    }
    
    /**
     * Cria um novo log
     */
    public function create($data) {
        $sql = "
            INSERT INTO logs (
                level, message, context, user_id, empresa_id, 
                ip_address, user_agent, created_at
            ) VALUES (
                :level, :message, :context, :user_id, :empresa_id,
                :ip_address, :user_agent, NOW()
            )
        ";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'level' => $data['level'],
            'message' => $data['message'],
            'context' => $data['context'],
            'user_id' => $data['user_id'],
            'empresa_id' => $data['empresa_id'],
            'ip_address' => $data['ip_address'],
            'user_agent' => $data['user_agent']
        ]);
        
        return $this->pdo->lastInsertId();
    }
    
    /**
     * Limpa todos os logs
     */
    public function clearAll() {
        $sql = "DELETE FROM logs";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        
        return $stmt->rowCount();
    }
    
    /**
     * Obtém estatísticas dos logs
     */
    public function getStats($filters = []) {
        $conditions = [];
        $params = [];
        
        // Filtro por empresa (para Admin da Empresa)
        if (isset($filters['empresa_id'])) {
            $conditions[] = 'empresa_id = :empresa_id';
            $params['empresa_id'] = $filters['empresa_id'];
        }
        
        $whereClause = !empty($conditions) ? 'WHERE ' . implode(' AND ', $conditions) : '';
        
        // Estatísticas gerais
        $generalSql = "
            SELECT 
                COUNT(*) as total,
                COUNT(CASE WHEN level = 'error' THEN 1 END) as errors,
                COUNT(CASE WHEN level = 'warning' THEN 1 END) as warnings,
                COUNT(CASE WHEN level = 'info' THEN 1 END) as info,
                COUNT(CASE WHEN level = 'debug' THEN 1 END) as debug,
                COUNT(CASE WHEN DATE(created_at) = CURDATE() THEN 1 END) as today
            FROM logs
            $whereClause
        ";
        
        $stmt = $this->pdo->prepare($generalSql);
        $stmt->execute($params);
        $general = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Logs por dia (últimos 7 dias)
        $dailySql = "
            SELECT 
                DATE(created_at) as date,
                COUNT(*) as count,
                COUNT(CASE WHEN level = 'error' THEN 1 END) as errors
            FROM logs
            $whereClause
            " . (!empty($conditions) ? ' AND ' : ' WHERE ') . "
            created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
            GROUP BY DATE(created_at)
            ORDER BY date DESC
        ";
        
        $stmt = $this->pdo->prepare($dailySql);
        $stmt->execute($params);
        $daily = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Top usuários com mais logs
        $usersSql = "
            SELECT 
                u.nome as user_name,
                COUNT(*) as count
            FROM logs l
            LEFT JOIN usuarios u ON l.user_id = u.id
            $whereClause
            " . (!empty($conditions) ? ' AND ' : ' WHERE ') . "
            l.user_id IS NOT NULL
            GROUP BY l.user_id, u.nome
            ORDER BY count DESC
            LIMIT 5
        ";
        
        $stmt = $this->pdo->prepare($usersSql);
        $stmt->execute($params);
        $topUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return [
            'general' => $general,
            'daily' => $daily,
            'topUsers' => $topUsers
        ];
    }
    
    /**
     * Método estático para registrar logs facilmente
     */
    public static function write($level, $message, $context = null, $userId = null, $empresaId = null) {
        try {
            $log = new self();
            $log->create([
                'level' => $level,
                'message' => $message,
                'context' => $context,
                'user_id' => $userId,
                'empresa_id' => $empresaId,
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null
            ]);
        } catch (Exception $e) {
            // Falha silenciosa para não quebrar a aplicação
            error_log('Erro ao registrar log: ' . $e->getMessage());
        }
    }
    
    /**
     * Métodos de conveniência para diferentes níveis
     */
    public static function debug($message, $context = null, $userId = null, $empresaId = null) {
        self::write('debug', $message, $context, $userId, $empresaId);
    }
    
    public static function info($message, $context = null, $userId = null, $empresaId = null) {
        self::write('info', $message, $context, $userId, $empresaId);
    }
    
    public static function warning($message, $context = null, $userId = null, $empresaId = null) {
        self::write('warning', $message, $context, $userId, $empresaId);
    }
    
    public static function error($message, $context = null, $userId = null, $empresaId = null) {
        self::write('error', $message, $context, $userId, $empresaId);
    }
}
?>